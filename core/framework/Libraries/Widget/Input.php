<?php
namespace RightNow\Libraries\Widget;

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Utils\Connect,
    RightNow\Utils\Framework,
    RightNow\Utils\Permissions\Social,
    RightNow\Connect\v1_4 as ConnectPHP,
    RightNow\Libraries\Formatter;

/**
 * Base class for all widgets that accept user input for a form. Provides a number of utility methods
 * for parsing the field in use as well as calculating the current or default value.
 */
abstract class Input extends Base
{
    /**
     * Connect field
     */
    public $field;

    /**
     * Table name (e.g. Contacts)
     */
    public $table;

    /**
     * Field name (e.g. Login)
     */
    public $fieldName;

    /**
     * Array of field validations
     */
    public $constraints;

    /**
     * Connect field data type
     */
    public $dataType;

    /**
     * Connect meta data for the fiel
     */
    public $fieldMetaData;

    private $cacheKey;

    /**
     * Basic getData member implemented by all widgets. Calls into the getDataType and retrieveAndInitializeData method.
     *
     * @return bool|null False if the current field cannot be displayed, null otherwise.
     */
    public function getData() {
        if ($this->getDataType() === false)
            return false;
        if ($this->retrieveAndInitializeData() === false)
            return false;

        if (isset($this->data['attrs']['hide_on_load']) && $this->data['attrs']['hide_on_load'])
            $this->classList->add('rn_Hidden');
    }

    /**
     * Calculates the data type for the current field. Determines the correct Connect metadata and
     * object field for the widget. Results of this method are cached. Calling this method will cause the
     * field, table, fieldName, dataType, and fieldMetaData properties to be populated.
     *
     * @return string|null An error message about the current field or null if no errors were found.
     */
    protected function getDataType() {
        $this->cacheKey = 'Input_DataType_' . $this->data['attrs']['name'];

        if ($cacheData = $this->cache('get')) {
            $this->setPropertiesFromCache($cacheData);
            if(isset($cacheData['error']) && $errorMessage = $cacheData['error']) {
                echo $this->reportError($errorMessage);
                return false;
            }
            return true;
        }

        $this->data['attrs']['name'] = Connect::mapOldFieldName($this->data['attrs']['name']);
        $namePieces = Connect::parseFieldName($this->data['attrs']['name'], true);
        if (is_string($namePieces)) {
            // Error message: Object or field name is incorrect or Object isn't supported
            return $this->setErrorMessage($namePieces);
        }

        $this->table = current($namePieces);

        $this->fieldName = end($namePieces);
        $this->data['inputName'] = implode('.', $namePieces);
        if(Connect::isChannelField($namePieces) && !Connect::isChannelFieldEnduserVisible($namePieces)) {
            return $this->setErrorMessage(sprintf(Config::getMessage(CHANNEL_FLD_PCT_S_FND_PCT_S_TABLE_MSG), $this->data['inputName'], $this->table));
        }

        try {
            list($this->field, $this->fieldMetaData) = $this->getConnectObjectForField($namePieces);
        }
        catch (\Exception $e) {
            return $this->setErrorMessage($e->getMessage());
        }
         
        if($this->fieldMetaData === null){
            return $this->setErrorMessage(sprintf(Config::getMessage(PCT_S_FLD_DOESNT_EX_PCT_S_PRIM_LBL), $this->fieldName, $this->table));
        }

        if($this->fieldMetaData->is_menu){
            $this->dataType = 'Menu';
        }
        else {
            $this->dataType = $this->fieldMetaData->COM_type;

            if (Connect::isNamedIDType($this->fieldMetaData) || in_array($this->dataType, array('Country', 'SalesProduct', 'Asset'))) {
                $this->field = isset($this->field->ID) ? $this->field->ID : null;
            }
        }

        // ID and LookupName are not marked as read only in the Connect metadata because Connect allows setting
        // either of these properties to associate by ID or perform a lookup by label.
        // Note: the ID and LookupName properties are not displayed on the Business Objects page and this will
        // prevent users from using them directly in forms
        if ($this->fieldName === "ID" || $this->fieldName === "LookupName" || $this->fieldMetaData->is_read_only_for_create || $this->fieldMetaData->is_read_only_for_update)
            return $this->setErrorMessage(sprintf(Config::getMessage(PCT_S_READ_FIELD_INPUT_WIDGET_MSG), $this->fieldName));

        // Ensure the field does not contain a primary sub-object (e.g. 'Incident.PrimaryContact.Name.First'), as it is not likely to get saved.
        if ($this->invalidSubObjectExists($this->data['attrs']['name'], $this->table, $this->fieldName)) {
            return $this->setErrorMessage(sprintf(Config::getMessage(PCT_S_CONT_PRIM_SUB_OBJECT_INPUT_MSG), $this->data['inputName']));
        }

        $this->setMask();

        $this->setInputName($this->data['inputName']);
        $this->cache('set');
    }

