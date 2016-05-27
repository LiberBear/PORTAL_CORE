<?php
namespace Bitrix\Crm\Requisite;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityRequisite;

class AddressRequisiteConverter extends EntityRequisiteConverter
{
	/** @var int */
	protected $presetID = 0;
	/** @var bool */
	protected $enablePermissionCheck = false;
	/**
	 * @param int $entityTypeID Entity type ID.
	 * @param int $presetID Preset ID.
	 * @param bool|false $enablePermissionCheck Permission check flag.
	 */
	public function __construct($entityTypeID, $presetID, $enablePermissionCheck = true)
	{
		parent::__construct($entityTypeID);

		$this->presetID = $presetID;
		$this->enablePermissionCheck = $enablePermissionCheck;
	}
	/**
	 * Check converter settings
	 * @return void
	 * @throws RequisiteConvertException
	 */
	public function validate()
	{
	}
	/**
	 * Process entity. Convert invoice requisites to entity requisites
	 * @param int $entityID Entity ID.
	 * @return bool
	 */
	public function processEntity($entityID)
	{
		if($this->enablePermissionCheck)
		{
			if(!(\CCrmAuthorizationHelper::CheckReadPermission($this->entityTypeID, $entityID)
				&& \CCrmAuthorizationHelper::CheckUpdatePermission($this->entityTypeID, $entityID)))
			{
				throw new AddressRequisiteConvertException(
					$this->entityTypeID,
					$this->presetID,
					AddressRequisiteConvertException::ACCESS_DENIED
				);
			}
		}

		$addresses = array();
		foreach(EntityAddress::getListByOwner($this->entityTypeID, $entityID) as $addressTypeID => $address)
		{
			if(EntityAddress::isEmpty($address))
			{
				continue;
			}

			$addresses[$addressTypeID] = array_merge(
				$address,
				array('ANCHOR_TYPE_ID' => $this->entityTypeID, 'ANCHOR_ID' => $entityID)
			);
		}

		if(empty($addresses))
		{
			return false;
		}

		$requisiteEntity = new EntityRequisite();
		$requisiteListResult = $requisiteEntity->getList(
			array(
				'select' => array('ID'),
				'filter' => array(
					'=PRESET_ID' => $this->presetID,
					'=ENTITY_TYPE_ID' => $this->entityTypeID,
					'=ENTITY_ID' => $entityID
				)
			)
		);

		$processedQty = 0;
		$isFound = false;
		while($fields = $requisiteListResult->fetch())
		{
			$requisiteID = (int)$fields['ID'];
			$requisiteAddresses = EntityRequisite::getAddresses($requisiteID);

			$added = false;
			foreach($addresses as $addressTypeID => $address)
			{
				if(isset($requisiteAddresses[$addressTypeID])
					&& !EntityAddress::isEmpty($requisiteAddresses[$addressTypeID]))
				{
					if(EntityAddress::areEquals($address, $requisiteAddresses[$addressTypeID]))
					{
						$isFound = true;
					}

					continue;
				}

				EntityAddress::register(\CCrmOwnerType::Requisite, $requisiteID, $addressTypeID, $address);
				$added = true;
			}

			if($added)
			{
				$processedQty++;
			}
		}

		if(!$isFound && $processedQty === 0)
		{
			$requisiteAddResult = $requisiteEntity->add(
				array(
					'ENTITY_TYPE_ID' => $this->entityTypeID,
					'ENTITY_ID' => $entityID,
					'PRESET_ID' => $this->presetID,
					'NAME' => \CCrmOwnerType::GetCaption($this->entityTypeID, $entityID, false),
					'SORT' => 500,
					'ACTIVE' => 'Y'
				)
			);
			if($requisiteAddResult->isSuccess())
			{
				$requisiteID = (int)$requisiteAddResult->getId();
				foreach($addresses as $addressTypeID => $address)
				{
					EntityAddress::register(\CCrmOwnerType::Requisite, $requisiteID, $addressTypeID, $address);
				}
				$processedQty++;
			}
			else
			{
				throw new AddressRequisiteConvertException(
					$this->entityTypeID,
					$this->presetID,
					AddressRequisiteConvertException::CREATION_FAILED
				);
			}
		}

		return $processedQty > 0;
	}
	/**
	 * Complete convertion process
	 * @return void
	 */
	public function complete()
	{
		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$outmodedOptionName = '';
		$optionName = '';
		$fieldMap = array();
		if($this->entityTypeID === \CCrmOwnerType::Contact)
		{
			$outmodedOptionName = '~CRM_ENABLE_CONTACT_OUTMODED_FIELDS';
			$optionName = 'CRM_CONTACT_EDIT_V12';
			$fieldMap['ADDRESS'] = true;
		}
		elseif($this->entityTypeID === \CCrmOwnerType::Company)
		{
			$outmodedOptionName = '~CRM_ENABLE_COMPANY_OUTMODED_FIELDS';
			$optionName = 'CRM_COMPANY_EDIT_V12';
			$fieldMap['ADDRESS'] = true;
			$fieldMap['ADDRESS_LEGAL'] = true;
		}

		if($outmodedOptionName !== '')
		{
			Main\Config\Option::delete('crm', array('name' => $outmodedOptionName));
		}

		if($optionName === '' || empty($fieldMap))
		{
			return;
		}

		$dbResult = $connection->query(/** @lang MySQL */
			"SELECT ID, VALUE FROM b_user_option where CATEGORY = 'main.interface.form' AND NAME = '{$optionName}'"
		);

		$resetCache = false;
		while($ary = $dbResult->fetch())
		{
			$optionID = (int)$ary['ID'];
			$value = isset($ary['VALUE']) ? $ary['VALUE'] : '';
			if($value === '')
			{
				continue;
			}

			$options = unserialize($value);
			if(!is_array($options) || empty($options) || !isset($options['tabs']) || !is_array($options['tabs']))
			{
				continue;
			}

			$changed = false;
			foreach($options['tabs'] as &$tab)
			{
				if(!isset($tab['id']) || $tab['id'] !== 'tab_1')
				{
					continue;
				}

				if(!isset($tab['fields']) || !is_array($tab['fields']))
				{
					continue;
				}

				$fieldQty = count($tab['fields']);
				for($index = 0; $index < $fieldQty; $index++)
				{
					$field = $tab['fields'][$index];
					if($field['type'] === 'section')
					{
						continue;
					}

					$fieldID = $field['id'];
					if(!isset($fieldMap[$fieldID]))
					{
						continue;
					}

					array_splice($tab['fields'], $index, 1, array());
					$changed = true;
				}

				if($changed)
				{
					$sqlValue = $sqlHelper->forSql(serialize($options));
					$connection->queryExecute(/** @lang MySQL */
						"UPDATE b_user_option SET VALUE = '{$sqlValue}' WHERE ID ='{$optionID}'"
					);
					$resetCache = true;
				}
			}
			unset($tab);
		}

		if($resetCache && isset($GLOBALS['CACHE_MANAGER']) && is_object($GLOBALS['CACHE_MANAGER']))
		{
			/** @global \CCacheManager $CACHE_MANAGER */
			global $CACHE_MANAGER;
			$CACHE_MANAGER->cleanDir('user_option');
		}
	}
}