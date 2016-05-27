<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/activity.js');
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/interface_grid.js');

if($arResult['NEED_FOR_REBUILD_DUP_INDEX']):
	?><div id="rebuildCompanyDupIndexMsg" class="crm-view-message">
		<?=GetMessage('CRM_COMPANY_REBUILD_DUP_INDEX', array('#ID#' => 'rebuildCompanyDupIndexLink', '#URL#' => '#'))?>
	</div><?
endif;

if($arResult['NEED_FOR_REBUILD_COMPANY_ATTRS']):
	?><div id="rebuildCompanyAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_COMPANY_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildCompanyAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
	</div><?
endif;

if($arResult['NEED_FOR_TRANSFER_REQUISITES']):
	?><div id="transferRequisitesMsg" class="crm-view-message">
		<?=Bitrix\Crm\Requisite\EntityRequisiteConverter::getIntroMessage(
			array(
				'EXEC_ID' => 'transferRequisitesLink', 'EXEC_URL' => '#',
				'SKIP_ID' => 'skipTransferRequisitesLink', 'SKIP_URL' => '#'
			)
		)?>
	</div><?
endif;

$currentUserID = $arResult['CURRENT_USER_ID'];
$isInternal = $arResult['INTERNAL'];
$activityEditorID = '';
if(!$isInternal):
	$activityEditorID = "{$arResult['GRID_ID']}_activity_editor";
	$APPLICATION->IncludeComponent(
		'bitrix:crm.activity.editor',
		'',
		array(
			'EDITOR_ID' => $activityEditorID,
			'PREFIX' => $arResult['GRID_ID'],
			'OWNER_TYPE' => 'COMPANY',
			'OWNER_ID' => 0,
			'READ_ONLY' => false,
			'ENABLE_UI' => false,
			'ENABLE_TOOLBAR' => false
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
endif;
$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$gridManagerCfg = array(
	'ownerType' => 'COMPANY',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'activityEditorId' => $activityEditorID,
	'serviceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'filterFields' => array()
);
$prefix = $arResult['GRID_ID'];
?>
<script type="text/javascript">
function crm_company_delete_grid(title, message, btnTitle, path)
{
	var d;
	d = new BX.CDialog({
		title: title,
		head: '',
		content: message,
		resizable: false,
		draggable: true,
		height: 70,
		width: 300
	});

	var _BTN = [
		{
			title: btnTitle,
			id: 'crmOk',
			'action': function ()
			{
				window.location.href = path;
				BX.WindowManager.Get().Close();
			}
		},
		BX.CDialog.btnCancel
	];
	d.ClearButtons();
	d.SetButtons(_BTN);
	d.Show();
}

BX.ready(function() {
	if (BX('actallrows_<?=$arResult['GRID_ID']?>')) {
		BX.bind(BX('actallrows_<?=$arResult['GRID_ID']?>'), 'click', function () {
			var el_t = BX.findParent(this, {tagName : 'table'});
			var el_s = BX.findChild(el_t, {tagName : 'select'}, true, false);
			for (i = 0; i < el_s.options.length; i++)
			{
				if (el_s.options[i].value == 'tasks' || el_s.options[i].value == 'calendar')
					el_s.options[i].disabled = this.checked;
			}
			if (this.checked && (el_s.options[el_s.selectedIndex].value == 'tasks' || el_s.options[el_s.selectedIndex].value == 'calendar'))
				el_s.selectedIndex = 0;
		});
	}
});
</script>
<?
	for ($i=0; $i < sizeof($arResult['FILTER']); $i++)
	{
		$filterField = $arResult['FILTER'][$i];
		$filterID = $filterField['id'];
		$filterType = $filterField['type'];
		$enable_settings = $filterField['enable_settings'];

		if ($filterType !== 'user')
		{
			continue;
		}

		$userID = isset($arResult['DB_FILTER'][$filterID])
			? (intval(is_array($arResult['DB_FILTER'][$filterID])
				? $arResult['DB_FILTER'][$filterID][0]
				: $arResult['DB_FILTER'][$filterID]))
			: 0;
		$userName = $userID > 0 ? CCrmViewHelper::GetFormattedUserName($userID) : '';

		ob_start();
		CCrmViewHelper::RenderUserCustomSearch(
			array(
				'ID' => "{$prefix}_{$filterID}_SEARCH",
				'SEARCH_INPUT_ID' => "{$prefix}_{$filterID}_NAME",
				'SEARCH_INPUT_NAME' => "{$filterID}_name",
				'DATA_INPUT_ID' => "{$prefix}_{$filterID}",
				'DATA_INPUT_NAME' => $filterID,
				'COMPONENT_NAME' => "{$prefix}_{$filterID}_SEARCH",
				'SITE_ID' => SITE_ID,
				'NAME_FORMAT' => $arParams['NAME_TEMPLATE'],
				'USER' => array('ID' => $userID, 'NAME' => $userName),
				'DELAY' => 100
			)
		);
		$val = ob_get_clean();

		$arResult["FILTER"][$i]["type"] = "custom";
		$arResult['FILTER'][$i]['value'] = $val;

		$filterFieldInfo = array(
			'typeName' => 'USER',
			'id' => $filterID,
			'params' => array(
				'data' => array(
					'paramName' => "{$filterID}",
					'elementId' => "{$prefix}_{$filterID}"
				),
				'search' => array(
					'paramName' => "{$filterID}_name",
					'elementId' => "{$prefix}_{$filterID}_NAME"
				)
			)
		);

		if($enable_settings)
		{
			ob_start();
			CCrmViewHelper::RenderUserCustomSearch(
				array(
					'ID' => "FILTER_SETTINGS_{$prefix}_{$filterID}_SEARCH",
					'SEARCH_INPUT_ID' => "FILTER_SETTINGS_{$prefix}_{$filterID}_NAME",
					'SEARCH_INPUT_NAME' => "{$filterID}_name",
					'DATA_INPUT_ID' => "FILTER_SETTINGS_{$prefix}_{$filterID}",
					'DATA_INPUT_NAME' => $filterID,
					'COMPONENT_NAME' => "FILTER_SETTINGS_{$prefix}_{$filterID}_SEARCH",
					'SITE_ID' => SITE_ID,
					'NAME_FORMAT' => $arParams['NAME_TEMPLATE'],
					'USER' => array('ID' => $userID, 'NAME' => $userName),
					'ZINDEX' => 4000,
					'DELAY' => 100
				)
			);
			$arResult['FILTER'][$i]['settingsHtml'] = ob_get_clean();

			$filterFieldInfo['params']['data']['settingsElementId'] = "FILTER_SETTINGS_{$prefix}_{$filterID}";
			$filterFieldInfo['params']['search']['settingsElementId'] = "FILTER_SETTINGS_{$prefix}_{$filterID}_NAME";
		}

		$gridManagerCfg['filterFields'][] = $filterFieldInfo;
	}

	$arResult['GRID_DATA'] = array();
	$arColumns = array();
	foreach ($arResult['HEADERS'] as $arHead)
		$arColumns[$arHead['id']] = false;
	foreach($arResult['COMPANY'] as $sKey =>  $arCompany)
	{
		$arActivityMenuItems = array();
		$arActions = array();
		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_COMPANY_SHOW_TITLE'),
			'TEXT' => GetMessage('CRM_COMPANY_SHOW'),
			'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arCompany['PATH_TO_COMPANY_SHOW'])."');",
			'DEFAULT' => true
		);
		if ($arCompany['EDIT']):
			$arActions[] =  array(
				'ICONCLASS' => 'edit',
				'TITLE' => GetMessage('CRM_COMPANY_EDIT_TITLE'),
				'TEXT' => GetMessage('CRM_COMPANY_EDIT'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arCompany['PATH_TO_COMPANY_EDIT'])."');"
			);
			$arActions[] =  array(
				'ICONCLASS' => 'copy',
				'TITLE' => GetMessage('CRM_COMPANY_COPY_TITLE'),
				'TEXT' => GetMessage('CRM_COMPANY_COPY'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arCompany['PATH_TO_COMPANY_COPY'])."');"
			);
		endif;
		if(!$isInternal):
			$arActions[] = array('SEPARATOR' => true);

			$arActions[] = $arActivityMenuItems[] = array(
				'ICONCLASS' => 'event',
				'TITLE' => GetMessage('CRM_COMPANY_EVENT_TITLE'),
				'TEXT' => GetMessage('CRM_COMPANY_EVENT'),
				'ONCLICK' => "javascript:(new BX.CDialog({'content_url':'/bitrix/components/bitrix/crm.event.add/box.php?FORM_TYPE=LIST&ENTITY_TYPE=COMPANY&ENTITY_ID=".$arCompany['ID']."', 'width':'498', 'height':'245', 'resizable':false })).Show();"
			);

			if ($arCompany['EDIT'] && IsModuleInstalled('tasks')):
				$arActions[] = $arActivityMenuItems[] = array(
					'ICONCLASS' => 'task',
					'TITLE' => GetMessage('CRM_COMPANY_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_COMPANY_TASK'),
					'ONCLICK' => 'BX.CrmInterfaceGridManager.addTask("'.CUtil::JSEscape($gridManagerID).'", { "ownerID":'.$arCompany['ID'].' })'
				);
			endif;
			if ($arCompany['EDIT'] && IsModuleInstalled('subscribe')):
				$arActions[] = $arActivityMenuItems[] = array(
					'ICONCLASS' => 'subscribe',
					'TITLE' => GetMessage('CRM_COMPANY_ADD_EMAIL_TITLE'),
					'TEXT' => GetMessage('CRM_COMPANY_ADD_EMAIL'),
					'ONCLICK' => 'BX.CrmInterfaceGridManager.addEmail("'.CUtil::JSEscape($gridManagerID).'", { "ownerID":'.$arCompany['ID'].' })'
				);
			endif;
			if ($arCompany['EDIT'] && IsModuleInstalled(CRM_MODULE_CALENDAR_ID)):
				$arActions[] = $arActivityMenuItems[] = array(
					'ICONCLASS' => 'calendar',
					'TITLE' => GetMessage('CRM_COMPANY_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_COMPANY_ADD_CALL'),
					'ONCLICK' => 'BX.CrmInterfaceGridManager.addCall("'.CUtil::JSEscape($gridManagerID).'", { "ownerID":'.$arCompany['ID'].' })'
				);

				$arActions[] = $arActivityMenuItems[] = array(
					'ICONCLASS' => 'calendar',
					'TITLE' => GetMessage('CRM_COMPANY_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_COMPANY_ADD_MEETING'),
					'ONCLICK' => 'BX.CrmInterfaceGridManager.addMeeting("'.CUtil::JSEscape($gridManagerID).'", { "ownerID":'.$arCompany['ID'].' })'
				);
			endif;
		endif;
		$bSep = false;
		if ($arResult['PERM_CONTACT']):
			$bSep = true;
			$arActions[] = array('SEPARATOR' => true);
			$arActions[] =  array(
				'ICONCLASS' => 'contact',
				'TITLE' => GetMessage('CRM_COMPANY_CONTACT_ADD_TITLE'),
				'TEXT' => GetMessage('CRM_COMPANY_CONTACT_ADD'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arCompany['PATH_TO_CONTACT_EDIT'])."');"
			);
		endif;
		if ($arResult['PERM_DEAL']):
			if (!$bSep)
				$arActions[] = array('SEPARATOR' => true);
			$arActions[] =  array(
				'ICONCLASS' => 'deal',
				'TITLE' => GetMessage('CRM_COMPANY_DEAL_ADD_TITLE'),
				'TEXT' => GetMessage('CRM_COMPANY_DEAL_ADD'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arCompany['PATH_TO_DEAL_EDIT'])."');"
			);
		endif;
		if ($arResult['PERM_QUOTE'] && IsModuleInstalled('sale')):
			$arActions[] = array(
				'ICONCLASS' => 'quote',
				'TITLE' => GetMessage('CRM_COMPANY_ADD_QUOTE_TITLE'),
				'TEXT' => GetMessage('CRM_COMPANY_ADD_QUOTE'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arCompany['PATH_TO_QUOTE_ADD'])."');"
			);
		endif;
		if ($arResult['PERM_INVOICE'] && IsModuleInstalled('sale')):
			$arActions[] = array(
				'ICONCLASS' => 'invoice',
				'TITLE' => GetMessage('CRM_DEAL_ADD_INVOICE_TITLE'),
				'TEXT' => GetMessage('CRM_DEAL_ADD_INVOICE'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arCompany['PATH_TO_INVOICE_ADD'])."');"
			);
		endif;
		if ($arCompany['EDIT']):
			if (IsModuleInstalled('bizproc')):
				$arActions[] = array('SEPARATOR' => true);
				if(isset($arContact['PATH_TO_BIZPROC_LIST']) && $arContact['PATH_TO_BIZPROC_LIST'] !== '')
					$arActions[] =  array(
						'ICONCLASS' => 'bizproc',
						'TITLE' => GetMessage('CRM_COMPANY_BIZPROC_TITLE'),
						'TEXT' => GetMessage('CRM_COMPANY_BIZPROC'),
						'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arCompany['PATH_TO_BIZPROC_LIST'])."');"
					);
				if (!empty($arCompany['BIZPROC_LIST'])):
					$arBizprocList = array();
					foreach ($arCompany['BIZPROC_LIST'] as $arBizproc) :
						$arBizprocList[] = array(
							'ICONCLASS' => 'bizproc',
							'TITLE' => $arBizproc['DESCRIPTION'],
							'TEXT' => $arBizproc['NAME'],
							'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arBizproc['PATH_TO_BIZPROC_START'])."');"
						);
					endforeach;
					$arActions[] =  array(
						'ICONCLASS' => 'bizproc',
						'TITLE' => GetMessage('CRM_COMPANY_BIZPROC_LIST_TITLE'),
						'TEXT' => GetMessage('CRM_COMPANY_BIZPROC_LIST'),
						'MENU' => $arBizprocList
					);
				endif;
			endif;
		endif;
		if ($arCompany['DELETE'] && !$arResult['INTERNAL']):
			$arActions[] = array('SEPARATOR' => true);
			$arActions[] =  array(
				'ICONCLASS' => 'delete',
				'TITLE' => GetMessage('CRM_COMPANY_DELETE_TITLE'),
				'TEXT' => GetMessage('CRM_COMPANY_DELETE'),
				'ONCLICK' => "crm_company_delete_grid('".CUtil::JSEscape(GetMessage('CRM_COMPANY_DELETE_TITLE'))."', '".CUtil::JSEscape(GetMessage('CRM_COMPANY_DELETE_CONFIRM'))."', '".CUtil::JSEscape(GetMessage('CRM_COMPANY_DELETE'))."', '".CUtil::JSEscape($arCompany['PATH_TO_COMPANY_DELETE'])."')"
			);
		endif;

		$resultItem = array(
			'id' => $arCompany['ID'],
			'actions' => $arActions,
			'data' => $arCompany,
			'editable' => !$arCompany['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
			'columns' => array(
				'COMPANY_SUMMARY' => CCrmViewHelper::RenderClientSummary($arCompany['PATH_TO_COMPANY_SHOW'], $arCompany['TITLE'], $arCompany['COMPANY_TYPE_NAME'], isset($arCompany['LOGO']) ? $arCompany['LOGO'] : ''),
				'ASSIGNED_BY' => $arCompany['~ASSIGNED_BY_ID'] > 0 ?
					'<a href="'.$arCompany['PATH_TO_USER_PROFILE'].'" id="balloon_'.$arResult['GRID_ID'].'_'.$arCompany['ID'].'">'.$arCompany['ASSIGNED_BY'].'</a>'.
						'<script type="text/javascript">BX.tooltip('.$arCompany['~ASSIGNED_BY_ID'].', "balloon_'.$arResult['GRID_ID'].'_'.$arCompany['ID'].'", "");</script>'
					: '',
				'COMMENTS' => nl2br($arCompany['COMMENTS']),
				'ADDRESS' => nl2br($arCompany['ADDRESS']),
				'REVENUE' =>  '<nobr>'.number_format($arCompany['REVENUE'], 2, ',', ' ').'</nobr>',
				'COMMENTS' => htmlspecialcharsback($arCompany['COMMENTS']),
				'ADDRESS_LEGAL' => nl2br($arCompany['ADDRESS_LEGAL']),
				'BANKING_DETAILS' => nl2br($arCompany['BANKING_DETAILS']),
				'DATE_CREATE' => '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arCompany['DATE_CREATE'])).'</nobr>',
				'DATE_MODIFY' => '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arCompany['DATE_MODIFY'])).'</nobr>',
				'COMPANY_TYPE' => isset($arResult['COMPANY_TYPE_LIST'][$arCompany['COMPANY_TYPE']]) ? $arResult['COMPANY_TYPE_LIST'][$arCompany['COMPANY_TYPE']] : $arCompany['COMPANY_TYPE'],
				'CURRENCY_ID' =>  CCrmCurrency::GetCurrencyName($arCompany['CURRENCY_ID']),
				'INDUSTRY' => isset($arResult['INDUSTRY_LIST'][$arCompany['INDUSTRY']]) ? $arResult['INDUSTRY_LIST'][$arCompany['INDUSTRY']] : $arCompany['INDUSTRY'],
				'EMPLOYEES' => isset($arResult['EMPLOYEES_LIST'][$arCompany['EMPLOYEES']]) ? $arResult['EMPLOYEES_LIST'][$arCompany['EMPLOYEES']] : $arCompany['EMPLOYEES'],
				'CREATED_BY' => $arCompany['~CREATED_BY'] > 0 ?
					'<a href="'.$arCompany['PATH_TO_USER_CREATOR'].'" id="balloon_'.$arResult['GRID_ID'].'_'.$arCompany['ID'].'">'.$arCompany['CREATED_BY_FORMATTED_NAME'].'</a>'.
						'<script type="text/javascript">BX.tooltip('.$arCompany['~CREATED_BY'].', "balloon_'.$arResult['GRID_ID'].'_'.$arCompany['ID'].'", "");</script>'
					: '',
				'MODIFY_BY' => $arCompany['~MODIFY_BY'] > 0 ?
					'<a href="'.$arCompany['PATH_TO_USER_MODIFIER'].'" id="balloon_'.$arResult['GRID_ID'].'_'.$arCompany['ID'].'">'.$arCompany['MODIFY_BY_FORMATTED_NAME'].'</a>'.
						'<script type="text/javascript">BX.tooltip('.$arCompany['~MODIFY_BY'].', "balloon_'.$arResult['GRID_ID'].'_'.$arCompany['ID'].'", "");</script>'
					: ''
			) + CCrmViewHelper::RenderListMultiFields($arCompany, "COMPANY_{$arCompany['ID']}_", array('ENABLE_SIP' => true, 'SIP_PARAMS' => array('ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::CompanyName, 'ENTITY_ID' => $arCompany['ID']))) + $arResult['COMPANY_UF'][$sKey]
		);

		$userActivityID = isset($arCompany['~ACTIVITY_ID']) ? intval($arCompany['~ACTIVITY_ID']) : 0;
		$commonActivityID = isset($arCompany['~C_ACTIVITY_ID']) ? intval($arCompany['~C_ACTIVITY_ID']) : 0;
		if($userActivityID > 0)
		{
			$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
				array(
					'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Company),
					'ENTITY_ID' => $arCompany['~ID'],
					'ENTITY_RESPONSIBLE_ID' => $arCompany['~ASSIGNED_BY'],
					'GRID_MANAGER_ID' => $gridManagerID,
					'ACTIVITY_ID' => $userActivityID,
					'ACTIVITY_SUBJECT' => isset($arCompany['~ACTIVITY_SUBJECT']) ? $arCompany['~ACTIVITY_SUBJECT'] : '',
					'ACTIVITY_TIME' => isset($arCompany['~ACTIVITY_TIME']) ? $arCompany['~ACTIVITY_TIME'] : '',
					'ACTIVITY_EXPIRED' => isset($arCompany['~ACTIVITY_EXPIRED']) ? $arCompany['~ACTIVITY_EXPIRED'] : '',
					'ALLOW_EDIT' => $arCompany['EDIT'],
					'MENU_ITEMS' => $arActivityMenuItems
				)
			);

			$counterData = array(
				'CURRENT_USER_ID' => $currentUserID,
				'ENTITY' => $arCompany,
				'ACTIVITY' => array(
					'RESPONSIBLE_ID' => $currentUserID,
					'TIME' => isset($arCompany['~ACTIVITY_TIME']) ? $arCompany['~ACTIVITY_TIME'] : '',
					'IS_CURRENT_DAY' => isset($arCompany['~ACTIVITY_IS_CURRENT_DAY']) ? $arCompany['~ACTIVITY_IS_CURRENT_DAY'] : false
				)
			);

			if(CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentCompanyActivies, $counterData))
			{
				$resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-deal-today');
			}
		}
		elseif($commonActivityID > 0)
		{
			$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
				array(
					'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Company),
					'ENTITY_ID' => $arCompany['~ID'],
					'ENTITY_RESPONSIBLE_ID' => $arCompany['~ASSIGNED_BY'],
					'GRID_MANAGER_ID' => $gridManagerID,
					'ACTIVITY_ID' => $commonActivityID,
					'ACTIVITY_SUBJECT' => isset($arCompany['~C_ACTIVITY_SUBJECT']) ? $arCompany['~C_ACTIVITY_SUBJECT'] : '',
					'ACTIVITY_TIME' => isset($arCompany['~C_ACTIVITY_TIME']) ? $arCompany['~C_ACTIVITY_TIME'] : '',
					'ACTIVITY_RESPONSIBLE_ID' => isset($arCompany['~C_ACTIVITY_RESP_ID']) ? intval($arCompany['~C_ACTIVITY_RESP_ID']) : 0,
					'ACTIVITY_RESPONSIBLE_LOGIN' => isset($arCompany['~C_ACTIVITY_RESP_LOGIN']) ? $arCompany['~C_ACTIVITY_RESP_LOGIN'] : '',
					'ACTIVITY_RESPONSIBLE_NAME' => isset($arCompany['~C_ACTIVITY_RESP_NAME']) ? $arCompany['~C_ACTIVITY_RESP_NAME'] : '',
					'ACTIVITY_RESPONSIBLE_LAST_NAME' => isset($arCompany['~C_ACTIVITY_RESP_LAST_NAME']) ? $arCompany['~C_ACTIVITY_RESP_LAST_NAME'] : '',
					'ACTIVITY_RESPONSIBLE_SECOND_NAME' => isset($arCompany['~C_ACTIVITY_RESP_SECOND_NAME']) ? $arCompany['~C_ACTIVITY_RESP_SECOND_NAME'] : '',
					'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
					'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
					'ALLOW_EDIT' => $arCompany['EDIT'],
					'MENU_ITEMS' => $arActivityMenuItems
				)
			);
		}
		else
		{
			$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
				array(
					'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Company),
					'ENTITY_ID' => $arCompany['~ID'],
					'ENTITY_RESPONSIBLE_ID' => $arCompany['~ASSIGNED_BY'],
					'GRID_MANAGER_ID' => $gridManagerID,
					'ALLOW_EDIT' => $arCompany['EDIT'],
					'MENU_ITEMS' => $arActivityMenuItems
				)
			);
		}

		$arResult['GRID_DATA'][] = &$resultItem;
		unset($resultItem);
	}

	$isEditable = $arResult['PERMS']['WRITE'] && !$arResult['INTERNAL'];
	$actionHtml = '';
	if($isEditable)
	{
		// Setup ASSIGNED_BY_ID -->
		ob_start();
		$prefix = $arResult['GRID_ID'];
		CCrmViewHelper::RenderUserSearch(
			"{$prefix}_ACTION_ASSIGNED_BY",
			"ACTION_ASSIGNED_BY_SEARCH",
			"ACTION_ASSIGNED_BY_ID",
			"{$prefix}_ACTION_ASSIGNED_BY",
			SITE_ID,
			$arParams['~NAME_TEMPLATE'],
			500
		);
		$actionHtml .= '<div id="ACTION_ASSIGNED_BY_WRAPPER" style="display:none;">'.ob_get_clean().'</div>';
		// <-- Setup ASSIGNED_BY_ID

		// Setup OPENED -->
		$opened = '<div id="ACTION_OPENED_WRAPPER" style="display:none;"><select name="ACTION_OPENED" size="1">';
		$opened .= '<option value="Y">'.GetMessage("CRM_COMPANY_MARK_AS_OPENED_YES").'</option>';
		$opened .= '<option value="N">'.GetMessage("CRM_COMPANY_MARK_AS_OPENED_NO").'</option>';
		$opened .= '</select></div>';
		$actionHtml .= $opened;
		// Setup OPENED -->

		$actionHtml .= '
		<script type="text/javascript">
			BX.ready(
				function(){
				var select = BX.findChild(BX.findPreviousSibling(BX.findParent(BX("ACTION_ASSIGNED_BY_WRAPPER"), { "tagName":"td" })), { "tagName":"select" });
				BX.bind(
					select,
					"change",
					function(e){
						BX("ACTION_ASSIGNED_BY_WRAPPER").style.display = select.value === "assign_to" ? "" : "none";
						BX("ACTION_OPENED_WRAPPER").style.display = select.value === "mark_as_opened" ? "" : "none";
					}
				)
			}
		);
		</script>';
	}

	$arActionList = array();
	if($isEditable)
	{
		if (IsModuleInstalled('tasks'))
		{
			$arActionList['tasks'] = GetMessage('CRM_COMPANY_TASK');
		}
		if (IsModuleInstalled('subscribe'))
		{
			$arActionList['subscribe'] = GetMessage('CRM_COMPANY_SUBSCRIBE');
		}
		//if (IsModuleInstalled(CRM_MODULE_CALENDAR_ID))
		//	$arActionList['calendar'] = GetMessage('CRM_COMPANY_CALENDAR');

		if($arResult['PERMS']['WRITE'])
		{
			$arActionList['assign_to'] = GetMessage('CRM_COMPANY_ASSIGN_TO');
			$arActionList['mark_as_opened'] = GetMessage('CRM_COMPANY_MARK_AS_OPENED');
		}
	}

	//region Navigation
	$navigationHtml = '';
	if(isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION']))
	{
		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:crm.pagenavigation',
			'',
			isset($arResult['PAGINATION']) ? $arResult['PAGINATION'] : array(),
			$component,
			array('HIDE_ICONS' => 'Y')
		);
		$navigationHtml = ob_get_contents();
		ob_end_clean();
	}
	//endregion

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.grid',
		'',
		array(
			'GRID_ID' => $arResult['GRID_ID'],
			'HEADERS' => $arResult['HEADERS'],
			'SORT' => $arResult['SORT'],
			'SORT_VARS' => $arResult['SORT_VARS'],
			'ROWS' => $arResult['GRID_DATA'],
			'FOOTER' => array(
				array(
					'type' => 'row_count',
					'title' => GetMessage('CRM_ALL'),
					'show_row_count' => GetMessage('CRM_SHOW_ROW_COUNT'),
					'service_url' => '/bitrix/components/bitrix/crm.company.list/list.ajax.php?'.bitrix_sessid_get()
				),
				array('custom_html' => '<td>'.$navigationHtml.'</td>')
			),
			'EDITABLE' => $isEditable ? 'Y' : 'N',
			'ACTIONS' => array(
				'delete' => $arResult['PERMS']['DELETE'],
				'list' => $arActionList,
				'custom_html' => $actionHtml
			),
			'ACTION_ALL_ROWS' => true,
			'NAV_OBJECT' => $arResult['DB_LIST'],
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => $arResult['TAB_ID'],
			'AJAX_MODE' => $arResult['AJAX_MODE'],
			'AJAX_ID' => $arResult['AJAX_ID'],
			'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
			'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
			'AJAX_LOADER' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null,
			'FILTER' => $arResult['FILTER'],
			'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
			'MANAGER' => array(
				'ID' => $gridManagerID,
				'CONFIG' => $gridManagerCfg
			)
		),
		$component
	);
