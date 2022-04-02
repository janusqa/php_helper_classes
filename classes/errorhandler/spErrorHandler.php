<?php 
defined( '_SPEXEC' ) or die( 'Restricted access' ); 
define( '_SPERRORHANDLER', 1 ); 
?>
<?php
class spErrorHandler
{
	
	private $_serrorcodes = array (
        '1'     => 'E_ERROR',
		'2'     => 'E_WARNING',
        '4'     => 'E_PARSE',
		'8'     => 'E_NOTICE',
        '16'    => 'E_CORE_ERROR',
		'32'    => 'E_CORE_WARNING',
        '64'    => 'E_COMPILE_ERROR',
		'128'   => 'E_COMPILE_WARNING',
        '256'   => 'E_USER_ERROR',
		'512'   => 'E_USER_WARNING',
		'1024'  => 'E_USER_NOTICE',
		'2048'  => 'E_STRICT',
		'4096'  => 'E_RECOVERABLE_ERROR',
		'8192'  => 'E_DEPRECATED',
		'16384' => 'E_USER_DEPRECATED',
		'30719' => 'E_ALL',
	);
    
    private $_warningLevels = array (
        E_WARNING,
        E_NOTICE,
        E_CORE_WARNING,
        E_COMPILE_WARNING,
        E_USER_WARNING,
        E_USER_NOTICE,
        E_STRICT,
    );

    private $_fatalLevels = array ( 
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
        E_USER_ERROR,
        E_RECOVERABLE_ERROR,
    );	
	
	public function __construct()
	{
		ini_set('error_reporting', E_ALL | E_STRICT);
		ini_set('log_errors', 1);
		ini_set('html_errors', 0);
		ini_set('error_log', _SP_DOCUMENT_ROOT . spConfig::getAppRoot() . '/debug/error_log.txt');
		ini_set('display_errors', 1);
		//set_exception_handler(array($this, 'exceptionhandler'));
		//set_error_handler(array($this, 'errorhandler'));
		//register_shutdown_function(array($this, 'shutdownhandler'));		
	}
	
    private function errortostring($e)
    {
    	$errortype = null;

    	switch ($e->getCode()) {
    		case E_ERROR:
    			$errortype = 'Fatal run-time ERRORS occured. Execution of the script is halted:';
    			break;
    		case E_WARNING:
    			$errortype = 'Run-time WARNINGS (non-fatal errors) occured. Execution of the script is not halted:';
    			break;
    		case E_PARSE:
    			$errortype = 'Compile-time PARSE ERRORS occured. Execution of the script is halted:';
    			break;
    		case E_NOTICE:
    			$errortype = 'Run-time NOTICES occured:';
    			break;
    		case E_CORE_ERROR:
    			$errortype = 'Fatal ERRORS occured during PHP\'s initial startup. Execution of the script is halted:';
    			break;
    		case E_CORE_WARNING:
    			$errortype = 'WARNIMGS (non-fatal errors) occured during PHP\'s initial startup:';
    			break;
    		case E_COMPILE_ERROR:
    			$errortype = 'Fatal compile-time ERRORS occured. Execution of the script is halted:';
    			break;
    		case E_COMPILE_WARNING:
    			$errortype = 'Compile-time WARNINGS (non-fatal errors) occured:';
    			break;
    		case E_USER_ERROR:
    			$errortype = 'User-generated ERRORS occured. Execution of the script is halted:';
    			break;
    		case E_USER_WARNING:
    			$errortype = 'User-generated WARNINGS occured:';
    			break;
    		case E_USER_NOTICE:
    			$errortype = 'User-generated NOTICES occured:';
    			break;
    		case E_STRICT: 
    			$errortype = 'Interoperability and forward compatibility NOTICES occured:';
    			break;
    		case E_RECOVERABLE_ERROR:
    			$errortype = 'Catchable fatal ERRORS occured. Execution of the script is halted:';
    			break;
    		case E_ALL:
    			$errortype = 'ERRORS and WARNINGS occured:';
    			break;
    		default:
    			if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
    				switch ($e->getCode()) {
    					case E_DEPRECATED:
    						$errortype = 'Deprecated WARNINGS occured:';
    						break;
    					case E_USER_DEPRECATED:
    						$errortype = 'User Deprecated WARNINGS occured:';
    						break;
    				}
    			}
    			else {
    				$errortype = 'Unknown ERRORS, WARNINGS and NOTICES occured:';
    			}
    			break;
    	}
    	
