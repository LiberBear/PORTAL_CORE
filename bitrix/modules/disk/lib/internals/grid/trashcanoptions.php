<?php

namespace Bitrix\Disk\Internals\Grid;

use \Bitrix\Disk;

/**
 * Class TrashCanOptions
 * @package Bitrix\Disk\Internals\Grid
 * @internal
 */
final class TrashCanOptions extends FolderListOptions
{
	/**
	 * Returns grid id.
	 * @return string
	 */
	public function getGridId()
	{
		return 'trashcan_' . $this->storage->getId();
	}
}