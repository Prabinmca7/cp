<?php

namespace RightNow\Internal\Utils;

use RightNow\Utils\Text,
    RightNow\Utils\Connect as ConnectExternal,
    RightNow\Connect\v1_4 as ConnectPHP;

/**
 * A collection of functions used to deal with Connect objects
 */
class Connect
{
    private static $tableMapping = array(
        'answers' => 'Answer',
        'answer' => 'Answer',
        'contacts' => 'Contact',
        'contact' => 'Contact',
        'incidents' => 'Incident',
        'incident' => 'Incident',
        'product' => 'ServiceProduct',
        'category' => 'ServiceCategory',
    );

    private static $answerFieldMapping = array(
        'a_id' => 'ID',
        'solution' => 'Solution',
        'summary' => 'Summary',
        'description' => 'Question',
        'products' => 'Products',
        'categories' => 'Categories',
        'fattach' => 'FileAttachments',
        'status_id' => 'StatusWithType',
        'lang_id' => 'Language',
        'created' => 'CreatedTime',
        'updated' => 'UpdatedTime',
        'type' => 'AnswerType',
        'url' => 'URL',
        'fileID' => 'FileAttachments.0.ID',
        'guideID' => 'GuidedAssistance.ID',
    );

    private static $contactFieldMapping = array(
        'c_id' => 'ID',
        'first_name' => 'Name.First',
        'last_name' => 'Name.Last',
        'full_name' => 'LookupName',
        'alt_first_name' => 'NameFurigana.First',
        'alt_last_name' => 'NameFurigana.Last',
        'disabled' => 'Disabled',
        'email' => 'Emails.PRIMARY.Address',
        'email_alt1' => 'Emails.ALT1.Address',
        'email_alt2' => 'Emails.ALT2.Address',
        'password_new' => 'NewPassword',
        'login' => 'Login',
        'organization_login' => 'Organization.Login',
        'organization_name' => 'Organization.Name',
        'organization_password' => 'Organization.NewPassword',
        'title' => 'Title',
        'ph_office' => 'Phones.OFFICE.Number',
        'ph_mobile' => 'Phones.MOBILE.Number',
        'ph_fax' => 'Phones.FAX.Number',
        'ph_asst' => 'Phones.ASST.Number',
        'ph_home' => 'Phones.HOME.Number',
        'street' => 'Address.Street',
        'city' => 'Address.City',
        'prov_id' => 'Address.StateOrProvince',
        'country_id' => 'Address.Country',
        'postal_code' => 'Address.PostalCode',
        'ma_opt_in' => 'MarketingSettings.MarketingOptIn',
        'survey_opt_in' => 'MarketingSettings.SurveyOptIn',
        'ma_mail_type' => 'MarketingSettings.EmailFormat',
        );
    private static $incidentFieldMapping = array(
        'i_id' => 'ID',
        'c_id' => 'PrimaryContact.ID',
        'org_id' => 'Organization.ID',
        'status_id' => 'StatusWithType.Status.ID',
        'contact_email' => 'PrimaryContact.Emails.PRIMARY.Address',
        'contact_first_name' => 'PrimaryContact.Name.First',
        'contact_last_name' => 'PrimaryContact.Name.Last',
        'ref_no' => 'ReferenceNumber',
        'subject' => 'Subject',
        'thread' => 'Threads',
        'prod' => 'Product',
        'cat' => 'Category',
        'created' => 'CreatedTime',
        'updated' => 'UpdatedTime',
        'closed' => 'ClosedTime',
        'sla' => 'SLAInstance',
        'status' => 'StatusWithType.Status',
        'fattach' => 'FileAttachments',
    );

    /**
     * Converts old CP object names to their new ConnectPHP equivalent
     * @param string $objectType The primary object type
     * @return string The mapped primary object type or the original name if no mapping was found
     */
    public static function mapObjectName($objectType){
        if(isset(self::$tableMapping[strtolower($objectType)]) && $mapping = self::$tableMapping[strtolower($objectType)]){
            return $mapping;
        }
        return $objectType;
    }

    /**
     * Converts old CP object fields to their new ConnectPHP equivalent, if available
     * @param string $objectType The primary object type
     * @param string $fieldName The sub field on the primary object
     * @return string The mapped field name. Unchanged if no mapping was found
     */
    public static function mapObjectField($objectType, $fieldName){
        $objectType = strtolower($objectType);
        if($objectType === 'answer' && isset(self::$answerFieldMapping[$fieldName]) && $mapping = self::$answerFieldMapping[$fieldName]){
            return $mapping;
        }
        if($objectType === 'contact' && isset(self::$contactFieldMapping[$fieldName]) && $mapping = self::$contactFieldMapping[$fieldName]){
            return $mapping;
        }
        if($objectType === 'incident' && isset(self::$incidentFieldMapping[$fieldName]) && $mapping = self::$incidentFieldMapping[$fieldName]){
            return $mapping;
        }
        if($customOrChannelFieldName = self::mapCustomOrChannelFieldName($fieldName))
            return $customOrChannelFieldName;
        return $fieldName;
    }

