<?php

namespace RightNow\Internal\Libraries;

use RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Api;

/**
 * A collection of methods related to encryption, decryption and authentication.
 */
final class Encryption {
    /**
     * Max lengths for the *decrypted* PTA_ENCRYPTION_[SALT|IV] value based on PTA_ENCRYPTION_METHOD
     */
    const PTA_SALT_MAX_LENGTH_DES = 8;
    const PTA_SALT_MAX_LENGTH_AES = 16;

    /**
     * Takes an encoded and possibly encrypted PTA string and decodes it into the source PTA data
     * @param string $ptaString Encoded/encrypted PTA string
     * @return string The decrypted PTA string
     * @throws \Exception If errors encountered.
     */
    public static function decryptPtaString($ptaString) {
        if (($decrypted = Api::decode_base64_urlsafe($ptaString)) === false) {
            throw new \Exception('ERROR_FAILED_DECODE');
        }
        if (($encryptionMethod = Config::getConfig(PTA_ENCRYPTION_METHOD)) !== '') {
            $decryptArgs = self::getApiCryptArgs($encryptionMethod, $decrypted);
            list($decrypted, $error) = self::apiDecrypt($decryptArgs);
            if ($decrypted === false) {
                Api::phpoutlog("Decryption failed with message: $error");
                if ($error && Text::stringContains($error, 'Unknown cipher method')) {
                    throw new \Exception('ERROR_UNSUPPORTED_ENCRYPTION_METHOD');
                }
                throw new \Exception('ERROR_FAILED_DECRYPTION');
            }
        }
        else if (Config::getConfig(PTA_IGNORE_CONTACT_PASSWORD)) {
            //Throw error here since enabling this config *requires* the use of encryption
            throw new \Exception('ERROR_MUST_USE_ENCRYPTION');
        }

        return $decrypted;
    }

    /**
     * Wrapper for the ske_buffer_encrypt Api method.
     * @param array $args An associative array containing keys expected by apiCrypt() below.
     * @return array A two element array containing the encrypted string and an error message if one was generated.
     */
    public static function apiEncrypt(array $args) {
        return self::apiCrypt($args, 'encrypt');
    }

    /**
     * Wrapper for the ske_buffer_decrypt Api method.
     * @param array $args An associative array containing keys expected by apiCrypt() below.
     * @return array A two element array containing the decrypted string and an error message if one was generated.
     */
    public static function apiDecrypt(array $args) {
        return self::apiCrypt($args, 'decrypt');
    }

    /**
     * Returns an array containing the common arguments to apiEncrypt and apiDecrypt.
     * @param string $encryptionMethod One of 'des3', 'aes128', 'aes192' or 'aes256'.
     * @param string $input Input value
     * @return array
     */
    public static function getApiCryptArgs($encryptionMethod, $input = '') {
        return array(
            'input' => $input,
            'secretKey' => Config::getConfig(PTA_SECRET_KEY),
            'encryptionMethod' => $encryptionMethod,
            'keygenMethod' => self::getPtaKeygenMethod(),
            'paddingMethod' => self::getPtaPaddingMethod(),
            'salt' => self::getPtaSaltOrIV(PTA_ENCRYPTION_SALT, $encryptionMethod),
            'initializationVector' => self::getPtaSaltOrIV(PTA_ENCRYPTION_IV, $encryptionMethod),
            'base64Encode' => false,
        );
    }
    
    /**
     * Generates cryptographically secured random bytess.
     * 
     * @param int $length Length of the random string to be generated
     * @return string Random string
     */
    public static function getRandomBytes($length = 8){
        // This function should get the random number via a cryptographically secure source, like:
        // 1. Direct "/dev/urandom", usage, e.g. (with no error checking at all):
        // 2. Exposing our libcmnapi rssl_get_rand_bytes() to internal PHP (or even cPHP)
        // 3. Using PHP 7+ (which has better random functions, like random_bytes())
         // TODO: The current hack of using ske_buffer_encryption should be replaced with one of the approaches from above.
        $randomString = '';
        for (; $length > 0; $length -= 8) {
            $randomString .= substr(self::getRandomBytesUsingEncryption(), 0, $length < 8 ? $length : 8);
        }
        return $randomString;
    }
    
    /**
     * Generates 8 bytes of random string using ske_buffer_encrypt.
     * @return string random bytes
     */
    private static function getRandomBytesUsingEncryption() {
        $cryptArgs = self::getApiCryptArgs('aes-128-cbc');
        $cryptArgs['input'] = "text";
        $cryptArgs['secretKey'] = "pw";
        $cryptArgs['keygenMethod'] = 1; // RSSL_KEYGEN_PKCS5_V15
        $cryptArgs['paddingMethod'] = 3; //RSSL_PAD_ZERO
        $cryptArgs['base64Encode'] = false;
        $cryptArgs['salt'] = "RANDOM_SALT";
        $cryptArgs['initializationVector'] = null;
        
        $encrypted = self::apiEncrypt($cryptArgs);
        if(empty($encrypted[1])) {
            if(strlen($encrypted[0]) < 8){
                return null;
            }
            $rndData = substr($encrypted[0], 0, 8);
            return $rndData;
        }
    }    

