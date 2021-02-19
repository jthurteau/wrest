<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Base class for filters
 */

namespace Saf;

abstract class Filter
{
	public static function filter($value)
	{
		return $value;
	}
	
	public static function breakup($whole, $match)
    {

//print_r(array(memory_get_usage(),memory_get_peak_usage(),128000000,ini_get('memory_limit'))); die;
		$result = array();
		$result['start'] = strpos($whole, $match);
		if ($result['start'] === FALSE) {
			$result['before'] = $whole;
			return $result;
		}
		$result['end'] = $result['start'] + strlen($match);
		$result['before'] = 
			$result['start'] 
			? substr($whole, 0, $result['start'])
			: '';
		$result['match'] = substr($whole, $result['start'], strlen($match));
		if ($result['end'] < strlen($whole)) {
			$result['after'] = substr($whole, $result['end']);
		}
		// $thresh = 20000000;
		// $usage = memory_get_usage();
		// if ($usage > $thresh) {
		// 	print_r(array($whole,$match,$result)); die;
		// 	//throw new Exception("Memory Usage Throttled at $usage");
		// }
//print_r(array('breakup_result', $whole, $match, $result));
		return $result;
	}
	
	protected static function _orderFilterMethods($prefix, $name)
    {
//print_r(array('order methods', $prefix, $name));		
        $options = substr($name,strlen($prefix));
        $names = array();
        if ($options) {
            $optionList = str_split($options);
            foreach($optionList as $option) {
                $names[$option] = self::_generateFilterMethodName($prefix, $option);
            }
        } else {
            $names[''] = self::_generateFilterMethodName($prefix, ''); //"_{$prefix}";
        }
        return $names;
	}
	
	protected static function _generateFilterMethodName($prefix, $option)
	{
		$type = ucfirst($prefix);
		$typer = (strrpos($type,'e') == strlen($type) - 1) ? substr($type, 0, -1) : $type;
		return "Saf_Filter_{$typer}er_{$type}{$option}::{$prefix}";//"_{$prefix}{$option}"
	}

	/**
	 * takes a callable, and returns a Reflector that can be invoked.
	 * In the case of instantiated callables, the return is an array
	 * bundling the object [0] and method reflector [1], or a relector class (ReflectionFunction or static ReflectionMethod)
	 * @param string $callable
	 * @return array 0 object or string, 1 Reflection
	 */
	protected static function _instantiateCallable($callable)
	{//#TODO #2.0.0 this is a candidate for being made a public member of Kickstart or something more global
		if (is_string($callable) && strpos($callable, '::') === FALSE) {
			return new ReflectionFunction($callable);
		}
		if (!is_array($callable)) {
			$callable = explode('::', $callable);
//print_r(array($callable)); //die;
			return new ReflectionMethod($callable[0],$callable[1]);
		} else {
			return array($callable[0], new ReflectionMethod($callable[0],$callable[1]));
		}
	}
}