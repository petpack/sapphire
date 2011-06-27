<?php
/**
 * A simple utility to create a filters that automatically quotes input parameters.
 * 
 * @author Alex Hayes <alex.hayes@dimension27.com>
 */
class SQLFilter {

	protected $format;
	protected $args;
	
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
		$this->format = array_shift($args);
		$this->args   = $args;
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
	
}