<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
class EntityConversionConfig
{
	/** @var EntityConversionConfigItem[] */
	protected $items = array();

	public function __construct(array $params = null)
	{
	}

	public function getItem($entityTypeID)
	{
		return isset($this->items[$entityTypeID]) ? $this->items[$entityTypeID] : null;
	}

	protected function addItem(EntityConversionConfigItem $item)
	{
		$this->items[$item->getEntityTypeID()] = $item;
	}

	/**
	* @return EntityConversionConfigItem[]
	*/
	public function getItems()
	{
		return $this->items;
	}

	public function toJavaScript()
	{
		$results = array();
		foreach($this->items as $k => $v)
		{
			$results[strtolower(\CCrmOwnerType::ResolveName($k))] = $v->toJavaScript();
		}
		return $results;
	}

	public function fromJavaScript(array $params)
	{
		$this->items = array();
		foreach($params as $k => $v)
		{
			$entityTypeID = \CCrmOwnerType::ResolveID($k);
			if($entityTypeID !== \CCrmOwnerType::Undefined)
			{
				$item = new EntityConversionConfigItem($entityTypeID);
				$item->fromJavaScript($v);
				$this->addItem($item);
			}
		}
	}

	public function externalize()
	{
		$results = array();
		foreach($this->items as $k => $v)
		{
			$results[\CCrmOwnerType::ResolveName($k)] = $v->externalize();
		}
		return $results;
	}

	public function internalize(array $params)
	{
		$this->items = array();
		foreach($params as $k => $v)
		{
			$entityTypeID = \CCrmOwnerType::ResolveID($k);
			if($entityTypeID !== \CCrmOwnerType::Undefined)
			{
				$item = new EntityConversionConfigItem($entityTypeID);
				$item->internalize($v);
				$this->addItem($item);
			}
		}
	}
}