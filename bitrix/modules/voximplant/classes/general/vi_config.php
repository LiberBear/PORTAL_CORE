<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Voximplant as VI;

class CVoxImplantConfig
{
	const MODE_LINK = 'LINK';
	const MODE_RENT = 'RENT';
	const MODE_SIP = 'SIP';

	const INTERFACE_CHAT_ADD = 'ADD';
	const INTERFACE_CHAT_APPEND = 'APPEND';
	const INTERFACE_CHAT_NONE = 'NONE';

	const CRM_CREATE_NONE = 'none';
	const CRM_CREATE_LEAD = 'lead';

	const QUEUE_TYPE_EVENLY = 'evenly';
	const QUEUE_TYPE_STRICTLY = 'strictly';
	const QUEUE_TYPE_ALL = 'all';

	const LINK_BASE_NUMBER = 'LINK_BASE_NUMBER';
	const FORWARD_LINE_DEFAULT = 'default';

	const GET_BY_SEARCH_ID = 'SEARCH_ID';
	const GET_BY_ID = 'ID';

	public static function SetPortalNumber($number)
	{
		$numbers = self::GetPortalNumbers();
		if (!(isset($numbers[$number]) || $number == CVoxImplantConfig::LINK_BASE_NUMBER))
		{
			return false;
		}
		COption::SetOptionString("voximplant", "portal_number", $number);

		$viHttp = new CVoxImplantHttp();
		$viHttp->ClearConfigCache();

		return true;
	}

	public static function GetPortalNumber()
	{
		return COption::GetOptionString("voximplant", "portal_number");
	}

	public static function SetPortalNumberByConfigId($configId)
	{
		$configId = intval($configId);
		if ($configId <= 0)
			return false;

		$orm = VI\ConfigTable::getList(Array(
			'filter'=>Array(
				'=ID' => $configId
			)
		));
		$element = $orm->fetch();
		if (!$element)
			return false;

		COption::SetOptionString("voximplant", "portal_number", $element['SEARCH_ID']);

		return true;
	}

	public static function GetPortalNumbers()
	{
		$result = Array();

		$res = VI\ConfigTable::getList();
		while ($row = $res->fetch())
		{
			if ($row['SEARCH_ID'] == 'test')
				continue;

			if (strlen($row['PHONE_NAME']) <= 0)
			{
				$row['PHONE_NAME'] = substr($row['SEARCH_ID'], 0, 3) == 'reg'? GetMessage('VI_CONFIG_SIP_CLOUD_DEF'): GetMessage('VI_CONFIG_SIP_OFFICE_DEF');
				$row['PHONE_NAME'] = str_replace('#ID#', $row['ID'], $row['PHONE_NAME']);
			}
			$result[$row['SEARCH_ID']] = htmlspecialcharsbx($row['PHONE_NAME']);
		}

		$ViAccount = new CVoxImplantAccount();
		$accountLang = $ViAccount->GetAccountLang();

		if (!in_array($accountLang, Array('ua','kz')))
		{
			$linkNumber = CVoxImplantPhone::GetLinkNumber();
			$result['LINK_BASE_NUMBER'] = $linkNumber == ''? GetMessage('VI_CONFIG_LINK_DEF'): '+'.$linkNumber;
		}

		return $result;
	}

	public static function GetModeStatus($mode)
	{
		if (!in_array($mode, Array(self::MODE_LINK, self::MODE_RENT, self::MODE_SIP)))
			return false;

		if ($mode == self::MODE_SIP)
		{
			return COption::GetOptionString("main", "~PARAM_PHONE_SIP", 'N') == 'Y';
		}

		return COption::GetOptionString("voximplant", "mode_".strtolower($mode));
	}

	public static function SetModeStatus($mode, $enable)
	{
		if (!in_array($mode, Array(self::MODE_LINK, self::MODE_RENT, self::MODE_SIP)))
			return false;

		if ($mode == self::MODE_SIP)
		{
			COption::SetOptionString("main", "~PARAM_PHONE_SIP", $enable? 'Y': 'N');
		}
		else
		{
			COption::SetOptionString("voximplant", "mode_".strtolower($mode), $enable? true: false);
		}

		return true;
	}

	public static function GetChatAction()
	{
		return COption::GetOptionString("voximplant", "interface_chat_action");
	}

	public static function SetChatAction($action)
	{
		if (!in_array($action, Array(self::INTERFACE_CHAT_ADD, self::INTERFACE_CHAT_APPEND, self::INTERFACE_CHAT_NONE)))
			return false;

		COption::SetOptionString("voximplant", "interface_chat_action", $action);

		return true;
	}

