<?php
namespace Bitrix\Crm\Color;
use Bitrix\Main;
class DealStageColorScheme extends PhaseColorScheme
{
	public function __construct()
	{
		parent::__construct('CONFIG_STATUS_DEAL_STAGE');
	}
	/** @var DealStageColorScheme|null  */
	private static $current = null;

	/**
	 * Get default element color by semantic ID.
	 * @param string $stageID Deal stage ID.
	 * @return string
	 */
	public static function getDefaultColorByStage($stageID)
	{
		return self::getDefaultColorBySemantics(\CCrmDeal::GetSemanticID($stageID));
	}
	/**
	 * Get default color for element.
	 * @param string $name Element Name.
	 * @return string
	 */
	public function getDefaultColor($name)
	{
		return self::getDefaultColorByStage($name);
	}
	/**
	 * Setup scheme by default
	 * @return void
	 */
	public function setupByDefault()
	{
		$this->reset();
		$infos = \CCrmDeal::GetStages();
		foreach($infos as $k => $v)
		{
			$this->addElement(new PhaseColorSchemeElement($k, $this->getDefaultColor($k)));
		}
	}
	/**
	 * Get current scheme
	 * @return DealStageColorScheme
	 * @throws Main\ArgumentNullException
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new DealStageColorScheme();
			if(!self::$current->load())
			{
				self::$current->setupByDefault();
			}
		}
		return self::$current;
	}
}