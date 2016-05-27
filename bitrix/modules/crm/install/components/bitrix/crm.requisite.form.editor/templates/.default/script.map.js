{"version":3,"file":"script.min.js","sources":["script.js"],"names":["BX","namespace","Crm","RequisiteFormEditor","parameters","this","containerId","container","requisiteEntityTypeId","requisiteEntityId","presetList","presetLastSelectedId","requisiteDataList","visible","messages","presetSelector","requisitePopupManager","requisitePopupAjaxUrl","requisiteFormEditorAjaxUrl","blockArea","prototype","initialize","style","display","requisiteEditHandler","delegate","onRequisiteEdit","RequisiteFormEditorBlockArea","nextNode","editor","ajaxUrl","RequisitePresetSelectorClass","getWrapperNode","getMessage","msgId","presetId","requisiteId","requisiteData","requisiteDataSign","blockIndex","copyMode","type","isNumber","isNotEmptyString","parseInt","RequisitePopupFormManagerClass","popupDestroyCallback","onRequisitePopupDestroy","openPopup","destroy","items","create","id","self","wrapper","blockList","attrs","class","insertBefore","appendChild","Array","i","length","addBlock","block","RequisiteFormEditorBlock","updateBlock","update","onBlockDestroy","splice","reindexBlocks","indexFrom","setIndex","requisiteDataJson","entityTypeId","entityId","viewData","isRequestRunning","closeButtonNode","closeButtonClickHandler","requisiteDataInputNode","requisiteDataSignInputNode","parseJSON","clean","data-tab-block","titleNode","html","util","htmlspecialchars","closeButton","table","row","cell","fields","insertRow","insertCell","className","innerHTML","nl2br","onCloseButtonClick","bind","titleClickHandler","onBlockTitleClick","name","value","startRequisiteDeleteRequest","ajax","url","method","dataType","data","action","requisite_id","onsuccess","onRequisiteDeleteRequestSuccess","onfailure","onRequestFailure","index","setAttribute","errRequisiteNotFound","cleanAll","cleanNode","unbind","setTextContent","element","textContent","undefined","innerText","RequisiteFormManager","_id","_settings","_entityTypeId","_entityId","_countryId","_container","_isVisible","_presetList","_presetSelector","_presetLastSelectedId","_presetSelectHandler","onPresetSelect","_pseudoIdSequence","_fieldNameTemplate","_enableFieldMasquerading","_formCreateHandler","onFormCreate","_forms","_isRequestRunning","_requestForm","_formLoaderUrl","_serviceUrl","settings","getSetting","isArray","isNaN","position","addCustomEvent","window","getId","defaultval","hasOwnProperty","messageId","getEntityTypeId","getEntityId","startLoadRequest","requisitePseudoId","toString","eventArgs","formId","elementId","enableFieldMasquerading","isBoolean","fieldNameTemplate","countryId","enableClientResolution","form","RequisiteInnerForm","manager","settingManagerId","toLowerCase","serviceUrl","topForm","getTopmostForm","getWrapper","sort","getSort","setSort","result","current","reloadForm","getPresetId","getElementId","getElementPseudoId","params","urlParams","urlencode","add_url_param","onLoadRequestSuccess","response","achor","getNextSiblingWrapper","release","node","childNodes","childNode","removeChild","_manager","_containerId","_elementId","_pseudoId","_sort","_presetId","_enableClientResolution","_multiAddressEditor","_settingManagerId","_settingManager","_settingManagerCreateHandler","onFormSettingManagerCreate","_settingManagerSaveHandler","onFormSettingManagerSave","_settingManagerSectionEditHandler","onFormSettingManagerSectionEdit","_settingManagerSectionRemoveHandler","onFormSettingManagerSectionRemove","_settingManagerFormReloadHandler","onFormSettingManagerFormReload","_addressCreateHandler","onAddressCreate","_isMarkedAsDeleted","CrmFormSettingManager","getItemById","input","getFieldControl","RequisiteFieldController","typeId","RequisiteFieldType","itin","callbacks","onFieldsLoad","setupFields","editors","CrmMultipleAddressEditor","getItemsByFormId","removeNode","formManager","getManager","CrmEditFormManager","getContainer","remove","parentNode","isElementNode","findNextSibling","removeCustomEvent","fieldName","ctrls","document","getElementsByName","resolveFieldInputName","getFieldValue","ctrl","setFieldValue","val","isFunction","inputs","n","addressData","j","address","addressTypeId","addressEditor","getItemByTypeId","createItem","setup","replace","markAsDeleted","props","isMarkedAsDeleted","replaceIdentity","section","field","getAssociatedField","getName","sender","getOriginatorId","setupByEntity"],"mappings":"AAAAA,GAAGC,UAAU,SAEb,UAAUD,IAAGE,IAAuB,sBAAM,YAC1C,CACCF,GAAGE,IAAIC,oBAAsB,SAAUC,GAEtCC,KAAKC,YAAcF,EAAWE,WAC9BD,MAAKE,UAAYP,GAAGK,KAAKC,YACzBD,MAAKG,sBAAwBJ,EAAWI,qBACxCH,MAAKI,kBAAoBL,EAAWK,iBACpCJ,MAAKK,WAAaN,EAAWM,UAC7BL,MAAKM,qBAAuBP,EAAWO,oBACvCN,MAAKO,kBAAoBR,EAAWQ,iBACpCP,MAAKQ,UAAYT,EAAWS,OAC5BR,MAAKS,SAAWV,EAAWU,YAC3BT,MAAKU,eAAiB,IACtBV,MAAKW,sBAAwB,IAC7BX,MAAKY,sBAAwBb,EAAWa,qBACxCZ,MAAKa,2BAA6Bd,EAAWc,0BAC7Cb,MAAKc,UAAY,KAElBnB,IAAGE,IAAIC,oBAAoBiB,WAE1BC,WAAY,WAEX,GAAIhB,KAAKE,UACT,CACC,GAAIF,KAAKQ,QACRR,KAAKE,UAAUe,MAAMC,QAAU,OAEhClB,MAAKmB,qBAAuBxB,GAAGyB,SAASpB,KAAKqB,gBAAiBrB,KAE9D,KAAKA,KAAKc,UACV,CACCd,KAAKc,UAAY,GAAInB,IAAGE,IAAIyB,8BAC3BpB,UAAWF,KAAKE,UAChBqB,SAAU,KACVC,OAAQxB,KACRO,kBAAmBP,KAAKO,kBACxBkB,QAASzB,KAAKa,2BACdM,qBAAsBnB,KAAKmB,uBAI7B,IAAKnB,KAAKU,eACV,CACCV,KAAKU,eAAiB,GAAIf,IAAGE,IAAI6B,8BAChCF,OAAQxB,KACRE,UAAWF,KAAKE,UAChBqB,SAAYvB,KAAc,UAAIA,KAAKc,UAAUa,iBAAmB,KAChE1B,YAAaD,KAAKC,YAClBE,sBAAuBH,KAAKG,sBAC5BC,kBAAmBJ,KAAKI,kBACxBC,WAAYL,KAAKK,WACjBC,qBAAsBN,KAAKM,qBAC3Ba,qBAAsBnB,KAAKmB,0BAK/BS,WAAY,SAASC,GAEpB,MAAO7B,MAAKS,SAASoB,IAEtBR,gBAAiB,SAASlB,EAAuBC,EAAmB0B,EAAUC,EAAaC,EACzDC,EAAmBC,EAAYC,GAEhE,GAAIxC,GAAGyC,KAAKC,SAASH,IAAeA,GAAc,GAAKvC,GAAGyC,KAAKE,iBAAiBJ,GAC/EA,EAAaK,SAASL,OAEtBA,IAAc,CAEfF,GAAiBrC,GAAGyC,KAAKE,iBAAiBN,GAAkBA,EAAgB,EAC5EC,GAAqBtC,GAAGyC,KAAKE,iBAAiBL,GAAsBA,EAAoB,EACxFE,KAAaA,CAEb,KAAKnC,KAAKW,sBACV,CACCX,KAAKW,sBAAwB,GAAIhB,IAAGE,IAAI2C,gCACvChB,OAAQxB,KACRc,UAAWd,KAAKc,UAChBX,sBAAuBA,EACvBC,kBAAmBA,EACnB2B,YAAaA,EACbC,cAAeA,EACfC,kBAAmBA,EACnBH,SAAUA,EACVlB,sBAAuBZ,KAAKY,sBAC5B6B,qBAAsB9C,GAAGyB,SAASpB,KAAK0C,wBAAyB1C,MAChEkC,WAAYA,EACZC,SAAUA,GAEXnC,MAAKW,sBAAsBgC,cAG7BD,wBAAyB,WAExB,GAAI1C,KAAKW,sBACT,CACCX,KAAKW,sBAAsBiC,SAC3B5C,MAAKW,sBAAwB,OAIhChB,IAAGE,IAAIC,oBAAoB+C,QAC3BlD,IAAGE,IAAIC,oBAAoBgD,OAAS,SAAUC,EAAIhD,GAEjD,GAAIiD,GAAO,GAAIrD,IAAGE,IAAIC,oBAAoBC,EAC1CiD,GAAKhC,YAELhB,MAAK6C,MAAME,GAAMC,CACjB,OAAOA,IAIT,SAAUrD,IAAGE,IAAgC,+BAAM,YACnD,CACCF,GAAGE,IAAIyB,6BAA+B,SAAUvB,GAE/CC,KAAKwB,OAASzB,EAAWyB,MACzBxB,MAAKO,kBAAoBR,EAAWQ,iBACpCP,MAAKyB,QAAU1B,EAAW0B,OAC1BzB,MAAKE,UAAYH,EAAWG,SAC5BF,MAAKuB,SAAWxB,EAAWwB,QAC3BvB,MAAKmB,qBAAuBpB,EAAWoB,oBACvCnB,MAAKiD,QAAU,IAEfjD,MAAKkD,YAEL,IAAIlD,KAAKE,UACT,CACCF,KAAKiD,QAAUtD,GAAGmD,OAAO,OAAQK,OAAUC,QAAS,+BACpD,IAAIpD,KAAKiD,QACT,CACC,GAAIjD,KAAKuB,SACRvB,KAAKE,UAAUmD,aAAarD,KAAKiD,QAASjD,KAAKuB,cAE/CvB,MAAKE,UAAUoD,YAAYtD,KAAKiD,UAInC,GAAIjD,KAAKO,mBAAqBP,KAAKO,4BAA6BgD,OAChE,CACC,GAAIvB,EACJ,KAAK,GAAIwB,GAAI,EAAGA,EAAIxD,KAAKO,kBAAkBkD,OAAQD,IACnD,CACC,GAAIxD,KAAKO,kBAAkBiD,GAAG,gBAAkBxD,KAAKO,kBAAkBiD,GAAG,kBACtExD,KAAKO,kBAAkBiD,GAAG,qBAC9B,CACCxD,KAAK0D,SACJ1D,KAAKO,kBAAkBiD,GAAG,eAC1BxD,KAAKO,kBAAkBiD,GAAG,iBAC1BxD,KAAKO,kBAAkBiD,GAAG,yBAM/B7D,IAAGE,IAAIyB,6BAA6BP,WAEnCY,eAAgB,WAEf,MAAO3B,MAAKiD,SAEbS,SAAU,SAAS3B,EAAaC,EAAeC,GAE9C,GAAIC,GAAalC,KAAKkD,UAAUO,MAChC,IAAIE,GAAQ,GAAIhE,IAAGE,IAAI+D,0BACtBpC,OAAQxB,KAAKwB,OACbV,UAAWd,KACXkC,WAAYA,EACZT,QAASzB,KAAKyB,QACdvB,UAAWF,KAAKiD,QAChB1B,SAAU,KACVJ,qBAAsBnB,KAAKmB,qBAC3BY,YAAaA,EACbC,cAAeA,EACfC,kBAAmBA,GAGpB,IAAI0B,EACH3D,KAAKkD,UAAUhB,GAAcyB,GAE/BE,YAAa,SAAS3B,EAAYH,EAAaC,EAAeC,GAE7DC,EAAcvC,GAAGyC,KAAKC,SAASH,IAAeA,GAAc,GAAKvC,GAAGyC,KAAKE,iBAAiBJ,GACzFK,SAASL,IAAe,CAEzB,IAAIA,GAAc,EAClB,CACC,GAAIyB,GAAQ3D,KAAKkD,UAAUhB,EAC3B,IAAIyB,EACJ,CACCA,EAAMG,QACL/B,YAAaA,EACbC,cAAeA,EACfC,kBAAmBA,OAKvB8B,eAAgB,SAAS7B,GAExB,GAAIA,GAAc,GAAKlC,KAAKkD,WAAalD,KAAKkD,UAAUO,OAASvB,EACjE,CACClC,KAAKkD,UAAUc,OAAO9B,EAAY,EAClClC,MAAKiE,cAAc/B,KAGrB+B,cAAe,SAASC,GAEvB,IAAK,GAAIV,GAAIU,EAAWV,EAAIxD,KAAKkD,UAAUO,OAAQD,IAClDxD,KAAKkD,UAAUM,GAAGW,SAASX,KAK/B,SAAU7D,IAAGE,IAA4B,2BAAM,YAC/C,CACCF,GAAGE,IAAI+D,yBAA2B,SAAU7D,GAE3CC,KAAKwB,OAASzB,EAAWyB,MACzBxB,MAAKc,UAAYf,EAAWe,SAC5Bd,MAAKkC,WAAanC,EAAWmC,UAC7BlC,MAAKyB,QAAU1B,EAAW0B,OAC1BzB,MAAKE,UAAYH,EAAWG,SAC5BF,MAAKuB,SAAWxB,EAAWwB,QAC3BvB,MAAK+B,YAAcQ,SAASxC,EAAWgC,YACvC/B,MAAKoE,kBAAoBrE,EAAWiC,aACpChC,MAAKiC,kBAAoBlC,EAAWkC,iBACpCjC,MAAKmB,qBAAuBpB,EAAWoB,oBAEvCnB,MAAKgC,cAAgB,IACrBhC,MAAKqE,aAAe,CACpBrE,MAAKsE,SAAW,CAChBtE,MAAK8B,SAAW,CAEhB9B,MAAKuE,SAAW,IAChBvE,MAAKwE,iBAAmB,KAExBxE,MAAKiD,QAAU,IACfjD,MAAKyE,gBAAkB,IACvBzE,MAAK0E,wBAA0B,IAC/B1E,MAAK2E,uBAAyB,IAC9B3E,MAAK4E,2BAA6B,IAElC5E,MAAKgB,aAENrB,IAAGE,IAAI+D,yBAAyB7C,WAE/B+C,OAAQ,SAAS/D,GAEhBC,KAAK+B,YAAcQ,SAASxC,EAAWgC,YACvC/B,MAAKoE,kBAAoBrE,EAAWiC,aACpChC,MAAKiC,kBAAoBlC,EAAWkC,iBAEpCjC,MAAKgC,cAAgB,IACrBhC,MAAKqE,aAAe,CACpBrE,MAAKsE,SAAW,CAChBtE,MAAK8B,SAAW,CAEhB9B,MAAKuE,SAAW,IAChBvE,MAAKwE,iBAAmB,KAExBxE,MAAKgB,cAENA,WAAY,WAEXhB,KAAKgC,cAAgBrC,GAAGkF,UAAU7E,KAAKoE,kBAAmBpE,KAE1D,IAAIA,KAAKgC,eAAiBhC,KAAKgC,cAAc,UAC7C,CACChC,KAAKqE,aAAe9B,SAASvC,KAAKgC,cAAc,UAAU,kBAC1DhC,MAAKsE,SAAW/B,SAASvC,KAAKgC,cAAc,UAAU,aACtDhC,MAAK8B,SAAWS,SAASvC,KAAKgC,cAAc,UAAU,cAGvD,GAAIhC,KAAKgC,eAAiBhC,KAAKgC,cAAc,YAC7C,CACChC,KAAKuE,SAAWvE,KAAKgC,cAAc,YAGpC,GAAIhC,KAAKE,UACT,CACCF,KAAK8E,OAEL,KAAK9E,KAAKiD,QACV,CACCjD,KAAKiD,QAAUtD,GAAGmD,OACjB,OAECK,OAAUC,QAAS,sBAAuB2B,iBAAkB,aAG9D,IAAI/E,KAAKiD,QACT,CACC,GAAIjD,KAAKuB,SACRvB,KAAKE,UAAUmD,aAAarD,KAAKiD,QAASjD,KAAKuB,cAE/CvB,MAAKE,UAAUoD,YAAYtD,KAAKiD,WAKpC,GAAIjD,KAAKiD,QACT,CACC,GAAIjD,KAAKuE,UAAYvE,KAAKuE,SAAS,SACnC,CACC,GAAIS,GAAYrF,GAAGmD,OAClB,OAECK,OAAUC,QAAS,4BAA6BnC,MAAS,oBACzDgE,KAAQtF,GAAGuF,KAAKC,iBAAiBnF,KAAKuE,SAAS,WAGjDvE,MAAKiD,QAAQK,YAAY0B,EAEzB,IAAII,GACHzF,GAAGmD,OAAO,QAASK,OAAUC,QAAS,0BAA2B2B,iBAAkB,aACpF/E,MAAKiD,QAAQK,YAAY8B,EAEzB,IAAIpF,KAAKuE,SAAS,UAClB,CACC,GAAIc,GAAOC,EAAKC,EAAM/B,CACtB,IAAIgC,GAASxF,KAAKuE,SAAS,SAC3B,IAAIiB,YAAkBjC,QAASiC,EAAO/B,OAAS,EAC/C,CACC4B,EAAQ1F,GAAGmD,OAAO,SAAUK,OAAUC,QAAS,wBAC/C,KAAKI,EAAI,EAAGA,EAAIgC,EAAO/B,OAAQD,IAC/B,CACC8B,EAAMD,EAAMI,WAAW,EACvBF,GAAOD,EAAII,YAAY,EACvBH,GAAKI,UAAY,oBACjBJ,GAAKK,WACFJ,EAAOhC,GAAG,SAAY7D,GAAGuF,KAAKC,iBAAiBK,EAAOhC,GAAG,UAAY,IAAM,GAC9E+B,GAAOD,EAAII,YAAY,EACvBH,GAAKI,UAAY,oBACjBJ,GAAKK,UACHJ,EAAOhC,GAAG,aACV7D,GAAGuF,KAAKW,MAAMlG,GAAGuF,KAAKC,iBAAiBK,EAAOhC,GAAG,eAAiB,GAErExD,KAAKiD,QAAQK,YAAY+B,IAG3B,GAAID,EACJ,CACCpF,KAAKyE,gBAAkBW,CACvBpF,MAAK0E,wBAA0B/E,GAAGyB,SAASpB,KAAK8F,mBAAoB9F,KACpEL,IAAGoG,KAAK/F,KAAKyE,gBAAiB,QAASzE,KAAK0E,yBAE7C,GAAIM,EACJ,CACChF,KAAKgF,UAAYA,CACjBhF,MAAKgG,kBAAoBrG,GAAGyB,SAASpB,KAAKiG,kBAAmBjG,KAC7DL,IAAGoG,KAAK/F,KAAKgF,UAAW,QAAShF,KAAKgG,oBAGxC,GAAIhG,KAAK+B,cAAgB,GAAK/B,KAAKoE,mBAAqBpE,KAAKiC,kBAC7D,CACCjC,KAAK2E,uBAAyBhF,GAAGmD,OAChC,SAECK,OACCf,KAAQ,SACR8D,KAAQ,kBAAoBlG,KAAKkC,WAAa,IAC9CiE,MAASnG,KAAKoE,oBAIjBpE,MAAKiD,QAAQK,YAAYtD,KAAK2E,uBAC9B3E,MAAK4E,2BAA6BjF,GAAGmD,OACpC,SAECK,OACCf,KAAQ,SACR8D,KAAQ,uBAAyBlG,KAAKkC,WAAa,IACnDiE,MAASnG,KAAKiC,oBAIjBjC,MAAKiD,QAAQK,YAAYtD,KAAK4E,+BAIjCjD,eAAgB,WAEf,MAAO3B,MAAKiD,SAEb6C,mBAAoB,WAEnB,GAAI9F,KAAK+B,YAAc,EACtB/B,KAAKoG,4BAA4BpG,KAAK+B,iBAEtC/B,MAAK4C,WAEPqD,kBAAmB,WAElB,GAAIjG,KAAKmB,qBACRnB,KAAKmB,qBACJnB,KAAKqE,aACLrE,KAAKsE,SACLtE,KAAK8B,SACL9B,KAAK+B,YACL/B,KAAKoE,kBACLpE,KAAKiC,kBACLjC,KAAKkC,aAGRkE,4BAA6B,SAASrE,GAErCA,EAAcQ,SAASR,EACvB,IAAG/B,KAAKwE,iBACP,MAEDxE,MAAKwE,iBAAmB,IACxB7E,IAAG0G,MAEDC,IAAKtG,KAAKyB,QACV8E,OAAQ,OACRC,SAAU,OACVC,MACCC,OAAU,kBACVC,aAAgB5E,GAEjB6E,UAAWjH,GAAGyB,SAASpB,KAAK6G,gCAAiC7G,MAC7D8G,UAAWnH,GAAGyB,SAASpB,KAAK+G,iBAAkB/G,SAIjDmE,SAAU,SAAS6C,GAElBhH,KAAKkC,WAAa8E,CAClB,IAAIhH,KAAK2E,uBACR3E,KAAK2E,uBAAuBsC,aAAa,OAAQ,kBAAoBjH,KAAKkC,WAAa,IACxF,IAAIlC,KAAK4E,2BACR5E,KAAK4E,2BAA2BqC,aAAa,OAAQ,uBAAyBjH,KAAKkC,WAAa,MAElG2E,gCAAiC,SAASJ,GAEzC,GAAI7D,GAAU,KACd,IAAIsE,GAAuB,CAC3B,IAAIT,GAAQA,EAAK,UACjB,CACC,GAAIA,EAAK,YAAc,WAAaA,EAAK,aAAeA,EAAK,YAAY,OACrElE,SAASkE,EAAK,YAAY,QAAUzG,KAAK+B,YAC7C,CACCa,EAAU,SAEN,IAAI6D,EAAK,YAAc,SAAWA,EAAK,WAAaA,EAAK,UAAU,IACpEA,EAAK,UAAU,GAAG,SAClBlE,SAASkE,EAAK,UAAU,GAAG,WAAaS,EAC5C,CACCtE,EAAU,MAGZ5C,KAAKwE,iBAAmB,KACxB,IAAI5B,EACH5C,KAAK4C,WAEPmE,iBAAkB,SAASN,GAE1BzG,KAAKwE,iBAAmB,OAEzBM,MAAO,SAASqC,GAEfA,IAAaA,CACb,IAAInH,KAAKiD,QACT,CACC,GAAGjD,KAAK2E,uBACR,CACChF,GAAGyH,UAAUpH,KAAK2E,uBAAwB,KAC1C3E,MAAK2E,uBAAyB,KAE/B,GAAG3E,KAAK4E,2BACR,CACCjF,GAAGyH,UAAUpH,KAAK4E,2BAA4B,KAC9C5E,MAAK4E,2BAA6B,KAEnC,GAAI5E,KAAKgF,UACT,CACCrF,GAAG0H,OAAOrH,KAAKgF,UAAW,QAAShF,KAAKgG,kBACxChG,MAAKgG,kBAAoB,IACzBhG,MAAKgF,UAAY,KAElB,GAAIhF,KAAKyE,gBACT,CACC9E,GAAG0H,OAAOrH,KAAKyE,gBAAiB,QAASzE,KAAK0E,wBAC9C1E,MAAK0E,wBAA0B,IAC/B1E,MAAKyE,gBAAkB,KAGxB9E,GAAGyH,UAAUpH,KAAKiD,QAASkE,KAG7BvE,QAAS,WAER5C,KAAK8E,MAAM,KAEX,IAAI9E,KAAKc,UACRd,KAAKc,UAAUiD,eAAe/D,KAAKkC,cAKvC,SAAUvC,IAAiB,iBAAM,YACjC,CACCA,GAAG2H,eAAiB,SAASC,EAASpB,GAErC,GAAIoB,EACJ,CACC,GAAIA,EAAQC,cAAgBC,UAC3BF,EAAQC,YAAcrB,MAEtBoB,GAAQG,UAAYvB,IAKxB,SAAUxG,IAAGE,IAAwB,uBAAM,YAC3C,CACCF,GAAGE,IAAI8H,qBAAuB,WAE7B3H,KAAK4H,IAAM,EACX5H,MAAK6H,YACL7H,MAAK8H,cAAgB,CACrB9H,MAAK+H,UAAY,CACjB/H,MAAKgI,WAAa,CAClBhI,MAAKiI,WAAa,IAClBjI,MAAKkI,WAAa,IAClBlI,MAAKmI,YAAc,IACnBnI,MAAKoI,gBAAkB,IACvBpI,MAAKqI,sBAAwB,CAC7BrI,MAAKsI,qBAAuB3I,GAAGyB,SAASpB,KAAKuI,eAAgBvI,KAC7DA,MAAKwI,kBAAoB,CACzBxI,MAAKyI,mBAAqB,EAC1BzI,MAAK0I,yBAA2B,KAChC1I,MAAK2I,mBAAqBhJ,GAAGyB,SAASpB,KAAK4I,aAAc5I,KACzDA,MAAK6I,SACL7I,MAAK8I,kBAAoB,KACzB9I,MAAK+I,aAAe,IACpB/I,MAAKgJ,eAAiB,EACtBhJ,MAAKiJ,YAAc,GAEpBtJ,IAAGE,IAAI8H,qBAAqB5G,WAE3BC,WAAY,SAAS+B,EAAImG,GAExBlJ,KAAK4H,IAAMjI,GAAGyC,KAAKE,iBAAiBS,GAAMA,EAAK,4BAC/C/C,MAAK6H,UAAYqB,EAAWA,IAE5BlJ,MAAKiI,WAAatI,GAAGK,KAAKmJ,WAAW,cAAe,IACpD,KAAInJ,KAAKiI,WACT,CACC,KAAM,yDAGPjI,KAAKgJ,eAAiBhJ,KAAKmJ,WAAW,gBAAiB,GACvD,KAAIxJ,GAAGyC,KAAKE,iBAAiBtC,KAAKgJ,gBAClC,CACC,KAAM,qFAGPhJ,KAAKiJ,YAAcjJ,KAAKmJ,WAAW,aAAc,GACjD,KAAIxJ,GAAGyC,KAAKE,iBAAiBtC,KAAKiJ,aAClC,CACC,KAAM,kFAGPjJ,KAAKyI,mBAAqBzI,KAAKmJ,WAAW,oBAAqB,GAC/DnJ,MAAK0I,yBAA2B1I,KAAKyI,qBAAuB,EAE5DzI,MAAK8H,cAAgBvF,SAASvC,KAAKmJ,WAAW,eAAgB,GAC9DnJ,MAAK+H,UAAYxF,SAASvC,KAAKmJ,WAAW,WAAY,GACtDnJ,MAAKmI,YAAcnI,KAAKmJ,WAAW,aAAc,KACjD,KAAIxJ,GAAGyC,KAAKgH,QAAQpJ,KAAKmI,aACzB,CACCnI,KAAKmI,eAGNnI,KAAKgI,WAAazF,SAASvC,KAAKmJ,WAAW,YAAa,GACxD,IAAGE,MAAMrJ,KAAKgI,aAAehI,KAAKgI,WAAa,EAC/C,CACChI,KAAKgI,WAAa,EAGnBhI,KAAKqI,sBAAwB9F,SAASvC,KAAKmJ,WAAW,uBAAwB,GAE9EnJ,MAAKkI,aAAelI,KAAKmJ,WAAW,YAAa,KACjDnJ,MAAKiI,WAAWhH,MAAMC,QAAUlB,KAAKkI,WAAa,GAAK,MAEvDlI,MAAKoI,gBAAkB,GAAIzI,IAAGE,IAAI6B,8BAEhCF,OAAQxB,KACR+C,GAAI/C,KAAK4H,IACT1H,UAAWF,KAAKiI,WAChBqB,SAAU,MACVnJ,sBAAuBH,KAAK8H,cAC5B1H,kBAAmBJ,KAAK+H,UACxB1H,WAAYL,KAAKmI,YACjB7H,qBAAsBN,KAAKqI,sBAC3BlH,qBAAsBnB,KAAKsI,sBAI7B3I,IAAG4J,eAAeC,OAAQ,6BAA8BxJ,KAAK2I,qBAG9Dc,MAAO,WAEN,MAAOzJ,MAAK4H,KAEbuB,WAAY,SAAUjD,EAAMwD,GAE3B,MAAO1J,MAAK6H,UAAU8B,eAAezD,GAAQlG,KAAK6H,UAAU3B,GAAQwD,GAErE9H,WAAY,SAASgI,GAEpB,GAAInJ,GAAWd,GAAGE,IAAI8H,qBAAqBlH,QAC3C,OAAOA,GAASkJ,eAAeC,GAAanJ,EAASmJ,GAAaA,GAEnEC,gBAAiB,WAEhB,MAAO7J,MAAK8H,eAEbgC,YAAa,WAEZ,MAAO9J,MAAK+H,WAEbQ,eAAgB,SAASlE,EAAcC,EAAUxC,GAEhD9B,KAAK+J,kBAEHjI,SAAUS,SAAST,GACnBC,YAAa,EACbiI,kBAAmB,KAAOhK,KAAKwI,qBAAqByB,cAIvDrB,aAAc,SAASsB,GAEtB,GAAIC,GAASD,EAAU,SAEvB,IAAGlK,KAAK6I,OAAOsB,GACf,OACQnK,MAAK6I,OAAOsB,GAGpB,GAAIC,GAAYzK,GAAGyC,KAAKC,SAAS6H,EAAU,cACxCA,EAAU,aAAe,CAE5B,IAAIG,GAA0B1K,GAAGyC,KAAKkI,UAAUJ,EAAU,4BACvDA,EAAU,2BAA6BlK,KAAK0I,wBAC/C,IAAI6B,GAAoB5K,GAAGyC,KAAKE,iBAAiB4H,EAAU,sBACxDA,EAAU,qBAAuBlK,KAAKyI,kBAEzC,IAAIxI,GAAcN,GAAGyC,KAAKE,iBAAiB4H,EAAU,gBAClDA,EAAU,eAAkB,aAAeC,CAE9C,IAAIK,GAAY7K,GAAGyC,KAAKC,SAAS6H,EAAU,cACxCA,EAAU,aAAelK,KAAKgI,UAEjC,IAAIyC,GAAyB9K,GAAGyC,KAAKkI,UAAUJ,EAAU,2BACtDA,EAAU,0BAA4B,KAEzC,IAAIQ,GAAO/K,GAAGE,IAAI8K,mBAAmB7H,OACpCqH,GAECS,QAAS5K,KACT6K,iBAAkBV,EAAOW,cACzBN,UAAWA,EACXC,uBAAwBA,EACxBxK,YAAaA,EACbmK,UAAWA,EACXC,wBAAyBA,EACzBE,kBAAmBA,EACnBQ,WAAY/K,KAAKiJ,aAKnB,IAAGmB,GAAa,EAChB,CACC,GAAIY,GAAUhL,KAAKiL,gBACnB,IAAGD,EACH,CACChL,KAAKiI,WAAW5E,aAAaqH,EAAKQ,aAAcF,EAAQE,aACxD,IAAIC,GAAOH,EAAQI,SACnB,IAAGD,EAAO,EACV,CACCA,IAGDT,EAAKW,QAAQF,IAIfnL,KAAK6I,OAAOsB,GAAUO,GAEvBO,eAAgB,WAEf,GAAIK,GAAS,IACb,KAAI,GAAInB,KAAUnK,MAAK6I,OACvB,CACC,IAAI7I,KAAK6I,OAAOc,eAAeQ,GAC/B,CACC,SAGD,GAAIoB,GAAUvL,KAAK6I,OAAOsB,EAC1B,IAAGmB,IAAW,MAAQA,EAAOF,UAAYG,EAAQH,UACjD,CACCE,EAASC,GAGX,MAAOD,IAERE,WAAY,SAASd,GAEpB1K,KAAK+J,kBAEHjI,SAAU4I,EAAKe,cACf1J,YAAa2I,EAAKgB,eAClB1B,kBAAmBU,EAAKiB,qBACxBjB,KAAMA,KAITX,iBAAkB,SAAS6B,GAE1B,GAAG5L,KAAK8I,kBACR,CACC,OAGD9I,KAAK8I,kBAAoB,IAEzB,UAAU8C,GAAO,UAAa,YAC9B,CACC5L,KAAK+I,aAAe6C,EAAO,QAG5B,GAAIC,IAAcxH,aAAcrE,KAAK8H,cAAexD,SAAUtE,KAAK+H,UAEnE,IAAIjG,GAAWnC,GAAGyC,KAAKC,SAASuJ,EAAO,aAAeA,EAAO,YAAc,CAC3E,IAAG9J,EAAW,EACd,CACC+J,EAAU,YAAc/J,EAGzB,GAAIC,GAAcpC,GAAGyC,KAAKC,SAASuJ,EAAO,gBAAkBA,EAAO,eAAiB,CACpF,IAAG7J,EAAc,EACjB,CACC8J,EAAU,eAAiB9J,EAG5B,GAAIiI,GAAoBrK,GAAGyC,KAAKE,iBAAiBsJ,EAAO,sBAAwBA,EAAO,qBAAuB,EAC9G,IAAG5B,IAAsB,GACzB,CACC6B,EAAU,qBAAuBD,EAAO,qBAGzC,GAAG5L,KAAK0I,yBACR,CACCmD,EAAU,qBAAuBlM,GAAGuF,KAAK4G,UAAU9L,KAAKyI,oBAGzD9I,GAAG0G,MAEDC,IAAK3G,GAAGuF,KAAK6G,cAAc/L,KAAKgJ,eAAgB6C,GAChDtF,OAAQ,MACRC,SAAU,OACVC,QACAG,UAAWjH,GAAGyB,SAASpB,KAAKgM,qBAAsBhM,MAClD8G,UAAWnH,GAAGyB,SAASpB,KAAK+G,iBAAkB/G,SAIjDgM,qBAAsB,SAASC,GAE9B,GAAIC,GAAQ,IAEZlM,MAAK8I,kBAAoB,KACzB,IAAG9I,KAAK+I,aACR,CACCmD,EAAQlM,KAAK+I,aAAaoD,uBAC1BnM,MAAK+I,aAAaqD,QAAQ,KAC1BpM,MAAK+I,aAAe,KAGrB,GAAIsD,GAAO1M,GAAGmD,OAAO,OAASmC,KAAMgH,GACpC,OAAMI,EAAKC,WAAW7I,OAAS,EAC/B,CACC,GAAI8I,GAAYF,EAAKC,WAAW,EAChC,IAAGJ,EACH,CACClM,KAAKiI,WAAW5E,aAAagJ,EAAKG,YAAYD,GAAYL,OAG3D,CACClM,KAAKiI,WAAW3E,YAAY+I,EAAKG,YAAYD,OAMhDxF,iBAAkB,SAASkF,GAE1BjM,KAAK8I,kBAAoB,KACzB9I,MAAK+I,aAAe,MAGtB,UAAUpJ,IAAGE,IAAI8H,qBAA6B,WAAM,YACpD,CACChI,GAAGE,IAAI8H,qBAAqBlH,YAI7Bd,GAAGE,IAAI8H,qBAAqB9E,QAC5BlD,IAAGE,IAAI8H,qBAAqB7E,OAAS,SAASC,EAAImG,GAEjD,GAAIlG,GAAO,GAAIrD,IAAGE,IAAI8H,oBACtB3E,GAAKhC,WAAW+B,EAAImG,EACpB,OAAQlJ,MAAK6C,MAAMG,EAAKyG,SAAWzG,GAIrC,SAAUrD,IAAGE,IAAsB,qBAAM,YACzC,CACCF,GAAGE,IAAI8K,mBAAqB,WAE3B3K,KAAK4H,IAAM,EACX5H,MAAK6H,YACL7H,MAAKyM,SAAW,IAChBzM,MAAKiJ,YAAc,EACnBjJ,MAAK0M,aAAe,EACpB1M,MAAK2M,WAAa,CAClB3M,MAAK4M,UAAY,IACjB5M,MAAK6M,OAAS,CACd7M,MAAK8M,WAAa,CAClB9M,MAAKgI,WAAa,CAClBhI,MAAK+M,wBAA0B,KAC/B/M,MAAK0I,yBAA2B,KAChC1I,MAAKgN,oBAAsB,IAC3BhN,MAAKyI,mBAAqB,EAC1BzI,MAAKiN,kBAAoB,EACzBjN,MAAKkN,gBAAkB,EACvBlN,MAAKmN,6BAA+BxN,GAAGyB,SAASpB,KAAKoN,2BAA4BpN,KACjFA,MAAKqN,2BAA6B1N,GAAGyB,SAASpB,KAAKsN,yBAA0BtN,KAC7EA,MAAKuN,kCAAoC5N,GAAGyB,SAASpB,KAAKwN,gCAAiCxN,KAC3FA,MAAKyN,oCAAsC9N,GAAGyB,SAASpB,KAAK0N,kCAAmC1N,KAC/FA,MAAK2N,iCAAmChO,GAAGyB,SAASpB,KAAK4N,+BAAgC5N,KACzFA,MAAK2N,iCAAmChO,GAAGyB,SAASpB,KAAK4N,+BAAgC5N,KACzFA,MAAK6N,sBAAwBlO,GAAGyB,SAASpB,KAAK8N,gBAAiB9N,KAC/DA,MAAK+N,mBAAqB,MAE3BpO,IAAGE,IAAI8K,mBAAmB5J,WAEzBC,WAAY,SAAS+B,EAAImG,GAExBlJ,KAAK4H,IAAMjI,GAAGyC,KAAKE,iBAAiBS,GAAMA,EAAK,4BAC/C/C,MAAK6H,UAAYqB,EAAWA,IAE5BlJ,MAAKgI,WAAahI,KAAKmJ,WAAW,YAAa,EAC/CnJ,MAAK+M,wBAA0B/M,KAAKmJ,WAAW,yBAA0B,MAEzEnJ,MAAKyM,SAAWzM,KAAKmJ,WAAW,UAAW,KAC3C,KAAInJ,KAAKyM,SACT,CACC,KAAM,6EAGPzM,KAAKiJ,YAAcjJ,KAAKmJ,WAAW,aAAc,GACjD,KAAIxJ,GAAGyC,KAAKE,iBAAiBtC,KAAKiJ,aAClC,CACC,KAAM,gFAGPjJ,KAAK0M,aAAe1M,KAAKmJ,WAAW,cAAe,GACnDnJ,MAAK2M,WAAapK,SAASvC,KAAKmJ,WAAW,YAAa,GACxDnJ,MAAK0I,2BAA6B1I,KAAKmJ,WAAW,0BAA2B,MAC7EnJ,MAAKyI,mBAAqBzI,KAAKmJ,WAAW,oBAAqB,GAE/DnJ,MAAKiN,kBAAoBjN,KAAKmJ,WAAW,mBAAoB,GAC7D,IAAGnJ,KAAKiN,oBAAsB,GAC9B,CACCjN,KAAKiN,kBAAoBjN,KAAK4H,IAG/B5H,KAAKkN,gBAAkBvN,GAAGqO,sBAAsBC,YAAYjO,KAAKiN,kBAAmB,KACpF,IAAGjN,KAAKkN,gBACR,CACClN,KAAK+F,WAGN,CACCpG,GAAG4J,eAAeC,OAAQ,8BAA+BxJ,KAAKmN,8BAG/D,GAAIe,GAASlO,KAAKmO,gBAAgB,SAClC,IAAGD,GAASlO,KAAK+M,wBACjB,CACCpN,GAAGE,IAAIuO,yBAAyBtL,OAC/B,UAEC0H,UAAWxK,KAAKgI,WAChBqG,OAAQ1O,GAAGE,IAAIyO,mBAAmBC,KAClCL,MAAOA,EACPnD,WAAY/K,KAAKiJ,YACjBuF,WAAaC,aAAc9O,GAAGyB,SAASpB,KAAK0O,YAAa1O,SAK5D,GAAI2O,GAAUhP,GAAGiP,yBAAyBC,iBAAiB7O,KAAK4H,IAChE,IAAG+G,EAAQlL,OAAS,EACpB,CACCzD,KAAKgN,oBAAsB2B,EAAQ,EACnChP,IAAG4J,eAAevJ,KAAKgN,oBAAqB,gCAAiChN,KAAK6N,yBAGpFzB,QAAS,SAAS0C,GAEjBA,IAAeA,CAEf9O,MAAKqH,QACL,IAAGrH,KAAKkN,gBACR,CACC,GAAI6B,GAAc/O,KAAKkN,gBAAgB8B,YACvCD,GAAY3C,QAAQ0C,SACbnP,IAAGsP,mBAAmBpM,MAAMkM,EAAYtF,QAC/CzJ,MAAKkN,gBAAkB,KAGxB,GAAG4B,EACH,CACC,GAAI5O,GAAYF,KAAKkP,cACrB,IAAGhP,EACH,CACCP,GAAGwP,OAAOjP,EAAUkP,eAIvB3F,MAAO,WAEN,MAAOzJ,MAAK4H,KAEbuB,WAAY,SAAUjD,EAAMwD,GAE3B,MAAO1J,MAAK6H,UAAU8B,eAAezD,GAAQlG,KAAK6H,UAAU3B,GAAQwD,GAErEwF,aAAc,WAEb,MAAOvP,IAAGK,KAAK0M,eAEhBxB,WAAY,WAEX,GAAIhL,GAAYF,KAAKkP,cACrB,OAAOvP,IAAGyC,KAAKiN,cAAcnP,GAAaA,EAAUkP,WAAa,MAElEjD,sBAAuB,WAEtB,GAAIlJ,GAAUjD,KAAKkL,YACnB,OAAOvL,IAAGyC,KAAKiN,cAAcpM,GAAWtD,GAAG2P,gBAAgBrM,GAAW0C,UAAW,kCAAqC,MAEvHI,KAAM,WAEL,GAAG/F,KAAKkN,gBACR,CACCvN,GAAG4J,eACFvJ,KAAKkN,gBACL,4BACAlN,KAAKqN,2BAGN1N,IAAG4J,eACFvJ,KAAKkN,gBACL,sCACAlN,KAAKuN,kCAGN5N,IAAG4J,eACFvJ,KAAKkN,gBACL,qCACAlN,KAAKyN,oCAGN9N,IAAG4J,eACFvJ,KAAKkN,gBACL,kCACAlN,KAAK2N,oCAIRtG,OAAQ,WAEP,GAAGrH,KAAKkN,gBACR,CACCvN,GAAG4P,kBACFvP,KAAKkN,gBACL,4BACAlN,KAAKqN,2BAGN1N,IAAG4P,kBACFvP,KAAKkN,gBACL,sCACAlN,KAAKuN,kCAGN5N,IAAG4P,kBACFvP,KAAKkN,gBACL,qCACAlN,KAAKyN,oCAGN9N,IAAG4P,kBACFvP,KAAKkN,gBACL,kCACAlN,KAAK2N,oCAIRjC,aAAc,WAEb,MAAO1L,MAAK2M,YAEbwB,gBAAiB,SAASqB,GAEzB,GAAIC,GAAQC,SAASC,kBAAkB3P,KAAK4P,sBAAsBJ,GAClE,OAAOC,GAAMhM,OAAS,EAAIgM,EAAM,GAAK,MAEtCI,cAAe,SAASL,GAEvB,GAAIM,GAAO9P,KAAKmO,gBAAgBqB,EAChC,OAAOM,KAAS,KAAOA,EAAK3J,MAAQ,IAErC4J,cAAe,SAASP,EAAWQ,GAElC,GAAIF,GAAO9P,KAAKmO,gBAAgBqB,EAChC,IAAGM,IAAS,KACZ,CACCA,EAAK3J,MAAQ6J,IAGftB,YAAa,SAASlJ,GAErB,GAAG7F,GAAGyC,KAAK6N,WAAWzG,OAAO,aAC7B,CACC,GAAI0G,GAAS1G,OAAO,YAAY,4BAA6BxJ,KAAKkP,eAClE,KAAI,GAAIiB,GAAI,EAAGA,EAAID,EAAOzM,OAAQ0M,IAClC,CACCD,EAAOC,GAAGhK,MAAQ,IAIpB,IAAI,GAAI3C,KAAKgC,GACb,CACC,IAAIA,EAAOmE,eAAenG,GAC1B,CACC,SAGD,GAAGA,IAAM,UACT,CACCxD,KAAK+P,cAAcvM,EAAGgC,EAAOhC,QAEzB,IAAGxD,KAAKgN,oBACb,CACC,GAAIoD,GAAc5K,EAAOhC,EACzB,KAAI,GAAI6M,KAAKD,GACb,CACC,IAAIA,EAAYzG,eAAe0G,GAC/B,CACC,SAGD,GAAIC,GAAUF,EAAYC,EAC1B,IAAIE,GAAgBhO,SAAS8N,EAC7B,IAAIG,GAAgBxQ,KAAKgN,oBAAoByD,gBAAgBF,EAC7D,IAAGC,IAAkB,KACrB,CACCA,EAAgBxQ,KAAKgN,oBAAoB0D,WAAWH,EAAevQ,KAAK4H,KAGzE4I,EAAcG,MAAML,OAKxB3E,mBAAoB,WAEnB,GAAG3L,KAAK4M,YAAc,KACtB,CACC5M,KAAK4M,UAAY5M,KAAK6P,cAAc,YAAa,IAGlD,MAAO7P,MAAK4M,WAEbnB,YAAa,WAEZ,GAAGzL,KAAK8M,UAAY,EACpB,CACC9M,KAAK8M,UAAYvK,SAASvC,KAAK6P,cAAc,aAC7C,IAAGxG,MAAMrJ,KAAK8M,WACd,CACC9M,KAAK8M,UAAY,GAInB,MAAO9M,MAAK8M,WAEb1B,QAAS,WAER,GAAGpL,KAAK6M,MAAQ,EAChB,CACC7M,KAAK6M,MAAQtK,SAASvC,KAAK6P,cAAc,QACzC,IAAGxG,MAAMrJ,KAAK6M,OACd,CACC7M,KAAK6M,MAAQ,GAIf,MAAO7M,MAAK6M,OAEbxB,QAAS,SAASF,GAEjB,IAAIxL,GAAGyC,KAAKC,SAAS8I,GACrB,CACCA,EAAO5I,SAAS4I,EAChB,IAAG9B,MAAM8B,GACT,CACCA,EAAO,GAITnL,KAAK6M,MAAQ1B,CACbnL,MAAK+P,cAAc,OAAQ/P,KAAK6M,QAEjC+C,sBAAuB,SAASJ,GAE/B,GAAGxP,KAAK0I,0BAA4B1I,KAAKyI,qBAAuB,GAChE,CACC,MAAOzI,MAAKyI,mBAAmBmI,QAAQ,gBAAiBpB,GAEzD,MAAOA,IAERqB,cAAe,WAEd,GAAG7Q,KAAK+N,mBACR,CACC,OAGD/N,KAAK+N,mBAAqB,IAE1B,IAAI7N,GAAYF,KAAKkP,cACrB,IAAGhP,EACH,CACCA,EAAUoD,YACT3D,GAAGmD,OAAO,SAERgO,OAEC5K,KAAQlG,KAAK4P,sBAAsB,WACnCxN,KAAQ,SACR+D,MAAS,OAKb,IAAIlD,GAAU/C,EAAUkP,UACxBnM,GAAQhC,MAAMC,QAAU,SAG1B6P,kBAAmB,WAElB,MAAO/Q,MAAK+N,oBAEbX,2BAA4B,SAASxC,GAEpC,GAAGA,EAAQnB,QAAQqB,gBAAkB9K,KAAKiN,kBAAkBnC,cAC5D,CACC9K,KAAKkN,gBAAkBtC,CACvB5K,MAAK+F,MAELpG,IAAG4P,kBAAkB/F,OAAQ,8BAA+BxJ,KAAKmN,gCAGnEG,yBAA0B,SAAS1C,EAASV,GAE3C,GAAGlK,KAAKkN,kBAAoBtC,GAAW5K,KAAK0I,yBAC5C,CACC/I,GAAGqO,sBAAsBgD,gBAAgB9G,EAAU,QAAS,WAG9DsD,gCAAiC,SAAS5C,EAASV,GAElD,GAAGlK,KAAKkN,kBAAoBtC,EAC5B,CACCV,EAAU,UAAY,IAEtB,IAAI+G,GAAU/G,EAAU,UACxB,IAAIgH,GAAQD,EAAQE,oBACpB,IAAGD,EACH,CACC,GAAIhD,GAAQvO,GAAGuR,EAAM,MACrB,IAAGhD,EACH,CACCA,EAAM/H,MAAQ8K,EAAQG,cAK1B1D,kCAAmC,SAAS9C,EAASV,GAEpD,GAAGlK,KAAKkN,kBAAoBtC,EAC5B,CACCV,EAAU,UAAY,IACtBlK,MAAK6Q,kBAGPjD,+BAAgC,SAAShD,EAASV,GAEjDA,EAAU,UAAY,IACtBlK,MAAKyM,SAASjB,WAAWxL,OAE1B8N,gBAAiB,SAASuD,EAAQf,GAEjC,GAAGA,EAAQgB,oBAAsBtR,KAAK4H,IACtC,CACC,OAGD,GAAIvD,GAAerE,KAAKyM,SAAS5C,iBACjC,IAAIvF,GAAWtE,KAAKyM,SAAS3C,aAC7B,IAAGzF,EAAe,GAAKC,EAAW,EAClC,CACCgM,EAAQiB,cAAclN,EAAcC,KAIvC3E,IAAGE,IAAI8K,mBAAmB7H,OAAS,SAASC,EAAImG,GAE/C,GAAIlG,GAAO,GAAIrD,IAAGE,IAAI8K,kBACtB3H,GAAKhC,WAAW+B,EAAImG,EACpB,OAAOlG"}