	public static function GetLinkCallRecord()
	{
		return COption::GetOptionInt("voximplant", "link_call_record");
	}

	public static function SetLinkCallRecord($active)
	{
		$active = $active? true: false;

		return COption::SetOptionInt("voximplant", "link_call_record", $active);
	}

	public static function GetLinkCheckCrm()
	{
		return COption::GetOptionInt("voximplant", "link_check_crm");
	}

	public static function SetLinkCheckCrm($active)
	{
		$active = $active? true: false;

		return COption::SetOptionInt("voximplant", "link_check_crm", $active);
	}

	public static function GetDefaultMelodies($lang = 'EN')
	{
		if ($lang !== false)
		{
			$lang = strtoupper($lang);
			if ($lang == 'KZ')
			{
				$lang = 'RU';
			}
			else if (!in_array($lang, array('EN', 'DE', 'RU', 'UA')))
			{
				$lang = 'EN';
			}
		}
		else
		{
			$lang = '#LANG_ID#';
		}

		return array(
			"MELODY_WELCOME" => "http://dl.bitrix24.com/vi/".$lang."01.mp3",
			"MELODY_WAIT" => "http://dl.bitrix24.com/vi/MELODY.mp3",
			"MELODY_HOLD" => "http://dl.bitrix24.com/vi/MELODY.mp3",
			"MELODY_VOICEMAIL" => "http://dl.bitrix24.com/vi/".$lang."03.mp3",
			"MELODY_VOTE" => "http://dl.bitrix24.com/vi/".$lang."04.mp3",
			"MELODY_VOTE_END" => "http://dl.bitrix24.com/vi/".$lang."05.mp3",
			"MELODY_RECORDING" => "http://dl.bitrix24.com/vi/".$lang."06.mp3",
			"WORKTIME_DAYOFF_MELODY" => "http://dl.bitrix24.com/vi/".$lang."03.mp3",
		);
	}

	public static function GetMelody($name, $lang = 'EN', $fileId = 0)
	{
		$fileId = intval($fileId);

		$result = '';
		if ($fileId > 0)
		{
			$res = CFile::GetFileArray($fileId);
			if ($res && $res['MODULE_ID'] == 'voximplant')
			{
				if (substr($res['SRC'], 0, 4) == 'http' || substr($res['SRC'], 0, 2) == '//')
				{
					$result = $res['SRC'];
				}
				else
				{
					$result = CVoxImplantHttp::GetServerAddress().$res['SRC'];
				}
			}
		}

		if ($result == '')
		{
			$default = CVoxImplantConfig::GetDefaultMelodies($lang);
			$result = isset($default[$name])? $default[$name]: '';
		}

		return $result;
	}



	public static function GetConfigBySearchId($searchId)
	{
		return self::GetConfig($searchId, self::GET_BY_SEARCH_ID);
	}

