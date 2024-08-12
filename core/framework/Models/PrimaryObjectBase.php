<?php

namespace RightNow\Models;

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Utils\Config,
    RightNow\Utils\Framework,
    RightNow\Libraries\Hooks,
    RightNow\Libraries\ResponseError,
    RightNow\ActionCapture,
    RightNow\Utils\Text,
    RightNow\Connect\Knowledge\v1 as KnowledgeFoundation;

/**
 * Base class for models that are used to retrieve, create, and update objects within CP.
 */
abstract class PrimaryObjectBase extends Base{
    protected $objectName;
    protected $className;

    /**
     * Constructor.
     * @param string $objectName Specify the name of the Connect Object the Connect instance is for.
     *          If not specified, the Connect object is assumed to be the class name of the inheriting model.
     * @throws \Exception If the expected class does not exist
     */
    function __construct($objectName = '') {
        if ($objectName === '') {
            $objectName = get_class($this);
            // Extract class name from its namespace
            if (($finalNamespaceSeparater = strrpos($objectName, '\\')) !== false) {
                $objectName = substr($objectName,  ++$finalNamespaceSeparater);
            }
        }
        $this->objectName = ucfirst(strtolower($objectName));
        $namespacedClass = CONNECT_NAMESPACE_PREFIX . '\\' . $objectName;
        $this->className = $namespacedClass;
        parent::__construct();
    }

    /**
     * Returns an empty structure of the primary object extending this class.
     * @return Connect\RNObject Instance of Connect object
     */
    protected function getBlank(){
        return new $this->className();
    }

    /**
     * Retrieve an existing Primary Object provided its ID
     * @param int|string $objectID ID of the object to retrieve
     * @return string|Connect\RNObject Error message or object instance
     */
    protected function get($objectID){
        static $objectCache = array();
        $cacheKey = "{$this->objectName}-{$objectID}";
        if (!Framework::isValidID($objectID)) {
            return "Invalid {$this->objectName} ID: $objectID";
        }

        if(isset($objectCache[$cacheKey]) && $cachedObject = $objectCache[$cacheKey]){
            return $cachedObject;
        }
        try {
            $connectObject = call_user_func("{$this->className}::fetch", $objectID);
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $e->getMessage();
        }
        $connectObject = \RightNow\Libraries\Formatter::formatSafeObject($connectObject);
        $objectCache[$cacheKey] = $connectObject;        
        return $connectObject;
    }

    /**
     * Utility method to create a new object
     * @param Connect\RNObject $connectObject Instance of object to create
     * @param int $source Source level 2 to use when creating the object
     * @param bool $shouldRunPreCreateHook Whether or not to execute the pre_{object}_create hook
     * @return Connect\RNObject Modified Connect oject
     */
    protected function createObject(Connect\RNObject $connectObject, $source, $shouldRunPreCreateHook = true){
        return $this->createOrUpdateObject($connectObject, $source, 'create', $shouldRunPreCreateHook);
    }

    /**
     * Utility method to update an existing object
     * @param Connect\RNObject $connectObject Instance of object to update
     * @param int $source Source level 2 to use when updating the object
     * @param bool $shouldRunPreUpdateHook Whether or not to execute the pre_{object}_update hook
     * @return Connect\RNObject Modified Connect oject
     */
    protected function updateObject(Connect\RNObject $connectObject, $source, $shouldRunPreUpdateHook = true){
        return $this->createOrUpdateObject($connectObject, $source, 'update', $shouldRunPreUpdateHook);
    }

