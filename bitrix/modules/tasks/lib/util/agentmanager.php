<?
/**
 * Class contains agent functions. Place all new agents here.
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Util;

final class AgentManager
{
	public static function notificationThrottleRelease()
	{
		\CTaskNotifications::throttleRelease();

		return '\\'.__CLASS__."::notificationThrottleRelease();";
	}

	public static function sendReminder()
	{
		\CTaskReminders::SendAgent();

		return '\\'.__CLASS__."::sendReminder();";
	}

	public static function checkAgentIsAlive($name, $interval)
	{
		$name = '\\'.__CLASS__.'::'.$name.'();';

		$agent = \CAgent::GetList(array(), array('MODULE_ID' => 'tasks', 'NAME' => $name))->fetch();
		if(!$agent['ID'])
		{
			\CAgent::AddAgent(
				$name,
				'tasks',
				'N', // dont care about how many times agent rises
				$interval
			);
		}
	}
}