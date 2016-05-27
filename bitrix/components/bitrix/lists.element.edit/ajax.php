<?php
use Bitrix\Lists\Internals\Error\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Lists\Internals\Controller;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!Loader::IncludeModule('lists') || !\Bitrix\Main\Application::getInstance()->getContext()
		->getRequest()->getQuery('action'))
{
	return;
}

Loc::loadMessages(__FILE__);

class ListsElementEditAjaxController extends Controller
{
	/** @var  int */
	protected $iblockId;
	protected $elementId;
	protected $socnetGroupId;
	protected $sectionId = 0;
	/** @var  string */
	protected $iblockTypeId;
	protected $listPerm;

	protected function listOfActions()
	{
		return array(
			'completeWorkflow' => array(
				'method' => array('POST'),
			),
		);
	}

	protected function processActionCompleteWorkflow()
	{
		$this->checkRequiredPostParams(
			array('workflowId', 'iblockTypeId', 'elementId', 'iblockId', 'sectionId', 'socnetGroupId', 'action')
		);

		$this->iblockTypeId = $this->request->getPost('iblockTypeId');
		$this->iblockId = $this->request->getPost('iblockId');
		$this->socnetGroupId = intval($this->request->getPost('socnetGroupId'));
		$this->sectionId = $this->request->getPost('sectionId');

		$this->checkPermission();
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$workflowId = $this->request->getPost('workflowId');
		$this->elementId = $this->request->getPost('elementId');

		$listError = CLists::completeWorkflow(
			$workflowId,
			$this->iblockTypeId,
			$this->elementId,
			$this->iblockId,
			$this->request->getPost('action')
		);

		if(!empty($listError))
		{
			$this->errorCollection->add(array(new Error($listError)));
		}
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}

		$this->sendJsonSuccessResponse(array('message' => Loc::getMessage('LISTS_MESSAGE_SUCCESS')));
	}

	protected function checkPermission()
	{
		$this->listPerm = CListPermissions::checkAccess(
			$this->getUser(),
			$this->iblockTypeId,
			$this->iblockId,
			$this->socnetGroupId
		);
		if($this->listPerm < 0)
		{
			switch($this->listPerm)
			{
				case CListPermissions::WRONG_IBLOCK_TYPE:
					$this->errorCollection->add(array(new Error(Loc::getMessage('LISTS_WRONG_IBLOCK_TYPE'))));
					break;
				case CListPermissions::WRONG_IBLOCK:
					$this->errorCollection->add(array(new Error(Loc::getMessage('LISTS_WRONG_IBLOCK'))));
					break;
				case CListPermissions::LISTS_FOR_SONET_GROUP_DISABLED:
					$this->errorCollection->add(array(new Error(Loc::getMessage('LISTS_FOR_SONET_GROUP_DISABLED'))));
					break;
				default:
					$this->errorCollection->add(array(new Error(Loc::getMessage('LISTS_UNKNOWN_ERROR'))));
					break;
			}
		}
		elseif(
			$this->listPerm < CListPermissions::CAN_READ && !(
				CIBlockRights::UserHasRightTo($this->iblockId, $this->iblockId, "element_read") ||
				CIBlockSectionRights::UserHasRightTo($this->iblockId, $this->sectionId, "section_element_bind")
			)
		)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('LISTS_ACCESS_DENIED'))));
		}
	}
}

$controller = new ListsElementEditAjaxController();
$controller
	->setActionName(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
	->exec();