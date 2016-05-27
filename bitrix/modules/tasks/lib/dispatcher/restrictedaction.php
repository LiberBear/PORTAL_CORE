<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Dispatcher;

abstract class RestrictedAction extends PublicAction
{
	public function canExecute()
	{
		if(!\Bitrix\Tasks\Util\Restriction::canManageTask())
		{
			$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', 'Task managing is restricted for the current user');
			return false;
		}

		return true;
	}
}