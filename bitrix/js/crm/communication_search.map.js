{"version":3,"file":"communication_search.min.js","sources":["communication_search.js"],"names":["BX","CrmCommunicationSearch","this","_id","_settings","_provider","_dlg","_dlgContainer","_enableSearch","_searchCompletionHandler","delegate","_handleSearchCompletion","_onDlgCloseCallback","_tabData","_items","prototype","initialize","id","settings","type","isNotEmptyString","getSetting","entityType","toUpperCase","CrmCommunicationSearchProvider","create","entityId","serviceUrl","communicationType","enableDataLoading","name","defaultval","getId","getCommunicationType","getDefaultCommunication","getTab","tabId","tabs","i","length","tab","search","needle","ajax","url","method","dataType","data","ACTION","ENTITY_TYPE","ENTITY_ID","COMMUNICATION_TYPE","NEEDLE","async","start","onsuccess","onfailure","openDialog","bindElem","onCloseCallback","isFunction","PopupWindow","autoHide","draggable","offsetLeft","offsetTop","closeByEsc","closeIcon","top","right","events","onPopupShow","onPopupClose","_handleDialogClose","onPopupDestroy","_handleDialogDestroy","content","_prepareDialogContent","buttons","_prepareDialogButtons","show","closeDialog","close","adjustDialogPosition","adjustPosition","selectCommunication","communication","callback","ex","window","console","error","isDataLoaded","prepareDataRequest","requestData","processDataResponse","responseData","e","destroy","activeTab","titleElems","prepareTabData","push","_createTabButton","searchTab","title","messages","active","contentElems","_prepareTabContent","attrs","className","children","value","text","click","_handleButtonClick","_prepareNoData","ary","wrapper","appendChild","result","commType","itemData","itemCommData","item","CrmCommunication","entityTitle","entityDescription","layout","itemWrapper","j","itemComm","_selectTab","activeButtons","findChildren","removeClass","button","findChild","property","addClass","parentNode","contentContainer","cleanNode","event","hidden","findPreviousSibling","target","tagName","self","CrmCommunicationType","undefined","phone","email","_manager","_entityType","_entityId","_commType","_data","manager","parseInt","getEntityData","_loadData","getEntityType","items","comms","setEntityData","_handleRequestCompletion","_handleRequestError","entityData","key","hasOwnProperty","getSettings","orig","copy","p","getType","getOwnerEntityType","getOwnerEntityId","getEntityId","getValue","getEntityTitle","getEntityDescription","_handleClick","val","document","createTextNode","descr","CrmCommunicationSearchController","_input","_value","_isActive","_timeoutId","_checkHandler","check","_keyPressHandler","onKeyPress","input","Math","random","toString","clearTimeout","bind","stop","unbind","setTimeout"],"mappings":"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"}