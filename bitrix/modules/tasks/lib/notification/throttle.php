<?php
namespace Bitrix\Tasks\Notification;

use Bitrix\Main\Entity;
//use Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

/**
 * Class ThrottleTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TASK_ID int mandatory
 * <li> STATE_ORIG string optional
 * <li> STATE_LAST string optional
 * </ul>
 *
 * @package Bitrix\Tasks
 **/

final class ThrottleTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_msg_throttle';
	}

	public static function submitUpdateMessage($taskId, $authorId, array $stateOrig, array $stateLast)
	{
		global $USER;

		if(!intval($authorId))
		{
			throw new \Bitrix\Tasks\Exception('Incorrect author id');
		}

		$item = static::getByTaskId($taskId);
		if($item['ID'])
		{
			$last = unserialize($item['STATE_LAST']);
			if(is_array($last) && is_array($stateLast))
			{
				$stateLast = array_merge($last, $stateLast);
			}

			$data = array(
				'STATE_LAST' => serialize($stateLast)
			);

			// if the next change was made by someone else, the origin author should know about that
			if($authorId != $item['AUTHOR_ID'])
			{
				$data['INFORM_AUTHOR'] = 1;
			}

			static::update($item['ID'], $data);
		}
		else
		{
			static::add(array(
				'TASK_ID' => $taskId,
				'AUTHOR_ID' => $authorId,
				'STATE_ORIG' => serialize($stateOrig),
				'STATE_LAST' => serialize($stateLast)
			));
		}
	}

	public static function getUpdateMessages()
	{
		$result = array();

		$res = static::getList(array('select' => array('TASK_ID', 'AUTHOR_ID', 'STATE_ORIG', 'STATE_LAST', 'INFORM_AUTHOR')));
		static::cleanUp();
		while($item = $res->fetch())
		{
			$rcpIgnore = array();
			if(!intval($item['INFORM_AUTHOR']))
			{
				$rcpIgnore[$item['AUTHOR_ID']] = true;
			}

			$stateOrig = unserialize($item['STATE_ORIG']);
			if(!is_array($stateOrig))
			{
				$stateOrig = array();
			}
			$stateLast = unserialize($item['STATE_LAST']);
			if(!is_array($stateLast))
			{
				$stateLast = array();
			}

			$result[$item['TASK_ID']] = array(
				'STATE_ORIG' => 	$stateOrig,
				'STATE_LAST' => 	$stateLast,
				'AUTHOR_ID' => 		$item['AUTHOR_ID'],
				'TASK_ID' => 		$item['TASK_ID'],
				'IGNORE_RECEPIENTS' => $rcpIgnore
			);
		}

		return $result;
	}

	public static function cleanUp()
	{
		global $DB;

		$DB->query("delete from ".static::getTableName());
	}

	private static function getByTaskId($taskId)
	{
		global $DB;

		$item = $DB->query("select ID, AUTHOR_ID, STATE_LAST from ".static::getTableName()." where TASK_ID = '".intval($taskId)."'")->fetch();
		return $item;
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				//'title' => Loc::getMessage('UPDATE_MSG_QUEUE_ENTITY_ID_FIELD'),
			),
			'TASK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				//'title' => Loc::getMessage('UPDATE_MSG_QUEUE_ENTITY_TASK_ID_FIELD'),
			),
			'AUTHOR_ID' => array(
				'data_type' => 'integer',
				//'title' => Loc::getMessage('MSG_THROTTLE_ENTITY_AUTHOR_ID_FIELD'),
			),
			'INFORM_AUTHOR' => array(
				'data_type' => 'boolean',
				'values' => array(0, 1)
				//'title' => Loc::getMessage('MSG_THROTTLE_ENTITY_INFORM_AUTHOR_FIELD'),
			),
			'STATE_ORIG' => array(
				'data_type' => 'text',
				//'title' => Loc::getMessage('UPDATE_MSG_QUEUE_ENTITY_STATE_ORIG_FIELD'),
			),
			'STATE_LAST' => array(
				'data_type' => 'text',
				//'title' => Loc::getMessage('UPDATE_MSG_QUEUE_ENTITY_STATE_LAST_FIELD'),
			),
		);
	}
}