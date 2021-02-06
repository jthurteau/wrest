<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for route/pipe resolution
 */

namespace Saf;

use Saf\Environment\Define;

require_once(dirname(__FILE__) . '/Environment/Define.php');

class Resolver
{

    public static function init($instance,$mode)
    {
        		#NOTE this is for application frameworks that need help anchoring their PSR-7 around Multiviews
		defined('ROUTER_NAME') || define('ROUTER_NAME', 'index');
		$routerIndexInPhpSelf = strpos($_SERVER['PHP_SELF'], strtolower(\ROUTER_NAME) . '.php');
		$routerPathLength = (
			$routerIndexInPhpSelf !== FALSE
			? $routerIndexInPhpSelf
			: PHP_MAXPATHLEN
		);
		defined('ROUTER_PATH') || define('ROUTER_PATH', NULL);
		$defaultRouterlessUrl = substr($_SERVER['PHP_SELF'], 0, $routerPathLength);
		Define::load('APPLICATION_BASE_URL',
			\ROUTER_NAME != ''
			? $defaultRouterlessUrl
			: './'
		);
		Define::load('APPLICATION_HOST', (
			array_key_exists('HTTP_HOST', $_SERVER) && $_SERVER['HTTP_HOST']
			? $_SERVER['HTTP_HOST']
			: 'commandline'
		));
		Define::load('STANDARD_PORT', '80');
		Define::load('SSL_PORT', '443');
		Define::load('APPLICATION_SSL', //#TODO #2.0.0 this detection needs work
			array_key_exists('HTTPS', $_SERVER)
				&& $_SERVER['HTTPS']
				&& $_SERVER['HTTPS'] != 'off'
			, Cast::TYPE_BOOL
		);
		Define::load('APPLICATION_PORT', (
				array_key_exists('SERVER_PORT', $_SERVER) && $_SERVER['SERVER_PORT']
				? $_SERVER['SERVER_PORT']
				: 'null'
		));
		Define::load('APPLICATION_SUGGESTED_PORT',
			(\APPLICATION_SSL && \APPLICATION_PORT != \SSL_PORT)
				|| (!\APPLICATION_SSL && \APPLICATION_PORT == \STANDARD_PORT)
			? ''
			: \APPLICATION_PORT
		);
		if(array_key_exists('SERVER_PROTOCOL', $_SERVER)) {
			$lowerServerProtocol = strtolower($_SERVER['SERVER_PROTOCOL']);
			$cleanProtocol = substr($lowerServerProtocol, 0, strpos($lowerServerProtocol, '/'));
			if ($cleanProtocol == 'https') { //#TODO #2.0.0 figure out what other possible base protocols there might be to filter...
				$baseProtocol = 'http';
			} else {
				$baseProtocol = $cleanProtocol;
			}
			define('APPLICATION_PROTOCOL', $baseProtocol);
		} else {
			define('APPLICATION_PROTOCOL','commandline');
		}
		Define::load('DEFAULT_RESPONSE_FORMAT', (
			'commandline' == \APPLICATION_PROTOCOL
			? 'text'
			: 'html+javascript:css'
		));
    }
}