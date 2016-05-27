<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
class ActivitySettings
{
	const UNDEFINED = 0;
	const KEEP_COMPLETED_CALLS = 1;
	const KEEP_COMPLETED_MEETINGS = 2;
	const KEEP_UNBOUND_TASKS = 3;
	const KEEP_REASSIGNED_CALLS = 4;
	const KEEP_REASSIGNED_MEETINGS = 5;
	const MARK_FORWARDED_EMAIL_AS_OUTGOING = 6;

	public static function isDefined($ID)
	{
		$ID = (int)$ID;
		return $ID > self::UNDEFINED && $ID <= self::MARK_FORWARDED_EMAIL_AS_OUTGOING;
	}

	public static function getValue($ID)
	{
		$ID = (int)$ID;
		if($ID === self::KEEP_COMPLETED_CALLS)
		{
			return Main\Config\Option::get('crm', 'act_cal_show_compl_call', 'Y', '') === 'Y';
		}
		elseif($ID === self::KEEP_COMPLETED_MEETINGS)
		{
			return Main\Config\Option::get('crm', 'act_cal_show_compl_meeting', 'Y', '') === 'Y';
		}
		elseif($ID === self::KEEP_UNBOUND_TASKS)
		{
			return Main\Config\Option::get('crm', 'act_task_keep_unbound', 'Y', '') === 'Y';
		}
		elseif($ID === self::KEEP_REASSIGNED_CALLS)
		{
			return Main\Config\Option::get('crm', 'act_cal_keep_reassign_call', 'Y', '') === 'Y';
		}
		elseif($ID === self::KEEP_REASSIGNED_MEETINGS)
		{
			return Main\Config\Option::get('crm', 'act_cal_keep_reassign_meeting', 'Y', '') === 'Y';
		}
		elseif($ID === self::MARK_FORWARDED_EMAIL_AS_OUTGOING)
		{
			return Main\Config\Option::get('crm', 'act_mark_fwd_emai_outgoing', 'N', '') === 'Y';
		}
		else
		{
			throw new Main\NotSupportedException("The setting '{$ID}' is not supported in current context");
		}
	}

	public static function setValue($ID, $value)
	{
		$ID = (int)$ID;
		if($ID === self::KEEP_COMPLETED_CALLS)
		{
			Main\Config\Option::set('crm', 'act_cal_show_compl_call', $value ? 'Y' : 'N', '');
		}
		elseif($ID === self::KEEP_COMPLETED_MEETINGS)
		{
			Main\Config\Option::set('crm', 'act_cal_show_compl_meeting', $value ? 'Y' : 'N', '');
		}
		elseif($ID === self::KEEP_UNBOUND_TASKS)
		{
			Main\Config\Option::set('crm', 'act_task_keep_unbound', $value ? 'Y' : 'N', '');
		}
		elseif($ID === self::KEEP_REASSIGNED_CALLS)
		{
			Main\Config\Option::set('crm', 'act_cal_keep_reassign_call', $value ? 'Y' : 'N', '');
		}
		elseif($ID === self::KEEP_REASSIGNED_MEETINGS)
		{
			Main\Config\Option::set('crm', 'act_cal_keep_reassign_meeting', $value ? 'Y' : 'N', '');
		}
		elseif($ID === self::MARK_FORWARDED_EMAIL_AS_OUTGOING)
		{
			Main\Config\Option::set('crm', 'act_mark_fwd_emai_outgoing', $value ? 'Y' : 'N', '');
		}
		else
		{
			throw new Main\NotSupportedException("The setting '{$ID}' is not supported in current context");
		}
	}
}