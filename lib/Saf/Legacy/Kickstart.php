<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Legacy Kickstart Wrapper, delegates to new objects

*******************************************************************************/

use Saf\Legacy\Adapter;
use Saf\Environment\Define;

class Saf_Kickstart extends Adapter
{
    
    public function __call($name, $args){
        parent::error(__CLASS__, isset($this), $name);
    }

    public static function __callStatic($name, $args){
        parent::error(__CLASS__, isset($this), $name);
    }

	public static function getConfigResource($resource, $compatMode = FALSE)
	{
        $config = Saf_Registry::get('config');
        if (!$config) {
            throw new Exception('application config missing');
        }
        $resources = 
            $compatMode 
            ? $config->get('resources', APPLICATION_ENV)
            : $config;
		if ($resources) {
			$resource = $resources->$resource;
			if ($resource || is_array($resource)) {
				return
					is_array($resource)
					? $resource
					: (method_exists($resource,'toArray') ? $resource->toArray() : array());
			}
		}
		return null;
	}

	public static function mapLoad($valueName, $memberCast = Define::CAST_TYPE_STRING)
	{
		return Define::mapLoad($valueName,$memberCast);
	}
    
	public static function getConfigItem($item)
	{
		$item = Saf_Registry::get('config')->get($item, APPLICATION_ENV);
		if ($item) {
			return
				is_array($item)
				? $item
				: (
					is_object($item)
					? $item->toArray()
					: $item
				);
		}
		return NULL;
	}
}
