<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$arResult['PATH_TO_WIDGET'] = isset($arParams['PATH_TO_WIDGET']) ? $arParams['PATH_TO_WIDGET'] : $APPLICATION->GetCurPage();
$arResult['PATH_TO_LIST'] = isset($arParams['PATH_TO_LIST']) ? $arParams['PATH_TO_LIST'] : $APPLICATION->GetCurPage();
$arParams['PATH_TO_DEMO_DATA'] = isset($arParams['PATH_TO_DEMO_DATA']) ? $arParams['PATH_TO_DEMO_DATA'] : '';
$arResult['GUID'] = $arParams['GUID'] = isset($arParams['GUID']) ? $arParams['GUID'] : 'crm_widget_panel';
$arResult['LAYOUT'] = $arParams['LAYOUT'] =isset($arParams['LAYOUT']) ? $arParams['LAYOUT'] : 'L50R50';
$arResult['MAX_GRAPH_COUNT'] = $arParams['MAX_GRAPH_COUNT'] = isset($arParams['MAX_GRAPH_COUNT']) ? (int)$arParams['MAX_GRAPH_COUNT'] : 6;
$arResult['MAX_WIDGET_COUNT'] = $arParams['MAX_WIDGET_COUNT'] = isset($arParams['MAX_WIDGET_COUNT']) ? (int)$arParams['MAX_WIDGET_COUNT'] : 15;
$arResult['NAVIGATION_CONTEXT_ID'] = $arParams['NAVIGATION_CONTEXT_ID'] =isset($arParams['NAVIGATION_CONTEXT_ID']) ? $arParams['NAVIGATION_CONTEXT_ID'] : '';
$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arParams['IS_SUPERVISOR'] = isset($arParams['IS_SUPERVISOR']) && $arParams['IS_SUPERVISOR'];

$arResult['DEMO_TITLE'] = isset($arParams['~DEMO_TITLE']) ? $arParams['~DEMO_TITLE'] : '';
$arResult['DEMO_CONTENT'] = isset($arParams['~DEMO_CONTENT']) ? $arParams['~DEMO_CONTENT'] : '';

$counterID = isset($arParams['~NAVIGATION_COUNTER_ID']) ? (int)$arParams['~NAVIGATION_COUNTER_ID'] : CCrmUserCounter::Undefined;
if(CCrmUserCounter::IsTypeDefined($counterID))
{
	$counter = new CCrmUserCounter(CCrmPerms::GetCurrentUserID(), $counterID);
	$arResult['NAVIGATION_COUNTER'] = $counter->GetValue(false);
}
else
{
	$arResult['NAVIGATION_COUNTER'] = isset($arParams['~NAVIGATION_COUNTER'])
		? (int)$arParams['~NAVIGATION_COUNTER'] : 0;
}

$entityType = isset($arParams['~ENTITY_TYPE']) ? strtoupper($arParams['~ENTITY_TYPE']) : '';
$entityTypes = isset($arParams['~ENTITY_TYPES']) && is_array($arParams['~ENTITY_TYPES']) ? $arParams['~ENTITY_TYPES'] : array();
if(empty($entityTypes))
{
	if($entityType !== '')
	{
		$entityTypes[] = $entityType;
	}
}
elseif($entityType === '')
{
	$entityType = $entityTypes[0];
}

$arResult['ENTITY_TYPES'] = $entityTypes;
$arResult['DEFAULT_ENTITY_TYPE'] = $entityType;


$options = CUserOptions::GetOption('crm.widget_panel', $arResult['GUID'], array());

if(isset($options['layout']))
{
	$arResult['LAYOUT'] = $options['layout'];
}

$enableDemo = $arResult['ENABLE_DEMO'] = !isset($options['enableDemoMode']) || $options['enableDemoMode'] === 'Y';
$arParams['ROWS'] = isset($arParams['ROWS']) ? $arParams['ROWS'] : array();
if(!$enableDemo && isset($options['rows']))
{
	$arParams['ROWS'] = $options['rows'];
}

$arParams['FILTER'] = isset($arParams['FILTER']) ? $arParams['FILTER'] : array();
$arResult['FILTER'] = array(
	array('id' => 'RESPONSIBLE_ID', 'name' => GetMessage('CRM_FILTER_FIELD_RESPONSIBLE'), 'default' => true, 'enable_settings' => true, 'type' => 'user'),
	array('id' => 'PERIOD', 'name' => GetMessage('CRM_FILTER_FIELD_PERIOD'), 'default' => true, 'enable_settings' => true, 'type' => 'period')
);

$gridOptions = new CGridOptions($arResult['GUID']);
$arResult['FILTER_FIELDS'] = $gridOptions->GetFilter($arResult['FILTER']);
$arResult['WIDGET_FILTER'] = Bitrix\Crm\Widget\Filter::internalizeParams($arResult['FILTER_FIELDS']);

$gridSettings = $gridOptions->GetOptions();
$visibleRows = isset($gridSettings['filter_rows']) ? explode(',', $gridSettings['filter_rows']) : array();

$arResult['FILTER_ROWS'] = array(
	'RESPONSIBLE_ID' => true,
	'PERIOD' => true
);

