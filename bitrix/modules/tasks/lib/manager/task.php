<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 * 
 * This class should be used in components, inside agent functions, in rest, ajax and more, bringing unification to all places and processes
 */

namespace Bitrix\Tasks\Manager;

use \Bitrix\Main\Loader;

use \Bitrix\Tasks\Util\Error\Collection;
use \Bitrix\Tasks\Util\Type;

use \Bitrix\Tasks\Manager\Task\Checklist;
use \Bitrix\Tasks\Manager\Task\Reminder;
use \Bitrix\Tasks\Manager\Task\Template;
use \Bitrix\Tasks\Manager\Task\ProjectDependence;
use \Bitrix\Tasks\Manager\Task\RelatedTask;
use \Bitrix\Tasks\Manager\Task\Tag;
use \Bitrix\Tasks\Manager\Task\Log;
use \Bitrix\Tasks\Manager\Task\ParentTask;
use \Bitrix\Tasks\Manager\Task\Project;
//use \Bitrix\Tasks\Manager\Task\TimeManager;
use \Bitrix\Tasks\Manager\Task\ElapsedTime;
use \Bitrix\Tasks\Manager\Task\Responsible;
use \Bitrix\Tasks\Manager\Task\Auditor;
use \Bitrix\Tasks\Manager\Task\Accomplice;
use \Bitrix\Tasks\Manager\Task\Originator;

final class Task extends \Bitrix\Tasks\Manager
{
	const LIMIT_PAGE_SIZE = 50;

	/**
	 * @param int $userId
	 * @param mixed[] $data
	 * @param mixed[] $parameters
	 * 		<li>PUBLIC_MODE
	 * 		<li>SOURCE
	 * 			<li> TYPE (TEMPLATE or TASK)
	 * 			<li> ID
	 */
	public static function add($userId, array $data, array $parameters = array('PUBLIC_MODE' => false, 'RETURN_ENTITY' => false))
	{
		$errors = static::ensureHaveErrorCollection($parameters);

		if($parameters['PUBLIC_MODE'])
		{
			$data = static::filterData($data, static::getFieldMap(), $errors);
		}
		if($errors->checkNoFatals())
		{
			$cacheAFWasDisabled = \CTasks::disableCacheAutoClear();
			$notifADWasDisabled = \CTaskNotifications::disableAutoDeliver();

			$task = static::doAdd($userId, $data, $parameters);

			if($notifADWasDisabled)
			{
				\CTaskNotifications::enableAutoDeliver();
			}
			if($cacheAFWasDisabled)
			{
				\CTasks::enableCacheAutoClear();
			}

			if($errors->checkNoFatals())
			{
				$data = array('ID' => $task->getId());

				if($parameters['RETURN_ENTITY'])
				{
					$data = $task->getData(false);
					$can = static::translateAllowedActionNames($task->getAllowedActions(true));
				}
			}
		}

		return array(
			'TASK' => $task,
			'ERRORS' => $errors,
			'DATA' => $data,
			'CAN' => $can
		);
	}

	public static function update($userId, $taskId, array $data, array $parameters = array('PUBLIC_MODE' => false, 'RETURN_ENTITY' => false))
	{
		$errors = static::ensureHaveErrorCollection($parameters);

		if($parameters['PUBLIC_MODE'])
		{
			$data = static::filterData($data, static::getFieldMap(), $errors);
		}

		if($errors->checkNoFatals())
		{
			$cacheAFWasDisabled = \CTasks::disableCacheAutoClear();
			$notifADWasDisabled = \CTaskNotifications::disableAutoDeliver();

			$updateParams = array(
				'TASK_ACTION_UPDATE_PARAMETERS' => array(
					'THROTTLE_MESSAGES' => $parameters['THROTTLE_MESSAGES']
				),
				'PUBLIC_MODE' => $parameters['PUBLIC_MODE'],
				'ERRORS' => $errors
			);

			$task = static::doUpdate($userId, $taskId, $data, $updateParams);

			if($notifADWasDisabled)
			{
				\CTaskNotifications::enableAutoDeliver();
			}
			if($cacheAFWasDisabled)
			{
				\CTasks::enableCacheAutoClear();
			}

			if($errors->checkNoFatals())
			{
				$data = array('ID' => $task->getId());

				if($parameters['RETURN_ENTITY'])
				{
					$data = $task->getData(false);
					$can = static::translateAllowedActionNames($task->getAllowedActions(true));
				}
			}
		}

		return array(
			'TASK' => $task,
			'ERRORS' => $errors,
			'DATA' => $data,
			'CAN' => $can
		);
	}

