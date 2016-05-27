<?php


namespace Bitrix\Disk\Rest\Entity;


use Bitrix\Disk\Internals\AttachedObjectTable;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\Uf\BlogPostCommentConnector;
use Bitrix\Disk\Uf\BlogPostConnector;
use Bitrix\Disk\Uf\CalendarEventConnector;
use Bitrix\Disk\Uf\ForumMessageConnector;
use Bitrix\Disk\Uf\SonetCommentConnector;
use Bitrix\Disk\Uf\SonetLogConnector;
use Bitrix\Disk\Uf\TaskConnector;
use Bitrix\Main\Type\DateTime;

final class AttachedObject extends Base
{
	/**
	 * Gets all fields (DataManager fields).
	 * @return array
	 */
	public function getDataManagerFields()
	{
		return AttachedObjectTable::getMap();
	}

	/**
	 * Gets fields which entity can show in response.
	 * @return array
	 */
	public function getFieldsForShow()
	{
		return array(
			'ID' => true,
			'OBJECT_ID' => true,
			'MODULE_ID' => true,
			'ENTITY_TYPE' => true,
			'ENTITY_ID' => true,
			'CREATE_TIME' => true,
			'CREATED_BY' => true,
		);
	}

	/**
	 * Gets fields which entity can filter in getList().
	 * @return array
	 */
	public function getFieldsForFilter()
	{
		return array();
	}

	/**
	 * Gets fields which Externalizer or Internalizer should modify.
	 * @return array
	 */
	public function getFieldsForMap()
	{
		return array(
			'CREATE_TIME' => array(
				'IN' => function($externalValue){
					return \CRestUtil::unConvertDateTime($externalValue);
				},
				'OUT' => function(DateTime $internalValue = null){
					return \CRestUtil::convertDateTime($internalValue);
				},
			),
			'ENTITY_TYPE' => array(
				'IN' => function($externalValue){
					switch($externalValue)
					{
						case 'blog_comment':
							return BlogPostCommentConnector::className();
						case 'blog_post':
							return BlogPostConnector::className();
						case 'calendar_event':
							return CalendarEventConnector::className();
						case 'forum_message':
							return ForumMessageConnector::className();
						case 'tasks_task':
							return TaskConnector::className();
						case 'sonet_log':
							return SonetLogConnector::className();
						case 'sonet_comment':
							return SonetCommentConnector::className();
					}

					return null;
				},
				'OUT' => function($internalValue){
					switch($internalValue)
					{
						case BlogPostCommentConnector::className():
							return 'blog_comment';
						case BlogPostConnector::className():
							return 'blog_post';
						case CalendarEventConnector::className():
							return 'calendar_event';
						case ForumMessageConnector::className():
							return 'forum_message';
						case TaskConnector::className():
							return 'tasks_task';
						case SonetLogConnector::className():
							return 'sonet_log';
						case SonetCommentConnector::className():
							return 'sonet_comment';
					}

					return null;
				}
			)
		);
	}
}