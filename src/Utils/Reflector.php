<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for reflection
 */

namespace Saf\Utils;

use Saf\Hash;
use Saf\Utils\Reflector;
use Saf\Utils\Debug\Mute;
use Saf\Debug;

class Reflector //#TODO #2.X.0 deprecate in favor of a more bare-bones view templating implementation
{
    const MAX_DEREF_DEPTH = 10;

    protected static $_encodeMap = array(
        '\\', '\]', '\)',
        '\-', '\$', '\*',
        '\;', '\?','\`'
    );

    protected static $_decodeMap = array(
        '&MRslash;','&MRcloseTerm;','&MRclosecall;',
        '&MRbar;','&MRref;', '&MRunique;',
        '&MRsep;', '&MRcond;', "&MRlit;",
        "&MRdem"
    );

    protected static $_filterMap = array(
        ':'
    );

    protected static $_unfilterMap = array(
        '&MRcol;'
    );

    public static function dereference($string, &$config = array(), $depth = 0)
    {
        if (
            array_key_exists('params', $config)
            && is_array($config['params'])
            && $depth == 0
        ) {
            foreach($config['params'] as $index => $value) {
                if (is_string($value)) {
                    $config['params'][$index] = self::_filterEscaped($value);
                }
            }
        }
        // $zendAutoloader = Zend_Loader_Autoloader::getInstance();
        // $currentZendAutoloaderSetting = $zendAutoloader->suppressNotFoundWarnings();
        // $zendAutoloader->suppressNotFoundWarnings(TRUE);
        if ($depth > self::MAX_DEREF_DEPTH) {
            throw new \Exepction('Model Reflection error: dereferenced too deeply.');
        }
        $myMute = Mute::activate();
        if (!array_key_exists('_lateBindingParams', $config)) {
            $config['_lateBindingParams'] = array();
        }
        $supplement =
            Debug::isEnabled()
                && is_array($config)
                && array_key_exists('derefSource', $config)
            ? (' in ' . Hash::extract('derefSource', $config, 'undisclosed_source'))
            : '';
        $newString = self::_encodeEscaped($string);
        //for ($i=0; $i < self::MAX_DEREF_DEPTH; $i++){
        $startCut = strpos($newString, '[[');
        while($startCut !== false) {
            $skipLength = 2;
            $endCut = strpos($newString, ']]', $startCut);
            if ($endCut !== false) {
                $term = substr($newString, $startCut + 2, $endCut - ($startCut + 2));
                if (strpos($term, '#') !== 0) {
                    try {
                        $translatedTerm = self::translate($term, $config, $depth + 1);
                    } catch (\Exception $e) {
                        Mute::deactivate($myMute);
                        throw $e;
                    }
                    $unique = strpos($term, '*') === 0;
                    $newString =
                        $unique
                        ? str_replace("[[{$term}]]", $translatedTerm, $newString, $unique)
                        : str_replace("[[{$term}]]", $translatedTerm, $newString);
                    $skipLength = strlen($translatedTerm);
                } else {
                    $skipLength = ($endCut - $startCut) + 2;
                }
            } else {
                Mute::deactivate($myMute);
//print_r(array($string,$newString)); die;
                throw new \Exception("Model Reflection Error: Unterminated term {$supplement}");
            }
            $startCut = strpos($newString, '[[', $startCut + $skipLength);
        }
        Mute::deactivate($myMute);
        // $zendAutoloader->suppressNotFoundWarnings($currentZendAutoloaderSetting);
        return $depth == 0 ? self::_unfilterEscaped($newString) : $newString;
    }

