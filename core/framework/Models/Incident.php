<?php

namespace RightNow\Models;

use RightNow\Connect\v1_4 as Connect,
    RightNow\Connect\v1_2 as ConnectV2,
    RightNow\Connect\Knowledge\v1 as KnowledgeFoundation,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Api,
    RightNow\Internal\Sql\Incident as Sql,
    RightNow\Libraries\Hooks,
    RightNow\Utils\Framework,
    RightNow\Utils\Text,
    RightNow\ActionCapture,
    RightNow\Utils\Config,
    RightNow\Libraries\AbuseDetection,
    RightNow\Utils\Date as DateUtil;

/**
 * Methods for handling the retrieval, creation, and updating of incidents.
 */
class Incident extends PrimaryObjectBase {
    /**
     * Returns an empty incident structure. Used to be able to access incident fields without having an incident ID.
     *
     * @return Connect\Incident An instance of the Connect incident object
     */
    public function getBlank() {
        $incident = parent::getBlank();
        ConnectUtil::setCustomFieldDefaults($incident);
        return $this->getResponseObject($incident);
    }

    /**
     * Returns a Connect Incident object for the specified incident ID.
     *
     * @param int $incidentID The ID of the incident
     * @return Connect\Incident|null Incident object on success, else null.
     */
    public function get($incidentID) {
        if(!Framework::isLoggedIn()) {
            return $this->getResponseObject(null, null, Config::getMessage(SESSION_EXP_PLEASE_LOGIN_CONTINUE_MSG));
        }

        $incident = parent::get($incidentID);
        if(!is_object($incident)){
            return $this->getResponseObject(null, null, $incident);
        }
        if(!$this->isContactAllowedToReadIncident($incident)) {
            return $this->getResponseObject(null, null, Config::getMessage(ACCESS_DENIED_LBL));
        }
        return $this->getResponseObject($incident);
    }

