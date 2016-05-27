<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arResult["BLOCK"] = "";
$block = array_shift($arResult["BLOCKS"]);
if ($block !== "")
{
	$templateFolder = $this->GetFolder();
	$folder = $templateFolder."/".ToLower($block)."/";

	$arResult["BLOCK"] = $block;
	$arResult["TEMPLATE_FOLDER"] = $folder;
    $arResult["TEMPLATE_DATA"] = $arParams["TEMPLATE_DATA"];

    if($arResult["TEMPLATE_DATA"]["ID"] == "")
    {
        $arResult["TEMPLATE_DATA"]["ID"] = md5($folder);
    }
	$arResult["EXTENSION_ID"] = md5("tasks_component_ext_".$arResult["TEMPLATE_DATA"]["ID"]);

	$modifier = $_SERVER["DOCUMENT_ROOT"].$folder."result_modifier.php";
	if (file_exists($modifier))
	{
		require($_SERVER["DOCUMENT_ROOT"].$folder."result_modifier.php");
	}
}
