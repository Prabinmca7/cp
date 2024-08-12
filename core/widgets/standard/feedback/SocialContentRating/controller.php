<?php
namespace RightNow\Widgets;

class SocialContentRating extends \RightNow\Libraries\Widget\Base {
    protected $ratingWeight = 100;
    protected $contentIDParameterName = null;
    protected $upvoteRatingScale = 1;
    protected $starRatingScale = 5;
    protected $updownRatingScale = 2;

    private $question;
    private $comment;

    function __construct($attrs) {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'submit_vote_ajax' => array(
                'method' => 'submitVoteHandler',
                'clickstream' => 'social_content_rate',
                'login_required' => true,
            )
        ));
    }

    function getData() {
        $questionID = $this->data['attrs']['question_id'];

        if(!$this->question = $this->CI->model('CommunityQuestion')->get($questionID)->result){
            return false;
        }

        if ($this->data['attrs']['content_type'] === 'comment') {
            $commentID = $this->data['attrs']['comment_id'];
            if (!$this->comment = $this->CI->model('CommunityComment')->get($commentID)->result) {
                return false;
            }
            $ratingInfo = $this->CI->model('CommunityComment')->getTabular($commentID)->result;
        }
        else{
            $ratingInfo = $this->CI->model('CommunityQuestion')->getTabular($questionID)->result;
        }
        $contentObject = ($this->data['attrs']['content_type'] === 'question') ? $this->question : $this->comment;
        $totalRatingLabel = $this->getTotalRatingLabel($ratingInfo->ContentRatingSummaries);

        $this->data['js'] = array(
            'ratingValue' 	=> ($ratingInfo->ContentRatingSummaries->PositiveVoteCount + $ratingInfo->ContentRatingSummaries->NegativeVoteCount),
            'alreadyRated'  => isset($ratingInfo->RatingValue) && !is_null($ratingInfo->RatingValue),
            'canRate' 		=> $this->canRateContent(),
            'canResetRating' 	=> $this->canResetRating($contentObject),
            'totalRatingLabel' => isset($totalRatingLabel['label']) ? $totalRatingLabel['label'] : null
        );

        $this->data['js']['ratingScale'] = $this->{$this->data['attrs']['rating_type'] . 'RatingScale'};
        $this->data['js']['userRating'] = isset($ratingInfo->RatingValue) ? $ratingInfo->RatingValue / ($this->ratingWeight / $this->data['js']['ratingScale']) : 0;
        $this->data['ratingStr'] = $this->data['attrs']['label_be_first_to_vote'];

        if ($this->data['js']['ratingValue'] !== 0) {
            $this->data['ratingStr'] = $this->data['js']['totalRatingLabel'];
        }
        else if (\RightNow\Utils\Framework::isLoggedIn() && !$this->data['js']['canRate']) {
            $this->data['ratingStr'] = $this->data['attrs']['label_upvote_disabled_tooltip'];
        }

        if ($this->data['attrs']['label_vote_count_singular'] && $this->data['attrs']['label_vote_count_plural']) {
            $this->data['ratingValueTitle'] = $this->helper->chooseCountLabel($this->data['js']['ratingValue'], $this->data['attrs']['label_vote_count_singular'], $this->data['attrs']['label_vote_count_plural']);
        }
        else {
            $this->data['ratingValueTitle'] = ($this->data['js']['ratingValue'] === 0) ? $this->data['attrs']['label_be_first_to_vote'] : $this->data['ratingStr'];
        }
        
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }

    /**
     * Handles the submit vote AJAX request. Echoes JSON of rating response
     * @param Array $params Post parameters having value of the button and other properties
     */
    function submitVoteHandler(array $params) {
        $outcome = array();
        $contentMethods = array(
            'question' => array('model' => 'CommunityQuestion', 'rateMethod' => 'rateQuestion', 'resetRateMethod' => 'resetQuestionRating'),
            'comment' => array('model' => 'CommunityComment', 'rateMethod' => 'rateComment', 'resetRateMethod' => 'resetCommentRating'),
        );
        $contentType = $this->data['attrs']['content_type'];

        if ($contentID = intval($this->data['attrs'][$contentType . '_id'])) {
            $model = $this->CI->model($contentMethods[$contentType]['model']);
            $fetched = $model->get($contentID);

            if ($object = $fetched->result) {
                if ($params['rating']) {
                    // The rating value will be calculated based on the rating type selected.
                    // For example for rating type 'star':
                    // no. of stars selected is 2,
                    // rating scale for star rating type is 5 and
                    // rating weight is 100
                    // then rating value will be 100/5 * 2 = 40
                    $userRatingValue = ($this->ratingWeight / $this->{$this->data['attrs']['rating_type'] . 'RatingScale'}) * $params['rating'];
                    $rateOperation = $model->{$contentMethods[$contentType]['rateMethod']}($object, $userRatingValue);

                    if ($rating = $rateOperation->result) {
                        $outcome = json_encode(array(
                            'ratingID' => $rating->ID,
                            'canResetRating' => $this->canResetRating($model->get($contentID)->result),
                            'totalRatingLabel' => $this->getTotalRatingLabel($model->getTabular($contentID)->result->ContentRatingSummaries, $userRatingValue),
                        ));
                    }
                    else {
                        $outcome = $rateOperation->toJson();
                    }
                }
                else {
                    $resetOperation = $model->{$contentMethods[$contentType]['resetRateMethod']}($object);
                    if ($userRating = $resetOperation->result) {
                        $outcome = json_encode(array(
                            'ratingReset' => true,
                            'totalRatingLabel' => $this->getTotalRatingLabel($model->getTabular($contentID)->result->ContentRatingSummaries, $userRating, true),
                        ));
                    }
                    else {
                        $outcome = $resetOperation->toJson();
                    }
                }
            }
            else{
                $outcome = $fetched->toJson();
            }
        }
        $this->echoJSON($outcome);
    }

    /**
     * Determines if the current user can delete the rating on the content
     * @param object $contentObject The CommunityQuestion/CommunityComment object
     * @return bool Whether or not deletion of rating is allowed
     */
    protected function canResetRating ($contentObject) {
        //Show the UI if the user has permission
        $userRatingObject = $this->CI->model($this->data['attrs']['content_type'] === 'question' ? 'CommunityQuestion' : 'CommunityComment')->getUserRating($contentObject);
        $userRating = $userRatingObject !== null ? $userRatingObject->result : null;
        if (!$userRating) {
            return false;
        }
        return $contentObject->SocialPermissions->canDeleteRating($userRating);
    }

    /**
     * Determines if the current user can rate the content we're displaying.
     * @return bool Whether or not rating is allowed
     */
    protected function canRateContent(){
        $isSocialUser = \RightNow\Utils\Framework::isSocialUser();
        //If the user isn't logged in or doesn't have a social profile and the questions is non-active/locked there isn't
        //much reason to show the UI to entice them to rate since for the majority of the time they won't be able to
        if(!$isSocialUser && ($this->question->SocialPermissions->isLocked() || !$this->question->SocialPermissions->isActive())){
            return false;
        }
        //Otherwise, show the UI if the user has permission, or they're not a social user (at which point we entice them to login/add social info)
        if($this->data['attrs']['content_type'] === 'question'){
            return !$isSocialUser || $this->question->SocialPermissions->canRate();
        }
        return !$isSocialUser || $this->comment->SocialPermissions->canRate();
    }

    /**
     * Calculates the rating based on the rating provided.
     * @param object $ratingSummary ContentRatingSummaries object
     * @param int $userRating New rating added/subtracted to/from the content
     * @param bool $reset True if it is a reset operation
     */
    protected function calculateRating ($ratingSummary, $userRating, $reset = false) {
        $ratingMidWeight = $this->ratingWeight / 2;       
        if ($reset) {
            if ($userRating >= $ratingMidWeight) {
                $ratingSummary->PositiveVoteCount = isset($ratingSummary->PositiveVoteCount) ? --$ratingSummary->PositiveVoteCount : -1;
            } else {
                $ratingSummary->NegativeVoteCount = isset($ratingSummary->NegativeVoteCount) ? --$ratingSummary->NegativeVoteCount : -1;
            }          
            $ratingSummary->RatingTotal = $ratingSummary->RatingTotal - $userRating;
        }
        else {
            $userRating >= $ratingMidWeight ? ++$ratingSummary->PositiveVoteCount : ++$ratingSummary->NegativeVoteCount;
            $ratingSummary->RatingTotal = $ratingSummary->RatingTotal + $userRating;
        }
    }

    /**
     * Determines the total content rating for the rating type and formats it accordingly
     * @param object $ratingSummary ContentRatingSummaries object
     * @param int $newRating New rating added to the content
     * @param bool $reset True if it is a reset operation
     * @return array An array containing the total rating label and different vote counts.
     */
    protected function getTotalRatingLabel ($ratingSummary, $newRating = 0, $reset = false) {
        if (empty($newRating)) {
            if (($totalVotes = $ratingSummary->PositiveVoteCount + $ratingSummary->NegativeVoteCount) === 0) {
                return;
            }
        }
        else {
            $this->calculateRating($ratingSummary, $newRating, $reset);
            $totalVotes = $ratingSummary->PositiveVoteCount + $ratingSummary->NegativeVoteCount;
        }

        $this->data['positiveVotes'] = $ratingSummary->PositiveVoteCount;
        $this->data['negativeVotes'] = $ratingSummary->NegativeVoteCount;
        $this->data['totalVotes'] = $totalVotes;

        if ($totalVotes === 0) {
            return array(
                'label' => $this->data['attrs']['label_be_first_to_vote'],
                'totalVotes' => $this->data['totalVotes']
            );
        }

        if ($this->data['attrs']['rating_type'] === 'star') {
            $ratingValue = round($ratingSummary->RatingTotal / $totalVotes * ($this->starRatingScale / $this->ratingWeight), 1);
            $ratingString = $ratingValue . "/{$this->starRatingScale}";
            $this->data['ratedValue'] = $ratingValue;
        }
        else if($this->data['attrs']['rating_type'] === 'updown') {
            $ratingString = "+" . intval($ratingSummary->PositiveVoteCount) . "/-" . intval($ratingSummary->NegativeVoteCount);
        }
        else {
            $ratingString = $ratingSummary->PositiveVoteCount;
        }

        // Rating format label for star would be for e.g Rating: 4.3/5 (5 users),
        // label for upvote would be Rating: 4 (5 users)
        // label for updown would be Rating: +1/-1 (2 users)
        return array(
            'label' => sprintf(($totalVotes === 1 ? $this->data['attrs']['label_rating_singular'] : $this->data['attrs']['label_rating_plural']), $ratingString, $totalVotes),
            'positiveVotes' => intval($this->data['positiveVotes']),
            'negativeVotes' => intval($this->data['negativeVotes']),
            'totalVotes' => intval($this->data['totalVotes']),
            'ratedValue' => isset($this->data['ratedValue']) ? $this->data['ratedValue'] : null
        );
    }
}
