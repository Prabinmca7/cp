<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Libraries\Decorator,
    RightNow\Libraries\TabularDataObject,
    RightNow\Libraries\ConnectTabular,
    RightNow\Decorators;

class SocialQuestionPermissionsTest extends CPTestCase {

    public $testingClass = 'RightNow\Decorators\SocialQuestionPermissions';

    function getMockedQuestion(array $fields){
        $question = new MockedSocialQuestionForQuestionTest();
        foreach($fields as $key => $value){
            $question->$key = $value;
        }
        return $question;
    }

    function clearCache($decoratorInstance){
        $cache = new \ReflectionProperty($decoratorInstance, 'cache');
        $cache->setAccessible(true);
        $cache->setValue($decoratorInstance, array());
    }

    function setLoggedInUserOnDecorator($decoratorInstance, $authorID, $isUserActive = true, $useConnectObj = false){
        $socialUser = new \ReflectionProperty($decoratorInstance, 'socialUser');
        $socialUser->setAccessible(true);
        if($useConnectObj){
            $user = new Connect\CommunityUser;
            $user->SocialPermissions = new MockUserPermissionsForQuestionTest($isUserActive);
            $socialUser->setValue($decoratorInstance, $user);
        }
        else{
            $socialUser->setValue($decoratorInstance, (object)array('ID' => $authorID, 'SocialPermissions' => new MockUserPermissionsForQuestionTest($isUserActive)));
        }
    }

    /**
     * Creates a question as the given user.  Logs out when done.
     * Takes a username, returns an array with the question and comment
     */
    function createQuestionAsUser($userName) {
        $this->logIn($userName);
        $question = new Connect\CommunityQuestion();
        $question->CreatedByCommunityUser = $this->CI->model('CommunityUser')->get()->result->ID;
        $question->Subject = 'bananas ' . __FUNCTION__;
        $question->Product = 1;
        $question->StatusWithType->Status->LookupName = 'Active';
        $question->save();
        \RightNow\Libraries\Decorator::add($question, array('class' => 'Permission/SocialQuestionPermissions', 'property' => 'SocialPermissions'));
        $this->logOut();
        return $question;
    }

    /**
     * Sets the status of the given question to the given value and clears the permission cache.
     * Logs the current user out and returns with no one logged in.
     */
    private function setQuestionStatus($question, $statusID) {
        $this->logOut();
        $this->logIn('slatest');
        $question->StatusWithType->Status->ID = $statusID;
        $question->save();
        $this->logOut();
        $this->clearCache($question->SocialPermissions);
    }

    function getDecoratedQuestion($id = null){
        if($id === null){
            $question = new Connect\CommunityQuestion;
        }
        else{
            $question = Connect\CommunityQuestion::fetch($id);
        }
        Decorator::add($question, array('class' => 'Permission/SocialQuestionPermissions', 'property' => 'SocialPermissions'));
        return $question;
    }

    function testIsLocked(){
        $mockQuestion = $this->getMockedQuestion(array('Attributes' => (object)array('ContentLocked' => false)));
        $decorator = new Decorators\SocialQuestionPermissions($mockQuestion);

        $this->assertFalse($decorator->isLocked());

        $this->clearCache($decorator);
        $mockQuestion->Attributes->ContentLocked = true;
        $this->assertTrue($decorator->isLocked());

        $this->clearCache($decorator);
        $mockQuestion->Attributes->ContentLocked = null;
        $this->assertFalse($decorator->isLocked());
    }

