BX.namespace('Tasks.Component');

BX.Tasks.Component.Task = BX.Tasks.Util.Widget.extend({
	options: {
		removeTemplates: false, // temporal, until the bug fixed
		data: {}
	},
	constants: {
		PRIORITY_AVERAGE: 1,
		PRIORITY_HIGH: 2
	},
	sys: {
		code: 'task-edit'
	},
	methods: {
		construct: function()
		{
			this.callConstruct(BX.Tasks.Util.Widget);

			this.instances.calendar = false;
			this.instances.query = false;
            this.instances.helpWindow = false;

            this.fireTaskEvent();

			if(this.option('doInit'))
			{
				this.bindEvents();
				this.disableHints();
				this.initCheckList();
				this.initResponsible();
				this.initOriginator();
				this.initAuditor();
				this.initAccomplice();
				this.initTag();
				this.initParentTask();
				this.initRelatedTask();
				this.initProject();
				this.initReminder();
				this.initReplication();
				this.initProjectDependence();
				this.initProjectPlan();
				this.initEstimateTime();
				this.initState();

				this.replaceCmdBtn();

				if(!this.getUser().IS_SUPER_USER)
				{
					this.restrictMemberSelectors();
				}
			}
		},

		getUser: function()
		{
			return this.option('auxData').USER;
		},

		restrictMemberSelectors: function()
		{
			var responsible = this.instances['responsible'];
			var originator = this.instances['originator'];

			var canChangeOriginator = this.getTaskActions().EDIT || this.getTaskActions()['EDIT.ORIGINATOR'];

			if(originator)
			{
				if(!canChangeOriginator)
				{
					originator.readonly(true);
				}
				else
				{
					if(!this.isEditMode() || this.getUser().ROLES.ORIGINATOR)
					{
						originator.bindEvent('change', BX.delegate(this.restrictOriginator, this));
						this.restrictOriginator();
					}

					if(responsible)
					{
						responsible.bindEvent('change', BX.delegate(function(items){
							this.restrictMultipleResponsibles(items.length > 1);
						}, this));
						this.restrictMultipleResponsibles(responsible.value().length > 1);
					}
				}
			}
		},

		restrictOriginator: function()
		{
			var responsible = this.instances['responsible'];
			var originator = this.instances['originator'];

			var user = this.getUser().DATA;
			var values = originator.value();
			var valueOrig = false;
			if(typeof values != 'undefined' && typeof values[0] != 'undefined')
			{
				valueOrig = values[0];
			}

			values = responsible.value();
			var valueResp = false;
			if(typeof values != 'undefined' && typeof values[0] != 'undefined')
			{
				valueResp = values[0];
			}

			// other originator. then set responsible to current user and make it read-only
			if(valueOrig)
			{
				if(valueOrig != user.ID)
				{
					if(valueResp != user.ID)
					{
						responsible.replaceItem(valueResp, user);
					}
					responsible.readonly(true);
				}
				else
				{
					responsible.readonly(false);
				}
			}
		},

		restrictMultipleResponsibles: function(way)
		{
			var originator = this.instances['originator'];

			if(originator)
			{
				// multiple responsibles. show originator, set to current user and make read-only
				if(way)
				{
					var user = this.getUser().DATA;
					var values = originator.value();
					var value = false;
					if(typeof values != 'undefined' && typeof values[0] != 'undefined')
					{
						value = values[0];
					}

					if(value && value != user.ID)
					{
						originator.replaceItem(value, user);

						if(BX.hasClass(this.control('originator'), 'invisible'))
						{
							this.toggleBlock('originator');
						}
					}

					originator.readonly(true);
				}
				else
				{
					originator.readonly(false);
				}
			}
		},

		disableHints: function()
		{
			BX.Tasks.Util.hintManager.disableSeveral(this.option('auxData').HINT_STATE);
		},

        fireTaskEvent: function()
        {
            var eType = this.option('componentData').EVENT_TYPE.toString().toUpperCase();
	        var task = this.option('data').EVENT_TASK;
            var uglyTask = this.option('data').EVENT_TASK_UGLY;

	        if(eType && (task || uglyTask))
	        {
		        BX.Tasks.Util.fireGlobalTaskEvent(eType, task, this.option('componentData').EVENT_OPTIONS, uglyTask);
	        }
        },

		replaceCmdBtn: function()
		{
			if(BX.browser.IsMac())
			{
				var cmd = this.control('cmd');
				if(cmd)
				{
					cmd.innerHTML = "&#8984;"
				}
			}
		},

		bindEditorEvents: function(editor, handler)
		{
			// to make form hotkeys work even focus is in editor
			BX.addCustomEvent(editor, 'OnIframeKeyup', handler);
			BX.addCustomEvent(editor, 'OnTextareaKeyup', handler);
		},

		setFocusOnTitle: function(editor)
		{
			setTimeout(BX.delegate(function(){
				editor.Focus(false);
				this.control('title').focus();
				BX.focus();
			}, this), 500);
		},

		isEditMode: function()
		{
			return this.option('template').EDIT_MODE;
		},

		bindEvents: function()
		{
			if(!this.isEditMode())
            {
	            // editor events
	            BX.ready(BX.delegate(function(){

		            var handler = BX.delegate(this.onFormKeyDown, this);

		            BX.bind(
			            document,
			            'keydown',
			            handler
		            );

		            var editorId = this.option('template').ID;
		            var editor = BXHtmlEditor.Get(editorId);

		            if(editor) // already initialized
		            {
			            this.bindEditorEvents(editor, handler);
			            this.setFocusOnTitle(editor, handler);
		            }
		            else
		            {
			            BX.addCustomEvent(
				            window,
				            'OnEditorInitedAfter',
				            BX.delegate(function(eventEditor){

					            if(eventEditor != null && eventEditor.id == editorId)
					            {
						            this.bindEditorEvents(eventEditor, handler);
						            this.setFocusOnTitle(editor, handler);
					            }
				            }, this)
			            );
		            }

	            }, this));
            }

            // toggle check list from main.post.from
			BX.addCustomEvent(document, 'main-post-form-tasks-cl-toggle', BX.delegate(this.onToggleCheckListBlock, this));

			// all block togglers
			this.bindDelegateControl('toggler', 'click', this.passCtx(this.onToggleBlock));

			// all flag togglers
			this.bindDelegateControl('flag', 'click', this.passCtx(this.onToggleFlag));

			// all block choosers
			this.bindDelegateControl('chooser', 'click', this.passCtx(this.onChooseBlock));

			// additional
			this.bindDelegateControl('additional-header', 'click', this.passCtx(this.onToggleAdditionalBlock));

			// priority button
			this.bindDelegateControl('priority-cb', 'change', this.passCtx(this.onPriorityChange));

			// additional flag logic
			this.bindDelegateControl('flag-replication', 'change', this.passCtx(this.onReplicationToggle));
            this.bindDelegateControl('flag-timeman', 'change', this.passCtx(this.onAllowTimeTrackingChange));

			this.bindDelegateControl('pin-footer', 'click', BX.delegate(this.onPinFooterClick, this));

			this.bindControl('cancel-button', 'click', BX.delegate(this.onCancelButtonClick, this));

			this.bindControl('flag-worktime', 'change', this.passCtx(this.onWorktimeChange));

			this.bindControl('form', 'submit', BX.delegate(this.onForumSubmit, this));

			BX.Tasks.Util.hintManager.bindHelp(this.control('options'));
		},

		getTaskData: function()
		{
			return this.option('data').TASK;
		},
		getTaskActions: function()
		{
			return this.getTaskData().ACTION;
		},

		initReplication: function()
		{
		},

		initProjectDependence: function()
		{
			var inst = BX.Tasks.Util.Dispatcher.get('projectdependence-'+this.id());

			inst.assignCalendar(this.getCalendar());
			inst.option('task', {data: this.getTaskData()});
			inst.load(
				this.getTaskData().SE_PROJECTDEPENDENCE,
				this.getTaskActions().SE_PROJECTDEPENDENCE
			);
		},

		initEstimateTime: function()
		{
			var times = this.controlAll('estimate-time');
			for(var k = 0; k < times.length; k++)
			{
				BX.Tasks.Util.bindInstantChange(times[k], BX.delegate(this.onEstimateTimeChange, this));
			}

			// set initial numbers

			var taskData = this.getTaskData();

			if(typeof taskData.TIME_ESTIMATE != 'undefined')
			{
				var estimate = parseInt(taskData.TIME_ESTIMATE);
				if(!isNaN(estimate) && estimate > 0)
				{
					var hours = Math.floor(estimate / 3600);

					if(hours > 0)
					{
						estimate -= hours*3600;
						var estHour = this.control('estimate-time-hour');

						if(BX.type.isElementNode(estHour))
						{
							estHour.value = hours;
						}
					}

					var estMin = this.control('estimate-time-minute');

					if(BX.type.isElementNode(estMin))
					{
						estMin.value = Math.floor(estimate / 60);
					}
				}
			}
		},

		initProjectPlan: function()
		{
            this.instances.projectPlan = new BX.Tasks.Shared.Form.ProjectPlan({
                scope: this.control('date-plan-manager'),
                parent: this,
	            matchWorkTime: this.getTaskData().MATCH_WORK_TIME == 'Y'
            });
			this.instances.projectPlan.bindEvent('change-deadline', BX.delegate(function(stamp){
				// fire event on reminder block, if any
				BX.Tasks.Util.Dispatcher.fireEvent(
					'reminder-'+this.id(),
					'setTaskDeadLine',
					[stamp]
				);
			}, this));
		},

		initProject: function()
		{
			var ctrlName = 'project';
			var project = new BX.Tasks.Component.Task.GroupItemSet({
				id: ctrlName+'-'+this.id(),
				min: 0,
				max: 1,
				parent: this,
                itemFx: 'horizontal',
                itemFxHoverDelete: true
			});
			project.bindEvent('change', BX.delegate(function(items){

				this.control('project-input').value = items.length > 0 ? parseInt(items[0]) : '';

			}, this));
			if(this.getTaskData().SE_PROJECT)
			{
				project.load([this.getTaskData().SE_PROJECT]);
			}

			this.instances[ctrlName] = project;
		},

		initResponsible: function()
		{
			var ctrlName = 'responsible';

			var parameters = {
				id: ctrlName+'-'+this.id(),
                nameTemplate: this.option('template').NAME_TEMPLATE,
				min: 1,
                parent: this,
                itemFx: 'horizontal',
                itemFxHoverDelete: true,
				useAdd: this.option('componentData').MODULES.mail
			};
			if(this.opts.template.EDIT_MODE)
			{
				parameters.max = 1;
			}

            var selector = new BX.Tasks.Component.Task.UserItemSet(parameters);
            selector.bindEvent('change', BX.delegate(this.onResponsibleChange, this));

            this.instances[ctrlName] = selector;

            selector.load(this.getTaskData().SE_RESPONSIBLE);
		},

		initOriginator: function()
		{
			var ctrlName = 'originator';

            var selector = new BX.Tasks.Component.Task.UserItemSet({
				id: ctrlName+'-'+this.id(),
                nameTemplate: this.option('template').NAME_TEMPLATE,
				min: 1,
				max: 1,
                parent: this,
                itemFx: 'horizontal',
                itemFxHoverDelete: true,
			});
			selector.bindEvent('change', BX.delegate(this.onOriginatorChange, this));
            selector.load([this.getTaskData().SE_ORIGINATOR]);

            this.instances[ctrlName] = selector;
		},

		initAccomplice: function()
		{
			this.instances['accomplice'] = new BX.Tasks.UserItemSet({
				id: 'accomplice-'+this.id(),
                nameTemplate: this.option('template').NAME_TEMPLATE,
				selectorCode: 'accomplice',
                parent: this,
                itemFx: 'horizontal',
                itemFxHoverDelete: true,
				useAdd: this.option('componentData').MODULES.mail
			});
			this.instances['accomplice'].load(
				this.getTaskData().SE_ACCOMPLICE
			);
		},

		initAuditor: function()
		{
			this.instances['auditor'] = new BX.Tasks.UserItemSet({
				id: 'auditor-'+this.id(),
                nameTemplate: this.option('template').NAME_TEMPLATE,
                parent: this,
                itemFx: 'horizontal',
                itemFxHoverDelete: true,
				useAdd: this.option('componentData').MODULES.mail
			});
			this.instances['auditor'].load(
				this.getTaskData().SE_AUDITOR
			);
		},

		initTag: function()
		{
			this.instances['tag'] = new BX.Tasks.Component.Task.TagItemSet({
				id: 'tag-'+this.id(),
				tagPreId: 'tag-pre-'+this.id(),
                itemFx: 'horizontal',
                itemFxHoverDelete: true
			});
			this.instances['tag'].parent(this);
			this.instances['tag'].load(
				this.getTaskData().SE_TAG
			);
		},

		initParentTask: function()
		{
			var ctrlName = 'parenttask';
			var parent = new BX.Tasks.Component.Task.TaskItemSet({
				id: ctrlName+'-'+this.id(),
				max: 1,
				selectorCode: ctrlName,
				itemFx: 'horizontal',
				itemFxHoverDelete: true,
				parent: this
			});
			parent.bindEvent('change', BX.delegate(function(items){

				this.control('parent-input').value = items.length > 0 ? parseInt(items[0]) : '';

			}, this));
			if(this.getTaskData().SE_PARENTTASK)
			{
				parent.load([this.getTaskData().SE_PARENTTASK]);
			}

			this.instances[ctrlName] = parent;
		},

		initRelatedTask: function()
		{
			this.instances['dependson'] = new BX.Tasks.Component.Task.TaskItemSet({
				id: 'dependson-'+this.id(),
				selectorCode: 'dependson',
                itemFx: 'horizontal',
                itemFxHoverDelete: true,
				parent: this
			});

			if(typeof this.getTaskData().SE_RELATEDTASK != 'undefined')
			{
				this.instances['dependson'].load(this.getTaskData().SE_RELATEDTASK);
			}
		},

		getCheckList: function()
		{
			return BX.Tasks.Util.Dispatcher.get('checklist-'+this.id());
		},

		initCheckList: function()
		{
			var checklist = this.getCheckList();
			if(checklist !== null)
			{
				checklist.load(
					this.getTaskData().SE_CHECKLIST
				);
			}
		},

		initReminder: function()
		{
			var reminder = BX.Tasks.Util.Dispatcher.get('reminder-'+this.id());
			if(reminder !== null)
			{
				reminder.load(
					this.getTaskData().SE_REMINDER,
					this.getTaskActions().SE_REMINDER
				);
				reminder.setTaskId(this.getTaskData().ID);
				reminder.setTaskDeadLine(this.getTaskData().DEADLINE);
			}
		},

		initState: function()
		{
			this.vars.state = BX.clone(this.option('state'));
			this.redrawState();
		},

		onAllowTimeTrackingChange: function(node)
		{
            BX.Tasks.Util.fadeToggleByClass(this.control('timeman-estimate-time'), 200);
		},

		onPinFooterClick: function()
		{
			var pinned = !this.vars.state.FLAGS.FORM_FOOTER_PIN;
			var footer = this.control('footer');

			if(footer)
			{
				BX[pinned ? 'addClass' : 'removeClass'](footer, 'pinned');
			}
			this.setState('FLAGS', 'FORM_FOOTER_PIN', false, pinned);
		},

		onReplicationToggle: function(node)
		{
			if (node.checked)
			{
				this.changeCSSFlag('mode-replication-off', !node.checked, this.control('replication-block'));
				BX.Tasks.Util.fadeSlideToggleByClass(this.control('replication-panel'));
			}
			else
			{
				BX.Tasks.Util.fadeSlideToggleByClass(
					this.control('replication-panel'),
					300,
					BX.proxy(function() {
						this.changeCSSFlag('mode-replication-off', !node.checked, this.control('replication-block'));
					}, this)
				);
			}

		},

		onEstimateTimeChange: function()
		{
			var hControl = 	this.control('estimate-time-hour');
			var mControl = 	this.control('estimate-time-minute');
			var sControl =	this.control('estimate-time-second');

			if(!BX.type.isElementNode(sControl))
			{
				return;
			}

			var hour = 0;
			if(BX.type.isElementNode(hControl))
			{
				var value = parseInt(hControl.value);

				if(!isNaN(value))
				{
					hour = value;
				}
			}

			var minute = 0;
			if(BX.type.isElementNode(mControl))
			{
				var value = parseInt(mControl.value);

				if(!isNaN(value))
				{
					minute = value;
				}
			}

			// seconds
			sControl.value = minute * 60 + hour * 3600;
		},

		onPriorityChange: function(node)
		{
			var input = this.control('priority');
			if(BX.type.isElementNode(input))
			{
				input.value = node.checked ? this.PRIORITY_HIGH : this.PRIORITY_AVERAGE;
			}
		},

		onForumSubmit: function()
		{
			var csrf = this.control('csrf');
			if(csrf)
			{
				csrf.value = BX.bitrix_sessid();
			}
		},

		onFormKeyDown: function(e)
		{
			e = e || window.event;

			var prevent = false;
			if(BX.Tasks.Util.isEnter(e))
			{
				if(e.ctrlKey || e.metaKey)
				{
					this.control('form').submit();
					prevent = true;
				}
			}

			if(prevent)
			{
				BX.PreventDefault(e);
			}
		},

		onChooseBlock: function(node)
		{
			var chosenContainer = this.control('chosen-blocks');
			var unChosenContainer = this.control('unchosen-blocks');

			if(!BX.type.isElementNode(chosenContainer) || !BX.type.isElementNode(unChosenContainer))
			{
				return;
			}

			var target = BX.data(node, 'target');
			if(typeof target != 'undefined' && BX.type.isNotEmptyString(target))
			{
				var node = this.control(target);
				var blockName = BX.data(node, 'block-name');

				if(BX.type.isNotEmptyString(blockName) && BX.type.isElementNode(node))
				{
					var stateBlock = this.vars.state['BLOCKS'][blockName];

					if(typeof stateBlock.C != 'undefined')
					{
						var toPin = !stateBlock.C;

						// find block exact place
						var to = this.control(target+'-place', toPin ? chosenContainer : unChosenContainer);
                        var from = this.control(target+'-place', toPin ? unChosenContainer : chosenContainer);
						if(to) // if there is an exact place, relocate to it
						{
                            BX.Tasks.Util.fadeSlideToggleByClass(from, 200, function(){
                                BX.addClass(to, 'invisible');
                                BX.append(node, to);
                                BX.Tasks.Util.fadeSlideToggleByClass(to, 200);

                                BX.removeClass(from, 'invisible');
                            });
						}
						else // static block, then just pin it
						{
							BX.toggleClass(node, 'pinned');
						}

						// update state
						this.setState('BLOCKS', blockName, 'C', !stateBlock.C);
					}
				}
			}
		},

		onToggleAdditionalBlock: function(node)
		{
			var opened = BX.hasClass(node, 'opened');
			BX.toggleClass(node, 'opened');

			this.toggleBlock('unchosen-blocks');
		},

		onToggleBlock: function(node)
		{
			var target = BX.data(node, 'target');

			if(typeof target != 'undefined' && BX.type.isNotEmptyString(target))
			{
				var way = this.toggleBlock(target);

				if(way && target == 'checklist') // pre-open checklist add form on empty checklist
				{
					var checklist = this.getCheckList();
					if(checklist)
					{
						if(!checklist.count())
						{
							checklist.newItemOpenForm();
						}
					}
				}
			}
		},

		toggleBlock: function(target, duration)
		{
			return BX.Tasks.Util.fadeSlideToggleByClass(this.control(target), duration || 100);
		},

		onToggleFlag: function(node)
		{
			var target = BX.data(node, 'target');
			if(typeof target != 'undefined' && BX.type.isNotEmptyString(target))
			{
				var flagNode = this.control(target);
				var flagName = BX.data(node, 'flag-name');

				if(BX.type.isElementNode(flagNode))
				{
					flagNode.value = node.checked ? 'Y' : 'N';
				}

				this.setState('FLAGS', flagName, false, node.checked);
			}
		},

		onOriginatorChange: function(items)
		{
			if(!items.length)
			{
				return;
			}

			var userMatch = this.getUser().DATA.ID.toString() == items[0].toString();
			BX.Tasks.Util[userMatch ? 'enable' : 'disable'](this.control('option-add2timeman'));

			var optionLabel = this.control('option-add2timeman-label');

			if(optionLabel)
			{
				optionLabel[userMatch ? 'removeAttribute' : 'setAttribute']('title', BX.message('TASKS_TASK_COMPONENT_TEMPLATE_NO_ADD2TIMEMAN'));
			}
		},

		onResponsibleChange: function(items)
		{
			var inst = this.instances['responsible'];

			// first item to real responsible
			var itemFirst = inst.getItemFirst();
			var realRespControl = this.control('real-responsible');
			if(BX.type.isElementNode(realRespControl) && itemFirst != null)
			{
				realRespControl.value = itemFirst.value();
			}

			if(items.length > 1)
			{
				BX.Tasks.Util.hintManager.show(
					this.instances['responsible'].scope(),
					BX.message('TASKS_TASK_COMPONENT_TEMPLATE_MULTIPLE_RESPONSIBLE_NOTICE'),
					false,
					'TASK_EDIT_MULTIPLE_RESPONSIBLES',
					{closeLabel: BX.message('TASKS_TASK_COMPONENT_TEMPLATE_CLOSE_N_DONT_SHOW')}
				);
			}
			else
			{
				BX.Tasks.Util.hintManager.hide('TASK_EDIT_MULTIPLE_RESPONSIBLES');
			}
		},

		onCancelButtonClick: function(e)
		{
			if(this.option('cancelActionIsEvent')) // let iframe popup close window, dont go to url
			{
				BX.Tasks.Util.fireGlobalTaskEvent('NOOP', {}, {STAY_AT_PAGE: false});
				BX.PreventDefault(e);
			}
		},

		onWorktimeChange: function(node)
		{
			this.instances.projectPlan.setMatchWorkTime(node.checked);
		},

		getCalendar: function()
		{
			if(this.instances.calendar == false)
			{
				this.instances.calendar = new BX.Tasks.Calendar(BX.Tasks.Calendar.adaptSettings(this.option('auxData').COMPANY_WORKTIME));
			}

			return this.instances.calendar;
		},

		getState: function(type, name, actionName)
		{
			if (type == 'BLOCKS') {
				return this.vars.state[type][name][actionName];
			}
			if (type == 'FLAGS') {
				return this.vars.state[type][name];
			}
		},

		setState: function(type, name, actionName, value)
		{
			if(!BX.type.isNotEmptyString(name))
			{
				return;
			}

			if(typeof this.vars.state[type] == 'undefined')
			{
				this.vars.state[type] = {};
			}
			if(typeof this.vars.state[type][name] == 'undefined')
			{
				this.vars.state[type][name] = {};
			}

			if(type == 'BLOCKS')
			{
				this.vars.state[type][name][actionName] = value;
			}
			if(type == 'FLAGS')
			{
				this.vars.state[type][name] = value;
			}

			this.submitState();
			this.redrawState(); // for submitting with form
		},

		submitState: function()
		{
			if(!this.instances.query)
			{
				this.instances.query = new BX.Tasks.Util.Query({
					url : this.option('template').COMPONENTURL,
                    autoExec: true,
                    autoExecDelay: 1500
				});
			}

			var st = BX.clone(this.vars.state);

			// send FORM_FOOTER_PIN, but dont send other flags in this manner, it looks pretty awkward
			var fp = st.FLAGS.FORM_FOOTER_PIN;
			delete(st.FLAGS);
			st.FLAGS = {
				FORM_FOOTER_PIN: fp
			};

			this.instances.query.add('this.setstate', {state: st});
		},

		redrawState: function()
		{
			var container = this.control('state');
			if(BX.type.isElementNode(container))
			{
				var html = '';

				if(typeof this.vars.state['BLOCKS'] != 'undefined')
				{
					for(var bName in this.vars.state['BLOCKS'])
					{
						var opened = this.vars.state['BLOCKS'][bName]['O'];
						var chosen = this.vars.state['BLOCKS'][bName]['C'];

						if(typeof opened != 'undefined')
						{
							html += this.getHTMLByTemplate('state-block', {
								NAME: bName,
								TYPE: 'O',
								VALUE: opened === true || opened === 'true' ? '1' : '0'
							});
						}
						if(typeof chosen != 'undefined')
						{
							html += this.getHTMLByTemplate('state-block', {
								NAME: bName,
								TYPE: 'C',
								VALUE: chosen === true || chosen === 'true' ? '1' : '0'
							});
						}
					}
				}

				if(typeof this.vars.state['FLAGS'] != 'undefined')
				{
					for(var fName in this.vars.state['FLAGS'])
					{
						var checked = this.vars.state['FLAGS'][fName];

						html += this.getHTMLByTemplate('state-flag', {
							NAME: fName,
							VALUE: checked === true || checked === 'true' ? '1' : '0'
						});
					}
				}

				container.innerHTML = html;
			}
		}
	}
});

