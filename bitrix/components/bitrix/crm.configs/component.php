<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

if (!CModule::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

if(IsModuleInstalled('bitrix24'))
	$arResult['BITRIX24'] = true;
else
	$arResult['BITRIX24'] = false;

$arResult['PERM_CONFIG'] = false;
$arResult['IS_ACCESS_ENABLED'] = false;
$crmPerms = CCrmPerms::getCurrentUserPermissions();
if(!$crmPerms->HavePerm('CONFIG', BX_CRM_PERM_NONE))
	$arResult['PERM_CONFIG'] = true;
if($crmPerms->IsAccessEnabled())
	$arResult['IS_ACCESS_ENABLED'] = true;

$arResult['RAND_STRING'] = $this->randString();

$APPLICATION->SetTitle(GetMessage('CRM_TITLE'));
$this->includeComponentTemplate();