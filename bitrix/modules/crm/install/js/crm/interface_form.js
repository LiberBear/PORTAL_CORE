function BxCrmInterfaceForm(name, aTabs)
{
	var _this = this;
	this.name = name; // is form ID
	this.aTabs = aTabs;
	this.bExpandTabs = false;
	this.vars = {};
	this.oTabsMeta = {};
	this.aTabsEdit = [];
	this.oFields = {};
	this.menu = new PopupMenu('bxFormMenu_'+this.name, 1010);
	this.settingsMenu = [];
	this.tabSettingsWnd = null;
	this.fieldSettingsWnd = null;
	this.activeTabClass = 'bx-crm-view-tab-active';
	this._form = null;
	this._isSubmitted = false;
	this._enableSigleSubmit = true;

	this.isVisibleInViewMode = true;
	var container = BX("container_" + this.name.toLowerCase());
	if(container)
	{
		this.isVisibleInViewMode = container.style.display !== "none";
	}

	this.Initialize = function()
	{
		this._form = BX("form_" + this.name);
		if(this._enableSigleSubmit)
		{
			this._submitHandler = BX.delegate(this._OnSubmit, this);
			BX.bind(this._form, 'submit', this._submitHandler);
		}

		BX.onCustomEvent(window, 'CrmInterfaceFormCreated', [ this ]);
	};

	this.EnableSigleSubmit = function(enable)
	{
		enable = !!enable;
		if(this._enableSigleSubmit === enable)
		{
			return;
		}

		if(this._enableSigleSubmit && this._submitHandler)
		{
			BX.unbind(this._form, 'submit', this._submitHandler);
			this._submitHandler = null;
		}

		this._enableSigleSubmit = enable;

		if(this._enableSigleSubmit)
		{
			this._submitHandler = BX.delegate(this._OnSubmit, this);
			BX.bind(this._form, 'submit', this._submitHandler);
		}
	};

	this.GetForm = function()
	{
		return this._form;
	};

	this._OnSubmit = function(e)
	{
		if(!this._enableSigleSubmit)
		{
			return true;
		}

		if(this._isSubmitted)
		{
			return BX.PreventDefault(e);
		}

		this._isSubmitted = true;
		window.setTimeout(BX.delegate(this._LockSubmits, this), 10);
		return true;
	};

	this._LockSubmits = function()
	{
		var saveAndViewBtn = BX(this.name + "_saveAndView");
		if(saveAndViewBtn)
		{
			saveAndViewBtn.disabled = "disabled";
		}

		var saveAndAddBtn = BX(this.name + "_saveAndAdd");
		if(saveAndAddBtn)
		{
			saveAndAddBtn.disabled = "disabled";
		}

		var applyBtn = BX(this.name + "_apply");
		if(applyBtn)
		{
			applyBtn.disabled = "disabled";
		}
	};

	this.GetTabs = function()
	{
		var tabs = BX.findChildren(
			BX(this.name + '_tab_block'),
			{ "tagName": "a", "className": "bx-crm-view-tab" },
			false
		);
		return tabs ? tabs : [];
	};

	this.GetActiveTabId = function()
	{
		var tabs = this.GetTabs();
		for(var i = 0; i < tabs.length; i++)
		{
			var tab = tabs[i];
			if(BX.hasClass(tab, this.activeTabClass))
			{
				return tab.id.substring((this.name + '_tab_').length);
			}
		}

		return '';
	};

	this.ShowOnDemand = function(caller)
	{
		var sectionContainer = BX.findParent(caller, { 'tagName':'DIV', 'className':'bx-crm-view-fieldset' });
		var rows = BX.findChildren(sectionContainer, { 'tagName':'tr', 'className':'bx-crm-view-on-demand' }, true);

		if(!BX.type.isArray(rows))
		{
			return;
		}

		for(var i = 0; i < rows.length; i++)
		{
			rows[i].style.display = '';
		}

		if(caller)
		{
			BX.findParent(caller, { 'tagName':'tr', 'className':'bx-crm-view-show-more' }).style.display='none';
		}
	};

	this.SelectTab = function(tab_id)
	{
		var div = BX('inner_tab_' + tab_id);

		if(!div || div.style.display != 'none')
			return;

		for (var i = 0, cnt = this.aTabs.length; i < cnt; i++)
		{
			var tab = BX('inner_tab_'+this.aTabs[i]);
			if(!tab)
				continue;

			if(tab.style.display != 'none')
			{
				this.ShowTab(this.aTabs[i], false);
				tab.style.display = 'none';
				break;
			}
		}

		this.ShowTab(tab_id, true);
		div.style.display = 'block';

		var hidden = BX(this.name+'_active_tab');
		if(hidden)
			hidden.value = tab_id;

		BX.onCustomEvent(
			window,
			'BX_CRM_INTERFACE_FORM_TAB_SELECTED',
			[this, this.name, tab_id, div]
		);
	};

	this.ShowTab = function(tab_id, on)
	{
		var id = this.name + '_tab_' + tab_id;
		var tabs = this.GetTabs();
		for(var i = 0; i < tabs.length; i++)
		{
			var tab = tabs[i];
			if(id !== tab.id)
			{
				continue;
			}

			if(on)
			{
				BX.addClass(tab, 'bx-crm-view-tab-active');
				BX.onCustomEvent(this, 'OnTabShow', [ tab_id ]);
			}
			else
			{
				BX.removeClass(tab, 'bx-crm-view-tab-active');
				BX.onCustomEvent(this, 'OnTabHide', [ tab_id ]);
			}

			break;
		}
	};

	this.HoverTab = function(tab_id, on)
	{
		var tab = document.getElementById('tab_'+tab_id);
		if(tab.className == 'bx-tab-selected')
			return;

		document.getElementById('tab_left_'+tab_id).className = (on? 'bx-tab-left-hover':'bx-tab-left');
		tab.className = (on? 'bx-tab-hover':'bx-tab');
		var tab_right = document.getElementById('tab_right_'+tab_id);
		tab_right.className = (on? 'bx-tab-right-hover':'bx-tab-right');
	};

	this.ShowDisabledTab = function(tab_id, disabled)
	{
		var tab = document.getElementById('tab_cont_'+tab_id);
		if(disabled)
		{
			tab.className = 'bx-tab-container-disabled';
			tab.onclick = null;
			tab.onmouseover = null;
			tab.onmouseout = null;
		}
		else
		{
			tab.className = 'bx-tab-container';
			tab.onclick = function(){_this.SelectTab(tab_id);};
			tab.onmouseover = function(){_this.HoverTab(tab_id, true);};
			tab.onmouseout = function(){_this.HoverTab(tab_id, false);};
		}
	};

	this.ToggleTabs = function(bSkipSave)
	{
		this.bExpandTabs = !this.bExpandTabs;

		var a = document.getElementById('bxForm_'+this.name+'_expand_link');
		a.title = (this.bExpandTabs? this.vars.mess.collapseTabs : this.vars.mess.expandTabs);
		a.className = (this.bExpandTabs? a.className.replace(/\s*bx-down/ig, ' bx-up') : a.className.replace(/\s*bx-up/ig, ' bx-down'));

		var div;
		for(var i in this.aTabs)
		{
			var tab_id = this.aTabs[i];
			this.ShowTab(tab_id, false);
			this.ShowDisabledTab(tab_id, this.bExpandTabs);
			div = document.getElementById('inner_tab_'+tab_id);
			div.style.display = (this.bExpandTabs? 'block':'none');
		}
		if(!this.bExpandTabs)
		{
			this.ShowTab(this.aTabs[0], true);
			div = document.getElementById('inner_tab_'+this.aTabs[0]);
			div.style.display = 'block';
		}
		if(bSkipSave !== true)
			BX.ajax.get('/bitrix/components'+this.vars.component_path+'/settings.php?FORM_ID='+this.name+'&action=expand&expand='+(this.bExpandTabs? 'Y':'N')+'&sessid='+this.vars.sessid);
	};

	this.SetTheme = function(menuItem, theme)
	{
		BX.loadCSS(this.vars.template_path+'/themes/'+theme+'/style.css');

		var themeMenu = this.menu.GetMenuByItemId(menuItem.id);
		themeMenu.SetAllItemsIcon('');
		themeMenu.SetItemIcon(menuItem, 'checked');

		BX.ajax.get('/bitrix/components'+_this.vars.component_path+'/settings.php?FORM_ID='+this.name+'&GRID_ID='+this.vars.GRID_ID+'&action=settheme&theme='+theme+'&sessid='+this.vars.sessid);
	};

	this.ShowSettings = function()
	{
		var bCreated = false;
		if(!window['formSettingsDialog'+this.name])
		{
			window['formSettingsDialog'+this.name] = new BX.CDialog({
				'content':'<form name="form_settings_'+this.name+'"></form>',
				'title': this.vars.mess.settingsTitle,
				'width': this.vars.settingWndSize.width,
				'height': this.vars.settingWndSize.height,
				'resize_id': 'InterfaceFormSettingWnd'
			});
			bCreated = true;
		}

		window['formSettingsDialog'+this.name].ClearButtons();
		window['formSettingsDialog'+this.name].SetButtons([
			{
				'title': this.vars.mess.settingsSave,
				'action': function()
				{
					_this.SaveSettings();
					this.parentWindow.Close();
				}
			},
			BX.CDialog.prototype.btnCancel
		]);

		window['formSettingsDialog'+this.name].Show();

		var form = document['form_settings_'+this.name];

		if(bCreated)
			form.appendChild(BX('form_settings_'+this.name));

		//editable data
		var i;
		this.aTabsEdit = [];
		for(i in this.oTabsMeta)
		{
			var fields = [];
			for(var j in this.oTabsMeta[i].fields)
				fields[fields.length] = BX.clone(this.oTabsMeta[i].fields[j]);
			this.aTabsEdit[this.aTabsEdit.length] = BX.clone(this.oTabsMeta[i]);
			this.aTabsEdit[this.aTabsEdit.length-1].fields = fields;
		}

		//tabs
		jsSelectUtils.deleteAllOptions(form.tabs);
		for(i in this.aTabsEdit)
			form.tabs.options[form.tabs.length] = new Option(this.aTabsEdit[i].name, this.aTabsEdit[i].id, false, false);

		//fields
		form.tabs.selectedIndex = 0;
		this.OnSettingsChangeTab();

		//available fields
		this.aAvailableFields = BX.clone(this.oFields);
		jsSelectUtils.deleteAllOptions(form.all_fields);
		for(i in this.aAvailableFields)
			form.all_fields.options[form.all_fields.length] = new Option(this.aAvailableFields[i].name, this.aAvailableFields[i].id, false, false);

		jsSelectUtils.sortSelect(form.all_fields);

		this.HighlightSections(form.all_fields);

		this.ProcessButtons();

		form.tabs.focus();
	};

	this.OnSettingsChangeTab = function()
	{
		var form = document['form_settings_'+this.name];
		var index = form.tabs.selectedIndex;

		jsSelectUtils.deleteAllOptions(form.fields);
		for(var i in this.aTabsEdit[index].fields)
		{
			var opt = new Option(this.aTabsEdit[index].fields[i].name, this.aTabsEdit[index].fields[i].id, false, false);
			if(this.aTabsEdit[index].fields[i].type == 'section')
				opt.className = 'bx-section';
			form.fields.options[form.fields.length] = opt;
		}

		this.ProcessButtons();
	};

	this.TabMoveUp = function()
	{
		var form = document['form_settings_'+this.name];
		var index = form.tabs.selectedIndex;

		if(index > 0)
		{
			var tab1 = BX.clone(this.aTabsEdit[index]);
			var tab2 = BX.clone(this.aTabsEdit[index-1]);
			this.aTabsEdit[index] = tab2;
			this.aTabsEdit[index-1] = tab1;
		}
		jsSelectUtils.moveOptionsUp(form.tabs);
	};

	this.TabMoveDown = function()
	{
		var form = document['form_settings_'+this.name];
		var index = form.tabs.selectedIndex;

		if(index < form.tabs.length-1)
		{
			var tab1 = BX.clone(this.aTabsEdit[index]);
			this.aTabsEdit[index] = BX.clone(this.aTabsEdit[index+1]);
			this.aTabsEdit[index+1] = tab1;
		}
		jsSelectUtils.moveOptionsDown(form.tabs);
	};

	this.TabEdit = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex < 0)
			return;

		this.ShowTabSettings(this.aTabsEdit[tabIndex],
			function()
			{
				var frm = document['tab_settings_'+_this.name];
				_this.aTabsEdit[tabIndex].name = frm.tab_name.value;
				_this.aTabsEdit[tabIndex].title = frm.tab_title.value;

				form.tabs[tabIndex].text = frm.tab_name.value;
			}
		);
	};

	this.TabAdd = function()
	{
		this.ShowTabSettings({'name':'', 'title':''},
			function()
			{
				var tab_id = 'tab_'+Math.round(Math.random()*1000000);

				var frm = document['tab_settings_'+_this.name];
				_this.aTabsEdit[_this.aTabsEdit.length] = {
					'id': tab_id,
					'name': frm.tab_name.value,
					'title': frm.tab_title.value,
					'fields': []
				};

				var form = document['form_settings_'+_this.name];
				form.tabs[form.tabs.length] = new Option(frm.tab_name.value, tab_id, true, true);
				_this.OnSettingsChangeTab();
			}
		);
	};

	this.TabDelete = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex < 0)
			return;

		//place to available fields before delete
		var i;
		for(i in this.aTabsEdit[tabIndex].fields)
		{
			this.aAvailableFields[this.aTabsEdit[tabIndex].fields[i].id] = this.aTabsEdit[tabIndex].fields[i];
			jsSelectUtils.addNewOption(form.all_fields, this.aTabsEdit[tabIndex].fields[i].id, this.aTabsEdit[tabIndex].fields[i].name, true, false);
		}

		this.HighlightSections(form.all_fields);

		this.aTabsEdit = BX.util.deleteFromArray(this.aTabsEdit, tabIndex);
		form.tabs.remove(tabIndex);

		if(form.tabs.length > 0)
		{
			i = (tabIndex < form.tabs.length? tabIndex : form.tabs.length-1);
			form.tabs[i].selected = true;
			this.OnSettingsChangeTab();
		}
		else
		{
			jsSelectUtils.deleteAllOptions(form.fields);
			this.ProcessButtons();
		}
	};

	this.ShowTabSettings = function(data, action)
	{
		var wnd = this.tabSettingsWnd;
		if(!wnd)
		{
			this.tabSettingsWnd = wnd = new BX.CDialog({
				'content':'<form name="tab_settings_'+this.name+'">'+
					'<table width="100%">'+
					'<tr>'+
					'<td width="50%" align="right">'+this.vars.mess.tabSettingsName+'</td>'+
					'<td><input type="text" name="tab_name" size="30" value="" style="width:90%"></td>'+
					'</tr>'+
					'<tr>'+
					'<td align="right">'+this.vars.mess.tabSettingsCaption+'</td>'+
					'<td><input type="text" name="tab_title" size="30" value="" style="width:90%"></td>'+
					'</tr>'+
					'</table>'+
					'</form>',
				'title': this.vars.mess.tabSettingsTitle,
				'width': this.vars.tabSettingWndSize.width,
				'height': this.vars.tabSettingWndSize.height,
				'resize_id': 'InterfaceFormTabSettingWnd'
			});
		}
		wnd.ClearButtons();
		wnd.SetButtons([
			{
				'title': this.vars.mess.tabSettingsSave,
				'action': function(){
					action();
					this.parentWindow.Close();
				}
			},
			BX.CDialog.prototype.btnCancel
		]);
		wnd.Show();

		var form = document['tab_settings_'+this.name];
		form.tab_name.value = data.name;
		form.tab_title.value = data.title;
		form.tab_name.focus();
	};

	this.ShowFieldSettings = function(data, action)
	{
		var wnd = this.fieldSettingsWnd;
		if(!wnd)
		{
			this.fieldSettingsWnd = wnd = new BX.CDialog({
				'content':'<form name="field_settings_'+this.name+'">'+
					'<table width="100%">'+
					'<tr>'+
					'<td width="50%" align="right" id="field_name_'+this.name+'"></td>'+
					'<td><input type="text" name="field_name" size="30" value="" style="width:90%"></td>'+
					'</tr>'+
					'</table>'+
					'</form>',
				'width': this.vars.fieldSettingWndSize.width,
				'height': this.vars.fieldSettingWndSize.height,
				'resize_id': 'InterfaceFormFieldSettingWnd'
			});
		}

		wnd.SetTitle(data.type && data.type == 'section'? this.vars.mess.sectSettingsTitle : this.vars.mess.fieldSettingsTitle);
		BX('field_name_'+this.name).innerHTML = (data.type && data.type == 'section'? this.vars.mess.sectSettingsName : this.vars.mess.fieldSettingsName);

		wnd.ClearButtons();
		wnd.SetButtons([
			{
				'title': this.vars.mess.tabSettingsSave,
				'action': function(){
					action();
					this.parentWindow.Close();
				}
			},
			BX.CDialog.prototype.btnCancel
		]);
		wnd.Show();

		var form = document['field_settings_'+this.name];
		form.field_name.value = data.name;
		form.field_name.focus();
	};

	this.FieldEdit = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;
		var fieldIndex = form.fields.selectedIndex;

		if(tabIndex < 0 || fieldIndex < 0)
			return;

		this.ShowFieldSettings(this.aTabsEdit[tabIndex].fields[fieldIndex],
			function()
			{
				var frm = document['field_settings_'+_this.name];
				_this.aTabsEdit[tabIndex].fields[fieldIndex].name = frm.field_name.value;

				form.fields[fieldIndex].text = frm.field_name.value;
			}
		);
	};

	this.FieldAdd = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex < 0)
			return;

		this.ShowFieldSettings({'name':'', 'type':'section'},
			function()
			{
				var field_id = 'field_'+Math.round(Math.random()*1000000);
				var frm = document['field_settings_'+_this.name];
				_this.aTabsEdit[tabIndex].fields[_this.aTabsEdit[tabIndex].fields.length] = {
					'id': field_id,
					'name': frm.field_name.value,
					'type': 'section'
				};
				var opt = new Option(frm.field_name.value, field_id, true, true);
				opt.className = 'bx-section';
				form.fields[form.fields.length] = opt;
				_this.ProcessButtons();
			}
		);
	};

	this.FieldsMoveUp = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		var n = form.fields.length;
		for(var i=0; i<n; i++)
		{
			if(form.fields[i].selected && i>0 && form.fields[i-1].selected == false)
			{
				var field1 = BX.clone(this.aTabsEdit[tabIndex].fields[i]);
				this.aTabsEdit[tabIndex].fields[i] = BX.clone(this.aTabsEdit[tabIndex].fields[i-1]);
				this.aTabsEdit[tabIndex].fields[i-1] = field1;

				var option1 = new Option(form.fields[i].text, form.fields[i].value);
				var option2 = new Option(form.fields[i-1].text, form.fields[i-1].value);
				option1.className = form.fields[i].className;
				option2.className = form.fields[i-1].className;
				form.fields[i] = option2;
				form.fields[i].selected = false;
				form.fields[i-1] = option1;
				form.fields[i-1].selected = true;
			}
		}
	};

	this.FieldsMoveDown = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		var n = form.fields.length;
		for(var i=n-1; i>=0; i--)
		{
			if(form.fields[i].selected && i<n-1 && form.fields[i+1].selected == false)
			{
				var field1 = BX.clone(this.aTabsEdit[tabIndex].fields[i]);
				this.aTabsEdit[tabIndex].fields[i] = BX.clone(this.aTabsEdit[tabIndex].fields[i+1]);
				this.aTabsEdit[tabIndex].fields[i+1] = field1;

				var option1 = new Option(form.fields[i].text, form.fields[i].value);
				var option2 = new Option(form.fields[i+1].text, form.fields[i+1].value);
				option1.className = form.fields[i].className;
				option2.className = form.fields[i+1].className;
				form.fields[i] = option2;
				form.fields[i].selected = false;
				form.fields[i+1] = option1;
				form.fields[i+1].selected = true;
			}
		}
	};

	this.FieldsAdd = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex == -1)
			return;

		var fields = this.aTabsEdit[tabIndex].fields;

		var n = form.all_fields.length, i;
		for(i=0; i<n; i++)
			if(form.all_fields[i].selected)
				fields[fields.length] = {
					'id': form.all_fields[i].value,
					'name': form.all_fields[i].text,
					'type': this.aAvailableFields[form.all_fields[i].value].type
				};

		jsSelectUtils.addSelectedOptions(form.all_fields, form.fields, false, false);
		jsSelectUtils.deleteSelectedOptions(form.all_fields);

		for(i=0, n=form.fields.length; i<n; i++)
			if(fields[i].type == 'section')
				form.fields[i].className = 'bx-section';

		this.ProcessButtons();
	};

	this.FieldsDelete = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex == -1)
			return;

		var n = form.fields.length;
		var delta = 0;
		for(var i=0; i<n; i++)
		{
			if(form.fields[i].selected)
			{
				this.aAvailableFields[form.fields[i].value] = this.aTabsEdit[tabIndex].fields[i-delta];
				this.aTabsEdit[tabIndex].fields = BX.util.deleteFromArray(this.aTabsEdit[tabIndex].fields, i-delta);
				delta++;
			}
		}

		jsSelectUtils.addSelectedOptions(form.fields, form.all_fields, false, true);
		jsSelectUtils.deleteSelectedOptions(form.fields);

		this.HighlightSections(form.all_fields);

		this.ProcessButtons();
	};

	this.ProcessButtons = function()
	{
		var form = document['form_settings_'+this.name];

		form.add_btn.disabled = (form.all_fields.selectedIndex == -1 || form.tabs.selectedIndex == -1);
		form.del_btn.disabled = form.up_btn.disabled = form.down_btn.disabled = form.field_edit_btn.disabled = (form.fields.selectedIndex == -1);
		form.tab_up_btn.disabled = form.tab_down_btn.disabled = form.tab_edit_btn.disabled = form.tab_del_btn.disabled = form.field_add_btn.disabled = (form.tabs.selectedIndex == -1);
	};

	this.HighlightSections = function(el)
	{
		for(var i=0, n=el.length; i<n; i++)
			if(this.aAvailableFields[el[i].value].type == 'section')
				el[i].className = 'bx-section';
	};

	this.SaveSettings = function()
	{
		var data = {
			'FORM_ID': this.name,
			'action': 'savesettings',
			'sessid': this.vars.sessid,
			'tabs': this.aTabsEdit
		};
		var form = document['form_settings_'+this.name];
		if(form && form['set_default_settings'])
		{
			data.set_default_settings = (form.set_default_settings.checked? 'Y':'N');
			data.delete_users_settings = (form.delete_users_settings.checked? 'Y':'N');
		}
		BX.ajax.post('/bitrix/components'+_this.vars.component_path+'/settings.php', data, function(){_this.Reload()});
	};

	this.SaveSettings = function(options)
	{
		if(!BX.type.isPlainObject(options))
		{
			options = {};
		}

		var callback = BX.type.isFunction(options['callback']) ? options['callback'] : null;
		var data =
			{
				'FORM_ID': this.name,
				'action': 'savesettings',
				'sessid': this.vars.sessid,
				'tabs': this.aTabsEdit
			};

		var form = document['form_settings_'+this.name];
		if(form && form['set_default_settings'])
		{
			data['set_default_settings'] = (form.set_default_settings.checked? 'Y':'N');
			data['delete_users_settings'] = (form.delete_users_settings.checked? 'Y':'N');
		}
		else
		{
			if(BX.type.isBoolean(options['setDefaultSettings']))
			{
				data['set_default_settings'] = options['setDefaultSettings'] ? 'Y' : 'N';
			}

			if(BX.type.isBoolean(options['deleteUserSettings']))
			{
				data['delete_users_settings'] = options['deleteUserSettings'] ? 'Y' : 'N';
			}
		}

		var url = '/bitrix/components' + _this.vars.component_path + '/settings.php';
		if(callback)
		{
			BX.ajax.post(url, data, callback);
		}
		else
		{
			BX.ajax.post(url, data, function(){ _this.Reload(); });
		}
	};

	this.EnableSettings = function(enabled, callback)
	{
		var url = '/bitrix/components' + this.vars.component_path + '/settings.php?FORM_ID=' + this.name + '&action=enable&enabled=' + (enabled? 'Y':'N') + '&sessid=' + this.vars.sessid;

		if(BX.type.isFunction(callback))
		{
			BX.ajax.get(url, callback);
		}
		else
		{
			BX.ajax.get(url, function(){ _this.Reload(); });
		}
	};
	this.Reload = function()
	{
		var ajaxId = this.vars.ajax.AJAX_ID;
		if(ajaxId != '')
		{
			var url = BX.util.remove_url_param(this.vars.current_url, 'bxajaxid');
			if(url[url.length - 1] === '?')
			{
				//remove_url_param fix
				url = url.substr(0, url.length - 1);
			}
			BX.ajax.insertToNode(url + (url.indexOf('?') < 0 ? '?' : '&') + 'bxajaxid=' + ajaxId, 'comp_' + ajaxId);
		}
		else
		{
			window.location = window.location.href;
		}
	};
	this.ReloadActiveTab = function()
	{
		var tabParamName = this.name + '_active_tab';
		var url = BX.util.remove_url_param(this.vars.current_url, tabParamName);
		if(url[url.length - 1] === '?')
		{
			//remove_url_param fix
			url = url.substr(0, url.length - 1);
		}

		url += (url.indexOf('?') < 0 ? '?' : '&') +  tabParamName + '=' + this.GetActiveTabId();

		var ajaxId = this.vars.ajax.AJAX_ID;
		if(ajaxId != '')
		{
			BX.ajax.insertToNode(url + '&bxajaxid=' + ajaxId, 'comp_' + ajaxId);
		}
		else
		{
			window.location = url;
		}
	};
	this.SetViewModeVisibility = function(visible)
	{
		visible = !!visible;
		if(this.isVisibleInViewMode === visible)
		{
			return;
		}

		this.isVisibleInViewMode = visible;

		var container = BX("container_" + this.name.toLowerCase());
		if(container)
		{
			container.style.display = this.isVisibleInViewMode ? "" : "none";
		}

		BX.userOptions.save("main.interface.form", this.name, "show_in_view_mode", visible ? "Y" : "N", false);
	};
}

BX.CmrSidebarFieldSelector = function()
{
	this._id = '';
	this._fieldId = '';
	this._currentItem = null;
	this._elem = null;
	this._settings = {};
	this._items = {};
	this._popupMenu = null;
};

