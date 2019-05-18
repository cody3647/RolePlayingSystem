<?php
/**
 * Class for getting and formatting the current date range of the game.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */
class RpsCurrentDate
{
	/**
	 * The instance of the class
	 * @var object
	 */
	private static $_instance = null;
	
	public $start_date;
	public $end_date;
	public $start_year;
	public $start_month;
	public $start_day;
	public $end_year;
	public $end_month;
	public $end_day;
	public $minyear;
	public $maxyear;
	public $span_year;
	public $span_months;
	public $diff;
	
	private function __construct()
	{
		global $modSettings;
		$this->start_date = new DateTime($modSettings['rps_current_start']);
		$this->end_date = new DateTime($modSettings['rps_current_end']);
		
		list($this->start_year, $this->start_month, $this->start_day) = array_map('intval', explode('-', $modSettings['rps_current_start']));
		list($this->end_year, $this->end_month, $this->end_day) = array_map('intval', explode('-', $modSettings['rps_current_end']));
		
		$this->span_year = $this->start_year != $this->end_year;
		$this->minyear = substr($modSettings['rps_begining'], 0, 4);
		$this->maxyear = $this->end_year + 5;
		
		$this->diff = $this->start_date->diff($this->end_date);
		//$this->getMonths();
	}
	
/*	private function getMonths()
	{
		$interval = DateInterval::createFromDateString('1 month');
		$period   = new DatePeriod($this->start_date, $interval, $this->end_date);

		foreach ($period as $dt) {
			$this->span_months[] = $dt->format('n');
		}
		if(end($this->span_months) != $this->end_date->format('n'))
			$this->span_months[] = $this->end_date->format('n');
	}
	*/
    public function __toString()
    {
		global $user_info;
		//$format = 'j F Y';
		$format = $user_info['datetime_format'];
		$end = $this->end_date->format($format);
		
		if(!$this->span_year)
			$format = $user_info['datetime_format_noyear'];
		$start = $this->start_date->format($format);
		
		return $start . '&ndash;' . $end;
    }
	
	public function between($strDate)
	{
		$day = substr($strDate,-2);
		if ($day == '00')
			return false;
			
		$date = new DateTime($strDate);
		return $date >= $this->start_date && $date <= $this->end_date;
	}
	
	/**
	 * Return the single instance of this class
	 * @return Debug
	 */
	public static function instance()
	{
		if (self::$_instance === null)
			self::$_instance = new RpsCurrentDate();

		return self::$_instance;
	}
}