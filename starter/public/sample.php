<?php 
/**
 * #SCOPE_NCSU_PUBLIC #LIC_SHORT
 * ???
 */

//#NOTE set this to anchor the path 
//e.g. ://www.x.com/sample/ ://www.x.com/sample/some/resource/ ://www.x.com/not/root/sample/
// define('ROUTER_PATH', 'default/index/sample'); //#NOTE optional set this to fast forward

header('HTTP/1.1 301 Moved Permanently');
header('Location: .');