BX.CmrSidebarFieldSelector.prototype =
{
	initialize: function(id, fieldId, elem, settings)
	{
		this._id = id;
		this._fieldId = fieldId;
		this._elem = elem;
		this._settings = settings;

		this._items = {};
		var opts = this.getSettings('options', null);
		if(opts)
		{
			for(var i = 0; i < opts.length; i++)
			{
				var opt = opts[i];
				if(BX.type.isNotEmptyString(opt['id']))
				{
					var optId = opt['id'];
					this._items[optId] = BX.CmrSidebarFieldSelectorItem.create(optId, this, { "text": BX.type.isNotEmptyString(opt['caption']) ? opt['caption'] : optId });
				}
			}
		}

		BX.bind(this._elem, 'click', BX.proxy(this._onElementClick, this));

		var button = BX(this.getSettings('buttonId', ''));
		if(button)
		{
			BX.bind(button, 'click', BX.proxy(this._onElementClick, this));
		}
	},
	getSettings: function(name, defaultval)
	{
		var s = this._settings;
		return  s[name] ? s[name] : defaultval;
	},
	getFieldId: function()
	{
		return this._fieldId;
	},
	getCurrentItem: function()
	{
		return this._currentItem;
	},
	setCurrentItemId: function(itemId, save)
	{
		var item = null;
		for(var key in this._items)
		{
			if(!this._items.hasOwnProperty(key))
			{
				continue;
			}

			if(this._items[key].getId() === itemId)
			{
				item = this._items[key];
			}
		}

		if(!item)
		{
			return;
		}

		this._currentItem = item;
		if(this._elem)
		{
			this._elem.innerHTML = item.getTitle();
		}

		save = !!save;
		if(save)
		{
			var editor = BX.CrmInstantEditor.getDefault();
			if(editor)
			{
				editor.saveFieldValue(this._fieldId, item.getId());
			}

			BX.CmrSidebarFieldSelector._synchronize(this);
		}

	},
	_onElementClick: function(e)
	{
		var menuItems = [];
		for(var key in this._items)
		{
			if(!this._items.hasOwnProperty(key))
			{
				continue;
			}

			var item = this._items[key].createMenuItem();
			if(item)
			{
				menuItems.push(item);
			}
		}

		BX.PopupMenu.show(
			this._id,
			this._elem,
			menuItems,
			{ "offsetTop": 0, "offsetLeft": 0 }
		);

		this._popupMenu = BX.PopupMenu.currentItem;
	},
	handleItemChange: function(item)
	{
		if(this._popupMenu && this._popupMenu.popupWindow)
		{
			this._popupMenu.popupWindow.close();
		}

		this.setCurrentItemId(item.getId(), true);
	}
};
BX.CmrSidebarFieldSelector.items = {};
BX.CmrSidebarFieldSelector.create = function(id, fieldId, elem, settings)
{
	var self = new BX.CmrSidebarFieldSelector();
	self.initialize(id, fieldId, elem, settings);
	this.items[id] = self;
	return self;
};

BX.CmrSidebarFieldSelector._synchronize = function(item)
{
	//var type = item.getEntityType();
	//var id = item.getEntityId();

	var selectedItem = item.getCurrentItem();
	if(!selectedItem)
	{
		return;
	}

	var fieldId = item.getFieldId();
	for(var itemId in this.items)
	{
		if(!this.items.hasOwnProperty(itemId))
		{
			continue;
		}

		var curItem = this.items[itemId];
		if(curItem === item)
		{
			continue;
		}

		if(fieldId === curItem.getFieldId())
		{
			curItem.setCurrentItemId(selectedItem.getId(), false);
		}
	}
};

BX.CmrSidebarFieldSelectorItem = function()
{
	this._id = '';
	this._parent = null;
	this._settings = {};
};

BX.CmrSidebarFieldSelectorItem.prototype =
{
	initialize: function(id, parent, settings)
	{
		this._id = id;
		this._parent = parent;
		this._settings = settings;
	},
	getSettings: function(name, defaultval)
	{
		var s = this._settings;
		return  s[name] ? s[name] : defaultval;

	},
	getId: function()
	{
		return this._id;
	},
	getTitle: function()
	{
		return this.getSettings('text', this._id);
	},
	createMenuItem: function()
	{
		return {
			"text":  this.getTitle(),
			"onclick": BX.proxy(this._onMenuItemClick, this)
		};
	},
	_onMenuItemClick: function()
	{
		if(this._parent)
		{
			this._parent.handleItemChange(this);
		}
	}
};

BX.CmrSidebarFieldSelectorItem.create = function(id, parent, settings)
{
	var self = new BX.CmrSidebarFieldSelectorItem();
	self.initialize(id, parent, settings);
	return self;
};

BX.CrmSidebarUserSelector = function()
{
	this._id = '';
	this._settings = {};
	this._button = null;
	this._container = null;
	this.componentName = '';
	this._componentContainer = null;
	this._componentObj = null;
	this._fieldId = '';
	this._editor = null;
	this._dlg = null;
	this._dlgDisplayed = false;
	this._userInfo = null;
	this._userInfoProvider = null;
	this._enableLazyLoad = false;
	this._isLoaded = false;
	this._serviceUrl = '';
	this._options = {};
	this._userSelectorScriptLoaded = null;
};

BX.CrmSidebarUserSelector.prototype =
{
	initialize: function(id, button, container, componentName, options)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : ('crm_sidebar_user_sel_' + Math.random());
		if(!BX.type.isElementNode(button))
		{
			throw 'BX.CrmSidebarUserSelector: button is not defined';
		}

		this._button = button;

		if(!BX.type.isElementNode(container))
		{
			throw 'BX.CrmSidebarUserSelector: container is not defined';
		}

		this._container = container;

		if(!BX.type.isNotEmptyString(componentName))
		{
			throw 'BX.CrmSidebarUserSelector: componentName is not defined';
		}
		this.componentName = componentName;

		this._options = options ? options : {};
		this._enableLazyLoad = this.getOption('enableLazyLoad', false);
		this._serviceUrl = this.getOption('serviceUrl', '');

		if(!this._enableLazyLoad)
		{
			this._componentContainer = BX(componentName + '_selector_content');
			var objName = 'O_' + componentName;
			if(window[objName])
			{
				this._componentObj = window[objName];
				this._componentObj.onSelect = BX.delegate(this._handleUserSelect, this);
				this._isLoaded = true;
			}
		}

		BX.bind(this._button, 'click', BX.delegate(this._handleButtonClick, this));

		this._fieldId = this.getStringOption('fieldId');
		this._userInfoProvider = BX.CrmUserInfoProvider.getItemById(this.getStringOption('userInfoProviderId'));

		if(this._fieldId !== '')
		{
			var editorId = this.getOption('editorId', '');
			if(editorId !== '')
			{
				var editor = BX.CrmInstantEditor.items[editorId];
				if(editor)
				{
					this._setupEditor(editor);
				}
				else
				{
					BX.addCustomEvent(
						'CrmInstantEditorCreated',
						BX.delegate(this._handleEditorCreation, this)
					);
				}
			}
		}
	},
	openDialog: function()
	{
		this._dlg = new BX.PopupWindow(
			this._id,
			this._button,
			{
				autoHide: true,
				draggable: false,
				closeByEsc: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: true },
				content : this._componentContainer,
				events:
				{
					onPopupShow: BX.delegate(
						function()
						{
							this._dlgDisplayed = true;
						},
						this
					),
					onPopupClose: BX.delegate(
						function()
						{
							this._dlgDisplayed = false;
							this._dlg.destroy();
						},
						this
					),
					onPopupDestroy: BX.delegate(
						function()
						{
							this._dlg = null;
						},
						this
					)
				}
			}
		);

		this._dlg.show();
	},
	closeDialog: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	getSetting: function(name, defaultval)
	{
		return this._settings[name] ? this._settings[name] : defaultval;
	},
	getOption: function(name, defaultval)
	{
		return this._options.hasOwnProperty(name) ? this._options[name] : defaultval;
	},
	getStringOption: function(name)
	{
		return BX.type.isNotEmptyString(this._options[name]) ? this._options[name] : '';
	},
	layout: function()
	{
		this._container.href = this._userInfo ? this._userInfo.getProfileUrl() : '#';
		var nameElem = BX.findChild(this._container, { className: "crm-detail-info-resp-name" }, true, false);
		if(nameElem)
		{
			nameElem.innerHTML = BX.util.htmlspecialchars(
				this._userInfo ? this._userInfo.getFullName() : ''
			);
		}

		var postElem = BX.findChild(this._container, { className: "crm-detail-info-resp-descr" }, true, false);
		if(postElem)
		{
			postElem.innerHTML = BX.util.htmlspecialchars(
				this._userInfo ? this._userInfo.getWorkPosition() : ''
			);
		}

		var photoElem = BX.findChild(this._container, { className: "crm-detail-info-resp-img" }, true, false);
		if(photoElem)
		{
			BX.cleanNode(photoElem, false);
			photoElem.appendChild(
				BX.create("IMG", { attrs: { src: this._userInfo ? this._userInfo.getPhotoUrl() : '' } })
			);
		}
	},
	toggleDialog: function()
	{
		if(this._dlg && this._dlgDisplayed)
		{
			this.closeDialog();
		}
		else
		{
			this.openDialog();
		}
	},
	_handleButtonClick: function()
	{
		if(this._isLoaded)
		{
			this.toggleDialog();
			return;
		}

		if(this._enableLazyLoad && this._serviceUrl !== "")
		{
			this._userSelectorScriptLoaded = BX.delegate(this._handleUserSelectorScriptLoaded, this);
			BX.addCustomEvent("onAjaxSuccessFinish", this._userSelectorScriptLoaded);
			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "html",
					data:
					{
						"MODE": "GET_USER_SELECTOR",
						"NAME": this.componentName
					},
					onsuccess: BX.delegate(this._handleUserSelectorHtmlLoaded, this)
				}
			);
		}
	},
	_handleUserSelectorHtmlLoaded: function(data)
	{
		this._container.parentNode.appendChild(BX.create("DIV", { html: data  }));
		this._isLoaded = true;
	},
	_handleUserSelectorScriptLoaded: function(config)
	{
		if(config["url"] !== this._serviceUrl)
		{
			return;
		}

		BX.removeCustomEvent("onAjaxSuccessFinish", this._userSelectorScriptLoaded);
		this._userSelectorScriptLoaded = null;

		this._componentContainer = BX(this.componentName + "_selector_content");
		var objName = "O_" + this.componentName;
		if(window[objName])
		{
			this._componentObj = window[objName];
			this._componentObj.onSelect = BX.delegate(this._handleUserSelect, this);
		}

		this.openDialog();
	},
	_handleUserSelect: function(user)
	{
		this.closeDialog();

		if(!this._userInfoProvider)
		{
			return;
		}

		var self = this;
		this._userInfoProvider.getInfo(
			user.id,
			function(userInfo)
			{
				self._userInfo = userInfo;
				self.layout();
				if(self._fieldId.length > 0)
				{
					var editor = self._editor;
					if(!editor)
					{
						editor = BX.CrmInstantEditor.getDefault();
					}

					if(editor)
					{
						editor.saveFieldValue(self._fieldId, userInfo.getId());
					}
				}
			}
		);
	},
	_handleEditorCreation: function(editor)
	{
		var editorId = this.getOption('editorId', '');
		if(editorId !== '' && editor.getId() === editorId)
		{
			this._setupEditor(editor);
		}
	},
	_handleEditorFieldValueSaved: function(name, val)
	{
		if(this._fieldId !== name || !this._userInfoProvider)
		{
			return;
		}

		if(this._userInfo && this._userInfo.getId() === val)
		{
			return;
		}

		var self = this;
		this._userInfoProvider.getInfo(
			val,
			function(userInfo)
			{
				self._userInfo = userInfo;
				self.layout();
			}
		);
	},
	_setupEditor: function(editor)
	{
		if(this._editor)
		{
			BX.removeCustomEvent(
				this._editor,
				'CrmInstantEditorFieldValueSaved',
				BX.delegate(this._handleEditorFieldValueSaved, this)
			);
		}

		this._editor = editor;

		if(this._editor)
		{
			BX.addCustomEvent(
				this._editor,
				'CrmInstantEditorFieldValueSaved',
				BX.delegate(this._handleEditorFieldValueSaved, this)
			);
		}
	}
};

BX.CrmSidebarUserSelector.create = function(id, button, container, componentName, options)
{
	var self = new BX.CrmSidebarUserSelector();
	self.initialize(id, button, container, componentName, options);
	return self;
};

BX.CrmUserSearchField = function()
{
	this._id = '';
	this._search_input = null;
	this._data_input = null;
	this._componentName = '';
	this._componentContainer = null;
	this._componentObj = null;
	this._dlg = null;
	this._dlgDisplayed = false;
	this._currentUser = {};
};

BX.CrmUserSearchField.prototype =
{
	initialize: function(id, search_input, data_input, componentName, user)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : ('crm_user_search_field_' + Math.random());

		if(!BX.type.isElementNode(search_input))
		{
			throw  "BX.CrmUserSearchField: 'search_input' is not defined!";
		}
		this._search_input = search_input;

		if(!BX.type.isElementNode(data_input))
		{
			throw  "BX.CrmUserSearchField: 'data_input' is not defined!";
		}
		this._data_input = data_input;

		if(!BX.type.isNotEmptyString(componentName))
		{
			throw  "BX.CrmUserSearchField: 'componentName' is not defined!";
		}
		this._componentName = componentName;

		this._componentContainer = BX(componentName + '_selector_content');
		var objName = 'O_' + componentName;
		if(window[objName])
		{
			this._componentObj = window[objName];
			this._componentObj.onSelect = BX.delegate(this._handleUserSelect, this);
			this._componentObj.searchInput = search_input;

			BX.bind(search_input, 'keyup', BX.proxy(this._handleSearchKey, this));
			BX.bind(search_input, 'focus', BX.proxy(this._handleSearchFocus, this));
			BX.bind(document, 'click', BX.delegate(this._handleExternalClick, this));
		}

		this._currentUser = user ? user : {};
		this._adjustUser();
	},
	openDialog: function()
	{
		this._dlg = new BX.PopupWindow(
			this._id,
			this._search_input,
			{
				autoHide: false,
				draggable: false,
				//closeByEsc: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: true },
				content : this._componentContainer,
				events:
				{
					onPopupShow: BX.delegate(
						function()
						{
							this._dlgDisplayed = true;
						},
						this
					),
					onPopupClose: BX.delegate(
						function()
						{
							this._dlgDisplayed = false;
							this._dlg.destroy();
						},
						this
					),
					onPopupDestroy: BX.delegate(
						function()
						{
							this._dlg = null;
						},
						this
					)
				}
			}
		);

		this._dlg.show();
	},
	_adjustUser: function()
	{
		this._search_input.value = this._currentUser['name'] ? this._currentUser.name : '';
		this._data_input.value = this._currentUser['id'] ? this._currentUser.id : 0;
	},
	closeDialog: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	_handleExternalClick: function(e)
	{
		if(!e)
		{
			e = window.event;
		}

		if(e.target !== this._search_input &&
			!BX.findParent(e.target, { attribute:{ id: this._componentName + '_selector_content' } }))
		{
			this._adjustUser();
			this.closeDialog();
		}
	},
	_handleSearchKey: function(e)
	{
		if(!this._dlg || !this._dlgDisplayed)
		{
			this.openDialog();
		}

		this._componentObj.search();
	},
	_handleSearchFocus: function(e)
	{
		if(!this._dlg || !this._dlgDisplayed)
		{
			this.openDialog();
		}

		this._componentObj._onFocus(e);
	},
	_handleUserSelect: function(user)
	{
		this._currentUser = user;
		this._adjustUser();
		this.closeDialog();
	}
};

BX.CrmUserSearchField.items = {};

BX.CrmUserSearchField.create = function(id, search_input, data_input, componentName, user)
{
	var self = new BX.CrmUserSearchField();
	self.initialize(id, search_input, data_input, componentName, user);
	this.items[id] = self;
	return self;
};

BX.CrmUserLinkField = function()
{
	this._settings = {};
	this._container = null;
	this._fieldId = '';
	this._editor = null;
	this._userInfoProvider = null;
	this._userInfo = null;

};

