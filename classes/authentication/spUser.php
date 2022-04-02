<?php
defined( '_SPEXEC' ) or die( 'Restricted access' );
define( '_SPUSER', 1 ); 
?>
<?php
class spUser
{		
	private $_db = null;
	private $_spsess = null;
	private $_spc_id = null;
	
	public function __construct($spc_id, $db, $spsess)
	{
		$this->_db =& $db;
		$this->_spsess =& $spsess;
		$this->_spc_id = $spc_id;
	}
	
	public function getPreference($key, $spc_id = null) 
	{
		$stmt = null;
		$stmtexecutestat = null;

		$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
		$stmt->set_custom_stmt('SELECT spc_value FROM spt_users_preferences WHERE ((spc_id = ?) AND (spc_key = ?))', 'ss', true);
		if ($stmt->prepare()) {
			if (isset($spc_id)) {
				$stmtexecutestat = $stmt->execute(array(spc_id, $key));
			}
			else {
				$stmtexecutestat = $stmt->execute(array($this->_spc_id, $key));
			}
			if ($stmtexecutestat) {
				if ((($results = $stmt->get_stmt_results()) != false) && isset($results)) {
					return $results[0]['spc_value'];
				}
				else {
					return null;
				}
			}
		}
		trigger_error('Class [' . get_class($this) . "] : Unable to get user preference.", E_USER_WARNING);
		return null; // TODO: ERROR: unable to get preference value
	}
	
	public function getUserID($spc_email)
	{
		$stmt = null;
		$stmtexecutestat = null;

		$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
		$stmt->set_custom_stmt('SELECT spc_id FROM spt_users WHERE (spc_email = ?)', 's', true);
		if ($stmt->prepare()) {
			$stmtexecutestat = $stmt->execute(array($spc_email));
			if ($stmtexecutestat) {
				if ((($results = $stmt->get_stmt_results()) != false) && isset($results)) {
					return $results[0]['spc_id'];
				}
				else {
					return null;
				}
			}
		}
		trigger_error('Class [' . get_class($this) . "] : Unable to get user preference.", E_USER_WARNING);
		return null; // TODO: ERROR: unable to get preference value		
	}
	
	public function setUser($spc_id)
	{
		if (isset($spc_id)) {
			$this->_spc_id = $spc_id;
		}
	}
	
	public function getUser()
	{
			return $this->_spc_id;
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
    		if (isset($this->_db)) {
				unset($this->_db);
			}
    	    if (isset($this->_spsess)) {
				unset($this->_spsess);
			}
    	}
    	catch (Exception $e) { 
    		// TODO: ERROR: Log error to file
    		// DO NOTHING.
    	}
    }	
}
?>