	public static function get($userId, $taskId, array $parameters = array())
	{
		$errors = static::ensureHaveErrorCollection($parameters);

		// todo: filterArguments() and filterResult() here on public mode?

		$data = static::getBasicData($userId, $taskId, $parameters);
		$can = array();

		if($errors->checkNoFatals())
		{
			$can = array(static::ACT_KEY => &$data[static::ACT_KEY]); // for compatibility

			// select sub-entity related data

			if(!is_array($parameters['ENTITY_SELECT']))
			{
				// by default none is selected
				$parameters['ENTITY_SELECT'] = array();
				// could be of static::getLegalSubEntities()
			}
			$entitySelect = array_flip($parameters['ENTITY_SELECT']);

			Originator::formatSet($data);
			Auditor::formatSet($data);
			Accomplice::formatSet($data);
			ParentTask::formatSet($data);
			Project::formatSet($data);

			// special case: responsibles
			$data[Responsible::getCode(true)] = array(array('ID' => $data['RESPONSIBLE_ID']));

			$code = Tag::getCode(true);
			if(isset($entitySelect[Tag::getCode()]))
			{
				$mgrResult = Tag::getList($userId, $taskId);
				$data[$code] = $mgrResult['DATA'];
				if(!empty($mgrResult['CAN']))
				{
					$can[$code] = $mgrResult['CAN'];
				}

				Tag::adaptSet($data); // for compatibility
			}

			$code = Checklist::getCode(true);
			if(isset($entitySelect['CHECKLIST']))
			{
				$mgrResult = CheckList::getListByParentEntity($userId, $taskId, $parameters);
				$data[$code] = $mgrResult['DATA'];
				if(!empty($mgrResult['CAN']))
				{
					$can[$code] = $mgrResult['CAN'];
				}
			}

			if(isset($entitySelect['REMINDER']))
			{
				$mgrResult = Reminder::getListByParentEntity($userId, $taskId, $parameters);
				$data[static::SE_PREFIX.'REMINDER'] = $mgrResult['DATA'];
				if(!empty($mgrResult['CAN']))
				{
					$can[static::SE_PREFIX.'REMINDER'] = $mgrResult['CAN'];
				}
			}

			if(isset($entitySelect['LOG']))
			{
				$mgrResult = Log::getListByParentEntity($userId, $taskId, $parameters);
				$data[static::SE_PREFIX.'LOG'] = $mgrResult['DATA'];
				if(!empty($mgrResult['CAN']))
				{
					$can[static::SE_PREFIX.'LOG'] = $mgrResult['CAN'];
				}
			}

			if(isset($entitySelect['ELAPSEDTIME']))
			{
				$mgrResult = ElapsedTime::getListByParentEntity($userId, $taskId, $parameters);
				$data[static::SE_PREFIX.'ELAPSEDTIME'] = $mgrResult['DATA'];
				if(!empty($mgrResult['CAN']))
				{
					$can[static::SE_PREFIX.'ELAPSEDTIME'] = $mgrResult['CAN'];
				}
			}

			if(isset($entitySelect['PROJECTDEPENDENCE']))
			{
				$mgrResult = ProjectDependence::getListByParentEntity($userId, $taskId, array_merge($parameters, array(
					'TYPE' => 				ProjectDependence::INGOING,
					'DIRECT' => 			true,
					'DEPENDS_ON_DATA' => 	true
				)));
				$data[static::SE_PREFIX.'PROJECTDEPENDENCE'] = $mgrResult['DATA'];
				if(!empty($mgrResult['CAN']))
				{
					$can[static::SE_PREFIX.'PROJECTDEPENDENCE'] = $mgrResult['CAN'];
				}
			}

			if(isset($entitySelect['TEMPLATE']))
			{
				if($data['REPLICATE'] == 'Y')
				{
					$template = Template::getByParentTask($userId, $taskId);
					$data[static::SE_PREFIX.'TEMPLATE'] = $template['DATA'];
				}
			}

			if(isset($entitySelect['TEMPLATE.SOURCE']))
			{
				if(intval($data['FORKED_BY_TEMPLATE_ID']))
				{
					$template = Template::get($userId, intval($data['FORKED_BY_TEMPLATE_ID']));

					// todo: remove this
					$tData = $template['DATA'];
					if(!empty($tData))
					{
						$tData = array(
							'ID' => 				$tData['ID'],
							'TITLE' => 				$tData['TITLE'],
							'TASK_ID' => 			$tData['TASK_ID'],
							'TPARAM_TYPE' => 		$tData['TPARAM_TYPE'],
							'REPLICATE_PARAMS' => 	$tData['REPLICATE_PARAMS']
						);
					}
					$data[static::SE_PREFIX.'TEMPLATE.SOURCE'] = $tData;
				}
			}

			if(isset($entitySelect['RELATEDTASK']))
			{
				$mgrResult = RelatedTask::getListByParentEntity($userId, $taskId, $parameters);
				$data[static::SE_PREFIX.'RELATEDTASK'] = $mgrResult['DATA'];
				if(!empty($mgrResult['CAN']))
				{
					$can[static::SE_PREFIX.'RELATEDTASK'] = $mgrResult['CAN'];
				}
			}

			if(isset($entitySelect['TIMEMANAGER']) || isset($entitySelect['DAYPLAN'])) // 'TIMEMANAGER' condition left for compatibility
			{
				$subData = array($data['ID'] => &$data);
				$subCan = array($data['ID'] => &$can);
				static::injectDayPlanFields($userId, $parameters, $subData, $subCan);
			}
		}

		return array(
			'DATA' => $data, 
			'CAN' => $can, // for compatibility
			'ERRORS' => $errors
		);
	}

