<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!$USER->IsAuthorized())
{
?>
	<div class="authorization-block"><a href="<?=(SITE_DIR."auth/?backurl=".$arResult["BACKURL"])?>" class="authorization-text"><?=GetMessage("AUTH_AUTH")?></a></div>
<?
	return;
}

$videoSteps = array(
	array(
		"id" => "start",
		"patterns" => array(),
		"learning_path" => "/start/",
		"title" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_1"),
		"title_full" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_FULL_1"),
		"youtube" => GetMessage("BITRIX24_HELP_VIDEO_1")
	),
	array(
		"id" => "tasks",
		"learning_path" => "/tasks/",
		"patterns" => array(
			"~^".SITE_DIR."(company|contacts)/personal/user/\\d+/tasks/~",
			"~^".SITE_DIR."workgroups/group/\\d+/tasks/~"
		),
		"title" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_2"),
		"title_full" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_FULL_2"),
		"youtube" => GetMessage("BITRIX24_HELP_VIDEO_2")
	),
	array(
		"id" => "calendar",
		"learning_path" => "/calendar/",
		"patterns" => array(
			"~^".SITE_DIR."(company|contacts)/personal/user/\\d+/calendar/~",
			"~^".SITE_DIR."workgroups/group/\\d+/calendar/~"
		),
		"title" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_3"),
		"title_full" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_FULL_3"),
		"youtube" => GetMessage("BITRIX24_HELP_VIDEO_3")
	),
	array(
		"id" => "docs",
		"learning_path" => "/docs/",
		"patterns" => array(
			"~^".SITE_DIR."(company|contacts)/personal/user/\\d+/disk/~",
			"~^".SITE_DIR."docs/~",
			"~^".SITE_DIR."workgroups/group/\\d+/disk/~"
		),
		"title" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_4"),
		"title_full" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_FULL_4"),
		"youtube" => GetMessage("BITRIX24_HELP_VIDEO_4")
	),
	array(
		"id" => "crm",
		"learning_path" => "/crm/",
		"patterns" => array("~^".SITE_DIR."crm/~"),
		"title" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_14"),
		"title_full" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_FULL_14"),
		"youtube" => GetMessage("BITRIX24_HELP_VIDEO_14")
	)
);

if (LANGUAGE_ID == "ru" || LANGUAGE_ID == "ua")
{
	$videoSteps[] = array(
		"id" => "company_struct",
		"learning_path" => "/company/vis_structure.php",
		"patterns" => $USER->CanDoOperation("bitrix24_invite") ? array("~^".SITE_DIR."company/vis_structure.php~") : array(),
		"title" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_13"),
		"title_full" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_FULL_13"),
		"youtube" => GetMessage("BITRIX24_HELP_VIDEO_13")
	);

	$videoSteps[] = array(
		"id" => "marketplace",
		"learning_path" => "/marketplace/",
		"patterns" => array("~^".SITE_DIR."marketplace/~"),
		"title" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_15"),
		"title_full" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_FULL_15"),
		"youtube" => GetMessage("BITRIX24_HELP_VIDEO_15")
	);

	$videoSteps[] = array(
		"id" => "im",
		"learning_path" => "",
		"patterns" => array(),
		"title" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_16"),
		"title_full" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_FULL_16"),
		"youtube" => GetMessage("BITRIX24_HELP_VIDEO_16")
	);
}
else
{
	$addVideo = array(
		"5" => array("crm_import", array(), "/crm/import/"),
		"6" => array("crm_email", array(), "/crm/email/"),
		"7" => array("crm_perms", array("~^".SITE_DIR."crm/configs/perms/~"), "/crm/configs/perms/"),
		"8" => array("crm_lists", array("~^".SITE_DIR."crm/configs/status/~"), "/crm/lists/"),
		"9" => array("crm_bp", array("~^".SITE_DIR."crm/configs/bp/~"), "/crm/configs/bp/"),
		"10" => array("im", array(), "/im/"),
		"11" => array("lists", array("~^".SITE_DIR."company/lists/~"), "/company/lists/"),
		"12" => array("twitter", array(), "/twitter/")
	);

	foreach ($addVideo as $number => $ids)
	{
		$videoSteps[] = array(
			"id" => $ids[0],
			"patterns" => $ids[1],
			"learning_path" => $ids[2],
			"title" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_".$number),
			"title_full" => GetMessage("BITRIX24_HELP_VIDEO_TITLE_FULL_".$number),
			"youtube" => GetMessage("BITRIX24_HELP_VIDEO_".$number)
		);
	}
}

