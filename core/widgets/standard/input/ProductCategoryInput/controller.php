<?php

namespace RightNow\Widgets;

use RightNow\Utils\Url,
    RightNow\Utils\Text;

class ProductCategoryInput extends \RightNow\Libraries\Widget\Input
{
    const PRODUCT = 'Product';
    const CATEGORY = 'Category';

    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        if (parent::getData() === false) return false;

        if($this->data['attrs']['set_button']) {
            $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        }

        $this->data['js']['table'] = $this->table;
        $dataType = $this->data['js']['data_type'] = (Text::stringContains(strtolower($this->fieldName), 'prod'))
            ? self::PRODUCT
            : self::CATEGORY;
        $isProduct = ($dataType === self::PRODUCT);
        $this->data['js']['readableProdcatIds'] = $this->data['js']['readableProdcatIdsWithChildren'] = $this->data['js']['permissionedProdcatList'] = $this->data['js']['permissionedProdcatIds'] = array();

        if ($this->data['js']['data_type'] === self::CATEGORY) {
            $this->data['attrs']['label_all_values'] =
                ($this->data['attrs']['label_all_values'] === \RightNow\Utils\Config::getMessage(ALL_PRODUCTS_LBL))
                ? \RightNow\Utils\Config::getMessage(ALL_CATEGORIES_LBL)
                : $this->data['attrs']['label_all_values'];

            $this->data['attrs']['label_input'] =
                ($this->data['attrs']['label_input'] === \RightNow\Utils\Config::getMessage(PRODUCT_LBL))
                ? \RightNow\Utils\Config::getMessage(CATEGORY_LBL)
                : $this->data['attrs']['label_input'];

            $this->data['attrs']['label_nothing_selected'] =
                ($this->data['attrs']['label_nothing_selected'] === \RightNow\Utils\Config::getMessage(SELECT_A_PRODUCT_LBL))
                ? \RightNow\Utils\Config::getMessage(SELECT_A_CATEGORY_LBL)
                : $this->data['attrs']['label_nothing_selected'];
        }

