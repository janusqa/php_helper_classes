<?php
defined('_SPEXEC') or die('Restricted access');
define('_SPFACEBOOKAUTHENTICATION', 1);
spLoader::import('authentication:spAuthentication');
spLoader::register('Facebook', 'classes:facebook-phpsdk:facebook.php');
?>
<?php
class spFacebookAuthentication extends spAuthentication
{
	private $_fboauthkeys = array(
		'appId'  => 'YOUR_APP_ID',
		'secret' => 'YOUR_SECRET',
	);

	private $_facebookobj = null;

	public function __construct($db, $spsess, $sp_oauth_callback = false)
	{
		parent::__construct($db, $spsess);
		$this->_facebookobj = new Facebook($this->_fboauthkeys);
	}

	public function getUser()
	{
		if (isset($this->_facebookobj) && ($this->_facebookobj instanceof Facebook)) {
			return $this->_facebookobj->getUser();
		}
		return null;
	}

	public function getFacebook()
	{
		if (isset($this->_facebookobj) && ($this->_facebookobj instanceof Facebook)) {
			return $this->_facebookobj;
		}
		return null;
	}

	public function verifyFacebookSession()
	{
		if ((isset($this->_facebookobj)) && ($this->_facebookobj instanceof Facebook)) {
			try {
				$this->_facebookobj->api('/me');
				return true;
			} catch (FacebookApiException $e) {
				trigger_error('Class [' . get_class($this) . '] : ' . $e->getCode() . ' - ' . $e->getMessage() . ' in file ' . $e->getFile() . 'at line ' . $e->getLine() . '.', E_USER_WARNING);
				return false;
			}
		}
	}

	public function signIn($sp_oauth_callback)
	{
		$sp_authtoken = null;
		$sp_oauth_callback_token = null;
		$spc_id = null;
		$userobj = null;
		$tz = null;
		$sessid = null;

		if (isset($this->_facebookobj) && ($this->_facebookobj instanceof Facebook)) {
			if (!$sp_oauth_callback) {
				$sp_oauth_callback_token = $this->generateToken(array($this->_db->genUniquePrimaryKey()));
				$this->_spsess->setSessionValue('sp_oauth_callback', $sp_oauth_callback_token);
				header('Location: ' . $this->_facebookobj->getLoginUrl(array('redirect_uri' => spConfig::HOSTNAME . '/application/authentication/signin.php?sp_oauth_callback=' . $sp_oauth_callback_token, 'scope' => 'user_about_me, user_birthday, email, user_relationship_details, user_location, create_event, rsvp_event, publish_checkins')));
				return null;
			} else {
				$spc_id = $this->verifyAccount($this->_facebookobj->getUser(), 'facebook');
				if ($this->verifyFacebookSession() && isset($spc_id)) {
					if ($this->syncAccount($spc_id, $this->_facebookobj->getUser(), 'facebook')) {
						$this->_spsess->revokeSession($this->_spsess->getSessionID());
						$sessid = $this->_spsess->initSession();
						$sp_authtoken = $this->generateToken(array($this->_db->genUniquePrimaryKey()));
						$userobj = new spUser($spc_id, $this->_db, $this->_spsess);
						$tz = $userobj->getPreference('tz');
						$tz = isset($tz) ? $tz : 'GMT';
						$this->_spsess->setSessionValue('oauth_provider', 'facebook', $sessid);
						$this->_spsess->setSessionValue('sp_authtoken', $sp_authtoken, $sessid);
						$this->_spsess->setSessionValue('spc_id', $spc_id, $sessid);
						$this->_spsess->setSessionValue('tz', $tz, $sessid);
						setcookie(self::AUTHCOOKIENAME, '', 1, '/');
						setcookie(self::AUTHCOOKIENAME, $sp_authtoken, 0, '/');
					}
				}
			}
		}
		header('Location: ' . spConfig::getAppRoot() . '/index.php');
		return null;
	}

	public function signOut($sp_oauth_callback)
	{
		$sp_oauth_callback_token = null;
		$php_session_name = null;

		if (isset($this->_facebookobj) && ($this->_facebookobj instanceof Facebook)) {
			if (!$sp_oauth_callback) {
				$sp_oauth_callback_token = $this->generateToken(array($this->_db->genUniquePrimaryKey()));
				$this->_spsess->setSessionValue('sp_oauth_callback', $sp_oauth_callback_token);
				header('Location: ' . $this->_facebookobj->getLogoutUrl(array('next' => spConfig::HOSTNAME . '/application/authentication/signout.php?sp_oauth_callback=' . $sp_oauth_callback_token)));
				return null;
			} else {
				setcookie(self::AUTHCOOKIENAME, '', 1, '/');
				$php_session_name = session_name();
				session_start();
				$_SESSION = array();
				session_destroy();
				setcookie($php_session_name, '', 1, '/');
				$this->_spsess->revokeSession($this->_spsess->getSessionID());
				$this->_spsess->initSession();
			}
		}
		header('Location: ' . spConfig::getAppRoot() . '/index.php');
		return null;
	}

