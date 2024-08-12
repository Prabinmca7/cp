<?php

namespace RightNow\Models;
use RightNow\Api,
    RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Connect as ConnectUtil;

/**
 * Retrieves product and category data from the DB. Helps with dealing with product/category linking as well as
 * getting partial hierarchies of the data.
 */
class Prodcat extends Base {
    const MAX_LEVEL = 2;
    const EVERYONE_ROLE_SET = 1;
    const UNFILTERED_LIST_BY_PERMISSION_REPORT = 10163;
    const PRODUCT_LIST_BY_PERMISSION_REPORT = 10155;
    const CATEGORY_LIST_BY_PERMISSION_REPORT = 10156;
    const SSS_CP_PROD_LIST_BY_PERM_NO_LOG_IN = 10169;
    const SSS_CP_CAT_LIST_BY_PERM_NO_LOG_IN = 10170;
    const SSS_UNFLTR_PER_ACCESS_ENTITY_NO_LOG_IN = 10171;

    private static $defaultProductID;
    private $interfaceID;
    private $cacheKey = 'ProdcatModel';

    public function __construct() {
        parent::__construct();
        $this->interfaceID = Api::intf_id();
        $this->cacheKey .= $this->interfaceID;
    }

    /**
     * Return either a Product or Category connect object from specified $id.
     *
     * @param int $id A product or category ID.
     * @return Connect\ServiceProduct|Connect\ServiceCategory|null Either the product or category Connect object or null
     * if no result could be found for the provided ID.
     */
    public function get($id) {
        if (Framework::isValidID($id)) {
            try {
                return $this->getResponseObject(Connect\ServiceProduct::fetch($id));
            }
            catch(Connect\ConnectAPIErrorBase $e) {
                try {
                    return $this->getResponseObject(Connect\ServiceCategory::fetch($id));
                }
                catch(Connect\ConnectAPIErrorBase $e) {
                    // pass
                }
            }
        }
        return $this->getResponseObject(null, null, new \RightNow\Libraries\ResponseError(null, null, null, "Invalid ID: No such Service Product or Category with ID = '$id'"));
    }

    /**
     * Returns a list of valid products or categories associated to the current (possibly anonymous) user related to social question reading
     * @param bool $isProduct Whether the report to run should contain products or categories
     * @return bool|null|array True if every product or category is valid, null if none are, or a list of specific valid products and categories.
     * Each returned product or category is an array containing the ID, Label, and IDs of all six levels of the hierarchy (-1 is used if it's not applicable).
     */
    public function getPermissionedListSocialQuestionRead($isProduct = true) {
        return $this->getPermissionedList(PERM_SOCIALQUESTION_READ, $isProduct);
    }

    /**
     * Returns a list of valid products or categories associated to the current (possibly anonymous) user related to social question creation
     * @param bool $isProduct Whether the report to run should contain products or categories
     * @return bool|null|array True if every product or category is valid, null if none are, or a list of specific valid products and categories.
     * Each returned product or category is an array containing the ID, Label, and IDs of all six levels of the hierarchy (-1 is used if it's not applicable).
     */
    public function getPermissionedListSocialQuestionCreate($isProduct = true) {
        return $this->getPermissionedList(PERM_SOCIALQUESTION_CREATE, $isProduct);
    }

    /**
     * Returns a list of valid products or categories associated to the current (possibly anonymous) user for the given permission
     * @param int $permissionID Permission to check
     * @param bool $isProduct Whether the report to run should contain products or categories
     * @return bool|null|array True if every product or category is valid, null if none are, or a list of specific valid products and categories.
     * Each returned product or category is an array containing the ID, Label, and IDs of all six levels of the hierarchy (-1 is used if it's not applicable).
     */
    public function getPermissionedList($permissionID, $isProduct = true) {
        $reportModel = $this->CI->model('Report');
        $generateFilter = function($filterName, $value, $reportID) use ($reportModel, &$filters) {
            $filterDefinition = $reportModel->getFilterByName($reportID, $filterName)->result;
            $filter = $reportModel->createSearchFilter($reportID,
                $filterDefinition['name'], $filterDefinition['fltr_id'],
                $value)->result;
            $filters[$filterName] = $filter;
        };

        if ($loggedIn = Framework::isLoggedIn()) {
            $reportID = self::UNFILTERED_LIST_BY_PERMISSION_REPORT;
        }
        else {
            $reportID = self::SSS_UNFLTR_PER_ACCESS_ENTITY_NO_LOG_IN;
        }
        $reportToken = Framework::createToken($reportID);
        $filters = array();
        $generateFilter('permissions.permission_id', $permissionID, $reportID);
        if ($loggedIn) {
            // if logged in, pass in the user's contactID
            $generateFilter('contacts.c_id', $this->CI->session->getProfileData('contactID'), $reportID);
        }
        else {
            // if not logged in, get the list for the EVERYONE_ROLE_SET
            $generateFilter('role_sets.role_set_id', self::EVERYONE_ROLE_SET, $reportID);
        }
        $permissionList = $this->CI->model('Report')->getDataHTML($reportID, $reportToken, $filters, null)->result['data'];

        // if first element is 'All' (ID: -1), return true since user can access everything
        if ($permissionList && $permissionList[0][0] === -1)
            return $this->getResponseObject(true, 'is_bool');

        if ($loggedIn) {
            $reportID = $isProduct ? self::PRODUCT_LIST_BY_PERMISSION_REPORT : self::CATEGORY_LIST_BY_PERMISSION_REPORT;
        }
        else {
            $reportID = $isProduct ? self::SSS_CP_PROD_LIST_BY_PERM_NO_LOG_IN : self::SSS_CP_CAT_LIST_BY_PERM_NO_LOG_IN;
        }
        $reportToken = Framework::createToken($reportID);
        $filters = array();
        $generateFilter('permissions.permission_id', $permissionID, $reportID);
        if (Framework::isLoggedIn()) {
            // if logged in, pass in the user's contactID
            $generateFilter('contacts.c_id', $this->CI->session->getProfileData('contactID'), $reportID);
        }
        else {
            // if not logged in, get the list for the EVERYONE_ROLE_SET
            $generateFilter('role_sets.role_set_id', self::EVERYONE_ROLE_SET, $reportID);
        }
        $permissionList = $this->CI->model('Report')->getDataHTML($reportID, $reportToken, $filters, null)->result['data'];

        // if nothing is returned, return null
        if (!$permissionList)
            return $this->getResponseObject(null, null);

        $createElement = function($permissionElement) {
            return array(
                'ID'     => $permissionElement[0],
                'Label'  => $permissionElement[1],
                'Level1' => $permissionElement[2],
                'Level2' => $permissionElement[3],
                'Level3' => $permissionElement[4],
                'Level4' => $permissionElement[5],
                'Level5' => $permissionElement[6],
                'Level6' => $permissionElement[7],
            );
        };

        $permissionList = array_map($createElement, $permissionList);

        return $this->getResponseObject($permissionList, 'is_array');
    }

