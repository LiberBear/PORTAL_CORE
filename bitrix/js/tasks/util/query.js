BX.namespace('Tasks.Util');

BX.Tasks.Util.Query = BX.Tasks.Util.Base.extend({
    options: {
        url: '/bitrix/components/bitrix/tasks.base/ajax.php',
        autoExec: false,
        replaceDuplicateCode: true,
        autoExecDelay: 500,
	    translateBooleanToZeroOne: true
    },
    methods: {
        construct: function()
        {
	        this.callConstruct(BX.Tasks.Util.Base);

            this.vars = {
                batch: 		[], // current batch pending
                local: 		{},
	            prevLocal:  {}
            };

            this.autoExecute = BX.debounce(this.autoExecute, this.option('autoExecDelay'), this);
        },

        destruct: function()
        {
            this.vars = null;
            this.opts = null;
        },

        autoExecute: function()
        {
            if(this.option('autoExec'))
            {
                this.execute();
            }
        },

        add: function(method, args, remoteParams, localParams)
        {
            if(typeof method == 'undefined')
            {
                throw new ReferenceError('Method name was not provided');
            }
            method = method.toString();

            if(method.length == 0)
            {
                throw new ReferenceError('Method name must not be empty');
            }

            if(typeof args == 'undefined' || !BX.type.isPlainObject(args))
            {
                args = {};
            }
            for(var k in args)
            {
                args[k] = this.processArguments(args[k]);
            }

            if(typeof remoteParams == 'undefined' || !BX.type.isPlainObject(remoteParams))
            {
                remoteParams = {};
            }
            if(typeof remoteParams.code == 'undefined')
            {
                remoteParams.code = '';
            }
            remoteParams.code = remoteParams.code.toString();
            if(remoteParams.code.length == 0)
            {
                remoteParams.code = 'op_'+(this.vars.batch.length);
            }

            if(this.option('replaceDuplicateCode'))
            {
                for(var k = 0; k < this.vars.batch.length; k++)
                {
                    if(this.vars.batch[k].PARAMETERS.code == remoteParams.code)
                    {
                        this.vars.batch.splice(k, 1);
                        break;
                    }
                }
            }

            this.vars.batch.push({
                OPERATION: method,
                ARGUMENTS: args,
                PARAMETERS: remoteParams
            });

            if(BX.type.isFunction(localParams))
            {
                localParams = {onExecuted: localParams};
            }
            else
            {
                localParams = localParams || {};
            }

            this.vars.local[remoteParams.code] = localParams;

            this.autoExecute();

            return this;
        },

        processArguments: function(args)
        {
            var type = typeof args;

            if(type == 'array')
            {
                if(args.length == 0)
                {
                    return '';
                }

                for(var k = 0; k < type.length; k++)
                {
                    args[k] = this.processArguments(args[k]);
                }
            }

            if(type == 'object')
            {
                var i = 0;
                for(var k in args)
                {
                    args[k] = this.processArguments(args[k]);
                    i++;
                }

                if(i == 0)
                {
                    return '';
                }
            }

	        if(type == 'boolean' && this.option('translateBooleanToZeroOne'))
	        {
		        return args === true ? '1' : '0';
	        }

            return args;
        },

        load: function(todo)
        {
            if(BX.type.isArray(todo))
            {
                this.clear();

                for(var k = 0; k < todo.length; k++)
                {
                    this.add(todo[k].m, todo[k].args, todo[k].rp);
                }
            }

            return this;
        },

        deleteAll: function()
        {
            this.vars.batch = [];
            this.vars.local = {};

            return this;
        },

        clear: function()
        {
            return this.deleteAll();
        },

        execute: function(params)
        {
            if(this.opts.url === false)
            {
                throw new ReferenceError('URL was not provided');
            }

            if(typeof params == 'undefined')
            {
                params = {};
            }

            if(this.vars.batch.length > 0)
            {
	            this.vars.prevLocal = BX.clone(this.vars.local);
	            var batch = this.vars.batch;
	            this.clear();

                BX.ajax({
                    url: this.opts.url,
                    method: 'post',
                    dataType: 'json',
                    async: true,
                    processData: true,
                    emulateOnload: true,
                    start: true,
                    data: {
	                    'sessid': BX.bitrix_sessid(), // make security filter feel happy, call variable "sessid" instead of "csrf"
                        'SITE_ID': BX.message('SITE_ID'),
                        'ACTION': batch
                    },
                    cache: false,
                    onsuccess: BX.delegate(function(result){
                        try // prevent falling through onfailure section in case of some exceptions inside onsuccess
                        {
                            var res = {
                                success: 				result.SUCCESS,
                                clientProcessErrors: 	[],
                                serverProcessErrors: 	result.ERROR,
                                data: 					result.DATA || {},
	                            response : result
                            };

                            //this.executeCallbacks(res);
                            this.executeDone(res, params.done);
                            this.fireEvent('executed', [res]);
                        }
                        catch(e)
                        {
	                        //console.dir('callback fail');
                            BX.debug(e);
                        }
                    }, this),
                    onfailure: BX.delegate(function(code, status){

                        var res = {
                            success: 				false,
                            clientProcessErrors: 	[{CODE: 'INTERNAL_ERROR', MESSAGE: 'Client process error', TYPE: 'FATAL', ajaxExtra: {code: code, status: status}}],
                            serverProcessErrors: 	[],
                            data: 					{}
                        };

                        //this.executeCallbacks(res);
                        this.executeDone(res, params.done);
                        this.fireEvent('executed', [res]);

                    }, this)
                });
            }
        },

        executeDone: function(res, done)
        {
	        var cl = this.getErrorCollectionClass();
	        var errors = new cl();
	        var toAdd;
	        var k;

	        toAdd = res.serverProcessErrors || [];
	        for(k = 0; k < toAdd.length; k++)
	        {
				errors.add(toAdd[k], 'C');
	        }

	        toAdd = res.clientProcessErrors || [];
	        for(k = 0; k < toAdd.length; k++)
	        {
		        errors.add(toAdd[k], 'C');
	        }

	        var data = BX.clone(res.data);
	        var commonErrors = new cl(errors);
	        var privateErrors;

            for(var m in data)
            {
	            privateErrors = null;
	            privateErrors = new cl(commonErrors);

	            toAdd = res.data[m].ERRORS || [];
	            for(k = 0; k < toAdd.length; k++)
	            {
		            privateErrors.add(toAdd[k]);
	            }

	            // execute callback
	            if(BX.type.isFunction(this.vars.prevLocal[m].onExecuted))
	            {
		            delete(data[m].ERRORS);
		            delete(data[m].SUCCESS);

		            this.vars.prevLocal[m].onExecuted.apply(this, [
			            privateErrors,
			            data[m]
		            ]);
	            }

	            // sum errors
	            privateErrors.deleteByMark('C');
	            errors.load(privateErrors);
            }

	        if(BX.type.isFunction(done))
	        {
                done.apply(this, [errors, res]);
            }

	        if(errors.checkHasErrors())
	        {
		        BX.onCustomEvent("TaskAjaxError", [errors]);
	        }
        },

	    getErrorCollectionClass: function()
	    {
		    return BX.Tasks.Util.Query.ErrorCollection;
	    }
    }
});

