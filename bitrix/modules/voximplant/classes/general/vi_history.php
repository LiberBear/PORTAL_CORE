<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Voximplant as VI;
use Bitrix\Main\IO;

class CVoxImplantHistory
{
	public static function Add($params)
	{
		$call = false;
		if (strlen($params["CALL_ID"]) > 0)
		{
			$res = VI\CallTable::getList(Array(
				'select' => Array('ID', 'DATE_CREATE', 'CRM', 'CONFIG_ID', 'USER_ID', 'TRANSFER_USER_ID'),
				'filter' => Array('=CALL_ID' => $params["CALL_ID"]),
			));
			if ($call = $res->fetch())
			{
				VI\CallTable::delete($call['ID']);
			}
		}

		$arFields = array(
			"ACCOUNT_ID" =>			$params["ACCOUNT_ID"],
			"APPLICATION_ID" =>		$params["APPLICATION_ID"],
			"APPLICATION_NAME" =>	isset($params["APPLICATION_NAME"])?$params["APPLICATION_NAME"]: '-',
			"INCOMING" =>			$params["INCOMING"],
			"CALL_START_DATE" =>	$call? $call['DATE_CREATE']: new Bitrix\Main\Type\DateTime(),
			"CALL_DURATION" =>		isset($params["CALL_DURATION"])? $params["CALL_DURATION"]: $params["DURATION"],
			"CALL_STATUS" =>		$params["CALL_STATUS"],
			"CALL_FAILED_CODE" =>	$params["CALL_FAILED_CODE"],
			"CALL_FAILED_REASON" =>	$params["CALL_FAILED_REASON"],
			"COST" =>				$params["COST_FINAL"],
			"COST_CURRENCY" =>		$params["COST_CURRENCY"],
			"CALL_VOTE" =>			intval($params["CALL_VOTE"]),
			"CALL_ID" =>			$params["CALL_ID"],
			"CALL_CATEGORY" =>		$params["CALL_CATEGORY"],
		);

		if (intval($params["PORTAL_USER_ID"]) > 0)
			$arFields["PORTAL_USER_ID"] = intval($params["PORTAL_USER_ID"]);

		if (strlen($params["PHONE_NUMBER"]) > 0)
			$arFields["PHONE_NUMBER"] = $params["PHONE_NUMBER"];

		if (strlen($params["CALL_DIRECTION"]) > 0)
			$arFields["CALL_DIRECTION"] = $params["CALL_DIRECTION"];

		if (strlen($params["PORTAL_NUMBER"]) > 0)
			$arFields["PORTAL_NUMBER"] = $params["PORTAL_NUMBER"];

		if (strlen($params["ACCOUNT_SEARCH_ID"]) > 0)
			$arFields["PORTAL_NUMBER"] = $params["ACCOUNT_SEARCH_ID"];

		if (strlen($params["CALL_LOG"]) > 0)
			$arFields["CALL_LOG"] = $params["CALL_LOG"];

		$orm = Bitrix\VoxImplant\StatisticTable::add($arFields);
		if (!$orm)
			return false;

		if ($call && intval($arFields["PORTAL_USER_ID"]) <= 0)
		{
			$res = VI\QueueTable::getList(Array(
				'select' => Array('ID', 'USER_ID'),
				'order' => Array('LAST_ACTIVITY_DATE' => 'asc'),
				'filter' => Array('=CONFIG_ID' => $call['CONFIG_ID']),
				'limit' => 1
			));
			$queueUser = $res->fetch();
			if ($queueUser)
			{
				$arFields["PORTAL_USER_ID"] = $queueUser['USER_ID'];
			}
		}

		if (strlen($arFields["PHONE_NUMBER"]) > 0 && $arFields["PORTAL_USER_ID"] > 0 && $params["CALL_FAILED_CODE"] != 423)
		{
			$plusSymbol = strlen($arFields["PHONE_NUMBER"]) >= 10? '+': '';
			if ($arFields["INCOMING"] == CVoxImplantMain::CALL_OUTGOING)
			{
				if ($arFields['CALL_FAILED_CODE'] == '603-S')
				{
					$message = GetMessage('VI_OUT_CALL_DECLINE_SELF', Array('#NUMBER#' => $plusSymbol.$arFields["PHONE_NUMBER"]));
				}
				else if ($arFields['CALL_FAILED_CODE'] == 603)
				{
					$message = GetMessage('VI_OUT_CALL_DECLINE', Array('#NUMBER#' => $plusSymbol.$arFields["PHONE_NUMBER"]));
				}
				else if ($arFields['CALL_FAILED_CODE'] == 486)
				{
					$message = GetMessage('VI_OUT_CALL_BUSY', Array('#NUMBER#' => $plusSymbol.$arFields["PHONE_NUMBER"]));
				}
				else if ($arFields['CALL_FAILED_CODE'] == 480)
				{
					$message = GetMessage('VI_OUT_CALL_UNAVAILABLE', Array('#NUMBER#' => $plusSymbol.$arFields["PHONE_NUMBER"]));
				}
				else if ($arFields['CALL_FAILED_CODE'] == 404 || $arFields['CALL_FAILED_CODE'] == 484)
				{
					$message = GetMessage('VI_OUT_CALL_ERROR_NUMBER', Array('#NUMBER#' => $plusSymbol.$arFields["PHONE_NUMBER"]));
				}
				else if ($arFields['CALL_FAILED_CODE'] == 402)
				{
					$message = GetMessage('VI_OUT_CALL_NO_MONEY', Array('#NUMBER#' => $plusSymbol.$arFields["PHONE_NUMBER"]));
				}
				else
				{
					$message = GetMessage('VI_OUT_CALL_END', Array(
						'#NUMBER#' => $plusSymbol.$arFields["PHONE_NUMBER"],
						'#INFO#' => '[PCH='.$orm->getId().']'.GetMessage('VI_CALL_INFO').'[/PCH]',
					));
				}
			}
			else
			{
				if ($arFields['CALL_FAILED_CODE'] == 304)
				{
					if (strlen($params['URL']) > 0)
						$subMessage = GetMessage('VI_CALL_VOICEMAIL', Array('#LINK_START#' => '[PCH='.$orm->getId().']', '#LINK_END#' => '[/PCH]', ));
					else
						$subMessage = '[PCH='.$orm->getId().']'.GetMessage('VI_CALL_INFO').'[/PCH]';

					$message = GetMessage('VI_IN_CALL_SKIP', Array(
						'#NUMBER#' => $plusSymbol.$arFields["PHONE_NUMBER"],
						'#INFO#' => $subMessage,
					));
				}
				else
				{
					$message = GetMessage('VI_IN_CALL_END', Array(
						'#NUMBER#' => $plusSymbol.$arFields["PHONE_NUMBER"],
						'#INFO#' => '[PCH='.$orm->getId().']'.GetMessage('VI_CALL_INFO').'[/PCH]',
					));
				}
			}

			self::SendMessageToChat($arFields["PORTAL_USER_ID"], $arFields["PHONE_NUMBER"], $arFields["INCOMING"], $message);
		}

		if ($call['CRM'] == 'Y')
			CVoxImplantCrmHelper::UpdateCall($arFields);

		if (strlen($params['URL']) > 0)
		{
			$attachToCrm = $call['CRM'] == 'Y';

			$startDownloadAgent = false;

			$recordLimit = COption::GetOptionInt("voximplant", "record_limit");
			if ($recordLimit > 0 && !CVoxImplantAccount::IsPro())
			{
				$sipConnectorActive = CVoxImplantConfig::GetModeStatus(CVoxImplantConfig::MODE_SIP);
				if ($params['PORTAL_TYPE'] == CVoxImplantConfig::MODE_SIP && $sipConnectorActive)
				{
					$startDownloadAgent = true;
				}
				else
				{
					$recordMonth = COption::GetOptionInt("voximplant", "record_month");
					if (!$recordMonth)
					{
						$recordMonth = date('Ym');
						COption::SetOptionInt("voximplant", "record_month", $recordMonth);
					}
					$recordCount = CGlobalCounter::GetValue('vi_records', CGlobalCounter::ALL_SITES);
					if ($recordCount < $recordLimit)
					{
						CGlobalCounter::Increment('vi_records', CGlobalCounter::ALL_SITES, false);
						$startDownloadAgent = true;
					}
					else
					{
						if ($recordMonth < date('Ym'))
						{
							COption::SetOptionInt("voximplant", "record_month", date('Ym'));
							CGlobalCounter::Set('vi_records', 1, CGlobalCounter::ALL_SITES, false);
							CGlobalCounter::Set('vi_records_skipped', 0, CGlobalCounter::ALL_SITES, false);
							$startDownloadAgent = true;
						}
						else
						{
							CGlobalCounter::Increment('vi_records_skipped', CGlobalCounter::ALL_SITES, false);
						}
					}
					CVoxImplantHistory::WriteToLog(Array(
						'limit' => $recordLimit,
						'saved' => CGlobalCounter::GetValue('vi_records', CGlobalCounter::ALL_SITES),
						'skipped' => CGlobalCounter::GetValue('vi_records_skipped', CGlobalCounter::ALL_SITES),
						'save to portal' => $startDownloadAgent? 'Y':'N',
					), 'STATUS OF RECORD LIMIT');
				}
			}
			else
			{
				$startDownloadAgent = true;
			}

			if ($startDownloadAgent)
			{
				self::DownloadAgent($orm->getId(), $params['URL'], $attachToCrm);
			}
		}

		if (strlen($params["ACCOUNT_PAYED"]) > 0 && in_array($params["ACCOUNT_PAYED"], Array('Y', 'N')))
		{
			CVoxImplantAccount::SetPayedFlag($params["ACCOUNT_PAYED"]);
		}

		foreach(GetModuleEvents("voximplant", "onCallEnd", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, Array(Array(
				'CALL_ID' => $arFields['CALL_ID'],
				'CALL_TYPE' => $arFields['INCOMING'],
				'PHONE_NUMBER' => $arFields['PHONE_NUMBER'],
				'PORTAL_NUMBER' => $arFields['PORTAL_NUMBER'],
				'PORTAL_USER_ID' => $arFields['PORTAL_USER_ID'],
				'CALL_DURATION' => $arFields['CALL_DURATION'],
				'CALL_START_DATE' => $arFields['CALL_START_DATE'],
				'COST' => $arFields['COST'],
				'COST_CURRENCY' => $arFields['COST_CURRENCY'],
				'CALL_FAILED_CODE' => $arFields['CALL_FAILED_CODE'],
				'CALL_FAILED_REASON' => $arFields['CALL_FAILED_REASON'],
			)));
		}

		return true;
	}

