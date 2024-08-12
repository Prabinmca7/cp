<?php
namespace RightNow\Internal\Libraries;

use RightNow\ActionCapture,
    RightNow\Api,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Internal\SiebelApi,
    RightNow\Libraries\Hooks,
    RightNow\Utils\Config,
    RightNow\Utils\Text;

/**
 * A simple class to handle submitting requests to Siebel
 */
final class SiebelRequest{
    /**
     * Current controller instance
     */
    private $CI = null;

    /**
     * Array of errors found during processing
     */
    private $errors = null;

    /**
     * Full URL to Siebel instance endpoint
     */
    private $siebelUrl;

    /**
     * SOAPAction to include in request
     */
    private $soapAction;

    /**
     * SOAP string to include before end user-supplied data
     */
    private $requestHeader;

    /**
     * SOAP string to include after end user-supplied data
     */
    private $requestFooter;

    /**
     * Array of key-value pairs of end user-supplied data that will become individual elements in SOAP request
     */
    private $siebelData;

    /**
     * Form data of end user-supplied data
     */
    private $formData;

    /**
     * Populated Connect Incident object
     */
    private $incident;

    /**
     * Creates a new SiebelRequest instance
     * @param array $siebelData Array of key-value pairs to send in the request to Siebel
     * @param array $formData Form fields
     * @param Connect\RNObject $incident Populated incident object
     */
    public function __construct(array $siebelData, array $formData, Connect\RNObject $incident) {
        $this->CI = get_instance();

        $siebelRequestParts = SiebelApi::generateRequestParts();

        $this->siebelUrl = $siebelRequestParts['siebelUrl'];
        $this->soapAction = $siebelRequestParts['soapAction'];
        $this->requestHeader = $siebelRequestParts['requestHeader'];
        $this->requestFooter = $siebelRequestParts['requestFooter'];

        $this->siebelData = $siebelData;
        $this->formData = $formData;
        $this->incident = $incident;
    }

    /**
     * Submits a request to a Siebel instance.
     */
    public function makeRequest() {
        // call hook to allow customers to modify data before sending it to Siebel
        $hookData = array('siebelUrl' => $this->siebelUrl, 'soapAction' => $this->soapAction, 'requestHeader' => $this->requestHeader, 'requestFooter' => $this->requestFooter,
            'formData' => $this->formData, 'incident' => $this->incident, 'siebelData' => $this->siebelData);
        Hooks::callHook('pre_siebel_incident_submit', $hookData);
        $this->resetData($hookData);

        $this->makeSiebelRequest();
    }

    /**
     * Returns any errors that were set
     * @return array|null Array of errors, if any
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Resets data on 'this' object. Used after calling a hook in case the hook changed any data.
     * @param array $data Array of key-value pairs
     */
    private function resetData(array $data) {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Sends an incident to a Siebel server and processes any errors that may result.
     */
    private function makeSiebelRequest() {
        $genericErrorMessage = Config::getMessage(SORRY_PROCESSING_SUBMISSION_PLS_MSG);
        $sessionID = $this->CI->session->getSessionData('sessionID');
        $contactID = $this->incident->PrimaryContact->ID;

        try {
            $makeRequestResults = SiebelApi::makeRequest($this->siebelData, $this->siebelUrl, $this->soapAction, $this->requestHeader, $this->requestFooter);
            $success = $makeRequestResults['success'];
            $response = $makeRequestResults['response'];
            $requestBody = $makeRequestResults['requestBody'];
            $requestErrorNumber = $makeRequestResults['requestErrorNumber'];
            $requestErrorMessage = $makeRequestResults['requestErrorMessage'];
            $responseInfo = $makeRequestResults['responseInfo'];
        }
        catch (\Exception $e) {
            $errors = array('Error' => $e->getMessage());
            $errorMessage = $this->outputSiebelErrors($errors, $sessionID, $contactID);
            $this->errors = array($genericErrorMessage . (IS_DEVELOPMENT ? " ($errorMessage)" : ''));
            return;
        }

        if ($success)
            return;

        // if there is an error, call a hook to allow customers to override the generic error message and possibly perform their own logging
        $errors = ($requestErrorNumber || $requestErrorMessage) ? array('RequestErrorNumber' => $requestErrorNumber, 'RequestErrorMessage' => $requestErrorMessage) : array();
        $errors['HTTP_CODE'] = $responseInfo['http_code'];
        $errors['Response'] = $response === false ? 'false' : Text::escapeHtml($response);
        $errors['RequestBody'] = Text::escapeHtml($requestBody);
        $errorMessage = $this->outputSiebelErrors($errors, $sessionID, $contactID);
        $hookData = array('errors' => array($genericErrorMessage . (IS_DEVELOPMENT ? " ($errorMessage)" : '')),
            'requestErrorNumber' => $requestErrorNumber, 'requestErrorMessage' => $requestErrorMessage, 'responseInfo' => $responseInfo,
            'requestBody' => $requestBody, 'incident' => $this->incident);
        if (is_string($hookError = Hooks::callHook('post_siebel_incident_error', $hookData))) {
            $this->errors = array($hookError);
        }
        else {
            $this->errors = $hookData['errors'];
        }
    }

    /**
     * Outputs error information to ACS, clickstreams, and phpoutlog.
     *
     * @param array $errors Array of key-value pairs to include in the error message
     * @param string $sessionID Session identifier
     * @param int $contactID ContactID associated to the incident submission
     * @return string Computed error message based in $errors parameter
     */
    private function outputSiebelErrors(array $errors, $sessionID, $contactID) {
        $errorMessage = $this->createErrorMessage($errors);
        ActionCapture::instrument('siebel', 'error', \RightNow\InstrumentationLevel::ERROR, $errors);
        $this->CI->model('Clickstream')->insertAction($sessionID, $contactID, CS_APP_EU,
            'siebel_integration_error', $errorMessage, '', '');
        Api::phpoutlog($errorMessage);
        return $errorMessage;
    }

    /**
     * Takes an array of key-values pairs and returns a string containg 'key: value' entries, separated by commas.
     *
     * @param array $errors Array of key-value pairs to include in the error message
     * @return string Comma-separated 'key: value' string
     */
    private function createErrorMessage(array $errors) {
        array_walk($errors, function(&$value, $key) {
            $value = "$key: $value";
        });
        return implode(", ", $errors);
    }
}
