<?php 
defined( '_SPEXEC' ) or die( 'Restricted access' );
define( '_SPAUTHENTICATION', 1 ); 
?>
<?php
class spAuthentication
{	
	Const AUTHCOOKIENAME = 'alau';
	
	public static $_oauth_providers = null;
	
	protected $_db = null;
	protected $_spsess = null;
	
	public function __construct($db, $spsess, $sp_oauth_callback = false)
	{
		$this->_db =& $db;
		$this->_spsess =& $spsess;
		
		self::$_oauth_providers = array(
			'Facebook' => 'facebook',
			'Twitter' => 'twitter',
		);
	}
	
	protected function generateToken($seeds) 
	{
		$token = null;
		
		$token = hash('sha256', implode('', $seeds));
		return $token;
	}
	
	public function checkAuthtentication() 
	{	
		if (isset($_COOKIE[self::AUTHCOOKIENAME])) {
			if (strcmp($_COOKIE[self::AUTHCOOKIENAME], $this->_spsess->getSessionValue('sp_authtoken')) == 0) {			
				return true;
			}
			else {
				$this->_spsess->revokeSession($this->_spsess->getSessionID());
			}
		}	
		return false;
	}
	
	protected function verifyAccount($spc_oauthid = null, $spc_oauthprovider = 'native') 
	{
		$stmt = null;
		$stmtexecutestat = null;

		$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
		$stmt->set_custom_stmt('SELECT A.spc_id AS aspcid FROM (spt_users AS A INNER JOIN spt_users_linked_accounts AS B ON (A.spc_id = B.spc_id)) WHERE ((B.spc_oauthprovider = ?) AND (B.spc_oauthid = ?))', 'ss', true);
		if ($stmt->prepare()) {
			$stmtexecutestat = $stmt->execute(array($spc_oauthprovider, $spc_oauthid));
			if ($stmtexecutestat) {
				if ((($results = $stmt->get_stmt_results()) != false) && isset($results)) {
					return $results[0]['aspcid'];
				}
				else {
					return null;
				}
			}
		}
		trigger_error('Class [' . get_class($this) . "] : Unable to verify user account.", E_USER_WARNING);
		return null; // TODO: ERROR: unable to verify user account
	}
	
	public function verifyRegistration($spc_email, $spc_oauthid = null, $oauth_provider = 'native') 
	{
		$stmt = null;
		$stmtexecutestat = null;

		$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
		$stmt->set_custom_stmt('SELECT A.spc_id AS aspcid FROM spt_users AS A INNER JOIN spt_users_linked_accounts AS B on (A.spc_id = B.spc_id) WHERE ((A.spc_email = ?) AND (B.spc_oauthprovider = ?) AND (B.spc_oauthid = ?))', 'sss', true);
		if ($stmt->prepare()) {
			$stmtexecutestat = $stmt->execute(array($spc_email, $oauth_provider, $spc_oauthid));
			if ($stmtexecutestat) {
				if ((($results = $stmt->get_stmt_results()) != false) && isset($results)) {
					return $results[0]['aspcid'];
				}
				else {
					return null;
				}
			}
		}
		trigger_error('Class [' . get_class($this) . "] : Unable to get user preference.", E_USER_WARNING);
		return null; // TODO: ERROR: unable to get session value
	}
	
	public function verifyPreActivation($spc_email, $spc_oauthid = null, $oauth_provider = 'native') 
	{
		$stmt = null;
		$temp_tz = null;
		$stmtexecutestat = null;

		$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
		$stmt->set_custom_stmt('SELECT spc_activation_token FROM spt_users_preactivations WHERE ((spc_native_email = ?) AND (spc_oauth_provider = ?) AND (spc_oauth_id = ?) AND (TIMESTAMPDIFF(SECOND, ?, spc_expiry) >= 0))', 'ssss', true);
		if ($stmt->prepare()) {
			$temp_tz = date_default_timezone_get();
			date_default_timezone_set('GMT');
			$stmtexecutestat = $stmt->execute(array($spc_email, $oauth_provider, $spc_oauthid, gmdate('Y-m-d H:i:s', time())));
			date_default_timezone_set($temp_tz);
			if ($stmtexecutestat) {
				if ((($results = $stmt->get_stmt_results()) != false) && isset($results)) {
					return $results[0]['spc_activation_token'];
				}
				else {
					return null;
				}
			}
		}
		trigger_error('Class [' . get_class($this) . "] : Unable to get user preference.", E_USER_WARNING);
		return null; // TODO: ERROR: unable to get session value
	}
	
