<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("crm"))
	return;
?>
<tr>
	<td align="right" width="40%"><?= GetMessage("BPCDSA_PD_DEAL") ?>:</td>
	<td width="60%">
		<input type="text" name="deal_id" id="id_deal_id" value="<?= htmlspecialcharsbx($arCurrentValues["deal_id"]) ?>" size="20" />
		<input type="button" value="..." onclick="BPAShowSelector('id_deal_id', 'int');" />
	</td>
</tr>
<tr>
	<td align="right" width="40%"><?= GetMessage("BPCDSA_PD_STAGE") ?>:</td>
	<td width="60%">
		<select name="stage">
			<option value=""><?= GetMessage("BPCDSA_PD_STAGE_N") ?></option>
			<?
			$stages = CCrmStatus::GetStatus('DEAL_STAGE');
			foreach ($stages as $stageId => $stage)
			{
				$s = CCrmDeal::GetStageSemantics($stageId);
				if ($s != 'process')
					continue;
				?><option value="<?= htmlspecialcharsbx($stageId) ?>"<?= ($arCurrentValues["stage"] == $stageId) ? " selected" : "" ?>><?= GetMessage("BPCDSA_PD_STAGE_P", array("#NAME#" => htmlspecialcharsbx($stage['NAME']))) ?></option><?
			}
			?>
		</select>
	</td>
</tr>
