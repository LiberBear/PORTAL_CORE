<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

use \Bitrix\Tasks\ElapsedTimeTable;
use \Bitrix\Tasks\TaskTable;

final class CTaskElapsedItem extends CTaskSubItemAbstract
{
	const ACTION_ELAPSED_TIME_ADD    = 0x01;
	const ACTION_ELAPSED_TIME_MODIFY = 0x02;
	const ACTION_ELAPSED_TIME_REMOVE = 0x03;

	const SOURCE_UNDEFINED = 0x01;	// unknown source
	const SOURCE_MANUAL    = 0x02;	// item was added by user or was modified by user
	const SOURCE_SYSTEM    = 0x03;	// item was added by system (automatically)


	/**
	 * @param CTaskItemInterface $oTaskItem
	 * @param array $arFields with mandatory elements MINUTES, COMMENT_TEXT
	 * @throws TasksException
	 * @return CTaskElapsedItem
	 */
	public static function add(CTaskItemInterface $oTaskItem, $arFields)
	{
		CTaskAssert::assert(
			is_array($arFields)
			&& isset($arFields['COMMENT_TEXT'])
			&& (
				(
					isset($arFields['MINUTES'])
					&& CTaskAssert::isLaxIntegers($arFields['MINUTES'])
				)
				|| (
					isset($arFields['SECONDS'])
					&& CTaskAssert::isLaxIntegers($arFields['SECONDS'])
				)
			)
			&& is_string($arFields['COMMENT_TEXT'])
		);

		if ( ! $oTaskItem->isActionAllowed(CTaskItem::ACTION_ELAPSED_TIME_ADD) )
			throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);

		$arFields['USER_ID'] = $oTaskItem->getExecutiveUserId();
		$arFields['TASK_ID'] = $oTaskItem->getId();

		/** @noinspection PhpDeprecationInspection */
		$obElapsed = new CTaskElapsedTime();
		$id = $obElapsed->Add($arFields);

		// Reset tagged system cache by tag 'tasks_user_' . $userId for each task member
		self::__resetSystemWideTasksCacheByTag($oTaskItem->getData(false));
		
		if ($id === false)
		{
			throw new TasksException('', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);
		}

