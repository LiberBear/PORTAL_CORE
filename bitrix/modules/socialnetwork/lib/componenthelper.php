<?php

namespace Bitrix\Socialnetwork;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

class ComponentHelper
{
	protected static $postsCache = array();
	protected static $commentsCache = array();
	protected static $commentListsCache = array();
	protected static $commentCountCache = array();
	protected static $authorsCache = array();
	protected static $destinationsCache = array();

	/**
	 * Returns data of a blog post
	 *
	 * @param int $postId Blog Post Id.
	 * @param string $languageId 2-char language Id.
	 * @return array|bool|false|mixed|null
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	*/
	public static function getBlogPostData($postId, $languageId)
	{
		global $USER_FIELD_MANAGER;

		if (isset(self::$postsCache[$postId]))
		{
			$result = self::$postsCache[$postId];
		}
		else
		{
			if (!Loader::includeModule('blog'))
			{
				throw new Main\SystemException("Could not load 'blog' module.");
			}

			$res = \CBlogPost::getList(
				array(),
				array(
					"ID" => $postId
				),
				false,
				false,
				array("ID", "BLOG_GROUP_ID", "BLOG_GROUP_SITE_ID", "BLOG_ID", "PUBLISH_STATUS", "TITLE", "AUTHOR_ID", "ENABLE_COMMENTS", "NUM_COMMENTS", "VIEWS", "CODE", "MICRO", "DETAIL_TEXT", "DATE_PUBLISH", "CATEGORY_ID", "HAS_SOCNET_ALL", "HAS_TAGS", "HAS_IMAGES", "HAS_PROPS", "HAS_COMMENT_IMAGES")
			);

			if ($result = $res->fetch())
			{
				$result["ATTACHMENTS"] = array();

				if($result["HAS_PROPS"] != "N")
				{
					$userFields = $USER_FIELD_MANAGER->getUserFields("BLOG_POST", $postId, $languageId);
					$postUf = array("UF_BLOG_POST_FILE");
					foreach ($userFields as $fieldName => $userField)
					{
						if (!in_array($fieldName, $postUf))
						{
							unset($userFields[$fieldName]);
						}
					}

					if (
						!empty($userFields["UF_BLOG_POST_FILE"])
						&& !empty($userFields["UF_BLOG_POST_FILE"]["VALUE"])
					)
					{
						$result["ATTACHMENTS"] = self::getAttachmentsData($userFields["UF_BLOG_POST_FILE"]["VALUE"], $result["BLOG_GROUP_SITE_ID"]);
					}
				}

				$result["DETAIL_TEXT"] = self::convertDiskFileBBCode(
					$result["DETAIL_TEXT"],
					'BLOG_POST',
					$postId,
					$result["AUTHOR_ID"],
					$result["ATTACHMENTS"]
				);

				$result["DETAIL_TEXT_FORMATTED"] = preg_replace(
					array(
						'|\[DISK\sFILE\sID=[n]*\d+\]|',
						'|\[DOCUMENT\sID=[n]*\d+\]|'
					),
					'',
					$result["DETAIL_TEXT"]
				);

				$result["DETAIL_TEXT_FORMATTED"] = preg_replace(
					"/\[USER\s*=\s*([^\]]*)\](.+?)\[\/USER\]/is".BX_UTF_PCRE_MODIFIER,
					"\\2",
					$result["DETAIL_TEXT_FORMATTED"]
				);

				$p = new \blogTextParser();
				$p->arUserfields = array();

				$images = array();
				$allow = array("IMAGE" => "Y");
				$parserParameters = array();

				$result["DETAIL_TEXT_FORMATTED"] = $p->convert($result["DETAIL_TEXT_FORMATTED"], false, $images, $allow, $parserParameters);

				$title = (
					$result["MICRO"] == "Y"
						? \blogTextParser::killAllTags($result["DETAIL_TEXT_FORMATTED"])
						: htmlspecialcharsEx($result["TITLE"])
				);

				$title = preg_replace(
					'|\[MAIL\sDISK\sFILE\sID=[n]*\d+\]|',
					'',
					$title
				);

				$title = str_replace(Array("\r\n", "\n", "\r"), " ", $title);
				$result["TITLE_FORMATTED"] = \TruncateText($title, 100);
				$result["DATE_PUBLISH_FORMATTED"] = self::formatDateTimeToGMT($result['DATE_PUBLISH'], $result['AUTHOR_ID']);
			}

			self::$postsCache[$postId] = $result;
		}

		return $result;
	}

	/**
	 * Returns data of blog post destinations
	 *
	 * @param int $postId Blog Post Id.
	 * @return array
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	*/
	public static function getBlogPostDestinations($postId)
	{
		if (isset(self::$destinationsCache[$postId]))
		{
			$result = self::$destinationsCache[$postId];
		}
		else
		{
			$result = array();

			if (!Loader::includeModule('blog'))
			{
				throw new Main\SystemException("Could not load 'blog' module.");
			}

			$sonetPermission = \CBlogPost::getSocnetPermsName($postId);
			if (!empty($sonetPermission))
			{
				foreach($sonetPermission as $typeCode => $type)
				{
					foreach($sonetPermission[$typeCode] as $userId => $destination)
					{
						$name = false;

						if ($typeCode == "SG")
						{
							if ($sonetGroup = \CSocNetGroup::getByID($destination["ENTITY_ID"]))
							{
								$name = $sonetGroup["NAME"];
							}
						}
						elseif ($typeCode == "U")
						{
							if(in_array("US".$destination["ENTITY_ID"], $destination["ENTITY"]))
							{
								$name = "#ALL#";
								Loader::includeModule('intranet');
							}
							else
							{
								$name = \CUser::formatName(
									\CSite::getNameFormat(false),
									array(
										"NAME" => $destination["~U_NAME"],
										"LAST_NAME" => $destination["~U_LAST_NAME"],
										"SECOND_NAME" => $destination["~U_SECOND_NAME"],
										"LOGIN" => $destination["~U_LOGIN"]
									),
									true
								);
							}
						}
						elseif ($typeCode == "DR")
						{
							$name = $destination["EL_NAME"];
						}

						if ($name)
						{
							$result[] = $name;
						}
					}
				}
			}

			self::$destinationsCache[$postId] = $result;
		}

		return $result;
	}

