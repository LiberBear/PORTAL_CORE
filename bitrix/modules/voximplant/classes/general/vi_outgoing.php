<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Type as FieldType;
use Bitrix\Main\Entity\Query;
use Bitrix\Voximplant as VI;

class CVoxImplantOutgoing
{
	const TTS_VOICE_DEFAULT = 'RU_RUSSIAN_MALE';
	const TTS_SPEED_DEFAULT = 'medium';
	const TTS_VOLUME_DEFAULT = 'medium';

	public static function Init($params)
	{
		$result['STATUS'] = 'OK';
		$result['PORTAL_CALL'] = 'N';

		if (strlen($params['PHONE_NUMBER']) > 0 && strlen($params['PHONE_NUMBER']) <= 4)
		{
			$res = CVoxImplantUser::GetList(Array(
				'select' => Array('ID', 'IS_ONLINE_CUSTOM', 'UF_VI_PHONE', 'ACTIVE'),
				'filter' => Array('=UF_PHONE_INNER' => intval($params['PHONE_NUMBER']), '=ACTIVE' => 'Y'),
			));
			if ($userData = $res->fetch())
			{
				$result['PORTAL_CALL'] = 'Y';
				$result['USER_ID'] = $userData['ID'];
				$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;

				if (CModule::IncludeModule('pull'))
				{
					$orm = \Bitrix\Pull\PushTable::getList(Array(
						'select' => Array('ID'),
						'filter' => Array('=USER_ID' => $userData['ID']),
					));
					$userData['USER_HAVE_MOBILE'] = $orm->fetch()? 'Y': 'N';
				}
				else
				{
					$userData['USER_HAVE_MOBILE'] = 'N';
				}

				if ($userData['ID'] == $params['USER_ID'])
				{
					$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
				}
				else if ($userData['IS_ONLINE_CUSTOM'] == 'Y' || $userData['UF_VI_PHONE'] == 'Y' || $userData['USER_HAVE_MOBILE'] == 'Y')
				{
					$result['COMMAND'] = CVoxImplantIncoming::RULE_WAIT;
					$result['TYPE_CONNECT'] = CVoxImplantIncoming::TYPE_CONNECT_DIRECT;
					$result['USER_HAVE_PHONE'] = $userData['UF_VI_PHONE'] == 'Y'? 'Y': 'N';
					$result['USER_HAVE_MOBILE'] = $userData['USER_HAVE_MOBILE'];
					$result['USER_SHORT_NAME'] = '';
				}
			}
		}

		$callAdd = true;
		if ($params['CALL_ID_TMP'])
		{
			$res = VI\CallTable::getList(Array(
				'filter' => Array('=CALL_ID' => $params['CALL_ID_TMP']),
			));
			if ($call = $res->fetch())
			{
				$res = VI\CallTable::update($call['ID'], Array(
					'CONFIG_ID' => $params['CONFIG_ID'],
					'CALL_ID' => $params['CALL_ID'],
					'CRM' => $params['CRM'],
					'USER_ID' => $params['USER_ID'],
					'CALLER_ID' => $params['PHONE_NUMBER'],
					'STATUS' => VI\CallTable::STATUS_CONNECTING,
					'ACCESS_URL' => $params['ACCESS_URL'],
					'PORTAL_USER_ID' => $result['PORTAL_CALL'] == 'Y'? $result['USER_ID']: 0,
				));
				if ($res)
				{
					$callAdd = false;
				}
			}
		}
		if ($callAdd)
		{
			VI\CallTable::add(Array(
				'CONFIG_ID' => $params['CONFIG_ID'],
				'CALL_ID' => $params['CALL_ID'],
				'CRM' => $params['CRM'],
				'USER_ID' => $params['USER_ID'],
				'CALLER_ID' => $params['PHONE_NUMBER'],
				'STATUS' => VI\CallTable::STATUS_CONNECTING,
				'ACCESS_URL' => $params['ACCESS_URL'],
				'PORTAL_USER_ID' => $result['PORTAL_CALL'] == 'Y'? $result['USER_ID']: 0,
				'DATE_CREATE' => new FieldType\DateTime(),
			));
		}

		$config = self::GetConfigByUserId($params['USER_ID']);

		if ($params['CRM'] == 'Y' && $result['PORTAL_CALL'] == 'N')
		{
			if ($config['CRM_CREATE'] == CVoxImplantConfig::CRM_CREATE_LEAD)
			{
				$crmData = CVoxImplantCrmHelper::GetDataForPopup($params['CALL_ID'], $params['PHONE_NUMBER']);
				if ($crmData['FOUND'] == 'N')
				{
					CVoxImplantCrmHelper::AddLead(Array(
						'USER_ID' => $params['USER_ID'],
						'PHONE_NUMBER' => $params['PHONE_NUMBER'],
						'SEARCH_ID' => $config['SEARCH_ID'],
						'CRM_SOURCE' => $config['CRM_SOURCE'],
						'INCOMING' => false,
					));
				}
			}

			CVoxImplantCrmHelper::AddCall(Array(
				'CALL_ID' => $params['CALL_ID'],
				'PHONE_NUMBER' => $params['PHONE_NUMBER'],
				'INCOMING' => CVoxImplantMain::CALL_OUTGOING,
				'USER_ID' => $params['USER_ID'],
				'DATE_CREATE' => new FieldType\DateTime()
			));

			$crmData = CVoxImplantCrmHelper::GetDataForPopup($params['CALL_ID'], $params['PHONE_NUMBER'], $params['USER_ID']);
		}
		else
		{
			$crmData = Array();
		}

		CVoxImplantHistory::WriteToLog(Array(
			'COMMAND' => 'outgoing',
			'USER_ID' => $params['USER_ID'],
			'CALL_ID' => $params['CALL_ID'],
			'CALL_ID_TMP' => $params['CALL_ID_TMP'],
			'CALL_DEVICE' => $params['CALL_DEVICE'],
			'PHONE_NUMBER' => $params['PHONE_NUMBER'],
			'EXTERNAL' => $params['CALL_ID_TMP']? true: false,
			'PORTAL_CALL' => $result['PORTAL_CALL'],
			'PORTAL_CALL_USER_ID' => $params['USER_ID'],
			'CRM' => $crmData,
		));

		$portalUser = Array();
		if ($result['PORTAL_CALL'] == 'Y')
		{
			if (CModule::IncludeModule('im'))
			{
				$portalUser = CIMContactList::GetUserData(Array('ID' => Array($params['USER_ID'], $result['USER_ID']), 'DEPARTMENT' => 'N', 'HR_PHOTO' => 'Y'));
			}
			else
			{
				$portalUser = Array();
			}
		}

		self::SendPullEvent(Array(
			'COMMAND' => 'outgoing',
			'USER_ID' => $params['USER_ID'],
			'CALL_ID' => $params['CALL_ID'],
			'CALL_ID_TMP' => $params['CALL_ID_TMP'],
			'CALL_DEVICE' => $params['CALL_DEVICE'],
			'PHONE_NUMBER' => $params['PHONE_NUMBER'],
			'EXTERNAL' => $params['CALL_ID_TMP']? true: false,
			'PORTAL_CALL' => $result['PORTAL_CALL'],
			'PORTAL_CALL_USER_ID' => $result['USER_ID'],
			'PORTAL_CALL_DATA' => $portalUser,
			'CONFIG' => Array(
				'RECORDING' => $config['RECORDING'],
				'CRM_CREATE' => $config['CRM_CREATE']
			),
			'CRM' => $crmData,
		));

		if ($result['PORTAL_CALL'] == 'Y' && $result['USER_ID'] > 0)
		{
			CVoxImplantIncoming::SendPullEvent(Array(
				'COMMAND' => 'invite',
				'USER_ID' => $result['USER_ID'],
				'CALL_ID' => $params['CALL_ID'],
				'CALLER_ID' => $params['USER_DIRECT_CODE'],
				'PHONE_NAME' => $params['CALLER_ID'],
				'PORTAL_CALL' => 'Y',
				'PORTAL_CALL_USER_ID' => $params['USER_ID'],
				'PORTAL_CALL_DATA' => $portalUser,
				'CONFIG' => Array(
					'RECORDING' => $config['RECORDING'],
					'CRM_CREATE' => $config['CRM_CREATE']
				),
			));
		}

		return $result;
	}