BX.Tasks.Component.Task.UserItemSet = BX.Tasks.UserItemSet.extend({
    methods: {

        onSearchBlurred: function()
        {
            if(this.callMethod(BX.Tasks.UserItemSet, 'onSearchBlurred'))
            {
	            this.restoreKept();
            }
        },

        restoreKept: function()
        {
            if(this.vars.toDelete)
            {
                this.addItem(this.vars.toDelete, {checkRestrictions: false});
                this.vars.toDelete = false;
            }
        },

        onSelectorItemSelected: function(data)
        {
            var value = this.extractItemValue(data);

            if(!this.hasItem(value))
            {
                var max = this.option('max');

	            this.addItem(data);
	            this.vars.toDelete = false;

                if(max == 1)
                {
	                this.instances.selector.close();
                    this.onSearchBlurred();
                }
            }

            this.resetInput();
        },

        openAddForm: function(node, e, keepValue)
        {
            var min = this.option('min');
            var max = this.option('max');

            if(keepValue || (max == 1 && (min == 0 || min == 1)))
            {
                var first = this.getItemFirst();
                if(first)
                {
                    this.vars.toDelete = first.data();
                    this.callMethod(BX.Tasks.UserItemSet, 'deleteItem', [first.value(), {checkRestrictions: false}]);
                }
            }

            this.callMethod(BX.Tasks.UserItemSet, 'openAddForm');
        },

        deleteItem: function(value)
        {
            if(!this.callMethod(BX.Tasks.UserItemSet, 'deleteItem', arguments))
            {
                this.openAddForm(false, false, true);
	            return false;
            }

	        return true;
        }
    }
});

