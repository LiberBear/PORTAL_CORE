<?php

namespace Bitrix\Tasks\Ui\Preview;

class Task
{
	public static function buildPreview(array $params)
	{
		global $APPLICATION;

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:tasks.task.preview',
			'',
			$params
		);
		return ob_get_clean();
	}

	public static function checkUserReadAccess(array $params)
	{
		$task = new \CTaskItem($params['taskId'], static::getUser()->GetID());
		$access = $task->checkCanRead();

		return !!$access;
	}

	protected function getUser()
	{
		global $USER;
		return $USER;
	}
}