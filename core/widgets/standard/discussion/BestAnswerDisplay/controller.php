<?php
namespace RightNow\Widgets;

use RightNow\Utils\Url,
    RightNow\Utils\Text;

class BestAnswerDisplay extends \RightNow\Libraries\Widget\Base {
    private $bestAnswerTypes;

    function __construct($attrs) {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'refresh_ajax' => array(
                'method'        => 'refresh',
                'clickstream'   => 'bestAnswerRefresh',
        )));

        $this->bestAnswerTypes = array(
            'author'    => SSS_BEST_ANSWER_AUTHOR,
            'moderator' => SSS_BEST_ANSWER_MODERATOR,
        );
    }

    function getData() {
        $this->data['question'] = $this->helper->question = $this->CI->model('CommunityQuestion')->get(intval(Url::getParameter('qid')))->result;

        if(!$this->data['question']) {
            return false;
        }

        $this->helper->bestAnswerTypes = $this->bestAnswerTypes;
        $this->data['bestAnswers'] = $this->getBestAnswers($this->CI->model('CommunityQuestion')->getBestAnswers($this->data['question'])->result);
        $this->data['socialUser'] = $this->CI->model('CommunityUser')->get()->result;
        $this->highlightContent();
        
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }

    /**
     * Returns the best answers for this question
     * @param Array $bestAnswers List of best answers
     * @return Array|Boolean Filtered list of answers or false
     */
    function getBestAnswers($bestAnswers) {
        if (!$bestAnswers) return false;

        $displayedAnswers = array();
        foreach ($bestAnswers as $bestAnswer) {
            if ($bestAnswer->CommunityComment->SocialPermissions->isActive() && $this->shouldDisplayComment($bestAnswer->BestAnswerType->ID)) {
                $selectedBy = array(
                    'type' => $bestAnswer->BestAnswerType->ID,
                    'id' => $bestAnswer->CommunityUser->ID,
                );

                // check if this comment is already marked as best by somebody else
                if (!array_key_exists($bestAnswer->CommunityComment->ID, $displayedAnswers)) {
                    if($comment = $this->getComment($bestAnswer->CommunityComment->ID)) {
                        $displayedAnswers[$bestAnswer->CommunityComment->ID] = array(
                            'comment' => $comment,
                            'selectedBy' => array($selectedBy),
                        );
                    }
                }
                else {
                    $displayedAnswers[$bestAnswer->CommunityComment->ID]['selectedBy'][] = $selectedBy;
                }
            }
        }

        return count($displayedAnswers) ? $displayedAnswers : false;
    }

    /**
     * Renders the best answer content.
     * @param array $params Post data; must have a questionID
     */
    function refresh($params) {
        $this->data['question'] = $this->helper->question = $this->CI->model('CommunityQuestion')->get(intval($params['questionID']))->result;
        $this->data['bestAnswers'] = $this->getBestAnswers($this->CI->model('CommunityQuestion')->getBestAnswers($this->data['question'])->result);
        $this->highlightContent();
        $this->helper->bestAnswerTypes = $this->bestAnswerTypes;
        $content = ($this->data['bestAnswers']) ? $this->render('BestAnswers', array('bestAnswers' => $this->data['bestAnswers'], 'question' => $this->data['question'])) : '';

        header('Content-Length: ' . strlen($content));
        header('Content-type: text/html');
        echo $content;
    }

    /*
     * Checks widget attributes to determine whether we should display a given comment
     * @param int $bestAnswerType Type of Best Answer (eg. Author or Moderator)
     * @return Boolean Whether to display comment or not
     */
    function shouldDisplayComment($bestAnswerType) {
        static $types;
        $types = $types ?: array_flip($this->bestAnswerTypes);
        return $types[$bestAnswerType] && in_array($types[$bestAnswerType], $this->data['attrs']['best_answer_types']);
    }

    /*
     * Pull the comment data for a comment ID
     * This is needed because the BestAnswer object currently only contains the comment ID
     * @param int $commentID ID of the comment to look up
     * @return Connect\Comment|null Comment object
     */
    function getComment($commentID) {
        if ($commentID && $comment = $this->CI->Model('CommunityComment')->get($commentID)->result) {
            return array(
                'data' => $comment,
                'metadata' => $comment::getMetadata(),
            );
        }
    }

    /*
     * Lookup a user's display name
     * This is needed because the BestAnswer object currently only contains the social user ID
     * @param int $userID ID of user to look up display name for
     * @return string|null Display name of social user
     */
    function userDisplayName($userID) {
        return null;
    }

    /**
     * Checks whether we should highlight the best answer comment.
     * If yes, data required for the same is prepared.
     */
    protected function highlightContent() {
        //Passing 0 disables the highlighting feature
        if($this->data['attrs']['author_roleset_callout']) {
            $this->data['author_roleset_callout'] = $this->helper('Social')->filterValidRoleSetIDs($this->data['attrs']['author_roleset_callout']);
            $this->data['author_roleset_styling'] = $this->helper('Social')->generateRoleSetStyles($this->data['author_roleset_callout']);
        }
    }

}
