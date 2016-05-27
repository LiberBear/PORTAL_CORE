<?php

namespace Bitrix\Crm;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class EntityPreset
{
	const Undefined = 0;
	const Requisite = 8;    // refresh FirstEntityType and LastEntityType constants (see the CCrmOwnerType constants)
	const FirstEntityType = 8;
	const LastEntityType = 8;

	const NO_ERRORS = 0;
	const ERR_DELETE_PRESET_USED = 1;

	public static function getEntityTypes()
	{
		$entityTypes = Array(
			self::Requisite => array(
				'CODE' => 'CRM_REQUISITE',
				'NAME' => GetMessage('CRM_ENTITY_TYPE_REQUISITE'),
				'DESC' => GetMessage('CRM_ENTITY_TYPE_REQUISITE_DESC')
			)
		);
		return $entityTypes;
	}

	public static function checkEntityType($entityTypeId)
	{
		if(!is_numeric($entityTypeId))
			return false;

		$entityTypeId = intval($entityTypeId);

		return $entityTypeId >= self::FirstEntityType && $entityTypeId <= self::LastEntityType;
	}

	public static function getCurrentCountryId()
	{
		return (int)\COption::GetOptionInt("crm", "crm_requisite_preset_country_id", 0);
	}

	public static function setCurrentCountryId($countryId)
	{
		\COption::SetOptionInt("crm", "crm_requisite_preset_country_id", $countryId);
	}

	public static function getCountryList()
	{
		$result = array();

		$countries = GetCountryArray();
		if (isset($countries['reference_id'])
			&& isset($countries['reference'])
			&& is_array($countries['reference_id'])
			&& is_array($countries['reference']))
		{
			$refId = &$countries['reference_id'];
			$ref = &$countries['reference'];
			foreach ($ref as $id => $val)
				$result[$refId[$id]] = $val;
		}

		return $result;
	}

	public static function getEntityTypeCode($entityTypeId)
	{
		$entityTypeId = (int)$entityTypeId;
		$types = self::getEntityTypes();

		return isset($types[$entityTypeId]['CODE']) ? $types[$entityTypeId]['CODE'] : '';
	}

	public static function isUTFMode()
	{
		if (Main\Config\Option::get('crm', 'entity_preset_force_utf_mode', 'N') === 'Y')
			return true;

		if (defined('BX_UTF') && BX_UTF)
			return true;

		return false;
	}

	public function getFields()
	{
		return PresetTable::getMap();
	}

	public function getSettingsFieldsInfo()
	{
		return array(
			'ID' => array('data_type' => 'integer'),
			'FIELD_NAME' => array('data_type' => 'string'),
			'FIELD_TITLE' => array('data_type' => 'string'),
			'SORT' => array('data_type' => 'integer'),
			'IN_SHORT_LIST' => array('data_type' => 'boolean')
		);
	}

	public function getList($params)
	{
		return PresetTable::getList($params);
	}

	public function getCountByFilter($filter = array())
	{
		return PresetTable::getCountByFilter($filter);
	}

	public function getById($id)
	{
		$result = PresetTable::getByPrimary($id);
		$row = $result->fetch();

		return (is_array($row)? $row : null);
	}

