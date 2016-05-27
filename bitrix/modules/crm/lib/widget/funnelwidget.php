<?php
namespace Bitrix\Crm\Widget;

use Bitrix\Main;
use Bitrix\Crm\History\HistoryEntryType;
use Bitrix\Crm\Widget\Data\DealStageHistory;
use Bitrix\Crm\Widget\Data\LeadStatusHistory;
use Bitrix\Crm\Widget\Data\InvoiceStatusHistory;
use Bitrix\Crm\PhaseSemantics;

class FunnelWidget extends Widget
{
	/** @var int $entityTypeID */
	protected $entityTypeID = \CCrmOwnerType::Undefined;

	public function __construct(array $settings, Filter $filter, $userID = 0, $enablePermissionCheck = true)
	{
		parent::__construct($settings, $filter, $userID, $enablePermissionCheck);

		$this->entityTypeID = \CCrmOwnerType::ResolveID($this->getSettingString('entityTypeName', ''));
		if($this->entityTypeID === \CCrmOwnerType::Undefined)
		{
			$this->entityTypeID = \CCrmOwnerType::Deal;
		}

		$this->configs = array();
		$configs = $this->getSettingArray('configs', array());
		foreach($configs as $config)
		{
			$this->configs[] = new WidgetConfig($config);
		}
	}
	/**
	* @return int
	*/
	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}
	/**
	* @return array
	*/
	public function prepareData()
	{
		$totals = array();

		if($this->entityTypeID === \CCrmOwnerType::Lead)
		{
			$statuses = \CCrmStatus::GetStatusList('STATUS');
			foreach($statuses as $k => $v)
			{
				$semanticID = \CCrmLead::GetSemanticID($k);
				if($semanticID === PhaseSemantics::FAILURE)
				{
					continue;
				}
				$totals[$k] = array('ID' => $k, 'NAME' => $v, 'TOTAL' => 0);
			}

			$source = new LeadStatusHistory(array(), $this->userID, $this->enablePermissionCheck);
		}
		elseif($this->entityTypeID === \CCrmOwnerType::Deal)
		{
			$stages = \CCrmStatus::GetStatusList('DEAL_STAGE');
			foreach($stages as $k => $v)
			{
				$semanticID = \CCrmDeal::GetSemanticID($k);
				if($semanticID === PhaseSemantics::FAILURE)
				{
					continue;
				}
				$totals[$k] = array('ID' => $k, 'NAME' => $v, 'TOTAL' => 0);
			}

			$source = new DealStageHistory(array(), $this->userID, $this->enablePermissionCheck);
		}
		elseif($this->entityTypeID === \CCrmOwnerType::Invoice)
		{
			$stages = \CCrmStatus::GetStatusList('INVOICE_STATUS');
			foreach($stages as $k => $v)
			{
				$semanticID = \CCrmInvoice::GetSemanticID($k);
				if($semanticID === PhaseSemantics::FAILURE)
				{
					continue;
				}
				$totals[$k] = array('ID' => $k, 'NAME' => $v, 'TOTAL' => 0);
			}

			$source = new InvoiceStatusHistory(array(), $this->userID, $this->enablePermissionCheck);
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeID);
			throw new Main\NotSupportedException("The '{$entityTypeName}' is not supported in current context");
		}

		//CREATION
		$this->filter->setExtras(array('typeID' => HistoryEntryType::CREATION));
		$values = $source->getList(array('filter' => $this->filter));
		$this->prepareTotals($values, $totals);
		//MODIFICATION
		$this->filter->setExtras(array('typeID' => HistoryEntryType::MODIFICATION));
		$values = $source->getList(array('filter' => $this->filter));
		$this->prepareTotals($values, $totals);
		//FINALIZATION
		$this->filter->setExtras(array('typeID' => HistoryEntryType::FINALIZATION));
		$values = $source->getList(array('filter' => $this->filter));
		$this->prepareTotals($values, $totals);

		$items = array();
		foreach($totals as $total)
		{
			if($total['TOTAL'] > 0)
			{
				$items[] = $total;
			}
		}
		return array('items' => $items, 'valueField' => 'TOTAL', 'titleField' => 'NAME');
	}
	/**
	* @return boolean
	*/
	protected function prepareTotals(array $values, array &$totals)
	{
		if($this->entityTypeID === \CCrmOwnerType::Lead || $this->entityTypeID === \CCrmOwnerType::Invoice)
		{
			foreach($values as $value)
			{
				$statusID = isset($value['STATUS_ID']) ? $value['STATUS_ID'] : '';
				$qty = isset($value['QTY']) ? (int)$value['QTY'] : 0;
				if(isset($totals[$statusID]))
				{
					$totals[$statusID]['TOTAL'] += $qty;
				}
			}
		}
		elseif($this->entityTypeID === \CCrmOwnerType::Deal)
		{
			foreach($values as $value)
			{
				$stageID = isset($value['STAGE_ID']) ? $value['STAGE_ID'] : '';
				$qty = isset($value['QTY']) ? (int)$value['QTY'] : 0;
				if(isset($totals[$stageID]))
				{
					$totals[$stageID]['TOTAL'] += $qty;
				}
			}
		}
	}
	/**
	* @return array
	*/
	public function initializeDemoData(array $data)
	{
		if(!(isset($data['items']) && is_array($data['items'])))
		{
			return $data;
		}

		if($this->entityTypeID === \CCrmOwnerType::Lead)
		{
			$statuses = \CCrmStatus::GetStatusList('STATUS');
			foreach($data['items'] as &$item)
			{
				$statusID = isset($item['ID']) ? $item['ID'] : '';
				if($statusID !== '' && isset($statuses[$statusID]))
				{
					$item['NAME'] = $statuses[$statusID];
				}
			}
			unset($item);
		}
		elseif($this->entityTypeID === \CCrmOwnerType::Deal)
		{
			$stages = \CCrmStatus::GetStatusList('DEAL_STAGE');
			foreach($data['items'] as &$item)
			{
				$stageID = isset($item['ID']) ? $item['ID'] : '';
				if($stageID !== '' && isset($stages[$stageID]))
				{
					$item['NAME'] = $stages[$stageID];
				}
			}
			unset($item);
		}
		if($this->entityTypeID === \CCrmOwnerType::Invoice)
		{
			$statuses = \CCrmStatus::GetStatusList('INVOICE_STATUS');
			foreach($data['items'] as &$item)
			{
				$statusID = isset($item['ID']) ? $item['ID'] : '';
				if($statusID !== '' && isset($statuses[$statusID]))
				{
					$item['NAME'] = $statuses[$statusID];
				}
			}
			unset($item);
		}
		return $data;
	}
}