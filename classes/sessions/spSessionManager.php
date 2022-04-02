<?php 
defined( '_SPEXEC' ) or die( 'Restricted access' );
define( '_SPSESSIONMANAGER', 1 ); 
?>
<?php
class spSessionManager 
{
	Const SESSIONTIMEOUTVALUE = 20;
	Const SESSIONCOOKIENAME = 'alsm';

	private $_sessionid = null;
	private $_db = null;

	public function __construct($db) 
	{
		$this->_db =& $db;
		$this->setSession($this->getToken());
	}

	private function generateToken($seeds) 
	{
		$token = null;
		
		$token = hash('sha256', implode('', $seeds));
		return $token;
	}
	
	private function setSession($token) 
	{
		if (!isset($token)) { // if no session exist then create one
			$this->initSession(true);
		}
		else { // if a session exists then verify that it is a valid and current session
			if (!$this->verifySession($token)) {
        			$this->revokeSession($token);
				$this->initSession(true);        					
			}
			else {
			  	if ($this->updateSession($token)) {
        				$this->_sessionid = $token;
        			}				
			}
		}
	}
	
	public function revokeSession($token = null, $reload = false) 
	{
		$stmt = null;
		$temp_tz = null;
		$stmtexecutestat = null;
				
		$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
		if (isset($token)) {
			$stmt->set_custom_stmt('DELETE FROM spt_sessions WHERE (spc_token = ?)', 's', false);
			if ($stmt->prepare()) {
				if ($stmt->execute(array($token))) {
					$this->_sessionid = null;
					setcookie(self::SESSIONCOOKIENAME, '', 1, '/');  // Delete cookie instantly
					if ($reload) {
						header('Location: ' . str_replace(_SP_DOCUMENT_ROOT, '', $_SERVER["SCRIPT_FILENAME"]));
					}
					return true;
				}
			}
		}
		else { //if $token = null delete all expired sessions in session table
			$stmt->set_custom_stmt('DELETE FROM spt_sessions WHERE (TIMESTAMPDIFF(SECOND, ?, spc_expiry) < 0)', 's', false);
			if ($stmt->prepare()) {
				$temp_tz = date_default_timezone_get();
				date_default_timezone_set('GMT');
				$stmtexecutestat = $stmt->execute(array(gmdate('Y-m-d H:i:s', time())));
				date_default_timezone_set($temp_tz);
				if ($stmtexecutestat) {
					return true;
				}
			}
		}
		trigger_error('Class [' . get_class($this) . "] : Unable to revoke a session.", E_USER_WARNING);
		return false; // TODO: ERROR: unable to revoke a session		
	}
	
	private function updateSession($token) 
	{
		$stmt = null;
		$temp_tz = null;
		$stmtexecutestat = null;
		
		$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
		$stmt->set_custom_stmt('UPDATE spt_sessions SET spc_expiry = ? WHERE ((spc_token = ?) AND (TIMESTAMPDIFF(SECOND, ?, spc_expiry) >= 0))', 'sss', false);
		if ($stmt->prepare()) {
			$temp_tz = date_default_timezone_get();
			date_default_timezone_set('GMT');
			$stmtexecutestat = $stmt->execute(array(gmdate('Y-m-d H:i:s', time() + (60 * self::SESSIONTIMEOUTVALUE)), $token, gmdate('Y-m-d H:i:s', time())));
			date_default_timezone_set($temp_tz);
			if ($stmtexecutestat) {
				return true;
			}
		}
		trigger_error('Class [' . get_class($this) . "] : Unable to update a session.", E_USER_WARNING);
		return false; // TODO: ERROR: unable to update session
	}

	public function initSession($reload = false) 
	{
		$token = null;
		$stmt = null;
		$temp_tz = null;
		$stmtexecutestat = null;
		$sesspk = null;

		$sesspk = $this->_db->genUniquePrimaryKey('spt_sessions', 'spc_id');
		if (isset($sesspk)) {
			$token = $this->generateToken(array($sesspk));
			$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
			$stmt->set_custom_stmt('INSERT INTO spt_sessions (spc_id, spc_token, spc_expiry) VALUES (?, ?, ?)', 'sss', false);
			if ($stmt->prepare()) {
				$temp_tz = date_default_timezone_get();
				date_default_timezone_set('GMT');
				$stmtexecutestat = $stmt->execute(array($sesspk, $token, gmdate('Y-m-d H:i:s', time() + (60 * self::SESSIONTIMEOUTVALUE))));
				date_default_timezone_set($temp_tz);
				if ($stmtexecutestat) {
					$this->_sessionid = $token;
					setcookie(self::SESSIONCOOKIENAME, '', 1, '/');
					setcookie(self::SESSIONCOOKIENAME, $token, 0, '/'); // Delete cookie at end of browser session
					if ($reload) {
						header('Location: ' . str_replace(_SP_DOCUMENT_ROOT, '', $_SERVER["SCRIPT_FILENAME"]));
					}
					return $sesspk;
				}
			}
		}
		trigger_error('Class [' . get_class($this) . "] : Unable to initiate a session.", E_USER_WARNING);
		return null; // TODO: ERROR: unable to initiate a session			
	}
	
