<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Context;
use \Bitrix\Main\Web\Json;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

use \Bitrix\Tasks\Util\Error\Collection;
use \Bitrix\Tasks\Dispatcher;
use \Bitrix\Tasks\Util\Calendar;

Loc::loadMessages(__FILE__);

abstract class TasksBaseComponent extends CBitrixComponent
{
	///////////////////////////////////
	// Component life cycle functions
	///////////////////////////////////

	const QUERY_TYPE_HIT = 			'hit';
	const QUERY_TYPE_AJAX = 		'ajax';

	protected $componentId = 		false;

	protected $errors = 			null;
	protected $userId = 			false;
	protected $auxParams = 			array();

	/**
	 * Component life cycle: page hit entry point
	 * @return void
	 */
	public function executeComponent()
	{
		$this->componentId = md5($this->getName().$this->getTemplateName());

		if(static::checkTasksModule())
		{
			$this->errors = new Collection();
		}

		$this->processExecutionStart();

		if(static::checkTasksModule())
		{
			$request = 		static::getRequest();

			$arResult = array();
			$this->auxParams = array(
				'QUERY_TYPE' => $this->getRequestParameter('AJAX') == '1' ? static::QUERY_TYPE_AJAX : static::QUERY_TYPE_HIT
			);

			static::checkRequiredModules($this->arParams, $arResult, $this->errors, $this->auxParams);

			if($this->errors->checkNoFatals())
			{
				static::checkBasicParameters($this->arParams, $arResult, $this->errors, $this->auxParams);
			}

			if($this->errors->checkNoFatals())
			{
				static::checkPermissions($this->arParams, $arResult, $this->errors, $this->auxParams);
			}

			$this->auxParams['ORIGIN_ARRESULT'] = $arResult;
			$this->translateArResult($arResult);

			if($this->errors->checkNoFatals())
			{
				$this->checkParameters();
			}

            $this->doPreAction();

			if($this->errors->checkNoFatals())
			{
                // check emitter here?
                if (($trigger = static::checkExecuteDispatcher($request, $this->errors)) !== false) {
                    $trigger = $this->processBeforeAction($trigger);
                    $this->auxParams['REQUEST'] = $request;
                    $this->arResult['ACTION_RESULT'] = static::dispatch($trigger, $this->errors, $this->auxParams, $this->arParams);
                    $this->processAfterAction();
                }
            }

			if($this->errors->checkNoFatals())
			{
				$this->getData();
				$this->getReferenceData();
				$this->formatData();
			}

			$this->doPostAction();
		}
		else
		{
			$this->arResult['ERROR'] = array(array('TYPE' => 'FATAL', 'CODE' => 'TASKS_MODULE_NOT_INSTALLED', 'MESSAGE' => Loc::getMessage("TASKS_TB_TASKS_MODULE_NOT_INSTALLED")));
		}

		$this->display();
		$this->processExecutionEnd();
	}

	protected function processExecutionStart()
	{
	}

	protected function processExecutionEnd()
	{
	}

	/**
	 * Component life cycle: ajax hit entry point
	 * @return mixed[]
	 */
	public static function executeComponentAjax(array $arParams = array(), array $behavior = array('DISPLAY' => true))
	{
		if(static::checkTasksModule())
		{
			$errors = 			new Collection();
			$request = 			static::getRequestUnescaped();

			$arParams = array_merge(static::extractParamsFromRequest($request), $arParams);
			$arResult = array();
			$auxParams = array(
				'QUERY_TYPE' => static::QUERY_TYPE_AJAX
			);

			static::checkSiteId($request, $errors); // SITE_ID should be present in request
			if($errors->checkNoFatals())
			{
				static::checkRequiredModules($arParams, $arResult, $errors, $auxParams);
			}
			if($errors->checkNoFatals())
			{
				static::checkBasicParameters($arParams, $arResult, $errors, $auxParams);
			}
			if($errors->checkNoFatals())
			{
				static::checkPermissions($arParams, $arResult, $errors, $auxParams);
			}

			$auxParams['ORIGIN_ARRESULT'] = $arResult;

			$result = array();
			if($errors->checkNoFatals())
			{
				if(($trigger = static::checkExecuteDispatcher($request, $errors)) !== false)
				{
					$auxParams['REQUEST'] = $request;
					$result = static::dispatch($trigger, $errors, $auxParams, $arParams);
				}
			}

			$errorsArray = $errors->getAll(true);
		}
		else
		{
			$errorsArray = array(array('TYPE' => 'FATAL', 'CODE' => 'TASKS_MODULE_NOT_INSTALLED', 'MESSAGE' => Loc::getMessage("TASKS_TB_TASKS_MODULE_NOT_INSTALLED")));
			$result = array();
		}

		if($behavior['DISPLAY'])
		{
			static::displayAjax($result, $errorsArray);
		}

		return array($result, $errorsArray);
	}

