<?

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($arParams['SHOW_SECTIONS_BAR'] !== 'Y' &&
	$arParams['SHOW_FILTER_BAR'] !== 'Y' &&
	$arParams['SHOW_COUNTERS_BAR'] !== 'Y'
)
{
	return;
}

CUtil::InitJSCore(array('popup', 'tooltip', 'ajax', 'date', 'tasks_util_query', 'socnetlogdest', 'CJSTask'));

$taskListUserOpts = CUserOptions::GetOption('tasks', 'task_list');
$taskListGlobalOpts = array(
	'enable_gantt_hint' => \Bitrix\Main\Config\Option::get('tasks', 'task_list_enable_gantt_hint')
);

$this->SetViewTarget($arParams["MENU_TARGET"], 100);

if ($arParams['SHOW_SECTIONS_BAR'] === 'Y')
{
	?><div class="tasks-top-menu-wrap task-target-<?=$arParams["MENU_TARGET"]?>">
		<div class="tasks-top-menu" id="task-menu-block">
			<?
			if (isset($arParams['CUSTOM_ELEMENTS']['BACK_BUTTON_ALT']) && $arParams['SECTION_URL_PREFIX'])
			{
				?><span class="tasks-top-item-wrap" id="tasks-menu-block-btn-back"><?
					?><a class="tasks-top-item"
						href="<? echo $arParams['SECTION_URL_PREFIX']; ?>?F_CANCEL=N"
						><span class="tasks-top-item-text"><span style="font-size: 19px;">&larr;</span> <? echo GetMessage('TASKS_PANEL_TAB_BACK_TO_LIST'); ?></span>
				</a></span><?
			}

			foreach ($arResult['VIEW_STATE']['ROLES'] as $roleCodename => $arRoleData)
			{
				$cls = '';
				$clsActive = '';
				switch ($roleCodename)
				{
					case 'VIEW_ROLE_RESPONSIBLE':
						$counterId = 'tasks-main-top-counter-my';
						$cls = 'tasks-icon-do';
					break;

					case 'VIEW_ROLE_ACCOMPLICE':
						$counterId = 'tasks-main-top-counter-accomplice';
						$cls = 'tasks-icon-help';
					break;

					case 'VIEW_ROLE_ORIGINATOR':
						$counterId = 'tasks-main-top-counter-originator';
						$cls = 'tasks-icon-delegate';
					break;

					case 'VIEW_ROLE_AUDITOR':
						$counterId = 'tasks-main-top-counter-auditor';
						$cls = 'tasks-icon-watch';
					break;
				}

				if (
					($arParams['MARK_ACTIVE_ROLE'] === 'Y')
					&& ($arRoleData['SELECTED'] === 'Y')
				)
				{
					if ($arResult['SELECTED_SECTION_NAME'] === 'VIEW_SECTION_ROLES')
						$clsActive = ' tasks-top-item-wrap-active';
				}

				$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['ROLES'][$roleCodename];

				if ($arParams['SHOW_SECTION_COUNTERS'] === 'Y')
					$counter = $arResult['VIEW_COUNTERS']['ROLES'][$roleCodename]['TOTAL']['COUNTER'];
				else
					$counter = '';

				if ($counter < 0)
					$counter = 0;

				?><span class="tasks-top-item-wrap <? echo $clsActive; ?>"><?
					?><a class="tasks-top-item" href="<? echo $href; ?>" onclick="this.blur();"><?
						?><span class="tasks-top-item-icon <? echo $cls; ?>"></span><?
						?><span class="tasks-top-item-text"><? echo $arRoleData['TITLE']; ?></span><?
						if ($arParams['SHOW_SECTION_COUNTERS'] === 'Y')
						{
							?><span
								id="<? echo $counterId; ?>"
								class="tasks-top-item-counter"
								<?
								if ($counter == 0)
									echo ' style="display:none;" '
								?>
								><? echo $counter; ?></span><?
						}
				?></a></span><?
			}

			// special presets
			if(is_array($arResult['VIEW_STATE']['SPECIAL_PRESETS']))
			{
				foreach($arResult['VIEW_STATE']['SPECIAL_PRESETS'] as $presetId => $preset)
				{
					?><span class="tasks-top-item-wrap <? if ($arResult['MARK_SPECIAL_PRESET'] === 'Y' && $preset['SELECTED'] === 'Y') echo ' tasks-top-item-wrap-active'; ?> "><?
					?><a class="tasks-top-item" href="<?=($arParams['SECTION_URL_PREFIX'].$arResult['VIEW_HREFS']['SPECIAL_PRESETS'][$presetId])?>"><?
						?><span class="tasks-top-item-icon tasks-icon-<?=htmlspecialcharsbx(ToLower($preset['CODE']))?>"></span><?
						?><span class="tasks-top-item-text"><?=htmlspecialcharsbx($preset['TITLE'])?></span><?
					?></a></span><?
				}
			}

			?><span class="tasks-top-item-wrap <? if ($arResult['MARK_SECTION_ALL'] === 'Y') echo ' tasks-top-item-wrap-active'; ?> tasks-top-item-wrap-all"><?
			?><a class="tasks-top-item" href="<?=($arParams['SECTION_URL_PREFIX'].$arResult['VIEW_SECTION_ADVANCED_FILTER_HREF'])?>"><?
				?><span class="tasks-top-item-icon tasks-icon-all"></span><?
				?><span class="tasks-top-item-text"><?=GetMessage('TASKS_PANEL_TAB_ALL')?></span><?
			?></a></span><?

			if ($arResult['SHOW_SECTION_PROJECTS'] == 'Y')
			{
				?><span class="tasks-top-item-wrap <? if ($arParams['MARK_SECTION_PROJECTS'] === 'Y') echo ' tasks-top-item-wrap-active'; ?>"><?
				?><a class="tasks-top-item"
					href="<? echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_PROJECTS"], array());?>"><?
					?><span class="tasks-top-item-icon tasks-icon-projects"></span><?
					?><span class="tasks-top-item-text"><?
						echo GetMessage('TASKS_PANEL_TAB_PROJECTS');
					?></span><?
				?></a></span><?
			}

			if ($arResult['SHOW_SECTION_MANAGE'] == 'Y')
			{
				?><span class="tasks-top-item-wrap <? if ($arParams['MARK_SECTION_MANAGE'] === 'Y') echo ' tasks-top-item-wrap-active'; ?>"><?
				?><a class="tasks-top-item"
					href="<? echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_DEPARTMENTS"], array());?>"><?
					?><span class="tasks-top-item-icon tasks-icon-lead"></span><?
					?><span class="tasks-top-item-text"><?
						echo GetMessage('TASKS_PANEL_TAB_MANAGE');
					?></span><?
					?><span
						id="tasks-main-top-counter-manage"
						class="tasks-top-item-counter tasks-top-item-orange-counter"
						<? if ($arResult['SECTION_MANAGE_COUNTER'] <= 0) echo ' style="display:none;" '; ?>><?
							echo $arResult['SECTION_MANAGE_COUNTER'];
					?></span><?
					// <span class="tasks-top-item-counter">22</span>
				?></a></span><?
			}

			?><span class="tasks-top-item-wrap <? if ($arParams['MARK_SECTION_REPORTS'] === 'Y') echo ' tasks-top-item-wrap-active'; ?>"><?
				?><a class="tasks-top-item" href="<? echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORTS"], array());?>"><?
					?><span class="tasks-top-item-icon tasks-icon-reports"></span><?
					?><span class="tasks-top-item-text"><? echo GetMessage('TASKS_PANEL_TAB_REPORTS'); ?></span><?
					?></a></span><?if($arResult['BX24_RU_ZONE']):?><span class="tasks-top-item-wrap "><?
				?><a class="tasks-top-item" href="/marketplace/?category=tasks"><?
					?><span class="tasks-top-item-icon tasks-icon-apps"></span><?
					?><span class="tasks-top-item-text"><?=GetMessage('TASKS_PANEL_TAB_APPLICATIONS');?></span><?
				?></a></span><?endif?>
		</div>
	</div>
	<script type="text/javascript">
		var more_btn_toggle = {
			state_btn:'hidden',
			menu_items_list:[],
			item_width:[],
			button_width:0,
			num_hidden_items:0,
			popup:null,
			params:{
				block:null,
				popup_items:[],
				popup_params:{},
				menu_item_class:'',
				button:null,
				events:null
			},

			init:function(params){

				this.params = params;

				this.set_menu_item_list();

				var my_style = this.params.button.currentStyle || window.getComputedStyle(this.params.button);

				var mLeft = parseInt(my_style.marginLeft) || 0;
				var mRight = parseInt(my_style.marginRight) || 0;

				this.params.block.appendChild(this.params.button);

				this.button_width = this.params.button.offsetWidth + mLeft + mRight;

				this.params.block.appendChild(this.params.button);
				this.params.button.style.position = 'absolute';
				this.params.button.style.top = '-500px';

				var _this = this;

				BX.bind(window, 'resize', function(){

					_this.toggle_btn();

					if(_this.popup){
						_this.popup.popupWindow.close();
						_this.popup.popupWindow.destroy();
						_this.popup = null;
					}
				});

				BX.bind(window, 'load', function(){
					_this.toggle_btn();
				});

				BX.bind(this.params.button, 'click', function(){

					_this.show_popup();
				});

				setTimeout(function(){_this.toggle_btn();},0);
			},

			set_menu_item_list: function()
			{
				var style;
				this.menu_items_list = BX.findChildren(this.params.block, {className:this.params.menu_item_class}, false);

				for(var i = 0; i < this.menu_items_list.length; i++ )
				{
					style = this.menu_items_list[i].currentStyle || window.getComputedStyle(this.menu_items_list[i]);
					this.item_width.push(this.menu_items_list[i].offsetWidth + parseInt(style.marginLeft) + parseInt(style.marginRight));
				}
			},

			show_popup:function()
			{
				var popup_items = [];

				if(this.popup) {
					this.popup.popupWindow.destroy();
					this.popup = null;
				}

				for(var i=0; i<this.params.popup_items.length; i++){
					popup_items.push(this.params.popup_items[i])
				}

				popup_items.splice(0, (this.params.popup_items.length - this.num_hidden_items));

				var _this = this;

				if(!this.params.popup_params.events) {
					this.params.popup_params.events = {
						onPopupClose: function() {
							if(_this.params.button.classList)
								_this.params.button.classList.remove('bx-menu-btn-more-active');
							else BX.removeClass(_this.params.button, 'bx-menu-btn-more-active')
						}
					}
				}

				this.popup =  BX.PopupMenu.create(
						(this.params.popup_id + Math.random()),
						this.params.button,
						popup_items,
						this.params.popup_params
				);

				this.popup.popupWindow.show();

				BX.addClass(this.params.button, 'bx-menu-btn-more-active')
			},

			toggle_btn:function()
			{
				var item_top,
					last_visible_item,
					empty_space,
					coord_block,
					first_item_top,
					last_item_top,
					num_first_hidden = 0,
					coord_last_visible,
					num_hidden_items = 0,
					block_pad_right,
					last_visible_padding;

				if(this.state_btn == 'show')
				{
					this.params.block.removeChild(this.params.button);
					this.state_btn = 'hidden';
				}

				first_item_top = parseInt(this.menu_items_list[0].offsetTop);

				last_item_top = parseInt(this.menu_items_list[this.menu_items_list.length-1].offsetTop);

				if(first_item_top != last_item_top)
				{

					for(var i=this.menu_items_list.length-1; i>=0; i--)
					{
						item_top = parseInt(this.menu_items_list[i].offsetTop);

						last_visible_item = this.menu_items_list[i];

						if(first_item_top != item_top){

							num_hidden_items++;
						}
						else if(first_item_top == item_top){
							break
						}
					}

					coord_last_visible = last_visible_item.getBoundingClientRect();

					coord_block = this.params.block.getBoundingClientRect();

					block_pad_right = parseInt(BX.style(this.params.block, 'paddingRight')) || 0;

					last_visible_padding = parseInt(BX.style(last_visible_item, 'marginRight')) || 0;

					empty_space = Math.ceil((coord_block.right - block_pad_right) - (coord_last_visible.right + last_visible_padding));

					num_first_hidden = this.menu_items_list.length-1 - (num_hidden_items-1);

					if(empty_space < Math.ceil(this.button_width)){
						num_first_hidden--;
						num_hidden_items++
					}

					this.num_hidden_items = num_hidden_items;

					this.params.button.style.top = '';
					this.params.button.style.position = 'static';
					this.params.button.style.display = 'inline-block';
					this.params.block.insertBefore(this.params.button, this.menu_items_list[num_first_hidden]);
					this.state_btn = 'show';
				}
			}
		};

		more_btn_toggle.init({
			block:BX('task-menu-block'),
			menu_item_class:'tasks-top-item-wrap',
			button:BX.create('span', {
				props:{
					className:'tasks-top-more-wrap'
				},
				html:
				'<span class="tasks-top-item-more">' +
					'<span class="tasks-top-item-icon tasks-icon-more"></span>' +
					'<span class="tasks-top-item-text"><? echo GetMessageJs('TASKS_PANEL_BTN_MORE'); ?></span>' +
					'<span class="tasks-top-item-arrow"></span>' +
				'</span>'
			}),
			popup_items:[
				{
					text : '<? echo $arResult['VIEW_STATE']['ROLES']['VIEW_ROLE_RESPONSIBLE']['TITLE']; ?>',
					className : "tasks-top-popup-item tasks-top-popup-do",
					href : "<? echo $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['ROLES']['VIEW_ROLE_RESPONSIBLE']; ?>"
				},
				{
					text : '<? echo $arResult['VIEW_STATE']['ROLES']['VIEW_ROLE_ACCOMPLICE']['TITLE']; ?>',
					className : "tasks-top-popup-item tasks-top-popup-help",
					href : "<? echo $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['ROLES']['VIEW_ROLE_ACCOMPLICE']; ?>"
				},
				{
					text : '<? echo $arResult['VIEW_STATE']['ROLES']['VIEW_ROLE_ORIGINATOR']['TITLE']; ?>',
					className : "tasks-top-popup-item tasks-top-popup-delegate",
					href : "<? echo $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['ROLES']['VIEW_ROLE_ORIGINATOR']; ?>"
				},
				{
					text : '<? echo $arResult['VIEW_STATE']['ROLES']['VIEW_ROLE_AUDITOR']['TITLE']; ?>',
					className : "tasks-top-popup-item tasks-top-popup-watch",
					href : "<? echo $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['ROLES']['VIEW_ROLE_AUDITOR']; ?>"
				},

				<?if(is_array($arResult['VIEW_STATE']['SPECIAL_PRESETS'])):?>
					<?foreach($arResult['VIEW_STATE']['SPECIAL_PRESETS'] as $presetId => $preset):?>
						{
							text : '<?=htmlspecialcharsbx($preset['TITLE'])?>',
							className : "tasks-top-popup-item tasks-top-popup-<?=htmlspecialcharsbx(ToLower($preset['CODE']))?>",
							href : "<?=($arParams['SECTION_URL_PREFIX'].$arResult['VIEW_HREFS']['SPECIAL_PRESETS'][$presetId])?>"
						},
					<?endforeach?>
				<?endif?>

				{
					text : '<? echo GetMessageJs('TASKS_PANEL_TAB_ALL'); ?>',
					className : "tasks-top-popup-item tasks-top-popup-all",
					href : "<? echo $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_SECTION_ADVANCED_FILTER_HREF']; ?>"
				},
				<?
				if ($arResult['SHOW_SECTION_PROJECTS'] == 'Y')
				{
					?>
					{
						text : '<? echo GetMessageJs('TASKS_PANEL_TAB_PROJECTS'); ?>',
						className : "tasks-top-popup-item tasks-top-popup-projects",
						href : "<? echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_PROJECTS"], array());?>"
					},
					<?
				}

				if ($arResult['SHOW_SECTION_MANAGE'] == 'Y')
				{
					?>
					{
						text : '<? echo GetMessageJs('TASKS_PANEL_TAB_MANAGE'); ?>',
						className : "tasks-top-popup-item tasks-top-popup-lead",
						href : "<? echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_DEPARTMENTS"], array());?>"
					},
					<?
				}
				?>
				{
					text : '<? echo GetMessageJs('TASK_TOOLBAR_FILTER_REPORTS'); ?>',
					className : "tasks-top-popup-item tasks-top-popup-reports",
					href : "<? echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORTS"], array());?>"
				}

				<?if($arResult['BX24_RU_ZONE']):?>

				,{
					text : '<? echo GetMessageJs('TASKS_PANEL_TAB_APPLICATIONS'); ?>',
					className : "tasks-top-popup-item tasks-top-popup-apps",
					href : "/marketplace/?category=tasks"
				}

				<?endif?>
			],
			popup_id:'tasks-top-more-popup',
			popup_params:{
				offsetTop:-2,
				offsetLeft:8,
				angle:{
					offset:17
				}
			}
		});


		BX.addCustomEvent("onPullEvent-main", function(command, params){
			if (command != 'user_counter' || !params[BX.message('SITE_ID')])
			{
				return;
			}

			var node = null;
			var value = null;
			if (params[BX.message('SITE_ID')]['tasks_my'])
			{
				node = BX('tasks-main-top-counter-my');
				if (node)
				{
					value = params[BX.message('SITE_ID')]['tasks_my'];

					if (value == 0)
					{
						node.style.display = 'none';
					}
					else
					{
						node.style.display = '';
						node.innerHTML = value;
					}
				}
			}

			if (params[BX.message('SITE_ID')]['tasks_acc'])
			{
				node = BX('tasks-main-top-counter-accomplice');
				if (node)
				{
					value = params[BX.message('SITE_ID')]['tasks_acc'];

					if (value == 0)
					{
						node.style.display = 'none';
					}
					else
					{
						node.style.display = '';
						node.innerHTML = value;
					}
				}
			}

			if (params[BX.message('SITE_ID')]['tasks_au'])
			{
				node = BX('tasks-main-top-counter-auditor');
				if (node)
				{
					value = params[BX.message('SITE_ID')]['tasks_au'];

					if (value == 0)
					{
						node.style.display = 'none';
					}
					else
					{
						node.style.display = '';
						node.innerHTML = value;
					}
				}

			}

			if (params[BX.message('SITE_ID')]['tasks_orig'])
			{
				node = BX('tasks-main-top-counter-originator');
				if (node)
				{
					value = params[BX.message('SITE_ID')]['tasks_orig'];

					if (value == 0)
					{
						node.style.display = 'none';
					}
					else
					{
						node.style.display = '';
						node.innerHTML = value;
					}
				}
			}

		});
	</script><?
}