$arResult['FILTER_PRESETS'] = array(
	'filter_current_month' => array(
		'name' => Bitrix\Crm\Widget\FilterPeriodType::getDescription(Bitrix\Crm\Widget\FilterPeriodType::CURRENT_MONTH),
		'fields' => array('PERIOD' => Bitrix\Crm\Widget\FilterPeriodType::CURRENT_MONTH)
	),
	'filter_current_quarter' => array(
		'name' => Bitrix\Crm\Widget\FilterPeriodType::getDescription(Bitrix\Crm\Widget\FilterPeriodType::CURRENT_QUARTER),
		'fields' => array('PERIOD' => Bitrix\Crm\Widget\FilterPeriodType::CURRENT_QUARTER)
	)
);

if(!empty($visibleRows))
{
	foreach(array_keys($arResult['FILTER_ROWS']) as $k)
	{
		$arResult['FILTER_ROWS'][$k] = in_array($k, $visibleRows);
	}
}

$arResult['OPTIONS'] = array(
	'filter_rows' => implode(',', array_keys($arResult['FILTER_ROWS'])),
	'filters' => array_merge($arResult['FILTER_PRESETS'], $gridSettings['filters'])
);

$commonFilter = new Bitrix\Crm\Widget\Filter($arResult['WIDGET_FILTER']);
if($commonFilter->isEmpty())
{
	$commonFilter->setPeriodTypeID(Bitrix\Crm\Widget\FilterPeriodType::LAST_DAYS_30);
	$arResult['WIDGET_FILTER'] = $commonFilter->getParams();
}

$arResult['WIDGET_FILTER']['enableEmpty'] = false;
$arResult['WIDGET_FILTER']['defaultPeriodType'] = Bitrix\Crm\Widget\FilterPeriodType::LAST_DAYS_30;

$demoRows = null;
if($enableDemo && $arParams['PATH_TO_DEMO_DATA'] !== '')
{
	$demoFileName = $arParams['IS_SUPERVISOR'] ? 'supervisor' : 'employee';
	$demoRows = (include "{$arParams['PATH_TO_DEMO_DATA']}/{$demoFileName}.php");
}

$arResult['ROWS'] = array();
$rowQty = count($arParams['ROWS']);

$widgetCount = 0;
$maxWidgetCount = $arResult['MAX_WIDGET_COUNT'];
$factoryOptions = array('maxGraphCount' => $arResult['MAX_GRAPH_COUNT']);
for($i = 0; $i < $rowQty; $i++)
{
	if($maxWidgetCount > 0 && $widgetCount >= $maxWidgetCount)
	{
		break;
	}

	if(!isset($arParams['ROWS'][$i]))
	{
		continue;
	}

	$rowConfig = $arParams['ROWS'][$i];
	$row = array('cells' => array());
	if(isset($rowConfig['height']))
	{
		$row['height'] = $rowConfig['height'];
	}

	$cellConfigs = isset($rowConfig['cells']) ? $rowConfig['cells'] : array();
	$cellQty = count($cellConfigs);
	for($j = 0; $j < $cellQty; $j++)
	{
		if($maxWidgetCount > 0 && $widgetCount >= $maxWidgetCount)
		{
			break;
		}

		$demoCell = $enableDemo && isset($demoRows[$i]['cells'][$j])
			? $demoRows[$i]['cells'][$j] : null;

		$cell = array('controls' => array(), 'data' => array());
		$cellConfig = isset($cellConfigs[$j]) ? $cellConfigs[$j] : array();
		$controls = isset($cellConfig['controls']) ? $cellConfig['controls'] : array();
		$controlQty = count($controls);

		for($k = 0; $k < $controlQty; $k++)
		{
			if($maxWidgetCount > 0 && $widgetCount >= $maxWidgetCount)
			{
				break;
			}

			$control = $controls[$k];
			$cell['controls'][] = $control;

			if(isset($control['filter']) && is_array($control['filter']))
			{
				$filter = new Bitrix\Crm\Widget\Filter($control['filter']);
				if($filter->isEmpty())
				{
					$filter = $commonFilter;
				}
			}
			else
			{
				$filter = $commonFilter;
			}

			$widget = Bitrix\Crm\Widget\WidgetFactory::create($control, $filter, $factoryOptions);
			if(!$enableDemo)
			{
				$cell['data'][] = $widget->prepareData();
			}
			else
			{
				if($k === 0 && isset($demoCell['data']))
				{
					$demoData = $demoCell['data'];
				}
				elseif(isset($demoCell[$k]) && isset($demoCell[$k]['data']))
				{
					$demoData = $demoCell[$k]['data'];
				}
				else
				{
					$demoData = array();
				}

				$cell['data'][] = $widget->initializeDemoData($demoData);
			}

			$widgetCount++;
		}
		$row['cells'][] = $cell;
	}
	$arResult['ROWS'][] = $row;
}

$arResult['CURRENCY_FORMAT'] = CCrmCurrency::GetCurrencyFormatParams(CCrmCurrency::GetBaseCurrencyID());

$arResult['BUILDERS'] = array();
if(!$enableDemo && CCrmPerms::IsAdmin())
{
	$builders = null;
	foreach($arResult['ENTITY_TYPES'] as $entityType)
	{
		$entityBuilders = Bitrix\Crm\Statistics\StatisticEntryManager::prepareBuilderData(CCrmOwnerType::ResolveID($entityType));
		if(is_array($builders))
		{
			$builders = array_merge($builders, $entityBuilders);
		}
		else
		{
			$builders = $entityBuilders;
		}
	}

	foreach($builders as $builder)
	{
		if($builder['ACTIVE'])
		{
			$arResult['BUILDERS'][] = $builder;
		}
	}
}

$this->IncludeComponentTemplate();
