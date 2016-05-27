<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 * 
 * This class DOES NOT check any CSRF tokens and even for current user`s authorization, so BE CAREFUL using it.
 */

namespace Bitrix\Tasks;

//use \Bitrix\Main\Localization\Loc;

use \Bitrix\Tasks\Util\Error\Collection;
use \Bitrix\Tasks\Util\Error\Filter;
use \Bitrix\Main\IO\Directory;

final class Dispatcher
{
	protected $rootNamespace = 		false;
	protected $errors = 			null;

	const ERROR_TYPE_PARSE = 		'PARSE';
	const ERROR_TYPE_CALL = 		'CALL';

	const NAMESPACE_TO_CALLABLE = 	'\\Dispatcher\\PublicAction';
	const DIRECTORY_TO_CALLABLE = 	'/dispatcher/publicaction';

	public function __construct()
	{
		$this->rootNamespace = __NAMESPACE__.static::NAMESPACE_TO_CALLABLE;
		$this->errors = new Collection();
	}

	public function execute(array $batch)
	{
		$batch = $this->parseBatch($batch);

		$result = array();
		if($this->errors->checkHasErrorOfType(static::ERROR_TYPE_PARSE))
		{
			throw new Dispatcher\BadQueryException(false);
		}

		foreach($batch as $operation)
		{
			// todo: break chain execution or continue when exception occured?
            // todo: replace call() with execute() which will return Operation Result object, move all Task/Exception catches inside operation->execute()

            $callResult = array();
			try
			{
				$callResult = $operation->call();
			}
			catch(\TasksException $e) // old-style tasks exception
			{
				$errorCode = static::getErrorCodeByTasksException($e);
				if($errorCode !== false)
				{
					$reasonsAdded = false;
					if($e->checkOfType(\TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE) && $e->getMessage() !== false)
					{
						$errors = \Bitrix\Tasks\Util\Type::unSerializeArray($e->getMessage());
						foreach ($errors as $error)
						{
							if((string) $error["id"] == '')
							{
								continue;
							}

							$operation->getErrorCollection()->add($error["id"], htmlspecialcharsBack($error["text"]));

							$reasonsAdded = true;
						}
					}

					if(!$reasonsAdded)
					{
						$operation->getErrorCollection()->add($errorCode, static::proxyExceptionMessage($e));
					}
				}
				else
				{
					throw $e; // let it log
				}
			}
			/*
			catch(\CTaskAssertException $e) // do not catch CTaskAssertException, let it log
			{
				$operation->getErrorCollection()->add('INTERNAL_ERROR', static::proxyExceptionMessage($e));
			}
			*/
			catch(\Bitrix\Tasks\AccessDeniedException $e)
			{
				// access to the entity is not allowed
				$operation->getErrorCollection()->add('ACCESS_DENIED', static::proxyExceptionMessage($e));
			}
			catch(\Bitrix\Tasks\ActionNotAllowedException $e)
			{
				// access to the entity is generally allowed, but the certain action is forbidden to execute
				$operation->getErrorCollection()->add('ACTION_NOT_ALLOWED', static::proxyExceptionMessage($e));
				static::addReasons($operation, $e->getErrors(), 'ACTION_NOT_ALLOWED');
			}
			catch(\Bitrix\Tasks\ActionFailedException $e)
			{
				// action was allowed, but due to some reasons execution failed
				$operation->getErrorCollection()->add('ACTION_FAILED', static::proxyExceptionMessage($e));
				$errors = $e->getErrors();

				if(is_array($errors) && !empty($errors))
				{
					foreach($errors as $error)
					{
						$operation->getErrorCollection()->add('ACTION_FAILED_REASON', $error);
					}
				}
			}
			/*
			catch(\Bitrix\Tasks\Exception $e)
			{
				// all other uncaught errors
				$operation->getErrorCollection()->add('INTERNAL_ERROR', static::proxyExceptionMessage($e));
			}
            catch(\Bitrix\Main\ArgumentException $e)
            {
                $operation->getErrorCollection()->add('INTERNAL_ERROR', static::proxyExceptionMessage($e));
            }
			*/

			$op = $operation->getOperation();

            // todo: an object Result with ArrayAccess, getOperation(), getArguments(), etc would be more appropriate here
			$result[$op['PARAMETERS']['CODE']] = array(
				'OPERATION' => 	$op['OPERATION'],
				'ARGUMENTS' => 	$op['ARGUMENTS'],
				'RESULT' => 	$callResult,
				'SUCCESS' => 	$operation->getErrorCollection()->checkNoFatals(),
				'ERRORS' => 	$operation->getErrorCollection()->getAll(true, new Filter())
			);
		}

		return $result;
	}

	private static function addReasons(Dispatcher\Operation $operation, array $reasons, $reasonPrefix = '')
	{
		$errors = $operation->getErrorCollection();

		if((string) $reasonPrefix != '')
		{
			$reasonPrefix = '_'.$reasonPrefix;
		}

		foreach($reasons as $reason)
		{
			if(is_string($reason))
			{
				$errors->add($reasonPrefix.'REASON', $reason);
			}
			else
			{
				if((string) $reason['MESSAGE'] != '')
				{
					$code = ((string) $reason['CODE'] != '' ? $reason['CODE'] : $reasonPrefix.'REASON');
					$errors->add($code, $reason['MESSAGE']);
				}
			}
		}
	}

	protected function parseBatch(array $batch)
	{
		if($this->rootNamespace == false)
		{
			throw new Dispatcher\Exception('Root namespace incorrect'); // paranoid disorder
		}

		// parse code and sort first
		$i = 0;
		$codesUsed = array();
		foreach($batch as &$operation)
		{
			if(is_array($operation['PARAMETERS']))
			{
				$operation['PARAMETERS'] = array_change_key_case($operation['PARAMETERS'], CASE_UPPER);
			}
			else
			{
				$operation['PARAMETERS'] = array();
			}

			if((string) $operation['PARAMETERS']['CODE'] === '')
			{
				$operation['PARAMETERS']['CODE'] = 'op_'.$i;
			}

			if(isset($codesUsed[$operation['PARAMETERS']['CODE']]))
			{
				$this->errors->add('CODE_USED_MULTIPLE_TIMES', 'The following code is used more than once: '.$operation['PARAMETERS']['CODE'], static::ERROR_TYPE_PARSE);
			}
			else
			{
				$codesUsed[$operation['PARAMETERS']['CODE']] = true;
			}

			$i++;
		}
		unset($operation);

		$batchParsed = array();
		foreach($batch as $operation)
		{
			$op = new Dispatcher\Operation($operation, array('NAMESPACE' => $this->rootNamespace));
			$op->parse();

			$batchParsed[] = $op;

			$this->errors->addForeignErrors($op->getErrorCollection());
		}

		return $batchParsed;
	}

	public function getErrorCollection()
	{
		return $this->errors;
	}

	public static function getErrorCodeByTasksException($e)
	{
		$result = false;

		if($e instanceof \TasksException)
		{
			if($e->checkOfType(\TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED))
			{
				$result = 'ACTION_FAILED';
			}
			elseif($e->checkOfType(\TasksException::TE_ACTION_NOT_ALLOWED)) // DO NOT relocate this ...
			{
				$result = 'ACTION_NOT_ALLOWED';
			}
			elseif($e->checkOfType(\TasksException::TE_ACCESS_DENIED)) // ... after this
			{
				$result = 'ACCESS_DENIED';
			}
			elseif($e->checkOfType(\TasksException::TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE))
			{
				$result = 'ACCESS_DENIED.NO_TASK';
			}
		}

		return $result;
	}

	/**
	 * There may be a policy of preventing users from seeing exception message due to security reasons
	 */
	public static function proxyExceptionMessage($e)
	{
		if(method_exists($e, 'getMessageFriendly'))
		{
			return $e->getMessageFriendly();
		}
		else
		{
			return $e->getMessage();
		}
	}

	/**
	 * Use this to get info about methods supported. 
	 * This is just a reference generator for developers. Proper work is not guaranteed. Also untested on Windows.
	 * 
	 * @access private
	 */
	public function getDescription()
	{
		$list = $this->getClasses();

		$result = array();
		foreach($list as $item)
		{
			$methods = get_class_methods($item['CLASS']);
			$class = $item['CLASS'];
			$forbiddenMethods = $class::getForbiddenMethods();

			if(is_array($methods))
			{
				foreach($methods as $method)
				{
					if(!isset($forbiddenMethods[$method]))
					{
						$method = ToLower($method);

						if(is_callable(array($item['CLASS'], $method)))
						{
							$info = static::getMethodInfo($item['CLASS'], $method);

							$query = $item['ENTITY'].'.'.$method;
							$info['QUERY'] = $query;

							$result[$query] = $info;
						}
					}
				}
			}
		}

		//ksort($result);

		return $result;
	}

	public function getDescriptionFormatted()
	{
		$formatted = '';

		$desc = $this->getDescription();

		foreach($desc as $method)
		{
			$argsFormatted = array();
			if(is_array($method['ARGUMENTS']))
			{
				foreach($method['ARGUMENTS'] as $arg)
				{
					$argsFormatted[] = $arg['TYPE'].' '.$arg['NAME'].($arg['REQUIRED'] ? '*' : '');
				}
			}

			$formatted[] = $method['QUERY'].'('.implode(', ', $argsFormatted).')'.($method['DOC'] !== '' ? ' - '.$method['DOC'] : '');
		}

		return implode(PHP_EOL, $formatted);
	}

	protected function getMethodInfo($class, $method)
	{
		$info = new \ReflectionMethod($class, $method);

		$doc = '';
		$comment = $info->getDocComment();
		if((string) $comment !== '')
		{
			$found = array();
			preg_match('#/\*\*\s+\*([^\*]+)#', $comment, $found);

			if($found[1] !== '')
			{
				$doc = trim($found[1]);
			}
		}

		$args = array();
		$arguments = $info->getParameters();
		if(is_array($arguments))
		{
			foreach($arguments as $arg)
			{
				$argName = ToLower($arg->getName());
				$args[] = array(
					'NAME' => 		$argName,
					'TYPE' => 		$arg->isArray() ? 'array' : 'string',
					'REQUIRED' => 	!$arg->isOptional(),
				);
			}
		}

		return array(
			'DOC' => $doc,
			'ARGUMENTS' => $args
		);
	}

	protected function getClasses()
	{
		if($this->rootNamespace == false)
		{
			throw new Dispatcher\Exception('Root namespace incorrect'); // paranoid disorder
		}

		$dir = dirname(__FILE__).static::DIRECTORY_TO_CALLABLE;

		$result = array();

		if(Directory::isDirectoryExists($dir))
		{
			$index = array();
			static::walkDirectory($dir, $index, '');

			if(is_array($index['FILE']))
			{
				foreach($index['FILE'] as $fileName)
				{
					$fileName = str_replace($dir, '', $fileName);
					$fileName = explode('/', $fileName);
					if(is_array($fileName))
					{
						$query = array();
						foreach($fileName as $part)
						{
							if((string) $part !== '')
							{
								$query[] = preg_replace('#\.php$#', '', $part);
							}
						}
					}

					$result[] = array(
						'ENTITY' => implode('.', $query),
						'CLASS' => $this->rootNamespace.'\\'.implode('\\', array_map('ucfirst', $query))
					);
				}
			}
		}

		return $result;
	}

	// todo: rewirite this on \Bitrix\Main\IO functions
	protected static function walkDirectory($dir, &$index, $rootDir)
	{
		$fullDir = $rootDir.$dir;

		if(!is_readable($fullDir))
			return;

		if(is_file($fullDir))
		{
			$index['FILE'][] = $dir;
			return;
		}
		elseif(is_dir($fullDir) && (string) $dir != '')
		{
			$index['DIR'][] = $dir;
			sort($index['DIR'], SORT_STRING);
		}

		foreach(new \DirectoryIterator($fullDir) as $entry)
		{
			if($entry->isDot())
			{
				continue;
			}

			$file = $dir.'/'.$entry->getFilename();
			static::walkDirectory($file, $index, $rootDir);
		}
	}
}