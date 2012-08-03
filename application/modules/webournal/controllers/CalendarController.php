<?php
/**
 * Caldendar-Controller
 */
class webournal_CalendarController extends Core_View_PaginatorController
{
    const VERSION = 1;

    /**
     *
     * @var webournal_Service_Directories
     */
    private $_directories = null;

    public function init()
    {
        parent::init();
        $this->_directories = new webournal_Service_Directories();
    }

    public function indexAction()
    {
		$month = intval($this->_request->getParam('month', date('m')));
		$year = intval($this->_request->getParam('year', date('Y')));

		if($month<1 || $month>12)
		{
			$month = date('m');
		}

		if($year<=2000)
		{
			$year = date('y');
		}

		$time = mktime(0,0,0, $month, 1, $year);
		$month = date('m', $time);
		$year = date('Y', $time);
		
		$directories = $this->_directories->getDirectoriesByMonth($month, $year);

		$days = array();
		$daysOfMonth = date('t', $time);
		for($i=1; $i<=$daysOfMonth; $i++)
		{
			$days[$i] = array();
		}

		foreach($directories as $directory)
		{
			$days[$directory['dayofmonth']][] = $directory;
		}

		$this->view->calendar_month = $month;
		$this->view->calendar_year = $year;
		$this->view->calendar_yearspan = $this->_directories->getYearSpan();
		$this->view->calendar_days = $days;
    }

    public static function updater($version)
    {
        if($version<self::VERSION)
        {
            for($i=$version+1; $i<=self::VERSION; $i++)
            {
                $function = 'update' . $i;
                if(!self::$function())
                {
                    return $i-1;
                }
            }
        }

        return self::VERSION;
    }

    private static function update1()
    {
        Core()->ACL()->addDefaultPermissions('allow', 2, 'webournal_calendar_index');
		
		$entry = Core()->Menu()->findBy('label', 'CALENDAR');
		if(is_null($entry))
		{
			Core()->Menu()->addControllerEntry('CALENDAR', 'Calendar', 'index', 'calendar', 'webournal');
		}
		
        return true;
    }
}