	public static function GetLinkConfig()
	{
		$portalUrl = '';
		if (CVoxImplantHttp::GetPortalType() == CVoxImplantHttp::TYPE_BITRIX24)
			$portalUrl = CVoxImplantHttp::GetServerAddress().'/settings/info_receiver.php?b24_action=phone&b24_direct=y';
		else
			$portalUrl = CVoxImplantHttp::GetServerAddress().'/services/telephony/info_receiver.php?b24_direct=y';

		return Array(
			'PORTAL_MODE' => 'LINK',
			'PORTAL_URL' => $portalUrl,
			'PORTAL_SIGN' => CVoxImplantHttp::GetPortalSign(),
			'SEARCH_ID' => CVoxImplantPhone::GetLinkNumber(),
			'PHONE_NAME' => CVoxImplantPhone::GetLinkNumber(), // TODO add "+" in next version
			'RECORDING' => CVoxImplantConfig::GetLinkCallRecord()? 'Y': 'N',
			'CRM' => CVoxImplantConfig::GetLinkCheckCrm()? 'Y': 'N',
			'MELODY_HOLD' => CVoxImplantConfig::GetMelody('MELODY_HOLD'),
		);
	}

	public static function GetConfigByUserId($userId)
	{
		$userId = intval($userId);
		if ($userId > 0)
		{
			$viUser = new CVoxImplantUser();
			$userInfo = $viUser->GetUserInfo($userId);
			if ($userInfo['user_backphone'] == '')
			{
				$userInfo['user_backphone'] = CVoxImplantConfig::LINK_BASE_NUMBER;
			}
		}
		else
		{
			$userInfo = Array();
			$userInfo['user_backphone'] = CVoxImplantConfig::GetPortalNumber();
			$userInfo['user_extranet'] = false;
			$userInfo['user_innerphone'] = CVoxImplantConfig::GetPortalNumber();
		}

		if ($userInfo['user_extranet'])
		{
			$result = Array('error' => Array('code' => 'EXTRANAET', 'msg' => 'Extranet user (or user hasnt department) cannot use telephony'));
		}
		else if ($userInfo['user_backphone'] == CVoxImplantPhone::GetLinkNumber() || $userInfo['user_backphone'] == CVoxImplantConfig::LINK_BASE_NUMBER)
		{
			$result = self::GetLinkConfig();
		}
		else
		{
			$result = CVoxImplantConfig::GetConfigBySearchId($userInfo['user_backphone']);
			if (isset($result['ERROR']) && strlen($result['ERROR']) > 0)
			{
				$result = self::GetLinkConfig();
			}
		}

		$result['USER_ID'] = $userId;
		$result['USER_DIRECT_CODE'] = $userInfo['user_innerphone'];

		return $result;
	}

