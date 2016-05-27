<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Util\Error;

class Collection extends \Bitrix\Main\Type\Dictionary
{
	const TYPE_FATAL = 		'FATAL';
	const TYPE_WARNING = 	'WARNING';

	protected $fatalCount = 0;

	public function add($code, $message, $type = false, $additionalData = null)
	{
		if($type == false)
		{
			$type = static::TYPE_FATAL;
		}

		$error = array(
			'CODE' => $code,
			'MESSAGE' => 	$message,
			'TYPE' => 		$type
		);

		if($additionalData !== null)
		{
			$error['DATA'] = $additionalData;
		}

		$this->values[] = $error;

		if($type == static::TYPE_FATAL)
		{
			$this->fatalCount++;
		}
	}

	public function checkNoFatals()
	{
		return $this->fatalCount == 0;
	}

	public function checkHasFatals()
	{
		return $this->fatalCount != 0;
	}

	public function getFatals()
	{
		return $this->getOfType(static::TYPE_FATAL);
	}

	public function getWarnings()
	{
		return $this->getOfType(static::TYPE_WARNING);
	}

	public function getAll($flat = false, $filter = null)
	{
		$result = array();

		if(is_object($filter) && method_exists($filter, 'process'))
		{
			$values = $filter->process($this->values);
		}
		else
		{
			$values = $this->values;
		}

		if($flat)
		{
			foreach($values as $value)
			{
				$result[] = $value;
			}
		}
		else
		{
			foreach($values as $value)
			{
				$type = $value['TYPE'];
				unset($value['TYPE']);
				$result[$type][] = $value;
			}
		}

		return $result;
	}

	public function getMessages()
	{
		$result = array();

		foreach($this->values as $value)
		{
			$result[] = $value['MESSAGE'];
		}

		return $result;
	}

	public function checkHasErrorOfType($type)
	{
		foreach($this->values as $value)
		{
			if($value['TYPE'] == $type)
			{
				return true;
			}
		}

		return false;
	}

	public function checkHasErrors()
	{
		return !!count($this->values);
	}

	public function addForeignErrors($other, array $parameters = array('CHANGE_TYPE_TO' => false))
	{
		if($other !== null)
		{
			$parameters['CHANGE_TYPE_TO'] = (string) $parameters['CHANGE_TYPE_TO'];

			if($other instanceof Collection)
			{
				foreach($other->toArray() as $error)
				{
					if($parameters['CHANGE_TYPE_TO'] != '')
					{
						$error['TYPE'] = $parameters['CHANGE_TYPE_TO'];
					}

					$this->values[] = $error;

					if($error['TYPE'] == static::TYPE_FATAL)
					{
						$this->fatalCount++;
					}
				}
			}
			elseif(is_array($other))
			{
				foreach($other as $error)
				{
					// old tasks crud errors
					if(array_key_exists('id', $error) && array_key_exists('text', $error))
					{
						$errorType = $parameters['CHANGE_TYPE_TO'] != '' ? $parameters['CHANGE_TYPE_TO'] : static::TYPE_FATAL;

						$this->values[] = array(
							'CODE' => (string) $error['id'],
							'MESSAGE' => (string) $error['text'],
							'TYPE' => $errorType
						);
					}
					else
					{
						if((string) $error['CODE'] == '')
						{
							continue;
						}

						$errorType = (string) $error['TYPE'];
						if((string) $error['TYPE'] == '')
						{
							$errorType = static::TYPE_FATAL;
						}
						if($parameters['CHANGE_TYPE_TO'] != '')
						{
							$errorType = $parameters['CHANGE_TYPE_TO'];
						}

						$this->values[] = array(
							'CODE' => (string) $error['CODE'],
							'MESSAGE' => (string) $error['MESSAGE'],
							'TYPE' => $errorType
						);
					}

					if($errorType == static::TYPE_FATAL)
					{
						$this->fatalCount++;
					}
				}
			}
		}
	}

	protected function getOfType($type)
	{
		$result = array();
		foreach($this->values as $value)
		{
			if($value['TYPE'] == $type)
			{
				unset($value['TYPE']);
				$result[] = $value;
			}
		}

		return $result;
	}
}