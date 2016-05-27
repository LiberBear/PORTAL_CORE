<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 * 
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Integration;

use \Bitrix\Main\ArgumentException;

final class SocialNetwork extends \Bitrix\Tasks\Dispatcher\PublicAction
{
    const ALLOWED_AVATAR_SIZE = 60;

	/**
	 * Display user selector control
	 */
	public function getDestinationData($context = 'TASKS')
	{
		$validCtxs = static::getValidDestinationDataContexts();
		if(!in_array($context, $validCtxs))
		{
			$this->errors->add('INVALID_CONTEXT', 'Invalid context passed');
			return;
		}

		return \Bitrix\Tasks\Integration\SocialNetwork::getLogDestination($context, array(
            'AVATAR_WIDTH' => static::ALLOWED_AVATAR_SIZE,
            'AVATAR_HEIGHT' => static::ALLOWED_AVATAR_SIZE
        ));
	}

	public function setDestinationLast($items = array(), $context = 'TASKS')
	{
		if(empty($items))
		{
			return array();
		}

		$validCtxs = static::getValidDestinationDataContexts();
		if(!in_array($context, $validCtxs))
		{
			$this->errors->add('INVALID_CONTEXT', 'Invalid context passed');
			return;
		}

		\Bitrix\Tasks\Integration\SocialNetwork::setLogDestinationLast($items, $context);

		return array();
	}

	private static function getValidDestinationDataContexts()
	{
		return array(
			'TASKS'
		);
	}
}