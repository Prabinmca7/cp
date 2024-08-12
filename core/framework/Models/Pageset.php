<?php

namespace RightNow\Models;

use RightNow\Libraries\PageSetMapping,
    RightNow\Utils\Framework,
    RightNow\Api;

/**
 * This model manipulates the page set mapping files and database entries. This model isn't expected to be used by customers.
 *
 * @codingStandardsIgnoreStart
 * @internal
 * @codingStandardsIgnoreEnd
 */
final class Pageset extends Base
{
    private $cacheKey = 'getPageSetMappingArrays';
    private $pageSetMappingFilePath = 'config/pageSetMapping.php';

    /**
     * Gets all page set mappings.
     * @param string $basePath A relative path to config/pageSetMapping.php. If null, APPPATH is used.
     * @return array
     */
    public function getPageSetMappingArrays($basePath = null)
    {
        if ($basePath === null && (($cachedResult = Framework::checkCache($this->cacheKey)) !== null))
        {
            return $cachedResult;
        }

        if (IS_OPTIMIZED || $basePath !== null)
            return $this->getCommittedMappings($basePath);
        else
            return $this->getUncommittedMappings();
    }

    /**
     * Gets all enabled page set mappings.
     * @return array
     */
    public function getEnabledPageSetMappingArrays()
    {
        $enabledPageSetMappings = array();
        $pageSetMappings = $this->getPageSetMappingArrays();
        foreach ($pageSetMappings as $type => $mappings)
        {
            foreach ($mappings as $mapping)
            {
                if ($mapping->enabled === true)
                    $enabledPageSetMappings[$mapping->value] = $mapping;
            }
        }
        return $enabledPageSetMappings;
    }

    /**
     * Returns an array of page set mappings comparing development entries from the database
     * and entries from the path specified in $targetBasePath. This is currently used when
     * copying configurations to a staging environment.
     * @param string $targetBasePath A relative path to config/pageSetMapping.php. If null, APPPATH is used.
     * @return array
     */
    public function getPageSetMappingComparedArray($targetBasePath = null)
    {
        $sourcePageSets = $this->mergePageSetArrays($this->getUncommittedMappings());
        $targetPageSets = $this->mergePageSetArrays($this->getCommittedMappings($targetBasePath));
        return $this->mergeComparedPageSetArrays($sourcePageSets, $targetPageSets);
    }

    /**
     * Gets the content to write to the deployed production file.
     * @param array|null $knownMappings Array of page set mappings to deploy
     * @param bool $useUnlockedValues True if you want all enabled values. False if you only want enabled and locked values
     * @return string
     */
    public function getDeployedContent($knownMappings = null, $useUnlockedValues = true)
    {
        $mappings = $knownMappings ?: $this->getPageSetMappingArrays();

        foreach (array('custom', 'standard') as $type)
        {
            foreach ($mappings[$type] as $key => $value)
            {
                if (!$value->enabled || (!$useUnlockedValues && !$value->locked))
                {
                    unset($mappings[$type][$key]);
                }
            }
        }
        return $this->generateContent($mappings['custom'], $mappings['standard']);
    }

