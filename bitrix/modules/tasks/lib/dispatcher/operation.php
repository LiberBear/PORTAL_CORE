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

namespace Bitrix\Tasks\Dispatcher;

use Bitrix\Tasks\Dispatcher;

//use \Bitrix\Main\Localization\Loc;

use \Bitrix\Tasks\Util\Error\Collection;

final class Operation
{
	const ARGUMENT_TYPE_STRING = 	'string';
	const ARGUMENT_TYPE_ARRAY = 	'array';

	protected $errors = 	null;
	protected $operation = 	array();
	protected $namespace = 	false;

	protected $parsed = 	array();

	public function __construct($operation, array $parameters = array())
	{
		$this->operation = $operation;
		$this->errors = new Collection();

		if((string) $parameters['NAMESPACE'] != '')
		{
			$this->namespace = $parameters['NAMESPACE'];
		}
		else
		{
			throw new Exception('Root NAMESPACE must be specified');
		}
	}

	public function getOperation()
	{
		return $this->operation;
	}

	public function parse()
	{
		// parse out class name and method name
		$this->parsed = $this->parseQueryPath($this->operation['OPERATION']);

		// check arguments presense
		if(!isset($this->operation['ARGUMENTS']))
		{
			$this->operation['ARGUMENTS'] = array();
		}
		elseif(!is_array($this->operation['ARGUMENTS']))
		{
			$this->addParseError('Arguments must be of type array for '.$this->operation['OPERATION']);
		}

		if(!$this->errors->checkHasErrors())
		{
			$this->checkClass();
		}

		if(!$this->errors->checkHasErrors())
		{
			$this->parsed['SIGNATURE'] = $this->getMethodSignature();
			$this->parsed['ARGUMENTS'] = $this->prepareArguments(); // re-order and check
		}
	}

	public function call()
	{
		if($this->parsed['SIGNATURE']['STATIC'])
		{
			$result = call_user_func_array($this->parsed['CLASS'].'::'.$this->parsed['METHOD'], $this->parsed['ARGUMENTS']);
			// some errors here???
		}
		else
		{
			$class = $this->parsed['CLASS'];
			$instance = new $class();

			if($instance->canExecute())
			{
				$result = call_user_func_array(array($instance, $this->parsed['METHOD']), $this->parsed['ARGUMENTS']);
			}

			$this->errors->addForeignErrors($instance->getErrorCollection());
		}

		return $result;
	}

	protected function prepareArguments()
	{
		$result = array();
		if(!empty($this->parsed['SIGNATURE']['ARGUMENTS']))
		{
			$this->operation['ARGUMENTS'] = array_change_key_case($this->operation['ARGUMENTS'], CASE_LOWER);

			foreach($this->parsed['SIGNATURE']['ARGUMENTS'] as $argName => $argDesc)
			{
				if(!isset($this->operation['ARGUMENTS'][$argName]) && $argDesc['REQUIRED'])
				{
					$this->addParseError('Argument "'.$argName.'" is required, but no value passed for '.$this->parsed['FULLPATH']);
					continue;
				}

				if(!isset($this->operation['ARGUMENTS'][$argName]) && !$argDesc['REQUIRED'])
				{
					break;
				}

				if(isset($this->operation['ARGUMENTS'][$argName]) && !is_array($this->operation['ARGUMENTS'][$argName]) && $argDesc['TYPE'] == static::ARGUMENT_TYPE_ARRAY)
				{
                    if($argDesc['TYPE'] == static::ARGUMENT_TYPE_ARRAY)
                    {
                        if((string) $this->operation['ARGUMENTS'][$argName] == '')
                        {
                            // it seems an empty array was transferred as an empty string, replace then
                            $this->operation['ARGUMENTS'][$argName] = array();
                        }
                        elseif(!is_array($this->operation['ARGUMENTS'][$argName]))
                        {
                            $this->addParseError('Argument "'.$argName.'" must be of type array, but given something else for '.$this->parsed['FULLPATH']);
                        }
                    }
				}

				$result[$argName] = $this->operation['ARGUMENTS'][$argName];
			}
		}

		return $result;
	}

