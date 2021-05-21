<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Exception when an upstream service does not respond at all
 */

namespace Saf\Exception;

class Inspectable extends \Exception {
    
    public function __construct()
    {
        $data = func_get_args();
        $message = self::inspect(...$data);
        parent::__construct($message, 0, null);
    }

    public static function inspect()
    {
        $data = func_get_args();
        $output = '';
        ob_start();
        print('<pre>');
        print_r($data);
        print('</pre>');
        $output = ob_get_clean();
        return $output;
    }

}