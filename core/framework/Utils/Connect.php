<?php
namespace RightNow\Utils;
use RightNow\Connect\v1_4 as ConnectPHP,
    RightNow\Connect\v1_2 as ConnectPHP1_2,
    RightNow\Api;

/**
 * Utility methods for handling Connect objects including parsing CP field names into their individual components. Also contains
 * numerous helper methods for checking Connect object types.
 */
final class Connect extends \RightNow\Internal\Utils\Connect {
    /**
     * For ease of validation, all supported primary object names are normalized to
     * a simple capitalization, even if the Connect names are actually Pascal-cased.
     *
     * Each item:
     *
     *     Connect object name:
     *         access: 'read' or 'read,write' (whether it can be updated or just displayed)
     *         model: Name of model to call the `#get` method to retrieve an instance
     *                 (not needed, if the same name as the Connect object name)
     *         parameter: URL parameter key to read the id of an object instance
     *         profile: Key in the profile cookie to retrieve the id of an object instance
     */
    private static $supportedObjects = array(
        'Answer' => array(
            'access'    => 'read',
            'parameter' => 'a_id',
        ),
        'Serviceproduct' => array(
            'access'    => 'read',
            'parameter' => 'p',
            'model'     => 'Prodcat',
        ),
        'Servicecategory' => array(
            'access'    => 'read',
            'parameter' => 'c',
            'model'     => 'Prodcat',
        ),
        'Incident' => array(
            'access'    => 'read,write',
            'parameter' => 'i_id',
        ),
        'Communityquestion' => array(
            'access'    => 'read,write',
            'parameter' => 'qid',
            'model'     => 'CommunityQuestion',
        ),
        'Communitycomment' => array(
            'access' => 'read,write',
            'model'  => 'CommunityComment',
        ),
        'Contact' => array(
            'access'  => 'read,write',
            'profile' => 'contactID',
        ),
        'Communityuser' => array(
            'access'    => 'read,write',
            'parameter' => 'user',
            'model'     => 'CommunityUser',
        ),
        'Asset' => array(
            'access'    => 'read,write',
            'parameter' => 'asset_id',
            'model'     => 'Asset',
        ),
    );

    /**
     * Get Connect objects supported by CP widgets and models.
     * @param string $objectName The Connect object name (e.g. 'Answer')
     * @return mixed Either all supported objects or the read/write support of a specific object, given `$objectName`
     */
    public static function getSupportedObjects($objectName = null) {
        static $supportedObjects;
        if (is_null($supportedObjects)) {
            $supportedObjects = array_map(function($item) { return $item['access']; }, self::$supportedObjects);
        }

        return $objectName === null ? $supportedObjects : $supportedObjects[ucfirst(strtolower($objectName))];
    }

    /**
     * Retrieve MetaData for a given Connect object.
     * @param string $objectName The Connect object name
     * @return object|null MetaData object for provided object name, or null if none exists
     */
    public static function retrieveMetaData($objectName) {
        if (!$objectName) return null;

        $objectName = self::prependNamespace($objectName);
        return $objectName::getMetaData();
    }

    /**
     * Converts old CP object names and fields to their new ConnectPHP equivalent
     * @param string $name Old CP field name
     * @return array Components of new Connect-style field name
     */
    public static function mapOldFieldName($name){
        $name = explode('.', $name, 2);
        $name[0] = parent::mapObjectName($name[0]);
        $name[1] = parent::mapObjectField($name[0], $name[1]);
        return implode(".", array_filter($name));
    }

    /**
     * Converts new CP object names and fields to their old CP equivalent
     * @param string $name New Connect-style field name
     * @return string|null The old CP field name
     */
    public static function mapNewFieldName($name){
        $name = explode('.', $name, 2);
        return parent::getOldObjectFieldName($name[0], $name[1]);
    }

    /**
     * Parse and validate a display field name attribute. Assumes the name has already been converted from the
     * old CP object name to the new ConnectPHP equivalent. Validates whether the specified object is one
     * that's supported by CP models and widgets.
     * @param string $name The field name attribute in the form table.field
     * @param bool $input Denotes if this field is going to be used for input
     * @return string|array String error message if attribute is invalid, or an array of the table and field parsed values
     */
    public static function parseFieldName($name, $input = false) {
        if (!$name || !is_string($name)) {
            return sprintf(Config::getMessage(PCT_S_ATTRIB_IS_REQUIRED_MSG), 'name');
        }

        $nameParts = explode('.', $name);
        if (count($nameParts) < 2) {
            return sprintf(Config::getMessage(FND_INV_VAL_NAME_ATTRIB_VAL_MSG), $name);
        }
        $objectName = $nameParts[0] = ucfirst(strtolower($nameParts[0]));
        $fieldName = $nameParts[1] = ucfirst($nameParts[1]);

        if ($fieldName === '') {
            // $name was something like 'Contact.'
            return Config::getMessage(FND_EMPTY_VAL_FLD_NAME_NAME_ATTRIB_MSG);
        }

        $accessForObject = self::getSupportedObjects($objectName);
        if ($input && (!$accessForObject || !Text::stringContains($accessForObject, 'write'))) {
            $validValues = array_keys(array_filter(self::$supportedObjects, function($obj) {
                return Text::stringContains($obj['access'], 'write');
            }));
            sort($validValues);
            return sprintf(Config::getMessage(INVALID_NAME_ATTRIBUTE_SUPPORTED_VALUES_MSG), $objectName, implode(', ', $validValues));
        }
        if (!$accessForObject) {
            $validValues = array_keys(self::$supportedObjects);
            sort($validValues);
            return sprintf(Config::getMessage(INVALID_NAME_ATTRIBUTE_SUPPORTED_VALUES_MSG), $objectName, implode(', ', $validValues));
        }

        return $nameParts;
    }

