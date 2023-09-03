<?php
defined('_SPEXEC') or die('Restricted access');
define('_SPTWITTERAUTHENTICATION', 1);
spLoader::import('authentication:spAuthentication');
spLoader::register('TwitterOAuth', 'classes:twitter-phpsdk:twitteroauth.php');
?>
<?php
class spTwitterAuthentication extends spAuthentication
{
	private $_twoauthkeys = array(
		'appId'  => 'YOUR_APP_ID',
		'secret' => 'YOUR_SECRET',
	);

	private $_twitterobj = null;
	private $_requestToken = null;
	private $_accessToken = null;

	public function __construct($db, $spsess, $sp_oauth_callback = false)
	{
		parent::__construct($db, $spsess);
		$this->_twitterobj = new TwitterOAuth($this->_twoauthkeys['appId'], $this->_twoauthkeys['secret']);
	}

	public function getUser()
	{
		if (isset($this->_twitterobj) && ($this->_twitterobj instanceof TwitterOAuth)) {
			return $this->_twitterobj->getUser();
		}
		return null;
	}

	public function getTwitter()
	{
		if (isset($this->_twitterobj) && ($this->_twitterobj instanceof TwitterOAuth)) {
			return $this->_twitterobj;
		}
		return null;
	}

	public function verifyTwitterSession()
	{
		$sessionActive = null;

		if ((isset($this->_twitterobj)) && ($this->_twitterobj instanceof TwitterOAuth)) {
			try {
				$sessionActive = $this->_twitterobj->get('account/verify_credentials');
				if ($this->_twitterobj->http_code == 200) {
					return true;
				}
			} catch (Exception $e) {
				return false;
			}
		}
		return false;
	}

	public function signIn($sp_oauth_callback)
	{
		$sp_authtoken = null;
		$sp_oauth_callback_token = null;
		$spc_id = null;
		$userobj = null;
		$tz = null;
		$sessid = null;

		if (isset($this->_twitterobj) && ($this->_twitterobj instanceof TwitterOAuth)) {
			if (!$sp_oauth_callback) {
				$sp_oauth_callback_token = $this->generateToken(array($this->_db->genUniquePrimaryKey()));
				$this->_spsess->setSessionValue('sp_oauth_callback', $sp_oauth_callback_token);
				$this->_requestToken = $this->_twitterobj->getRequestToken(spConfig::HOSTNAME . '/application/authentication/signin.php?sp_oauth_callback=' . $sp_oauth_callback_token);
				$this->_spsess->setSessionValue('trt_oauth_token', $this->_requestToken['oauth_token']);
				$this->_spsess->setSessionValue('trt_oauth_token_secret', $this->_requestToken['oauth_token_secret']);
				header('Location: ' . $this->_twitterobj->getAuthorizeURL($this->_requestToken));
				return null;
			} else {
				if (!isset($_GET['denied'])) {
					$this->_twitterobj = null;
					$this->_twitterobj = new TwitterOAuth($this->_twoauthkeys['appId'], $this->_twoauthkeys['secret'], $this->_spsess->getSessionValue('trt_oauth_token'), $this->_spsess->getSessionValue('trt_oauth_token_secret'));
					$this->_accessToken = $this->_twitterobj->getAccessToken($_GET['oauth_verifier']);
					$this->_spsess->setSessionValue('tat_oauth_token', $this->_accessToken['oauth_token']);
					$this->_spsess->setSessionValue('tat_oauth_token_secret', $this->_accessToken['oauth_token_secret']);
					$spc_id = $this->verifyAccount($this->_accessToken['user_id'], 'twitter');
					if ($this->verifyTwitterSession() && isset($spc_id)) {
						if ($this->syncAccount($spc_id, $this->_accessToken['user_id'], 'twitter')) {
							$this->_spsess->revokeSession($this->_spsess->getSessionID());
							$sessid = $this->_spsess->initSession();
							$sp_authtoken = $this->generateToken(array($this->_db->genUniquePrimaryKey()));
							$userobj = new spUser($spc_id, $this->_db, $this->_spsess);
							$tz = $userobj->getPreference('tz');
							$tz = isset($tz) ? $tz : 'GMT';
							$this->_spsess->setSessionValue('oauth_provider', 'twitter', $sessid);
							$this->_spsess->setSessionValue('sp_authtoken', $sp_authtoken, $sessid);
							$this->_spsess->setSessionValue('spc_id', $spc_id, $sessid);
							$this->_spsess->setSessionValue('tz', $tz, $sessid);
							setcookie(self::AUTHCOOKIENAME, '', 1, '/');
							setcookie(self::AUTHCOOKIENAME, $sp_authtoken, 0, '/');
						}
					}
				}
			}
		}
		header('Location: ' . spConfig::getAppRoot() . '/index.php');
		return null;
	}

	public function signOut($sp_oauth_callback)
	{
		setcookie(self::AUTHCOOKIENAME, '', 1, '/');
		$php_session_name = session_name();
		session_start();
		$_SESSION = array();
		session_destroy();
		setcookie($php_session_name, '', 1, '/');
		$this->_spsess->revokeSession($this->_spsess->getSessionID());
		$this->_spsess->initSession();

		header('Location: ' . 'http://twitter.com/logout');
		return null;
	}

