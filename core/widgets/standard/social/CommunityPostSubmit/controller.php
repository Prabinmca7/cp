<?php
namespace RightNow\Widgets;
class CommunityPostSubmit extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
        $this->setAjaxHandlers(array('submit_post_ajax' => 'submitCommunityPost'));
    }

    function getData() {
        if (!\RightNow\Utils\Config::getConfig(COMMUNITY_ENABLED)) {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(SOCIAL_ENABLED_CFG_SET_ENABLED_MSG));
            return false;
        }
        if (\RightNow\Utils\Config::getConfig(COMMUNITY_BASE_URL) === '') {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(SOCIAL_BASE_URL_CFG_SET_SET_MSG));
            return false;
        }
        if(!isLoggedIn()){
            return false;
        }
        $postType = $this->CI->model('Social')->getPostTypeFields($this->data['attrs']['post_type_id'])->result;
        if (!$postType || !$postType->postType || count($postType->postType->fields) < 2) {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(ERROR_RETRIEVING_POST_TYPE_LBL));
            return false;
        }
        else if ($postType->postType->error && intval($postType->postType->error->code) === COMMUNITY_ERROR_INVALID_USER) {
            //logged-in CP user doesn't exist in community
            $this->data['js']['newUser'] = true;
            $this->data['createAccountURL'] = \RightNow\Utils\Config::getConfig(COMMUNITY_HOME_URL) . \RightNow\Utils\Url::communitySsoToken('?', true, \RightNow\Utils\Url::getShortEufBaseUrl('sameAsCurrentPage', '/app/' . $this->CI->page . \RightNow\Utils\Url::sessionParameter()));
        }
        $this->data['fields'] = $postType->postType->fields;
        $this->data['js']['token'] = \RightNow\Utils\Framework::createTokenWithExpiration(10);
        $this->data['js']['inputError'] = COMMUNITY_ERROR_INVALID_INPUT;
        if ($this->data['attrs']['on_success_url']) {
            $this->data['attrs']['on_success_url'] .= \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url']);
        }
        else {
            $this->data['attrs']['add_params_to_url'] = \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url']);
        }
    }

    /**
     * Submits the contents of a community post. Used via Ajax request. Echos out JSON encoded results.
     * @param array|null $parameters Post parameters
     */
    function submitCommunityPost($parameters) {
        \RightNow\Libraries\AbuseDetection::check();
        $response = $this->CI->model('Social')->submitPost(
            $parameters['postTypeID'],
            $parameters['resourceHash'],
            (object)array('id' => $parameters['titleID'], 'value' => $parameters['titleValue']),
            (object)array('id' => $parameters['bodyID'], 'value' => $parameters['bodyValue'])
        );
        $data = $response->result;
        if($response->error){
            $data['error'] = true;
            if(is_object($response->error)) {
                $data['message'] = $response->error->externalMessage;
                $data['errorCode'] = $response->error->errorCode;
            }
            else {
                $data['message'] = $response->error;
            }
        }
        $this->renderJSON($data);
    }
}
