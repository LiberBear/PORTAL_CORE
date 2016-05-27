if (typeof(getToken) == "undefined")
{
	var createLink = function(tag)
	{
		var link = (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/');
		var result = false;
		var unique = false;
		var uniqueParams = {};

		var params = [];

		if (
			tag.substr(0, 10) == 'BLOG|POST|'
			|| tag.substr(0, 13) == 'BLOG|COMMENT|'
			|| tag.substr(0, 18) == 'BLOG|POST_MENTION|'
			|| tag.substr(0, 21) == 'BLOG|COMMENT_MENTION|'
			|| tag.substr(0, 11) == 'BLOG|SHARE|'
			|| tag.substr(0, 17) == 'BLOG|SHARE2USERS|'
		)
		{
			params = tag.split("|");
			result = link + "mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=" + params[2];
		}

		if(
			tag.substr(0, 11) == 'TASKS|TASK|'
			|| tag.substr(0, 14) == 'TASKS|COMMENT|'
		)
		{
			params = tag.split("|");
			result = link + "mobile/tasks/snmrouter/?routePage=view&TASK_ID=" + params[2];
		}

		if (result)
		{
			result = {
				LINK: result,
				UNIQUE: unique,
				DATA: uniqueParams
			};
		}

		return result;
	};

	/* PULL EVENTS */
	BX.addCustomEvent("onPullExtendWatch", function (data)
	{
		BX.PULL.extendWatch(data.id, data.force);
	});
	BX.addCustomEvent("onPullClearWatch", function (data)
	{
		BX.PULL.extendWatch(data.id);
	});

	BX.addCustomEvent("thisPageWillDie", function (data)
	{
		BX.PULL.clearWatch(data.page_id);
	});

	BX.addCustomEvent("onPullEvent", function (module_id, command, params)
	{
		BXMobileApp.onCustomEvent('onPull-'+module_id, {'command': command, 'params': params});
		BXMobileApp.onCustomEvent('onPull', {'module_id': module_id, 'command': command, 'params': params});
	});

	BX.addCustomEvent("onPullOnlineEvent", function (command, params)
	{
		BXMobileApp.onCustomEvent('onPullOnline', {'command': command, 'params': params});
	});

	BX.PULL.authTimeout = null;
	BX.addCustomEvent("onPullError", function (error)
	{
		if (error == 'AUTHORIZE_ERROR')
		{
			clearTimeout(BX.PULL.authTimeout);
			BX.PULL.authTimeout = setTimeout(function(){
				app.BasicAuth({
					success:function ()
					{
						BX.PULL.setPrivateVar('_pullTryConnect', true);
						BX.PULL.updateState('13', true);
					}
				});
			}, 500);
		}
	});

	/* WEBRTC */
	BX.addCustomEvent("onCallInvite", function (data)
	{
		if (data.userId)
			mwebrtc.callInvite(data.userId, (data.video != "NO"));
	});

	BX.addCustomEvent("onOpenPush", function (push)
	{
		var pushParams = BXMobileApp.PushManager.prepareParams(push);

		if (BX.util.in_array(pushParams.ACTION, ["post", "tasks", "comment", "mention", "share", "share2users"]))
		{
			var data = createLink(pushParams.TAG);

			if (
				typeof (data.LINK) != 'undefined'
				&& data.LINK.length > 0
			)
			{
				BXMobileApp.PageManager.loadPageUnique({
					url : data.LINK,
					unique : data.UNIQUE,
					data: data.DATA,
					bx24ModernStyle : true
				});
			}
		}
	});

	/* IM EVENTS */
	BX.ready(function(){
		BXIM = new BX.ImMobile({
			'mobileAction': 'INIT',
			'userId': BX.message('USER_ID')
		});
	});

	var getToken = function(repeatable)
	{
		//get device token
		var dt = (window.platform == "ios"
			? "APPLE"
			: "GOOGLE"+(app.enableInVersion(14) ? "/REV2" :"")
		);

		var params = {
			iOSUseVoipService:true,
			callback: function (data)
			{
				var token = null;

				if(typeof data == "object" )
				{
					if(data.voipToken)
					{
						token = data.voipToken;
						dt = "APPLE/VOIP"
					}
					else if(data.token)
					{
						token = data.token;
					}
				}
				else
				{
					token = data;
				}

				var config =
				{
					url:app.dataBrigePath,
					method:"POST",
					tokenSaveRequest:true,
					data:{
						mobile_action: "save_device_token",
						device_name: (typeof device.name == "undefined" ? device.model : device.name),
						uuid: device.uuid,
						device_token: token,
						device_type: dt,
						sessid: BX.bitrix_sessid()
					}
				};

				if(repeatable)
				{
					config.repeatble = true;
					var failHandler = function (field, statusCode, config)
					{
						BX.removeCustomEvent("onAjaxFailure", failHandler);

						if(config.tokenSaveRequest && statusCode == 401 && config.repeatble)
						{
							app.BasicAuth({
								success:function()
								{
									getToken(false);
								}
							})
						}
					};

					BX.addCustomEvent("onAjaxFailure",  failHandler);
				}

				BX.ajax(config);
			}
		};

		app.exec("getToken", params);
	};

	getToken(true);
}
