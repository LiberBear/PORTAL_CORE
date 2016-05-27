<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
*/


?>
<script>
	BX.ready(function(){
		app.hidePopupLoader();
		app.addButtons({
			backButton:
			{
				type:     'right_text',
				style:    'custom',
				position: 'left',
				name:     '<?=GetMessageJS("MB_TASKS_CANCEL_S")?>',
				callback: function() {
					app.closeModalDialog();
				}
			},
			saveButton:
			{
				type:     'right_text',
				style:    'custom',
				name:     '<?=GetMessageJS("MB_TASKS_SELECT")?>',
				callback: function()
				{
					app.onCustomEvent(
						'onTaskSaveBefore',
						{
							module_id: 'tasks',
							dialogKey:  MBTasks.CPT.edit.dialogKey,
							delayFire:  557,
							taskData:   taskData
						}
					);

					app.closeModalDialog();
				}
			}
		});
	});
</script>
