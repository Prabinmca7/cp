<?php
namespace RightNow;

use RightNow\Connect\v1_4 as Connect,
    RightNow\Internal\Utils\Version as Version;

/**
 * Various core RightNow API methods.
 */
final class Api extends \RightNow\Internal\Api{
    private static $messageCache = array();
    private static $configCache = array();
    private static $fallBackMessages = array(
        '_INTERNAL_TEST_MESSAGE_' => 'This message is here for testing purposes.',
    );
    private static $fallBackConfigs = array(
        '_INTERNAL_TEST_CONFIG_' => 'This config is here for testing purposes.',
        'wap_interface_enabled' => false
    );

    /**
     * Retrieves a configuration setting value of the appropriate type.
     * If a $slotID is not an integer/define, and a "fall-back" config has been defined, return that value.
     *
     * SPECIAL NOTE: This function is in the public namespace for performance reasons
     *
     * @param int|string $slotID Slot ID
     * @throws \Exception If $slotID is not valid, and a fall-back value is not defined.
     * @return mixed
     * @internal
     */
    public static function cfg_get_compat($slotID)
    {
        if (!is_int($slotID)) {
            if (array_key_exists($slotID, self::$fallBackConfigs)) {
                return self::$fallBackConfigs[$slotID];
            }
            throw new \Exception("Expected an integer for config slot ID, but got '" . var_export($slotID, true) . "' instead.");
        }

        if (array_key_exists($slotID, self::$configCache)) {
            return self::$configCache[$slotID];
        }

        return self::$configCache[$slotID] = cfg_get_casted($slotID);
    }

    /**
     * Returns the API profile of the user or null if the session ID/auth token does not match a user
     * @param string &$sessionID The session ID of the current user
     * @param string $authToken The users auth token string used to verify the user
     * @param array|null $contactPairData Data to optionally update/create the contact
     * @return object|null Contact profile object
     */
    public static function contact_login_verify(&$sessionID, $authToken, $contactPairData = array()){
        // handle case where newer style session is sent in (eg. framework version downgrade)
        if (is_array($sessionID) && $sessionID['s']) {
            $sessionID = $sessionID['s'];
        }

        $params = array(
            'sessionid' => $sessionID,
        );
        $modifiedChannelFields = array();
        if($contactPairData)
        {
            //Compatibility changes for 121114-000057, ensure that PTA country and province fields are valid
            if(isset($contactPairData['addr']) && is_array($contactPairData['addr']) && count($contactPairData['addr'])){
                $modifiedAddressFields = self::validateCountryAndProvince($contactPairData['addr']);
                if(count($modifiedAddressFields)){
                    $contactPairData['addr'] = $modifiedAddressFields;
                }
                else{
                    unset($contactPairData['addr']);
                }
            }
            //Compatibility changes for 130429-000034, tweak channel usernames to work with new sm_users table
            if(isset($contactPairData['channel_type']) && is_array($contactPairData['channel_type']) && ($username = $contactPairData['login'])){
                //If channel type fields are sent in, then at least one of them is new or modified. Figure out which ones those are so we can do some modifications
                $modifiedChannelFields = self::getListOfModifiedChannelFields($contactPairData['channel_type'], $username);
            }
            unset($contactPairData['channel_type']);
            $params['contact'] = $contactPairData;
        }
        if ($authToken)
        {
            $params['cookie'] = $authToken;
        }
        $apiStruct = (object) contact_login_verify($params);
        if(isset($apiStruct->session_expired) && $apiStruct->session_expired)
        {
            $sessionID = null;
        }
        if(isset($apiStruct->contact_login_ret_info))
        {
            //Since channels(now sm_users) is no longer a subobject of contact,
            //we have to take care of the update/creation of those things here instead
            //of in the contact_update/create
            if($modifiedChannelFields && ($contactID = $apiStruct->contact_login_ret_info['c_id'])){
                self::createOrUpdateChannelFields($contactID, $modifiedChannelFields);
            }
            return (object) $apiStruct->contact_login_ret_info;
        }
    }

    /**
     * Returns a decoded and decrypted value
     * @param string &$encryptEncoded The encrypted-then-encoded string
     * @return string The decoded and decrypted content
     */
    public static function decode_and_decrypt(&$encryptEncoded){
        if($encryptEncoded == null){
            return;
        }
        $bytes = decode_base64_urlsafe($encryptEncoded);
        $len = strlen($bytes);

        //swaps the pair of bits taking two bits at a time from starting
        for ($bit = 0; $bit < ((int)($len / 2) * 2); $bit += 2)
        {
                $tmp          = $bytes[$bit];
                $bytes[$bit]   = $bytes[$bit + 1];
                $bytes[$bit + 1] = $tmp;
        }
        for ($bit = 0; $bit < $len; $bit++)
        {
            //ord() returns the ASCII value for specified character
            $byte = ord($bytes[$bit]);
            if ($byte & 0x01)
                $byte = ($byte >> 1) | 0x80;
            else
                $byte = ($byte >> 1) & 0x7f;

            if ($bit % 2 && ($bit < 33))
                $byte = $byte ^ $bit;
            else
                $byte = ~$byte;
            //chr() returns a character for specified ASCII value
            $bytes[$bit] = chr($byte);
        }
        return $bytes;
    }

