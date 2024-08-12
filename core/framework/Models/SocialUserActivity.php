<?php

namespace RightNow\Models;

use RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Utils\Framework,
    RightNow\Libraries\ConnectTabular,
    RightNow\Connect\v1_4 as Connect;

/**
 * Represents CommunityUser activity on SocialQuestions and
 * SocialQuestionComments.
 */
class SocialUserActivity extends CommunityObjectBase {
    private $maxActivityResults = 25;

    /**
     * Returns the most recent questions asked by the given user.
     * @param  Connect\CommunityUser $user CommunityUser
     * @return array       Contains Questions asked by the user
     */
    public function getQuestions ($user) {
        return $this->getResponseObject($this->getQuestionsWithUserID($user->ID), 'is_array');
    }

    /**
     * Returns the most recent comments posted by the given user.
     * @param  Connect\CommunityUser $user CommunityUser
     * @return array       Contains Comments asked by the user;
     *                              Each Comment's parent Question
     *                              is attached via the CommunityQuestion
     *                              property
     */
    public function getComments ($user) {
        $comments = $this->getCommentsWithUserID($user->ID);
        $questions = $this->getQuestionsByIDs(self::extractIDs($comments, array('CommunityQuestion', 'ID')));
        $bestAnswers = $this->aggregateBestAnswers($questions);

        return $this->getResponseObject(self::mergeArraysOfObjects($comments, $questions, function ($comment, $question) use ($bestAnswers) {
            if ($question->ID === $comment->CommunityQuestion->ID) {
                $comment->CommunityQuestion = $question;
                $comment->CommunityQuestion->BestCommunityQuestionAnswers = isset($bestAnswers[$question->ID]) ? $bestAnswers[$question->ID] : array();

                return $comment;
            }
        }), 'is_array');

    }

    /**
     * Returns the comments where a best answer designation has been assigned by the specified user.
     * @param  Connect\CommunityUser $user CommunityUser
     * @return array Comments where each comment's parent question is attached via the CommunityQuestion property
     */
    public function getBestAnswersVotedByUser ($user) {
        $bestAnswers = array();

        if ($questions = $this->getQuestionsWithBestAnswerSuppliedByUser($user->ID)) {
            $commentIDs = self::extractIDs($questions, array('BestCommunityQuestionAnswers', 'CommunityComment'));
            $bestAnswers = self::mergeArraysOfObjects($this->getCommentsByIDs($commentIDs), $questions, function ($comment, $question) {
                if ($question->BestCommunityQuestionAnswers->CommunityComment === $comment->ID) {
                    $comment->CommunityQuestion = $question;
                    return $comment;
                }
            });
        }

        return $this->getResponseObject($bestAnswers, 'is_array');
    }

    /**
     * Returns the comments where a best answer designation has
     * been assigned for a comment that the specified user authored.
     * @param  Connect\CommunityUser $user CommunityUser
     * @return array Comments where each comment's parent question is attached via the CommunityQuestion property
     */
    public function getBestAnswersAuthoredByUser ($user) {
        $bestAnswers = array();
        if ($comments = $this->getComments($user)->result) {
            $commentIDs = array();
            foreach ($comments as $comment) {
                foreach ($comment->CommunityQuestion->BestCommunityQuestionAnswers as $bestAnswer) {
                    if ($comment->ID === $bestAnswer->CommunityComment && !in_array($comment->ID, $commentIDs)) {
                        $bestAnswers[] = $comment;
                        $commentIDs[] = $comment->ID;
                    }
                }
            }
        }

        return $this->getResponseObject($bestAnswers, 'is_array');
    }