    /**
     * Returns an instance of a Connect object or null if one cannot be returned.
     * @param string $table The object type to return; supported values:
     * * Contact: Returns a blank or populated instance
     * * Incident: Returns a blank or populated instance
     * * Answer: Returns a populated instance or null
     * * Question: Returns a blank or populated instance
     * * ServiceProduct: Returns a populated instance or null
     * * ServiceCategory: Returns a populated instance or null
     * @param boolean|number|string $id Specify true to return only a blank instance of the object.
     *          Specify false to read the record id out of the URL (default).
     *          Specify a record id to retrieve that record.
     * @return object|null Connect object or null.
     *          If an ID is specified (via $id or URL parameter) but doesn't resolve to a valid record, a blank object instance is returned (or NULL if read-only)
     *          If an ID isn't specified (via $id or URL parameter) and the object is read-only, then NULL is returned
     */
    public static function getObjectInstance($table, $id = false) {
        if (!is_string($table)) return;

        $table = ucfirst(strtolower($table));

        if (!$supportedObject = self::$supportedObjects[$table]) return;

        $requestingABlankObject = ($id === true);
        $readOnlyObject = ($supportedObject['access'] === 'read');

        if (!$requestingABlankObject) {
            $objectID = (is_string($id) || is_int($id)) ? $id : null;
            if (!$objectID && (isset($supportedObject['parameter']) && $urlParameterKey = $supportedObject['parameter'])) {
                // If the object can be retrieved via the URL then $id can be passed in to override the URL value.
                $objectID = Url::getParameter($urlParameterKey);
            }
            else if (isset($supportedObject['profile']) && $profileField = $supportedObject['profile']) {
                // If the object is retrieved via a session value, then it cannot be overridden by $id.
                $objectID = get_instance()->session->getProfileData($profileField);
            }
            $objectID = Text::extractCommaSeparatedID($objectID) ?: null;
        }

        if (!$readOnlyObject || $objectID) {
            return self::getObjectByIDOrBlank(isset($supportedObject['model']) && $supportedObject['model'] ? $supportedObject['model'] : $table, $objectID);
        }
    }

    /**
    * Sets the value of the specified field to the specified value.
    *
    * @param ConnectPHP\RNObject $connectObject Primary object to set the value on [an example](http://example.com/ "Title")
    * @param array $fieldComponents Denotes what object, field, and sub-field whose value should be set
    *      e.g. array('Contact', 'Login'), array('Incident', 'StatusType', 'Status')
    * @param mixed $value The value to set
    * @return void
    * @throws \Exception If the specified object or field is incorrect
    * @throws ConnectPHP\ConnectAPIErrorBase If the specified value is of an incorrect type
    */
    public static function setFieldValue(ConnectPHP\RNObject $connectObject, array $fieldComponents, $value) {
        $primaryObjectName = array_shift($fieldComponents);

        self::guardObjectType($connectObject, $primaryObjectName);

        $metaData = $connectObject::getMetadata();
        $deepest = count($fieldComponents);
        $fieldName = current($fieldComponents);
        $currentLevel = 1;
        while ($currentLevel < $deepest) {
            if ($newConnectObject = self::fetchFromArray($connectObject, $fieldName)) {
                // remove existing object
                if ($value === null || $value === '') {
                    foreach($connectObject as $index => $subObject) {
                        if ($subObject === $newConnectObject) {
                            $connectObject->offsetUnset((int)$index);
                        }
                    }
                    return;
                }
                $metaData = $metaData->$fieldName;
                $connectObject = &$newConnectObject;
                unset($newConnectObject);
            }
            else if (is_int($arrayIndex = self::getIndexFromField($fieldName, self::getClassSuffix($connectObject)))) {
                // don't create existing object if there is no value to set
                if ($value === null || $value === '') {
                    return;
                }
                $subObject = self::createArrayElement(self::prependNamespace($metaData->COM_type), $arrayIndex);
                $connectObject[] = $subObject;
                $connectObject = &$subObject;
            }
            else if (property_exists($connectObject, $fieldName)) {
                $metaData = $metaData->$fieldName;
                $connectObject = &$connectObject->$fieldName;
            }
            else {
                throw new \Exception(sprintf(Config::getMessage(PCT_S_EXIST_PRIMARY_OBJECT_PCT_S_LBL), $fieldName, $primaryObjectName));
            }
            $fieldName = next($fieldComponents);
            $currentLevel++;
        }
        $metaData = $connectObject::getMetadata()->$fieldName;
        if($metaData->COM_type === 'Organization'){
            $connectObject->$fieldName = ConnectPHP\Organization::fetch($value);
        }
        else if($metaData->is_menu){
            if ($value === '' || $value === null) {
                $menuValue = null;
            }
            else {
                $menuObject = $metaData->type_name;
                $menuValue = $menuObject::fetch($value);
            }
            $connectObject->$fieldName = $menuValue;
        }
        else{
            // will sanitize content with $metaData->usageType === ConnectPHP\PropertyUsage::HTML
            $value = \RightNow\Libraries\Formatter::formatHTMLUsageType($value, $metaData);
            $connectObject->$fieldName = $value;
        }

    }

