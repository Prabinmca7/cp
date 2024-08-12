<?php

namespace RightNow\Decorators;

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Libraries\TabularDataObject;

/**
 * Extends the Connect CommunityQuestion object to provide simple methods to check for various permissions.
 */
abstract class PermissionBase extends Base {

    protected $cache = array();

    /**
     * All decorator methods are cached using the call magic method. This allows
     * for generic caching of data so we don't repeat calculations. Currently
     * doesn't expect any Permission decorators to have parameters.
     * @param  string $method   Name of method being called
     * @param  array $arguments Array of arguments
     * @return mixed Result from calling method
     */
    public function __call($method, array $arguments = array()){
        //Don't cache methods which have arguments
        if(count($arguments)){
            return call_user_func_array(array($this, $method), $arguments);
        }
        if(array_key_exists($method, $this->cache)){
            return $this->cache[$method];
        }
        return $this->cache[$method] = $this->{$method}();
    }

    /**
     * Gets the currently logged-in social user
     * @return Object The currently logged-in social user
     */
    protected function getSocialUser() {
        return \RightNow\Utils\Framework::isLoggedIn() ? get_instance()->model('CommunityUser')->get()->result : null;
    }

    /**
     * A check if the logged-in user may create, update, delete ("modify") the
     * object based on more abstract or "universal" constraints among all social
     * objects as indicated by the arguments to the method.
     * By default, only active users may modify social objects.
     * @param array $statuses Statuses for which to check user against. Default
     *  check is for active users.
     * @return boolean Whether the logged in user may modify the object. If
     *  no user is logged in, false is returned.
     */
    protected function canUserModify(array $statuses = array()) {
        //Set default value manually (as opposed to setting it in method parameter)
        //as to avoid syntax error in production mode, which surrounds defines
        //with parentheses.
        if(empty($statuses)) {
            $statuses []= STATUS_TYPE_SSS_USER_ACTIVE;
        }
        if(($socialUser = $this->getSocialUser()) && (in_array((int) $socialUser->StatusWithType->StatusType->ID, $statuses, true))) {
            return true;
        }
        return false;
    }

    /**
     * Checks if the objects StatusWithType.StatusType.ID matches the provided status ID
     * @param  int $statusType Status type to check
     * @return boolean Whether object is set to provided status
     */
    protected function isStatusOf($statusType){
        return isset($this->connectObj->StatusWithType->StatusType->ID) ? (int)$this->connectObj->StatusWithType->StatusType->ID === $statusType : 0;        
    }

    /**
     * Checks if the object can perform the provided permission
     * @param  int $permission The permission to check
     * @param  object $alternateObject Alternate Connect object to check instead of default decorator object
     * @return boolean Whether the provided permission is allowed
     */
    protected function can($permission, $alternateObject = null){
        return ConnectUtil::hasPermission($alternateObject ?: $this->connectObj, $permission);
    }

    /**
     * Generic method for returning new Connect object and setting CreatedByCommunityUser field
     * @param  string $objectType Name of Connect class to create
     * @return object Instance of Connect class
     */
    protected function getObjectShell($objectType){
        $className = CONNECT_NAMESPACE_PREFIX . "\\{$objectType}";
        $objectShell = new $className();
        return $objectShell;
    }

    /**
     * Creates a new empty social Connect class and assigns the parent CommunityQuestion and CreatedByCommunityUser
     * properties if provided.  If the decorated object is a TabularDataObject, copy the top-level non-object
     * properties onto the new object to provide the most accurate response from hasPermission/hasAbility
     * @param  string      $objectType     Type of Connect object to create
     * @param  object|null $socialQuestion Instance of Connect CommunityUser to set on object
     * @param  object|null $socialUser     Instance of Connect CommunityQuestion to set on object
     * @param  object|null $socialComment  Instance of Connect CommunityComment to set on object
     * @return object Instance of Connect object
     */
    protected function getSocialObjectShell($objectType, $socialQuestion = null, $socialUser = null, $socialComment = null){
        $objectShell = $this->getObjectShell($objectType);
        if($socialQuestion){
            $objectShell->CommunityQuestion = $socialQuestion;
        }
        if($socialUser){
            if (is_int($socialUser))
                $objectShell->CreatedByCommunityUser = Connect\CommunityUser::fetch($socialUser, Connect\RNObject::VALIDATE_KEYS_OFF);
            else
                $objectShell->CreatedByCommunityUser = $socialUser;
        }
        if($socialComment){
            $objectShell->CommunityComment = $socialComment;
        }

        // if this is a decorated tabular data object, we can copy the properties without fear of incurring a DB hit through lazy loading
        if ($this->connectObj instanceof TabularDataObject) {
            $metadata = $objectShell::getMetadata();
            foreach ($this->connectObj as $key => $value) {
                // don't attempt read-only fields or properties that are already set
                if (isset($metadata->$key->is_read_only_for_create) && !$metadata->$key->is_read_only_for_create && isset($objectShell->$key) && !$objectShell->$key) {   
                    if ($metadata->$key->COM_type === 'DateTime') {
                        $objectShell->$key = strtotime($value);
                    } else if ($metadata->$key->is_object) {
                        // some properties, like StatusWithType, are more complicated so we only deal with objects where we can set the ID
                        if ($value->ID) {
                            if ($metadata->$key->COM_type === 'NamedIDOptList')
                                $objectShell->$key->ID = intval($value->ID);
                            else if ($key !== 'Parent')
                                $objectShell->$key = intval($value->ID);
                        }
                    } else {
                        $objectShell->$key = $value;
                    }   
                }
            }
        }

        return $objectShell;
    }
}