    /**
     * Given an array of page set id's indicating what page sets to either copy to staging or remove from staging,
     * build a new pageSetMappingArray to send to getDeployedContent().
     * '$changes' is an array having the page set id as key and a value of either 1 (Copy to staging) or 2 (Remove from staging).
     * These actions are defined in controllers/Staging.php::StagingControls.
     *
     * @param array $changes List of page set changes to make
     * @param string $targetBasePath A relative path to config/pageSetMapping.php. If null, APPPATH is used.
     * @param array|null $comparedArray An array of standard and custom page sets. If null, get mappings from Pageset->getPageSetMappingArrays().
     * @return array
     * @throws \Exception If action provided in $changes array is invalid
     */
    public function getPageSetMappingFromComparedArray(array $changes, $targetBasePath = null, $comparedArray = null)
    {
        require_once CPCORE . 'Internal/Libraries/Staging.php';
        $comparedArray = $comparedArray ?: $this->getPageSetMappingComparedArray($targetBasePath);
        $newMappings = $this->getInitialPageSetArray();
        $validActions = array_merge(array(null), array_keys(\RightNow\Internal\Libraries\StagingControls::getOptions())); // Create array with null, 0, 1, and 2
        foreach ($comparedArray as $id => $data)
        {
            $newMapping = null;
            $pageSetType = $this->getPageSetTypeFromID($id);
            $action = $changes[$id];
            if (!in_array($action, $validActions)) {
                throw new \Exception("Invalid changes action: $action");
            }
            if ($data['exists'][1] === true && ($action === null || $action === 0)) {
                $newMapping = 1;
            }
            else if ($data['exists'][0] === true && $action === 1) {
                $newMapping = 0;
            }
            if ($newMapping !== null) {
                $newMappings[$pageSetType][$id] = new PageSetMapping(array(
                    'id'            => $id,
                    'item'          => $data['item'][$newMapping],
                    'description'   => $data['description'][$newMapping],
                    'value'         => $data['value'][$newMapping],
                    'locked'        => $data['locked'][$newMapping],
                ));
            }
        }
        return $newMappings;
    }

    /**
     * Gets a merged array of rnt and custom page set mapping values.
     * @return array
     */
    public function getPageSetMappingMergedArray()
    {
        return $this->mergePageSetArrays($this->getPageSetMappingArrays());
    }

    /**
     * Gets the standard mappings array for the reference implementation.
     * No custom content is included, all values are enabled.
     * @return array
     */
    public function getPageSetDefaultArray()
    {
        $copyArray = array();
        $data = $this->getPageSetMappingArrays();
        return $data['standard'];
    }

    /**
     * Gets the unique folder values as an array. This is used in the admin section.
     * @return array
     */
    public function getPageSetMappingUniqueValues()
    {
        $returnData = array();
        foreach ($this->getPageSetMappingMergedArray() as $mapping)
        {
            if ($mapping->enabled)
                $returnData[$mapping->value] = $mapping->value;
        }
        return array_unique($returnData);
    }

    /**
     * Gets the unique folder values of non-custom mappings. This is used in the admin section.
     * @return array
     */
    public function getPageSetDefaultMappingUniqueValues()
    {
        $returnData = array();
        foreach ($this->getPageSetDefaultArray() as $mapping)
        {
            $returnData[$mapping->value] = $mapping->value;
        }
        return array_unique($returnData);
    }

    /**
     * Delete an item. This should only be done if the item is not locked and not a standard item.
     * @param int $mappingID ID of the mapping
     * @return boolean True if the operation was a success, False if the mapping is already locked
     * or is a standard item
     */
    public function deleteItem($mappingID)
    {
        if ($this->pageSetIdIsCustom($mappingID) &&
            Api::cp_ua_mapping_destroy(array('page_set_id' => intval($mappingID)))) {
            $allMappings = $this->getPageSetMappingArrays();
            $standardMappings = $allMappings['standard'];
            $customMappings = $allMappings['custom'];

            if (isset($standardMappings[$mappingID]) && $standardMappings[$mappingID]) {
                return false;
            }
            else if ($customMappings[$mappingID] && $customMappings[$mappingID]->locked) {
                return false;
            }
            unset($customMappings[$mappingID]);
            $this->setMappingCache($standardMappings, $customMappings);
            return true;
        }
        return false;
    }

    /**
     * Enables / disables a page set mapping.
     * @param int $mappingID The id to disable
     * @param bool $enable Whether to enable or disable the mapping
     * @return boolean Whether the operation was a success or failure
     */
    public function enableItem($mappingID, $enable)
    {
        $mappingID = intval($mappingID);
        $allMappings = $this->getPageSetMappingArrays();
        $mappings = $allMappings[$this->getPageSetTypeFromID($mappingID)];
        if (($mapping = $mappings[$mappingID]) &&
            $result = Api::cp_ua_mapping_update(array(
                'page_set_id' => $mappingID,
                'attr' => $this->getAttrValue($enable, $mapping->locked)
        ))) {
            $mappings[$mappingID] = new PageSetMapping(array(
                'id'            => $mapping->id,
                'item'          => $mapping->item,
                'description'   => $mapping->description,
                'value'         => $mapping->value,
                'enabled'       => $enable,
                'locked'        => $mapping->locked,
            ));
            if ($this->pageSetIdIsCustom($mappingID))
                $this->setMappingCache($allMappings['standard'], $mappings);
            else
                $this->setMappingCache($mappings, $allMappings['custom']);
            return true;
        }
        return false;
    }

