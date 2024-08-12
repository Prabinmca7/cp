<?php
namespace RightNow\Api\Models;

use RightNow\Api,
    RightNow\Internal\Api\Response,
    RightNow\Internal\Utils\Version as Version,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Utils\Framework;

require_once CORE_FILES . 'compatibility/Internal/Api/Models/Base.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Response.php';

class ProductCategory extends Base {

    /**
     * Return a Category for specified $id.
     * @param int $id Category Id.
     * @return Connect\ServiceCategory Category with list of its children
     */
    public function getCategory($id) {
        if (Framework::isValidID($id)) {
            try {
                $category = call_user_func(CONNECT_NAMESPACE_PREFIX . '\\' . 'ServiceCategory' . '::fetch', $id);
                if (!$this->isEnduserVisible($category)) {
                    return Response::getErrorResponseObject("Invalid Id", Response::HTTP_BAD_REQUEST);
                }
                $category->Children = $this->getDirectDescendants('Category', $id);
                $category->HasChildren = count($category->Children) ? true : false;
                return Response::getResponseObject($category, 'is_object');
            }
            catch(\Exception $e) {
                return Response::getErrorResponseObject("An unexpected error has occurred.", Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        return Response::getErrorResponseObject('No category found with given id.', Response::HTTP_NOT_FOUND_STATUS_CODE);
    }

    /**
     * Return a Product for specified $id.
     * @param int $id Product Id.
     * @return Connect\ServiceProduct Product with list of its children
     */
    public function getProduct($id) {
        if (Framework::isValidID($id)) {
            try {
                $product = call_user_func(CONNECT_NAMESPACE_PREFIX . '\\' . 'ServiceProduct' . '::fetch', $id);
                if (!$this->isEnduserVisible($product)) {
                    return Response::getErrorResponseObject("Invalid Id", Response::HTTP_BAD_REQUEST);
                }
                $product->Children = $this->getDirectDescendants('Product', $id);
                $product->HasChildren = count($product->Children) ? true : false;
                return Response::getResponseObject($product, 'is_object');
            }
            catch(\Exception $e) {
                return Response::getErrorResponseObject("An unexpected error has occurred.", Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        return Response::getErrorResponseObject('No product found with given id.', Response::HTTP_NOT_FOUND_STATUS_CODE);
    }

    /**
     * Fetches top level categories
     * @return Array Array of ServiceCategory objects
     */
    public function getTopLevelCategories() {
        return Response::getResponseObject($this->getDirectDescendants('Category'), 'is_array');
    }

    /**
     * Fetches top level products
     * @return Array Array of ServiceProduct objects
     */
    public function getTopLevelProducts() {
        return Response::getResponseObject($this->getDirectDescendants('Product'), 'is_array');
    }

    /**
     * Return all the direct descendants of a specific ID.
     * @param string $filterType The value 'Product' or 'Category'
     * @param int|null $id A valid ID
     * @return array A flat list of all the direct children of the given ID. Each item in the array is an array with the following structure:
     *   ['ID' => integer id, 'LookupName' => string label, 'HasChildren' => boolean whether the item has children]
     */
    private function getDirectDescendants($filterType, $id = null) {
        if($id && !Framework::isValidID($id))
            return Response::getErrorResponseObject("Invalid Id", Response::HTTP_BAD_REQUEST);

        if($filterType === 'Product') {
            $connectName = 'ServiceProduct';
        }
        if($filterType === 'Category') {
            $connectName = 'ServiceCategory';
        }
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
            $query = "SELECT ID, LookupName
            FROM {$connectName}
            WHERE Parent " . (($id) ? "= $id" : "IS NULL") . " AND EndUserVisibleInterfaces.ID = curInterface()  
            ORDER BY DisplayOrder";

            if($this->connectVersion == 1.4)
                $firstLevelObjects = \RightNow\Connect\v1_4\ROQL::query($query)->next();
            else
                $firstLevelObjects = \RightNow\Connect\v1_3\ROQL::query($query)->next();
            
            $parents = $getObjects($firstLevelObjects);

            //Now find all of the next level objects
            if(!empty($parents)) {
                $mapFunction = function($i){
                    return $i['ID'];
                };
                $childQuery = "SELECT Parent
                FROM {$connectName}
                WHERE Parent IN (" . implode(',', array_map($mapFunction, $parents)) . ")
                AND EndUserVisibleInterfaces.ID = curInterface()";

                if($this->connectVersion == 1.4)
                    $childObjects = \RightNow\Connect\v1_4\ROQL::query($childQuery)->next();
                else    
                    $childObjects = \RightNow\Connect\v1_3\ROQL::query($childQuery)->next();

                //Transform all of the unique parents into a hash for easy indexing
                $children = $getObjects($childObjects);

                $getParent = function($i){
                    return $i['Parent'];
                };
                $children = array_flip(array_unique(array_map($getParent, $children)));

                foreach($parents as $parent) {
                    $objectID = (int) $parent['ID'];
                    $hierarchy[] = (object) array(
                        'ID'            => $objectID,
                        'LookupName'    => $parent['LookupName'],
                        'HasChildren'   => isset($children[$objectID])
                    );
                }
            }
        }
        catch(\Exception $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return empty($hierarchy) ? null : $hierarchy;
    }

    /**
     * Returns true if product or category is enduser visible.
     * @param Connect\ServiceProduct|Connect\ServiceCategory $connectObject A product or category connect object
     * @return bool|null Whether the product or category is enduser visible; null if an object wasn't found
     */
    private function isEnduserVisible($connectObject) {
        if ($connectObject) {
            if(ConnectUtil::isArray($connectObject->EndUserVisibleInterfaces)){
                foreach ($connectObject->EndUserVisibleInterfaces as $interface) {
                    if ($interface->ID === Api::intf_id()) {
                        $isVisible = true;
                    }
                }
            }
        }
        return $isVisible;
    }
}