    /**
     * Creates an incident. In order to create an incident, a contact must be logged-in or there must be sufficient
     * contact information in the supplied form data. Form data is expected to look like
     *
     *      -Keys are Field names (e.g. Incident.Subject)
     *      -Values are objects with the following members:
     *          -value: (string) value to save for the field
     *          -required: (boolean) Whether the field is required
     *
     * @param array $formData Form fields to update the incident with In order to be created successfully, either a contact
     * must be logged in or this array must contain a 'Incident.PrimaryContact' key which must be either the ID of the
     * contact, or a instance of a Connect Contact class.
     * @param boolean $smartAssist Denotes whether smart assistant should be run
     * @return Connect\Incident|array|null Created incident object, array of SmartAssistant data, or null if there are error messages and the incident wasn't created
     */
    public function create(array $formData, $smartAssist = false) {
        if(!Framework::isLoggedIn() && Framework::isUnauthenticatedIncidentCreateDisabled()) {
            return $this->getResponseObject(null, null, Config::getMessage(ERROR_REQUEST_ACTION_COMPLETED_MSG));
        }

        $incident = $this->getBlank()->result;

        if ($contact = $this->getContact()) {
            $incident->PrimaryContact = $contact;
        }
        else if($formData['Incident.PrimaryContact']){
            if($formData['Incident.PrimaryContact'] instanceof Connect\Contact){
                $incident->PrimaryContact = $formData['Incident.PrimaryContact'];
            }
            else if((is_int($formData['Incident.PrimaryContact']) || ctype_digit($formData['Incident.PrimaryContact']))
                && ($contactAssociatedToIncident = $this->getContact($formData['Incident.PrimaryContact']))) {
                    $incident->PrimaryContact = $contactAssociatedToIncident;
                    $incident->ResponseEmailAddressType->ID = $this->lookupEmailPriority($formData['Contact.Emails.PRIMARY.Address']->value);
            }
        }
        unset($formData['Incident.PrimaryContact']);
        if(!$incident->PrimaryContact){
            return $this->getResponseObject(null, null, Config::getMessage(QUESTION_SUBMITTED_PLEASE_LOG_TRY_MSG));
        }
        if($incident->PrimaryContact->Disabled) {
            // Disabled contacts can't create incidents
            return $this->getResponseObject(null, null, Config::getMessage(SORRY_THERES_ACCT_PLS_CONT_SUPPORT_MSG));
        }
        $incident->Organization = $incident->PrimaryContact->Organization;
        $formData = $this->autoFillSubject($formData);

        $errors = $warnings = $smartAssistantData = array();
        foreach ($formData as $name => $field) {
            if(!\RightNow\Utils\Text::beginsWith($name, 'Incident')){
                continue;
            }
            $fieldName = explode('.', $name);
            try {
                //Get the metadata about the field we're trying to set. In order to do that we have to
                //populate some of the sub-objects on the record. We don't want to touch the existing
                //record at all, so instead we'll just pass in a dummy instance.
                list(, $fieldMetaData) = ConnectUtil::getObjectField($fieldName, $this->getBlank()->result);
            }
            catch (\Exception $e) {
                $warnings []= $e->getMessage();
                continue;
            }
            if (\RightNow\Utils\Validation::validate($field, $name, $fieldMetaData, $errors)) {
                $field->value = ConnectUtil::checkAndStripMask($name, $field->value, $fieldMetaData);
                $field->value = ConnectUtil::castValue($field->value, $fieldMetaData);
                if($setFieldError = $this->setFieldValue($incident, $name, $field->value, $fieldMetaData->COM_type)) {
                    $errors[] = $setFieldError;
                }
            }
            if ($smartAssist === true && ($field->value !== null && $field->value !== '')) {
                //For menu-type custom fields, we have the key for the value they selected (instead of the value). The KFAPI expects us to
                //denote that by adding a .ID onto the end of the name in the key/value pair list.
                if ((isset($fieldMetaData) && $fieldMetaData->is_menu) || (in_array($fieldMetaData->COM_type, array('NamedIDLabel', 'NamedIDOptList', 'ServiceProduct', 'ServiceCategory', 'Asset')))) {
                    $name .= ".ID";
                }
                $smartAssistantData[$name] = $field->value;
            }
        }
        if ($errors) {
            return $this->getResponseObject(null, null, $errors);
        }

        if ($smartAssist === true) {
            // OKCS API Hook
            $hookData = array('formData' => $formData, 'token' => $this->getKnowledgeApiSessionToken(), 'canEscalate' => true, 'apiInvoked' => false);

            // if OKCS API is invoked and has no error, return the data to SA Dialog
            if ((!is_string($hookError = Hooks::callHook('pre_retrieve_smart_assistant_answers', $hookData))) && $hookData['apiInvoked']) {
                if (is_array($hookData['suggestions']))
                    return $this->getResponseObject($hookData, 'is_array');
            }
            else {
                Api::phpoutlog($hookError);
            }

            //run KFAPI if the OKCS API wasn't invoked at all
            Connect\ConnectAPI::setSource(SRC2_EU_SMART_ASST);
            list($smartAssistantIncidentContent, $additionalFields) = $this->convertFormDataToSmartAssistantSearch($smartAssistantData, $incident->PrimaryContact);
            $smartAssistantResults = $this->getSmartAssistantResults($smartAssistantIncidentContent, $additionalFields);
            Connect\ConnectAPI::releaseSource(SRC2_EU_SMART_ASST);
            //Return a response to the SA dialog if either there are results to display or we tried to run rules, but couldn't find anything
            if(is_array($smartAssistantResults) && is_array($smartAssistantResults['suggestions']) && (count($smartAssistantResults['suggestions']) || (isset($smartAssistantResults['rulesMatched']) && $smartAssistantResults['rulesMatched']))){
                unset($smartAssistantResults['rulesMatched']);
                //bonus hit is needed on AAQ page with captcha verified when smart assistant popup appears. 
                if(Framework::doesTokenRequireChallenge($this->CI->input->post('f_tok'))){
                     AbuseDetection::giveBonusHit();
                }
                return $this->getResponseObject($smartAssistantResults, 'is_array');
            }
        }

        try{
            $hookData = array('formData' => $formData, 'incident' => $incident, 'shouldSave' => true);
            // if the hook returns a string, override the $incident variable with whatever is set in $hookData
            if (is_string($hookError = Hooks::callHook('pre_incident_create_save', $hookData))){
                $incident = $hookError;
            }
            else{
                $incident = $hookData['incident'];
                if ($hookData['shouldSave']){
                    $incident = parent::createObject($incident, SRC2_EU_AAQ);
                }
            }
        }
        catch(\Exception $e){
            $incident = $this->getSaveErrors($e);
        }
        if(!is_object($incident)){
            return $this->getResponseObject(null, null, $incident);
        }

        if($smartAssist === 'false' || $smartAssist === false){
            ActionCapture::record('incident', 'notDeflected');
        }
        //Always register SA results in order to get proper clickstream entries
        try{
            $smartAssistantToken = $this->CI->input->post('saToken') ?: null;
            if(!$smartAssistantToken && isset($smartAssistantResults) && is_array($smartAssistantResults) && isset($smartAssistantResults['token']) && $smartAssistantResults['token']){
                $smartAssistantToken = $smartAssistantResults['token'];
            }
            $resolution = new KnowledgeFoundation\SmartAssistantResolution();
            $resolution->ID = 3; //KF_API_SA_RESOLUTION_TYPE_ESCALATED
            $hookData = array('knowledgeApiSessionToken' => $this->getKnowledgeApiSessionToken(), 'smartAssistantToken' => $smartAssistantToken,
                'resolution' => $resolution, 'incident' => $incident, 'shouldRegister' => true);
            // don't call #RegisterSmartAssistantResolution if a string is returned by the hook; the callee is assumed to have taken care of it
            Hooks::callHook('pre_register_smart_assistant_resolution', $hookData);
            if ($hookData['shouldRegister']){
                KnowledgeFoundation\Knowledge::RegisterSmartAssistantResolution($this->getKnowledgeApiSessionToken(), $smartAssistantToken, $resolution, $incident);
            }
        }
        catch(\Exception $e){
            //No reason to loudly fail if we can't register the SA resolution for some reason
        }

        if (Framework::isLoggedIn() && !$this->CI->session->getProfileData('disabled')) {
            $this->regenerateProfile(
                $this->CI->session->getProfile(true),
                array('openLoginUsed', 'socialUserID')
            );
        }

        return $this->getResponseObject($incident, 'is_object', null, $warnings);
    }

