<?php
/**
 * This class contains ui helper for task/tag entity
 *
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Tasks\UI\Task;

final class Tag
{
	public static function formatTagString($tags)
	{
		if ($tags && is_array($tags))
		{
			$formatted = array();

			foreach ($tags as $tag)
			{
				if(is_array($tag) && !empty($tag['NAME']))
				{
					$formatted[] = (string) $tag['NAME'];
				}
				elseif(!empty($tag))
				{
					$formatted[] = (string) $tag;
				}
			}

			return implode(', ', $formatted);
		}

		return '';
	}
}