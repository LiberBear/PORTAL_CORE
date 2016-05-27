<?php
namespace Bitrix\Crm\Requisite;
use Bitrix\Main;

class EntityLink
{
	/**
	 * @param $parameters
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 */
	public static function getList($parameters)
	{
		return LinkTable::getList($parameters);
	}

	/**
	 * @param $entityTypeId
	 * @param $entityId
	 * @param $requisiteId
	 * @param int $bankDetailId
	 * @throws Main\ArgumentException
	 * @throws Main\NotSupportedException
	 */
	public static function register($entityTypeId, $entityId, $requisiteId, $bankDetailId = 0)
	{
		$errMsgGreaterThanZero = 'Must be greater than zero';

		$entityTypeId = (int)$entityTypeId;
		if($entityTypeId <= 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'entityTypeId');

		$entityId = (int)$entityId;
		if($entityId <= 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'entityId');

		$requisiteId = (int)$requisiteId;
		if($requisiteId <= 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'requisiteId');

		$bankDetailId = (int)$bankDetailId;
		if($bankDetailId < 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'bankDetailId');

		LinkTable::upsert(
			array(
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
				'REQUISITE_ID' => $requisiteId,
				'BANK_DETAIL_ID' => $bankDetailId
			)
		);
	}

	/**
	 * @param $entityTypeId
	 * @param $entityId
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	public static function unregister($entityTypeId, $entityId)
	{
		$errMsgGreaterThanZero = 'Must be greater than zero';

		$entityTypeId = (int)$entityTypeId;
		if($entityTypeId <= 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'entityTypeId');

		$entityId = (int)$entityId;
		if($entityId <= 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'entityId');

		LinkTable::delete(
			array(
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId
			)
		);
	}

	/**
	 * @param $requisiteId
	 * @throws Main\ArgumentException
	 * @throws Main\NotSupportedException
	 */
	public static function unregisterByRequisite($requisiteId)
	{
		$errMsgGreaterThanZero = 'Must be greater than zero';

		$requisiteId = (int)$requisiteId;
		if ($requisiteId <= 0)
			throw new Main\ArgumentException($errMsgGreaterThanZero, 'requisiteId');

		$connection = Main\Application::getConnection();

		if($connection instanceof Main\DB\MysqlCommonConnection
			|| $connection instanceof Main\DB\MssqlConnection
			|| $connection instanceof Main\DB\OracleConnection)
		{
			$tableName = 'b_crm_requisite_link';
			if ($connection instanceof Main\DB\MssqlConnection
				|| $connection instanceof Main\DB\OracleConnection)
			{
				$tableName = strtoupper($tableName);
			}
			$connection->queryExecute(
				"DELETE FROM {$tableName} WHERE REQUISITE_ID = {$requisiteId}"
			);
		}
		else
		{
			$dbType = $connection->getType();
			throw new Main\NotSupportedException("The '{$dbType}' is not supported in current context");
		}
	}
}