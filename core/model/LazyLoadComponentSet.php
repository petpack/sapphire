<?php
/**
 * This is a special kind of DataObjectSet used to represent the items linked to in a 1-many or many-many
 * join.  It provides add and remove methods that will update the database.
 * @package sapphire
 * @subpackage model
 */
class LazyLoadComponentSet extends ComponentSet {

	protected $query;
	
	protected $iterator = null;
	
	function __construct($query = null) {
		parent::__construct();
		$this->query = $query;
	}
	
	/**
	 * Returns an Iterator for this DataObjectSet.
	 * This function allows you to use DataObjectSets in foreach loops
	 * @return LazyLoadComponentSet_Iterator
	 */
	public function getIterator() {
		if( is_null($this->iterator) ) {
			$this->iterator = new LazyLoadComponentSet_Iterator($this, $this->query);
		}
		return $this->iterator;
	}
	
	/**
	 * @return LazyLoadComponentSet_Iterator
	 * @author Alex Hayes <alex.hayes@dimension27.com>
	 */
	public function executeIteratorQuery() {
		$iterator = $this->getIterator();
		if( !$iterator->hasExecuted() ) {
			$iterator->executeQuery();
			$this->items = $iterator->items;
		}
		return $iterator;
	}

	/**
	 * Returns false if the set is empty.
	 * @return boolean
	 */
	public function exists() {
		return (bool) $this->Count();
	}

	/**
	 * Returns the actual number of items in this dataset.
	 * @return int
	 */
	public function Count() {
		$iterator = $this->executeIteratorQuery();
		return parent::Count();
	}
	
	/**
	 * Return the first item in the set.
	 * @return DataObject
	 */
	public function First() {
		$iterator = $this->executeIteratorQuery();
		return parent::First();
	}
	
	/**
	 * Return the last item in the set.
	 * @return DataObject
	 */
	public function Last() {
		$iterator = $this->executeIteratorQuery();
		return parent::Last();
	}

	/**
	 * Return the total number of items in this dataset.
	 * @return int
	 */
	public function TotalItems() {
		$iterator = $this->executeIteratorQuery();
		return parent::TotalItems();
	}

	/**
	 * Returns this set as a XHTML unordered list.
	 * @return string
	 */
	public function UL() {
		$iterator = $this->executeIteratorQuery();
		return parent::UL();
	}
	
	/**
	 * Returns this set as a XHTML unordered list.
	 * @return string
	 */
	public function forTemplate() {
		$iterator = $this->executeIteratorQuery();
		return parent::forTemplate();
	}

	/**
	 * Returns an array of ID => Title for the items in this set.
	 * 
	 * @param string $index The field to use as a key for the array
	 * @param string $titleField The field (or method) to get values for the map
	 * @param string $emptyString Empty option text e.g "(Select one)"
	 * @param bool $sort Sort the map alphabetically based on the $titleField value
	 * @return array
	 */
	public function map($index = 'ID', $titleField = 'Title', $emptyString = null, $sort = false) {
		$iterator = $this->executeIteratorQuery();
		return parent::map($index, $titleField, $emptyString, $sort);
	}
	
	/**
	 * Find an item in this list where the field $key is equal to $value
	 * Eg: $doSet->find('ID', 4);
	 * @return ViewableData The first matching item.
	 */
	public function find($key, $value) {
		$iterator = $this->executeIteratorQuery();
		return parent::find($key, $value);
	}
	
	/**
	 * Return a column of the given field
	 * @param string $value The field name
	 * @return array
	 */
	public function column($value = "ID") {
		$iterator = $this->executeIteratorQuery();
		return parent::column($value);
	}

	/**
	 * Returns an array of DataObjectSets. The array is keyed by index.
	 * 
	 * @param string $index The field name to index the array by.
	 * @return array
	 */
	public function groupBy($index) {
		$iterator = $this->executeIteratorQuery();
		return parent::groupBy($index);
	}

