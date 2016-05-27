<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

/** @global CMain $APPLICATION */
global $APPLICATION, $USER;

// PARSE PARAMS
$arResult['PATH_TO_FULL_VIEW'] = $arParams['PATH_TO_FULL_VIEW'] = CrmCheckPath('PATH_TO_FULL_VIEW', $arParams['PATH_TO_FULL_VIEW'], COption::GetOptionString('crm', 'path_to_activity_list'));
$bindings = (isset($arParams['BINDINGS']) && is_array($arParams['BINDINGS'])) ? $arParams['BINDINGS'] : array();
// Check show mode
$showMode = isset($arParams['SHOW_MODE']) ? strtoupper(strval($arParams['SHOW_MODE'])) : 'ALL';
$arResult['SHOW_MODE'] = $showMode;
$arResult['PATH_TO_USER_PROFILE'] = $arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', isset($arParams['PATH_TO_USER_PROFILE']) ? $arParams['PATH_TO_USER_PROFILE'] : '', '/company/personal/user/#user_id#/');
// Check permissions (READ by default)
$permissionType = isset($arParams['PERMISSION_TYPE']) ? strtoupper((string)$arParams['PERMISSION_TYPE']) : 'READ';
if($permissionType !== 'READ' && $permissionType !== 'WRITE')
{
	$permissionType = 'READ';
}

$arResult['READ_ONLY'] = $permissionType == 'READ';

$arResult['PREFIX'] = isset($arParams['PREFIX']) ? strval($arParams['PREFIX']) : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['FORM_TYPE'] = isset($arParams['FORM_TYPE']) ? $arParams['FORM_TYPE'] : '';
$arResult['ENABLE_CONTROL_PANEL'] = isset($arParams['ENABLE_CONTROL_PANEL']) ? $arParams['ENABLE_CONTROL_PANEL'] : true;
$arResult['FORM_URI'] = isset($arParams['FORM_URI']) ? $arParams['FORM_URI'] : '';

$currentUserPermissions = CCrmPerms::GetCurrentUserPermissions();
$currentUserID = $arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$currentUserName = $arResult['CURRENT_USER_NAME'] = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);

$filterFieldPrefix = $arResult['FILTER_FIELD_PREFIX'] = $arResult['TAB_ID'] !== '' ? strtoupper($arResult['TAB_ID']).'_' : '';
$tabParamName = $arResult['FORM_ID'] !== '' ? $arResult['FORM_ID'].'_active_tab' : 'active_tab';
$activeTabID = isset($_REQUEST[$tabParamName]) ? $_REQUEST[$tabParamName] : '';

$topCount = $arResult['TOP_COUNT'] = isset($arParams['TOP_COUNT']) ? intval($arParams['TOP_COUNT']) : 0;
$arFilter = array();
$arResult['OWNER_UID'] = '';

$arBindingFilter = array();
for($i = count($bindings); $i >= 0; $i--)
{
	$binding = $bindings[$i];
	$ownerTypeID = isset($binding['TYPE_ID']) ? intval($binding['TYPE_ID']) : 0;
	if($ownerTypeID <= 0)
	{
		$ownerTypeName = isset($binding['TYPE_NAME']) ? $binding['TYPE_NAME'] : '';
		$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
		if($ownerTypeID <= 0)
		{
			continue;
		}
	}

	$innerFilter = array(
		'OWNER_TYPE_ID' => $ownerTypeID
	);

	$ownerID = isset($binding['ID']) ? intval($binding['ID']) : 0;
	if($ownerID > 0)
	{
		$innerFilter['OWNER_ID'] = $ownerID;
	}

	$arBindingFilter[] = $innerFilter;

	if($arResult['OWNER_UID'] !== '')
	{
		$arResult['OWNER_UID'] .= '_';
	}
	$arResult['OWNER_UID'] .=  strtolower(CCrmOwnerType::ResolveName($ownerTypeID)).($ownerID > 0 ? '_'.$ownerID : '');
}

if(!empty($arBindingFilter))
{
	$arFilter['BINDINGS'] = $arBindingFilter;
}

$arResult['UID'] = $arResult['GRID_ID'] = 'CRM_ACTIVITY_LIST_'.($arResult['PREFIX'] !== '' ? $arResult['PREFIX'] : strtoupper($arResult['OWNER_UID']));
$arResult['IS_INTERNAL'] = $arResult['OWNER_UID'] !== '';


if(count($arBindingFilter) === 1)
{
	$arBinding = $arBindingFilter[0];
	$arResult['OWNER_TYPE'] = CCrmOwnerType::ResolveName($arBinding['OWNER_TYPE_ID']);
	$arResult['OWNER_ID'] = isset($arBinding['OWNER_ID']) ? $arBinding['OWNER_ID'] : 0;
}
elseif(isset($arParams['OWNER']))
{
	$arResult['OWNER_TYPE'] = isset($arParams['OWNER']['TYPE_NAME']) ? $arParams['OWNER']['TYPE_NAME'] : '';
	$arResult['OWNER_ID'] = isset($arParams['OWNER']['ID']) ? $arParams['OWNER']['ID'] : 0;;
}
else
{
	$arResult['OWNER_TYPE'] = '';
	$arResult['OWNER_ID'] = 0;
}

if($showMode === 'COMPLETED')
{
	$arFilter['__INNER_FILTER_SHOW_MODE'] = array(
		'RESPONSIBLE_ID' => $currentUserID,
		'LOGIC' => 'AND',
		'COMPLETED' => 'Y'
	);
}
elseif($showMode === 'NOT_COMPLETED')
{
	$arFilter['__INNER_FILTER_SHOW_MODE'] = array(
		'RESPONSIBLE_ID' => $currentUserID,
		'LOGIC' => 'AND',
		'COMPLETED' => 'N'
	);
}
elseif($showMode === 'ALL_NOT_COMPLETED')
{
	$arFilter['COMPLETED'] = 'N';
}
elseif($showMode === 'NOT_COMPLETED_OR_RECENT_CHANGED')
{
	$arFilter['__INNER_FILTER_SHOW_MODE'] = array(
		'LOGIC' => 'AND',
		'RESPONSIBLE_ID' => $currentUserID,
		'__INNER_FILTER' => array(
			'LOGIC' => 'OR',
			'COMPLETED' => 'N',
			'>=LAST_UPDATED' => ConvertTimeStamp(AddToTimeStamp(array('HH' => -1), time() + CTimeZone::GetOffset()), 'FULL')
		)
	);
}

