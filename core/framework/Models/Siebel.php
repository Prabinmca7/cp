<?php

namespace RightNow\Models;

use RightNow\ActionCapture,
    RightNow\Api,
    RightNow\Connect\Knowledge\v1 as KnowledgeFoundation,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Internal\Libraries\SiebelRequest,
    RightNow\Internal\SiebelApi,
    RightNow\Libraries\Hooks,
    RightNow\Utils\Config,
    RightNow\Utils\Connect as ConnectUtil,
    RIghtNow\Utils\Text;

require_once CORE_FILES . 'compatibility/Internal/SiebelApi.php';
require_once CPCORE . 'Internal/Libraries/SiebelRequest.php';

/**
 * Model for handling interactions with Siebel.
 */
class Siebel extends Base {
    /**
     * Max length for Siebel abstract field
     */
    const ABSTRACT_MAX_LENGTH = 100;

    /**
     * Max length for Siebel description field
     */
    const DESCRIPTION_MAX_LENGTH = 2000;

    /**
     * Buffer amount for Siebel description field to accommodate email address at the front
     */
    const DESCRIPTION_BUFFER = 100;

    /**
     * Sends an incident submission to a Siebel server.
     *
     * @param array &$hookData Data from the pre_incident_create_save hook having keys:
     *   'formData' {array} Form fields
     *   'incident' {Connect\RNObject} Populated incident object
     *   'shouldSave' {bool} Whether the Incident model should call parent::createObject
     * @return string|null Error message, if any
     */
    public function processRequest(array &$hookData) {
        $convertErrorArrayToString = function($errors) {
            return implode("<br>", $errors);
        };

        $hookData['shouldSave'] = false;

        $formData = $hookData['formData'];
        $incident = $hookData['incident'];

        $siebelData = $this->generateSiebelData($formData, $incident);
        if ($errors = $siebelData['rightnow_integration_errors'])
            return $hookData['incident'] = $convertErrorArrayToString($errors);

        $siebelRequest = new SiebelRequest($siebelData, $formData, $incident);
        $siebelRequest->makeRequest();

        if ($errors = $siebelRequest->getErrors()) {
            return $hookData['incident'] = $convertErrorArrayToString($errors);
        }

        ActionCapture::record('siebel', 'submit');
    }

    /**
     * Handles calling the KnowledgeFoundation\Knowledge::RegisterSmartAssistantResolution for Siebel, when there is not a saved Incident object available.
     *
     * @param array &$hookData Data from the pre_incident_create_save hook having keys:
     *   'knowledgeApiSessionToken' {array} KFAPI token
     *   'smartAssistantToken' {string} Token for coordinating SmartAssistant data
     *   'resolution' {KnowledgeFoundation\SmartAssistantResolution} SmartAssistantResolution object
     *   'incident' {Connect\RNObject} Populated incident object
     *   'shouldRegister' {bool} Whether the Incident model should call KnowledgeFoundation\Knowledge::RegisterSmartAssistantResolution
     * @return void
     */
    public function registerSmartAssistantResolution(array &$hookData) {
        $hookData['shouldRegister'] = false;

        KnowledgeFoundation\Knowledge::RegisterSmartAssistantResolution($hookData['knowledgeApiSessionToken'], $hookData['smartAssistantToken'], $hookData['resolution']);
    }

