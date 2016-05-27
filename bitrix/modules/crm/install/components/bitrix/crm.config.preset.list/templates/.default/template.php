<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

/** @var array $arParams */
/** @var array $arResult */
/** @var \Bitrix\Crm\PresetListComponent $component */
/** @global CMain $APPLICATION */
global $APPLICATION;

$presetListManagerId = 'PresetListManager_'.$arResult['COMPONENT_ID'];
$presetAddFormId = 'PresetListManager_'.$arResult['COMPONENT_ID'].'_FormPresetAdd';

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.toolbar',
	'',
	array(
		'BUTTONS'=>array(
			array(
				'TEXT' => GetMessage('CRM_PRESET_TOOLBAR_ADD'),
				'TITLE' => GetMessage('CRM_PRESET_TOOLBAR_ADD_TITLE'),
				'LINK' => htmlspecialcharsbx('javascript:BX.Crm["'.CUtil::JSEscape($presetListManagerId).'"].addPreset();'),
				'ICON' => 'btn-add-field'
			)
		)
	),
	$component, array('HIDE_ICONS' => 'Y')
);

$rows = array();
foreach($arResult['LIST_DATA'] as $key => &$listRow)
{
	$row = array();
	foreach ($listRow as $fName => $fValue)
	{
		if(is_array($fValue))
			$row[$fName] = htmlspecialcharsEx($fValue);
		elseif(preg_match("/[;&<>\"]/", $fValue))
			$row[$fName] = htmlspecialcharsEx($fValue);
		else
			$row[$fName] = $fValue;
		$row["~".$fName] = $fValue;
	}
	$presetEditUrl = str_replace(
		array('#entity_type#', '#preset_id#'),
		array($arResult['ENTITY_TYPE_ID'], $key),
		$arResult['PRESET_EDIT_URL']
	);
	$countryValue = (int)$listRow['COUNTRY_ID'];
	if (isset($arResult['COUNTRY_LIST'][$countryValue]))
		$countryValue = $arResult['COUNTRY_LIST'][$countryValue];
	$gridDataItem = array(
		'id' => $key,
		'data' => $row,
		'actions' => array(
			array(
				'ICONCLASS' => 'list',
				'TEXT' => GetMessage('CRM_PRESET_LIST_ACTION_MENU_FIELD_LIST'),
				'ONCLICK' => "jsUtils.Redirect(arguments, '".CUtil::JSEscape($presetEditUrl)."')",
				'DEFAULT' => true
			),
			array(
				'ICONCLASS' => 'edit',
				'TEXT' => GetMessage('CRM_PRESET_LIST_ACTION_MENU_EDIT'),
				'ONCLICK' => 'javascript:BX.Crm["'.CUtil::JSEscape($presetListManagerId).'"].editPreset('.$key.');'
			),
			array(
				'ICONCLASS' => 'delete',
				'TEXT' => GetMessage('CRM_PRESET_LIST_ACTION_MENU_DELETE'),
				'ONCLICK' => "bxGrid_".$arResult['GRID_ID'].".DeleteItem('".$key."', '".GetMessage("CRM_PRESET_LIST_ACTION_MENU_DELETE_CONF")."')"
			)
		),
		'columns' => array(
			/*'NAME' => '<a target="_self" href="'.$presetEditUrl.'">'.htmlspecialcharsbx($listRow['NAME']).'</a>',*/
			'NAME' => '<span style="color: #2067b0; cursor: pointer;" onclick="'.htmlspecialcharsbx('javascript:BX.Crm["'.CUtil::JSEscape($presetListManagerId).'"].editPreset('.$key.');').'">'.htmlspecialcharsbx($listRow['NAME']).'</span>',
			'ACTIVE' => $listRow['ACTIVE'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
			'COUNTRY_ID' => $countryValue
		),
		'editable' => array()
	);
	$rows[] = $gridDataItem;
	unset($gridDataItem);
}
unset($listRow);

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $rows,
		'ACTIONS' => array('delete' => true),
		'ACTION_ALL_ROWS' => true,
		'FOOTER' => array(array('title' => GetMessage('CRM_ALL'), 'value' => $arResult['ROWS_COUNT'])),
		'NAV_OBJECT' => $arResult['NAV_OBJECT'],
		'AJAX_MODE' => $arResult['AJAX_MODE'],
		'AJAX_OPTION_SHADOW' => $arResult['AJAX_OPTION_SHADOW'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP']
	),
	$component, array('HIDE_ICONS' => 'Y')
);
?>
<form id="<?= $presetAddFormId ?>" action="<?= POST_FORM_ACTION_URI ?>" method="POST" enctype="multipart/form-data">
	<?= bitrix_sessid_post(); ?>
	<input type="hidden" name="CREATE_NEW" value="Y">
	<input type="hidden" name="FIXED_PRESET_ID" value="0">
	<input type="hidden" name="ID" value="0">
	<input type="hidden" name="NAME" value="">
	<input type="hidden" name="ACTIVE" value="">
	<input type="hidden" name="SORT" value="">
	<input type="hidden" name="COUNTRY_ID" value="">
	<input type="hidden" name="action" value="">
