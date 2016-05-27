{"version":3,"file":"script.min.js","sources":["script.js"],"names":["BX","CrmConfigStatusClass","parameters","this","randomString","tabs","ajaxUrl","data","oldData","clone","max_sort","requestIsRunning","totalNumberFields","checkSubmit","defaultColor","defaultFinalSuccessColor","defaultFinalUnSuccessColor","defaultLineColor","textColorLight","textColorDark","entityId","twoPhaseSettings","DEAL_STAGE","question","message","STATUS","QUOTE_STATUS","INVOICE_STATUS","jsClass","contentIdPrefix","contentClass","contentActiveClass","fieldNameIdPrefix","fieldEditNameIdPrefix","fieldHiddenNameIdPrefix","spanStoringNameIdPrefix","mainDivStorageFieldIdPrefix","fieldSortHiddenIdPrefix","fieldHiddenNumberIdPrefix","extraStorageFieldIdPrefix","finalSuccessStorageFieldIdPrefix","finalStorageFieldIdPrefix","previouslyScaleIdPrefix","previouslyScaleNumberIdPrefix","previouslyScaleFinalSuccessIdPrefix","previouslyScaleNumberFinalSuccessIdPrefix","previouslyScaleFinalUnSuccessIdPrefix","previouslyScaleNumberFinalUnSuccessIdPrefix","previouslyScaleFinalCellIdPrefix","previouslyScaleNumberFinalCellIdPrefix","funnelSuccessIdPrefix","funnelUnSuccessIdPrefix","successFields","unSuccessFields","initialFields","extraFields","finalFields","extraFinalFields","dataFunnel","colorFunnel","initAmChart","footer","windowSize","scrollPosition","contentPosition","footerPosition","limit","footerFixed","blockFixed","initAmCharts","showError","init","prototype","selectTab","tabId","div","className","i","cnt","length","content","showTab","value","processingFooter","AmCharts","handleLoad","on","sel","statusReset","document","forms","submit","recoveryName","fieldId","name","fieldHiddenNumber","searchElement","fieldName","fieldHiddenName","innerHTML","util","htmlspecialchars","NAME","recalculateSort","editField","domElement","fieldDiv","spanStoring","create","props","children","id","attrs","type","onkeydown","onblur","data-onblur","style","width","setAttribute","appendChild","fieldEditName","focus","selectionStart","openPopupBeforeDeleteField","isNaN","parseInt","deleteField","html","modalWindow","modalId","title","overlay","events","onPopupClose","destroy","onAfterPopupShow","popup","findChild","contentContainer","cursor","bind","proxy","_startDrag","buttons","text","click","delegate","e","PopupWindowManager","getCurrentPopup","close","parentNode","fieldHidden","removeChild","params","bindElement","autoHide","closeIcon","right","top","Math","random","withoutContentWrap","contentClassName","contentStyle","withoutWindowManager","contentDialogChildren","push","concat","hasOwnProperty","contentDialog","onPopupShow","firstButtonInModalWindow","_keyPress","proxy_context","closePopup","unbind","_keypress","windowsWithoutManager","PopupWindow","closeByEsc","zIndex","show","saveFieldValue","input","newFieldName","newFieldValue","NAME_INIT","tag","element","findChildren","attribute","addField","color","addCellFinalScale","addCellMainScale","k","ID","SORT","ENTITY_ID","COLOR","insertBefore","createStructureHtml","parentId","structureFields","data-calculate","number","sort","getAttribute","replace","inputFields","fieldSortHidden","data-success","j","changeCellScale","scale","data-scale-type","scaleNumber","scaleFinalSuccess","scaleNumberFinalSuccess","mainCount","scaleCount","deleteCellMainScale","background","getElementsByTagName","scaleFinalUnSuccess","scaleNumberFinalUnSuccess","finalCount","scaleFinalUnSuccessCount","l","deleteCellFinalScale","h","scaleHtml","scaleNumberHtml","quantity","scaleCell","scaleCellNumber","fieldObject","iconClass","blockClass","img","onclick","ondblclick","data-sort","data-space","data-class","data-status-id","getNewStatusId","newStatusId","listInputStatusId","statusId","showPlaceToInsert","replaceableElement","parentElement","spaceId","spaceToInsert","data-place","coords","getCoords","displacementHeight","pageY","middleElement","offsetHeight","deleteSpaceToInsert","insertAfter","putDomElement","beforeElement","spacetoinsert","node","referenceNode","parent","nextSibling","checkChanges","newTotalNumberFields","changes","newSort","oldSort","newName","toLowerCase","oldName","newColor","oldColor","confirmSubmit","fixStatuses","ajax","url","method","dataType","ACTION","onsuccess","window","location","reload","onfailure","correctionColorPicker","event","blockColorPicker","left","pageX","paintElement","objColorPicker","pWnd","fields","span","phasePanel","result","ICON_CLASS","BLOCK_CLASS","hiddenInputColor","isReady","renderAmCharts","ready","charts","getDataForAmCharts","chart","makeChart","theme","titleField","valueField","dataProvider","colors","labelsEnabled","marginRight","marginLeft","labelPosition","funnelAlpha","startX","neckWidth","startAlpha","depth3D","angle","outlineAlpha","outlineColor","outlineThickness","neckHeight","balloonText","export","enabled","chartId","success","error","getParameterByName","regex","RegExp","results","exec","search","decodeURIComponent","addCustomEvent","state","removeClass","addClass","onCustomEvent","GetWindowInnerSize","GetWindowScrollPos","pos","scrollBottom","scrollTop","innerHeight","bottom","height","padding","fixFooter","fixButton","userOptions","save"],"mappings":"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"}