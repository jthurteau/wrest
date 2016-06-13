<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for localization

*******************************************************************************/

class Saf_Language
{
	protected $_config = NULL;

	/**
	 * maximum depth when dereferencing
	 * @var int
	 */
	const MAX_DEREF_DEPTH = 10;

	function __construct($config){
		$this->_config = $config;
	}

	/**
	 * return a localized term, return $default if no match is found
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public function get($name, $default = ''){
		$phrase = $this->dereference($this->_config->get($name));
		return ('' != trim($phrase) ? $phrase : $default);
	}
	
	/**
	 * return a localized term without defererencing any terms it contains
	 * @param string $name
	 * @throws Exception when there is no matching term
	 * @return string
	 */
	public function getRaw($name){
		return $this->_config->get($name);
	}

	/**
	 * print a localized term, print $default if no match is found
	 * @param string $name
	 * @param string $default
	 */
	public function say($name, $default = ''){
		print($this->get($name, $default));
		return $this;
	}
	
	/**
	 * convert parse a localized term, localizing references to other terms.
	 * @param string $string
	 * @return string
	 */
	public function dereference($string){
		$newString = $string;
		for ($i=0; $i < self::MAX_DEREF_DEPTH; $i++){
			$startCut = strpos($newString, '[[');
			if ($startCut !== false) {
				$endCut = strpos($newString, ']]', $startCut);
				if ($endCut !== false) {
					$term = substr($newString, $startCut + 2, $endCut - ($startCut + 2));
				}
				$newString = str_replace("[[{$term}]]", $this->_config->get($term), $newString);
			} else {
				break;
			}
		}
		return $newString;
	}

}