	public static function getList($userId, array $parameters)
	{
		$data = array();
		$can = array();
		$aux = array(
			'GROUP_IDS' => array(),
			'USER_IDS' => array()
		);

		$errors = static::ensureHaveErrorCollection($parameters);

		$navParams = static::prepareNav(
			isset($parameters['LIST_PARAMETERS']['limit']) ? $parameters['LIST_PARAMETERS']['limit'] : false,
			isset($parameters['LIST_PARAMETERS']['offset']) ? $parameters['LIST_PARAMETERS']['offset'] : false,
			$parameters['PUBLIC_MODE']
		);

		$params = false;
		if(!empty($navParams))
		{
			$params = array('NAV_PARAMS' => $navParams);
		}

		// an exception about sql error may fall here
		// dont use CTaskItem::fetchList() here, its slower than the following
		list($items, $res) = \CTaskItem::fetchListArray(
			$userId,
			$parameters['LIST_PARAMETERS']['order'],
			$parameters['LIST_PARAMETERS']['legacyFilter'],
			$params,
			$parameters['LIST_PARAMETERS']['select']
		);

		if(is_array($items))
		{
			foreach($items as $taskData)
			{
				if((int) $taskData['GROUP_ID'])
				{
					$aux['GROUP_IDS'][(int) $taskData['GROUP_ID']] = true;
				}

				$data[$taskData['ID']] = $taskData;
				$data[$taskData['ID']]['ACTION'] = $can[$taskData['ID']]['ACTION'] = static::translateAllowedActionNames(\CTaskItem::getAllowedActionsArray($userId, $taskData, true));
			}
		}

		return array(
			'DATA' => $data, 
			'CAN' => $can, 
			'ERRORS' => $errors,
		);
	}

