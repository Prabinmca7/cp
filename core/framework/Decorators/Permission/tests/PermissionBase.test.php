<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class PermissionBaseTest extends CPTestCase {

    function testCanUserModify(){
        $testsAndExpectedResults = array(
            //users with no social associations can't modify
            'eturner@rightnow.com.invalid' => false,
            //active social users can modify
            'useractive1' => true,
            //non-active social users can't modify
            'userpending' => false,
            'userdeleted' => false,
            'usersuspended' => false,
            //active mods can modify
            'modactive1' => true,
            //non-active mods can't modify
            'modpending' => false,
            'moddeleted' => false,
            'modsuspended' => false
        );

        $mock = new MockedConnectObjectForPermissionBaseTest('TestObject');

        foreach($testsAndExpectedResults as $login => $result) {
            $this->logIn($login);
            $decorated = new PermissionBaseExtension($mock);
            $this->assertSame($decorated->canUserModify(), $result);
            $this->logOut();
        }

        //can pass custom args
        $this->logIn('userpending');
        $this->assertTrue($decorated->canUserModify(array( STATUS_TYPE_SSS_USER_ACTIVE, STATUS_TYPE_SSS_USER_PENDING )));
        $this->logOut();
    }

    function testIsStatusOf(){
        $mock = new MockedConnectObjectForPermissionBaseTest('TestObject');
        $decorated = new PermissionBaseExtension($mock);

        $decorated->setStatus(1);
        $this->assertTrue($decorated->callStatusMethod(1));
        $this->assertFalse($decorated->callStatusMethod(null));
        $this->assertFalse($decorated->callStatusMethod(2));
    }

    function testGetObjectShell(){
        $mock = new MockedConnectObjectForPermissionBaseTest('TestObject');
        $decorated = new PermissionBaseExtension($mock);

        $socialQuestion = $decorated->callGetObjectShell('CommunityQuestion');
        $this->assertIsA($socialQuestion, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestion');

        $incident = $decorated->callGetObjectShell('Incident');
        $this->assertIsA($incident, CONNECT_NAMESPACE_PREFIX . '\Incident');

        $incident = $decorated->callGetObjectShell('Incident');
        $this->assertIsA($incident, CONNECT_NAMESPACE_PREFIX . '\Incident');
    }

    function testGetSocialObjectShellWithTabularData(){
        $this->logIn('useractive1');
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'oranges ' . __FUNCTION__),
            'CommunityQuestion.Body' => (object) array('value' => 'bananas ' . __FUNCTION__),
            // include an optlist
            'CommunityQuestion.BodyContentType.ID' => (object) array('value' => 2),
            // include an object which is not an optlist
            'CommunityQuestion.Product' => (object) array('value' => 1),
        )))->result;
        $tabularQuestion = $this->assertResponseObject($this->CI->model('CommunityQuestion')->getTabular($question->ID))->result;
        $decorated = new PermissionBaseExtension($tabularQuestion);

        $questionShell = $decorated->callGetSocialObjectShell('CommunityQuestion');
        $this->assertIdentical($question->CreatedByCommunityUser->ID, $questionShell->CreatedByCommunityUser->ID);
        $this->assertIdentical($question->Subject, $questionShell->Subject);
        $this->assertIdentical($question->Body, $questionShell->Body);
        $this->assertIdentical($question->BodyContentType->ID, $questionShell->BodyContentType->ID);
        $this->assertIdentical($question->Product->ID, $questionShell->Product->ID);
    }

    function testGetSocialObjectShell(){
        $mock = new MockedConnectObjectForPermissionBaseTest('TestObject');
        $decorated = new PermissionBaseExtension($mock);

        $incident = $decorated->callGetSocialObjectShell('Incident');
        $this->assertIsA($incident, CONNECT_NAMESPACE_PREFIX . '\Incident');

        $contact = $decorated->callGetSocialObjectShell('Contact', 'stuff', 'stuff', 'stuff');
        $this->assertIsA($contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertIdentical('stuff', $contact->CommunityQuestion);
        $this->assertIdentical('stuff', $contact->CreatedByCommunityUser);
        $this->assertIdentical('stuff', $contact->CommunityComment);

        $question = $decorated->callGetObjectShell('CommunityQuestion');
        $user = $decorated->callGetObjectShell('CommunityUser');
        $comment = $decorated->callGetObjectShell('CommunityComment');
        $contentRating = $decorated->callGetSocialObjectShell('CommunityQuestionRtg', $question, $user, $comment);
        $this->assertIsA($contentRating, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestionRtg');
        $this->assertIsA($contentRating->CommunityQuestion, CONNECT_NAMESPACE_PREFIX . '\CommunityQuestion');
        $this->assertIsA($contentRating->CreatedByCommunityUser, CONNECT_NAMESPACE_PREFIX . '\CommunityUser');
        $this->assertIsA($contentRating->CommunityComment, CONNECT_NAMESPACE_PREFIX . '\CommunityComment');
        $this->assertIdentical($question, $contentRating->CommunityQuestion);
        $this->assertIdentical($user, $contentRating->CreatedByCommunityUser);
        $this->assertIdentical($comment, $contentRating->CommunityComment);
    }
}

class PermissionBaseExtension extends RightNow\Decorators\PermissionBase {
    public $connectTypes = array(
        'TestObject',
        'CommunityQuestion',
        'RightNow\Libraries\TabularDataObject',
    );

    public function setStatus($id){
        $this->connectObj->StatusWithType->StatusType->ID = $id;
    }

    public function callStatusMethod($id){
        return parent::isStatusOf($id);
    }

    public function callGetObjectShell($className){
        return parent::getObjectShell($className);
    }

    public function callGetSocialObjectShell($className, $question = null, $user = null, $comment = null){
        return parent::getSocialObjectShell($className, $question, $user, $comment);
    }
}

class MockedConnectObjectForPermissionBaseTest {
    static $comType = '';
    function __construct($comType) {
        self::$comType = $comType;
        $this->StatusWithType = (object)array('StatusType' => (object) array('ID' => null));
    }
    static function getMetadata() {
        return (object) array(
            'COM_type' => self::$comType,
        );
    }
}