BX.CrmUserLinkField.prototype =
{
	initialize: function(settings)
	{
		this._settings = settings ? settings : {};
		this._container = this.getSetting('container', null);
		if(!this._container)
		{
			this._container = BX(this.getSetting('containerId', ''));
		}

		if(!this._container)
		{
			throw 'BX.CrmUserLinkField: container is not found';
		}

		this._userInfoProvider = BX.CrmUserInfoProvider.getItemById(this.getSetting('userInfoProviderId', ''));
		this._userInfo = this.getSetting('userInfo', null);

		this._fieldId = this.getSetting('fieldId', '');
		if(this._fieldId !== '')
		{
			var editorId = this.getSetting('editorId', '');
			if(editorId !== '')
			{
				var editor = BX.CrmInstantEditor.items[editorId];
				if(editor)
				{
					this._setupEditor(editor);
				}
				else
				{
					BX.addCustomEvent(
						'CrmInstantEditorCreated',
						BX.delegate(this._handleEditorCreation, this)
					);
				}
			}
		}
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	layout: function()
	{
		this._container.href = this._userInfo ? this._userInfo.getProfileUrl() : '#';

		var nameElem = BX.findChild(this._container, { className: 'crm-detail-info-resp-name' }, true, false);
		if(nameElem)
		{
			nameElem.innerHTML = BX.util.htmlspecialchars(
				this._userInfo ? this._userInfo.getFullName() : ''
			);
		}

		var postElem = BX.findChild(this._container, { className: 'crm-detail-info-resp-descr' }, true, false);
		if(postElem)
		{
			postElem.innerHTML = BX.util.htmlspecialchars(
				this._userInfo ? this._userInfo.getWorkPosition() : ''
			);
		}

		var photoElem = BX.findChild(this._container, { className: 'crm-detail-info-resp-img' }, true, false);
		if(photoElem)
		{
			BX.cleanNode(photoElem, false);
			photoElem.appendChild(
				BX.create('IMG',
					{
						attrs: { src: this._userInfo ? this._userInfo.getPhotoUrl() : '' }
					}
				)
			);
		}
	},
	_handleEditorCreation: function(editor)
	{
		var editorId = this.getSetting('editorId', '');
		if(editorId !== '' && editor.getId() === editorId)
		{
			this._setupEditor(editor);
		}
	},
	_setupEditor: function(editor)
	{
		if(this._editor)
		{
			BX.removeCustomEvent(
				this._editor,
				'CrmInstantEditorFieldValueSaved',
				BX.delegate(this._handleEditorFieldValueSaved, this)
			);
		}

		this._editor = editor;

		if(this._editor)
		{
			BX.addCustomEvent(
				this._editor,
				'CrmInstantEditorFieldValueSaved',
				BX.delegate(this._handleEditorFieldValueSaved, this)
			);
		}
	},
	_handleEditorFieldValueSaved: function(name, val)
	{
		if(this._fieldId !== name || !this._userInfoProvider)
		{
			return;
		}

		var self = this;
		this._userInfoProvider.getInfo(
			val,
			function(userInfo)
			{
				self._userInfo = userInfo;
				self.layout();
			}
		);
	}
};

BX.CrmUserLinkField.create = function(settings)
{
	var self = new BX.CrmUserLinkField();
	self.initialize(settings);
	return self;
};

BX.CrmUserInfo = function()
{
	this._data = {};
};

BX.CrmUserInfo.prototype =
{
	initialize: function(data)
	{
		this._data = data ? data : {};
	},
	getId: function()
	{
		return BX.type.isNotEmptyString(this._data['ID']) ? this._data['ID'] : '';
	},
	getProfileUrl: function()
	{
		return BX.type.isNotEmptyString(this._data['USER_PROFILE']) ? this._data['USER_PROFILE'] : '';
	},
	getFullName: function()
	{
		return BX.type.isNotEmptyString(this._data['FULL_NAME']) ? this._data['FULL_NAME'] : '';
	},
	getWorkPosition: function()
	{
		return BX.type.isNotEmptyString(this._data['WORK_POSITION']) ? this._data['WORK_POSITION'] : '';
	},
	getPhotoUrl: function()
	{
		return BX.type.isNotEmptyString(this._data['PERSONAL_PHOTO']) ? this._data['PERSONAL_PHOTO'] : '';
	}
};

BX.CrmUserInfo.items = {};
BX.CrmUserInfo.create = function(data)
{
	var self = new BX.CrmUserInfo();
	self.initialize(data);
	this.items[self.getId()] = self;
	return self;
};

BX.CrmUserInfoProvider = function()
{
	this._id = '';
	this._settings = {};
	this._serviceUrl = '';
	this._items = {};
};

BX.CrmUserInfoProvider.prototype =
{
	initialize: function(id, settings)
	{
		if(!BX.type.isNotEmptyString(id))
		{
			throw 'BX.CrmUserInfoProvider: id is not defined';
		}

		this._id = id;

		this._settings = settings ? settings : {};
		var serviceUrl = this.getSetting('serviceUrl', '');
		if(serviceUrl === '')
		{
			throw 'BX.CrmUserInfoProvider: serviceUrl is not found';
		}

		this._serviceUrl = serviceUrl;
	},
	getId: function()
	{
		return this._id;
	},
	getSetting: function(name, defaultval)
	{
		return this._settings[name] ? this._settings[name] : defaultval;
	},
	getInfo: function(userId, callback)
	{
		if(!BX.type.isString(userId))
		{
			userId = userId.toString();
		}

		if(!BX.type.isNotEmptyString(userId))
		{
			if(BX.type.isFunction(callback))
			{
				callback(null);
			}
			return;
		}

		if(typeof(this._items[userId]) !== 'undefined')
		{
			if(BX.type.isFunction(callback))
			{
				callback(this._items[userId]);
			}
			return;
		}

		var self = this;
		BX.ajax(
			{
				'url': this._serviceUrl,
				'method': 'POST',
				'dataType': 'json',
				'data':
				{
					'MODE': 'GET_USER_INFO',
					'USER_ID': userId,
					'USER_PROFILE_URL_TEMPLATE': this.getSetting('userProfileUrlTemplate', '')
				},
				onsuccess: function(data)
					{
						var item = BX.CrmUserInfo.create(data['USER_INFO'] ? data['USER_INFO'] : {});
						self._items[userId] = item;
						if(BX.type.isFunction(callback))
						{
							callback(item);
						}
					},
				onfailure: function(data)
					{
						self._showError(self.getMessage('generalError'));
						if(BX.type.isFunction(callback))
						{
							callback(null);
						}
					}
			}
		);
	},
	getMessage: function(name)
	{
		var msg = BX.CrmUserInfoProvider.messages;
		return typeof(msg[name]) !== 'undefined' ? msg[name] : '';
	},
	_showError: function(msg)
	{
		alert(msg);
	}
};

BX.CrmUserInfoProvider.items = {};
BX.CrmUserInfoProvider.getItemById = function(id)
{
	return typeof(this.items[id]) ? this.items[id] : null;
};
BX.CrmUserInfoProvider.createIfNotExists = function(id, settings)
{
	if(typeof(this.items[id]) !== 'undefined')
	{
		return this.items[id];
	}

	var self = new BX.CrmUserInfoProvider();
	self.initialize(id, settings);
	this.items[self.getId()] = self;
	return self;
};

if(typeof(BX.CrmUserInfoProvider.messages) === 'undefined')
{
	BX.CrmUserInfoProvider.messages = {};
}

BX.CrmDateLinkField = function()
{
	this._dataElem = null;
	this._viewElem = null;
	this._settings = {};
};

BX.CrmDateLinkField.prototype =
{
	initialize: function(dataElem, viewElem, settings)
	{
		if(!BX.type.isElementNode(dataElem))
		{
			throw "BX.CrmDateLinkField: 'dataElem' is not defined!";
		}
		this._dataElem = dataElem;
		if(BX.type.isElementNode(viewElem))
		{
			this._viewElem = viewElem;
			BX.bind(viewElem, 'click', BX.delegate(this._onViewClick, this));
		}
		else
		{
			BX.bind(dataElem, 'click', BX.delegate(this._onViewClick, this));
		}
		this._settings = settings ? settings : {};
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	//layout: function(){},
	_onViewClick: function(e)
	{
		BX.calendar({ node: (this._viewElem ? this._viewElem : this._dataElem), field: this._dataElem, bTime: this.getSetting('showTime', true), bSetFocus: this.getSetting('setFocusOnShow', true), callback: BX.delegate(this._onCalendarSaveValue, this) });
	},
	_onCalendarSaveValue: function(value)
	{
		var s = BX.calendar.ValueToString(value, this.getSetting('showTime', true), false);
		this._dataElem.value = s;
		if(this._viewElem)
		{
			this._viewElem.innerHTML = s;
		}
	}
};

BX.CrmDateLinkField.create = function(dataElem, viewElem, settings)
{
	var self = new BX.CrmDateLinkField();
	self.initialize(dataElem, viewElem, settings);
	return self;
};

BX.CrmEntityEditor = function()
{
	this._id = '';
	this._settings = {};
	this._readonly = false;
	this._dlg = null;
	this._data = null;
	this._info = null;
	this._container = null;
	this._selector = null;
	this._advInfoContainer = null;
	this._externalRequestData = null;
	this._externalEventHandler = null;
	this._blockArea = null;
	this._rqLinkedInputId = "";
	this._rqLinkedId = 0;
	this._bdLinkedId = 0;
};

BX.CrmEntityEditor.prototype =
{
	initialize: function(id, settings, data, info)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : 'CRM_ENTITY_EDITOR' + Math.random();
		this._settings = settings ? settings : {};

		if(!data)
		{
			data = this._prepareData();
		}

		if(!data)
		{
			throw "BX.CrmEntityEditor: Could not find data!";
		}

		this._data = data;

		this._info = info ? info : BX.CrmEntityInfo.create();

		var selectorId = this.getSetting('entitySelectorId', '');
		if(obCrm && obCrm[selectorId])
		{
			var selector = this._selector = obCrm[selectorId];
			selector.AddOnSaveListener(BX.delegate(this._onEntitySelect, this));
			//selector.AddOnBeforeSearchListener();
		}

		var c = this._container = BX(this.getSetting('containerId', ''));
		if(!c)
		{
			throw "BX.CrmEntityEditor: Could not find field container!";
		}

		this._advInfoContainer = BX(this.getSetting('containerId', '') + '_descr');

		BX.bind(BX.findChild(c, { className: 'crm-element-item-delete'}, true, false), 'click', BX.delegate(this._onDeleteButtonClick, this));

		var btnChangeIgnore = this.getSetting('buttonChangeIgnore', false);
		if (!btnChangeIgnore)
			BX.bind(BX.findChild(c, { className: 'bx-crm-edit-crm-entity-change'}, true, false), 'click', BX.delegate(this._onChangeButtonClick, this));

		var btnAdd = BX(this.getSetting('buttonAddId', ''));
		BX.bind((btnAdd) ? btnAdd : BX.findChild(c, { className: 'bx-crm-edit-crm-entity-add'}, true, false), 'click', BX.delegate(this._onAddButtonClick, this));

		if (this.getSetting("cardViewMode", false))
		{
			this._rqLinkedInputId = this.getSetting("rqLinkedInputId", "");
			this._bdLinkedInputId = this.getSetting("bdLinkedInputId", "");
			this._setRqLinkedId(
				this.getSetting("rqLinkedId", 0),
				this.getSetting("bdLinkedId", 0),
				this.getSetting("skipInitInput", false)
			);
		}

		var entityId = this._info.getSetting("id", "");
		var pos, numericId = 0;
		if (typeof(entityId) === 'string' && entityId.length > 0)
		{
			if (/^\d+$/.test(entityId))
				numericId = parseInt(entityId);
			else if (/^\w+_\d+$/.test(entityId))
				numericId = parseInt(entityId.replace(/^\w+_/, ""));
			else
				numericId = 0;
		}
		else
		{
			numericId = parseInt(entityId);
		}
		if (numericId > 0)
		{
			this._data.setId(entityId);
			this.layout();
		}
	},
	getId: function()
	{
		return this._id;
	},
	getTypeName: function()
	{
		return this.getSetting('typeName', '');
	},
	getContext: function()
	{
		return this.getSetting('context', '');
	},
	getCreateUrl: function()
	{
		return this.getSetting('createUrl', '');
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	getData: function()
	{
		return this._data;
	},
	getMessage: function(name)
	{
		var msgs = BX.CrmEntityEditor.messages;
		return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : '';
	},
	openDialog: function(anchor, mode)
	{
		if(this._dlg)
		{
			this._dlg.setData(this._data);
			this._dlg.open(anchor, mode);
			return;
		}

		switch(this.getTypeName())
		{
			case 'CONTACT':
				this._dlg = BX.CrmContactEditDialog.create(
					this._id,
					this.getSetting('dialog', {}),
					this._data,
					BX.delegate(this._onSaveDialogData, this));
				break;
			case 'COMPANY':
				this._dlg = BX.CrmCompanyEditDialog.create(
					this._id,
					this.getSetting('dialog', {}),
					this._data,
					BX.delegate(this._onSaveDialogData, this));
				break;
		}

		if(this._dlg)
		{
			this._dlg.open(anchor, mode);
		}
	},
	closeDialog: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	isReadOnly: function()
	{
		return this._readonly;
	},
	setReadOnly: function(readonly)
	{
		readonly = !!readonly;
		if(this._readonly === readonly)
		{
			return;
		}

		this._readonly = readonly;

		var deleteButton = BX.findChild(this._container, { className: 'crm-element-item-delete'}, true, false);
		if(deleteButton)
		{
			deleteButton.style.display = readonly ? 'none' : '';
		}

		var buttonsWrapper = BX.findChild(this._container, { className: 'bx-crm-entity-buttons-wrapper'}, true, false);
		if(buttonsWrapper)
		{
			buttonsWrapper.style.display = readonly ? 'none' : '';
		}
	},
	_prepareData: function(settings)
	{
		var typeName = this.getTypeName();
		var enablePrefix = this.getSetting('enableValuePrefix', false);

		if(typeName === 'CONTACT')
		{
			if(settings && enablePrefix)
				settings['id'] = 'C_' + settings['id'];
			return BX.CrmContactData.create(settings);
		}
		if(typeName === 'COMPANY')
		{
			if(settings && enablePrefix)
				settings['id'] = 'CO_' + settings['id'];
			return BX.CrmCompanyData.create(settings);
		}
		if(typeName === 'LEAD')
		{
			if(settings && enablePrefix)
				settings['id'] = 'L_' + settings['id'];
			return BX.CrmLeadData.create(settings);
		}
		if(typeName === 'DEAL')
		{
			if(settings && enablePrefix)
				settings['id'] = 'D_' + settings['id'];
			return BX.CrmDealData.create(settings);
		}
		if(typeName === 'QUOTE')
		{
			if(settings && enablePrefix)
				settings['id'] = 'Q_' + settings['id'];
			return BX.CrmQuoteData.create(settings);
		}
		return null;
	},
	_onDeleteButtonClick: function(e)
	{
		if(this._readonly)
		{
			return;
		}

		var dataInput = BX(this.getSetting('dataInputId', ''));
		if(dataInput)
		{
			dataInput.value = 0;
		}

		if (this.getSetting("cardViewMode", false))
		{
			var rqLinkedInput = BX(this.getSetting('rqLinkedInputId', ''));
			if (rqLinkedInput)
				rqLinkedInput.value = 0;
		}
		else
		{
			BX.cleanNode(BX.findChild(this._container, { className: 'bx-crm-entity-info-wrapper' }, true, false));
			if (this._advInfoContainer)
				BX.cleanNode(this._advInfoContainer);
		}

		BX.onCustomEvent('CrmEntitySelectorChangeValue', [this.getId(), this.getTypeName(), 0, this]);
	},
	_onChangeButtonClick: function(e)
	{
		if(this._readonly)
		{
			return;
		}

		var selector = this._selector;
		if(selector)
		{
			selector.Open();
		}
	},
	_onAddButtonClick: function(e)
	{
		if(this._readonly)
		{
			return;
		}

		this._data.reset();

		var url = this.getCreateUrl();
		var context = this.getContext();

		if(url === '' || context === '')
		{
			this.openDialog(
				BX.findChild(this._container, { className: "bx-crm-edit-crm-entity-add" }, true, false),
				"CREATE"
			);
			return;
		}

		context = (context + "_" + BX.util.getRandomString(6)).toLowerCase();
		url = BX.util.add_url_param(url, { external_context: context });

		if(!this._externalRequestData)
		{
			this._externalRequestData = {};
		}
		this._externalRequestData[context] = { context: context, wnd: window.open(url) };

		if(!this._externalEventHandler)
		{
			this._externalEventHandler = BX.delegate(this._onExternalEvent, this);
			BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
		}
	},
	_onExternalEvent: function(params)
	{
		var key = BX.type.isNotEmptyString(params["key"]) ? params["key"] : "";
		var value = BX.type.isPlainObject(params["value"]) ? params["value"] : {};
		var typeName = BX.type.isNotEmptyString(value["entityTypeName"]) ? value["entityTypeName"] : "";
		var context = BX.type.isNotEmptyString(value["context"]) ? value["context"] : "";

		if(key === "onCrmEntityCreate"
			&& typeName === this.getTypeName()
			&& this._externalRequestData
			&& BX.type.isPlainObject(this._externalRequestData[context]))
		{
			var isCanceled = BX.type.isBoolean(value["isCanceled"]) ? value["isCanceled"] : false;
			if(!isCanceled)
			{
				var entityInfo = BX.type.isPlainObject(value["entityInfo"]) ? value["entityInfo"] : {};

				this._data = this._prepareData(entityInfo);
				this._info = BX.CrmEntityInfo.create(entityInfo);

				var newDataInput = BX(this.getSetting("newDataInputId", ""));
				if(newDataInput)
				{
					newDataInput.value = this._data.getId();
					BX.onCustomEvent(
						"CrmEntitySelectorChangeValue",
						[ this.getId(), this.getTypeName(), this._data.getId(), this ]
					);
				}

				this.layout();
			}

			if(this._externalRequestData[context]["wnd"])
			{
				this._externalRequestData[context]["wnd"].close();
			}

			delete this._externalRequestData[context];
		}
	},
	_onSaveDialogData: function(dialog)
	{
		this._data = this._dlg.getData();

		var url = this.getSetting('serviceUrl', '');
		var action = this.getSetting('actionName', '');

		if(!(BX.type.isNotEmptyString(url) && BX.type.isNotEmptyString(action)))
		{
			return;
		}

		var self = this;
		BX.ajax(
			{
				'url': url,
				'method': 'POST',
				'dataType': 'json',
				'data':
				{
					'ACTION' : action,
					'DATA': this._data.toJSON()
				},
				onsuccess: function(data)
				{

					if(data['ERROR'])
					{
						self._showDialogError(data['ERROR']);
					}
					else if(!data['DATA'])
					{
						self._showDialogError('BX.CrmEntityEditor: Could not find contact data!');
					}
					else
					{
						self._data = self._prepareData(data['DATA']);
						self._info = BX.CrmEntityInfo.create(data['INFO'] ? data['INFO'] : {});

						var newDataInput = BX(self.getSetting('newDataInputId', ''));
						if(newDataInput)
						{
							newDataInput.value = self._data.getId();
							BX.onCustomEvent('CrmEntitySelectorChangeValue', [self.getId(), self.getTypeName(), self._data.getId(), self]);
						}

						self.layout((function(self) {
							self.closeDialog();
						})(self));
					}
				},
				onfailure: function(data)
				{
					self._showDialogError(data['ERROR'] ? data['ERROR'] : self.getMessage('unknownError'));
				}
			}
		);
	},
	layout: function(afterLayout)
	{
		var dataInput = BX(this.getSetting('dataInputId', ''));
		if(dataInput)
		{
			dataInput.value = this._data.getId();
		}

		var cardViewMode = this.getSetting("cardViewMode", false);
		if (cardViewMode)
		{
			var rqLinkedInput = BX(this.getSetting('rqLinkedInputId', ''));
			if (rqLinkedInput)
				rqLinkedInput.value = this._rqLinkedId;

			var bdLinkedInput = BX(this.getSetting('bdLinkedInputId', ''));
			if (bdLinkedInput)
				bdLinkedInput.value = this._bdLinkedId;
		}
		if (cardViewMode && this._advInfoContainer)
		{
			if (this._blockArea)
			{
				var self = this;
				this._blockArea.destroy(function(){
					self._continueLayout(afterLayout);
				});
			}
			else
			{
				this._continueLayout(afterLayout);
			}
		}
		else
		{
			var view = BX.findChild(this._container, { className: 'bx-crm-entity-info-wrapper'}, true, false);
			if(!view)
			{
				return;
			}

			BX.cleanNode(view);
			view.appendChild(
				BX.create(
					'A',
					{
						attrs:
						{
							className: 'bx-crm-entity-info-link',
							href: this._info.getSetting('url', ''),
							target: '_blank'
						},
						text: this._info.getSetting('title', this._data.getId())
					}
				)
			);

			view.appendChild(
				BX.create(
					'SPAN',
					{
						attrs:
						{
							className: 'crm-element-item-delete'
						},
						events:
						{
							click: BX.delegate(this._onDeleteButtonClick, this)
						}
					}
				)
			);

			if (this._advInfoContainer)
			{
				this._advInfoContainer.innerHTML = this._prepareAdvInfoHTML();
			}

			if (typeof(afterLayout) === "function")
				afterLayout(afterLayout);
		}
	},
	_continueLayout: function(afterLayout)
	{
		this._blockArea = null;

		this._blockArea = new BX.Crm.EntityEditorBlockAreaClass({
			editor: this,
			container: this._advInfoContainer,
			nextNode: null,
			entityInfoList: [this._info],
			readOnlyMode: this.isReadOnly(),
			closeBlockHandler: BX.delegate(this._onDeleteButtonClick, this),
			changeSelectedRequisiteHandler: BX.delegate(this._onChangeSelectedRequisite, this),
			changeLinkedRequisiteHandler: BX.delegate(this._onChangeLinkedRequisite, this),
			rqLinkedId: this._rqLinkedId,
			bdLinkedId: this._bdLinkedId
		});

		if (typeof(afterLayout) === "function")
			afterLayout();
	},
	_onEntitySelect: function(settings)
	{
		var typeName = this.getTypeName().toLowerCase();
		var item = settings[typeName] && settings[typeName][0] ? settings[typeName][0] : null;
		var cardViewMode = this.getSetting("cardViewMode", false);
		if(!item)
		{
			if (cardViewMode && this._advInfoContainer && this._blockArea)
				this._blockArea.destroy();

			return;
		}

		this._data.setId(item['id']);
		this._info = BX.CrmEntityInfo.create(item);

		this._setRqLinkedId(0, 0);

		var self = this;
		this.layout(function() {
			BX.onCustomEvent('CrmEntitySelectorChangeValue', [self.getId(), self.getTypeName(), item['id'], self]);
		});
	},
	_showDialogError: function(msg)
	{
		if(this._dlg)
		{
			this._dlg.showError(msg);
		}
	},
	_prepareAdvInfoHTML: function()
	{
		var result = "";
		var type, advInfo, i;
		var contactType = "";
		var phoneItems = [], emailItems = [];

		type = this._info.getSetting("type", null);
		if (type)
		{
			advInfo = this._info.getSetting("advancedInfo", null);
			if (advInfo)
			{
				if (advInfo["contactType"] && advInfo["contactType"]["name"]
					&& typeof(advInfo["contactType"]["name"]) === "string")
				{
					contactType = BX.util.trim(advInfo["contactType"]["name"]);
				}

				if (advInfo["multiFields"] && advInfo["multiFields"] instanceof Array)
				{
					var mf = advInfo["multiFields"];
					for (i = 0; i < mf.length; i++)
					{
						if (mf[i]["TYPE_ID"] && mf[i]["TYPE_ID"] === "PHONE")
						{
							phoneItems.push({"VALUE": BX.util.trim(mf[i]["VALUE"])});
						}
						if (mf[i]["TYPE_ID"] && mf[i]["TYPE_ID"] === "EMAIL")
						{
							emailItems.push({"VALUE": BX.util.trim(mf[i]["VALUE"])});
						}
					}
				}
				
				switch (type)
				{
					case 'contact':
						if (phoneItems.length > 0)
						{
							result +=
								"<span class=\"crm-offer-info-descrip-tem crm-offer-info-descrip-tel\">" +
								this.getMessage("prefPhone") + ": " + BX.util.htmlspecialchars(phoneItems[0]["VALUE"]) +
								"<a href=\"callto:" + BX.util.htmlspecialchars(phoneItems[0]["VALUE"]) +
								"\" class=\"crm-offer-info-descrip-icon\"></a></span><br/>";
						}
						if (emailItems.length > 0)
						{
							result +=
								"<span class=\"crm-offer-info-descrip-tem crm-offer-info-descrip-imail\">" +
								this.getMessage("prefEmail") + ": " + BX.util.htmlspecialchars(emailItems[0]["VALUE"]) +
								"<a href=\"mailto:" + BX.util.htmlspecialchars(emailItems[0]["VALUE"]) +
								"\" class=\"crm-offer-info-descrip-icon\"></a></span><br/>";
						}
						if (contactType)
						{
							result +=
								"<span class=\"crm-offer-info-descrip-tem crm-offer-info-descrip-type\">" +
								this.getMessage("prefContactType") + ": " + BX.util.htmlspecialchars(contactType) +
								"</span><br/>";
						}
						break;
					case 'company':
					case 'lead':
						if (phoneItems.length > 0)
						{
							result +=
								"<span class=\"crm-offer-info-descrip-tem crm-offer-info-descrip-tel\">" +
								this.getMessage("prefPhone") + ": " + BX.util.htmlspecialchars(phoneItems[0]["VALUE"]) +
								"<a href=\"callto:" + BX.util.htmlspecialchars(phoneItems[0]["VALUE"]) +
								"\" class=\"crm-offer-info-descrip-icon\"></a></span><br/>";
						}
						if (emailItems.length > 0)
						{
							result +=
								"<span class=\"crm-offer-info-descrip-tem crm-offer-info-descrip-imail\">" +
								this.getMessage("prefEmail") + ": " + BX.util.htmlspecialchars(emailItems[0]["VALUE"]) +
								"<a href=\"mailto:" + BX.util.htmlspecialchars(emailItems[0]["VALUE"]) +
								"\" class=\"crm-offer-info-descrip-icon\"></a></span><br/>";
						}
						break;
				}
			}
		}

		return result;
	},
	_onChangeSelectedRequisite: function(entityTypeId, entityId, requisiteId, bankdetailId)
	{
		entityTypeId = parseInt(entityTypeId);
		entityId = parseInt(entityId);
		requisiteId = parseInt(requisiteId);
		bankdetailId = parseInt(bankdetailId);

		if (bankdetailId < 0 || isNaN(bankdetailId))
			bankdetailId = 0;

		var i, advancedInfo, requisiteData, id;

		if (entityTypeId > 0 && entityId > 0 && requisiteId > 0)
		{
			id = parseInt(this._info.getSetting("id", 0));
			if (id === entityId)
			{
				advancedInfo = this._info.getSetting("advancedInfo", null);
				if (BX.type.isPlainObject(advancedInfo) && BX.type.isArray(advancedInfo["requisiteData"]))
				{
					requisiteData = advancedInfo["requisiteData"];
					for (i = 0; i < requisiteData.length; i++)
					{
						if (
							requisiteData[i]["selected"] = (
								entityTypeId === parseInt(requisiteData[i]["entityTypeId"])
								&& entityId === parseInt(requisiteData[i]["entityId"])
								&& requisiteId === parseInt(requisiteData[i]["requisiteId"])
							)
						)
						{
							requisiteData[i]["bankDetailIdSelected"] = bankdetailId;
						}
					}
				}
			}
		}
	},
	_onChangeLinkedRequisite: function(requisiteId, bankDetailId)
	{
		this._setRqLinkedId(requisiteId, bankDetailId);
	},
	_setRqLinkedId: function(requisiteId, bankDetailId, skipSetInputValue)
	{
		skipSetInputValue = !!skipSetInputValue;
		if (this.getSetting("cardViewMode", false))
		{
			var inp;

			this._rqLinkedId = parseInt(requisiteId);
			if (this._rqLinkedId < 0 || isNaN(this._rqLinkedId))
				this._rqLinkedId = 0;
			if (!skipSetInputValue)
			{
				inp = BX(this._rqLinkedInputId);
				if (inp)
					inp.value = this._rqLinkedId;
			}

			this._bdLinkedId = parseInt(bankDetailId);
			if (this._bdLinkedId < 0 || isNaN(this._bdLinkedId))
				this._bdLinkedId = 0;
			if (!skipSetInputValue)
			{
				inp = BX(this._bdLinkedInputId);
				if (inp)
					inp.value = this._bdLinkedId;
			}
		}
	}
};

if(typeof(BX.CrmEntityEditor.messages) === 'undefined')
{
	BX.CrmEntityEditor.messages = {};
}

BX.CrmEntityEditor.items = {};

BX.CrmEntityEditor.create = function(id, settings, data, info)
{
	var self = new BX.CrmEntityEditor();
	self.initialize(id, settings, data, info);
	this.items[id] = self;
	return self;
};

BX.CrmContactEditDialog = function()
{
	this._id = '';
	this._settings = {};
	this._dlg = null;
	this._dlgCfg = {};
	this._data = null;
	this._mode = 'CREATE';
	this._onSaveCallback = null;
};

BX.CrmContactEditDialog.prototype =
{
	initialize: function(id, settings, data, onSaveCallback)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : 'CRM_CONTACT_EDIT_DIALOG_' + Math.random();
		this._settings = settings ? settings : {};
		this._data = data ? data : BX.CrmContactData.create();
		this._onSaveCallback = BX.type.isFunction(onSaveCallback) ? onSaveCallback : null;
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	getData: function()
	{
		return this._data;
	},
	setData: function(data)
	{
		this._data = data ? data : BX.CrmContactData.create();
	},
	isOpened: function()
	{
		return this._dlg && this._dlg.isShown();
	},
	open: function(anchor, mode)
	{
		if(!BX.type.isNotEmptyString(mode) || (mode !== 'CREATE' && mode !== 'EDIT'))
		{
			mode = this._mode;
		}

		if(this._dlg && this._mode === mode)
		{
			if(!this._dlg.isShown())
			{
				this._dlg.show();
			}
			return;
		}

		if(this._mode !== mode)
		{
			this._mode = mode;
		}

		var cfg = this._dlgCfg = {};
		cfg['id'] = this._id;
		this._dlg = new BX.PopupWindow(
			cfg['id'],
			anchor,
			{
				autoHide: false,
				draggable: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: false },
				closeByEsc: true,
				closeIcon: { top: '10px', right: '15px'},
				titleBar:
				{
					content: BX.CrmPopupWindowHelper.prepareTitle(this.getSetting('title', 'New contact'))
				},
				events:
				{
					//onPopupShow: function(){},
					onPopupClose: BX.delegate(this._onPopupClose, this),
					onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
				},
				content: this._prepareContent(),
				buttons: this._prepareButtons()
			}
		);

		this._dlg.show();
	},
	close: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	showError: function(msg)
	{
		var errorWrap = BX(this._getElementId('errors'));
		if(errorWrap)
		{
			errorWrap.innerHTML = msg;
			errorWrap.style.display = '';
		}
	},
	_onPopupClose: function()
	{
		this._dlg.destroy();
	},
	_onPopupDestroy: function()
	{
		this._dlg = null;
	},
	_prepareContent: function()
	{
		var wrapper = BX.create(
			'DIV',
			{
				attrs: { className: 'bx-crm-dialog-quick-create-popup' }
			}
		);

		var data = this._data;
		wrapper.appendChild(
			BX.create(
				'DIV',
				{
					attrs:
					{
						className: 'bx-crm-dialog-quick-create-error-wrap',
						style: 'display:none'
					},
					props: { id: this._getElementId('errors') }
				}
			)
		);
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('lastName'), title: this.getSetting('lastNameTitle', 'Last Name'), value: data.getLastName() }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('name'), title: this.getSetting('nameTitle', 'Name'), value: data.getName() }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('secondName'), title: this.getSetting('secondNameTitle', 'Second Name'), value: data.getSecondName() }));
		if(this.getSetting('enableEmail', true))
		{
			wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('email'), title: this.getSetting('emailTitle', 'E-mail'), value: data.getEmail() }));
		}
		if(this.getSetting('enablePhone', true))
		{
			wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('phone'), title: this.getSetting('phoneTitle', 'Phone'), value: data.getPhone() }));
		}
		if(this.getSetting('enableExport', true))
		{
			if(this._mode === 'CREATE')
			{
				data.markAsExportable(true);
			}

			wrapper.appendChild(BX.CrmPopupWindowHelper.prepareCheckBoxField({ id: this._getElementId('export'), title: this.getSetting('exportTitle', 'Enable Export'), value: data.isExportable() }));
		}
		return wrapper;
	},
	_prepareButtons: function()
	{
		return BX.CrmPopupWindowHelper.prepareButtons(
			[
				{
					type: 'button',
					settings:
					{
						text: this.getSetting('addButtonName', 'Add'),
						className: 'popup-window-button-accept',
						events:
						{
							click : BX.delegate(this._onSaveButtonClick, this)
						}
					}
				},
				{
					type: 'link',
					settings:
					{
						text: this.getSetting('cancelButtonName', 'Cancel'),
						className: 'popup-window-button-link-cancel',
						events:
						{
							click :
								function()
								{
									this.popupWindow.close();
								}
						}
					}
				}
			]
		);
	},
	_getElementId: function(code)
	{
		return this._dlgCfg['id'] + '_' + code;
	},
	_onSaveButtonClick: function()
	{
		this._data.setLastName(BX(this._getElementId('lastName')).value);
		this._data.setName(BX(this._getElementId('name')).value);
		this._data.setSecondName(BX(this._getElementId('secondName')).value);
		if(this.getSetting('enableEmail', true))
		{
			this._data.setEmail(BX(this._getElementId('email')).value);
		}
		if(this.getSetting('enablePhone', true))
		{
			this._data.setPhone(BX(this._getElementId('phone')).value);
		}
		if(this.getSetting('enableExport', true))
		{
			this._data.markAsExportable(BX(this._getElementId('export')).checked);
		}
		if(this._onSaveCallback)
		{
			this._onSaveCallback(this);
		}
	}
};

BX.CrmContactEditDialog.create = function(id, settings, data, onSaveCallback)
{
	var self = new BX.CrmContactEditDialog();
	self.initialize(id, settings, data, onSaveCallback);
	return self;
};

BX.CrmCompanyEditDialog = function()
{
	this._id = '';
	this._settings = {};
	this._dlg = null;
	this._dlgCfg = {};
	this._data = null;
	this._mode = 'CREATE';
	this._onSaveCallback = null;
};

BX.CrmCompanyEditDialog.prototype =
{
	initialize: function(id, settings, data, onSaveCallback)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : 'CRM_COMPANY_EDIT_DIALOG_' + Math.random();
		this._settings = settings ? settings : {};
		this._data = data ? data : BX.CrmCompanyData.create();
		this._onSaveCallback = BX.type.isFunction(onSaveCallback) ? onSaveCallback : null;
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	getData: function()
	{
		return this._data;
	},
	setData: function(data)
	{
		this._data = data ? data : BX.CrmCompanyData.create();
	},
	isOpened: function()
	{
		return this._dlg && this._dlg.isShown();
	},
	open: function(anchor, mode)
	{
		if(!BX.type.isNotEmptyString(mode) || (mode !== 'CREATE' && mode !== 'EDIT'))
		{
			mode = this._mode;
		}

		if(this._dlg && this._mode === mode)
		{
			this._dlg.setContent(this._prepareContent());
			if(!this._dlg.isShown())
			{
				this._dlg.show();
			}
			return;
		}

		if(this._mode !== mode)
		{
			this._mode = mode;
		}

		var cfg = this._dlgCfg = {};
		cfg['id'] = this._id;
		this._dlg = new BX.PopupWindow(
			cfg['id'],
			anchor,
			{
				autoHide: false,
				draggable: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: false },
				closeByEsc: true,
				closeIcon: { top: '10px', right: '15px'},
				titleBar:
				{
					content: BX.CrmPopupWindowHelper.prepareTitle(this.getSetting('title', 'New contact'))
				},
				events:
				{
					//onPopupShow: function(){},
					onPopupClose: BX.delegate(this._onPopupClose, this),
					onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
				},
				content: this._prepareContent(),
				buttons: this._prepareButtons()
			}
		);

		this._dlg.show();
	},
	close: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	showError: function(msg)
	{
		var errorWrap = BX(this._getElementId('errors'));
		if(errorWrap)
		{
			errorWrap.innerHTML = msg;
			errorWrap.style.display = '';
		}
	},
	_onPopupClose: function()
	{
		this._dlg.destroy();
	},
	_onPopupDestroy: function()
	{
		this._dlg = null;
	},
	_prepareContent: function()
	{
		var wrapper = BX.create(
			'DIV',
			{
				attrs: { className: 'bx-crm-dialog-quick-create-popup' }
			}
		);

		var data = this._data;
		wrapper.appendChild(
			BX.create(
				'DIV',
				{
					attrs:
					{
						className: 'bx-crm-dialog-quick-create-error-wrap',
						style: 'display:none'
					},
					props: { id: this._getElementId('errors') }
				}
			)
		);
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('title'), title: this.getSetting('titleTitle', 'Title'), value: data.getTitle() }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareSelectField({ id: this._getElementId('companyType'), title: this.getSetting('companyTypeTitle', 'Company Type'), value: data.getCompanyType(), items: this.getSetting('companyTypeItems', null) } ));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareSelectField({ id: this._getElementId('industry'), title: this.getSetting('industryTitle', 'Industry'), value: data.getIndustry(), items: this.getSetting('industryItems', null) }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('email'), title: this.getSetting('emailTitle', 'E-mail'), value: data.getEmail() }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('phone'), title: this.getSetting('phoneTitle', 'Phone'), value: data.getPhone() }));
		return wrapper;
	},
	_prepareButtons: function()
	{
		return BX.CrmPopupWindowHelper.prepareButtons(
			[
				{
					type: 'button',
					settings:
					{
						text: this.getSetting('addButtonName', 'Add'),
						className: 'popup-window-button-accept',
						events:
						{
							click : BX.delegate(this._onSaveButtonClick, this)
						}
					}
				},
				{
					type: 'link',
					settings:
					{
						text: this.getSetting('cancelButtonName', 'Cancel'),
						className: 'popup-window-button-link-cancel',
						events:
						{
							click :
								function()
								{
									this.popupWindow.close();
								}
						}
					}
				}
			]
		);
	},
	_getElementId: function(code)
	{
		return this._dlgCfg['id'] + '_' + code;
	},
	_onSaveButtonClick: function()
	{
		this._data.setTitle(BX(this._getElementId('title')).value);
		this._data.setCompanyType(BX(this._getElementId('companyType')).value);
		this._data.setIndustry(BX(this._getElementId('industry')).value);
		this._data.setEmail(BX(this._getElementId('email')).value);
		this._data.setPhone(BX(this._getElementId('phone')).value);

		if(this._onSaveCallback)
		{
			this._onSaveCallback(this);
		}
	}
};