    /**
     * Updates or creates a custom PageSetMapping.
     * @param array $mappings An array of ids and item/values to update
     * @return mixed PageSetMapping if successful, false otherwise
     */
    public function updateItem(array $mappings) {
        $returnVal = false;
        $allMappings = $this->getPageSetMappingArrays();
        $customMappings = $allMappings['custom'];
        foreach ($mappings as $key => $value) {
            if ($customMappings[$key] !== null && $this->pageSetIdIsCustom($key)) {
                if ((($customMappings[$key]->item !== $value['item']) ||
                    ($customMappings[$key]->description !== $value['description']) ||
                    ($customMappings[$key]->value !== $value['value'])) &&
                    Api::cp_ua_mapping_update(array(
                        'page_set_id' => $key,
                        'ua_regex' => $value['item'],
                        'description' => $value['description'] ?: $customMappings[$key]->description,
                        'page_set' => $value['value'] ?: $customMappings[$key]->value,
                    ))) {
                        $returnVal = $customMappings[$key] = new PageSetMapping(array(
                            'id'            => $key,
                            'item'          => $value['item'],
                            'description'   => $value['description'],
                            'value'         => $value['value'],
                            'enabled'       => $customMappings[$key]->enabled,
                            'locked'        => $customMappings[$key]->locked,
                        ));
                }
            }
            else {
                return $this->addItem($value);
            }
        }
        if ($returnVal) {
            $this->setMappingCache($allMappings['standard'], $customMappings);
        }
        return $returnVal;
    }

    /**
     * Creates a new custom PageSetMapping.
     * @param array $map Contains item, description, value keys and associated data
     * @return mixed PageSetMapping if successful otherwise Boolean false
     */
    public function addItem(array $map)
    {
        if(isset($map['item'], $map['description'], $map['value']))
        {
            $enabled = true;
            $locked = false;
            if($newID = Api::cp_ua_mapping_create(array(
                'ua_regex'      => $map['item'],
                'description'   => $map['description'],
                'page_set'      => $map['value'],
                'attr'          => $this->getAttrValue($enabled, $locked)
            ))) {
                $newMapping = new PageSetMapping($map + array(
                    'id' => $newID,
                    'enabled' => $enabled,
                    'locked' => $locked
                ));
                $allMappings = $this->getPageSetMappingArrays();
                $customMappings = $allMappings['custom'];
                $customMappings[$newID] = $newMapping;
                $this->setMappingCache($allMappings['standard'], $customMappings);
                return $newMapping;
            }
        }
        return false;
    }

    /**
     * Locks all custom page set mappings. This is done at deploy time.
     * @param string|null $basePath A relative path to config/pageSetMapping.php. If null, APPPATH is used.
     * @return void
     */
    public function lockPageSetMappings($basePath = null)
    {
        $mappings = $this->getPageSetMappingArrays($basePath);
        foreach ($mappings['custom'] as $mapping)
        {
            $attrOld = $this->getAttrValue($mapping->enabled, $mapping->locked);
            $attrNew = $this->getAttrValue($mapping->enabled, 1);
            if ($attrNew !== $attrOld)
            {
                Api::cp_ua_mapping_update(array(
                    'page_set_id' => $mapping->id,
                    'attr' => $attrNew,
                ));
            }
        }
    }

    /**
     * Returns the path to the editable user agent file. This is used by the deployer.
     * @return string The path to the editable user agent file.
     */
    public function getPageSetFilePath()
    {
        return $this->pageSetMappingFilePath;
    }

