<?
global $DOCUMENT_ROOT, $MESS;

IncludeModuleLangFile(__FILE__);

if (class_exists("timeman")) return;

class timeman extends CModule
{
	var $MODULE_ID = "timeman";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	function timeman()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		elseif (defined('TIMEMAN_VERSION') && defined('TIMEMAN_VERSION_DATE'))
		{
			$this->MODULE_VERSION = TIMEMAN_VERSION;
			$this->MODULE_VERSION_DATE = TIMEMAN_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("TIMEMAN_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("TIMEMAN_MODULE_DESCRIPTION");
	}

	function InstallDB()
	{
		global $DB, $APPLICATION;

		if (!$DB->Query("SELECT 'x' FROM b_timeman_entries", true))
		{
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/'.$this->MODULE_ID.'/install/db/'.strtolower($DB->type).'/install.sql');

			if (!empty($errors))
			{
				$APPLICATION->ThrowException(implode("", $errors));
				return false;
			}

			$this->InstallTasks();
		}

		RegisterModule($this->MODULE_ID);

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/fields.php");

		RegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'timeman', 'CReportNotifications', 'AddEvent');
		RegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'timeman', 'CReportNotifications', 'OnFillSocNetAllowedSubscribeEntityTypes');

		RegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'timeman', 'CTimeManNotify', 'OnFillSocNetLogEvents');
		RegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'timeman', 'CTimeManNotify', 'OnFillSocNetAllowedSubscribeEntityTypes');
		RegisterModuleDependences("im", "OnGetNotifySchema", "timeman", "CTimemanNotifySchema", "OnGetNotifySchema");

		RegisterModuleDependences('main', 'OnAfterUserUpdate', 'timeman', 'CTimeManNotify', 'OnAfterUserUpdate');
		RegisterModuleDependences('main', 'OnAfterUserUpdate', 'timeman', 'CReportNotifications', 'OnAfterUserUpdate');

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $APPLICATION;

		$errors = null;

		if ((true == array_key_exists("savedata", $arParams)) && ($arParams["savedata"] != 'Y'))
		{
			if(CModule::IncludeModule("socialnetwork"))
			{
				$dbLog = CSocNetLog::GetList(
					array(),
					array(
						"ENTITY_TYPE" => array("R", "T"),
						"EVENT_ID" => array("timeman_entry", "report")
					),
					false,
					false,
					array("ID")
				);
				while ($arLog = $dbLog->Fetch())
				{
					CSocNetLog::Delete($arLog["ID"]);
				}
			}

			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/'.$this->MODULE_ID.'/install/db/'.strtolower($DB->type).'/uninstall.sql');

			if (!empty($errors))
			{
				$APPLICATION->ThrowException(implode("", $errors));
				return false;
			}

			$this->UnInstallTasks();
		}

		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'timeman', 'CReportNotifications', 'AddEvent');
		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'timeman', 'CReportNotifications', 'OnFillSocNetAllowedSubscribeEntityTypes');

		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetLogEvents', 'timeman', 'CTimeManNotify', 'OnFillSocNetLogEvents');
		UnRegisterModuleDependences('socialnetwork', 'OnFillSocNetAllowedSubscribeEntityTypes', 'timeman', 'CTimeManNotify', 'OnFillSocNetAllowedSubscribeEntityTypes');
		UnRegisterModuleDependences("im", "OnGetNotifySchema", "timeman", "CTimemanNotifySchema", "OnGetNotifySchema");

		UnRegisterModuleDependences('main', 'OnAfterUserUpdate', 'timeman', 'CTimeManNotify', 'OnAfterUserUpdate');
		UnRegisterModuleDependences('main', 'OnAfterUserUpdate', 'timeman', 'CReportNotifications', 'OnAfterUserUpdate');

		UnRegisterModule($this->MODULE_ID);

		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles()
	{
		global $APPLICATION;

		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/components",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/components",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/admin",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/admin",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/js",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/themes",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/themes",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/tools",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/tools",
				true, true
			);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/images",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/images",
				true, true
			);
		}

		return true;
	}

	function UnInstallFiles()
	{
		return true;
	}

	function DoInstall()
	{
		global $APPLICATION;

		if (!CBXFeatures::IsFeatureEditable('timeman'))
		{
			$this->errors = array(GetMessage("MAIN_FEATURE_ERROR_EDITABLE"));
			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("TIMEMAN_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step1.php");
		}
		else
		{
			if (!IsModuleInstalled($this->MODULE_ID))
			{
				if ($this->InstallDB())
				{
					CBXFeatures::SetFeatureEnabled('timeman', true);
					$this->InstallEvents();
					$this->InstallFiles();
				}
			}
		}
	}

	function DoUninstall()
	{
		global $DB, $APPLICATION, $USER, $step;
		if($USER->IsAdmin())
		{
			$step = IntVal($step);
			if($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage("TIMEMAN_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/unstep1.php");
			}
			elseif($step == 2)
			{
				$this->UnInstallDB(array(
					"savedata" => $_REQUEST["savedata"],
				));
				$this->UnInstallEvents();
				$this->UnInstallFiles();

				CBXFeatures::SetFeatureEnabled('timeman', false);

				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage("TIMEMAN_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/unstep2.php");
			}
		}
	}

	function GetModuleTasks()
	{
		return array(
			'timeman_denied' => array(
				"LETTER" => "D",
				"BINDING" => "module",
				"OPERATIONS" => array(),
			),
			'timeman_subordinate' => array(
				"LETTER" => "N",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'tm_manage', 'tm_read_subordinate', 'tm_write_subordinate'
				),
			),
			'timeman_read' => array(
				"LETTER" => "R",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'tm_read', 'tm_write_subordinate'
				),
			),
			'timeman_write' => array(
				"LETTER" => "T",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'tm_read', 'tm_write'
				),
			),
			'timeman_full_access' => array(
				"LETTER" => "W",
				"BINDING" => "module",
				"OPERATIONS" => array(
					'tm_manage', 'tm_manage_all', 'tm_read', 'tm_write', 'tm_settings'
				),
			),
		);
	}
}
?>