<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\RequisiteAddress;
use	Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Internals\BusinessValuePersonDomainTable;
/**
 * @global $APPLICATION CMain
 * @global $DB CDatabase
 */
global $DB, $DBType;

Loc::loadMessages(__FILE__);

if (!function_exists('OnModuleInstalledEvent'))
{
	function OnModuleInstalledEvent($id)
	{
		foreach (GetModuleEvents("main", "OnModuleInstalled", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($id));
	}
}

if (!Main\ModuleManager::isModuleInstalled('currency'))
{
	$errMsg[] = Loc::getMessage('CRM_CURRENCY_NOT_INSTALLED');
	$bError = true;
	return;
}

if (!Main\ModuleManager::isModuleInstalled('sale'))
{
	// clean before install
	if (!$DB->TableExists('b_sale_order'))
	{
		$arTablesToDrop = array();
		if ($DB->TableExists('b_sale_delivery2paysystem'))
			$arTablesToDrop[] = 'sale_delivery2paysystem';
		if ($DB->TableExists('b_sale_person_type_site'))
			$arTablesToDrop[] = 'sale_person_type_site';
		if ($DB->TableExists('b_sale_store_barcode'))
			$arTablesToDrop[] = 'sale_store_barcode';
		foreach ($arTablesToDrop as $tableName)
		{
			$strSql = $strSql1 = '';
			switch (StrToUpper($DBType))
			{
				case 'MYSQL':
					$strSql = "DROP TABLE if exists b_$tableName";
					break;
				case 'ORACLE':
					$strSql = "DROP TABLE b_$tableName CASCADE CONSTRAINTS";
					if($tableName === 'sale_store_barcode')
						$strSql1 = "DROP SEQUENCE sq_$tableName";
					break;
				case 'MSSQL':
					$strSql = "DROP TABLE b_$tableName";
					break;
			}
			if (!empty($strSql))
				$DB->Query($strSql, true);
			if (!empty($strSql1))
				$DB->Query($strSql1, true);
		}
		unset($arTablesToDrop, $strSql, $strSql1);
	}

	$CModule = new CModule();
	/** @var sale $Module */
	if($Module = $CModule->CreateModuleObject("sale"))
	{
		OnModuleInstalledEvent('sale');
		$result = true;

		if(!Main\ModuleManager::isModuleInstalled('bitrix24') || !defined('BX24_HOST_NAME'))
			$result = $Module->InstallFiles();

		if ($result)
			$result = $Module->InstallDB();

		if (!$result)
		{
			$errMsg[] = Loc::getMessage('CRM_CANT_INSTALL_SALE');
			$bError = true;
			return;
		}
		unset($Module);
	}
}

if (!Main\ModuleManager::isModuleInstalled('sale'))
{
	$errMsg[] = Loc::getMessage('CRM_SALE_NOT_INSTALLED');
	$bError = true;
	return;
}

if (!Main\Loader::includeModule('sale'))
{
	$errMsg[] = Loc::getMessage('CRM_SALE_NOT_INCLUDED');
	$bError = true;
	return;
}

$bitrix24Path = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/bitrix24/';
$bitrix24 = file_exists($bitrix24Path) && is_dir($bitrix24Path);
unset($bitrix24Path);
$languageId = '';
if (IsModuleInstalled('bitrix24')
	&& CModule::IncludeModule('bitrix24')
	&& method_exists('CBitrix24', 'getLicensePrefix'))
{
	$languageId = CBitrix24::getLicensePrefix();
}
if ($languageId == '')
{
	$siteIterator = \Bitrix\Main\SiteTable::getList(array(
		'select' => array('LID', 'LANGUAGE_ID'),
		'filter' => array('=DEF' => 'Y', '=ACTIVE' => 'Y')
	));
	if ($site = $siteIterator->fetch())
		$languageId = (string)$site['LANGUAGE_ID'];
	unset($site, $siteIterator);
}
if ($languageId == '')
	$languageId = 'en';
$countryLangId = '';
switch ($languageId)
{
	case 'ua':
	case 'de':
	case 'en':
	case 'la':
	case 'tc':
	case 'sc':
	case 'in':
	case 'kz':
	case 'br':
	case 'by':
		$countryLangId = $languageId;
		break;
	case 'ru':
		if (!$bitrix24)
		{
			$languageIterator = \Bitrix\Main\Localization\LanguageTable::getList(array(
				'select' => array('ID'),
				'filter' => array('=ID' => 'kz', '=ACTIVE' => 'Y')
			));
			if ($existLanguage = $languageIterator->fetch())
				$countryLangId = $existLanguage['ID'];

			if ($countryLangId == '')
			{
				$languageIterator = \Bitrix\Main\Localization\LanguageTable::getList(array(
					'select' => array('ID'),
					'filter' => array('=ID' => 'by', '=ACTIVE' => 'Y')
				));
				if ($existLanguage = $languageIterator->fetch())
					$countryLangId = $existLanguage['ID'];
			}

			if ($countryLangId == '')
			{
				$languageIterator = \Bitrix\Main\Localization\LanguageTable::getList(array(
					'select' => array('ID'),
					'filter' => array('=ID' => 'ua', '=ACTIVE' => 'Y')
				));
				if ($existLanguage = $languageIterator->fetch())
					$countryLangId = $existLanguage['ID'];
			}

			unset($existLanguage, $languageIterator);
		}
		if ($countryLangId == '')
			$countryLangId = $languageId;
		break;
	default:
		$countryLangId = 'en';
		break;
}
switch ($countryLangId)
{
	case 'ru':
	case 'ua':
	case 'de':
	case 'en':
	case 'la':
		$shopLocalization = $countryLangId;
		$psLocalization = $countryLangId;
		break;
	case 'by':
		$shopLocalization = 'ru';
		$psLocalization = 'ru';
		break;
	case 'tc':
		$shopLocalization = 'tc';
		$psLocalization = 'en';
		break;
	case 'sc':
		$shopLocalization = 'sc';
		$psLocalization = 'en';
		break;
	case 'br':
		$shopLocalization = 'la';
		$psLocalization = 'la';
		break;
	case 'in':
		$shopLocalization = 'en';
		$psLocalization = 'en';
		break;
	default:
		$shopLocalization = $countryLangId;
		$psLocalization = 'en';
		break;
}

$currentSiteID = SITE_ID;
if (defined("ADMIN_SECTION"))
{
	$siteIterator = Main\SiteTable::getList(array(
		'select' => array('LID', 'LANGUAGE_ID'),
		'filter' => array('=DEF' => 'Y', '=ACTIVE' => 'Y')
	));
	if ($defaultSite = $siteIterator->fetch())
	{
		$currentSiteID = $defaultSite['LID'];
	}
	unset($defaultSite, $siteIterator);
}

$defCurrency = \Bitrix\Currency\CurrencyManager::getBaseCurrency();
COption::SetOptionString("sale", "default_currency", $defCurrency);

// Create order statuses
$statusesSort = array(
	'N' => 100,
	'A' => 120,
	'D' => 140,
	'P' => 130,
	'S' => 110
);
$createStatusList = $statusesSort;

$dbStatusList = CSaleStatus::GetList(
	array(),
	array('ID' => array_keys($statusesSort)),
	false,
	false,
	array('ID'));

$arExistStatuses = array();

while($arStatusList = $dbStatusList->Fetch())
{
	//$arExistStatuses[$arStatusList['ID']] = $arStatusList;
	$arExistStatuses[$arStatusList['ID']] = true;
	if ($arStatusList['ID'] == 'N')
		continue;
	unset($createStatusList[$arStatusList['ID']]);
}
unset($arStatusList, $dbStatusList);

$arActiveLangs = array();
$languageIterator = Main\Localization\LanguageTable::getList(array(
	'select' => array('ID'),
	'filter' => array('=ACTIVE' => 'Y')
));
while ($language = $languageIterator->fetch())
{
	$arActiveLangs[] = $language['ID'];
}
unset($language, $languageIterator);

$statusLangFiles = array();
if (!empty($createStatusList))
{
	foreach ($arActiveLangs as &$language)
		$statusLangFiles[$language] = Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/status.php', $language);
	unset($language);

	foreach ($createStatusList as $statusId => $statusSort)
	{
		$langData = array();
		foreach ($arActiveLangs as &$language)
		{
			$nameExist = isset($statusLangFiles[$language]['CRM_STATUS_'.$statusId]);
			$descrExist = isset($statusLangFiles[$language]['CRM_STATUS_'.$statusId.'_DESCR']);
			if (!$nameExist && !$descrExist)
				continue;
			$oneLang = array(
				'LID' => $language
			);
			if ($nameExist)
				$oneLang['NAME'] = $statusLangFiles[$language]['CRM_STATUS_'.$statusId];
			if ($descrExist)
				$oneLang['DESCRIPTION'] = $statusLangFiles[$language]['CRM_STATUS_'.$statusId.'_DESCR'];
			$langData[] = $oneLang;
			unset($oneLang, $descrExist, $nameExist);
		}
		unset($language);

		if ($statusId === 'N' && isset($arExistStatuses[$statusId]))
		{
			CSaleStatus::Update(
				$statusId,
				array(
					'SORT' => $statusSort,
					'LANG' => $langData
				)
			);
		}
		else
		{
			CSaleStatus::Add(
				array(
					'ID' => $statusId,
					'SORT' => $statusSort,
					'LANG' => $langData
				)
			);
		}
	}
	unset($statusLangFiles);
}

//Create person types
$companyPTID  = $contactPTID = 0;

$dbPerson = CSalePersonType::GetList(
	array(),
	array(
		"LID" => $currentSiteID,
		"PERSON_TYPE_ID" => array('CRM_COMPANY', 'CRM_CONTACT')
	)
);

while($arPerson = $dbPerson->Fetch())
{
	if($arPerson["NAME"] == 'CRM_COMPANY')
		$companyPTID = $arPerson["ID"];
	elseif($arPerson["NAME"] == 'CRM_CONTACT')
		$contactPTID = $arPerson["ID"];
}

if($companyPTID <=0 )
{
	$companyPTID = CSalePersonType::Add(array(
					"LID" => $currentSiteID,
					"NAME" => 'CRM_COMPANY',
					"SORT" => "100",
					"ACTIVE" => "Y"
			)
	);

	$allPersonTypes = BusinessValue::getPersonTypes(true);
	$personTypeId = $companyPTID;
	$domain = BusinessValue::ENTITY_DOMAIN;

	$r = BusinessValuePersonDomainTable::add(array(
			'PERSON_TYPE_ID' => $personTypeId,
			'DOMAIN'         => $domain,
	));

	if ($r->isSuccess())
	{
		$allPersonTypes[$personTypeId]['DOMAIN'] = $domain;
		BusinessValue::getPersonTypes(true, $allPersonTypes);
	}
	else
	{
		CEventLog::Add(array(
				'SEVERITY' => 'ERROR',
				'AUDIT_TYPE_ID' => 'SALE_1C_TO_BUSINESS_VALUE_ERROR',
				'MODULE_ID' => 'sale',
				'ITEM_ID' => "sale_link.Contact.Add:".$personTypeId,
				'DESCRIPTION' => 'Unable to set person type "'.$personTypeId.'" domain'."\n".implode("\n", $r->getErrorMessages()),
		));
	}
}


if($contactPTID <=0 )
{
	$contactPTID = CSalePersonType::Add(array(
					"LID" => $currentSiteID,
					"NAME" => 'CRM_CONTACT',
					"SORT" => "110",
					"ACTIVE" => "Y"
			)
	);

	$allPersonTypes = BusinessValue::getPersonTypes(true);
	$personTypeId = $contactPTID;
	$domain = BusinessValue::INDIVIDUAL_DOMAIN;

	$r = BusinessValuePersonDomainTable::add(array(
			'PERSON_TYPE_ID' => $contactPTID,
			'DOMAIN'         => $domain,
	));

	if ($r->isSuccess())
	{
		$allPersonTypes[$personTypeId]['DOMAIN'] = $domain;
		BusinessValue::getPersonTypes(true, $allPersonTypes);
	}
	else
	{
		CEventLog::Add(array(
				'SEVERITY' => 'ERROR',
				'AUDIT_TYPE_ID' => 'SALE_1C_TO_BUSINESS_VALUE_ERROR',
				'MODULE_ID' => 'sale',
				'ITEM_ID' => "sale_link.Contact.Add:".$personTypeId,
				'DESCRIPTION' => 'Unable to set person type "'.$personTypeId.'" domain'."\n".implode("\n", $r->getErrorMessages()),
		));
	}
}


//Order user fields
$obUserField  = new CUserTypeEntity;
$arOrderUserFieldDefault = array(
	'ENTITY_ID' => 'ORDER',
	'FIELD_NAME' => 'UF_FIELD',
	'USER_TYPE_ID' => 'string',
	'XML_ID' => 'uf_field',
	'SORT' => '2000',
	'MULTIPLE' => null,
	'MANDATORY' => null,
	'SHOW_FILTER' => 'N',
	'SHOW_IN_LIST' => 'N',
	'EDIT_IN_LIST' => 'N',
	'IS_SEARCHABLE' => null,
	'SETTINGS' => array(
		'DEFAULT_VALUE' => null,
		'SIZE' => '',
		'ROWS' => '1',
		'MIN_LENGTH' => '0',
		'MAX_LENGTH' => '0',
		'REGEXP' => ''
	),
	'EDIT_FORM_LABEL' => array('ru' => '', 'en' => ''),
	'LIST_COLUMN_LABEL' => array('ru' => '', 'en' => ''),
	'LIST_FILTER_LABEL' => array('ru' => '', 'en' => ''),
	'ERROR_MESSAGE' => array('ru' => '', 'en' => ''),
	'HELP_MESSAGE' => array('ru' => '', 'en' => '')
);
$dbRes = $obUserField->GetList(array('SORT' => 'DESC'), array('ENTITY_ID' => 'ORDER'));
$maxUFSort = 0;
$i = 0;
$arUFNames = array();
while ($arUF = $dbRes->Fetch())
{
	if ($i++ === 0)
		$maxUFSort = intval($arUF['SORT']);
	$arUFNames[] = $arUF['FIELD_NAME'];
}
unset($dbRes, $arUF, $i);
$arOrderUserFields = array();
if (!in_array('UF_DEAL_ID', $arUFNames))
{
	$arOrderUserFields[] = array(
		'FIELD_NAME' => 'UF_DEAL_ID',
		'USER_TYPE_ID' => 'integer',
		'XML_ID' => 'uf_deal_id'
	);
}
if (!in_array('UF_QUOTE_ID', $arUFNames))
{
	$arOrderUserFields[] = array(
		'FIELD_NAME' => 'UF_QUOTE_ID',
		'USER_TYPE_ID' => 'integer',
		'XML_ID' => 'uf_quote_id'
	);
}
if (!in_array('UF_COMPANY_ID', $arUFNames))
{
	$arOrderUserFields[] = array(
		'FIELD_NAME' => 'UF_COMPANY_ID',
		'USER_TYPE_ID' => 'integer',
		'XML_ID' => 'uf_company_id'
	);
}
if (!in_array('UF_CONTACT_ID', $arUFNames))
{
	$arOrderUserFields[] = array(
		'FIELD_NAME' => 'UF_CONTACT_ID',
		'USER_TYPE_ID' => 'integer',
		'XML_ID' => 'uf_contact_id'
	);
}
unset($arUFNames);
$sort = $maxUFSort;
foreach ($arOrderUserFields as $field)
{
	$arFields = $arOrderUserFieldDefault;

	if ($sort === 0)
		$sort = $arFields['SORT'];
	else
		$sort += 10;
	$arFields['SORT'] = $sort;

	foreach ($field as $k => $v)
		$arFields[$k] = $v;

	$ID = $obUserField->Add($arFields);
	if ($ID <= 0)
	{
		$errMsg[] = Loc::getMessage('CRM_CANT_ADD_USER_FIELD', array('#FIELD_NAME#' => $arFields['FIELD_NAME']));
		$bError = true;
	}
}

if ($bError)
	return;

//Order Prop Group
$arPropGroup = array();

$dbSaleOrderPropsGroup = CSaleOrderPropsGroup::GetList(array(), array("PERSON_TYPE_ID" => $contactPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_FIZ1")), false, false, array("ID"));
if ($arSaleOrderPropsGroup = $dbSaleOrderPropsGroup->GetNext())
	$arPropGroup["user_fiz"] = $arSaleOrderPropsGroup["ID"];
else
	$arPropGroup["user_fiz"] = CSaleOrderPropsGroup::Add(array("PERSON_TYPE_ID" => $contactPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_FIZ1"), "SORT" => 100));

$dbSaleOrderPropsGroup = CSaleOrderPropsGroup::GetList(array(), array("PERSON_TYPE_ID" => $contactPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_FIZ2")), false, false, array("ID"));
if ($arSaleOrderPropsGroup = $dbSaleOrderPropsGroup->GetNext())
	$arPropGroup["adres_fiz"] = $arSaleOrderPropsGroup["ID"];
else
	$arPropGroup["adres_fiz"] = CSaleOrderPropsGroup::Add(array("PERSON_TYPE_ID" => $contactPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_FIZ2"), "SORT" => 200));

$dbSaleOrderPropsGroup = CSaleOrderPropsGroup::GetList(array(), array("PERSON_TYPE_ID" => $companyPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_UR1")), false, false, array("ID"));
if ($arSaleOrderPropsGroup = $dbSaleOrderPropsGroup->GetNext())
	$arPropGroup["user_ur"] = $arSaleOrderPropsGroup["ID"];
else
	$arPropGroup["user_ur"] = CSaleOrderPropsGroup::Add(array("PERSON_TYPE_ID" => $companyPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_UR1"), "SORT" => 300));

$dbSaleOrderPropsGroup = CSaleOrderPropsGroup::GetList(array(), array("PERSON_TYPE_ID" => $companyPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_UR2")), false, false, array("ID"));
if ($arSaleOrderPropsGroup = $dbSaleOrderPropsGroup->GetNext())
	$arPropGroup["adres_ur"] = $arSaleOrderPropsGroup["ID"];
else
	$arPropGroup["adres_ur"] = CSaleOrderPropsGroup::Add(array("PERSON_TYPE_ID" => $companyPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_UR2"), "SORT" => 400));

$arProps = array();

$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => Loc::getMessage("CRM_ORD_PROP_6"),
	"TYPE" => "TEXT",
	"REQUIED" => "Y",
	"DEFAULT_VALUE" => "",
	"SORT" => 100,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "N",
	"PROPS_GROUP_ID" => $arPropGroup["user_fiz"],
	"SIZE1" => 40,
	"SIZE2" => 0,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "N",
	"IS_PROFILE_NAME" => "Y",
	"IS_PAYER" => "Y",
	"IS_LOCATION4TAX" => "N",
	"CODE" => "FIO",
	"IS_FILTERED" => "Y",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => "E-Mail",
	"TYPE" => "TEXT",
	"REQUIED" => "Y",
	"DEFAULT_VALUE" => "",
	"SORT" => 110,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "N",
	"PROPS_GROUP_ID" => $arPropGroup["user_fiz"],
	"SIZE1" => 40,
	"SIZE2" => 0,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "Y",
	"IS_PROFILE_NAME" => "N",
	"IS_PAYER" => "N",
	"IS_LOCATION4TAX" => "N",
	"CODE" => "EMAIL",
	"IS_FILTERED" => "Y",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => Loc::getMessage("CRM_ORD_PROP_9"),
	"TYPE" => "TEXT",
	"REQUIED" => "Y",
	"DEFAULT_VALUE" => "",
	"SORT" => 120,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "N",
	"PROPS_GROUP_ID" => $arPropGroup["user_fiz"],
	"SIZE1" => 0,
	"SIZE2" => 0,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "N",
	"IS_PROFILE_NAME" => "N",
	"IS_PAYER" => "N",
	"IS_LOCATION4TAX" => "N",
	"CODE" => "PHONE",
	"IS_FILTERED" => "N",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => Loc::getMessage("CRM_ORD_PROP_4"),
	"TYPE" => "TEXT",
	"REQUIED" => "N",
	"DEFAULT_VALUE" => "101000",
	"SORT" => 130,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "N",
	"PROPS_GROUP_ID" => $arPropGroup["adres_fiz"],
	"SIZE1" => 8,
	"SIZE2" => 0,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "N",
	"IS_PROFILE_NAME" => "N",
	"IS_PAYER" => "N",
	"IS_LOCATION4TAX" => "N",
	"CODE" => "ZIP",
	"IS_FILTERED" => "N",
	"IS_ZIP" => "Y",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => Loc::getMessage("CRM_ORD_PROP_21"),
	"TYPE" => "TEXT",
	"REQUIED" => "N",
	"DEFAULT_VALUE" => "",
	"SORT" => 145,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "N",
	"PROPS_GROUP_ID" => $arPropGroup["adres_fiz"],
	"SIZE1" => 40,
	"SIZE2" => 0,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "N",
	"IS_PROFILE_NAME" => "N",
	"IS_PAYER" => "N",
	"IS_LOCATION4TAX" => "N",
	"CODE" => "CITY",
	"IS_FILTERED" => "Y",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => Loc::getMessage("CRM_ORD_PROP_2"),
	"TYPE" => "LOCATION",
	"REQUIED" => "Y",
	"DEFAULT_VALUE" => "",
	"SORT" => 140,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "Y",
	"PROPS_GROUP_ID" => $arPropGroup["adres_fiz"],
	"SIZE1" => 40,
	"SIZE2" => 0,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "N",
	"IS_PROFILE_NAME" => "N",
	"IS_PAYER" => "N",
	"IS_LOCATION4TAX" => "Y",
	"CODE" => "LOCATION",
	"IS_FILTERED" => "N",
	"INPUT_FIELD_LOCATION" => ""
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => Loc::getMessage("CRM_ORD_PROP_5"),
	"TYPE" => "TEXTAREA",
	"REQUIED" => "Y",
	"DEFAULT_VALUE" => "",
	"SORT" => 150,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "N",
	"PROPS_GROUP_ID" => $arPropGroup["adres_fiz"],
	"SIZE1" => 30,
	"SIZE2" => 3,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "N",
	"IS_PROFILE_NAME" => "N",
	"IS_PAYER" => "N",
	"IS_LOCATION4TAX" => "N",
	"CODE" => "ADDRESS",
	"IS_FILTERED" => "N",
);

if ($shopLocalization == "ru")
{
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_13"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 220,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "INN",
		"IS_FILTERED" => "N",
	);

	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_14"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 230,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "KPP",
		"IS_FILTERED" => "N",
	);
}
elseif ($shopLocalization == "de")
{
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_BLZ"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 220,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "BLZ",
		"IS_FILTERED" => "N",
	);

	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_IBAN"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 230,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "IBAN",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_BIC_SWIFT"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 240,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "BIC_SWIFT",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_UST_IDNR"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 250,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "UST_IDNR",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_STEU"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 260,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "STEU",
		"IS_FILTERED" => "N",
	);
}
elseif ($shopLocalization == "en")
{
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_IBAN"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 230,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "IBAN",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_BIC_SWIFT"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 240,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "BIC_SWIFT",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_SORT_CODE"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 250,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "SORT_CODE",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_CRN"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 260,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY_REG_NO",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_TRN"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 270,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "TAX_REG_NO",
		"IS_FILTERED" => "N",
	);

}

if($shopLocalization != "ua")
{
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_8"),
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 200,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "Y",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_7"),
		"TYPE" => "TEXTAREA",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 210,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY_ADR",
		"IS_FILTERED" => "N",
	);

	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_10"),
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 240,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "Y",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "CONTACT_PERSON",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => "E-Mail",
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 250,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "Y",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "EMAIL",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_9"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" =>260,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "PHONE",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_11"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 270,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "FAX",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_4"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 280,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 8,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "ZIP",
		"IS_FILTERED" => "N",
		"IS_ZIP" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_21"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 285,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "CITY",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_2"),
		"TYPE" => "LOCATION",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 290,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "Y",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "Y",
		"CODE" => "LOCATION",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_12"),
		"TYPE" => "TEXTAREA",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 300,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 30,
		"SIZE2" => 40,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "ADDRESS",
		"IS_FILTERED" => "N",
	);
}
elseif($shopLocalization == "ua")
{
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => "E-Mail",
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 110,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "Y",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "EMAIL",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_40"),
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 130,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "Y",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY_NAME",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_47"),
		"TYPE" => "TEXTAREA",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 140,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY_ADR",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_48"),
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 150,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 30,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "EGRPU",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_49"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 160,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 30,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "INN",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_46"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 170,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 30,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "NDS",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_44"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 180,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 8,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "ZIP",
		"IS_FILTERED" => "N",
		"IS_ZIP" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_43"),
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => $shopLocation,
		"SORT" => 190,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 30,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "CITY",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_42"),
		"TYPE" => "TEXTAREA",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 200,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 30,
		"SIZE2" => 3,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "ADDRESS",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_45"),
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 210,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 30,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "PHONE",
		"IS_FILTERED" => "N",
	);

}

$arGeneralInfo = array();

foreach($arProps as $prop)
{
	$variants = array();
	if(!empty($prop["VARIANTS"]))
	{
		$variants = $prop["VARIANTS"];
		unset($prop["VARIANTS"]);
	}

	$dbSaleOrderProps = CSaleOrderProps::GetList(
											array(),
											array(
												"PERSON_TYPE_ID" => $prop["PERSON_TYPE_ID"],
												"CODE" =>  $prop["CODE"])
	);

	if ($arSaleOrderProps = $dbSaleOrderProps->GetNext())
		$id = $arSaleOrderProps["ID"];
	else
		$id = CSaleOrderProps::Add($prop);

	if(!empty($variants))
	{
		foreach($variants as $val)
		{
			$val["ORDER_PROPS_ID"] = $id;
			CSaleOrderPropsVariant::Add($val);
		}
	}
}

$newPSContactParams = $newPSCompanyParams = false;
$rqCountryId = EntityPreset::getCurrentCountryId();
if ($rqCountryId > 0 && in_array($rqCountryId, EntityRequisite::getAllowedRqFieldCountries(), true))
{
	$newPSContactParams = (Main\Config\Option::get('crm', '~CRM_TRANSFER_REQUISITES_TO_CONTACT', 'N') !== 'Y');
	$newPSCompanyParams = (Main\Config\Option::get('crm', '~CRM_TRANSFER_REQUISITES_TO_COMPANY', 'N') !== 'Y');
}

//PaySystem
$arPaySystems = array();
$paySysName = 'bill';

if($shopLocalization != 'ru')
	$paySysName .= $psLocalization;

if($shopLocalization != "ua")
{
	$arPaySystems[] = array(
		"NAME" => Loc::getMessage("CRM_ORD_PS_BILL_UL"),
		"PSA_NAME" => Loc::getMessage("CRM_ORD_PS_BILL_UL"),
		"SORT" => 100,
		"DESCRIPTION" => "",
		"ACTION_FILE" => $paySysName,
		"PERSON_TYPE_ID" => $companyPTID,
		"RESULT_FILE" => "",
		"NEW_WINDOW" => "Y",
		"PARAMS" => serialize(array(
			"PAYMENT_DATE_INSERT" => array("TYPE" => "ORDER", "VALUE" => "DATE_BILL_DATE"),
			"PAYMENT_DATE_PAY_BEFORE" => array("TYPE" => "ORDER", "VALUE" => "DATE_PAY_BEFORE"),
			"SELLER_COMPANY_NAME" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_ADDRESS" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_PHONE" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_INN" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_KPP" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_BANK_ACCOUNT" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_BANK_ACCOUNT_CORR" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_BANK_BIC" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_BANK_NAME" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_BANK_CITY" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_DIRECTOR_NAME" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_ACCOUNTANT_NAME" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_NAME" => array(
				"TYPE" => $newPSCompanyParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSCompanyParams ? "RQ_COMPANY_NAME|$rqCountryId" : "COMPANY"
			),
			"BUYER_PERSON_COMPANY_INN" => array(
				"TYPE" => $newPSCompanyParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSCompanyParams ? "RQ_INN|$rqCountryId" : "INN"
			),
			"BUYER_PERSON_COMPANY_ADDRESS" => array(
				"TYPE" => $newPSCompanyParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSCompanyParams ? "RQ_ADDR_".RequisiteAddress::Registered."|$rqCountryId" : "COMPANY_ADR"
			),
			"BUYER_PERSON_COMPANY_PHONE" => array(
				"TYPE" => $newPSCompanyParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSCompanyParams ? "RQ_PHONE|$rqCountryId" : "PHONE"
			),
			"BUYER_PERSON_COMPANY_FAX" => array(
				"TYPE" => $newPSCompanyParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSCompanyParams ? "RQ_FAX|$rqCountryId" : "FAX"
			),
			"BUYER_PERSON_COMPANY_NAME_CONTACT" => array(
				"TYPE" => $newPSCompanyParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSCompanyParams ? "RQ_CONTACT|$rqCountryId" : "CONTACT_PERSON"
			),
			ToUpper($paySysName)."_COMMENT1" => array("TYPE" => "ORDER", "VALUE" => "USER_DESCRIPTION"),
			ToUpper($paySysName)."_COMMENT2" => array("TYPE" => "", "VALUE" => ""),
			ToUpper($paySysName)."_PATH_TO_STAMP" => array("TYPE" => "", "VALUE" => ""),
		)),
		"HAVE_PAYMENT" => "Y",
		"HAVE_ACTION" => "N",
		"HAVE_RESULT" => "N",
		"HAVE_PREPAY" => "N",
		"HAVE_RESULT_RECEIVE" => "N"
	);

	$arPaySystems[] = array(
		"NAME" => Loc::getMessage("CRM_ORD_PS_BILL_FL"),
		"PSA_NAME" => Loc::getMessage("CRM_ORD_PS_BILL_FL"),
		"SORT" => 100,
		"DESCRIPTION" => "",
		"ACTION_FILE" => $paySysName,
		"RESULT_FILE" => "",
		"NEW_WINDOW" => "Y",
		"PERSON_TYPE_ID" => $contactPTID,
		"PARAMS" => serialize(array(
			"PAYMENT_DATE_INSERT" => array("TYPE" => "ORDER", "VALUE" => "DATE_BILL_DATE"),
			"PAYMENT_DATE_PAY_BEFORE" => array("TYPE" => "ORDER", "VALUE" => "DATE_PAY_BEFORE"),
			"SELLER_COMPANY_NAME" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_ADDRESS" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_PHONE" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_INN" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_KPP" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_BANK_ACCOUNT" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_BANK_ACCOUNT_CORR" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_BANK_BIC" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_BANK_NAME" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_BANK_CITY" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_DIRECTOR_NAME" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_ACCOUNTANT_NAME" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_NAME" => array(
				"TYPE" => $newPSContactParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSContactParams ? "RQ_NAME|$rqCountryId" : "FIO"
			),
			"BUYER_PERSON_COMPANY_INN" => array(
				"TYPE" => $newPSContactParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSContactParams ? "RQ_INN|$rqCountryId" : "INN"
			),
			"BUYER_PERSON_COMPANY_ADDRESS" => array(
				"TYPE" => $newPSContactParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSContactParams ? "RQ_ADDR_".RequisiteAddress::Primary."|$rqCountryId" : "ADDRESS"
			),
			"BUYER_PERSON_COMPANY_PHONE" => array(
				"TYPE" => $newPSContactParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSContactParams ? "RQ_PHONE|$rqCountryId" : "PHONE"
			),
			"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_NAME_CONTACT" => array("TYPE" => "", "VALUE" => ""),
			ToUpper($paySysName)."_COMMENT1" => array("TYPE" => "ORDER", "VALUE" => "USER_DESCRIPTION"),
			ToUpper($paySysName)."_COMMENT2" => array("TYPE" => "", "VALUE" => ""),
			ToUpper($paySysName)."_PATH_TO_STAMP" => array("TYPE" => "", "VALUE" => ""),
		)),
		"HAVE_PAYMENT" => "Y",
		"HAVE_ACTION" => "N",
		"HAVE_RESULT" => "N",
		"HAVE_PREPAY" => "N",
		"HAVE_RESULT_RECEIVE" => "N"
	);

	$customPaySystemPath = COption::GetOptionString('sale', 'path2user_ps_files', '');
	if($customPaySystemPath === '')
	{
		$customPaySystemPath = BX_ROOT.'/php_interface/include/sale_payment/';
	}

	// QUOTE PAYSYSTEMS -->
	$quotePaySysName = 'quote_'.$psLocalization;
	$arPaySystems[] = array(
		'NAME' => Loc::getMessage('CRM_QUOTE_PS_COMPANY'),
		'SORT' => 200,
		'DESCRIPTION' => '',
		'PSA_NAME' => Loc::getMessage('CRM_QUOTE_PS_COMPANY'),
		'ACTION_FILE' => $customPaySystemPath.$quotePaySysName,
		'RESULT_FILE' => '',
		'NEW_WINDOW' => 'Y',
		"PERSON_TYPE_ID" => $companyPTID,
		'PARAMS' =>
			serialize(
				array(
					'DATE_INSERT' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_BILL_DATE'),
					'DATE_PAY_BEFORE' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_PAY_BEFORE'),
					'BUYER_NAME' => array(
						'TYPE' => $newPSCompanyParams ? 'REQUISITE' : 'PROPERTY',
						'VALUE' => $newPSCompanyParams ? 'RQ_COMPANY_NAME|'.$rqCountryId : 'COMPANY'
					),
					'BUYER_INN' => array(
						'TYPE' => $newPSCompanyParams ? 'REQUISITE' : 'PROPERTY',
						'VALUE' => $newPSCompanyParams ? 'RQ_INN|'.$rqCountryId : 'INN'
					),
					'BUYER_ADDRESS' => array(
						'TYPE' => $newPSCompanyParams ? 'REQUISITE' : 'PROPERTY',
						'VALUE' => $newPSCompanyParams ?
							'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$rqCountryId : 'COMPANY_ADR'
					),
					'BUYER_PHONE' => array(
						'TYPE' => $newPSCompanyParams ? 'REQUISITE' : 'PROPERTY',
						'VALUE' => $newPSCompanyParams ? 'RQ_PHONE|'.$rqCountryId : 'PHONE'
					),
					'BUYER_FAX' => array(
						'TYPE' => $newPSCompanyParams ? 'REQUISITE' : 'PROPERTY',
						'VALUE' => $newPSCompanyParams ? 'RQ_FAX|'.$rqCountryId : 'FAX'
					),
					'BUYER_PAYER_NAME' => array(
						'TYPE' => $newPSCompanyParams ? 'REQUISITE' : 'PROPERTY',
						'VALUE' => $newPSCompanyParams ? 'RQ_CONTACT|'.$rqCountryId : 'CONTACT_PERSON'
					),
					'COMMENT1' => array('TYPE' => 'ORDER', 'VALUE' => 'USER_DESCRIPTION')
				)
			),
		'HAVE_PAYMENT' => 'Y',
		'HAVE_ACTION' => 'N',
		'HAVE_RESULT' => 'N',
		'HAVE_PREPAY' => 'N',
		'HAVE_RESULT_RECEIVE' => 'N'
	);
	$arPaySystems[] = array(
		'NAME' => Loc::getMessage('CRM_QUOTE_PS_CONTACT'),
		'PSA_NAME' => Loc::getMessage('CRM_QUOTE_PS_CONTACT'),
		'SORT' => 300,
		'DESCRIPTION' => '',
		"PERSON_TYPE_ID" => $contactPTID,
		'ACTION_FILE' => $customPaySystemPath.$quotePaySysName,
		'RESULT_FILE' => '',
		'NEW_WINDOW' => 'Y',
		'PARAMS' => serialize(
			array(
				'DATE_INSERT' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_BILL_DATE'),
				'DATE_PAY_BEFORE' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_PAY_BEFORE'),
				'BUYER_NAME' => array(
					'TYPE' => $newPSContactParams ? 'REQUISITE' : 'PROPERTY',
					'VALUE' => $newPSContactParams ? 'RQ_NAME|'.$rqCountryId : 'FIO'
				),
				'BUYER_INN' => array(
					'TYPE' => $newPSContactParams ? 'REQUISITE' : 'PROPERTY',
					'VALUE' => $newPSContactParams ? 'RQ_INN|'.$rqCountryId : 'INN'
				),
				'BUYER_ADDRESS' => array(
					'TYPE' => $newPSContactParams ? 'REQUISITE' : 'PROPERTY',
					'VALUE' => $newPSContactParams ? 'RQ_ADDR_'.RequisiteAddress::Primary.'|'.$rqCountryId : 'ADDRESS'
				),
				'BUYER_PHONE' => array(
					'TYPE' => $newPSContactParams ? 'REQUISITE' : 'PROPERTY',
					'VALUE' => $newPSContactParams ? 'RQ_PHONE|'.$rqCountryId : 'PHONE'
				),
				'BUYER_FAX' => array('TYPE' => '', 'VALUE' => ''),
				'BUYER_PAYER_NAME' => array('TYPE' => '', 'VALUE' => ''),
				'COMMENT1' => array('TYPE' => 'ORDER', 'VALUE' => 'USER_DESCRIPTION')
			)
		),
		'HAVE_PAYMENT' => 'Y',
		'HAVE_ACTION' => 'N',
		'HAVE_RESULT' => 'N',
		'HAVE_PREPAY' => 'N',
		'HAVE_RESULT_RECEIVE' => 'N'
	);
	//<-- QUOTE PAYSYSTEMS
}
else
{
	//bill
	$arPaySystems[] = array(
		"NAME" => Loc::getMessage("CRM_ORD_PS_BILL_UL"),
		"PSA_NAME" => Loc::getMessage("CRM_ORD_PS_BILL_UL"),
		"SORT" => 100,
		"DESCRIPTION" => "",
		"PERSON_TYPE_ID" => $companyPTID,
		"ACTION_FILE" => $paySysName,
		"RESULT_FILE" => "",
		"NEW_WINDOW" => "Y",
		"PARAMS" => serialize(array(
			"PAYMENT_DATE_INSERT" => array("TYPE" => "ORDER", "VALUE" => "DATE_BILL_DATE"),
			"SELLER_COMPANY_NAME" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_ADDRESS" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_PHONE" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_IPN" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_EDRPOY" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_BANK_ACCOUNT" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_BANK_NAME" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_MFO" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_PDV" => array("TYPE" => "", "VALUE" => ""),
			"ORDER_ID" => array("TYPE" => "ORDER", "VALUE" => "ID"),
			"SELLER_COMPANY_SYS" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_NAME" => array(
				"TYPE" => $newPSCompanyParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSCompanyParams ? "RQ_COMPANY_NAME|$rqCountryId" : "COMPANY_NAME"
			),
			"BUYER_PERSON_COMPANY_INN" => array(
				"TYPE" => $newPSCompanyParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSCompanyParams ? "RQ_INN|$rqCountryId" : "INN"
			),
			"BUYER_PERSON_COMPANY_ADDRESS" => array(
				"TYPE" => $newPSCompanyParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSCompanyParams ? "RQ_ADDR_".RequisiteAddress::Registered."|$rqCountryId" : "COMPANY_ADR"
			),
			"BUYER_PERSON_COMPANY_PHONE" => array(
				"TYPE" => $newPSCompanyParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSCompanyParams ? "RQ_PHONE|$rqCountryId" : "PHONE"
			),
			"BUYER_PERSON_COMPANY_FAX" => array(
				"TYPE" => $newPSCompanyParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSCompanyParams ? "RQ_FAX|$rqCountryId" : "FAX"
			),
			ToUpper($paySysName)."_PATH_TO_STAMP" => array("TYPE" => "", "VALUE" => ""),
		)),
		"HAVE_PAYMENT" => "Y",
		"HAVE_ACTION" => "N",
		"HAVE_RESULT" => "N",
		"HAVE_PREPAY" => "N",
		"HAVE_RESULT_RECEIVE" => "N",
	);

	$arPaySystems[] = array(
		"NAME" => Loc::getMessage("CRM_ORD_PS_BILL_FL"),
		"SORT" => 100,
		"DESCRIPTION" => "",
		"PERSON_TYPE_ID" => $contactPTID,
		"ACTION_FILE" => $paySysName,
		"RESULT_FILE" => "",
		"NEW_WINDOW" => "Y",
		"PARAMS" => serialize(array(
			"PAYMENT_DATE_INSERT" => array("TYPE" => "ORDER", "VALUE" => "DATE_BILL_DATE"),
			"SELLER_COMPANY_NAME" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_ADDRESS" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_PHONE" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_IPN" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_EDRPOY" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_BANK_ACCOUNT" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_BANK_NAME" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_MFO" => array("TYPE" => "", "VALUE" => ""),
			"SELLER_COMPANY_PDV" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_NAME" => array(
				"TYPE" => $newPSContactParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSContactParams ? "RQ_NAME|$rqCountryId" : "FIO"
			),
			"BUYER_PERSON_COMPANY_INN" => array(
				"TYPE" => $newPSContactParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSContactParams ? "RQ_INN|$rqCountryId" : "INN"
			),
			"BUYER_PERSON_COMPANY_ADDRESS" => array(
				"TYPE" => $newPSContactParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSContactParams ? "RQ_ADDR_".RequisiteAddress::Primary."|$rqCountryId" : "ADDRESS"
			),
			"BUYER_PERSON_COMPANY_PHONE" => array(
				"TYPE" => $newPSContactParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSContactParams ? "RQ_PHONE|$rqCountryId" : "PHONE"
			),
			"BUYER_PERSON_COMPANY_FAX" => array(
				"TYPE" => $newPSContactParams ? "REQUISITE" : "PROPERTY",
				"VALUE" => $newPSContactParams ? "RQ_FAX|$rqCountryId" : "FAX"
			),
			ToUpper($paySysName)."_PATH_TO_STAMP" => array("TYPE" => "", "VALUE" => ""),
		)),
		"HAVE_PAYMENT" => "Y",
		"HAVE_ACTION" => "N",
		"HAVE_RESULT" => "N",
		"HAVE_PREPAY" => "N",
		"HAVE_RESULT_RECEIVE" => "N",
	);
}

foreach($arPaySystems as $val)
{
	$dbSalePaySystem = \Bitrix\Sale\PaySystem\Manager::getList(
		array(
			'select' => array('ID', 'NAME'),
			'filter' => array('NAME' => $val['NAME'])
		)
	);

	if ($data = $dbSalePaySystem->fetch())
	{
		$result = \Bitrix\Sale\Internals\PaySystemActionTable::update($data['ID'], $val);
		$id = $data['ID'];
	}
	else
	{
		$result = \Bitrix\Sale\Internals\PaySystemActionTable::add($val);
		$id = $result->getId();
	}

	$psParams = unserialize($val['PARAMS']);
	foreach ($psParams as $code => $map)
	{
		$tmpMap['PROVIDER_KEY'] = $map['TYPE'];
		$tmpMap['PROVIDER_VALUE'] = $map['VALUE'];
		\Bitrix\Sale\BusinessValue::setMapping($code, 'PAYSYSTEM_'.$id, $val['PERSON_TYPE_ID'], $tmpMap, true);
	}

	if ($val['PERSON_TYPE_ID'])
	{
		$params = array(
			'filter' => array(
				"SERVICE_ID" => $id,
				"SERVICE_TYPE" => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
				"=CLASS_NAME" => '\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType'
			)
		);

		$dbRes = \Bitrix\Sale\Internals\ServiceRestrictionTable::getList($params);
		if (!$dbRes->fetch())
		{
			$fields = array(
				"SERVICE_ID" => $id,
				"SERVICE_TYPE" => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
				"SORT" => 100,
				"PARAMS" => array(
					'PERSON_TYPE_ID' => array($val['PERSON_TYPE_ID'])
				)
			);
			\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType::save($fields);
		}
	}

	$updateFields = array('PAY_SYSTEM_ID' => $id);

	if (strpos($val['ACTION_FILE'], '/') !== false)
		$pathImg = $_SERVER["DOCUMENT_ROOT"].$val["ACTION_FILE"]."/logo.gif";
	else
		$pathImg = $_SERVER["DOCUMENT_ROOT"].\Bitrix\Sale\PaySystem\Manager::getPathToHandlerFolder($val["ACTION_FILE"])."/logo.gif";

	if (Bitrix\Main\IO\File::isFileExists($pathImg))
	{
		$updateFields['LOGOTIP'] = CFile::MakeFileArray($pathImg);

		if (array_key_exists("LOGOTIP", $updateFields) && is_array($updateFields["LOGOTIP"]))
			$updateFields["LOGOTIP"]["MODULE_ID"] = "sale";

		CFile::SaveForDB($updateFields, "LOGOTIP", "sale/paysystem/logotip");
	}

	$psParams['BX_PAY_SYSTEM_ID'] = array('TYPE' => '', 'VALUE' => $id);
	$updateFields['PARAMS'] = serialize($psParams);

	\Bitrix\Sale\Internals\PaySystemActionTable::update($id, $updateFields);
}

if (!Main\ModuleManager::isModuleInstalled('catalog'))
{
	$CModule = new CModule();
	if($Module = $CModule->CreateModuleObject("catalog"))
	{
		OnModuleInstalledEvent('catalog');
		$result = true;

		if(!Main\ModuleManager::isModuleInstalled('bitrix24') || !defined('BX24_HOST_NAME'))
			$result = $Module->InstallFiles();

		if ($result)
			$result = $Module->InstallDB();
		if ($result)
			$result = $Module->InstallEvents();
		if (!$result)
		{
			$errMsg[] = Loc::getMessage('CRM_CANT_INSTALL_CATALOG');
			$bError = true;
			return;
		}
		unset($Module);
	}
	unset($CModule);
}

if (!Main\ModuleManager::isModuleInstalled('catalog'))
{
	$errMsg[] = Loc::getMessage('CRM_CATALOG_NOT_INSTALLED');
	$bError = true;
	return;
}

if (!Main\Loader::includeModule('catalog'))
{
	$errMsg[] = Loc::getMessage('CRM_CATALOG_NOT_INCLUDED');
	$bError = true;
	return;
}

if($shopLocalization == "ru")
{
	$dbVat = CCatalogVat::GetListEx(
		array(),
		array('RATE' => 0),
		false,
		false,
		array('ID')
	);
	if(!($dbVat->Fetch()))
	{
		$arF = array ("ACTIVE" => "Y", "SORT" => "100", "NAME" => Loc::getMessage("CRM_VAT_1"), "RATE" => '0');
		CCatalogVat::Add($arF);
	}
	$dbVat = CCatalogVat::GetListEx(
		array(),
		array('RATE' => 18),
		false,
		false,
		array('ID')
	);
	if(!($dbVat->Fetch()))
	{
		$arF = array ("ACTIVE" => "Y", "SORT" => "200", "NAME" => Loc::getMessage("CRM_VAT_2"), "RATE" => '18');
		CCatalogVat::Add($arF);
	}
}

// get default vat
$defCatVatId = 0;
$dbVat = CCatalogVat::GetListEx(array('SORT' => 'ASC'), array(), false, array('nPageTop' => 1));
if ($arVat = $dbVat->Fetch())
{
	$defCatVatId = $arVat['ID'];
}
unset($arVat, $dbVat);
$defCatVatId = (int)$defCatVatId;

// create base price
$basePriceId = 0;
$basePrice = array();
$dbRes = CCatalogGroup::GetListEx(array(), array("BASE" => "Y"), false, false, array('ID'));
if(!($basePrice = $dbRes->Fetch()))
{
	$catalogGroupLangFiles = array();
	foreach ($arActiveLangs as &$language)
		$catalogGroupLangFiles[$language] = Loc::loadLanguageFile(__FILE__, $language);
	$arFields = array();
	$arFields["USER_LANG"] = array();
	foreach ($arActiveLangs as &$language)
	{
		if (isset($catalogGroupLangFiles[$language]))
			$arFields["USER_LANG"][$language] = $catalogGroupLangFiles[$language]['CRM_BASE_PRICE_NAME'];
	}
	unset($language);
	unset($catalogGroupLangFiles);
	$arFields["BASE"] = "Y";
	$arFields["SORT"] = 100;
	$arFields["NAME"] = "BASE";
	$arFields["USER_GROUP"] = array(1, 2);
	$arFields["USER_GROUP_BUY"] = array(1, 2);
	$basePriceId = CCatalogGroup::Add($arFields);
	if ($basePriceId <= 0)
	{
		$errMsg[] = Loc::getMessage('CRM_UPDATE_ERR_003');
		$bError = true;
		return;
	}
}
if ($basePriceId <= 0 && isset($basePrice['ID']) && $basePrice['ID'] > 0) $basePriceId = $basePrice['ID'];
unset($basePrice, $dbRes);

$arCatalogId = array();
$dbCatalogList = CCrmCatalog::GetList();
while ($arCatalog = $dbCatalogList->Fetch())
	$arCatalogId[] = $arCatalog['ID'];
$defCatalogId = CCrmCatalog::EnsureDefaultExists();
if ($defCatalogId > 0)
{
	if (!in_array($defCatalogId, $arCatalogId))
		$arCatalogId[] = $defCatalogId;
}
else
{
	$errMsg[] = Loc::getMessage('CRM_UPDATE_ERR_001');
	$bError = true;
	return;
}
if (!empty($arCatalogId) && !$bError)
{
	$CCatalog = new CCatalog();
	if ($CCatalog)
	{
		foreach ($arCatalogId as $catalogId)
		{
			$arFields = array(
				'IBLOCK_ID' => $catalogId,
				'CATALOG' => 'Y'
			);
			if ($defCatVatId > 0)
				$arFields['VAT_ID'] = $defCatVatId;

			// add crm iblock to catalog
			$dbRes = $CCatalog->GetList(array(), array('ID' => $catalogId), false, false, array('ID'));
			if (!$dbRes->Fetch())    // if catalog iblock is not exists
			{
				if ($CCatalog->Add($arFields))
				{
					COption::SetOptionString('catalog', 'save_product_without_price', 'Y');
					COption::SetOptionString('catalog', 'default_can_buy_zero', 'Y');
				}
				else
				{
					$errMsg[] = Loc::getMessage('CRM_UPDATE_ERR_002');
					$bError = true;
					return;
				}
			}
			unset($dbRes);
		}

		// transfer crm products to catalog
		if ($basePriceId > 0)
		{
			if (COption::GetOptionString('crm', '~CRM_INVOICE_PRODUCTS_CONVERTED_12_5_7', 'N') !== 'Y')
			{
				if (
					$DB->TableExists('b_crm_product') &&
					$DB->TableExists('b_catalog_product') &&
					$DB->TableExists('b_catalog_price') &&
					$DB->TableExists('b_catalog_group')
				)
				{
					// update iblock element xml_id
					$local_err = 0;
					$strSql = '';
					switch(strtoupper($DBType))
					{
						case 'MYSQL':
							$strSql = PHP_EOL.
								'UPDATE b_iblock_element IB'.PHP_EOL.
								"\t".'INNER JOIN b_crm_product CP ON IB.ID = CP.ID'.PHP_EOL.
								'SET IB.XML_ID = CONCAT(IFNULL(CP.ORIGINATOR_ID, \'\'), \'#\', IFNULL(CP.ORIGIN_ID, \'\'))'.PHP_EOL;
							break;
						case 'ORACLE':
							$strSql = PHP_EOL.
								'UPDATE b_iblock_element UPD'.PHP_EOL.
								'SET UPD.XML_ID ='.PHP_EOL.
								"\t".'('.PHP_EOL.
								"\t\t".'SELECT NVL(CP.ORIGINATOR_ID, \'\')||\'#\'||NVL(CP.ORIGIN_ID, \'\')'.PHP_EOL.
								"\t\t".'FROM b_crm_product CP'.PHP_EOL.
								"\t\t".'WHERE CP.ID = UPD.ID'.PHP_EOL.
								"\t".')'.PHP_EOL.
								'WHERE UPD.ID IN (SELECT CPI.ID FROM b_crm_product CPI)'.PHP_EOL;
							break;
						case 'MSSQL':
							$strSql = PHP_EOL.
								'UPDATE IB'.PHP_EOL.
								"\t".'SET IB.XML_ID = ISNULL(CP.ORIGINATOR_ID, \'\') + \'#\' + ISNULL(CP.ORIGIN_ID, \'\')'.PHP_EOL.
								"\t".'FROM b_iblock_element IB'.PHP_EOL.
								"\t\t".'INNER JOIN b_crm_product CP ON IB.ID = CP.ID'.PHP_EOL;
							break;
					}

					if (!$strSql || !$DB->Query($strSql, true))
						$local_err = 1;

					if (!$local_err)
					{
						// insert catalog products
						$strSql = '';
						switch(strtoupper($DBType))
						{
							case 'MYSQL':
							case 'ORACLE':
							case 'MSSQL':
								$strSql = PHP_EOL.
									'INSERT INTO b_catalog_product (ID, QUANTITY, QUANTITY_TRACE, RECUR_SCHEME_LENGTH, RECUR_SCHEME_TYPE, VAT_ID, VAT_INCLUDED, CAN_BUY_ZERO)'.PHP_EOL.
									"\t".'SELECT CP.ID, 0, \'D\', 0, \'D\', '.intval($defCatVatId).', \'N\', \'D\' FROM b_crm_product CP'.PHP_EOL.
									"\t".'WHERE ID NOT IN (SELECT CTP.ID FROM b_catalog_product CTP)'.PHP_EOL;
								break;
						}
						if (!$strSql || !$DB->Query($strSql, true))
							$local_err = 2;
					}

					if (!$local_err)
					{
						//set base prices
						$strSql = '';
						switch(strtoupper($DBType))
						{
							case 'MYSQL':
							case 'ORACLE':
							case 'MSSQL':
							$strSql = PHP_EOL.
								'INSERT INTO b_catalog_price (PRODUCT_ID, CATALOG_GROUP_ID, PRICE, CURRENCY)'.PHP_EOL.
								"\t".'SELECT CP.ID, '.$basePriceId.', CP.PRICE, CP.CURRENCY_ID FROM b_crm_product CP'.PHP_EOL.
								"\t".'WHERE ID NOT IN (SELECT CPR.PRODUCT_ID FROM b_catalog_price CPR WHERE CPR.CATALOG_GROUP_ID = '.$basePriceId.')'.PHP_EOL;
								break;
						}
						if (!$strSql || !$DB->Query($strSql, true))
							$local_err = 3;
					}

					if ($local_err)
					{
						$errMsg[] = Loc::getMessage('CRM_UPDATE_ERR_006').' ('.$local_err.')';
						$bError = true;
						return;
					}
					unset($local_err);

					COption::SetOptionString('crm', '~CRM_INVOICE_PRODUCTS_CONVERTED_12_5_7', 'Y');
				}
				else
				{
					$errMsg[] = Loc::getMessage('CRM_UPDATE_ERR_005');
					$bError = true;
					return;
				}
			}
		}
		else
		{
			$errMsg[] = Loc::getMessage('CRM_UPDATE_ERR_004');
			$bError = true;
			return;
		}
	}
}

if(!$bError)
{
	//Copy perms from deals to invoices
	$CCrmRole = new CCrmRole();
	$dbRoles = $CCrmRole->GetList();

	while($arRole = $dbRoles->Fetch())
	{
		$arPerms = $CCrmRole->GetRolePerms($arRole['ID']);

		if(!isset($arPerms['INVOICE']) && is_array($arPerms['DEAL']))
		{
			foreach ($arPerms['DEAL'] as $key => $value)
			{
				if(isset($value['-']) && $value['-'] != 'O')
					$arPerms['INVOICE'][$key]['-'] = $value['-'];
				else
					$arPerms['INVOICE'][$key]['-'] = 'X';
			}
		}

		$arFields = array('RELATION' => $arPerms);
		$CCrmRole->Update($arRole['ID'], $arFields);
	}
}