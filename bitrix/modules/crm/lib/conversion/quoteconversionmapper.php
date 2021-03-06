<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Main;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;

class QuoteConversionMapper extends EntityConversionMapper
{
	/** @var Array */
	protected $srcFields = null;
	/** @var Array */
	protected $srcMultiFields = null;
	public function __construct($srcEntityID)
	{
		parent::__construct(\CCrmOwnerType::Quote, $srcEntityID);
	}
	/**
	 * Get source fields.
	 * @return array
	 */
	public function getSourceFields()
	{
		if($this->srcFields !== null)
		{
			return $this->srcFields;
		}

		$dbResult = \CCrmQuote::GetList(
			array(),
			array('=ID'=> $this->srcEntityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*', 'UF_*')
		);

		$result = $dbResult->Fetch();
		return ($this->srcFields = is_array($result) ? $result : array());
	}
	/**
	 * Create conversion map for destination entity type.
	 * @static
	 * @param int $entityTypeID Destination Entity Type ID.
	 * @return EntityConversionMap
	 */
	public static function createMap($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('dstEntityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if($entityTypeID !== \CCrmOwnerType::Deal
			&& $entityTypeID !== \CCrmOwnerType::Invoice)
		{
			$dstEntityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$dstEntityTypeName}' is not supported in current context");
		}

		$map = new EntityConversionMap(\CCrmOwnerType::Quote, $entityTypeID);
		if($entityTypeID === \CCrmOwnerType::Deal)
		{
			//region Deal Map Static Field Bindings
			$map->createItem('TITLE');
			$map->createItem('COMMENTS');
			$map->createItem('ASSIGNED_BY_ID');
			$map->createItem('OPENED');
			$map->createItem('OPPORTUNITY');
			$map->createItem('CURRENCY_ID');
			$map->createItem('TAX_VALUE');
			$map->createItem('EXCH_RATE');
			$map->createItem('LOCATION_ID');
			$map->createItem('LEAD_ID');
			$map->createItem('COMPANY_ID');
			$map->createItem('CONTACT_ID');
			$map->createItem('PRODUCT_ROWS');
			//endregion

			//region Invoice Map User Field Bindings
			$intersections = UserFieldSynchronizer::getIntersection(\CCrmOwnerType::Quote, \CCrmOwnerType::Deal);
			foreach($intersections as $intersection)
			{
				$map->createItem($intersection['SRC_FIELD_NAME'], $intersection['DST_FIELD_NAME']);
			}
			//endregion
		}
		if($entityTypeID === \CCrmOwnerType::Invoice)
		{
			//region Invoice Map Static Field Bindings
			$map->createItem('TITLE', 'ORDER_TOPIC');
			$map->createItem('COMPANY_ID', 'UF_COMPANY_ID');
			$map->createItem('CONTACT_ID', 'UF_CONTACT_ID');
			$map->createItem('DEAL_ID', 'UF_DEAL_ID');
			$map->createItem('ASSIGNED_BY_ID', 'RESPONSIBLE_ID');
			$map->createItem('COMMENTS');
			$map->createItem('PRODUCT_ROWS');
			//endregion
			//region Invoice Map User Field Bindings
			$intersections = UserFieldSynchronizer::getIntersection(\CCrmOwnerType::Quote, \CCrmOwnerType::Invoice);
			foreach($intersections as $intersection)
			{
				$map->createItem($intersection['SRC_FIELD_NAME'], $intersection['DST_FIELD_NAME']);
			}
			//endregion
		}

		return $map;
	}
	/**
	 * Map entity fields to specified type.
	 * @param EntityConversionMap $map Entity map.
	 * @param array|null $options Mapping options.
	 * @return array
	 */
	public function map(EntityConversionMap $map, array $options = null)
	{
		$srcFields = $this->getSourceFields();
		if(empty($srcFields))
		{
			return array();
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$dstFields = array();
		$dstEntityTypeID = $map->getDestinationEntityTypeID();
		foreach($map->getItems() as $item)
		{
			$dstFieldID = $item->getDestinationField();

			//Skip empty binding
			if($dstFieldID === '-')
			{
				continue;
			}

			$srcFieldID = $item->getSourceField();
			if((!isset($srcFields[$srcFieldID]) || $srcFields[$srcFieldID] === '')
				&& $srcFieldID !== 'PRODUCT_ROWS')
			{
				$altSrcFieldID = '';
				foreach($item->getAlternativeSourceFields() as $fieldID)
				{
					if(isset($srcFields[$fieldID]))
					{
						$altSrcFieldID = $fieldID;
						break;
					}
				}

				if($altSrcFieldID !== '')
				{
					$srcFieldID = $altSrcFieldID;
				}
			}

			if($dstFieldID === '')
			{
				$dstFieldID = $srcFieldID;
			}

			if(strpos($srcFieldID, 'UF_') === 0 && strpos($dstFieldID, 'UF_') === 0)
			{
				self::mapUserField(\CCrmOwnerType::Quote, $srcFieldID, $srcFields, $dstEntityTypeID, $dstFieldID, $dstFields, $options);
			}
			elseif($srcFieldID === 'PRODUCT_ROWS')
			{
				$productRows = \CCrmQuote::LoadProductRows($this->srcEntityID);
				if(count($productRows) > 0)
				{
					if($dstEntityTypeID === \CCrmOwnerType::Invoice)
					{
						$entityProductRows = array();
						$entityDbResult = \CCrmInvoice::GetList(
							array(),
							array('=UF_QUOTE_ID' => $this->srcEntityID, 'CHECK_PERMISSIONS' => 'N'),
							false,
							false,
							array('ID')
						);
						while($entityFields = $entityDbResult->Fetch())
						{
							$entityProductRows[] = \CCrmInvoice::GetProductRows((int)$entityFields['ID']);
						}
						$productRows = \CCrmProductRow::GetDiff(array($productRows), $entityProductRows);

						$currencyID = isset($srcFields['CURRENCY_ID']) ? $srcFields['CURRENCY_ID'] : '';
						if($currencyID === '' || !\CCrmCurrency::IsExists($currencyID))
						{
							$currencyID = \CCrmCurrency::GetBaseCurrencyID();
						}

						$actualRows = \CCrmInvoice::ProductRows2BasketItems($productRows, $currencyID, \CCrmInvoice::GetCurrencyID());
						if (count($actualRows) > 0)
						{
							foreach($actualRows as &$productRow)
							{
								$productRow['ID'] = 0;
							}
							unset($productRow);

							$dstFields[$dstFieldID] = $actualRows;
						}
					}
					elseif($dstEntityTypeID === \CCrmOwnerType::Deal)
					{
						$entityProductRows = array();
						$entityDbResult = \CCrmDeal::GetListEx(
							array(),
							array('=QUOTE_ID' => $this->srcEntityID, 'CHECK_PERMISSIONS' => 'N'),
							false,
							false,
							array('ID')
						);
						while($entityFields = $entityDbResult->Fetch())
						{
							$entityProductRows[] = \CCrmDeal::LoadProductRows((int)$entityFields['ID']);
						}
						$dstFields[$dstFieldID] = \CCrmProductRow::GetDiff(array($productRows), $entityProductRows);
					}
				}
			}
			elseif(isset($srcFields[$srcFieldID]))
			{
				$dstFields[$dstFieldID] = $srcFields[$srcFieldID];
			}
		}

		if(!empty($dstFields))
		{
			if($dstEntityTypeID === \CCrmOwnerType::Invoice)
			{
				//region Status
				if(!isset($dstFields['STATUS_ID']))
				{
					$statusList = \CCrmInvoice::GetStatusList();
					if(is_array($statusList) && !empty($statusList))
					{
						reset($statusList);
						$dstFields['STATUS_ID'] = key($statusList);
					}
				}
				//endregion
				//region Person Type
				$personTypes = \CCrmPaySystem::getPersonTypeIDs();
				$dstFields['PERSON_TYPE_ID'] = 0;
				if (isset($personTypes['CONTACT']) && (!isset($dstFields['UF_COMPANY_ID']) || $dstFields['UF_COMPANY_ID'] <= 0))
				{
					$dstFields['PERSON_TYPE_ID'] = (int)$personTypes['CONTACT'];
				}
				elseif (isset($personTypes['COMPANY']) && isset($dstFields['UF_COMPANY_ID']) && (int)$dstFields['UF_COMPANY_ID'] > 0)
				{
					$dstFields['PERSON_TYPE_ID'] = (int)$personTypes['COMPANY'];
				}
				//endregion
				//region Pay System
				if($dstFields['PERSON_TYPE_ID'] > 0)
				{
					$paySystemList = \CCrmPaySystem::GetPaySystemsListItems($dstFields['PERSON_TYPE_ID']);
					if(is_array($paySystemList) && !empty($paySystemList))
					{
						reset($paySystemList);
						$dstFields['PAY_SYSTEM_ID'] = key($paySystemList);
					}
				}
				//endregion Pay System
				//region Prepare Invoice Properties
				$dstFields['INVOICE_PROPERTIES'] = array();
				$invoiceEntity = new \CCrmInvoice(false);
				$companyID = isset($dstFields['UF_COMPANY_ID']) ? (int)$dstFields['UF_COMPANY_ID'] : 0;
				$contactID = isset($dstFields['UF_CONTACT_ID']) ? (int)$dstFields['UF_CONTACT_ID'] : 0;

				$personTypeID = 0;
				$personTypes = \CCrmPaySystem::getPersonTypeIDs();
				if ($companyID > 0 && isset($personTypes['COMPANY']))
				{
					$personTypeID = $personTypes['COMPANY'];
				}
				elseif(isset($personTypes['CONTACT']))
				{
					$personTypeID = $personTypes['CONTACT'];
				}

				// requisite link
				$requisiteEntityList = array();
				$requisite = new EntityRequisite();
				if ($this->srcEntityID > 0)
					$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Quote, 'ENTITY_ID' => $this->srcEntityID);
				if (isset($dstFields['UF_DEAL_ID']) && $dstFields['UF_DEAL_ID'] > 0)
					$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Deal, 'ENTITY_ID' => $dstFields['UF_DEAL_ID']);
				if (isset($dstFields['UF_COMPANY_ID']) && $dstFields['UF_COMPANY_ID'] > 0)
					$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'ENTITY_ID' => $dstFields['UF_COMPANY_ID']);
				if (isset($dstFields['UF_CONTACT_ID']) && $dstFields['UF_CONTACT_ID'] > 0)
					$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Contact, 'ENTITY_ID' => $dstFields['UF_CONTACT_ID']);
				$requisiteIdLinked = 0;
				$requisiteInfoLinked = $requisite->getDefaultRequisiteInfoLinked($requisiteEntityList);
				if (is_array($requisiteInfoLinked))
				{
					if (isset($requisiteInfoLinked['REQUISITE_ID']))
						$requisiteIdLinked = (int)$requisiteInfoLinked['REQUISITE_ID'];
				}
				unset($requisiteEntityList, $requisite, $requisiteInfoLinked);

