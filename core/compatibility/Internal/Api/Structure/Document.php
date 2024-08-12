<?php
namespace RightNow\Internal\Api\Structure;

/**
 * Supports {json:api} specification for overall response
 */

class Document {

    private $data;
    private $errors;
    private $included = array();

    /**
     * Gets the data
     * @return object|array {json-api} data object or collection of data objects
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Gets the errors
     * @return array Array of {json-api} error objects
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Gets the includes
     * @return array Array of {json-api} includes
     */
    public function getIncluded() {
        return $this->included;
    }

    /**
     * Gets the meta data
     * @return object {json-api} meta object
     */
    public function getMeta() {
        return $this->meta;
    }

    /**
     * Sets the daya
     * @param object|array $data Single or collection of {json-api} data objects
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * Sets the errors
     * @param array $errors Array of {json-api} error objects
     */
    public function setErrors($errors) {
        $this->errors = $errors;
    }

    /**
     * Sets the includes
     * @param array $include Object/Array of {json-api} resources
     */
    public function setIncluded($include) {
        if(is_array($include)) {
            $this->included = array_merge($this->included, $include);
        }
        else {
            $this->included[] = $include;
        }
    }

    /**
     * Sets the meta data
     * @param object $meta Meta object
     */
    public function setMeta($meta) {
        $this->meta = $meta;
    }

    /**
     * Generates standard object excluding the instance NULL fields
     * @return \stdClass $output Standard object without the instance NULL fields
     */
    public function output() {
        $output = new \stdClass();
        if($this->errors) {
            $output->errors = $this->errors;
            return $output;
        }
        foreach ($this as $key => $value) {
            if(!empty($value) || $key === 'data') {
                $output->$key = $value;
            }
        }
        return $output;
    }
}
