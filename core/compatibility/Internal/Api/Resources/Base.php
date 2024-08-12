<?php
namespace RightNow\Internal\Api\Resources;
use RightNow\Internal\Api\Structure\Relationship;

require_once CORE_FILES . 'compatibility/Internal/Api/Structure/Error.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Structure/Data.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Structure/Relationship.php';

abstract class Base{
    const DATETIME_ATTRIBUTES = array(
        'created',
        'updated'
    );
    public $attributeMapping;
    public $type;

    /**
     * Generates {json-api} data
     * @param array $result Contains result data
     * @param array $attributes Contains fields to be included in the response
     * @return object {json-api} data
     */
    public function createData($result, $attributes) {
        $data = new \RightNow\Internal\Api\Structure\Data();
        $data->setId((string)$result->ID);
        $data->setType($this->type);

        if($this->getClassName() === 'Answer') {
            if($result->AnswerType->ID === $this->ATTACHMENT_TYPE_ANSWER) {
                $result->URL = null; //URL will be available with FileAttachments property
            }
        }
        foreach ($attributes as $attribute) {
            $mappedAttribute = $this->getMappedConnectAttributes()[$attribute];

            if($mappedAttribute === 'AnswerType') {
                $data->setAttributes($attribute, $result->AnswerType->LookupName);
                continue;
            }

            if($mappedAttribute === 'Children' && $result->Children) {
                if ($this->getClassName() === 'Product') {
                    $product = new \RightNow\Internal\Api\Resources\Product();
                }
                if ($this->getClassName() === 'Category') {
                    $product = new \RightNow\Internal\Api\Resources\Category();
                }
                $relationship = $product->createRelationship($result->Children);
                $data->setRelationships($relationship);
                continue;
            }

            if($mappedAttribute === 'FileAttachments' && $result->FileAttachments) {
                $fileMapping = $this->getMappedConnectAttributes('file');
                $files = array();
                foreach($result->FileAttachments as $index => $attachment) {
                    $file = array();
                    foreach ($fileMapping as $field => $mappedField) {
                        if(in_array($field, self::DATETIME_ATTRIBUTES) && $attachment->$mappedField) {
                            $file[$field] = date(DATE_W3C, $attachment->$mappedField);
                            continue;
                        }
                        $file[$field] = $attachment->$mappedField;
                    }
                    $files[$index] = $file;
                }
                $data->setAttributes($attribute, $files);
                continue;
            }

            if($mappedAttribute === 'Language') {
                $data->setAttributes($attribute, $result->Language->LookupName);
                continue;
            }

            if($mappedAttribute === 'PrimaryContact' || $mappedAttribute === 'Contact') {
                $contact = new \RightNow\Internal\Api\Resources\Contact();
                $relationship = $contact->createRelationship(($result->PrimaryContact ? $result->PrimaryContact : $result->Contact));
                $data->setRelationships($relationship);
                continue;
            }

            if($mappedAttribute === 'Threads' && $result->Threads) {
                $incidentThread = new \RightNow\Internal\Api\Resources\IncidentThread();
                $relationship = $incidentThread->createRelationship($result->Threads);
                $data->setRelationships($relationship);
                continue;
            }

            if(in_array($attribute, self::DATETIME_ATTRIBUTES) && $result->$mappedAttribute) {
                $data->setAttributes($attribute, date(DATE_W3C, $result->$mappedAttribute));
                continue;
            }
            $data->setAttributes($attribute, $result->$mappedAttribute);
        }

        return $data->output();
    }

    /**
     * Generates {json-api} data collection
     * @param array $result Contains result data
     * @param array $attributes Contains fields to be included in the response
     * @return array {json-api} data collection
     */
    public function createDataCollection($result, $attributes) {
        $dataCollection = array();

        for($index = 0; $index < count($result); $index++) {
            $dataItem = new \RightNow\Internal\Api\Structure\Data();
            $dataItem->setType($this->type);
            $dataItem->setId((string)$result[$index]->ID);

            foreach ($attributes as $attribute) {
                $objectAttribute = $this->getMappedConnectAttributes('list')[$attribute];
                if(in_array($attribute, self::DATETIME_ATTRIBUTES) && $result[$index]->$objectAttribute) {
                    $dataItem->setAttributes($attribute, date(DATE_W3C, $result[$index]->$objectAttribute));
                    continue;
                }
                $dataItem->setAttributes($attribute, $result[$index]->$objectAttribute);
            }
            $dataCollection[$index] = $dataItem->output();
        }
        return $dataCollection;
    }

    /**
     * Generates {json-api} error
     * @param array $errors Contains errors
     * @return array {json-api} error
     */
    public function createError($errors) {
        $err = array();
        for($index = 0; $index < count($errors); $index++) {
            $error = new \RightNow\Internal\Api\Structure\Error();
            $error->setDetail($errors[$index]->externalMessage);
            $error->setStatus($errors[$index]->errorCode);
            $err[$index] = $error->output();
        }
        return $err;
    }

