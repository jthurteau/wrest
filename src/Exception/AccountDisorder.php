<?php 

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Exception class when a user's account exists, but is not valid
 */

namespace Saf\Exception;

use Saf\Util\Diction;

class AccountDisorder extends Workflow
{
    public function getTitle()
    {
        return 'Assistance Is Required';
    }

    public function getAdditionalInfo()
    {
        return Diction::lookup('account-disorder')
            . parent::getAdditionalInfo();
    }
}