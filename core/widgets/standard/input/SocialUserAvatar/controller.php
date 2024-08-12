<?php
namespace RightNow\Widgets;
use RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Utils\Url;

use \RightNow\Internal\Libraries\OpenLogin as OpenLoginLibrary;

class SocialUserAvatar extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
        $this->setAjaxHandlers(array(
            'submit_avatar_library_action_ajax' => array(
                'method' => 'getImages',
                'clickstream' => 'submit_avatar_library_action_ajax'
            ),
            'save_profile_picture_ajax' => array(
                'method' => 'updateProfilePicture'
            ),
            'cancel_profile_picture_ajax' => array(
                'method' => 'cancelProfilePicture'
            ),
        ));
    }

    function getData() {
        if (!\RightNow\Utils\Framework::isLoggedIn()) return false;

        $this->data['js']['ftokenPresent'] = false;

        // constructing fully qualified home page url, due to isExternalUrl() requirement
        $homePage = (\RightNow\Utils\Url::isRequestHttps() ? 'https' : 'http') . '://' . Config::getConfig(OE_WEB_SERVER) . \RightNow\Utils\Url::getHomePage();

        // get http_referer to handle cancel button flow
        $referringUrl = $this->CI->agent->referrer() ?: $homePage;

        // if referrer url is not external url,
        // then save it in session for cancel button to work
        if (!\RightNow\Utils\Url::isExternalUrl($referringUrl) && $referringUrl !== \RightNow\Utils\Url::getOriginalUrl()) {
            $this->CI->session->setSessionData(array('prePage' => $referringUrl));
        }

        $this->data['js']['previousPage'] = $this->CI->session->getSessionData('prePage');

        $services = array('gravatar', 'facebook', 'assets');
        $this->data['js']['editingOwnAvatar'] = true;

        if (array_search('default', $this->data['attrs']['avatar_selection_options']) === false) {
            $this->data['attrs']['avatar_selection_options'][] = 'default';
        }

        if(!is_readable(HTMLROOT . $this->data['attrs']['avatar_library_image_location_gallery'])) {
            $this->data['attrs']['avatar_selection_options'] = array_diff($this->data['attrs']['avatar_selection_options'], array('avatarLibrary'));
        }

        if (!$this->data['attrs']['avatar_library_folder_roleset_map']) {
            echo $this->reportError(Config::getMessage(AVATARLIBRARYFOLDERROLESETMAP_ATTRIBUTE_MSG));
            return false;
        }

        $socialUser = $this->CI->model('CommunityUser')->get()->result;
        if (($userID = \RightNow\Utils\Url::getParameter('user')) && $userID != $socialUser->ID &&
            ($socialUserFromURL = $this->CI->model('CommunityUser')->get($userID)->result) &&
            $socialUserFromURL->SocialPermissions->canUpdateAvatar()) {
            $socialUser = $socialUserFromURL;
            $this->data['js']['editingOwnAvatar'] = false;
            $services = array();
        }

        $this->data['js']['socialUser'] = $socialUser ? $socialUser->ID : null;
        $this->data['js']['socialUserDisplayName'] = $socialUser ? $socialUser->DisplayName : '';
        $this->data['js']['defaultAvatar'] = $this->helper('Social')->getDefaultAvatar($socialUser->DisplayName);

        if($socialUser) {
            foreach($socialUser->RoleSets as $roleSet){
                $this->data['socialUserRoleSets'][] = $roleSet->ID;
            }
            $this->filterFolderAndLabels($this->data['attrs']['avatar_library_folder_roleset_map']);

            $this->data['js']['currentAvatar'] = $socialUser->AvatarURL;

            $contact = $this->CI->model('Contact')->getForSocialUser($socialUser->ID)->result;
            $this->data['js']['gravatar']['url'] = $this->CI->model('CommunityUser')->getGravatarUrl($contact->Emails[0]->Address);
            $this->data['js']['email'] = array(
                'address' => $contact->Emails[0]->Address,
                'hash' => md5($contact->Emails[0]->Address),
            );

            // if there is facebook token, use it to fetch and display profile picture
            $accessToken = $this->CI->session->getSessionData('fbToken');
            if ($accessToken) {
                $this->data['js']['ftokenPresent'] = true;
                $fbUser = \RightNow\Utils\OpenLoginUserInfo::getFacebookUserInfo($accessToken);
                $this->data['js']['facebook']['url'] = $fbUser->avatarUrl;

                // check if this is first login by user into facebook
                if ($this->CI->session->getFlashData('fbFirstLogin')) {
                    // set users current selection to facebook
                    // this will help $this->data['currentAvatar']['type'] to get set correctly down below
                    $socialUser->AvatarURL = $fbUser->avatarUrl;
                    $this->data['js']['selectedServiceName'] = 'facebook';

                    // display facebook success message only once
                    $this->data['displayFacebookSuccessMessage'] = true;
                }
            }

            // default type to other
            $this->data['currentAvatar']['type'] = 'other';
            foreach ($services as $service) {
                if (Text::stringContainsCaseInsensitive($socialUser->AvatarURL, $service)) {
                    $this->data['currentAvatar']['type'] = $service;
                    break;
                }
            }

            $this->data['currentAvatar']['url'] = ($this->helper('Social')->checkIfFileExists($socialUser->AvatarURL)) ? $socialUser->AvatarURL : false;

            // when facebook cookie nolonger exists and when user's current avatar url is facebook profile pic
            // set $this->data['js']['facebook']['url'] to current avatar url
            if ($this->data['currentAvatar']['type'] === 'facebook') {
                $this->data['js']['facebook']['url'] = $socialUser->AvatarURL;
            }
        }
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }

    /**
     * Handles the AJAX request for retrieving images.
     * @param array $params Post data; must have a folder
     */
    function getImages(array $params) {
        $avatarImages = $this->getFiles(HTMLROOT . $this->data['attrs']['avatar_library_image_location_gallery'], $this->data['attrs']['avatar_library_count'], $params['folder']);

        $outcome = json_encode(array(
            'numberOfPages' => (int) ceil(count($avatarImages) / $this->data['attrs']['avatar_library_page_size']),
            'files' => $avatarImages
        ));
        $this->echoJSON($outcome);
    }

    /**
     * Handles the AJAX request for submitting profile picture.
     * @param array $params Post data
     */
    function updateProfilePicture(array $params) {
        $outcome = array();
        if($this->validateUserAvatarUrl($params)){
            // destroy fb token
            if ($this->CI->session->getSessionData('fbToken')) {
                $this->CI->session->setSessionData(array('fbToken' => null));
            }
            if($params['checkArchived'] === 'true') {
                $currentAvatarParam = isset($params['currentAvatar']) && $params['currentAvatar'] ? $params['currentAvatar'] : '';
                $currentAvatar = explode($this->data['attrs']['avatar_library_image_location_display'], $currentAvatarParam);
                $archivedAvatar = !(is_readable(HTMLROOT . $this->data['attrs']['avatar_library_image_location_gallery'] . (isset($currentAvatar[1]) ? $currentAvatar[1] : '')));
            }
            if(!$archivedAvatar && $params['socialUser']){
                $paramValue = isset($params['value']) && $params['value'] ? $params['value'] : '';
                $updatedUser = $this->CI->model('CommunityUser')->update($params['socialUser'], array('CommunityUser.AvatarURL' => (object) array('value' => Text::escapeHtml($paramValue, true), 'w_id' => $params['w_id'], 'avatarSelectionType' => $params['avatarSelectionType'])));
                if ($updatedUser->result) {
                    $outcome = json_encode(array('success' => true, 'archivedAvatar' => false));
                    $this->CI->session->setFlashData('info', $this->data['attrs']['label_success_message']);
                }
                else {
                    $outcome = json_encode(array('success' => false, 'errorMessage' => $updatedUser->errors[0]->externalMessage, 'archivedAvatar' => false));
                }
            }
            else if($archivedAvatar) {
                $outcome = json_encode(array('archivedAvatar' => true));
            }
        }
        else {
            $outcome = json_encode(array('success' => false, 'errorMessage' => \RightNow\Utils\Config::getMessage(NOT_A_VALID_PROFILE_PICTURE_MSG)));
        }
        echo $this->echoJSON($outcome);
    }

    /**
     * Handles the AJAX request on cancel profile picture.
     */
    function cancelProfilePicture() {
        // destroy fb token
        if ($this->CI->session->getSessionData('fbToken')) {
            $this->CI->session->setSessionData(array('fbToken' => null));
        }

        echo $this->echoJSON(json_encode(array('success' => true)));
    }

    /**
     * Determines the names of the files to be displayed.
     * @param string $imagesDirectory The directory of the images to be displayed.
     * @param int $maxNumberOfImages The maximum number of images to be displayed
     * @param string $folder The folder name of the avatar image gallery
     * @return array The array of images to be displayed.
     */
    function getFiles($imagesDirectory, $maxNumberOfImages, $folder) {
        $files = array();
        $extensions = array_map('trim', array_unique(explode(',', strtolower($this->data['attrs']['avatar_library_image_types']))));
        $folderName = $imagesDirectory . $folder . '/';

        // protect against directory traversal attacks by ensuring the resolved path is a subset of the gallery
        if (strpos(realpath($folderName), realpath($imagesDirectory)) === false)
            return array();

        // also avoid the appearance of evil.  If "everyone" is a valid dir in the gallery, then so is "../gallery/everyone",
        // but some security scans consider allowing '..' at all to indicate vulnerability
        if (strpos($folderName, '..') !== false)
            return array();

        if (is_readable($folderName) && $handle = opendir($folderName)) {
            while (false !== ($file = readdir($handle))) {
                $fileExtension = strtolower($this->getFileExtension($file));
                $sizeInKB = filesize($folderName . $file) / 1024;

                if ($fileExtension && in_array($fileExtension, $extensions) && $sizeInKB <= $this->data['attrs']['avatar_library_max_image_size']) {
                     $files[] = $folder . '/' . $file;
                }
                if (count($files) === $maxNumberOfImages)
                    break;
            }
            closedir($handle);
        }
        return $files;
    }

    /**
     * Returns a file's extension.
     * @param string $fileName Name of the file
     * @return string The extension string
     */
    function getFileExtension($fileName) {
        return substr(strrchr($fileName, '.'), 1);
    }

    /**
     * Validate user avatar url
     * @param array $params Post data
     * @return boolean
     */
    protected function validateUserAvatarUrl ($params) {
        $error = false;
        switch ($params['avatarSelectionType']) {
            case 'gravatar':
            case 'avatar_library':
                if(Url::isExternalUrl($params['value']) || ($params['validationUrl'] !== $params['value'])) {
                    $error = true;
                }
                break;
            case 'default':
                if(isset($params['value']) && $params['value']) {
                    $error = true;
                }
                break;
            case '':
                if(!isset($params['value']) || Url::isExternalUrl($params['value']) || ($params['currentAvatar'] !== $params['value'])) {
                    $error = true;
                }
                break;
            case 'facebook':
                $accessToken = $this->CI->session->getSessionData('fbToken');
                if ($accessToken) {
                    $fbUser = \RightNow\Utils\OpenLoginUserInfo::getFacebookUserInfo($accessToken);

                    if ($params['value'] !== $fbUser->avatarUrl) {
                        $error = true;
                    }
                } else {
                    $error = true;
                }
                break;
            default:
                $error = true;
        }
        if($error) {
            return false;
        }
        return true;
    }

    /**
     * Converts the avatar_library_folder_roleset_map attribute to an array and filters valid values based on user's rolesets
     * @param string $rolesetsWithLabels Semicolon separated string containing pipe symbol seperated roleset IDs,folder-name and tab name
     * E.g. 1|everyone|All Users; 5,6|moderator|Moderators
     * Calculates an array based on the rolesets of the logged-in user
     * E.g. array('everyone' => 'All Users', 'moderator' => 'Moderators') if the logged-in user is a moderator.
     */
    protected function filterFolderAndLabels($rolesetsWithLabels) {
        $rolesetsFolderMap = array();

        $rolesetLabels = explode(";", $rolesetsWithLabels);
        foreach($rolesetLabels as $idsWithAssociatedText) {
            $rolelabels = explode("|", $idsWithAssociatedText);
            if(count($rolelabels) !== 3) {
                continue;
            }

            $ids = array_map('intval', array_filter(explode(',', $rolelabels[0]), function ($v) {
                return $v > 0;
            }));

            if(array_intersect($ids, $this->data['socialUserRoleSets'])){
                $rolesetsFolderMap[$rolelabels[1]]  = $rolelabels[2];
            }

            if((!isset($defaultTab) || !$defaultTab) && in_array(1, $ids)){
                $defaultTab = $rolelabels[1];
            }
        }
        $this->data['js']['defaultTab'] = $defaultTab ?: key($rolesetsFolderMap);
        $this->data['js']['rolesetsFolderMap'] = $rolesetsFolderMap;
    }

}
