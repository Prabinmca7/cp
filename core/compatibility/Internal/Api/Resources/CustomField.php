<?php
namespace RightNow\Internal\Api\Resources;
use RightNow\Api\Models\CustomField as CustomFieldModel,
    RightNow\Internal\Api\Structure\Document,   
    RightNow\Internal\Api\Structure\Relationship,
    RightNow\Internal\Api\Response;

require_once CORE_FILES . 'compatibility/Internal/Api/Models/CustomField.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Structure/Relationship.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Resources/Base.php';

class CustomField extends Base {

    const IS_API_OPEN = true;

    public function __construct() {
        $this->type = "customFields";
        $this->attributeMapping = array(
            'osvc' => array(
                'customField'       => array(
                    "fieldName"    => "col_name",
                    "dataType"      => "data_type",
                    "defaultValue"  => "dflt_val",
                    "fieldSize"     => "field_size",
                    "hint"          => "lang_hint",
                    "isRequired"    => "required",
                    "label"         => "lang_name",
                    "mask"          => "mask",
                    "maxValue"      => "max_val",
                    "menuItems"     => "menu_items",
                    "minValue"      => "min_val"
                ),
            )
        );
    }

    /**
     * Fetches list of custom fields, and generates {json-api} document
     * @param array $params Contains queryParamters and uriParameters
     * @return object {json-api} top level document
     */
    public function getCustomFieldList($params) {
        $document = new Document();
        if (!self::IS_API_OPEN) {
            $errors = $this->createError(Response::getErrorResponseObject("This API is currently disabled.", Response::HTTP_NOT_FOUND_STATUS_CODE)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $fields = isset($params['queryParams']['filter']['fields']) ? $params['queryParams']['filter']['fields'] : null;
        $resourseType = isset($params['queryParams']['filter']['type']) ? $params['queryParams']['filter']['type'] : null;
        $visibility = isset($params['queryParams']['filter']['visibility']) ? $params['queryParams']['filter']['visibility'] : null;

        $attributes = array_keys($this->attributeMapping['osvc']['customField']);

        if(!$fields) {
            $errors = $this->createError(Response::getErrorResponseObject("Missing required query parameter: filter[fields]", Response::HTTP_BAD_REQUEST)->errors);
            $document->setErrors($errors);
            return $document->output();
        }
        if(!$resourseType) {
            $errors = $this->createError(Response::getErrorResponseObject("Missing required query parameter: filter[type]", Response::HTTP_BAD_REQUEST)->errors);
            $document->setErrors($errors);
            return $document->output();
        }
        if(!$visibility) {
            $errors = $this->createError(Response::getErrorResponseObject("Missing required query parameter: filter[visibility]", Response::HTTP_BAD_REQUEST)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $customField = new CustomFieldModel();
        $requestedFields = array_map('trim', explode(",", $fields));
        $result = $customField->getList($resourseType, $visibility, $requestedFields);

        if($result->errors) {
            $errors = $this->createError($result->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $data = $this->createDataCollection($result->result, $attributes);
        $document->setData($data);
        return $document->output();
    }
}
