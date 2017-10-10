<?php
/**
 * Represents an image attached to a page.
 * @package sapphire
 * @subpackage filesystem
 */
class Image extends File {
	
	/**
	 * Sets Image pixel limit, to restrict images to a 
	 * 	certain size - if they're too large, they'll eat up all available
	 * 	memory doing GD operations.
	 * 
	 * Set to <=0 to disable this option.
	 * 
	 * When this is set, images with more than this many pixels will be 
	 * 	scaled down to a reasonable size (~90% of this pixel count) 
	 * 	using ImageMagick.
	 * 
	 * You'll probably want to set this in _config.php
	 * 
	 * Enabling this functionality requires that you install the ImageMagick
	 * 	PECL plugin. (maybe by running 'pecl install Imagick').
	 * 	use Image::canHasImageMagick() to test whether it is installed
	 * 
	 * @see suggestMaxPixels() 
	 * @see ensureNotInsanelyHuge()
	 * @var int
	 */
	static public $maxPixels = -1;
	
	/**
	 * Whether to dump messages like 'image is insanely huge, resizing 
	 * 	from <x>x<y> to <x>x<y>' to the error log when a large image is 
	 * 	resized
	 * @var boolean
	 */
	static public $logHugeImages = False;
	
	/**
	 * Provides a suggestion for self::$maxPixels based on PHP's memory limit,
	 * current memory usage, and image channels 
	 * 	(i.e 8/16/24/32 bit images. We default to 32 bit - 24bit + alpha).
	 * Note that the return from this is pretty damn unpredictable due to the
	 * 	highly variable nature of current memory usage
	 * @param int $bitsPerPixel		number of bits per pixel in image
	 * @param float $safetyBuffer	fraction of memory to be used (0-1)
	 * @return int
	 */
	static public function suggestMaxPixels($bitsPerPixel = 32,$safetyBuffer = 0.75) {
		$usage = memory_get_usage();
		$limit = ini_get('memory_limit');
		//convert hunam-readable '128M' to bytes
		if (preg_match('/^\s*([0-9.]+)\s*([KMGTPE])B?\s*$/i', $limit, $matches)) {
			$num = (float)$matches[1];
			switch (strtoupper($matches[2])) {
				case 'E':
					$num = $num * 1024;
				case 'P':
					$num = $num * 1024;
				case 'T':
					$num = $num * 1024;
				case 'G':
					$num = $num * 1024;
				case 'M':
					$num = $num * 1024;
				case 'K':
					$num = $num * 1024;
			}
			//echo "\nlimit: $num, usage: $usage\n";
			$available = $num - $usage;
		} else {
			// unknown response. possibly -1, indicating no limit
			$available = 104857600; //assume 100MB;
		}
		return intval(($available * $safetyBuffer) / ($bitsPerPixel / 8));
	}
	

	/**
	 * Return full path and filename to image file
	 * @return string
	 */
	function absoluteFilename() {
		return Director::baseFolder() . '/' . $this->getField('Filename');
	}
	
	/**
	 * Returns the number of pixels in the image
	 * @return int
	 */
	function pixelCount() {
		$dim = $this->getDimensions("Array");
		return $dim['width'] * $dim['height'];
	}

	/**
	 * Tells us whether the ImageMagick PECL extension is installed. 
	 */
	static public function canHasImageMagick(){
		return class_exists("Imagick");
	}
	
	/**
	 * Returns a boolean indicating whether the image is too large
	 * 	(i.e pixelCount() > $maxPixels)
	 * @return boolean
	 */
	function isTooBig() {
		return (self::$maxPixels > 0 && 
			($this->pixelCount() > self::$maxPixels)
		);
	}
	
