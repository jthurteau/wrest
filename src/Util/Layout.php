<?php 

/**
 * Utility class for layout handling
 */

declare(strict_types=1);

namespace Saf\Util;
//publicPath

use Saf\Util\Location;
use Saf\Hash;
use Saf\Utils\Filter\Truthy;
use Saf\Debug;
use Saf\Utils\Debug\Ui as DebugUi;
use Saf\Client\Http;
use Saf\Utils\Breadcrumb;

class Layout
{

    const DEFAULT_HTML_FORMAT = 'html+javascript:css';
    const BASE_HTML_FORMAT = 'html';
    const DEFAULT_AJAX_FORMAT = 'json';
    const DEFAULT_FILE_FORMAT = 'binary';
    const DEFAULT_RESPONSE_FORMAT = self::BASE_HTML_FORMAT;
    const SUPPORTED_PROTOCOLS = ['https', 'http'];

    protected static $format = self::DEFAULT_RESPONSE_FORMAT;
    protected static $css = [];
    protected static $js = [];
    protected static $min = false;
    protected static $baseUri = null;

    protected static $_formatMap = [
        'text/html' => self::DEFAULT_HTML_FORMAT,
        'application/xhtml+xml' => self::DEFAULT_HTML_FORMAT,
        'application/xml' => self::BASE_HTML_FORMAT,
        'image/webp' => self::DEFAULT_FILE_FORMAT,
        'application/json' => self::DEFAULT_AJAX_FORMAT,
        'text/javascript' => self::DEFAULT_AJAX_FORMAT //#TODO #2.0.0 css?
    ];

    public static function setBaseUri($base)
    {
        self::$baseUri = $base;
    }

    public static function parseUri($uri = null) : string
    {
		if (is_null($uri)) {
			return is_callable(self::$baseUri)
			? (self::$baseUri)('')
			: (self::$baseUri . $uri);
		}
        if (strpos($uri, '/') !== 0) { //#TODO consider protocol relative "//"
            foreach(self::SUPPORTED_PROTOCOLS as $protocol) {
                if (strpos($uri, "$protocol://") === 0) { //#TODO support protocol callbacks
                    return $uri;
                }
            }
			//die(\Saf\Debug::stringR(__FILE__,__LINE__,self::$baseUri,$uri));
            return 
                !is_null(self::$baseUri) 
                ? (
                    is_callable(self::$baseUri)
                    ? (self::$baseUri)($uri)
                    : (self::$baseUri . $uri)
                ) : $uri;
        } else { //#TODO support root uri callbacks
            return $uri;
        }
    }

    /**
     * sets the preferred output format
     * @param string $format
     */
    public static function setFormat($format)
    {
        if (key_exists($format, self::$_formatMap)) {
            $format = self::$_formatMap[$format];
        }
        self::$format = $format;
        return self::$format;
    }

    /**
     * returns the preferred output format
     * @return string
     */
    public static function getFormat()
    {
        return self::$format;
    }

    public static function formatIsHtml()
    {
        return strpos(self::$format, 'html') === 0;
    }

    public static function formatIsJson()
    {
        return strpos(self::$format, 'json') === 0;
    }

    /**
     * autodetect the preferred output format
     * @param unknown_type $hint
     */
    public static function autoFormat($hint = NULL)
    {//#TODO #2.0.0
        $headers = apache_request_headers();
        $isAjax = array_key_exists('X-Requested-With', $headers)
            && 'xmlhttprequest' == strtolower($headers['X-Requested-With']);
        $accept =
            array_key_exists('Accept', $headers)
            ? explode(',', $headers['Accept'])
            : array();
        foreach($accept as $type) {
            $type = explode(';', $type);
            $mimeType = $type[0];
            if ($type != '*/*') {
                return self::setFormat(strtolower(trim($mimeType)));
            }
        }
    }

