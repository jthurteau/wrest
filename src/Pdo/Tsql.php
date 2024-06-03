<?php

namespace Pdo\Tsql;

class Tsql
{

    /**
     * @param $string
     * @return string
     * utility function to escape DB strings in "MSSQL" (TSQL)
     */
    public static function mssqlEscapeString(string $string): string
    {
        if (is_null($string) || '' === trim($string)) {
            return "''";
        }
        if (is_numeric($string)) {
            return $string;
        }
        /*
        $unpack = unpack('H*hex',$string);
        return '0x' . $unpack['hex'];
        */
        $rejectables = array(
            '/%0[0-8bcef]/',
            '/%1[0-9a-f]/',
            '/[\x00-\x08]/',
            '/\x0b/',
            '/\x0c/',
            '/[\x0e-\x1f]/'
        );
        foreach ($rejectables as $regex) {
            $string = preg_replace($regex, '', $string);
            $string = str_replace("'", "''", $string);
            return "'{$string}'";
        }
    }

}