BX.namespace("BX.Mobile.Grid");

BX.Mobile.Grid = {
	init: function(params)
	{
		this.curPage = 1;
		this.pagerName = "";
		this.pagesNum = 1;
		this.ajaxUrl = "";
		this.sortEventName = "";
		this.fieldsEventName = "";
		this.filterEventName = "";
		this.reloadGridAfterEvent = true;

		if (typeof params == 'object')
		{
			this.pagerName = params.pagerName || "";
			this.pagesNum = params.pagesNum || 1;
			this.ajaxUrl = params.ajaxUrl || "";
			this.sortEventName = params.sortEventName || "";
			this.fieldsEventName = params.fieldsEventName || "";
			this.filterEventName = params.filterEventName || "";
			this.reloadGridAfterEvent = params.reloadGridAfterEvent === "N" ? false : true;
		}

		BX.bind(window, "scroll", function() {
			var clientHeight = document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.clientHeight;
			var documentHeight = document.documentElement.scrollHeight ? document.documentElement.scrollHeight : document.body.scrollHeight;
			var scrollTop = window.pageYOffset ? window.pageYOffset : (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop);

			if ((documentHeight - clientHeight) <= scrollTop)
			{
				BX.Mobile.Grid.getNextPageItems();
			}
		});

		this.wrapper = document.querySelector("[data-role='mobile-grid']");

		if (this.reloadGridAfterEvent)
		{
			BX.addCustomEvent(this.sortEventName, function() {
				window.BXMobileApp.UI.Page.reload();
			});
			BX.addCustomEvent(this.fieldsEventName, function() {
				window.BXMobileApp.UI.Page.reload();
			});
			BX.addCustomEvent(this.filterEventName, function() {
				window.BXMobileApp.UI.Page.reload();
			});
		}
	},
	showMoreActions: function(actions)
	{
		var buttons = [];
		for (var i=0; i<actions.length; i++)
		{
			buttons.push({
				title: actions[i].TEXT,
				callback:BX.proxy(function()
				{
					eval(this.action);
				}, {action: actions[i].ONCLICK})
			});
		}

		new window.BXMobileApp.UI.ActionSheet({
				buttons: buttons
			}, "actionSheet"
		).show();
	},
	getNextPageItems: function()
	{
		this.curPage++;

		if (this.curPage > this.pagesNum)
			return;

		if (!BX('bx-mobile-grid-page-loader'))
		{
			var loader = BX.create("div", {
				attrs: {
					id: "bx-mobile-grid-page-loader",
					class: "mobile-grid-loader"
				}
			});
			this.wrapper.appendChild(loader);
			document.body.scrollTop = document.body.scrollTop + 26;
		}

		var ajaxUrl = this.ajaxUrl.indexOf("?") !== -1 ? this.ajaxUrl + "&" + this.pagerName + "=" + this.curPage : this.ajaxUrl + "?" + this.pagerName + "=" + this.curPage;

		BX.ajax({
			timeout:   30,
			method:   'POST',
			url: ajaxUrl,
			data: {
				ajax: "Y"
			},
			onsuccess: BX.proxy(function(newHTML)
			{
				if (BX('bx-mobile-grid-page-loader'))
					BX.remove(BX('bx-mobile-grid-page-loader'));

				var ob = BX.processHTML(newHTML, false),
					tmpNode = BX.create("div", {html: ob.HTML});
				var items = tmpNode.querySelectorAll('[data-role="mobile-grid-item"]');
				if (items)
				{
					for(var i=0; i<items.length; i++)
					{
						if (this.wrapper)
							this.wrapper.appendChild(items[i]);
					}
				}
				BX.ajax.processScripts(ob.SCRIPT);
			}, this),
			onfailure: function(){
			}
		});
	},

	searchInit : function(params)
	{
		this.searchInput = document.querySelector("[data-role='search-input']");
		if (this.searchInput)
		{
			BX.bind(this.searchInput.form, "submit", BX.proxy(function (e) {
				this.onSearchKeyUp();
				return BX.PreventDefault(e);
			}, this));
			BX.bind(this.searchInput, "keyup", BX.proxy(function () {
				this.onSearchKeyUp();
			}, this));

			var searchCancel = document.querySelector("[data-role='search-cancel']");
			if (searchCancel)
			{
				BX.bind(searchCancel, "click", BX.proxy(function(){
					this.searchInput.value = "";
					this.onSearchKeyUp();
				}, this));
			}
		}
	},

	onSearchKeyUp : function(e)
	{
		if(!e)
			e = window.event;

		if (this.timeoutId)
			clearTimeout(this.timeoutId);

		if (this.searchInput.value.length > 2 || this.searchInput.value.length == 0)
		{
			this.timeoutId = setTimeout(BX.proxy(function () {
				BX.ajax.post(
					this.ajaxUrl,
					{
						action: "search",
						sessid: BX.bitrix_sessid(),
						search: this.searchInput.value
					},
					BX.proxy(function (result) {

						if (this.wrapper)
						{
							var ob = BX.processHTML(result, false),
								f = function(){
									if (this.wrapper.childNodes.length > 0)
										BX.ajax.processScripts(ob.SCRIPT);
									else
										BX.defer_proxy(f, this);
								};
							this.wrapper.innerHTML = ob.HTML;
							BX.defer_proxy(f, this);
						}
					}, this)
				);
			}, this), 250);
		}
	}
};

