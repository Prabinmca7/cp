<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Connect\v1_4 as Connect,
    RightNow\UnitTest\Fixture;

class TestFileListDisplay extends WidgetTestCase {
    public $testingWidget = "standard/output/FileListDisplay";

    function __construct() {
        parent::__construct();

        // Set up file attachments
        foreach(array('/winning', '/warlock', '/bi-winning', '/goldenSombrero') as $file) {
            file_put_contents(\RightNow\Api::fattach_full_path($file), 'test data');
        }

        $idFromLogin = $this->CI->model('Contact')->getIDFromLogin('slatest')->result;
        $contact = $this->CI->model('Contact')->get($idFromLogin)->result;

        $this->incident = $this->CI->model('Incident')->create(array(
            'Incident.PrimaryContact' => $contact,
            'Incident.Subject' => (object) array('value' => 'incident subject'),
            'Incident.Thread' => (object) array('value' => 'thread'),
            'Incident.FileAttachments' => (object) array('value' => array(
                (object) array(
                    'localName' => 'winning',
                    'contentType' => 'application/msword',
                    'userName' => 'winning.docx'
                ),
                (object) array(
                    'localName' => 'warlock',
                    'contentType' => 'image/jpeg',
                    'userName' => 'warlock.jpg'
                ),
                (object) array(
                    'localName' => 'bi-winning',
                    'contentType' => 'image/png',
                    'userName' => 'biWinning.png'
                ), (object) array(
                    'localName' => 'goldenSombrero',
                    'contentType' => 'image/gif',
                    'userName' => 'goldenSombrero.gif'
                )
            )
        )))->result;
    }

    function testGetData() {
        $this->addUrlParameters(array('i_id' => $this->incident->ID));
        $this->logIn();

        // Ensure file attachment objects look ok.
        $this->createWidgetInstance(array('name' => 'Incident.FileAttachments'));
        $data = $this->getWidgetData();
        foreach($data['attachments'] as $attachment) {
            $this->assertNotNull($attachment->ContentType);
            $this->assertNotNull($attachment->CreatedTime);
            $this->assertNotNull($attachment->FileName);
            $this->assertNotNull($attachment->Icon);
            $this->assertNotNull($attachment->ReadableSize);
            $this->assertNotNull($attachment->AttachmentUrl);
        }

        // Ensure file names are ok
        $this->assertIdentical($data['attachments'][0]->FileName, 'winning.docx');
        $this->assertIdentical($data['attachments'][1]->FileName, 'warlock.jpg');
        $this->assertIdentical($data['attachments'][2]->FileName, 'biWinning.png');
        $this->assertIdentical($data['attachments'][3]->FileName, 'goldenSombrero.gif');

        // Assert images that should have thumbnails, have thumbnails. And vice versa.
        $this->createWidgetInstance(array('name' => 'Incident.FileAttachments', 'display_thumbnail' => true));
        $data = $this->getWidgetData();
        foreach($data['attachments'] as $attachment) {
            if(\RightNow\Utils\Text::beginsWith($attachment->ContentType, 'image')) {
                $this->assertNotNull($attachment->ThumbnailUrl);
                $this->assertNotNull($attachment->ThumbnailScreenReaderText);
            }
            else {
                $this->assertNull($attachment->ThumbnailUrl);
                $this->assertNull($attachment->ThumbnailScreenReaderText);
            }
        }

        // Assert images do not have thumbnails when the attribute is not set to true.
        $this->setWidgetAttributes(array('display_thumbnail' => null));
        $data = $this->getWidgetData();
        foreach($data['attachments'] as $attachment) {
            $this->assertNull($attachment->ThumbnailUrl);
            $this->assertNull($attachment->ThumbnailScreenReaderText);
        }

        $this->restoreUrlParameters();
        $this->logOut();
    }

    function testGetDataReturnsFalseWhenAllAttachmentsArePrivate() {
        $this->addUrlParameters(array('i_id' => $this->incident->ID));
        $this->logIn();

        $attachments = $this->incident->FileAttachments;
        $togglePrivacy = function($private = false) use ($attachments) {
            foreach($attachments as $attachment) {
                $attachment->Private = $private;
            }
        };

        $togglePrivacy(true);
        $widget = $this->createWidgetInstance(array('name' => 'Incident.FileAttachments'));
        $this->assertFalse($widget->getData());
        $togglePrivacy();
        $this->restoreUrlParameters();
        $this->logOut();
    }