BX.Tasks.Component.Task.GroupItemSet = BX.Tasks.Component.Task.UserItemSet.extend({
    sys: {
        code: 'group-item-set'
    },
    methods: {
        extractItemDisplay: function(data)
        {
            return data.NAME || BX.util.htmlspecialcharsback(data.nameFormatted); // socnetlogdest returns escaped name, we want unescaped
        },
        getNSMode: function()
        {
            return 'group';
        }
    }
});

// legacy popup - task selector
BX.Tasks.Component.Task.TaskItemSet = BX.Tasks.PopupItemSet.extend({
	sys: {
		code: 'task-item-set'
	},
    options: {
        itemFx: 'horizontal'
    },
	methods: {
		construct: function()
		{
			this.callConstruct(BX.Tasks.PopupItemSet);

			this.instances.selector = window['O_'+this.option('selectorCode')];
		},
		extractItemDisplay: function(data)
		{
			if(typeof data.DISPLAY != 'undefined')
			{
				return data.DISPLAY;
			}

			if(typeof data.name != 'undefined')
			{
				return data.name;
			}

			return data.TITLE;
		},
		extractItemValue: function(data)
		{
			return (typeof data.ID == 'undefined' ? data.id : data.ID);
		},
		bindFormEvents: function()
		{
			if(typeof this.instances.selector != 'undefined' && this.instances.selector != null && this.instances.selector != false)
			{
				BX.addCustomEvent(this.instances.selector, 'on-change', BX.delegate(this.itemsChanged, this));

				if(typeof this.instances.window != 'undefined')
				{
					var selectorCtrl = this.instances.selector;
					BX.addCustomEvent(this.instances.window, "onAfterPopupShow", function(){
						setTimeout(function(){
							selectorCtrl.searchInput.focus();
						}, 100);
					});
				}
			}
		},
		deleteItem: function(value, parameters)
		{
			// todo: in some cases we got numeric in value, in other cases - object. re-check it and unify
			var taskId = (BX.type.isNumber(value) || BX.type.isString(value)) ? value : value.value();

			if(this.callMethod(BX.Tasks.PopupItemSet, 'deleteItem', arguments))
			{
				this.instances.selector.unselect(taskId);
			}
		}
	}
});

