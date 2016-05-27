<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

use \Bitrix\Tasks\Manager;
use \Bitrix\Tasks\Util\Type;

$templateId = $arResult['TEMPLATE_DATA']['ID'];
?>

<?//top right menu?>
<?$this->SetViewTarget("pagetitle", 100);?>
	<div class="task-list-toolbar">
		<div class="task-list-toolbar-actions">
			<a href="<?=htmlspecialcharsbx($arParams['PATH_TO_TASKS'])?>" class="task-list-back"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_TO_LIST')?></a>
			<?$APPLICATION->IncludeComponent(
				'bitrix:tasks.task.detail.parts',
				'flat',
				array(
					'MODE' => 'VIEW TASK',
					'BLOCKS' => array('templateselector'),
					'TEMPLATE_DATA' => array(
						'ID' => 'templateselector-'.$templateId,
						'DATA' => array(
							'TEMPLATES' => 	$arResult['AUX_DATA']['TEMPLATE'],
						),
						'PATH_TO_TASKS_TASK' => 		$arParams['PATH_TO_TASKS_TASK_ORIGINAL'],
						'PATH_TO_TASKS_TEMPLATES' => 	$arParams['PATH_TO_TASKS_TEMPLATES'],
					)
				),
				false,
				array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
			);?>
		</div>
	</div>
<?$this->EndViewTarget();?>

<?$hasFatals = false;?>
<?if(!empty($arResult['ERROR'])):?>
	<?foreach($arResult['ERROR'] as $error):?>
		<?if($error['TYPE'] == 'FATAL'):?>
			<div class="task-message-label error"><?=htmlspecialcharsbx($error['MESSAGE'])?></div>
			<?$hasFatals = true;?>
		<?endif?>
	<?endforeach?>
<?endif?>

