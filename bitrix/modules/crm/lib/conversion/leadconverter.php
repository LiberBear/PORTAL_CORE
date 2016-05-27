<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Crm\Merger\CompanyMerger;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Crm\Settings\ConversionSettings;
use Bitrix\Crm\Merger\ContactMerger;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Main;
class LeadConverter extends EntityConverter
{
	/** @var array */
	private static $maps = array();
	/** @var LeadConversionMapper|null  */
	private $mapper = null;

	public function __construct(LeadConversionConfig $config = null)
	{
		if($config === null)
		{
			$config = new LeadConversionConfig();
		}
		parent::__construct($config);
	}
	/**
	 * Initialize converter.
	 * @return void
	 * @throws EntityConversionException If entity is not exist.
	 * @throws EntityConversionException If read or update permissions are denied.
	 */
	public function initialize()
	{
		if($this->currentPhase === LeadConversionPhase::INTERMEDIATE)
		{
			$this->currentPhase = LeadConversionPhase::COMPANY_CREATION;
		}

		if(!\CCrmLead::Exists($this->entityID))
		{
			throw new EntityConversionException(
				\CCrmOwnerType::Lead,
				\CCrmOwnerType::Undefined,
				EntityConversionException::TARG_SRC,
				EntityConversionException::NOT_FOUND
			);
		}

		/** @var \CCrmPerms $permissions */
		$permissions = $this->getUserPermissions();
		if(!\CCrmAuthorizationHelper::CheckReadPermission(\CCrmOwnerType::LeadName, $this->entityID, $permissions))
		{
			throw new EntityConversionException(
				\CCrmOwnerType::Lead,
				\CCrmOwnerType::Undefined,
				EntityConversionException::TARG_SRC,
				EntityConversionException::READ_DENIED
			);
		}

		if(!\CCrmAuthorizationHelper::CheckUpdatePermission(\CCrmOwnerType::LeadName, $this->entityID, $permissions))
		{
			throw new EntityConversionException(
				\CCrmOwnerType::Lead,
				\CCrmOwnerType::Undefined,
				EntityConversionException::TARG_SRC,
				EntityConversionException::UPDATE_DENIED
			);
		}
	}
	/**
	 * Get converter entity type ID.
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Lead;
	}
	/**
	 * Get conversion mapper
	 * @return LeadConversionMapper|null
	 */
	public function getMapper()
	{
		if($this->mapper === null)
		{
			$this->mapper = new LeadConversionMapper($this->entityID);
		}

		return $this->mapper;
	}
	/**
	 * Get conversion map for for specified entity type.
	 * Try to load saved map. If map is not found then default map will be created.
	 * @param int $entityTypeID Entity Type ID.
	 * @return EntityConversionMap
	 */
	public static function getMap($entityTypeID)
	{
		return self::prepareMap($entityTypeID);
	}
	/**
	 * Map entity fields to specified type.
	 * @param int $entityTypeID Entity type ID.
	 * @param array|null $options Mapping options.
	 * @return array
	 */
	public function mapEntityFields($entityTypeID, array $options = null)
	{
		$fields = $this->getMapper()->map($this->getMap($entityTypeID), $options);
		if($entityTypeID === \CCrmOwnerType::Contact)
		{
			if(isset($this->resultData[\CCrmOwnerType::CompanyName]))
			{
				$fields['COMPANY_ID'] = $this->resultData[\CCrmOwnerType::CompanyName];
			}
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			if(isset($this->resultData[\CCrmOwnerType::ContactName]))
			{
				$fields['CONTACT_ID'] = $this->resultData[\CCrmOwnerType::ContactName];
			}

			if(isset($this->resultData[\CCrmOwnerType::CompanyName]))
			{
				$fields['COMPANY_ID'] = $this->resultData[\CCrmOwnerType::CompanyName];
			}
		}
		return $fields;
	}
	/**
	 * Try to move converter to next phase
	 * @return bool
	 */
	public function moveToNextPhase()
	{
		switch($this->currentPhase)
		{
			case LeadConversionPhase::INTERMEDIATE:
				$this->currentPhase = LeadConversionPhase::COMPANY_CREATION;
				return true;
				break;
			case LeadConversionPhase::COMPANY_CREATION:
				$this->currentPhase = LeadConversionPhase::CONTACT_CREATION;
				return true;
				break;
			case LeadConversionPhase::CONTACT_CREATION:
				$this->currentPhase = LeadConversionPhase::DEAL_CREATION;
				return true;
				break;
			case LeadConversionPhase::DEAL_CREATION:
				$this->currentPhase = LeadConversionPhase::FINALIZATION;
				return true;
				break;
			//case LeadConversionPhase::FINALIZATION:
			default:
				return false;
		}
	}
	/**
	 * Try to execute current conversion phase.
	 * @return bool
	 * @throws EntityConversionException If mapper return empty fields.
	 * @throws EntityConversionException If target entity is not found.
	 * @throws EntityConversionException If target entity fields are invalid.
	 * @throws EntityConversionException If target entity has bizproc workflows.
	 * @throws EntityConversionException If target entity creation is failed.
	 * @throws EntityConversionException If target entity update permission is denied.
	 */
	public function executePhase()
	{
		if($this->currentPhase === LeadConversionPhase::COMPANY_CREATION
			|| $this->currentPhase === LeadConversionPhase::CONTACT_CREATION
			|| $this->currentPhase === LeadConversionPhase::DEAL_CREATION)
		{
			if($this->currentPhase === LeadConversionPhase::COMPANY_CREATION)
			{
				$entityTypeID = \CCrmOwnerType::Company;
			}
			elseif($this->currentPhase === LeadConversionPhase::CONTACT_CREATION)
			{
				$entityTypeID = \CCrmOwnerType::Contact;
			}
			else//if($this->currentPhase === LeadConversionPhase::DEAL_CREATION)
			{
				$entityTypeID = \CCrmOwnerType::Deal;
			}

			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			$config = $this->config->getItem($entityTypeID);
			if(!$config->isActive())
			{
				return false;
			}

			//Only one company and one contact may be created
			if($entityTypeID === \CCrmOwnerType::Company)
			{
				$dbResult = \CCrmCompany::GetListEx(
					array(),
					array('=LEAD_ID' => $this->entityID, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID')
				);
				$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
				if(is_array($entityFields))
				{
					//Company already created
					$this->resultData[\CCrmOwnerType::CompanyName] = (int)$entityFields['ID'];
					return true;
				}
			}
			elseif($entityTypeID === \CCrmOwnerType::Contact)
			{
				$dbResult = \CCrmContact::GetListEx(
					array(),
					array('=LEAD_ID' => $this->entityID, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID')
				);
				$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
				if(is_array($entityFields))
				{
					//Contact already created
					$this->resultData[\CCrmOwnerType::ContactName] = (int)$entityFields['ID'];
					return true;
				}
			}

			/** @var LeadConversionMapper $mapper */
			$mapper = $this->getMapper();
			/** @var EntityConversionMap $map */
			$map = self::prepareMap($entityTypeID);
			/** @var \CCrmPerms $permissions */
			$permissions = $this->getUserPermissions();

			$entityID = isset($this->contextData[$entityTypeName]) ? $this->contextData[$entityTypeName] : 0;
			if($entityID > 0)
			{
				if(!\CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, $entityID, $permissions))
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Lead,
						$entityTypeID,
						EntityConversionException::TARG_DST,
						EntityConversionException::UPDATE_DENIED
					);
				}

				if($entityTypeID === \CCrmOwnerType::Company)
				{
					$entity = new \CCrmCompany(false);

					if(isset($this->contextData['ENABLE_MERGE'])
						&& $this->contextData['ENABLE_MERGE'] === true)
					{
						$dbResult = \CCrmCompany::GetListEx(
							array(),
							array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
							false,
							false,
							array('*', 'UF_*')
						);

						$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
						if(!is_array($fields))
						{
							throw new EntityConversionException(
								\CCrmOwnerType::Lead,
								\CCrmOwnerType::Company,
								EntityConversionException::TARG_DST,
								EntityConversionException::NOT_FOUND
							);
						}

						$mappedFields = $mapper->map($map, array('DISABLE_USER_FIELD_INIT' => true));
						if(!empty($mappedFields))
						{
							$merger = new CompanyMerger($this->getUserID(), true);
							$merger->mergeFields($mappedFields, $fields);
							$entity->Update($entityID, $fields);
						}
					}
					elseif(!\CCrmCompany::Exists($entityID))
					{
						throw new EntityConversionException(
							\CCrmOwnerType::Lead,
							\CCrmOwnerType::Company,
							EntityConversionException::TARG_DST,
							EntityConversionException::NOT_FOUND
						);
					}
				}
				elseif($entityTypeID === \CCrmOwnerType::Contact)
				{
					$entity = new \CCrmContact(false);

					if(isset($this->contextData['ENABLE_MERGE'])
						&& $this->contextData['ENABLE_MERGE'] === true)
					{
						$dbResult = \CCrmContact::GetListEx(
							array(),
							array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
							false,
							false,
							array('*', 'UF_*')
						);

						$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
						if(!is_array($fields))
						{
							throw new EntityConversionException(
								\CCrmOwnerType::Lead,
								\CCrmOwnerType::Contact,
								EntityConversionException::TARG_DST,
								EntityConversionException::NOT_FOUND
							);
						}

						$mappedFields = $mapper->map($map, array('DISABLE_USER_FIELD_INIT' => true));
						if(!empty($mappedFields))
						{
							$merger = new ContactMerger($this->getUserID(), true);
							$merger->mergeFields($mappedFields, $fields);
							$entity->Update($entityID, $fields);
						}
					}
					elseif(!\CCrmContact::Exists($entityID))
					{
						throw new EntityConversionException(
							\CCrmOwnerType::Lead,
							\CCrmOwnerType::Contact,
							EntityConversionException::TARG_DST,
							EntityConversionException::NOT_FOUND
						);
					}
				}
				else//if($entityTypeID === \CCrmOwnerType::Deal)
				{
					if(!\CCrmDeal::Exists($entityID))
					{
						throw new EntityConversionException(
							\CCrmOwnerType::Lead,
							\CCrmOwnerType::Deal,
							EntityConversionException::TARG_DST,
							EntityConversionException::NOT_FOUND
						);
					}

					$entity = new \CCrmDeal(false);
				}

				$fields = array('LEAD_ID' => $this->entityID);
				$entity->Update($entityID, $fields, false, false);
				$this->resultData[$entityTypeName] = $entityID;

				return true;
			}

			if(!\CCrmAuthorizationHelper::CheckCreatePermission($entityTypeName , $permissions))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Lead,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::CREATE_DENIED
				);
			}

			if(UserFieldSynchronizer::needForSynchronization(\CCrmOwnerType::Lead, $entityTypeID))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Lead,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::NOT_SYNCHRONIZED
				);
			}

			if(!ConversionSettings::getCurrent()->isAutocreationEnabled())
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Lead,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::AUTOCREATION_DISABLED
				);
			}

			if(\CCrmBizProcHelper::HasAutoWorkflows($entityTypeID, \CCrmBizProcEventType::Create))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Lead,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::HAS_WORKFLOWS
				);
			}

			$fields = $mapper->map($map);
			if(empty($fields))
			{
				throw new EntityConversionException(
					\CCrmOwnerType::Lead,
					$entityTypeID,
					EntityConversionException::TARG_DST,
					EntityConversionException::EMPTY_FIELDS
				);
			}

			if($entityTypeID === \CCrmOwnerType::Company)
			{
				$entity = new \CCrmCompany(false);
				$entityID = $entity->Add($fields);
				if($entityID <= 0)
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Lead,
						\CCrmOwnerType::Company,
						EntityConversionException::TARG_DST,
						EntityConversionException::CREATE_FAILED,
						$entity->LAST_ERROR
					);
				}

				//region BizProcess
				$arErrors = array();
				\CCrmBizProcHelper::AutoStartWorkflows(
					\CCrmOwnerType::Company,
					$entityID,
					\CCrmBizProcEventType::Create,
					$arErrors
				);
				//endregion

				$this->resultData[\CCrmOwnerType::CompanyName] = $entityID;
			}
			elseif($entityTypeID === \CCrmOwnerType::Contact)
			{
				if(isset($this->resultData[\CCrmOwnerType::CompanyName]))
				{
					$fields['COMPANY_ID'] = $this->resultData[\CCrmOwnerType::CompanyName];
				}

				$entity = new \CCrmContact(false);
				if(!$entity->CheckFields($fields))
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Lead,
						$entityTypeID,
						EntityConversionException::TARG_DST,
						EntityConversionException::INVALID_FIELDS,
						$entity->LAST_ERROR
					);
				}

				$entityID = $entity->Add($fields);
				if($entityID <= 0)
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Lead,
						\CCrmOwnerType::Contact,
						EntityConversionException::TARG_DST,
						EntityConversionException::CREATE_FAILED,
						$entity->LAST_ERROR
					);
				}

				//region BizProcess
				$arErrors = array();
				\CCrmBizProcHelper::AutoStartWorkflows(
					\CCrmOwnerType::Contact,
					$entityID,
					\CCrmBizProcEventType::Create,
					$arErrors
				);
				//endregion

				$this->resultData[\CCrmOwnerType::ContactName] = $entityID;
			}
			else//if($entityTypeID === \CCrmOwnerType::Deal)
			{
				if(isset($this->resultData[\CCrmOwnerType::ContactName]))
				{
					$fields['CONTACT_ID'] = $this->resultData[\CCrmOwnerType::ContactName];
				}

				if(isset($this->resultData[\CCrmOwnerType::CompanyName]))
				{
					$fields['COMPANY_ID'] = $this->resultData[\CCrmOwnerType::CompanyName];
				}

				$entity = new \CCrmDeal(false);
				$entityID = $entity->Add($fields);
				if($entityID <= 0)
				{
					throw new EntityConversionException(
						\CCrmOwnerType::Lead,
						\CCrmOwnerType::Deal,
						EntityConversionException::TARG_DST,
						EntityConversionException::CREATE_FAILED,
						$entity->LAST_ERROR
					);
				}

				if(isset($fields['PRODUCT_ROWS'])
					&& is_array($fields['PRODUCT_ROWS'])
					&& !empty($fields['PRODUCT_ROWS']))
				{
					\CCrmDeal::SaveProductRows($entityID, $fields['PRODUCT_ROWS'], false, false, false);
				}

				// requisite link
				$requisiteEntityList = array();
				$requisite = new EntityRequisite();
				if (isset($fields['COMPANY_ID']) && $fields['COMPANY_ID'] > 0)
					$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'ENTITY_ID' => $fields['COMPANY_ID']);
				if (isset($fields['CONTACT_ID']) && $fields['CONTACT_ID'] > 0)
					$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Contact, 'ENTITY_ID' => $fields['CONTACT_ID']);
				$requisiteIdLinked = 0;
				$bankDetailIdLinked = 0;
				$requisiteInfoLinked = $requisite->getDefaultRequisiteInfoLinked($requisiteEntityList);
				if (is_array($requisiteInfoLinked))
				{
					if (isset($requisiteInfoLinked['REQUISITE_ID']))
						$requisiteIdLinked = (int)$requisiteInfoLinked['REQUISITE_ID'];
					if (isset($requisiteInfoLinked['BANK_DETAIL_ID']))
						$bankDetailIdLinked = (int)$requisiteInfoLinked['BANK_DETAIL_ID'];
				}
				unset($requisiteEntityList, $requisite, $requisiteInfoLinked);
				if ($requisiteIdLinked > 0)
				{
					EntityLink::register(
						\CCrmOwnerType::Deal, $entityID, $requisiteIdLinked, $bankDetailIdLinked
					);
				}
				unset($requisiteIdLinked);

				//region BizProcess
				$arErrors = array();
				\CCrmBizProcHelper::AutoStartWorkflows(
					\CCrmOwnerType::Deal,
					$entityID,
					\CCrmBizProcEventType::Create,
					$arErrors
				);
				//endregion

				$this->resultData[\CCrmOwnerType::DealName] = $entityID;
			}

			return true;
		}
		elseif($this->currentPhase === LeadConversionPhase::FINALIZATION)
		{
			$result = \CCrmLead::GetListEx(
				array(),
				array('=ID' => $this->entityID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('STATUS_ID')
			);

			$presentFields = is_object($result) ? $result->Fetch() : null;
			if(is_array($presentFields))
			{
				$fields = array();

				$statusID = isset($presentFields['STATUS_ID']) ? $presentFields['STATUS_ID'] : '';
				if($statusID !== 'CONVERTED')
				{
					$fields['STATUS_ID'] = 'CONVERTED';
				}
				if(isset($this->resultData[\CCrmOwnerType::CompanyName]))
				{
					$fields['COMPANY_ID'] = $this->resultData[\CCrmOwnerType::CompanyName];
				}
				if(isset($this->resultData[\CCrmOwnerType::ContactName]))
				{
					$fields['CONTACT_ID'] = $this->resultData[\CCrmOwnerType::ContactName];
				}

				if(!empty($fields))
				{
					$entity = new \CCrmLead(false);
					if($entity->Update($this->entityID, $fields, true, true, array('REGISTER_SONET_EVENT' => true)))
					{
						//region BizProcess
						$arErrors = array();
						\CCrmBizProcHelper::AutoStartWorkflows(
							\CCrmOwnerType::Lead,
							$this->entityID,
							\CCrmBizProcEventType::Edit,
							$arErrors
						);
						//endregion
					}
				}
			}

			return true;
		}

		return false;
	}
	/**
	 * Preparation of conversion map for specified entity type.
	 * Try to load saved map. If map is not found then default map will be created.
	 * @param int $entityTypeID Entity Type ID.
	 * @return EntityConversionMap
	*/
	protected static function prepareMap($entityTypeID)
	{
		if(isset(self::$maps[$entityTypeID]))
		{
			return self::$maps[$entityTypeID];
		}

		$map = EntityConversionMap::load(\CCrmOwnerType::Lead, $entityTypeID);
		if($map === null)
		{
			$map = LeadConversionMapper::createMap($entityTypeID);
			$map->save();
		}
		elseif($map->isOutOfDate())
		{
			LeadConversionMapper::updateMap($map);
			$map->save();
		}

		return (self::$maps[$entityTypeID] = $map);
	}
}