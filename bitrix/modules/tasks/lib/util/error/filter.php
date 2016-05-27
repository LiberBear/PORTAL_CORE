<?php

namespace Bitrix\Tasks\Util\Error;

class Filter
{
	/**
	 * @param array $values
	 * @return array
	 */
	public function process(array $values = array())
	{
		$result = array();

		foreach($values as $value)
		{
			$error = array(
				'CODE' => $value['CODE'],
				'MESSAGE' => $value['MESSAGE'],
				'TYPE' => $value['TYPE']
			);

			if(!empty($value['DATA']))
			{
				$error['DATA'] = $value['DATA'];
			}

			$result[] = $error;
		}

		return $result;
	}
}