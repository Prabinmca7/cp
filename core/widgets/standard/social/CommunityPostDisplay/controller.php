<?php
namespace RightNow\Widgets;

class CommunityPostDisplay extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs){
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'get_post_comments_ajax' => 'getPostComments',
            'post_comment_ajax' => 'submitPostCommentAction',
        ));
    }

    function getData(){
        if(!\RightNow\Utils\Config::getConfig(COMMUNITY_ENABLED)){
            echo $this->reportError(\RightNow\Utils\Config::getMessage(SOCIAL_ENABLED_CFG_SET_ENABLED_MSG));
            return false;
        }
        if(\RightNow\Utils\Config::getConfig(COMMUNITY_BASE_URL) === ''){
            echo $this->reportError(\RightNow\Utils\Config::getMessage(SOCIAL_BASE_URL_CFG_SET_SET_MSG));
            return false;
        }
        if($postHash = \RightNow\Utils\Url::getParameter('posts')){
            $urlParameter = true;
        }
        else{
            $postHash = $this->data['attrs']['post_hash'];
        }
        if(!$postHash){
            return false;
        }

        $this->data['baseUrl'] = \RightNow\Utils\Config::getConfig(COMMUNITY_BASE_URL);
        $postObject = $this->CI->model('Social')->getCommunityPost($postHash)->result;
        if(!$postObject || !$postObject->post){
            echo \RightNow\Utils\Config::getMessage(OOPS_POST_ISNT_AVAILABLE_LOGGING_MSG);
            return false;
        }
        else if($postObject->post->error && intval($postObject->post->error->code) === COMMUNITY_ERROR_INVALID_USER){
            //logged-in CP user doesn't exist in community
            $this->data['js']['newUser'] = true;
            $this->data['createAccountURL'] = \RightNow\Utils\Config::getConfig(COMMUNITY_HOME_URL) . \RightNow\Utils\Url::communitySsoToken('?', true, \RightNow\Utils\Url::getShortEufBaseUrl('sameAsCurrentPage', \RightNow\Utils\Url::addParameter("/app/{$this->CI->page}", 'posts', $postHash) . \RightNow\Utils\Url::sessionParameter()));
        }
        $postObject->post->positiveRating = (int) ($postObject->post->ratingTotal / 100);
        $postObject->post->negativeRating = (int) ($postObject->post->ratingCount - $postObject->post->positiveRating);
        $this->data['js']['postRating'] = array('rating' => $postObject->post->ratingTotal, 'count' => $postObject->post->ratingCount);
        if($postObject->post->ratedByRequestingUser && $postObject->post->ratedByRequestingUser->ratingValue !== null){
            if($postObject->post->ratedByRequestingUser->ratingValue === 100){
                $postObject->post->userRating = (object) array('label' => $this->data['attrs']['label_positive_rating_submitted'], 'positive' => true);
                if($postObject->post->positiveRating === 1){
                    //don't display '1 person likes this' if the one person is the current user...
                    $postObject->post->positiveRating = 0;
                }
            }
            else{
                $postObject->post->userRating = (object) array('label' => $this->data['attrs']['label_negative_rating_submitted']);
                if($postObject->post->negativeRating === 1){
                    $postObject->post->negativeRating = 0;
                }
            }
        }
        $this->data['post'] = $postObject->post;
        $this->data['js']['postHash'] = $postHash;
        if(!\RightNow\Utils\Framework::isLoggedIn() && $this->data['attrs']['login_link_url'] === '/app/' . \RightNow\Utils\Config::getConfig(CP_LOGIN_URL)){
            $this->data['attrs']['login_link_url'] = \RightNow\Utils\Url::addParameter(\RightNow\Utils\Url::getShortEufBaseUrl('sameAsRequest', $this->data['attrs']['login_link_url'] . \RightNow\Utils\Url::sessionParameter()), 'redirect', urlencode($this->CI->page . (($urlParameter === true) ? "/posts/$postHash" : '')));
        }
    }

    /**
     * Retrieves a community post specified by the post id. Hit via Ajax request. Echos out JSON encoded results
     * @param array|null $parameters Post parameters
    */
    function getPostComments($parameters)
    {
        \RightNow\Libraries\AbuseDetection::check();
        \RightNow\Utils\Framework::sendCachedContentExpiresHeader();
        $this->renderJSON($this->CI->model('Social')->getPostComments($parameters['postID'])->result);
    }

    /**
     * Submits an action on a post comment via Ajax request. Echos out JSON encoded results.
     * @param array|null $parameters Post parameters
     */
    function submitPostCommentAction($parameters)
    {
        \RightNow\Libraries\AbuseDetection::check();
        $response = $this->CI->model('Social')->performPostCommentAction($parameters['postID'], $parameters['action'], $parameters['content'], $parameters['commentID']);
        $data = $response->result;
        if($response->error){
            $data['error'] = true;
            $data['message'] = $response->error;
            if($response->error->errorCode){
                $data['errorCode'] = $response->error->errorCode;
            }
        }
        $this->renderJSON($data);
    }
}
