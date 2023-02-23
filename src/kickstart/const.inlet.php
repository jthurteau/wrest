<?php
/**
 * Inlet for setting Contant Values into the Executable Environment
 * 
 * PHP version 7 or 8
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * @link saf.src:kickstart/tools/const.inlet.php
 * @link install:kickstart/tools/const.inlet.php
 * @license https://github.com/jthurteau/saf/blob/main/LICENSE GNU General Public License v3.0
 */

declare(strict_types=1);

return function ($data, $canister) {
    if(is_array($data) || ($data instanceof ArrayAccess)) {
        foreach($data as $const => $value) {
            defined($const) || define($const, $value);
        }
    }
    //#TODO return list of set constants?
};