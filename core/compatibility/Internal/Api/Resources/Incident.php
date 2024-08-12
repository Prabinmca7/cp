<?php
namespace RightNow\Internal\Api\Resources;
use RightNow\Api\Models\Contact as ContactModel,
    RightNow\Api\Models\Incident as IncidentModel,
    RightNow\Internal\Api\Response,
    RightNow\Internal\Api\Structure\Document,
    RightNow\Internal\Utils\Version as Version;

require_once CORE_FILES . 'compatibility/Internal/Api/Models/Contact.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Models/Incident.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Resources/Base.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Resources/Contact.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Resources/IncidentThread.php';

class Incident extends Base {

    const IS_API_OPEN = true;
    const MINIMUM_SUPPORTED_CP_FRAMEWORK_VERSION = '3.1';

    public function __construct() {
        $this->type = "incidents";
        $this->attributeMapping = array(
            'osvc' => array(
                'incident'              => array(
                    'contactId'         => 'PrimaryContact',
                    'created'           => 'CreatedTime',
                    'referenceNumber'   => 'ReferenceNumber',
                    'subject'           => 'Subject',
                    'threads'           => 'Threads',
                    'updated'           => 'UpdatedTime',
                )
            )
        );
    }

    /**
     * Creates incident and generates {json-api} document
     * @return object {json-api} top level document
     */
    public function createIncident() {
        if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.0") === 0) {
            $errors = $this->createError(Response::getErrorResponseObject("This API is supported for CP framework 3.1 or above", Response::HTTP_FORBIDDEN_STATUS_CODE)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $attributes = array_keys($this->attributeMapping['osvc']['incident']);
        $contactModel = new ContactModel();
        $document = new Document();
        $incidentModel = new IncidentModel();
        $processedData = array();

        if (!self::IS_API_OPEN) {
            $errors = $this->createError(Response::getErrorResponseObject("This API is currently disabled.", Response::HTTP_NOT_FOUND_STATUS_CODE)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $postData = json_decode(\RightNow\Environment\xssSanitizeReplacer(file_get_contents("php://input"), false))->data->attributes;
        if(!$postData || !$postData->threads[0]->body || !$postData->email) {
            $errors = $this->createError(Response::getErrorResponseObject("Body and Email are mandatory fields for Incident creation.", Response::HTTP_BAD_REQUEST)->errors);
            $document->setErrors($errors);
            return $document->output();
        }
        foreach($postData as $key => $value) {
            if($key === 'threads') {
                $value = $value[0]->body; //currently we support single thread creation
            }
            if($connectField = $this->attributeMapping['osvc']['incident'][$key]) {
                $fieldObject = new \stdClass();
                $fieldObject->value = $value;
                $fieldObject->required = 1;
                $processedData['Incident' . '.' . $connectField] = $fieldObject;
            }    
        }

        $existingContact = $contactModel->lookupContactByEmail($postData->email)->result;
        if($existingContact){
            $processedData['Incident.PrimaryContact'] = $existingContact;
        }
        else {
            $contactData = array();
            $emailObject = new \stdClass();
            $emailObject->value = $postData->email;
            $emailObject->required = 1;
            $contactData['Contact.Emails.PRIMARY.Address'] = $emailObject;
            $newContact = $contactModel->create($contactData);
            if($newContact->result && $newContact->result->ID){
                $processedData['Incident.PrimaryContact'] = $newContact->result;
            }
        }

        if(!$processedData['Incident.PrimaryContact']) {
            $errors = $this->createError(Response::getErrorResponseObject("Your question cannot be submitted. Please try again.", Response::HTTP_BAD_REQUEST)->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $result = $incidentModel->create($processedData);

        if($result->errors) {
            $errors = $this->createError($result->errors);
            $document->setErrors($errors);
            return $document->output();
        }

        $data = $this->createData($result->result, $attributes);
        $document->setData($data);
        $document->setIncluded($this->includeThreads($result->result));
        return $document->output();
    }

    /**
     * Helper method to generate threads for the document:include
     * @param object $incident Incident object
     * @return array {json-api} Array of IncidentThread
     */
    private function includeThreads($incident) {
        $includeMembers = array();
        $incidentThread = new IncidentThread();
        foreach($incident->Threads as $index => $thread) {
            $includeMembers[] = $incidentThread->createInclude($thread);
        }
        return $includeMembers;
    }
}