    function testStatus(){
        $mockQuestion = $this->getMockedQuestion(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_QUESTION_ACTIVE))));
        $decorator = new Decorators\SocialQuestionPermissions($mockQuestion);

        $this->assertTrue($decorator->isActive());
        $this->assertFalse($decorator->isPending());
        $this->assertFalse($decorator->isSuspended());
        $this->assertFalse($decorator->isDeleted());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_PENDING;
        $this->assertFalse($decorator->isActive());
        $this->assertTrue($decorator->isPending());
        $this->assertFalse($decorator->isSuspended());
        $this->assertFalse($decorator->isDeleted());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_SUSPENDED;
        $this->assertFalse($decorator->isActive());
        $this->assertFalse($decorator->isPending());
        $this->assertTrue($decorator->isSuspended());
        $this->assertFalse($decorator->isDeleted());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_DELETED;
        $this->assertFalse($decorator->isActive());
        $this->assertFalse($decorator->isPending());
        $this->assertFalse($decorator->isSuspended());
        $this->assertTrue($decorator->isDeleted());
    }

    function testIsAuthor(){
        $question = $this->createQuestionAsUser('useractive1');

        $this->assertFalse($question->SocialPermissions->isAuthor());

        $this->logIn('useractive2');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->isAuthor());

        $this->logOut();
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->isAuthor());
    }

    function testCanRead(){
        $mockQuestion = $this->getMockedQuestion(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_QUESTION_ACTIVE))));
        $decorator = new Decorators\SocialQuestionPermissions($mockQuestion);

        //Can't read active questions without READ perm
        $this->assertFalse($decorator->canRead());

        //Can read active questions with READ perm
        $this->clearCache($decorator);
        $mockQuestion->setResultForPermission(PERM_SOCIALQUESTION_READ, true);
        $this->assertTrue($decorator->canRead());

        //Can't read non-active
        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_DELETED;
        $this->assertFalse($decorator->canRead());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_PENDING;
        $this->assertFalse($decorator->canRead());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_SUSPENDED;
        $this->assertFalse($decorator->canRead());

        //Can read pending posts if user is author
        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_PENDING;
        $this->logIn('useractive1');
        $mockQuestion->CreatedByCommunityUser = (object)array('ID' => $this->CI->model('CommunityUser')->get()->result->ID);
        $this->assertTrue($decorator->canRead());
        $mockQuestion->CreatedByCommunityUser = null;
        $this->logOut();

        //Can't read pending/suspended with only update delete perm
        $this->clearCache($decorator);
        $mockQuestion->setResultForPermission(PERM_SOCIALQUESTION_UPDATE_STATUS_DELETE, true);
        $this->assertFalse($decorator->canRead());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_PENDING;
        $this->assertFalse($decorator->canRead());

        //and still can't read deleted questions
        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_DELETED;
        $this->assertFalse($decorator->canRead());

        $mockQuestion->setResultForPermission(PERM_SOCIALQUESTION_UPDATE_STATUS_DELETE, false);

        //Can't read pending/suspended with just update status perm
        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_PENDING;
        $mockQuestion->setResultForPermission(PERM_SOCIALQUESTION_UPDATE_STATUS, true);
        $this->assertFalse($decorator->canRead());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_SUSPENDED;
        $this->assertFalse($decorator->canRead());

        //and still can't read deleted questions
        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_DELETED;
        $this->assertFalse($decorator->canRead());

        //Can read pending/suspended questions with both update and update status perms
        $this->logIn('useractive2');
        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_PENDING;
        $mockQuestion->setResultForPermission(PERM_SOCIALQUESTION_UPDATE, true);
        $this->assertTrue($decorator->canRead());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_SUSPENDED;
        $this->assertTrue($decorator->canRead());

        //..but still can't read deleted questions
        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_DELETED;
        $this->assertFalse($decorator->canRead());
    }

    function testCanCreate(){
        $question = new Connect\CommunityQuestion();
        $decorator = new Decorators\SocialQuestionPermissions($question);

        // Can't create without being logged in
        $this->assertFalse($decorator->canCreate());

        // Active users may create
        $this->logIn('useractive1');
        $this->clearCache($decorator);
        $this->assertTrue($decorator->canCreate());
        $this->logOut();

        // Can't create with non-active user
        $this->logIn('userpending');
        $this->clearCache($decorator);
        $this->assertFalse($decorator->canCreate());
        $this->logOut();
    }

    function testCanUpdate(){
        $mockQuestion = $this->getMockedQuestion(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_QUESTION_ACTIVE)),
                                                       'Attributes' => (object)array('ContentLocked' => false)));
        $decorator = new Decorators\SocialQuestionPermissions($mockQuestion);

        //Can't edit without being logged in
        $this->assertFalse($decorator->canUpdate());

        //Can edit with correct perms
        $this->clearCache($decorator);
        $this->logIn('modactive1');
        $mockQuestion->setResultForPermission(PERM_SOCIALQUESTION_UPDATE, true);
        $this->assertTrue($decorator->canUpdate());

        //Can't edit without edit perm
        $this->clearCache($decorator);
        $mockQuestion->setResultForPermission(PERM_SOCIALQUESTION_UPDATE, false);
        $this->assertFalse($decorator->canUpdate());
        $mockQuestion->setResultForPermission(PERM_SOCIALQUESTION_UPDATE, true);

        //Can't edit with non-active user
        $this->logOut();
        $this->logIn('userpending');
        $this->clearCache($decorator);
        $this->assertFalse($decorator->canUpdate());
        $this->logOut();
        $this->logIn('modactive1');

        //Can't edit non-active questions
        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_SUSPENDED;
        $this->assertFalse($decorator->canUpdate());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_DELETED;
        $this->assertFalse($decorator->canUpdate());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_PENDING;
        $this->assertFalse($decorator->canUpdate());

        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_ACTIVE;

        //Can't edit locked questions
        $this->clearCache($decorator);
        $mockQuestion->Attributes->ContentLocked = true;
        $this->assertFalse($decorator->canUpdate());

        //...unless you have permission
        $this->clearCache($decorator);
        $mockQuestion->setResultForPermission(PERM_SOCIALQUESTION_UPDATE_LOCKED, true);
        $this->assertTrue($decorator->canUpdate());
    }

    function testCanUpdateWithTabularData() {
        $question = $this->createQuestionAsUser('userpending');
        $decorator = new Decorators\SocialQuestionPermissions($this->assertResponseObject($this->CI->model('CommunityQuestion')->getTabular($question->ID))->result);

        //Can't edit with non-active user
        $this->logIn('userpending');
        $this->clearCache($decorator);
        $this->assertFalse($decorator->canUpdate());
        $this->logOut();

        $this->destroyObject($question);

        $question = $this->createQuestionAsUser('useractive1');
        $tabularQuestion = $this->assertResponseObject($this->CI->model('CommunityQuestion')->getTabular($question->ID))->result;
        $decorator = new Decorators\SocialQuestionPermissions($tabularQuestion);

        // regular tabular data should work
        $this->logIn('useractive1');
        $this->assertTrue($decorator->canUpdate());

        // blank tabular data should not have permission but not throw an error
        $tabularQuestion = new TabularDataObject();
        $decorator = new Decorators\SocialQuestionPermissions($tabularQuestion);
        $this->assertFalse($decorator->canUpdate());

        // constructed-from-scratch tabular data should have permission
        $tabularQuestion = new TabularDataObject();

        $fakeSocialUser = new stdClass();
        $fakeSocialUser->ID = $question->CreatedByCommunityUser->ID;
        $tabularQuestion->CreatedByCommunityUser = $fakeSocialUser;

        $fakeStatus = new stdClass();
        $fakeStatus->StatusType = new stdClass();
        $fakeStatus->StatusType->ID = $question->StatusWithType->StatusType->ID;
        $tabularQuestion->StatusWithType = $fakeStatus;

        $tabularQuestion->Subject = $question->Subject;
        $decorator = new Decorators\SocialQuestionPermissions($tabularQuestion);
        $this->assertTrue($decorator->canUpdate());

        $this->destroyObject($question);
    }

    function testCanRate(){
        $question = $this->createQuestionAsUser('useractive1');
        $this->assertFalse($question->SocialPermissions->canRate());

        $this->logIn('useractive2');
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canRate());

        //Cannot rate questions with non-active status
        $this->setQuestionStatus($question, 32); // pending
        $this->logIn('useractive2');
        $this->assertFalse($question->SocialPermissions->canRate());

        $this->setQuestionStatus($question, 30); // suspended
        $this->logIn('useractive2');
        $this->assertFalse($question->SocialPermissions->canRate());

        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('useractive2');
        $this->assertFalse($question->SocialPermissions->canRate());

        $this->setQuestionStatus($question, 29); // active

        //Cannot rate their own questions
        $this->logOut();
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canRate());

        //Non active users shouldn't be able to rate
        $this->logOut();
        $this->logIn('userpending');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canRate());

        $this->logOut();
        $this->logIn('usersuspended');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canRate());

        $this->logOut();
        $this->logIn('userdeleted');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canRate());

        //Cannot rate locked questions
        $this->logIn('useradmin');
        $this->clearCache($question->SocialPermissions);
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->update($question->ID, array(
            'CommunityQuestion.Attributes.ContentLocked' => (object) array('value' => true)
        )))->result;
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canRate());
    }

    function testCanDeleteRating(){
        $question = $this->createQuestionAsUser('useractive1');
        $this->logIn('useractive2');
        $rating = $this->assertResponseObject($this->CI->model('CommunityQuestion')->rateQuestion($question, 50))->result;

        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canDeleteRating($rating));

        // Cannot delete ratings on questions with non-active status
        $this->setQuestionStatus($question, 32); // pending
        $this->logIn('useractive2');
        $this->assertFalse($question->SocialPermissions->canDeleteRating($rating));

        $this->setQuestionStatus($question, 30); // suspended
        $this->logIn('useractive2');
        $this->assertFalse($question->SocialPermissions->canDeleteRating($rating));

        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('useractive2');
        $this->assertFalse($question->SocialPermissions->canDeleteRating($rating));

        $this->setQuestionStatus($question, 29); // active

        //Cannot delete ratings on their own questions
        $this->logOut();
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDeleteRating($rating));

        //Non active users shouldn't be able to delete ratings
        $this->logOut();
        $this->logIn('userpending');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDeleteRating($rating));

        $this->logOut();
        $this->logIn('usersuspended');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDeleteRating($rating));

        $this->logOut();
        $this->logIn('userdeleted');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDeleteRating($rating));

        //Cannot delete ratings on locked questions
        $this->logIn('useradmin');
        $this->clearCache($question->SocialPermissions);
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->update($question->ID, array(
            'CommunityQuestion.Attributes.ContentLocked' => (object) array('value' => true)
        )))->result;
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDeleteRating($rating));
    }

    function testCanFlag(){
        $question = $this->createQuestionAsUser('useractive1');
        $this->assertFalse($question->SocialPermissions->canFlag());

        $this->logIn('useractive2');
        //Can flag active questions
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canFlag());

        //Cannot flag questions with non-active status
        $this->setQuestionStatus($question, 32); // pending
        $this->logIn('useractive2');
        $this->assertFalse($question->SocialPermissions->canFlag());

        $this->setQuestionStatus($question, 30); // suspended
        $this->logIn('useractive2');
        $this->assertFalse($question->SocialPermissions->canFlag());

        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('useractive2');
        $this->assertFalse($question->SocialPermissions->canFlag());

        $this->setQuestionStatus($question, 29); // active

        //Cannot flag their own questions
        $this->logOut();
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canFlag());

        //Non active users shouldn't be able to flag
        $this->logOut();
        $this->logIn('userpending');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canFlag());

        $this->logOut();
        $this->logIn('usersuspended');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canFlag());

        $this->logOut();
        $this->logIn('userdeleted');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canFlag());

        //Cannot flag locked questions
        $this->logIn('useradmin');
        $this->clearCache($question->SocialPermissions);
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->update($question->ID, array(
            'CommunityQuestion.Attributes.ContentLocked' => (object) array('value' => true)
        )))->result;
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canFlag());
    }

    function testCanDeleteFlag(){
        $question = $this->createQuestionAsUser('useractive1');
        $this->logIn('useractive2');
        $flag = $this->assertResponseObject($this->CI->model('CommunityQuestion')->flagQuestion($question, 1))->result;

        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canDeleteFlag($flag));

        //Cannot flag questions with non-active status
        $this->setQuestionStatus($question, 32); // pending
        $this->logIn('useractive2');
        $this->assertFalse($question->SocialPermissions->canDeleteFlag($flag));

        $this->setQuestionStatus($question, 30); // suspended
        $this->logIn('useractive2');
        $this->assertFalse($question->SocialPermissions->canDeleteFlag($flag));

        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('useractive2');
        $this->assertFalse($question->SocialPermissions->canDeleteFlag($flag));

        $this->setQuestionStatus($question, 29); // active

        //Cannot flag their own questions
        $this->logOut();
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDeleteFlag($flag));

        //Non active users shouldn't be able to flag
        $this->logOut();
        $this->logIn('userpending');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDeleteFlag($flag));

        $this->logOut();
        $this->logIn('usersuspended');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDeleteFlag($flag));

        $this->logOut();
        $this->logIn('userdeleted');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDeleteFlag($flag));

        //Cannot flag locked questions
        $this->logIn('useradmin');
        $this->clearCache($question->SocialPermissions);
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->update($question->ID, array(
            'CommunityQuestion.Attributes.ContentLocked' => (object) array('value' => true)
        )))->result;
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDeleteFlag($flag));
    }

    function testCanComment(){
        $question = $this->createQuestionAsUser('useractive1');
        $this->assertFalse($question->SocialPermissions->canComment());

        $this->logIn('useractive2');
        //Can comment on active questions
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canComment());

        //Cannot comment on questions with non-active status
        $this->setQuestionStatus($question, 32); // pending
        $this->logIn('useractive2');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canComment());

        $this->setQuestionStatus($question, 30); // suspended
        $this->logIn('useractive2');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canComment());

        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('useractive2');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canComment());

        //Even mods can't comment on non-active questions
        $this->setQuestionStatus($question, 32); // pending
        $this->logIn('modactive2');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canComment());

        $this->setQuestionStatus($question, 30); // suspended
        $this->logIn('modactive2');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canComment());

        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('modactive2');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canComment());

        $this->setQuestionStatus($question, 29); // active

        //Can comment on their own questions
        $this->logOut();
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canComment());

        //Non active users shouldn't be able to comment
        $this->logOut();
        $this->logIn('userpending');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canComment());

        $this->logOut();
        $this->logIn('usersuspended');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canComment());

        $this->logOut();
        $this->logIn('userdeleted');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canComment());

        //Cannot comment on locked questions
        $this->logIn('useradmin');
        $this->clearCache($question->SocialPermissions);

        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->update($question->ID, array(
            'CommunityQuestion.Attributes.ContentLocked' => (object) array('value' => true)
        )))->result;
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canComment());

        //Mods can comment on locked questions
        $this->logIn('modactive2');
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canComment());
    }

    function testIsUnlockedOrUserCanChangeLockStatus(){
        $mockQuestion = $this->getMockedQuestion(array('Attributes' => (object)array('ContentLocked' => false)));
        $decorator = new Decorators\SocialQuestionPermissions($mockQuestion);

        $this->assertTrue($decorator->isUnlockedOrUserCanChangeLockStatus());

        $this->clearCache($decorator);
        $mockQuestion->Attributes->ContentLocked = true;
        $this->assertFalse($decorator->isUnlockedOrUserCanChangeLockStatus());

        $this->clearCache($decorator);
        $mockQuestion->setResultForPermission(PERM_SOCIALQUESTION_UPDATE_LOCKED, true);
        $this->assertTrue($decorator->isUnlockedOrUserCanChangeLockStatus());
    }

    function testAreUserAndQuestionActiveOrUserCanChangeStatus(){
        $mockQuestion = $this->getMockedQuestion(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_QUESTION_ACTIVE))));
        $decorator = new Decorators\SocialQuestionPermissions($mockQuestion);

        //Test with no user
        $this->assertFalse($decorator->areUserAndQuestionActiveOrUserCanChangeStatus());

        //Test with inactive social user
        $this->clearCache($decorator);
        $this->logIn('userpending');
        $this->assertFalse($decorator->areUserAndQuestionActiveOrUserCanChangeStatus());

        $this->clearCache($decorator);
        $this->logOut();
        $this->logIn('useractive1');
        $this->assertTrue($decorator->areUserAndQuestionActiveOrUserCanChangeStatus());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_SUSPENDED;
        $this->assertFalse($decorator->areUserAndQuestionActiveOrUserCanChangeStatus());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_DELETED;
        $this->assertFalse($decorator->areUserAndQuestionActiveOrUserCanChangeStatus());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_PENDING;
        $this->assertFalse($decorator->areUserAndQuestionActiveOrUserCanChangeStatus());

        $this->clearCache($decorator);
        $mockQuestion->setResultForPermission(PERM_SOCIALQUESTION_UPDATE_STATUS, true);
        $this->assertFalse($decorator->areUserAndQuestionActiveOrUserCanChangeStatus());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_SUSPENDED;
        $this->assertFalse($decorator->areUserAndQuestionActiveOrUserCanChangeStatus());

        $this->clearCache($decorator);
        $mockQuestion->setResultForPermission(PERM_SOCIALQUESTION_UPDATE, true);
        $this->assertTrue($decorator->areUserAndQuestionActiveOrUserCanChangeStatus());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_PENDING;
        $this->assertTrue($decorator->areUserAndQuestionActiveOrUserCanChangeStatus());

        $this->clearCache($decorator);
        $mockQuestion->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_QUESTION_DELETED;
        $this->assertFalse($decorator->areUserAndQuestionActiveOrUserCanChangeStatus());
    }

    function testCanUpdateStatus() {
        $question = $this->createQuestionAsUser('useractive1');
        $this->assertFalse($question->SocialPermissions->canUpdateStatus());

        //Normal user should not be allowed to change status
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateStatus());
        $this->logOut();

        //Moderator should be allowed to change status
        $this->logIn('modactive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canUpdateStatus());
        $this->logOut();

        //Moderator should not be allowed to change status of a deleted question
        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('modactive1');
        $this->assertFalse($question->SocialPermissions->canUpdateStatus());

        $this->setQuestionStatus($question, 29); // active

        //Non active moderators shouldn't be allowed to change status
        $this->logIn('modpending');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateStatus());
        $this->logOut();

        $this->logIn('modsuspended');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateStatus());
        $this->logOut();

        $this->logIn('moddeleted');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateStatus());
        $this->logOut();

        $this->logIn('modarchive');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateStatus());
        $this->logOut();

        $this->logIn('userupdateonly');
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canUpdateStatus());
        $this->logOut();

        $this->logIn('userdeletenoupdatestatus');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateStatus());
        $this->logOut();
    }

    function testCanDelete() {
        $question = $this->createQuestionAsUser('useractive1');
        $this->assertFalse($question->SocialPermissions->canDelete());

        // Normal user should not be allowed to delete
        $this->logIn('useractive2');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDelete());
        $this->logOut();

        // Normal user can delete their own content
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canDelete());
        $this->logOut();

        // Moderator should be allowed to delete
        $this->logIn('modactive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canDelete());
        $this->logOut();

        // Moderator should not be allowed to delete a deleted question
        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('modactive1');
        $this->assertFalse($question->SocialPermissions->canDelete());

        $this->setQuestionStatus($question, 29); // active

        // Non active moderators shouldn't be allowed to delete
        $this->logIn('modpending');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDelete());
        $this->logOut();

        $this->logIn('modsuspended');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDelete());
        $this->logOut();

        $this->logIn('moddeleted');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDelete());
        $this->logOut();

        $this->logIn('modarchive');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDelete());
        $this->logOut();

        $this->logIn('userupdateonly');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canDelete());
        $this->logOut();

        $this->logIn('userdeletenoupdatestatus');
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canDelete());
        $this->logOut();
    }

    function testCanUpdateInterface() {
        $question = $this->createQuestionAsUser('useractive1');
        $this->assertFalse($question->SocialPermissions->canUpdateInterface());

        // Normal user should not be allowed to update interface
        $this->logIn('useractive2');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateInterface());
        $this->logOut();

        // Moderator should be allowed to update interface
        $this->logIn('modactive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canUpdateInterface());
        $this->logOut();

        // Moderator should not be allowed to update interface on a deleted question
        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('modactive1');
        $this->assertFalse($question->SocialPermissions->canUpdateInterface());

        $this->setQuestionStatus($question, 29); // active

        // Non active moderators shouldn't be allowed to update interface
        $this->logIn('modpending');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateInterface());
        $this->logOut();

        $this->logIn('modsuspended');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateInterface());
        $this->logOut();

        $this->logIn('moddeleted');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateInterface());
        $this->logOut();

        $this->logIn('modarchive');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateInterface());
        $this->logOut();
    }

    function testCanSelectBestAnswerAs() {
        $question = $this->createQuestionAsUser('useractive1');
        $this->assertFalse($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_AUTHOR));
        $this->assertFalse($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_MODERATOR));

        // Normal user should not be allowed to select best answer
        $this->logIn('useractive2');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_AUTHOR));
        $this->assertFalse($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_MODERATOR));
        $this->logOut();

        // Author should be allowed to select best answer as author, not moderator
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_AUTHOR));
        $this->assertFalse($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_MODERATOR));
        $this->logOut();

        // Moderator should be allowed to select best answer, not as author
        $this->logIn('modactive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_MODERATOR));
        $this->assertFalse($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_AUTHOR));
        $this->logOut();

        // Moderator should not be allowed to select best answer on a deleted question
        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('modactive1');
        $this->assertFalse($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_MODERATOR));

        // Author should not be allowed to select best answer on a deleted question
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_AUTHOR));

        $this->setQuestionStatus($question, 29); // active

        // Non active moderators shouldn't be allowed to select best answer
        $this->logIn('modpending');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_MODERATOR));
        $this->logOut();

        $this->logIn('modsuspended');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_MODERATOR));
        $this->logOut();

        $this->logIn('moddeleted');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_MODERATOR));
        $this->logOut();

        $this->logIn('modarchive');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_MODERATOR));
        $this->logOut();

        // Author who is also moderator
        $this->destroyObject($question);
        $question =  $this->createQuestionAsUser('modactive1');
        $this->login('modactive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_MODERATOR));
        $this->assertTrue($question->SocialPermissions->canSelectBestAnswerAs(SSS_BEST_ANSWER_AUTHOR));
    }

    function testCanUpdateLock() {
        $question = $this->createQuestionAsUser('useractive1');
        $this->assertFalse($question->SocialPermissions->canUpdateLock());

        //Moderator should be allowed to lock/unlock a question
        $this->logIn('modactive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertTrue($question->SocialPermissions->canUpdateLock());
        $this->logOut();

        //Moderator should not be allowed to lock/unlock a deleted question
        $this->setQuestionStatus($question, 31); // deleted
        $this->logIn('modactive1');
        $this->assertFalse($question->SocialPermissions->canUpdateLock());

        $this->setQuestionStatus($question, 29); // active

        //Non moderator should not be allowed to lock/unlock a question
        $this->logIn('useractive1');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateLock());
        $this->logOut();

        //Non active moderators shouldn't be allowed to lock/unlock a question
        $this->logIn('modpending');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateLock());
        $this->logOut();

        $this->logIn('modsuspended');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateLock());
        $this->logOut();

        $this->logIn('moddeleted');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateLock());
        $this->logOut();

        $this->logIn('modarchive');
        $this->clearCache($question->SocialPermissions);
        $this->assertFalse($question->SocialPermissions->canUpdateLock());
        $this->logOut();
    }

    function testCanMove() {
        $question = $this->getDecoratedQuestion(10);
        $this->assertFalse($question->SocialPermissions->canMove(2, 'Product'));

        //Moderator should be allowed to move a question
        $this->logIn('modactive1');
        $question = $this->getDecoratedQuestion(100);
        $this->assertTrue($question->SocialPermissions->canMove(6, 'Product'));

        //Non moderator should not be allowed to move a question
        $this->logIn('useractive1');
        $question = $this->getDecoratedQuestion(100);
        $this->assertFalse($question->SocialPermissions->canMove(6, 'Product'));
        $this->logOut();
    }
}

class MockedSocialQuestionForQuestionTest extends Connect\RNObject{
    private $permissionResults = array();

    static function &getMetadata(){
        return (object)array('COM_type' => 'CommunityQuestion');
    }

    function setResultForPermission($permissionID, $returnValue){
        $this->permissionResults[$permissionID] = $returnValue;
    }

    function clearPermissions(){
        $this->permissionResults = array();
    }

    function hasPermission($permission){
        $permissionID = $permission instanceof ConnectPHP\NamedID ? $permission->ID : $permission;
        return $this->permissionResults[$permissionID] ?: false;
    }
}

class MockUserPermissionsForQuestionTest{

    function __construct($isUserActive){
        $this->isUserActive = $isUserActive;
    }
    function isActive(){
        return $this->isUserActive;
    }
}