?>

<script type="text/javascript">
	function showUserMenu(bindElement)
	{
		BX.addClass(bindElement, "user-block-active");
		BX.PopupMenu.show("user-menu", bindElement, [
			{ text : "<?=GetMessageJS("AUTH_PROFILE")?>", className : "user-menu-myPage", href : "<?=CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_SONET_PROFILE'], array("user_id" => $USER->GetID() ))?>"},
			{ text : "<?=GetMessageJS("AUTH_CHANGE_PROFILE")?>", className : "user-menu-edit-data", href : "<?=CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_SONET_PROFILE_EDIT'], array("user_id" => $USER->GetID() ))?>"},
			<?if(isset($arResult['B24NET_WWW'])):?>
			{ text : "<?=GetMessageJS("AUTH_PROFILE_B24NET")?>", className : "user-menu-myPage", href : "<?=CUtil::JSEscape($arResult['B24NET_WWW'])?>"},
			<?endif;?>
			<?if (IsModuleInstalled("im")):?>
			{ text : "<?=GetMessageJS("AUTH_CHANGE_NOTIFY")?>", className : "user-menu-notify", onclick : "BXIM.openSettings({'onlyPanel':'notify'})"},
			<?endif?>
			<?if (CModule::IncludeModule("intranet") && CIntranetUtils::IsExternalMailAvailable()):?>
			{ text : "<?=GetMessageJS("AUTH_CHANGE_MAIL")?>", className : "user-menu-mail", href : "<?=CUtil::JSEscape($arParams['PATH_TO_SONET_EXTMAIL_SETUP']); ?>" },
				<?if (is_object($USER) && $USER->IsAuthorized() && ($USER->isAdmin() || $USER->canDoOperation('bitrix24_config'))):?>
					<?if (IsModuleInstalled('bitrix24') || in_array(LANGUAGE_ID, array('ru', 'ua'))):?>
			{ text : "<?=GetMessageJS("AUTH_MANAGE_MAIL")?>", className : "user-menu-mail-set", href : "<?=CUtil::JSEscape($arParams['PATH_TO_SONET_EXTMAIL_MANAGE']); ?>" },
					<?endif?>
				<?endif?>
			<?endif?>
				{ text : "<?=GetMessageJS("AUTH_LOGOUT")?>", className : "user-menu-logOut", href : "/auth/?logout=yes&backurl=" + encodeURIComponent(B24.getBackUrl()) }
		],
			{
				offsetTop: 9,
				offsetLeft: 43,
				angle: true,
				events: {
					onPopupClose : function() {
						BX.removeClass(this.bindElement, "user-block-active");
					}
				}
			});
	}
</script>


<?
$arViewedSteps = CUserOptions::GetOption("bitrix24", "help_views", array());
$currentStepId = __getStepByUrl($videoSteps, $APPLICATION->GetCurDir());

if (!in_array("start", $arViewedSteps))
{
	$currentStepId = "start";
}

require_once($_SERVER["DOCUMENT_ROOT"].$this->GetFolder()."/functions.php");
CIntranetPopupShow::getInstance()->init(($currentStepId && !in_array($currentStepId, $arViewedSteps) ? "Y" : "N"));