    /**
     * Returns the specified field and its metadata.
     * @param array $fieldComponents Denotes what object, field, and sub-field (if applicable) to retrieve
     *      e.g. array('Contact', 'Login'), array('Incident', 'StatusType', 'Status')
     * @param ConnectPHP\RNObject|number|null $connectObject Optional instance of Connect object that possesses the field,
     *      or an id of the record to retrieve in order to access the field.
     * @return array List of field value (object or literal) and field metadata if field was found, string if not
     * @throws \Exception If the specified field is not found on the primary object or if the given object
     *      doesn't match the class name specified in $fieldComponents
     */
    public static function getObjectField(array $fieldComponents, $connectObject = null) {
        $primaryObjectName = array_shift($fieldComponents);
        self::guardObjectType($connectObject, $primaryObjectName);
        if (!is_object($connectObject)) {
            $connectObject = self::getObjectInstance($primaryObjectName, (is_string($connectObject) || is_int($connectObject)) ? $connectObject : false);
        }
        if ($connectObject === null) {
            throw new \Exception(sprintf(Config::getMessage(PRIMARY_OBJECT_PCT_S_SUPPORTED_MSG), $primaryObjectName));
        }
        $metaData = $connectObject::getMetadata();
        try {
            return self::find($fieldComponents, $metaData, $connectObject);
        }
        catch (\Exception $e) {
            // field wasn't found
            throw new \Exception(sprintf(Config::getMessage(PCT_S_FLD_DOESNT_EX_PCT_S_PRIM_LBL), implode('.', $fieldComponents), $primaryObjectName));
        }
    }

    /**
     * Populate lazy-loading field values by referencing them.
     *
     * @param array $fieldNames A list of Connect field names to be referenced so they will be populated in the json array.
     *     Field Name Examples:
     *         - 'UpdatedTime'
     *         - 'Address.Country.LookupName'
     *         - 'Provinces.*.[ID, DisplayOrder, Name]' (Lookup all Provinces, populating the specified fields)
     *         - 'Provinces.*.Names.*.LabelText' (Lookup all Provinces, then all Names within Provinces, populating the 'LabelText' field)
     *
     * @param object $connectObject Instance of the Connect or KFAPI object that possesses the field(s)
     * @return void
     */
    public static function populateFieldValues(array $fieldNames, $connectObject) {
        if(!is_object($connectObject)){
            return;
        }
        foreach ($fieldNames as $field) {
            try {
                if (property_exists($connectObject, $field)) {
                    $connectObject->$field;
                }
                else if (Text::stringContains($field, '.*.')) {
                    self::populateFieldsRecursively(explode('.*.', $field), $connectObject);
                }
                else if ($connectName = self::getClassSuffix($connectObject)) {
                    // Attempt to lookup any sub-objects (e.g. Emails.0.Address)
                    self::getObjectField(array_merge(array($connectName), explode('.', $field)), $connectObject);
                }
            }
            catch (\Exception $e) {
                //Continue on to next field name if this one fails
            }
        }
    }

    /**
     * Returns the formatted value of a field given the table and field to look for. If an error
     * occurred, it will be displayed in the development header.
     *
     * @param array $fieldComponents The Connect object to search within
     * @param bool $highlight Denotes if content should be highlighted with the URL 'kw' parameter
     * @param number $id Optional id of the record to display; if not specified, the ID is read out of the URL or a blank
     *          field value is returned
     * @return string|null Formatted value or null if an error occurred
     * @internal
     */
    public static function getFormattedObjectFieldValue($fieldComponents, $highlight, $id = null){
        try {
            list($fieldValue, $fieldMetaData) = self::getObjectField($fieldComponents, $id);
        }
        catch (\Exception $e) {
            Framework::addErrorToPageAndHeader(sprintf(Config::getMessage(ERROR_WITH_RN_FIELD_TAG_PCT_S_LBL), $e->getMessage()));
            return null;
        }
        $fieldValue = \RightNow\Libraries\Formatter::formatField($fieldValue, $fieldMetaData, $highlight);

        if(is_object($fieldValue)){
            $error = sprintf(Config::getMessage(ERR_RN_FLD_TG_FLD_PCT_S_COMPLEX_MSG), implode(".", $fieldComponents));
            if (!Text::endsWith($fieldMetaData->COM_type, '_menu_list')) {
                // The DataDisplay widget currently does not work with menu type sub-fields.
                $error .= ' ' . Config::getMessage(PLS_OUTPUT_S_DATADISPLAY_WIDGET_MSG);
            }
            Framework::addErrorToPageAndHeader($error, false);
            return null;
        }

        if(self::isCustomField($fieldMetaData) && !Framework::isCustomFieldEnduserVisible($fieldComponents[0], $fieldMetaData->name)) {
            return null;
        }

        return $fieldValue;
    }

