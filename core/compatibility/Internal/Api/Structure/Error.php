<?php
namespace RightNow\Internal\Api\Structure;

/**
 * Supports {json:api} specification for errors
 */

class Error {

    private $detail;
    private $status;

    /**
     * Gets the error message
     * @return string Error detail message
     */
    public function getDetail(){
        return $this->detail;
    }

    /**
     * Gets the error status
     * @return string Htto error code
     */
    public function getStatus(){
        return $this->status;
    }

    /**
     * Sets the error message
     * @param string $detail Error detail message
     */
    public function setDetail($detail){
        $this->detail = $detail;
    }

    /**
     * Sets the error status code
     * @param string $status Http error code
     */
    public function setStatus($status){
        $this->status = $status;
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