BX.CrmCompanyEditDialog.create = function(id, settings, data, onSaveCallback)
{
	var self = new BX.CrmCompanyEditDialog();
	self.initialize(id, settings, data, onSaveCallback);
	return self;
};

BX.CrmContactData = function()
{
	this._id = 0;
	this._name = this._secondName = this._lastName = this._email = this._phone = '';
	this._export = null;
};

BX.CrmContactData.prototype =
{
	initialize: function(settings)
	{
		if(!settings)
		{
			return;
		}

		if(settings['id'])
		{
			this.setId(settings['id']);
		}

		if(settings['name'])
		{
			this.setName(settings['name']);
		}

		if(settings['secondName'])
		{
			this.setSecondName(settings['secondName']);
		}

		if(settings['lastName'])
		{
			this.setLastName(settings['lastName']);
		}

		if(settings['email'])
		{
			this.setEmail(settings['email']);
		}

		if(settings['phone'])
		{
			this.setPhone(settings['phone']);
		}

		if(settings['export'])
		{
			this.markAsExportable(settings['export']);
		}
	},
	reset: function()
	{
		this._id = 0;
		this._name = this._secondName = this._lastName = this._email = this._phone = '';
		this._export = null;
	},
	getId: function()
	{
		return this._id;
	},
	setId: function(val)
	{
		this._id = val;
	},
	getName: function()
	{
		return this._name;
	},
	setName: function(val)
	{
		this._name = BX.type.isNotEmptyString(val) ? val : '';
	},
	getSecondName: function()
	{
		return this._secondName;
	},
	setSecondName: function(val)
	{
		this._secondName = BX.type.isNotEmptyString(val) ? val : '';
	},
	getLastName: function()
	{
		return this._lastName;
	},
	setLastName: function(val)
	{
		this._lastName = BX.type.isNotEmptyString(val) ? val : '';
	},
	getEmail: function()
	{
		return this._email;
	},
	setEmail: function(val)
	{
		this._email = BX.type.isNotEmptyString(val) ? val : '';
	},
	getPhone: function()
	{
		return this._phone;
	},
	setPhone: function(val)
	{
		this._phone = BX.type.isNotEmptyString(val) ? val : '';
	},
	isExportable: function()
	{
		return !!this._export;
	},
	markAsExportable: function(val)
	{
		this._export = !!val;
	},
	toJSON: function()
	{
		var result =
			{
				id: this._id,
				name: this._name,
				secondName: this._secondName,
				lastName: this._lastName,
				email: this._email,
				phone: this._phone
			};
		if(this._export !== null)
		{
			result['export'] = this._export ? 'Y' : 'N';
		}
		return result;
	}
};

BX.CrmContactData.create = function(settings)
{
	var self = new BX.CrmContactData();
	self.initialize(settings);
	return self;
};

BX.CrmCompanyData = function()
{
	this._id = 0;
	this._title = this._companyType = this._industry = this._email = this._phone = this._addressLegal = '';
};

BX.CrmCompanyData.prototype =
{
	initialize: function(settings)
	{
		if(!settings)
		{
			return;
		}

		if(settings['id'])
		{
			this.setId(settings['id']);
		}

		if(settings['title'])
		{
			this.setTitle(settings['title']);
		}

		if(settings['companyType'])
		{
			this.setCompanyType(settings['companyType']);
		}

		if(settings['industry'])
		{
			this.setIndustry(settings['industry']);
		}

		if(settings['email'])
		{
			this.setEmail(settings['email']);
		}

		if(settings['phone'])
		{
			this.setPhone(settings['phone']);
		}
	},
	reset: function()
	{
		this._id = 0;
		this._title = this._companyType = this._industry = this._email = this._phone = this._addressLegal = '';
	},
	getId: function()
	{
		return this._id;
	},
	setId: function(val)
	{
		this._id = val;
	},
	getTitle: function()
	{
		return this._title;
	},
	setTitle: function(val)
	{
		this._title = BX.type.isNotEmptyString(val) ? val : '';
	},
	getCompanyType: function()
	{
		return this._companyType;
	},
	setCompanyType: function(val)
	{
		this._companyType = BX.type.isNotEmptyString(val) ? val : '';
	},
	getIndustry: function()
	{
		return this._industry;
	},
	setIndustry: function(val)
	{
		this._industry = BX.type.isNotEmptyString(val) ? val : '';
	},
	getEmail: function()
	{
		return this._email;
	},
	setEmail: function(val)
	{
		this._email = BX.type.isNotEmptyString(val) ? val : '';
	},
	getPhone: function()
	{
		return this._phone;
	},
	setPhone: function(val)
	{
		this._phone = BX.type.isNotEmptyString(val) ? val : '';
	},
	getAddressLegal: function()
	{
		return this._addressLegal;
	},
	setAddressLegal: function(val)
	{
		this._addressLegal = BX.type.isNotEmptyString(val) ? val : '';
	},
	toJSON: function()
	{
		return(
			{
				id: this.id,
				title: this._title,
				companyType: this._companyType,
				industry: this._industry,
				email: this._email,
				phone: this._phone
			}
		);
	}
};

BX.CrmCompanyData.create = function(settings)
{
	var self = new BX.CrmCompanyData();
	self.initialize(settings);
	return self;
};

BX.CrmLeadData = function()
{
	this._id = 0;
	this._title = this._name = this._secondName = this._lastName = this._email = this._phone = '';
};

BX.CrmLeadData.prototype =
{
	initialize: function(settings)
	{
		if(!settings)
		{
			return;
		}

		if(settings['id'])
		{
			this.setId(settings['id']);
		}

		if(settings['title'])
		{
			this.setTitle(settings['title']);
		}

		if(settings['name'])
		{
			this.setName(settings['name']);
		}

		if(settings['secondName'])
		{
			this.setSecondName(settings['secondName']);
		}

		if(settings['lastName'])
		{
			this.setLastName(settings['lastName']);
		}

		if(settings['email'])
		{
			this.setEmail(settings['email']);
		}

		if(settings['phone'])
		{
			this.setPhone(settings['phone']);
		}
	},
	reset: function()
	{
		this._id = 0;
		this._name = this._secondName = this._lastName = this._email = this._phone = '';
	},
	getId: function()
	{
		return this._id;
	},
	setId: function(val)
	{
		this._id = val;
	},
	getName: function()
	{
		return this._name;
	},
	setTitle: function(val)
	{
		this._title = BX.type.isNotEmptyString(val) ? val : '';
	},
	setName: function(val)
	{
		this._name = BX.type.isNotEmptyString(val) ? val : '';
	},
	getSecondName: function()
	{
		return this._secondName;
	},
	setSecondName: function(val)
	{
		this._secondName = BX.type.isNotEmptyString(val) ? val : '';
	},
	getLastName: function()
	{
		return this._lastName;
	},
	setLastName: function(val)
	{
		this._lastName = BX.type.isNotEmptyString(val) ? val : '';
	},
	getEmail: function()
	{
		return this._email;
	},
	setEmail: function(val)
	{
		this._email = BX.type.isNotEmptyString(val) ? val : '';
	},
	getPhone: function()
	{
		return this._phone;
	},
	setPhone: function(val)
	{
		this._phone = BX.type.isNotEmptyString(val) ? val : '';
	},
	toJSON: function()
	{
		return {
			id: this._id,
			name: this._name,
			secondName: this._secondName,
			lastName: this._lastName,
			email: this._email,
			phone: this._phone
		};
	}
};

BX.CrmLeadData.create = function(settings)
{
	var self = new BX.CrmLeadData();
	self.initialize(settings);
	return self;
};

BX.CrmDealData = function()
{
	this._id = this._dealPrice = 0;
	this._title = this._dealType = '';
};

BX.CrmDealData.prototype =
{
	initialize: function(settings)
	{
		if(!settings)
		{
			return;
		}

		if(settings['id'])
		{
			this.setId(settings['id']);
		}

		if(settings['title'])
		{
			this.setTitle(settings['title']);
		}

		if(settings['dealType'])
		{
			this.setDealType(settings['dealType']);
		}

		if(settings['dealPrice'])
		{
			this.setDealType(settings['dealPrice']);
		}
	},
	reset: function()
	{
		this._id = this._dealPrice = 0;
		this._title = this._dealType = '';
	},
	getId: function()
	{
		return this._id;
	},
	setId: function(val)
	{
		this._id = val;
	},
	getTitle: function()
	{
		return this._title;
	},
	setTitle: function(val)
	{
		this._title = BX.type.isNotEmptyString(val) ? val : '';
	},
	getDealType: function()
	{
		return this._dealType;
	},
	setDealType: function(val)
	{
		this._dealType = BX.type.isNotEmptyString(val) ? val : '';
	},
	getDealPrice: function()
	{
		return this._dealPrice;
	},
	setDealPrice: function(val)
	{
		this._dealPrice = BX.type.isNumber(val) ? val : 0;
	}
};

BX.CrmDealData.create = function(settings)
{
	var self = new BX.CrmDealData();
	self.initialize(settings);
	return self;
};

BX.CrmQuoteData = function()
{
	this._id = this._quotePrice = 0;
	this._title = this._quoteType = '';
};

BX.CrmQuoteData.prototype =
{
	initialize: function(settings)
	{
		if(!settings)
		{
			return;
		}

		if(settings['id'])
		{
			this.setId(settings['id']);
		}

		if(settings['title'])
		{
			this.setTitle(settings['title']);
		}

		if(settings['quoteType'])
		{
			this.setQuoteType(settings['quoteType']);
		}

		if(settings['quotePrice'])
		{
			this.setQuoteType(settings['quotePrice']);
		}
	},
	reset: function()
	{
		this._id = this._quotePrice = 0;
		this._title = this._quoteType = '';
	},
	getId: function()
	{
		return this._id;
	},
	setId: function(val)
	{
		this._id = val;
	},
	getTitle: function()
	{
		return this._title;
	},
	setTitle: function(val)
	{
		this._title = BX.type.isNotEmptyString(val) ? val : '';
	},
	getQuoteType: function()
	{
		return this._quoteType;
	},
	setQuoteType: function(val)
	{
		this._quoteType = BX.type.isNotEmptyString(val) ? val : '';
	},
	getQuotePrice: function()
	{
		return this._quotePrice;
	},
	setQuotePrice: function(val)
	{
		this._quotePrice = BX.type.isNumber(val) ? val : 0;
	}
};

BX.CrmQuoteData.create = function(settings)
{
	var self = new BX.CrmQuoteData();
	self.initialize(settings);
	return self;
};

BX.CrmEntityInfo = function()
{
	this._settings = {};
};

BX.CrmEntityInfo.prototype =
{
	initialize: function(settings)
	{
		this._settings = settings ? settings : {};
	},
	getSetting: function(name, defaultval)
	{
		return this._settings[name] ? this._settings[name] : defaultval;
	}
};

BX.CrmEntityInfo.create = function(settings)
{
	var self = new BX.CrmEntityInfo();
	self.initialize(settings);
	return self;
};

BX.CrmPopupWindowHelper = {};
BX.CrmPopupWindowHelper.prepareButtons = function(data)
{
	var result = [];
	for(var i = 0; i < data.length; i++)
	{
		var datum = data[i];
		result.push(
			datum['type'] === 'link'
				? new BX.PopupWindowButtonLink(datum['settings'])
				: new BX.PopupWindowButton(datum['settings']));
	}

	return result;
};

BX.CrmPopupWindowHelper.prepareTextField = function(settings)
{
	return BX.create(
		'DIV',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field' },
			children:
				[
					BX.create(
						'SPAN',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-title' },
							text: settings['title'] + ':'
						}
					),
					BX.create(
						'INPUT',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-text-input' },
							props: { id: settings['id'], value: settings['value'] }
						}
					)
				]
		}
	);
};

BX.CrmPopupWindowHelper.prepareSelectField = function(settings)
{
	var select = BX.create(
		'SELECT',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field-select' },
			props: { id: settings['id'] }
		}
	);

	var value = settings['value'] ? settings['value'] : '';

	if(settings['items'])
	{
		for(var i = 0; i < settings['items'].length; i++)
		{
			var item = settings['items'][i];
			var v = item['value'] ? item['value'] : i.toString();

			var option = BX.create(
				'OPTION',
				{
					text: item['text'] ? item['text'] : v,
					props: { value : v }
				}
			);

			if(!BX.browser.isIE)
			{
				select.add(option, null);
			}
			else
			{
				try
				{
					// for IE earlier than version 8
					select.add(option, select.options[null]);
				}
				catch (e)
				{
					select.add(option, null);
				}
			}

			if(v === value)
			{
				option.selected = true;
			}
		}
	}

	return BX.create(
		'DIV',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field' },
			children:
				[
					BX.create(
						'SPAN',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-title' },
							text: settings['title'] + ':'
						}
					),
					select
				]
		}
	);
};

BX.CrmPopupWindowHelper.prepareTextAreaField = function(settings)
{
	return BX.create(
		'DIV',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field' },
			children:
				[
					BX.create(
						'SPAN',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-title' },
							text: settings['title'] + ':'
						}
					),
					BX.create(
						'TEXTAREA',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-text-input' },
							props: { id: settings['id'] },
							text: settings['value']
						}
					)
				]
		}
	);
};

BX.CrmPopupWindowHelper.prepareCheckBoxField = function(settings)
{
	var checkbox = BX.create(
		'INPUT',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field-checkbox' },
			props: { id: settings['id'], type: 'checkbox', checked: (!!settings['value']) ? 'checked' : '' }
		}
	);

	if(!!settings['value'])
	{
		checkbox.checked = true;
	}

	return BX.create(
		'DIV',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field' },
			children:
				[
					BX.create(
						'LABEL',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-checkbox-label' },
							children:
							[
								checkbox,
								BX.create(
									'SPAN',
									{
										attrs: { className: 'bx-crm-dialog-quick-create-field-checkbox-label-text' },
										text: settings['title']
									}
								)
							]
						}
					)
				]
		}
	);
};

BX.CrmPopupWindowHelper.prepareTitle = function(text)
{
	return BX.create(
		'DIV',
		{
			attrs: { className: 'bx-crm-dialog-tittle-wrap' },
			children:
				[
					BX.create(
						'SPAN',
						{
							text: text,
							props: { className: 'bx-crm-dialog-title-text' }
						}
					)
				]
		}
	);
};

BX.CrmEntityDetailViewDialog = function()
{
	this._id = '';
	this._dlg = null;
	this._settings = {};
};

BX.CrmEntityDetailViewDialog.prototype =
{
	initialize: function(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : 'CRM_ENTITY_DETAIL_VIEW_DIALOG_' + Math.random();
		this._settings = settings ? settings : {};
	},
	getId: function()
	{
		return this._id;
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	isOpened: function()
	{
		return this._dlg && this._dlg.isShown();
	},
	open: function()
	{
		if(this._dlg)
		{
			if(!this._dlg.isShown())
			{
				this._dlg.show();
			}
			return;
		}

		var container = BX(this.getSetting('containerId'));
		if(!container)
		{
			container = BX.findChild(BX('sidebar'), { 'class': 'crm-entity-info-details-container' }, true, false);
		}

		this._dlg = new BX.PopupWindow(
			this._id,
			null,
			{
				autoHide: false,
				draggable: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: false },
				closeByEsc: true,
				closeIcon: { top: '10px', right: '15px'},
				titleBar:
				{
					content: BX.CrmPopupWindowHelper.prepareTitle(this.getSetting('title', 'Details'))
				},
				events:
				{
					onAfterPopupShow:  BX.delegate(this._onAfterPopupShow, this),
					onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
				},
				content: container
			}
		);

		this._dlg.show();
	},
	close: function()
	{
		if(this._dlg && this._dlg.isShown())
		{
			this._dlg.close();
		}
	},
	toggle: function()
	{
		this.isOpened() ? this.close() : this.open();
	},
	_onAfterPopupShow: function()
	{
		var sidebarContainer = BX.findChild(BX('sidebar'), { 'class': 'sidebar-block' }, true, false);
		if(!sidebarContainer)
		{
			return;
		}
		var sidebarPos = BX.pos(sidebarContainer);

		var dialogContainer = this._dlg.popupContainer;
		if(!dialogContainer)
		{
			return;
		}
		var dialogPos = BX.pos(dialogContainer);

		dialogContainer.style.top = sidebarPos.top.toString() + 'px';
		dialogContainer.style.left = (sidebarPos.left - dialogPos.width - 1).toString() + 'px';
	},
	_onPopupDestroy: function()
	{
		this._dlg = null;
	}
};

BX.CrmEntityDetailViewDialog.items = {};
BX.CrmEntityDetailViewDialog.create = function(id, settings)
{
	var self = new BX.CrmEntityDetailViewDialog();
	self.initialize(id, settings);
	this.items[self.getId()] = self;
	return self;
};

BX.CrmEntityDetailViewDialog.ensureCreated = function(id, settings)
{
	return typeof(this.items[id]) !== 'undefined' ? this.items[id] : this.create(id, settings);
};

BX.CrmContactEditor = function()
{
	this._id = '';
	this._settings = {};
	this._dlg = null;
	this._clientField = null;
	this._mode = 'CREATE';
};