	/**
	 * Ensures the image isn't too large (according to Image::$maxPixels)
	 * 	if it is too large, use ImageMagick to scale it down to something
	 * 	reasonable.
	 * You should call this before the image is displayed - it's painful
	 * 	to handle this when the file is uploaded.
	 * WILL DIE HORRIBLY IF IMAGICK IS NOT INSTALLED!
	 * @return boolean
	 */
	function ensureNotInsanelyHuge() {
		
		//debugging:
		//if (!$this->isTooBig()) error_log($this->Filename . " is not too large");
		//if (!file_exists($this->absoluteFilename())) error_log($this->Filename . " does not exist!");
		
		if (!$this->isTooBig() || !file_exists($this->absoluteFilename())) 
			return False;
		
		if (Image::canHasImageMagick()) {
			$size = $this->getDimensions("Array");
			
			$ar = $size['width'] / $size['height'];
			
			//determine new image size (use 90% of $maxPixels)
			$newHeight = intval(sqrt((self::$maxPixels * 0.9) / $ar));
			$newWidth = intval($newHeight * $ar);
			
			if (self::$logHugeImages) { 
				error_log($this->Filename . " is insanely huge - Resizing from " . 
					$size['width'] . 'x' . $size['height'] . 
					" to {$newWidth}x{$newHeight}"
				);
			}
			
			$img = new Imagick($filename = $this->absoluteFilename());
			$img->scaleImage($newWidth,$newHeight);
			$img->writeImage($filename);
		} else {
			die('Please install the Imagick PECL Plugin.');
		}
		
		return true;
	}
	
	/**
	 * Duplicates both the dataobject and file.
	 * @see DataObject::duplicate()
	 */
	public function duplicate() {
		$c=0;
		$src = $this->getFullPath();
		
		if (!file_exists($src) || !is_file($src)) {
			//hmmm... what to do if the file doesn't exist?
			error_log("Image::duplicate: file '" . $src . 
				"' does not exist or cannot be duplicated!");
			
			$rec = parent::duplicate();
			
			return $rec;
		}
		
		$fn=$src;
		$ext = pathinfo($fn, PATHINFO_EXTENSION);
		
		while (file_exists($fn)) {
			$c++;
			if ($ext)
				$fn = str_replace(".$ext", "-$c.$ext", $src);
			else
				$fn = "$src-$c";
		}
		
		//error_log("Duplicate '$src' -> '$fn'");
		//copy the file:
		copy($src, $fn);
		
		$rec = parent::duplicate(false);
		
		$rec->Filename = $fn;
		
		$rec->write();
		
		return $rec;
		
	}
	
	function validate() {
		$this->ensureNotInsanelyHuge();
		return parent::validate();
	}
	
	function onBeforeWrite() {
		$this->ensureNotInsanelyHuge();
		return parent::onBeforeWrite();
	}
	
	const ORIENTATION_SQUARE = 0;
	const ORIENTATION_PORTRAIT = 1;
	const ORIENTATION_LANDSCAPE = 2;
	
	/**
	 * CSS Class(es) to be added to <img> tag
	 * @var string
	 */
	public $classes = "";
	
	static $casting = array(
		'Tag' => 'HTMLText',
	);

	/**
	 * The width of an image thumbnail in a strip.
	 * @var int
	 */
	public static $strip_thumbnail_width = 50;
	
	/**
	 * The height of an image thumbnail in a strip.
	 * @var int
	 */
	public static $strip_thumbnail_height = 50;
	
	/**
	 * The width of an image thumbnail in the CMS.
	 * @var int
	 */
	public static $cms_thumbnail_width = 100;
	
	/**
	 * The height of an image thumbnail in the CMS.
	 */
	public static $cms_thumbnail_height = 100;
	
	/**
	 * The width of an image thumbnail in the Asset section.
	 */
	public static $asset_thumbnail_width = 100;
	
	/**
	 * The height of an image thumbnail in the Asset section.
	 */
	public static $asset_thumbnail_height = 100;
	
	/**
	 * The width of an image preview in the Asset section.
	 */
	public static $asset_preview_width = 400;
	
	/**
	 * The height of an image preview in the Asset section.
	 */
	public static $asset_preview_height = 200;
	
	/**
	 * Set up template methods to access the transformations generated by 'generate' methods.
	 */
	public function defineMethods() {
		$methodNames = $this->allMethodNames();
		foreach($methodNames as $methodName) {
			if(substr($methodName,0,8) == 'generate') {
				$this->addWrapperMethod(substr($methodName,8), 'getFormattedImage');
			}
		}
		
		parent::defineMethods();
	}
	
