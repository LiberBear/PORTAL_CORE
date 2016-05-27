<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Integration;

abstract class SocialNetwork extends \Bitrix\Tasks\Integration
{
	const MODULE_NAME = 'socialnetwork';

	private static $enabled = true;

	public static function enable()
	{
		static::$enabled = true;
	}
	public static function disable()
	{
		static::$enabled = false;
	}
	public static function isEnabled()
	{
		return static::$enabled;
	}

    /**
     * Get data for user selector dialog
     *
     * @param string $context
     * @param array $parameters
     * @return array
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
	public static function getLogDestination($context = 'TASKS', array $parameters = array())
	{
		if(!static::includeModule())
		{
			return array();
		}

        $destinationParams = array();
        if(intval($parameters['AVATAR_HEIGHT']) && intval($parameters['AVATAR_WIDTH']))
        {
            $destinationParams['THUMBNAIL_SIZE_WIDTH'] = intval($parameters['AVATAR_WIDTH']);
            $destinationParams['THUMBNAIL_SIZE_HEIGHT'] = intval($parameters['AVATAR_HEIGHT']);
        }

		global $USER;

		if(!is_object($USER))
		{
			throw new \Bitrix\Main\SystemException('Global user is not defined');
		}

		$structure = \CSocNetLogDestination::GetStucture(array());
		$destination = array(
			"DEST_SORT" => \CSocNetLogDestination::GetDestinationSort(array(
				"DEST_CONTEXT" => $context,
				"ALLOW_EMAIL_INVITATION" => \Bitrix\Main\ModuleManager::isModuleInstalled("mail"),
			)),
			"LAST" => array("USERS" => array(), "SONETGROUPS" => array()),
			"DEPARTMENT" => $structure["department"],
			"DEPARTMENT_RELATION" => $structure["department_relation"],
			"DEPARTMENT_RELATION_HEAD" => $structure["department_relation_head"],
			/*
			"SELECTED" => array(
				"USERS" => array($USER->GetId())
			)
			*/
		);

		\CSocNetLogDestination::fillLastDestination($destination["DEST_SORT"], $destination["LAST"]);

		if (\Bitrix\Main\Loader::includeModule("extranet") && !\CExtranet::isIntranetUser())
		{
			$destination["EXTRANET_USER"] = "Y";
			$destination["USERS"] = \CSocNetLogDestination::getExtranetUser($destinationParams);
		}
		else
		{
			$destUser = array();
			foreach ($destination["LAST"]["USERS"] as $value)
			{
				$destUser[] = str_replace("U", "", $value);
			}

			$destination["EXTRANET_USER"] = "N";
			$destination["USERS"] = \CSocNetLogDestination::getUsers(array_merge($destinationParams, array("id" => $destUser)));
		}

		$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 3153600 : 3600*4;
		$cacheId = "dest_project_".$USER->GetId().md5(serialize($parameters));
		$cacheDir = "/tasks/dest/".$USER->GetId();
		$cache = new \CPHPCache;
		if($cache->initCache($cacheTtl, $cacheId, $cacheDir))
		{
			$destination["SONETGROUPS"] = $cache->getVars();
		}
		else
		{
			$cache->startDataCache();
			$destination["SONETGROUPS"] = \CSocNetLogDestination::getSocnetGroup(array_merge($destinationParams, array("GROUP_CLOSED" => "N", "features" => array("tasks", array("view")))));
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->startTagCache($cacheDir);
				foreach($destination["SONETGROUPS"] as $val)
				{
					$CACHE_MANAGER->registerTag("sonet_features_G_".$val["entityId"]);
					$CACHE_MANAGER->registerTag("sonet_group_".$val["entityId"]);
				}
				$CACHE_MANAGER->registerTag("sonet_user2group_U".$USER->GetId());
				$CACHE_MANAGER->endTagCache();
			}
			$cache->endDataCache($destination["SONETGROUPS"]);
		}

		return $destination;
	}

    /**
     * Save last selected items in user selector dialog
     *
     * @param array $items
     * @param string $context
     */
	public static function setLogDestinationLast(array $items = array(), $context = 'TASKS')
	{
		if(!static::includeModule())
		{
			return;
		}

		$result = array();

		if(is_array($items['USER']))
		{
			$items['USER'] = array_unique($items['USER']);
			foreach($items['USER'] as $userId)
			{
				if(intval($userId))
				{
					$result[] = 'U'.$userId;
				}
			}
		}

		if(is_array($items['SGROUP']))
		{
			$items['SGROUP'] = array_unique($items['SGROUP']);
			foreach($items['SGROUP'] as $groupId)
			{
				if(intval($groupId))
				{
					$result[] = 'SG'.$groupId;
				}
			}
		}

		\Bitrix\Main\FinderDestTable::merge(array(
			"CONTEXT" => $context,
			"CODE" => $result
		));
	}

	public static function getParser(array $parameters = array())
	{
		if(!static::includeModule())
		{
			return null;
		}

		static $parser;
		if($parser == null)
		{
			$parser = new \logTextParser(false, $parameters["PATH_TO_SMILE"]);
		}

		return $parser;
	}

	public static function extractPublicGroupData(array $group)
	{
		$safe = array(
			'NAME' => $group['NAME'],
		);

		if(intval($group['ID']))
		{
			$safe['ID'] = intval($group['ID']);
		}

		return $safe;
	}

	public static function formatDateTimeToGMT($time, $userId)
	{
		if(!static::includeModule())
		{
			return $time;
		}

		return \Bitrix\Socialnetwork\ComponentHelper::formatDateTimeToGMT($time, $userId);
	}
}