    /**
     * Determines if the passed in Connect object is of type FileAttachment by checking it's base class.
     * @param object $connectObject Instance of object to check
     * @return bool bool true if object is an instance of a file attachment
     */
    public static function isFileAttachmentType($connectObject){
        return is_object($connectObject) && (
            $connectObject instanceof ConnectPHP\FileAttachmentAnswerArray ||
            $connectObject instanceof ConnectPHP\FileAttachmentIncidentArray ||
            $connectObject instanceof ConnectPHP\FileAttachmentCommunityArray ||
            $connectObject instanceof ConnectPHP\FileAttachmentCommon ||
            $connectObject instanceof ConnectPHP\FileAttachmentSharedArray ||
            $connectObject instanceof ConnectPHP1_2\FileAttachmentAnswerArray ||
            $connectObject instanceof ConnectPHP1_2\FileAttachmentIncidentArray ||
            $connectObject instanceof ConnectPHP1_2\FileAttachmentCommon ||
            $connectObject instanceof ConnectPHP1_2\FileAttachmentSharedArray);
    }

    /**
     * Determines if the passed in Connect object is of type ServiceProduct or ServiceCategory by checking it's base class. Returns
     * a string designating it's type or null if the object is not a product or category field.
     * @param object $connectObject Instance of object to check
     * @return mixed String 'product' or 'category' if value is one of the two, false otherwise
     */
    public static function getProductCategoryType($connectObject){
        if(!is_object($connectObject)){
            return false;
        }
        if($connectObject instanceof ConnectPHP\ServiceProduct || $connectObject instanceof ConnectPHP\ServiceProductArray
            || $connectObject instanceof ConnectPHP1_2\ServiceProduct || $connectObject instanceof ConnectPHP1_2\ServiceProductArray)
            return "product";
        if($connectObject instanceof ConnectPHP\ServiceCategory || $connectObject instanceof ConnectPHP\ServiceCategoryArray
            || $connectObject instanceof ConnectPHP1_2\ServiceCategory || $connectObject instanceof ConnectPHP1_2\ServiceCategoryArray)
            return "category";
        return false;
    }

    /**
    * Returns an array of named values for NamedIDOptList, NamedIDLabel, and Country fields.
    * @param string $objectName Name of the Core Object that the field exists off of (e.g. 'Contact')
    * @param string $fieldName Name of the field to retrieve named values for (e.g. 'Address.Country')
    * @return array|null the field's named values or null if the field doesn't have named values
    * @throws ConnectPHP\ConnectAPIErrorBase if the specified object name or field name doesn't exist
    */
    public static function getNamedValues($objectName, $fieldName) {
        return ConnectPHP\ConnectAPI::getNamedValues(self::prependNamespace($objectName), $fieldName);
    }

    /**
     * Determines if the passed in Connect object or metadata about the object is a NamedID type object (either NamedIDOptList or NamedIDLabel)
     * @param object $connectObjectOrMetadata Instance of object to check
     * @return bool True if object is an instance of a NamedID object
     */
    public static function isNamedIDType($connectObjectOrMetadata){
        if(!is_object($connectObjectOrMetadata)){
            return false;
        }
        if(property_exists($connectObjectOrMetadata, 'COM_type')){
            return $connectObjectOrMetadata->COM_type === 'NamedIDOptList' || $connectObjectOrMetadata->COM_type === 'NamedIDLabel';
        }
        return ($connectObjectOrMetadata instanceof ConnectPHP\NamedIDOptList) || ($connectObjectOrMetadata instanceof ConnectPHP1_2\NamedIDOptList) || ($connectObjectOrMetadata instanceof ConnectPHP\NamedIDLabel) || ($connectObjectOrMetadata instanceof ConnectPHP1_2\NamedIDLabel);
    }

    /**
     * True or false if the given Connect object is type ProductNotification
     * @param object $connectObject Connect instance
     * @return boolean
     */
    public static function isProductNotificationType($connectObject){
        return is_object($connectObject) && $connectObject instanceof ConnectPHP\ProductNotification;
    }

    /**
     * Determines if the passed in Connect object is a Country object
     * @param object $connectObject Instance of object to check
     * @return bool True if object is an instance of a Country object
     */
    public static function isCountryType($connectObject){
        return is_object($connectObject) && $connectObject instanceof ConnectPHP\Country;
    }

    /**
     * Determines if the passed in Connect object is a SLAInstance object
     * @param object $connectObject Instance of object to check
     * @return bool True if object is an instance of a SLAInstance object
     */
    public static function isSlaInstanceType($connectObject){
        return is_object($connectObject) && ($connectObject instanceof ConnectPHP\AssignedSLAInstance || $connectObject instanceof ConnectPHP\SLAInstance);
    }

