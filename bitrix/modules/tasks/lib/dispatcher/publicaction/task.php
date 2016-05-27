<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 * 
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction;

use \Bitrix\Tasks\Manager;
use \Bitrix\Tasks\Util;

final class Task extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{
	/**
	 * Get a task
	 */
	public function get($id, array $parameters = array())
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$mgrResult = Manager\Task::get(Util\User::getId(), $id, array(
				'ENTITY_SELECT' => $parameters['ENTITY_SELECT'],
				'PUBLIC_MODE' => true,
				'ERRORS' => $this->errors
			));

			if($this->errors->checkNoFatals())
			{
				$result = array(
					'DATA' => $mgrResult['DATA'],
					'CAN' => $mgrResult['CAN']
				);
			}
		}

		return $result;
	}

	/**
	 * Get a list of tasks
	 */
	public function getList(array $order = array(), array $filter = array(), array $select = array(), array $parameters = array())
	{
		$result = array();

		// ID is required
		$select[] = 'ID';

		$mgrResult = Manager\Task::getList(Util\User::getId(), array(
			'LIST_PARAMETERS' => array(
				'order' => $order,
				'legacyFilter' => $filter,
				'select' => $select,
			),
			'PUBLIC_MODE' => true
		));

		$this->errors->addForeignErrors($mgrResult['ERRORS']);

		if($mgrResult['ERRORS']->checkNoFatals())
		{
			$result = array(
				'DATA' => $mgrResult['DATA'],
				'CAN' => $mgrResult['CAN']
			);
		}

		return $result;
	}

	/**
	 * Add a new task
	 */
	public function add(array $data, array $parameters = array('RETURN_DATA' => false))
	{
		$mgrResult = Manager\Task::add(Util\User::getId(), $data, array(
			'PUBLIC_MODE' => true,
			'ERRORS' => $this->errors,
			// there also could be RETURN_CAN or RETURN_DATA, or both as RETURN_ENTITY
			'RETURN_ENTITY' => $parameters['RETURN_ENTITY']
		));

		return array(
			'DATA' => $mgrResult['DATA'],
			'CAN' => $mgrResult['CAN'],
		);
	}

	/**
	 * Update a task with some new data
	 */
	public function update($id, array $data, array $parameters = array())
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			if(!empty($data)) // simply nothing to do, not a error
			{
				$mgrResult = Manager\Task::update(Util\User::getId(), $id, $data, array(
					'PUBLIC_MODE' => true,
					'ERRORS' => $this->errors,
					'THROTTLE_MESSAGES' => $parameters['THROTTLE_MESSAGES'],

					// there also could be RETURN_CAN or RETURN_DATA, or both as RETURN_ENTITY
					'RETURN_ENTITY' => $parameters['RETURN_ENTITY'],
				));

				$result['DATA'] = $mgrResult['DATA'];
				$result['CAN'] = $mgrResult['CAN'];

				if($this->errors->checkNoFatals())
				{
					if($parameters['RETURN_OPERATION_RESULT_DATA'])
					{
						$task = $mgrResult['TASK'];
						$result['OPERATION_RESULT'] = $task->getLastOperationResultData('UPDATE');
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Delete a task
	 */
	public function delete($id, array $parameters = array())
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			// this will ONLY delete tags, members, favorites, old depedences, old files, clear cache
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->delete();
		}

		return $result;
	}

    /**
     * Delegates a task to a new responsible
     */
    public function delegate($id, $userId)
    {
		$result = array();

		if($id = $this->checkTaskId($id))
        {
            $task = \CTaskItem::getInstance($id, Util\User::getId());
            $task->delegate($userId);
        }

		return $result;
    }

	/**
	 * Get a list of actions that you can do with a specified task
	 */
	/*
	public function getAllowedActions($id)
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$result = Manager\Task::getAllowedActions($id, Util\User::getId());
		}

		return $result;
	}
	*/

	/**
	 * Check if a specified task is readable by the current user
	 */
	public function checkCanRead($id)
	{
		$result = array('READ' => false);

		if($id = $this->checkTaskId($id))
		{
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$result['READ'] = $task->checkCanRead();
		}

		return $result;
	}

	/**
	 * Start execution of a specified task
	 */
	public function start($id)
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->startExecution();
		}

		return $result;
	}

	/**
	 * Pause execution of a specified task
	 */
	public function pause($id)
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->pauseExecution();
		}

		return $result;
	}

	/**
	 * Complete a specified task
	 */
	public function complete($id)
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->complete();
		}

		return $result;
	}

	/**
	 * Accept a specified task
	 */
	/*
	public function accept($id)
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->accept();
		}

		return $result;
	}
	*/

	/**
	 * Decline a specified task
	 */
	/*
	public function decline($id)
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->decline();
		}

		return $result;
	}
	*/

	/**
	 * Renew (switch to status "pending, await execution") a specified task
	 */
	public function renew($id)
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->renew();
		}

		return $result;
	}

	/**
	 * Defer (put aside) a specified task
	 */
	public function defer($id)
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->defer();
		}

		return $result;
	}

	/**
	 * Approve (confirm the result of) a specified task
	 */
	public function approve($id)
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->approve();
		}

		return $result;
	}

	/**
	 * Disapprove (reject the result of) a specified task
	 */
	public function disapprove($id)
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->disapprove();
		}

		return $result;
	}

	/**
	 * Become an auditor of a specified task
	 */
	public function enterAuditor($id)
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->startWatch();
		}

		return $result;
	}

	/**
	 * Stop being an auditor of a specified task
	 */
	public function leaveAuditor($id)
	{
		$result = array();

		if($id = $this->checkTaskId($id))
		{
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->stopWatch();
		}

		return $result;
	}
}