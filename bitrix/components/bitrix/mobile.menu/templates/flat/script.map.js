{"version":3,"file":"script.min.js","sources":["script.js"],"names":["BX","Menu","addCustomEvent","proxy","window","bCalendarShowMobileHelp","this","calendarList","MenuSettings","userId","pullParams","enable","pulltext","lang","downtext","loadtext","app","enableInVersion","action","callback","document","location","reload","pullDown","delegate","command","params","USER_ID","COLOR","style","counterCode","ob","updateCounters","message","counters","obZeroCounter","siteDir","canInvite","calendarFirstVisit","profileUrl","helpUrl","setSettings","settings","type","isNotEmptyString","onCustomEvent","currentItem","init","items","getElementById","that","FastButton","event","onItemClick","buttons","menu-user-accounts","exec","eventCancelBubble","menu-user-help","BXMobileApp","PageManager","loadPageStart","url","menu-user-logout","logOut","buttonId","button","bind","addClass","removeClass","bx24ModernStyle","page_id","target","isChild","hasClass","parentNode","nodeType","unselectItem","selectItem","getAttribute","setTimeout","pageId","sideNotifyPanel","pageParams","onclick","item","id","value","zeroCounter","totalCount","counter","toLowerCase","plus","firstChild","innerHTML","key","parseInt","prototype","userList","openBXTable","isroot","table_settings","alphabet_index","outsection","openNewPage","openUserList","source_url","closeMenu","bpList","p","useTagsInSearch","webdavList","diskList","storageData","path","encodeURIComponent","entityId","undefined","platform","table_id","cache","use_sections","loadPage","MobileMenu"],"mappings":"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"}