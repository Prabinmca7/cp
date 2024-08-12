<?php
namespace RightNow\Internal\Api\Resources;
use RightNow\Api\Models\ProductCategory as ProductCategoryModel,
    RightNow\Internal\Api\Structure\Document,   
    RightNow\Internal\Api\Structure\Relationship,
    RightNow\Internal\Api\Response;

require_once CORE_FILES . 'compatibility/Internal/Api/Models/ProductCategory.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Structure/Relationship.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Resources/Base.php';

class Product extends Base {

    const IS_API_OPEN = true;

    public function __construct() {
        $this->type = "products";
        $this->attributeMapping = array(
            'osvc' => array(
                'product'           => array(
                    "children"      => "Children",
                    "hasChildren"   => "HasChildren",
                    "name"          => "LookupName",
                ),
                'productList'       => array(
                    "hasChildren"   => "HasChildren",
                    "name"          => "LookupName",
                ),
            )
        );
    }

    /**
     * Fetches a product, and generates {json-api} document
     * @param array $params Contains queryParamters and uriParameters
     * @return object {json-api} top level document
     */
    public function getProduct($params) {
        $productId = $params['uriParams']['products'];
        $attributes = array_keys($this->attributeMapping['osvc']['product']);
        $document = new Document();
        $product = new ProductCategoryModel();

        if (!self::IS_API_OPEN) {
            $errors = $this->createError(Response::getErrorResponseObject("This API is currently disabled.", Response::HTTP_NOT_FOUND_STATUS_CODE)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $result = $product->getProduct($productId);

        if($result->errors) {
            $errors = $this->createError($result->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $data = $this->createData($result->result, $attributes);
        $document->setData($data);
        $document->setIncluded($this->includeChildren($result->result));
        return $document->output();
    }

    /**
     * Fetches list of products, and generates {json-api} document
     * @return object {json-api} top level document
     */
    public function getProductList() {
        $attributes = array_keys($this->attributeMapping['osvc']['productList']);
        $document = new Document();
        $product = new ProductCategoryModel();

        if (!self::IS_API_OPEN) {
            $errors = $this->createError(Response::getErrorResponseObject("This API is currently disabled.", Response::HTTP_NOT_FOUND_STATUS_CODE)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $result = $product->getTopLevelProducts();

        if($result->errors) {
            $errors = $this->createError($result->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $data = $this->createDataCollection($result->result, $attributes);
        $document->setData($data);
        return $document->output();
    }

    /**
     * Helper method to generate children for document:include
     * @param object $product ServiceProduct object
     * @return array {json-api} Array of child products
     */
    private function includeChildren($product) {
        $includeMembers = array();
        if($product->Children) {
            foreach($product->Children as $index => $childProduct) {
                $includeMembers[] = $this->createInclude($childProduct, 'list');
            }
        }   
        return $includeMembers;
    }
}