</form>
<script type="text/javascript">
	BX.namespace("BX.Crm");
	var presetListManagerId = "<?= CUtil::JSEscape($presetListManagerId) ?>";
	BX.Crm[presetListManagerId] = new BX.Crm.PresetListManagerClass({
		id: presetListManagerId,
		componentId: "<?= CUtil::JSEscape($arResult['COMPONENT_ID']) ?>",
		/*gridId: "<?= CUtil::JSEscape($arResult['GRID_ID']) ?>",
		gridAjaxMode: "<?= ($arResult['AJAX_MODE'] === 'Y' ? 'Y' : 'N') ?>",
		gridUrl: "<?= CUtil::JSEscape(str_replace(
			array('#entity_type#'),
			array($arResult['ENTITY_TYPE_ID']),
			$arResult['PRESET_LIST_URL']
		)) ?>",*/
		formId: "<?= CUtil::JSEscape($presetAddFormId) ?>",
		fixedPresetSelectItems: <?= CUtil::PhpToJSObject($arResult['FIXED_PRESET_SELECT_ITEMS']) ?>,
		presetData: <?= CUtil::PhpToJSObject($arResult['LIST_DATA']) ?>,
		messages: {
			"presetAddDialogTitle": "<?= GetMessageJS('CRM_PRESET_TOOLBAR_ADD') ?>",
			"presetEditDialogTitle": "<?= GetMessageJS('CRM_PRESET_TOOLBAR_EDIT') ?>",
			"emptyNameError": "<?= GetMessageJS('CRM_ADD_PRESET_DIALOG_ERR_EMPTY_NAME') ?>",
			"longNameError": "<?= GetMessageJS('CRM_ADD_PRESET_DIALOG_ERR_LONG_NAME') ?>",
			"createNewTitle": "<?= GetMessageJS('CRM_ADD_PRESET_DIALOG_CREATE_NEW_TITLE') ?>",
			"fixedPresetFieldTitle": "<?= GetMessageJS('CRM_ADD_PRESET_DIALOG_FIXED_PRESET_TITLE') ?>",
			"createSelectedTitle": "<?= GetMessageJS('CRM_ADD_PRESET_DIALOG_CREATE_SELECTED_TITLE') ?>",
			"nameFieldTitle": "<?= GetMessageJS('CRM_ADD_PRESET_DIALOG_NAME_TITLE') ?>",
			"activeFieldTitle": "<?= GetMessageJS('CRM_ADD_PRESET_DIALOG_ACTIVE_TITLE') ?>",
			"sortFieldTitle": "<?= GetMessageJS('CRM_ADD_PRESET_DIALOG_SORT_TITLE') ?>",
			"addBtnText": "<?= GetMessageJS('CRM_ADD_PRESET_DIALOG_BTN_ADD_TEXT') ?>",
			"editBtnText": "<?= GetMessageJS('CRM_ADD_PRESET_DIALOG_BTN_EDIT_TEXT') ?>",
			"cancelBtnText": "<?= GetMessageJS('CRM_ADD_PRESET_DIALOG_BTN_CANCEL_TEXT') ?>",
			"defaultActive": "Y",
			"defaultSort": "500",
			"defaultCountryId": <?= CUtil::JSEscape($arResult['CURRENT_COUNTRY_ID']) ?>
		}
	});
</script>