AddEventHandler("intranet", "OnIntranetPopupShow", "onIntranetPopupShow");
if (!function_exists("onIntranetPopupShow"))
{
	function onIntranetPopupShow()
	{
		$isPopupShowed = CIntranetPopupShow::getInstance()->isPopupShowed();
		if ($isPopupShowed == "Y")
			return false;
	}
}
/*
if (!IsModuleInstalled("bitrix24"))
{
	$frame = $this->createFrame("help")->begin("");
	$frameAjax = \Bitrix\Main\Page\Frame::isAjaxRequest();
	?>
	<script type="text/javascript">
		B24.VideoPopupWindow.init(<?=CUtil::PhpToJSObject($videoSteps);?>, { site_dir : "<?=SITE_DIR?>", currentStepId :"<?=$currentStepId?>" , learning_url : "<?=GetMessageJS("BITRIX24_HELP_VIDEO_LEARNING_URL")?>", learning_question : "<?=GetMessageJS("BITRIX24_HELP_VIDEO_LEARNING_QUESTION")?>", learning_answer : "<?=GetMessageJS("BITRIX24_HELP_VIDEO_LEARNING_ANSWER")?>", learning_title : "<?=GetMessageJS("BITRIX24_HELP_VIDEO_TITLE_OTHER")?>", learning_title_full : "<?=GetMessageJS("BITRIX24_HELP_VIDEO_TITLE_FULL_OTHER")?>"});
		B24.VideoPopupWindow.setCurrentStep("<?=$currentStepId?>");<?

		if ($currentStepId && !in_array($currentStepId, $arViewedSteps)):?>
			<?if (!$frameAjax):?>BX.bind(window, "load", function() {<?endif?>
				BX.userOptions.save("bitrix24", "help_views",  "<?=$currentStepId?>", "<?=$currentStepId?>");
				B24.VideoPopupWindow.show();
			<?if (!$frameAjax):?>});<?endif;
		endif?>
	</script>
<?
	$frame->end();
}*/?>

<div class="user-block" onclick="showUserMenu(this)">
	<span class="user-img" <?if ($arResult["USER_PERSONAL_PHOTO_SRC"]):?>style="background: url('<?=$arResult["USER_PERSONAL_PHOTO_SRC"]?>') no-repeat center;"<?endif?>></span><?if (!$arResult["SHOW_LICENSE_BUTTON"]):?><span class="user-name"><?=$arResult["USER_NAME"]?></span><?endif?>
</div>
<?if ($arResult["SHOW_LICENSE_BUTTON"]):?>
<?
	$arJsParams = array(
		"LICENSE_PATH" => $arResult["B24_LICENSE_PATH"],
		"COUNTER_URL" => $arResult["LICENSE_BUTTON_COUNTER_URL"],
		"HOST" => $arResult["HOST_NAME"]
	);
?>
<a href="javascript:void(0)" onclick="BX.Bitrix24.SystemAuthForm.licenseHandler(<?=CUtil::PhpToJSObject($arJsParams)?>)" class="upgrade-btn <?if (!isset($_SESSION["B24_LICENSE_BUTTON"])) echo " upgrade-btn-anim"; if (!in_array(LANGUAGE_ID, array("ru", "ua"))) echo " upgrade-btn-en"?>">
	<span class="upgrade-btn-icon"></span>
	<span class="upgrade-btn-text"><?=GetMessage("B24_LICENSE_ALL")?></span>
</a>
<?endif?>
<div class="help-block" id="bx-help-block" title="<?=GetMessage("AUTH_HELP")?>">
	<div class="help-icon-border"></div>
	<div class="help-block-icon"></div>
	<div class="help-block-counter-wrap" id="bx-help-notify">
		<?if (false && isset($arResult["HELP_NOTIFY_NUM"]) && intval($arResult["HELP_NOTIFY_NUM"])):?>
		<div class="help-block-counter"><?=$arResult["HELP_NOTIFY_NUM"]?></div>
		<?endif?>
	</div>
</div>