?>
<script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmSipManager.getCurrent().setServiceUrl(
					"CRM_<?=CUtil::JSEscape(CCrmOwnerType::CompanyName)?>",
					"/bitrix/components/bitrix/crm.company.show/ajax.php?<?=bitrix_sessid_get()?>"
				);

				if(typeof(BX.CrmSipManager.messages) === 'undefined')
				{
					BX.CrmSipManager.messages =
					{
						"unknownRecipient": "<?= GetMessageJS('CRM_SIP_MGR_UNKNOWN_RECIPIENT')?>",
						"enableCallRecording": "<?= GetMessageJS('CRM_SIP_MGR_ENABLE_CALL_RECORDING')?>",
						"makeCall": "<?= GetMessageJS('CRM_SIP_MGR_MAKE_CALL')?>"
					};
				}
			}
	);
</script>
<?if(!$isInternal):?>
<script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmActivityEditor.items['<?= CUtil::JSEscape($activityEditorID)?>'].addActivityChangeHandler(
						function()
						{
							BX.CrmInterfaceGridManager.reloadGrid('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
						}
				);
			}
	);
</script>
<?endif;?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmLongRunningProcessDialog.messages =
			{
				startButton: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_BTN_START')?>",
				stopButton: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_BTN_STOP')?>",
				closeButton: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_BTN_CLOSE')?>",
				wait: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_WAIT')?>",
				requestError: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_REQUEST_ERR')?>"
			};
		}
	);