    /**
     * Returns the comments where a rating has
     * been assigned by the specified user.
     * @param  Connect\CommunityUser $user CommunityUser
     * @return array       Comments;
     *                     Each Comment's parent Question
     *                     is attached via the CommunityQuestion
     *                     property and the Rating is attached
     *                     via the UserRating property
     */
    public function getCommentRatingsGivenByUser ($user) {
        $ratedComments = array();

        if ($ratings = $this->getCommentRatingsForUserID($user->ID)) {
            $ratedComments = $this->getCommentsByIDs(self::extractIDs($ratings, array('CommunityComment')));

            $questionIDs = array();
            foreach ($ratedComments as $comment) {
                $questionIDs []= $comment->CommunityQuestion->ID;
                foreach ($ratings as $rating) {
                    if ($rating->CommunityComment === $comment->ID) {
                        $comment->UserRating = $rating;
                    }
                }
            }

            $questions = $this->getQuestionsByIDs($questionIDs);

            $ratedComments = self::mergeArraysOfObjects($ratedComments, $questions, function ($comment, $question) {
                if ($question->ID === $comment->CommunityQuestion->ID) {
                    $comment->CommunityQuestion = $question;
                    return $comment;
                }
            });
        }
        return $this->getResponseObject($ratedComments, 'is_array');
    }

    /**
     * Returns the comments where a rating has
     * been assigned onto a comment authored by the specified user.
     * @param  Connect\CommunityUser $user CommunityUser
     * @return array       Comments;
     *                     Each Comment's parent Question
     *                     is attached via the CommunityQuestion
     *                     property and the Rating is attached
     *                     via the UserRating property
     */
    public function getCommentRatingsGivenToUser ($user) {
        $commentsWithRatings = array();

        if ($comments = $this->getComments($user)->result) {
            if ($ratings = $this->getRatingsForCommentIDs(self::extractIDs($comments, array('ID')))) {
                $commentsWithRatings = self::mergeArraysOfObjects($ratings, $comments, function ($rating, $comment) {
                    if ($rating->CommunityComment === $comment->ID) {
                        $comment->UserRating = $rating;
                        return $comment;
                    }
                });
            }
        }

        return $this->getResponseObject($commentsWithRatings, 'is_array');
    }

    /**
     * Returns the questions where a rating has
     * been assigned by the specified user.
     * @param  Connect\CommunityUser $user CommunityUser
     * @return array       Questions;
     *                     Each rating is attached via the
     *                     UserRating property
     */
    public function getQuestionRatingsGivenByUser ($user) {
        $ratedQuestions = array();

        if ($ratings = $this->getQuestionRatingsForUserID($user->ID)) {
            $ratedQuestions = $this->getQuestionsByIds(self::extractIDs($ratings, array('CommunityQuestion')));
            foreach ($ratedQuestions as $question) {
                foreach ($ratings as $rating) {
                    if ($rating->CommunityQuestion === $question->ID)
                        $question->UserRating = $rating;
                }
            }
        }

        return $this->getResponseObject($ratedQuestions, 'is_array');
    }

    /**
     * Returns the questions where a rating has
     * been assigned onto a question authored by the specified user.
     * @param  Connect\CommunityUser $user CommunityUser
     * @return array       Questions;
     *                     Each rating is attached via the
     *                     UserRating property
     */
    public function getQuestionRatingsGivenToUser ($user) {
        $questionsWithRatings = array();

        if ($questions = $this->getQuestions($user)->result) {
            if ($ratings = $this->getRatingsForQuestionIDs(self::extractIDs($questions, array('ID')))) {
                $questionsWithRatings = self::mergeArraysOfObjects($ratings, $questions, function ($rating, $questions) {
                    if ($rating->CommunityQuestion === $questions->ID) {
                        $questions->UserRating = $rating;
                        return $questions;
                    }
                });
            }
        }

        return $this->getResponseObject($questionsWithRatings, 'is_array');
    }

    /**
     * Retrieves the ratings for the given comment ids.
     * @param  string $commentIDs Comma-separated comment ids
     * @return array             Ratings
     */
    protected function getRatingsForCommentIDs ($commentIDs) {
        return $this->getRatingsFromQuery("comment", "CommunityComment IN ($commentIDs)");
    }

     /**
     * Retrieves the comment ratings for the given social user id.
     * @param  string|int $userID Social User ID
     * @return array         Ratings
     */
    protected function getCommentRatingsForUserID ($userID) {
        return $this->getRatingsFromQuery("comment", "CreatedByCommunityUser = {$userID}");
    }