    /**
     * Wrapper for the ske_buffer_[encrypt|decrypt] Api methods.
     *
     * @param array $args An associative array having keys:
     *     - 'input'                The string to be encrypted or decrypted
     *     - 'secretKey'            The secret key
     *     - 'encryptionMethod'     The encryption method
     *     - 'keygenMethod'         The key generation method
     *     - 'paddingMethod'        The padding method
     *     - 'salt'                 The salt value
     *     - 'initializationVector' The Initialization vector value
     *     - 'base64Encode'         True if value should be base64 encoded.
     * @param string $mode One of 'encrypt' or 'decrypt'
     *
     * @return array A two element array containing the encrypted string and an error message if one was generated.
     */
    private static function apiCrypt(array $args, $mode) {
        $method = $mode === 'encrypt' ? 'ske_buffer_encrypt' : 'ske_buffer_decrypt';
        ob_start();
        $output = Api::$method(
            $args['input'],
            $args['secretKey'],
            $args['encryptionMethod'],
            $args['keygenMethod'],
            $args['paddingMethod'],
            $args['salt'],
            $args['initializationVector'],
            $args['base64Encode']
        );
        return array($output, ob_get_clean());
    }

    /**
     * Returns method to use for PTA padding
     * @return int The value of the PTA_ENCRYPTION_PADDING config.
     * @throws \Exception If an invalid value found.
     */
    private static function getPtaPaddingMethod() {
        $paddingMethod = intval(Config::getConfig(PTA_ENCRYPTION_PADDING));
        if (!in_array($paddingMethod, array(RSSL_PAD_PKCS7, RSSL_PAD_NONE, RSSL_PAD_ZERO, RSSL_PAD_ISO10126, RSSL_PAD_ANSIX923), true)) {
             throw new \Exception('ERROR_UNSUPPORTED_PADDING_METHOD');
        }
        return $paddingMethod;
    }

    /**
     * Returns method to use for PTA keygen
     * @return mixed The value of the PTA_ENCRYPTION_KEYGEN config.
     * @throws \Exception If an invalid value found.
     */
    private static function getPtaKeygenMethod() {
        $keygenMethod = intval(Config::getConfig(PTA_ENCRYPTION_KEYGEN));
        if ($keygenMethod === 3) {
            $keygenMethod = RSSL_KEYGEN_NONE;
        }

        if (!in_array($keygenMethod, array(RSSL_KEYGEN_PKCS5_V15, RSSL_KEYGEN_PKCS5_V20, RSSL_KEYGEN_NONE), true)) {
            throw new \Exception('ERROR_UNSUPPORTED_KEYGEN_METHOD');
        }
        return $keygenMethod;
    }

    /**
     * Returns the value from PTA_ENCRYPTION_SALT or PTA_ENCRYPTION_IV suitable for passing to ske_buffer_decrypt.
     *
     * EXAMPLES:
     * Config Value      Returns
     * ------------      -------
     * [null]            [null]
     * ''                [null]
     * ENCODED           RSSL_[SALT|IV]STR_RANDOM (define value)
     * 53616c7479426974  [hex decoded value]
     *
     * @param int $slot PTA_ENCRYPTION_SALT or PTA_ENCRYPTION_IV
     * @param string $encryptionMethod One of 'des3', 'aes128', 'aes192' or 'aes256'.
     * @return string|null
     * @throws \Exception If the config value exceeds the max length for the specified encryption method.
     */
    private static function getPtaSaltOrIV($slot, $encryptionMethod) {
        $value = Config::getConfig($slot);
        if ($value !== null && $value !== '') {
            if (trim(strtolower($value)) === 'encoded') {
                return ($slot === PTA_ENCRYPTION_SALT) ? RSSL_SALTSTR_RANDOM : RSSL_IVSTR_RANDOM;
            }
            $maxLength = ($encryptionMethod === 'des3') ? self::PTA_SALT_MAX_LENGTH_DES : self::PTA_SALT_MAX_LENGTH_AES;
            if($slot === PTA_ENCRYPTION_IV) {
                //The initialization vector should be padded to 16 bytes for AES and 8 bytes for DES
                $value = str_pad($value, $maxLength * 2, "0");
            }
            $decodedValue = self::decodeHexValue($value);
            if (strlen($decodedValue) > $maxLength) {
                throw new \Exception($slot === PTA_ENCRYPTION_SALT ? 'ERROR_SALT_VALUE_TOO_LONG' : 'ERROR_IV_VALUE_TOO_LONG');
            }
            return $decodedValue;
        }
    }

    /**
     * Decode hex encoded $value
     * @param string $value A hex encoded string.
     * @return string The decoded string.
     */
    private static function decodeHexValue($value) {
        // Using 'pack' as our version of PHP does not have a hex2bin function.
        return pack("H*", $value);
    }
}