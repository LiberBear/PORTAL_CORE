<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var TasksBaseComponent $component */

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest()->toArray();

$parameters = array();
if(is_array($arParams['FORM_PARAMETERS']))
{
	$parameters = $arParams['FORM_PARAMETERS'];
}

$edit = $arParams['ACTION'] == 'edit';

if($edit)
{
	$template = '.default';
	$parameters['SUB_ENTITY_SELECT'] = array(
		"TAG",
		"CHECKLIST",
		"REMINDER",
		"PROJECTDEPENDENCE",
		"TEMPLATE",
		"RELATEDTASK",
	);
}
else
{
	$template = 'view';
	$parameters['SUB_ENTITY_SELECT'] = array(
		"TAG",
		"CHECKLIST",
		"REMINDER",
		"PROJECTDEPENDENCE",
        "TEMPLATE",
		"TEMPLATE.SOURCE",
		"LOG",
		"ELAPSEDTIME",
		"DAYPLAN"
	);
}

$parameters['AUX_DATA_SELECT'] = array(
	"COMPANY_WORKTIME",
	"USER_FIELDS"
);
if(!$arResult['IFRAME'])
{
    // do not need template list in popup
	$parameters['AUX_DATA_SELECT'][] = 'TEMPLATE';
}
else
{
    // turn off some controls
    //$parameters['ENABLE_CANCEL_BUTTON'] = 'N';
    $parameters['ENABLE_FOOTER_UNPIN'] = 'N';
    $parameters['ENABLE_MENU_TOOLBAR'] = 'N';

    // disable redirect in case of edit opened directly
	if($edit)
	{
		if($request['SOURCE'] != 'view')
		{
			$parameters['REDIRECT_ON_SUCCESS'] = 'N';
			$parameters['CANCEL_ACTION_IS_EVENT'] = true; // fire global event "NOOP" when "Cancel" button pressed
		}
	}
}
?>

<?if($arResult['IFRAME']):?>

	<?
	// to stay inside iframe after form submit and also after clicking on "to task" links
	// for other links target="_top" is used
	$parameters['TASK_URL_PARAMETERS'] = array('IFRAME' => 'Y');

	global $APPLICATION;
	$APPLICATION->RestartBuffer();
	?>

	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID?>" lang="<?=LANGUAGE_ID?>">
		<head>
			<script type="text/javascript">
				// Prevent loading page without header and footer
				if (window == window.top)
				{
					window.location = "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam("", array("IFRAME"))); ?>";
				}
			</script>
			<? $APPLICATION->ShowHead(); ?>
		</head>
		<body class="template-<?=SITE_TEMPLATE_ID?> <?$APPLICATION->ShowProperty("BodyClass");?>" onload="window.top.BX.onCustomEvent(window.top, 'tasksIframeLoad');" onunload="window.top.BX.onCustomEvent(window.top, 'tasksIframeUnload');">

			<div class="task-iframe-workarea" id="tasks-content-outer">
				<div class="task-iframe-sidebar"><? $APPLICATION->ShowViewContent("sidebar"); ?></div>
				<div class="task-iframe-content"><?
endif;

if(\Bitrix\Tasks\Util\Restriction::canManageTask())
{
	$APPLICATION->IncludeComponent(
		"bitrix:tasks.task",
		$template,
		$parameters,
		$component,
		array("HIDE_ICONS" => "Y")
	);
}
else
{
	$APPLICATION->IncludeComponent("bitrix:bitrix24.business.tools.info", "", array(
		"SHOW_TITLE" => "Y"
	));
}

if($arResult['IFRAME']):
				?></div>
			</div>
		</body>
	</html><?
	require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
	die();?>

<?endif?>