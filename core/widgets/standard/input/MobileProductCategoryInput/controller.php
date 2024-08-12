<?php
namespace RightNow\Widgets;

use RightNow\Utils\Text,
    Rightnow\Utils\Url;

class MobileProductCategoryInput extends \RightNow\Libraries\Widget\Input {
    const PRODUCT = 'Product';
    const CATEGORY = 'Category';

    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (parent::getData() === false) return false;

        $this->data['js']['table'] = $this->table;
        $dataType = $this->data['js']['data_type'] = (Text::stringContains(strtolower($this->fieldName), 'prod'))
            ? self::PRODUCT
            : self::CATEGORY;
        $this->data['isProduct'] = ($dataType === self::PRODUCT);
        $this->data['js']['readableProdcatIds'] = $this->data['js']['readableProdcatIdsWithChildren'] = $this->data['js']['permissionedProdcatList'] = $this->data['js']['permissionedProdcatIds'] = $this->data['js']['initial'] = array();

        // all of the label defaults are for products - if the filter type is category, see if
        // the labels have the product default value and replace them with the category default value
        // otherwise, the attribute values were modified and should persist
        if ($this->data['js']['data_type'] === self::CATEGORY) {
            $this->data['attrs']['label_all_values'] =
            ($this->data['attrs']['label_all_values'] === \RightNow\Utils\Config::getMessage(ALL_PRODUCTS_LBL))
            ? \RightNow\Utils\Config::getMessage(ALL_CATEGORIES_LBL)
            : $this->data['attrs']['label_all_values'];

            $this->data['attrs']['label_input'] =
            ($this->data['attrs']['label_input'] === \RightNow\Utils\Config::getMessage(PRODUCT_LBL))
            ? \RightNow\Utils\Config::getMessage(CATEGORY_LBL)
            : $this->data['attrs']['label_input'];

            $this->data['attrs']['label_prompt'] =
            ($this->data['attrs']['label_prompt'] === \RightNow\Utils\Config::getMessage(SELECT_A_PRODUCT_LBL))
            ? \RightNow\Utils\Config::getMessage(SELECT_A_CATEGORY_LBL)
            : $this->data['attrs']['label_prompt'];

            $this->data['attrs']['label_data_type'] =
            ($this->data['attrs']['label_data_type'] === \RightNow\Utils\Config::getMessage(PRODUCTS_LBL))
            ? \RightNow\Utils\Config::getMessage(CATEGORIES_LBL)
            : $this->data['attrs']['label_data_type'];
        }

