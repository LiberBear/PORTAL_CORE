<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/telephony/.left.menu.php");

$aMenuLinks = Array(
	Array(
		GetMessage("SERVICES_MENU_TELEPHONY_BALANCE"),
		"#SITE_DIR#services/telephony/index.php",
		Array("/services/telephony/detail.php"),
		Array("menu_item_id"=>"menu_telephony_balance"),
		""
	),
	Array(
		GetMessage("SERVICES_MENU_TELEPHONY_LINES"),
		"#SITE_DIR#services/telephony/lines.php",
		Array("/services/telephony/edit.php"),
		Array("menu_item_id"=>"menu_telephony_lines"),
		""
	),
	Array(
		GetMessage("SERVICES_MENU_TELEPHONY_USERS"),
		"#SITE_DIR#services/telephony/users.php",
		Array(),
		Array("menu_item_id"=>"menu_telephony_users"),
		""
	),
	Array(
		GetMessage("SERVICES_MENU_TELEPHONY_PHONES"),
		"#SITE_DIR#services/telephony/phones.php",
		Array(),
		Array("menu_item_id"=>"menu_telephony_phones"),
		""
	),
	Array(
		GetMessage("SERVICES_MENU_TELEPHONY"),
		"#SITE_DIR#services/telephony/configs.php",
		Array(),
		Array("menu_item_id"=>"menu_telephony_configs"),
		""
	),
);
?>