BX.CrmContactEditor.prototype =
{
	initialize: function(id, settings)
	{
		this._id = id;
		this._settings = settings ? settings : {};

		var initData = this.getSetting('data', null);
		if(initData)
		{
			this._mode = 'EDIT';
		}
		else
		{
			initData = {};
			this._mode = 'CREATE';
		}
		this._data = BX.CrmContactData.create(initData);
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	openDialog: function(anchor)
	{
		if(this._dlg)
		{
			this._dlg.setData(this._data);
			this._dlg.open(anchor);
			return;
		}

		this._dlg = BX.CrmContactEditDialog.create(
			this._id,
			this.getSetting('dialog', {}),
			this._data,
			BX.delegate(this._onSaveDialogData, this));

		if(this._dlg)
		{
			this._dlg.open(anchor, this._mode);
		}
	},
	closeDialog: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	openExternalFieldEditor: function(field)
	{
		this._clientField = field;
		this.openDialog();
	},
	_onSaveDialogData: function(dialog)
	{
		this._data = this._dlg.getData();

		var url = this.getSetting('serviceUrl', '');
		var action = this.getSetting('actionName', '');

		if(!(BX.type.isNotEmptyString(url) && BX.type.isNotEmptyString(action)))
		{
			return;
		}

		var self = this;
		BX.ajax(
			{
				'url': url,
				'method': 'POST',
				'dataType': 'json',
				'data':
				{
					'ACTION' : action,
					'DATA': this._data.toJSON(),
					'NAME_TEMPLATE': this.getSetting('nameTemplate', '')
				},
				onsuccess: function(data)
				{

					if(data['ERROR'])
					{
						self._showDialogError(data['ERROR']);
					}
					else if(!data['DATA'])
					{
						self._showDialogError('BX.CrmContactEditor: Could not find contact data!');
					}
					else
					{
						self._data = BX.CrmContactData.create(data['DATA']);
						var info = data['INFO'] ? data['INFO'] : {};
						self._clientField.setFieldValue(
							BX.type.isNotEmptyString(info['title'])
								? BX.util.htmlspecialchars(info['title']) : ''
						);
						self.closeDialog();
					}
				},
				onfailure: function(data)
				{
					self._showDialogError(data['ERROR'] ? data['ERROR'] : self.getMessage('unknownError'));
				}
			}
		);
	},
	_showDialogError: function(msg)
	{
		if(this._dlg)
		{
			this._dlg.showError(msg);
		}
	}
};

BX.CrmContactEditor.create = function(id, settings)
{
	var self = new BX.CrmContactEditor();
	self.initialize(id, settings);
	return self;
};

BX.CrmSonetSubscription = function()
{
	this._id = '';
	this._settings = {};
};

BX.CrmSonetSubscription.prototype =
{
	initialize: function(id, settings)
	{
		this._id = id;
		this._settings = settings ? settings : {};
	},
	getSetting: function (name, defaultval)
	{
		return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	},
	enableSubscription: function(entityId, enable, callback)
	{
		var url = this.getSetting("serviceUrl", "");
		var action = this.getSetting("actionName", "");

		if(!(BX.type.isNotEmptyString(url) && BX.type.isNotEmptyString(action)))
		{
			return;
		}

		var reload = this.getSetting("reload", false);
		//var self = this;
		BX.ajax(
			{
				"url": url,
				"method": "POST",
				"dataType": "json",
				"data":
				{
					"ACTION" : action,
					"ENTITY_TYPE": this.getSetting("entityType", ""),
					"ENTITY_ID": entityId,
					"ENABLE": enable ? "Y" : "N"
				},
				onsuccess: function(data)
				{
					if(BX.type.isFunction(callback))
					{
						callback();
					}
				},
				onfailure: function(data) {}
			}
		);
	},
	subscribe: function(entityId, callback)
	{
		this.enableSubscription(entityId, true, callback);
	},
	unsubscribe: function(entityId, callback)
	{
		this.enableSubscription(entityId, false, callback);
	}
};

BX.CrmSonetSubscription.items = {};
BX.CrmSonetSubscription.create = function(id, settings)
{
	var self = new BX.CrmSonetSubscription();
	self.initialize(id, settings);
	this.items[id] = self;
	return self;
};

if(typeof(BX.CrmFormTabLazyLoader) == "undefined")
{
	BX.CrmFormTabLazyLoader = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._wrapper = null;
		this._serviceUrl = "";
		this._formId = "";
		this._tabId = "";
		this._params = {};
		this._formManager = null;

		this._isRequestRunning = false;
		this._isLoaded = false;

		this._waiter = null;
		this._scrollHandler = BX.delegate(this._onWindowScroll, this);
		this._formManagerHandler = BX.delegate(this._onFormManagerCreate, this);
	};

	BX.CrmFormTabLazyLoader.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_lf_disp_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._container = BX(this.getSetting("containerID", ""));
			if(!this._container)
			{
				throw "Error: Could not find container.";
			}

			this._wrapper = BX.findParent(this._container, { "tagName": "DIV", "className": "bx-edit-tab-inner" });

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "Error. Could not find service url.";
			}

			this._formId = this.getSetting("formID", "");
			if(!BX.type.isNotEmptyString(this._formId))
			{
				throw "Error: Could not find form id.";
			}

			this._tabId = this.getSetting("tabID", "");
			if(!BX.type.isNotEmptyString(this._tabId))
			{
				throw "Error: Could not find tab id.";
			}

			this._params = this.getSetting("params", {});

			var formManager = window["bxForm_" + this._formId];
			if(formManager)
			{
				this.setFormManager(formManager);
			}
			else
			{
				BX.addCustomEvent(window, "CrmInterfaceFormCreated", this._formManagerHandler);
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		load: function()
		{
			if(this._isLoaded)
			{
				return;
			}

			var params = this._params;
			params["FORM_ID"] = this._formId;
			params["TAB_ID"] = this._tabId;

			this._startRequest(params);
		},
		getContainerRect: function()
		{
			var r = this._container.getBoundingClientRect();
			return(
				{
					top: r.top, bottom: r.bottom, left: r.left, right: r.right,
					width: typeof(r.width) !== "undefined" ? r.width : (r.right - r.left),
					height: typeof(r.height) !== "undefined" ? r.height : (r.bottom - r.top)
				}
			);
		},
		isContanerInClientRect: function()
		{
			return this.getContainerRect().top <= document.documentElement.clientHeight;
		},
		setFormManager: function(formManager)
		{
			if(this._formManager === formManager)
			{
				return;
			}

			this._formManager = formManager;
			if(!this._formManager)
			{
				return;
			}

			if(this._formManager.GetActiveTabId() !== this._tabId)
			{
				BX.addCustomEvent(window, 'BX_CRM_INTERFACE_FORM_TAB_SELECTED', BX.delegate(this._onFormTabSelect, this));
			}
			else
			{
				if(this.isContanerInClientRect())
				{
					this.load();
				}
				else
				{
					BX.bind(window, "scroll", this._scrollHandler);
				}
			}
		},
		_startRequest: function(params)
		{
			if(this._isRequestRunning)
			{
				return false;
			}

			this._isRequestRunning = true;
			this._waiter = BX.showWait(this._container);
			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "html",
					data:
					{
						"LOADER_ID": this._id,
						"PARAMS": params
					},
					onsuccess: BX.delegate(this._onRequestSuccess, this),
					onfailure: BX.delegate(this._onRequestFailure, this)
				}
			);

			return true;
		},
		_onRequestSuccess: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._container, this._waiter);
				this._waiter = null;
			}

			this._container.innerHTML = data;
			this._isLoaded = true;
		},
		_onRequestFailure: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._container, this._waiter);
				this._waiter = null;
			}
			this._isLoaded = true;
		},
		_onFormManagerCreate: function(formManager)
		{
			if(formManager["name"] === this._formId)
			{
				BX.removeCustomEvent(window, "CrmInterfaceFormCreated", this._formManagerHandler);
				this.setFormManager(formManager);
			}
		},
		_onFormTabSelect: function(sender, formId, tabId, tabContainer)
		{
			if(this._formId === formId && (tabId === this._tabId || this._wrapper === tabContainer))
			{
				this.load();
			}
		},
		_onWindowScroll: function(e)
		{
			if(!this._isLoaded && !this._isRequestRunning && this.isContanerInClientRect())
			{
				BX.unbind(window, "scroll", this._scrollHandler);
				this.load();
			}
		}
	};

	BX.CrmFormTabLazyLoader.items = {};
	BX.CrmFormTabLazyLoader.create = function(id, settings)
	{
		var self = new BX.CrmFormTabLazyLoader();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.CrmCustomDragItem) === "undefined")
{
	BX.CrmCustomDragItem = function()
	{
		this._id = "";
		this._settings = {};
		this._node = null;
		this._ghostNode = null;
		this._ghostOffset = { x: 0, y: 0 };

		this._enableDrag = true;
		this._isInDragMode = false;
		this._dragNotifier = null;
		this._bodyOverflow = "";
	};
	BX.CrmCustomDragItem.prototype =
	{
		initialize: function(id, settings)
		{
			if(typeof(jsDD) === "undefined")
			{
				throw "CrmCustomDragItem: Could not find jsDD API.";
			}

			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(8);
			this._settings = settings ? settings : {};

			this._node = this.getSetting("node");
			if(!this._node)
			{
				throw "CrmCustomDragItem: The 'node' parameter is not defined in settings or empty.";
			}

			this._enableDrag = this.getSetting("enableDrag", true);
			this._ghostOffset = this.getSetting("ghostOffset", { x: 0, y: 0 });

			this._dragNotifier = BX.CrmNotifier.create(this);

			this.doInitialize();
			this.bindEvents();
		},
		doInitialize: function()
		{
		},
		release: function()
		{
			this.doRelease();
			this.unbindEvents();
		},
		doRelease: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		bindEvents: function()
		{
			this._node.onbxdragstart = BX.delegate(this._onDragStart, this);
			this._node.onbxdrag = BX.delegate(this._onDrag, this);
			this._node.onbxdragstop = BX.delegate(this._onDragStop, this);
			this._node.onbxdragrelease = BX.delegate(this._onDragRelease, this);

			jsDD.registerObject(this._node);

			this.doBindEvents();
		},
		doBindEvents: function()
		{
		},
		unbindEvents: function()
		{
			delete this._node.onbxdragstart;
			delete this._node.onbxdrag;
			delete this._node.onbxdragstop;
			delete this._node.onbxdragrelease;

			if(BX.type.isFunction(jsDD.unregisterObject))
			{
				jsDD.unregisterObject(this._node);
			}

			this.doUnbindEvents();
		},
		doUnbindEvents: function()
		{
		},
		createGhostNode: function()
		{
			throw "CrmCustomDragItem: The 'createGhostNode' function is not implemented.";
		},
		getGhostNode: function()
		{
			return this._ghostNode;
		},
		removeGhostNode: function()
		{
			throw "CrmCustomDragItem: The 'removeGhostNode' function is not implemented.";
		},
		processDragStart: function()
		{
		},
		processDrag: function(x, y)
		{
		},
		processDragStop: function()
		{
		},
		addDragListener: function(listener)
		{
			this._dragNotifier.addListener(listener);
		},
		removeDragListener: function(listener)
		{
			this._dragNotifier.removeListener(listener);
		},
		getContextId: function()
		{
			return "";
		},
		getContextData: function()
		{
			return {};
		},
		getScrollTop: function()
		{
			var html = document.documentElement;
			var body = document.body;

			var scrollTop = html.scrollTop || body && body.scrollTop || 0;
			scrollTop -= html.clientTop;

			return scrollTop;
		},
		isDragDropBinEnabled: function()
		{
			return true;
		},
		_onDragStart: function()
		{
			if(!this._enableDrag)
			{
				return;
			}

			this.createGhostNode();

			var pos = BX.pos(this._node);
			this._ghostNode.style.top = pos.top + "px";
			this._ghostNode.style.left = pos.left + "px";

			this._isInDragMode = true;
			BX.CrmCustomDragItem.currentDragged = this;

			BX.onCustomEvent('CrmDragItemDragStart', [this]);
			this.processDragStart();

			window.setTimeout(BX.delegate(this._prepareDocument, this), 0);
		},
		_onDrag: function(x, y)
		{
			if(!this._isInDragMode)
			{
				return;
			}

			if(this._ghostNode)
			{
				this._ghostNode.style.top = (y + this._ghostOffset.y) + "px";
				this._ghostNode.style.left = (x + this._ghostOffset.x) + "px";
			}

			var scrollTop = this.getScrollTop();
			if(scrollTop > 0 && y <= scrollTop)
			{
				window.scrollTo(0, 0);
				return;
			}

			this.processDrag(x, y);
			this._dragNotifier.notify([x, y]);
		},
		_onDragStop: function(x, y)
		{
			if(!this._isInDragMode)
			{
				return;
			}

			this.removeGhostNode();
			this._isInDragMode = false;
			if(BX.CrmCustomDragItem.currentDragged === this)
			{
				BX.CrmCustomDragItem.currentDragged = null;
			}

			BX.onCustomEvent('CrmDragItemDragStop', [this]);
			this.processDragStop();

			window.setTimeout(BX.delegate(this._resetDocument, this), 0);
		},
		_onDragRelease: function(x, y)
		{
			BX.onCustomEvent('CrmDragItemDragRelease', [this]);
		},
		_prepareDocument: function()
		{
			this._bodyOverflow = document.body.style.overflow;
			document.body.style.overflow = "hidden";
		},
		_resetDocument: function()
		{
			document.body.style.overflow = this._bodyOverflow;
		}
	};
	BX.CrmCustomDragItem.currentDragged = null;
	BX.CrmCustomDragItem.emulateDrag = function()
	{
		jsDD.refreshDestArea();
		if(jsDD.current_node)
		{
			//Emilating drag event on previous drag position
			jsDD.drag({ clientX: (jsDD.x - jsDD.wndSize.scrollLeft), clientY: (jsDD.y - jsDD.wndSize.scrollTop) });
		}
	};
}
if(typeof(BX.CrmCustomDragContainer) === "undefined")
{
	BX.CrmCustomDragContainer = function()
	{
		this._id = "";
		this._settings = {};
		this._node = null;
		this._itemDragHandler = BX.delegate(this._onItemDrag, this);
		this._draggedItem = null;
		this._dragFinishNotifier = null;
		this._enabled = true;
	};
	BX.CrmCustomDragContainer.prototype =
	{
		initialize: function(id, settings)
		{
			if(typeof(jsDD) === "undefined")
			{
				throw "CrmCustomDragContainer: Could not find jsDD API.";
			}

			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(8);
			this._settings = settings ? settings : {};

			this._node = this.getSetting("node");
			if(!this._node)
			{
				throw "CrmCustomDragContainer: The 'node' parameter is not defined in settings or empty.";
			}

			this._dragFinishNotifier = BX.CrmNotifier.create(this);
			this.doInitialize();
			this.bindEvents();
		},
		doInitialize: function()
		{
		},
		release: function()
		{
			this.doRelease();
			this.unbindEvents();
		},
		doRelease: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		bindEvents: function()
		{
			this._node.onbxdestdraghover = BX.delegate(this._onDragOver, this);
			this._node.onbxdestdraghout = BX.delegate(this._onDragOut, this);
			this._node.onbxdestdragfinish = BX.delegate(this._onDragFinish, this);
			this._node.onbxdragstop = BX.delegate(this._onDragStop, this);
			this._node.onbxdragrelease = BX.delegate(this._onDragRelease, this);

			jsDD.registerDest(this._node, this.getPriority());

			this.doBindEvents();
		},
		doBindEvents: function()
		{
		},
		unbindEvents: function()
		{
			delete this._node.onbxdestdraghover;
			delete this._node.onbxdestdraghout;
			delete this._node.onbxdestdragfinish;
			delete this._node.onbxdragstop;
			delete this._node.onbxdragrelease;

			if(BX.type.isFunction(jsDD.unregisterDest))
			{
				jsDD.unregisterDest(this._node);
			}

			this.doUnbindEvents();
		},
		doUnbindEvents: function()
		{
		},
		createPlaceHolder: function(pos)
		{
			throw "CrmCustomDragContainer: The 'createPlaceHolder' function is not implemented.";
		},
		removePlaceHolder: function()
		{
			throw "CrmCustomDragContainer: The 'removePlaceHolder' function is not implemented.";
		},
		initializePlaceHolder: function(pos)
		{
			this.createPlaceHolder(pos);
			this.refresh();
		},
		releasePlaceHolder: function()
		{
			this.removePlaceHolder();
			this.refresh();
		},
		getPriority: function()
		{
			return BX.CrmCustomDragContainer.defaultPriority;
		},
		addDragFinishListener: function(listener)
		{
			this._dragFinishNotifier.addListener(listener);
		},
		removeDragFinishListener: function(listener)
		{
			this._dragFinishNotifier.removeListener(listener);
		},
		getDraggedItem: function()
		{
			return this._draggedItem;
		},
		setDraggedItem: function(draggedItem)
		{
			if(this._draggedItem === draggedItem)
			{
				return;
			}

			if(this._draggedItem)
			{
				this._draggedItem.removeDragListener(this._itemDragHandler);
			}

			this._draggedItem = draggedItem;

			if(this._draggedItem)
			{
				this._draggedItem.addDragListener(this._itemDragHandler);
			}
		},
		isAllowedContext: function(contextId)
		{
			return true;
		},
		isEnabled: function()
		{
			return this._enabled;
		},
		enable: function(enable)
		{
			enable = !!enable;
			if(this._enabled === enable)
			{
				return;
			}

			this._enabled = enable;
			if(enable)
			{
				jsDD.enableDest(this._node);
			}
			else
			{
				jsDD.disableDest(this._node);
			}
		},
		refresh: function()
		{
			jsDD.refreshDestArea(this._node.__bxddeid);
		},
		processDragOver: function(pos)
		{
			this.initializePlaceHolder(pos);
		},
		processDragOut: function()
		{
			this.releasePlaceHolder();
		},
		processDragStop: function()
		{
			this.releasePlaceHolder();
		},
		processDragRelease: function()
		{
			this.releasePlaceHolder();
		},
		processItemDrop: function()
		{
			this.releasePlaceHolder();
		},
		_onDragOver: function(node, x, y)
		{
			var draggedItem = BX.CrmCustomDragItem.currentDragged;
			if(!draggedItem)
			{
				return;
			}

			if(!this.isAllowedContext(draggedItem.getContextId()))
			{
				return;
			}

			this.setDraggedItem(draggedItem);
			this.processDragOver({ x: x, y: y });
		},
		_onDragOut: function(node, x, y)
		{
			if(!this._draggedItem)
			{
				return;
			}

			this.processDragOut();
			this.setDraggedItem(null);
		},
		_onDragFinish: function(node, x, y)
		{
			if(!this._draggedItem)
			{
				return;
			}

			this._dragFinishNotifier.notify([this._draggedItem, x, y]);

			this.processItemDrop();
			this.setDraggedItem(null);

			BX.CrmCustomDragContainer.refresh();
		},
		_onDragRelease: function(node, x, y)
		{
			if(!this._draggedItem)
			{
				return;
			}

			this.processDragRelease();
			this.setDraggedItem(null);
		},
		_onDragStop: function(node, x, y)
		{
			if(!this._draggedItem)
			{
				return;
			}

			this.processDragStop();
			this.setDraggedItem(null);
		},
		_onItemDrag: function(item, x, y)
		{
			if(!this._draggedItem)
			{
				return;
			}

			this.initializePlaceHolder({ x: x, y: y });
		}
	};
	BX.CrmCustomDragContainer.defaultPriority = 100;
	BX.CrmCustomDragContainer.refresh = function()
	{
		jsDD.refreshDestArea();
	};
}

BX.CrmDragDropBinState = { suspend: 0, wait: 1, ready: 2, open: 3, close: 4 };

if(typeof(BX.CrmDragDropBin) === "undefined")
{
	BX.CrmDragDropBin = function()
	{
		this._state = BX.CrmDragDropBinState.suspend;
		this._chargeItem = null;

		this._enableChargeItem = false;
		this._chargeDragStartHandler = BX.delegate(this._onChargeDragStart, this);
		this._chargeDragStopHandler = BX.delegate(this._onChargeDragStop, this);
		this._chargeDragReleaseHandler = BX.delegate(this._onChargeDragRelease, this);
		this._chargeDragHandler = BX.delegate(this._onChargeDrag, this);

		this._workareaRect = null;

		this._promptingWrapper = null;
		this._closePromptingButtonId = "crm_dd_bin_close_prompting_btn";
		this._closePromptingHandler = BX.delegate(this._onClosePromptingButtonClick, this);

		this._demoButtonId = "crm_dd_bin_demo_btn";
		this._demoHandler = BX.delegate(this._onDemoButtonClick, this);

	};
	BX.extend(BX.CrmDragDropBin, BX.CrmCustomDragContainer);
	BX.CrmDragDropBin.prototype.doInitialize = function()
	{
		BX.addCustomEvent(window, "CrmDragItemDragStart", this._chargeDragStartHandler);
		BX.addCustomEvent(window, "CrmDragItemDragStop", this._chargeDragStopHandler);
		BX.addCustomEvent(window, "CrmDragItemDragRelease", this._chargeDragReleaseHandler);

		this.cacheWorkareaRect();
		BX.bind(window, "resize", BX.delegate(this._onWindowResize, this));
	};
	BX.CrmDragDropBin.prototype.getPriority = function()
	{
		return 10;
	};
	BX.CrmDragDropBin.prototype.createPlaceHolder = function(pos)
	{
	};
	BX.CrmDragDropBin.prototype.removePlaceHolder = function()
	{
	};
	BX.CrmDragDropBin.prototype.processDragOver = function(pos)
	{
		if(this._chargeItem)
		{
			this._enableChargeItem = false;
		}
		this.setState(BX.CrmDragDropBinState.open);
	};
	BX.CrmDragDropBin.prototype.processDragOut = function()
	{
		if(this._chargeItem)
		{
			this._enableChargeItem = true;
		}
		this.setState(BX.CrmDragDropBinState.ready);
	};
	BX.CrmDragDropBin.prototype.processDragStop = function()
	{
		this.setState(BX.CrmDragDropBinState.suspend);
	};
	BX.CrmDragDropBin.prototype.processDragRelease = function()
	{
		this.setState(BX.CrmDragDropBinState.suspend);
	};
	BX.CrmDragDropBin.prototype.processItemDrop = function()
	{
		if(this._chargeItem)
		{
			this._chargeItem.removeDragListener(this._chargeDragHandler);
			this._chargeItem = null;
		}
		this._enableChargeItem = false;

		this.setState(BX.CrmDragDropBinState.close);
		window.setTimeout(BX.delegate(this.reset, this), 1000);
		BX.onCustomEvent(this, "CrmDragDropBinItemDrop", [ this, this.getDraggedItem() ]);
	};
	BX.CrmDragDropBin.prototype.getState = function()
	{
		return this._state;
	};
	BX.CrmDragDropBin.prototype.reset = function()
	{
		this.setState(BX.CrmDragDropBinState.suspend);
	};
	BX.CrmDragDropBin.prototype.setState = function(state)
	{
		state = parseInt(state);
		if(state < BX.CrmDragDropBinState.suspend || state > BX.CrmDragDropBinState.close)
		{
			state = BX.CrmDragDropBinState.suspend;
		}

		if(this._state === state)
		{
			return;
		}

		this._state = state;

		var classNames = ["crm-cart-block-wrap"];
		if(this._state >= BX.CrmDragDropBinState.wait)
		{
			classNames.push("crm-cart-start");
		}
		if(this._state >= BX.CrmDragDropBinState.ready)
		{
			classNames.push("crm-cart-active");
		}
		if(this._state >= BX.CrmDragDropBinState.open)
		{
			classNames.push("crm-cart-hover");
		}
		if(this._state === BX.CrmDragDropBinState.close)
		{
			classNames.push("crm-cart-finish");
		}

		this._node.className = classNames.join(" ");

		window.setTimeout(BX.delegate(BX.CrmCustomDragItem.emulateDrag, this), 400);
		window.setTimeout(BX.delegate(BX.CrmCustomDragItem.emulateDrag, this), 800);
	};
	BX.CrmDragDropBin.prototype._onChargeDragStart = function(item)
	{
		if(!item.isDragDropBinEnabled())
		{
			return;
		}

		this._enableChargeItem = true;
		this._chargeItem = item;
		this._chargeItem.addDragListener(this._chargeDragHandler);

		this.setState(BX.CrmDragDropBinState.wait);
	};
	BX.CrmDragDropBin.prototype._onChargeDragStop = function(item)
	{
		if(!this._enableChargeItem || this._chargeItem !== item)
		{
			return;
		}

		this._chargeItem.removeDragListener(this._chargeDragHandler);
		this._chargeItem = null;
		this._enableChargeItem = false;

		this.setState(BX.CrmDragDropBinState.suspend);
	};
	BX.CrmDragDropBin.prototype._onChargeDragRelease = function(item)
	{
		if(!this._enableChargeItem || this._chargeItem !== item)
		{
			return;
		}

		this._chargeItem.removeDragListener(this._chargeDragHandler);
		this._chargeItem = null;
		this._enableChargeItem = false;

		this.setState(BX.CrmDragDropBinState.suspend);
	};
	BX.CrmDragDropBin.prototype._onChargeDrag = function(item, x, y)
	{
		if(this._enableChargeItem && this._chargeItem === item)
		{
			this.adjust();
		}
	};
	BX.CrmDragDropBin.prototype._onWindowResize = function(e)
	{
		this.cacheWorkareaRect();
	};
	BX.CrmDragDropBin.prototype.cacheWorkareaRect = function()
	{
		var workarea = BX("workarea");
		if(!workarea)
		{
			workarea = document.documentElement;
		}
		this._workareaRect = BX.pos(workarea);
		this._readyThreshold = this._workareaRect.width / 6;
	};
	BX.CrmDragDropBin.prototype.adjust = function()
	{
		if(!this._chargeItem)
		{
			return;
		}

		var ghostNode = this._chargeItem.getGhostNode();
		if(!ghostNode)
		{
			return;
		}

		var ghostRect = BX.pos(ghostNode);
		var isReady = this._state >= BX.CrmDragDropBinState.ready;
		if(isReady !== ((this._workareaRect.right - ghostRect.left) <= this._readyThreshold))
		{
			isReady = !isReady;
			this.setState(isReady ? BX.CrmDragDropBinState.ready : BX.CrmDragDropBinState.wait);
		}
	};
	BX.CrmDragDropBin.prototype.getMessage = function(name, defaultval)
	{
		var m = BX.CrmDragDropBin.messages;
		return m.hasOwnProperty(name) ? m[name] : defaultval;
	};
	BX.CrmDragDropBin.prototype.showPromptingIfRequired = function(container)
	{
		if(BX.localStorage.get("crm_dd_bin_show_prompt") !== "N")
		{
			this.showPrompting(container);
		}
	};
	BX.CrmDragDropBin.prototype.showPrompting = function(container)
	{
		if(this._promptingWrapper)
		{
			return;
		}

		var msg = this.getMessage("prompting");
		msg = msg.replace("#CLOSE_BTN_ID#", this._closePromptingButtonId).replace("#DEMO_BTN_ID#", this._demoButtonId);
		this._promptingWrapper = BX.create("DIV", { attrs: { className: "crm-view-message" }, html: msg });
		container.appendChild(this._promptingWrapper);

		BX.bind(BX(this._closePromptingButtonId), "click", this._closePromptingHandler);
		BX.bind(BX(this._demoButtonId), "click", this._demoHandler);
	};
	BX.CrmDragDropBin.prototype.hidePrompting = function()
	{
		if(!this._promptingWrapper)
		{
			return;
		}

		BX.localStorage.set("crm_dd_bin_show_prompt", "N", 31104000);
		BX.unbind(BX(this._closePromptingButtonId), "click", this._closePromptingHandler);
		BX.unbind(BX(this._demoButtonId), "click", this._demoHandler);
		BX.remove(this._promptingWrapper);
	};
	BX.CrmDragDropBin.prototype.demo = function()
	{
		this.setState(BX.CrmDragDropBinState.wait);

		var self = this;
		window.setTimeout(function(){ self.setState(BX.CrmDragDropBinState.ready); }, 1000);
		window.setTimeout(function(){ self.setState(BX.CrmDragDropBinState.open); }, 1500);
		window.setTimeout(function(){ self.setState(BX.CrmDragDropBinState.close); }, 2000);
	};
	BX.CrmDragDropBin.prototype._onDemoButtonClick = function(e)
	{
		this.demo();
		return BX.PreventDefault(e);
	};
	BX.CrmDragDropBin.prototype._onClosePromptingButtonClick = function(e)
	{
		this.hidePrompting();
		return BX.PreventDefault(e);
	};
	BX.CrmDragDropBin.instance = null;
	BX.CrmDragDropBin.getInstance = function()
	{
		if(this.instance)
		{
			return this.instance;
		}

		var node = BX.create("DIV",
			{
				attrs: { className: "crm-cart-block-wrap" },
				children:
				[
					BX.create("DIV",
						{
							attrs: { className: "crm-cart-block" },
							children:
							[
								BX.create("DIV",
									{
										attrs: { className: "crm-cart-icon" },
										children:
										[
											BX.create("DIV", { attrs: { className: "crm-cart-icon-top" } }),
											BX.create("DIV", { attrs: { className: "crm-cart-icon-body" } })
										]
									}
								)
							]
						}
					)
				]
			}
		);
		document.body.appendChild(node);
		var self = new BX.CrmDragDropBin();
		self.initialize("default", { node: node });
		return (this.instance = self);
	};

	if(typeof(BX.CrmDragDropBin.messages) === "undefined")
	{
		BX.CrmDragDropBin.messages = {};
	}
}

if(typeof(BX.CrmLocalitySearchField) === "undefined")
{
	BX.CrmLocalitySearchField = function()
	{
		this._id = "";
		this._settings = {};
		this._localityType = "";
		this._serviceUrl = "";
		this._searchInput = null;
		this._dataInput = null;
		this._timeoutId = 0;
		this._value = "";
		this._items = [];
		this._menuId = "crm-locality-search";
		this._menu = null;
		this._isRequestStarted = false;

		this._checkHandler = BX.delegate(this.check, this);
		this._keyPressHandler = BX.delegate(this.onKeyPress, this);
		this._menuItemClickHandler = BX.delegate(this.onMenuItemClick, this);
		this._searchCompletionHandler =  BX.delegate(this.onSearchRequestComplete, this);
	};

	BX.CrmLocalitySearchField.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : ('crm_loc_search_field_' + Math.random());
			this._settings = settings ? settings : {};

			this._localityType = this.getSetting("localityType");
			if(!BX.type.isNotEmptyString(this._localityType))
			{
				throw  "BX.CrmLocalitySearchField: localityType is not found!";
			}

			this._serviceUrl = this.getSetting("serviceUrl");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw  "BX.CrmLocalitySearchField: serviceUrl is not found!";
			}

			this._searchInput = this.findElement("searchInput");
			if(!BX.type.isElementNode(this._searchInput))
			{
				throw  "BX.CrmLocalitySearchField: searchInput is not found!";
			}

			this._dataInput = this.findElement("dataInput");
			if(!BX.type.isElementNode(this._dataInput))
			{
				throw  "BX.CrmLocalitySearchField: dataInputId is not found!";
			}

			BX.bind(this._searchInput, "keyup", BX.proxy(this._keyPressHandler, this));
			BX.bind(document, "click", BX.delegate(this._handleExternalClick, this));
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		findElement: function(paramName)
		{
			var param = this.getSetting(paramName);
			if(BX.type.isElementNode(paramName))
			{
				return param;
			}

			var element = null;
			if(BX.type.isNotEmptyString(param))
			{
				element = BX(param);
				if(!element)
				{
					var elements = document.getElementsByName(param);
					if(elements.length > 0)
					{
						element = elements[0];
					}
				}
			}
			return element !== undefined ? element : null;
		},
		check: function()
		{
			this._timeoutId = 0;
			if(this._value !== this._searchInput.value)
			{
				this._value = this._searchInput.value;
				this._timeoutId = window.setTimeout(this._checkHandler, 750);
			}
			else if(this._value.length >= 2)
			{
				this.startSearchRequest(this._value);
			}
		},
		startSearchRequest: function(needle)
		{
			if(this._isRequestStarted)
			{
				return false;
			}

			this._isRequestStarted = true;

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "FIND_LOCALITIES",
						"LOCALITY_TYPE": this._localityType,
						"NEEDLE": needle
					},
					onsuccess: this._searchCompletionHandler,
					onfailure: this._searchCompletionHandler
				}
			);
		},
		showMenu: function(items)
		{
			BX.PopupMenu.destroy(this._menuId);

			var menuItems = [];
			for(var i = 0; i < items.length; i++)
			{
				menuItems.push(this.prepareMenuItem(items[i]));
			}

			this._menu = BX.PopupMenu.create(this._menuId, this._searchInput, menuItems, { offsetTop:0, offsetLeft:0 });
			this._menu.popupWindow.show();
		},
		closeMenu: function()
		{
			BX.PopupMenu.destroy(this._menuId);
			this._menu = null;
		},
		prepareMenuItem: function(data)
		{
			var code = BX.type.isNotEmptyString(data["CODE"]) ? data["CODE"] : "";
			if(code === "")
			{
				throw  "BX.CrmLocalitySearchField: could not find item code!";
			}
			var caption = BX.type.isNotEmptyString(data["CAPTION"]) ? data["CAPTION"] : code;
			return { value: code,  text: caption, onclick: this._menuItemClickHandler };
		},
		onMenuItemClick: function(e, item)
		{
			this.selectItem(item);
			this.closeMenu();
		},
		selectItem: function(item)
		{
			this._dataInput.value = item["value"];
			this._searchInput.value = item["text"];
		},
		onKeyPress: function(e)
		{
			if(this._timeoutId !== 0)
			{
				window.clearTimeout(this._timeoutId);
				this._timeoutId = 0;
			}
			this._timeoutId = window.setTimeout(this._checkHandler, 375);
		},
		onSearchRequestComplete: function(result)
		{
			this._isRequestStarted = false;

			var items = typeof(result["DATA"]) !== "undefined" && typeof(result["DATA"]["ITEMS"]) !== "undefined"
				? result["DATA"]["ITEMS"] : [];

			if(items.length > 0)
			{
				this.showMenu(items);
			}
		}
	};

	BX.CrmLocalitySearchField.create = function(id, settings)
	{
		var self = new BX.CrmLocalitySearchField();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmAddressType) === "undefined")
{
	BX.CrmAddressType =
	{
		undefined: 0,
		primary: 1,
		secondary: 2,
		third: 3,
		home: 4,
		work: 5,
		registered: 6,
		custom: 7,
		post: 8,
		beneficiary: 9,
		bank: 10
	};
}