    /**
     * ensure relative URLs work regardless of where the application is served from
     * @param string $url
     */
    public static function printLink($url)
    {
        print(self::getLink($url));
    }

    /**
     * ensure relative URLs work regardless of where the application is served from
     * @param string $url
     */
    public static function getLink($uri)
    {
        return self::parseUri($uri);
    }

    /**
     * output the tag for an RSS Link
     * @param string $url
     */
    public static function printRssLink($url)
    {

    }

    public static function printCss($css, $media = 'screen', $extention = '.css')
    {
        $cssUri = self::parseUri($css) . ($extention ? $extention : '');
?>
        <link href="<?php print($cssUri); ?>" rel="stylesheet" type="text/css" media="<?php print($media);?>"/>
<?php
    }

    /**
     * store a css file to render a link to during layout
     * @param string $css
     * @param string $media default screen
     */
    public static function autoCss($css, $media = 'screen')
    {
        if ($media === 'screen') {
            self::$css[] = $css;
        } else {
            if (key_exists($media, self::$css)) {
                self::$css[$media][] = $css;
            } else {
                self::$css[$media] = [$css];
            }
        }
    }

    /**
     * output any appropriate CSS files
     */
    public static function printAutoCss()
    {
        foreach(self::$css as $media => $css) {
            $media = is_array($css) ? $media : 'screen';
            $css = is_array($css) ? $css : [$css];
            foreach($css as $currentCss) {
                self::printCss($currentCss, $media);
            }
        }
    }

    public static function printCoreCss()
    {
        $coreCss = array(
            'reset',
            'jquery/ui/jquery-ui',
            'foundation/5/foundation',
            'fontawesome/font-awesome',
            'saf',
            'main'
        );
        $media = 'screen'; //#TODO 2.0.0
        foreach($coreCss as $css) {
			$cssUri = self::parseUri($css);
            self::printCss($cssUri, $media);
        }
    }

    public static function printPreloadJs() //#TODO ???
    {
?>
<script>

</script>
<?php
    }

    public static function printJs($js, $extention='.js')
    {
        $jsUri = self::parseUri($js) . $extention ? $extention : '';
?>
        <script src="<?php print($jsUri); ?>"></script>
<?php
    }

    public static function printCoreJs() //#TODO what?
    {
        $baseUri = self::parseUri();
        $publicPath = '';
        $coreJs = array(
            'jquery/2/jquery-2.1.4',
            'jquery/ui/jquery-ui',
            'foundation/5/foundation.min',
            'saf',
            '_init'
        );
        foreach($coreJs as $js) {
            if (strpos($js, '/.cdn') !== FALSE) {
                if (file_exists("{$publicPath}{$js}")) {
                    self::printJs(file_get_contents("{$publicPath}/javascript/{$js}"));
                } else {
?>
<!-- unavailable cdn <?php print($js);?> -->
<?php
                }
            } else if ('_init' != $js) {
                self::printJs($js);
            } else {
?>
<script type="text/javascript">
$(document).ready(function() {
    saf.init({
        baseUrl: '<?php print($baseUri); ?>'});
});
</script>
<?php
            }
        }
    }

    public static function printPostloadJs() //#TODO ???
    {
?>
    <script>

    </script>
<?php
        }

    public static function autoJs($path){
        self::$js[] = $path;
    }

    /**
     * output any appropriate JS files
     */
    public static function printAutoJs()
    {
        foreach(self::$js as $script) {
			$uri = self::parseUri("javascript/{$script}") . '.js';
			//die(\Saf\Debug::stringR(__FILE__,__LINE__,$uri,self::$baseUri));
?>
<script src="<?php self::printLink("{$uri}")?>"></script>
<?php
        }
    }