    /**
     * Maps a custom or channel field name to its correct Connect location
     * @param string $name The old custom or channel field name, starting with c$ or channel$
     * @return mixed The mapped name or null if field name is not in the correct custom/channel field format
     */
    public static function mapCustomOrChannelFieldName($name){
        if(Text::beginsWith($name, 'c$')){
            return "CustomFields.c." . Text::getSubstringAfter($name, 'c$');
        }
        if(Text::beginsWith($name, 'channel$')){
            $channelList = \RightNow\Utils\Connect::getArrayFieldAliases();
            if($channelName = array_search(intval(Text::getSubstringAfter($name, 'channel$')), $channelList['ChannelUsernameArray'])){
                return "ChannelUsernames.$channelName.Username";
            }
        }
        return null;
    }

    /**
     * Converts a new connect-style field name into the old CP based field name.
     * @param string $objectType The type of object, either Contact, Incident, or Answer
     * @param string $fieldName Name of the Connect field
     * @return string|null Old field name, including object type, or null if not found
     */
    public static function getOldObjectFieldName($objectType, $fieldName){
        $dynamicFieldName = null;
        if(Text::beginsWith($fieldName, 'CustomFields.c.')){
            $dynamicFieldName = 'c$' . Text::getSubstringAfter($fieldName, 'CustomFields.c.');
        }
        else if(Text::beginsWith($fieldName, 'ChannelUsernames')){
            $channelList = \RightNow\Utils\Connect::getArrayFieldAliases();
            if($channelDefine = $channelList['ChannelUsernameArray'][Text::getSubstringBefore(Text::getSubstringAfter($fieldName, 'ChannelUsernames.'), '.Username')]){
                $dynamicFieldName = 'channel$' . $channelDefine;
            }
        }
        if($objectType === 'Contact'){
            if(($oldName = $dynamicFieldName) || ($oldName = array_search($fieldName, self::$contactFieldMapping))){
                return "contacts.$oldName";
            }
        }
        else if($objectType === 'Incident'){
            if(($oldName = $dynamicFieldName) || ($oldName = array_search($fieldName, self::$incidentFieldMapping))){
                return "incidents.$oldName";
            }
        }
        else if($objectType === 'Answer'){
            if(($oldName = $dynamicFieldName) || ($oldName = array_search($fieldName, self::$answerFieldMapping))){
                return "answers.$oldName";
            }
        }
        return null;
    }

    /**
     * Performs a save on $connectObject, setting and releasing $source appropriately.
     *
     * @param ConnectPHP\RNObject $connectObject Object to save
     * @param int $source The source define.
     * @return void
     * @throws Exception from Connect if save or source calls fail.
     */
    public static function save(ConnectPHP\RNObject $connectObject, $source) {
        ConnectPHP\ConnectAPI::setSource($source, SRC1_EU, false);
        $connectObject = \RightNow\Libraries\Formatter::formatSafeObject($connectObject);
        $connectObject->save();
        ConnectPHP\ConnectAPI::releaseSource($source);
    }

