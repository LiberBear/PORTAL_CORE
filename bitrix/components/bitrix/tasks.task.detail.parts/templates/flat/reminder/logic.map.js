{"version":3,"file":"logic.min.js","sources":["logic.js"],"names":["BX","namespace","Item","Tasks","Util","ItemSet","extend","sys","code","methods","construct","this","callConstruct","vars","data","option","randomTag","Math","round","random","bindEvents","bindDelegateControl","delegate","onEditClick","TRANSPORT","TYPE","REMIND_DATE","RECEPIENT_TYPE","value","VALUE","display","DISPLAY","update","prepareData","control","innerHTML","REMINDER_TEXT","mergeEx","delete","remove","scope","ctrls","e","parent","openUpdateForm","PreventDefault","onDeleteClick","deleteItem","text","message","TRANSPORT_CLASS","Form","Widget","constants","REMINDER_TYPE_DEADLINE","REMINDER_TYPE_COMMON","deadLine","formatValue","date","convertBitrixFormat","load","type","isNotEmptyString","setType","setRecipientType","setTransportType","diff","getDateDiff","setUnit","unit","setMultiplier","setDate","reset","UNIT_DAY","open","node","win","getWindow","changeCSSFlag","show","close","instances","window","PopupWindow","id","autoHide","closeByEsc","content","overlay","lightShadow","closeIcon","draggable","titleBar","angle","position","offsetTop","offsetLeft","events","onPopupClose","onFormClose","bindControl","passCtx","onChangeType","onChangeRecipientType","onChangeTransportType","onSubmit","datepicker","isElementNode","dp","DatePicker","defaultTime","optionP","COMPANY_WORKTIME","HOURS","START","bindEvent","onChangeRemindDate","closeTypeSubWindow","setBindElement","setDeadLineStamp","stamp","toggleDeadLineMode","string","setPicker","setValue","setCSSMode","toLowerCase","noDeadline","getDeadLineOffset","mp","parseInt","isNaN","seconds","dateStampToString","length","fireEvent","clone","closeCalendarSubWindow","typeWindow","popupWindow","closeCalendar","self","menu","title","className","onclick","menuId","PopupMenu","onPopupShow","getMenuById","Component","TaskDetailPartsReminder","UNIT_HOUR","editItemValue","windows","form","setTaskId","setTaskDeadLine","onItemApply","addCustomEvent","onTaskDeadLineChange","openAddForm","extractItemValue","idOffset","extractItemDisplay","item","items","createItem","getNodeByTemplate","append","auxData","taskId","dateString","dateStringToStamp","syncAllIfCan","addItem","fireChangeEvent","parameters","callMethod","arguments","from","to","isNumber","floor","format","d","Date","parsed","parseDate","getTime","syncAll","q","getQuery","arg","k","push","add","SE_REMINDER"],"mappings":"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"}