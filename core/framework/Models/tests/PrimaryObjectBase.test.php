<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Text;

class FakeModelInstance extends \RightNow\Models\PrimaryObjectBase{
    function __construct($type){
        parent::__construct($type);
    }

    public function getBlank(){
        return parent::getBlank();
    }

    public function get($id){
        return parent::get($id);
    }

    public function create(Connect\RNObject $connectObject, $source, $shouldRunPreCreateHook = true){
        return parent::createObject($connectObject, $source, $shouldRunPreCreateHook);
    }

    public function update(Connect\RNObject $connectObject, $source, $shouldRunPreCreateHook = true){
        return parent::updateObject($connectObject, $source, $shouldRunPreCreateHook);
    }

    public function setFieldValue(Connect\RNObject $connectObject, $fieldName, $fieldValue, $fieldType = null){
        return parent::setFieldValue($connectObject, $fieldName, $fieldValue, $fieldType);
    }

    public function getContact($contactID = null){
        return parent::getContact($contactID);
    }

    public function createAttachmentEntry(Connect\RNObject $connectObject, $value){
        return parent::createAttachmentEntry($connectObject, $value);
    }

    public function callGetSaveErrors($exception){
        return $this->getSaveErrors($exception);
    }

    public function getExtendedSmartAssistantResults ($subject, $body) {
        return parent::getExtendedSmartAssistantResults($subject, $body);
    }
}

class PrimaryObjectBaseTest extends CPTestCase {
    private $instance = null;
    private $cacheTests;

    function __construct() {
        parent::__construct();
        $this->CI->model('Contact');
    }

    function testConstructorAndGetBlank(){
        $model = new FakeModelInstance('Incident');
        $this->assertIsA($model->getBlank(), CONNECT_NAMESPACE_PREFIX . '\Incident');

        $model = new FakeModelInstance('INCIDENT');
        $this->assertIsA($model->getBlank(), CONNECT_NAMESPACE_PREFIX . '\Incident');

        $model = new FakeModelInstance('iNcIDent');
        $this->assertIsA($model->getBlank(), CONNECT_NAMESPACE_PREFIX . '\Incident');

        $model = new FakeModelInstance('contact');
        $this->assertIsA($model->getBlank(), CONNECT_NAMESPACE_PREFIX . '\Contact');

        $model = new FakeModelInstance('Country');
        $this->assertIsA($model->getBlank(), CONNECT_NAMESPACE_PREFIX . '\Country');

        $model = new FakeModelInstance('');
        // No exception thrown.
    }

