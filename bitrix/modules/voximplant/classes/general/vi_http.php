<?
class CVoxImplantHttp
{
	const TYPE_BITRIX24 = 'B24';
	const TYPE_CP = 'CP';
	const VERSION = 8;

	private $controllerUrl = 'https://telephony.bitrix.info/telephony/portal.php';
	private $licenceCode = '';
	private $domain = '';
	private $type = '';
	private $error = null;

	function __construct()
	{
		$this->error = new CVoxImplantError(null, '', '');
		if (defined('VOXIMPLANT_CONTROLLER_URL'))
		{
			$this->controllerUrl = VOXIMPLANT_CONTROLLER_URL;
		}
		if(defined('BX24_HOST_NAME'))
		{
			$this->licenceCode = BX24_HOST_NAME;
		}
		else if(defined('VOXIMPLANT_HOST_NAME'))
		{
			$this->licenceCode = VOXIMPLANT_HOST_NAME;
		}
		else
		{
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");
			$this->licenceCode = md5("BITRIX".CUpdateClient::GetLicenseKey()."LICENCE");
		}
		$this->type = self::GetPortalType();
		$this->domain = self::GetServerAddress();

		return true;
	}

	public static function GetPortalType()
	{
		$type = '';
		if(defined('BX24_HOST_NAME') || defined('VOXIMPLANT_HOST_NAME'))
		{
			$type = self::TYPE_BITRIX24;
		}
		else
		{
			$type = self::TYPE_CP;
		}
		return $type;
	}

