<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class UserActivityTest extends WidgetTestCase {
    public $testingWidget = 'standard/user/UserActivity';

    function testNothingRendersWhenUrlParameterOrUserIDAttrIsAbsent () {
        $this->createWidgetInstance();
        $return = $this->widgetInstance->getData();
        $this->assertFalse($return);
    }

    function testNothingRendersWhenUserDoesntExist () {
        $this->createWidgetInstance(array('user_id' => 1000000000));
        $return = $this->widgetInstance->getData();
        $this->assertFalse($return);
    }

    function testDeletedParentComment() {
        $this->logIn('useractive1');
        list($fixtureInstance, $question, $childComment) = $this->getFixtures(array('QuestionActiveParentCommentDeleted', 'CommentToUseWithDeletedParent'));
        $this->logout();
        $this->createWidgetInstance(array('user_id' => 215, 'type' => "question,comment,bestAnswerGivenToUser,bestAnswerGivenByUser,commentRatingGivenToUser,commentRatingGivenByUser,questionRatingGivenToUser,questionRatingGivenByUser"));
        $widgetData = $this->getWidgetData();
        $activityIDs = array_map(function($activity) {
            return $activity->ID;
        }, $widgetData['activity']);
        $this->assertTrue(!in_array($childComment->Parent->ID, $activityIDs));
        $fixtureInstance->destroy();
     }

    function testUserActivityWithActiveReply() {
        list($fixtureInstance, $question, $parentComment, $reply) = $this->getFixtures(array('QuestionActiveUserActive', 'CommentWithRepliesUserArchive', 'CommentActiveModActive'));
        $this->createWidgetInstance(array('user_id' => $reply->CreatedByCommunityUser->ID, 'type' => "question,comment,bestAnswerGivenToUser,bestAnswerGivenByUser,commentRatingGivenToUser,commentRatingGivenByUser,questionRatingGivenToUser,questionRatingGivenByUser"));
        $widgetData = $this->getWidgetData();
        $this->assertEqual($reply->ID, $widgetData['activity'][0]->ID);
        $fixtureInstance->destroy();
     }

    function testAllActivities () {
        $this->createWidgetInstance(array('user_id' => 215, 'type' => "question,comment,bestAnswerGivenToUser,bestAnswerGivenByUser,commentRatingGivenToUser,commentRatingGivenByUser,questionRatingGivenToUser,questionRatingGivenByUser"));
        $widgetData = $this->getWidgetData();
        $this->assertIsA($widgetData['activity'], 'array');
        $this->assertIsA($widgetData['activityOrdering'], 'array');
        $this->assertEqual(count($widgetData['activityOrdering']), $widgetData['attrs']['limit']);
        $this->assertIsA($widgetData['user'], 'RightNow\Connect\v1_4\CommunityUser');
    }

    function testGetDate () {
        $getDate = $this->getWidgetMethod('getDate');
        $action = (object)array(
            'CreatedTime' => '2014-01-02T03:04:30Z',
            'CommunityQuestion' => (object)array(
                'BestCommunityQuestionAnswers' => (object)array(
                    'CreatedTime' => '2014-01-02T03:04:30Z',
                ),
                'BestSocialQuestionCreatedTime' => '2014-01-03T03:04:30Z',
            ),
            'UserRating' => (object)array(
                'CreatedTime' => '2014-08-09T10:11:30Z',
            ),
        );
        $this->assertIdentical('2014-01-02T03:04:30Z', $getDate('question', $action));
        $this->assertIdentical('2014-01-02T03:04:30Z', $getDate('comment', $action));
        $this->assertIdentical('2014-01-03T03:04:30Z', $getDate('bestAnswerGivenByUser', $action));
        $this->assertIdentical('2014-01-03T03:04:30Z', $getDate('bestAnswerGivenToUser', $action));
        $this->assertIdentical('2014-08-09T10:11:30Z', $getDate('commentRatingGivenByUser', $action));
        $this->assertIdentical('2014-08-09T10:11:30Z', $getDate('commentRatingGivenToUser', $action));
        $this->assertIdentical('2014-08-09T10:11:30Z', $getDate('questionRatingGivenByUser', $action));
        $this->assertIdentical('2014-08-09T10:11:30Z', $getDate('questionRatingGivenToUser', $action));
    }
}