    public static function translate($term, &$config = array(), $depth = 0)
    {
        // $zendAutoloader = Zend_Loader_Autoloader::getInstance();
        // $currentZendAutoloaderSetting = $zendAutoloader->suppressNotFoundWarnings();
        //$zendAutoloader->suppressNotFoundWarnings(TRUE);
        if ($depth > self::MAX_DEREF_DEPTH) {
            throw new \Exepction('Model Reflection error: translated too deeply.');
        }
        $myMute = Mute::activate();
        if (!array_key_exists('_lateBindingParams', $config)) {
            $config['_lateBindingParams'] = array();
        }
        $model = array();
        $modelIsIterable = FALSE;
        $allowsNonString =
            array_key_exists('allowNonStrings', $config)
            && $config['allowNonStrings'];
        $termIsIterable = !array_key_exists('termIterable', $config) || $config['termIterable'];
        $term = trim($term);
        if (strpos($term, '*') === 0) {
            $term = substr($term, 1); //#TODO #2.0.0 if not unique, pull from cache
        }
        if ($termIsIterable && strpos($term, ';') !== FALSE) {
            $terms = explode(';', $term);
        } else {
            $terms = array($term);
        }
        try {
            foreach($terms as $currentTerm) {
                $conditional = FALSE;
                if (strpos($currentTerm, '?') === 0) {
                    $conditional = TRUE;
                    $currentTerm = trim(substr($currentTerm, 1));
                }
                $currentTerm = trim(self::_translateVariables($currentTerm, $conditional, $config));
                if (strpos($currentTerm, '=') !== FALSE) {
                    $tagParts = explode('=', $currentTerm, 2);
                    $model[] = self::_translateTag(trim($tagParts[0]),trim($tagParts[1]), $conditional, $config, $depth + 1);
                } else {
                    if(self::_validClassName($currentTerm)){
                        //#TODO #2.0.0 branch this out so it is framework agnostic
                        $autoloader = Zend_Loader_Autoloader::getInstance();
                        $autoloader->suppressNotFoundWarnings(TRUE);
                        try {
                            if(Zend_Loader_Autoloader::autoload($currentTerm)){
                                $modelObject = new $currentTerm();
                                $model[] = $modelObject;
                            }else{
                                //#TODO #1.1.0
                                $model[] = $currentTerm;

                            }

                        } catch (Exception $e) {
                            $model[] = $currentTerm;
                        }
                        $autoloader->suppressNotFoundWarnings(FALSE);
                    } else {
                        $nextComment = strpos($currentTerm, '<!--');
                        $nextObjectRef = strpos($currentTerm,'->');
                        while ($nextComment !== FALSE && $nextObjectRef !== FALSE && $nextComment < $nextObjectRef) {
                            $nextComment = strpos($currentTerm, '<!--', $nextObjectRef + 1);
                            $nextObjectRef = strpos($currentTerm,'->', $nextObjectRef + 1);
                        }
                        $nextClassRef = strpos($currentTerm,'::'); //#TODO #2.0.0 simplify this logic since it can only be the first in the stack.
                        if (
                            $nextObjectRef !== FALSE
                            && ($nextClassRef === FALSE || $nextObjectRef < $nextClassRef)
                        ) {
                            $termObject = substr($currentTerm, 0, strpos($currentTerm,'->'));
                            $termRest = substr($currentTerm, strpos($currentTerm,'->') + 2);
                            $model[] = self::_translateObject($termObject, $termRest, $conditional, $config, $depth + 1);
                        } else if ($nextClassRef !== FALSE) {
                            $termClass = substr($currentTerm, 0, strpos($currentTerm,'::'));
                            $termRest = substr($currentTerm, strpos($currentTerm,'::') + 2);
                            $model[] = self::_translateClass($termClass, $termRest, $conditional, $config, $depth + 1);//call_user_func(array($termParts[0],$termParts[1]));
                        } else {
                            $model[] = $currentTerm;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Mute::deactivate($myMute);
            throw $e;
        }
        Mute::deactivate($myMute);
        //$zendAutoloader->suppressNotFoundWarnings($currentZendAutoloaderSetting);
        if (
            (class_exists('\Saf\Audit', FALSE) && class_exists('\Saf\Auth', FALSE)) 
            && (is_object($model))
        ) {
            if (!method_exists( $model, '__toString' )) {
                try {
                    throw new \Exception('stringable object');
                } catch (Exception $e) {
                    \Saf\Audit::add('reflector_debug', \Saf\Audit::exceptionMessage($e), array($term, $config, $depth), \Saf\Auth::getPluginProvidedUsername());
                }
            } else {
                $logged = NULL;
                try {
                    throw new Exception('non-stringable object');
                } catch (Exception $e) {
                    $logged = \Saf\Audit::add('reflector_debug', \Saf\Audit::exceptionMessage($e), array($term, $config, $depth), \Saf\Auth::getPluginProvidedUsername());
                }
                if ($logged) {
                    throw new \Saf\Exception\InternalTracked("Model Reflector could not translate object.", $logged);
                } else {
                    throw new \Saf\Exception\Internal("Model Reflector could not translate object.");
                }
            }
        }
        return
            $allowsNonString
            ? ($modelIsIterable ? $model : $model[0])
            : implode('', $model);
    }

    protected static function _translateTag($tagName, $tagContents, $conditional, &$config, $depth)
    {
        if ($depth > self::MAX_DEREF_DEPTH) {
            throw new \Exepction('Model Reflection error: translated tag too deeply.');
        }
// if ('value' == $tagName) {
// print_r(array($tagName, $tagContents, $conditional, $config));die;
// }
        $conditionalFlag =
            $conditional
            ? '?'
            : '';
        if (array_key_exists($tagContents, $config['_lateBindingParams'])){
            $contents = $config['_lateBindingParams'][$tagContents];
        } else {
            $blockingConfig = $config;
            $blockingConfig['termIterable'] = FALSE;
            $contents = trim(self::translate("{$conditionalFlag}{$tagContents}", $blockingConfig, $depth + 1));
        }
        if (is_array($contents)) {
            $contentString = '';
            foreach($contents as $content) {

                $contentString .= $content
                ? ("<{$tagName}>" . $content . "</{$tagName}>")
                : '';
                if ($contentString == '' && !$conditional) {
                    $contentString = "<$tagName/>";
                }
            }
// if ($tagName == 'EventName') {
// 	print_r(array($tagName, $tagContents, $contentString, $conditional, $config, $contents)); die;
// }
            return $contentString;
        } else {
            $trimContents = trim($contents);
            $ommitedContents =
                strpos($trimContents, '<!--') === 0
                && strrpos($trimContents,'->') === strlen($trimContents) - 2; //#TODO #2.0.0 not flawless
            $contentString = "<{$tagName}>{$contents}</{$tagName}>";
            $return =
                !is_null($contents) && '' !== $contents
                ? (
                    $ommitedContents && $conditional
                    ? ''
                    : $contentString
                ) : (
                    $conditional
                    ? (
                        $ommitedContents
                        ? $trimContents
                        : ''
                    ) : "<{$tagName}/>"
                );
// if ($tagName == 'EventName') {
//  	print_r(array($tagName, $tagContents, $contents, $return, $conditional, $config, $ommitedContents,strpos($trimContents, '<!--'), strrpos($trimContents,'->'),strlen($trimContents) - 2));die;
// }
            return $return;
        }
    }

    protected static function _translateVariables($term, $conditional, &$config)
    {
        $variables = array();
        $variablePattern = '/(?P<var>\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';
        $matches = array();
        $match = preg_match_all($variablePattern, $term, $matches);
        if ($match && array_key_exists('var', $matches)) {
            $variables = $matches['var'];
        }
        foreach($variables as $variable) {
            $name = trim(substr($variable, 1));
            if (
                $conditional
                || (
                    array_key_exists('params', $config)
                    && array_key_exists($name, $config['params'])
                )
            ) {
                if (
                    array_key_exists($name, $config['params'])
                    && is_array($config['params'][$name])
                ) { //Let functions like translateTags detect and handle iterative data
                    $config['_lateBindingParams'][$variable] = $config['params'][$name];
                } else if (array_key_exists($name, $config['params'])) {
//if ('$auth' != $term) {
//	print_r(array($term, $conditional, &$config, $name, $variable)); die;
//}						
                    $term = str_replace($variable, $config['params'][$name], $term);
                } else {
//if ('$auth' != $term) {
//	print_r(array('wut?',array_key_exists($name, $config['params']),$term, $conditional, &$config, $name, $variable)); die;
//}
                    $term = str_replace($variable, "<!-- ommited $name -->", $term);
                }
            } else {
                throw new \Exception(
                    'Model Reflection error: unknown parameter variable'
                    . (
                        Debug::isEnabled()
                        ? " {$name}."
                        : '.'
                    )
                );
            }
        }
        return $term;
    }

    protected static function _walkParams($string)
    {
        //$return = explode(',', $string);
        $return = array(); //#TODO #2.0.0 needs more work?
        $start = 0;
        do {
            $nextDelim = strpos($string, ',', $start);
            $nextParamRef = strpos($string,'(', $start);
            $nextParamEnd = strpos($string,')', $nextParamRef);
            if (
                $nextDelim !== FALSE
                && ($nextParamRef == FALSE || $nextDelim < $nextParamRef)
            ){
                $return[] = substr($string, $start, $nextDelim);
            } else {
                $return[] = substr($string, $start);
            }
            $start = $nextDelim + 1;
        } while($nextDelim !== FALSE && $start < strlen($string));

//  if (
//  	'' != $string
//  	&& 'Ems->getAllRoomNames' != $string
// // 	&& 'Ems->getBookingDates' != $string
// // 	&& 'Ems->getDayTimeBlocks(' != $string

//  ) {
//  			print_r(array('['.$string.']',$return)); die;
//  }
        return $return;
    }

    protected static function _scanParams($string)
    {
        return strpos($string,')');  // #TODO #2.0.0 this needs to scan for the matching close
    }

    protected static function _translateObject($object, $term, $conditional, &$config, $depth = 0)
    {
        if ($depth > self::MAX_DEREF_DEPTH) {
            throw new \Exepction('Model Reflection error: translated object too deeply.');
        }
        if (!is_object($object) && !self::_validClassName($object)){
            throw new \Exception('Model Reflection error: invalid object name'
                . (Debug::isEnabled() ? " {$object} {$term}.": '.')
            );
        }
        $nextObjectRef = strpos($term,'->');
        $nextParamRef = strpos($term,'(');
        $allowsNonString =
            array_key_exists('allowNonStrings', $config)
            && $config['allowNonStrings'];
        if (!is_object($object)) {
            $objectString = $object;
            $object = new $object(); //#TODO #2.0.0 constructor param(s) from config?
        } else {
            $objectString = get_class($object);
        }
        $reflector = new \ReflectionObject($object);
        if (
            $nextParamRef != FALSE
            && ($nextObjectRef === FALSE || $nextParamRef < $nextObjectRef)
        ) {
            $termMethod = trim(substr($term, 0, $nextParamRef));
            $termRest = substr($term, $nextParamRef + 1);
// if (
// 		'arrayToMultiOptions(Ems->getDayTimeBlocks(`1`, 4)' == $term
// ) {
// 	print_r(array($termMethod, $termRest)); //die;
// }
            $endParam = self::_scanParams($termRest);
            if ($endParam !== FALSE) {
                $termParam = trim(substr($termRest, 0, $endParam));
                $termRest = trim(substr($termRest, $endParam + 1));
            } else {
                $termParam = trim($termRest);
                $termRest = '';
            }
            $params = self::_walkParams($termParam);
// if (
// 	'arrayToMultiOptions(Ems->getDayTimeBlocks(`1`, 4)' == $term
// ) {
//	print_r(array('a',$term,$termMethod,$termParam,$termRest,'params'=>$params)); 
// }
            $paramConfig = array_merge($config, array('allowNonStrings' => TRUE));
            foreach($params as $paramIndex=>$param) {
                $param = trim($param);
                if (strpos($param,'`') === 0 && strrpos($param, '`') == (strlen($param) - 1)) {
                    $params[$paramIndex] = substr($param, 1, strlen($param) - 2);
                    if ('TRUE' == $params[$paramIndex]) {
                        $params[$paramIndex] = TRUE;
                    }
                    if ('FALSE' == $params[$paramIndex]) {
                        $params[$paramIndex] = FALSE;
                    }
                    if (
                        is_numeric($params[$paramIndex])
                        && (
                            strlen($params[$paramIndex]) == strlen((int)$params[$paramIndex])
                            || strlen($params[$paramIndex]) == strlen((float)$params[$paramIndex])
                        )
                    ) {
                        $params[$paramIndex] = (int)$params[$paramIndex];//#TODO #1.1.0 actually check for digits only non leading zeros
                    }
                } else {
                    $params[$paramIndex] = self::translate($param, $paramConfig, $depth + 1); //#TODO #2.0.0 flag in config to indivate returning a non-string
//print_r(array('b',$term,$termMethod,$termParam,$termRest,'params'=>$params,$paramIndex,$param));
// 	if ('4' == $param) {
// 		print_r($params); die;
// 	}
                }
            }
            if ($reflector->hasMethod($termMethod)) {
                $reflectorMethod = $reflector->getMethod($termMethod);
                return
                    $allowsNonString
                    ? $reflectorMethod->invokeArgs($object, $params)
                    : (string)$reflectorMethod->invokeArgs($object, $params);
            } else {
                throw new \Exception(
                    'Model Reflection Error : Missing method'
                    . (
                        Debug::isEnabled()
                        ? " {$termMethod} in {$objectString}"
                        : ''
                    )
                ); //#TODO #1.1.0
            }
        } else if (
            $nextObjectRef !== FALSE
            && ($nextClassRef === FALSE || $nextObjectRef < $nextClassRef)
        ) {
            $currentTerm = substr($term, 0, strpos($term,'->'));
            $termRest = trim(substr($term, strpos($term,'->') + 2));
            if ($reflector->hasProperty($currentTerm)) {
                $nextObject = $reflector->getProperty($currentTerm);
                $allowsNonString
                    ? self::_translateObject($nextObject, $termRest, $conditional, $config, $depth + 1)
                    : (string)self::_translateObject($nextObject, $termRest, $conditional, $config, $depth + 1);
            }
            //$modelString .= self::_translateObject($termObject, $termRest, $conditional, $config);
        } else {
            if ($reflector->hasMethod($term)) {
                $nextObject = $reflector->getMethod($term)->invoke($object);
            } else if ($reflector->hasProperty($term)) {
                $nextObject = $reflector->getProperty($term);
            } else {
                throw new \Exception(
                    'Model Reflection Error : Missing property or method'
                    . (
                        Debug::isEnabled()
                        ? " {$term} in {$objectString}"
                        : ''
                    )
                );
            }
            return
                $allowsNonString
                ? $nextObject
                : (string)$nextObject;
        }
    }

    protected static function _translateClass($class, $term, $conditional, &$config, $depth = 0)
    {
        if ($depth > self::MAX_DEREF_DEPTH) {
            throw new Exepction('Model Reflection error: translated class too deeply.');
        }
        if (!is_object($class) && !self::_validClassName($class)){
            throw new Exception('Model Reflection error: invalid class name'
                . (Debug::isEnabled() ? " {$class} {$term}.": '.')
            );
        }
        $nextObjectRef = strpos($term,'->');
        $nextParamRef = strpos($term,'(');
        $reflector = new \ReflectionClass($class);
        $allowsNonString =
            array_key_exists('allowNonStrings', $config)
            && $config['allowNonStrings'];
        if (
            $nextParamRef != FALSE
            && ($nextObjectRef === FALSE || $nextParamRef < $nextObjectRef)
        ) {
            $termMethod = trim(substr($term, 0, strpos($term,'(')));
            $termRest = substr($currentTerm, strpos($currentTerm,'(') + 1);
            $endParam = strpos($termRest,')');  // #TODO #2.0.0 this needs to scan for the matching close
            if ($endParam !== FALSE) {
                $termParam = trim(substr($term, 0, $endParam));
                $termRest = trim(substr($currentTerm, $endParam + 1));
            } else {
                $termParam = trim($termRest);
                $termRest = '';
            }
            $params = explode(',', $termParam);
            $paramConfig = array_merge($config, array('allowNonStrings' => TRUE));
            foreach($params as $paramIndex=>$param) {
                $param = trim($param);
                if (strpos($param,'`') === 0 && strrpos($param, '`') == (strlen($param) - 1)) {
                    $params[$paramIndex] = substr($param, 1, strlen($param) - 2);
                } else {
                    $params[$paramIndex] = self::translate($param, $paramConfig, $depth + 1); //#TODO #2.0.0 flag in config to indivate returning a non-string
                }
            }
            $allowsNonString
                ? $reflector->getMethod($termMethod)->invokeArgs(NULL, $params)
                : (string)$reflector->getMethod($termMethod)->invokeArgs(NULL, $params);
        } else if (
            $nextObjectRef !== FALSE
            && ($nextClassRef === FALSE || $nextObjectRef < $nextClassRef)
        ) {
            $currentTerm = substr($term, 0, strpos($term,'->'));
            $termRest = trim(substr($term, strpos($term,'->') + 2));
            if (isset($class::$$currentTerm)) {
                $nextObject = $class::$$currentTerm;
                print_r(array('TBR' => 'check allows non string')); die;
                return
                    $allowsNonString
                    ? self::_translateObject($nextObject, $termRest, $conditional, $config, $depth + 1)
                    : (string)self::_translateObject($nextObject, $termRest, $conditional, $config, $depth + 1);
            }
        } else {
            if ($reflector->hasMethod($term)) {
                $method = $reflector->getMethod($term);
                return
                    $allowsNonString
                    ? $method->invoke(NULL)
                    : (string)$method->invoke(NULL);
            } if (FALSE) {
                print_r(array('TBR' => 'support constants')); die;
                //#TODO #2.0.0 support constants
            } else {
                return
                    $allowsNonString
                    ? $reflector->getStaticPropertyValue($term)
                    : (string)$reflector->getStaticPropertyValue($term);
            }
        }
    }

    protected static function _containsNonNameCharacters($term)
    {
        return strpos($term, ' ') !== FALSE
            || strpos($term, "$") !== FALSE
            || strpos($term, "\n") !== FALSE
            || strpos($term, "\t") !== FALSE
            || strpos($term, "->") !== FALSE
            || strpos($term, "::") !== FALSE
            || strpos($term, "(") !== FALSE;
    }

    protected static function _validClassName($name)
    {
        $pattern = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';
        return preg_match($pattern, $name);
    }

    protected static function _encodeEscaped($string)
    {
        return str_replace(
            self::$_encodeMap,
            self::$_decodeMap,
            $string
        );
    }

    protected static function _unencodeEscaped($string)
    {
        return str_replace(
            self::$_decodeMap,
            self::$_encodeMap,
            $string
        );
    }

    protected static function _filterEscaped($string)
    {
        return str_replace(
            self::$_filterMap,
            self::$_unfilterMap,
            $string
        );
    }

    protected static function _unfilterEscaped($string)
    {
        return str_replace(
            self::$_unfilterMap,
            self::$_filterMap,
            $string
        );
    }

}

