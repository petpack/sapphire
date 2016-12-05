<?php
/**
 * Library of conversion functions, implemented as static methods.
 *
 * The methods are all of the form (format)2(format), where the format is one of
 * 
 *  raw: A UTF8 string
 *  attr: A UTF8 string suitable for inclusion in an HTML attribute
 *  js: A UTF8 string suitable for inclusion in a double-quoted javascript string.
 * 
 *  array: A PHP associative array
 *  json: JavaScript object notation
 *
 *  html: HTML source suitable for use in a page or email
 *  text: Plain-text content, suitable for display to a user as-is, or insertion in a plaintext email.
 * 
 * Objects of type {@link ViewableData} can have an "escaping type",
 * which determines if they are automatically escaped before output by {@link SSViewer}. 
 * 
 * @package sapphire
 * @subpackage misc
 */
class Convert {
	
	/**
	 * Convert a value to be suitable for an XML attribute.
	 * 
	 * @param array|string $val String to escape, or array of strings
	 * @return array|string
	 */
	static function raw2att($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2att($v);
			return $val;
		} else {
			return str_replace(array('&','"',"'",'<','>'), array('&amp;','&quot;','&#39;','&lt;','&gt;'), $val);
		}
	}
	
	/**
	 * Convert a value to be suitable for an HTML attribute.
	 * 
	 * This is useful for converting human readable values into
	 * a value suitable for an ID or NAME attribute.
	 * 
	 * @see http://www.w3.org/TR/REC-html40/types.html#type-cdata
	 * @uses Convert::raw2att()
	 * @param array|string $val String to escape, or array of strings
	 * @return array|string
	 */
	static function raw2htmlatt($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2htmlatt($v);
			return $val;
		} else {
			$val = self::raw2att($val);
			$val = preg_replace('/[^a-zA-Z0-9\-_]*/', '', $val);
			return $val;
		}
	}
	
	/**
	 * Ensure that text is properly escaped for XML.
	 * 
	 * @see http://www.w3.org/TR/REC-xml/#dt-escape
	 * @param array|string $val String to escape, or array of strings
	 * @return array|string
	 */
	static function raw2xml($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2xml($v);
			return $val;
		} else {
			return str_replace(array('&','<','>',"\n",'"',"'"), array('&amp;','&lt;','&gt;','<br />','&quot;','&#39;'), $val);
		}
	}
	
	/**
	 * Ensure that text is properly escaped for Javascript.
	 *
	 * @param array|string $val String to escape, or array of strings
	 * @return array|string
	 */
	static function raw2js($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2js($v);
			return $val;
		} else {
			return str_replace(array("\\", '"', "\n", "\r", "'"), array("\\\\", '\"', '\n', '\r', "\\'"), $val);
		}
	}
	
	/**
	 * Uses the PHP 5.2 native json_encode function if available,
	 * otherwise falls back to the Services_JSON class.
	 * 
	 * @see http://pear.php.net/pepr/pepr-proposal-show.php?id=198
	 * @uses Director::baseFolder()
	 * @uses Services_JSON
	 *
	 * @param mixed $val
	 * @return string JSON safe string
	 */
	static function raw2json($val) {
		if(function_exists('json_encode')) {
			return json_encode($val);	
		} else {
			require_once(Director::baseFolder() . '/sapphire/thirdparty/json/JSON.php');
			$json = new Services_JSON();
			return $json->encode($val);
		}
	}
	
	static function raw2sql($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::raw2sql($v);
			return $val;
		} else {
			return DB::getConn()->addslashes($val);
		}
	}

	/**
	 * Convert XML to raw text.
	 * @uses html2raw()
	 * @todo Currently &#xxx; entries are stripped; they should be converted
	 */
	static function xml2raw($val) {
		if(is_array($val)) {
			foreach($val as $k => $v) $val[$k] = self::xml2raw($v);
			return $val;
		} else {
			// More complex text needs to use html2raw instead
			if(strpos($val,'<') !== false) return self::html2raw($val);
			
			$converted = str_replace(array('&amp;','&lt;','&gt;','&quot;','&apos;', '&#39;'), array('&','<','>','"',"'", "'"), $val);
			$converted = ereg_replace('&#[0-9]+;', '', $converted);
			return $converted;
		}
	}
	
	/**
	 * Convert an array into a JSON encoded string.
	 * 
	 * @see http://pear.php.net/pepr/pepr-proposal-show.php?id=198
	 * @uses Director::baseFolder()
	 * @uses Services_JSON
	 * 
	 * @param array $val Array to convert
	 * @return string JSON encoded string
	 */
	static function array2json($val) {
		if(function_exists('json_encode')) {
			return json_encode($val);
		} else {
			require_once(Director::baseFolder() . '/sapphire/thirdparty/json/JSON.php');
			$json = new Services_JSON();
			return $json->encode($val);
		}
	}
	
	/**
	 * Convert a JSON encoded string into an object.
	 * 
	 * @see http://pear.php.net/pepr/pepr-proposal-show.php?id=198
	 * @uses Director::baseFolder()
	 * @uses Services_JSON
	 *
	 * @param string $val
	 * @return mixed JSON safe string
	 */
	static function json2obj($val) {
		require_once(Director::baseFolder() . '/sapphire/thirdparty/json/JSON.php');
		$json = new Services_JSON();
		return $json->decode($val);
	}

	/**
	 * Convert a JSON string into an array.
	 * 
	 * @uses json2obj
	 * @param string $val JSON string to convert
	 * @return array|boolean
	 */
	static function json2array($val) {
		$json = self::json2obj($val);
		if(!$json) return false;
		
		$arr = array();
		foreach($json as $k => $v) {
			$arr[$k] = $v;
		}
		
		return $arr;
	}
	
	/**
	 * Helper for Convert::prettyJSON()
	 * Returns a HTML <span> with a class matching the data type (integer,string,double,etc)
	 * 	Add css to colour the values according to type.
	 * 
	 * autodetects numeric strings and treats them as numbers 
	 * 
	 * runs htmlentities() and wordwrap() on values (wraps at 100 chars)
	 * 
	 * @param mixed $val	value to beautify
	 * @param int $indents	number of indents
	 * @param bool $isKey	true if this is a key name
	 * @return HTML
	 * @see Convert::prettyJSON()
	 * @see Convert::json2PrettyHTML() 
	 * 
	 */
	private static function jsonColor($val,$indents=1,$isKey=false) {
		//echo print_r($val,true) . ": " . gettype($val) . "\n";
		$type = gettype($val);
		
		if (($type == "string") && is_numeric($val)) {
			//try to convert it to a number
			$val = floatval($val);
			
			if (intval($val) == $val)	//convert from float to int if it's a whole number: 
				$val = intval($val);
			
			$type = gettype($val);
		}
		
		//$type = gettype($val);
		
		$color = "";
		switch($type) {
			case 'string':
				$val = '"' . $val . '"';
				break;
			case 'array':
				$val = self::prettyJSON($val,$indents);
				break;
		}
		$val = wordwrap(htmlentities($val),100,"<br />",true);
		
		if ($isKey) $type = $type . " key";
		
		return "<span class='$type'>" . //"' style='color:$color;'>" 
			"$val</span>"; // . " (" . gettype($val) . ")";
	}
	
	/**
	 * Helper for Convert::json2PrettyHtml()
	 * convert a value (i.e from json_decode) into a pretty colourised string
	 * @param array|string|number $json		value to prettify
	 * @param number $indents				indentation level (used for recursion)
	 * @return string
	 * @see Convert::json2PrettyHTML()
	 */
	private static function prettyJSON($json,$indents = 1) {
		$ret = "";
		$indent=str_repeat("<span class='indent'>&nbsp;</span>",$indents);
		if (is_array($json) || is_object($json) ) {
			foreach ($json as $k => $v) {
				$k = htmlentities($k);
				$kv = self::jsonColor($k,$indents,true) . ":\t";
				if (is_array($json)) {	//don't show keys for arrays
					$kv = "";
				}
				
				if (is_array($v) || is_object($v)) {	//recursively handle nested arrays/objects
					$v = self::prettyJson($v,$indents+1);
					$ret .= ($ret ? ",<br />\n" : "") . $indent .
						$kv ."<br />$v";
				} else {
					$ret .= ($ret ? ",<br />\n" : "") . $indent .
						$kv . self::jsonColor($v,$indents);
				}
			}
			if (is_object($json)) {
				$openbrace = "{";
				$closebrace = "}";
			} else {
				$openbrace = "[";
				$closebrace = "]";
			}
			$outdent=str_repeat("<span class='indent'>&nbsp;</span>",$indents-1);
			$ret = "$outdent$openbrace<br />\n$ret<br />\n$outdent$closebrace";
		} else
			$ret = self::jsonColor($json,$indents);
		
		return $ret;
		
	}
	
	/**
	 * Converts a JSON string to pretty, readable HTML output which can be 
	 * 	colourised/customised via CSS
	 * 
	 * Also does other nice things, like word wrapping at 100 chars, running 
	 * 	values through htmlentities(), and treating numeric strings as numbers
	 * 
	 * Include CSS to style the output (set colours, indent width, etc)
	 * Notes: 
	 * 		- everything will be wrapped in a span.json (i.e <span> with 'json' 
	 * 			as the class, css: span.json)
	 * 		- keys will be spans with the'key' class  ( e.g span.key )
	 * 		- values and keys will be spans and will have the datatype as the 
	 * 			class ( span.integer, span.key.integer)
	 * 		- there will be empty spans with the 'indent' class in the 
	 * 			appropriate places. There may be more than one consecutively. 
	 * 
	 * Example CSS is returned by the jsonPrettyHtmlCSS() function
			
	 * @param string $json	the json to beautify
	 * @return HTML
	 * @see Convert::jsonPrettyHtmlCSS()
	 */
	public static function json2PrettyHTML($json) {
		return "<span class='json'>" . self::prettyJSON(json_decode($json)) . "</span>";
	}
	
	/**
	 * Return or add some CSS for json2PrettyHTML to the requirements
	 * @param string $return	if true, return the CSS. Otherwise insert it using Requirements::customCSS()
	 * @return string | void
	 * @see Convert::json2PrettyHTML()
	 */
	public static function jsonPrettyHtmlCSS($return = true) {
		$css = 'span.json .integer, span.json .double {
				color: #700;
				font-family: mono;
			}
			
			span.json .string {
				color: #070;
				font-family: mono;
			}
			
			
			span.json .key.string {
				color: #007;
			}
			
			span.json .key.integer, span.json .key.double {
				color: #707;
			}
			
			
			span.json .indent {
				padding-left: 40px;
			}';
		if ($return) return $css;
		else return Requirements::customCSS($css,"jsonPrettyHtmlCSS");
	}
	
	/**
	 * @uses recursiveXMLToArray()
	 */
	static function xml2array($val) {
		$xml = new SimpleXMLElement($val);
		return self::recursiveXMLToArray($xml);
	}

	/**
	 * Function recursively run from {@link Convert::xml2array()}
	 * @uses SimpleXMLElement
	 */
	protected static function recursiveXMLToArray($xml) {
		if(is_object($xml) && get_class($xml) == 'SimpleXMLElement') {
			$attributes = $xml->attributes();
			foreach($attributes as $k => $v) {
				if($v) $a[$k] = (string) $v;
			}
			$x = $xml;
			$xml = get_object_vars($xml);
		}
		if(is_array($xml)) {
			if(count($xml) == 0) return (string) $x; // for CDATA
			foreach($xml as $key => $value) {
				$r[$key] = self::recursiveXMLToArray($value);
			}
			if(isset($a)) $r['@'] = $a; // Attributes
			return $r;
		}
		return (string) $xml;
	}
	
	/**
	 * Create a link if the string is a valid URL
	 * @param string The string to linkify
	 * @return A link to the URL if string is a URL
	 */
	static function linkIfMatch($string) {
		if( preg_match( '/^[a-z+]+\:\/\/[a-zA-Z0-9$-_.+?&=!*\'()%]+$/', $string ) )
			return "<a style=\"white-space: nowrap\" href=\"$string\">$string</a>";
		else
			return $string;
	}
	
	/**
	 * Simple conversion of HTML to plaintext.
	 * 
	 * @param $data string
	 * @param $preserveLinks boolean
	 * @param $wordwrap array 
	 */
	static function html2raw($data, $preserveLinks = false, $wordWrap = 60, $config = null) {
		$defaultConfig = array(
			'PreserveLinks' => false,
			'ReplaceBoldAsterisk' => true,
			'CompressWhitespace' => true,
			'ReplaceImagesWithAlt' => true,
		);
		if(isset($config)) {
			$config = array_merge($defaultConfig,$config);
		} else {
			$config = $defaultConfig;
		}

		// sTRIp style and script
		/* $data = eregi_replace("<style(^A-Za-z0-9>][^>]*)?>.*</style[^>]*>","", $data);*/
		/* $data = eregi_replace("<script(^A-Za-z0-9>][^>]*)?>.*</script[^>]*>","", $data);*/
		
		$data = preg_replace("/<(script|style)[^>]*>.*<\/(script|style)[^>]*?>/isU","", $data);

		if($config['ReplaceBoldAsterisk']) {
			$data = preg_replace('%<(strong|b)( [^>]*)?>|</(strong|b)>%i','*',$data);
		}
		
		// Expand hyperlinks
		if(!$preserveLinks && !$config['PreserveLinks']) {
			$data = preg_replace('/<a[^>]*href\s*=\s*"([^"]*)">(.*?)<\/a>/ie', "Convert::html2raw('\\2').'[\\1]'", $data);
			$data = preg_replace('/<a[^>]*href\s*=\s*([^ ]*)>(.*?)<\/a>/ie', "Convert::html2raw('\\2').'[\\1]'", $data);
			
			/* $data = eregi_replace('<a[^>]*href *= *"([^"]*)">([^<>]*)</a>', '\\2 [\\1]', $data); */
			/* $data = eregi_replace('<a[^>]*href *= *([^ ]*)>([^<>]*)</a>', '\\2 [\\1]', $data); */
		}
	
		// Replace images with their alt tags
		if($config['ReplaceImagesWithAlt']) {
			$data = eregi_replace('<img[^>]*alt *= *"([^"]*)"[^>]*>', ' \\1 ', $data);
			$data = eregi_replace('<img[^>]*alt *= *([^ ]*)[^>]*>', ' \\1 ', $data);
		}
	
		// Compress whitespace
		if($config['CompressWhitespace']) {
			$data = ereg_replace("[\n\r\t ]+", " ", $data);
		}
		
		// Parse newline tags
		$data = ereg_replace("[ \n\r\t]*<[Hh][1-6]([^A-Za-z0-9>][^>]*)?> *", "\n\n", $data);
		$data = ereg_replace("[ \n\r\t]*<[Pp]([^A-Za-z0-9>][^>]*)?> *", "\n\n", $data);
		$data = ereg_replace("[ \n\r\t]*<[Dd][Ii][Vv]([^A-Za-z0-9>][^>]*)?> *", "\n\n", $data);
		$data = ereg_replace("\n\n\n+","\n\n", $data);
		
		$data = ereg_replace("<[Bb][Rr]([^A-Za-z0-9>][^>]*)?> *", "\n", $data);
		$data = ereg_replace("<[Tt][Rr]([^A-Za-z0-9>][^>]*)?> *", "\n", $data);
		$data = ereg_replace("</[Tt][Dd]([^A-Za-z0-9>][^>]*)?> *", "    ", $data);
		$data = preg_replace('/<\/p>/i', "\n\n", $data );
		
	
		// Replace HTML entities
		//$data = preg_replace("/&#([0-9]+);/e", 'chr(\1)', $data);
		//$data = str_replace(array("&lt;","&gt;","&amp;","&nbsp;"), array("<", ">", "&", " "), $data);
		$data = html_entity_decode($data, ENT_COMPAT , 'UTF-8');
		// Remove all tags (but optionally keep links)
		
		// strip_tags seemed to be restricting the length of the output
		// arbitrarily. This essentially does the same thing.
		if(!$preserveLinks && !$config['PreserveLinks']) {
			$data = preg_replace('/<\/?[^>]*>/','', $data);
		} else {
			$data = strip_tags($data, '<a>');
		}
		return trim(wordwrap(trim($data), $wordWrap));
	}
	
	/**
	 * There are no real specifications on correctly encoding mailto-links,
	 * but this seems to be compatible with most of the user-agents.
	 * Does nearly the same as rawurlencode().
	 * Please only encode the values, not the whole url, e.g.
	 * "mailto:test@test.com?subject=" . Convert::raw2mailto($subject)
	 * 
	 * @param $data string
	 * @return string
	 * @see http://www.ietf.org/rfc/rfc1738.txt
	 */
	static function raw2mailto($data) {
		return str_ireplace(
			array("\n",'?','=',' ','(',')','&','@','"','\'',';'),
			array('%0A','%3F','%3D','%20','%28','%29','%26','%40','%22','%27','%3B'),
			$data
		);
	}
	
	/**
	 * convert an int number of seconds to a string like 'Xh Ym Zs'
	 * @param int $sec	number of seconds
	 * @param string $padHours	true to pad hours to 2 digits.
	 * @return string
	 */
	static function sec2hms ($sec, $padHours = false) {
		$hms = "";
		$hours = intval(intval($sec) / 3600);
		if ($hours > 0) {
			$hms .= ($padHours)
			? str_pad($hours, 2, "0", STR_PAD_LEFT). 'h '
			: $hours. 'h ';
		}
		$minutes = intval(($sec / 60) % 60);
		if ($minutes > 0) {
			$hms .= $minutes . "m ";
		}
		$seconds = intval($sec % 60);
		$hms .= $seconds;
		$hms .= "s";
		return $hms;
	}
	
	/**
	 * Convert a hex colour notation (#xxx. #xxxxxx) to an RGB value.
	 * 
	 * @param string $hex	the HTML hex colour (3 or 6 chars)
	 * @param string $asHtml if true, will return something like rgb(r,g,b),
	 * 							otherwise returns an array with keys 'r', 'g', and 'b'
	 */
	static function Hex2RGB($hex,$asHtml = false) {
		
		//trim leading hash:
		if (substr($hex,0,1) == "#")
			$hex = substr($hex,1);
		
		if (strlen($hex) == 3) {
			//convert 3 digit to 6 digit hex code - '#f07' -> '#ff0077'
			$hex = $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
		}
		
		$arr = str_split($hex,2);
		$keys = Array('r','g','b');
		$rgb = Array();
		for ($k=0; $k<3; $k++) {
			$rgb[$keys[$k]] = base_convert($arr[$k], 16, 10);
		}
		
		if ($asHtml) {
			return "rgb(" . $rgb['r'] . ',' . $rgb['g'] . "," . $rgb['b'] . ")"; 
		}
		
		return $rgb;
		
	}

}