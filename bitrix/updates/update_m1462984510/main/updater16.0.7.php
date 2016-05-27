<?
$updater->CopyFiles("install/components", "components");
$updater->CopyFiles("install/js", "js");

if($updater->CanUpdateDatabase())
{
	if ($updater->TableExists("b_short_uri"))
	{
		$updater->Query(array(
			"MySql" => "alter table b_short_uri
				CHANGE MODIFIED MODIFIED datetime not null,
				CHANGE LAST_USED LAST_USED datetime null
			"
		));
	}

	if ($updater->TableExists("b_user_counter"))
	{
		if(!$DB->Query("select TIMESTAMP_X from b_user_counter WHERE 1=0", true))
		{
			$updater->Query(array(
				"MySQL"  => "ALTER TABLE b_user_counter ADD TIMESTAMP_X datetime not null default '3000-01-01 00:00:00'",
				"Oracle" => "ALTER TABLE B_USER_COUNTER ADD TIMESTAMP_X date default (TO_DATE('3000-01-01 00:00:00', 'YYYY-MM-DD HH24:MI:SS')) not null",
				"MSSQL"  => "ALTER TABLE B_USER_COUNTER ADD TIMESTAMP_X datetime CONSTRAINT DF_B_USER_COUNTER_TIMESTAMP_X DEFAULT CONVERT(DATETIME, '3000-01-01 00:00:00', 121) not null",
			));
		}

		if (
			$DB->Query("select TIMESTAMP_X from b_user_counter WHERE 1=0", true)
			&& !$DB->IndexExists("b_user_counter", array("TIMESTAMP_X"))
		)
		{
			$updater->Query(array(
				"MySQL"  => "CREATE INDEX ix_buc_ts ON b_user_counter (TIMESTAMP_X)",
				"Oracle" => "CREATE INDEX ix_buc_ts ON B_USER_COUNTER(TIMESTAMP_X)",
				"MSSQL"  => "CREATE INDEX ix_buc_ts ON B_USER_COUNTER(TIMESTAMP_X)",
			));
		}

		if (
			$DB->Query("select SENT from b_user_counter WHERE 1=0", true)
			&& $DB->Query("select USER_ID from b_user_counter WHERE 1=0", true)
			&& !$DB->IndexExists("b_user_counter", array("SENT", "USER_ID"))
		)
		{
			$updater->Query(array(
				"MySQL"  => "CREATE INDEX ix_buc_sent_userid ON b_user_counter (SENT, USER_ID)",
				"Oracle" => "CREATE INDEX ix_buc_sent_userid ON B_USER_COUNTER (SENT, USER_ID)",
				"MSSQL"  => "CREATE INDEX ix_buc_sent_userid ON B_USER_COUNTER (SENT, USER_ID)",
			));
		}
	}

	$agent = $DB->ForSql("CUserCounter::DeleteOld();");
	$res = $DB->Query("SELECT 'x' FROM b_agent WHERE MODULE_ID='main' AND NAME='".$agent."'");
	if(!$res->Fetch())
	{
		$updater->Query("INSERT INTO b_agent (MODULE_ID, SORT, NAME, ACTIVE, AGENT_INTERVAL, IS_PERIOD, NEXT_EXEC) VALUES('main', 100, '".$agent."', 'Y', 60*60*24, 'N', ".$DB->GetNowDate().")");
	}
	
	$agent = $DB->ForSql("\\Bitrix\\Main\\Analytics\\CounterDataTable::submitData();");
	$res = $DB->Query("SELECT 'x' FROM b_agent WHERE MODULE_ID='main' AND NAME='".$agent."'");
	if(!$res->Fetch())
	{
		$updater->Query("INSERT INTO b_agent (MODULE_ID, SORT, NAME, ACTIVE, AGENT_INTERVAL, IS_PERIOD, NEXT_EXEC) VALUES('main', 100, '".$agent."', 'Y', 60, 'N', ".$DB->GetNowDate().")");
	}
}

if($updater->CanUpdateKernel())
{
	$arToDelete = array(
		"modules/main/install/components/bitrix/main.interface.form/templates/mobile/style_add.css",
		"components/bitrix/main.interface.form/templates/mobile/style_add.css",
	);
	foreach($arToDelete as $file)
		CUpdateSystem::DeleteDirFilesEx($_SERVER["DOCUMENT_ROOT"].$updater->kernelPath."/".$file);
}
?>