$this->EndViewTarget();

$this->SetViewTarget($arParams["CONTROLS_TARGET"], 200);

if ($arParams['SHOW_COUNTERS_BAR'] === 'Y')
{
	$arStrings = array();

	if (
		isset($arResult['TASKS_NEW_COUNTER']['VALUE'])
		&& $arResult['TASKS_NEW_COUNTER']['VALUE']
	)
	{
		$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_NEW'];
		$arStrings[] = '<a href="' . $href . '" class="task-green-text">'
			. $arResult['TASKS_NEW_COUNTER']['VALUE']
			. ' '
			. GetMessage(
				'TASKS_PANEL_EXPLANATION_NEW_TASKS_SUFFIX_PLURAL_'
				. $arResult['TASKS_NEW_COUNTER']['PLURAL']
			)
			. '</a>';
	}

	if (
		isset($arResult['TASKS_EXPIRED_COUNTER']['VALUE'])
		&& $arResult['TASKS_EXPIRED_COUNTER']['VALUE']
	)
	{
		$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_EXPIRED'];
		$arStrings[] = '<a href="' . $href . '" class="task-red-text">'
			. $arResult['TASKS_EXPIRED_COUNTER']['VALUE']
			. ' '
			. GetMessage(
				'TASKS_PANEL_EXPLANATION_EXPIRED_TASKS_SUFFIX_PLURAL_'
				. $arResult['TASKS_EXPIRED_COUNTER']['PLURAL']
			)
			. '</a>';
	}

	if (
		isset($arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['VALUE'])
		&& $arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['VALUE']
	)
	{
		$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES'];
		$arStrings[] = '<a href="' . $href . '" class="task-brown-text">'
			. $arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['VALUE']
			. ' '
			. GetMessage(
				'TASKS_PANEL_EXPLANATION_EXPIRED_SOON_TASKS_SUFFIX_PLURAL_'
				. $arResult['TASKS_EXPIRED_CANDIDATES_COUNTER']['PLURAL']
			)
			. '</a>';
	}

	if (
		isset($arResult['TASKS_WAIT_CTRL_COUNTER']['VALUE'])
		&& $arResult['TASKS_WAIT_CTRL_COUNTER']['VALUE']
	)
	{
		$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_WAIT_CTRL'];
		$arStrings[] = '<a href="' . $href . '" class="task-brown-text">'
			. $arResult['TASKS_WAIT_CTRL_COUNTER']['VALUE']
			. ' '
			. GetMessage(
				'TASKS_PANEL_EXPLANATION_WAIT_CTRL_TASKS_SUFFIX_PLURAL_'
				. $arResult['TASKS_WAIT_CTRL_COUNTER']['PLURAL']
			)
			. '</a>';
	}

	if (
		isset($arResult['TASKS_WO_DEADLINE_COUNTER']['VALUE'])
		&& $arResult['TASKS_WO_DEADLINE_COUNTER']['VALUE']
	)
	{
		$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES']['VIEW_TASK_CATEGORY_WO_DEADLINE'];
		$woDlString = '<a href="' . $href . '" class="task-brown-text">'
			. $arResult['TASKS_WO_DEADLINE_COUNTER']['VALUE']
			. ' '
			. GetMessage(
				'TASKS_PANEL_EXPLANATION_WO_DEADLINE_TASKS_SUFFIX_PLURAL_'
				. $arResult['TASKS_WO_DEADLINE_COUNTER']['PLURAL']
			);
		$roles = $arResult['VIEW_STATE']['ROLES'];
		if($roles['VIEW_ROLE_RESPONSIBLE']['SELECTED'] == 'Y')
		{
			$woDlString .= ' '.GetMessage('TASKS_PANEL_EXPLANATION_WO_DEADLINE_TASKS_RESPONSIBLE');
		}
		elseif($roles['VIEW_ROLE_ORIGINATOR']['SELECTED'] == 'Y')
		{
			$woDlString .= ' '.GetMessage('TASKS_PANEL_EXPLANATION_WO_DEADLINE_TASKS_ORIGINATOR');
		}
		$woDlString .= '</a>';
		$arStrings[] = $woDlString;
	}

	$stringsCount = count($arStrings);
	if ($stringsCount)
	{
		?>
		<div class="task-top-panel-tre">
			<div class="task-main-notification-icon-counter"><?
				echo $arResult['SELECTED_ROLE_COUNTER']['VALUE'];
				?></div>
			<span><? echo GetMessage('TASKS_PANEL_EXPLANATION_PREFIX'); ?></span>
			<?
			$stringsPrinted = 0;

			foreach ($arStrings as $string)
			{
				echo $string;
				$stringsPrinted++;

				$stringsRemain = $stringsCount - $stringsPrinted;

				if ($stringsRemain == 1)
					echo ' ' . GetMessage('TASKS_PANEL_EXPLANATION_AND_WORD') . ' ';
				elseif ($stringsRemain >= 2)
					echo ', ';
			}
			?>
		</div>
		<?
	}
}

