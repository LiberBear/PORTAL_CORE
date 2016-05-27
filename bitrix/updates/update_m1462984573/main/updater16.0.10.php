<?
$updater->CopyFiles("install/components", "components");
$updater->CopyFiles("install/images", "images");
$updater->CopyFiles("install/js", "js");

if($updater->CanUpdateKernel())
{
	if(defined("BX_ICONV_DISABLE") && BX_ICONV_DISABLE === true)
	{
		$content = file_get_contents($_SERVER["DOCUMENT_ROOT"]."/bitrix/.settings.php");
		if($content <> '')
		{
			if(strpos($content, "disable_iconv") === false)
			{
				$content = preg_replace("/return\\s+array\\s*\\(/i", "return array (\n  'disable_iconv' => array('value' => true),", $content);
				file_put_contents($_SERVER["DOCUMENT_ROOT"]."/bitrix/.settings.php", $content);
			}
		}
	}

	$arToDelete = array(
		"modules/main/classes/general/smtpclient.php",
		"modules/main/install/components/bitrix/main.post.list/component.php",
		"components/bitrix/main.post.list/component.php",
	);
	foreach($arToDelete as $file)
		CUpdateSystem::DeleteDirFilesEx($_SERVER["DOCUMENT_ROOT"].$updater->kernelPath."/".$file);
}
?>