    /**
     * Determines if the passed in Connect object is an Incident thread type object
     * @param object $connectObject Instance of object to check
     * @return bool True if object is an instance of an Incident thread object
     */
    public static function isIncidentThreadType($connectObject){
        return is_object($connectObject) && ($connectObject instanceof ConnectPHP\ThreadArray || $connectObject instanceof ConnectPHP\Thread);
    }

    /**
     * Determines if the passed in Connect object is an Question comment type object
     * @param object $connectObject Instance of object to check
     * @return bool True if object is an instance of an Question comment object
     */
    public static function isQuestionCommentType($connectObject){
        return is_object($connectObject) && ($connectObject instanceof ConnectPHP\CommentArray || $connectObject instanceof ConnectPHP\Comment);
    }

    /**
     * Determines if the passed in Connect object is ConnectArray object
     * @param object $connectObject Instance of object to check
     * @return bool true if object is an instance of a ConnectArray object
     */
    public static function isArray($connectObject){
        return (($connectObject instanceof ConnectPHP\ConnectArray) || ($connectObject instanceof ConnectPHP1_2\ConnectArray));
    }

    /**
     * Determines if the passed in Connect object is of type SalesProduct by checking it's base class.
     * @param object $connectObject Instance of object to check
     * @return bool True if object is an instance of SalesProduct
     */
    public static function isProductCatalogType($connectObject) {
        return is_object($connectObject) && $connectObject instanceof ConnectPHP\SalesProduct;
    }

    /**
     * Determines if the passed in Connect object is an Asset object
     * @param object $connectObject Instance of object to check
     * @return bool True if object is an instance of an Asset object
     */
    public static function isAssetType($connectObject) {
        return is_object($connectObject) && $connectObject instanceof ConnectPHP\Asset;
    }

    /**
     * Determines if the field described by the given meta data object is a custom field.
     * @param object $metaData Metadata object containing info about the field
     * @return bool true if object is an instance of a Connect CustomField object
     */
    public static function isCustomField($metaData) {
        return is_object($metaData) && isset($metaData->container_class) && Text::endsWith($metaData->container_class, 'CustomFieldsc');
    }

    /**
     * Determines if the field described by the given meta data object is a custom attribute.
     *
     * **Note:** This method inspects the nearest object in the relationship chain if it cannot
     * be immediately determined (via the `container_class`) whether the object is a custom attribute.
     * This is liable to result in inaccurate results if the given meta data for a non-custom attribute menu-type
     * field that has a relationship defined with a custom attribute.
     *
     * @param object $metaData Metadata object containing info about the field
     * @return bool true if it appears that the object is an instance of a Connect CustomField object and is not a custom field.
     */
    public static function isCustomAttribute($metaData) {
        return (is_object($metaData) && (
                (isset($metaData->container_class) && Text::stringContains($metaData->container_class, 'CustomFields') && !self::isCustomField($metaData))
                 || (($relationships = isset($metaData->relationships[0]) ? $metaData->relationships[0] : null) && Text::stringContains($relationships->relationName, 'CustomFields'))));
    }

    /**
     * Determines if the field contained in the given field components is a channel field.
     * @param array $fieldComponents An array of the field name pieces
     * @return bool True if field is on the Contact table and a child of the ChannelUsername object
     */
    public static function isChannelField(array $fieldComponents) {
        return (reset($fieldComponents) === 'Contact') && Framework::inArrayCaseInsensitive($fieldComponents, 'ChannelUsernames');
    }

    /**
     * Determine if the given channel username field is end user visible
     * @param array $fieldComponents An array of the field name pieces
     * @return bool True if the field is part of the contact table, accesses the ChannelUsername object and is
     *         end user visible.
     */
    public static function isChannelFieldEndUserVisible(array $fieldComponents) {
        if(reset($fieldComponents) !== 'Contact') return false;

        while($component = array_pop($fieldComponents)) {
            //If this is a channel field and we have an ID or Name, check if it's visible
            if(strcasecmp($component, 'ChannelUsernames') === 0 && $lastComponent) {
                $visibleChannels = get_instance()->model('Contact')->getChannelTypes()->result;
                if(!is_int($lastComponent)) {
                    $arrayFieldAliases = self::getArrayFieldAliases();
                    $lastComponent = $arrayFieldAliases['ChannelUsernameArray'][strtoupper($lastComponent)];
                }
                return ($visibleChannels[$lastComponent]) ? true : false;
            }
            $lastComponent = $component;
        }
        return false;
    }

