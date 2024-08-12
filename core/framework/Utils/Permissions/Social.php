<?php
namespace RightNow\Utils\Permissions;

use \RightNow\Utils\Config;

/**
 * Methods useful for checking user permissions.
 */
final class Social
{
    /**
     * Determines if the logged-in user has the permission to update the specified CommunityUser field.
     * @param string $fieldName Field to check permission for, minus the 'CommunityUser.' prefix
     * @param int|null $userID The social user ID. If null, the user will be obtained from
     *     the 'user' url parameter if present, or fall back to the logged in user.
     * @param mixed $value The value the $fieldName is being set to. Currently only applicable when setting Status fields.
     * @param array &$errors Populated with error messages
     * @return bool True if user can edit otherwise false
     */
    public static function userCanEdit($fieldName, $userID = null, $value = null, array &$errors = array()) {
        list($user) = self::getUserAndSource($userID);
        if (!$user || !$user->SocialPermissions->canUpdate()) {
            $errors[] = Config::getMessage(DOES_HAVE_PERMISSION_UPDATE_SOCIAL_USER_LBL);
            return false;
        }

        if ($fieldName === 'AvatarURL' && !$user->SocialPermissions->canUpdateAvatar()) {
            $errors[] = Config::getMessage(DOES_PERM_CHANGE_AVATAR_SOCIAL_USER_LBL);
            return false;
        }

        if ($fieldName === 'DisplayName' && !$user->SocialPermissions->canUpdateDisplayName()) {
            $errors[] = Config::getMessage(DOES_HAVE_PERMISSION_CHANGE_SOCIAL_USER_LBL);
            return false;
        }

        // 'AvatarOrDisplayName' is not an actual fieldName but here for convenience
        if ($fieldName === 'AvatarOrDisplayName' && !$user->SocialPermissions->canUpdateAvatar() && !$user->SocialPermissions->canUpdateDisplayName()) {
            $errors[] = Config::getMessage(DOES_PERM_CHANGE_EITHER_AVATAR_S_USER_LBL);
            return false;
        }

        if (\RightNow\Utils\Text::endsWith($fieldName, 'Status.ID')) {
            // get a list of the status IDs that mean Deleted
            // $deletedStatuses = get_instance()->Model('SocialObjectBase')->getStatusesFromStatusType(STATUS_TYPE_SSS_USER_DELETED)->result;
            $deletedStatuses = get_instance()->model('CommunityUser')->getStatusesFromStatusType(STATUS_TYPE_SSS_USER_DELETED)->result;

            // deletions need the delete permission, everything else needs the status change permission
            if (in_array($value, $deletedStatuses)) {
                if(!$user->SocialPermissions->canDelete()) {
                    $errors[] = Config::getMessage(DOES_HAVE_PERMISSION_DELETE_SOCIAL_USER_LBL);
                    return false;
                }
            }
            else if(!$user->SocialPermissions->canUpdateStatus()) {
                $errors[] = Config::getMessage(DOES_PERMISSI_CHANGE_STATUS_SOCIAL_USER_LBL);
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the Social User object of either the passed-in $userID, the 'user' specified in the URL parameter, or the logged-in user.
     * @param int|null $userID The Social User ID, or null
     * @return array|null A two element array having the Social User object as the first element and one of 'input', 'url' or 'session'
     *     as the second, specifying the source. Returns null if user is not logged in.
     */
    public static function getUserAndSource($userID = null) {
        if (\RightNow\Utils\Framework::isLoggedIn()) {
            $model = get_instance()->model('CommunityUser');
            if ($userID && ($fromInput = $model->get($userID)->result)) {
                return array($fromInput, 'input');
            }
            if (($id = (int) \RightNow\Utils\Url::getParameter('user')) && ($fromUrl = $model->get($id)->result)) {
                return array($fromUrl, 'url');
            }
            if ($fromSession = $model->get()->result) {
                return array($fromSession, 'session');
            }
        }
    }
}