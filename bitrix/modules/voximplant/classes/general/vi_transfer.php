<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Voximplant as VI;

class CVoxImplantTransfer
{
	public static function Invite($callId, $transferUserId)
	{
		$transferUserId = intval($transferUserId);
		if ($transferUserId <= 0)
			return false;

		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'USER_ID', 'CALLER_ID', 'CRM', 'TRANSFER_USER_ID', 'ACCESS_URL'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		if ($call['TRANSFER_USER_ID'] > 0)
		{
			self::Cancel($callId);
		}

		$call['TRANSFER_USER_ID'] = $transferUserId;

		VI\CallTable::update($call['ID'], Array('TRANSFER_USER_ID' => $transferUserId));

		$call['USER_HAVE_PHONE'] = 'N';
		$res = CVoxImplantUser::GetList(Array(
			'select' => Array('ID', 'IS_ONLINE_CUSTOM', 'UF_VI_PHONE', 'ACTIVE'),
			'filter' => Array('=ID' => $call['TRANSFER_USER_ID'], '=ACTIVE' => 'Y'),
		));
		if ($userData = $res->fetch())
		{
			$call['USER_HAVE_PHONE'] = $userData['UF_VI_PHONE'];
		}

		$command['COMMAND'] = 'inviteTransfer';
		$command['OPERATOR_ID'] = $call['USER_ID'];
		$command['TRANSFER_USER_ID'] = $call['TRANSFER_USER_ID'];
		$command['USER_HAVE_PHONE'] = $call['USER_HAVE_PHONE'];

		$http = new \Bitrix\Main\Web\HttpClient();
		$http->waitResponse(false);
		$http->post($call['ACCESS_URL'], json_encode($command));

		$crmData = Array();
		if ($call['CRM'] == 'Y')
			$crmData = CVoxImplantCrmHelper::GetDataForPopup($call['CALL_ID'], $call['CALLER_ID'], $transferUserId);

		self::SendPullEvent(Array(
			'COMMAND' => 'inviteTransfer',
			'USER_ID' => $transferUserId,
			'CALL_ID' => $call['CALL_ID'],
			'CALLER_ID' => $call['CALLER_ID'],
			'CRM' => $crmData,
		));

		return true;
	}

	public static function Cancel($callId)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'CALLER_ID', 'USER_ID', 'TRANSFER_USER_ID', 'ACCESS_URL'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		VI\CallTable::update($call['ID'], Array('TRANSFER_USER_ID' => 0));

		$command['COMMAND'] = 'cancelTransfer';
		$command['OPERATOR_ID'] = $call['USER_ID'];
		$command['TRANSFER_USER_ID'] = $call['TRANSFER_USER_ID'];

		$http = new \Bitrix\Main\Web\HttpClient();
		$http->waitResponse(false);
		$http->post($call['ACCESS_URL'], json_encode($command));

