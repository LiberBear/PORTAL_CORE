<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->AddHeadScript('/bitrix/js/crm/interface_form.js');
$APPLICATION->AddHeadScript('/bitrix/js/crm/common.js');
$APPLICATION->AddHeadScript('/bitrix/js/main/dd.js');


$settings = isset($arParams['SETTINGS']) && is_array($arParams['SETTINGS']) ? $arParams['SETTINGS'] : array();

// Looking for 'tab_1' (Only single tab is supported).
$mainTab = null;
foreach($arResult['TABS'] as $tab):
	if($tab['id'] !== 'tab_1')
		continue;
	$mainTab = $tab;
	break;
endforeach;

//Take first tab if tab_1 is not found
if(!$mainTab):
	$mainTab = reset($arResult['TABS']);
endif;

if(!($mainTab && isset($mainTab['fields']) && is_array($mainTab['fields'])))
	return;

$hasRequiredFields = false;

$arUserSearchFields = array();
$arSections = array();
$sectionIndex = -1;
foreach($mainTab['fields'] as &$field):
	if(!is_array($field))
		continue;

	$fieldID = isset($field['id']) ? $field['id'] : '';

	if($field['type'] === 'section'):

		$arSections[] = array(
			'SECTION_FIELD' => $field,
			'SECTION_ID' => $fieldID,
			'SECTION_NAME' => isset($field['name']) ? $field['name'] : $fieldID,
			'FIELDS' => array()
		);
		$sectionIndex++;
		continue;
	endif;

	if($sectionIndex < 0):
		$arSections[] = array(
			'SECTION_FIELD' => null,
			'SECTION_ID' => '',
			'SECTION_NAME' => '',
			'FIELDS' => array()
		);
		$sectionIndex = 0;
	endif;

	$arSections[$sectionIndex]['FIELDS'][] = $field;
endforeach;
unset($field);

if(isset($arParams['TABS_META']))
{
	$arResult['TABS_META'] = $arParams['TABS_META'];
}
elseif($arParams['SHOW_SETTINGS'] && $arResult['OPTIONS']['settings_disabled'])
{
	$arResult['TABS_META'] = array();
	foreach($arResult['TABS'] as $tabID => $tabData)
	{
		$arResult['TABS_META'][$tabID] = array('id'=>$tabID, 'name'=>$tabData['name'], 'title'=>$tabData['title']);
		foreach($tabData['fields'] as $field)
		{
			$fieldInfo = array('id'=>$field['id'], 'name'=>$field['name'], 'type'=>$field['type']);
			if(isset($field['required']))
			{
				$fieldInfo['required'] = $field['required'];
			}
			if(isset($field['persistent']))
			{
				$fieldInfo['persistent'] = $field['persistent'];
			}
			if(isset($field['associatedField']))
			{
				$fieldInfo['associatedField'] = $field['associatedField'];
			}
			if(isset($field['rawId']))
			{
				$fieldInfo['rawId'] = $field['rawId'];
			}
			$arResult['TABS_META'][$tabID]['fields'][$field['id']] = &$fieldInfo;
			unset($fieldInfo);
		}
	}
}

if(isset($arParams['AVAILABLE_FIELDS']))
{
	$arResult['AVAILABLE_FIELDS'] = $arParams['AVAILABLE_FIELDS'];
}

$formIDLower = strtolower($arParams['FORM_ID']);
$containerID = 'container_'.$formIDLower;
$undoContainerID = 'undo_container_'.$formIDLower;

$mode = isset($arParams['MODE']) ? strtoupper($arParams['MODE']) : 'EDIT';
$isVisible = $mode !== 'VIEW' || !isset($arResult['OPTIONS']['show_in_view_mode']) || $arResult['OPTIONS']['show_in_view_mode'] === 'Y';
?><div id="<?=$undoContainerID?>"></div>
<div id="<?=$containerID?>" class="bx-interface-form bx-crm-edit-form"<?=!$isVisible ? ' style="display:none;"' : ''?>>
<script type="text/javascript">
	var bxForm_<?=$arParams['FORM_ID']?> = null;
</script><?
if($arParams['SHOW_FORM_TAG']):
?><form name="form_<?=$arParams['FORM_ID']?>" id="form_<?=$arParams["FORM_ID"]?>" action="<?=POST_FORM_ACTION_URI?>" method="POST" enctype="multipart/form-data">
	<?=bitrix_sessid_post();?>
	<input type="hidden" id="<?=$arParams["FORM_ID"]?>_active_tab" name="<?=$arParams["FORM_ID"]?>_active_tab" value="<?=htmlspecialcharsbx($arResult["SELECTED_TAB"])?>"><?
endif;

$canCreateUserField = (
	CCrmAuthorizationHelper::CheckConfigurationUpdatePermission()
	&& (!isset($arParams['ENABLE_USER_FIELD_CREATION']) || $arParams['ENABLE_USER_FIELD_CREATION'] !== 'N')
);
$canEditSection = (
		CCrmAuthorizationHelper::CheckConfigurationUpdatePermission()
		&& (!isset($arParams['ENABLE_SECTION_EDIT']) || $arParams['ENABLE_SECTION_EDIT'] !== 'N')
);
$canCreateSection = ($canEditSection
	&& CCrmAuthorizationHelper::CheckConfigurationUpdatePermission()
	&& (!isset($arParams['ENABLE_SECTION_CREATION']) || $arParams['ENABLE_SECTION_CREATION'] !== 'N')
);
$title = isset($arParams['~TITLE']) ? $arParams['~TITLE'] : '';
if(is_string($title) && $title !== ''):
?><div class="crm-title-block">
	<span class="ctm-title-text"><?=strip_tags($title)?></span>
	<span id="<?=$arParams['FORM_ID']?>_menu" class="crm-toolbar-btn crm-title-btn">
		<span class="crm-toolbar-btn-icon"></span>
	</span>
</div><?
endif;

$prefix = isset($arParams['~PREFIX']) ? strtolower($arParams['~PREFIX']) : '';
$sectionWrapperID = $formIDLower.'_section_wrapper';
?><div id="<?=$sectionWrapperID?>" class="crm-offer-main-wrap"><?
$sipManagerRequired = false;
$enableFieldDrag = !isset($settings['ENABLE_FIELD_DRAG']) || $settings['ENABLE_FIELD_DRAG'] === 'Y';
$enableSectionDrag = $canEditSection;
if($enableSectionDrag && isset($settings['ENABLE_SECTION_DRAG']))
{
	$enableSectionDrag = $settings['ENABLE_SECTION_DRAG'] === 'Y';
}

