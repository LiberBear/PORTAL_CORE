{"version":3,"file":"logic.min.js","sources":["logic.js"],"names":["BX","namespace","checkListItem","itemData","parent","this","vars","ctrls","data","can","MODIFY","REMOVE","TOGGLE","tData","clone","CHECKED","isComplete","READONLY","APPEARANCE","isSeparatorValue","TITLE","mode","scope","getNodeByTemplate","mergeEx","prototype","blockRedraw","title","flag","IS_COMPLETE","redraw","sortIndex","index","SORT_INDEX","parseInt","id","ID","number","num","isNaN","control","innerHTML","overwriteHtml","TITLE_HTML","util","htmlspecialchars","titleTmp","value","toString","destruct","remove","isSeparator","setAppearance","app","dropCSSFlags","setCSSFlag","setEditMode","setReadMode","applyTitleChange","handleDelete","replace","match","setCSSMode","e","checked","Tasks","Component","TaskDetailPartsChecklist","Util","Widget","extend","sys","code","options","autoSync","taskId","taskCanEdit","methods","construct","callConstruct","items","newIncrement","syncLock","dd","DragAndDrop","createFlying","delegate","node","itemId","item","autoMarkItemAfter","autoMarkZoneTopBottom","bindDropZone","instances","dragNDrop","query","bindEvents","load","option","bindDelegateControl","passCtx","setItemEdit","setItemCancel","setItemApply","setItemToggle","setItemApplyOnKeydown","newItemOpenForm","newItemCloseForm","newItemAdd","newItemTitleKeydown","onCompleteToggle","newSeparatorAdd","bindEvent","itemRelocated","listNode","nodeScope","itemInst","acts","toComplete","push","afterItemId","after","getOngoingItemByGreatestSortIndex","afterId","sync","shiftSortIndexes","getSortedItemList","newArr","k","length","i","btn","getInstanceByNode","getQuery","deleteItem","confirm","message","way","ctx","isEnter","PreventDefault","itemScope","controlP","type","isElementNode","Query","addItem","params","bindNode","handle","controlAll","unBindNode","syncAddItem","onAdd","onToggle","TASK_ID","todo","callback","syncToggle","isFunction","DoNothing","q","self","apply","m","args","rp","execute","done","errors","result","call","ix","sort","a","b","redrawControls","complete","total","showControlIf","completeCounters","redrawPool","ongoingPool","create","completePool","append","moveNodePool","from","to","childNodes","isPlainObject","cnt","ACTION","max","maxItemId","getGreatestSortIndex","switchControl","focus","makeItemData","RESULT","DATA","disable","enable","text","toggleClass","condition","addClass","removeClass","count"],"mappings":"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"}