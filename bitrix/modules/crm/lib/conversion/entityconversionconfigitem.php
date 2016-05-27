<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
class EntityConversionConfigItem
{
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	protected $active = false;
	protected $enableSynchronization = false;

	public function __construct($entityTypeID = 0)
	{
		$this->setEntityTypeID($entityTypeID);
	}

	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}

	public function setEntityTypeID($entityTypeID)
	{
		$this->entityTypeID = $entityTypeID;
	}

	public function isActive()
	{
		return $this->active;
	}

	public function setActive($active)
	{
		$this->active = $active;
	}

	public function isSynchronizationEnabled()
	{
		return $this->enableSynchronization;
	}

	public function enableSynchronization($enable)
	{
		$this->enableSynchronization = $enable;
	}

	public function toJavaScript()
	{
		return array(
			'active' => $this->active ? 'Y' : 'N',
			'enableSync' => $this->enableSynchronization ? 'Y' : 'N'
		);
	}

	public function fromJavaScript(array $params)
	{
		$this->active = isset($params['active']) && $params['active'] === 'Y';
		$this->enableSynchronization = isset($params['enableSync'])  && $params['enableSync'] === 'Y';
	}

	public function externalize()
	{
		return array(
			'entityTypeId' => $this->entityTypeID,
			'active' => $this->active,
			'enableSync' => $this->enableSynchronization
		);
	}

	public function internalize(array $params)
	{
		if(isset($params['entityTypeId']))
		{
			$this->entityTypeID = (int)$params['entityTypeId'];
		}
		$this->active = isset($params['active']) ? (bool)$params['active'] : false;
		$this->enableSynchronization = isset($params['enableSync']) ? (bool)$params['enableSync'] : false;
	}
}