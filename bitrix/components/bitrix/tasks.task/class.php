<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;

use \Bitrix\Tasks\Util\Error\Collection;
use \Bitrix\Tasks\Manager;
use \Bitrix\Tasks\Manager\Task;
use \Bitrix\Tasks\UI;
use \Bitrix\Tasks\Util;
use \Bitrix\Tasks\Util\Type;
use \Bitrix\Tasks;
use \Bitrix\Tasks\Integration;

Loc::loadMessages(__FILE__);

require_once(dirname(__FILE__).'/class/formstate.php');

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksTaskComponent extends TasksBaseComponent
{
	const ERROR_TYPE_TASK_SAVE_ERROR = 'TASK_SAVE_ERROR';

	const DATA_SOURCE_TEMPLATE = 	'TEMPLATE';
	const DATA_SOURCE_TASK = 		'TASK';

	protected $task = 			null;
	protected $users2Get = 		array();
	protected $groups2Get = 	array();
	protected $tasks2Get = 		array();
	protected $formData = 		false;

    private $success =          false;
	private $responsibles = 	false;
    private $eventType =        false;
    private $eventTaskId =      false;
    private $eventOptions =     array();

	/**
	 * Function checks if required modules installed. Also check for available features
	 * @throws Exception
	 * @return void
	 */
	protected static function checkRequiredModules(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		if(!Loader::includeModule('socialnetwork'))
		{
			$errors->add('SOCIALNETWORK_MODULE_NOT_INSTALLED', Loc::getMessage("TASKS_TT_SOCIALNETWORK_MODULE_NOT_INSTALLED"));
		}

		if(!Loader::includeModule('forum'))
		{
			$errors->add('FORUM_MODULE_NOT_INSTALLED', Loc::getMessage("TASKS_TT_FORUM_MODULE_NOT_INSTALLED"));
		}

		return $errors->checkNoFatals();
	}

	/**
	 * Function checks if user have basic permissions to launch the component
	 * @throws Exception
	 * @return void
	 */
	protected static function checkPermissions(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		parent::checkPermissions($arParams, $arResult, $errors, $auxParams);
		static::checkRestrictions($arParams, $arResult, $errors);

		if($errors->checkNoFatals())
		{
			// check task access
			$taskId = intval($arParams[static::getParameterAlias('ID')]);
			if($taskId)
			{
				try
				{
					$arResult['TASK_INSTANCE'] = CTaskItem::getInstanceFromPool($taskId, $arResult['USER_ID']);
					$access = $arResult['TASK_INSTANCE']->checkCanRead();
				}
				catch(TasksException $e)
				{
					$access = false;
				}

				if(!$access)
				{
					$errors->add('ACCESS_DENIED.NO_TASK', Loc::getMessage('TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE'));
				}
			}
		}

		return $errors->checkNoFatals();
	}

	protected static function checkRestrictions(array &$arParams, array &$arResult, Collection $errors)
	{
		if(!\Bitrix\Tasks\Util\Restriction::canManageTask())
		{
			$errors->add('ACTION_NOT_ALLOWED.RESTRICTED', 'Task managing is restricted for the current user');
		}
	}

	/**
	 * Function checks and prepares only the basic parameters passed
	 */
	protected static function checkBasicParameters(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		static::tryParseIntegerParameter($arParams[static::getParameterAlias('ID')], 0, true); // parameter keeps currently chosen task ID

		return $errors->checkNoFatals();
	}

	/**
	 * Function checks and prepares all the parameters passed
	 */
	protected function checkParameters()
	{
		parent::checkParameters();
		if($this->arParams['USER_ID'])
		{
			$this->users2Get[] = $this->arParams['USER_ID'];
		}

		static::tryParseIntegerParameter($this->arParams['GROUP_ID'], 0);
		if($this->arParams['GROUP_ID'])
		{
			$this->groups2Get[] = $this->arParams['GROUP_ID'];
		}

		static::tryParseArrayParameter($this->arParams['SUB_ENTITY_SELECT']);
		static::tryParseArrayParameter($this->arParams['AUX_DATA_SELECT']);

        static::tryParseBooleanParameter($this->arParams['REDIRECT_ON_SUCCESS'], true);

		return $this->errors->checkNoFatals();
	}

	protected function processBeforeAction($trigger = array())
	{
		$request = static::getRequest()->toArray();

		if(Type::isIterable($request['ADDITIONAL']))
		{
			$this->setDataSource(
				$request['ADDITIONAL']['DATA_SOURCE']['TYPE'],
				$request['ADDITIONAL']['DATA_SOURCE']['ID']
			);
		}

		// set responsible id and multiple
		if(Type::isIterable($trigger) && Type::isIterable($trigger[0]))
		{
			$action =& $trigger[0];
			$taskData =& $action['ARGUMENTS']['data'];

			if(Type::isIterable($taskData))
			{
				$this->setResponsibles($this->extractResponsibles($taskData));
			}

			$responsibles = $this->getResponsibles();

			// must pre-invite responsibles ...
			static::inviteUsers($responsibles, $this->errors);

			if(!empty($responsibles))
			{
				$taskData =& $action['ARGUMENTS']['data'];

				// create here...

				if($action['OPERATION'] == 'task.add')
				{
					// a bit more interesting
					if(count($responsibles) > 1)
					{
						$taskData['MULTITASK'] = 'Y';

						// this "root" task will have current user as responsible
						// RESPONSIBLE_ID has higher priority than SE_RESPONSIBLE, so its okay
						$taskData['RESPONSIBLE_ID'] = $this->userId;
					}
				}
			}
		}

		return $trigger;
	}

	protected function processAfterAction()
	{
		if(Type::isIterable($this->arResult['ACTION_RESULT']['task_action']) && !empty($this->arResult['ACTION_RESULT']['task_action']))
		{
			$op = $this->arResult['ACTION_RESULT']['task_action'];
            $actionTask = false;

			if($op['SUCCESS'])
			{
				$this->processAfterSaveAction($op);

                $this->setEventType($op['OPERATION'] == 'task.add' ? 'ADD' : 'UPDATE');
                $this->setEventTaskId(intval($op['RESULT']['DATA']['ID']));
                $this->setEventOption('STAY_AT_PAGE', !!$this->request['STAY_AT_PAGE']);

                if($this->arParams['REDIRECT_ON_SUCCESS'])
                {
	                LocalRedirect($this->makeRedirectUrl($op));
                }

                $this->formData = false;
                $this->success = true;
                $actionTask = static::getOperationTaskId($op);
			}
			else
			{
				// merge errors
				if(!empty($op['ERRORS']))
				{
					$this->errors->addForeignErrors($op['ERRORS'], array('CHANGE_TYPE_TO' => static::ERROR_TYPE_TASK_SAVE_ERROR));
				}

				$this->formData = Task::normalizeData($op['ARGUMENTS']['data']);

                $this->success = false;
			}

            $this->arResult['COMPONENT_DATA']['ACTION'] = array(
                'SUCCESS' => $this->success,
                'ID' => $actionTask
            );
		}
	}

	// here we create all subtasks by template or task being copyed
	protected function processAfterSaveAction(array $op)
	{
		if($op['OPERATION'] == 'task.add') // task.add usually returns TASK_ID on success
		{
			$mainTaskId = static::getOperationTaskId($op);

			if($mainTaskId) // main task is okay, create the rest
			{
				$responsibles = $this->getResponsibles();

				$tasks = array(
					$mainTaskId
				);

				$cacheAFWasDisabled = CTasks::disableCacheAutoClear();
				$notifADWasDisabled = CTaskNotifications::disableAutoDeliver();

				if(count($responsibles) > 1)
				{
					// create one more task for each responsible
					if(!empty($op['ARGUMENTS']['data']))
					{
						$fields = $op['ARGUMENTS']['data'];

						// uncheck some...
						unset($fields['MULTITASK']);
						unset($fields['REPLICATE']);
						unset($fields['RESPONSIBLE_ID']); // will be overwritten by SE_RESPONSIBLE
						unset($fields[Manager::SE_PREFIX.'PROJECTDEPENDENCE']);
						unset($fields[Manager::SE_PREFIX.'TEMPLATE']);
						unset($fields[Task\ParentTask::getCode(true)]);

						foreach($responsibles as $user)
						{
							if($fields[Task\Originator::getCode(true)]['ID'] == $user['ID'])
							{
								continue; // do not copy to creator
							}

							$cFields = $fields;
							$cFields[Task\Responsible::getCode(true)] = 	    array($user);
							$cFields[Task\ParentTask::getCode(true)]['ID'] = 	$mainTaskId;

							$addResult = Task::add($this->userId, $cFields, array('PUBLIC_MODE' => true, 'RETURN_ENTITY' => true));
							if($addResult['ERRORS']->checkNoFatals() && intval($addResult['DATA']['ID']))
							{
								$tasks[] = intval($addResult['DATA']['ID']);
							}
						}
					}
				}

				foreach($tasks as $taskId)
				{
					$this->createSubTasksBySource($taskId);
				}

				if($notifADWasDisabled)
				{
					CTaskNotifications::enableAutoDeliver();
				}
				if($cacheAFWasDisabled)
				{
					CTasks::enableCacheAutoClear();
				}
			}
		}
	}

    private static function getOperationTaskId(array $operation)
    {
        return intval($operation['RESULT']['DATA']['ID']); // task.add and task.update always return TASK_ID on success
    }

    private function makeRedirectUrl(array $operation)
    {
        $actionAdd = $operation['OPERATION'] == 'task.add';
        $resultTaskId = static::getOperationTaskId($operation);

        $url = (string) $this->request['BACKURL'] != '' ? $this->request['BACKURL'] : $GLOBALS["APPLICATION"]->GetCurPageParam('');

	    $action = 'view'; // having default backurl after success edit we go to view ...

	    // .. but there are some exceptions
	    $taskId = 0;
        if($actionAdd)
        {
            $taskId = $resultTaskId;
            if($this->request['STAY_AT_PAGE'])
            {
                $taskId = 0;
	            $action = 'edit';
            }
        }

	    $url = UI\Task::makeActionUrl($url, $taskId, $action);
	    $url = UI\Task::cleanFireEventUrl($url);
        $url = UI\Task::makeFireEventUrl($url, $this->getEventTaskId(), $this->getEventType(), array(
	        'STAY_AT_PAGE' => $this->getEventOption('STAY_AT_PAGE')
        ));

        return $url;
    }

	protected function createSubTasksBySource($taskId)
	{
		$task = CTaskItem::getInstance($taskId, $this->userId);
		$source = $this->getDataSource();

		if(intval($source['ID']))
		{
			// clone subtasks or create them by template
			if($source['TYPE'] == static::DATA_SOURCE_TEMPLATE)
			{
				// bad code:
				$templateData = CTaskTemplates::GetList(false, array('ID' => intval($source['ID'])), false, array('USER_ID' => $this->userId), array('ID'))->fetch();
				if(Type::isIterable($templateData))
				{
					$task->addChildTasksByTemplate(intval($source['ID']));
				}
			}
			elseif($source['TYPE'] == static::DATA_SOURCE_TASK)
			{
				$sourceTask = CTaskItem::getInstance(intval($source['ID']), $this->userId);
				$sourceTask->duplicateChildTasks($task);
			}
		}
	}

	/**
	 * Allows to pass some of arParams through ajax request, according to the white-list
	 * @return mixed[]
	 */
	protected static function extractParamsFromRequest($request)
	{
		return array('ID' => $request['ID']); // DO NOT simply pass $request to the result, its unsafe
	}

	/**
	 * Allows to decide which data should be passed to $this->arResult, and which should not
	 */
	protected function translateArResult($arResult)
	{
		if(isset($arResult['TASK_INSTANCE']) && $arResult['TASK_INSTANCE'] instanceof CTaskItem)
		{
			$this->task = $arResult['TASK_INSTANCE']; // a short-cut to the currently selected task instance
			unset($arResult['TASK_INSTANCE']);
		}

		parent::translateArResult($arResult); // all other will merge to $this->arResult
	}

	protected function getDataDefaults()
	{
		$stateFlags = $this->arResult['COMPONENT_DATA']['STATE']['FLAGS'];

		$rights = Task::getFullRights($this->userId);
		$data = array(
			'CREATED_BY' => 		$this->userId,
			Task\Originator::getCode(true) => array('ID' => $this->userId),
			Task\Responsible::getCode(true) => array(array('ID' => $this->userId)),
			'PRIORITY' => 			CTasks::PRIORITY_AVERAGE,
			'FORUM_ID' => 			CTasksTools::getForumIdForIntranet(),
			'REPLICATE' => 			'N',

			'ALLOW_CHANGE_DEADLINE' =>  $stateFlags['ALLOW_CHANGE_DEADLINE'] ? 'Y' : 'N',
			'ALLOW_TIME_TRACKING' => 	$stateFlags['ALLOW_TIME_TRACKING'] ? 'Y' : 'N',
			'TASK_CONTROL' => 			$stateFlags['TASK_CONTROL'] ? 'Y' : 'N',
			'MATCH_WORK_TIME' => 		$stateFlags['MATCH_WORK_TIME'] ? 'Y' : 'N',

			'DESCRIPTION_IN_BBCODE' => 'Y', // new tasks should be always in bbcode
			'DURATION_TYPE' => 		CTasks::TIME_UNIT_TYPE_DAY,
			'DURATION_TYPE_ALL' =>  CTasks::TIME_UNIT_TYPE_DAY,

			Manager::ACT_KEY => $rights
		);

		return array('DATA' => $data, 'CAN' => array('ACTION' => $rights));
	}

	protected function getDataRequest()
	{
		$data = array();

        // also check out for pre-set variables in request
        if(intval($this->request['PARENT_ID']))
        {
            $data[Task\ParentTask::getCode(true)] = array('ID' => intval($this->request['PARENT_ID']));
        }
        if(intval($this->request['GROUP_ID']))
        {
            $data[Task\Project::getCode(true)] = array('ID' => intval($this->request['GROUP_ID']));
        }
        elseif(intval($this->arParams['GROUP_ID']))
        {
            $data[Task\Project::getCode(true)] = array('ID' => intval($this->arParams['GROUP_ID']));
        }
        if((string) $this->request['TITLE'] != '')
        {
            $data['TITLE'] = $this->request['TITLE'];
        }
        if((string) $this->request['UF_CRM_TASK'] != '')
        {
            $data['UF_CRM_TASK'] = array((string) $this->request['UF_CRM_TASK']);
        }
        if(isset($this->request['TAGS']))
        {
            $tags = array();
            if(is_string($this->request['TAGS']) && $this->request['TAGS'] != '')
            {
                $tags = explode(',', $this->request['TAGS']);
            }
            elseif(Type::isIterable($this->request['TAGS']) && !empty($this->request['TAGS']))
            {
                $tags = $this->request['TAGS'];
            }

            if(!empty($tags))
            {
				$trans = array();
	            foreach($tags as $tag)
	            {
		            $tag = trim($tag);
		            if($tag != '')
		            {
			            $trans[] = array('NAME' => $tag);
		            }
	            }

                $data[Task\Tag::getCode(true)] = $trans;
            }
        }

		return array('DATA' => $data);
	}

	// get some data and decide what goes to arResult
	protected function getData()
	{
		// todo: if we have not done any redirect after doing some actions, better re-check task accessibility here

		//TasksTaskFormState::reset();
		$this->arResult['COMPONENT_DATA']['STATE'] = static::getState();

		$formSubmitted = $this->formData !== false;

		if($this->task != null) // editing an existing task, get THIS task data
		{
			$data = Task::get($this->userId, $this->task->getId(), array(
				'ENTITY_SELECT' => $this->arParams['SUB_ENTITY_SELECT'],
				'ESCAPE_DATA' => static::getEscapedData(),
				'ERRORS' => $this->errors
			));

			if($this->errors->checkHasFatals())
			{
				return;
			}

			if($formSubmitted)
			{
				// applying form data on top, what changed
				$data['DATA'] = Task::mergeData($this->formData, $data['DATA']);
			}
		}
		else // get from other sources: default task data, or other task data, or template data
		{
			$data = $this->getDataDefaults();

			if($formSubmitted)
			{
				// applying form data on top, what changed
				$data['DATA'] = Task::mergeData($this->formData, $data['DATA']);
			}
			else
			{
				$copyErrors = new Collection();
				$parameters = array(
					'ENTITY_SELECT' => array_intersect($this->arParams['SUB_ENTITY_SELECT'], array('CHECKLIST', 'REMINDER', 'TAG', 'PROJECTDEPENDENCE')),
					'ESCAPE_DATA' => false,
					'ERRORS' => $copyErrors,
					'DROP_PRIMARY' => true
				);

				$error = false;
				$sourceData = array();
				try
				{
					if(intval($this->request['TEMPLATE'])) // copy from template?
					{
						$sourceData = Manager\Task\Template::get($this->userId, intval($this->request['TEMPLATE']), $parameters);

						// do not inherit replication from template
						$sourceData['DATA']['REPLICATE'] = 'N';
						$this->setDataSource(
							static::DATA_SOURCE_TEMPLATE,
							$this->request['TEMPLATE']
						);
					}
					elseif(intval($this->request['COPY'])) // copy from another task?
					{
						$sourceData = Task::get($this->userId, intval($this->request['COPY']), $parameters);

						$this->setDataSource(
							static::DATA_SOURCE_TASK,
							$this->request['COPY']
						);
					}
					else // get some from request
					{
						$sourceData = $this->getDataRequest();
					}
				}
				catch(TasksException $e)
				{
					if($e->checkOfType(TasksException::TE_ACCESS_DENIED) || $e->checkOfType(TasksException::TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE))
					{
						$error = 'access';
					}
					else
					{
						$error = 'other';
					}
				}
				catch(\Bitrix\Tasks\AccessDeniedException $e)
				{
					$error = 'access';
				}
				if($error === false) // no exceptions? may be error collection has any?
				{
					if($copyErrors->checkHasErrors())
					{
						$error = 'other';
					}
				}

				if($error !== false)
				{
					$this->errors->add('COPY_ERROR', Loc::getMessage($error == 'access' ? 'TASKS_TT_NOT_FOUND_OR_NOT_ACCESSIBLE_COPY' : 'TASKS_TT_COPY_READ_ERROR'), Collection::TYPE_WARNING);
				}

				$data['DATA'] = Task::mergeData($sourceData['DATA'], $data['DATA']);
			}
		}

		$this->arResult['DATA']['TASK'] = $data['DATA'];
		$this->arResult['CAN']['TASK'] = $data['CAN'];

		// obtaining additional data: calendar settings, user fields
		$this->getDataAux();

		// collect related: tasks, users & groups
		$this->collectTaskMembers();
		$this->collectRelatedTasks();
		$this->collectProjects();
		$this->collectLogItems();
	}

	protected function getDataAux()
	{
		$this->arResult['AUX_DATA'] = array();
		$auxSelect = array_flip($this->arParams['AUX_DATA_SELECT']);

        $this->arResult['AUX_DATA']['COMPANY_WORKTIME'] = static::getCompanyWorkTime(!isset($auxSelect['COMPANY_WORKTIME']));

		if(isset($auxSelect['USER_FIELDS']))
		{
			$this->getDataUserFields();
		}
		if(isset($auxSelect['TEMPLATE']))
		{
			$this->getDataTemplates();
		}

		$this->arResult['AUX_DATA']['HINT_STATE'] = \Bitrix\Tasks\UI::getHintState();
	}

	protected function getDataTemplates()
	{
		$res = CTaskTemplates::GetList(
			array("ID" => "DESC"),
			array("CREATED_BY" => $this->userId, 'BASE_TEMPLATE_ID' => false, '!TPARAM_TYPE' => CTaskTemplates::TYPE_FOR_NEW_USER),
			array('NAV_PARAMS' => array('nTopCount' => 10)),
			array(),
			array('ID', 'TITLE')
		);

		$templates = array();
		while($template = $res->fetch())
		{
			$templates[$template['ID']] = array(
				'ID' => $template['ID'],
				'TITLE' => $template['TITLE']
			);
		}

		$this->arResult['AUX_DATA']['TEMPLATE'] = $templates;
	}

	protected function getDataUserFields()
	{
		$this->arResult['AUX_DATA']['USER_FIELDS'] = static::getUserFields($this->task !== null ? $this->task->getId() : 0);

		// restore uf values from task data
		if(Type::isIterable($this->arResult['AUX_DATA']['USER_FIELDS']))
		{
			foreach($this->arResult['AUX_DATA']['USER_FIELDS'] as $ufCode => $ufDesc)
			{
				if(isset($this->arResult['DATA']['TASK'][$ufCode]))
				{
					$this->arResult['AUX_DATA']['USER_FIELDS'][$ufCode]['VALUE'] = $this->arResult['DATA']['TASK'][$ufCode];
				}
			}
		}
	}

	protected function collectTaskMembers()
	{
		$data = $this->arResult['DATA']['TASK'];

		$this->collectMembersFromArray(Task\Originator::extractPrimaryIndexes($data[Task\Originator::getCode(true)]));
		$this->collectMembersFromArray(Task\Responsible::extractPrimaryIndexes($data[Task\Responsible::getCode(true)]));
		$this->collectMembersFromArray(Task\Accomplice::extractPrimaryIndexes($data[Task\Accomplice::getCode(true)]));
		$this->collectMembersFromArray(Task\Auditor::extractPrimaryIndexes($data[Task\Auditor::getCode(true)]));
		$this->collectMembersFromArray(array(
			$this->arResult['DATA']['TASK']['CHANGED_BY'],
			$this->userId
		));
	}

	protected function collectRelatedTasks()
	{
		if($this->arResult['DATA']['TASK']['PARENT_ID'])
		{
			$this->tasks2Get[] = $this->arResult['DATA']['TASK']['PARENT_ID'];
		}
		elseif($this->arResult['DATA']['TASK'][Task\ParentTask::getCode(true)])
		{
			$this->tasks2Get[] = $this->arResult['DATA']['TASK'][Task\ParentTask::getCode(true)]['ID'];
		}

		if(Type::isIterable($this->arResult['DATA']['TASK'][Task::SE_PREFIX.'PROJECTDEPENDENCE']))
		{
			$projdep = $this->arResult['DATA']['TASK'][Task::SE_PREFIX.'PROJECTDEPENDENCE'];
			foreach($projdep as $dep)
			{
				$this->tasks2Get[] = $dep['DEPENDS_ON_ID'];
			}
		}

		if(Type::isIterable($this->arResult['DATA']['TASK'][Task::SE_PREFIX.'RELATEDTASK']))
		{
			$related = $this->arResult['DATA']['TASK'][Task::SE_PREFIX.'RELATEDTASK'];
			foreach($related as $task)
			{
				$this->tasks2Get[] = $task['ID'];
			}
		}
	}

	protected function collectProjects()
	{
		if($this->arResult['DATA']['TASK']['GROUP_ID'])
		{
			$this->groups2Get[] = $this->arResult['DATA']['TASK']['GROUP_ID'];
		}
		elseif($this->arResult['DATA']['TASK'][Task\Project::getCode(true)])
		{
			$this->groups2Get[] = $this->arResult['DATA']['TASK'][Task\Project::getCode(true)]['ID'];
		}
	}

	protected function collectLogItems()
	{
		if (!Type::isIterable($this->arResult['DATA']['TASK'][Task::SE_PREFIX.'LOG']))
		{
			return;
		}

		foreach ($this->arResult['DATA']['TASK'][Task::SE_PREFIX.'LOG'] as $record)
		{
			switch ($record['FIELD'])
			{
				case 'CREATED_BY':
				case 'RESPONSIBLE_ID':
					if ($record['FROM_VALUE'])
					{
						$this->users2Get[] = $record['FROM_VALUE'];
					}

					if ($record['TO_VALUE'])
					{
						$this->users2Get[] = $record['TO_VALUE'];
					}

					break;
				case 'AUDITORS':
				case 'ACCOMPLICES':
					if ($record['FROM_VALUE'])
					{
						$this->collectMembersFromArray(explode(',', $record['FROM_VALUE']));
					}

					if ($record['TO_VALUE'])
					{
						$this->collectMembersFromArray(explode(',', $record['TO_VALUE']));
					}
					break;

				case 'GROUP_ID':
					if ($record['FROM_VALUE'])
					{
						$this->groups2Get[] = intval($record['FROM_VALUE']);
					}

					if ($record['TO_VALUE'])
					{
						$this->groups2Get[] = intval($record['TO_VALUE']);
					}
					break;

				case 'PARENT_ID':
					if ($record['FROM_VALUE'])
					{
						$this->tasks2Get[] = intval($record['FROM_VALUE']);
					}

					if ($record['TO_VALUE'])
					{
						$this->tasks2Get[] = intval($record['TO_VALUE']);
					}
					break;

				case 'DEPENDS_ON':
					if ($record['FROM_VALUE'])
					{
						$this->collectTasksFromArray(explode(',', $record['FROM_VALUE']));
					}

					if ($record['TO_VALUE'])
					{
						$this->collectTasksFromArray(explode(',', $record['TO_VALUE']));
					}
					break;

				default:
					break;
			}
		}
	}

	protected function collectMembersFromArray($ids)
	{
		if(Type::isIterable($ids) && !empty($ids))
		{
			$this->users2Get = array_merge($this->users2Get, $ids);
		}
	}

	protected function collectTasksFromArray($ids)
	{
		if (Type::isIterable($ids) && !empty($ids))
		{
			$this->tasks2Get = array_merge($this->tasks2Get, $ids);
		}
	}

	protected function getReferenceData()
	{
		$this->arResult['DATA']['GROUP'] = 			static::getGroupsData($this->groups2Get);
		$this->arResult['DATA']['USER'] = 			\Bitrix\Tasks\Util\User::getData($this->users2Get);
		$this->arResult['DATA']['RELATED_TASK'] = 	static::getTasksData($this->tasks2Get);

		$this->getCurrentUserData();
	}

	protected function getCurrentUserData()
	{
		$currentUser = array('DATA' => $this->arResult['DATA']['USER'][$this->userId]);

		$currentUser['IS_SUPER_USER'] = \Bitrix\Tasks\Util\User::isSuper($this->userId);
		$roles = array(
			'ORIGINATOR' => false,
			'DIRECTOR' => false, // director usually is more than just originator, according to the subordination rules
		);
		if($this->task !== null)
		{
			try
			{
				$roles['ORIGINATOR'] =  $this->task['CREATED_BY'] == $this->userId;
				$roles['DIRECTOR'] =    !!$this->task->isUserRole(\CTaskItem::ROLE_DIRECTOR);
			}
			catch(\TasksException $e)
			{
			}
		}
		$currentUser['ROLES'] = $roles;

		$this->arResult['AUX_DATA']['USER'] = $currentUser;
	}

	protected function formatData()
	{
		$data =& 	$this->arResult['DATA']['TASK'];

		if(Type::isIterable($data))
		{
			Task::extendData($data, $this->arResult['DATA']);

			// left for compatibility
			$data[Task::SE_PREFIX.'PARENT'] = $data[Task\ParentTask::getCode(true)];
		}
	}

    protected function doPreAction()
    {
        parent::doPreAction();

        $this->arResult['COMPONENT_DATA']['BACKURL'] = $this->getBackUrl();
    }

	protected function doPostAction()
	{
		parent::doPostAction();

		if($this->errors->checkNoFatals())
		{
            if($this->task != null)
            {
                // set this task as viewed and update its view time
                CTasks::UpdateViewed($this->task->getId(), $this->userId);
            }

            $this->getEventData();
		}
	}

    protected function getEventData()
    {
        if($this->getEventTaskId() && ($this->formData === false || $this->success))
        {
            /*
            form had not been submitted at the current hit, or submitted successfully
            */

            $eventTaskData = false;
            if($this->task != null && $this->task->getId() == $this->getEventTaskId())
            {
                $eventTaskData = static::dropSubEntitiesData($this->arResult['DATA']['TASK']);
            }
            else // have to get data manually
            {
                try
                {
                    $eventTask = Task::get($this->userId, $this->getEventTaskId());
                    if($eventTask['ERRORS']->checkNoFatals())
                    {
                        $eventTaskData = $eventTask['DATA'];
                    }
                }
                catch(Tasks\Exception $e) // smth went wrong - no access or smth else. just skip, what else to do?
                {
                }
            }

            // happy end
            if(Type::isIterable($eventTaskData) && !empty($eventTaskData))
            {
                $eventTaskData['CHILDREN_COUNT'] = 0;
                $childrenCount = CTasks::GetChildrenCount(array(), $eventTaskData['ID'])->fetch();
                if ($childrenCount)
                {
                    $eventTaskData['CHILDREN_COUNT'] = $childrenCount['CNT'];
                }

                $this->arResult['DATA']['EVENT_TASK'] = $eventTaskData;
                $this->arResult['COMPONENT_DATA']['EVENT_TYPE'] = $this->getEventType();
                $this->arResult['COMPONENT_DATA']['EVENT_OPTIONS'] = array(
                    'STAY_AT_PAGE' => $this->getEventOption('STAY_AT_PAGE')
                );
            }
        }
    }

	protected static function getTasksData(array $taskIds)
	{
		$tasks = array();

		if(!empty($taskIds))
		{
			$taskIds = array_unique($taskIds);
			$parsed = array();
			foreach($taskIds as $taskId)
			{
				if(intval($taskId))
				{
					$parsed[] = $taskId;
				}
			}

			if(!empty($parsed))
			{
				list($list, $res) = CTaskItem::fetchList(
					$GLOBALS['USER']->GetId(),
					array("ID" => "ASC"),
					array("ID" => $parsed),
					array(),
					array("ID", "TITLE", "START_DATE_PLAN", "END_DATE_PLAN")
				);

				foreach($list as $item)
				{
					$data = $item->getData(false);
					$tasks[$data["ID"]] = array(
						'ID' => $data['ID'],
						'TITLE' => $data['TITLE'],
						'START_DATE_PLAN' => $data['START_DATE_PLAN'],
						'END_DATE_PLAN' => $data['END_DATE_PLAN'],
					);
				}
			}
		}

		return $tasks;
	}

	protected static function getUserFields($entityId = 0, $entityName = 'TASKS_TASK')
	{
		return $GLOBALS['USER_FIELD_MANAGER']->getUserFields($entityName, $entityId, LANGUAGE_ID);
	}

	// dont turn it to true for new components
	protected static function getEscapedData()
	{
		return false;
	}

	// temporal
    private function dropSubEntitiesData(array $data)
    {
        foreach($data as $key => $value)
        {
            if(strpos((string) $key, Manager::SE_PREFIX) === 0)
            {
                unset($data[$key]);
            }
        }

        return $data;
    }

	private function setDataSource($type, $id)
	{
		if(($type == static::DATA_SOURCE_TEMPLATE || $type == static::DATA_SOURCE_TASK) && intval($id))
		{
			$this->arResult['COMPONENT_DATA']['DATA_SOURCE'] = array(
				'TYPE' => $type,
				'ID' => intval($id)
			);
		}
	}

	private function getDataSource()
	{
		return $this->arResult['COMPONENT_DATA']['DATA_SOURCE'];
	}

    private function getEventType()
    {
        if($this->eventType === false && (string) $this->request['EVENT_TYPE'] != '')
        {
            $this->eventType = $this->request['EVENT_TYPE'] == 'UPDATE' ? 'UPDATE' : 'ADD';
        }

        return $this->eventType;
    }

    private function setEventType($type)
    {
        $this->eventType = $type;
    }

    private function getEventOption($name)
    {
        if(Type::isIterable($this->request['EVENT_OPTIONS']) && isset($this->request['EVENT_OPTIONS'][$name]))
        {
            $this->eventOptions[$name] = !!$this->request['EVENT_OPTIONS'][$name];
        }

        return $this->eventOptions[$name];
    }

    private function setEventOption($name, $value)
    {
        $this->eventOptions[$name] = $value;
    }

    private function getEventTaskId()
    {
        if(intval($this->request['EVENT_TASK_ID']))
        {
            $this->eventTaskId = intval($this->request['EVENT_TASK_ID']);
        }

        return $this->eventTaskId;
    }

    private function setEventTaskId($taskId)
    {
        $this->eventTaskId = $taskId;
    }

    private function getBackUrl()
    {
        if((string) $this->request['BACKURL'] != '')
        {
            return $this->request['BACKURL'];
        }
        /*
        if((string) $this->arParams['BACKURL'] != '')
        {
            return $this->arParams['BACKURL'];
        }
        */
        // or else backurl will be defined somewhere like result_modifer, see below

        return false;
    }

	private function setResponsibles($users)
	{
		if(Type::isIterable($users))
		{
			$this->responsibles = \Bitrix\Tasks\Util\Type::normalizeArray($users);
		}
	}

	private function extractResponsibles(array $data)
	{
		$code = Task\Responsible::getCode(true);

		if(array_key_exists($code, $data))
		{
			return $data[$code];
		}
		return array();
	}

	private function getResponsibles()
	{
		if($this->responsibles !== false && Type::isIterable($this->responsibles))
		{
			return $this->responsibles;
		}
		else
		{
			return array();
		}
	}

	private static function inviteUsers(array &$users, Collection $errors)
	{
		foreach($users as $i => $user)
		{
			if(!intval($user['ID']))
			{
				if((string) $user['EMAIL'] != '' && \check_email($user['EMAIL']))
				{
					$newId = \Bitrix\Tasks\Integration\Mail\User::create($user);
					if($newId)
					{
						$users[$i]['ID'] = $newId;
					}
					else
					{
						$errors->add('USER_INVITE_FAIL', 'User has not been invited');
					}
				}
				else
				{
					unset($users[$i]); // bad structure
				}
			}
		}
	}

	// for dispatcher below

	public static function getAllowedMethods()
	{
		return array(
			'setState'
		);
	}

	public static function setState(array $state = array())
	{
		TasksTaskFormState::set($state);
	}

	public static function getState()
	{
		return TasksTaskFormState::get();
	}
}