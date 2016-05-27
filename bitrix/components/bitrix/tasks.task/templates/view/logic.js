BX.namespace("BX.Tasks.Component");

BX.Tasks.Component.TaskView = function(parameters)
{
	this.parameters = parameters || {};
	this.taskId = this.parameters.taskId;
	this.layout = {
		favorite: BX("task-detail-favorite"),
		switcher: BX("task-switcher"),
		switcherTabs: [],
		elapsedHours: BX("task-switcher-elapsed-hours"),
		elapsedMinutes: BX("task-switcher-elapsed-minutes"),
		createButton: BX("task-detail-create-button")
	};

	this.messages = this.parameters.messages || {};
	for (var key in this.messages)
	{
		BX.message[key] = this.messages[key];
	}

	this.paths = this.parameters.paths || {};
	this.createButtonMenu = [];

	this.query = new BX.Tasks.Util.Query();
	this.query.bindEvent("executed", BX.proxy(this.onQueryExecuted, this));

	this.initFavorite();
	this.initProject();
	this.initCreateButton();
	this.initSwitcher();
	this.initViewer();
	this.initAjaxErrorHandler();

	this.fireTaskEvent();

	this.temporalCommentFix();
};

// todo: remove when forum stops calling the same page for comment.add()
BX.Tasks.Component.TaskView.prototype.temporalCommentFix = function()
{
	BX.addCustomEvent(window, 'OnUCFormResponse', function(id, id1, obj){
		if (BX.type.isNotEmptyString(id) && id.indexOf("TASK_") === 0 && BX.proxy_context && BX.proxy_context.jsonFailure === true)
		{
			if (obj && obj["handler"] && obj.handler["oEditor"] && obj.handler.oEditor["DenyBeforeUnloadHandler"])
			{
				obj.handler.oEditor.DenyBeforeUnloadHandler();
			}
			BX.reload();
		}
	});
};

BX.Tasks.Component.TaskView.prototype.fireTaskEvent = function()
{
	if(this.parameters.eventTaskUgly != null)
	{
		BX.Tasks.Util.fireGlobalTaskEvent(this.parameters.componentData.EVENT_TYPE, {ID: this.parameters.eventTaskUgly.id}, {STAY_AT_PAGE: true}, this.parameters.eventTaskUgly);
	}
};

BX.Tasks.Component.TaskView.prototype.initCreateButton = function()
{
	BX.bind(this.layout.createButton, "click", BX.proxy(this.onCreateButtonClick, this));

	this.createButtonMenu = [
		{
			text : this.messages.addTask,
			className : "menu-popup-item-create",
			href: this.paths.newTask
		},
		{
			text : this.messages.addSubTask,
			className : "menu-popup-item-create",
			href: this.paths.newSubTask
		}
	];
};

BX.Tasks.Component.TaskView.prototype.onCreateButtonClick = function()
{
	BX.PopupMenu.show(
		"task-detail-create-button",
		this.layout.createButton,
		this.createButtonMenu,
		{
		}
	);
};

BX.Tasks.Component.TaskView.prototype.initFavorite = function()
{
	BX.bind(this.layout.favorite, "click", BX.proxy(this.onFavoriteClick, this));
};

BX.Tasks.Component.TaskView.prototype.onFavoriteClick = function()
{
	var action = BX.hasClass(this.layout.favorite, "task-detail-favorite-active") ? "task.favorite.delete" : "task.favorite.add";

	this.query.deleteAll();
	this.query.add(
		action,
		{
			taskId: this.taskId
		},
		{
			code: action
		}
	);

	this.query.execute();

	BX.toggleClass(this.layout.favorite, "task-detail-favorite-active");
};

BX.Tasks.Component.TaskView.prototype.initProject = function()
{
	var scope = BX("task-detail-group");
	if (!scope)
	{
		return;
	}

	var project = new BX.Tasks.GroupItemSet({
		scope: scope,
		useSearch: true,
		min: 0,
		max: 1
	 });

	project.load([this.parameters.project]);
	project.bindEvent("change", BX.proxy(this.onProjectChanged, this));
};

BX.Tasks.Component.TaskView.prototype.initSwitcher = function()
{
	if (!this.layout.switcher)
	{
		return;
	}

	var tabs = this.layout.switcher.getElementsByClassName("task-switcher");
	var blocks = this.layout.switcher.parentNode.getElementsByClassName("task-switcher-block");
	for (var i = 0; i < tabs.length; i++)
	{
		var tab = tabs[i];
		var block = blocks[i];
		BX.bind(tab, "click", BX.proxy(this.onSwitch, this));
		this.layout.switcherTabs.push({
			title: tab,
			block: block
		});
	}

	BX.addCustomEvent("TaskElapsedTimeUpdated", BX.proxy(function(hours, minutes) {
		this.layout.elapsedHours.innerText = hours;
		this.layout.elapsedMinutes.innerText = minutes;
	}, this));
};

BX.Tasks.Component.TaskView.prototype.onSwitch = function()
{
	var currentTitle = BX.proxy_context;
	if (BX.hasClass(currentTitle, "task-switcher-selected"))
	{
		return false;
	}

	for (var i = 0; i < this.layout.switcherTabs.length; i++)
	{
		var title = this.layout.switcherTabs[i].title;
		var block = this.layout.switcherTabs[i].block;

		if (title === currentTitle)
		{
			BX.addClass(title, "task-switcher-selected");
			BX.addClass(block, "task-switcher-block-selected");
		}
		else
		{
			BX.removeClass(title, "task-switcher-selected");
			BX.removeClass(block, "task-switcher-block-selected");
		}
	}

	return false;
};

BX.Tasks.Component.TaskView.prototype.onQueryExecuted = function(response)
{
};

BX.Tasks.Component.TaskView.prototype.onProjectChanged = function(items)
{
	var groupId = BX.type.isArray(items) && items.length ? items[0] : 0;

	this.query.deleteAll();
	this.query.add(
		"task.update",
		{
			id: this.taskId,
			data: {
				"GROUP_ID": groupId
			},
			parameters: {}
		},
		{
			code: "task.update.project"
		}
	);
	this.query.execute();
};

BX.Tasks.Component.TaskView.prototype.initViewer = function()
{
	var fileAreas = ["task-detail-description", "task-detail-files", "task-comments-block", "task-files-block"];

	for (var i = 0; i < fileAreas.length; i++)
	{
		var area = BX(fileAreas[i]);
		if (area)
		{
			top.BX.viewElementBind(
				area,
				{},
				function(node){
					return BX.type.isElementNode(node) &&
						(node.getAttribute("data-bx-viewer") || node.getAttribute("data-bx-image"));
				}
			);
		}
	}
};

BX.Tasks.Component.TaskView.prototype.initAjaxErrorHandler = function()
{
	BX.addCustomEvent("TaskAjaxError", function(errors) {
		BX.Tasks.alert(errors);
	});
};