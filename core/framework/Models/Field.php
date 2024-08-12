<?php
namespace RightNow\Models;

use RightNow\Utils\Connect,
    RightNow\Utils\Framework,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Api;

/**
 * Model used to marshal contact and incident form data and direct it to the correct model for creating and updating of records.
 */
class Field extends Base {

    function __construct() {
        if (func_num_args() === 1)
            parent::__construct(func_get_arg(0));
        else
            parent::__construct();
    }

    /**
     * Handles password reset form submission for a non logged-in, pre-existing contact
     * by reading in an encrypted string containing the contact's id.
     * @param array|null $formData All submitted form data
     * @param string $resetCredentials Reversibly-encrypted contact_id/expire_time string
     * used to validate user in order to reset their password
     * @return array Details of the reset password transcation
     */
    public function resetPassword($formData, $resetCredentials) {
        if ($this->CI->input->post('f_tok') != "" && $tokenError = $this->verifyFormToken()) return $tokenError;

        if (!$resetCredentials) {
            return $this->getResponseObject(false, 'is_bool', Config::getMessage(REQUIRED_ENCRYPTED_STRING_LBL));
        }

        $resetCredentials = Api::ver_ske_decrypt($resetCredentials);
        // In the form 'c_id/exp_time'
        // And optionally /login/ (if creating a username for existing contact)
        // And optionally /email (if creating a new contact for a shared email address)
        $contactCredential = explode('/', $resetCredentials);
        if (!is_array($contactCredential) || !count(array_filter($contactCredential))) {
            return $this->getResponseObject(false, 'is_bool', Config::getMessage(ENCRYPTED_CREDENTIAL_STRING_MSG));
        }
        if(count($contactCredential) == 4){
            list($contactID, $expiration, $loginRequired, $email) = $contactCredential;
        }else if(count($contactCredential) == 3){
            list($contactID, $expiration, $loginRequired) = $contactCredential;
        }else if(count($contactCredential) == 2){
            list($contactID, $expiration) = $contactCredential;
        }else if(count($contactCredential) == 1){
            list($contactID) = $contactCredential;
        }
        
        if ((!isset($expiration) || !$expiration) || $expiration <= time()) {
            return $this->getResponseObject(false, 'is_bool', Config::getMessage(THE_GIVEN_CREDENTIALS_HAVE_EXPIRED_LBL));
        }

        $contactID = intval($contactID);
        $formData = $this->processFields($formData, $presentFields);

        // add form data element to indicate to contact model that duplicate emails are allowed
        // (if CFG_OPT_DUPLICATE_EMAIL is enabled) through the reset password/finish account creation process
        $formData['ResetPasswordProcess'] = (object) array('value' => true);

        $actions = array();
        if (Framework::isPta() && !Config::getConfig(PTA_IGNORE_CONTACT_PASSWORD)) {
            $actions['contact'] = Config::getMessage(SORRY_ALLOWED_UPDATE_PROFILE_MSG);
        }
        else {
            if ($contactID > 0) {
                // contact update
                $contact = $this->CI->model('Contact')->update($contactID, $formData);
                $actions['contactUpdated'] = true;
                if($contact && $contact->result) {
                    \RightNow\ActionCapture::record('contact', 'resetPassword');
                }
            }
            else {
                // contact create
                $formData['Contact.Emails.PRIMARY.Address'] = (object) array('value' => $email);
                $contact = $this->CI->model('Contact')->create($formData, true);
                $actions['contactCreated'] = true;
            }
            $actions['contact'] = $contact->result ?: $contact->errors;
        }

        return $this->getStatus($actions);
    }