if(typeof(BX.CrmMultipleAddressEditor) === "undefined")
{
	BX.CrmMultipleAddressEditor = function()
	{
		this._id = "";
		this._settings = {};

		this._fieldId = "";
		this._formId = "";
		this._scheme = null;
		this._data = null;
		this._currentTypeId = 0;
		this._typeInfos = null;
		this._fieldLabels = null;

		this._fielNameTemplate = "";
		this._serviceUrl = "";

		this._container = null;
		this._createButton = null;
		this._typeMenuButton = null;

		this._createButtonHandler = BX.delegate(this.onCreateButtonClick, this);
		this._typenuButtonHandler = BX.delegate(this.onTypeMenuButtonClick, this);
		this._typeMenuItemClickHandler = BX.delegate(this.onTypeMenuItemClick, this);
		this._typeMenuCloseHandler = BX.delegate(this.onTypeMenuClose, this);
		this._typeMenuId = "";
		this._isTypeMenuOpened = false;
		this._items = {};
		this._entityAddresses = {};
	};

	BX.CrmMultipleAddressEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : ("crm_multiaddr_" + Math.random());
			this._settings = settings ? settings : {};

			this._fieldId = this.getSetting("fieldId", "");
			this._formId = this.getSetting("formId", "");;

			this._scheme = this.getSetting("scheme");
			if(!BX.type.isArray(this._scheme))
			{
				throw "BX.CrmMultipleAddressEditor: Could not find a parameter named 'scheme'.";
			}

			this._data = this.getSetting("data");
			if(!BX.type.isPlainObject(this._data))
			{
				throw "BX.CrmMultipleAddressEditor: Could not find a parameter named 'data'.";
			}

			this._typeInfos = this.getSetting("typeInfos");
			if(!BX.type.isArray(this._typeInfos))
			{
				throw "BX.CrmMultipleAddressEditor: Could not find a parameter named 'typeInfos'.";
			}

			this._fieldLabels = this.getSetting("fieldLabels");
			if(!BX.type.isPlainObject(this._fieldLabels))
			{
				throw "BX.CrmMultipleAddressEditor: Could not find a parameter named 'fieldLabels'.";
			}

			this._container = this.getSetting("container");
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmMultipleAddressEditor: Could not find a parameter named 'container'.";
			}

			this._itemWrapper = BX.findChildByClassName(this._container, "crm-multi-address");

			var createButtonContainer = this.getSetting("createButtonContainer");
			if(BX.type.isElementNode(createButtonContainer))
			{
				this._createButton = BX.findChildByClassName(createButtonContainer, "crm-offer-requisite-option-text");
				if(this._createButton)
				{
					BX.bind(this._createButton, "click", this._createButtonHandler);
				}

				this._typeMenuButton = BX.findChildByClassName(createButtonContainer, "crm-offer-requisite-option-arrow");
				if(this._typeMenuButton)
				{
					BX.bind(this._typeMenuButton, "click", this._typenuButtonHandler);
				}
			}

			this._currentTypeId = this.getSetting("currentTypeId");
			this._fielNameTemplate = this.getSetting("fielNameTemplate", "");
			this._serviceUrl = this.getSetting("serviceUrl", "");

			for(var key in this._data)
			{
				if(!this._data.hasOwnProperty(key))
				{
					continue;
				}

				var typeId = parseInt(key);
				var itemId = this._id + "_" + typeId.toString();

				var containerId = this._fielNameTemplate
					.replace("#TYPE_ID#", typeId)
					.replace("#FIELD_NAME#", "wrapper")
					.toLowerCase();

				this._items[itemId] = BX.CrmMultipleAddressItemEditor.create(
					itemId,
					{
						typeId: typeId,
						fields: this._data[key],
						editor: this,
						container: BX(containerId),
						hasLayout: true,
						isPersistent: true
					}
				);
			}
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getFieldId: function()
		{
			return this._fieldId;
		},
		getFormId: function()
		{
			return this._formId;
		},
		getScheme: function()
		{
			return this._scheme;
		},
		getTypeName: function(typeId)
		{
			for(var i = 0; i < this._typeInfos.length; i++)
			{
				var info = this._typeInfos[i];
				if(info["id"] == typeId)
				{
					return info["name"];
				}
			}

			return typeId;
		},
		getFieldLabel: function(name)
		{
			return this._fieldLabels.hasOwnProperty(name) ? this._fieldLabels[name] : name;
		},
		getFieldNameTemplate: function()
		{
			return this._fielNameTemplate;
		},
		prepareQualifiedName: function(name, params)
		{
			if(this._fielNameTemplate !== "")
			{
				if(!BX.type.isPlainObject(params))
				{
					params = {};
				}

				var typeId = typeof(params["typeId"]) !== "undefined" ? params["typeId"] : this._currentTypeId;
				name = this._fielNameTemplate.replace("#TYPE_ID#", typeId).replace("#FIELD_NAME#", name);
			}
			return name;
		},
		getServiceUrl: function()
		{
			return this._serviceUrl;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmMultipleAddressEditor.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		createItem: function(typeId, originatorId)
		{
			if(!BX.type.isNumber(typeId))
			{
				typeId = parseInt(typeId);
			}

			if(isNaN(typeId) || typeId <= 0)
			{
				typeId = this._currentTypeId;
			}

			if(!BX.type.isString(originatorId))
			{
				originatorId = "";
			}

			var itemContainer = BX.create("DIV",
				{
					attrs: { className: "crm-multi-address-item" }
				}
			);
			this._itemWrapper.appendChild(itemContainer);

			var isPersistent = false;
			var itemId = this._id + "_" + typeId.toString();
			if(this._items.hasOwnProperty(itemId))
			{
				var presetItem = this._items[itemId];
				if(presetItem.isMarkedAsDeleted())
				{
					isPersistent = presetItem.isPersistent();
					this.removeItem(presetItem, true);
				}
				else
				{
					window.alert(
						this.getMessage("alreadyExists")
						.replace("#TYPE_NAME#", this.getTypeName(typeId))
					);
					return false;
				}
			}

			var item = BX.CrmMultipleAddressItemEditor.create(
				itemId,
				{
					typeId: typeId,
					fields: {},
					editor: this,
					container: itemContainer,
					hasLayout: false,
					isPersistent: isPersistent,
					originatorId: originatorId
				}
			);
			this._items[itemId] = item;
			item.layout();

			if(this._itemWrapper.style.display === "none")
			{
				this._itemWrapper.style.display = "";
			}

			BX.onCustomEvent(this, "CrmMultipleAddressItemCreated", [this, item]);

			return item;
		},
		removeItem: function(item, forced)
		{
			var itemId = item.getId();
			if(!this._items.hasOwnProperty(itemId))
			{
				return false;
			}

			if(!item.isPersistent() || !!forced)
			{
				item.cleanLayout();
				BX.remove(item.getContainer());
				delete this._items[itemId];
			}

			if(this.getActiveItemCount() === 0)
			{
				this._itemWrapper.style.display = "none";
			}

			return true;
		},
		getItemByTypeId: function(typeId)
		{
			typeId = parseInt(typeId);
			if(isNaN(typeId) || typeId <= 0)
			{
				return null;
			}

			for(var k in this._items)
			{
				if(!this._items.hasOwnProperty(k))
				{
					continue;
				}

				var item = this._items[k];
				if(typeId === item.getTypeId())
				{
					return item;
				}
			}

			return null;
		},
		getActiveItemCount: function()
		{
			var result = 0;
			for(var k in this._items)
			{
				if(this._items.hasOwnProperty(k) && !this._items[k].isMarkedAsDeleted())
				{
					result++;
				}
			}
			return result;
		},
		loadEntityAddress: function(typeId, entityTypeId, entityId, callback)
		{
			if(BX.type.isPlainObject(this._entityAddresses[entityTypeId])
				&& BX.type.isPlainObject(this._entityAddresses[entityTypeId][entityId]))
			{
				callback(this._entityAddresses[entityTypeId][entityId]["fields"]);
				return;
			}

			if(!BX.type.isPlainObject(this._entityAddresses[entityTypeId]))
			{
				this._entityAddresses[entityTypeId] = {};
			}

			if(!BX.type.isPlainObject(this._entityAddresses[entityTypeId][entityId]))
			{
				this._entityAddresses[entityTypeId][entityId] = {};
			}

			if(BX.type.isFunction(callback))
			{
				if(!BX.type.isArray(this._entityAddresses[entityTypeId][entityId]["callbacks"]))
				{
					this._entityAddresses[entityTypeId][entityId]["callbacks"] = [];
				}

				this._entityAddresses[entityTypeId][entityId]["callbacks"].push(callback);
			}

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION": "GET_ENTITY_ADDRESS",
						"ENTITY_TYPE_ID": entityTypeId,
						"ENTITY_ID": entityId,
						"TYPE_ID": typeId
					},
					onsuccess: BX.delegate(this.onEntityAddressLoadSuccess, this)
				}
			);
		},

		openTypeMenu: function()
		{
			if(this._isTypeMenuOpened)
			{
				return;
			}

			var menuItems = [];
			for(var i = 0; i < this._typeInfos.length; i++)
			{
				var info = this._typeInfos[i];
				menuItems.push(
					{
						text: info["name"],
						id: info["id"],
						className: "crm-convert-item",
						onclick: this._typeMenuItemClickHandler
					}
				);
			}

			if(typeof(BX.PopupMenu.Data[this._typeMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._typeMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._typeMenuId];
			}

			BX.PopupMenu.show(
				this._typeMenuId,
				this._typeMenuButton,
				menuItems,
				{
					autoHide: true,
					offsetLeft: (BX.pos(this._typeMenuButton)["width"] / 2),
					angle: { position: "top", offset: 0 },
					events: { onPopupClose : this._typeMenuCloseHandler }
				}
			);

			this._isTypeMenuOpened = true;
		},
		closeTypeMenu: function()
		{
			if(this._isTypeMenuOpened)
			{
				BX.PopupMenu.destroy(this._typeMenuId);
				this._isTypeMenuOpened = false;
			}
		},
		onCreateButtonClick: function(e)
		{
			this.createItem(this._currentTypeId);
		},
		onTypeMenuButtonClick: function(e)
		{
			if(!this._isTypeMenuOpened)
			{
				this.openTypeMenu();
			}
			else
			{
				this.closeTypeMenu();
			}

			return BX.PreventDefault(e);
		},
		onTypeMenuItemClick: function(e, item)
		{
			this._currentTypeId = parseInt(item["id"]);
			this._createButton.innerHTML = this.getTypeName(this._currentTypeId);
			this.closeTypeMenu();
		},
		onTypeMenuClose: function(e)
		{
			this._isTypeMenuOpened = false;
		},
		onEntityAddressLoadSuccess: function(result)
		{
			if(!BX.type.isPlainObject(result["DATA"]))
			{
				return;
			}

			var data = result["DATA"];
			var entityTypeId = typeof(data["ENTITY_TYPE_ID"]) ? parseInt(data["ENTITY_TYPE_ID"]) : 0;
			var entityId = typeof(data["ENTITY_ID"]) ? parseInt(data["ENTITY_ID"]) : 0;
			if(isNaN(entityTypeId) || entityTypeId <= 0 || isNaN(entityId) || entityId <= 0)
			{
				return;
			}

			if(!BX.type.isPlainObject(this._entityAddresses[entityTypeId]))
			{
				this._entityAddresses[entityTypeId] = {};
			}

			if(!BX.type.isPlainObject(this._entityAddresses[entityTypeId][entityId]))
			{
				this._entityAddresses[entityTypeId][entityId] = {};
			}

			var fields = BX.type.isPlainObject(data["FIELDS"]) ? data["FIELDS"] : null;
			this._entityAddresses[entityTypeId][entityId]["fields"] = fields;

			if(BX.type.isArray(this._entityAddresses[entityTypeId][entityId]["callbacks"]))
			{
				var callbacks = this._entityAddresses[entityTypeId][entityId]["callbacks"];
				for(var i = 0; i < callbacks.length; i++)
				{
					callbacks[i](fields);
				}
				delete this._entityAddresses[entityTypeId][entityId]["callbacks"];
			}
		}
	};

	if(typeof(BX.CrmMultipleAddressEditor.messages) === "undefined")
	{
		BX.CrmMultipleAddressEditor.messages =
		{
		};
	}
	BX.CrmMultipleAddressEditor.items = {};
	BX.CrmMultipleAddressEditor.getItemsByFormId = function(formId)
	{
		var item;

		formId = BX.type.isNotEmptyString(formId) ? formId : "";
		if(formId === "")
		{
			return [];
		}

		var results = [];
		for(var k in this.items)
		{
			if(this.items.hasOwnProperty(k))
			{
				item = this.items[k];
				if(item.getFormId() === formId)
				{
					results.push(item);
				}
			}
		}
		return results;
	};
	BX.CrmMultipleAddressEditor.create = function(id, settings)
	{
		var self = new BX.CrmMultipleAddressEditor();
		self.initialize(id, settings);
		BX.CrmMultipleAddressEditor.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.CrmMultipleAddressItemEditor) === "undefined")
{
	BX.CrmMultipleAddressItemEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._typeId = 0;
		this._fields = {};
		this._editor = null;
		this._container = null;
		this._isPersistent = false;
		this._isMarkedAsDeleted = false;
		this._hasLayout = false;
		this._originatorId = "";
		this._deleteButton = null;
		this._deleteButtonHandler = BX.delegate(this.onDeleteButtonClick, this);
	};

	BX.CrmMultipleAddressItemEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : ("crm_multiaddr_item_" + Math.random());
			this._settings = settings ? settings : {};

			this._typeId = parseInt(this.getSetting("typeId", 0));
			this._fields = this.getSetting("fields", {});

			this._editor = this.getSetting("editor");
			if(!(this._editor instanceof BX.CrmMultipleAddressEditor))
			{
				throw "BX.CrmMultipleAddressItemEditor: Could not find a parameter named 'editor'.";
			}

			this._container = this.getSetting("container");
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmMultipleAddressItemEditor: Could not find a parameter named 'container'.";
			}

			this._isPersistent = !!this.getSetting("isPersistent", false);

			this._hasLayout = !!this.getSetting("hasLayout", false);
			if(this._hasLayout)
			{
				this._deleteButton = BX.findChildByClassName(this._container, "crm-offer-title-del");
				if(!BX.type.isElementNode(this._deleteButton))
				{
					throw "BX.CrmMultipleAddressItemEditor: Could not find the 'Delete' button.";
				}

				this.bind();
			}

			this._originatorId = this.getSetting("originatorId", "");
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmMultipleAddressItemEditor.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getTypeId: function()
		{
			return this._typeId;
		},
		getContainer: function()
		{
			return this._container;
		},
		getOriginatorId: function()
		{
			return this._originatorId;
		},
		getFieldControl: function(fieldName)
		{
			var qualifiedName = this._editor.prepareQualifiedName(fieldName, { typeId: this._typeId });
			var ctrls = document.getElementsByName(qualifiedName);
			return ctrls.length > 0 ? ctrls[0] : null;
		},
		getFieldValue: function(fieldName)
		{
			var ctrl = this.getFieldControl(fieldName);
			return ctrl !== null ? ctrl.value : "";
		},
		setFieldValue: function(fieldName, val)
		{
			var ctrl = this.getFieldControl(fieldName);
			if(ctrl !== null)
			{
				ctrl.value = val;
			}
		},
		setup: function(fields)
		{
			if(!BX.type.isPlainObject(fields))
			{
				return;
			}

			for(var k in fields)
			{
				if(fields.hasOwnProperty(k))
				{
					this.setFieldValue(k, fields[k]);
				}
			}
		},
		bind: function()
		{
			if(this._deleteButton)
			{
				BX.bind(this._deleteButton, "click", this._deleteButtonHandler);
			}
		},
		unbind: function()
		{
			if(this._deleteButton)
			{
				BX.unbind(this._deleteButton, "click", this._deleteButtonHandler);
			}
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var table, row, cell;
			table = BX.create("TABLE", { attrs: { className: "crm-offer-info-table" } });
			this._container.appendChild(table);

			row = table.insertRow(-1);
			cell = row.insertCell(-1);
			cell.colSpan  = "4";

			this._deleteButton = BX.create("SPAN", { attrs: { className: "crm-offer-title-del" } });

			cell.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-offer-title" },
						children:
						[
							BX.create("SPAN",
								{
									attrs: { className: "crm-offer-title-text" },
									text: this._editor.getTypeName(this._typeId)
								}
							),
							BX.create("SPAN",
								{
									attrs: { className: "crm-offer-title-set-wrap" },
									children: [ this._deleteButton ]
								}
							)
						]
					}
				)
			);

			var scheme = this._editor.getScheme();
			for(var i = 0; i < scheme.length; i++)
			{
				var info = scheme[i];
				var type = BX.type.isNotEmptyString(info["type"]) ? info["type"] : "";
				var name = BX.type.isNotEmptyString(info["name"]) ? info["name"] : "";
				var qualifiedName = this._editor.prepareQualifiedName(name, { typeId: this._typeId });
				var val = this._fields.hasOwnProperty(name) ? this._fields[name] : "";

				if(type === "locality")
				{
					row = table.insertRow(-1);
					row.style.display = "none";
					cell = row.insertCell(-1);
					cell.colSpan  = "4";
					cell.appendChild(
						BX.create("INPUT",
							{
								props:
								{
									type: "hidden",
									name: qualifiedName,
									value: val
								}
							}
						)
					);

					var params = BX.type.isPlainObject(info["params"]) ? info["params"] : {};
					var relatedName = BX.type.isNotEmptyString(info["related"]) ? info["related"] : "";
					BX.CrmLocalitySearchField.create(
						qualifiedName,
						{
							localityType: BX.type.isNotEmptyString(params["locality"]) ? params["locality"] : "",
							serviceUrl: this._editor.getServiceUrl(),
							searchInput: this._editor.prepareQualifiedName(relatedName, { typeId: this._typeId }),
							dataInput: qualifiedName
						}
					);
				}
				else
				{
					row = table.insertRow(-1);
					cell = row.insertCell(-1);
					cell.className = "crm-offer-info-left";
					cell.appendChild(BX.create("SPAN", { attrs: { className: "crm-offer-info-label-alignment" } }));
					cell.appendChild(
						BX.create("SPAN",
							{
								attrs: { className: "crm-offer-info-label" },
								text: this._editor.getFieldLabel(name)
							}
						)
					);

					cell = row.insertCell(-1);
					cell.className = "crm-offer-info-right";

					if(type === "multilinetext")
					{
						cell.appendChild(
							BX.create("DIV",
								{
									attrs: { className: "crm-offer-info-data-wrap" },
									children:
										[
											BX.create("TEXTAREA",
												{
													props:
														{
															className: "bx-crm-edit-text-area",
															name: qualifiedName,
															value: val
														}
												}
											)
										]
								}
							)
						);
					}
					else
					{
						cell.appendChild(
							BX.create("DIV",
								{
									attrs: {className: "crm-offer-info-data-wrap"},
									children:
										[
											BX.create("INPUT",
												{
													props:
														{
															className: "crm-offer-item-inp",
															type: "text",
															name: qualifiedName,
															value: val
														}
												}
											)
										]
								}
							)
						);
					}

					cell = row.insertCell(-1);
					cell.className = "crm-offer-info-right-btn";

					cell = row.insertCell(-1);
					cell.className = "crm-offer-last-td";

				}
			}

			this.bind();
			this._hasLayout = true;
		},
		cleanLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this.unbind();
			BX.cleanNode(this._container);

			this._hasLayout = false;
		},
		isPersistent: function()
		{
			return this._isPersistent;
		},
		markAsDeleted: function()
		{
			if(this._isMarkedAsDeleted)
			{
				return;
			}

			this._isMarkedAsDeleted = true;

			this._container.appendChild(
				BX.create("INPUT",
					{
						props:
						{
							"name": this._editor.prepareQualifiedName("DELETED", { typeId: this._typeId }),
							"type": "hidden",
							"value": "Y"
						}
					}
				)
			);
			this._container.style.display = "none";
		},
		isMarkedAsDeleted: function()
		{
			return this._isMarkedAsDeleted;
		},
		setupByEntity: function(entityTypeId, entityId)
		{
			this._editor.loadEntityAddress(
				this.getTypeId(),
				entityTypeId,
				entityId,
				BX.delegate(this.onEntityAddressLoad, this)
			);
		},
		onEntityAddressLoad: function(fields)
		{
			if(!BX.type.isPlainObject(fields))
			{
				return;
			}

			if(window.confirm(this.getMessage("copyConfirmation")))
			{
				this.setup(fields);
			}
		},
		onDeleteButtonClick: function(e)
		{
			if(window.confirm(this.getMessage("deletionConfirmation")))
			{
				this.markAsDeleted();
				this._editor.removeItem(this);
			}
		}
	};

	if(typeof(BX.CrmMultipleAddressItemEditor.messages) === "undefined")
	{
		BX.CrmMultipleAddressItemEditor.messages =
		{
		};
	}

	BX.CrmMultipleAddressItemEditor.create = function(id, settings)
	{
		var self = new BX.CrmMultipleAddressItemEditor();
		self.initialize(id, settings);
		return self;
	};
}

BX.namespace("BX.Crm");

BX.Crm.EntityEditorBlockAreaClass = (function ()
{
	var EntityEditorBlockAreaClass = function (parameters)
	{
		this.editor = parameters.editor;
		this.container = parameters.container;
		this.nextNode = parameters.nextNode;
		this.entityInfoList = parameters.entityInfoList;
		this.readOnlyMode = parameters.readOnlyMode;
		this.closeBlockHandler = parameters.closeBlockHandler;
		this.changeSelectedRequisiteHandler = parameters.changeSelectedRequisiteHandler;
		this.changeLinkedRequisiteHandler = parameters.changeLinkedRequisiteHandler;
		this.rqLinkedId = parameters.rqLinkedId;
		this.bdLinkedId = parameters.bdLinkedId;

		this.wrapper = null;
		this.visualization = null;

		this.blockList = [];

		this.cleanState = {
			started: false,
			cleanAll: false,
			afterClean: null
		};

		this.initialize();
	};

	EntityEditorBlockAreaClass.prototype = {
		initialize: function()
		{
			if (this.container)
			{
				if (this.wrapper)
				{
					this.clean(false, BX.delegate(this.continueInitialize, this));
				}
			}

			this.continueInitialize();
		},
		continueInitialize: function()
		{
			if (this.container)
			{
				if (!this.wrapper)
					this.wrapper = BX.create("DIV", {"attrs": {"class": "crm-offer-tabs-wrapper"}});

				if (this.wrapper)
				{
					if (this.nextNode)
						this.container.insertBefore(this.wrapper, this.nextNode);
					else
						this.container.appendChild(this.wrapper);

					this.visualization = new BX.Crm.EntityEditorBlockAreaVisualizationClass({
						blockArea: this,
						tabActiveClass: "crm-offer-tab-active"
					});
				}
			}

			var n = this.entityInfoList.length;
			if (this.entityInfoList instanceof Array && n > 0)
			{
				for (var i = 0; i < n; i++)
				{
					if (this.entityInfoList[i] instanceof BX.CrmEntityInfo)
						this.addBlock(this.entityInfoList[i]);
				}
			}
		},
		getWrapperNode: function()
		{
			return this.wrapper;
		},
		addBlock: function(entityInfo)
		{
			var blockIndex = this.blockList.length;
			var block = new BX.Crm.EntityEditorBlockClass({
				editor: this.editor,
				blockArea: this,
				blockIndex: blockIndex,
				container: this.wrapper,
				nextNode: null,
				entityInfo: entityInfo,
				readOnlyMode: this.readOnlyMode,
				closeBlockHandler: this.closeBlockHandler,
				changeSelectedRequisiteHandler: this.changeSelectedRequisiteHandler,
				changeLinkedRequisiteHandler: BX.delegate(this.changeLinkedRequisite, this),
				rqLinkedId: this.rqLinkedId,
				bdLinkedId: this.bdLinkedId
			});

			if (block)
			{
				this.blockList[blockIndex] = block;

				if (this.visualization)
					this.visualization.addTabBlock(block);
			}
		},
		clean: function(cleanAll, afterClean)
		{
			if (!this.cleanState.started)
			{
				this.cleanState.started = true;
				this.cleanState.cleanAll = !!cleanAll;
				this.cleanState.afterClean = (typeof(afterClean) === "function") ? afterClean : null;

				if (this.blockList.length > 0)
					this.blockList[0].destroy();
				else
					this.continueClean();
			}
		},
		continueClean: function()
		{
			if (this.cleanState.started)
			{
				if (this.blockList.length > 0)
				{
					this.blockList[0].destroy();
				}
				else
				{
					if (this.wrapper)
					{
						if (this.visualization)
							this.visualization = null;
						BX.cleanNode(this.wrapper, this.cleanState.cleanAll);
					}

					var afterClean = this.cleanState.afterClean;

					this.cleanState = {
						cleanAll: false,
						started: false,
						afterClean: null
					};

					if (typeof(afterClean) === "function")
						afterClean();
				}
			}
		},
		onBlockDestroy: function(blockIndex)
		{
			if (blockIndex >= 0 && this.blockList && this.blockList.length > blockIndex)
			{
				if (this.visualization)
					this.visualization.removeTabsBlock(
						this.blockList[blockIndex],
						BX.delegate(this.continueBlockDestroy, this)
					);
				else
					this.continueBlockDestroy(blockIndex);
			}
		},
		changeLinkedRequisite: function(requisiteId, bankDetailId)
		{
			this.rqLinkedId = parseInt(requisiteId);
			if (this.rqLinkedId < 0 || isNaN(this.rqLinkedId))
				this.rqLinkedId = 0;
			this.bdLinkedId = parseInt(bankDetailId);
			if (this.bdLinkedId < 0 || isNaN(this.bdLinkedId))
				this.bdLinkedId = 0;

			if (typeof(this.changeLinkedRequisiteHandler) === "function")
				this.changeLinkedRequisiteHandler(this.rqLinkedId, this.bdLinkedId);
		},
		continueBlockDestroy: function(blockIndex)
		{
			if (blockIndex >= 0 && this.blockList && this.blockList.length > blockIndex)
			{
				var block = this.blockList[blockIndex];

				this.blockList.splice(blockIndex, 1);

				this.reindexBlocks(blockIndex);

				block.continueDestroy();

				if (this.cleanState.started)
					this.continueClean();
			}
		},
		reindexBlocks: function(indexFrom)
		{
			for (var i = indexFrom; i < this.blockList.length; i++)
				this.blockList[i].setIndex(i);
		},
		destroy: function(afterDestroy)
		{
			this.clean(true, afterDestroy);
		}
	};

	return EntityEditorBlockAreaClass;
})();

