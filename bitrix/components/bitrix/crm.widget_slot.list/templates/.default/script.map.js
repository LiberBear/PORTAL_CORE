{"version":3,"file":"script.min.js","sources":["script.js"],"names":["BX","CrmWidgetSlotList","this","_id","_settings","_data","_limit","_prefix","_table","_serviceUrl","_nodes","_nodeBuilderPanels","prototype","initialize","id","settings","type","isNotEmptyString","util","getRandomString","getSetting","rows","i","length","row","className","nodeId","getAttribute","node","CrmWidgetSlotNode","create","list","prefix","helpUrl","tolltip","enableBitrix24Helper","data","getNodeData","builderPanel","createBuilderPanel","layout","_limitSummaryContainer","name","defaultval","hasOwnProperty","getId","getMessage","msg","messages","getOverallLimit","getEntityLimit","datum","getNodeBuilderPanel","getNode","getBusySlotCount","result","insertRow","index","removeRow","deleteRow","rowIndex","saveNodeBindings","getData","bindings","isArray","ajax","url","method","dataType","ACTION","PARAMS","ID","BINDINGS","onsuccess","delegate","_onRequestSuccess","onfailure","_onRequestFailure","isActive","setActive","refreshLimitSummary","innerHTML","htmlspecialchars","replace","expandAll","expand","foldAll","fold","processNodeItemAdd","item","processNodeItemRemove","self","_items","_expanded","_list","_headRow","_itemLegendRow","_buttonRow","_limitSummaryWrapper","_helpLink","_helpLinkClickHandler","onHelpLinkClick","_folder","_folderClickHandler","onFolderClick","_addBtn","_addBtnClickHandler","onAddButtonClick","_fixedFieldToggle","_fixedFieldToggleClickHandler","onFixedFieldToggleClick","_isFixedFieldVisible","resolveElementId","bind","slots","itemPrefix","getItemPrefix","itemRow","slot","isBoolean","persistent","binding","SLOT","j","style","display","push","CrmWidgetSlotItem","visible","fixed","k","getItem","getList","suffix","toLowerCase","getFreeSlotNames","slotName","isBound","isFixed","isExpanded","setVisible","addClass","removeClass","getFieldInfos","getFieldTitle","infos","info","getSlotTitle","slotInfos","slotInfo","cell","insertCell","props","appendChild","summaryWrapper","text","clear","fixedFieldWrapper","href","title","children","colSpan","buttonWrapper","addItem","removeItem","splice","containerId","isPlainObject","CrmLongRunningProcessPanel","active","message","manager","dialogTitle","dialogSummary","actionName","serviceUrl","findBindingIndex","saveBinding","removeBinding","processItemEditStart","toggleMode","processItemEditCancel","isPersistent","cleanLayout","getRow","processItemEditAccept","editData","getEditData","isChanged","CrmWidgetSlotBinding","equals","fieldName","getFieldName","alert","saveData","toArray","setPersistent","processItemRemoval","setFieldName","clearOptions","e","PreventDefault","slotNames","mode","CrmWidgetSlotItemMode","edit","Helper","show","undifined","view","_node","_row","_editButton","_editButtonClickHandler","onEditButtonClick","_deleteButton","_deleteButtonClickHandler","onDeleteButtonClick","_resetButton","_resetButtonClickHandler","onResetButtonClick","_saveButton","_saveButtonClickHandler","onSaveButtonClick","_cancelButton","_cancelButtonClickHandler","onCancelButtonClick","_fieldNameSelector","_addProductSumChBx","_mode","_visible","_persistent","_fixed","_hasLayout","getMode","hasLayout","clone","value","setOption","checked","isVisible","wrapper","captionWrapper","attrs","height","setupSelectOptions","prepareFieldSelectorOptions","fieldTitle","optionWrapper","addProductSumChBxId","for","getOption","html","createButton","unbind","cleanNode","fields","notSelectedText","options","field","select","remove","setting","option","Option","browser","IsIE","add","button","_slotName","_fieldName","_options","getSlotName","setSlotName","FIELD","OPTIONS","a","b"],"mappings":"AAAA,SAAUA,IAAoB,oBAAM,YACpC,CACCA,GAAGC,kBAAoB,WAEtBC,KAAKC,IAAM,EACXD,MAAKE,YACLF,MAAKG,MAAQ,IACbH,MAAKI,OAAS,IACdJ,MAAKK,QAAU,EACfL,MAAKM,OAAS,IACdN,MAAKO,YAAc,EACnBP,MAAKQ,SACLR,MAAKS,sBAENX,IAAGC,kBAAkBW,WAEpBC,WAAY,SAASC,EAAIC,GAExBb,KAAKC,IAAMH,GAAGgB,KAAKC,iBAAiBH,GAAMA,EAAKd,GAAGkB,KAAKC,gBAAgB,EACvEjB,MAAKE,UAAYW,EAAWA,IAE5Bb,MAAKK,QAAUL,KAAKkB,WAAW,SAAU,GACzC,IAAGlB,KAAKK,UAAY,GACpB,CACCL,KAAKK,QAAUL,KAAKC,IAGrBD,KAAKO,YAAcP,KAAKkB,WAAW,aAAc,GACjD,IAAGlB,KAAKO,cAAgB,GACxB,CACC,KAAM,0DAGPP,KAAKM,OAASR,GAAGE,KAAKkB,WAAW,WACjC,KAAIlB,KAAKM,OACT,CACC,KAAM,2CAGPN,KAAKG,MAAQH,KAAKkB,WAAW,UAC7BlB,MAAKI,OAASJ,KAAKkB,WAAW,WAE9B,IAAIC,GAAOnB,KAAKM,OAAOa,IACvB,KAAI,GAAIC,GAAI,EAAGA,EAAID,EAAKE,OAAQD,IAChC,CACC,GAAIE,GAAMH,EAAKC,EACf,IAAGE,EAAIC,YAAc,eACrB,CACC,SAGD,GAAIC,GAASF,EAAIG,aAAa,eAC9B,IAAG3B,GAAGgB,KAAKC,iBAAiBS,GAC5B,CACC,GAAIE,GAAO5B,GAAG6B,kBAAkBC,OAC/BJ,GAECK,KAAM7B,KACN8B,OAAQ9B,KAAKK,QACbiB,IAAKA,EACLS,QAAS/B,KAAKkB,WAAW,cAAe,IACxCc,QAAShC,KAAKkB,WAAW,cAAe,IACxCe,qBAAsBjC,KAAKkB,WAAW,uBAAwB,OAC9DgB,KAAMlC,KAAKmC,YAAYX,IAGzBxB,MAAKQ,OAAOgB,GAAUE,CACtB,IAAIU,GAAeV,EAAKW,mBAAmBrC,KAAKkB,WAAW,sBAC3D,IAAGkB,EACH,CACCA,EAAaE,QACbtC,MAAKS,mBAAmBe,GAAUY,IAKrCpC,KAAKuC,uBAAyBzC,GAAGE,KAAKkB,WAAW,6BAElDA,WAAY,SAAUsB,EAAMC,GAE3B,MAAOzC,MAAKE,UAAUwC,eAAeF,GAAQxC,KAAKE,UAAUsC,GAAQC,GAErEE,MAAO,WAEN,MAAO3C,MAAKC,KAEb2C,WAAY,SAASJ,GAEpB,GAAIK,GAAM/C,GAAGC,kBAAkB+C,QAC/B,OAAOD,GAAIH,eAAeF,GAAQK,EAAIL,GAAQA,GAE/CO,gBAAiB,WAEhB,MAAO/C,MAAKI,OAAOsC,eAAe,WAAa1C,KAAKI,OAAO,WAAa,GAEzE4C,eAAgB,WAEf,MAAOhD,MAAKI,OAAOsC,eAAe,UAAY1C,KAAKI,OAAO,UAAY,GAEvE+B,YAAa,SAASX,GAErB,IAAI,GAAIJ,GAAI,EAAGA,EAAIpB,KAAKG,MAAMkB,OAAQD,IACtC,CACC,GAAI6B,GAAQjD,KAAKG,MAAMiB,EACvB,IAAG6B,EAAM,QAAUzB,EACnB,CACC,MAAOyB,IAGT,MAAO,OAERC,oBAAqB,SAAS1B,GAE7B,MAAOxB,MAAKS,mBAAmBiC,eAAelB,GAAUxB,KAAKS,mBAAmBe,GAAU,MAE3F2B,QAAS,SAAS3B,GAEjB,MAAOxB,MAAKQ,OAAOkC,eAAelB,GAAUxB,KAAKQ,OAAOgB,GAAU,MAEnE4B,iBAAkB,WAEjB,GAAIC,GAAS,CACb,KAAI,GAAIzC,KAAMZ,MAAKQ,OACnB,CACC,GAAGR,KAAKQ,OAAOkC,eAAe9B,GAC9B,CACCyC,GAAUrD,KAAKQ,OAAOI,GAAIwC,oBAG5B,MAAOC,IAERC,UAAW,SAASC,GAEnB,MAAOvD,MAAKM,OAAOgD,UAAUC,IAE9BC,UAAW,SAASlC,GAEnBtB,KAAKM,OAAOmD,UAAUnC,EAAIoC,WAE3BpB,OAAQ,WAEP,IAAI,GAAI1B,KAAMZ,MAAKQ,OACnB,CACC,GAAGR,KAAKQ,OAAOkC,eAAe9B,GAC9B,CACCZ,KAAKQ,OAAOI,GAAI0B,YAInBqB,iBAAkB,SAASjC,GAG1B,GAAIF,GAASE,EAAKiB,OAClB,IAAIT,GAAOR,EAAKkC,SAChB,IAAIC,GAAW/D,GAAGgB,KAAKgD,QAAQ5B,EAAK,kBAAoBA,EAAK,mBAC7DpC,IAAGiE,MAEDC,IAAKhE,KAAKO,YACV0D,OAAQ,OACRC,SAAU,OACVhC,MAAQiC,OAAW,gBAAiBC,QAAYC,GAAM7C,EAAQ8C,SAAYT,IAC1EU,UAAWzE,GAAG0E,SAASxE,KAAKyE,kBAAmBzE,MAC/C0E,UAAW5E,GAAG0E,SAASxE,KAAK2E,kBAAmB3E,OAIjD,IAAIoC,GAAepC,KAAKkD,oBAAoB1B,EAC5C,IAAGY,IAAiBA,EAAawC,WACjC,CACCxC,EAAayC,UAAU,QAGzBC,oBAAqB,WAEpB,GAAG9E,KAAKuC,uBACR,CACCvC,KAAKuC,uBAAuBwC,UAAYjF,GAAGkB,KAAKgE,iBAC/ChF,KAAK4C,WAAW,SACdqC,QAAQ,YAAajF,KAAKoD,oBAC1B6B,QAAQ,cAAejF,KAAK+C,sBAIjCmC,UAAW,WAEV,IAAI,GAAItE,KAAMZ,MAAKQ,OACnB,CACC,GAAGR,KAAKQ,OAAOkC,eAAe9B,GAC9B,CACCZ,KAAKQ,OAAOI,GAAIuE,YAInBC,QAAS,WAER,IAAI,GAAIxE,KAAMZ,MAAKQ,OACnB,CACC,GAAGR,KAAKQ,OAAOkC,eAAe9B,GAC9B,CACCZ,KAAKQ,OAAOI,GAAIyE,UAInBC,mBAAoB,SAAS5D,EAAM6D,GAElCvF,KAAK8E,uBAENU,sBAAuB,SAAS9D,EAAM6D,GAErCvF,KAAK8E,uBAGP,UAAUhF,IAAGC,kBAA0B,WAAM,YAC7C,CACCD,GAAGC,kBAAkB+C,YAEtBhD,GAAGC,kBAAkB6B,OAAS,SAAShB,EAAIC,GAE1C,GAAI4E,GAAO,GAAI3F,IAAGC,iBAClB0F,GAAK9E,WAAWC,EAAIC,EACpB,OAAO4E,IAIT,SAAU3F,IAAoB,oBAAM,YACpC,CACCA,GAAG6B,kBAAoB,WAEtB3B,KAAKC,IAAM,EACXD,MAAKE,YACLF,MAAKG,MAAQ,IACbH,MAAK0F,SACL1F,MAAK2F,UAAY,KAEjB3F,MAAK4F,MAAQ,IACb5F,MAAKK,QAAU,EACfL,MAAK6F,SAAW,IAChB7F,MAAK8F,eAAiB,IACtB9F,MAAK+F,WAAa,IAClB/F,MAAKgG,qBAAuB,IAE5BhG,MAAKiG,UAAY,IACjBjG,MAAKkG,sBAAwBpG,GAAG0E,SAASxE,KAAKmG,gBAAiBnG,KAE/DA,MAAKoG,QAAU,IACfpG,MAAKqG,oBAAsBvG,GAAG0E,SAASxE,KAAKsG,cAAetG,KAE3DA,MAAKuG,QAAU,IACfvG,MAAKwG,oBAAsB1G,GAAG0E,SAASxE,KAAKyG,iBAAkBzG,KAE9DA,MAAK0G,kBAAoB,IACzB1G,MAAK2G,8BAAgC7G,GAAG0E,SAASxE,KAAK4G,wBAAyB5G,KAE/EA,MAAK6G,qBAAuB,MAE7B/G,IAAG6B,kBAAkBjB,WAEpBC,WAAY,SAASC,EAAIC,GAExBb,KAAKC,IAAMW,CACXZ,MAAKE,UAAYW,EAAWA,IAE5Bb,MAAKG,MAAQH,KAAKkB,WAAW,UAE7BlB,MAAK4F,MAAQ5F,KAAKkB,WAAW,OAC7B,KAAIlB,KAAK4F,MACT,CACC,KAAM,oDAGP5F,KAAK6F,SAAW7F,KAAKkB,WAAW,MAChC,KAAIlB,KAAK6F,SACT,CACC,KAAM,mDAGP7F,KAAK2F,UAAY3F,KAAKkB,WAAW,WAAY,MAC7ClB,MAAKK,QAAUL,KAAKkB,WAAW,SAAU,GAEzClB,MAAKoG,QAAUtG,GAAGE,KAAK8G,iBAAiB,UACxC,IAAG9G,KAAKoG,QACR,CACCtG,GAAGiH,KAAK/G,KAAKoG,QAAS,QAASpG,KAAKqG,qBAGrC,IAAIvG,GAAGgB,KAAKgD,QAAQ9D,KAAKG,MAAM,kBAC/B,CACCH,KAAKG,MAAM,oBAEZ,GAAI0D,GAAW7D,KAAKG,MAAM,gBAE1B,KAAIL,GAAGgB,KAAKgD,QAAQ9D,KAAKG,MAAM,UAC/B,CACCH,KAAKG,MAAM,YAEZ,GAAI6G,GAAQhH,KAAKG,MAAM,QAEvB,IAAI8G,GAAajH,KAAKkH,eACtB,IAAIC,GAAU,IACd,IAAI5D,GAAQvD,KAAK6F,SAASnC,QAC1B,IAAIlB,GAAO,EACX,KAAI,GAAIpB,GAAI,EAAGA,EAAI4F,EAAM3F,OAAQD,IACjC,CACC,GAAIgG,GAAOJ,EAAM5F,EACjB,MAAKtB,GAAGgB,KAAKuG,UAAUD,EAAK,cAAgBA,EAAK,aACjD,CACC,SAGD5E,EAAO1C,GAAGgB,KAAKC,iBAAiBqG,EAAK,SAAWA,EAAK,QAAU,EAC/D,IAAG5E,IAAS,GACZ,CACC,SAGD,GAAI8E,GAAa,KACjB,IAAIC,IAAYC,KAAQhF,EACxB,KAAI,GAAIiF,GAAI,EAAGA,EAAI5D,EAASxC,OAAQoG,IACpC,CACC,GAAGjF,IAASqB,EAAS4D,GAAG,QACxB,CACC,SAGDH,EAAa,IACbC,GAAU1D,EAAS4D,EAEnB,OAGDN,EAAUnH,KAAK4F,MAAMtC,YAAYC,EACjC,KAAIvD,KAAK2F,UACT,CACCwB,EAAQO,MAAMC,QAAU,OAGzB3H,KAAK0F,OAAOkC,KACX9H,GAAG+H,kBAAkBjG,OACpBY,GAECd,KAAM1B,KACN8B,OAAQmF,EACR3F,IAAK6F,EACLW,QAAS9H,KAAK6G,qBACdS,WAAYA,EACZS,MAAO,KACP7F,KAAMqF,KAMVvH,KAAK8F,eAAiB9F,KAAK4F,MAAMtC,YAAYC,EAE7C,KAAI,GAAIyE,GAAI,EAAGA,EAAInE,EAASxC,OAAQ2G,IACpC,CACCxF,EAAO1C,GAAGgB,KAAKC,iBAAiB8C,EAASmE,GAAG,SAAWnE,EAASmE,GAAG,QAAU,EAC7E,IAAGxF,IAAS,IAAMxC,KAAKiI,QAAQzF,KAAU,KACzC,CACC,SAGD2E,EAAUnH,KAAK4F,MAAMtC,YAAYC,EACjC,KAAIvD,KAAK2F,UACT,CACCwB,EAAQO,MAAMC,QAAU,OAGzB3H,KAAK0F,OAAOkC,KACX9H,GAAG+H,kBAAkBjG,OACpBY,GAECd,KAAM1B,KACN8B,OAAQmF,EACR3F,IAAK6F,EACLW,QAAS9H,KAAK2F,UACd2B,WAAY,KACZS,MAAO,MACP7F,KAAM2B,EAASmE,MAMnBhI,KAAK+F,WAAa/F,KAAK4F,MAAMtC,YAAYC,EACzC,KAAIvD,KAAK2F,UACT,CACC3F,KAAK+F,WAAW2B,MAAMC,QAAU,SAGlCzG,WAAY,SAAUsB,EAAMC,GAE3B,MAAOzC,MAAKE,UAAUwC,eAAeF,GAAQxC,KAAKE,UAAUsC,GAAQC,GAErEE,MAAO,WAEN,MAAO3C,MAAKC,KAEbiI,QAAS,WAER,MAAOlI,MAAK4F,OAEbsB,cAAe,WAEd,GAAIiB,GAASnI,KAAKC,IAAImI,aACtB,OAAOpI,MAAKK,UAAY,GAAML,KAAKK,QAAU,IAAM8H,EAAUA,GAE9DE,iBAAkB,WAEjB,GAAIhF,KACJ,IAAIQ,GAAW/D,GAAGgB,KAAKgD,QAAQ9D,KAAKG,MAAM,kBAAoBH,KAAKG,MAAM,mBACzE,IAAI6G,GAAQlH,GAAGgB,KAAKgD,QAAQ9D,KAAKG,MAAM,UAAYH,KAAKG,MAAM,WAC9D,KAAI,GAAIiB,GAAI,EAAGA,EAAI4F,EAAM3F,OAAQD,IACjC,CACC,GAAIgG,GAAOJ,EAAM5F,EACjB,IAAGtB,GAAGgB,KAAKuG,UAAUD,EAAK,cAAgBA,EAAK,YAC/C,CACC,SAGD,GAAIkB,GAAWlB,EAAK,OACpB,IAAImB,GAAU,KACd,KAAI,GAAId,GAAI,EAAGA,EAAI5D,EAASxC,OAAQoG,IACpC,CACC,GAAIF,GAAU1D,EAAS4D,EACvB,IAAG3H,GAAGgB,KAAKC,iBAAiBwG,EAAQ,UAAYA,EAAQ,UAAYe,EACpE,CACCC,EAAU,IACV,QAIF,IAAIA,EACJ,CACClF,EAAOuE,KAAKU,IAGd,MAAOjF,IAERD,iBAAkB,WAEjB,GAAIC,GAAS,CACb,KAAI,GAAIjC,GAAI,EAAGA,EAAIpB,KAAK0F,OAAOrE,OAAQD,IACvC,CACC,IAAIpB,KAAK0F,OAAOtE,GAAGoH,UACnB,CACCnF,KAGF,MAAOA,IAERT,WAAY,SAASJ,GAEpB,GAAIK,GAAM/C,GAAG6B,kBAAkBmB,QAC/B,OAAOD,GAAIH,eAAeF,GAAQK,EAAIL,GAAQA,GAE/CiG,WAAY,WAEX,MAAOzI,MAAK2F,WAEbR,OAAQ,WAEP,GAAGnF,KAAK2F,UACR,CACC,OAGD,IAAI,GAAIvE,GAAI,EAAGA,EAAIpB,KAAK0F,OAAOrE,OAAQD,IACvC,CACC,GAAImE,GAAOvF,KAAK0F,OAAOtE,EACvB,KAAImE,EAAKiD,WAAaxI,KAAK6G,qBAC3B,CACCtB,EAAKmD,WAAW,OAIlB1I,KAAK+F,WAAW2B,MAAMC,QAAU,EAChC3H,MAAK8F,eAAe4B,MAAMC,QAAU,EAEpC7H,IAAG6I,SAAS3I,KAAK6F,SAAU,iBAC3B/F,IAAG8I,YAAY5I,KAAKoG,QAAS,OAC7BtG,IAAG6I,SAAS3I,KAAKoG,QAAS,QAE1BpG,MAAK2F,UAAY,MAGlBN,KAAM,WAEL,IAAIrF,KAAK2F,UACT,CACC,OAGD,IAAI,GAAIvE,GAAI,EAAGA,EAAIpB,KAAK0F,OAAOrE,OAAQD,IACvC,CACCpB,KAAK0F,OAAOtE,GAAGsH,WAAW,OAG3B1I,KAAK+F,WAAW2B,MAAMC,QAAU,MAChC3H,MAAK8F,eAAe4B,MAAMC,QAAU,MAEpC7H,IAAG8I,YAAY5I,KAAK6F,SAAU,iBAC9B/F,IAAG8I,YAAY5I,KAAKoG,QAAS,QAC7BtG,IAAG6I,SAAS3I,KAAKoG,QAAS,OAE1BpG,MAAK2F,UAAY,OAGlBmB,iBAAkB,SAAStE,GAE1B,GAAI5B,GAAKZ,KAAKC,IAAM,IAAMuC,CAC1B,IAAGxC,KAAKK,UAAY,GACpB,CACCO,EAAKZ,KAAKK,QAAU,IAAMO,EAE3B,MAAOA,IAERiI,cAAe,WAEd,MAAO/I,IAAGgB,KAAKgD,QAAQ9D,KAAKG,MAAM,gBAAkBH,KAAKG,MAAM,mBAEhE2I,cAAe,SAAStG,GAEvB,GAAIuG,GAAQjJ,GAAGgB,KAAKgD,QAAQ9D,KAAKG,MAAM,gBAAkBH,KAAKG,MAAM,iBACpE,KAAI,GAAIiB,GAAI,EAAGA,EAAI2H,EAAM1H,OAAQD,IACjC,CACC,GAAI4H,GAAOD,EAAM3H,EACjB,IAAGtB,GAAGgB,KAAKC,iBAAiBiI,EAAK,UAAYA,EAAK,UAAYxG,EAC9D,CACC,MAAO1C,IAAGgB,KAAKC,iBAAiBiI,EAAK,UAAYA,EAAK,SAAWA,EAAK,SAGxE,MAAOxG,IAERyG,aAAc,SAASzG,GAEtB,GAAI0G,GAAYpJ,GAAGgB,KAAKgD,QAAQ9D,KAAKG,MAAM,UAAYH,KAAKG,MAAM,WAClE,KAAI,GAAIiB,GAAI,EAAGA,EAAI8H,EAAU7H,OAAQD,IACrC,CACC,GAAI+H,GAAWD,EAAU9H,EACzB,IAAGoB,IAAS2G,EAAS,QACrB,CACC,SAGD,MAAQrJ,IAAGgB,KAAKC,iBAAiBoI,EAAS,UAAYA,EAAS,SAAW,GAG3E,MAAO,IAERvF,QAAS,WAER,MAAO5D,MAAKG,OAEbmC,OAAQ,WAEP,GAAI8G,GAAOpJ,KAAK6F,SAASwD,YAAY,EACrCD,GAAK7H,UAAY,SAEjBvB,MAAKoG,QAAUtG,GAAG8B,OAAO,QAAU0H,OAAS/H,UAAW,6BACvD6H,GAAKG,YAAYvJ,KAAKoG,QACtBtG,IAAGiH,KAAK/G,KAAKoG,QAAS,QAASpG,KAAKqG,oBAEpC+C,GAAOpJ,KAAK6F,SAASwD,YAAY,EAEjC,IAAIG,GAAiB1J,GAAG8B,OAAO,OAAS0H,OAAS/H,UAAW,+BAC5D6H,GAAKG,YAAYC,EACjBA,GAAeD,YACdzJ,GAAG8B,OAAO,QAER6H,KAAM3J,GAAGgB,KAAKC,iBAAiBf,KAAKG,MAAM,UAAYH,KAAKG,MAAM,SAAWH,KAAKC,MAIpFD,MAAKgG,qBAAuBlG,GAAG8B,OAAO,OAAS0H,OAAS/H,UAAW,6BACnEiI,GAAeD,YAAYvJ,KAAKgG,qBAChCwD,GAAeD,YAAYzJ,GAAG8B,OAAO,OAAS8F,OAASgC,MAAS,UAChE1J,MAAK8E,qBAEL,IAAI6E,GAAoB7J,GAAG8B,OAAO,OAAS0H,OAAS/H,UAAW,2BAC/D6H,GAAKG,YAAYI,EAEjB3J,MAAK0G,kBAAoB5G,GAAG8B,OAAO,KAEjC0H,OAASM,KAAM,KACfH,KAAMzJ,KAAK4C,WAAW,aAGxB9C,IAAGiH,KAAK/G,KAAK0G,kBAAmB,QAAS1G,KAAK2G,8BAE9C3G,MAAKiG,UAAYnG,GAAG8B,OAAO,QAEzB0H,OAEE/H,UAAW,eACXsI,MAAO7J,KAAKkB,WAAW,UAAW,MAItCpB,IAAGiH,KAAK/G,KAAKiG,UAAW,QAASjG,KAAKkG,sBAEtCyD,GAAkBJ,YACjBzJ,GAAG8B,OAAO,OAER0H,OAAS/H,UAAW,wDACpBuI,UAAY9J,KAAK0G,kBAAmB1G,KAAKiG,aAK5CjG,MAAK6F,SAASwD,YAAY,EAC1BrJ,MAAK6F,SAASwD,YAAY,EAE1BrJ,MAAK+F,WAAWsD,YAAY,EAC5BD,GAAOpJ,KAAK+F,WAAWsD,YAAY,EACnCD,GAAKW,QAAU,CAEf,IAAIC,GAAgBlK,GAAG8B,OAAO,OAAS0H,OAAS/H,UAAW,kCAC3D6H,GAAKG,YACJzJ,GAAG8B,OAAO,OAER0H,OAAS/H,UAAW,8BACpBuI,UAAYE,KAKfhK,MAAKuG,QAAUzG,GAAG8B,OAAO,KAAO0H,OAASM,KAAM,KAAOH,KAAMzJ,KAAK4C,WAAW,QAC5EoH,GAAcT,YAAYvJ,KAAKuG,QAC/BzG,IAAGiH,KAAK/G,KAAKuG,QAAS,QAASvG,KAAKwG,oBAEpCxG,MAAK8F,eAAeuD,YAAY,EAChCD,GAAOpJ,KAAK8F,eAAeuD,YAAY,EACvCD,GAAKW,QAAU,CACfX,GAAKG,YACJzJ,GAAG8B,OAAO,OAER0H,OAAS/H,UAAW,8BACpBuI,UAEEhK,GAAG8B,OAAO,OAER0H,OAAS/H,UAAW,iCACpBuI,UAEChK,GAAG8B,OAAO,QAER0H,OAAS/H,UAAW,oCACpBkI,KAAMzJ,KAAK4C,WAAW,sBAWhC,KAAI,GAAIxB,GAAI,EAAGA,EAAIpB,KAAK0F,OAAOrE,OAAQD,IACvC,CACCpB,KAAK0F,OAAOtE,GAAGkB,WAGjB2H,QAAS,SAAS1E,GAEjBvF,KAAK0F,OAAOkC,KAAKrC,EACjBvF,MAAK8E,qBACL9E,MAAK4F,MAAMN,mBAAmBtF,KAAMuF,IAErC0C,QAAS,SAASrH,GAEjB,IAAI,GAAIQ,GAAI,EAAGA,EAAIpB,KAAK0F,OAAOrE,OAAQD,IACvC,CACC,GAAGpB,KAAK0F,OAAOtE,GAAGuB,UAAY/B,EAC9B,CACC,MAAOZ,MAAK0F,OAAOtE,IAGrB,MAAO,OAER8I,WAAY,SAAS3E,GAEpB,IAAI,GAAInE,GAAI,EAAGA,EAAIpB,KAAK0F,OAAOrE,OAAQD,IACvC,CACC,GAAGpB,KAAK0F,OAAOtE,KAAOmE,EACtB,CACC,SAGDvF,KAAK0F,OAAOyE,OAAO/I,EAAG,EACtBpB,MAAK8E,qBACL9E,MAAK4F,MAAMJ,sBAAsBxF,KAAMuF,EACvC,UAGFT,oBAAqB,WAEpB,GAAG9E,KAAKgG,qBACR,CACChG,KAAKgG,qBAAqBjB,UAAYjF,GAAGkB,KAAKgE,iBAC7ChF,KAAK4C,WAAW,SACdqC,QAAQ,YAAajF,KAAKoD,oBAC1B6B,QAAQ,cAAejF,KAAK4F,MAAM5C,qBAIvCX,mBAAoB,SAAS+H,GAE5B,GAAIlI,GAAOpC,GAAGgB,KAAKuJ,cAAcrK,KAAKG,MAAM,YAAcH,KAAKG,MAAM,aACrE,IAAIU,GAAWf,GAAGgB,KAAKuJ,cAAcnI,EAAK,aAAeA,EAAK,cAC9D,OACCpC,IAAGwK,2BAA2B1I,OAC7B5B,KAAKC,KAEJmK,YAAeA,EACftI,OAAU9B,KAAKkH,gBACfqD,OAAUzK,GAAGgB,KAAKuG,UAAUnF,EAAK,WAAaA,EAAK,UAAY,MAC/DsI,QAAW1K,GAAGgB,KAAKC,iBAAiBmB,EAAK,YAAcA,EAAK,WAAa,GACzEuI,SAEEC,YAAa5K,GAAGgB,KAAKC,iBAAiBF,EAAS,UAAYA,EAAS,SAAW,GAC/E8J,cAAe7K,GAAGgB,KAAKC,iBAAiBF,EAAS,YAAcA,EAAS,WAAa,GACrF+J,WAAY9K,GAAGgB,KAAKC,iBAAiBF,EAAS,WAAaA,EAAS,UAAY,GAChFgK,WAAY/K,GAAGgB,KAAKC,iBAAiBF,EAAS,QAAUA,EAAS,OAAS,OAMhFiK,iBAAkB,SAASxC,GAE1B,GAAGxI,GAAGgB,KAAKgD,QAAQ9D,KAAKG,MAAM,kBAC9B,CACC,IAAI,GAAIiB,GAAI,EAAGA,EAAIpB,KAAKG,MAAM,iBAAiBkB,OAAQD,IACvD,CACC,GAAImG,GAAUvH,KAAKG,MAAM,iBAAiBiB,EAC1C,IAAGtB,GAAGgB,KAAKC,iBAAiBwG,EAAQ,UAAYe,IAAaf,EAAQ,QACrE,CACC,MAAOnG,KAIV,OAAQ,GAET2J,YAAa,SAASxD,GAErB,IAAIzH,GAAGgB,KAAKC,iBAAiBwG,EAAQ,SACrC,CACC,KAAM,4DAGP,IAAIzH,GAAGgB,KAAKC,iBAAiBwG,EAAQ,UACrC,CACC,KAAM,6DAGP,IAAIzH,GAAGgB,KAAKgD,QAAQ9D,KAAKG,MAAM,kBAC/B,CACCH,KAAKG,MAAM,oBAGZ,GAAIoD,GAAQvD,KAAK8K,iBAAiBvD,EAAQ,QAC1C,IAAGhE,GAAS,EACZ,CACCvD,KAAKG,MAAM,iBAAiBoD,GAASgE,MAGtC,CACCvH,KAAKG,MAAM,iBAAiByH,KAAKL,GAGlCvH,KAAK4F,MAAMjC,iBAAiB3D,OAE7BgL,cAAe,SAASzD,GAEvB,IAAIzH,GAAGgB,KAAKC,iBAAiBwG,EAAQ,SACrC,CACC,KAAM,4DAGP,GAAIhE,GAAQvD,KAAK8K,iBAAiBvD,EAAQ,QAC1C,IAAGhE,EAAQ,EACX,CACC,MAAO,OAGRvD,KAAKG,MAAM,iBAAiBgK,OAAO5G,EAAO,EAC1CvD,MAAK4F,MAAMjC,iBAAiB3D,KAC5B,OAAO,OAERiL,qBAAsB,SAAS1F,GAE9B,IAAIvF,KAAK2F,UACT,CACC,OAGDJ,EAAK2F,YACLlL,MAAK+F,WAAW2B,MAAMC,QAAU,QAEjCwD,sBAAuB,SAAS5F,GAE/B,IAAIvF,KAAK2F,UACT,CACC,OAGD,IAAIJ,EAAK6F,iBAAmB7F,EAAKiD,UACjC,CACCjD,EAAK8F,aACLrL,MAAK4F,MAAMpC,UAAU+B,EAAK+F,SAC1BtL,MAAKkK,WAAW3E,OAGjB,CACCA,EAAK2F,aAENlL,KAAK+F,WAAW2B,MAAMC,QAAU,IAEjC4D,sBAAuB,SAAShG,GAE/B,IAAIvF,KAAK2F,UACT,CACC,OAGD,GAAI6F,GAAWjG,EAAKkG,aACpB,IAAIC,IAAa5L,GAAG6L,qBAAqBC,OAAOrG,EAAK3B,UAAW4H,EAChE,IAAGE,IAAcnG,EAAK6F,eACtB,CACC,GAAIS,GAAYL,EAASM,cACzB,IAAGD,IAAc,GACjB,CACCE,MAAM/L,KAAK4C,WAAW,oBACtB,QAGD,IAAI,GAAIxB,GAAI,EAAGA,EAAIpB,KAAK0F,OAAOrE,OAAQD,IACvC,CACC,GAAGpB,KAAK0F,OAAOtE,KAAOmE,GAAQsG,IAAc7L,KAAK0F,OAAOtE,GAAGwC,UAAUkI,eACrE,CACCC,MAAM/L,KAAK4C,WAAW,2BAA2BqC,QAAQ,YAAajF,KAAK8I,cAAc+C,IACzF,SAIF,IAAIH,EACJ,CACC,OAGD1L,KAAK+K,YAAYxF,EAAKyG,WAAWC,UACjC,KAAI1G,EAAK6F,eACT,CACC7F,EAAK2G,cAAc,OAIrB3G,EAAK2F,YACLlL,MAAK+F,WAAW2B,MAAMC,QAAU,IAEjCwE,mBAAoB,SAAS5G,GAE5B,IAAIvF,KAAK2F,UACT,CACC,OAGD3F,KAAKgL,cAAczF,EAAK3B,UAAUqI,UAElC,IAAG1G,EAAKiD,UACR,CACC,GAAItG,GAAOqD,EAAK3B,SAChB1B,GAAKkK,aAAa,GAClBlK,GAAKmK,cACL9G,GAAK8F,aACL9F,GAAKjD,QACL,QAGDiD,EAAK8F,aACLrL,MAAK4F,MAAMpC,UAAU+B,EAAK+F,SAC1BtL,MAAKkK,WAAW3E,IAEjBe,cAAe,SAASgG,GAEvB,GAAGtM,KAAK2F,UACR,CACC3F,KAAKqF,WAGN,CACCrF,KAAKmF,SAGN,MAAOrF,IAAGyM,eAAeD,IAE1B7F,iBAAkB,SAAS6F,GAE1B,GAAI/I,GAAQvD,KAAK+F,WAAWrC,QAC5B,IAAIyD,GAAUnH,KAAK4F,MAAMtC,UAAUC,EACnC,KAAIvD,KAAK2F,UACT,CACCwB,EAAQO,MAAMC,QAAU,OAGzB,GAAI3H,KAAK4F,MAAM5C,iBAAmBhD,KAAKoD,oBAAuB,EAC9D,CACC2I,MAAM/L,KAAK4C,WAAW,2BACtB,QAGD,GAAI4J,GAAYxM,KAAKqI,kBACrB,IAAGmE,EAAUnL,SAAW,EACxB,CACC0K,MAAM/L,KAAK4C,WAAW,oBACtB,QAGD,GAAI0F,GAAWkE,EAAU,EACzB,IAAIjH,GAAOzF,GAAG+H,kBAAkBjG,OAC/B0G,GAEC5G,KAAM1B,KACN8B,OAAQ9B,KAAKkH,gBACb5F,IAAK6F,EACLsF,KAAM3M,GAAG4M,sBAAsBC,KAC/B7E,QAAS9H,KAAK2F,UACd2B,WAAY,MACZpF,MAAQsF,KAAQc,IAGlBtI,MAAKiK,QAAQ1E,EACbA,GAAKjD,QACLtC,MAAK+F,WAAW2B,MAAMC,QAAU,MAEhC,OAAO7H,IAAGyM,eAAeD,IAE1B1F,wBAAyB,SAAS0F,GAEjCtM,KAAK6G,sBAAwB7G,KAAK6G,oBAElC,KAAI7G,KAAK2F,UACT,CACC,IAAI3F,KAAK6G,qBACT,CACC7G,KAAK6G,qBAAuB,KAE7B7G,KAAKmF,QACL,OAAOrF,IAAGyM,eAAeD,GAG1B,IAAI,GAAIlL,GAAI,EAAGA,EAAIpB,KAAK0F,OAAOrE,OAAQD,IACvC,CACC,GAAImE,GAAOvF,KAAK0F,OAAOtE,EACvB,IAAGmE,EAAKiD,UACR,CACCjD,EAAKmD,WAAW1I,KAAK6G,uBAGvB,MAAO/G,IAAGyM,eAAeD,IAE1BnG,gBAAiB,SAASmG,GAEzB,GAAIrK,GAAuBjC,KAAKkB,WAAW,uBAAwB,MACnE,IAAIa,GAAU/B,KAAKkB,WAAW,UAC9B,IAAGe,GAAwBnC,GAAGgB,KAAKC,iBAAiBgB,GACpD,CACCjC,GAAG8M,OAAOC,KAAK9K,KAIlB,UAAUjC,IAAG6B,kBAA0B,WAAM,YAC7C,CACC7B,GAAG6B,kBAAkBmB,YAEtBhD,GAAG6B,kBAAkBC,OAAS,SAAShB,EAAIC,GAE1C,GAAI4E,GAAO,GAAI3F,IAAG6B,iBAClB8D,GAAK9E,WAAWC,EAAIC,EACpB,OAAO4E,IAGT,SAAU3F,IAAwB,wBAAM,YACxC,CACCA,GAAG4M,uBAEFI,UAAW,EACXC,KAAM,EACNJ,KAAM,GAGR,SAAU7M,IAAoB,oBAAM,YACpC,CACCA,GAAG+H,kBAAoB,WAEtB7H,KAAKC,IAAM,EACXD,MAAKE,YACLF,MAAKG,MAAQ,IACbH,MAAKK,QAAU,EACfL,MAAKgN,MAAQ,IACbhN,MAAKiN,KAAO,IAEZjN,MAAK+F,WAAa/F,IAElBA,MAAKkN,YAAc,IACnBlN,MAAKmN,wBAA0BrN,GAAG0E,SAASxE,KAAKoN,kBAAmBpN,KAEnEA,MAAKqN,cAAgB,IACrBrN,MAAKsN,0BAA4BxN,GAAG0E,SAASxE,KAAKuN,oBAAqBvN,KAEvEA,MAAKwN,aAAe,IACpBxN,MAAKyN,yBAA2B3N,GAAG0E,SAASxE,KAAK0N,mBAAoB1N,KAErEA,MAAK2N,YAAc,IACnB3N,MAAK4N,wBAA0B9N,GAAG0E,SAASxE,KAAK6N,kBAAmB7N,KAEnEA,MAAK8N,cAAgB,IACrB9N,MAAK+N,0BAA4BjO,GAAG0E,SAASxE,KAAKgO,oBAAqBhO,KAEvEA,MAAKiO,mBAAqB,IAC1BjO,MAAKkO,mBAAqB,IAE1BlO,MAAKmO,MAAQrO,GAAG4M,sBAAsBI,SACtC9M,MAAKoO,SAAW,KAChBpO,MAAKqO,YAAc,KACnBrO,MAAKsO,OAAS,KAEdtO,MAAKuO,WAAa,MAEnBzO,IAAG+H,kBAAkBnH,WAEpBC,WAAY,SAASC,EAAIC,GAExBb,KAAKC,IAAMW,CACXZ,MAAKE,UAAYW,EAAWA,IAC5Bb,MAAKG,MAAQL,GAAG6L,qBAAqB/J,OAAO5B,KAAKkB,WAAW,WAE5DlB,MAAKgN,MAAQhN,KAAKkB,WAAW,OAC7B,KAAIlB,KAAKgN,MACT,CACC,KAAM,oDAGPhN,KAAKiN,KAAOjN,KAAKkB,WAAW,MAC5B,KAAIlB,KAAKiN,KACT,CACC,KAAM,mDAGPjN,KAAKK,QAAUL,KAAKkB,WAAW,SAAU,GACzClB,MAAKqO,YAAcrO,KAAKkB,WAAW,aAAc,MACjDlB,MAAKsO,OAAStO,KAAKkB,WAAW,QAAS,MAEvC,IAAI4G,GAAU9H,KAAKkB,WAAW,UAAW,KACzC,KAAIpB,GAAGgB,KAAKuG,UAAUS,GACtB,CACC9H,KAAKoO,SAAWpO,KAAKiN,KAAKvF,MAAMC,UAAY,WAG7C,CACC3H,KAAKoO,SAAWtG,CAChB9H,MAAKiN,KAAKvF,MAAMC,QAAUG,EAAU,GAAK,OAG1C9H,KAAKmO,MAAQnO,KAAKkB,WAAW,OAAQpB,GAAG4M,sBAAsBK,OAE/D7L,WAAY,SAAUsB,EAAMC,GAE3B,MAAOzC,MAAKE,UAAUwC,eAAeF,GAAQxC,KAAKE,UAAUsC,GAAQC,GAErEE,MAAO,WAEN,MAAO3C,MAAKC,KAEb2C,WAAY,SAASJ,GAEpB,GAAIK,GAAM/C,GAAG+H,kBAAkB/E,QAC/B,OAAOD,GAAIH,eAAeF,GAAQK,EAAIL,GAAQA,GAE/C8I,OAAQ,WAEP,MAAOtL,MAAKiN,MAEb9J,QAAS,WAER,MAAOnD,MAAKgN,OAEbwB,QAAS,WAER,MAAOxO,MAAKmO,OAEbjD,WAAY,WAEX,GAAIuD,GAAYzO,KAAKuO,UACrB,IAAGE,EACH,CACCzO,KAAKqL,cAGNrL,KAAKmO,MAAQnO,KAAKmO,QAAUrO,GAAG4M,sBAAsBK,KAAOjN,GAAG4M,sBAAsBC,KAAO7M,GAAG4M,sBAAsBK,IAErH,IAAG0B,EACH,CACCzO,KAAKsC,WAGPsB,QAAS,WAER,MAAO5D,MAAKG,OAEbsL,YAAa,WAEZ,GAAIvJ,GAAOlC,KAAKG,MAAMuO,OACtBxM,GAAKkK,aAAapM,KAAKiO,mBAAmBU,MAC1CzM,GAAK0M,UAAU,sBAAuB5O,KAAKkO,mBAAmBW,QAAU,IAAM,IAC9E,OAAO3M,IAER8J,SAAU,WAEThM,KAAKG,MAAMiM,aAAapM,KAAKiO,mBAAmBU,MAChD3O,MAAKG,MAAMyO,UAAU,sBAAuB5O,KAAKkO,mBAAmBW,QAAU,IAAM,IACpF,OAAO7O,MAAKG,OAEb2O,UAAW,WAEV,MAAO9O,MAAKoO,UAEb1F,WAAY,SAASZ,GAEpBA,IAAYA,CACZ,IAAG9H,KAAKoO,WAAatG,EACrB,CACC,OAGD9H,KAAKoO,SAAWtG,CAChB9H,MAAKiN,KAAKvF,MAAMC,QAAUG,EAAU,GAAK,QAE1CsD,aAAc,WAEb,MAAOpL,MAAKqO,aAEbnC,cAAe,SAAS5E,GAEvBtH,KAAKqO,cAAgB/G,GAEtBkB,QAAS,WAER,MAAOxI,MAAKsO,QAEbhM,OAAQ,WAEPxC,GAAG6I,SAAS3I,KAAKiN,KAAM,qBACvB,IAAGjN,KAAKsO,OACR,CACCxO,GAAG6I,SAAS3I,KAAKiN,KAAM,kCAGxBjN,KAAKiN,KAAK5D,YAAY,EACtB,IAAID,GAAOpJ,KAAKiN,KAAK5D,YAAY,EACjC,IAAI0F,GAAUjP,GAAG8B,OAAO,OAAS0H,OAAS/H,UAAW,0BACrD6H,GAAKG,YAAYwF,EAEjB,IAAG/O,KAAKsO,OACR,CACC,GAAIU,GAAiBlP,GAAG8B,OAAO,OAE7B0H,OAAS/H,UAAW,8BACpBuI,UAEChK,GAAG8B,OAAO,OAER0H,OAAS/H,UAAW,iCACpBuI,UAEChK,GAAG8B,OAAO,QAER0H,OAAS/H,UAAW,oCACpBkI,KAAMzJ,KAAKgN,MAAM/D,aAAajJ,KAAKC,YAS3C8O,GAAQxF,YAAYyF,GAGrB,GAAIxF,GAAiB1J,GAAG8B,OAAO,OAAS0H,OAAS/H,UAAW,+BAC5DwN,GAAQxF,YAAYC,EAEpB,IAAIqC,GAAY7L,KAAKG,MAAM2L,cAC3B,IAAG9L,KAAKmO,QAAUrO,GAAG4M,sBAAsBC,KAC3C,CACC3M,KAAKiO,mBAAqBnO,GAAG8B,OAAO,UAElCqN,OAAS1N,UAAW,sCACpBmG,OAASwH,OAAQ,SAInBlP,MAAKmP,mBACJnP,KAAKiO,mBACLjO,KAAKoP,4BACJpP,KAAKgN,MAAMnE,gBACX7I,KAAK4C,WAAW5C,KAAKsO,OAAS,YAAc,gBAG9CtO,MAAKiO,mBAAmBU,MAAQ9C,CAEhCrC,GAAeD,YAAYvJ,KAAKiO,wBAGjC,CACC,GAAIoB,GAAaxD,IAAc,GAC5B7L,KAAKgN,MAAMlE,cAAc+C,GACzB7L,KAAK4C,WAAW5C,KAAKsO,OAAS,YAAc,cAE/C9E,GAAeD,YAAYzJ,GAAG8B,OAAO,QAAU6H,KAAM4F,KAEtD7F,EAAeD,YAAYzJ,GAAG8B,OAAO,OAAS8F,OAASgC,MAAO,UAE9D,IAAG1J,KAAKmO,QAAUrO,GAAG4M,sBAAsBC,KAC3C,CACC,GAAI2C,GAAgBxP,GAAG8B,OAAO,OAAS0H,OAAS/H,UAAW,yDAC3D6H,GAAKG,YACJzJ,GAAG8B,OAAO,OAER0H,OAAS/H,UAAW,8BACpBuI,UAAYwF,KAIf,IAAIC,GAAsBvP,KAAK8G,iBAAiB,eAChD9G,MAAKkO,mBAAqBpO,GAAG8B,OAAO,SAAW0H,OAAS1I,GAAI2O,EAAqBzO,KAAM,aACvFwO,GAAc/F,YAAYvJ,KAAKkO,mBAC/BoB,GAAc/F,YACbzJ,GAAG8B,OAAO,SAAW0H,OAASxI,KAAM,WAAY0O,MAAOD,GAAuB9F,KAAMzJ,KAAK4C,WAAW,mBAGrG5C,MAAKkO,mBAAmBW,QAAU7O,KAAKG,MAAMsP,UAAU,sBAAuB,OAAS,IAGxFzP,KAAKiN,KAAK5D,YAAY,EACtBD,GAAOpJ,KAAKiN,KAAK5D,YAAY,EAC7B,IAAGrJ,KAAKmO,QAAUrO,GAAG4M,sBAAsBK,KAC3C,CACC/M,KAAKkN,YAAcpN,GAAG8B,OAAO,KAAO0H,OAASM,KAAM,KAAOH,KAAMzJ,KAAK4C,WAAW,SAChF9C,IAAGiH,KAAK/G,KAAKkN,YAAa,QAASlN,KAAKmN,wBAExC,IAAGnN,KAAKqO,YACR,CACC,GAAGrO,KAAKsO,OACR,CACCtO,KAAKwN,aAAe1N,GAAG8B,OAAO,KAAO0H,OAAQM,KAAM,KAAMH,KAAMzJ,KAAK4C,WAAW,UAC/E9C,IAAGiH,KAAK/G,KAAKwN,aAAc,QAASxN,KAAKyN,8BAG1C,CACCzN,KAAKqN,cAAgBvN,GAAG8B,OAAO,KAAM0H,OAAQM,KAAM,KAAMH,KAAMzJ,KAAK4C,WAAW,WAC/E9C,IAAGiH,KAAK/G,KAAKqN,cAAe,QAASrN,KAAKsN,4BAI5ClE,EAAKG,YACJzJ,GAAG8B,OAAO,OAER0H,OAAS/H,UAAW,iCACpBuI,UAEC9J,KAAKkN,YACLpN,GAAG8B,OAAO,QAAU8N,KAAM,WACzB1P,KAAKsO,OAAStO,KAAKwN,aAAexN,KAAKqN,kBAO7C,GAAGrN,KAAKmO,QAAUrO,GAAG4M,sBAAsBC,KAC3C,CACC3M,KAAK+F,WAAa/F,KAAKgN,MAAM9E,UAAU5E,UAAUtD,KAAKiN,KAAKvJ,SAAW,EACtE5D,IAAG6I,SAAS3I,KAAK+F,WAAY,uBAC7BjG,IAAG6I,SAAS3I,KAAK+F,WAAY,qBAE7B/F,MAAK+F,WAAWsD,YAAY,EAC5BD,GAAOpJ,KAAK+F,WAAWsD,YAAY,EACnCD,GAAKW,QAAU,CAEf/J,MAAK2N,YAAc3N,KAAK2P,cAEtBpO,UAAW,mDACXkI,KAAMzJ,KAAK4C,WAAW,SAGxB9C,IAAGiH,KAAK/G,KAAK2N,YAAa,QAAS3N,KAAK4N,wBAExC5N,MAAK8N,cAAgB9N,KAAK2P,cAExBpO,UAAW,uBACXkI,KAAMzJ,KAAK4C,WAAW,WAGxB9C,IAAGiH,KAAK/G,KAAK8N,cAAe,QAAS9N,KAAK+N,0BAE1C3E,GAAKG,YACJzJ,GAAG8B,OAAO,QAER0H,OAAS/H,UAAW,8BACpBuI,UAAY9J,KAAK2N,YAAa3N,KAAK8N,kBAMvC9N,KAAKuO,WAAa,MAEnBlD,YAAa,WAEZ,GAAGrL,KAAKmO,QAAUrO,GAAG4M,sBAAsBC,KAC3C,CACC3M,KAAKiO,mBAAqB,IAC1BjO,MAAKkO,mBAAqB,IAE1BpO,IAAG8P,OAAO5P,KAAK2N,YAAa,QAAS3N,KAAK4N,wBAC1C5N,MAAK2N,YAAc,IACnB7N,IAAG8P,OAAO5P,KAAK8N,cAAe,QAAS9N,KAAK+N,0BAC5C/N,MAAK8N,cAAgB,IAErB9N,MAAKgN,MAAM9E,UAAU1E,UAAUxD,KAAK+F,WACpC/F,MAAK+F,WAAa,SAGnB,CACCjG,GAAG8P,OAAO5P,KAAKkN,YAAa,QAASlN,KAAKmN,wBAC1CnN,MAAKkN,YAAc,IAEnB,IAAGlN,KAAKqN,cACR,CACCvN,GAAG8P,OAAO5P,KAAKqN,cAAe,QAASrN,KAAKsN,0BAC5CtN,MAAKqN,cAAgB,KAGtB,GAAGrN,KAAKwN,aACR,CACC1N,GAAG8P,OAAO5P,KAAKwN,aAAc,QAASxN,KAAKyN,yBAC3CzN,MAAKwN,aAAe,MAItB1N,GAAG+P,UAAU7P,KAAKiN,KAAM,MACxBjN,MAAKuO,WAAa,OAEnBa,4BAA6B,SAASU,EAAQC,GAE7C,IAAIjQ,GAAGgB,KAAKC,iBAAiBgP,GAC7B,CACCA,EAAkB/P,KAAK4C,WAAW,eAGnC,GAAIoN,KAAarB,MAAO,GAAIlF,KAAMsG,GAClC,KAAI,GAAI3O,GAAI,EAAGA,EAAI0O,EAAOzO,OAAQD,IAClC,CACC,GAAI6O,GAAQH,EAAO1O,EACnB4O,GAAQpI,MAAO+G,MAAOsB,EAAM,QAASxG,KAAMwG,EAAM,WAElD,MAAOD,IAERb,mBAAoB,SAASe,EAAQrP,GAEpC,MAAOqP,EAAOF,QAAQ3O,OAAS,EAC/B,CACC6O,EAAOC,OAAO,GAGf,IAAI,GAAI/O,GAAI,EAAGA,EAAIP,EAASQ,OAAQD,IACpC,CACC,GAAIgP,GAAUvP,EAASO,EAEvB,IAAIuN,GAAQ7O,GAAGgB,KAAKC,iBAAiBqP,EAAQ,UAAYA,EAAQ,SAAW,EAC5E,IAAI3G,GAAO3J,GAAGgB,KAAKC,iBAAiBqP,EAAQ,SAAWA,EAAQ,QAAUA,EAAQ,QACjF,IAAIC,GAAS,GAAIC,QAAO7G,EAAMkF,EAAO,MAAO,MAC5C,KAAI7O,GAAGyQ,QAAQC,OACf,CACCN,EAAOO,IAAIJ,EAAQ,UAGpB,CACC,IAGCH,EAAOO,IAAIJ,EAAQH,EAAOF,QAAQ,OAEnC,MAAO1D,GAEN4D,EAAOO,IAAIJ,EAAQ,UAKvBvJ,iBAAkB,SAAStE,GAE1B,GAAI5B,GAAKZ,KAAKC,IAAM,IAAMuC,CAC1B,IAAGxC,KAAKK,UAAY,GACpB,CACCO,EAAKZ,KAAKK,QAAU,IAAMO,EAE3B,MAAOA,IAER+O,aAAc,SAAS9O,GAEtB,GAAI6P,GAAS5Q,GAAG8B,OAAO,QAErB0H,OAAS/H,UAAWzB,GAAGgB,KAAKC,iBAAiBF,EAAS,cAAgBA,EAAS,aAAe,KAIhG6P,GAAOnH,YAAYzJ,GAAG8B,OAAO,QAAU0H,OAAS/H,UAAW,+BAC3DmP,GAAOnH,YACNzJ,GAAG8B,OAAO,QAER0H,OAAS/H,UAAW,6BACpBkI,KAAM3J,GAAGgB,KAAKC,iBAAiBF,EAAS,SAAWA,EAAS,QAAU,KAKzE6P,GAAOnH,YAAYzJ,GAAG8B,OAAO,QAAU0H,OAAS/H,UAAW,gCAE3D,OAAOmP,IAERtD,kBAAmB,SAASd,GAE3BtM,KAAKgN,MAAM/B,qBAAqBjL,KAChC,OAAOF,IAAGyM,eAAeD,IAE1BiB,oBAAqB,SAASjB,GAE7BtM,KAAKgN,MAAMb,mBAAmBnM,KAC9B,OAAOF,IAAGyM,eAAeD,IAE1BoB,mBAAoB,SAASpB,GAE5BtM,KAAKgN,MAAMb,mBAAmBnM,KAC9B,OAAOF,IAAGyM,eAAeD,IAE1BuB,kBAAmB,SAASvB,GAE3BtM,KAAKgN,MAAMzB,sBAAsBvL,KACjC,OAAOF,IAAGyM,eAAeD,IAE1B0B,oBAAqB,SAAS1B,GAE7BtM,KAAKgN,MAAM7B,sBAAsBnL,KACjC,OAAOF,IAAGyM,eAAeD,IAG3B,UAAUxM,IAAG+H,kBAA0B,WAAM,YAC7C,CACC/H,GAAG+H,kBAAkB/E,YAEtBhD,GAAG+H,kBAAkBjG,OAAS,SAAShB,EAAIC,GAE1C,GAAI4E,GAAO,GAAI3F,IAAG+H,iBAClBpC,GAAK9E,WAAWC,EAAIC,EACpB,OAAO4E,IAGT,SAAU3F,IAAuB,uBAAM,YACvC,CACCA,GAAG6L,qBAAuB,WAEzB3L,KAAKC,IAAM,EACXD,MAAKE,YACLF,MAAK2Q,UAAY,EACjB3Q,MAAK4Q,WAAa,EAClB5Q,MAAK6Q,YAGN/Q,IAAG6L,qBAAqBjL,WAEvBC,WAAY,SAASE,GAEpBb,KAAKE,UAAYW,EAAWA,IAC5Bb,MAAK2Q,UAAY3Q,KAAKkB,WAAW,OAAQ,GACzClB,MAAK4Q,WAAa5Q,KAAKkB,WAAW,QAAS,GAC3ClB,MAAK6Q,SAAW/Q,GAAG4O,MAAM1O,KAAKkB,WAAW,cAAgB,OAE1DA,WAAY,SAAUsB,EAAMC,GAE3B,MAAOzC,MAAKE,UAAUwC,eAAeF,GAAQxC,KAAKE,UAAUsC,GAAQC,GAErEqO,YAAa,WAEZ,MAAO9Q,MAAK2Q,WAEbI,YAAa,SAASzI,GAErBtI,KAAK2Q,UAAYrI,GAElBwD,aAAc,WAEb,MAAO9L,MAAK4Q,YAEbxE,aAAc,SAASP,GAEtB7L,KAAK4Q,WAAa/E,GAEnB4D,UAAW,SAASjN,EAAMC,GAEzB,MAAOzC,MAAK6Q,SAASnO,eAAeF,GAAQxC,KAAK6Q,SAASrO,GAAQC,GAEnEmM,UAAW,SAASpM,EAAMmM,GAEzB3O,KAAK6Q,SAASrO,GAAQmM,GAEvBtC,aAAc,WAEbrM,KAAK6Q,aAENnC,MAAO,WAEN,MAAO5O,IAAG6L,qBAAqB/J,OAAO5B,KAAKiM,YAE5CA,QAAS,WAER,OAASzE,KAAQxH,KAAK2Q,UAAWK,MAAShR,KAAK4Q,WAAYK,QAAWjR,KAAK6Q,WAI7E/Q,IAAG6L,qBAAqB/J,OAAS,SAASf,GAEzC,GAAI4E,GAAO,GAAI3F,IAAG6L,oBAClBlG,GAAK9E,WAAWE,EAChB,OAAO4E,GAGR3F,IAAG6L,qBAAqBC,OAAS,SAASsF,EAAGC,GAE5C,GAAGD,IAAMC,EACT,CACC,MAAO,MAGR,GAAGD,EAAEP,YAAcQ,EAAER,UACrB,CACC,MAAO,OAGR,GAAGO,EAAEN,aAAeO,EAAEP,WACtB,CACC,MAAO,OAGR,IAAI,GAAI5I,KAAKkJ,GAAEL,SACf,CACC,IAAIK,EAAEL,SAASnO,eAAesF,GAC9B,CACC,SAGD,IAAImJ,EAAEN,SAASnO,eAAesF,IAAMkJ,EAAEL,SAAS7I,KAAOmJ,EAAEN,SAAS7I,GACjE,CACC,MAAO,QAGT,MAAO"}