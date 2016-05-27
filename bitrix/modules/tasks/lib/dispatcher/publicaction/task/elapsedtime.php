<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Task;

use \Bitrix\Tasks\Util\Error\Collection;
use \Bitrix\Tasks\Manager;

final class ElapsedTime extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{
	/**
	 * Get all elapsed time items for a specified task
	 */
	public function getListByTask($taskId, array $order = array(), array $filter = array())
	{
		global $USER;

		$result = array();

		if($taskId = $this->checkTaskId($taskId))
		{
			$result = Manager\Task\ElapsedTime::getListByParentEntity($USER->GetId(), $taskId);
		}

		return $result;
	}

	/**
	 * Add a new elapsed time record to a specified task
	 */
	public function add(array $data, array $parameters = array())
	{
		global $USER;

		$mgrResult = Manager\Task\ElapsedTime::add($USER->GetId(), $data, array(
			'PUBLIC_MODE' => true,
			'ERRORS' => $this->errors,
			'RETURN_ENTITY' => $parameters['RETURN_ENTITY'], // just an exception for this type of entity
		));

		return array(
			'DATA' => $mgrResult['DATA'],
			'CAN' => $mgrResult['CAN'],
		);
	}

	/**
	 * Update an elapsed time record
	 */
	public function update($id, array $data, array $parameters = array())
	{
		global $USER;

		$mgrResult = Manager\Task\ElapsedTime::update($USER->GetId(), $id, $data, array(
			'PUBLIC_MODE' => true,
			'ERRORS' => $this->errors,
			'RETURN_ENTITY' => $parameters['RETURN_ENTITY'],  // just an exception for this type of entity
		));

		return array(
			'DATA' => $mgrResult['DATA'],
			'CAN' => $mgrResult['CAN'],
		);
	}

	/**
	 * Delete an elapsed time record
	 */
	public function delete($id)
	{
		global $USER;

		$result = array();

		if($id = $this->checkId($id))
		{
			// get task id
			$taskId = $this->getOwnerTaskId($id);
			if($taskId)
			{
				$task = \CTaskItem::getInstanceFromPool($taskId, $USER->GetId()); // or directly, new \CTaskItem($taskId, $USER->GetId());
				$item = new \CTaskElapsedItem($task, $id);
				$item->delete();
			}
		}

		return $result;
	}

	private function getOwnerTaskId($itemId)
	{
		$item = \CTaskElapsedTime::getList(array(), array('ID' => $itemId), array('skipJoinUsers' => false))->fetch();
		if(is_array($item) && !empty($item))
		{
			return $item['TASK_ID'];
		}
		else
		{
			$this->errors->add('ITEM_NOT_FOUND', 'Item not found');
		}

		return false;
	}

	protected function checkId($id)
	{
		return parent::checkId($id, 'Item');
	}
}