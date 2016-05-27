<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (!CModule::IncludeModule('voximplant'))
	return;

$ViAccount = new CVoxImplantAccount();

$arResult['LANG'] = $ViAccount->GetAccountLang();
$arResult['CURRENCY'] = $ViAccount->GetAccountCurrency();

if ( in_array($arResult['LANG'], Array('ua', 'kz')) && !isset($_GET['REFRESH']))
{
	$arResult['AMOUNT'] = 0;
}
else
{
	$arResult['AMOUNT'] = $ViAccount->GetAccountBalance(true);
}

$arResult['ERROR_MESSAGE'] = '';

if ($ViAccount->GetError()->error)
{
	$arResult['AMOUNT'] = '';
	$arResult['CURRENCY'] = '';
	if ($ViAccount->GetError()->code == 'LICENCE_ERROR')
	{
		$arResult['ERROR_MESSAGE'] = GetMessage('VI_ERROR_LICENSE');
	}
	else
	{
		$arResult['ERROR_MESSAGE'] = GetMessage('VI_ERROR');
	}
}

if (LANGUAGE_ID == "kz")
{
	$arResult['LANG'] = "kz";
}

$arResult['LINK_TO_BUY'] = '';

if (IsModuleInstalled('bitrix24'))
{
	if ($arResult['LANG'] == "ua")
	{
		$arResult['LINK_TO_BUY'] = 'https://www.bitrix24.ua/prices/telephony.php';
	}
	else if ($arResult['LANG'] != "kz")
	{
		$arResult['LINK_TO_BUY'] = '/settings/license_phone.php';
	}
}
else
{
	if ($arResult['LANG'] == 'ru')
	{
		$arResult['LINK_TO_BUY'] = 'http://www.1c-bitrix.ru/buy/intranet.php#tab-call-link';
	}
	else if ($arResult['LANG'] == 'ua')
	{
		$arResult['LINK_TO_BUY'] = 'https://www.bitrix24.ua/prices/telephony.php';
	}
	else if ($arResult['LANG'] == 'kz')
	{
	}
	else if ($arResult['LANG'] == 'de')
	{
		$arResult['LINK_TO_BUY'] = 'https://www.bitrix24.de/prices/calls.php';
	}
	else
	{
		$arResult['LINK_TO_BUY'] = 'https://www.bitrix24.com/prices/calls.php';
	}
}

$arResult['RECORD_LIMIT'] = \CVoxImplantAccount::GetRecordLimit();

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>