	public static function getAllowedActions($userId, $taskId)
	{
		$task = static::getTask($userId, $taskId);
		return static::translateAllowedActionNames($task->getAllowedActions(true));
	}

	public static function getFullRights($userId)
	{
		// get rights as creator, just EDIT for now
		return array(
			'EDIT' => true,
			'EDIT.PLAN' => true,
			'CHECKLIST.ADD' => true,
			'CHECKLIST.REORDER' => true,
			'EDIT.ORIGINATOR' => true,
			'FAVORITE.ADD' => true,
			'FAVORITE.DELETE' => false,
			'DAYPLAN.ADD' => !\Bitrix\Tasks\Integration\Extranet\User::isExtranet($userId)
		);
	}

	public static function extendData(&$data, array $references = array())
	{
		if(is_array($references['USER']))
		{
			Originator::extendData($data, $references['USER']);
			Responsible::extendData($data, $references['USER']);
			Auditor::extendData($data, $references['USER']);
			Accomplice::extendData($data, $references['USER']);
		}
		if(is_array($references['RELATED_TASK']))
		{
			RelatedTask::extendData($data, $references['RELATED_TASK']);
			ParentTask::extendData($data, $references['RELATED_TASK']);
			ProjectDependence::extendData($data, $references['RELATED_TASK']);
		}
		if(is_array($references['GROUP']))
		{
			Project::extendData($data, $references['GROUP']);
		}
	}

	public static function normalizeData($data)
	{
		if(!is_array($data) || empty($data))
		{
			return array();
		}

		foreach($data as $k => $v)
		{
			if($seName = static::checkIsSubEntityKey($k))
			{
				$fName = __NAMESPACE__.'\\Task\\'.$seName.'::normalizeData';
				if(is_callable($fName))
				{
					$data[$k] = call_user_func_array($fName, array($v));
				}
			}
		}

		return $data;
	}

	public static function mergeData($primary = array(), $secondary = array())
	{
		if(is_array($secondary) && is_array($primary))
		{
			foreach($secondary as $k => $v)
			{
				if(!array_key_exists($k, $primary) || $k == static::ACT_KEY) // force rights merging
				{
					$primary[$k] = $secondary[$k];
				}
				elseif($seName = static::checkIsSubEntityKey($k))
				{
					$fName = __NAMESPACE__.'\\Task\\'.$seName.'::mergeData';
					if(is_callable($fName))
					{
						$primary[$k] = call_user_func_array($fName, array($primary[$k], $secondary[$k]));
					}
				}
			}
		}

		return $primary;
	}

	private static function getFieldMap()
	{
		// READ, WRITE, SORT, FILTER, DATE
		$fieldMap = \CTasks::getPublicFieldMap();

		$fieldMap['REPLICATE'] = 		array(1, 1, 0, 0, 0); // not allowed in rest, but allowed here
		$fieldMap['MULTITASK'] = 		array(1, 1, 0, 0, 0); // not allowed in rest, but allowed here
		$fieldMap['ADD_TO_FAVORITE'] = 	array(0, 1, 0, 0, 0); // virtual, for add() only
		$fieldMap['ADD_TO_TIMEMAN'] = 	array(0, 1, 0, 0, 0); // virtual, for add() only

		$fieldMap['RESPONSIBLES'] = 	array(0, 1, 0, 0, 0); // just for compatibility

		return $fieldMap;
	}

	protected static function getLegalSubEntities()
	{
		static $legal;

		if($legal === null)
		{
			$legal = array(
				Originator::getCode(),
				Responsible::getCode(),
				Auditor::getCode(),
				Accomplice::getCode(),
				Checklist::getCode(),
				Reminder::getCode(),
				ElapsedTime::getCode(),
				Log::getCode(),
				ProjectDependence::getCode(),
				Template::getCode(),
				Tag::getCode(),
				RelatedTask::getCode(),
				'DAYPLAN',
				'TIMEMANAGER', // alias for DAYPLAN
			);
		}

		return $legal;
	}

