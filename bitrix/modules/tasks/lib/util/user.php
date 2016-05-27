<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Util;

use \Bitrix\Tasks\Integration\Extranet;

final class User
{
	public static function get()
	{
		return $GLOBALS['USER'];
	}

	/**
	 * Get current user ID
	 * @return bool|null
	 */
	public static function getId()
	{
		global $USER;

		if(is_object($USER) && method_exists($USER, 'getId'))
		{
			$userId = $USER->getId();
			if($userId)
			{
				return $userId;
			}
		}

		return false;
	}

	/**
	 * Get admin user ID
	 * @return bool|int|null
	 */
	public static function getAdminId()
	{
		global $USER;

		$id = static::getId();
		if($id !== false && $USER->isAdmin())
		{
			return $id;
		}

		static $admin;

		if($admin === null)
		{
			$user = \CUser::GetList(
				($by = 'id'),
				($sort = 'asc'),
				array('GROUPS_ID' => array(1), 'ACTIVE' => 'Y'),
				array('FIELDS' => array('ID'), 'NAV_PARAMS' => array('nTopCount' => 1))
			)->fetch();

			if (is_array($user) && intval($user['ID']))
			{
				$admin = intval($user['ID']);
			}
		}

		return $admin === null ? false : $admin;
	}

	/**
	 * Return user id previously set by setOccurAsId()
	 *
	 * @return null
	 */
	public static function getOccurAsId()
	{
		return \CTasksPerHitOption::get('tasks', static::getOccurAsIdKey());
	}

	/**
	 * Set user id that will figure in all logs and notifications as the user performed an action.
	 * This allows to create task task under admin id and put to log someone else.
	 *
	 * In general, this is a hacky function, so it could be set deprecated in future as architecture changes.
	 *
	 * @param int $userId Use 0 or null or false to switch off user replacement
	 * @return string
	 */
	public static function setOccurAsId($userId = 0)
	{
		$userId = intval($userId);

		$key = static::getOccurAsIdKey();

		// todo: use user cache here, when implemented
		if(!$userId || !\CUser::getById($userId)->fetch())
		{
			$userId = null;
		}

		\CTasksPerHitOption::set('tasks', $key, $userId);

		return $key;
	}

	/**
	 * Check if a user with a given id is admin
	 *
	 * @param null $userId
	 * @return bool
	 */
	public static function isAdmin($userId = null)
	{
		global $USER;
		static $arCache = array();

		$isAdmin = false;
		$loggedInUserId = null;

		if ($userId === null)
		{
			if (is_object($USER) && method_exists($USER, 'GetID'))
			{
				$loggedInUserId = (int) $USER->GetID();
				$userId = $loggedInUserId;
			}
			else
			{
				$loggedInUserId = false;
			}
		}

		if ($userId > 0)
		{
			if ( ! isset($arCache[$userId]) )
			{
				if ($loggedInUserId === null)
				{
					if (is_object($USER) && method_exists($USER, 'GetID'))
					{
						$loggedInUserId = (int) $USER->GetID();
					}
				}

				if ((int)$userId === $loggedInUserId)
				{
					$arCache[$userId] = (bool)$USER->isAdmin();
				}
				else
				{
					/** @noinspection PhpDynamicAsStaticMethodCallInspection */
					$ar = \CUser::GetUserGroup($userId);
					if (in_array(1, $ar, true) || in_array('1', $ar, true))
						$arCache[$userId] = true;	// user is admin
					else
						$arCache[$userId] = false;	// user isn't admin
				}
			}

			$isAdmin = $arCache[$userId];
		}

		return ($isAdmin);
	}

	public static function isSuper($userId = null)
	{
		return static::isAdmin($userId) || \Bitrix\Tasks\Integration\Bitrix24\User::isAdmin($userId);
	}