		return (new self($oTaskItem, (int) $id));
	}


	public function delete()
	{
		if ( ! $this->isActionAllowed(self::ACTION_ELAPSED_TIME_REMOVE) )
			throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);

		/** @noinspection PhpDeprecationInspection */
		$rc = CTaskElapsedTime::delete($this->itemId, array('USER_ID' => $this->executiveUserId));

		// Reset tagged system cache by tag 'tasks_user_' . $userId for each task member
		$this->resetSystemWideTasksCacheByTag();
		
		// Reset cache
		$this->resetCache();

		if ( ! $rc )
			throw new TasksException('', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);
	}


	public function update($arFields)
	{
		static $allowedFields = array('MINUTES', 'SECONDS', 'COMMENT_TEXT', 'CREATED_DATE');

		if ( ! $this->isActionAllowed(self::ACTION_ELAPSED_TIME_MODIFY) )
			throw new TasksException('', TasksException::TE_ACTION_NOT_ALLOWED);

		// Ensure that only allowed fields given
		foreach (array_keys($arFields) as $fieldName)
			CTaskAssert::assert(in_array($fieldName, $allowedFields));

		// Nothing to do?
		if (empty($arFields))
			return;

		/** @noinspection PhpDeprecationInspection */
		$o  = new CTaskElapsedTime();
		$rc = $o->Update($this->itemId, $arFields, array('USER_ID' => $this->executiveUserId));

		// Reset tagged system cache by tag 'tasks_user_' . $userId for each task member
		$this->resetSystemWideTasksCacheByTag();

		// Reset cache
		$this->resetCache();

		if ( ! $rc )
			throw new TasksException('', TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED);

		return;
	}


	public function isActionAllowed($actionId)
	{
		$isActionAllowed = false;
		CTaskAssert::assertLaxIntegers($actionId);
		$actionId = (int) $actionId;

		$isAdmin = CTasksTools::IsAdmin($this->executiveUserId)
			|| CTasksTools::IsPortalB24Admin($this->executiveUserId);

		if ($actionId === self::ACTION_ELAPSED_TIME_ADD)
			$isActionAllowed = $this->oTaskItem->isActionAllowed(CTaskItem::ACTION_ELAPSED_TIME_ADD);
		elseif (($actionId === self::ACTION_ELAPSED_TIME_MODIFY) || ($actionId === self::ACTION_ELAPSED_TIME_REMOVE))
		{
			$arItemData = $this->getData($bEscape = false);
			if ($isAdmin || ($arItemData['USER_ID'] == $this->executiveUserId))
				$isActionAllowed = true;
		}

		return ($isActionAllowed);
	}

	final protected function fetchListFromDb($taskData, $arOrder = array('ID' => 'ASC'), $arFilter = array())
	{
		CTaskAssert::assertLaxIntegers($taskData['ID']);

		if(!isset($arOrder))
			$arOrder = array('ID' => 'ASC');

		if(!is_array($arFilter))
			$arFilter = array();

		$arFilter['TASK_ID'] = (int) $taskData['ID'];

		$arItemsData = array();
		/** @noinspection PhpDeprecationInspection */
		$rsData = CTaskElapsedTime::GetList(
			$arOrder,
			$arFilter
		);

		if ( ! is_object($rsData) )
			throw new Exception();

		while ($arData = $rsData->fetch())
			$arItemsData[] = $arData;

		return (array($arItemsData, $rsData));
	}

	final protected function fetchDataFromDb($taskId, $itemId)
	{
		/** @noinspection PhpDeprecationInspection */
		$rsData = CTaskElapsedTime::GetList(
			array(),
			array('ID' => (int) $itemId)
		);

		if (is_object($rsData) && ($arData = $rsData->fetch()))
			return ($arData);
		else
			throw new Exception();
	}

	/**
	 * Only for rest purposes
	 * 
	 */
	public static function getList(array $order = array(), array $filter = array(), array $select = array(), array $params = array())
	{
		global $USER;

		$parameters = array();

		if(is_array($order) && !empty($order))
		{
			$parameters['order'] = $order;
		}
		if(is_array($filter) && !empty($filter))
		{
			$parameters['filter'] = $filter;
		}
		if(is_array($select) && !empty($select))
		{
			$parameters['select'] = $select;
		}

		if(is_array($params) && is_array($params['NAV_PARAMS']))
		{
			if(isset($params['NAV_PARAMS']['nPageSize']))
			{
				$parameters['limit'] = (int) $params['NAV_PARAMS']['nPageSize'];
			}
			if(isset($params['NAV_PARAMS']['iNumPage']))
			{
				$parameters['offset'] = (int) $params['NAV_PARAMS']['iNumPage'];
			}
		}

		$res = \Bitrix\Tasks\Integration\Rest\ElapsedTimeTable::getList($parameters, array(
			'USER_ID' => $USER->GetId(),
			'ROW_LIMIT' => 50
		));

		$result = array();
		while($item = $res->fetch())
		{
			$result[] = $item;
		}

		return $result;
	}

	private static function __resetSystemWideTasksCacheByTag($arData)
	{
		global $CACHE_MANAGER;

		$arParticipants = array_unique(array_merge(
			array($arData['CREATED_BY'], $arData['RESPONSIBLE_ID']),
			$arData['ACCOMPLICES'],
			$arData['AUDITORS']
		));

		foreach ($arParticipants as $userId)
			$CACHE_MANAGER->ClearByTag('tasks_user_' . $userId);
	}


	private function resetSystemWideTasksCacheByTag()
	{
		$arData = $this->oTaskItem->getData($bEscape = false);
		self::__resetSystemWideTasksCacheByTag($arData);
	}


	public static function runRestMethod($executiveUserId, $methodName, $args,
		/** @noinspection PhpUnusedParameterInspection */ $navigation)
	{
		static $arManifest = null;
		static $arMethodsMetaInfo = null;

		if ($arManifest === null)
		{
			$arManifest = self::getManifest();
			$arMethodsMetaInfo = $arManifest['REST: available methods'];
		}

		// Check and parse params
		CTaskAssert::assert(isset($arMethodsMetaInfo[$methodName]));
		$arMethodMetaInfo = $arMethodsMetaInfo[$methodName];
		$argsParsed = CTaskRestService::_parseRestParams('ctaskelapseditem', $methodName, $args);

		$runAs = $methodName;
		if(isset($arMethodsMetaInfo[$methodName]['runAs']) && (string) $arMethodsMetaInfo[$methodName]['runAs'] != '')
		{
			$runAs = $arMethodsMetaInfo[$methodName]['runAs'];
		}

		$returnValue = null;
		if (isset($arMethodMetaInfo['staticMethod']) && $arMethodMetaInfo['staticMethod'])
		{
			if ($methodName === 'add')
			{
				$taskId    = $argsParsed[0];
				$arFields  = $argsParsed[1];
				$oTaskItem = CTaskItem::getInstance($taskId, $executiveUserId);	// taskId in $argsParsed[0]
				$oItem     = self::add($oTaskItem, $arFields);

				$returnValue = $oItem->getId();
			}
			elseif ($methodName === 'getlist')
			{
				$taskId = $argsParsed[0];
				$order = $argsParsed[1];
				$filter = $argsParsed[2];
				$oTaskItem = CTaskItem::getInstance($taskId, $executiveUserId);
				list($oElapsedItems, $rsData) = self::fetchList($oTaskItem, $order, $filter);

				$returnValue = array();

				foreach ($oElapsedItems as $oElapsedItem)
					$returnValue[] = $oElapsedItem->getData(false);
			}
			else
			{
				$returnValue = call_user_func_array(array('self', $runAs), $argsParsed);
			}
		}
		else
		{
			$taskId     = array_shift($argsParsed);
			$itemId     = array_shift($argsParsed);
			$oTaskItem  = CTaskItem::getInstance($taskId, $executiveUserId);
			$obElapsed  = new self($oTaskItem, $itemId);

			if ($methodName === 'get')
			{
				$returnValue = $obElapsed->getData();
			}
			else
			{
				$returnValue = call_user_func_array(array($obElapsed, $runAs), $argsParsed);
			}
		}

		return (array($returnValue, null));
	}

	public static function getPublicFieldMap()
	{
		// READ, WRITE, SORT, FILTER, DATE
		return array(
			'ID' => 			array(1, 0, 1, 1, 0),
			'TASK_ID' => 		array(1, 0, 1, 1, 0),
			'USER_ID' => 		array(1, 0, 1, 1, 0),
			'COMMENT_TEXT' => 	array(1, 1, 0, 0, 0),
			'SECONDS' => 		array(1, 1, 1, 0, 0),
			'MINUTES' => 		array(1, 0, 1, 0, 0),
			'SOURCE' => 		array(1, 1, 0, 0, 0),
			'CREATED_DATE' => 	array(1, 1, 1, 1, 1),
			'DATE_START' => 	array(1, 1, 1, 0, 1),
			'DATE_STOP' => 		array(1, 1, 1, 0, 1),
		);
	}

	/**
	 * This method is not part of public API.
	 * Its purpose is for internal use only.
	 * It can be changed without any notifications
	 * 
	 * @access private
	 */
	public static function getManifest()
	{
        // todo: get data from self::getPublicFieldMap()

		$arWritableKeys = 		array('SECONDS', 'COMMENT_TEXT', 'SOURCE', 'CREATED_DATE', 'DATE_START', 'DATE_STOP');
		$arSortableKeys = 		array('ID', 'USER_ID', 'MINUTES', 'SECONDS', 'CREATED_DATE', 'DATE_START', 'DATE_STOP', 'TASK_ID');
		$arAggregatableKeys = 	array('SECONDS', 'MINUTES', 'USER_ID', 'ID');
		$arDateKeys = 			array('CREATED_DATE', 'DATE_START', 'DATE_STOP');
		$arReadableKeys = array_unique(array_merge(
			$arDateKeys,
			$arSortableKeys,
			$arWritableKeys
		));
		$arFiltrableKeys = array('ID', 'USER_ID', 'CREATED_DATE', 'TASK_ID');
		$aggregations = array('MAX', 'MIN', 'COUNT', 'SUM', 'AVG');

		return(array(
			'Manifest version' => '1.1',
			'Warning' => 'don\'t rely on format of this manifest, it can be changed without any notification',
			'REST: shortname alias to class' => 'elapseditem',
			'REST: writable elapseditem data fields'   =>  $arWritableKeys,
			'REST: readable elapseditem data fields'   =>  $arReadableKeys,
			'REST: sortable elapseditem data fields'   =>  $arSortableKeys,
			'REST: filterable elapseditem data fields' =>  $arFiltrableKeys,
			'REST: date fields' =>  $arDateKeys,
			'REST: available methods' => array(
				'getmanifest' => array(
					'staticMethod' => true,
					'params'       => array()
				),
				'getlist' => array(
					'staticMethod'         =>  true,
					'mandatoryParamsCount' =>  1,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'arOrder',
							'type'        => 'array',
							'allowedKeys' => $arSortableKeys
						),
						array(
							'description' => 'arFilter',
							'type'        => 'array',
							'allowedKeys' => $arFiltrableKeys,
							'allowedKeyPrefixes' => array(
								'!', '<=', '<', '>=', '>'
							)
						),
					),
					'allowedKeysInReturnValue' => $arReadableKeys,
					'collectionInReturnValue'  => true
				),
				'listglobal' => array(
					'staticMethod'         =>  true,
					'runAs' => 'getlist',
					'params' => array(
						array(
							'description' => 'arOrder',
							'type'        => 'array',
							'allowedKeys' => $arSortableKeys,
							'allowedAggregations' => $aggregations,
							'allowedKeysInAggregation' => $arAggregatableKeys
						),
						array(
							'description' => 'arFilter',
							'type'        => 'array',
							'allowedKeys' => $arFiltrableKeys,
							'allowedKeyPrefixes' => array(
								'!', '<=', '<', '>=', '>'
							),
						),
						array(
							'description' => 'arSelect',
							'type'        => 'array',
							'allowedValues' => array_merge(array('*'), $arReadableKeys),
							'allowedAggregations' => $aggregations,
							'allowedValuesInAggregation' => $arAggregatableKeys
						),
						array(
							'description' => 'arParams',
							'type'        => 'array',
						),
					),
					'allowedKeysInReturnValue' => $arReadableKeys,
					'allowedAggregations' => $aggregations,
					'collectionInReturnValue'  => true,
				),
				'get' => array(
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						)
					),
					'allowedKeysInReturnValue' => $arReadableKeys
				),
				'add' => array(
					'staticMethod'         => true,
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'arFields',
							'type'        => 'array',
							'allowedKeys' => $arWritableKeys
						)
					)
				),
				'update' => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 3,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						),
						array(
							'description' => 'arFields',
							'type'        => 'array',
							'allowedKeys' => $arWritableKeys
						)
					)
				),
				'delete' => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 2,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						)
					)
				),
				'isactionallowed' => array(
					'staticMethod'         => false,
					'mandatoryParamsCount' => 3,
					'params' => array(
						array(
							'description' => 'taskId',
							'type'        => 'integer'
						),
						array(
							'description' => 'itemId',
							'type'        => 'integer'
						),
						array(
							'description' => 'actionId',
							'type'        => 'integer'
						)
					)
				)
			)
		));
	}
}

