<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Integration;

use Bitrix\Bitrix24\Feature;
use \Bitrix\Tasks\Util;

abstract class Bitrix24 extends \Bitrix\Tasks\Integration
{
	const MODULE_NAME = 'bitrix24';

	public static function checkToolAvailable($toolName)
	{
		if(!static::includeModule()) // box installation, say yes
		{
			return true;
		}

		return \CBitrix24BusinessTools::isToolAvailable(Util\User::getId(), $toolName);
	}

	public static function checkFeatureEnabled($featureName)
	{
		if(!static::includeModule()) // box installation, say yes
		{
			return true;
		}

		if(Feature::isFeatureEnabled($featureName)) // trial is on = yes
		{
			return true;
		}

		return false;
	}
}