    /**
     * Updates the specified incident with the given form data. Form data is expected to look like
     *
     *      -Keys are Field names (e.g. Incident.Subject)
     *      -Values are objects with the following members:
     *          -value: (string) value to save for the field
     *          -required: (boolean) Whether the field is required
     *
     * @param int $incidentID ID of the incident to update
     * @param array $formData Form fields to update the incident with
     * @return Connect\Incident|null Updated incident object containing the incident or error messages if the incident wasn't updated
     */
    public function update($incidentID, array $formData) {
        $incident = $this->get($incidentID);
        if (!$incident->result) {
            // Error: return the ResponseObject
            return $incident;
        }
        $incident = $incident->result;

        $errors = $warnings = array();

        $requireThread = true;
        $statusField = 'Incident.StatusWithType.Status';
        //Incident threads are always required with the only exception being if the user is closing out
        //their incident. Also change the value of the status field to match the correct define
        if(array_key_exists($statusField, $formData) && intval($formData[$statusField]->value) > 0){
            $formData[$statusField]->value = STATUS_SOLVED;
            $requireThread = false;
        }
        else {
            // If the status field's value isn't supplied but the incident's being
            // updated then set the status to 'updated', since it can currently
            // be set to 'solved'.
            $formData[$statusField] || ($formData[$statusField] = (object) array());
            $formData[$statusField]->value = STATUS_UPDATED;
        }
        if(array_key_exists('Incident.Threads', $formData)){
            $formData['Incident.Threads']->required = $requireThread;
        }

        foreach ($formData as $name => $field) {
            if(!\RightNow\Utils\Text::beginsWith($name, 'Incident')){
                continue;
            }
            $fieldName = explode('.', $name);
            try {
                //Get the metadata about the field we're trying to set. In order to do that we have to
                //populate some of the sub-objects on the record. We don't want to touch the existing
                //record at all, so instead we'll just pass in a dummy instance.
                list(, $fieldMetaData) = ConnectUtil::getObjectField($fieldName, $this->getBlank()->result);
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                $warnings []= $e->getMessage();
                continue;
            }

            if (\RightNow\Utils\Validation::validate($field, $name, $fieldMetaData, $errors)) {
                $field->value = ConnectUtil::checkAndStripMask($name, $field->value, $fieldMetaData);
                $field->value = ConnectUtil::castValue($field->value, $fieldMetaData);
                if($setFieldError = $this->setFieldValue($incident, $name, $field->value, $fieldMetaData->COM_type)) {
                    $errors[] = $setFieldError;
                }
            }
        }
        if ($errors) {
            return $this->getResponseObject(null, null, $errors);
        }

        try{
            $incident = parent::updateObject($incident, SRC2_EU_MYSTUFF_Q);
        }
        catch(\Exception $e){
            $incident = $this->getSaveErrors($e);
        }
        if(!is_object($incident)){
            return $this->getResponseObject(null, null, $incident);
        }
        return $this->getResponseObject($incident, 'is_object', null, $warnings);
    }

