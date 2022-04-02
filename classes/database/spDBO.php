<?php 
defined( '_SPEXEC' ) or die( 'Restricted access' );
define( '_SPDBO', 1 ); 
spLoader::import('database:spPreparedStatement'); 
?>
<?php
class spDBO
{
	
	// HOW-TO: Defeating SQL Injections
	// --------------------------------
	// 1) Enforce DB User priviliges to the minimum necessary
	// 2) Enforce data input sizes
	// 3) Enforce data type validation
	// 4) Enforce mysql_real_escape_string() on input that will be used in queries
	// 5) Enforce bound parameters and prepared statements
	// 6) If using LIKE in a query remember to escape "%" and "_" in any parameters
	
	const DB_PORT = 3306;

    	private $_sdb;
    
    public function __construct($db_name = spConfig::DB_NAME, $db_user = spConfig::DB_USER, $db_password = spConfig::DB_PASSWORD, $db_server = spConfig::DB_SERVER, $db_port = self::DB_PORT)
    {
    	$this->_sdb['db_server'] = $db_server;
       	$this->_sdb['db_port'] = $db_port;
       	$this->_sdb['db_authentication'][$db_user] = array('db_user' => $db_user, 'db_password' => $db_password);
       	$this->_sdb['db_name'] = $db_name;
       	$this->_sdb['db_link'] = null;
       	$this->connect();
    }
    
    public function connect($db_user = spConfig::DB_USER) 
    {
    	$c_link = $this->_sdb['db_link'];
    	
		$this->_sdb['db_link'] = mysqli_connect($this->_sdb['db_server'], $this->_sdb['db_authentication'][$db_user]['db_user'], $this->_sdb['db_authentication'][$db_user]['db_password'], $this->_sdb['db_name'], $this->_sdb['db_port']);
		
        if ($this->isalive($this->_sdb['db_link'])) {
            if (($this->isalive($c_link)) && ($c_link != $this->_sdb['db_link'])) {
        		$this->disconnect($c_link);
        	}
        	unset($c_link);
        	return true;	
        }
        else {
        	// TODO: ERROR:
        	trigger_error('Class [' . get_class($this) . "] : [" . mysqli_connect_errno() . "]" . mysqli_connect_error(), E_USER_WARNING);
        }

        unset($c_link);
        return false;
	}

    public function disconnect($link) 
    {
        if ((isset($link)) && ($this->isalive($link))) {
           	if (mysqli_close($link)) {
           		return true;
           	}
           	else {
           		// TODO: ERROR:
           		trigger_error('Class [' . get_class($this) . "] : [" . mysqli_errno() . "]" . mysqli_error(), E_USER_WARNING);
           	}
        }

       	return false;
    }

    public function getconnection()
    {
    	if (isset($this->_sdb['db_link']) && ($this->isalive($this->_sdb['db_link']))) {
			return $this->_sdb['db_link'];
        }
        else if ($this->connect() && ($this->isalive($this->_sdb['db_link']))) {
        	return $this->_sdb['db_link'];
        }
        
        return false;
    }

    public function init_db($db_name, $db_user, $db_password, $db_server = spConfig::DB_SERVER, $db_port = self::DB_PORT)
    {
    	$this->_sdb['db_server'] = $db_server;
        $this->_sdb['db_port'] = $db_port;
        unset($this->_sdb['db_authentication']);
        $this->_sdb['db_authentication'][$db_user] = array('db_user' => $db_user, 'db_password' => $db_password);
    	$this->_sdb['db_name'] = $db_name;
    	$this->_sdb['db_link'] = null;
    }
    
    public function isalive($link)
    {
    	if (isset($link) && ($link instanceof mysqli)) {
    		if (mysqli_ping($link)) {
    			return true;
    		}
    		else {
    			trigger_error('Class [' . get_class($this) . "] : [" . mysqli_errno() . "]" . mysqli_error(), E_USER_WARNING);
    		}
    	}
    	
    	return false;
    }
    
    public function beginTransaction($link)
    {
    	$state = false;
    	
        if ((isset($link)) && ($this->isalive($link))) {
        	$state = mysqli_autocommit($link, false);
        	if (!$state) {
        		trigger_error('Class [' . get_class($this) . "] : [" . mysqli_errno() . "]" . mysqli_error(), E_USER_WARNING);	
        	}
        	return $state;
        }
		return false;
    }
    