</script>
<?if($arResult['NEED_FOR_REBUILD_DUP_INDEX']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmDuplicateManager.messages =
			{
				rebuildCompanyIndexDlgTitle: "<?=GetMessageJS('CRM_COMPANY_REBUILD_DUP_INDEX_DLG_TITLE')?>",
				rebuildCompanyIndexDlgSummary: "<?=GetMessageJS('CRM_COMPANY_REBUILD_DUP_INDEX_DLG_SUMMARY')?>"
			};

			var mgr = BX.CrmDuplicateManager.create("mgr", { entityTypeName: "<?=CUtil::JSEscape(CCrmOwnerType::CompanyName)?>", serviceUrl: "<?=SITE_DIR?>bitrix/components/bitrix/crm.company.list/list.ajax.php?&<?=bitrix_sessid_get()?>" });
			BX.addCustomEvent(
				mgr,
				'ON_COMPANY_INDEX_REBUILD_COMPLETE',
				function()
				{
					var msg = BX("rebuildCompanyDupIndexMsg");
					if(msg)
					{
						msg.style.display = "none";
					}
				}
			);

			var link = BX("rebuildCompanyDupIndexLink");
			if(link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						mgr.rebuildIndex();
						return BX.PreventDefault(e);
					}
				);
			}
		}
	);
