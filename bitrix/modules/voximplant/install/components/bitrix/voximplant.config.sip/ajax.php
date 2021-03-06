<?
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define("NO_AGENT_CHECK", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

if (!CModule::IncludeModule("voximplant"))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'VI_MODULE_NOT_INSTALLED'));
	CMain::FinalActions();
	die();
}

if (!CVoxImplantMain::CheckAccess())
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'AUTHORIZE_ERROR'));
	CMain::FinalActions();
	die();
}

if (check_bitrix_sessid())
{
	if ($_POST['VI_ADD'])
	{
		$arSend['ERROR'] = '';

		CUtil::decodeURIComponent($_POST);

		$viSip = new CVoxImplantSip();
		$result = $viSip->Add(Array(
			'TYPE' => strtolower($_POST['TYPE']),
			'PHONE_NAME' => $_POST['TITLE'],
			'SERVER' => $_POST['SERVER'],
			'LOGIN' => $_POST['LOGIN'],
			'PASSWORD' => $_POST['PASSWORD'],
		));
		if ($result)
		{
			$arSend['RESULT'] = $result;
		}
		else
		{
			$arSend['ERROR'] = $viSip->GetError()->msg;
		}
		echo CUtil::PhpToJsObject($arSend);
	}
	else if ($_POST['VI_DELETE'] == 'Y')
	{
		$arSend['ERROR'] = '';

		$viSip = new CVoxImplantSip();
		$viSip->Delete($_POST['CONFIG_ID']);

		echo CUtil::PhpToJsObject($arSend);
	}
	else
	{
		echo CUtil::PhpToJsObject(Array('ERROR' => 'UNKNOWN_ERROR'));
	}
}
else
{
	echo CUtil::PhpToJsObject(Array(
		'BITRIX_SESSID' => bitrix_sessid(),
		'ERROR' => 'SESSION_ERROR'
	));
}
CMain::FinalActions();
die();