	/**
	 * Groups the items by a given field.
	 * Returns a DataObjectSet suitable for use in a nested template.
	 * @param string $index The field to group by
	 * @param string $childControl The name of the nested page control
	 * @return DataObjectSet
	 */
	public function GroupedBy($index, $childControl = "Children") {
		$iterator = $this->executeIteratorQuery();
		return parent::GroupedBy($index, $childControl);
	}

	/**
	 * Returns a nested unordered list out of a "chain" of DataObject-relations,
	 * using the automagic ComponentSet-relation-methods to find subsequent DataObjectSets.
	 * The formatting of the list can be different for each level, and is evaluated as an SS-template
	 * with access to the current DataObjects attributes and methods.
	 *
	 * Example: Groups (Level 0, the "calling" DataObjectSet, needs to be queried externally)
	 * and their Members (Level 1, determined by the Group->Members()-relation).
	 * 
	 * @param array $nestingLevels
	 * Defines relation-methods on DataObjects as a string, plus custom
	 * SS-template-code for the list-output. Use "Root" for the current DataObjectSet (is will not evaluate into
	 * a function).
	 * Caution: Don't close the list-elements (determined programatically).
	 * You need to escape dollar-signs that need to be evaluated as SS-template-code.
	 * Use $EvenOdd to get appropriate classes for CSS-styling.
	 * Format:
	 * array(
	 * 	array(
	 * 		"dataclass" => "Root",
	 * 		"template" => "<li class=\"\$EvenOdd\"><a href=\"admin/crm/show/\$ID\">\$AccountName</a>"
	 * 	),
	 * 	array(
	 * 		"dataclass" => "GrantObjects",
	 * 		"template" => "<li class=\"\$EvenOdd\"><a href=\"admin/crm/showgrant/\$ID\">#\$GrantNumber: \$TotalAmount.Nice, \$ApplicationDate.ShortMonth \$ApplicationDate.Year</a>"
	 * 	)
	 * );
	 * @param string $ulExtraAttributes Extra attributes
	 * 
	 * @return string Unordered List (HTML)
	 */
	public function buildNestedUL($nestingLevels, $ulExtraAttributes = "") {
		$iterator = $this->executeIteratorQuery();
		return parent::buildNestedUL($nestingLevels, $ulExtraAttributes);
	}

	/**
	 * Gets called recursively on the child-objects of the chain.
	 * 
	 * @param array $nestingLevels see {@buildNestedUL}
	 * @param int $level Current nesting level
	 * @param string $template Template for list item
	 * @param string $ulExtraAttributes Extra attributes
	 * @return string
	 */
	public function getChildrenAsUL($nestingLevels, $level = 0, $template = "<li id=\"record-\$ID\" class=\"\$EvenOdd\">\$Title", $ulExtraAttributes = null, &$itemCount = 0) {
		$iterator = $this->executeIteratorQuery();
		return parent::getChildrenAsUL($nestingLevels, $level, $template, $ulExtraAttributes, &$itemCount);
	}

	/**
	* Sorts the current DataObjectSet instance.
	* @param string $fieldname The name of the field on the DataObject that you wish to sort the set by.
	* @param string $direction Direction to sort by, either "ASC" or "DESC".
	*/
	public function sort($fieldname, $direction = "ASC") {
		$iterator = $this->executeIteratorQuery();
		return parent::sort($fieldname, $direction);
	}

	/**
	* Remove duplicates from this set based on the dataobjects field.
	* Assumes all items contained in the set all have that field.
	* Useful after merging to sets via {@link merge()}.
	* 
	* @param string $field the field to check for duplicates
	*/
	public function removeDuplicates($field = 'ID') {
		$iterator = $this->executeIteratorQuery();
		return parent::removeDuplicates($field);
	}
	
	/**
	 * Returns information about this set in HTML format for debugging.
	 * @return string
	 */
	public function debug() {
		$iterator = $this->executeIteratorQuery();
		return parent::debug();
	}

