__logOnDateChange = function(sel)
{
	var bShowFrom=false, bShowTo=false, bShowHellip=false, bShowDays=false, bShowBr=false;

	if(sel.value == 'interval')
		bShowBr = bShowFrom = bShowTo = bShowHellip = true;
	else if(sel.value == 'before')
		bShowTo = true;
	else if(sel.value == 'after' || sel.value == 'exact')
		bShowFrom = true;
	else if(sel.value == 'days')
		bShowDays = true;

	BX('flt_date_from_span').style.display = (bShowFrom? '':'none');
	BX('flt_date_to_span').style.display = (bShowTo? '':'none');
	BX('flt_date_hellip_span').style.display = (bShowHellip? '':'none');
	BX('flt_date_day_span').style.display = (bShowDays? 'inline':'none');
}

function __logOnReload(log_counter)
{
	if (BX("menu-popup-lenta-sort-popup"))
	{
		var arMenuItems = BX.findChildren(BX("menu-popup-lenta-sort-popup"), { className: 'lenta-sort-item' }, true);

		if (!BX.hasClass(arMenuItems[0], 'lenta-sort-item-selected'))
		{
			for (var i = 0; i < arMenuItems.length; i++)
			{
				if (i == 0)
					BX.addClass(arMenuItems[i], 'lenta-sort-item-selected');
				else if (i != (arMenuItems.length-1))
					BX.removeClass(arMenuItems[i], 'lenta-sort-item-selected');
			}
		}
	}

	if (BX("lenta-sort-button"))
	{
		var menuButtonText = BX.findChild(BX("lenta-sort-button"), { className: 'lenta-sort-button-text-internal' }, true, false);
		if (menuButtonText)
			menuButtonText.innerHTML = BX.message('sonetLFAllMessages');
	}

	var counter_cont = BX("sonet_log_counter_preset", true);
	if (counter_cont)
	{
		if (parseInt(log_counter) > 0)
		{
			counter_cont.style.display = "inline-block";
			counter_cont.innerHTML = log_counter;
		}
		else
		{
			counter_cont.innerHTML = '';
			counter_cont.style.display = "none";
		}
	}
}

BitrixLFFilter = function ()
{
	this.filterPopup = false;
	this.filterUserPopup = false;
	this.filterCreatedByPopup = false;
};

BitrixLFFilter.prototype.onFilterGroupSelect = function(arGroups)
{
	if (arGroups[0])
	{
		BX('filter-field-user').value = '';
		document.forms["log_filter"]["flt_to_user_id"].value = 0;
		document.forms["log_filter"]["flt_group_id"].value = arGroups[0].id;
		BX.removeClass(BX("filter-field-group").parentNode.parentNode, "webform-field-textbox-empty");
	}
}

BitrixLFFilter.prototype.onFilterCreatedBySelect = function(arUser)
{
	if (arUser.id)
	{
		document.forms["log_filter"]["flt_created_by_id"].value = arUser.id;
		document.forms["log_filter"]["filter-field-created-by"].value = arUser.name;
		BX.removeClass(BX("filter-field-created-by").parentNode.parentNode, "webform-field-textbox-empty");
		if (BX("flt_comments_cont"))
		{
			BX("flt_comments_cont").style.display = "block";
		}
	}
	else if (BX("flt_comments_cont"))
	{
		BX("flt_comments_cont").style.display = "none";
	}

	oLFFilter.filterCreatedByPopup.close();
}

BitrixLFFilter.prototype.onFilterUserSelect = function(arUser)
{
	if (arUser.id)
	{
		BX('filter-field-group').value = '';
		document.forms["log_filter"]["flt_group_id"].value = 0;
		document.forms["log_filter"]["flt_to_user_id"].value = arUser.id;
		document.forms["log_filter"]["filter-field-user"].value = arUser.name;
		BX.removeClass(BX("filter-field-user").parentNode.parentNode, "webform-field-textbox-empty");
	}

	oLFFilter.filterUserPopup.close();
}