foreach($arSections as &$arSection):
	$sectionNodePrefix = strtolower($arSection['SECTION_ID']);
	if($prefix !== "")
		$sectionNodePrefix = "{$prefix}_{$sectionNodePrefix}";

	?><table id="<?=$sectionNodePrefix?>_contents" class="crm-offer-info-table<?=$mode === 'VIEW' ? ' crm-offer-main-info-text' : ''?>"><tbody><?
	$associatedField = isset($arSection['SECTION_FIELD']['associatedField']) && is_array($arSection['SECTION_FIELD']['associatedField'])
		? $arSection['SECTION_FIELD']['associatedField'] : null;

	?><tr id="<?=$arSection['SECTION_ID']?>"><?
		$sectionName = isset($arSection['SECTION_NAME'])
			? htmlspecialcharsbx($arSection['SECTION_NAME']) : $arSection['SECTION_ID'];
		if($associatedField !== null && isset($associatedField['value'])):
			$sectionName = htmlspecialcharsbx($associatedField['value']);
		endif;
		?><td colspan="5">
			<div class="crm-offer-title">
				<span class="crm-offer-drg-btn"<?= ($enableSectionDrag ? '' : ' style="display: none;"')?>></span>
				<span class="crm-offer-title-text"><?=$sectionName?></span>
				<span class="crm-offer-title-set-wrap"><?
				if($mode === 'EDIT'):
				?><span id="<?= $sectionNodePrefix ?>_edit" class="crm-offer-title-edit"<?= ($canEditSection ? '' : ' style="display: none;"') ?>></span><?
				endif;
				?><span id="<?=$sectionNodePrefix?>_delete" class="crm-offer-title-del"<?= ($canEditSection ? '' : ' style="display: none;"') ?>></span><?
				?></span>
			</div><?
			if($associatedField !== null):
				$associatedFieldID = isset($associatedField['id']) ? htmlspecialcharsbx($associatedField['id']) : '';
				$associatedFieldValue= isset($associatedField['value']) ? htmlspecialcharsbx($associatedField['value']) : '';
				?><input type="hidden" id="<?=$associatedFieldID?>" name="<?=$associatedFieldID?>" value="<?=$associatedFieldValue?>" /><?
			endif;
		?></td>
	</tr><?
	$fieldCount = 0;
	foreach($arSection['FIELDS'] as &$field):
		$fieldNodePrefix = strtolower($field["id"]);
		if($prefix !== "")
			$fieldNodePrefix = "{$prefix}_{$fieldNodePrefix}";

		$visible = isset($field['visible']) ? (bool)$field['visible'] : true;
		$dragDropType = $field['type'] === 'lhe' ? 'lhe' : '';
		$containerClassName = $field['type'] === 'address' ? 'crm-offer-row crm-offer-info-address-row' : 'crm-offer-row';

		if(is_array($field['options']) && isset($field['options']['nohover']) && $field['options']['nohover'])
			$containerClassName .= ' crm-offer-row-no-hover';

		$rowContainerID = "{$fieldNodePrefix}_wrap";
		?><tr id="<?=$rowContainerID?>"<?=$visible ? '' : 'style="display:none;"'?> class="<?=$containerClassName?>" data-dragdrop-context="field" data-dragdrop-id="<?=$field["id"]?>"<?=$dragDropType !== '' ? ' data-dragdrop-type="'.$dragDropType.'"' : ''?>>
			<td class="crm-offer-info-drg-btn" <?= ($enableFieldDrag ? '' : ' style="display: none;"') ?>><span class="crm-offer-drg-btn"></span></td><?
		$required = isset($field['required']) && $field['required'] === true;
		$persistent = isset($field['persistent']) && $field['persistent'] === true;

		//default attributes
		if(!is_array($field['params']))
			$field['params'] = array();

		if($field['type'] == '' || $field['type'] == 'text')
		{
			if($field['params']['size'] == '')
				$field['params']['size'] = '30';
		}
		elseif($field['type'] == 'textarea')
		{
			if($field['params']['cols'] == '')
				$field['params']['cols'] = '40';

			if($field['params']['rows'] == '')
				$field['params']['rows'] = '3';
		}
		elseif($field['type'] == 'date')
		{
			if($field['params']['size'] == '')
				$field['params']['size'] = '10';
		}
		elseif($field['type'] == 'date_short')
		{
			if($field['params']['size'] == '')
				$field['params']['size'] = '10';
		}

		$params = '';
		if(is_array($field['params']) && $field['type'] <> 'file')
			foreach($field['params'] as $p=>$v)
				$params .= ' '.$p.'="'.$v.'"';

		$val = isset($field['value']) ? $field['value'] : $arParams['~DATA'][$field['id']];

		if($field['type'] === 'vertical_container'):
			?><td class="crm-offer-info-right" colspan="4">
			<div class="crm-offer-editor-title">
				<div class="crm-offer-editor-title-contents-wapper">
					<?if($required):?><span class="required">*</span><?endif;?>
					<span class="crm-offer-editor-title-contents"><?=htmlspecialcharsEx($field['name'])?></span>
				</div>
			</div>
			<div class="crm-offer-editor-wrap crm-offer-info-data-wrap"><?=$val?></div>
			<span class="crm-offer-edit-btn-wrap"><?
				if(!$required && !$persistent):
				?><span class="crm-offer-item-del"></span><?
				endif;
				?><span class="crm-offer-item-edit"></span>
			</span>
			</td><!-- "crm-offer-info-right" --><?
		elseif($field['type'] === 'lhe'):
			$params = isset($field['componentParams']) ? $field['componentParams'] : array();
			$params['id'] = strtolower("{$arParams['FORM_ID']}_{$field['id']}");

			CModule::IncludeModule('fileman');
			$lhe = new CLightHTMLEditor();
			?><td class="crm-offer-info-right" colspan="4">
				<div class="crm-offer-editor-title">
					<div class="crm-offer-editor-title-contents-wapper">
						<?if($required):?><span class="required">*</span><?endif;?>
						<span class="crm-offer-editor-title-contents"><?=htmlspecialcharsEx($field['name'])?></span>
					</div>
				</div>
				<div class="crm-offer-editor-wrap crm-offer-info-data-wrap"><?$lhe->Show($params);?></div>
				<span class="crm-offer-edit-btn-wrap"><?
					if(!$required && !$persistent):
					?><span class="crm-offer-item-del"></span><?
					endif;
					?><span class="crm-offer-item-edit"></span>
				</span>
			</td><!-- "crm-offer-info-right" --><?
		elseif($field['type'] === 'multiple_address'):
			$params = isset($field['componentParams']) ? $field['componentParams'] : array();
			$addressData = isset($params['DATA']) && is_array($params['DATA']) ? $params['DATA'] : array();
			$addressScheme = isset($params['SCHEME']) && is_array($params['SCHEME']) ? $params['SCHEME'] : array();
			$addressServiceUrl = isset($params['SERVICE_URL']) ? $params['SERVICE_URL'] : '';
			$fielNameTemplate = isset($params['FIELD_NAME_TEMPLATE']) ? $params['FIELD_NAME_TEMPLATE'] : '';

			$addressCreationCaption = GetMessage('intarface_form_add');
			$addressAlreadyExists = isset($params['ALREADY_EXISTS'])
				? $params['ALREADY_EXISTS'] : GetMessage('CRM_ADDRESS_ALREADY_EXISTS');

			if (is_array($params['ADDRESS_TYPE_INFOS']) && !empty($params['ADDRESS_TYPE_INFOS']))
			{
				$addressTypeInfos = $params['ADDRESS_TYPE_INFOS'];
			}
			else
			{
				$addressTypeInfos = Bitrix\Crm\EntityAddress::getClientTypeInfos();
			}
			$addressTypeDesc = array();
			foreach ($addressTypeInfos as $typeInfo)
			{
				if (isset($typeInfo['id']) && isset($typeInfo['desc']))
					$addressTypeDesc[$typeInfo['id']] = $typeInfo['desc'];
			}
			$currentAddressTypeID = Bitrix\Crm\EntityAddress::Primary;
			$createAddressButtonID = strtolower("{$arParams['FORM_ID']}_{$field['id']}_add");
			$addressLabels = Bitrix\Crm\EntityAddress::getLabels();

			$addressDataWrapperID = "{$fieldNodePrefix}_data_wrap";

			?><td class="crm-offer-requisite-table-wrap" colspan="4">
				<div class="crm-offer-address-title">
					<div class="crm-offer-addres-title-contents-wrapper">
						<span class="crm-offer-address-title-contents"><?=$field['name']?></span>
					</div>
				</div>
				<div class="crm-offer-info-data-wrap" id="<?=htmlspecialcharsEx($addressDataWrapperID)?>">
					<div class="crm-offer-requisite-block-wrap">
						<span id="<?=$createAddressButtonID?>" class="crm-offer-requisite-option">
							<span class="crm-offer-requisite-option-caption">
								<?=htmlspecialcharsbx($addressCreationCaption)?>:
							</span>
							<span class="crm-offer-requisite-option-text">
								<?=htmlspecialcharsbx(
									isset($addressTypeDesc[$currentAddressTypeID])
										? $addressTypeDesc[$currentAddressTypeID]
										: Bitrix\Crm\EntityAddress::getTypeDescription($currentAddressTypeID)
								)?>
							</span>
							<span class="crm-offer-requisite-option-arrow"></span>
						</span>
						<div class="crm-offer-requisite-form-wrap">
							<div class="crm-multi-address"><?
							foreach($addressData as $addressTypeID => $addresFields):
								if($fielNameTemplate === '')
									$itemWrapperID = "{$field['id']}_wrapper";
								else
									$itemWrapperID = str_replace(
										array('#TYPE_ID#', '#FIELD_NAME#'),
										array($addressTypeID, 'wrapper'),
										$fielNameTemplate
									);
								$itemWrapperID = strtolower($itemWrapperID);
								$itemTitle = isset($addressTypeDesc[$addressTypeID]) ?
									$addressTypeDesc[$addressTypeID] :
									Bitrix\Crm\EntityAddress::getTypeDescription($addressTypeID);
								?><div class="crm-multi-address-item" id="<?=htmlspecialcharsbx($itemWrapperID)?>">
									<table class="crm-offer-info-table"><tbody>
										<tr>
											<td colspan="5">
												<div class="crm-offer-title">
													<span class="crm-offer-title-text"><?=htmlspecialcharsbx($itemTitle)?></span>
													<span class="crm-offer-title-set-wrap">
														<span class="crm-offer-title-del"></span>
													</span>
												</div>
											</td>
										</tr><?
										foreach($addressScheme as $addressSchemeItem):
											$addresFieldName = $addressSchemeItem['name'];
											$addresFieldType = $addressSchemeItem['type'];
											$addresFieldQualifiedName = $addresFieldName;
											if($fielNameTemplate !== '')
												$addresFieldQualifiedName = str_replace(
													array('#TYPE_ID#', '#FIELD_NAME#'),
													array($addressTypeID, $addresFieldName),
													$fielNameTemplate
												);
											$addresFieldValue = isset($addresFields[$addresFieldName]) ? $addresFields[$addresFieldName] : array();

											if($addresFieldType === 'locality'):
												$addressSchemeItemParams = $addressSchemeItem['params'];
												$addressLocalityType = $addressSchemeItemParams['locality'];
												$addresSearchFieldName = $addressSchemeItem['related'];
												$addresSearchFieldQualifiedName = $addresSearchFieldName;
												if($fielNameTemplate !== '')
													$addresSearchFieldQualifiedName = str_replace(
														array('#TYPE_ID#', '#FIELD_NAME#'),
														array($addressTypeID, $addresSearchFieldName),
														$fielNameTemplate
													);
												?><tr style="display: none;">
													<td colspan="4">
														<input type="hidden" name="<?=$addresFieldQualifiedName?>" value="<?=htmlspecialcharsbx($addresFieldValue)?>"/>
														<script type="text/javascript">
															BX.ready(
																function()
																{
																	BX.CrmLocalitySearchField.create(
																		"<?=$addresSearchFieldQualifiedName?>",
																		{
																			localityType: "<?=$addressLocalityType?>",
																			serviceUrl: "<?=$addressServiceUrl?>",
																			searchInput: "<?=$addresSearchFieldQualifiedName?>",
																			dataInput: "<?=$addresFieldQualifiedName?>"
																		}
																	);
																}
															);
														</script>
													</td>
												</tr><?
											else:
												?><tr>
													<td class="crm-offer-info-left">
														<span class="crm-offer-info-label-alignment"></span>
														<span class="crm-offer-info-label"><?=$addressLabels[$addresFieldName]?>:</span>
													</td>
													<td class="crm-offer-info-right">
														<div class="crm-offer-info-data-wrap"><?
															if($addresFieldType === 'multilinetext'):?>
																<textarea class="bx-crm-edit-text-area" name="<?=htmlspecialcharsEx($addresFieldQualifiedName)?>"><?=$addresFieldValue?></textarea><?
															else:?>
																<input class="crm-offer-item-inp" name="<?=htmlspecialcharsEx($addresFieldQualifiedName)?>" type="text" value="<?=htmlspecialcharsbx($addresFieldValue)?>" />
															<?endif;
														?></div>
													</td>
												<td class="crm-offer-info-right-btn"></td>
												<td class="crm-offer-last-td"></td>
												</tr><?
											endif;
										endforeach;
										?>
									</tbody></table>
								</div><?
							endforeach;
							?></div>
						</div>
					</div>
				</div>
				<script type="text/javascript">
					BX.ready(
						function()
						{
							BX.CrmMultipleAddressItemEditor.messages =
							{
								copyConfirmation: "<?=GetMessageJS("CRM_ADDRESS_COPY_CONFIRMATION")?>",
								deletionConfirmation: "<?=GetMessageJS('CRM_ADRESS_DELETE_CONFIRMATION')?>",
								deleteButton: "<?=GetMessageJS("intarface_form_del")?>"
							};

							BX.CrmMultipleAddressEditor.messages =
							{
								alreadyExists: "<?=CUtil::JSEscape($addressAlreadyExists)?>"
							};

							BX.CrmMultipleAddressEditor.create(
								"<?=$fieldNodePrefix?>",
								{
									fieldId: "",
									formId: "<?=CUtil::JSEscape($arParams['FORM_ID'])?>",
									scheme: <?=CUtil::PhpToJSObject($addressScheme)?>,
									currentTypeId: <?=$currentAddressTypeID?>,
									typeInfos: <?=CUtil::PhpToJSObject($addressTypeInfos)?>,
									fieldLabels: <?=CUtil::PhpToJSObject($addressLabels)?>,
									data: <?=!empty($addressData) ? CUtil::PhpToJSObject($addressData) : '{}'?>,
									container: BX("<?=CUtil::JSEscape($addressDataWrapperID)?>"),
									createButtonContainer: BX("<?=CUtil::JSEscape($createAddressButtonID)?>"),
									serviceUrl: "<?=CUtil::JSEscape($addressServiceUrl)?>",
									fielNameTemplate: "<?=CUtil::JSEscape($fielNameTemplate)?>"
								}
							);
						}
					);
				</script>
			</td>
			<?
		elseif($field['type'] === 'address'):
			$params = isset($field['componentParams']) ? $field['componentParams'] : array();
			$addressData = isset($params['DATA']) ? $params['DATA'] : array();
			$addressServiceUrl = isset($params['SERVICE_URL']) ? $params['SERVICE_URL'] : '';
			?><td class="crm-offer-info-left" colspan="2">
				<div class="crm-offer-address-title">
					<div class="crm-offer-addres-title-contents-wrapper">
						<span class="crm-offer-address-title-contents"><?=$field['name']?></span>
					</div>
				</div>
				<div class="crm-offer-info-data-wrap">
					<table class="crm-offer-info-table"><tbody><?
					$addressLabels = Bitrix\Crm\EntityAddress::getLabels();
					foreach($addressData as $itemKey => $item):
						$itemValue = isset($item['VALUE']) ? $item['VALUE'] : '';
						$itemName = isset($item['NAME']) ? $item['NAME'] : $itemKey;
						$itemLocality = isset($item['LOCALITY']) ? $item['LOCALITY'] : null;
						?><tr>
							<td class="crm-offer-info-left">
								<span class="crm-offer-info-label-alignment"></span>
								<span class="crm-offer-info-label"><?=$addressLabels[$itemKey]?>:</span>
							</td>
							<td class="crm-offer-info-right">
								<div class="crm-offer-info-data-wrap"><?
									if(is_array($itemLocality)):
										$searchInputID = "{$arParams['FORM_ID']}_{$itemName}";
										$dataInputID = "{$arParams['FORM_ID']}_{$itemLocality['NAME']}";
										?><input class="crm-offer-item-inp" id="<?=$searchInputID?>" name="<?=$itemName?>" type="text" value="<?=htmlspecialcharsEx($itemValue)?>" />
										<input type="hidden" id="<?=$dataInputID?>" name="<?=$itemLocality['NAME']?>" value="<?=htmlspecialcharsbx($itemLocality['VALUE'])?>"/>
										<script type="text/javascript">
											BX.ready(
												function()
												{
													BX.CrmLocalitySearchField.create(
														"<?=$searchInputID?>",
														{
															localityType: "<?=$itemLocality['TYPE']?>",
															serviceUrl: "<?=$addressServiceUrl?>",
															searchInput: "<?=$searchInputID?>",
															dataInput: "<?=$dataInputID?>"
														}
													);
												}
											);
										</script><?
									else:
										if(isset($item['IS_MULTILINE']) && $item['IS_MULTILINE']):
											?><textarea class="bx-crm-edit-text-area" name="<?=htmlspecialcharsEx($itemName)?>"><?=$itemValue?></textarea><?
										else:
											?><input class="crm-offer-item-inp" name="<?=htmlspecialcharsEx($itemName)?>" type="text" value="<?=htmlspecialcharsEx($itemValue)?>" /><?
										endif;
									endif;
								?></div>
							</td>
						</tr><?
					endforeach;
				?></tbody></table>
				</div>
			</td><!-- "crm-offer-info-left" -->
			<td class="crm-offer-info-right-btn"><?
				if(!$required && !$persistent):
					?><span class="crm-offer-item-del"></span><?
				endif;
				if($mode === 'EDIT'):
					?><span class="crm-offer-item-edit"></span><?
				endif;
			?></td>
			<td class="crm-offer-last-td"></td><?
		elseif($field['type'] === 'bank_details'):
			$params = isset($field['componentParams']) ? $field['componentParams'] : array();
			$containerId = isset($params['CONTAINER_ID']) ? $params['CONTAINER_ID'] : '';
			?><td class="crm-offer-requisite-table-wrap" colspan="4">
				<div class="crm-offer-address-title">
					<div class="crm-offer-addres-title-contents-wrapper">
						<span class="crm-offer-address-title-contents"><?=$field['name']?></span>
					</div>
				</div>
			<div class="crm-offer-info-data-wrap" id="<?=htmlspecialcharsEx($containerId)?>"></div>
			<script type="text/javascript">
				BX.ready(function(){
					BX.Crm.RequisiteBankDetailsArea.create(
						"<?= CUtil::JSEscape("{$field['id']}_area") ?>",
						{
							mode: "<?= CUtil::JSEscape($mode) ?>",
							container: BX("<?= CUtil::JSEscape($params['CONTAINER_ID']) ?>"),
							fieldList: <?= CUtil::PhpToJSObject($params['FIELD_LIST']) ?>,
							dataList: <?= CUtil::PhpToJSObject($params['DATA_LIST']) ?>,
							fieldNameTemplate: <?= CUtil::PhpToJSObject($params['FIELD_NAME_TEMPLATE']) ?>,
							messages: {
								"addBlockBtnText": "<?= GetMessageJS('CRM_BANK_DETAILS_ADD_BTN_TEXT') ?>",
								'bankDetailsTitle': '<?=GetMessageJS('interface_form_entity_selector_bankDetailsTitle')?>',
								"fieldNamePlaceHolder": "<?= GetMessageJS('interface_form_bank_details_ttl_placeholder') ?>"
							}
						}
					);
				});
			</script>
			</td>
			<?
		else:
			?><td class="crm-offer-info-left">
				<div class="crm-offer-info-label-wrap"><span class="crm-offer-info-label-alignment"></span><?if($required):?><span class="required">*</span><?endif;?><span class="crm-offer-info-label">
					<?if(!in_array($field['type'], array('checkbox', 'vertical_checkbox'))):?><?=htmlspecialcharsEx($field['name'])?>:<?endif;?>
				</span></div>
			</td><?
			?><td class="crm-offer-info-right"><div class="crm-offer-info-data-wrap"><?
			$advancedInfoHTML = '';
			switch($field['type']):
					case 'label':
						echo '<div id="'.$field["id"].'" class="crm-fld-block-readonly">', htmlspecialcharsEx($val), '</div>';
						break;
					case 'custom':
						$isUserField = strpos($field['id'], 'UF_') === 0;
						$wrap = isset($field['wrap']) && $field['wrap'] === true;
						if($isUserField):
							?><div class="bx-crm-edit-user-field"><?
						elseif($wrap):
							?><div class="bx-crm-edit-field"><?
						endif;

						echo $val;
						if($isUserField || $wrap):
							?></div><?
						endif;
						break;
					case 'checkbox':
					case 'vertical_checkbox':
						$chkBxId = strtolower($field['id']).'_chbx';
						?><input type="hidden" name="<?=$field['id']?>" value="N">
						<input class="crm-offer-checkbox" type="checkbox" id="<?=$chkBxId?>" name="<?=$field['id']?>" value="Y"<?=($val == 'Y'? ' checked':'')?><?=$params?>/>
						<label class="crm-offer-label" for="<?=$chkBxId?>"><?=htmlspecialcharsEx($field['name'])?></label><?
						break;
					case 'textarea':
						?><textarea class="bx-crm-edit-text-area" name="<?=$field["id"]?>"<?=$params?>><?=$val?></textarea><?
						break;
					case 'list':
						?><select class="crm-item-table-select" name="<?=$field["id"]?>"<?=$params?>><?
							if(is_array($field["items"])):
								if(!is_array($val))
									$val = array($val);
								foreach($field["items"] as $k=>$v):
									?><option value="<?=htmlspecialcharsbx($k)?>"<?=(in_array($k, $val)? ' selected':'')?>><?=htmlspecialcharsEx($v)?></option><?
								endforeach;
							endif;
							?></select><?
						break;
					case 'file':
						?><div class="bx-crm-edit-file-field"><?
							$arDefParams = array("iMaxW"=>150, "iMaxH"=>150, "sParams"=>"border=0", "strImageUrl"=>"", "bPopup"=>true, "sPopupTitle"=>false, "size"=>20);
							foreach($arDefParams as $k=>$v)
								if(!array_key_exists($k, $field["params"]))
									$field["params"][$k] = $v;

							echo CFile::InputFile($field["id"], $field["params"]["size"], $val);
							if($val <> '')
								echo '<br>'.CFile::ShowImage($val, $field["params"]["iMaxW"], $field["params"]["iMaxH"], $field["params"]["sParams"], $field["params"]["strImageUrl"], $field["params"]["bPopup"], $field["params"]["sPopupTitle"]);
							?></div><?
						break;
					case 'date':
						$fieldId = $field['id'];
						?><input id="<?=$fieldId?>" name="<?=$fieldId?>" class="crm-offer-item-inp crm-item-table-date" type="text" value="<?=htmlspecialcharsbx($val)?>" />
						<script type="text/javascript">
							BX.ready(function(){ BX.CrmDateLinkField.create(BX('<?=CUtil::JSEscape($fieldId)?>'), null, { showTime: true, setFocusOnShow: false }); });
						</script><?
						break;
					case 'date_short':
						$fieldId = $field['id'];
						?><input id="<?=$fieldId?>" name="<?=$fieldId?>" class="crm-offer-item-inp crm-item-table-date" type="text" value="<?=htmlspecialcharsbx($val)?>" />
						<script type="text/javascript">
							BX.ready(function(){ BX.CrmDateLinkField.create(BX('<?=CUtil::JSEscape($fieldId)?>'), null, { showTime: false, setFocusOnShow: false }); });
						</script><?
						break;
					case 'date_link':
						$dataID = "{$arParams['FORM_ID']}_{$field['id']}_DATA";
						$viewID = "{$arParams['FORM_ID']}_{$field['id']}_VIEW";
						?><span id="<?=htmlspecialcharsbx($viewID)?>" class="bx-crm-edit-datetime-link"><?=htmlspecialcharsEx($val !== '' ? $val : GetMessage('interface_form_set_datetime'))?></span>
						<input id="<?=htmlspecialcharsbx($dataID)?>" type="hidden" name="<?=htmlspecialcharsbx($field['id'])?>" value="<?=htmlspecialcharsbx($val)?>" <?=$params?>>
						<script type="text/javascript">BX.ready(function(){ BX.CrmDateLinkField.create(BX('<?=CUtil::addslashes($dataID)?>'), BX('<?=CUtil::addslashes($viewID)?>'), { showTime: false }); });</script><?
						break;
					case 'intranet_user_search':
						$params = isset($field['componentParams']) ? $field['componentParams'] : array();
						if(!empty($params)):
							$rsUser = CUser::GetByID($val);
							if($arUser = $rsUser->Fetch()):
								$params['USER'] = $arUser;
							endif;
							?><input type="text" class="crm-offer-item-inp" name="<?=htmlspecialcharsbx($params['SEARCH_INPUT_NAME'])?>">
						<input type="hidden" name="<?=htmlspecialcharsbx($params['INPUT_NAME'])?>" value="<?=htmlspecialcharsbx($val)?>"><?
							$arUserSearchFields[] = $params;
							$APPLICATION->IncludeComponent(
								'bitrix:intranet.user.selector.new',
								'',
								array(
									'MULTIPLE' => 'N',
									'NAME' => $params['NAME'],
									'INPUT_NAME' => $params['SEARCH_INPUT_NAME'],
									'POPUP' => 'Y',
									'SITE_ID' => SITE_ID,
									'NAME_TEMPLATE' => $params['NAME_TEMPLATE']
								),
								null,
								array('HIDE_ICONS' => 'Y')
							);
						endif;
						break;
					case 'crm_entity_selector':
						CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/crm.js');
						$params = isset($field['componentParams']) ? $field['componentParams'] : array();
						if(!empty($params)):
							$context = isset($params['CONTEXT']) ? $params['CONTEXT'] : '';
							$entityType = isset($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '';
							$entityID = isset($params['INPUT_VALUE']) ? intval($params['INPUT_VALUE']) : 0;
							$newEntityID = isset($params['NEW_INPUT_VALUE']) ? intval($params['NEW_INPUT_VALUE']) : 0;
							$rqLinkedId = isset($params['REQUISITE_LINKED_ID']) ? intval($params['REQUISITE_LINKED_ID']) : 0;
							$rqLinkedInputId = '';
							$bdLinkedId = isset($params['BANK_DETAIL_LINKED_ID']) ? intval($params['BANK_DETAIL_LINKED_ID']) : 0;
							$bdLinkedInputId = '';
							$editorID = "{$arParams['FORM_ID']}_{$field['id']}";
							$containerID = "{$arParams['FORM_ID']}_FIELD_CONTAINER_{$field['id']}";
							$selectorID = "{$arParams['FORM_ID']}_ENTITY_SELECTOR_{$field['id']}";
							$changeButtonID = "{$arParams['FORM_ID']}_CHANGE_BTN_{$field['id']}";
							$dataInputName = isset($params['INPUT_NAME']) ? $params['INPUT_NAME'] : $field['id'];
							$dataInputID = "{$arParams['FORM_ID']}_DATA_INPUT_{$dataInputName}";
							$newDataInputName = isset($params['NEW_INPUT_NAME']) ? $params['NEW_INPUT_NAME'] : '';
							$newDataInputID = $newDataInputName !== '' ? "{$arParams['FORM_ID']}_NEW_DATA_INPUT_{$dataInputName}" : '';
							$cardViewMode = $requireRequisiteData = false;
							if ($entityType === 'CONTACT' || $entityType === 'COMPANY')
							{
								$cardViewMode = $requireRequisiteData = true;
							}
							$entityInfo = CCrmEntitySelectorHelper::PrepareEntityInfo(
								$entityType,
								$entityID,
								array(
									'ENTITY_EDITOR_FORMAT' => true,
									'REQUIRE_REQUISITE_DATA' => $requireRequisiteData,
									'NAME_TEMPLATE' => isset($params['NAME_TEMPLATE']) ?
										$params['NAME_TEMPLATE'] :
										\Bitrix\Crm\Format\PersonNameFormatter::getFormat()
								)
							);
							$advancedInfoHTML = '<div id="'.htmlspecialcharsbx($containerID.'_descr').'" class="crm-offer-info-description"></div>';
							?><div id="<?=htmlspecialcharsbx($containerID)?>" class="bx-crm-edit-crm-entity-field">
							<div class="bx-crm-entity-info-wrapper"></div>
							<input type="hidden" id="<?=htmlspecialcharsbx($dataInputID)?>" name="<?=htmlspecialcharsbx($dataInputName)?>" value="<?=$entityID?>" /><?
							if($newDataInputName !== ''):
							?><input type="hidden" id="<?=htmlspecialcharsbx($newDataInputID)?>" name="<?=htmlspecialcharsbx($newDataInputName)?>" value="<?=$newEntityID?>" /><?
							endif;
							if ($requireRequisiteData)
							{
								$rqLinkedInputName = '';
								if (isset($params['REQUISITE_INPUT_NAME']))
								{
									$rqLinkedInputName = $params['REQUISITE_INPUT_NAME'];
								}
								else
								{
									$postfix = '_REQUISITE_ID';
									if (isset($params['INPUT_NAME']))
										$rqLinkedInputName = $params['INPUT_NAME'].$postfix;
									else
										$rqLinkedInputName = $field['id'].$postfix;
								}
								$rqLinkedInputId = "{$arParams['FORM_ID']}_DATA_INPUT_{$rqLinkedInputName}";
								?><input type="hidden" id="<?= htmlspecialcharsbx($rqLinkedInputId) ?>" name="<?= htmlspecialcharsbx($rqLinkedInputName) ?>" value="<?= $rqLinkedId ?>" /><?
								
								$bdLinkedInputName = '';
								if (isset($params['BANK_DETAIL_INPUT_NAME']))
								{
									$bdLinkedInputName = $params['BANK_DETAIL_INPUT_NAME'];
								}
								else
								{
									$postfix = '_BANK_DETAIL_ID';
									if (isset($params['INPUT_NAME']))
										$bdLinkedInputName = $params['INPUT_NAME'].$postfix;
									else
										$bdLinkedInputName = $field['id'].$postfix;
								}
								$bdLinkedInputId = "{$arParams['FORM_ID']}_DATA_INPUT_{$bdLinkedInputName}";
								?><input type="hidden" id="<?= htmlspecialcharsbx($bdLinkedInputId) ?>" name="<?= htmlspecialcharsbx($bdLinkedInputName) ?>" value="<?= $bdLinkedId ?>" /><?
							}
							?><div class="bx-crm-entity-buttons-wrapper">
							<span id="<?=htmlspecialcharsbx($changeButtonID)?>" class="bx-crm-edit-crm-entity-change"><?= htmlspecialcharsbx(GetMessage('intarface_form_select'))?></span><?
							if($newDataInputName !== ''):
							?> <span class="bx-crm-edit-crm-entity-add"><?=htmlspecialcharsEx(GetMessage('interface_form_add_new_entity'))?></span><?
							endif;
						?></div>
						</div><?
							$serviceUrl = '';
							$createUrl = '';
							$actionName = '';
							$dialogSettings = array(
								'addButtonName' => GetMessage('interface_form_add_dialog_btn_add'),
								'cancelButtonName' => GetMessage('interface_form_cancel')
							);
							if($entityType === 'CONTACT')
							{
								$serviceUrl = '/bitrix/components/bitrix/crm.contact.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get();
								$createUrl = CCrmOwnerType::GetEditUrl(CCrmOwnerType::Contact, 0, false);
								$actionName = 'SAVE_CONTACT';

								$dialogSettings['title'] = GetMessage('interface_form_add_contact_dlg_title');
								$dialogSettings['lastNameTitle'] = GetMessage('interface_form_add_contact_fld_last_name');
								$dialogSettings['nameTitle'] = GetMessage('interface_form_add_contact_fld_name');
								$dialogSettings['secondNameTitle'] = GetMessage('interface_form_add_contact_fld_second_name');
								$dialogSettings['emailTitle'] = GetMessage('interface_form_add_contact_fld_email');
								$dialogSettings['phoneTitle'] = GetMessage('interface_form_add_contact_fld_phone');
								$dialogSettings['exportTitle'] = GetMessage('interface_form_add_contact_fld_export');
							}
							elseif($entityType === 'COMPANY')
							{
								$serviceUrl = '/bitrix/components/bitrix/crm.company.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get();
								$createUrl = CCrmOwnerType::GetEditUrl(CCrmOwnerType::Company, 0, false);
								$actionName = 'SAVE_COMPANY';

								$dialogSettings['title'] = GetMessage('interface_form_add_company_dlg_title');
								$dialogSettings['titleTitle'] = GetMessage('interface_form_add_company_fld_title_name');
								$dialogSettings['companyTypeTitle'] = GetMessage('interface_form_add_conpany_fld_company_type');
								$dialogSettings['industryTitle'] = GetMessage('interface_form_add_company_fld_industry');
								$dialogSettings['emailTitle'] = GetMessage('interface_form_add_conpany_fld_email');
								$dialogSettings['phoneTitle'] = GetMessage('interface_form_add_company_fld_phone');
								$dialogSettings['companyTypeItems'] = CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('COMPANY_TYPE'));
								$dialogSettings['industryItems'] = CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('INDUSTRY'));
							}
							elseif($entityType === 'DEAL')
							{
								$dialogSettings['title'] = GetMessage('interface_form_add_company_dlg_title');
								$dialogSettings['titleTitle'] = GetMessage('interface_form_add_company_fld_title_name');
								$dialogSettings['dealTypeTitle'] = GetMessage('interface_form_add_conpany_fld_company_type');
								$dialogSettings['dealPriceTitle'] = GetMessage('interface_form_add_company_fld_industry');
								$dialogSettings['companyTypeItems'] = CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('DEAL_TYPE'));
							}
							elseif($entityType === 'QUOTE')
							{
								$dialogSettings['titleTitle'] = GetMessage('interface_form_add_company_fld_title_name');
							}
							//$sipManagerRequired = true;
							?><script type="text/javascript">
							BX.ready(
									function()
									{
										var entitySelectorId = CRM.Set(
											BX('<?=CUtil::JSEscape($changeButtonID) ?>'),
											'<?=CUtil::JSEscape($selectorID)?>',
											'',
											<?php
												echo CUtil::PhpToJsObject(
													CCrmEntitySelectorHelper::PreparePopupItems(
														$entityType,
														false,
														isset($params['NAME_TEMPLATE']) ?
															$params['NAME_TEMPLATE'] :
															\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
														50,
														array('REQUIRE_REQUISITE_DATA' => $requireRequisiteData)
													)
												);
											?>,
											false,
											false,
											['<?=CUtil::JSEscape(strtolower($entityType))?>'],
											<?=CUtil::PhpToJsObject(CCrmEntitySelectorHelper::PrepareCommonMessages())?>,
											true,
											{requireRequisiteData: <?= $requireRequisiteData ? 'true' : 'false' ?>}
										);

										BX.CrmEntityEditor.messages =
										{
											'unknownError': '<?=GetMessageJS('interface_form_ajax_unknown_error')?>',
											'prefContactType': '<?=GetMessageJS('interface_form_entity_selector_prefContactType')?>',
											'prefPhone': '<?=GetMessageJS('interface_form_entity_selector_prefPhone')?>',
											'prefPhoneLong': '<?=GetMessageJS('interface_form_entity_selector_prefPhoneLong')?>',
											'prefEmail': '<?=GetMessageJS('interface_form_entity_selector_prefEmail')?>',
											'tabTitleAbout': '<?=GetMessageJS('interface_form_entity_selector_tabTitleAbout')?>',
											'contactTabTitleAbout': '<?=GetMessageJS('interface_form_entity_selector_contactTabTitleAbout')?>',
											'companyTabTitleAbout': '<?=GetMessageJS('interface_form_entity_selector_companyTabTitleAbout')?>',
											'tabTitleContactRequisites': '<?=GetMessageJS('interface_form_entity_selector_tabTitleContactRequisites')?>',
											'tabTitleCompanyRequisites': '<?=GetMessageJS('interface_form_entity_selector_tabTitleCompanyRequisites')?>',
											'bankDetailsTitle': '<?=GetMessageJS('interface_form_entity_selector_bankDetailsTitle')?>'
										};

										BX.CrmEntityEditor.create(
											'<?=CUtil::JSEscape($editorID)?>',
											{
													'context': '<?=CUtil::JSEscape($context)?>',
												'typeName': '<?=CUtil::JSEscape($entityType)?>',
												'containerId': '<?=CUtil::JSEscape($containerID)?>',
												'dataInputId': '<?=CUtil::JSEscape($dataInputID)?>',
												'newDataInputId': '<?=CUtil::JSEscape($newDataInputID)?>',
												'entitySelectorId': entitySelectorId,
												'serviceUrl': '<?= CUtil::JSEscape($serviceUrl) ?>',
													'createUrl': '<?= CUtil::JSEscape($createUrl) ?>',
												'actionName': '<?= CUtil::JSEscape($actionName) ?>',
												'dialog': <?=CUtil::PhpToJSObject($dialogSettings)?>,
												'cardViewMode': <?php echo ($cardViewMode ? 'true' : 'false'); ?>,
												'rqLinkedInputId': '<?=CUtil::JSEscape($rqLinkedInputId)?>',
												'bdLinkedInputId': '<?=CUtil::JSEscape($bdLinkedInputId)?>',
												'rqLinkedId': '<?=CUtil::JSEscape($rqLinkedId)?>',
												'bdLinkedId': '<?=CUtil::JSEscape($bdLinkedId)?>'
											},
											null,
											BX.CrmEntityInfo.create(<?=CUtil::PhpToJSObject($entityInfo)?>)
										);
									}
							);
						</script><?
						endif;
						break;
					case 'crm_client_selector':
						CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/crm.js');
						$params = isset($field['componentParams']) ? $field['componentParams'] : array();
						if(!empty($params))
						{
							$context = isset($params['CONTEXT']) ? $params['CONTEXT'] : '';
							$entityID = $inputValue = isset($params['INPUT_VALUE']) ? $params['INPUT_VALUE'] : '';
							$entityType = isset($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '';
							switch (substr($entityID, 0, 2))
							{
								case 'C_':
									$valEntityType = 'contact';
									break;
								case 'CO':
									$valEntityType = 'company';
									break;
								default:
									$valEntityType = '';
							}
							$entityID = intval(substr($entityID, intval(strpos($entityID, '_')) + 1));
							$rqLinkedId = isset($params['REQUISITE_LINKED_ID']) ? intval($params['REQUISITE_LINKED_ID']) : 0;
							$rqLinkedInputId = '';
							$bdLinkedId = isset($params['BANK_DETAIL_LINKED_ID']) ? intval($params['BANK_DETAIL_LINKED_ID']) : 0;
							$bdLinkedInputId = '';
							$editorID = "{$arParams['FORM_ID']}_{$field['id']}";
							$containerID = "{$arParams['FORM_ID']}_FIELD_CONTAINER_{$field['id']}";
							$createEntitiesBlockID = "{$arParams['FORM_ID']}_CREATE_ENTITIES_{$field['id']}";
							$selectorID = "{$arParams['FORM_ID']}_ENTITY_SELECTOR_{$field['id']}";
							$changeButtonID = "{$arParams['FORM_ID']}_CHANGE_BTN_{$field['id']}";
							$addContactButtonID = "{$arParams['FORM_ID']}_ADD_CONTACT_BTN_{$field['id']}";
							$addCompanyButtonID = "{$arParams['FORM_ID']}_ADD_COMPANY_BTN_{$field['id']}";
							$dataInputName = isset($params['INPUT_NAME']) ? $params['INPUT_NAME'] : $field['id'];
							$dataInputID = "{$arParams['FORM_ID']}_DATA_INPUT_{$dataInputName}";
							$newDataInputName = isset($params['NEW_INPUT_NAME']) ? $params['NEW_INPUT_NAME'] : '';
							$newDataInputID = $newDataInputName !== '' ? "{$arParams['FORM_ID']}_NEW_DATA_INPUT_{$dataInputName}" : '';
							$cardViewMode = $requireRequisiteData = true;
							$entityInfo = CCrmEntitySelectorHelper::PrepareEntityInfo(
								$valEntityType,
								$entityID,
								array(
									'ENTITY_EDITOR_FORMAT' => true,
									'ENTITY_PREFIX_ENABLED' => true,
									'REQUIRE_REQUISITE_DATA' => true,
									'NAME_TEMPLATE' => isset($params['NAME_TEMPLATE']) ?
										$params['NAME_TEMPLATE'] :
										\Bitrix\Crm\Format\PersonNameFormatter::getFormat()
								)
							);
							$advancedInfoHTML = '<div id="'.htmlspecialcharsbx($containerID.'_descr').'" class="crm-offer-info-description"></div>';
							?><div id="<?=htmlspecialcharsbx($containerID)?>" class="bx-crm-edit-crm-entity-field">
							<div class="bx-crm-entity-info-wrapper"></div>
							<input type="hidden" id="<?=htmlspecialcharsbx($dataInputID)?>" name="<?=htmlspecialcharsbx($dataInputName)?>" value="<?=htmlspecialcharsbx($inputValue)?>" />
							<? if($newDataInputName !== ''): ?>
								<input type="hidden" id="<?=htmlspecialcharsbx($newDataInputID)?>" name="<?=htmlspecialcharsbx($newDataInputName)?>" value="" />
							<? endif;
							if ($requireRequisiteData)
							{
								$rqLinkedInputName = '';
								if (isset($params['REQUISITE_INPUT_NAME']))
								{
									$rqLinkedInputName = $params['REQUISITE_INPUT_NAME'];
								}
								else
								{
									$postfix = '_REQUISITE_ID';
									if (isset($params['INPUT_NAME']))
										$rqLinkedInputName = $params['INPUT_NAME'].$postfix;
									else
										$rqLinkedInputName = $field['id'].$postfix;
								}
								$rqLinkedInputId = "{$arParams['FORM_ID']}_DATA_INPUT_{$rqLinkedInputName}";
								?><input type="hidden" id="<?= htmlspecialcharsbx($rqLinkedInputId) ?>" name="<?= htmlspecialcharsbx($rqLinkedInputName) ?>" value="<?= $rqLinkedId ?>" /><?
								
								$bdLinkedInputName = '';
								if (isset($params['BANK_DETAIL_INPUT_NAME']))
								{
									$bdLinkedInputName = $params['BANK_DETAIL_INPUT_NAME'];
								}
								else
								{
									$postfix = '_BANK_DETAIL_ID';
									if (isset($params['INPUT_NAME']))
										$bdLinkedInputName = $params['INPUT_NAME'].$postfix;
									else
										$bdLinkedInputName = $field['id'].$postfix;
								}
								$bdLinkedInputId = "{$arParams['FORM_ID']}_DATA_INPUT_{$bdLinkedInputName}";
								?><input type="hidden" id="<?= htmlspecialcharsbx($bdLinkedInputId) ?>" name="<?= htmlspecialcharsbx($bdLinkedInputName) ?>" value="<?= $bdLinkedId ?>" /><?
							}
							?>
							<!--<div class="bx-crm-entity-buttons-wrapper">-->
								<span id="<?=htmlspecialcharsbx($changeButtonID)?>" class="bx-crm-edit-crm-entity-change"><?= htmlspecialcharsbx(GetMessage('intarface_form_select'))?></span>
								<? if($newDataInputName !== ''): ?>
									<br>
									<span id="<?=htmlspecialcharsbx($createEntitiesBlockID)?>" class="bx-crm-edit-description"<?=($entityID>0)?' style="display: none;"':''?>>
									<span><?=htmlspecialcharsEx(GetMessage('interface_form_add_new_entity'))?> </span>
									<span id="<?=htmlspecialcharsbx($addCompanyButtonID)?>" class="bx-crm-edit-crm-entity-add"><?= htmlspecialcharsbx(GetMessage('interface_form_add_btn_company'))?></span>
									<span><?= htmlspecialcharsbx(' '.GetMessage('interface_form_add_btn_or')).' '?></span>
									<span id="<?=htmlspecialcharsbx($addContactButtonID)?>" class="bx-crm-edit-crm-entity-add"><?= htmlspecialcharsbx(GetMessage('interface_form_add_btn_contact'))?></span>
									</span>
								<? endif; ?>
							<!--</div>-->
							</div><?
							$dialogSettings['CONTACT'] = array(
								'addButtonName' => GetMessage('interface_form_add_dialog_btn_add'),
								'cancelButtonName' => GetMessage('interface_form_cancel'),
								'title' => GetMessage('interface_form_add_contact_dlg_title'),
								'lastNameTitle' => GetMessage('interface_form_add_contact_fld_last_name'),
								'nameTitle' => GetMessage('interface_form_add_contact_fld_name'),
								'secondNameTitle' => GetMessage('interface_form_add_contact_fld_second_name'),
								'emailTitle' => GetMessage('interface_form_add_contact_fld_email'),
								'phoneTitle' => GetMessage('interface_form_add_contact_fld_phone'),
								'exportTitle' => GetMessage('interface_form_add_contact_fld_export')
							);
							$dialogSettings['COMPANY'] = array(
								'addButtonName' => GetMessage('interface_form_add_dialog_btn_add'),
								'cancelButtonName' => GetMessage('interface_form_cancel'),
								'title' => GetMessage('interface_form_add_company_dlg_title'),
								'titleTitle' => GetMessage('interface_form_add_company_fld_title_name'),
								'companyTypeTitle' => GetMessage('interface_form_add_conpany_fld_company_type'),
								'industryTitle' => GetMessage('interface_form_add_company_fld_industry'),
								'emailTitle' => GetMessage('interface_form_add_conpany_fld_email'),
								'phoneTitle' => GetMessage('interface_form_add_company_fld_phone'),
								'companyTypeItems' => CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('COMPANY_TYPE')),
								'industryItems' => CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('INDUSTRY'))
							);
							//$sipManagerRequired = true;
							?><script type="text/javascript">
							BX.ready(
								function()
								{
									var entitySelectorId = CRM.Set(
										BX('<?=CUtil::JSEscape($changeButtonID) ?>'),
										'<?=CUtil::JSEscape($selectorID)?>',
										'',
										<?php
											echo CUtil::PhpToJsObject(
												CCrmEntitySelectorHelper::PreparePopupItems(
													$entityType,
													true,
													isset($params['NAME_TEMPLATE']) ?
														$params['NAME_TEMPLATE'] :
														\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
													50,
													array('REQUIRE_REQUISITE_DATA' => true)
												)
											);
										?>,
										false,
										false,
										<?=CUtil::PhpToJsObject($entityType)?>,
										<?=CUtil::PhpToJsObject(CCrmEntitySelectorHelper::PrepareCommonMessages())?>,
										true,
										{requireRequisiteData: true}
									);

									BX.CrmEntityEditor.messages =
									{
										'unknownError': '<?=GetMessageJS('interface_form_ajax_unknown_error')?>',
										'prefContactType': '<?=GetMessageJS('interface_form_entity_selector_prefContactType')?>',
										'prefPhone': '<?=GetMessageJS('interface_form_entity_selector_prefPhone')?>',
										'prefPhoneLong': '<?=GetMessageJS('interface_form_entity_selector_prefPhoneLong')?>',
										'prefEmail': '<?=GetMessageJS('interface_form_entity_selector_prefEmail')?>',
										'tabTitleAbout': '<?=GetMessageJS('interface_form_entity_selector_tabTitleAbout')?>',
										'contactTabTitleAbout': '<?=GetMessageJS('interface_form_entity_selector_contactTabTitleAbout')?>',
										'companyTabTitleAbout': '<?=GetMessageJS('interface_form_entity_selector_companyTabTitleAbout')?>',
										'tabTitleContactRequisites': '<?=GetMessageJS('interface_form_entity_selector_tabTitleContactRequisites')?>',
										'tabTitleCompanyRequisites': '<?=GetMessageJS('interface_form_entity_selector_tabTitleCompanyRequisites')?>',
										'bankDetailsTitle': '<?=GetMessageJS('interface_form_entity_selector_bankDetailsTitle')?>'
									};

									BX.CrmEntityEditor.create(
										'<?=CUtil::JSEscape($editorID.'_C')?>',
										{
											'context': '<?=CUtil::JSEscape($context)?>',
											'typeName': 'CONTACT',
											'containerId': '<?=CUtil::JSEscape($containerID)?>',
											'buttonAddId': '<?=CUtil::JSEscape($addContactButtonID)?>',
											'enableValuePrefix': true,
											'dataInputId': '<?=CUtil::JSEscape($dataInputID)?>',
											'newDataInputId': '<?=CUtil::JSEscape($newDataInputID)?>',
											'entitySelectorId': entitySelectorId,
											'serviceUrl': '<?=CUtil::JSEscape('/bitrix/components/bitrix/crm.contact.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get())?>',
											'createUrl': '<?=CUtil::JSEscape(CCrmOwnerType::GetEditUrl(CCrmOwnerType::Contact, 0, false))?>',
											'actionName': 'SAVE_CONTACT',
											'dialog': <?=CUtil::PhpToJSObject($dialogSettings['CONTACT'])?>,
											'cardViewMode': <?php echo ($cardViewMode ? 'true' : 'false'); ?>,
											'rqLinkedInputId': '<?=CUtil::JSEscape($rqLinkedInputId)?>',
											'bdLinkedInputId': '<?=CUtil::JSEscape($bdLinkedInputId)?>',
											'rqLinkedId': '<?=CUtil::JSEscape($valEntityType === 'contact' ? $rqLinkedId : 0)?>',
											'bdLinkedId': '<?=CUtil::JSEscape($valEntityType === 'contact' ? $bdLinkedId : 0)?>',
											'skipInitInput': '<?=CUtil::JSEscape($valEntityType !== 'contact' ? 'true' : 'false')?>'

										},
										null,
										<?= (($valEntityType === 'contact') ? 'BX.CrmEntityInfo.create('.CUtil::PhpToJSObject($entityInfo).')' : 'null') ?>
									);

									BX.CrmEntityEditor.create(
										'<?=CUtil::JSEscape($editorID).'_CO'?>',
										{
											'context': '<?=CUtil::JSEscape($context)?>',
											'typeName': 'COMPANY',
											'containerId': '<?=CUtil::JSEscape($containerID)?>',
											'buttonAddId': '<?=CUtil::JSEscape($addCompanyButtonID)?>',
											'buttonChangeIgnore': true,
											'enableValuePrefix': true,
											'dataInputId': '<?=CUtil::JSEscape($dataInputID)?>',
											'newDataInputId': '<?=CUtil::JSEscape($newDataInputID)?>',
											'entitySelectorId': entitySelectorId,
											'serviceUrl': '<?=CUtil::JSEscape('/bitrix/components/bitrix/crm.company.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get())?>',
											'createUrl': '<?=CUtil::JSEscape(CCrmOwnerType::GetEditUrl(CCrmOwnerType::Company, 0, false))?>',
											'actionName': 'SAVE_COMPANY',
											'dialog': <?=CUtil::PhpToJSObject($dialogSettings['COMPANY'])?>,
											'cardViewMode': <?php echo ($cardViewMode ? 'true' : 'false'); ?>,
											'rqLinkedInputId': '<?=CUtil::JSEscape($rqLinkedInputId)?>',
											'bdLinkedInputId': '<?=CUtil::JSEscape($bdLinkedInputId)?>',
											'rqLinkedId': '<?=CUtil::JSEscape($valEntityType === 'company' ? $rqLinkedId : 0)?>',
											'bdLinkedId': '<?=CUtil::JSEscape($valEntityType === 'company' ? $bdLinkedId : 0)?>',
											'skipInitInput': '<?=CUtil::JSEscape($valEntityType !== 'company' ? 'true' : 'false')?>'
										},
										null,
										<?= (($valEntityType === 'company') ? 'BX.CrmEntityInfo.create('.CUtil::PhpToJSObject($entityInfo).')' : 'null') ?>
									);
								}
							);
						</script><?
						}
						break;
					case 'crm_locality_search':
						$params = isset($field['componentParams']) ? $field['componentParams'] : array();
						$searchInputID = "{$arParams['FORM_ID']}_{$field['id']}";
						$dataInputID = "{$arParams['FORM_ID']}_{$params['DATA_INPUT_NAME']}";
						?><input type="text" class="crm-offer-item-inp" id="<?=$searchInputID?>" name="<?=$field["id"]?>"  name="<?=$field["id"]?>" value="<?=htmlspecialcharsbx($val)?>"<?=$params?>/>
						<input type="hidden" id="<?=$dataInputID?>" name="<?=$params['DATA_INPUT_NAME']?>" value="<?=htmlspecialcharsbx($params['DATA_VALUE'])?>"/>
						<script type="text/javascript">
							BX.ready(
								function()
								{
									BX.CrmLocalitySearchField.create(
										"<?=$searchInputID?>",
										{
											localityType: "<?=$params['LOCALITY_TYPE']?>",
											serviceUrl: "<?=$params['SERVICE_URL']?>",
											searchInput: "<?=$searchInputID?>",
											dataInput: "<?=$dataInputID?>"
										}
									);
								}
							);
						</script>
						<?
						break;
					default:
						?><input type="text" class="crm-offer-item-inp" name="<?=$field["id"]?>" value="<?=htmlspecialcharsbx($val)?>"<?=$params?>><?
				endswitch;
			?></div><?
			if ($advancedInfoHTML !== '')
				echo $advancedInfoHTML;
			?></td><!-- "crm-offer-info-right" -->
			<td class="crm-offer-info-right-btn"><?
				if(!$required && !$persistent):
					?><span class="crm-offer-item-del"></span><?
				endif;
				if($mode === 'EDIT'):
					?><span class="crm-offer-item-edit"></span><?
				endif;
				?></td>
			<td class="crm-offer-last-td"></td><?
		endif;
		?></tr><?
		$fieldCount++;
	endforeach;
	unset($field);
	?><tr id="<?=$sectionNodePrefix?>_buttons" style="visibility: hidden;">
		<td class="crm-offer-info-drg-btn" <?= ($enableFieldDrag ? '' : ' style="display: none;"') ?>></td>
		<td class="crm-offer-info-left"></td>
		<td class="crm-offer-info-right">
			<div class="crm-offer-item-link-wrap">
				<? if ($canCreateUserField): ?>
				<span id="<?=$sectionNodePrefix?>_add_field" class="crm-offer-info-link"><?=GetMessage('interface_form_add_btn_add_field')?></span>
				<? endif; ?>
				<? if ($canCreateSection): ?>
				<span id="<?=$sectionNodePrefix?>_add_section" class="crm-offer-info-link"><?=GetMessage('interface_form_add_btn_add_section')?></span>
				<? endif; ?>
				<span id="<?=$sectionNodePrefix?>_restore_field" class="crm-offer-info-link"><?=GetMessage('interface_form_add_btn_restore_field')?></span>
			</div>
		</td>
		<td class="crm-offer-info-right-btn"></td>
		<td class="crm-offer-last-td"></td>
	</tr>
	</tbody></table><!-- "crm-offer-info-table" --><?