	/**
	 * Groups the set by $groupField and returns the parent of each group whose class
	 * is $groupClassName. If $collapse is true, the group will be collapsed up until an ancestor with the
	 * given class is found.
	 * @param string $groupField The field to group by.
	 * @param string $groupClassName Classname.
	 * @param string $sortParents SORT clause to insert into the parents SQL.
	 * @param string $parentField Parent field.
	 * @param boolean $collapse Collapse up until an ancestor with the given class is found.
	 * @param string $requiredParents Required parents
	 * @return DataObjectSet
	 */
	public function groupWithParents($groupField, $groupClassName, $sortParents = null, $parentField = 'ID', $collapse = false, $requiredParents = null) {
		$iterator = $this->executeIteratorQuery();
		return parent::groupWithParents($groupField, $groupClassName, $sortParents, $parentField, $collapse, $requiredParents);
	}
	
	/**
	 * Add a field to this set without writing it to the database
	 * @param DataObject $field Field to add
	 */
    function addWithoutWrite($field) {
		$iterator = $this->executeIteratorQuery();
		return parent::addWithoutWrite($field);
	}
	
	/**
	 * Returns true if the DataObjectSet contains all of the IDs givem
	 * @param $idList An array of object IDs
	 */
	function containsIDs($idList) {
		$iterator = $this->executeIteratorQuery();
		return parent::containsIDs($idList);
	}
	
	/**
	 * Returns true if the DataObjectSet contains all of and *only* the IDs given.
	 * Note that it won't like duplicates very much.
	 * @param $idList An array of object IDs
	 */
	function onlyContainsIDs($idList) {
		$iterator = $this->executeIteratorQuery();
		return parent::onlyContainsIDs($idList);
	}

	

	/**
	 * Get an array of all the IDs in this component set, where the keys are the same as the
	 * values.
	 * @return array
	 */
	function getIdList() {
		$iterator = $this->executeIteratorQuery();
		return parent::getIdList();
	}
		
	/**
	 * Add an item to this set.
	 * @param DataObject|int|string $item Item to add, either as a DataObject or as the ID.
	 * @param array $extraFields A map of extra fields to add.
	 * @param bool $loadExisting If set to true, then existing records will be loaded into memory and 
	 *                           this record placed at the end of the stack.
	 */
	function add($item, $extraFields = null) {
		return parent::add($item, $extraFields);
	}
	
	/**
	 * Method to save many-many join data into the database for the given $item.
	 * Used by add() and write().
	 * @param DataObject|string|int The item to save, as either a DataObject or the ID.
	 * @param array $extraFields Map of extra fields.
	 */
	protected function loadChildIntoDatabase($item, $extraFields = null) {
		return parent::loadChildIntoDatabase($item, $extraFields);
	}
    	
	/**
	 * Add a number of items to the component set.
	 * @param array $items Items to add, as either DataObjects or IDs.
	 * @param bool $loadExisting If set to true, then existing records will be loaded into memory and 
	 *                           these records placed at the end of the stack.
	 */
	function addMany($items) {
		return parent::addMany($items);
	}
	
	/**
	 * Sets the ComponentSet to be the given ID list.
	 * Records will be added and deleted as appropriate.
	 * @param array $idList List of IDs.
	 */
	function setByIDList($idList) {
		$iterator = $this->executeIteratorQuery();
		return parent::setByIDList($idList);
	}
	
	/**
	 * Remove an item from this set.
	 *
	 * @param DataObject|string|int $item Item to remove, either as a DataObject or as the ID.
	 */
	function remove($item) {
		return parent::remove($item);
	}
	
	/**
	 * Remove many items from this set.
	 * @param array $itemList The items to remove, as a numerical array with IDs or as a DataObjectSet
	 */
	function removeMany($itemList) {
		return parent::removeMany($itemList);
	}

	/**
	 * Remove all items in this set.
	 */
	function removeAll() {
		if(empty($this->tableName)) {
			// Load up items into memory, absolutely ridiculous that it has to be done this way...
			$this->executeIteratorQuery();
		}
		return parent::removeAll();
	}
	
	/**
	 * Write this set to the database.
	 * Called by DataObject::write().
	 * @param boolean $firstWrite This should be set to true if it the first time the set is being written.
	 */
	function write($firstWrite = false) {
		$iterator = $this->executeIteratorQuery();
		return parent::write($firstWrite);
	}
	
}

class LazyLoadComponentSet_Iterator extends DataObjectSet_Iterator {

