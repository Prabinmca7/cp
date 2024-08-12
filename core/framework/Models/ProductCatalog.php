<?php

namespace RightNow\Models;
use RightNow\Api,
    RightNow\Utils\Framework,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Config;

/**
 * Retrieves product data from the DB.
 */
class ProductCatalog extends Base {
    /**
     * Maximum level of Parent Folders.
     */
    const MAX_LEVEL = 11;
    private $cacheKey = 'ProductCatalogModel';
    private $interfaceID;

    public function __construct() {
        parent::__construct();
        $this->interfaceID = Api::intf_id();
        $this->cacheKey .= $this->interfaceID;
    }

    /**
     * Returns a Product Catalog connect object from specified $id.
     *
     * @param int $id A Product Catalog ID.
     * @return Connect\SalesProduct|null Either the Sales Product Connect object or null
     * if no result could be found for the provided ID.
     */
    public function get($id) {
        if (Framework::isValidID($id)) {
            try {
                return $this->getResponseObject(Connect\SalesProduct::fetch($id));
            }
            catch(Connect\ConnectAPIErrorBase $e) {
                // pass
            }
        }
        return $this->getResponseObject(null, null, Config::getMessage(INV_ID_SA_PROD_ID_EQUALS_ID_COLON_LBL));
    }