BX.Crm.EntityEditorBlockAreaVisualizationClass = (function()
{
	var EntityEditorBlockAreaVisualizationClass = function (parameters)
	{
		this.block = null;
		this.wrapper = null;
		this.activeClass = '';
		this.tabsObjList = [];

		this.init(parameters);
	};

	EntityEditorBlockAreaVisualizationClass.prototype = {
		init : function(params)
		{
			this.blockArea = params.blockArea;
			if (this.blockArea)
				this.wrapper = this.blockArea.getWrapperNode();
			this.activeClass = params.tabActiveClass;
		},
		createTabObj : function(tabNode)
		{
			var btnList,
				contList,
				requisiteItems,
				_this = this;

			var tabsObj =
			{
				mainblock : tabNode,
				innerBlock : tabNode.querySelector('[data-tab-block=tabBlockInner]'),
				contWrap : tabNode.querySelector('[data-tab-block=tabBlockWrap]'),
				/*closeBtn : tabNode.querySelector('[data-tab-block=closeBtn]'),*/
				btnList : [],
				contList : [],
				requisiteItems : []
			};

			btnList = tabNode.querySelectorAll('[data-tab-block=tab-btn]');

			/*BX.bind(tabsObj.closeBtn, 'click', function()
			{
				_this.removeTabsBlock(tabNode);
			});*/


			contList = tabNode.querySelectorAll('[data-tab-block=tab-cont]');

			for(var c=0; c < contList.length; c++)
			{
				tabsObj.contList[c] = contList[c];

				requisiteItems = contList[c].querySelectorAll('[data-tab-block=requisiteItem]');

				if(requisiteItems.length > 0)
					this.bindSwitchRequisiteItems(requisiteItems);
			}

			for(var b=0; b< btnList.length; b++)
			{
				tabsObj.btnList[b] = btnList[b];

				(function(blockNum,tabNum)
				{
					BX.bind(btnList[b], 'click',  function()
					{
						_this.switchTab(blockNum, tabNum);
					});
				})(tabsObj,b);
			}

			this.tabsObjList.push(tabsObj)
		},
		switchTab : function(tabsObj, tabNum)
		{
			tabsObj.contWrap.style.height = tabsObj.contWrap.clientHeight + 'px';

			for(var i=0; i<tabsObj.btnList.length; i++)
			{
				BX.removeClass(tabsObj.btnList[i], this.activeClass);
				tabsObj.contList[i].style.display = 'none';
			}

			tabsObj.contList[tabNum].style.display = 'block';
			BX.addClass(tabsObj.btnList[tabNum], this.activeClass);

			tabsObj.contWrap.style.height = tabsObj.contList[tabNum].offsetHeight + 'px';

		},
		bindSwitchRequisiteItems : function(itemList)
		{
			var inputs, index, nodes, i, j,
				_this = this;

			for(i=0; i<itemList.length; i++)
			{
				index = 0;
				inputs = [];
				inputs[index++] = itemList[i].querySelector('[class=crm-offer-requisite-inp]');
				nodes = itemList[i].querySelectorAll('[class=crm-offer-bankdetail-inp]');
				if (nodes)
				{
					for (j = 0; j < nodes.length; j++)
						inputs[index++] = nodes[j];
				}
				nodes = null;

				(function(item, itemList)
				{
					for (var i = 0; i < inputs.length; i++)
					{
						BX.bind(inputs[i], 'click', function(){
							_this.switchRequisiteItems(item, itemList)
						});
					}
				})(itemList[i], itemList)
			}
		},
		switchRequisiteItems : function(item, itemList)
		{
			var inp;
			
			for(var i=0; i<itemList.length; i++)
			{
				BX.removeClass(itemList[i], 'crm-offer-requisite-active');
			}

			BX.addClass(item, 'crm-offer-requisite-active');
			if (inp = item.querySelector('[class=crm-offer-requisite-inp]'))
			{
				inp.checked = true;
			}
		},
		removeTabsBlock : function(tabsBlock, continueCallback)
		{
			var blockWrapper = tabsBlock.getWrapperNode();
			if (blockWrapper)
			{
				blockWrapper.style.height = blockWrapper.offsetHeight + 'px';
				var thisBlockCord = blockWrapper.getBoundingClientRect().top,
					nextBlockCord = thisBlockCord,
					itemIndex;

				for(var i=0; i<this.tabsObjList.length; i++)
				{
					if(this.tabsObjList[i].mainblock == blockWrapper)
					{
						if(i<this.tabsObjList.length-1)
							nextBlockCord = this.tabsObjList[i+1].mainblock.getBoundingClientRect().top;

						itemIndex = this.tabsObjList.indexOf(this.tabsObjList[i]);

						this.tabsObjList.splice(itemIndex,1);
					}
				}

				setTimeout(function()
				{
					if(thisBlockCord == nextBlockCord)
						blockWrapper.style.width = 0;

					blockWrapper.style.height = 0;
					blockWrapper.style.opacity = 0;

				},100);

				setTimeout(function()
				{
					blockWrapper.style.display = 'none';
					continueCallback(tabsBlock.getIndex());
				},700)
			}
		},
		addTabBlock : function (tabBlock)
		{
			var blockWrapper = tabBlock.getWrapperNode();
			this.createTabObj(blockWrapper);
			this.showTabObj();
		},
		showTabObj : function()
		{
			var lastNum = this.tabsObjList.length-1;
			var marginBottom = parseInt(BX.style(this.tabsObjList[lastNum].innerBlock, 'marginBottom'));
			this.tabsObjList[lastNum].mainblock.style.height = this.tabsObjList[lastNum].innerBlock.offsetHeight + marginBottom +'px';

			BX.bind(this.tabsObjList[lastNum].mainblock, 'transitionend', function()
			{
				BX.removeClass(this, 'crm-offer-tab-block-hidden');
				this.style.height = 'auto';
			});
		}
	};

	return EntityEditorBlockAreaVisualizationClass;
})();

BX.Crm.EntityEditorBlockClass = (function ()
{
	var EntityEditorBlockClass = function (parameters)
	{
		this.editor = parameters.editor;
		this.blockArea = parameters.blockArea;
		this.blockIndex = parameters.blockIndex;
		this.container = parameters.container;
		this.nextNode = parameters.nextNode;
		this.entityInfo = parameters.entityInfo;
		this.readOnlyMode = parameters.readOnlyMode;
		this.closeBlockHandler = parameters.closeBlockHandler;
		this.changeSelectedRequisiteHandler = parameters.changeSelectedRequisiteHandler;
		this.changeLinkedRequisiteHandler = parameters.changeLinkedRequisiteHandler;

		this.rqLinkedId = parseInt(parameters.rqLinkedId);
		if (this.rqLinkedId < 0 || isNaN(this.rqLinkedId))
			this.rqLinkedId = 0;

		this.bdLinkedId = parseInt(parameters.bdLinkedId);
		if (this.bdLinkedId < 0 || isNaN(this.bdLinkedId))
			this.bdLinkedId = 0;

		this.ajaxUrl = "/bitrix/components/bitrix/crm.requisite.edit/settings.php";

		this.closeButtonClickHandler = null;
		this.wrapper = null;
		this.wrapperInner = null;
		this.titleNode = null;
		this.closeButtonNode = null;
		this.requisiteIndex = [];
		
		this.random = Math.random().toString().substring(2);

		this.initialize();
	};

	EntityEditorBlockClass.prototype = {
		initialize: function()
		{
			if (this.container)
			{
				this.clean();

				if (!this.wrapper)
				{
					var typeClass = "";
					if (this.entityInfo instanceof BX.CrmEntityInfo)
					{
						var type = this.entityInfo.getSetting("type", "");
						if (typeof(type) === "string" && type.length > 0)
							typeClass = " crm-offer-tab-" + type;
					}
					this.wrapper = BX.create("DIV", {
						"attrs": {
							"class": "crm-offer-tab-block-wrap" + typeClass + " crm-offer-tab-block-hidden",
							"data-tab-block": "tabBlock"
						}
					});
					if (this.wrapper)
					{
						if (this.nextNode)
							this.container.insertBefore(this.wrapper, this.nextNode);
						else
							this.container.appendChild(this.wrapper);
					}
				}
			}

			if (this.wrapper)
			{
				this.wrapperInner = BX.create(
					"DIV", {"attrs": {"class": "crm-offer-tab-block", "data-tab-block": "tabBlockInner"}}
				);
				if (this.wrapperInner)
				{
					this.wrapper.appendChild(this.wrapperInner);

					// title
					var title, url, description,
						titleLinkNode = null;
					if (this.entityInfo instanceof BX.CrmEntityInfo)
					{
						title = this.entityInfo.getSetting("title", "");
						if (title.length > 0)
						{
							url = this.entityInfo.getSetting("url", "");
							if (BX.type.isNotEmptyString(url))
							{
								titleLinkNode = BX.create(
									"A",
									{
										"attrs": {
											"class": "crm-offer-tab-title-name",
											"href": url,
											"target": "_blank"
										},
										"html": BX.util.htmlspecialchars(title)
									}
								);
							}
							else
							{
								titleLinkNode = BX.create(
									"SPAN",
									{
										"attrs": {"class": "crm-offer-tab-title-name"},
										"html": BX.util.htmlspecialchars(title)
									}
								);
							}
							description = this.entityInfo.getSetting("desc", "");
						}
						if (titleLinkNode)
						{
							this.titleNode = BX.create(
								"DIV",
								{
									"attrs": {"class": "crm-offer-tab-title"},
									"children": [
										BX.create("DIV", {"attrs": {"class": "crm-offer-tab-title-icon"}}),
										BX.create(
											"DIV",
											{
												"attrs": {"class": "crm-offer-tab-title-name-block"},
												"children": [
													titleLinkNode,
													BX.create(
														"DIV",
														{
															"attrs": {"class": "crm-offer-tab-title-descript"},
															"html": BX.util.htmlspecialchars(description)
														}
													)
												]
											}
										)
									]
								}
							);
						}
					}

					if (this.titleNode)
					{
						if (!this.readOnlyMode)
						{
							this.closeButtonNode = BX.create(
								"DIV",
								{
									"attrs": {
										"class": "crm-offer-tab-close-btn",
										"data-tab-block": "closeBtn"
									}
								}
							);
							if (this.closeButtonNode)
							{
								this.titleNode.appendChild(this.closeButtonNode);
								this.closeButtonClickHandler = BX.delegate(this.onCloseButtonClick, this);
								BX.bind(this.closeButtonNode, "click", this.closeButtonClickHandler);
							}
						}
						this.wrapperInner.appendChild(this.titleNode);
					}

					if (this.titleNode)
					{
						var i, j, advancedInfo, entityId, entityTypeName, contactTypeName = "",
							phoneItems = [], emailItems = [], requisiteItems = [];

						advancedInfo = this.entityInfo.getSetting("advancedInfo", null);
						entityId = this.entityInfo.getSetting("id", "");
						entityTypeName = this.entityInfo.getSetting("type", "");
						if (BX.type.isPlainObject(advancedInfo))
						{
							if (entityTypeName === "contact")
							{
								if (BX.type.isPlainObject(advancedInfo["contactType"])
									&& typeof(advancedInfo["contactType"]["name"]) === "string")
								{
									contactTypeName = advancedInfo["contactType"]["name"];
								}
							}
							if (advancedInfo["multiFields"] && advancedInfo["multiFields"] instanceof Array)
							{
								var mf = advancedInfo["multiFields"];
								for (i = 0; i < mf.length; i++)
								{
									if (mf[i]["TYPE_ID"] && mf[i]["TYPE_ID"] === "PHONE")
									{
										phoneItems.push({"VALUE": BX.util.trim(mf[i]["VALUE"])});
									}
									if (mf[i]["TYPE_ID"] && mf[i]["TYPE_ID"] === "EMAIL")
									{
										emailItems.push({"VALUE": BX.util.trim(mf[i]["VALUE"])});
									}
								}
							}
							if (advancedInfo["requisiteData"] instanceof Array)
							{
								var requisiteData, requisiteId, newRequisiteIdLinked, entityTypeId,
									data, viewData, bankDetailViewDataList,
									selected, bankDetailLinkedIndex, newBankDetailIdLinked,
									bankDetailIdSelected, bankDetailSelectedIndex,
									bankDetailSelectedIndexPrior, bankDetailId,
									index = 0, selectedIndex = -1, linkedIndex = -1;

								requisiteData = advancedInfo["requisiteData"];
								newRequisiteIdLinked = this.rqLinkedId;
								for (i = 0; i < requisiteData.length; i++)
								{
									if (typeof(requisiteData[i]["requisiteId"]) !== "undefined")
									{
										requisiteId = parseInt(requisiteData[i]["requisiteId"]);
									}
									else
									{
										requisiteId = 0;
									}

									if (typeof(requisiteData[i]["entityTypeId"]) !== "undefined")
									{
										entityTypeId = parseInt(requisiteData[i]["entityTypeId"]);
									}
									else
									{
										entityTypeId = 0;
									}

									if (typeof(requisiteData[i]["entityId"]) !== "undefined")
									{
										entityId = parseInt(requisiteData[i]["entityId"]);
									}
									else
									{
										entityId = 0;
									}

									if (typeof(requisiteData[i]["requisiteData"]) === "string")
									{
										data = BX.parseJSON(requisiteData[i]["requisiteData"], this);
										if (!BX.type.isPlainObject(data))
											data = {};
									}
									else
									{
										data = {};
									}

									if (BX.type.isPlainObject(data["viewData"]))
										viewData = data["viewData"];
									else
										viewData = {};

									if (typeof(viewData["title"]) === "string" && viewData["title"].length > 0)
									{
										if (BX.type.isArray(data["bankDetailViewDataList"]))
											bankDetailViewDataList = data["bankDetailViewDataList"];
										else
											bankDetailViewDataList = [];

										if (typeof(requisiteData[i]["bankDetailIdSelected"]) !== "undefined")
										{
											bankDetailIdSelected = parseInt(requisiteData[i]["bankDetailIdSelected"]);
											if (bankDetailIdSelected < 0 || isNaN(bankDetailIdSelected))
												bankDetailIdSelected = 0;
										}
										else
										{
											requisiteData[i]["bankDetailIdSelected"] = bankDetailIdSelected = 0;
										}

										if (bankDetailViewDataList.length > 0)
										{
											bankDetailLinkedIndex = -1;
											bankDetailSelectedIndex = -1;
											bankDetailSelectedIndexPrior = -1;
											for (j = 0; j < bankDetailViewDataList.length; j++)
											{
												bankDetailId = parseInt(bankDetailViewDataList[j]["pseudoId"]);
												if (bankDetailId < 0 || isNaN(bankDetailId))
													bankDetailId = 0;
												if (requisiteId === this.rqLinkedId &&
													this.bdLinkedId > 0 && bankDetailId === this.bdLinkedId)
												{
													bankDetailLinkedIndex = j;
												}
												if (bankDetailIdSelected > 0 && bankDetailId === bankDetailIdSelected)
													bankDetailSelectedIndexPrior = j;

												if (bankDetailViewDataList[j]["selected"])
													bankDetailSelectedIndex = j;
											}
											if (bankDetailLinkedIndex >= 0)
											{
												if (bankDetailSelectedIndexPrior !== bankDetailSelectedIndex)
												{
													if (bankDetailSelectedIndex >= 0)
														bankDetailViewDataList[bankDetailSelectedIndex]["selected"] = false;
													bankDetailViewDataList[bankDetailLinkedIndex]["selected"] = true;
													requisiteData[i]["bankDetailIdSelected"] = parseInt(
														bankDetailViewDataList[bankDetailLinkedIndex]["pseudoId"]
													);
												}
											}
											else if (bankDetailSelectedIndexPrior >= 0)
											{
												if (bankDetailSelectedIndexPrior !== bankDetailSelectedIndex)
												{
													if (bankDetailSelectedIndex >= 0)
														bankDetailViewDataList[bankDetailSelectedIndex]["selected"] = false;
													bankDetailViewDataList[bankDetailSelectedIndexPrior]["selected"] = true;
													requisiteData[i]["bankDetailIdSelected"] = parseInt(
														bankDetailViewDataList[bankDetailSelectedIndexPrior]["pseudoId"]
													);
												}
											}
											else
											{
												if (bankDetailSelectedIndex < 0)
													bankDetailSelectedIndex = 0;
												requisiteData[i]["bankDetailIdSelected"] = parseInt(
													bankDetailViewDataList[bankDetailSelectedIndex]["pseudoId"]
												);
											}
										}

										requisiteItems.push({
											"requisiteId": requisiteId,
											"entityTypeId": entityTypeId,
											"entityId": entityId,
											"viewData": viewData,
											"bankDetailViewDataList": bankDetailViewDataList,
											"bankDetailIdSelected": requisiteData[i]["bankDetailIdSelected"],
											"selected": requisiteData[i]["selected"]
										});

										this.requisiteIndex[requisiteId] = {
											"entityTypeId": entityTypeId,
											"entityId": entityId,
											"bankDetailIdSelected": requisiteData[i]["bankDetailIdSelected"]
										};

										if (requisiteData[i]["selected"])
											selectedIndex = index;
										if (this.rqLinkedId > 0 && this.rqLinkedId == requisiteId)
											linkedIndex = index;

										index++;
									}
								}

								if (linkedIndex >= 0)
								{
									if (selectedIndex >= 0 && selectedIndex !== linkedIndex)
									{
										requisiteItems[selectedIndex]["selected"] = false;
										requisiteItems[linkedIndex]["selected"] = true;
										selectedIndex = linkedIndex;
									}
								}

								newRequisiteIdLinked = 0;
								newBankDetailIdLinked = 0;
								if (selectedIndex >= 0)
								{
									newRequisiteIdLinked = parseInt(requisiteItems[selectedIndex]["requisiteId"]);
									if (newRequisiteIdLinked < 0 || isNaN(newRequisiteIdLinked))
										newRequisiteIdLinked = 0;

									newBankDetailIdLinked = parseInt(requisiteItems[selectedIndex]["bankDetailIdSelected"]);
									if (newBankDetailIdLinked < 0 || isNaN(newBankDetailIdLinked))
										newBankDetailIdLinked = 0;
								}

								if (this.rqLinkedId !== newRequisiteIdLinked || this.bdLinkedId !== newBankDetailIdLinked)
								{
									if (typeof(this.changeLinkedRequisiteHandler) === "function")
									{
										this.rqLinkedId = newRequisiteIdLinked;
										this.bdLinkedId = newBankDetailIdLinked;
										this.changeLinkedRequisiteHandler(this.rqLinkedId, this.bdLinkedId);
									}
								}
							}
						}

						var infoDataExists = false, requisiteDataExists = false, contentDataExists = false;
						if ((entityTypeName === "contact" && contactTypeName.length > 0)
							|| phoneItems.length > 0 || emailItems.length > 0)
						{
							infoDataExists = true;
						}
						if (requisiteItems.length > 0)
						{
							requisiteDataExists = true;
						}
						contentDataExists = infoDataExists || requisiteDataExists;

						if (contentDataExists)
						{
							var tabInfo = 1, tabRequisite = 2, activeTab = (infoDataExists) ? tabInfo : tabRequisite;

							var tabInfoTitle = this.getMessage(entityTypeName + "TabTitleAbout"),
								tabRequisiteTitle;

							if (entityTypeName === "company")
							{
								tabRequisiteTitle = this.getMessage("tabTitleCompanyRequisites");
							}
							else    // contact
							{
								tabRequisiteTitle = this.getMessage("tabTitleContactRequisites");
							}

							if (!BX.type.isNotEmptyString(tabInfoTitle))
								tabInfoTitle = 	this.getMessage(entityTypeName + "tabTitleAbout");

							var tabInfoAttrs = {
								"class": "crm-offer-tab" + ((tabInfo === activeTab) ? " crm-offer-tab-active" : ""),
								"data-tab-block": "tab-btn"
							};
							var tabRequisiteAttrs = {
								"class": "crm-offer-tab" + ((tabRequisite === activeTab) ? " crm-offer-tab-active" : ""),
								"data-tab-block": "tab-btn"
							};
							if (!infoDataExists)
								tabInfoAttrs["style"] = "display: none;";
							if (!requisiteDataExists)
								tabRequisiteAttrs["style"] = "display: none;";

							var mainContBlock = null;
							this.wrapperInner.appendChild(
								mainContBlock = BX.create(
									"DIV", {
										"attrs": {"class": "crm-offer-tab-main-cont-wrap"},
										"children": [
											BX.create(
												"DIV", {
													"attrs": {"class": "crm-offer-tab-list-wrap"},
													"children": [
														BX.create(
															"SPAN", {
																"attrs": tabInfoAttrs,
																"html": "<span class=\"crm-offer-tab-text\">" +
																BX.util.htmlspecialchars(tabInfoTitle) + "</span>"
															}
														),
														BX.create(
															"SPAN", {
																"attrs": tabRequisiteAttrs,
																"html": "<span class=\"crm-offer-tab-text\">" +
																BX.util.htmlspecialchars(tabRequisiteTitle) + "</span>"
															}
														)
													]
												}
											)
										]
									}
								)
							);

							if (mainContBlock)
							{
								var infoContBlockAttrs = {
										"class": "crm-offer-tab-cont",
										"data-tab-block": "tab-cont"
									},
									requisiteContBlockAttrs = {
										"class": "crm-offer-tab-cont",
										"data-tab-block": "tab-cont"
									};

								if (tabInfo !== activeTab)
									infoContBlockAttrs["style"] = "display: none;";
								if (tabRequisite !== activeTab)
									requisiteContBlockAttrs["style"] = "display: none;";

								var infoWrapper = null, requisiteWrapper = null;

								mainContBlock.appendChild(
									BX.create(
										"DIV", {
											"attrs": {
												"class": "crm-offer-tab-cont-wrap",
												"data-tab-block": "tabBlockWrap"
											},
											"children": [
												BX.create(
													"DIV", {
														"attrs": infoContBlockAttrs,
														"children": [
															infoWrapper = BX.create(
																"DIV", {
																	"attrs": {"class": "crm-offer-tab-info"},
																	"children": [
																		BX.create(
																			"DIV", {
																				"attrs": {"class": "crm-offer-tab-info-img"}
																			}
																		)
																	]
																}
															)
														]
													}
												),
												BX.create(
													"DIV", {
														"attrs": requisiteContBlockAttrs,
														"children": [
															requisiteWrapper = BX.create("FORM")
														]
													}
												)
											]
										}
									)
								);

								var tableNode, row, cell, bankDetailsNode;

								if (infoWrapper && infoDataExists)
								{
									tableNode = BX.create("TABLE", {"attrs": {"class": "crm-offer-tab-table"}});
									if (tableNode)
									{
										if (entityTypeName === "contact" && contactTypeName.length > 0)
										{
											var contactTypeTitle = this.getMessage("prefContactType");
											row = tableNode.insertRow(-1);
											cell = row.insertCell(-1);
											cell.className = "crm-offer-tab-cell";
											cell.innerHTML = BX.util.htmlspecialchars(contactTypeTitle + ":");
											cell = row.insertCell(-1);
											cell.className = "crm-offer-tab-cell";
											cell.innerHTML = BX.util.htmlspecialchars(contactTypeName);
										}

										if (phoneItems.length > 0)
										{
											var phoneTitle, phoneHtml;

											phoneTitle = this.getMessage("prefPhoneLong");

											var phoneContent = "", phoneNumber = "", onclickContent = "";
											for (i = 0; i < phoneItems.length; i++)
											{
												if (BX.type.isNotEmptyString(phoneItems[i]["VALUE"]))
												{
													phoneNumber = phoneItems[i]["VALUE"];

													/*onclickContent = " onclick=\"BX.CrmSipManager.startCall(" +
														"{'number': '" + BX.util.jsencode(phoneNumber) +
														"', 'enableInfoLoading': true}, {'ENTITY_TYPE': " +
														"BX.CrmSipManager.resolveSipEntityTypeName('" +
														BX.util.jsencode(entityTypeName) + "'), 'ENTITY_ID': '" +
														BX.util.jsencode(entityId) + "'}, true, this);\"";*/

													phoneContent += "<div class=\"crm-offer-tab-hr\">";
													phoneContent += "<span style=\"max-width: 330px;\" class=\"crm-detail-info-item-text crm-detail-info-item-handset\">";
													phoneContent += "<a class=\"crm-item-tel-num\" href=\"callto://" + BX.util.urlencode(phoneNumber) + "\">" + BX.util.htmlspecialchars(phoneNumber) + "</a>";
													/*phoneContent += "<span class=\"crm-tel-btn\"" + onclickContent + "></span>";*/
													phoneContent += "</span>";
													phoneContent += "</div>";
												}
											}

											row = tableNode.insertRow(-1);
											cell = row.insertCell(-1);
											cell.className = "crm-offer-tab-cell";
											cell.innerHTML = BX.util.htmlspecialchars(phoneTitle + ":");
											cell = row.insertCell(-1);
											cell.className = "crm-offer-tab-cell";
											cell.innerHTML = phoneContent;
										}

										if (emailItems.length > 0)
										{
											var emailTitle, emailContent = "", emailAddr = "";

											emailTitle = this.getMessage("prefEmail");

											for (i = 0; i < emailItems.length; i++)
											{
												if (BX.type.isNotEmptyString(emailItems[i]["VALUE"]))
												{
													emailAddr = emailItems[i]["VALUE"];

													emailContent += "<div class=\"crm-offer-tab-hr\">";
													emailContent += "<a class=\"crm-offer-tab-link\" href=\"mailto://" + BX.util.urlencode(emailAddr) + "\">" + BX.util.htmlspecialchars(emailAddr) + "</a>";
													/*emailContent += "<span class=\"crm-offer-mail-icon\"></span>";*/
													emailContent += "</div>";
												}
											}

											row = tableNode.insertRow(-1);
											cell = row.insertCell(-1);
											cell.className = "crm-offer-tab-cell";
											cell.innerHTML = BX.util.htmlspecialchars(emailTitle + ":");
											cell = row.insertCell(-1);
											cell.className = "crm-offer-tab-cell";
											cell.innerHTML = emailContent;
										}
									}

									infoWrapper.appendChild(tableNode);
								}

								if (requisiteWrapper && requisiteDataExists)
								{
									var requisiteBlockNode,
										requisiteRadioIdPrefix,
										requisiteRadioId;
									
									if (this.editor)
										requisiteRadioIdPrefix = this.editor.getSetting("containerId", null);
									if (!BX.type.isNotEmptyString(requisiteRadioIdPrefix))
										requisiteRadioIdPrefix = this.random;
									requisiteRadioIdPrefix += "_REQUISITE";

									for (i = 0; i < requisiteItems.length; i++)
									{
										if (BX.type.isPlainObject(requisiteItems[i]["viewData"])
											&& typeof(requisiteItems[i]["requisiteId"]) !== "undefined"
											&& typeof(requisiteItems[i]["selected"]) !== "undefined"
											&& BX.type.isNotEmptyString(requisiteItems[i]["viewData"]["title"])
											&& requisiteItems[i]["viewData"]["fields"] instanceof Array)
										{
											requisiteRadioId = requisiteRadioIdPrefix + "_" + i;
											requisiteBlockNode = BX.create("DIV", {
												"attrs": {
													"class": "crm-offer-requisite" +
													((requisiteItems[i]["selected"] === true) ?
														" crm-offer-requisite-active" : ""),
													"data-tab-block": "requisiteItem"
												},
												"children": [
													BX.create("DIV", {
														"attrs": {"class": "crm-offer-requisite-title"},
														"children": [
															BX.create("INPUT", {
																"attrs": {
																	"id": requisiteRadioId,
																	"class": "crm-offer-requisite-inp",
																	"type": "radio",
																	"name": requisiteRadioIdPrefix,
																	"value": requisiteItems[i]["requisiteId"]
																},
																"props": {
																	"checked": (requisiteItems[i]["selected"] === true)
																},
																"events": {
																	"click": BX.delegate(this.onRequisiteRadioClick, this)
																}
															}),
															BX.create("LABEL", {
																"attrs": {
																	"class": "crm-offer-requisite-lable",
																	"for": requisiteRadioId
																},
																"html": BX.util.htmlspecialchars(
																	requisiteItems[i]["viewData"]["title"]
																)
															})
														]
													})
												]
											});
											if (requisiteBlockNode)
											{
												var requisiteFields = requisiteItems[i]["viewData"]["fields"];
												
												if (requisiteFields.length > 0 ||
													requisiteItems[i]["bankDetailViewDataList"].length > 0)
												{
													tableNode = BX.create("TABLE", {
														"attrs": {"class": "crm-offer-tab-table"}
													});
													if (tableNode)
													{
														for (j = 0; j < requisiteFields.length; j++)
														{
															row = tableNode.insertRow(-1);
															cell = row.insertCell(-1);
															cell.className = "crm-offer-tab-cell";
															cell.innerHTML =
																((requisiteFields[j]["title"]) ?
																	BX.util.htmlspecialchars(
																		requisiteFields[j]["title"]
																	) : "") + ":";
															cell = row.insertCell(-1);
															cell.className = "crm-offer-tab-cell";
															cell.innerHTML =
																(requisiteFields[j]["textValue"]) ?
																	BX.util.nl2br(
																		BX.util.htmlspecialchars(
																			requisiteFields[j]["textValue"]
																		)
																	) : "";
														}
														bankDetailsNode =
															this.makeBankDetailsNode(requisiteItems[i]);
														if (bankDetailsNode)
														{
															row = tableNode.insertRow(-1);
															cell = row.insertCell(-1);
															cell.className = "crm-offer-tab-cell";
															cell.innerHTML = BX.util.htmlspecialchars(
																this.getMessage("bankDetailsTitle") + ":"
															);
															cell = row.insertCell(-1);
															cell.className = "crm-offer-tab-cell";
															cell.appendChild(bankDetailsNode);
														}
														requisiteBlockNode.appendChild(tableNode);
													}
												}
												requisiteWrapper.appendChild(requisiteBlockNode);
											}
										}
									}
								}
							}
						}
					}
				}
			}
		},
		makeBankDetailsNode: function(requisiteItem)
		{
			var i, cnt, viewDataList, wrapper, requisiteId, pseudoId, radioIdPrefix, radioId;

			if (!BX.type.isPlainObject(requisiteItem))
			{
				return null;
			}

			requisiteId = parseInt(requisiteItem["requisiteId"]);
			if (!(requisiteId > 0
				&& BX.type.isArray(requisiteItem["bankDetailViewDataList"])
				&& requisiteItem["bankDetailViewDataList"].length > 0))
			{
				return null;
			}

			viewDataList = requisiteItem["bankDetailViewDataList"];

			wrapper = BX.create("DIV");
			if (!wrapper)
			{
				return null;
			}

			cnt = 0;
			for (i = 0; i < viewDataList.length; i++)
			{
				if (!(typeof(viewDataList[i]["pseudoId"]) !== "undefined"
					&& BX.type.isPlainObject(viewDataList[i]["viewData"])
					&& BX.type.isNotEmptyString(viewDataList[i]["viewData"]["title"])
					&& BX.type.isArray(viewDataList[i]["viewData"]["fields"])))
				{
					continue;
				}

				pseudoId = viewDataList[i]["pseudoId"];
				if (this.editor)
					radioIdPrefix = this.editor.getSetting("containerId", null);
				if (!BX.type.isNotEmptyString(radioIdPrefix))
					radioIdPrefix = this.random;
				radioIdPrefix += "_RQ_"+requisiteId+"_BD";
				radioId = radioIdPrefix + "_" + pseudoId;

				wrapper.appendChild(
					BX.create("DIV", {
						/*"attrs": {"class": "crm-offer-requisite-title"},*/
						"children": [
							BX.create("INPUT", {
								"attrs": {
									"id": radioId,
									"class": "crm-offer-bankdetail-inp",
									"type": "radio",
									"name": radioIdPrefix,
									"value": "" + requisiteId + "|" + pseudoId
								},
								"props": {
									"checked": viewDataList[i]["selected"] === true
								},
								"events": {
									"click": BX.delegate(this.onBankDetailRadioClick, this)
								}
							}),
							BX.create("LABEL", {
								"attrs": {
									/*"class": "crm-offer-requisite-lable",*/
									"for": radioId
								},
								"html": BX.util.htmlspecialchars(
									viewDataList[i]["viewData"]["title"]
								)
							})
						]
					})
				);
				cnt++;
			}
			if (cnt <= 0)
			{
				BX.cleanNode(wrapper, true);
				wrapper = null;
			}

			return wrapper;
		},
		getMessage: function(name)
		{
			if (this.editor)
				return this.editor.getMessage(name);

			return "";
		},
		getWrapperNode: function()
		{
			return this.wrapper;
		},
		onCloseButtonClick: function()
		{
			if (this.readOnlyMode)
				return;

			if (typeof(this.closeBlockHandler) === "function")
				this.closeBlockHandler();

			this.destroy();
		},
		onRequisiteRadioClick: function(e)
		{
			var requisiteId, entityTypeId, entityId, bankDetailId;

			if(!e)
				e = window.event;

			if (e && BX.type.isDomNode(e.target))
			{
				requisiteId = parseInt(e.target.value);
				if (requisiteId > 0)
				{
					if (this.requisiteIndex[requisiteId])
					{
						entityTypeId = parseInt(this.requisiteIndex[requisiteId].entityTypeId);
						entityId = parseInt(this.requisiteIndex[requisiteId].entityId);
						bankDetailId = parseInt(this.requisiteIndex[requisiteId].bankDetailIdSelected);
						if (bankDetailId < 0 || isNaN(bankDetailId))
							bankDetailId = 0;

						if (entityTypeId > 0 && entityId > 0)
						{
							if (typeof(this.changeSelectedRequisiteHandler) === "function")
								this.changeSelectedRequisiteHandler(entityTypeId, entityId, requisiteId, bankDetailId);

							if (typeof(this.changeLinkedRequisiteHandler) === "function")
								this.changeLinkedRequisiteHandler(requisiteId, bankDetailId);

							var data = {
								"action": "savelastselectedrequisite",
								"requisiteEntityTypeId": entityTypeId,
								"requisiteEntityId": entityId,
								"requisiteId": requisiteId,
								"bankDetailId": bankDetailId
							};
							BX.ajax.post(this.ajaxUrl, data);
						}
					}
				}
			}
		},
		onBankDetailRadioClick: function(e)
		{
			var requisiteId, entityTypeId, entityId, bankDetailId, mr;

			if(!e)
				e = window.event;

			if (e && BX.type.isDomNode(e.target) && BX.type.isNotEmptyString(e.target.value))
			{
				mr = e.target.value.match(/^(\d+)\|(\d+)$/);
				if (mr)
				{
					requisiteId = parseInt(mr[1]);
					bankDetailId = parseInt(mr[2]);
					if (requisiteId > 0)
					{
						if (this.requisiteIndex[requisiteId])
						{
							entityTypeId = parseInt(this.requisiteIndex[requisiteId].entityTypeId);
							entityId = parseInt(this.requisiteIndex[requisiteId].entityId);

							if (entityTypeId > 0 && entityId > 0)
							{
								if (typeof(this.changeSelectedRequisiteHandler) === "function")
									this.changeSelectedRequisiteHandler(entityTypeId, entityId, requisiteId, bankDetailId);

								if (typeof(this.changeLinkedRequisiteHandler) === "function")
									this.changeLinkedRequisiteHandler(requisiteId, bankDetailId);

								var data = {
									"action": "savelastselectedrequisite",
									"requisiteEntityTypeId": entityTypeId,
									"requisiteEntityId": entityId,
									"requisiteId": requisiteId,
									"bankDetailId": bankDetailId
								};
								BX.ajax.post(this.ajaxUrl, data);
							}
						}
					}
				}
			}
		},
		getIndex: function()
		{
			return this.blockIndex;
		},
		setIndex: function(index)
		{
			this.blockIndex = index;
		},
		clean: function(cleanAll)
		{
			cleanAll = !!cleanAll;
			if (this.wrapper)
			{
				if (this.closeButtonNode)
				{
					if (this.closeButtonClickHandler)
					{
						BX.unbind(this.closeButtonNode, "click", this.closeButtonClickHandler);
						this.closeButtonClickHandler = null;
						this.closeButtonNode = null;
						this.requisiteIndex = [];
					}
				}

				BX.cleanNode(this.wrapper, cleanAll);
			}
		},
		destroy: function()
		{
			if (this.blockArea)
				this.blockArea.onBlockDestroy(this.blockIndex);
			else
				this.continueDestroy();
		},
		continueDestroy: function()
		{
			this.clean(true);
		}
	};

	return EntityEditorBlockClass;
})();

