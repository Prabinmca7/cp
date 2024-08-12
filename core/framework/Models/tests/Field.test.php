<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Framework,
    RightNow\Utils\Text;

class FieldModelTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\Field';
    static $mockIncidentModel;
    static $mockAssetModel;
    static $mockContactModel;
    static $mockQuestionModel;
    static $mockSocialUserModel;
    static $mockController;
    static $mockSocialSubcription;

    function __construct() {
        $this->CI = get_instance();

        $mocks = array(
            'Models/Contact',
            'Models/Incident',
            'Models/CommunityQuestion',
            'Models/CommunityUser',
            'Libraries/Session',
            'Controllers/Base',
            'Models/Asset',
            'Models/SocialSubscription',
        );
        foreach ($mocks as $name) {
            if (Text::stringContains($name, 'Models')) {
                require_once CPCORE . "{$name}.php";
            }
            $namespaced = "\\RightNow\\" . str_replace('/', '\\', $name);

            $temp = explode("\\", $namespaced);
            $mockClass = array_pop($temp);
            $temp []= "Mock{$mockClass}";

            $namespacedMock = implode("\\", $temp);

            if (!class_exists($namespacedMock)) {
                Mock::generate($namespaced, $namespacedMock);
            }
        }
    }
    function instance($mock = true) {
        if ($mock) {
            return new RightNow\Models\Field(self::$mockController);
        }

        return new RightNow\Models\Field();
    }

    function setUp() {
        parent::setUp();
        self::$mockController = new \RightNow\Controllers\MockBase();
        self::$mockController->input = $this->CI->input;
        self::$mockController->session = $this->CI->session;
        self::$mockContactModel = new \RightNow\Models\MockContact();
        self::$mockIncidentModel = new \RightNow\Models\MockIncident();
        self::$mockQuestionModel = new \RightNow\Models\MockCommunityQuestion();
        self::$mockSocialUserModel = new \RightNow\Models\MockCommunityUser();
        self::$mockAssetModel = new \RightNow\Models\MockAsset();
        self::$mockSocialSubcription = new \RightNow\Models\MockSocialSubscription();

        // note that tests that login a contact may need to do this themselves
        $_POST['f_tok'] = Framework::createTokenWithExpiration(0, false);
    }

    function tearDown() {
        parent::tearDown();
        unset($_POST['f_tok']);
    }

    function testSendFormInvalidToken() {
        unset($_POST['f_tok']);

        $model = $this->instance();

        $return = $model->sendForm(array(), array('i_id' => 1, 'asset_id' => null), true);
        $this->assertIsA($return, '\RightNow\Libraries\ResponseObject');
        $this->assertNull($return->result['transaction']);
        $this->assertIsA($return->result['sessionParam'], 'string');
        $this->assertIsA($return->result['redirectOverride'], 'string');

        $return = $model->sendForm(array(), array('i_id' => 1, 'asset_id' => null), true);
        $this->assertIsA($return, '\RightNow\Libraries\ResponseObject');
        $this->assertNull($return->result['transaction']);
        $this->assertIsA($return->result['sessionParam'], 'string');
        $this->assertIsA($return->result['redirectOverride'], 'string');
    }

    function testSendFormNoSupportedObjects() {
        $model = $this->instance();
        $_POST['f_tok'] = Framework::createTokenWithExpiration(0, false);
        $return = $model->sendForm(array(
            'Birch.Banana' => (object) array('value' => 'rain', 'required' => true),
            'Account.Login' => (object) array('value' => 'bananas'),
        ));
        $this->assertNull($return->result);
        $this->assertSame(1, count($return->errors));
    }
    
    function testSendFormContactUpdateWithExpiredPassword() {  
        self::$mockController->setReturnValue('model', self::$mockContactModel);
        $session = new \RightNow\Libraries\MockSession();
        $session->setReturnValue("getProfileData", 2);
        self::$mockController->session = $session;    	

        $testContact = $this->CI->model('Contact')->get(2)->result;
        $this->logIn($testContact->Login);
        $_POST['f_tok'] = Framework::createTokenWithExpiration(0, false);
        $model = $this->instance();
        $return = $model->sendForm(array(
                        (object) array('required' => true, 'name' => 'Contact.Login', 'value' => 'th@nway.xom.invalid'),
                        (object) array('name' => 'Contact.Name.First', 'value' => 'First slatest')
        ), array(), 0);
        $this->logOut();
        $this->assertNull($return->result);
        $this->assertSame(1, count($return->errors));
    }
    
    function testChangePasswordWhenExpired() {
        self::$mockContactModel->expectOnce('update', array(
            2,
            array(
                'Contact.Login' => (object) array('required' => true, 'value' => 'th@nway.xom.invalid'),
                'Contact.NewPassword' => (object) array('value' => 'banana'),
            )
        ));
        self::$mockContactModel->setReturnValue('update', (object) array('result' => (object) array('ID' => 2)));
        self::$mockController->setReturnValue('model', self::$mockContactModel);
        $session = new \RightNow\Libraries\MockSession();
        $session->setReturnValue("getProfileData", 2);
        self::$mockController->session = $session;


        $testContact = $this->CI->model('Contact')->get(2)->result;
        $this->logIn($testContact->Login);
        $_POST['f_tok'] = Framework::createTokenWithExpiration(0, false);
        $model = $this->instance();
        $return = $model->sendForm(array(
                        (object) array('required' => true, 'name' => 'Contact.Login', 'value' => 'th@nway.xom.invalid'),
                        (object) array('name' => 'Contact.NewPassword', 'value' => 'banana'),
        ), array(), 0);
        $this->logOut();
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(array('contact' => array('value' => 2)), $return->result['transaction']);
    }

    function testSendFormContactCreate() {
        self::$mockContactModel->expectOnce('create', array(
            array(
                'Contact.Login' => (object) array('value' => 'banana'),
                'Contact.Emails.PRIMARY.Address' => (object) array('value' => 'banana@banana.banana'),
                'Contact.Name.First' => (object) array('value' => 'First banana'),
                'Contact.Name.Last' => (object) array('value' => 'Last banana'),
            ),
            true
        ));
        self::$mockContactModel->setReturnValue('create', (object) array('result' => (object) array('ID' => 12)));
        self::$mockController->setReturnValue('model', self::$mockContactModel);
        self::$mockController->session = new \RightNow\Libraries\MockSession();
        self::$mockController->session->setReturnValue('canSetSessionCookies', true);
        self::$mockController->session->setReturnValue('getSessionData', true, array('cookiesEnabled'));

        $model = $this->instance();
        $return = $model->sendForm(array(
            (object) array('name' => 'Contact.Login', 'value' => 'banana'),
            (object) array('name' => 'Contact.Emails.PRIMARY.Address', 'value' => 'banana@banana.banana'),
            (object) array('name' => 'Contact.Name.First', 'value' => 'First banana'),
            (object) array('name' => 'Contact.Name.Last', 'value' => 'Last banana'),
        ), array(), 0);
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(array('contact' => array('value' => 12)), $return->result['transaction']);
    }

    function testSendFormContactUpdate() {
        self::$mockContactModel->expectOnce('update', array(
            1286,
            array(
                'Contact.Login' => (object) array('required' => true, 'value' => 'slatester'),
                'Contact.Emails.PRIMARY.Address' => (object) array('value' => 'slatest@banana.banana'),
                'Contact.Name.First' => (object) array('value' => 'First slatest'),
                'Contact.CustomFields.banana' => (object) array('value' => 'banana'),
            )
        ));
        self::$mockContactModel->setReturnValue('update', (object) array('result' => (object) array('ID' => 12)));
        self::$mockController->setReturnValue('model', self::$mockContactModel);
        $session = new \RightNow\Libraries\MockSession();
        $session->setReturnValue('getProfileData', 1286);
        self::$mockController->session = $session;

        $this->logIn();
        $_POST['f_tok'] = Framework::createTokenWithExpiration(0, false);
        $model = $this->instance();
        $return = $model->sendForm(array(
            (object) array('required' => true, 'name' => 'Contact.Login', 'value' => 'slatester'),
            (object) array('name' => 'Contact.Emails.PRIMARY.Address', 'value' => 'slatest@banana.banana'),
            (object) array('name' => 'Contact.Name.First', 'value' => 'First slatest'),
            (object) array('name' => 'Contact.CustomFields.banana', 'value' => 'banana'),
        ), array(), 0);
        $this->logOut();
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(array('contact' => array('value' => 12)), $return->result['transaction']);
    }

    function testSendFormSocialUserCreateNoOp() {
        // No-op. Contact wasn't created in the transaction and the user's not logged in.
        $model = $this->instance();
        $return = $model->sendForm(array(
            (object) array('name' => 'CommunityUser.DisplayName', 'value' => 'Banana Alec'),
        ));
        $this->assertIdentical(array(), $return->result['transaction']);
    }

    function testSendFormSocialUserCreateCreatesNewContactAndSocialUser() {
        // Contact is created in the transaction and CommunityUser along with it.
        $model = $this->instance();
        self::$mockContactModel->setReturnValue('create', (object) array('result' => (object) array('ID' => 238778)));
        self::$mockSocialUserModel->setReturnValue('create', (object) array('result' => (object) array('ID' => 'lol')));
        self::$mockController->setReturnValue('model', self::$mockContactModel, array('Contact'));
        self::$mockController->setReturnValue('model', self::$mockSocialUserModel, array('CommunityUser'));
        self::$mockController->session = new \RightNow\Libraries\MockSession();
        self::$mockController->session->setReturnValue('canSetSessionCookies', true);
        self::$mockController->session->setReturnValue('getSessionData', true, array('cookiesEnabled'));

        $return = $model->sendForm(array(
            (object) array('name' => 'CommunityUser.DisplayName', 'value' => 'Banana Alec'),
            (object) array('name' => 'Contact.Emails.PRIMARY.Address', 'value' => 'fake@banana.bananas'),
            (object) array('name' => 'Contact.Login', 'value' => 'fake@banana.bananas'),
        ));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(2, count($return->result['transaction']));
        $this->assertIdentical(array('value' => 'lol'), $return->result['transaction']['communityUser']);
    }

    function testSendFormSocialUserCreateCreatesSocialUserForExistingContact() {
        // CommunityUser is created for existing Contact.
        $model = $this->instance();
        $this->logIn();
        self::$mockContactModel->expectNever('create');
        self::$mockContactModel->expectNever('update');
        self::$mockSocialUserModel->setReturnValue('create', (object) array('result' => (object) array('ID' => 3453232)));
        self::$mockController->setReturnValue('model', self::$mockContactModel, array('Contact'));
        self::$mockController->setReturnValue('model', self::$mockSocialUserModel, array('CommunityUser'));
        self::$mockController->session = \RightNow\Libraries\Session::getInstance();
        $_POST['f_tok'] = Framework::createTokenWithExpiration(0, false);
        $return = $model->sendForm(array(
            (object) array('name' => 'Communityuser.DisplayName', 'value' => 'Lord Wyandot'),
        ));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($return->result['transaction']));
        $this->assertIdentical(array('value' => 3453232), $return->result['transaction']['communityUser']);
        $this->logOut();
    }

    function testSendFormSocialUserUpdate() {
        $model = $this->instance();
        $this->logIn();
        self::$mockContactModel->expectNever('create');
        self::$mockContactModel->expectNever('update');
        self::$mockSocialUserModel->expectNever('create');
        self::$mockSocialUserModel->setReturnValue('update', (object) array('result' => (object) array('ID' => 'ftw')));
        self::$mockSocialUserModel->setReturnValue('get', (object) array('result' => (object) array('ID' => 'ftw')));
        self::$mockController->setReturnValue('model', self::$mockContactModel, array('Contact'));
        self::$mockController->setReturnValue('model', self::$mockSocialUserModel, array('CommunityUser'));
        self::$mockController->session = \RightNow\Libraries\Session::getInstance();
        $_POST['f_tok'] = Framework::createTokenWithExpiration(0, false);
        $return = $model->sendForm(array(
            (object) array('name' => 'CommunityUser.DisplayName', 'value' => 'Lord Follow'),
        ));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($return->result['transaction']));
        $this->assertIdentical(array('value' => 'ftw'), $return->result['transaction']['communityUser']);
        $this->logOut();
    }

    function testSendFormIncidentCreateLoggedIn() {
        self::$mockContactModel->expectOnce('update', array(
            1286,
            array(
                'Incident.Subject' => (object) array('value' => 'incident subject'),
                'Incident.Thread' => (object) array('value' => 'thread'),
                'Contact.Name.First' => (object) array('value' => 'First slatest New'),
                'Contact.Name.Last' => (object) array('required' => true, 'value' => 'Last slatest New'),
            )
        ));
        self::$mockContactModel->setReturnValue('update', (object) array('return' => (object) array('ID' => 12)));
        // it's really incident.create that's called here, but let's not quibble...
        self::$mockContactModel->expectOnce('create', array(
            array(
                'Incident.Subject' => (object) array('value' => 'incident subject'),
                'Incident.Thread' => (object) array('value' => 'thread'),
                'Contact.Name.First' => (object) array('value' => 'First slatest New'),
                'Contact.Name.Last' => (object) array('required' => true, 'value' => 'Last slatest New'),
            ),
            0
        ));
        self::$mockContactModel->setReturnValue('create', (object) array('result' => (object) array('ID' => 12)));
        self::$mockController->setReturnValue('model', self::$mockContactModel);
        $session = new \RightNow\Libraries\MockSession();
        $session->setReturnValue('getProfileData', 1286);
        self::$mockController->session = $session;

        $this->logIn();
        $_POST['f_tok'] = Framework::createTokenWithExpiration(0, false);
        $model = $this->instance();
        $return = $model->sendForm(array(
            (object) array('name' => 'Incident.Subject', 'value' => 'incident subject'),
            (object) array('name' => 'Incident.Thread', 'value' => 'thread'),
            (object) array('name' => 'Contact.Name.First', 'value' => 'First slatest New'),
            (object) array('required' => true, 'name' => 'Contact.Name.Last', 'value' => 'Last slatest New'),
        ), array('i_id' => null, 'asset_id' => null), 0);
        $this->logOut();
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(array('incident' => array('key' => 'i_id', 'value' => 12)), $return->result['transaction']);
    }

    function testSendFormIncidentCreateLoggedOut() {
        self::$mockContactModel->expectCallCount('create', 2);
        self::$mockContactModel->expectAt(0, 'create', array(
            array(
                'Incident.Subject' => (object) array('value' => 'incident'),
                'Incident.Thread' => (object) array('value' => 'thread'),
                'Contact.Name.First' => (object) array('value' => 'First slatest New'),
                'Contact.Emails.PRIMARY.Address' => (object) array('value' => 'perpetualslacontactnoorg@invalid.org'),
                'Contact.Name.Last' => (object) array('value' => 'Last slatest New'),
            ),
            true
        ));
        self::$mockContactModel->setReturnValueAt(0, 'create', (object) array('result' => (object) array('ID' => 12)));
        // it's really incident.create that's called here, but let's not quibble...
        self::$mockContactModel->expectAt(1, 'create', array(
            array(
                'Incident.Subject' => (object) array('value' => 'incident'),
                'Incident.Thread' => (object) array('value' => 'thread'),
                'Contact.Name.First' => (object) array('value' => 'First slatest New'),
                'Contact.Emails.PRIMARY.Address' => (object) array('value' => 'perpetualslacontactnoorg@invalid.org'),
                'Contact.Name.Last' => (object) array('value' => 'Last slatest New'),
                'Incident.PrimaryContact' => (object) array('ID' => 12),
            ),
            false
        ));
        self::$mockContactModel->setReturnValueAt(1, 'create', (object) array('result' => (object) array('ID' => 11, 'ReferenceNumber' => 'bananas')));
        self::$mockController->setReturnValue('model', self::$mockContactModel);
        self::$mockController->session = new \RightNow\Libraries\MockSession();
        self::$mockController->session->setReturnValue('canSetSessionCookies', true);
        self::$mockController->session->setReturnValue('getSessionData', true, array('cookiesEnabled'));

        $model = $this->instance();
        $return = $model->sendForm(array(
            (object) array('name' => 'Incident.Subject', 'value' => 'incident'),
            (object) array('name' => 'Incident.Thread', 'value' => 'thread'),
            (object) array('name' => 'Contact.Name.First', 'value' => 'First slatest New'),
            (object) array('name' => 'Contact.Emails.PRIMARY.Address', 'value' => 'perpetualslacontactnoorg@invalid.org'),
            (object) array('name' => 'Contact.Name.Last', 'value' => 'Last slatest New'),
        ));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(array('contact' => array('value' => 12), 'incident' => array('key' => 'refno', 'value' => 'bananas')), $return->result['transaction']);
    }

    function testSendFormIncidentCreateEmailOnly() {
        self::$mockContactModel->expectOnce('create', array(
            array(
                'Incident.Subject' => (object) array('value' => 'incident subject'),
                'Incident.Thread' => (object) array('value' => 'thread update'),
                'Contact.Emails.PRIMARY.Address' => (object) array('value' => 'perpetualslacontactnoorg@invalid.org'),
            ),
            true
        ));
        self::$mockContactModel->setReturnValueAt(0, 'create', (object) array('result' => (object) array('ID' => 1234)));
        self::$mockContactModel->returns('lookupContactByEmail', (object) array('result' => false), array(
            'perpetualslacontactnoorg@invalid.org',
            false,
            false,
        ));
        self::$mockIncidentModel->expectOnce('create', array(
            array(
                'Incident.Subject' => (object) array('value' => 'incident subject'),
                'Incident.Thread' => (object) array('value' => 'thread update'),
                'Contact.Emails.PRIMARY.Address' => (object) array('value' => 'perpetualslacontactnoorg@invalid.org'),
                'Incident.PrimaryContact' => (object) array('ID' => 1234),
            ),
            true
        ));
        self::$mockIncidentModel->setReturnValueAt(0, 'create', (object) array('result' => (object) array('ID' => 1234567, 'ReferenceNumber' => 'bananas')));
        self::$mockController->setReturnValue('model', self::$mockIncidentModel, array('Incident'));
        self::$mockController->setReturnValue('model', self::$mockContactModel, array('Contact'));
        self::$mockController->session = new \RightNow\Libraries\MockSession();
        self::$mockController->session->setReturnValue('canSetSessionCookies', true);
        self::$mockController->session->setReturnValue('getSessionData', true, array('cookiesEnabled'));
        $model = $this->instance();
        $return = $model->sendForm(array(
            (object) array('name' => 'Incident.Subject', 'value' => 'incident subject'),
            (object) array('name' => 'Incident.Thread', 'value' => 'thread update'),
            (object) array('name' => 'Contact.Emails.PRIMARY.Address', 'value' => 'perpetualslacontactnoorg@invalid.org'),
        ), array(), true);
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(array('contact' => array('value' => 1234), 'incident' => array('key' => 'refno', 'value' => 'bananas')), $return->result['transaction']);
    }

    function testSendFormIncidentUpdate() {
        self::$mockIncidentModel->expectOnce('update', array(
            137,
            array(
                'Incident.Subject' => (object) array('value' => 'incident subject'),
                'Incident.Thread' => (object) array('value' => 'thread update'),
            )
        ));
        self::$mockIncidentModel->setReturnValue('update', (object) array('result' => (object) array('ID' => 137)));
        self::$mockController->setReturnValue('model', self::$mockIncidentModel);
        $this->logIn();
        $_POST['f_tok'] = Framework::createTokenWithExpiration(0, false);
        $model = $this->instance();
        $return = $model->sendForm(array(
            (object) array('name' => 'Incident.Subject', 'value' => 'incident subject'),
            (object) array('name' => 'Incident.Thread', 'value' => 'thread update'),
        ), array('i_id' => 137, 'asset_id' => null), 0);
        $this->logOut();
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(array('incident' => array('key' => 'i_id', 'value' => 137)), $return->result['transaction']);
    }

    function testSendFormIncidentCreateSmartAssistant() {
        self::$mockIncidentModel->expectOnce('create', array(
            array(
                'Incident.Subject' => (object) array('value' => 'incident subject'),
                'Incident.Thread' => (object) array('value' => 'thread update'),
            ),
            true
        ));
        self::$mockIncidentModel->setReturnValue('create', (object) array('result' => array('smart' => true)));
        self::$mockController->setReturnValue('model', self::$mockIncidentModel);
        $model = $this->instance();
        $return = $model->sendForm(array(
            (object) array('name' => 'Incident.Subject', 'value' => 'incident subject'),
            (object) array('name' => 'Incident.Thread', 'value' => 'thread update'),
        ), array(), true);
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result['sa'], 'array');
    }

    function testSendFormIncidentCreateSmartAssistantContactCreate() {
        self::$mockIncidentModel->expectCallCount('create', 2);
        // it's really contact.create that's called here, but let's not quibble...
        self::$mockIncidentModel->expectAt(0, 'create', array(
            array(
                'Incident.Subject' => (object) array('value' => 'incident subject'),
                'Incident.Thread' => (object) array('value' => 'thread update'),
                'Contact.Login' => (object) array('value' => 'loginName'),
            ),
            true
        ));
        self::$mockIncidentModel->expectAt(1, 'create', array(
            array(
                'Incident.Subject' => (object) array('value' => 'incident subject'),
                'Incident.Thread' => (object) array('value' => 'thread update'),
                'Contact.Login' => (object) array('value' => 'loginName'),
            ),
            true
        ));
        self::$mockIncidentModel->setReturnValueAt(0, 'create', (object) array('result' => (object) array('ID' => 1234)));
        self::$mockIncidentModel->setReturnValueAt(1, 'create', (object) array('result' => array('smart' => true)));
        self::$mockController->setReturnValue('model', self::$mockIncidentModel);
        self::$mockController->session = new \RightNow\Libraries\MockSession();
        self::$mockController->session->setReturnValue('canSetSessionCookies', true);
        self::$mockController->session->setReturnValue('getSessionData', true, array('cookiesEnabled'));
        $model = $this->instance();
        $return = $model->sendForm(array(
            (object) array('name' => 'Incident.Subject', 'value' => 'incident subject'),
            (object) array('name' => 'Incident.Thread', 'value' => 'thread update'),
            (object) array('name' => 'Contact.Login', 'value' => 'loginName'),
        ), array(), true);
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($return->result['transaction']);
        $this->assertIsA($return->result['sa'], 'array');
        $this->assertIsA($return->result['newFormToken'], 'string');
    }

    function testSendFormQuestionCreate() {
        self::$mockQuestionModel->expectOnce('create', array(
            array(
                'CommunityQuestion.Subject' => (object) array('value' => 'question title'),
                'CommunityQuestion.Body' => (object) array('value' => 'question body')
            ),
            true
        ));
        self::$mockQuestionModel->setReturnValueAt(0, 'create', (object) array('result' => (object) array('ID' => 1234567)));
        self::$mockController->setReturnValue('model', self::$mockQuestionModel, array('CommunityQuestion'));
        self::$mockController->setReturnValue('model', self::$mockSocialSubcription, array('SocialSubscription'));
        if (!class_exists('\RightNow\Libraries\MockSession')) {
            Mock::generate('\RightNow\Libraries\Session');
        }
        self::$mockController->session = new \RightNow\Libraries\MockSession;
        $model = $this->instance();
        self::$mockController->session->expect('setSessionData', array(array('previouslySeenEmail' => $email)));
        self::$mockController->session->expect('setFlashData', array('info', "Your question has been created!"));
        $return = $model->sendForm(array(
            (object) array('name' => 'CommunityQuestion.Subject', 'value' => 'question title'),
            (object) array('name' => 'CommunityQuestion.Body', 'value' => 'question body'),
        ), array(), true);
        $this->unsetMockSession();
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(array('question' => array('key' => 'qid', 'value' => 1234567)), $return->result['transaction']);
    }

    function testSendFormSubscribeToQuestionCreated() {
       self::$mockQuestionModel->expectOnce('create', array(
            array(
                'CommunityQuestion.Subject' => (object) array('value' => 'question title'),
                'CommunityQuestion.Body' => (object) array('value' => 'question body'),
                'CommunityUser.Subscribe' => (object) array('value' => '1')
            ),
            true
        ));
        self::$mockQuestionModel->setReturnValueAt(0, 'create', (object) array('result' => (object) array('ID' => 1234567)));
        self::$mockController->setReturnValue('model', self::$mockQuestionModel, array('CommunityQuestion'));
        self::$mockController->setReturnValue('model', self::$mockSocialSubcription, array('SocialSubscription'));
        self::$mockSocialSubcription->setReturnValueAt(0, 'getSubscriptionID', (object) array('result' => '1'));
        if (!class_exists('\RightNow\Libraries\MockSession')) {
            Mock::generate('\RightNow\Libraries\Session');
        }
        self::$mockController->session = new \RightNow\Libraries\MockSession;
        $model = $this->instance();
        self::$mockController->session->expect('setFlashData', array('info', "Your question has been created!<br>You will receive an email notification when someone replies."));
        $return = $model->sendForm(array(
            (object) array('name' => 'CommunityQuestion.Subject', 'value' => 'question title'),
            (object) array('name' => 'CommunityQuestion.Body', 'value' => 'question body'),
            (object) array('name' => 'CommunityUser.Subscribe', 'value' => '1'),
            ), array(), true);
        $this->unsetMockSession();
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(array('question' => array('key' => 'qid', 'value' => 1234567)), $return->result['transaction']);
    }

    function testSendFormQuestionUpdate() {
        # code...
    }

    function testSendFormQuestionFieldsAndIncidentFields() {
        # code...
    }

    function testSendFormQuestionCreateAndContactUpdate() {
        # code...
    }

    function testSendFormQuestionUpdateAndContactUpdate() {
        # code...
    }

    function testSendFormWithNoFields(){
        $model = $this->instance();
        $return = $model->sendForm(array());
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($return->errors));
        $this->assertIsA($return->error->externalMessage, 'string');
    }

    function testSendFormWithNoValidFields() {
        $model = $this->instance();
        $return = $model->sendForm(array(
            (object) array('name' => 'Answer.Summary', 'value' => 'bananas'),
        ));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($return->errors));
        $this->assertIsA($return->error->externalMessage, 'string');
    }

    function testResetPassword() {
        $model = $this->instance();
        // empty credentials
        $return = $model->resetPassword(array(), '');
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse($return->result);
        $this->assertIsA($return->error->externalMessage, 'string');
        // invalid credentials
        $return = $model->resetPassword(array(), 'asdfsadfwfasdfsadfasfsafsf');
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result, 'array');
        $this->assertIsA($return->error->externalMessage, 'string');
        // contact id + expired credentials
        $return = $model->resetPassword(array(), \RightNow\Api::ver_ske_encrypt_urlsafe("123/" . time()));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result, 'array');
        $this->assertIsA($return->error->externalMessage, 'string');
        // no expires
        $return = $model->resetPassword(array(), \RightNow\Api::ver_ske_encrypt_urlsafe("123/"));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result, 'array');
        $this->assertIsA($return->error->externalMessage, 'string');
        // contact id
        self::$mockContactModel->expectOnce('update', array(123, array(
            'Contact.NewPassword' => (object) array('value' => 'banana'),
            'ResetPasswordProcess' => (object) array('value' => true),
        )));
        self::$mockContactModel->setReturnValue('update', (object) array('result' => (object) array('ID' => 12)));
        self::$mockController->setReturnValue('model', self::$mockContactModel);

        $previousSession = self::$mockController->session;
        self::$mockController->session = new \RightNow\Libraries\MockSession();

        $model = $this->instance();
        $_POST['f_tok'] = Framework::createTokenWithExpiration(0, false);
        $return = $model->resetPassword(array(
            (object) array('name' => 'Contact.NewPassword', 'value' => 'banana')
        ), \RightNow\Api::ver_ske_encrypt_urlsafe("123/" . (time() + 10)));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(array('contact' => array('value' => 12)), $return->result['transaction']);
        $this->assertIsA($return->result['sessionParam'], 'string');
        
        self::$mockController->session = new \RightNow\Libraries\MockSession();
        $model = $this->instance();
        $_POST['f_tok'] = "randomInvalidToken";
        $return = $model->resetPassword(array(
            (object) array('name' => 'Contact.NewPassword', 'value' => 'banana')
        ), \RightNow\Api::ver_ske_encrypt_urlsafe("123/" . (time() + 10)));
        $this->assertTrue(sizeof($return->errors) > 0);
        $this->assertIdentical("/app/error/error_id/5", $return->result['redirectOverride']);
        $this->assertIdentical("The form submission token either did not match or has expired. You will need to refresh this page, possibly log back in, and fill out the form again to successfully submit.", $return->errors[0]->externalMessage);
        
        self::$mockController->session = $previousSession;
    }

    function testProcessFields(){
        list(
            $class,
            $method
        ) = $this->reflect('method:processFields');

        // Base case
        $instance = $class->newInstanceArgs(array($CI));
        $presentFields = null;

        $args = array(array(), &$presentFields);
        $response = $method->invokeArgs($instance, $args);
        $this->assertIdentical(array(), $response);
        $this->assertNull($presentFields);

        $presentFields = null;
        $args = array(array(
            (object)array('name' => 'Contact.Foo',
                          'value' => 'test')
            ), &$presentFields);
        $response = $method->invokeArgs($instance, $args);
        $this->assertIdentical(array('Contact.Foo' => (object)array('value' => 'test')), $response);
        $this->assertIdentical(array('contact' => true), $presentFields);

        $presentFields = null;
        $args = array(array(
            (object)array('name' => 'Incident.Foo',
                          'value' => 'test')
            ), &$presentFields);
        $response = $method->invokeArgs($instance, $args);
        $this->assertIdentical(array('Incident.Foo' => (object)array('value' => 'test')), $response);
        $this->assertIdentical(array('incident' => true), $presentFields);

        $presentFields = null;
        $args = array(array(
            (object)array('name' => 'Contact.Foo',
                          'value' => 'test'),
            (object)array('name' => 'Incident.Foo',
                          'value' => 'test')
            ), &$presentFields);
        $response = $method->invokeArgs($instance, $args);
        $this->assertIdentical(array('Contact.Foo' => (object)array('value' => 'test'), 'Incident.Foo' => (object)array('value' => 'test')), $response);
        $this->assertIdentical(array('contact' => true, 'incident' => true), $presentFields);

        $presentFields = null;
        $args = array(array(
            (object)array('name' => 'SomethingElse.Foo',
                          'value' => array(1,2,3,4)),
            (object)array('name' => 'NonExistingTable.Foo',
                          'value' => false)
            ), &$presentFields);
        $response = $method->invokeArgs($instance, $args);
        $this->assertIdentical(array('SomethingElse.Foo' => (object)array('value' => array(1,2,3,4)), 'NonExistingTable.Foo' => (object)array('value' => false)), $response);
        $this->assertIdentical(array('somethingelse' => true, 'nonexistingtable' => true), $presentFields);

        $presentFields = null;
        $args = array(array(
            (object)array('name' => 'Asset.Name',
                          'value' => 'Asset Name')
            ), &$presentFields);
        $response = $method->invokeArgs($instance, $args);
        $this->assertIdentical(array('Asset.Name' => (object)array('value' => 'Asset Name')), $response);
        $this->assertIdentical(array('asset' => true), $presentFields);
    }

    function testGetStatus() {
        $method = $this->getMethod('getStatus');
        $return = $method(array());
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result['sessionParam'], 'string');
        $this->assertNull($return->result['status']);

        $return = $method(array('contact' => array('there was an error')));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result['sessionParam'], 'string');
        $this->assertSame(1, count($return->errors));
        $this->assertSame('there was an error', $return->errors[0]->externalMessage);

        $return = $method(array('incident' => array('error')));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result['sessionParam'], 'string');
        $this->assertSame(1, count($return->errors));
        $this->assertSame('error', $return->errors[0]->externalMessage);

        $return = $method(array('contact' => array('error'), 'incident' => (object) array('ID' => 23)));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result['sessionParam'], 'string');
        $this->assertNull($return->result['status']);
        $this->assertSame(1, count($return->errors));
        $this->assertSame('error', $return->errors[0]->externalMessage);

        $return = $method(array('incident' => array('error'), 'contact' => (object) array('ID' => 44)));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result['sessionParam'], 'string');
        $this->assertNull($return->result['status']);
        $this->assertSame(1, count($return->errors));
        $this->assertSame('error', $return->errors[0]->externalMessage);

        $return = $method(array('contact' => (object) array('ID' => 23)));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result['sessionParam'], 'string');
        $this->assertSame(0, count($return->errors));
        $this->assertIdentical(array('contact' => array('value' => 23)), $return->result['transaction']);

        $this->setMockSession();
        $this->CI->session->expect('setFlashData', array('info', "Your question has been updated!"));
        $return = $method(array('question' => (object) array('ID' => 57)));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->errors));
        $this->assertIdentical(array('question' => array('key' => 'qid', 'value' => 57)), $return->result['transaction']);
        $this->unsetMockSession();

        $return = $method(array('question' => array('error'), 'contact' => (object) array('ID' => 44)));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result['sessionParam'], 'string');
        $this->assertNull($return->result['status']);
        $this->assertSame(1, count($return->errors));
        $this->assertSame('error', $return->errors[0]->externalMessage);

        $return = $method(array('incident' => (object) array('ID' => 23, 'ReferenceNumber' => 'bananas')));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result['sessionParam'], 'string');
        $this->assertSame(0, count($return->errors));
        $this->assertIdentical(array('incident' => array('key' => 'refno', 'value' => 'bananas')), $return->result['transaction']);
        $this->assertSame('bananas', $this->CI->session->getFlashData('newlySubmittedIncidentRefNum'));

        $return = $method(array('contact' => (object) array('ID' => 23), 'incident' => (object) array('ID' => 34, 'ReferenceNumber' => 'bananas')));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result['sessionParam'], 'string');
        $this->assertSame(0, count($return->errors));
        $this->assertIdentical(array('contact' => array('value' => 23), 'incident' => array('key' => 'refno', 'value' => 'bananas')), $return->result['transaction']);

        $return = $method(array('contact' => (object) array('ID' => 23), 'communityUser' => (object) array('ID' => 34)));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result['sessionParam'], 'string');
        $this->assertSame(0, count($return->errors));
        $this->assertIdentical(array('contact' => array('value' => 23), 'communityUser' => array('value' => 34)), $return->result['transaction']);

        $return = $method(array('asset' => (object) array('ID' => 5)));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result['sessionParam'], 'string');
        $this->assertSame(0, count($return->errors));
        $this->assertIdentical(array('asset' => array('value' => 5)), $return->result['transaction']);

        $return = $method(array('asset' => array('error')));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($return->result['sessionParam'], 'string');
        $this->assertSame(1, count($return->errors));
        $this->assertSame('error', $return->errors[0]->externalMessage);
    }

    function testVerifySupportedFieldsArePresent() {
        $method = $this->getMethod('verifySupportedFieldsArePresent');
        $this->assertFalse($method(array()));
        $this->assertFalse($method(array('Birch' => true)));
        $this->assertTrue($method(array('Contact' => true)));
        $this->assertTrue($method(array('Birch' => true, 'Contact' => true)));
    }

    function testGetStatusWithCookiesDisabled(){
        //Setup mock session stuff in order to get cookie methods to return false
        $session = new \RightNow\Libraries\MockSession();
        $session->setReturnValue('canSetSessionCookies', false);
        $session->setReturnValue('getSessionData', false);
        self::$mockController->session = $session;

        $model = $this->instance();
        $method = new \ReflectionMethod($model, 'getStatus');
        $method->setAccessible(true);

        $cpLoginStart = $_COOKIE['cp_login_start'];
        unset($_COOKIE['cp_login_start']);

        //Sending in contact with login, should produce a redirectOverride
        $result = $method->invoke($model, array('contact' => (object) array('ID' => 23, 'Login' => 'testLogin'), 'contactCreated' => true ))->result;
        $this->assertIdentical($result['transaction'], array('contact' => array('value' => 23)));
        $this->assertIdentical($result['redirectOverride'], '/app/error/error_id/7');

        $result = $method->invoke($model, array('contact' => (object) array('ID' => 23, 'Login' => '0'), 'contactCreated' => true ))->result;
        $this->assertIdentical($result['redirectOverride'], '/app/error/error_id/7');
        $result = $method->invoke($model, array('contact' => (object) array('ID' => 23, 'Login' => false), 'contactCreated' => true ))->result;
        $this->assertIdentical($result['redirectOverride'], '/app/error/error_id/7');

        $result = $method->invoke($model, array('contact' => (object) array('ID' => 23), 'contactCreated' => true ))->result;
        $this->assertNull($result['redirectOverride']);
        $result = $method->invoke($model, array('contact' => (object) array('ID' => 23, 'Login' => null), 'contactCreated' => true ))->result;
        $this->assertNull($result['redirectOverride']);

        if ($cpLoginStart)
            $_COOKIE['cp_login_start'] = $cpLoginStart;
    }

    //@@@ QA 140225-000075 -- CP3: Error thrown on update from questions/detail when Contact field included on page via input/formInput widget
    function testSendFormIncidentContactUpdate() {
        self::$mockContactModel->expectOnce('update', array(
            1286,
            array(
                'Incident.Subject' => (object) array('value' => 'incident subject'),
                'Incident.Thread' => (object) array('value' => 'thread update'),
                'Contact.Name.First' => (object) array('value' => 'Joeup2'),
                )
            ));
        self::$mockContactModel->setReturnValue('update', (object) array('result' => (object) array('ID' => 12)));
        self::$mockController->setReturnValue('model', self::$mockContactModel);
        $session = new \RightNow\Libraries\MockSession();
        $session->setReturnValue('getProfileData', 1286);
        self::$mockController->session = $session;

        $this->logIn();
        $_POST['f_tok'] = Framework::createTokenWithExpiration(0, false);
        $model = $this->instance();
        $return = $model->sendForm(array(
            (object) array('name' => 'Incident.Subject', 'value' => 'incident subject'),
            (object) array('name' => 'Incident.Thread', 'value' => 'thread update'),
            (object) array('name' => 'Contact.Name.First', 'value' => 'Joeup2'),
            ), array(), 0);
        $this->logOut();
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(array('contact' => array('value' => 12)), $return->result['transaction']);
    }

    function testSendFormSocialUserFromPublicProfilePage() {
        // Contact is created in the transaction and CommunityUser along with it.
        $model = $this->instance();
        self::$mockContactModel->setReturnValue('create', (object) array('result' => (object) array('ID' => 238778)));
        self::$mockSocialUserModel->setReturnValue('create', (object) array('result' => (object) array('ID' => 'lol')));
        self::$mockController->setReturnValue('model', self::$mockContactModel, array('Contact'));
        self::$mockController->setReturnValue('model', self::$mockSocialUserModel, array('CommunityUser'));
        self::$mockController->session = new \RightNow\Libraries\MockSession();
        self::$mockController->session->setReturnValue('canSetSessionCookies', true);
        self::$mockController->session->setReturnValue('getSessionData', true, array('cookiesEnabled'));
        $return = $model->sendForm(array(
            (object) array('name' => 'CommunityUser.DisplayName', 'value' => 'Banana Alec'),
            (object) array('name' => 'Contact.Emails.PRIMARY.Address', 'value' => 'fake@banana.bananas'),
            (object) array('name' => 'Contact.Login', 'value' => 'fake@banana.bananas'),
        ), array('user_id' => 11287));
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(2, count($return->result['transaction']));
        $this->assertIdentical(array('value' => 'lol'), $return->result['transaction']['communityUser']);
    }
    
    function testGetRawFormFields(){
        $_POST["form"] = "testValue";
        // for tests post data is empty thus any change in post afterwards should not reflect in raw form fields 
        $this->assertNull($this->instance()->getRawFormFields());
    }
}