		self::SendPullEvent(Array(
			'COMMAND' => 'cancelTransfer',
			'USER_ID' => $call['TRANSFER_USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		return true;
	}

	public static function Wait($callId)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'USER_ID', 'TRANSFER_USER_ID', 'ACCESS_URL'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		$command['COMMAND'] = 'waitTransfer';
		$command['OPERATOR_ID'] = $call['USER_ID'];

		$http = new \Bitrix\Main\Web\HttpClient();
		$http->waitResponse(false);
		$http->post($call['ACCESS_URL'], json_encode($command));

		self::SendPullEvent(Array(
			'COMMAND' => 'waitTransfer',
			'USER_ID' => $call['USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		return true;
	}

	public static function Answer($callId)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'USER_ID', 'TRANSFER_USER_ID', 'ACCESS_URL'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		$command['COMMAND'] = 'waitTransfer';
		$command['OPERATOR_ID'] = $call['USER_ID'];

		$http = new \Bitrix\Main\Web\HttpClient();
		$http->waitResponse(false);
		$http->post($call['ACCESS_URL'], json_encode($command));

		self::SendPullEvent(Array(
			'COMMAND' => 'waitTransfer',
			'USER_ID' => $call['USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		self::SendPullEvent(Array(
			'COMMAND' => 'timeoutTransfer',
			'USER_ID' => $call['TRANSFER_USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		return true;
	}

	public static function Ready($callId)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'CALLER_ID', 'USER_ID', 'TRANSFER_USER_ID', 'ACCESS_URL'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		$answer['COMMAND'] = 'transferConnect';
		$answer['OPERATOR_ID'] = $call['USER_ID'];

		$http = new \Bitrix\Main\Web\HttpClient();
		$http->waitResponse(false);
		$http->post($call['ACCESS_URL'], json_encode($answer));

		return true;
	}

	public static function Complete($callId, $device = 'WEBRTC')
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'CRM_LEAD', 'CALLER_ID', 'USER_ID', 'TRANSFER_USER_ID', 'ACCESS_URL', 'CRM', 'CONFIG_ID'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		VI\CallTable::update($call['ID'], Array('USER_ID' => $call['TRANSFER_USER_ID'], 'TRANSFER_USER_ID' => 0));

		CVoxImplantHistory::TransferMessage($call['USER_ID'], $call['TRANSFER_USER_ID'], $call['CALLER_ID']);

		self::SendPullEvent(Array(
			'COMMAND' => 'completeTransfer',
			'USER_ID' => $call['USER_ID'],
			'TRANSFER_USER_ID' => $call['TRANSFER_USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		$crmDataSend = false;
		if ($call['CRM'] == 'Y' && $call['CONFIG_ID'] > 0)
		{
			$config = CVoxImplantConfig::GetConfig($call['CONFIG_ID']);
			if (isset($config['CRM_TRANSFER_CHANGE']) && $config['CRM_TRANSFER_CHANGE'] == 'Y')
			{
				if ($call['CRM_LEAD'] > 0)
				{
					$crmData = Array(
						'LEAD_DATA' => Array(
							'ID' => $call['CRM_LEAD'],
							'ASSIGNED_BY_ID' => 0,
						)
					);
				}
				else
				{
					$crmData = CVoxImplantCrmHelper::GetDataForPopup($call['CALL_ID'], $call['CALLER_ID']);
				}

				if (isset($crmData['LEAD_DATA']) && $crmData['LEAD_DATA']['ASSIGNED_BY_ID'] >= 0 && $call['TRANSFER_USER_ID'] > 0 && $crmData['LEAD_DATA']['ASSIGNED_BY_ID'] != $call['TRANSFER_USER_ID'])
				{
					CVoxImplantCrmHelper::UpdateLead($crmData['LEAD_DATA']['ID'], Array('ASSIGNED_BY_ID' => $call['TRANSFER_USER_ID']));
					$crmDataSend = CVoxImplantCrmHelper::GetDataForPopup($call['CALL_ID'], $call['CALLER_ID'], $call['TRANSFER_USER_ID']);
				}
			}
		}

		self::SendPullEvent(Array(
			'COMMAND' => 'completeTransfer',
			'USER_ID' => $call['TRANSFER_USER_ID'],
			'TRANSFER_USER_ID' => $call['TRANSFER_USER_ID'],
			'CALL_DEVICE' => $device,
			'CALL_ID' => $call['CALL_ID'],
			'CRM' => $crmDataSend
		));

		return true;
	}

	public static function Decline($callId, $send = true)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID','CALL_ID', 'USER_ID', 'TRANSFER_USER_ID', 'ACCESS_URL'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		VI\CallTable::update($call['ID'], Array('TRANSFER_USER_ID' => 0));

		if ($send)
		{
			$command['COMMAND'] = 'declineTransfer';
			$command['OPERATOR_ID'] = $call['USER_ID'];

			$http = new \Bitrix\Main\Web\HttpClient();
			$http->waitResponse(false);
			$http->post($call['ACCESS_URL'], json_encode($command));
		}

		self::SendPullEvent(Array(
			'COMMAND' => 'declineTransfer',
			'USER_ID' => $call['USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		self::SendPullEvent(Array(
			'COMMAND' => 'timeoutTransfer',
			'USER_ID' => $call['TRANSFER_USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		return true;
	}

	public static function Timeout($callId)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'USER_ID', 'TRANSFER_USER_ID', 'ACCESS_URL'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		VI\CallTable::update($call['ID'], Array('TRANSFER_USER_ID' => 0));

		self::SendPullEvent(Array(
			'COMMAND' => 'timeoutTransfer',
			'USER_ID' => $call['TRANSFER_USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		return true;
	}

	public static function SendPullEvent($params)
	{
		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus() || $params['USER_ID'] <= 0)
			return false;

		if (empty($params['COMMAND']))
			return false;

		$config = Array();
		if ($params['COMMAND'] == 'inviteTransfer')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"callerId" => $params['CALLER_ID'],
				"phoneNumber" => $params['PHONE_NAME'],
				"chatId" => 0,
				"chat" => array(),
				"application" => $params['APPLICATION'],
				"CRM" => $params['CRM'],
			);
		}
		else if ($params['COMMAND'] == 'completeTransfer')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"transferUserId" => $params['TRANSFER_USER_ID'],
				"callDevice" => $params['CALL_DEVICE'],
				"CRM" => $params['CRM']? $params['CRM']: false,
			);
		}
		else
		{
			$config["callId"] = $params['CALL_ID'];
		}
		if (isset($params['MARK']))
		{
			$config['mark'] = $params['MARK'];
		}
		CPullStack::AddByUser($params['USER_ID'],
			Array(
				'module_id' => 'voximplant',
				'command' => $params['COMMAND'],
				'params' => $config
			)
		);

		return true;
	}
}
?>