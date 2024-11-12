<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for generating messages (e.g. XML)
 */

namespace Saf\Utils;

use Saf\Utils\Reflector;

class Template //#TODO deprecate in favor of Util/Template
{
    protected static $templateOptions = [
        'php',
        'xml'
    ];

    protected $rawMessage = '';
    protected $viewPath = '';


    protected static $messagePath = 'application:/messages';

    public function __construct(string $messageName, string $ext = null)
    {
        $path = self::$messagePath;
        if (strpos($path,'application:') === 0) {
            $path = str_replace('application:', \Saf\APPLICATION_PATH, $path);
        }
        if (is_null($ext)) {
            foreach(self::$templateOptions as $extOption){
                if (file_exists("{$path}/{$messageName}.{$extOption}")) {
                    $ext = $extOption;
                    break;
                }
            }
        }
        !is_null($ext) || throw new \Exception("No template for {$messageName} available.");
        $messagePath ="{$path}/{$messageName}.{$ext}";
        is_readable($messagePath) || throw new \Exception("Template for {$messageName} unavailable.");
        switch($ext) {
            case 'php':
                $this->viewPath = $messagePath;
                break;
            default:
                $this->rawMessage = file_get_contents($messagePath);
        }
    }
    
    public function get($params = null)
    {
        if ($this->viewPath) {
            ob_start();
            $extractParams = key_exists('params', $params) ? $params['params'] : $params ;
            if (is_array($extractParams)) {
                extract($extractParams);
            } 
            require $this->viewPath;
            $buffer = ob_get_contents();
            ob_end_clean();
            // if (key_exists('timezone',$extractParams)) {
            //    die(\Saf\Debug::stringR([__FILE__,__LINE__,$extractParams,$buffer));
            // }
            return $buffer;
        }
        return
            is_null($params)
            ? $this->rawMessage
            : Reflector::dereference($this->rawMessage, $params);
    }

    public static function render($phpPath, $canister){
        $output = '';
        try{
            ob_start();
            //var_export($context);
            require($phpPath);
            $output .= ob_get_contents();
            ob_end_clean();
            return $output;
        } catch (\Error | \Exception $e) {
            throw new \Exception("Emmiter Exception while rendering {$phpPath}", 500 , $e);
        }
    }

    public static function setPath(string $path)
    {
        self::$messagePath = $path;
    }
}