// error collection
BX.Tasks.Util.Query.ErrorCollection = function(errors)
{
	this.length = 0;

	if(typeof errors != 'undefined')
	{
		this.load(errors);
	}
};
BX.mergeEx(BX.Tasks.Util.Query.ErrorCollection.prototype, {
	add: function(data, marker)
	{
		this[this.length++] = new BX.Tasks.Util.Query.Error(BX.clone(data), marker);
	},
	load: function(errors)
	{
		for(var k = 0; k < errors.length; k++)
		{
			this.add(errors[k], false);
		}
	},
	getByCode: function(code)
	{
		if(!BX.type.isNotEmptyString(code))
		{
			return false;
		}

		for(var k = 0; k < this.length; k++)
		{
			if(this[k].checkIsOfCode(code))
			{
				return BX.clone(this[k]);
			}
		}
		return null;
	},
	checkHasErrors: function()
	{
		return !!this.length;
	},
	deleteByCodeAll: function(code)
	{
		if(!BX.type.isNotEmptyString(code))
		{
			return;
		}

		this.deleteByCondition(function(item){
			return item.checkIsOfCode(code);
		});
	},
	deleteByMark: function(mark)
	{
		if(!BX.type.isNotEmptyString(mark))
		{
			return;
		}

		this.deleteByCondition(function(item){
			return item.mark() == mark;
		});
	},
	deleteByCondition: function(fn)
	{
		var errors = [];

		for(var k = 0; k < this.length; k++)
		{
			if(!fn.apply(this, [this[k]]))
			{
				errors.push(this[k]);
			}
		}

		this.deleteAll(false);

		this.load(errors);
	},
	deleteAll: function(makeNull)
	{
		for(var k = 0; k < this.length; k++)
		{
			if(makeNull !== false)
			{
				this[k] = null;
			}
			delete(this[k]);
		}
		this.length = 0;
	}
});

// error
BX.Tasks.Util.Query.Error = function(error, mark)
{
	for(var k in error)
	{
		if(error.hasOwnProperty(k))
		{
			this[k] = BX.clone(error[k]);
		}
	}
	this.vars = {mark: mark};
};
BX.mergeEx(BX.Tasks.Util.Query.Error.prototype, {
	checkIsOfCode: function(code)
	{
		return this.CODE == code || BX.util.in_array(code, this.CODE.toString().split('.'));
	},
	code: function()
	{
		return this.CODE;
	},
	mark: function()
	{
		return this.vars.mark;
	},
	data: function()
	{
		if(BX.type.isPlainObject(this.DATA))
		{
			return this.DATA;
		}

		return {};
	}
});
