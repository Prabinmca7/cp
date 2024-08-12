<?php
namespace RightNow\Libraries;

/**
 * Standard response object returned by models, ajax requests and possibly other entities.
 *
 * @property mixed $result The actual value being returned from the method call
 * @property array $errors List of errors that occured during the method call
 * @property mixed $error The first error in the errors array
 * @property array $warnings List of warnings that occured during the method call
 * @property mixed $warning The first warning in the warnings array
 */
class ResponseObject {
    private $result = null;
    private $validationFunction = null;
    private $errors = array();
    private $warnings = array();
    private $published = null;
    private $dateModified = null;
    private $version = null;
    private $title = null;
    private $documentId = null;
    private $owner = null;
    private $answerId = null;
    private $xml = null;
    private $contentType = null;
    private $resourcePath = null;
    private $locale = null;
    private $categories = null;
    private $versionId = null;
    private $lastModifier = null;
    private $lastModifiedDate = null;
    private $creator = null;
    private $userGroups = null;
    private $recordId = null;
    private $items = null;
    private $hasMore = false;
    private $ratingType = null;
    private $questions = null;
    private $subscriptionSchedule = null;
    private $session = null;
    private $transactionId = null;
    private $priorTransactionId = null;
    private $query = null;
    private $results = null;
    private $resultLocales = null;

    public $action;

    /**
     * Constructs a ResponseObject
     * @param \Closure|null $validationFunction A callable function that takes the return value as its sole argument and returns
     * true upon success. If specified as null, no validation is performed.
     */
    public function __construct($validationFunction = 'is_object') {
        $this->validationFunction = $validationFunction;
    }

    /**
     * Retrieve private attributes.
     * @param string $name Property name
     * @return mixed Value of property
     * @internal
     */
    public function __get($name) {
        if($name === 'error'){
            return isset($this->errors[0]) ? $this->errors[0] : null;
        }
        if($name === 'warning'){
            return $this->warnings[0];
        }
        return $this->$name;
    }

    /**
     * Set private attributes.
     * @param string $name Property name
     * @param mixed $value Property value
     * @return void
     * @throws \Exception If property being set is private
     * @internal
     */
    public function __set($name, $value) {
        switch ($name) {
            case 'result';
                $this->validateReturnValue($value);
                $this->result = $value;
                break;
            case 'error':
                $this->addError($value);
                break;
            case 'warning':
                $this->warnings[] = (string) $value;
                break;
            case 'errors':
            case 'warnings':
                throw new \Exception("Cannot set private attribute: $name");
            default:
                $this->$name = $value;
        }
    }

    /**
     * Return the isset value.
     * @param string $name Property name
     * @return boolean
     */
    public function __isset($name) {
        if($name === 'error'){
            return isset($this->errors[0]);
        }
        if($name === 'warning'){
            return isset($this->warnings[0]);
        }
        return isset($name);
    }       

    /**
     * Return the return value as a human readable string.
     * Prepends error messages if present.
     * @return string Return value of ResponseObject, with errors prepended.
     */
    public function __toString() {
        $string = ($this->errors) ? "ERROR: {$this->errors}" : '';
        return $string . json_encode($this->result, JSON_PRETTY_PRINT);
    }

    /**
     * Return JSON encoded class properties array.
     *
     * @param array $connectFields A list of Connect field names to be referenced so they will be populated in the JSON array.
     *     Example: array('UpdatedTime', 'Address.Country.LookupName').
     * @param boolean $indicateIsResponseObject Adds a 'isResponseObject' attribute to the returned JSON when true, useful
     *     for telling client code that returned content is a response object.
     * @return string JSON encoded content
     */
    public function toJson(array $connectFields = array(), $indicateIsResponseObject = false) {
        $properties = get_object_vars($this);
        $errors = $properties['errors'];
        if(count($errors) > 0){
            $properties['errors'] = array();
            //Replace each error object with it's 'toString' message.
            foreach ($errors as $error) {
                $properties['errors'][] = $error->toArray();
            }
        }
        else{
            unset($properties['errors']);
        }
        //Remove warnings index if there are no warnings
        if(count($properties['warnings']) === 0){
            unset($properties['warnings']);
        }
        //Remove the validation function as it serves no purpose
        unset($properties['validationFunction']);
        // Reference any specified connect fields in order to populate them.
        if ($connectFields && ($connectObject = $properties['result']) && gettype($connectObject) === 'object') {
            \RightNow\Utils\Connect::populateFieldValues($connectFields, $connectObject);
        }
        if($indicateIsResponseObject) {
            $properties['isResponseObject'] = true;
        }
        return json_encode($properties);
    }