// tag selector
BX.Tasks.Component.Task.TagItemSet = BX.Tasks.Util.ItemSet.extend({
	sys: {
		code: 'tag-item-set'
	},
	options: {
		itemFx: 'horizontal'
	},
	methods: {

		bindEvents: function()
		{
			this.callMethod(BX.Tasks.Util.ItemSet, 'bindEvents');

			BX.addCustomEvent(window, 'onTaskTagSelectAlt', BX.delegate(this.onTagsChange, this));
		},

		onTagsChange: function(tags)
		{
			// add new
			for(var k = 0; k < tags.length; k++)
			{
				var tag = {NAME: tags[k]};
				this.addItem(tag);
			}

			// delete deleted
			this.each(function(item){
				if(!BX.util.in_array(item.display(), tags))
				{
					this.deleteItem(item.value());
				}
			});
		},

		openAddForm: function(node)
		{
			if(!window.tasksTagsPopUp)
			{
				BX.debug('tasksTagsPopUp is not defined');
				return;
			}

			window.tasksTagsPopUp.popupWindow.setBindElement(node);
			window.tasksTagsPopUp.showPopup();
		},

		extractItemDisplay: function(data)
		{
			return data.NAME;
		},
		extractItemValue: function(data)
		{
			return BX.util.hashCode(data.NAME);
		}
	}
});