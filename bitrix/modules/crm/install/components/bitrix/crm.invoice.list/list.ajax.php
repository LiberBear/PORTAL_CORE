<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmInvoiceListEndResonse'))
{
	function __CrmInvoiceListEndResonse($result)
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
	if($userPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ'))
	{
		return;
	}

	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

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
		$arFilter['ID'] = (int) $search;
	else if (preg_match('/(.*)\[(\d+?)\]/i'.BX_UTF_PCRE_MODIFIER, $search, $arMatches))
	{
		$arFilter['ID'] = (int) $arMatches[2];
		$arFilter['%ORDER_TOPIC'] = trim($arMatches[1]);
		$arFilter['LOGIC'] = 'OR';
	}
	else
		$arFilter['%ORDER_TOPIC'] = $search;

	$arInvoiceStatusList = CCrmStatus::GetStatusListEx('INVOICE_STATUS');
	$arSelect = array('ID', 'ORDER_TOPIC', 'STATUS_ID');
	$arOrder = array('ORDER_TOPIC' => 'ASC');
	$arData = array();
	$obRes = CCrmInvoice::GetList($arOrder, $arFilter, false, (intval($nPageTop) > 0) ? array('nTopCount' => $nPageTop) : false, $arSelect);
	$arFiles = array();
	while ($arRes = $obRes->Fetch())
	{
		$arData[] =
			array(
				'id' => $multi? 'I_'.$arRes['ID']: $arRes['ID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_invoice_show'),
					array(
						'invoice_id' => $arRes['ID']
					)
				),
				'title' => (str_replace(array(';', ','), ' ', $arRes['ORDER_TOPIC'])),
				'desc' => isset($arInvoiceStatusList[$arRes['STATUS_ID']])? $arInvoiceStatusList[$arRes['STATUS_ID']]: '',
				'type' => 'invoice'
			)
		;
	}

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arData);
	die();
}
elseif ($action === 'SAVE_PROGRESS')
{
	CUtil::JSPostUnescape();

	$errMessage = '';
	$ID = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0;
	$typeName = isset($_REQUEST['TYPE']) ? $_REQUEST['TYPE'] : '';
	$statusID = isset($_REQUEST['VALUE']) ? $_REQUEST['VALUE'] : '';

	// status sort array
	$statusSort = array();
	$arInvoiceStatuses = CCrmStatusInvoice::GetStatus('INVOICE_STATUS');
	foreach ($arInvoiceStatuses as $statusId => $statusInfo)
	{
		$statusSort[$statusId] = $statusInfo['SORT'];
	}
	unset($arInvoiceStatuses);

	$statusParams = array();
	$statusParams['STATE_SUCCESS'] = (isset($_REQUEST['VALUE']) && $_REQUEST['VALUE'] === 'P') ? true : false;
	$statusParams['STATE_FAILED'] = (isset($_REQUEST['VALUE']) && $statusSort[$_REQUEST['VALUE']] >= $statusSort['D']) ? true : false;
	if ($statusParams['STATE_SUCCESS'])
	{
		$statusParams['PAY_VOUCHER_NUM'] = isset($_REQUEST['PAY_VOUCHER_NUM']) ? substr(trim($_REQUEST['PAY_VOUCHER_NUM']), 0, 20) : '';
		$statusParams['DATE_MARKED'] = $statusParams['PAY_VOUCHER_DATE'] = isset($_REQUEST['PAY_VOUCHER_DATE']) ? trim($_POST['PAY_VOUCHER_DATE']) : null;
		$statusParams['REASON_MARKED'] = isset($_REQUEST['REASON_MARKED_SUCCESS']) ? substr(trim($_REQUEST['REASON_MARKED_SUCCESS']), 0, 255) : '';
	}
	elseif ($statusParams['STATE_FAILED'])
	{
		$statusParams['DATE_MARKED'] = isset($_REQUEST['DATE_MARKED']) ? trim($_POST['DATE_MARKED']) : null;
		$statusParams['REASON_MARKED'] = isset($_REQUEST['REASON_MARKED']) ? substr(trim($_REQUEST['REASON_MARKED']), 0, 255) : '';
	}

	$targetTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Invoice);
	if($statusID === '' || $ID <= 0  || $typeName !== $targetTypeName)
	{
		return;
	}

	$entityAttrs = $userPerms->GetEntityAttr($targetTypeName, array($ID));
	if (!$userPerms->CheckEnityAccess($targetTypeName, 'WRITE', $entityAttrs[$ID]))
	{
		return;
	}

	if (empty($errMessage))
	{
		$CCrmInvoice = new CCrmInvoice(false);
		if (!$CCrmInvoice->SetStatus($ID, $statusID, $statusParams, array('SYNCHRONIZE_LIVE_FEED' => true)))
		{
			$errMessage = 'Status error!';
		}
	}

	$APPLICATION->RestartBuffer();
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	if (empty($errMessage))
	{
		$data = array(
			'TYPE' => $targetTypeName,
			'ID' => $ID,
			'VALUE' => $statusID,
			'STATE_SUCCESS' => $statusParams['STATE_SUCCESS'] ? 'Y' : 'N',
			'STATE_FAILED' => $statusParams['STATE_FAILED'] ? 'Y' : 'N'
		);
		if ($statusParams['STATE_SUCCESS'])
		{
			$data['PAY_VOUCHER_NUM'] = $statusParams['PAY_VOUCHER_NUM'];
			$data['PAY_VOUCHER_DATE'] = $statusParams['PAY_VOUCHER_DATE'];
			$data['REASON_MARKED_SUCCESS'] = $statusParams['REASON_MARKED'];
		}
		elseif ($statusParams['STATE_FAILED'])
		{
			$data['DATE_MARKED'] = $statusParams['DATE_MARKED'];
			$data['REASON_MARKED'] = $statusParams['REASON_MARKED'];
		}
		echo CUtil::PhpToJsObject($data);
	}
	else
	{
		echo CUtil::PhpToJSObject(
			array('ERROR' => $errMessage)
		);
	}
	die();
}
elseif ($action === 'REBUILD_STATISTICS')
{
	//~CRM_REBUILD_INVOICE_STATISTICS
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmInvoice::CheckUpdatePermission(0))
	{
		__CrmInvoiceListEndResonse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_REBUILD_INVOICE_STATISTICS', 'N') !== 'Y')
	{
		__CrmInvoiceListEndResonse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_INVOICE_LIST_REBUILD_STATISTICS_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_INVOICE_STATISTICS_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData) : array();
	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? (int)$progressData['LAST_ITEM_ID'] : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? (int)$progressData['PROCESSED_ITEMS'] : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? (int)$progressData['TOTAL_ITEMS'] : 0;
	if($totalItemQty <= 0)
	{
		//COUNT form GetList(array(), array(), array()) is not currently supported
		$dbResult = CCrmInvoice::GetList(array(), array('CHECK_PERMISSIONS' => 'N'), false, false, array('ID'));
		$dbResult->NavStart();
		$totalItemQty = (int)$dbResult->NavRecordCount;
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmInvoice::GetList(
		array('ID' => 'ASC'),
		$filter,
		false,
		array('nTopCount' => 20),
		array('ID')
	);

	$itemIDs = array();
	$itemQty = 0;
	if(is_object($dbResult))
	{
		while($fields = $dbResult->Fetch())
		{
			$itemIDs[] = (int)$fields['ID'];
			$itemQty++;
		}
	}

	if($itemQty > 0)
	{
		CCrmInvoice::RebuildStatistics($itemIDs);

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_REBUILD_INVOICE_STATISTICS_PROGRESS', serialize($progressData));
		__CrmInvoiceListEndResonse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_INVOICE_LIST_REBUILD_STATISTICS_PROGRESS_SUMMARY',
					array(
						'#PROCESSED_ITEMS#' => $processedItemQty,
						'#TOTAL_ITEMS#' => $totalItemQty
					)
				)
			)
		);
	}
	else
	{
		COption::RemoveOption('crm', '~CRM_REBUILD_INVOICE_STATISTICS');
		COption::RemoveOption('crm', '~CRM_REBUILD_INVOICE_STATISTICS_PROGRESS');
		__CrmInvoiceListEndResonse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_INVOICE_LIST_REBUILD_STATISTICS_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
elseif ($action === 'REBUILD_SUM_STATISTICS')
{
	//~CRM_REBUILD_INVOICE_SUM_STATISTICS
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmInvoice::CheckUpdatePermission(0))
	{
		__CrmInvoiceListEndResonse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_REBUILD_INVOICE_SUM_STATISTICS', 'N') !== 'Y')
	{
		__CrmInvoiceListEndResonse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_INVOICE_LIST_REBUILD_STATISTICS_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_INVOICE_SUM_STATISTICS_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData) : array();
	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		//COUNT form GetList(array(), array(), array()) is not currently supported
		$dbResult = CCrmInvoice::GetList(array(), array('CHECK_PERMISSIONS' => 'N'), false, false, array('ID'));
		$dbResult->NavStart();
		$totalItemQty = (int)$dbResult->NavRecordCount;
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmInvoice::GetList(
		array('ID' => 'ASC'),
		$filter,
		false,
		array('nTopCount' => 20),
		array('ID')
	);

	$itemIDs = array();
	$itemQty = 0;
	if(is_object($dbResult))
	{
		while($fields = $dbResult->Fetch())
		{
			$itemIDs[] = (int)$fields['ID'];
			$itemQty++;
		}
	}

	if($itemQty > 0)
	{
		CCrmInvoice::RebuildStatistics(
			$itemIDs,
			array(
				'FORCED' => true,
				'ENABLE_SUM_STATISTICS' => true,
				'ENABLE_HISTORY'=> false
			)
		);

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_REBUILD_INVOICE_SUM_STATISTICS_PROGRESS', serialize($progressData));
		__CrmInvoiceListEndResonse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_INVOICE_LIST_REBUILD_STATISTICS_PROGRESS_SUMMARY',
					array(
						'#PROCESSED_ITEMS#' => $processedItemQty,
						'#TOTAL_ITEMS#' => $totalItemQty
					)
				)
			)
		);
	}
	else
	{
		COption::RemoveOption('crm', '~CRM_REBUILD_INVOICE_SUM_STATISTICS');
		COption::RemoveOption('crm', '~CRM_REBUILD_INVOICE_SUM_STATISTICS_PROGRESS');
		__CrmInvoiceListEndResonse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_INVOICE_LIST_REBUILD_STATISTICS_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
?>