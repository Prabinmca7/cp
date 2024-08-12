<?php

namespace RightNow\Libraries\Widget;

use RightNow\Utils\Connect,
    RightNow\Utils\Text,
    RightNow\Utils\Framework;

/**
 * Base class for all widgets that display the contents of a DB field. Provides a number of utility methods
 * for massaging the data for correct display.
 */
abstract class Output extends Base
{
    public $fieldComponents;
    public $fieldMetaData;
    public $table;
    public $fieldName;

    private $cacheKey;

    /**
     * Basic getData member implemented by all widgets. Calls into the retrieveAndInitializeData method.
     *
     * @return bool|null False if the current field cannot be displayed, null otherwise.
     */
    public function getData()
    {
        if($this->retrieveAndInitializeData() === false)
            return false;
    }

    /**
     * Populates various data for the current field. This includes the various attributes that a custom field
     * can contain, as well as the default value for the widget if it doesn't already have a value.
     *
     * @return bool|null False if an error was found with the current field, null otherwise.
     */
    protected function retrieveAndInitializeData()
    {
        $this->cacheKey = "Output_DataType_{$this->data['attrs']['name']}";

        $cacheResult = $this->restoreFromCache();
        if ($cacheResult !== null) return $cacheResult;

        $this->data['attrs']['name'] = Connect::mapOldFieldName($this->data['attrs']['name']);
        $this->fieldComponents = Connect::parseFieldName($this->data['attrs']['name']);
        if(!is_array($this->fieldComponents)) {
            return $this->setErrorMessage($this->fieldComponents);
        }
        $this->table = current($this->fieldComponents);
        $this->fieldName = end($this->fieldComponents);

        try {
            list($this->data['value'], $this->fieldMetaData) = $this->getConnectObjectForField($this->fieldComponents);
        }
        catch (\Exception $e) {
            return $this->setErrorMessage($e->getMessage());
        }

        if (!$this->validateUsage()) return false;

        $this->setMask();

        $this->setLabel();

        if(!Text::beginsWith($this->data['attrs']['name'], 'Community')) {
            $this->cache();
        }

        return true;
    }

    /**
     * Retrieves the Connect object instance's field value for the specified field.
     * @param  array $fieldNames Denotes what object, field, and sub-field (if applicable) to retrieve the value for
     * @return array Field value, field metadata
     * @throws \Exception If the underlying Connect library finds an error  with the supplied field name
     */
    protected function getConnectObjectForField(array $fieldNames) {
        return Connect::getObjectField($fieldNames, $this->getObjectID());
    }

    /**
     * Retrieve the table specific Connect object ID to be passed to getObjectField.
     * Calls the  `get{tablename}ID` method if present or returns null.
     * @return integer|null The object ID or null.
     */
    private function getObjectID() {
        $method = "get{$this->table}ID";
        return method_exists($this, $method) ? $this->$method() : null;
    }

    /**
     * Returns the SocialUserID, either from the `user` url parameter, or the session
     * @return id|null
     */
    private function getCommunityuserID() {
        $model = $this->CI->model('CommunityUser');
        $socialUser = null;
        if (($id = \RightNow\Utils\Url::getParameter('user')) &&
            ($object = $model->get($id)->result) &&
            $object->SocialPermissions->canRead()) {
                $socialUser = $object;
        }
        else {
            $socialUser = $model->get()->result;
        }

        return $this->data['socialUserID'] = $socialUser ? $socialUser->ID : null;
    }

    /**
     * Returns the SocialQuestionCommentID as passed by the FileDataDisplay widget as an attribute, null if not found
     * @return id|null
     */
    private function getCommunitycommentID() {
        return isset($this->data['attrs']['parent_object_id']) ? $this->data['attrs']['parent_object_id'] : null;
    }

    /**
     * Returns the SocialQuestionID as passed by the FileDataDisplay widget as an attribute, null if not found
     * @return id|null
     */
    private function getCommunityquestionID() {
        return isset($this->data['attrs']['parent_object_id']) ? $this->data['attrs']['parent_object_id'] : null;
    }

    /**
     * Sets the widget's label attribute if it was left at its default.
     * @return void
     */
    private function setLabel() {
        if (isset($this->data['attrs']['label']) && $this->data['attrs']['label'] === '{default_label}') {
            $label = $this->fieldMetaData->label;
            $this->data['attrs']['label'] = ($label && $label !== 'NamedIDLabel') ? $label : $this->fieldName;
        }
    }

    /**
     * Sets the widget's mask if it should be set
     * @return void
     */
    private function setMask() {
        if ($mask = Connect::getMask($this->table, $this->data['attrs']['name'], $this->fieldMetaData))
            $this->data['mask'] = $mask;
    }

    /**
     * Validates whether the field should be displayed.
     * Checks Channel visibility, Custom Field visibility,
     * and whether the field is a password.
     * @return boolean True if the field's usage checks out, false otherwise
     */
    private function validateUsage() {
        if (Connect::isChannelField($this->fieldComponents) && !Connect::isChannelFieldEnduserVisible($this->fieldComponents)) {
            return $this->setErrorMessage(sprintf(\RightNow\Utils\Config::getMessage(CHANNEL_FLD_PCT_S_FND_PCT_S_TABLE_MSG), $this->data['attrs']['name'], $this->table));
        }
        if (\RightNow\Utils\Text::endsWith($this->data['attrs']['name'], 'NewPassword')) {
            return $this->setErrorMessage(\RightNow\Utils\Config::getMessage(PASSWORD_FIELDS_DISPLAYED_MSG));
        }
        if (Connect::isCustomField($this->fieldMetaData) && !Framework::isCustomFieldEnduserVisible($this->table, $this->fieldName)) {
            return false;
        }
        if ($this->data['value'] === '' || $this->data['value'] === null) {
            // Don't display fields whose values are an empty string or null.
            return false;
        }

        return true;
    }

    /**
     * Displays the error message and calls into inherited method.
     * @param string $message Error message
     * @param boolean $cacheError Whether the error should be cached
     * @return boolean False, as a convenience for callers to return
     */
    public function setErrorMessage($message, $cacheError = true) {
        if ($cacheError) {
            Framework::setCache($this->cacheKey, array($errorMessage), false);
        }
        echo $this->reportError($message);

        return false;
    }

    /**
     * Restores widget members if a cached state is found for `$this->cacheKey`.
     * @return null|boolean True if the widget was restored from cache, False if
     * an error message was retrieved from cache, Null if nothing was cached
     */
    private function restoreFromCache() {
        if ($cachedResults = Framework::checkCache($this->cacheKey)) {
            list(
                $error,
                $canonicalFieldName,
                $this->fieldComponents,
                $this->table,
                $this->fieldName,
                $this->fieldMetaData,
                $value,
                $mask,
            ) = $cachedResults;

            if ($error) {
                return $this->setErrorMessage($error, false);
            }

            $this->data['attrs']['name'] = $canonicalFieldName;
            $this->data['value'] = $value;
            if($mask)
                $this->data['mask'] = $mask;
            $this->setLabel();

            return true;
        }
    }

    /**
     * Caches widget members for `$this->cacheKey`.
     * @return void
     */
    private function cache() {
        Framework::setCache($this->cacheKey, array(
            null,
            $this->data['attrs']['name'],
            $this->fieldComponents,
            $this->table,
            $this->fieldName,
            $this->fieldMetaData,
            $this->data['value'],
            isset($this->data['mask']) ? $this->data['mask'] : null,
        ), false);
    }
}
