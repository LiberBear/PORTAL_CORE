<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!CModule::IncludeModule('crm'))
	return ;

global $APPLICATION;

$CCrmPerms = new CCrmPerms($USER->GetID());
if (!$USER->IsAuthorized() || $CCrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE))
	return ;

$arResult = array();
$_GET['USER_ID'] = preg_replace('/^(CONTACT|COMPANY|LEAD|DEAL)_/i'.BX_UTF_PCRE_MODIFIER, '', $_GET['USER_ID']);
$iLeadId = (int) $_GET['USER_ID'];
if ($iLeadId > 0)
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
	$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
	$arResult['STATUS_LIST'] = CCrmStatus::GetStatusListEx('STATUS');

	$obRes = CCrmLead::GetListEx(array(), array('ID' => $iLeadId));
	$arLead = $obRes->Fetch();
	if ($arLead == false)
		return ;

	$arLead['PATH_TO_LEAD_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
		array(
			'lead_id' => $iLeadId
		)
	);
	$arLead['PATH_TO_LEAD_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
		array(
			'lead_id' => $iLeadId
		)
	);

	$arLead['FORMATTED_NAME'] =CCrmLead::PrepareFormattedName(
				array(
					'HONORIFIC' => isset($arLead['HONORIFIC']) ? $arLead['HONORIFIC'] : '',
					'NAME' => isset($arLead['NAME']) ? $arLead['NAME'] : '',
					'LAST_NAME' => isset($arLead['LAST_NAME']) ? $arLead['LAST_NAME'] : '',
					'SECOND_NAME' => isset($arLead['SECOND_NAME']) ? $arLead['SECOND_NAME'] : ''
				)
			);

	//region Multifields
	$arEntityTypes = CCrmFieldMulti::GetEntityTypes();
	$multiFields = array();
	$multiFieldHtml = array();

	$sipConfig =  array(
		'ENABLE_SIP' => true,
		'SIP_PARAMS' => array(
			'ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::LeadName,
			'ENTITY_ID' => $iLeadId)
	);

	$dbRes = CCrmFieldMulti::GetListEx(
		array(),
		array('ENTITY_ID' => CCrmOwnerType::LeadName, 'ELEMENT_ID' => $iLeadId, '@TYPE_ID' => array('PHONE', 'EMAIL')),
		false,
		false,
		array('TYPE_ID', 'VALUE_TYPE', 'VALUE')
	);

	while($multiField = $dbRes->Fetch())
	{
		$typeID = isset($multiField['TYPE_ID']) ? $multiField['TYPE_ID'] : '';

		if(isset($multiFieldHtml[$typeID]))
		{
			continue;
		}

		$value = isset($multiField['VALUE']) ? $multiField['VALUE'] : '';
		$valueType = isset($multiField['VALUE_TYPE']) ? $multiField['VALUE_TYPE'] : '';

		$entityType = $arEntityTypes[$typeID];
		$valueTypeInfo = isset($entityType[$valueType]) ? $entityType[$valueType] : null;

		$params = array('VALUE' => $value, 'VALUE_TYPE_ID' => $valueType, 'VALUE_TYPE' => $valueTypeInfo);
		$item = CCrmViewHelper::PrepareMultiFieldValueItemData($typeID, $params, $sipConfig);
		if(isset($item['value']) && $item['value'] !== '')
		{
			$multiFieldHtml[$typeID] = $item['value'];
		}
	}
	//endregion

	$strCard = '
<div class="bx-user-info-data-cont-video  bx-user-info-fields" id="bx_user_info_data_cont_1">
	<div class="bx-user-info-data-name ">
		<a href="'.$arLead['PATH_TO_LEAD_SHOW'].'">'.htmlspecialcharsbx($arLead['TITLE']).'</a>
	</div>
	<div class="bx-user-info-data-info">';
	if (!empty($arLead['STATUS_ID']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_STATUS').'</span>:
		<span class="fields enumeration">'.$arResult['STATUS_LIST'][$arLead['STATUS_ID']].'</span>
		<br />';
	}

	$arProductRows = CCrmLead::LoadProductRows($arLead['ID']);
	if(count($arProductRows) > 0)
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_PRODUCTS').'</span>:<span class="fields enumeration">'.htmlspecialcharsbx(CCrmProductRow::RowsToString($arProductRows)).'</span><br />';
	}

	$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_DATE_MODIFY').'</span>:
		<span class="fields enumeration">'.FormatDate('x', MakeTimeStamp($arLead['DATE_MODIFY']), (time() + CTimeZone::GetOffset())).'</span>
		<br />
		<br />
	</div>
	<div class="bx-user-info-data-name bx-user-info-seporator">
		<nobr>'.GetMessage('CRM_SECTION_CONTACT_INFO').'</nobr>
	</div>
	<div class="bx-user-info-data-info">';
	if (!empty($arLead['FORMATTED_NAME']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_NAME').'</span>:
		<span class="fields enumeration">'.htmlspecialcharsbx($arLead['FORMATTED_NAME']).'</span>
		<br />';
	}
	if (isset($multiFieldHtml['PHONE']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_PHONE').'</span>:
		<span class="crm-detail-info-item-text crm-detail-info-item-handset">'.$multiFieldHtml['PHONE'].'</span>
		<br />';
	}
	if (isset($multiFieldHtml['EMAIL']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_EMAIL').'</span>:
		<span class="crm-detail-info-item-text">'.$multiFieldHtml['EMAIL'].'</span>
		<br />';
	}
	$strCard .= '</div>
</div>';
	$strPhoto = '<a href="'.$arLead['PATH_TO_LEAD_SHOW'].'" class="bx-user-info-data-photo no-photo"></a>';

	$strToolbar2 = '
<div class="bx-user-info-data-separator"></div>
<ul>
	<li class="bx-icon-show">
		<a href="'.$arLead['PATH_TO_LEAD_SHOW'].'">'.GetMessage('CRM_OPER_SHOW').'</a>
	</li>
	<li class="bx-icon bx-icon-message">
		<a href="'.$arLead['PATH_TO_LEAD_EDIT'].'" >'.GetMessage('CRM_OPER_EDIT').'</a>
	</li>
</ul>';

	$script = '
		if(typeof(BX.CrmSipManager) === "undefined")
		{
			BX.loadScript(
				"/bitrix/js/crm/common.js",
				function()
				{
					BX.CrmSipManager.getCurrent().setServiceUrl(
						"CRM_'.CUtil::JSEscape(CCrmOwnerType::LeadName).'",
						"/bitrix/components/bitrix/crm.lead.show/ajax.php?'.bitrix_sessid_get().'"
					);

					if(typeof(BX.CrmSipManager.messages) === "undefined")
					{
						BX.CrmSipManager.messages =
						{
							"unknownRecipient": "'.GetMessageJS('CRM_SIP_MGR_UNKNOWN_RECIPIENT').'",
							"makeCall": "'.GetMessageJS('CRM_SIP_MGR_MAKE_CALL').'"
						};
					}
				}
			);
		}';

	$arResult = array(
		'Toolbar' => '',
		'ToolbarItems' => '',
		'Toolbar2' => $strToolbar2,
		'Card' => $strCard,
		'Card2' => $arLead,
		'Photo' => $strPhoto
	);
}

$APPLICATION->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
echo CUtil::PhpToJsObject(array('RESULT' => $arResult));
if(!defined('PUBLIC_AJAX_MODE'))
{
	define('PUBLIC_AJAX_MODE', true);
}
include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
die();

?>
