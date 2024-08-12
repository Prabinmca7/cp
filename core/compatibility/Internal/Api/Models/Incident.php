<?php
namespace RightNow\Api\Models;

use RightNow\Api,
    RightNow\Api\Models\Contact as ContactModel,
    RightNow\Connect\Knowledge\v1 as KnowledgeFoundation,
    RightNow\Internal\Api\Response,
    RightNow\Internal\Utils\Version as Version,
    RightNow\Libraries\Hooks,
    RightNow\Utils\Config,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Utils\Framework,
    RightNow\Utils\Text;

require_once CORE_FILES . 'compatibility/Internal/Api/Models/Base.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Models/Contact.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Response.php';

class Incident extends Base {

    /**
     * Returns an empty incident object.
     *
     * @return Connect\Incident An instance of the Connect incident object
     */
    public function getBlank() {
        $namespacedClass = $this->connectNamespace . '\\' . 'Incident';
        $incident = new $namespacedClass();
        $this->setCustomFieldDefaults($incident);
        return Response::getResponseObject($incident);
    }

    /**
     * Returns a Contact Object if a valid $contactID sent in, or the contact is logged in.
     *
     * @param int|null $contactID The ID of the contact to retrieve
     * @return Connect\Contact|null The contact or null if no contact was found.
     */
    public function getContact($contactID = null) {
        if ($contactID) {
            $contactModel = new ContactModel();
            return $contactModel->get($contactID);
        }
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
     * @param array $formData Form fields to create the incident. In order to be created successfully, either a contact
     * must be logged in or this array must contain a 'Incident.PrimaryContact' key which must be either the ID of the
     * contact, or a instance of a Connect Contact class.
     * @param boolean $smartAssist Denotes whether smart assistant should be run
     * @return Connect\Incident|array|null Created incident object, array of SmartAssistant data, or null if there are error messages and the incident wasn't created
     */
    public function create(array $formData, $smartAssist = false) {
        $incident = $this->getBlank()->result;

        if ($contact = $this->getContact()) {
            $incident->PrimaryContact = $contact;
        }
        else if($formData['Incident.PrimaryContact']){
            if($formData['Incident.PrimaryContact'] instanceof \RightNow\Connect\v1_3\Contact 
                || $formData['Incident.PrimaryContact'] instanceof \RightNow\Connect\v1_4\Contact
                || $formData['Incident.PrimaryContact'] instanceof \RightNow\Connect\v1_2\Contact){
                $incident->PrimaryContact = $formData['Incident.PrimaryContact'];
            }
            else if((is_int($formData['Incident.PrimaryContact']) || ctype_digit($formData['Incident.PrimaryContact']))
                && ($contactAssociatedToIncident = $this->getContact($formData['Incident.PrimaryContact']))) {
                    $incident->PrimaryContact = $contactAssociatedToIncident;
                    $incident->ResponseEmailAddressType->ID = $this->lookupEmailPriority($formData['Contact.Emails.PRIMARY.Address']->value);
            }
        }
        unset($formData['Incident.PrimaryContact']);
        if(!$incident->PrimaryContact) {
            return Response::getErrorResponseObject('Your question cannot be submitted. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        if($incident->PrimaryContact->Disabled) {
            // Disabled contacts can't create incidents
            return Response::getErrorResponseObject('Sorry, there is problem with your account. Please contact our support.', Response::HTTP_INTERNAL_SERVER_ERROR);
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
            if($smartAssist === true && ($field->value !== null && $field->value !== '')){
                //For menu-type custom fields, we have the key for the value they selected (instead of the value). The KFAPI expects us to
                //denote that by adding a .ID onto the end of the name in the key/value pair list.
                if(in_array($fieldMetaData->COM_type, array('NamedIDLabel', 'NamedIDOptList', 'ServiceProduct', 'ServiceCategory', 'Asset'))){
                    $name .= ".ID";
                }
                $smartAssistantData[$name] = $field->value;
            }
        }
        if ($errors) {
            return Response::getErrorResponseObject('Data validation failed.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $incident = parent::createObject($incident, SRC2_EU_AAQ);
        }
        catch (\Exception $e){
            return Response::getErrorResponseObject("An unexpected error has occurred.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        if(!is_object($incident)) { 
            return Response::getErrorResponseObject("An unexpected error has occurred.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        /*
        Commenting this code as OIT APIs do not support current session
        if (Framework::isLoggedIn() && !$this->CI->session->getProfileData('disabled')) {
            $this->regenerateProfile(
                $this->CI->session->getProfile(true),
                array('openLoginUsed', 'socialUserID')
            );
        }
        */
        return Response::getResponseObject($incident, 'is_object');
    }

    /**
     * Under the right circumstances, automatically generate a subject from the thread entry.
     * @param array $formData Incident form data
     * @return array Form data with possibly modified subject.
     */
    protected function autoFillSubject(array $formData) {
        $isSubjectSet = (array_key_exists('Incident.Subject', $formData) && isset($formData['Incident.Subject']->value) && $formData['Incident.Subject']->value !== "");
        $isThreadSet = (array_key_exists('Incident.Threads', $formData) && isset($formData['Incident.Threads']->value) && $formData['Incident.Threads']->value !== "");

        if (!$isSubjectSet && $isThreadSet) {
            $formData['Incident.Subject'] = (object)array('value' => Text::truncateText($formData['Incident.Threads']->value, 80));
        }
        else if(!$isSubjectSet && !$isThreadSet) {
            $formData['Incident.Subject'] = (object)array('value' => Config::getMessage(SUBMITTED_FROM_WEB_LBL) . Api::date_str(DATEFMT_DTTM, time()));
        }
        return $formData;
    }

    /**
     * Utility function to create a thread entry object with the specified value. Additionally sets
     * values for the entry type and channel of the thread.
     * @param Connect\Incident $incident Current incident object that is being created/updated
     * @param string $value Thread value
     * @return object Error response object on failure
     */
    protected function createThreadEntry($incident, $value){
        if($value !== null && $value !== false && $value !== ''){
            try {
                if($this->connectVersion == 1.4) {
                    $incident->Threads = new \RightNow\Connect\v1_4\ThreadArray();
                    $thread = $incident->Threads[] = new \RightNow\Connect\v1_4\Thread();
                }
                else {
                    $incident->Threads = new \RightNow\Connect\v1_3\ThreadArray();
                    $thread = $incident->Threads[] = new \RightNow\Connect\v1_3\Thread();
                }
                $thread->EntryType = ENTRY_CUSTOMER;
                $thread->Channel = CHAN_CSS_WEB;
                $thread->Text = $value;
                if ($contact = $this->getContact()) {
                    $thread->Contact = $contact;
                }
            }
            catch (\Exception $e) {
                return Response::getErrorResponseObject("An unexpected error has occurred.", Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
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
     * Utility method to set the value on the Incident object. Handles more complex types such as thread entries
     * and file attachment values.
     * @param Connect\RNObject $incident Current incident object that is being created/updated
     * @param string $fieldName Name of the field we're setting
     * @param mixed $fieldValue Value of the field.
     * @param string $fieldType Common object model field type
     * @return null|string Returns null upon success or an error message from Connect::setFieldValue upon error.
     */
    protected function setFieldValue($incident, $fieldName, $fieldValue, $fieldType = null){
        if($fieldName === 'Incident.StatusWithType.Status' && $fieldValue < 1) {
            return;
        }
        if($fieldType === 'Thread') {
            return $this->createThreadEntry($incident, $fieldValue);
        }
        else {
            return parent::setFieldValue($incident, $fieldName, $fieldValue);
        }
    }
}
