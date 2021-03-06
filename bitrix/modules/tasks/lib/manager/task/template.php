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

namespace Bitrix\Tasks\Manager\Task;

use \Bitrix\Main\Loader;

use \Bitrix\Tasks\Util\Error\Collection;
use \Bitrix\Tasks\Util\Assert;
use \Bitrix\Tasks\Util\Type;

use \Bitrix\Tasks\Manager\Task\Responsible;
use \Bitrix\Tasks\Manager\Task\Auditor;
use \Bitrix\Tasks\Manager\Task\Accomplice;
use \Bitrix\Tasks\Manager\Task\Originator;
use \Bitrix\Tasks\Manager\Task\ParentTask;
use \Bitrix\Tasks\Manager\Task\Project;

// todo: inherit this class from \Bitrix\Tasks\Manager\Task ?
final class Template extends \Bitrix\Tasks\Manager
{
	public static function get($userId, $itemId, array $parameters = array())
	{
		$errors = static::ensureHaveErrorCollection($parameters);

		$result = array(
			'DATA' => array(),
			'CAN' => array(), 
			'ERRORS' => $errors
		);

		$userId = 		Assert::expectIntegerPositive($userId, '$userId');
		$itemId = 		Assert::expectIntegerPositive($itemId, '$itemId');

		$data = \CTaskTemplates::getList(array(), array('ID' => $itemId), array(), array('USER_ID' => $userId), array('*', 'UF_*'))->fetch();
		$can = array();

		if(empty($data))
		{
			$errors->add('ACCESS_DENIED.NO_TEMPLATE', 'Template not found');
			return $result;
		}

		$data['ACCOMPLICES'] = 			Type::unserializeArray($data['ACCOMPLICES']);
		$data['AUDITORS'] = 			Type::unserializeArray($data['AUDITORS']);
		$data['REPLICATE_PARAMS'] = 	Type::unserializeArray($data['REPLICATE_PARAMS']);

		// adapt responsibles

		$responsibles = array();
		if($data['MULTITASK'] == 'Y')
		{
			$responsibles = Type::unserializeArray($data['RESPONSIBLES']);
		}
		unset($data['RESPONSIBLES']);

		if(empty($responsibles))
		{
			$responsibles = array(
				$data['RESPONSIBLE_ID']
			);
		}

		$data[Responsible::getCode(true)] = static::formatSetResponsible($responsibles);

		// todo: temporal formatters, implement model object with array access and lazy data fetch/format
		Originator::formatSet($data);
		Auditor::formatSet($data);
		Accomplice::formatSet($data);
		ParentTask::formatSet($data);
		Project::formatSet($data);

		$deadline = static::getDeadLine($data['DEADLINE_AFTER']);
		if($deadline != '')
		{
			$data['DEADLINE'] = $deadline;
		}

		// select sub-entity related data

		if(!is_array($parameters['ENTITY_SELECT']))
		{
			$parameters['ENTITY_SELECT'] = array(); // none by default
		}
		$entitySelect = array_flip($parameters['ENTITY_SELECT']);

		if(isset($entitySelect['CHECKLIST']))
		{
			$mgrResult = Template\Checklist::getListByParentEntity($userId, $itemId, $parameters);
			$data[static::SE_PREFIX.'CHECKLIST'] = $mgrResult['DATA'];
			if(!empty($mgrResult['CAN']))
			{
				$can[static::SE_PREFIX.'CHECKLIST'] = $mgrResult['CAN'];
			}
		}

		$tags = Type::unserializeArray($data['TAGS']);
		unset($data['TAGS']);
		if(isset($entitySelect['TAG']))
		{
			// in task entity tags come from a separate table, in template - from the same table
			$seTag = array();
			foreach($tags as $tag)
			{
				$seTag[] = array('NAME' => $tag);
			}
			$data[static::SE_PREFIX.'TAG'] = $seTag;
		}

		if($parameters['DROP_PRIMARY'])
		{
			unset($data['ID']);
			$can = array();
		}

		$data[static::ACT_KEY] = $can;
		$result['DATA'] = $data;
		$result['CAN'] = $can;

		return $result;
	}

