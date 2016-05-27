<?php
namespace Bitrix\Crm\Widget\Data;
use Bitrix\Main;

abstract class DataSourceFactory
{
	public static function checkSettings(array $settings)
	{
		return !empty($settings) && isset($settings['name']) && $settings['name'] !== '';
	}
	public static function create(array $settings, $userID = 0, $enablePermissionCheck = true)
	{
		$name = isset($settings['name']) ? strtoupper($settings['name']) : '';
		if($name === DealSumStatistics::TYPE_NAME)
		{
			return new DealSumStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadSumStatistics::TYPE_NAME)
		{
			return new LeadSumStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealInvoiceStatistics::TYPE_NAME)
		{
			return new DealInvoiceStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealActivityStatistics::TYPE_NAME)
		{
			return new DealActivityStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadActivityStatistics::TYPE_NAME)
		{
			return new LeadActivityStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealStageHistory::TYPE_NAME)
		{
			return new DealStageHistory($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadStatusHistory::TYPE_NAME)
		{
			return new LeadStatusHistory($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealInWork::TYPE_NAME)
		{
			return new DealInWork($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadInWork::TYPE_NAME)
		{
			return new LeadInWork($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === DealIdle::TYPE_NAME)
		{
			return new DealIdle($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadIdle::TYPE_NAME)
		{
			return new LeadIdle($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadNew::TYPE_NAME)
		{
			return new LeadNew($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadConversionStatistics::TYPE_NAME)
		{
			return new LeadConversionStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadConversionRate::TYPE_NAME)
		{
			return new LeadConversionRate($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === LeadJunk::TYPE_NAME)
		{
			return new LeadJunk($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === InvoiceInWork::TYPE_NAME)
		{
			return new InvoiceInWork($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === InvoiceSumStatistics::TYPE_NAME)
		{
			return new InvoiceSumStatistics($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === InvoiceOverdue::TYPE_NAME)
		{
			return new InvoiceOverdue($settings, $userID, $enablePermissionCheck);
		}
		elseif($name === ExpressionDataSource::TYPE_NAME)
		{
			return new ExpressionDataSource($settings, $userID);
		}
		else
		{
			throw new Main\NotSupportedException("The data source '{$name}' is not supported in current context.");
		}
	}

	public static function getPresets()
	{
		return array_merge(
			DealSumStatistics::getPresets(),
			DealInWork::getPresets(),
			DealIdle::getPresets(),
			DealActivityStatistics::getPresets(),
			DealInvoiceStatistics::getPresets(),
			LeadSumStatistics::getPresets(),
			LeadActivityStatistics::getPresets(),
			LeadInWork::getPresets(),
			LeadIdle::getPresets(),
			LeadNew::getPresets(),
			LeadConversionStatistics::getPresets(),
			LeadConversionRate::getPresets(),
			LeadJunk::getPresets(),
			InvoiceSumStatistics::getPresets(),
			InvoiceInWork::getPresets(),
			InvoiceOverdue::getPresets()
		);
	}

	public static function getCategiries()
	{
		$categories = array();
		DealInWork::prepareCategories($categories);
		DealIdle::prepareCategories($categories);
		LeadNew::prepareCategories($categories);
		LeadInWork::prepareCategories($categories);
		LeadIdle::prepareCategories($categories);
		LeadConversionStatistics::prepareCategories($categories);
		LeadConversionRate::prepareCategories($categories);
		LeadJunk::prepareCategories($categories);
		InvoiceInWork::prepareCategories($categories);
		InvoiceOverdue::prepareCategories($categories);
		return array_values($categories);
	}
}