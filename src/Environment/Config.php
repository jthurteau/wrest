<?php

    #NOTE these functions may not transistion since they are not inline with modern PSR practices 



	
	/**
	 * loads resource configuration data
	 */
	public static function getConfigResource($resource, $compatMode = FALSE)
	{//#TODO #2.0.0 compatMode supports Zend_config objects (i.e. original Room Res implementation), but it's a pretty cryptic solution...
		$resources = $compatMode ? \Saf_Registry::get('config')->get('resources', \APPLICATION_ENV) : \Saf_Registry::get('config');
		//
		if ($resources) {
			$resource = $resources->$resource;
			if ($resource || is_array($resource)) {
				return
					is_array($resource)
					? $resource
					: (method_exists($resource,'toArray') ? $resource->toArray() : array());
			}
		}
		return NULL;
	}

	/**
	 * loads configuration data
	 */
	public static function getConfigItem($item)
	{
		$item = \Saf_Registry::get('config')->get($item, \APPLICATION_ENV);
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