if (intval($arParams['ITEM_COUNT']) <= 0)
{
	$arParams['ITEM_COUNT'] = 20;
}

$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath(
	'PATH_TO_USER_PROFILE',
	isset($arParams['PATH_TO_USER_PROFILE']) ? $arParams['PATH_TO_USER_PROFILE'] : '',
	'/company/personal/user/#user_id#/'
);

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'type'=> 'number', 'name' => 'ID', 'sort' => 'id', 'default' => false, 'editable' => false, 'class' => 'minimal')
);

$arResult['HEADERS'][] = array('id' => 'SUBJECT', 'type'=> 'text', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_SUBJECT'), 'default' => true, 'editable' => true);
$arResult['HEADERS'][] = array('id' => 'START_TIME', 'type'=> 'date', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_START'), 'default' => false, 'editable' => true, 'class' => 'datetime');
$arResult['HEADERS'][] = array('id' => 'END_TIME', 'type'=> 'date', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_END_2'), 'default' => false, 'editable' => true, 'class' => 'datetime');
$arResult['HEADERS'][] = array('id' => 'DEADLINE', 'type'=> 'date', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_DEADLINE'), 'sort' => 'DEADLINE', 'default' => true, 'editable' => false, 'class' => 'datetime');
$displayReference = $arResult['DISPLAY_REFERENCE'] = isset($arParams['DISPLAY_REFERENCE']) ? $arParams['DISPLAY_REFERENCE'] : false;
$arResult['HEADERS'][] = array('id' => 'REFERENCE', 'type'=> 'text', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_REFERENCE'), 'default' => $displayReference, 'editable' => false);

$displayClient = $arResult['DISPLAY_CLIENT'] = isset($arParams['DISPLAY_CLIENT']) ? $arParams['DISPLAY_CLIENT'] : true;
$arResult['HEADERS'][] = array('id' => 'CLIENT', 'type'=> 'text', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_CLIENT'), 'default' => $displayClient, 'editable' => false);

$arResult['HEADERS'][] = array('id' => 'DESCRIPTION', 'type'=> 'text', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_DESCRIPTION'), 'default' => false, 'editable' => true);
$arResult['HEADERS'][] = array('id' => 'RESPONSIBLE_FULL_NAME', 'type'=> 'text', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_RESPONSIBLE'), 'sort' => 'RESPONSIBLE_FULL_NAME', 'default' => true, 'editable' => false, 'class' => 'username');
$arResult['HEADERS'][] = array('id' => 'COMPLETED', 'type'=> 'list', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_COMPLETED'), 'hideName' => true, 'sort' => 'COMPLETED', 'default' => true, 'editable' => array('items' => array('N' => GetMessage('CRM_ACTIVITY_STATUS_NOT_COMPLETED'), 'Y' => GetMessage('CRM_ACTIVITY_STATUS_COMPLETED'))));
$arResult['HEADERS'][] = array('id' => 'CREATED', 'type'=> 'date', 'name' => GetMessage('CRM_ACTIVITY_COLUMN_CREATED'), 'sort' => 'CREATED', 'default' => false, 'editable' => false, 'class' => 'date');

$arResult['FILTER'] = array();
$arResult['FILTER_PRESETS'] = array();

$typeListItems = array(
	strval(CCrmActivityType::Meeting) => CCrmActivityType::ResolveDescription(CCrmActivityType::Meeting),
	strval(CCrmActivityType::Call).'.'.strval(CCrmActivityDirection::Incoming) => GetMessage('CRM_ACTIVITY_INCOMING_CALL'),
	strval(CCrmActivityType::Call).'.'.strval(CCrmActivityDirection::Outgoing) => GetMessage('CRM_ACTIVITY_OUTGOING_CALL'),
	strval(CCrmActivityType::Task) => CCrmActivityType::ResolveDescription(CCrmActivityType::Task),
	strval(CCrmActivityType::Email).'.'.strval(CCrmActivityDirection::Incoming) => GetMessage('CRM_ACTIVITY_INCOMING_EMAIL'),
	strval(CCrmActivityType::Email).'.'.strval(CCrmActivityDirection::Outgoing) => GetMessage('CRM_ACTIVITY_OUTGOING_EMAIL')
);

if($arResult['TAB_ID'] === ''
	&& $_SERVER['REQUEST_METHOD'] === 'GET'
	&& isset($_GET['conv']))
{
	if(CCrmPerms::IsAdmin())
	{
		$conv = strtoupper($_GET['conv']);
		if($conv === 'EXEC_CAL')
		{
			CCrmActivityConverter::ConvertCalEvents(false, true);
			COption::SetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_CALENDAR_EVENTS', 'Y');
		}
		elseif($conv === 'EXEC_TASK')
		{
			CCrmActivityConverter::ConvertTasks(false, true);
			COption::SetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_OF_TASKS', 'Y');
		}
		elseif($conv === 'SKIP_CAL')
		{
			COption::SetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_CALENDAR_EVENTS', 'Y');
		}
		elseif($conv === 'SKIP_TASK')
		{
			COption::SetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_OF_TASKS', 'Y');
		}
		elseif($conv === 'RESET_CAL')
		{
			COption::RemoveOption('crm', '~CRM_ACTIVITY_LIST_CONVERTING_CALENDAR_EVENTS');
		}
		elseif($conv === 'RESET_TASK')
		{
			COption::RemoveOption('crm', '~CRM_ACTIVITY_LIST_CONVERTING_OF_TASKS');
		}
	}

	LocalRedirect(CHTTP::urlDeleteParams($APPLICATION->GetCurPage(), array('conv')));
}

ob_start();
$GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.entity.selector',
	'',
	array(
		'ENTITY_TYPE' => Array('LEAD', 'DEAL'),
		'INPUT_NAME' => 'REFERENCE',
		'INPUT_VALUE' =>  isset($_REQUEST["{$filterFieldPrefix}REFERENCE"]) ? $_REQUEST["{$filterFieldPrefix}REFERENCE"] : '',
		'FORM_NAME' => $arResult['UID'],
		'MULTIPLE' => 'N',
		'FILTER' => true,
	),
	false,
	array('HIDE_ICONS' => 'Y')
);
$referenceFilterHtml = ob_get_contents();
ob_end_clean();

ob_start();
$GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.entity.selector',
	'',
	array(
		'ENTITY_TYPE' => array('COMPANY', 'CONTACT'),
		'INPUT_NAME' => 'CLIENT',
		'INPUT_VALUE' =>  isset($_REQUEST["{$filterFieldPrefix}CLIENT"]) ? $_REQUEST["{$filterFieldPrefix}CLIENT"] : '',
		'FORM_NAME' => $arResult['UID'],
		'MULTIPLE' => 'N',
		'FILTER' => true,
	),
	false,
	array('HIDE_ICONS' => 'Y')
);
$clientFilterHtml = ob_get_contents();
ob_end_clean();

$arResult['FILTER'] = array(
	array('id' => "{$filterFieldPrefix}ID", 'name' => 'ID', 'default' => false),
	//array('id' => "{$filterFieldPrefix}COMPLETED", 'name' => GetMessage('CRM_ACTIVITY_FILTER_COMPLETED'), 'type'=> 'list', 'items'=> array(''=> '', 'Y' => GetMessage('CRM_ACTIVITY_FILTER_ITEM_COMPLETED'), 'N' => GetMessage('CRM_ACTIVITY_FILTER_ITEM_NOT_COMPLETED')), 'default' => true),
	array('id' => "{$filterFieldPrefix}COMPLETED", 'name' => GetMessage('CRM_ACTIVITY_FILTER_COMPLETED'), 'type'=> 'list', 'items'=> array('Y' => GetMessage('CRM_ACTIVITY_FILTER_ITEM_COMPLETED'), 'N' => GetMessage('CRM_ACTIVITY_FILTER_ITEM_NOT_COMPLETED')), 'params' => array('multiple' => 'Y'), 'default' => true),
	array('id' => "{$filterFieldPrefix}TYPE_ID", 'name' => GetMessage('CRM_ACTIVITY_FILTER_TYPE_ID'), 'type'=> 'list', 'items'=> $typeListItems, 'params' => array('multiple' => 'Y'), 'default' => true),
	array('id' => "{$filterFieldPrefix}PRIORITY", 'name' => GetMessage('CRM_ACTIVITY_FILTER_PRIORITY'), 'type'=> 'list', 'items'=> CCrmActivityPriority::PrepareFilterItems(), 'params' => array('multiple' => 'Y'), 'default' => true),
	array('id' => "{$filterFieldPrefix}RESPONSIBLE_ID",  'name' => GetMessage('CRM_ACTIVITY_FILTER_RESPONSIBLE'), 'default' => true, 'enable_settings' => true, 'type' => 'user'),
	array('id' => "{$filterFieldPrefix}START",  'name' => GetMessage('CRM_ACTIVITY_FILTER_START'), 'default' => false, 'type' => 'date'),
	array('id' => "{$filterFieldPrefix}END",  'name' => GetMessage('CRM_ACTIVITY_FILTER_END_2'), 'default' => false, 'type' => 'date'),
	array('id' => "{$filterFieldPrefix}DEADLINE",  'name' => GetMessage('CRM_ACTIVITY_FILTER_DEADLINE'), 'default' => true, 'type' => 'date'),
	array('id' => "{$filterFieldPrefix}CREATED",  'name' => GetMessage('CRM_ACTIVITY_FILTER_CREATED'), 'default' => true, 'type' => 'date')
);

if($displayReference)
{
	$arResult['FILTER'][] = array('id' => "{$filterFieldPrefix}REFERENCE",  'name' => GetMessage('CRM_ACTIVITY_COLUMN_REFERENCE'), 'default' => true, 'type' => 'custom', 'value'=> $referenceFilterHtml);
}

if($displayClient)
{
	$arResult['FILTER'][] = array('id' => "{$filterFieldPrefix}CLIENT",  'name' => GetMessage('CRM_ACTIVITY_COLUMN_CLIENT'), 'default' => true, 'type' => 'custom', 'value'=> $clientFilterHtml);
}

$arResult['FILTER_PRESETS'] = array(
	'not_completed' => array(
		'name' => GetMessage('CRM_PRESET_NOT_COMPLETED'),
		'fields' => array(
			"{$filterFieldPrefix}COMPLETED" => array('selN' => 'N'),
			"{$filterFieldPrefix}RESPONSIBLE_ID_name" => $currentUserName,
			"{$filterFieldPrefix}RESPONSIBLE_ID" => $currentUserID
		)
	),
	'completed' => array(
		'name' => GetMessage('CRM_PRESET_COMPLETED'),
		'fields' => array(
			"{$filterFieldPrefix}COMPLETED" => array('selY' => 'Y'),
			"{$filterFieldPrefix}RESPONSIBLE_ID_name" => $currentUserName,
			"{$filterFieldPrefix}RESPONSIBLE_ID" => $currentUserID
		)
	),
	'not_completed_all' => array(
		'name' => GetMessage('CRM_PRESET_NOT_COMPLETED_ALL'),
		'fields' => array(
			"{$filterFieldPrefix}COMPLETED" => array('selN' => 'N')
		)
	),
	'completed_all' => array(
		'name' => GetMessage('CRM_PRESET_COMPLETED_ALL'),
		'fields' => array(
			"{$filterFieldPrefix}COMPLETED" => array('selY' => 'Y')
		)
	)
);


// HACK: for clear filter by RESPONSIBLE_ID
if($_SERVER['REQUEST_METHOD'] === 'GET')
{
	$filterItemID = "{$filterFieldPrefix}RESPONSIBLE_ID";
	$filterItemName = "{$filterFieldPrefix}RESPONSIBLE_ID_name";
	if(isset($_REQUEST[$filterItemName]) && $_REQUEST[$filterItemName] === '')
	{
		$_REQUEST[$filterItemID] = $_GET[$filterItemID] = array();
	}
}

$postAction = 'action_button_'.$arResult['UID'];
$postActionForAll = 'action_all_rows_'.$arResult['UID'];

//region Try to extract user action data
// We have to extract them before call of CGridOptions::GetFilter() overvise the custom filter will be corrupted.
$actionData = array(
	'METHOD' => $_SERVER['REQUEST_METHOD'],
	'ACTIVE' => false
);
if(check_bitrix_sessid())
{
	$postAction = 'action_button_'.$arResult['UID'];
	if ($actionData['METHOD'] == 'POST' && isset($_POST[$postAction]))
	{
		$actionData['ACTIVE'] = true;

		$actionData['NAME'] = $_POST[$postAction];
		unset($_POST[$postAction], $_REQUEST[$postAction]);

		$allRows = 'action_all_rows_'.$arResult['UID'];
		$actionData['ALL_ROWS'] = false;
		if(isset($_POST[$allRows]))
		{
			$actionData['ALL_ROWS'] = $_POST[$allRows] == 'Y';
			unset($_POST[$allRows], $_REQUEST[$allRows]);
		}

		if(isset($_POST['ID']))
		{
			$actionData['ID'] = $_POST['ID'];
			unset($_POST['ID'], $_REQUEST['ID']);
		}

		if(isset($_POST['ACTION_RESPONSIBLE_ID']))
		{
			$responsibleID = 0;
			if(!is_array($_POST['ACTION_RESPONSIBLE_ID']))
			{
				$responsibleID = intval($_POST['ACTION_RESPONSIBLE_ID']);
			}
			elseif(count($_POST['ACTION_RESPONSIBLE_ID']) > 0)
			{
				$responsibleID = intval($_POST['ACTION_RESPONSIBLE_ID'][0]);
			}

			$actionData['RESPONSIBLE_ID'] = $responsibleID;
			unset($_POST['ACTION_RESPONSIBLE_ID'], $_REQUEST['ACTION_RESPONSIBLE_ID']);
		}

		$actionData['AJAX_CALL'] = false;
		if(isset($_POST['AJAX_CALL']))
		{
			$actionData['AJAX_CALL']  = true;
		}
	}
}
//endregion
$arSort = array('DEADLINE' => 'ASC');
$arNavParams = false;


$arNavParams = array(
	'nPageSize' => $arParams['ITEM_COUNT']
);

if($topCount > 0)
{
	$arNavParams['nTopCount'] = $topCount;
}

$arNavigation = CDBResult::GetNavParams($arNavParams);
$CGridOptions = new CCrmGridOptions($arResult['UID'], $arResult['FILTER_PRESETS']);
$arNavParams = $CGridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;

if (($arResult['TAB_ID'] === '' || $arResult['TAB_ID'] === $activeTabID)
	&& isset($_REQUEST['clear_filter'])
	&& $_REQUEST['clear_filter'] === 'Y')
{
	if(isset($_SESSION['CRM_PAGINATION_DATA']) && isset($_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']]))
	{
		unset($_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']]);
	}

	$urlParams = array();
	foreach($arResult['FILTER'] as $arFilterField)
	{
		$filterFieldID = $arFilterField['id'];
		if ($arFilterField['type'] == 'user')
		{
			$urlParams[] = $filterFieldID.'_name';
		}
		if ($arFilterField['type'] == 'date')
		{
			$urlParams[] = $filterFieldID.'_datesel';
			$urlParams[] = $filterFieldID.'_days';
			$urlParams[] = $filterFieldID.'_from';
			$urlParams[] = $filterFieldID.'_to';
		}

		$urlParams[] = $filterFieldID;
	}
	$urlParams[] = 'clear_filter';
	$CGridOptions->GetFilter(array());
	if($arResult['TAB_ID'] !== '')
	{
		$urlParams[] = $tabParamName;
		LocalRedirect($APPLICATION->GetCurPageParam(
			urlencode($tabParamName).'='.urlencode($arResult['TAB_ID']),
			$urlParams));
	}
	else
	{
		LocalRedirect($APPLICATION->GetCurPageParam('', $urlParams));
	}
}

$arGridFilter = $CGridOptions->GetFilter($arResult['FILTER']);
$arResult['GRID_CONTEXT'] = CCrmGridContext::Parse($arGridFilter);

if(!$arResult['GRID_CONTEXT']['FILTER_INFO']['IS_APPLIED'])
{
	$clearFilterKey = 'activity_list_clear_filter'.strtolower($arResult['UID']);
	if(isset($_REQUEST['clear_filter'])
		&& $_REQUEST['clear_filter'] !== '')
	{
		$_SESSION[$clearFilterKey] = $arResult['CLEAR_FILTER'] = true;
	}
	elseif(isset($_SESSION[$clearFilterKey]) && $_SESSION[$clearFilterKey])
	{
		$arResult['CLEAR_FILTER'] = true;
	}
}

if(empty($arGridFilter) && isset($arParams['DEFAULT_FILTER']) && is_array($arParams['DEFAULT_FILTER']))
{
	$arGridFilter = $arParams['DEFAULT_FILTER'];
}

$arResult['GRID_FILTER'] = $arGridFilter;

if(!empty($arGridFilter))
{
	// Clear SHOW_MODE filter if grid filter is enabled
	$showMode = $arResult['SHOW_MODE'] = 'ALL';
	if(isset($arFilter['__INNER_FILTER_SHOW_MODE']))
	{
		unset($arFilter['__INNER_FILTER_SHOW_MODE']);
	}

	if($filterFieldPrefix === '')
	{
		$arFilter = array_merge($arFilter, $arGridFilter);
	}
	else
	{
		$prefixLength = strlen($filterFieldPrefix);
		foreach($arGridFilter as $key=>&$value)
		{
			if(strpos($key, $filterFieldPrefix) === false)
			{
				$arFilter[$key] = $value;
			}
			else
			{
				$arFilter[substr($key, $prefixLength)] = $value;
			}
		}
		unset($value);
	}
}
elseif($arResult['CLEAR_FILTER'])
{
	// Clear SHOW_MODE filter if grid filter is enabled
	$showMode = $arResult['SHOW_MODE'] = 'ALL';
	if(isset($arFilter['__INNER_FILTER_SHOW_MODE']))
	{
		unset($arFilter['__INNER_FILTER_SHOW_MODE']);
	}
}

$arDatetimeFields = array('CREATED', 'LAST_UPDATED', 'START_TIME', 'END_TIME', 'DEADLINE');
$arUserBindings = array();
foreach ($arFilter as $k => $v)
{
	if($k === 'REFERENCE' || $k === 'CLIENT')
	{
		$ownerData =explode('_', $v);
		if(count($ownerData) > 1)
		{
			$ownerTypeID = CCrmOwnerType::ResolveID($ownerData[0]);
			$ownerID = intval($ownerData[1]);
			if($ownerTypeID > 0 && $ownerID > 0)
			{
				$arUserBindings[] =
					array(
						'OWNER_TYPE_ID' => $ownerTypeID,
						'OWNER_ID' => $ownerID
					);
			}
		}
		unset($arFilter[$k]);
	}

	elseif (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		$fieldID = $arMatch[1];
		if($fieldID === 'END')
		{
			$fieldID = 'END_TIME';
		}
		elseif($fieldID === 'START')
		{
			$fieldID = 'START_TIME';
		}

		if(strlen($v) > 0 && in_array($fieldID, $arDatetimeFields, true))
		{
			$arFilter['>='.$fieldID] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		$fieldID = $arMatch[1];
		if($fieldID === 'END')
		{
			$fieldID = 'END_TIME';
		}
		elseif($fieldID === 'START')
		{
			$fieldID = 'START_TIME';
		}

		if(strlen($v) > 0 && in_array($fieldID, $arDatetimeFields, true))
		{
			if (!preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
			{
				$v = CCrmDateTimeHelper::SetMaxDayTime($v);
			}
			$arFilter['<='.$fieldID] = $v;
		}
		unset($arFilter[$k]);
	}
}

if(!empty($arUserBindings))
{
	//override bindings
	$arFilter['BINDINGS'] = $arUserBindings;
}

$arGridSort = $CGridOptions->GetSorting(
	array(
		'sort' => array('DEADLINE' => 'ASC'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);

$arSort = $arGridSort['sort'];

$arResult['SORT'] = $arSort;
$arResult['SORT_VARS'] = $arGridSort['vars'];

// HACK: Make custom sort for RESPONSIBLE field
if(isset($arSort['RESPONSIBLE_FULL_NAME']))
{
	$assignedBySort = $arSort['RESPONSIBLE_FULL_NAME'];
	$arSort['RESPONSIBLE_LAST_NAME'] = $assignedBySort;
	$arSort['RESPONSIBLE_NAME'] = $assignedBySort;
	$arSort['RESPONSIBLE_LOGIN'] = $assignedBySort;
	unset($arSort['RESPONSIBLE_FULL_NAME']);
}


if(!isset($arResult['GRID_CONTEXT']))
{
	$arResult['GRID_CONTEXT'] = CCrmGridContext::GetEmpty();
}
$arResult['GRID_FILTER_INFO'] = $arResult['GRID_CONTEXT']['FILTER_INFO'];

if ($permissionType === 'WRITE' && $actionData['ACTIVE'] && $actionData['METHOD'] == 'POST')
{
	$actionName = $actionData['NAME'];
	$forAll = $actionData['ALL_ROWS'];

	if ($actionName === 'delete')
	{
		$dbResult = null;
		if($forAll)
		{
			$dbResult = CCrmActivity::GetList(
				array(),
				$arFilter,
				false,
				false,
				array('ID', 'OWNER_TYPE_ID', 'OWNER_ID')
			);
		}
		elseif(isset($actionData['ID']) && !empty($actionData['ID']))
		{
			$dbResult = CCrmActivity::GetList(
				array(),
				array('@ID' => $actionData['ID']),
				false,
				false,
				array('ID', 'OWNER_TYPE_ID', 'OWNER_ID')
			);
		}

		if(is_object($dbResult))
		{
			while($arActivity = $dbResult->Fetch())
			{
				if(CCrmActivity::CheckItemDeletePermission($arActivity, $currentUserPermissions))
				{
					CCrmActivity::Delete($arActivity['ID']);
				}
			}
		}
	}
	elseif($actionName === 'edit')
	{
		if(isset($_POST['FIELDS']) && is_array($_POST['FIELDS']))
		{
			global $DB;
			foreach($_POST['FIELDS'] as $ID => $arSrcData)
			{
				//Modification of emails is not allowed
				$dbActivity = CCrmActivity::GetList(array(), array('=ID' => $ID), false, false, array('TYPE_ID'));
				$arActivity = $dbActivity ? $dbActivity->Fetch() : null;
				if(!(is_array($arActivity) && isset($arActivity['TYPE_ID']) && (int)$arActivity['TYPE_ID'] !== CCrmActivityType::Email))
				{
					continue;
				}

				if(!CCrmActivity::CheckItemUpdatePermission($arActivity, $currentUserPermissions))
				{
					continue;
				}

				$arUpdateData = array();
				foreach ($arResult['HEADERS'] as $arHead)
				{
					if (isset($arHead['editable']) && $arHead['editable'] == true && isset($arSrcData[$arHead['id']]))
					{
						$arUpdateData[$arHead['id']] = $arSrcData[$arHead['id']];
					}
				}

				if (!empty($arUpdateData))
				{
					CCrmActivity::Update($ID, $arUpdateData);
				}
			}
		}
	}
	elseif($actionName === 'mark_as_completed' || $actionName === 'mark_as_not_completed')
	{
		$completed = $actionName === 'mark_as_completed' ? 'Y' : 'N';
		if($forAll)
		{
			$arActionFilter = $arFilter;
			$dbResult = CCrmActivity::GetList(
				array(),
				$arActionFilter,
				false,
				false,
				array('ID', 'OWNER_TYPE_ID', 'OWNER_ID', 'TYPE_ID', 'ASSOCIATED_ENTITY_ID', 'COMPLETED')
			);

			while($arActivity = $dbResult->Fetch())
			{
				if($arActivity['COMPLETED'] === $completed)
				{
					continue;
				}

				if(!CCrmActivity::CheckCompletePermission(
					$arActivity['OWNER_TYPE_ID'],
					$arActivity['OWNER_ID'],
					$currentUserPermissions,
					array('FIELDS' => $arActivity)))
				{
					continue;
				}

				$arActivity['COMPLETED'] = $completed;
				CCrmActivity::Update($arActivity['ID'], $arActivity);
			}
		}
		elseif(isset($actionData['ID']) && !empty($actionData['ID']))
		{
			$arActionFilter = $arFilter;
			$arActionFilter['@ID'] = $actionData['ID'];
			$dbResult = CCrmActivity::GetList(
				array(),
				$arActionFilter,
				false,
				false,
				array('ID', 'OWNER_TYPE_ID', 'OWNER_ID', 'TYPE_ID', 'ASSOCIATED_ENTITY_ID', 'COMPLETED')
			);
			while($arActivity = $dbResult->Fetch())
			{
				if($arActivity['COMPLETED'] === $completed)
				{
					continue;
				}


				if(!CCrmActivity::CheckCompletePermission(
					$arActivity['OWNER_TYPE_ID'],
					$arActivity['OWNER_ID'],
					$currentUserPermissions,
					array('FIELDS' => $arActivity)))
				{
					continue;
				}

				$arActivity['COMPLETED'] = $completed;
				CCrmActivity::Update($arActivity['ID'], $arActivity);
			}
		}
	}
	elseif ($actionName === 'assign_to')
	{
		if(isset($actionData['RESPONSIBLE_ID']) && $actionData['RESPONSIBLE_ID'] > 0)
		{
			$responsibleID = $actionData['RESPONSIBLE_ID'];
			$dbResult = null;
			if ($forAll)
			{
				$dbResult = CCrmActivity::GetList(
					array(),
					$arFilter,
					false,
					false,
					array('ID', 'OWNER_TYPE_ID', 'OWNER_ID', 'RESPONSIBLE_ID')
				);
			}
			elseif(isset($actionData['ID']) && !empty($actionData['ID']))
			{
				$dbResult = CCrmActivity::GetList(
					array(),
					array_merge($arFilter, array('@ID' => $actionData['ID'])),
					false,
					false,
					array('ID', 'OWNER_TYPE_ID', 'OWNER_ID', 'RESPONSIBLE_ID')
				);
			}

			if(is_object($dbResult))
			{
				while($arItem = $dbResult->Fetch())
				{
					$currentResponsibleID = isset($arItem['RESPONSIBLE_ID']) ? (int)$arItem['RESPONSIBLE_ID'] : 0;
					if($currentResponsibleID === $responsibleID)
					{
						continue;
					}

					if(CCrmActivity::CheckItemUpdatePermission($arItem, $currentUserPermissions))
					{
						CCrmActivity::Update($arItem['ID'], array('RESPONSIBLE_ID' => $responsibleID));
					}
				}
			}
		}
	}

	if (!isset($_POST['AJAX_CALL']))
	{
		LocalRedirect($APPLICATION->GetCurPageParam(urlencode($tabParamName).'='.urlencode($arResult['TAB_ID']), array($tabParamName)));
	}
//	else
//	{
//		$arResult['AJAX_RELOAD_ITEMS'] = true;
//	}
}

foreach($arFilter as $fieldID => $values)
{
	if($fieldID !== 'TYPE_ID')
	{
		continue;
	}

	if(!is_array($values))
	{
		$values = array($values);
	}

	$innerFilter = array();

	foreach($values as $i => $val)
	{
		$ary = explode('.', $val, 2);
		if(count($ary) > 1)
		{
			$innerFilter["__INNER_FILTER_TYPE_$i"] = array(
				'LOGIC' => 'AND',
				'TYPE_ID' => intval($ary[0]),
				'DIRECTION' => intval($ary[1])
			);
		}
		else
		{
			$innerFilter["__INNER_FILTER_TYPE_$i"] = array(
				'LOGIC' => 'AND',
				'TYPE_ID' => intval($ary[0])
			);
		}
	}

	unset($arFilter['TYPE_ID']);
	$innerFilter['LOGIC'] = 'OR';
	$arFilter['__INNER_FILTER'] = $innerFilter;
	break;
}

$skipFiles = isset($arParams['SKIP_FILES']) && $arParams['SKIP_FILES'] === true;

//region Navigation data initialization
$pageNum = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? (int)$_REQUEST['page'] : 0;
if($pageNum > 0)
{
	if(!isset($_SESSION['CRM_PAGINATION_DATA']))
	{
		$_SESSION['CRM_PAGINATION_DATA'] = array();
	}
	$_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']] = array('PAGE_NUM' => $pageNum);
}
elseif((isset($_REQUEST['apply_filter']) && $_REQUEST['apply_filter'] === 'Y'))
{
	$pageNum  = 1;
}
else
{
	if(!$arResult['IS_INTERNAL']
		&& isset($_SESSION['CRM_PAGINATION_DATA'])
		&& isset($_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']])
		&& isset($_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM']))
	{
		$pageNum = (int)$_SESSION['CRM_PAGINATION_DATA'][$arResult['GRID_ID']]['PAGE_NUM'];
	}

	if($pageNum <= 0)
	{
		$pageNum  = 1;
	}
}

$pageSize = (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $arParams['ITEM_COUNT']);
$enableNextPage = false;
//endregion

$arOptions = isset($arNavParams['nTopCount']) && $arNavParams['nTopCount'] > 0
	? array('QUERY_OPTIONS' => array('LIMIT' => $arNavParams['nTopCount']))
	: array('QUERY_OPTIONS' => array('LIMIT' => $pageSize + 1, 'OFFSET' => $pageSize * ($pageNum - 1)));

// Ignore select: we need all fields for editor
$dbRes = CCrmActivity::GetList($arSort, $arFilter, false, false, array(), $arOptions);
$arResult['ITEMS'] = array();
$bbCodeParser = new CTextParser();
$responsibleIDs = array();
$items = array();

$qty = 0;
while($arRes = $dbRes->GetNext())
{
	if(++$qty > $pageSize)
	{
		$enableNextPage = true;
		break;
	}

	$itemID = intval($arRes['~ID']);
	$ownerID = intval($arRes['~OWNER_ID']);
	$ownerTypeID = intval($arRes['~OWNER_TYPE_ID']);

	if($arResult['READ_ONLY'])
	{
		$arRes['CAN_EDIT'] = $arRes['CAN_COMPLETE'] = $arRes['CAN_DELETE'] = false;
	}
	else
	{
		if($ownerID > 0 && $ownerTypeID > 0)
		{
			$arRes['CAN_EDIT'] = CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID, $currentUserPermissions);
			$arRes['CAN_COMPLETE'] = (int)$arRes['~TYPE_ID'] !== CCrmActivityType::Task
				? $arRes['CAN_EDIT']
				: CCrmActivity::CheckCompletePermission(
					$ownerTypeID,
					$ownerID,
					$currentUserPermissions,
					array('FIELDS' => $arRes)
				);
			$arRes['CAN_DELETE'] = CCrmActivity::CheckDeletePermission($ownerTypeID, $ownerID, $currentUserPermissions);
		}
		else
		{
			$arRes['CAN_EDIT'] = $arRes['CAN_COMPLETE'] = $arRes['CAN_DELETE'] = true;
		}
	}

	$responsibleID = isset($arRes['~RESPONSIBLE_ID'])
		? intval($arRes['~RESPONSIBLE_ID']) : 0;
	$arRes['~RESPONSIBLE_ID'] = $responsibleID;
	if($responsibleID <= 0)
	{
		$arRes['RESPONSIBLE'] = false;
		$arRes['RESPONSIBLE_FULL_NAME'] = '';
		$arRes['PATH_TO_RESPONSIBLE'] = '';
	}
	elseif(!in_array($responsibleID, $responsibleIDs, true))
	{
		$responsibleIDs[] = $responsibleID;
	}

	$storageTypeID = isset($arRes['STORAGE_TYPE_ID']) ? intval($arRes['STORAGE_TYPE_ID']) : CCrmActivityStorageType::Undefined;
	if($storageTypeID === CCrmActivityStorageType::Undefined || !CCrmActivityStorageType::IsDefined($storageTypeID))
	{
		$storageTypeID = $arRes['STORAGE_TYPE_ID'] = $arRes['~STORAGE_TYPE_ID'] = CCrmActivity::GetDefaultStorageTypeID();
	}

	$arRes['FILES'] = array();
	$arRes['WEBDAV_ELEMENTS'] = array();
	$arRes['DISK_FILES'] = array();

	if(!$skipFiles)
	{
		CCrmActivity::PrepareStorageElementIDs($arRes);
		CCrmActivity::PrepareStorageElementInfo($arRes);
	}

	//$arRes['SETTINGS'] = (isset($arRes['~SETTINGS']) && $arRes['~SETTINGS'] !== '') ? unserialize($arRes['~SETTINGS']) : array();
	//Lazy communications loading
	//$arRes['COMMUNICATIONS'] = CCrmActivity::GetCommunications($itemID);
	$arRes['COMMUNICATIONS_LOADED'] = false;

	$description = isset($arRes['~DESCRIPTION']) ? $arRes['~DESCRIPTION'] : '';
	$descriptionType = isset($arRes['DESCRIPTION_TYPE']) ? intval($arRes['DESCRIPTION_TYPE']) : CCrmContentType::PlainText;

	if($descriptionType === CCrmContentType::BBCode)
	{
		$arRes['DESCRIPTION_BBCODE'] = $description;
		$arRes['DESCRIPTION_HTML'] = $bbCodeParser->convertText($description);
		$arRes['DESCRIPTION_RAW'] = strip_tags(
			preg_replace('/(<br[^>]*>)+/is'.BX_UTF_PCRE_MODIFIER, "\n", $arRes['DESCRIPTION_HTML'])
		);
		$arRes['ENABLE_DESCRIPTION_CUT'] = false;
	}
	elseif($descriptionType === CCrmContentType::Html)
	{
		$arRes['DESCRIPTION_BBCODE'] = '';
		//Already sanitaized
		$arRes['DESCRIPTION_HTML'] = $description;
		$arRes['DESCRIPTION_RAW'] = html_entity_decode(
			strip_tags(
				preg_replace('/(<br[^>]*>)+/is'.BX_UTF_PCRE_MODIFIER, "\n", $description)
			)
		);
		$arRes['ENABLE_DESCRIPTION_CUT'] = false;
	}
	else//CCrmContentType::PlainText and other
	{
		$arRes['DESCRIPTION_BBCODE'] = '';
		$arRes['DESCRIPTION_HTML'] = preg_replace("/[\r\n]+/".BX_UTF_PCRE_MODIFIER, "<br/>", htmlspecialcharsbx($description));
		$arRes['DESCRIPTION_RAW'] = $description;
		$arRes['ENABLE_DESCRIPTION_CUT'] = true;
	}

	if(isset($arRes['~DEADLINE']) && CCrmDateTimeHelper::IsMaxDatabaseDate($arRes['~DEADLINE']))
	{
		$arRes['~DEADLINE'] = $arRes['DEADLINE'] = '';
	}

	$items[$itemID] = $arRes;
}
if($displayClient && !empty($items))
{
	$clientInfos = CCrmActivity::PrepareClientInfos(array_keys($items));

	foreach($clientInfos as $itemID => &$clientInfo)
	{
		$items[$itemID]['CLIENT_INFO'] = $clientInfo;
	}
	unset($clientInfo);
}

$arResult['ITEMS'] = array_values($items);

$responsibleInfos = array();
if(!empty($responsibleIDs))
{
	$dbUsers = CUser::GetList(
		($by = 'ID'),
		($order = 'ASC'),
		array('ID' => implode('||', $responsibleIDs)),
		array('FIELDS' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'TITLE'))
	);

	$userNameFormat = CSite::GetNameFormat(false);
	while($arUser = $dbUsers->Fetch())
	{
		$userID = intval($arUser['ID']);

		$responsibleInfo = array('USER' => $arUser);
		$responsibleInfo['FULL_NAME'] = CUser::FormatName($userNameFormat, $arUser, true, false);
		$responsibleInfo['HTML_FULL_NAME'] = htmlspecialcharsbx($responsibleInfo['FULL_NAME']);
		$responsibleInfo['PATH'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_USER_PROFILE'],
			array('user_id' => $userID)
		);
		$responsibleInfos[$userID] = &$responsibleInfo;
		unset($responsibleInfo);
	}

	foreach($arResult['ITEMS'] as &$item)
	{
		$responsibleID = $item['~RESPONSIBLE_ID'];
		if(!isset($responsibleInfos[$responsibleID]))
		{
			continue;
		}

		$responsibleInfo = $responsibleInfos[$responsibleID];

		$item['RESPONSIBLE'] = $responsibleInfo['USER'];
		$item['~RESPONSIBLE_FULL_NAME'] = $responsibleInfo['FULL_NAME'];
		$item['RESPONSIBLE_FULL_NAME'] = $responsibleInfo['HTML_FULL_NAME'];
		$item['PATH_TO_RESPONSIBLE'] = $responsibleInfo['PATH'];
	}
	unset($item);
}

//region Navigation data storing
$arResult['PAGINATION'] = array(
	'PAGE_NUM' => $pageNum,
	'ENABLE_NEXT_PAGE' => $enableNextPage,
	'URL' => $APPLICATION->GetCurPageParam('', array('apply_filter', 'clear_filter', 'save', 'page'))
);

$arResult['DB_FILTER'] = $arFilter;

if(!isset($_SESSION['CRM_GRID_DATA']))
{
	$_SESSION['CRM_GRID_DATA'] = array();
}
$_SESSION['CRM_GRID_DATA'][$arResult['GRID_ID']] = array('FILTER' => $arFilter);
//endregion

$arResult['SHOW_TOP'] = isset($arParams['SHOW_TOP']) && intval($arParams['SHOW_TOP']) > 0 ? intval($arParams['SHOW_TOP']) : 0;
$arResult['ENABLE_TASK_ADD'] = !$arResult['READ_ONLY'] && IsModuleInstalled('tasks');
$arResult['ENABLE_CALENDAR_EVENT_ADD'] = !$arResult['READ_ONLY'] && IsModuleInstalled('calendar');
$arResult['ENABLE_EMAIL_ADD'] = !$arResult['READ_ONLY'] && IsModuleInstalled('subscribe');
$arResult['IS_AJAX_CALL'] = isset($_REQUEST['bxajaxid']) || isset($_REQUEST['AJAX_CALL']);
$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : 'N';
$arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';
$arResult['USE_QUICK_FILTER'] = isset($arParams['USE_QUICK_FILTER']) ? $arParams['USE_QUICK_FILTER'] : false;
if(is_string($arResult['USE_QUICK_FILTER']))
{
	$arResult['USE_QUICK_FILTER'] = strtoupper($arResult['USE_QUICK_FILTER']) === 'Y';
}
$arResult['ENABLE_TOOLBAR'] = isset($arParams['ENABLE_TOOLBAR']) ? $arParams['ENABLE_TOOLBAR'] : true;
$arResult['ENABLE_WEBDAV'] = IsModuleInstalled('webdav');
if(!$arResult['ENABLE_WEBDAV'])
{
	$arResult['WEBDAV_SELECT_URL'] = $arResult['WEBDAV_UPLOAD_URL'] = $arResult['WEBDAV_SHOW_URL'] = '';
}
else
{
	$webDavPaths = CCrmWebDavHelper::GetPaths();
	$arResult['WEBDAV_SELECT_URL'] = isset($webDavPaths['PATH_TO_FILES'])
		? $webDavPaths['PATH_TO_FILES'] : '';
	$arResult['WEBDAV_UPLOAD_URL'] = isset($webDavPaths['ELEMENT_UPLOAD_URL'])
		? $webDavPaths['ELEMENT_UPLOAD_URL'] : '';
	$arResult['WEBDAV_SHOW_URL'] = isset($webDavPaths['ELEMENT_SHOW_INLINE_URL'])
		? $webDavPaths['ELEMENT_SHOW_INLINE_URL'] : '';
}


if($_SERVER['REQUEST_METHOD'] === 'GET')
{
	if(isset($_GET['open_view']))
	{
		$itemID = intval($_GET['open_view']);
		if($itemID > 0)
		{
			$arResult['OPEN_VIEW_ITEM_ID'] = $itemID;
		}
	}
	elseif(isset($_GET['open_edit']))
	{
		$itemID = intval($_GET['open_edit']);
		if($itemID > 0)
		{
			$arResult['OPEN_EDIT_ITEM_ID'] = $itemID;
		}
		$disableStorageEdit = isset($_GET['disable_storage_edit']) && strtoupper($_GET['disable_storage_edit']) === 'Y';
		if($disableStorageEdit)
		{
			$arResult['DISABLE_STORAGE_EDIT'] = true;
		}
	}
}

$arResult['NEED_FOR_CONVERTING_OF_CALENDAR_EVENTS'] = $arResult['NEED_FOR_CONVERTING_OF_TASKS'] = false;
if($arResult['TAB_ID'] === '' && CCrmPerms::IsAdmin())
{
	$curPage = $APPLICATION->GetCurPage();
	//Converting existing calendar events
	if(COption::GetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_CALENDAR_EVENTS', 'N') !== 'Y')
	{
		if(CCrmActivityConverter::IsCalEventConvertigRequired())
		{
			$arResult['NEED_FOR_CONVERTING_OF_CALENDAR_EVENTS'] = true;
			$arResult['CAL_EVENT_CONV_EXEC_URL'] = CHTTP::urlAddParams($curPage, array('conv' => 'exec_cal'));
			$arResult['CAL_EVENT_CONV_SKIP_URL'] = CHTTP::urlAddParams($curPage, array('conv' => 'skip_cal'));
		}
		else
		{
			COption::SetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_CALENDAR_EVENTS', 'Y');
		}
	}

	//Converting existing tasks
	if(COption::GetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_OF_TASKS', 'N') !== 'Y')
	{
		if(CCrmActivityConverter::IsTaskConvertigRequired())
		{
			$arResult['NEED_FOR_CONVERTING_OF_TASKS'] = true;
			$arResult['TASK_CONV_EXEC_URL'] = CHTTP::urlAddParams($curPage, array('conv' => 'exec_task'));
			$arResult['TASK_CONV_SKIP_URL'] = CHTTP::urlAddParams($curPage, array('conv' => 'skip_task'));
		}
		else
		{
			COption::SetOptionString('crm', '~CRM_ACTIVITY_LIST_CONVERTING_OF_TASKS', 'Y');
		}
	}
}

// HACK: for to prevent title overwrite after AJAX call.
if(isset($_REQUEST['bxajaxid']))
{
	$APPLICATION->SetTitle('');
}
$this->IncludeComponentTemplate();
return $arResult['ROWS_COUNT'];
