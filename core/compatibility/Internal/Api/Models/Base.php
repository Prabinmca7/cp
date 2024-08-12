<?php

namespace RightNow\Api\Models;
use RightNow\ActionCapture,
    RightNow\Connect\Knowledge\v1 as KnowledgeFoundation,
    RightNow\Internal\Utils\Version as Version,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Utils\Framework,
    RightNow\Libraries\Hooks;

/**
 * Base class for all models.
 */
abstract class Base {

    const CACHE_TIME = 300;

    private $cache;
    protected $CI;
    protected static $sessionToken = null;
    public $connectNamespace;
    public $connectVersion;

    function __construct() {
        $this->CI = (func_num_args() === 1) ? func_get_arg(0) : get_instance();
        if (IS_OPTIMIZED) {
            //Cache items in Production & Staging.
            $this->cache = new \RightNow\Libraries\Cache\Memcache(self::CACHE_TIME);
        }
        if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.8") >= 0) {
            $this->connectNamespace = 'RightNow\Connect\v1_4';
            $this->connectVersion = 1.4;
        }
        else if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.3") <= 0) {
            $this->connectNamespace = 'RightNow\Connect\v1_2';
            $this->connectVersion = 1.2;
        }
        else {
            $this->connectNamespace = 'RightNow\Connect\v1_3';
            $this->connectVersion = 1.3;
        }
    }

    /**
     * Caches the given value with a key.
     * @param string $key Cache key
     * @param mixed $value The object, array, string, etc. to be cached
     * @return mixed The value stored in the cache
     */
    protected function cache($key, $value) {
        \RightNow\Utils\Framework::setCache($key, $value);
        if ($this->cache) {
            return $this->cache->set($key, $value);
        }
        return $value;
    }

    /**
     * Returns the cached value for the given key.
     * @param string $key Cache key
     * @return object|boolean Deserialized object or false if not found
     */
    protected function getCached($key) {
        if ($result = \RightNow\Utils\Framework::checkCache($key)) {
            return $result;
        }
        if ($this->cache) {
            return $this->cache->get($key);
        }
        return false;
    }

    /**
     * Returns a token to be used for various KFAPI requests. This token is cached across a single CGI request.
     * @return string Token to pass to each KFAPI method
     */
    public function getKnowledgeApiSessionToken() {
        if(self::$sessionToken === null){
            $CI = get_instance();
            $sessionID = ($CI->session) ? $CI->session->getSessionData('sessionID') : null;
            self::$sessionToken = KnowledgeFoundation\Knowledge::StartInteraction(MOD_NAME, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['HTTP_REFERER'], $sessionID);
        }
        return self::$sessionToken;
    }

    /**
     * Sets the SecurityOptions object on the KFAPI Content object. Uses the Contact object parameter
     * if provided or the currently logged in user.
     * @param object $knowledgeApiContent Instance of KFAPI Content/ContentSearch object or one of its
     * multiple extended classes
     * @param Connect\Contact|null $contact Instance of Connect contact object used to populate filters.
     * No need to specify if the user is logged in
     * @return void
     */
    public function addKnowledgeApiSecurityFilter($knowledgeApiContent, $contact = null) {
        // if the contact is not logged in, do not add the contact to the security options
        // for SmartAssistant to prevent privileged content from being returned
        if (!\RightNow\Utils\Framework::isLoggedIn())
            return;
        if(!is_object($contact) && $this->CI->session && \RightNow\Utils\Framework::isLoggedIn()){
            $contact = $this->CI->model('Contact')->get($this->CI->session->getProfileData('contactID'))->result;
        }
        if(is_object($contact)){
            $knowledgeApiContent->SecurityOptions = new KnowledgeFoundation\ContentSecurityOptions();
            $knowledgeApiContent->SecurityOptions->Contact = $contact;
        }
    }

    /**
     * Adds namespace as prefix to the object name
     *
     * @param string $objectName Name of Connect object
     * @return string
     */
    public function prependNamespace($objectName = '') {
        return CONNECT_NAMESPACE_PREFIX . "\\$objectName";
    }

    /**
     * Iterates through the custom fields and attributes of $connectObject and sets any default values found.
     *
     * @param ConnectPHP\RNObject $connectObject A primary Connect object having CustomFields such as Contact or Incident.
     * @return void
     */
    public function setCustomFieldDefaults($connectObject) {
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
     * Utility method to create a new object
     * @param Connect\RNObject $connectObject Instance of object to create
     * @param int $source Source level 2 to use when creating the object
     * @return Connect\RNObject Modified Connect oject
     */
    protected function createObject($connectObject, $source) {
        return $this->createOrUpdateObject($connectObject, $source);
    }

    /**
     * Sets a field value on the provided Primary Object.
     * @param Connect\RNObject $connectObject The Connect object on which to set the value
     * @param string $fieldName Connect formatted name of the field
     * @param mixed $fieldValue The value to set on the object
     * @return null|string Exception error message or null on success.
     */
    protected function setFieldValue($connectObject, $fieldName, $fieldValue) {
        try {
            ConnectUtil::setFieldValue($connectObject, explode('.', $fieldName), $fieldValue);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Generic method for either updating or creating the provided Connect object.
     * @param Connect\RNObject $connectObject The Connect object to save.
     * @param int $source The source level 2 to use when creating or updating the object
     * @return object|string The created or updated object
     */
    private function createOrUpdateObject($connectObject, $source) {
        ConnectUtil::save($connectObject, $source);
        return $connectObject;
    }
}
