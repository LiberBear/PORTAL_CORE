<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if($_SERVER["REQUEST_METHOD"]=="POST" && strlen($_POST["action"])>0 && check_bitrix_sessid())
{
	$action = $_POST["action"];
	$gridId = $_POST["gridId"];
	$curOption = CUserOptions::GetOption("mobile.interface.grid", $gridId);

	if ($action == "fields")
	{
		$fields = $_POST["fields"];
		if (is_array($fields))
			$curOption["fields"] = $fields;
		elseif(isset($curOption["fields"]))
			unset($curOption["fields"]);

		CUserOptions::SetOption("mobile.interface.grid", $gridId, $curOption);
	}
}
?>