    function testGet(){
        $model = new FakeModelInstance('Incident');
        $this->assertTrue(is_string($model->get('asdf')));
        $this->assertTrue(is_string($model->get(0)));
        $this->assertTrue(is_string($model->get(null)));

        $incident = $model->get(1);
        $this->assertIsA($incident, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $this->assertIdentical(1, $incident->ID);

        $incident = $model->get(2);
        $this->assertIsA($incident, CONNECT_NAMESPACE_PREFIX . '\Incident');
        $this->assertIdentical(2, $incident->ID);
    }

    function testCreate(){
        $model = new FakeModelInstance('Incident');

        $newIncident = $model->getBlank();
        $newIncident->Subject = 'new incident';
        $this->assertNull($newIncident->ID);

        try{
            $newIncident = $model->create($newIncident, SRC2_EU_AAQ);
            $this->fail($newIncident);
        }
        catch(\Exception $e){}

        $newIncident->PrimaryContact = $this->CI->model('Contact')->get(1)->result;
        $newIncident = $model->create($newIncident, SRC2_EU_AAQ);
        $this->assertTrue(is_int($newIncident->ID));
    }

    function testAbuseCreate(){
        $model = new FakeModelInstance('Incident');
        $newIncident = $model->getBlank();
        $newIncident->Subject = 'new incident';
        $newIncident->PrimaryContact = $this->CI->model('Contact')->get(1)->result;
        $this->setIsAbuse();
        $expected = \RightNow\Utils\Config::getMessage(REQUEST_PERFORMED_SITE_ABUSIVE_MSG);
        $this->assertEqual($expected, $model->create($newIncident, SRC2_EU_AAQ));
        $this->clearIsAbuse();
    }

    function testUpdate(){
        $model = new FakeModelInstance('Contact');

        $contact = $model->get(1);
        $contact->Title = 'Mr';

        $contact = $model->update($contact, SRC2_EU_CONTACT_EDIT);
        $this->assertIdentical('Mr', $contact->Title);
    }

    function testAbuseUpdate(){
        $model = new FakeModelInstance('Contact');
        $contact = $model->get(1);
        $contact->Title = 'Mr';
        $this->setIsAbuse();
        $expected = \RightNow\Utils\Config::getMessage(REQUEST_PERFORMED_SITE_ABUSIVE_MSG);
        $this->assertEqual($expected, $model->update($contact, SRC2_EU_CONTACT_EDIT));
        $this->clearIsAbuse();
    }

    function testGetContact(){
        $model = new FakeModelInstance('Contact');
        $this->assertNull($model->getContact());

        $this->logIn();
        $contact = $model->getContact();
        $this->assertIsA($contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertIdentical('perpetualslacontactnoorg@invalid.com', $contact->Emails[0]->Address);

        $contact = $model->getContact(1268);
        $this->assertIdentical('eturner@rightnow.com.invalid', $contact->Emails[0]->Address);
        $this->assertIdentical(1268, $contact->ID);

        $this->logOut();

        $contact = $model->getContact(1268);
        $this->assertIdentical('eturner@rightnow.com.invalid', $contact->Emails[0]->Address);
        $this->assertIdentical(1268, $contact->ID);
    }

    function testSetFieldValue(){
        $model = new FakeModelInstance('Contact');

        $blankContact = $model->getBlank();

        $this->assertNull($model->setFieldValue($blankContact, 'Contact.Login', 'test'));
        $this->assertIdentical('test', $blankContact->Login);

        $this->assertNull($model->setFieldValue($blankContact, 'Contact.Emails.PRIMARY.Address', 'test@examle.com'));
        $this->assertIdentical('test@examle.com', $blankContact->Emails[0]->Address);

        $model = new FakeModelInstance('Incident');
        $this->assertTrue(is_string($model->setFieldValue($model->getBlank(), 'Contact.Login', 'foo')));
    }

    //@@@ QA 130320-000068
    function testGetSaveErrors(){
        $logPath = \RightNow\Api::cfg_path() . '/log';
        umask(0);
        file_put_contents("$logPath/tr.cphp", 'ALL TIME');

        $tests = array(
            array('runGetSaveErrorsGeneric',
                'Connect exception in Incident model: code: 1, message: The Exception',
                'Connect (previous) exception in Incident model: code: 2, message: The previous Exception',
                'Connect (previous) exception in Incident model: code: 3, message: The previous Exception #2'),
            array('runGetSaveErrorsContactPassword',
                'Connect exception in Contact model: code: 4, message: The Contact.NewPassword Exception',
                'Connect (previous) exception in Contact model: code: ' . PW_ERR_PREV_MATCH . ', message: The previous Exception',
                'Connect (previous) exception in Contact model: code: 6, message: The previous Exception #2'),
        );

        foreach ($tests as $test) {
            list($testFunction, $expectedCurrentMessage, $expectedPreviousMessage, $expectedSecondPreviousMessage) = $test;

            $loggedCurrent = $loggedPrevious = $loggedSecondPrevious = false;

            $result = $this->makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/$testFunction");
            $this->assertIdentical('', $result);

            foreach(glob("$logPath/cphp*.tr") as $logFile) {
                if (Text::stringContains(file_get_contents($logFile), $expectedCurrentMessage)) {
                    $loggedCurrent = true;
                }
                if (Text::stringContains(file_get_contents($logFile), $expectedPreviousMessage)) {
                    $loggedPrevious = true;
                }
                if (Text::stringContains(file_get_contents($logFile), $expectedSecondPreviousMessage)) {
                    $loggedSecondPrevious = true;
                }
                unlink($logFile);
            }

            $this->assertTrue($loggedCurrent, "Did not find expected current message ($expectedCurrentMessage) in phpoutlog");
            $this->assertTrue($loggedPrevious, "Did not find expected previous message ($expectedPreviousMessage) in phpoutlog");
            $this->assertTrue($loggedSecondPrevious, "Did not find expected second previous message ($expectedSecondPreviousMessage) in phpoutlog");
        }

        unlink("$logPath/tr.cphp");
    }

    function runGetSaveErrorsGeneric() {
        $exception = new \Exception('The previous Exception #2', 3);
        $exception = new \Exception('The previous Exception', 2, $exception);
        $exception = new \Exception('The Exception', 1, $exception);
        $model = new FakeModelInstance('Incident');
        $response = $model->callGetSaveErrors($exception);

        $this->assertIsA($response, 'array');
        $this->assertIdentical(1, count($response));
        $this->assertIsA($response[0], '\RightNow\Libraries\ResponseError');
        $this->assertIdentical(\RightNow\Utils\Config::getMessage(SORRY_ERROR_SUBMISSION_LBL), $response[0]->externalMessage);
        $this->assertIdentical(1, $response[0]->errorCode);
        $this->assertIdentical('Incident', $response[0]->source);
        $this->assertIdentical('The Exception', $response[0]->internalMessage);
        $this->assertIsA($response[0]->extraDetails, '\Exception');
    }

    function runGetSaveErrorsContactPassword() {
        $exception = new \Exception('The previous Exception #2', 6);
        $exception = new \Exception('The previous Exception', PW_ERR_PREV_MATCH, $exception);
        $exception = new \Exception('The Contact.NewPassword Exception', 4, $exception);
        $model = new FakeModelInstance('Contact');
        $response = $model->callGetSaveErrors($exception);

        $this->assertIsA($response, 'array');
        $this->assertIdentical(2, count($response));

        $this->assertIsA($response[0], '\RightNow\Libraries\ResponseError');
        $this->assertIdentical(\RightNow\Utils\Config::getMessage(PASSWD_MATCHES_PREV_PASSWD_CONT_LBL), $response[0]->externalMessage);
        $this->assertIdentical(PW_ERR_PREV_MATCH, $response[0]->errorCode);
        $this->assertIdentical('Contact', $response[0]->source);
        $this->assertIdentical('The previous Exception', $response[0]->internalMessage);
        $this->assertIsA($response[0]->extraDetails, '\Exception');

        $this->assertIsA($response[1], '\RightNow\Libraries\ResponseError');
        $this->assertIdentical('The previous Exception #2', $response[1]->externalMessage);
        $this->assertIdentical(6, $response[1]->errorCode);
        $this->assertIdentical('Contact', $response[1]->source);
        $this->assertIdentical('The previous Exception #2', $response[1]->internalMessage);
        $this->assertIsA($response[1]->extraDetails, '\Exception');
    }

    /* @@@ QA 130711-000070 */
    function testCreateAttachmentEntry() {
        // create a temp file since model method checks it's existence
        $localName = tempnam(get_cfg_var('upload_tmp_dir'), 'createAttachmentEntry-');
        file_put_contents($localName, "1");
        $model = new FakeModelInstance('Incident');

        $invalidChars = array("\r", "\n", "/", ":", "*", "?", '"', "<", ">", "|");
        $numberOfInvalidChars = count($invalidChars);

        $filenameToAttachment = function($filename) use ($localName) {
            return (object) array(
                'localName'     => basename($localName),
                'userName'      => $filename,
                'contentType'   => 'text/plain',
            );
        };

        // test with individual sets of random invalid characters
        $filenamesToTest = array();
        for ($i = 0; $i < 100; ++$i) {
            $filenamesToTest[] = sprintf("asdf%sasdf.txt",
                $invalidChars[mt_rand(0, $numberOfInvalidChars - 1)]);

            $filenamesToTest[] = sprintf("asdf%s%sasdf.txt",
                $invalidChars[mt_rand(0, $numberOfInvalidChars - 1)],
                $invalidChars[mt_rand(0, $numberOfInvalidChars - 1)]);
        }

        try {
            $incident = $model->getBlank();
            $attachments = array_map($filenameToAttachment, $filenamesToTest);
            $model->createAttachmentEntry($incident, $attachments);

            // iterate over every file and ensure filename was changed to asdf_asdf.txt
            $count = 0;
            foreach ($incident->FileAttachments as $attachment) {
                $this->assertSame('asdf_asdf.txt', $attachment->FileName, "Original filename: {$filenamesToTest[$count]}, modified filename: {$attachment->FileName}, expected: asdf_asdf.txt");
                ++$count;
            }
        }
        catch (\Exception $e) {
            $this->fail($e->getMessage);
        }

        // test with multiple sets of random invalid characters
        $filenamesToTest = array();
        for ($i = 0; $i < 100; ++$i) {
            $filenamesToTest[] = sprintf("asdf%sasdf%sasdf.txt",
                $invalidChars[mt_rand(0, $numberOfInvalidChars - 1)],
                $invalidChars[mt_rand(0, $numberOfInvalidChars - 1)],
                $invalidChars[mt_rand(0, $numberOfInvalidChars - 1)]);

            $filenamesToTest[] = sprintf("asdf%s%sasdf%sasdf.txt",
                $invalidChars[mt_rand(0, $numberOfInvalidChars - 1)],
                $invalidChars[mt_rand(0, $numberOfInvalidChars - 1)],
                $invalidChars[mt_rand(0, $numberOfInvalidChars - 1)]);

            $filenamesToTest[] = sprintf("asdf%s%sasdf%s%sasdf.txt",
                $invalidChars[mt_rand(0, $numberOfInvalidChars - 1)],
                $invalidChars[mt_rand(0, $numberOfInvalidChars - 1)],
                $invalidChars[mt_rand(0, $numberOfInvalidChars - 1)],
                $invalidChars[mt_rand(0, $numberOfInvalidChars - 1)]);
        }


        try {
            $incident = $model->getBlank();
            $attachments = array_map($filenameToAttachment, $filenamesToTest);
            $model->createAttachmentEntry($incident, $attachments);

            // iterate over every file and ensure filename was changed to asdf_asdf_asdf.txt
            $count = 0;
            foreach ($incident->FileAttachments as $attachment) {
                $this->assertSame('asdf_asdf_asdf.txt', $attachment->FileName, "Original filename: {$filenamesToTest[$count]}, modified filename: {$attachment->FileName}, expected: asdf_asdf_asdf.txt");
                ++$count;
            }
        }
        catch (\Exception $e) {
            $this->fail($e->getMessage);
        }

        // cleanup temp file
        unlink($localName);
        
        // For social
        $localName = tempnam(get_cfg_var('upload_tmp_dir'), 'createAttachmentEntry-');
        file_put_contents($localName, "1");
        $model = new FakeModelInstance('communityquestion');
        $commonFileName = 'asdf_asdf.txt';
        $filenameToAttachment = function($ID) use ($localName, $commonFileName) {
            return (object) array(
                'localName'     => basename($localName),
                'userName'      => $ID . "_" . $commonFileName,
                'contentType'   => 'text/plain',
            );
        };

        $filenamesToTest = array(1, 2, 3, 4, 5);

        try {
            $CommunityQuestion = $model->getBlank();
            $SocialQuestionConnectObject = new Connect\RNObject();
            $SocialQuestionConnectObject->ID = 22;
            $createAttachments = array_map($filenameToAttachment, array(1, 2, 3, 4, 5));
            $attachments = array();
            $attachments['newFiles'] = $createAttachments;
            $attachments['removedFiles'] = array(32);
            $model->createAttachmentEntry($SocialQuestionConnectObject, $attachments);

            // iterate over every file and check all new files added not returned old files
            $count = 1;
            foreach ($SocialQuestionConnectObject->FileAttachments as $attachment) {
                $this->assertSame($count . '_asdf_asdf.txt', $attachment->FileName, "expected filename: {$count}_asdf_asdf.txt");
                $count++;
            }
        }
        catch (\Exception $e) {
            $this->fail($e->getMessage);
        }
        // cleanup temp file
        unlink($localName);
    }

    function testGetSAResults() {
        $originalConfigValue = \Rnow::updateConfig('KFAPI_SSS_ENABLED', true);
        $response = $this->makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/getSAResults');
        \Rnow::updateConfig('KFAPI_SSS_ENABLED', $originalConfigValue);
        $this->assertIdentical('', $response);
    }

    function getSAResults () {
        $model = new FakeModelInstance('Incident');
        $this->logIn();
        $smartAssistantResults = $model->getExtendedSmartAssistantResults("Iphone is not working", "How do i get my iphone to work");
        $this->assertIsA($smartAssistantResults, 'array');
        $this->assertIsA($smartAssistantResults['suggestions'], 'array');
        $suggestions = $smartAssistantResults['suggestions'];
        $this->assertIdentical($suggestions[0]['type'], 'AnswerSummary');
        $this->assertIdentical($suggestions[1]['type'], 'QuestionSummary');
        $this->assertIsA($suggestions[1]['list'], 'array');
        $this->logOut();
    }

    function testGetSAResultsException () {
        $result = $this->makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/getSAResultsException");
        $this->assertIdentical($result, 'Not Allowed: Cannot be set to Nil/NULL; SmartAssistantSearch.SecurityOptions.Contact');
    }

    function getSAResultsException () {
        $model = new FakeModelInstance('Incident');
        $model->getExtendedSmartAssistantResults("Iphone is not working", "How do i get my iphone to work");
    }
}