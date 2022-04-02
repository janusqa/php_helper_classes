<?php 
defined( '_SPEXEC' ) or die( 'Restricted access' ); 
define( '_SPCONFIG', 1 );
?>
<?php
class spConfig 
{
    const DB_SERVER = 'localhost';
    const DB_USER = 'xxx';
    const DB_NAME = 'xxx';
    const DB_PASSWORD = 'xxx';
	const APP_ROOT = spLoader::APP_ROOT;
	const DS = spLoader::DS;
	const HOSTNAME = 'xxx';
    const APPNAME = 'xxx';
	const SITENAME = 'xxx';
    const METADESC = 'xxx';
    const METAKEYS = 'xxx';
    const OFFLINE_MESSAGE = 'This site is down for maintenance. Please check back again soon.';

	public static function get_server_tz() {
		return 'America/New_York';
	}
	
	public static function getAppRoot() {
		return (strcmp('/', self::APP_ROOT) == 0) ? '' : self::APP_ROOT;
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
}
?>