    /**
     * Finds the correct Facebook page set ID for the current interface.
     * @return int The ID of the Facebook page set
     */
    public function getFacebookPageSetID()
    {
        return \RightNow\Internal\Sql\Pageset::getFacebookPageSetID();
    }

    /**
     * Determines if a PageSetMapping value is valid or not. Valid values may be either
     * directories under views/pages or fully-qualified URLs beginning with either http or https.
     * @param string $value The path or fully-qualified URL to validate
     * @return bool
     */
    public function isValueValid($value)
    {
        // Directory traversal
        // Each one of those translate to 1. No dot at the beginning
        // 2. No dot at the end 3. No consecutive dots
        return (preg_match("/^(?!\.)(?!.*\.$)(?!.*?\.\.)[a-zA-Z0-9:\/_.]+$/", $value)
                && (\RightNow\Utils\Text::beginsWithCaseInsensitive(parse_url($value, PHP_URL_SCHEME), 'http')
                || \RightNow\Utils\FileSystem::isReadableDirectory(CUSTOMER_FILES . "views/pages/$value")));
    }

    /**
     * Return an array of unique page set items from source and target arrays.
     * @param array $sourceArray Source
     * @param array $targetArray Destination
     * @return array Sorted (by key) array containing merged items
     */
    private function mergeComparedPageSetArrays(array $sourceArray, array $targetArray)
    {
        $mergedArray = array();
        foreach($sourceArray as $pageSet)
        {
            $id = $pageSet->id;
            $mergedArray[$id] = array('exists' => array(true, false));
            foreach($pageSet->toArray() as $key => $value)
            {
                $mergedArray[$id][$key] = array($value, null);
            }
        }
        foreach($targetArray as $pageSet)
        {
            $id = $pageSet->id;
            if (array_key_exists($id, $mergedArray))
            {
                $existsInSource = true;
            }
            else
            {
                $existsInSource = false;
                $mergedArray[$id] = array();
            }

            $mergedArray[$id]['exists'] = array($existsInSource, true);

            foreach($pageSet->toArray() as $key => $value)
            {
                $sourceValue = ($existsInSource && array_key_exists($key, $mergedArray[$id])) ? $mergedArray[$id][$key][0] : null;
                $mergedArray[$id][$key] = array($sourceValue, $value);
            }
        }
        ksort($mergedArray);
        return $mergedArray;
    }

    /**
     * Gets the page sets from the production or staging file.
     * @param string $basePath A relative path to config/pageSetMapping.php. If null, APPPATH is used.
     * @return array
     */
    private function getCommittedMappings($basePath = null)
    {
        $filePath = $this->pageSetMappingFilePath;
        $fullPath = ($basePath === null) ? APPPATH . $filePath : "{$basePath}/{$filePath}";
        // If pageSetMapping.php does not exist, that indicates it is the same as development
        // (IE. the values in the database) so we return the initial, empty arrays.
        if (\RightNow\Utils\FileSystem::isReadableFile($fullPath)) {
            require_once $fullPath;
        }
        $data = $this->getInitialPageSetArray();
        if (function_exists('getRNPageSetMapping'))
            $data['standard'] = getRNPageSetMapping();
        if (function_exists('getPageSetMapping'))
            $data['custom'] = getPageSetMapping();

        Framework::setCache($this->cacheKey, $data, true);
        return $data;
    }

    /**
     * Returns the initial array for page sets
     * @return array Default page set array
     */
    private function getInitialPageSetArray()
    {
        return array('standard' => array(), 'custom' => array());
    }

    /**
     * Gets the page sets from the database.
     * This is for development and reference implementation modes.
     * @return array Contains PageSetMapping objects keyed by type
     */
    private function getUncommittedMappings() {
        $pageSets = \RightNow\Internal\Sql\Pageset::get();
        $data = $this->getInitialPageSetArray();
        foreach ($pageSets as $set) {
            $pageSetID = $set['page_set_id'];
            $data[$this->getPageSetTypeFromID($pageSetID)][$pageSetID] = new PageSetMapping(array(
                'id'            => $pageSetID,
                'item'          => $set['ua_regex'],
                'description'   => $set['description'],
                'value'         => $set['page_set'],
                'enabled'       => (bool) ($set['attr'] & UA_ATTR_ENABLED),
                'locked'        => (bool) ($set['attr'] & UA_ATTR_LOCKED),
            ));
        }
        $this->setMappingCache($data['standard'], $data['custom']);
        return $data;
    }

