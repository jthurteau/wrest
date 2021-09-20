<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Db Connection Flask
 */

namespace Saf\Flask;

use Saf\Pdo\Db as PdoDb;
use Saf\Pdo\Exception as PdoException;

class Db {// implements FlaskInterface {

    public static function test(callable $sample, PdoDb $db) : string 
    {
        if (is_null($db)) {
            return 'Error, Not Configured';
        }
        \Saf\Debug::outData([__FILE__,__LINE__,$db->isConnected()]);
        try{
            $result = $sample();
            if (!$db->isConnected()) {
				return 'Error, ' . $db->getErrorMessage() . $result;
			} else {
                return $result; 
			}
		} catch (PdoException $e) {
			return 'PdoException, '
				. (
					$db->getErrorMessage()
					? $db->getErrorMessage()
					: $e->getMessage()
				);
		} catch (\Error | \Exception $e){
			return 'Exception, ' . $e->getMessage();
		}
    }
}