    /**
    * Converts the specified value into the correct data type.
    * @param string|int|bool $fieldValue The value of a particular field
    * @param object $fieldMetaData The Connect/_metadata object for the field
    * @return string|null The value converted to the correct data type
    */
    public static function castValue($fieldValue, $fieldMetaData) {
        $dataType = Text::cStrToLower($fieldMetaData->COM_type);

        switch ($dataType) {
            case 'string':
                //Change any null passwords to empty string
                if($fieldMetaData->name === 'NewPassword') {
                    return $fieldValue;
                }
                if($fieldValue === null || is_bool($fieldValue) || ($trimmed = trim($fieldValue)) === ''){
                    // API doesn't accept empty string. It's either non-empty or null.
                    return null;
                }
                return $trimmed;
            case 'boolean':
                if (!is_bool($fieldValue)) {
                    if (is_int($fieldValue)) {
                        $fieldValue = (bool) $fieldValue;
                    }
                    else if (is_string($fieldValue)) {
                        //bool fields that are not specified are sent in as empty strings. Don't
                        //convert that to false as it essentially means 'not specified'.
                        if($fieldValue === ''){
                            $fieldValue = null;
                        }
                        else{
                            $fieldValue = (Framework::inArrayCaseInsensitive(array('true', '1'), $fieldValue));
                        }
                    }
                }
                return $fieldValue;
            case 'integer':
                if(is_null($fieldValue) || $fieldValue === '') {
                    return null;
                }
                return is_int($fieldValue) ? $fieldValue : intval($fieldValue);
            case 'namedidlabel':
            case 'namedidoptlist':
            case 'country':
            case 'stateorprovince':
            case 'servicecategory':
            case 'serviceproduct':
            case 'assignedslainstance':
            case 'communityquestion':
            case 'communitycomment':
            case 'communityuser':
            case 'siteinterface':
            case 'contact':
                //The best possible case is that the value looks like a positive integer already so convert it to an int and return
                if(Framework::isValidID($fieldValue)){
                    return intval($fieldValue);
                }
                //The less ideal case is if calling intval still returns us a positive value (unless it is supposed to be an array).
                //This means we support getting values like '27.1', but we're nice like that.
                else if(($castedInt = intval($fieldValue)) > 0 && (!is_array($fieldValue) || (is_array($fieldValue) && $fieldMetaData->is_list === false))){
                    return $castedInt;
                }
                //Finally, if the value is falsey, just ignore it and don't set the field. Otherwise, we have no idea what they
                //were trying to do (what did you mean by -8?) so return the value, knowing that is likely going to cause an error.
                else if(!$fieldValue){
                    return null;
                }
                return $fieldValue;
            case 'date':
            case 'datetime':
                if(strlen($fieldValue) == 0 || $fieldValue == null)
                    $fieldValue = null;
                else if (!is_numeric($fieldValue)) {
                    $dt = new \DateTime($fieldValue);
                    $fieldValue = ($dataType === "date") ? $dt->format('Y-m-d') : $dt->format('Y-m-d H:i:s');
                }
                return $fieldValue;
            case 'fileattachment':
            case 'fileattachmentincident':
                if(!is_array($fieldValue)){
                    return null;
                }
                return $fieldValue;
            case 'fileattachmentcommunity':
                if(!is_array($fieldValue) && !is_object($fieldValue)){
                    return null;
                }
                return (array) $fieldValue;
            default:
                return trim(isset($fieldValue) ? $fieldValue : '');
        }
    }

    /**
     * Determine the country mask field based upon the field name. If the field doesn't have an associated mask field, returns null.
     * @param string $fieldName The field name being checked
     * @return string|null The string for the field or null
     */
    private static function getPhoneOrPostalMaskName($fieldName) {
        if (Text::beginsWith($fieldName, 'Contact.Phones.')) {
            return 'PhoneMask';
        }
        if ($fieldName === "Contact.Address.PostalCode") {
            return 'PostalMask';
        }
    }

    /**
     * If the given field has a mask, check that it is valid and strip off the mask.
     * @param string $fieldName The field name (e.g. Contact.Login)
     * @param string $value The value of the field with a mask applied
     * @param object $fieldMetadata The Connect PHP metadata for the field
     * @param object $country The country form field if available
     * @return string Either the original value, or the stripped value
     */
    public static function checkAndStripMask($fieldName, $value, $fieldMetadata, $country = null) {
        if($country && $country->value && ($result = get_instance()->model('Country')->get($country->value)->result)) {
            if(($maskName = self::getPhoneOrPostalMaskName($fieldName)) && $result->$maskName) {
                $mask = $result->$maskName;
            }
        }

        if((isset($mask) && $mask) || $mask = self::getMask(Text::getSubstringBefore($fieldName, '.'), $fieldName, $fieldMetadata)) {
            if(!$errors = Text::validateInputMask($value, $mask)) {
                return Text::stripInputMask($value, $mask);
            }
        }
        return $value;
    }