    /**
     * Generates resource for document:include
     * @param object $thread Connect object for the resource
     * @param string $attributeGroup Group of attributes to be included
     * @return object Resource to be included
     */
    public function createInclude($thread, $attributeGroup = null) {
        $attributes = array_keys($this->getMappedConnectAttributes($attributeGroup));
        return $this->createData($thread, $attributes);
    }

    /**
     * Generates {json-api} meta data for search
     * @param object $result Contains result data
     * @return object {json-api} meta data
     */
    public function createMeta($result) {
        $meta = new \RightNow\Internal\Api\Structure\Meta();
        $meta->setLimit((string) $result->size);
        $meta->setOffset((string) $result->offset);
        $meta->setTotalResults((string) $result->total);
        return $meta->output();
    }

    /**
     * Generates {json-api} data member for relationships
     * @param array $connectObject Contact object for which relationship data member has to be created
     * @return object {json-api} data:relationship
     */
    public function createRelationship($connectObject) {
        $relationship = new Relationship();
        $dataMember = new \stdClass();
        if($this->getClassName() === 'Contact') {
            $dataMember->id = (string)$connectObject->ID;
            $dataMember->type = $this->type;
            $relationship->setData($dataMember);
            return (object) array("contact" => $relationship->output());
        }
        if($this->getClassName() === 'IncidentThread') {
            $threads = array();
            foreach($connectObject as $index => $thread) {
                $dataMember = new \stdClass();
                $dataMember->id = (string)$thread->ID;
                $dataMember->type = $this->type;
                $threads[] = $dataMember;
            }
            $relationship->setData($threads);
            return (object) array("threads" => $relationship->output());
        }
        if($this->getClassName() === 'Product' || $this->getClassName() === 'Category') {
            $children = array();
            foreach($connectObject as $index => $child) {
                $dataMember = new \stdClass();
                $dataMember->id = (string)$child->ID;
                $dataMember->type = $this->type;
                $children[] = $dataMember;
            }
            $relationship->setData($children);
            return (object) array("children" => $relationship->output());
        }
    }

    /**
     * Generates {json-api} data collection for search results
     * @param array $result Contains result data
     * @param array $attributes Contains fields to be included in the response
     * @return array {json-api} data collection
     */
    public function createSearchDataCollection($result, $attributes) {
        $dataCollection = array();
        $searchResults = $result->results;
        for($index = 0; $index < count($searchResults); $index++) {
            $dataItem = new \RightNow\Internal\Api\Structure\Data();
            $dataItem->setType($this->type);
            $dataItem->setId((string)$searchResults[$index]->KFSearch->id);

            foreach ($attributes as $attribute) {
                $objectAttribute = $this->getMappedConnectAttributes('search')[$attribute];
                if(in_array($attribute, self::DATETIME_ATTRIBUTES) && $searchResults[$index]->$objectAttribute) {
                    $dataItem->setAttributes($attribute, date(DATE_W3C, $searchResults[$index]->$objectAttribute));
                    continue;
                }
                $dataItem->setAttributes($attribute, $searchResults[$index]->$objectAttribute);
            }
            $dataCollection[$index] = $dataItem->output();
        }
        return $dataCollection;
    }

    /**
     * Gets the class for current instance object
     * @return string Class name
     */
    public function getClassName() {
        $path = explode('\\', get_class($this));
        return array_pop($path);
    }

    /**
     * Returns connect object attributes
     * @param array $attributeGroup Group name of the attributes
     * @return array Array of Connect object attributes
     */
    public function getMappedConnectAttributes($attributeGroup = null) {
        switch ($this->getClassName()) {
            case 'Answer':
                if($attributeGroup === 'file')
                    $mappedAttributes = $this->attributeMapping['kf']['file'];
                else if($attributeGroup === 'list')
                    $mappedAttributes = $this->attributeMapping['kf']['answerList'];
                else if($attributeGroup === 'search')
                    $mappedAttributes = $this->attributeMapping['kf']['search'];
                else
                    $mappedAttributes = $this->attributeMapping['kf']['answer'];
                break;
            case 'Category':
                if($attributeGroup === 'list')
                    $mappedAttributes = $this->attributeMapping['osvc']['categoryList'];
                else
                    $mappedAttributes = $this->attributeMapping['osvc']['category'];
                break;
            case 'CustomField':
                $mappedAttributes = $this->attributeMapping['osvc']['customField'];
                break;
            case 'Incident':
                $mappedAttributes = $this->attributeMapping['osvc']['incident'];
                break;
            case 'IncidentThread':
                $mappedAttributes = $this->attributeMapping['osvc']['incidentThread'];
                break;
            case 'Product':
                if($attributeGroup === 'list')
                    $mappedAttributes = $this->attributeMapping['osvc']['productList'];
                else
                    $mappedAttributes = $this->attributeMapping['osvc']['product'];
                break;
        }
        return $mappedAttributes;
    }

    /**
     * Validates if the requested list of attributes are valid or not
     * @param array $attributes List of requested attributes
     * @param array $map Attribute map with actual DB fields
     * @return string Name of invalid attribute
     */
    public function validateAttributes($attributes, $map) {
        if (!$attributes) return false;
        foreach($attributes as $attribute) {
            if (!$map[$attribute]) {
                return $attribute;
            }
        }
    }
}
