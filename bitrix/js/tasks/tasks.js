/**
 * The file contains functionality that is used very often
 */

BX.namespace('Tasks');

BX.mergeEx(BX.Tasks, {

	alert: function(errors, fn)
	{
		if(BX.Tasks.Runtime.errorPopup == null)
		{
			BX.Tasks.Runtime.errorPopup = new BX.PopupWindow("task-error-popup", null, { lightShadow: true });
		}

		var errorPopup = BX.Tasks.Runtime.errorPopup;

		if (errorPopup === null)
		{
			errorPopup = new BX.PopupWindow("task-error-popup", null, { lightShadow: true });
		}

		errorPopup.setButtons([
			new BX.PopupWindowButton({
				text: BX.message("JS_CORE_WINDOW_CLOSE"),
				className: "",
				events: {
					click: function() {
						if (BX.type.isFunction(fn))
						{
							fn();
						}
						else
						{
							BX.reload();
						}

						this.popupWindow.close();
					}
				}
			})
		]);

		var popupContent = "";
		for (var i = 0; i < errors.length; i++)
		{
			popupContent += (typeof(errors[i].MESSAGE) !== "undefined" ? errors[i].MESSAGE : errors[i]) + "<br>";
		}

		errorPopup.setContent(
			"<div style='width: 350px;padding: 10px; font-size: 12px; color: red;'>" +
			popupContent +
			"</div>"
		);

		if (window.console && window.console.dir)
		{
			window.console.dir(errors);
		}

		errorPopup.show();
	},
	
	confirm: function(body, callback, params)
	{
		if(!BX.type.isFunction(callback))
		{
			callback = BX.DoNothing;
		}

		params = params || {};
		params.ctx = params.ctx || this;

		if(BX.Tasks.Runtime.confirmPopup == null)
		{
			BX.Tasks.Runtime.confirmPopup = new BX.PopupWindow(
				"task-confirm-popup",
				null,
				{
					zIndex : 22000,
					overlay : { opacity: 50 },
					titleBar : {},
					content : '',
					autoHide   : false,
					closeByEsc : false,
					buttons : [
						new BX.PopupWindowButton({
							text: BX.message('JS_CORE_WINDOW_CONTINUE'),
							className: "popup-window-button-accept",
							events : {
								click : function(){
									callback.apply(params.ctx, [true]);
									this.popupWindow.close();

									delete(params);
								}
							}
						}),
						new BX.PopupWindowButton({
							text: BX.message('JS_CORE_WINDOW_CANCEL'),
							events : {
								click : function(){
									callback.apply(params.ctx, [false]);
									this.popupWindow.close();

									delete(params);
								}
							}
						})
					]
				}
			);
		}

		if(typeof params.title != 'undefined')
		{
			BX.Tasks.Runtime.confirmPopup.setTitleBar({content: BX.type.isElementNode(params.title) ? params.title : BX.create('div', {
				html: params.title
			})});
		}

		body = BX.create(
			'div',
			{
				style: {padding: '16px 12px', maxWidth: '400px', maxHeight: '400px', overflow: 'hidden'},
				html : BX.type.isElementNode(body) ? body.outerHTML : body.toString()
			}
		);

		BX.Tasks.Runtime.confirmPopup.setContent(body.outerHTML);
		BX.Tasks.Runtime.confirmPopup.show();
	},

	passCtx: function(f, ctx)
	{
		return function()
		{
			var args = Array.prototype.slice.call(arguments);
			args.unshift(this); // this is a ctx of the node event happened on
			return f.apply(ctx, args);
		}
	}
});

if(typeof BX.Tasks.Runtime == 'undefined')
{
	BX.Tasks.Runtime = {
		errorPopup: null,
		confirmPopup: null
	};
}
