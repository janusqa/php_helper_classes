<?php 
defined( '_SPEXEC' ) or die( 'Restricted access' );
define( '_SPRBAC', 1 ); 
?>
<?php
class spRBAC
{	
	private $groups = array(
   		"root"          => 1,
   		"web"   		=> 2,
	);
	
	private $permissions = array(
   		"o+r"   		=> 1,
   		"o+w" 			=> 2,
   		"o+d" 			=> 4,
   		"g+r"   		=> 8,
   		"g+w"  			=> 16,
   		"g+d" 			=> 32,
   		"w+r"   		=> 64,
   		"w+w"  			=> 128,
   		"w+d" 			=> 256,
	);

	private $statuses = array(
   		"deleted"   	=> 1,
   		"inactive"		=> 2,
   		"active"     	=> 4,
   		"cancelled"  	=> 8,
   		"pending"    	=> 16,
	);
	
	public function __construct()
	{
		// TODO:
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
    	}
    	catch (Exception $e) { 
    		// TODO: ERROR: Log error to file
    		// DO NOTHING.
    	}
    }	
}
?>