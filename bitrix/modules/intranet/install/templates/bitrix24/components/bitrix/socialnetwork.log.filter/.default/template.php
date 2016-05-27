<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$this->setFrameMode(true);


if (!is_array($arResult["PresetFilters"]) &&
	!(array_key_exists("SHOW_SETTINGS_LINK", $arParams) && $arParams["SHOW_SETTINGS_LINK"] == "Y"))
	return;

$isFiltered = false;
foreach (array("flt_created_by_id", "flt_group_id", "flt_to_user_id", "flt_date_datesel", "flt_show_hidden") as $param)
{
	if (array_key_exists($param, $_GET) && (strlen($_GET[$param]) > 0) && ($_GET[$param] !== "0"))
	{
		$isFiltered = true;
		break;
	}
}

if (!is_array($arResult["PageParamsToClear"]))
{
	$arResult["PageParamsToClear"] = array();
}

if ($arResult["MODE"] == "AJAX")
{
	ob_end_clean();
	$APPLICATION->RestartBuffer();

	?><div id="sonet-log-filter" class="sonet-log-filter-block">
		<div class="filter-block-title sonet-log-filter-title"><?=GetMessage("SONET_C30_T_FILTER_TITLE")?></div>
		<form method="GET" name="log_filter" target="_self" action="<?=POST_FORM_ACTION_URI?>">
		<input type="hidden" name="SEF_APPLICATION_CUR_PAGE_URL" value="<?=GetPagePath()?>"><?
		$userName = "";
		if (intval($arParams["CREATED_BY_ID"]) > 0)
		{
			$rsUser = CUser::GetByID($arParams["CREATED_BY_ID"]);
			if ($arUser = $rsUser->Fetch())
				$userName = CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false));
		}
		?><div class="filter-field">
			<label class="filter-field-title" for="filter-field-created-by"><?=GetMessage("SONET_C30_T_FILTER_CREATED_BY");?></label>
			<span class="webform-field webform-field-textbox<?=(!$arParams["CREATED_BY_ID"]?" webform-field-textbox-empty":"")?> webform-field-textbox-clearable">
				<span id="sonet-log-filter-created-by" class="webform-field-textbox-inner" style="width: 200px; padding: 0 20px 0 4px;">
					<input type="text" class="webform-field-textbox" id="filter-field-created-by" value="<?=$userName?>" style="height: 20px; width: 200px;" autocomplete="off" />
					<a class="sonet-log-field-textbox-clear" href=""></a>
				</span>
			</span>
		</div>
		<input type="hidden" name="flt_created_by_id" value="<?=$arParams["CREATED_BY_ID"]?>" id="filter_field_createdby_hidden">
		<? $APPLICATION->IncludeComponent(
			"bitrix:intranet.user.selector.new", ".default", array(
				"MULTIPLE" => "N",
				"NAME" => "FILTER_CREATEDBY",
				"VALUE" => intval($arParams["CREATED_BY_ID"]),
				"POPUP" => "Y",
				"INPUT_NAME" => "filter-field-created-by",
				"ON_SELECT" => "oLFFilter.onFilterCreatedBySelect",
				"SITE_ID" => SITE_ID,
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
				"SHOW_EXTRANET_USERS" => "FROM_MY_GROUPS",
				"SHOW_INACTIVE_USERS" => "Y"
			), null, array("HIDE_ICONS" => "Y")
		);

		if (array_key_exists("flt_comments", $_REQUEST) && $_REQUEST["flt_comments"] == "Y")
			$bChecked = true;
		else
			$bChecked = false;
		?><div class="filter-field" id="flt_comments_cont" style="display: <?=(intval($arParams["CREATED_BY_ID"]) > 0 ? "block" : "none")?>"><input type="checkbox" class="filter-checkbox" id="flt_comments" name="flt_comments" value="Y" <?=($bChecked ? "checked" : "")?>> <label for="flt_comments"><?=GetMessage("SONET_C30_T_FILTER_COMMENTS")?></label></div><?

		if ($arParams["USE_SONET_GROUPS"] != "N")
		{
			?><div class="filter-field">
				<div class="filter-field-title-block">
					<span id="filter-dest-group-tab" class="filter-field-title-tab<?=(empty($arResult["ToUser"]) ? "" : " webform-field-action-link")?>" onclick="oLFFilter.onFilterDestChangeTab('group');"><?=GetMessage("SONET_C30_T_FILTER_GROUP");?></span><?
					?><span class="filter-field-title-tab">&nbsp;/&nbsp;</span><?
					?><span id="filter-dest-user-tab" class="filter-field-title-tab<?=(empty($arResult["ToUser"]) ? " webform-field-action-link" : "")?>" onclick="oLFFilter.onFilterDestChangeTab('user');"><?=GetMessage("SONET_C30_T_FILTER_USER");?></span>
				</div>
				<span id="filter-dest-group-block" class="webform-field webform-field-textbox<?=(!$arResult["Group"]["ID"] ? " webform-field-textbox-empty" : "")?> webform-field-textbox-clearable" style="display: <?=(empty($arResult["ToUser"]) ? "inline-block" : "none")?>;">
					<span id="sonet-log-filter-group" class="webform-field-textbox-inner" style="width: 200px; padding: 0 20px 0 4px;">
						<input type="text" class="webform-field-textbox" id="filter-field-group" value="<?=$arResult["Group"]["NAME"]?>" style="height: 20px; width: 200px;" autocomplete="off" />
						<a class="sonet-log-field-textbox-clear" href=""></a>
					</span>
				</span>
				<span id="filter-dest-user-block" class="webform-field webform-field-textbox<?=(!$arResult["ToUser"]["ID"] ? " webform-field-textbox-empty" : "")?> webform-field-textbox-clearable" style="display: <?=(empty($arResult["ToUser"]) ? "none" : "inline-block")?>;">
					<span id="sonet-log-filter-user" class="webform-field-textbox-inner" style="width: 200px; padding: 0 20px 0 4px;">
						<input type="text" class="webform-field-textbox" id="filter-field-user" value="<?=$arResult["ToUser"]["NAME"]?>" style="height: 20px; width: 200px;" autocomplete="off" />
						<a class="sonet-log-field-textbox-clear" href=""></a>
					</span>
				</span>
			</div>
			<input type="hidden" name="flt_group_id" value="<?=$arResult["Group"]["ID"]?>" id="filter_field_group_hidden">
			<input type="hidden" name="flt_to_user_id" value="<?=$arResult["User"]["ID"]?>" id="filter_field_user_hidden">
			<?
			$APPLICATION->IncludeComponent(
				"bitrix:socialnetwork.group.selector",
				".default",
				array(
					"BIND_ELEMENT" => "sonet-log-filter-group",
					"JS_OBJECT_NAME" => "filterGroupsPopup",
					"ON_SELECT" => "oLFFilter.onFilterGroupSelect",
					"SEARCH_INPUT" => "filter-field-group",
					"SELECTED" => $arResult["Group"]["ID"] ? $arResult["Group"]["ID"] : 0
				),
				null,
				array("HIDE_ICONS" => "Y")
			);

			$APPLICATION->IncludeComponent(
				"bitrix:intranet.user.selector.new", ".default", array(
					"MULTIPLE" => "N",
					"NAME" => "FILTER_USER",
					"VALUE" => intval($arParams["USER"]),
					"POPUP" => "Y",
					"INPUT_NAME" => "filter-field-user",
					"ON_SELECT" => "oLFFilter.onFilterUserSelect",
					"SITE_ID" => SITE_ID,
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
					"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
					"SHOW_EXTRANET_USERS" => "FROM_MY_GROUPS",
					"SHOW_INACTIVE_USERS" => "Y"
				), null, array("HIDE_ICONS" => "Y")
			);
		}

		?><div class="filter-field filter-field-date-combobox">
			<label for="flt-date-datesel" class="filter-field-title"><?=GetMessage("SONET_C30_T_FILTER_DATE");?></label>
			<select name="flt_date_datesel" onchange="__logOnDateChange(this)" class="filter-dropdown" id="flt-date-datesel"><?
			foreach($arResult["DATE_FILTER"] as $k=>$v):
				?><option value="<?=$k?>"<?if($_REQUEST["flt_date_datesel"] == $k) echo ' selected="selected"'?>><?=$v?></option><?
			endforeach;
			?></select>
		<span class="filter-field filter-day-interval" style="display:none" id="flt_date_day_span">
			<input type="text" name="flt_date_days" value="<?=htmlspecialcharsbx($_REQUEST["flt_date_days"])?>" class="filter-date-days" size="2" /> <?echo GetMessage("SONET_C30_DATE_FILTER_DAYS")?>
		</span>
		<span class="filter-date-interval filter-date-interval-after filter-date-interval-before">
			<span class="filter-field filter-date-interval-from" style="display:none" id="flt_date_from_span"><input type="text" name="flt_date_from" value="<?=(array_key_exists("LOG_DATE_FROM", $arParams) ? $arParams["LOG_DATE_FROM"] : "")?>" class="filter-date-interval-from" /><?
				$APPLICATION->IncludeComponent(
					"bitrix:main.calendar",
					"",
					array(
						"SHOW_INPUT" => "N",
						"INPUT_NAME" => "flt_date_from",
						"INPUT_VALUE" => (array_key_exists("LOG_DATE_FROM", $arParams) ? $arParams["LOG_DATE_FROM"] : ""),
						"FORM_NAME" => "log_filter",
					),
					$component,
					array("HIDE_ICONS"	=> true)
				);?></span><span class="filter-date-interval-hellip" style="display:none" id="flt_date_hellip_span">&hellip;</span><span class="filter-field filter-date-interval-to" style="display:none" id="flt_date_to_span"><input type="text" name="flt_date_to" value="<?=(array_key_exists("LOG_DATE_TO", $arParams) ? $arParams["LOG_DATE_TO"] : "")?>" class="filter-date-interval-to" /><?
				$APPLICATION->IncludeComponent(
					"bitrix:main.calendar",
					"",
					array(
						"SHOW_INPUT" => "N",
						"INPUT_NAME" => "flt_date_to",
						"INPUT_VALUE" => (array_key_exists("LOG_DATE_TO", $arParams) ? $arParams["LOG_DATE_TO"] : ""),
						"FORM_NAME" => "log_filter",
					),
					$component,
					array("HIDE_ICONS"	=> true)
				);?></span>
		</span>
		</div>

		<script type="text/javascript">
		BX.ready(function(){
			__logOnDateChange(document.forms['log_filter'].flt_date_datesel);
		});
		</script>
		<?
		if ($arParams["SUBSCRIBE_ONLY"] == "Y"):
			if (array_key_exists("flt_show_hidden", $_REQUEST) && $_REQUEST["flt_show_hidden"] == "Y")
				$bChecked = true;
			else
				$bChecked = false;
			?><div class="filter-field"><input type="checkbox" class="filter-checkbox" id="flt_show_hidden" name="flt_show_hidden" value="Y" <?=($bChecked ? "checked" : "")?>> <label for="flt_show_hidden"><?=GetMessage("SONET_C30_T_SHOW_HIDDEN")?></label></div>
			<?
		endif;

		?><div class="sonet-log-filter-submit"><?
			?><span class="popup-window-button popup-window-button-create" onclick="document.forms['log_filter'].submit();"><span class="popup-window-button-left"></span><span class="popup-window-button-text"><?=GetMessage("SONET_C30_T_SUBMIT")?></span><span class="popup-window-button-right"></span></span><input type="hidden" name="log_filter_submit" value="Y"><?if ($isFiltered):?><a href="<?=$GLOBALS["APPLICATION"]->GetCurPageParam("preset_filter_id=".(array_key_exists("preset_filter_id", $_GET) && strlen($_GET["preset_filter_id"]) > 0 ? htmlspecialcharsbx($_GET["preset_filter_id"]) : "clearall"), array("flt_created_by_id","flt_group_id","flt_to_user_id","flt_date_datesel","flt_date_days","flt_date_from","flt_date_to","flt_date_to","flt_show_hidden","skip_subscribe","preset_filter_id","sessid","bxajaxid", "log_filter_submit", "FILTER_CREATEDBY","SONET_FILTER_MODE", "set_follow_type"), false)?>" class="popup-window-button popup-window-button-link popup-window-button-link-cancel"><span class="popup-window-button-link-text"><?=GetMessage("SONET_C30_T_RESET")?></span></a><?endif;
		?></div>
		<input type="hidden" name="skip_subscribe" value="<?=(isset($_REQUEST["skip_subscribe"]) && $_REQUEST["skip_subscribe"] == "Y" ? "Y" : "N")?>">
		<input type="hidden" name="preset_filter_id" value="<?=(array_key_exists("preset_filter_id", $_GET) ? htmlspecialcharsbx($_GET["preset_filter_id"]) : "")?>" />
		</form>
	</div><?
	die();
}
else
{
	$APPLICATION->AddHeadScript('/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/users.js');
	$APPLICATION->AddHeadScript('/bitrix/components/bitrix/socialnetwork.group.selector/templates/.default/script.js');
	$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/style.css");
	$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/socialnetwork.group.selector/templates/.default/style.css");

	if ($arParams["USE_TARGET"] != "N")
	{

		$this->SetViewTarget((strlen($arParams["PAGETITLE_TARGET"]) > 0 ? $arParams["PAGETITLE_TARGET"] : "pagetitle"), 50);
	}

	$isCompositeMode = defined("BITRIX24_INDEX_COMPOSITE");
	$isCompositeMode === false ?: ($dynamicArea = $this->createFrame()->begin(""));

	?><script type="text/javascript">

		function showLentaMenu(bindElement)
		{
			BX.addClass(bindElement, "lenta-sort-button-active");
			BX.PopupMenu.show("lenta-sort-popup", bindElement, [
				{
					text : "<?=(!empty($arResult["ALL_ITEM_TITLE"]) > 0 ? $arResult["ALL_ITEM_TITLE"] : GetMessageJS("SONET_C30_PRESET_FILTER_ALL"))?>",
					className : (window.bRefreshed !== undefined && window.bRefreshed ? "lenta-sort-item lenta-sort-item-selected" : "lenta-sort-item<?=(!$arResult["PresetFilterActive"] ? " lenta-sort-item-selected" : "")?>"),
					href : "<?=CUtil::JSEscape($GLOBALS["APPLICATION"]->GetCurPageParam("preset_filter_id=clearall", array_merge($arResult["PageParamsToClear"], array("preset_filter_id"))))?>"
				},
				<?
				$buttonName = false;
				if (is_array($arResult["PresetFilters"]))
				{
					foreach($arResult["PresetFilters"] as $preset_filter_id => $arPresetFilter)
					{
						if ($arResult["PresetFilterActive"] == $preset_filter_id)
							$buttonName = $arPresetFilter["NAME"];
						?>{
							text : "<?=$arPresetFilter["NAME"]?>",
							className : (window.bRefreshed !== undefined && window.bRefreshed ? "lenta-sort-item" : "lenta-sort-item<?=($arResult["PresetFilterActive"] == $preset_filter_id ? " lenta-sort-item-selected" : "")?>"),
							href : "<?=CUtil::JSEscape($GLOBALS["APPLICATION"]->GetCurPageParam("preset_filter_id=".$preset_filter_id, array_merge($arResult["PageParamsToClear"], array("preset_filter_id"))))?>"
						},<?
					}
				}
				?>
				{ delimiter : true },
				{
					text : "<?=GetMessageJS("SONET_C30_T_FILTER_TITLE")?>...",
					className : (window.bRefreshed !== undefined && window.bRefreshed ? "lenta-sort-item" : "lenta-sort-item<?=($isFiltered ? " lenta-sort-item-selected" : "")?>"),
					onclick: function() {
						this.popupWindow.close();
						oLFFilter.ShowFilterPopup(BX("lenta-sort-button"));
					}
				}
				<?
				if ($arParams["SHOW_FOLLOW"] != "N")
				{
					?>
					,{ delimiter : true },
					{
						text : "<?=GetMessageJS("SONET_C30_SMART_FOLLOW")?>",
						className : "lenta-sort-item<?=($arResult["FOLLOW_TYPE"] == "N" ? " lenta-sort-item-selected" : "")?>",
						href : "<?=CUtil::JSEscape($GLOBALS["APPLICATION"]->GetCurPageParam("set_follow_type=".($arResult["FOLLOW_TYPE"] == "Y" ? "N" : "Y"), array("set_follow_type")))?>"
					}
					<?
				}

				if (
					$arParams["SHOW_EXPERT_MODE"] != "N"
					&& class_exists('\Bitrix\Socialnetwork\LogViewTable') // socialnetwork 16.5.0
				)
				{
					?>
					,{ delimiter : true },
					{
						text : "<?=GetMessageJS("SONET_C30_SMART_EXPERT_MODE")?>",
						className : "lenta-sort-item<?=($arResult["EXPERT_MODE"] == "Y" ? " lenta-sort-item-selected" : "")?>",
						href : "<?=CUtil::JSEscape($GLOBALS["APPLICATION"]->GetCurPageParam("set_expert_mode=".($arResult["EXPERT_MODE"] == "Y" ? "N" : "Y"), array("set_expert_mode")))?>"
					}
					<?
				}
				?>
			],
			{
				offsetTop:2,
				offsetLeft : 43,
				angle : true,
				events : {
					onPopupClose : function() {
						BX.removeClass(this.bindElement, "lenta-sort-button-active");
					}
				}
			});
			return false;
		}

		<?
		if (
			isset($arResult["SHOW_EXPERT_MODE_POPUP"])
			&& $arResult["SHOW_EXPERT_MODE_POPUP"] == "Y"
		)
		{
			?>
			BX.ready(function() {
				setTimeout(function() {
					oLFFilter.__SLFShowExpertModePopup(null);
				}, 1000);
			});
			<?
		}
		?>
		BX.message({
			sonetLFAjaxPath: '<?=CUtil::JSEscape($arResult["AjaxURL"])?>',
			ajaxControllerURL: '<?=CUtil::JSEscape($arResult["ajaxControllerURL"])?>',
			sonetLFAllMessages: '<?=GetMessageJS("SONET_C30_PRESET_FILTER_ALL")?>',
			sonetLFDialogClose: '<?=GetMessageJS("SONET_C30_F_DIALOG_CLOSE_BUTTON")?>',
			sonetLFExpertModePopupTitle: '<?=GetMessageJS("SONET_C30_F_EXPERT_MODE_POPUP_TITLE")?>',
			sonetLFExpertModePopupText1: '<?=GetMessageJS("SONET_C30_F_EXPERT_MODE_POPUP_TEXT1")?>',
			sonetLFExpertModePopupText2: '<?=GetMessageJS("SONET_C30_F_EXPERT_MODE_POPUP_TEXT2")?>',
			sonetLFExpertModeImagePath: '<?=CUtil::JSEscape($this->GetFolder())?>/images/expert_mode/<?=GetMessageJS("SONET_C30_F_EXPERT_MODE_IMAGENAME")?>.png'
		});
	</script><?

	$isCompositeMode === false ?: $dynamicArea->end();
	$logCounter = intval($arResult["LOG_COUNTER"]);
	?><a href="" id="lenta-sort-button" class="lenta-sort-button" onclick="return showLentaMenu(this);" onmousedown="BX.addClass(this, 'lenta-sort-button-press')" onmouseup="BX.removeClass(this,'lenta-sort-button-press')"><?
		?><span class="lenta-sort-button-left"></span><?
		?><span class="lenta-sort-button-text"><?
			?><span class="lenta-sort-button-text-internal" id="lenta-button"><?
				$frame = $this->createFrame("lenta-button", false)->begin(GetMessage("SONET_C30_PRESET_FILTER_ALL"));
				echo ($buttonName !== false ? $buttonName : GetMessage("SONET_C30_PRESET_FILTER_ALL") );
				echo ($isFiltered ? " (".GetMessageJS("SONET_C30_T_FILTER_TITLE").")" : "");
				if ($logCounter > 0 && Bitrix\Main\Page\Frame::isAjaxRequest()):?>
					<script type="text/javascript">BX("sonet_log_counter_preset").innerHTML="<?=$logCounter?>"</script><?
				endif;
				$frame->end();
			?></span><?
			if ($buttonName === false || $isCompositeMode):
				?><span id="sonet_log_counter_preset" class="pagetitle-but-counter"><?=(($logCounter > 0 && $arParams["ENTITY_TYPE"] != SONET_ENTITY_GROUP && !$isCompositeMode) ? $logCounter : "")?></span><?
			endif;
		?></span><?
		?><span class="lenta-sort-button-right"></span><?
	?></a><?

	if ($arParams["USE_TARGET"] != "N")
	{
		$this->EndViewTarget();
	}

	if (isset($_SESSION["SL_SHOW_FOLLOW_HINT"]))
	{
		unset($_SESSION["SL_SHOW_FOLLOW_HINT"]);
		?><div class="feed-smart-follow-hint"><?=GetMessage("SONET_C30_SMART_FOLLOW_HINT");?></div><?
	}
	elseif (isset($_SESSION["SL_EXPERT_MODE_HINT"]))
	{
		unset($_SESSION["SL_EXPERT_MODE_HINT"]);
		?><div class="feed-smart-follow-hint"><?=GetMessage("SONET_C30_EXPERT_MODE_HINT");?></div><?
	}
}
?>