        if ($this->data['js']['table'] === 'Contact') {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(NO_LONGER_SUPPORTED_PART_ATTRIBUTE_MSG), 'Contact', 'Object', 'name'));
            return false;
        }

        if (!in_array($this->dataType, array('ServiceProduct', 'ServiceCategory'))) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(DATA_TYPE_PCT_S_APPR_PROD_S_CAT_MSG), $this->fieldName));
            return false;
        }

        if ($this->data['attrs']['required_lvl'] > $this->data['attrs']['max_lvl']) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(VAL_PCT_S_EXCEEDS_PCT_S_PCT_S_SET_MSG), "required_lvl", "max_lvl", "max_lvl", "required_lvl", $this->data['attrs']['required_lvl']));
            $this->data['attrs']['max_lvl'] = $this->data['attrs']['required_lvl'];
        }

        $this->data['js']['linkingOn'] = $this->data['attrs']['linking_off'] ? 0 : $this->CI->model('Prodcat')->getLinkingMode();
        $this->data['firstLevel'] = array(); //only populated with the first-level items and only passed to the view

        //If there is a default chain for this widget, find it and create the top level array.
        $defaultChain = $this->getDefaultChain();

        if ($this->data['attrs']['verify_permissions'] !== 'None') {
            $permissionMethod = 'getPermissionedListSocialQuestion' . $this->data['attrs']['verify_permissions'];
            $permissionedHierarchy = $this->CI->model('Prodcat')->$permissionMethod($this->data['isProduct'])->result;
            //Not permissioned to view any prodcats
            if (is_null($permissionedHierarchy)) {
                return false;
            }

            if (is_array($permissionedHierarchy)) {
                $this->data['js']['permissionedProdcatList'] = $permissionedHierarchy;
                $this->data['js']['permissionedProdcatIds'] = $this->buildListOfPermissionedProdcatIds();
                list($this->data['js']['readableProdcatIds'], $this->data['js']['readableProdcatIdsWithChildren']) = $this->getProdcatInfoFromPermissionedHierarchies($this->data['js']['permissionedProdcatList']);
                $defaultChain = $this->isChainPermissioned($defaultChain) ? $defaultChain : array();
            }
        }

        if ($this->data['js']['linkingOn'] && !$this->data['isProduct']) {
            $defaultProductID = $this->CI->model('Prodcat')->getDefaultProductID() ?: null;
            if ($defaultProductID) {
                // If this is the categories widget AND there's some pre-selected product(s) AND linking is on -> get prod linking defaults.
                $defaultSelection = $this->setProdLinkingDefaults($this->data['firstLevel'], $defaultProductID, $defaultChain);
            }
            else {
                // Otherwise just get the data normally.
                $defaultSelection = $this->setDefaults($this->data['firstLevel'], $defaultChain, $dataType);
            }
        }
        else {
            if ($this->data['isProduct']) {
                $this->CI->model('Prodcat')->setDefaultProductID(end($defaultChain));
            }
            $defaultSelection = $this->setDefaults($this->data['firstLevel'], $defaultChain, $dataType);
        }

        if ($defaultSelection) {
            $this->data['js']['initial'] = $defaultSelection;
            $label = '';
            foreach ($defaultSelection as $item) {
                $label .= Text::escapeHtml($item['label']) . '<br>';
            }
        }
        else {
            $label = $this->data['attrs']['label_prompt'];
        }

        if ($this->data['attrs']['verify_permissions'] !== 'None' && is_array($permissionedHierarchy)) {
            $this->data['firstLevel'] = $this->pruneEmptyPaths($this->data['firstLevel'], $this->data['js']['initial']);
            $this->updateProdcatsForReadPermissions($this->data['firstLevel'], $this->data['js']['readableProdcatIds'], $this->data['js']['readableProdcatIdsWithChildren']);
        }

        $this->data['promptLabel'] = $label;
        $this->data['js']['hm_type'] = $this->data['isProduct'] ? HM_PRODUCTS : HM_CATEGORIES;

        //If there are no products or categories either don't render the widget at all, or if it's possible that it will display later, just hide it.
        if (empty($this->data['firstLevel'])) {
            if ($this->data['js']['linkingOn'] && !$this->data['isProduct']) {
                $this->classList->add('rn_HideEmpty');
            }
            else {
                return false;
            }
        }
    }

    /**
     * Retrieves defaults with the following priority:
     * 1) from the product or category data of an incident
     * 2) new-school POST parameter (e.g. 'incidents.prod')
     * 3) new-new-school POST parameter (e.g. 'Incident.Product')
     * 4) old-school URL parameter (e.g. 'p', 'c')
     * 5) new-school URL parameter (e.g. incidents.prod)
     * 6) new-new-school URL parameter (e.g. Incident.Product) (yep.)
     * 7) default_value attribute
     * @return array The default chain chosen from the above options. If no chain is found, returns an empty array.
     */
    protected function getDefaultChain() {
        //Attempt to find a default value in one of the 7 different areas.
        $dataType = $this->data['js']['data_type'];
        if (($incidentID = Url::getParameter('i_id')) && ($incident = $this->CI->model('Incident')->get($incidentID)->result)) {
            $defaultValue = $incident->{$dataType}->ID;
        }
        if (!isset($defaultValue) || !$defaultValue) {
            $order = array(
                // PHP replaces dots in POST parameter names with underscores, so look for that syntax
                array('name' => "incidents_" . ($this->data['isProduct'] ? 'prod' : 'cat'), 'post' => true),
                array('name' => "Incident_{$dataType}", 'post' => true),
                array('name' => strtolower($dataType[0])),
                array('name' => "incidents." . ($this->data['isProduct'] ? 'prod' : 'cat')),
                array('name' => "Incident.$dataType"),
            );
            foreach ($order as $prefill) {
                $defaultValue = (isset($prefill['post']) && $prefill['post'])
                ? $this->CI->input->post($prefill['name'])
                : Url::getParameter($prefill['name']);

                if ($defaultValue) {
                    break;
                }
            }
            $defaultValue || ($defaultValue = $this->data['attrs']['default_value']);
        }
        //If the given value is only one ID long then it may be the last ID in a chain.
        //Attempt to get the full chain. If a full chain is given, trust that it is correct and get
        //the end user visible portion of it.
        if ($defaultValue) {
            $defaultChain = explode(',', $defaultValue);
            $defaultChain = (count($defaultChain) === 1)
            ? $this->CI->model('Prodcat')->getFormattedChain($dataType, $defaultChain[0], true)->result
            : $this->CI->model('Prodcat')->getEnduserVisibleHierarchy($defaultChain)->result;
            if (count($defaultChain) > $this->data['attrs']['max_lvl']) {
                $defaultChain = array_splice($defaultChain, 0, $this->data['attrs']['max_lvl']);
            }
        }
        return (isset($defaultChain) && $defaultChain) ? $defaultChain : array();
    }

    /**
     * Utility function to retrieve hier menus and massage
     * the data for our usage.
     * @param array|null &$firstLevelItems To be populated with the first-level of items
     * @param array|null $hierItems List of hier menu IDs
     * @param string $dataType Name of data type (either products or categories)
     * @return array Populated array containing the pre-selected items
     */
    protected function setDefaults(&$firstLevelItems, $hierItems, $dataType) {
        $selection = array(); //populated list of what items are already chosen via URL parameter values
        $model = $this->CI->model('Prodcat');
        if ($hierItems) {
            // Get the hierarchy chain for the specified ids.
            $lastItem = end($hierItems);
            $selection = $model->getFormattedChain($dataType, $lastItem)->result;
        }
        if (!$firstLevelItems = $model->getDirectDescendants($dataType)->result) {
            return false;
        }

        if ($selection) {
            $firstLevelSelectedItem = $selection[0]['id'];
            foreach ($firstLevelItems as &$item) {
                if ($item['id'] == $firstLevelSelectedItem) {
                    $item['selected'] = true;
                    $selection[0] = array_merge($selection[0], $item);
                    break;
                }
            }
        }

        //add an additional 'no value' node to the front
        array_unshift($firstLevelItems, array('id' => 0, 'label' => $this->data['attrs']['label_all_values']));

        return $selection;
    }

    /**
     * Utility function to retrieve hier menus for prod linking
     * and massage the data for our usage.
     * @param array|null &$firstLevelItems To be populated with the first-level of items
     * @param int $productID The previously selected product ID
     * @param array|null $catArray List of category hier menu IDs
     * @return array Populated array containing the pre-selected items
     */
    protected function setProdLinkingDefaults(&$firstLevelItems, $productID, $catArray) {
        if (!($hierArray = $this->CI->model('Prodcat')->getLinkedCategories($productID)->result)) {
            return false;
        }

        ksort($hierArray);

        $matchIndex = 0;
        $hierList = '';
        $selection = array(); //Populate with the URL pre-selected categories
        foreach ($hierArray as $parentID => $child) {
            if (!count($child)) {
                //for some reason there's empty arrays floating around...
                unset($hierArray[$parentID]);
                continue;
            }
            foreach ($child as $dataArray) {
                $id = $dataArray['id'];
                if ($id === intval($catArray[$matchIndex])) {
                    $selected = true;
                    $matchIndex++;
                    $hierList .= $id;
                    $selection[] = $dataArray + array('hierList' => $hierList);
                    $hierList .= ',';
                }
                else {
                    $selected = false;
                }
                if ($parentID === 0) {
                    //only want to pass first-level items to the view
                    $firstLevelItems[] = $dataArray + array('selected' => $selected);
                }
            }
        }

        //add an additional 'no value' node to the front
        array_unshift($firstLevelItems, array('id' => 0, 'label' => $this->data['attrs']['label_all_values']));
        $this->data['js']['linkMap'] = $hierArray;
        return $selection;
    }

    /**
     * Get a list of all paths that have valid product choices for the user
     * @param array $hierMap Map containing the current default prodcats the user will have access to
     * @return array Updated map of prodcats the user will have access to
     */
    protected function pruneEmptyPaths(array $hierMap) {
        $pathsToKeep = array();
        // User has full prodcat permissions
        if ($this->data['js']['permissionedProdcatIds'] === array()) {
            return $hierMap;
        }

        // Build a list of all root elements whose children the user has access to
        if (is_array($this->data['js']['permissionedProdcatList'])) {
            foreach ($this->data['js']['permissionedProdcatList'] as $permissionedElement) {
                if (!in_array($permissionedElement['Level1'], $pathsToKeep)) {
                    array_push($pathsToKeep, $permissionedElement['Level1']);
                }
            }
        }

        // Prune any paths that are completely unavailable to the user
        foreach ($hierMap as $index => $element) {
            if (!in_array($element['id'], $pathsToKeep)) {
                unset($hierMap[$index]);
            }
        }

        return array_values($hierMap);
    }

    /**
     * Build a list of element IDs the current user is permissioned to view
     * @return array Array of IDs the current user is able to view, or empty array for no restrictions
     */
    protected function buildListOfPermissionedProdcatIds() {
        // User has visibility to all elements, return empty array
        if ($this->data['js']['permissionedProdcatList'] === array()) {
            return array();
        }

        // Prepopulate the array with the default 'Select a product' ID
        $privilegedIDs = array(0);

        if (is_array($this->data['js']['permissionedProdcatList'])) {
            foreach ($this->data['js']['permissionedProdcatList'] as $permissionedElement) {
                array_push($privilegedIDs, $permissionedElement['ID']);
            }
        }

        return $privilegedIDs;
    }

    /**
     * For a given prodcat chain verify that its items are readable.
     * @param array $chain Chain of products or categories
     * @return boolean Whether the chain of products or categories is readable. This
     * function will return false if any part of the chain is not readable.
     */
    protected function isChainPermissioned(array $chain) {
        // Check to see if the selected ID is permissioned
        if (!is_array($chain) || (is_array($chain) && !empty($chain) && !in_array($chain[count($chain) - 1], $this->data['js']['permissionedProdcatIds']))) {
            return false;
        }

        //Ensure the chain leading to the selectable ID is readable the whole way
        if (count($this->data['js']['readableProdcatIds']) > 0) {
            foreach ($chain as $prodcat) {
                if (!in_array($prodcat, $this->data['js']['readableProdcatIds'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns two single-dimensional arrays representing information about readable
     * prodcats from a permission hierarchy array. First array contains unique readable
     * prodcat ids from the permission hierarchy. The second array contains unique
     * readable prodcat ids which have readable children.
     * @param array $prodcatHierarchies An array of arrays of prodcat hierarchies
     * @return array An array containing two arrays; first one is a list of readable
     *    product ids, the second is readable product ids with readable childen.
     */
    protected function getProdcatInfoFromPermissionedHierarchies(array $prodcatHierarchies) {
        $productCatIds = $prodcatIdsWithChildren = array();

        foreach ($prodcatHierarchies as $prodcatHierarchy) {
            for ($i = 1; $i < 7; $i++) {
                // Hierarchy data has 6 'levels', represented with the keys 'Level1', 'Level2',
                // all the way up to 'Level6'.
                $level = 'Level' . $i;
                if (!$prodcatHierarchy[$level]) {
                    break;
                }

                $productCatIds[] = (int) $prodcatHierarchy[$level];
                if ($prodcatHierarchy['Level' . ($i + 1)]) {
                    $prodcatIdsWithChildren[] = (int) $prodcatHierarchy[$level];
                }
            }
        }

        return array(
            array_values(array_unique($productCatIds)),
            array_values(array_unique($prodcatIdsWithChildren)),
        );
    }

    /**
     * Removes non-readable prodcats from an array representing a (presumably readable) prodcat
     * hierarchy, and updates the 'hasChildren' attributes of contained prodcats pertaining to
     * whether or not they have readable children.
     * @param array &$prodcats An array representing a prodcat hierarchy.
     * @param array $readableProdcatIds An single-dimensional array of prodcat ids for which to remove.
     * @param array $readableProdcatIdsWithChildren An single-dimensional array of readable prodcat ids
     *    which have readable childen.
     */
    protected function updateProdcatsForReadPermissions(array &$prodcats, array $readableProdcatIds, array $readableProdcatIdsWithChildren) {
        foreach ($prodcats as $prodcatKey => &$prodcat) {
            if (!in_array($prodcat['id'], $readableProdcatIds)) {
                unset($prodcatGroup[$prodcatKey]);
            }
            else if (in_array($prodcat['id'], $readableProdcatIdsWithChildren)) {
                $prodcat['hasChildren'] = true;
            }
            else {
                $prodcat['hasChildren'] = null;
            }
        }
    }
}
