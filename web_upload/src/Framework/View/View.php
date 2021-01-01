<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\View;

use Fenom;
use Fenom\Provider as FenomProvider;

use Psr\Http\Message\ResponseInterface as Response;

class View implements \ArrayAccess
{
	/**
	 * @var \Fenom
	 */
	protected $templater = null;

	/**
	 * @var array
	 */
	protected $defaultVariables = [];

	/**
	 * Constructor for \Framework\View.
	 *
	 * @param	string	$templatePath
	 * @param	string	$cachePath
	 * @param	array	$options
	 */
	public function __construct($templatePath, $cachePath, array $options)
	{
		$fenom = new Fenom(new FenomProvider($templatePath));
		$fenom->setCompileDir($cachePath);
		$fenom->setOptions($options);

		$this->templater = $fenom;
	}

	/**
	 * Fetch rendered template.
	 *
	 * @param	string	$template
	 * @param	array	$data
	 *
	 * @return	string
	 */
	public function fetch($template, array $data = [])
	{
		$data = array_merge($this->defaultVariables, $data);

		return $this->templater->fetch($template, $data);
	}

	/**
	 * Output rendered template
	 *
	 * @param	\Psr\Http\Message\ResponseInterface	$response
	 * @param	string								$template
	 * @param	array								$data
	 *
	 * @return	\Psr\Http\Message\ResponseInterface
	 */
	public function render(Response $response, $template, array $data = [])
	{
		$response->getBody()->write($this->fetch($template, $data));

		return $response;
	}

	/**
	 * @param	callable	$callback
	 * @return	$this
	 */
	public function addPreFilter($callback)
	{
		$this->templater->addPreFilter($callback);
		return $this;
	}

	/**
	 * @param	callable	$callback
	 * @return	$this
	 */
	public function addPostFilter($callback)
	{
		$this->templater->addPostFilter($callback);
		return $this;
	}

	/**
	 * @param	callable	$callback
	 * @return	$this
	 */
	public function addFilter($callback)
	{
		$this->templater->addFilter($callback);
		return $this;
	}

	/**
	 * @param	callable	$callback
	 * @return	$this
	 */
	public function addTagFilter($callback)
	{
		$this->templater->addTagFilter($callback);
		return $this;
	}

	/**
	 * Add modifier.
	 *
	 * @param	string		$modifier
	 * @param	callable	$callback
	 * @return	$this
	 */
	public function addModifier($modifier, $callback)
	{
		$this->templater->addModifier($modifier, $callback);
		return $this;
	}

	/**
	 * Add inline tag compiler.
	 *
	 * @param	string		$compiler
	 * @param	callable	$parser
	 * @return	$this
	 */
	public function addCompiler($compiler, $parser)
	{
		$this->templater->addCompiler($compiler, $parser);
		return $this;
	}

	/**
	 * @param	string			$compiler
	 * @param	string|object	$storage
	 * @return	$this
	 */
	public function addCompilerSmart($compiler, $storage)
	{
		$this->templater->addCompilerSmart($compiler, $storage);
		return $this;
	}

	/**
	 * Add block compiler
	 *
	 * @param	string			$compiler
	 * @param	callable		$open_parser
	 * @param	callable|string	$close_parser
	 * @param	array			$tags
	 * @return	$this
	 */
	public function addBlockCompiler($compiler, $open_parser, $close_parser, array $tags = [])
	{
		$this->templater->addBlockCompiler($compiler, $open_parser, $close_parser, $tags);
		return $this;
	}

	/**
	 * @param	string			$compiler
	 * @param	string|object	$storage
	 * @param	array			$tags
	 * @param	array			$floats
	 *
	 * @throws	LogicException
	 * @return	$this
	 */
	public function addBlockCompilerSmart($compiler, $storage, array $tags = [], array $floats = [])
	{
		$this->templater->addBlockCompilerSmart($compiler, $storage, $tags, $floats);
		return $this;
	}

	/**
	 * @param	string			$function
	 * @param	callable		$callback
	 * @param	callable|string	$parser
	 * @return	$this
	 */
	public function addFunction($function, $callback, $parser = Fenom::DEFAULT_FUNC_PARSER)
	{
		$this->templater->addFunction($function, $callback, $parser);
		return $this;
	}

	/**
	 * @param	string			$function
	 * @param	callable		$callback
	 * @return	$this
	 */
	public function addFunctionSmart($function, $callback)
	{
		$this->templater->addFunctionSmart($function, $callback);
		return $this;
	}

	/**
	 * @param	string			$function
	 * @param	callable		$callback
	 * @param	callable|string	$parser_open
	 * @param	callable|string	$parser_close
	 * @return	$this
	 */
	public function addBlockFunction($function, $callback, $parser_open, $parser_close)
	{
		$this->templater->addBlockFunction($function, $callback, $parser_open, $parser_close);
		return $this;
	}

	/**
	 * @param	array	$functions
	 * @return	$this
	 */
	public function addAllowedFunctions(array $funcs)
	{
		$this->templater->addAllowedFunctions($funcs);
		return $this;
	}

	/**
	 * Array Access interface for default variables.
	 */
	/**
	 * Does this collection have a given key?
	 *
	 * @param	string	$key
	 * @return	bool
	 */
	public function offsetExists($key)
	{
		return array_key_exists($key, $this->defaultVariables);
	}

	/**
	 * Get collection item for key
	 *
	 * @param	string	$key
	 *
	 * @return	mixed
	 */
	public function offsetGet($key)
	{
		return $this->defaultVariables[$key];
	}

	/**
	 * Set collection item
	 *
	 * @param	string	$key
	 * @param	mixed	$value
	 */
	public function offsetSet($key, $value)
	{
		$this->defaultVariables[$key] = $value;
	}
	/**
	 * Remove item from collection
	 *
	 * @param	string	$key
	 */
	public function offsetUnset($key)
	{
		unset($this->defaultVariables[$key]);
	}

	/**
	 * Countable interface for default variables.
	 */
	/**
	 * Get number of items in collection
	 *
	 * @return	int
	 */
	public function count()
	{
		return count($this->defaultVariables);
	}

	/**
	 * IteratorAggregate interface for default variables.
	 */
	/**
	 * Get collection iterator
	 *
	 * @return	\ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->defaultVariables);
	}
}