    /**
     * output all breadcrumbs
     */
    public static function printBreadCrumbs()
    {
        $crumbs = Location::getCrumbs();
        if(count($crumbs) > 0) {
?>
        <ul class="breadcrumbs">
<?php
            foreach($crumbs as $label=>$info) {
                $uri = is_array($info)
                    ? (
                        key_exists('uri', $info)
                        ? $info['uri']
                        : ( //$#TODO backwards compatablity for PHP5 version
							key_exists('url', $info)
							? $info['url']
							: ''
						)
                    ) : $info;
                $class =
                    is_array($info) && array_key_exists('status', $info)
                    ? " class=\"{$info['status']}\""
                    : '';
                if (is_array($info) && Hash::keyExistsAndIsArray('options', $info)) {
                    $prefix = Hash::keyExistsAndNotBlank('prefix', $info['options'])
                        ? $info['options']['prefix']
                        :'';
                } else {
                    $prefix = '';
                }

                if ('' != $uri) {
					$baseUri = self::parseUri();
                    $url = str_replace(Breadcrumb::getBaseUriMatches(), $baseUri, $uri); //#TODO duplicative?
?>
        <li<?php print($class);?>><?php print($prefix); ?><a href="<?php print($url); ?>"><?php print($label);?></a></li>
<?php
                } else {
?>
        <li<?php print($class);?>><?php print($prefix . $label);?></li>
<?php
                }
            }
        ?>
        </ul>
<?php
        }
    }

    /**
     * output any buffered messages
     */
    public static function outputMessages()
    {

    }

    /**
     * output any buffered messages
     */
    public static function setMessage()
    {

    }

    public static function debugHeader()
    {
        if(Debug::isVerbose()) {
            DebugUi::printDebugAnchor();
            DebugUi::printDebugReveal();
            DebugUi::printProfileReveal();
        }
    }

    public static function debugFooter()
    {
        if(Debug::isEnabled()){
?>
<!-- debug buffer -->
<?php
            DebugUi::flushBuffer();
            DebugUi::printDebugExit();
        }
        if (Debug::isVerbose()) {
            DebugUi::printDebugEntry();
        }
    }

    /**
     * output the HTML for an icon
     * @param string $symbol
     * @param string $altText
     */
    public static function printIcon($symbol, $altText='')
    {
        print(self::getIcon($symbol, $altText));
    }

    /**
     * get the HTML string for an icon
     * @param string $symbol
     * @param string $altText
     */
    public static function getIcon($symbol, $altText='')
    {
        $variant = '';
        if(strpos($symbol,':')){
            $variant = substr($symbol, 0, strpos($symbol,':'));
            $symbol = substr($symbol, strpos($symbol,':') + 1);
        }
        $title = $altText ? ' title="' . htmlentities($altText) . '"' : '';
        return "<span{$title} class=\"fa{$variant} fa-{$symbol}\"></span>";
    }

    public static function isReady()
    {
        return TRUE; //#TODO #2.0.0 return false if not rendering an html view.
    }

    public static function stateCheck($request = array()) //#TODO #2.0.0 decouple from Session
    {
        if(array_key_exists('forceDesktop', $request)) {
            if (Truthy::filter($request['forceDesktop'])) {
                $_SESSION['forceDesktopView'] = TRUE;
            } else if (array_key_exists('forceDesktopView', $_SESSION)) {
                unset($_SESSION['forceDesktopView']);
            }
        }
        return array_key_exists('forceDesktopView', $_SESSION);
    }

    public static function jQueryCdn($version = NULL, $uiVersion = NULL) //#TODO move these to Layout_Cdn
    {
        if ($version) {
            print("<script src=\"//ajax.googleapis.com/ajax/libs/jquery/{$version}/jquery.min.js\"></script>");
        }
        if ($uiVersion) {
            print("<link rel=\"stylesheet\" href=\"https://ajax.googleapis.com/ajax/libs/jqueryui/{$uiVersion}/themes/smoothness/jquery-ui.css\">");
            print("<script src=\"https://ajax.googleapis.com/ajax/libs/jqueryui/{$uiVersion}/jquery-ui.min.js\"></script>");
        }
    }

