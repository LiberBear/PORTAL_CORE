<?php
/**
 * Created by PhpStorm.
 * User: zg
 * Date: 20.06.2015
 * Time: 15:50
 */

namespace Bitrix\Crm\Integration;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
class IntranetManager
{
	/**
	* Check if user is head of any company departmant
	* @param integer $userID User ID
	* @return boolean
	*/
	public static function isSupervisor($userID)
	{
		if(!Loader::includeModule('intranet'))
		{
			return false;
		}

		$dbResult = \CIntranetUtils::GetSubordinateDepartmentsList($userID);
		return is_array($dbResult->Fetch());
	}

	/**
	* Check if user is extranet user
	* @param integer $userID User ID
	* @return boolean
	*/
	public static function isExternalUser($userID)
	{
		if(!ModuleManager::isModuleInstalled('extranet'))
		{
			return false;
		}

		$dbResult = \CUser::getList(
			$o = 'ID',
			$b = 'ASC',
			array('ID_EQUAL_EXACT' => $userID),
			array('FIELDS' => array('ID'), 'SELECT' => array('UF_DEPARTMENT'))
		);

		$user = $dbResult->Fetch();
		return !(is_array($user)
			&& isset($user['UF_DEPARTMENT'])
			&& isset($user['UF_DEPARTMENT'][0])
			&& $user['UF_DEPARTMENT'][0] > 0);
	}
}