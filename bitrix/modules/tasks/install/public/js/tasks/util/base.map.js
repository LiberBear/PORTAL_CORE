{"version":3,"file":"base.min.js","sources":["base.js"],"names":["BX","namespace","Tasks","Util","Base","options","mergeEx","prototype","construct","fireEvent","name","args","onCustomEvent","this","bindEvent","callback","addCustomEvent","callMethod","classRef","arguments","apply","callConstruct","runParentConstructor","owner","superclass","constructor","walkPrototypeChain","obj","fn","ref","destroy","proto","destruct","call","option","value","opts","initialized","sys","passCtx","f","this_","Array","slice","unshift","id","type","isNotEmptyString","toString","toLowerCase","register","Dispatcher","extend","parameters","isPlainObject","child","middle","registerDispatcher","methods","constants","k","hasOwnProperty","parent","DoNothing","vars","registry","pend","bind","registerInstance","instance","ReferenceError","event","cb","get","addDeferredBind","TypeError","isFunction","push","addDeferredFire","params","getInstance","Singletons","dispatcher"],"mappings":"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"}