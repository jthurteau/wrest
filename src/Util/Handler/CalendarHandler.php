<?php //#TODO KNOWN BUGS

declare(strict_types=1);

namespace Saf\Util\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
// use Mezzio\Template\TemplateRendererInterface;
// use Mezzio\Plates\PlatesRenderer;

use Saf\Util\Time;
use Saf\Utils\Breadcrumb;
// use Saf\Exception\Redirect; 
use Saf\Psr\RequestHandler;
use Saf\Auth;
use Saf\Util\Model\CalendarModel;

class CalendarHandler extends RequestHandler implements RequestHandlerInterface
{

    protected $calendarModel = null;

    protected $accessList = [
        'open' => '*',
    ];

    public function setModel(string $className)
    {
        $this->calendarModel = $className;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        parent::prehandle($request);
        $resourceStack = self::getResourceStack($request);
        $function = 'index';

        $status = 200;
        $result = $this->handleFunction($function, $resourceStack, $request, $status);
        $result['calendarModel'] = $this->calendarModel ? new $this->calendarModel() : new CalendarModel();
        // $result += [
        //     'pageTitle' => 'Select a date',
        // ];
        $response = 
            self::successful($result)
            ? new HtmlResponse($this->template->render('saf::calendar', $result))
            : new HtmlResponse($this->template->render('error::error-message', $result + ['request' => $request]));
        return $response->withStatus($status);
    }

    public function indexProcess($resourceStack, $request, &$status)
    {
        Breadcrumb::set();
        $forward = $resourceStack;
        $date = array_shift($forward);
        if ('today' == $date) {
    		$timestamp = Time::time();
    	} else if (strlen($date) == 4) {
    		$year = (int)$date;
    		$timestamp = strtotime("{$year}-06-01");
    	} else if ( !Time::isTimeStamp($date)) {
    		$year = (int)substr($date, 0, 4);
    		$month =
				str_pad(
					(string)min(12, max(1, (int)substr($date, 5, 2))
				), 2, '0', STR_PAD_LEFT);
			$dayStart = strpos($date, '-', 6);
			$maxDay = (int)date('t', strtotime("{$year}-{$month}-01"));
    		$day =
				$dayStart && strlen($date) >= $dayStart + 2
				? 	str_pad(
						(string)min($maxDay, max(1, (int)substr($date, $dayStart + 1, 2))
					), 2, '0', STR_PAD_LEFT)
				: '01';
			$timestamp =
				strtotime("{$year}-{$month}-{$day}");
    	}
    	(isset($year) && $year) || ($year = date('Y',$timestamp));
    	(isset($month) && $month) || ($month = date('m',$timestamp));
    	(isset($day) && $day) || (date('d',$timestamp));
        return [
            'success' => true,
            'forward' => $forward,
			'returnLink' => $this->baseUri . implode('/', $forward),
            'date' => $date,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            //'pageTitle' => 'INDEX'
        ];
    }

//     public function indexAction()
//     {


// //Saf_Debug::outData(array($this->view->timestamp, $this->view->date, $this->view->year,$this->view->month,$this->view->day)); die;
// 		if (array_key_exists(0, $this->view->forwardResource)) {
// 			$where = Crumb::forStack($this->view->forwardResource);
// 			$this->view->calendarModel = $this->getCalendar($this->view->forwardResource);
// 		}
// 		if ('' != $where) {
// 			Saf_Layout_Location::pushCrumb(
// 				$where, '[[baseUrl]]' . implode('/', $this->view->forwardResource)
// 			);
// 		}
//     	Saf_Layout_Location::pushCrumb('Pick a date');
//     	if (
//     		$this->view->calendarModel 
//     		&& !$this->view->calendarModel->allowedDate($this->view->day, $this->view->month, $this->view->year)
//     	) {
//     		$minDate = $this->view->calendarModel->getMinDate();
//     		$this->view->year = substr($minDate,0,4);
//     		$this->view->month = substr($minDate,5,2);
//     		$this->view->day = substr($minDate,8,2);
//     		$this->view->timestamp = strtotime("{$this->view->year}-{$this->view->month}-{$this->view->day}");
//     	}
//     	$this->view->date = "{$this->view->year}-{$this->view->month}-{$this->view->day}";
//     }

	protected function getCalendar($stack, $forward)
	{
		if (!array_key_exists(0, $stack)) {
			return NULL;
		}
		switch($stack[0]) {
			case 'reservation':
				$username = Auth::getPluginProvidedUsername();
				$action =
					key_exists(1, $stack)
						? $forward[1]
						: NULL;
				$resource =
					'modify' == $action
					&& key_exists(2, $stack)
						? (int)$stack[2]
						: null;
				return new $this->calendarModel(array(
					'user' => $username,
					'action' => $action,
					'resource' => $resource
				));
				break;
			default:
				return null;
		}
	}

}