	public function signUp($sp_oauth_callback)
	{
		$sp_oauth_callback_token = null;
		$spc_id = null;
		$spc_email = null;
		$spc_atcivationtoken = null;

		if (isset($this->_facebookobj) && ($this->_facebookobj instanceof Facebook)) {
			if (!$sp_oauth_callback) {
				$sp_oauth_callback_token = $this->generateToken(array($this->_db->genUniquePrimaryKey()));
				$this->_spsess->setSessionValue('sp_oauth_callback', $sp_oauth_callback_token);
				header('Location: ' . $this->_facebookobj->getLoginUrl(array('redirect_uri' => spConfig::HOSTNAME . '/application/authentication/signup.php?sp_oauth_callback=' . $sp_oauth_callback_token, 'scope' => 'user_about_me, user_birthday, email, user_relationship_details, user_location, create_event, rsvp_event, publish_checkins')));
				return null;
			} else {
				if ($this->verifyFacebookSession()) {
					$spc_id = $this->verifyRegistration($this->_spsess->getSessionValue('registration_email'), $this->_facebookobj->getUser(), 'facebook');
					if ($this->verifyFacebookSession() && isset($spc_id) && !$this->checkAuthtentication()) {
						$this->signIn($sp_oauth_callback);
						return null;
					} else {
						$spc_activationtoken = $this->verifyPreActivation($this->_spsess->getSessionValue('registration_email'), $this->_facebookobj->getUser(), 'facebook');
						if (isset($spc_activationtoken)) {
							$this->_spsess->setSessionValue('$spc_activationtoken', $spc_activationtoken);
							$this->_spsess->setSessionValue('send_activation_email', 0);
							header('Location: ' . spConfig::getAppRoot() . '/application/authentication/send_activation_email.php');
							return null;
						} else {
							if ($this->preRegisterAccount($this->_facebookobj->getUser(), 'facebook')) {
								$this->_spsess->setSessionValue('send_activation_email', 1);
								header('Location: ' . spConfig::getAppRoot() . '/application/authentication/send_activation_email.php');
								return null;
							} else {
								$errormsg = base64_encode('Unable to register your account at this time.  Please try again later.');
								header('Location: ' . spConfig::getAppRoot() . '/application/error/error.php?alem=' . $errormsg);
								return null;
							}
						}
					}
				}
			}
		}
		header('Location: ' . spConfig::getAppRoot() . '/index.php');
		return null;
	}

	private function preRegisterAccount($spc_oauth_id, $spc_oauth_provider)
	{
		$fql = null;
		$param = null;
		$fqlResult = null;
		$stmt = null;
		$stmtexecutestat = null;
		$temp_tz = null;
		$spc_oauth_email = null;
		$spc_oauth_firstname = null;
		$spc_oauth_lastname = null;
		$spc_oauth_gender = null;
		$spc_oauth_birthdate = null;
		$spc_oauth_relationship_status = null;
		$spc_oauth_timezone = null;

		if ($this->verifyFacebookSession()) {
			$fql = "SELECT email, first_name, last_name, sex, birthday_date, relationship_status, timezone FROM user WHERE (uid = {$spc_oauth_id})";
			$param = array('method' => 'fql.query', 'query' => $fql, 'callback'  => '');
			$fqlResult = $this->_facebookobj->api($param);
			$spc_oauth_email = isset($fqlResult[0]['email']) ? $fqlResult[0]['email'] : 'N/A';
			$spc_oauth_firstname = isset($fqlResult[0]['first_name']) ? $fqlResult[0]['first_name'] : 'N/A';
			$spc_oauth_lastname = isset($fqlResult[0]['last_name']) ? $fqlResult[0]['last_name'] : 'N/A';
			$spc_oauth_gender = isset($fqlResult[0]['sex']) ? $fqlResult[0]['sex'] : 'N/A';
			$spc_oauth_birthdate = isset($fqlResult[0]['birthday_date']) ? date_format(date_create($fqlResult[0]['birthday_date']), 'Y-m-d H:i:s') : 'N/A';
			$spc_oauth_relationship_status = isset($fqlResult[0]['relationship_status']) ? $fqlResult[0]['relationship_status'] : 'N/A';
			$spc_oauth_timezone = isset($fqlResult[0]['timezone']) ? $fqlResult[0]['timezone'] : 'N/A';
		} else {
			return false;
		}

		$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
		$stmt->set_custom_stmt('INSERT INTO spt_users_preactivations (spc_native_email, spc_native_timezone, spc_oauth_id, spc_oauth_provider, spc_oauth_email, spc_oauth_firstname, spc_oauth_lastname, spc_oauth_gender, spc_oauth_birthdate, spc_oauth_relationship_status, spc_oauth_timezone, spc_activation_token, spc_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ? ,? ,? ,? ,?, ?) ON DUPLICATE KEY UPDATE spc_native_email = VALUES(spc_native_email), spc_native_timezone = VALUES(spc_native_timezone), spc_oauth_email = VALUES(spc_oauth_email), spc_oauth_firstname = VALUES(spc_oauth_firstname), spc_oauth_lastname = VALUES(spc_oauth_lastname), spc_oauth_gender = VALUES(spc_oauth_gender), spc_oauth_birthdate = VALUES(spc_oauth_birthdate), spc_oauth_relationship_status = VALUES(spc_oauth_relationship_status), spc_oauth_timezone = VALUES(spc_oauth_timezone)', 'sssssssssssss', false);
		if ($stmt->prepare()) {
			$temp_tz = date_default_timezone_get();
			date_default_timezone_set('GMT');
			$stmtexecutestat = $stmt->execute(array($this->_spsess->getSessionValue('registration_email'), $this->_spsess->getSessionValue('registration_tz'), $spc_oauth_id, $spc_oauth_provider, $spc_oauth_email, $spc_oauth_firstname, $spc_oauth_lastname, $spc_oauth_gender, $spc_oauth_birthdate, $spc_oauth_relationship_status, $spc_oauth_timezone, $this->generateToken(array($this->_db->genUniquePrimaryKey())), gmdate('Y-m-d H:i:s', time() + (60 * 60 * 24))));
			date_default_timezone_set($temp_tz);
			if ($stmtexecutestat) {
				return true;
			}
		}
		return false;
	}