	protected function getMethodSignature()
	{
		$info = new \ReflectionMethod($this->parsed['CLASS'], $this->parsed['METHOD']);

		$result = array(
			'STATIC' => $info->isStatic(),
			'ARGUMENTS' => array()
		);
		$arguments = $info->getParameters();
		if(is_array($arguments))
		{
			foreach($arguments as $arg)
			{
				$argName = ToLower($arg->getName());
				$result['ARGUMENTS'][$argName] = array(
					'NAME' => 		$argName,
					'TYPE' => 		$arg->isArray() ? self::ARGUMENT_TYPE_ARRAY : self::ARGUMENT_TYPE_STRING,
					'REQUIRED' => 	!$arg->isOptional(),
				);
			}
		}

		return $result;
	}

	protected function checkClass()
	{
		if(class_exists($this->parsed['CLASS']) && is_subclass_of($this->parsed['CLASS'], 'TasksBaseComponent'))
		{
			// its a component class. Such class can not be loaded by autoloader, so it must be pre-loaded above
			$class = $this->parsed['CLASS'];
			$allowedMethods = $class::getAllowedMethods();
			if(!is_array($allowedMethods))
			{
				throw new \Bitrix\Tasks\Exception('Method '.$class.'::allowedMethods() returned a non-array value, too frightful to execute');
			}
			else
			{
				$allowedMethods = array_flip($allowedMethods);
				$allowedMethods = array_change_key_case($allowedMethods, CASE_LOWER);
			}

			// in the component class the easiest way to control accessibility of methods is the white-list,
			// because there are also huge amount of methods that can be potentially called by mistake
			if(!isset($allowedMethods[$this->parsed['METHOD']]))
			{
				$this->addParseError('Method is not allowed to call: '.$this->parsed['FULLPATH']);
				return;
			}
		}
		else
		{
			$this->parsed['CLASS'] = '\\'.$this->namespace.'\\'.$this->parsed['CLASS'];

			// in the callable class each public method is meant to be callable outside, and only few methods are not, so the black-list here
			if(class_exists($this->parsed['CLASS']) && is_subclass_of($this->parsed['CLASS'], '\Bitrix\Tasks\Dispatcher\PublicAction'))
			{
				$class = $this->parsed['CLASS'];
				$forbiddenMethods = $class::getForbiddenMethods();
				if(!is_array($forbiddenMethods))
				{
					throw new \Bitrix\Tasks\Exception('Method '.$class.'::getForbiddenMethods() returned a non-array value, too frightful to execute');
				}
				else
				{
					$forbiddenMethods = array_flip($forbiddenMethods);
					$forbiddenMethods = array_change_key_case($forbiddenMethods, CASE_LOWER);
				}

				if(isset($forbiddenMethods[$this->parsed['METHOD']]))
				{
					$this->addParseError('Method is not allowed to call: '.$this->parsed['FULLPATH']);
					return;
				}
			}
			else
			{
				$this->addParseError('Entity not found: '.$this->parsed['ENTITY']);
			}
		}

		if(!is_callable($this->parsed['CLASS'].'::'.$this->parsed['METHOD']))
		{
			$this->addParseError('Method not found or not callable: '.$this->parsed['FULLPATH']);
		}
	}

	protected function parseQueryPath($path)
	{
		$path = ToLower(trim((string) $path));

		// not empty
		// contains at least two parts: entity.method, each part should not start with a digit, should not start from or end with comma
		if(!isset($path) || $path == '' || !preg_match('#^([a-z_]+[a-z0-9_]+)(\.[a-z_]+[a-z0-9_]+)+$#', $path))
		{
			$this->addParseError('Incorrect method name');
			return;
		}

		$fullPath = $path;
		$path = explode('.', $path);
		$method = array_pop($path);

		$namespace = array_map('ucfirst', $path);

		return array(
			'FULLPATH' => 		$fullPath,
			'ENTITY' => 	implode('.', $path),
			'CLASS' => 		implode('\\', $namespace),
			'METHOD' => 	$method
		);
	}

	protected function addParseError($message)
	{
		$this->errors->add('PARSE_ERROR', $message, Dispatcher::ERROR_TYPE_PARSE, $this->getSupplementaryErrorInfo());
	}

	protected function getSupplementaryErrorInfo()
	{
		return array(
			'QUERY' => $this->operation
		);
	}

	public function getErrorCollection()
	{
		return $this->errors;
	}
}