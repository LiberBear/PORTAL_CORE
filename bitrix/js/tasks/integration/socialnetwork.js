BX.namespace('BX.Tasks.Integration');

(function(){

	BX.Tasks.Integration.Socialnetwork = {};

	var dataCache = false;
	var dataFetchingInProgress = false;
	var popupOpenedId = false;

	BX.Tasks.Integration.Socialnetwork.NetworkSelector = BX.Tasks.Util.Widget.extend({
		sys: {
			code: 'network-selector'
		},
		options: {
			mode: 'user', // could be also "group"
            query: false,
			useSearch: false,
			useAdd: false
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				this.vars.snldId = false;
				this.vars.intendSearch = '';
				this.vars.last = {};
				this.vars.intendOpen = false;
				this.vars.changed = false;
			},

			initialize: function()
			{
				if(this.dialogInitialized())
				{
					this.fireInitEvent();
				}
				else
				{
					if(dataCache === false)
					{
						// no data loaded previously
						this.fetchDestinationData();
					}
					else
					{
						this.initializeDialog();
					}
				}
			},

			dialogInitialized: function()
			{
				return this.vars.snldId !== false;
			},

			open: function()
			{
				if(!this.dialogInitialized())
				{
					this.vars.intendOpen = true;
					this.initialize();
				}
				else if(this.vars.snldId != popupOpenedId)
				{
					this.vars.intendOpen = false;

					if(popupOpenedId != false)
					{
						BX.SocNetLogDestination.openDialog(this.vars.snldId); // close
					}
					BX.SocNetLogDestination.openDialog(this.vars.snldId); // re-open
					popupOpenedId = this.vars.snldId;
				}
			},

			close: function()
			{
				this.vars.intendOpen = false;

				if(this.vars.snldId == popupOpenedId)
				{
					BX.SocNetLogDestination.closeDialog();
				}
			},

			addLast: function(itemId)
			{
				this.vars.last[itemId] = true;
			},

			deleteLast: function(itemId)
			{
				this.vars.last[itemId] = false;
			},

			updateLast: function()
			{
				if(!this.vars.changed)
				{
					return;
				}

				var items = this.vars.last;
				this.vars.last = {};

				var result = {};
				var key = this.option('mode') == 'user' ? 'USER' : 'SGROUP';
				result[key] = [];
				for(var k in items)
				{
					if(items[k] === true)
					{
						result[key].push(k);
					}
				}

				if(result[key].length > 0)
				{
					// send with delay, using query
                    this.getQuery().add('integration.socialnetwork.setdestinationlast', {items: result});
				}

				this.vars.changed = false;
			},

			fetchDestinationData: function()
			{
				if(!dataFetchingInProgress)
				{
					dataFetchingInProgress = true;
                    // send immediately, regardless the delay
                    this.getQuery().add('integration.socialnetwork.getdestinationdata', [], {code: 'get_destination_data'}).execute();
				}
			},

			getQuery: function()
			{
				if(typeof this.instances.query == 'undefined')
				{
                    if(this.option('query'))
                    {
                        this.instances.query = this.option('query');
                    }
                    else
                    {
                        this.instances.query = new BX.Tasks.Util.Query({
                            autoExec: true
                        });
                    }
					this.instances.query.bindEvent('executed', BX.delegate(this.onQueryExecuted, this));
				}

				return this.instances.query;
			},

			onQueryExecuted: function(result)
			{
				dataFetchingInProgress = false;

				if(result.success)
				{
					if(typeof result.data != 'undefined' && typeof result.data['get_destination_data'] != 'undefined')
					{
						if(result.data['get_destination_data'].SUCCESS)
						{
							dataCache = result.data['get_destination_data'].RESULT;
							this.initializeDialog();
						}
					}
				}
			},

			onSelectDestination: function(entity)
			{
				//console.dir('select');
				//console.dir(entity);

				this.vars.changed = true;

				this.addLast(entity.entityId);

				entity.params = entity.params || {};

                var type = {
	                extranet: entity.isExtranet == 'Y',
	                email: entity.isEmail == 'Y'
                };

				var id = parseInt(entity.entityId);

				this.fireEvent('item-selected', [{
					id: isNaN(id) ? 0 : id,
					nameFormatted: entity.name || '',
                    description: entity.desc || '',
                    avatar: entity.avatar || '',
					name: entity.params.name || '',
					lastName: entity.params.lastName || '',
					email: entity.email || '',
					type: type
				}]);
			},

			onUnSelectDestination: function(entity)
			{
				this.vars.changed = true;

				this.deleteLast(entity.entityId);

				this.fireEvent('item-deselected', [{
					id: entity.entityId,
					name: entity.name
				}]);
			},

			onOpenDialogDestination: function(id)
			{
				//console.dir('open: '+id);

				popupOpenedId = id;
			},

			onCloseDialogDestination: function(id)
			{
				//console.dir('close: '+id);

                if(id == popupOpenedId) // last opened dialog was ours
                {
                    this.fireEvent('close');
                    this.updateLast();
                }

				popupOpenedId = false;
			},

			onOpenSearchDestination: function(id)
			{
				//console.dir('open search: '+id);

				popupOpenedId = id;
			},

			onCloseSearchDestination: function(id)
			{
				//console.dir('close search: '+id);

				if(id == popupOpenedId) // last opened dialog was ours
				{
					this.fireEvent('close');
					this.updateLast();
				}

				popupOpenedId = false;
			},

			onOpenEmailDestination: function(id)
			{
				//console.dir('open email: '+id);

				popupOpenedId = id;
			},

			onCloseEmailDestination: function(id)
			{
				//console.dir('close email: '+id);

				if(id == popupOpenedId) // last opened dialog was ours
				{
					this.fireEvent('close');
					this.updateLast();
				}

				popupOpenedId = false;
			},

			checkIsOpened: function()
			{
				return popupOpenedId == this.vars.snldId;
			},

			deselectItem: function(id)
			{
				if(this.vars.snldId != false)
				{
					var u = this.option('mode') == 'user';

					// access to a low-level function, may fall here one day
					BX.SocNetLogDestination.deleteItem((u ? 'U' : 'SG')+id, u ? 'users' : 'groups', this.vars.snldId);
				}
			},

			selectItem: function()
			{
				if(this.vars.snldId != false)
				{
					var u = this.option('mode') == 'user';

					// access to a low-level function, may fall here one day
					BX.SocNetLogDestination.selectItem(this.vars.snldId, null, null, (u ? 'U' : 'SG')+id, u ? 'users' : 'groups');// = function(name, element, template, itemId, type, search)
				}
			},

			initializeDialog: function()
			{
				if(this.vars.snldId == false)
				{
					this.vars.snldId = BX.util.hashCode(Math.random().toString());
					var scope = this.scope();
					var inputName = 'name-'+this.id();
					var input = this.control('search');

					if(input)
					{
						BX.adjust(input, {
							attrs: {input: inputName, id: inputName}
						});
					}

					var parameters = {
						name : this.vars.snldId,
						searchInput : input || null,
						bindMainPopup : { 'node' : scope, 'offsetTop' : '0px', 'offsetLeft': '0px'},
						bindSearchPopup : { 'node' : scope, 'offsetTop' : '0px', 'offsetLeft': '0px'},
						departmentSelectDisable: true,
						allowAddUser: this.option('useAdd'),
						allowAddSocNetGroup: false,
						sendAjaxSearch: this.option('mode') == 'user', // as we pre-load groups, ajax is required only in "user" mode
						callback : {
							select : BX.proxy(this.onSelectDestination, this),
							unSelect : BX.proxy(this.onUnSelectDestination, this),
							openDialog : BX.proxy(this.onOpenDialogDestination, this),
							closeDialog : BX.proxy(this.onCloseDialogDestination, this),
							openSearch : BX.proxy(this.onOpenSearchDestination, this),
							closeSearch : BX.proxy(this.onCloseSearchDestination, this),
							openEmailAdd: BX.proxy(this.onOpenEmailDestination, this),
							closeEmailAdd: BX.proxy(this.onCloseEmailDestination, this)
						}
					};

					if (this.option('useSearch'))
					{
						parameters.showSearchInput = true;
					}

					if(this.option('mode') == 'user')
					{
						parameters.items = {
							users: 					dataCache.USERS,
							department: 			dataCache.DEPARTMENT || {},
							departmentRelation: 	dataCache.DEPARTMENT_RELATION || {}
						};
						parameters.itemsLast = {
							users: dataCache.LAST.USERS
						};
						parameters.itemsSelected = dataCache.SELECTED || {};
					}
					else if(this.option('mode') == 'group')
					{
						parameters.items = {
							users: {},
							groups: {},
							department: {},
							departmentRelation: {},
							sonetgroups: dataCache.SONETGROUPS || {}
						};
						parameters.itemsLast = {
							users: {},
							groups: {},
							department: {},
							sonetgroups: dataCache.LAST.SONETGROUPS || {}
						};
						parameters.itemsSelected = {};
					}

					BX.SocNetLogDestination.init(parameters);

					if (input)
					{
						var params = {
							formName: this.vars.snldId,
							inputName: 'name-'+this.id(),
							sendAjax: this.option('mode') == 'user'
						};

						BX.bind(input, "keyup", BX.proxy(BX.SocNetLogDestination.BXfpSearch, params));
						BX.bind(input, "keydown", BX.proxy(BX.SocNetLogDestination.BXfpSearchBefore, params));
						BX.bind(input, "click", BX.delegate(this.open, this)); //re-open when occasionly closed
					}
				}

				this.fireInitEvent();
				if(this.vars.intendOpen)
				{
					this.open();
				}
			},

			fireInitEvent: function()
			{
				this.fireEvent('initialized');
			},

			clearDataCache: function()
			{
				dataCache = false;
			}
		}
	});

})();