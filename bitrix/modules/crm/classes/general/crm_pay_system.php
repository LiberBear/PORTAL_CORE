<?

IncludeModuleLangFile(__FILE__);
class CCrmPaySystem
{
	private static $arActFiles = array();
	private static $arCrmCompatibleActs = array('bill', 'bill_ua', 'bill_en', 'bill_de', 'bill_la', 'quote');
	private static $paySystems = null;

	public static function LocalGetPSActionParams($fileName)
	{
		$arPSCorrespondence = array();

		if (file_exists($fileName) && is_file($fileName))
			include($fileName);

		if (isset($data))
			$arPSCorrespondence = self::convertOldToNew($data);
		return $arPSCorrespondence;
	}

	private static function convertOldToNew($data)
	{
		foreach ($data['CODES'] as &$code)
		{
			if (isset($code['DESCRIPTION']))
			{
				$code['DESCR'] = $code['DESCRIPTION'];
				unset($code['DESCRIPTION']);
			}
			else
			{
				$code['DESCR'] = '';
			}

			if (isset($code['INPUT']))
			{
				if ($code['INPUT']['TYPE'] == 'ENUM')
				{
					$code['TYPE'] = 'SELECT';

					foreach ($code['INPUT']['OPTIONS'] as $key => $value)
						$code['VALUE'][$key] = array('NAME' => $value);
				}
				else
				{
					$code['TYPE'] = $code['INPUT']['TYPE'];
				}
				unset($code['INPUT']);
			}

			if (isset($code['DEFAULT']))
			{
				$code['VALUE'] = $code['DEFAULT']['PROVIDER_VALUE'];
				if ($code['DEFAULT']['PROVIDER_KEY'] == 'VALUE')
					$type = '';
				elseif ($code['DEFAULT']['PROVIDER_KEY'] == 'PAYMENT')
					$type = 'ORDER';
				else
					$type = $code['DEFAULT']['PROVIDER_KEY'];

				$code['TYPE'] = $type;
				unset($code['DEFAULT']);
			}
			elseif (!isset($code['TYPE']))
			{
				$code['TYPE'] = '';
				$code['VALUE'] = '';
			}
		}
		unset($code);

		return $data['CODES'];
	}

	private static function LocalGetPSActionDescr($fileName)
	{
		$data = array();
		$psTitle = "";
		$psDescription = "";

		if (file_exists($fileName) && is_file($fileName))
			include($fileName);

		if ($data)
			return array($data['NAME'], $psDescription);

		return array($psTitle, $psDescription);
	}

	public static function getActions()
	{
		if (!CModule::IncludeModule('sale'))
			return array();

		if(!empty(self::$arActFiles))
			return self::$arActFiles;

		$arUserPSActions = array();
		$arSystemPSActions = array();

		$path2SystemPSFiles = "/bitrix/modules/sale/payment/";
		$path2UserPSFiles = COption::GetOptionString("sale", "path2user_ps_files", BX_PERSONAL_ROOT."/php_interface/include/sale_payment/");
		CheckDirPath($_SERVER["DOCUMENT_ROOT"].$path2UserPSFiles);

		$handle = @opendir($_SERVER["DOCUMENT_ROOT"].$path2UserPSFiles);
		if ($handle)
		{
			while (false !== ($dir = readdir($handle)))
			{
				if ($dir == "." || $dir == ".." )
					continue;

				$title = "";
				$description = "";

				if (is_dir($_SERVER["DOCUMENT_ROOT"].$path2UserPSFiles.$dir))
				{
					$newFormat = "Y";
					list($title, $description) = self::LocalGetPSActionDescr($_SERVER["DOCUMENT_ROOT"].$path2UserPSFiles.$dir."/.description.php");
					if (strlen($title) <= 0)
						$title = $dir;
					else
						$title .= " (".$dir.")";
				}

				if(strlen($title) > 0)
				{
					$arUserPSActions[] = array(
							"ID" => $dir,
							"PATH" => $path2UserPSFiles.$dir,
							"TITLE" => $title,
							"DESCRIPTION" => $description,
							"NEW_FORMAT" => $newFormat
						);
				}
			}
			@closedir($handle);
		}

		$handle = @opendir($_SERVER["DOCUMENT_ROOT"].$path2SystemPSFiles);
		if ($handle)
		{
			while (false !== ($dir = readdir($handle)))
			{
				if ($dir == "." || $dir == ".." || !in_array($dir, self::$arCrmCompatibleActs))
					continue;

				if (is_dir($_SERVER["DOCUMENT_ROOT"].$path2SystemPSFiles.$dir))
				{
					$newFormat = "Y";
					list($title, $description) = self::LocalGetPSActionDescr($_SERVER["DOCUMENT_ROOT"].$path2SystemPSFiles.$dir."/.description.php");
					if (strlen($title) <= 0)
						$title = $dir;
					else
						$title .= " (".$dir.")";
				}

				$arSystemPSActions[] = array(
						"ID" => $dir,
						"PATH" => $path2SystemPSFiles.$dir,
						"TITLE" => $title,
						"DESCRIPTION" => $description,
						"NEW_FORMAT" => $newFormat
					);
			}
			@closedir($handle);
		}

		foreach($arUserPSActions as $val)
			self::$arActFiles[$val['ID']] = $val;

		foreach($arSystemPSActions as $val)
			self::$arActFiles[$val['ID']] = $val;

		sortByColumn(self::$arActFiles, array("ID" => SORT_ASC));

		return self::$arActFiles;
	}

