<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

class Html //#TODO 1.1.0 move into lib
{
	public static function arrayToHtmlRow($array,$headerRow = false, $headerCells = array()) //#TODO #2.0.0 $headerCell support not implemented
	{ 
		$cellement = ($headerRow ? 'th' : 'td');	
		foreach ($array as $key=>$value) {
		    $array[$key] = htmlentities($value); 
		}
		return "<tr><{$cellement}>" . implode("</{$cellement}><{$cellement}>", $array) . "</{$cellement}></tr>"; 
	}
	
	public static function arrayToHtmlTable($array)
	{
		$string ='<table>';
		$first = true;
		foreach ($array as $line) {
			$string .= self::arrayToHtmlRow($line,$first) . "\n";
			$first = false;
		}
		return $string . '</table>';
	}
	
	public static function arrayToPreformatRaw($array)
	{
		$string = '<pre>';
		ob_start();
		print_r($array);
		$raw = ob_get_clean();
		ob_end_flush();
		$string .= htmlentities($raw);
		return $string . '</pre>';
	}
}