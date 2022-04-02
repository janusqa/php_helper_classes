<?php 
define( '_SPEXEC', 1 ); 
if (!class_exists('spLoader')) {
	defined('_SPLOADER') or require($_SERVER['DOCUMENT_ROOT'] . '/' .'classes/utilities/spLoader.php');
}
ob_start('ob_gzhandler');
spLoader::import('config:spConfig', null);
spLoader::import('errorhandler:spErrorHandler');
spLoader::import('sessions:spSessionManager');
spLoader::import('database:spDBO');
//spLoader::register('spDBO', 'classes:database:spDBO.php');
// -- Inititilization Start -- //
date_default_timezone_set(spConfig::get_user_tz()); 
$spe = new spErrorHandler(); 
// -- Inititilization End   -- //
?>
<?php 
$spe->debug_print("<pre>");
$spe->debug_print("begin...\n\n");
$spe->debug_print((20 & ~2) | 8);
//$spdb = new spDBO();
//$spsess = new spSessionManager($spdb);
//$spe->debug_print('Session: ' . $spsess->getSessionID(false));
//$spsess->setSessionValue('key1', 111);
//$spsess->setSessionValue('key2', 222);
//$spsess->setSessionValue('key5', 333);
//$spe->debug_print($spsess->getSessionValue('key1'));
//$spe->debug_print($spsess->getSessionValue('key2'));
//$spe->debug_print($spsess->getSessionValue('key3'));
//$spe->debug_print($spsess->getSessionValue('key5'));
//$spsess->revokeSession();
//$temp_tz = date_default_timezone_get();
//date_default_timezone_set('GMT');
//$spe->debug_print(gmdate('Y-m-d H:i:s', time()));
//$spe->debug_print(gmdate('Y-m-d H:i:s', time() + (60 * 20)));
//date_default_timezone_set($temp_tz);
//$spe->debug_print($spdb->info($spdb->getconnection()));
//try {
//	throw new Exception("This is just a test", E_USER_WARNING);
//}
//catch (Exception $e) {
//	$spe->log_error_database($e);
//}
$spe->debug_print('...still going...');
$spe->debug_print("\n\n...end!\n");
$spe->debug_print('</pre>');
//unset($spsess);
//unset($spdb);
unset($spe);
?>
<?php
ob_end_flush();
?>