	public static function DownloadAgent($historyID, $recordUrl, $attachToCrm = true)
	{
		$historyID = intval($historyID);
		if (strlen($recordUrl) <= 0 || $historyID <= 0)
		{
			return false;
		}

		$http = new \Bitrix\Main\Web\HttpClient();
		$http->query('GET', $recordUrl);
		if ($http->getStatus() != 200)
		{
			CAgent::AddAgent(
				"CVoxImplantHistory::DownloadAgent('{$historyID}','{$recordUrl}','{$attachToCrm}');",
				'voximplant', 'N', 30, '', 'Y', ConvertTimeStamp(time() + CTimeZone::GetOffset() + 30, 'FULL')
			);

			return false;
		}

		$history = VI\StatisticTable::getById($historyID);
		$arHistory = $history->fetch();

		try
		{
			$urlComponents = parse_url($recordUrl);
			if ($urlComponents && strlen($urlComponents["path"]) > 0)
				$tempPath = \CFile::GetTempName('', bx_basename($urlComponents["path"]));
			else
				$tempPath = \CFile::GetTempName('', bx_basename($recordUrl));

			IO\Directory::createDirectory(IO\Path::getDirectory($tempPath));

			$file = new IO\File($tempPath);
			$handler = $file->open("w+");
			if($handler === false)
			{
				self::WriteToLog('Error opening temporary file ' . $tempPath);
				return false;
			}

			$http->setOutputStream($handler);
			$http->getResult();
			$file->close();

			$recordFile = CFile::MakeFileArray($tempPath);
			if (is_array($recordFile) && $recordFile['size'] && $recordFile['size'] > 0)
			{
				$recordFile['MODULE_ID'] = 'voximplant';
				$fileID = CFile::SaveFile($recordFile, 'voximplant');
				if(is_int($fileID) && $fileID > 0)
				{
					$elementID = CVoxImplantDiskHelper::SaveFile(
						$arHistory,
						CFile::GetFileArray($fileID),
						CSite::GetDefSite()
					);
					$elementID = intval($elementID);
					if($attachToCrm && $elementID> 0)
					{
						CVoxImplantCrmHelper::AttachRecordToCall(Array(
							'CALL_ID' => $arHistory['CALL_ID'],
							'CALL_RECORD_ID' => $fileID,
							'CALL_WEBDAV_ID' => $elementID,
						));
					}
					VI\StatisticTable::update($historyID, Array('CALL_RECORD_ID' => $fileID, 'CALL_WEBDAV_ID' => $elementID));
				}
			}
		}
		catch (Exception $ex)
		{
			self::WriteToLog('Error caught during downloading record: ' . PHP_EOL . print_r($ex, true));
		}

		return false;
	}