    /**
     * Return the corresponding sub-object or field specified by $index and $fieldName using Connect's fetch method.
     *
     * @param object|null $arrayObject A Connect array object (e.g. $contact->Emails)
     * @param mixed $index Index can be one of:
     *                     - Numerical index (e.g. 0 or '0' for 'Email - Primary')
     *                     - String alias (e.g. PRIMARY) where PRIMARY is aliased in getArrayFieldAliases
     * @param string|null $fieldName The name of the field to return. If not specified, the array object specified by $index is returned.
     * @return mixed Returns a Connect Object if exists and $fieldName is null, or the value of $fieldName if specified.
     *               Returns null if $arrayObject is null (as array fields like Contact.Emails are null if not defined),
     *               or if object or fieldName does not exist.
     *
     * @throws \Exception if $arrayObject is not a Connect object.
     */
    public static function fetchFromArray($arrayObject, $index, $fieldName = null) {
        if ($arrayObject === null) {
            return;
        }

        if (!$arrayName = self::getClassSuffix($arrayObject)) {
            throw new \Exception(Config::getMessage(ARRAYOBJECT_APPEAR_VALID_CONN_MSG));
        }
        if (method_exists($arrayObject, 'fetch') && is_int($numericIndex = self::getIndexFromField($index, $arrayName))) {
            try {
                if ($object = $arrayObject->fetch($numericIndex)) {
                    return ($fieldName) ? $object->$fieldName : $object;
                }
            }
            catch (\Exception $e) {
                // Connect throws an exception on fetch if the NamedIDOptList (e.g $contact->Emails[0].AddressType.ID) is not set.
            }
        }
    }

    /**
     * Returns a mapping of array field aliases to their numeric index.
     * The array keys correspond to the Connect object class name.
     *
     * @return array List of aliases
     */
    public static function getArrayFieldAliases() {
        static $aliases;
        if (!$aliases) {
            $aliases = array(
                'EmailArray' => array(
                    'PRIMARY' => CONNECT_EMAIL_PRIMARY,
                    'ALT1' => CONNECT_EMAIL_ALT1,
                    'ALT2' => CONNECT_EMAIL_ALT2,
                ),
                'PhoneArray' => array(
                    'OFFICE' => PHONE_OFFICE,
                    'MOBILE' => PHONE_MOBILE,
                    'FAX' => PHONE_FAX,
                    'ASST' => PHONE_ASST,
                    'HOME' => PHONE_HOME,
                    'ALT1' => PHONE_ALT1,
                    'ALT2' => PHONE_ALT2,
                ),
                'ChannelUsernameArray' => array(
                    'TWITTER' => CHAN_TWITTER,
                    'YOUTUBE' => CHAN_YOUTUBE,
                    'FACEBOOK' => CHAN_FACEBOOK,
                ),
            );
        }
        return $aliases;
    }

    /**
     * Returns an array of primary Connect objects from $field.
     *
     * @param string $fieldName A period delimited string of fields (e.g. 'Incident.PrimaryContact.Emails.PRIMARY.Address')
     *                   The first field should be a primary object and will NOT be included in the returned array.
     * @param bool $returnAll If true, return all primary sub objects, else return only the first object found.
     * @return array Primary Connect objects contained within the $fieldName
     */
    public static function getPrimarySubObjectsFromField($fieldName, $returnAll = false) {
        if (!$fieldName || !is_string($fieldName) ||
            !($elements = explode('.', $fieldName)) ||
            !($table = array_shift($elements)) ||
            !($objectName = self::prependNamespace($table)) ||
            !method_exists($objectName, 'getMetadata') ||
            !($meta = $objectName::getMetadata())) {
                return array();
        }
        $objects = $lookup = array();
        $object = new $objectName();
        foreach($elements as $element) {
            $lookup[] = $element;
            try {
                $result = self::find($lookup, $meta, $object);
            }
            catch (\Exception $e) {
                break; // field likely does not exist.
            }
            if ($result && $result[1]->is_primary) {
                $objects[] = $result[0];
                if (!$returnAll) {
                    break;
                }
            }
        }
        return $objects;
    }

    /**
    * Returns a mask for the current field, if available
    * @param string $table Table name
    * @param string $fieldName Full field name (e.g. Contact.Address.PostalCode)
    * @param object $fieldMetaData MetaData associated with the field
    * @return string|null Mask if exists or null
    */
    public static function getMask($table, $fieldName, $fieldMetaData) {
        if ($fieldMetaData->inputMask) {
            return $fieldMetaData->inputMask;
        }

        if ($countryFieldName = self::getPhoneOrPostalMaskName($fieldName)) {
            $objectInstance = self::getObjectInstance($table);
            if ($objectInstance instanceof ConnectPHP\Contact && (isset($objectInstance->Address->Country->$countryFieldName) && $objectInstance->Address->Country->$countryFieldName))
                return $objectInstance->Address->Country->$countryFieldName;
        }

        return null;
    }

    /**
     * Iterates through the custom fields and attributes of $connectObject and sets any default values found.
     *
     * @param ConnectPHP\RNObject $connectObject A primary Connect object having CustomFields such as Contact or Incident.
     * @return void
     */
    public static function setCustomFieldDefaults(ConnectPHP\RNObject $connectObject) {
        $customFields = $connectObject::getMetadata()->COM_type . 'CustomFields';
        $objectName = self::prependNamespace($customFields);
        $connectObject->CustomFields = new $objectName();
        foreach($connectObject->CustomFields as $custom => $value) {
            $objectName = self::prependNamespace("$customFields{$custom}");
            $object = $connectObject->CustomFields->{$custom} = new $objectName();
            $meta = $object::getMetadata();
            foreach($object as $fieldName => $value) {
                $fieldMeta = $meta->$fieldName;
                if(!$fieldMeta->is_read_only_for_update && $fieldMeta->default !== null) {
                    $object->{$fieldName} = $fieldMeta->default;
                }
            }
        }
    }