	/**
	 * Returns data of a blog post/comment author
	 *
	 * @param int $authorId User Id.
	 * @param array $params Format parameters (avatar size etc).
	 * @return array
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	*/
	public static function getBlogAuthorData($authorId, $params)
	{
		if (isset(self::$authorsCache[$authorId]))
		{
			$result = self::$authorsCache[$authorId];
		}
		else
		{
			if (!Loader::includeModule('blog'))
			{
				throw new Main\SystemException("Could not load 'blog' module.");
			}

			$result = \CBlogUser::getUserInfo(
				intval($authorId),
				'',
				array(
					"AVATAR_SIZE" => (
						isset($params["AVATAR_SIZE"])
						&& intval($params["AVATAR_SIZE"]) > 0
							? intval($params["AVATAR_SIZE"])
							: false
					),
					"AVATAR_SIZE_COMMENT" => (
						isset($params["AVATAR_SIZE_COMMENT"])
						&& intval($params["AVATAR_SIZE_COMMENT"]) > 0
							? intval($params["AVATAR_SIZE_COMMENT"])
							: false
					),
					"RESIZE_IMMEDIATE" => "Y"
				)
			);

			$result["NAME_FORMATTED"] = \CUser::formatName(
				\CSite::getNameFormat(false),
				array(
					"NAME" => $result["~NAME"],
					"LAST_NAME" => $result["~LAST_NAME"],
					"SECOND_NAME" => $result["~SECOND_NAME"],
					"LOGIN" => $result["~LOGIN"]
				),
				true
			);

			self::$authorsCache[$authorId] = $result;
		}

		return $result;
	}

	/**
	 * Returns full list of blog post comments
	 *
	 * @param int $postId Blog Post Id.
	 * @param array $params Additional paramaters.
	 * @param string $languageId Language Id (2-char).
	 * @param array &$authorIdList List of User Ids.
	 * @return array
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	*/
	public static function getBlogCommentListData($postId, $params, $languageId, &$authorIdList = array())
	{
		if (isset(self::$commentListsCache[$postId]))
		{
			$result = self::$commentListsCache[$postId];
		}
		else
		{
			$result = array();

			if (!Loader::includeModule('blog'))
			{
				throw new Main\SystemException("Could not load 'blog' module.");
			}

			$p = new \blogTextParser();

			$selectedFields = Array("ID", "BLOG_GROUP_ID", "BLOG_GROUP_SITE_ID", "BLOG_ID", "POST_ID", "AUTHOR_ID", "AUTHOR_NAME", "AUTHOR_EMAIL", "POST_TEXT", "DATE_CREATE", "PUBLISH_STATUS", "HAS_PROPS", "SHARE_DEST");

			$connection = \Bitrix\Main\Application::getConnection();
			if ($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection)
			{
				$selectedFields[] = "DATE_CREATE_TS";
			}

			$res = \CBlogComment::getList(
				array("ID" => "DESC"),
				array(
					"PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
					"POST_ID" => $postId,
//					"SHARE_DEST" => false
				),
				false,
				array(
					"nTopCount" => $params["COMMENTS_COUNT"]
				),
				$selectedFields
			);

			while ($comment = $res->fetch())
			{
				self::processCommentData($comment, $languageId, $p);

				$result[] = $comment;

				if (!in_array($comment["AUTHOR_ID"], $authorIdList))
				{
					$authorIdList[] = $comment["AUTHOR_ID"];
				}
			}

			if (!empty($result))
			{
				$result = array_reverse($result);
			}

			self::$commentListsCache[$postId] = $result;
		}

		return $result;
	}

	/**
	 * Returns a number of blog post comments
	 *
	 * @param int $postId Blog Post Id.
	 * @return bool|int
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	*/
	public static function getBlogCommentListCount($postId)
	{
		if (isset(self::$commentCountCache[$postId]))
		{
			$result = self::$commentCountCache[$postId];
		}
		else
		{
			if (!Loader::includeModule('blog'))
			{
				throw new Main\SystemException("Could not load 'blog' module.");
			}

			$selectedFields = Array("ID");

			$result = \CBlogComment::getList(
				array("ID" => "DESC"),
				array(
					"PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
					"POST_ID" => $postId,
//					"SHARE_DEST" => false
				),
				array(), // count only
				false,
				$selectedFields
			);

			self::$commentCountCache[$postId] = $result;
		}

		return $result;
	}


	/**
	 * Returns data of a blog comment
	 *
	 * @param int $commentId Comment Id.
	 * @param string $languageId Language id (2-chars).
	 * @return array|bool|false|mixed|null
	*/
	public static function getBlogCommentData($commentId, $languageId)
	{
		$result = array();

		if (isset(self::$commentsCache[$commentId]))
		{
			$result = self::$commentsCache[$commentId];
		}
		else
		{
			$selectedFields = Array("ID", "BLOG_GROUP_ID", "BLOG_GROUP_SITE_ID", "BLOG_ID", "POST_ID", "AUTHOR_ID", "AUTHOR_NAME", "AUTHOR_EMAIL", "POST_TEXT", "DATE_CREATE", "PUBLISH_STATUS", "HAS_PROPS", "SHARE_DEST");

			$connection = \Bitrix\Main\Application::getConnection();
			if ($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection)
			{
				$selectedFields[] = "DATE_CREATE_TS";
			}

			$res = \CBlogComment::getList(
				array(),
				array(
					"ID" => $commentId
				),
				false,
				false,
				$selectedFields
			);

			if ($comment = $res->fetch())
			{
				$p = new \blogTextParser();

				self::processCommentData($comment, $languageId, $p);

				$result = $comment;
			}

			self::$commentsCache[$commentId] = $result;
		}

		return $result;
	}