	public static function getList($userId, array $parameters = array())
	{
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

	// todo: normal add() here later, with public mode check
	// todo: normalize data here!
	public static function add($userId, array $data, array $parameters = array('PUBLIC_MODE' => false))
	{
		$userId = Assert::expectIntegerPositive($userId, '$userId');

		$errors = static::ensureHaveErrorCollection($parameters);

		if($parameters['PUBLIC_MODE'])
		{
			$data = static::filterData($data, $errors);
		}

		if($errors->checkNoFatals())
		{
			$responsibles = Responsible::extractPrimaryIndexes($data[Responsible::getCode(true)]);

			// todo: temporal adapters, implement model object with array access and lazy adaptation
			static::adaptSet($data);

			// serialize some...
			$data["ACCOMPLICES"] = 		Type::serializeArray($data["ACCOMPLICES"], true);
			$data["AUDITORS"] = 		Type::serializeArray($data["AUDITORS"], true);
			$data["RESPONSIBLES"] = 	Type::serializeArray($responsibles, true);
			$data["TAGS"] = 			Type::serializeArray($data["TAGS"], true);
			$data["REPLICATE_PARAMS"] = Type::serializeArray($data["REPLICATE_PARAMS"], true);

			if(count($responsibles) > 1) // template represents multitask in case of multiple responsibles
			{
				$data['MULTITASK'] = 'Y';
			}

			// todo: CTaskTemplates::add() hates unknown fields in $data. Improve CTaskTemplates::add() to make it ignore garbage in input
			$dataToAdd = $data;
			unset($dataToAdd['DEPENDS_ON']);
			foreach($dataToAdd as $key => $v)
			{
				if(strpos($key, static::SE_PREFIX) === 0)
				{
					unset($dataToAdd[$key]);
				}
			}

			$templateInstance = new \CTaskTemplates();
			$itemId = $templateInstance->add($dataToAdd);

			if($itemId)
			{
				$subEntityParams = array_merge(
					$parameters, array('MODE' => static::MODE_ADD)
				);

				if(is_array($data[static::SE_PREFIX.'CHECKLIST']))
				{
					Template\Checklist::manageSet($userId, $itemId, $data[static::SE_PREFIX.'CHECKLIST'], $subEntityParams);
				}
			}

			$resultErrors = $templateInstance->getErrors();
			if(!empty($resultErrors))
			{
				$errors->addForeignErrors($resultErrors);
			}
		}

		return array(
			'DATA' => array('ID' => $itemId),
			'ERRORS' => $errors,
		);
	}

	// todo: make normal update() here, without spiking REPLICATE and with accepting members as arrays
	// todo: normalize data here
	public static function update($userId, $itemId, array $data, array $parameters = array('PUBLIC_MODE' => false))
	{
		$userId = 		Assert::expectIntegerPositive($userId, '$userId');
		$itemId = 		Assert::expectIntegerPositive($itemId, '$itemId');

		$errors = static::ensureHaveErrorCollection($parameters);

		if($parameters['PUBLIC_MODE'])
		{
			$data = static::filterData($data, $errors);
		}

		$template = static::getTemplate($itemId);
		if(intval($template['ID']))
		{
			$templateData = $template;
		}
		else
		{
			$errors->add('ACCESS_DENIED.NO_TEMPLATE', 'Template not found');
			return;
		}
		$prevReplicateData = Type::unserializeArray($templateData['REPLICATE_PARAMS']);

		if(array_key_exists('REPLICATE_PARAMS', $data))
		{
			$times = intval($data['REPLICATE_PARAMS']['TIMES']);
			$data['REPLICATE_PARAMS'] = Type::serializeArray($data['REPLICATE_PARAMS']);
		}
		else
		{
			$times = false; // times unchanged
		}

		// reset replication counter when TIMES was changed
		if($times !== false && intval($prevReplicateData['TIMES']) != $times)
		{
			$data['TPARAM_REPLICATION_COUNT'] = 1;
		}

		$templateInstance = new \CTaskTemplates();
		$templateInstance->update($itemId, $data);

		$resultErrors = $templateInstance->getErrors();
		if(!empty($resultErrors))
		{
			$errors->addForeignErrors($resultErrors);
		}
	}

	// task-specific
	public static function manageTaskReplication($userId, $taskId, array $taskData = array(), array $parameters = array('PUBLIC_MODE' => false, 'MODE' => self::MODE_ADD))
	{
		$errors = static::ensureHaveErrorCollection($parameters);
		$result = array(
			'ERRORS' => $errors
		);

		$templateKey = static::getCode(true);
		$replicateKey = 'REPLICATE';

		if(!array_key_exists($templateKey, $taskData) && !array_key_exists($replicateKey, $taskData))
		{
			return $result; // nothing to do
		}

		// todo: when we edit task with rights loose, this condition will make troubles. temporary commented out
		/*
		$task = static::getTask($userId, $taskId);
		if($parameters['MODE'] != self::MODE_ADD && !$task->isActionAllowed(\CTaskItem::ACTION_EDIT)) // on edit check rights we must
		{
			throw new \Bitrix\Tasks\ActionNotAllowedException();
		}
		*/

		$data = array();

		$replicate = $taskData[$replicateKey] == 'Y';
		$templateId = false;

		if($replicate) // replication was changed to true
		{
			if(array_key_exists($templateKey, $taskData)) // replication data defined
			{
				$replicateParams = $taskData[$templateKey]['REPLICATE_PARAMS'];

				if($parameters['MODE'] == static::MODE_ADD) // task add, replicate = y
				{
					// then add template
					$addResult = static::add($userId, static::getTemplateDataByTaskData($taskId, $taskData), $parameters);
					$data['ID'] = $addResult['DATA']['ID'];
				}
				elseif($parameters['MODE'] == static::MODE_UPDATE) // task update, replicate = y
				{
					$template = static::getByParentTask(false, $taskId);
					if(intval($template['DATA']['ID'])) // then update template
					{
						static::update($userId, intval($template['DATA']['ID']), array(
							'REPLICATE_PARAMS' => $replicateParams,
							'REPLICATE' => 'Y' // required for agent re-creation by update()
						), $parameters);
						$templateId = intval($template['DATA']['ID']);
					}
					else // no template? add!
					{
						$addResult = static::add($userId, static::getTemplateDataByTaskData($taskId, $taskData), $parameters);
						$templateId = $addResult['DATA']['ID'];
					}
				}
			}
		}
		else
		{
			if($parameters['MODE'] == static::MODE_UPDATE)
			{
				$template = static::getByParentTask(false, $taskId);
				if(intval($template['DATA']['ID'])) // then update template
				{
					$templateInstance = new \CTaskTemplates();
					$templateInstance->delete(intval($template['DATA']['ID']));
				}
			}
		}

		if($templateId)
		{
			$data['ID'] = $templateId;
		}

		$result['DATA'] = $data;

		return $result;
	}

	public static function getByParentTask($userId, $taskId)
	{
		$access = array();
		if($userId !== false)
		{
			$access = array('USER_ID' => $userId);
		}

		$item = \CTaskTemplates::getList(array(), array('TASK_ID' => $taskId), array(), $access, array(
			'ID', 'TASK_ID', 'TITLE', 'REPLICATE_PARAMS', 'TPARAM_TYPE', 'CREATED_BY'
		))->fetch();

		$data = array();
		if(is_array($item))
		{
			// there are lots of garbage come in $item even if 'select' array defined
			$data = array(
				'ID' => 				$item['ID'],
				'TITLE' => 				$item['TITLE'],
				'TASK_ID' => 			$item['TASK_ID'],
				'TPARAM_TYPE' => 		$item['TPARAM_TYPE'],
				'CREATED_BY' =>         $item['CREATED_BY'],
				'REPLICATE_PARAMS' => 	Type::unserializeArray($item['REPLICATE_PARAMS'])
			);
		}

		return array(
			'DATA' => $data
		);
	}

	// todo: make more clever function which will return all errors and also check other part of data
	protected static function filterData(array $data, Collection $errors)
	{
		return $data;
	}

	private static function formatSetResponsible(array $responsibles)
	{
		$data = array();
		$responsibles = \Bitrix\Tasks\Util\Type::normalizeArray($responsibles);
		foreach($responsibles as $item)
		{
			$data[] = array('ID' => intval($item));
		}

		return $data;
	}

	private static function getDeadLine($deadlineAfter)
	{
		$deadlineAfter = intval($deadlineAfter);

		if ($deadlineAfter)
		{
			global $DB;

			$deadlineAfter = $deadlineAfter / 86400; // to days
			return date($DB->DateFormatToPHP(\CSite::GetDateFormat("FULL")), strtotime(date("Y-m-d 00:00")." +".$deadlineAfter." days"));
		}

		return '';
	}

	private static function getTemplateDataByTaskData($taskId, array $data)
	{
		$replicate = $data[static::SE_PREFIX.'TEMPLATE']['REPLICATE_PARAMS'];
		$data['REPLICATE_PARAMS'] = $replicate;

		if($data['MULTITASK'] != 'Y' || empty($data['RESPONSIBLES']))
		{
			unset(
				$data['MULTITASK'],
				$data['RESPONSIBLES']
			);
		}

		unset(
			$data['DEADLINE'],
			$data['START_DATE_PLAN'],
			$data['END_DATE_PLAN'],
			$data['DURATION_TYPE'],

			$data[static::SE_PREFIX.'TEMPLATE'],
			$data[static::SE_PREFIX.'REMINDER'],
			$data[static::SE_PREFIX.'PROJECTDEPENDENCE'],

			$data[\Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode()]
		);

		$data['TASK_ID'] = $taskId;

		return $data;
	}

	private static function getTemplate($id)
	{
		$item = \CTaskTemplates::getList(array(), array('ID' => $id), array(), array(), array(
			'ID', 'TASK_ID', 'TITLE', 'REPLICATE_PARAMS', 'TPARAM_TYPE'
		))->fetch();

		return $item;
	}
}