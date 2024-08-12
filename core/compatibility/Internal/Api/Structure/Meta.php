<?php
namespace RightNow\Internal\Api\Structure;

/**
 * Supports {json:api} specification for meta data
 */
class Meta {

    private $limit;
    private $offset;
    private $totalResults;

    /**
     * Gets the limit on no. of results to be returned
     * @return int limit
     */
    public function getLimit(){
        return $this->limit;
    }

    /**
     * Gets the current offset
     * @return int offset
     */
    public function getOffset(){
        return $this->offset;
    }

    /**
     * Gets the total no. of results
     * @return int total results returned
     */
    public function getTotalResults(){
        return $this->totalResults;
    }

    /**
     * Sets the limit on no. of results to be returned
     * @param int $limit Limit value
     */
    public function setLimit($limit){
        $this->limit = $limit;
    }

    /**
     * Sets the current offset
     * @param int $offset Current offset of result set
     */
    public function setOffset($offset){
        $this->offset = $offset;
    }

    /**
     * Sets the total no. of results returned
     * @param int $totalResults Total no. of results
     */
    public function setTotalResults($totalResults){
        $this->totalResults = $totalResults;
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
