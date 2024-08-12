<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Api,
    RightNow\Libraries\Session,
    RightNow\UnitTest\Helper,
    RightNow\Connect\v1_4 as Connect;


class ApiTest extends CPTestCase
{
    public $testingClass = "RightNow\Api";

    public function testContactLoginVerify(){
        $userData = Helper::logInUser();
        // get a legit session and authToken
        $session = $userData["rawSession"];
        $encryptedProfile = urldecode($userData["profile"]);
        $profile = json_decode(Api::ver_ske_decrypt($encryptedProfile));
        $authToken = $profile->c;

        $result = Api::contact_login_verify($session->sessionID, $authToken);
        $this->assertSame($result->login, "slatest");

        $downgradedFrameworkSession = array("s" => $session->sessionID);
        $result = Api::contact_login_verify($downgradedFrameworkSession, $authToken);
        $this->assertSame($result->login, "slatest");

        Helper::logOutUser($userData["rawProfile"], $userData["rawSession"]);
        $this->logOut();
    }

    public function testContactLoginVerifyBadSession() {
        $userData = Helper::logInUser();
        // get a legit session and authToken
        $session = $userData["rawSession"];
        $encryptedProfile = urldecode($userData["profile"]);
        $profile = json_decode(Api::ver_ske_decrypt($encryptedProfile));
        $authToken = $profile->c;

        $badSession = "jjjjkkkk";
        $result = Api::contact_login_verify($badSession, $authToken);
        \RightNow\Utils\Framework::logMessage($result);
        $this->assertNull($result);

        Helper::logOutUser($userData["rawProfile"], $userData["rawSession"]);
        $this->logOut();
    }

    public function testDecodeAndDecryptEvenLength() {
        //even length input string
        $inputString = "~!@#$%even_token^&*()/|7";
        $encryptedInputString = Api::pw_rev_encrypt($inputString);
        $encodedAndEncryptedStr = Api::encode_base64_urlsafe($encryptedInputString);
        $outputString = Api::decode_and_decrypt($encodedAndEncryptedStr);
        $this->assertEqual("~!@#$%even_token^&*()/|7", $outputString);
    }

    public function testDecodeAndDecryptOddLength() {
        //odd length input string
        $inputString = "~!@#$%odd_token^&*()/|7";
        $encryptedInputString = Api::pw_rev_encrypt($inputString);
        $encodedAndEncryptedStr = Api::encode_base64_urlsafe($encryptedInputString);
        $outputString = Api::decode_and_decrypt($encodedAndEncryptedStr);
        $this->assertEqual("~!@#$%odd_token^&*()/|7", $outputString);
    }

    public function testDecodeAndDecryptEmailId() {
        $inputString = "testMail@decodeAndDecrypt.com.invalid";
        $encryptedInputString = Api::pw_rev_encrypt($inputString);
        $encodedAndEncryptedStr = Api::encode_base64_urlsafe($encryptedInputString);
        $outputString = Api::decode_and_decrypt($encodedAndEncryptedStr);
        $this->assertEqual("testMail@decodeAndDecrypt.com.invalid", $outputString);
    }

    public function testDecodeAndDecryptContactId() {
        $inputString = "1172";
        $encryptedInputString = Api::pw_rev_encrypt($inputString);
        $encodedAndEncryptedStr = Api::encode_base64_urlsafe($encryptedInputString);
        $outputString = Api::decode_and_decrypt($encodedAndEncryptedStr);
        $this->assertEqual("1172", $outputString);
    }

    public function testDecodeAndDecryptAllChar() {
        //input string containing all characters
        $inputString = "~`!@#$%^&*()_+-=1234567890QWERTYUIOPASDFGHJKL;ZXCVBNM,./<>?[]:'\{}|\"";
        $encryptedInputString = Api::pw_rev_encrypt($inputString);
        $encodedAndEncryptedStr = Api::encode_base64_urlsafe($encryptedInputString);
        $outputString = Api::decode_and_decrypt($encodedAndEncryptedStr);
        $this->assertEqual("~`!@#$%^&*()_+-=1234567890QWERTYUIOPASDFGHJKL;ZXCVBNM,./<>?[]:'\{}|\"", $outputString);
    }

    public function testDecodeAndDecryptNullChar() {
        //null input string
        $inputString = "";
        $encryptedInputString = Api::pw_rev_encrypt($inputString);
        $encodedAndEncryptedStr = Api::encode_base64_urlsafe($encryptedInputString);
        $outputString = Api::decode_and_decrypt($encodedAndEncryptedStr);
        $this->assertEqual("", $outputString);
    }

    public function testLangId() {
        $id = Api::lang_id(LANG_DIR);
        $query = Connect\ROQL::query("SELECT CURLANGUAGENAME() as lang_name, CURLANGUAGE() as lang_id");
        $row = $query->next()->next();
        $langId = (int) $row['lang_id'];
        $this->assertEqual($id, $langId);
    }

    public function testIntfId() {
        $id = Api::intf_id();
        $intfName = Api::intf_name();
        $result = Connect\ROQL::query("SELECT ID from SiteInterface where Name = '".$intfName."'");
        $row = $result->next()->next();
        $intfId = $row['ID'];
        $this->assertEqual($id, $intfId);
    }
}
