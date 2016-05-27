<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var CMain $APPLICATION
 * @var array $arParams
 * @var CBitrixComponentTemplate $this
 */
$this->__component->arResult = $APPLICATION->IncludeComponent(
	'bitrix:tasks.task.selector',
	'.default',
	$arParams,
	$this->__component
);