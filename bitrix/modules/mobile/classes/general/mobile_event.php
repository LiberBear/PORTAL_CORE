<?
IncludeModuleLangFile(__FILE__);

class CMobileEvent
{
	public static function PullOnGetDependentModule()
	{
		return Array(
			'MODULE_ID' => "mobile",
			'USE' => Array("PUBLIC_SECTION")
		);
	}
}

class MobileApplication extends Bitrix\Main\Authentication\Application
{
	protected $validUrls = array(
		"/mobile/",
		"/extranet/mobile/",
		"/bitrix/tools/check_appcache.php",
		"/bitrix/tools/disk/uf.php",
		"/bitrix/services/disk/index.php",
		"/bitrix/groupdav.php",
	);

	public function __construct()
	{
		$diskEnabled = \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk');

		if(!$diskEnabled)
		{
			$this->validUrls = array_merge(
				$this->validUrls,
				array(
					"/company/personal.php",
					"/extranet/contacts/personal.php",
					"/docs/index.php",
					"/docs/shared/index.php",
					"/workgroups/index.php"
				));
		}
	}

	public static function OnApplicationsBuildList()
	{
		return array(
			"ID" => "mobile",
			"NAME" => GetMessage("MOBILE_APPLICATION_NAME"),
			"DESCRIPTION" => GetMessage("MOBILE_APPLICATION_DESC"),
			"SORT" => 90,
			"CLASS" => "MobileApplication",
		);
	}
}
