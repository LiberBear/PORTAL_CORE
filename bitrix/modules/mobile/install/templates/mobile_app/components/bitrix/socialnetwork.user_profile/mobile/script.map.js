{"version":3,"file":"script.min.js","sources":["script.js"],"names":["BX","namespace","Mobile","Profile","user","pullDown","isWebRTCSupported","menu","actionSheet","userPhotoUrl","init","params","this","type","isArray","isNotEmptyString","ready","proxy","initButtons","initMenu","initAvatar","initStatus","initPullDown","buttons","getVideoButton","getAudioButton","getTextButton","docFragment","document","createDocumentFragment","i","length","button","buttonNode","create","props","className","children","text","title","events","touchstart","event","addClass","touchend","removeClass","FastButton","click","appendChild","childNodes","btnContainer","app","enableInVersion","message","id","external_auth_id","onCustomEvent","userId","eventReturnFalse","menuItems","getAudioMenuItems","showAudioMenu","items","push","callback","video","phones","work_phone","personal_mobile","item","MobileTools","phoneTo","delegate","BXMobileApp","UI","ActionSheet","show","itemsTitle","confirm","itemIndex","PageManager","loadPageUnique","url","bx24ModernStyle","data","dialogId","menuCreate","Page","TopBar","setText","setCallback","menuShow","addButtons","menuButton","style","Photo","photos","updateStatus","is_online","addCustomEvent","command","USER_ID","window","pageColor","COLOR","exec","background","titleText","titleDetailText","USERS","isUserOnline","statusNode","innerHTML","action","location","reload"],"mappings":"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"}