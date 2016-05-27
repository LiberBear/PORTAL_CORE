<?php
/**
 * This class contains ui helper for task/template entity
 *
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Tasks\UI\Task;

final class Template
{
	public static function makeActionUrl($path, $templateId = 0, $actionId = 'edit')
	{
		if((string) $path == '')
		{
			return '';
		}

		$templateId = intval($templateId);
		$actionId = $actionId == 'edit' ? 'edit' : 'view';

		return \CComponentEngine::MakePathFromTemplate($path, array(
			"template_id" => $templateId,
			"action" => $actionId,
			"TEMPLATE_ID" => $templateId,
			"ACTION" => $actionId,
		));
	}
}