	/**
	 * Processes comment data, rendering formatted text and date
	 *
	 * @param array $comment Comment fields set.
	 * @param string $languageId Language Id (2-chars).
	 * @param \blogTextParser $p TextParser object.
	*/
	private static function processCommentData(&$comment, $languageId, $p)
	{
		global $USER_FIELD_MANAGER;

		$comment["ATTACHMENTS"] = $comment["PROPS"] = array();

		if($comment["HAS_PROPS"] != "N")
		{
			$userFields = $comment["PROPS"] = $USER_FIELD_MANAGER->getUserFields("BLOG_COMMENT", $comment["ID"], $languageId);
			$commentUf = array("UF_BLOG_COMMENT_FILE");
			foreach ($userFields as $fieldName => $userField)
			{
				if (!in_array($fieldName, $commentUf))
				{
					unset($userFields[$fieldName]);
				}
			}

			if (
				!empty($userFields["UF_BLOG_COMMENT_FILE"])
				&& !empty($userFields["UF_BLOG_COMMENT_FILE"]["VALUE"])
			)
			{
				$comment["ATTACHMENTS"] = self::getAttachmentsData($userFields["UF_BLOG_COMMENT_FILE"]["VALUE"], $comment["BLOG_GROUP_SITE_ID"]);
			}
		}

		$comment["POST_TEXT"] = self::convertDiskFileBBCode(
			$comment["POST_TEXT"],
			'BLOG_COMMENT',
			$comment["ID"],
			$comment["AUTHOR_ID"],
			$comment["ATTACHMENTS"]
		);

		$comment["POST_TEXT_FORMATTED"] = preg_replace(
			array(
				'|\[DISK\sFILE\sID=[n]*\d+\]|',
				'|\[DOCUMENT\sID=[n]*\d+\]|'
			),
			'',
			$comment["POST_TEXT"]
		);

		$comment["POST_TEXT_FORMATTED"] = preg_replace(
			"/\[USER\s*=\s*([^\]]*)\](.+?)\[\/USER\]/is".BX_UTF_PCRE_MODIFIER,
			"\\2",
			$comment["POST_TEXT_FORMATTED"]
		);

		if ($p)
		{
			$p->arUserfields = array();
		}
		$images = array();
		$allow = array("IMAGE" => "Y");
		$parserParameters = array();

		$comment["POST_TEXT_FORMATTED"] = $p->convert($comment["POST_TEXT_FORMATTED"], false, $images, $allow, $parserParameters);
		$comment["DATE_CREATE_FORMATTED"] = self::formatDateTimeToGMT($comment['DATE_CREATE'], $comment['AUTHOR_ID']);
	}

