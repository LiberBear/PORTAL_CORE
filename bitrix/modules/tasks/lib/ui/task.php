<?php
/**
 * This class contains ui helper for task entity
 *
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Tasks\UI;

use \Bitrix\Tasks\Util;

final class Task
{
	public static function makeCopyUrl($url, $taskId)
	{
		$taskId = intval($taskId);
		if(!$taskId)
		{
			return $url;
		}

		return Util::replaceUrlParameters($url, array('COPY' => $taskId));
	}

	public static function makeCreateSubtaskUrl($url, $taskId)
	{
		$taskId = intval($taskId);
		if(!$taskId)
		{
			return $url;
		}

		return Util::replaceUrlParameters($url, array('PARENT_ID' => $taskId));
	}

	public static function makeFireEventUrl($url, $taskId, $eventType, array $eventOptions = array())
	{
		$taskId = intval($taskId);
		if(!$taskId)
		{
			return $url;
		}

		$urlParams = array(
			'EVENT_TYPE' => $eventType,
			'EVENT_TASK_ID' => $taskId,
			'EVENT_OPTION[STAY_AT_PAGE]' => $eventOptions['STAY_AT_PAGE'],
		);

		return Util::replaceUrlParameters($url, $urlParams, array_keys($urlParams));
	}

	public static function cleanFireEventUrl($url)
	{
		$urlParams = array(
			'EVENT_TYPE',
			'EVENT_TASK_ID',
			'EVENT_OPTION[STAY_AT_PAGE]',
		);

		return Util::replaceUrlParameters($url, array(), $urlParams);
	}

	public static function makeActionUrl($path, $taskId = 0, $actionId = 'edit', $userId = false)
	{
		if((string) $path == '')
		{
			return '';
		}

		$taskId = intval($taskId);
		$actionId = $actionId == 'edit' ? 'edit' : 'view';
		$userId = intval($userId);
		if(!$userId)
		{
			$userId = \Bitrix\Tasks\Util\User::getId();
		}

		return \CComponentEngine::MakePathFromTemplate($path, array(
			"task_id" => $taskId,
			"action" => $actionId,
			"TASK_ID" => $taskId,
			"ACTION" => $actionId,
			"user_id" => $userId,
			"USER_ID" => $userId,
		));
	}
}