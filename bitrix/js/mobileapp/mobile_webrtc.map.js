{"version":3,"file":"mobile_webrtc.min.js","sources":["mobile_webrtc.js"],"names":["MobileWebrtc","this","siteDir","mobileSiteDir","signalingLink","initiator","callUserId","debug","eventTimeRange","incomingCallTimeOut","delayedIncomingCall","incomingCallTimeOutId","callBackUserId","callChatId","callToGroup","waitTimeout","callGroupUsers","callInit","callActive","ready","pcStart","connected","sessionDescription","remoteSessionDescription","iceCandidates","iceCandidatesToSend","iceCandidateTimeout","peerConnectionInited","userId","BX","message","utcOffest","localStorage","get","ajax","url","method","dataType","timeout","async","onsuccess","proxy","json","localUtcTimeStamp","Math","round","Date","getTime","server_utc_time","set","onfailure","webrtc","setEventListeners","onAnswer","onDecline","onCallback","onClose","onUserMediaSuccess","onDisconnect","onPeerConnectionCreated","onIceCandidateDiscovered","onLocalSessionDescriptionCreated","onIceConnectionStateChanged","onIceGatheringStateChanged","onSignalingStateChanged","onError","prototype","callInvite","video","repeat","chatId","senderId","clearDelayedCallData","callVideo","isRepeatCall","isConversationUIReady","ajaxCall","COMMAND","CHAT_ID","CHAT","VIDEO","delegate","params","ERROR","resetState","finishDialog","bitrix_sessid","BITRIX_SESSID","app","BasicAuth","success","CALL_TO_GROUP","UI","show","state","OUTGOING_CALL","data","recipient","avatar","name","caller","onCustomEvent","showIncomingCall","callCommand","INCOMING_CALL","clearTimeout","isMobile","callSignaling","userID","RECIPIENT_ID","PEER","JSON","stringify","command","parseInt","PARAMS","close","signalingPeerData","peerData","signal","parse","type","createPeerConnection","setRemoteDescription","addIceCandidates","candidates","i","length","push","reqParam","reqData","getUTCOffset","getTimeDiff","utcDate","localTimestamp","incomingTimestamp","abs","timesUp","ACTIVE","INITIATOR","CONVERSATION","getUserMedia","label","candidate","sdpMLineIndex","id","sdpMid","setTimeout","onIceCandidate","createOffer","createAnswer","sdp","showLocalVideo","errorData","window","mwebrtc","addCustomEvent","isTooLate","SERVER_TIME","FAIL_CALL","handler","removeCustomEvent","peer","onReconnect"],"mappings":"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"}