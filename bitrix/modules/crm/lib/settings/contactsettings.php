<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
class ContactSettings
{
	/** @var ContactSettings  */
	private static $current = null;
	/** @var BooleanSetting  */
	private $isOpened = null;

	function __construct()
	{
		$this->isOpened = new BooleanSetting('contact_opened_flag', true);
	}
	/**
	 * Get current instance
	 * @return ContactSettings
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new ContactSettings();
		}
		return self::$current;
	}
	/**
	 * Get value of flag 'OPENED'
	 * @return bool
	 */
	public function getOpenedFlag()
	{
		return $this->isOpened->get();
	}
	/**
	 * Set value of flag 'OPENED'
	 * @param bool $opened Opened Flag.
	 * @return void
	 */
	public function setOpenedFlag($opened)
	{
		$this->isOpened->set($opened);
	}
}