    /**
     * Generic function to handle form submission. Processes form input and performs the following actions:
     *
     * * Contact creation: contact fields are present and the user's not logged in
     * * Contact update: contact fields are present and the user's logged in
     * * CommunityUser creation: communityuser fields are present and either the user's logged in or a contact creation occurs
     * * CommunityUser update: communityuser fields are present and the user's logged in
     * * Incident creation: incident fields are present and $updateID is null (question fields must not be present)
     * * Incident update: incident fields are present and $updateID is not null (question fields must not be present)
     * * Question creation: question fields are present and $updateID is null (incident fields must not be present)
     * * Question update: question fields are present and $updateID is not null (incident fields must not be present)
     *
     * Expected format of each field in the $formData array:
     *
     *      - value: (string|int) Field's value
     *      - name: (string) "Object.Field" (e.g. Contact.Login)
     *      - required: (boolean) Whether a value is required
     *
     * @param array|null $formData All submitted form data
     * @param array|null $listOfUpdateIDs List of IDs for the records we're updating. Keys should be ID type (i_id) and values should be the ID
     * @param boolean $smartAssistant True/false denoting if smart assistant should be run
     * @return array Details of the form submit action including any errors that might have occured
     */
    public function sendForm($formData, $listOfUpdateIDs = array(), $smartAssistant = false) {
        if ($tokenError = $this->verifyFormToken()) return $tokenError;

        try {
            setlocale(LC_COLLATE, 'C');
            setlocale(LC_CTYPE, 'C');
        }
        catch (Exception $e) {
            // do nothing
        }
        $userIsLoggedIn = Framework::isLoggedIn();
        $actions = array();
        $processActions = function($type, $action, $response) {
            return array(
                $type               => $response->result ?: $response->errors,
                "{$type}{$action}"  => ($response->errors) ? false : true,
            );
        };
        $formData = $this->processFields($formData, $presentFields);
        
        if(!Framework::ensurePasswordIsNotExpired() && is_null($formData['Contact.NewPassword'])){
            return $this->getResponseObject(null, null, Config::getMessage(PASSWORD_EXPIRED_PLEASE_SET_MSG));
        }

        if (!$formData || !$this->verifySupportedFieldsArePresent($presentFields)) {
            return $this->getResponseObject(null, null, Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG));
        }

