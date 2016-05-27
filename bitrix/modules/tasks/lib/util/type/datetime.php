<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 * 
 * Dont use getdate() here, it returns time according to the php timezone, which is incorrect in this use-case.
 * todo: make this class work not only with current user`s timezone, but any other user`s
 *
 * Correct time is identified by a pair of values: timestamp (or string) and timezone
 */

namespace Bitrix\Tasks\Util\Type;

final class DateTime extends \Bitrix\Main\Type\DateTime
{
	protected static $timeZoneEnabled = null;

	public static function getCurrentTimeString()
	{
		return (string) new self();
	}

	public function getMonthGmt($zeroBase = false)
	{
		$m = (int) gmdate("n", $this->getTimeStamp());

		return $zeroBase ? ($m - 1) : $m;
	}

	public function getDayGmt()
	{
		return (int) gmdate("j", $this->getTimeStamp());
	}

	public function getWeekDayGmt()
	{
		$day = (int) gmdate("w", $this->getTimeStamp());

		// $day will be one of:
		// Mo - 1, Tu - 2, We - 3, Th - 4, Fr - 5, Sa - 6, Su - 0

		return $day;
	}

	public function getYearGmt()
	{
		return (int) gmdate("Y", $this->getTimeStamp());
	}

	public function getHourGmt()
	{
		return intval(gmdate("H", $this->getTimeStamp()));
	}

	public function getMinuteGmt()
	{
		return (int) gmdate("i", $this->getTimeStamp());
	}

	public function getSecondGmt()
	{
		return (int) gmdate("s", $this->getTimeStamp());
	}

	/**
	 * $dateTime is treated as local datetime struct
	 */
	public static function createFromTimeStruct(array $dateTime, $monthZeroBase = false)
	{
		return static::createFromTimestamp(static::getTimeStampByStruct($dateTime, $monthZeroBase));
	}

	/**
	 * $dateTime is treated as GMT datetime struct
	 */
	public static function createFromTimeStructGmt(array $dateTime, $monthZeroBase = false)
	{
		return static::createFromTimestampGmt(static::getTimeStampByStruct($dateTime, $monthZeroBase));
	}

	protected static function getTimeStampByStruct(array $dateTime, $monthZeroBase)
	{
		return gmmktime(
			intval($dateTime['hours']), // 01 => 1
			intval($dateTime['minutes']), 
			intval($dateTime['seconds']), 
			$monthZeroBase ? intval($dateTime['mon']) + 1 : intval($dateTime['mon']),
			intval($dateTime['day']), 
			intval($dateTime['year'])
		);
	}

	// this works fine only with localtime
	public function getTimeStruct($monthZeroBase = false)
	{
		// dont use ParseDateTime() here, it does not understand AM\PM format

		//  $this->getTimeStamp() will return timestamp with not user offset, so as we work with
		// local time (php timezone + user time zone), we must add user offset manually
		$date = date("H:i:s:j:n:Y", $this->getTimeStamp() + \CTimeZone::GetOffset());
		list($hour, $minute, $second, $day, $month, $year) = explode(':', $date);

		return array(
			'HOUR' => 		intval($hour),
			'MINUTE' => 	intval($minute),
			'SECOND' => 	intval($second),
			'DAY' => 		intval($day),
			'MONTH' => 		$monthZeroBase ? (intval($month) - 1) : intval($month),
			'YEAR' => 		intval($year)
		);
	}

	public function getTimeStructGmt($monthZeroBase = false)
	{
		$date = date("H:i:s:j:n:Y", $this->getTimeStamp());
		list($hour, $minute, $second, $day, $month, $year) = explode(':', $date);

		return array(
			'HOUR' => 		intval($hour),
			'MINUTE' => 	intval($minute),
			'SECOND' => 	intval($second),
			'DAY' => 		intval($day),
			'MONTH' => 		$monthZeroBase ? (intval($month) - 1) : intval($month),
			'YEAR' => 		intval($year)
		);
	}

	/**
	 * $unix is treated as GMT time stamp, 
	 * e.g. 1445337000 is "Tue, 20 Oct 2015 10:30:00 GMT", but NOT "10/20/2015 12:30:00 PM GMT+2:00 DST"
	 */
	public static function createFromTimestampGmt($unix)
	{
		$obj = new static(ConvertTimeStamp(0, "FULL"), null, new \DateTimeZone("GMT"));
		$obj->value->setTimestamp($unix);

		return $obj;
	}

	/**
	 * $timeString is treated as local datetime string
	 */
	public static function createFromUserTime($timeString)
	{
		$time = \MakeTimeStamp($timeString) - \CTimeZone::GetOffset();
		return DateTime::createFromTimestamp($time);
	}

	/**
	 * $timeString treated as a GMT, i.e. with time zone offset equals to zero
	 */
	public static function createFromUserTimeGmt($timeString)
	{
		return new static($timeString, null, new \DateTimeZone("GMT"));
	}

	// unused?
	public static function createFromInstance(\Bitrix\Main\Type\DateTime $date)
	{
		return static::createFromTimestamp($date->getTimeStamp());
	}

	public function convertToGmt()
	{
		// assume $this is in localtime
		return static::createFromUserTimeGmt((string) $this);
	}

	public function convertToLocalTime()
	{
		// assume $this is in utc
		return new static($this->toStringGmt(), null, static::getDefaultTimeZone());
	}

	public function checkGT(DateTime $date, $strict = true)
	{
		if($strict)
		{
			return $this->getTimeStamp() > $date->getTimeStamp();
		}
		else
		{
			return $this->getTimeStamp() >= $date->getTimeStamp();
		}
	}

	public function checkLT(DateTime $date, $strict = true)
	{
		if($strict)
		{
			return $this->getTimeStamp() < $date->getTimeStamp();
		}
		else
		{
			return $this->getTimeStamp() <= $date->getTimeStamp();
		}
	}

	// this will work fine only on gmt...
	public function stripTime()
	{
		$unix = gmmktime(
			0,0,0,
			$this->getMonthGmt(),
			$this->getDayGmt(), 
			$this->getYearGmt()
		);
		$this->value->setTimestamp($unix);
	}

	public function addDay($offset)
	{
		$this->add(($offset < 0 ? '-' : '').abs($offset).' days');
	}

	public function addHour($offset)
	{
		$this->add(($offset < 0 ? '-' : '').'T'.abs($offset).'H');
	}

	public function addMinute($offset)
	{
		$this->add(($offset < 0 ? '-' : '').'T'.abs($offset).'M');
	}

	public function addSecond($offset)
	{
		$this->add(($offset < 0 ? '-' : '').'T'.abs($offset).'S');
	}

	public function toStringGmt()
	{
		static::disableTimeZone();
		$value = $this->toString();
		static::enableTimeZone();

		return $value;
	}

	/**
	 * This function is for debug purposes only
	 * 
	 * @access private
	 */
	public function getInfoGmt()
	{
		return $this->toStringGmt().' ('.$this->getTimeStamp().')';
	}

	public static function getDefaultTimeZone()
	{
		$time = new \DateTime();
		return $time->getTimezone();
	}

	private static function disableTimeZone()
	{
		static::$timeZoneEnabled = \CTimeZone::Enabled();
		\CTimeZone::Disable();
	}

	private static function enableTimeZone()
	{
		if(static::$timeZoneEnabled !== null && static::$timeZoneEnabled)
		{
			\CTimeZone::Enable();
			static::$timeZoneEnabled = null;
		}
	}
}