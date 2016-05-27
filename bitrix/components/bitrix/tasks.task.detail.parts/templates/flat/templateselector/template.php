<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); 

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$templateId = $arResult['TEMPLATE_DATA']['ID'];
$templates = $arResult['TEMPLATE_DATA']['DATA']['TEMPLATES'];
?>

<div id="bx-component-scope-<?=$templateId?>" class="task-template-selector"><?
	?><span data-bx-id="templateselector-open" class="webform-small-button webform-small-button-transparent task-list-toolbar-templates" title="<?=Loc::getMessage('TASKS_TTDP_TEMPLATESELECTOR_CREATE_HINT')?>"><?
		?><span class="webform-small-button-icon"></span><?
	?></span>

	<div data-bx-id="templateselector-popup-content" class="task-popup-templates hidden-soft">

		<div class="task-popup-templates-title"><?=Loc::getMessage('TASKS_TTDP_TEMPLATESELECTOR_CREATE_HINT')?></div>
		<div class="popup-window-hr"><i></i></div>
		
		<?if(\Bitrix\Tasks\Util\Type::isIterable($templates) && !empty($templates)):?>
			<ol class="task-popup-templates-items">
				<? $commonUrl = CComponentEngine::MakePathFromTemplate($arParams['TEMPLATE_DATA']["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"))?>

				<?foreach($templates as $template):?>
					<? $createUrl = $commonUrl.(strpos($commonUrl, "?") === false ? "?" : "&")."TEMPLATE=".$template["ID"];?>
					<li class="task-popup-templates-item"><a class="task-popup-templates-item-link" href="<?=htmlspecialcharsbx($createUrl)?>"><?=htmlspecialcharsbx($template["TITLE"])?></a></li>
				<?endforeach?>
			</ol>
		<?else:?>
			<div class="task-popup-templates-empty"><?=Loc::getMessage('TASKS_TTDP_TEMPLATESELECTOR_EMPTY')?></div>
		<?endif?>

		<div class="popup-window-hr"><i></i></div>
		<a class="task-popup-templates-item task-popup-templates-item-all" href="<?=htmlspecialcharsbx(CComponentEngine::MakePathFromTemplate($arParams['TEMPLATE_DATA']["PATH_TO_TASKS_TEMPLATES"], array()))?>"><?=Loc::getMessage('TASKS_TTDP_TEMPLATESELECTOR_TO_LIST')?></a>

	</div>
</div>

<script>
	new BX.Tasks.Component.TaskDetailPartsTemplateSelector(<?=CUtil::PhpToJSObject(array(
		'id' => $templateId,
	), false, false, true)?>);
</script>