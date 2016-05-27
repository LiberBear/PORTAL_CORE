<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Marta extends Base
{
	const BOT_CODE = "marta";
	const INSTALL_WITH_MODULE = false;

	public static function register(array $params = Array())
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$agentMode = isset($params['AGENT']) && $params['AGENT'] == 'Y';

		if (self::getBotId())
			return $agentMode? "": self::getBotId();

		$birthday = new \Bitrix\Main\Type\DateTime(Loc::getMessage('IMBOT_BOT_BIRTHDAY').' 19:45:00', 'Y-m-d H:i:s');
		$birthday = $birthday->format(\Bitrix\Main\Type\Date::convertFormatToPhp(\CSite::GetDateFormat('SHORT')));

		$botId = \Bitrix\Im\Bot::register(Array(
			'CODE' => self::BOT_CODE,
			'TYPE' => \Bitrix\Im\Bot::TYPE_HUMAN,
			'MODULE_ID' => self::MODULE_ID,
			'CLASS' => __CLASS__,
			'INSTALL_TYPE' => \Bitrix\Im\Bot::INSTALL_TYPE_SILENT,
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',
			'METHOD_WELCOME_MESSAGE' => 'onChatStart',
			'METHOD_BOT_DELETE' => 'onBotDelete',
			'PROPERTIES' => Array(
				'NAME' => Loc::getMessage('IMBOT_BOT_NAME'),
				'LAST_NAME' => Loc::getMessage('IMBOT_BOT_LAST_NAME'),
				'COLOR' => Loc::getMessage('IMBOT_BOT_COLOR'),
				'EMAIL' => Loc::getMessage('IMBOT_BOT_EMAIL'),
				'PERSONAL_BIRTHDAY' => $birthday,
				'WORK_POSITION' => Loc::getMessage('IMBOT_BOT_WORK_POSITION'),
				'PERSONAL_WWW' => Loc::getMessage('IMBOT_BOT_SITE'),
				'PERSONAL_GENDER' => Loc::getMessage('IMBOT_BOT_GENDER'),
				'PERSONAL_PHOTO' => self::uploadAvatar(),
			)
		));
		if ($botId)
		{
			self::setBotId($botId);

			$eventManager = \Bitrix\Main\EventManager::getInstance();
			$eventManager->registerEventHandlerCompatible("main", "OnAfterUserAuthorize", self::MODULE_ID, __CLASS__,  "onAfterUserAuthorize");
			$eventManager->registerEventHandlerCompatible("timeman", "OnAfterTMDayStart", self::MODULE_ID, __CLASS__,  "onAfterTmDayStart");

			\Bitrix\Im\Command::register(Array(
				'MODULE_ID' => self::MODULE_ID,
				'BOT_ID' => $botId,
				'COMMAND' => 'tictactoe',
				'CLASS' => __CLASS__,
				'METHOD_COMMAND_ADD' => 'onCommandAdd'
			));
		}

		return $agentMode? "": $botId;
	}

	public static function unRegister()
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$result = \Bitrix\Im\Bot::unRegister(Array('BOT_ID' => self::getBotId()));
		if ($result)
		{
			self::setBotId(0);

			$eventManager = \Bitrix\Main\EventManager::getInstance();
			$eventManager->unRegisterEventHandler("main", "OnAfterUserAuthorize", self::MODULE_ID, __CLASS__, "onAfterUserAuthorize");
			$eventManager->unRegisterEventHandler("timeman", "OnAfterTMDayStart", self::MODULE_ID, __CLASS__, "onAfterTmDayStart");
		}

		return $result;
	}

	public static function onChatStart($dialogId, $joinFields)
	{
		if ($joinFields['CHAT_TYPE'] == IM_MESSAGE_PRIVATE)
		{
			if (isset($_SESSION['USER_LAST_CHECK_MARTA_'.$dialogId]))
			{
				return true;
			}

			$message = Loc::getMessage('IMBOT_BOT_WELCOME_MESSAGE');
			\CUserOptions::SetOption(self::MODULE_ID, self::BOT_CODE.'_welcome_message', time(), false, $dialogId);
		}
		else
		{
			$message = Loc::getMessage('IMBOT_BOT_WELCOME_MESSAGE_CHAT');
		}

		if ($message)
		{
			\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => self::getBotId()), $dialogId);
			self::sendAnswer(0, Array(
				'DIALOG_ID' => $dialogId,
				'ANSWER' => $message
			));
		}

		return true;
	}

	public static function onMessageAdd($messageId, $messageFields)
	{
		if ($messageFields['SYSTEM'] == 'Y')
			return false;

		\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => self::getBotId()), $messageFields['DIALOG_ID']);

		$userName = \Bitrix\Im\User::getInstance($messageFields['FROM_USER_ID'])->getName();

		$dateNow = new \Bitrix\Main\Type\DateTime();
		self::setBotOption($messageFields['FROM_USER_ID'], 'last_message', $dateNow->format('Ymd'));

		self::sendMessage(Array(
			'BOT_ID' => self::getBotId(),
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE_ID' => $messageId,
			'MESSAGE_TEXT' => $messageFields['MESSAGE'],
			'MESSAGE_TYPE' => $messageFields['MESSAGE_TYPE'],
			'USER_NAME' => htmlspecialcharsback($userName),
			'USER_AGE' => 30,
		));

		return true;
	}

	public static function onCommandAdd($messageId, $messageFields)
	{
		if ($messageFields['SYSTEM'] == 'Y')
			return false;

		if ($messageFields['COMMAND_CONTEXT'] == 'TEXTAREA')
		{
			if (
				$messageFields['MESSAGE_TYPE'] != IM_MESSAGE_PRIVATE ||
				$messageFields['FROM_USER_ID'] == self::getBotId() ||
				$messageFields['TO_USER_ID'] == self::getBotId()
			)
			{
				\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => self::getBotId()), $messageFields['DIALOG_ID']);
			}
		}

		$dateNow = new \Bitrix\Main\Type\DateTime();
		self::setBotOption($messageFields['FROM_USER_ID'], 'last_message', $dateNow->format('Ymd'));

		self::sendCommand(Array(
			'BOT_ID' => self::getBotId(),
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE_ID' => $messageId,
			'MESSAGE_TEXT' => $messageFields['MESSAGE'],
			'MESSAGE_TYPE' => $messageFields['MESSAGE_TYPE'],
			'COMMAND' => $messageFields['COMMAND'],
			'COMMAND_ID' => $messageFields['COMMAND_ID'],
			'COMMAND_PARAMS' => $messageFields['COMMAND_PARAMS'],
			'COMMAND_CONTEXT' => $messageFields['COMMAND_CONTEXT'],
		));

		return true;
	}


	public static function onAnswerAdd($command, $params)
	{
		if($command == "AnswerMessage")
		{
			self::sendAnswer($params['MESSAGE_ID'], Array(
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE' => $params['MESSAGE'],
				'ANSWER' => $params['MESSAGE_ANSWER'],
				'RICH' => $params['MESSAGE_RICH'],
				'ATTACH' => isset($params['ATTACH'])? $params['ATTACH']: '',
				'KEYBOARD' => isset($params['KEYBOARD'])? $params['KEYBOARD']: '',
				'ANSWER_URL' => $params['MESSAGE_URL']? $params['MESSAGE_URL']: '',
			));
			$result = Array('RESULT' => 'OK');
		}
		else if($command == "AnswerCommand")
		{
			self::sendAnswerCommand($params['MESSAGE_ID'], Array(
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE' => $params['MESSAGE'],
				'MESSAGE_ANSWER' => $params['MESSAGE_ANSWER'],
				'ATTACH' => isset($params['ATTACH'])? $params['ATTACH']: '',
				'KEYBOARD' => isset($params['KEYBOARD'])? $params['KEYBOARD']: '',
				'MESSAGE_ID' => $params['MESSAGE_ID']? intval($params['MESSAGE_ID']): 0,
				'COMMAND_ID' => $params['COMMAND_ID']? intval($params['COMMAND_ID']): 0,
				'COMMAND_CONTEXT' => $params['COMMAND_CONTEXT']? $params['COMMAND_CONTEXT']: 'TEXTAREA',
			));
			$result = Array('RESULT' => 'OK');
		}
		else
		{
			$result = new \Bitrix\ImBot\Error(__METHOD__, 'UNKNOWN_COMMAND', 'Command isnt found');
		}

		return $result;
	}

	public static function onAfterUserAuthorize($params)
	{
		$auth = \CHTTP::ParseAuthRequest();
		if (
			isset($auth["basic"]) && $auth["basic"]["username"] <> '' && $auth["basic"]["password"] <> ''
			&& strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'bitrix') === false
		)
		{
			return true;
		}

		if (isset($params['update']) && $params['update'] === false)
			return true;

		if ($params['user_fields']['ID'] <= 0)
			return true;

		$params['user_fields']['ID'] = intval($params['user_fields']['ID']);

		if (isset($_SESSION['USER_LAST_CHECK_MARTA_'.$params['user_fields']['ID']]))
			return true;

		$martaCheck = \CUserOptions::GetOption(self::MODULE_ID, self::BOT_CODE.'_welcome_message', 0, $params['user_fields']['ID']);
		if ($martaCheck > 0)
		{
			$_SESSION['USER_LAST_CHECK_MARTA_'.$params['user_fields']['ID']] = $martaCheck;
			//$dateNow = new \Bitrix\Main\Type\DateTime();
			//if (self::getBotOption($params['user_fields']['ID'], 'planner_message', 0) < $dateNow->format('Ymd'))
			//{
			//	\CAgent::AddAgent('\\Bitrix\\ImBot\\Bot\\Marta::addPlannerMessageAgent('.$params['user_fields']['ID'].');', "imbot", "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL"));
			//}
		}
		else
		{
			\CAgent::AddAgent('\\Bitrix\\ImBot\\Bot\\Marta::addWelcomeMessageAgent('.$params['user_fields']['ID'].');', "imbot", "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL"));
		}

		return true;
	}

	public static function onAfterTmDayStart($params)
	{
		//$dateNow = new \Bitrix\Main\Type\DateTime();
		//if (self::getBotOption($params['USER_ID'], 'planner_message', 0) < $dateNow->format('Ymd'))
		//{
		//	\CAgent::AddAgent('\\Bitrix\\ImBot\\Bot\\Marta::addPlannerMessageAgent('.$params['USER_ID'].');', "imbot", "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL"));
		//}
		self::notifyAboutPlans($params['USER_ID'], $params['USER_ID']);
	}

	public static function addWelcomeMessageAgent($userId)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return "";

		if (\CUserOptions::GetOption(self::MODULE_ID, self::BOT_CODE.'_welcome_message', 0, $userId) == 0)
		{
			if (!\Bitrix\Main\Loader::includeModule('im'))
				return "";

			if (\Bitrix\Im\User::getInstance($userId)->isExists() && \Bitrix\Im\User::getInstance($userId)->isExtranet())
			{
				\CUserOptions::SetOption(self::MODULE_ID, self::BOT_CODE.'_welcome_message', time(), false, $userId);
				$_SESSION['USER_LAST_CHECK_MARTA_'.$userId] = time();

				return "";
			}

			$userData = \Bitrix\Main\UserTable::getById($userId)->fetch();
			if (in_array(Array('email', 'bot', 'network'), $userData['EXTERNAL_AUTH_ID']))
			{
				\CUserOptions::SetOption(self::MODULE_ID, self::BOT_CODE.'_welcome_message', time(), false, $userId);
				$_SESSION['USER_LAST_CHECK_MARTA_'.$userId] = time();

				return "";
			}

			if (is_object($userData['DATE_REGISTER']) && time() - $userData['DATE_REGISTER']->getTimestamp() < 86400)
			{
				if ($userId == 1)
				{
					$message = Loc::getMessage('IMBOT_BOT_WELCOME_NEW_B24');
				}
				else
				{
					$message = Loc::getMessage('IMBOT_BOT_WELCOME_NEW_USER');
				}
			}
			else
			{
				$userName = !empty($userData['NAME'])? $userData['NAME']: $userData['LAST_MAME'];
				if (empty($userName))
				{
					$userData['LOGIN'];
				}
				$message = Loc::getMessage('IMBOT_BOT_WELCOME_AUTH_USER', Array('#USER_NAME#' => $userName));
			}

			\CUserOptions::SetOption(self::MODULE_ID, self::BOT_CODE.'_welcome_message', time(), false, $userId);
			$_SESSION['USER_LAST_CHECK_MARTA_'.$userId] = time();

			self::sendAnswer(0, Array(
				'DIALOG_ID' => $userId,
				'ANSWER' => $message.'[br]'.Loc::getMessage('IMBOT_BOT_WELCOME_MESSAGE')
			));
		}

		return "";
	}

	public static function addPlannerMessageAgent($userId)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return "";

		self::notifyAboutPlans($userId, $userId);

		return "";
	}

	private static function notifyAboutPlans($dialogId, $userId)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$userData = \Bitrix\Im\User::getInstance($userId);
		if (!$userData || $userData->isExtranet())
			return false;

		$dateNow = new \Bitrix\Main\Type\DateTime();
		if (self::getBotOption($userId, 'planner_message', 0) < $dateNow->format('Ymd'))
		{
			self::setBotOption($userId, 'planner_message', $dateNow->format('Ymd'));
		}
		else
		{
			return false;
		}

		$welcomeMessage = '';

		$dateNow = new \Bitrix\Main\Type\DateTime();
		if (self::getBotOption($userId, 'last_message', 0) < $dateNow->format('Ymd'))
		{
			$welcomeMessage = self::getHelloMessage($userId).' :) [br]';
		}

		$answer = '';
		$attaches = Array();
		if (\Bitrix\Main\Loader::includeModule('intranet') && \Bitrix\Main\Loader::includeModule('calendar'))
		{
        	$calendarUrl = \CCalendar::GetPathForCalendarEx($userId);
			$calendarEventUrl = $calendarUrl.((strpos($calendarUrl, "?") === false) ? '?' : '&').'EVENT_ID=';

			$planner = \CIntranetPlanner::getData('s1', true);
			//$planner = \CIntranetPlanner::getData('s1', true, $userId); // TODO replace this

			$attach = new \CIMMessageParamAttach(1, \CIMMessageParamAttach::CHAT);
			foreach ($planner['DATA']['EVENTS'] as $event)
			{
				if ($event['TIME_FROM'] == $event['TIME_TO'] && $event['TIME_FROM'] == '00:00')
				{
					$eventTimeFormatted = Loc::getMessage('IMBOT_BOT_MESSAGE_CALEND_4');
				}
				else
				{
					$eventTimeFormatted = Loc::getMessage('IMBOT_BOT_MESSAGE_CALEND_3', Array('#TIME_1#' => $event['TIME_FROM'], '#TIME_2#' => $event['TIME_TO']));
				}
				$attach->AddGrid(Array(
					Array(
						"VALUE" => $eventTimeFormatted,
						"LINK" => $calendarEventUrl.$event['ID'],
						"DISPLAY" => "LINE",
						"WIDTH" => 100,
					),
					Array(
						"VALUE" => $event['NAME'],
						"DISPLAY" => "LINE"
					),
				));
			}
			if (!$attach->IsEmpty())
			{
				$answer .= Loc::getMessage('IMBOT_BOT_MESSAGE_CALEND_1').'[BR][ATTACH=1][BR]';
				$attaches[] = $attach;
			}
		}

		if (\Bitrix\Main\Loader::includeModule('tasks'))
		{
			try
			{
				$tasksCounter = \CTaskListCtrl::getMainCounterForUser($userId);
				if ($tasksCounter > 0)
				{
					$tasksUrl = \CTaskCountersNotifier::getTasksListLink($userId);

					$pluralForm = \CTasksTools::getPluralForm($tasksCounter, true);
					if ($pluralForm !== false)
						$taskMessage = 'IMBOT_BOT_MESSAGE_TASKS_'.($pluralForm+1);
					else
						$taskMessage = 'IMBOT_BOT_MESSAGE_TASKS_2';

					$answer = $answer.Loc::getMessage($taskMessage, Array('#TASKS_COUNT#' => $tasksCounter, '#URL_START#' => '[URL='.$tasksUrl.']', '#URL_END#' => '[/URL]'));
				}
			}
			catch (\Exception $e)
			{}
		}

		if ($answer)
		{
			$answer = $welcomeMessage.'[br]'.$answer;

			\Bitrix\Im\Bot::startWriting(Array('BOT_ID' => self::getBotId()), $dialogId);

			\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => self::getBotId()), Array(
				'DIALOG_ID' => $dialogId,
				'MESSAGE' => $answer,
				'ATTACH' => $attaches,
			));
		}

		return true;
	}

	private static function getHelloMessage($userId)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$userName = \Bitrix\Im\User::getInstance($userId)->getName();
		if (!$userName)
			return "";

		$dateNow = new \Bitrix\Main\Type\DateTime();
		$hour = $dateNow->format('H');

		if ($hour >= 18 && $hour <= 23 || $hour >= 0 && $hour < 5)
		{
			$message = Loc::getMessage('IMBOT_BOT_MESSAGE_HELLO_EVENING', Array('#USER_NAME#' => $userName));
		}
		else if ($hour >= 5 && $hour < 12)
		{
			$message = Loc::getMessage('IMBOT_BOT_MESSAGE_HELLO_MORNING', Array('#USER_NAME#' => $userName));
		}
		else
		{
			$message = Loc::getMessage('IMBOT_BOT_MESSAGE_HELLO_DAY', Array('#USER_NAME#' => $userName));
		}

		return $message;
	}

	public static function sendAnswer($messageId, $messageFields)
	{
		if ($messageFields['ANSWER_URL'])
		{
			$messageFields['ANSWER'] = ' '.$messageFields['ANSWER_URL'];
		}

		\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => self::getBotId()), Array(
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE' => $messageFields['ANSWER'],
			'ATTACH' => $messageFields['ATTACH'],
			'KEYBOARD' => $messageFields['KEYBOARD'],
			'URL_PREVIEW' => isset($messageFields['RICH'])? $messageFields['RICH']: "Y"
		));
	}

	public static function sendAnswerCommand($messageId, $messageFields)
	{
		$attach = Array();
		if (!empty($messageFields['ATTACH']))
		{
			$attach = \CIMMessageParamAttach::GetAttachByJson($messageFields['ATTACH']);
		}

		$keyboard = Array();
		if (!empty($messageFields['KEYBOARD']))
		{
			$keyboard = Array('BOT_ID' => self::getBotId());
			if (!isset($messageFields['KEYBOARD']['BUTTONS']))
			{
				$keyboard['BUTTONS'] = $messageFields['KEYBOARD'];
			}
			else
			{
				$keyboard = $messageFields['KEYBOARD'];
			}
			$keyboard = \Bitrix\Im\Bot\Keyboard::getKeyboardByJson($keyboard);
		}

		$messageParams = Array(
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE' => $messageFields['MESSAGE_ANSWER'],
			'ATTACH' => $attach,
			'KEYBOARD' => $keyboard
		);

		if ($messageFields['COMMAND_ID'] > 0)
		{
			if ($messageFields['COMMAND_CONTEXT'] == 'KEYBOARD')
			{
				\CIMMessageParam::Set($messageFields['MESSAGE_ID'], Array('KEYBOARD' => $keyboard? $keyboard: 'N'));
				\CIMMessageParam::Set($messageFields['MESSAGE_ID'], Array('ATTACH' => $attach? $attach: Array()));

				if (!empty($messageParams['MESSAGE']))
				{
					\CIMMessenger::Update($messageFields['MESSAGE_ID'], $messageParams['MESSAGE'], true, false, self::getBotId());
				}
				else
				{
					\CIMMessageParam::SendPull($messageFields['MESSAGE_ID']);
				}
			}
			else
			{
				\Bitrix\Im\Command::addMessage(Array('MESSAGE_ID' => $messageFields['MESSAGE_ID'], 'COMMAND_ID' => $messageFields['COMMAND_ID']), $messageParams);
			}
		}
		else
		{
			\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => self::getBotId()), $messageParams);
		}
	}

	private static function sendMessage($params)
	{
		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$query = $http->query(
			'SendMessage',
			$params
		);
		if (isset($query->error))
		{
			self::$lastError = new \Bitrix\ImBot\Error(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	private static function sendCommand($params)
	{
		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$query = $http->query(
			'SendCommand',
			$params
		);
		if (isset($query->error))
		{
			self::$lastError = new \Bitrix\ImBot\Error(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public static function getLangMessage($messageCode = '')
	{
		return Loc::getMessage($messageCode);
	}
}