	/**
	 * Returns true if the Filename attribute has been set (absolutely useless) or if the record exists in the database.
	 * 
	 * An image exists if it has a filename. Does not do any filesystem checks.
	 * 
	 * @param boolean $existsInDB     If true then parent::exists() is called rather than just checking if the 
	 *                                Filename attribute has been set (which honestly, is useless).
	 * @return boolean
	 * 
	 * @author Alex Hayes <alex.hayes@dimension27.com> (added support to check if database record exists)
	 * @ffs How is this vaguely useful to just check if a field is set! Adding support so that access to 
	 *      the parent is possible.
	 */
	public function exists( $existsInDB = false ) {
		if( $existsInDB ) {
			return parent::exists();
		}
		else {
			// Existing (useless) functionality
			if(isset($this->record["Filename"])) {
				return true;
			}
			return false;
		}
	}
	
	/**
	 * Determines if width and height tags should be included in generated tags
	 * @var unknown
	 */
	public $widthTag = False;
	public $heightTag = False;

	/**
	 * Return an XHTML img tag for this Image,
	 * or NULL if the image file doesn't exist on the filesystem.
	 * 
	 * @return string
	 */
	function getTag() {
		$this->ensureNotInsanelyHuge();
		if(file_exists(Director::baseFolder() . '/' . $this->Filename)) {
			$url = $this->getURL();
			$title = ($this->Title) ? $this->Title : $this->Filename;
			if($this->Title) {
				$title = Convert::raw2att($this->Title);
			} else {
				if(preg_match("/([^\/]*)\.[a-zA-Z0-9]{1,6}$/", $title, $matches)) $title = Convert::raw2att($matches[1]);
			}
			
			$html = "<img src=\"$url\" alt=\"$title\" ";
			
			if ($this->widthTag || $this->heightTag) {
				$dimensions = $this->getDimensions("array");
				if ($this->widthTag)
					$html .= ' width="' . $dimensions['width'] . '"';
				
				if ($this->heightTag)
					$html .= ' height="' . $dimensions['height'] . '"';
			}
			
			if ($this->classes) 
				$html .= ' class="' . $this->classes . '"';
			
			$html .= "/>";
			
			return $html;
		}
	}
	
	/**
	 * Add a css class to the image. 
	 * 	You can add more than one if you separate them by spaces.
	 * returns $this, so you can chain this with another function, e.g
	 * 	$image->addCssClass('something')->SetWidth(42);
	 * 	(though in this example you could just use SetWidthAndClass() )
	 * @see Image::SetWidthAndClass
	 * @param string $class
	 */
	function addCssClass($class) {
		if ($this->classes) {
			if (strpos($this->classes,$class) === false)	//don't add the same class multiple times
				$this->classes .= " " . $class;
		} else
			$this->classes = $class;
		return $this;
	}
	
	
	/**
	 * Return an XHTML img tag for this Image.
	 * 
	 * @return string
	 */
	function forTemplate() {
		return $this->getTag();
	}

	function loadUploadedImage($tmpFile) {
		if(!is_array($tmpFile)) {
			user_error("Image::loadUploadedImage() Not passed an array.  Most likely, the form hasn't got the right enctype", E_USER_ERROR);
		}
		
		if(!$tmpFile['size']) {
			return;
		}
		
		$class = $this->class;

		// Create a folder		
		if(!file_exists(ASSETS_PATH)) {
			mkdir(ASSETS_PATH, Filesystem::$folder_create_mask);
		}
		
		if(!file_exists(ASSETS_PATH . "/$class")) {
			mkdir(ASSETS_PATH . "/$class", Filesystem::$folder_create_mask);
		}

		// Generate default filename
		$file = str_replace(' ', '-',$tmpFile['name']);
		$file = ereg_replace('[^A-Za-z0-9+.-]+','',$file);
		$file = ereg_replace('-+', '-',$file);
		if(!$file) {
			$file = "file.jpg";
		}
		
		$file = ASSETS_PATH . "/$class/$file";
		
		while(file_exists(BASE_PATH . "/$file")) {
			$i = $i ? ($i+1) : 2;
			$oldFile = $file;
			$file = ereg_replace('[0-9]*(\.[^.]+$)',$i . '\\1', $file);
			if($oldFile == $file && $i > 2) user_error("Couldn't fix $file with $i", E_USER_ERROR);
		}
		
		if(file_exists($tmpFile['tmp_name']) && copy($tmpFile['tmp_name'], BASE_PATH . "/$file")) {
			// Remove the old images

			$this->deleteFormattedImages();
			return true;
		}
	}
	
