{"version":3,"file":"script.min.js","sources":["script.js"],"names":["BX","window","namespace","Mobile","Tasks","detail","opts","nf","this","parentConstruct","merge","sys","classCode","vars","objectId","util","getRandomString","task","taskData","taskEditObj","handleInitStack","extend","page","prototype","init","app","hidePopupLoader","act","delegate","actExecute","actSuccess","actFailure","onCustomEvent","addCustomEvent","taskId","data","showPopupLoader","reload","closeController","drop","variable","bind","proxy","addClass","removeClass","getDefaultMenu","menu_items","name","message","arrowFlag","icon","action","createWindow","actions","task_id","option","index","hasOwnProperty","toUpperCase","push","url","replace","Date","getTime","BXMobileApp","PageManager","loadPageModal","bx24ModernStyle","cache","confirm","title","text","buttons","callback","btnNum","UI","Table","table_settings","a_users","userid","toString","markmode","multiple","return_full_mode","skipSpecialChars","modal","alphabet_index","outsection","cancelname","show","image","isBusy","appCtrls","menu","hide","add_url_param","id","Util","Query","add","taskid","onExecuted","response","status","BasicAuth","success","execute","failure","apply","arguments","errors","length","ii","alert","join","specify","reset","s","statusMap","innerHTML","jsAction","resetMenu"],"mappings":"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"}