endforeach;
unset($arSection);

if ($sipManagerRequired)
{
?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmSipManager.getCurrent().setServiceUrl(
				"CRM_<?=CUtil::JSEscape(CCrmOwnerType::LeadName)?>",
				"/bitrix/components/bitrix/crm.lead.show/ajax.php?<?=bitrix_sessid_get()?>"
			);

			BX.CrmSipManager.getCurrent().setServiceUrl(
				"CRM_<?=CUtil::JSEscape(CCrmOwnerType::ContactName)?>",
				"/bitrix/components/bitrix/crm.contact.show/ajax.php?<?=bitrix_sessid_get()?>"
			);

			BX.CrmSipManager.getCurrent().setServiceUrl(
				"CRM_<?=CUtil::JSEscape(CCrmOwnerType::CompanyName)?>",
				"/bitrix/components/bitrix/crm.company.show/ajax.php?<?=bitrix_sessid_get()?>"
			);

			if(typeof(BX.CrmSipManager.messages) === 'undefined')
			{
				BX.CrmSipManager.messages =
				{
					"unknownRecipient": "<?= GetMessageJS('CRM_SIP_MGR_UNKNOWN_RECIPIENT')?>",
					"enableCallRecording": "<?= GetMessageJS('CRM_SIP_MGR_ENABLE_CALL_RECORDING')?>",
					"makeCall": "<?= GetMessageJS('CRM_SIP_MGR_MAKE_CALL')?>"
				};
			}
		}
	);
