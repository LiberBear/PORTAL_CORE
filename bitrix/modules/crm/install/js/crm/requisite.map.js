{"version":3,"file":"requisite.min.js","sources":["requisite.js"],"names":["BX","namespace","Crm","RequisitePresetSelectorClass","parameters","this","id","type","isNotEmptyString","editor","container","nextNode","isElementNode","position","content","requisiteEntityTypeId","requisiteEntityId","presetList","presetLastSelectedId","parseInt","ajaxUrl","requisiteEditHandler","curPreset","title","curPresetName","labelElement","buttonElement","_menuId","_isMenuShown","_buttonClickHandler","delegate","onButtonClick","_menuIiemClickHandler","onMenuItemClick","_menuCloseHandler","onMenuClose","length","i","setTimeout","saveLastSelectedPresetId","buildContent","prototype","create","text","attrs","className","children","getMessage","events","click","onSelectorClick","ajust","bind","style","display","selectPreset","item","setTextContent","msgId","getNextNode","setNextNode","node","parentNode","removeChild","firstChild","insertBefore","appendChild","showError","msg","alert","e","showMenu","menuItems","push","value","href","onclick","PopupMenu","Data","popupWindow","destroy","anchor","anchorPos","pos","show","autoHide","offsetLeft","angle","offset","onPopupClose","closeMenu","PreventDefault","data","action","presetId","ajax","post","RequisitePopupFormManagerClass","_random","Math","random","toString","substring","_index","blockArea","requisiteId","requisiteData","requisiteDataSign","multiAddressEditor","requisiteAjaxUrl","requisitePopupAjaxUrl","isRequestRunning","wrapper","popup","formId","formSettingManager","formCreateHandler","onFormCreate","editorPopupDestroyCallback","popupDestroyCallback","blockIndex","isNumber","afterRequisiteEditCallback","copyMode","register","getWrapperNode","getFieldControl","fieldName","ctrls","document","getElementsByName","setFieldValue","val","ctrl","setupFields","fields","isFunction","window","inputs","n","hasOwnProperty","addressData","j","address","addressTypeId","addressEditor","getItemByTypeId","createItem","setup","openPopup","startLoadRequest","closePopup","close","reloadPopup","startReloadRequest","urlParams","util","urlencode","url","method","dataType","prepareData","onsuccess","onLoadRequestSuccess","onfailure","onRequestFailure","form","getForm","props","name","submitAjax","onReloadRequestSuccess","startFormSubmitRequest","onFormSubmitRequestSuccess","addCustomEvent","onFormManagerCreate","PopupWindow","overlay","opacity","draggable","offsetTop","bindOptions","forceBindPosition","closeByEsc","closeIcon","top","right","zIndex","titleBar","preparePopupTitle","onPopupShow","opPopupShow","opPopupClose","onPopupDestroy","preparePopupContent","buttons","prepareButtons","innerHTML","response","hiddenRequisiteId","requisiteDataNode","requisiteDataSignNode","needClosePopup","findChild","tag","attr","updateBlock","addBlock","eventArgs","removeCustomEvent","enableClientResolution","isBoolean","countryId","input","RequisiteFieldController","typeId","RequisiteFieldType","itin","serviceUrl","callbacks","onFieldsLoad","editors","CrmMultipleAddressEditor","getItemsByFormId","onAddressCreate","html","result","PopupWindowButton","onSaveBtnClick","PopupWindowButtonLink","onCloseBtnClick","remove","sender","onFormReload","unregister","element","textContent","undefined","innerText","_id","_settings","_countryId","_typeId","_input","_value","_needle","_timeoutId","_keyPressHandler","onKeyPress","_timeoutHandler","onTimeout","_serviceUrl","_isRequestRunning","_dialog","initialize","settings","getSetting","getId","defaultval","validate","replace","search","startSearchRequest","openDialog","searchResult","closeDialog","items","isArray","ExternalRequisiteDialog","open","addClass","findParent","ACTION","PROPERTY_TYPE_ID","PROPERTY_VALUE","COUNTRY_ID","onSearchRequestSuccess","event","c","keyCode","clearTimeout","removeClass","isPlainObject","self","_callbacks","_anchor","_itemData","_items","cb","messages","windows","prepareContent","onDialogShow","onDialogClose","onDialogDestroy","processItemSelection","getFields","width","qty","list","ExternalRequisiteDialogItem","dialog","layout","_data","_container","_element","_onClickHandler","onClick","_hasLayout","getCaption","clearLayout"],"mappings":"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"}