	public static function GetForPopup($id)
	{
		$id = intval($id);
		if ($id <= 0)
			return false;

		$history = VI\StatisticTable::getById($id);
		$params = $history->fetch();
		if (!$params)
			return false;

		$params = self::PrepereData($params);

		$arResult = Array(
			'PORTAL_USER_ID' => $params['PORTAL_USER_ID'],
			'PHONE_NUMBER' => $params['PHONE_NUMBER'],
			'INCOMING_TEXT' => $params['INCOMING_TEXT'],
			'CALL_ICON' => $params['CALL_ICON'],
			'CALL_FAILED_CODE' => $params['CALL_FAILED_CODE'],
			'CALL_FAILED_REASON' => $params['CALL_FAILED_REASON'],
			'CALL_DURATION_TEXT' => $params['CALL_DURATION_TEXT'],
			'COST_TEXT' => $params['COST_TEXT'],
			'CALL_RECORD_HREF' => $params['CALL_RECORD_HREF'],
		);

		return $arResult;
	}

	public static function PrepereData($params)
	{
		if ($params["INCOMING"] == "N")
		{
			$params["INCOMING"] = CVoxImplantMain::CALL_OUTGOING;
		}
		else if ($params["INCOMING"] == "N")
		{
			$params["INCOMING"] = CVoxImplantMain::CALL_INCOMING;
		}
		if ($params["PHONE_NUMBER"] == "hidden")
		{
			$params["PHONE_NUMBER"] = GetMessage("IM_PHONE_NUMBER_HIDDEN");
		}

		$params["CALL_FAILED_REASON"] = in_array($params["CALL_FAILED_CODE"], array("200","304","603-S","603","404","486","484","503","480","402","423")) ? GetMessage("VI_STATUS_".$params["CALL_FAILED_CODE"]) : GetMessage("VI_STATUS_OTHER");

		if ($params["INCOMING"] == CVoxImplantMain::CALL_OUTGOING)
		{
			$params["INCOMING_TEXT"] = GetMessage("VI_OUTGOING");
			if ($params["CALL_FAILED_CODE"] == 200)
				$params["CALL_ICON"] = 'outgoing';
		}
		else if ($params["INCOMING"] == CVoxImplantMain::CALL_INCOMING)
		{
			$params["INCOMING_TEXT"] = GetMessage("VI_INCOMING");
			if ($params["CALL_FAILED_CODE"] == 200)
				$params["CALL_ICON"] = 'incoming';
		}
		else if ($params["INCOMING"] == CVoxImplantMain::CALL_INCOMING_REDIRECT)
		{
			$params["INCOMING_TEXT"] = GetMessage("VI_INCOMING_REDIRECT");
			if ($params["CALL_FAILED_CODE"] == 200)
				$params["CALL_ICON"] = 'incoming-redirect';
		}

		if ($params["CALL_FAILED_CODE"] == 304)
		{
			$params["CALL_ICON"] = 'skipped';
		}
		else if ($params["CALL_FAILED_CODE"] != 200)
		{
			$params["CALL_ICON"] = 'decline';
		}

		if ($params["CALL_DURATION"] > 60)
		{
			$formatTimeMin = floor($params["CALL_DURATION"]/60);
			$formatTimeSec = $params["CALL_DURATION"] - $formatTimeMin*60;
			$params["CALL_DURATION_TEXT"] = $formatTimeMin." ".GetMessage("VI_MIN");
			if ($formatTimeSec > 0)
				$params["CALL_DURATION_TEXT"] = $params["CALL_DURATION_TEXT"]." ".$formatTimeSec." ".GetMessage("VI_SEC");
		}
		else
		{
			$params["CALL_DURATION_TEXT"] = $params["CALL_DURATION"]." ".GetMessage("VI_SEC");
		}

		if (CModule::IncludeModule("catalog"))
		{
			$params["COST_TEXT"] = FormatCurrency($params["COST"], ($params["COST_CURRENCY"] == "RUR" ? "RUB" : $params["COST_CURRENCY"]));
		}
		else
		{
			$params["COST_TEXT"] = $params["COST"]." ".GetMessage("VI_CURRENCY_".$params["COST_CURRENCY"]);
		}

		if (!$params["COST_TEXT"])
		{
			$params["COST_TEXT"] = '-';
		}

		if (intval($params["CALL_RECORD_ID"]) > 0)
		{
			$recordFile = CFile::GetFileArray($params["CALL_RECORD_ID"]);
			if ($recordFile !== false)
			{
				$params["CALL_RECORD_HREF"] = $recordFile['SRC'];
			}
		}

		$params["CALL_WEBDAV_ID"] = (int)$params["CALL_WEBDAV_ID"];
		if($params["CALL_WEBDAV_ID"] > 0 && \Bitrix\Main\Loader::includeModule('disk'))
		{
			$fileId = $params["CALL_WEBDAV_ID"];
			$file = \Bitrix\Disk\File::loadById($fileId);
			if(!is_null($file))
				$params['CALL_RECORD_DOWNLOAD_URL'] = \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlForDownloadFile($file, true);
		}

		return $params;
	}