    /**
     * Ensures that the result validates against the provided validation function to ensure that the result is formatted correctly.
     * @param mixed $value Value of ResponseObject
     * @return void
     * @throws \Exception If the result doesn't conform to the validation function
     */
    private function validateReturnValue($value) {
        if ($validationFunction = $this->validationFunction) {
            try {
                if (($returnValue = $validationFunction($value)) !== true) {
                    throw new \Exception(is_string($returnValue) ? $returnValue : 'Validation returned: ' . var_export($returnValue, true));
                }
            }
            catch (\Exception $e) {
                $this->addError($e->getMessage());
            }
        }
    }

    /**
     * Adds an error to the current ResponseObject
     *
     * @param ResponseError|string|array $error Error to add
     * @return void
     * @throws \Exception if error isn't a string or a ResponseError
     */
    private function addError($error) {
        if (is_string($error)) {
            $this->errors[] = new ResponseError($error);
        }
        else if (is_array($error) && array_key_exists('externalMessage', $error))  {
            $errorExternalMessage = isset($error['externalMessage']) ? $error['externalMessage'] : null;
            $errorExternalErrorCode = isset($error['errorCode']) ? $error['errorCode'] : null;
            $errorSource = isset($error['source']) ? $error['source'] : null;
            $errorInternalMessage = isset($error['internalMessage']) ? $error['internalMessage'] : null;
            $errorExtraDetails = isset($error['extraDetails']) ? $error['extraDetails'] : null;
            $errorDisplayToUser = isset($error['displayToUser']) ? $error['displayToUser'] : null;
            $this->errors[] = new ResponseError($errorExternalMessage, $errorExternalErrorCode, $errorSource, $errorInternalMessage, $errorExtraDetails, $errorDisplayToUser);
        }
        else if ($error instanceof ResponseError)  {
            $this->errors[] = $error;
        }
        else {
            throw new \Exception('Errors must either be a string, an associative array with a "externalMessage" key, or a ResponseError object.');
        }
    }
}

/**
 * ResponseError object used by ResponseObject.
 */
class ResponseError {
    public $externalMessage;
    public $errorCode;
    private $source;
    private $internalMessage;
    private $extraDetails;
    private $displayToUser;

    /**
     * ResponseError constructor
     *
     * @param string $externalMessage Error message to display to end-users/customers.
     * @param string $errorCode A define-like error code that can be used by the caller to determine how to handle the error.
     * @param string $source Defines where the error came from (e.g. 'models/standard/report').
     * @param string $internalMessage Error message used by developers.
     * @param mixed $extraDetails Any extra details to attach to the error, such as a stack trace, for example.
     * @param boolean $displayToUser Indicates if externalMessage should be shown to the end user, as opposed to a generic error.
     */
    public function __construct($externalMessage, $errorCode = null, $source = null, $internalMessage = null, $extraDetails = null, $displayToUser = false) {
        $this->externalMessage = $externalMessage;
        $this->errorCode = $errorCode;
        $this->source = $source;
        $this->internalMessage = $internalMessage;
        $this->extraDetails = $extraDetails;
        $this->displayToUser = $displayToUser;
    }

    /**
     * Returns the externalMessage of the ResponseError
     * @return string The error message
     */
    public function __toString() {
        return $this->externalMessage ?: '';
    }

    /**
     * Returns an array represenation of the ResponseError object, with falsey keys removed,
     *  presumably to be converted to JSON in ResponseObject.
     * @return array Array of key => value pairs representing object attributes.
     *               Falsey values are unset.
     */
    public function toArray() {
        return array_filter(get_object_vars($this));
    }
     
    /**
     * Returns the errorCode of the ResponseError
     * @return string The error code
     */
    public function getErrorCode(){
        return $this->errorCode;
    }

    /**
     * Returns the source of the ResponseError
     * @return string The error code
     */
    public function getSource(){
        return $this->source;
    }

}
