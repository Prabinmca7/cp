<?php
namespace RightNow\Internal\Api\Structure;

/**
 * Supports {json:api} specification for resource relationship
 */
class Relationship {

    private $data;

    /**
     * Gets the data
     * @return object|array {json-api} data object for relationship
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Sets the data
     * @param object|array $data Single or collection of {json-api} relationship data objects
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * Generates standard object excluding the instance NULL fields
     * @return \stdClass $output Standard object without the instance NULL fields
     */
    public function output() {
        $output = new \stdClass();
        foreach ($this as $key => $value) {
            if($value !== null) {
                $output->$key = $value;
            }
        }
        return $output;
    }
}
