<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility functions for altering URLs

*******************************************************************************/

class Saf_UrlRewrite{

	/**
	 * formats a $url with provided query ($getArray) sanitized based on $config
	 * @param string $url
	 * @param array $getArray
	 * @param array $config
	 * @return string full url with query
	 */
	public static function scrubGet($url, $getArray, $config)
	{//#TODO #2.0.0 support get query as a string
		$scrubbedUrl =
			strpos($url, '?') !== false
			? substr($url, 0 , strpos($url, '?'))
			: $url;
		$scrubbedSearch = '?';
		foreach($getArray as $getName=>$getValue){
			if(
				(
					array_key_exists('include', $config)
					&& (
						(is_array($config['include']) && in_array($getName, $config['include']))
						|| $getName == $config['include']
					)
				) || (
					array_key_exists('exclude', $config)
					&& (
						(is_array($config['exclude']) && !in_array($getName, $config['exclude']))
						|| (is_string($config['exclude']) && $getName != $config['exclude'])
					)
				)
			) {
				$scrubbedSearch .= ('?' == $scrubbedSearch ? '' : '&') . urlencode($getName) . '=' . $getValue;
			}
		}
		return $scrubbedUrl . ('?' != $scrubbedSearch ? $scrubbedSearch : '');
	}
	
	/**
	 * parses (currently rather dumbly) a string to see if it is safe to add to 
	 * a URL.
	 * @param string $string to evaluate
	 * @return bool is safe for a URL
	 */		
	
	public static function isUrlUnsafe($string)
	{// #TODO #2.0.0 look for unbalanced & and ;, allow / in non-queries via flag param
		return strpbrk($string, '?,/:@<"\'#%{}|\\^~[]`') !== FALSE; //#NOTE + is a valid encoding for space
	}
	
	public static function urlEncode($string)
	{
		return urlencode($string);
	}

	/**
	 * returns an encoded version of the string that is safe to add to a URL
	 * @param string $string to evaluate and alter
	 * @return string safe string
	 */
	public static function makeUrlSafe($string)
	{
		return self::isUrlUnsafe($cleanQuery) ? self::urlEncode($cleanQuery) : $cleanQuery;
	}
	
	/**
	 * returns an encoded version of the url that is safe to add to a URL
	 * @param string $url alter
	 * @param bool $userFriendly
	 * @return string safe url query component
	 */
	public static function encodeForward($url, $userFriendly = FALSE)
	{
		return
			$url
			? (
				$userFriendly
				? ('forwardUrl=' . urlencode($url))
				: ('forwardCode=.' . urlencode(base64_encode($url)))
			) : '';
	}
	
	/**
	 * decodes a url that was encoded with encodeForward
	 * @param string $url
	 * @return string url
	 */
	public static function decodeForward($url)
	{
		$userFriendly = !self::isForwardCode($url);
		return
			$userFriendly
			? $url
			: base64_decode(substr($url, 1));
	}
	
	/**
	 * decides if a string is a forward code encoded with encodeForward
	 * @param string $url
	 * @return bool
	 */
	public static function isForwardCode($url){
		$pattern = '/^[.][a-zA-Z0-9+\/=]{4,}[=]{0,2}$/';
		return preg_match($pattern, $url);
	}
}