	/** 
	 * Does addCssClass and SetWidth. For templates.
	 * @param int $width
	 * @param string $class
	 * @return Image_Cached
	 */
	public function SetWidthAndClass($width,$class) {
		$this->addCssClass($class);
		return $this->SetWidth($width);
	}
	
	/**
	 * For embedding images in a newsletter.
	 * All newsletter images should be scaled to 600px (i.e 100%) wide
	 * 	(for responsive view) and they should have a 'width' attribute to 
	 * 	set their desired width
	 * 	(thanks, outlook. We'd prefer to just use max-width, but nooooooo)
	 * @param string $class	css classes
	 * @param number $widthTag	desired display width
	 * @return mixed
	 */
	public function forNewsletter($class,$widthTag = 170) {
		$ret = $this->SetWidthAndClass(600, $class);
		
		$ret = preg_replace('|/>$|', ' width="' . $widthTag . '" />', $this->getTag());
		
		return $ret;
	}
	
	public function SetWidth($width) {
		return $this->getFormattedImage('SetWidth', $width);
	}
	
	public function SetHeight($height) {
		return $this->getFormattedImage('SetHeight', $height);
	}
	
	public function SetSize($width, $height) {
		return $this->getFormattedImage('SetSize', $width, $height);
	}
	
	public function SetRatioSize($width, $height) {
		return $this->getFormattedImage('SetRatioSize', $width, $height);
	}
	
	public function generateSetRatioSize(GD $gd, $width, $height) {
		return $gd->resizeRatio($width, $height);
	}
	
	/**
	 * Resize this Image by width, keeping aspect ratio. Use in templates with $SetWidth.
	 * @return GD
	 */
	public function generateSetWidth(GD $gd, $width) {
		return $gd->resizeByWidth($width);
	}
	
	/**
	 * Resize this Image by height, keeping aspect ratio. Use in templates with $SetHeight.
	 * @return GD
	 */
	public function generateSetHeight(GD $gd, $height){
		return $gd->resizeByHeight($height);
	}
	
	/**
	 * Resize this Image by both width and height, using padded resize. Use in templates with $SetSize.
	 * @return GD
	 */
	public function generateSetSize(GD $gd, $width, $height) {
		return $gd->paddedResize($width, $height);
	}
	
	public function CMSThumbnail() {
		return $this->getFormattedImage('CMSThumbnail');
	}
	
	/**
	 * Resize this image for the CMS. Use in templates with $CMSThumbnail.
	 * @return GD
	 */
	function generateCMSThumbnail(GD $gd) {
		return $gd->paddedResize($this->stat('cms_thumbnail_width'),$this->stat('cms_thumbnail_height'));
	}
	
	/**
	 * Resize this image for preview in the Asset section. Use in templates with $AssetLibraryPreview.
	 * @return GD
	 */
	function generateAssetLibraryPreview(GD $gd) {
		return $gd->paddedResize($this->stat('asset_preview_width'),$this->stat('asset_preview_height'));
	}
	
	/**
	 * Resize this image for thumbnail in the Asset section. Use in templates with $AssetLibraryThumbnail.
	 * @return GD
	 */
	function generateAssetLibraryThumbnail(GD $gd) {
		return $gd->paddedResize($this->stat('asset_thumbnail_width'),$this->stat('asset_thumbnail_height'));
	}
	
	/**
	 * Resize this image for use as a thumbnail in a strip. Use in templates with $StripThumbnail.
	 * @return GD
	 */
	function generateStripThumbnail(GD $gd) {
		return $gd->croppedResize($this->stat('strip_thumbnail_width'),$this->stat('strip_thumbnail_height'));
	}
	
	function generatePaddedImage(GD $gd, $width, $height) {
		return $gd->paddedResize($width, $height);
	}

