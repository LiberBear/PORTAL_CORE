<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Main;
abstract class EntityConverter
{
	/** @var EntityConversionConfig */
	protected $config = null;
	/** @var int */
	protected $entityID = 0;
	/** @var int */
	protected $currentPhase = 0;
	/** @var array */
	protected $contextData = array();
	/** @var array */
	protected $resultData = array();
	/** @var \CCrmPerms|null  */
	protected $userPermissions = null;

	/**
	 * @param EntityConversionConfig $config
	 */
	public function __construct(EntityConversionConfig $config)
	{
		$this->config = $config;
	}
	/**
	 * Initialize converter.
	 * @return void
	 */
	public function initialize()
	{
	}
	/**
	 * Get converter entity type ID.
	 * @return int
	 */
	abstract public function getEntityTypeID();
	/**
	 * Get converter entity ID.
	 * @return int
	 */
	public function getEntityID()
	{
		return $this->entityID;
	}
	/**
	 * Set converter entity ID.
	 * @param int $entityID Entity ID.
	 * @return void
	 */
	public function setEntityID($entityID)
	{
		$this->entityID = $entityID;
	}
	/**
	 * Get current converter phase.
	 * @return int
	 */
	public function getCurrentPhase()
	{
		return $this->currentPhase;
	}
	/**
	 * Get converter context data.
	 * @return array
	 */
	public function getContextData()
	{
		return $this->contextData;
	}

	/**
	 * Set converter context data.
	 * @param array $contextData Converter context data.
	 * @return void
	 */
	public function setContextData(array $contextData)
	{
		$this->contextData = $contextData;
	}
	/**
	 * Get converter result data.
	 * @return array
	 */
	public function getResultData()
	{
		return $this->resultData;
	}
	/**
	 * Get current user ID.
	 * @return int
	 */
	public function getUserID()
	{
		return \CCrmSecurityHelper::GetCurrentUserID();
	}
	/**
	 * Get current user permissions
	 * @return \CCrmPerms
	 */
	protected function getUserPermissions()
	{
		if($this->userPermissions === null)
		{
			$this->userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		}

		return $this->userPermissions;
	}
	/**
	 * Try to execute current conversion phase.
	 * @return bool
	 */
	abstract public function executePhase();
	/**
	 * Map entity fields to specified type.
	 * @param int $entityTypeID Entity type ID.
	 * @param array|null $options Mapping options.
	 * @return array
	 */
	abstract public function mapEntityFields($entityTypeID, array $options = null);
	/**
	 * Externalize converter settings
	 * @return array
	 */
	public function externalize()
	{
		return array(
			'config' => $this->config->externalize(),
			'entityId' => $this->entityID,
			'currentPhase' => $this->currentPhase,
			'resultData' => $this->resultData
		);
	}
	/**
	 * Internalize converter settings.
	 * @param array $params Income parameters.
	 * @return void
	 */
	public function internalize(array $params)
	{
		if(isset($params['config']) && is_array($params['config']))
		{
			$this->config->internalize($params['config']);
		}

		if(isset($params['entityId']))
		{
			$this->entityID = (int)$params['entityId'];
		}

		if(isset($params['currentPhase']))
		{
			$this->currentPhase = (int)$params['currentPhase'];
		}

		if(isset($params['resultData']) && is_array($params['resultData']))
		{
			$this->resultData = $params['resultData'];
		}
	}
}