	public static function TransferMessage($userId, $transferUserId, $phoneNumber)
	{
		$userName = '';
		$arSelect = Array("ID", "LAST_NAME", "NAME", "LOGIN", "SECOND_NAME", "PERSONAL_GENDER");
		$dbUsers = CUser::GetList(($sort_by = false), ($dummy=''), array('ID' => $transferUserId), array('FIELDS' => $arSelect));
		if ($arUser = $dbUsers->Fetch())
			$userName = CUser::FormatName(CSite::GetNameFormat(false), $arUser, true, false);

		self::SendMessageToChat($userId, $phoneNumber, CVoxImplantMain::CALL_INCOMING_REDIRECT, GetMessage('VI_CALL_TRANSFER', Array('#USER#' => $userName)));

		return true;
	}

	public static function SendMessageToChat($userId, $phoneNumber, $incomingType, $message)
	{
		$ViMain = new CVoxImplantMain($userId);
		$dialogInfo = $ViMain->GetDialogInfo($phoneNumber, "", false);
		$ViMain->SendChatMessage($dialogInfo['DIALOG_ID'], $incomingType, $message);

		return true;
	}

	public static function WriteToLog($data, $title = '')
	{
		if (!COption::GetOptionInt("voximplant", "debug"))
			return false;

		if (is_array($data))
		{
			unset($data['HASH']);
			unset($data['BX_HASH']);
		}
		else if (is_object($data))
		{
			if ($data->HASH)
			{
				$data->HASH = '';
			}
			if ($data->BX_HASH)
			{
				$data->BX_HASH = '';
			}
		}
		$f=fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant.log", "a+t");
		$w=fwrite($f, "\n------------------------\n".date("Y.m.d G:i:s")."\n".(strlen($title)>0? $title: 'DEBUG')."\n".print_r($data, 1)."\n------------------------\n");
		fclose($f);

		return true;
	}
}