	/**
	 * @var LazyLoadComponentSet
	 */
	protected $set;

	/**
	 * @var SQLQuery
	 */
	protected $query;
	
	protected $executed = false;
	
	/**
	 * @var array
	 */
	public $items = array();
	
	function __construct($set, $query) {
		$this->set = $set;
		$this->query = $query;
	}
	
	public function hasExecuted() {
		return $this->executed;
	}
	
	public function executeQuery() {
		if( $this->hasExecuted() ) {
			return true;
		}
		if( !is_object($this->query) ) {
			return false;
		}
//		var_dump('---- ' . __METHOD__ . ' ----');
		$records = $this->query->execute();
		$this->executed = true;
		if( $records ) {
			$this->set->parseQueryLimit($this->query); // for pagination support
		} else {
			$records = new ComponentSet();
		}

		$this->items = array();
		
		foreach($records as $record) {
			if(empty($record['RecordClassName'])) {
				$record['RecordClassName'] = $record['ClassName'];
			}
			if(class_exists($record['RecordClassName'])) {
				$this->items[] = new $record['RecordClassName']($record);
			} else {
				if(!$baseClass) {
					user_error("Bad RecordClassName '{$record['RecordClassName']}' and "
						. "\$baseClass not set", E_USER_ERROR);
				} else if(!is_string($baseClass) || !class_exists($baseClass)) {
					user_error("Bad RecordClassName '{$record['RecordClassName']}' and bad "
						. "\$baseClass '$baseClass not set", E_USER_ERROR);
				}
				$this->items[] = new $baseClass($record);
			}
		}

		$this->current = $this->prepareItem(current($this->items));
		
	}
	
	/**
	 * Prepare an item taken from the internal array for 
	 * output by this iterator.  Ensures that it is an object.
	 * @param DataObject $item Item to prepare
	 * @return DataObject
	 */
	protected function prepareItem($item) {
		if(is_object($item)) {
			$item->iteratorProperties(key($this->items), sizeof($this->items));
		}
		// This gives some reliablity but it patches over the root cause of the bug...
		// else if(key($this->items) !== null) $item = new ViewableData();
		return $item;
	}
	
	/**
	 * Return the current object of the iterator.
	 * @return DataObject
	 */
	public function current() {
		$this->executeQuery();
		return $this->current;
	}
	
	/**
	 * Return the key of the current object of the iterator.
	 * @return mixed
	 */
	public function key() {
		$this->executeQuery();
		return key($this->items);
	}
	
	/**
	 * Return the next item in this set.
	 * @return DataObject
	 */
	public function next() {
		$this->executeQuery();
		$this->current = $this->prepareItem(next($this->items));
		return $this->current;
	}
	
	/**
	 * Rewind the iterator to the beginning of the set.
	 * @return DataObject The first item in the set.
	 */
	public function rewind() {
		$this->executeQuery();
		$this->current = $this->prepareItem(reset($this->items));
		return $this->current;
	}
	
	/**
	 * Check the iterator is pointing to a valid item in the set.
	 * @return boolean
	 */
	public function valid() {
		$this->executeQuery();
	 	return $this->current !== false;
	}
	
	/**
	 * Return the next item in this set without progressing the iterator.
	 * @return DataObject
	 */
	public function peekNext() {
		return $this->getOffset(1);
	}
	
	/**
	 * Return the prvious item in this set, without affecting the iterator.
	 * @return DataObject
	 */
	public function peekPrev() {
		$this->executeQuery();
		return $this->getOffset(-1);
	}
	
	/**
	 * Return the object in this set offset by $offset from the iterator pointer.
	 * @param int $offset The offset.
	 * @return DataObject|boolean DataObject of offset item, or boolean FALSE if not found
	 */
	public function getOffset($offset) {
		$keys = array_keys($this->items);
		foreach($keys as $i => $key) {
			if($key == key($this->items)) break;
		}
		
		if(isset($keys[$i + $offset])) {
			$requiredKey = $keys[$i + $offset];
			return $this->items[$requiredKey];
		}
		
		return false;
	}

}
