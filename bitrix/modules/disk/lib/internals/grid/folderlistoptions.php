<?php

namespace Bitrix\Disk\Internals\Grid;

use \Bitrix\Disk;
use Bitrix\Disk\Driver;
use Bitrix\Main\Localization\Loc;

/**
 * Class FolderListOptions
 * @package Bitrix\Disk\Internals\Grid
 * @internal
 */
class FolderListOptions
{
	const COUNT_ON_PAGE = 50;

	const SORT_MODE_MIX      = 'mix';
	const SORT_MODE_ORDINARY = 'ord';

	const VIEW_MODE_GRID = 'grid';
	const VIEW_MODE_TILE = 'tile';

	/** @var \Bitrix\Disk\Storage */
	protected $storage;
	/** @var \CGridOptions  */
	private $gridOptions;

	/**
	 * FolderListOptions constructor.
	 * @param Disk\Storage $storage
	 */
	public function __construct(Disk\Storage $storage)
	{
		$this->storage = $storage;
	}

	/**
	 * Returns grid id.
	 * @return string
	 */
	public function getGridId()
	{
		return 'folder_list_' . $this->storage->getId();
	}

	/**
	 * Returns columns which may use to sorting.
	 * @return array
	 */
	public function getPossibleColumnForSorting()
	{
		return array(
			'UPDATE_TIME' => array(
				'ALIAS' => 'UPDATE_TIME',
				'LABEL' => Loc::getMessage('DISK_FOLDER_LIST_SORT_BY_UPDATE_TIME')
			),
			'NAME' => array(
				'ALIAS' => 'NAME',
				'LABEL' => Loc::getMessage('DISK_FOLDER_LIST_SORT_BY_NAME')
			),
			'FORMATTED_SIZE' => array(
				'ALIAS' => 'SIZE',
				'LABEL' => Loc::getMessage('DISK_FOLDER_LIST_SORT_BY_FORMATTED_SIZE')
			),
		);
	}

	/**
	 * Returns columns for sorting for current user.
	 * @return array
	 */
	public function getSortingColumns()
	{
		$gridSort = $this->getGridOptions()->getSorting(array(
			'sort' => array('UPDATE_TIME' => 'DESC'),
			'vars' => array('by' => 'by', 'order' => 'order')
		));
		$sorting = $gridSort['sort'];
		$possibleColumnForSorting = $this->getPossibleColumnForSorting();

		$byColumn = key($sorting);
		if(!isset($possibleColumnForSorting[$byColumn]) || (strtolower($sorting[$byColumn]) !== 'desc' && strtolower($sorting[$byColumn]) !== 'asc'))
		{
			$sorting = array();
		}
		$order = $sorting;
		$byColumn = key($order);
		$sortingColumns = array();
		if(!$this->isMixSortMode())
		{
			$sortingColumns['TYPE'] = array(SORT_NUMERIC, SORT_ASC);
		}
		$sortingColumns[$possibleColumnForSorting[$byColumn]['ALIAS']] = strtolower($order[$byColumn]) === 'asc' ? SORT_ASC : SORT_DESC;
		if($byColumn !== 'NAME')
		{
			$sortingColumns[$possibleColumnForSorting['NAME']['ALIAS']] = SORT_ASC;
		}

		return $sortingColumns;
	}

	/**
	 * Returns data to order in select orm-parameters.
	 * @return array
	 */
	public function getOrderForOrm()
	{
		$order = array();
		foreach($this->getSortingColumns() as $columnName => $columnData)
		{
			if(is_array($columnData))
			{
				$order[$columnName] = in_array(SORT_DESC, $columnData, true) ? 'DESC' : 'ASC';
			}
			else
			{
				$order[$columnName] = SORT_DESC === $columnData ? 'DESC' : 'ASC';
			}
		}
		unset($columnName, $columnData);

		return $order;
	}

	/**
	 * Returns grid sorting options (@see \CGridOptions).
	 * @return array
	 */
	public function getGridOptionsSorting()
	{
		$gridSort = $this->getGridOptions()->getSorting(array(
			'sort' => array('UPDATE_TIME' => 'DESC'),
			'vars' => array('by' => 'by', 'order' => 'order')
		));

		return array($gridSort['sort'], $gridSort['vars']);
	}

	/**
	 * Returns grid mode for view (grid or tile).
	 * @return string
	 */
	public function getViewMode()
	{
		$options = $this->getGridSpecificOptions();

		return $options['viewMode'];
	}

	/**
	 * Returns grid mode for view (grid or tile).
	 * @return string
	 */
	public function getSortMode()
	{
		$options = $this->getGridSpecificOptions();

		return $options['sortMode'];
	}

	/**
	 * Tells if sort mode is mix.
	 * @return bool
	 */
	private function isMixSortMode()
	{
		return $this->getSortMode() === self::SORT_MODE_MIX;
	}

	private function getGridSpecificOptions()
	{
		return \CUserOptions::getOption(Driver::INTERNAL_MODULE_ID, 'grid', array(
			'sortMode' => self::SORT_MODE_ORDINARY,
			'viewMode' => self::VIEW_MODE_GRID,
		));
	}

	/**
	 * Stores view mode (grid or tile) for folder list.
	 * @param string $mode
	 * @return void
	 */
	public function storeViewMode($mode)
	{
		$mode = strtolower($mode);
		if($mode !== self::VIEW_MODE_GRID && $mode !== self::VIEW_MODE_TILE)
		{
			$mode = self::VIEW_MODE_GRID;
		}

		\CUserOptions::setOption(Driver::INTERNAL_MODULE_ID, 'grid', array(
			'sortMode' => $this->getSortMode(),
			'viewMode' => $mode,
		));
	}

	/**
	 * Stores sort mode for folder list.
	 * @param string $mode
	 * @return void
	 */
	public function storeSortMode($mode)
	{
		$mode = strtolower($mode);
		if($mode !== self::SORT_MODE_ORDINARY && $mode !== self::SORT_MODE_MIX)
		{
			$mode = self::SORT_MODE_ORDINARY;
		}

		\CUserOptions::setOption(Driver::INTERNAL_MODULE_ID, 'grid', array(
			'sortMode' => $mode,
			'viewMode' => $this->getViewMode(),
		));
	}

	/**
	 * Returns page size.
	 * @return int
	 */
	public function getPageSize()
	{
		$navParams = $this->getGridOptions()->getNavParams(array('nPageSize' => self::COUNT_ON_PAGE));
		return (int)$navParams['nPageSize'];
	}

	private function getGridOptions()
	{
		if($this->gridOptions === null)
		{
			$this->gridOptions = new \CGridOptions($this->getGridId());
		}
		return $this->gridOptions;
	}
}