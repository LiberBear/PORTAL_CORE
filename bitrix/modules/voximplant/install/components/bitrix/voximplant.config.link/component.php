<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (!CModule::IncludeModule('voximplant'))
	return;

if (check_bitrix_sessid() && $_POST['vi_link_form'])
{
	CVoxImplantConfig::SetLinkCallRecord(isset($_POST['vi_link_call_record']));
	CVoxImplantConfig::SetLinkCheckCrm(isset($_POST['vi_link_check_crm']));

	$viHttp = new CVoxImplantHttp();
	$viHttp->ClearConfigCache();
}

$arResult = CVoxImplantPhone::GetCallerId();
$arResult['LINK_CALL_RECORD'] = CVoxImplantConfig::GetLinkCallRecord();
$arResult['LINK_CHECK_CRM'] = CVoxImplantConfig::GetLinkCheckCrm();

$arResult['RECORD_LIMIT'] = \CVoxImplantAccount::GetRecordLimit();

$arResult["TRIAL_TEXT"] = '';
if (!CVoxImplantAccount::IsPro() || CVoxImplantAccount::IsDemo())
{
	$arResult["TRIAL_TEXT"] = CVoxImplantMain::GetTrialText();
}

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>