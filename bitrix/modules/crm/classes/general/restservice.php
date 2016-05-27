<?php
if(!CModule::IncludeModule('rest'))
{
	return;
}

use Bitrix\Rest\RestException;
use Bitrix\Rest\UserFieldProxy;
use Bitrix\Crm\Integration\StorageFileType;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Integration\DiskManager;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Color\PhaseColorSchemeElement;
use Bitrix\Crm\Color\DealStageColorScheme;
use Bitrix\Crm\Color\LeadStatusColorScheme;
use Bitrix\Crm\Color\QuoteStatusColorScheme;

final class CCrmRestService extends IRestService
{
	private static $METHOD_NAMES = array(
		//region Status
		'crm.status.fields',
		'crm.status.add',
		'crm.status.get',
		'crm.status.list',
		'crm.status.update',
		'crm.status.delete',
		'crm.status.entity.types',
		'crm.status.entity.items',
		'crm.status.extra.fields',

		'crm.invoice.status.fields',
		'crm.invoice.status.add',
		'crm.invoice.status.get',
		'crm.invoice.status.list',
		'crm.invoice.status.update',
		'crm.invoice.status.delete',
		//endregion
		//region Enumeration
		'crm.enum.fields',
		'crm.enum.ownertype',
		'crm.enum.contenttype',
		'crm.enum.activitytype',
		'crm.enum.activitypriority',
		'crm.enum.activitydirection',
		'crm.enum.activitynotifytype',
		//endregion
		//region Lead
		'crm.lead.fields',
		'crm.lead.add',
		'crm.lead.get',
		'crm.lead.list',
		'crm.lead.update',
		'crm.lead.delete',
		'crm.lead.productrows.set',
		'crm.lead.productrows.get',
		//endregion
		//region Deal
		'crm.deal.fields',
		'crm.deal.add',
		'crm.deal.get',
		'crm.deal.list',
		'crm.deal.update',
		'crm.deal.delete',
		'crm.deal.productrows.set',
		'crm.deal.productrows.get',
		//endregion
		//region Company
		'crm.company.fields',
		'crm.company.add',
		'crm.company.get',
		'crm.company.list',
		'crm.company.update',
		'crm.company.delete',
		//endregion
		//region Contact
		'crm.contact.fields',
		'crm.contact.add',
		'crm.contact.get',
		'crm.contact.list',
		'crm.contact.update',
		'crm.contact.delete',
		//endregion
		//region Currency
		'crm.currency.fields',
		'crm.currency.add',
		'crm.currency.get',
		'crm.currency.list',
		'crm.currency.update',
		'crm.currency.delete',
		'crm.currency.localizations.fields',
		'crm.currency.localizations.get',
		'crm.currency.localizations.set',
		'crm.currency.localizations.delete',
		//endregion
		//region Catalog
		'crm.catalog.fields',
		'crm.catalog.get',
		'crm.catalog.list',
		//endregion
		//region Product
		'crm.product.fields',
		'crm.product.add',
		'crm.product.get',
		'crm.product.list',
		'crm.product.update',
		'crm.product.delete',
		//endregion
		//region Product Property
		'crm.product.property.types',
		'crm.product.property.fields',
		'crm.product.property.settings.fields',
		'crm.product.property.enumeration.fields',
		'crm.product.property.add',
		'crm.product.property.get',
		'crm.product.property.list',
		'crm.product.property.update',
		'crm.product.property.delete',
		//endregion
		//region Product Section
		'crm.productsection.fields',
		'crm.productsection.add',
		'crm.productsection.get',
		'crm.productsection.list',
		'crm.productsection.update',
		'crm.productsection.delete',
		//endregion
		//region Product Row
		'crm.productrow.fields',
		'crm.productrow.add',
		'crm.productrow.get',
		'crm.productrow.list',
		'crm.productrow.update',
		'crm.productrow.delete',
		//endregion
		//region Activity
		'crm.activity.fields',
		'crm.activity.add',
		'crm.activity.get',
		'crm.activity.list',
		'crm.activity.update',
		'crm.activity.delete',
		'crm.activity.communication.fields',
		//endregion
		//region Quote
		'crm.quote.fields',
		'crm.quote.add',
		'crm.quote.get',
		'crm.quote.list',
		'crm.quote.update',
		'crm.quote.delete',
		'crm.quote.productrows.set',
		'crm.quote.productrows.get',
		//endregion
		//region User Field
		'crm.lead.userfield.add',
		'crm.lead.userfield.get',
		'crm.lead.userfield.list',
		'crm.lead.userfield.update',
		'crm.lead.userfield.delete',

		'crm.deal.userfield.add',
		'crm.deal.userfield.get',
		'crm.deal.userfield.list',
		'crm.deal.userfield.update',
		'crm.deal.userfield.delete',

		'crm.company.userfield.add',
		'crm.company.userfield.get',
		'crm.company.userfield.list',
		'crm.company.userfield.update',
		'crm.company.userfield.delete',

		'crm.contact.userfield.add',
		'crm.contact.userfield.get',
		'crm.contact.userfield.list',
		'crm.contact.userfield.update',
		'crm.contact.userfield.delete',

		'crm.quote.userfield.add',
		'crm.quote.userfield.get',
		'crm.quote.userfield.list',
		'crm.quote.userfield.update',
		'crm.quote.userfield.delete',

		'crm.userfield.fields',
		'crm.userfield.types',
		'crm.userfield.enumeration.fields',
		'crm.userfield.settings.fields',
		//endregion
		//region Misc.
		'crm.multifield.fields',
		'crm.duplicate.findbycomm',
		'crm.livefeedmessage.add',
		//endregion
	);
	const SCOPE_NAME = 'crm';
	private static $DESCRIPTION = null;
	private static $PROXIES = array();

	public static function onRestServiceBuildDescription()
	{
		if(!self::$DESCRIPTION)
		{
			$bindings = array();
			// There is one entry point
			$callback = array('CCrmRestService', 'onRestServiceMethod');
			foreach(self::$METHOD_NAMES as $name)
			{
				$bindings[$name] = $callback;
			}

			CCrmLeadRestProxy::registerEventBindings($bindings);
			CCrmDealRestProxy::registerEventBindings($bindings);
			CCrmCompanyRestProxy::registerEventBindings($bindings);
			CCrmContactRestProxy::registerEventBindings($bindings);
			CCrmQuoteRestProxy::registerEventBindings($bindings);
			CCrmCurrencyRestProxy::registerEventBindings($bindings);
			CCrmProductRestProxy::registerEventBindings($bindings);
			CCrmActivityRestProxy::registerEventBindings($bindings);

			self::$DESCRIPTION = array('crm' => $bindings);
		}

		return self::$DESCRIPTION;
	}
	public static function onRestServiceMethod($arParams, $nav, CRestServer $server)
	{
		if(!CCrmPerms::IsAccessEnabled())
		{
			throw new RestException('Access denied.');
		}

		$methodName = $server->getMethod();

		$parts = explode('.', $methodName);
		$partCount = count($parts);
		if($partCount < 3 || $parts[0] !== 'crm')
		{
			throw new RestException("Method '{$methodName}' is not supported in current context.");
		}

		$typeName = strtoupper($parts[1]);
		$proxy = null;

		if(isset(self::$PROXIES[$typeName]))
		{
			$proxy = self::$PROXIES[$typeName];
		}

		if(!$proxy)
		{
			if($typeName === 'ENUM')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmEnumerationRestProxy();
			}
			elseif($typeName === 'MULTIFIELD')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmMultiFieldRestProxy();
			}
			elseif($typeName === 'CURRENCY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmCurrencyRestProxy();
			}
			elseif($typeName === 'CATALOG')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmCatalogRestProxy();
			}
			elseif($typeName === 'PRODUCT' && strtoupper($parts[2]) === 'PROPERTY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmProductPropertyRestProxy();
			}
			elseif($typeName === 'PRODUCT')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmProductRestProxy();
			}
			elseif($typeName === 'PRODUCTSECTION')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmProductSectionRestProxy();
			}
			elseif($typeName === 'PRODUCTROW')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmProductRowRestProxy();
			}
			elseif($typeName === 'STATUS')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmStatusRestProxy();
			}
			elseif($typeName === 'LEAD')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmLeadRestProxy();
			}
			elseif($typeName === 'DEAL')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmDealRestProxy();
			}
			elseif($typeName === 'COMPANY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmCompanyRestProxy();
			}
			elseif($typeName === 'CONTACT')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmContactRestProxy();
			}
			elseif($typeName === 'QUOTE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmQuoteRestProxy();
			}
			elseif($typeName === 'ACTIVITY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmActivityRestProxy();
			}
			elseif($typeName === 'DUPLICATE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmDuplicateRestProxy();
			}
			elseif($typeName === 'LIVEFEEDMESSAGE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmLiveFeedMessageRestProxy();
			}
			elseif($typeName === 'USERFIELD')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmUserFieldRestProxy(CCrmOwnerType::Undefined);
			}
			elseif($typeName === 'INVOICE' && strtoupper($parts[2]) === 'STATUS')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmStatusInvoiceRestProxy();
			}
			else
			{
				throw new RestException("Could not find proxy for method '{$methodName}'.");
			}
			$proxy->setServer($server);
		}

		return $proxy->processMethodRequest(
			$parts[2],
			$partCount > 3 ? array_slice($parts, 3) : array(),
			$arParams,
			$nav,
			$server
		);
	}
	public static function getNavData($start)
	{
		return parent::getNavData($start);
	}
	public static function setNavData($result, $dbRes)
	{
		return parent::setNavData($result, $dbRes);
	}
}

class CCrmRestHelper
{
	public static function resolveArrayParam(array &$arParams, $name, array $default = null)
	{
		// Check for upper case notation (FILTER, SORT, SELECT, etc)
		$upper = strtoupper($name);
		if(isset($arParams[$upper]))
		{
			return $arParams[$upper];
		}

		// Check for lower case notation (filter, sort, select, etc)
		$lower = strtolower($name);
		if(isset($arParams[$lower]))
		{
			return $arParams[$lower];
		}

		// Check for capitalized notation (Filter, Sort, Select, etc)
		$capitalized = ucfirst($lower);
		if(isset($arParams[$capitalized]))
		{
			return $arParams[$capitalized];
		}

		// Check for hungary notation (arFilter, arSort, arSelect, etc)
		$hungary = "ar{$capitalized}";
		if(isset($arParams[$hungary]))
		{
			return $arParams[$hungary];
		}

		return $default;
	}
	public static function resolveParam(array &$arParams, $name, $default = null)
	{
		// Check for lower case notation (type, etc)
		$lower = strtolower($name);
		if(isset($arParams[$lower]))
		{
			return $arParams[$lower];
		}

		// Check for upper case notation (TYPE, etc)
		$upper = strtoupper($name);
		if(isset($arParams[$upper]))
		{
			return $arParams[$upper];
		}

		// Check for capitalized notation (Type, etc)
		$capitalized = ucfirst($lower);
		if(isset($arParams[$capitalized]))
		{
			return $arParams[$capitalized];
		}

		return $default;
	}
}