				$properties = $invoiceEntity->GetProperties(0, $personTypeID);
				if(is_array($properties))
				{
					\CCrmInvoice::__RewritePayerInfo($companyID, $contactID, $properties);
					if ($dstFields['PERSON_TYPE_ID'] > 0 && $requisiteIdLinked > 0)
						\CCrmInvoice::rewritePropsFromRequisite(
							$dstFields['PERSON_TYPE_ID'],
							$requisiteIdLinked,
							$properties
						);
					foreach($properties as $property)
					{
						$dstFields['INVOICE_PROPERTIES'][$property['FIELDS']['ID']] = $property['VALUE'];
					}
				}
				unset($requisiteIdLinked);
				//endregion

				$dstFields['UF_QUOTE_ID'] = $this->srcEntityID;
			}
			elseif($dstEntityTypeID === \CCrmOwnerType::Deal)
			{
				$dstFields['QUOTE_ID'] = $this->srcEntityID;

				if(isset($dstFields['PRODUCT_ROWS'])
					&& is_array($dstFields['PRODUCT_ROWS'])
					&& !empty($dstFields['PRODUCT_ROWS']))
				{
					$totalInfo = \CCrmProductRow::CalculateTotalInfo('D', 0, false, $dstFields, $dstFields['PRODUCT_ROWS']);
					$dstFields['OPPORTUNITY'] = isset($totalInfo['OPPORTUNITY']) ? $totalInfo['OPPORTUNITY'] : 1.0;
					$dstFields['TAX_VALUE'] = isset($totalInfo['TAX_VALUE']) ? $totalInfo['TAX_VALUE'] : 0.0;
				}
			}

			if(!(isset($options['DISABLE_USER_FIELD_INIT']) && $options['DISABLE_USER_FIELD_INIT'] === true))
			{
				self::initializeUserFields($dstEntityTypeID, $dstFields);
			}
		}

		return $dstFields;
	}
}