	protected function processBeforeAction($trigger = array())
	{
		return $trigger;
	}

	protected function processAfterAction()
	{
	}

	/**
	 * Allows to pass some of arParams through ajax request, according to the white-list
	 */
	protected static function extractParamsFromRequest($request)
	{
		return array(); // DO NOT simply pass $request to the result, its unsafe
	}

	protected function doPreAction()
	{
		return true;
	}

	/**
	 * Use it if you need to modify arResult in ancestor or do smth else before template show
	 */
	protected function doPostAction()
	{
		$this->arResult['ERROR'] = $this->errors->getAll(true);
		$this->arResult['COMPONENT_DATA']['ID'] = $this->componentId;
		$this->arResult['COMPONENT_DATA']['QUERY_TYPE'] = $this->auxParams['QUERY_TYPE'];

		return true;
	}

	protected function display()
	{
		$this->includeComponentTemplate();
	}

	protected static function displayAjax($data, $errors)
	{
		$result = array(
			'SUCCESS' => 	empty($errors),
			'ERROR' => 		$errors,
			'DATA' => 		$data
		);

		static::outputJSONResponce($result);
	}

	protected static function outputJSONResponce($result)
	{
		header('Content-Type: application/json');
		print(Json::encode($result));
	}

	public static function doFinalActions()
	{
		CMain::FinalActions();
		die();
	}

	protected static function checkExecuteDispatcher($request, Collection $errors)
	{
		if($errors->checkNoFatals())
		{
			if(($trigger = static::detectDispatchTrigger($request)) && static::checkCSRF($request, $errors))
			{
				return $trigger;
			}
		}

		return false;
	}

	/**
	 * Fetch all component data here
	 * You can add cached parts here
	 * @return void
	 */
	protected function getData()
	{
	}

	/**
	 * Fetch common data aggregated with getData(): users, gropus from different sources, etc
	 */
	protected function getReferenceData()
	{
	}

	/**
	 * Reformat result data if required
	 */
	protected function formatData()
	{
	}

	/**
	 * @param Bitrix\Main\Type\ParameterDictionary|string[] $request
	 * 
	 */
	protected static function dispatch($batch, Collection $errors, array $auxParams = array(), array $arParams = array())
	{
		$result = array();

		if(!is_array($batch))
		{
			return $result;
		}

		$dispatcher = new Dispatcher();

		$className = ToLower(get_called_class());

		// scan batch for "this.***" operations
		if(is_array($batch))
		{
			foreach($batch as $i => &$op)
			{
				if((string) $op['OPERATION'] != '')
				{
					$opName = trim($op['OPERATION']);

					if(strpos('this.', $opName) == 0)
					{
						$op['OPERATION'] = preg_replace('#^\s*this\.#', $className.'.', $op['OPERATION']);
					}
				}
			}
		}

		try
		{
			$result = $dispatcher->execute($batch);
		}
		catch(Dispatcher\BadQueryException $e)
		{
			$errors->addForeignErrors($dispatcher->getErrorCollection());
		}

		return $result;
	}

	protected static function checkTasksModule()
	{
		return Loader::includeModule('tasks');
	}

	protected static function checkSiteId($request, Collection $errors)
	{
		$siteId = static::extractSiteId($request);

		if((string) $siteId == '')
		{
			$errors->add('NO_SITE_ID', 'SITE_ID was not provided. There may be troubles with server-side API', Collection::TYPE_WARNING);
			return true;
		}
		$siteId = trim($siteId);

		if(!preg_match('#^[a-zA-Z0-9]{2}$#', $siteId))
		{
			$errors->add('SITE_ID_INVALID', 'SITE_ID is not valid');
			return false;
		}

		return true;
	}

	/**
	 * Function checks if required modules installed. If not, throws an exception
	 * @throws Exception
	 * @return void
	 */
	protected static function checkRequiredModules(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		return $errors->checkNoFatals();
	}

	/**
	 * Function checks and prepares only the basic parameters passed
	 */
	protected static function checkBasicParameters(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		return $errors->checkNoFatals();
	}