	private function syncAccount($spc_id, $spc_oauthid, $spc_oauthprovider)
	{
		$fql = null;
		$param = null;
		$fqlResult = null;
		$stmt = null;
		$stmtexecutestat = null;
		$temp_tz = null;
		$spc_email = null;
		$spc_firstname = null;
		$spc_lastname = null;
		$spc_gender = null;
		$spc_birthdate = null;
		$spc_relationship_status = null;
		$spc_timezone = null;

		if ($this->verifyFacebookSession()) {
			$fql = "SELECT email, first_name, last_name, sex, birthday_date, relationship_status, timezone FROM user WHERE (uid = {$spc_oauthid})";
			$param = array('method' => 'fql.query', 'query' => $fql, 'callback'  => '');
			$fqlResult = $this->_facebookobj->api($param);
			$spc_email = isset($fqlResult[0]['email']) ? $fqlResult[0]['email'] : 'N/A';
			$spc_firstname = isset($fqlResult[0]['first_name']) ? $fqlResult[0]['first_name'] : 'N/A';
			$spc_lastname = isset($fqlResult[0]['last_name']) ? $fqlResult[0]['last_name'] : 'N/A';
			$spc_gender = isset($fqlResult[0]['sex']) ? $fqlResult[0]['sex'] : 'N/A';
			$spc_birthdate = isset($fqlResult[0]['birthday_date']) ? date_format(date_create($fqlResult[0]['birthday_date']), 'Y-m-d H:i:s') : 'N/A';
			$spc_relationship_status = isset($fqlResult[0]['relationship_status']) ? $fqlResult[0]['relationship_status'] : 'N/A';
			$spc_timezone = isset($fqlResult[0]['timezone']) ? $fqlResult[0]['timezone'] : 'N/A';
		} else {
			return false;
		}

		$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
		//$stmt->set_custom_stmt('UPDATE spt_users_linked_accounts SET spc_email = ?, spc_firstname = ?, spc_lastname = ?, spc_gender = ?, spc_birthdate = ?, spc_relationship_status = ?, spc_timezone = ?, spc_updated = ? WHERE ((spc_id = ?) AND (spc_oauthid = ?) AND (spc_oauthprovider = ?))', 'sssssssssss', false);
		$stmt->set_custom_stmt('INSERT INTO spt_users_linked_accounts (spc_oauthid, spc_oauthprovider, spc_id, spc_email, spc_firstname, spc_lastname, spc_gender, spc_birthdate, spc_relationship_status, spc_timezone, spc_joindate, spc_updated) VALUES (?, ?, ?, ?, ?, ?, ?, ? ,? ,? ,? ,?) ON DUPLICATE KEY UPDATE spc_email = VALUES(spc_email), spc_firstname = VALUES(spc_firstname), spc_lastname = VALUES(spc_lastname), spc_gender = VALUES(spc_gender), spc_birthdate = VALUES(spc_birthdate), spc_relationship_status = VALUES(spc_relationship_status), spc_timezone = VALUES(spc_timezone), spc_updated = VALUES(spc_updated)', 'ssssssssssss', false);
		if ($stmt->prepare()) {
			$temp_tz = date_default_timezone_get();
			date_default_timezone_set('GMT');
			$stmtexecutestat = $stmt->execute(array($spc_oauthid, $spc_oauthprovider, $spc_id, $spc_email, $spc_firstname, $spc_lastname, $spc_gender, $spc_birthdate, $spc_relationship_status, $spc_timezone, gmdate('Y-m-d H:i:s', time()), gmdate('Y-m-d H:i:s', time())));
			date_default_timezone_set($temp_tz);
			if ($stmtexecutestat) {
				return true;
			}
		}
		return false;
	}

	public function __destruct()
	{
		try {
			if (isset($this->_facebookobj) && ($this->_facebookobj instanceof Facebook)) {
				unset($this->_facebookobj);
			}
			parent::__destruct();
		} catch (Exception $e) {
			// TODO: ERROR: Log error to file
			// DO NOTHING.
		}
	}
}
?>