    /**
     * Encodes the passed in string so that it can be read by the marketing survey
     * @param string &$trackingString The string to encode.
     * @return string Encoded content
     */
    public static function generic_track_encode(&$trackingString){
        return generic_track_encode($trackingString);
    }

    /**
     * Returns the interface id.
     * @return int ID of the current interface
     */
    public static function intf_id(){
        static $intfId = null;
        if (is_null($intfId)){
            if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.8") >= 0){
                $query = Connect\ROQL::query("SELECT CURINTERFACE() as intf_id");
            }
            else{
                $query = \RightNow\Connect\v1_3\ROQL::query("SELECT CURINTERFACE() as intf_id");
            }

            $row = $query->next()->next();
            $intfId = (int) $row['intf_id'];
        }
        return $intfId;
    }

    /**
     * Returns the language id given the language code (e.g. en_US).
     * @param string $languageCode Human readable language code
     * @return int The ID of the current interfaces language
     */
    public static function lang_id($languageCode){
        static $language = null;
        if(is_null($language))
            $language = \RightNow\Utils\Connect::getNamedValues('SiteInterface', 'Language');

        $len = count($language);
        for ($i = 0; $i < $len; $i++)
        {
            if(!(strcmp($languageCode, $language[$i]->LookupName)))
                return $language[$i]->ID;
        }
        return 1;
    }

    /**
     * Returns message base value.
     * If $slotId is not an integer/define, and a "fall-back" value is defined, return that value.
     *
     * SPECIAL NOTE: This function is in the public namespace for performance reasons
     *
     * @param int $slotID Slot of message base
     * @throws \Exception If $slotID is not an integer and does not match a fall-back value.
     * @return mixed Message base value
     * @internal
     */
    public static function msg_get_compat($slotID)
    {
        if (!is_int($slotID)) {
            if (array_key_exists($slotID, self::$fallBackMessages)) {
                return self::$fallBackMessages[$slotID];
            }
            throw new \Exception("Expected an integer for message slot ID, but got '" . var_export($slotID, true) . "' instead.");
        }
        if (array_key_exists($slotID, self::$messageCache)) {
            return self::$messageCache[$slotID];
        }
        //handling custom message fetch via connect API
        if($slotID > 1000000) {
            if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.8") >= 0)
                $match = Connect\MessageBase::find("ID = $slotID");
            else
                $match = \RightNow\Connect\v1_3\MessageBase::find("ID = $slotID");
            return (self::$messageCache[$slotID] = (count($match) > 0 ? $match[0]->Value : null));
        }

        return (self::$messageCache[$slotID] = msg_get($slotID));
    }

    /**
     * Logs a message to the standard tracing mechanism (cphp tracing) with the default error level.
     * @param object|string|array|int|bool $message Message to log; arrays and objects are casted
     * to strings using var_export prior to being logged
     * @return bool Whether the message was logged
     */
    public static function phpoutlog($message) {
        if (is_array($message) || is_object($message)) $message = var_export($message, true);

        return phpoutlog($message ? $message : '');
    }

    /**
    * Returns a decrypted value that was encrypted using versioned symmetric key encryption
    * @param string &$encrypted The encrypted string
    * @return string Decrypted value
    */
    public static function ver_ske_decrypt(&$encrypted){
        return !is_null($encrypted) ? ver_ske_decrypt($encrypted) : null;
    }

    /**
    * Build a URL for accessing the specified survey
    * @param int $surveyID Survey for which to build URL
    * @param int|null $contactID Contact to associate with the survey
    * @param int|null $incidentID Incident to associate with the survey
    * @param int|null $chatID Chat to associate with the survey
    * @param int|null $opID Opportunity to associate with the survey
    * @return string URL for accessing this survey
    */
    public static function build_survey_url($surveyID, $contactID = null, $incidentID = null, $chatID = null, $opID = null) {
        $url = "/ci/documents/detail/5/$surveyID/12/" . sha1("{$surveyID}surVey-*-feeDback");
        if ($contactID !== null) {
            $trackString = generic_track_encode($contactID);
            $url .= "/1/$trackString";
        }

        //Incident ID and Chat ID are mutally exclusive
        if ($incidentID !== null) {
            $sourceString = "/" . MA_QS_SOURCE_PARM . "/" . VTBL_INCIDENTS . "/" . MA_QS_SOURCE_ID_PARM . "/" . $incidentID;
            $url .= $sourceString;
        }
        else if ($chatID !== null) {
            $sourceString = "/" . MA_QS_SOURCE_PARM . "/" . TBL_CHATS . "/" . MA_QS_SOURCE_ID_PARM . "/" . $chatID;
            $url .= $sourceString;
        }
        else if ($opID !== null) {
            $sourceString = "/" . MA_QS_SOURCE_PARM . "/" . VTBL_OPPORTUNITIES . "/" . MA_QS_SOURCE_ID_PARM . "/" . $opID;
            $url .= $sourceString;
        }

        return $url;
    }
}
