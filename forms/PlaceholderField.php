<?php
/**
 * Text input field.
 * @package forms
 * @subpackage fields-basic
 */
class PlaceholderField extends FormField {

	/**
	 * @var string
	 */
	protected $placeholder = false;
	
	/**
	 * Set the placeholder attribute.
	 * 
	 * @param bool|string $placeholder     Placeholder as a string or if true then the Title() for the field is used
	 *
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
	function setPlaceholder($placeholder = true) {
		if( $placeholder )
			$this->placeholder = is_bool($placeholder) ? $this->Title() : $placeholder;
	}
	
}