    public static function externalJs($name) //#TODO candidate to deprecate
    {
        $baseUri = self::parseUri('');
        print("<script src=\"{$baseUri}javascript/external/{$name}.js\" type=\"text/javascript\" charset=\"utf\"></script>");
    }

    public static function foundationCss($version, $addons = array())
    {
        $internal = array('magellan','dropdown'); //#TODO #1.11.0 we only need to do this when not using the min version?
        $baseUri = self::parseUri('');
        if ($version) {
            print("<link href=\"{$baseUri}foundation/css/foundation.min.css\" rel=\"stylesheet\" type=\"text/css\" media=\"screen\"/>");
        }
        foreach($addons as $name) {
            if (!in_array($name, $internal)) {
                print("<link href=\"{$baseUri}foundation/css/{$name}.min.css\" rel=\"stylesheet\" type=\"text/css\" media=\"screen\"/>");
            }
        }
    }
    public static function foundationJs($version, $addons = array())
    {
        $internal = array(
            'abide',
            'accordion',
            'alert',
            'clearing',
            'dropdown',
            'equalizer',
            'interchange',
            'joyride',
            'magellan',
            'offcanvas',
            'orbit',
            'reveal',
            'slider',
            'tab',
            'tooltip',
            'topbar'
        ); //#TODO #1.11.0 we only need to do this when not using the min version?
        $baseUri = self::parseUri('');
        if ($version) {
            if (self::$min) {
                print("<script src=\"{$baseUri}foundation/js/foundation.min.js\" type=\"text/javascript\" charset=\"utf\"></script>");
            } else {
                print("<script src=\"{$baseUri}foundation/js/foundation/foundation.js\" type=\"text/javascript\" charset=\"utf\"></script>");
            }
        }
        foreach($addons as $name) {
            if (in_array($name, $internal)) {
                print("<script src=\"{$baseUri}foundation/js/foundation/foundation.{$name}.js\" type=\"text/javascript\" charset=\"utf\"></script>");
            } else {
                print("<script src=\"{$baseUri}foundation/js/{$name}.min.js\" type=\"text/javascript\" charset=\"utf\"></script>");
            }
        }
    }

    public static function useMin()
    {
        self::$min = true;
    }

    public static function foundationPrerequisites($optional = [])
    {
        if (!is_array($optional)) {
            $optional = array($optional);
        }
        $baseUri = self::parseUri('');
        print("<script src=\"{$baseUri}foundation/js/vendor/modernizr.js\" type=\"text/javascript\" charset=\"utf\"></script>");
        if (in_array('jquery', $optional)) {
            print("<script src=\"{$baseUri}foundation/js/vendor/jquery.js\" type=\"text/javascript\" charset=\"utf\"></script>");
        }
    }

    public static function foundationLateIncludes($include = array())
    {
        $baseUri = self::parseUri('');
        if (array_key_exists('fastclick', $include) || in_array('fastclick',$include)) {
            print("<script src=\"{$baseUri}foundation/js/vendor/fastclick.js\" type=\"text/javascript\" charset=\"utf\"></script>");
        }
        print("<script type=\"text/javascript\">saf.endBody();</script>");
    }

    
    public static function bootStrapCdn($version) //#TODO deprecate?
    {
        print('');
    }

    public static function getCdnResource($url, $altContent)
    {
        try {
            $curl = new Http(array(
                'url' => $url
            ));
            $result = $curl->go();
            if (
                $result 
                && array_key_exists('status', $result)
                && $result['status'] > 99
                && $result['status'] < 300
                && array_key_exists('raw', $result)
            ) {
                return $result['raw'];
            } else {
                Debug::outData(array('deficient cdn response', $url, $result));
            }
        } catch (\Exception $e) {
            Debug::outData(array('failed to curl cdn resource', $url, $e));
        }
        return $altContent;
    }
}