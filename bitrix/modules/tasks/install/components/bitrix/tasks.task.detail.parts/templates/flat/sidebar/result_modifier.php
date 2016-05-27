<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */

$taskData = $arParams["TEMPLATE_DATA"]["DATA"]["TASK"];

$arParams["TEMPLATE_DATA"]["PATH_TO_TEMPLATES_TEMPLATE"] = \Bitrix\Tasks\UI\Task\Template::makeActionUrl($arParams["PATH_TO_TEMPLATES_TEMPLATE"], $taskData["SE_TEMPLATE"]["ID"], 'edit');
$arParams["TEMPLATE_DATA"]["PATH_TO_TEMPLATES_TEMPLATE_SOURCE"] = \Bitrix\Tasks\UI\Task\Template::makeActionUrl($arParams["PATH_TO_TEMPLATES_TEMPLATE"], $taskData["SE_TEMPLATE.SOURCE"]["ID"], 'edit');

$arParams["TEMPLATE_DATA"]["TAGS"] = \Bitrix\Tasks\UI\Task\Tag::formatTagString($taskData["SE_TAG"]);

//Dates
$dates = array(
	"STATUS_CHANGED_DATE",
	"DEADLINE",
	"CREATED_DATE",
	"START_DATE_PLAN",
	"END_DATE_PLAN"
);

$format = preg_replace("/:s/", "", $DB->DateFormatToPHP(CSite::GetDateFormat("FULL")));
foreach ($dates as $date)
{
	$formattedDate = "";
	if (isset($taskData[$date]) && strlen($taskData[$date]))
	{
		$formattedDate = FormatDate($format, MakeTimeStamp($taskData[$date]));
	}
	
	$arParams["TEMPLATE_DATA"][$date] = $formattedDate;
}
