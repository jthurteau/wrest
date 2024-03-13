<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for generating messages (e.g. XML)
 */

namespace Saf\Util;

use Saf\Utils\Reflector;

class Template
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
        
        $path = self::autoPath($messageName);
        if (is_null($ext)) {
            foreach(self::$templateOptions as $extOption){
                if (file_exists("{$path}.{$extOption}")) {
                    $ext = $extOption;
                    break;
                }
            }
        }
        $basename = basename($messageName);
        !is_null($ext) || throw new \Exception("No template for {$basename} available.");
        $messagePath ="{$path}.{$ext}";
        is_readable($messagePath) || throw new \Exception("Template for {$basename} unavailable.");
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
            $buffer = ob_get_clean();
            ob_end_clean();
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
            $output .= ob_get_clean();
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

    public static function getPath() : string
    {
        return self::$messagePath;
    }

    protected static function autoPath(string $path) : string
    {
        $qualified = strpos($path, ':'); // #NOTE false or 0
        $path = $qualified ? $path: ("'application:'{$path}");
        if (strpos($path, 'file:') === 0) {
            $path = str_replace('file:', '', $path);
        }
        if (strpos($path, 'application:') === 0) {
            $path = str_replace('application:', \Saf\APPLICATION_PATH, $path);
        }
        return $path;
    }

}