        if ($this->data['js']['table'] === 'Contact') {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(NO_LONGER_SUPPORTED_PART_ATTRIBUTE_MSG), 'Contact', 'Object', 'name'));
            return false;
        }

        if (!in_array($this->dataType, array('ServiceProduct', 'ServiceCategory'))) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(DATA_TYPE_PCT_S_APPR_PROD_S_CAT_MSG), $this->fieldName));
            return false;
        }

        if($this->data['attrs']['required_lvl'] > $this->data['attrs']['max_lvl']) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(VAL_PCT_S_EXCEEDS_PCT_S_PCT_S_SET_MSG), "required_lvl", "max_lvl", "max_lvl", "required_lvl", $this->data['attrs']['required_lvl']));
            $this->data['attrs']['max_lvl'] = $this->data['attrs']['required_lvl'];
        }

        if($this->data['attrs']['hint'] && strlen(trim($this->data['attrs']['hint']))){
            $this->data['js']['hint'] = $this->data['attrs']['hint'];
        }

        $this->data['js']['linkingOn'] = $this->data['attrs']['linking_off'] ? 0 : $this->CI->model('Prodcat')->getLinkingMode();
        $this->data['js']['hm_type'] = $isProduct ? HM_PRODUCTS : HM_CATEGORIES;

        // Build up a tree of the default data set given a default chain. If there is not a default chain and linking
        // is off, just return the top level products or categories. If linking is on and this is the category
        // widget, return all of the linked categories.
        $maxLevel = $this->data['attrs']['max_lvl'];
        $defaultChain = $this->getDefaultChain();
        if($this->data['js']['linkingOn'] && !$isProduct) {
            $defaultProductID = $this->CI->model('Prodcat')->getDefaultProductID() ?: null;
            $this->data['js']['link_map'] = $defaultHierMap = $this->CI->model('Prodcat')->getFormattedTree($dataType, $defaultChain, true, $defaultProductID, $maxLevel)->result;
            $this->data['js']['hierDataNone'] = $this->CI->model('Prodcat')->getFormattedTree($dataType, array(), true, null, $maxLevel)->result;
            array_unshift($this->data['js']['hierDataNone'][0], array('id' => 0, 'label' => $this->data['attrs']['label_all_values']));
            array_unshift($this->data['js']['link_map'][0], array('id' => 0, 'label' => $this->data['attrs']['label_all_values']));
        }
        else {
            if($isProduct) {
                $this->CI->model('Prodcat')->setDefaultProductID(end($defaultChain));
            }
            $defaultHierMap = $this->CI->model('Prodcat')->getFormattedTree($dataType, $defaultChain, false, null, $maxLevel)->result;
        }

        if($this->data['attrs']['verify_permissions'] !== 'None') {
            $permissionMethod = 'getPermissionedListSocialQuestion' . $this->data['attrs']['verify_permissions'];
            $permissionedHierarchy = $this->CI->model('Prodcat')->$permissionMethod($isProduct)->result;
            //Not permissioned to view any prodcats
            if(is_null($permissionedHierarchy))
                return false;

            if(is_array($permissionedHierarchy)) {
                $this->data['js']['permissionedProdcatList'] = $permissionedHierarchy;
                $this->data['js']['permissionedProdcatIds'] = $this->buildListOfPermissionedProdcatIds();
                list($this->data['js']['readableProdcatIds'], $this->data['js']['readableProdcatIdsWithChildren']) = $this->getProdcatInfoFromPermissionedHierarchies($this->data['js']['permissionedProdcatList']);
                $defaultHierMap = $this->pruneEmptyPaths($defaultHierMap, $defaultChain);
                if($this->data['js']['linkingOn'] && !$isProduct && $this->data['js']['hierDataNone']) {
                    $this->data['js']['hierDataNone'] = $this->pruneEmptyPaths($this->data['js']['hierDataNone']);
                }
                $this->updateProdcatsForReadPermissions($defaultHierMap, $this->data['js']['readableProdcatIds'], $this->data['js']['readableProdcatIdsWithChildren']);
                if((!empty($this->data['js']['readableProdcatIds'])) && $this->data['attrs']['required_lvl'] === 0) {
                    $this->data['attrs']['required_lvl'] = 1;
                }
            }
        }

        // Add in the all values label
        array_unshift($defaultHierMap[0], array('id' => 0, 'label' => $this->data['attrs']['label_all_values']));
        $this->data['js']['hierData'] = $defaultHierMap;
    }

    /**
    * Retrieves defaults with the following priority:
    * 1) old-school POST parameter (e.g. 'p')
    * 2) new-school POST parameter (e.g. 'incidents_prod')
    * 3) new-new-school POST parameter (e.g. 'Incident_Product')
    * 4) from the product or category data of an incident
    * 5) old-school URL parameter (e.g. 'p', 'c')
    * 6) new-school URL parameter (e.g. incidents.prod)
    * 7) new-new-school URL parameter (e.g. Incident.Product) (yep.)
    * 8) default_value attribute
    * @return Array The default chain chosen from the above options. If no chain is found, returns an empty array.
    */
    protected function getDefaultChain() {
        $dataType = $this->data['js']['data_type'];
        $shortDataType = ($dataType === self::PRODUCT) ? 'prod' : 'cat';
        $defaultValue = null;

        $postKeys = array(
            "Incident_$dataType",
            "incidents_$shortDataType",
            $shortDataType[0],
        );
        $urlKeys = array(
            "Incident.$dataType",
            "incidents.$shortDataType",
            $shortDataType[0],
        );

        // Look for a value in the the post vars. Generally only used by basic pageset
        foreach ($postKeys as $key) {
            $postParam = $this->CI->input->post($key);
            if ($postParam !== false) {
                $defaultValue = $postParam;
            }
        }

        // Look for a value in the incident data
        $incidentID = Url::getParameter('i_id');
        if (($defaultValue === false || $defaultValue === null) &&
            $incidentID && $incident = $this->CI->model('Incident')->get($incidentID)->result) {
            $incidentValue = $incident->{$dataType}->ID;
            if ($incidentValue) {
                $defaultValue = $incidentValue;
            }
        }

        // Look for a value in the url params
        if ($defaultValue === false || $defaultValue === null) {
            foreach ($urlKeys as $key) {
                $urlParam = Url::getParameter($key);
                if ($urlParam !== null) {
                    $defaultValue = $urlParam;
                }
            }
        }

        // Look for a value in the widget attributes
        if ($defaultValue === false || $defaultValue === null) {
            $defaultFromAttribute = $this->data['attrs']['default_value'];
            if ($defaultFromAttribute !== false) {
                $defaultValue = $defaultFromAttribute;
            }
        }

        // If the given value is only one ID long then it may be the last ID in a chain.
        // Attempt to get the full chain. If a full chain is given, trust that it is correct and get
        // the end user visible portion of it.
        if($defaultValue) {
            $defaultChain = explode(',', $defaultValue);
            $defaultChain = (count($defaultChain) === 1)
                ? $this->CI->model('Prodcat')->getFormattedChain($dataType, $defaultChain[0], true)->result
                : $this->CI->model('Prodcat')->getEnduserVisibleHierarchy($defaultChain)->result;
            if(count($defaultChain) > $this->data['attrs']['max_lvl']) {
                $defaultChain = array_splice($defaultChain, 0, $this->data['attrs']['max_lvl']);
            }
        }

        return isset($defaultChain) && $defaultChain ? $defaultChain : array();
    }

    /**
    * Get a list of all paths that have valid product choices for the user
    * @param array $hierMap Map containing the current default prodcats the user will have access to
    * @param array $defaultChain List of IDs that should prepopulate the dropdown
    * @return array Updated map of prodcats the user will have access to
    */
    protected function pruneEmptyPaths(array $hierMap, array $defaultChain = array()) {
        $pathsToKeep = array();

        // User has full prodcat permissions
        if($this->data['js']['permissionedProdcatIds'] === array()) {
            return $hierMap;
        }

        // Build a list of all root elements whose children the user has access to
        if(is_array($this->data['js']['permissionedProdcatList'])) {
            foreach($this->data['js']['permissionedProdcatList'] as $permissionedElement) {
                if(!in_array($permissionedElement['Level1'], $pathsToKeep)) {
                    array_push($pathsToKeep, $permissionedElement['Level1']);
                }
            }
        }

        // Prune any paths that are completely unavailable to the user
        foreach($hierMap[0] as $index => $element) {
            if(!in_array($element['id'], $pathsToKeep)) {
                unset($hierMap[0][$index]);
            }
        }
        $hierMap[0] = array_values($hierMap[0]);

        // Prune default chain values
        foreach($defaultChain as $defaultChainID) {
            if(is_array($hierMap[$defaultChainID])) {
                foreach($hierMap[$defaultChainID] as $index => $hierData) {
                    if(!in_array($hierData['id'], $this->data['js']['permissionedProdcatIds']) && (!isset($hierData['hasChildren']) || !$hierData['hasChildren']) && (!isset($hierData['selected']) || !$hierData['selected'])) {
                        unset($hierMap[$defaultChainID][$index]);
                    }
                }
                $hierMap[$defaultChainID] = array_values($hierMap[$defaultChainID]);
            }
        }

        return $hierMap;
    }

    /**
    * Build a list of element IDs the current user is permissioned to view
    * @return array Array of IDs the current user is able to view, or empty array for no restrictions
    */
    protected function buildListOfPermissionedProdcatIds() {
        // User has visibility to all elements, return empty array
        if($this->data['js']['permissionedProdcatList'] === array()) {
            return array();
        }

        // Prepopulate the array with the default 'Select a product' ID
        $privilegedIDs = array(0);

        if(is_array($this->data['js']['permissionedProdcatList'])) {
            foreach($this->data['js']['permissionedProdcatList'] as $permissionedElement) {
                array_push($privilegedIDs, $permissionedElement['ID']);
            }
        }

        return $privilegedIDs;
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
        $productCatIds = array(0);
        $prodcatIdsWithChildren = array();

        foreach($prodcatHierarchies as $prodcatHierarchy) {
            for($i = 1; $i < 7; $i++) {
                // Hierarchy data has 6 'levels', represented with the keys 'Level1', 'Level2',
                // all the way up to 'Level6'.
                $level = 'Level' . $i;
                if(!$prodcatHierarchy[$level])
                    break;

                $productCatIds []= (int) $prodcatHierarchy[$level];
                if($prodcatHierarchy['Level' . ($i + 1)])
                    $prodcatIdsWithChildren []= (int) $prodcatHierarchy[$level];
            }
        }

        return array(
            array_values(array_unique($productCatIds)),
            array_values(array_unique($prodcatIdsWithChildren))
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
        foreach($prodcats as &$prodcatGroup) {
            foreach($prodcatGroup as $prodcatKey => $prodcat) {
                if(!in_array($prodcat['id'], $readableProdcatIds)) {
                    unset($prodcatGroup[$prodcatKey]);
                }
                else if(in_array($prodcat['id'], $readableProdcatIdsWithChildren)) {
                    $prodcat['hasChildren'] = true;
                }
                else {
                    $prodcat['hasChildren'] = null;
                }
            }
        }
    }
}
