<?php
namespace RightNow\Internal\Api\Structure;

/**
 * Supports {json:api} specification for result data
 */
class Data {

    private $attributes = array();
    private $id;
    private $type;
    private $relationships = array();

    /**
     * Gets the associated resource attributes
     * @return array Attributes
     */
    public function getAttributes(){
        return $this->attributes;
    }

    /**
     * Gets the associated resource id
     * @return int Resource Id
     */
    public function getId(){
        return $this->id;
    }

    /**
     * Gets the associated resource type
     * @return string resource type
     */
    public function getType(){
        return $this->type;
    }

    /**
     * Sets attributes of the result object
     * @param string $key Attribute key
     * @param string $value Attribute value
     */
    public function setAttributes($key, $value){
        $this->attributes[$key] = $value;
    }

    /**
     * Sets the associated resource id
     * @param string $id Resource id to set
     */
    public function setId($id){
        $this->id = $id;
    }

    /**
     * Sets the associated resource type
     * @param string $type Resource type to set
     */
    public function setType($type){
        $this->type = $type;
    }

    /**
     * Sets the relationships with other resource objects
     * @param object $relationships Relationships to set
     */
    public function setRelationships($relationships){
        $this->relationships = (object) array_merge((array) $this->relationships, (array) $relationships);
    }

    /**
     * Generates standard object excluding the instance NULL fields
     * @return \stdClass $output Standard object without the instance NULL fields
     */
    public function output() {
        $output = new \stdClass();
        foreach ($this as $key => $value) {
            if(!empty($value)) {
                $output->$key = $value;
            }
        }
        return $output;
    }
}
