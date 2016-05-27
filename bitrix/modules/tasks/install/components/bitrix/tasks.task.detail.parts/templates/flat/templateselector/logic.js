BX.namespace('Tasks.Component');

BX.Tasks.Component.TaskDetailPartsTemplateSelector = BX.Tasks.Util.Widget.extend({
	sys: {
		code: 'templateselector'
	},
	methods: {
		construct: function()
		{
			this.callConstruct(BX.Tasks.Util.Widget);

			BX.bind(this.control('open'), 'click', this.passCtx(this.onPopupOpen));
		},

		onPopupOpen: function(node)
		{
			BX.PopupWindowManager.create("task-templates-popup-"+this.id(), node, {
				autoHide : true,
				offsetTop : 1,
				events : {
					onPopupClose : BX.delegate(this.onPopupClose, this)
				},
				content : this.control('popup-content')
			}).show();

			BX.addClass(node, "webform-button-active");
		},

		onPopupClose: function()
		{
			BX.removeClass(this.control('open'), "webform-button-active");
		}
	}
});