<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Framework;
class FileAttachmentTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\FileAttachment';

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\FileAttachment();
        $this->CI = get_instance();
    }

    function testGet() {
        $return = $this->model->get('sdf', null);
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse($return->result);
        $this->assertSame(1, count($return->errors));
        $return = $this->model->get('12.34', null);
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse($return->result);
        $this->assertSame(1, count($return->errors));
        $return = $this->model->get(1, null);
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse($return->result);
        $this->assertSame(1, count($return->errors));
        $return = $this->model->get(24, null);
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($return->errors));
        // accessing community file through url with token
        $_GET['token'] = Framework::createCommunityAttachmentToken(32, '2010-03-25 23:04:04');
        $originalParameterSegment = $this->CI->config->item('parm_segment');
        $originalSegments = $segments = $this->CI->router->segments;
        $segments[] = 'cq';
        $segments[] = '22'; // setting question id
        $this->CI->router->segments = $segments;
        
        if (!\RightNow\Utils\Url::getParameter('cq'))
            $this->CI->config->set_item('parm_segment', $originalParameterSegment - 1);
        
        $return = $this->model->get(32, '2010-03-25 23:04:04', true);
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->errors));
        
        $this->CI->config->set_item('parm_segment', $originalParameterSegment);
        $this->CI->router->segments = $originalSegments;
        unset($_GET['token']);
        // accessing community file not through url
        $return = $this->model->get(32, '2010-03-25 23:04:04', false);
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->errors));
    }

    function testGetDetails() {
        $method = $this->getMethod('getDetails');
        $return = $method('abc');
        $this->assertFalse($return);
        $return = $method('12.2');
        $this->assertFalse($return);
        $return = $method('24');
        $this->assertIsA($return, 'stdClass');
        $this->assertIsA($return->type, 'int');
        $this->assertIsA($return->size, 'int');
        $this->assertIsA($return->created, 'int');
        $this->assertIsA($return->contentType, 'string');
        $this->assertIsA($return->localFileName, 'string');
    }

    function testGetIDFromCreatedTime() {
        $return = $this->model->getIDFromCreatedTime(0, 0, 0);
        $this->assertSame(false, $return->result);
    }

    function testValidate() {
        $attach=$this->setupValidate();
        $method = $this->getMethod('validate');
        $this->logOut();
        // logged out user attempts incident
        $return = $method((object) array(
            'table' => VTBL_INCIDENTS
        ), false, false);
        $this->assertFalse($return);

        $this->logIn('outboundrocks5@yahoo.com.invalid.060523.060509.060504.invalid.060523.060509.inva');

        // logged in but not validating date
        $return = $method((object) array(
            'table' => VTBL_INCIDENTS
        ), false, false);
        $this->assertFalse($return);

        // logged in, validating date; also checking incident owner
        $warning = '';
        $attachment = (object) array('table' => VTBL_INCIDENTS, 'id' => 142);
        $return = $method($attachment, true, false, $warning, $error);
        $this->assertTrue($return);
        $this->logOut();

        // answer status
        $return = $method((object) array(
            'table' => TBL_ANSWERS,
            'id' => 1,
        ), false, false);
        $this->assertTrue($return);
        // threads: invalid controller
        $return = $method((object) array(
            'table' => TBL_THREADS
        ), false, false);
        $this->assertFalse($return);
        // threads: valid controller
        get_instance()->router->class = 'inlineImage';
        $return = $method((object) array(
            'table' => TBL_THREADS
        ), false, false);
        $this->assertTrue($return);
        get_instance()->router->class = 'inlineImg';
        $return = $method((object) array(
            'table' => TBL_THREADS
        ), false, false);
        $this->assertTrue($return);
        // no table
        $return = $method((object) array(

        ), false, false);
        $this->assertTrue($return);
        // no table: image
        $return = $method((object) array(
            'contentType' => 'image/gif'
        ), false, false);
        $this->assertFalse($return);
        // no table: image, non-workflow image
        $return = $method((object) array(
            'contentType' => 'image/gif',
            'type' => FA_TYPE_AC_IMAGES
        ), false, false);
        $this->assertFalse($return);
        // no table: image, workflow image
        $return = $method((object) array(
            'contentType' => 'image/gif',
            'type' => FA_TYPE_WF_SCRIPT_IMAGE,
            'fileID' => 28
        ), true, false);
        $this->assertTrue($return);
        // no table: html
        $return = $method((object) array(
            'contentType' => 'text/html'
        ), false, false);
        $this->assertTrue($return);
        
        // @@@ QA 130610-000129 – testcases for validating sibling attachment
        //  visibility  
        $return = $method($attach, true, $warning, $error);
        $this->assertTrue($return);
        //Making the answer private and performing negative testcases
        $answer=Connect\Answer::fetch(1);
        $answer->StatusWithType->Status->ID=5;
        $answer->save();
        Connect\ConnectAPI::commit();
        $return = $method($attach, true, false, $warning, $error);
        $this->assertFalse($return);
        //Making it public and asserting again
        $answer=Connect\Answer::fetch(1);
        $answer->StatusWithType->Status->ID=4;
        $answer->save();
        Connect\ConnectAPI::commit();
        
        //Change the access level to a restricted access level and assert false
        $this->setAnswerAccessLevel($answer,4);
        //$warning="";
        $return = $method($attach, true, false, $warning, $error);
        $this->assertFalse($return);
        
        //Change it back to everyone and assert it to be true
        $this->setAnswerAccessLevel($answer,1);
        $return = $method($attach, true, false, $warning, $error);
        $this->assertTrue($return);
    }

    private function setAnswerAccessLevel(&$answer, $accessID) {
        $answer->AccessLevels->offsetUnset(0);
        $answer->AccessLevels[0] = new Connect\AccessLevel();
        $answer->AccessLevels[0]->ID = $accessID;
        $answer->save();
        Connect\ConnectAPI::commit();
    }
    // @@@ QA 130610-000129 Add a sibling attachment to an answer
    // for testing it against validate method 
    function setupValidate()
    {
    $answer=Connect\Answer::fetch(1);
    $f_count = count($answer->CommonAttachments);
    if($f_count > 0 ){
        for($f = ($f_count-1); $f >=0 ; $f--) {
            $answer->CommonAttachments->offsetUnset($f);
        }
    }
    $answer->CommonAttachments =new Connect\FileAttachmentSharedArray();
    $fattach = new Connect\FileAttachmentShared();
    $fattach->ContentType = "text/text";
    $fp = $fattach->makeFile();
    fwrite($fp,"Making some notes in this text file for the answer".date("Y-m-d h:i:s"));
    fclose($fp);
    $fattach->FileName = "NewTextFilecommon".date("Y-m-d_h_i_s").".txt";
    $fattach->Name = "New Text File ".date("Y-m-d h:i:s").".txt";
    $fattach->Names=new Connect\LabelRequiredArray();
    $fattach->Names[0]=new Connect\LabelRequired();
    $fattach->Names[0]->LabelText="Canada.txt";
    $fattach->Names[0]->Language= new Connect\NamedIDOptList();
    $fattach->Names[0]->Language->ID=1;
    
    $answer->CommonAttachments[] = $fattach;
    $answer->save();
    Connect\ConnectAPI::commit();
    
    $method = $this->getMethod('getDetails');
    $return = $method($answer->CommonAttachments[0]->ID,$answer->CommonAttachments[0]->CreatedTime);
    return $return;
    }

}
