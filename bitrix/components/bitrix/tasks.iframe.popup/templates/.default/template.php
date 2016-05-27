<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$this->setFrameMode(true);
?>

<script>
	BX.Tasks.Component.IframePopup.create(<?=CUtil::PhpToJsObject(array(
		'pathToTasks' => $arParams["PATH_TO_TASKS"],
        'callbacks' => $arResult["CALLBACKS"]
	))?>);
</script>