	public function activateAccount($spc_activation_token) 
	{
		$spc_id = null;
		$spc_native_email = null;
		$spc_native_timezone = null;
		$spc_oauth_id = null;
		$spc_oauth_provider = null;
		$spc_oauth_email = null;
		$spc_oauth_firstname = null;
		$spc_oauth_lastname = null;
		$spc_oauth_gender = null;
		$spc_oauth_birthdate = null;
		$spc_oauth_relationship_status = null;
		$spc_oauth_timezone = null;
		$transaction_state = false;
		$stmt = null;
		$temp_tz = null;
		$user_id = null;
		$spuser = null;
		
		if ($this->_db->beginTransaction($this->_db->getconnection())) {
			$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
			// SELECT spt_users_preactivations
			$stmt->set_custom_stmt('SELECT * FROM spt_users_preactivations WHERE ((spc_activation_token = ?) AND (TIMESTAMPDIFF(SECOND, ?, spc_expiry) >= 0))', 'ss', true);
			if ($stmt->prepare()) {
				$temp_tz = date_default_timezone_get();
				date_default_timezone_set('GMT');
				$stmtexecutestat = $stmt->execute(array($spc_activation_token, gmdate('Y-m-d H:i:s', time())));
				date_default_timezone_set($temp_tz);
				if ($stmtexecutestat) {
					if ((($results = $stmt->get_stmt_results()) != false) && isset($results)) {
						$spc_native_email = isset($results[0]['spc_native_email']) ? $results[0]['spc_native_email'] : 'N/A';
						$spc_native_timezone = isset($results[0]['spc_native_timezone']) ? $results[0]['spc_native_timezone'] : 'N/A';
						$spc_oauth_id = isset($results[0]['spc_oauth_id']) ? $results[0]['spc_oauth_id'] : 'N/A';
						$spc_oauth_provider = isset($results[0]['spc_oauth_provider']) ? $results[0]['spc_oauth_provider'] : 'N/A';
						$spc_oauth_email = isset($results[0]['spc_oauth_email']) ? $results[0]['spc_oauth_email'] : 'N/A';
						$spc_oauth_firstname = isset($results[0]['spc_oauth_firstname']) ? $results[0]['spc_oauth_firstname'] : 'N/A';
						$spc_oauth_lastname = isset($results[0]['spc_oauth_lastname']) ? $results[0]['spc_oauth_lastname'] : 'N/A';
						$spc_oauth_gender = isset($results[0]['spc_oauth_gender']) ? $results[0]['spc_oauth_gender'] : 'N/A';
						$spc_oauth_birthdate = isset($results[0]['spc_oauth_birthdate']) ? $results[0]['spc_oauth_birthdate'] : 'N/A';
						$spc_oauth_relationship_status = isset($results[0]['spc_oauth_relationship_status']) ? $results[0]['spc_oauth_relationship_status'] : 'N/A';		
						$spc_oauth_timezone =isset($results[0]['spc_oauth_timezone']) ? $results[0]['spc_oauth_timezone'] : 'N/A';
						// Check if user already exist (based on registration email) in spt_users table
						$spuser = new spUser(null, $this->_db, $this->_spsess);
						$user_id = $spuser->getUserID($spc_native_email);
						unset($spuser);
						if (isset($user_id)) {
							$spc_id = $user_id;
						}
						else {
							$spc_id = $this->_db->genUniquePrimaryKey('spt_users', 'spc_id');
						}
						// INSERT spt_users
						$stmt->set_custom_stmt('INSERT INTO spt_users (spc_id, spc_email, spc_status, spc_joindate) VALUES (?, ?, ?, ?)', 'ssss', false);
						if ($stmt->prepare()) {
							if (isset($user_id)) {
								$stmtexecutestat = true;
							}
							else {
								$temp_tz = date_default_timezone_get();
								date_default_timezone_set('GMT');
								$stmtexecutestat = $stmt->execute(array($spc_id, $spc_native_email, 0, gmdate('Y-m-d H:i:s', time())));
								date_default_timezone_set($temp_tz);
							}
							if ($stmtexecutestat) {
								// INSERT spt_users_linked_accounts
								$stmt->set_custom_stmt('INSERT INTO spt_users_linked_accounts (spc_oauthid, spc_oauthprovider, spc_id, spc_email, spc_firstname, spc_lastname, spc_gender, spc_birthdate, spc_relationship_status, spc_timezone, spc_joindate, spc_updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', 'ssssssssssss', false);
								if ($stmt->prepare()) {
									$temp_tz = date_default_timezone_get();
									date_default_timezone_set('GMT');
									$stmtexecutestat = $stmt->execute(array($spc_oauth_id, $spc_oauth_provider, $spc_id, $spc_oauth_email, $spc_oauth_firstname, $spc_oauth_lastname, $spc_oauth_gender, $spc_oauth_birthdate, $spc_oauth_relationship_status, $spc_oauth_timezone, gmdate('Y-m-d H:i:s', time()), gmdate('Y-m-d H:i:s', time())));
									date_default_timezone_set($temp_tz);
									if ($stmtexecutestat) {
										// INSERT spt_users_preferences
										$stmt->set_custom_stmt('INSERT INTO spt_users_preferences (spc_id, spc_key, spc_value) VALUES (?, ?, ?)', 'sss', false);
										if ($stmt->prepare()) {
											if (isset($user_id)) {
												$stmtexecutestat = true;
											}
											else {
												$stmtexecutestat = $stmt->execute(array($spc_id, 'tz', $spc_native_timezone));
											}	
											if ($stmtexecutestat) {
												// DELETE spt_users_preactivations
												$stmt->set_custom_stmt('DELETE FROM  spt_users_preactivations WHERE (spc_activation_token = ?)', 's', false);
												if ($stmt->prepare()) {
													$stmtexecutestat = $stmt->execute(array($spc_activation_token));
													if ($stmtexecutestat) {
														$transaction_state = true;
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}	
		$this->_db->endTransaction($this->_db->getconnection(), $transaction_state);
		return $transaction_state;
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