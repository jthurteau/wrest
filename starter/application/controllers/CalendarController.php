<?php //#SCOPE_NCSU_PUBLIC
/*******************************************************************************
 #LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

*******************************************************************************/

class CalendarController extends Saf_Controller_Zend
{

    public function indexAction()
    { //#TODO #1.0.0 calendar for static
    	$this->_helper->viewRenderer('index');
    	$this->view->forwardResource = $this->getRequest()->getParam('resourceStack');
    	$this->view->date = array_shift($this->view->forwardResource);
    	if (strlen($this->view->date) < 4) {
    		$this->view->date = Saf_Time::time();
    	} 
    	if (strlen($this->view->date) == 4) {
    		$this->view->year = (int)$this->view->date;
    		$this->view->date = NULL;
    	} else if ( !Saf_Time::isTimeStamp($this->view->date) && strlen($this->view->date) == 7) {
    		$this->view->year = (int)substr($this->view->date, 0, 4);
    		$this->view->month = (int)substr($this->view->date, 5, 2);
    		$this->view->date = NULL;
    	} else {
    		$this->view->date = strtotime($this->view->date);
    	}
    	if (!$this->view->year) {
    		$this->view->year = date('Y',$this->view->date);
    	}
    	if (!$this->view->month) {
    		$this->view->month = date('m',$this->view->date);
    	}
    	Saf_Layout_Location::pushCrumb( //#TODO #1.1.0 map values for typical routes
    		'Where You Came From', '[[baseUrl]]' . implode('/', $this->view->forwardResource)
    	);
    	Saf_Layout_Location::pushCrumb('Pick a date');
    }
    
    public function __call($name, $args)
    {
    	return $this->indexAction();
    }
}
