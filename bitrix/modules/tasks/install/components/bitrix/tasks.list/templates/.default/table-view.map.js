{"version":3,"file":"table-view.min.js","sources":["table-view.js"],"names":["tasksListTemplateDefaultTableViewInit","BX","bind","e","window","event","groupsPopup","show","PreventDefault","tasksListNS","isReady","table","columnsContextId","registeredTimerNodes","columnsMetaData","initialColumnsMetaDataHash","groupActionCheckboxes","lastCheckboxIndex","objResizer","arFilter","groupActionCheckboxesName","groupActionSelectAll","userSelector","groupSelector","curUserName","menu","approveTask","taskId","row","SetCSSStatus","cells","getElementsByTagName","title","findChild","tagName","style","innerHTML","link","className","onclick","message","SetServerStatus","disapproveTask","renderTimerItem","timeSpentInLogs","timeEstimate","isRunning","taskTimersTotalValue","canStartTimeTracking","timeSpent","create","props","id","events","click","hasClass","TasksTimerManager","stop","start","children","text","renderTimerTimes","str","bShowSeconds","renderSecondsToHHMMSS","totalSeconds","pad","hours","Math","floor","minutes","seconds","result","substring","length","redrawTimerNode","taskTimerBlock","newTaskTimerBlock","parentNode","replaceChild","a","this","removeCustomEvent","insertBefore","__getTimerChangeCallback","addCustomEvent","removeTimerNode","removeChild","selfTaskId","state","params","switchStateTo","innerTimerBlock","action","data","TASK","TIME_SPENT_IN_LOGS","TIME_ESTIMATE","TIMER","RUN_TIME","timerData","TASK_ID","TIMER_STARTED_AT","removeClass","addClass","onDeadlineChangeClick","node","curDeadline","calendar","value","form","bTime","currentTime","round","Date","getTimezoneOffset","bHideTimebar","callback_after","bTimeIn","CJSTask","batchOperations","operation","taskData","ID","DEADLINE","ValueToString","callbackOnSuccess","reply","renderDeadline","deadlineIn","allowEdit","isAmPmMode","deadline","substr","split","date","time","join","deadlineHtml","onColumnResize","columnId","columnWidth","sessid","mode","columnContextId","ajax","method","dataType","url","tasksListAjaxUrl","async","processData","onsuccess","onColumnMoved","prevCellIndex","newCellIndex","i","iMin","iMax","tmp1","tmp2","movedColumnId","movedColumnHiddenInput","movedAfterColumnId","leftColumnHiddenInput","rows","parseInt","min","max","width","offsetWidth","appendChild","self","setTimeout","reinit","onColumnAddRemove","selectedColumnsIds","JSON","stringify","isSelected","push","selectedColumns","location","reload","onResetToDefaultColumns","isAnyCheckboxChecked","cnt","isChecked","checked","onCheckboxChanged","checkbox","bSkipRefresh","refreshCheckboxes","onCheckboxClick","clickedCheckboxIndex","shiftKey","getColumnsOrder","hiddenInput","columnsOrder","type","isDomNode","getColumnIndex","columnName","columns","onPopupTaskChanged","task","newDataPack","legacyHtmlTaskItem","currentRow","newRow","tmpRow","currentParentId","currentParent","currentProjectId","newDepth","currentDepth","taskChildCountSpan","tasksMenuPopup","menuItems","quickInfoData","Depth","findPreviousSibling","replace","tempDiv","document","createElement","firstChild","scriptRaw","browser","IsIE","documentMode","script","html","parentTaskId","remove","projectId","beforeRow","__FindBeforeRow","projectName","onPopupTaskAdded","detailTaksID","onCustomEvent","callbackOnAfterAdd","display","checkboxes","actionCheckboxes","hasOwnProperty","name","onActionSelect","selector","selectors","options","selectedIndex","disabled","Tasks","lwPopup","__initSelectors","requestedObject","selectedUsersIds","anchorId","bindClickTo","userInputId","multiple","GROUP_ID_FOR_SITE","callbackOnSelect","arUser","onLoadedViaAjax","bindElement","arGroups","jsObjectName","wait","delay","timeout","onGroupActionDaysChanged","selectedType","getMessagePlural","toUpperCase","submitGroupAction","object","subaction","additionalFields","elements_ids","len","arFilterTmp","parse","formAttributes","k","append","body","submit","onReady","groupActionSelectAllOnPage","minColumnWidth","lastColumnMinWidth","menuElement","knownColumnsIds","ii","curColumnData","knownColumnsCnt","selectedColumnsCnt","columnsMenuItems","getMenuItem","layout","item","tasks_TableColumnResize","lastColumnAsElastic","elasticColumnMinWidth","callbackOnStopResize","columnHiddenInput","columnIndex","tasks_TableColumnMove","delimiter","PopupMenu","closeByEsc","zIndex","autoHide","bindOptions","position","onPopupClose","popupWindow","confirm","__CreateProjectRow","groupObj","addUrl","groupUrl","colSpanCount","attrs","data-project-id","colSpan","util","htmlspecialchars","indexOf","parentId","parentRow","findNextSibling","span","folding","titleHolder","loadedTasks","count","projectRow","nextSibling","AddToFavorite","parameters","add","datum","DeleteFavorite","rowDelete","TASKS_table_view_onDeleteClick_onSuccess","DeleteTask","__DeleteTaskRow","GetRowIdByDomNode","toString","GetChildrenCountByRowId","SetChildrenCount","counter","querySelector","depth","nextDepth","directChild","prevRow","nextRow","modeDeleteSubtree","toRemove","parentChildCount","querySelectorAll","taskRowsCount","bMustBeRemoved","rowToBeRemoved","ToggleProjectTasks","projectID","tag","bFoldingClosed","userOptions","save","toggleClass","SortTable","onGroupSelect","groups","adjust","deleteIcon","cleanNode","deselect","newTaskGroup","newTaskGroupObj","onPopupTaskDeleted","__renderMark","directorId","currentUser","isSubordinate","responsibleId","mark","isInReport","__renderPriority","priority","__renderDeadline","bCanEditDeadline","canEditDealine","dateDeadline","__renderFlag","status","CloseTask","titleColIndex","StartTask","AcceptTask","PauseTask","RenewTask","DeferTask"],"mappings":"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"}