abstract class CCrmRestProxyBase
{
	private $currentUser = null;
	private $webdavSettings = null;
	private $webdavIBlock = null;
	/** @var CRestServer  */
	private $server = null;
	private $sanitizer = null;
	private static $MULTIFIELD_TYPE_IDS = null;
	public function getFields()
	{
		$fildsInfo = $this->getFieldsInfo();
		return self::prepareFields($fildsInfo);
	}
	public function isValidID($ID)
	{
		return is_int($ID) && $ID > 0;
	}
	public function add(&$fields, array $params = null)
	{
		$this->internalizeFields($fields, $this->getFieldsInfo(), array());

		$errors = array();
		$result = $this->innerAdd($fields, $errors, $params);
		if(!$this->isValidID($result))
		{
			throw new RestException(implode("\n", $errors));
		}

		return $result;
	}
	public function get($ID)
	{
		if(!$this->checkEntityID($ID))
		{
			throw new RestException('ID is not defined or invalid.');
		}


		$errors = array();
		$result = $this->innerGet($ID, $errors);
		if(!is_array($result))
		{
			throw new RestException(implode("\n", $errors));
		}
		$this->externalizeFields($result, $this->getFieldsInfo());
		return $result;

	}
	public function getList($order, $filter, $select, $start)
	{
		$this->prepareListParams($order, $filter, $select);

		$navigation = CCrmRestService::getNavData($start);

		$enableMultiFields = false;
		$selectedFmTypeIDs = array();
		if(is_array($select) && !empty($select))
		{
			$supportedFmTypeIDs = $this->getSupportedMultiFieldTypeIDs();

			if(is_array($supportedFmTypeIDs) && !empty($supportedFmTypeIDs))
			{
				foreach($supportedFmTypeIDs as $fmTypeID)
				{
					if(in_array($fmTypeID, $select, true))
					{
						$selectedFmTypeIDs[] = $fmTypeID;
					}
				}
			}
			$enableMultiFields = !empty($selectedFmTypeIDs);
			if($enableMultiFields)
			{
				$identityFieldName = $this->getIdentityFieldName();
				if($identityFieldName === '')
				{
					throw new RestException('Could not find identity field name.');
				}

				if(!in_array($identityFieldName, $select, true))
				{
					$select[] = $identityFieldName;
				}
			}
		}

		$this->internalizeFilterFields($filter, $this->getFieldsInfo());
		$errors = array();
		$result = $this->innerGetList($order, $filter, $select, $navigation, $errors);
		if(!$result)
		{
			throw new RestException(implode("\n", $errors));
		}

		return $result instanceOf CDBResult
			? $this->prepareListFromDbResult($result, array('SELECTED_FM_TYPES' => $selectedFmTypeIDs))
			: $this->prepareListFromArray($result, array('SELECTED_FM_TYPES' => $selectedFmTypeIDs));
	}
	protected function prepareListFromDbResult(CDBResult $dbResult, array $options)
	{
		$result = array();
		$fieldsInfo = $this->getFieldsInfo();

		$selectedFmTypeIDs = isset($options['SELECTED_FM_TYPES']) ? $options['SELECTED_FM_TYPES'] : array();
		if(empty($selectedFmTypeIDs))
		{
			while($fields = $dbResult->Fetch())
			{
				$this->prepareListItemFields($fields);

				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
		}
		else
		{
			$entityMap = array();
			while($fields = $dbResult->Fetch())
			{
				$this->prepareListItemFields($fields);

				$entityID = intval($this->getIdentity($fields));
				if($entityID <= 0)
				{
					throw new RestException('Could not find entity ID.');
				}
				$entityMap[$entityID] = $fields;
			}

			$this->prepareListItemMultiFields($entityMap, $this->getOwnerTypeID(), $selectedFmTypeIDs);

			foreach($entityMap as &$fields)
			{
				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
			unset($fields);
		}

		return CCrmRestService::setNavData($result, $dbResult);
	}
	protected function prepareListFromArray(array $list, array $options)
	{
		$result = array();
		$fieldsInfo = $this->getFieldsInfo();

		$selectedFmTypeIDs = isset($options['SELECTED_FM_TYPES']) ? $options['SELECTED_FM_TYPES'] : array();
		if(empty($selectedFmTypeIDs))
		{
			foreach($list as $fields)
			{
				$this->prepareListItemFields($fields);

				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
		}
		else
		{
			$entityMap = array();
			foreach($list as $fields)
			{
				$this->prepareListItemFields($fields);

				$entityID = intval($this->getIdentity($fields));
				if($entityID <= 0)
				{
					throw new RestException('Could not find entity ID.');
				}
				$entityMap[$entityID] = $fields;
			}

			$this->prepareListItemMultiFields($entityMap, $this->getOwnerTypeID(), $selectedFmTypeIDs);

			foreach($entityMap as &$fields)
			{
				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
			unset($fields);
		}

		return CCrmRestService::setNavData($result, array('offset' => 0, 'count' => count($result)));
	}
	public function update($ID, &$fields, array $params = null)
	{
		if(!$this->checkEntityID($ID))
		{
			throw new RestException('ID is not defined or invalid.');
		}

		$this->internalizeFields(
			$fields,
			$this->getFieldsInfo(),
			array(
				'IGNORED_ATTRS' => array(
					CCrmFieldInfoAttr::Immutable,
					CCrmFieldInfoAttr::UserPKey
				)
			)
		);

		$errors = array();
		$result = $this->innerUpdate($ID, $fields, $errors, $params);
		if($result !== true)
		{
			throw new RestException(implode("\n", $errors));
		}

		return $result;
	}
	public function delete($ID, array $params = null)
	{
		if(!$this->checkEntityID($ID))
		{
			throw new RestException('ID is not defined or invalid.');
		}

		$errors = array();
		$result = $this->innerDelete($ID, $errors, $params);
		if($result !== true)
		{
			throw new RestException(implode("\n", $errors));
		}

		return $result;
	}
	protected function prepareListParams(&$order, &$filter, &$select)
	{
	}
	protected function prepareListItemFields(&$fields)
	{
	}
	protected function getCurrentUser()
	{
		return $this->currentUser !== null
			? $this->currentUser
			: ($this->currentUser = CCrmSecurityHelper::GetCurrentUser());
	}
	protected function getCurrentUserID()
	{
		return $this->getCurrentUser()->GetID();
	}
	public function getServer()
	{
		return $this->server;
	}
	public function setServer(CRestServer $server)
	{
		$this->server = $server;
	}
	public function getOwnerTypeID()
	{
		return CCrmOwnerType::Undefined;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$ownerTypeID = $this->getOwnerTypeID();

		$name = strtoupper($name);
		if($name === 'FIELDS')
		{
			return $this->getFields();
		}
		elseif($name === 'ADD')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');
			$methodParams = $this->resolveArrayParam($arParams, 'params');
			return $this->add($fields, $methodParams);
		}
		elseif($name === 'GET')
		{
			return $this->get($this->resolveEntityID($arParams));
		}
		elseif($name === 'LIST')
		{
			$order = $this->resolveArrayParam($arParams, 'order');
			if(!is_array($order))
			{
				throw new RestException("Parameter 'order' must be array.");
			}

			$filter = $this->resolveArrayParam($arParams, 'filter');
			if(!is_array($filter))
			{
				throw new RestException("Parameter 'filter' must be array.");
			}
			$select = $this->resolveArrayParam($arParams, 'select');
			return $this->getList($order, $filter, $select, $nav);
		}
		elseif($name === 'UPDATE')
		{
			$ID = $this->resolveEntityID($arParams);
			$fields = $fields = $this->resolveArrayParam($arParams, 'fields');
			$methodParams = $this->resolveArrayParam($arParams, 'params');
			return $this->update($ID, $fields, $methodParams);
		}
		elseif($name === 'DELETE')
		{
			$ID = $this->resolveEntityID($arParams);
			$methodParams = $this->resolveArrayParam($arParams, 'params');
			return $this->delete($ID, $methodParams);
		}
		elseif($name === 'USERFIELD' && $ownerTypeID !== CCrmOwnerType::Undefined)
		{
			$ufProxy = new CCrmUserFieldRestProxy($ownerTypeID);

			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'ADD')
			{
				$fields = $this->resolveArrayParam($arParams, 'fields', null);
				return $ufProxy->add(is_array($fields) ? $fields : $arParams);
			}
			elseif($nameSuffix === 'GET')
			{
				return $ufProxy->get($this->resolveParam($arParams, 'id', ''));
			}
			elseif($nameSuffix === 'LIST')
			{
				$order = $this->resolveArrayParam($arParams, 'order', array());
				if(!is_array($order))
				{
					throw new RestException("Parameter 'order' must be array.");
				}

				$filter = $this->resolveArrayParam($arParams, 'filter', array());
				if(!is_array($filter))
				{
					throw new RestException("Parameter 'filter' must be array.");
				}

				return $ufProxy->getList($order, $filter);
			}
			elseif($nameSuffix === 'UPDATE')
			{
				return $ufProxy->update(
					$this->resolveParam($arParams, 'id'),
					$this->resolveArrayParam($arParams, 'fields')
				);
			}
			elseif($nameSuffix === 'DELETE')
			{
				return $ufProxy->delete($this->resolveParam($arParams, 'id', ''));
			}
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
	protected function resolveParam(&$arParams, $name)
	{
		return CCrmRestHelper::resolveParam($arParams, $name, '');
	}
	protected function resolveMultiPartParam(&$arParams, array $nameParts, $default = '')
	{
		if(empty($nameParts))
		{
			return $default;
		}

		$upperUnderscoreName = strtoupper(implode('_', $nameParts));
		if(isset($arParams[$upperUnderscoreName]))
		{
			return $arParams[$upperUnderscoreName];
		}

		$lowerUnderscoreName = strtolower($upperUnderscoreName);
		if(isset($arParams[$lowerUnderscoreName]))
		{
			return $arParams[$lowerUnderscoreName];
		}

		$hungaryName = '';
		foreach($nameParts as $namePart)
		{
			$hungaryName .= ucfirst($namePart);
		}

		if(isset($arParams[$hungaryName]))
		{
			return $arParams[$hungaryName];
		}

		$hungaryName = "ar{$hungaryName}";
		if(isset($arParams[$hungaryName]))
		{
			return $arParams[$hungaryName];
		}

		return $default;
	}
	protected function resolveArrayParam(&$arParams, $name, $default = array())
	{
		return CCrmRestHelper::resolveArrayParam($arParams, $name, $default);
	}
	protected function resolveEntityID(&$arParams)
	{
		return isset($arParams['ID'])
			? intval($arParams['ID'])
			: (isset($arParams['id']) ? intval($arParams['id']) : 0);
	}
	protected function resolveRelationID(&$arParams, $relationName)
	{
		$nameLowerCase = strtolower($relationName);
		// Check for camel case (entityId or entityID)
		$camel = "{$nameLowerCase}Id";
		if(isset($arParams[$camel]))
		{
			return $arParams[$camel];
		}

		$camel = "{$nameLowerCase}ID";
		if(isset($arParams[$camel]))
		{
			return $arParams[$camel];
		}

		// Check for lower case (entity_id)
		$lower = "{$nameLowerCase}_id";
		if(isset($arParams[$lower]))
		{
			return $arParams[$lower];
		}

		// Check for upper case (ENTITY_ID)
		$upper = strtoupper($lower);
		if(isset($arParams[$upper]))
		{
			return $arParams[$upper];
		}

		return '';
	}
	protected function checkEntityID($ID)
	{
		return is_int($ID) && $ID > 0;
	}
	protected static function prepareMultiFieldsInfo(&$fieldsInfo)
	{
		$typesID = array_keys(CCrmFieldMulti::GetEntityTypeInfos());
		foreach($typesID as $typeID)
		{
			$fieldsInfo[$typeID] = array(
				'TYPE' => 'crm_multifield',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
			);
		}
	}
	protected static function prepareUserFieldsInfo(&$fieldsInfo, $entityTypeID)
	{
		$userType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], $entityTypeID);
		$userType->PrepareFieldsInfo($fieldsInfo);
	}
	protected static function prepareFields(&$fieldsInfo)
	{
		$result = array();

		foreach($fieldsInfo as $fieldID => &$fieldInfo)
		{
			$attrs = isset($fieldInfo['ATTRIBUTES']) ? $fieldInfo['ATTRIBUTES'] : array();
			// Skip hidden fields
			if(in_array(CCrmFieldInfoAttr::Hidden, $attrs, true))
			{
				continue;
			}

			$fieldType = $fieldInfo['TYPE'];
			$field = array(
				'type' => $fieldType,
				'isRequired' => in_array(CCrmFieldInfoAttr::Required, $attrs, true),
				'isReadOnly' => in_array(CCrmFieldInfoAttr::ReadOnly, $attrs, true),
				'isImmutable' => in_array(CCrmFieldInfoAttr::Immutable, $attrs, true),
				'isMultiple' => in_array(CCrmFieldInfoAttr::Multiple, $attrs, true),
				'isDynamic' => in_array(CCrmFieldInfoAttr::Dynamic, $attrs, true)
			);

			if(in_array(CCrmFieldInfoAttr::Deprecated, $attrs, true))
			{
				$field['isDeprecated'] = true;
			}

			if($fieldType === 'enumeration')
			{
				$field['items'] = isset($fieldInfo['ITEMS']) ? $fieldInfo['ITEMS'] : array();
			}
			elseif($fieldType === 'crm_status')
			{
				$field['statusType'] = isset($fieldInfo['CRM_STATUS_TYPE']) ? $fieldInfo['CRM_STATUS_TYPE'] : '';
			}
			elseif ($fieldType === 'product_property')
			{
				$field['propertyType'] = isset($fieldInfo['PROPERTY_TYPE']) ? $fieldInfo['PROPERTY_TYPE'] : '';
				$field['userType'] = isset($fieldInfo['USER_TYPE']) ? $fieldInfo['USER_TYPE'] : '';
				$field['title'] = isset($fieldInfo['NAME']) ? $fieldInfo['NAME'] : '';
				if ($field['propertyType'] === 'L')
					$field['values'] = isset($fieldInfo['VALUES']) ? $fieldInfo['VALUES'] : array();
			}

			if(isset($fieldInfo['LABELS']) && is_array($fieldInfo['LABELS']))
			{
				$labels = $fieldInfo['LABELS'];
				if(isset($labels['LIST']))
				{
					$field['listLabel'] = $labels['LIST'];
				}
				if(isset($labels['FORM']))
				{
					$field['formLabel'] = $labels['FORM'];
				}
				if(isset($labels['FILTER']))
				{
					$field['filterLabel'] = $labels['FILTER'];
				}
			}

			$result[$fieldID] = &$field;
			unset($field);
		}
		unset($fieldInfo);

		return $result;
	}
	protected function internalizeFields(&$fields, &$fieldsInfo, $options = array())
	{

		if(!is_array($fields))
		{
			return;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$ignoredAttrs = isset($options['IGNORED_ATTRS']) ? $options['IGNORED_ATTRS'] : array();
		if(!in_array(CCrmFieldInfoAttr::Hidden, $ignoredAttrs, true))
		{
			$ignoredAttrs[] = CCrmFieldInfoAttr::Hidden;
		}
		if(!in_array(CCrmFieldInfoAttr::ReadOnly, $ignoredAttrs, true))
		{
			$ignoredAttrs[] = CCrmFieldInfoAttr::ReadOnly;
		}

		$multifields = array();
		foreach($fields as $k => $v)
		{
			$info = isset($fieldsInfo[$k]) ? $fieldsInfo[$k] : null;
			if(!$info)
			{
				unset($fields[$k]);
				continue;
			}

			$attrs = isset($info['ATTRIBUTES']) ? $info['ATTRIBUTES'] : array();
			$isMultiple = in_array(CCrmFieldInfoAttr::Multiple, $attrs, true);

			$ary = array_intersect($ignoredAttrs, $attrs);
			if(!empty($ary))
			{
				unset($fields[$k]);
				continue;
			}

			$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
			if($fieldType === 'date')
			{
				$date = CRestUtil::unConvertDate($v);
				if(is_string($date))
				{
					$fields[$k] = $date;
				}
				else
				{
					unset($fields[$k]);
				}
			}
			elseif($fieldType === 'datetime')
			{
				$date = CRestUtil::unConvertDateTime($v);
				if(is_string($date))
				{
					$fields[$k] = $date;
				}
				else
				{
					unset($fields[$k]);
				}
			}
			elseif($fieldType === 'file')
			{
				$this->tryInternalizeFileField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'webdav')
			{
				$this->tryInternalizeWebDavElementField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'diskfile')
			{
				$this->tryInternalizeDiskFileField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'crm_multifield')
			{
				$this->tryInternalizeMultiFields($fields, $k, $multifields);
			}
			elseif($fieldType === 'product_file')
			{
				$this->tryInternalizeProductFileField($fields, $k);
			}
			elseif($fieldType === 'product_property')
			{
				$this->tryInternalizeProductPropertyField($fields, $fieldsInfo, $k);
			}
		}

		if(!empty($multifields))
		{
			$fields['FM'] = $multifields;
		}
	}
	protected function tryInternalizeMultiFields(array &$fields, $fieldName, array &$data)
	{
		if(!isset($fields[$fieldName]) && is_array($fields[$fieldName]))
		{
			return false;
		}

		$qty = 0;
		$result = array();
		$values = $fields[$fieldName];
		foreach($values as &$v)
		{
			$ID = isset($v['ID']) ? $v['ID'] : 0;
			$value = isset($v['VALUE']) ? trim($v['VALUE']) : '';
			//Allow empty values for persistent fields for support deletion operation.
			if($ID <= 0 && $value === '')
			{
				continue;
			}

			if($ID > 0 && isset($v['DELETE']) && strtoupper($v['DELETE']) === 'Y')
			{
				//Empty fields will be deleted.
				$value = '';
			}

			$valueType = isset($v['VALUE_TYPE']) ? trim($v['VALUE_TYPE']) : '';
			if($valueType === '')
			{
				$valueType = CCrmFieldMulti::GetDefaultValueType($fieldName);
			}

			$key = $ID > 0 ? $ID : 'n'.(++$qty);
			$result[$key] = array('VALUE_TYPE' => $valueType, 'VALUE' => $value);
		}
		unset($v, $fields[$fieldName]);

		if(empty($result))
		{
			return false;
		}

		$data[$fieldName] = $result;
		return true;
	}
	protected function tryInternalizeFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$fileID = isset($v['id']) ? intval($v['id']) : 0;
			$removeFile = isset($v['remove']) && is_string($v['remove']) && strtoupper($v['remove']) === 'Y';
			$fileData = isset($v['fileData']) ? $v['fileData'] : '';

			if(!self::isIndexedArray($fileData))
			{
				$fileName = '';
				$fileContent = $fileData;
			}
			else
			{
				$fileDataLength = count($fileData);

				if($fileDataLength > 1)
				{
					$fileName = $fileData[0];
					$fileContent = $fileData[1];
				}
				elseif($fileDataLength === 1)
				{
					$fileName = '';
					$fileContent = $fileData[0];
				}
				else
				{
					$fileName = '';
					$fileContent = '';
				}
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				// Add/replace file
				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);
				if(is_array($fileInfo))
				{
					if($fileID > 0)
					{
						$fileInfo['old_id'] = $fileID;
					}

					//In this case 'del' flag does not make sense - old file will be replaced by new one.
					/*if($removeFile)
					{
						$fileInfo['del'] = true;
					}*/

					$result[] = &$fileInfo;
					unset($fileInfo);
				}
			}
			elseif($fileID > 0 && $removeFile)
			{
				// Remove file
				$result[] = array(
					'old_id' => $fileID,
					'del' => true
				);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryInternalizeProductFileField(&$fields, $fieldName)
	{
		if(!(isset($fields[$fieldName]) && self::isAssociativeArray($fields[$fieldName])))
			return false;

		$result = array();

		//$fileID = isset($fields[$fieldName]['id']) ? intval($fields[$fieldName]['id']) : 0;
		$removeFile = isset($fields[$fieldName]['remove']) && is_string($fields[$fieldName]['remove'])
			&& strtoupper($fields[$fieldName]['remove']) === 'Y';
		$fileData = isset($fields[$fieldName]['fileData']) ? $fields[$fieldName]['fileData'] : '';

		if(!self::isIndexedArray($fileData))
		{
			$fileName = '';
			$fileContent = $fileData;
		}
		else
		{
			$fileDataLength = count($fileData);

			if($fileDataLength > 1)
			{
				$fileName = $fileData[0];
				$fileContent = $fileData[1];
			}
			elseif($fileDataLength === 1)
			{
				$fileName = '';
				$fileContent = $fileData[0];
			}
			else
			{
				$fileName = '';
				$fileContent = '';
			}
		}

		if(is_string($fileContent) && $fileContent !== '')
		{
			// Add/replace file
			$fileInfo = CRestUtil::saveFile($fileContent, $fileName);
			if(is_array($fileInfo))
			{
				$result = &$fileInfo;
				unset($fileInfo);
			}
		}
		elseif($removeFile)
		{
			// Remove file
			$result = array(
				'del' => 'Y'
			);
		}

		if(!empty($result))
		{
			$fields[$fieldName] = $result;
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryInternalizeWebDavElementField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$elementID = isset($v['id']) ? intval($v['id']) : 0;
			$removeElement = isset($v['remove']) && is_string($v['remove']) && strtoupper($v['remove']) === 'Y';
			$fileData = isset($v['fileData']) ? $v['fileData'] : '';

			if(!self::isIndexedArray($fileData))
			{
				continue;
			}

			$fileDataLength = count($fileData);
			if($fileDataLength === 0)
			{
				continue;
			}

			if($fileDataLength === 1)
			{
				$fileName = '';
				$fileContent = $fileData[0];
			}
			else
			{
				$fileName = $fileData[0];
				$fileContent = $fileData[1];
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);

				$settings = $this->getWebDavSettings();
				$iblock = $this->prepareWebDavIBlock($settings);
				$fileName = $iblock->CorrectName($fileName);

				$filePath = $fileInfo['tmp_name'];
				$options = array(
					'new' => true,
					'dropped' => false,
					'arDocumentStates' => array(),
					'arUserGroups' => $iblock->USER['GROUPS'],
					'TMP_FILE' => $filePath,
					'FILE_NAME' => $fileName,
					'IBLOCK_ID' => $settings['IBLOCK_ID'],
					'IBLOCK_SECTION_ID' => $settings['IBLOCK_SECTION_ID'],
					'WF_STATUS_ID' => 1
				);
				$options['arUserGroups'][] = 'Author';

				global $DB;
				$DB->StartTransaction();
				if (!$iblock->put_commit($options))
				{
					$DB->Rollback();
					unlink($filePath);
					throw new RestException($iblock->LAST_ERROR);
				}
				$DB->Commit();
				unlink($filePath);

				if(!isset($options['ELEMENT_ID']))
				{
					throw new RestException('Could not save webdav element.');
				}

				$elementData = array(
					'ELEMENT_ID' => $options['ELEMENT_ID']
				);

				if($elementID > 0)
				{
					$elementData['OLD_ELEMENT_ID'] = $elementID;
				}

				$result[] = &$elementData;
				unset($elementData);
			}
			elseif($elementID > 0 && $removeElement)
			{
				$result[] = array(
					'OLD_ELEMENT_ID' => $elementID,
					'DELETE' => true
				);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryInternalizeDiskFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$fileID = isset($v['id']) ? intval($v['id']) : 0;
			$removeElement = isset($v['remove']) && is_string($v['remove']) && strtoupper($v['remove']) === 'Y';
			$fileData = isset($v['fileData']) ? $v['fileData'] : '';

			if(!self::isIndexedArray($fileData))
			{
				continue;
			}

			$fileDataLength = count($fileData);
			if($fileDataLength === 0)
			{
				continue;
			}

			if($fileDataLength === 1)
			{
				$fileName = '';
				$fileContent = $fileData[0];
			}
			else
			{
				$fileName = $fileData[0];
				$fileContent = $fileData[1];
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);

				$folder = DiskManager::ensureFolderCreated(StorageFileType::Rest);
				if(!$folder)
				{
					unlink($fileInfo['tmp_name']);
					throw new RestException('Could not create disk folder for rest files.');
				}

				$file = $folder->uploadFile(
					$fileInfo,
					array('NAME' => $fileName, 'CREATED_BY' => $this->getCurrentUserID()),
					array(),
					true
				);
				unlink($fileInfo['tmp_name']);

				if(!$file)
				{
					throw new RestException('Could not create disk file.');
				}

				$result[] = array('FILE_ID' => $file->getId());
			}
			elseif($fileID > 0 && $removeElement)
			{
				$result[] = array('OLD_FILE_ID' => $fileID, 'DELETE' => true);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryInternalizeProductPropertyField(&$fields, &$fieldsInfo, $fieldName)
	{
		static $sanitizer = null;

		if(!is_array($fields) || !isset($fields[$fieldName]))
		{
			return;
		}

		$info = isset($fieldsInfo[$fieldName]) ? $fieldsInfo[$fieldName] : null;
		$rawValue = isset($fields[$fieldName]) ? $fields[$fieldName] : null;

		if(!$info)
		{
			unset($fields[$fieldName]);
			return;
		}

		$attrs = isset($info['ATTRIBUTES']) ? $info['ATTRIBUTES'] : array();

		$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
		$propertyType = isset($info['PROPERTY_TYPE']) ? $info['PROPERTY_TYPE'] : '';
		$userType = isset($info['USER_TYPE']) ? $info['USER_TYPE'] : '';

		if ($fieldType === 'product_property')
		{
			$value = array();
			$newIndex = 0;
			$valueId = 'n'.$newIndex;
			if (!self::isIndexedArray($rawValue))
				$rawValue = array($rawValue);
			foreach ($rawValue as &$valueElement)
			{
				if (is_array($valueElement) && isset($valueElement['value']))
				{
					$valueId = (isset($valueElement['valueId']) && intval($valueElement['valueId']) > 0) ?
						intval($valueElement['valueId']) : 'n'.$newIndex++;
					$value[$valueId] = &$valueElement['value'];
				}
				else
				{
					$valueId = 'n'.$newIndex++;
					$value[$valueId] = &$valueElement;
				}
			}
			unset($newIndex, $valueElement);
			foreach ($value as $valueId => $v)
			{
				if($propertyType === 'S' && $userType === 'Date')
				{
					$date = CRestUtil::unConvertDate($v);
					if(is_string($date))
						$value[$valueId] = $date;
					else
						unset($value[$valueId]);
				}
				elseif($propertyType === 'S' && $userType === 'DateTime')
				{
					$datetime = CRestUtil::unConvertDateTime($v);
					if(is_string($datetime))
						$value[$valueId] = $datetime;
					else
						unset($value[$valueId]);
				}
				elseif($propertyType === 'F' && empty($userType))
				{
					$this->tryInternalizeProductFileField($value, $valueId);
				}
				elseif($propertyType === 'S' && $userType === 'HTML')
				{
					if (is_array($v) && isset($v['TYPE']) && isset($v['TEXT'])
						&& strtolower($v['TYPE']) === 'html' && !empty($v['TEXT']))
					{
						if ($sanitizer === null)
						{
							$sanitizer = new CBXSanitizer();
							$sanitizer->ApplyDoubleEncode(false);
							$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);
						}
						$value[$valueId]['TEXT'] = $sanitizer->SanitizeHtml($v['TEXT']);
					}
				}
			}
			$fields[$fieldName] = $value;
		}
		else
		{
			unset($fields[$fieldName]);
		}
	}

	protected function externalizeFields(&$fields, &$fieldsInfo)
	{
		if(!is_array($fields))
		{
			return;
		}

		//Multi fields processing
		if(isset($fields['FM']))
		{
			foreach($fields['FM'] as $fmTypeID => &$fmItems)
			{
				foreach($fmItems as &$fmItem)
				{
					$fmItem['TYPE_ID'] = $fmTypeID;
					unset($fmItem['ENTITY_ID'], $fmItem['ELEMENT_ID']);
				}
				unset($fmItem);
				$fields[$fmTypeID] = $fmItems;
			}
			unset($fmItems);
			unset($fields['FM']);
		}

		foreach($fields as $k => $v)
		{
			$info = isset($fieldsInfo[$k]) ? $fieldsInfo[$k] : null;
			if(!$info)
			{
				unset($fields[$k]);
				continue;
			}

			$attrs = isset($info['ATTRIBUTES']) ? $info['ATTRIBUTES'] : array();
			$isMultiple = in_array(CCrmFieldInfoAttr::Multiple, $attrs, true);
			$isHidden = in_array(CCrmFieldInfoAttr::Hidden, $attrs, true);
			$isDynamic = in_array(CCrmFieldInfoAttr::Dynamic, $attrs, true);

			if($isHidden)
			{
				unset($fields[$k]);
				continue;
			}

			$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
			if($fieldType === 'date')
			{
				if(!is_array($v))
				{
					CTimeZone::Disable();
					$fields[$k] = CRestUtil::ConvertDate($v);
					CTimeZone::Enable();
				}
				else
				{
					CTimeZone::Disable();
					$fields[$k] = array();
					foreach($v as &$value)
					{
						$fields[$k][] = CRestUtil::ConvertDate($value);
					}
					unset($value);
					CTimeZone::Enable();
				}
			}
			elseif($fieldType === 'datetime')
			{
				if(!is_array($v))
				{
					$fields[$k] = CRestUtil::ConvertDateTime($v);
				}
				else
				{
					$fields[$k] = array();
					foreach($v as &$value)
					{
						$fields[$k][] = CRestUtil::ConvertDateTime($value);
					}
					unset($value);
				}
			}
			elseif($fieldType === 'file')
			{
				$this->tryExternalizeFileField($fields, $k, $isMultiple, $isDynamic);
			}
			elseif($fieldType === 'webdav')
			{
				$this->tryExternalizeWebDavElementField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'diskfile')
			{
				$this->tryExternalizeDiskFileField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'product_file')
			{
				$this->tryExternalizeProductFileField($fields, $k, false, false);
			}
			elseif($fieldType === 'product_property')
			{
				$this->tryExternalizeProductPropertyField($fields, $fieldsInfo, $k);
			}
		}
	}
	protected function tryExternalizeFileField(&$fields, $fieldName, $multiple = false, $dynamic = true)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$ownerTypeID = $this->getOwnerTypeID();
		$ownerID = isset($fields['ID']) ? intval($fields['ID']) : 0;
		if(!$multiple)
		{
			$fileID = intval($fields[$fieldName]);
			if($fileID <= 0)
			{
				unset($fields[$fieldName]);
				return false;
			}

			$fields[$fieldName] = $this->externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic);
		}
		else
		{
			$result = array();
			$filesID = $fields[$fieldName];
			if(!is_array($filesID))
			{
				$filesID = array($filesID);
			}

			foreach($filesID as $fileID)
			{
				$fileID = intval($fileID);
				if($fileID > 0)
				{
					$result[] = $this->externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic);
				}
			}
			$fields[$fieldName] = &$result;
			unset($result);
		}

		return true;
	}
	protected function tryExternalizeProductFileField(&$fields, $fieldName, $multiple = false, $dynamic = true)
	{
		if(!isset($fields[$fieldName]))
			return false;

		$productID = isset($fields['ID']) ? intval($fields['ID']) : 0;
		if(!$multiple)
		{
			if (!$dynamic)
			{
				$fileID = intval($fields[$fieldName]);
				if($fileID <= 0)
				{
					unset($fields[$fieldName]);
					return false;
				}

				$fields[$fieldName] = $this->externalizeProductFile($productID, $fieldName, 0, $fileID, $dynamic);
			}
			else
			{
				if (!(is_array(isset($fields[$fieldName]) && isset($fields[$fieldName]['VALUE_ID'])
					&& isset($fields[$fieldName]['VALUE']))))
				{
					unset($fields[$fieldName]);
					return false;
				}

				$valueID = intval($fields[$fieldName]['VALUE_ID']);
				$fileID = intval($fields[$fieldName]['VALUE']);
				if($fileID <= 0)
				{
					unset($fields[$fieldName]);
					return false;
				}

				$fields[$fieldName] = $this->externalizeProductFile($productID, $fieldName, $valueID, $fileID, $dynamic);
			}
		}
		else
		{
			if (!self::isIndexedArray($fields[$fieldName]))
			{
				unset($fields[$fieldName]);
				return false;
			}

			$result = array();
			foreach($fields[$fieldName] as $element)
			{
				if (!(isset($element['VALUE_ID']) && isset($element['VALUE'])))
					continue;

				$valueID = intval($element['VALUE_ID']);
				$fileID = intval($element['VALUE']);
				if($fileID > 0)
				{
					$result[] = $this->externalizeProductFile($productID, $fieldName, $valueID, $fileID, $dynamic);
				}
			}
			$fields[$fieldName] = &$result;
			unset($result);
		}

		return true;
	}
	protected function tryExternalizeWebDavElementField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		if(!$multiple)
		{
			$elementID = intval($fields[$fieldName]);
			$info = CCrmWebDavHelper::GetElementInfo($elementID, false);
			if(empty($info))
			{
				unset($fields[$fieldName]);
				return false;
			}
			else
			{
				$fields[$fieldName] = array(
					'id' => $elementID,
					'url' => isset($info['SHOW_URL']) ? $info['SHOW_URL'] : ''
				);

				return true;
			}
		}

		$result = array();
		$elementsID = $fields[$fieldName];
		if(is_array($elementsID))
		{
			foreach($elementsID as $elementID)
			{
				$elementID = intval($elementID);
				$info = CCrmWebDavHelper::GetElementInfo($elementID, false);
				if(empty($info))
				{
					continue;
				}

				$result[] = array(
					'id' => $elementID,
					'url' => isset($info['SHOW_URL']) ? $info['SHOW_URL'] : ''
				);
			}
		}

		if(!empty($result))
		{
			$fields[$fieldName] = &$result;
			unset($result);
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryExternalizeDiskFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$options = array(
			'OWNER_TYPE_ID' => $this->getOwnerTypeID(),
			'OWNER_ID' => $fields['ID'],
			'VIEW_PARAMS' => array('auth' => $this->server->getAuth()),
			'USE_ABSOLUTE_PATH' => true
		);

		if(!$multiple)
		{
			$fileID = intval($fields[$fieldName]);
			$info = DiskManager::getFileInfo($fileID, false, $options);
			if(empty($info))
			{
				unset($fields[$fieldName]);
				return false;
			}
			else
			{
				$fields[$fieldName] = array(
					'id' => $fileID,
					'url' => isset($info['VIEW_URL']) ? $info['VIEW_URL'] : ''
				);

				return true;
			}
		}

		$result = array();
		$fileIDs = $fields[$fieldName];
		if(is_array($fileIDs))
		{
			foreach($fileIDs as $fileID)
			{
				$info = DiskManager::getFileInfo($fileID, false, $options);
				if(empty($info))
				{
					continue;
				}

				$result[] = array(
					'id' => $fileID,
					'url' => isset($info['VIEW_URL']) ? $info['VIEW_URL'] : ''
				);
			}
		}

		if(!empty($result))
		{
			$fields[$fieldName] = &$result;
			unset($result);
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryExternalizeProductPropertyField(&$fields, &$fieldsInfo, $fieldName)
	{
		if(!is_array($fields) || !isset($fields[$fieldName]))
		{
			return;
		}

		$info = isset($fieldsInfo[$fieldName]) ? $fieldsInfo[$fieldName] : null;
		$value = isset($fields[$fieldName]) ? $fields[$fieldName] : null;

		if(!$info)
		{
			unset($fields[$fieldName]);
			return;
		}

		$attrs = isset($info['ATTRIBUTES']) ? $info['ATTRIBUTES'] : array();
		$isMultiple = in_array(CCrmFieldInfoAttr::Multiple, $attrs, true);
		$isDynamic = in_array(CCrmFieldInfoAttr::Dynamic, $attrs, true);

		$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
		$propertyType = isset($info['PROPERTY_TYPE']) ? $info['PROPERTY_TYPE'] : '';
		$userType = isset($info['USER_TYPE']) ? $info['USER_TYPE'] : '';
		if($fieldType === 'product_property' && $propertyType === 'S' && $userType === 'Date')
		{
			if (self::isIndexedArray($value))
			{
				$fields[$fieldName] = array();
				CTimeZone::Disable();
				foreach($value as $valueElement)
				{
					if (isset($valueElement['VALUE_ID']) && isset($valueElement['VALUE']))
					{
						$fields[$fieldName][] = array(
							'valueId' => $valueElement['VALUE_ID'],
							'value' => CRestUtil::ConvertDate($valueElement['VALUE'])
						);
					}
				}
				CTimeZone::Enable();
			}
			else
			{
				if (isset($value['VALUE_ID']) && isset($value['VALUE']))
				{
					CTimeZone::Disable();
					$fields[$fieldName] = array(
						'valueId' => $value['VALUE_ID'],
						'value' => CRestUtil::ConvertDate($value['VALUE'])
					);
					CTimeZone::Enable();
				}
				else
				{
					$fields[$fieldName] = null;
				}
			}
		}
		elseif($fieldType === 'product_property' && $propertyType === 'S' && $userType === 'DateTime')
		{
			if (self::isIndexedArray($value))
			{
				$fields[$fieldName] = array();
				foreach($value as $valueElement)
				{
					if (isset($valueElement['VALUE_ID']) && isset($valueElement['VALUE']))
					{
						$fields[$fieldName][] = array(
							'valueId' => $valueElement['VALUE_ID'],
							'value' => CRestUtil::ConvertDateTime($valueElement['VALUE'])
						);
					}
				}
			}
			else
			{
				if (isset($value['VALUE_ID']) && isset($value['VALUE']))
				{
					$fields[$fieldName] = array(
						'valueId' => $value['VALUE_ID'],
						'value' => CRestUtil::ConvertDateTime($value['VALUE'])
					);
				}
				else
				{
					$fields[$fieldName] = null;
				}
			}
		}
		elseif($fieldType === 'product_property' && $propertyType === 'F' && empty($userType))
		{
			$this->tryExternalizeProductFileField($fields, $fieldName, $isMultiple, $isDynamic);
		}
		else
		{
			if (self::isIndexedArray($value))
			{
				$fields[$fieldName] = array();
				foreach($value as $valueElement)
				{
					if (isset($valueElement['VALUE_ID']) && isset($valueElement['VALUE']))
					{
						$fields[$fieldName][] = array(
							'valueId' => $valueElement['VALUE_ID'],
							'value' => $valueElement['VALUE']
						);
					}
				}
			}
			else
			{
				if (isset($value['VALUE_ID']) && isset($value['VALUE']))
				{
					$fields[$fieldName] = array(
						'valueId' => $value['VALUE_ID'],
						'value' => $value['VALUE']
					);
				}
				else
				{
					$fields[$fieldName] = null;
				}
			}
		}
	}
	protected function internalizeFilterFields(&$filter, &$fieldsInfo)
	{
		if(!is_array($filter))
		{
			return;
		}

		foreach($filter as $k => $v)
		{
			$operationInfo =  CSqlUtil::GetFilterOperation($k);
			$fieldName = $operationInfo['FIELD'];

			$info = isset($fieldsInfo[$fieldName]) ? $fieldsInfo[$fieldName] : null;
			if(!$info)
			{
				unset($filter[$k]);
				continue;
			}

			$operation = substr($k, 0, strlen($k) - strlen($fieldName));
			if(isset($info['FORBIDDEN_FILTERS'])
				&& is_array($info['FORBIDDEN_FILTERS'])
				&& in_array($operation, $info['FORBIDDEN_FILTERS'], true))
			{
				unset($filter[$k]);
				continue;
			}

			$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
			if(($fieldType === 'crm_status' || $fieldType === 'crm_company' || $fieldType === 'crm_contact')
				&& ($operation === '%' || $operation === '%=' || $operation === '=%'))
			{
				//Prevent filtration by LIKE due to performance considerations
				$filter["={$fieldName}"] = $v;
				unset($filter[$k]);
				continue;
			}

			if($fieldType === 'datetime')
			{
				$filter[$k] = CRestUtil::unConvertDateTime($v);
			}
			elseif($fieldType === 'date')
			{
				$filter[$k] = CRestUtil::unConvertDate($v);
			}
		}

		CCrmEntityHelper::PrepareMultiFieldFilter($filter, array(), '=%', true);
	}
	protected static function isAssociativeArray($ary)
	{
		if(!is_array($ary))
		{
			return false;
		}

		$keys = array_keys($ary);
		foreach($keys as $k)
		{
			if (!is_int($k))
			{
				return true;
			}
		}
		return false;
	}
	protected static function isIndexedArray($ary)
	{
		if(!is_array($ary))
		{
			return false;
		}

		$keys = array_keys($ary);
		foreach($keys as $k)
		{
			if (!is_int($k))
			{
				return false;
			}
		}
		return true;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$errors[] = 'The operation "ADD" is not supported by this entity.';
		return false;
	}
	protected function innerGet($ID, &$errors)
	{
		$errors[] = 'The operation "GET" is not supported by this entity.';
		return false;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$errors[] = 'The operation "LIST" is not supported by this entity.';
		return null;
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$errors[] = 'The operation "UPDATE" is not supported by this entity.';
		return false;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$errors[] = 'The operation "DELETE" is not supported by this entity.';;
		return false;
	}
	protected function externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic = true)
	{
		$ownerTypeName = strtolower(CCrmOwnerType::ResolveName($ownerTypeID));
		if($ownerTypeName === '')
		{
			return '';
		}

		$handlerUrl = "/bitrix/components/bitrix/crm.{$ownerTypeName}.show/show_file.php";
		$showUrl = CComponentEngine::MakePathFromTemplate(
			"{$handlerUrl}?ownerId=#owner_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'owner_id' => $ownerID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		$downloadUrl = CComponentEngine::MakePathFromTemplate(
			"{$handlerUrl}?auth=#auth#&ownerId=#owner_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'auth' => $this->server ? $this->server->getAuth() : '',
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'owner_id' => $ownerID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		return array(
			'id' => $fileID,
			'showUrl' => $showUrl,
			'downloadUrl' => $downloadUrl
		);
	}
	protected function externalizeProductFile($productID, $fieldName, $valueID, $fileID, $dynamic = true)
	{
		$handlerUrl = "/bitrix/components/bitrix/crm.product.file/download.php";
		$showUrl = CComponentEngine::MakePathFromTemplate(
			"{$handlerUrl}?productId=#product_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'product_id' => $productID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		$downloadUrl = CComponentEngine::MakePathFromTemplate(
			"{$handlerUrl}?auth=#auth#&productId=#product_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'auth' => $this->server ? $this->server->getAuth() : '',
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'product_id' => $productID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		$result = array(
			'id' => $fileID,
			'showUrl' => $showUrl,
			'downloadUrl' => $downloadUrl
		);

		if ($dynamic)
			$result = array(
				'valueId' => $valueID,
				'value' => $result
			);

		return $result;
	}
	// WebDav -->
	protected function prepareWebDavIBlock($settings = null)
	{
		if($this->webdavIBlock !== null)
		{
			return $this->webdavIBlock;
		}

		if(!CModule::IncludeModule('webdav'))
		{
			throw new RestException('Could not load webdav module.');
		}

		if(!is_array($settings) || empty($settings))
		{
			$settings = $this->getWebDavSettings();
		}

		$iblockID = isset($settings['IBLOCK_ID']) ? $settings['IBLOCK_ID'] : 0;
		if($iblockID <= 0)
		{
			throw new RestException('Could not find webdav iblock.');
		}

		$sectionId = isset($settings['IBLOCK_SECTION_ID']) ? $settings['IBLOCK_SECTION_ID'] : 0;
		if($sectionId <= 0)
		{
			throw new RestException('Could not find webdav section.');
		}

		$user = CCrmSecurityHelper::GetCurrentUser();
		$this->webdavIBlock = new CWebDavIblock(
			$iblockID,
			'',
			array(
				'ROOT_SECTION_ID' => $sectionId,
				'DOCUMENT_TYPE' => array('webdav', 'CIBlockDocumentWebdavSocnet', 'iblock_'.$sectionId.'_user_'.$user->GetID())
			)
		);

		return $this->webdavIBlock;
	}
	protected function getWebDavSettings()
	{
		if($this->webdavSettings !== null)
		{
			return $this->webdavSettings;
		}

		if(!CModule::IncludeModule('webdav'))
		{
			throw new RestException('Could not load webdav module.');
		}

		$opt = COption::getOptionString('webdav', 'user_files', null);
		if($opt == null)
		{
			throw new RestException('Could not find webdav settings.');
		}

		$user = CCrmSecurityHelper::GetCurrentUser();

		$opt = unserialize($opt);
		$iblockID = intval($opt[CSite::GetDefSite()]['id']);
		$userSectionID = CWebDavIblock::getRootSectionIdForUser($iblockID, $user->GetID());
		if(!is_numeric($userSectionID) || $userSectionID <= 0)
		{
			throw new RestException('Could not find webdav section for user '.$user->GetLastName().'.');
		}

		return ($this->webdavSettings =
			array(
				'IBLOCK_ID' => $iblockID,
				'IBLOCK_SECTION_ID' => intval($userSectionID),
			)
		);
	}
	// <-- WebDav
	protected function getFieldsInfo()
	{
		throw new RestException('The method is not implemented.');
	}
	protected function sanitizeHtml($html)
	{
		$html = strval($html);
		if($html === '' || strpos($html, '<') === false)
		{
			return $html;
		}

		if($this->sanitizer === null)
		{
			$this->sanitizer = new CBXSanitizer();
			$this->sanitizer->ApplyDoubleEncode(false);
			$this->sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
		}

		return $this->sanitizer->SanitizeHtml($html);
	}
	protected function getIdentityFieldName()
	{
		return '';
	}
	protected function getIdentity(&$fields)
	{
		return 0;
	}
	protected static function getMultiFieldTypeIDs()
	{
		if(self::$MULTIFIELD_TYPE_IDS === null)
		{
			self::$MULTIFIELD_TYPE_IDS = array_keys(CCrmFieldMulti::GetEntityTypeInfos());
		}

		return self::$MULTIFIELD_TYPE_IDS;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return null;
	}
	protected function prepareListItemMultiFields(&$entityMap, $entityTypeID, $typeIDs)
	{
		$entityIDs = array_keys($entityMap);
		if(empty($entityIDs))
		{
			return;
		}

		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
		if($entityTypeName === '')
		{
			return;
		}

		$dbResult = CCrmFieldMulti::GetListEx(
			array(),
			array(
				'=ENTITY_ID' => $entityTypeName,
				'@ELEMENT_ID' => $entityIDs,
				'@TYPE_ID' => $typeIDs
			)
		);

		while($fm = $dbResult->Fetch())
		{
			$typeID = isset($fm['TYPE_ID']) ? $fm['TYPE_ID'] : '';
			if(!in_array($typeID, $typeIDs, true))
			{
				continue;
			}

			$entityID = isset($fm['ELEMENT_ID']) ? intval($fm['ELEMENT_ID']) : 0;
			if(!isset($entityMap[$entityID]))
			{
				continue;
			}

			$entity = &$entityMap[$entityID];
			if(!isset($entity['FM']))
			{
				$entity['FM'] = array();
			}

			if(!isset($entity['FM'][$typeID]))
			{
				$entity['FM'][$typeID] = array();
			}

			$entity['FM'][$typeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
			unset($entity);
		}
	}
	protected function prepareMultiFieldData($entityTypeID, $entityID, &$entityFields, $typeIDs = null)
	{
		$entityTypeID = intval($entityTypeID);
		$entityID = intval($entityID);

		if(!CCrmOwnerType::IsDefined($entityTypeID) || $entityID <= 0)
		{
			return;
		}

		$dbResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName($entityTypeID),
				'ELEMENT_ID' => $entityID
			)
		);

		if(!is_array($typeIDs) || empty($typeIDs))
		{
			$typeIDs = self::getMultiFieldTypeIDs();
		}

		$entityFields['FM'] = array();
		while($fm = $dbResult->Fetch())
		{
			$typeID = $fm['TYPE_ID'];
			if(!in_array($typeID, $typeIDs, true))
			{
				continue;
			}

			if(!isset($entityFields['FM'][$typeID]))
			{
				$entityFields['FM'][$typeID] = array();
			}

			$entityFields['FM'][$typeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}
	}

	protected static function isBizProcEnabled()
	{
		return !Bitrix24Manager::isEnabled() || Bitrix24Manager::isRestBizProcEnabled();
	}

	public static function processEvent($entityTypeID, array $arParams, array $arHandler)
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
		if($entityTypeName === '')
		{
			throw new RestException("The 'entityTypeName' is not specified");
		}

		$eventName = $arHandler['EVENT_NAME'];
		if(strpos(strtoupper($eventName), 'ONCRM'.$entityTypeName) !== 0)
		{
			throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		$action = substr($eventName, 5 + strlen($entityTypeName));
		if($action === false || $action === '')
		{
			throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		switch (strtoupper($action))
		{
			case 'ADD':
			case 'UPDATE':
			{
				$fields = isset($arParams[0]) ? $arParams[0] : null;
				$ID = is_array($fields) && isset($fields['ID']) ? (int)$fields['ID'] : 0;
			}
			break;
			case 'DELETE':
			{
				$ID = isset($arParams[0]) ? (int)$arParams[0] : 0;
			}
			break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		if($ID <= 0)
		{
			throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
		}
		return array('FIELDS' => array('ID' => $ID));
	}

	protected static function getDefaultEventSettings()
	{
		return array('category' => \Bitrix\Rest\Sqs::CATEGORY_CRM);
	}

	protected static function createEventInfo($moduleName, $eventName, array $callback)
	{
		return array($moduleName, $eventName, $callback, array('category' => \Bitrix\Rest\Sqs::CATEGORY_CRM));
	}
}

class CCrmEnumerationRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'int',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
			);
		}
		return $this->FIELDS_INFO;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$descriptions = null;

		$name = strtoupper($name);
		if($name === 'OWNERTYPE')
		{
			$descriptions = CCrmOwnerType::GetDescriptions(
				array(
					CCrmOwnerType::Lead,
					CCrmOwnerType::Deal,
					CCrmOwnerType::Contact,
					CCrmOwnerType::Company
				)
			);
		}
		elseif($name === 'CONTENTTYPE')
		{
			$descriptions = CCrmContentType::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYTYPE')
		{
			$descriptions = CCrmActivityType::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYPRIORITY')
		{
			$descriptions = CCrmActivityPriority::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYDIRECTION')
		{
			$descriptions = CCrmActivityDirection::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYNOTIFYTYPE')
		{
			$descriptions = CCrmActivityNotifyType::GetAllDescriptions();
		}

		if(!is_array($descriptions))
		{
			return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
		}

		$result = array();
		foreach($descriptions as $k => &$v)
		{
			$result[] = array('ID' => $k, 'NAME' => $v);
		}
		unset($v);
		return $result;
	}
}

class CCrmMultiFieldRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'int',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'TYPE_ID' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'VALUE' => array('TYPE' => 'string'),
				'VALUE_TYPE' => array('TYPE' => 'string')
			);
		}
		return $this->FIELDS_INFO;
	}
}

class CCrmCatalogRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmCatalog::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}