        if (isset($formData['Incident.FileAttachments']->value) && count($formData['Incident.FileAttachments']->value) > 0) {
            if (!$this->verifyFileAttachments($formData['Incident.FileAttachments']->value)) {
                return $this->getResponseObject(null, null, Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG));
            }
        }

        if (isset($presentFields['contact']) && $presentFields['contact']) {
            //Contact update
            if ($userIsLoggedIn) {
                $return = $this->CI->model('Contact')->update($this->CI->session->getProfileData('contactID'), $formData);
                $action = 'Updated';
            }
            //Incident create email only (and can optionally contain other contact fields)
            else if(isset($formData['Contact.Emails.PRIMARY.Address']) && $formData['Contact.Emails.PRIMARY.Address'] && $formData['Contact.Emails.PRIMARY.Address']->value && isset($presentFields['incident'])){
                $existingContact = $this->CI->model('Contact')->lookupContactByEmail(
                    $formData['Contact.Emails.PRIMARY.Address']->value,
                    isset($formData['Contact.Name.First']) && $formData['Contact.Name.First'] ? $formData['Contact.Name.First']->value : null,
                    isset($formData['Contact.Name.Last']) && $formData['Contact.Name.Last'] ? $formData['Contact.Name.Last']->value : null
                )->result;
                if($existingContact){
                    $formData['Incident.PrimaryContact'] = $existingContact;
                }
                else{
                    $return = $this->CI->model('Contact')->create($formData, true);
                    if($return->result && $return->result->ID){
                        $formData['Incident.PrimaryContact'] = $return->result;
                    }
                    $action = 'Created';
                }
            }
            //Contact create
            else{
                $return = $this->CI->model('Contact')->create($formData, true);
                $action = 'Created';
            }
            if(isset($action) && $action){
                $actions += $processActions('contact', $action, $return);
            }
        }
         
        if (isset($presentFields['communityuser']) && $presentFields['communityuser'] && ((isset($actions['contact']) && is_object($actions['contact']) && property_exists($actions['contact'], 'ID') && $actions['contact']->ID) || (isset($userIsLoggedIn) && $userIsLoggedIn))) {
            $socialUserModel = $this->CI->model('CommunityUser');
            if (!isset($actions['contactCreated']) || !$actions['contactCreated']){
                $userID = isset($listOfUpdateIDs['user_id']) ? $listOfUpdateIDs['user_id'] : null;  
            }else{
                $userID = null;
            }
            if ($user = $socialUserModel->get($userID)->result) {
                $return = $socialUserModel->update($user->ID, $formData, (!$userID || $user->ID === $userID));
                $action = 'Updated';
            }
            else {
                $contact = isset($actions['contact']) ? $actions['contact'] : $this->CI->model('Contact')->get()->result;
                $formData['Communityuser.Contact'] = (object) array('value' => $contact->ID);
                $return = $socialUserModel->create($formData, true);
                $action = 'Created';
            }
            $actions += $processActions('communityUser', $action, $return);
        }

        // Either Incident or Question. There's (probably) not a valid use-case to support both at the same time.
        if (isset($presentFields['incident']) && (!isset($presentFields['communityquestion']) || !$presentFields['communityquestion'])) {
            if (isset($listOfUpdateIDs['i_id']) && $incidentIDToUpdate = $listOfUpdateIDs['i_id']) {
                $return = $this->CI->model('Incident')->update($incidentIDToUpdate, $formData);
                $action = 'Updated';
            }
            else {
                $return = $this->CI->model('Incident')->create($formData, $smartAssistant);
                $action = 'Created';
            }
            $actions += $processActions('incident', $action, $return);
        }
        else if (isset($presentFields['communityquestion']) && !array_key_exists('incident', $presentFields)) {
            if($rawFields = $this->getRawFormFields('Communityquestion.Body')) {
                $formData['Communityquestion.Body']->value = $rawFields->value;
            }

            if (($questionIDToUpdate = isset($listOfUpdateIDs['qid']) ? $listOfUpdateIDs['qid'] : null) !== null) {    
                $return = $this->CI->model('CommunityQuestion')->update($questionIDToUpdate, $formData);
                $action = 'Updated';
            }
            else {
                $return = $this->CI->model('CommunityQuestion')->create($formData, $smartAssistant);
                $action = 'Created';
            }
            $actions += $processActions('question', $action, $return);
        }

        if (isset($presentFields['asset']) && $presentFields['asset']) {
            $productID = (isset($listOfUpdateIDs['product_id'])) ? $listOfUpdateIDs['product_id'] : null;
            if ($assetIDToUpdate = $listOfUpdateIDs['asset_id']) {
                $serialNumber = (isset($listOfUpdateIDs['serial_no'])) ? $listOfUpdateIDs['serial_no'] : null;
                if($serialNumber !== null) {
                    $serialNumber = urldecode($serialNumber);
                }
                $return = $this->CI->model('Asset')->update($assetIDToUpdate, $formData, $serialNumber);
                $action = 'Updated';
            }
            else {
                $return = $this->CI->model('Asset')->create($productID, $formData);
                $action = 'Created';
            }
            $actions += $processActions('asset', $action, $return);
        }

        return $this->getStatus($actions);
    }

    /**
     * Warning: Returns unsanitized form data thus they are susceptible to xss attack
     * @param string $fieldName Name of the field of form
     * @return array|string|null Associative array whose keys are each field's name and values are the fields
     *         or only fields's value of the field name argument
     */
    public function getRawFormFields($fieldName = null) {
        static $rawFormFields = null;

        if($rawFormFields === null) {
            $originalFormData = \RightNow\Environment\unsanitizedPostVariable('form');

            if(!is_string($originalFormData)) {
                return null;
            }

            $rawFormFields = $this->processFields(json_decode($originalFormData));
        }

        return $fieldName ? $rawFormFields[$fieldName] : $rawFormFields;
    }

    /**
     * Processes form fields. Takes an array of fields and returns an associative array keyed by each field's name.
     * @param array $fields Form fields
     * @param array|null &$presentFields Pass by reference variable keyed by the name of the object that field(s) belong to;
     * so given:
     *
     *         [{name: 'Contact.Login', ...}, {name: 'Incident.Subject', ...}, {name: 'ArbitraryName', ...}]
     *
     * , this variable is be populated with:
     *
     *         ['contact' => true, 'incident' => true]
     *
     * @return array Associative array whose keys are each field's name and values are the fields
     * (each field's name property is unset)
     */
    private function processFields(array $fields, &$presentFields = array()) {
        $return = array();

        foreach ($fields as $field) {
            $fieldName = $field->name;

            if (!is_string($fieldName) || $fieldName === '') continue;

            unset($field->name);
            $return[$fieldName] = $field;

            if ($objectName = Text::cStrToLower(Text::getSubstringBefore($fieldName, '.'))) {
                $presentFields[$objectName] = true;
            }
        }

        return $return;
    }

    /**
     * Builds an array containing the status of the transaction.
     * @param array $actions May contain the following values:
     *  - contact: object Created/updated contact object
     *  - contactUpdated: boolean exists if the transaction was a contact update
     *  - contactCreated: boolean exists if the transaction was a contact create
     *  - incident: object Created/update incident object
     *  - incidentUpdated: boolean exists if the transaction was a incident update
     *  - incidentCreated: boolean exists if the transaction was a incident create
     *  - question: object Created/update question object
     *  - questionUpdated: boolean exists if the transaction was a question update
     *  - questionCreated: boolean exists if the transaction was a question create
     * @return array Status of the form submission; contains the following values:
     *  - transaction: (array) keyed by names of the objects involved with the transaction;
     *            values are arrays with a 'value' key; an optional 'key' key may be provided if
     *            it is intended that the key and value is to be added as a URL parameter on
     *            the success redirect page; e.g.
     *            [contact => [value: 1278], incident => [value: '123212-000002', key: 'refno']]
     *  - errors: (string) Error message for a failure (actually contained in ResponseObject)
     *  - redirectOverride: (String) Error page that the client should redirect to
     *  - sa: (array) Smart assistant results
     *  - newFormToken: (string) Exists if Smart assistant results are returned
     *                  and the transaction created and logged-in a new contact
     *  - sessionParam: (string) Generated session string
     */
    private function getStatus(array $actions) {
        $errors = array();
        $return = array(
            'sessionParam' => \RightNow\Utils\Url::sessionParameter(),
            'transaction' => array(),
        );

        if (isset($actions['contact']) && $contact = $actions['contact']) {
            if (is_object($contact) && $contact->ID) {
                $return['transaction']['contact'] = array('value' => $contact->ID);

                if (isset($actions['contactCreated']) && $contact->Login !== null && !$this->cookiesEnabled()) {
                    $return['redirectOverride'] = '/app/error/error_id/7';
                }
            }
            else {
                unset($return['transaction']);
                $errors = $contact;
            }
        }

        if (!$errors && isset($actions['incident']) && ($incident = $actions['incident'])) {
            if (is_object($incident) && $incident->ID) {
                $return['transaction']['incident'] = Framework::isLoggedIn()
                    ? array('key' => 'i_id', 'value' => $incident->ID)
                    : array('key' => 'refno', 'value' => $incident->ReferenceNumber);
                get_instance()->session->setFlashData('newlySubmittedIncidentRefNum', $incident->ReferenceNumber);
            }
            else if (is_array($incident)) {
                unset($return['transaction']);
                if ($actions['incidentCreated']) {
                    // Smart Assistant results
                    $return['sa'] = $incident;
                    //generate new token if SA results were returned. A new token is needed because old token is invalid
                    $return['newFormToken'] = Framework::createTokenWithExpiration(0, Framework::doesTokenRequireChallenge($this->CI->input->post('f_tok')));
                }
                else {
                    $errors += $incident;
                }
            }
        }
        else if (!$errors && isset($actions['question']) && ($question = $actions['question'])) {
            if (is_array($question) && is_array($question['suggestions'])) {
                unset($return['transaction']);
                $return['sa'] = $question;
                //generate new token if SA results were returned. A new token is needed because old token is invalid
                $return['newFormToken'] = Framework::createTokenWithExpiration(0, Framework::doesTokenRequireChallenge($this->CI->input->post('f_tok')));
            }
            else if (is_object($question) && $question->ID) {
                $return['transaction']['question'] = array('key' => 'qid', 'value' => $question->ID);

                // TK - add pre-form-response hook so that customizers can tie into the payload data returned and the flash data set.
                if(isset($actions['questionCreated']) && $actions['questionCreated']) {
                    $message = Config::getMessage(YOUR_QUESTION_HAS_BEEN_CREATED_LBL);
                    $userID = $this->CI->session->getProfileData('socialUserID');
                    if($this->CI->model('SocialSubscription')->getSubscriptionID($question->ID, $userID, 'Question')->result !== null || $this->CI->model('SocialSubscription')->getSubscriptionID($question->Product->ID, $userID, 'Product')->result !== null) {
                        $message .= "<br>" . Config::getMessage(RECEIVE_NOTIFICATION_SOMEONE_REPLIES_MSG);
                    }
                }
                else {
                    $message = Config::getMessage(YOUR_QUESTION_HAS_BEEN_UPDATED_LBL);
                }
                $this->CI->session->setFlashData('info', $message);
            }
            else {
                unset($return['transaction']);
                $errors += $question;
            }
        }
        else if (!$errors && (isset($actions['communityUser']) && $user = $actions['communityUser'])) {
            if (is_object($user) && $user->ID) {
                $return['transaction']['communityUser'] = array('value' => $user->ID);
            }
            else {
                unset($return['transaction']);
                $errors += $user;
            }
        }

        if (!$errors && (isset($actions['asset']) && $asset = $actions['asset'])) {
            if (is_object($asset) && $asset->ID) {
                $return['transaction']['asset'] = array('value' => $asset->ID);
            }
            else {
                unset($return['transaction']);
                $errors += $asset;
            }
        }

        if (!empty($errors)) {
            //send back new token incase of form errors for user to resubmit form
            $return['newFormToken'] = Framework::createTokenWithExpiration(0, Framework::doesTokenRequireChallenge($this->CI->input->post('f_tok')));
        }

        return $this->getResponseObject($return, 'is_array', $errors);
    }

    /**
     * Validates form token POST param to protect against XSRF.
     * @param  string     $tokenName Name of the token param; defaults to f_tok
     * @param  int|string $tokenSeed Seed value used to generate the token
     * @return array|null Array if the token is invalid or null if the token is valid
     */
    private function verifyFormToken($tokenName = 'f_tok', $tokenSeed = 0) {
        if (!Framework::isValidSecurityToken($this->CI->input->post($tokenName), $tokenSeed)) {
            return $this->getResponseObject(array('redirectOverride' => '/app/error/error_id/5', 'sessionParam' => \RightNow\Utils\Url::sessionParameter()), 'is_array',
                Config::getMessage(FORM_SUBMISSION_TOKEN_MATCH_EXP_MSG)
            );
        }
    }

    /**
     * Checks whether cookies are enabled.
     * @return boolean True whether cookies are enabled, false otherwise
     */
    private function cookiesEnabled() {
        return ($this->CI->session->canSetSessionCookies() && $this->CI->session->getSessionData('cookiesEnabled')) ||
            Framework::checkForTemporaryLoginCookie();
    }

    /**
     * Verifies that, of the fields present, at least one is a CP-supported read/write object.
     * @param  array $presentFields Keys are the fields present in the form
     * @return boolean True if there's at least one supported field present, False otherwise
     */
    private function verifySupportedFieldsArePresent(array $presentFields) {
        $getName = function($name) {
            return ucfirst(Text::cStrToLower($name));
        };
        if (array_intersect(array_map($getName, array_keys($presentFields)), array_keys(Connect::getSupportedObjects(), 'read,write'))) {
            return true;
        }
        Api::phpoutlog("The form did not contain any fields with names of the Connect objects for which we have read and write support. Fields present: " . var_export($presentFields, true));

        return false;
    }

    /**
     * Verifies that the attached files extensions and media types are allowed
     * @param array $fileAttachments Set of file attachments
     * @return boolean True if all attachment types are allowed, False otherwise
     */
    private function verifyFileAttachments($fileAttachments) {
        foreach ($fileAttachments as $fileAttachment) {
            if (!Framework::validateExtAndMimeType($fileAttachment->userName, Api::fattach_full_path($fileAttachment->localName))) {
                return false;
            }
        }
        return true;
    }
}
