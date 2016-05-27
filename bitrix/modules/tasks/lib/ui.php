<?
/**
 * Namespace contains functions\classes for UI render purposes
 */

namespace Bitrix\Tasks;

class UI
{
	public static function getAvatar($fileId, $width = 50, $height = 50)
	{
		$fileId = intval($fileId);
		if ($fileId < 1) {
			return "";
		}

		$file = \CFile::GetFileArray($fileId);
		if ($file !== false) {
			$fileInfo = \CFile::ResizeImageGet(
				$file,
				array("width" => $width, "height" => $height),
				BX_RESIZE_IMAGE_EXACT,
				false
			);

			return $fileInfo["src"];
		}

		return "";
	}

	public static function formatTimezoneOffsetUTC($offset)
	{
		return 'UTC '.($offset > 0 ? '+' : '-').static::formatTimeAmount($offset, 'HH:MI');
	}

	public static function formatTimeAmount($time, $format = 'HH:MI:SS')
	{
		$time = intval($time);

		// todo: could be other formats, i.e. with T placeholder...

		$printFFormat = '%02d:%02d:%02d';
		if($format == 'HH:MI')
		{
			$printFFormat = '%02d:%02d';
		}

		return sprintf(
			$printFFormat,
			floor($time / 3600),	// hours
			floor($time / 60) % 60,	// minutes
			$time % 60				// seconds
		);
	}

	public static function parseTimeAmount($time)
	{
		$time = trim((string) $time);

		if($time == '')
		{
			return 0;
		}

		// todo: could be other formats
		$found = array();
		if(!preg_match('#^(\d{1,2}):(\d{1,2})$#', $time, $found))
		{
			return 0;
		}

		$h = intval($found[1]);
		$m = intval($found[2]);

		if(($h < 0 || $h > 23 || $m < 0 || $m > 59))
		{
			return 0;
		}

		return $h*3600 + $m*60;
	}

	public static function getHintState()
	{
		$result = array();

		$options = \CUserOptions::getOption('tasks', 'task_hints');
		if(is_array($options))
		{
			foreach($options as $name => $value)
			{
				$result[$name] = $value == 'Y';
			}
		}

		return $result;
	}

	/**
	 * Use when you need to display bbcode-d text as (safe) html
	 *
	 * @param $value
	 * @param array $parameters
	 * @return string
	 *
	 */
	public static function convertBBCodeToHtml($value, array $parameters = array())
	{
		$value = (string) $value;
		if($value == '')
		{
			return '';
		}

		$preset = $parameters['PRESET'] == 'BASIC' ? 'BASIC' : 'FULL';

		if($preset == 'FULL')
		{
			$parser = \Bitrix\Tasks\Util::getParser($parameters);

			if(!empty($parameters["USER_FIELDS"]))
			{
				$parser->arUserfields = $parameters["USER_FIELDS"];
			}

			$rules = array(
				"HTML" => "N",
				"ALIGN" => "Y",
				"ANCHOR" => "Y", "BIU" => "Y",
				"IMG" => "Y", "QUOTE" => "Y",
				"CODE" => "Y", "FONT" => "Y",
				"LIST" => "Y", "SMILES" => "Y",
				"NL2BR" => "Y", "MULTIPLE_BR" => "N",
				"VIDEO" => "Y", "LOG_VIDEO" => "N",
				"SHORT_ANCHOR" => "Y",
				"USERFIELDS" => $parameters["USER_FIELDS"]
			);

			return $parser->convert(
				$value,
				$rules,
				"html",
				array()
			);
		}
		else
		{
			$parser = new \CTextParser();
			$rules = array('ANCHOR' => 'Y', 'BIU' => 'Y', 'HTML' => 'N');
			$parser->allow = $rules;

			return $parser->convertText($value);
		}
	}

	public static function convertBBCodeToHtmlSimple($value)
	{
		return static::convertBBCodeToHtml($value, array('PRESET' => 'BASIC'));
	}

	/**
	 * Use when you need to make your html a little safer
	 *
	 * @param $value
	 * @return string
	 *
	 */
	public static function convertHtmlToSafeHtml($value)
	{
		$value = (string) $value;
		if($value == '')
		{
			return '';
		}

		static $sanitizer;

		if($sanitizer === null)
		{
			$sanitizer = new \CBXSanitizer();
			$sanitizer->setLevel(\CBXSanitizer::SECURE_LEVEL_LOW);
			$sanitizer->addTags(
				array(
					'blockquote' => array('style', 'class', 'id'),
					'colgroup'   => array('style', 'class', 'id'),
					'col'        => array('style', 'class', 'id', 'width', 'height', 'span', 'style')
				)
			);
			$sanitizer->applyHtmlSpecChars(true);

			// if we don't disable this, than text such as "df 1 < 2 dasfa and 5 > 4 will be partially lost"
			$sanitizer->deleteSanitizedTags(false);
		}

		return $sanitizer->sanitizeHtml($value);
	}
}