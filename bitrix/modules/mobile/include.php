<?
// delete from updates
//include("module_updater.php");

CModule::AddAutoloadClasses(
	"mobile",
	array(
		"CMobileEvent" => "classes/general/mobile_event.php",
		"CMobileHelper" => "classes/general/mobile_helper.php",
		"MobileApplication" => "classes/general/mobile_event.php",
	)
);

CJSCore::RegisterExt('mobile_voximplant', array(
	'js' => '/bitrix/js/mobile/mobile_voximplant.js',
));
