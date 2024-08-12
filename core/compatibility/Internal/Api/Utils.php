<?php
namespace RightNow\Internal\Api;
use RightNow\Connect\Crypto\v1_4 as Crypto,
    RightNow\Internal\Utils\Version as Version,
    RightNow\Utils\Config;

/**
 * General utility class
 */

final class Utils {

    /**
     * Create SHA-256 hash of a string
     * @param string $message The string to be hashed
     * @return string SHA-256 hash of the string provided
     */
    public static function getSHA2Hash($message) {
        try {
            if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.8") >= 0)
                $md = new Crypto\MessageDigest();
            else
                $md = new \RightNow\Connect\Crypto\v1_3\MessageDigest();
            $md->Algorithm->ID = 3; //SHA256
            $md->Text = $message;
            $md->Encoding->ID = 1;
            $md->hash();
            $hashedText = $md->HashText;
            return bin2hex($hashedText);
        }
        catch(Exception $err) {
            return Response::generateResponseObject(null, null, $err->getMessage());
        }
    }

    /**
     * Default function to create KB answer url
     * @param int $id Unique answer id
     * @return string Answer Url for specified KB answer
     */
    public static function defaultAnswerUrl ($id) {
        static $answerUrlPrefix;
        if (!$answerUrlPrefix) {
            $answerUrlPrefix = '/app/' . Config::getConfig(CP_ANSWERS_DETAIL_URL) . '/a_id';
        }
        return "$answerUrlPrefix/$id" . (($keywordWithKey = self::getParameterWithKey('kw')) ? "/$keywordWithKey" : '') . self::sessionParameter();
    }

    /**
     * Same function as getParameter except that it returns a string of the
     * key and value separated by a / to be used in a URL.
     *
     * @param string $key The url key to search for.
     * @param bool $checkPostData Whether the key should be checked in the POST data, too.
     * @return mixed The string of key and value separate by a /, null otherwise
     */
    public static function getParameterWithKey($key, $checkPostData = true)
    {
        $CI = func_num_args() > 2 ? func_get_arg(2) : get_instance(); // Allow unit
        $uriSegments = $CI->uri->uri_to_assoc($CI->config->item('parm_segment'));
        if(array_key_exists($key, $uriSegments))
            return $key . '/' . $uriSegments[$key];
        if($checkPostData && ($postData = $CI->input->post($key)) !== false)
            return $key . '/' . urlencode($postData);
        return null;
    }

    /**
     * Returns the URL sessionParm if it is required to be in the URL
     *
     * @return string The url parameter that goes into the URL
     */
    public static function sessionParameter()
    {
        $CI = get_instance();
        if((!isset($CI->session) || !$CI->session) || ($CI->session->canSetSessionCookies() && $CI->session->getSessionData('cookiesEnabled')) || $CI->rnow->isSpider())
        {
            return '';
        }
        return $CI->session->getSessionData('sessionString');
    }

    /**
     * Utility method to escape string using PHPs htmlspecialchars method. Escapes
     * all quotes and also forces UTF-8 encoding. If value passed in is not a string then
     * it will just be returned unmodified.
     * @param string $string String to escape
     * @param bool $doubleEncode Whether to encode existing html entities
     * @return mixed Escaped string or original unmodified value if not a string.
     */
    public static function escapeHtml($string, $doubleEncode = true){
        if(!is_string($string)){
            return $string;
        }
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }

    /**
     * Commits any outstanding SQL operations to the DB, which may invoke additional mail actions.
     * @param boolean $disconnectDatabase Flag to indicate whether the database connection to be closed or not
     * @return void
     */
    public static function commit($disconnectDatabase = false) {
        if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.8") >= 0) {
            \RightNow\Connect\v1_4\ConnectAPI::commit();
        }
        else if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.3") <= 0) {
            \RightNow\Connect\v1_2\ConnectAPI::commit();
        }
        else {
            \RightNow\Connect\v1_3\ConnectAPI::commit();
        }
        if($disconnectDatabase) {
            if(!IS_HOSTED) {
                return;
            }
            \RightNow\Api::sql_disconnect();
        }
    }
}