    /**
     * Returns an array of all hierarchy items and sub-items for the specified level.
     *
     * @param string $filterType      Products or categories
     * @param int    $level           Hierarchy level. A level of 1 will return a single level of products/categories.
     * @param int    $limit           Max number of top level product or categories to return.
     * @param array  $topLevelIDs     A list of top level product or category IDs
     * @param int    $descendantLimit Max number of descendants to return. This value is only used if $level is greater than 1
     * @return array Hierarchy items: (id, label, seq, parent, level, hierList, subItems)
     */
    public function getHierarchy($filterType, $level, $limit = 30, array $topLevelIDs = array(), $descendantLimit = 30) {
        //TODO: If MAX_LEVEL ever increases beyond 2, the logic below will need to handle the deeper levels...
        $level = intval($level);
        if (!in_array($level, range(0, self::MAX_LEVEL))) {
            //Note: This previously accepted any old value, and just used level 1 when specified level did
            //not equal 2. Not sure if we need to maintain that behavior?
            return $this->getResponseObject(null, null, 'Invalid level: Level needs to be between 0 and ' . self::MAX_LEVEL . " but received '$level'");
        }

        if ($limit > 30 || $limit < 1) {
            // Ensure $limit is within the acceptable bounds.  This is for performance concerns.
            return $this->getResponseObject(null, null, "Invalid limit: Limit needs to be between 1 and 30 but received '$limit'");
        }

        if ($level > 1 && ($descendantLimit > 30 || $descendantLimit < 1)) {
            // Ensure $descendantLimit is within the acceptable bounds.  This is for performance concerns.
            return $this->getResponseObject(null, null, "Invalid descendantLimit: descendantLimit needs to be between 1 and 30 but received '$descendantLimit'");
        }

        if (isset($root) && $root !== null && !Framework::isValidID($root)) {
            return $this->getResponseObject(null, null, "Invalid root ID");
        }

        if (!$connectName = $this->getFilter($filterType)) {
            return $this->getResponseObject(null, null, "Invalid filter type: '$filterType'");
        }

        $cacheKey = "{$this->cacheKey}{$connectName}{$level}{$limit}" . implode('_', $topLevelIDs) . 'Hierarchy';
        if (!$hierarchy = $this->getCached($cacheKey)) {
            try {
                $parents = $this->getTopLevelObjects($connectName, $limit, $topLevelIDs);
                $children = $level > 1 ? $this->getDirectDescendantsFromList($parents, $connectName, $descendantLimit) : array();
                $hierarchy = $this->combineParentsAndChildren($parents, $children, $level);
            }
            catch(Connect\ConnectAPIErrorBase $e) {
                return $this->getResponseObject(null, null, $e->getMessage());
            }
            $this->cache($cacheKey, $hierarchy);
        }

        return $this->getResponseObject($hierarchy, 'is_array');
    }