    function testSiblingAnswerAttachmentsNotVisible() {
        $answer=Connect\Answer::fetch(2);
        $answer->FileAttachments =new Connect\FileAttachmentAnswerArray();
        $fattach = new Connect\FileAttachmentAnswer();
        $fattach->ContentType = "text/text";
        $fp = $fattach->makeFile();
        fwrite($fp,"Making some notes in this text file for the answer".date("Y-m-d h:i:s"));
        fclose($fp);
        $fattach->FileName = "AnswerFile.txt";
        $fattach->Names=new Connect\LabelRequiredArray();
        $fattach->Names[0]=new Connect\LabelRequired();
        $fattach->Names[0]->LabelText="Canada.txt";
        $fattach->Names[0]->Language= new Connect\NamedIDOptList();
        $fattach->Names[0]->Language->ID=1;
        $answer->FileAttachments[] = $fattach;
        // attaching sibling.
        $answer->CommonAttachments =new Connect\FileAttachmentSharedArray();
        $fattach = new Connect\FileAttachmentShared();
        $fattach->ContentType = "text/text";
        $fp = $fattach->makeFile();
        fwrite($fp,"Making some notes in this text file for the answer".date("Y-m-d h:i:s"));
        fclose($fp);
        $fattach->FileName = "SiblingFile.txt";
        $fattach->Names=new Connect\LabelRequiredArray();
        $fattach->Names[0]=new Connect\LabelRequired();
        $fattach->Names[0]->LabelText="Canada.txt";
        $fattach->Names[0]->Language= new Connect\NamedIDOptList();
        $fattach->Names[0]->Language->ID=1;
        $answer->CommonAttachments[] = $fattach;
        $answer->save();

        $this->addUrlParameters(array('a_id' => $answer->ID));
        $this->createWidgetInstance(array('name' => 'Answer.FileAttachments'));
        $data = $this->getWidgetData();
        foreach($data['attachments'] as $attachment) {
            $this->assertNotNull($attachment->ContentType);
            $this->assertNotNull($attachment->CreatedTime);
            $this->assertNotNull($attachment->FileName);
            $this->assertNotNull($attachment->Icon);
            $this->assertNotNull($attachment->ReadableSize);
            $this->assertNotNull($attachment->AttachmentUrl);
        }
        $this->assertIdentical($data['attachments'][0]->FileName, 'AnswerFile.txt');
        $this->assertIdentical($data['attachments'][0]->ContentType, 'text/text');
        $this->assertIdentical($data['attachments'][1]->FileName, 'SiblingFile.txt');
        $this->assertIdentical($data['attachments'][1]->ContentType, 'text/text');
        Connect\ConnectAPI::RollBack();

        $this->restoreUrlParameters();
    }

    function buildAttachments(array $specs = array()) {
        $attachments = new Connect\FileAttachmentIncidentArray();
        foreach ($specs as $spec) {
            $attachment = new Connect\FileAttachmentIncident();
            $attachment->ContentType = $spec['ContentType'];
            $attachment->FileName = $spec['FileName'];
            $attachment->Private = $spec['Private'];
            $attachments[] = $attachment;
        }
        return $attachments;
    }

    function testGetAttachments() {
        $attachments = $this->buildAttachments(array(
            array(
                'FileName'   => 'product_specs',
                'ContentType' => 'application/msword',
                'Private'     => false,
            ),
            array(
                'FileName'   => 'logo',
                'ContentType' => 'image/jpeg',
                'Private'     => false,
            ),
            array(
                'FileName'   => 'roadmap',
                'ContentType' => 'application/pdf',
                'Private'     => true,
            ),
        ));

        $commonAttachments = (array) $this->buildAttachments(array(
            array(
                'FileName'   => 'my_summer_vacation',
                'ContentType' => 'application/msword',
                'Private'     => false,
            ),
            array(
                'FileName'   => 'forYourEyesOnly',
                'ContentType' => 'application/pdf',
                'Private'     => true,
            ),
        ));

        $instance = $this->createWidgetInstance(array('name' => 'Incident.FileAttachments'));
        $method = $this->getWidgetMethod('getAttachments', $instance);

        // No attachments
        $emptyFileAttachmentsObject = $this->buildAttachments();
        $this->assertIdentical(array(), $method(array(), array()));
        $this->assertIdentical(array(), $method($emptyFileAttachmentsObject, array()));

        // Only regular attachments
        $results = $method($attachments, array());
        $this->assertEqual(2, count($results));

        // Only common attachments
        $results = $method($emptyFileAttachmentsObject, $commonAttachments);
        $this->assertEqual(1, count($results));

        // Regular and common attachments. Verify 'private' attachments not returned.
        $results = $method($attachments, $commonAttachments);
        $this->assertIsA($results, 'array');
        $this->assertEqual(3, count($results));
        foreach($results as $attachment) {
            $this->assertNotNull($attachment->FileName);
            $this->assertNotNull($attachment->ContentType);
            $this->assertFalse($attachment->Private);
        }
    }

    //Test that the widget doesn't return false for Community objects that don't have file attachments
    function testGetDataCommunityAttachmentsWithNoAttachments() {
        $this->logIn();
        $this->fixtureInstance = new Fixture();
        //Create question without any attachments
        $question = $this->fixtureInstance->make('QuestionActiveModActive');
        $this->addUrlParameters(array('qid' => $question->ID));
        $instance = $this->createWidgetInstance(array('name' => 'CommunityQuestion.FileAttachments'));
        $data = $this->getWidgetData();
        $this->assertTrue($data ? true : false);
        $this->assertEqual(0, count($data['value']));
        $this->logOut();
        $this->fixtureInstance->destroy();
    }

    //test URL formation for Social attachments
    function testCommunityGetAttachments() {
        $this->logIn();
        $question = $this->CI->model('CommunityQuestion')->create(array(
            'CommunityQuestion.Subject' => (object) array('value' => 'CommunityQuestion subject'),
            'CommunityQuestion.Body' => (object) array('value' => 'CommunityQuestion body'),
            'CommunityQuestion.FileAttachments' => (object) array('value' => array(
                (object) array(
                    'localName' => 'winning',
                    'contentType' => 'application/msword',
                    'userName' => 'winning.docx'
                ),
                (object) array(
                    'localName' => 'warlock',
                    'contentType' => 'image/jpeg',
                    'userName' => 'warlock.jpg'
                )
            )
        )))->result;
        $this->addUrlParameters(array('qid' => $question->ID));
        $instance = $this->createWidgetInstance(array('name' => 'CommunityQuestion.FileAttachments'));
        $data = $this->getWidgetData();
        $method = $this->getWidgetMethod('getAttachments', $instance);
        $results = $method($data['value'], array());
        $this->assertEqual(2, count($results));
        foreach($results as $attachment) {
            $this->assertNotNull($attachment->FileName);
            $this->assertTrue(strpos($attachment->AttachmentUrl, '/cq/') !== false);
            $this->assertTrue(strpos($attachment->AttachmentUrl, '?token=') !== false);
        }
        $this->logOut();
    }
}