	private static function inviteMembers(&$data, Collection $errors)
	{
		//Originator::inviteMembers($data, $errors); // we may not invite originator
		Auditor::inviteMembers($data, $errors);
		Accomplice::inviteMembers($data, $errors);
		Responsible::inviteMembers($data, $errors);
	}

	private static function adaptSet(&$data)
	{
		Originator::adaptSet($data);
		Auditor::adaptSet($data);
		Accomplice::adaptSet($data);
		Tag::adaptSet($data);
		RelatedTask::adaptSet($data);
		ParentTask::adaptSet($data);
		Project::adaptSet($data);

		// special case: responsibles
		Responsible::adaptSet($data);
		if(is_array($data[Responsible::getLegacyFieldName()]))
		{
			$data[Responsible::getLegacyFieldName()] = array_shift($data[Responsible::getLegacyFieldName()]);
		}
	}

	private static function doAdd($userId, array $data, array $parameters)
	{
		$errors = static::ensureHaveErrorCollection($parameters);

		$data = static::normalizeData($data);

		static::inviteMembers($data, $errors);
		static::adaptSet($data);

		static::ensureDatePlanChangeAllowed($userId, $data);

		$task = \CTaskItem::add(static::stripSubEntityData($data), $userId, array(
			'CLONE_DISK_FILE_ATTACHMENT' => true
		));
		$taskId = $task->getId();

		if ($taskId)
		{
			if(!\Bitrix\Tasks\Integration\Extranet\User::isExtranet($userId) && $data["ADD_TO_TIMEMAN"] == "Y")
			{
				// add the task to planner only if the user this method executed under is current and responsible for the task
				if($userId == $data['RESPONSIBLE_ID'] && $userId == \Bitrix\Tasks\Util\User::getId())
				{
					\CTaskPlannerMaintance::plannerActions(array('add' => array($taskId)));
				}
			}
			if($data["ADD_TO_FAVORITE"] == "Y")
			{
				$task->addToFavorite();
			}

			// add sub-entities (SE_*)
			$subEntityParams = array_merge(
				$parameters, array('MODE' => static::MODE_ADD)
			);

			if(array_key_exists(Reminder::getCode(true), $data))
			{
				Reminder::manageSet($userId, $taskId, $data[Reminder::getCode(true)], $subEntityParams);
			}

			if(array_key_exists(ProjectDependence::getCode(true), $data))
			{
				ProjectDependence::manageSet($userId, $taskId, $data[ProjectDependence::getCode(true)], $subEntityParams);
			}

			if(array_key_exists(Checklist::getCode(true), $data))
			{
				Checklist::manageSet($userId, $taskId, $data[Checklist::getCode(true)], $subEntityParams);
			}

			Template::manageTaskReplication($userId, $taskId, $data, $subEntityParams);
		}

		return $task;
	}

