<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;

class QuoteConversionWizard extends EntityConversionWizard
{
	/**
	 * @param int $entityID Entity ID.
	 * @param QuoteConversionConfig|null $config Configuration parameters.
	 */
	public function __construct($entityID = 0, QuoteConversionConfig $config = null)
	{
		$converter = new QuoteConverter($config);
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
		/** @var QuoteConverter $converter */
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
			elseif(isset($resultData[\CCrmOwnerType::InvoiceName]))
			{
				$this->redirectUrl = \CCrmOwnerType::GetShowUrl(\CCrmOwnerType::Invoice, $resultData[\CCrmOwnerType::InvoiceName], false);
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
					array('conv_quote_id' => $converter->getEntityID())
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
		/** @var QuoteConverter $converter */
		$converter = $this->converter;

		$userFields = QuoteConversionMapper::getUserFields($entityTypeID);
		$mappedFields = $converter->mapEntityFields($entityTypeID, array('ENABLE_FILES' => false));

		foreach($mappedFields as $k => $v)
		{
			if($k === 'PRODUCT_ROWS')
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
		$dstUserFields = QuoteConversionMapper::getUserFields($entityTypeID);
		foreach($dstUserFields as $dstName => $dstField)
		{
			if($dstField['USER_TYPE_ID'] === 'file')
			{
				$this->prepareFileUserFieldForSave($dstName, $dstField, $fields);
			}
		}

		$mappedFields = $this->converter->mapEntityFields($entityTypeID, array('DISABLE_USER_FIELD_INIT' => true));
		foreach($mappedFields as $k => $v)
		{
			if(!isset($fields[$k]))
			{
				$fields[$k] = $v;
			}
		}
	}
	/**
	 * Save wizard settings in session.
	 * @return void
	 */
	public function save()
	{
		if(!isset($_SESSION['QUOTE_CONVERTER']))
		{
			$_SESSION['QUOTE_CONVERTER'] = array();
		}

		$_SESSION['QUOTE_CONVERTER'][$this->getEntityID()] = $this->externalize();
	}
	/**
	 * Load wizard related to entity from session.
	 * @param int $entityID Entity ID.
	 * @return QuoteConversionWizard|null
	 */
	public static function load($entityID)
	{
		if(!(isset($_SESSION['QUOTE_CONVERTER']) && $_SESSION['QUOTE_CONVERTER'][$entityID]))
		{
			return null;
		}

		$item = new QuoteConversionWizard($entityID);
		$item->internalize($_SESSION['QUOTE_CONVERTER'][$entityID]);
		return $item;
	}
	/**
	 * Remove wizard related to entity from session.
	 * @param int $entityID Entity ID.
	 * @return void
	 */
	public static function remove($entityID)
	{
		if(isset($_SESSION['QUOTE_CONVERTER']) && $_SESSION['QUOTE_CONVERTER'][$entityID])
		{
			unset($_SESSION['QUOTE_CONVERTER'][$entityID]);
		}
	}
}