	protected function innerGet($ID, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCatalog::GetByID($ID);
		if(!is_array($result))
		{
			$errors[] = 'Catalog is not found.';
			return null;
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmCatalog::GetList($order, $filter, false, $navigation, $select, array('IS_EXTERNAL_CONTEXT' => true));
	}
}

class CCrmProductRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;

	private $userTypes = null;
	private $properties = null;

	protected function initializePropertiesInfo($catalogID)
	{
		if ($this->userTypes === null)
			$this->userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
		if ($this->properties === null)
			$this->properties = CCrmProductPropsHelper::GetProps($catalogID, $this->userTypes);
	}

	protected function getFieldsInfo()
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmProduct::GetFieldsInfo();
			$this->preparePropertyFieldsInfo($this->FIELDS_INFO);
		}
		return $this->FIELDS_INFO;
	}
	protected function preparePropertyFieldsInfo(&$fieldsInfo)
	{
		$catalogID = CCrmCatalog::GetDefaultID();
		if($catalogID <= 0)
			return;
		$this->initializePropertiesInfo($catalogID);
		foreach($this->properties as $propertyName => $propertyInfo)
		{
			$propertyType = $propertyInfo['PROPERTY_TYPE'];
			$info = array(
				'TYPE' => 'product_property',
				'PROPERTY_TYPE' => $propertyType,
				'USER_TYPE' => $propertyInfo['USER_TYPE'],
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Dynamic),
				'NAME' => $propertyInfo['NAME']
			);

			$isMultuple = isset($propertyInfo['MULTIPLE']) && $propertyInfo['MULTIPLE'] === 'Y';
			$isRequired = isset($propertyInfo['IS_REQUIRED']) && $propertyInfo['IS_REQUIRED'] === 'Y';
			if($isMultuple || $isRequired)
			{
				if($isMultuple)
					$info['ATTRIBUTES'][] = CCrmFieldInfoAttr::Multiple;
				if($isRequired)
					$info['ATTRIBUTES'][] = CCrmFieldInfoAttr::Required;
			}

			if ($propertyInfo['PROPERTY_TYPE'] === 'L')
			{
				$values = array();
				$resEnum = CIBlockProperty::GetPropertyEnum($propertyInfo['ID'], array('SORT' => 'ASC','ID' => 'ASC'));
				while($enumValue = $resEnum->Fetch())
				{
					$values[intval($enumValue['ID'])] = array(
						'ID' => $enumValue['ID'],
						'VALUE' => $enumValue['VALUE']
					);
				}
				$info['VALUES'] = $values;
			}

			$fieldsInfo[$propertyName] = $info;
		}
	}

	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!CCrmProduct::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$catalogID = intval(CCrmCatalog::EnsureDefaultExists());
		if($catalogID <= 0)
		{
			$errors[] = 'Default catalog is not exists.';
			return false;
		}

		// Product properties
		$this->initializePropertiesInfo($catalogID);
		$propertyValues = array();
		foreach ($this->properties as $propId => $property)
		{
			if (isset($fields[$propId]))
				$propertyValues[$property['ID']] = $fields[$propId];
			unset($fields[$propId]);
		}
		if(count($propertyValues) > 0)
			$fields['PROPERTY_VALUES'] = $propertyValues;

		$result = CCrmProduct::Add($fields);
		if(!is_int($result))
		{
			$errors[] = CCrmProduct::GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!CCrmProduct::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$catalogID = CCrmCatalog::GetDefaultID();
		if($catalogID <= 0)
		{
			$errors[] = 'Product is not found.';
			return null;
		}

		$filter = array('ID' => $ID, 'CATALOG_ID'=> $catalogID);
		$dbResult = CCrmProduct::GetList(array(), $filter, array('*'), array('nTopCount' => 1));
		$result = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Product is not found.';
			return null;
		}

		$this->initializePropertiesInfo($catalogID);
		$this->getProperties($catalogID, $result, array('PROPERTY_*'));

		return $result;
	}
	public function getList($order, $filter, $select, $start)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!CCrmProduct::CheckReadPermission(0))
		{
			throw new RestException('Access denied.');
		}

		$catalogID = CCrmCatalog::GetDefaultID();
		if($catalogID <= 0)
		{
			$result = array();
			$dbResult = new CDBResult();
			$dbResult->InitFromArray($result);
			return CCrmRestService::setNavData($result, $dbResult);
		}

		$navigation = CCrmRestService::getNavData($start);

		if(!is_array($order) || empty($order))
		{
			$order = array('sort' => 'asc');
		}

		if(!isset($navigation['bShowAll']))
		{
			$navigation['bShowAll'] = false;
		}

		$enableCatalogData = false;
		$catalogSelect = null;
		$priceSelect = null;
		$vatSelect = null;
		$propertiesSelect = array();

		$selectAll = false;
		if(is_array($select))
		{
			if(!empty($select))
			{
				// Remove '*' for get rid of inefficient construction of price data
				foreach($select as $k => $v)
				{
					if($v === '*')
					{
						$selectAll = true;
						unset($select[$k]);
					}
					else if (preg_match('/^PROPERTY_(\d+|\*)$/', $v))
					{
						$propertiesSelect[] = $v;
						unset($select[$k]);
					}
				}
			}

			if (!empty($propertiesSelect) && empty($select) && !$selectAll)
				$select = array('ID');

			if(empty($select))
			{
				$priceSelect = array('PRICE', 'CURRENCY_ID');
				$vatSelect = array('VAT_ID', 'VAT_INCLUDED', 'MEASURE');
			}
			else
			{
				$priceSelect = array();
				$vatSelect = array();

				$select = CCrmProduct::DistributeProductSelect($select, $priceSelect, $vatSelect);
			}

			$catalogSelect = array_merge($priceSelect, $vatSelect);
			$enableCatalogData = !empty($catalogSelect);
		}

		$filter['CATALOG_ID'] = $catalogID;
		$dbResult = CCrmProduct::GetList($order, $filter, $select, $navigation);
		if(!$enableCatalogData)
		{
			$result = array();
			$fieldsInfo = $this->getFieldsInfo();
			while($fields = $dbResult->Fetch())
			{
				$selectedFields = array();
				if (!empty($select))
				{
					$selectedFields['ID'] = $fields['ID'];
					foreach ($select as $k)
						$selectedFields[$k] = &$fields[$k];
					$fields = &$selectedFields;
				}
				unset($selectedFields);

				$this->getProperties($catalogID, $fields, $propertiesSelect);
				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
		}
		else
		{
			$itemMap = array();
			$itemIDs = array();
			while($fields = $dbResult->Fetch())
			{
				$selectedFields = array();
				if (!empty($select))
				{
					$selectedFields['ID'] = $fields['ID'];
					foreach ($select as $k)
						$selectedFields[$k] = &$fields[$k];
					$fields = &$selectedFields;
				}
				unset($selectedFields);

				foreach ($catalogSelect as $fieldName)
				{
					$fields[$fieldName] = null;
				}

				$itemID = isset($fields['ID']) ? intval($fields['ID']) : 0;
				if($itemID > 0)
				{
					$itemIDs[] = $itemID;
					$itemMap[$itemID] = $fields;
				}

			}
			CCrmProduct::ObtainPricesVats($itemMap, $itemIDs, $priceSelect, $vatSelect, true);

			$result = array_values($itemMap);
			$fieldsInfo = $this->getFieldsInfo();
			foreach($result as &$fields)
			{
				$this->getProperties($catalogID, $fields, $propertiesSelect);
				$this->externalizeFields($fields, $fieldsInfo);
			}
			unset($fields);
		}

		return CCrmRestService::setNavData($result, $dbResult);
	}
	public function getProperties($catalogID, &$fields, $propertiesSelect)
	{
		if ($catalogID <= 0)
			return;

		$productID = isset($fields['ID']) ? intval($fields['ID']) : 0;

		if ($productID <= 0)
			return;

		$this->initializePropertiesInfo($catalogID);

		$selectAll = false;
		foreach($propertiesSelect as $k => $v)
		{
			if($v === 'PROPERTY_*')
			{
				$selectAll = true;
				break;
			}
		}

		$propertyValues = array();
		if ($productID > 0 && count($this->properties) > 0)
		{
			$rsProperties = CIBlockElement::GetProperty(
				$catalogID,
				$productID,
				array(
					'sort' => 'asc',
					'id' => 'asc',
					'enum_sort' => 'asc',
					'value_id' => 'asc',
				),
				array(
					'ACTIVE' => 'Y',
					'EMPTY' => 'N',
					'CHECK_PERMISSIONS' => 'N'
				)
			);
			while ($property = $rsProperties->Fetch())
			{
				if (isset($property['USER_TYPE']) && !empty($property['USER_TYPE'])
					&& !array_key_exists($property['USER_TYPE'], $this->userTypes))
					continue;

				$propId = 'PROPERTY_' . $property['ID'];
				if(!isset($propertyValues[$propId]))
					$propertyValues[$propId] = array();
				$propertyValues[$propId][] =
					array('VALUE_ID' => $property['PROPERTY_VALUE_ID'], 'VALUE' => $property['VALUE']);
			}
			unset($rsProperties, $property, $propId);
		}
		foreach ($this->properties as $propId => $prop)
		{
			if ($selectAll || in_array($propId, $propertiesSelect, true))
			{
				$value = null;
				if (isset($propertyValues[$propId]))
				{
					if ($prop['MULTIPLE'] === 'Y')
						$value = $propertyValues[$propId];
					else if (count($propertyValues[$propId]) > 0)
						$value = end($propertyValues[$propId]);
				}
				$fields[$propId] = $value;
			}
		}
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!(CCrmProduct::CheckUpdatePermission($ID) && CCrmProduct::EnsureDefaultCatalogScope($ID)))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$catalogID = CCrmCatalog::GetDefaultID();
		if($catalogID <= 0)
		{
			$errors[] = 'Product catalog is not found.';
			return false;
		}

		if(!CCrmProduct::Exists($ID))
		{
			$errors[] = 'Product is not found';
			return false;
		}

		// Product properties
		$this->initializePropertiesInfo($catalogID);
		$propertyValues = array();
		foreach ($this->properties as $propId => $property)
		{
			if (isset($fields[$propId]))
				$propertyValues[$property['ID']] = $fields[$propId];
			unset($fields[$propId]);
		}
		if(count($propertyValues) > 0)
		{
			$fields['PROPERTY_VALUES'] = $propertyValues;
			$rsProperties = CIBlockElement::GetProperty(
				$catalogID,
				$ID,
				'sort', 'asc',
				array('ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N')
			);
			while($property = $rsProperties->Fetch())
			{
				if (isset($property['USER_TYPE']) && !empty($property['USER_TYPE'])
					&& !array_key_exists($property['USER_TYPE'], $this->userTypes))
					continue;

				if($property['PROPERTY_TYPE'] !== 'F' && !array_key_exists($property['ID'], $propertyValues))
				{
					if(!array_key_exists($property['ID'], $fields['PROPERTY_VALUES']))
						$fields['PROPERTY_VALUES'][$property['ID']] = array();

					$fields['PROPERTY_VALUES'][$property['ID']][$property['PROPERTY_VALUE_ID']] = array(
						'VALUE' => $property['VALUE'],
						'DESCRIPTION' => $property['DESCRIPTION']
					);
				}
			}
		}

		$result = CCrmProduct::Update($ID, $fields);
		if($result !== true)
		{
			$errors[] = CCrmProduct::GetLastError();
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!(CCrmProduct::CheckDeletePermission($ID) && CCrmProduct::EnsureDefaultCatalogScope($ID)))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProduct::Delete($ID);
		if($result !== true)
		{
			$errors[] = CCrmProduct::GetLastError();
		}
		return $result;
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmProductRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmProductAdd'] = self::createEventInfo('catalog', 'OnProductAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmProductUpdate'] = self::createEventInfo('catalog', 'OnProductUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmProductDelete'] = self::createEventInfo('iblock', 'OnAfterIBlockElementDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch (strtolower($eventName))
		{
			case 'oncrmproductadd':
			case 'oncrmproductupdate':
			{
				$ID = isset($arParams[0]) ? (int)$arParams[0] : 0;

				if($ID <= 0)
				{
					throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
				}

				$fields = CCrmProduct::GetByID($ID);
				$catalogID = is_array($fields) && isset($fields['CATALOG_ID']) ? (int)$fields['CATALOG_ID'] : 0;
				if($catalogID !== CCrmCatalog::GetDefaultID())
				{
					throw new RestException("Outside CRM product event is detected");
				}
				return array('FIELDS' => array('ID' => $ID));
			}
			break;
			case 'oncrmproductdelete':
			{
				$fields = isset($arParams[0]) && is_array($arParams[0]) ? $arParams[0] : array();
				$ID = isset($fields['ID']) ? (int)$fields['ID'] : 0;

				if($ID <= 0)
				{
					throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
				}

				$catalogID = isset($fields['IBLOCK_ID']) ? (int)$fields['IBLOCK_ID'] : 0;
				if($catalogID !== CCrmCatalog::GetDefaultID())
				{
					throw new RestException("Outside CRM product event is detected");
				}
				return array('FIELDS' => array('ID' => $ID));
			}
			break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}
	}
}