	private static function doUpdate($userId, $taskId, array $data, array $parameters)
	{
		$errors = static::ensureHaveErrorCollection($parameters);
		$task = static::getTask($userId, $taskId);

		$data = static::normalizeData($data);

		static::inviteMembers($data, $errors);
		static::adaptSet($data);

		if(!is_array($parameters['TASK_ACTION_UPDATE_PARAMETERS']))
		{
			$parameters['TASK_ACTION_UPDATE_PARAMETERS'] = array();
		}

		static::ensureDatePlanChangeAllowed($userId, $data);
		$cleanData = static::stripSubEntityData($data);

		// under some conditions we may loose rights (for edit or read, or both) during update, so a little trick is needed
		$canEditBefore = $task->isActionAllowed(\CTaskItem::ACTION_EDIT); // get our rights before doing anything
		if(!empty($cleanData))
		{
			$task->update($cleanData, $parameters['TASK_ACTION_UPDATE_PARAMETERS']); // do not check return result, because method will throw an exception on error
		}
		$canReadAfter = $task->checkCanRead();
		$canEditAfter = $canReadAfter && $task->isActionAllowed(\CTaskItem::ACTION_EDIT);
		$rightsLost = $canEditBefore && !$canEditAfter;
		$adminUserId = \Bitrix\Tasks\Util\User::getAdminId();

		// if we have had rights before, but have lost them now, do the rest of update under superuser`s rights, or else continue normally
		// todo: instead of replacing userId make option "skipRights under current user"
		$continueAs = $rightsLost ? $adminUserId : $userId;

		if(!$canReadAfter) // at least become an auditor for that task
		{
			$sameTask = \CTaskItem::getInstance($taskId, $adminUserId);
			$sameTask->startWatch($userId);
		}

		// update sub-entities (SE_*)
		$subEntityParams = array_merge(
			$parameters, array('MODE' => static::MODE_UPDATE, 'ERROR' => $errors)
		);

		if(array_key_exists(Reminder::getCode(true), $data))
		{
			Reminder::manageSet($userId, $taskId, $data[Reminder::getCode(true)], $subEntityParams);
		}

		if(array_key_exists(ProjectDependence::getCode(true), $data))
		{
			ProjectDependence::manageSet($continueAs, $taskId, $data[ProjectDependence::getCode(true)], $subEntityParams);
		}

		if(array_key_exists(Checklist::getCode(true), $data))
		{
			Checklist::manageSet($continueAs, $taskId, $data[Checklist::getCode(true)], $subEntityParams);
		}

		Template::manageTaskReplication($userId, $taskId, $data, $subEntityParams);

		return $task;
	}

	private static function ensureDatePlanChangeAllowed($userId, array &$data)
	{
		$projdepKey = ProjectDependence::getCode(true);

		// smth is meant to be added in project dependency, thus we must enable ALLOW_CHANGE_DEADLINE for the task
		// todo: this is required for making dependencies in case of task update with rights loose. remove this when AUTHOR_ID field introduced
		if(array_key_exists($projdepKey, $data) && !empty($data[$projdepKey]) && $userId == $data['RESPONSIBLE_ID'])
		{
			$data['ALLOW_CHANGE_DEADLINE'] = 'Y';
		}
	}

	private static function injectDayPlanFields($userId, array $parameters, array &$data, array &$can)
	{
		if(empty($data))
		{
			return;
		}

		$extranetSite = \Bitrix\Tasks\Integration\Extranet::isExtranetSite();
		$extranetUser = \Bitrix\Tasks\Integration\Extranet\User::isExtranet($userId);

        // no dayplan for extranet site, even if intranet user goes to extranet site
		$plan = array();
		if(!$extranetSite && !$extranetUser)
		{
			$plan = \CTaskPlannerMaintance::getCurrentTasksList();

			if(is_array($plan) && !empty($plan))
            {
                $plan = array_flip($plan);
            }
		}

		foreach($data as &$task)
		{
			$inDayPlan = 		false;
			$canAddToPlan = 	false;

			if ($task["RESPONSIBLE_ID"] == $userId || (is_array($task['ACCOMPLICES']) && in_array($userId, $task['ACCOMPLICES'])))
			{
				$canAddToPlan = true;

				// if in day plan already
				if (isset($plan[$task['ID']]))
				{
					$inDayPlan = true;
					$canAddToPlan = false;
				}
			}

			$task['IN_DAY_PLAN'] = $inDayPlan;
			$task['TIME_ELAPSED'] = intval($task['TIME_SPENT_IN_LOGS']);
			$task['TIMER_IS_RUNNING_FOR_CURRENT_USER'] = false;

			$can[$task['ID']]['ACTION']['ADD_TO_DAY_PLAN'] = $can[$task['ID']]['ACTION']['DAYPLAN.ADD'] = !$extranetUser && $canAddToPlan;
		}

		// current timer
		$runningTaskData  = \CTaskTimerManager::getInstance($userId)->getRunningTask(false);
		foreach($data as $k => &$task)
		{
			if($task['ID'] == $runningTaskData['TASK_ID'] && $task['ALLOW_TIME_TRACKING'] == 'Y')
			{
				$task['TIME_ELAPSED'] += (time() - $runningTaskData['TIMER_STARTED_AT']); // elapsed time is a sum of times in task log plus time of the current timer
				$task['TIME_ELAPSED'] = (string) $task['TIME_ELAPSED']; // for consistency
				$task['TIMER_IS_RUNNING_FOR_CURRENT_USER'] = true;
			}
		}
	}