if(typeof(BX.Crm.RequisiteBankDetailsArea) === "undefined")
{
	BX.Crm.RequisiteBankDetailsArea = function()
	{
		this._id = null;
		this.container = null;
		this.nextNode = null;
		this.messages = {};
		this.fieldList = [];
		this.dataList = [];
		this.fieldNameTemplate = '';
		this.wrapper = null;
		this.blocksContainer = null;

		this.blockList = [];

		this.elementIndex = 0;

		this.cleanState = {
			started: false,
			cleanAll: false,
			afterClean: null
		};

		this.addBlockBtn = null;
		this.addBlockBtnClickHandler = null;

		this.mode = "EDIT";
	};
	BX.Crm.RequisiteBankDetailsArea.prototype =
	{
		initialize: function(id, parameters)
		{
			var i;

			this._id = BX.type.isNotEmptyString(id) ?
				id : "crm_rq_bank_details_area_" + Math.random().toString().substring(2);
			this.container = parameters.container;
			this.nextNode = parameters.nextNode;
			this.messages = parameters.messages || {};
			this.fieldList = BX.type.isArray(parameters.fieldList) ? parameters.fieldList : [];
			this.dataList = BX.type.isArray(parameters.dataList) ? parameters.dataList : [];
			this.fieldNameTemplate = BX.type.isNotEmptyString(parameters.fieldNameTemplate) ?
				parameters.fieldNameTemplate : "BANK_DETAILS[#ELEMENT_ID#][#FIELD_NAME#]";
			this.mode = parameters.mode || "EDIT";

			if (this.container)
			{
				this.clean();

				if (!this.wrapper)
				{
					this.wrapper = BX.create("DIV", {"attrs": {"class": "crm-offer-requisite-block-wrap"}});
					if (this.wrapper)
					{
						if (this.nextNode)
							this.container.insertBefore(this.wrapper, this.nextNode);
						else
							this.container.appendChild(this.wrapper);
					}
					if (this.wrapper)
					{
						if (this.nextNode)
							this.container.insertBefore(this.wrapper, this.nextNode);
						else
							this.container.appendChild(this.wrapper);
					}
				}
			}

			this.elementIndex = 0;

			if (this.wrapper)
			{
				this.wrapper.appendChild(BX.create("SPAN", {
					"attrs": {"class": "crm-offer-requisite-option"},
					children: [
						this.addBlockBtn = BX.create("SPAN", {
							"attrs": {
								"class": "crm-offer-requisite-option-text"
							},
							"text": this.getMessage("addBlockBtnText")
						})
					]
				}));

				this.wrapper.appendChild(BX.create("DIV", {
					"attrs": {"class": "crm-offer-requisite-form-wrap"},
					"children": [
						this.blocksContainer = BX.create("DIV", {
							"attrs": {"class": "crm-multi-address"}
						})
					]
				}));

				if (this.addBlockBtn)
				{
					this.addBlockBtnClickHandler = BX.delegate(this.onAddBlockButtonClick, this);
					BX.bind(this.addBlockBtn, "click", this.addBlockBtnClickHandler);
				}

				if (BX.type.isArray(this.dataList))
				{
					for (i = 0; i < this.dataList.length; i++)
					{
						if (BX.type.isPlainObject(this.dataList[i]))
						{
							id = (this.dataList[i].hasOwnProperty("ID")) ? parseInt(this.dataList[i]["ID"]) : 0;
							this.addBlock(id, this.dataList[i]);
						}
					}
				}
			}
		},
		getId: function()
		{
			return this._id;
		},
		getWrapperNode: function()
		{
			return this.wrapper;
		},
		getMessage: function(msgId)
		{
			return this.messages[msgId];
		},
		onAddBlockButtonClick: function()
		{
			this.addBlock();
		},
		addBlock: function(bankDetailId, bankDetailData)
		{

			bankDetailId = parseInt(bankDetailId);
			if (bankDetailId < 0)
				bankDetailId = 0;

			if (!bankDetailData || !BX.type.isPlainObject(bankDetailData))
				bankDetailData = {};

			var blockIndex = this.blockList.length;
			var nextNode = null;
			if (blockIndex > 0)
				nextNode = this.blockList[blockIndex-1].getWrapperNode();
			var block = new BX.Crm.RequisiteBankDetailsBlock({
				mode: this.mode,
				bankDetailId: bankDetailId,
				bankDetailData: bankDetailData,
				blockArea: this,
				blockIndex: blockIndex,
				elementIndex: this.elementIndex++,
				container: this.blocksContainer,
				nextNode: nextNode,
				fieldList: this.fieldList,
				fieldNameTemplate: this.fieldNameTemplate
			});

			if (block)
				this.blockList[blockIndex] = block;
		},
		onBlockDestroy: function(blockIndex)
		{
			if (blockIndex >= 0 && this.blockList && this.blockList.length > blockIndex)
			{
				this.blockList.splice(blockIndex, 1);
				this.reindexBlocks(blockIndex);
			}

			if (this.cleanState.started)
				this.continueClean();
		},
		reindexBlocks: function(indexFrom)
		{
			for (var i = indexFrom; i < this.blockList.length; i++)
				this.blockList[i].setIndex(i);
		},
		clean: function(cleanAll, afterClean)
		{
			if (!this.cleanState.started)
			{
				this.cleanState.started = true;
				this.cleanState.cleanAll = !!cleanAll;
				this.cleanState.afterClean = (typeof(afterClean) === "function") ? afterClean : null;

				if (this.blockList.length > 0)
					this.blockList[0].destroy();
				else
					this.continueClean();
			}
		},
		continueClean: function()
		{
			if (this.cleanState.started)
			{
				if (this.blockList.length > 0)
				{
					this.blockList[0].destroy();
				}
				else
				{
					if (this.addBlockBtn)
					{
						if (this.addBlockBtnClickHandler)
						{
							BX.unbind(this.addBlockBtn, "click", this.addBlockBtnClickHandler);
							this.addBlockBtnClickHandler = null;
						}
						this.addBlockBtn = null;
					}

					if (this.wrapper)
					{
						this.blocksContainer = null;
						BX.cleanNode(this.wrapper, this.cleanState.cleanAll);
					}

					var afterClean = this.cleanState.afterClean;

					this.cleanState = {
						cleanAll: false,
						started: false,
						afterClean: null
					};
					this.mode = "EDIT";

					if (typeof(afterClean) === "function")
						afterClean();
				}
			}
		},
		destroy: function(afterDestroy)
		{
			this.clean(true, afterDestroy);
		}
	};
	BX.Crm.RequisiteBankDetailsArea.items = {};
	BX.Crm.RequisiteBankDetailsArea.create = function (id, parameters)
	{
		var self = new BX.Crm.RequisiteBankDetailsArea();
		self.initialize(id, parameters);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.Crm.RequisiteBankDetailsBlock) === "undefined")
{
	BX.Crm.RequisiteBankDetailsBlock = function (parameters)
	{
		this.bankDetailId = parseInt(parameters.bankDetailId);
		if (this.bankDetailId < 0)
			this.bankDetailId = 0;

		this.bankDetailData = (BX.type.isPlainObject(parameters.bankDetailData)) ? parameters.bankDetailData : {};

		this.blockArea = parameters.blockArea;
		this.blockIndex = parameters.blockIndex;
		this.elementIndex = parameters.elementIndex;
		this.container = parameters.container;
		this.nextNode = parameters.nextNode;
		this.fieldList = parameters.fieldList;
		this.fieldNameTemplate = parameters.fieldNameTemplate;
		this.mode = parameters.mode || "EDIT";
		this.wrapper = null;

		this.documentClickHandler = null;
		
		this.editNameMode = false;
		this.editNameInput = null;
		this.hiddenNameInput = null;
		this.nameLabelNode = null;
		this.editNameBtn = null;
		this.editNameKeyPressHandler = null;
		this.editNameBtnClickHandler = null;

		this.deleteBtn = null;
		this.deleteBtnClickHandler = null;
		this.markedAsDeleted = false;

		this.initialize();
	};
	BX.Crm.RequisiteBankDetailsBlock.prototype =
	{
		initialize: function()
		{
			var value, valueNode, valueNodeName, hiddenContainer, node;

			if (this.container)
			{
				this.clean();

				if (!this.wrapper)
				{
					this.wrapper = BX.create(
						"DIV",
						{
							"attrs": {"class": "crm-multi-address-item"}
						}
					);
					if (this.wrapper)
					{
						if (this.nextNode)
							this.container.insertBefore(this.wrapper, this.nextNode);
						else
							this.container.appendChild(this.wrapper);
					}
				}
			}

			if (this.wrapper && this.fieldList.length > 0)
			{
				var tableNode, row, cell;

				tableNode = BX.create("TABLE", {"attrs": {"class": "crm-offer-info-table"}});
				hiddenContainer = BX.create("DIV", {"attrs": {"style": "display: none;"}});

				// title
				row = tableNode.insertRow(-1);
				cell = row.insertCell(-1);
				cell.colSpan = "4";
				cell.appendChild(node = BX.create("DIV", {
					"attrs": {"class": "crm-offer-title"},
					"children": [
						this.editNameInput = BX.create("INPUT", {
							"attrs": {
								"class": "crm-item-table-inp",
								"type": "text",
								"placeholder": this.getMessage("fieldNamePlaceHolder"),
								"style": "display: none;"
							}
						}),
						this.nameLabelNode = BX.create("SPAN", {"attrs": {"class": "crm-offer-title-text"}})
					]
				}));
				if (node && this.mode === "EDIT")
				{
					this.editNameMode = false;
					this.documentClickHandler = BX.delegate(this.onDocumentClick, this);
					this.editNameKeyPressHandler = BX.delegate(this.onEditNameKeyPress, this);
					node.appendChild(BX.create("SPAN", {
						"attrs": {"class": "crm-offer-title-set-wrap"},
						"children": [
							this.editNameBtn = BX.create("SPAN", {"attrs": {"class": "crm-offer-title-edit"}}),
							this.deleteBtn = BX.create("SPAN", {"attrs": {"class": "crm-offer-title-del"}})
						]
					}));
					if (this.editNameBtn)
					{
						this.editNameBtnClickHandler = BX.delegate(this.onEditNameButtonClick, this);
						BX.bind(this.editNameBtn, "click", this.editNameBtnClickHandler);
					}
					if (this.deleteBtn)
					{
						this.deleteBtnClickHandler = BX.delegate(this.onDeleteButtonClick, this);
						BX.bind(this.deleteBtn, "click", this.deleteBtnClickHandler);
					}
				}

				for (var i = 0; i < this.fieldList.length; i++)
				{
					valueNodeName = this.resolveFieldInputName(this.fieldList[i]["name"]);
					if (BX.type.isPlainObject(this.bankDetailData)
						&& this.bankDetailData.hasOwnProperty(this.fieldList[i]["name"]))
					{
						value = this.bankDetailData[this.fieldList[i]["name"]];
					}
					else if (this.fieldList[i].hasOwnProperty("defaultValue"))
					{
						value = this.fieldList[i]["defaultValue"];
					}
					else
					{
						value = "";
					}
					if (this.fieldList[i]["name"] === "NAME")
					{
						if (!BX.type.isNotEmptyString(value))
						{
							value = this.getMessage("bankDetailsTitle") + " " + (this.elementIndex + 1);
						}
						if (this.nameLabelNode)
						{
							BX.adjust(this.nameLabelNode, {"text": value});
						}
						if (hiddenContainer)
						{
							hiddenContainer.appendChild(this.hiddenNameInput = BX.create("INPUT", {
								"attrs": {
									"type": "hidden",
									"name": valueNodeName,
									"value": value
								}
							}));
						}
					}
					else if (hiddenContainer && this.fieldList[i]["type"] === "hidden")
					{
						valueNode = BX.create("INPUT", {
							"attrs": {
								"type": "hidden",
								"name": valueNodeName,
								"value": value
							}
						});
						if (valueNode)
							hiddenContainer.appendChild(valueNode);
					}
					else if (tableNode)
					{
						row = tableNode.insertRow(-1);
						cell = row.insertCell(-1);
						cell.className = "crm-offer-info-left";
						cell.appendChild(BX.create("SPAN", {
							"attrs": {"class": "crm-offer-info-label-alignment"}
						}));
						if (this.fieldList[i]["required"])
						{
							cell.appendChild(BX.create("SPAN", {
								"attrs": {"class": "required"},
								"text": "*"
							}));
						}
						cell.appendChild(BX.create("SPAN", {
							"attrs": {"class": "crm-offer-info-label"},
							"text": this.fieldList[i]["title"] + ":"
						}));
						cell = row.insertCell(-1);
						cell.className = "crm-offer-info-right";
						valueNode = null;
						if (this.fieldList[i]["type"] === "text")
						{
							valueNode = BX.create("INPUT", {
								"attrs": {
									"type": "text",
									"class": "crm-offer-item-inp",
									"name": valueNodeName,
									"value": value
								}
							});
						}
						else if (this.fieldList[i]["type"] === "textarea")
						{
							valueNode = BX.create("TEXTAREA", {
								"attrs": {
									"class": "bx-crm-edit-text-area",
									"name": valueNodeName,
									"cols": 40,
									"rows": 3
								},
								"html": BX.util.htmlspecialchars(value)
							});
						}
						if (valueNode)
							cell.appendChild(valueNode);
						cell = row.insertCell(-1);
						cell.className = "crm-offer-info-right-btn";
						cell = row.insertCell(-1);
						cell.className = "crm-offer-last-td";
					}
				}

				if (tableNode)
					this.wrapper.appendChild(tableNode);
				if (hiddenContainer)
					this.wrapper.appendChild(hiddenContainer);
			}
		},
		setIndex: function(index)
		{
			this.blockIndex = index;
		},
		getPseudoId: function()
		{
			return (this.bankDetailId > 0) ? this.bankDetailId : "n" + this.elementIndex;
		},
		getWrapperNode: function()
		{
			return this.wrapper;
		},
		getMessage: function(name)
		{
			if (this.blockArea)
				return this.blockArea.getMessage(name);

			return "";
		},
		clean: function(cleanAll)
		{
			cleanAll = !!cleanAll;

			if (this.wrapper)
			{
				this.hiddenNameInput = null;
				this.nameLabelNode = null;
				if (this.editNameInput && this.editNameKeyPressHandler)
				{
					BX.unbind(this.editNameInput, "keydown", this.editNameKeyPressHandler);
					this.editNameKeyPressHandler = null;
				}
				this.editNameInput = null;
				this.editNameMode = false;
				if (this.documentClickHandler)
					this.enableDocumentClick(false);
				this.documentClickHandler = null;
				if (this.editNameBtn)
				{
					if (this.editNameBtnClickHandler)
					{
						BX.unbind(this.editNameBtn, "click", this.editNameBtnClickHandler);
						this.editNameBtnClickHandler = null;
					}
					this.editNameBtn = null;
				}
				if (this.deleteBtn)
				{
					if (this.deleteBtnClickHandler)
					{
						BX.unbind(this.deleteBtn, "click", this.deleteBtnClickHandler);
						this.deleteBtnClickHandler = null;
					}
					this.deleteBtn = null;
					this.markedAsDeleted = false;
				}

				BX.cleanNode(this.wrapper, cleanAll);
			}
		},
		destroy: function()
		{
			this.clean(true);

			if (this.blockArea)
				this.blockArea.onBlockDestroy(this.blockIndex);
		},
		onEditNameButtonClick: function()
		{
			if (this.editNameMode)
				return;

			this.enableEditNameMode(true);
		},
		enableEditNameMode: function(enable)
		{
			enable = !!enable;
			
			if (this.editNameMode === enable)
				return;
			
			this.editNameMode = enable;
			if (this.editNameMode)
			{
				if (this.nameLabelNode)
					this.nameLabelNode.style.display = "none";
				if (this.editNameInput)
				{
					this.editNameInput.style.display = "";
					if (this.hiddenNameInput)
						this.editNameInput.value = this.hiddenNameInput.value;
					this.editNameInput.focus();
					this.editNameInput.setSelectionRange(0, this.editNameInput.value.length);
					this.enableDocumentClick(true);
					BX.bind(this.editNameInput, "keydown", this.editNameKeyPressHandler);
				}
			}
			else 
			{
				this.editNameInput.style.display = "none";
				this.nameLabelNode.style.display = "";

				this.enableDocumentClick(false);
				BX.unbind(this.editNameInput, "keydown", this.editNameKeyPressHandler);
			}
		},
		enableDocumentClick: function(enable)
		{
			if(enable)
			{
				var self = this;
				window.setTimeout(function(){ BX.bind(document, "click", self.documentClickHandler); }, 0);
			}
			else
			{
				BX.unbind(document, "click", this.documentClickHandler);
			}
		},
		onDocumentClick: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			var target = BX.getEventTarget(e);
			if(target && this.editNameInput === target)
			{
				return;
			}

			if(!this.editNameMode)
			{
				BX.unbind(document, "click", this.documentClickHandler);
			}
			else
			{
				this.completeEditName(true);
			}
		},
		onEditNameKeyPress: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			if(e.keyCode == 13)
			{
				//Enter
				this.completeEditName(true);
				return BX.eventReturnFalse(e);
			}
			else if(e.keyCode == 27)
			{
				//Esc
				this.completeEditName(false);
				return BX.eventReturnFalse(e);
			}

			return true;
		},
		completeEditName: function(enableSaving)
		{
			if(!this.editNameMode)
			{
				return;
			}

			if(!!enableSaving)
			{
				var name = BX.util.trim(this.editNameInput.value);
				if(name !== "" && this.hiddenNameInput)
				{
					this.hiddenNameInput.value = name;
				}
				if (this.nameLabelNode)
					BX.adjust(this.nameLabelNode, {"text": name});
			}

			this.enableEditNameMode(false);
		},
		onDeleteButtonClick: function()
		{
			this.markAsDeleted();
		},
		resolveFieldInputName: function(fieldName)
		{
			if(this.fieldNameTemplate !== "")
			{
				return this.fieldNameTemplate.replace(
					"#ELEMENT_ID#", this.getPseudoId()
				).replace(
					"#FIELD_NAME#", fieldName
				);
			}

			return fieldName;
		},
		markAsDeleted: function()
		{
			if(this.markedAsDeleted)
			{
				return;
			}

			this.markedAsDeleted = true;

			if(this.wrapper)
			{
				this.wrapper.appendChild(
					BX.create("INPUT",
						{
							props:
							{
								"name": this.resolveFieldInputName("DELETED"),
								"type": "hidden",
								"value": "Y"
							}
						}
					)
				);
				this.wrapper.style.display = "none";
			}
		},
		isMarkedAsDeleted: function()
		{
			return this.markedAsDeleted;
		}
	};
}