</script>
<?endif;?>
<?if($arResult['NEED_FOR_REBUILD_COMPANY_ATTRS']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var link = BX("rebuildCompanyAttrsLink");
			if(link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						var msg = BX("rebuildCompanyAttrsMsg");
						if(msg)
						{
							msg.style.display = "none";
						}
					}
				);
			}
		}
	);
</script>
<?endif;?>
<?if($arResult['NEED_FOR_TRANSFER_REQUISITES']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmRequisitePresetSelectDialog.messages =
			{
				title: "<?=GetMessageJS("CRM_COMPANY_RQ_TX_SELECTOR_TITLE")?>",
				presetField: "<?=GetMessageJS("CRM_COMPANY_RQ_TX_SELECTOR_FIELD")?>"
			};

			BX.CrmRequisiteConverter.messages =
			{
				processDialogTitle: "<?=GetMessageJS('CRM_COMPANY_RQ_TX_PROC_DLG_TITLE')?>",
				processDialogSummary: "<?=GetMessageJS('CRM_COMPANY_RQ_TX_PROC_DLG_DLG_SUMMARY')?>"
			};

			var converter = BX.CrmRequisiteConverter.create(
				"converter",
				{
					entityTypeName: "<?=CUtil::JSEscape(CCrmOwnerType::CompanyName)?>",
					serviceUrl: "<?=SITE_DIR?>bitrix/components/bitrix/crm.company.list/list.ajax.php?&<?=bitrix_sessid_get()?>"
				}
			);

			BX.addCustomEvent(
				converter,
				'ON_COMPANY_REQUISITE_TRANFER_COMPLETE',
				function()
				{
					var msg = BX("transferRequisitesMsg");
					if(msg)
					{
						msg.style.display = "none";
					}
				}
			);

			var transferLink = BX("transferRequisitesLink");
			if(transferLink)
			{
				BX.bind(
					transferLink,
					"click",
					function(e)
					{
						converter.convert();
						return BX.PreventDefault(e);
					}
				);
			}

			var skipTransferLink = BX("skipTransferRequisitesLink");
			if(skipTransferLink)
			{
				BX.bind(
					skipTransferLink,
					"click",
					function(e)
					{
						converter.skip();

						var msg = BX("transferRequisitesMsg");
						if(msg)
						{
							msg.style.display = "none";
						}

						return BX.PreventDefault(e);
					}
				);
			}
		}
	);
</script>
<?endif;?>
