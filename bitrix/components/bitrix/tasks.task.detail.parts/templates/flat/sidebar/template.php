<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CBitrixComponent $component */

$templateData = $arParams["TEMPLATE_DATA"];
$taskData = $templateData["DATA"]["TASK"];
$can = $templateData["DATA"]["TASK"]["ACTION"];
$workingTime = $templateData["AUX_DATA"]["COMPANY_WORKTIME"];
?>

<div class="task-detail-sidebar">

	<div class="task-detail-sidebar-content">
		<div class="task-detail-sidebar-status">
			<span id="task-detail-status-name" class="task-detail-sidebar-status-text"><?=Loc::getMessage("TASKS_STATUS_".$taskData["REAL_STATUS"])?></span>
			<span id="task-detail-status-date" class="task-detail-sidebar-status-date"><?
				if ($taskData["REAL_STATUS"] != 4 && $taskData["REAL_STATUS"] != 5)
				{
					echo Loc::getMessage("TASKS_SIDEBAR_START_DATE")." ";
				}

				echo $templateData["STATUS_CHANGED_DATE"];

			?></span>
		</div>

		<? if ($can["EDIT"] || $can["EDIT.PLAN"] || $templateData["DEADLINE"]): ?>
		<div class="task-detail-sidebar-item task-detail-sidebar-item-deadline">
			<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_QUICK_DEADLINE")?>:</div>
			<div class="task-detail-sidebar-item-value"><?
				if ($can["EDIT"] || $can["EDIT.PLAN"]):
					?><span id="task-detail-deadline"><?=($templateData["DEADLINE"] ? $templateData["DEADLINE"] : Loc::getMessage("TASKS_SIDEBAR_DEADLINE_NO"))?></span><?
					?><span id="task-detail-deadline-clear" class="task-detail-sidebar-item-value-del"<?if (!$templateData["DEADLINE"]):?> style="display: none"<?endif?>></span><?
				else:
					echo $templateData["DEADLINE"];
				endif ?>
			</div>
			<? if ($taskData["STATUS"] == CTasks::METASTATE_EXPIRED):?>
			<div class="task-detail-sidebar-item-delay">
				<div class="task-detail-sidebar-item-delay-message">
					<span class="task-detail-sidebar-item-delay-message-icon"></span>
					<span class="task-detail-sidebar-item-delay-message-text"><?=Loc::getMessage("TASKS_SIDEBAR_TASK_OVERDUE")?></span>
				</div>
			</div>
			<? endif ?>
		</div>
		<? endif ?>

		<div class="task-detail-sidebar-item task-detail-sidebar-item-reminder">
			<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_SIDEBAR_REMINDER")?>:</div>
			<div class="task-detail-sidebar-item-value"><span id="task-detail-reminder-add"><?=Loc::getMessage("TASKS_REMINDER_TITLE")?></span></div>
			<?$APPLICATION->IncludeComponent(
				"bitrix:tasks.task.detail.parts",
				"flat",
				array(
					"MODE" => "VIEW TASK",
					"BLOCKS" => array("reminder"),
					"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
					"TEMPLATE_DATA" => array(
						"ITEMS" => array(
							"DATA" => $taskData["SE_REMINDER"],
							"CAN" => $arResult["CAN"]["TASK"]["SE_REMINDER"]
						),
						"TASK_ID" => $taskData["ID"],
						"TASK_DEADLINE" => $taskData["DEADLINE"],
						"AUTO_SYNC" => true,
						"COMPANY_WORKTIME" => array(
							"HOURS" => $arResult["TEMPLATE_DATA"]["AUX_DATA"]["COMPANY_WORKTIME"]["HOURS"]
						)
					)
				),
				false,
				array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
			);?>
		</div>

		<div class="task-detail-sidebar-item">
			<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_SIDEBAR_CREATED_DATE")?>:</div>
			<div class="task-detail-sidebar-item-value"><?=$templateData["CREATED_DATE"]?></div>
		</div>

		<? if ($taskData["ALLOW_TIME_TRACKING"] === "Y"): ?>
			<div class="task-detail-sidebar-item">
				<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_SIDEBAR_TIME_SPENT_IN_LOGS")?>:</div>
				<div class="task-detail-sidebar-item-value" id="task-detail-spent-time-<?=$taskData["ID"]?>">
					<?=\Bitrix\Tasks\UI::formatTimeAmount($taskData["TIME_ELAPSED"]);?>
				</div>
			</div>

			<?if($taskData["TIME_ESTIMATE"] > 0):?>
				<div class="task-detail-sidebar-item">
					<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_SIDEBAR_TIME_ESTIMATE")?>:</div>
					<div class="task-detail-sidebar-item-value" id="task-detail-estimate-time-<?=$taskData["ID"]?>">
						<?=\Bitrix\Tasks\UI::formatTimeAmount($taskData["TIME_ESTIMATE"]);?>
					</div>
				</div>
			<?endif?>
		<?endif?>

		<? if ($templateData["START_DATE_PLAN"]): ?>
		<div class="task-detail-sidebar-item">
			<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_SIDEBAR_START")?>:</div>
			<div class="task-detail-sidebar-item-value"><?=$templateData["START_DATE_PLAN"]?></div>
		</div>
		<? endif ?>

		<? if ($templateData["END_DATE_PLAN"]): ?>
		<div class="task-detail-sidebar-item">
			<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_SIDEBAR_FINISH")?>:</div>
			<div class="task-detail-sidebar-item-value"><?=$templateData["END_DATE_PLAN"]?></div>
		</div>
		<? endif ?>

		<div class="task-detail-sidebar-item task-detail-sidebar-item-mark">
			<div class="task-detail-sidebar-item-title"><?=Loc::getMessage("TASKS_MARK")?>:</div>
			<div class="task-detail-sidebar-item-value<? if (!$can["EDIT"]):?> task-detail-sidebar-item-readonly<?endif?>"><?
				?><span class="task-detail-sidebar-item-mark-<?=strtolower($taskData["MARK"])?>" id="task-detail-mark"><?
				if ($taskData["MARK"])
				{
					echo Loc::getMessage("TASKS_MARK_".$taskData["MARK"]);
				}
				else
				{
					echo Loc::getMessage("TASKS_MARK_NONE");
				}
			?></span></div>
		</div>

		<?$APPLICATION->IncludeComponent(
			"bitrix:tasks.task.detail.parts",
			"flat",
			array(
				"MODE" => "VIEW TASK",
				"BLOCKS" => array("user-view"),
				"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
				"PATH_TO_TASKS" => $arParams["PATH_TO_TASKS"],
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
				"TEMPLATE_DATA" => array(
					"ROLE" => "ORIGINATOR",
					"ITEMS" => array(
						"DATA" => array(
							$taskData[\Bitrix\Tasks\Manager::SE_PREFIX."ORIGINATOR"]
						),
					),
					"MULTIPLE" => false
				)
			),
			false,
			array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
		);?>

		<?$APPLICATION->IncludeComponent(
			"bitrix:tasks.task.detail.parts",
			"flat",
			array(
				"MODE" => "VIEW TASK",
				"BLOCKS" => array("user-view"),
				"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
				"PATH_TO_TASKS" => $arParams["PATH_TO_TASKS"],
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
				"TEMPLATE_DATA" => array(
					"ROLE" => "RESPONSIBLE",
					"ITEMS" => array(
						"DATA" => $taskData[\Bitrix\Tasks\Manager::SE_PREFIX."RESPONSIBLE"]
					),
					"TASK_ID" => $taskData["ID"],
					"TASK_CAN_EDIT" => $can["EDIT"],
					"MULTIPLE" => false
				)
			),
			false,
			array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
		);?>

		<?$APPLICATION->IncludeComponent(
			"bitrix:tasks.task.detail.parts",
			"flat",
			array(
				"MODE" => "VIEW TASK",
				"BLOCKS" => array("user-view"),
				"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
				"PATH_TO_TASKS" => $arParams["PATH_TO_TASKS"],
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
				"TEMPLATE_DATA" => array(
					"ROLE" => "ACCOMPLICES",
					"ITEMS" => array(
						"DATA" => $taskData[\Bitrix\Tasks\Manager::SE_PREFIX."ACCOMPLICE"],
					),
					"TASK_ID" => $taskData["ID"],
					"TASK_CAN_EDIT" => $can["EDIT"],
					"MULTIPLE" => true,
				)
			),
			false,
			array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
		);?>

		<?$APPLICATION->IncludeComponent(
			"bitrix:tasks.task.detail.parts",
			"flat",
			array(
				"MODE" => "VIEW TASK",
				"BLOCKS" => array("user-view"),
				"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
				"PATH_TO_TASKS" => $arParams["PATH_TO_TASKS"],
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
				"TEMPLATE_DATA" => array(
					"ROLE" => "AUDITORS",
					"ITEMS" => array(
						"DATA" => $taskData[\Bitrix\Tasks\Manager::SE_PREFIX."AUDITOR"],
					),
					"TASK_ID" => $taskData["ID"],
					"TASK_CAN_EDIT" => $can["EDIT"],
					"MULTIPLE" => true,
					"USER" => $arResult["TEMPLATE_DATA"]["DATA"]["USER"][$GLOBALS["USER"]->GetId()],
					//"PATH_TO_TASKS_TASK"
				)
			),
			false,
			array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
		);?>

		<?if(!$arParams["PUBLIC_MODE"]):?>
			<?if($taskData["REPLICATE"] === "Y"):?>
				<div class="task-detail-sidebar-info-title"><?=Loc::getMessage("TASKS_SIDEBAR_REGULAR_TASK")?></div>
				<div class="task-detail-sidebar-info">
					<?if(!empty($taskData["SE_TEMPLATE"])):?>
						<?=Loc::getMessage("TASKS_SIDEBAR_TASK_REPEATS")?> <?=tasksPeriodToStr($taskData["SE_TEMPLATE"]["REPLICATE_PARAMS"])?><br />
						(<a href="<?=$arParams["TEMPLATE_DATA"]["PATH_TO_TEMPLATES_TEMPLATE"]?>" target="_top"><?=Loc::getMessage("TASKS_SIDEBAR_TEMPLATE")?></a>)
					<?else:?>
						<?=Loc::getMessage("TASKS_SIDEBAR_TEMPLATE_NOT_ACCESSIBLE")?>
					<?endif?>
				</div>
			<?endif?>
			<?if(!empty($taskData["SE_TEMPLATE.SOURCE"])):?>
				<div class="task-detail-sidebar-info template-source">
					<?=Loc::getMessage("TASKS_SIDEBAR_TASK_CREATED_BY_TEMPLATE")?><br />
					(<a href="<?=$arParams["TEMPLATE_DATA"]["PATH_TO_TEMPLATES_TEMPLATE_SOURCE"]?>" target="_top"><?=Loc::getMessage("TASKS_SIDEBAR_TEMPLATE")?></a>)
				</div>
			<?endif?>
		<?endif?>


		<? if (!$arParams["PUBLIC_MODE"] && ($can["EDIT"] || $arParams["TEMPLATE_DATA"]["TAGS"] !== "")):?>
		<div class="task-detail-sidebar-info-title"><?=Loc::getMessage("TASKS_TASK_TAGS")?></div>
		<div class="task-detail-sidebar-info">
			<div class="task-detail-sidebar-info-tag"><?
				if ($can["EDIT"])
				{
					$APPLICATION->IncludeComponent(
						"bitrix:tasks.tags.selector",
						".default",
						array(
							"NAME" => "TAGS",
							"VALUE" => $arParams["TEMPLATE_DATA"]["TAGS"]
						),
						null,
						array("HIDE_ICONS" => "Y")
					);
				}
				else
				{
					echo htmlspecialcharsbx($arParams["TEMPLATE_DATA"]["TAGS"]);
				}
			?>
			</div>
		</div>
		<? endif ?>

	</div>

</div>

<script>
	new BX.Tasks.Component.TaskViewSidebar({
		taskId: <?=$taskData["ID"]?>,
		deadline: "<?=CUtil::JSEscape($taskData["DEADLINE"])?>",
		mark: "<?=CUtil::JSEscape($taskData["MARK"])?>",
		workingTime: {
			start : {
				hours: <?=intval($workingTime["HOURS"]["START"]["H"])?>,
				minutes: <?=intval($workingTime["HOURS"]["START"]["M"])?>
			},
			end : {
				hours: <?=intval($workingTime["HOURS"]["END"]["H"])?>,
				minutes: <?=intval($workingTime["HOURS"]["END"]["M"])?>
			}
		},
		can: <?=CUtil::PhpToJSObject($can)?>,
		allowTimeTracking: <?=CUtil::PhpToJSObject($taskData["ALLOW_TIME_TRACKING"] === "Y")?>,
		messages: {
			emptyDeadline: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_SIDEBAR_DEADLINE_NO"))?>"
		}
	});
</script>