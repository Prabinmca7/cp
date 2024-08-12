<?php

use RightNow\Utils\Text,
    RightNow\Api;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class FileAttachmentUploadTest extends WidgetTestCase {
    public $testingWidget = 'standard/input/FileAttachmentUpload';

    /***
     * @@@ 210129-000086 CP default list of "valid_file_extensions"
     * @@@ 240227-000007 CP allowing any file extension for "valid_file_extensions"
     *
     * Test has 2 parts:
     *   1. check that the 'valid_file_extensions' widget attribute matches the expectation
     *   2. check that the 'valid_file_extensions' when passed as * allows any extension without any validation
     *   2. check that the hashed attribute is passed in the form token
     */
    function testDefaultFileExtensionsAndMediaTypes() {
        $instance = $this->getWidgetInstance();
        $expectedFileExtensions = "png,jpg,txt,gif,pdf,docx,bmp,doc,csv,xlsx,xls,jpeg,odt,odm,ods,odp,odf,msg,eml,rtf,ppt,pptx,htm,html,zip,wav,mov,mp4,mp3";
        $data = $this->getWidgetData($instance);
        if($data['js']['valid_file_extensions'] == "*")
        {
        $expectedFileExtensions = "*"; // set * when valid_file_extension is set to allow all extensions
        $this->assertEqual($expectedFileExtensions, $data['js']['valid_file_extensions']);
        }
        else
        $this->assertEqual($expectedFileExtensions, $data['js']['valid_file_extensions']);

        // 2. check that the hashed attribute is passed in the form token
        $token = $instance->generateFormConstraints();
        $decodedToken = Api::decode_base64_urlsafe($token);
        $decryptedToken = Api::ver_ske_decrypt($decodedToken);

        $constraints = json_encode(array('upload_' . $instance->instanceID => $expectedFileExtensions));
        $this->assertTrue(Text::beginsWith($decryptedToken, sha1($constraints)));
    }
}