    public function endTransaction($link, $transaction_state)
    {	
    	$state = null;
    	
        if ((isset($link)) && ($this->isalive($link))) {
			$state = (isset($transaction_state) && $transaction_state) ? mysqli_commit($link) : mysqli_rollback($link);
            if (!$state) {
        		trigger_error('Class [' . get_class($this) . "] : [" . mysqli_errno() . "]" . mysqli_error(), E_USER_WARNING);	
        	} 
        	if (!mysqli_autocommit($link, true)) {
        		trigger_error('Class [' . get_class($this) . "] : [" . mysqli_errno() . "]" . mysqli_error(), E_USER_WARNING);
        	}
        	return $state;
        }
		return false;
    }
    
    public function genUniquePrimaryKey($namespace = null, $keyname = null) {
    	
    	$guid = $this->genGUID();
		$results = null;
		$loopcheck = 0;
		$stmt = null;
		     	
    	if (isset($namespace)) {

			$stmt = new spPreparedStatement($this, 'PS_CUSTOM');
			$stmt->set_custom_stmt('SELECT COUNT(*) AS spc_id_exists FROM ' . $namespace . ' WHERE (' . $keyname . ' = ?)', 's', true);
			if ($stmt->prepare()) {					
				do {
        			if ($stmt->execute(array($guid))) {
        				unset($results);
        				if ((($results = $stmt->get_stmt_results()) != false) && isset($results) && isset($results[0]['spc_id_exists'])) {
        					if ($results[0]['spc_id_exists'] != 0) {
        						$guid = $this->genGUID();
        					}
        				}
        				else {
        					unset($results);
        					$guid = null;
        				}
        			}
        			else {
        				unset($results);
        				$guid = null;
        			}
        			$loopcheck++;
    			} while (isset($results) && isset($results[0]['spc_id_exists']) && ($results[0]['spc_id_exists'] != 0) && ($loopcheck < 5));
			}
			else {
				$guid = null;
			}
    	}
    	
    	return $guid; 
    }
    
    private function genGUID() {
    		
    	$guid = '';
 
		$time_low = str_pad(dechex(mt_rand(0, 65535)), 4, '0', STR_PAD_LEFT) . str_pad(dechex(mt_rand(0, 65535)), 4, '0', STR_PAD_LEFT);
		$time_mid = str_pad(dechex(mt_rand(0, 65535)), 4, '0', STR_PAD_LEFT);
 
		$time_high_and_version = mt_rand(0, 255);
		$time_high_and_version = $time_high_and_version & hexdec('0f');
		$time_high_and_version = $time_high_and_version ^ hexdec('40');  // Sets the version number to 4 in the high byte
		$time_high_and_version = str_pad(dechex($time_high_and_version), 2, '0', STR_PAD_LEFT);
 
		$clock_seq_hi_and_reserved = mt_rand(0, 255);
		$clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved & hexdec('3f');
		$clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved ^ hexdec('80');  // Sets the variant for this GUID type to '10x'
		$clock_seq_hi_and_reserved = str_pad(dechex($clock_seq_hi_and_reserved), 2, '0', STR_PAD_LEFT);
 
		$clock_seq_low = str_pad(dechex(mt_rand(0, 65535)), 4, '0', STR_PAD_LEFT);
 
		$node = str_pad(dechex(mt_rand(0, 65535)), 4, '0', STR_PAD_LEFT) . str_pad(dechex(mt_rand(0, 65535)), 4, '0', STR_PAD_LEFT) . str_pad(dechex(mt_rand(0, 65535)), 4, '0', STR_PAD_LEFT);
 
		$guid = $time_low . '-' . $time_mid . '-' . $time_high_and_version . $clock_seq_hi_and_reserved . '-' . $clock_seq_low . '-' . $node;
		
		return $guid;
    }
    
    public function info($link)
    {
    	if (isset($link) && ($this->isalive($link))) {
    		return mysqli_get_host_info($link) . "\n" . mysqli_get_server_info($link) . "\n";
    	}
    	
    	return 'No connection available at this time.' . "\n";
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
    		if (isset($this->_sdb)) {
    	    		
    			if (isset($this->_sdb['db_link'])) {
    				$this->disconnect($this->_sdb['db_link']);
    			}
    	
    			if (isset($this->_sdb['db_authentication'])) {
    				unset($this->_sdb['db_authentication']);
    			}
    	
        		unset($this->_sdb);
    		}
    	}
    	catch (Exception $e) { 
    		// TODO: ERROR: Log error to file
    		// DO NOTHING.
    	}
    }
}
?>
