<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var $APPLICATION CAllMain
 **/

use Bitrix\Main\Page\AssetShowTargetType;

$platform = "android";
if (CModule::IncludeModule("mobileapp"))
{
	CMobile::Init();
	$platform = CMobile::$platform;
}
else
{
	die();
}

\Bitrix\Main\Data\AppCacheManifest::getInstance()->setManifestCheckFile(SITE_DIR . "mobile/");

define("MOBILE_MODULE_VERSION", "160101");
$moduleVersion = (defined("MOBILE_MODULE_VERSION") ? MOBILE_MODULE_VERSION : "default");

$APPLICATION->IncludeComponent("bitrix:mobile.data", "", Array(
	"START_PAGE" => SITE_DIR . "mobile/index.php?version=" . $moduleVersion,
	"MENU_PAGE" => SITE_DIR . "mobile/left.php?version=" . $moduleVersion,
	"CHAT_PAGE" => SITE_DIR . "mobile/im/right.php?version=" . $moduleVersion
), false, Array("HIDE_ICONS" => "Y"));
?><!DOCTYPE html>
<html<?= $APPLICATION->ShowProperty("manifest"); ?> class="<?= $platform; ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=<?= SITE_CHARSET ?>"/>
	<meta name="format-detection" content="telephone=no">
	<?

	if (!defined("BX_DONT_INCLUDE_MOBILE_TEMPLATE_CSS"))
	{
		$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH . (defined('MOBILE_TEMPLATE_CSS') ? MOBILE_TEMPLATE_CSS : "/common_styles.css"));
	}

	$APPLICATION->AddBufferContent(array(&$APPLICATION, "GetHeadStrings"), 'BEFORE_CSS');
	$APPLICATION->ShowHeadStrings();
	$APPLICATION->ShowHeadScripts();
	$APPLICATION->AddBufferContent(array(&$APPLICATION, "GetCSS"), true, true, AssetShowTargetType::TEMPLATE_PAGE);
	CJSCore::Init('ajax');
	?>
	<script type="text/javascript" src="<?=CUtil::GetAdditionalFileURL(BX_PERSONAL_ROOT.'/js/mobile/mobile_tools.js')?>"></script>
	<title><?$APPLICATION->ShowTitle()?></title>
</head>
<body class="<?= $APPLICATION->ShowProperty("BodyClass"); ?>"><?
?>
<script>
	BX.message({
		MobileSiteDir: '<?=CUtil::JSEscape(htmlspecialcharsbx(SITE_DIR))?>'
	});
</script><?
?>