<?php 
defined( '_SPEXEC' ) or die( 'Restricted access' ); 
define( '_SPLOADER', 1 ); 
if (!defined('_SP_APPLICATION_BASE')) {
	require $_SERVER['DOCUMENT_ROOT'] . '/base.php';
}
?>
<?php
class spLoader
{
	const DS = DIRECTORY_SEPARATOR;
	const APP_ROOT = '/';
	const DS_F = ':';
	
	private static $classes;
	
	public static function import($filePath, $key = 'classes', $forceimport = false)
	{
		
		$keyPath = isset($key) ? $key . self::DS_F . $filePath : $filePath;
		$classname = substr(strrchr(self::DS_F . $filePath, self::DS_F), 1);				
		if (!isset(self::$classes[$classname])) {			
			self::$classes = self::register($classname, $keyPath . '.php', $forceimport);
		}
	}
	
	public static function &register($class = null, $file = null, $forceload = false)
	{
		$approot = (strcmp('/', self::APP_ROOT) == 0) ? '' : self::APP_ROOT;
		
		if (isset($file)) {
			$file = _SP_DOCUMENT_ROOT . $approot . '/' . str_replace(self::DS_F, self::DS, $file);
		}
		
		if(!isset(self::$classes)) {
			self::$classes = array();
		}

		if(isset($class) && is_file($file)) {
			self::$classes[$class] = $file;

			if ($forceload) {
				spLoader::load($class);
			}
		}

		return self::$classes;
	}

	public static function load($class)
	{
		if (class_exists($class)) {
			  return;
		}

		self::$classes = self::register();
		if(array_key_exists($class, self::$classes)) {
			include(self::$classes[$class]);
			return true;
		}
		return false;
	}
	
	public static function translatepath($path, $pathseperator = self::DS_F) {
		return str_replace($pathseperator, self::DS, $path);
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
<?php 
function __autoload($class)
{
	if (spLoader::load($class)) {
		return true;
	}
	return false;
}
?>
