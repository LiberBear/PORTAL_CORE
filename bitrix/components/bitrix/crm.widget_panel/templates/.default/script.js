if(typeof(BX.CrmWidget) === "undefined")
{
	BX.CrmWidget = function()
	{
		this._id = "";
		this._settings = null;
		this._entityTypeName = "";
		this._typeName = "";
		this._serviceUrl = "";
		this._title = "";
		this._prefix = "";
		this._heightInPixel = 0;
		this._widthInPercent = 0;
		this._layout = "";
		this._config = null;
		this._configEditor = null;
		this._data = {};
		this._container = null;
		this._wrapper = null;
		this._headerWrapper = null;
		this._contentWrapper = null;
		this._settingButton = null;
		//this._editButton = null;
		this._hasLayout = false;
		this._cell = null;
		this._settingButtonClickHandler = BX.delegate(this.onSettingButtonClick, this);
		this._isSettingMenuShown = false;
		this._settingMenuId = "";
		this._settingMenu = null;
		this._settingMenuHandler = BX.delegate(this.onSettingMenuItemClick, this);
	};
	BX.CrmWidget.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._entityTypeName = this.getSetting("entityTypeName", "");

			this._cell = this.getSetting("cell", null);
			this._config = this.getSetting("config", "");
			this._typeName = BX.type.isNotEmptyString(this._config["typeName"]) ? this._config["typeName"] : "";
			if(this._typeName === "")
			{
				throw "CrmWidget: The type name is not found.";
			}
			this._data = this.getSetting("data", {});
			if(!BX.type.isArray(this._data["items"]))
			{
				this._data["items"] = [];
			}

			this._prefix = this.getSetting("prefix", "");

			var containerId = this.getSetting("containerId", "");
			this._container = BX(containerId !== "" ? containerId : (this._prefix + "_" + "container"));

			this._heightInPixel = parseInt(this.getSetting("heightInPixel", 0));
			if(this._heightInPixel <= 0)
			{
				this._heightInPixel = BX.CrmWidgetLayoutHeight.full;
			}

			this._widthInPercent = parseInt(this.getSetting("widthInPercent", 0));
			if(this._widthInPercent <= 0)
			{
				this._widthInPercent = 100;
			}

			this._layout = BX.type.isNotEmptyString(this._config["layout"]) ? this._config["layout"] : "";
			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		getTitle: function()
		{
			return BX.type.isNotEmptyString(this._config["title"]) ? this._config["title"] : "";
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidget.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getContainer: function()
		{
			return this._container;
		},
		setContainer: function(container)
		{
			this._container = container;
		},
		getWrapper: function()
		{
			return this._wrapper;
		},
		getPanel: function()
		{
			return this._cell ? this._cell.getPanel() : null;
		},
		getCell: function()
		{
			return this._cell;
		},
		getRow: function()
		{
			return this._cell ? this._cell.getRow() : null;
		},
		getIndex: function()
		{
			return this._cell ? this._cell.getWidgetIndex(this) : -1;
		},
		getNextSibling: function()
		{
			return this._cell ? this._cell.getNextWidget(this) : null;
		},
		getConfig: function()
		{
			return this._config;
		},
		getPeriodDescription: function()
		{
			var filter = BX.type.isPlainObject(this._config["filter"]) ? this._config["filter"] : null;
			if(!filter)
			{
				return "";
			}

			var editor = BX.CrmWidgetConfigPeriodEditor.create("", { config: filter });
			return !editor.isEmpty() ? editor.getDescription() : "";
		},
		getHeight: function()
		{
			return BX.CrmWidgetLayoutHeight.undifined;
		},
		getData: function()
		{
			return this._data;
		},
		setData: function(data)
		{
			this._data = data;
		},
		refresh: function()
		{
			this.clearLayout();
			this.layout();

			var panel = this.getPanel();
			if(panel)
			{
				panel.processWidgetRefresh(this);
			}
		},
		invalidate: function()
		{
		},
		prepareHeader: function(title, buttons)
		{
			var wrapper = BX.create("DIV", { attrs: { className: "crm-widget-head" } });

			if(buttons && typeof(buttons["settings"]) !== "undefined")
			{
				wrapper.appendChild(buttons["settings"]);
			}

			var innerWrapper = BX.create("SPAN",
				{
					attrs: { className: "crm-widget-title-container" },
					children:
					[
						BX.create("SPAN",
							{
								attrs: { className: "crm-widget-title-inner" },
								children:
								[
									BX.create("SPAN",
										{ attrs: { className: "crm-widget-title", title: title }, text: title }
									)
								]
							}
						)
					]
				}
			);

			wrapper.appendChild(innerWrapper);
			if(buttons && typeof(buttons["edit"]) !== "undefined")
			{
				innerWrapper.appendChild(buttons["edit"]);
			}
			return wrapper;
		},
		prepareButtons: function()
		{
			return({
				"settings": BX.create("SPAN", { attrs: { className: "crm-widget-settings" } })
				//'edit': BX.create("SPAN", { attrs: { className: "crm-widget-title-edit" } })
			});
		},
		renderHeader: function(container)
		{
			this._headerWrapper = BX.create("DIV", { attrs: { className: "crm-widget-head" } });
			if(BX.type.isElementNode(container))
			{
				container.appendChild(this._headerWrapper);
			}
			else
			{
				this._wrapper.appendChild(this._headerWrapper);
			}

			this._settingButton = BX.create("SPAN",  { attrs: { className: "crm-widget-settings" } });
			//this._editButton = BX.create("SPAN",  { attrs: { className: "crm-widget-title-edit" } });

			this._headerWrapper.appendChild(this._settingButton);

			var title = this.getTitle();
			if(title === "")
			{
				title = this.getMessage("untitled");
			}
			this._headerWrapper.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "crm-widget-title-container" },
						children:
						[
							BX.create("SPAN",
								{
									attrs: { className: "crm-widget-title-inner" },
									children:
									[
										BX.create("SPAN",
											{ attrs: { className: "crm-widget-title", title: title }, text: title }
										),
										this._editButton
									]
								}
							)
						]
					}
				)
			);
		},
		renderContent: function()
		{
		},
		renderLayout: function()
		{
			this.renderHeader();
			this.renderContent();
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			if(!this._container)
			{
				throw "CrmWidget: Could not find container.";
			}

			this._wrapper = BX.create("DIV", { attrs: { className: "crm-widget" } });
			this._wrapper.setAttribute("data-widget-id", this._id);

			var next = this.getNextSibling();
			var nextWrapper = next !== null ? next.getWrapper() : null;
			if(nextWrapper === null)
			{
				this._container.appendChild(this._wrapper);
			}
			else
			{
				this._container.insertBefore(this._wrapper, nextWrapper);
			}

			this.renderLayout();

			if(this._settingButton)
			{
				BX.bind(this._settingButton, "click", this._settingButtonClickHandler);
			}

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this.innerClearLayout();

			if(this._settingButton)
			{
				BX.unbind(this._settingButton, "click", this._settingButtonClickHandler);
				this._settingButton = null;
			}

			if(this._container)
			{
				try
				{
					this._container.removeChild(this._wrapper);
				}
				catch(e)
				{
				}
			}
			this._wrapper = null;

			this._hasLayout = false;
		},
		innerClearLayout: function()
		{
		},
		remove: function()
		{
			if(!window.confirm(this.getMessage("removalConfirmation").replace(/#TITLE#/gi, this.getTitle())))
			{
				return false;
			}

			this.clearLayout();
			this.undock();
			return true;
		},
		dock: function(cell, index)
		{
			cell.addWidget(this, index);
			this._cell = cell;
		},
		undock: function()
		{
			if(this._cell)
			{
				this._cell.removeWidget(this);
				this._cell = null;
			}
		},
		processConfigSave: function()
		{
			var panel = this.getPanel();
			if(!panel || !this._configEditor)
			{
				return;
			}

			this._configEditor.saveConfig();
			this._config = this._configEditor.getConfig();
			panel.saveConfig(BX.delegate(this.onAfterConfigSave, this));
		},
		onAfterConfigSave: function()
		{
			BX.CrmWidgetManager.getCurrent().prepareWidgetData(this);
		},
		ensureConfigEditorCreated: function()
		{
			if(!this._configEditor)
			{
				this._configEditor = BX.CrmWidgetConfigEditor.create(
					this._id + "_config",
					{ widget: this, config: this._config, entityTypeName: this._entityTypeName }
				);
			}
		},
		onSettingButtonClick: function()
		{
			if(!this._isSettingMenuShown)
			{
				this.openSettingMenu();
			}
			else
			{
				this.closeSettingMenu();
			}
		},
		openSettingMenu: function()
		{
			if(this._isSettingMenuShown)
			{
				return;
			}

			this._settingMenuId = this._id + "_menu";
			if(typeof(BX.PopupMenu.Data[this._settingMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._settingMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._settingMenuId];
			}

			this._settingMenu = BX.PopupMenu.create(
				this._settingMenuId,
				this._settingButton,
				[
					{ id: "configure", text: this.getMessage("menuItemConfigure"), onclick: this._settingMenuHandler },
					{ id: "remove", text: this.getMessage("menuItemRemove"), onclick: this._settingMenuHandler }
				],
				{
					autoHide: true,
					offsetLeft: -21,
					offsetTop: -11,
					angle:
					{
						position: "top",
						offset: 42
					},
					events:
					{
						onPopupClose : BX.delegate(this.onSettingMenuClose, this)
					}
				}
		   );
		   this._settingMenu.popupWindow.show();
		   this._isSettingMenuShown = true;
		},
		closeSettingMenu: function()
		{
			if(this._settingMenu && this._settingMenu.popupWindow)
			{
				this._settingMenu.popupWindow.close();
			}
		},
		onSettingMenuClose: function()
		{
			this._settingMenu = null;
			if(typeof(BX.PopupMenu.Data[this._settingMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._settingMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._settingMenuId];
			}
			this._isSettingMenuShown = false;
		},
		onSettingMenuItemClick: function(e, item)
		{
			if(item.id === "configure")
			{
				this.openConfigDialog();
			}
			else if(item.id === "remove")
			{
				var panel = this.getPanel();
				if(panel)
				{
					panel.processWidgetRemoval(this);
				}
			}
			this.closeSettingMenu();
		},
		openConfigDialog: function()
		{
			this.ensureConfigEditorCreated();
			this._configEditor.openDialog();
		}
	};
	if(typeof(BX.CrmWidget.messages) === "undefined")
	{
		BX.CrmWidget.messages = {};
	}
	BX.CrmWidget.create = function(id, settings)
	{
		var self = new BX.CrmWidget();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmFunnelWidget) === "undefined")
{
	BX.CrmFunnelWidget = function()
	{
		this._chart = null;
		BX.CrmFunnelWidget.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmFunnelWidget, BX.CrmWidget);
	BX.CrmFunnelWidget.prototype.renderContent = function()
	{
		if(!AmCharts.isReady)
		{
			if(BX.CrmWidgetPanel.isAjaxMode)
			{
				AmCharts.handleLoad();
			}
			else
			{
				AmCharts.ready(BX.delegate(this.renderContent, this));
				return;
			}
		}

		this._contentWrapper = BX.create("DIV",
			{ attrs: { id: this._prefix + "_" +  "content_wrapper", className: "crm-widget-content" } }
		);
		this._wrapper.appendChild(this._contentWrapper);
		if(this._heightInPixel > 0)
		{
			this._contentWrapper.style.height = this._heightInPixel + "px";
		}
		var periodDescr = this.getPeriodDescription();
		if(periodDescr !== "")
		{
			this._contentWrapper.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "crm-widget-content-period" },
						children: [ BX.create("SPAN", { html: this.getMessage("periodCaption") + ": " + periodDescr}) ]
					}
				)
			);
		}

		var chartWrapper = BX.create("DIV", { attrs: { id: this._prefix + "_chart_wrapper" } });
		this._contentWrapper.appendChild(chartWrapper);
		if(this._heightInPixel > 0)
		{
			chartWrapper.style.height = (this._heightInPixel - 40) + "px";
		}

		var valueField = BX.type.isNotEmptyString(this._data["valueField"]) ? this._data["valueField"] : "";
		var titleField = BX.type.isNotEmptyString(this._data["titleField"]) ? this._data["titleField"] : "";
		var items = BX.type.isArray(this._data["items"]) ? this._data["items"] : [];
		for(var i = 0; i < items.length; i++)
		{
			var title = BX.type.isNotEmptyString(items[i][titleField]) ? items[i][titleField] : "";
			if(title !== "")
			{
				items[i][titleField] = BX.util.strip_tags(title);
			}
		}

		this._chart = AmCharts.makeChart(chartWrapper.id,
			{
				"type": "funnel",
				"theme": "none",
				"titleField": titleField,
				"valueField": valueField,
				"dataProvider": items,
				"labelPosition": "right",
				"depth3D": 160,
				"angle": 16,
				"outlineAlpha": 2,
				"outlineColor": "#FFFFFF",
				"outlineThickness": 2,
				"startY": -400,
				"marginRight": 240,
				"marginLeft": 10,
				"balloon": { "fixedPosition": true }
			}
		);
	};
	BX.CrmFunnelWidget.prototype.innerClearLayout = function()
	{
		if(this._chart)
		{
			this._chart.clear();
			this._chart = null;
		}
	};
	BX.CrmFunnelWidget.prototype.ensureConfigEditorCreated = function()
	{
		if(!this._configEditor)
		{
			this._configEditor = BX.CrmWidgetConfigEditor.create(
				this._id + "_config",
				{ widget: this, config: this._config, enableTitle: true, entityTypeName: this._entityTypeName }
			);
		}
	};
	BX.CrmFunnelWidget.prototype.getHeight = function()
	{
		return BX.CrmWidgetLayoutHeight.full;
	};
	BX.CrmFunnelWidget.prototype.invalidate = function()
	{
		if(this._chart)
		{
			this._chart.invalidateSize();
		}
	};
	BX.CrmFunnelWidget.create = function(id, settings)
	{
		var self = new BX.CrmFunnelWidget();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmPieWidget) === "undefined")
{
	BX.CrmPieWidget = function()
	{
		this._chart = null;
		BX.CrmPieWidget.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmPieWidget, BX.CrmWidget);
	BX.CrmPieWidget.prototype.renderContent = function()
	{
		if(!AmCharts.isReady)
		{
			if(BX.CrmWidgetPanel.isAjaxMode)
			{
				AmCharts.handleLoad();
			}
			else
			{
				AmCharts.ready(BX.delegate(this.renderContent, this));
				return;
			}
		}

		this._contentWrapper = BX.create("DIV",
			{ attrs: { id: this._prefix + "_" +  "content_wrapper", className: "crm-widget-content" } }
		);
		this._wrapper.appendChild(this._contentWrapper);
		if(this._heightInPixel > 0)
		{
			this._contentWrapper.style.height = this._heightInPixel + "px";
		}
		var periodDescr = this.getPeriodDescription();
		if(periodDescr !== "")
		{
			this._contentWrapper.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "crm-widget-content-period" },
						children: [ BX.create("SPAN", { html: this.getMessage("periodCaption") + ": " + periodDescr}) ]
					}
				)
			);
		}

		var chartWrapper = BX.create("DIV", { attrs: { id: this._prefix + "_chart_wrapper" } });
		this._contentWrapper.appendChild(chartWrapper);
		if(this._heightInPixel > 0)
		{
			chartWrapper.style.height = (this._heightInPixel - 40) + "px";
		}

		this._chart = AmCharts.makeChart(chartWrapper.id,
			{
				"type": "pie",
				"theme": "none",
				"titleField": BX.type.isNotEmptyString(this._data["titleField"]) ? this._data["titleField"] : "",
				"valueField": BX.type.isNotEmptyString(this._data["valueField"]) ? this._data["valueField"] : "",
				"dataProvider": BX.type.isArray(this._data["items"]) ? this._data["items"] : [],
				"labelsEnabled": false,
				"depth3D": 15,
				"angle": 30,
				"outlineAlpha": 0.4,
				"outlineColor": "#FFFFFF",
				"outlineThickness": 1,
				"legend":
				{
					"markerType": "circle",
					"position": "right",
					"marginRight": 10,
					"autoMargins": false
				}
			}
		);
	};
	BX.CrmPieWidget.prototype.innerClearLayout = function()
	{
		if(this._chart)
		{
			this._chart.clear();
			this._chart = null;
		}
	};
	BX.CrmPieWidget.prototype.ensureConfigEditorCreated = function()
	{
		if(!this._configEditor)
		{
			this._configEditor = BX.CrmWidgetConfigEditor.create(
				this._id + "_config",
				{ widget: this, config: this._config, enableTitle: true, entityTypeName: this._entityTypeName }
			);
		}
	};
	BX.CrmPieWidget.prototype.getHeight = function()
	{
		return BX.CrmWidgetLayoutHeight.full;
	};
	BX.CrmPieWidget.prototype.invalidate = function()
	{
		if(this._chart)
		{
			this._chart.invalidateSize();
		}
	};
	BX.CrmPieWidget.create = function(id, settings)
	{
		var self = new BX.CrmPieWidget();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmGraphWidget) === "undefined")
{
	BX.CrmGraphWidget = function()
	{
		this._chart = null;
		this._chartWrapper = null;
		this._maxGraphCount = 0;
		BX.CrmGraphWidget.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmGraphWidget, BX.CrmWidget);
	BX.CrmGraphWidget.prototype.doInitialize = function()
	{
		var panel = this.getPanel();
		if(panel)
		{
			this._maxGraphCount = panel.getMaxGraphCount();
		}
	};
	BX.CrmGraphWidget.prototype.prepareGraphSettings = function(config)
	{
		var result =
			{
				"title": config["title"],
				"valueField": config["selectField"],
				"balloonText": "[[title]]: [[value]]"
			};

		var displayParams = typeof(config["display"]) !== "undefined" ? config["display"] : {};
		var graphParams = typeof(displayParams["graph"]) !== "undefined" ? displayParams["graph"] : {};

		var graphType = this._typeName;
		if(BX.type.isNotEmptyString(graphParams["type"]))
		{
			graphType = config["display"]["type"];
		}

		if(BX.type.isNotEmptyString(graphParams["clustered"]))
		{
			result["clustered"] = graphParams["clustered"] === 'Y';
		}

		var scheme = BX.CrmWidgetColorScheme.getInfo(
			BX.type.isNotEmptyString(displayParams["colorScheme"]) ? displayParams["colorScheme"] : ""
		);
		if(scheme)
		{
			result["lineColor"] = scheme["color"];
		}

		if(graphType === "bar")
		{
			result["type"] = "column";
			result["fillAlphas"] = 1;
		}
		else
		{
			result["type"] = "smoothedLine";
			result["lineThickness"] = 2;
			result["bullet"] = "round";
			result["bulletSize"] = 7;
			result["bulletBorderAlpha"] = 1;
		}

		return result;
	};
	BX.CrmGraphWidget.prototype.renderContent = function()
	{
		if(!AmCharts.isReady)
		{
			if(BX.CrmWidgetPanel.isAjaxMode)
			{
				AmCharts.handleLoad();
			}
			else
			{
				AmCharts.ready(BX.delegate(this.renderContent, this));
				return;
			}
		}

		this._contentWrapper = BX.create("DIV",
			{ attrs: { id: this._prefix + "_" +  "content_wrapper", className: "crm-widget-content" } }
		);
		this._wrapper.appendChild(this._contentWrapper);
		if(this._heightInPixel > 0)
		{
			this._contentWrapper.style.height = this._heightInPixel + "px";
			this._contentWrapper.style.overflow = "visible";
		}

		var periodDescr = this.getPeriodDescription();
		if(periodDescr !== "")
		{
			this._contentWrapper.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "crm-widget-content-period" },
						children: [ BX.create("SPAN", { html: this.getMessage("periodCaption") + ": " + periodDescr}) ]
					}
				)
			);
		}

		var item = BX.type.isArray(this._data["items"]) && this._data["items"].length > 0
			? this._data["items"][0] : {};
		var graphs = [];
		var graphConfigs = BX.type.isArray(item["graphs"]) ? item["graphs"] : null;
		if(graphConfigs)
		{
			var graphCount =  (this._maxGraphCount > 0 && graphConfigs.length < this._maxGraphCount)
				? graphConfigs.length : this._maxGraphCount;

			for(var i = 0; i < graphCount; i++)
			{
				graphs.push(this.prepareGraphSettings(graphConfigs[i]));
			}
		}
		else
		{
			graphs.push(this.prepareGraphSettings(item));
		}

		var values = BX.type.isArray(item["values"]) ? item["values"] : [];
		var groupField = BX.type.isNotEmptyString(item["groupField"]) ? item["groupField"] : "";
		var chartConfig =
			{
				type: "serial",
				theme: "none",
				marginLeft: 20,
				dataProvider: values,
				graphs: graphs,
				dataDateFormat: this._data["dateFormat"],
				categoryField: groupField,
				legend:
					{
						useGraphSettings: true,
						equalWidths: false,
						position: "bottom"
					},
				chartCursor:
					{
						enabled: true,
						oneBalloonOnly: true,
						categoryBalloonEnabled: true,
						categoryBalloonColor: "#000000"
					}
			};

		var format = BX.type.isPlainObject(this._config["format"]) ? this._config["format"] : {};
		if(BX.type.isNotEmptyString(format["isCurrency"]) && format["isCurrency"] === "Y")
		{
			var currencyFormat = this.getPanel().getCurrencyFormat();
			if(BX.type.isPlainObject(currencyFormat))
			{
				chartConfig["numberFormatter"] =
					{
						decimalSeparator: currencyFormat["DEC_POINT"],
						thousandsSeparator: currencyFormat["THOUSANDS_SEP"]
					};
			}
		}

		if(this._typeName === "bar" && graphs.length > 0)
		{
			var valueAxeCongig = { id:"v1", axisAlpha: 0.3, gridAlpha: 0 };
			if(BX.type.isNotEmptyString(this._config["enableStack"]) && this._config["enableStack"] === "Y")
			{
				valueAxeCongig["stackType"] = "regular";
			}
			if(BX.type.isNotEmptyString(this._config["integersOnly"]) && this._config["integersOnly"] === "Y")
			{
				valueAxeCongig["integersOnly"] = true;
			}

			chartConfig["valueAxes"] = [valueAxeCongig];
			//chartConfig["depth3D"] = 20;
			//chartConfig["angle"] = 30;
		}

		if(groupField === "DATE")
		{
			var monthNames = [];
			var shortMonthNames = [];
			for(var m = 1; m <= 12; m++)
			{
				monthNames.push(BX.message["MONTH_" + m.toString()]);
				shortMonthNames.push(BX.message["MON_" + m.toString()]);
			}
			AmCharts.monthNames = monthNames;
			AmCharts.shortMonthNames = shortMonthNames;

			var rawFormat = BX.message("FORMAT_DATE");
			var dateFormat = rawFormat.indexOf("D") < rawFormat.indexOf("M") ? "DD MMM" : "MMM DD";
			chartConfig["chartCursor"]["categoryBalloonDateFormat"] = dateFormat;
			chartConfig["categoryAxis"] =
				{
					parseDates: true,
					minPeriod: "DD",
					dateFormats:
						[
							{ period: "fff", format:'JJ:NN:SS' },
							{ period: "ss", format:'JJ:NN:SS' },
							{ period: "mm", format:'JJ:NN' },
							{ period: "hh", format:'JJ:NN' },
							{ period: "DD", format: dateFormat },
							{ period: "WW", format: dateFormat },
							{ period: "MM", format: "MMM" },
							{ period: "YYYY", format: "YYYY" }
						]
				};
		}
		else if(groupField === "USER")
		{
			chartConfig["categoryAxis"] = { labelRotation: values.length < 5 ? 0 : 45 };
		}

		this._chartWrapper = BX.create("DIV", { attrs: { id: this._prefix + "_chart_wrapper" } });
		this._contentWrapper.appendChild(this._chartWrapper);
		if(this._heightInPixel > 0)
		{
			this._chartWrapper.style.height = (this._heightInPixel - 40) + "px";
		}

		this._chart = AmCharts.makeChart(this._chartWrapper.id, chartConfig);
	};
	BX.CrmGraphWidget.prototype.innerClearLayout = function()
	{
		if(this._chart)
		{
			this._chart.clear();
			this._chart = null;
		}
	};
	BX.CrmGraphWidget.prototype.ensureConfigEditorCreated = function()
	{
		if(!this._configEditor)
		{
			this._configEditor = BX.CrmGraphWidgetConfigEditor.create(
				this._id + "_config",
				{
					widget: this,
					config: this._config,
					enableTitle: true,
					maxGraphCount: this.getMaxGraphCount(),
					entityTypeName: this.getEntityTypeName()
				}
			);
		}
	};
	BX.CrmGraphWidget.prototype.getHeight = function()
	{
		return BX.CrmWidgetLayoutHeight.full;
	};
	BX.CrmGraphWidget.prototype.invalidate = function()
	{
		if(this._chart)
		{
			this._chart.invalidateSize();
		}
	};
	BX.CrmGraphWidget.prototype.getMaxGraphCount = function()
	{
		return this._maxGraphCount;
	};
	BX.CrmGraphWidget.create = function(id, settings)
	{
		var self = new BX.CrmGraphWidget();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmRatingWidget) === "undefined")
{
	BX.CrmRatingWidget = function()
	{
		BX.CrmRatingWidget.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmRatingWidget, BX.CrmWidget);
	BX.CrmRatingWidget.prototype.renderContent = function()
	{
		BX.addClass(this._wrapper, "crm-widget-rating");
		this._contentWrapper = BX.create("DIV",
			{ attrs: { id: this._prefix + "_" +  "content_wrapper", className: "crm-widget-content" } }
		);

		this._wrapper.appendChild(this._contentWrapper);
		var wrapper = BX.create("DIV", { attrs: { className: "crm-widget-content-rating" } });
		this._contentWrapper.appendChild(wrapper);

		var periodDescr = this.getPeriodDescription();
		if(periodDescr !== "")
		{
			this._contentWrapper.appendChild(
				BX.create("SPAN",
					{
						attrs: { className: "crm-widget-content-period" },
						children: [ BX.create("SPAN", { html: this.getMessage("periodCaption") + ": " + periodDescr}) ]
					}
				)
			);
		}

		var item = BX.type.isArray(this._data["items"]) && this._data["items"].length > 0
			? this._data["items"][0] : {};
		var nomineeId = parseInt(item["nomineeId"]);
		var positions = BX.type.isArray(item["positions"]) ? item["positions"] : [];
		var i;
		var nomineeIndex = -1;
		for(i = 0; i < positions.length; i++)
		{
			if(parseInt(positions[i]["id"]) === nomineeId)
			{
				nomineeIndex = i;
				break;
			}
		}

		var legendHtml;
		if(nomineeIndex >= 0)
		{
			var nominee = positions[nomineeIndex];
			legendHtml = BX.type.isNotEmptyString(nominee["legendType"]) && nominee["legendType"] === "html"
				? nominee["legend"] : BX.util.htmlspecialchars(nominee["legend"]);

			wrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-widget-rating-position" },
						children:
						[
							BX.create("DIV",
								{
									attrs: { className: "crm-widget-rating-position-inner" },
									children:
									[
										BX.create("SPAN",
											{
												attrs: { className: "crm-widget-rating-pl" },
												text: this.getMessage("nomineeRatingPosition").replace("#POSITION#", nominee["value"])
											}
										),
										BX.create("SPAN",
											{
												attrs: { className: "crm-widget-rating-result" },
												children:
												[
													BX.create("SPAN",
														{
															html: BX.util.htmlspecialchars(this.getMessage("legend")).replace("#LEGEND#", legendHtml)
														}
													)
												]
											}
										)
									]
								}
							)
						]
					}
				)
			);
		}

		var neighbours = BX.create("DIV", { attrs: { className: "crm-widget-rating-positions" } });
		wrapper.appendChild(neighbours);

		for(i = 0; i < positions.length; i++)
		{
			var pos = positions[i];
			var posId = parseInt(pos["id"]);
			if(posId === nomineeId)
			{
				continue;
			}

			legendHtml = BX.type.isNotEmptyString(pos["legendType"]) && pos["legendType"] === "html"
				? pos["legend"] : BX.util.htmlspecialchars(pos["legend"]);

			neighbours.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-widget-rating-position" },
						children:
						[
							this.getMessage("ratingPosition").replace("#POSITION#", pos["value"]),
							BX.create("SPAN",
								{
									attrs: { className: "crm-widget-rating-result" },
									children:
									[
										BX.create("SPAN",
											{
												html: BX.util.htmlspecialchars(this.getMessage("legend")).replace("#LEGEND#", legendHtml)
											}
										)
									]
								}
							)
						]
					}
				)
			);
		}
	};
	BX.CrmRatingWidget.prototype.ensureConfigEditorCreated = function()
	{
		if(!this._configEditor)
		{
			this._configEditor = BX.CrmRatingWidgetConfigEditor.create(
				this._id + "_config",
				{ widget: this, config: this._config, entityTypeName: this.getEntityTypeName() }
			);
		}
	};
	BX.CrmRatingWidget.prototype.getHeight = function()
	{
		return BX.CrmWidgetLayoutHeight.half;
	};
	BX.CrmRatingWidget.create = function(id, settings)
	{
		var self = new BX.CrmRatingWidget();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmNumericWidget) === "undefined")
{
	BX.CrmNumericWidget = function()
	{
		BX.CrmNumericWidget.superclass.constructor.apply(this);
		this._widowResizeHandler = null;
	};
	BX.extend(BX.CrmNumericWidget, BX.CrmWidget);
	BX.CrmNumericWidget.prototype.doInitialize = function()
	{
	};
	BX.CrmNumericWidget.prototype.getTitle = function()
	{
		var result = BX.type.isNotEmptyString(this._config["title"]) ? this._config["title"] : "";
		if(BX.type.isArray(this._data["items"])
			&& this._data["items"].length > 0
			&& BX.type.isNotEmptyString(this._data["items"][0]["title"]))
		{
			result =  this._data["items"][0]["title"];
		}
		return result;
	};
	BX.CrmNumericWidget.prototype.renderLayout = function()
	{
		var html, url, displayParams, scheme, content;
		var buttons = this.prepareButtons();
		this._settingButton = buttons["settings"];
		//this._editButton = buttons["edit"];
		var periodDescr = this.getPeriodDescription();

		if(this._layout === "tiled")
		{
			BX.addClass(this._wrapper, "crm-widget-total");

			var first, second, third;
			if(BX.type.isArray(this._data["items"]))
			{

				if(this._data["items"].length > 0)
				{
					first = this._data["items"][0];
				}
				if(this._data["items"].length > 1)
				{
					second = this._data["items"][1];
				}
				if(this._data["items"].length > 2)
				{
					third = this._data["items"][2];
				}
			}
			if(!first)
			{
				return;
			}

			displayParams = typeof(first["display"]) !== "undefined" ? first["display"] : {};
			scheme = BX.CrmWidgetColorScheme.getInfo(
				BX.type.isNotEmptyString(displayParams["colorScheme"]) ? displayParams["colorScheme"] : ""
			);

			var topContainer = BX.create("DIV", { attrs: { className: "crm-widget-total-top" } });
			if(scheme)
			{
				topContainer.style.backgroundColor = scheme["color"];
			}
			this._wrapper.appendChild(topContainer);

			this._headerWrapper = this.prepareHeader(
				BX.type.isNotEmptyString(first["title"]) ? first["title"] : this.getMessage("untitled"),
				buttons
			);
			topContainer.appendChild(this._headerWrapper);

			this._contentWrapper = BX.create("DIV",
				{ attrs: { id: this._prefix + "_" +  "content_wrapper", className: "crm-widget-content" } }
			);

			if(periodDescr !== "")
			{
				this._contentWrapper.appendChild(
					BX.create("SPAN",
						{
							attrs: { className: "crm-widget-content-period" },
							children: [ BX.create("SPAN", { html: this.getMessage("periodCaption") + ": " + periodDescr}) ]
						}
					)
				);
			}

			html = this.prepareItemHtml(first);
			url = BX.type.isNotEmptyString(first["url"]) ? first["url"] : "";
			content = url !== ""
				? BX.create("A", { attrs: { className: "crm-widget-content-text" }, props: { href: url }, html: html, style: { fontSize: "48px", lineHeight: "56px", opacity: 1 } })
				: BX.create("SPAN", { attrs: { className: "crm-widget-content-text" }, html: html, style: { fontSize: "48px", lineHeight: "56px", opacity: 1 } });

			this._contentWrapper.appendChild(
				BX.create("DIV",
					{ attrs: { className: "crm-widget-content-amt" }, children: [ content ] }
				)
			);

			topContainer.appendChild(this._contentWrapper);

			var bottomContainer = BX.create("DIV", { attrs: { className: "crm-widget-total-bottom" } });
			this._wrapper.appendChild(bottomContainer);

			var leftWrapper = BX.create("DIV", { attrs: { className: "crm-widget-total-left" } });
			bottomContainer.appendChild(leftWrapper);
			if(second)
			{
				displayParams = typeof(second["display"]) !== "undefined" ? second["display"] : {};
				scheme = BX.CrmWidgetColorScheme.getInfo(
					BX.type.isNotEmptyString(displayParams["colorScheme"]) ? displayParams["colorScheme"] : ""
				);

				if(scheme)
				{
					leftWrapper.style.backgroundColor = scheme["color"];
				}

				var leftHeaderWrapper = this.prepareHeader(
					BX.type.isNotEmptyString(second["title"]) ? second["title"] : this.getMessage("untitled")
				);
				leftWrapper.appendChild(leftHeaderWrapper);

				html = this.prepareItemHtml(second);
				url = BX.type.isNotEmptyString(second["url"]) ? second["url"] : "";
				content = url !== ""
					? BX.create("A", { attrs: { className: "crm-widget-content-text" }, props: { href: url }, html: html, style: { fontSize: "48px", lineHeight: "56px", opacity: 1 } })
					: BX.create("SPAN", { attrs: { className: "crm-widget-content-text" }, html: html, style: { fontSize: "48px", lineHeight: "56px", opacity: 1 } });

				var leftContentWrapper = BX.create("DIV",
					{
						attrs: { className: "crm-widget-content" },
						children:
						[
							BX.create("DIV",
								{ attrs: { className: "crm-widget-content-amt" }, children: [ content ] }
							)
						]
					}
				);
				leftWrapper.appendChild(leftContentWrapper);
			}

			var rightWrapper = BX.create("DIV", { attrs: { className: "crm-widget-total-right" } });
			bottomContainer.appendChild(rightWrapper);
			if(third)
			{
				displayParams = typeof(third["display"]) !== "undefined" ? third["display"] : {};
				scheme = BX.CrmWidgetColorScheme.getInfo(
					BX.type.isNotEmptyString(displayParams["colorScheme"]) ? displayParams["colorScheme"] : ""
				);

				if(scheme)
				{
					rightWrapper.style.backgroundColor = scheme["color"];
				}

				var rightHeaderWrapper = this.prepareHeader(
					BX.type.isNotEmptyString(third["title"]) ? third["title"] : this.getMessage("untitled")
				);
				rightWrapper.appendChild(rightHeaderWrapper);

				html = this.prepareItemHtml(third);
				url = BX.type.isNotEmptyString(third["url"]) ? third["url"] : "";
				content = url !== ""
					? BX.create("A", { attrs: { className: "crm-widget-content-text" }, props: { href: url }, html: html, style: { fontSize: "48px", lineHeight: "56px", opacity: 1 } })
					: BX.create("SPAN", { attrs: { className: "crm-widget-content-text" }, html: html, style: { fontSize: "48px", lineHeight: "56px", opacity: 1 } });

				var rightContentWrapper = BX.create("DIV",
					{
						attrs: { className: "crm-widget-content" },
						children:
						[
							BX.create("DIV",
								{ attrs: { className: "crm-widget-content-amt" }, children: [ content ] }
							)
						]
					}
				);
				rightWrapper.appendChild(rightContentWrapper);
			}
		}
		else
		{
			BX.addClass(this._wrapper, "crm-widget-number");

			var item = BX.type.isArray(this._data["items"]) && this._data["items"].length > 0
				? this._data["items"][0] : null;
			if(!item)
			{
				return;
			}

			displayParams = typeof(item["display"]) !== "undefined" ? item["display"] : {};
			scheme = BX.CrmWidgetColorScheme.getInfo(
				BX.type.isNotEmptyString(displayParams["colorScheme"]) ? displayParams["colorScheme"] : ""
			);
			if(scheme)
			{
				this._wrapper.style.backgroundColor = scheme["color"];
			}

			this._headerWrapper = this.prepareHeader(
				BX.type.isNotEmptyString(item["title"]) ? item["title"] : this.getMessage("untitled"),
				buttons
			);
			this._wrapper.appendChild(this._headerWrapper);
			this._contentWrapper = BX.create("DIV",
				{ attrs: { id: this._prefix + "_" +  "content_wrapper", className: "crm-widget-content" } }
			);
			this._wrapper.appendChild(this._contentWrapper);

			html = this.prepareItemHtml(item);
			url = BX.type.isNotEmptyString(item["url"]) ? item["url"] : "";
			content = url !== ""
				? BX.create("A", { attrs: { className: "crm-widget-content-text" }, props: { href: url }, html: html, style: { fontSize: "48px", lineHeight: "56px", opacity: 1 } })
				: BX.create("SPAN", { attrs: { className: "crm-widget-content-text" }, html: html, style: { fontSize: "48px", lineHeight: "56px", opacity: 1 } });

			if(periodDescr !== "")
			{
				this._contentWrapper.appendChild(
					BX.create("SPAN",
						{
							attrs: { className: "crm-widget-content-period" },
							children: [ BX.create("SPAN", { html: this.getMessage("periodCaption") + ": " + periodDescr}) ]
						}
					)
				);
			}

			this._contentWrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-widget-content-amt" },
						children: [ content ]
					}
				)
			);
		}

		this.ajustFontSize(this._wrapper.querySelectorAll(".crm-widget-content-text"));
		this._widowResizeHandler = BX.throttle(BX.delegate(this.onWidowResize, this), 300);
		BX.bind(window, "resize", this._widowResizeHandler);
	};
	BX.CrmNumericWidget.prototype.prepareItemHtml = function(item)
	{
		var html = "";
		if(BX.type.isNotEmptyString(item["html"]))
		{
			html = item["html"];
		}
		else if(BX.type.isNotEmptyString(item["text"]))
		{
			html = BX.util.htmlspecialchars(item["text"]);
		}
		else if(BX.type.isNotEmptyString(item["value"]))
		{
			var value = item["value"];

			var formatParams = typeof(item["format"]) !== "undefined" ? item["format"] : {};
			if(BX.type.isNotEmptyString(formatParams["isPercent"]) && formatParams["isPercent"] === "Y")
			{
				value += "%";
			}

			html = BX.util.htmlspecialchars(value);
		}

		return html;
	};
	BX.CrmNumericWidget.prototype.innerClearLayout = function()
	{
		BX.unbind(window, "resize", this._widowResizeHandler);
		this._widowResizeHandler = null;
	};
	BX.CrmNumericWidget.prototype.ensureConfigEditorCreated = function()
	{
		if(!this._configEditor)
		{
			this._configEditor = BX.CrmNumericWidgetConfigEditor.create(
				this._id + "_config",
				{ widget: this, config: this._config, entityTypeName: this.getEntityTypeName() }
			);
		}
	};
	BX.CrmNumericWidget.prototype.getHeight = function()
	{
		return this._layout === "tiled" ? BX.CrmWidgetLayoutHeight.full : BX.CrmWidgetLayoutHeight.half;
	};
	BX.CrmNumericWidget.prototype.onWidowResize = function(e)
	{
		if(this._hasLayout && this._wrapper)
		{
			this.ajustFontSize(this._wrapper.querySelectorAll(".crm-widget-content-text"));
		}
	};
	BX.CrmNumericWidget.prototype.ajustFontSize = function(nodeList)
	{
		var fontSize = 0;
		var mainFontSize = 0;
		var decrease = true;
		var increase = true;
		var maxFontSize = 0;

		if(!nodeList)
			return;

		for(var i=0; i< nodeList.length; i++)
		{
			fontSize = parseInt(BX.style(nodeList[i], 'font-size'));

			if(!maxFontSize)
				maxFontSize = 72;
			else
				maxFontSize = 53;

			decrease = nodeList[i].offsetWidth > (nodeList[i].parentNode.offsetWidth-20);
			increase = nodeList[i].offsetWidth < (nodeList[i].parentNode.offsetWidth-20);

			while(nodeList[i].offsetWidth > (nodeList[i].parentNode.offsetWidth-20) && decrease)
			{
				fontSize -=2;
				nodeList[i].style.fontSize = fontSize + 'px';
				nodeList[i].style.lineHeight = (fontSize + 8) + 'px';
				increase = false;
			}

			while(nodeList[i].offsetWidth < (nodeList[i].parentNode.offsetWidth-20) && fontSize<maxFontSize && increase)
			{
				fontSize +=2;
				nodeList[i].style.fontSize = fontSize + 'px';
				nodeList[i].style.lineHeight = (fontSize + 8) + 'px';
				decrease = false;
			}

			if(!mainFontSize && i>0)
				mainFontSize = fontSize;

			if(i>0)
				mainFontSize = Math.min(mainFontSize, fontSize)
		}

		for(var b=0; b<nodeList.length; b++)
		{
			nodeList[b].style.opacity = 1;

			if(b>0)
			{
				nodeList[b].style.fontSize = mainFontSize + 'px';
				nodeList[b].style.lineHeight = (mainFontSize + 8) + 'px';
			}

		}
	};
	BX.CrmNumericWidget.create = function(id, settings)
	{
		var self = new BX.CrmNumericWidget();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmWidgetConfigEditor) === "undefined")
{
	BX.CrmWidgetConfigEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._entityTypeName = "";
		this._widget = null;
		this._config = null;
		this._enableTitle = false;
		this._dlg = null;
		this._container = null;
		this._leadingWrapper = null;
		this._titleEditor = null;
		this._period = null;
	};
	BX.CrmWidgetConfigEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._entityTypeName = this.getSetting("entityTypeName", "");
			this._widget = this.getSetting("widget");
			if(!this._widget)
			{
				throw "BX.CrmWidgetConfigEditor: Could not find widget parameter.";
			}

			this._config = this.getSetting("config");
			if(!this._config)
			{
				throw "BX.CrmWidgetConfigEditor: Could not find config parameter.";
			}

			this._enableTitle = this.getSetting("enableTitle", false);

			this.doInitialize();
		},
		doInitialize: function()
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
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetConfigEditor.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		openDialog: function()
		{
			if(this._dlg)
			{
				return;
			}

			var dlgId = this._id;
			this._dlg = new BX.PopupWindow(
				dlgId,
				null,
				{
					autoHide: false,
					draggable: true,
					bindOptions: { forceBindPosition: false },
					closeByEsc: false,
					closeIcon: { top: "10px", right: "15px" },
					zIndex: 0,
					titleBar: { content: this.prepareDialogTitle() },
					content: this.prepareDialogContent(),
					buttons:
					[
						new BX.PopupWindowButton(
							{
								text: this.getMessage("dialogSaveButton"),
								className: "popup-window-button-accept",
								events: { click : BX.delegate(this.onDialogAcceptButtonClick, this) }
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text: this.getMessage("dialogCancelButton"),
								className: 'popup-window-button-link-cancel',
								events: { click : BX.delegate(this.onDialogCancelButtonClick, this) }
							}
						)
					],
					events:
					{
						onPopupShow: BX.delegate(this.onDialogShow, this),
						onPopupClose: BX.delegate(this.onDialogClose, this),
						onPopupDestroy: BX.delegate(this.onDialogDestroy, this)
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
		prepareDialogTitle: function()
		{
			return BX.create("SPAN",
				{
					attrs: { className: "bx-lists-dialog-title" },
					text: this.getMessage("dialogTitle")
				}
			);
		},
		prepareDialogContent: function()
		{
			this._container = BX.create("DIV", {});
			this._leadingWrapper = BX.create("DIV", { attrs: { className: "container-item" } });
			this._container.appendChild(this._leadingWrapper);

			if(this._enableTitle)
			{
				this._titleEditor = BX.CrmWidgetConfigTitleEditor.create(this._id,
					{ config: this._config, backgroundColor: "#b0b6bc", enableBackgroundColorChange: false }
				);
				this._titleEditor.prepareLayout(this._leadingWrapper);
			}

			this._period = BX.CrmWidgetConfigPeriodEditor.create(
				this._id + "_period",
				{
					isNested: true,
					config: BX.type.isPlainObject(this._config["filter"]) ? this._config["filter"] : {}
				}
			);
			this._period.prepareLayout(this._leadingWrapper);

			this.innerPrepareDialogContent(this._container);
			return this._container;
		},
		innerPrepareDialogContent: function(container)
		{
		},
		reset: function()
		{
		},
		getWidget: function()
		{
			return this._widget;
		},
		getConfig: function()
		{
			return this._config;
		},
		saveConfig: function()
		{
			this._period.saveConfig();
			if(this._enableTitle && this._titleEditor)
			{
				this._titleEditor.saveConfig();
				this._config["title"] = this._titleEditor.getTitle();
			}

			//Entity type name is required for widgets without data presets
			if(this._entityTypeName !== "")
			{
				this._config["entityTypeName"] = this._entityTypeName;
			}

			this._config["filter"] = this._period.getConfig();
			this.innerSaveConfig();
		},
		innerSaveConfig: function()
		{
		},
		onDialogShow: function()
		{
		},
		onDialogClose: function()
		{
			if(this._dlg)
			{
				this.reset();
				this._dlg.destroy();
			}
		},
		onDialogDestroy: function()
		{
			this._dlg = null;
		},
		onDialogAcceptButtonClick: function()
		{
			this._widget.processConfigSave();
			this.closeDialog();
		},
		onDialogCancelButtonClick: function()
		{
			this.closeDialog();
		}
	};
	if(typeof(BX.CrmWidgetConfigEditor.messages) === "undefined")
	{
		BX.CrmWidgetConfigEditor.messages = {};
	}
	BX.CrmWidgetConfigEditor.createSelect = function(settings, options)
	{
		var select = BX.create('SELECT', settings);
		this.setupSelectOptions(select, options);
		return select;
	};
	BX.CrmWidgetConfigEditor.setupSelectOptions = function(select, settings)
	{
		while (select.options.length > 0)
		{
			select.remove(0);
		}

		var currentGroup = null;
		var currentGroupName = "";

		for(var i = 0; i < settings.length; i++)
		{
			var setting = settings[i];

			var groupName = BX.type.isNotEmptyString(setting["group"]) ? setting["group"] : "";
			if(groupName !== "" && groupName !== currentGroupName)
			{
				currentGroupName = groupName;
				currentGroup = document.createElement("OPTGROUP");
				currentGroup.label = groupName;
				select.appendChild(currentGroup);
			}

			var value = BX.type.isNotEmptyString(setting['value']) ? setting['value'] : '';
			var text = BX.type.isNotEmptyString(setting['text']) ? setting['text'] : setting['value'];

			var option = new Option(text, value, false, false);

			var attrs = BX.type.isPlainObject(setting['attrs']) ? setting['attrs'] : null;
			if(attrs)
			{
				for(var k in attrs)
				{
					if(!attrs.hasOwnProperty(k))
					{
						continue;
					}

					option.setAttribute("data-" + k, attrs[k]);
				}
			}

			if(currentGroup)
			{
				currentGroup.appendChild(option);
			}
			else
			{
				if(!BX.browser.IsIE())
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
			}
		}
	};
	BX.CrmWidgetConfigEditor.create = function(id, settings)
	{
		var self = new BX.CrmWidgetConfigEditor();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmWidgetConfigPeriodEditor) === "undefined")
{
	BX.CrmWidgetConfigPeriodEditor = function()
	{
		this._id = "";
		this._settings = null;

		this._isNested = false;
		this._enableEmpty = true;

		this._config = null;
		this._period = "";
		this._curYear = 0;
		this._curMonth = 0;
		this._curQuarter = 0;
		this._year = 0;
		this._quarter = 0;
		this._month = 0;

		this._periodSelector = null;
		this._yearSelector = null;
		this._quarterSelector = null;
		this._monthSelector = null;

		this._yearSelectorWrapper = null;
		this._quarterSelectorWrapper = null;
		this._monthSelectorWrapper = null;

		this._periodChangeHandler = BX.delegate(this.onPeriodChange, this);
		this._yearChangeHandler = BX.delegate(this.onYearChange, this);
		this._quarterChangeHandler = BX.delegate(this.onQuarterChange, this);
		this._monthChangeHandler = BX.delegate(this.onMonthChange, this);
		this._changeNotifier = null;
	};
	BX.CrmWidgetConfigPeriodEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._isNested = this.getSetting("isNested", false);
			this._config = this.getSetting("config", {});
			this._period = BX.type.isNotEmptyString(this._config["periodType"])
				? this._config["periodType"].toUpperCase() : this.getDefaultPeriod();
			this._enableEmpty = BX.type.isBoolean(this._config["enableEmpty"])
				? this._config["enableEmpty"] : true;

			var d = new Date();
			this._curYear = d.getFullYear();
			this._curMonth = d.getMonth() + 1;
			this._curQuarter = this._curMonth >= 10 ? 4 : (this._curMonth >= 7 ? 3 : (this._curMonth >= 4 ? 2 : 1));

			this._year =  typeof(this._config["year"]) !== "undefined"
				? parseInt(this._config["year"]) : this._curYear;

			this._quarter =  typeof(this._config["quarter"]) !== "undefined"
				? parseInt(this._config["quarter"]) : this._curQuarter;

			this._month = typeof(this._config["month"]) !== "undefined"
				? parseInt(this._config["month"]) : this._curMonth;

			this._changeNotifier = BX.CrmNotifier.create(this);

			var controls = this.getSetting("controls");
			if(BX.type.isPlainObject(controls))
			{
				this._yearSelectorWrapper = BX(controls["yearWrap"]);
				this._quarterSelectorWrapper = BX(controls["quarterWrap"]);
				this._monthSelectorWrapper = BX(controls["monthWrap"]);


				this._periodSelector = BX(controls["period"]);
				BX.CrmWidgetConfigEditor.setupSelectOptions(
					this._periodSelector,
					this.preparePeriodListItems()
				);
				this._periodSelector.value = this._period;
				BX.bind(this._periodSelector, "change", this._periodChangeHandler);

				this._yearSelector = BX(controls["year"]);
				BX.CrmWidgetConfigEditor.setupSelectOptions(this._yearSelector, this.prepareYearListItems());
				this._yearSelector.value = this._year;
				BX.bind(this._yearSelector, "change", this._yearChangeHandler);

				this._quarterSelector = BX(controls["quarter"]);
				BX.CrmWidgetConfigEditor.setupSelectOptions(this._quarterSelector, this.prepareQuarterListItems());
				this._quarterSelector.value = this._quarter;
				BX.bind(this._quarterSelector, "change", this._quarterChangeHandler);

				this._monthSelector = BX(controls["month"]);
				BX.CrmWidgetConfigEditor.setupSelectOptions(this._monthSelector, this.prepareMonthListItems());
				this._monthSelector.value = this._month;
				BX.bind(this._monthSelector, "change", this._monthChangeHandler);

				this.adjust();
			}

			BX.onCustomEvent(window, "CrmWidgetConfigPeriodEditorCreate", [this]);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetConfigPeriodEditor.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		isNested: function()
		{
			return this._isNested;
		},
		getDefaultPeriod: function()
		{
			return this.getSetting("defaultPeriodType", BX.CrmWidgetFilterPeriod.undefined);
		},
		getPeriod: function()
		{
			return this._period;
		},
		setPeriod: function(period)
		{
			this._period = period;
			this._periodSelector.value = period;
			this.adjust();
			this._changeNotifier.notify();
		},
		getYear: function()
		{
			return this._year;
		},
		setYear: function(year)
		{
			this._year = year;
			this._yearSelector.value = year;
			this._changeNotifier.notify();
		},
		getQuarter: function()
		{
			return this._quarter;
		},
		setQuarter: function(quarter)
		{
			this._quarter = quarter;
			this._quarterSelector.value = quarter;
			this._changeNotifier.notify();
		},
		getMonth: function()
		{
			return this._month;
		},
		setMonth: function(month)
		{
			this._month = month;
			this._monthSelector.value = month;
			this._changeNotifier.notify();
		},
		addChangeListener: function(listener)
		{
			this._changeNotifier.addListener(listener);
		},
		removeChangeListener: function(listener)
		{
			this._changeNotifier.removeListener(listener);
		},
		prepareYearListItems: function()
		{
			var years = [];
			for(var y = (this._curYear - 20); y <= (this._curYear + 20); y++)
			{
				years.push({ value: y.toString(), text: y.toString() });
			}
			return years;
		},
		prepareQuarterListItems: function()
		{
			return([{ value: "1", text: "I" }, { value: "2", text: "II" }, { value: "3", text: "III" }, { value: "4", text: "IV" }]);
		},
		prepareMonthListItems: function()
		{
			var months = [];
			var monthNames = BX.CrmWidgetConfigPeriodEditor.getMonthNames();
			for(var m = 1; m <= 12; m++)
			{
				months.push({ value: m.toString(), text: monthNames[(m - 1)] });
			}

			return months;
		},
		preparePeriodListItems: function()
		{
			return (!this._isNested
				? BX.CrmWidgetFilterPeriod.prepareListItems({}, !this._enableEmpty)
				: (this._enableEmpty
					? BX.CrmWidgetFilterPeriod.prepareListItems({ "" : this.getMessage("accordingToFilter") }, false)
					: BX.CrmWidgetFilterPeriod.prepareListItems({}, true)));
		},
		prepareLayout: function(container)
		{
			this._periodSelector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._periodChangeHandler } },
				this.preparePeriodListItems()
			);
			this._periodSelector.value = this._period;


			this._yearSelector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._yearChangeHandler } },
				this.prepareYearListItems()
			);
			this._yearSelector.value = this._year;
			this._yearSelectorWrapper = BX.create("SPAN",
				{
					attrs: { className: "select-container select-container-period select-container-period-year" },
					children: [ this._yearSelector ]
				}
			);

			this._quarterSelector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._quarterChangeHandler } },
				this.prepareQuarterListItems()
			);
			this._quarterSelector.value = this._quarter;
			this._quarterSelectorWrapper = BX.create("SPAN",
				{
					attrs: { className: "select-container select-container-period select-container-period-quarter" },
					children: [ this._quarterSelector ]
				}
			);

			this._monthSelector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._monthChangeHandler } },
				this.prepareMonthListItems()
			);
			this._monthSelector.value = this._month;
			this._monthSelectorWrapper = BX.create("SPAN",
				{
					attrs: { className: "select-container select-container-period select-container-period-month" },
					children: [ this._monthSelector ]
				}
			);

			container.appendChild(
				BX.create(
					"DIV",
					{
						attrs: { className: "field-container field-container-period field-container-small field-container-left" },
						children:
						[
							BX.create("LABEL",
								{
									attrs: { className: "field-container-title" },
									text: this.getMessage("caption") + ":"
								}
							),
							BX.create(
								"SPAN",
								{
									attrs: { className: "select-container select-container-period" },
									children: [ this._periodSelector ]
								}
							),
							this._monthSelectorWrapper,
							this._quarterSelectorWrapper,
							this._yearSelectorWrapper
							//TODO: Implement help tip
							//, BX.create("SPAN", { attrs: { className: "field-help" }, text: "?" })
						]
					}
				)
			);

			this.adjust();
		},
		resetLayout: function()
		{
		},
		saveConfig: function()
		{
			this._config["periodType"] = this._period;

			if(this._period === BX.CrmWidgetFilterPeriod.year
				|| this._period === BX.CrmWidgetFilterPeriod.quarter
				|| this._period === BX.CrmWidgetFilterPeriod.month)
			{
				this._config["year"] = this._year;
			}

			if(this._period === BX.CrmWidgetFilterPeriod.quarter)
			{
				this._config["quarter"] = this._quarter;
			}

			if(this._period === BX.CrmWidgetFilterPeriod.month)
			{
				this._config["month"] = this._month;
			}
		},
		getConfig: function()
		{
			return this._config;
		},
		isEmpty: function()
		{
			return this._period === BX.CrmWidgetFilterPeriod.undefined;
		},
		getDescription: function()
		{
			var monthNames;
			if(this._period === BX.CrmWidgetFilterPeriod.undefined)
			{
				return "";
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.year)
			{
				return this.getMessage("yearDescription").replace(/#YEAR#/gi, this._year);
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.quarter)
			{
				var lastMonth = 3 * this._quarter;
				var firstMonth = lastMonth - 2;
				monthNames = BX.CrmWidgetConfigPeriodEditor.getMonthNames();
				return this.getMessage("quarterDescription").replace(/#YEAR#/gi, this._year).replace(/#FIRST_MONTH#/gi, monthNames[firstMonth - 1]).replace(/#LAST_MONTH#/gi, monthNames[lastMonth - 1]);
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.month)
			{
				monthNames = BX.CrmWidgetConfigPeriodEditor.getMonthNames();
				return this.getMessage("monthDescription").replace(/#YEAR#/gi, this._year).replace(/#MONTH#/gi, monthNames[this._month - 1]);
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.currentMonth)
			{
				return BX.CrmWidgetFilterPeriod.getDescription(BX.CrmWidgetFilterPeriod.currentMonth).toLowerCase();
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.currentQuarter)
			{
				return BX.CrmWidgetFilterPeriod.getDescription(BX.CrmWidgetFilterPeriod.currentQuarter).toLowerCase();
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.lastDays90)
			{
				return this.getMessage("lastDaysDescription").replace(/#DAYS#/gi, 90);
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.lastDays60)
			{
				return this.getMessage("lastDaysDescription").replace(/#DAYS#/gi, 60);
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.lastDays30)
			{
				return this.getMessage("lastDaysDescription").replace(/#DAYS#/gi, 30);
			}
			else if(this._period === BX.CrmWidgetFilterPeriod.lastDays7)
			{
				return this.getMessage("lastDaysDescription").replace(/#DAYS#/gi, 7);
			}
			return "";
		},
		adjust: function()
		{
			this._yearSelectorWrapper.style.display = (this._period === BX.CrmWidgetFilterPeriod.year
				|| this._period === BX.CrmWidgetFilterPeriod.quarter
				|| this._period === BX.CrmWidgetFilterPeriod.month)
				? "" : "none";
			this._quarterSelectorWrapper.style.display = this._period === BX.CrmWidgetFilterPeriod.quarter ? "" : "none";
			this._monthSelectorWrapper.style.display = this._period === BX.CrmWidgetFilterPeriod.month ? "" : "none";
		},
		onPeriodChange: function(e)
		{
			this._period = this._periodSelector.value;
			this.adjust();
			this._changeNotifier.notify();
		},
		onYearChange: function(e)
		{
			this._year = parseInt(this._yearSelector.value);
			this._changeNotifier.notify();
		},
		onQuarterChange: function(e)
		{
			this._quarter = parseInt(this._quarterSelector.value);
			this._changeNotifier.notify();
		},
		onMonthChange: function(e)
		{
			this._month = parseInt(this._monthSelector.value);
			this._changeNotifier.notify();
		}
	};
	BX.CrmWidgetConfigPeriodEditor.monthNames = null;
	BX.CrmWidgetConfigPeriodEditor.getMonthNames = function()
	{
		if(!this.monthNames)
		{
			this.monthNames = [];
			for(var m = 1; m <= 12; m++)
			{
				this.monthNames.push(BX.message["MONTH_" + m.toString()]);
			}
		}
		return this.monthNames;
	};
	if(typeof(BX.CrmWidgetConfigPeriodEditor.messages) === "undefined")
	{
		BX.CrmWidgetConfigPeriodEditor.messages = {};
	}
	BX.CrmWidgetConfigPeriodEditor.items = {};
	BX.CrmWidgetConfigPeriodEditor.create = function(id, settings)
	{
		var self = new BX.CrmWidgetConfigPeriodEditor();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
if(typeof(BX.CrmWidgetConfigTitleEditor) === "undefined")
{
	BX.CrmWidgetConfigTitleEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._config = null;
		this._mode = BX.CrmWidgetConfigControlMode.undifined;

		this._title = "";
		this._titleBackgroundColor = "";
		this._letter = "";
		this._schemeId = "";

		this._titleInput = null;
		this._titleEditButton = null;
		this._titleSaveButton = null;

		this._schemeSelectButton = null;
		this._enableScemeChange = true;

		this._deleteButton = null;
		this._enableDeletion = false;

		this._wrapper = null;
		this._titleViewContainer = null;
		this._titleEditContainer = null;
		this._titleWrapper = null;

		this._inputKeyDownHandler = BX.delegate(this.onInputKeyDown, this);
		this._titleEitButtonClickHandler = BX.delegate(this.onTitleEditButtonClick, this);
		this._titleSaveButtonClickHandler = BX.delegate(this.onTitleSaveButtonClick, this);
		this._schemeSelectButtonCkickHandler = BX.delegate(this.onSchemeSelectButtonClick, this);
		this._isSchemeMenuShown = false;
		this._schemeMenuId = "";
		this._schemeMenu = null;
		this._schemeMenuHandler = BX.delegate(this.onSchemeMenuItemClick, this);
		this._schemeChangeNotifier = null;

		this._deleteButtonCkickHandler = BX.delegate(this.onDeleteButtonClick, this);
		this._deletionNotifier = null;
	};
	BX.CrmWidgetConfigTitleEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._config = this.getSetting("config", {});

			this._title = BX.type.isNotEmptyString(this._config["title"]) ? this._config["title"] : "";
			this._letter = this.getSetting("letter", "");
			this._titleBackgroundColor = this.getSetting("backgroundColor", "#22d02c");
			if(BX.type.isPlainObject(this._config["display"])
				&& BX.type.isNotEmptyString(this._config["display"]["colorScheme"]))
			{
				this._schemeId = this._config["display"]["colorScheme"];
				var scheme = BX.CrmWidgetColorScheme.getInfo(this._schemeId);
				if(scheme)
				{
					this._titleBackgroundColor = scheme["color"];
				}
			}

			this._mode = BX.CrmWidgetConfigControlMode.view;

			this._enableScemeChange = this.getSetting("enableBackgroundColorChange", true);
			this._schemeChangeNotifier = BX.CrmNotifier.create(this);

			this._enableDeletion = this.getSetting("enableDeletion", false);
			this._deletionNotifier = BX.CrmNotifier.create(this);
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetConfigTitleEditor.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getId: function()
		{
			return this._id;
		},
		getTitle: function()
		{
			return this._title;
		},
		setTitle: function(title)
		{
			this._title = title;
			if(this._titleInput)
			{
				this._titleInput.value = title;
			}
			if(this._titleViewContainer)
			{
				this._titleViewContainer.innerHTML = BX.util.htmlspecialchars(title);
			}
		},
		getName: function()
		{
			return this._name;
		},
		getTitleBackgroundColor: function()
		{
			return this._titleBackgroundColor;
		},
		prepareLayout: function(container)
		{
			this._titleEditButton = BX.create("SPAN",
				{
					attrs: { className: "field-title-icon-edit" },
					events: { "click": this._titleEitButtonClickHandler }
				}
			);
			this._titleSaveButton = BX.create("SPAN",
				{
					attrs: { className: "field-title-icon-ready" },
					events: { "click": this._titleSaveButtonClickHandler },
					style: { display: "none" }
				}
			);

			var title = this._title !== "" ? this._title : this.getMessage("untitled");
			this._titleViewContainer = BX.create("SPAN", { attrs: { className: "title-name" }, text: title });
			this._titleInput = BX.create("INPUT", { props: { type: "text", placeholder: this.getTitle("placeholder") } });
			this._titleEditContainer = BX.create("SPAN",
				{
					attrs: { className: "fiedl-title-input-container" },
					children: [ this._titleInput ],
					style: { display: "none" }
				}
			);

			var titleControls = [];

			if(this._letter !== "")
			{
				titleControls.push(BX.create("SPAN", {attrs: {className: "field-title-letter"}, text: this._letter}));
			}
			titleControls.push(this._titleViewContainer);
			titleControls.push(this._titleEditContainer);

			this._titleWrapper = BX.create("SPAN",
				{
					attrs: { className: "field-title-title-inner" },
					children:
					[
						BX.create("SPAN", { attrs: { className: "field-title-name" }, children: titleControls }),
						this._titleEditButton,
						this._titleSaveButton
					]
				}
			);

			var panel = BX.create("DIV",
				{ attrs: { className: "field-title-panel" } }
			);
			if(this._enableScemeChange)
			{
				this._schemeSelectButton = BX.create("DIV",
					{
						attrs: {className: "field-title-panel-button field-title-button-bucket"},
						events: {"click": this._schemeSelectButtonCkickHandler}
					}
				);
				panel.appendChild(this._schemeSelectButton);
			}
			if(this._enableDeletion)
			{
				this._deleteButton = BX.create("DIV",
					{
						attrs: {className: "field-title-panel-button field-title-button-close"},
						events: {"click": this._deleteButtonCkickHandler}
					}
				);
				panel.appendChild(this._deleteButton);
			}

			this._wrapper = BX.create("DIV",
				{
					attrs: { className: "field-title" },
					style: { backgroundColor: this._titleBackgroundColor},
					children:
					[
						panel,
						BX.create("SPAN",
							{ attrs: { className: "field-title-title" }, children: [ this._titleWrapper ] }
						)
					]
				}
			);

			container.appendChild(this._wrapper);
			return container;
		},
		resetLayout: function()
		{
			if(this._schemeMenu && this._schemeMenu.popupWindow)
			{
				this._schemeMenu.popupWindow.close();
			}

			if(this._titleEditButton)
			{
				BX.unbind(this._titleEditButton, "click", this._titleEitButtonClickHandler);
				this._titleEditButton = null;
			}

			if(this._titleSaveButton)
			{
				BX.unbind(this._titleSaveButton, "click", this._titleSaveButtonClickHandler);
				this._titleSaveButton = null;
			}

			if(this._schemeSelectButton)
			{
				BX.unbind(this._schemeSelectButton, "click", this._schemeSelectButtonCkickHandler);
				this._schemeSelectButton = null;
			}

			this._titleInput = null;
			this._titleWrapper = null;
			this._titleViewContainer = null;
			this._titleEditContainer = null;

			BX.cleanNode(this._wrapper, true);
			this._wrapper = null;
		},
		getConfig: function()
		{
			return this._config;
		},
		getColorSchemeId: function()
		{
			return this._schemeId;
		},
		saveConfig: function()
		{
			if(this._mode === BX.CrmWidgetConfigControlMode.edit)
			{
				this.saveInputValue();
				this.switchMode(BX.CrmWidgetConfigControlMode.view);
			}

			this._config["title"] = this._title;

			if(this._schemeId !== "")
			{
				if(typeof(this._config["display"]) === "undefined")
				{
					this._config["display"] = {};
				}
				this._config["display"]["colorScheme"] = this._schemeId;
			}
			else if(typeof(this._config["display"]) !== "undefined")
			{
				delete this._config["display"]["colorScheme"];
			}
		},
		switchMode: function(mode)
		{
			if(this._mode === mode)
			{
				return;
			}

			if(mode === BX.CrmWidgetConfigControlMode.view)
			{
				BX.unbind(this._titleInput, "keydown", this._inputKeyDownHandler);
				this._titleInput.blur();

				BX.removeClass(this._titleWrapper, "input-container-edit");
				this._titleSaveButton.style.display = "none";
				this._titleEditContainer.style.display = "none";
				this._titleEditButton.style.display = "";
				this._titleViewContainer.style.display = "";
			}
			else if(mode === BX.CrmWidgetConfigControlMode.edit)
			{
				BX.addClass(this._titleWrapper, "input-container-edit");
				this._titleEditButton.style.display = "none";
				this._titleViewContainer.style.display = "none";
				this._titleSaveButton.style.display = "";
				this._titleEditContainer.style.display = "";

				BX.bind(this._titleInput, "keydown", this._inputKeyDownHandler);
				this._titleInput.focus();
			}
			this._mode = mode;
		},
		addColorSchemeChangeListener: function(listener)
		{
			this._schemeChangeNotifier.addListener(listener);
		},
		removeColorSchemeChangeListener: function(listener)
		{
			this._schemeChangeNotifier.removeListener(listener);
		},
		addDeletionListener: function(listener)
		{
			this._deletionNotifier.addListener(listener);
		},
		removeDeletionListener: function(listener)
		{
			this._deletionNotifier.removeListener(listener);
		},
		onTitleEditButtonClick: function(e)
		{
			this.initInputValue();
			this.switchMode(BX.CrmWidgetConfigControlMode.edit);
		},
		onTitleSaveButtonClick: function(e)
		{
			this.saveInputValue();
			this.switchMode(BX.CrmWidgetConfigControlMode.view);
		},
		saveInputValue: function()
		{
			this._title = this._titleInput.value;
			this._titleViewContainer.innerHTML = this._title !== ""
				? BX.util.htmlspecialchars(this._title) : this.getMessage("untitled");
		},
		initInputValue: function()
		{
			this._titleInput.value = this._title;
		},
		onInputKeyDown: function(e)
		{
			if(this._mode !== BX.CrmWidgetConfigControlMode.edit)
			{
				return;
			}

			e = e || window.event;
			if(e.keyCode === 13)
			{
				this.saveInputValue();
				this.switchMode(BX.CrmWidgetConfigControlMode.view);
			}
			else if(e.keyCode === 27)
			{
				this.switchMode(BX.CrmWidgetConfigControlMode.view);
			}
		},
		onSchemeSelectButtonClick: function(e)
		{
			if(!this._isSchemeMenuShown)
			{
				this.openSchemeMenu();
			}
			else
			{
				this.closeSchemeMenu();
			}
		},
		onDeleteButtonClick: function(e)
		{
			this._deletionNotifier.notify();
		},
		openSchemeMenu: function()
		{
			if(this._isSchemeMenuShown)
			{
				return;
			}

			this._schemeMenuId = this._id + "_scheme_menu";
			if(typeof(BX.PopupMenu.Data[this._schemeMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._schemeMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._schemeMenuId];
			}

			this._schemeMenu = BX.PopupMenu.create(
				this._schemeMenuId,
				this._schemeSelectButton,
				BX.CrmWidgetColorScheme.prepareMenuItems(this._schemeMenuHandler),
				{
					autoHide: true,
					offsetLeft: -21,
					offsetTop: -3,
					angle:
					{
						position: "top",
						offset: 42
					},
					events:
					{
						onPopupClose : BX.delegate(this.onSchemeMenuClose, this)
					}
				}
		   );
		   this._schemeMenu.popupWindow.show();
		   this._isSchemeMenuShown = true;
		},
		closeSchemeMenu: function()
		{
			if(this._schemeMenu && this._schemeMenu.popupWindow)
			{
				this._schemeMenu.popupWindow.close();
			}
		},
		onSchemeMenuClose: function()
		{
			this._schemeMenu = null;
			if(typeof(BX.PopupMenu.Data[this._schemeMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._schemeMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._schemeMenuId];
			}
			this._isSchemeMenuShown = false;
		},
		onSchemeMenuItemClick: function(e, item)
		{
			this._schemeId = item.id;
			var scheme = BX.CrmWidgetColorScheme.getInfo(this._schemeId);
			if(scheme && this._wrapper)
			{
				this._wrapper.style.backgroundColor = this._titleBackgroundColor = scheme["color"];
			}
			this.closeSchemeMenu();

			this._schemeChangeNotifier.notify();
		}
	};
	if(typeof(BX.CrmWidgetConfigTitleEditor.messages) === "undefined")
	{
		BX.CrmWidgetConfigTitleEditor.messages = {};
	}
	BX.CrmWidgetConfigTitleEditor.create = function(id, settings)
	{
		var self = new BX.CrmWidgetConfigTitleEditor();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmWidgetConfigPresetEditor) === "undefined")
{
	BX.CrmWidgetConfigPresetEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._config = null;

		this._entityTypeName = "";
		this._contextId = "";
		this._enableContextChange = true;
		this._semanticId = "";
		this._categoryName = "";
		this._preset = null;

		this._typeSelector = null;
		this._enableSemanticsChange = true;

		this._presetSelector = null;
		this._contextSwitches = {};

		this._contextChangeHandler = BX.delegate(this.onContextChange, this);
		this._typeChangeHandler = BX.delegate(this.onTypeChange, this);
		this._presetChangeHandler = BX.delegate(this.onPresetChange, this);
		this._presetChangeCallback = null;

		this._container = null;
		this._semanticsWrapper = null;
		this._presetWrapper = null;
	};

	BX.CrmWidgetConfigPresetEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._config = this.getSetting("config", {});

			this._preset = BX.CrmWidgetDataPreset.getItem(
				BX.type.isNotEmptyString(this._config["dataPreset"]) ? this._config["dataPreset"] : this._config["name"]
			);

			this._entityTypeName = this._preset &&  BX.type.isNotEmptyString(this._preset["entity"])
				? this._preset["entity"] : this.getSetting("entityTypeName", "");

			var filter = BX.type.isPlainObject(this._config["filter"]) ? this._config["filter"] : {};
			this._semanticId = BX.type.isNotEmptyString(filter["semanticID"]) ? filter["semanticID"] : "";

			this._contextId = this.getSetting("context", BX.CrmWidgetDataContext.undefined);
			if(this._contextId === BX.CrmWidgetDataContext.undefined)
			{
				this._contextId = this._preset ? this._preset["context"] : BX.CrmWidgetDataContext.entity;
			}

			this._categoryName =  this.getSetting("category", "");
			if(this._categoryName === "")
			{
				this._categoryName = this._preset && BX.type.isNotEmptyString(this._preset["category"])
					? this._preset["category"] : "";
			}

			this._presetChangeCallback = this.getSetting("presetChange", null);
			this._enableContextChange = this.getSetting("enableContextChange", true);
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetConfigPresetEditor.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getId: function()
		{
			return this._id;
		},
		prepareLayout: function(container, layout)
		{
			this._container = container;

			//region Creation of data context selector
			var contextContainer = null;
			if(this._enableContextChange)
			{
				contextContainer = BX.create("SPAN", { attrs: { className: "radiobox-container" } });
				var contextInfos = BX.CrmWidgetDataContext.descriptions;
				for(var contextId in contextInfos)
				{
					if(!contextInfos.hasOwnProperty(contextId) || contextId === BX.CrmWidgetDataContext.undefined)
					{
						continue;
					}

					var contextTitle = contextInfos[contextId];
					var inputName = this.getId() + "_data_context";
					var inputId = inputName + "_" + contextId.toLowerCase();
					this._contextSwitches[contextId] = BX.create("INPUT",
						{
							props: { type: "radio", id: inputId, name: inputName },
							events: { "click": this._contextChangeHandler }
						}
					);

					if(this._contextId === contextId)
					{
						this._contextSwitches[contextId].checked = true;
					}
					contextContainer.appendChild(this._contextSwitches[contextId]);
					contextContainer.appendChild(BX.create("LABEL", { attrs: { "for": inputId }, text: contextTitle }));
				}

			}
			//endregion

			//region Creation of semantics and category selector
			var semanticsCaption = BX.CrmPhaseSemantics.getSelectorTitle(this._entityTypeName);
			if(semanticsCaption === "")
			{
				semanticsCaption = this.getMessage("semanticsCaption");
			}

			var typeItems = [{ value: "", text: this.getMessage("notSelected") }];

			var semanticItems = BX.CrmPhaseSemantics.prepareListItems(this._entityTypeName);
			var semanticGroupTitle = BX.CrmPhaseSemantics.getGroupTitle(this._entityTypeName);
			for(var i = 0; i < semanticItems.length; i++)
			{
				var semanticItem = semanticItems[i];
				if(semanticItem["value"] !== "")
				{
					semanticItem["attrs"] = { context: "semantics" };
					semanticItem["group"] = semanticGroupTitle;
					typeItems.push(semanticItem);
				}
			}

			var categoryItems = BX.CrmWidgetDataCategory.prepareListItems(this._entityTypeName);
			for(var j = 0; j < categoryItems.length; j++)
			{
				var categoryItem = categoryItems[j];
				if(categoryItem["value"] !== "")
				{
					categoryItem["attrs"] = { context: "category" };
					categoryItem["group"] = BX.CrmWidgetDataCategory.groupTitle;
					typeItems.push(categoryItem);
				}
			}

			this._typeSelector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._typeChangeHandler } },
				typeItems
			);

			if(this._semanticId !== "")
			{
				this._typeSelector.value = this._semanticId;
			}
			else if(this._categoryName !== "")
			{
				this._typeSelector.value = this._categoryName;
			}
			//endregion

			//region Creation of preset selector
			this._presetSelector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._presetChangeHandler } },
				BX.CrmWidgetDataPreset.prepareListItems(this._entityTypeName, this._categoryName, this._contextId)
			);
			if(this._preset)
			{
				this._presetSelector.value = this._preset["name"];
			}
			//endregion

			if(!BX.type.isNotEmptyString(layout))
			{
				layout = "";
			}

			if(layout === "wide")
			{

				this._semanticsWrapper = BX.create("DIV",
					{
						attrs: { className: "field-container field-container-left" },
						children:
						[
							BX.create("LABEL",
								{
									attrs: { className: "field-container-title" },
									text: semanticsCaption + ":"
								}
							),
							BX.create("SPAN",
								{
									attrs: { className: "select-container" },
									children: [ this._typeSelector ]
								}
							)
							//TODO: Implement help tip
							//,BX.create("SPAN", { attrs: { className: "field-help" }, text: "?" })
						]
					}
				);
				container.appendChild(this._semanticsWrapper);

				this._presetWrapper = BX.create("DIV",
					{
						attrs: { className: "field-container field-container-right" },
						children:
						[
							BX.create("LABEL",
								{
									attrs: { className: "field-container-title" },
									text: this.getMessage("nameCaption") + ":"
								}
							),
							BX.create("SPAN",
								{
									attrs: { className: "select-container" },
									children: [ this._presetSelector ]
								}
							)
						]
					}
				);
				container.appendChild(this._presetWrapper);

				if(this._enableContextChange)
				{
					container.appendChild(
						BX.create("DIV",
							{
								attrs: { className: "field-container field-container-left" },
								children: [ contextContainer ]
							}
						)
					);
				}
			}
			else
			{
				this._semanticsWrapper = BX.create("DIV",
					{
						attrs: { className: "field-container" },
						children:
						[
							BX.create("LABEL", { attrs: { className: "field-container-title" }, text: semanticsCaption + ":" }),
							BX.create("SPAN",
								{
									attrs: { className: "select-container" },
									children: [ this._typeSelector ]
								}
							)
							//TODO: Implement help tip
							//,BX.create("SPAN", { attrs: { className: "field-help" }, text: "?" })
						]
					}
				);
				container.appendChild(this._semanticsWrapper);

				if(this._enableContextChange)
				{
					container.appendChild(
						BX.create("DIV",
							{
								attrs: { className: "field-container" },
								children: [ contextContainer ]
							}
						)
					);
				}

				this._presetWrapper = BX.create("DIV",
					{
						attrs: { className: "field-container" },
						children:
						[
							BX.create("LABEL", { attrs: { className: "field-container-title" }, text: this.getMessage("nameCaption") + ":" }),
							BX.create("SPAN",
								{
									attrs: { className: "select-container" },
									children: [ this._presetSelector ]
								}
							)
						]
					}
				);
				container.appendChild(this._presetWrapper);
			}
		},
		resetLayout: function()
		{
			if(this._presetSelector)
			{
				BX.unbind(this._presetSelector, "change", this._presetChangeHandler);
				this._presetSelector = null;
			}

			if(this._typeSelector)
			{
				BX.unbind(this._typeSelector, "change", this._typeChangeHandler);
				this._typeSelector = null;
			}

			for(var k in this._contextSwitches)
			{
				if(!this._contextSwitches.hasOwnProperty(k))
				{
					continue;
				}

				BX.unbind(this._contextSwitches[k], "click", this._contextChangeHandler);
			}
			this._contextSwitches = {};

			this._semanticsWrapper = this._presetWrapper = null;
			BX.cleanNode(this._container, false);
		},
		getContextId: function()
		{
			return this._preset ? this._preset["context"] : this._contextId;
		},
		setContextId: function(contextId)
		{
			if(this._contextId === contextId)
			{
				return;
			}

			this._contextId = contextId;
			BX.CrmWidgetConfigEditor.setupSelectOptions(
				this._presetSelector,
				BX.CrmWidgetDataPreset.prepareListItems(this._entityTypeName, this._categoryName, this._contextId)
			);
		},
		saveConfig: function()
		{
			if(this._semanticId !== BX.CrmPhaseSemantics.undefined)
			{
				if(!BX.type.isPlainObject(this._config["filter"]))
				{
					this._config["filter"] = {};
				}

				this._config["filter"]["semanticID"] = this._semanticId;
			}
			else if(BX.type.isPlainObject(this._config["filter"]))
			{
				delete this._config["filter"]["semanticID"];
			}

			if(this._preset)
			{
				if(this._config["title"] === "")
				{
					this._config["title"] = this._preset["title"];
				}

				this._config["dataPreset"] = this._preset["name"];
				this._config["dataSource"] = this._preset["source"];
				this._config["select"] = this._preset["select"];

				var context = this.getContextId();

				if(context === BX.CrmWidgetDataContext.fund)
				{
					if(!BX.type.isPlainObject(this._config["format"]))
					{
						this._config["format"] = {};
					}
					this._config["format"]["isCurrency"] = "Y";
					this._config["format"]["enableDecimals"] = "N";
				}
				else if(BX.type.isPlainObject(this._config["format"]))
				{
					delete this._config["format"]["isCurrency"];
					delete this._config["format"]["enableDecimals"];
				}

				if(context === BX.CrmWidgetDataContext.percent)
				{
					if(!BX.type.isPlainObject(this._config["format"]))
					{
						this._config["format"] = {};
					}
					this._config["format"]["isPercent"] = "Y";
				}
				else if(BX.type.isPlainObject(this._config["format"]))
				{
					delete this._config["format"]["isPercent"];
				}
			}
		},
		getConfig: function()
		{
			return this._config;
		},
		onContextChange: function(e)
		{
			if(this._contextId !== BX.CrmWidgetDataContext.fund
				&& this._contextSwitches[BX.CrmWidgetDataContext.fund].checked)
			{
				this.setContextId(BX.CrmWidgetDataContext.fund);
			}
			else if(this._contextId !== BX.CrmWidgetDataContext.entity
				&& this._contextSwitches[BX.CrmWidgetDataContext.entity].checked)
			{
				this.setContextId(BX.CrmWidgetDataContext.entity);
			}
			else if(this._contextId !== BX.CrmWidgetDataContext.percent
				&& this._contextSwitches[BX.CrmWidgetDataContext.percent].checked)
			{
				this.setContextId(BX.CrmWidgetDataContext.percent);
			}
		},
		onTypeChange: function(e)
		{
			var selectedIndex = this._typeSelector.selectedIndex;

			var selectedOption = selectedIndex >= 0 ? this._typeSelector.options[selectedIndex] : null;
			if(!selectedOption)
			{
				this._semanticId =  BX.CrmPhaseSemantics.undefined;
				this._categoryName = "";
			}
			else
			{
				var context = selectedOption.getAttribute("data-context", "");
				if(context === "category")
				{
					this._semanticId =  BX.CrmPhaseSemantics.undefined;
					this._categoryName = selectedOption.value;
				}
				else
				{
					this._semanticId = selectedOption.value;
					this._categoryName = "";
				}
			}

			var presetName = this._presetSelector.value;
			BX.CrmWidgetConfigEditor.setupSelectOptions(
				this._presetSelector,
				BX.CrmWidgetDataPreset.prepareListItems(this._entityTypeName, this._categoryName, this._contextId)
			);

			for(var i = 0; i < this._presetSelector.options.length; i++)
			{
				if(this._presetSelector.options[i].value === presetName)
				{
					this._presetSelector.value = presetName;
					break;
				}
			}
		},
		onPresetChange: function(e)
		{
			this._preset = BX.CrmWidgetDataPreset.getItem(this._presetSelector.value);
			if(BX.type.isFunction(this._presetChangeCallback))
			{
				this._presetChangeCallback(this, this._preset);
			}
		}
	};
	if(typeof(BX.CrmWidgetConfigPresetEditor.messages) === "undefined")
	{
		BX.CrmWidgetConfigPresetEditor.messages = {};
	}
	BX.CrmWidgetConfigPresetEditor.create = function(id, settings)
	{
		var self = new BX.CrmWidgetConfigPresetEditor();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmWidgetConfigGroupingEditor) === "undefined")
{
	BX.CrmWidgetConfigGroupingEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._config = null;
		this._control = null;
		this._grouping = "";

		this._groupingSelector = null;
		this._groupingChangeHandler = BX.delegate(this.onGroupingChange, this);
	};
	BX.CrmWidgetConfigGroupingEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._control = this.getSetting("control", null);
			if(!this._control)
			{
				throw "BX.CrmWidgetConfigGroupingEditor: Could not find 'control' parameter.";
			}

			this._config = this.getSetting("config", {});
			this._grouping = BX.type.isNotEmptyString(this._config["group"])
				? this._config["group"].toUpperCase() : this._control.getDefault();
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetConfigGroupingEditor.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getGrouping: function()
		{
			return this._grouping;
		},
		prepareLayout: function(container, position)
		{
			this._groupingSelector = BX.CrmWidgetConfigEditor.createSelect(
				{ events: { "change": this._groupingChangeHandler } },
				this._control.prepareListItems()
			);
			this._groupingSelector.value = this._grouping;

			var wrapperClassName = "field-container";
			if(BX.type.isNotEmptyString(position))
			{
				if(position === "left")
				{
					wrapperClassName += " field-container-left";
				}
				else if(position === "right")
				{
					wrapperClassName += " field-container-right";
				}
			}

			container.appendChild(
				BX.create(
					"DIV",
					{
						attrs: { className: wrapperClassName },
						children:
						[
							BX.create("LABEL",
								{
									attrs: { className: "field-container-title" },
									text: this.getMessage("caption") + ":"
								}
							),
							BX.create(
								"SPAN",
								{
									attrs: { className: "select-container select-container-period" },
									children: [ this._groupingSelector ]
								}
							)
							//TODO: Implement help tip
							//,BX.create("SPAN", { attrs: { className: "field-help" }, text: "?" })
						]
					}
				)
			);
		},
		resetLayout: function()
		{
			if(this._groupingSelector)
			{
				BX.unbind(this._groupingSelector, "change", this._groupingChangeHandler);
				this._groupingSelector = null;
			}
		},
		saveConfig: function()
		{
			this._config["group"] = this._grouping;
		},
		getConfig: function()
		{
			return this._config;
		},
		onGroupingChange: function(e)
		{
			this._grouping = this._groupingSelector.value;
		}
	};
	if(typeof(BX.CrmWidgetConfigGroupingEditor.messages) === "undefined")
	{
		BX.CrmWidgetConfigGroupingEditor.messages = {};
	}
	BX.CrmWidgetConfigGroupingEditor.create = function(id, settings)
	{
		var self = new BX.CrmWidgetConfigGroupingEditor();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmWidgetConfigContextEditor) === "undefined")
{
	BX.CrmWidgetConfigContextEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._config = null;
		this._contextId = BX.CrmWidgetDataContext.undefined;
		this._contextSwitches = {};
		this._contextChangeHandler = BX.delegate(this.onContextChange, this);
		this._contextChangeCallback = null;
	};
	BX.CrmWidgetConfigContextEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._config = this.getSetting("config", {});

			this._contextId = BX.type.isNotEmptyString(this._config["context"])
				? this._config["context"] : BX.CrmWidgetDataContext.entity;

			this._contextChangeCallback = this.getSetting("contextChange", null);
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		prepareLayout: function(container, position)
		{
			var contextWrapper = BX.create("SPAN", { attrs: { className: "radiobox-container" } });
			var contextInfos = BX.CrmWidgetDataContext.descriptions;
			for(var contextId in contextInfos)
			{
				if(!contextInfos.hasOwnProperty(contextId) || contextId === BX.CrmWidgetDataContext.undefined)
				{
					continue;
				}

				var contextTitle = contextInfos[contextId];
				var inputName = this.getId() + "_data_context";
				var inputId = inputName + "_" + contextId.toLowerCase();
				this._contextSwitches[contextId] = BX.create("INPUT",
					{
						props: { type: "radio", id: inputId, name: inputName },
						events: { "click": this._contextChangeHandler }
					}
				);

				if(this._contextId === contextId)
				{
					this._contextSwitches[contextId].checked = true;
				}
				contextWrapper.appendChild(this._contextSwitches[contextId]);
				contextWrapper.appendChild(BX.create("LABEL", { attrs: { "for": inputId }, text: contextTitle }));
			}

			var wrapperClassName = "field-container";
			if(BX.type.isNotEmptyString(position))
			{
				if(position === "left")
				{
					wrapperClassName += " field-container-left";
				}
				else if(position === "right")
				{
					wrapperClassName += " field-container-right";
				}
			}

			container.appendChild(
				BX.create("DIV", { attrs: { className: wrapperClassName }, children: [ contextWrapper ] })
			);
		},
		resetLayout: function()
		{
			for(var k in this._contextSwitches)
			{
				if(!this._contextSwitches.hasOwnProperty(k))
				{
					continue;
				}

				BX.unbind(this._contextSwitches[k], "click", this._contextChangeHandler);
			}
			this._contextSwitches = {};
		},
		getContextId: function()
		{
			return this._contextId;
		},
		setContextId: function(contextId)
		{
			if(this._contextId === contextId)
			{
				return;
			}

			this._contextId = contextId;
			if(BX.type.isFunction(this._contextChangeCallback))
			{
				this._contextChangeCallback(this, this._contextId);
			}
		},
		saveConfig: function()
		{
			this._config["context"] = this._contextId;
		},
		getConfig: function()
		{
			return this._config;
		},
		onContextChange: function(e)
		{
			if(this._contextId !== BX.CrmWidgetDataContext.fund
				&& this._contextSwitches[BX.CrmWidgetDataContext.fund].checked)
			{
				this.setContextId(BX.CrmWidgetDataContext.fund);
			}
			else if(this._contextId !== BX.CrmWidgetDataContext.entity
				&& this._contextSwitches[BX.CrmWidgetDataContext.entity].checked)
			{
				this.setContextId(BX.CrmWidgetDataContext.entity);
			}
			else if(this._contextId !== BX.CrmWidgetDataContext.percent
				&& this._contextSwitches[BX.CrmWidgetDataContext.percent].checked)
			{
				this.setContextId(BX.CrmWidgetDataContext.percent);
			}
		}
	};
	BX.CrmWidgetConfigContextEditor.create = function(id, settings)
	{
		var self = new BX.CrmWidgetConfigContextEditor();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmRatingWidgetConfigEditor) === "undefined")
{
	BX.CrmRatingWidgetConfigEditor = function()
	{
		BX.CrmRatingWidgetConfigEditor.superclass.constructor.apply(this);
		this._config = null;
		this._titleEditor = null;
		this._presetEditor = null;
		this._presetChangeHandler = BX.delegate(this.onPresetChange, this);

	};
	BX.extend(BX.CrmRatingWidgetConfigEditor, BX.CrmWidgetConfigEditor);
	BX.CrmRatingWidgetConfigEditor.prototype.innerPrepareDialogContent = function(container)
	{
		this._titleEditor = BX.CrmWidgetConfigTitleEditor.create(this._id,
			{ config: this._config, backgroundColor: "#43a4af", enableBackgroundColorChange: false }
		);

		var presetConfig = BX.type.isArray(this._config["configs"]) && this._config["configs"].length > 0
							? this._config["configs"][0] : {};
		this._presetEditor = BX.CrmWidgetConfigPresetEditor.create(this._id,
			{ config: presetConfig, presetChange: this._presetChangeHandler, entityTypeName: this.getEntityTypeName() }
		);

		var itemWrapper = BX.create("DIV", { attrs: { className: "container-item container-item-center" } });
		container.appendChild(itemWrapper);
		this._titleEditor.prepareLayout(itemWrapper);
		this._presetEditor.prepareLayout(itemWrapper, "wide");

		return container;
	};
	BX.CrmRatingWidgetConfigEditor.prototype.innerSaveConfig = function()
	{
		this._titleEditor.saveConfig();
		this._config["title"] = this._titleEditor.getTitle();

		this._presetEditor.saveConfig();
		this._config["configs"] = [ this._presetEditor.getConfig() ];
	};
	BX.CrmRatingWidgetConfigEditor.prototype.reset = function()
	{
		this._titleEditor.resetLayout();
		this._titleEditor = null;

		this._presetEditor.resetLayout();
		this._presetEditor = null;

		this._period.resetLayout();
		this._period = null;
	};
	BX.CrmRatingWidgetConfigEditor.prototype.onPresetChange = function(sender, preset)
	{
		if(preset && BX.type.isNotEmptyString(preset["title"]) && this._titleEditor)
		{
			this._titleEditor.setTitle(preset["title"]);
		}
	};
	BX.CrmRatingWidgetConfigEditor.create = function(id, settings)
	{
		var self = new BX.CrmRatingWidgetConfigEditor();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmNumericWidgetConfigEditor) === "undefined")
{
	BX.CrmNumericWidgetConfigEditor = function()
	{
		BX.CrmNumericWidgetConfigEditor.superclass.constructor.apply(this);
		this._fields = [];
	};
	BX.extend(BX.CrmNumericWidgetConfigEditor, BX.CrmWidgetConfigEditor);
	BX.CrmNumericWidgetConfigEditor.prototype.innerPrepareDialogContent = function(container)
	{
		var configs = BX.type.isArray(this._config["configs"]) ? this._config["configs"] : [];
		var i, j;
		var titleBgColor = "";
		if(configs.length === 1)
		{
			titleBgColor = "#43a4af";
		}

		for(i = 0; i < configs.length; i++)
		{
			var config = configs[i];

			var id = this._id + "_" + i;
			var settings =
			{
				config: config,
				index: i,
				parent: this,
				titleBackgroundColor: titleBgColor,
				entityTypeName: this.getEntityTypeName()
			};

			this._fields.push(
				BX.CrmNumericWidgetConfigExpressionFieldEditor.isExpression(config)
					? BX.CrmNumericWidgetConfigExpressionFieldEditor.create(id, settings)
					: BX.CrmNumericWidgetConfigPresetFieldEditor.create(id, settings)
			);
		}

		for(i = 0; i < this._fields.length; i++)
		{
			this._fields[i].postInitialize();
		}

		var fieldConfigWrapper = null;
		var extras = [];
		for(i = 0; i < this._fields.length; i++)
		{
			var field = this._fields[i];
			if(i === 0)
			{
				container.appendChild(
					field.prepareLayout(BX.create("DIV", { attrs: { className: "container-item" } }), "wide")
				);
			}
			else
			{
				if(!fieldConfigWrapper)
				{
					fieldConfigWrapper = BX.create("DIV", {attrs: {className: "container-item-wrapper"}});
					container.appendChild(fieldConfigWrapper);
				}

				var fieldEditorContainer = BX.create("DIV",
					{ attrs: { className: "container-item container-item-5 " + ((i % 2) > 0 ? "container-item-first" : "container-item-second") } }
				);
				fieldConfigWrapper.appendChild(fieldEditorContainer);
				field.prepareLayout(fieldEditorContainer)
			}
			field.prepareExtraControls(extras);
		}

		if(extras.length > 0)
		{
			for(i = 0; i < extras.length; i++)
			{
				var extra = extras[i];

				if(!BX.type.isArray(extra["controls"]))
				{
					continue;
				}

				if(BX.type.isNotEmptyString(extra["type"]) && extra["type"] === "action")
				{
					var actionWrapper = BX.create("DIV", { attrs: { className: "action-container" } });
					for(j = 0; j < extra["controls"].length; j++)
					{
						actionWrapper.appendChild(extra["controls"][j]);
					}
					if(fieldConfigWrapper)
					{
						container.insertBefore(actionWrapper, fieldConfigWrapper);
					}
					else
					{
						container.appendChild(actionWrapper);
					}
				}
				else
				{
					for(j = 0; j < extra["controls"].length; j++)
					{
						container.appendChild(extra["controls"][j]);
					}
				}
			}
		}

		return container;
	};
	BX.CrmNumericWidgetConfigEditor.prototype.getFieldByName = function(name)
	{
		for(var i = 0; i < this._fields.length; i++)
		{
			var field = this._fields[i];
			if(name === field.getName())
			{
				return field;
			}
		}
		return null;
	};
	BX.CrmNumericWidgetConfigEditor.prototype.getFields = function()
	{
		return this._fields;
	};
	BX.CrmNumericWidgetConfigEditor.prototype.innerSaveConfig = function()
	{
		for(var i = 0; i < this._fields.length; i++)
		{
			this._fields[i].saveConfig();
			this._config["configs"][i] = this._fields[i].getConfig();
		}
	};
	BX.CrmNumericWidgetConfigEditor.prototype.reset = function()
	{
		for(var i = 0; i < this._fields.length; i++)
		{
			this._fields[i].resetLayout();
		}
		this._fields = [];

		this._period.resetLayout();
		this._period = null;
	};
	BX.CrmNumericWidgetConfigEditor.create = function(id, settings)
	{
		var self = new BX.CrmNumericWidgetConfigEditor();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmGraphWidgetConfigEditor) === "undefined")
{
	BX.CrmGraphWidgetConfigEditor = function()
	{
		BX.CrmGraphWidgetConfigEditor.superclass.constructor.apply(this);
		this._grouping = null;
		this._context = null;
		this._fields = [];
		this._maxGraphCount = 0;
		this._buttonContainer = null;
		this._addButton = null;
		this._contextChangeHandler = BX.delegate(this.onContextChange, this);
		this._fieldDeletionHandler = BX.delegate(this.onFieldDelete, this);
		this._addButtonClickHandler = BX.delegate(this.onAddButtonClick, this);
	};
	BX.extend(BX.CrmGraphWidgetConfigEditor, BX.CrmWidgetConfigEditor);
	BX.CrmGraphWidgetConfigEditor.prototype.doInitialize = function()
	{
		this._maxGraphCount = parseInt(this.getSetting("maxGraphCount", 0));
		if(isNaN(this._maxGraphCount))
		{
			this._maxGraphCount = 0;
		}
	};
	BX.CrmGraphWidgetConfigEditor.prototype.innerPrepareDialogContent = function(container)
	{
		this._grouping = BX.CrmWidgetConfigGroupingEditor.create(
			this._id,
			{
				config: this._config,
				control: BX.CrmGraphWidgetDataGroup.create()
			}
		);
		this._grouping.prepareLayout(this._leadingWrapper, "left");

		this._context = BX.CrmWidgetConfigContextEditor.create(this._id, { config: this._config, contextChange: this._contextChangeHandler });
		this._context.prepareLayout(this._leadingWrapper, "right");
		var contextId = this._context.getContextId();
		var configs = BX.type.isArray(this._config["configs"]) ? this._config["configs"] : [];
		var configCount = (this._maxGraphCount > 0 && configs.length < this._maxGraphCount)
			? configs.length : this._maxGraphCount;
		for(var i = 0; i < configCount; i++)
		{
			var config = configs[i];
			var id = this._id + "_" + (i + 1).toString();
			var settings =
				{
					config: config,
					context: contextId,
					parent: this,
					isNew: false,
					index: i,
					titleBackgroundColor: "",
					entityTypeName: this.getEntityTypeName()
				};
			this._fields.push(BX.CrmGraphWidgetConfigFieldEditor.create(id, settings));
		}

		for(var j = 0; j < this._fields.length; j++)
		{
			var field = this._fields[j];
			field.addDeletionListener(this._fieldDeletionHandler);
			container.appendChild(
				field.prepareLayout(BX.create("DIV", { attrs: { className: "container-item" } }), "wide")
			);
		}

		this._addButton = BX.create("A",
			{
				attrs: { className: "field-link", href: "#" }, text: this.getMessage("addGraph")
			}
		);
		this._buttonContainer = BX.create("DIV",
			{
				attrs: { className: "container-item container-item-button" },
				children: [ this._addButton ]
			}
		);

		BX.bind(this._addButton, "click", this._addButtonClickHandler);
		container.appendChild(this._buttonContainer);
		return container;
	};
	BX.CrmGraphWidgetConfigEditor.prototype.getMessage = function(name)
	{
		if(BX.CrmGraphWidgetConfigEditor.messages.hasOwnProperty(name))
		{
			return BX.CrmGraphWidgetConfigEditor.messages[name];
		}
		return BX.CrmGraphWidgetConfigEditor.superclass.getMessage.apply(this, [ name ]);
	};
	BX.CrmGraphWidgetConfigEditor.prototype.getFieldByName = function(name)
	{
		for(var i = 0; i < this._fields.length; i++)
		{
			var field = this._fields[i];
			if(name === field.getName())
			{
				return field;
			}
		}
		return null;
	};
	BX.CrmGraphWidgetConfigEditor.prototype.getFields = function()
	{
		return this._fields;
	};
	BX.CrmGraphWidgetConfigEditor.prototype.createField = function()
	{
		var qty = 0;
		for(var i = 0; i < this._fields.length; i++)
		{
			if(!this._fields[i].isDeleted())
			{
				qty++;
			}
		}
		if(qty >= this._maxGraphCount)
		{
			alert(this.getMessage("maxGraphError").replace(/#MAX_GRAPH_COUNT#/gi, this._maxGraphCount));
			return;
		}

		var index = this._fields.length;
		var id = this._id + "_" + (index + 1).toString();
		var settings =
			{
				config: { name: "param" + (index + 1).toString() },
				context: this._context.getContextId(),
				parent: this,
				isNew: true,
				index: index,
				titleBackgroundColor: "",
				entityTypeName: this.getEntityTypeName()
			};
		var field = BX.CrmGraphWidgetConfigFieldEditor.create(id, settings);
		this._fields.push(field);
		this._container.insertBefore(
			field.prepareLayout(BX.create("DIV", { attrs: { className: "container-item" } }), "wide"),
			this._buttonContainer
		);
		field.addDeletionListener(this._fieldDeletionHandler);
	};
	BX.CrmGraphWidgetConfigEditor.prototype.removeField = function(field)
	{
		field.resetLayout();
	};
	BX.CrmGraphWidgetConfigEditor.prototype.removeConfig = function(config)
	{
		if(!BX.type.isArray(this._config["configs"]))
		{
			return;
		}

		for(var i = 0; i < this._config["configs"].length; i++)
		{
			if(config === this._config["configs"][i])
			{
				this._config["configs"].splice(i, 1);
				return;
			}
		}
	};
	BX.CrmGraphWidgetConfigEditor.prototype.innerSaveConfig = function()
	{
		this._grouping.saveConfig();
		this._context.saveConfig();

		for(var i = 0; i < this._fields.length; i++)
		{
			var field = this._fields[i];
			if(!field.isDeleted())
			{
				field.saveConfig();
				if(field.isNew())
				{
					this._config["configs"].push(field.getConfig());
				}
			}
			else if(!field.isNew())
			{
				this.removeConfig(field.getConfig());
			}
		}
	};
	BX.CrmGraphWidgetConfigEditor.prototype.reset = function()
	{
		for(var i = 0; i < this._fields.length; i++)
		{
			this._fields[i].resetLayout();
		}
		this._fields = [];

		this._grouping.resetLayout();
		this._grouping = null;

		this._context.resetLayout();
		this._context = null;

		BX.unbind(this._addButton, "click", this._addButtonClickHandler);
		BX.cleanNode(this._buttonContainer, true);
		this._buttonContainer = null;
		this._addButton = null;
	};
	BX.CrmGraphWidgetConfigEditor.prototype.onContextChange = function(sender, contextId)
	{
		for(var i = 0; i < this._fields.length; i++)
		{
			this._fields[i].setContextId(contextId);
		}
	};
	BX.CrmGraphWidgetConfigEditor.prototype.onFieldDelete = function(sender)
	{
		this.removeField(sender);
	};
	BX.CrmGraphWidgetConfigEditor.prototype.onAddButtonClick = function(e)
	{
		this.createField();
		return BX.PreventDefault(e);
	};
	if(typeof(BX.CrmGraphWidgetConfigEditor.messages) === "undefined")
	{
		BX.CrmGraphWidgetConfigEditor.messages = {};
	}
	BX.CrmGraphWidgetConfigEditor.create = function(id, settings)
	{
		var self = new BX.CrmGraphWidgetConfigEditor();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmNumericWidgetConfigFieldEditor) === "undefined")
{
	BX.CrmNumericWidgetConfigFieldEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._parent = null;
		this._name = "";
		this._index = 0;
		this._letter = "";
		this._config = null;

		this._titleBackgroundColor = "";
		this._titleEditor = null;
	};
	BX.CrmNumericWidgetConfigFieldEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._parent = this.getSetting("parent");
			if(!this._parent)
			{
				throw "CrmNumericWidgetConfigFieldEditor: Could not find 'parent' parameter.";
			}

			this._index = this.getSetting("index", 0);
			this._letter = String.fromCharCode(65 + this._index);

			this._config = this.getSetting("config", {});
			this._name = BX.type.isNotEmptyString(this._config["name"]) ? this._config["name"] : "c" + (this._index + 1);
			this._titleBackgroundColor = this.getSetting("titleBackgroundColor", "");
			if(BX.type.isPlainObject(this._config["display"])
				&& BX.type.isNotEmptyString(this._config["display"]["colorScheme"]))
			{
				this._schemeId = this._config["display"]["colorScheme"];
				var scheme = BX.CrmWidgetColorScheme.getInfo(this._schemeId);
				if(scheme)
				{
					this._titleBackgroundColor = scheme["color"];
				}
			}
			if(this._titleBackgroundColor === "")
			{
				if(this._index > 1)
				{
					this._titleBackgroundColor = "#dec01f";
				}
				else if(this._index > 0)
				{
					this._titleBackgroundColor = "#4fc3f7";
				}
				else
				{
					this._titleBackgroundColor = "#22d02c";
				}
			}

			this.innerInitialize();
		},
		innerInitialize: function()
		{
		},
		postInitialize: function()
		{
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getIndex: function()
		{
			return this._index;
		},
		getLetter: function()
		{
			return this._letter;
		},
		getName: function()
		{
			return this._name;
		},
		getTitleEditor: function()
		{
			return this._titleEditor;
		},
		getTitleBackgroundColor: function()
		{
			return this._titleEditor ? this._titleEditor.getTitleBackgroundColor() : this._titleBackgroundColor;
		},
		prepareLayout: function(container, layout)
		{
			this._titleEditor = BX.CrmWidgetConfigTitleEditor.create(this._id,
				{ config: this._config, backgroundColor: this._titleBackgroundColor, letter: this._letter }
			);
			this._titleEditor.prepareLayout(container);

			this.innerPrepareLayout(container, layout);
			return container;
		},
		innerPrepareLayout: function(container, layout)
		{
		},
		prepareExtraControls: function(collection)
		{
		},
		resetLayout: function()
		{
			this._titleEditor.resetLayout();
			this._titleEditor = null;

			this.innerResetLayout();
		},
		innerResetLayout: function()
		{
		},
		getConfig: function()
		{
			return this._config;
		},
		saveConfig: function()
		{
			this._config["name"] = this._name;
			this._titleEditor.saveConfig();
			this._config["title"] = this._titleEditor.getTitle();
			this.innerSaveConfig();
		},
		innerSaveConfig: function()
		{
		}
	};
}
if(typeof(BX.CrmNumericWidgetConfigPresetFieldEditor) === "undefined")
{
	BX.CrmNumericWidgetConfigPresetFieldEditor = function()
	{
		BX.CrmNumericWidgetConfigPresetFieldEditor.superclass.constructor.apply(this);
		this._entityTypeName = "";
		this._presetEditor = null;
		this._presetChangeHandler = BX.delegate(this._onPresetChange, this);
	};
	BX.extend(BX.CrmNumericWidgetConfigPresetFieldEditor, BX.CrmNumericWidgetConfigFieldEditor);
	BX.CrmNumericWidgetConfigPresetFieldEditor.prototype.innerInitialize = function()
	{
		this._entityTypeName = this.getSetting("entityTypeName", "");
	};
	BX.CrmNumericWidgetConfigPresetFieldEditor.prototype.getContextId = function()
	{
		return this._presetEditor ? this._presetEditor.getContextId() : BX.CrmWidgetDataContext.undefined;
	};
	BX.CrmNumericWidgetConfigPresetFieldEditor.prototype.innerPrepareLayout = function(container, layout)
	{
		this._presetEditor = BX.CrmWidgetConfigPresetEditor.create(this._id,
			{ config: this._config, entityTypeName: this._entityTypeName, presetChange: this._presetChangeHandler }
		);
		this._presetEditor.prepareLayout(container, layout);
	};
	BX.CrmNumericWidgetConfigPresetFieldEditor.prototype.innerResetLayout = function()
	{
		this._presetEditor.resetLayout();
		this._presetEditor = null;
	};
	BX.CrmNumericWidgetConfigPresetFieldEditor.prototype.innerSaveConfig = function()
	{
		this._presetEditor.saveConfig();
		this._config = this._presetEditor.getConfig();
	};
	BX.CrmNumericWidgetConfigPresetFieldEditor.prototype._onPresetChange = function(sender, preset)
	{
		if(preset && BX.type.isNotEmptyString(preset["title"]) && this._titleEditor)
		{
			this._titleEditor.setTitle(preset["title"]);
		}
	};
	BX.CrmNumericWidgetConfigPresetFieldEditor.create = function(id, settings)
	{
		var self = new BX.CrmNumericWidgetConfigPresetFieldEditor();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmNumericWidgetConfigExpressionFieldEditor) === "undefined")
{
	BX.CrmNumericWidgetConfigExpressionFieldEditor = function()
	{
		BX.CrmNumericWidgetConfigExpressionFieldEditor.superclass.constructor.apply(this);
		this._source = null;
		this._operation = "";
		this._leftItem = null;
		this._rightItem = null;
		this._leftIcon = null;
		this._icon = null;
		this._rightIcon = null;
		this._operationLegend = null;
		this._operationIcon = null;
		this._operationSign = null;
		this._operationSelector = null;
		this._operationChangeHandler = BX.delegate(this.onOperationChange, this);
		this._colorSchemeChangeHandler = BX.delegate(this.onColorSchemeChange, this);
		this._leftItemColorSchemeChangeHandler = BX.delegate(this.onLeftItemColorSchemeChange, this);
		this._rightItemColorSchemeChangeHandler = BX.delegate(this.onRightItemColorSchemeChange, this);
	};
	BX.extend(BX.CrmNumericWidgetConfigExpressionFieldEditor, BX.CrmNumericWidgetConfigFieldEditor);
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.innerInitialize = function()
	{
		this._source = BX.type.isPlainObject(this._config["dataSource"]) ? this._config["dataSource"] : {};
		this._operation = BX.type.isNotEmptyString(this._source["operation"])
			? this._source["operation"].toUpperCase() : BX.CrmWidgetExpressionOperation.diff;
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.postInitialize = function()
	{
		this._items = {};
		var rx = new RegExp("%([^%]+)%");
		var arguments = BX.type.isArray(this._source["arguments"]) ? this._source["arguments"] : [];
		var argCount = arguments.length;
		var field;
		if(argCount > 0)
		{
			if(argCount < 2)
			{
				throw "CrmNumericWidgetExpressionConfigEditor: Configuration must contain at least two arguments.";
			}

			for(var i = 0; i < 2; i++)
			{
				var m = rx.exec(arguments[i]);
				if(!m)
				{
					throw "CrmNumericWidgetExpressionConfigEditor: Could not parse argument.";
				}

				var name = m[1];
				field = this._parent.getFieldByName(name);
				if(!field)
				{
					throw "CrmNumericWidgetExpressionConfigEditor: Could not find field.";
				}

				if(i === 0)
				{
					this._leftItem = field;
				}
				else
				{
					this._rightItem = field;
				}
			}
		}
		else
		{
			var fields = this._parent.getFields();
			var fildCount = fields.length;
			if(fildCount < 2)
			{
				throw "CrmNumericWidgetExpressionConfigEditor: Parent must contain at least two fields.";
			}
			for(var j = 0; j < 2; j++)
			{
				if(j === 0)
				{
					this._leftItem = fields[j];
				}
				else
				{
					this._rightItem = fields[j];
				}
			}
		}
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.innerPrepareLayout = function(container, layout)
	{
		this._operationLegend = BX.create("P", { text: BX.CrmWidgetExpressionOperation.getLegend(this._operation) });
		container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "field-container" },
					children: [ this._operationLegend ]
				}
			)
		);

		this._icon = BX.create("SPAN",
			{
				attrs: { className: "color-digit" },
				style: { backgroundColor: this.getTitleBackgroundColor() },
				text: this._letter
			}
		);

		this._leftIcon = BX.create("SPAN",
			{
				attrs: { className: "color-digit" },
				style: { backgroundColor: this._leftItem.getTitleBackgroundColor() },
				text: this._leftItem.getLetter()
			}
		);

		this._rightIcon = BX.create("SPAN",
			{
				attrs: { className: "color-digit" },
				style: { backgroundColor: this._rightItem.getTitleBackgroundColor() },
				text: this._rightItem.getLetter()
			}
		);

		this._operationSign = BX.create("SPAN", { attrs: { className: BX.CrmWidgetExpressionOperation.getSymbolClassName(this._operation) } });
		container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "subtraction" },
					children:
					[
						BX.create("DIV",
							{
								children:
								[
									this._icon,
									BX.create("SPAN", { attrs: { className: "symbol symbol-equally" } }),
									this._leftIcon,
									this._operationSign,
									this._rightIcon
								]
							}
						)
					]
				}
			)
		);

		if(BX.type.isElementNode(container.parentNode))
		{
			container.parentNode.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "container-item-equally" },
						children: [ BX.create("SPAN") ]
					}
				)
			);
		}

		var titleEditor = this.getTitleEditor();
		if(titleEditor)
		{
			titleEditor.addColorSchemeChangeListener(this._colorSchemeChangeHandler);
		}

		var leftTitleEditor = this._leftItem.getTitleEditor();
		if(leftTitleEditor)
		{
			leftTitleEditor.addColorSchemeChangeListener(this._leftItemColorSchemeChangeHandler);
		}

		var rightTitleEditor = this._rightItem.getTitleEditor();
		if(rightTitleEditor)
		{
			rightTitleEditor.addColorSchemeChangeListener(this._rightItemColorSchemeChangeHandler);
		}
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.prepareExtraControls = function(collection)
	{
		var operation = { type: "action", controls: [] };

		this._operationIcon = BX.create("SPAN",
			{ attrs: { className: BX.CrmWidgetExpressionOperation.getIconClassName(this._operation) } }
		);

		operation["controls"].push(this._operationIcon);

		this._operationSelector = BX.CrmWidgetConfigEditor.createSelect(
			{ events: { "change": this._operationChangeHandler } },
			BX.CrmWidgetExpressionOperation.prepareListItems()
		);
		this._operationSelector.value = this._operation;

		operation["controls"].push(
			BX.create("SPAN", { attrs: { className: "select-container" }, children: [ this._operationSelector ] })
		);

		operation["controls"].push(BX.create("SPAN", { text: BX.CrmWidgetExpressionOperation.getHint() }));

		collection.push(operation);
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.innerSaveConfig = function()
	{
		if(!BX.type.isPlainObject(this._config["dataSource"]))
		{
			this._config["dataSource"] = {};
		}

		this._config["dataSource"]["name"] = "EXPRESSION";
		this._config["dataSource"]["operation"] = this._operation;
		this._config["dataSource"]["arguments"] =
		[
			("%" + this._leftItem.getName() + "%"),
			("%" + this._rightItem.getName() + "%")
		];

		if( this._operation !== BX.CrmWidgetExpressionOperation.percent
			&& (this._leftItem.getContextId() === BX.CrmWidgetDataContext.fund
			|| this._rightItem.getContextId() === BX.CrmWidgetDataContext.fund))
		{
			if(!BX.type.isPlainObject(this._config["format"]))
			{
				this._config["format"] = {};
			}
			this._config["format"]["isCurrency"] = "Y";
			this._config["format"]["enableDecimals"] = "N";
		}
		else if(BX.type.isPlainObject(this._config["format"]))
		{
			delete this._config["format"]["isCurrency"];
			delete this._config["format"]["enableDecimals"];
		}
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.innerResetLayout = function()
	{
		var titleEditor = this.getTitleEditor();
		if(titleEditor)
		{
			titleEditor.removeColorSchemeChangeListener(this._colorSchemeChangeHandler);
		}

		this._leftIcon = null;
		var leftTitleEditor = this._leftItem ? this._leftItem.getTitleEditor() : null;
		if(leftTitleEditor)
		{
			leftTitleEditor.removeColorSchemeChangeListener(this._leftItemColorSchemeChangeHandler);
		}

		this._rightIcon = null;
		var rightTitleEditor = this._rightItem ? this._rightItem.getTitleEditor() : null;
		if(rightTitleEditor)
		{
			rightTitleEditor.removeColorSchemeChangeListener(this._rightItemColorSchemeChangeHandler);
		}

		this._operationLegend = null;
		this._operationSign = null;
		this._operationIcon = null;

		if(this._operationSelector)
		{
			BX.unbind(this._presetSelector, "change", this._operationChangeHandler);
			this._operationSelector = null;
		}
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.onOperationChange = function(e)
	{
		this._operation = this._operationSelector.value;
		this._operationIcon.className = BX.CrmWidgetExpressionOperation.getIconClassName(this._operation);
		this._operationLegend.innerHTML = BX.util.htmlspecialchars(BX.CrmWidgetExpressionOperation.getLegend(this._operation));
		this._operationSign.className = BX.CrmWidgetExpressionOperation.getSymbolClassName(this._operation);
		this._titleEditor.setTitle(BX.CrmWidgetExpressionOperation.getDescription(this._operation));
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.onColorSchemeChange = function()
	{
		if(this._icon)
		{
			this._icon.style.backgroundColor = this.getTitleBackgroundColor();
		}
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.onLeftItemColorSchemeChange = function()
	{
		if(this._leftItem && this._leftIcon)
		{
			this._leftIcon.style.backgroundColor = this._leftItem.getTitleBackgroundColor();
		}
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.onRightItemColorSchemeChange = function()
	{
		if(this._rightItem && this._rightIcon)
		{
			this._rightIcon.style.backgroundColor = this._rightItem.getTitleBackgroundColor();
		}
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.prototype.getOperation = function()
	{
		return this._operation;
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.isExpression = function(config)
	{
		var source = BX.type.isPlainObject(config["dataSource"]) ? config["dataSource"] : {};
		return (BX.type.isNotEmptyString(source["name"]) && source["name"].toUpperCase() === "EXPRESSION");
	};
	BX.CrmNumericWidgetConfigExpressionFieldEditor.create = function(id, settings)
	{
		var self = new BX.CrmNumericWidgetConfigExpressionFieldEditor();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmGraphWidgetConfigFieldEditor) === "undefined")
{
	BX.CrmGraphWidgetConfigFieldEditor = function()
	{
		this._id = "";
		this._settings = null;
		this._name = "";
		this._index = 0;
		this._config = null;
		this._entityTypeName = "";

		this._contextId = BX.CrmWidgetDataContext.undefined;
		this._titleBackgroundColor = "";
		this._titleEditor = null;
		this._presetEditor = null;
		this._presetChangeHandler = BX.delegate(this.onPresetChange, this);
		this._titleDeletionHandler = BX.delegate(this.onTitleDeletion, this);

		this._isNew = false;
		this._isDeleted = false;
		this._deletionNotifier = null;
		this._container = null;
		this._hasLayout = false;
	};
	BX.CrmGraphWidgetConfigFieldEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._entityTypeName = this.getSetting("entityTypeName", "");
			this._index = this.getSetting("index", 0);
			this._contextId = this.getSetting("context", BX.CrmWidgetDataContext.entity);
			this._config = this.getSetting("config", {});
			this._name = BX.type.isNotEmptyString(this._config["name"]) ? this._config["name"] : "c" + (this._index + 1);
			this._titleBackgroundColor = this.getSetting("titleBackgroundColor", "");
			this._isNew = this.getSetting("isNew", false);
			this._deletionNotifier = BX.CrmNotifier.create(this);
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getIndex: function()
		{
			return this._index;
		},
		getName: function()
		{
			return this._name;
		},
		getContextId: function()
		{
			return this._contextId;
		},
		setContextId: function(contextId)
		{
			this._contextId = contextId;
			if(this._presetEditor)
			{
				this._presetEditor.setContextId(contextId);
			}
		},
		getTitleBackgroundColor: function()
		{
			return this._titleBackgroundColor;
		},
		getTitleEditor: function()
		{
			return this._titleEditor;
		},
		prepareLayout: function(container)
		{
			if(this._hasLayout)
			{
				return;
			}

			this._container = container;
			this._titleEditor = BX.CrmWidgetConfigTitleEditor.create(this._id,
				{ config: this._config, enableDeletion: true, backgroundColor: this._titleBackgroundColor }
			);
			this._titleEditor.addDeletionListener(this._titleDeletionHandler);
			this._titleEditor.prepareLayout(container);

			this._presetEditor = BX.CrmWidgetConfigPresetEditor.create(this._id,
				{
					config: this._config,
					entityTypeName: this._entityTypeName,
					presetChange: this._presetChangeHandler,
					context: this._contextId,
					enableContextChange: false
				}
			);
			this._presetEditor.prepareLayout(container, "wide");

			this._hasLayout = true;
			return container;
		},
		resetLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			this._titleEditor.removeDeletionListener(this._titleDeletionHandler);
			this._titleEditor.resetLayout();
			this._titleEditor = null;

			this._presetEditor.resetLayout();
			this._presetEditor = null;

			BX.cleanNode(this._container, true);
			this._container = null;
			this._hasLayout = false;
		},
		getConfig: function()
		{
			return this._config;
		},
		saveConfig: function()
		{
			this._config["name"] = this._name;
			this._titleEditor.saveConfig();
			this._presetEditor.saveConfig();
		},
		addDeletionListener: function(listener)
		{
			this._deletionNotifier.addListener(listener);
		},
		removeDeletionListener: function(listener)
		{
			this._deletionNotifier.removeListener(listener);
		},
		isNew: function()
		{
			return this._isNew;
		},
		isDeleted: function()
		{
			return this._isDeleted;
		},
		onPresetChange: function(sender, preset)
		{
			if(preset && BX.type.isNotEmptyString(preset["title"]) && this._titleEditor)
			{
				this._titleEditor.setTitle(preset["title"]);
			}
		},
		onTitleDeletion: function(e)
		{
			this._isDeleted = true;
			this._deletionNotifier.notify();
		}
	};
	BX.CrmGraphWidgetConfigFieldEditor.create = function(id, settings)
	{
		var self = new BX.CrmGraphWidgetConfigFieldEditor();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmWidgetDataPreset) === "undefined")
{
	BX.CrmWidgetDataPreset = function() {};
	BX.CrmWidgetDataPreset.prototype = {};
	BX.CrmWidgetDataPreset.items = [];
	BX.CrmWidgetDataPreset.notSelected = "-";
	BX.CrmWidgetDataPreset.prepareListItems = function(entityTypeName, categoryName, contextId)
	{
		if(!BX.type.isNotEmptyString(entityTypeName))
		{
			entityTypeName = "";
		}

		var results = [{ value: "", text: this.notSelected }];
		for(var i = 0; i < this.items.length; i++)
		{
			var item = this.items[i];

			var itemEntityTypeName = BX.type.isNotEmptyString(item["entity"]) ? item["entity"] : "";
			var itemCategoryName = BX.type.isNotEmptyString(item["category"]) ? item["category"] : "";
			var itemContextId = BX.type.isNotEmptyString(item["context"]) ? item["context"] : "";

			if(itemEntityTypeName !== entityTypeName
				|| itemCategoryName !== categoryName
				|| (contextId !== BX.CrmWidgetDataContext.undefined && itemContextId !== contextId))
			{
				continue;
			}

			var name = BX.type.isNotEmptyString(item["name"]) ? item["name"] : "";
			var title = BX.type.isNotEmptyString(item["listTitle"]) ? item["listTitle"] : "";
			if(title === "")
			{
				title = BX.type.isNotEmptyString(item["title"]) ? item["title"] : "";
			}
			if(title === "")
			{
				title = name;
			}

			results.push({ value: name, text: title });
		}
		return results;
	};
	BX.CrmWidgetDataPreset.getItem = function(name)
	{
		if(!BX.type.isNotEmptyString(name))
		{
			return null;
		}

		for(var i = 0; i < this.items.length; i++)
		{
			var item = this.items[i];
			var itemName = BX.type.isNotEmptyString(item["name"]) ? item["name"] : "";
			if(itemName === name)
			{
				return item;
			}
		}
		return null;
	};
}
if(typeof(BX.CrmWidgetDataCategory) === "undefined")
{
	BX.CrmWidgetDataCategory = function() {};
	BX.CrmWidgetDataCategory.prototype = {};
	BX.CrmWidgetDataCategory.items = [];
	BX.CrmWidgetDataCategory.notSelected = "-";
	BX.CrmWidgetDataCategory.getItem = function(name)
	{
		if(!BX.type.isNotEmptyString(name))
		{
			return null;
		}

		for(var i = 0; i < this.items.length; i++)
		{
			var item = this.items[i];
			var itemName = BX.type.isNotEmptyString(item["name"]) ? item["name"] : "";
			if(itemName === name)
			{
				return item;
			}
		}
		return null;
	};
	BX.CrmWidgetDataCategory.prepareListItems = function(entityTypeName)
	{
		if(!BX.type.isNotEmptyString(entityTypeName))
		{
			entityTypeName = "";
		}

		var results = [{ value: "", text: this.notSelected }];
		for(var i = 0; i < this.items.length; i++)
		{
			var item = this.items[i];
			var itemEntityTypeName = BX.type.isNotEmptyString(item["entity"]) ? item["entity"] : "";

			if(itemEntityTypeName !== entityTypeName)
			{
				continue;
			}

			var name = BX.type.isNotEmptyString(item["name"]) ? item["name"] : "";
			var title = BX.type.isNotEmptyString(item["title"]) ? item["title"] : "";
			if(title === "")
			{
				title = name;
			}

			results.push({ value: name, text: title });
		}
		return results;
	};
}
if(typeof(BX.CrmWidgetPanelCell) === "undefined")
{
	BX.CrmWidgetPanelCell = function()
	{
		this._id = "";
		this._settings = null;
		this._prefix = "";
		this._hasLayout = false;
		this._panel = null;
		this._row = null;
		this._container = null;
		this._widgets = [];
	};
	BX.CrmWidgetPanelCell.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._prefix = this.getSetting("prefix", this._id);

			this._row = this.getSetting("row");
			if(!this._row)
			{
				throw  "BX.CrmWidgetPanelCell: Parameter 'row' is not found.";
			}

			this._panel = this.getSetting("panel");
			if(!this._panel)
			{
				this._panel = this._row.getPanel();
			}

			this._container = this.getSetting("container", null);

			var data = this.getSetting("data", []);
			var controls = this.getSetting("controls", []);

			var height = this._row.getHeight();
			var widgetCount = this._panel.getWidgetCount();
			var maxWidgetCount = this._panel.getMaxWidgetCount();
			for(var i = 0; i < data.length; i++)
			{
				if(maxWidgetCount > 0 && widgetCount >= maxWidgetCount)
				{
					break;
				}

				var config = controls[i];
				var entityTypeName = BX.type.isNotEmptyString(config["entityTypeName"])
					? config["entityTypeName"] : "";

				if(entityTypeName === "")
				{
					entityTypeName = this._panel.getDefaultEntityTypeName();
				}

				var widget = BX.CrmWidgetManager.getCurrent().createWidget(
					this._prefix + "_" + i,
					{
						cell: this,
						prefix: this._prefix,
						data: data[i],
						config: config,
						entityTypeName: entityTypeName,
						heightInPixel: height
					}
				);
				this._widgets.push(widget);
				widgetCount++;
			}
		},
		createWidget: function(settings, index)
		{
			var widget = BX.CrmWidgetManager.getCurrent().createWidget(
				this._prefix + "_" + index,
				{
					cell: this,
					prefix: this._prefix,
					entityTypeName: BX.type.isNotEmptyString(settings["entityTypeName"]) ? settings["entityTypeName"] : "",
					config: BX.type.isPlainObject(settings["config"]) ? settings["config"] : {},
					data: BX.type.isPlainObject(settings["data"]) ? settings["data"] : {},
					heightInPixel: this._row.getHeight()
				}
			);

			this.addWidget(widget, index);
			return widget;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getRow: function()
		{
			return this._row;
		},
		getPanel: function()
		{
			return this._panel;
		},
		getContainer: function()
		{
			return this._container;
		},
		getIndex: function()
		{
			return this._row.getCellIndex(this);
		},
		getConfig: function()
		{
			var config = { "controls": [] };
			for(var i = 0; i < this._widgets.length; i++)
			{
				var widget = this._widgets[i];
				config["controls"].push(widget.getConfig());
			}

			if(config["controls"].length == 0)
			{
				config["isEmpty"] = 'Y';
			}

			return config;
		},
		getWidgets: function()
		{
			return this._widgets;
		},
		getWidgetCount: function()
		{
			return this._widgets.length;
		},
		getWidgetTotalHeight: function()
		{
			var result = 0;
			for(var i = 0; i < this._widgets.length; i++)
			{
				result += this._widgets[i].getHeight();
			}
			return result;
		},
		getWidgetById: function(id)
		{
			for(var i = 0; i < this._widgets.length; i++)
			{
				var widget = this._widgets[i];
				if(widget.getId() === id)
				{
					return widget;
				}
			}
			return null;
		},
		getWidgetIndex: function(widget)
		{
			for(var i = 0; i < this._widgets.length; i++)
			{
				if(widget === this._widgets[i])
				{
					return i;
				}
			}
			return -1;
		},
		getNextWidget: function(widget)
		{
			var last = (this._widgets.length - 1);
			for(var i = 0; i < last; i++)
			{
				if(widget === this._widgets[i])
				{
					return this._widgets[i + 1];
				}
			}
			return null;
		},
		addWidget: function(widget, index)
		{
			if(index < this._widgets.length)
			{
				this._widgets.splice(index, 0, widget);
			}
			else
			{
				this._widgets.push(widget);
			}

			widget.setContainer(this._container);
		},
		removeWidget: function(widget)
		{
			var index = -1;
			for(var i = 0; i < this._widgets.length; i++)
			{
				if(widget === this._widgets[i])
				{
					index = i;
					break;
				}
			}

			if(index >= 0)
			{
				this._widgets.splice(index, 1);
				widget.setContainer(null);
			}
		},
		isEmpty: function()
		{
			return this._widgets.length === 0;
		},
		isThin: function()
		{
			return (this._row.getHeight() - this.getWidgetTotalHeight()) > 0;
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}


			this._container = BX.create("DIV",
				{ attrs: { id: this._prefix + "_container", className: "crm-widget-container" } }
			);

			if(this._row.getCellCount() > 1)
			{
				BX.addClass(this._container, this.getIndex() === 0 ? "crm-widget-left" : "crm-widget-right");
			}
			this._row.getContainer().appendChild(this._container);

			for(var i = 0; i < this._widgets.length; i++)
			{
				var widget = this._widgets[i];
				widget.setContainer(this._container);
				widget.layout();
			}

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			for(var i = 0; i < this._widgets.length; i++)
			{
				var widget = this._widgets[i];
				widget.clearLayout();
			}

			BX.cleanNode(this._container, true);
			this._container = null;

			this._hasLayout = false;
		},
		invalidate: function()
		{
			for(var i = 0; i < this._widgets.length; i++)
			{
				this._widgets[i].invalidate();
			}
		}
	};
	BX.CrmWidgetPanelCell.getItemWidgetCount = function(item)
	{
		return item ? item.getWidgetCount() : 0;
	};
	BX.CrmWidgetPanelCell.create = function(id, settings)
	{
		var self = new BX.CrmWidgetPanelCell();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmWidgetPanelRow) === "undefined")
{
	BX.CrmWidgetPanelRow = function()
	{
		this._id = "";
		this._settings = null;
		this._panel = null;
		this._prefix = "";
		this._height = 0;
		this._hasLayout = false;
		this._container = null;
		this._index = -1;
		this._cells = [];
	};
	BX.CrmWidgetPanelRow.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this._panel = this.getSetting("panel");
			if(!this._panel)
			{
				throw  "BX.CrmWidgetPanelRow: Parameter 'panel' is not found.";
			}

			this._prefix = this.getSetting("prefix", this._id);
			this._height = parseInt(this.getSetting("height", BX.CrmWidgetLayoutHeight.full));

			var cellData = this.getSetting("cells", []);
			for(var i = 0; i < cellData.length; i++)
			{
				var cell = BX.CrmWidgetPanelCell.create(
					this._prefix + "_" + (i + 1),
					{ row: this, controls: cellData[i]["controls"], data: cellData[i]["data"] }
				);
				this._cells.push(cell);
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
		getPanel: function()
		{
			return this._panel;
		},
		getContainer: function()
		{
			return this._container;
		},
		getCells: function()
		{
			return this._cells;
		},
		getCellCount: function()
		{
			return this._cells.length;
		},
		getMaxCellHeight: function()
		{
			var result = 0;
			for(var i = 0; i < this._cells.length; i++)
			{
				var height = this._cells[i].getWidgetTotalHeight();
				if(height > result)
				{
					result = height;
				}
			}
			return result;
		},
		getHeight: function()
		{
			return this._height;
		},
		setHeight: function(height)
		{
			this._height = height;
			if(this._container)
			{
				this._container.style.height = this._height + "px";
			}
		},
		getIndex: function()
		{
			return this._panel.getRowIndex(this);
		},
		getConfig: function()
		{
			var config = { "cells": [] };
			for(var i = 0; i < this._cells.length; i++)
			{
				var cell = this._cells[i];
				config["cells"].push(cell.getConfig());
			}

			if(this._height > 0)
			{
				config["height"] = this._height;
			}
			return config;
		},
		getCellIndex: function(cell)
		{
			for(var i = 0; i < this._cells.length; i++)
			{
				if(this._cells[i] === cell)
				{
					return i;
				}
			}
			return -1;
		},
		getCellByIndex: function(index)
		{
			return this._cells.length > index ? this._cells[index] : null;
		},
		getThinCells: function()
		{
			var result = [];
			for(var i = 0; i < this._cells.length; i++)
			{
				var cell = this._cells[i];
				if(cell.isThin())
				{
					result.push(cell);
				}
			}
			return result;
		},
		getWidgetById: function(id)
		{
			var result = null;
			for(var i = 0; i < this._cells.length; i++)
			{
				result = this._cells[i].getWidgetById(id);
				if(result)
				{
					break;
				}
			}
			return result;
		},
		getWidgetCount: function()
		{
			var result = 0;
			for(var i = 0; i < this._cells.length; i++)
			{
				result += this._cells[i].getWidgetCount();
			}
			return result;
		},
		isEmpty: function()
		{
			for(var i = 0; i < this._cells.length; i++)
			{
				if(!this._cells[i].isEmpty())
				{
					return false;
				}
			}
			return true;
		},
		ensureCellCreated: function(index, layout)
		{
			if(index < 0)
			{
				return;
			}

			layout = !!layout;

			for(var i = 0; i <= index; i++)
			{
				if(this._cells.length <= i)
				{
					var cell = BX.CrmWidgetPanelCell.create(this._prefix + "_" + (i + 1), { row: this });
					this._cells.push(cell);
					if(layout)
					{
						cell.layout();
					}
				}
			}
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._container = BX.create("DIV", {attrs: {id: this._prefix + "_container", className: "crm-widget-row"}});
			this._container.style.height = this._height + "px";

			var index = this.getIndex();
			var parentContainer = this._panel.getContainer();
			if(index < 0 || index > parentContainer.children.length)
			{
				index = parentContainer.children.length;
			}

			if(index === parentContainer.children.length)
			{
				parentContainer.appendChild(this._container);
			}
			else
			{
				parentContainer.insertBefore(this._container, parentContainer.children[index]);
			}

			for(var i = 0; i < this._cells.length; i++)
			{
				this._cells[i].layout();
			}

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			for(var i = 0; i < this._cells.length; i++)
			{
				var cell = this._cells[i];
				cell.clearLayout();
			}

			if(this._container)
			{
				BX.cleanNode(this._container, true);
				this._container = null;
			}

			this._hasLayout = false;
		},
		invalidate: function()
		{
			for(var i = 0; i < this._cells.length; i++)
			{
				this._cells[i].invalidate();
			}
		}
	};
	BX.CrmWidgetPanelRow.create = function(id, settings)
	{
		var self = new BX.CrmWidgetPanelRow();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmWidgetPanel) === "undefined")
{
	BX.CrmWidgetPanel = function()
	{
		this._id = "";
		this._settings = null;
		this._defaultEntityTypeName = "";
		this._entityTypeNames = null;
		this._layoutType = BX.CrmWidgetLayout.undifined;

		this._prefix = "";
		this._container = null;
		this._settingButton = null;
		this._rows = [];
		this._dragDropController = null;
		this._saveConfigCallback = null;

		this._isSettingMenuShown = false;
		this._settingMenuId = "";
		this._settingMenu = null;
		this._settingMenuHandler = BX.delegate(this.onSettingMenuItemClick, this);
		this._settingButtonClickHandler = BX.delegate(this.onSettingButtonClick, this);

		this._dynamicRowKeys = {};

		this._isAjaxMode = false;

		this._isDemoMode = false;
		this._demoModeInfoContainer = null;
		this._disableDemoModeButton = null;
		this._demoModeInfoCloseButton = null;
		this._disableDemoButtonClickHandler = BX.delegate(this.onDisableDemoButtonClick, this);
		this._demoModeInfoCloseButtonClickHandler = BX.delegate(this.onDemoInfoCloseButtonClick, this);

		this._typeSelector = null;
		this._layoutTypeSelector = null;

		this._maxGraphCount = 0;
		this._maxWidgetCount = 0;
	};
	BX.CrmWidgetPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._entityTypeNames = this.getSetting("entityTypes", []);
			this._defaultEntityTypeName = this.getSetting("defaultEntityType", "");
			if(this._defaultEntityTypeName === "" && this._entityTypeNames.length > 0)
			{
				this._defaultEntityTypeName = this._entityTypeNames[0];
			}

			this._isAjaxMode = this.getSetting("isAjaxMode", false);
			this._prefix = this.getSetting("prefix", this._id);

			var containerId = this.getSetting("containerId");
			if(!BX.type.isNotEmptyString(containerId))
			{
				throw  "BX.CrmWidgetPanel: Parameter 'containerId' is not found.";
			}

			this._container = BX(containerId);
			if(!this._container)
			{
				throw  "BX.CrmWidgetPanel: Container is not found.";
			}

			this._layoutType = this.getSetting("layout", BX.CrmWidgetLayout.l50r50);
			this.processLayoutTypeChange();

			this._maxGraphCount = parseInt(this.getSetting("maxGraphCount", 0));
			this._maxWidgetCount = parseInt(this.getSetting("maxWidgetCount", 0));

			var rowData = this.getSetting("rows");
			if(BX.type.isArray(rowData))
			{
				for(var i = 0; i < rowData.length; i++)
				{
					var rowSettings = rowData[i];
					rowSettings["panel"] = this;
					var row = BX.CrmWidgetPanelRow.create(this._prefix + "_" + (i + 1), rowSettings);
					this._rows.push(row);
				}
			}

			this._settingButton = BX(this.getSetting("settingButtonId"));
			if(this._settingButton)
			{
				BX.bind(this._settingButton, "click", this._settingButtonClickHandler);
			}

			this._isDemoMode = this.getSetting("isDemoMode");
			if(this._isDemoMode)
			{
				this._demoModeInfoContainer = BX(this.getSetting("demoModeInfoContainerId"));

				this._disableDemoModeButton = BX(this.getSetting("disableDemoModeButtonId"));
				if(this._disableDemoModeButton)
				{
					BX.bind(this._disableDemoModeButton, "click", this._disableDemoButtonClickHandler);
				}

				this._demoModeInfoCloseButton = BX(this.getSetting("demoModeInfoCloseButtonId"));
				if(this._demoModeInfoCloseButton)
				{
					BX.bind(this._demoModeInfoCloseButton, "click", this._demoModeInfoCloseButtonClickHandler);
				}

			}
			BX.onCustomEvent(window, "CrmWidgetPanelCreated", [this]);
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
			var msg = BX.CrmWidgetPanel.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getContainer: function()
		{
			return this._container;
		},
		getDefaultEntityTypeName: function()
		{
			return this._defaultEntityTypeName;
		},
		getEntityTypeNames: function()
		{
			return this._entityTypeNames;
		},
		getLayoutType: function()
		{
			return this._layoutType;
		},
		setLayoutType: function(layoutType)
		{
			if(this._layoutType === layoutType)
			{
				return;
			}

			this._layoutType = layoutType;
			this.processLayoutTypeChange();
			this.invalidate();

			BX.showWait();
			BX.ajax.post(
				this.getSetting("serviceUrl", ""),
				{ "guid": this._id, "action": "savelayout", "layout": layoutType },
				BX.delegate(this.onAfterConfigSave, this)
			);
		},
		getRows: function()
		{
			return this._rows;
		},
		getRowCount: function()
		{
			return this._rows.length;
		},
		getRowIndex: function(row)
		{
			for(var i = 0; i < this._rows.length; i++)
			{
				if(this._rows[i] === row)
				{
					return i;
				}
			}
			return -1;
		},
		getRowById: function(id)
		{
			for(var i = 0; i < this._rows.length; i++)
			{
				var row = this._rows[i];
				if(row.getId() === id)
				{
					return row;
				}
			}
			return null;
		},
		getRowByIndex: function(index)
		{
			return this._rows.length > index ? this._rows[index] : null;
		},
		getThinCells: function()
		{
			var result = [];
			for(var i = 0; i < this._rows.length; i++)
			{
				var cells = this._rows[i].getThinCells();
				for(var j = 0; j < cells.length; j++)
				{
					result.push(cells[j]);
				}
			}
			return result;
		},
		getWidgetById: function(id)
		{
			var result = null;
			for(var i = 0; i < this._rows.length; i++)
			{
				result = this._rows[i].getWidgetById(id);
				if(result)
				{
					break;
				}
			}
			return result;
		},
		getWidgetCount: function()
		{
			var result = 0;
			for(var i = 0; i < this._rows.length; i++)
			{
				result += this._rows[i].getWidgetCount();
			}
			return result;
		},
		getCurrencyFormat: function()
		{
			return this.getSetting("currencyFormat", null);
		},
		getMaxGraphCount: function()
		{
			return this._maxGraphCount;
		},
		getMaxWidgetCount: function()
		{
			return this._maxWidgetCount;
		},
		openTypeSelector: function()
		{
			if(this._layoutTypeSelector && this._layoutTypeSelector.isDialogOpened())
			{
				this._layoutTypeSelector.closeDialog();
			}

			if(!this._typeSelector)
			{
				this._typeSelector = BX.CrmWidgetTypeSelector.create(
					this._id + "_widget_type_selector",
					{
						entityTypeNames: [ this._defaultEntityTypeName ],
						callback: BX.delegate(this.onConfiguratorAction, this)
					}
				);
			}

			this._typeSelector.openDialog();
		},
		openLayoutTypeSelector: function()
		{
			if(this._typeSelector && this._typeSelector.isDialogOpened())
			{
				this._typeSelector.closeDialog();
			}

			if(!this._layoutTypeSelector)
			{
				this._layoutTypeSelector = BX.CrmWidgetPanelLayoutTypeSelector.create(
					this._id + "_layout_type_selector",
					{
						layoutType: this._layoutType,
						callback: BX.delegate(this.onConfiguratorAction, this)
					}
				);
			}

			this._layoutTypeSelector.openDialog();
		},
		onConfiguratorAction: function(sender, args)
		{
			if(sender !== this._typeSelector && sender !== this._layoutTypeSelector)
			{
				return;
			}

			sender.closeDialog();

			var action = BX.type.isNotEmptyString(args["action"]) ? args["action"] : "";
			var data = BX.type.isPlainObject(args["data"]) ? args["data"] : {};

			if(action === "addItem" && BX.type.isPlainObject(data["params"]))
			{
				this.addWidget(data["params"]);
			}
			else if(action = "changeLayout" && BX.type.isNotEmptyString(data["layoutType"]))
			{
				this.setLayoutType(data["layoutType"]);
			}
		},
		addWidget: function(params)
		{
			var widgetCount = this.getWidgetCount();
			if(this._maxWidgetCount > 0 && widgetCount >= this._maxWidgetCount)
			{
				alert(this.getMessage("maxWidgetError").replace(/#MAX_WIDGET_COUNT#/gi, this._maxWidgetCount));
				return;
			}

			var row = this.createRow(
				{
					index: 0,
					height: BX.type.isNumber(params["rowHeight"]) ? params["rowHeight"] : BX.CrmWidgetLayoutHeight.full,
					cellCount: 1
				}
			);

			var widget = row.getCellByIndex(0).createWidget(params, 0);
			widget.layout();
			this._dragDropController.registerRow(row);
			this.saveConfig(function() { widget.openConfigDialog(); });
		},
		createRow: function(data)
		{
			var key = BX.util.getRandomString(6).toLowerCase();
			while(this._dynamicRowKeys.hasOwnProperty(key))
			{
				key = BX.util.getRandomString(8).toLowerCase();
			}

			var id = this._prefix + "_" + key;
			this._dynamicRowKeys[key] = id;

			var index = data["index"];
			var height = data["height"];
			var cellCount = data["cellCount"];

			var row = BX.CrmWidgetPanelRow.create(id, { panel: this, height: height, dynamicKey: key });
			for(var i = 0; i < cellCount; i++)
			{
				row.ensureCellCreated(i, false);
			}
			this._rows.splice(index, 0, row);
			row.layout();
			return row;
		},
		removeRow: function(row)
		{
			for(var i = 0; i < this._rows.length; i++)
			{
				if(this._rows[i] === row)
				{
					var key = row.getSetting("dynamicKey", "");
					if(key !== "")
					{
						delete this._dynamicRowKeys[key];
					}

					row.clearLayout();
					this._rows.splice(i, 1);
					return;
				}
			}
		},
		moveWidget: function(widget, row, cellIndex, index)
		{
			widget.clearLayout();
			widget.undock();

			row.ensureCellCreated(cellIndex);
			var cell = row.getCellByIndex(cellIndex);
			widget.dock(cell, index);
			widget.layout();
		},
		processWidgetRemoval: function(widget)
		{
			var row = widget.getRow();
			if(!row)
			{
				return;
			}

			if(!widget.remove())
			{
				return;
			}

			this._dragDropController.processRowChange(row);
			this.saveConfig();
		},
		processWidgetRefresh: function(widget)
		{
			this._dragDropController.processWidgetChange(widget);
		},
		processLayoutTypeChange: function()
		{
			var className = "crm-widget";
			if(this._layoutType === BX.CrmWidgetLayout.l70r30)
			{
				className = "crm-widget-70-30";
			}
			else if(this._layoutType === BX.CrmWidgetLayout.l50r50)
			{
				className = "crm-widget-50-50";
			}
			else if(this._layoutType === BX.CrmWidgetLayout.l30r70)
			{
				className = "crm-widget-30-70";
			}

			this._container.className = className;
		},
		layout: function()
		{
			for(var i = 0; i < this._rows.length; i++)
			{
				this._rows[i].layout();
			}

			this._dragDropController = BX.CrmWidgetDragDropController.create(
				this._id,
				{ panel: this, wrapper: this._container, rows: this._rows }
			);
		},
		saveConfig: function(callback)
		{
			var url = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(url))
			{
				throw  "BX.CrmWidgetPanel: Parameter 'serviceUrl' is not found.";
			}

			var data = { "guid": this._id, "action": "saveconfig", "rows": [] };
			for(var i = 0; i < this._rows.length; i++)
			{
				data["rows"].push(this._rows[i].getConfig());
			}

			if(BX.type.isFunction(callback))
			{
				this._saveConfigCallback = callback;
			}
			BX.showWait();
			BX.ajax.post(url, data, BX.delegate(this.onAfterConfigSave, this));
		},
		resetConfig: function(callback)
		{
			var url = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(url))
			{
				throw  "BX.CrmWidgetPanel: Parameter 'serviceUrl' is not found.";
			}

			var data = { "guid": this._id, "action": "resetrows", "rows": [] };
			for(var i = 0; i < this._rows.length; i++)
			{
				data["rows"].push(this._rows[i].getConfig());
			}

			if(BX.type.isFunction(callback))
			{
				this._saveConfigCallback = callback;
			}
			BX.showWait();
			BX.ajax.post(url, data, BX.delegate(this.onAfterConfigSave, this));
		},
		enableDemoMode: function(enable, callback)
		{
			enable = !!enable;
			window.setTimeout(function(){ window.location.reload(); }, 200);

			var url = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(url))
			{
				throw  "BX.CrmWidgetPanel: Parameter 'serviceUrl' is not found.";
			}
			
			var data = { "guid": this._id, "action": "enabledemo", "enable": enable ? "Y" : "N" };

			if(BX.type.isFunction(callback))
			{
				this._saveConfigCallback = callback;
			}
			BX.showWait();
			BX.ajax.post(url, data, BX.delegate(this.onAfterConfigSave, this));
		},
		isAjaxMode: function()
		{
			return this._isAjaxMode;
		},
		invalidate: function()
		{
			for(var i = 0; i < this._rows.length; i++)
			{
				this._rows[i].invalidate();
			}
		},
		onAfterConfigSave: function()
		{
			BX.closeWait();
			if(this._saveConfigCallback)
			{
				this._saveConfigCallback();
				this._saveConfigCallback = null;
			}
		},
		onSettingButtonClick: function()
		{
			if(!this._isSettingMenuShown)
			{
				this.openSettingMenu();
			}
			else
			{
				this.closeSettingMenu();
			}
		},
		openSettingMenu: function()
		{
			if(this._isSettingMenuShown)
			{
				return;
			}

			this._settingMenuId = this._id + "_menu";
			if(typeof(BX.PopupMenu.Data[this._settingMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._settingMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._settingMenuId];
			}

			var menuItems = [];
			if(!this._isDemoMode)
			{
				menuItems.push(
					{ id: "add", text: this.getMessage("menuItemAdd"), onclick: this._settingMenuHandler }
				);
			}

			menuItems.push(
				{ id: "layout", text: this.getMessage("menuChangeLayout"), onclick: this._settingMenuHandler }
			);

			menuItems.push({ id: "reset", text: this.getMessage("menuItemReset"), onclick: this._settingMenuHandler });
			if(!this._isDemoMode)
			{
				menuItems.push(
					{ id: "enabledemomode", text: this.getMessage("menuItemEnableDemoMode"), onclick: this._settingMenuHandler }
				);
			}

			this._settingMenu = BX.PopupMenu.create(
				this._settingMenuId,
				this._settingButton,
				menuItems,
				{
					autoHide: true,
					offsetLeft: -21,
					offsetTop: -3,
					angle:
					{
						position: "top",
						offset: 42
					},
					events:
					{
						onPopupClose : BX.delegate(this.onSettingMenuClose, this)
					}
				}
		   );
		   this._settingMenu.popupWindow.show();
		   this._isSettingMenuShown = true;
		},
		closeSettingMenu: function()
		{
			if(this._settingMenu && this._settingMenu.popupWindow)
			{
				this._settingMenu.popupWindow.close();
			}
		},
		onSettingMenuClose: function()
		{
			this._settingMenu = null;
			if(typeof(BX.PopupMenu.Data[this._settingMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._settingMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._settingMenuId];
			}
			this._isSettingMenuShown = false;
		},
		onSettingMenuItemClick: function(e, item)
		{
			if(item.id === "add")
			{
				var widgetCount = this.getWidgetCount();
				if(this._maxWidgetCount > 0 && widgetCount >= this._maxWidgetCount)
				{
					alert(this.getMessage("maxWidgetError").replace(/#MAX_WIDGET_COUNT#/gi, this._maxWidgetCount));
				}
				else
				{
					this.openTypeSelector();
				}
			}
			else if(item.id === "layout")
			{
				this.openLayoutTypeSelector();
			}
			else if(item.id === "reset")
			{
				this.resetConfig(function(){ window.location.reload(); });
			}
			else if(item.id === "enabledemomode")
			{
				this.enableDemoMode(true, function(){ window.location.reload(); });
			}
			this.closeSettingMenu();
		},
		onDisableDemoButtonClick: function(e)
		{
			this.enableDemoMode(false, function(){ window.location.reload(); });
		},
		onDemoInfoCloseButtonClick: function()
		{
			if(this._disableDemoModeButton)
			{
				BX.unbind(this._disableDemoModeButton, "click", this._disableDemoButtonClickHandler);
				this._disableDemoModeButton = null;
			}

			if(this._demoModeInfoCloseButton)
			{
				BX.unbind(this._demoModeInfoCloseButton, "click", this._demoModeInfoCloseButtonClickHandler);
				this._demoModeInfoCloseButton = null;
			}

			BX.cleanNode(this._demoModeInfoContainer, true);
			this._demoModeInfoContainer = null;
		}
	};
	if(typeof(BX.CrmWidgetPanel.messages) === "undefined")
	{
		BX.CrmWidgetPanel.messages = {};
	}
	BX.CrmWidgetPanel.isAjaxMode = false;
	BX.CrmWidgetPanel.current = null;
	BX.CrmWidgetPanel.create = function(id, settings)
	{
		var self = new BX.CrmWidgetPanel();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmWidgetLayout) === "undefined")
{
	BX.CrmWidgetLayout =
	{
		undifined: '',
		l70r30: 'L70R30',
		l50r50: 'L50R50',
		l30r70: 'L30R70',
		getAll: function()
		{
			return [ this.l70r30, this.l50r50, this.l30r70 ];
		}
	};
}
if(typeof(BX.CrmWidgetLayoutHeight) === "undefined")
{
	BX.CrmWidgetLayoutHeight =
	{
		undifined: 0,
		full: 380,
		half: 180
	};
}
if(typeof(BX.CrmWidgetExpressionOperation) === "undefined")
{
	BX.CrmWidgetExpressionOperation =
	{
		undefined: '',
		sum: 'SUM',
		diff: 'DIFF',
		percent: 'PC',
		descriptions: {},
		prepareListItems: function()
		{
			var result = [];
			for(var k in this.descriptions)
			{
				if(!this.descriptions.hasOwnProperty(k))
				{
					continue;
				}

				result.push({ value: k, text: this.descriptions[k] });
			}
			return result;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetExpressionOperation.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getDescription: function(operation)
		{
			return this.descriptions.hasOwnProperty(operation) ? this.descriptions[operation] : "";
		},
		getLegend: function(operation)
		{
			operation = operation.toUpperCase();
			if(operation === this.diff)
			{
				return this.getMessage("diffLegend");
			}
			else if(operation === this.sum)
			{
				return this.getMessage("sumLegend");
			}
			else if(operation === this.percent)
			{
				return this.getMessage("percentLegend");
			}
			return "";
		},
		getHint: function()
		{
			return this.getMessage("hint");
		},
		getSign: function(operation)
		{
			operation = operation.toUpperCase();
			if(operation === this.diff)
			{
				return "&#8722;";
			}
			else if(operation === this.sum)
			{
				return "&#43;";
			}
			else if(operation === this.percent)
			{
				return "&#37;";
			}
			return "";
		},
		getIconClassName: function(operation)
		{
			operation = operation.toUpperCase();
			if(operation === this.diff)
			{
				return "action-icon action-icon-minus";
			}
			else if(operation === this.sum)
			{
				return "action-icon action-icon-plus";
			}
			else if(operation === this.percent)
			{
				return "action-icon action-icon-persent";
			}
			return "action-icon";
		},
		getSymbolClassName: function(operation)
		{
			operation = operation.toUpperCase();
			if(operation === this.diff)
			{
				return "symbol symbol-minus";
			}
			else if(operation === this.sum)
			{
				return "symbol symbol-plus";
			}
			else if(operation === this.percent)
			{
				return "symbol symbol-persent";
			}
			return "";
		}
	};
	if(typeof(BX.CrmWidgetExpressionOperation.messages) === "undefined")
	{
		BX.CrmWidgetExpressionOperation.messages = {};
	}
}
if(typeof(BX.CrmWidgetConfigControlMode) === "undefined")
{
	BX.CrmWidgetConfigControlMode =
	{
		undifined: 0,
		view: 1,
		edit: 2
	};
}
if(typeof(BX.CrmWidgetFilterPeriod) === "undefined")
{
	BX.CrmWidgetFilterPeriod =
	{
		undefined: "",
		year: "Y",
		quarter: "Q",
		month: "M",
		currentMonth: "M0",
		currentQuarter: "Q0",
		lastDays90: "D90",
		lastDays60: "D60",
		lastDays30: "D30",
		lastDays7: "D7",

		descriptions: {},
		getDescription: function(typeId)
		{
			return this.descriptions.hasOwnProperty(typeId) ? this.descriptions[typeId] : "";
		},
		prepareListItems: function(aliases, skipUndefined)
		{
			if(!aliases)
			{
				aliases = {};
			}

			skipUndefined = !!skipUndefined;

			var result = [];
			for(var k in this.descriptions)
			{
				if(!this.descriptions.hasOwnProperty(k))
				{
					continue;
				}

				if(k === BX.CrmWidgetFilterPeriod.undefined && skipUndefined)
				{
					continue;
				}

				var text = aliases.hasOwnProperty(k) ? aliases[k] : this.descriptions[k];
				result.push({ value: k, text: text });
			}
			return result;
		}
	}
}
if(typeof(BX.CrmWidgetColorScheme) === "undefined")
{
	BX.CrmWidgetColorScheme =
	{
		undifined: "",
		red: "red",
		green: "green",
		blue: "blue",
		cyan: "cyan",
		yellow: "yellow",
		descriptions: {},
		infos:
		{
			red: { color: "#f02f2f" },
			green: { color: "#05d215" },
			blue: { color: "#4fc3f7" },
			cyan: { color: "#50c5d3" },
			yellow: { color: "#f7d622" }
		},
		getInfo: function(scheme)
		{
			return typeof(this.infos[scheme]) !== "undefined" ? this.infos[scheme] : null;
		},
		prepareMenuItems: function(callback)
		{
			var results = [];
			for(var k in this.infos)
			{
				if(!this.infos.hasOwnProperty(k))
				{
					continue;
				}

				var color = this.infos[k]["color"];
				var descr = this.descriptions[k];
				results.push(
					{
						id: k,
						text: '<span class="color-item"><span style="background: ' + color + ';" class="color"></span>' + descr + '</span>',
						onclick: callback
					}
				);
			}
			return results;
		}
	}
}
if(typeof(BX.CrmWidgetDataGrouping) === "undefined")
{
	BX.CrmWidgetDataGrouping =
	{
		undefined: '',
		date: 'DATE',
		user: 'USER',
		descriptions: {},
		prepareListItems: function(enableUndefined)
		{
			enableUndefined = !!enableUndefined;
			var result = [];
			for(var k in this.descriptions)
			{
				if(!this.descriptions.hasOwnProperty(k))
				{
					continue;
				}

				if(k !== this.undefined || enableUndefined)
				{
					result.push({ value: k, text: this.descriptions[k] });
				}
			}
			return result;
		}
	}
}
if(typeof(BX.CrmWidgetDataContext) === "undefined")
{
	BX.CrmWidgetDataContext =
	{
		undefined: "",
		entity: "E",
		fund: "F",
		percent: "P",
		descriptions: {},
		prepareListItems: function()
		{
			var result = [];
			for(var k in this.descriptions)
			{
				if(!this.descriptions.hasOwnProperty(k))
				{
					continue;
				}

				result.push({ value: k, text: this.descriptions[k] });
			}
			return result;
		}
	}
}
if(typeof(BX.CrmPhaseSemantics) === "undefined")
{
	BX.CrmPhaseSemantics =
	{
		undefined: '',
		process: 'P',
		success: 'S',
		failure: 'F',
		descriptions: {},
		detailedInfos: {},
		getCaption: function(entityTypeName)
		{
			return (BX.type.isNotEmptyString(entityTypeName)
				&& BX.type.isPlainObject(this.detailedInfos[entityTypeName])
				&& BX.type.isNotEmptyString(this.detailedInfos[entityTypeName]["caption"])
				? this.detailedInfos[entityTypeName]["caption"]
				: "");
		},
		getSelectorTitle: function(entityTypeName)
		{
			return (BX.type.isNotEmptyString(entityTypeName)
				&& BX.type.isPlainObject(this.detailedInfos[entityTypeName])
				&& BX.type.isNotEmptyString(this.detailedInfos[entityTypeName]["selectorTitle"])
				? this.detailedInfos[entityTypeName]["selectorTitle"]
				: "");
		},
		getGroupTitle: function(entityTypeName)
		{
			return (BX.type.isNotEmptyString(entityTypeName)
				&& BX.type.isPlainObject(this.detailedInfos[entityTypeName])
				&& BX.type.isNotEmptyString(this.detailedInfos[entityTypeName]["groupTitle"])
				? this.detailedInfos[entityTypeName]["groupTitle"]
				: "");
		},
		prepareListItems: function(entityTypeName)
		{
			var descriptions = BX.type.isNotEmptyString(entityTypeName)
				&& BX.type.isPlainObject(this.detailedInfos[entityTypeName])
				&& BX.type.isPlainObject(this.detailedInfos[entityTypeName]["descriptions"])
				? this.detailedInfos[entityTypeName]["descriptions"]
				: this.descriptions;

			var result = [];
			for(var k in descriptions)
			{
				if(!descriptions.hasOwnProperty(k))
				{
					continue;
				}

				result.push({ value: k, text: descriptions[k] });
			}
			return result;
		}
	}
}
if(typeof(BX.CrmWidgetManager) === "undefined")
{
	BX.CrmWidgetManager = function()
	{
		this._requestQueue = null;
		this._isRequestRunning = false;
	};
	BX.CrmWidgetManager.prototype =
	{
		initialize: function()
		{
			this._requestQueue = [];
		},
		createWidget: function(id, settings)
		{
			settings = settings ? settings : {};
			var config = settings.hasOwnProperty("config") ? settings["config"] : {};
			var typeName = config.hasOwnProperty("typeName") ? config["typeName"] : "";
			if(typeName === "")
			{
				throw "CrmWidgetManager: The type name is not found.";
			}

			if(typeName === "funnel")
			{
				return BX.CrmFunnelWidget.create(id, settings);
			}
			else if(typeName === "pie")
			{
				return BX.CrmPieWidget.create(id, settings);
			}
			else if(typeName === "graph" || typeName === "bar")
			{
				return BX.CrmGraphWidget.create(id, settings);
			}
			else if(typeName === "number")
			{
				return BX.CrmNumericWidget.create(id, settings);
			}
			else if(typeName === "rating")
			{
				return BX.CrmRatingWidget.create(id, settings);
			}
			return BX.CrmWidget.create(id, settings);
		},
		prepareWidgetData: function(widget)
		{
			this.addToQueueItem(
				widget,
				"PREPARE_DATA",
				{ "CONTROL": widget.getConfig(), "FILTER": BX.CrmWidgetManager.filter }
			);

			this.processQueue();
		},
		addToQueueItem: function(widget, action, params)
		{
			var guid = widget.getId() + "_"+ BX.util.getRandomString(8).toLowerCase();
			this._requestQueue.push({ guid: guid, widget: widget, action: action, params: params });
		},
		getQueueItem: function(guid)
		{
			for(var i = 0; i < this._requestQueue.length; i++)
			{
				if(this._requestQueue[i]["guid"] === guid)
				{
					return this._requestQueue[i];
				}
			}
			return null;
		},
		removeQueueItem: function(item)
		{
			for(var i = 0; i < this._requestQueue.length; i++)
			{
				if(this._requestQueue[i] === item)
				{
					this._requestQueue.splice(i, 1);
					return true;
				}
			}
			return false;
		},
		processQueue: function()
		{
			if(this._isRequestRunning || this._requestQueue.length === 0)
			{
				return;
			}

			var queueItem = this._requestQueue[0];
			var params = queueItem["params"];
			params["GUID"] = queueItem["guid"];
			this._startRequest(queueItem["action"], params);
		},
		_startRequest: function(action, params)
		{
			if(this._isRequestRunning)
			{
				return false;
			}

			var serviceUrl = BX.CrmWidgetManager.serviceUrl;
			if(!BX.type.isNotEmptyString(serviceUrl))
			{
				throw "CrmWidgetManager: Could no start request. The fild 'serviceUrl' is not assigned.";
			}

			this._isRequestRunning = true;
			BX.showWait();
			BX.ajax(
				{
					url: serviceUrl,
					method: "POST",
					dataType: "json",
					data: { "ACTION" : action, "PARAMS": params },
					onsuccess: BX.delegate(this._onRequestSuccess, this),
					onfailure: BX.delegate(this._onRequestFailure, this)
				}
			);

			return true;
		},
		_onRequestSuccess: function(data)
		{
			this._isRequestRunning = false;
			BX.closeWait();

			if(!BX.type.isPlainObject(data["RESULT"]))
			{
				return;
			}

			var result = data["RESULT"];
			var queueItem = BX.type.isNotEmptyString(result["GUID"]) ? this.getQueueItem(result["GUID"]) : null;
			if(!queueItem)
			{
				return;
			}

			if(BX.type.isPlainObject(result["DATA"]))
			{
				queueItem["widget"].setData(result["DATA"]);
				queueItem["widget"].refresh();
			}

			this.removeQueueItem(queueItem);
		},
		_onRequestFailure: function(data)
		{
			this._isRequestRunning = false;
			BX.closeWait();
		}
	};

	BX.CrmWidgetManager.serviceUrl = "";
	BX.CrmWidgetManager.current = null;
	BX.CrmWidgetManager.getCurrent = function()
	{
		if(!this.current)
		{
			this.current = new BX.CrmWidgetManager();
			this.current.initialize();
		}
		return this.current;
	}
}
if(typeof(BX.CrmWidgetDragDropController) === "undefined")
{
	BX.CrmWidgetDragDropController = function()
	{
		this._id = "";
		this._settings = null;
		this.panel = null;
		this.wrapper = null;
		this.dropZoneListObj = {};
		this.dropZoneList = [];
		this.dropZoneCounter = 0;
		this.dropZoneActiveClass = "crm-widget-catcher-inner-active";
		this.activeDragRow = null;
		this.prevEventPosX = 0;
		this.prevEventPosY = 0;
		this.isDropBlockShow = false;
	};
	BX.CrmWidgetDragDropController.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};
			this.panel = settings.panel;
			var rows = settings.rows;
			for(var i = 0; i < rows.length; i++)
			{
				var row = rows[i];
				row.getContainer().setAttribute("data-row-id", row.getId());
			}

			this.wrapper = settings.wrapper;
			this.ddPlaceBlock  = this.createDropZoneBlock();
			this.crmDnD =  BX.DragDrop.create(
				{
					dragItemClassName: "crm-widget",
					dropZoneList: this.dropZoneList,
					dragActiveClass: "crm-widget-drag-active",
					drag: BX.delegate(this.drag, this),
					dragStart: BX.delegate(this.dragStart, this),
					dragDrop:  BX.delegate(this.dragDrop, this),
					dragEnter: BX.delegate(this.dragEnter, this),
					dragLeave: BX.delegate(this.dragLeave, this),
					dragEnd: BX.delegate(this.dragEnd, this),
					sortable: { rootElem : settings.wrapper, className : "crm-widget-row", node : this.ddPlaceBlock }
				}
			);

			this.initializeCellDropZones();
		},
		initializeCellDropZones: function()
		{
			var cells = this.panel.getThinCells();
			for(var i = 0; i < cells.length; i++)
			{
				var cell = cells[i];
				var cellHeight = cell.getWidgetTotalHeight();
				if(cellHeight > BX.CrmWidgetLayoutHeight.half)
				{
					continue;
				}

				var row = cell.getRow();
				var height = cellHeight > 0 ? BX.CrmWidgetLayoutHeight.half : row.getHeight();
				var dropZone = this.createDropZone(
					{
						parentRowId : row.getId(),
						parentCellId : cell.getId(),
						height : height,
						htmlHeight : height,
						position: row.getCellCount() > 1 ? (row.getCellIndex(cell) > 0 ? "right" : "left") : "wide"
					}
				);

				dropZone.style.display = "none";
				cell.getContainer().appendChild(dropZone);
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getRowInfo: function(id)
		{
			var result = { height: 0, node: null, index: 0, chartsCount: 0 };
			var row = this.panel.getRowById(id);
			if(row)
			{
				result.height = row.getHeight();
				result.node = row.getContainer();
				result.index = row.getIndex();
				result.chartsCount = row.getWidgetCount();
			}
			return result;
		},
		getWidgetInfo: function(id)
		{
			var result =
				{ height: 0, node: null, parentRowId: "", parentCellId: "", rowIndex: -1, cellIndex: -1, index: -1 };
			var widget = this.panel.getWidgetById(id);
			if(widget)
			{
				var cell = widget.getCell();
				var row = cell.getRow();

				result.height = widget.getHeight();
				result.node = widget.getWrapper();
				result.parentRowId = row.getId();
				result.parentCellId = cell.getId();
				result.rowIndex = row.getIndex();
				result.cellIndex = cell.getIndex();
				result.index = widget.getIndex();
			}
			return result;
		},
		createDropZone: function(params)
		{
			var position = params.position || null,
				parentRowId = params.parentRowId || null,
				parentCellId = params.parentCellId || null,
				height = params.height,
				htmlHeight = params.htmlHeight;

			var id = 'dropZone-' + this.dropZoneCounter;
			this.dropZoneCounter++;

			var htmlDropZone = BX.create(
				'div',
				{
					props: { className: 'crm-widget-catcher-inner' },
					attrs: { 'data-dropZone-id': id },
					style: { height :htmlHeight + 'px' }
				}
			);

			this.dropZoneListObj[id] =
			{
				id: id,
				node: htmlDropZone,
				position: position,
				parentRowId: parentRowId,
				parentCellId: parentCellId,
				height: height
			};

			if(parentRowId)
				this.crmDnD.addCatcher(htmlDropZone);

			if(position)
				this.dropZoneList.push(htmlDropZone);

			return htmlDropZone;
		},
		createDropZoneBlock: function()
		{
			return BX.create('div', {
				props:{className:'crm-widget-catcher-wrap'},
				children : [
						BX.create('div',{
									props:{className: 'crm-widget-catcher crm-widget-left'},
									children :[this.createDropZone({
										position : 'left',
										height : BX.CrmWidgetLayoutHeight.full,
										htmlHeight : 55
									})]
						}),
						BX.create('div',{
									props:{className: 'crm-widget-catcher crm-widget-right'},
									children :[this.createDropZone({
										position : 'right',
										height : BX.CrmWidgetLayoutHeight.full,
										htmlHeight : 55
									})]
						}),
						BX.create('div',{
									props:{className: 'crm-widget-catcher crm-widget-bottom'},
									children :[this.createDropZone({
										position : 'wide',
										height : BX.CrmWidgetLayoutHeight.full,
										htmlHeight : 55
									})]
						})
				]
			});
		},
		showDropZone: function(dropzoneHeight)
		{
			for(var b in this.dropZoneListObj)
			{
				if(dropzoneHeight == BX.CrmWidgetLayoutHeight.half && this.dropZoneListObj[b].height <= BX.CrmWidgetLayoutHeight.full)
					this.dropZoneListObj[b].node.style.display = 'block';
				else if(this.dropZoneListObj[b].height == BX.CrmWidgetLayoutHeight.full)
					this.dropZoneListObj[b].node.style.display = 'block';
			}
		},
		hideDropZone: function()
		{
			for(var b in this.dropZoneListObj)
			{
				BX.removeClass(this.dropZoneListObj[b].node, this.dropZoneActiveClass);
				this.dropZoneListObj[b].node.style.display = 'none';
			}
		},
		showDropBlock: function(elem)
		{
			this.ddPlaceBlock.style.display = "block";
			this.wrapper.insertBefore(this.ddPlaceBlock, elem);

			setTimeout(BX.delegate(function(){ this.ddPlaceBlock.style.height = 133 + "px"; }, this), 50);
		},
		hideDropBlock: function()
		{
			this.ddPlaceBlock.style.height = 0;
			this.isDropBlockShow = false;

			setTimeout(BX.delegate(function(){ this.wrapper.removeChild(this.ddPlaceBlock); }, this), 300);
		},
		showNewRow: function(rowId)
		{
			var rowObj = this.getRowInfo(rowId);
			this.wrapper.insertBefore(rowObj.node, this.ddPlaceBlock);
			rowObj.node.setAttribute("data-row-id", rowId);
			rowObj.node.style.opacity = 0;
			rowObj.node.style.height = 133 + "px";

			this.ddPlaceBlock.style.display = "none";
			setTimeout(
				BX.delegate(
					function()
					{
						rowObj.node.style.height = rowObj.height + "px";
						rowObj.node.style.opacity = 1;
					}
				),
				50
			);
		},
		getCellDropZone: function(cellId)
		{
			for(var k in this.dropZoneListObj)
			{
				if(!this.dropZoneListObj.hasOwnProperty(k))
				{
					continue;
				}

				var dropZone = this.dropZoneListObj[k];
				if(dropZone.parentCellId === cellId)
				{
					return dropZone;
				}
			}
			return null;
		},
		registerRow: function(row)
		{
			var container = row.getContainer();
			container.setAttribute("data-row-id", row.getId());
			this.crmDnD.addSortableItem(container);

			var cells = row.getCells();
			for(var i = 0; i < cells.length; i++)
			{
				var widgets = cells[i].getWidgets();
				for(var j = 0; j < widgets.length; j++)
				{
					this.crmDnD.addDragItem([ widgets[j].getWrapper() ]);
				}
			}

			this.processRowChange(row);
		},
		processWidgetChange: function(widget)
		{
			this.crmDnD.addDragItem([ widget.getWrapper() ]);
		},
		processRowChange: function(row)
		{
			if(row.isEmpty())
			{
				this.crmDnD.removeSortableItem(row.getContainer());
				this.panel.removeRow(row);

				return;
			}

			var i = 0, cell = null, cellHeight = 0, maxCellHeight = 0;
			var cells = row.getCells();
			var cellQty = cells.length;
			for(i = 0; i < cellQty; i++)
			{
				cellHeight = cells[i].getWidgetTotalHeight();
				if(maxCellHeight < cellHeight)
				{
					maxCellHeight = cellHeight;
				}
			}

			var rowHeight = row.getHeight();
			if(rowHeight !== maxCellHeight)
			{
				rowHeight = maxCellHeight > BX.CrmWidgetLayoutHeight.half
					? BX.CrmWidgetLayoutHeight.full : BX.CrmWidgetLayoutHeight.half;
				row.setHeight(rowHeight);
			}

			for(i = 0; i < cellQty; i++)
			{
				cell = cells[i];
				cellHeight = cell.getWidgetTotalHeight();
				var dropZoneObj = this.getCellDropZone(cell.getId());
				var enableDropZone = (rowHeight - cellHeight) >= BX.CrmWidgetLayoutHeight.half;
				if(!enableDropZone && dropZoneObj)
				{
					delete this.dropZoneListObj[dropZoneObj.id];
					this.crmDnD.removeCatcher(dropZoneObj.node);
					BX.cleanNode(dropZoneObj.node, true);
				}
				else if(enableDropZone)
				{
					var height = cellHeight === 0 ? rowHeight : BX.CrmWidgetLayoutHeight.half;
					if(dropZoneObj)
					{
						delete this.dropZoneListObj[dropZoneObj.id];
						this.crmDnD.removeCatcher(dropZoneObj.node);
						BX.cleanNode(dropZoneObj.node, true);
					}

					var dropZoneNode = this.createDropZone(
						{
							parentRowId : row.getId(),
							parentCellId: cell.getId(),
							height : height,
							htmlHeight : height,
							position: cellQty > 1 ? (cell.getIndex() > 0 ? "right" : "left") : "wide"
						}
					);

					dropZoneNode.style.display = "none";
					cell.getContainer().appendChild(dropZoneNode);
				}
			}
		},
		dragStart: function(params)
		{
			var objKey = params.dragElement.getAttribute('data-widget-id');
			var widgetInfo = this.getWidgetInfo(objKey);

			var rootRowKey = widgetInfo.parentRowId;
			var rootRow = this.getRowInfo(rootRowKey).node;
			var height = widgetInfo.height;

			this.showDropZone(height);

			this.activeDragRow = rootRow;
			this.prevEventPosY = params.event.clientY;
			this.prevEventPosX = params.event.clientX;
		},
		drag : function (dragNode,  dropZoneBlock, event)
		{
			this.dragClientY = event.clientFFY || event.clientY;

			if(this.dragClientY > this.prevEventPosY && !this.isDropBlockShow)
			{
				this.showDropBlock(this.activeDragRow.nextSibling);
				this.isDropBlockShow = true;
			}
			else if (this.dragClientY < this.prevEventPosY && !this.isDropBlockShow)
			{
				this.showDropBlock(this.activeDragRow);
				this.isDropBlockShow = true;
			}

			this.prevEventPosY = this.dragClientY;
		},
		dragDrop: function(dropZone, widget)
		{
			var dropZoneId = dropZone.getAttribute("data-dropZone-id");
			var dropZoneObj = this.dropZoneListObj[dropZoneId];

			var widgetId = widget.getAttribute("data-widget-id");
			var widgetItem = this.panel.getWidgetById(widgetId);

			var newRowItem = null;
			var newCellIndex = 0;
			var newIndex = 0;

			if (dropZoneObj.parentRowId)
			{
				newRowItem = this.panel.getRowById(dropZoneObj.parentRowId);
				newCellIndex = dropZoneObj.position === "right" ? 1 : 0;
				newIndex = BX.CrmWidgetPanelCell.getItemWidgetCount(newRowItem.getCellByIndex(newCellIndex));
			}
			else if(dropZoneObj.position)
			{
				var rowIndex = this.panel.getRowCount();
				var nextRowNode = BX.findNextSibling(this.ddPlaceBlock, { tagName: "DIV", className: "crm-widget-row" });
				if(nextRowNode)
				{
					rowIndex = this.panel.getRowById(nextRowNode.getAttribute("data-row-id")).getIndex();
				}

				newRowItem = this.panel.createRow(
					{
						index: rowIndex,
						height: widgetItem.getHeight(),
						cellCount: dropZoneObj.position === "wide" ? 1 : 2
					}
				);
				newCellIndex = dropZoneObj.position === "right" ? 1 : 0;
				newIndex = 0;

				var newRowId = newRowItem.getId();
				this.crmDnD.addSortableItem(newRowItem.getContainer());
				this.showNewRow(newRowId);
			}

			var prevRowItem = widgetItem.getRow();

			this.panel.moveWidget(widgetItem, newRowItem, newCellIndex, newIndex);
			this.crmDnD.addDragItem([widgetItem.getWrapper()]);

			newRowItem = widgetItem.getRow();
			this.processRowChange(prevRowItem);
			if(newRowItem !== prevRowItem)
			{
				this.processRowChange(newRowItem);
			}

			this.panel.saveConfig();
		},
		dragEnter: function (dropZone)
		{
			BX.addClass(dropZone, this.dropZoneActiveClass);
		},
		dragLeave: function (dropZone)
		{
			BX.removeClass(dropZone, this.dropZoneActiveClass);
		},
		dragEnd: function()
		{
			this.hideDropZone();
			this.hideDropBlock();
			this.isDropBlockShow = false;
		}
	};
	BX.CrmWidgetDragDropController.items = {};
	BX.CrmWidgetDragDropController.create = function(id, settings)
	{
		var self = new BX.CrmWidgetDragDropController();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
if(typeof(BX.CrmWidgetTypeSelector) === "undefined")
{
	BX.CrmWidgetTypeSelector = function()
	{
		this._id = "";
		this._settings = null;
		this._currentEntityTypeName = "";
		this._entityTypeNames = null;
		this._tabs = null;
		this._widgetTypeItems = null;
		this._widgetTypeContainer = null;
		this._tabSelectionHandler = BX.delegate(this.onTabSelection, this);
		this._widgetTypeSelectionHandler = BX.delegate(this.onWidgetTypeSelection, this);

		this._callback = null;
		this._dlg = null;
	};
	BX.CrmWidgetTypeSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._entityTypeNames = this.getSetting("entityTypeNames", []);
			this._currentEntityTypeName = this._entityTypeNames.length > 0 ? this._entityTypeNames[0] : "";
			this._callback = this.getSetting("callback");
			if(!BX.type.isFunction(this._callback))
			{
				this._callback = null;
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
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetTypeSelector.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		isEntityTypeEnabled: function(entityTypeName)
		{
			for(var i = 0; i < this._entityTypeNames.length; i++)
			{
				if(this._entityTypeNames[i] === entityTypeName)
				{
					return true;
				}
			}
			return false;
		},
		openDialog: function()
		{
			if(this._dlg)
			{
				return;
			}

			var dlgId = this._id;
			this._dlg = new BX.PopupWindow(
				dlgId,
				null,
				{
					autoHide: false,
					draggable: true,
					bindOptions: { forceBindPosition: false },
					closeByEsc: false,
					closeIcon: { top: "10px", right: "15px" },
					zIndex: 0,
					titleBar: { content: this.prepareDialogTitle() },
					content: this.prepareDialogContent(),
					buttons:
					[
						/*new BX.PopupWindowButton(
							{
								text: this.getMessage("dialogSaveButton"),
								className: "popup-window-button-accept",
								events: { click : BX.delegate(this.onDialogAcceptButtonClick, this) }
							}
						),*/
						new BX.PopupWindowButtonLink(
							{
								text: this.getMessage("dialogCancelButton"),
								className: 'popup-window-button-link-cancel',
								events: { click : BX.delegate(this.onDialogCancelButtonClick, this) }
							}
						)
					],
					events:
					{
						onPopupShow: BX.delegate(this.onDialogShow, this),
						onPopupClose: BX.delegate(this.onDialogClose, this),
						onPopupDestroy: BX.delegate(this.onDialogDestroy, this)
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
		isDialogOpened: function()
		{
			return this._dlg && this._dlg.isShown();
		},
		prepareDialogTitle: function()
		{
			return BX.create("SPAN",
				{
					attrs: { className: "bx-lists-dialog-title" },
					text: this.getMessage("dialogTitle")
				}
			);
		},
		prepareDialogContent: function()
		{
			var wrapper = BX.create("DIV", { attrs: { className: "view-report-wrapper-container" } });
			var sidebar = BX.create("DIV", { attrs: { className: "view-report-sidebar" } });
			wrapper.appendChild(sidebar);

			this._tabs = [];
			for(var i = 0; i < BX.CrmWidgetTypeSelector.entityTypeInfos.length; i++)
			{
				var entityTypeInfo = BX.CrmWidgetTypeSelector.entityTypeInfos[i];
				var entityTypeName = BX.type.isNotEmptyString(entityTypeInfo["name"]) ? entityTypeInfo["name"] : "";
				if(entityTypeName === "")
				{
					continue;
				}

				var entityTypeDescription = BX.type.isNotEmptyString(entityTypeInfo["description"]) ? entityTypeInfo["description"] : "";
				if(entityTypeDescription === "")
				{
					entityTypeDescription = entityTypeName;
				}

				var isActive = this.isEntityTypeEnabled(entityTypeName);
				var entityTypeTab = BX.CrmWidgetPanelConfiguratorTab.create(
					entityTypeName,
					{
						title: entityTypeDescription,
						isActive: isActive,
						className: isActive ? "sidebar-tab-active" : "sidebar-tab-disabled",
						container: sidebar,
						configurator: this
					}
				);
				this._tabs.push(entityTypeTab);
				entityTypeTab.addSelectionListener(this._tabSelectionHandler);
				entityTypeTab.layout();
			}

			var contentWrapper = BX.create("DIV", { attrs: { className: "view-report-wrapper view-report-wrapper-popup" } });
			wrapper.appendChild(contentWrapper);

			this._widgetTypeContainer = BX.create("DIV", { attrs: { className: "view-report-wrapper-inner" } });
			contentWrapper.appendChild(this._widgetTypeContainer);

			this._widgetTypeItems = [];
			for(var j = 0; j < BX.CrmWidgetTypeSelector.infos.length; j++)
			{
				var info = BX.CrmWidgetTypeSelector.infos[j];
				var widgetTypeItem = BX.CrmWidgetTypeSelectorItem.create(
					info["name"],
					{ info: info, wrapper: this._widgetTypeContainer, configurator: this }
				);
				this._widgetTypeItems.push(widgetTypeItem);
				widgetTypeItem.layout();
				widgetTypeItem.addSelectionListener(this._widgetTypeSelectionHandler);
			}

			return wrapper;
		},
		onDialogShow: function()
		{
		},
		onDialogClose: function()
		{
			if(BX.type.isArray(this._tabs))
			{
				for(var i = 0; i < this._tabs.length; i++)
				{
					var tab = this._tabs[i];
					tab.removeSelectionListener(this._tabSelectionHandler);
					tab.cleanLayout();
				}
			}
			this._tabs = null;

			if(this._dlg)
			{
				this._dlg.destroy();
			}
		},
		onDialogDestroy: function()
		{
			this._dlg = null;
		},
		/*onDialogAcceptButtonClick: function()
		{
			this.closeDialog();
		},*/
		onDialogCancelButtonClick: function()
		{
			this.closeDialog();
		},
		onTabSelection: function(sender)
		{
			var tabId = sender.getId();
			if(tabId === "LAYOUT")
			{
				this._widgetTypeContainer.style.display = "none";
				this._layoutTypeContainer.style.display = "";
			}
			else
			{
				this._layoutTypeContainer.style.display = "none";
				this._widgetTypeContainer.style.display = "";

				this._currentEntityTypeName = tabId;
			}
		},
		onWidgetTypeSelection: function(sender)
		{
			if(!this._callback)
			{
				return;
			}

			var info = sender.getInfo();
			var params = BX.clone(info["params"]);
			params["entityTypeName"] = this._currentEntityTypeName;
			this._callback(this, { action: "addItem", data: { params: params } });
		}
	};
	if(typeof(BX.CrmWidgetTypeSelector.messages) === "undefined")
	{
		BX.CrmWidgetTypeSelector.messages = {};
	}
	if(typeof(BX.CrmWidgetTypeSelector.entityTypeInfos) === "undefined")
	{
		BX.CrmWidgetTypeSelector.entityTypeInfos = [];
	}
	if(typeof(BX.CrmWidgetTypeSelector.infos) === "undefined")
	{
		BX.CrmWidgetTypeSelector.infos = [];
	}

	BX.CrmWidgetTypeSelector.create = function(id, settings)
	{
		var self = new BX.CrmWidgetTypeSelector();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmWidgetTypeSelectorItem) === "undefined")
{
	BX.CrmWidgetTypeSelectorItem = function()
	{
		this._id = "";
		this._settings = null;
		this._info = null;
		this._configurator = null;

		this._wrapper = null;
		this._container = null;
		this._clickHandler = BX.delegate(this.onClick, this);
		this._selectionNotifier = null;

		this._hasLayout = false;
	};
	BX.CrmWidgetTypeSelectorItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._info = this.getSetting("info");
			if(!this._info)
			{
				throw "CrmWidgetTypeSelectorItem: The 'info' parameter is not found.";
			}

			this._configurator = this.getSetting("configurator");
			if(!this._configurator)
			{
				throw "CrmWidgetTypeSelectorItem: The 'configurator' parameter is not found.";
			}

			this._wrapper = this.getSetting("wrapper");
			if(!this._wrapper)
			{
				throw "CrmWidgetTypeSelectorItem: The 'wrapper' parameter is not found.";
			}

			this._selectionNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getInfo: function()
		{
			return this._info;
		},
		addSelectionListener: function(listener)
		{
			this._selectionNotifier.addListener(listener);
		},
		removeSelectionListener: function(listener)
		{
			this._selectionNotifier.removeListener(listener);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._container = BX.create("DIV",
				{
					attrs: { className: "view-report-container" },
					children:
					[
						BX.create("DIV",
							{
								attrs: { className: "view-report" },
								children:
								[
									BX.create("IMG", { props: { src: this._info["logo"] } })
								]
							}
						),
						BX.create("DIV",
							{
								attrs: { className: "view-report-title" },
								text: this._info["title"]
							}
						)
					]
				}
			);

			BX.bind(this._container, "click", this._clickHandler);
			this._wrapper.appendChild(this._container);

			this._hasLayout = true;
		},
		cleanLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			BX.unbind(this._container, "click", this._clickHandler);
			BX.cleanNode(this._container, true);
			this._container = null;
			this._hasLayout = false;
		},
		onClick: function(e)
		{
			this._selectionNotifier.notify();
			return BX.PreventDefault(e);
		}
	};
	BX.CrmWidgetTypeSelectorItem.create = function(id, settings)
	{
		var self = new BX.CrmWidgetTypeSelectorItem();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmWidgetPanelLayoutTypeSelector) === "undefined")
{
	BX.CrmWidgetPanelLayoutTypeSelector = function()
	{
		this._id = "";
		this._settings = null;
		this._layoutTypeItems = null;
		this._layoutType = BX.CrmWidgetLayout.undifined;
		this._layoutTypeContainer = null;
		this._layoutTypeSelectionHandler = BX.delegate(this.onLayoutTypeSelection, this);

		this._callback = null;
		this._dlg = null;
	};
	BX.CrmWidgetPanelLayoutTypeSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._layoutType = this.getSetting("layoutType", BX.CrmWidgetLayout.l50r50);

			this._callback = this.getSetting("callback");
			if(!BX.type.isFunction(this._callback))
			{
				this._callback = null;
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
		getMessage: function(name)
		{
			var msg = BX.CrmWidgetPanelLayoutTypeSelector.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		openDialog: function()
		{
			if(this._dlg)
			{
				return;
			}

			var dlgId = this._id;
			this._dlg = new BX.PopupWindow(
				dlgId,
				null,
				{
					autoHide: false,
					draggable: true,
					bindOptions: { forceBindPosition: false },
					closeByEsc: false,
					closeIcon: { top: "10px", right: "15px" },
					zIndex: 0,
					titleBar: { content: this.prepareDialogTitle() },
					content: this.prepareDialogContent(),
					buttons:
					[
						/*new BX.PopupWindowButton(
							{
								text: this.getMessage("dialogSaveButton"),
								className: "popup-window-button-accept",
								events: { click : BX.delegate(this.onDialogAcceptButtonClick, this) }
							}
						),*/
						new BX.PopupWindowButtonLink(
							{
								text: this.getMessage("dialogCancelButton"),
								className: 'popup-window-button-link-cancel',
								events: { click : BX.delegate(this.onDialogCancelButtonClick, this) }
							}
						)
					],
					events:
					{
						onPopupShow: BX.delegate(this.onDialogShow, this),
						onPopupClose: BX.delegate(this.onDialogClose, this),
						onPopupDestroy: BX.delegate(this.onDialogDestroy, this)
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
		isDialogOpened: function()
		{
			return this._dlg && this._dlg.isShown();
		},
		prepareDialogTitle: function()
		{
			return BX.create("SPAN",
				{
					attrs: { className: "bx-lists-dialog-title" },
					text: this.getMessage("dialogTitle")
				}
			);
		},
		prepareDialogContent: function()
		{
			var wrapper = BX.create("DIV", { attrs: { className: "view-report-wrapper-container" } });
			var contentWrapper = BX.create("DIV", { attrs: { className: "view-report-wrapper view-report-wrapper-popup" } });
			wrapper.appendChild(contentWrapper);

			this._layoutTypeContainer = BX.create("DIV", { attrs: { className: "view-report-wrapper-inner" } });
			contentWrapper.appendChild(this._layoutTypeContainer);

			this._layoutTypeItems = [];
			var layoutTypes = BX.CrmWidgetLayout.getAll();
			for(var k = 0; k < layoutTypes.length; k++)
			{
				var layoutType = layoutTypes[k];
				var layoutTypeItem = BX.CrmWidgetPanelLayoutTypeItem.create(
					layoutType,
					{
						layoutType: layoutType,
						isActive: this._layoutType === layoutType,
						container: this._layoutTypeContainer,
						configurator: this
					}
				);
				this._layoutTypeItems.push(layoutTypeItem);
				layoutTypeItem.layout();
				layoutTypeItem.addSelectionListener(this._layoutTypeSelectionHandler);
			}
			return wrapper;
		},
		onDialogShow: function()
		{
		},
		onDialogClose: function()
		{
			if(this._dlg)
			{
				this._dlg.destroy();
			}
		},
		onDialogDestroy: function()
		{
			this._dlg = null;
		},
		/*onDialogAcceptButtonClick: function()
		{
			this.closeDialog();
		},*/
		onDialogCancelButtonClick: function()
		{
			this.closeDialog();
		},
		onLayoutTypeSelection: function(sender)
		{
			if(!this._callback)
			{
				return;
			}

			this._layoutType = sender.getLayoutType();
			this._callback(this, { action: "changeLayout", data: { layoutType: this._layoutType } });
		}
	};
	if(typeof(BX.CrmWidgetPanelLayoutTypeSelector.messages) === "undefined")
	{
		BX.CrmWidgetPanelLayoutTypeSelector.messages = {};
	}
	BX.CrmWidgetPanelLayoutTypeSelector.create = function(id, settings)
	{
		var self = new BX.CrmWidgetPanelLayoutTypeSelector();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmWidgetPanelLayoutTypeItem) === "undefined")
{
	BX.CrmWidgetPanelLayoutTypeItem = function()
	{
		this._id = "";
		this._settings = null;
		this._layoutType = BX.CrmWidgetLayout.undifined;
		this._container = null;
		this._configurator = null;
		this._isActive = false;

		this._clickHandler = BX.delegate(this.onClick, this);
		this._selectionNotifier = null;
		this._hasLayout = false;
	};
	BX.CrmWidgetPanelLayoutTypeItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._layoutType = this.getSetting("layoutType");
			if(this._layoutType === BX.CrmWidgetLayout.undifined)
			{
				throw "CrmWidgetPanelLayoutTypeItem: The 'layoutType' parameter is not found.";
			}

			this._container = this.getSetting("container");
			if(!this._container)
			{
				throw "CrmWidgetPanelLayoutTypeItem: The 'container' parameter is not found.";
			}

			this._configurator = this.getSetting("configurator");
			if(!this._configurator)
			{
				throw "CrmWidgetPanelLayoutTypeItem: The 'configurator' parameter is not found.";
			}

			this._isActive = this.getSetting("isActive", false);

			this._selectionNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getLayoutType: function()
		{
			return this._layoutType;
		},
		addSelectionListener: function(listener)
		{
			this._selectionNotifier.addListener(listener);
		},
		removeSelectionListener: function(listener)
		{
			this._selectionNotifier.removeListener(listener);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			var className = "crm-widget-template";
			if(this._isActive)
			{
				className += " crm-widget-template-active";
			}

			this._wrapper = BX.create("DIV", { attrs: { className: className } });
			this._container.appendChild(this._wrapper);

			var leftFactor = 5;
			var rightFactor = 5;
			if(this._layoutType === BX.CrmWidgetLayout.l30r70)
			{
				leftFactor = 3;
				rightFactor = 7;
			}
			else if(this._layoutType === BX.CrmWidgetLayout.l70r30)
			{
				leftFactor = 7;
				rightFactor = 3;
			}

			this._wrapper.appendChild(this.prepareTileLayout(leftFactor));
			this._wrapper.appendChild(this.prepareTileLayout(rightFactor));

			this._wrapper.appendChild(BX.create("BR"));

			this._wrapper.appendChild(this.prepareTileLayout(10));

			this._wrapper.appendChild(BX.create("BR"));

			this._wrapper.appendChild(this.prepareTileLayout(leftFactor));
			this._wrapper.appendChild(this.prepareTileLayout(rightFactor));

			BX.bind(this._wrapper, "click", this._clickHandler);

			this._hasLayout = true;
		},
		prepareTileLayout: function(factor)
		{
			var className = "crm-widget-template-item";
			if(factor < 10)
			{
				className += " template-item-" + factor.toString();
			}

			return BX.create("DIV", { attrs: { className: className }, text: (factor * 10).toString() + "%" });
		},
		cleanLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			BX.unbind(this._wrapper, "click", this._clickHandler);
			BX.cleanNode(this._wrapper, true);
			this._wrapper = null;

			this._hasLayout = false;
		},
		onClick: function(e)
		{
			this._selectionNotifier.notify();
			return BX.PreventDefault(e);
		}
	};
	BX.CrmWidgetPanelLayoutTypeItem.create = function(id, settings)
	{
		var self = new BX.CrmWidgetPanelLayoutTypeItem();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmWidgetPanelConfiguratorTab) === "undefined")
{
	BX.CrmWidgetPanelConfiguratorTab = function()
	{
		this._id = "";
		this._settings = null;
		this._title = "";
		this._configurator = null;
		this._isActive = false;
		this._container = null;
		this._element = null;
		this._clickHandler = BX.delegate(this.onClick, this);
		this._selectionNotifier = null;
		this._hasLayout = false;
	};
	BX.CrmWidgetPanelConfiguratorTab.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : {};

			this._title = this.getSetting("title");
			if(this._title === "")
			{
				this._title = this._id
			}

			this._configurator = this.getSetting("configurator");
			if(!this._configurator)
			{
				throw "CrmWidgetPanelConfiguratorTab: The 'configurator' parameter is not found.";
			}

			this._container = this.getSetting("container");
			if(!this._container)
			{
				throw "CrmWidgetPanelConfiguratorTab: The 'container' parameter is not found.";
			}

			this._isActive = this.getSetting("isActive", false);
			this._selectionNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		addSelectionListener: function(listener)
		{
			this._selectionNotifier.addListener(listener);
		},
		removeSelectionListener: function(listener)
		{
			this._selectionNotifier.removeListener(listener);
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._element = BX.create("A",
				{
					attrs: { className: "sidebar-tab" },
					props: { href: "#" },
					text: this._title
				}
			);

			this._container.appendChild(this._element);
			BX.bind(this._element, "click", this._clickHandler);

			var className = this.getSetting("className", "");
			if(className !== "")
			{
				BX.addClass(this._element, className);
			}
			this._hasLayout = true;
		},
		cleanLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			BX.unbind(this._element, "click", this._clickHandler);
			BX.cleanNode(this._element, true);
			this._element = null;
			this._hasLayout = false;
		},
		onClick: function(e)
		{
			if(this._isActive)
			{
				this._selectionNotifier.notify();
			}
			return BX.PreventDefault(e);
		}
	};
	BX.CrmWidgetPanelConfiguratorTab.create = function(id, settings)
	{
		var self = new BX.CrmWidgetPanelConfiguratorTab();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmWidgetDataGroup) === "undefined")
{
	BX.CrmWidgetDataGroup = function()
	{
		this._settings = {};
	};
	BX.CrmWidgetDataGroup.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this.doInitialize();
		},
		doInitialize: function() {},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getDefault: function(){ return ""; },
		prepareListItems: function(){ return []; }
	};
}
if(typeof(BX.CrmGraphWidgetDataGroup) === "undefined")
{
	BX.CrmGraphWidgetDataGroup = function()
	{
		BX.CrmGraphWidgetDataGroup.superclass.constructor.apply(this);
	};

	BX.CrmGraphWidgetDataGroup.undefined = '';
	BX.CrmGraphWidgetDataGroup.date = 'DATE';
	BX.CrmGraphWidgetDataGroup.user = 'USER';

	BX.extend(BX.CrmGraphWidgetDataGroup, BX.CrmWidgetDataGroup);
	BX.CrmGraphWidgetDataGroup.prototype.getDefault = function()
	{
		return BX.CrmGraphWidgetDataGroup.date;
	};
	BX.CrmGraphWidgetDataGroup.prototype.prepareListItems = function(enableUndefined)
	{
		enableUndefined = !!enableUndefined;
		var result = [];
		var items = BX.CrmGraphWidgetDataGroup.descriptions;
		for(var k in items)
		{
			if(!items.hasOwnProperty(k))
			{
				continue;
			}

			if(k !== BX.CrmGraphWidgetDataGroup.undefined || enableUndefined)
			{
				result.push({ value: k, text: items[k] });
			}
		}
		return result;
	};
	if(typeof(BX.CrmGraphWidgetDataGroup.descriptions) === "undefined")
	{
		BX.CrmGraphWidgetDataGroup.descriptions = {};
	}
	BX.CrmGraphWidgetDataGroup.create = function()
	{
		var self = new BX.CrmGraphWidgetDataGroup();
		self.initialize();
		return self;
	};
}
if(typeof(BX.CrmPieWidgetDataGroup) === "undefined")
{
	BX.CrmPieWidgetDataGroup = function()
	{
		BX.CrmPieWidgetDataGroup.superclass.constructor.apply(this);
		this._entityTypeName = "";
	};

	BX.CrmPieWidgetDataGroup.undefined = '';

	BX.extend(BX.CrmPieWidgetDataGroup, BX.CrmWidgetDataGroup);
	BX.CrmPieWidgetDataGroup.prototype.doInitialize = function()
	{
		this._entityTypeName = this.getSetting("entityTypeName", "");
		if(this._entityTypeName === "")
		{
			throw "BX.CrmPieWidgetDataGroup: Could not find 'entityTypeName' parameter.";
		}
	};
	BX.CrmPieWidgetDataGroup.prototype.getDefault = function()
	{
		return BX.CrmPieWidgetDataGroup.undefined;
	};
	BX.CrmPieWidgetDataGroup.prototype.prepareListItems = function()
	{
		var result = [];
		var items = typeof(BX.CrmPieWidgetDataGroup.descriptions[this._entityTypeName]) !== "undefined"
			? BX.CrmPieWidgetDataGroup.descriptions[this._entityTypeName] : {};
		for(var k in items)
		{
			if(!items.hasOwnProperty(k))
			{
				continue;
			}

			result.push({ value: k, text: items[k] });
		}
		return result;
	};
	if(typeof(BX.CrmPieWidgetDataGroup.descriptions) === "undefined")
	{
		BX.CrmPieWidgetDataGroup.descriptions = {};
	}
	BX.CrmPieWidgetDataGroup.create = function()
	{
		var self = new BX.CrmPieWidgetDataGroup();
		self.initialize();
		return self;
	};
}