	public function signUp($sp_oauth_callback)
	{
		$sp_oauth_callback_token = null;
		$spc_id = null;
		$spc_email = null;
		$spc_atcivationtoken = null;

		if (isset($this->_twitterobj) && ($this->_twitterobj instanceof TwitterOAuth)) {
			if (!$sp_oauth_callback) {
				$sp_oauth_callback_token = $this->generateToken(array($this->_db->genUniquePrimaryKey()));
				$this->_spsess->setSessionValue('sp_oauth_callback', $sp_oauth_callback_token);
				$this->_requestToken = $this->_twitterobj->getRequestToken(spConfig::HOSTNAME . '/application/authentication/signup.php?sp_oauth_callback=' . $sp_oauth_callback_token);
				$this->_spsess->setSessionValue('trt_oauth_token', $this->_requestToken['oauth_token']);
				$this->_spsess->setSessionValue('trt_oauth_token_secret', $this->_requestToken['oauth_token_secret']);
				header('Location: ' . $this->_twitterobj->getAuthorizeURL($this->_requestToken));
				return null;
			} else {
				if (!isset($_GET['denied'])) {
					$this->_twitterobj = null;
					$this->_twitterobj = new TwitterOAuth($this->_twoauthkeys['appId'], $this->_twoauthkeys['secret'], $this->_spsess->getSessionValue('trt_oauth_token'), $this->_spsess->getSessionValue('trt_oauth_token_secret'));
					$this->_accessToken = $this->_twitterobj->getAccessToken($_GET['oauth_verifier']);
					$this->_spsess->setSessionValue('tat_oauth_token', $this->_accessToken['oauth_token']);
					$this->_spsess->setSessionValue('tat_oauth_token_secret', $this->_accessToken['oauth_token_secret']);
					$this->_spsess->setSessionValue('registration_spc_id', $this->_accessToken['user_id']);
					$spc_id = $this->verifyRegistration($this->_spsess->getSessionValue('registration_email'), $this->_accessToken['user_id'], 'twitter');
					if ($this->verifyTwitterSession() && isset($spc_id) && !$this->checkAuthtentication()) {
						$this->signIn($sp_oauth_callback);
						return null;
					} else {
						$spc_activationtoken = $this->verifyPreActivation($this->_spsess->getSessionValue('registration_email'), $this->_accessToken['user_id'], 'twitter');
						if (isset($spc_activationtoken)) {
							$this->_spsess->setSessionValue('$spc_activationtoken', $spc_activationtoken);
							$this->_spsess->setSessionValue('send_activation_email', 0);
							header('Location: ' . spConfig::getAppRoot() . '/application/authentication/send_activation_email.php');
							return null;
						} else {
							if ($this->preRegisterAccount($this->_accessToken['user_id'], 'twitter')) {
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
		$spc_oauth_name = null;

		$twitter_user = $this->_twitterobj->get('account/verify_credentials');
		if ($this->_twitterobj->http_code == 200) {
			$spc_oauth_email = 'N/A';
			$spc_oauth_name = explode(' ', $twitter_user->name);
			$spc_oauth_firstname = isset($spc_oauth_name[0]) ? $spc_oauth_name[0] : 'N/A';
			$spc_oauth_lastname = isset($spc_oauth_name[1]) ? $spc_oauth_name[1] : 'N/A';
			$spc_oauth_gender = 'N/A';
			$spc_oauth_birthdate = 'N/A';
			$spc_oauth_relationship_status = 'N/A';
			$spc_oauth_timezone = (isset($twitter_user->utc_offset) && is_numeric($twitter_user->utc_offset)) ? $twitter_user->utc_offset / (60 * 60) : 'N/A';
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
		$spc_name = null;

		$twitter_user = $this->_twitterobj->get('account/verify_credentials');
		if ($this->_twitterobj->http_code == 200) {
			$spc_email = 'N/A';
			$spc_name = explode(' ', $twitter_user->name);
			$spc_firstname = isset($spc_name[0]) ? $spc_name[0] : 'N/A';
			$spc_lastname = isset($spc_name[1]) ? $spc_name[1] : 'N/A';
			$spc_gender = 'N/A';
			$spc_birthdate = 'N/A';
			$spc_relationship_status = 'N/A';
			$spc_timezone = (isset($twitter_user->utc_offset) && is_numeric($twitter_user->utc_offset)) ? $twitter_user->utc_offset / (60 * 60) : 'N/A';
		} else {
			return false;
		}

		$stmt = new spPreparedStatement($this->_db, 'PS_CUSTOM');
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
			if (isset($this->_twitterobj) && ($this->_twitterobj instanceof TwitterOAuth)) {
				unset($this->_twitterobj);
			}
			parent::__destruct();
		} catch (Exception $e) {
			// TODO: ERROR: Log error to file
			// DO NOTHING.
		}
	}
}
?>