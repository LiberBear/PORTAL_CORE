<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmQuoteListEndResonse'))
{
	function __CrmQuoteListEndResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

if (!CModule::IncludeModule('crm'))
{
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	return;
}

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if (isset($_REQUEST['MODE']) && $_REQUEST['MODE'] === 'SEARCH')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmQuote::CheckReadPermission(0, $userPerms))
	{
		__CrmQuoteListEndResonse(array('ERROR' => 'Access denied.'));
	}

	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();

	// Limit count of items to be found
	$nPageTop = 50;		// 50 items by default
	if (isset($_REQUEST['LIMIT_COUNT']) && ($_REQUEST['LIMIT_COUNT'] >= 0))
	{
		$rawNPageTop = (int) $_REQUEST['LIMIT_COUNT'];
		if ($rawNPageTop === 0)
			$nPageTop = false;		// don't limit
		elseif ($rawNPageTop > 0)
			$nPageTop = $rawNPageTop;
	}

	$search = trim($_REQUEST['VALUE']);
	$multi = isset($_REQUEST['MULTI']) && $_REQUEST['MULTI'] == 'Y'? true: false;
	$arFilter = array();
	if (is_numeric($search))
	{
		$arFilter['ID'] = (int) $search;
		$arFilter['%QUOTE_NUMBER'] = $search;
		$arFilter['%TITLE'] = $search;
		$arFilter['LOGIC'] = 'OR';
	}
	else if (preg_match('/(.*)\[(\d+?)\]/i'.BX_UTF_PCRE_MODIFIER, $search, $arMatches))
	{
		$arFilter['ID'] = (int) $arMatches[2];
		$arFilter['%TITLE'] = trim($arMatches[1]);
		$arFilter['LOGIC'] = 'OR';
	}
	else
	{
		$arFilter['%QUOTE_NUMBER'] = $search;
		$arFilter['%TITLE'] = $search;
		$arFilter['LOGIC'] = 'OR';
	}

	$arQuoteStatusList = CCrmStatus::GetStatusListEx('QUOTE_STATUS');
	$arSelect = array('ID', 'QUOTE_NUMBER', 'TITLE', 'STATUS_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME');
	$arOrder = array('TITLE' => 'ASC');
	$arData = array();
	$obRes = CCrmQuote::GetList(
		$arOrder,
		$arFilter,
		false,
		($nPageTop === false) ? false : array('nTopCount' => intval($nPageTop)),
		$arSelect
	);
	$arFiles = array();
	while ($arRes = $obRes->Fetch())
	{
		$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
		$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '').$arRes['CONTACT_FULL_NAME'];

		$quoteTitle = empty($arRes['TITLE']) ? $arRes['QUOTE_NUMBER'] : $arRes['QUOTE_NUMBER'].' - '.$arRes['TITLE'];

		$arData[] =
			array(
				'id' => $multi? CCrmQuote::OWNER_TYPE.'_'.$arRes['ID']: $arRes['ID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_quote_show'),
					array(
						'quote_id' => $arRes['ID']
					)
				),
				'title' => empty($quoteTitle) ? '' : str_replace(array(';', ','), ' ', $quoteTitle),
				'desc' => $clientTitle,
				'type' => 'quote'
			);
	}

	__CrmQuoteListEndResonse($arData);
}
elseif ($action === 'SAVE_PROGRESS')
{
	$ID = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0;
	$typeName = isset($_REQUEST['TYPE']) ? $_REQUEST['TYPE'] : '';
	$statusID = isset($_REQUEST['VALUE']) ? $_REQUEST['VALUE'] : '';

	$targetTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Quote);
	if($statusID === '' || $ID <= 0  || $typeName !== $targetTypeName)
	{
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(
			array('ERROR' => 'Invalid data!')
		);
		die();
	}

	$entityAttrs = $userPerms->GetEntityAttr($targetTypeName, array($ID));
	if (!$userPerms->CheckEnityAccess($targetTypeName, 'WRITE', $entityAttrs[$ID]))
	{
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(
			array('ERROR' => 'Access denied!')
		);
		die();
	}

	$arFields = CCrmQuote::GetByID($ID, false);

	if(!is_array($arFields))
	{
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(
			array('ERROR' => 'Not found!')
		);
		die();
	}

	if(isset($arFields['CREATED_BY_ID']))
	{
		unset($arFields['CREATED_BY_ID']);
	}

	if(isset($arFields['DATE_CREATE']))
	{
		unset($arFields['DATE_CREATE']);
	}

	if(isset($arFields['MODIFY_BY_ID']))
	{
		unset($arFields['MODIFY_BY_ID']);
	}

	if(isset($arFields['DATE_MODIFY']))
	{
		unset($arFields['DATE_MODIFY']);
	}

	$arFields['STATUS_ID'] = $statusID;
	$CCrmQuote = new CCrmQuote(false);
	$CCrmQuote->Update($ID, $arFields, true, true, array('DISABLE_USER_FIELD_CHECK' => true, 'REGISTER_SONET_EVENT' => true));

	$APPLICATION->RestartBuffer();
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject(
		array(
			'TYPE' => $targetTypeName,
			'ID' => $ID,
			'VALUE' => $statusID
		)
	);
	die();
}
elseif ($action === 'GET_ROW_COUNT')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmQuote::CheckReadPermission(0, $userPerms))
	{
		__CrmQuoteListEndResonse(array('ERROR' => 'Access denied.'));
	}

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
	if(!($gridID !== ''
		&& isset($_SESSION['CRM_GRID_DATA'])
		&& isset($_SESSION['CRM_GRID_DATA'][$gridID])
		&& is_array($_SESSION['CRM_GRID_DATA'][$gridID])))
	{
		__CrmQuoteListEndResonse(array('DATA' => array('TEXT' => '')));
	}

	$gridData = $_SESSION['CRM_GRID_DATA'][$gridID];
	$filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
	$result = CCrmQuote::GetList(array(), $filter, array(), false, array(), array());

	$text = GetMessage('CRM_QUOTE_LIST_ROW_COUNT', array('#ROW_COUNT#' => $result));
	if($text === '')
	{
		$text = $result;
	}
	__CrmQuoteListEndResonse(array('DATA' => array('TEXT' => $text)));
}
?>
