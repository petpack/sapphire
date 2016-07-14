<?php
/**
 * Password input field.
 * @package forms
 * @subpackage fields-formattedinput
 */
class PasswordField extends PlaceholderField {

	/**
	 * maxlength of the password field
	 *
	 * @var SS_Int
	 */
	protected $maxLength;

	protected $preventAutoComplete;

	/**
	 * Returns an input field, class="text" and type="text" with an optional
	 * maxlength
	 */
	function __construct($name, $title = null, $value = "", $maxLength = null) {
		$this->maxLength = $maxLength;
		parent::__construct($name, $title, $value);
	}


	function Field() {
		$disabled = $this->isDisabled()?"disabled=\"disabled\"":"";
		$readonly = $this->isReadonly()?"readonly=\"readonly\"":"";
	
		if( $this->placeholder ) {
			$placeholder = ' placeholder="' . $this->placeholder . '"';
		} else {
			$placeholder = '';
		}
		
		$attributes = '';
		if( $this->preventAutoComplete ) {
			$attributes = ' autocomplete="off"';
		}
		if($this->maxLength) {
			return "<input class=\"text\" type=\"password\" id=\"" . $this->id() .
				"\" name=\"{$this->name}\" value=\"" . $this->attrValue() .
				"\" maxlength=\"$this->maxLength\" size=\"$this->maxLength\"$placeholder $disabled $readonly $attributes />";
		} else {
			return "<input class=\"text\" type=\"password\" id=\"" . $this->id() .
				"\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\"$placeholder $disabled $readonly $attributes />";
		}
	}

	function preventAutoComplete( $bool = true ) {
		$this->preventAutoComplete = $bool;
	}

	/**
	 * Makes a pretty readonly field with some stars in it
	 */
	function performReadonlyTransformation() {
		$stars = '*****';

		$field = new ReadonlyField($this->name, $this->title ? $this->title : '', $stars);
		$field->setForm($this->form);
		$field->setReadonly(true);
		return $field;
	}
}