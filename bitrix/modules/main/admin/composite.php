<?
use Bitrix\Main\Config\Option;
use Bitrix\Main\Page\Frame;
use Bitrix\Main\Page\FrameComponent;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/prolog.php");
define("HELP_FILE", "settings/composite.php");
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/cache_html.php");
/** @var CUser $USER */
/** @var CMain $APPLICATION */

IncludeModuleLangFile(__FILE__);

$isAdmin = $USER->CanDoOperation('cache_control');
if(!$USER->CanDoOperation('cache_control') && !$USER->CanDoOperation('view_other_settings'))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$APPLICATION->SetAdditionalCSS("/bitrix/panel/main/composite.css");
$APPLICATION->AddHeadString("<style type=\"text/css\">".\Bitrix\Main\Page\Frame::getInjectedCSS()."</style>");

$tabs = array(
	/*array(
		"DIV" => "default",
		"TAB" => GetMessage("MAIN_COMPOSITE_TITLE"),
		"ICON" => "main_settings",
		"TITLE" => GetMessage("MAIN_COMPOSITE_TAB"),
	),*/
	array(
		"DIV" => "settings",
		"TAB" => GetMessage("MAIN_COMPOSITE_SETTINGS_TAB"),
		"ICON" => "main_settings",
		"TITLE" => GetMessage("MAIN_COMPOSITE_TAB_TITLE"),
	),
	array(
		"DIV" => "groups",
		"TAB" => GetMessage("MAIN_COMPOSITE_TAB_GROUPS"),
		"ICON" => "main_settings",
		"TITLE" => GetMessage("MAIN_COMPOSITE_TAB_GROUPS_TITLE_NEW"),
	),
	array(
		"DIV" => "button",
		"TAB" => GetMessage("MAIN_COMPOSITE_BANNER_SEP")." \"".GetMessage("COMPOSITE_BANNER_TEXT")."\"",
		"ICON" => "main_banner",
		"TITLE" => GetMessage("MAIN_COMPOSITE_BANNER_SEP")." &quot;".GetMessage("COMPOSITE_BANNER_TEXT")."&quot;",
	),
);

/*if (LANGUAGE_ID === "ru")
{
	$tabs[] = array(
		"DIV" => "patent",
		"TAB" => GetMessage("MAIN_COMPOSITE_PATENT_TAB"),
		"TITLE" => GetMessage("MAIN_COMPOSITE_PATENT_TAB"),
	);
}*/

$tabControl = new CAdminTabControl("tabControl", $tabs, true, true);

if (
	$_SERVER["REQUEST_METHOD"] == "POST"
	&& check_bitrix_sessid()
	&& $isAdmin
	&& (
		(isset($_REQUEST["composite_save_opt"]) && strlen($_REQUEST["composite_save_opt"]) > 0)
		|| (isset($_REQUEST["composite_siteb"]) && strlen($_REQUEST["composite_siteb"]) > 0)
	)
)
{
	$compositeOptions = CHTMLPagesCache::getOptions();
	$compositeOptions["INCLUDE_MASK"] = $_REQUEST["composite_include_mask"];
	$compositeOptions["EXCLUDE_MASK"] = $_REQUEST["composite_exclude_mask"];
	$compositeOptions["EXCLUDE_PARAMS"] = $_REQUEST["composite_exclude_params"];
	$compositeOptions["NO_PARAMETERS"] = $_REQUEST["composite_no_parameters"];
	$compositeOptions["IGNORED_PARAMETERS"] = $_REQUEST["composite_ignored_parameters"];
	$compositeOptions["FILE_QUOTA"] = $_REQUEST["composite_quota"];
	$compositeOptions["BANNER_BGCOLOR"] = $_REQUEST["composite_banner_bgcolor"];
	$compositeOptions["BANNER_STYLE"] = $_REQUEST["composite_banner_style"];
	$compositeOptions["ALLOW_HTTPS"] = $_REQUEST["composite_allow_https"];

	if (isset($compositeOptions["STORAGE"])
		&& isset($_REQUEST["composite_storage"])
		&& $compositeOptions["STORAGE"] !== $_REQUEST["composite_storage"]
	)
	{
		CHTMLPagesCache::writeStatistic(false, false, false, false, false);
	}

	$storage = $_REQUEST["composite_storage"];
	if ( ($storage === "memcached" || $storage === "memcached_cluster") && extension_loaded("memcache"))
	{
		$compositeOptions["MEMCACHED_HOST"] = $_REQUEST["composite_memcached_host"];
		$compositeOptions["MEMCACHED_PORT"] = $_REQUEST["composite_memcached_port"];

		if (defined("BX_CLUSTER_GROUP"))
		{
			$compositeOptions["MEMCACHED_CLUSTER_GROUP"] = BX_CLUSTER_GROUP;
		}
	}
	else
	{
		$storage = "files";
	}

	$compositeOptions["STORAGE"] = $storage;

	if (isset($_REQUEST["group"]) && is_array($_REQUEST["group"]))
	{
		$compositeOptions["GROUPS"] = array();
		$b = "";
		$o = "";
		$rsGroups = CGroup::GetList($b, $o, array());
		while ($arGroup = $rsGroups->Fetch())
		{
			if ($arGroup["ID"] > 2)
			{
				if (in_array($arGroup["ID"], $_REQUEST["group"]))
				{
					$compositeOptions["GROUPS"][] = $arGroup["ID"];
				}
			}
		}
	}

	if (isset($_REQUEST["composite_domains"]) && strlen($_REQUEST["composite_domains"]) > 0)
	{
		$compositeOptions["DOMAINS"] = array();
		foreach(explode("\n", $_REQUEST["composite_domains"]) as $domain)
		{
			$domain = trim($domain, " \t\n\r");
			if ($domain != "")
			{
				$compositeOptions["DOMAINS"][$domain] = $domain;
			}
		}
	}

	$compositeOptions["FRAME_MODE"] = isset($_REQUEST["composite_frame_mode"]) ? $_REQUEST["composite_frame_mode"] : "";
	$compositeOptions["FRAME_TYPE"] = isset($_REQUEST["composite_frame_type"]) ? $_REQUEST["composite_frame_type"] : "";
	$compositeOptions["AUTO_COMPOSITE"] =
		isset($_REQUEST["auto_composite"]) && $_REQUEST["auto_composite"] === "Y" ? "Y" : "N";

	if (isset($_REQUEST["composite_cache_mode"]))
	{
		if ($_REQUEST["composite_cache_mode"] === "standard_ttl")
		{
			$compositeOptions["AUTO_UPDATE"] = "Y";
			$ttl = isset($_REQUEST["composite_standard_ttl"]) ? intval($_REQUEST["composite_standard_ttl"]) : 120;
			$compositeOptions["AUTO_UPDATE_TTL"] = $ttl;
		}
		elseif ($_REQUEST["composite_cache_mode"] === "no_update")
		{
			$compositeOptions["AUTO_UPDATE"] = "N";
			$ttl = isset($_REQUEST["composite_no_update_ttl"]) ? intval($_REQUEST["composite_no_update_ttl"]) : 600;
			$compositeOptions["AUTO_UPDATE_TTL"] = $ttl;
		}
		else
		{
			$compositeOptions["AUTO_UPDATE"] = "Y";
			$compositeOptions["AUTO_UPDATE_TTL"] = "0";
		}
	}

	if (isset($_REQUEST["composite_siteb"]) && isset($_REQUEST["composite_on"]))
	{
		if ($_REQUEST["composite_on"] == "N")
		{
			CHTMLPagesCache::setEnabled(false);
		}
		elseif ($_REQUEST["composite_on"] == "Y")
		{
			CHTMLPagesCache::setEnabled(true);
		}
	}

	if (isset($_REQUEST["composite_show_banner"]) && in_array($_REQUEST["composite_show_banner"], array("Y", "N")))
	{
		Option::set("main", "~show_composite_banner", $_REQUEST["composite_show_banner"]);
	}

	CHTMLPagesCache::setOptions($compositeOptions);
	bx_accelerator_reset();
	LocalRedirect("/bitrix/admin/composite.php?lang=".LANGUAGE_ID."&".$tabControl->ActiveTabParam());
}