    /**
     * Retrieves the ratings for the given question ids.
     * @param  string $questionIDs Comma-separated question ids
     * @return array             Ratings
     */
    protected function getRatingsForQuestionIDs ($questionIDs) {
        return $this->getRatingsFromQuery("question", "CommunityQuestion IN ($questionIDs)");
    }

    /**
     * Retrieves the question ratings for the given social user id.
     * @param  string|int $userID Social User ID
     * @return array         Ratings
     */
    protected function getQuestionRatingsForUserID ($userID) {
        return $this->getRatingsFromQuery("question", "CreatedByCommunityUser = {$userID}");
    }

    /**
     * Returns Questions for the specified ids.
     * @param  string|array $questionIDs List if ids or comma-separated Question IDs
     * @return array              Query results
     */
    protected function getQuestionsByIDs ($questionIDs) {
        $questionIDs = (is_array($questionIDs)) ? implode(',', $questionIDs) : $questionIDs;
        return $this->queryQuestions("ID IN ({$questionIDs})");
    }

    /**
     * Returns Questions for the specified user.
     * @param  string|int $userID Author ID
     * @return array              Query results
     */
    protected function getQuestionsWithUserID ($userID) {
        return $this->queryQuestions("CreatedByCommunityUser = {$userID}");
    }

    /**
     * Returns Questions with the best answer assigned by the specified user.
     * @param  string|int $userID Best Answer assigner ID
     * @return array         Query results
     */
    protected function getQuestionsWithBestAnswerSuppliedByUser ($userID) {
        return $this->queryQuestions("BestCommunityQuestionAnswers.CommunityUser = {$userID}");
    }

    /**
     * Returns Comments for the specified ids.
     * @param  string|array $commentIDs List of ids or comma-separated Comment IDs
     * @return array              Query results
     */
    protected function getCommentsByIDs ($commentIDs) {
        $commentIDs = (is_array($commentIDs)) ? implode(',', $commentIDs) : $commentIDs;
        return $this->queryComments("ID IN ({$commentIDs})");
    }

    /**
     * Returns Comments for the specified user.
     * @param  string|int $userID Author ID
     * @return array              Query results
     */
    protected function getCommentsWithUserID ($userID) {
        return $this->queryComments("CreatedByCommunityUser = {$userID}");
    }

    /**
     * Retrieves Questions with the specified WHERE clause.
     * @param  string $where WHERE clause
     * @return array        Questions
     */
    protected function queryQuestions ($where) {
        return $this->queryByType('question', "$where AND Interface.ID = {$this->interfaceID}");
    }

    /**
     * Retrieves Comments with the specified WHERE clause.
     * @param  string $where WHERE clause
     * @return array        Comments
     */
    protected function queryComments ($where) {
        return $this->queryByType('comment', "$where AND ParentCommunityQuestion.Interface.ID = {$this->interfaceID}");
    }

    /**
     * Retrieves Questions or Comments with the specified WHERE clause.
     * @param  string $type One of 'question' or 'comment'
     * @param  string $where WHERE clause
     * @return array An array of the query results.
     */
    protected function queryByType ($type, $where) {
        static $types;

        $types = $types ?: array(
            'question' => array(
                'alias' => 'q',
                'method' => 'getQuestionSelectROQL',
                'exclude' => array(
                    STATUS_TYPE_SSS_QUESTION_PENDING,
                    STATUS_TYPE_SSS_QUESTION_DELETED,
                    STATUS_TYPE_SSS_QUESTION_SUSPENDED,
                ),
            ),
            'comment' => array(
                'alias' => 'c',
                'method' => 'getCommentSelectROQL',
                'exclude' => array(
                    STATUS_TYPE_SSS_COMMENT_PENDING,
                    STATUS_TYPE_SSS_COMMENT_DELETED,
                    STATUS_TYPE_SSS_COMMENT_SUSPENDED,
                ),
            ),
        );
        
        if (isset($types[strtolower($type)])) {
            $options = $types[strtolower($type)];
            if (isset($options['method'])) {
                return $this->getResultsForQuery($this->{$options['method']}(
                    "{$options['alias']}.{$where} AND {$options['alias']}.StatusWithType.StatusType NOT IN (" .
                    implode(',', $options['exclude']) .
                    ")\nORDER BY {$options['alias']}.CreatedTime DESC",
                    $this->maxActivityResults
                ));
            }
        }

        return array();
    }