	public function GetAccountInfo()
	{
		$query = $this->Query('GetAccountInfo');
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetPhoneNumberCategories($countryCode = '')
	{
		$query = $this->Query(
			'GetPhoneNumberCategories',
			Array('COUNTRY_CODE' => $countryCode)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetPhoneNumberCountryStates($phoneCategoryName, $countryCode, $countryState = '')
	{
		$params = Array(
			'PHONE_CATEGORY_NAME' => $phoneCategoryName,
			'COUNTRY_CODE' => $countryCode,
			'COUNTRY_STATE' => $countryState,
		);

		$query = $this->Query(
			'GetPhoneNumberCountryStates',
			$params
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetPhoneNumberRegions($phoneCategoryName, $countryCode, $countryState = '', $phoneRegionName = '', $phoneRegionCode = '',  $phoneRegionId = '')
	{
		$params = Array(
			'PHONE_CATEGORY_NAME' => $phoneCategoryName,
			'COUNTRY_CODE' => $countryCode,
			'COUNTRY_STATE' => $countryState,
			'PHONE_REGION_NAME' => $phoneRegionName,
			'PHONE_REGION_CODE' => $phoneRegionCode,
			'PHONE_REGION_ID' => $phoneRegionId,
		);

		$query = $this->Query(
			'GetPhoneNumberRegions',
			$params
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetPhoneNumbers()
	{
		$query = $this->Query(
			'GetPhoneNumbers'
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function ClearConfigCache()
	{
		$query = $this->Query(
			'ClearConfigCache'
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function StartOutgoingCall($userId, $phoneNumber)
	{
		$query = $this->Query(
			'StartOutgoingCall',
			Array('TYPE' => 'phone', 'USER_ID' => intval($userId), 'NUMBER' => $phoneNumber, 'IP' => Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRemoteAddress())
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetNewPhoneNumbers($phoneCategoryName, $countryCode, $phoneRegionId, $offset = 0, $count = 20, $countryState = '')
	{
		$params = Array(
			'PHONE_CATEGORY_NAME' => $phoneCategoryName,
			'COUNTRY_CODE' => $countryCode,
			'PHONE_REGION_ID' => $phoneRegionId,
			'OFFSET' => intval($offset),
			'COUNT' => intval($count),
			'COUNTRY_STATE' => $countryState,
		);

		$query = $this->Query(
			'GetNewPhoneNumbers',
			$params
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function AttachPhoneNumber($phoneCategoryName, $countryCode, $phoneRegionId, $phoneNumber = '', $countryState = '', $addressVerification = '')
	{
		$params = Array(
			'PHONE_CATEGORY_NAME' => $phoneCategoryName,
			'COUNTRY_CODE' => $countryCode,
			'PHONE_REGION_ID' => $phoneRegionId,
			'PHONE_NUMBER' => $phoneNumber,
			'COUNTRY_STATE' => $countryState,
			'ADDRESS_VERIFICATION' => $addressVerification
		);
		$query = $this->Query(
			'AttachPhoneNumber',
			$params
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function DeactivatePhoneNumber($phoneNumber)
	{
		$query = $this->Query(
			'DeactivatePhoneNumber',
			Array('PHONE_NUMBER' => $phoneNumber)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function CancelDeactivatePhoneNumber($phoneNumber)
	{
		$query = $this->Query(
			'CancelDeactivatePhoneNumber',
			Array('PHONE_NUMBER' => $phoneNumber)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetPhoneOrderStatus()
	{
		$query = $this->Query(
			'GetPhoneOrderStatus'
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function AddPhoneOrder($params)
	{
		$query = $this->Query(
			'AddPhoneOrder',
			Array('FORM_DATA' => Bitrix\Main\Web\Json::encode($params))
		);

		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function AddServiceOrder($params)
	{
		$query = $this->Query(
			'AddServiceOrder',
			Array('FORM_DATA' => Bitrix\Main\Web\Json::encode($params))
		);

		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetUser($userId, $getPhoneAccess = false)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		$query = $this->Query(
			'GetUser',
			Array('USER_ID' => $userId, 'GET_PHONE_ACCESS' => $getPhoneAccess? 'Y': 'N')
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetUsers($userId, $multiply = true)
	{
		if (!is_array($userId))
			$userId = Array($userId);

		foreach ($userId as $key => $value)
		{
			$userId[$key] = intval($value);
		}

		$query = $this->Query(
			'GetUsers',
			Array('USER_ID' => implode('|', $userId), 'MULTIPLY' => $multiply? 'Y': 'N')
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function UpdateUserPassword($userId, $mode, $password = false)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		$query = $this->Query(
			'UpdateUserPassword',
			Array('USER_ID' => $userId, 'MODE' => $mode, 'PASSWORD' => $password? $password: '')
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetSipInfo()
	{
		$query = $this->Query(
			'GetSipInfo',
			Array()
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}
		return $query;
	}

	public function GetSipParams($configId)
	{
		$configId = intval($configId);

		$query = $this->Query(
			'GetSipParams',
			Array('CONFIG_ID' => $configId)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}
		return $query;
	}

	public function GetOnlineUsers()
	{
		$query = $this->Query(
			'GetOnlineUsers',
			Array()
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetCallHistory($filter = Array(), $limit = 20, $page = 1)
	{
		$arFilter = Array('LIMIT' => intval($limit), 'PAGE' => intval($page));
		if (isset($filter['LAST_ID']))
			$arFilter['LAST_ID'] = intval($filter['LAST_ID']);

		$query = $this->Query(
			'GetCallHistory',
			$arFilter
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function CreateSipRegistration($server, $login, $password = '')
	{
		if (strlen($server) <= 3)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'SERVER_INCORRECT', 'Server is not correct');
			return false;
		}
		if (strlen($login) <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'LOGIN_INCORRECT', 'Login is not correct');
			return false;
		}

		$query = $this->Query(
			'CreateSipRegistration',
			Array('SERVER' => $server, 'LOGIN' => $login, 'PASSWORD' => $password)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function UpdateSipRegistration($regId, $server, $login, $password = '')
	{
		if (intval($regId) <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'REG_ID_INCORRECT', 'Registration ID is not correct');
			return false;
		}
		if (strlen($server) <= 3)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'SERVER_INCORRECT', 'Server is not correct');
			return false;
		}
		if (strlen($login) <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'LOGIN_INCORRECT', 'Login is not correct');
			return false;
		}

		$query = $this->Query(
			'UpdateSipRegistration',
			Array('REG_ID' => $regId, 'SERVER' => $server, 'LOGIN' => $login, 'PASSWORD' => $password)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function DeleteSipRegistration($regId)
	{
		if (intval($regId) <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'REG_ID_INCORRECT', 'Registration ID is not correct');
			return false;
		}

		$query = $this->Query(
			'DeleteSipRegistration',
			Array('REG_ID' => $regId)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetSipRegistrations($regId)
	{
		if (intval($regId) <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'REG_ID_INCORRECT', 'Registration ID is not correct');
			return false;
		}

		$query = $this->Query(
			'GetSipRegistrations',
			Array('REG_ID' => $regId)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function AddCallerID($number)
	{
		if (strlen($number) < 10)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CALLERID_INCORRECT', 'CallerID is not correct');
			return false;
		}

		$query = $this->Query(
			'AddCallerID',
			Array('NUMBER' => $number)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function DelCallerID($number)
	{
		if (strlen($number) < 10)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CALLERID_INCORRECT', 'CallerID is not correct');
			return false;
		}

		$query = $this->Query(
			'DelCallerID',
			Array('NUMBER' => $number)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetCallerIDs($number = '')
	{
		if ($number > 0 && strlen($number) < 10)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CALLERID_INCORRECT', 'CallerID is not correct');
			return false;
		}

		$query = $this->Query(
			'GetCallerIDs',
			$number > 0? Array('NUMBER' => $number): Array()
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function VerifyCallerID($number)
	{
		if (strlen($number) < 10)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CALLERID_INCORRECT', 'CallerID is not correct');
			return false;
		}

		$query = $this->Query(
			'VerifyCallerID',
			Array('NUMBER' => $number)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function ActivateCallerID($number, $code)
	{
		if (strlen($number) < 10)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CALLERID_INCORRECT', 'CallerID is not correct');
			return false;
		}
		if (strlen($code) <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'CODE_INCORRECT', 'Code for activation is not correct');
			return false;
		}

		$query = $this->Query(
			'ActivateCallerID',
			Array('NUMBER' => $number, 'CODE' => $code)
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetDocumentAccess()
	{
		$query = $this->Query(
			'GetDocumentAccess',
			Array()
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetAvailableVerifications($countryCode, $categoryName, $regionCode = '')
	{
		$parameters = array(
			'COUNTRY_CODE' => $countryCode,
			'CATEGORY_NAME' => $categoryName,
			'REGION_CODE' => $regionCode
		);

		$query = $this->Query(
				'GetAvailableVerifications',
				$parameters
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;
	}

	public function GetVerifications($countryCode = '', $phoneCategoryName = '', $phoneRegionCode = '', $verified = null, $inProgress = null)
	{
		$parameters = array(
			'COUNTRY_CODE' => $countryCode,
			'CATEGORY_NAME' => $phoneCategoryName,
			'REGION_CODE' => $phoneRegionCode,
			'VERIFIED' => $verified,
			'IN_PROGRESS' => $inProgress,
		);

		$query = $this->Query(
			'GetVerifications',
			$parameters
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		return $query;

	}

	public function GetDocumentStatus()
	{
		$query = $this->Query(
			'GetDocumentStatus',
			Array()
		);
		if (isset($query->error))
		{
			$this->error = new CVoxImplantError(__METHOD__, $query->error->code, $query->error->msg);
			return false;
		}

		foreach ($query as $key => $verification)
		{
			if (isset($verification->DOCUMENTS))
			{
				foreach ($verification->DOCUMENTS as $id => $document)
				{
					$query[$key]->DOCUMENTS[$id]->REVIEWER_COMMENT = \Bitrix\Main\Text\Encoding::convertEncodingToCurrent($document->REVIEWER_COMMENT);
				}
			}
		}

		return $query;
	}

	public function GetError()
	{
		return $this->error;
	}

	private function Query($command, $params = array())
	{
		if (strlen($command) <= 0 || !is_array($params))
			return false;

		$params['BX_COMMAND'] = $command;
		$params['BX_LICENCE'] = $this->licenceCode;
		$params['BX_DOMAIN'] = $this->domain;
		$params['BX_TYPE'] = $this->type;
		$params['BX_VERSION'] = self::VERSION;
		$params["BX_HASH"] = $this->RequestSign($this->type, md5(implode("|", $params)));

		$CHTTP = new CHTTP();
		$arUrl = $CHTTP->ParseURL($this->controllerUrl);
		if ($CHTTP->Query('POST', $arUrl['host'], $arUrl['port'], $arUrl['path_query'], CHTTP::PrepareData($params), $arUrl['proto']))
		{
			$result = json_decode($CHTTP->result);
			if (!$result)
			{
				CVoxImplantHistory::WriteToLog($CHTTP->result, 'ERROR QUERY EXECUTE');
			}
		}
		else
		{
			$result = json_decode(json_encode(Array('error' => Array('code' => 'CONNECT_ERROR', 'msg' => 'Parse error or connect error from server'))));
			CVoxImplantHistory::WriteToLog($result, 'ERROR QUERY EXECUTE');
		}
		return $result;
	}

	public function RequestSign($type, $str)
	{
		if ($type == self::TYPE_BITRIX24 && function_exists('bx_sign'))
		{
			return bx_sign($str);
		}
		else
		{
			$LICENSE_KEY = "";
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php");
			return md5($str.md5($LICENSE_KEY));
		}
	}

	public function CheckDirectRequest($params)
	{
		if(strlen($params["HASH"]) <= 0)
		{
			return false;
		}

		$hash = $params["HASH"];
		unset($params["HASH"]);

		$string = "";
		$paramsExeption = array("PARAMS", "SCENARIO_VERSION", "SCENARIO_NAME", "DIRECTION", "CALL_DIRECTION", "CALL_FAILED_REASON", "CALL_FAILED_CODE", "ACCESS_URL");

		foreach($params as $k => $v)
		{
			if(!in_array($k, $paramsExeption))
			{
				if(strlen($string) > 0)
					$string .= "&";
				$string .= $k."=".$v;
			}
		}
		$string .= "|".self::GetPortalSign();

		if(md5($string) == $hash)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function GetPortalSign()
	{
		if(defined('BX24_HOST_NAME') || defined('VOXIMPLANT_HOST_NAME'))
		{
			return self::RequestSign(self::TYPE_BITRIX24, defined('BX24_HOST_NAME')? md5(BX24_HOST_NAME): md5(VOXIMPLANT_HOST_NAME));
		}
		else
		{
			return self::RequestSign(self::TYPE_CP, 'DIRECT CONNECT SIGN');
		}
	}

	public static function GetServerAddress()
	{
		$publicUrl = COption::GetOptionString("voximplant", "portal_url", '');

		if ($publicUrl != '')
			return $publicUrl;
		else
			return (CMain::IsHTTPS() ? "https" : "http")."://".$_SERVER['SERVER_NAME'].(in_array($_SERVER['SERVER_PORT'], Array(80, 443))?'':':'.$_SERVER['SERVER_PORT']);
	}
}
?>