class CCrmProductPropertyRestProxy extends CCrmRestProxyBase
{
	private $TYPES_INFO = null;
	private $FIELDS_INFO = null;
	private $SETTINGS_FIELDS_INFO = null;
	private $ENUMERATION_FIELDS_INFO = null;

	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'IBLOCK_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'XML_ID' => array(
					'TYPE' => 'string'
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'ACTIVE' => array(
					'TYPE' => 'char'
				),
				'IS_REQUIRED' => array(
					'TYPE' => 'char'
				),
				'SORT' => array(
					'TYPE' => 'integer'
				),
				'PROPERTY_TYPE' => array(
					'TYPE' => 'char',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required, CCrmFieldInfoAttr::Immutable)
				),
				'MULTIPLE' => array(
					'TYPE' => 'char'
				),
				'DEFAULT_VALUE' => array(
					'TYPE' => 'object'
				),
				'ROW_COUNT' => array(
					'TYPE' => 'integer'
				),
				'COL_COUNT' => array(
					'TYPE' => 'integer'
				),
				'FILE_TYPE' => array(
					'TYPE' => 'string'
				),
				'LINK_IBLOCK_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'USER_TYPE' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Immutable)
				),
				'USER_TYPE_SETTINGS' => array(
					'TYPE' => 'object'
				),
				'VALUES' => array(
					'TYPE' => 'product_property_enum_element',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
				)
			);
		}

		return $this->FIELDS_INFO;
	}

	protected function getSettingsFieldsInfo($propertyType, $userType)
	{
		$fieldsInfo = array();

		if(!$this->SETTINGS_FIELDS_INFO)
		{
			$this->SETTINGS_FIELDS_INFO = array(
				'S' => array(
					'HTML' => array(
						'HEIGHT' => array(
							'TYPE' => 'integer'/*,
							'DEFAULT_VALUE' => 200*/
						)
					)
				),
				'E' => array(
					'Elist' => array(
						'SIZE' => array(
							'TYPE' => 'integer'/*,
							'DEFAULT_VALUE' => 1*/
						),
						'WIDTH' => array(
							'TYPE' => 'integer'/*,
							'DEFAULT_VALUE' => 0*/
						),
						'GROUP' => array(
							'TYPE' => 'char'/*,
							'DEFAULT_VALUE' => 'N'*/
						),
						'MULTIPLE' => array(
							'TYPE' => 'char'/*,
							'DEFAULT_VALUE' => 'N'*/
						)
					)
				),
				'N' => array(
					'Sequence' => array(
						'WRITE' => array(
							'TYPE' => 'char'/*,
							'DEFAULT_VALUE' => 'N'*/
						),
						'CURRENT_VALUE' => array(
							'TYPE' => 'integer'/*,
							'DEFAULT_VALUE' => '1'*/
						)
					)
				),
			);
		}

		if (isset($this->SETTINGS_FIELDS_INFO[$propertyType])
			&& isset($this->SETTINGS_FIELDS_INFO[$propertyType][$userType]))
		{
			$fieldsInfo = $this->SETTINGS_FIELDS_INFO[$propertyType][$userType];
		}

		return self::prepareFields($fieldsInfo);
	}

	protected function getEnumerationFieldsInfo()
	{
		if(!$this->ENUMERATION_FIELDS_INFO)
		{
			$this->ENUMERATION_FIELDS_INFO = array(
				'ID' => array('TYPE' => 'integer'),
				'VALUE' => array('TYPE' => 'string'),
				'XML_ID' => array('TYPE' => 'string'),
				'SORT' => array('TYPE' => 'integer'),
				'DEF' => array('TYPE' => 'char')
			);
		}

		return self::prepareFields($this->ENUMERATION_FIELDS_INFO);
	}

	protected function getTypesInfo()
	{
		$typesInfo = array();

		if(!$this->TYPES_INFO)
		{
			if(!CModule::IncludeModule('iblock'))
			{
				throw new RestException('Could not load iblock module.');
			}

			$descriptions = CCrmProductPropsHelper::GetPropsTypesDescriptions();
			$typesInfo = array(
				array('PROPERTY_TYPE' => 'S', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['S']),
				array('PROPERTY_TYPE' => 'N', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['N']),
				array('PROPERTY_TYPE' => 'L', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['L']),
				array('PROPERTY_TYPE' => 'F', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['F']),
				/*array('PROPERTY_TYPE' => 'G', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['G']),*/
				array('PROPERTY_TYPE' => 'E', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['E'])
			);
			$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
			if (is_array($userTypes))
			{
				foreach ($userTypes as $propertyInfo)
				{
					$typesInfo[] = array(
						'PROPERTY_TYPE' => $propertyInfo['PROPERTY_TYPE'],
						'USER_TYPE' => $propertyInfo['USER_TYPE'],
						'DESCRIPTION' => $propertyInfo['DESCRIPTION']
					);
				}
			}

			$this->TYPES_INFO = $typesInfo;
		}

		return $this->TYPES_INFO;
	}

	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		/** @var CCrmPerms $userPerms */
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}
		
		$iblockId = intval(CCrmCatalog::EnsureDefaultExists());

		$userTypeSettings = array();
		if (isset($fields['USER_TYPE_SETTINGS']) && is_array($fields['USER_TYPE_SETTINGS']))
			foreach ($fields['USER_TYPE_SETTINGS'] as $key => $value)
				$userTypeSettings[strtolower($key)] = $value;

		$arFields = array(
			'ACTIVE' => isset($fields['ACTIVE']) ? ($fields['ACTIVE'] === 'Y' ? 'Y' : 'N') : 'Y',
			'IBLOCK_ID' => $iblockId,
			'PROPERTY_TYPE' => $fields['PROPERTY_TYPE'],
			'USER_TYPE' => isset($fields['USER_TYPE']) ? $fields['USER_TYPE'] : '',
			'LINK_IBLOCK_ID' => ($fields['PROPERTY_TYPE'] === 'E' || $fields['PROPERTY_TYPE'] === 'G') ? $iblockId : 0,
			'NAME' => $fields['NAME'],
			'SORT' => isset($fields['SORT']) ? $fields['SORT'] : 500,
			'CODE' => '',
			'MULTIPLE' => isset($fields['MULTIPLE']) ? ($fields['MULTIPLE'] === 'Y' ? 'Y' : 'N') : 'N',
			'IS_REQUIRED' => isset($fields['IS_REQUIRED']) ? ($fields['IS_REQUIRED'] === 'Y' ? 'Y' : 'N') : 'N',
			'SEARCHABLE' => 'N',
			'FILTRABLE' => 'N',
			'WITH_DESCRIPTION' => '',
			'MULTIPLE_CNT' => isset($fields['MULTIPLE_CNT']) ? $fields['MULTIPLE_CNT'] : 0,
			'HINT' => '',
			'ROW_COUNT' => isset($fields['ROW_COUNT']) ? $fields['ROW_COUNT'] : 1,
			'COL_COUNT' => isset($fields['COL_COUNT']) ? $fields['COL_COUNT'] : 30,
			'DEFAULT_VALUE' => isset($fields['DEFAULT_VALUE']) ? $fields['DEFAULT_VALUE'] : null,
			'LIST_TYPE' => 'L',
			'USER_TYPE_SETTINGS' => $userTypeSettings,
			'FILE_TYPE' => isset($fields['FILE_TYPE']) ? $fields['FILE_TYPE'] : '',
			'XML_ID' => isset($fields['XML_ID']) ? $fields['XML_ID'] : ''
		);

		if ($fields['PROPERTY_TYPE'] === 'L' && isset($fields['VALUES']) && is_array($fields['VALUES']))
		{
			$values = array();

			$newKey = 0;
			foreach ($fields['VALUES'] as $key => $value)
			{
				if (!is_array($value) || !isset($value['VALUE']) || '' == trim($value['VALUE']))
					continue;
				$values[(0 < intval($key) ? $key : 'n'.$newKey)] = array(
					'ID' => (0 < intval($key) ? $key : 'n'.$newKey),
					'VALUE' => strval($value['VALUE']),
					'XML_ID' => (isset($value['XML_ID']) ? strval($value['XML_ID']) : ''),
					'SORT' => (isset($value['SORT']) ? intval($value['SORT']) : 500),
					'DEF' => (isset($value['DEF']) ? ($value['DEF'] === 'Y' ? 'Y' : 'N') : 'N')
				);
				$newKey++;
			}

			$arFields['VALUES'] = $values;
		}

		$property = new CIBlockProperty;
		$result = $property->Add($arFields);

		if (intval($result) <= 0)
		{
			if (!empty($property->LAST_ERROR))
				$errors[] = $property->LAST_ERROR;
			else if($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
		}

		return $result;
	}

	protected function innerGet($id, &$errors)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		/** @var CCrmPerms $userPerms */
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = false;
		$iblockId = intval(CCrmCatalog::EnsureDefaultExists());
		$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
		$res = CIBlockProperty::GetByID($id, $iblockId);
		if (is_object($res))
			$result = $res->Fetch();
		unset($res);
		if(!is_array($result)
			|| (isset($result['USER_TYPE']) && !empty($result['USER_TYPE'])
				&& !array_key_exists($result['USER_TYPE'], $userTypes)))
		{
			$errors[] = 'Not found';
			return false;
		}

		$userTypeSettings = array();
		if (isset($result['USER_TYPE_SETTINGS']) && is_array($result['USER_TYPE_SETTINGS']))
		{
			foreach ($result['USER_TYPE_SETTINGS'] as $key => $value)
				$userTypeSettings[strtoupper($key)] = $value;
			$result['USER_TYPE_SETTINGS'] = $userTypeSettings;
		}

		return $result;
	}

	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		/** @var CCrmPerms $userPerms */
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');

		$filter['IBLOCK_ID'] = intval(CCrmCatalog::EnsureDefaultExists());
		$filter['CHECK_PERMISSIONS'] = 'N';
		$res = CIBlockProperty::GetList($order, $filter);
		$result = array();
		while ($row = $res->Fetch())
		{
			if ($row['PROPERTY_TYPE'] !== 'G' && isset($row['USER_TYPE'])
				&& (empty($row['USER_TYPE']) || array_key_exists($row['USER_TYPE'], $userTypes)))
			{
				$values = null;
				if ($row['PROPERTY_TYPE'] === 'L')
				{
					$values = array();
					$resEnum = CIBlockProperty::GetPropertyEnum($row['ID'], array('SORT' => 'ASC','ID' => 'ASC'));
					while($enumValue = $resEnum->Fetch())
					{
						$values[intval($enumValue['ID'])] = array(
							'ID' => $enumValue['ID'],
							'VALUE' => $enumValue['VALUE'],
							'XML_ID' => $enumValue['XML_ID'],
							'SORT' => $enumValue['SORT'],
							'DEF' => $enumValue['DEF']
						);
					}
				}
				$row['VALUES'] = $values;
				$result[] = $row;
			}
		}

		return $result;
	}

	protected function innerUpdate($id, &$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		/** @var CCrmPerms $userPerms */
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$iblockId = intval(CCrmCatalog::EnsureDefaultExists());
		$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
		$res = CIBlockProperty::GetByID($id, $iblockId);
		$prop = false;
		if (is_object($res))
			$prop = $res->Fetch();
		unset($res);
		if(!is_array($prop)
			|| (isset($prop['USER_TYPE']) && !empty($prop['USER_TYPE'])
				&& !array_key_exists($prop['USER_TYPE'], $userTypes)))
		{
			$errors[] = 'Not found';
			return false;
		}

		$fields['IBLOCK_ID'] = $iblockId;
		$fields['PROPERTY_TYPE'] = $prop['PROPERTY_TYPE'];
		$fields['USER_TYPE'] = $prop['USER_TYPE'];

		if (isset($fields['USER_TYPE_SETTINGS']) && is_array($fields['USER_TYPE_SETTINGS']))
		{
			$userTypeSettings = array();
			foreach ($fields['USER_TYPE_SETTINGS'] as $key => $value)
				$userTypeSettings[strtolower($key)] = $value;
			$fields['USER_TYPE_SETTINGS'] = $userTypeSettings;
			unset($userTypeSettings);
		}

		if ($prop['PROPERTY_TYPE'] === 'L' && isset($fields['VALUES']) && is_array($fields['VALUES']))
		{
			$values = array();

			$newKey = 0;
			foreach ($fields['VALUES'] as $key => $value)
			{
				if (!is_array($value) || !isset($value['VALUE']) || '' == trim($value['VALUE']))
					continue;
				$values[(0 < intval($key) ? $key : 'n'.$newKey)] = array(
					'ID' => (0 < intval($key) ? $key : 'n'.$newKey),
					'VALUE' => strval($value['VALUE']),
					'XML_ID' => (isset($value['XML_ID']) ? strval($value['XML_ID']) : ''),
					'SORT' => (isset($value['SORT']) ? intval($value['SORT']) : 500),
					'DEF' => (isset($value['DEF']) ? ($value['DEF'] === 'Y' ? 'Y' : 'N') : 'N')
				);
				$newKey++;
			}
			$fields['VALUES'] = $values;
			unset($values);
		}

		$property = new CIBlockProperty;
		$result = $property->Update($id, $fields);

		if (!$result)
		{
			if (!empty($property->LAST_ERROR))
				$errors[] = $property->LAST_ERROR;
			else if($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
		}

		return $result;
	}

	protected function innerDelete($id, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		/** @var CCrmPerms $userPerms */
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$iblockId = intval(CCrmCatalog::EnsureDefaultExists());
		$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
		$res = CIBlockProperty::GetByID($id, $iblockId);
		$result = false;
		if (is_object($res))
			$result = $res->Fetch();
		unset($res);
		if(!is_array($result)
			|| (isset($result['USER_TYPE']) && !empty($result['USER_TYPE'])
				&& !array_key_exists($result['USER_TYPE'], $userTypes)))
		{
			$errors[] = 'Not found';
			return false;
		}

		if(!CIBlockProperty::Delete($id))
		{
			if($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
			else
				$errors[] = 'Error on deleting product property.';
			return false;
		}

		return true;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'PROPERTY')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				return self::getFields();
			}
			elseif($nameSuffix === 'TYPES')
			{
				return $this->getTypesInfo();
			}
			else if($nameSuffix === 'SETTINGS_FIELDS')
			{
				$propertyType = $userType = '';
				foreach ($arParams as $name => $value)
				{
					switch (strtolower($name))
					{
						case 'propertytype':
							$propertyType = strval($value);
							break;
						case 'usertype':
							$userType = strval($value);
							break;
					}
				}
				if($propertyType === '')
				{
					throw new RestException("Parameter 'propertyType' is not specified or empty.");
				}
				if($userType === '')
				{
					throw new RestException("Parameter 'userType' is not specified or empty.");
				}

				return $this->getSettingsFieldsInfo($propertyType, $userType);
			}
			else if($nameSuffix === 'ENUMERATION_FIELDS')
			{
				return $this->getEnumerationFieldsInfo();
			}
			else if(in_array($nameSuffix, array('ADD', 'GET', 'LIST', 'UPDATE', 'DELETE'), true))
			{
				return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
			}
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
}

class CCrmProductSectionRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmProductSection::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmProduct::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductSection::Add($fields);
		if(!(is_int($result) && $result > 0))
		{
			$errors[] = CCrmProductSection::GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductSection::GetByID($ID);
		if(!is_array($result))
		{
			$errors[] = 'Product section is not found.';
			return null;
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmProductSection::GetList($order, $filter, $select, $navigation);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmProduct::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductSection::Update($ID, $fields);
		if($result !== true)
		{
			$errors[] = CCrmProductSection::GetLastError();
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmProduct::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductSection::Delete($ID);
		if($result !== true)
		{
			$errors[] = CCrmProductSection::GetLastError();
		}
		return $result;
	}
}

class CCrmProductRowRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmProductRow::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$ownerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;
		$ownerType = isset($fields['OWNER_TYPE']) ? $fields['OWNER_TYPE'] : '';

		if($ownerID <= 0 || $ownerType === '')
		{
			if ($ownerID <= 0)
			{
				$errors[] = 'The field OWNER_ID is required.';
			}

			if ($ownerType === '')
			{
				$errors[] = 'The field OWNER_TYPE is required.';
			}
			return false;
		}

		if(!CCrmAuthorizationHelper::CheckCreatePermission(
			CCrmProductRow::ResolveOwnerTypeName($ownerType)))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductRow::Add($fields, true, true);
		if(!is_int($result))
		{
			$errors[] = CCrmProductRow::GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		$result = CCrmProductRow::GetByID($ID);
		if(!is_array($result))
		{
			$errors[] = "Product Row not found";
		}

		if(!CCrmAuthorizationHelper::CheckReadPermission(
			CCrmProductRow::ResolveOwnerTypeName($result['OWNER_TYPE']),
			$result['OWNER_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$ownerID = isset($filter['OWNER_ID']) ? intval($filter['OWNER_ID']) : 0;
		$ownerType = isset($filter['OWNER_TYPE']) ? $filter['OWNER_TYPE'] : '';

		if($ownerID <= 0 || $ownerType === '')
		{
			if ($ownerID <= 0)
			{
				$errors[] = 'The field OWNER_ID is required in filer.';
			}

			if ($ownerType === '')
			{
				$errors[] = 'The field OWNER_TYPE is required in filer.';
			}
			return false;
		}

		if($ownerType === 'I')
		{
			//Crutch for Invoices
			if(!CCrmInvoice::CheckReadPermission($ownerID))
			{
				$errors[] = 'Access denied.';
				return false;
			}

			$result = array();
			$productRows = CCrmInvoice::GetProductRows($ownerID);
			foreach($productRows as $productRow)
			{
				$price = isset($productRow['PRICE']) ? $productRow['PRICE'] : 0.0;
				$discountSum = isset($productRow['DISCOUNT_PRICE']) ? $productRow['DISCOUNT_PRICE'] : 0.0;
				$taxRate = isset($productRow['VAT_RATE']) ? $productRow['VAT_RATE'] * 100 : 0.0;

				$exclusivePrice = CCrmProductRow::CalculateExclusivePrice($price, $taxRate);
				$discountRate = \Bitrix\Crm\Discount::calculateDiscountRate(($exclusivePrice + $discountSum), $exclusivePrice);

				$result[] = array(
					'ID' => $productRow['ID'],
					'OWNER_ID' => $ownerID,
					'OWNER_TYPE' => 'I',
					'PRODUCT_ID' => isset($productRow['PRODUCT_ID']) ? $productRow['PRODUCT_ID'] : 0,
					'PRODUCT_NAME' => isset($productRow['PRODUCT_NAME']) ? $productRow['PRODUCT_NAME'] : '',
					'PRICE' => $price,
					'QUANTITY' => isset($productRow['QUANTITY']) ? $productRow['QUANTITY'] : 0,
					'DISCOUNT_TYPE_ID' => \Bitrix\Crm\Discount::MONETARY,
					'DISCOUNT_RATE' => $discountRate,
					'DISCOUNT_SUM' => $discountSum,
					'TAX_RATE' => $taxRate,
					'TAX_INCLUDED' => 'Y',
					'MEASURE_CODE' => isset($productRow['MEASURE_CODE']) ? $productRow['MEASURE_CODE'] : '',
					'MEASURE_NAME' => isset($productRow['MEASURE_NAME']) ? $productRow['MEASURE_NAME'] : '',
					'CUSTOMIZED' => isset($productRow['CUSTOM_PRICE']) ? $productRow['CUSTOM_PRICE'] : 'N',
				);
			}
			return $result;
		}

		if(!CCrmAuthorizationHelper::CheckReadPermission(
			CCrmProductRow::ResolveOwnerTypeName($ownerType),
			$ownerID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmProductRow::GetList($order, $filter, false, $navigation, $select, array('IS_EXTERNAL_CONTEXT' => true));
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$entity = CCrmProductRow::GetByID($ID);
		if(!is_array($entity))
		{
			$errors[] = "Product Row is not found";
			return false;
		}

		if(!CCrmAuthorizationHelper::CheckUpdatePermission(
			CCrmProductRow::ResolveOwnerTypeName($entity['OWNER_TYPE']),
			$entity['OWNER_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		// The fields OWNER_ID and OWNER_TYPE can not be changed.
		if(isset($fields['OWNER_ID']))
		{
			unset($fields['OWNER_ID']);
		}

		if(isset($fields['OWNER_TYPE']))
		{
			unset($fields['OWNER_TYPE']);
		}

		$result = CCrmProductRow::Update($ID, $fields, true, true);
		if($result !== true)
		{
			$errors[] = CCrmProductRow::GetLastError();
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$entity = CCrmProductRow::GetByID($ID);
		if(!is_array($entity))
		{
			$errors[] = "Product Row is not found";
			return false;
		}

		if(!CCrmAuthorizationHelper::CheckDeletePermission(
			CCrmProductRow::ResolveOwnerTypeName($entity['OWNER_TYPE']),
			$entity['OWNER_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductRow::Delete($ID, true, true);
		if($result !== true)
		{
			$errors[] = CCrmProductRow::GetLastError();
		}
		return $result;
	}

	public function prepareForSave(&$fields)
	{
		$fieldsInfo = $this->getFieldsInfo();
		$this->internalizeFields($fields, $fieldsInfo);
	}
}

class CCrmLeadRestProxy extends CCrmRestProxyBase
{
	private static $ENTITY = null;
	private $FIELDS_INFO = null;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Lead;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmLead(true);
		}

		return self::$ENTITY;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmLead::GetFieldsInfo();
			self::prepareMultiFieldsInfo($this->FIELDS_INFO);
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmLead::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmLead::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$options = array();
		if(is_array($params) && isset($params['REGISTER_SONET_EVENT']))
		{
			$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
		}
		$result = $entity->Add($fields, true, $options);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Lead,
				$result,
				CCrmBizProcEventType::Create,
				$errors
			);
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmLead::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmLead::GetListEx(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$result['FM'] = array();
		$fmResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName(CCrmOwnerType::Lead),
				'ELEMENT_ID' => $ID
			)
		);

		while($fm = $fmResult->Fetch())
		{
			$fmTypeID = $fm['TYPE_ID'];
			if(!isset($result['FM'][$fmTypeID]))
			{
				$result['FM'][$fmTypeID] = array();
			}

			$result['FM'][$fmTypeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmLead::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = isset($ufData['VALUE']) ? $ufData['VALUE'] : '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmLead::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$options = array('IS_EXTERNAL_CONTEXT' => true);
		if(is_array($order))
		{
			if(isset($order['STATUS_ID']))
			{
				$order['STATUS_SORT'] = $order['STATUS_ID'];
				unset($order['STATUS_ID']);

				$options['FIELD_OPTIONS'] = array('ADDITIONAL_FIELDS' => array('STATUS_SORT'));
			}
		}

		return CCrmLead::GetListEx($order, $filter, false, $navigation, $select, $options);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmLead::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmLead::Exists($ID))
		{
			$errors[] = 'Lead is not found';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$compare = true;
		$options = array();
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}

			if(isset($params['REGISTER_SONET_EVENT']))
			{
				$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
			}
		}

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Lead,
				$ID,
				CCrmBizProcEventType::Edit,
				$errors
			);
		}

		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmLead::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID, array('CHECK_DEPENDENCIES' => true));
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}

	public function getProductRows($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!CCrmLead::CheckReadPermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmLead::LoadProductRows($ID);
	}
	public function setProductRows($ID, $rows)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($rows))
		{
			throw new RestException('The parameter rows must be array.');
		}

		if(!CCrmLead::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		if(!CCrmLead::Exists($ID))
		{
			throw new RestException('Not found.');
		}

		$proxy = new CCrmProductRowRestProxy();

		$actualRows = array();
		$qty = count($rows);
		for($i = 0; $i < $qty; $i++)
		{
			$row = $rows[$i];
			if(!is_array($row))
			{
				continue;
			}

			$proxy->prepareForSave($row);
			if(isset($row['OWNER_TYPE']))
			{
				unset($row['OWNER_TYPE']);
			}

			if(isset($row['OWNER_ID']))
			{
				unset($row['OWNER_ID']);
			}

			$actualRows[] = $row;
		}

		return CCrmLead::SaveProductRows($ID, $actualRows, true, true, true);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'PRODUCTROWS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');

			if($nameSuffix === 'GET')
			{
				return $this->getProductRows($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$rows = $this->resolveArrayParam($arParams, 'rows');
				return $this->setProductRows($ID, $rows);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmLeadRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmLeadAdd'] = self::createEventInfo('crm', 'OnAfterCrmLeadAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmLeadUpdate'] = self::createEventInfo('crm', 'OnAfterCrmLeadUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmLeadDelete'] = self::createEventInfo('crm', 'OnAfterCrmLeadDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEvent(CCrmOwnerType::Lead, $arParams, $arHandler);
	}
}

class CCrmDealRestProxy extends CCrmRestProxyBase
{
	private static $ENTITY = null;
	private $FIELDS_INFO = null;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Deal;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmDeal(true);
		}

		return self::$ENTITY;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmDeal::GetFieldsInfo();
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmDeal::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmDeal::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$options = array();
		if(is_array($params) && isset($params['REGISTER_SONET_EVENT']))
		{
			$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
		}
		$result = $entity->Add($fields, true, $options);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Deal,
				$result,
				CCrmBizProcEventType::Create,
				$errors
			);
		}

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmDeal::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmDeal::GetListEx(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmDeal::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = isset($ufData['VALUE']) ? $ufData['VALUE'] : '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmDeal::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$options = array('IS_EXTERNAL_CONTEXT' => true);
		if(is_array($order))
		{
			if(isset($order['STAGE_ID']))
			{
				$order['STAGE_SORT'] = $order['STAGE_ID'];
				unset($order['STAGE_ID']);

				$options['FIELD_OPTIONS'] = array('ADDITIONAL_FIELDS' => array('STAGE_SORT'));
			}
		}

		return CCrmDeal::GetListEx($order, $filter, false, $navigation, $select, $options);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmDeal::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmDeal::Exists($ID))
		{
			$errors[] = 'Deal is not found';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$compare = true;
		$options = array();
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}

			if(isset($params['REGISTER_SONET_EVENT']))
			{
				$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
			}
		}

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Deal,
				$ID,
				CCrmBizProcEventType::Edit,
				$errors
			);
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmDeal::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}

	public function getProductRows($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!CCrmDeal::CheckReadPermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmDeal::LoadProductRows($ID);
	}
	public function setProductRows($ID, $rows)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($rows))
		{
			throw new RestException('The parameter rows must be array.');
		}

		if(!CCrmDeal::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		if(!CCrmDeal::Exists($ID))
		{
			throw new RestException('Not found.');
		}

		$proxy = new CCrmProductRowRestProxy();

		$actualRows = array();
		$qty = count($rows);
		for($i = 0; $i < $qty; $i++)
		{
			$row = $rows[$i];
			if(!is_array($row))
			{
				continue;
			}

			$proxy->prepareForSave($row);
			if(isset($row['OWNER_TYPE']))
			{
				unset($row['OWNER_TYPE']);
			}

			if(isset($row['OWNER_ID']))
			{
				unset($row['OWNER_ID']);
			}

			$actualRows[] = $row;
		}

		return CCrmDeal::SaveProductRows($ID, $actualRows, true, true, true);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'PRODUCTROWS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');

			if($nameSuffix === 'GET')
			{
				return $this->getProductRows($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$rows = $this->resolveArrayParam($arParams, 'rows');
				return $this->setProductRows($ID, $rows);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmDealRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmDealAdd'] = self::createEventInfo('crm', 'OnAfterCrmDealAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmDealUpdate'] = self::createEventInfo('crm', 'OnAfterCrmDealUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmDealDelete'] = self::createEventInfo('crm', 'OnAfterCrmDealDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEvent(CCrmOwnerType::Deal, $arParams, $arHandler);
	}
}

class CCrmCompanyRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Company;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmCompany::GetFieldsInfo();
			self::prepareMultiFieldsInfo($this->FIELDS_INFO);
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmCompany::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmCompany(true);
		}

		return self::$ENTITY;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmCompany::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$options = array();
		if(is_array($params) && isset($params['REGISTER_SONET_EVENT']))
		{
			$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
		}
		$result = $entity->Add($fields, true, $options);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Company,
				$result,
				CCrmBizProcEventType::Create,
				$errors
			);
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmCompany::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmCompany::GetListEx(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$result['FM'] = array();
		$fmResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName(CCrmOwnerType::Company),
				'ELEMENT_ID' => $ID
			)
		);

		while($fm = $fmResult->Fetch())
		{
			$fmTypeID = $fm['TYPE_ID'];
			if(!isset($result['FM'][$fmTypeID]))
			{
				$result['FM'][$fmTypeID] = array();
			}

			$result['FM'][$fmTypeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmCompany::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = isset($ufData['VALUE']) ? $ufData['VALUE'] : '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmCompany::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmCompany::GetListEx(
			$order,
			$filter,
			false,
			$navigation,
			$select,
			array('IS_EXTERNAL_CONTEXT' => true)
		);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmCompany::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmCompany::Exists($ID))
		{
			$errors[] = 'Company is not found';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$compare = true;
		$options = array();
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}

			if(isset($params['REGISTER_SONET_EVENT']))
			{
				$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
			}
		}

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Company,
				$ID,
				CCrmBizProcEventType::Edit,
				$errors
			);
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmCompany::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmCompanyRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmCompanyAdd'] = self::createEventInfo('crm', 'OnAfterCrmCompanyAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmCompanyUpdate'] = self::createEventInfo('crm', 'OnAfterCrmCompanyUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmCompanyDelete'] = self::createEventInfo('crm', 'OnAfterCrmCompanyDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEvent(CCrmOwnerType::Company, $arParams, $arHandler);
	}
}

class CCrmContactRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;

	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Contact;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmContact::GetFieldsInfo();
			self::prepareMultiFieldsInfo($this->FIELDS_INFO);
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmContact::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmContact(true);
		}

		return self::$ENTITY;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmContact::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$options = array();
		if(is_array($params) && isset($params['REGISTER_SONET_EVENT']))
		{
			$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
		}
		$result = $entity->Add($fields, true, $options);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Contact,
				$result,
				CCrmBizProcEventType::Create,
				$errors
			);
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmContact::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmContact::GetListEx(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$result['FM'] = array();
		$fmResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
				'ELEMENT_ID' => $ID
			)
		);

		while($fm = $fmResult->Fetch())
		{
			$fmTypeID = $fm['TYPE_ID'];
			if(!isset($result['FM'][$fmTypeID]))
			{
				$result['FM'][$fmTypeID] = array();
			}

			$result['FM'][$fmTypeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmContact::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = isset($ufData['VALUE']) ? $ufData['VALUE'] : '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmContact::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmContact::GetListEx(
			$order,
			$filter,
			false,
			$navigation,
			$select,
			array('IS_EXTERNAL_CONTEXT' => true)
		);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmContact::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmContact::Exists($ID))
		{
			$errors[] = 'Contact is not found';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$compare = true;
		$options = array();
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}

			if(isset($params['REGISTER_SONET_EVENT']))
			{
				$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
			}
		}

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Contact,
				$ID,
				CCrmBizProcEventType::Edit,
				$errors
			);
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmContact::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmContactRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmContactAdd'] = self::createEventInfo('crm', 'OnAfterCrmContactAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmContactUpdate'] = self::createEventInfo('crm', 'OnAfterCrmContactUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmContactDelete'] = self::createEventInfo('crm', 'OnAfterCrmContactDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEvent(CCrmOwnerType::Contact, $arParams, $arHandler);
	}
}

class CCrmCurrencyRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private $LOC_FIELDS_INFO = null;
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmCurrency::GetFieldsInfo();
			$this->FIELDS_INFO['LANG'] = array(
				'TYPE' => 'currency_localization',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
			);
		}
		return $this->FIELDS_INFO;
	}
	public function getLocalizationFieldsInfo()
	{
		if(!$this->LOC_FIELDS_INFO)
		{
			$this->LOC_FIELDS_INFO = CCrmCurrency::GetCurrencyLocalizationFieldsInfo();
		}
		return $this->LOC_FIELDS_INFO;
	}
	public function isValidID($ID)
	{
		return is_string($ID) && $ID !== '';
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmCurrency::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCurrency::Add($fields);
		if($result === false)
		{
			$errors[] = CCrmCurrency::GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmCurrency::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCurrency::GetByID($ID);
		if(is_array($result))
		{
			return $result;
		}

		$errors[] = 'Not found';
		return false;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmCurrency::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmCurrency::GetList($order);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmCurrency::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmCurrency::IsExists($ID))
		{
			$errors[] = 'Currency is not found';
			return false;
		}

		$result = CCrmCurrency::Update($ID, $fields);
		if($result !== true)
		{
			$errors[] = CCrmCurrency::GetLastError();
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmCurrency::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCurrency::Delete($ID);
		if($result !== true)
		{
			$errors[] = CCrmCurrency::GetLastError();
		}

		return $result;
	}
	protected function resolveEntityID(&$arParams)
	{
		return isset($arParams['ID'])
			? strtoupper($arParams['ID'])
			: (isset($arParams['id']) ? strtoupper($arParams['id']) : '');
	}
	protected function checkEntityID($ID)
	{
		return is_string($ID) && $ID !== '';
	}
	public function getLocalizations($ID)
	{
		$ID = strval($ID);
		if($ID === '')
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!CCrmCurrency::CheckReadPermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmCurrency::GetCurrencyLocalizations($ID);
	}
	public function setLocalizations($ID, $localizations)
	{
		$ID = strval($ID);
		if($ID === '')
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($localizations) || empty($localizations))
		{
			return false;
		}

		if(!CCrmCurrency::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmCurrency::SetCurrencyLocalizations($ID, $localizations);
	}
	public function deleteLocalizations($ID, $langs)
	{
		$ID = strval($ID);
		if($ID === '')
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($langs) || empty($langs))
		{
			return false;
		}

		if(!CCrmCurrency::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmCurrency::DeleteCurrencyLocalizations($ID, $langs);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'LOCALIZATIONS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				$fildsInfo = $this->getLocalizationFieldsInfo();
				return parent::prepareFields($fildsInfo);
			}
			elseif($nameSuffix === 'GET')
			{
				return $this->getLocalizations($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$localizations = $this->resolveArrayParam($arParams, 'localizations');
				return $this->setLocalizations($ID, $localizations);
			}
			elseif($nameSuffix === 'DELETE')
			{
				$ID = $this->resolveEntityID($arParams);
				$lids = $this->resolveArrayParam($arParams, 'lids');
				return $this->deleteLocalizations($ID, $lids);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmCurrencyRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmCurrencyAdd'] = self::createEventInfo('currency', 'OnCurrencyAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmCurrencyUpdate'] = self::createEventInfo('currency', 'OnCurrencyUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmCurrencyDelete'] = self::createEventInfo('currency', 'OnCurrencyDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch (strtolower($eventName))
		{
			case 'oncrmcurrencyadd':
			case 'oncrmcurrencyupdate':
			case 'oncrmcurrencydelete':
			{
				$ID = isset($arParams[0]) && is_string($arParams[0]) ? $arParams[0] : '';
			}
			break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		if($ID === '')
		{
			throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
		}
		return array('FIELDS' => array('ID' => $ID));
	}
}

class CCrmStatusRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY_TYPES = null;

	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmStatus::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmStatus::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entityID = isset($fields['ENTITY_ID']) ? $fields['ENTITY_ID'] : '';
		$statusID = isset($fields['STATUS_ID']) ? $fields['STATUS_ID'] : '';
		if($entityID === '' || $statusID === '')
		{
			if($entityID === '')
			{
				$errors[] = 'The field ENTITY_ID is required.';
			}

			if($statusID === '')
			{
				$errors[] = 'The field STATUS_ID is required.';
			}

			return false;
		}

		$entityTypes = self::prepareEntityTypes();
		if(!isset($entityTypes[$entityID]))
		{
			$errors[] = 'Specified entity type is not supported.';
			return false;
		}

		$fields['SYSTEM'] = 'N';
		$entity = new CCrmStatus($entityID);
		$result = $entity->Add($fields, true);
		if($result === false)
		{
			$errors[] = $entity->GetLastError();
		}
		elseif(isset($fields['EXTRA']))
		{
			self::saveExtra($fields);
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmStatus::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbResult = CCrmStatus::GetList(array(), array('ID' => $ID));
		$result = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'CRM Status is not found.';
			return null;
		}

		self::prepareExtra($result);
		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmStatus::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!is_array($order))
		{
			$order = array();
		}

		if(empty($order))
		{
			$order['sort'] = 'asc';
		}

		$results = array();
		$dbResult = CCrmStatus::GetList($order, $filter);
		if(is_object($dbResult))
		{
			while($item = $dbResult->Fetch())
			{
				self::prepareExtra($item);
				$results[] = $item;
			}
		}
		return $results;
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmStatus::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbResult = CCrmStatus::GetList(array(), array('ID' => $ID));
		$currentFields = $dbResult ? $dbResult->Fetch() : null;
		if(!is_array($currentFields))
		{
			$errors[] = 'Status is not found.';
			return false;
		}

		$result = true;
		if(isset($fields['NAME']) || isset($fields['SORT']) || isset($fields['STATUS_ID']))
		{
			if(!isset($fields['NAME']))
			{
				$fields['NAME'] = $currentFields['NAME'];
			}

			if(!isset($fields['SORT']))
			{
				$fields['SORT'] = $currentFields['SORT'];
			}
			$entity = new CCrmStatus($currentFields['ENTITY_ID']);
			$result = $entity->Update($ID, $fields);
			if($result === false)
			{
				$errors[] = $entity->GetLastError();
			}
		}
		if($result && isset($fields['EXTRA']))
		{
			$fields['ENTITY_ID'] = $currentFields['ENTITY_ID'];
			if(!isset($fields['STATUS_ID']))
			{
				$fields['STATUS_ID'] = $currentFields['STATUS_ID'];
			}
			self::saveExtra($fields);
		}

		return $result !== false;

	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmStatus::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbResult = CCrmStatus::GetList(array(), array('ID' => $ID));
		$currentFields = $dbResult ? $dbResult->Fetch() : null;
		if(!is_array($currentFields))
		{
			$errors[] = 'Status is not found.';
			return false;
		}

		$isSystem = isset($currentFields['SYSTEM']) && $currentFields['SYSTEM'] === 'Y';
		$forced = is_array($params) && isset($params['FORCED']) && $params['FORCED'] === 'Y';

		if($isSystem && !$forced)
		{
			$errors[] = 'CRM System Status can be deleted only if parameter FORCED is specified and equal to "Y".';
			return false;
		}

		$entity = new CCrmStatus($currentFields['ENTITY_ID']);
		$result = $entity->Delete($ID);
		if($result === false)
		{
			$errors[] = $entity->GetLastError();
		}
		return $result !== false;
	}
	private static function prepareExtra(array &$fields)
	{
		$statusID = isset($fields['STATUS_ID']) ? $fields['STATUS_ID'] : '';
		if($statusID === '')
		{
			return null;
		}

		$result = null;
		$colorScheme = null;
		$entityID = isset($fields['ENTITY_ID']) ? $fields['ENTITY_ID'] : '';
		if($entityID === 'DEAL_STAGE')
		{
			$result = array('SEMANTICS' => CCrmDeal::GetStageSemantics($statusID));
			$colorScheme = DealStageColorScheme::getCurrent();
		}
		elseif($entityID === 'STATUS')
		{
			$result = array('SEMANTICS' => CCrmLead::GetStatusSemantics($statusID));
			$colorScheme = LeadStatusColorScheme::getCurrent();
		}
		elseif($entityID === 'QUOTE_STATUS')
		{
			$result = array('SEMANTICS' => CCrmQuote::GetStatusSemantics($statusID));
			$colorScheme = QuoteStatusColorScheme::getCurrent();
		}

		if(is_array($result))
		{
			if($colorScheme !== null && $colorScheme->isPersistent())
			{
				$element = $colorScheme->getElementByName($statusID);
				if($element !== null)
				{
					$result['COLOR'] = $element->getColor();
				}
			}
			$fields['EXTRA'] = $result;
		}
	}
	private static function saveExtra(array $fields)
	{
		$extra = isset($fields['EXTRA']) && is_array($fields['EXTRA']) ? $fields['EXTRA'] : null;
		if(empty($extra) || !isset($extra['COLOR']) || !is_string($extra['COLOR']))
		{
			return;
		}
		$color = $extra['COLOR'];

		$statusID = isset($fields['STATUS_ID']) ? $fields['STATUS_ID'] : '';
		if($statusID === '')
		{
			return;
		}

		$colorScheme = null;
		$entityID = isset($fields['ENTITY_ID']) ? $fields['ENTITY_ID'] : '';
		if($entityID === 'DEAL_STAGE')
		{
			$colorScheme = DealStageColorScheme::getCurrent();
		}
		elseif($entityID === 'STATUS')
		{
			$colorScheme = LeadStatusColorScheme::getCurrent();
		}
		elseif($entityID === 'QUOTE_STATUS')
		{
			$colorScheme = QuoteStatusColorScheme::getCurrent();
		}

		if($colorScheme !== null)
		{
			$isChanged = false;

			$element = $colorScheme->getElementByName($statusID);
			if($element !== null)
			{
				if($color === '')
				{
					$color = $colorScheme->getDefaultColor($statusID);
				}

				if($element->getColor() !== $color)
				{
					$element->setColor($color);
					$isChanged = true;
				}
			}
			else
			{
				$colorScheme->addElement(new PhaseColorSchemeElement($statusID, $color));
				$isChanged = true;
			}

			if($isChanged)
			{
				$colorScheme->save();
			}
		}
	}
	private static function prepareEntityTypes()
	{
		if(!self::$ENTITY_TYPES)
		{
			self::$ENTITY_TYPES = CCrmStatus::GetEntityTypes();
		}

		return self::$ENTITY_TYPES;
	}
	public function getEntityTypes()
	{
		return array_values(self::prepareEntityTypes());
	}
	public function getEntityItems($entityID)
	{
		if(!CCrmStatus::CheckReadPermission(0))
		{
			throw new RestException('Access denied.');
		}

		if($entityID === '')
		{
			throw new RestException('The parameter entityId is not defined or invalid.');
		}

		//return CCrmStatus::GetStatusList($entityID);
		$dbResult = CCrmStatus::GetList(array('sort' => 'asc'), array('ENTITY_ID' => strtoupper($entityID)));
		if(!$dbResult)
		{
			return array();
		}

		$result = array();
		while($fields = $dbResult->Fetch())
		{
			$result[] = array(
				'NAME' => $fields['NAME'],
				'SORT' => intval($fields['SORT']),
				'STATUS_ID' => $fields['STATUS_ID']
			);
		}

		return $result;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'ENTITY')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'TYPES')
			{
				return $this->getEntityTypes();
			}
			elseif($nameSuffix === 'ITEMS')
			{
				return $this->getEntityItems($this->resolveRelationID($arParams, 'entity'));
			}
		}
		elseif($name === 'EXTRA')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				return CCrmStatus::GetFieldExtraTypeInfo();
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
}

class CCrmStatusInvoiceRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;

	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmStatusInvoice::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CCrmStatus::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$statusInvoice = new CCrmStatusInvoice('INVOICE_STATUS');
		$result = $statusInvoice->Add($fields);
		if($result === false)
		{
			if ($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
			else
				$errors[] = 'Error on creating status.';
		}
		elseif(is_string($result))
		{
			$result = ord($result);
		}

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmStatus::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$crmStatus = new CCrmStatus('INVOICE_STATUS');
		$result = $crmStatus->getStatusById($ID);
		if($result === false)
		{
			$errors[] = 'Status is not found.';
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmStatus::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmStatusInvoice::GetList($order, $filter, $select);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CCrmStatus::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$statusInvoice = new CCrmStatusInvoice('INVOICE_STATUS');
		$currentFields = $statusInvoice->getStatusById($ID);
		if(!is_array($currentFields))
		{
			$errors[] = 'Status is not found.';
			return false;
		}

		$result = $statusInvoice->Update($ID, $fields);
		if($result === false)
		{
			if ($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
			else
				$errors[] = 'Error on updating status.';
		}

		return $result !== false;

	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CCrmStatus::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$statusInvoice = new CCrmStatusInvoice('INVOICE_STATUS');
		$currentFields = $statusInvoice->getStatusById($ID);
		if(!is_array($currentFields))
		{
			$errors[] = 'Status is not found.';
			return false;
		}

		$statusId = intval($ID);
		if ($statusId === ($statusId & 0xFF) && $statusId >= 65 && $statusId <= 90)
		{
			$statusId = chr($statusId);
			if (isset($currentFields['SYSTEM']) && $currentFields['SYSTEM'] === 'Y')
			{
				$errors[] = "Can't delete system status";
				return false;
			}
		}
		unset($statusId);

		$result = $statusInvoice->Delete($ID);
		if($result === false)
		{
			if ($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
			else
				$errors[] = 'Error on deleting status.';
		}
		return $result !== false;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'STATUS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');

			switch ($nameSuffix)
			{
				case 'FIELDS':
				case 'ADD':
				case 'GET':
				case 'LIST':
				case 'UPDATE':
				case 'DELETE':
					return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
					break;
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
}

class CCrmActivityRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private $COMM_FIELDS_INFO = null;
	public function getOwnerTypeID()
	{
		return CCrmOwnerType::Activity;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmActivity::GetFieldsInfo();
			$this->FIELDS_INFO['COMMUNICATIONS'] = array(
				'TYPE' => 'crm_activity_communication',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
			);

			$storageTypeID =  CCrmActivity::GetDefaultStorageTypeID();
			if($storageTypeID === StorageType::Disk)
			{
				$this->FIELDS_INFO['FILES'] = array(
					'TYPE' => 'diskfile',
					'ALIAS' => 'WEBDAV_ELEMENTS',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple),
				);
				$this->FIELDS_INFO['WEBDAV_ELEMENTS'] = array(
					'TYPE' => 'diskfile',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Deprecated, CCrmFieldInfoAttr::Multiple)
				);
			}
			else
			{
				$this->FIELDS_INFO['WEBDAV_ELEMENTS'] = array(
					'TYPE' => 'webdav',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
				);
			}
			$this->FIELDS_INFO['BINDINGS'] = array(
				'TYPE' => 'crm_activity_binding',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple, CCrmFieldInfoAttr::ReadOnly)
			);
		}
		return $this->FIELDS_INFO;
	}
	protected function getCommunicationFieldsInfo()
	{
		if(!$this->COMM_FIELDS_INFO)
		{
			$this->COMM_FIELDS_INFO = CCrmActivity::GetCommunicationFieldsInfo();
		}
		return $this->COMM_FIELDS_INFO;
	}
	protected function prepareCommunications($ownerTypeID, $ownerID, $typeID, &$communications, &$bindings)
	{
		foreach($communications as $k => &$v)
		{
			$commEntityTypeID = $v['ENTITY_TYPE_ID'] ? intval($v['ENTITY_TYPE_ID']) : 0;
			$commEntityID = $v['ENTITY_ID'] ? intval($v['ENTITY_ID']) : 0;
			$commValue = $v['VALUE'] ? $v['VALUE'] : '';
			$commType = $v['TYPE'] ? $v['TYPE'] : '';

			if($commValue !== '' && ($commEntityTypeID <= 0 || $commEntityID <= 0))
			{
				// Push owner info into communication (if ommited)
				$commEntityTypeID = $v['ENTITY_TYPE_ID'] = $ownerTypeID;
				$commEntityID = $v['ENTITY_ID'] = $ownerID;
			}

			if($commEntityTypeID <= 0 || $commEntityID <= 0 || $commValue === '')
			{
				unset($communications[$k]);
				continue;
			}

			if($commType === '')
			{
				if($typeID === CCrmActivityType::Call)
				{
					$v['TYPE'] = 'PHONE';
				}
				elseif($typeID === CCrmActivityType::Email)
				{
					$v['TYPE'] = 'EMAIL';
				}
			}
			elseif(($typeID === CCrmActivityType::Call && $commType !== 'PHONE')
				|| ($typeID === CCrmActivityType::Email && $commType !== 'EMAIL'))
			{
				// Invalid communication type is specified
				unset($communications[$k]);
				continue;
			}

			$bindings["{$commEntityTypeID}_{$commEntityID}"] = array(
				'OWNER_TYPE_ID' => $commEntityTypeID,
				'OWNER_ID' => $commEntityID
			);
		}
		unset($v);
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? intval($fields['OWNER_TYPE_ID']) : 0;
		$ownerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;

		$bindings = array();
		if($ownerTypeID > 0 && $ownerID > 0)
		{
			$bindings["{$ownerTypeID}_{$ownerID}"] = array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID
			);
		}

		$responsibleID = isset($fields['RESPONSIBLE_ID']) ? intval($fields['RESPONSIBLE_ID']) : 0;
		if($responsibleID <= 0 && $ownerTypeID > 0 && $ownerID > 0)
		{
			$fields['RESPONSIBLE_ID'] = $responsibleID = CCrmOwnerType::GetResponsibleID($ownerTypeID, $ownerID);
		}

		if($responsibleID <= 0)
		{
			$responsibleID = CCrmSecurityHelper::GetCurrentUserID();
		}

		if($responsibleID <= 0)
		{
			$errors[] = 'The field RESPONSIBLE_ID is not defined or invalid.';
			return false;
		}

		$typeID = isset($fields['TYPE_ID']) ? intval($fields['TYPE_ID']) : CCrmActivityType::Undefined;
		if(!CCrmActivityType::IsDefined($typeID))
		{
			$errors[] = 'The field TYPE_ID is not defined or invalid.';
			return false;
		}

		if(!in_array($typeID, array(CCrmActivityType::Call, CCrmActivityType::Meeting, CCrmActivityType::Email), true))
		{
			$errors[] = 'The activity type "'.CCrmActivityType::ResolveDescription($typeID).' is not supported in current context".';
			return false;
		}

		$description = isset($fields['DESCRIPTION']) ? $fields['DESCRIPTION'] : '';
		$descriptionType = isset($fields['DESCRIPTION_TYPE']) ? intval($fields['DESCRIPTION_TYPE']) : CCrmContentType::PlainText;
		if($description !== '' && CCrmActivity::AddEmailSignature($description, $descriptionType))
		{
			$fields['DESCRIPTION'] = $description;
		}

		$direction = isset($fields['DIRECTION']) ? intval($fields['DIRECTION']) : CCrmActivityDirection::Undefined;
		$completed = isset($fields['COMPLETED']) && strtoupper($fields['COMPLETED']) === 'Y';
		$communications = isset($fields['COMMUNICATIONS']) && is_array($fields['COMMUNICATIONS'])
			? $fields['COMMUNICATIONS'] : array();

		$this->prepareCommunications($ownerTypeID, $ownerID, $typeID, $communications, $bindings);

		if(empty($communications))
		{
			$errors[] = 'The field COMMUNICATIONS is not defined or invalid.';
			return false;
		}

		if(($typeID === CCrmActivityType::Call || $typeID === CCrmActivityType::Meeting)
			&& count($communications) > 1)
		{
			$errors[] = 'The only one communication is allowed for activity of specified type.';
			return false;
		}

		if(empty($bindings))
		{
			$errors[] = 'Could not build binding. Please ensure that owner info and communications are defined correctly.';
			return false;
		}

		foreach($bindings as &$binding)
		{
			if(!CCrmActivity::CheckUpdatePermission($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']))
			{
				$errors[] = 'Access denied.';
				return false;
			}
		}
		unset($binding);

		$fields['BINDINGS'] = array_values($bindings);
		$fields['COMMUNICATIONS'] = $communications;
		$storageTypeID = $fields['STORAGE_TYPE_ID'] = CCrmActivity::GetDefaultStorageTypeID();
		$fields['STORAGE_ELEMENT_IDS'] = array();

		if($storageTypeID === StorageType::WebDav)
		{
			$webdavElements = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
				? $fields['WEBDAV_ELEMENTS'] : array();

			foreach($webdavElements as &$element)
			{
				$elementID = isset($element['ELEMENT_ID']) ? intval($element['ELEMENT_ID']) : 0;
				if($elementID > 0)
				{
					$fields['STORAGE_ELEMENT_IDS'][] = $elementID;
				}
			}
			unset($element);
		}
		elseif($storageTypeID === StorageType::Disk)
		{
			$diskFiles = isset($fields['FILES']) && is_array($fields['FILES'])
				? $fields['FILES'] : array();

			if(empty($diskFiles))
			{
				//For backward compatibility only
				$diskFiles = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
					? $fields['WEBDAV_ELEMENTS'] : array();
			}

			foreach($diskFiles as &$fileInfo)
			{
				$fileID = isset($fileInfo['FILE_ID']) ? (int)$fileInfo['FILE_ID'] : 0;
				if($fileID > 0)
				{
					$fields['STORAGE_ELEMENT_IDS'][] = $fileID;
				}
			}
			unset($fileInfo);
		}

		if(!($ID = CCrmActivity::Add($fields)))
		{
			$errors[] = CCrmActivity::GetLastErrorMessage();
			return false;
		}

		CCrmActivity::SaveCommunications($ID, $communications, $fields, false, false);

		if($completed
			&& $typeID === CCrmActivityType::Email
			&& $direction === CCrmActivityDirection::Outgoing)
		{
			$sendErrors = array();
			if(!CCrmActivityEmailSender::TrySendEmail($ID, $fields, $sendErrors))
			{
				foreach($sendErrors as &$error)
				{
					$code = $error['CODE'];
					if($code === CCrmActivityEmailSender::ERR_CANT_LOAD_SUBSCRIBE)
					{
						$errors[] = 'Email send error. Failed to load module "subscribe".';
					}
					elseif($code === CCrmActivityEmailSender::ERR_INVALID_DATA)
					{
						$errors[] = 'Email send error. Invalid data.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_INVALID_EMAIL)
					{
						$errors[] = 'Email send error. Invalid email is specified.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_FIND_EMAIL_FROM)
					{
						$errors[] = 'Email send error. "From" is not found.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_FIND_EMAIL_TO)
					{
						$errors[] = 'Email send error. "To" is not found.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_ADD_POSTING)
					{
						$errors[] = 'Email send error. Failed to add posting. Please see details below.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_SAVE_POSTING_FILE)
					{
						$errors[] = 'Email send error. Failed to save posting file. Please see details below.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_UPDATE_ACTIVITY)
					{
						$errors[] = 'Email send error. Failed to update activity.';
					}
					else
					{
						$errors[] = 'Email send error. General error.';
					}

					$msg = isset($error['MESSAGE']) ? $error['MESSAGE'] : '';
					if($msg !== '')
					{
						$errors[] = $msg;
					}
				}
				unset($error);
				return false;
			}
		}
		return $ID;
	}
	protected function innerGet($ID, &$errors)
	{
		// Permissions will be checked by default
		$dbResult = CCrmActivity::GetList(array(), array('ID' => $ID));
		if($dbResult)
		{
			return $dbResult->Fetch();
		}

		$errors[] = 'Activity is not found.';
		return null;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!is_array($order))
		{
			$order = array();
		}

		if(empty($order))
		{
			$order['START_TIME'] = 'ASC';
		}

		if(!is_array($select))
		{
			$select = array();
		}

		//Proces storage aliases
		if(array_search('STORAGE_ELEMENT_IDS', $select, true) === false
			&& (array_search('FILES', $select, true) !== false || array_search('WEBDAV_ELEMENTS', $select, true) !== false))
		{
			$select[] = 'STORAGE_ELEMENT_IDS';
		}

		// Permissions will be checked by default
		return CCrmActivity::GetList($order, $filter, false, $navigation, $select, array('IS_EXTERNAL_CONTEXT' => true));
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$currentFields = CCrmActivity::GetByID($ID);
		CCrmActivity::PrepareStorageElementIDs($currentFields);

		if(!is_array($currentFields))
		{
			$errors[] = 'Activity is not found.';
			return false;
		}

		$typeID = intval($currentFields['TYPE_ID']);
		$currentOwnerID = intval($currentFields['OWNER_ID']);
		$currentOwnerTypeID = intval($currentFields['OWNER_TYPE_ID']);

		if(!CCrmActivity::CheckUpdatePermission($currentOwnerTypeID, $currentOwnerID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$ownerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;
		if($ownerID <= 0)
		{
			$ownerID = $currentOwnerID;
		}

		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? intval($fields['OWNER_TYPE_ID']) : 0;
		if($ownerTypeID <= 0)
		{
			$ownerTypeID = $currentOwnerTypeID;
		}

		if(($ownerTypeID !== $currentOwnerTypeID || $ownerID !== $currentOwnerID)
			&& !CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$communications = isset($fields['COMMUNICATIONS']) && is_array($fields['COMMUNICATIONS'])
			? $fields['COMMUNICATIONS'] : null;

		if(is_array($communications))
		{
			$bindings = array();
			if($ownerTypeID > 0 && $ownerID > 0)
			{
				$bindings["{$ownerTypeID}_{$ownerID}"] = array(
					'OWNER_TYPE_ID' => $ownerTypeID,
					'OWNER_ID' => $ownerID
				);
			}

			$this->prepareCommunications($ownerTypeID, $ownerID, $typeID, $communications, $bindings);

			if(empty($communications))
			{
				$errors[] = 'The field COMMUNICATIONS is not defined or invalid.';
				return false;
			}

			$fields['BINDINGS'] = array_values($bindings);
			$fields['COMMUNICATIONS'] = $communications;
		}


		$storageTypeID = $fields['STORAGE_TYPE_ID'] = CCrmActivity::GetDefaultStorageTypeID();
		$fields['STORAGE_ELEMENT_IDS'] = array();
		if($storageTypeID === StorageType::WebDav)
		{
			$webdavElements = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
				? $fields['WEBDAV_ELEMENTS'] : array();

			$prevStorageElementIDs = isset($currentFields['STORAGE_ELEMENT_IDS']) ? $currentFields['STORAGE_ELEMENT_IDS'] : array();
			$oldStorageElementIDs = array();
			foreach($webdavElements as &$element)
			{
				$elementID = isset($element['ELEMENT_ID']) ? intval($element['ELEMENT_ID']) : 0;
				if($elementID > 0)
				{
					$fields['STORAGE_ELEMENT_IDS'][] = $elementID;
				}

				$oldElementID = isset($element['OLD_ELEMENT_ID']) ? intval($element['OLD_ELEMENT_ID']) : 0;
				if($oldElementID > 0
					&& ($elementID > 0 || (isset($element['DELETE']) && $element['DELETE'] === true)))
				{
					if(in_array($oldElementID, $prevStorageElementIDs))
					{
						$oldStorageElementIDs[] = $oldElementID;
					}
				}
			}
			unset($element);
		}
		else if($storageTypeID === StorageType::Disk)
		{
			$diskFiles = isset($fields['FILES']) && is_array($fields['FILES'])
				? $fields['FILES'] : array();

			if(empty($diskFiles))
			{
				//For backward compatibility only
				$diskFiles = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
					? $fields['WEBDAV_ELEMENTS'] : array();
			}

			foreach($diskFiles as &$fileInfo)
			{
				$fileID = isset($fileInfo['FILE_ID']) ? (int)$fileInfo['FILE_ID'] : 0;
				if($fileID > 0)
				{
					$fields['STORAGE_ELEMENT_IDS'][] = $fileID;
				}
			}
			unset($fileInfo);
		}

		$regEvent = true;
		if(is_array($params) && isset($params['REGISTER_HISTORY_EVENT']))
		{
			$regEvent = strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
		}

		$result = CCrmActivity::Update($ID, $fields, false, $regEvent, array());
		if($result === false)
		{
			$errors[] = CCrmActivity::GetLastErrorMessage();
		}
		else
		{
			if(is_array($communications))
			{
				CCrmActivity::SaveCommunications($ID, $communications, $fields, false, false);
			}

			if(!empty($oldStorageElementIDs))
			{
				$webdavIBlock = $this->prepareWebDavIBlock();
				foreach($oldStorageElementIDs as $elementID)
				{
					$webdavIBlock->Delete(array('element_id' => $elementID));
				}
			}
		}

		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$currentFields = CCrmActivity::GetByID($ID);
		if(!is_array($currentFields))
		{
			$errors[] = 'Activity is not found.';
			return false;
		}

		if(!CCrmActivity::CheckDeletePermission(
			$currentFields['OWNER_TYPE_ID'], $currentFields['OWNER_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmActivity::Delete($ID, false, true, array());
		if($result === false)
		{
			$errors[] = CCrmActivity::GetLastErrorMessage();
		}

		return $result;
	}
	protected function externalizeFields(&$fields, &$fieldsInfo)
	{
		$storageTypeID = isset($fields['STORAGE_TYPE_ID'])
			? $fields['STORAGE_TYPE_ID'] : CCrmActivity::GetDefaultStorageTypeID();

		if(isset($fields['STORAGE_ELEMENT_IDS']))
		{
			CCrmActivity::PrepareStorageElementIDs($fields);
			if($storageTypeID === Bitrix\Crm\Integration\StorageType::Disk)
			{
				$fields['FILES'] = $fields['STORAGE_ELEMENT_IDS'];
			}
			elseif($storageTypeID === Bitrix\Crm\Integration\StorageType::WebDav)
			{
				$fields['WEBDAV_ELEMENTS'] = $fields['STORAGE_ELEMENT_IDS'];
			}
			unset($fields['STORAGE_ELEMENT_IDS']);
		}
		parent::externalizeFields($fields, $fieldsInfo);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'COMMUNICATION')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				$fieldsInfo = $this->getCommunicationFieldsInfo();
				return parent::prepareFields($fieldsInfo);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmActivityRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmActivityAdd'] = self::createEventInfo('crm', 'OnActivityAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmActivityUpdate'] = self::createEventInfo('crm', 'OnActivityUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmActivityDelete'] = self::createEventInfo('crm', 'OnActivityDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch (strtolower($eventName))
		{
			case 'oncrmactivityadd':
			case 'oncrmactivityupdate':
			case 'oncrmactivitydelete':
			{
				$ID = isset($arParams[0]) ? (int)$arParams[0] : 0;
			}
			break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		if($ID <= 0)
		{
			throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
		}
		return array('FIELDS' => array('ID' => $ID));
	}
}

class CCrmDuplicateRestProxy extends CCrmRestProxyBase
{
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if(!CCrmLead::CheckReadPermission(0, $userPerms)
			&& !CCrmContact::CheckReadPermission(0, $userPerms)
			&& !CCrmCompany::CheckReadPermission(0, $userPerms))
		{
			throw new RestException('Access denied.');
		}

		if(strtoupper($name) === 'FINDBYCOMM')
		{
			$type = strtoupper($this->resolveParam($arParams, 'type'));
			if($type !== 'EMAIL' && $type !== 'PHONE')
			{
				if($type === '')
				{
					throw new RestException("Communication type is not defined.");
				}
				else
				{
					throw new RestException("Communication type '{$type}' is not supported in current context.");
				}
			}

			$values = $this->resolveArrayParam($arParams, 'values');
			if(!is_array($values) || count($values) === 0)
			{
				throw new RestException("Communication values is not defined.");
			}

			$entityTypeID = CCrmOwnerType::ResolveID(
				$this->resolveMultiPartParam($arParams, array('entity', 'type'))
			);

			if($entityTypeID === CCrmOwnerType::Deal)
			{
				throw new RestException("Deal is not supported in current context.");
			}

			$criterions = array();
			$dups = array();
			$qty = 0;
			foreach($values as $value)
			{
				if(!is_string($value) || $value === '')
				{
					continue;
				}

				$criterion = new \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion($type, $value);
				$isExists = false;
				foreach($criterions as $curCriterion)
				{
					/** @var \Bitrix\Crm\Integrity\DuplicateCriterion $curCriterion */
					if($criterion->equals($curCriterion))
					{
						$isExists = true;
						break;
					}
				}

				if($isExists)
				{
					continue;
				}
				$criterions[] = $criterion;

				$duplicate = $criterion->find($entityTypeID, 20);
				if($duplicate !== null)
				{
					$dups[] = $duplicate;
				}

				$qty++;
				if($qty >= 20)
				{
					break;
				}
			}

			$entityByType = array();
			foreach($dups as $dup)
			{
				/** @var \Bitrix\Crm\Integrity\Duplicate $dup */
				$entities = $dup->getEntities();
				if(!(is_array($entities) && !empty($entities)))
				{
					continue;
				}

				//Each entity type limited by 50 items
				foreach($entities as $entity)
				{
					/** @var \Bitrix\Crm\Integrity\DuplicateEntity $entity */
					$entityTypeID = $entity->getEntityTypeID();
					$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);

					$entityID = $entity->getEntityID();

					if(!isset($entityByType[$entityTypeName]))
					{
						$entityByType[$entityTypeName] = array($entityID);
					}
					elseif(!in_array($entityID, $entityByType[$entityTypeName], true))
					{
						$entityByType[$entityTypeName][] = $entityID;
					}
				}
			}
			return $entityByType;
		}
		throw new RestException('Method not found!', RestException::ERROR_METHOD_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
	}
}

class CCrmLiveFeedMessageRestProxy extends CCrmRestProxyBase
{
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'ADD')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');

			$arComponentResult = array(
				'USER_ID' => $this->getCurrentUserID()
			);

			$arPOST = array(
				'ENABLE_POST_TITLE' => 'Y',
				'MESSAGE' => $fields['MESSAGE'],
				'SPERM' => $fields['SPERM']
			);

			if (
				isset($fields['POST_TITLE']) 
				&& strlen($fields['POST_TITLE']) > 0
			)
			{
				$arPOST['POST_TITLE'] = $fields['POST_TITLE'];
			}

			$entityTypeID = $fields['ENTITYTYPEID'];
			$entityID = $fields['ENTITYID'];

			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);			
			$userPerms = CCrmPerms::GetCurrentUserPermissions();

			if(
				$entityTypeName !== '' 
				&& !CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, $entityID, $userPerms)
			)
			{
				throw new RestException('Access denied.');
			}

			$res = CCrmLiveFeedComponent::ProcessLogEventEditPOST($arPOST, $entityTypeID, $entityID, $arComponentResult);

			if(is_array($res))
			{
				throw new RestException(implode(", ", $res));
			}

			return $res;
		}

		throw new RestException('Method not found!', RestException::ERROR_METHOD_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
	}
}

class CCrmUserFieldRestProxy extends UserFieldProxy
{
	private $ownerTypeID = CCrmOwnerType::Undefined;
	private $server = null;

	function __construct($ownerTypeID, \CUser $user = null)
	{
		$this->ownerTypeID = CCrmOwnerType::IsDefined($ownerTypeID) ? $ownerTypeID : CCrmOwnerType::Undefined;
		parent::__construct(CCrmOwnerType::ResolveUserFieldEntityID($this->ownerTypeID), $user);
		$this->setNamePrefix('crm');
	}
	public function getOwnerTypeID()
	{
		return $this->ownerTypeID;
	}
	public function getServer()
	{
		return $this->server;
	}
	public function setServer($server)
	{
		$this->server = $server;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'FIELDS')
		{
			return self::getFields();
		}
		elseif($name === 'TYPES' && method_exists('\Bitrix\Rest\UserFieldProxy', 'getTypes'))
		{
			return self::getTypes();
		}
		elseif($name === 'SETTINGS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				$type = CCrmRestHelper::resolveParam($arParams, 'type', '');
				if($type === '')
				{
					throw new RestException("Parameter 'type' is not specified or empty.");
				}

				return self::getSettingsFields($type);
			}
		}
		elseif($name === 'ENUMERATION')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				return self::getEnumerationElementFields();
			}
		}
		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
	protected function isAuthorizedUser()
	{
		if($this->isAuthorizedUser === null)
		{
			/**@var \CCrmPerms $userPermissions @**/
			$userPermissions = CCrmPerms::GetUserPermissions($this->user->GetID());
			$this->isAuthorizedUser = $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
		}
		return $this->isAuthorizedUser;
	}
	protected function checkCreatePermission()
	{
		return $this->isAuthorizedUser();
	}
	protected function checkReadPermission()
	{
		return $this->isAuthorizedUser();
	}
	protected function checkUpdatePermission()
	{
		return $this->isAuthorizedUser();
	}
	protected function checkDeletePermission()
	{
		return $this->isAuthorizedUser();
	}
}

class CCrmQuoteRestProxy extends CCrmRestProxyBase
{
	private static $ENTITY = null;
	private $FIELDS_INFO = null;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Quote;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmQuote(true);
		}

		return self::$ENTITY;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmQuote::GetFieldsInfo();
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmQuote::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmQuote::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		if(isset($fields['CONTENT']))
		{
			$fields['CONTENT'] = $this->sanitizeHtml($fields['CONTENT']);
		}

		if(isset($fields['TERMS']))
		{
			$fields['TERMS'] = $this->sanitizeHtml($fields['TERMS']);
		}

		$entity = self::getEntity();
		$result = $entity->Add($fields, true);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmQuote::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmQuote::GetList(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmQuote::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = isset($ufData['VALUE']) ? $ufData['VALUE'] : '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmQuote::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$options = array('IS_EXTERNAL_CONTEXT' => true);
		if(is_array($order))
		{
			if(isset($order['STATUS_ID']))
			{
				$order['STATUS_SORT'] = $order['STATUS_ID'];
				unset($order['STATUS_ID']);

				$options['FIELD_OPTIONS'] = array('ADDITIONAL_FIELDS' => array('STATUS_SORT'));
			}
		}

		return CCrmQuote::GetList($order, $filter, false, $navigation, $select, $options);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmQuote::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmQuote::Exists($ID))
		{
			$errors[] = 'Quote is not found';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		if(isset($fields['CONTENT']))
		{
			$fields['CONTENT'] = $this->sanitizeHtml($fields['CONTENT']);
		}

		if(isset($fields['TERMS']))
		{
			$fields['TERMS'] = $this->sanitizeHtml($fields['TERMS']);
		}

		$entity = self::getEntity();
		$compare = true;
		$options = array();
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}
		}

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors)
	{
		if(!CCrmQuote::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}
	public function getProductRows($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!CCrmQuote::CheckReadPermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmQuote::LoadProductRows($ID);
	}
	public function setProductRows($ID, $rows)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($rows))
		{
			throw new RestException('The parameter rows must be array.');
		}

		if(!CCrmQuote::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		if(!CCrmQuote::Exists($ID))
		{
			throw new RestException('Not found.');
		}

		$proxy = new CCrmProductRowRestProxy();

		$actualRows = array();
		$qty = count($rows);
		for($i = 0; $i < $qty; $i++)
		{
			$row = $rows[$i];
			if(!is_array($row))
			{
				continue;
			}

			$proxy->prepareForSave($row);
			if(isset($row['OWNER_TYPE']))
			{
				unset($row['OWNER_TYPE']);
			}

			if(isset($row['OWNER_ID']))
			{
				unset($row['OWNER_ID']);
			}

			$actualRows[] = $row;
		}

		return CCrmQuote::SaveProductRows($ID, $actualRows, true, true, true);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'PRODUCTROWS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');

			if($nameSuffix === 'GET')
			{
				return $this->getProductRows($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$rows = $this->resolveArrayParam($arParams, 'rows');
				return $this->setProductRows($ID, $rows);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}
	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmQuoteRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmQuoteAdd'] = self::createEventInfo('crm', 'OnAfterCrmQuoteAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmQuoteUpdate'] = self::createEventInfo('crm', 'OnAfterCrmQuoteUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmQuoteDelete'] = self::createEventInfo('crm', 'OnAfterCrmQuoteDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEvent(CCrmOwnerType::Quote, $arParams, $arHandler);
	}
}
