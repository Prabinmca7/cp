<?php

namespace RightNow\Decorators;

use RightNow\Libraries\TabularDataObject,
    RightNow\Utils\Framework,
    RightNow\Connect\v1_4 as Connect;

require_once CPCORE . 'Decorators/Permission/PermissionBase.php';

/**
 * Extends the Connect CommunityUser object to provide simple methods to check for various permissions.
 */
class SocialUserPermissions extends PermissionBase {
    protected $connectTypes = array( 'CommunityUser', 'RightNow\Libraries\TabularDataObject' );

    /**
     * Whether the user is in an active state
     * @return boolean True if the user is active, false otherwise
     */
    public function isActive() {
        return parent::isStatusOf(STATUS_TYPE_SSS_USER_ACTIVE);
    }

    /**
     * Whether the user is in an pending state
     * @return boolean True if the user is pending, false otherwise
     */
    public function isPending() {
        return parent::isStatusOf(STATUS_TYPE_SSS_USER_PENDING);
    }

    /**
     * Whether the user is in a suspended state
     * @return boolean True if the user is suspended, false otherwise
     */
    public function isSuspended() {
        return parent::isStatusOf(STATUS_TYPE_SSS_USER_SUSPENDED);
    }

    /**
     * Whether the user is in a deleted state
     * @return boolean True if the user is deleted, false otherwise
     */
    public function isDeleted() {
        return parent::isStatusOf(STATUS_TYPE_SSS_USER_DELETED);
    }

    /**
     * Whether the user is in a archived state
     * @return boolean True if the user is archived, false otherwise
     */
    public function isArchived() {
        return parent::isStatusOf(STATUS_TYPE_SSS_USER_ARCHIVE);
    }

    /**
     * Whether the current user is a moderator
     * @return boolean Whether or not the current user is in a moderator role
     */
    public function isModerator() {
        return Framework::isSocialModerator();
    }

    /**
     * Whether the user can read this user record
     * @return boolean True if the user can read, false if the user doesn't have permission
     */
    public function canRead() {
        return parent::can(PERM_SOCIALUSER_READ, $this->getUserShell());
    }

    /**
     * Whether the user can read this user contact details
     * @return boolean True if the user can read, false if the user doesn't have permission
     */
    public function canReadContactDetails() {
        return Connect\ConnectAPI::getCurrentContext()->hasPermission(PERM_VIEWCONTACTDETAILS);
    }

    /**
     * Checks if the currently logged in user has permission to create a social user. To create a social user,
     * the contact needs to be logged in and not have a social user association or they need to be active with
     * the correct permission(s).
     * @return bool True if logged-in user can create
     */
    protected function canCreate() {
        return \RightNow\Utils\Framework::isLoggedIn() &&
               (!$this->getSocialUser() || $this->canUserModify()) &&
               parent::can(PERM_SOCIALUSER_CREATE, $this->getUserShell());
    }

    /**
     * Checks whether the currently logged-in user has permission to update the decorated user.  Some fields
     * require additional permissions to update
     * @return bool True if logged-in user can update otherwise, false if they do not have UPDATE permission
     */
    protected function canUpdate() {
        return !$this->isDeleted() &&
               (isset($this->getSocialUser()->ID, $this->connectObj->ID) && $this->getSocialUser()->ID === $this->connectObj->ID) ? $this->canUserModify(array( STATUS_TYPE_SSS_USER_ACTIVE, STATUS_TYPE_SSS_USER_SUSPENDED, STATUS_TYPE_SSS_USER_PENDING )) : $this->canUserModify() &&
               parent::can(PERM_SOCIALUSER_UPDATE, $this->getUserShell());
    }

    /**
     * Checks whether the currently logged-in user has permission to update the decorated user's avatar
     * @return bool True if logged-in user can update otherwise, false if they do not have UPDATE_AVATAR permission
     */
    protected function canUpdateAvatar() {
        return $this->canUpdate() &&
               parent::can(PERM_SOCIALUSER_UPDATE_AVATAR, $this->getUserShell());
    }

    /**
     * Checks whether the currently logged-in user has permission to update the decorated user's display name
     * @return bool True if logged-in user can update otherwise, false if they do not have UPDATE_DISPLAYNAME permission
     */
    protected function canUpdateDisplayName() {
        return $this->canUpdate() &&
               parent::can(PERM_SOCIALUSER_UPDATE_DISPLAYNAME, $this->getUserShell());
    }

    /**
     * Checks whether the current user has permission to update the status of a user
     * @return bool True if user can update status, false if user is not active or does not have UPDATE_STATUS permission or the acted upon user is in a deleted state
     */
    protected function canUpdateStatus() {
        return $this->canUpdate() &&
               $this->canUserModify() &&
               !$this->isDeleted() &&
               parent::can(PERM_SOCIALUSER_UPDATE_STATUS, $this->getUserShell());
    }

    /**
     * Checks whether the currently logged-in user has permission to soft-delete the decorated user (e.g. set status to deleted)
     * @return bool True if logged-in user can update otherwise, false if they do not have UPDATE_STATUS_DELETE permission
     */
    protected function canDelete() {
        return $this->canUpdate() &&
               $this->canUserModify() &&
               parent::can(PERM_SOCIALUSER_UPDATE_STATUS_DELETE, $this->getUserShell());
    }

    /**
     * Returns a CommunityUser object suitable for using with hasPermission() and hasAbility().
     * @return Object An empty CommunityUser object.  If the decorated object is a TabularDataObject, the returned
     *   object will have the top-level properties copied from the tabular data.
     */
    private function getUserShell() {
        if (!$this->connectObj instanceof TabularDataObject)
            return $this->connectObj;

        return parent::getSocialObjectShell('CommunityUser');
    }
}