    /**
     * Creates a new incident when a user submits either answer or site feedback
     * @param int $answerID Answer ID of which feedback was given
     * @param int $rate Rating of feedback ([1-2] for no/yes, [1-N] for rating)
     * @param int $threshold Threshold required for feedback to be submitted
     * @param string $name Name of user giving feedback
     * @param string $message Message given with feedback
     * @param string|null $givenEmail Email address given in feedback
     * @param int|null $numberOfOptions Number of options for the rating
     * @return Connect\Incident|null Created incident feedback or null if an error was encountered
     */
    public function submitFeedback($answerID, $rate, $threshold, $name, $message, $givenEmail, $numberOfOptions = null) {
        if (($givenEmail !== null && $givenEmail !== false && $givenEmail !== '') && !Text::isValidEmailAddress($givenEmail)) {
            return $this->getResponseObject(null, null, Config::getMessage(EMAIL_ADDRESS_PROVIDED_IS_INVALID_MSG));
        }

        $numberOfOptions = intval($numberOfOptions);
        $rate = intval($rate);
        if (!$threshold)
            $threshold = 100;

        //If an email is provided, store it off for usability, otherwise use a placeholder email
        if($givenEmail) {
            $this->CI->session->setSessionData(array('previouslySeenEmail' => $givenEmail));
        }
        else {
            $givenEmail = 'unknown@mail.null';
        }

        if ($rate > $threshold) {
            return $this->getResponseObject(true, 'is_bool', null, 'No incident was created because submitted rating was greater than the incident rating threshold');
        }
        $incident = $this->getBlank()->result;

        if(!is_null($answerID)) {
            $url = \RightNow\Utils\Url::getShortEufAppUrl('sameAsCurrentPage', Config::getConfig(CP_ANSWERS_DETAIL_URL) . "/a_id/$answerID");
            $this->createThreadEntry($incident, Config::getMessage(THIS_FEEDBK_ABOUT_MSG) . ":\n$url\n\n$message");
            $incident->Subject = $this->getAnswerFeedbackSubject($answerID, $numberOfOptions, $rate);
            $source = SRC2_EU_FB_ANS;
        }
        else {
            $incident->Subject = Config::getMessage(SITE_FEEDBACK_HDG);
            $this->createThreadEntry($incident, $message);
            $source = SRC2_EU_FB_SITE;
        }

        if (Framework::isLoggedIn()) {
            $contactID = $this->CI->session->getProfileData('contactID');
            $organizationID = $this->CI->session->getProfileData('orgID');
        }
        else if($contactDetails = $this->CI->model('Contact')->lookupContactAndOrgIdByEmail(strtolower($givenEmail))->result) {
            $contactID = $contactDetails[0];
            $organizationID = $contactDetails[1];
            $incident->ResponseEmailAddressType = $this->lookupEmailPriority(strtolower($givenEmail));
        }

        //Check if we need to do a contact create
        if (isset($contactID) && $contactID) {
            $contact = $this->getContact($contactID);
        }
        else {
            $contact = $this->CI->model('Contact')->getBlank()->result;
            $email = $contact->Emails[] = new Connect\Email();
            $email->Address = strtolower($givenEmail);
            $email->AddressType->ID = CONNECT_EMAIL_PRIMARY;
            if (!is_null($name)) {
                $contact->Name->First = $name;
            }

            $preHookData = array('data' => $contact);
            if (is_string($customHookError = Hooks::callHook('pre_contact_create', $preHookData))) {
                return $this->getResponseObject(null, null, $customHookError);
            }

            if ($abuseMessage = $this->isAbuse()) {
                return $this->getResponseObject(null, null, $abuseMessage);
            }

            try {
                ConnectUtil::save($contact, $source);
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                return $this->getResponseObject(null, null, Config::getMessage(SORRY_ERROR_SUBMISSION_LBL));
            }

            $postHookData = array('data' => $contact, 'returnValue' => $contact->ID);
            Hooks::callHook('post_contact_create', $postHookData);
        }
        $incident->PrimaryContact = $contact;
        if (isset($organizationID) && $organizationID) {
            $incident->Organization = Connect\Organization::fetch($organizationID);
        }

        $preHookData = array('data' => $incident);
        if (is_string($customHookError = Hooks::callHook('pre_feedback_submit', $preHookData))) {
            return $this->getResponseObject(null, null, $customHookError);
        }

        if ($abuseMessage = $this->isAbuse()) {
            return $this->getResponseObject(null, null, $abuseMessage);
        }

        try {
            ConnectUtil::save($incident, $source);
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, Config::getMessage(SORRY_ERROR_SUBMISSION_LBL));
        }