    /**
     * Retrieves the Connect object instance's field
     * value for the specified field.
     * @param  array $fieldNames Denotes what object, field, and sub-field
     *                            (if applicable) to retrieve the value for
     * @return array       Field value, field metadata
     * @throws \Exception If the underlying Connect library finds an error
     *         with the supplied field name
     */
    protected function getConnectObjectForField(array $fieldNames) {
        return Connect::getObjectField($fieldNames, $this->getObjectID());
    }

    /**
     * Cache setter/getter
     * @param string $mode One of 'set' or 'get'
     * @param array $cacheData An associative array of cache data to set. Defaults to `getCacheData` if omitted.
     * @return array An associative array of cache data
     * @throws \Exception if $mode not one of 'set' or 'get'.
     */
    private function cache($mode, array $cacheData = array()) {
        if ($mode === 'set') {
            $cacheData = $cacheData ?: $this->getCacheData();
            Framework::setCache($this->cacheKey, $cacheData, false);
            return $cacheData;
        }

        if ($mode === 'get') {
            return Framework::checkCache($this->cacheKey);
        }

        throw new \Exception("Mode must be one of 'set' or 'get'");
    }

    /**
     * Returns the data to be cached.
     * Note: If additional keys are added, logic will need to be added to `setPropertiesFromCache` below.
     * @return array An associative array of cache data
     */
    private function getCacheData() {
        return array(
            'name'         => $this->data['attrs']['name'],
            'inputName'    => $this->data['inputName'],
            'field'        => $this->field,
            'table'        => $this->table,
            'fieldName'    => $this->fieldName,
            'dataType'     => $this->dataType,
            'mask'         => isset($this->data['js']['mask']) && $this->data['js']['mask'] ? $this->data['js']['mask'] : null,
            'socialUserID' => isset($this->data['socialUserID']) && $this->data['socialUserID'] ? $this->data['socialUserID'] : null,
            'meta'         => serialize($this->fieldMetaData),
        );
    }

    /**
     * Sets class and data properties for recognized keys.
     * @param array $cacheData An associative array of cache data to set.
     */
    private function setPropertiesFromCache(array $cacheData) {
        foreach($cacheData as $key => $value) {
            switch($key) {
                case 'name':
                    $this->data['attrs']['name'] = $value;
                    break;
                case 'inputName':
                    $this->data['inputName'] = $value;
                    $this->setInputName($this->data['inputName']);
                    break;
                case 'field':
                    $this->field = $value;
                    break;
                case 'table':
                    $this->table = $value;
                    break;
                case 'fieldName':
                    $this->fieldName = $value;
                    break;
                case 'dataType':
                    $this->dataType = $value;
                    break;
                case 'mask':
                    $this->data['js']['mask'] = $value;
                    break;
                case 'socialUserID':
                    $this->data['socialUserID'] = $value;
                    break;
                case 'meta':
                    $this->fieldMetaData = unserialize($value);
                    break;
                default:
                    if ($key !== 'error') {
                        echo $this->reportError("Unrecognized cache key: '$key'");
                    }
            }
        }
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
        // Note: This function intentionally _not_ named 'getSocialUserID' as `getObjectID` needs to match by $this->table which is 'Socialuser'.
        $user = null;
        if (Social::userCanEdit($this->fieldName)) {
            list($user) = Social::getUserAndSource();
        }
        return $this->data['socialUserID'] = $user ? $user->ID : null;
    }

    /**
     * Returns true if the field is read-only
     * Calls the  `isReadOnly{tablename}Field` method if present or returns false.
     * @return boolean
     */
    private function isReadOnly() {
        $method = "isReadOnly{$this->table}Field";
        return method_exists($this, $method) ? $this->$method() : false;
    }

    /**
     * Returns true if the Contact field is read-only
     * @return boolean
     */
    private function isReadOnlyContactField() {
        return Framework::isLoggedIn() &&
            ($this->CI->page === Config::getConfig(CP_CHAT_URL) ||
            (Config::getConfig(PTA_ENABLED) && $this->fieldName === 'Login') ||
            (Framework::isPta() && (!$this->data['attrs']['allow_external_login_updates'] || $this->fieldName === 'Login')));
    }

