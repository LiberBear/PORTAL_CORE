<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
use Bitrix\Crm\Merger\EntityMerger;

class LeadConversionWizard extends EntityConversionWizard
{
	/**
	 * @param int $entityID Entity ID.
	 * @param LeadConversionConfig|null $config Configuration parameters.
	 */
	public function __construct($entityID = 0, LeadConversionConfig $config = null)
	{
		$converter = new LeadConverter($config);
		$converter->setEntityID($entityID);
		parent::__construct($converter);

	}
	/**
	 * Execute wizard.
	 * @param array|null $contextData Conversion context data.
	 * @return bool
	 */
	public function execute(array $contextData = null)
	{
		/** @var LeadConverter $converter */
		$converter = $this->converter;

		if(is_array($contextData) && !empty($contextData))
		{
			$converter->setContextData(array_merge($converter->getContextData(), $contextData));
		}

		$result = false;
		try
		{
			$converter->initialize();
			do
			{
				$converter->executePhase();
			}
			while($converter->moveToNextPhase());

			$resultData = $converter->getResultData();

			if(isset($resultData[\CCrmOwnerType::DealName]))
			{
				$this->redirectUrl = \CCrmOwnerType::GetShowUrl(\CCrmOwnerType::Deal, $resultData[\CCrmOwnerType::DealName], false);
			}
			elseif(isset($resultData[\CCrmOwnerType::CompanyName]))
			{
				$this->redirectUrl = \CCrmOwnerType::GetShowUrl(\CCrmOwnerType::Company, $resultData[\CCrmOwnerType::CompanyName], false);
			}
			elseif(isset($resultData[\CCrmOwnerType::ContactName]))
			{
				$this->redirectUrl = \CCrmOwnerType::GetShowUrl(\CCrmOwnerType::Contact, $resultData[\CCrmOwnerType::ContactName], false);
			}

			$result = true;
		}
		catch(EntityConversionException $e)
		{
			$this->exception = $e;
			if($e->getTargetType() === EntityConversionException::TARG_DST)
			{
				$this->redirectUrl = \CCrmUrlUtil::AddUrlParams(
					\CCrmOwnerType::GetEditUrl($e->getDestinationEntityTypeID(), 0, false),
					array('lead_id' => $converter->getEntityID())
				);
			}
		}
		catch(\Exception $e)
		{
			$this->errorText = $e->getMessage();
		}

		$this->save();
		return $result;
	}
	/**
	 * Prepare entity fields for edit.
	 * @param int $entityTypeID Entity type ID.
	 * @param array &$fields Entity fields.
	 * @param bool|true $encode Encode fields flag.
	 * @return void
	 */
	public function prepareDataForEdit($entityTypeID, array &$fields, $encode = true)
	{
		/** @var LeadConverter $converter */
		$converter = $this->converter;

		$userFields = LeadConversionMapper::getUserFields($entityTypeID);
		$mappedFields = $converter->mapEntityFields($entityTypeID, array('ENABLE_FILES' => false));

		foreach($mappedFields as $k => $v)
		{
			if($k === 'FM' || $k === 'PRODUCT_ROWS')
			{
				$fields[$k] = $v;
				continue;
			}
			elseif(strpos($k, 'UF_CRM') === 0)
			{
				$userField = isset($userFields[$k]) ? $userFields[$k] : null;
				if(is_array($userField))
				{
					// hack for UF
					if($userField['USER_TYPE_ID'] === 'file')
					{
						$GLOBALS["{$k}_old_id"] = $v;
					}
					else
					{
						$_REQUEST[$k] = $v;
					}
				}
			}
			elseif($encode)
			{
				$fields["~{$k}"] = $v;
				if(!is_array($v))
				{
					$fields[$k] = htmlspecialcharsbx($v);
				}
			}
		}
	}
	/**
	 * Prepare entity fields for save.
	 * @param int $entityTypeID Entity type ID.
	 * @param array &$fields Entity fields.
	 * @return void
	 */
	public function prepareDataForSave($entityTypeID, array &$fields)
	{
		$dstUserFields = LeadConversionMapper::getUserFields($entityTypeID);
		foreach($dstUserFields as $dstName => $dstField)
		{
			if($dstField['USER_TYPE_ID'] === 'file')
			{
				$this->prepareFileUserFieldForSave($dstName, $dstField, $fields);
			}
		}

		/** @var LeadConverter $converter */
		$converter = $this->converter;
		$mappedFields = $converter->mapEntityFields($entityTypeID, array('DISABLE_USER_FIELD_INIT' => true));
		if(!empty($mappedFields))
		{
			$merger = EntityMerger::create($entityTypeID, $converter->getUserID(), true);
			$merger->mergeFields($mappedFields, $fields);
		}
	}
	/**
	 * Save wizard settings in session.
	 * @return void
	 */
	public function save()
	{
		if(!isset($_SESSION['LEAD_CONVERTER']))
		{
			$_SESSION['LEAD_CONVERTER'] = array();
		}

		$_SESSION['LEAD_CONVERTER'][$this->getEntityID()] = $this->externalize();
	}
	/**
	 * Load wizard related to entity from session.
	 * @param int $entityID Entity ID.
	 * @return LeadConversionWizard|null
	 */
	public static function load($entityID)
	{
		if(!(isset($_SESSION['LEAD_CONVERTER']) && $_SESSION['LEAD_CONVERTER'][$entityID]))
		{
			return null;
		}

		$item = new LeadConversionWizard($entityID);
		$item->internalize($_SESSION['LEAD_CONVERTER'][$entityID]);
		return $item;
	}
	/**
	 * Remove wizard related to entity from session.
	 * @param int $entityID Entity ID.
	 * @return void
	 */
	public static function remove($entityID)
	{
		if(isset($_SESSION['LEAD_CONVERTER']) && $_SESSION['LEAD_CONVERTER'][$entityID])
		{
			unset($_SESSION['LEAD_CONVERTER'][$entityID]);
		}
	}
}