	/**
	 * Return data for users we really can see
	 *
	 * todo: make static cache here, call this function everywhere (at least, in CTaskNotifications)
	 *
	 * @param array $userIds
	 * @return array
	 */
	public static function getData(array $userIds)
	{
		$users = array();
		$current = static::getId();

		if(empty($userIds))
		{
			$userIds = array($current);
		}

		$userIds = array_unique($userIds);
		$parsed = array();
		foreach($userIds as $userId)
		{
			if(intval($userId))
			{
				$parsed[] = $userId;
			}
		}

		if(!empty($parsed))
		{
			// we must skip "bus-users" and unaccessible extranet users

			$extranetUsers = Extranet\User::getAccessible();
			if(is_array($extranetUsers))
			{
				$extranetUsers = array_flip($extranetUsers);
			}

			$res = \CUser::GetList(($by = ""), ($order="asc"), array(
				'ID' => implode("|", $parsed),
				'!=EXTERNAL_AUTH_ID' => array('replica'/*, 'email'*/)
			), array('SELECT' => array('*', 'UF_DEPARTMENT')));
			while($item = $res->fetch())
			{
				$isExtranetUser = Extranet\User::isExtranet($item);

				if($item['ID'] != $current)
				{
					// todo: you must check what users you have access to
					/*
					if($isExtranetUser && !isset($extranetUsers[$item['ID']]))
					{
						continue;
					}
					*/
				}

				$item['IS_EXTRANET_USER'] = $isExtranetUser;
				$item['IS_EMAIL_USER'] = $item['EXTERNAL_AUTH_ID'] == 'email';

				$users[$item['ID']] = $item;
			}
		}

		return $users;
	}

	/**
	 * Extract user data suitable to publicise using json_encode() or CUtil::PhpToJsObject()
	 * @param array $user
	 * @return array
	 */
	public static function extractPublicData($user)
	{
		if(!is_array($user))
		{
			return array();
		}

		$safe = array(
			'NAME' => $user['NAME'],
			'LAST_NAME' => $user['LAST_NAME'],
			'SECOND_NAME' => $user['SECOND_NAME'],
			'LOGIN' => $user['LOGIN'],
			'WORK_POSITION' => $user['WORK_POSITION'],
			'PERSONAL_PHOTO' => $user['PERSONAL_PHOTO'],
			'PERSONAL_GENDER' => $user['PERSONAL_GENDER'],

			'IS_EXTRANET_USER' => $user['IS_EXTRANET_USER'],
			'IS_EMAIL_USER' => $user['IS_EMAIL_USER'],
		);

		if(intval($user['ID']))
		{
			$safe['ID'] = intval($user['ID']);
		}

		return $safe;
	}

	public static function formatName($data, $siteId = false)
	{
		return \CUser::FormatName(\Bitrix\Tasks\Util\Site::getUserNameFormat($siteId), $data, true, false);
	}

	public static function getTimeZoneOffsetCurrentUser()
	{
		$userId = static::getId();
		if(!$userId)
		{
			return 0; // server time
		}

		return static::getTimeZoneOffset($userId);
	}

	public static function getTimeZoneOffset($userId = false, $utc = false)
	{
		$userId = intval($userId);
		if(!$userId)
		{
			$userId = static::getId();
		}

		$disabled = !\CTimeZone::enabled();

		if($disabled)
		{
			\CTimeZone::enable();
		}

		$offset = static::getOffset($userId ? $userId : null) + ($utc ? \Bitrix\Tasks\Util::getServerTimeZoneOffset() : 0);

		if($disabled)
		{
			\CTimeZone::disable();
		}

		return intval($offset);
	}

	private function getOffset($userId)
	{
		static $cache = array();

		$key = 'U'.$userId;
		if (!array_key_exists($key, $cache))
		{
			$cache[$key] = \CTimeZone::getOffset($userId);
		}
		return $cache[$key];
	}

	private static function getOccurAsIdKey()
	{
		static $key;

		if($key == null)
		{
			$key = 'occurAs_key:' . md5(mt_rand(1000, 999999) . '-' . mt_rand(1000, 999999));
		}

		return $key;
	}
}