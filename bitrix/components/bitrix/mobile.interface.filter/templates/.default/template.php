<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

if (is_array($arResult['FIELDS']) && !empty($arResult['FIELDS']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.form',
		'mobile',
		array(
			'FORM_ID' => "mobile_iterface_filter",
			'TABS' => array(array(
				"fields" => $arResult['FIELDS']
			)),
			'DATE_FORMAT' => FORMAT_DATE,
			"BUTTONS" => array(
				"standard_buttons" => false,
				"custom_html" => "
					<a href='javascript:void(0)' onclick='BX.Mobile.Grid.Filter.apply();'>".GetMessage("M_FILTER_BUTTON_APPLY")."</a>
					<a href='javascript:void(0)' onclick='BXMobileApp.UI.Page.close();'>".GetMessage("M_FILTER_BUTTON_CANCEL")."</a>"
			),
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}
$arJsParams = array(
	"gridId" => $arParams["GRID_ID"],
	"eventName" => $arResult['EVENT_NAME'],
	"ajaxPath" => "/mobile/?mobile_action=mobile_grid_filter",
	"formId" => "mobile_iterface_filter",
	"formFields" => $arResult['FIELDS_ID']
);
?>
<script>
	app.pullDown({
		enable:   true,
		pulltext: '<?=GetMessageJS('M_FILTER_PULL_TEXT');?>',
		downtext: '<?=GetMessageJS('M_FILTER_DOWN_TEXT');?>',
		loadtext: '<?=GetMessageJS('M_FILTER_LOAD_TEXT');?>',
		callback: function()
		{
			app.reload();
		}
	});
	BXMobileApp.UI.Page.TopBar.title.setText('<?=GetMessageJS("M_FILTER_TITLE")?>');
	BXMobileApp.UI.Page.TopBar.title.show();

	BX.Mobile.Grid.Filter.init(<?=CUtil::PhpToJSObject($arJsParams)?>);
</script>

