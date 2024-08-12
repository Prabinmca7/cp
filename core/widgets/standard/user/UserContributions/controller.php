<?php
namespace RightNow\Widgets;

use RightNow\Connect\v1_4 as Connect;

class UserContributions extends \RightNow\Libraries\Widget\Base {
    /**
     * Widget constructor
     */
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    /**
     * Populate widget data
     */
    function getData() {
        $userID = \RightNow\Utils\Url::getParameter('user');
        if (!\RightNow\Utils\Framework::isValidID($userID)) {
            return false;
        }
        $this->data['userID'] = (int) $userID;

        $types = $this->data['attrs']['contribution_types'];
        if (!is_array($types)) {
            $types = explode(',', $types);
        }

        $this->data['contributions'] = array();
        foreach($types as $type) {
            $this->data['contributions'][$type] = $this->getContributions($type);
        }

        if (!$this->data['contributions']) {
            return false;
        }

    }

    /**
     * Constructs an array of contribution data
     * @param string $type One of the specified contribution types
     * @return array An associated array of contribution data
     */
    function getContributions($type) {
        return array(
            'label' => $this->data['attrs']["label_{$type}"],
            'count' => $this->getCount($type)
        );
    }

    /**
     * Calculates the user's total contribution count for the specified type.
     * Uses $this->{$type}Count() function if exists, else returns 0.
     * @param string $type One of the specified contribution types
     * @return int The total contribution count for $type
     */
    function getCount($type) {
        $method = "{$type}Count";
        return method_exists($this, $method) ? $this->$method() : 0;
    }

    /**
     * Calculates total questions count
     * @return int The total questions count
     */
    protected function questionsCount() {
        $questions = $this->getRoqlResults('CommunityQuestion');
        $count = 0;
        while($question = $questions->next()) {
            $count++;
        }

        return $count;
    }

    /**
     * Calculates the total number of comments created by the specified user that were designated as the best answer.
     * @return int The total answers count
     */
    protected function answersCount() {
        list(, $count) = $this->getCommentAndBestAnswerCounts();

        return $count;
    }

    /**
     * Calculates total comments count
     * @return int The total comments count
     */
    protected function commentsCount() {
        list($count, ) = $this->getCommentAndBestAnswerCounts();

        return $count;
    }

    /**
     * Retrieve results from ROQL for 'CommunityQuestion' or 'CommunityComment'.
     * This is temporary until we either not limit results from the SocialUserActivity model or can get this from the KFAPI.
     * @param string $objectName One of 'CommunityQuestion' or 'CommunityComment'
     * @return object ROQL results
     */
    private function getRoqlResults($objectName = 'CommunityQuestion') {
        return Connect\ROQL::queryObject(sprintf(
            "SELECT $objectName FROM $objectName WHERE CreatedByCommunityUser = %d
            AND {$objectName}%s.Interface.ID = %d AND StatusWithType.StatusType NOT IN (%s) ORDER BY ID",
            $this->data['userID'],
            ($objectName === 'CommunityComment' ? '.ParentCommunityQuestion' : ''),
            \RightNow\Api::intf_id(),
            implode(',',  $this->helper('Social')->getExcludedStatuses($objectName))
        ))->next();
    }

    /**
     * Calculates total comments and best answer count
     * This is temporary until we either not limit results from the SocialUserActivity model or can get this from the KFAPI.
     * @return array A two element array containing the comment and answer total
     */
    private function getCommentAndBestAnswerCounts() {
        static $totals;
        if (!$totals) {
            $comments = $this->getRoqlResults('CommunityComment');
            $commentCount = $bestAnswerCount = 0;
            while ($comment = $comments->next()) {
                if(isset($comment->CommunityQuestion->StatusWithType->StatusType->ID) &&
                    !in_array($comment->CommunityQuestion->StatusWithType->StatusType->ID, $this->helper('Social')->getExcludedStatuses('CommunityQuestion')) &&
                    isset($comment->Parent->StatusWithType->StatusType->ID) &&
                    !in_array($comment->Parent->StatusWithType->StatusType->ID, $this->helper('Social')->getExcludedStatuses('CommunityComment'))){
                    $commentCount++;
                    $bestAnswerCount += $this->getBestAnswerCount($comment);
                }
            }
            $totals = array($commentCount, $bestAnswerCount);
        }

        return $totals;
    }

    /**
     * Calculates the best answer count from $comment
     * This is temporary until we either not limit results from the SocialUserActivity model or can get this from the KFAPI.
     * @param object $comment A CommunityComment object
     * @return integer The number of best answers associated with $comment and the specified user ID
     */
    private function getBestAnswerCount($comment) {
        $count = 0;
        foreach($comment->CommunityQuestion->BestCommunityQuestionAnswers ?: array() as $bestAnswer) {
            if ($comment->ID === $bestAnswer->CommunityComment->ID
                && $this->data['userID'] === $bestAnswer->CommunityComment->CreatedByCommunityUser->ID
                && !in_array($bestAnswer->CommunityComment->StatusWithType->StatusType->ID, $this->helper('Social')->getExcludedStatuses('CommunityComment'))) {
                    $count++;
            }
        }

        return $count;
    }
}