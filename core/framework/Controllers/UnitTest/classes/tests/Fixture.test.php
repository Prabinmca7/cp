<?php

use RightNow\Utils\Text,
    RightNow\Connect\v1_4 as Connect;

class UnitTestFixtureTest extends CPTestCase {
    public $testingClass = 'RightNow\UnitTest\Fixture';

    function __construct() {
        parent::__construct();
    }

    function testMakeLoadError() {
        $fixtureInstance = new $this->testingClass();
        $fixture = $fixtureInstance->make('QuestionNotReal');
        $this->assertTrue(count($fixtureInstance->errors) > 0);
        $this->assertSame('No fixture loaded for QuestionNotReal', $fixtureInstance->errors[2]);
    }

    function testMakeWithUnloadedFixture() {
        $fixtureInstance = new $this->testingClass();
        $fixture = $fixtureInstance->make('QuestionActiveModActive');
        $this->assertIsA($fixture, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestion');
        $this->assertIsA($fixture->ID, 'int');
        $fixtureInstance->destroy();
    }

    function testMakeWithAlreadyLoadedFixture() {
        $fixtureInstance = new $this->testingClass();
        $fixture = $fixtureInstance->make('QuestionActiveModActive');
        $fixture = $fixtureInstance->make('QuestionActiveModActive');
        $this->assertIsA($fixture, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestion');
        $this->assertIsA($fixture->ID, 'int');
    }

    function testDestroy() {
        $fixtureInstance = new $this->testingClass();
        list(, $loadedFixtures, $variables, $variableTypes) = $this->reflect('loadedFixtures', 'variables', 'variableTypes');

        $getPropertyCount = function($property) use ($fixtureInstance) {
            return count($property->getValue($fixtureInstance));
        };

        $question = $fixtureInstance->make('QuestionActiveModActive');
        $this->assertIsA($question, 'RightNow\Connect\v1_4\CommunityQuestion');

        $user = $fixtureInstance->make('UserModActive');
        $this->assertIsA($user, 'RightNow\Connect\v1_4\CommunityUser');

        $this->assertTrue($getPropertyCount($loadedFixtures) > 0);
        $this->assertTrue($getPropertyCount($variables) > 0);
        $this->assertTrue($getPropertyCount($variableTypes) > 0);

        $objects = $variables->getValue($fixtureInstance);

        $fixtureInstance->destroy();

        $this->assertEqual($getPropertyCount($loadedFixtures), 0);
        // 18 database social users
        $this->assertEqual($getPropertyCount($variables), 18);
        $this->assertEqual($getPropertyCount($variableTypes), 1);
        $this->assertEqual(array_keys($variableTypes->getValue($fixtureInstance)), array('DatabaseSocialUser'));
        // JVSWFIXTUREHACK
        return;

        $primaryObjects = array(
            'Contact',
            'CommunityUser',
            'CommunityQuestion',
            'CommunityComment',
        );

        $getRowCount = function($objectName, $ID) {
            $column = 'COUNT()';
            $query = "SELECT $column FROM $objectName WHERE ID = $ID";
            return (int) \RightNow\Libraries\ConnectTabular::query($query)->getFirst()->{$column};
        };

        foreach($objects as $variable => $ID) {
            $objectName = Text::getSubstringBefore($variable, '_', $variable);
            if ($ID && in_array($objectName, $primaryObjects)) {
                $this->assertEqual(0, $getRowCount($objectName, $ID));
            }
        }
    }

    function testLoadObjectFromFixture() {
        $loadObjectFromFixture = $this->getMethod('loadObjectFromFixture');
        $loadedObject = $loadObjectFromFixture('QuestionActiveModActive');
        $this->assertIsA($loadedObject->ID, 'int');
        $this->assertIsA($loadedObject, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestion');
        $this->assertTrue($loadedObject->CreatedByCommunityUser->ID > 0);
    }

    function testAddDeferredSubFixturesComments() {
        $question = new Connect\CommunityQuestion();
        $question->Subject = 'books';
        $question->Body = 'walruses';
        $question->save();

        $comment = array(
            'fixture' => 'CommentActiveModActive',
            'parameters' => array(
                'CommunityQuestion' => $question->ID,
            ),
        );

        $fixturesToAdd = array(
            'CommunityQuestion._comments' => array($comment),
        );

        $fixtureInstance = new $this->testingClass();
        $addDeferredSubFixtures = $this->getMethod('addDeferredSubFixtures', false, $fixtureInstance);
        $addDeferredSubFixtures($question, $fixturesToAdd);

        $rql = "SELECT ID FROM CommunityComment WHERE CommunityQuestion = {$question->ID}";
        $comment = Connect\ROQL::query($rql)->next()->next();

        $this->assertNotNull($comment);
        $this->assertTrue($comment['ID'] > 0);

        $fixtureInstance->destroy();
        $this->destroyObject($question);
    }

    function testAddDeferredSubFixturesNonComments() {
        $question = new Connect\CommunityQuestion();
        $question->Subject = 'books';
        $question->Body = 'walruses';
        $question->save();

        $bestAnswer = array('fixture' => 'BestAnswerAuthor');

        $comment = array(
            'fixture' => 'CommentActiveUserActive',
            'parameters' => array(
                'CommunityQuestion' => $question->ID,
            ),
        );

        $fixturesToAdd = array(
            'CommunityQuestion._comments' => array($comment),
            'CommunityQuestion.BestCommunityQuestionAnswers' => array($bestAnswer),
        );

        $fixtureInstance = new $this->testingClass();
        $addDeferredSubFixtures = $this->getMethod('addDeferredSubFixtures', false, $fixtureInstance);
        $addDeferredSubFixtures($question, $fixturesToAdd);

        $this->assertTrue(count($question->BestCommunityQuestionAnswers) > 0);
        $this->assertIsA($question->BestCommunityQuestionAnswers[0], CONNECT_NAMESPACE_PREFIX . '\BestCommunityQuestionAnswer');

        $fixtureInstance->destroy();
        $this->destroyObject($question);
    }

    function testProcessDetachedFixtures() {
        $question = new Connect\CommunityQuestion();
        $question->Subject = 'books';
        $question->Body = 'walruses';
        $question->save();

        $comment = array(
            'fixture' => 'CommentActiveModActive',
            'parameters' => array(
                'CommunityQuestion' => $question->ID,
            ),
        );

        $fixtureInstance = new $this->testingClass();
        $processDetachedFixtures = $this->getMethod('processDetachedFixtures', false, $fixtureInstance);
        $processDetachedFixtures($question, array($comment));

        $rql = "SELECT ID FROM CommunityComment WHERE CommunityQuestion = {$question->ID}";
        $comment = Connect\ROQL::query($rql)->next()->next();

        $this->assertNotNull($comment);
        $this->assertTrue($comment['ID'] > 0);

        $fixtureInstance->destroy();
        $this->destroyObject($question);
    }

    function testProcessAttachedFixture() {
        $fixtureInstance = new $this->testingClass();
        list(, $addDeferredSubFixtures, $processAttachedFixtures) = $this->reflect('method:addDeferredSubFixtures', 'method:processAttachedFixtures');

        $question = new Connect\CommunityQuestion();
        $question->Subject = 'books';
        $question->Body = 'walruses';
        $question->save();

        $comment = array(
            'fixture' => 'CommentActiveUserActive',
            'parameters' => array(
                'CommunityQuestion' => $question->ID,
            ),
        );

        $fixturesToAdd = array(
            'CommunityQuestion._comments' => array($comment),
        );

        $addDeferredSubFixtures->invoke($fixtureInstance, $question, $fixturesToAdd);

        $fieldNameArray = array(
            'CommunityQuestion',
            'BestCommunityQuestionAnswers',
        );

        $bestAnswer = array(
            'fixture' => 'BestAnswerAuthor',
        );

        $processAttachedFixtures->invoke($fixtureInstance, $question, $fieldNameArray, array($bestAnswer));

        $this->assertTrue(count($question->BestCommunityQuestionAnswers) > 0);
        $this->assertIsA($question->BestCommunityQuestionAnswers[0], CONNECT_NAMESPACE_PREFIX . '\BestCommunityQuestionAnswer');

        $fixtureInstance->destroy();
        $this->destroyObject($question);
    }

    function testLoadPrimaryObjectFixture() {
        $question = new Connect\CommunityQuestion();
        $question->Subject = 'books';
        $question->Body = 'walruses';
        $question->save();

        $comment = array(
            'fixture' => 'CommentActiveModActive',
            'parameters' => array(
                'CommunityQuestion' => $question->ID,
            ),
        );

        $fixtureInstance = new $this->testingClass();
        $loadPrimaryObjectFixture = $this->getMethod('loadPrimaryObjectFixture', false, $fixtureInstance);
        $loadedComment = $loadPrimaryObjectFixture($question, $comment);

        $this->assertNotNull($loadedComment);
        $this->assertTrue(count($loadedComment->ID) > 0);

        $fixtureInstance->destroy();
        $this->destroyObject($question);
    }

    function testLoadNonPrimaryObjectFixture() {
        $loadNonPrimaryObjectFixture = $this->getMethod('loadNonPrimaryObjectFixture');
        $loadedRating = $loadNonPrimaryObjectFixture('RatingSocialQuestion');

        $this->assertNotNull($loadedRating);
        $this->assertNull($loadedRating->ID);
        $this->assertIsA($loadedRating, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestionRtg');
    }

    function testAddUserFromFixture() {
        $fixtureInstance = new $this->testingClass();
        $addUser = $this->getMethod('addUser', false, $fixtureInstance);

        $question = new Connect\CommunityQuestion();
        $question->Body = 'bananas';
        $fieldNameArray = 'CommunityQuestion.CreatedByCommunityUser';
        $toAdd = array('fixture' => 'UserModActive');
        $addUser($question, $fieldNameArray, $toAdd);
        $this->assertTrue($question->CreatedByCommunityUser->ID > 0);
        $this->assertSame('bananas', $question->Body);

        $user = new Connect\CommunityUser();
        $user->DisplayName = 'dude';
        $addUser($user, 'CommunityUser.Contact', array('fixture' => 'ContactActive1'));
        $this->assertIsA($user->Contact, CONNECT_NAMESPACE_PREFIX . '\Contact');

        $fixtureInstance->destroy();
    }

    function testUserAssociatedToContact() {
        $fixtureInstance = new $this->testingClass();

        $fixture = $fixtureInstance->make('QuestionActiveSingleComment');
        $this->assertIsA($fixture, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestion');
        $this->assertIsA($fixture->CreatedByCommunityUser->Contact, CONNECT_NAMESPACE_PREFIX . '\Contact');

        $fixture = $fixtureInstance->make('UserActive1');
        $this->assertIsA($fixture, CONNECT_NAMESPACE_PREFIX . '\CommunityUser');
        $this->assertIsA($fixture->Contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
    }

    function testFillParameters() {
        $mockLoadedObject = (object) array('ID' => 15);
        $mockParams = array(
            'walrus' => 'shoes',
            'book.ID' => 'this.ID',
        );

        $fillParameters = $this->getMethod('fillParameters');
        $filled = $fillParameters($mockLoadedObject, $mockParams);

        $this->assertSame('shoes', $filled['walrus']);
        $this->assertSame(15, $filled['book.ID']);
    }

    function testLoadValidFixtureFromFile() {
        $loadFixtureFromFile = $this->getMethod('loadFixtureFromFile');
        $loadedFixture = $loadFixtureFromFile('QuestionActiveModActive');
        $this->assertIsA($loadedFixture['type'], 'string');
        $this->assertIsA($loadedFixture['object'], 'array');
    }

    function testAddVariable() {
        $method = $this->getMethod('addVariable');
        $fixtureInstance = new $this->testingClass();
        $this->assertEqual('CommunityUser', $method('CommunityUser', 123));
        $this->assertNull($method('CommunityUser', 123));
        $this->assertEqual('CommunityUser_1', $method('CommunityUser', 456));
    }

    function testAddUserVariable() {
        $method = $this->getMethod('addUserVariable');
        $fixtureInstance = new $this->testingClass();
        // CommunityQuestion
        $fixture = $fixtureInstance->make('QuestionActiveModActive');
        $this->assertEqual('CommunityUser', $method($fixture));
        $this->assertNull($method($fixture)); // already added

        // CommunityComment with a different CommunityUser
        $fixture = $fixtureInstance->make('CommentActiveUserActive');
        $this->assertEqual('CommunityUser_1', $method($fixture));
        $this->assertNull($method($fixture)); // already added

        // CommunityUser unique from above
        $fixture = $fixtureInstance->make('UserModArchive');
        $this->assertEqual('CommunityUser_2', $method($fixture));
        $this->assertNull($method($fixture)); // already added
    }

    function testMakeQuestionWithTheWorks() {
        $fixtureInstance = new $this->testingClass();
        $question = $fixtureInstance->make('QuestionActiveLongAuthorBestAnswer');

        $this->assertNotNull($question);
        $this->assertTrue($question->ID > 0);
        $this->assertNotNull($question->BestCommunityQuestionAnswers[0]->CommunityComment);

        // Ensure fixture's BestCommunityQuestionAnswers have been saved to the DB
        $rql = "SELECT CommunityQuestion.BestCommunityQuestionAnswers.BestAnswerType.ID as BestSocialQuestionAnswerType FROM CommunityQuestion WHERE CommunityQuestion.ID = {$question->ID}";
        $comment = Connect\ROQL::query($rql)->next()->next();
        $this->assertNotNull($comment['BestSocialQuestionAnswerType']);

        $rql = "SELECT ID FROM CommunityComment WHERE CommunityQuestion = {$question->ID}";
        $comment = Connect\ROQL::query($rql)->next()->next();
        $this->assertTrue($comment['ID'] > 0);

        $rql = "SELECT RatingValue FROM CommunityQuestionRtg WHERE CommunityQuestion = {$question->ID}";
        $questionRating = Connect\ROQL::query($rql)->next()->next();
        $this->assertNotNull($questionRating);
        $this->assertSame('100', $questionRating['RatingValue']);

        $rql = "SELECT RatingValue FROM CommunityCommentRtg WHERE CommunityComment = {$comment['ID']}";
        $commentRating = Connect\ROQL::query($rql)->next()->next();
        $this->assertNotNull($commentRating);
        $this->assertSame('100', $commentRating['RatingValue']);

        // until we bring back flagging, ignore these tests
        return;
        $rql = "SELECT Type FROM CommunityQuestionFlg WHERE CommunityQuestion = {$question->ID}";
        $questionFlag = Connect\ROQL::query($rql)->next()->next();
        $this->assertSame('1', $questionFlag['Type']);

        $rql = "SELECT Type FROM CommunityCommentFlg WHERE CommunityComment = {$comment['ID']}";
        $commentFlag = Connect\ROQL::query($rql)->next()->next();
        $this->assertSame('1', $commentFlag['Type']);
    }

    function testLogIn() {
        $fixtureInstance = new $this->testingClass();
        $user = $fixtureInstance->make('UserModActive');

        $this->assertNotNull($user);
        $this->assertNotNull($user->Contact);

        $this->logIn($user->Contact->Login);
        $this->assertNotNull($this->CI->model('CommunityUser')->get()->result);

        $this->logOut();
        $fixtureInstance->destroy();
    }

    function testModPermissions() {
        $fixtureInstance = new $this->testingClass();
        $user = $fixtureInstance->make('UserModActive');

        $this->assertNotNull($user);
        $this->assertNotNull($user->Contact);

        $this->logIn($user->Contact->Login);
        $socialUser = $this->CI->model('CommunityUser')->get()->result;
        $this->assertTrue($socialUser->SocialPermissions->canUpdateStatus());

        $this->logOut();
        $fixtureInstance->destroy();
    }

    function testSocialuserPermissions() {
        $fixtureInstance = new $this->testingClass();
        $user = $fixtureInstance->make('UserActive1');

        $this->assertNotNull($user);
        $this->assertNotNull($user->Contact);

        $this->logIn($user->Contact->Login);
        $socialUser = $this->CI->model('CommunityUser')->get()->result;
        $this->assertFalse($socialUser->SocialPermissions->canUpdateStatus());
        $this->assertTrue($socialUser->SocialPermissions->canUpdateAvatar());

        $this->logOut();
        $fixtureInstance->destroy();
    }
}
