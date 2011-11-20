<?php
/**
 * Stringifier
 *
 * Handles stringifying variables.
 *
 */
class Dev_Stringifier 
{

	/**
	 * Returns the given $variable as a string.
	 * @param mixed $variable
	 * @return string
	 */
	public static function getVariableAsString($variable) {
		$rv = '';
		if (is_object($variable)) {
			$rv = 'Object['.get_class($variable).']';
            if (self::objectHasMethod($variable, 'toDebugString')) {
				$rv .= '('.$variable->toDebugString().')';
            }
            elseif (self::objectHasMethod($variable, '_toString')) {
				$rv .= '('.$variable->_toString().')';
			}
		}
		elseif (is_bool($variable)) {
			if ($variable) {
				$rv = 'true';
			}
			else {
				$rv = 'false';
			}
		}
		elseif (is_array($variable)) {
			$rv = 'array(';
			$first = true;
			foreach ($variable as $key => $value) {
				if ($first) {
					$first = false;
				}
				else {
					$rv .= ', ';
				}
				$rv .= NL;
				$rv .= '['.self::getVariableAsString($key).'] => '.self::getVariableAsString($value);
			}
			if (!$first) {
				$rv .= NL;
			}
			$rv .= ')';
		}
		elseif (is_null($variable)) {
			$rv = 'null';
		}
		elseif (is_resource($variable)) {
			$rv = 'Resource('.get_resource_type($variable).' '.$variable.')';
		}
		else {
			$rv = $variable;
		}

		return $rv;
	}
	
	protected function objectHasMethod($object, $method) {
		if (method_exists($object, $method)) {
			return true;
		}
		if (method_exists($object, 'hasMethod')) {
			return $object->hasMethod($method);
		}
		return false;
	}
}
?>