<?$frame = $this->createFrame("b24_helper")->begin("");?>
	<?
	CJSCore::Init(array('helper'));
	$helpUrl = GetMessage('B24_HELP_URL');
	$helpUrl = CHTTP::urlAddParams($helpUrl, array(
			"url" => urlencode("https://".$_SERVER["HTTP_HOST"].$APPLICATION->GetCurPageParam()),
			"is_admin" => IsModuleInstalled("bitrix24") && CBitrix24::IsPortalAdmin($USER->GetID()) || !IsModuleInstalled("bitrix24") && $USER->IsAdmin() ? 1 : 0,
			"user_id" => $USER->GetID(),
			"tariff" => COption::GetOptionString("main", "~controller_group_name", ""),
			"is_cloud" => IsModuleInstalled("bitrix24") ? "1" : "0",
			"user_date_register" => $arResult["USER_DATE_REGISTER"],
			"portal_date_register" => IsModuleInstalled("bitrix24") ? COption::GetOptionString("main", "~controller_date_create", "") : ""
		)
	);

	$frameOpenUrl = CHTTP::urlAddParams($helpUrl, array(
			"action" => "open",
		)
	);
	$frameCloseUrl = CHTTP::urlAddParams($helpUrl, array(
			"action" => "close",
		)
	);

	$host = IsModuleInstalled("bitrix24") ? BX24_HOST_NAME : CIntranetUtils::getHostName();
	$notifyData = array(
		"is_admin" => IsModuleInstalled("bitrix24") && CBitrix24::IsPortalAdmin($USER->GetID()) || !IsModuleInstalled("bitrix24") && $USER->IsAdmin() ? 1 : 0,
		"user_id" => $USER->GetID(),
		"tariff" => COption::GetOptionString("main", "~controller_group_name", ""),
		"host" => $host,
		"key" => IsModuleInstalled("bitrix24") ? CBitrix24::RequestSign($host.$USER->GetID()) : md5($host.$USER->GetID().'BX_USER_CHECK'),
		"is_cloud" => IsModuleInstalled("bitrix24") ? "1" : "0"
	);
	?>

	<?if ($arResult["SHOW_HELPER_HERO"] == "Y"):?>
	<script>
		BX.Helper.showAnimatedHero();
	</script>
	<?endif?>

	<script>
		BX.message({
			HELPER_LOADER: '<?=GetMessageJS('B24_HELP_LOADER')?>',
			HELPER_TITLE: '<?=GetMessageJS('B24_HELP_TITLE_NEW')?>'
		});
		BX.Helper.init({
			frameOpenUrl : '<?=CUtil::JSEscape($frameOpenUrl)?>',
			frameCloseUrl : '<?=CUtil::JSEscape($frameCloseUrl)?>',
			helpBtn : BX('bx-help-block'),
			notifyBlock : BX('bx-help-notify'),
			topPaddingNode : BX('header'),
			langId: '<?=LANGUAGE_ID?>',
			reloadPath: '<?=GetMessageJS("B24_HELP_RELOAD_URL")?>',
			ajaxUrl: '<?=$this->GetFolder()."/ajax.php"?>',
			currentStepId: '<?=CUtil::JSEscape($arResult["CURRENT_STEP_ID"])?>',
			needCheckNotify: '<?=($arResult["NEED_CHECK_HELP_NOTIFICATION"] == "Y" ? "Y" : "N")?>',
			notifyNum: '<?=CUtil::JSEscape($arResult["HELP_NOTIFY_NUM"])?>',
			notifyData: <?=CUtil::PhpToJSObject($notifyData)?>,
			notifyUrl: '<?=GetMessageJS("B24_HELP_NOTIFY_URL")?>',
			helpUrl: '<?=GetMessageJS("B24_HELPDESK_URL")?>',
			runtimeUrl: '//helpdesk.bitrix24.ru/widget/hero/runtime.js'
		});
	</script>
<?$frame->end();?>