</script><?
}

$arFieldSets = isset($arParams['FIELD_SETS']) ? $arParams['FIELD_SETS'] : array();
if(!empty($arFieldSets)):
	foreach($arFieldSets as &$arFieldSet):
		$html = isset($arFieldSet['HTML']) ? $arFieldSet['HTML'] : '';
		if($html === '')
			continue;
		?><div class="bx-crm-view-fieldset">
		<h2 class="bx-crm-view-fieldset-title"><? if (isset($arFieldSet['REQUIRED']) && $arFieldSet['REQUIRED'] === true): ?><span class="required">*</span><? endif; ?><?=isset($arFieldSet['NAME']) ? htmlspecialcharsbx($arFieldSet['NAME']) : ''?></h2>
			<div class="bx-crm-view-fieldset-content">
				<table class="bx-crm-view-fieldset-content-table">
					<tbody>
					<tr>
						<td class="bx-field-value"><?=$html?></td>
					</tr>
				</tbody>
			</table>
		</div>
		</div><?
	endforeach;
	unset($arFieldSet);
endif;
?></div><!-- "crm-offer-main-wrap" --><?

if(isset($arParams['~BUTTONS'])):
	if($arParams['~BUTTONS']['standard_buttons'] !== false):
		$buttonsTitles = array(
			'saveAndView' => array(
				'value' => GetMessage('interface_form_save_and_view'),
				'title' => GetMessage('interface_form_save_and_view_title')
			),
			'saveAndAdd' => array(
				'value' => GetMessage('interface_form_save_and_add'),
				'title' => GetMessage('interface_form_save_and_add_title')
			),
			'apply' => array(
				'value' => GetMessage('interface_form_apply'),
				'title' => GetMessage('interface_form_apply_title')
			),
			'cancel' => array(
				'value' => GetMessage('interface_form_cancel'),
				'title' => GetMessage('interface_form_cancel_title')
			)
		);
		if (is_array($arParams['~BUTTONS']['standard_buttons_titles']))
		{
			$customTitles = array_replace_recursive($buttonsTitles, $arParams['~BUTTONS']['standard_buttons_titles']);
			if (is_array($customTitles))
				$buttonsTitles = $customTitles;
			unset($customTitles);
		}
		?><div class="webform-buttons ">
			<span class="webform-button webform-button-create">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="submit" name="saveAndView" id="<?=$arParams["FORM_ID"]?>_saveAndView" value="<?=htmlspecialcharsbx($buttonsTitles['saveAndView']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['saveAndView']['title'])?>" />
				<span class="webform-button-right"></span>
			</span><?
		if(isset($arParams['IS_NEW']) && $arParams['IS_NEW'] === true):
			?><span class="webform-button">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="submit" name="saveAndAdd" id="<?=$arParams["FORM_ID"]?>_saveAndAdd" value="<?=htmlspecialcharsbx($buttonsTitles['saveAndAdd']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['saveAndAdd']['title'])?>" />
				<span class="webform-button-right"></span>
			</span><?
		else:
			?><span class="webform-button">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="submit" name="apply" id="<?=$arParams["FORM_ID"]?>_apply" value="<?=htmlspecialcharsbx($buttonsTitles['apply']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['apply']['title'])?>" />
				<span class="webform-button-right"></span>
			</span><?
		endif;
		if(isset($arParams['~BUTTONS']['back_url']) && $arParams['~BUTTONS']['back_url'] !== ''):
			?><span class="webform-button">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="button" name="cancel" onclick="window.location='<?=CUtil::JSEscape($arParams['~BUTTONS']['back_url'])?>'" value="<?= htmlspecialcharsbx($buttonsTitles['cancel']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['cancel']['title'])?>" />
				<span class="webform-button-right"></span>
			</span><?
		endif;
		?></div><?
	elseif($arParams['~BUTTONS']['wizard_buttons'] !== false):
		$buttonsTitles = array(
			'continue' => array(
				'value' => GetMessage('interface_form_continue'),
				'title' => GetMessage('interface_form_continue_title')
			),
			'cancel' => array(
				'value' => GetMessage('interface_form_cancel'),
				'title' => GetMessage('interface_form_cancel_title')
			)
		);
		?><div class="webform-buttons ">
			<span class="webform-button webform-button-create">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="submit" name="continue" id="<?=$arParams["FORM_ID"]?>_continue" value="<?=htmlspecialcharsbx($buttonsTitles['continue']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['continue']['title'])?>" />
				<span class="webform-button-right"></span>
			</span><?
			if(isset($arParams['~BUTTONS']['back_url']) && $arParams['~BUTTONS']['back_url'] !== ''):
				?><span class="webform-button">
					<span class="webform-button-left"></span>
					<input class="webform-button-text" type="button" name="cancel" onclick="window.location='<?=CUtil::JSEscape($arParams['~BUTTONS']['back_url'])?>'" value="<?= htmlspecialcharsbx($buttonsTitles['cancel']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['cancel']['title'])?>" />
					<span class="webform-button-right"></span>
				</span><?
			endif;
		?></div><?
	elseif($arParams['~BUTTONS']['dialog_buttons'] !== false):
		$buttonsTitles = array(
			'save' => array(
				'value' => GetMessage('interface_form_save'),
				'title' => GetMessage('interface_form_save_title')
			),
			'cancel' => array(
				'value' => GetMessage('interface_form_cancel'),
				'title' => GetMessage('interface_form_cancel_title')
			)
		);
		?><div class="webform-buttons ">
			<span class="webform-button webform-button-create">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="submit" name="save" id="<?=$arParams["FORM_ID"]?>_save" value="<?=htmlspecialcharsbx($buttonsTitles['save']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['save']['title'])?>" />
				<span class="webform-button-right"></span>
			</span>
			<span class="webform-button">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="submit" name="cancel" id="<?=$arParams["FORM_ID"]?>_cancel" value="<?= htmlspecialcharsbx($buttonsTitles['cancel']['value'])?>" title="<?= htmlspecialcharsbx($buttonsTitles['cancel']['title'])?>" />
				<span class="webform-button-right"></span>
			</span>
		</div><?
	endif;
	if(isset($arParams['~BUTTONS']['custom_html'])):
		echo $arParams['~BUTTONS']['custom_html'];
	endif;
