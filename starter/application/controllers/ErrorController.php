<?php //#SCOPE_OS_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

class ErrorController extends Zend_Controller_Action
{

	/**
	 * This action handles
	 *	- Application errors
	 *	- Errors in the controller chain arising from missing
	 *	  controller classes and/or action methods
	 */
	public function errorAction()
	{
	 	$username = Saf_Auth::getPluginProvidedUsername();
	 	$isError = TRUE;
		$errors = $this->_getParam('error_handler');
		if (!$errors || !$errors instanceof ArrayObject) {
			$this->view->message = 'You have reached the error page';
			return;
		}
		$format = Saf_Layout::getFormat();
		if (!Saf_Layout::formatIsHtml()) {
			$this->_helper->layout->disableLayout(); //#TODO this should eventually not be needed
		}		
		switch ($errors->type) {
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				//$this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
				Saf_Status::set(Saf_Status::STATUS_404_NOTFOUND);
				$this->view->title = 'Requested Document Not Found';
				$isError = FALSE;
				//$priority = Zend_Log::NOTICE;
				break;
			default:
				//$this->getResponse()->setRawHeader('HTTP/1.1 500 Internal Server Error');
				if (is_a($errors->exception, 'Saf_Exception_Workflow')) {
					Saf_Status::set(Saf_Status::STATUS_400_BADREQUEST);
					$this->view->title = 'Assitance is required';
					$this->view->additionalError = $errors->exception->getAdditionalInfo();
					$isError = FALSE;
					break;
				}
				switch (get_class($errors->exception)) {					
					case 'Saf_Exception_Redirect':
						$style = $errors->exception->isKept()
						? '301 Moved Permanently'
						: (
								TRUE //#TODO #2.0.0 figure out when 302 is needed for old agents...
								? '303 See Other'
								: '302 Found'
						);
						$siteUrl = Zend_Registry::get('siteUrl');
						$subUrl = $errors->exception->getMessage();
						$url =
						strpos($subUrl, '://') !== FALSE
						? $subUrl
						: $siteUrl . $subUrl;
						$version = '1'; //#TODO #2.0.0 figure out when 303 is being used...
						$this->view->title = 'Redirecting';
						if (Saf_Debug::isEnabled()) {
							$this->view->additionalError = "Debug Mode Intercepting redirect to <a href=\"{$url}\">{$url}</a>";
						} else {
							if(!headers_sent()) {
								header("Location: {$url}");
								header("HTTP/1.{$version} {$style}");
							}
							$this->view->additionalError = "Redirecting. Please continue at <a href=\"{$url}\">{$url}</a>";
						}
						$isError = FALSE;
						break;
					case 'Saf_Exception_NoResource':
						Saf_Status::set(Saf_Status::STATUS_404_NOTFOUND);
						$this->view->title = 'Requested Document Not Found';
						$isError = FALSE;
						break;
					case 'Saf_Exception_NotAllowed':
						Saf_Status::set(Saf_Status::STATUS_403_FORBIDDEN);
						$this->view->title = 'Access Denied';
						break;
					case 'Saf_Exception_NotImplemented':
						Saf_Status::set(Saf_Status::STATUS_501_NOTIMPLEMENTED);
						$this->view->title = 'Operation Not Supported';
						break;	
					case 'Saf_Exception_BadGateway':
						Saf_Status::set(Saf_Status::STATUS_502_BADGATEWAY);
						//$priority = Zend_Log::CRIT;
						$this->view->title = 'Network Error';
						break;
					case 'Saf_Exception_Upstream':
						Saf_Status::set(Saf_Status::STATUS_502_BADGATEWAY);
						//$priority = Zend_Log::CRIT;
						$this->view->title = 'Service Error';
						break;
					case 'Saf_Exception_GatewayTimeout':
						Saf_Status::set(Saf_Status::STATUS_504_GATEWAYTIMEOUT);
						//$priority = Zend_Log::CRIT;
						$this->view->title = 'Service Error';
						break;
					default:
						Saf_Status::set(Saf_Status::STATUS_500_ERROR);
						//$priority = Zend_Log::CRIT;
						$this->view->title = 'Application Error';
						break;
				}
				break;
		}
		if ($isError) {
			if (Ems::userCanAdminSystem($username)) {
				Saf_Layout_Location::pushCrumb('System Status', '[[baseUrl]]api/');
			}
			Saf_Layout_Location::pushCrumb('An Error Occured');
		}
		$this->view->exception = $errors->exception;

	/*
	// Log exception, if logger available
		if ($log = $this->getLog()) {
			$log->log($this->view->message, $priority, $errors->exception);
			$log->log('Request Parameters', $priority, $errors->request->getParams());
		}

	$this->view->request   = $errors->request;
		 */
	}

	/*
	public function getLog()
	{
	 	$bootstrap = $this->getInvokeArg('bootstrap');
		if (!$bootstrap->hasResource('Log')) {
			return false;
		}
	}
	*/

	/**
	 * This action handles
	 *	- APPLICATION_STATUS = 'down', for maintenance mode.
	 */
	public function downAction()
	{
	 	Saf_Status::set(Saf_Status::STATUS_503_UNAVAILABLE);
		$this->view->mainHeader =  
			Zend_Registry::get('language')->get('applicationName', 'Application Name') . 'Unavailable';
	}
	
	public function badgatewayAction()
	{
		Saf_Status::set(Saf_Status::STATUS_502_BADGATEWAY);
		$this->view->mainHeader =
		Zend_Registry::get('language')->get('applicationName', 'Application Name') . 'Unavailable';
	}
}