<?if(!$hasFatals):?>

	<?if(Type::isIterable($arResult['ERROR']) && !empty($arResult['ERROR'])):?>
		<?foreach($arResult['ERROR'] as $error):?>
			<div class="task-message-label <?=($error['TYPE'] == 'WARNING' ? 'warning' : 'error')?>"><?=htmlspecialcharsbx($error['MESSAGE'])?></div>
		<?endforeach?>
	<?endif?>

	<?if($arResult['COMPONENT_DATA']['EVENT_TYPE'] == 'ADD' && !empty($arResult['DATA']['EVENT_TASK'])):?>
		<div class="task-message-label">
			<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_SAVED');?>.
			<?if($arResult['DATA']['EVENT_TASK']['ID'] != $arResult['DATA']['TASK']['ID']):?>
				<a href="<?=\Bitrix\Tasks\UI\Task::makeActionUrl($arParams['PATH_TO_TASKS_TASK'], $arResult['DATA']['EVENT_TASK']['ID'], 'view');?>" target="_blank"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_VIEW_TASK');?></a>
			<?endif?>
		</div>
	<?endif?>

	<?
	$taskData = !empty($arResult['DATA']['TASK']) ? $arResult['DATA']['TASK'] : array();
	$editMode = $arResult['TEMPLATE_DATA']['EDIT_MODE'];
	$taskCan = $taskData['ACTION'];
	$state = $arResult['COMPONENT_DATA']['STATE'];
	$inputPrefix = $arResult['TEMPLATE_DATA']['INPUT_PREFIX'];
	$taskUrlTemplate = str_replace(array('#task_id#', '#action#'), array('{{VALUE}}', 'view'), $arParams['PATH_TO_TASKS_TASK_ORIGINAL']);
	$openedBlocks = $arResult['TEMPLATE_DATA']['BLOCKS']['OPENED'];
	$blockClasses = $arResult['TEMPLATE_DATA']['BLOCKS']['CLASSES'];
    $userProfileUrlTemplate = str_replace('#user_id#', '{{VALUE}}', $arParams['PATH_TO_USER_PROFILE']);
	?>

	<div id="bx-component-scope-<?=$templateId?>" class="task-form">

        <?//no need to load html when we intend to close the interface?>
        <?if($arResult['TEMPLATE_DATA']['SHOW_SUCCESS_MESSAGE']):?>
            <div class="tasks-success-message">
                <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_CHANGES_SAVED')?>
            </div>
        <?else:?>
		    <form action="<?=POST_FORM_ACTION_URI?>" method="post" id="task-form-<?=$arResult['TEMPLATE_DATA']['ID']?>" name="task-form" data-bx-id="task-edit-form">

			<input type="hidden" name="SITE_ID" value="<?=SITE_ID?>" />
			<input data-bx-id="task-edit-csrf" type="hidden" name="sessid" value="<?=bitrix_sessid()?>" />

			<input type="hidden" name="BACKURL" value="<?=htmlspecialcharsbx($arResult['TEMPLATE_DATA']['BACKURL'])?>" />
			<input type="hidden" name="CANCELURL" value="<?=htmlspecialcharsbx($arResult['TEMPLATE_DATA']['CANCELURL'])?>" />

			<?if(intval($taskData['ID'])):?>
				<input type="hidden" name="ACTION[0][OPERATION]" value="task.update" />
				<input type="hidden" name="ACTION[0][ARGUMENTS][id]" value="<?=intval($taskData['ID'])?>" />
			<?else:?>
				<input type="hidden" name="ACTION[0][OPERATION]" value="task.add" />
			<?endif?>
			<input type="hidden" name="ACTION[0][PARAMETERS][CODE]" value="task_action" />

            <?if(Type::isIterable($arResult['COMPONENT_DATA']['DATA_SOURCE'])):?>
                <input type="hidden" name="ADDITIONAL[DATA_SOURCE][TYPE]" value="<?=htmlspecialcharsbx($arResult['COMPONENT_DATA']['DATA_SOURCE']['TYPE'])?>" />
                <input type="hidden" name="ADDITIONAL[DATA_SOURCE][ID]" value="<?=intval($arResult['COMPONENT_DATA']['DATA_SOURCE']['ID'])?>" />
            <?endif?>

			<div class="task-info">
				<div class="task-info-panel">
					<div class="task-info-panel-important">
						<input data-bx-id="task-edit-priority-cb" type="checkbox" id="tasks-task-priority-cb" <?=($taskData['PRIORITY'] == CTasks::PRIORITY_HIGH ? 'checked' : '')?>>
						<label for="tasks-task-priority-cb"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_PRIORITY')?></label>
						<input data-bx-id="task-edit-priority" type="hidden" name="<?=$inputPrefix?>[PRIORITY]" value="<?=intval($taskData['PRIORITY'])?>" />
					</div>
					<div class="task-info-panel-title"><input data-bx-id="task-edit-title" type="text" name="<?=$inputPrefix?>[TITLE]" value="<?=htmlspecialcharsbx($taskData['TITLE'])?>" placeholder="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_WHAT_TO_BE_DONE')?>"/></div>
				</div>
				<div data-bx-id="task-edit-editor-container" class="task-info-editor">
					<?$APPLICATION->IncludeComponent(
						'bitrix:main.post.form',
						'',
						$arResult['AUX_TEMPLATE_DATA']['EDITOR_PARAMETERS'],
						false,
						array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
					);?>
				</div>
			</div>

			<?$blockName = Manager\Task::SE_PREFIX.'CHECKLIST';?>
			<div data-bx-id="task-edit-checklist" data-block-name="<?=$blockName?>" class="task-checklist-container pinable-block task-openable-block <?=$blockClasses[$blockName]?>">

                <?$APPLICATION->IncludeComponent(
                    'bitrix:tasks.task.detail.parts',
                    'flat',
                    array(
                        'MODE' => 'VIEW TASK',
                        'BLOCKS' => array('checklist'),
                        'TEMPLATE_DATA' => array(
                            'ID' => 'checklist-'.$templateId,
                            'INPUT_PREFIX' => $inputPrefix.'['.$blockName.']',
                            "TASK_ID" => $taskData['ID'],
                            "TASK_CAN" => $taskCan,
                        )
                    ),
                    false,
                    array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
                );?>
                <span data-bx-id="task-edit-chooser" data-target="checklist" class="task-option-fixedbtn" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_PINNER_HINT')?>"></span>

			</div>

			<div class="task-options task-options-main">

                <div class="task-options-item-destination-wrap">

                    <div>
                        <div class="task-options-item task-options-item-destination">
                            <span class="task-options-item-param"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_RESPONSIBLE')?></span>
                            <div class="task-options-item-open-inner">

                                <span id="bx-component-scope-responsible-<?=$templateId?>" class="task-options-destination-wrap user-item-set-empty-true">
                                    <span data-bx-id="task-edit-responsible-notice" class="task-options-destination-wrap-popup hidden-soft"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_MULTIPLE_RESPONSIBLE_NOTICE')?></span>

                                    <span data-bx-id="user-item-set-items">
                                        <script type="text/html" data-bx-id="user-item-set-item">
                                            <span data-bx-id="user-item-set-item" data-item-value="{{VALUE}}" class="task-inline-selector-item task-inline-selector-item-{{USER_TYPE}} {{ITEM_SET_INVISIBLE}}">
                                                <span class="task-options-destination task-options-destination-all-users">
	                                                <a href="<?=htmlspecialcharsbx($userProfileUrlTemplate)?>" target="_blank" class="task-options-destination-text">{{DISPLAY}}</a><span class="task-option-inp-del" data-bx-id="user-item-set-item-delete" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DELETE')?>"></span>
                                                    <input type="hidden" name="<?=$inputPrefix?>[SE_RESPONSIBLE][{{VALUE}}][ID]" value="{{VALUE}}">
	                                                <input type="hidden" name="<?=$inputPrefix?>[SE_RESPONSIBLE][{{VALUE}}][EMAIL]" value="{{EMAIL}}">
	                                                <input type="hidden" name="<?=$inputPrefix?>[SE_RESPONSIBLE][{{VALUE}}][NAME]" value="{{NAME}}">
	                                                <input type="hidden" name="<?=$inputPrefix?>[SE_RESPONSIBLE][{{VALUE}}][LAST_NAME]" value="{{LAST_NAME}}">
                                                </span>
                                            </span>
                                        </script>
                                    </span>

                                    <span data-bx-id="task-edit-responsible-search" class="task-inline-selector-item fixed-width">
                                        <span class="task-options-destination-loader"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_LOADING')?>...</span>
                                        <input data-bx-id="user-item-set-search network-selector-search" type="text" value="" autocomplete="off" class="task-options-destination-inp">
                                        <a data-bx-id="user-item-set-open-form" href="javascript:void(0)" class="feed-add-destination-link" ><?=($editMode ? Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_CHANGE') : '+ '.Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD_MORE'))?></a>
                                    </span>

                                </span>

                                <span class="task-dashed-link task-dashed-link-add">
                                    <span class="task-dashed-link-inner" data-bx-id="task-edit-toggler" data-target="originator"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ORIGINATOR')?></span> <span class="task-dashed-link-inner" data-bx-id="task-edit-toggler" data-target="auditor"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_AUDITORS')?></span> <span class="task-dashed-link-inner" data-bx-id="task-edit-toggler" data-target="accomplice"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ACCOMPLICES')?></span>
                                </span>

                            </div>
                        </div>
                    </div>

                    <?$blockName = Manager\Task::SE_PREFIX.'ORIGINATOR';?>
                    <div data-bx-id="task-edit-originator" data-block-name="<?=$blockName?>" class="pinable-block task-openable-block <?=$blockClasses[$blockName]?>">
                        <div class="task-options-item task-options-item-destination">
                            <span data-bx-id="task-edit-chooser" data-target="originator" class="task-option-fixedbtn"></span>
                            <span class="task-options-item-param"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ORIGINATOR')?></span>
                            <div class="task-options-item-open-inner">

                                <div id="bx-component-scope-originator-<?=$templateId?>" class="task-options-destination-wrap user-item-set-empty-true">

                                    <span data-bx-id="user-item-set-items">
                                        <script type="text/html" data-bx-id="user-item-set-item">
                                            <span data-bx-id="user-item-set-item" data-item-value="{{VALUE}}" class="task-inline-selector-item task-inline-selector-item-{{USER_TYPE}} {{ITEM_SET_INVISIBLE}}">
                                                <span class="task-options-destination task-options-destination-all-users">
                                                    <a href="<?=htmlspecialcharsbx($userProfileUrlTemplate)?>" target="_blank" class="task-options-destination-text">{{DISPLAY}}</a><span class="task-option-inp-del" data-bx-id="user-item-set-item-delete" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DELETE')?>"></span>
                                                    <?$disabled = $taskCan['EDIT.ORIGINATOR'] ? '' : 'disabled="disabled"';?>
	                                                <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][ID]" value="{{VALUE}}" <?=$disabled?> />
	                                                <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][EMAIL]" value="{{EMAIL}}" <?=$disabled?> />
	                                                <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][NAME]" value="{{NAME}}" <?=$disabled?> />
	                                                <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][LAST_NAME]" value="{{LAST_NAME}}" <?=$disabled?> />
                                                </span>
                                            </span>
                                        </script>
                                    </span>

                                    <span class="task-inline-selector-item fixed-width">
                                        <input data-bx-id="user-item-set-search network-selector-search" type="text" value="" autocomplete="off" class="task-options-destination-inp">
                                        <span class="task-options-destination-loader"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_LOADING')?>...</span>
                                        <a href="javascript:void(0)" data-bx-id="user-item-set-open-form" class="feed-add-destination-link"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_CHANGE')?></a>
                                    </span>

                                </div>

                            </div>
                        </div>

                    </div>
                    <?$blockName = Manager\Task::SE_PREFIX.'AUDITOR';?>
                    <div data-bx-id="task-edit-auditor" data-block-name="<?=$blockName?>" class="pinable-block task-openable-block <?=$blockClasses[$blockName]?>">

                        <div class="task-options-item task-options-item-destination">
                            <span data-bx-id="task-edit-chooser" data-target="auditor" class="task-option-fixedbtn"></span>
                            <span class="task-options-item-param"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_AUDITORS')?></span>
                            <div class="task-options-item-open-inner">

                                <div id="bx-component-scope-auditor-<?=$templateId?>" class="task-options-destination-wrap user-item-set-empty-true">

                                    <span data-bx-id="user-item-set-items">
                                        <script type="text/html" data-bx-id="user-item-set-item">
                                            <span data-bx-id="user-item-set-item" data-item-value="{{VALUE}}" class="task-inline-selector-item task-inline-selector-item-{{USER_TYPE}} {{ITEM_SET_INVISIBLE}}">
                                                <span class="task-options-destination task-options-destination-all-users">
                                                    <a href="<?=htmlspecialcharsbx($userProfileUrlTemplate)?>" target="_blank" class="task-options-destination-text">{{DISPLAY}}</a><span class="task-option-inp-del" data-bx-id="user-item-set-item-delete" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DELETE')?>"></span>
                                                    <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][{{VALUE}}][ID]" value="{{VALUE}}">
	                                                <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][{{VALUE}}][EMAIL]" value="{{EMAIL}}">
	                                                <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][{{VALUE}}][NAME]" value="{{NAME}}">
	                                                <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][{{VALUE}}][LAST_NAME]" value="{{LAST_NAME}}">
                                                </span>
                                            </span>
                                        </script>
                                    </span>

                                    <span class="task-inline-selector-item fixed-width">
                                        <input data-bx-id="user-item-set-search network-selector-search" type="text" value="" autocomplete="off" class="task-options-destination-inp">
                                        <span class="task-options-destination-loader"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_LOADING')?>...</span>

                                        <a href="javascript:void(0)" data-bx-id="user-item-set-open-form" class="feed-add-destination-link">
                                            <span class="user-item-set-empty-block-off">+ <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD')?></span>
                                            <span class="user-item-set-empty-block-on">+ <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD_MORE')?></span>
                                        </a>
                                    </span>

                                    <?// in case of all items removed, the field should be sent anyway?>
                                    <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][]" value="">
                                </div>

                            </div>
                        </div>

                    </div>
                    <?$blockName = Manager\Task::SE_PREFIX.'ACCOMPLICE';?>
                    <div data-bx-id="task-edit-accomplice" data-block-name="<?=$blockName?>" class="pinable-block task-openable-block <?=$blockClasses[$blockName]?>">

                        <div class="task-options-item task-options-item-destination">
                            <span data-bx-id="task-edit-chooser" data-target="accomplice" class="task-option-fixedbtn"></span>
                            <span class="task-options-item-param"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ACCOMPLICES')?></span>
                            <div class="task-options-item-open-inner">

                                <div id="bx-component-scope-accomplice-<?=$templateId?>" class="task-options-destination-wrap user-item-set-empty-true">

                                    <span data-bx-id="user-item-set-items">
                                        <script type="text/html" data-bx-id="user-item-set-item">
                                            <span data-bx-id="user-item-set-item" data-item-value="{{VALUE}}" class="task-inline-selector-item task-inline-selector-item-{{USER_TYPE}} {{ITEM_SET_INVISIBLE}}">
                                                <span class="task-options-destination task-options-destination-all-users">
                                                    <a href="<?=htmlspecialcharsbx($userProfileUrlTemplate)?>" target="_blank" class="task-options-destination-text">{{DISPLAY}}</a><span class="task-option-inp-del" data-bx-id="user-item-set-item-delete" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DELETE')?>"></span>
                                                    <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][{{VALUE}}][ID]" value="{{VALUE}}">
	                                                <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][{{VALUE}}][EMAIL]" value="{{EMAIL}}">
	                                                <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][{{VALUE}}][NAME]" value="{{NAME}}">
	                                                <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][{{VALUE}}][LAST_NAME]" value="{{LAST_NAME}}">
                                                </span>
                                            </span>
                                        </script>
                                    </span>

                                    <span class="task-inline-selector-item fixed-width">
                                        <input data-bx-id="user-item-set-search network-selector-search" type="text" value="" autocomplete="off" class="task-options-destination-inp">
                                        <span class="task-options-destination-loader"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_LOADING')?>...</span>

                                        <a href="javascript:void(0)" data-bx-id="user-item-set-open-form" class="feed-add-destination-link">
                                            <span class="user-item-set-empty-block-off">+ <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD')?></span>
                                            <span class="user-item-set-empty-block-on">+ <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD_MORE')?></span>
                                        </a>
                                    </span>

                                    <?// in case of all items removed, the field should be sent anyway?>
                                    <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][]" value="">
                                </div>

                            </div>
                        </div>

                    </div>

                </div>

                <div>
	                <?$disabled = $taskCan['EDIT.PLAN'] ? '' : 'disabled="disabled"';?>
                    <div data-bx-id="task-edit-date-plan-manager" class="mode-unit-selected-<?=htmlspecialcharsbx($taskData['DURATION_TYPE'])?> task-options-item task-options-item-open">
                        <span class="task-options-item-param"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DEADLINE')?></span>
							<div class="task-options-item-more">
								<span class="task-options-destination-wrap date">
									<span data-bx-id="dateplanmanager-deadline" class="task-options-inp-container task-options-date">
										<input data-bx-id="datepicker-display" type="text" class="task-options-inp" value="" readonly="readonly">
										<span data-bx-id="datepicker-clear" class="task-option-inp-del"></span>
										<input data-bx-id="datepicker-value" type="hidden" name="<?=$inputPrefix?>[DEADLINE]" value="<?=htmlspecialcharsbx($taskData['DEADLINE'])?>" <?=$disabled?> />
									</span>
								</span>
								<span class="task-dashed-link task-dashed-link-terms task-dashed-link-add">
									<span class="task-dashed-link-inner" data-bx-id="task-edit-toggler" data-target="date-plan"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DATE_PLAN')?></span>
									<span class="task-dashed-link-inner" data-bx-id="task-edit-toggler" data-target="options"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ATTRIBUTES')?></span>
								</span>
							</div>
							<div class="task-options-item-open-inner task-options-item-open-inner-sh task-options-item-open-inner-sett">
                            <?$blockName = 'DATE_PLAN';?>
                            <div data-bx-id="task-edit-date-plan" data-block-name="<?=$blockName?>" class="pinable-block task-openable-block <?=$blockClasses[$blockName]?>">
                                <div class="task-options-sheduling-block">
                                    <div class="task-options-divider"></div>
                                    <div class="task-options-field task-options-field-left">
                                        <label for="" class="task-field-label task-field-label-br"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_START_FROM')?></label>
                                        <span data-bx-id="dateplanmanager-start-date-plan" class="task-options-inp-container task-options-date">
                                            <input data-bx-id="datepicker-display" type="text" class="task-options-inp" value="" readonly="readonly">
                                            <span data-bx-id="datepicker-clear" class="task-option-inp-del"></span>
                                            <input data-bx-id="datepicker-value" type="hidden" name="<?=$inputPrefix?>[START_DATE_PLAN]" value="<?=htmlspecialcharsbx($taskData['START_DATE_PLAN'])?>" <?=$disabled?> />
                                        </span>
                                    </div>
                                    <div class="task-options-field task-options-field-left task-options-field-duration">
                                        <label for="" class="task-field-label task-field-label-br"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DURATION')?></label>
                                        <span class="task-options-inp-container">
                                            <input data-bx-id="dateplanmanager-duration" type="text" class="task-options-inp" value="">
                                        </span>
                                        <span class="task-dashed-link">
                                            <span data-bx-id="dateplanmanager-unit-setter" data-unit="<?=CTasks::TIME_UNIT_TYPE_DAY?>" class="task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_OF_DAYS')?></span><span data-bx-id="dateplanmanager-unit-setter" data-unit="<?=CTasks::TIME_UNIT_TYPE_HOUR?>" class="task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_OF_HOURS')?></span><span data-bx-id="dateplanmanager-unit-setter" data-unit="<?=CTasks::TIME_UNIT_TYPE_MINUTE?>" class="task-dashed-link-inner"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_OF_MINUTES')?></span>
                                            <input data-bx-id="dateplanmanager-duration-type-value" type="hidden" name="<?=$inputPrefix?>[DURATION_TYPE]" value="<?=htmlspecialcharsbx($taskData['DURATION_TYPE'])?>" <?=$disabled?> />
                                        </span>
                                    </div>
                                    <div class="task-options-field task-options-field-left">
                                        <label for="" class="task-field-label task-field-label-br"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_END_WITH')?></label>
                                        <span data-bx-id="dateplanmanager-end-date-plan" class="task-options-inp-container task-options-date">
                                            <input data-bx-id="datepicker-display" type="text" class="task-options-inp" value="" readonly="readonly">
                                            <span data-bx-id="datepicker-clear" class="task-option-inp-del"></span>
                                            <input data-bx-id="datepicker-value" type="hidden" name="<?=$inputPrefix?>[END_DATE_PLAN]" value="<?=htmlspecialcharsbx($taskData['END_DATE_PLAN'])?>" <?=$disabled?> />
                                        </span>
                                    </div>
                                    <span data-bx-id="task-edit-chooser" data-target="date-plan" class="task-option-fixedbtn"></span>
                                </div>
                            </div>

                            <?$blockName = 'OPTIONS';?>
                            <div data-bx-id="task-edit-options" data-block-name="<?=$blockName?>" class="pinable-block task-openable-block <?=$blockClasses[$blockName]?>">
                                <div class="task-options-settings-block">
                                    <div class="task-options-divider"></div>
                                    <div class="task-options-field-container">
                                        <?$checked = $taskData['ALLOW_CHANGE_DEADLINE'] == 'Y';?>
                                        <div class="task-options-field">
                                            <div class="task-options-field-inner">
                                                <span class="js-bx-id-hint-help task-options-help tasks-icon-help"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_HINT_ALLOW_CHANGE_DEADLINE')?></span>
                                                <label class="task-field-label"><input data-bx-id="task-edit-flag" data-target="allow-change-deadline" data-flag-name="ALLOW_CHANGE_DEADLINE" <?=($checked? 'checked' : '')?> class="task-field-checkbox" type="checkbox"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ALLOW_CHANGE_DEADLINE')?></label>
                                                <input data-bx-id="task-edit-allow-change-deadline" type="hidden" name="<?=$inputPrefix?>[ALLOW_CHANGE_DEADLINE]" value="<?=($checked ? 'Y' : 'N')?>" />
                                            </div>
                                        </div>
                                        <?$checked = $taskData['MATCH_WORK_TIME'] == 'Y';?>
                                        <div class="task-options-field">
                                            <div class="task-options-field-inner">
                                                <span class="js-bx-id-hint-help task-options-help tasks-icon-help"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_HINT_MATCH_WORK_TIME')?></span>
                                                <label class="task-field-label"><input data-bx-id="task-edit-flag task-edit-flag-worktime" data-target="match-work-time" data-flag-name="MATCH_WORK_TIME" class="task-field-checkbox" type="checkbox" <?=($checked? 'checked' : '')?>><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_MATCH_WORK_TIME')?></label>
                                                <input data-bx-id="task-edit-match-work-time" type="hidden" name="<?=$inputPrefix?>[MATCH_WORK_TIME]" value="<?=($checked ? 'Y' : 'N')?>" />

                                                <?if(!$arResult['AUX_DATA']['USER']['IS_EXTRANET_USER'] && $arResult['COMPONENT_DATA']['MODULES']['bitrix24']):?>
                                                    <a href="/settings/configs.php" target="_blank"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_CUSTOMIZE')?></a>
                                                <?endif?>
                                            </div>
                                        </div>
                                        <?$checked = $taskData['TASK_CONTROL'] == 'Y';?>
                                        <div class="task-options-field">
                                            <div class="task-options-field-inner">
                                                <span class="js-bx-id-hint-help task-options-help tasks-icon-help"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_HINT_ALLOW_TASK_CONTROL')?></span>
                                                <label class="task-field-label"><input data-bx-id="task-edit-flag" data-target="task-control" data-flag-name="TASK_CONTROL" class="task-field-checkbox" type="checkbox" <?=($checked? 'checked' : '')?>><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_TASK_CONTROL')?></label>
                                                <input data-bx-id="task-edit-task-control" type="hidden" name="<?=$inputPrefix?>[TASK_CONTROL]" value="<?=($checked ? 'Y' : 'N')?>" />
                                            </div>
                                        </div>

                                        <?if(!$editMode):?>

                                            <div class="task-options-field">
                                                <div class="task-options-field-inner">
                                                    <span class="js-bx-id-hint-help task-options-help tasks-icon-help"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_HINT_ADD_TO_FAVORITE')?></span>
                                                    <label class="task-field-label"><input class="task-field-checkbox" type="checkbox" name="<?=$inputPrefix?>[ADD_TO_FAVORITE]" value="Y" <?=($taskData['ADD_TO_FAVORITE'] == 'Y' ? 'checked' : '')?>><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD_TO_FAVORITE')?></label>
                                                </div>
                                            </div>

	                                        <?if($taskCan['DAYPLAN.ADD']):?>

	                                            <div class="task-options-field">
	                                                <div class="task-options-field-inner">
	                                                    <span class="js-bx-id-hint-help task-options-help tasks-icon-help"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_HINT_ADD_TO_TIMEMAN')?></span>
	                                                    <label data-bx-id="task-edit-option-add2timeman-label" class="task-field-label"><input data-bx-id="task-edit-option-add2timeman" class="task-field-checkbox" type="checkbox" name="<?=$inputPrefix?>[ADD_TO_TIMEMAN]" value="Y" <?=($taskData['ADD_TO_TIMEMAN'] == 'Y' ? 'checked' : '')?>><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD_TO_TIMEMAN')?></label>
	                                                </div>
	                                            </div>

		                                    <?endif?>

                                        <?endif?>
                                    </div>
                                    <span data-bx-id="task-edit-chooser" data-target="options" class="task-option-fixedbtn"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

				<div data-bx-id="task-edit-chosen-blocks" class="pinned">

                    <?foreach($arResult['TEMPLATE_DATA']['ADDITIONAL_BLOCKS'] as $blockName):?>

                        <?
						ob_start();
                        $blockNameJs = ToLower(str_replace('_', '-', $blockName));

						$itemOpenClass = "";
						$openClassBlocks = array(
							Manager\Task::SE_PREFIX.'PROJECTDEPENDENCE',
							Manager\Task::SE_PREFIX.'TEMPLATE',
							'USER_FIELDS'
						);

						if (in_array($blockName, $openClassBlocks))
						{
							$itemOpenClass = " task-options-item-open";
						}
						?>

                        <div data-bx-id="task-edit-<?=$blockNameJs?>-block" data-block-name="<?=$blockName?>" class="pinable-block task-options-item task-options-item-<?=$blockNameJs?><?=$itemOpenClass?>">

                            <span data-bx-id="task-edit-chooser" data-target="<?=$blockNameJs?>-block" class="task-option-fixedbtn" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_PINNER_HINT')?>"></span>
                            <span class="task-options-item-param"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_BLOCK_TITLE_'.$blockName)?></span>

                            <?if($blockName == Manager\Task::SE_PREFIX.'PROJECT'):?>

                                <span id="bx-component-scope-project-<?=$templateId?>" class="task-options-item-project group-item-set-empty-true">

	                                <input data-bx-id="task-edit-project-input" type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][ID]" value="">

                                    <span class="task-options-destination-wrap">

                                        <span data-bx-id="group-item-set-items">
                                            <script type="text/html" data-bx-id="group-item-set-item">
                                                <span data-bx-id="group-item-set-item" data-item-value="{{VALUE}}" class="task-inline-selector-item {{ITEM_SET_INVISIBLE}}">
                                                    <span class="task-options-destination task-options-destination-all-users">
                                                        <a href="<?=htmlspecialcharsbx(str_replace('#group_id#', '{{VALUE}}', $arParams['PATH_TO_GROUP']))?>" target="_blank" class="task-options-destination-text">{{DISPLAY}}</a><span class="task-option-inp-del" data-bx-id="group-item-set-item-delete" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DELETE')?>"></span>
                                                    </span>
                                                </span>
                                            </script>
                                        </span>

                                        <span class="task-inline-selector-item fixed-width">
                                            <input data-bx-id="group-item-set-search network-selector-search" type="text" value="" autocomplete="off" class="task-options-destination-inp">
                                            <span class="task-options-destination-loader"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_LOADING')?>...</span>
                                            <a href="javascript:void(0)" data-bx-id="group-item-set-open-form" class="feed-add-destination-link">
                                                <span class="group-item-set-empty-block-off">+ <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD')?></span>
                                                <span class="group-item-set-empty-block-on"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_CHANGE')?></span>
                                            </a>
                                        </span>

                                    </span>

                                </span>

                            <?elseif($blockName == 'TIMEMAN'):?>

	                            <?$checked = $taskData['ALLOW_TIME_TRACKING'] == 'Y';?>
                                <label class="task-field-label"><input data-bx-id="task-edit-flag task-edit-flag-timeman" data-target="allow-time-tracking" data-flag-name="ALLOW_TIME_TRACKING" <?=($checked? 'checked' : '')?> class="task-options-checkbox" type="checkbox"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_TIME_TO_DO')?></label>
                                <input data-bx-id="task-edit-allow-time-tracking" type="hidden" name="<?=$inputPrefix?>[ALLOW_TIME_TRACKING]" value="<?=($checked ? 'Y' : 'N')?>" />
                                <span data-bx-id="task-edit-timeman-estimate-time" class="task-options-inp-container-time task-openable-block<?if(!$checked):?> invisible<?endif?>">
                                    <span class="task-options-inp-container">
                                        <input data-bx-id="task-edit-estimate-time task-edit-estimate-time-hour" type="text" class="task-options-inp" value="">
                                    </span>
                                    <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_OF_HOURS')?>
                                    <span class="task-options-inp-container">
                                        <input data-bx-id="task-edit-estimate-time task-edit-estimate-time-minute" type="text" class="task-options-inp" value="">
                                    </span>
                                    <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_OF_MINUTES')?>
                                    <input data-bx-id="task-edit-estimate-time-second" type="hidden" name="<?=$inputPrefix?>[TIME_ESTIMATE]" value="<?=intval($taskData['TIME_ESTIMATE'])?>" />
                                </span>

                            <?elseif($blockName == Manager\Task::SE_PREFIX.'REMINDER'):?>

                                <div class="task-options-item-open-inner">
                                    <div class="task-options-reminder">
                                        <?$APPLICATION->IncludeComponent(
                                            'bitrix:tasks.task.detail.parts',
                                            'flat',
                                            array(
                                                'MODE' => 'VIEW TASK',
                                                'BLOCKS' => array('reminder'),
                                                'TEMPLATE_DATA' => array(
                                                    'ID' => 'reminder-'.$templateId,
                                                    'INPUT_PREFIX' => $inputPrefix.'['.$blockName.']',
                                                    'COMPANY_WORKTIME' => array(
                                                        'HOURS' => $arResult['AUX_DATA']['COMPANY_WORKTIME']['HOURS']
                                                    ),
                                                    'ENABLE_ADD_BUTTON' => 'Y',
                                                    'ITEM_FX' => 'horizontal'
                                                )
                                            ),
                                            false,
                                            array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
                                        );?>
                                    </div>
                                </div>

                            <?elseif($blockName == Manager\Task::SE_PREFIX.'TEMPLATE'):?>

                                <?
                                $template = $arResult['DATA']['TASK'][$blockName];
                                $linkToTemplate = str_replace(
                                    array('#action#', '#template_id#'),
                                    array('view', intval($template['ID'])),
                                    $arParams['PATH_TO_TEMPLATES_TEMPLATE']
                                );
                                $replicationOn = $taskData['REPLICATE'] == 'Y';
                                ?>

                                <div data-bx-id="task-edit-replication-block" class="task-options-item-open-inner <?=($replicationOn ? '' : 'mode-replication-off')?>">
                                    <label class="task-field-label task-field-label-repeat">
                                        <input data-bx-id="task-edit-flag task-edit-flag-replication" data-target="replication" class="task-options-checkbox" type="checkbox" <?=($taskData['REPLICATE'] == 'Y' ? 'checked' : '')?>><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_MAKE_REPLICABLE')?>
                                        <input data-bx-id="task-edit-replication" type="hidden" name="<?=$inputPrefix?>[REPLICATE]" value="<?=htmlspecialcharsbx($taskData['REPLICATE'])?>" />
                                    </label>
                                    <div data-bx-id="task-edit-replication-panel" class="task-options-repeat task-openable-block<?=($replicationOn ? '' : ' invisible')?>">
                                        <?$APPLICATION->IncludeComponent(
                                            'bitrix:tasks.task.detail.parts',
                                            'flat',
                                            array(
                                                'MODE' => 'VIEW TASK',
                                                'BLOCKS' => array('replication'),
                                                'TEMPLATE_DATA' => array(
                                                    'ID' => 'replication-'.$templateId,
                                                    'INPUT_PREFIX' => $inputPrefix.'['.$blockName.'][REPLICATE_PARAMS]',
                                                    'DATA' => $arResult['DATA']['TASK'][$blockName]['REPLICATE_PARAMS'],
	                                                'TEMPLATE' => $arResult['DATA']['TASK']['SE_TEMPLATE'],
                                                    'COMPANY_WORKTIME' => $arResult['AUX_DATA']['COMPANY_WORKTIME'],
	                                                'USER' => $arResult['DATA']['USER'][\Bitrix\Tasks\Util\User::getId()],
                                                )
                                            ),
                                            false,
                                            array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
                                        );?>
                                        <?if(intval($template['ID'])):?>
                                            <div class="task-options-field-fn task-options-field-norm">
                                                <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_TEMPLATE_CREATED')?> <a href="<?=htmlspecialcharsbx($linkToTemplate)?>" target="_blank"><?=htmlspecialcharsbx($template['TITLE'])?></a>
                                            </div>
                                        <?else:?>
                                            <div class="task-options-field-fn task-options-field-norm">
                                                <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_TEMPLATE_WILL_BE_CREATED');?>
                                            </div>
                                        <?endif?>
                                    </div>
                                    <?if($editMode && intval($template['ID'])):?>
                                        <div class="task-options-field-fn task-options-field-norm task-repeat-warning">
                                            <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_TEMPLATE_WILL_BE_DELETED');?> <a href="<?=htmlspecialcharsbx($linkToTemplate)?>" target="_blank"><?=htmlspecialcharsbx($template['TITLE'])?></a>
                                        </div>
                                    <?endif?>
                                </div>

                            <?elseif($blockName == Manager\Task::SE_PREFIX.'PROJECTDEPENDENCE'):?>

                                <div class="task-options-item-open-inner">
                                    <?$APPLICATION->IncludeComponent(
                                        'bitrix:tasks.task.detail.parts',
                                        'flat',
                                        array(
                                            'MODE' => 'VIEW TASK',
                                            'BLOCKS' => array('projectdependence'),
                                            'TEMPLATE_DATA' => array(
                                                'ID' => 'projectdependence-'.$templateId,
                                                'INPUT_PREFIX' => $inputPrefix.'['.$blockName.']',
                                                'PATH_TO_TASKS_TASK' => $arParams['PATH_TO_TASKS_TASK_ORIGINAL']
                                            )
                                        ),
                                        false,
                                        array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
                                    );?>
                                </div>

                            <?elseif($blockName == 'UF_CRM_TASK'):?>

                                <div class="task-options-item-open-inner task-edit-crm-block">

                                    <?
                                    $crmUf = $arResult['AUX_DATA']["USER_FIELDS"][$blockName];
                                    $crmUf['FIELD_NAME'] = $inputPrefix.'['.$blockName.']';

                                    $APPLICATION->IncludeComponent(
                                        $crmUf['EDIT_IN_LIST'] === 'Y' ? 'bitrix:system.field.edit' : 'bitrix:system.field.view',
                                        $crmUf["USER_TYPE"]["USER_TYPE_ID"],
                                        array(
                                            "bVarsFromForm" => false,
                                            "arUserField" => $crmUf,
                                            "form_name" => "task-form",
                                            'SHOW_FILE_PATH'    => false,
                                            'FILE_URL_TEMPLATE' => '/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=#file_id#'
                                        ), null, array("HIDE_ICONS" => "Y")
                                    );?>
                                </div>

                            <?elseif($blockName == Manager\Task\ParentTask::getCode(true)):?>

                                <div class="task-options-item-open-inner">

                                    <span id="bx-component-scope-parenttask-<?=$templateId?>" class="task-options-destination-wrap task-item-set-empty-true">

	                                    <input data-bx-id="task-edit-parent-input" type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][ID]" value="">

                                        <span data-bx-id="task-item-set-items">
                                            <script type="text/html" data-bx-id="task-item-set-item">
                                                <span data-bx-id="task-item-set-item" data-item-value="{{VALUE}}" class="task-inline-selector-item {{ITEM_SET_INVISIBLE}}">
                                                    <span class="task-options-destination task-options-destination-all-users">
                                                        <a href="<?=htmlspecialcharsbx($taskUrlTemplate)?>" target="_blank" class="task-options-destination-text">{{DISPLAY}}</a><span data-bx-id="task-item-set-item-delete" class="task-option-inp-del" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DELETE')?>"></span>
                                                    </span>
                                                </span>
                                            </script>
                                        </span>

                                        <span class="task-inline-selector-item">
                                            <a href="javascript:void(0)" data-bx-id="task-item-set-open-form" class="feed-add-destination-link">
                                                <span class="task-item-set-empty-block-off">+ <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD')?></span>
                                                <span class="task-item-set-empty-block-on"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_CHANGE')?></span>
                                            </a>
                                        </span>

                                        <div data-bx-id="task-item-set-picker-content" class="hidden-soft">
                                            <?$APPLICATION->IncludeComponent(
                                                "bitrix:tasks.task.selector", ".default", array(
                                                "MULTIPLE" => "N",
                                                "NAME" => "parenttask",
                                                "VALUE" => $taskData["PARENT_ID"],
                                                "PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK_ORIGINAL"],
                                                "SITE_ID" => SITE_ID
                                            ), null, array("HIDE_ICONS" => "Y")
                                            );?>
                                        </div>
                                    </span>

                                </div>

                            <?elseif($blockName == Manager\Task::SE_PREFIX.'TAG'):?>

                                <div class="task-options-item-open-inner">

                                    <span id="bx-component-scope-tag-<?=$templateId?>" class="task-options-destination-wrap tag-item-set-empty-true">

                                        <span data-bx-id="tag-item-set-items">
                                            <script type="text/html" data-bx-id="tag-item-set-item">
                                                <span data-bx-id="tag-item-set-item" data-item-value="{{VALUE}}" class="task-inline-selector-item {{ITEM_SET_INVISIBLE}}">
                                                    <span class="task-options-destination task-options-destination-all-users">
                                                        <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][{{VALUE}}][NAME]" value="{{DISPLAY}}"><span class="task-options-destination-text">{{DISPLAY}}</span><span data-bx-id="tag-item-set-item-delete" class="task-option-inp-del" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DELETE')?>"></span>
                                                    </span>
                                                </span>
                                            </script>
                                        </span>
										<span class="task-inline-selector-item">
											<a href="javascript:void(0)" data-bx-id="tag-item-set-open-form" class="feed-add-destination-link">
												<span class="tag-item-set-empty-block-off">+ <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD')?></span>
												<span class="tag-item-set-empty-block-on">+ <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD_MORE')?></span>
											</a>
										</span>
                                        <?$APPLICATION->IncludeComponent(
	                                        "bitrix:tasks.tags.selector",
	                                        ".default",
	                                        array(
		                                        "NAME" => "TAGS",
		                                        "VALUE" => $arResult['TEMPLATE_DATA']['TAG_STRING'],
		                                        "SILENT" => 'Y'
	                                        ),
	                                        null,
	                                        array("HIDE_ICONS" => "Y")
                                        );?>

	                                    <?// in case of all items removed, the field should be sent anyway?>
	                                    <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][]" value="">

                                    </span>

                                </div>

                            <?elseif($blockName == 'USER_FIELDS'):?>

                                <div class="task-options-item-open-inner">

                                    <?foreach($arResult['AUX_DATA'][$blockName] as $uf):?>
                                        <?
                                        if (
                                            $uf['FIELD_NAME_ORIG'] === \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode() || $uf['FIELD_NAME_ORIG'] === 'UF_CRM_TASK' ||
                                            $uf['USER_TYPE_ID'] === 'file' /*file is a deprecated type*/
                                        )
                                        {
                                            continue;
                                        }
                                        ?>
                                        <div class="task-property-name"><?=htmlspecialcharsbx((string) $uf['EDIT_FORM_LABEL'] != '' ? $uf['EDIT_FORM_LABEL'] : $uf['FIELD_NAME_ORIG'])?>:</div>
                                        <div class="task-property-value">
                                            <?$APPLICATION->IncludeComponent(
                                                "bitrix:system.field.edit",
                                                $uf["USER_TYPE"]["USER_TYPE_ID"],
                                                array(
                                                    "bVarsFromForm" => false,
                                                    "arUserField" => $uf,
                                                    "form_name" => "task-form",
                                                    'SHOW_FILE_PATH'    => false,
                                                    'FILE_URL_TEMPLATE' => '/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=#file_id#'
                                                ), null, array("HIDE_ICONS" => "Y")
                                            );?>
                                        </div>
                                    <?endforeach?>

                                </div>

                            <?elseif($blockName == Manager\Task::SE_PREFIX.'RELATEDTASK'):?>

                                <div class="task-options-item-open-inner">

                                    <span id="bx-component-scope-dependson-<?=$templateId?>" class="task-options-destination-wrap task-item-set-empty-true">

                                        <span data-bx-id="task-item-set-items">
                                            <script type="text/html" data-bx-id="task-item-set-item">
                                                <span data-bx-id="task-item-set-item" data-item-value="{{VALUE}}" class="task-inline-selector-item {{ITEM_SET_INVISIBLE}}">
                                                    <span class="task-options-destination task-options-destination-all-users">
                                                        <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][{{VALUE}}][ID]" value="{{VALUE}}"><a href="<?=htmlspecialcharsbx($taskUrlTemplate)?>" target="_blank" class="task-options-destination-text">{{DISPLAY}}</a><span data-bx-id="task-item-set-item-delete" class="task-option-inp-del" title="<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_DELETE')?>"></span>
                                                    </span>
                                                </span>
                                            </script>
                                        </span>

                                        <span class="task-inline-selector-item">
                                            <a href="javascript:void(0)" data-bx-id="task-item-set-open-form" class="feed-add-destination-link">
                                                <span class="task-item-set-empty-block-off">+ <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD')?></span>
                                                <span class="task-item-set-empty-block-on">+ <?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD_MORE')?></span>
                                            </a>
                                        </span>

                                        <div data-bx-id="task-item-set-picker-content" class="hidden-soft">
                                            <?$APPLICATION->IncludeComponent(
                                                "bitrix:tasks.task.selector", ".default", array(
                                                "MULTIPLE" => "Y",
                                                "NAME" => "dependson",
                                                "VALUE" => $taskData["DEPENDS_ON"],
                                                "PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK_ORIGINAL"],
                                                "SITE_ID" => SITE_ID
                                            ), null, array("HIDE_ICONS" => "Y")
                                            );?>
                                        </div>

	                                    <?// in case of all items removed, the field should be sent anyway?>
	                                    <input type="hidden" name="<?=$inputPrefix?>[<?=$blockName?>][]" value="">
                                    </span>

                                </div>

                            <?endif?>

                        </div>

                        <?
                        $blocks[$blockName] = ob_get_contents();
                        ob_end_clean();
                        ?>

                    <?endforeach?>

					<?foreach($arResult['COMPONENT_DATA']['STATE']['BLOCKS'] as $blockName => $block):?>
						<?if(array_key_exists(TasksTaskFormState::O_CHOSEN, $block) && isset($blocks[$blockName])):?>
							<div data-bx-id="task-edit-<?=ToLower(str_replace('_', '-', $blockName))?>-block-place" class="task-edit-block-place">
								<?if($block[TasksTaskFormState::O_CHOSEN]):?>
									<?=$blocks[$blockName]?>
								<?endif?>
							</div>
						<?endif?>
					<?endforeach?>

				</div>
			</div>

			<?$displayed = $arResult['TEMPLATE_DATA']['ADDITIONAL_DISPLAYED'];?>
			<?$opened = $arResult['TEMPLATE_DATA']['ADDITIONAL_OPENED'];?>
			<div data-bx-id="task-edit-additional" class="task-additional-block <?=($displayed ? '' : 'hidden')?>">

				<div data-bx-id="task-edit-additional-header" class="task-additional-alt <?=($opened ? 'opened' : '')?>">
					<div class="task-additional-alt-more">
						<?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADDITIONAL_OPEN')?>
					</div>
					<div class="task-additional-alt-promo">
						<?foreach($arResult['COMPONENT_DATA']['STATE']['BLOCKS'] as $blockName => $block):?>
							<?$label = Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_BLOCK_HEADER_'.$blockName);?>
							<?if((string) $label != ''):?>
								<span class="task-additional-alt-promo-text"><?=htmlspecialcharsbx($label);?></span>
							<?endif?>
						<?endforeach?>
					</div>
				</div>

				<div data-bx-id="task-edit-unchosen-blocks" class="task-options task-options-more task-openable-block <?=($opened ? '' : 'invisible')?>">

					<?foreach($arResult['COMPONENT_DATA']['STATE']['BLOCKS'] as $blockName => $block):?>
						<?if(array_key_exists(TasksTaskFormState::O_CHOSEN, $block) && isset($blocks[$blockName])):?>
							<div data-bx-id="task-edit-<?=ToLower(str_replace('_', '-', $blockName))?>-block-place" class="task-edit-block-place">
								<?if(!$block[TasksTaskFormState::O_CHOSEN]):?>
									<?=$blocks[$blockName]?>
								<?endif?>
							</div>
						<?endif?>
					<?endforeach?>

				</div>

			</div>

			<div data-bx-id="task-edit-footer" class="webform-buttons pinable-block <?=($arResult['TEMPLATE_DATA']['FOOTER_PINNED'] ? 'pinned' : '')?>">

                <div class="tasks-form-footer-container">

                    <?if($arParams['ENABLE_FOOTER_UNPIN']):?>
                        <span data-bx-id="task-edit-pin-footer" class="task-option-fixedbtn"></span>
                    <?endif?>

                    <?/*<span class="task-option-save"></span>*/?>

                    <?if(intval($taskData['ID'])):?>
                        <button class="webform-small-button webform-small-button-accept">
                            <span class="webform-small-button-text"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_SAVE_TASK')?></span>
                        </button>
                    <?else:?>
                        <button class="webform-small-button webform-small-button-accept">
                            <span class="webform-small-button-text"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD_TASK')?> <span>(<span data-bx-id="task-edit-cmd">Ctrl</span>+Enter)</span></span>
                        </button>
                        <button data-bx-id="task-edit-save-n-open" name="STAY_AT_PAGE" value="1" class="webform-small-button webform-small-button-transparent">
                            <span class="webform-small-button-text"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_ADD_TASK_AND_OPEN_AGAIN')?></span>
                        </button>
                    <?endif?>

                    <a data-bx-id="task-edit-cancel-button" href="<?=htmlspecialcharsbx($arResult['TEMPLATE_DATA']['CANCELURL'])?>" class="webform-button-link"><?=Loc::getMessage('TASKS_TASK_COMPONENT_TEMPLATE_CANCEL')?></a>

                </div>
            </div>

			<input type="hidden" name="ACTION[1][OPERATION]" value="this.setstate" />
			<div data-bx-id="task-edit-state">
				<script data-bx-id="task-edit-state-block" type="text/html">
					<input type="hidden" name="ACTION[1][ARGUMENTS][state][BLOCKS][{{NAME}}][{{TYPE}}]" value="{{VALUE}}" />
				</script>
				<script data-bx-id="task-edit-state-flag" type="text/html">
					<input type="hidden" name="ACTION[1][ARGUMENTS][state][FLAGS][{{NAME}}]" value="{{VALUE}}" />
				</script>
			</div>

			</form>
        <?endif?>
	</div>

	<script>

        var options = <?=CUtil::PhpToJSObject(array(
            'id' => $arResult['TEMPLATE_DATA']['ID'],

            // be careful here, do not "publish" entire data without filtering
            'data' => array(
                'TASK' => $arResult['DATA']['TASK'],
                'EVENT_TASK' => $arResult['DATA']['EVENT_TASK']
            ),
            'can' => array('TASK' => $arResult['CAN']['TASK']),
            'template' => $arResult['TEMPLATE_DATA'],
            'state' => $state,
            'componentData' => array(
                'EVENT_TYPE' => $arResult['COMPONENT_DATA']['EVENT_TYPE'],
                'EVENT_OPTIONS' => $arResult['COMPONENT_DATA']['EVENT_OPTIONS'],
	            'MODULES' => $arResult['COMPONENT_DATA']['MODULES'],
            ),
            'auxData' => array( // currently no more, no less
                'COMPANY_WORKTIME' => $arResult['AUX_DATA']['COMPANY_WORKTIME'],
	            'HINT_STATE' => $arResult['AUX_DATA']['HINT_STATE'],
	            'USER' => $arResult['AUX_DATA']['USER']
            ),
	        'doInit' => !$arResult['TEMPLATE_DATA']['SHOW_SUCCESS_MESSAGE'],
	        'cancelActionIsEvent' => !!$arParams['CANCEL_ACTION_IS_EVENT'],
        ), false, false, true)?>;

        <?/*
        todo: move php function tasksRenderJSON() to javascript, use CUtil::PhpToJSObject() here for EVENT_TASK, and then remove the following code
        */?>
        <?if(Type::isIterable($arResult['DATA']['EVENT_TASK'])):?>
            <?CJSCore::Init('CJSTask'); // ONLY to make BX.CJSTask.fixWin() available?>
            options.data.EVENT_TASK_UGLY = <?tasksRenderJSON(
                $arResult['DATA']['EVENT_TASK_SAFE'],
                intval($arResult['DATA']['EVENT_TASK']['CHILDREN_COUNT']),
                array(
                    'PATH_TO_TASKS_TASK' => $arParams['PATH_TO_TASKS_TASK_ORIGINAL']
                ),
                true,
                true,
                true,
                CSite::GetNameFormat(false)
            )?>;
        <?endif?>

		new BX.Tasks.Component.Task(options);
	</script>

<?endif?>
