<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text,
    RightNow\Connect\v1_4 as Connect;

class InlineImageTest extends CPTestCase {
    protected $attachmentAnswer = null;    

    function testGet() {
        $response = $this->makeRequest("/ci/InlineImage/get/foo/bar", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "404 Not Found"));
    }

    function testGuidGet() {
        //test 400 response received when no guid value provided
        $response = $this->makeRequest("/ci/InlineImage/guidGet", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "400 Bad Request"));

        //test 400 response received when invalid guid value provided
        $response = $this->makeRequest("/ci/InlineImage/guidGet/0123456789", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "400 Bad Request"));

        //create attachment and give it a guid value
        $content = "Some content";
        list($attachmentID,) = $this->createAttachment($content);
        test_sql_exec_direct("UPDATE fattach set guid_value = 12345678 WHERE file_id = $attachmentID");

        $response = $this->makeRequest("/ci/InlineImage/guidGet/12345678", array('justHeaders' => true));
        $this->assertTrue(Text::stringContains($response, "200 OK"));
    }

    function createAttachment($content, $fileName = null, $contentType = "image/png") {
        $ans = new Connect\Answer();
        $ans->FileAttachments = new Connect\FileAttachmentAnswerArray();
        $fattach = new Connect\FileAttachmentAnswer();
        $fattach->ContentType = $contentType;
        $fp = $fattach->makeFile();
        fwrite( $fp, $content );
        fclose( $fp );
        $fattach->FileName = $fileName ?: "NewImage.png";
        $ans->FileAttachments[] = $fattach;
        $ans->AccessLevels[] = 1; //everyone
        $ans->StatusWithType->Status->ID = 4; //public
        $ans->AnswerType->ID = 1;
        $ans->Language->ID = 1;
        $ans->Summary = "The summary of the answer";
        $ans->save();
        // Force a commit so we can access it via the controller
        Connect\ConnectAPI::commit();
        $this->attachmentAnswer = $ans;
        return array($ans->FileAttachments[0]->ID, $fattach);
    }
}
