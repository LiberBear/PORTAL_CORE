<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$arComponentDescription = array(
	"NAME" => GetMessage("INTRANET_RESMITS_LIST"),
	"DESCRIPTION" => GetMessage("INTRANET_RESMITS_LIST_DESCRIPTION"),
	"ICON" => "/images/icon.gif",
	"COMPLEX" => "N",
	"PATH" => array(
		"ID" => "intranet",
		'NAME' => GetMessage('INTR_GROUP_NAME'),
		"CHILD" => array(
			"ID" => "resmit",
			"NAME" => GetMessage("INTRANET_RESMIT")
		)
	),
);
?>