	/**
	 * Return an image object representing the image in the given format.
	 * This image will be generated using generateFormattedImage().
	 * The generated image is cached, to flush the cache append ?flush=1 to your URL.
	 * @param string $format The name of the format.
	 * @param string $arg1 An argument to pass to the generate function.
	 * @param string $arg2 A second argument to pass to the generate function.
	 * @return Image_Cached
	 */
	function getFormattedImage($format, $arg1 = null, $arg2 = null) {
		$this->ensureNotInsanelyHuge();
		if($this->ID && $this->Filename && Director::fileExists($this->Filename)) {
			$cacheFile = $this->cacheFilename($format, $arg1, $arg2);

			if(!file_exists(Director::baseFolder()."/".$cacheFile) || isset($_GET['flush'])) {
				$this->generateFormattedImage($format, $arg1, $arg2);
			}
			
			$cached = new Image_Cached($cacheFile);
			// Pass through the title so the templates can use it
			$cached->Title = $this->Title;
			//and css classes
			$cached->classes = $this->classes;
			
			return $cached;
		}
	}
	
	/**
	 * Return the filename for the cached image, given it's format name and arguments.
	 * @param string $format The format name.
	 * @param string $arg1 The first argument passed to the generate function.
	 * @param string $arg2 The second argument passed to the generate function.
	 * @return string
	 */
	function cacheFilename($format, $arg1 = null, $arg2 = null) {
		$folder = $this->ParentID ? $this->Parent()->Filename : ASSETS_DIR . "/";
		
		$format = $format.$arg1.$arg2;
		
		return $folder . "_resampled/$format-" . $this->Name;
	}
	
	/**
	 * Generate an image on the specified format. It will save the image
	 * at the location specified by cacheFilename(). The image will be generated
	 * using the specific 'generate' method for the specified format.
	 * @param string $format Name of the format to generate.
	 * @param string $arg1 Argument to pass to the generate method.
	 * @param string $arg2 A second argument to pass to the generate method.
	 */
	function generateFormattedImage($format, $arg1 = null, $arg2 = null) {
		$cacheFile = $this->cacheFilename($format, $arg1, $arg2);
	
		$gd = new GD($this->getFullPath());
		
		
		if($gd->hasGD()){
			$generateFunc = "generate$format";		
			if($this->hasMethod($generateFunc)){
				$gd = $this->$generateFunc($gd, $arg1, $arg2);
				if($gd){
					$gd->writeTo(Director::baseFolder()."/" . $cacheFile);
				}
	
			} else {
				USER_ERROR("Image::generateFormattedImage - Image $format function not found.",E_USER_WARNING);
			}
		}
	}
	
	/**
	 * Generate a resized copy of this image with the given width & height.
	 * Use in templates with $ResizedImage.
	 */
	function generateResizedImage($gd, $width, $height) {
		if(is_numeric($gd) || !$gd){
			USER_ERROR("Image::generateFormattedImage - generateResizedImage is being called by legacy code or gd is not set.",E_USER_WARNING);
		}else{
			return $gd->resize($width, $height);
		}
	}

	/**
	 * Generate a resized copy of this image with the given width & height, cropping to maintain aspect ratio.
	 * Use in templates with $CroppedImage
	 */
	function generateCroppedImage($gd, $width, $height) {
		return $gd->croppedResize($width, $height);
	}
	
	/**
	 * Remove all of the formatted cached images for this image.
	 * @return int The number of formatted images deleted
	 */
	public function deleteFormattedImages() {
		if(!$this->Filename) return 0;
		
		$numDeleted = 0;
		$methodNames = $this->allMethodNames();
		$cachedFiles = array();
		
		$folder = $this->ParentID ? $this->Parent()->Filename : ASSETS_DIR . '/';
		$cacheDir = Director::getAbsFile($folder . '_resampled/');
		
		if(is_dir($cacheDir)) {
			if($handle = opendir($cacheDir)) {
				while(($file = readdir($handle)) !== false) {
					// ignore all entries starting with a dot
					if(substr($file, 0, 1) != '.' && is_file($cacheDir . $file)) {
						$cachedFiles[] = $file;
					}
				}
				closedir($handle);
			}
		}
		
		foreach($methodNames as $methodName) {
			if(substr($methodName, 0, 8) == 'generate') {
				$format = substr($methodName, 8);
				$pattern = '/^' . $format . '[^\-]*\-' . $this->Name . '$/i';
				foreach($cachedFiles as $cfile) {
					if(preg_match($pattern, $cfile)) {
						if(Director::fileExists($cacheDir . $cfile)) {
							unlink($cacheDir . $cfile);
							$numDeleted++;
						}
					}
				}
			}
		}
		
		return $numDeleted;
	}
	
