<?
/**
 * @internal
 * @access private
 *
 * Add this action as agent in module updater like:
 *
	if (IsModuleInstalled('tasks'))
	{
		CAgent::AddAgent('\Bitrix\Tasks\Util\DisposableAction::restoreReplicationAgents();', 'tasks', 'N', 100, '', 'Y', '', 100, false, false);
	}
 *
 */

namespace Bitrix\Tasks\Util;

class DisposableAction
{
	public static function restoreReplicationAgents()
	{
		global $DB;

		$tasks = array();
		$res = $DB->query("
			select T.ID, TT.ID as TT_ID, TT.REPLICATE_PARAMS as REPLICATE_PARAMS, TT.TPARAM_REPLICATION_COUNT as TT_TPARAM_REPLICATION_COUNT, TT.CREATED_BY as TT_CREATED_BY from b_tasks T
				inner join b_tasks_template TT on T.ID = TT.TASK_ID
			where T.ZOMBIE != 'Y' and T.REPLICATE = 'Y' and T.FORKED_BY_TEMPLATE_ID is null
		");
		while($item = $res->fetch())
		{
			$tasks[$item['ID']] = $item;
		}

		$agents = array();
		$res = $DB->query("select NAME from b_agent where MODULE_ID = 'tasks' and ACTIVE = 'Y'");
		while($item = $res->fetch())
		{
			$found = array();

			if(preg_match('#^CTasks::RepeatTaskByTemplateId\((\d+)#', $item['NAME'], $found))
			{
				$templateId = intval($found[1]);
				if($templateId)
				{
					$agents[$templateId] = $item;
				}
			}
		}

		foreach($tasks as $taskId => $taskData)
		{
			if(!array_key_exists($taskData['TT_ID'], $agents))
			{
				$name = 'CTasks::RepeatTaskByTemplateId('.$taskData['TT_ID'].');';

				$nextTime = \CTasks::getNextTime(unserialize($taskData['REPLICATE_PARAMS']), array(
					'ID' => $taskData['TT_ID'],
					'CREATED_BY' => $taskData['TT_CREATED_BY'],
					'TPARAM_REPLICATION_COUNT' => $taskData['TT_TPARAM_REPLICATION_COUNT'],
				));
				if ($nextTime)
				{
					\CAgent::AddAgent(
						$name,
						'tasks',
						'N', 		// is periodic?
						86400, 		// interval (24 hours)
						$nextTime, 	// datecheck
						'Y', 		// is active?
						$nextTime	// next_exec
					);
				}
			}
		}
	}
}