    /**
     * Return all the direct descendants of a specific Sales Product Folder. If level=0, it returns products and folders at root level.
     * @param int|null $id A valid ID
     * @param int $level Denotes which level of descendants to retrieve
     * @param boolean $isSearchRequest True if request is from search functionality, false if request is from Product registration
     * @return array A flat list of all the direct children of the given ID. Each item in the array is an array with the following structure:
     *   ['id' => integer id, 'label' => string label, 'hasChildren' => boolean whether the item has children,
     *   'serialized' => boolean whether the item is a serialized product]
     */
    public function getDirectDescendants($id = null, $level = 0, $isSearchRequest = false) {
        $level = intval($level);
        if($isSearchRequest) {
            $searchClause = "And AdminVisibleInterfaces.ID = curInterface()";
            $cacheKey = "{$this->cacheKey}{$id}{$level}SearchDescendants";
        }
        else {
            $searchClause = "AND Disabled != 1 And Attributes.IsServiceProduct = 1 And AdminVisibleInterfaces.ID = curInterface()";
            $cacheKey = "{$this->cacheKey}{$id}{$level}Descendants";
        }

        $hierarchy = $this->getCached($cacheKey);

        if ($hierarchy === false || $hierarchy === null) {
            $hierarchy = array();

            try {
                $getObjects = function($resultSet) {
                    $objects = array();
                    if($resultSet !== null) {
                        while($object = $resultSet->next()) {
                            $objects[] = $object;
                        }
                    }
                    return $objects;
                };

                $products = null;
                $foldersDetail = null;
                $foldersLevelDetail = null;

                if($level === 0) {
                    //Fetch Sales Products at root level i.e level 0
                    $products = Connect\ROQL::queryObject("SELECT SalesProduct FROM SalesProduct WHERE Folder IS NULL $searchClause ORDER BY LookupName")->next();
                    //This query will return folders which contain sales products
                    $foldersDetail = Connect\ROQL::query("SELECT DISTINCT Folder.ID,Folder.LookupName FROM SalesProduct WHERE Folder.ID IS NOT NULL and Folder.Level1 IS NULL $searchClause")->next();
                    //This query will return folders which contain subfolders
                    $foldersLevelDetail = Connect\ROQL::query("SELECT DISTINCT Folder.Level1.ID, Folder.Level1.LookupName FROM SalesProduct WHERE Folder.Level1 IS NOT NULL $searchClause")->next();
                }
                else {
                    $currentLevel = $level + 1;
                    //Fetch Sales Products that are present under folder with the given id
                    $products = Connect\ROQL::queryObject(sprintf("SELECT SalesProduct FROM SalesProduct WHERE Folder.ID = %d $searchClause ORDER BY LookupName", $id))->next();

                    if($level <= self::MAX_LEVEL) {
                        //This query will return folders at the desired level which contain sales products
                        if($level === self::MAX_LEVEL) {
                            $foldersDetail = Connect\ROQL::query(sprintf("SELECT DISTINCT Folder.ID, Folder.LookupName FROM SalesProduct WHERE Folder.Level$level.ID = %d $searchClause", $id))->next();
                        }
                        else {
                            $foldersDetail = Connect\ROQL::query(sprintf("SELECT DISTINCT Folder.ID, Folder.LookupName FROM SalesProduct WHERE Folder.Level$level.ID = %d AND Folder.Level$currentLevel IS NULL $searchClause", $id))->next();
                            $foldersLevelDetail = Connect\ROQL::query(sprintf("SELECT DISTINCT Folder.Level$currentLevel.ID, Folder.Level$currentLevel.LookupName FROM SalesProduct WHERE Folder.Level$level.ID = %d AND Folder.Level$currentLevel IS NOT NULL $searchClause", $id))->next();
                        }
                    }
                }

                $foldersDetailObjects = $getObjects($foldersDetail);
                $foldersLevelDetailObjects = $getObjects($foldersLevelDetail);
                $folders = array_merge($foldersDetailObjects, $foldersLevelDetailObjects);
                $folderObjects = $this->getDistinctFolders($folders);

                foreach($folderObjects as $folder) {
                    $hierarchy[] = array(
                        'id' => (int) $folder['ID'],
                        'label' => $folder['LookupName'],
                        'hasChildren' => true,
                    );
                }

                $productObjects = $getObjects($products);
                foreach($productObjects as $product) {
                    $hierarchy[] = array(
                        'id' => $product->ID,
                        'label' => $product->LookupName,
                        'hasChildren' => false,
                        'serialized' => (intval($product->Attributes->HasSerialNumber) === 1),
                    );
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
     * Returns distinct list of folders.
     * @param array $folders A list of folders
     * @return array An array of distinct folders
     */
    private function getDistinctFolders(array $folders) {
        $folderIDs = array();
        $distinctFolders = array();
        foreach($folders as $folder) {
            $id = (int) $folder['ID'];
            if (!in_array($id, $folderIDs, true)) {
                $folderIDs[] = $id;
                $distinctFolders[] = $folder;
            }
        }

        usort($distinctFolders, function($a, $b) {
            return strcmp($a['LookupName'], $b['LookupName']);
        });

        return $distinctFolders;
    }

    /**
     * Return the chain to the root of Sales Product.
     * Typically used by DisplaySearchFilters
     * @param int $id A valid ID
     * @param boolean $asFlatArray An optional parameter indicating whether labels should be including in the results
     * @param boolean $showNonVisibleProduct True if you want to display a non-visible product, false otherwise
     * @return array An array of parents to the root with labels attached.  [0] = Root -> [N] = $ID elem
     */
    public function getFormattedChain($id, $asFlatArray = false, $showNonVisibleProduct = false) {
        if(!Framework::isValidID($id)) {
            return $this->getResponseObject(array(), 'is_array', "Invalid ID: No such Sales Product with ID = '$id'");
        }

        $cacheKey = "{$this->cacheKey}{$id}{$asFlatArray}FormattedChain";

        if (!$visibleHierarchy = $this->getCached($cacheKey)) {
            try {
                $hierarchy = $visibleHierarchy = array();
                $salesProduct = Connect\ROQL::queryObject(sprintf("SELECT SalesProduct FROM SalesProduct WHERE ID = %d" . ($showNonVisibleProduct ? "" : " And AdminVisibleInterfaces.ID = curInterface()"), $id))->next();
                $connectObject = null;
                if($object = $salesProduct->next()) {
                    $connectObject = $object;
                }

                if($connectObject !== null) {
                    if(($folder = $connectObject->Folder) !== null) {
                        if(($parentFolders = $folder->Parents) !== null) {
                            foreach($parentFolders as $parentFolder) {
                                $hierarchy[] = array('id' => $parentFolder->ID, 'label' => $parentFolder->LookupName);
                            }
                        }

                        $hierarchy[] = array('id' => $folder->ID, 'label' => $folder->LookupName);
                    }
                    $hierarchy[] = array('id' => $connectObject->ID, 'label' => $connectObject->LookupName);
                }

                //Only return the visible nodes and if necessary flatten the array
                foreach($hierarchy as $object) {
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
     * Handles the creation of Product Catalog Tree when given a chain of selected items.
     * The selected product (the one at the end of the chain) is marked in the
     * resultant tree so that it can be easily rendered on the client side. The tree follows the following
     * format:
     *      array(0 =>
     *            array(
     *                 array('id' => <ID of a product catalog or folder at the root (0) level>,
     *                       'label' => <End User Label>,
     *                       'hasChildren' => <Flag indicating whether or not this node has children>,
     *                       'selected' => <Flag indicating whether or not this node is selected from the chain>)
     *                  ...
     *                  ),
     *            4 =>
     *            array(
     *                 array('id' => <ID parented by product catalog folder 4>,
     *                       ...)
     *                  ...
     *                  ),
     *           )
     *
     * The tree map will contain all of the data necessary to render the selected chain.
     * @param array $selectedChain The selected product chain. A valid chain of products.
     * @param boolean $isSearchRequest True if request is from search functionality, false if request is from Product registration
     * @return array The aforementioned array representing a tree.
     */
    public function getFormattedTree(array $selectedChain, $isSearchRequest = false) {
        $productCatalogTree = array();

        array_unshift($selectedChain, 0); //Always include the root node
        $level = count($selectedChain) - 2;
        $productID = array_pop($selectedChain);
        $hasSelected = false;
        while(($id = array_pop($selectedChain)) !== null) {
            $descendants = array();
            if($level === 0) {
                $descendants = $this->getDirectDescendants(null, 0, $isSearchRequest)->result;
            }
            else {
                $descendants = $this->getDirectDescendants($id, $level, $isSearchRequest)->result;
            }

            if (is_array($descendants) && count($descendants)) {
                foreach($descendants as &$child) {
                    if($child['selected'] = ($child['id'] === (int)$productID && !$hasSelected)) {
                        $hasSelected = true;
                    }
                }
                $productCatalogTree[$id] = $descendants;
            }

            $level--;
        }

        ksort($productCatalogTree);
        return $this->getResponseObject($productCatalogTree, 'is_array');
    }

    /**
     * Return an array of hier_menu items of specific type in sorted order.
     * @param boolean $isSearchRequest True if request is from search functionality, false if request is from Product registration
     * @return array The sorted array of product entries
     */
    public function getHierPopup($isSearchRequest = false) {
        return $this->getResponseObject($this->getCompleteProductCatalogHierarchy($isSearchRequest), 'is_array');
    }

    /**
     * Return an array which consists of 2 arrays. One is array of processed product and folder entries, second one is any array of products only.
     * @param boolean $isSearchRequest True if request is from search functionality, false if request is from Product registration
     * @return array This array contains two arrays. One is array of processed product and folder entries, second one is any array of products only.
     */
    private function getProductCatalogHierarchy($isSearchRequest = false) {
        //Fetch all sales products from Product Catalog
        if($isSearchRequest) {
            $searchClause = "where AdminVisibleInterfaces.ID = curInterface()";
        }
        else {
            $searchClause = "where Disabled != 1 And Attributes.IsServiceProduct = 1 And AdminVisibleInterfaces.ID = curInterface()";
        }
        $salesProducts = Connect\ROQL::queryObject("SELECT SalesProduct FROM SalesProduct $searchClause")->next();
        $itemList = array();
        $unsortedProductList = array();
        while ($salesProduct = $salesProducts->next()) {
            $folderChain = array();
            $parentFolder = $salesProduct->Folder;

            if($parentFolder !== null) {
                $folderTree = $parentFolder->Parents;
                if($folderTree !== null) {
                    foreach ($folderTree as $folder) {
                        $folderChain[] = $folder->ID;
                        $itemList[$folder->ID] = array(
                            'name' => $folder->Name ?: $folder->LookupName,
                            'chain' => $folderChain,
                            'isFolder' => true,
                        );
                    }
                }
                $folderChain[] = $parentFolder->ID;
                $itemList[$parentFolder->ID] = array(
                    'name' => $parentFolder->Name ?: $parentFolder->LookupName,
                    'chain' => $folderChain,
                    'isFolder' => true,
                );
            }

            $folderChain[] = $salesProduct->ID;

            $rootFolder = 0;
            if(count($folderChain) > 1) {
                $rootFolder = $folderChain[0];
            }

            $itemList[$salesProduct->ID] = array(
                'name' => $salesProduct->Name ?: $salesProduct->LookupName,
                'chain' => $folderChain,
                'isFolder' => false,
                'rootFolder' => $rootFolder,
            );

            $unsortedProductList[] = $salesProduct->ID;
        }
        return array($unsortedProductList, $itemList);
    }

    /**
     * Return an array of sales product items in a sorted order.
     * @param array $itemList Preprocessed array of folder and product entries
     * @param array $unsortedProductList Array of products
     * @return array The sorted array of product entries
     */
    private function sortProductCatalogHierarchy(array $itemList, array $unsortedProductList) {
        $sortedProductList = array();
        foreach($unsortedProductList as $unsortedProduct) {
            $key = -1;
            $unsortedRootFolder = $itemList[$unsortedProduct]['rootFolder'];

            //Go through each sorted product until we find a location that we know the unsorted product should appear before.
            //In that case, we insert the unsorted product before that specific product.
            //Otherwise, we'll end up appending the unsorted product to the end of the sorted list

            foreach($sortedProductList as $sortedProduct) {
                $sortedRootFolder = $itemList[$sortedProduct]['rootFolder'];
                // Compare products w.r.t their root folders
                if($unsortedRootFolder === 0 && $sortedRootFolder === 0) {
                    //both Products at root level
                    $unsortedProductName = $itemList[$unsortedProduct]['name'];
                    $sortedProductName = $itemList[$sortedProduct]['name'];

                    //Sort these products
                    if(strcmp($unsortedProductName, $sortedProductName) < 0) {
                        //If the unsorted product should be before the sorted product, set the key so that it's inserted before
                        $key = array_search($sortedProduct, $sortedProductList);
                        break;
                    }
                }
                else if ($unsortedRootFolder !== 0 && $sortedRootFolder === 0) {
                    //The unsorted product is in a folder and the sorted product is in the root, so it must be inserted before the sorted product.
                    $key = array_search($sortedProduct, $sortedProductList);
                    break;
                }
                else if($unsortedRootFolder === $sortedRootFolder) {
                    // both the unsorted and sorted products belong to same root. As a result we need to traverse down the chain for sorting
                    $sortedProductChain = $itemList[$sortedProduct]['chain'];
                    $unsortedProductChain = $itemList[$unsortedProduct]['chain'];
                    $minLevel = min(count($unsortedProductChain), count($sortedProductChain));
                    $index = 1; //Index starts from 1, as it is root folder and is same for both products

                    while($index < $minLevel) {
                        $sortedFolderID = $sortedProductChain[$index];
                        $unsortedFolderID = $unsortedProductChain[$index];

                        $index++;
                        //If the next level's folders are not equal, determine where the unsorted product should be inserted. If they are equal, go to the next level.
                        if($unsortedFolderID !== $sortedFolderID) {
                            $isFolderUnsortedItem = $itemList[$unsortedFolderID]['isFolder'];
                            $isFolderSortedItem = $itemList[$sortedFolderID]['isFolder'];
                            $unsortedFolderName = $itemList[$unsortedFolderID]['name'];
                            $sortedFolderName = $itemList[$sortedFolderID]['name'];

                            if($isFolderUnsortedItem && $isFolderSortedItem) {
                                if(strcmp($unsortedFolderName, $sortedFolderName) < 0) {
                                    //They're both in different folders, so insert the unsorted product before the sorted product if the unsorted product's folder is less than the sorted product's folder
                                    $key = array_search($sortedProduct, $sortedProductList);
                                    break 2;
                                }
                            }
                            else if ($isFolderUnsortedItem && !$isFolderSortedItem) {
                                //The unsorted product is in a folder, but the sorted product is not, so the unsorted product must be inserted before the sorted product.
                                $key = array_search($sortedProduct, $sortedProductList);
                                break 2;
                            }
                            else if(!$isFolderUnsortedItem && !$isFolderSortedItem) {
                                if(strcmp($unsortedFolderName, $sortedFolderName) < 0) {
                                    //If both the unsorted and sorted products are not in further sub-folders, insert the unsorted product before the sorted product if the unsorted product name should appear before the sorted product name.
                                    $key = array_search($sortedProduct, $sortedProductList);
                                    break 2;
                                }
                            }
                            //If the folders are not the same, but we could not find a reason to definitely insert the unsorted product before the sorted product, continue on to the next sorted product.
                            break;
                        }
                    }
                }
                else if($unsortedRootFolder === 0 && $sortedRootFolder !== 0) {
                    // unsorted is a root level product and sorted product is under a folder.
                    $key = -1;
                }
                else if ($unsortedRootFolder !== $sortedRootFolder ) {
                    //They're both in different folders, so insert the unsorted product before the sorted product if they unsorted product's folder is less than the sorted product's folder
                    $unsortedFolderName = $itemList[$unsortedRootFolder]['name'];
                    $sortedFolderName = $itemList[$sortedRootFolder]['name'];

                    if(strcmp($unsortedFolderName, $sortedFolderName) < 0) {
                        $key = array_search($sortedProduct, $sortedProductList);
                        break;
                    }
                }
            }
            if($key === -1) {
                array_push($sortedProductList, $unsortedProduct);
            }
            else {
                array_splice($sortedProductList, $key, 0, $unsortedProduct);
            }
        }

        return $sortedProductList;
    }

    /**
     * Return an array of items needed by getHierPopup, sorted and grouped by level.
     * @param boolean $isSearchRequest True if request is from search functionality, false if request is from Product registration
     * @return array Sorted list of items
     */
    private function getCompleteProductCatalogHierarchy($isSearchRequest = false) {
        $cacheKey = $this->cacheKey . ($isSearchRequest ? "SalesProductSearchPopup" : "SalesProductPopup");

        if (!$hierarchy = $this->getCached($cacheKey)) {
            //Fetch all sales products and build the entire tree structure.
            //$unsortedProductList contains list of unsorted products.
            //$itemList consists of folders and products in the hierarchy tree.
            list($unsortedProductList, $itemList) = $this->getProductCatalogHierarchy($isSearchRequest);

            //Sort the tree structure
            $sortedProductList = $this->sortProductCatalogHierarchy($itemList, $unsortedProductList);

            $sortItems = array();
            $hierarchy = array();
            //Build the tree structure required for rendering.
            foreach ($sortedProductList as $sortedList) {
                $chain = $itemList[$sortedList]['chain'];
                foreach ($chain as $item) {
                    if(!in_array($item, $sortItems)) {
                        $sortItems[] = $item;
                        $folder = $itemList[$item];
                        $popup = array($folder['name'], $item, $folder['chain'], $folder['isFolder']);
                        $level = count($folder['chain']) - 1;
                        $popup['level'] = $level;
                        $popup['hier_list'] = implode(',', $folder['chain']);
                        $hierarchy[] = $popup;
                    }
                }
            }
            $this->cache($cacheKey, $hierarchy);
        }
        return $hierarchy;
    }
}