    /**
     * Sets the widget's mask if it should be set
     * @return void
     */
    private function setMask() {
        if ($mask = Connect::getMask($this->table, $this->data['attrs']['name'], $this->fieldMetaData))
            $this->data['js']['mask'] = $mask;
    }

    /**
     * Calculates if the current table/field combination crosses a boundary to a new primary object.
     *
     * @param string $name Full table/field name
     * @param string $table Table of current field, either 'Contact' or 'Incident' or 'Asset'
     * @param string $field Sub field off of $table
     *
     * @return bool Whether the current table/field is allowed to be used as an input element.
     */
    private function invalidSubObjectExists($name, $table, $field) {
        if (($table === 'Incident' && ($field === 'Product' || $field === 'Category' || $field === 'Asset')) ||
            ($table === 'Communityquestion' && ($field === 'Product' || $field === 'Category')) ||
            ($table === 'Contact' && $field === 'Country') ||
            ($table === 'Asset' && $field === 'Status') ||
            ($table === 'Asset' && $field === 'Product') ||
            (!$subObject = Connect::getPrimarySubObjectsFromField($name)) ||
            (($meta = $subObject[0]::getMetadata()) && $meta->is_menu) ||
            ($table === 'Contact' && $meta->COM_type === 'Organization' && $field !== 'Name')) {
                return false;
        }
        return true;
    }

    /**
     * Sets an error message in the cache for the current field and displays the error.
     *
     * @param string $errorMessage Error message
     * @return bool Always returns false to stop the widget from rendering
     */
    private function setErrorMessage($errorMessage) {
        $this->cache('set', array('error' => $errorMessage));
        echo $this->reportError($errorMessage);
        return false;
    }

    /**
     * Populates various data for the current field. This includes the various attributes that a custom field
     * can contain, as well as the default value for the widget if it doesn't already have a value.
     *
     * @return bool|null False if an error was found with the current field, null otherwise.
     */
    private function retrieveAndInitializeData() {
        if(Connect::isCustomField($this->fieldMetaData)){
            //If a custom field, ensure it's visible and check if it's required
            $customField = \RightNow\Utils\Framework::getCustomField($this->table, $this->fieldName);
            if (!$customField || !$customField['enduser_visible']) {
                 echo $this->reportError(sprintf(Config::getMessage(CF_PCT_S_FOUND_PCT_S_TABLE_MSG), $this->fieldName, $this->table));
                 return false;
            }
            else if (!$customField['enduser_writable']) {
                 echo $this->reportError(sprintf(Config::getMessage(PCT_S_READ_FIELD_INPUT_WIDGET_MSG), $this->fieldName));
                 return false;
            }
            $this->data['attrs']['required'] = ($customField['required'] === true) || $this->data['attrs']['required'];
        }
        if ($this->fieldMetaData->usageType) {
            // @codingStandardsIgnoreStart
            if ($this->fieldMetaData->usageType === ConnectPHP\PropertyUsage::EmailAddress) {
                $this->data['js']['email'] = true;
            }
            else if ($this->fieldMetaData->usageType === ConnectPHP\PropertyUsage::URI) {
                $this->data['js']['url'] = true;
            }
            // @codingStandardsIgnoreEnd
        }

        $this->data['readOnly'] = $this->isReadOnly();
        $this->data['value'] = $this->setFieldValue();

        if (!$this->data['readOnly']) {
            $this->setConstraints();
            $this->setAttributeDefaults();
            $this->setJavaScriptMembers();
            $this->setServerConstraints();
        }
    }

    /**
     * Sets JS data member.
     * @return void
     */
    private function setJavaScriptMembers() {
        $this->data['js'] = array_merge(array(
            'type' => $this->dataType,
            'name' => $this->data['inputName'],
            'custom' => Connect::isCustomField($this->fieldMetaData),
            'constraints' => $this->constraints,
        ), $this->data['js']);

        if(Connect::isCustomField($this->fieldMetaData) && ($hintField = trim($this->fieldMetaData->description ? $this->fieldMetaData->description : '')) && !$this->data['attrs']['hide_hint'] && $this->data['attrs']['hint'] === '') {
            $this->data['attrs']['hint'] = $hintField;
        }
    }