    /**
    * Selector function to supply queryRatings with the correct parameters
    * @param string $type Type of query - either question or comment
    * @param string $where WHERE clause in query
    * @return array Ratings
    */
    protected function getRatingsFromQuery($type, $where) {
        switch($type) {
            case 'comment':
                return $this->queryRatings($where, "CommunityCommentRtg", "CommunityComment");
            case 'question':
                return $this->queryRatings($where, "CommunityQuestionRtg", "CommunityQuestion");
            default:
                return null;
        }
    }

    /**
     * Fetches the results for the given query.
     * @param  string $roql Query
     * @return array  Results
     */
    protected function getResultsForQuery ($roql) {
        $query = ConnectTabular::query($roql);
        return $query->getCollection();
    }

     /**
     * Retrieves ratings with the supplied WHERE, FROM, and SELECT type clauses.
     * @param  string $where WHERE clause
     * @param  string $from FROM clause - Either CommunityQuestionRtg or CommunityCommentRtg
     * @param  string $type SELECT type clause - Either CommunityQuestion or CommunityComment
     * @return array  Ratings
     */
    protected function queryRatings ($where, $from, $type) {
        $max = $this->maxActivityResults;
        $roql = <<<ROQL
SELECT
 ID,
 CreatedTime,
 UpdatedTime,
 RatingValue,
 RatingWeight,
 $type
 FROM
 $from
 WHERE $where
 ORDER BY CreatedTime DESC
 LIMIT $max
ROQL;

        return $this->getResultsForQuery($roql);
    }

    /**
     * Returns an array of arrays indexed by question ID containing the best answers for that question.
     * This is used to aggregate best answers from tabular query results, as each row of the tabular
     * results contains only one best answer.
     * @param array $questions An array of question objects
     * @return array An array indexed by question ID containing the best answer details for that question.
     */
    protected function aggregateBestAnswers(array $questions) {
        $bestAnswers = $commentIDs = array();
        foreach($questions as $question) {
            $answers = $question->BestCommunityQuestionAnswers;
            foreach(is_array($answers) ? $answers : array($answers) as $answer) {
                if ($commentID = $answer->CommunityComment) {
                    if (!isset($bestAnswers[$question->ID]) || !$bestAnswers[$question->ID]) {
                        $bestAnswers[$question->ID] = $commentIDs[$question->ID] = array();
                    }
                    if (!in_array($commentID, $commentIDs[$question->ID])) {
                        $commentIDs[$question->ID][] = $commentID;
                        $bestAnswers[$question->ID][] = $answer;
                    }
                }
            }
        }

        return $bestAnswers;
    }

    /**
     * Iterates thru the supplied array of objects,
     * constructing a new array composed of the specified ID for
     * each object.
     * @param  array $objects Array of objects
     * @param  array $keys    Name(s) of properties on each object
     *                        where the ID is located
     *                        e.g.
     *                        ['SubObject', 'ID'] =>
     *                        objectInArray->SubObject->ID
     * @return string         Comma-separated IDs
     */
    protected static function extractIDs (array $objects, array $keys) {
        $ids = array();

        foreach ($objects as $obj) {
            while($propName = current($keys)) {
                $obj = $obj->{$propName};
                next($keys);
            }
            reset($keys);
            $ids []= $obj;
        }

        return implode(',', $ids);
    }

    /**
     * Passes each pair of items supplied in the two
     * arrays to the callback. The truthy return value
     * from the callback is added to the returned array,
     * which stops inner loop execution when a truthy
     * value has been found.
     * @param  array  $structureA Array of items
     * @param  array  $structureB Array of items
     * @param  \Closure $callback   Function to pass each item
     * @return array             Results
     */
    protected static function mergeArraysOfObjects (array $structureA, array $structureB, \Closure $callback) {
        $result = array();

        foreach ($structureA as $objectA) {
            foreach ($structureB as $objectB) {
                if ($merged = $callback($objectA, $objectB)) {
                    $result []= $merged;
                    break;
                }
            }
        }

        return $result;
    }
}
