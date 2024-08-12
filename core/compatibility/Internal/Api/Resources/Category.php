<?php
namespace RightNow\Internal\Api\Resources;
use RightNow\Api\Models\ProductCategory as ProductCategoryModel,
    RightNow\Internal\Api\Structure\Document,   
    RightNow\Internal\Api\Structure\Relationship,
    RightNow\Internal\Api\Response;

require_once CORE_FILES . 'compatibility/Internal/Api/Models/ProductCategory.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Structure/Relationship.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Resources/Base.php';

class Category extends Base {

    const IS_API_OPEN = true;

    public function __construct() {
        $this->type = "categories";
        $this->attributeMapping = array(
            'osvc' => array(
                'category'           => array(
                    "children"      => "Children",
                    "hasChildren"   => "HasChildren",
                    "name"          => "LookupName",
                ),
                'categoryList'       => array(
                    "hasChildren"   => "HasChildren",
                    "name"          => "LookupName",
                ),
            )
        );
    }

    /**
     * Fetches a category, and generates {json-api} document
     * @param array $params Contains queryParamters and uriParameters
     * @return object {json-api} top level document
     */
    public function getCategory($params) {
        $categoryId = $params['uriParams']['categories'];
        $attributes = array_keys($this->attributeMapping['osvc']['category']);
        $document = new Document();
        $category = new ProductCategoryModel();

        if (!self::IS_API_OPEN) {
            $errors = $this->createError(Response::getErrorResponseObject("This API is currently disabled.", Response::HTTP_NOT_FOUND_STATUS_CODE)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $result = $category->getCategory($categoryId);

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
     * Fetches list of categories, and generates {json-api} document
     * @return object {json-api} top level document
     */
    public function getCategoryList() {
        $attributes = array_keys($this->attributeMapping['osvc']['categoryList']);
        $document = new Document();
        $category = new ProductCategoryModel();

        if (!self::IS_API_OPEN) {
            $errors = $this->createError(Response::getErrorResponseObject("This API is currently disabled.", Response::HTTP_NOT_FOUND_STATUS_CODE)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $result = $category->getTopLevelCategories();

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
     * Helper method to generate children for the document:include
     * @param object $category ServiceCategory object
     * @return array {json-api} Array of child categories
     */
    private function includeChildren($category) {
        $includeMembers = array();
        if($category->Children) {
            foreach($category->Children as $index => $childCategory) {
                $includeMembers[] = $this->createInclude($childCategory, 'list');
            }
        }   
        return $includeMembers;
    }
}
