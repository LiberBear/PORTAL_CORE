<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
?>

<div id="bx-mobile-interface-fields-block" class="mobile-grid-field-list">
	<span class="mobile-grid-field-title"><?=GetMessage("M_FIELDS_TITLE")?></span>
	<?foreach($arResult['ALL_FIELDS'] as $field):?>
		<div data-id="<?=$field["id"]?>" data-role="mobile-grid-field-item" class="mobile-grid-field <?if (in_array($field["id"], $arResult["SELECTED_FIELDS"])) echo 'mobile-grid-field-selected'?>">
			<div class="mobile-grid-field-textarea"><span class="mobile-grid-field-textarea-select"></span><?=$field["name"]?></div>
		</div>
	<?endforeach?>

	<div class="mobile-grid-field mobile-grid-field-fixed">
		<div class="mobile-grid-button-panel">
			<a href="javascript:void(0)" ontouchstart="BX.Mobile.Grid.Fields.apply()"><?=GetMessage("M_FIELDS_BUTTON")?></a>
			<span class="mobile-grid-button-panel-divider"></span>
			<a href="javascript:void(0)" ontouchstart="BXMobileApp.UI.Page.close();"><?=GetMessage("M_FIELDS_CANCEL")?></a>
		</div>
	</div>
</div>

<?
$arJsParams = array(
	"gridId" => $arParams["GRID_ID"],
	"eventName" => $arResult['EVENT_NAME']
);
?>
<script>
	app.pullDown({
		enable:   true,
		pulltext: '<?=GetMessageJS('M_FIELDS_PULL_TEXT');?>',
		downtext: '<?=GetMessageJS('M_FIELDS_DOWN_TEXT');?>',
		loadtext: '<?=GetMessageJS('M_FIELDS_LOAD_TEXT');?>',
		callback: function()
		{
			app.reload();
		}
	});
	BXMobileApp.UI.Page.TopBar.title.setText('<?=GetMessageJS("M_FIELDS_TITLE")?>');
	BXMobileApp.UI.Page.TopBar.title.show();

	BX.Mobile.Grid.Fields.init(<?=CUtil::PhpToJSObject($arJsParams)?>);
</script>
