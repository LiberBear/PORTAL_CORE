(function (window)
{
	if (window.BX.MobileTools) return;


	BX.MobileTools = {
		phoneTo: function (number, params)
		{
			params = typeof(params) == 'object'? params: {};
			app.onCustomEvent("onPhoneTo", {number: number, params: params});
		},
		callTo: function (userId, video)
		{
			video = typeof(video) == 'undefined'? false: video;
			app.onCustomEvent("onCallInvite", {userId: userId, video: video});
		}
	};


})(window);