if (
	$_SERVER["REQUEST_METHOD"] == "POST"
	&& isset($_REQUEST["check_connection"])
	&& $_REQUEST["check_connection"] === "Y"
	&& check_bitrix_sessid()
	&& $isAdmin
)
{
	$host = isset($_REQUEST["host"]) ? $_REQUEST["host"] : "";
	$port = isset($_REQUEST["port"]) ? $_REQUEST["port"] : "";

	$status = "";
	$text = "";
	if (!extension_loaded("memcache"))
	{
		$text = GetMessage("MAIN_COMPOSITE_CHECK_CONNECTION_ERR1");
		$status = "error";
	}
	elseif (strlen($host) > 0 && strlen($port) > 0 && ($memcached = new \Memcache()) && @$memcached->connect($host, $port))
	{
		$text = GetMessage("MAIN_COMPOSITE_CHECK_CONNECTION_OK");
		$status = "success";
	}
	else
	{
		$text = GetMessage("MAIN_COMPOSITE_CHECK_CONNECTION_ERR2");
		$status = "error";
	}

	header("Content-Type: application/x-javascript; charset=".LANG_CHARSET);
	die("{ status : '".$status."', text : '".$text."' }");
}

$APPLICATION->SetTitle(GetMessage("MAIN_COMPOSITE_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$compositeOptions = CHTMLPagesCache::getOptions();
$configFile = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/html_pages/.config.php";
$firstActivation = !CHTMLPagesCache::isOn() && !file_exists($configFile);
if ($firstActivation)
{
	$compositeOptions["AUTO_COMPOSITE"] = "Y";
	$compositeOptions["FRAME_MODE"] = "Y";
	$compositeOptions["FRAME_TYPE"] = "DYNAMIC_WITH_STUB";
	$compositeOptions["AUTO_UPDATE"] = "Y";
	$compositeOptions["AUTO_UPDATE_TTL"] = "120";
}

?>

<form method="POST" name="composite_form" action="<?echo $APPLICATION->GetCurPage()?>">

<?
echo BeginNote();

if (CHTMLPagesCache::isOn()):?>
	<div style="color:green;"><b><?echo GetMessage("MAIN_COMPOSITE_ON")?>.</b></div><br>
	<input type="hidden" name="composite_on" value="N">
	<input type="submit" name="composite_siteb" value="<?echo GetMessage("MAIN_COMPOSITE_BUTTON_OFF")?>"<?if(!$isAdmin || (defined("FIRST_EDITION") && FIRST_EDITION=="Y")) echo " disabled"?>>
<?else:?>
	<div style="color:red;"><b><?echo GetMessage("MAIN_COMPOSITE_OFF")?>.</b></div><br>
	<input type="hidden" name="composite_on" value="Y">
	<input type="submit" name="composite_siteb" value="<?echo GetMessage("MAIN_COMPOSITE_BUTTON_ON")?>"<?if(!$isAdmin) echo " disabled"?> class="adm-btn-save">
<?endif?>

<? if (defined("FIRST_EDITION") && FIRST_EDITION=="Y"): ?>
	<br><br><?echo GetMessage("MAIN_COMPOSITE_FIRST_SITE_RESTRICTION")?>
<?endif;?>

<?
$warning = GetMessage("MAIN_COMPOSITE_WARNING_EDUCATION");
if (!empty($warning) && $warning != "-")
{
	echo "<br><br>".$warning;
}

echo EndNote();

$tabControl->Begin();
$tabControl->BeginNextTab();

$autoComposite = isset($compositeOptions["AUTO_COMPOSITE"]) && $compositeOptions["AUTO_COMPOSITE"] === "Y";
?>
<tr class="adm-detail-valign-top">
	<td width="40%"></td>
	<td width="60%">
		<div class="adm-list">
			<div class="adm-list-item">
				<div class="adm-list-control">
					<input
						type="checkbox"
						id="auto_composite"
						name="auto_composite"
						value="Y"
						onclick="onAutoCompositeChanged(this.checked)"
						<?if ($autoComposite):?>checked<?endif?>
					>
				</div>
				<div class="adm-list-label">
					<label for="auto_composite"><?=GetMessage("MAIN_COMPOSITE_AUTO_COMPOSITE")?></label>
				</div>
			</div>
		</div>
		<script>
			function onAutoCompositeChanged(enable)
			{
				var modePro = BX("composite_frame_mode_pro");
				var modeContra = BX("composite_frame_mode_contra");
				var dynamicWithStub = BX("composite_frame_type_dynamic_with_stub");
				var staticType = BX("composite_frame_type_static");

				var frameModeRow = BX("composite_frame_mode_row");
				var frameTypeRow = BX("composite_frame_type_row");
				var noUpdateModeOption = BX("composite_cache_mode_no_update_option");

				var standardTtlMode = BX("composite_cache_mode_standard_ttl");
				var noUpdateMode = BX("composite_cache_mode_no_update_ttl");

				if (enable)
				{
					modePro.checked = true;
					dynamicWithStub.checked = true;
					standardTtlMode.checked = true;

					modeContra.disabled = true;
					staticType.disabled = true;
					noUpdateMode.disabled = true;

					standardTtlMode.checked = true;

					BX.addClass(frameModeRow, "adm-composite-label-disabled");
					BX.addClass(frameTypeRow, "adm-composite-label-disabled");
					BX.addClass(noUpdateModeOption, "adm-composite-label-disabled");

					onFrameModeChanged(true);
					onCacheModeChanged('standard_ttl');
				}
				else
				{
					modeContra.disabled = false;
					staticType.disabled = false;
					noUpdateMode.disabled = false;

					BX.removeClass(frameModeRow, "adm-composite-label-disabled");
					BX.removeClass(frameTypeRow, "adm-composite-label-disabled");
					BX.removeClass(noUpdateModeOption, "adm-composite-label-disabled");
				}
			}
		</script>
	</td>
</tr>

<tr class="heading">
	<td colspan="2"><?=GetMessage("MAIN_COMPOSITE_VOTING_TITLE");?></td>
</tr>

<?
$frameMode = isset($compositeOptions["FRAME_MODE"]) && $compositeOptions["FRAME_MODE"] === "Y";

?>
<tr class="adm-detail-valign-top<?if ($autoComposite):?> adm-composite-label-disabled<?endif?>"
	id="composite_frame_mode_row"
>
	<td width="40%"><?=GetMessage("MAIN_COMPOSITE_FRAME_MODE")?>:</td>
	<td width="60%">
		<div class="adm-list adm-list-radio">
			<div class="adm-list-item">
				<div class="adm-list-control">
					<input
						type="radio"
						id="composite_frame_mode_pro"
						name="composite_frame_mode"
						value="Y"
						onclick="onFrameModeChanged(true)"
						<?if ($frameMode):?>checked<?endif?>
					>

				</div>
				<div class="adm-list-label">
					<label for="composite_frame_mode_pro"><?=GetMessage("MAIN_COMPOSITE_FRAME_MODE_PRO")?></label>
				</div>
			</div>
			<div class="adm-list-item">
				<div class="adm-list-control">
					<input
						type="radio"
						id="composite_frame_mode_contra"
						name="composite_frame_mode"
						value="N"
						style="opacity: 1"
						onclick="onFrameModeChanged(false)"
						<?if (!$frameMode):?>checked<?endif?>
						<?if ($autoComposite):?>disabled<?endif?>
					>
				</div>
				<div class="adm-list-label">
					<label for="composite_frame_mode_contra"><?=GetMessage("MAIN_COMPOSITE_FRAME_MODE_CONTRA")?></label>
				</div>
			</div>
			<script>
				function onFrameModeChanged(pro)
				{
					var contentType = BX("composite_frame_type_row");
					contentType.style.display = pro ? "" : "none";
				}
			</script>
		</div>
	</td>
</tr>
<?
$frameType = "STATIC";
if (isset($compositeOptions["FRAME_TYPE"]) && in_array($compositeOptions["FRAME_TYPE"], FrameComponent::getFrameTypes()))
{
	$frameType = $compositeOptions["FRAME_TYPE"];
}
?>
<tr class="adm-detail-valign-top<? if ($autoComposite):?> adm-composite-label-disabled<?endif?>"
	id="composite_frame_type_row"
	<? if (!$frameMode):?>style="display: none"<?endif?>
>
	<td width="40%"><?=GetMessage("MAIN_COMPOSITE_FRAME_TYPE")?>:</td>
	<td width="60%">
		<div class="adm-list adm-list-radio">
			<div class="adm-list-item">
				<div class="adm-list-control">
					<input
						type="radio"
						id="composite_frame_type_dynamic_with_stub"
						name="composite_frame_type"
						value="DYNAMIC_WITH_STUB"
						<? if ($frameType === "DYNAMIC_WITH_STUB"):?>checked<?endif?>
					>
				</div>
				<div class="adm-list-label">
					<label for="composite_frame_type_dynamic_with_stub"><?
						echo GetMessage("MAIN_COMPOSITE_FRAME_TYPE_DYNAMIC_WITH_STUB")
					?></label>
				</div>
			</div>
			<div class="adm-list-item">
				<div class="adm-list-control">
					<input
						type="radio"
						id="composite_frame_type_static"
						name="composite_frame_type"
						value="STATIC"
						style="opacity: 1"
						<? if ($frameType === "STATIC"):?>checked<?endif?>
						<? if ($autoComposite):?>disabled<?endif?>
					>
				</div>
				<div class="adm-list-label">
					<label for="composite_frame_type_static"><?=GetMessage("MAIN_COMPOSITE_FRAME_TYPE_STATIC")?></label>
				</div>
			</div>
		</div>

	</td>
</tr>
<tr>
	<td width="40%">

	</td>
	<td width="60%">
		<i><?=GetMessage("MAIN_COMPOSITE_FRAME_DESC")?></i>
	</td>
</tr>


<tr class="heading">
	<td colspan="2"><?=GetMessage("MAIN_COMPOSITE_CACHE_REWRITING")?></td>
</tr>

<?
$autoUpdate = isset($compositeOptions["AUTO_UPDATE"]) && $compositeOptions["AUTO_UPDATE"] === "N" ? false : true;
$defaultAutoUpdateTTL = $autoUpdate ? 0 : 600;
$autoUpdateTTL = isset($compositeOptions["AUTO_UPDATE_TTL"]) ? intval($compositeOptions["AUTO_UPDATE_TTL"]) : $defaultAutoUpdateTTL;
?>
<tr class="adm-detail-valign-top">
	<td width="40%"><?=GetMessage("MAIN_COMPOSITE_CACHE_REWRITING")?>:</td>
	<td width="60%">
		<div class="adm-list adm-list-radio">
			<?
			$isTTLMode = $autoUpdate && $autoUpdateTTL > 0;
			?>
			<div class="adm-list-item">
				<div class="adm-list-control">
					<input
						type="radio"
						id="composite_cache_mode_standard_ttl"
						name="composite_cache_mode"
						value="standard_ttl"
						onclick="onCacheModeChanged('standard_ttl')"
						<?if ($isTTLMode):?>checked<?endif?>
					>

				</div>
				<div class="adm-list-label">
					<label for="composite_cache_mode_standard_ttl">
						<?=GetMessage("MAIN_COMPOSITE_CACHE_MODE_STANDARD_TTL")?>
						<div class="adm-composite-cache-mode-hint">
							<?=GetMessage("MAIN_COMPOSITE_CACHE_MODE_STANDARD_TTL_DESC")?>
						</div>
					</label>

					<div class="adm-composite-cache-ttl<?if (!$isTTLMode):?> adm-composite-label-disabled<?endif?>">
						<?=GetMessage("MAIN_COMPOSITE_CACHE_MODE_TTL")?>:
						<input
							id="composite_standard_ttl"
							name="composite_standard_ttl"
							type="text"
							size="8"
							value="<?=($isTTLMode ? $autoUpdateTTL : 120)?>"
							<?if (!$isTTLMode):?>disabled<?endif?>
						>
						<?=GetMessage("MAIN_COMPOSITE_CACHE_MODE_TTL_UNIT_SEC")?>
					</div>
				</div>
			</div>
			<div class="adm-list-item">
				<div class="adm-list-control">
					<input
						type="radio"
						id="composite_cache_mode_standard"
						name="composite_cache_mode"
						value="standard"
						onclick="onCacheModeChanged('standard')"
						<?if ($autoUpdate && $autoUpdateTTL <= 0):?>checked<?endif?>
					>
				</div>
				<div class="adm-list-label">
					<label for="composite_cache_mode_standard">
						<?=GetMessage("MAIN_COMPOSITE_CACHE_MODE_STANDARD")?>
						<div class="adm-composite-cache-mode-hint">
							<?=GetMessage("MAIN_COMPOSITE_CACHE_MODE_STANDARD_DESC")?>
						</div>
					</label>
				</div>
			</div>
			<?
			$isNoUpdateMode = !$autoUpdate;
			?>
			<div class="adm-list-item">
				<div class="adm-list-control">
					<input
						type="radio"
						id="composite_cache_mode_no_update_ttl"
						name="composite_cache_mode"
						value="no_update"
						onclick="onCacheModeChanged('no_update')"
						<?if ($isNoUpdateMode):?>checked<?endif?>
						<?if ($autoComposite):?>disabled<?endif?>
					>

				</div>
				<div class="adm-list-label<?if ($autoComposite):?> adm-composite-label-disabled<?endif?>"
					 id="composite_cache_mode_no_update_option">
					<label for="composite_cache_mode_no_update_ttl">
						<?=GetMessage("MAIN_COMPOSITE_CACHE_MODE_NO_UPDATE")?>
						<div class="adm-composite-cache-mode-hint">
							<?=GetMessage("MAIN_COMPOSITE_CACHE_MODE_NO_UPDATE_DESC")?>
						</div>
					</label>
					<div class="adm-composite-cache-ttl<?if (!$isNoUpdateMode):?> adm-composite-label-disabled<?endif?>">
						<?=GetMessage("MAIN_COMPOSITE_CACHE_MODE_NO_UPDATE_TTL")?>:
						<input
							id="composite_no_update_ttl"
							name="composite_no_update_ttl"
							type="text"
							size="8"
							value="<?=($isNoUpdateMode ? $autoUpdateTTL : 600)?>"
							<?if (!$isNoUpdateMode):?>disabled<?endif?>
						>
						<?=GetMessage("MAIN_COMPOSITE_CACHE_MODE_TTL_UNIT_SEC")?>
					</div>
				</div>
			</div>
			<script>
				function onCacheModeChanged(mode)
				{
					var noUpdateTTL = BX("composite_no_update_ttl");
					var standardTTL = BX("composite_standard_ttl");

					if (mode === "standard_ttl")
					{
						standardTTL.disabled = false;
						noUpdateTTL.disabled = true;

						BX.removeClass(standardTTL.parentNode, "adm-composite-label-disabled");
						BX.addClass(noUpdateTTL.parentNode, "adm-composite-label-disabled");
					}
					else if (mode === "no_update")
					{
						standardTTL.disabled = true;
						noUpdateTTL.disabled = false;

						BX.addClass(standardTTL.parentNode, "adm-composite-label-disabled");
						BX.removeClass(noUpdateTTL.parentNode, "adm-composite-label-disabled");
					}
					else
					{
						standardTTL.disabled = true;
						noUpdateTTL.disabled = true;

						BX.addClass(standardTTL.parentNode, "adm-composite-label-disabled");
						BX.addClass(noUpdateTTL.parentNode, "adm-composite-label-disabled");

					}
				}
			</script>
		</div>
	</td>
</tr>



<tr class="heading">
	<td colspan="2"><?=GetMessage("MAIN_COMPOSITE_OPT")?></td>
</tr>
<?
if (!is_array($compositeOptions["DOMAINS"]) || count($compositeOptions["DOMAINS"]) < 1)
{
	$compositeOptions["DOMAINS"] = array(CHTMLPagesCache::getHttpHost());
}
?>
<tr class="adm-detail-valign-top">
	<td width="40%" class="adm-required-field"><?=GetMessage("MAIN_COMPOSITE_DOMAINS")?>:</td>
	<td width="60%">
		<textarea name="composite_domains" rows="5" style="width:100%"><?echo htmlspecialcharsEx(implode("\n", $compositeOptions["DOMAINS"]))?></textarea><br>
	</td>
</tr>
<tr class="adm-detail-valign-top">
	<td width="40%"><?=GetMessage("MAIN_COMPOSITE_INC_MASK")?>:</td>
	<td width="60%">
		<textarea name="composite_include_mask" rows="5" style="width:100%"><?echo htmlspecialcharsEx($compositeOptions["INCLUDE_MASK"])?></textarea>
	</td>
</tr>

<tr class="adm-detail-valign-top">
	<td><?=GetMessage("MAIN_COMPOSITE_IGNORED_PARAMETERS")?>:</td>
	<td>
		<textarea name="composite_ignored_parameters" rows="5" style="width:100%"><?echo htmlspecialcharsEx($compositeOptions["IGNORED_PARAMETERS"])?></textarea>
	</td>
</tr>

<tr>
	<td><label for="composite_no_parameters"><?=GetMessage("MAIN_COMPOSITE_NO_PARAMETERS")?>:</label></td>
	<td>
		<input type="hidden" name="composite_no_parameters" value="N">
		<input type="checkbox" name="composite_no_parameters" id="composite_no_parameters" value="Y" <?if ($compositeOptions["NO_PARAMETERS"] === "Y") echo 'checked="checked"'?>>
	</td>
</tr>

<tr class="adm-detail-valign-top">
	<td><?=GetMessage("MAIN_COMPOSITE_EXCLUDE_BY_PARAMS")?>:</td>
	<td>
		<textarea name="composite_exclude_params" rows="5" style="width:100%"><?
			echo htmlspecialcharsEx($compositeOptions["EXCLUDE_PARAMS"])
		?></textarea>
	</td>
</tr>

<tr class="heading">
	<td colspan="2"><?=GetMessage("MAIN_COMPOSITE_STORAGE_TITLE")?></td>
</tr>
<?
$storages = array(
	"files" => array(
		"name" => GetMessage("MAIN_COMPOSITE_STORAGE_FILES")
	),

	"memcached" => array(
		"name" => "memcached",
		"extension" => "memcache"
	),

	"memcached_cluster" => array(
		"name" => "memcached cluster",
		"extension" => "memcache",
		"module" => "cluster"
	),
);

$currentStorage = "files";
if (isset($compositeOptions["STORAGE"]) && array_key_exists($compositeOptions["STORAGE"], $storages))
{
	$currentStorage = $compositeOptions["STORAGE"];
}

//Defaults for memcached
if (!isset($compositeOptions["MEMCACHED_HOST"]))
{
	$compositeOptions["MEMCACHED_HOST"] = "localhost";
}

if (!isset($compositeOptions["MEMCACHED_PORT"]))
{
	$compositeOptions["MEMCACHED_PORT"] = "11211";
}
?>
<tr>
	<td><?echo GetMessage("MAIN_COMPOSITE_STORAGE");?>:</td>
	<td>
		<script type="text/javascript">
			function onStorageSelect(select)
			{
				var hostRow = BX("composite_memcached_host_row", true);
				var portRow = BX("composite_memcached_port_row", true);
				var hintRow = BX("composite_memcached_hint_row", true);
				var clusterRow = BX("composite_cluster_hint_row", true);
				var quotaRow = BX("composite_quota_row", true);
				var quotaStatRow = BX("composite_quota_stat_row", true);
				var quotaSizeRow = BX("composite_quota_size_row", true);
				if (select.value === "memcached")
				{
					hostRow.style.cssText = "";
					portRow.style.cssText = "";
					hintRow.style.cssText = "";
				}
				else
				{
					hostRow.style.display = "none";
					portRow.style.display = "none";
					hintRow.style.display = "none";
				}

				if (select.value === "memcached_cluster")
				{
					clusterRow.style.cssText = "";
				}
				else
				{
					clusterRow.style.display = "none";
				}

				if (select.value !== "files")
				{
					quotaRow.style.display = "none";
					quotaStatRow && (quotaStatRow.style.display = "none");
					quotaSizeRow && (quotaSizeRow.style.display = "none");
				}
				else
				{
					quotaRow.style.cssText = "";
					quotaStatRow && (quotaStatRow.style.cssText = "");
					quotaSizeRow && (quotaSizeRow.style.cssText = "");
				}
			}
		</script>
		<select name="composite_storage" id="composite_storage" style="width:300px;" onchange="onStorageSelect(this)">
			<?
			foreach ($storages as $storageId => $storage):
				$disabled = "";
				$nameDesc = "";
				$selected = $currentStorage == $storageId ? " selected" : "";
				if (isset($storage["module"]) && !\Bitrix\Main\ModuleManager::isModuleInstalled($storage["module"]))
				{
					$disabled = " disabled";
					$nameDesc = " (".GetMessage("MAIN_COMPOSITE_MODULE_ERROR", array("#MODULE#" => $storage["module"])).")";
				}
				elseif (isset($storage["extension"]) && strlen($storage["extension"]) > 0 && !extension_loaded($storage["extension"]))
				{
					$disabled = " disabled";
					$nameDesc = " (".GetMessage("MAIN_COMPOSITE_EXT_ERROR", array("#EXTENSION#" => $storage["extension"])).")";
				}

				?>
				<option value="<?=htmlspecialcharsbx($storageId)?>"<?=$selected?><?=$disabled?>><?=htmlspecialcharsbx($storage["name"])?><?=$nameDesc?></option>
			<?endforeach?>
		</select>
	</td>
</tr>
<tr id="composite_memcached_host_row" <?if ($compositeOptions["STORAGE"] !== "memcached") echo 'style="display:none"'?>>
	<td class="adm-required-field"><?=GetMessage("MAIN_COMPOSITE_MEMCACHED_HOST")?>:</td>
	<td>
		<input type="text" size="45" style="width:300px" name="composite_memcached_host" value="<?echo htmlspecialcharsbx($compositeOptions["MEMCACHED_HOST"])?>">
	</td>
</tr>

<tr id="composite_memcached_port_row" <?if ($compositeOptions["STORAGE"] !== "memcached") echo 'style="display:none"'?>>
	<td class="adm-required-field"><?=GetMessage("MAIN_COMPOSITE_MEMCACHED_PORT")?>:</td>
	<td>
		<input type="text" size="45" style="width:50px" name="composite_memcached_port" value="<?echo htmlspecialcharsbx($compositeOptions["MEMCACHED_PORT"])?>">

	</td>
</tr>
<tr id="composite_memcached_hint_row" <?if ($compositeOptions["STORAGE"] !== "memcached") echo 'style="display:none"'?>>
	<td class="adm-required-field"></td>
	<td>
		<script type="text/javascript">
			function checkConnection()
			{
				BX.ajax({
					method: "POST",
					dataType: 'json',
					url: window.location.href,
					data: {
						sessid : BX.bitrix_sessid(),
						check_connection : "Y",
						host : document.forms["composite_form"].elements["composite_memcached_host"].value,
						port : document.forms["composite_form"].elements["composite_memcached_port"].value
					},
					onsuccess: function(result) {
						var status = BX("check_connection_status");
						if (result && result.text)
						{
							var color = "green";
							if (result.status && result.status === "error")
							{
								color = "red";
							}

							status.style.color = color;
							status.innerHTML = result.text;
						}
					}
				});
			}
		</script>
		<input type="button" name="" value="<?=GetMessage("MAIN_COMPOSITE_CHECK_CONNECTION")?>" onclick="checkConnection()" />&nbsp;<span id="check_connection_status"></span><br><br><br>
		<?=GetMessage("MAIN_COMPOSITE_HOST_HINT");?>
	</td>
</tr>
<tr id="composite_cluster_hint_row" <?if ($compositeOptions["STORAGE"] !== "memcached_cluster") echo 'style="display:none"'?>>
	<td class="adm-required-field"></td>
	<td><?=GetMessage("MAIN_COMPOSITE_CLUSTER_HINT", array(
			"#A_START#" => "<a href=\"/bitrix/admin/cluster_memcache_list.php?lang=".LANGUAGE_ID."&group_id=".(defined("BX_CLUSTER_GROUP") ? BX_CLUSTER_GROUP : 1)."\">",
			"#A_END#" => "</a>"
		));?></td>
</tr>

<tr id="composite_quota_row" <?if ($compositeOptions["STORAGE"] !== "files") echo 'style="display:none"'?>>
	<td><?=GetMessage("MAIN_COMPOSITE_QUOTA")?>:</td>
	<td>
		<input type="text" size="8" name="composite_quota" value="<?echo intval($compositeOptions["FILE_QUOTA"])?>">
	</td>
</tr>
<?
if(CHTMLPagesCache::isOn())
{
	$arStatistic = CHTMLPagesCache::readStatistic();?>
	<tr id="composite_quota_stat_row" <?if ($compositeOptions["STORAGE"] !== "files") echo 'style="display:none"'?>>
		<td><?=GetMessage("MAIN_COMPOSITE_HITS_WITHOUT_CACHE")?></td>
		<td><?echo $arStatistic["QUOTA"]?></td>
	</tr>
	<tr id="composite_quota_size_row" <?if ($compositeOptions["STORAGE"] !== "files") echo 'style="display:none"'?>>
		<td><?=GetMessage("MAIN_COMPOSITE_STAT_FILE_SIZE")?></td>
		<td><?=CFile::FormatSize($arStatistic["FILE_SIZE"])?></td>
	</tr>
	<?
}
?>
<tr>
	<td></td>
	<td>
		<a href="/bitrix/admin/cache.php?lang=<?=LANGUAGE_ID?>&cachetype=html&tabControl_active_tab=fedit2"><?=GetMessage("MAIN_COMPOSITE_CLEAR_CACHE")?></a>
	</td>
</tr>
<?
$tabControl->BeginNextTab();
$arUsedGroups = array();
$groups = $compositeOptions["GROUPS"];
$arGROUPS = array();
$b = "";
$o = "";
$rsGroups = CGroup::GetList($b, $o, array("ACTIVE"=>"Y", "ADMIN"=>"N", "ANONYMOUS"=>"N"));
while ($arGroup = $rsGroups->Fetch())
{
	$arGROUPS[] = $arGroup;
}

?>

	<select style="width: 400px" disabled>
		<option value=""><?=GetMessage("MAIN_COMPOSITE_ANONYMOUS_GROUP")?></option>
	</select><br><br>

<?
if(is_array($groups))
{
	foreach($groups as $group)
	{
		?>

			<select style="width: 400px" name="group[]">
				<option value=""><?=GetMessage("MAIN_NO")?></option>
				<?
				foreach ($arGROUPS as $arGroup)
				{
					?>
					<option
						value="<? echo htmlspecialcharsbx($arGroup["ID"]) ?>"
						<? echo $group == $arGroup["ID"] ? 'selected="selected"' : '' ?>
						><? echo htmlspecialcharsEx($arGroup["NAME"] . " [" . $arGroup["ID"] . "]") ?></option>
				<?
				}
				?>
			</select><br><br>
	<?
	}
}
?>
	<div id="groups-select" style="display: none;">
		<select style="width: 400px" name="group[]">
			<option value=""><?=GetMessage("MAIN_COMPOSITE_SELECT_GROUP") ?></option>
			<?
			foreach ($arGROUPS as $arGroup)
			{
				?>
				<option
					value="<? echo htmlspecialcharsbx($arGroup["ID"]) ?>"
					><? echo htmlspecialcharsEx($arGroup["NAME"] . " [" . $arGroup["ID"] . "]") ?></option>
			<?
			}
			?>
		</select><br><br>
	</div>
	<div id="groups-add">
		<a class="bx-action-href" href="javascript:addGroups()"><?=GetMessage("MAIN_ADD")?></a>
		<script>
			function addGroups()
			{
				var groupsSelect = BX('groups-select');
				var row = BX.clone(groupsSelect);
				row.style.display = "block";
				groupsSelect.parentNode.insertBefore(row, BX('groups-add'));
			}
		</script>
	</div>
	<?
$tabControl->BeginNextTab();?>


<?
$showBanner = Frame::isBannerEnabled();
?>
<tr>
	<td colspan="2">
		<div class="adm-list adm-list-radio">
			<div class="adm-list-item">
				<div class="adm-list-control">
					<input
						type="checkbox"
						value="Y"
						id="composite_show_banner_checkbox"
						<?if ($showBanner):?>checked<?endif?>
						onclick="onShowBannerClick(this)"
					>
					<input
						type="hidden"
						name="composite_show_banner"
						id="composite_show_banner"
						value="<?=($showBanner ? "Y" : "N")?>"
					>
				</div>
				<div class="adm-list-label">
					<label for="composite_show_banner_checkbox"><?=GetMessage("MAIN_COMPOSITE_SHOW_BANNER")?></label>
				</div>
			</div>
		</div>
		<script>
			function onShowBannerClick(checkbox)
			{
				BX("composite_show_banner").value = checkbox.checked ? "Y" : "N";
				BX("composite_button_disclaimer_row").style.display = checkbox.checked ? "" : "none";
				BX("composite_button_row").style.display = checkbox.checked ? "" : "none";
			}
		</script>
	</td>
</tr>
<tr id="composite_button_disclaimer_row" <?if (!$showBanner):?>style="display: none"<?endif?>>
	<td colspan="2">
		<?=BeginNote()?><?=GetMessage("MAIN_COMPOSITE_BANNER_DISCLAIMER")?><?=EndNote()?>
	</td>
</tr>

<tr class="adm-detail-valign-top" id="composite_button_row" <?if (!$showBanner):?>style="display: none"<?endif?>>
	<td><?=GetMessage("MAIN_COMPOSITE_BANNER_SELECT_STYLE")?>:</td>
	<td>
		<div class="adm-composite-btn-wrap">
			<div class="adm-composite-btn-select-wrap">
			<span class="adm-composite-btn-select" onclick="showPopup(this)">
				<span id="composite-banner" class="bx-composite-btn bx-btn-white"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
				<span class="adm-composite-btn-select-icon"></span>
			</span>
			<span class="adm-composite-btn-checkbox-wrap">
				<input type="checkbox" id="composite_white_bgcolor" class="adm-composite-btn-checkbox" onclick="setWhiteBgColor(this.checked)"/><label class="adm-composite-btn-label-bg" for="composite_white_bgcolor"><?=GetMessage("MAIN_COMPOSITE_BANNER_STYLE_WHITE")?></label>
			</span>

			</div>
			<div class="adm-composite-btn-color">
				<div class="adm-composite-btn-label"><?=GetMessage("MAIN_COMPOSITE_BANNER_BGCOLOR")?></div>
				<input type="text" name="composite_banner_bgcolor" id="composite_banner_bgcolor" value="" class="adm-composite-btn-color-inp"/>
			</div>
			<div class="adm-composite-btn-logo-block">
				<div class="adm-composite-btn-label"><?=GetMessage("MAIN_COMPOSITE_BANNER_STYLE")?></div>
				<div class="adm-composite-btn-logo-list">
				<span class="adm-composite-btn-logo">
					<label class="adm-composite-btn-logo-img adm-composite-btn-logo-white" for="composite_banner_style_white"></label><input id="composite_banner_style_white" class="adm-composite-btn-logo-radio" type="radio" name="composite_banner_style" value="white" onclick="changeBannerType(null, 'white')" />
				</span><span class="adm-composite-btn-logo">
					<label class="adm-composite-btn-logo-img adm-composite-btn-logo-grey" for="composite_banner_style_grey"></label><input id="composite_banner_style_grey" class="adm-composite-btn-logo-radio" type="radio" name="composite_banner_style" value="grey" onclick="changeBannerType(null, 'grey')"/>
				</span><span class="adm-composite-btn-logo">
					<label class="adm-composite-btn-logo-img adm-composite-btn-logo-red" for="composite_banner_style_red"></label><input id="composite_banner_style_red" class="adm-composite-btn-logo-radio" type="radio" name="composite_banner_style" value="red" onclick="changeBannerType(null, 'red')" />
				</span><span class="adm-composite-btn-logo">
					<label class="adm-composite-btn-logo-img adm-composite-btn-logo-black" for="composite_banner_style_black"></label><input id="composite_banner_style_black" class="adm-composite-btn-logo-radio" type="radio" name="composite_banner_style" value="black" onclick="changeBannerType(null, 'black')"/>
				</span>
				</div>
			</div>
		</div>

		<div id="btn-popup" class="adm-composite-btn-popup" style="display: none;">
			<span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-white" style="background-color: #000000;" href="#" onclick="selectPreset('#000000', 'white')"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span><span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-white" style="background-color: #e94524;" href="#" onclick="selectPreset('#E94524', 'white')"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span><span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-white" style="background-color: #3a424d;" href="#" onclick="selectPreset('#3A424D', 'white')"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span><span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-white" style="background-color: #d37222;" href="#" onclick="selectPreset('#D37222', 'white')"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span><span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-grey" style="background-color: #dae1e5;" href="#" onclick="selectPreset('#DAE1E5', 'grey')"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span><span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-grey bx-btn-border" style="background-color: #ffffff;" href="#" onclick="selectPreset('#FFFFFF', 'grey' , true)"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span><span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-white" style="background-color: #59b7cf;" href="#" onclick="selectPreset('#59B7CF', 'white')"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span><span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-white" style="background-color: #2f6e73;" href="#" onclick="selectPreset('#2F6E73', 'white')"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span><span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-red bx-btn-border" style="background-color: #ffffff;" href="#" onclick="selectPreset('#FFFFFF', 'red', true)"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span><span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-white" style="background-color: #51626b;" href="#" onclick="selectPreset('#51626B', 'white')"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span><span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-white" style="background-color: #83a61a;" href="#" onclick="selectPreset('#83A61A', 'white')"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span><span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-black bx-btn-border" style="background-color: #ffffff;" href="#" onclick="selectPreset('#FFFFFF', 'black', true)"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span><span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-white" style="background-color: #b39c85;" href="#" onclick="selectPreset('#B39C85', 'white')"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span><span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-white" style="background-color: #ff8534;" href="#" onclick="selectPreset('#FF8534', 'white')"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span><span class="adm-composite-btn-popup-wrap">
				<span class="bx-composite-btn bx-btn-white" style="background-color: #51c1ef;" href="#" onclick="selectPreset('#51C1EF', 'white')"><?=GetMessage("COMPOSITE_BANNER_TEXT")?></span>
			</span>
		</div>
		<script type="text/javascript">

			BX.ready(function() {

				var banner = BX("composite-banner");
				var bgcolorInput = BX("composite_banner_bgcolor");
				var whiteRadio = BX("composite_banner_style_white");
				var whiteBgCheckbox = BX("composite_white_bgcolor");
				var radio = document.forms["composite_form"].elements["composite_banner_style"];
				var lastStyle = "";
				var bgColorBeforeBorder = "";
				var styleBeforeBorder = "";

				window.changeBannerType = function(bgcolor, style, border)
				{
					if (border === true)
					{
						styleBeforeBorder = radio.value;
						bgColorBeforeBorder = bgcolorInput.value;

						bgcolorInput.disabled = true;
						whiteRadio.disabled = true;
						whiteBgCheckbox.checked = true;
						BX.addClass(banner, "bx-btn-border");
					}
					else if (border === false)
					{
						bgcolorInput.disabled = false;
						whiteRadio.disabled = false;
						whiteBgCheckbox.checked = false;
						BX.removeClass(banner, "bx-btn-border");
					}

					if (BX.type.isNotEmptyString(bgcolor))
					{
						banner.style.backgroundColor = bgcolor;
						bgcolorInput.value = bgcolor;
					}

					if (BX.type.isNotEmptyString(style))
					{
						BX.removeClass(banner, lastStyle);
						lastStyle = "bx-btn-" + style;
						BX.addClass(banner, lastStyle);
						BX("composite_banner_style_" + style, true).checked = true;
					}
				};

				window.selectPreset = function(bgcolor, style, border)
				{
					changeBannerType(bgcolor, style, border === true);
					window.bannerPopup.close();
				};

				window.onBgColorChanged = function()
				{
					banner.style.backgroundColor = bgcolorInput.value;
				};

				window.setWhiteBgColor = function(border)
				{
					if (border)
					{
						changeBannerType(
							"#FFFFFF",
							lastStyle == "bx-btn-white" || lastStyle == "" ? "red" : null,
							true
						);
					}
					else
					{
						if (bgColorBeforeBorder == "")
						{
							bgColorBeforeBorder = "#E94524";
						}

						if (styleBeforeBorder == "")
						{
							styleBeforeBorder = "white";
						}
						changeBannerType(bgColorBeforeBorder, styleBeforeBorder, false);
					}
				};

				window.showPopup = function(btn)
				{
					window.bannerPopup = BX.PopupWindowManager.create("adm-composite-btn-popup", btn, {
						content: BX("btn-popup"),
						lightShadow: true,
						closeByEsc : true,
						autoHide : true,
						offsetTop : 5
					});
					window.bannerPopup.show();
				};

				var bgcolor = "<?=CUtil::JSEscape($compositeOptions["BANNER_BGCOLOR"])?>";
				var style = "<?=CUtil::JSEscape($compositeOptions["BANNER_STYLE"])?>";
				if (!BX.type.isNotEmptyString(bgcolor))
				{
					bgcolor = "#E94524";
				}

				if (!BX.type.isNotEmptyString(style))
				{
					style = "white";
				}

				changeBannerType(bgcolor, style, BX.util.in_array(bgcolor.toUpperCase(), ["#FFF", "#FFFFFF", "WHITE"]));

				BX.bind(bgcolorInput, "change", onBgColorChanged);
				BX.bind(bgcolorInput, "cut", onBgColorChanged);
				BX.bind(bgcolorInput, "paste", onBgColorChanged);
				BX.bind(bgcolorInput, "drop", onBgColorChanged);
				BX.bind(bgcolorInput, "keyup", onBgColorChanged);
				BX.bind(document.forms["composite_form"], "submit", function() {  bgcolorInput.disabled = false; })
			});

		</script>
	</td>
</tr>


<?$tabControl->Buttons(array(
	"disabled" => !$isAdmin,
	"btnSave" => false,
	"btnApply" => false,
	"btnCancel" => false,
));
?>
	<input type="submit" name="composite_save_opt" class="adm-btn-save" value="<?echo GetMessage("MAIN_COMPOSITE_SAVE");?>"<?if(!$isAdmin) echo " disabled"?>>
<?
$tabControl->End();
?>
<?echo bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?echo LANGUAGE_ID?>">
</form>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>