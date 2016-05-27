BX.namespace('Tasks.Util');

BX.Tasks.Util.Base = function(options)
{
}

BX.mergeEx(BX.Tasks.Util.Base.prototype, {

	// top-level constructor, makes the Earth spin and other stuff
	construct: function()
	{
	},

	fireEvent: function(name, args)
	{
		BX.onCustomEvent(this, name, args);
	},

	bindEvent: function(name, callback)
	{
		BX.addCustomEvent(this, name, callback);
	},

	callMethod: function(classRef, name, arguments)
	{
		return classRef.prototype[name].apply(this, arguments);
	},

	callConstruct: function(classRef)
	{
		this.callMethod(classRef, 'construct');
	},

	runParentConstructor: function(owner)
	{
		if(typeof owner.superclass == 'object')
		{
			owner.superclass.constructor.apply(this, [null, true]);
		}
	},

	walkPrototypeChain: function(obj, fn)
	{
		var ref = obj.constructor;
		while(typeof ref != 'undefined' && ref != null)
		{
			fn.apply(this, [ref.prototype, ref.superclass]);

			if(typeof ref.superclass == 'undefined')
			{
				return;
			}

			ref = ref.superclass.constructor;
		}
	},

	destroy: function()
	{
		this.walkPrototypeChain(this, function(proto){
			if(typeof proto.destruct == 'function')
			{
				proto.destruct.call(this);
			}
		});
	},

	option: function(name, value)
	{
		if(typeof value != 'undefined')
		{
			this.opts[name] = value;
		}
		else
		{
			return typeof this.opts[name] != 'undefined' ? this.opts[name] : false;
		}
	},

    initialized: function()
    {
        return this.sys.initialized;
    },

	// util
	passCtx: function(f)
	{
		var this_ = this;
		return function()
		{
			var args = Array.prototype.slice.call(arguments);
			args.unshift(this); // this is a ctx of the node event happened on
			return f.apply(this_, args);
		}
	},

	// dispatching
	id: function(id)
	{
		if(typeof id != 'undefined' && BX.type.isNotEmptyString(id))
		{
			this.sys.id = id.toString().toLowerCase();
		}
		else
		{
			return this.sys.id;
		}
	},
	register: function()
	{
		if(this.option('registerDispatcher'))
		{
			var id = this.id();
			if(id)
			{
				BX.Tasks.Util.Dispatcher.register(id, this);
			}
		}
	}
});

BX.Tasks.Util.Base.extend = function(parameters){

	// here "this" refers to the class constructor function

	if(typeof parameters == 'undefined' || !BX.type.isPlainObject(parameters)) 
	{
		parameters = {};
	}

	var child = function(opts, middle){

		// here "this" refers to the object instance to be created

		this.runParentConstructor(child); // apply all parent constructors

		if(typeof this.opts == 'undefined')
		{
			this.opts = {
				registerDispatcher: false
			};
		}
		if(typeof parameters.options != 'undefined' && BX.type.isPlainObject(parameters.options))
		{
			BX.mergeEx(this.opts, parameters.options);
		}

		if(typeof this.sys == 'undefined')
		{
			this.sys = {
				id: 		    false, // instance id, a unique hash that can be used to refer to an instance among other (must be unique on the page if used)
				initialized:    false
			};
		}
		if(typeof parameters.sys != 'undefined' && BX.type.isPlainObject(parameters.sys))
		{
			BX.mergeEx(this.sys, parameters['sys']);
		}

		delete(parameters);
		delete(child);

		// in the last constructor we run this
		if(!middle)
		{
			// final version of opts array should be ready before "post-constructors" are called
			if(typeof opts != 'undefined' && BX.type.isPlainObject(opts))
			{
				BX.mergeEx(this.opts, opts);
			}

			this.id(this.option('id'));
			this.register(); // register instance in dispatcher, if needed
			this.construct(); // run the top-level constructor

            this.sys.initialized = true;
			// todo: init event here
		}
	};
	child.extend = BX.Tasks.Util.Base.extend; // just a short-cut to extend() function

	BX.extend(child, this);
    parameters.methods = parameters.methods || {};
    parameters.constants = parameters.constants || {};

	if(typeof parameters.methods != 'undefined' && BX.type.isPlainObject(parameters.methods))
	{
		for(var k in parameters.methods)
		{
			if(parameters.methods.hasOwnProperty(k))
			{
				child.prototype[k] = parameters.methods[k];
			}
		}
	}
	if(typeof parameters.constants != 'undefined' && BX.type.isPlainObject(parameters.constants))
	{
		for(var k in parameters.constants)
		{
			if(parameters.constants.hasOwnProperty(k))
			{
				child.prototype[k] = parameters.constants[k];
			}
		}
	}

	// "virtual" constructor to prevent constructor chain break
	if(typeof parameters.methods.construct != 'function')
	{
		var parent = this;
		child.prototype.construct = function(){
			this.callConstruct(parent);
			delete(parent);
		};
	}
	if(typeof parameters.methods.destruct != 'function')
	{
		child.prototype.destruct = BX.DoNothing();
	}

	return child;
};