    /**
    * Recursively retrieves a Connect object's field and its meta data. Normalizes meta data retrieval for
    * populated / unpopulated Connect objects.
    * @param array $lookup Each item is the name of a sub-field; each iteration removes the first item
    * @param object $metaData Connect _metaData object for the current object
    * @param object|null $object Parent object to process; each iteration goes to its sub-item
    * @param bool $isCustomField Indicates whether the sub-field is a custom field
    * @return array The first element is the field's value; the second element is the field's meta data
    * @throws \Exception If a field specified in $lookup is not found
    */
    public static function find(array $lookup, $metaData, $object, $isCustomField = false) {
        if (!$lookup) {
            // base case: done processing.
            // will sanitize content with $metaData->usageType === ConnectPHP\PropertyUsage::HTML
            $object = \RightNow\Libraries\Formatter::formatHTMLUsageType($object, $metaData);
            return array($object, $metaData);
        }
        $fieldName = array_shift($lookup);
        $field = isset($object->{$fieldName}) && $object->{$fieldName} ? $object->{$fieldName} : null;
        if ($isCustomField || (is_object($field) && ($fieldMetaData = $field::getMetadata())
            && (is_object($fieldMetaData) && !$fieldMetaData->is_list))) {
            // Metadata exists off of the (usually populated) field.
            // The metadata for custom fields is retrieved via `primaryObject->CustomFields->c::getMetadata()`;
            // metadata accesses for custom fields must be through that object.
            $fieldMetaData = ($isCustomField) ? $metaData->{$fieldName} : $fieldMetaData;
            //if we can't find the metadata for the custom field throw an exception
            if($isCustomField && $fieldMetaData === null) {
                throw new \Exception(sprintf(\RightNow\Utils\Config::getMessage(FIELD_PCT_S_DOES_NOT_EXIST_MSG), $fieldName));
            }
            if (Text::endsWith($fieldMetaData->COM_type, 'CustomFieldsc') && $fieldName === 'c') {
                // Set custom field flag so that subsequent calls know to re-use
                // the metadata coming from `object->CustomFields->c::getMetaData()` instead of
                // trying to get it from a field itself.
                $isCustomField = true;
            }
            return self::find($lookup, $fieldMetaData, $field, $isCustomField);
        }
        // Reaches here when:
        // -The last simple data type for the field has been reached
        // -The field is a populated Array-type field
        // -Proccessing an unpopulated Connect object, in which case, manually construct and retrieve
        // metadata for the next sub-item.
        $info = is_array($metaData) ? $metaData[$fieldName] : $metaData->{$fieldName};
        if (!$info) {
            throw new \Exception(sprintf(\RightNow\Utils\Config::getMessage(FIELD_PCT_S_DOES_NOT_EXIST_MSG), $fieldName));
        }
        if (isset($info->is_list) && $info->is_list) {
            if (!$field) {
                // Build an empty Array for unpopulated objects
                $field = new $info->type_name();
            }

            $nextField = array_shift($lookup);
            if ($nextField !== null) {
                if ($newField = ConnectExternal::fetchFromArray($field, $nextField)) {
                    $field = $newField;
                    $info = $field::getMetadata();
                }
                else if (is_int($index = self::getIndexFromField($nextField, self::getClassSuffix($info->type_name)))) {
                    $subObject = self::createArrayElement(self::prependNamespace($info->COM_type), $index);
                    $info = $subObject::getMetadata();
                    $field[] = $subObject;
                }
            }
        }
        else if (isset($info->is_object) && $info->is_object) {
            // Build an empty metadata object and field for the next sub-item
            $subItemClass = self::prependNamespace($info->COM_type);
            // Below we include the $object->fieldName in the assignment as Connect expects certain sub-objects to be associated with their parent.
            $field = $object->{$fieldName} = new $subItemClass();
            $info = $field::getMetadata();
        }
        return self::find($lookup, $info, $field);
    }

    /**
     * Returns the numeric index specified by $field, where $field can be an integer, or a field alias as defined by getArrayFieldAliases
     *
     * @param mixed $field Can be an integer index (e.g. 0 or '0'), or one of the defined field aliases (e.g. PRIMARY)
     * @param string $arrayName Example: 'EmailArray'
     * @return int|null
     */
    public static function getIndexFromField($field, $arrayName = null) {
        if (is_int($field) || ctype_digit($field)) {
            return intval($field);
        }
        $aliases = ConnectExternal::getArrayFieldAliases();
        if (isset($aliases[$arrayName]) && $aliases[$arrayName] && isset($aliases[$arrayName][$field])) {
            return $aliases[$arrayName][$field];
        }
    }

    /**
     * Returns the part of the object's class name after CONNECT_NAMESPACE_PREFIX\\
     * Null is returned if the class name does not begin with CONNECT_NAMESPACE_PREFIX\\
     *
     * @param object|string $objectOrString A Connect object, or the class name of a Connect object.
     * @return string|null
     */
    public static function getClassSuffix($objectOrString) {
        $className = (is_string($objectOrString)) ? $objectOrString : get_class($objectOrString);
        return Text::getSubstringAfter($className, self::prependNamespace(), null);
    }

    /**
     * Returns CONNECT_NAMESPACE_PREFIX\\$objectName
     *
     * @param string $objectName Name of Connect object
     * @return string
     */
    public static function prependNamespace($objectName = '') {
        return CONNECT_NAMESPACE_PREFIX . "\\$objectName";
    }

    /**
     * Returns a new Connect object specified by $connectClass, and sets its 'NamedIDOptList' property (e.g. AddressType) to $index.
     *
     * @param string $connectClass  Namespaced connect class name e.g. 'RightNow\\Connect\\v1_4\\Email'
     * @param int $index The numeric index of the element.
     * @return object Object specified by $connectClass
     */
    public static function createArrayElement($connectClass, $index) {
        $object = new $connectClass();
        $meta = $object::getMetadata();
        foreach (array_keys(get_object_vars($object)) as $property) {
            if (!$meta->{$property}->is_nillable
                && (ConnectExternal::isNamedIDType($meta->{$property}) || ($connectClass === 'RightNow\Connect\v1_4\ChannelUsername' && $meta->{$property}->COM_type === 'ChannelType'))) {
                if (ConnectExternal::isNamedIDType($meta->{$property})) {
                    $object->{$property}->ID = $index;
                }
                else {
                    // ChannelUsernames are keyed by the ChannelType field, which is a primary object and not a NamedID type
                    $subClassName = self::prependNamespace($property);
                    $object->{$property} = $subClassName::fetch($index);
                }
            }
        }
        return $object;
    }
}
