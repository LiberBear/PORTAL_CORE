{"version":3,"file":"script.min.js","sources":["script.js"],"names":["BX","window","BXMobileApp","namespace","repo","formId","gridId","initSelect","d","select","eventNode","container","this","click","delegate","callback","init","prototype","multiple","options","length","setAttribute","bind","hasAttribute","initValues","titles","values","defaultTitles","ii","push","innerHTML","value","e","show","PreventDefault","UI","SelectPicker","multiselect","default_value","data","keys","jj","html","removeAttribute","util","in_array","message","onCustomEvent","initDatetime","node","type","formats","format","inner","datetime","time","date","bitrix","visible","eventCancelBubble","res","start_date","getStrDate","DatePicker","setParams","delButton","style","display","makeDate","str","Date","isNotEmptyString","dateR","RegExp","timeR","m","test","exec","setDate","setMonth","setFullYear","setHours","setMinutes","parseDate","str_pad_left","getDate","toString","getMonth","getFullYear","getHours","getMinutes","DATETIME_FORMAT","convertBitrixFormat","DATE_FORMAT","TIME_FORMAT","substr","trim","indexOf","replace","id","proxy","drop","getAttribute","initSelectUser","showDrop","actualizeNodes","showMenu","Table","url","table_settings","markmode","return_full_mode","skipSpecialChars","modal","alphabet_index","outsection","okname","cancelname","proxy_context","remove","findParent","tagName","className","a_users","user","existedUsers","Math","min","htmlspecialchars","join","ij","f","childNodes","setTimeout","initText","app","attachButton","items","attachFileSettings","attachedFiles","extraData","mentionButton","smileButton","text","htmlspecialcharsback","okButton","name","cancelButton","initBox","change","Mobile","Grid","Form","params","Page","LoadingScreen","hide","nodes","obj","apply","restrictedMode","pop","bindElement","elements","addCustomEvent","cancel","TopBar","updateButtons","bar_type","position","ok","addClass","removeClass","result","tag","toLowerCase","event","keyCode","found","form","focus","hasClass","nextSibling","Disk","UF","getByName","save","input","submit","ajax","restricted","method","onsuccess","arguments","onfailure","onprogress","create","attrs","appendChild","submitAjax","getByFormId","getByGridId"],"mappings":"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"}