	public static function GetConfig($id, $type = self::GET_BY_ID)
	{
		if (strlen($id) <= 0)
		{
			return Array('ERROR' => 'Config is`t found for undefined id/number');
		}
		if ($type == self::GET_BY_SEARCH_ID)
		{
			$orm = VI\ConfigTable::getList(Array(
				'filter'=>Array(
					'=SEARCH_ID' => (string)$id
				)
			));
		}
		else
		{
			$orm = VI\ConfigTable::getList(Array(
				'filter'=>Array(
					'=ID' => intval($id)
				)
			));
		}

		$config = $orm->fetch();
		if (!$config)
		{
			$result = Array(
				'ERROR' => $type == self::GET_BY_SEARCH_ID? 'Config is`t found for number: '.$id: 'Config is`t found for id: '.$id
			);
		}
		else
		{
			$result = $config;

			$result['PHONE_TITLE'] = $result['PHONE_NAME'];
			if ($result['PORTAL_MODE'] == self::MODE_SIP)
			{
				$viSip = new CVoxImplantSip();
				$sipResult = $viSip->Get($config["ID"]);

				$result['PHONE_NAME'] = preg_replace("/[^0-9\#\*]/i", "", $result['PHONE_NAME']);
				$result['PHONE_NAME'] = strlen($result['PHONE_NAME']) >= 4? $result['PHONE_NAME']: '';

				if($sipResult)
				{
					$result['SIP_SERVER'] = $sipResult['SERVER'];
					$result['SIP_LOGIN'] = $sipResult['LOGIN'];
					$result['SIP_PASSWORD'] = $sipResult['PASSWORD'];
					$result['SIP_TYPE'] = $sipResult['TYPE'];
					$result['SIP_REG_ID'] = $sipResult['REG_ID'];
				}
				else
				{
					$result['SIP_SERVER'] = '';
					$result['SIP_LOGIN'] = '';
					$result['SIP_PASSWORD'] = '';
					$result['SIP_TYPE'] = '';
					$result['SIP_REG_ID'] = '';
				}
			}

			if (strlen($result['FORWARD_LINE']) > 0 && $result['FORWARD_LINE'] != self::FORWARD_LINE_DEFAULT)
			{
				if ($result['FORWARD_LINE'] == CVoxImplantPhone::GetLinkNumber() || $result['FORWARD_LINE'] == CVoxImplantConfig::LINK_BASE_NUMBER)
				{
					$result['FORWARD_LINE_TYPE'] = 'LINK';
					if ($result['FORWARD_LINE_NUMBER'] == CVoxImplantConfig::LINK_BASE_NUMBER)
					{
						$result['FORWARD_LINE_NUMBER'] = '';
					}
					else
					{
						$result['FORWARD_LINE_NUMBER'] = CVoxImplantPhone::GetLinkNumber();
					}
				}
				else
				{
					$ormForward = VI\ConfigTable::getList(Array(
						'filter'=>Array(
							'=SEARCH_ID' => (string)$result['FORWARD_LINE']
						)
					));
					$configForward = $ormForward->fetch();
					CVoxImplantHistory::WriteToLog($configForward);
					if ($configForward)
					{
						$result['FORWARD_LINE_TYPE'] = $configForward['PORTAL_MODE'];
						if ($result['FORWARD_LINE_TYPE'] == self::MODE_SIP)
						{
							$viForwardSip = new CVoxImplantSip();
							$forwardSipResult = $viForwardSip->Get($configForward["ID"]);

							$result['FORWARD_LINE_SIP_SERVER'] = $forwardSipResult? $forwardSipResult['SERVER']: '';
							$result['FORWARD_LINE_SIP_LOGIN'] = $forwardSipResult? $forwardSipResult['LOGIN']: '';
							$result['FORWARD_LINE_SIP_PASSWORD'] = $forwardSipResult? $forwardSipResult['PASSWORD']: '';
						}
						else
						{
							$result['FORWARD_LINE_NUMBER'] = $configForward['SEARCH_ID'];
						}
					}
					else
					{
						$result['FORWARD_LINE'] = self::FORWARD_LINE_DEFAULT;
					}
				}
			}

			if (strlen($result['FORWARD_NUMBER']) > 0)
			{
				$result["FORWARD_NUMBER"] = NormalizePhone($result['FORWARD_NUMBER'], 1);
			}

			if (strlen($result['WORKTIME_DAYOFF_NUMBER']) > 0)
			{
				$result["WORKTIME_DAYOFF_NUMBER"] = NormalizePhone($result['WORKTIME_DAYOFF_NUMBER'], 1);
			}
			// check work time
			$result['WORKTIME_SKIP_CALL'] = 'N';
			if ($config['WORKTIME_ENABLE'] == 'Y')
			{
				$timezone = (!empty($config["WORKTIME_TIMEZONE"])) ? new DateTimeZone($config["WORKTIME_TIMEZONE"]) : null;
				$numberDate = new Bitrix\Main\Type\DateTime(null, null, $timezone);

				if (!empty($config['WORKTIME_DAYOFF']))
				{
					$daysOff = explode(",", $config['WORKTIME_DAYOFF']);

					$allWeekDays = array('MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 7);
					$currentWeekDay = $numberDate->format('N');
					foreach($daysOff as $day)
					{
						if ($currentWeekDay == $allWeekDays[$day])
						{
							$result['WORKTIME_SKIP_CALL'] = "Y";
						}
					}
				}
				if ($result['WORKTIME_SKIP_CALL'] !== "Y" && !empty($config['WORKTIME_HOLIDAYS']))
				{
					$holidays = explode(",", $config['WORKTIME_HOLIDAYS']);
					$currentDay = $numberDate->format('d.m');

					foreach($holidays as $holiday)
					{
						if ($currentDay == $holiday)
						{
							$result['WORKTIME_SKIP_CALL'] = "Y";
						}
					}
				}
				if ($result['WORKTIME_SKIP_CALL'] !== "Y" && !empty($config['WORKTIME_FROM']) && !empty($config['WORKTIME_TO']))
				{
					$currentTime = $numberDate->format('G.i');

					if (!($currentTime >= $config['WORKTIME_FROM'] && $currentTime <= $config['WORKTIME_TO']))
					{
						$result['WORKTIME_SKIP_CALL'] = "Y";
					}
				}

				if ($result['WORKTIME_SKIP_CALL'] === "Y")
				{
					$result['WORKTIME_DAYOFF_MELODY'] =  CVoxImplantConfig::GetMelody('WORKTIME_DAYOFF_MELODY', $config['MELODY_LANG'], $config['WORKTIME_DAYOFF_MELODY']);
				}
			}

			if (CVoxImplantHttp::GetPortalType() == CVoxImplantHttp::TYPE_BITRIX24)
			{
				$result['PORTAL_URL'] = CVoxImplantHttp::GetServerAddress().'/settings/info_receiver.php?b24_action=phone&b24_direct=y';
			}
			else
			{
				$result['PORTAL_URL'] = CVoxImplantHttp::GetServerAddress().'/services/telephony/info_receiver.php?b24_direct=y';
			}

			$result['PORTAL_SIGN'] = CVoxImplantHttp::GetPortalSign();
			$result['MELODY_WELCOME'] = CVoxImplantConfig::GetMelody('MELODY_WELCOME', $config['MELODY_LANG'], $config['MELODY_WELCOME']);
			$result['MELODY_VOICEMAIL'] =  CVoxImplantConfig::GetMelody('MELODY_VOICEMAIL', $config['MELODY_LANG'], $config['MELODY_VOICEMAIL']);
			$result['MELODY_HOLD'] =  CVoxImplantConfig::GetMelody('MELODY_HOLD', $config['MELODY_LANG'], $config['MELODY_HOLD']);
			$result['MELODY_WAIT'] =  CVoxImplantConfig::GetMelody('MELODY_WAIT', $config['MELODY_LANG'], $config['MELODY_WAIT']);
			$result['MELODY_RECORDING'] =  CVoxImplantConfig::GetMelody('MELODY_RECORDING', $config['MELODY_LANG'], $config['MELODY_RECORDING']);
			$result['MELODY_VOTE'] =  CVoxImplantConfig::GetMelody('MELODY_VOTE', $config['MELODY_LANG'], $config['MELODY_VOTE']);
			$result['MELODY_VOTE_END'] =  CVoxImplantConfig::GetMelody('MELODY_VOTE_END', $config['MELODY_LANG'], $config['MELODY_VOTE_END']);
		}

		return $result;
	}

	public static function AddConfigBySearchId($phone, $country = 'RU')
	{
		$melodyLang = 'EN';
		$country = strtoupper($country);
		if ($country == 'KZ')
		{
			$melodyLang = 'RU';
		}
		else if (in_array($country, Array('RU', 'UA', 'DE')))
		{
			$melodyLang = $country;
		}

		$arFields = Array(
			'SEARCH_ID' => $phone,
			'PHONE_NAME' => $phone,
			'MELODY_LANG' => $melodyLang,
		);

		$result = VI\ConfigTable::add($arFields);
		if ($result)
		{
			if (CVoxImplantConfig::GetPortalNumber() == CVoxImplantConfig::LINK_BASE_NUMBER)
			{
				CVoxImplantConfig::SetPortalNumber($arFields['SEARCH_ID']);
			}
		}

		CVoxImplantConfig::SetModeStatus(CVoxImplantConfig::MODE_RENT, true);

		return true;
	}

	public static function DeleteConfigBySearchId($searchId)
	{
		if (strlen($searchId) <= 0)
		{
			return Array('ERROR' => 'Config is`t found for undefined number');
		}

		$orm = VI\ConfigTable::getList(Array(
			'filter'=>Array(
				'=SEARCH_ID' => (string)$searchId
			)
		));
		$config = $orm->fetch();
		if (!$config)
		{
			$result = Array('ERROR' => 'Config is`t found for number: '.$searchId);
		}
		else
		{
			$orm = VI\QueueTable::getList(Array(
				'filter'=>Array(
					'=CONFIG_ID' => $config["ID"]
				)
			));
			while ($row = $orm->fetch())
			{
				VI\QueueTable::delete($row['ID']);
			}

			VI\ConfigTable::delete($config["ID"]);

			$viHttp = new CVoxImplantHttp();
			$viHttp->ClearConfigCache();

			$result = Array('RESULT'=> 'OK', 'ERROR' => '');
		}

		return $result;
	}

	public static function GetNoticeOldConfigOfficePbx()
	{
		$result = false;
		if (COption::GetOptionString("voximplant", "notice_old_config_office_pbx") == 'Y' && CVoxImplantMain::CheckAccess())
		{
			$result = true;
		}

		return $result;
	}

	public static function HideNoticeOldConfigOfficePbx()
	{
		$result = false;

		COption::SetOptionString("voximplant", "notice_old_config_office_pbx", 'N');

		return $result;
	}

}
