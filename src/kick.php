<?php

declare(strict_types=1);

use Saf\Kickstart;

require_once(dirname(__FILE__) . '/Kickstart.php');
Kickstart::kick(Kickstart::go());

// try {
// 	switch (Saf_Kickstart::go()) {
// 		case Saf_Kickstart::MODE_ZFMVC :
// 			$application = new Zend_Application(APPLICATION_ENV, APPLICATION_CONFIG);
// 			$application->bootstrap()->run();
// 			break;
// 		case Saf_Kickstart::MODE_SAF :
// 			$application = Saf_Application::load(APPLICATION_ENV, APPLICATION_CONFIG, TRUE);
// 			break;
// 		default :
// 	} 
// } catch (Exception $e) {
// 	Saf_Status::set(Saf_Status::STATUS_500_ERROR);
// 	Saf_Kickstart::exceptionDisplay($e);
// }
// Saf_Debug::dieSafe();