endif;

if($arParams['SHOW_FORM_TAG']):
	?></form><?
endif;

if($GLOBALS['USER']->IsAuthorized() && $arParams["SHOW_SETTINGS"] == true):?>
<div style="display:none">

	<div id="form_settings_<?=$arParams["FORM_ID"]?>">
		<table width="100%">
			<tr class="section">
				<td colspan="2"><?echo GetMessage("interface_form_tabs")?></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<table>
						<tr>
							<td style="background-image:none" nowrap>
								<select style="min-width:150px;" name="tabs" size="10" ondblclick="this.form.tab_edit_btn.onclick()" onchange="bxForm_<?=$arParams["FORM_ID"]?>.OnSettingsChangeTab()">
								</select>
							</td>
							<td style="background-image:none">
								<div style="margin-bottom:5px"><input type="button" name="tab_up_btn" value="<?echo GetMessage("intarface_form_up")?>" title="<?echo GetMessage("intarface_form_up_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabMoveUp()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_down_btn" value="<?echo GetMessage("intarface_form_up_down")?>" title="<?echo GetMessage("intarface_form_down_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabMoveDown()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_add_btn" value="<?echo GetMessage("intarface_form_add")?>" title="<?echo GetMessage("intarface_form_add_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabAdd()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_edit_btn" value="<?echo GetMessage("intarface_form_edit")?>" title="<?echo GetMessage("intarface_form_edit_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabEdit()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_del_btn" value="<?echo GetMessage("intarface_form_del")?>" title="<?echo GetMessage("intarface_form_del_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabDelete()"></div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr class="section">
				<td colspan="2"><?echo GetMessage("intarface_form_fields")?></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<table>
						<tr>
							<td style="background-image:none" nowrap>
								<div style="margin-bottom:5px"><?echo GetMessage("intarface_form_fields_available")?></div>
								<select style="min-width:150px;" name="all_fields" multiple size="12" ondblclick="this.form.add_btn.onclick()" onchange="bxForm_<?=$arParams["FORM_ID"]?>.ProcessButtons()">
								</select>
							</td>
							<td style="background-image:none">
								<div style="margin-bottom:5px"><input type="button" name="add_btn" value="&gt;" title="<?echo GetMessage("intarface_form_add_field")?>" style="width:30px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsAdd()"></div>
								<div style="margin-bottom:5px"><input type="button" name="del_btn" value="&lt;" title="<?echo GetMessage("intarface_form_del_field")?>" style="width:30px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsDelete()"></div>
							</td>
							<td style="background-image:none" nowrap>
								<div style="margin-bottom:5px"><?echo GetMessage("intarface_form_fields_on_tab")?></div>
								<select style="min-width:150px;" name="fields" multiple size="12" ondblclick="this.form.del_btn.onclick()" onchange="bxForm_<?=$arParams["FORM_ID"]?>.ProcessButtons()">
								</select>
							</td>
							<td style="background-image:none">
								<div style="margin-bottom:5px"><input type="button" name="up_btn" value="<?echo GetMessage("intarface_form_up")?>" title="<?echo GetMessage("intarface_form_up_title")?>" style="width:80px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsMoveUp()"></div>
								<div style="margin-bottom:5px"><input type="button" name="down_btn" value="<?echo GetMessage("intarface_form_up_down")?>" title="<?echo GetMessage("intarface_form_down_title")?>" style="width:80px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsMoveDown()"></div>
								<div style="margin-bottom:5px"><input type="button" name="field_add_btn" value="<?echo GetMessage("intarface_form_add")?>" title="<?echo GetMessage("intarface_form_add_sect")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldAdd()"></div>
								<div style="margin-bottom:5px"><input type="button" name="field_edit_btn" value="<?echo GetMessage("intarface_form_edit")?>" title="<?echo GetMessage("intarface_form_edit_field")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldEdit()"></div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>

</div><?
endif; //$GLOBALS['USER']->IsAuthorized()

$variables = array(
	"mess"=>array(
		"collapseTabs"=>GetMessage("interface_form_close_all"),
		"expandTabs"=>GetMessage("interface_form_show_all"),
		"settingsTitle"=>GetMessage("intarface_form_settings"),
		"settingsSave"=>GetMessage("interface_form_save"),
		"tabSettingsTitle"=>GetMessage("intarface_form_tab"),
		"tabSettingsSave"=>"OK",
		"tabSettingsName"=>GetMessage("intarface_form_tab_name"),
		"tabSettingsCaption"=>GetMessage("intarface_form_tab_title"),
		"fieldSettingsTitle"=>GetMessage("intarface_form_field"),
		"fieldSettingsName"=>GetMessage("intarface_form_field_name"),
		"sectSettingsTitle"=>GetMessage("intarface_form_sect"),
		"sectSettingsName"=>GetMessage("intarface_form_sect_name"),
	),
	"ajax"=>array(
		"AJAX_ID"=>$arParams["AJAX_ID"],
		"AJAX_OPTION_SHADOW"=>($arParams["AJAX_OPTION_SHADOW"] == "Y"),
	),
	"settingWndSize"=>CUtil::GetPopupSize("InterfaceFormSettingWnd"),
	"tabSettingWndSize"=>CUtil::GetPopupSize("InterfaceFormTabSettingWnd", array('width'=>400, 'height'=>200)),
	"fieldSettingWndSize"=>CUtil::GetPopupSize("InterfaceFormFieldSettingWnd", array('width'=>400, 'height'=>150)),
	"component_path"=>(isset($arParams["CUSTOM_FORM_SETTINGS_COMPONENT_PATH"])
		&& !empty($arParams["CUSTOM_FORM_SETTINGS_COMPONENT_PATH"])) ?
		strval($arParams["CUSTOM_FORM_SETTINGS_COMPONENT_PATH"]) : $component->GetRelativePath(),
	"template_path"=>$this->GetFolder(),
	"sessid"=>bitrix_sessid(),
	"current_url"=>$APPLICATION->GetCurPageParam("", array("bxajaxid", "AJAX_CALL")),
	"GRID_ID"=>$arParams["THEME_GRID_ID"],
);

?><script type="text/javascript">
var formSettingsDialog<?=$arParams["FORM_ID"]?>;
bxForm_<?=$arParams["FORM_ID"]?> = new BxCrmInterfaceForm('<?=$arParams["FORM_ID"]?>', <?=CUtil::PhpToJsObject(array_keys($arResult["TABS"]))?>);
bxForm_<?=$arParams["FORM_ID"]?>.vars = <?=CUtil::PhpToJsObject($variables)?>;<?
if($arParams["SHOW_SETTINGS"] == true):
	?>bxForm_<?=$arParams["FORM_ID"]?>.oTabsMeta = <?=CUtil::PhpToJsObject($arResult["TABS_META"])?>;
bxForm_<?=$arParams["FORM_ID"]?>.oFields = <?=CUtil::PhpToJsObject($arResult["AVAILABLE_FIELDS"])?>;<?
endif;

if($arResult["OPTIONS"]["expand_tabs"] == "Y"):
	?>BX.ready(function(){bxForm_<?=$arParams["FORM_ID"]?>.ToggleTabs(true);});<?
endif;
?>bxForm_<?=$arParams["FORM_ID"]?>.Initialize();
bxForm_<?=$arParams["FORM_ID"]?>.EnableSigleSubmit(true);
</script><?

?></div><!-- bx-interface-form --><?
?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmFormSectionSetting.messages =
			{
				deleteButton: "<?=CUtil::JSEscape(GetMessage('intarface_form_del'))?>",
				createTextFiledMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_string_field_menu_item'))?>",
				createDoubleFiledMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_double_field_menu_item'))?>",
				createBooleanFiledMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_boolean_field_menu_item'))?>",
				createDatetimeFiledMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_datetime_field_menu_item'))?>",
				createSectionMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_add_section_menu_item'))?>",
				sectionTitlePlaceHolder: "<?=CUtil::JSEscape(GetMessage('interface_form_section_ttl_placeholder'))?>",
				sectionDeleteDlgTitle: "<?=CUtil::JSEscape(GetMessage('interface_form_section_delete_dlg_title'))?>",
				sectionDeleteDlgContent: "<?=CUtil::JSEscape(GetMessage('interface_form_section_delete_dlg_content'))?>",
				editMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_field_field_edit_menu_item'))?>",
				deleteMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_field_field_hide_menu_item'))?>"
			};

			BX.CrmFormFieldSetting.messages =
			{
				saveButton: "<?=CUtil::JSEscape(GetMessage('interface_form_save'))?>",
				cancelButton: "<?=CUtil::JSEscape(GetMessage('interface_form_cancel'))?>",
				inShortListOptionTitle: "<?=CUtil::JSEscape(GetMessage('interface_form_in_short_list_option_title'))?>",
				deleteButton: "<?=CUtil::JSEscape(GetMessage('interface_form_hide'))?>",
				fieldNamePlaceHolder: "<?=CUtil::JSEscape(GetMessage('interface_form_field_name_placeholder'))?>",
				fieldDeleteDlgTitle: "<?=CUtil::JSEscape(GetMessage('interface_form_field_hide_dlg_title'))?>",
				fieldDeleteDlgContent: "<?=CUtil::JSEscape(GetMessage('interface_form_field_hide_dlg_content'))?>",
				editMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_field_field_edit_menu_item'))?>",
				deleteMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_field_field_hide_menu_item'))?>"
			};

			BX.CrmFormFieldRenderer.messages =
			{
				addSectionButton: "<?=CUtil::JSEscape(GetMessage('interface_form_add_btn_add_section'))?>",
				addFieldButton: "<?=CUtil::JSEscape(GetMessage('interface_form_add_btn_add_field'))?>",
				restoreFieldButton: "<?=CUtil::JSEscape(GetMessage('interface_form_add_btn_restore_field'))?>"
			};

			BX.CrmFormSettingManager.messages =
			{
				newFieldName: "<?=CUtil::JSEscape(GetMessage('interface_form_new_field_name'))?>",
				newSectionName: "<?=CUtil::JSEscape(GetMessage('interface_form_new_section_name'))?>",
				resetMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_reset_menu_item'))?>",
				saveForAllMenuItem: "<?=CUtil::JSEscape(GetMessage('interface_form_save_for_all_menu_item'))?>",
				sectionHasRequiredFields: "<?=CUtil::JSEscape(GetMessage('interface_form_section_has_required_fields'))?>",
				saved: "<?=CUtil::JSEscape(GetMessage('interface_form_settings_saved'))?>",
				undo: "<?=CUtil::JSEscape(GetMessage('interface_form_settings_undo_change'))?>"
			};

			var isSettingsApplied = <?=$arResult['OPTIONS']['settings_disabled'] !== 'Y' ? 'true' : 'false'?>;

			BX.CrmEditFormManager.create(
				"<?=$formIDLower?>",
				{
					formId: "<?=$arParams['FORM_ID']?>",
					form: bxForm_<?=$arParams["FORM_ID"]?>,
					mode: <?=strtoupper($arParams["MODE"]) === 'VIEW' ? 'BX.CrmFormMode.view' : 'BX.CrmFormMode.edit'?>,
					prefix: "<?=CUtil::JSEscape($prefix)?>",
					sectionWrapperId: "<?=$sectionWrapperID?>",
					undoContainerId: "<?=$undoContainerID?>",
					tabId: "tab_1",
					metaData: window["bxForm_<?=$arParams['FORM_ID']?>"]["oTabsMeta"],
					hiddenMetaData: isSettingsApplied ? window["bxForm_<?=$arParams['FORM_ID']?>"]["oFields"] : [],
					isSettingsApplied: isSettingsApplied,
					canCreateUserField: <?=($canCreateUserField ? 'true' : 'false')?>,
					canCreateSection: <?=($canCreateSection ? 'true' : 'false')?>,
					canSaveSettingsForAll: <?=CCrmAuthorizationHelper::CanEditOtherSettings() ? 'true' : 'false'?>,
					userFieldEntityId: "<?=isset($arParams['USER_FIELD_ENTITY_ID']) ? $arParams['USER_FIELD_ENTITY_ID'] : ''?>",
					userFieldServiceUrl: "<?=isset($arParams['USER_FIELD_SERVICE_URL']) ? $arParams['USER_FIELD_SERVICE_URL'] : ''?>",
					enableInShortListOption: <?= ((isset($arParams['ENABLE_IN_SHORT_LIST_OPTION']) && $arParams['ENABLE_IN_SHORT_LIST_OPTION'] === 'Y') ? 'true' : 'false') ?>,
					isModal: <?= ((isset($arParams['IS_MODAL']) && $arParams['IS_MODAL'] === 'Y') ? 'true' : 'false') ?>,
					dragPriority: <?= (isset($settings['DRAG_PRIORITY']) ? (int)$settings['DRAG_PRIORITY'] : -1) ?>,
					enableFieldDrag: <?=($enableFieldDrag ? 'true' : 'false')?>,
					enableSectionDrag: <?=($enableSectionDrag ? 'true' : 'false')?>,
					serverTime: "<?=time() + CTimeZone::GetOffset()?>"
				}
			);
		}
	);
</script><?
if(!empty($arUserSearchFields)):
?><script type="text/javascript">
	BX.ready(
		function()
		{<?
			foreach($arUserSearchFields as &$arField):
				$arUserData = array();
				if(isset($arField['USER'])):
					$nameFormat = isset($arField['NAME_TEMPLATE']) ? $arField['NAME_TEMPLATE'] : '';
					if($nameFormat === '')
						$nameFormat = CSite::GetNameFormat(false);
					$arUserData['id'] = $arField['USER']['ID'];
					$arUserData['name'] = CUser::FormatName($nameFormat, $arField['USER'], true, false);
				endif;
			?>BX.CrmUserSearchField.create(
				'<?=$arField['NAME']?>',
				document.getElementsByName('<?=$arField['SEARCH_INPUT_NAME']?>')[0],
				document.getElementsByName('<?=$arField['INPUT_NAME']?>')[0],
				'<?=$arField['NAME']?>',
				<?= CUtil::PhpToJSObject($arUserData)?>
			);<?
			endforeach;
			unset($arField);
		?>}
	);
</script><?
endif;
