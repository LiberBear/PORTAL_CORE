<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;

if(!Loader::includeModule('pull') || !Loader::includeModule('im'))
{
	return;
}

$sent = false;
$sendBroadcastNotify = function () use(&$sent)
{
	if($sent)
	{
		return;
	}

	\CPullStack::addBroadcast(Array(
		'module_id' => 'disk',
		'command' => 'notify',
		'params' => array(
			'setModuleOption' => true,
		),
	));
	$sent = true;
};

$eventManager = EventManager::getInstance();

$eventManager->addEventHandler('main', 'OnAfterSetOption_disk_allow_use_external_link', $sendBroadcastNotify);
$eventManager->addEventHandler('main', 'OnAfterSetOption_disk_object_lock_enabled', $sendBroadcastNotify);