 		return '<pre>' .
    		   $errortype . "\n" .
     		   'in File: ' . $e->getFile() . "\n" .
    	       'at Line: ' . $e->getLine() . "\n" .
    	       'Error Code: ' . $e->getCode() . "\n" .
    		   'Error Message: ' . $e->getMessage() . "\n" .
    		   'Stack Trace: ' . "\n" . $e->getTraceAsString() . "\n" .
    	       '</pre>';    		 	    		   
    }

    public function exceptionhandler($e)
    {
    	if (error_reporting() == 0) {
    		// TODO: ERROR: Log error to backend database.
    		$this->log_error_database($e);
        	return true;
    	}   	

    	if (ini_get('display_errors')) {
    		echo $this->errortostring($e);
    	}

    	// TODO: ERROR: Log error to backend database.
    	$this->log_error_database($e);
    	
    	return true;
    }

    public function errorhandler($errno, $errstr, $errfile, $errline, $errcontext = null)
    {    	
    	$e = new ErrorException($errstr, $errno, 0, $errfile, $errline);
    	
    	if (error_reporting() == 0) {
    		// TODO: ERROR: Log error to backend database.
    		$this->log_error_database($e);
    		return true;
    	}   	
    	    	    	
        if (in_array($errno, $this->_warningLevels)) {
    		if (ini_get('display_errors')) {
    			echo $this->errortostring($e);
    		}
    		// TODO: ERROR: Log error to backend database.
    		$this->log_error_database($e);
    	}

        if (in_array($errno, $this->_fatalLevels)) {
            throw $e;
        }
        
    	return true;
    }
    
    public function shutdownhandler()
    {
   	
    	if (!$error = error_get_last()) {
    		return;
    	}    	

    	$e = new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);
    	
        if (error_reporting() == 0) {
    		// TODO: ERROR: Log error to backend database.
    		$this->log_error_database($e);
        	return;
    	} 
    	    	
		$msg = '<pre>Application terminted prematurely...</pre>' . $this->errortostring($e);

 		if (ini_get('display_errors')) {
 			echo $msg;
 		}  
 				
    	// TODO: ERROR: Log error to backend database.
 		$this->log_error_database($e); 
		unset($e);
    		       	  	
    }
    
    public function log_error_database($e) 
    {
    	
    	$db = new spDBO();

    	$stmt = new spPreparedStatement($db, 'PS_LOG_ERROR_DB');
		if ($stmt->prepare()) {
			$temp_tz = date_default_timezone_get();
			date_default_timezone_set('GMT');
			$stmtexecutestat = $stmt->execute(array($db->genUniquePrimaryKey('spt_errorlog', 'spc_id'), $e->getFile(), $e->getLine(), $e->getCode(), $this->_serrorcodes[$e->getCode()], $e->getMessage(), $e->getTraceAsString(), gmdate('Y-m-d H:i:s', time())));
			date_default_timezone_set($temp_tz);
			if ($stmtexecutestat) {
				return true;
			}
		}
		unset($stmt);
		unset($db);
		// TODO: ERROR: Log error to file.
    	return false;
    }
    
    public function log_error_file($e) 
    {
		// TODO: ERROR: Log error to file. 
    	
    }
    
    public function display_error_page($e) 
    {
     	// TODO: ERROR: Redirect to an error page and display a friendly error to user.
    }
    
    public function debug_print($v) 
    {
    	
    	echo "<pre>";
    	
    	if (is_array($v)) {
    		var_dump($v);
    		echo "</pre>";
    		return;
    	}
    	
    	if (is_bool($v)) {
    		echo (int)$v . "\n";
    		echo "</pre>";
    		return;
    	}
    	
    	if (is_object($v)) {
    		print_r($v);
    		echo "</pre>";
    		return;
    	}
    	
    	if (is_resource($v)) {	
    		print_r($v);
    		echo "</pre>";
    		return;
    	}
    	
    	echo $v . "\n";
    	echo "</pre>";
    	
    	return;
    }
        
    public function __set($property, $value) 
    {
        trigger_error('Class [' . get_class($this) . "] does not support the property: [{$property}].", E_USER_ERROR);
    }
   
    public function __get($property) 
    {
        trigger_error('Class [' . get_class($this) . "] does not support the property: [{$property}].", E_USER_ERROR);
    }

    public function __call($function, $args)
    {
    	trigger_error('Class [' . get_class($this) . "] does not support the function: [{$function}].", E_USER_ERROR);
    }

    public function __destruct()
	{
		try {
			restore_error_handler();
			restore_exception_handler();
		}
	    catch (Exception $e) { 
    		// TODO: Log error to file.
    		// DO NOTHING.
    	}		
	}
} 
?>