        if($answerID){
            ActionCapture::record('answerFeedback', 'submit', $answerID);
        }
        else{
            ActionCapture::record('siteFeedback', 'submit');
        }

        $postHookData = array('data' => $incident, 'returnValue' => $incident->ID);
        Hooks::callHook('post_feedback_submit', $postHookData);

        return $this->getResponseObject($incident);
    }

    /**
     * Looks up an Incident ID given its reference number
     * @param string $referenceNumber The reference number to use
     * @return int|bool Incident ID or false if no incident was found
     */
    public function getIncidentIDFromRefno($referenceNumber){
        try
        {
            $result = Connect\ROQL::query(sprintf("SELECT I.ID FROM Incident I WHERE I.ReferenceNumber = '%s'", Connect\ROQL::escapeString($referenceNumber)))->next();
        }
        catch(Connect\ConnectAPIErrorBase $e)
        {
            return $this->getResponseObject(false, null, $e->getMessage());
        }
        if($row = $result->next())
            return $this->getResponseObject(intval($row['ID']), 'is_int');
        return $this->getResponseObject(false, null, 'Invalid or non-existent reference number supplied');
    }

    /**
     * Lookup the email_priority_matched for the provided email address
     * @param string $email The email address to look up the preference for
     * @return int email_priority_matched value (zero-based)
     */
    protected function lookupEmailPriority($email) {
        $matchResults = Api::contact_match(array('email' => $email));
        if ($matchResults && $matchResults['email_priority_matched'] > 0) {
            return $matchResults['email_priority_matched'] - 1;
        }
        return 0;
    }

    /**
     * Under the right circumstances, automagically generate a subject from the thread entry.
     * @param array $formData Incident form data
     * @return array Form data with possibly modified subject.
     */
    protected function autoFillSubject(array $formData) {
        $isSubjectSet = (array_key_exists('Incident.Subject', $formData) && isset($formData['Incident.Subject']->value) && $formData['Incident.Subject']->value !== "");
        $isThreadSet = (array_key_exists('Incident.Threads', $formData) && isset($formData['Incident.Threads']->value) && $formData['Incident.Threads']->value !== "");

        if(array_key_exists('Incident.Subject', $formData) && $formData['Incident.Subject']->required) {
            return $formData;
        }

        if (!$isSubjectSet && $isThreadSet) {
            $formData['Incident.Subject'] = (object)array('value' => Text::truncateText($formData['Incident.Threads']->value, 80));
        }
        else if(!$isSubjectSet && !$isThreadSet) {
            $formData['Incident.Subject'] = (object)array('value' => Config::getMessage(SUBMITTED_FROM_WEB_LBL) . Api::date_str(DATEFMT_DTTM, time()));
        }
        return $formData;
    }

    /**
     * Processes SmartAssistant™ results returned from the KFAPI.
     * @param KnowledgeFoundation\SmartAssistantContentSearch $incidentContent Object with predefined summary/question, permission, and prod/cat filtering fields already set
     * @param array $keyValueList Additional incident fields that have an affect on which SA results are returned (e.g. custom fields, severity, etc)
     * @return array Processed Smart Assistant results; empty if no results
     */
    protected function getSmartAssistantResults(KnowledgeFoundation\SmartAssistantContentSearch $incidentContent, array $keyValueList) {
        ActionCapture::record('incident', 'suggest');
        $results = array('suggestions' => array());
        $answerSummarySuggestions = $questionSummarySuggestions = array();
        try{
            $smartAssistantSuggestions = KnowledgeFoundation\Knowledge::GetSmartAssistantSuggestions($this->getKnowledgeApiSessionToken(), $incidentContent, $keyValueList);
            if(!is_object($smartAssistantSuggestions)){
                return $results;
            }
            $results['canEscalate'] = $smartAssistantSuggestions->CanEscalate;
            $results['token'] = $smartAssistantSuggestions->Token;

            //If there are no results and the user can escalate the post, send back whether or not we ran rules
            //to determine if we have to show an empty result set (patent issues) or if we can immediately escalate the incident
            if(!is_object($smartAssistantSuggestions->Suggestions)){
                $results['rulesMatched'] = $smartAssistantSuggestions->RulesMatched;
                return $results;
            }
            ActionCapture::record('incident', 'suggestFound');
            foreach($smartAssistantSuggestions->Suggestions as $suggestion){
                //Standard text
                if($suggestion instanceof KnowledgeFoundation\StandardContentContent){
                    //These responses return the value as an array of both the text and html types. We
                    //want the HTML type.
                    foreach($suggestion->ContentValues as $standardText){
                        if($standardText->ContentType->ID === STD_CONTENT_CONTENT_TYPE_HTML){
                            $results['suggestions'][] = array(
                                'type' => 'StandardContent',
                                'content' => $standardText->Value
                            );
                        }
                    }
                }
                //Full answer
                else if($suggestion instanceof KnowledgeFoundation\AnswerContent){
                    $results['suggestions'][] = array(
                        'type' => 'Answer',
                        'title' => Text::escapeHtml($suggestion->Summary),
                        'content' => $suggestion->Solution,
                        'FileAttachments' => $suggestion->FileAttachments[0]->ID,
                        'ID' => $suggestion->ID
                    );
                }
                //Partial answer content search result. In order to allow these to be
                //displayed correctly, we'll group them together in a sub array
                else if($suggestion instanceof KnowledgeFoundation\AnswerSummaryContent){
                    $answerSummarySuggestions[] = array(
                        'ID' => $suggestion->ID,
                        'title' => Text::escapeHtml($suggestion->Title),
                    );
                }
                else if ($suggestion instanceof KnowledgeFoundation\SocialQuestionSummaryContent){
                    $questionSummarySuggestions[] = array(
                        'ID' => $suggestion->ID,
                        'title' => Text::escapeHtml($suggestion->Title),
                    );
                }
            }
        }
        catch(Connect\ConnectAPIErrorBase $e){
            if(!IS_HOSTED){
                echo $e->getMessage();
                exit;
            }
            return null;
        }
        catch(ConnectV2\ConnectAPIErrorBase $e){
            if(!IS_HOSTED){
                echo $e->getMessage();
                exit;
            }
            return null;
        }
        //Merge in normal SA results with the other potential types so that they are grouped together
        //for display purposes
        if(count($answerSummarySuggestions)){
            $results['suggestions'][] = array(
                'type' => 'AnswerSummary',
                'list' => $answerSummarySuggestions
            );
        }
        if(count($questionSummarySuggestions)) {
            $results['suggestions'][] = array(
                'type' => 'QuestionSummary',
                'list' => $questionSummarySuggestions
            );
        }
        return $results;
    }

    /**
     * Converts incident form data to the two pieces of data needed to get Smart Assistant suggestions,
     * a SmartAssistantContentSearch object and an associative array.
     * @param array $formData Submitted form data with each field prefixed by "Incident."
     * @param Connect\Contact $contact The contact that is submitting this incident
     * @return array The SmartAssistantContentSearch object and the key-value array needed to get SA suggestions
     */
    protected function convertFormDataToSmartAssistantSearch(array $formData, Connect\Contact $contact){
        $incidentContent = new KnowledgeFoundation\SmartAssistantContentSearch();
        $this->addKnowledgeApiSecurityFilter($incidentContent, $contact);
        //Set up primary summary/description fields in their own object. One of these must have
        //a value so create a generic default if neither is provided
        $subjectIsSet = array_key_exists('Incident.Subject', $formData);
        $threadIsSet = array_key_exists('Incident.Threads', $formData);
        if($subjectIsSet || $threadIsSet){
            if($subjectIsSet){
                $incidentContent->Summary = $formData['Incident.Subject'];
            }
            if($threadIsSet){
                $incidentContent->DetailedDescription = $formData['Incident.Threads'];
            }
        }
        else{
            $incidentContent->Summary = Config::getMessage(SUBMITTED_FROM_WEB_LBL) . Api::date_str(DATEFMT_DTTM, time());;
        }
        //Attach any product/category filters
        $productIsSet = array_key_exists('Incident.Product.ID', $formData);
        $categoryIsSet = array_key_exists('Incident.Category.ID', $formData);
        if($productIsSet || $categoryIsSet){
            $incidentContent->Filters = new KnowledgeFoundation\ContentFilterArray();
            if($productIsSet && $product = $this->CI->model('Prodcat')->get($formData['Incident.Product.ID'])->result){
                $productFilter = new KnowledgeFoundation\ServiceProductContentFilter();
                $productFilter->ServiceProduct = $product;
                $incidentContent->Filters[] = $productFilter;
            }
            if($categoryIsSet && $category = $this->CI->model('Prodcat')->get($formData['Incident.Category.ID'])->result){
                $categoryFilter = new KnowledgeFoundation\ServiceCategoryContentFilter();
                $categoryFilter->ServiceCategory = $category;
                $incidentContent->Filters[] = $categoryFilter;
            }
        }
        //Remove these so we don't unnecessarily iterate over them below
        unset($formData['Incident.Subject'], $formData['Incident.Threads'], $formData['Incident.Product.ID'], $formData['Incident.Category.ID']);

        $keyValueList = array();
        foreach($formData as $fieldName => $fieldValue){
            //The API won't handle empty values passed in the key/value array
            if($fieldValue === null || $fieldValue === ''){
                continue;
            }
            //Currently SA only supports a subset of possible fields
            if(Text::beginsWith($fieldName, 'Incident.CustomFields')){
                // DateTime custom fields need to be converted to timestamps.  Since the field type isn't available in
                // the form data, treat any non-numeric values that can be parsed as dates as date values
                if (!is_numeric($fieldValue) && DateUtil::canParse($fieldValue))
                    $keyValueList[$fieldName] = DateUtil::getDateObject($fieldValue)->getTimestamp();
                else
                    $keyValueList[$fieldName] = $fieldValue;
            }
            else if(in_array($fieldName, array('Incident.Disposition', 'Incident.Language', 'Incident.Queue', 'Incident.Severity.ID', 'Incident.StatusWithType'))){
                $keyValueList[$fieldName] = $fieldValue;
            }
        }
        return array($incidentContent, $keyValueList);
    }

    /**
     * Utility function to verify incident viewing based on contact ID and organization hierarchies
     * @param Connect\Incident $incident A Connect Incident object.
     * @return bool True if contact is allowed to read the incident, false otherwise
     */
    protected function isContactAllowedToReadIncident(Connect\Incident $incident) {
        $contactID = $this->CI->session->getProfileData('contactID');
        if (!Framework::isLoggedIn() || !Framework::isValidID($contactID)) {
            return false;
        }

        if ($incident->PrimaryContact->ID === $contactID) {
            return true;
        }

        $organizationID = $this->CI->session->getProfileData('orgID');
        if (!Framework::isValidID($incident->Organization->ID) || !Framework::isValidID($organizationID)) {
            return false;
        }

        $organizationIDsMatch = ($incident->Organization->ID === $organizationID);
        $orgLevelFromConfig = Config::getConfig(MYQ_VIEW_ORG_INCIDENTS);
        if ($orgLevelFromConfig === 1) {
            return $organizationIDsMatch;
        }

        if ($orgLevelFromConfig === 2) {
            if ($organizationIDsMatch) {
                return true;
            }
            if (($this->CI->session->getProfileData('orgLevel')) !== false) {
                foreach ($incident->Organization->OrganizationHierarchy as $parentOrg) {
                    if ($organizationID === $parentOrg->ID) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Utility method to set the value on the Incident object. Handles more complex types such as thread entries
     * and file attachment values.
     * @param Connect\RNObject $incident Current incident object that is being created/updated
     * @param string $fieldName Name of the field we're setting
     * @param mixed $fieldValue Value of the field.
     * @param string $fieldType Common object model field type
     * @return null|string Returns null upon success or an error message from Connect::setFieldValue upon error.
     */
    protected function setFieldValue(Connect\RNObject $incident, $fieldName, $fieldValue, $fieldType = null){
        if($fieldName === 'Incident.StatusWithType.Status' && $fieldValue < 1){
            return;
        }
        if($fieldType === 'Thread'){
            $this->createThreadEntry($incident, $fieldValue);
        }
        else if($fieldType === 'FileAttachmentIncident'){
            $this->createAttachmentEntry($incident, $fieldValue);
        }
        else if($fieldType === 'AssignedSLAInstance'){
            $this->createSlaEntry($incident, $fieldValue);
        }
        else if($fieldName === 'Incident.Asset'){
            $incident->Asset = $this->CI->model('Asset')->get($fieldValue)->result;
        }
        else{
            // DateTime values are submitted as date strings but stored as timestamps
            if($fieldType === 'DateTime')
                $fieldValue = strval(DateUtil::getDateObject($fieldValue)->getTimestamp());

            return parent::setFieldValue($incident, $fieldName, $fieldValue);
        }
    }

    /**
     * Utility function to create a thread entry object with the specified value. Additionally sets
     * values for the entry type and channel of the thread.
     * @param Connect\Incident $incident Current incident object that is being created/updated
     * @param string $value Thread value
     * @return void
     */
    protected function createThreadEntry(Connect\Incident $incident, $value){
        if($value !== null && $value !== false && $value !== ''){
            $incident->Threads = new Connect\ThreadArray();
            $thread = $incident->Threads[] = new Connect\Thread();
            $thread->EntryType = ENTRY_CUSTOMER;
            $thread->Channel = CHAN_CSS_WEB;
            $thread->Text = $value;
            if ($contact = $this->getContact()) {
                $thread->Contact = $contact;
            }
        }
    }

    /**
     * Create the necessary SLA Connect object with the provided SLA ID.
     * @param Connect\Incident $incident Connect incident object that is being created/updated
     * @param int $value ID of the SLA to apply to the incident
     * @return void
     */
    protected function createSlaEntry(Connect\Incident $incident, $value){
        if(is_int($value) && $value > 0){
            $incident->SLAInstance = new Connect\AssignedSLAInstance();
            $incident->SLAInstance->NameOfSLA->ID = $value;
        }
    }

    /**
     * Generate a new profile and write to cp_profile cookie
     * @param Object $currentProfile The current profile object
     * @param array $propertyNamesToPreserve An array of profile property names to preserve.
     *    Only non null properties of $currentProfile will be carried forward.
     * @return Object|null The new profile object or null if API verification fails.
     */
    protected function regenerateProfile($currentProfile, array $propertyNamesToPreserve = array()) {
        //Reverify the user so that SLA instances get updated
        $sessionID = $this->CI->session->getSessionData('sessionID');
        if ($profile = Api::contact_login_verify($sessionID, $currentProfile->authToken)) {
            if (!$sessionID) {
                // API indicated that the session expired
                $this->CI->session->generateNewSession();
            }

            foreach($propertyNamesToPreserve as $property) {
                if (($value = $currentProfile->$property) !== null) {
                    $profile->$property = $value;
                }
            }

            if ($profile = $this->CI->session->createMapping($profile)) {
                $this->CI->session->createProfileCookie($profile);
            }
        }

        return $profile;
    }


    /**
     * Computes incident subject to create based on answer feedback rating value and number of options
     * @param int $answerID ID of the answer being rated
     * @param int $numberOfOptions Size of rating scale. Should be between 2-5
     * @param int $rating Value user rated answer
     * @return string Subject of incident
     */
    private function getAnswerFeedbackSubject($answerID, $numberOfOptions, $rating){
        if ($numberOfOptions === 2) {
            // Yes / No feedback
            $message = ($rating < 2) ? Config::getMessage(NOT_HELPFUL_LBL) : Config::getMessage(HELPFUL_LBL);
            return sprintf(Config::getMessage(FEEDBK_ANS_ID_PCT_D_RATED_PCT_S_LBL), $answerID, $message);
        }
        if($numberOfOptions <= 5){
            $ratingLabels = array();
            if ($numberOfOptions === 3) {
                $ratingLabels = array(RANK_0_LBL, RANK_50_LBL, RANK_100_LBL);
            }
            else if ($numberOfOptions === 4) {
                $ratingLabels = array(RANK_0_LBL, RANK_25_LBL, RANK_75_LBL, RANK_100_LBL);
            }
            else if ($numberOfOptions === 5) {
                $ratingLabels = array(RANK_0_LBL, RANK_25_LBL, RANK_50_LBL, RANK_75_LBL, RANK_100_LBL);
            }
            $rating = Config::getMessage($ratingLabels[$rating - 1]);
        }
        return sprintf(Config::getMessage(FEEDBK_ANS_ID_PCT_D_RATED_HELPFUL_LBL), $answerID, $rating);
    }
}