    /**
     * Add in any server-side constraints
     * @return void
     */
    private function setServerConstraints() {
        if(isset($this->data['attrs']['required']) && $this->data['attrs']['required']) {
            $this->data['constraints']['required'] = true;
        }

        foreach(array('label_input', 'label_error', 'label_validation') as $label) {
            if(isset($this->data['attrs'][$label])) {
                $this->data['constraints']['labels'][$label] = $this->data['attrs'][$label];
            }
        }
    }

    /**
     * Sets validation constraints.
     * @return void
     */
    private function setConstraints() {
        $constraints = array();
        if ($this->data['inputName'] === "Incident.Threads") {
            //Constraints for threads are only available on Thread->Text and not ThreadsArray
            $threadObject = CONNECT_NAMESPACE_PREFIX . '\\Thread';
            $fieldConstraints = $threadObject::getMetadata()->Text->constraints;
        }
        else {
            $fieldConstraints = $this->fieldMetaData->constraints;
        }
        if(is_array($fieldConstraints)){
            foreach ($fieldConstraints as $constraint) {
                if(!$connectConstraint = \RightNow\Utils\Validation::getConstraintByID($constraint->kind)) continue;

                $constraints[$connectConstraint] = $constraint->value;
                if(is_string($constraint->value)){
                    //Escape/transform some regex that is not understood by JavaScript as-is
                    $constraints[$connectConstraint] = str_replace(array('[][', '[:blank:]'), array('[\]\[', ' \t'), $constraint->value);
                }
            }
        }
        $this->constraints = $constraints;
    }

    /**
     * Returns an array of default values from 'url' and 'post'
     * @param string $fieldName The field name
     * @return array
     */
    private function getPostAndUrlDefaults($fieldName) {
        return array(
            'url' => Url::getParameter($fieldName),
            'post' => $this->CI->input->post(str_replace(".", "_", $fieldName ? $fieldName : '')),
        );
    }

    /**
     * Returns an array of default values from sources:
     *    'post'    - From post
     *    'url'     - From url parameters
     *    'attrs'   - From widget attributes
     *    'meta'    - From $this->fieldMetaData
     *    'dynamic' - If specified, one of 'url' or 'attrs', specifying that the
     *                source has a default and was determined to have priority.
     *
     * @param string $fieldName The field name
     * @return array
     */
    private function getDefaultValues($fieldName) {
        // 'post' and 'url'
        $defaults = $this->getPostAndUrlDefaults($fieldName);
        if ($defaults['post'] === false && $defaults['url'] === null) {
            // New format name wasn't found, check if old naming convention was found instead
            $defaults = $this->getPostAndUrlDefaults(Connect::mapNewFieldName($fieldName));
        }
        if ($defaults['post'] !== false) {
            $defaults['post'] = str_replace(array("'", '"'), array('&#039;', '&quot;'), $defaults['post']);
        }

        // 'meta' and 'attrs'
        $defaults = $defaults + array(
            'meta' => (is_object($this->fieldMetaData) && property_exists($this->fieldMetaData, 'default')) ? $this->fieldMetaData->default : '',
            'attrs' => (isset($this->data['attrs']['default_value'])) ? $this->data['attrs']['default_value'] : '',
        );

        // 'dynamic'
        if ($defaults['url'] !== null && $defaults['url'] !== '') {
            $defaults['dynamic'] = 'url';
        }
        else if ($defaults['attrs'] !== null && $defaults['attrs'] !== '') {
            $defaults['dynamic'] = 'attrs';
        }

        return $defaults;
    }

    /**
     * Returns a menu object. If $menuObject->ID is null and relevant defaults are present,
     * the specified menu object is fetched and returned. Otherwise, $menuObject is returned unmodified.
     * @param Object $menuObject The menu object.
     * @param array $defaults The defaults from getDefaultValues().
     * @return Object The menu object
     */
    private function getMenuFieldValue($menuObject, array $defaults) {
        $fieldValue = $menuObject;

        // The menu object may be already populated on a new instance
        // because a default value has been set for it via the console.
        // Rather than checking for existence of the menu object itself,
        // we will ignore any defaults passed into the function if the
        // instance already exists.
        $objectInstance = Connect::getObjectInstance($this->table);
        if (!$objectInstance || !$objectInstance->ID) {
            $defaultSource = null;
            if ($defaults['post'] !== false) {
                $defaultSource = 'post';
            }
            else if ($defaults['dynamic']) {
                $defaultSource = $defaults['dynamic'];
            }
            if ($defaultSource && ($defaultValue = $defaults[$defaultSource])) {
                $objectName = $this->fieldMetaData->type_name;
                $fieldValue = $objectName::fetch($defaultValue);
            }
        }

        return $fieldValue;
    }