	/**
	 * Returns mail-hash url
	 *
	 * @param string $url Entity Link.
	 * @param int $userId User Id.
	 * @param string $entityType Entity Type.
	 * @param int $entityId Entity Id.
	 * @param string $siteId Site id (2-char).
	 * @return bool|string
	 * @throws Main\LoaderException
	*/
	public static function getReplyToUrl($url, $userId, $entityType, $entityId, $siteId, $backUrl = null)
	{
		$result = false;

		if (
			strlen($url) > 0
			&& intval($userId) > 0
			&& strlen($entityType) > 0
			&& intval($entityId) > 0
			&& strlen($siteId) > 0
			&& Loader::includeModule('mail')
		)
		{
			$urlRes = \Bitrix\Mail\User::getReplyTo(
				$siteId,
				intval($userId),
				$entityType,
				$entityId,
				$url,
				$backUrl
			);
			if (is_array($urlRes))
			{
				list($replyTo, $backUrl) = $urlRes;

				if ($backUrl)
				{
					$result = $backUrl;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns data of attached files
	 *
	 * @param array $valueList Attachments List.
	 * @param string|bool|false $siteId Site Id (2-chars).
	 * @return array
	 * @throws Main\LoaderException
     */
	public static function getAttachmentsData($valueList, $siteId = false)
	{
		$result = array();

		if (!Loader::includeModule('disk'))
		{
			return $result;
		}

		if (
			!$siteId
			|| strlen($siteId) <= 0
		)
		{
			$siteId = SITE_ID;
		}

		$driver = \Bitrix\Disk\Driver::getInstance();
		$urlManager = $driver->getUrlManager();

		foreach ($valueList as $key => $value)
		{
			$attachedObject = \Bitrix\Disk\AttachedObject::loadById($value, array('OBJECT'));
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
				"IS_IMAGE" => \Bitrix\Disk\TypeFile::isImage($attachedObject->getFile())
			);
		}

		return $result;
	}

	/**
	 * Processes disk objects list and generates external links (for inline images) if needed
	 *
	 * @param array $valueList
	 * @param string $entityType Entity Type.
	 * @param int $entityId Entity Id.
	 * @param int $authorId User Id.
	 * @param array $attachmentList Attachments List.
	 * @return array
	 * @throws Main\LoaderException
	*/
	public static function getAttachmentUrlList($valueList = array(), $entityType = '', $entityId = 0, $authorId = 0, $attachmentList = array())
	{
		$result = array();

		if (
			empty($valueList)
			|| empty($attachmentList)
			|| intval($authorId) <= 0
			|| intval($entityId) <= 0
			|| !Loader::includeModule('disk')
		)
		{
			return $result;
		}

		$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();
		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType($entityType);

		foreach($valueList as $value)
		{
			$attachedFileId = false;
			$attachedObject = false;

			list($type, $realValue) = \Bitrix\Disk\Uf\FileUserType::detectType($value);
			if ($type == \Bitrix\Disk\Uf\FileUserType::TYPE_NEW_OBJECT)
			{
				$attachedObject = \Bitrix\Disk\AttachedObject::load(array(
					'=ENTITY_TYPE' => $connectorClass,
					'ENTITY_ID' => $entityId,
					'=MODULE_ID' => $moduleId,
					'OBJECT_ID'=> $realValue
				), array('OBJECT'));

				if($attachedObject)
				{
					$attachedFileId = $attachedObject->getId();
				}
			}
			else
			{
				$attachedFileId = $realValue;
			}

			if (
				intval($attachedFileId) > 0
				&& !empty($attachmentList[$attachedFileId])
			)
			{
				if (!$attachmentList[$attachedFileId]["IS_IMAGE"])
				{
					$result[$value] = array(
						'TYPE' => 'file',
						'URL' => $attachmentList[$attachedFileId]["URL"]
					);
				}
				else
				{
					if (!$attachedObject)
					{
						$attachedObject = \Bitrix\Disk\AttachedObject::loadById($attachedFileId, array('OBJECT'));
					}

					if ($attachedObject)
					{
						$file = $attachedObject->getFile();

						$extLinks = $file->getExternalLinks(array(
							'filter' => array(
								'OBJECT_ID' => $file->getId(),
								'CREATED_BY' => $authorId,
								'TYPE' => \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_MANUAL,
								'IS_EXPIRED' => false,
							),
							'limit' => 1,
						));

						if (empty($extLinks))
						{
							$externalLink = $file->addExternalLink(array(
								'CREATED_BY' => $authorId,
								'TYPE' => \Bitrix\Disk\Internals\ExternalLinkTable::TYPE_MANUAL,
							));
						}
						else
						{
							/** @var \Bitrix\Disk\ExternalLink $externalLink */
							$externalLink = reset($extLinks);
						}

						if ($externalLink)
						{
							$originalFile = $file->getFile();

							$result[$value] = array(
								'TYPE' => 'image',
								'URL' => \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlExternalLink(
									array(
										'hash' => $externalLink->getHash(),
										'action' => 'showFile'
									),
									true
								),
								'WIDTH' => intval($originalFile["WIDTH"]),
								'HEIGHT' => intval($originalFile["HEIGHT"])
							);
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Converts formatted text replacing pseudo-BB code MAIL DISK, using calculated URLs
	 *
	 * @param string $text Text to convert.
	 * @param array $attachmentList Attachments List.
	 * @return mixed|string
	*/
	public static function convertMailDiskFileBBCode($text = '', $attachmentList = array())
	{
		if (preg_match_all('|\[MAIL\sDISK\sFILE\sID=([n]*\d+)\]|', $text, $matches))
		{
			foreach($matches[1] as $inlineFileId)
			{
				$attachmentId = false;
				if (strpos($inlineFileId, 'n') === 0)
				{
					$found = false;
					foreach($attachmentList as $attachmentId => $attachment)
					{
						if (
							isset($attachment["OBJECT_ID"])
							&& intval($attachment["OBJECT_ID"]) == intval(substr($inlineFileId, 1))
						)
						{
							$found = true;
							break;
						}
					}
					if (!$found)
					{
						$attachmentId = false;
					}
				}
				else
				{
					$attachmentId = $inlineFileId;
				}

				if (intval($attachmentId) > 0)
				{
					$text = preg_replace(
						'|\[MAIL\sDISK\sFILE\sID='.$inlineFileId.'\]|',
						'[URL='.$attachmentList[$attachmentId]["URL"].']['.$attachmentList[$attachmentId]["NAME"].'][/URL]',
						$text
					);
				}
			}

			$p = new \blogTextParser();
			$p->arUserfields = array();

			$text = $p->convert($text, false, array(), array("HTML" => "Y", "ANCHOR" => "Y"), array());
		}

		return $text;
	}

	/**
	 * Converts DISK FILE BB-code to the pseudo-BB code MAIL DISK FILE or IMG BB-code
	 *
	 * @param string $text Text to convert.
	 * @param string $entityType Entity Type.
	 * @param int $entityId Entity Type.
	 * @param int $authorId User id.
	 * @param array $attachmentList Attachments List.
	 * @return mixed
	*/
	function convertDiskFileBBCode($text, $entityType, $entityId, $authorId, $attachmentList = array())
	{
		if (
			strlen(trim($text)) <= 0
			|| empty($attachmentList)
			|| intval($authorId) <= 0
			|| strlen($entityType) <= 0
			|| intval($entityId) <= 0
		)
		{
			return $text;
		}

		if (preg_match_all('|\[DISK\sFILE\sID=([n]*\d+)\]|', $text, $matches))
		{
			$attachmentUrlList = self::getAttachmentUrlList(
				$matches[1],
				$entityType,
				$entityId,
				$authorId,
				$attachmentList
			);

			foreach($matches[1] as $inlineFileId)
			{
				if (!empty($attachmentUrlList[$inlineFileId]))
				{
					$needCreatePicture = false;
					$sizeSource = $sizeDestination = array();
					\CFile::scaleImage(
						$attachmentUrlList[$inlineFileId]['WIDTH'], $attachmentUrlList[$inlineFileId]['HEIGHT'],
						array('width' => 400, 'height' => 1000), BX_RESIZE_IMAGE_PROPORTIONAL,
						$needCreatePicture, $sizeSource, $sizeDestination
					);

					$replacement = (
						$attachmentUrlList[$inlineFileId]["TYPE"] == 'image'
							? '[IMG WIDTH='.intval($sizeDestination['width']).' HEIGHT='.intval($sizeDestination['height']).']'.\htmlspecialcharsBack($attachmentUrlList[$inlineFileId]["URL"]).'[/IMG]'
							: '[MAIL DISK FILE ID='.$inlineFileId.']'
					);
					$text = preg_replace(
						'|\[DISK\sFILE\sID='.$inlineFileId.'\]|',
						$replacement,
						$text
					);
				}
			}
		}

		return $text;
	}

	/**
	 * Formsts date time to the value of author + GMT offset
	 *
	 * @param string $dateTimeSource Date/Time in site format.
	 * @param int $authorId User Id.
	 * @return string
	*/
	public static function formatDateTimeToGMT($dateTimeSource, $authorId)
	{
		$result = '';

		if (!empty($dateTimeSource))
		{
			$serverTs = \MakeTimeStamp($dateTimeSource) - \CTimeZone::getOffset();
			$serverGMTOffset = date('Z');

			$authorOffset = \CTimeZone::getOffset($authorId);
			$authorGMTOffset = $serverGMTOffset + $authorOffset;
			$authorGMTOffsetFormatted = 'GMT';
			if ($authorGMTOffset != 0)
			{
				$authorGMTOffsetFormatted .= ($authorGMTOffset >= 0 ? '+' : '-').sprintf('%02d', floor($authorGMTOffset / 3600)).':'.sprintf('%02u', ($authorGMTOffset % 3600) / 60);
			}

			$result = \FormatDate(
				preg_replace('/[\/.,\s:][s]/', '', \Bitrix\Main\Type\Date::convertFormatToPhp(FORMAT_DATETIME)),
				($serverTs + $authorOffset)
			).' ('.$authorGMTOffsetFormatted.')';
		}

		return $result;
	}

	/**
	 * Creates a user blog (when it is the first post of the user)
	 *
	 * @param array $params Parameters.
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	*/
	public static function createUserBlog($params)
	{
		$result = false;

		if (!Loader::includeModule('blog'))
		{
			throw new Main\SystemException("Could not load 'blog' module.");
		}

		if (
			!isset($params["BLOG_GROUP_ID"])
			|| intval($params["BLOG_GROUP_ID"]) <= 0
			|| !isset($params["USER_ID"])
			|| intval($params["USER_ID"]) <= 0
			|| !isset($params["SITE_ID"])
			|| strlen($params["SITE_ID"]) <= 0
		)
		{
			return false;
		}

		if (
			!isset($params["PATH_TO_BLOG"])
			|| strlen($params["PATH_TO_BLOG"]) <= 0
		)
		{
			$params["PATH_TO_BLOG"] = "";
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$fields = array(
			"=DATE_UPDATE" => $helper->getCurrentDateTimeFunction(),
			"=DATE_CREATE" => $helper->getCurrentDateTimeFunction(),
			"GROUP_ID" => intval($params["BLOG_GROUP_ID"]),
			"ACTIVE" => "Y",
			"OWNER_ID" => intval($params["USER_ID"]),
			"ENABLE_COMMENTS" => "Y",
			"ENABLE_IMG_VERIF" => "Y",
			"EMAIL_NOTIFY" => "Y",
			"ENABLE_RSS" => "Y",
			"ALLOW_HTML" => "N",
			"ENABLE_TRACKBACK" => "N",
			"SEARCH_INDEX" => "Y",
			"USE_SOCNET" => "Y",
			"PERMS_POST" => Array(
				1 => "I",
				2 => "I"
			),
			"PERMS_COMMENT" => Array(
				1 => "P",
				2 => "P"
			)
		);

		$res = \Bitrix\Main\UserTable::getList(array(
			'order' => array(),
			'filter' => array(
				"ID" => $params["USER_ID"]
			),
			'select' => array("NAME", "LAST_NAME", "LOGIN")
		));

		if ($user = $res->fetch())
		{
			$fields["NAME"] = Loc::getMessage("BLG_NAME")." ".(
				strlen($user["NAME"]."".$user["LAST_NAME"]) <= 0
					? $user["LOGIN"]
					: $user["NAME"]." ".$user["LAST_NAME"]
			);

			$fields["URL"] = str_replace(" ", "_", $user["LOGIN"])."-blog-".$params["SITE_ID"];
			$urlCheck = preg_replace("/[^a-zA-Z0-9_-]/is", "", $fields["URL"]);
			if ($urlCheck != $fields["URL"])
			{
				$fields["URL"] = "u".$params["USER_ID"]."-blog-".$params["SITE_ID"];
			}

			if(\CBlog::getByUrl($fields["URL"]))
			{
				$uind = 0;
				do
				{
					$uind++;
					$fields["URL"] = $fields["URL"].$uind;
				}
				while (\CBlog::getByUrl($fields["URL"]));
			}

			$fields["PATH"] = \CComponentEngine::makePathFromTemplate(
				$params["PATH_TO_BLOG"],
				array(
					"blog" => $fields["URL"],
					"user_id" => $fields["OWNER_ID"]
				)
			);

			if ($blogID = \CBlog::add($fields))
			{
				BXClearCache(true, "/blog/form/blog/");

				$rightsFound = false;

				$featureOperationPerms = \CSocNetFeaturesPerms::getOperationPerm(
					SONET_ENTITY_USER,
					$fields["OWNER_ID"],
					"blog",
					"view_post"
				);

				if ($featureOperationPerms == SONET_RELATIONS_TYPE_ALL)
				{
					$rightsFound = true;
				}

				if ($rightsFound)
				{
					\CBlog::addSocnetRead($blogID);
				}

				$result = \CBlog::getByID($blogID);
			}
		}

		return $result;
	}

	/**
	 * get urlPreview property value from text with links
	 *
	 * @param $text string
	 * @param bool|true $html
	 * @return bool|string
	 * @throws Main\ArgumentTypeException
	*/
	public static function getUrlPreviewValue($text, $html = true)
	{
		static $parser = false;
		$value = false;

		if (empty($text))
		{
			return $value;
		}

		if (!$parser)
		{
			$parser = new \CTextParser();
		}

		if ($html)
		{
			$text = $parser->convertHtmlToBB($text);
		}

		preg_match_all("/\[url\s*=\s*([^\]]*)\](.+?)\[\/url\]/ies".BX_UTF_PCRE_MODIFIER, $text, $res);

		if (
			!empty($res)
			&& !empty($res[1])
		)
		{
			$url = (
				!\Bitrix\Main\Application::isUtfMode()
					? \Bitrix\Main\Text\Encoding::convertEncoding($res[1][0], 'UTF-8', \Bitrix\Main\Context::getCurrent()->getCulture()->getCharset())
					: $res[1][0]
			);

			$metaData = \Bitrix\Main\UrlPreview\UrlPreview::getMetadataAndHtmlByUrl($url);
			if (
				!empty($metaData)
				&& !empty($metaData["ID"])
				&& intval($metaData["ID"]) > 0
			)
			{
				$signer = new \Bitrix\Main\Security\Sign\Signer();
				$value = $signer->sign($metaData["ID"].'', \Bitrix\Main\UrlPreview\UrlPreview::SIGN_SALT);
			}
		}

		return $value;
	}

	/**
	 * Returns rendered url preview block
	 *
	 * @param array $uf
	 * @param array $params
	 * @return string|boolean
	*/
	public static function getUrlPreviewContent($uf, $params = array())
	{
		global $APPLICATION;
		$res = false;

		if ($uf["USER_TYPE"]["USER_TYPE_ID"] != 'url_preview')
		{
			return $res;
		}

		ob_start();

		$APPLICATION->includeComponent(
			"bitrix:system.field.view",
			$uf["USER_TYPE"]["USER_TYPE_ID"],
			array(
				"LAZYLOAD" => (isset($params["LAZYLOAD"]) && $params["LAZYLOAD"] == "Y" ? "Y" : "N"),
				"MOBILE" => (isset($params["MOBILE"]) && $params["MOBILE"] == "Y" ? "Y" : "N"),
				"arUserField" => $uf,
				"arAddField" => array(
					"NAME_TEMPLATE" => (isset($params["NAME_TEMPLATE"]) ? $params["NAME_TEMPLATE"] : false),
					"PATH_TO_USER" => (isset($params["PATH_TO_USER"]) ? $params["PATH_TO_USER"] : '')
				)
			), null, array("HIDE_ICONS"=>"Y")
		);

		$res = ob_get_clean();

		return $res;
	}

	public static function getExtranetUserIdList()
	{
		$result = array();

		$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
		$cacheId = 'sonet_ex_userid';
		$cache = new \CPHPCache;
		$cacheDir = '/bitrix/sonet/user_ex';

		if($cache->initCache($ttl, $cacheId, $cacheDir))
		{
			$tmpVal = $cache->getVars();
			$result = $tmpVal['EX_USER_ID'];
			unset($tmpVal);
		}
		elseif (ModuleManager::isModuleInstalled('extranet'))
		{
			$filter = array(
				'UF_DEPARTMENT' => false
			);

			$externalAuthIdList = self::checkPredefinedAuthIdList(array('replica', 'bot', 'email'));
			if (!empty($externalAuthIdList))
			{
				$filter['!=EXTERNAL_AUTH_ID'] = $externalAuthIdList;
			}

			$res = \Bitrix\Main\UserTable::getList(array(
				'order' => array(),
				'filter' => $filter,
				'select' => array('ID')
			));

			while($user = $res->fetch())
			{
				$result[] = $user["ID"];
			}

			if($cache->startDataCache())
			{
				$cache->endDataCache(array(
					'EX_USER_ID' => $result
				));
			}
		}

		return $result;
	}

	public static function getEmailUserIdList()
	{
		$result = array();

		$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
		$cacheId = 'sonet_email_userid';
		$cache = new \CPHPCache;
		$cacheDir = '/bitrix/sonet/user_email';

		if($cache->initCache($ttl, $cacheId, $cacheDir))
		{
			$tmpVal = $cache->getVars();
			$result = $tmpVal['EMAIL_USER_ID'];
			unset($tmpVal);
		}
		elseif (ModuleManager::isModuleInstalled('mail'))
		{
			$res = \Bitrix\Main\UserTable::getList(array(
				'order' => array(),
				'filter' => array(
					'=EXTERNAL_AUTH_ID' => 'email'
				),
				'select' => array('ID')
			));

			while($user = $res->fetch())
			{
				$result[] = $user["ID"];
			}

			if($cache->startDataCache())
			{
				$cache->endDataCache(array(
					'EMAIL_USER_ID' => $result
				));
			}
		}

		return $result;
	}

	public static function getExtranetSonetGroupIdList()
	{
		$result = array();

		$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
		$cacheId = 'sonet_ex_groupid';
		$cache = new \CPHPCache;
		$cacheDir = '/bitrix/sonet/group_ex';

		if($cache->initCache($ttl, $cacheId, $cacheDir))
		{
			$tmpVal = $cache->getVars();
			$result = $tmpVal['EX_GROUP_ID'];
			unset($tmpVal);
		}
		elseif (Loader::includeModule('extranet'))
		{
			global $CACHE_MANAGER;
			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->startTagCache($cacheDir);
			}

			$res = WorkgroupTable::getList(array(
				'order' => array(),
				'filter' => array(
					"=WorkgroupSite:GROUP.SITE_ID" => \CExtranet::getExtranetSiteID()
				),
				'select' => array('ID')
			));

			while($sonetGroup = $res->fetch())
			{
				$result[] = $sonetGroup["ID"];
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->registerTag('sonet_group_'.$sonetGroup["ID"]);
				}
			}

			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->registerTag('sonet_group');
				$CACHE_MANAGER->endTagCache();
			}

			if($cache->startDataCache())
			{
				$cache->endDataCache(array(
					'EX_GROUP_ID' => $result
				));
			}
		}

		return $result;
	}

	public static function hasCommentSource($params)
	{
		$res = false;

		if (empty($params["LOG_EVENT_ID"]))
		{
			return $res;
		}

		$commentEvent = \CSocNetLogTools::findLogCommentEventByLogEventID($params["LOG_EVENT_ID"]);

		if (
			isset($commentEvent["DELETE_CALLBACK"])
			&& $commentEvent["DELETE_CALLBACK"] != "NO_SOURCE"
		)
		{
			if (
				$commentEvent["EVENT_ID"] == "crm_activity_add_comment"
				&& isset($params["LOG_ENTITY_ID"])
				&& intval($params["LOG_ENTITY_ID"]) > 0
				&& Loader::includeModule('crm')
			)
			{
				$result = \CCrmActivity::getList(
					array(),
					array(
						'ID' => intval($params["LOG_ENTITY_ID"]),
						'CHECK_PERMISSIONS' => 'N'
					)
				);

				if ($activity = $result->fetch())
				{
					$res = ($activity['TYPE_ID'] == \CCrmActivityType::Task);
				}
			}
			else
			{
				$res = true;
			}
		}

		return $res;
	}

	// only by current userid
	public static function processBlogPostShare($fields, $params)
	{
		$postId = intval($fields["POST_ID"]);
		$blogId = intval($fields["BLOG_ID"]);
		$siteId = $fields["SITE_ID"];
		$sonetRights = $fields["SONET_RIGHTS"];
		$newRights = $fields["NEW_RIGHTS"];
		$userId = $fields["USER_ID"];

		$commentId = false;
		$logId = false;

		if (Loader::includeModule('blog'))
		{
			$connection = \Bitrix\Main\Application::getConnection();
			$helper = $connection->getSqlHelper();

			if(\CBlogPost::update($postId, array("SOCNET_RIGHTS" => $sonetRights, "HAS_SOCNET_ALL" => "N")))
			{
				BXClearCache(true, "/blog/socnet_post/".intval($postId / 100)."/".$postId."/");
				BXClearCache(true, "/blog/socnet_post/gen/".intval($postId / 100)."/".$postId."/");
				BXClearCache(True, "/".$siteId."/blog/popular_posts/");

				$logSiteListNew = array();
				$newRightsNameList = array();
				$user2NotifyList = array();
				$sonetPermissionList = \CBlogPost::getSocnetPermsName($postId);
				$extranet = Loader::includeModule("extranet");
				$currentExtranetSite = ($extranet && \CExtranet::isExtranetSite());
				$extranetSite = ($extranet ? \CExtranet::getExtranetSiteID() : false);
				$tzOffset = \CTimeZone::getOffset();

				$res = \CBlogPost::getList(
					array(),
					array("ID" => $postId),
					false,
					false,
					array("ID", "BLOG_ID", "PUBLISH_STATUS", "TITLE", "AUTHOR_ID", "ENABLE_COMMENTS", "NUM_COMMENTS", "VIEWS", "CODE", "MICRO", "DETAIL_TEXT", "DATE_PUBLISH", "CATEGORY_ID", "HAS_SOCNET_ALL", "HAS_TAGS", "HAS_IMAGES", "HAS_PROPS", "HAS_COMMENT_IMAGES")
				);
				$post = $res->fetch();
				if (!$post)
				{
					return false;
				}

				$intranetUserIdList = ($extranet ? \CExtranet::getIntranetUsers() : false);

				foreach($sonetPermissionList as $type => $v)
				{
					foreach($v as $vv)
					{
						$name = "";
						$link = "";
						$id = "";
						if (
							$type == "SG"
							&& in_array($type.$vv["ENTITY_ID"], $newRights)
						)
						{
							if($sonetGroup = \CSocNetGroup::getByID($vv["ENTITY_ID"]))
							{
								$name = $sonetGroup["NAME"];
								$link = \CComponentEngine::makePathFromTemplate(
									$params["PATH_TO_GROUP"],
									array(
										"group_id" => $vv["ENTITY_ID"]
									)
								);

								$groupSiteID = false;

								$res = \CSocNetGroup::getSite($vv["ENTITY_ID"]);
								while ($groupSiteList = $res->fetch())
								{
									$logSiteListNew[] = $groupSiteList["LID"];
									if (
										!$groupSiteID
										&& (
											!$extranet
											|| $groupSiteList["LID"] != $extranetSite
										)
									)
									{
										$groupSiteID = $groupSiteList["LID"];
									}
								}

								if ($groupSiteID)
								{
									$tmp = \CSocNetLogTools::processPath(array("GROUP_URL" => $link), $userId, $groupSiteID); // user_id is not important parameter
									$link = (strlen($tmp["URLS"]["GROUP_URL"]) > 0 ? $tmp["SERVER_NAME"].$tmp["URLS"]["GROUP_URL"] : $link);
								}
							}
						}
						elseif ($type == "U")
						{
							if (
								in_array("US".$vv["ENTITY_ID"], $vv["ENTITY"])
								&& in_array("UA", $newRights)
							)
							{
								$name = (
									ModuleManager::isModuleInstalled("intranet")
										? Loc::getMessage("BLG_SHARE_ALL")
										: Loc::getMessage("BLG_SHARE_ALL_BUS")
								);

								if (
									!$currentExtranetSite
									&& defined("BITRIX24_PATH_COMPANY_STRUCTURE_VISUAL")
								)
								{
									$link = BITRIX24_PATH_COMPANY_STRUCTURE_VISUAL;
								}
							}
							elseif(in_array($type.$vv["ENTITY_ID"], $newRights))
							{
								$tmpUser = array(
									"NAME" => $vv["~U_NAME"],
									"LAST_NAME" => $vv["~U_LAST_NAME"],
									"SECOND_NAME" => $vv["~U_SECOND_NAME"],
									"LOGIN" => $vv["~U_LOGIN"],
									"NAME_LIST_FORMATTED" => "",
								);
								$name = \CUser::formatName($params["NAME_TEMPLATE"], $tmpUser, ($params["SHOW_LOGIN"] != "N" ? true : false));
								$id = $vv["ENTITY_ID"];
								$link = \CComponentEngine::makePathFromTemplate(
									$params["PATH_TO_USER"],
									array(
										"user_id" => $vv["ENTITY_ID"]
									)
								);
								$user2NotifyList[] = $vv["ENTITY_ID"];

								if (
									$extranet
									&& is_array($intranetUserIdList)
									&& !in_array($vv["ENTITY_ID"], $intranetUserIdList)
								)
								{
									$logSiteListNew[] = $extranetSite;
								}
							}
						}
						elseif($type == "DR" && in_array($type.$vv["ENTITY_ID"], $newRights))
						{
							$name = $vv["EL_NAME"];
							$id = $vv["ENTITY_ID"];
							$link = \CComponentEngine::makePathFromTemplate(
								$params["PATH_TO_CONPANY_DEPARTMENT"],
								array(
									"ID" => $vv["ENTITY_ID"]
								)
							);
						}

						if(strlen($name) > 0)
						{
							if(strlen($link) > 0)
							{
								$newRightsNameList[] = (
									$type == "U"
									&& intval($id) > 0
										? "[user=".$id."]".htmlspecialcharsback($name)."[/user]"
										: "[url=".$link."]".htmlspecialcharsback($name)."[/url]"
								);
							}
							else
							{
								$newRightsNameList[] = htmlspecialcharsback($name);
							}
						}
					}
				}

				$userIP = \CBlogUser::getUserIP();
				$commentFields = Array(
					"POST_ID" => $postId,
					"BLOG_ID" => $blogId,
					"POST_TEXT" => (
						count($newRightsNameList) > 1
							? Loc::getMessage("BLG_SHARE")
							: Loc::getMessage("BLG_SHARE_1")
						).implode(", ", $newRightsNameList),
					"DATE_CREATE" => convertTimeStamp(time() + $tzOffset, "FULL"),
					"AUTHOR_IP" => $userIP[0],
					"AUTHOR_IP1" => $userIP[1],
					"PARENT_ID" => false,
					"AUTHOR_ID" => $userId,
					"SHARE_DEST" => implode(",", $newRights),
				);

				$userIdSent = array();

				if($commentId = \CBlogComment::add($commentFields))
				{
					BXClearCache(true, "/blog/comment/".intval($postId / 100)."/".$postId."/");



					if($post["AUTHOR_ID"] != $userId)
					{
						$fieldsIM = array(
							"TYPE" => "SHARE",
							"TITLE" => htmlspecialcharsback($post["TITLE"]),
							"URL" => \CComponentEngine::makePathFromTemplate(
								htmlspecialcharsBack($params["PATH_TO_POST"]),
								array(
									"post_id" => $postId,
									"user_id" => $post["AUTHOR_ID"]
								)
							),
							"ID" => $postId,
							"FROM_USER_ID" => $userId,
							"TO_USER_ID" => array($post["AUTHOR_ID"]),
						);
						\CBlogPost::notifyIm($fieldsIM);
						$userIdSent[] = array_merge($userIdSent, $fieldsIM["TO_USER_ID"]);
					}

					if(!empty($user2NotifyList))
					{
						$fieldsIM = array(
							"TYPE" => "SHARE2USERS",
							"TITLE" => htmlspecialcharsback($post["TITLE"]),
							"URL" => \CComponentEngine::makePathFromTemplate(
								htmlspecialcharsBack($params["PATH_TO_POST"]),
								array(
									"post_id" => $postId,
									"user_id" => $post["AUTHOR_ID"]
								)),
							"ID" => $postId,
							"FROM_USER_ID" => $userId,
							"TO_USER_ID" => $user2NotifyList,
						);
						\CBlogPost::notifyIm($fieldsIM);
						$userIdSent[] = array_merge($userIdSent, $fieldsIM["TO_USER_ID"]);

						\CBlogPost::notifyMail(array(
							"type" => "POST_SHARE",
							"siteId" => $siteId,
							"userId" => $user2NotifyList,
							"authorId" => $userId,
							"postId" => $post["ID"],
							"postUrl" => \CComponentEngine::makePathFromTemplate(
								'/pub/post.php?post_id=#post_id#',
								array(
									"post_id"=> $post["ID"]
								)
							)
						));
					}
				}

				/* update socnet log rights*/
				$res = \CSocNetLog::getList(
					array("ID" => "DESC"),
					array(
						"EVENT_ID" => array("blog_post", "blog_post_important"),
						"SOURCE_ID" => $postId
					),
					false,
					false,
					array("ID", "ENTITY_TYPE", "ENTITY_ID")
				);
				if ($logEntry = $res->fetch())
				{
					$logId = $logEntry["ID"];
					$logSiteList = array();
					$res = \CSocNetLog::getSite($logId);
					while ($logSite = $res->fetch())
					{
						$logSiteList[] = $logSite["LID"];
					}
					$logSiteListNew = array_merge($logSiteListNew, $logSiteList);

					$socnetPerms = \CBlogPost::getSocNetPermsCode($postId);
					if(!in_array("U".$post["AUTHOR_ID"], $socnetPerms))
					{
						$socnetPerms[] = "U".$post["AUTHOR_ID"];
					}
					$socnetPerms[] = "SA"; // socnet admin

					if (
						in_array("AU", $socnetPerms)
						|| in_array("G2", $socnetPerms)
					)
					{
						$socnetPermsAdd = array();

						foreach($socnetPerms as $perm)
						{
							if (preg_match('/^SG(\d+)$/', $perm, $matches))
							{
								if (
									!in_array("SG".$matches[1]."_".SONET_ROLES_USER, $socnetPerms)
									&& !in_array("SG".$matches[1]."_".SONET_ROLES_MODERATOR, $socnetPerms)
									&& !in_array("SG".$matches[1]."_".SONET_ROLES_OWNER, $socnetPerms)
								)
								{
									$socnetPermsAdd[] = "SG".$matches[1]."_".SONET_ROLES_USER;
								}
							}
						}
						if (count($socnetPermsAdd) > 0)
						{
							$socnetPerms = array_merge($socnetPerms, $socnetPermsAdd);
						}
					}

					\CSocNetLogRights::deleteByLogID($logId);
					\CSocNetLogRights::add($logId, $socnetPerms, true);

					if (count(array_diff($logSiteListNew, $logSiteList)) > 0)
					{
						\CSocNetLog::update($logId, array(
							"ENTITY_TYPE" => $logEntry["ENTITY_TYPE"], // to use any real field
							"SITE_ID" => $logSiteListNew,
							"=LOG_UPDATE" => $helper->getCurrentDateTimeFunction(),
						));
					}
					else
					{
						\CSocNetLog::update($logId, array(
							"=LOG_UPDATE" => $helper->getCurrentDateTimeFunction(),
						));
					}

					\CSocNetLogFollow::deleteByLogID($logId, "Y", true);

					/* subscribe share author */
					\CSocNetLogFollow::set(
						$userId,
						"L".$logId,
						"Y",
						convertTimeStamp(time() + $tzOffset, "FULL")
					);
				}

				/* update socnet groupd activity*/
				foreach($newRights as $v)
				{
					if(substr($v, 0, 2) == "SG")
					{
						$groupId = intval(substr($v, 2));
						if($groupId > 0)
						{
							\CSocNetGroup::setLastActivity($groupId);
						}
					}
				}

				\Bitrix\Blog\Broadcast::send(array(
					"EMAIL_FROM" => \COption::getOptionString("main","email_from", "nobody@nobody.com"),
					"SOCNET_RIGHTS" => $newRights,
					"ENTITY_TYPE" => "POST",
					"ENTITY_ID" => $post["ID"],
					"AUTHOR_ID" => $post["AUTHOR_ID"],
					"URL" => \CComponentEngine::makePathFromTemplate(
						htmlspecialcharsBack($params["PATH_TO_POST"]),
						array(
							"post_id" => $post["ID"],
							"user_id" => $post["AUTHOR_ID"]
						)
					),
					"EXCLUDE_USERS" => $userIdSent
				));

				\Bitrix\Main\FinderDestTable::merge(array(
					"CONTEXT" => "blog_post",
					"CODE" => \Bitrix\Main\FinderDestTable::convertRights($newRights)
				));

				if (
					intval($commentId) > 0
					&& (
						!isset($params["LIVE"])
						|| $params["LIVE"] != "N"
					)
				)
				{
					\CBlogComment::addLiveComment($commentId, array(
						"PATH_TO_USER" => $params["PATH_TO_USER"],
						"LOG_ID" => ($logId ? intval($logId) : 0)
					));
				}
			}
		}

		return $commentId;
	}

	public static function getUrlContext()
	{
		$result = array();

		if (
			isset($_GET["entityType"])
			&& strlen($_GET["entityType"]) > 0
		)
		{
			$result["ENTITY_TYPE"] = $_GET["entityType"];
		}

		if (
			isset($_GET["entityId"])
			&& intval($_GET["entityId"]) > 0
		)
		{
			$result["ENTITY_ID"] = intval($_GET["entityId"]);
		}

		return $result;
	}

	public static function addContextToUrl($url, $context)
	{
		$result = $url;

		if (
			!empty($context)
			&& !empty($context["ENTITY_TYPE"])
			&& !empty($context["ENTITY_ID"])
		)
		{
			$result = $url.(strpos($url, '?') === false ? '?' : '&').'entityType='.$context["ENTITY_TYPE"].'&entityId='.$context["ENTITY_ID"];
		}

		return $result;
	}

	public static function checkPredefinedAuthIdList($authIdList = array())
	{
		if (!is_array($authIdList))
		{
			$authIdList = array($authIdList);
		}

		foreach($authIdList as $key => $authId)
		{
			if (
				$authId == 'replica'
				&& !ModuleManager::isModuleInstalled("replica")
			)
			{
				unset($authIdList[$key]);
			}

			if (
				$authId == 'bot'
				&& !ModuleManager::isModuleInstalled("im")
			)
			{
				unset($authIdList[$key]);
			}

			if (
				$authId == 'email'
				&& !ModuleManager::isModuleInstalled("mail")
			)
			{
				unset($authIdList[$key]);
			}
		}

		return $authIdList;
	}

	public static function setComponentOption($list, $params = array())
	{
		if (!is_array($list))
		{
			return false;
		}

		$siteId = (!empty($params["SITE_ID"]) ? $params["SITE_ID"] : SITE_ID);
		$sefFolder = (!empty($params["SEF_FOLDER"]) ? $params["SEF_FOLDER"] : false);

		foreach($list as $key => $value)
		{
			if (
				empty($value["OPTION"])
				|| empty($value["OPTION"]["MODULE_ID"])
				|| empty($value["OPTION"]["NAME"])
				|| empty($value["VALUE"])
			)
			{
				return false;
			}

			$optionValue = Option::get($value["OPTION"]["MODULE_ID"], $value["OPTION"]["NAME"], false, $siteId);

			if (
				!$optionValue
				|| (
					!!$value["CHECK_SEF_FOLDER"]
					&& $sefFolder
					&& substr($optionValue, 0, strlen($sefFolder)) !== $sefFolder
				)
			)
			{
				Option::set($value["OPTION"]["MODULE_ID"], $value["OPTION"]["NAME"], $value["VALUE"], $siteId);
			}
		}

		return true;
	}
}