	public static function getActionsList()
	{
		$arReturn = array();
		$arAFF = self::getActions();

		foreach ($arAFF as $id => $arAction)
			$arReturn[$id] = $arAction['TITLE'];

		return $arReturn;
	}

	public static function getActionPath($actionId)
	{
		$arActions = self::getActions();

		if(isset($arActions[$actionId]['PATH']))
			return $arActions[$actionId]['PATH'];

		return false;
	}

	public static function getActionSelector($idCorr, $arCorr)
	{
		if ($arCorr['TYPE'] == 'SELECT' || $arCorr['TYPE'] == 'FILE')
		{
			$res  = '<select name="TYPE_'.$idCorr.'" id="TYPE_'.$idCorr.'" style="display: none;">';
			$res .= '<option selected value="'.$arCorr["TYPE"].'"></option>';
			$res .= '</select>';
		}
		else
		{
			$bSimple = self::isFormSimple();

			$res = '<select name="TYPE_'.$idCorr.'" id="TYPE_'.$idCorr.'"'.($bSimple ? ' style="display: none;"' : '').'>\n';
			$res .= '<option value=""'.($arCorr['TYPE'] == '' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_OTHER").'</option>\n';
			//$res .= '<option value="USER"'.($arCorr['TYPE'] == 'USER' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_USER").'</option>\n';
			$res .= '<option value="ORDER"'.($arCorr['TYPE'] == 'ORDER' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_ORDER").'</option>\n';
			$res .= '<option value="PROPERTY"'.($arCorr['TYPE'] == 'PROPERTY' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_PROPERTY").'</option>\n';
			$res .= '<option value="REQUISITE"'.($arCorr['TYPE'] == 'REQUISITE' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_REQUISITE").'</option>\n';
			$res .= '<option value="BANK_DETAIL"'.($arCorr['TYPE'] == 'BANK_DETAIL' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_BANK_DETAIL").'</option>\n';
			$res .= '<option value="CRM_COMPANY"'.($arCorr['TYPE'] == 'CRM_COMPANY' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_CRM_COMPANY").'</option>\n';
			$res .= '<option value="CRM_CONTACT"'.($arCorr['TYPE'] == 'CRM_CONTACT' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_CRM_CONTACT").'</option>\n';
			$res .= '</select>';
		}

		return $res;
	}

	public static function getOrderPropsList($persTypeId = false)
	{
		static $arProps = array();

		if(empty($arProps) && CModule::IncludeModule('sale'))
		{
			$arPersTypeIds = self::getPersonTypeIDs();

			$dbOrderProps = CSaleOrderProps::GetList(
					array("SORT" => "ASC", "NAME" => "ASC"),
					array("PERSON_TYPE_ID" => $arPersTypeIds),
					false,
					false,
					array("ID", "CODE", "NAME", "TYPE", "SORT", "PERSON_TYPE_ID")
				);

			while ($arOrderProps = $dbOrderProps->Fetch())
			{
				$idx = strlen($arOrderProps["CODE"])>0 ? $arOrderProps["CODE"] : $arOrderProps["ID"];
				$arProps[$arOrderProps["PERSON_TYPE_ID"]][$idx] = $arOrderProps["NAME"];

				if ($arOrderProps["TYPE"] == "LOCATION")
				{
					$idx = strlen($arOrderProps["CODE"])>0 ? $arOrderProps["CODE"]."_COUNTRY" : $arOrderProps["ID"]."_COUNTRY";
					$arProps[$arOrderProps["PERSON_TYPE_ID"]][$idx] = $arOrderProps["NAME"]." (".GetMessage("CRM_PS_JCOUNTRY").")";

					$idx = strlen($arOrderProps["CODE"])>0 ? $arOrderProps["CODE"]."_CITY" : $arOrderProps["ID"]."_CITY";
					$arProps[$arOrderProps["PERSON_TYPE_ID"]][$idx] = $arOrderProps["NAME"]." (".GetMessage("CRM_PS_JCITY").")";
				}
			}
		}

		if($persTypeId && isset($arProps[$persTypeId]))
			$arReturn = $arProps[$persTypeId];
		elseif($persTypeId && !isset($arProps[$persTypeId]))
			$arReturn = false;
		else
			$arReturn = $arProps;

		return $arReturn;
	}

	public static function getOrderFieldsList()
	{
		return $arProps = array(
					"ID" => GetMessage("CRM_PS_ORDER_ID"),
					"ORDER_TOPIC" => GetMessage("CRM_FIELD_ORDER_TOPIC"),
					"DATE_INSERT" => GetMessage("CRM_PS_ORDER_DATETIME"),
					"DATE_INSERT_DATE" => GetMessage("CRM_PS_ORDER_DATE"),
					"DATE_BILL" => GetMessage("CRM_PS_ORDER_DATE_BILL"),
					"DATE_BILL_DATE" => GetMessage("CRM_PS_ORDER_DATE_BILL_DATE"),
					"DATE_PAY_BEFORE" => GetMessage("CRM_PS_ORDER_DATE_PAY_BEFORE"),
					"SHOULD_PAY" => GetMessage("CRM_PS_ORDER_PRICE"),
					"CURRENCY" => GetMessage("CRM_PS_ORDER_CURRENCY"),
					"PRICE" => GetMessage("CRM_PS_ORDER_SUM"),
					//"LID" => GetMessage("CRM_PS_ORDER_SITE"),
					"PRICE_DELIVERY" => GetMessage("CRM_PS_ORDER_PRICE_DELIV"),
					"DISCOUNT_VALUE" => GetMessage("CRM_PS_ORDER_DESCOUNT"),
					"USER_ID" => GetMessage("CRM_PS_ORDER_USER_ID"),
					"PAY_SYSTEM_ID" => GetMessage("CRM_PS_ORDER_PS"),
					"DELIVERY_ID" => GetMessage("CRM_PS_ORDER_DELIV"),
					"TAX_VALUE" => GetMessage("CRM_PS_ORDER_TAX"),
					"USER_DESCRIPTION" => GetMessage("CRM_PS_ORDER_USER_DESCRIPTION")
				);
	}

	public static function getUserPropsList()
	{
		return $arProps = array(
					"ID" => GetMessage("CRM_PS_USER_ID"),
					"LOGIN" => GetMessage("CRM_PS_USER_LOGIN"),
					"NAME" => GetMessage("CRM_PS_USER_NAME"),
					"SECOND_NAME" => GetMessage("CRM_PS_USER_SECOND_NAME"),
					"LAST_NAME" => GetMessage("CRM_PS_USER_LAST_NAME"),
					"EMAIL" => "EMail",
					//"LID" => GetMessage("CRM_PS_USER_SITE"),
					"PERSONAL_PROFESSION" => GetMessage("CRM_PS_USER_PROF"),
					"PERSONAL_WWW" => GetMessage("CRM_PS_USER_WEB"),
					"PERSONAL_ICQ" => GetMessage("CRM_PS_USER_ICQ"),
					"PERSONAL_GENDER" => GetMessage("CRM_PS_USER_SEX"),
					"PERSONAL_FAX" => GetMessage("CRM_PS_USER_FAX"),
					"PERSONAL_MOBILE" => GetMessage("CRM_PS_USER_PHONE"),
					"PERSONAL_STREET" => GetMessage("CRM_PS_USER_ADDRESS"),
					"PERSONAL_MAILBOX" => GetMessage("CRM_PS_USER_POST"),
					"PERSONAL_CITY" => GetMessage("CRM_PS_USER_CITY"),
					"PERSONAL_STATE" => GetMessage("CRM_PS_USER_STATE"),
					"PERSONAL_ZIP" => GetMessage("CRM_PS_USER_ZIP"),
					"PERSONAL_COUNTRY" => GetMessage("CRM_PS_USER_COUNTRY"),
					"WORK_COMPANY" => GetMessage("CRM_PS_USER_COMPANY"),
					"WORK_DEPARTMENT" => GetMessage("CRM_PS_USER_DEPT"),
					"WORK_POSITION" => GetMessage("CRM_PS_USER_DOL"),
					"WORK_WWW" => GetMessage("CRM_PS_USER_COM_WEB"),
					"WORK_PHONE" => GetMessage("CRM_PS_USER_COM_PHONE"),
					"WORK_FAX" => GetMessage("CRM_PS_USER_COM_FAX"),
					"WORK_STREET" => GetMessage("CRM_PS_USER_COM_ADDRESS"),
					"WORK_MAILBOX" => GetMessage("CRM_PS_USER_COM_POST"),
					"WORK_CITY" => GetMessage("CRM_PS_USER_COM_CITY"),
					"WORK_STATE" => GetMessage("CRM_PS_USER_COM_STATE"),
					"WORK_ZIP" => GetMessage("CRM_PS_USER_COM_ZIP"),
					"WORK_COUNTRY" => GetMessage("CRM_PS_USER_COM_COUNTRY")
		);
	}

	public static function getSelectPropsList($values)
	{
		$arProps = array();

		foreach ($values as $k => $value)
		{
			$arProps[$k] = $value['NAME'];
		}

		return $arProps;
	}

	public static function getActionValueSelector(
		$idCorr, $arCorr, $persTypeId, $actionFileName = '', $userFields = null,
		$requisiteFields = null, $bankDetailFields = null, $companyFields = null, $contactFields = null
	)
	{
		if ($arCorr['TYPE'] == 'FILE')
		{
			$res = '<input type="file" name="VALUE1_'.$idCorr.'" id="VALUE1_'.$idCorr.'" size="40">';

			if ($arCorr['VALUE'])
			{
				$res .= '<span><br>' . $arCorr['VALUE'];
				$res .= '<br><input type="checkbox" name="' . $idCorr . '_del" value="Y" id="' . $idCorr . '_del" >';
				$res .= '<label for="' . $idCorr . '_del">' . GetMessage("CRM_PS_DEL_FILE") . '</label></span>';
			}
		}
		else
		{
			$res = '<select name="VALUE1_'.$idCorr.'" id="VALUE1_'.$idCorr.'"'.($arCorr['TYPE'] == '' ? ' style="display: none;"' : '').'>';

			$arProps = array();

			if($arCorr['TYPE'] == 'USER')
			{
				$arProps = self::getUserPropsList();
			}
			if($arCorr['TYPE'] == 'ORDER')
			{
				$arProps = self::getOrderFieldsList();
			}
			elseif($arCorr['TYPE'] == 'PROPERTY')
			{
				$arProps = self::getOrderPropsList($persTypeId);

				if( is_array($userFields)
					&& is_string($actionFileName)
					&& preg_match('/^([a-z]+)(?:_([a-z]+))?$/i', $actionFileName, $matches) === 1
					&& isset($userFields[$matches[1]]))
				{
					$arProps = array_merge($arProps, $userFields[$matches[1]]);
				}
			}
			elseif($arCorr['TYPE'] === 'REQUISITE' || $arCorr['TYPE'] === 'BANK_DETAIL')
			{
				$items = array();
				if ($arCorr['TYPE'] == 'REQUISITE')
					$items = $requisiteFields;
				else if ($arCorr['TYPE'] == 'BANK_DETAIL')
					$items = $bankDetailFields;

				if(!empty($items))
				{
					$groupStart = false;
					foreach ($items as $itemInfo)
					{
						if (isset($itemInfo['type']) && $itemInfo['type'] === 'group')
						{
							if ($groupStart)
								$res .= '</optgroup>'.PHP_EOL;
							$res .= '<optgroup label="'.htmlspecialcharsbx($itemInfo['title']).'">'.PHP_EOL;
							$groupStart = true;
						}
						else
						{
							$id = htmlspecialcharsbx($itemInfo['id']);
							$title = htmlspecialcharsbx($itemInfo['title']);
							$res .= '<option value="'.$id.'"'.($arCorr['VALUE'] == $itemInfo['id'] ? ' selected' : '').'>'.$title.'</option>'.PHP_EOL;
						}
					}
					if ($groupStart)
						$res .= '</optgroup>'.PHP_EOL;
					unset($groupStart, $id, $title);
				}
			}
			elseif($arCorr['TYPE'] == 'CRM_COMPANY')
			{
				if(is_array($companyFields))
					$arProps = $companyFields;
			}
			elseif($arCorr['TYPE'] == 'CRM_CONTACT')
			{
				if(is_array($contactFields))
					$arProps = $contactFields;
			}
			elseif ($arCorr['TYPE'] == 'SELECT')
			{
				$arProps = self::getSelectPropsList($arCorr['OPTIONS']);
			}

			if(!empty($arProps))
				foreach ($arProps as $id => $propName)
					$res .= '<option value="'.$id.'"'.($arCorr['VALUE'] == $id ? ' selected' : '').'>'.$propName.'</option>\n';

			if ($arCorr['TYPE'] != 'SELECT')
			{
				if ($arCorr['TYPE'] != '')
					$arCorr['VALUE'] = '';

				$res .= '<input type="text" value="'.htmlspecialcharsbx($arCorr['VALUE']);
				$res .= '" name="VALUE2_'.$idCorr;
				$res .= '" id="VALUE2_'.$idCorr;
				$res .= '" size="40"'.($arCorr['TYPE'] == '' ? '' : ' style="display: none;"').'>';
			}

			$res .= '</select>';
		}

		return $res;
	}

	public static function getPersonTypeIDs()
	{
		if (!CModule::IncludeModule('sale'))
			return array();

		static $arPTIDs = array();

		if(!empty($arPTIDs))
			return $arPTIDs;

		$dbPersonType = CSalePersonType::GetList(
				array('SORT' => "ASC", 'NAME' => 'ASC'),
				array('NAME' => array('CRM_COMPANY', 'CRM_CONTACT'))
		);

		while($arPT = $dbPersonType->GetNext())
		{
			if($arPT['NAME'] == 'CRM_COMPANY')
				$arPTIDs['COMPANY'] = $arPT['ID'];

			if($arPT['NAME'] == 'CRM_CONTACT')
				$arPTIDs['CONTACT'] = $arPT['ID'];
		}

		return $arPTIDs;
	}

	public static function getPersonTypesList($getEmpty = false)
	{
		$arPtIDs = self::getPersonTypeIDs();

		if(empty($arPtIDs) || !CModule::IncludeModule('sale'))
			return array();

		$arReturn = array();

		if($getEmpty)
			$arReturn[""] = GetMessage('CRM_ANY');

		$dbPersonType = CSalePersonType::GetList(
			array('SORT' => "ASC", 'NAME' => 'ASC'),
			array('ID' => array($arPtIDs['COMPANY'], $arPtIDs['CONTACT']))
		);

		while($arPT = $dbPersonType->GetNext())
			$arReturn[$arPT['ID']] = GetMessage($arPT['NAME']."_PT");

		return $arReturn;
	}

	public static function resolveOwnerTypeID($personTypeID)
	{
		$personTypeID = intval($personTypeID);
		$personTypeIDs = self::getPersonTypeIDs();
		if(isset($personTypeIDs['COMPANY']) && intval($personTypeIDs['COMPANY']) === $personTypeID)
		{
			return CCrmOwnerType::Company;
		}
		if(isset($personTypeIDs['CONTACT']) && intval($personTypeIDs['CONTACT']) === $personTypeID)
		{
			return CCrmOwnerType::Contact;
		}
		return CCrmOwnerType::Undefined;
	}

	public static function getPSCorrespondence($actFile)
	{
		if(!$actFile || !CModule::IncludeModule('sale'))
			return false;

		$arPSCorrespondence = array();

		$file = CCrmPaySystem::getActionPath($actFile);

		$path2SystemPSFiles = "/bitrix/modules/sale/payment/";
		$path2UserPSFiles = COption::GetOptionString("sale", "path2user_ps_files", BX_PERSONAL_ROOT."/php_interface/include/sale_payment/");

		if (substr($path2UserPSFiles, strlen($path2UserPSFiles) - 1, 1) != "/")
			$path2UserPSFiles .= "/";

		$bSystemPSFile = (substr($file, 0, strlen($path2SystemPSFiles)) == $path2SystemPSFiles);

		if (!$bSystemPSFile)
		{
			if (substr($path2UserPSFiles, strlen($path2UserPSFiles) - 1, 1) != "/")
				$path2UserPSFiles .= "/";
			$bUserPSFile = (substr($file, 0, strlen($path2UserPSFiles)) == $path2UserPSFiles);
		}

		if ($bUserPSFile || $bSystemPSFile)
		{
			if ($bUserPSFile)
				$fileName = substr($file, strlen($path2UserPSFiles));
			else
				$fileName = substr($file, strlen($path2SystemPSFiles));

			$fileName = preg_replace("#[^A-Za-z0-9_.-]#i", "", $fileName);

			$arPSCorrespondence = CCrmPaySystem::LocalGetPSActionParams($_SERVER["DOCUMENT_ROOT"].(($bUserPSFile) ? $path2UserPSFiles : $path2SystemPSFiles).$fileName."/.description.php");
		}

		return $arPSCorrespondence;
	}
	
	public static function rewritePSCorrByRqSource($personTypeId, &$params)
	{
		$personTypeId = (int)$personTypeId;
		$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
		if ($arPersonTypes['COMPANY'] != "" && $arPersonTypes['CONTACT'] != ""
			&& ($personTypeId == $arPersonTypes['CONTACT'] || $personTypeId == $arPersonTypes['COMPANY'])
		)
		{
			$personTypeCode = '';
			if ($personTypeId == $arPersonTypes['CONTACT'])
				$personTypeCode = 'CONTACT';
			else if ($personTypeId == $arPersonTypes['COMPANY'])
				$personTypeCode = 'COMPANY';

			$requisiteConverted = false;
			if (!empty($personTypeCode))
			{
				$requisiteConverted =
					(COption::GetOptionString('crm', '~CRM_TRANSFER_REQUISITES_TO_'.$personTypeCode, 'N') !== 'Y');
			}

			if ($requisiteConverted)
			{
				$countryId = \Bitrix\Crm\EntityPreset::getCurrentCountryId();
				if ($countryId)
				{
					$convMap = array(
						'PROPERTY' => array(
							'COMPANY' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_COMPANY_NAME|'.$countryId
							),
							'COMPANY_NAME' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_COMPANY_NAME|'.$countryId
							),
							'INN' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_INN|'.$countryId
							),
							'COMPANY_ADR' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_ADDR_'.\Bitrix\Crm\EntityAddress::Registered.'|'.$countryId
							),
							'PHONE' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_PHONE|'.$countryId
							),
							'FAX' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_FAX|'.$countryId
							),
							'CONTACT_PERSON' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_CONTACT|'.$countryId
							),
							'FIO' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_NAME|'.$countryId
							),
							'ADDRESS' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_ADDR_'.\Bitrix\Crm\EntityAddress::Primary.'|'.$countryId
							)
						)
					);
					if (is_array($params) && !empty($params))
					{
						foreach ($params as &$param)
						{
							if (isset($param['TYPE']) && $param['TYPE'] === 'PROPERTY'
								&& isset($param['VALUE']))
							{
								foreach ($convMap as $type => $typeMap)
								{
									if ($param['TYPE'] === $type)
									{
										foreach ($typeMap as $value => $newParam)
										{
											if ($param['VALUE'] === $value)
											{
												$param['TYPE'] = $newParam['TYPE'];
												$param['VALUE'] = $newParam['VALUE'];
											}
										}
									}
								}
							}
						}
						unset($param);
					}
				}
			}
		}
	}

	public static function isFormSimple()
	{
		return CUserOptions::GetOption("crm", "simplePSForm", "Y") == "Y";
	}

	public static function setFormSimple($bSimple = true)
	{
		return CUserOptions::SetOption("crm", "simplePSForm", ($bSimple ? "Y" : "N"));
	}

	public static function unSetFormSimple()
	{
		self::setFormSimple(false);
	}

	public static function GetPaySystems($personTypeId)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		if (self::$paySystems === null)
		{
			$arPersonTypes = self::getPersonTypeIDs();
			if (!isset($arPersonTypes['COMPANY']) || !isset($arPersonTypes['CONTACT']) ||
				$arPersonTypes['COMPANY'] <= 0 || $arPersonTypes['CONTACT'] <= 0)
				return false;

			$companyPaySystems = CSalePaySystem::DoLoadPaySystems($arPersonTypes['COMPANY']);
			$contactPaySystems = CSalePaySystem::DoLoadPaySystems($arPersonTypes['CONTACT']);

			self::$paySystems = array(
				$arPersonTypes['COMPANY'] => $companyPaySystems,
				$arPersonTypes['CONTACT'] => $contactPaySystems,
			);
		}

		if (!in_array($personTypeId, array_keys(self::$paySystems)))
			return false;

		return self::$paySystems[$personTypeId];
	}

	public static function GetPaySystemsListItems($personTypeId)
	{
		$arItems = array();

		$arPaySystems = self::GetPaySystems($personTypeId);
		if (is_array($arPaySystems))
			foreach ($arPaySystems as $paySystem)
			{
				if (preg_match('/bill(_\w+)*$/i'.BX_UTF_PCRE_MODIFIER, $paySystem['~PSA_ACTION_FILE']))
					$arItems[$paySystem['~ID']] = $paySystem['~NAME'];
			}

		return $arItems;
	}

	/**
	* Checks if is filled company-name at least in one pay system
	*/
	public static function isNameFilled()
	{
		if (!CModule::IncludeModule('sale'))
			return false;

		$result = false;
		$arCrmPtIDs = CCrmPaySystem::getPersonTypeIDs();
		$dbPaySystems = CSalePaySystem::GetList(array(), array( "PERSON_TYPE_ID" => $arCrmPtIDs ));

		while($arPaySys = $dbPaySystems->Fetch())
		{
			$params = $arPaySys['PSA_PARAMS'];
			$params = unserialize($arPaySys['PSA_PARAMS']);

			if(strlen(trim($params['SELLER_NAME']['VALUE'])) > 0)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	public static function isUserMustFillPSProps()
	{
		if(CUserOptions::GetOption('crm', 'crmInvoicePSPropsFillDialogViewedByUser', 'N') === 'Y')
			return false;

		$CrmPerms = new CCrmPerms($GLOBALS['USER']->GetID());

		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
			return false;

		if(self::isNameFilled())
			return false;

		return true;
	}

	public static function markPSFillPropsDialogAsViewed()
	{
		return CUserOptions::SetOption('crm', 'crmInvoicePSPropsFillDialogViewedByUser', 'Y');
	}

	public static function getRequisiteFieldSelectItems($fieldsUsedInSettings)
	{
		$result = array();

		$preset = new \Bitrix\Crm\EntityPreset();
		$requisite = new \Bitrix\Crm\EntityRequisite();
		$allowedCountries = $requisite->getAllowedRqFieldCountries();

		// address types
		$addressTypeList = array();
		$addressTitleList = array();
		foreach(Bitrix\Crm\RequisiteAddress::getClientTypeInfos() as $typeInfo)
		{
			$addressTypeList[] = $typeInfo['id'];
			$addressTitleList[$typeInfo['id']] = $typeInfo['name'];
		}


		// rq fields
		$rqFields = array();
		$tmpFields = $requisite->getRqFields();
		foreach ($tmpFields as $fieldName)
		{
			if ($fieldName === \Bitrix\Crm\EntityRequisite::ADDRESS)
			{
				foreach ($addressTypeList as $addressType)
					$rqFields[$fieldName.'_'.$addressType] = true;
			}
			else
			{
				$rqFields[$fieldName] = true;
			}
		}

		// rq fields by country
		$rqFieldsByCountry = array();
		foreach ($requisite->getRqFieldsCountryMap() as $fieldName => $fieldCountryIds)
		{
			if (is_array($fieldCountryIds))
			{
				foreach ($fieldCountryIds as $countryId)
				{
					if ($fieldName === \Bitrix\Crm\EntityRequisite::ADDRESS)
					{
						foreach ($addressTypeList as $addressType)
							$rqFieldsByCountry[$countryId][$fieldName.'_'.$addressType] = true;
					}
					else
					{
						$rqFieldsByCountry[$countryId][$fieldName] = true;
					}
				}
			}
		}

		// allowed fields
		$fieldsAllowed = array();
		foreach (array_merge(array_keys($rqFields), $requisite->getUserFields()) as $fieldName)
		{
			if ($fieldName === \Bitrix\Crm\EntityRequisite::ADDRESS)
			{
				foreach ($addressTypeList as $addressType)
					$fieldsAllowed[$fieldName.'_'.$addressType] = true;
			}
			else
			{
				$fieldsAllowed[$fieldName] = true;
			}
		}

		// used fields
		$usedCountries = array();
		$usedFieldsByCountry = array();
		if (is_array($fieldsUsedInSettings))
		{
			foreach ($fieldsUsedInSettings as $index)
			{
				$parts = explode('|', $index, 2);
				if (is_array($parts) && count($parts) === 2)
				{
					$fieldName = $parts[0];
					$fieldCountryId = (int)$parts[1];
					if (!empty($fieldName) && in_array($fieldCountryId, $allowedCountries, true))
					{
						if (!is_array($usedFieldsByCountry[$fieldCountryId]))
							$usedFieldsByCountry[$fieldCountryId] = array();
						$usedFieldsByCountry[$fieldCountryId][$fieldName] = true;
						$usedCountries[$fieldCountryId] = true;
					}
				}
			}
		}

		$currentCountryId = \Bitrix\Crm\EntityPreset::getCurrentCountryId();

		// active fields
		$activeFieldsByCountry = array();
		$tmpFields = $preset->getSettingsFieldsOfPresets(
			\Bitrix\Crm\EntityPreset::Requisite,
			'active',
			array(
				'ARRANGE_BY_COUNTRY' => true,
				'FILTER_BY_COUNTRY_IDS' => $allowedCountries
			)
		);
		foreach ($tmpFields as $countryId => $fieldList)
		{
			foreach ($fieldList as $fieldName)
			{
				if ($fieldName === \Bitrix\Crm\EntityRequisite::ADDRESS)
				{
					foreach ($addressTypeList as $addressType)
						$activeFieldsByCountry[$countryId][$fieldName.'_'.$addressType] = true;
				}
				else
				{
					$activeFieldsByCountry[$countryId][$fieldName] = true;
				}
				$usedCountries[$countryId] = true;
			}
		}

		// rq fields for backward compatibility
		$rqbcFields = array(
			'RQ_COMPANY_NAME' => true,
			'RQ_INN' => true,
			'RQ_KPP' => true,
			'RQ_ADDR_'.\Bitrix\Crm\EntityAddress::Primary => true,
			'RQ_ADDR_'.\Bitrix\Crm\EntityAddress::Registered => true,
			'RQ_EMAIL' => true,
			'RQ_PHONE' => true,
			'RQ_FAX' => true,
			'RQ_CONTACT' => true,
			'RQ_NAME' => true
		);

		$fieldsTitleMap = $requisite->getRqFieldTitleMap();

		$countrySort = array();
		if (isset($usedCountries[$currentCountryId]))
			$countrySort[] = $currentCountryId;
		foreach ($allowedCountries as $countryId)
		{
			if ($countryId !== $currentCountryId && isset($usedCountries[$countryId]))
				$countrySort[] = $countryId;
		}

		$countryTitleList = array();
		foreach (\Bitrix\Crm\EntityPreset::getCountryList() as $k => $v)
			$countryTitleList[$k] = $v;

		$result[] = array('id' => '', 'title' => GetMessage('CRM_PS_SELECT_NONE'));
		$addressPrefix = \Bitrix\Crm\EntityRequisite::ADDRESS;
		$isUTFMode = \Bitrix\Crm\EntityPreset::isUTFMode();
		foreach ($countrySort as $countryId)
		{
			$groupExists = false;
			$groupItem = array('type' => 'group', 'title' => $countryTitleList[$countryId]);
			$isCountryToShow = ($isUTFMode || $countryId === $currentCountryId);
			foreach (array_keys($fieldsAllowed) as $fieldName)
			{
				if ((isset($activeFieldsByCountry[$countryId][$fieldName])
						&& $isCountryToShow)
					|| isset($usedFieldsByCountry[$countryId][$fieldName])
					|| (isset($rqbcFields[$fieldName])
						&& isset($rqFieldsByCountry[$countryId][$fieldName])
						&& $isCountryToShow))
				{
					$matches = array();
					if (preg_match('/'.$addressPrefix.'_(\d+)/'.BX_UTF_PCRE_MODIFIER, $fieldName, $matches))
					{
						$addressType = (int)$matches[1];
						if (isset($addressTitleList[$addressType]))
						{
							if (!$groupExists)
							{
								$result[] = $groupItem;
								$groupExists = true;
							}
							$result[] = array(
								'id' => $fieldName.'|'.$countryId,
								'title' => $addressTitleList[$addressType]
							);
						}
					}
					else
					{
						$title = isset($fieldsTitleMap[$fieldName][$countryId]) ?
							$fieldsTitleMap[$fieldName][$countryId] : '';
						if (empty($title))
							$title = $fieldName;
						if (!$groupExists)
						{
							$result[] = $groupItem;
							$groupExists = true;
						}
						$result[] = array('id' => $fieldName.'|'.$countryId, 'title' => $title);
					}
				}
			}
		}

		return $result;
	}

	public static function getBankDetailFieldSelectItems()
	{
		$result = array();

		$preset = new \Bitrix\Crm\EntityPreset();
		$bankDetail = new \Bitrix\Crm\EntityBankDetail();

		$currentCountryId = \Bitrix\Crm\EntityPreset::getCurrentCountryId();

		$allowedCountries = $bankDetail->getAllowedRqFieldCountries();
		$activeCountries = array();
		$res = $preset->getList(array(
			'order' => array('SORT' => 'ASC'),
			'filter' => array(
				'=ENTITY_TYPE_ID' => \Bitrix\Crm\EntityPreset::Requisite,
				'=COUNTRY_ID' => $allowedCountries,
				'=ACTIVE' => 'Y'
			),
			'select' => array('ID', 'COUNTRY_ID')
		));
		while ($presetData = $res->fetch())
		{
			$countryId = (int)$presetData['COUNTRY_ID'];
			if ($countryId > 0)
				$activeCountries[$countryId] = true;
		}

		$fieldsTitleMap = $bankDetail->getRqFieldTitleMap();

		$countrySort = array();
		if (isset($activeCountries[$currentCountryId]))
			$countrySort[] = $currentCountryId;
		foreach (array_keys($activeCountries) as $countryId)
		{
			if ($countryId !== $currentCountryId)
				$countrySort[] = $countryId;
		}

		$countryList = array();
		foreach (\Bitrix\Crm\EntityPreset::getCountryList() as $k => $v)
			$countryList[$k] = $v;

		$fieldsByCountry = $bankDetail->getRqFieldByCountry();

		$isUTFMode = \Bitrix\Crm\EntityPreset::isUTFMode();
		$result[] = array('id' => '', 'title' => GetMessage('CRM_PS_SELECT_NONE'));
		foreach ($countrySort as $countryId)
		{
			if (!($isUTFMode || $countryId === $currentCountryId))
				continue;

			$result[] = array('type' => 'group', 'title' => $countryList[$countryId]);
			foreach ($fieldsByCountry[$countryId] as $fieldName)
			{
				$title = isset($fieldsTitleMap[$fieldName][$countryId]) ? $fieldsTitleMap[$fieldName][$countryId] : '';
				if (empty($title))
					$title = $fieldName;
				$result[] = array('id' => $fieldName.'|'.$countryId, 'title' => $title);
			}
		}

		return $result;
	}
}

?>