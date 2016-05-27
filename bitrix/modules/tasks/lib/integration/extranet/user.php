<?
/**
 * Class implements all further interactions with "extranet" module
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Extranet;

final class User extends \Bitrix\Tasks\Integration\Extranet
{
	public static function getAccessible()
	{
		if(!static::includeModule())
		{
			return array();
		}

		return \CExtranet::getMyGroupsUsersSimple(\CExtranet::getExtranetSiteID());
	}

	public static function isExtranet($user = null)
	{
		if(!static::includeModule() || \CExtranet::getExtranetSiteID() == false)
		{
			return false; // no extranet - no problem, user is NOT AN EXTRANET USER
		}

		$userData = array();

		if(is_array($user))
		{
			if(!empty($user))
			{
				$userData = $user;
			}
		}
		else
		{
			if($user === null)
			{
				$user = \Bitrix\Tasks\Util\User::getId(); // check current
			}

			$user = intval($user);
			if($user)
			{
				// todo: make functionality to create "user cache" in \Bitrix\Tasks\Util\User and use it here
				$user = \CUser::getById($user)->fetch();
				if(is_array($user) && !empty($user))
				{
					$userData = $user;
				}
			}
		}

		if(empty($userData))
		{
			return false; // was unable to obtain user data
		}

		if(isset($userData['UF_DEPARTMENT']) && is_array($userData['UF_DEPARTMENT']))
		{
			if(empty($userData['UF_DEPARTMENT']))
			{
				return true;
			}
			else
			{
				$item = array_shift($userData['UF_DEPARTMENT']);
				if(empty($item))
				{
					return true;
				}
			}
		}

		return false;
	}
}