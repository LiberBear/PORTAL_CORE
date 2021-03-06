<?php
namespace Bitrix\Crm\Integration;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

/**
 * Class Bitrix24Manager
 *
 * Required in Biitrix24 context. Provodes information about the license and supported features.
 * @package Bitrix\Crm\Integration
 */
class Bitrix24Manager
{
	//region Members
	/** @var bool|null */
	private static $hasPurchasedLicense = null;
	/** @var bool|null */
	private static $hasDemoLicense = null;
	/** @var bool|null */
	private static $hasNfrLicense = null;
	/** @var bool|null */
	private static $hasPurchasedUsers = null;
	/** @var bool|null */
	private static $hasPurchasedDiskSpace = null;
	/** @var bool|null */
	private static $isPaidAccount = null;
	/** @var bool|null */
	private static $enableRestBizProc = null;
	/** @var array|null */
	private static $entityAccessFlags = null;
	/** @var array|null */
	private static $unlimitedAccessFlags = null;
	//endregion
	//region Methods
	/**
	 * Check if current manager enabled.
	 * @return bool
	 */
	public static function isEnabled()
	{
		return ModuleManager::isModuleInstalled('bitrix24');
	}
	/**
	 * Check if portal has paid license, paid for extra users, paid for disk space or SIP features.
	 * @return bool
	 */
	public static function isPaidAccount()
	{
		if(self::$isPaidAccount !== null)
		{
			return self::$isPaidAccount;
		}

		self::$isPaidAccount = self::hasPurchasedLicense()
			|| self::hasPurchasedUsers()
			|| self::hasPurchasedDiskSpace();

		if(!self::$isPaidAccount)
		{
			//Phone number check: voximplant::account_payed
			//SIP connector check: main::~PARAM_PHONE_SIP
			self::$isPaidAccount = \COption::GetOptionString('voximplant', 'account_payed', 'N') === 'Y'
				|| \COption::GetOptionString('main', '~PARAM_PHONE_SIP', 'N') === 'Y';
		}

		return self::$isPaidAccount;
	}
	/**
	 * Check if portal has paid license.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function hasPurchasedLicense()
	{
		if(self::$hasPurchasedLicense !== null)
		{
			return self::$hasPurchasedLicense;
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
			&& method_exists('CBitrix24', 'IsLicensePaid'))
		{
			return (self::$hasPurchasedLicense = false);
		}

		return (self::$hasPurchasedLicense = \CBitrix24::IsLicensePaid());
	}
	/**
	 *  Check if portal has trial license.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function hasDemoLicense()
	{
		if(self::$hasDemoLicense !== null)
		{
			return self::$hasDemoLicense;
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
			&& method_exists('CBitrix24', 'IsDemoLicense'))
		{
			return (self::$hasDemoLicense = false);
		}

		return (self::$hasDemoLicense = \CBitrix24::IsDemoLicense());
	}
	/**
	 * Check if portal has NFR license.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function hasNfrLicense()
	{
		if(self::$hasNfrLicense !== null)
		{
			return self::$hasNfrLicense;
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
			&& method_exists('CBitrix24', 'IsNfrLicense'))
		{
			return (self::$hasNfrLicense = false);
		}

		return (self::$hasNfrLicense = \CBitrix24::IsNfrLicense());
	}
	/**
	 * Check if portal has paid for extra users.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function hasPurchasedUsers()
	{
		if(self::$hasPurchasedUsers !== null)
		{
			return self::$hasPurchasedUsers;
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
			&& method_exists('CBitrix24', 'IsExtraUsers'))
		{
			return (self::$hasPurchasedUsers = false);
		}

		return (self::$hasPurchasedUsers = \CBitrix24::IsExtraUsers());
	}
	/**
	 * Check if portal has paid for extra disk space.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function hasPurchasedDiskSpace()
	{
		if(self::$hasPurchasedDiskSpace !== null)
		{
			return self::$hasPurchasedDiskSpace;
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
			&& method_exists('CBitrix24', 'IsExtraDiskSpace'))
		{
			return (self::$hasPurchasedDiskSpace = false);
		}

		return (self::$hasPurchasedDiskSpace = \CBitrix24::IsExtraDiskSpace());
	}
	/**
	 * Check if Business Processes are enabled for REST API.
	 * @return bool
	 */
	public static function isRestBizProcEnabled()
	{
		if(self::$enableRestBizProc !== null)
		{
			return self::$enableRestBizProc;
		}

		return (self::$enableRestBizProc = (self::hasPurchasedLicense() || self::hasNfrLicense()));
	}
	/**
	 * Prepare JavaScript for license purchase information.
	 * @param array $params Popup params.
	 * @return string
	 * @throws Main\LoaderException
	 */
	public static function prepareLicenseInfoPopupScript(array $params)
	{
		if(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24')
			&& method_exists('CBitrix24', 'initLicenseInfoPopupJS'))
		{
			\CBitrix24::initLicenseInfoPopupJS();

			$popupID = isset($params['ID']) ? \CUtil::JSEscape($params['ID']) : '';
			$title = isset($params['TITLE']) ? \CUtil::JSEscape($params['TITLE']) : '';
			$content = isset($params['CONTENT']) ? \CUtil::JSEscape($params['CONTENT']) : '';

			return "if(typeof(B24.licenseInfoPopup) !== 'undefined'){ B24.licenseInfoPopup.show('{$popupID}', '{$title}', '{$content}'); }";
		}

		return '';
	}
	/**
	 * Prepare HTML for license purchase information.
	 * @param array $params Popup params.
	 * @return string
	 * @throws Main\LoaderException
	 */
	public static function prepareLicenseInfoHtml(array $params)
	{
		if(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
		{
			$popupID = isset($params['ID']) ? \CUtil::JSEscape($params['ID']) : '';
			$content = '';
			if(isset($params['CONTENT']))
			{
				$licenseListUrl = \CUtil::JSEscape(\CBitrix24::PATH_LICENSE_ALL);
				$demoLicenseUrl = \CUtil::JSEscape(\CBitrix24::PATH_LICENSE_DEMO);

				$content = str_replace(
					array(
						'#LICENSE_LIST_SCRIPT#',
						'#DEMO_LICENSE_SCRIPT#'
					),
					array(
						"BX.CrmRemoteAction.items['{$popupID}'].execute('{$licenseListUrl}');",
						"BX.CrmRemoteAction.items['{$popupID}'].execute('{$demoLicenseUrl}');"
					),
					$params['CONTENT']
				);
			}

			$serviceUrl = \CUtil::JSEscape(\CBitrix24::PATH_COUNTER);
			$hostName = \CUtil::JSEscape(BX24_HOST_NAME);
			return "{$content}
				<script type='text/javascript'>
					BX.ready(
						function()
						{
							BX.CrmRemoteAction.create(
								'{$popupID}',
								{
									serviceUrl: '{$serviceUrl}',
									data: { host: '{$hostName}', action: 'tariff', popupId: '{$popupID}' }
								}
							);
						}
					);
				</script>";
		}

		return '';
	}
	/**
	 * Get URL for "Choose a Bitrix24 plan" page.
	 * @return string
	 * @throws Main\LoaderException
	 */
	public static function getLicenseListPageUrl()
	{
		if(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
		{
			return \CBitrix24::PATH_LICENSE_ALL;
		}

		return '';
	}
	/**
	 * Get URL for "Free 30-day trial" page.
	 * @return string
	 * @throws Main\LoaderException
	 */
	public static function getDemoLicensePageUrl()
	{
		if(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24'))
		{
			return \CBitrix24::PATH_LICENSE_DEMO;
		}

		return '';
	}
	/**
	 * Check accessability of entity type according to Bitrix24 restrictions.
	 * @param int $entityTypeID Entity type ID.
	 * @param int $userID User ID (if not specified then current user ID will be taken).
	 * @return bool
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 */
	public static function isAccessEnabled($entityTypeID, $userID = 0)
	{
		if(!is_integer($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_integer($userID))
		{
			$userID = (int)$userID;
		}

		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		if(self::$entityAccessFlags === null)
		{
			self::$entityAccessFlags = array();
		}

		if(!isset(self::$entityAccessFlags[$userID]))
		{
			self::$entityAccessFlags[$userID] = array();
		}

		$code = $entityTypeID === \CCrmOwnerType::Lead ? 'crm_lead' : 'crm';
		if(isset(self::$entityAccessFlags[$userID][$code]))
		{
			return self::$entityAccessFlags[$userID][$code];
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24')
			&& method_exists('CBitrix24BusinessTools', 'isToolAvailable')))
		{
			return (self::$entityAccessFlags[$userID][$code] = true);
		}

		return (self::$entityAccessFlags[$userID][$code] = \CBitrix24BusinessTools::isToolAvailable($userID, $code));
	}
	/**
	 * Check if user has unlimited access
	 * @param int $userID User ID (if not specified then current user ID will be taken).
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function isUnlimitedAccess($userID = 0)
	{
		if(!is_integer($userID))
		{
			$userID = (int)$userID;
		}

		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}

		if(self::$unlimitedAccessFlags === null)
		{
			self::$unlimitedAccessFlags = array();
		}

		if(isset(self::$unlimitedAccessFlags[$userID]))
		{
			return self::$unlimitedAccessFlags[$userID];
		}

		if(!(ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('bitrix24')
			&& method_exists('CBitrix24BusinessTools', 'isUserUnlimited')))
		{
			return (self::$unlimitedAccessFlags[$userID] = true);
		}

		return (self::$unlimitedAccessFlags[$userID] = \CBitrix24BusinessTools::isUserUnlimited($userID));
	}
	//endregion
}
?>