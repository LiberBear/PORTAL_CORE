<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Util;

abstract class Type
{
	public static function isIterable($arg)
	{
		return is_array($arg); // todo: or implements ArrayAccess
	}

	public static function checkDateTimeString($arg)
	{
		$arg = \Bitrix\Tasks\Util::trim($arg);
		if($arg == '' || !\CheckDateTime($arg))
		{
			return false;
		}

		return $arg;
	}

	/////////////////////////////////
	// helper functions for checking elements of component parameters and other similar places

	public static function checkYNKey(array &$data, $paramName)
	{
		if((string) $paramName != '' && array_key_exists($paramName, $data))
		{
			$data[$paramName] = $data[$paramName] == 'Y' ? 'Y' : 'N';
		}
	}

	public static function checkBooleanKey(array &$data, $paramName, $default = null)
	{
		if((string) $paramName != '' && array_key_exists($paramName, $data))
		{
			if($data[$paramName] != 'Y' && $data[$paramName] != 'N' && $default !== null)
			{
				$data[$paramName] = $default;
			}
			else
			{
				$data[$paramName] = $data[$paramName] == 'Y';
			}
		}
	}

	public static function checkEnumKey(array &$data, $paramName, array $enum, $default = null)
	{
		if((string) $paramName != '' && array_key_exists($paramName, $data))
		{
			if(!in_array($data[$paramName], $enum))
			{
				if($default !== null)
				{
					$data[$paramName] = $default;
				}
				else
				{
					unset($data[$paramName]);
					return false; // value was incorrect
				}
			}
		}

		return true; // value was correct or was replaced with the default one (which is assumed to be correct)
	}

	public static function checkArrayOfUPIntegerKey(array &$data, $paramName)
	{
		if((string) $paramName != '' && array_key_exists($paramName, $data))
		{
			$data[$paramName] = static::castToArrayOfUniquePositiveInteger($data[$paramName]);
		}
	}

	private static function castToArrayOfUniquePositiveInteger($arg)
	{
		if(isset($arg))
		{
			if(!is_array($arg))
			{
				$arg = array();
			}
			else
			{
				foreach($arg as $i => &$item)
				{
					$item = intval($item);
					if($item <= 0)
					{
						unset($arg[$i]);
					}
				}
				unset($item);

				$arg = array_unique($arg);
			}
		}

		return $arg;
	}

	public static function serializeArray($data, $returnFalse = false)
	{
		if(!is_array($data))
		{
			$data = $returnFalse ? false : array();
		}

		return serialize($data);
	}

	public static function unSerializeArray($data)
	{
		if(!\CheckSerializedData($data))
		{
			return array();
		}

		$data = unserialize($data);
		return is_array($data) ? $data : array();
	}

	public static function normalizeArray($data)
	{
		if(!is_array($data))
		{
			return array();
		}

		foreach($data as $i => $value)
		{
			if((string) $value == '')
			{
				unset($data[$i]);
			}
		}

		return $data;
	}
}