if ($arParams['SHOW_FILTER_BAR'] === 'Y')
{
	$selectedRoleCodename = $arResult['VIEW_STATE']['ROLE_SELECTED']['CODENAME'];
	$categoryHref = $arParams['SECTION_URL_PREFIX'].$arResult['VIEW_HREFS']['TASK_CATEGORIES'][$arResult['VIEW_STATE']['TASK_CATEGORY_SELECTED']['CODENAME']];

	// names
	if ($arResult['F_CREATED_BY'])
	{
		if ($arResult['F_CREATED_BY'] == $USER->getId())
			$creatorName = GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_RESPONSIBLE_IS_ME');
		else
			$creatorName = htmlspecialcharsbx($arResult['~USER_NAMES'][$arResult['F_CREATED_BY']]);
	}
	else
		$creatorName = GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_ANY_ORIGINATOR');

	if ($arResult['F_RESPONSIBLE_ID'])
		$responsibleName = htmlspecialcharsbx($arResult['~USER_NAMES'][$arResult['F_RESPONSIBLE_ID']]);
	else
		$responsibleName = GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_ANY_RESPONSIBLE');

	$currentSortingName = "---";
	foreach ($arParams['SORTING'] as $sortItem)
	{
		if ($sortItem["SELECTED"])
		{
			$currentSortingName = GetMessage("TASKS_LIST_COLUMN_".$sortItem["INDEX"]);
			break;
		}
	}

	$projectId = 0;
	$projectName = GetMessage("TASKS_QUICK_IN_GROUP");
	if (isset($arParams["~GROUP"]))
	{
		$projectId = $arParams["~GROUP"]["ID"];
		$projectName = $arParams["~GROUP"]["NAME"];
	}
	?>
	<div class="task-top-notification" id="task-new-item-notification">
		<div class="task-top-notification-inner">
			<?=GetMessage("TASKS_QUICK_FORM_AFTER_SAVE_MESSAGE", array("#TASK_NAME#" => '<span class="task-top-notification-message" id="task-new-item-message"></span>'))?>
			<a href="" class="task-top-notification-link" id="task-new-item-open"><?=GetMessage("TASKS_QUICK_FORM_OPEN_TASK")?></a>
			<span class="task-top-notification-link" id="task-new-item-highlight"><?=GetMessage("TASKS_QUICK_FORM_HIGHLIGHT_TASK")?></span>
		</div>
		<span class="task-top-panel-tab-close task-top-panel-tab-close-active task-top-notification-hide" id="task-new-item-notification-hide"></span>
	</div>
	<div class="task-top-panel-create">
		<div class="task-top-panel-righttop" id="task-new-item">
			<form id="task-new-item-form" action="">
			<span class="task-top-panel-create-container">
				<input type="text" autocomplete="off" placeholder="<?=GetMessage("TASKS_RESPONSIBLE")?>"
					tabindex="3" id="task-new-item-responsible" name="task-new-item-responsible"
					value="<?
					echo tasksFormatName(
						$arParams["~USER"]["NAME"],
						$arParams["~USER"]["LAST_NAME"],
						$arParams["~USER"]["LOGIN"],
						$arParams["~USER"]["SECOND_NAME"],
						$arParams["NAME_TEMPLATE"]
					);
					?>">
				<input type="hidden" id="task-new-item-responsible-id" value="<? echo $arParams["USER_ID"]?>">
			</span>
			<span class="task-top-panel-create-container">
				<input type="text" autocomplete="off" placeholder="<?=GetMessage("TASKS_QUICK_DEADLINE")?>" tabindex="2"
					id="task-new-item-deadline"
					name="task-new-item-deadline"
					data-default-hour="<?=intval($arParams["COMPANY_WORKTIME"]["END"]["H"])?>"
					data-default-minute="<?=intval($arParams["COMPANY_WORKTIME"]["END"]["M"])?>">
			</span>
			<span class="task-top-panel-create-container task-top-panel-create-container-big">
				<span class="task-top-panel-create-menu" id="task-new-item-menu"></span>
				<input type="text" placeholder="<?=GetMessage("TASKS_QUICK_FORM_TITLE_PLACEHOLDER")?>" tabindex="1" id="task-new-item-title">
			</span>
			<span class="task-top-panel-middle">
				<span class="task-top-panel-leftmiddle" id="task-new-item-description-block">
					<span id="task-new-item-project-link" class="task-top-panel-tab"><?=$projectName?></span><span class="task-top-panel-tab-close<?=($projectId > 0 ? " task-top-panel-tab-close-active" : "")?>" id="task-new-item-project-clearing"></span><span class="task-top-panel-tab task-top-panel-leftmiddle-description" id="task-new-item-description-link" href=""><?=GetMessage("TASKS_QUICK_DESCRIPTION")?></span>
					<input type="hidden" id="task-new-item-project-id" value="<?=$projectId?>">
					<textarea cols="30" rows="10" placeholder="<?=GetMessage("TASKS_QUICK_FORM_DESC_PLACEHOLDER")?>" tabindex="4" id="task-new-item-description"></textarea>
				</span>
				<span class="webform-small-button webform-small-button-transparent" id="task-new-item-save"><?=GetMessage("TASKS_QUICK_SAVE")?></span>
				<span class="webform-button-link" id="task-new-item-cancel"><?=GetMessage("TASKS_QUICK_CANCEL")?></span>
			</span>
			</form>
			<script>
				new BX.Tasks.QuickForm("task-new-item", {
					nameTemplate: "<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>",
					filter: "<?=CUtil::JSEscape(serialize($arParams["FILTER"]))?>",
					order: "<?=CUtil::JSEscape(serialize($arParams["ORDER"]))?>",
					navigation: "<?=CUtil::JSEscape(serialize($arParams["NAVIGATION"]))?>",
					select: "<?=CUtil::JSEscape(serialize($arParams["SELECT"]))?>",
					ganttMode: <?= (isset($arParams["GANTT_MODE"]) ? "true" : "false")?>,
					destination: <?=CUtil::PhpToJSObject($arResult["DESTINATION"])?>,
					canAddMailUsers: <?=CUtil::PhpToJSObject(\Bitrix\Main\ModuleManager::isModuleInstalled("mail"))?>,
					canManageTask: <?=CUtil::PhpToJSObject(\Bitrix\Tasks\Util\Restriction::canManageTask())?>,
					messages: {
						taskInProject: "<?=GetMessageJs("TASKS_QUICK_IN_GROUP")?>"
					}
				});
			</script>
		</div>
		<div class="task-top-panel-leftbottom">
		<span class="task-top-panel-two-inright">
			<span class="task-top-panel-create-text"><?=GetMessage("TASKS_PANEL_SORTED_BY")?>:</span>
			<span class="task-top-panel-create-link" id="task-top-panel-sorting-selector"><span<?if (LANGUAGE_ID !== "de"):?> style="text-transform: lowercase"<?endif?>><?=$currentSortingName?></span></span>
			<span id="task-top-panel-view-mode-selector" class="webform-small-button webform-small-button-transparent bx-filter-button">
				<span class="webform-small-button-text"><?
					$selectedViewCodename = $arResult['VIEW_STATE']['VIEW_SELECTED']['CODENAME'];
					echo $arResult['VIEW_STATE']['VIEWS'][$selectedViewCodename]['SHORT_TITLE'];
					?></span><span class="webform-small-button-icon"></span>
			</span>
		</span>

		<span class="task-top-panel-inleft">

			<?
			if ($arResult['MARK_SECTION_ALL'] === 'Y') // "all" + advanced filter
			{
				?><div class="task-main-top-menu-advanced-filter">&nbsp;<?

				$filterName = '';
				if (strlen($arParams['SELECTED_PRESET_NAME']))
					$filterName .= ': ' . $arParams['SELECTED_PRESET_NAME'];

				if ($arParams["VIEW_TYPE"] == "gantt")
				{
					?><span class="webform-small-button task-list-toolbar-filter webform-small-button-transparent bx-filter-button" onclick="showGanttFilter(this)"><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?
						echo GetMessage("TASK_TOOLBAR_FILTER_BUTTON") . $filterName;
						?></span><span class="webform-small-button-icon"></span><span class="webform-small-button-right"></span></span><?
				}
				else
				{
					?><span class="webform-small-button task-list-toolbar-filter webform-small-button-transparent bx-filter-button" onclick="showTaskListFilter(this)"><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?
						echo GetMessage("TASK_TOOLBAR_FILTER_BUTTON") . $filterName;
						?></span><span class="webform-small-button-icon"></span><span class="webform-small-button-right"></span></span><?
				}
				?></div><?
			}
			else
			{
				?><span class="task-top-panel-create-text"><?=GetMessage("TASKS_PANEL_FILTER_STATUS_LABEL")?>:</span>
				<span id="task-top-panel-task-category-selector" class="task-top-panel-create-link"><span><?
						$selectedCategoryCodename = $arResult['VIEW_STATE']['TASK_CATEGORY_SELECTED']['CODENAME'];
						echo $arResult['VIEW_STATE']['TASK_CATEGORIES'][$selectedCategoryCodename]['TITLE'];
						?></span><?
				?></span>,<?

				$showCreatorSelector = true;
				$showResponsibleSelector = true;
				if ($arResult["SELECTED_SECTION_NAME"] === "VIEW_SECTION_ROLES" &&
					in_array($selectedRoleCodename, array("VIEW_ROLE_RESPONSIBLE", "VIEW_ROLE_ORIGINATOR")))
				{
					$showCreatorSelector = $selectedRoleCodename === "VIEW_ROLE_RESPONSIBLE";
					$showResponsibleSelector = $selectedRoleCodename === "VIEW_ROLE_ORIGINATOR";
					?><span class="task-top-panel-create-text task-top-panel-from-to"><?= ($showCreatorSelector ? GetMessage("TASKS_PANEL_HUMAN_FILTER_STRING_FROM") : GetMessage("TASKS_PANEL_HUMAN_FILTER_STRING_FOR"))?></span><?
				}
				else
				{
					?><span class="task-top-panel-create-link task-top-panel-switch" id="task-top-panel-from-for-switch">
						<span data-bx-ui-id="from-for-switch-label" data-label="FROM"><?=GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_FROM')?></span>
						<span data-bx-ui-id="from-for-switch-label" data-label="FOR"><?=GetMessage('TASKS_PANEL_HUMAN_FILTER_STRING_FOR')?></span>
					</span><?
				}

				if ($showCreatorSelector)
				{
					?><span id="task-top-panel-task-originator-selector" class="task-top-panel-create-link"><span><?=$creatorName?></span></span><?
				}

				if ($showResponsibleSelector)
				{
					?><span id="task-top-panel-task-responsible-selector" class="task-top-panel-create-link"><span><?=$responsibleName?></span></span><?
				}
			}
			?>
		</span>
	</div>
	</div>

	<?if($taskListGlobalOpts['enable_gantt_hint'] != 'N' && $taskListUserOpts['enable_gantt_hint'] != 'N' && $arParams["VIEW_TYPE"] == "gantt"):?>
		<div class="task-widg-white-tooltip" id="gantt-hint">
			<div class="task-widg-white-text">
				<?=GetMessage('TASKS_PANEL_GANTT_HINT_TITLE')?>
			</div>
			<div class="task-widg-white-text">
				<?=GetMessage('TASKS_PANEL_GANTT_HINT_BODY')?>
			</div>
			<img src="<?=$templateFolder?>/images/gant-task-pict.png" class="task-widg-gant" alt="" />
			<div class="task-widg-white-close" id="gantt-hint-close"></div>
		</div>
	<?endif?>

	<script>
	(function(){
		BX.ready(function(){
			BX.Tasks.ListControlsNS.menu.create('views_menu');
			<?
			foreach ($arResult['VIEW_STATE']['VIEWS'] as $viewCodeName => $viewData)
			{
				$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['VIEWS'][$viewCodeName];
				?>BX.Tasks.ListControlsNS.menu.addItem(
					'views_menu',
					'<? echo CUtil::JSEscape($viewData['TITLE']); ?>',
					'<?=($viewData["SELECTED"] === "Y" ? "menu-popup-item-accept" : "task-menu-popup-no-icon")?>',
					'<? echo $href; ?>'
				);
				<?
			}
			?>

			BX.Tasks.ListControlsNS.menu.addDelimiter('views_menu');

			<?
			foreach ($arResult['VIEW_STATE']['SUBMODES'] as $submodeCodeName => $submodeData)
			{
				$cls = (($submodeData['SELECTED'] === 'Y') ? 'menu-popup-item-accept' : 'menu-popup-no-icon');
				$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['SUBMODES'][$submodeCodeName];

				if ($submodeCodeName === 'VIEW_SUBMODE_WITH_GROUPS')
				{
					continue; // inactive field should not be displayed

					$cls .= ' task-top-panel-disabled-menu-item';
					$href = "javascript:void(0);";
				}

				?>BX.Tasks.ListControlsNS.menu.addItem(
					'views_menu',
					'<? echo CUtil::JSEscape($submodeData['TITLE']); ?>',
					'<? echo $cls; ?>',
					'<? echo $href; ?>'
				);
				<?
			}
			?>

			BX.Tasks.ListControlsNS.menu.create('categories_menu');
			<?
			foreach ($arResult['VIEW_STATE']['TASK_CATEGORIES'] as $categoryCodeName => $categoryData)
			{
				$href = $arParams['SECTION_URL_PREFIX'] . $arResult['VIEW_HREFS']['TASK_CATEGORIES'][$categoryCodeName];

				?>BX.Tasks.ListControlsNS.menu.addItem(
					'categories_menu',
					'<? echo CUtil::JSEscape($categoryData['TITLE']); ?>',
					'menu-popup-no-icon',
					'<? echo $href; ?>'
				);
				<?

				// add delimiter after completed tasks
				if ($categoryCodeName === 'VIEW_TASK_CATEGORY_COMPLETED')
					break;
			}
			?>

			BX.Tasks.ListControlsNS.menu.create('sorting_menu');
			<?
			$currentSorting = null;
			foreach ($arParams['SORTING'] as $sortItem)
			{
				if ($sortItem["SELECTED"])
				{
					$currentSorting = $sortItem;
				}
				?>BX.Tasks.ListControlsNS.menu.addItem(
					'sorting_menu',
					'<? echo GetMessageJS("TASKS_LIST_COLUMN_".$sortItem["INDEX"]) ?>',
					'<?= ($sortItem["SELECTED"] ? " menu-popup-item-accept" : "task-menu-popup-no-icon") ?>',
					'<?= CUtil::JSEscape($sortItem["ASC_DIRECTION"] ? $sortItem["ASC_URL"] : $sortItem["DESC_URL"]) ?>'
				);<?
			}

			if ($currentSorting && $currentSorting["INDEX"] !== "SORTING")
			{
				?>
				BX.Tasks.ListControlsNS.menu.addDelimiter('sorting_menu');
				BX.Tasks.ListControlsNS.menu.addItem(
					'sorting_menu',
					'<?= GetMessageJS("TASKS_PANEL_SORTING_DIRECTION_ASC") ?>',
					'<?= ($currentSorting["ASC_DIRECTION"] ? "menu-popup-item-accept" : "task-menu-popup-no-icon")?>',
					'<?= CUtil::JSEscape($currentSorting["ASC_URL"]) ?>'
				);
				BX.Tasks.ListControlsNS.menu.addItem(
					'sorting_menu',
					'<?= GetMessageJS("TASKS_PANEL_SORTING_DIRECTION_DESC") ?>',
					'<?= (!$currentSorting["ASC_DIRECTION"] ? "menu-popup-item-accept" : "task-menu-popup-no-icon")?>',
					'<?= CUtil::JSEscape($currentSorting["DESC_URL"]) ?>'
				);<?
			}
			?>

			BX.Tasks.ListControlsNS.init();

			BX.bind(
				BX('task-top-panel-view-mode-selector'),
				'click',
				function(){ BX.Tasks.ListControlsNS.menu.show('views_menu', BX('task-top-panel-view-mode-selector')); }
			);

			BX.bind(
				BX('task-top-panel-task-category-selector'),
				'click',
				function(){ BX.Tasks.ListControlsNS.menu.show('categories_menu', BX('task-top-panel-task-category-selector'), {useAppendParams: true}); }
			);

			BX.bind(
				BX('task-top-panel-sorting-selector'),
				'click',
				function(){ BX.Tasks.ListControlsNS.menu.show('sorting_menu', BX('task-top-panel-sorting-selector')); }
			);


			var userInputs = [];

			if (BX('task-top-panel-task-originator-selector'))
			{
				userInputs.push({
					inputNode        : BX('task-top-panel-task-originator-selector'),
					menuId           : 'originators_menu',
					pathPrefix       : '<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('F_CREATED_BY', 'F_RESPONSIBLE_ID')));?>',
					strAnyOriginator : '<?=GetMessageJS('TASKS_PANEL_HUMAN_FILTER_STRING_ANY_ORIGINATOR');?>',
					operation        : 'tasks.list::getOriginators()',
					urlParamName     : 'F_CREATED_BY'
				});
			}

			if (BX('task-top-panel-task-responsible-selector'))
			{
				userInputs.push({
					inputNode        : BX('task-top-panel-task-responsible-selector'),
					menuId           : 'responsibles_menu',
					pathPrefix       : '<? echo CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('F_CREATED_BY', 'F_RESPONSIBLE_ID'))); ?>',
					strAnyOriginator : '<? echo GetMessageJS('TASKS_PANEL_HUMAN_FILTER_STRING_ANY_RESPONSIBLE'); ?>',
					operation        : 'tasks.list::getResponsibles()',
					urlParamName     : 'F_RESPONSIBLE_ID'
				});
			}

			BX.Tasks.ListControlsNS.createGanttHint();

			userInputs.forEach(function(userInput){
				BX.Tasks.ListControlsNS.menu.create(userInput.menuId);
				BX.Tasks.ListControlsNS.menu.addItem(
					userInput.menuId,
					userInput.strAnyOriginator,
					'menu-popup-no-icon',
					'<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('F_CREATED_BY', 'F_RESPONSIBLE_ID')))?>'
				);

				BX.bind(
					userInput.inputNode,
					'click',
					(function(userInput){
						var menuInited = false;

						return function(){
							if (menuInited)
							{
								BX.Tasks.ListControlsNS.menu.show(userInput.menuId, userInput.inputNode, {useAppendParams: true});
								return;
							}

							menuInited = true;

							BX.CJSTask.batchOperations(
								[{
									operation : userInput.operation,
									userId    : <? echo (int) $arParams['USER_ID']; ?>,
									groupId   : <? echo (int) $arParams['GROUP_ID']; ?>,
									rawState  : '<? echo CUtil::JSEscape($arResult['VIEW_STATE_RAW']); ?>'
								}],
								{
									callbackOnSuccess : (function(){
										return function(reply){
											var loggedInUserId = BX.message('USER_ID');
											var menuItems = [];

											reply['rawReply']['data'][0]['returnValue'].forEach(function(item){
												var menuItem = null;
												var name = '';

												if (item['USER_ID'] == loggedInUserId)
													name = '<? echo GetMessageJS("TASKS_PANEL_HUMAN_FILTER_STRING_RESPONSIBLE_IS_ME"); ?>';
												else
												{
													<?// NAME_FORMATTED may vary, but we want "LAST_NAME NAME" format?>
													var name = [];
													if(typeof item['LAST_NAME'] != 'undefined')
														name.push(item['LAST_NAME']);
													if(typeof item['NAME'] != 'undefined')
														name.push(item['NAME']);

													name = name.join(' ');
												}

												menuItem = {
													title : name + ' (' + item['TASKS_CNT'] + ')',
													path  : userInput.pathPrefix
														+ ((userInput.pathPrefix.indexOf('?') !== -1) ? '&' : '?')
														+ userInput.urlParamName
														+ '='
														+ item['USER_ID']
												};

												if (item['USER_ID'] == loggedInUserId)
													menuItems.unshift(menuItem);
												else
													menuItems.push(menuItem);
											});

											if (menuItems.length)
												BX.Tasks.ListControlsNS.menu.addDelimiter(userInput.menuId);

											menuItems.forEach(function(item){
												BX.Tasks.ListControlsNS.menu.addItem(
													userInput.menuId,
													item.title,
													'menu-popup-no-icon',
													item.path
												);
											});

											BX.Tasks.ListControlsNS.menu.show(userInput.menuId, userInput.inputNode, {useAppendParams: true});
										};
									})()
								}
							);
						};
					})(userInput)
				);
			});

			<?if($taskListUserOpts['enable_viewmode_hint'] != 'N' && $arParams['VIEW_TYPE'] == 'gantt'):?>

				BX.message(<?=CUtil::PhpToJSObject(array(
					'TASKS_PANEL_VM_HINT_TITLE' => GetMessage('TASKS_PANEL_VM_HINT_TITLE'),
					'TASKS_PANEL_VM_HINT_BODY' => GetMessage('TASKS_PANEL_VM_HINT_BODY'),
					'TASKS_PANEL_VM_HINT_DISABLE' => GetMessage('TASKS_PANEL_VM_HINT_DISABLE')
				))?>);

				BX.Tasks.ListControlsNS.createViewModeHint();

			<?endif?>
		});
	})();

	// from-for switch

	var switchFromFor = function(way){

		var sw = BX('task-top-panel-from-for-switch');

		if(typeof sw != 'undefined' && sw != null)
		{
			var buttons = sw.querySelectorAll('span');

			if(buttons != null)
			{
				// switch label itself
				for(var k = 0; k < buttons.length; k++)
				{
					var label = BX.data(buttons[k], 'label');

					if (label == way)
					{
						buttons[k].style.display = "inline-block";
					}
					else
					{
						buttons[k].style.display = "none";
					}
				}

				// switch buttons
				if(way == 'FOR')
				{
					BX('task-top-panel-task-originator-selector').style.display = "none";
					BX('task-top-panel-task-responsible-selector').style.display = "inline-block";
				}
				else
				{
					BX('task-top-panel-task-originator-selector').style.display = "inline-block";
					BX('task-top-panel-task-responsible-selector').style.display = "none";
				}

				BX.Tasks.ListControlsNS.params.appendUrlParams.SW_FF = way;
			}
		}
	}

	BX.bindDelegate(BX('task-top-panel-from-for-switch'), 'click', {tagName: 'span'}, function(){
		var label = BX.data(this, 'label');

		label = label == 'FOR' ? 'FROM' : 'FOR'; // invert on click

		switchFromFor.apply(window, [label]);
	});
	switchFromFor('<?=$arResult['FROM_FOR_SWITCH']?>');
	</script>
	<?
}

$this->EndViewTarget();