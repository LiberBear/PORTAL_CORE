<?php
namespace Bitrix\Crm\Integration;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Crm;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityAddress;
use Bitrix\Socialservices;

class ClientResolver
{
	const TYPE_UNKNOWN = 0;
	const TYPE_COMPANY = 1;
	const TYPE_PERSON = 2;
	const PROP_ITIN = 'ITIN'; //Individual Taxpayer Identification Number

	/** @var Socialservices\Properties\Client */
	private static $client = null;
	/** @var boolean|null */
	private static $isOnline = null;
	protected static function getClient()
	{
		Loader::includeModule('socialservices');
		if(self::$client === null)
		{
			self::$client = new Socialservices\Properties\Client();
		}

		return self::$client;
	}
	public static function isOnline()
	{
		if(self::$isOnline === null)
		{
			self::$isOnline = self::getClient()->isServiceOnline();
		}
		return self::$isOnline;
	}
	public static function isEnabled($countryID)
	{
		if(!is_int($countryID))
		{
			$countryID = (int)$countryID;
		}

		//For Russia only
		return $countryID === 1 && self::isOnline();
	}
	public static function resolve($propertyTypeID, $propertyValue, $countryID = 1)
	{
		if(!is_int($countryID))
		{
			$countryID = (int)$countryID;
		}

		if($countryID !== 1)
		{
			throw new Main\NotSupportedException("Country ID: '{$countryID}' is not supported in current context.");
		}

		$dateFormat = Date::convertFormatToPhp(FORMAT_DATE);
		$nameFormat = Crm\Format\PersonNameFormatter::LastFirstSecondFormat;
		$alphaRegex = "/[[:alpha:]]/".BX_UTF_PCRE_MODIFIER;
		$results = array();

		Loc::loadMessages(__FILE__);

		if($propertyTypeID === self::PROP_ITIN)
		{
			$info = self::getClient()->getByInn($propertyValue);
			if(is_array($info))
			{
				$caption = '';
				$fields = null;
				$len = strlen(isset($info['INN']) ? $info['INN'] : '');
				$clientType = self::TYPE_UNKNOWN;
				if($len === 10)
				{
					$clientType = self::TYPE_COMPANY;
				}
				elseif($len === 12)
				{
					$clientType = self::TYPE_PERSON;
				}

				if($clientType === self::TYPE_COMPANY)
				{

					$fullName = isset($info['NAME']) ? $info['NAME'] : '';
					$shortName = isset($info['NAME_SHORT']) ? $info['NAME_SHORT'] : '';

					$fields = array(
						EntityRequisite::INN => isset($info['INN']) ? $info['INN'] : '',
						EntityRequisite::KPP => isset($info['KPP']) ? $info['KPP'] : '',
						EntityRequisite::OGRN => isset($info['OGRN']) ? $info['OGRN'] : '',
						EntityRequisite::OKVED => isset($info['OKVED_CODE']) ? $info['OKVED_CODE'] : '',
						EntityRequisite::COMPANY_NAME => $shortName,
						EntityRequisite::COMPANY_FULL_NAME => $fullName,
						EntityRequisite::IFNS => isset($info['TAX_REGISTRAR_NAME']) ? $info['TAX_REGISTRAR_NAME'] : ''
					);
					$caption = $shortName !== '' ? $shortName : $fullName;

					$registrationDate = isset($info['CREATION_REGISTRATION_DATE'])
						? $info['CREATION_REGISTRATION_DATE'] : '';

					if($registrationDate === '' && isset($info['CREATION_OGRN_DATE']))
					{
						$registrationDate = $info['CREATION_OGRN_DATE'];
					}

					if($registrationDate !== '')
					{
						try
						{
							$d = new Date($registrationDate, 'Y-m-d');
							$fields[EntityRequisite::COMPANY_REG_DATE] = $d->format($dateFormat);
						}
						catch(Main\ObjectException $e)
						{
						}
					}

					$address1Parts = array();

					$street = isset($info['ADDRESS_STREET_NAME']) ? $info['ADDRESS_STREET_NAME'] : '';
					if($street !== '')
					{
						if(isset($info['ADDRESS_STREET_TYPE']) && $info['ADDRESS_STREET_TYPE'] === GetMessage('CRM_CLIENT_ADDRESS_STREET_TYPE'))
						{
							$street = GetMessage(
								'CRM_CLIENT_ADDRESS_TEMPLATE_STREET',
								array('#STREET#' => $street)
							);
						}
						$address1Parts[] = $street;
					}

					$house = isset($info['ADDRESS_HOUSE']) ? $info['ADDRESS_HOUSE'] : '';
					if($house !== '')
					{
						if(preg_match($alphaRegex, $house) === 0)
						{
							$address1Parts[] = GetMessage(
								'CRM_CLIENT_ADDRESS_TEMPLATE_HOUSE',
								array('#HOUSE#' => $house)
							);
						}
						else
						{
							$address1Parts[] = $house;
						}
					}

					$building = isset($info['ADDRESS_BUILDING']) ? $info['ADDRESS_BUILDING'] : '';
					if($building !== '')
					{
						if(preg_match($alphaRegex, $building) === 0)
						{
							$address1Parts[] = GetMessage(
								'CRM_CLIENT_ADDRESS_TEMPLATE_BUILDING',
								array('#BUILDING#' => $building)
							);
						}
						else
						{
							$address1Parts[] = $building;
						}
					}

					$address1 = implode(', ', $address1Parts);
					$address2 = isset($info['ADDRESS_FLAT']) ? $info['ADDRESS_FLAT'] : '';

					$city = isset($info['ADDRESS_CITY_NAME']) ? $info['ADDRESS_CITY_NAME'] : '';
					$cityType = isset($info['ADDRESS_CITY_TYPE']) ? $info['ADDRESS_CITY_TYPE'] : '';
					$region = isset($info['ADDRESS_AREA_NAME']) ? $info['ADDRESS_AREA_NAME'] : '';
					$province = isset($info['ADDRESS_REGION_NAME']) ? $info['ADDRESS_REGION_NAME'] : '';
					$provinceType = isset($info['ADDRESS_REGION_TYPE']) ? $info['ADDRESS_REGION_TYPE'] : '';
					$postalCode = isset($info['ADDRESS_INDEX']) ? $info['ADDRESS_INDEX'] : '';
					if($provinceType === GetMessage('CRM_CLIENT_ADDRESS_CITY_TYPE'))
					{
						$city = $province;
						$province = '';
					}
					elseif($provinceType !== '')
					{
						$province = "{$province} {$provinceType}";
					}

					$settlementName = isset($info['ADDRESS_SETTLEMENT_NAME']) ? $info['ADDRESS_SETTLEMENT_NAME'] : '';
					if($settlementName !== '')
					{
						if($cityType !== '')
						{
							if($cityType === GetMessage('CRM_CLIENT_ADDRESS_CITY_TYPE'))
							{
								$cityType = GetMessage('CRM_CLIENT_ADDRESS_BOROUGH');
							}
							$city = "{$cityType} {$city}";
						}

						$settlementType = isset($info['ADDRESS_SETTLEMENT_TYPE']) ? $info['ADDRESS_SETTLEMENT_TYPE'] : '';
						$settlement = $settlementType !== '' ? "{$settlementType} {$settlementName}" : $settlementName;
						$city = "{$settlement}, {$city}";
					}

					$fields['RQ_ADDR'] = array(
						EntityAddress::Registered => array(
							'ADDRESS_1' => $address1,
							'ADDRESS_2' => $address2,
							'CITY' => $city,
							'REGION' => $region,
							'PROVINCE' => $province,
							'POSTAL_CODE' => $postalCode,
							'COUNTRY' => GetMessage('CRM_CLIENT_ADDRESS_COUNTRY_RUSSIA'),
						)
					);

					$directorName = '';
					$accoutantName = '';
					if(isset($info['OFFICIALS']) && is_array($info['OFFICIALS']))
					{
						foreach($info['OFFICIALS'] as $person)
						{
							$positionType = isset($person['POSITION_TYPE']) ? (int)$person['POSITION_TYPE'] : 0;
							if($positionType === 2 && $directorName === '')
							{
								$directorName = \CCrmContact::PrepareFormattedName($person, $nameFormat);
							}
							elseif($positionType === 3 && $accoutantName === '')
							{
								$accoutantName = \CCrmContact::PrepareFormattedName($person, $nameFormat);
							}

							if($directorName !== '' && $accoutantName !== '')
							{
								break;
							}
						}
					}

					if($directorName !== '')
					{
						$fields[EntityRequisite::COMPANY_DIRECTOR] = $directorName;
					}

					if($accoutantName !== '')
					{
						$fields[EntityRequisite::COMPANY_ACCOUNTANT] = $accoutantName;
					}
				}
				elseif($clientType === self::TYPE_PERSON)
				{
					$firstName = isset($info['NAME']) ? $info['NAME'] : '';
					$secondName = isset($info['SECOND_NAME']) ? $info['SECOND_NAME'] : '';
					$lastName = isset($info['LAST_NAME']) ? $info['LAST_NAME'] : '';

					$fullName = \CCrmContact::PrepareFormattedName(
						array('NAME' => $firstName, 'SECOND_NAME' => $secondName, 'LAST_NAME' => $lastName),
						$nameFormat
					);

					$caption =  $fullName;

					$fields = array(
						EntityRequisite::INN => isset($info['INN']) ? $info['INN'] : '',
						EntityRequisite::OGRNIP => isset($info['OGRNIP']) ? $info['OGRNIP'] : '',
						EntityRequisite::OKVED => isset($info['OKVED_CODE']) ? $info['OKVED_CODE'] : '',
						EntityRequisite::PERSON_FIRST_NAME => $firstName,
						EntityRequisite::PERSON_SECOND_NAME => $secondName,
						EntityRequisite::PERSON_LAST_NAME => $lastName,
						EntityRequisite::PERSON_FULL_NAME => $fullName,
						EntityRequisite::IFNS => isset($info['TAX_AUTHORITY_NAME']) ? $info['TAX_AUTHORITY_NAME'] : ''
					);
				}

				if(is_array($fields))
				{
					$results[] = array('caption' => $caption,  'fields' => $fields);
				}
			}
		}
		else
		{
			throw new Main\ArgumentOutOfRangeException('propertyTypeID', self::PROP_ITIN, self::PROP_ITIN);
		}

		return $results;
	}
}