;(function() {
	var BX = window.BX;
	if (BX && BX["Mobile"] && BX["Mobile"]["Tasks"] && BX["Mobile"]["Tasks"]["edit"])
		return;
	BX.namespace("BX.Mobile.Tasks.edit");
	var counter = 0,
		getId = function(){ return 'TaskEdit' + (++counter) + BX.util.getRandomString(); },
		initCheckList = (function () {
		var d = function(id, checkList) {
			this.clickAdd = BX.delegate(this.clickAdd, this);
			this.clickSeparator = BX.delegate(this.clickSeparator, this);
			this.clickMenu = BX.delegate(this.clickMenu, this);
			this.callback = BX.delegate(this.callback, this);
			var ii;
			this.taskId = id;
			checkList = (checkList || []);
			for (ii = 0; ii < checkList.length; ii++)
			{
				this.bindItem(checkList[ii]);
			}
			this.container = BX("checkList" + id + "Container");
			if (this.container && BX("checkList" + id + "Add"))
			{
				this.canAdd = true;
				BX.bind(BX("checkList" + id + "Add"), "click", this.clickAdd);
				if (BX("checkList" + id + "Separator"))
				{
					BX.bind(BX("checkList" + id + "Separator"), "click", this.clickSeparator);
				}
			}
		};
		d.prototype = {
			canAdd : false,
			container : null,
			bindItem : function(id) {
				if (BX("checkListItem" + id + "Menu"))
					BX.bind(BX("checkListItem" + id + "Menu"), "click", BX.proxy(function(e){ this.clickMenu(e, id); }, this));
				var checkbox = BX("checkListItem" + id),
					node = BX("checkListItem" + id + 'Label');

				if (BX.hasClass(node, "task-view-checklist-toggle"))
				{
					node.setAttribute("bx-toggle", "Y");
					checkbox.setAttribute("bx-toggle", "Y");
				}
				if (BX.hasClass(node, "task-view-checklist-modify"))
				{
					node.setAttribute("bx-modify", "Y");
					checkbox.setAttribute("bx-modify", "Y");
				}
				if (BX.hasClass(node, "task-view-checklist-remove"))
				{
					node.setAttribute("bx-remove", "Y");
					checkbox.setAttribute("bx-remove", "Y");
				}
				if (BX.findParent(checkbox, {tagName : 'span', className : 'mobile-grid-field-divider'}, node))
				{
					node.setAttribute("bx-separator", "Y");
					checkbox.setAttribute("bx-separator", "Y");
				}
				if (node.hasAttribute("bx-toggle"))
					BX.bind(checkbox, "click", BX.proxy(function(){ this.fireEvent(id, "toggle", {}); }, this));
				else
					BX.bind(checkbox, "click", BX.proxy(function(e) { return BX.eventCancelBubble(e); }, this));
				this.sort = checkbox.form.elements['data[SE_CHECKLIST][' + id + '][SORT_INDEX]'].value;
			},
			clickMenu : function(e, id) {
				var checkbox = BX("checkListItem" + id),
					node = BX("checkListItem" + id + 'Label'),
					buttons = [];
				if (!node.hasAttribute("bx-separator"))
				{
					if (node.hasAttribute("bx-toggle"))
						buttons.push({
							title: checkbox.checked ? BX.message("MB_TASKS_TASK_UNCHECK") : BX.message("MB_TASKS_TASK_CHECK"),
							callback: BX.delegate(function() {
								checkbox.checked = (!checkbox.checked);
								this.fireEvent(id, "toggle", {});
							}, this)
						});
					if (node.hasAttribute("bx-modify"))
						buttons.push({
							title: BX.message("MB_TASKS_TASK_EDIT"),
							callback: BX.delegate(function() {
								var title = BX.findChild(node, {tagName : "INPUT", attribute : {type : "hidden", name : 'data[SE_CHECKLIST][' + id + '][TITLE]' }}, true);
								if (title)
									this.show(title.value, id);
							}, this)
						});
				}
				if (node.hasAttribute("bx-remove"))
					buttons.push({
						title: BX.message("MB_TASKS_TASK_DELETE"),
						callback: BX.delegate(function() {
							this.fireEvent(id, "remove", {});
							BX.remove(node);
						}, this)
					});
				if (buttons.length > 0)
					(new window.BXMobileApp.UI.ActionSheet( { buttons : buttons }, "textPanelSheet" )).show();
				return BX.PreventDefault(e);
			},
			counter : 0,
			sort : 0,
			clickSeparator :  function(e) {
				if (this.canAdd)
					this.callback({text : '===', extraData : { id : 'n' + (this.counter++)} }, {separator : true});
				return (e ? BX.PreventDefault(e) : false);
			},
			clickAdd : function(e) {
				if (this.canAdd)
					this.showAdd('n' + (this.counter++));
				return (e ? BX.PreventDefault(e) : false);
			},
			showAdd : function(id) {
				var node = BX.create('LABEL', {
						attrs : {
							id : 'checkListItem' + id + 'Label',
							className : "edit"
						},
						html : [
							'<span class="mobile-grid-field-tasks-checklist-item">',
								'<span class="mobile-grid-field-tasks-checklist-item-text">&nbsp;</span>',
								'<input type="text" id="checkListItem', id, 'Text" value="" placeholder="', BX.message("MB_TASKS_TASK_CHECKLIST_PLACEHOLDER"),'"/>',
							'</span>'
						].join("")
					});

				this.container.appendChild(node);

				var counter = 0,
					f = BX.proxy(function(id){
					if (counter > 100)
						return;
					counter++;

					if (BX('checkListItem' + id + 'Text')) {
						BX.bind(BX('checkListItem' + id + 'Text'), "blur", BX.proxy(function () {
							if (BX('checkListItem' + id + 'Text'))
							{
								var text = BX('checkListItem' + id + 'Text').value,
									node = BX('checkListItem' + id + 'Label');
								if (BX.type.isNotEmptyString(text))
									this.callback({text : text, extraData: { id : id }}, {replaceNode : BX('checkListItem' + id + 'Label')});
								else if (node && node.parentNode)
									node.parentNode.removeChild(node);
							}
						}, this));
						BX.bind(BX('checkListItem' + id + 'Text'), "keyup", BX.proxy(function (e) {
							if (e.keyCode == 13)
							{
								var text = BX('checkListItem' + id + 'Text').value,
									node = BX('checkListItem' + id + 'Label');
								if (BX.type.isNotEmptyString(text))
									setTimeout(BX.proxy(this.clickAdd, this), 100);
								else if (node && node.parentNode)
									node.parentNode.removeChild(node);
							}
						}, this));

						setTimeout(function(){BX.focus(BX('checkListItem' + id + 'Text'))}, 100);
					}
					else { setTimeout(function(){ f(id); }, 100); }
				}, this);
				f(id);
			},
			show : function(value, id) {
				window.app.exec('showPostForm', {
					attachButton : null,
					attachedFiles : null,
					extraData: {
						id : id
					},
					mentionButton: null,
					smileButton: null,
					message : { text : BX.util.htmlspecialcharsback(value) },
					okButton: {
						callback: this.callback,
						name: BX.message("interface_form_save")
					},
					cancelButton : {
						callback : function(){},
						name : BX.message("interface_form_cancel")
					}
				});
			},
			callback: function(data, params) {
				data.text = (BX.util.htmlspecialchars(data.text) || '');
				params = (params || {});
				var id = (data.extraData.id),
					node, checked = false,
					replaceNode = params.replaceNode,
					separator = params.separator;
				if (BX('checkListItem' + id))
				{
					node = BX("checkListItem" + id + "Label");
					BX.removeClass(node, "edit");
					checked = BX('checkListItem' + id).checked;
				}
				else
				{
					node = BX.create('LABEL', {attrs : {
						"for" : 'checkListItem' + id,
						id : 'checkListItem' + id + 'Label',
						className : "task-view-checklist task-view-checklist-toggle task-view-checklist-modify task-view-checklist-remove"
					}});
					if (BX(replaceNode))
					{
						replaceNode.parentNode.replaceChild(node, replaceNode);
					}
					else
					{
						this.container.appendChild(node);
					}
				}

				node.innerHTML = [
						'<span class="', (separator ? 'mobile-grid-field-divider' : 'mobile-grid-field-tasks-checklist-item'), '">',
							'<input type="hidden" name="data[SE_CHECKLIST][', id, '][ID]" value="', id, '" />',
							'<input type="checkbox" name="data[SE_CHECKLIST][', id, '][IS_COMPLETE]" id="checkListItem', id, '"', (checked ? " checked " : ""), ' value="Y" />',
							(separator ? '' : '<span class="mobile-grid-field-tasks-checklist-item-text">' + data.text + '</span>'),
							'<i class="mobile-grid-menu" id="checkListItem', id, 'Menu"></i>',
							'<input type="hidden" name="data[SE_CHECKLIST][', id, '][TITLE]" value="', data.text, '" />',
							'<input type="hidden" name="data[SE_CHECKLIST][', id, '][SORT_INDEX]" value="', (data.sort || (++this.sort)), '" />',
						'</span>'
					].join("");
				var counter = 0,
					f = BX.proxy(function(id){
					if (counter > 100)
						return;
					counter++;
					if (BX('checkListItem' + id + 'Menu')) {
						this.bindItem(id);
						this.fireEvent(id, "modify", params);
					}
					else { setTimeout(function(){ f(id); }, 100); }
				}, this);
				f(id);
			},
			fireEvent : function(id, eventName, data) {
				BX.onCustomEvent(this, "onChange", [this, BX("checkListItem" + id), eventName, data]);
			}
		};
		return d;
		})(),
		initCheckListView = (function () {
			var d = function(select, eventNode, container) {
				initCheckListView.superclass.constructor.apply(this, arguments);
				this.actCallback = BX.delegate(this.actCallback, this);
			};
			BX.extend(d, initCheckList);
			d.prototype.ids = {};
			d.prototype.queue = [];
			d.prototype.getId = function(id) {
				return (this.ids[id] || id);
			};
			d.prototype.fireEvent = function(id, eventName, params) {
				this.queue.push([BX.proxy(function(){
					var node = BX("checkListItem" + id);
					if (node && node.form)
					{
						if (eventName == "remove")
							this.remove(id);
						else if (eventName == "toggle")
							this.toggle(id, node);
						else
						{
							var data = {
								TITLE : node.form.elements["data[SE_CHECKLIST][" + id + "][TITLE]"].value,
								IS_COMPLETE : node.form.elements["data[SE_CHECKLIST][" + id + "][IS_COMPLETE]"].checked ? "Y" : "N",
								SORT_INDEX : node.form.elements["data[SE_CHECKLIST][" + id + "][SORT_INDEX]"].value
							};
							if (eventName == "modify" && (this.getId(id) + "").indexOf("n") === 0)
								this.create(id, data, params);
							else
								this.modify(id, data);
						}
					}
				}, this), arguments]);
				this.startQueue();
			};
			d.prototype.getQuery = function()
			{
				if (!this.query)
				{
					this.query = new BX.Tasks.Util.Query({url : BX.util.add_url_param(BX.message("TASK_PATH_TO_AJAX"), {act : 'checklist', id : this.taskId})});
				}

				return this.query;
			};
			d.prototype.statusQueue = "ready";
			d.prototype.startQueue = function()
			{
				if (this.statusQueue === "ready")
				{
					this.statusQueue = "busy";
					this.checkQueue();
				}
			};
			d.prototype.checkQueue = function()
			{
				var f = this.queue.shift();
				if (f && BX.type.isFunction(f[0]))
				{
					f[0].apply(this, f[1]);
				}
				else
				{
					this.statusQueue = "ready";
				}
			};
			d.prototype.actCallback = function(errors, result){
				if (errors && errors.length > 0)
				{
					for (var ii = 0; ii < errors.length; ii++)
						errors[ii] = (errors[ii]["MESSAGE"] || errors[ii]["CODE"]);
					window.app.alert({text: errors.join(". "), title : BX.message("MB_TASKS_TASK_ERROR_TITLE")});
				}
				this.checkQueue();
			};
			d.prototype.create = function(id, data, params) {
				this.
				getQuery().
				add('task.checklist.add', {data : {
						TASK_ID: this.taskId,
						TITLE: data.TITLE,
						IS_COMPLETE: data.IS_COMPLETE,
						SORT_INDEX: data.SORT_INDEX
					}}, {}, BX.proxy(function(errors, result){
					if (errors && errors.length > 0)
					{
						for (var ii = 0; ii < errors.length; ii++)
							errors[ii] = (errors[ii]["MESSAGE"] || errors[ii]["CODE"]);
						window.app.alert({text: errors.join(". "), title : BX.message("MB_TASKS_TASK_ERROR_TITLE")});
						BX.remove(BX("checkListItem" + id + "Label"));
					}
					else
					{
						this.ids[id] = result["RESULT"]["DATA"]["ID"];
					}
					this.checkQueue();
				}, this)).
				execute();
			};
			d.prototype.modify = function(id, data) {
				var realId = this.getId(id);
				this.
				getQuery().
				add(
					'task.checklist.update',
					{id: realId, data : {
						TITLE: data.TITLE
					}},
					{},
					this.actCallback).
				execute();
			};
			d.prototype.remove = function(id) {
				var realId = this.getId(id);
				this.
				getQuery().
				add(
					'task.checklist.delete',
					{id: realId},
					{},
					this.actCallback).
				execute();
			};
			d.prototype.toggle = function(id, node) {
				var realId = this.getId(id);
				this.
				getQuery().
				add(
					'task.checklist.' + (node.checked ? 'complete' : 'renew'),
					{id: realId},
					{},
					this.actCallback).
				execute();
			};
			return d;
		})(),
		titleTask = (function () {
		var d = function(node, container) {
			this.node = node;
			this.container = container;
			this.click = BX.delegate(this.click, this);
			this.callback = BX.delegate(this.callback, this);
			BX.bind(this.container.parentNode, "click", this.click);
		};
		d.prototype = {
			multiple : false,
			select : null,
			eventNode : null,
			container : null,
			showDrop : true,
			showMenu : false,
			click : function(e) {
				this.show();
				return BX.PreventDefault(e);
			},
			show : function() {
				window.app.exec('showPostForm', {
					attachButton : null,
					attachedFiles : null,
					extraData: {},
					mentionButton: null,
					smileButton: null,
					message : { text : BX.util.htmlspecialcharsback(this.node.value) },
					okButton: {
						callback: this.callback,
						name: BX.message("interface_form_save")
					},
					cancelButton : {
						callback : function(){},
						name : BX.message("interface_form_cancel")
					}
				});
			},
			callback: function(data) {
				data.text = (BX.util.htmlspecialchars(data.text) || '');
				if (data.text.length > 0)
				{
					this.container.innerHTML = data.text;
					this.node.value = data.text;
				}
				BX.onCustomEvent(this, "onChange", [this, this.node]);
			}
		};
		return d;
		})(),
		parentId = (function () {
		var d = function(id/*, parentId*/) {
			this.click = BX.delegate(this.click, this);
			this.callback = BX.delegate(this.callback, this);
			this.drop = BX.delegate(this.drop, this);
			this.node = BX("parentId" + id);
			this.container = BX("parentId" + id + "Container");
			BX.bind(BX("parentId" + id + "Add"), "click", this.click);
			this.bindItem();
		};
		d.prototype = {
			multiple : false,
			select : null,
			eventNode : null,
			container : null,
			showDrop : true,
			showMenu : false,
			click : function(e) {
				this.show();
				return BX.PreventDefault(e);
			},
			show : function() {
				window.BXMobileApp.PageManager.loadPageModal({
					url: BX.message('TASK_PATH_TO_SELECTOR') + '&multiple=false',
					bx24ModernStyle : true
				});
				BX.addCustomEvent(window, "onTasksWereSelected", this.callback);
			},
			drop : function() {
				this.container.innerHTML = BX.message("MB_TASKS_TASK_PLACEHOLDER");
				this.node.value = 0;
				BX.onCustomEvent(this, "onChange", [this, this.node]);
			},
			bindItem : function() {
				var del = BX.findChild(this.container, {tagName : "DEL"}, true);
				if (del)
					BX.bind(del, "click", this.drop)
			},
			callback : function(/*data*/) {
				BX.removeCustomEvent(window, "onTasksWereSelected", this.callback);
			}
		};
		return d;
		})(),
		duration = (function () {
				var d = function(id) {
					this.click = BX.delegate(this.click, this);
					this.callback = BX.delegate(this.callback, this);
					this.durationType = BX("durationType" + id);
					this.durationTypeLabel = BX("durationType" + id + "Label");
					BX.bind(this.durationTypeLabel, "click", this.click);
				};
				d.prototype = {
					click : function(e) {
						this.show();
						return BX.PreventDefault(e);
					},
					show : function() {
						BXMobileApp.UI.SelectPicker.show({
							callback: this.callback,
							values: [
								BX.message("MB_TASKS_TASK_SETTINGS_DURATION_PLAN_HOURS"),
								BX.message("MB_TASKS_TASK_SETTINGS_DURATION_PLAN_DAYS")
							],
							multiselect: false,
							default_value : (this.durationType.value == "hours" ? BX.message("MB_TASKS_TASK_SETTINGS_DURATION_PLAN_HOURS") : BX.message("MB_TASKS_TASK_SETTINGS_DURATION_PLAN_DAYS"))
						});
					},
					callback : function(data) {
						if (data && data.values && data.values.length > 0)
						{
							var title = data.values.pop();
							if (title == BX.message("MB_TASKS_TASK_SETTINGS_DURATION_PLAN_DAYS"))
							{
								this.durationType.value = "days";
								this.durationTypeLabel.innerHTML = BX.message("MB_TASKS_TASK_SETTINGS_DURATION_PLAN_DAYS");
							}
							else
							{
								this.durationType.value = "hours";
								this.durationTypeLabel.innerHTML = BX.message("MB_TASKS_TASK_SETTINGS_DURATION_PLAN_HOURS");
							}
						}
					}
				};
				return d;
				})();

	BX.Mobile.Tasks.edit = function(opts, nf){

		this.parentConstruct(BX.Mobile.Tasks.edit, opts);

		BX.merge(this, {
			sys: {
				classCode: 'edit'
			},
			vars: {
				id : getId()
			}
		});
		BX.merge(opts, {
			usePull : false,
			setTitle : true,
			setPullDown : false}
		);
		this.handleInitStack(nf, BX.Mobile.Tasks.edit, opts);
	};
	BX.extend(BX.Mobile.Tasks.edit, BX.Mobile.Tasks.page);
	// the following functions can be overrided with inheritance
	BX.merge(BX.Mobile.Tasks.edit.prototype, {
		// member of stack of initializers, must be defined even if do nothing
		init: function() {
			if (this.option('checkList'))
				this.elements.push(new initCheckList(this.option('taskId'), this.option('checkList')));
			else if (this.option('checkListView'))
				new initCheckListView(this.option('taskId'), this.option('checkListView'));
			if (BX("title" + this.option('taskId')))
				this.elements.push(new titleTask(BX("title" + this.option('taskId')), BX("title" + this.option('taskId') + "Container")));

			this.elements.push(new parentId(this.option('taskId'), this.option('parentId')));
			this.elements.push(new duration(this.option('taskId')));

			var init2 = BX.delegate(function(formId, gridId, obj) {
				if (formId == this.option('formId') && obj)
				{
					this.restricted = (obj["restrictedMode"]===true || obj["restrictedMode"]=="Y" );
					if (this.restricted)
					{
						var f = function() { obj.apply.apply(arguments); };
						for (var ii = 0; ii < this.elements.length; ii++)
						{
							BX.addCustomEvent(this.elements[ii], "onChange", f);
						}
					}

					BX.addCustomEvent(obj, 'onChange', function(obj, node) {
						var form = BX(formId),
							markNode = BX(formId).elements["data[MARK]"];
						if (BX(node) && node == markNode)
						{
							var pNode = BX.findParent(node, {className : "bx-tasks-task-mark"}, form);
							if (node.value == "P")
							{
								BX.removeClass(pNode, "bx-tasks-task-mark-N");
								if (!BX.hasClass(pNode, "bx-tasks-task-mark-P"))
									BX.addClass(pNode, "bx-tasks-task-mark-P");
							}
							else if (node.value == "N")
							{
								BX.removeClass(pNode, "bx-tasks-task-mark-P");
								if (!BX.hasClass(pNode, "bx-tasks-task-mark-N"))
									BX.addClass(pNode, "bx-tasks-task-mark-N");
							}
							else
							{
								BX.removeClass(pNode, "bx-tasks-task-mark-P");
								BX.removeClass(pNode, "bx-tasks-task-mark-N");
							}
						}
						if (node.name == "data[PRIORITY]")
						{
							node.nextSibling.innerHTML = BX.message("TASKS_PRIORITY_" + (node.checked ? "2" : "0"));
						}
						else if (node.name == "data[ADD_TO_FAVORITE]")
						{
							node.nextSibling.innerHTML = (node.checked ? BX.message("TASKS_FAVORITES_1") : BX.message("TASKS_FAVORITES_0"));
						}
					});
					BX.addCustomEvent(obj, "onCancel", function() {
						window.app.closeModalDialog( { } );
					});

					BX.addCustomEvent(obj, 'onSubmitForm', BX.proxy(function(obj, obForm, nullObj, res) {
						res.submit = false;
						if (!this.restricted)
							BXMobileApp.UI.Page.LoadingScreen.show();
						var formData = BX.ajax.prepareForm(obForm).data,
							data = formData.data,
							id = this.option('taskId'),
							url = BX.util.add_url_param(BX.message("TASK_PATH_TO_AJAX"), {act : "update", id : id}),
							ii, node, tmp;

						if (data["SE_AUDITOR"])
						{
							tmp = data["SE_AUDITOR"];
							data["SE_AUDITOR"] = [];
							while ((ii = tmp.pop()) && ii)
								data["SE_AUDITOR"].push({ID : ii});
						}
						if (data["SE_ACCOMPLICE"])
						{
							tmp = data["SE_ACCOMPLICE"];
							data["SE_ACCOMPLICE"] = [];
							while ((ii = tmp.pop()) && ii)
								data["SE_ACCOMPLICE"].push({ID : ii});
						}
						if (obForm.elements['ADDITIONAL[]'])
						{
							for (ii = 0; ii < obForm.elements['ADDITIONAL[]'].length; ii++)
							{
								node = obForm.elements['ADDITIONAL[]'][ii];
								data[node.value] = (node.checked ? "Y" : "N");
							}
						}
						if (this.restricted)
						{
							delete data["SE_CHECKLIST"];
						}

						var params = {id : id, userid : BX.message("USER_ID"), taskid : id, data: data};

						(new BX.Tasks.Util.Query({url: url})).add((id > 0 ? 'task.update' : 'task.add'), params, {}, {
							onExecuted : BX.proxy(function(response) {
								if (response && response.response && response.response.status == 'failed')
								{
									window.app.BasicAuth( {
										success: BX.proxy(function() {
											(new BX.Tasks.Util.Query({url: url})).
												add('task.update', params, {}, {onExecuted: this.actExecute }).
												execute();
											}, this),
										failure: function(){
											window.app.alert({text : BX.message("MB_TASKS_TASK_ERROR3"), title : BX.message("MB_TASKS_TASK_ERROR_TITLE")});

										}
									});
								}
								else
									this.actExecute.apply(this, arguments);
							}, this)}).
							execute();
					}, this));
				}
			}, this);
			BX.addCustomEvent("onInitialized", init2);
			var form = BX.Mobile.Grid.Form.getByFormId(this.option('formId'));
			init2(this.option('formId'), 'doesNotMatter', form);
		},
		actExecute : function(errorConnection, data) {
			/*
			@ errorConnection BX.Tasks.Util.Query.ErrorCollection
			 */
			BXMobileApp.UI.Page.LoadingScreen.hide();
			if (errorConnection.checkHasErrors())
			{
				var errors = [];
				for (var ii = 0; ii < errorConnection.length; ii++)
				{
					errors.push(errorConnection[ii]["MESSAGE"]);
				}
				window.app.alert({text: errors.join(". "), title : BX.message("MB_TASKS_TASK_ERROR_TITLE")});
			}
			else
			{
				window.app.onCustomEvent((this.option('taskId') > 0 ? "onTaskWasUpdated" : "onTaskWasCreated"), [this.option('taskId'), this.variable("id"), data["RESULT"]["DATA"], data]);
				if (!this.restricted)
				{
					window.app.closeModalDialog( { } );
				}
			}
		},
		////////// CLASS-SPECIFIC: free to modify in a child class
		elements : [],
		getMenu : function(){
			return [];
		}
	});
}());