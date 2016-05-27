<?php

use Bitrix\Main\Web\Json;

if(!CModule::IncludeModule("voximplant"))
	return false;

if (is_object($APPLICATION))
	$APPLICATION->RestartBuffer();

CVoxImplantHistory::WriteToLog($_POST, 'PORTAL HIT');

$params = $_POST;
$hash = $params["BX_HASH"];
unset($params["BX_HASH"]);

// VOXIMPLANT CLOUD HITS
if(
	!isset($params['BX_TYPE']) && isset($_GET['b24_direct']) && CVoxImplantHttp::CheckDirectRequest($params) ||
	$params['BX_TYPE'] == 'B24' && CVoxImplantHttp::RequestSign($params['BX_TYPE'], md5(implode("|", $params)."|".BX24_HOST_NAME)) === $hash ||
	$params['BX_TYPE'] == 'CP' && CVoxImplantHttp::RequestSign($params['BX_TYPE'], md5(implode("|", $params))) === $hash
)
{
	if ($params["BX_COMMAND"] != "add_history" && !in_array($params["COMMAND"], Array("OutgoingRegister", "AddCallHistory")) && isset($params['PHONE_NUMBER']) && isset($params['ACCOUNT_SEARCH_ID']))
	{
		$params['PHONE_NUMBER'] = $params['ACCOUNT_SEARCH_ID'];
	}

	if (isset($_GET['b24_direct']) && isset($params['PORTAL_USER_ID']) && isset($params['USER_ID']))
	{
		$params['USER_ID'] = $params['PORTAL_USER_ID'];
	}

	if($params["COMMAND"] == "OutgoingRegister")
	{
		if (isset($params['CALLER_ID']) && isset($params['ACCOUNT_SEARCH_ID']))
		{
			$params['CALLER_ID'] = $params['ACCOUNT_SEARCH_ID'];
		}

		$result = CVoxImplantOutgoing::Init(Array(
			'ACCOUNT_SEARCH_ID' => $params['ACCOUNT_SEARCH_ID'],
			'CONFIG_ID' => $params['CONFIG_ID'],
			'USER_ID' => $params['USER_ID'],
			'USER_DIRECT_CODE' => $params['USER_DIRECT_CODE'],
			'PHONE_NUMBER' => $params['PHONE_NUMBER'],
			'CALL_ID' => $params['CALL_ID'],
			'CALL_ID_TMP' => $params['CALL_ID_TMP']? $params['CALL_ID_TMP']: '',
			'CALL_DEVICE' => $params['CALL_DEVICE'],
			'CALLER_ID' => $params['CALLER_ID'],
			'ACCESS_URL' => $params['ACCESS_URL'],
			'CRM' => $params['CRM'],
		));

		foreach(GetModuleEvents("voximplant", "onCallInit", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, Array(Array(
				'CALL_ID' => $params['CALL_ID'],
				'CALL_ID_TMP' => $params['CALL_ID_TMP']? $params['CALL_ID_TMP']: '',
				'CALL_TYPE' => 1,
				'ACCOUNT_SEARCH_ID' => $params['ACCOUNT_SEARCH_ID'],
				'PHONE_NUMBER' => $params['PHONE_NUMBER'],
				'CALLER_ID' => $params['CALLER_ID'],
			)));
		}

		CVoxImplantHistory::WriteToLog($result, 'OUTGOING REGISTER');

		echo Json::encode($result);
	}
	else if($params["COMMAND"] == "IncomingInvite")
	{
		$result = CVoxImplantIncoming::Init(Array(
			'SEARCH_ID' => $params['PHONE_NUMBER'],
			'CALL_ID' => $params['CALL_ID'],
			'CALLER_ID' => $params['CALLER_ID'],
			'DIRECT_CODE' => $params['DIRECT_CODE'],
			'ACCESS_URL' => $params['ACCESS_URL'],
		));

		CVoxImplantHistory::WriteToLog($result, 'INCOMING INVITE: ANSWER');

		echo Json::encode($result);
	}
	else if($params["COMMAND"] == "FailAnswer")
	{
		CVoxImplantMain::SendPullEvent(Array(
			'COMMAND' => 'timeout',
			'USER_ID' => $params['USER_ID'],
			'CALL_ID' => $params['CALL_ID'],
			'MARK' => 'timeout_hit_1'
		));
		echo Json::encode(Array('result' => 'OK'));
	}
	else if($params["COMMAND"] == "TransferTimeout")
	{
		CVoxImplantTransfer::Timeout($params['CALL_ID']);

		echo Json::encode(Array('result' => 'OK'));
	}
	else if($params["COMMAND"] == "TransferCancel")
	{
		CVoxImplantTransfer::Decline($params['CALL_ID'], false);

		echo Json::encode(Array('result' => 'OK'));
	}
	else if($params["COMMAND"] == "TransferComplete")
	{
		CVoxImplantTransfer::Complete($params['CALL_ID'], $params['CALL_DEVICE']);

		echo Json::encode(Array('result' => 'OK'));
	}
	else if($params["COMMAND"] == "StartCall")
	{
		CVoxImplantMain::CallStart($params['CALL_ID'], $params['USER_ID'], $params['CALL_DEVICE'], $params['EXTERNAL'] == 'Y');

		$res = Bitrix\Voximplant\CallTable::getList(Array(
			'filter' => Array('=CALL_ID' => $params['CALL_ID']),
		));
		if ($call = $res->fetch())
		{
			if ($call['PORTAL_USER_ID'] <= 0)
			{
				$res = Bitrix\Voximplant\QueueTable::getList(Array(
					'filter' => Array('=CONFIG_ID' => $call['CONFIG_ID']),
				));
				while ($queue = $res->fetch())
				{
					if ($params['USER_ID'] == $queue['USER_ID'] || $call['PORTAL_USER_ID'] == $queue['USER_ID'])
						continue;

					CVoxImplantMain::SendPullEvent(Array(
						'COMMAND' => 'timeout',
						'USER_ID' => $queue['USER_ID'],
						'CALL_ID' => $call['CALL_ID'],
						'MARK' => 'timeout_hit_2'
					));
				}
			}
		}

		echo Json::encode(Array('result' => 'OK'));
	}
	else if($params["COMMAND"] == "HangupCall")
	{
		$res = Bitrix\Voximplant\CallTable::getList(Array(
			'filter' => Array('=CALL_ID' => $params['CALL_ID']),
		));
		$userTimeout = Array();
		if ($call = $res->fetch())
		{
			$res = Bitrix\Voximplant\QueueTable::getList(Array(
				'filter' => Array('=CONFIG_ID' => $call['CONFIG_ID']),
			));
			while ($queue = $res->fetch())
			{
				if ($call['TRANSFER_USER_ID'] == $queue['USER_ID'])
					continue;

				$userTimeout[$queue['USER_ID']] = true;
				CVoxImplantMain::SendPullEvent(Array(
					'COMMAND' => 'timeout',
					'USER_ID' => $queue['USER_ID'],
					'CALL_ID' => $call['CALL_ID'],
					'MARK' => 'timeout_hit_3'
				));
			}
			if ($call['TRANSFER_USER_ID'] > 0)
			{
				$userTimeout[$call['TRANSFER_USER_ID']] = true;
				CVoxImplantTransfer::SendPullEvent(Array(
					'COMMAND' => 'timeoutTransfer',
					'USER_ID' => $call['TRANSFER_USER_ID'],
					'CALL_ID' => $call['CALL_ID'],
				));
			}
			if ($call['PORTAL_USER_ID'] > 0 && !$userTimeout[$call['PORTAL_USER_ID']])
			{
				$userTimeout[$call['PORTAL_USER_ID']] = true;
				CVoxImplantMain::SendPullEvent(Array(
					'COMMAND' => 'timeout',
					'USER_ID' => $call['PORTAL_USER_ID'],
					'CALL_ID' => $call['CALL_ID'],
					'MARK' => 'timeout_hit_4'
				));
			}
			if ($call['USER_ID'] > 0 && !$userTimeout[$call['USER_ID']])
			{
				CVoxImplantMain::SendPullEvent(Array(
					'COMMAND' => 'timeout',
					'USER_ID' => $call['USER_ID'],
					'CALL_ID' => $call['CALL_ID'],
					'MARK' => 'timeout_hit_5'
				));
			}
		}
		else
		{
			CVoxImplantMain::SendPullEvent(Array(
				'COMMAND' => 'timeout',
				'USER_ID' => $params['USER_ID'],
				'CALL_ID' => $params['CALL_ID'],
				'MARK' => 'timeout_hit_6'
			));
		}

		CVoxImplantHistory::WriteToLog($call, 'PORTAL HANGUP');

		echo Json::encode(Array('result' => 'OK'));
	}
	else if($params["COMMAND"] == "GetNextAction")
	{
		if ($params['QUEUE_TYPE'] == CVoxImplantConfig::QUEUE_TYPE_ALL)
		{
			$result = CVoxImplantIncoming::GetQueue(Array(
				'SEARCH_ID' => $params['PHONE_NUMBER'],
				'CALL_ID' => $params['CALL_ID'],
				'CALLER_ID' => $params['CALLER_ID'],
				'LAST_TYPE_CONNECT' => $params['LAST_TYPE_CONNECT'],
				'LOAD_QUEUE' => 'N',
				'SEND_INVITE' => 'N',
			));
			CVoxImplantHistory::WriteToLog($result, 'GET NEXT ACTION - QUEUE');
		}
		else
		{
			$result = CVoxImplantIncoming::GetNextAction(Array(
				'SEARCH_ID' => $params['PHONE_NUMBER'],
				'CALL_ID' => $params['CALL_ID'],
				'CALLER_ID' => $params['CALLER_ID'],
				'LAST_USER_ID' => $params['LAST_USER_ID'],
				'LAST_TYPE_CONNECT' => $params['LAST_TYPE_CONNECT'],
				'LAST_ANSWER_USER_ID' => $params['LAST_ANSWER_USER_ID'],
				'EXCLUDE_USERS' => $excludeUsers,
			));
			CVoxImplantHistory::WriteToLog($result, 'GET NEXT ACTION');
		}
		echo Json::encode($result);
	}
	else if($params["COMMAND"] == "GetNextInQueue")
	{
		$excludeUsers = Array();
		if (isset($params['EXCLUDE_USERS']))
			$excludeUsers = explode('|', $params['EXCLUDE_USERS']);

		if (in_array($params['LAST_TYPE_CONNECT'], Array(CVoxImplantIncoming::TYPE_CONNECT_DIRECT, CVoxImplantIncoming::TYPE_CONNECT_CRM)))
		{
			$result = CVoxImplantIncoming::GetNextAction(Array(
				'SEARCH_ID' => $params['PHONE_NUMBER'],
				'CALL_ID' => $params['CALL_ID'],
				'CALLER_ID' => $params['CALLER_ID'],
				'LAST_USER_ID' => $params['LAST_USER_ID'],
				'LAST_TYPE_CONNECT' => $params['LAST_TYPE_CONNECT'],
				'LAST_ANSWER_USER_ID' => $params['LAST_ANSWER_USER_ID'],
				'EXCLUDE_USERS' => $excludeUsers,
			));
			CVoxImplantHistory::WriteToLog($result, 'GET NEXT ACTION');
		}
		else if ($params['QUEUE_TYPE'] == CVoxImplantConfig::QUEUE_TYPE_ALL)
		{
			$result = CVoxImplantIncoming::GetQueue(Array(
				'SEARCH_ID' => $params['PHONE_NUMBER'],
				'CALL_ID' => $params['CALL_ID'],
				'CALLER_ID' => $params['CALLER_ID'],
				'LAST_TYPE_CONNECT' => $params['LAST_TYPE_CONNECT'],
			));
			CVoxImplantHistory::WriteToLog($result, 'RESEND IN QUEUE');
		}
		else
		{
			$result = CVoxImplantIncoming::GetNextInQueue(Array(
				'SEARCH_ID' => $params['PHONE_NUMBER'],
				'CALL_ID' => $params['CALL_ID'],
				'CALLER_ID' => $params['CALLER_ID'],
				'LAST_USER_ID' => $params['LAST_USER_ID'],
				'LAST_TYPE_CONNECT' => $params['LAST_TYPE_CONNECT'],
				'LAST_ANSWER_USER_ID' => $params['LAST_ANSWER_USER_ID'],
				'EXCLUDE_USERS' => $excludeUsers,
			));
			CVoxImplantHistory::WriteToLog($result, 'GET NEXT IN QUEUE');
		}

		echo Json::encode($result);
	}

	// CONTROLLER OR EMERGENCY HITS
	else if($params["BX_COMMAND"] == "add_history" || $params["COMMAND"] == "AddCallHistory")
	{
		CVoxImplantHistory::WriteToLog($params, 'PORTAL ADD HISTORY');

		if (isset($params['PORTAL_NUMBER']) && isset($params['ACCOUNT_SEARCH_ID']))
		{
			$params['PORTAL_NUMBER'] = $params['ACCOUNT_SEARCH_ID'];
		}

		CVoxImplantHistory::Add($params);

		if (isset($params["balance"]))
		{
			$ViAccount = new CVoxImplantAccount();
			$ViAccount->SetAccountBalance($params["balance"]);
		}

		echo "200 OK";
	}
	elseif($params["COMMAND"] == "IncomingGetConfig")
	{
		$result = CVoxImplantIncoming::GetConfigBySearchId($params['PHONE_NUMBER']);
		CVoxImplantHistory::WriteToLog($result, 'PORTAL GET INCOMING CONFIG');

		if ($result['ID'])
		{
			$result = CVoxImplantIncoming::RegisterCall($result, $params);
		}

		$isNumberInBlacklist = CVoxImplantIncoming::IsNumberInBlackList($params["CALLER_ID"]);
		$isBlacklistAutoEnable = Bitrix\Main\Config\Option::get("voximplant", "blacklist_auto", "N") == "Y";

		if ($result["WORKTIME_SKIP_CALL"] == "Y" && !$isNumberInBlacklist && $isBlacklistAutoEnable)
		{
			$isNumberInBlacklist = CVoxImplantIncoming::CheckNumberForBlackList($params["CALLER_ID"]);
		}

		if ($isNumberInBlacklist)
		{
			$result["NUMBER_IN_BLACKLIST"] = "Y";
		}

		if (!CVoxImplantAccount::IsPro())
		{
			$result["CRM_SOURCE"] = 'CALL';
			$result["CALL_VOTE"] = 'N';
	
			if ($result["QUEUE_TYPE"] == CVoxImplantConfig::QUEUE_TYPE_ALL)
			{
				$result["QUEUE_TYPE"] = CVoxImplantConfig::QUEUE_TYPE_EVENLY;
				$result["NO_ANSWER_RULE"] = CVoxImplantIncoming::RULE_VOICEMAIL;
			}
		}

		foreach(GetModuleEvents("voximplant", "onCallInit", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, Array(Array(
				'CALL_ID' => $params['CALL_ID'],
				'CALL_TYPE' => 2,
				'ACCOUNT_SEARCH_ID' => $params['ACCOUNT_SEARCH_ID'],
				'PHONE_NUMBER' => $params['PHONE_NUMBER'],
				'CALLER_ID' => $params['CALLER_ID'],
			)));
		}

		echo Json::encode($result);
	}
	elseif($params["COMMAND"] == "OutgoingGetConfig")
	{
		$result = CVoxImplantOutgoing::GetConfigByUserId($params['USER_ID']);
		CVoxImplantHistory::WriteToLog($result, 'PORTAL GET OUTGOING CONFIG');

		echo Json::encode($result);
	}


	// CONTROLLER HITS
	elseif (isset($params['BX_TYPE']))
	{
		if($params["COMMAND"] == "AddPhoneNumber")
		{
			$result = CVoxImplantConfig::AddConfigBySearchId($params['PHONE_NUMBER'], $params['COUNTRY_CODE']);

			CVoxImplantHistory::WriteToLog($result, 'CONTROLLER ADD NEW PHONE NUMBER');

			echo Json::encode($result);
		}
		elseif($params["COMMAND"] == "UnlinkExpirePhoneNumber")
		{
			$result = CVoxImplantConfig::DeleteConfigBySearchId($params['PHONE_NUMBER']);
			CVoxImplantHistory::WriteToLog($result, 'CONTROLLER UNLINK EXPIRE PHONE NUMBER');

			echo Json::encode($result);
		}
		elseif($params["COMMAND"] == "UpdateOperatorRequest")
		{
			$params['OPERATOR_CONTRACT'] = \Bitrix\Main\Text\Encoding::convertEncodingToCurrent($params['OPERATOR_CONTRACT']);
			CVoxImplantPhoneOrder::Update($params);

			$result = Array('RESULT' => 'OK');
			CVoxImplantHistory::WriteToLog($result, 'UPDATE OPERATOR REQUEST');

			echo Json::encode($result);
		}
		else if($params["COMMAND"] == "ExternalHungup")
		{
			$res = Bitrix\Voximplant\CallTable::getList(Array(
				'filter' => Array('=CALL_ID' => $params['CALL_ID_TMP']),
			));
			if ($call = $res->fetch())
			{
				Bitrix\Voximplant\CallTable::delete($call['ID']);

				CVoxImplantOutgoing::SendPullEvent(Array(
					'COMMAND' => 'timeout',
					'USER_ID' => $call['USER_ID'],
					'CALL_ID' => $call['CALL_ID'],
					'FAILED_CODE' => intval($params['CALL_FAILED_CODE']),
					'MARK' => 'timeout_hit_7'
				));
				CVoxImplantHistory::WriteToLog($call, 'EXTERNAL CALL HANGUP');
			}
		}
		else if($params["COMMAND"] == "VerifyResult")
		{
			$params['REVIEWER_COMMENT'] = \Bitrix\Main\Text\Encoding::convertEncodingToCurrent($params['REVIEWER_COMMENT']);

			$ViDocs = new CVoxImplantDocuments();
			$ViDocs->SetVerifyResult($params);
			$ViDocs->notifyUserWithVerifyResult($params);
		}
		else if($params["COMMAND"] == "SetSipStatus")
		{
			$sipStatus = ($params["SIP_PAID"] === 'Y');
			CVoxImplantConfig::SetModeStatus(CVoxImplantConfig::MODE_SIP, $sipStatus);
			CVoxImplantHistory::WriteToLog('Sip status set');
		}
		else if($params["COMMAND"] == "AddressVerified")
		{
			$addressVerification = new \Bitrix\VoxImplant\AddressVerification();
			$addressVerification->notifyUserWithVerifyResult($params);
		}
	}
	else
	{
		CVoxImplantHistory::WriteToLog('Command not found');
		echo "Requested command is not found.";
	}
}
else
{
	CVoxImplantHistory::WriteToLog('request not authorized');
	echo "You don't have access to this page.";
}

CMain::FinalActions();
die();