	/**
	 * Function checks if user have basic permissions to launch the component
	 * @throws Exception
	 * @return void
	 */
	protected static function checkPermissions(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		$userId = static::getEffectiveUserId($arParams);

		if(!$userId)
		{
			$errors->add('USER_NOT_DEFINED', 'Can not identify current user');
		}
		else
		{
			$arResult['USER_ID'] = static::checkUserRestrictions($userId, $errors);
		}

		if (!CBXFeatures::IsFeatureEnabled('Tasks'))
		{
			$errors->add('TASKS_MODULE_NOT_AVAILABLE', Loc::getMessage("TASKS_TB_TASKS_MODULE_NOT_AVAILABLE"));
		}

		$arResult['COMPONENT_DATA']['MODULES']['bitrix24'] = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24');
		$arResult['COMPONENT_DATA']['MODULES']['mail'] = \Bitrix\Main\ModuleManager::isModuleInstalled('mail');

		return $errors->checkNoFatals();
	}

	protected static function getEffectiveUserId($arParams)
	{
		return \Bitrix\Tasks\Util\User::getId();
	}

	protected static function checkUserRestrictions($userId, Collection $errors)
	{
		if($userId == \Bitrix\Tasks\Util\User::getId()) // if the effective user equals to the current user, check if authorized
		{
			if(!\Bitrix\Tasks\Util\User::get()->isAuthorized())
			{
				$errors->add('USER_NOT_AUTHORIZED', Loc::getMessage("TASKS_TB_USER_NOT_AUTHORIZED"));
				return false;
			}
		}

		return $userId;
	}

	/**
	 * Function checks and prepares all the parameters passed
	 */
	protected function checkParameters()
	{
		static::tryParseIntegerParameter($this->arParams['USER_ID'], $this->userId);

		return $this->errors->checkNoFatals();
	}

	/**
	 * Allows to decide which data shoult pass to $this->arResult, and which should not
	 */
	protected function translateArResult($arResult)
	{
		$this->userId = $arResult['USER_ID']; // a short-cut to current user`s ID
		unset($arResult['USER_ID']);

		$this->arResult = array_merge($this->arResult, $arResult); // default action: merge to $this->arResult
	}

	/**
	 * Check conditions on which the component starts to show interest to the current request. 
	 * There could be some general conditions besides the main dispatching process.
	 * @param mixed[] $request
	 * @return boolean
	 */
	protected static function detectDispatchTrigger($request)
	{
		if(Context::getCurrent()->getServer()->getRequestMethod() == 'POST' && !empty($request['ACTION']) && is_array($request['ACTION']))
		{
			return $request['ACTION'];
		}

		return false;
	}

	protected static function extractCSRF($request)
	{
		return $request['sessid'];
	}

	protected static function extractSiteId($request)
	{
		return $request['SITE_ID'];
	}

	protected static function checkCSRF($request, Collection $errors)
	{
		$csrf = static::extractCSRF($request);

		if((string) $csrf == '')
		{
			$errors->add('CSRF_ABSENT', 'CSRF token was not provided');
			return false;
		}
		elseif(\bitrix_sessid() != $csrf)
		{
			$errors->add('CSRF_FAIL', 'CSRF token is not valid');
			return false;
		}

		return true;
	}

	protected function getParameter($name)
	{
		return $this->arParams[static::getParameterAlias($name)];
	}

	protected static function getParameterAlias($name)
	{
		return $name;
	}

	protected static function getRequest($unEscape = false)
	{
		$request = Context::getCurrent()->getRequest();

		if($unEscape)
		{
			$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);
		}

