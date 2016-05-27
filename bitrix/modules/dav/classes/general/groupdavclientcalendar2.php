<?
define("DAV_CALDAV_DEBUG", false);

if (!class_exists("CDavGroupdavClientCalendar"))
{
	class CDavGroupdavClientCalendar
		extends CDavGroupdavClient
	{
		public function __construct($scheme, $server, $port, $userName, $userPassword, $siteId = null)
		{
			parent::__construct($scheme, $server, $port, $userName, $userPassword);
			$this->SetCurrentEncoding($siteId);
		}

		private function GetCalendarListByPath($path = '/')
		{
			$this->Connect();

			$xmlDoc = $this->Propfind(
				$path,
				array(
					array("calendar-home-set", "urn:ietf:params:xml:ns:caldav"),
					array("getctag", "http://calendarserver.org/ns/"),
					"displayname",
					array("calendar-description", "urn:ietf:params:xml:ns:caldav"),
					array("calendar-color", "http://apple.com/ns/ical/"),
					array("supported-calendar-component-set", "urn:ietf:params:xml:ns:caldav"),
					"resourcetype",
					"owner",
					"current-user-principal",
					"principal-URL",
				),
				null,
				1
			);

			$this->Disconnect();

			if (is_null($xmlDoc))
				return null;

			$arCalendars = array();
			$calendarHomeSet = null;
			$currentUserPrincipal = null;
			$principalUrl = null;

			$arResponse = $xmlDoc->GetPath("/*/response");

			foreach ($arResponse as $response)
			{
				$arResourceType = $response->GetPath("/response/propstat/prop/resourcetype/calendar");
				if (count($arResourceType) > 0)
				{
					$arHref = $response->GetPath("/response/href");
					if (count($arHref) > 0)
					{
						$arCalendar = array(
							"href" => urldecode($arHref[0]->GetContent()),
						);

						$arProps = $response->GetPath("/response/propstat/prop/*");
						foreach ($arProps as $prop)
						{
							$s = $prop->GetContent();
							if (is_string($s) || is_numeric($s))
								$arCalendar[$prop->GetTag()] = $this->Encode($s);
						}

						$arCalendars[] = $arCalendar;
					}
				}

				if (is_null($calendarHomeSet))
				{
					$arCalendarHomeSet = $response->GetPath("/response/propstat/prop/calendar-home-set/href");
					if (count($arCalendarHomeSet) > 0)
						$calendarHomeSet = urldecode($arCalendarHomeSet[0]->GetContent());
				}

				if (is_null($currentUserPrincipal))
				{
					$arCurrentUserPrincipal = $response->GetPath("/response/propstat/prop/current-user-principal/href");
					if (count($arCurrentUserPrincipal) > 0)
						$currentUserPrincipal = urldecode($arCurrentUserPrincipal[0]->GetContent());
				}

				if (is_null($principalUrl))
				{
					$arPrincipalUrl = $response->GetPath("/response/propstat/prop/principal-URL/href");
					if (count($arPrincipalUrl) > 0)
						$principalUrl = urldecode($arPrincipalUrl[0]->GetContent());
				}
			}

			if (count($arCalendars) > 0)
				return $arCalendars;

			if (!is_null($calendarHomeSet) && ($path != $calendarHomeSet))
				return $calendarHomeSet;
			if (!is_null($principalUrl) && ($path != $principalUrl))
				return $principalUrl;
			if (!is_null($currentUserPrincipal) && ($path != $currentUserPrincipal))
				return $currentUserPrincipal;

			return null;
		}

		public function GetCalendarList($path = '/')
		{
			$this->ClearErrors();

			$i = 0;
			do
			{
				$i++;

				$result = $this->GetCalendarListByPath($path);
				if (is_null($result) || is_array($result))
					return $result;

				$path = $result;
				//$path = str_replace("%40", "@", $result);
			}
			while ($i < 5);

			return null;
		}

		public function GetCalendarModificationLabel($path = '/')
		{
			$this->ClearErrors();

			$this->Connect();

			$xmlDoc = $this->Propfind(
				$path,
				array(
					array("getctag", "http://calendarserver.org/ns/")
				),
				null,
				0
			);

			$this->Disconnect();

			if (is_null($xmlDoc))
				return null;

			$getctag = null;

			$arPropstat = $xmlDoc->GetPath("/*/response/propstat");
			foreach ($arPropstat as $propstat)
			{
				$arStatus = $propstat->GetPath("/propstat/status");
				if (count($arStatus) > 0 && preg_match("#\s200\s+OK#i", $arStatus[0]->GetContent()))
				{
					$arGetCTag = $propstat->GetPath("/propstat/prop/getctag");
					if (count($arGetCTag) > 0)
						$getctag = $arGetCTag[0]->GetContent();
				}
			}

			return $getctag;
		}

		public function GetCalendarItemsList($path = '/', $arHrefs = null, $calendarData = false, $arFilter = array())
		{
			$this->ClearErrors();

			$this->Connect();

			if (!is_array($arHrefs))
				$arHrefs = array($arHrefs);

			$arHrefsNew = array();
			foreach ($arHrefs as $value)
			{
				if (!empty($value))
					$arHrefsNew[] = $value;
			}

			$arProperties = array(
				"getcontenttype",
				"resourcetype",
				"getetag",
			);
			if ($calendarData && (count($arHrefsNew) > 0))
				$arProperties[] = array("calendar-data", "urn:ietf:params:xml:ns:caldav");

			$arFilterNew = array();
			if (array_key_exists("start", $arFilter))
				$arFilterNew = array("time-range" => array("start" => ConvertDateTime($arFilter["start"], "YYYYMMDD\THHMISS\Z")));

			if (count($arHrefsNew) > 0)
			{
				$xmlDoc = $this->Report(
					$path,
					$arProperties,
					$arFilterNew,
					$arHrefsNew,
					1
				);
			}
			else
			{
				$xmlDoc = $this->Propfind(
					$path,
					$arProperties,
					$arFilterNew,
					1
				);
			}

			$this->Disconnect();

			if (is_null($xmlDoc))
				return null;

			$arItems = array();

			$arResponse = $xmlDoc->GetPath("/*/response");
			foreach ($arResponse as $response)
			{
				$arHref = $response->GetPath("/response/href");
				if (count($arHref) > 0)
				{
					$arItem = array(
						"href" => urldecode($arHref[0]->GetContent()),
					);

					$arProps = $response->GetPath("/response/propstat/prop/*");
					foreach ($arProps as $prop)
					{
						$s = $prop->GetContent();
						if (is_string($s) || is_numeric($s))
							$arItem[$prop->GetTag()] = $this->Encode($s);
					}

					if ($calendarData)
					{
						$arCalendarData = $response->GetPath("/response/propstat/prop/calendar-data");
						if (count($arCalendarData) > 0)
						{
							$cal = new CDavICalendar($this->Encode($arCalendarData[0]->GetContent()));
							$arEvents = $cal->GetComponents('VTIMEZONE', false);
							if (count($arEvents) > 0)
								$arItem["calendar-data"] = $this->ConvertICalToArray($arEvents[0], $cal);
						}
					}

					$arItems[] = $arItem;
				}
			}

			return $arItems;
		}

		private function ConvertICalToArray($event, $calendar)
		{
			static $arWeekDayMap = array("SU" => 6, "MO" => 0, "TU" => 1, "WE" => 2, "TH" => 3, "FR" => 4, "SA" => 5);

			$oneDay = 86400; //24 * 60 * 60
			$arFields = array(
				"NAME" => $event->GetPropertyValue("SUMMARY"),
				"VERSION" => $event->GetPropertyValue("SEQUENCE"),
				"PROPERTY_LOCATION" => $event->GetPropertyValue("LOCATION"),
				"DETAIL_TEXT" => $event->GetPropertyValue("DESCRIPTION"),
				"DETAIL_TEXT_TYPE" => 'text',
				"DATE_FROM" => CDavICalendarTimeZone::GetFormattedServerDateTime(
					$event->GetPropertyValue("DTSTART"),
					false,
					$calendar
				),
				"TZ_FROM" => $event->GetPropertyParameter("DTSTART", "TZID"),
				"DATE_TO" => CDavICalendarTimeZone::GetFormattedServerDateTime(
					$event->GetPropertyValue("DTEND"),
					false,
					$calendar
				),
				"TZ_TO" => $event->GetPropertyParameter("DTEND", "TZID"),
				"SKIP_TIME" => $event->GetPropertyParameter("DTSTART", "VALUE") == "DATE" && $event->GetPropertyParameter("DTEND", "VALUE") == "DATE",
				"XML_ID" => $event->GetPropertyValue("UID"),
				"DATE_CREATE" => CDavICalendarTimeZone::GetFormattedServerDateTime($event->GetPropertyValue("CREATED")),
				"PROPERTY_CATEGORY" => $event->GetPropertyValue("CATEGORIES"),
			);

			$arFields["DT_FROM_TS"] = MakeTimeStamp($arFields["ACTIVE_FROM"]);

			if ($priority = $event->GetPropertyValue("PRIORITY"))
			{
				if ($priority <= 3)
					$arFields["PROPERTY_IMPORTANCE"] = "high";
				elseif ($priority > 3 && $priority <= 6)
					$arFields["PROPERTY_IMPORTANCE"] = "normal";
				else
					$arFields["PROPERTY_IMPORTANCE"] = "low";
			}
			else
			{
				$arFields["PROPERTY_IMPORTANCE"] = "normal";
			}

			if ($transp = $event->GetPropertyValue("TRANSP"))
			{
				if ($transp == 'TRANSPARENT')
					$arFields["PROPERTY_ACCESSIBILITY"] = "free";
				else
					$arFields["PROPERTY_ACCESSIBILITY"] = "busy";
			}
			else
			{
				$arFields["PROPERTY_ACCESSIBILITY"] = "busy";
			}

			$arVAlarm = $event->GetComponents("VALARM");
			if (count($arVAlarm) > 0 && $event->GetPropertyValue("X-MOZ-LASTACK") == null)
			{
				$trigger = $arVAlarm[0]->GetPropertyValue("TRIGGER");
				if (preg_match('/^-PT([0-9]+)([HMD])$/i', $trigger, $arMatches))
				{
					$arPeriodMapTmp = array("M" => "min", "H" => "hour", "D" => "day");
					$arFields["PROPERTY_REMIND_SETTINGS"] = $arMatches[1]."_".$arPeriodMapTmp[$arMatches[2]];
				}
			}

			if ($rrule = $event->GetPropertyValueParsed("RRULE"))
			{
				// RRULE:FREQ=WEEKLY;COUNT=5;INTERVAL=2;BYDAY=TU,SA
				$arFields["PROPERTY_PERIOD_TYPE"] = $rrule["FREQ"];
				$arFields["PROPERTY_PERIOD_COUNT"] = isset($rrule["INTERVAL"]) ? $rrule["INTERVAL"] : 1;

				if ($arFields["PROPERTY_PERIOD_TYPE"] == "WEEKLY")
				{
					if (isset($rrule["BYDAY"]))
					{
						$ar = explode(",", $rrule["BYDAY"]);
						$ar1 = array();
						foreach ($ar as $v)
							$ar1[] = $arWeekDayMap[strtoupper($v)];
						$arFields["PROPERTY_PERIOD_ADDITIONAL"] = implode(",", $ar1);
					}
					else
					{
						$arFields["PROPERTY_PERIOD_ADDITIONAL"] = date("w", MakeTimeStamp($arFields["ACTIVE_FROM"])) - 1;
						if ($arFields["PROPERTY_PERIOD_ADDITIONAL"] < 0)
							$arFields["PROPERTY_PERIOD_ADDITIONAL"] = 6;
					}
				}

				$arFields["DT_LENGTH"] = MakeTimeStamp($arFields["ACTIVE_TO"]) - $arFields["DT_FROM_TS"];
				$arFields["PROPERTY_EVENT_LENGTH"] = $arFields["DT_LENGTH"];

				if (isset($rrule["UNTIL"]))
				{
					$arFields["PROPERTY_PERIOD_UNTIL"] = CDavICalendarTimeZone::GetFormattedServerDateTime($rrule["UNTIL"]);
				}
				elseif (isset($rrule["COUNT"]))
				{
					$eventTime = $this->GetPeriodicEventTime(
						MakeTimeStamp($arFields["ACTIVE_TO"]),
						array(
							"freq" => $arFields["PROPERTY_PERIOD_TYPE"],
							"interval" => $arFields["PROPERTY_PERIOD_COUNT"],
							"byday" => $arFields["PROPERTY_PERIOD_ADDITIONAL"]
						),
						$rrule["COUNT"]
					);
					$arFields["ACTIVE_TO"] = date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $eventTime);
				}
				else
				{
					$arFields["ACTIVE_TO"] = date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), mktime(0, 0, 0, 1, 1, 2038));
				}

				$arFields["DT_TO_TS"] = MakeTimeStamp($arFields["ACTIVE_TO"]);
			}
			else
			{
				$arFields["DT_TO_TS"] = MakeTimeStamp($arFields["ACTIVE_TO"]);
				if ($arFields['SKIP_TIME'])
					$arFields["DT_TO_TS"] -= $oneDay;
				$arFields["DT_LENGTH"] = $arFields["DT_TO_TS"] - $arFields["DT_FROM_TS"];
			}

			$exDatesVal = $event->GetProperties("EXDATE");
			if (count($exDatesVal) > 0)
			{
				$arFields["EXDATE"] = array();
				foreach ($exDatesVal as $val)
				{
					$arFields["EXDATE"][] = CDavICalendarTimeZone::GetFormattedServerDate($val->Value());
				}
			}

			return $arFields;
		}

		private function GetPeriodicEventTime($eventDate, $arParams, $number)
		{
			$number = intval($number);
			if ($number < 1)
				$number = 1;

			if (!isset($arParams["interval"]))
				$arParams["interval"] = 1;
			$arParams["interval"] = intval($arParams["interval"]);

			if (!isset($arParams["freq"]) || !in_array(strtoupper($arParams["freq"]), array('DAYLY', 'WEEKLY', 'MONTHLY', 'YEARLY')))
				$arParams["freq"] = "DAYLY";
			$arParams["freq"] = strtoupper($arParams["freq"]);

			if ($arParams["freq"] == 'WEEKLY')
			{
				if (isset($arParams["byday"]))
				{
					$arOld = explode(",", $arParams["byday"]);
					$arNew = array();
					foreach ($arOld as $v)
					{
						$v = trim($v);
						if (is_numeric($v))
						{
							$v = intval($v);
							if ($v >= 0 && $v < 7)
								$arNew[] = intval($v);
						}
					}
					if (count($arNew) > 0)
					{
						sort($arNew, SORT_NUMERIC);
						$arParams["byday"] = implode(",", $arNew);
					}
					else
					{
						unset($arParams["byday"]);
					}
				}

				if (!isset($arParams["byday"]))
				{
					$arParams["byday"] = date("w", $eventDate) - 1;
					if ($arParams["byday"] < 0)
						$arParams["byday"] = 6;
				}
			}

			$newEventDate = $eventDate;
			switch ($arParams["freq"])
			{
				case 'DAYLY':
					$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate) + $arParams["interval"] * ($number - 1), date("Y", $newEventDate));
					break;
				case 'WEEKLY':
					$newEventDateDay = date("w", $newEventDate) - 1;
					if ($newEventDateDay < 0)
						$newEventDateDay = 6;

					$bStartFound = false;
					$arDays = explode(",", $arParams["byday"]);
					foreach ($arDays as $day)
					{
						if ($day >= $newEventDateDay)
						{
							$bStartFound = true;
							if ($day > $newEventDateDay)
							{
								$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate) + ($day - $newEventDateDay), date("Y", $newEventDate));
								$newEventDateDay = $day;
							}
							break;
						}
					}
					if (!$bStartFound)
					{
						$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate) + (($arParams["interval"] - 1) * 7 + (6 - $newEventDateDay) + $arDays[0] + 1), date("Y", $newEventDate));
						$newEventDateDay = $arDays[0];
					}

					$d = $i = 0;
					$priorDay = $newEventDateDay;
					foreach ($arDays as $day)
					{
						if ($newEventDateDay >= $day)
							continue;

						$d += $day - $priorDay;
						$priorDay = $day;

						$i++;
						if ($i >= $number - 1)
							break;
					}

					while ($i < $number - 1)
					{
						$bFirst = true;
						foreach ($arDays as $day)
						{
							if ($bFirst)
								$d += ($arParams["interval"] - 1) * 7 + (6 - $priorDay) + $day + 1;
							else
								$d += $day - $priorDay;
							$bFirst = false;

							$priorDay = $day;

							$i++;
							if ($i >= $number - 1)
								break;
						}
					}
					$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate) + $d, date("Y", $newEventDate));
					break;
				case 'MONTHLY':
					$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate) + $arParams["interval"] * ($number - 1), date("d", $newEventDate), date("Y", $newEventDate));
					break;
				case 'YEARLY':
					$newEventDate = mktime(date("H", $newEventDate), date("i", $newEventDate), date("s", $newEventDate), date("m", $newEventDate), date("d", $newEventDate), date("Y", $newEventDate) + $arParams["interval"] * ($number - 1));
					break;
			}

			return $newEventDate;
		}

		public function PutCalendarItem($path = '/', $siteId = null, $arData = array())
		{
			if (!array_key_exists("DAV_XML_ID", $arData))
				$arData["DAV_XML_ID"] = self::GenerateNewCalendarItemName();

			if (substr($path, -strlen("/".$arData["DAV_XML_ID"].".ics")) != "/".$arData["DAV_XML_ID"].".ics")
			{
				$path = rtrim($path, "/");
				$path .= "/".$arData["DAV_XML_ID"].".ics";
			}

			$data = $this->GetICalContent($arData, $siteId);
			$result = $this->Put($path, $this->Decode($data));

			if ($result == 201 || $result == 204)
			{
				$result = $this->GetCalendarItemsList($path);
				if (is_array($result) && count($result) > 0)
					return array("XML_ID" => basename($result[0]["href"], ".ics"), "MODIFICATION_LABEL" => $result[0]["getetag"]);
			}

			return null;
		}

		public function DeleteCalendarItem($path)
		{
			return $this->Delete($path);
		}

		private function GetICalContent(array $event, $siteId)
		{
			$oneDay = 86400; //24*60*60
			$dateFrom = date("Ymd\\THis", MakeTimeStamp($event["DATE_FROM"]));
			$dateTo = date("Ymd\\THis", MakeTimeStamp($event["DATE_TO"]));
			$tzFrom = $event["TZ_FROM"];
			$tzTo = $event["TZ_TO"];

			$arICalEvent = array(
				"TYPE" => "VEVENT",
				"CREATED" => date("Ymd\\THis\\Z", MakeTimeStamp($event["DATE_CREATE"])),
				"LAST-MODIFIED" => date("Ymd\\THis\\Z", MakeTimeStamp($event["TIMESTAMP_X"])),
				"DTSTAMP" => date("Ymd\\THis\\Z", MakeTimeStamp($event["TIMESTAMP_X"])),
				"UID" => $event["DAV_XML_ID"],
				"SUMMARY" => $event["NAME"]
			);

			if ($event["DT_SKIP_TIME"] == 'Y')
			{
				$arICalEvent["DTSTART"] = date("Ymd", MakeTimeStamp($event["DATE_FROM"]));
				$arICalEvent["DTEND"] = date("Ymd", MakeTimeStamp($event["DATE_TO"]) + $oneDay);
			}
			else
			{
				$arICalEvent["DTSTART"] = array(
					"VALUE" => $dateFrom,
					"PARAMETERS" => array("TZID" => $tzFrom)
				);
				$arICalEvent["DTEND"] = array(
					"VALUE" => $dateTo,
					"PARAMETERS" => array("TZID" => $tzTo)
				);
			}

			if (isset($event["ACCESSIBILITY"]) && ($event["ACCESSIBILITY"] == 'free' || $event["ACCESSIBILITY"] == 'quest'))
				$arICalEvent["TRANSP"] = 'TRANSPARENT';
			else
				$arICalEvent["TRANSP"] = 'OPAQUE';

			if (isset($event["LOCATION"]) && is_array($event["LOCATION"]) && isset($event["LOCATION"]["NEW"]) && strlen($event["LOCATION"]["NEW"]) > 0)
				$arICalEvent["LOCATION"] = $event["LOCATION"]["NEW"];

			if (isset($event["IMPORTANCE"]))
			{
				if ($event["IMPORTANCE"] == "low")
					$arICalEvent["PRIORITY"] = 9;
				elseif ($event["IMPORTANCE"] == "high")
					$arICalEvent["PRIORITY"] = 1;
				else
					$arICalEvent["PRIORITY"] = 5;
			}

			if (isset($event["DESCRIPTION"]) && strlen($event["DESCRIPTION"]) > 0)
				$arICalEvent["DESCRIPTION"] = $event["DESCRIPTION"];

			if (isset($event["PROPERTY_REMIND_SETTINGS"]) && strlen($event["PROPERTY_REMIND_SETTINGS"]) > 0)
			{
				$arPeriodMapTmp = array("min" => "M", "hour" => "H", "day" => "D");
				$ar = explode("_", $event["PROPERTY_REMIND_SETTINGS"]);

				$arICalEvent["@VALARM"] = array(
					"TYPE" => "VALARM",
					"ACTION" => "DISPLAY",
					"TRIGGER" => array(
						"PARAMETERS" => array("VALUE" => "DURATION"),
						"VALUE" => "-PT".$ar[0].$arPeriodMapTmp[$ar[1]]
					)
				);
			}

			if (isset($event["RRULE"]) && is_array($event["RRULE"]))
			{
				$val = "FREQ=".$event["RRULE"]["FREQ"];
				if (isset($event["RRULE"]["INTERVAL"]) && $event["RRULE"]["INTERVAL"] !== '')
					$val .= ";INTERVAL=".$event["RRULE"]["INTERVAL"];
				if (isset($event["RRULE"]["BYDAY"]) && $event["RRULE"]["BYDAY"] !== '')
					$val .= ";BYDAY=".$event["RRULE"]["BYDAY"];

				if (isset($event["RRULE"]["UNTIL"]))
				{
					if ($event["RRULE"]["UNTIL"] != '' && intval($event["RRULE"]["UNTIL"]) == $event["RRULE"]["UNTIL"])
						$val .= ";UNTIL=".date("Ymd\\THis\\Z", $event["RRULE"]["UNTIL"]);
					else if($event["RRULE"]["UNTIL"] != '')
						$val .= ";UNTIL=".date("Ymd\\THis\\Z", MakeTimeStamp($event["RRULE"]["UNTIL"]));
				}
				else
				{
					$val .= ";UNTIL=".date("Ymd\\THis\\Z", $event['DATE_TO_TS_UTC'] + intVal(date("Z")));
				}

				$arICalEvent["RRULE"] = $val;
			}

			// TODO: we have to update SEQUENCE corresponding to rfc5546
			$arICalEvent["SEQUENCE"] = $event["VERSION"];
			$cal = new CDavICalendar($arICalEvent, $siteId);

			return $cal->Render();
		}

		public static function GenerateNewCalendarItemName()
		{
			return str_replace(".", "-", uniqid("BX-", true));
		}

		public static function InitUserEntity()
		{
			if (!CModule::IncludeModule("calendar"))
				return;

			//if (!defined("BX_NO_ACCELERATOR_RESET"))
			//	define("BX_NO_ACCELERATOR_RESET", true);
		}

		public static function DataSync($paramEntityType = null, $paramEntityId = 0)
		{
			if (DAV_CALDAV_DEBUG)
				CDav::WriteToLog("Starting CalDAV sync", "SYNCC");

			self::InitUserEntity();

			$maxNumber = 5;
			$index = 0;
			$bShouldClearCache = false;

			$paramEntityId = intval($paramEntityId);
			$arConnectionsFilter = array('ACCOUNT_TYPE' => array('caldav', 'caldav_google_oauth'));
			if (!is_null($paramEntityType) && ($paramEntityId > 0))
			{
				$arConnectionsFilter["ENTITY_TYPE"] = $paramEntityType;
				$arConnectionsFilter["ENTITY_ID"] = $paramEntityId;
			}

			$dbConnections = CDavConnection::GetList(
				array("SYNCHRONIZED" => "ASC"),
				$arConnectionsFilter,
				false,
				false,
				array('ID', 'ENTITY_TYPE', 'ENTITY_ID', 'ACCOUNT_TYPE', 'SERVER_SCHEME', 'SERVER_HOST', 'SERVER_PORT', 'SERVER_USERNAME', 'SERVER_PASSWORD', 'SERVER_PATH', 'SYNCHRONIZED')
			);
			while ($arConnection = $dbConnections->Fetch())
			{
				$index++;
				if ($index > $maxNumber)
					break;

				if (DAV_CALDAV_DEBUG)
					CDav::WriteToLog("Connection [".$arConnection["ID"]."] ".$arConnection["ENTITY_TYPE"]."/".$arConnection["ENTITY_ID"], "SYNCC");

				CDavConnection::SetLastResult($arConnection["ID"], "[0]");

				$client = new CDavGroupdavClientCalendar($arConnection["SERVER_SCHEME"], $arConnection["SERVER_HOST"], $arConnection["SERVER_PORT"], $arConnection["SERVER_USERNAME"], $arConnection["SERVER_PASSWORD"]);
				if (CDav::UseProxy())
				{
					$arProxy = CDav::GetProxySettings();
					$client->SetProxy($arProxy["PROXY_SCHEME"], $arProxy["PROXY_HOST"], $arProxy["PROXY_PORT"], $arProxy["PROXY_USERNAME"], $arProxy["PROXY_PASSWORD"]);
				}
				if ($arConnection['ACCOUNT_TYPE'] == 'caldav_google_oauth')
					$client->setGoogleCalendarOAuth($arConnection['ENTITY_ID']);

				//$client->Debug();

				if (!$client->CheckWebdavServer($arConnection["SERVER_PATH"]))
				{
					$t = '';
					$arErrors = $client->GetErrors();
					foreach ($arErrors as $arError)
					{
						if (strlen($t) > 0)
							$t .= ', ';
						$t .= '['.$arError[0].'] '.$arError[1];
					}

					CDavConnection::SetLastResult($arConnection["ID"], ((strlen($t) > 0) ? $t : "[404] Not Found"));
					if (DAV_CALDAV_DEBUG)
						CDav::WriteToLog("ERROR: ".$t, "SYNCC");
					continue;
				}

				$arCalendarsList = $client->GetCalendarList($arConnection["SERVER_PATH"]);
				if (count($arCalendarsList) <= 0)
				{
					CDavConnection::SetLastResult($arConnection["ID"], "[204] No Content");
					continue;
				}

				$arUserCalendars = array();
				foreach ($arCalendarsList as $value)
				{
					$arUserCalendars[] = array(
						"XML_ID" => $value["href"],
						"NAME" => $value["displayname"],
						"DESCRIPTION" => $value["calendar-description"],
						"COLOR" => $value["calendar-color"],
						"MODIFICATION_LABEL" => $value["getctag"],
					);
				}
				$tmpNumCals = count($arUserCalendars);
				$tmpNumItems = 0;

				$arUserCalendars = CCalendar::SyncCalendars("caldav", $arUserCalendars, $arConnection["ENTITY_TYPE"], $arConnection["ENTITY_ID"], $arConnection["ID"]);

				foreach ($arUserCalendars as $userCalendar)
				{
					$bShouldClearCache = true;
					$arCalendarItemsList = $client->GetCalendarItemsList($userCalendar["XML_ID"]);

					$arUserCalendarItems = array();
					if (is_array($arCalendarItemsList))
					{
						foreach ($arCalendarItemsList as $value)
						{
							if (strpos($value["getcontenttype"], "text/calendar") !== false
								&& strpos($value["getcontenttype"], "component=vevent") !== false
								&& isset($value["getetag"]))
							{
								$arUserCalendarItems[] = array(
									"XML_ID" => basename($value["href"], ".ics"),
									"MODIFICATION_LABEL" => $value["getetag"],
								);
							}
						}
					}

					$arUserCalendarItems = CCalendar::SyncCalendarItems("caldav", $userCalendar["CALENDAR_ID"], $arUserCalendarItems);
					$arHrefs = array();
					$arIdMap = array();
					foreach ($arUserCalendarItems as $value)
					{
						$h = $client->GetRequestEventPath($userCalendar["XML_ID"], $value["XML_ID"]);
						$arHrefs[] = $h;
						$arIdMap[$h] = $value["ID"];
					}

					$arCalendarItemsList = $client->GetCalendarItemsList($userCalendar["XML_ID"], $arHrefs, true);
					$tmpNumItems += count($arCalendarItemsList);

					foreach ($arCalendarItemsList as $value)
					{
						if (!array_key_exists($value["href"], $arIdMap))
							continue;

						$arModifyEventArray = array(
							"ID" => $arIdMap[$value["href"]],
							"NAME" => $value["calendar-data"]["NAME"],
							"DETAIL_TEXT" => $value["calendar-data"]["DETAIL_TEXT"],
							"DETAIL_TEXT_TYPE" => $value["calendar-data"]["DETAIL_TEXT_TYPE"],
							"XML_ID" => basename($value["href"], ".ics"),
							"PROPERTY_LOCATION" => $value["calendar-data"]["PROPERTY_LOCATION"],
							"DATE_FROM" => $value["calendar-data"]["DATE_FROM"],
							"DATE_TO" => $value["calendar-data"]["DATE_TO"],
							"TZ_FROM" => $value["calendar-data"]["TZ_FROM"],
							"TZ_TO" => $value["calendar-data"]["TZ_TO"],
							"DT_LENGTH" => $value["calendar-data"]["DT_LENGTH"],
							"SKIP_TIME" => $value["calendar-data"]["SKIP_TIME"],
							"PROPERTY_IMPORTANCE" => $value["calendar-data"]["PROPERTY_IMPORTANCE"],
							"PROPERTY_ACCESSIBILITY" => $value["calendar-data"]["PROPERTY_ACCESSIBILITY"],
							"PROPERTY_REMIND_SETTINGS" => $value["calendar-data"]["PROPERTY_REMIND_SETTINGS"],
							"PROPERTY_PERIOD_TYPE" => "NONE",
							"PROPERTY_BXDAVCD_LABEL" => $value["getetag"],
							"VERSION" => $value["calendar-data"]["VERSION"]
						);

						if (isset($value["calendar-data"]["PROPERTY_PERIOD_TYPE"]) && $value["calendar-data"]["PROPERTY_PERIOD_TYPE"] != "NONE")
						{
							$arModifyEventArray["PROPERTY_PERIOD_TYPE"] = $value["calendar-data"]["PROPERTY_PERIOD_TYPE"];
							$arModifyEventArray["PROPERTY_PERIOD_COUNT"] = $value["calendar-data"]["PROPERTY_PERIOD_COUNT"];
							$arModifyEventArray["PROPERTY_PERIOD_ADDITIONAL"] = $value["calendar-data"]["PROPERTY_PERIOD_ADDITIONAL"];
							$arModifyEventArray["PROPERTY_EVENT_LENGTH"] = $value["calendar-data"]["PROPERTY_EVENT_LENGTH"];
							$arModifyEventArray["PROPERTY_PERIOD_UNTIL"] = $value["calendar-data"]["PROPERTY_PERIOD_UNTIL"];
							$arModifyEventArray["EXDATE"] = $value["calendar-data"]["EXDATE"];
						}
						$k = CCalendar::ModifyEvent($userCalendar["CALENDAR_ID"], $arModifyEventArray);
					}
				}

				if (DAV_CALDAV_DEBUG)
					CDav::WriteToLog("Sync ".intval($tmpNumCals)." calendars, ".intval($tmpNumItems)." items", "SYNCC");

				CDavConnection::SetLastResult($arConnection["ID"], "[200] OK");
			}

			if ($bShouldClearCache)
				CCalendar::SyncClearCache();

			if (DAV_CALDAV_DEBUG)
				CDav::WriteToLog("CalDAV sync finished", "SYNCC");

			return "CDavGroupdavClientCalendar::DataSync();";
		}

		public static function DoAddItem($connectionId, $calendarXmlId, $arFields)
		{
			if (DAV_CALDAV_DEBUG)
				CDav::WriteToLog("CalDAV DoAddItem called for connection ".$connectionId, "MDFC");

			$connectionId = intval($connectionId);
			if ($connectionId <= 0)
				return null;

			$arConnection = CDavConnection::GetById($connectionId);
			if (!is_array($arConnection))
				return null;

			$client = new CDavGroupdavClientCalendar($arConnection["SERVER_SCHEME"], $arConnection["SERVER_HOST"], $arConnection["SERVER_PORT"], $arConnection["SERVER_USERNAME"], $arConnection["SERVER_PASSWORD"]);
			if (CDav::UseProxy())
			{
				$arProxy = CDav::GetProxySettings();
				$client->SetProxy($arProxy["PROXY_SCHEME"], $arProxy["PROXY_HOST"], $arProxy["PROXY_PORT"], $arProxy["PROXY_USERNAME"], $arProxy["PROXY_PASSWORD"]);
			}
			if ($arConnection['ACCOUNT_TYPE'] == 'caldav_google_oauth')
				$client->setGoogleCalendarOAuth($arConnection['ENTITY_ID']);

			//$client->Debug();
			self::InitUserEntity();

			$result = $client->PutCalendarItem($calendarXmlId, SITE_ID, $arFields);

			if (!is_null($result))
				return $result;

			return $client->GetErrors();
		}

		public static function DoUpdateItem($connectionId, $calendarXmlId, $itemXmlId, $itemModificationLabel, $arFields)
		{
			if (DAV_CALDAV_DEBUG)
				CDav::WriteToLog("CalDAV DoUpdateItem called for connection ".$connectionId, "MDFC");

			$connectionId = intval($connectionId);
			if ($connectionId <= 0)
				return null;

			$arConnection = CDavConnection::GetById($connectionId);
			if (!is_array($arConnection))
				return null;

			$client = new CDavGroupdavClientCalendar($arConnection["SERVER_SCHEME"], $arConnection["SERVER_HOST"], $arConnection["SERVER_PORT"], $arConnection["SERVER_USERNAME"], $arConnection["SERVER_PASSWORD"]);
			if (CDav::UseProxy())
			{
				$arProxy = CDav::GetProxySettings();
				$client->SetProxy($arProxy["PROXY_SCHEME"], $arProxy["PROXY_HOST"], $arProxy["PROXY_PORT"], $arProxy["PROXY_USERNAME"], $arProxy["PROXY_PASSWORD"]);
			}
			if ($arConnection['ACCOUNT_TYPE'] == 'caldav_google_oauth')
				$client->setGoogleCalendarOAuth($arConnection['ENTITY_ID']);

			//$client->Debug();
			self::InitUserEntity();

			$arFields["XML_ID"] = $itemXmlId;
			$result = $client->PutCalendarItem($client->GetRequestEventPath($calendarXmlId, $itemXmlId), SITE_ID, $arFields);

			if (!is_null($result))
				return $result;

			return $client->GetErrors();
		}

		public static function DoDeleteItem($connectionId, $calendarXmlId, $itemXmlId)
		{
			if (DAV_CALDAV_DEBUG)
				CDav::WriteToLog("CalDAV DoDeleteItem called for connection ".$connectionId, "MDFC");

			$connectionId = intval($connectionId);
			if ($connectionId <= 0)
				return null;

			$arConnection = CDavConnection::GetById($connectionId);
			if (!is_array($arConnection))
				return null;

			$client = new CDavGroupdavClientCalendar($arConnection["SERVER_SCHEME"], $arConnection["SERVER_HOST"], $arConnection["SERVER_PORT"], $arConnection["SERVER_USERNAME"], $arConnection["SERVER_PASSWORD"]);
			if (CDav::UseProxy())
			{
				$arProxy = CDav::GetProxySettings();
				$client->SetProxy($arProxy["PROXY_SCHEME"], $arProxy["PROXY_HOST"], $arProxy["PROXY_PORT"], $arProxy["PROXY_USERNAME"], $arProxy["PROXY_PASSWORD"]);
			}
			if ($arConnection['ACCOUNT_TYPE'] == 'caldav_google_oauth')
				$client->setGoogleCalendarOAuth($arConnection['ENTITY_ID']);

			//$client->Debug();

			self::InitUserEntity();

			$result = $client->DeleteCalendarItem($client->GetRequestEventPath($calendarXmlId, $itemXmlId));
			if ($result === true)
				return $result;

			return $client->GetErrors();
		}

		public static function DoAddCalendar($connectionId, $arFields)
		{
			return array(array(501, "Not Implemented"));
		}

		public static function DoUpdateCalendar($connectionId, $itemXmlId, $itemModificationLabel, $arFields)
		{
			return array(array(501, "Not Implemented"));
		}

		public static function DoDeleteCalendar($connectionId, $itemXmlId)
		{
			return array(array(501, "Not Implemented"));
		}

		public static function IsCalDAVEnabled()
		{
			$agentCalendar = COption::GetOptionString("dav", "agent_calendar_caldav", "N");
			return ($agentCalendar == "Y");
		}

		public static function DoCheckCalDAVServer($scheme, $host = null, $port = null, $username = null, $password = null, $path = null, $oauth = null)
		{
			if ($scheme."!" == intval($scheme)."!")
			{
				$scheme = intval($scheme);
				if ($scheme <= 0)
					return false;

				$arConnection = CDavConnection::GetById($scheme);
				if (!is_array($arConnection))
					return false;

				$scheme = $arConnection["SERVER_SCHEME"];
				$host = $arConnection["SERVER_HOST"];
				$port = $arConnection["SERVER_PORT"];
				$username = $arConnection["SERVER_USERNAME"];
				$password = $arConnection["SERVER_PASSWORD"];
				$path = $arConnection["SERVER_PATH"];

				if ($arConnection['ACCOUNT_TYPE'] == 'caldav_google_oauth')
					$oauth = array('type' => 'google', 'id' => $arConnection['ENTITY_ID']);
			}

			$client = new CDavGroupdavClientCalendar($scheme, $host, $port, $username, $password);
			if (CDav::UseProxy())
			{
				$arProxy = CDav::GetProxySettings();
				$client->SetProxy($arProxy["PROXY_SCHEME"], $arProxy["PROXY_HOST"], $arProxy["PROXY_PORT"], $arProxy["PROXY_USERNAME"], $arProxy["PROXY_PASSWORD"]);
			}
			if (!empty($oauth['type']) && $oauth['type'] == 'google')
				$client->setGoogleCalendarOAuth($oauth['id']);

			return $client->CheckWebdavServer($path);
		}

		public function GetRequestEventPath($calendarXmlId = '', $itemXmlId = '')
		{
			return rtrim($calendarXmlId, '/').'/'.$itemXmlId.".ics";
		}

		public function setGoogleCalendarOAuth($id)
		{
			CModule::includeModule('socialservices');

			$googleOAuthClient = new CSocServGoogleOAuth($id);
			$googleOAuthClient->getUrl('modal', array(
				'https://www.googleapis.com/auth/calendar',
				'https://www.googleapis.com/auth/calendar.readonly'
			));
			if ($token = $googleOAuthClient->getStorageToken())
				$this->setGoogleOAuth($token);
		}
	}
}
?>