    /**
     * Generates data for sending to a Siebel server.
     *
     * @param array $formData Form fields
     * @param Connect\RNObject $incident Populated incident object
     * @return array Array of key-value pairs to send in the request to Siebel; the key 'rightnow_integration_errors' (containing an array for a value) will indicate that errors occured.
     */
    private function generateSiebelData(array $formData, Connect\RNObject $incident) {
        $errors = array();

        $abstract = Text::escapeHtml($incident->Subject);
        if (strlen($abstract) > self::ABSTRACT_MAX_LENGTH)
            $errors[] = sprintf(Config::getMessage(QUEST_MAX_LNG_PCT_D_PLS_SHORTEN_MSG), self::ABSTRACT_MAX_LENGTH);

        // fetch the primary email address
        $primaryEmail = $incident->PrimaryContact->Emails && ($primaryEmail = $incident->PrimaryContact->Emails->fetch(0)) ? $primaryEmail : null;

        $description = $incident->Threads[0] ? Text::escapeHtml(\RightNow\Libraries\Formatter::formatThreadEntry($incident->Threads[0], false)) : '';
        if (strlen($description) > (self::DESCRIPTION_MAX_LENGTH - self::DESCRIPTION_BUFFER))
            $errors[] = sprintf(Config::getMessage(DESC_QUEST_MAX_LNG_PCT_D_PLS_MSG), self::DESCRIPTION_MAX_LENGTH - self::DESCRIPTION_BUFFER);

        if ($errors)
            return array('rightnow_integration_errors' => $errors);

        $description = "Email: " . ($primaryEmail && $primaryEmail->Address ? $primaryEmail->Address : Config::getMessage(NONE_SUPPLIED_LBL)) . "\n" . $description;
        if (strlen($description) > self::DESCRIPTION_MAX_LENGTH)
            return array('rightnow_integration_errors' => array(sprintf(Config::getMessage(DESC_QUEST_MAX_LNG_PCT_D_PLS_MSG), self::DESCRIPTION_MAX_LENGTH)));

        foreach ($formData as $name => $field) {
            // contact's email address, incident subject, and incident thread are already taken care of
            if (in_array($name, array('Contact.Emails.PRIMARY.Address', 'Incident.Subject', 'Incident.Threads')))
                continue;
            $fieldValue = $this->getSiebelFieldValue($name, $incident, $field);
            if ($fieldValue === null)
                continue;
            $description .= "\n$name: " . Text::escapeHtml($fieldValue);
        }
        // truncate the description field to fit the Siebel request, ignoring any data that may be lost,
        // since we at least have the end user's email and the thread content
        return array(
            'Abstract' => $abstract,
            'Description' => substr($description, 0, 2000),
        );
    }

    /**
     * Determines the human-readable value of a given field. Intended only for use with the Siebel integration.
     *
     * @param string $name The field name (e.g. Contact.Login)
     * @param Connect\RNObject $incident Populated incident object
     * @param object $rawField Raw field content object
     * @return string|null Human-readable value of the field
     */
    private function getSiebelFieldValue($name, Connect\RNObject $incident, $rawField) {
        try {
            $fieldComponents = ConnectUtil::parseFieldName($name, true);

            $connectObject = $incident;
            if ($fieldComponents[0] === 'Contact') {
                // return raw field content for contact fields
                // in cases where the user already existed, the contact object will not have any of the new data
                // we acknowledge that menu fields will then have integers passed to Siebel instead of names
                $rawFieldValue = $rawField->value;
                return ($rawFieldValue === null || $rawFieldValue === '') ? null : $rawFieldValue;
            }
            else if ($fieldComponents[0] !== 'Incident') {
                // ignore any fields that are not associated to contacts or incidents
                return;
            }

            list($fieldValue, $fieldMetaData) = ConnectUtil::getObjectField($fieldComponents, $connectObject);

            // skip unset fields and ignore file attachments
            if ($fieldValue === null || ConnectUtil::isFileAttachmentType($fieldValue))
                return;

            if (ConnectUtil::getProductCategoryType($fieldValue)) {
                // output hierarchy of prod/cat selected
                if ($fieldValue->ID === null)
                    return;
                $hierarchy = array($fieldValue->LookupName);
                $currentObject = $fieldValue;
                while ($currentObject->Parent) {
                    $hierarchy[] = $currentObject->Parent->LookupName;
                    $currentObject = $currentObject->Parent;
                }
                $hierarchy = array_reverse($hierarchy);
                $fieldValue = implode(' - ', $hierarchy);
            }
            else if (strtolower($fieldMetaData->COM_type) !== 'string') {
                // don't format strings b/c we'll get awful things like email tags around email addresses and br elements instead of newlines in multi-line text
                $fieldValue = \RightNow\Libraries\Formatter::formatField($fieldValue, $fieldMetaData, false);
            }
            // fallback to LookupName if the field value is still an object
            if (is_object($fieldValue))
                $fieldValue = $fieldValue->LookupName;
        }
        catch (\Exception $e) {
            // ignore exceptions
        }

        return $fieldValue;
    }
}
