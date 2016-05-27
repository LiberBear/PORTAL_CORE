<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var $arParams array
 * @var $arResult array
 * @var $this CBitrixComponent
 * @var $APPLICATION CMain
 * @var $USER CUser
 */

use Bitrix\Main\Loader;

class CVoxImplantComponentDocuments extends CBitrixComponent
{
	protected $showTemplate = true;

	protected function init()
	{
		if(isset($this->arParams['TEMPLATE_HIDE']) && $this->arParams['TEMPLATE_HIDE'] === 'Y')
			$this->showTemplate = false;
	}

	protected function prepareData()
	{
		$this->showTemplate = false;
		$documents = new CVoxImplantDocuments();
		$request = Bitrix\Main\Context::getCurrent()->getRequest();

		$this->arResult['DOCUMENTS'] = $documents->GetStatus();
		if(is_array($this->arResult['DOCUMENTS']) && count($this->arResult['DOCUMENTS']) > 0)
		{
			$this->showTemplate = true;
			foreach($this->arResult['DOCUMENTS'] as &$verification)
			{
				$verification['COUNTRY_CODE'] = $verification['REGION'];
				$verification['COUNTRY'] = $verification['REGION_NAME'];
				$verification['ADDRESS'] = $verification['COUNTRY'];
				$verification['UPLOAD_IFRAME_URL'] = $documents->GetUploadUrl($verification['REGION']);
				unset($verification['REGION']);
				unset($verification['REGION_NAME']);
			}
		}
		unset($verification);
		$documents->setFilledByUser($this->getCurrentUserId());

		$addressVerification = new \Bitrix\VoxImplant\AddressVerification();
		$verifications = $addressVerification->getVerifications();
		if(is_array($verifications['VERIFIED_ADDRESS']))
		{
			$this->showTemplate = true;
			if(!is_array($this->arResult['DOCUMENTS']))
				$this->arResult['DOCUMENTS'] = array();

			foreach($verifications['VERIFIED_ADDRESS'] as $verification)
			{
				$verification['ADDRESS'] = $verification['ZIP_CODE'].', '.$verification['COUNTRY'].', '.$verification['CITY'].', '.$verification['STREET'].' '.$verification['BUILDING_NUMBER'].($verification['BUILDING_LETTER'] ? '-'.$verification['BUILDING_LETTER'] : '');
				$this->arResult['DOCUMENTS'][] = $verification;
			}
		}

		if(isset($request['SHOW_UPLOAD_IFRAME'])
				&& $request['SHOW_UPLOAD_IFRAME'] === 'Y'
				&& isset($request['UPLOAD_COUNTRY_CODE'])
		)
		{
			$addressVerification->setFilledByUser($this->getCurrentUserId());
			$this->showTemplate = true;
			if(!is_array($this->arResult['DOCUMENTS']))
				$this->arResult['DOCUMENTS'] = array();

			$verificationFound = false;
			foreach($this->arResult['DOCUMENTS'] as &$verification)
			{
				if($verification['COUNTRY_CODE'] === $request['UPLOAD_COUNTRY_CODE'])
				{
					$verificationFound = true;
					break;
				}
			}
			unset($verification);

			if(!$verificationFound)
				$verification = $this->createVerification($request['UPLOAD_COUNTRY_CODE'], CVoxImplantDocuments::STATUS_REQUIRED);

			$verification['SHOW_UPLOAD_IFRAME'] = true;
			if(!isset($verification['UPLOAD_IFRAME_URL']))
			{
				$iframeUrl = $documents->GetUploadUrl($request['UPLOAD_COUNTRY_CODE'], $request['UPLOAD_ADDRESS_TYPE'], $request['UPLOAD_PHONE_CATEGORY'], $request['UPLOAD_REGION_CODE']);
				if($iframeUrl === false)
				{
					$verification['SHOW_UPLOAD_IFRAME'] = false;
					$verification['STATUS'] = 'ERROR';
				}
				else
				{
					$verification['UPLOAD_IFRAME_URL'] = $iframeUrl;
				}
			}

			if(!$verificationFound)
				$this->arResult['DOCUMENTS'][] = $verification;
		}
	}

	protected function createVerification($countryCode, $status)
	{
		return array(
			'COUNTRY_CODE' => $countryCode,
			'COUNTRY' => CVoxImplantPhone::getCountryName($countryCode),
			'ADDRESS' => CVoxImplantPhone::getCountryName($countryCode),
			'STATUS' => $status,
			'STATUS_NAME' => CVoxImplantDocuments::GetStatusName($status)
		);
	}

	protected function getCurrentUserId()
	{
		global $USER;
		return $USER->GetID();
	}

	/**
	 * Executes component
	 */
	public function executeComponent()
	{
		if (!Loader::includeModule('voximplant'))
			return false;

		$this->init();
		$this->prepareData();
		if ($this->showTemplate)
			$this->includeComponentTemplate();

		return $this->arResult;
	}
}