    /**
     * Used to quickly run the Connect API hasPermission call on the provided object with the provided permission ID
     * @param  ConnectPHP\RNObject $connectObject Object on which to check permission
     * @param  int|string|ConnectPHP\NamedID   $permission  Permission to check
     * @return bool Whether or not the provided object has the permission
     */
    public static function hasPermission(ConnectPHP\RNObject $connectObject, $permission){
        if (!method_exists($connectObject, 'hasPermission')) return false;

        try {
            return $connectObject->hasPermission($permission);
        }
        catch (ConnectPHP\ConnectAPIError $e) {
            // an exception here typically means the object did not have enough information to
            // evaluate the permission, but that is normal in some circumstances such as
            // creating a question without product or category and just means the user
            // doesn't have the permission
            return false;
        }
    }

    /**
     * Recursively loop through an object, populating the specified fieldName(s).
     *
     * @param array $fieldNames A list of field names.
     *     Examples:
     *         *array('Provinces', 'Names', 'LabelText)
     *         *array('Provinces', '[ID, DisplayOrder, Name]')
     * @param object $object An iterable Connect object.
     * @return void
     */
    private static function populateFieldsRecursively(array $fieldNames, $object) {
        $fieldName = array_shift($fieldNames);

        // iterate through any subFields within brackets []
        if (Text::beginsWith($fieldName, '[') && Text::endsWith($fieldName, ']')) {
            foreach (explode(',', substr($fieldName, 1, -1)) as $subField) {
                $subField = trim($subField);
                $object->$subField;
            }
            return;
        }

        $objectOrField = $object->$fieldName;
        if (is_object($objectOrField)) {
            foreach($objectOrField as $subObject) {
                self::populateFieldsRecursively($fieldNames, $subObject);
            }
        }
    }

    /**
     * Returns an instance of ConnectPHP\Answer for the answer specified
     * by the a_id URL parameter or null if there is no a_id URL parameter.
     * @return null|ConnectPHP\Answer Answer object or null
     */
    private static function getAnswer() {
        if (($answerID = Url::getParameter('a_id')) === null) return;

        return get_instance()->model('Answer')->get($answerID)->result;
    }

    /**
     * Returns an instance of ConnectPHP\Contact populated with:
     *
     * * The currently logged-in user
     * * Blank object if no user is logged-in
     *
     * @param boolean $blankObjectOnly If true, this method will always return a blank object instance
     * @return ConnectPHP\Contact Contact object
     */
    private static function getContact($blankObjectOnly = false) {
        return self::getObjectByIDOrBlank('Contact', ($blankObjectOnly) ? null : get_instance()->session->getProfileData('contactID'));
    }

    /**
     * Returns an instance of ConnectPHP\Incident populated with:
     *
     * * Incident specified via i_id URL parameter
     * * Blank object if no i_id URL parameter is present
     *
     * @param boolean $blankObjectOnly If true, this method will always return a blank object instance
     * @return ConnectPHP\Incident Incident object
     */
    private static function getIncident($blankObjectOnly = false) {
        return self::getObjectByIDOrBlank('Incident', ($blankObjectOnly) ? null : Url::getParameter('i_id'));
    }

    /**
      * Returns an instance of ConnectPHP\Asset populated with:
      *
      * * Asset specified via asset_id URL parameter
      * * Blank object if no asset_id URL parameter is present
      *
      * @return ConnectPHP\Asset Asset object
      */
    private static function getAsset() {
        return self::getObjectByIDOrBlank('Asset', Url::getParameter('asset_id'));
    }

    /**
     * Returns an instance of the specified ConnectPHP object with the specified
     * ID. Falls back to a blank ConnectPHP object if specified ID is not found.
     * If the model does not provide blank objects, or the object is not enduser
     * visible, null will be returned.
     * @param string $name Name of a legit model
     * @param string|int|null $id Numeric id of a record; if falsey,
     * a blank object instance is returned
     * @return object|null Instance populated with data from the specified id, blank
     * if $id is falsey or the retrieval of the id-ed object didn't return anything,
     * or null otherwise.
     */
    private static function getObjectByIDOrBlank($name, $id) {
        $model = get_instance()->model($name);

        if ($id) {
            $result = $model->get($id)->result;
        }
        if ((!isset($result) || !$result) && method_exists($model, 'getBlank')) {
            $result = $model->getBlank()->result;
        }
        if(($name !== 'Prodcat' || $model->isEnduserVisible($result->ID))) {
            return $result;
        }
    }

    /**
     * Throws an Exception if the specified object isn't an instance of the given class name.
     * @param Object $connectObject Instance of a Connect object
     * @param String $className Object's class name should match
     * @throws \Exception If $connectObject isn't an instance of $className
     */
    private static function guardObjectType($connectObject, $className) {
        $fullClassName = self::prependNamespace($className);
        if (is_object($connectObject) && !($connectObject instanceof $fullClassName)) {
            throw new \Exception(sprintf(Config::getMessage(OBJECT_ISNT_PCT_S_CLASS_LBL), $className));
        }
    }
}
