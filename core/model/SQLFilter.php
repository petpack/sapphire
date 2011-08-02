<?php
/**
 * A simple utility to create a filters that automatically quotes input parameters.
 * 
 * @author Alex Hayes <alex.hayes@dimension27.com>
 */
class SQLFilter {

	protected $format = "";
	protected $args = array();
	
	/**
	 * Constuct the filter.
	 *
	 * <code>
	 * $foo = "Something";
	 * $bar = "What's up!";
	 * DataObject::get('MyDataObject', new SQLFilter('Foo = %s AND Bar != %s', $foo, $bar));
	 * </code>
	 * 
	 * The generated sql will be as follows:
	 * 
	 * <code>
	 * SELECT ... FROM MyDataObject WHERE Foo = 'Something' AND Bar != 'What\'s up!' 
	 * </code>
	 * 
	 * @param mixed $args,... The first argument should be a string that is used as the format argument to sprintf, each
	 *                        successive argument will be used as a replacement.
	 *                        
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
	public function __construct() {
		$args = func_get_args();
		call_user_func_array(array($this, 'push'), $args);
	}
	
	/**
	 * Return the filter as a string, quoting each argument.
	 *
	 * @return string
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
	public function __toString() {
		$args = array($this->format);
		foreach($this->args as $arg) {
			$args[] = "'".Convert::raw2sql($arg)."'";
		}
		return call_user_func_array('sprintf', $args);
	}
	
	/**
	 * Push an AND condition onto the stack.
	 *
	 * @param mixed $args,... The first argument should be a string that is used as the format argument to sprintf, each
	 *                        successive argument will be used as a replacement.
	 * @return void
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
	public function push() {
		$args = func_get_args();
		$this->pushWithOperator($args, 'AND');
	}
	
	/**
	 * Push an OR condition onto the stack.
	 * 
	 * @param mixed $args,... The first argument should be a string that is used as the format argument to sprintf, each
	 *                        successive argument will be used as a replacement.
	 * @return void
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
	public function pushOr() {
		$args = func_get_args();
		$this->pushWithOperator($args, 'OR');
	}
	
	/**
	 * Push a filter onto the stack.
	 *
	 * @param array $args       The first argument should be a string that is used as the format argument to sprintf, each
	 *                          successive argument will be used as a replacement.
	 * @param string $operator  The operator, if required, that will be used to join this filter with any previous filters.
	 * @return void
	 * 
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
	public function pushWithOperator($args = array(), $operator = 'AND') {
		$this->pushOperatorIfRequired($operator);
		$this->format .= array_shift($args);
		foreach($args as $arg) {
			array_push($this->args, $arg);
		}
	}

	/**
	 * Pushes an operator onto the format stack if required.
	 * 
	 * @param string $operator     An operator such as AND, OR etc..
	 * @return bool                True if an operator was pushed on, false otherwise (ie if there is not query already set).
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
	public function pushOperatorIfRequired($operator = 'AND') {
		if( empty($this->format) ) {
			return false;
		}
		$this->format .= ' ' . $operator . ' ';
		return true;
	}
	
	/**
	 * Returns true if a filter has been set.
	 * 
	 * @return bool
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
	public function exists() {
		if( !empty($this->format) ) {
			return true;
		}
		return false;
	}
	
}