BitrixLFFilter.prototype.onFilterDestChangeTab = function(type)
{
	var type_hide;
	if (type != 'group')
	{
		type = 'user';
		type_hide = 'group';
		if (
			filterGroupsPopup
			&& typeof filterGroupsPopup.popupWindow != 'undefined'
		)
		{
			filterGroupsPopup.popupWindow.close();
		}
	}
	else
	{
		type_hide = 'user';
		if (oLFFilter.filterUserPopup)
		{
			oLFFilter.filterUserPopup.close();
		}
	}

	BX.removeClass(BX('filter-dest-' + type + '-tab'), 'webform-field-action-link');
	BX.addClass(BX('filter-dest-' + type_hide + '-tab'), 'webform-field-action-link');

	BX('filter-dest-' + type + '-block').style.display = 'inline-block';
	BX('filter-dest-' + type_hide + '-block').style.display = 'none';

	if (type != 'group')
	{
		BX("filter-field-user").focus();
		oLFFilter.__SLFShowUseropup(BX("filter-field-user"));
	}
	else
	{
		BX("filter-field-group").focus();
		oLFFilter.__SLFShowGroupsPopup();
	}
}

BitrixLFFilter.prototype.ShowFilterPopup = function(bindElement)
{
	if (!this.filterPopup)
	{
		//BX.showWait(bindElement);
		BX.ajax.get(BX.message('sonetLFAjaxPath'), function(data)
		{
			BX.closeWait(bindElement);

			this.filterPopup = new BX.PopupWindow(
				'bx_log_filter_popup',
				bindElement,
				{
					closeIcon : false,
					offsetTop: 5,
					autoHide: true,
					zIndex : -100,
					//angle : { offset : 59},
					className : 'sonet-log-filter-popup-window',
					events : {
						onPopupClose: function() {
							if (!BX.hasClass(this.bindElement, "pagetitle-menu-filter-set"))
								BX.removeClass(this.bindElement, "pagetitle-menu-filter-selected")
						},
						onPopupShow: function() { BX.addClass(this.bindElement, "pagetitle-menu-filter-selected")}
					}
				}
			);
			var filter_block = BX.create('DIV', {html: BX.util.trim(data)});
			this.filterPopup.setContent(filter_block.firstChild);
			this.filterPopup.show();

			BX.bind(BX("filter-field-created-by"), "click", function(e) {
				if(!e) e = window.event;

				oLFFilter.__SLFShowCreatedByPopup(this);
				return BX.PreventDefault(e);
			});

			BX.bind(BX.findNextSibling(BX("filter-field-created-by"), {tagName : "a"}), "click", function(e){
				if(!e) e = window.event;

				BX("filter-field-created-by").value = "";
				BX("filter_field_createdby_hidden").value = "0";
				BX.addClass(BX("filter-field-created-by").parentNode.parentNode, "webform-field-textbox-empty");
				if (BX("flt_comments_cont"))
				{
					BX("flt_comments_cont").style.display = "none";
				}
				return BX.PreventDefault(e);
			});

			if (BX("filter-field-group"))
			{
				BX.bind(BX("filter-field-group"), "click", function(e) {
					if(!e) e = window.event;

					oLFFilter.__SLFShowGroupsPopup();
					return BX.PreventDefault(e);
				});

				BX.bind(BX.findNextSibling(BX("filter-field-group"), {tagName : "a"}), "click", function(e){
					if(!e) e = window.event;

					filterGroupsPopup.deselect(BX("filter_field_group_hidden").value.value);
					BX("filter_field_group_hidden").value = "0";
					BX.addClass(BX("filter-field-group").parentNode.parentNode, "webform-field-textbox-empty");
					return BX.PreventDefault(e);
				});
			}

			if (BX("filter-field-user"))
			{
				BX.bind(BX("filter-field-user"), "click", function(e) {
					if(!e) e = window.event;

					oLFFilter.__SLFShowUseropup(this);
					return BX.PreventDefault(e);
				});

				BX.bind(BX.findNextSibling(BX("filter-field-user"), {tagName : "a"}), "click", function(e){
					if(!e) e = window.event;

					BX("filter-field-user").value = "";
					BX("filter_field_user_hidden").value = "0";
					BX.addClass(BX("filter-field-user").parentNode.parentNode, "webform-field-textbox-empty");
					return BX.PreventDefault(e);
				});
			}
		});
	}
	else
	{
		this.filterPopup.show();
	}
}