    /**
     * Retrieves default value for field either out of the URL or based on
     * the attribute set.
     * @return string The value for the field
     */
    private function setFieldValue() {
        $fieldValue = null;
        $field = $this->field;
        $isArray = is_array($field) || Connect::isArray($field);
        $defaults = $this->getDefaultValues($this->data['attrs']['name']);

        // Custom attributes and custom fields can have default values specified for them.
        // So if there's a default value for the field but there's also a dynamic default value
        // coming in, that dynamic value should take precedence.
        if (!$isArray && $defaults['meta'] !== '' && isset($defaults['dynamic']) && $defaults['dynamic'] && !is_null($field) && (string) $field === (string) $defaults['meta']) {
            $this->field = $field = null;
        }

        if ($this->dataType === 'Menu') {
            // fieldValue is preserved as the actual Connect menu option object
            $fieldValue = $this->getMenuFieldValue($field, $defaults);
        }
        // If the field has a value (including '') sent in through POST, we should use that.
        // Generally only the case in the basic pageset.
        else if ($defaults['post'] !== false) {
            $fieldValue = $defaults['post'];
        }
        // Check if the field has an existing value.
        else if($field !== null && !$isArray) {
            if (is_bool($field) || is_int($field) || is_float($field)) {
                $fieldValue = $field;
            }
            else if (is_string($field)) {
                // only encode strings that are not html type
                if(\RightNow\Libraries\Formatter::isFormatHTMLType($this->fieldMetaData->usageType)) {
                    $fieldValue = $field;
                }
                else {
                    $fieldValue = htmlspecialchars($field ? $field : '', ENT_QUOTES, 'UTF-8', false);
                }
            }
        }
        // If the field has a value from the URL or widget attributes
        else if(isset($defaults['dynamic']) && $defaults['dynamic']) {
            $fieldValue = $defaults[$defaults['dynamic']];
        }
        else if ($isArray) {
            //Keep an existing array value as an array. It isn't up to this base class to determine what to do
            //with the data so it shouldn't be destroying data. The child widget is responsible for displaying the data to how it sees fit.
            $fieldValue = $field;
        }
        else {
            // Entity-ize any characters in the existing value in non-array types.
            $fieldValue = htmlspecialchars($field ? $field : '', ENT_QUOTES, 'UTF-8', false);
        }
        return $this->modifyDateValue($fieldValue);
    }

    /**
     * If the field is of type date or datetime and the value is specified, the value will be modified to the integer timestamp
     * or the empty string if the value does not parse correctly with `strtotime`.
     * @param mixed $value The value for the field
     * @return mixed The integer timestamp equivalent for a date/datetime field if it's set, an empty string for invalid
     * date/datetime fields, or the original passed in value.
     */
    private function modifyDateValue($value) {
        //Ignore fields that are not of type date or datetime or if the value is not specified
        if(!($this->dataType === 'Date' || $this->dataType === 'DateTime') || !is_string($value) || $value === '')
            return $value;
        //Check if the value found really is an integer in string form (i.e. a string of numbers). If so,
        //we have to assume it's a timestamp. Otherwise, pass it to strtotime and have it run its magic.
        if (is_string($value)) {
            return $value;
        }
        $integerValue = intval($value);
        if(strval($integerValue) === $value) {
            return $integerValue;
        }
        if(\RightNow\Utils\Text::isValidDate($value) === false)
            return '';
        return strtotime($value) ?: '';
    }

    /**
     * Any attributes modified in code in the base class should make those modifications
     * here so that the sub-classes can merge their attribute information onto
     * that of the cache without losing any information. This function is
     * executed either after a merge from the cache, or before the attributes
     * are set into the cache.
     * @return void
     */
    private function setAttributeDefaults() {
        if (isset($this->data['attrs']['label_input']) && $this->data['attrs']['label_input'] === '{default_label}') {
            $this->data['attrs']['label_input'] = $this->fieldMetaData->label !== 'NamedIDLabel' ? $this->fieldMetaData->label : $this->fieldName;
        }
        if (isset($this->data['attrs']['hint']) && $this->data['attrs']['hint'] && isset( $this->data['attrs']['hide_hint']) && $this->data['attrs']['hide_hint']) {
            $this->data['attrs']['hint'] = '';
        }
    }
}