	private static function getBasicData($userId, $taskId, array $parameters)
	{
		$data = array();
		$denied = false;

		try
		{
			$task = static::getTask($userId, $taskId);

			if($task !== null)
			{
				$data = $task->getData(!!$parameters['ESCAPE_DATA']);
				$data[static::ACT_KEY] = static::translateAllowedActionNames($task->getAllowedActions(true));

				if(!intval($data['FORUM_ID']))
				{
					$data['FORUM_ID'] = \CTasksTools::getForumIdForIntranet();
				}
				$data['COMMENTS_COUNT'] = intval($data['COMMENTS_COUNT']);
			}

			if($parameters['DROP_PRIMARY'])
			{
				unset($data['ID']);
				$data[static::ACT_KEY] = array();
			}
		}
		catch(\TasksException $e) // todo: get rid of this annoying catch by making \Bitrix\Tasks\*Exception classes inherited from TasksException (dont forget about code)
		{
			if($e->checkOfType(\TasksException::TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE))
			{
				$denied = true;
			}
			else
			{
				throw $e; // let it log
			}
		}
		catch(\Bitrix\Tasks\AccessDeniedException $e) // task not found or not accessible
		{
			$denied = true;
		}

		if($denied)
		{
			$parameters['ERRORS']->add('ACCESS_DENIED.NO_TASK', 'Task not found or not accessible');
		}

		return $data;
	}

	private static function translateAllowedActionNames($can)
	{
		$newCan = array();
		if(is_array($can))
		{
			foreach($can as $act => $flag)
			{
				$newCan[str_replace('ACTION_', '', $act)] = $flag;
			}

			static::replaceKey($newCan, 'CHANGE_DIRECTOR', 'EDIT.ORIGINATOR');
			static::replaceKey($newCan, 'CHECKLIST_REORDER_ITEMS', 'CHECKLIST.REORDER');
			static::replaceKey($newCan, 'ELAPSED_TIME_ADD', 'ELAPSEDTIME.ADD');
			static::replaceKey($newCan, 'START_TIME_TRACKING', 'DAYPLAN.TIMER.TOGGLE');

			// todo: when mobile stops using this fields, remove the third argument here
			static::replaceKey($newCan, 'CHANGE_DEADLINE', 'EDIT.PLAN', false); // used in mobile already
			static::replaceKey($newCan, 'CHECKLIST_ADD_ITEMS', 'CHECKLIST.ADD', false); // used in mobile already
			static::replaceKey($newCan, 'ADD_FAVORITE', 'FAVORITE.ADD', false); // used in mobile already
			static::replaceKey($newCan, 'DELETE_FAVORITE', 'FAVORITE.DELETE', false); // used in mobile already
		}

		return $newCan;
	}

	private static function replaceKey(array &$data, $from, $to, $dropFrom = true)
	{
		if(array_key_exists($from, $data))
		{
			$data[$to] = $data[$from];
			if($dropFrom)
			{
				unset($data[$from]);
			}
		}
	}

	private static function prepareNav($limit = false, $offset = false, $public = false)
	{
		$nav = array();

		if($limit !== false)
		{
			$limit = intval($limit);

			if($public)
			{
				$limit = min($limit, static::LIMIT_PAGE_SIZE);
			}

			if($offset !== false)
			{
				$nav['nPageSize'] = $limit;
			}
			else
			{
				$nav['nTopCount'] = $limit;
			}
		}
		else
		{
			if($public)
			{
				$nav['nTopCount'] = static::LIMIT_PAGE_SIZE;
			}
		}

		if($offset !== false)
		{
			$nav['iNumPageSize'] = intval($offset);
		}

		return $nav;
	}
}