	public static function SendPullEvent($params)
	{
		// TODO check params

		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus() || $params['USER_ID'] <= 0)
			return false;

		$config = Array();
		$push = Array();
		if ($params['COMMAND'] == 'outgoing')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"callIdTmp" => $params['CALL_ID_TMP']? $params['CALL_ID_TMP']: '',
				"callDevice" => $params['CALL_DEVICE'] == 'PHONE'? 'PHONE': 'WEBRTC',
				"phoneNumber" => $params['PHONE_NUMBER'],
				"external" => $params['EXTERNAL']? true: false,
				"portalCall" => $params['PORTAL_CALL'] == 'Y'? true: false,
				"portalCallUserId" => $params['PORTAL_CALL'] == 'Y'? $params['PORTAL_CALL_USER_ID']: 0,
				"portalCallData" => $params['PORTAL_CALL'] == 'Y'? $params['PORTAL_CALL_DATA']: Array(),
				"config" => $params['CONFIG']? $params['CONFIG']: Array(),
				"CRM" => $params['CRM']? $params['CRM']: Array(),
			);
			$push['send_immediately'] = 'Y';
			$push['advanced_params'] = Array(
				"notificationsToCancel" => array('VI_CALL_'.$params['CALL_ID']),
			);
		}
		else if ($params['COMMAND'] == 'timeout')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"failedCode" => intval($params['FAILED_CODE']),
			);
			$push['send_immediately'] = 'Y';
			$push['advanced_params'] = Array(
				"notificationsToCancel" => array('VI_CALL_'.$params['CALL_ID']),
			);
		}

		if (isset($params['MARK']))
		{
			$config['mark'] = $params['MARK'];
		}

		CPullStack::AddByUser($params['USER_ID'],
			Array(
				'module_id' => 'voximplant',
				'command' => $params['COMMAND'],
				'params' => $config,
				'push' => $push
			)
		);

		return true;
	}

	public static function StartCall($userId, $phoneNumber)
	{
		$phoneNormalized = CVoxImplantPhone::Normalize($phoneNumber);
		if (!$phoneNormalized)
		{
			$phoneNormalized = preg_replace("/[^0-9\#\*]/i", "", $phoneNumber);
		}

		$userId = intval($userId);
		if ($userId <= 0 || !$phoneNormalized)
			return false;

		$call = VI\CallTable::add(Array(
			'CALL_ID' => 'temp.'.md5($userId.$phoneNumber).time(),
			'USER_ID' => $userId,
			'CALLER_ID' => $phoneNormalized,
			'STATUS' => VI\CallTable::STATUS_CONNECTING,
			'DATE_CREATE' => new FieldType\DateTime(),
		));

		$viHttp = new CVoxImplantHttp();
		$result = $viHttp->StartOutgoingCall($userId, $phoneNumber);

		VI\CallTable::update($call->GetId(), Array(
			'CALL_ID' => $result->call_id,
			'ACCESS_URL' => $result->access_url,
			'DATE_CREATE' => new FieldType\DateTime(),
		));

		$config = self::GetConfigByUserId($call->GetId());

		self::SendPullEvent(Array(
			'COMMAND' => 'outgoing',
			'USER_ID' => $userId,
			'PHONE_NUMBER' => $phoneNormalized,
			'CALL_ID' => $result->call_id,
			'CALL_DEVICE' => 'PHONE',
			'EXTERNAL' => true,
			'CONFIG' => Array(
				'RECORDING' => $config['RECORDING'],
				'CRM_CREATE' => $config['CRM_CREATE']
			),
		));

		return $result? true: false;
	}

	public static function GetTtsDefaultVoice()
	{
		$lang = 'ru';
		if ($lang == 'ru')
		{
			$voice = 'RU_RUSSIAN_MALE';
		}
		else
		{
			$voice = 'US_ENGLISH_MALE';
		}

		return $voice;
	}

	public static function GetTtsVoiceList()
	{
		return Array(
			'RU_RUSSIAN_FEMALE' => GetMessage('VI_TTS_VOICE_RU_RUSSIAN_FEMALE'),
			self::TTS_VOICE_DEFAULT => GetMessage('VI_TTS_VOICE_RU_RUSSIAN_MALE'),
			'US_ENGLISH_FEMALE' => GetMessage('VI_TTS_VOICE_US_ENGLISH_FEMALE'),
			'US_ENGLISH_MALE' => GetMessage('VI_TTS_VOICE_US_ENGLISH_MALE'),
			'US_SPANISH_FEMALE' => GetMessage('VI_TTS_VOICE_US_SPANISH_FEMALE'),
			'US_SPANISH_MALE' => GetMessage('VI_TTS_VOICE_US_SPANISH_MALE'),
			'EUR_GERMAN_FEMALE' => GetMessage('VI_TTS_VOICE_EUR_GERMAN_FEMALE'),
			'EUR_GERMAN_MALE' => GetMessage('VI_TTS_VOICE_EUR_GERMAN_MALE'),
			'EUR_DUTCH_FEMALE' => GetMessage('VI_TTS_VOICE_EUR_DUTCH_FEMALE'),
			'EUR_CATALAN_FEMALE' => GetMessage('VI_TTS_VOICE_EUR_CATALAN_FEMALE'),
			'EUR_CZECH_FEMALE' => GetMessage('VI_TTS_VOICE_EUR_CZECH_FEMALE'),
			'EUR_DANISH_FEMALE' => GetMessage('VI_TTS_VOICE_EUR_DANISH_FEMALE'),
			'EUR_FINNISH_FEMALE' => GetMessage('VI_TTS_VOICE_EUR_FRENCH_FEMALE'),
			'EUR_FRENCH_MALE' => GetMessage('VI_TTS_VOICE_EUR_FRENCH_MALE'),
			'EUR_ITALIAN_FEMALE' => GetMessage('VI_TTS_VOICE_EUR_ITALIAN_FEMALE'),
			'EUR_ITALIAN_MALE' => GetMessage('VI_TTS_VOICE_EUR_ITALIAN_MALE'),
			'EUR_NORWEGIAN_FEMALE' => GetMessage('VI_TTS_VOICE_EUR_NORWEGIAN_FEMALE'),
			'EUR_POLISH_FEMALE' => GetMessage('VI_TTS_VOICE_EUR_POLISH_FEMALE'),
			'EUR_PORTUGUESE_FEMALE' => GetMessage('VI_TTS_VOICE_EUR_PORTUGUESE_FEMALE'),
			'EUR_PORTUGUESE_MALE' => GetMessage('VI_TTS_VOICE_EUR_PORTUGUESE_MALE'),
			'EUR_SPANISH_FEMALE' => GetMessage('VI_TTS_VOICE_EUR_SPANISH_FEMALE'),
			'SW_SWEDISH_FEMALE' => GetMessage('VI_TTS_VOICE_SW_SWEDISH_FEMALE'),
			'HU_HUNGARIAN_FEMALE' => GetMessage('VI_TTS_VOICE_HU_HUNGARIAN_FEMALE'),
			'EUR_TURKISH_FEMALE' => GetMessage('VI_TTS_VOICE_EUR_TURKISH_FEMALE'),
			'EUR_TURKISH_MALE' => GetMessage('VI_TTS_VOICE_EUR_TURKISH_MALE'),
			'UK_ENGLISH_FEMALE' => GetMessage('VI_TTS_VOICE_UK_ENGLISH_FEMALE'),
			'UK_ENGLISH_MALE' => GetMessage('VI_TTS_VOICE_UK_ENGLISH_MALE'),
			'AU_ENGLISH_FEMALE' => GetMessage('VI_TTS_VOICE_AU_ENGLISH_FEMALE'),
			'BR_PORTUGUESE_FEMALE' => GetMessage('VI_TTS_VOICE_BR_PORTUGUESE_FEMALE'),
			'CA_ENGLISH_FEMALE' => GetMessage('VI_TTS_VOICE_CA_ENGLISH_FEMALE'),
			'CA_FRENCH_FEMALE' => GetMessage('VI_TTS_VOICE_CA_FRENCH_FEMALE'),
			'CA_FRENCH_MALE' => GetMessage('VI_TTS_VOICE_CA_FRENCH_MALE'),
			'CH_CHINESE_FEMALE' => GetMessage('VI_TTS_VOICE_CH_CHINESE_FEMALE'),
			'CH_CHINESE_MALE' => GetMessage('VI_TTS_VOICE_CH_CHINESE_MALE'),
			'JP_JAPANESE_FEMALE' => GetMessage('VI_TTS_VOICE_JP_JAPANESE_FEMALE'),
			'JP_JAPANESE_MALE' => GetMessage('VI_TTS_VOICE_JP_JAPANESE_MALE'),
			'KR_KOREAN_FEMALE' => GetMessage('VI_TTS_VOICE_KR_KOREAN_FEMALE'),
			'KR_KOREAN_MALE' => GetMessage('VI_TTS_VOICE_KR_KOREAN_MALE'),
			'TW_CHINESE_FEMALE' => GetMessage('VI_TTS_VOICE_TW_CHINESE_FEMALE'),
		);
	}

	public static function GetTtsSpeedList()
	{
		return Array(
			'x-slow' => GetMessage('VI_TTS_SPEED_X_SLOW'),
			'slow' => GetMessage('VI_TTS_SPEED_SLOW'),
			self::TTS_SPEED_DEFAULT => GetMessage('VI_TTS_SPEED_MEDIUM'),
			'fast' => GetMessage('VI_TTS_SPEED_FAST'),
			'x-fast' => GetMessage('VI_TTS_SPEED_X_FAST'),
		);
	}

	public static function GetTtsVolumeList()
	{
		return Array(
			self::TTS_VOLUME_DEFAULT => GetMessage('VI_TTS_VOLUME_DEFAULT'),
			'x-soft' => GetMessage('VI_TTS_VOLUME_X_SOFT'),
			'soft' => GetMessage('VI_TTS_VOLUME_SOFT'),
			'medium' => GetMessage('VI_TTS_VOLUME_MEDIUM'),
			'loud' => GetMessage('VI_TTS_VOLUME_LOUD'),
			'x-loud' => GetMessage('VI_TTS_VOLUME_X_LOUD'),
		);
	}

	public static function SetInfoCallResult()
	{
		// voximplant onInfoCallResult [md5('CALL_ID'), array('number' => Array('result' => true, 'reason' => 200))]
	}

	public static function StartInfoCallWithText($outputNumber, $number, $text, $voiceLanguage = self::TTS_VOICE_DEFAULT, $voiceSpeed = self::TTS_SPEED_DEFAULT, $voiceVolume = self::TTS_VOLUME_DEFAULT)
	{
		CVoxImplantHistory::WriteToLog(Array($outputNumber, $number, $text, $voiceLanguage, $voiceSpeed, $voiceVolume), 'StartInfoCallWithText');

		return md5('CALL_ID');
	}

	public static function StartInfoCallWithSound($outputNumber, $number, $url)
	{
		CVoxImplantHistory::WriteToLog(Array($outputNumber, $number, $url), 'StartInfoCallWithSound');
		return md5('CALL_ID');
	}
}
?>