	public function add($fields, $options = array())
	{
		unset($fields['ID'], $fields['DATE_MODIFY'], $fields['MODIFY_BY_ID']);
		$fields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
		$fields['CREATED_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		return PresetTable::add($fields);
	}

	public function update($id, $fields, $options = array())
	{
		unset($fields['DATE_CREATE'], $fields['CREATED_BY_ID']);
		$fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
		$fields['MODIFY_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		return PresetTable::update($id, $fields);
	}

	public function delete($id, $options = array())
	{
		$id = (int)$id;
		$row = $this->getList(
			array(
				'filter' => array('=ID' => $id),
				'select' => array('ENTITY_TYPE_ID')
			)
		)->fetch();
		if (is_array($row) && isset($row['ENTITY_TYPE_ID']))
		{
			$entityTypeId = (int)$row['ENTITY_TYPE_ID'];

			if ($entityTypeId === self::Requisite)
			{
				$requisite = new EntityRequisite();
				$row = $requisite->getList(
					array(
						'filter' => array('=PRESET_ID' => $id),
						'select' => array('ID'),
						'limit' => 1
					)
				)->fetch();
				if (is_array($row))
				{
					$result = new Entity\DeleteResult();
					$result->addError(
						new Main\Error(
							GetMessage('CRM_ENTITY_PRESET_ERR_DELETE_PRESET_USED'),
							self::ERR_DELETE_PRESET_USED
						)
					);
					return $result;
				}
			}
		}

		return PresetTable::delete($id);
	}

	public function extractFieldNames(array $settings)
	{
		$results = array();
		foreach($settings as $field)
		{
			if(isset($field['FIELD_NAME']))
			{
				$results[] = $field['FIELD_NAME'];
			}
		}
		return $results;
	}

	public function settingsGetFields(array $settings)
	{
		return (isset($settings['FIELDS']) && is_array($settings['FIELDS']) ? $settings['FIELDS'] : array());
	}

	public function settingsAddField(&$settings, $field)
	{
		if (!is_array($settings) || !is_array($field) || empty($field)
			|| !isset($field['FIELD_NAME']) || empty($field['FIELD_NAME']))
		{
			return false;
		}

		$maxId = 0;
		if (isset($settings['LAST_FIELD_ID']))
		{
			$maxId = (int)$settings['LAST_FIELD_ID'];
		}
		else
		{
			if (is_array($settings['FIELDS']))
			{
				foreach ($settings['FIELDS'] as $field)
				{
					$curId = (int)$field['ID'];
					if ($curId > $maxId)
						$maxId = $curId;
				}
			}
		}
		$id = $maxId + 1;

		$newField = array();
		$newField['ID'] = $id;
		$newField['FIELD_NAME'] = '';
		if (isset($field['FIELD_NAME']))
		{
			$newField['FIELD_NAME'] = substr(strval($field['FIELD_NAME']), 0, 255);
			if ($newField['FIELD_NAME'] === false)
				$newField['FIELD_NAME'] = '';
		}
		$newField['FIELD_TITLE'] = '';
		if (isset($field['FIELD_TITLE']))
		{
			$newField['FIELD_TITLE'] = substr(strval($field['FIELD_TITLE']), 0, 255);
			if ($newField['FIELD_TITLE'] === false)
				$newField['FIELD_TITLE'] = '';
		}
		$newField['IN_SHORT_LIST'] = 'N';
		if (isset($field['IN_SHORT_LIST'])
			&& $field['IN_SHORT_LIST'] === 'Y')
		{
			$newField['IN_SHORT_LIST'] = 'Y';
		}
		$newField['SORT'] = 500;
		if (isset($field['SORT']))
			$newField['SORT'] = (int)$field['SORT'];

		if (!is_array($settings['FIELDS']))
			$settings['FIELDS'] = array();

		$duplicate = false;
		foreach ($settings['FIELDS'] as $fieldInfo)
		{
			if ($fieldInfo['FIELD_NAME'] === $newField['FIELD_NAME'])
			{
				$duplicate = true;
				break;
			}
		}
		unset($fieldInfo);
		if ($duplicate)
			return false;

		$settings['LAST_FIELD_ID'] = $id;
		$settings['FIELDS'][] = $newField;

		return true;
	}

	public function settingsUpdateField(&$settings, $field, $fieldIndex = null)
	{
		if (!is_array($settings) || !is_array($settings['FIELDS']) || !is_array($field) || empty($field)
			|| !isset($field['ID']) || intval($field['ID']) <= 0
			|| (isset($field['FIELD_NAME']) && empty($field['FIELD_NAME'])))
		{
			return false;
		}
		$id = (int)$field['ID'];
		if ($fieldIndex === null)
		{
			foreach ($settings['FIELDS'] as $index => $fieldData)
			{
				if (isset($fieldData['ID']) && intval($fieldData['ID']) === $id)
					$fieldIndex = $index;
			}
			unset($index, $fieldData);
		}
		if ($fieldIndex === null || $id !== intval($settings['FIELDS'][$fieldIndex]['ID']))
			return false;
		unset($id);

		$numberOfModified = 0;
		foreach ($field as $fieldName => $fieldValue)
		{
			$value = null;
			$fieldModified = true;
			if ($fieldName === 'FIELD_NAME' || $fieldName === 'FIELD_TITLE')
			{
				$value = substr(strval($fieldValue), 0, 255);
				if ($value === false)
					$value = '';
			}
			else if ($fieldName === 'IN_SHORT_LIST')
			{
				$value = ($fieldValue === 'Y') ? 'Y' : 'N';
			}
			else if ($fieldName === 'SORT')
			{
				$value = (int)$fieldValue;
			}
			else
			{
				$fieldModified = false;
			}

			if ($fieldModified)
			{
				$settings['FIELDS'][$fieldIndex ][$fieldName] = $value;
				$numberOfModified++;
			}
		}
		
		if ($numberOfModified <= 0)
			return false;
		
		return true;
	}

	public function settingsDeleteField(&$settings, $id, $fieldIndex = null)
	{
		$id = (int)$id;
		if (!is_array($settings) || !is_array($settings['FIELDS']) || $id <= 0)
			return false;
		if ($fieldIndex === null)
		{
			foreach ($settings['FIELDS'] as $index => $fieldData)
			{
				if (isset($fieldData['ID']) && intval($fieldData['ID']) === $id)
					$fieldIndex = intval($index);
			}
			unset($index, $fieldData);
		}
		if ($fieldIndex === null || $id !== intval($settings['FIELDS'][$fieldIndex]['ID']))
			return false;
		unset($id);

		unset($settings['FIELDS'][$fieldIndex]);

		if (empty($settings['FIELDS']))
			$settings['LAST_FIELD_ID'] = 0;

		return true;
	}

	public function getSettingsFieldsOfPresets($entityTypeId, $type = 'all', $options = array())
	{
		$result = array();

		if (!is_array($options))
			$options = array();

		$arrangeByCountry = false;
		if (isset($options['ARRANGE_BY_COUNTRY'])
			&& ($options['ARRANGE_BY_COUNTRY'] === true
				|| strtoupper(strval($options['ARRANGE_BY_COUNTRY'])) === 'Y'))
		{
			$arrangeByCountry = true;
		}

		$filterByCountryIds = array();
		if (isset($options['FILTER_BY_COUNTRY_IDS']))
		{
			if (!is_array($options['FILTER_BY_COUNTRY_IDS']))
			{
				$filterByCountryIds = array((int)$options['FILTER_BY_COUNTRY_IDS']);
			}
			else
			{
				foreach ($options['FILTER_BY_COUNTRY_IDS'] as $id)
					$filterByCountryIds[] = (int)$id;
			}
			$arrangeByCountry = true;
		}
		$filterByCountry = !empty($filterByCountryIds);

		$filter = array('=ENTITY_TYPE_ID' => $entityTypeId);

		switch ($type)
		{
			case 'all':
				break;

			case 'active':
				$filter['=ACTIVE'] = 'Y';
				break;

			case 'inactive':
				$filter['=ACTIVE'] = 'N';
				break;
		}

		if ($this->checkEntityType($entityTypeId))
		{
			$fieldsAllowed = array();
			if ($entityTypeId === self::Requisite)
			{
				$requisite = new EntityRequisite();
				$fieldsAllowed = array_merge($requisite->getRqFields(), $requisite->getUserFields());
				unset($requisite);
			}

			$iResult = array();
			
			$select = array('ID');
			if ($arrangeByCountry)
				$select[] = 'COUNTRY_ID';
			$select[] =  'SETTINGS';

			if ($filterByCountry)
				$filter['=COUNTRY_ID'] = $filterByCountryIds;
			
			$res = $this->getList(array(
				'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
				'filter' => $filter,
				'select' => $select
			));
			while ($row = $res->fetch())
			{
				if (is_array($row['SETTINGS']))
				{
					$fields = $this->settingsGetFields($row['SETTINGS']);
					if (!empty($fields) && (!$arrangeByCountry || isset($row['COUNTRY_ID'])))
					{
						$countryId = (int)$row['COUNTRY_ID'];
						if (empty($filterByCountryIds) || in_array($countryId, $filterByCountryIds, true))
						{
							foreach ($fields as $fieldInfo)
							{
								if ($arrangeByCountry)
								{
									if ($countryId > 0)
									{
										if (isset($fieldInfo['FIELD_NAME'])
											&& !isset($iResult[$countryId][$fieldInfo['FIELD_NAME']]))
										{
											$iResult[$countryId][$fieldInfo['FIELD_NAME']] = true;
										}
									}
								}
								else
								{

									if (isset($fieldInfo['FIELD_NAME']) && !isset($iResult[$fieldInfo['FIELD_NAME']]))
										$iResult[$fieldInfo['FIELD_NAME']] = true;
								}
							}
						}
					}
				}
			}
			if ($arrangeByCountry)
			{
				$countryIds = array_keys($iResult);
				$includeZeroCountry = in_array(0, $filterByCountryIds, true);

				foreach ($fieldsAllowed as $fieldName)
				{
					if (!$filterByCountry || $includeZeroCountry)
						$result[0][] = $fieldName;

					foreach ($countryIds as $countryId)
					{
						if (isset($iResult[$countryId][$fieldName]))
							$result[$countryId][] = $fieldName;
					}
				}
			}
			else
			{
				foreach ($fieldsAllowed as $fieldName)
				{
					if (isset($iResult[$fieldName]))
						$result[] = $fieldName;
				}
			}
			unset($iResult);
		}

		return $result;
	}
}