    /**
     * Sets a field value on the provided Primary Object.
     * @param Connect\RNObject $connectObject The Connect object on which to set the value
     * @param string $fieldName Connect formatted name of the field
     * @param mixed $fieldValue The value to set on the object
     * @return null|string Exception error message or null on success.
     */
    protected function setFieldValue(Connect\RNObject $connectObject, $fieldName, $fieldValue){
        try {
            ConnectUtil::setFieldValue($connectObject, explode('.', $fieldName), $fieldValue);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Returns a Contact Object if a valid $contactID sent in, or the contact is logged in.
     *
     * @param int|null $contactID The ID of the contact to retrieve
     * @return Connect\Contact|null The contact or null if no contact was found.
     */
    protected function getContact($contactID = null) {
        if (!$contactID && Framework::isLoggedIn()) {
            $contactID = $this->CI->session->getProfileData('contactID');
        }
        if ($contactID) {
            return $this->CI->model('Contact')->get($contactID)->result;
        }
    }

    /**
     * Create the necessary file attachment Connect object with the specified details. Assumes
     * that the file has already been uploaded to the upload_tmp_dir. If not, no attachment will be added
     * @param Connect\RNObject $connectObject Connect object that is being created/updated
     * @param array|null $value List of files to upload. Each file should be an object with the attachment contentType, userName, and localName
     * @return void
     */
    protected function createAttachmentEntry(Connect\RNObject $connectObject, $value) {
        if(is_array($value) && count($value)) {
            $objectName = strtolower($this->objectName);
            if($objectName === "communityquestion" || $objectName === "communitycomment") {
                $containerClassName = CONNECT_NAMESPACE_PREFIX . '\\FileAttachmentCommunityArray';
                $objectClassName = CONNECT_NAMESPACE_PREFIX . '\\FileAttachmentCommunity';

                if(isset($value['removedFiles'])) {
                    $modelName = ($objectName === "communityquestion") ? 'CommunityQuestion' : 'CommunityComment';
                    $result = $this->CI->model("{$modelName}")->get($connectObject->ID)->result;
                    $connectObject->FileAttachments = isset($result->FileAttachments) ? $result->FileAttachments : null;   
                 
                    //Attachments can only be removed in last-to-first order due to some constraints on the ConnectArray object
                    $faCount = is_array($connectObject->FileAttachments) ? count($connectObject->FileAttachments) : 0;
                    if($faCount > 0) {
                        for($index = ($faCount - 1); $index >= 0; $index--) {
                            if(in_array($connectObject->FileAttachments[$index]->ID, $value['removedFiles'])) {
                                $connectObject->FileAttachments->offsetUnset($index);
                            }
                        }
                    }
                }
                if(isset($value['newFiles'])) {
                    if(!isset($connectObject->FileAttachments) || !is_array($connectObject->FileAttachments) || count($connectObject->FileAttachments) === 0) {
                        $connectObject->FileAttachments = new $containerClassName();
                    }
                    $value = $value['newFiles'];
                }
            }

            else {
                $containerClassName = CONNECT_NAMESPACE_PREFIX . '\\FileAttachmentIncidentArray';
                $objectClassName = CONNECT_NAMESPACE_PREFIX . '\\FileAttachmentIncident';
                $connectObject->FileAttachments = new $containerClassName();
            }

            if(is_array($value)) {
                foreach($value as $attachment){
                    if(($tempFile = \RightNow\Api::fattach_full_path($attachment->localName)) && \RightNow\Utils\FileSystem::isReadableFile($tempFile)){
                        $file = $connectObject->FileAttachments[] = new $objectClassName();
                        $file->ContentType = $attachment->contentType;
                        $file->setFile($tempFile);
                        $file->FileName = preg_replace("/[\r\n\/:*?\"<>|]+/", '_', $attachment->userName);
                    }
                }
            }
        }
    }
    /**
     * Checks the given exception chain for specific errors and normalizes the
     * errors that are returned.
     * @param \Exception $exception Exception instance
     * @return array Array of exception messages
     */
    protected function getSaveErrors(\Exception $exception) {
        $errors = array();

        // first, put all exception message in phpoutlog
        $callOutlog = function($exception, $objectName, $isPrevious = false) {
            \RightNow\Api::phpoutlog("Connect " . ($isPrevious ? "(previous) " : "") . "exception in $objectName model: code: {$exception->getCode()}, message: {$exception->getMessage()}");
        };
        $callOutlog($exception, $this->objectName);
        $previousException = $exception;
        while($previousException = $previousException->getPrevious()) {
            $callOutlog($previousException, $this->objectName, true);
        }

        // add any specific handling of Connect exceptions
        $message = $exception->getMessage();
        if($this->objectName === 'Contact' && Text::stringContains($message, 'Contact.NewPassword')) {
            // Password validation: first exception has a technical message but previous ones are human-readable
            // Ideally, we would get unique error codes that would allow us to provide a localized error message
            $previousException = $exception;
            while($previousException = $previousException->getPrevious()) {
                $exceptionMessage = ($previousException->getCode() === PW_ERR_PREV_MATCH) ? Config::getMessage(PASSWD_MATCHES_PREV_PASSWD_CONT_LBL) : $previousException->getMessage();
                $errors[] = new ResponseError($exceptionMessage, $previousException->getCode(), $this->objectName, $previousException->getMessage(), $previousException);
            }
        }

        // add a generic sorry message if no specific error messages were added
        if(!count($errors))
            $errors[] = new ResponseError(Config::getMessage(SORRY_ERROR_SUBMISSION_LBL), $exception->getCode(), $this->objectName, $exception->getMessage(), $exception);
        return $errors;
    }

    /**
     * Function to return extended (KB Answers and Social Discussions) smart assistant results for the currently logged in user
     * @param String $subject Question Subject
     * @param String $body Question Body
     * @return null|Array Smart Assistant Results
     */
    protected function getExtendedSmartAssistantResults ($subject, $body) {
        $results = [];
        try {
            $saSearch = new KnowledgeFoundation\SmartAssistantSearch();
            $saSearch->SessionToken = $this->getKnowledgeApiSessionToken();
            $saSearch->Subject = $subject;
            $saSearch->Body = $body;
            $saSearch->Limit = Config::getConfig(SA_NL_MAX_SUGGESTIONS);
            $saSearch->SecurityOptions->Contact = $this->getContact();
            $smartAssistantSuggestions = KnowledgeFoundation\Knowledge::GetSmartAssistantSearchExtended($saSearch);
            $answerSummarySuggestions = $questionSummarySuggestions = array();
            if ($smartAssistantSuggestions->SummaryContents) {
                foreach ($smartAssistantSuggestions->SummaryContents as $summary) {
                    if ($summary instanceof KnowledgeFoundation\AnswerSummaryContent) {
                        $answerSummarySuggestions[] = array(
                            'ID' => $summary->ID,
                            'title' => Text::escapeHtml($summary->Title, false),
                        );
                    }
                    else if ($summary instanceof KnowledgeFoundation\SocialQuestionSummaryContent) {
                        $questionSummarySuggestions[] = array(
                            'ID' => $summary->ID,
                            'title' => Text::escapeHtml($summary->Title, false),
                        );
                    }
                }
            }
            if (!empty($answerSummarySuggestions)) {
                $results['suggestions'][] = array(
                    'type' => 'AnswerSummary',
                    'list' => $answerSummarySuggestions
                );
            }
            if (!empty($questionSummarySuggestions)) {
                $results['suggestions'][] = array(
                    'type' => 'QuestionSummary',
                    'list' => $questionSummarySuggestions
                );
            }
        }
        catch (\Exception $ex) {
            if (!IS_HOSTED) {
                echo $ex->getMessage();
                exit;
            }
            return null;
        }
        return $results;
    }

    /**
     * Generic method for either updating or creating the provided Connect object. Runs the pre (optional) and post
     * hooks as well as adds a ActionCapture entry for the action.
     * @param Connect\RNObject $connectObject The Connect object to save.
     * @param int $source The source level 2 to use when creating or updating the object
     * @param string $mode Either 'update' or 'create'
     * @param bool $shouldRunPreHook Whether or not to execute the pre_{object}_create|update hook
     * @return object|string The created or updated object or a string error message on failure
     * @throws \Exception If an error occured during the save
     */
    private function createOrUpdateObject(Connect\RNObject $connectObject, $source, $mode, $shouldRunPreHook){
        $objectName = strtolower($this->objectName);
        if($shouldRunPreHook){
            $preHookData = array('data' => $connectObject);
            if (is_string($customHookError = Hooks::callHook("pre_{$objectName}_{$mode}", $preHookData))) {
                return $customHookError;
            }
        }

        if ($abuseMessage = $this->isAbuse()) {
            return $abuseMessage;
        }

        ConnectUtil::save($connectObject, $source);

        ActionCapture::record($objectName, $mode);

        $postHookData = array('data' => $connectObject, 'returnValue' => 1);
        Hooks::callHook("post_{$objectName}_{$mode}", $postHookData);
        return $connectObject;
    }
}