BX.Tasks.Util.Dispatcher = BX.Tasks.Util.Base.extend({
	methods: {
		construct: function()
		{
			this.callConstruct(BX.Tasks.Util.Base);

			this.vars = {
				registry: {},
				pend: {
					bind: {}
				}
			};
		},
		destruct: function()
		{
			this.vars = null;
		},
		registerInstance: function(id, instance)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw new ReferenceError('Trying to register while id is empty');
			}

			if(instance == null || instance == false)
			{
				throw new ReferenceError('Bad instance');
			}

			if(typeof this.vars.registry[id] != 'undefined')
			{
				throw new ReferenceError('The id "'+id.toString()+'" is already in use in registry');
			}

			this.vars.registry[id] = instance;

			// bind deferred
			if(typeof this.vars.pend.bind[id] != 'undefined')
			{
				for(var k in this.vars.pend.bind[id])
				{
					this.vars.registry[id].bindEvent(this.vars.pend.bind[id][k].event, this.vars.pend.bind[id][k].cb);
				}

				delete(this.vars.pend.bind[id]);
			}
		},
		get: function(id)
		{
			if(typeof this.vars.registry[id] == 'undefined')
			{
				return null;
			}

			return this.vars.registry[id];
		},
		addDeferredBind: function(id, name, cb)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw new TypeError('Bad id: '+id);
			}

			if(!BX.type.isNotEmptyString(name))
			{
				throw new TypeError('Bad event name: '+name);
			}

			if(!BX.type.isFunction(cb))
			{
				throw new TypeError('Callback is not a function to call for: '+id+' '+name);
			}

			if(typeof this.vars.registry[id] != 'undefined') // no pend, just bind
			{
				this.vars.registry[id].bindEvent(name, cb);
			}
			else
			{
				if(typeof this.vars.pend.bind[id] == 'undefined')
				{
					this.vars.pend.bind[id] = [];
				}
				this.vars.pend.bind[id].push({
					event: name,
					cb: cb
				});
			}
		},
		addDeferredFire: function(id, name, args, params)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw new TypeError('Bad id: '+id);
			}

			if(!BX.type.isNotEmptyString(name))
			{
				throw new TypeError('Bad event name: '+name);
			}

			args = args || [];

			if(typeof this.vars.registry[id] != 'undefined') // no pend, just fire
			{
				this.vars.registry[id].fireEvent(name, args);
			}
			else
			{
				// todo (await for 'init' event and then fire)
			}
		}
	}
});
BX.Tasks.Util.Dispatcher.register = function(id, instance)
{
	BX.Tasks.Util.Dispatcher.getInstance().registerInstance(id, instance);
};
BX.Tasks.Util.Dispatcher.get = function(id)
{
	return BX.Tasks.Util.Dispatcher.getInstance().get(id);
};
BX.Tasks.Util.Dispatcher.bindEvent = function(id, name, cb)
{
	BX.Tasks.Util.Dispatcher.getInstance().addDeferredBind(id, name, cb);
};
BX.Tasks.Util.Dispatcher.fireEvent = function(id, name, args, params)
{
	BX.Tasks.Util.Dispatcher.getInstance().addDeferredFire(id, name, args, params);
};
BX.Tasks.Util.Dispatcher.getInstance = function()
{
	if(typeof BX.Tasks.Singletons == 'undefined')
	{
		BX.Tasks.Singletons = {};
	}
	if(typeof BX.Tasks.Singletons.dispatcher == 'undefined')
	{
		BX.Tasks.Singletons.dispatcher = new BX.Tasks.Util.Dispatcher({
			registerDispatcher: false
		});
	}

	return BX.Tasks.Singletons.dispatcher;
};