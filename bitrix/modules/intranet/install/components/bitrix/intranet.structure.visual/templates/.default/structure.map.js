{"version":3,"file":"structure.min.js","sources":["structure.js"],"names":["BX","IntranetVS","struct","sections_stack","registered_blocks","_onresize","windowSize","GetWindowScrollSize","this","style","width","scrollWidth","node","config","Clear","EDIT","jsDD","Reset","mirror","lastDragPos","reloadCallback","proxy","_reloadCallback","ready","Init","get","prototype","setScrollWindow","UNDO","setUndo","data","closeWait","Enable","util","trim","substring","parseJSON","error","alert","opened_sections_ids_stack","length","current_sect","pop","push","section_id","closeNext","innerHTML","f","sect_id","setTimeout","showNext","CONT","CONT_POS","pos","_undoCheckScroll","bind","window","scrollPos","GetWindowScrollPos","nodePos","scrollTop","top","addClass","removeClass","ondragstart","new_mirror","parentNode","removeChild","employeesPopup","close","ondrag","x","y","Undo","undo_id","Disable","showWait","ajax","URL","sessid","bitrix_sessid","action","undo","CloseUndo","cleanNode","unbind","Reload","mode","IntranetVSBlock","params","bEdit","section_level","section_parent","head","employees","hasChildren","disableDrag","disableDragDest","tplParts","employees_index","i","ID","template","emp","proxy_context","__bxdepartment","DDRegister","department_employee_images","firstChild","type","isElementNode","setTooltip","getAttribute","IntranetVSUser","register","nextSibling","department_head","department_edit","delegate","editDepartment","department_delete","deleteDepartment","department_add","addDepartment","department_next_link","hoverEvents","department_employee_count","showEmployees","create","props","className","obEmployee","attrs","title","NAME","data-user","data-dpt","html","PHOTO","PROFILE","POSITION","htmlspecialchars","insertBefore","appendChild","PopupWindow","Math","random","closeByEsc","autoHide","lightShadow","zIndex","content","offsetLeft","angle","show","user_id","user_node","USE_USER_LINK","id","tooltip","e","IntranetStructure","ShowForm","UF_DEPARTMENT_ID","PreventDefault","IBLOCK_SECTION_ID","confirm","message","dpt_id","cb","url_params","section","level","HAS_MULTIPLE_ROOTS","mr","isFunction","_showNextCallback","_showNext","obPos","clone","left","height","replaceChild","document","body","overlay","position","scrollHeight","shadow","padding","obPosSelf","parseInt","right","stick","bottom","lastChild","onclick","bStarted","HIDE_TIMEOUT","clearTimeout","apply","bSkipPop","refreshDestArea","registerDest","onbxdestdragfinish","ddAction","onbxdestdraghover","ddHover","onbxdestdraghout","ddHout","registerObject","onbxdrag","ddDrag","onbxdragstart","ddStart","onbxdragstop","ddFinish","event","__bxemployee","_ddActionEmployee","arguments","_ddActionDepartment","employee_id","orig_department","bxheadareaover","SKIP_CONFIRM","replace","department_name","innerText","textContent","dpt_from","shiftKey","dpt","titleNode","titleNodeTo","dpt_to","el","browser","IsIE","IsIE11","nextLinkPos","headAreaPos","__ie_mouseover_check","__nextlink_hout","__nextlink_hover","__headarea_hover","__headarea_hout","disable","adjust","min","wndSize","offsetWidth","offsetHeight","enable","nextlinkover","BXHOVERTIMER","current_node","__pos_check","IntranetVSSorter","Action","parentSection","dpt_before","beforeId","dpt_after","afterId","dpt_parent","section_block","onbxdragfinish"],"mappings":"CAAC,WAED,GAAIA,GAAGC,WACN,MAID,IAAIC,GAAS,KACZC,KACAC,KACAC,EAAY,WAEX,GAAIC,GAAaN,GAAGO,qBACpBC,MAAKC,MAAMC,MAAQJ,EAAWK,YAAc,KAI9CX,IAAGC,WAAa,SAASW,EAAMC,GAE9B,KAAMX,EACLA,EAAOY,OAERZ,GAASM,IAETA,MAAKI,KAAOA,CACZJ,MAAKK,OAASA,CAEd,MAAML,KAAKK,OAAOE,KACjBC,KAAKC,OAENT,MAAKU,OAAS,IACdV,MAAKW,aAAe,EAAE,EAEtBX,MAAKY,eAAiBpB,GAAGqB,MAAMb,KAAKc,gBAAiBd,KAErDR,IAAGuB,MAAMvB,GAAGqB,MAAMb,KAAKgB,KAAMhB,OAG9BR,IAAGC,WAAWwB,IAAM,WAEnB,MAAOvB,GAGRF,IAAGC,WAAWyB,UAAUF,KAAO,WAE9BhB,KAAKI,KAAOZ,GAAGQ,KAAKI,KAEpB,MAAMJ,KAAKK,OAAOE,KACjBC,KAAKW,gBAAgBnB,KAAKI,KAE3B,MAAMJ,KAAKK,OAAOe,KACjBpB,KAAKqB,UAGP7B,IAAGC,WAAWyB,UAAUJ,gBAAkB,SAASQ,GAElD9B,GAAG+B,WACHf,MAAKgB,QAEL,IAAIhC,GAAGiC,KAAKC,KAAKJ,GAAMK,UAAU,EAAG,IAAM,IAC1C,CACCL,EAAO9B,GAAGoC,UAAUN,EACpB,IAAIA,EAAKO,MACT,CACCC,MAAMR,EAAKO,YAIb,CACC,GAAIE,KACJ,OAAOpC,EAAeqC,OAAS,EAC/B,CACCC,aAAetC,EAAeuC,KAC9BH,GAA0BI,KAAKF,aAAaG,WAC5CH,cAAaI,UAAU,MAGxBrC,KAAKI,KAAKkC,UAAYhB,CAGtB,IAAIS,EAA0BC,OAAS,EACvC,CACC,GAAIO,GAAI,WACP,GAAIC,GAAUT,EAA0BG,KACxC,IAAIM,EAAU,GAAK5C,EAAkB4C,GACrC,CACCC,WAAW,WACV7C,EAAkB4C,GAASE,SAASH,IAClC,KAILA,OAKH/C,IAAGC,WAAWyB,UAAUG,QAAU,WAEjCrB,KAAKK,OAAOe,KAAKuB,KAAOnD,GAAGQ,KAAKK,OAAOe,KAAKuB,KAC5C3C,MAAKK,OAAOe,KAAKwB,SAAWpD,GAAGqD,IAAI7C,KAAKK,OAAOe,KAAKuB,KACpD,IAAI3C,KAAKK,OAAOe,KAAKuB,KACrB,CACC3C,KAAK8C,kBACLtD,IAAGuD,KAAKC,OAAQ,SAAUxD,GAAGqB,MAAMb,KAAK8C,iBAAkB9C,QAI5DR,IAAGC,WAAWyB,UAAU4B,iBAAmB,WAE1C,GAAIG,GAAYzD,GAAG0D,qBAClBC,EAAU3D,GAAGqD,IAAI7C,KAAKI,KAEvB,IAAI6C,EAAUG,UAAYD,EAAQE,IAAM,GACvC7D,GAAG8D,SAAStD,KAAKK,OAAOe,KAAKuB,KAAM,6BAEnCnD,IAAG+D,YAAYvD,KAAKK,OAAOe,KAAKuB,KAAM,yBAGxCnD,IAAGC,WAAWyB,UAAUsC,YAAc,SAASC,GAE9C,GAAIzD,KAAKU,QAAUV,KAAKU,OAAOgD,WAC9B1D,KAAKU,OAAOgD,WAAWC,YAAY3D,KAAKU,OAEzC,IAAIV,KAAK4D,eACR5D,KAAK4D,eAAeC,OAErB7D,MAAKU,OAAS+C,EAGfjE,IAAGC,WAAWyB,UAAU4C,OAAS,SAASC,EAAGC,GAE5ChE,KAAKW,aAAeoD,EAAEC,GAGvBxE,IAAGC,WAAWyB,UAAU+C,KAAO,SAASC,GAEvC1D,KAAK2D,SACL3E,IAAG4E,SAASpE,KAAKK,OAAOe,KAAKuB,KAC7BnD,IAAG6E,KAAKpD,IAAIjB,KAAKK,OAAOiE,KACvBC,OAAQ/E,GAAGgF,gBACXC,OAAQ,OACRC,KAAMR,GACJlE,KAAKY,gBAGTpB,IAAGC,WAAWyB,UAAUyD,UAAY,SAAST,GAE5C,GAAIlE,KAAKK,OAAOe,MAAQpB,KAAKK,OAAOe,KAAKuB,KACzC,CACCnD,GAAGoF,UAAU5E,KAAKK,OAAOe,KAAKuB,KAAM,MAGrCnD,GAAGqF,OAAO7B,OAAQ,SAAUxD,GAAGqB,MAAMb,KAAK8C,iBAAkB9C,OAG7DR,IAAGC,WAAWyB,UAAU4D,OAAS,WAEhCtE,KAAK2D,SACL3E,IAAG4E,SAASpE,KAAKI,KACjBZ,IAAG6E,KAAKpD,IAAIjB,KAAKK,OAAOiE,KACvBC,OAAQ/E,GAAGgF,gBACXO,KAAM,UACJ/E,KAAKY,gBAGTpB,IAAGC,WAAWyB,UAAUZ,MAAQ,WAE/BN,KAAK2E,YAMNnF,IAAGwF,gBAAkB,SAASC,GAE7BjF,KAAKkF,QAAUD,EAAOC,KAEtBlF,MAAKoC,WAAa6C,EAAO7C,UACzBpC,MAAKmF,cAAgBF,EAAOE,aAC5BnF,MAAKoF,eAAiBH,EAAOG,cAE7BpF,MAAKI,KAAO6E,EAAO7E,IACnBJ,MAAKqF,KAAOJ,EAAOI,IACnBrF,MAAKsF,UAAYL,EAAOK,SAExBtF,MAAKuF,YAAcN,EAAOM,WAC1BvF,MAAKwF,YAAcP,EAAOO,WAC1BxF,MAAKyF,gBAAkBR,EAAOQ,eAE9BzF,MAAK0F,WACL1F,MAAK2F,kBAEL,KAAK,GAAIC,GAAI,EAAGA,EAAI5F,KAAKsF,UAAUtD,OAAQ4D,IAC3C,CACC5F,KAAK2F,gBAAgB3F,KAAKsF,UAAUM,GAAGC,IAAM7F,KAAKsF,UAAUM,GAG7DhG,EAAkBI,KAAKoC,YAAcpC,IAErCR,IAAGsG,SAAS9F,KAAKI,KAAMZ,GAAGqB,MAAMb,KAAKgB,KAAMhB,MAAO,OAGnDR,IAAGwF,gBAAgB9D,UAAUF,KAAO,SAAS0E,GAE5C,GAAIK,EAEJ/F,MAAKI,KAAOZ,GAAGwG,aACfhG,MAAK0F,SAAWA,CAEhB1F,MAAKI,KAAK6F,eAAiBjG,IAE3BA,MAAKK,OAASb,GAAGC,WAAWwB,MAAMZ,MAClCL,MAAKkF,QAAUlF,KAAKK,OAAOE,IAE3B,IAAIP,KAAKkF,MACT,CACClF,KAAKkG,aAGN,GAAIR,EAASS,2BACb,CACCJ,EAAML,EAASS,2BAA2BC,UAC1C,SAASL,EACT,CACC,GAAIvG,GAAG6G,KAAKC,cAAcP,GAC1B,CACC/F,KAAKuG,WAAWvG,KAAK2F,gBAAgBI,EAAIS,aAAa,cAAcX,GAAIE,EAExE,IAAI/F,KAAKkF,MACT,CACC1F,GAAGiH,eAAeC,SAASX,EAAK/F,KAAK2F,gBAAgBI,EAAIS,aAAa,gBAIxET,EAAMA,EAAIY,aAIZ,KAAMjB,EAASkB,mBAAqB5G,KAAKqF,KACzC,CACCrF,KAAKuG,WAAWvG,KAAKqF,KAAMK,EAASkB,gBAEpC,IAAI5G,KAAKkF,MACT,CACC1F,GAAGiH,eAAeC,SAAShB,EAASkB,gBAAiB5G,KAAK2F,gBAAgB3F,KAAKqF,QAIjF,GAAIrF,KAAKkF,MACT,CACC,GAAIQ,EAASmB,gBACZrH,GAAGuD,KAAK2C,EAASmB,gBAAiB,QAASrH,GAAGsH,SAAS9G,KAAK+G,eAAgB/G,MAC7E,IAAI0F,EAASsB,kBACZxH,GAAGuD,KAAK2C,EAASsB,kBAAmB,QAASxH,GAAGsH,SAAS9G,KAAKiH,iBAAkBjH,MACjF,IAAI0F,EAASwB,eACZ1H,GAAGuD,KAAK2C,EAASwB,eAAgB,QAAS1H,GAAGsH,SAAS9G,KAAKmH,cAAenH,OAG5E,GAAIA,KAAKuF,aAAevF,KAAK0F,SAAS0B,qBACtC,CACC5H,GAAGuD,KAAK/C,KAAK0F,SAAS0B,qBAAsB,QAAS5H,GAAGqB,MAAMb,KAAK0C,SAAU1C,MAC7ER,IAAG6H,YAAYrH,KAAK0F,SAAS0B,sBAG9B,GAAIpH,KAAK0F,SAAS4B,0BAClB,CACC9H,GAAGuD,KAAK/C,KAAK0F,SAAS4B,0BAA2B,QAAS9H,GAAGqB,MAAMb,KAAKuH,cAAevH,QAIzFR,IAAGwF,gBAAgB9D,UAAUqG,cAAgB,WAE5C,IAAKvH,KAAK4D,eACV,CACC,GAAItC,GAAO9B,GAAGgI,OAAO,OAAQC,OAAQC,UAAW,6BAEhD,KAAK,GAAI9B,GAAE,EAAGA,EAAI5F,KAAKsF,UAAUtD,OAAQ4D,IACzC,CACC,GAAI+B,GAAanI,GAAGgI,OAAO,OAC1BC,OAAQC,UAAW,wBACnBE,OACCC,MAAS7H,KAAKsF,UAAUM,GAAGkC,KAC3BC,YAAa/H,KAAKsF,UAAUM,GAAGC,GAC/BmC,WAAYhI,KAAKoC,YAGlB6F,KAAM,MAAQjI,KAAKsF,UAAUM,GAAGsC,MAAQ,6BAA6BlI,KAAKsF,UAAUM,GAAGsC,MAAM,mDAAqD,IAAM,mCAAmClI,KAAKsF,UAAUM,GAAGuC,QAAQ,uDAAuDnI,KAAKsF,UAAUM,GAAGuC,QAAQ,KAAOnI,KAAKsF,UAAUM,GAAGkC,KAAO,cAAgB9H,KAAKsF,UAAUM,GAAGwC,SAAW,wCAAwC5I,GAAGiC,KAAK4G,iBAAiBrI,KAAKsF,UAAUM,GAAGwC,UAAU,SAAW,KAG5d,IAAIpI,KAAKkF,MACT,CACC1F,GAAGiH,eAAeC,SAASiB,EAAY3H,KAAKsF,UAAUM,IAGvD,GAAI5F,KAAKsF,UAAUM,GAAGC,IAAM7F,KAAKqF,KACjC,CACCsC,EAAWD,WAAa,gBACxB,IAAIpG,EAAK8E,WACT,CACC9E,EAAKgH,aAAaX,EAAYrG,EAAK8E,WACnC,WAIF9E,EAAKiH,YAAYZ,GAGlB3H,KAAK4D,eAAiB,GAAIpE,IAAGgJ,YAAY,WAAaC,KAAKC,SAAUlJ,GAAGwG,eACvE2C,WAAY,KACZC,SAAU,KACVC,YAAa,KACbC,OAAQ,EACRC,QAASzH,EACT0H,WAAY,GACZC,MAAQ,OAIVzJ,GAAGC,WAAWwB,MAAM2C,eAAiB5D,KAAK4D,cAC1C5D,MAAK4D,eAAesF,OAIrB1J,IAAGwF,gBAAgB9D,UAAUqF,WAAa,SAAS4C,EAASC,GAE3D,KAAMD,KAAanJ,KAAKK,OAAOgJ,iBAAmBD,EAClD,CACC,IAAKA,EAAUE,GACdF,EAAUE,GAAK,YAAcH,EAAU,IAAMV,KAAKC,QAEnDlJ,IAAG+J,QAAQJ,EAASC,EAAUE,GAAItJ,KAAKK,OAAOiE,MAIhD9E,IAAGwF,gBAAgB9D,UAAU6F,eAAiB,SAASyC,GAEtDhK,GAAGiK,kBAAkBC,UAAUC,iBAAiB3J,KAAKoC,YACrD,OAAO5C,IAAGoK,eAAeJ,GAG1BhK,IAAGwF,gBAAgB9D,UAAUiG,cAAgB,SAASqC,GAErDhK,GAAGiK,kBAAkBC,UAAUG,kBAAkB7J,KAAKoC,YACtD,OAAO5C,IAAGoK,eAAeJ,GAG1BhK,IAAGwF,gBAAgB9D,UAAU+F,iBAAmB,SAASuC,GAExD,GAAIM,QAAQtK,GAAGuK,QAAQ,8BACvB,CACCvJ,KAAK2D,SACL3E,IAAG4E,SAASpE,KAAKI,KACjBZ,IAAG6E,KAAKpD,IAAIjB,KAAKK,OAAOiE,KACvBC,OAAQ/E,GAAGgF,gBACXC,OAAQ,oBACRuF,OAAQhK,KAAKoC,YACX5C,GAAGC,WAAWwB,MAAML,gBAExB,MAAOpB,IAAGoK,eAAeJ,GAO1BhK,IAAGwF,gBAAgB9D,UAAUwB,SAAW,SAASuH,GAEhD,KAAMjK,KAAK0F,SAAS0B,qBACpB,CACC,GAAI8C,IACHnF,KAAM,UACNoF,QAASnK,KAAKoC,WACdgI,MAAOpK,KAAKmF,cAGb,IAAInF,KAAKK,OAAOgK,mBACfH,EAAWI,GAAK,GAEjB9K,IAAG4E,SAASpE,KAAKI,KAEjB,IAAIZ,GAAG6G,KAAKkE,WAAWN,GACtBjK,KAAKwK,kBAAoBP,CAE1BzK,IAAG6E,KAAKpD,IAAIjB,KAAKK,OAAOiE,IAAK4F,EAAY1K,GAAGqB,MAAMb,KAAKyK,UAAWzK,QAIpER,IAAGwF,gBAAgB9D,UAAUuJ,UAAY,SAAUnJ,GACjD9B,GAAG+B,UAAUvB,KAAKI,KAClB,IAAIsK,GAAQlL,GAAGqD,IAAI7C,KAAKI,KAExB,IAAIJ,KAAK2K,OAASD,EAAME,MAAQ,EAC/B,MAED5K,MAAK2K,MAAQnL,GAAGgI,OAAO,OACtBvH,OACC4K,OAAQH,EAAMG,OAAS,KACvB3K,MAAOwK,EAAMxK,MAAQ,OAIvBF,MAAKI,KAAKsD,WAAWoH,aAAa9K,KAAK2K,MAAO3K,KAAKI,KAEnDZ,IAAG8D,SAAStD,KAAKI,KAAM,aACvBZ,IAAG+D,YAAYvD,KAAKI,KAAM,yBAE1BJ,MAAKI,KAAKH,MAAM6I,OAAS,GAAK9I,KAAKmF,aAEnCnF,MAAKI,KAAKH,MAAMoD,IAAMqH,EAAMrH,IAAM,IAClCrD,MAAKI,KAAKH,MAAM2K,KAAOF,EAAME,KAAO,IAEpCG,UAASC,KAAKzC,YAAYvI,KAAKI,KAE/B,IAAIN,GAAaN,GAAGO,qBAEpBC,MAAKiL,QAAUF,SAASC,KAAKzC,YAAY/I,GAAGgI,OAAO,OAClDvH,OACCiL,SAAU,WACV7H,IAAK,MACLuH,KAAM,MACN1K,MAAOJ,EAAWK,YAAc,KAChC0K,OAAQ/K,EAAWqL,aAAe,KAClCrC,OAAQ,GAAK9I,KAAKmF,iBAGpB3F,IAAGuD,KAAKC,OAAQ,SAAUxD,GAAGqB,MAAMhB,EAAWG,KAAKiL,SAEnDjL,MAAKoL,OAASL,SAASC,KAAKzC,YAAY/I,GAAGgI,OAAO,OACjDC,OAAQC,UAAW,iBACnBzH,OACCiL,SAAU,WACV7H,IAAMqH,EAAMrH,IAAM,GAAM,KACxByF,OAAQ,GAAK9I,KAAKmF,cAClBkG,QAAUX,EAAMG,OAAS,GAAM,qBAEhC5C,KAAM3G,IAIP,IAAIgK,GAAY9L,GAAGqD,IAAI7C,KAAKoL,OAC5B,IAAIR,GAAOW,SAASb,EAAME,MAAQF,EAAMc,MAAMd,EAAME,MAAM,GAAKU,EAAUE,MAAMF,EAAUV,MAAM,EAC/F,IAAIA,EAAO,EAAGA,EAAO,EACrB,IAAIA,EAAOF,EAAME,KAChBA,EAAOF,EAAME,KAAK,EAEnB5K,MAAKoL,OAAOnL,MAAM2K,KAAOA,EAAO,IAEhC5K,MAAKyL,MAAQV,SAASC,KAAKzC,YAAY/I,GAAGgI,OAAO,OAChDC,OAAQC,UAAW,gBACnBzH,OACC6I,OAAQ,GAAK9I,KAAKmF,cAClB9B,IAAMqH,EAAMgB,OAAS,EAAK,KAC1Bd,KAAMW,UAAUb,EAAMc,MAAMd,EAAME,MAAM,GAAK,QAK/CU,GAAY9L,GAAGqD,IAAI7C,KAAKoL,OAExB,IAAIE,EAAUE,MAAQd,EAAMc,MAAQ,GACpC,CACCxL,KAAKoL,OAAOnL,MAAMC,MAASwK,EAAMc,MAAQ,GAAKZ,EAAQ,KAGvD5K,KAAKoL,OAAO9I,WAAa,8DAEzB3C,GAAewC,KAAKnC,KAEpBA,MAAKoL,OAAOO,UAAUC,QAAmC5L,KAAKiL,QAAQW,QAAUpM,GAAGqB,MAAMb,KAAKqC,UAAUrC,KAExGR,IAAGuD,KAAK/C,KAAKiL,QAAS,YAAazL,GAAGsH,SAAS,SAAS0C,GAEvD,GAAIxJ,KAAKkF,OAAS1E,KAAKqL,SACvB,CACC7L,KAAK8L,aAAerJ,WAAWjD,GAAGqB,MAAMb,KAAKqC,UAAWrC,MAAO,KAEhE,MAAOR,IAAGoK,eAAeJ,IACvBxJ,MAEHR,IAAGuD,KAAK/C,KAAKoL,OAAQ,YAAa5L,GAAGsH,SAAS,SAAS0C,GAEtD,GAAIxJ,KAAK8L,aACT,CACCC,aAAa/L,KAAK8L,aAClB9L,MAAK8L,aAAe,KAErB,MAAOtM,IAAGoK,eAAeJ,IACvBxJ,MAEH,IAAIR,GAAG6G,KAAKkE,WAAWvK,KAAKwK,mBAC3BxK,KAAKwK,kBAAkBwB,MAAMhM,MAGhCR,IAAGwF,gBAAgB9D,UAAUmB,UAAY,SAAS4J,GAEjD,GAAIA,IAAa,KAChBtM,EAAeuC,KAEhB1C,IAAG+D,YAAYvD,KAAKI,KAAM,aAC1BZ,IAAG8D,SAAStD,KAAKI,KAAM,yBACvBJ,MAAKI,KAAKH,MAAM6I,OAAS,EACzB9I,MAAKI,KAAKH,MAAMoD,IAAM,EACtBrD,MAAKI,KAAKH,MAAM2K,KAAO,EAEvB,IAAI5K,KAAK2K,OAAS3K,KAAK2K,MAAMjH,WAC5B1D,KAAK2K,MAAMjH,WAAWoH,aAAa9K,KAAKI,KAAMJ,KAAK2K,MAEpDnL,IAAGoF,UAAU5E,KAAKoL,OAAQ,KAC1B5L,IAAGoF,UAAU5E,KAAK2K,MAAO,KAEzB,IAAI,MAAQ3K,KAAKiL,QACjB,CACCzL,GAAGqF,OAAO7B,OAAQ,SAAUxD,GAAGqB,MAAMhB,EAAWG,KAAKiL,SACrDzL,IAAGoF,UAAU5E,KAAKiL,QAAS,MAG5B,GAAI,MAAQjL,KAAKyL,MAChBjM,GAAGoF,UAAU5E,KAAKyL,MAAO,KAE1BzL,MAAK2K,MAAQ,IAEb,IAAI3K,KAAKkF,MACRzC,WAAW,WAAYjC,KAAK0L,mBAAoB,KAOlD1M,IAAGwF,gBAAgB9D,UAAUgF,WAAa,WAEzC,GAAIlG,KAAKkF,OAASlF,KAAKI,KACvB,CACCI,KAAK2L,aAAanM,KAAKI,KAAM,IAAOJ,KAAKmF,cAAgB,GAEzDnF,MAAKI,KAAKgM,mBAAqB5M,GAAGqB,MAAMb,KAAKqM,SAAUrM,KACvDA,MAAKI,KAAKkM,kBAAoB9M,GAAGqB,MAAMb,KAAKuM,QAASvM,KACrDA,MAAKI,KAAKoM,iBAAmBhN,GAAGqB,MAAMb,KAAKyM,OAAQzM,KAEnD,KAAKA,KAAKwF,YACV,CACChF,KAAKkM,eAAe1M,KAAKI,KACzBJ,MAAKI,KAAKuM,SAAWnN,GAAGsH,SAAS9G,KAAK4M,OAAQ5M,KAC9CA,MAAKI,KAAKyM,cAAgBrN,GAAGsH,SAAS9G,KAAK8M,QAAS9M,KACpDA,MAAKI,KAAK2M,aAAevN,GAAGsH,SAAS9G,KAAKgN,SAAUhN,QAKvDR,IAAGwF,gBAAgB9D,UAAUmL,SAAW,SAASjM,EAAM2D,EAAGC,EAAGwF,GAE5DA,EAAIA,GAAKxG,OAAOiK,KAChBzN,IAAGwG,cAAcwG,kBAEjB,MAAMpM,EAAK8M,aACV1N,GAAGqB,MAAMb,KAAKmN,kBAAmBnN,MAAMgM,MAAMxM,GAAGwG,cAAeoH,eAE/D5N,IAAGqB,MAAMb,KAAKqN,oBAAqBrN,MAAMgM,MAAMxM,GAAGwG,cAAeoH,UAElE,OAAO,MAGR5N,IAAGwF,gBAAgB9D,UAAUiM,kBAAoB,SAAS/M,EAAM2D,EAAGC,EAAGwF,GAErE,IAAKxJ,KAAKkF,MACT,MAAO,MAER,IAAIoI,GAAclN,EAAKoG,aAAa,aACnC+G,EAAkBnN,EAAKoG,aAAa,WAErC,IAAIxG,KAAKoC,YAAcmL,GAAmBvN,KAAKwN,gBAAkBF,GAAetN,KAAKqF,KACrF,CACC,GAAIrF,KAAKwN,eACT,CACC,KAAMxN,KAAKK,OAAOoN,cAAgB3D,QACjCtK,GAAGuK,QAAQ,oBAAoB2D,QAAQ,aAActN,EAAK8M,aAAapF,MAAM4F,QAAQ,aAAclO,GAAGiC,KAAKC,KAAK1B,KAAK0F,SAASiI,gBAAgBC,WAAW5N,KAAK0F,SAASiI,gBAAgBE,eAExL,CACCrN,KAAK2D,SACL3E,IAAG4E,SAASpE,KAAKI,KACjBZ,IAAG6E,KAAKpD,IAAIjB,KAAKK,OAAOiE,KACvBC,OAAQ/E,GAAGgF,gBACXC,OAAQ,sBACR0E,QAASmE,EACTtD,OAAQhK,KAAKoC,WACb0L,SAAUP,GACR/N,GAAGC,WAAWwB,MAAML,qBAIzB,CACC,GAAIyF,IAAQmD,GAAGxG,OAAOiK,OAAOc,SAAW,EAAI,CAE5C,IAAI/N,KAAKK,OAAOoN,cAAgB3D,QAC9BtK,GAAGuK,QAAQ,6BAA+B1D,GACxCqH,QAAQ,aAAclO,GAAGiC,KAAKC,KAAKtB,EAAK8M,aAAapF,OACrD4F,QAAQ,aAAclO,GAAGiC,KAAKC,KAAK1B,KAAK0F,SAASiI,gBAAgBC,WAAW5N,KAAK0F,SAASiI,gBAAgBE,eAE9G,CACCrN,KAAK2D,SACL3E,IAAG4E,SAASpE,KAAKI,KACjBZ,IAAG6E,KAAKpD,IAAIjB,KAAKK,OAAOiE,KACvBC,OAAQ/E,GAAGgF,gBACXC,OAAQ,oBACR0E,QAASmE,EACTtD,OAAQhK,KAAKoC,WACb0L,SAAUP,EACVlH,KAAMA,GACJ7G,GAAGC,WAAWwB,MAAML,mBAO3BpB,IAAGwF,gBAAgB9D,UAAUmM,oBAAsB,SAASjN,EAAM2D,EAAGC,EAAGwF,GAEvE,IAAKxJ,KAAKkF,OAASlF,KAAKyF,gBACvB,MAAO,MAER,IAAIuI,GAAM5N,EAAK6F,eACdgI,EAAYD,EAAItI,SAASiI,gBACzBO,EAAclO,KAAK0F,SAASiI,eAE7B,IAAIK,GAAOhO,KACX,CACC,KAAMA,KAAKK,OAAOoN,cAAgB3D,QACjCtK,GAAGuK,QAAQ,2BAA2B2D,QAAQ,aAAclO,GAAGiC,KAAKC,KAAKuM,EAAUL,WAAWK,EAAUJ,cAAcH,QAAQ,gBAAiBlO,GAAGiC,KAAKC,KAAKwM,EAAYN,WAAWM,EAAYL,eAEhM,CACCrN,KAAK2D,SACL3E,IAAG4E,SAASpE,KAAKI,KACjBZ,IAAG6E,KAAKpD,IAAIjB,KAAKK,OAAOiE,KACvBC,OAAQ/E,GAAGgF,gBACXC,OAAQ,kBACRuF,OAAQgE,EAAI5L,WACZ+L,OAAQnO,KAAKoC,YACX5C,GAAGC,WAAWwB,MAAML,kBAK1BpB,IAAGwF,gBAAgB9D,UAAUqL,QAAU,SAAS6B,GAE/C,KAAMA,EAAGlB,aACT,CACC,GAAIkB,EAAG5H,aAAa,aAAexG,KAAKoC,YAAcgM,EAAG5H,aAAa,cAAgBxG,KAAKqF,KAC3F,CACC7F,GAAG8D,SAAStD,KAAKI,KAAM,+BAGpB,IAAIgO,GAAMpO,KAAKI,KACpB,CACCZ,GAAG8D,SAAStD,KAAKI,KAAM,sBAGxB,GAAIZ,GAAG6O,QAAQC,QAAU9O,GAAG6O,QAAQE,SACpC,CACCvO,KAAKwO,YAAc,IACnBxO,MAAKyO,YAAc,IAEnB,MAAMzO,KAAK0F,SAAS0B,qBACpB,CACCpH,KAAKwO,YAAchP,GAAGqD,IAAI7C,KAAK0F,SAAS0B,sBAGzC,KAAMpH,KAAK0F,SAASkB,mBAAqBwH,EAAGlB,cAAgBkB,EAAG5H,aAAa,cAAgBxG,KAAKqF,KACjG,CACCrF,KAAKyO,YAAcjP,GAAGqD,IAAI7C,KAAK0F,SAASkB,iBAGzC,GAAI5G,KAAKyO,aAAezO,KAAKwO,YAC7B,CACChP,GAAGuD,KAAKgI,SAAU,YAAavL,GAAGqB,MAAMb,KAAK0O,qBAAsB1O,YAIrE,CACC,KAAMA,KAAK0F,SAAS0B,qBACpB,CACC5H,GAAGuD,KAAK/C,KAAK0F,SAAS0B,qBAAsB,WAAY5H,GAAGqB,MAAMb,KAAK2O,gBAAiB3O,MACvFR,IAAGuD,KAAK/C,KAAK0F,SAAS0B,qBAAsB,YAAa5H,GAAGqB,MAAMb,KAAK4O,iBAAkB5O,OAG1F,KAAMA,KAAK0F,SAASkB,mBAAqBwH,EAAGlB,cAAgBkB,EAAG5H,aAAa,cAAgBxG,KAAKqF,KACjG,CACC7F,GAAGuD,KAAK/C,KAAK0F,SAASkB,gBAAiB,YAAapH,GAAGqB,MAAMb,KAAK6O,iBAAkB7O,MACpFR,IAAGuD,KAAK/C,KAAK0F,SAASkB,gBAAiB,WAAWpH,GAAGqB,MAAMb,KAAK8O,gBAAiB9O,SAKpFR,IAAGwF,gBAAgB9D,UAAUuL,OAAS,WAErCjN,GAAG+D,YAAYvD,KAAKI,KAAM,yBAC1BZ,IAAG+D,YAAYvD,KAAKI,KAAM,qBAC1BZ,IAAG+D,YAAYvD,KAAKI,KAAM,2BAE1B,MAAMJ,KAAK0F,SAAS0B,qBACpB,CACC3E,WAAWjD,GAAGsH,SAAS,WACtBtH,GAAGqF,OAAO7E,KAAK0F,SAAS0B,qBAAsB,YAAa5H,GAAGqB,MAAMb,KAAK4O,iBAAkB5O,MAC3FR,IAAGqF,OAAO7E,KAAK0F,SAAS0B,qBAAsB,WAAY5H,GAAGqB,MAAMb,KAAK2O,gBAAiB3O,QACvFA,MAAO,IAGX,KAAMA,KAAK0F,SAASkB,gBACpB,CACCpH,GAAGqF,OAAO7E,KAAK0F,SAASkB,gBAAiB,YAAapH,GAAGqB,MAAMb,KAAK6O,iBAAkB7O,MACtFR,IAAGqF,OAAO7E,KAAK0F,SAASkB,gBAAiB,WAAWpH,GAAGqB,MAAMb,KAAK8O,gBAAiB9O,OAGpFR,GAAGqF,OAAOkG,SAAU,YAAavL,GAAGqB,MAAMb,KAAK0O,qBAAsB1O,OAGtER,IAAGwF,gBAAgB9D,UAAU4L,QAAU,WAEtC,KAAMtN,GAAG+J,QAAQwF,QAChBvP,GAAG+J,QAAQwF,SAEZ,IAAIlM,GAAMrD,GAAGqD,IAAI7C,KAAKI,KAEtBZ,IAAG8D,SAAStD,KAAKI,KAAM,sBAEvBJ,MAAKU,OAASqK,SAASC,KAAKzC,YAAY/I,GAAGmL,MAAM3K,KAAKI,MACtDZ,IAAGwP,OAAOhP,KAAKU,QACdT,OACCiL,SAAU,WACV7H,IAAKR,EAAIQ,IAAM,KACfuH,KAAM/H,EAAI+H,KAAO,KACjB9B,OAAQ,OAIVtJ,IAAGC,WAAWwB,MAAMuC,YAAYxD,KAAKU,OACrC,IAAIV,KAAK4D,eACR5D,KAAK4D,eAAeC,QAGtBrE,IAAGwF,gBAAgB9D,UAAU0L,OAAS,SAAS7I,EAAGC,GAEjD,KAAMhE,KAAKU,OACX,CACCV,KAAKU,OAAOT,MAAM2K,KAAOnC,KAAKwG,IAAIlL,EAAI,EAAGvD,KAAK0O,QAAQ/O,YAAcH,KAAKU,OAAOyO,YAAc,GAAK,IACnGnP,MAAKU,OAAOT,MAAMoD,IAAMoF,KAAKwG,IAAIjL,EAAI,EAAGxD,KAAK0O,QAAQ/D,aAAenL,KAAKU,OAAO0O,aAAe,GAAK,MAItG5P,IAAGwF,gBAAgB9D,UAAU8L,SAAW,WAEvC,KAAMhN,KAAKU,UAAYV,KAAKU,OAAOgD,WAClC1D,KAAKU,OAAOgD,WAAWC,YAAY3D,KAAKU,OAEzCV,MAAKU,OAAS,IAEdlB,IAAG+D,YAAYvD,KAAKI,KAAM,sBAE1B,MAAMZ,GAAG+J,QAAQ8F,OAChB7P,GAAG+J,QAAQ8F,SAGb7P,IAAGwF,gBAAgB9D,UAAU0N,iBAAmB,WAE/C5O,KAAKsP,aAAe,IACpB,IAAItP,KAAKuP,aACRxD,aAAa/L,KAAKuP,aAEnBvP,MAAKuP,aAAe9M,WAAWjD,GAAGqB,MAAMb,KAAK0C,SAAU1C,MAAO,KAG/DR,IAAGwF,gBAAgB9D,UAAUyN,gBAAkB,WAE9C3O,KAAKsP,aAAe,KACpB,IAAItP,KAAKuP,aACT,CACCxD,aAAa/L,KAAKuP,aAClBvP,MAAKuP,aAAe,MAItB/P,IAAGwF,gBAAgB9D,UAAU2N,iBAAmB,WAE/C7O,KAAKwN,eAAiB,IACtBhO,IAAG8D,SAAStD,KAAKI,KAAM,2BACvBZ,IAAG+D,YAAYvD,KAAKI,KAAM,0BAG3BZ,IAAGwF,gBAAgB9D,UAAU4N,gBAAkB,WAE9C9O,KAAKwN,eAAiB,KACtBhO,IAAG+D,YAAYvD,KAAKI,KAAM,2BAE1B,IAAII,KAAKgP,aAAahJ,aAAa,aAAexG,KAAKoC,WACvD,CACC5C,GAAG8D,SAAStD,KAAKI,KAAM,2BAIzBZ,IAAGwF,gBAAgB9D,UAAUuO,YAAc,SAAS5M,GAEnD,MACCrD,IAAGC,WAAWwB,MAAMN,YAAY,IAAMkC,EAAI+H,MAAQpL,GAAGC,WAAWwB,MAAMN,YAAY,IAAMkC,EAAI2I,OACzFhM,GAAGC,WAAWwB,MAAMN,YAAY,IAAMkC,EAAIQ,KAAO7D,GAAGC,WAAWwB,MAAMN,YAAY,IAAMkC,EAAI6I,OAIhGlM,IAAGwF,gBAAgB9D,UAAUwN,qBAAuB,WAEnD,KAAM1O,KAAKyO,aAAezO,KAAKyP,YAAYzP,KAAKyO,aAChD,CACC,IAAKzO,KAAKwN,eACTxN,KAAK6O,uBAGP,CACC,GAAI7O,KAAKwN,eACRxN,KAAK8O,kBAGP,KAAM9O,KAAKwO,aAAexO,KAAKyP,YAAYzP,KAAKwO,aAChD,CACC,IAAKxO,KAAKsP,aACTtP,KAAK4O,uBAGP,CACC,GAAI5O,KAAKsP,aACRtP,KAAK2O,mBAMRnP,IAAGkQ,iBAAmB,SAASzK,GAE9BjF,KAAKI,KAAO6E,EAAO7E,IACnBJ,MAAKiF,OAASA,CACdjF,MAAKK,OAASb,GAAGC,WAAWwB,MAAMZ,MAElCL,MAAKyE,OAAS,KAEdjF,IAAGuB,MAAMvB,GAAGsH,SAAS9G,KAAKgB,KAAMhB,OAGjCR,IAAGkQ,iBAAiBxO,UAAUF,KAAO,WAEpChB,KAAKI,KAAOZ,GAAGQ,KAAKI,KAEpB,IAAIJ,KAAKI,KACT,CACCJ,KAAKI,KAAKkM,kBAAoB9M,GAAGsH,SAAS9G,KAAKsM,kBAAmBtM,KAClEA,MAAKI,KAAKoM,iBAAmBhN,GAAGsH,SAAS9G,KAAKwM,iBAAkBxM,KAChEA,MAAKI,KAAKgM,mBAAqB5M,GAAGsH,SAAS9G,KAAK2P,OAAQ3P,KAExDQ,MAAK2L,aAAanM,KAAKI,OAIzBZ,IAAGkQ,iBAAiBxO,UAAUyO,OAAS,SAASvP,EAAM2D,EAAGC,EAAGwF,GAE3D,GAAIxJ,KAAKyE,OACT,CACC,GAAIzE,KAAKiF,OAAO2K,eAAiBxP,EAAK6F,eAAeb,eACrD,CACC5E,KAAK2D,SACL3E,IAAG4E,SAASpE,KAAKI,KACjBZ,IAAG6E,KAAKpD,IAAIjB,KAAKK,OAAOiE,KACvBC,OAAQ/E,GAAGgF,gBACXC,OAAQ,kBACRuF,OAAQ5J,EAAK6F,eAAe7D,WAC5B+L,OAAQnO,KAAKiF,OAAO2K,cACpBC,WAAY7P,KAAKiF,OAAO6K,SACxBC,UAAW/P,KAAKiF,OAAO+K,QACvBC,WAAYjQ,KAAKiF,OAAO2K,eACtBpQ,GAAGC,WAAWwB,MAAML,oBAGxB,CACCJ,KAAK2D,SACL3E,IAAG4E,SAASpE,KAAKI,KACjBZ,IAAG6E,KAAKpD,IAAIjB,KAAKK,OAAOiE,KACvBC,OAAQ/E,GAAGgF,gBACXC,OAAQ,kBACRuF,OAAQ5J,EAAK6F,eAAe7D,WAC5ByN,WAAY7P,KAAKiF,OAAO6K,SACxBC,UAAW/P,KAAKiF,OAAO+K,QACvBC,WAAYjQ,KAAKiF,OAAO2K,eACtBpQ,GAAGC,WAAWwB,MAAML,iBAIzBZ,KAAKwM,mBAGNhN,IAAGkQ,iBAAiBxO,UAAUoL,kBAAoB,SAAS8B,GAE1D,KACGA,EAAGnI,gBAEFmI,EAAGnI,eAAe7D,YAAcpC,KAAKiF,OAAO6K,UAC5C1B,EAAGnI,eAAe7D,YAAcpC,KAAKiF,OAAO+K,QAEhD,CACChQ,KAAKyE,OAAS,IACdjF,IAAG8D,SAAStD,KAAKI,KAAM,2BAEvB,IAAIJ,KAAKiF,OAAO2K,eAAiBxB,EAAGnI,eAAeb,eACnD,CACC,GAAI8K,GAAgBlQ,KAAKkQ,eAAiB1Q,GAAG,UAAYQ,KAAKiF,OAAO2K,cACrE,IAAIM,EACJ,CACClQ,KAAKkQ,cAAgBA,CACrBlQ,MAAKkQ,cAAc5D,kBAAkBN,MAAMhM,KAAKkQ,cAAe9C,cAOnE5N,IAAGkQ,iBAAiBxO,UAAUsL,iBAAmB,SAAS4B,GAEzD5O,GAAG+D,YAAYvD,KAAKI,KAAM,2BAE1B,IAAIJ,KAAKkQ,cACRlQ,KAAKkQ,cAAc1D,iBAAiBR,MAAMhM,KAAKkQ,cAAe9C,UAE/DpN,MAAKyE,OAAS,MAKfjF,IAAGiH,gBACF/F,OAAQ,KAERgG,SAAU,SAAStG,EAAMkB,GAExB,KAAMlB,EACN,CACCA,EAAKyM,cAAgBrN,GAAGiH,eAAeoG,aACvCzM,GAAKuM,SAAWnN,GAAGiH,eAAekG,QAClCvM,GAAK2M,aAAevN,GAAGiH,eAAe0J,cAEtC/P,GAAK8M,aAAe5L,CAEpBd,MAAKkM,eAAetM,KAItByM,cAAe,WAEd,IAAK7M,KAAKkN,aACT,MAED,MAAM1N,GAAG+J,QAAQwF,QAChBvP,GAAG+J,QAAQwF,SAEZ,IAAIlM,GAAMrD,GAAGqD,IAAI7C,KAEjBR,IAAGiH,eAAe/F,OAASqK,SAASC,KAAKzC,YAAY/I,GAAGgI,OAAO,OAC9DC,OACCC,UAAW,2BAEZzH,OACCoD,IAAKR,EAAIQ,IAAM,KACfuH,KAAM/H,EAAI+H,KAAO,KACjB9B,OAAQ,MAETb,KAAM,SAAWjI,KAAKkN,aAAahF,MAAQ,6BAA6BlI,KAAKkN,aAAahF,MAAM,mDAAqD,IAAM,yEAAyElI,KAAKkN,aAAapF,KAAM,8CAA8CtI,GAAGiC,KAAK4G,iBAAiBrI,KAAKkN,aAAa9E,UAAU,WAGhW5I,IAAGC,WAAWwB,MAAMuC,YAAYhE,GAAGiH,eAAe/F,SAGnDiM,SAAU,SAAU5I,EAAGC,GAEtBxE,GAAGC,WAAWwB,MAAM6C,OAAOC,EAAGC,EAC9B,IAAIxE,GAAGiH,eAAe/F,OACtB,CACClB,GAAGiH,eAAe/F,OAAOT,MAAM2K,KAAOnC,KAAKwG,IAAIlL,EAAI,EAAGvD,KAAK0O,QAAQ/O,YAChEX,GAAGiH,eAAe/F,OAAOyO,YAAc,GAAK,IAC/C3P,IAAGiH,eAAe/F,OAAOT,MAAMoD,IAAMoF,KAAKwG,IAAIjL,EAAI,EAAGxD,KAAK0O,QAAQ/D,aAC/D3L,GAAGiH,eAAe/F,OAAO0O,aAAe,GAAK,OAIlDe,eAAgB,WAEf,GAAI3Q,GAAGiH,eAAe/F,QAAUlB,GAAGiH,eAAe/F,OAAOgD,WACxDlE,GAAGiH,eAAe/F,OAAOgD,WAAWC,YAAYnE,GAAGiH,eAAe/F,OAEnElB,IAAGiH,eAAe/F,OAAS,IAE3B,MAAMlB,GAAG+J,QAAQ8F,OAChB7P,GAAG+J,QAAQ8F"}