    /**
     * Returns the type of mapping (custom or standard)
     * @param int $pageSetID ID of the page set
     * @return string 'standard' or 'custom'
     * @throws \Exception If $pageSetID is not an integer
     */
    private function getPageSetTypeFromID($pageSetID)
    {
        if (!is_int($pageSetID)) {
            throw new \Exception('pageSetID not an integer');
        }
        return ($pageSetID >= CP_FIRST_CUSTOM_PAGESET_ID) ? 'custom' : 'standard';
    }

    /**
     * Determines whether the page set is custom or not
     * @param int $pageSetID ID of the page set
     * @return boolean
     */
    private function pageSetIdIsCustom($pageSetID) {
        return $this->getPageSetTypeFromID($pageSetID) === 'custom';
    }

    /**
     * Merges an array containing arrays into a single array with those arrays' contents.
     * @param array $arrays Array to merge
     * @return array
     */
    private function mergePageSetArrays(array $arrays) {
        return array_merge($arrays['custom'], $arrays['standard']);
    }

    /**
     * Generates the content for the PageSetMapping file. Also used by the deployer
     * via #getDeployedContent.
     * @param array $customMappings The custom mapping array to write
     * @param array $standardMappings The standard mapping array to write
     * @return string
     */
    private function generateContent(array $customMappings, array $standardMappings)
    {
        return $this->getHeader() .
               $this->getCustomFunction() .
               $this->getMappingArrayString($customMappings) .
               $this->getFooter() .
               $this->getStandardFunction() .
               $this->getMappingArrayString($standardMappings) .
               $this->getFooter();
    }

    /**
     * Generates the header content for the PageSetMapping file.
     * @return string
     */
    private function getHeader()
    {
        return "<?\n/****************************\n" .
               "**\n** Edits to this file should be done through the Page Set Mapping interface\n" .
               "**\n** *****************************/\n";
    }

    /**
     * Gets the custom function string.
     * @return string
     */
    private function getCustomFunction()
    {
        return "function getPageSetMapping() {\n" .
               "return array(\n";
    }

    /**
     * Makes the array string for the function. Assumption is that the string is only used for production so only enabled items are returned.
     * @param array|null $mappings List of mappings
     * @return string
     */
    private function getMappingArrayString($mappings)
    {
        if (is_array($mappings))
        {
            sort($mappings);
            $getMappingString = function($mapping) {
                return (string) $mapping;
            };
            $getMappingEnabledFlag = function($mapping) {
                return $mapping->enabled;
            };
            return implode(",\n", array_map($getMappingString, array_filter($mappings, $getMappingEnabledFlag)));
        }
    }

    /**
     * Gets the standard function string
     * @return string
     */
    private function getStandardFunction()
    {
        return "function getRNPageSetMapping() {\n" .
               "return array(\n";
    }

    /**
     * Generates the footer content for the PageSetMapping file
     * @return string
     */
    private function getFooter()
    {
        return ");\n}\n";
    }

    /**
     * Sets the cache.
     * @param array $standardMappings Standard mappings
     * @param array $customMappings Custom mappings
     * @return void
     */
    private function setMappingCache(array $standardMappings, array $customMappings)
    {
        Framework::setCache($this->cacheKey, array('standard' => $standardMappings, 'custom' => $customMappings), true);
    }

    /**
     * Gets the value to put into the 'attr' column in the db.
     * @param bool $enabled Whether the page set is enabled
     * @param bool $locked Whether hte page set is locked
     * @return int
     */
    private function getAttrValue($enabled, $locked)
    {
        $attrValue = 0x0000;
        if ($enabled)
            $attrValue |= UA_ATTR_ENABLED;
        if ($locked)
            $attrValue |= UA_ATTR_LOCKED;
        return $attrValue;
    }
}