    /**
     * Return all the direct descendants of a specific ID. This function will be the recipient of many AJAX requests.
     * Typically used within the ProductCategoryInput and ProductCategorySearchFilters widgets.
     * @param string $filterType The value 'Product' or 'Category'
     * @param int|null $id A valid ID
     * @return array A flat list of all the direct children of the given ID. Each item in the array is an array with the following structure:
     *   ['id' => integer id, 'label' => string label, 'hasChildren' => boolean whether the item has children]
     */
    public function getDirectDescendants($filterType, $id = null)
    {
        if ($invalidRequest = $this->verifyRequest($filterType, $id)) {
            return $invalidRequest;
        }

        $connectName = $this->getFilter($filterType);
        $pluralName = $this->getPluralizedName($connectName);
        $cacheKey = "{$this->cacheKey}{$connectName}{$id}Descendants";

        $hierarchy = $this->getCached($cacheKey);
        if ($hierarchy === false || $hierarchy === null) {
            $hierarchy = array();
            try {
                $getObjects = function($resultSet) {
                    $objects = array();
                    while($object = $resultSet->next()) {
                        $objects[] = $object;
                    }
                    return $objects;
                };

                //Find all of the first level objects
                $firstLevelObjects = Connect\ROQL::query("SELECT ID, LookupName
                                                          FROM Service{$connectName}
                                                          WHERE Parent " . (($id) ? "= $id" : "IS NULL") . " AND EndUserVisibleInterfaces.ID = curInterface()
                                                          ORDER BY DisplayOrder")->next();
                $parents = $getObjects($firstLevelObjects);

                //Now find all of the next level objects
                if(!empty($parents)) {
                    $mapFunction = function($i){
                        return $i['ID'];
                    };
                    $childObjects = Connect\ROQL::query("SELECT Parent
                                                         FROM Service{$connectName}
                                                         WHERE Parent IN (" . implode(',', array_map($mapFunction, $parents)) . ")
                                                         AND EndUserVisibleInterfaces.ID = curInterface()")->next();

                    //Transform all of the unique parents into a hash for easy indexing
                    $children = $getObjects($childObjects);
                    $getParent = function($i){
                        return $i['Parent'];
                    };
                    $children = array_flip(array_unique(array_map($getParent, $children)));

                    foreach($parents as $parent) {
                        $objectID = (int) $parent['ID'];
                        $hierarchy[] = array(
                            'id' => $objectID,
                            'label' => $parent['LookupName'],
                            'hasChildren' => isset($children[$objectID])
                        );
                    }
                }
            }
            catch(Connect\ConnectAPIErrorBase $e) {
                return $this->getResponseObject(null, null, $e->getMessage());
            }
            $this->cache($cacheKey, $hierarchy);
        }

        return $this->getResponseObject($hierarchy, 'is_array');
    }

    /**
     * Return the chain to the root of a product or category.
     * Typically used by DisplaySearchFilters
     * @param string $filterType The string 'Product' or 'Category'
     * @param int $id A valid ID
     * @param boolean $asFlatArray An optional parameter indicating whether labels should be including in the results
     * @return array An array of parents to the root with labels attached.  [0] = Root -> [N] = $ID elem
     */
    public function getFormattedChain($filterType, $id, $asFlatArray = false)
    {
        if(!$connectName = $this->getFilter($filterType)) {
            return $this->getResponseObject(array(), 'is_array', "Invalid filter type: '$filterType'");
        }
        if(!Framework::isValidID($id)) {
            return $this->getResponseObject(array(), 'is_array', "Invalid ID: No such Service Product or Category with ID = '$id'");
        }
        $cacheKey = "{$this->cacheKey}{$connectName}{$id}-{$asFlatArray}FormattedChain";

        if (!$visibleHierarchy = $this->getCached($cacheKey)) {
            try {
                $hierarchy = $visibleHierarchy = array();
                if($connectName === 'Product')
                    $connectObject = Connect\ServiceProduct::fetch($id);
                else
                    $connectObject = Connect\ServiceCategory::fetch($id);
                $type = $connectName . 'Hierarchy';
                if(count($connectObject->$type)) {
                    foreach($connectObject->$type as $object) {
                        $hierarchy[] = array('id' => $object->ID, 'label' => $object->LookupName);
                    }
                }
                $hierarchy[] = array('id' => $connectObject->ID, 'label' => $connectObject->LookupName);

                //Only return the visible nodes and if necessary flatten the array
                foreach($hierarchy as $object) {
                    if(!$this->isEnduserVisible($object['id']))
                        break;
                    $visibleHierarchy[] = ($asFlatArray) ? $object['id'] : $object;
                }
            }
            catch(Connect\ConnectAPIErrorBase $e) {
                return $this->getResponseObject(array(), 'is_array', $e->getMessage());
            }
            $this->cache($cacheKey, $visibleHierarchy);
        }
        return $this->getResponseObject($visibleHierarchy, 'is_array');
    }

    /**
     * Handles the creation of a tree of end user visible products or categories given a chain
     * of selected items. The selected product or category (the one at the end of the chain) is marked in the
     * resultant tree so that it can be easily rendered on the client side. The tree follows the following
     * format:
     *
     *      array(0 =>
     *            array(
     *                 array('id' => <ID of a product or category at the root (0) level>,
     *                       'label' => <End User Label>,
     *                       'hasChildren' => <Flag indicating whether or not this node has children>,
     *                       'selected' => <Flag indicating whether or not this node is selected from the chain>)
     *                  ),
     *            4 =>
     *            array(
     *                 array('id' => <ID parented by Product or category 4>,
     *                       ...)
     *                  ),
     *           )
     *
     * The tree map will contain all of the data necessary to render the selected chain. If linking is enabled
     * and a valid product ID is supplied, the tree map will contain all of the linked categories of that product,
     * otherwise, it will return all visible categories.
     * @param string $dataType Either 'Product' or 'Category'
     * @param array $selectedChain The selected product chain. A valid chain of end user visible products or categories.
     * @param boolean $linkingOn Whether or not the returned tree should contain the linkedCategory data.
     * @param int $linkingProductID The ID of the product we are retrieving linked data for. Only used if linkingOn is true.
     * @param int $maxLevel The maximum allowable depth of the returned tree.
     * @return array The aforementioned array of arrays representing a tree.
     */
    public function getFormattedTree($dataType, array $selectedChain, $linkingOn = false, $linkingProductID = null, $maxLevel = 6) {
        //Validate all of the incoming parameters
        if(($error = $this->verifyRequest($dataType, $linkingProductID)) || ($error = $this->verifyMaxLevel($maxLevel))) {
            return $error;
        }

        $chain = $this->getEnduserVisibleHierarchy($selectedChain)->result;
        if(count($chain) !== count($selectedChain)) {
            $additionalWarning = "The provided chain '" . implode(',', $selectedChain) . "' contains a non-enduser visible category. Reverted to the following: '" . implode(',', $chain) . "'";
        }

        //Build up the tree necessary for rendering on the client
        $prodcatTree = array(array());
        if($linkingOn) {
            $prodcatTree = $this->getLinkedCategories($linkingProductID, $maxLevel)->result ?: array(array());
        }

        $chain = array_slice($chain, 0, $maxLevel);
        array_unshift($chain, 0); //Always include the root node
        while(($id = array_pop($chain)) !== null) {
            if(count($chain) < $maxLevel) {
                $descendants = ($linkingOn && $linkingProductID)
                                    ? $prodcatTree[$id]
                                    : $this->getDirectDescendants($dataType, $id ?: null)->result;

                if (is_array($descendants) && count($descendants)) {
                    foreach($descendants as &$child) {
                        if (!isset($lastID)) $lastID = 0;
                        
                        if($child['selected'] = ($child['id'] === (int)$lastID && (!isset($hasSelected) || !$hasSelected))) {
                            $hasSelected = true;
                        }
                        if(count($chain) + 1 === $maxLevel) {
                            $child['hasChildren'] = false;
                        }
                    }
                    $prodcatTree[$id] = $descendants;
                }
            }
            $lastID = $id;
        }

        ksort($prodcatTree);
        return $this->getResponseObject($prodcatTree, 'is_array', array(), isset($additionalWarning) && $additionalWarning ? $additionalWarning : array());
    }

    /**
     * Returns a flattened tree of all the categories linked to the sent in product ID. Categories
     * having non-enduser-visible parents will be omitted, along with that entire category chain.
     * If the product ID is null, returns all the top level categories. Typically used within the
     * ProductCategoryInput and ProductCategorySearchFilter widgets.
     * @param int $id Product ID
     * @param int $maxLevel The maximum depth of the returned categories
     * @return array Map of the linked categories keyed by category ID -> array with each sub-array containing all child categories of that category
     * Each sub-array has the following structure:
     *   ['id' => integer id, 'label' => string label, 'hasChildren' => boolean whether the item has children]
     */
    public function getLinkedCategories($id = null, $maxLevel = 6) {
        if (($error = $this->verifyRequest('Category', $id)) || ($error = $this->verifyMaxLevel($maxLevel))) {
            return $error;
        }

        $cacheKey = "{$this->cacheKey}LinkedCategoriesFor{$id}{$maxLevel}";
        if (!$categoryTree = $this->getCached($cacheKey)) {
            $categoryTree = array(0 => array());
            if($id) {
                if(!$categories = $this->getChildCategories($id, $maxLevel)) {
                    return $this->getResponseObject(array(), 'is_array', null, "Warning: No linked categories found with ID = '$id'");
                }
                $orderedCategories = Connect\ROQL::query("SELECT Parent as ParentID, ID, LookupName
                                                          FROM ServiceCategory
                                                          WHERE ID IN (" . implode(',', array_keys($categories)) . ")
                                                          ORDER BY ServiceCategory.DisplayOrder")->next();

                //Build up the category tree
                while($category = $orderedCategories->next()) {
                    $parentID = ((int) $category['ParentID']) ?: 0;
                    $categoryTree[$parentID] []= array(
                        'id'          => (int) $category['ID'],
                        'label'       => $category['LookupName'],
                        'hasChildren' => $categories[$category['ID']]
                    );
                }
            }
            //Otherwise, return the top level categories
            else {
                $result = $this->getDirectDescendants('Category');
                if(count($result->errors) > 0)
                    return $result;
                $categories = $result->result;

                if($maxLevel === 1) {
                    foreach($categories as $category) {
                        $category['hasChildren'] = false;
                    }
                }

                $categoryTree[0] = $categories;
            }
            $this->cache($cacheKey, $categoryTree);
        }
        return $this->getResponseObject($categoryTree, 'is_array');
    }


    /**
     * Function to return if prod/cat linking is turned on. Only checks
     * if the filter is of type product.
     *
     * @return bool True of linking is turned on, false otherwise
     */
    public function getLinkingMode() {
        return CFG_OPT_PROD_CAT_LINK & Api::sci_cache_int_get(SCI_OPTS);
    }

    /**
     * Returns true if product or category is enduser visible.
     *
     * @param Connect\ServiceProduct|Connect\ServiceCategory|int $objectOrID A product or category connect object, or ID.
     * @return bool|null Whether the product or category is enduser visible; null if an object wasn't found
     */
    public function isEnduserVisible($objectOrID) {
        //You might wonder why this isn't a ternary. Turns out for some reason if the DB has a ton of prod/cat
        //data, a ternary causes some weird ref count problems so now it's a good 'ol if/else
        $connectObject = null;
        if(ConnectUtil::getProductCategoryType($objectOrID)){
            $connectObject = $objectOrID;
        }
        else{
            $connectObject = $this->get($objectOrID)->result;
        }

        if ($connectObject) {
            $cacheKey = "{$this->cacheKey}{$connectObject->ID}Visible";
            if (($isVisible = Framework::checkCache($cacheKey)) === null) {
                $isVisible = false;
                if(ConnectUtil::isArray($connectObject->EndUserVisibleInterfaces)){
                    foreach ($connectObject->EndUserVisibleInterfaces as $interface) {
                        if ($interface->ID === $this->interfaceID) {
                            $isVisible = true;
                        }
                    }
                }
                Framework::setCache($cacheKey, $isVisible);
            }
        }
        return $isVisible;
    }

    /**
     * Returns the portion of a product or category's hierarchy chain which is visible to end users.
     * For example, if the chain is (1,2,3,4) with 1 and 2 being end user visible, but 3 not, then this
     * function will return (1,2), removing the remainder of the hierarchy after the first non-visible ID.
     *
     * @param array $hierarchy The hierarchy of product or category IDs to check
     * @return array The enduser visible hierarchy array.
     */
    public function getEnduserVisibleHierarchy(array $hierarchy) {
        $visible = array();
        foreach ($hierarchy as $depth => $id) {
            if (!Framework::isValidID($id)) {
                return $this->getResponseObject(array(), 'is_array', "Hierarchy contains an invalid id: '$id'");
            }
            if (!$this->isEnduserVisible($id) || ($depth + 1) > 6) {
                break;
            }
            $visible[] = $id;
        }
        return $this->getResponseObject($visible, 'is_array');
    }

    /**
    * Returns a (static) default Product ID
    * @return int The previously set product ID
    */
    public function getDefaultProductID() {
        return self::$defaultProductID;
    }

    /**
    * Sets a (static) default product ID
    * @param int $defaultID The product ID to be set
    * @return boolean True or false whether the product was correctly set
    */
    public function setDefaultProductID($defaultID) {
        $chain = $this->getFormattedChain('Product', $defaultID, true);
        if(count($chain->errors) > 0 || count($chain->warnings) > 0) {
            return $this->getResponseObject(false, 'is_bool', $chain->errors, $chain->warnings);
        }
        if(end($chain->result) != $defaultID) {
            return $this->getResponseObject(false, 'is_bool', "The provided ID is not enduser visible or is part of a chain which contains non-enduser visible products");
        }
        self::$defaultProductID = $defaultID;
        return $this->getResponseObject(true, 'is_bool');

    }

    /**
     * Return an array of hier_menu items of specific type in sorted order.
     *
     * @param int|string $filterType Can be a product or category ID, or string.
     * @param int $linkingValue Value of current product selected to get linking values
     *
     * @return array The sorted array of product or category entries
     */
    public function getHierPopup($filterType, $linkingValue = null) {
        if (!$connectName = $this->getFilter($filterType)) {
            return $this->getResponseObject(null, null, "Invalid filter type: '$filterType'");
        }

        $response = $this->getResponseObject(array(), 'is_array');
        if ($linkingValue) {
            if (!Framework::isValidID($linkingValue) || (!$categoryIDs = $this->getChildCategories($linkingValue))) {
                $response->warning = Config::getMessage(LINKING_VAL_LINKINGVALUE_0_LINKED_LBL);
                return $response;
            }
            $categoryIDs = array_keys($categoryIDs);
            sort($categoryIDs);
            $hierarchy = $this->getSortedItems('Category', $categoryIDs, 1); //TODO: does this need to limit to top level items?
            $values = $this->getSortedItems($connectName, array($linkingValue));
            $hierarchy['prod_chain'] = $values[0]['hier_list'];
        }
        else {
            $hierarchy = $this->getSortedItems($connectName);
        }

        $response->result = $hierarchy;
        return $response;
    }

    /**
     * This function converts the level.id format of hier menu information into the expected
     * hier menu chain that is used everywhere else.
     *
     * @param int $id A product or category id.
     * @param int $level The level of the hier menu item (1-6)
     * @param bool $ignoreVisibility If true, return chain regardless if $id is enduser visible.
     * @return array The product/category chain as an array.
     */
    public function getChain($id, $level, $ignoreVisibility = false) {
        if(stripos($id, "u") === 0) {
            // Apparently the views engine sends in $id prepended with a 'u',
            // in which case we need to decrement $level by 1.
            $id = substr($id, 1);
            $level--;
        }

        if (($response = $this->get($id)) && (!$connectObject = $response->result)) {
            return $response;
        }

        $response = new \RightNow\Libraries\ResponseObject('is_array');

        // Verify $level based on [prod|cat]_lvlX_id database columns.
        $minLevel = 1;
        $maxLevel = 6;
        if (!Framework::isValidID($level) || !in_array($level, range($minLevel, $maxLevel))) {
            $response->error = Config::getMessage(LEVEL_INTEGER_MINLEVEL_MAXLEVEL_MSG);
            return $response;
        }

        if (!$ignoreVisibility && !$this->isEnduserVisible($connectObject)) {
            $response->warning = Config::getMessage(RETURNING_EMPTY_ARRAY_ID_ENDUSER_MSG);
            $response->result = array();
            return $response;
        }

        $chain = $this->getChainFromObject($connectObject);
        if (count($chain) > $level) {
            $chain = array_slice($chain, 0, $level);
        }
        $response->result = array_pad($chain, $level, null);

        return $response;
    }

    /**
     * Returns the entire hierarchy of IDs up to the first level parent if the specified product
     * or category has at least one non-enduser-visible parent.
     * Returns an empty array if $objectOrID is not valid, or, all ancestors are enduser-visible.
     * The visibility of the $connectObject itself is not taken into account.
     *
     * @param Connect\ServiceProduct|Connect\ServiceCateory|int $objectOrID A product or category connect object, or ID.
     * @return array List of IDs up to the first level parent of the product/category
     */
    public function getNonEnduserVisibleChain($objectOrID) {
        $chain = $this->getChainFromObject(ConnectUtil::getProductCategoryType($objectOrID)
            ? $objectOrID
            : $this->get($objectOrID)->result);

        for($i = count($chain) - 2; $i >= 0; $i--) {
            if (!$this->isEnduserVisible($chain[$i])) {
                return $chain;
            }
        }
        return array();
    }

    /**
     * Return either a product/category name, or ID as specified by $format.
     *
     * @param string $filterType The type of filter, either prod*, cat* or a product or category id
     * @param string $format Return prod/cat name if 'name', else return prod/cat ID.
     * @return string|int|null One of 'Product', 'Category', '{product_id}', '{category_id}' or null.
     */
    protected function getFilter($filterType, $format = 'name') {
        if($filterType === HM_PRODUCTS || stristr($filterType, 'prod'))
            return ($format === 'name') ? 'Product' : HM_PRODUCTS;
        if($filterType === HM_CATEGORIES || stristr($filterType, 'cat'))
            return ($format === 'name') ? 'Category' : HM_CATEGORIES;
    }

    /**
     * Return the hierarchy chain as an array from specified connectObject.
     * @param Connect\ServiceProduct|Connect\ServiceCategory $connectObject Connect object from which to get chain
     * @return array Chain of product/category in an array
     */
    protected function getChainFromObject($connectObject) {
        $chain = array();
        $hierarchy = Text::getSubstringAfter(get_class($connectObject), 'Service') . 'Hierarchy';
        if ($connectObject->$hierarchy) {
            foreach ($connectObject->$hierarchy as $parent) {
                $chain[] = $parent->ID;
            }
        }
        $chain[] = $connectObject->ID;
        return $chain;
    }

    /**
     * Fetches all the child categories of a given product. If any of the categories have a non-enduser-visible
     * parent, the entire category chain will be omitted. If that product is a leaf node, the categories are obvious
     * (just CategoryLinks). If that product is however, a non-leaf node, the categories are all
     * linked categories of all child products of the original product.
     * @param int $id The ID of the product that you wish to fetch categories for.
     * @param int $maxLevel The maximum depth of the returned categories
     * @return array An array keyed by category ID with a value true to signify that it is a leaf node, or false, a parent node
     */
    protected function getChildCategories($id, $maxLevel = 6) {
        if(!intval($id))
            return array();

        //get the product levels
        $searchFields = $this->getProductLevels($id);
        if(count($searchFields) === 0)
            return array();

        $allCategories = array();
        foreach($searchFields as $searchField) {
            //Perform a query to get the chains (list of IDs) to every linked leaf node, then iterate through
            $categoryChains = Connect\ROQL::query("SELECT CategoryLinks.ServiceCategory.Parent.level1,
                                                          CategoryLinks.ServiceCategory.Parent.level2,
                                                          CategoryLinks.ServiceCategory.Parent.level3,
                                                          CategoryLinks.ServiceCategory.Parent.level4,
                                                          CategoryLinks.ServiceCategory.Parent as ParentID,
                                                          CategoryLinks.ServiceCategory
                                                   FROM ServiceProduct
                                                   WHERE $searchField = $id
                                                   AND CategoryLinks.ServiceCategory IS NOT NULL
                                                   GROUP BY CategoryLinks.ServiceCategory")->next();

            //Grab every category from all of the generated chains. Every leaf in this list should be end user visible.
            while($categoryChain = $categoryChains->next()) {
                $depth = 1;
                $parent = null;
                foreach($categoryChain as $key => $value) {
                    if($depth > $maxLevel) break;

                    if($value) {
                        if(!$this->isEnduserVisible($value)) break;
                        if(!isset($allCategories[$value]))
                            $allCategories[$value] = false;
                        // Parent has visible child
                        if($parent !== null)
                            $allCategories[$parent] = true;
                        $parent = $value;
                        $depth++;
                    }
                }
            }
        }

        ksort($allCategories);
        return $allCategories;
    }

    /**
     * Ensures that the given filter name and id are legit. Considers null to be an acceptable value for $id.
     * @param string $filterType Filter name
     * @param string|int|null $id ID of the filter
     * @return null|object Null if everything checks out, ResponseObject if there's a problem
     */
    private function verifyRequest($filterType, $id) {
        if(!$connectName = $this->getFilter($filterType))
            return $this->getResponseObject(null, null, new \RightNow\Libraries\ResponseError(null, null, null, "Invalid filter type: '$filterType'"));

        if($id !== null && (!Framework::isValidID($id) || !$this->isEnduserVisible($id)))
            return $this->getResponseObject(null, null, new \RightNow\Libraries\ResponseError(null, null, null, "Invalid ID: No such Service Product or Category with ID = '$id'"));
    }

    /**
     * Ensure that the maxLevel is within the constraints
     * @param int $maxLevel An integer value to verify
     * @return null|object Null if the max level is valid, otherwise a ResponseObject with an error
     */
    private function verifyMaxLevel($maxLevel) {
        if(!is_int($maxLevel) || $maxLevel > 6 || $maxLevel < 1) {
            return $this->getResponseObject(null, null, "Invalid Max Level. The value must be an integer and in the range [1,6]");
        }
    }

    /**
     * Returns the pluralized name for Product and Category
     * @param string $filterType Description.
     *
     * @return string Either Products or Categories
     */
    private function getPluralizedName($filterType)
    {
        if($filterType === 'Category')
          return 'Categories';
        return 'Products';
    }

    /**
     * Return an array of top level Product or Category connect objects.
     * @param string $connectName Name of Connect object
     * @param int $limit Number of items to limit result to
     * @param array $topLevelIDs A list of top level product or category IDs
     * @return array List of products or categories
     */
    private function getTopLevelObjects($connectName, $limit, array $topLevelIDs = array()) {
        $objects = array();
        $query = "SELECT Service{$connectName} FROM Service{$connectName} WHERE Parent IS NULL" .
            ($topLevelIDs ? (' AND ID IN ('. implode(',', array_map('intval', $topLevelIDs)) . ')') : '') .
            " AND EndUserVisibleInterfaces.ID = curInterface() ORDER BY DisplayOrder LIMIT $limit";
        $queryResult = Connect\ROQL::queryObject($query)->next();
        while ($connectObject = $queryResult->next()) {
            $objects[] = $connectObject;
        }
        return $objects;
    }

    /**
     * Queries out all of the children of the provided list of parent nodes
     * @param  array  $list         List of parent ServiceProduct or ServiceCategory objects
     * @param  string $connectName Type of object, either 'Product' or 'Category'
     * @param  int    $limit       Limit on the number of children nodes to return
     * @return array  List of child nodes, keyed by their ID
     * @throws \Exception If the provided $connectName was invalid or the query fails
     */
    private function getDirectDescendantsFromList(array $list, $connectName, $limit){
        $parentIDList = array();
        foreach($list as $parent){
            $parentIDList []= $parent->ID;
        }
        array_unique($parentIDList);

        if(!count($parentIDList)){
            return array();
        }
        $query = sprintf("SELECT ID, LookupName, DisplayOrder, Parent as ParentID FROM Service{$connectName}
                          WHERE Parent IN (%s) AND EndUserVisibleInterfaces.ID = curInterface() ORDER BY DisplayOrder LIMIT $limit", implode(',', $parentIDList));
        $objects = array();
        $queryResult = Connect\ROQL::query($query)->next();
        while ($result = $queryResult->next()) {
            $objects[(int)$result['ID']] = $result;
        }
        return $objects;
    }

    /**
     * Combines a list of parent and children nodes into a single hiearchy.
     * @param  array $parents  Array of parent ServiceProduct or ServiceCategory objects
     * @param  array $children Array of child products/categories from ROQL query (i.e. not Connect objects)
     * @param  int   $level    Level of products/categories we're going to display
     * @return array Combined results
     */
    private function combineParentsAndChildren(array $parents, array $children, $level){
        $hierarchy = array();
        foreach($parents as $parent) {
            $ID = $parent->ID;
            $hierarchy[$ID] = array(
                'id'       => $parent->ID,
                'label'    => $parent->LookupName,
                'seq'      => $parent->DisplayOrder,
                'parent'   => $parent->ID,
                'level'    => 0,
                'hierList' => (string)$parent->ID,
            );
            if($level < 2) {
                continue;
            }
            $hierarchy[$ID]['subItems'] = array();
            foreach($children as $childID => $child) {
                if((int)$child['ParentID'] === $ID) {
                    $hierarchy[$ID]['subItems'] []= array(
                        'id' => $childID,
                        'label' => $child['LookupName'],
                        'seq' => (int)$child['DisplayOrder'],
                        'parent' => $ID,
                        'level' => 1,
                        'hierList' => "{$ID},{$childID}",
                    );
                    //Remove this child so we don't have to iterate over it for subsequent parents
                    unset($children[$childID]);
                }
            }
        }
        return $hierarchy;
    }

    /**
     * Return an array of items needed by getHierPopup, sorted and grouped by level and display order.
     *
     * @param string $connectName Product or Category.
     * @param array $IDs An array of product or category IDs. Empty will fetch all.
     * @return array Sorted list of items
     */
    private function getSortedItems($connectName, array $IDs = array()) {
        $cacheKey = "{$this->cacheKey}{$connectName}Popup";
        if (!$sorted = $this->getCached($cacheKey)) {
            $unsorted = array();
            $levels = array();

            $query = "SELECT Service{$connectName} FROM Service{$connectName} ORDER BY Parent, DisplayOrder";
            $queryResult = Connect\ROQL::queryObject($query)->next();
            while ($connectObject = $queryResult->next()) {
                $chain = $this->getChainFromObject($connectObject);
                if ($chain === $this->getEnduserVisibleHierarchy($chain)->result) {
                    $unsorted[$connectObject->ID] = array(
                        'name' => $connectObject->Name ?: $connectObject->LookupName,
                        'display_order' => $connectObject->DisplayOrder,
                        'chain' => $chain,
                    );
                    $levels[count($chain) - 1][] = $connectObject->ID;
                }
            }

            // Now that we've built the levels array, loop through and create the sort_array used by usort
            $sorted = array();
            foreach($unsorted as $ID => $items) {
                $popup = array($items['name'], $ID, $items['display_order']);
                $sortArray  = array();
                $level = 0;
                foreach($items['chain'] as $index => $chainID) {
                    $sortArray[] = array_search($chainID, $levels[$index]) ?: 0;
                    $popup[] = $chainID;
                    if ($chainID === $ID) {
                        $level = $index;
                    }
                }
                $popup[9] = ''; // Not exactly sure this is necessary, but mimicking current behavior for now.
                $popup['level'] = $level;
                $popup['hier_list'] = implode(',', $items['chain']);
                $sortArray = array_pad($sortArray, 6, 0);
                $sortArray[] = count($items['chain']) - 1;
                $sortArray[] = $items['display_order'];
                $sorted[] = array('id' => $ID, 'popup' => $popup, 'sort_array' => $sortArray);
            }

            usort($sorted, function($a, $b) {
                if ($a['sort_array'] === $b['sort_array'])
                    return 0;
                return ($a['sort_array'] < $b['sort_array']) ? -1 : 1;
            });
            $this->cache($cacheKey, $sorted);
        }

        $hierarchy = array();
        foreach($sorted as $values) {
            if (!$IDs || in_array($values['id'], $IDs)) {
                $hierarchy[] = $values['popup'];
            }
        }
        return $hierarchy;
    }

    /**
     * Return the product hierarchy IDs to search in
     *
     * @param int $id Product ID
     * @return array List of WHERE clauses according to product level
     */
    private function getProductLevels($id = null) {
        $searchFields = array();
        if ($id) {
            $query = "SELECT ID, LookupName, Parent as ParentID,
                            Parent.level1 as lv1,
                            Parent.level2 as lv2,
                            Parent.level3 as lv3,
                            Parent.level4 as lv4
                      FROM ServiceProduct
                      WHERE ID = $id";

            $queryResult = Connect\ROQL::query($query)->next();
            while ($connectObject = $queryResult->next()) {
                if ($connectObject['ParentID'] > 0 && $connectObject['lv4'] > 0)
                    $searchFields = array("ID");
                else if ($connectObject['ParentID'] > 0 && $connectObject['lv3'] > 0)
                    $searchFields = array("ID", "Parent");
                else if ($connectObject['ParentID'] > 0 && $connectObject['lv2'] > 0)
                    $searchFields = array("ID", "Parent", "Parent.level4");
                else if ($connectObject['ParentID'] > 0 && $connectObject['lv1'] > 0)
                    $searchFields = array("ID", "Parent", "Parent.level3");
                else if ($connectObject['ParentID'] > 0 && (!$connectObject['lv1'] || !$connectObject['lv2'] || !$connectObject['lv3'] || !$connectObject['lv4']))
                    $searchFields = array("ID", "Parent", "Parent.level2");
                else if (!$connectObject['ParentID'])
                    $searchFields = array("ID", "Parent", "Parent.level1");
            }
        }
        return $searchFields;
    }

    /**
     * Fetches the ID, LookupName and Description of the products or categories
     * @param string $filterType Filtertype can be product or category.
     * @param array $prodCatList List of products/categories for which comment count should be determined.
     * @param boolean $showDescription Determines whether to fetch the description or not.
     * @return array Array containing the list of product/category metadata
     */
    public function getProdCatByIDs($filterType, array $prodCatList = array(), $showDescription = true) {
        $prodCats = array();
        try {
            $roql = "Select ID, LookupName" . ($showDescription ? ", Descriptions.LabelText" : "") . " from Service{$filterType} WHERE" . (!empty($prodCatList) ? " ID IN (" . implode(',', $prodCatList) . ") AND" : null) . " EndUserVisibleInterfaces.ID = curInterface() AND Descriptions.Language = curLanguage()";
            $results = Connect\ROQL::query($roql)->next();
            while($row = $results->next()) {
                $prodCats[$row['ID']] = array('name' => $row['LookupName'], 'desc' => $row['LabelText']);
            }
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject($prodCats, 'is_array', null, null);
    }
}
