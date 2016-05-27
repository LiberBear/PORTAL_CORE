<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Integration;

use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\AttachedObject;

use Bitrix\Tasks\Util\Error\Collection;

abstract class Disk extends \Bitrix\Tasks\Integration
{
	const MODULE_NAME = 'disk';

	/**
	 * @param $userId
	 * @param array $file File array
	 * @param Collection|null $errors
	 * @return bool|int
	 * @throws RestException
	 */
	public static function uploadFile($userId, array $file, Collection $errors = null)
	{
		if(!static::includeModule())
		{
			return false;
		}

		$storage = Driver::getInstance()->getStorageByUserId($userId);
		if(!$storage)
		{
			if($errors)
			{
				$errors->add('CANT_OBTAIN_STORAGE', 'Could not obtain storage');
			}
			return false;
		}

		$folder = $storage->getFolderForUploadedFiles();
		if(!$folder)
		{
			if($errors)
			{
				$errors->add('CANT_OBTAIN_FOLDER', 'Could not obtain folder');
			}
			return false;
		}
		$securityContext = $storage->getSecurityContext($userId);
		if(!$folder->canAdd($securityContext))
		{
			if($errors)
			{
				$errors->add('ACCESS_DENIED', 'Access denied');
			}
			return false;
		}
		$file = $folder->uploadFile($file, array(
			'NAME' => $file["name"],
			'CREATED_BY' => $userId
		), array(), true);
		if(!$file)
		{
			if($errors)
			{
				$errors->add('UPLOAD_FAIL', 'Could not upload file into the storage', false, array('FOLDER_ERRORS' => $folder->getErrors()));
			}

			return false;
		}

		return FileUserType::NEW_FILE_PREFIX.$file->getId();
	}

	public static function cloneFileAttachment($userId, array $diskFiles = array())
	{
		$result = array();

		if(!static::includeModule())
		{
			return $result;
		}

		// transform UF files
		if(!empty($diskFiles))
		{
			// find which files are new and which are old
			$old = array();
			$new = array();
			foreach($diskFiles as $fileId)
			{
				if((string) $fileId != '')
				{
					if(strpos($fileId, FileUserType::NEW_FILE_PREFIX) === 0)
					{
						$new[] = $fileId;
					}
					else
					{
						$old[] = $fileId;
					}
				}
			}

			if(!empty($old))
			{
				$userFieldManager = Driver::getInstance()->getUserFieldManager();
				$old = $userFieldManager->cloneUfValuesFromAttachedObject($old, $userId);

				if(is_array($old) && !empty($old))
				{
					$new = array_merge($new, $old);
				}
			}

			$result = $new;
		}

		return $result;
	}

	/**
	 * Deletes unnecessary files, which we created in cloneFileAttachment.
	 *
	 * @param int $userId Id of user.
	 * @param array $files List of new files (n1, n23, etc), which were created in cloneFileAttachment.
	 */
	public static function deleteUnattachedFiles($userId, array $files)
	{
		if(empty($files))
		{
			return;
		}

		if(!static::includeModule())
		{
			return;
		}

		foreach($files as $fileValue)
		{
			list($type, $fileValue) = FileUserType::detectType($fileValue);
			if($type != FileUserType::TYPE_NEW_OBJECT)
			{
				continue;
			}

			/** @var File $file */
			$file = File::loadById($fileValue);
			if(!$file)
			{
				continue;
			}

			$securityContext = $file->getStorage()->getSecurityContext($userId);
			if(!$file->canDelete($securityContext))
			{
				continue;
			}

			$file->delete($userId);
		}
		unset($file);
	}

	public static function getAttachmentData(array $valueList)
	{
		$result = array();

		if(!static::includeModule())
		{
			return $result;
		}

		$driver = Driver::getInstance();
		$urlManager = $driver->getUrlManager();

		foreach ($valueList as $key => $value)
		{
			$attachedObject = AttachedObject::loadById($value, array('OBJECT'));
			if(
				!$attachedObject
				|| !$attachedObject->getFile()
			)
			{
				continue;
			}

			$attachedObjectUrl = $urlManager->getUrlUfController('show', array('attachedId' => $value));

			$result[$value] = array(
				"ID" => $value,
				"OBJECT_ID" => $attachedObject->getFile()->getId(),
				"NAME" => $attachedObject->getFile()->getName(),
				"SIZE" => \CFile::formatSize($attachedObject->getFile()->getSize()),
				"URL" => $attachedObjectUrl,
				"IS_IMAGE" => TypeFile::isImage($attachedObject->getFile())
			);
		}

		return $result;
	}
}