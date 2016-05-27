<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class FocusController extends Internals\Controller
{
	const ERROR_COULD_NOT_FIND_FILE = 'DISK_FC_22004';
	const ERROR_COULD_NOT_READ_FILE = 'DISK_FC_22005';

	protected function listActions()
	{
		return array(
			'showObjectInGrid' => array(
				'method' => array('GET'),
				'redirect_on_auth' => true,
				'close_session' => true,
			),
			'openFileDetail' => array(
				'method' => array('GET'),
				'redirect_on_auth' => true,
				'close_session' => true,
			),
			'openFolderList' => array(
				'method' => array('GET'),
				'redirect_on_auth' => true,
				'close_session' => true,
			),
		);
	}

	private function showNotFound()
	{
		require(Application::getDocumentRoot() . '/bitrix/header.php');

		global $APPLICATION;
		$APPLICATION->includeComponent(
			'bitrix:disk.error.page',
			'',
			array()
		);

		require(Application::getDocumentRoot() . '/bitrix/footer.php');
		die;
	}

	protected function processActionShowObjectInGrid()
	{
		if(!$this->checkRequiredGetParams(array('objectId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var Folder|File $object */
		$object = BaseObject::loadById((int)$this->request->getQuery('objectId'), array('STORAGE'));
		if(!$object)
		{
			$this->errorCollection->addOne(new Error('Could not find file or folder', self::ERROR_COULD_NOT_FIND_FILE));
			$this->showNotFound();
		}
		$storage = $object->getStorage();
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			$this->errorCollection->addOne(new Error('Could not find file or folder', self::ERROR_COULD_NOT_READ_FILE));
			$this->showNotFound();
		}

		$gridOptions = new Internals\Grid\FolderListOptions($storage);
		$pageSize = $gridOptions->getPageSize();

		$parameters = array(
			'select' => array('ID'),
			'filter' => array(
				'PARENT_ID' => $object->getParentId(),
				'DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			),
			'order' => $gridOptions->getOrderForOrm(),
			'limit' => $pageSize,
		);

		$countQuery = new Query(ObjectTable::getEntity());
		$countQuery->addSelect(new ExpressionField('CNT', 'COUNT(1)'));
		$countQuery->setFilter($parameters['filter']);
		$totalCount = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();
		$totalCount = $totalCount['CNT'];

		$pageCount = ceil($totalCount / $pageSize);

		$driver = Driver::getInstance();
		$finalPage = null;
		for($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++)
		{
			$fullParameters = $driver->getRightsManager()->addRightsCheck($securityContext, $parameters, array('ID', 'CREATED_BY'));
			$fullParameters['offset'] = $pageSize * ($pageNumber - 1);
			$query = ObjectTable::getList($fullParameters);
			while($row = $query->fetch())
			{
				if($row['ID'] == $object->getId())
				{
					$finalPage = $pageNumber;
					break;
				}
			}
			if($finalPage !== null)
			{
				break;
			}
		}
		$finalPage = $finalPage?: 1;

		$command = $this->request->getQuery('cmd')?: '';
		if($command)
		{
			$command = '!' . $command;
		}
		$urlManager = $driver->getUrlManager();
		$pathInListing = $urlManager->getPathInListing($object) . "?&pageNumber={$finalPage}";

		LocalRedirect(
			$urlManager->encodeUrn($pathInListing) . "#hl-" . $object->getId() . ($command)
		);
	}

	protected function processActionOpenFileDetail()
	{
		if(!$this->checkRequiredGetParams(array('fileId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var File $file */
		$file = File::loadById((int)$this->request->getQuery('fileId'), array('STORAGE'));
		if(!$file)
		{
			$this->errorCollection->addOne(new Error('Could not find file', self::ERROR_COULD_NOT_FIND_FILE));
			$this->showNotFound();
		}
		if(!$file->canRead($file->getStorage()->getCurrentUserSecurityContext()))
		{
			$this->errorCollection->addOne(new Error('Could not find file', self::ERROR_COULD_NOT_READ_FILE));
			$this->showNotFound();
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		$pathDetail = $urlManager->getPathFileDetail($file);
		if($this->request->getQuery('back'))
		{
			$pathDetail .= '?&' . http_build_query(array('back' => $this->request->getQuery('back')));
		}
		LocalRedirect(
			$urlManager->encodeUrn($pathDetail)
		);
	}

	protected function processActionOpenFolderList()
	{
		if(!$this->checkRequiredGetParams(array('folderId')))
		{
			$this->sendJsonErrorResponse();
		}

		/** @var Folder $folder */
		$folder = Folder::loadById((int)$this->request->getQuery('folderId'), array('STORAGE'));
		if(!$folder)
		{
			$this->errorCollection->addOne(new Error('Could not find folder', self::ERROR_COULD_NOT_FIND_FILE));
			$this->showNotFound();
		}
		if(!$folder->canRead($folder->getStorage()->getCurrentUserSecurityContext()))
		{
			$this->errorCollection->addOne(new Error('Could not find folder', self::ERROR_COULD_NOT_READ_FILE));
			$this->showNotFound();
		}

		$urlManager = Driver::getInstance()->getUrlManager();
		LocalRedirect(
			$urlManager->encodeUrn(
				$urlManager->getPathFolderList($folder)
			)
		);
	}
}