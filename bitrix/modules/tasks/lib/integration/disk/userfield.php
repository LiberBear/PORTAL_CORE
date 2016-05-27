<?
/**
 * Class implements all further interactions with "disk" module considering "task" entity
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Disk;

final class UserField extends \Bitrix\Tasks\Integration\Disk
{
	public static function getMainSysUFCode()
	{
		return 'UF_TASK_WEBDAV_FILES';
	}
}