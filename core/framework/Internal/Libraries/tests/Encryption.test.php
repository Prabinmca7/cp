<?php

use RightNow\Internal\Libraries\Encryption,
    RightNow\UnitTest\Helper,
    RightNow\Utils\Config,
    RightNow\Api;

Helper::loadTestedFile(__FILE__);

class EncryptionTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Encryption';
    private static $ptaSecretKey = 'IJGaZMkMmuEoMs3pFpdGfpJpFHsMiwWk';

    function testDecryptPtaString() {
        list(, $ptaString) = $this->getPtaToken(true, false);
        $ptaToken = Api::encode_base64_urlsafe($ptaString);
        $result = Encryption::decryptPtaString($ptaToken);
        $this->assertEqual($result, $ptaString);

        $expected = 'ERROR_FAILED_DECODE';
        try {
            $result = Encryption::decryptPtaString($ptaString);
            $this->fail("Expected exception '$expected'");
        }
        catch (\Exception $e) {
            $this->assertEqual($expected, $e->getMessage());
        }

        //PTA with AES encryption - initialization vector should be padded to 16 bytes for AES encryption
        $ptaString = "p_addr.prov_id=7&p_ph_office=(450) 677-8797&p_email.addr=ldesroches@mediagrif.com&p_addr.country_id=1";

        //initialization vector is set as a 3 byte hex value - "466f6f"
        Helper::setConfigValues(array( 'PTA_ENCRYPTION_IV' => '466f6f',
		'PTA_ENCRYPTION_KEYGEN' => '3', 'PTA_ENCRYPTION_METHOD' => 'aes128',
		'PTA_IGNORE_CONTACT_PASSWORD' => '1', 'PTA_SECRET_KEY' => '86FXvP6N2ruG34Vj' ));

        $encrypted = $this->encryptPtaString($ptaString);
        $decrypted = \RightNow\Internal\Libraries\Encryption::decryptPtaString($encrypted);

        $this->assertEqual( $ptaString, $decrypted );

        //PTA with DES encryption - initialization vector should be padded to 8 bytes for DES encryption
        Helper::setConfigValues(array( 'PTA_ENCRYPTION_METHOD' => 'des3' ));

        $encrypted = $this->encryptPtaString($ptaString);
        $decrypted = \RightNow\Internal\Libraries\Encryption::decryptPtaString($encrypted);

        $this->assertEqual( $ptaString, $decrypted );

        Helper::setConfigValues(array( 'PTA_ENCRYPTION_IV' => '',
		'PTA_ENCRYPTION_KEYGEN' => '2', 'PTA_ENCRYPTION_METHOD' => '', 'PTA_ENCRYPTION_PADDING' =>  '5',
		'PTA_IGNORE_CONTACT_PASSWORD' => '', 'PTA_SECRET_KEY' => '' ));
    }

    function testApiDecrypt() {
        $encryptionMethod = 'des3';
        $args = $this->getCryptArgs($encryptionMethod);

        // Ensure a value encoded with ske_buffer_encrypt is decoded properly by decrypt
        $args['salt'] = '53616c7479426974';
        $args['initializationVector'] = 'edac34b187f65838';
        $rawInput = $args['input'];
        list($encryptedString, $error) = Encryption::apiEncrypt($args);
        $this->assertEqual(null, $error);
        $args['input'] = $encryptedString;
        $result = Encryption::apiDecrypt($args);
        $this->assertIsA($result, 'array');
        $this->assertEqual($rawInput, $result[0]);
        $this->assertEqual('', $result[1]);

        // A value of 'ENCODED' for the salt or iv specifies the corresponding value of the RSSL_[SALT|IV]STR_RANDOM be sent in.
        $args['salt'] = RSSL_SALTSTR_RANDOM;
        $args['initializationVector'] = RSSL_IVSTR_RANDOM;
        $args['input'] = $rawInput;
        list($encryptedString, $error) = Encryption::apiEncrypt($args);
        $this->assertEqual(null, $error);
        $args['input'] = $encryptedString;
        $result = Encryption::apiDecrypt($args);
        $this->assertIsA($result, 'array');
        $this->assertEqual($rawInput, $result[0]);
        $this->assertEqual('', $result[1]);
    }

    function testGetApiCryptArgs() {
        $encryptionMethod = 'des3';
        $result = $this->getCryptArgs($encryptionMethod);
        $this->assertIsA($result, 'array');
        $this->assertTrue(array_key_exists('secretKey', $result));
        $this->assertEqual($encryptionMethod, $result['encryptionMethod']);
        $this->assertTrue(array_key_exists('keygenMethod', $result));
        $this->assertTrue(array_key_exists('paddingMethod', $result));
        $this->assertTrue(array_key_exists('salt', $result));
        $this->assertTrue(array_key_exists('initializationVector', $result));
    }

    function testGetPtaPaddingMethod() {
        $getPtaPaddingMethod = $this->getMethod('getPtaPaddingMethod', true);
        $result = $getPtaPaddingMethod();
        $this->assertIsA($result, 'integer');
    }

    function testGetPtaKeygenMethod() {
        $getPtaKeygenMethod = $this->getMethod('getPtaKeygenMethod', true);
        $result = $getPtaKeygenMethod();
        $this->assertIsA($result, 'integer');
    }

    function testGetPtaSalt() {
        $method = $this->getMethod('getPtaSaltOrIV', true);
        $getPtaSalt = function($encryptionMethod = 'des3') use ($method) {
            return $method(PTA_ENCRYPTION_SALT, $encryptionMethod);
        };

        $result = $getPtaSalt();
        $this->assertNull($result);

        $raw = 'SaltyBit';
        $encoded = bin2hex($raw);
        Helper::setConfigValues(array('PTA_ENCRYPTION_SALT' => $encoded));
        $this->assertIdentical($raw, $getPtaSalt());

        // Special 'ENCODED' value that signifies the value of RSSL_SALTSTR_RANDOM define be used.
        Helper::setConfigValues(array('PTA_ENCRYPTION_SALT' => 'ENCODED'));
        $this->assertIdentical(RSSL_SALTSTR_RANDOM, $getPtaSalt());

        Helper::setConfigValues(array('PTA_ENCRYPTION_SALT' => null));
    }

    function testGetPtaIV() {
        $method = $this->getMethod('getPtaSaltOrIV', true);
        $getPtaIV = function($encryptionMethod = 'des3') use ($method) {
            return $method(PTA_ENCRYPTION_IV, $encryptionMethod);
        };

        $encryptionMethod = 'des3';
        $result = $getPtaIV($encryptionMethod);
        $this->assertNull($result);

        $raw = 'daVector';
        $encoded = bin2hex($raw);
        Helper::setConfigValues(array('PTA_ENCRYPTION_IV' => $encoded));
        $this->assertIdentical($raw, $getPtaIV($encryptionMethod));

        // Special 'ENCODED' value that signifies the value of RSSL_SALTSTR_RANDOM define be used.
        Helper::setConfigValues(array('PTA_ENCRYPTION_IV' => 'ENCODED'));
        $this->assertIdentical(RSSL_IVSTR_RANDOM, $getPtaIV($encryptionMethod));

        Helper::setConfigValues(array('PTA_ENCRYPTION_SALT' => null));
    }

    function testDecodeHexValue(){
        $decodeHexValue = $this->getMethod('decodeHexValue', true);

        // An array of inputs:
        //   array[0] - raw input
        //   array[1] - bin2hex(array[0])
        $inputs = array(
            array(10110111, '3130313130313131'),
            array('10110111', '3130313130313131'),
            array('SaltyBit', '53616c7479426974'),
            array('IvBit', '4976426974'),
            array('31', '3331'),
            array('a', '61'),
        );
        foreach ($inputs as $pair) {
            list($raw, $encoded) = $pair;
            $bin2hexed = bin2hex($raw);
            $decoded = $decodeHexValue($encoded, 8);
            if (strval($raw) !== $decoded) {
                $msg = sprintf("Decoded value does not match raw: %s - encoded: %s, decoded: %s", 
                    var_export($raw, true),
                    var_export($encoded, true),
                    var_export($decoded, true)
                );
                $this->fail($msg);

            }
        }
    }
    
    function testGetRandomBytes(){
        $this->assertNotNull(Encryption::getRandomBytes());
        $this->assertNotEqual(Encryption::getRandomBytes(),Encryption::getRandomBytes());
        $this->assertNotEqual(Encryption::getRandomBytes(),Encryption::getRandomBytes(450));
        $this->assertNotEqual(Encryption::getRandomBytes(400),Encryption::getRandomBytes(500));
        $this->assertNotEqual(strlen(Encryption::getRandomBytes(400)),strlen(Encryption::getRandomBytes(500)));
        $this->assertEqual(strlen(Encryption::getRandomBytes(600)),strlen(Encryption::getRandomBytes(600)));
    }    

    // -=-=-=-=-=- Utility Functions -=-=-=-=-=- //
    private function encryptPtaString($ptaString) {
        if (($encryptionMethod = Config::getConfig(PTA_ENCRYPTION_METHOD)) !== '') {
            $encryptArgs = \RightNow\Internal\Libraries\Encryption::getApiCryptArgs($encryptionMethod, $ptaString);
            list($encrypted, $error) = \RightNow\Internal\Libraries\Encryption::apiEncrypt($encryptArgs);
            if ($encrypted === false) {
                Api::phpoutlog("Encryption failed with message: $error");
                if ($error && Text::stringContains($error, 'Unknown cipher method')) {
                    throw new \Exception('ERROR_UNSUPPORTED_ENCRYPTION_METHOD');
                }
                throw new \Exception('ERROR_FAILED_ENCRYPTION');
            }
        }
        else if (Config::getConfig(PTA_IGNORE_CONTACT_PASSWORD)) {
            //Throw error here since enabling this config *requires* the use of encryption
            throw new \Exception('ERROR_MUST_USE_ENCRYPTION');
        }
        if (($encrypted = Api::encode_base64_urlsafe($encrypted)) === false) {
            throw new \Exception('ERROR_FAILED_DECODE');
        }
        return $encrypted;
    }

    private function getPtaToken($addSecretKey = true, $encode = true, $extras = '') {
        static $counter = 0;
        $counter++;
        $userID = "pta_test{$counter}";
        $tokens = array(
            "p_userid={$userID}",
            "p_passwd=",
            "p_email.addr={$userID}@email.null",
        );
        if ($addSecretKey) {
            $tokens[] = "p_li_passwd=" . self::$ptaSecretKey;
        }
        $tokenString = implode('&', $tokens) . $extras;
        return array($userID, $encode ? Api::encode_base64_urlsafe($tokenString) : $tokenString);
    }

    private function getCryptArgs($encryptionMethod) {
        list(,$ptaString) = $this->getPtaToken(true, false);
        $args = Encryption::getApiCryptArgs($encryptionMethod, $ptaString);
        $args['secretKey'] = self::$ptaSecretKey;
        return $args;
    }
}