	private function verifySession($token) 
	{
		$stmt = null;
		$results = null;
		$stmtexecutestat = null;
		$temp_tz = null;
		$seeds = null;

		$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
		$stmt->set_custom_stmt('SELECT spc_id FROM spt_sessions WHERE ((spc_token = ?) AND (TIMESTAMPDIFF(SECOND, ?, spc_expiry) >= 0))', 'ss', true);
		if ($stmt->prepare()) {
			$temp_tz = date_default_timezone_get();
			date_default_timezone_set('GMT');
			$stmtexecutestat = $stmt->execute(array($token, gmdate('Y-m-d H:i:s', time())));
			date_default_timezone_set($temp_tz);
			if ($stmtexecutestat) {
        		if ((($results = $stmt->get_stmt_results()) != false) && isset($results) && isset($results[0]['spc_id'])) {
        			unset($stmt);
        			$seeds[] = $results[0]['spc_id'];      					
        			// Add other bits of information to $seeds[] in same order as when you generated them.
					// Now hash seeds with sha256 and compare it to $token to see if it is the same.
					// If they are the same we probably have a legitimate host.
					if (strcmp($token, $this->generateToken($seeds)) == 0) {
						return true;
					}
					else {
						return false;
					}
        		}
        		else {
        			return false;
        		}
			}
		}
		trigger_error('Class [' . get_class($this) . "] : Unable to verify a session.", E_USER_WARNING);
		return false; // TODO: ERROR: unable to verify a session
	}	
	
	public function getSessionValue($key) 
	{
		$stmt = null;
		$temp_tz = null;
		$stmtexecutestat = null;

		$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
		//$stmt->set_custom_stmt('SELECT B.spc_value FROM (spt_sessions AS A INNER JOIN spt_sessions_data AS B ON (A.spc_id = B.spc_id)) WHERE ((A.spc_token = ?) AND (TIMESTAMPDIFF(SECOND, ?, A.spc_expiry) >= 0) AND (B.spc_key = ?))', 'sss', true);
		$stmt->set_custom_stmt('SELECT spc_value FROM spt_sessions_data WHERE ((spc_id = (SELECT spc_id from spt_sessions WHERE ((spc_token = ?) AND (TIMESTAMPDIFF(SECOND, ?, spc_expiry) >= 0)))) AND (spc_key = ?))', 'sss', true);
		if ($stmt->prepare()) {
			$temp_tz = date_default_timezone_get();
			date_default_timezone_set('GMT');
			$stmtexecutestat = $stmt->execute(array($this->getToken(), gmdate('Y-m-d H:i:s', time()), $key));
			date_default_timezone_set($temp_tz);
			if ($stmtexecutestat) {
				if ((($results = $stmt->get_stmt_results()) != false) && isset($results)) {
					return $results[0]['spc_value'];
				}
				else {
					return null;
				}
			}
		}
		trigger_error('Class [' . get_class($this) . "] : Unable to get session value.", E_USER_WARNING);
		return null; // TODO: ERROR: unable to get session value
	}

	public function setSessionValue($key, $value = null, $spc_id = null) 
	{
		$stmt = null;
		$stmtexecutestat = null;
		$temp_tz = null;
		
		$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
		if (isset($value)) {
			if (isset($spc_id)) {
				$stmt->set_custom_stmt('INSERT INTO spt_sessions_data (spc_id, spc_key, spc_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE spc_value = VALUES(spc_value)', 'sss', false);
			}
			else {
				$stmt->set_custom_stmt('INSERT INTO spt_sessions_data (spc_id, spc_key, spc_value) (SELECT spc_id, ?, ? FROM spt_sessions WHERE ((spc_token = ?) AND (TIMESTAMPDIFF(SECOND, ?, spc_expiry) >= 0))) ON DUPLICATE KEY UPDATE spc_value = VALUES(spc_value)', 'ssss', false);
			}
			if ($stmt->prepare()) {
				$temp_tz = date_default_timezone_get();
				date_default_timezone_set('GMT');
				if (isset($spc_id)) {
					$stmtexecutestat = $stmt->execute(array($spc_id, $key, $value));
				}
				else {
					$stmtexecutestat = $stmt->execute(array($key, $value, $this->getToken(), gmdate('Y-m-d H:i:s', time())));
				}
				date_default_timezone_set($temp_tz);
				if ($stmtexecutestat) {
					return true;
				}
			}
		}
		else {
			$stmt->set_custom_stmt('DELETE FROM spt_sessions_data WHERE ((spc_key = ?) AND (spc_id = (SELECT spc_id FROM spt_sessions WHERE ((spc_token = ?) AND (TIMESTAMPDIFF(SECOND, ?, spc_expiry) >= 0)))))', 'sss', false);
			if ($stmt->prepare()) {
				$temp_tz = date_default_timezone_get();
				date_default_timezone_set('GMT');
				$stmtexecutestat = $stmt->execute(array($key, $this->getToken(), gmdate('Y-m-d H:i:s', time())));
				date_default_timezone_set($temp_tz);
				if ($stmtexecutestat) {
					return true;
				}
			}
		}		
		trigger_error('Class [' . get_class($this) . "] : Unable to set session value.", E_USER_WARNING);
		return false; // TODO: ERROR: unable to set session value
	}

	public function getToken($fromcookie = true) 
	{
		if ($fromcookie) {
			if (isset($_COOKIE[self::SESSIONCOOKIENAME])) {
				return $_COOKIE[self::SESSIONCOOKIENAME];
			}
		}
		else {
			if (isset($this->_sessionid)) {
				return $this->_sessionid;
			}
		}
		return null;
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
		}
		catch (Exception $e) {
			// DO NOTHING
			// TODO: ERROR: Log error to file.
		}
	}
}
?>