	/**
	 * Get the dimensions of this Image.
	 * @param string $dim If this is equal to "string", return the dimensions in string form,
	 * if it is 1 return the height, if it is 0 return the width.
	 * @return string|int
	 */
	function getDimensions($dim = "string") {
		if (!is_numeric($dim)) $dim = strtolower($dim);
		if($this->getField('Filename')) {
			$imagefile = $this->getFullPath();
			//DM: Dear silverstripe devs: great work on the lack of consistency!
			//$imagefile = Director::baseFolder() . '/' . $this->getField('Filename');
			if(file_exists($imagefile)) {
				$size = getimagesize($imagefile);
				if ($dim === "string") {
					$rv = "$size[0]x$size[1]";
				} else if ($dim === 'array') {
					$rv = Array(
						'width'  => $size[0],
						'height' => $size[1],
					);					
				} else if (is_numeric($dim)) {
					$rv = $size[$dim];
				}
				return $rv;
			} else {
				return ($dim === "string") ? "file '$imagefile' not found" : null;
			}
		}
	}

	/**
	 * Get the width of this image.
	 * @return int
	 */
	function getWidth() {
		return $this->getDimensions(0);
	}
	
	/**
	 * Get the height of this image.
	 * @return int
	 */
	function getHeight() {
		return $this->getDimensions(1);
	}
	
	/**
	 * Get the orientation of this image.
	 * @return ORIENTATION_SQUARE | ORIENTATION_PORTRAIT | ORIENTATION_LANDSCAPE
	 */
	function getOrientation() {
		$width = $this->getWidth();
		$height = $this->getHeight();
		if($width > $height) {
			return self::ORIENTATION_LANDSCAPE;
		} elseif($height > $width) {
			return self::ORIENTATION_PORTRAIT;
		} else {
			return self::ORIENTATION_SQUARE;
		}
	}
	
	protected function onBeforeDelete() { 
		parent::onBeforeDelete(); 

		$this->deleteFormattedImages();
	}

	public function getSizedTag($width = null, $height = null) {
		if (is_null($width) && is_null($height)){
			$image = $this;
		}
		else {
			if (is_null($width)) {
				$width = $this->getWidth();
			}
			if (is_null($height)) {
				$height = $this->getHeight();
			}
			$image = $this->SetRatioSize($width, $height);
		}
		$fileName = Director::baseFolder() . '/' . $image->Filename;
		if(file_exists($fileName)) {
			$url = $image->getURL();
			if($image->Title) {
				$title = Convert::raw2att($image->Title);
			} else {
				$title = $image->Filename;
				if(preg_match("/([^\/]*)\.[a-zA-Z0-9]{1,6}$/", $title, $matches)) $title = Convert::raw2att($matches[1]);
			}
			$size = getimagesize($fileName);
			return '<img src="'.$url.'" width="'.$size[0].'" height="'.$size[1].'" alt="'.$title.'" />';
		}
	}

}

/**
 * A resized / processed {@link Image} object.
 * When Image object are processed or resized, a suitable Image_Cached object is returned, pointing to the
 * cached copy of the processed image.
 * @package sapphire
 * @subpackage filesystem
 */
class Image_Cached extends Image {
	/**
	 * Create a new cached image.
	 * @param string $filename The filename of the image.
	 * @param boolean $isSingleton This this to true if this is a singleton() object, a stub for calling methods.  Singletons
	 * don't have their defaults set.
	 */
	public function __construct($filename = null, $isSingleton = false) {
		parent::__construct(array(), $isSingleton);
		$this->Filename = $filename;
	}
	
	public function getRelativePath() {
		return $this->getField('Filename');
	}
	
	// Prevent this from doing anything
	public function requireTable() {
		
	}
	
	public function debug() {
		return "Image_Cached object for $this->Filename";
	}
}

