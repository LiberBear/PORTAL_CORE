<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
\Bitrix\Main\Localization\Loc::loadMessages(dirname(__FILE__)."/footer.php");

CUtil::initJSCore(array('ajax', 'popup'));

?><!DOCTYPE html>
<html>
<head>
<meta name="robots" content="noindex, nofollow, noarchive">
<?
$APPLICATION->showHead();
$APPLICATION->SetAdditionalCSS("/bitrix/templates/bitrix24/interface.css", true);
?>
<title><? $APPLICATION->showTitle(); ?></title>
</head>

<body>
<?
/*
This is commented to avoid Project Quality Control warning
$APPLICATION->ShowPanel();
*/
?>
<table class="main-wrapper">
	<col><col><col>
	<tr>
		<td class="main-wrapper-left-cell"></td>
		<td class="main-wrapper-center-cell">
			<div class="content-wrap">
				<h1 class="main-title">
				<? if (isModuleInstalled('bitrix24')) : ?>
					<? if ($clientLogo = COption::getOptionString('bitrix24', 'client_logo', '')) : ?>
					<img src="<?=CFile::getPath($clientLogo); ?>">
					<? else : ?>
					<span class="main-title-inner"><?=htmlspecialcharsbx(COption::getOptionString('bitrix24', 'site_title', '')); ?></span>
					<? if (COption::getOptionString('bitrix24', 'logo24show', 'Y') !== 'N') : ?><span class="title-num">24</span><? endif; ?>
					<? endif; ?>
				<? else : ?>
					<? if ($logoID = COption::getOptionString('main', 'wizard_site_logo', '', SITE_ID)) : ?>
					<? $APPLICATION->includeComponent(
						'bitrix:main.include', '',
						array('AREA_FILE_SHOW' => 'file', 'PATH' => SITE_DIR.'include/company_name.php')
					); ?>
					<? else : ?>
					<span class="main-title-inner"><?=htmlspecialcharsbx(COption::getOptionString('main', 'site_name', '')); ?></span>
					<span class="title-num">24</span>
					<? endif; ?>
				<? endif; ?>
				</h1>
