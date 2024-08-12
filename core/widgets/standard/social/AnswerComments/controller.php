<?php
namespace RightNow\Widgets;

use RightNow\Utils\Url;

class AnswerComments extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
        $this->setAjaxHandlers(array(
            'submit_action_ajax' => 'submitAnswerCommentAction',
        ));
    }

    function getData()
    {
        if(!\RightNow\Utils\Config::getConfig(COMMUNITY_ENABLED))
        {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(SOCIAL_ENABLED_CFG_SET_ENABLED_MSG));
            return false;
        }
        if(\RightNow\Utils\Config::getConfig(COMMUNITY_BASE_URL) === '')
        {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(SOCIAL_BASE_URL_CFG_SET_SET_MSG));
            return false;
        }

        $answerID = Url::getParameter('a_id');
        if(!$answerID)
            return false;
        $this->data['totalComments'] = 0;
        $this->data['js']['newUser'] = false;
        $commentData = $this->CI->model('Social')->getAnswerComments($answerID)->result;
        $comments = $commentData->comments;
        $this->data['permissions'] = $commentData->permissionsForRequestingUser;
        //Ensure the user can view the comments from community permissions
        if(!$this->data['permissions']->commentViewAll)
            return false;
        if($commentData->error && intval($commentData->error->code) === COMMUNITY_ERROR_INVALID_USER)
        {
            //logged-in CP user doesn't exist in community
            $this->data['js']['newUser'] = true;
            $this->data['createAccountUrl'] = \RightNow\Utils\Config::getConfig(COMMUNITY_HOME_URL) .
                Url::communitySsoToken('?', true, Url::getShortEufBaseUrl('sameAsCurrentPage', '/app/' . $this->CI->page .
                    '/a_id/' . Url::getParameter('a_id') .
                    (Url::getParameter('comment') !== null ? ('/comment/' . Url::getParameter('comment')) : '') . Url::sessionParameter()));
        }

        if(\RightNow\Utils\Framework::isLoggedIn())
            $this->data['contactID'] = intval($this->CI->session->getProfileData('contactID'));

        //No comments were found, stop processing, but still display
        if(!$comments || !is_array($comments) || !count($comments))
            return;

        $this->data['totalComments'] = count($comments);
        $topLevel = $commentsKeyedByParentId = array();
        foreach($comments as $comment)
        {
            //Formatting of results
            $comment->value = str_replace("\n", '<br/>', $comment->value);
            $comment->edited = ($comment->created !== $comment->lastEdited);
            $comment->lastEdited = \RightNow\Utils\Framework::formatDate($comment->lastEdited);
            $comment->createdBy->guid = intval($comment->createdBy->guid);
            $comment->status = intval($comment->status);
            if($this->data['attrs']['author_link_base_url']){
                $comment->createdBy->webUri = $this->data['attrs']['author_link_base_url'] . \RightNow\Utils\Text::getSubstringStartingWith($comment->createdBy->webUri, '/people/');
            }
            //Change content on deleted comments
            if($comment->status === COMMENT_STATUS_DELETED || $comment->status === COMMENT_STATUS_SUSPENDED || $comment->status === COMMENT_STATUS_PENDING)
            {
                $comment->createdBy->avatar = 'images/layout/whitePixel.png';
                $comment->createdBy->name = '';
                $comment->ratingCount = 0;
                if($comment->status === COMMENT_STATUS_DELETED)
                    $comment->value = '<em>' . $this->data['attrs']['label_deleted'] . '</em>';
                else
                    $comment->value = '<em>' . $this->data['attrs']['label_suspended'] . '</em>';
            }

            if($comment->ratingCount > 0)
            {
                $comment->ratingUp = round($comment->ratingValueTotal / 100);
                $comment->ratingDown = $comment->ratingCount - $comment->ratingUp;

                if($ratingDown === 0)
                    $comment->ratingPercentage = 100;
                else
                    $comment->ratingPercentage = round($comment->ratingUp / $comment->ratingCount, 2) * 100;

                if($comment->ratingPercentage > 50)
                {
                    $comment->ratingClass = 'rn_PositiveRating';
                    $comment->ratingImage = $this->data['attrs']['thumbs_up_icon'];
                }
                else
                {
                    $comment->ratingClass = 'rn_NegativeRating';
                    $comment->ratingImage = $this->data['attrs']['thumbs_down_icon'];
                }
            }
            if($comment->parentId)
            {
                if(isset($commentsKeyedByParentId[$comment->parentId]))
                    $commentsKeyedByParentId[$comment->parentId][] = $comment;
                else
                    $commentsKeyedByParentId[$comment->parentId] = array($comment);
            }
            else
            {
                $comment->level = 0;
                $topLevel[] = $comment;
            }
        }
        $this->data['commentStack'] = array_reverse($topLevel);
        $this->data['commentsKeyedByParentId'] = $commentsKeyedByParentId;
    }

    /**
     * Submits an action on an answer comment for Ajax requests. Echos out JSON encoded results
     * @param array|null $parameters Post parameters
     */
    function submitAnswerCommentAction($parameters)
    {
        \RightNow\Libraries\AbuseDetection::check();
        $response = $this->CI->model('Social')->performAnswerCommentAction($parameters['answerID'], $parameters['action'], json_decode($parameters['data']));
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
