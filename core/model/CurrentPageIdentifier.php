<?php
/**
 * This interface lets us set up objects that will tell us what the current page is.
 * @package sapphire
 * @subpackage model
 */
interface CurrentPageIdentifier {
	/**
	 * Get the current page ID.
	 * @return SS_Int
	 */
	function currentPageID();
	
	/**
	 * Check if the given DataObject is the current page.
	 * @param DataObject $page The page to check.
	 * @return SS_Boolean
	 */
	function isCurrentPage(DataObject $page);
}

?>