<?
/**
 * Namespace contains functions\classes for different purposes
 * See also
 *      tasks/classes/general/tasktools.php
 *      tasks/tools.php
 */

namespace Bitrix\Tasks;

class Util
{
	public static function trim($arg)
	{
		$arg = (string) $arg;
		if($arg == '')
		{
			return $arg;
		}

		$arg = trim($arg);
		// remove that annoying undying sequences from wysiwyg
		$arg = preg_replace('#(^((\x20)?(\xc2)?\xa0(\x20)?)+|((\x20)?(\xc2)?\xa0(\x20)?)+$)#', '', $arg);

		return $arg;
	}

	public static function escape($arg)
	{
		if(is_array($arg))
		{
			foreach($arg as $i => $value)
			{
				$arg[$i] = static::escape($value);
			}

			return $arg;
		}
		else
		{
			if(is_numeric($arg) && !is_string($arg))
			{
				return $arg;
			}
			else
			{
				return htmlspecialcharsbx($arg);
			}
		}
	}

	public static function replaceUrlParameters($url, array $paramsToAdd = array(), array $paramsToDelete = array(), array $options = array())
	{
		// CHTTP::url*Params() functions does not like #placeholders# in url, so a little trick is needed
		$found = array();
		preg_match_all("/#([a-zA-Z0-9_-]+)#/", $url, $found);

		$match = array();
		if(is_array($found[1]) && !empty($found[1]))
		{
			foreach($found[1] as $holder)
			{
				$match['#'.$holder.'#'] = '__'.$holder.'__';
			}
		}

		if(!empty($match))
		{
			$url = str_replace(array_keys($match), $match, $url);
		}

		// to avoid adding duplicates and delete other params
		$url = \CHTTP::urlDeleteParams($url, array_merge(array_keys($paramsToAdd), $paramsToDelete));
		$url = \CHTTP::urlAddParams($url, $paramsToAdd, $options);

		if(!empty($match))
		{
			$match = array_flip($match);
			$url = str_replace(array_keys($match), $match, $url);
		}

		return $url;
	}

	public static function getParser(array $parameters = array())
	{
		$parser = \Bitrix\Tasks\Integration\Forum::getParser($parameters);
		if($parser == null)
		{
			$parser = \Bitrix\Tasks\Integration\SocialNetwork::getParser($parameters);
		}
		if($parser == null)
		{
			$parser = new \CTextParser();
		}

		return $parser;
	}

	public static function getServerTimeZoneOffset()
	{
		$localTime = new \DateTime();
		return $localTime->getOffset();
	}
}