BitrixLFFilter.prototype.__SLFShowCreatedByPopup = function(obj)
{
	this.filterCreatedByPopup = BX.PopupWindowManager.create("filter-created-by-popup", obj.parentNode, {
		offsetTop : 1,
		autoHide : true,
		content : BX("FILTER_CREATEDBY_selector_content"),
		zIndex : 1200,
		buttons : [
			new BX.PopupWindowButton({
				text : BX.message("sonetLFDialogClose"),
				className : "popup-window-button-accept",
				events : {
					click : function() {
						this.popupWindow.close();
					}
				}
			})
		]
	});

	if (this.filterCreatedByPopup.popupContainer.style.display != "block")
	{
		this.filterCreatedByPopup.show();
	}
}

BitrixLFFilter.prototype.__SLFShowGroupsPopup = function()
{
	BX('filter-field-user').value = '';
	BX('filter_field_user_hidden').value = "0";

	filterGroupsPopup.show();
}

BitrixLFFilter.prototype.__SLFShowUseropup = function(obj)
{
	this.filterUserPopup = BX.PopupWindowManager.create("filter-user-popup", obj.parentNode, {
		offsetTop : 1,
		autoHide : true,
		content : BX("FILTER_USER_selector_content"),
		zIndex : 1200,
		buttons : [
			new BX.PopupWindowButton({
				text : BX.message("sonetLFDialogClose"),
				className : "popup-window-button-accept",
				events : {
					click : function() {
						this.popupWindow.close();
					}
				}
			})
		]
	});

	if (this.filterUserPopup.popupContainer.style.display != "block")
	{
		this.filterUserPopup.show();
	}
}

BitrixLFFilter.prototype.__SLFShowExpertModePopup = function(bindObj)
{
	var modalWindow = new BX.PopupWindow('setExpertModePopup', bindObj, {
		closeByEsc: false,
		closeIcon: false,
		autoHide: false,
		overlay: true,
		events: {},
		buttons: [],
		zIndex : 0,
		content: BX.create('DIV', {
			children: [
				BX.create('DIV', {
					props: {
						className: 'bx-slf-popup-title'
					},
					text: BX.message('sonetLFExpertModePopupTitle')
				}),
				BX.create('DIV', {
					props: {
						className: 'bx-slf-popup-content'
					},
					children: [
						BX.create('DIV', {
							props: {
								className: 'bx-slf-popup-cont-title'
							},
							html: BX.message('sonetLFExpertModePopupText1')
						}),
						BX.create('DIV', {
							props: {
								className: 'bx-slf-popup-descript'
							},
							children: [
								BX.create('DIV', {
									html: BX.message('sonetLFExpertModePopupText2')
								}),
								BX.create('IMG', {
									props: {
										className: 'bx-slf-popup-descript-img'
									},
									attrs: {
										src: BX.message('sonetLFExpertModeImagePath'),
										width: 354,
										height: 201
									}
								})
							]
						})
					]
				}),
				BX.create('DIV', {
					props: {
						className: 'popup-window-buttons'
					},
					children: [
						BX.create('SPAN', {
							props: {
								className: 'popup-window-button popup-window-button-accept'
							},
							events: {
								click: function () {
									BX.ajax({
										method: 'POST',
										dataType: 'json',
										url: BX.message('ajaxControllerURL'),
										data: {
											sessid : BX.bitrix_sessid(),
											closePopup: 'Y'
										},
										onsuccess: function(response)
										{
											if (
												typeof (response) != 'undefined'
												&& typeof (response.SUCCESS) != 'undefined'
												&& response.SUCCESS == 'Y'
											)
											{
												modalWindow.close();
												top.location = top.location.href;
											}
										}
									});
								}
							},
							children: [
								BX.create('SPAN', {
									props: {
										className: 'popup-window-button-left'
									}
								}),
								BX.create('SPAN', {
									props: {
										className: 'popup-window-button-text'
									},
									text: BX.message('sonetLFDialogClose')
								}),
								BX.create('SPAN', {
									props: {
										className: 'popup-window-button-right'
									}
								})
							]
						})
					]
				})
			]
		})
	});
	modalWindow.show();
}

oLFFilter = new BitrixLFFilter;
window.oLFFilter = oLFFilter;