<?php
namespace Bitrix\ImBot;

class Controller
{
	public static function sendAnswer($botName, $command, $params)
	{
		$result = null;

		$botName = trim(preg_replace("/[^a-z]/","", strtolower($botName)));
		if (!$botName)
			return $result;

		foreach ($params as $key => $value)
		{
			$params[$key] = $value == '#EMPTY#'? '': $value;
		}

		if (class_exists('\\Bitrix\\ImBot\\Bot\\'.ucfirst($botName)))
		{
			return call_user_func_array(array('\\Bitrix\\ImBot\\Bot\\'.ucfirst($botName), 'onAnswerAdd'), Array($command, $params));
		}
		return $result;
	}
}