		return $request->getPostList();
	}

	////////////////////////////////////////////////////
	// Auxiliary data getters, can be included on-demand
	////////////////////////////////////////////////////

	protected static function getCompanyWorkTime($default = false)
	{
		return $default ? Calendar::getDefaultSettings() : Calendar::getSettings();
	}

	protected static function getUserFields($entityId = 0, $entityName = 'TASKS_TASK')
	{
		return $GLOBALS['USER_FIELD_MANAGER']->GetUserFields($entityName, $entityId, LANGUAGE_ID);
	}

	protected static function getGroupsData(array $groupIds)
	{
		$groups = array();

		if(!empty($groupIds))
		{
			$groupIds = array_unique($groupIds);
			$parsed = array();
			foreach($groupIds as $groupId)
			{
				if(intval($groupId))
				{
					$parsed[] = $groupId;
				}
			}

			if(!empty($parsed))
			{
				$openedProjects = CUserOptions::GetOption("tasks", "opened_projects", array());

				$res = CSocNetGroup::GetList(array("ID" => "ASC"), array("ID" => $parsed));
				while($item = $res->fetch())
				{
					$item["EXPANDED"] = array_key_exists($item["ID"], $openedProjects) && $openedProjects[$item["ID"]] == "false" ? false : true;
					$groups[$item["ID"]] = $item;
				}
			}
		}

		return $groups;
	}

	////////////////////////////
	// Parameter parse functions
	////////////////////////////

	/**
	 * Function forces 'Y'/'N' value to boolean
	 * @param mixed $fld Field value
	 * @param string $default Default value
	 * @return string parsed value
	 */
	public static function tryParseBooleanParameter(&$fld, $default = false)
	{
		if(!isset($fld) || ($fld != 'Y' && $fld != 'N'))
		{
			$fld = $default;
			return $default;
		}

		$fld = $fld == 'Y';
		return $fld;
	}

	/**
	 * Function processes parameter value by white list, if gets null, passes the first value in white list
	 * @param mixed $fld Field value
	 * @param string $default Default value
	 * @return string parsed value
	 */
	protected static function tryParseListParameter(&$fld, $list = array())
	{
		if(!in_array($fld, $list))
		{
			$fld = current($list);
		}

		return $fld;
	}

	/**
	 * Function reduces input value to integer type, and, if gets null, passes the default value
	 * @param mixed $fld Field value
	 * @param int $default Default value
	 * @param int $allowZero Allows zero-value of the parameter
	 * @return int Parsed value
	 */
	public static function tryParseIntegerParameter(&$fld, $default = false, $allowZero = false)
	{
		$fld = intval($fld);
		if(!$allowZero && !$fld && $default !== false)
		{
			$fld = $default;
		}
			
		return $fld;
	}

	protected static function tryParseNonNegativeIntegerParameter(&$fld, $default = false)
	{
		$fld = isset($fld) ? abs(intval($fld)) : ($default !== false ? $default : 0);

		return $fld;
	}

	/**
	 * Function processes string value and, if gets null, passes the default value to it
	 * @param mixed $fld Field value
	 * @param string $default Default value
	 * @return string parsed value
	 */
	public static function tryParseStringParameter(&$fld, $default = false)
	{
		$fld = trim((string)$fld);
		if((string) $fld == '' && $default !== false)
		{
			$fld = $default;
		}

		$fld = htmlspecialcharsbx($fld);

		return $fld;
	}

	/**
	 * Function processes string value and, if gets null, passes the default value to it
	 * @param mixed $fld Field value
	 * @param string $default Default value
	 * @return string parsed value
	 */
	protected static function tryParseStringParameterStrict(&$fld, $default = false)
	{
		$fld = trim((string)$fld);
		if((string) $fld == '' && $default !== false)
		{
			$fld = $default;
		}

		$fld = preg_replace('#[^a-z0-9_-]#i', '', $fld);

		return $fld;
	}

	protected static function tryParseArrayParameter(&$fld, $default = array())
	{
		if(!is_array($fld))
		{
			$fld = $default;
		}

		return $fld;
	}

	/**
	* When not a part of enumeration assign default.
	*/
	protected static function tryParseEnumerationParameter(&$fld, array $enum, $default = false)
	{
		if(!in_array($fld, $enum))
		{
			$fld = $default;
		}

		return $fld;
	}

	////////////////////////////
	// Helper functions
	////////////////////////////

	protected function getRequestParameter($name)
	{
		$value = false;
		if($this->request['EMITTER'] == $this->componentId)
		{
			return isset($this->request[$name]) ? $this->request[$name] : false;
		}

		return $value;
	}

	protected static function cleanTaskData(&$data)
	{
		//unset($data['CREATED_BY_NAME']);
		//unset($data['CREATED_BY_LAST_NAME']);
		//unset($data['CREATED_BY_SECOND_NAME']);
		//unset($data['CREATED_BY_LOGIN']);
		unset($data['CREATED_BY_WORK_POSITION']);
		unset($data['CREATED_BY_PHOTO']);

		//unset($data['RESPONSIBLE_NAME']);
		//unset($data['RESPONSIBLE_LAST_NAME']);
		//unset($data['RESPONSIBLE_SECOND_NAME']);
		//unset($data['RESPONSIBLE_LOGIN']);
		unset($data['RESPONSIBLE_WORK_POSITION']);
		unset($data['RESPONSIBLE_PHOTO']);
	}

	protected static function getRequestUnescaped()
	{
		CUtil::JSPostUnescape();

		return static::getRequest(true);
	}

	public static function getAllowedMethods()
	{
		return array();
	}
}