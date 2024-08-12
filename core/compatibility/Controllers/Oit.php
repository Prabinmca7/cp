<?php
namespace RightNow\Controllers;
use RightNow\Utils\Framework,
    RightNow\Utils\Url,
    RightNow\Libraries\AbuseDetection,
    RightNow\Internal\Oit\Utils as Utils,
    RightNow\Internal\Utils\Version,
    RightNow\Utils\Config as Config,
    RightNow\Api as CoreApi;

if (!IS_HOSTED){
    require_once CORE_FILES . 'compatibility/Mappings/Functions.php';
}
require_once CORE_FILES . 'compatibility/Internal/Oit/Utils.php';
require_once CPCORE . 'Internal/Libraries/Version.php';

/**
 * This class defines endpoints to support Inlays
 */
final class Oit extends Base {

    const HTTP_RESPONSE_CACHE_TIME = 120;

    /**
     * Error message returned when file uploaded has no size
     * @internal
     */
    const EMPTY_FILE_ERROR = 10;

    /**
     * Error returned when a non-specific error occured
     * @internal
     */
    const GENERIC_ERROR = 2;

    function __construct() {
        parent::__construct();
        parent::_setClickstreamMapping(array(
            "getConfigs" => "get_configs_for_inlays",
            "authenticateChat" => "authenticate_chat_for_inlays",
        ));
        parent::_setMethodsExemptFromContactLoginRequired(array(
            "getConfigs",
            "authenticateChat"
        ));

        //Inlays are not supported below CX version 18.5 as OIT_CORS_ALLOWLIST is not available
        $currentCXVersion = new \RightNow\Internal\Libraries\Version(Version::getCXVersionNumber());
        if ($currentCXVersion->lessThan('18.5')) {
            header('HTTP/1.1 403 Forbidden');
            exit(getMessage(ACCESS_DENIED_LBL));
        }
    }

    /**
     * Checks if legact chat or mercury service is enabled
     * @return boolean
     */
    private function isChannelServiceEnabled() {
        return (\RightNow\Utils\Config::getConfig(MOD_CHAT_ENABLED) || \RightNow\Utils\Config::getConfig(MOD_ENGAGEMENT_CHANNELS_ENABLED));
    }

    /**
     * Endpoint to fetch configuration verbs. It is used to bootstrap inlays.
     * @return response json
     */
    public function getConfigs() {
        AbuseDetection::check();

        if(!Utils::isValidRequestDomain()) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            Utils::sendCORSHeaders(array('GET'), Utils::getAllowedOrigin(), self::HTTP_RESPONSE_CACHE_TIME);
            exit;
        }
        if (!$this->input->get('fields') || !in_array('application/json', explode(",", $_SERVER['HTTP_ACCEPT']))) {
            header('HTTP/1.1 400 Bad Request');
            return;
        }

        $cache = new \RightNow\Libraries\Cache\Memcache(240);
        $cacheKey = Utils::getSHA2Hash(Utils::getOriginalUrl());

        if ($cachedResponse = $cache->get($cacheKey)) {
            Utils::sendCORSHeaders(array('GET'), Utils::getAllowedOrigin(), self::HTTP_RESPONSE_CACHE_TIME);
            header("Cache-Control: public, s-maxage=" . self::HTTP_RESPONSE_CACHE_TIME . ", max-age=" . self::HTTP_RESPONSE_CACHE_TIME, true);
            echo $cachedResponse;
            return;
        }

        $allowedConfigs = array(
            'answerUri'                     => CP_ANSWERS_DETAIL_URL,
            'billingId'                     => ACS_BILLING_ID,
            'billingServiceHost'            => ACS_CAPTURE_HOST,
            'cachedContentServer'           => CACHED_CONTENT_SERVER,
            'channelCachedContentServer'    => CHANNEL_CACHED_CONTENT_SERVER,
            'channelServiceEnabled'         => self::isChannelServiceEnabled(),
            'channelServiceHost'            => SRV_CHAT_HOST,
            'fileUploadMaxSize'             => FATTACH_MAX_SIZE,
            'interfaceId'                   => \RightNow\Api::intf_id(),
            'interfaceName'                 => \RightNow\Api::intf_name(),
            'launchPage'                    => CP_CHAT_URL,
            'serviceHttpPort'               => SERVLET_HTTP_PORT,
            'servicePoolId'                 => CHAT_CLUSTER_POOL_ID,
            'siteUrl'                       => OE_WEB_SERVER,
            'tenantName'                    => DB_NAME,
            'tenantType'                    => 'osvc',
            'tenantVersion'                 => Version::getCXVersionNumber(),
            'userAbsentInterval'            => ABSENT_INTERVAL,
            'userAbsentRetryCount'          => USER_ABSENT_RETRY_COUNT,
            'validEmailPattern'             => DE_VALID_EMAIL_PATTERN,
            'videoClientScript'             => VIDEO_CHAT_CLIENT_SCRIPT,
            'videoEnabled'                  => MOD_VIDEO_CHAT_ENABLED
        );
        $computedConfigs = array('channelServiceEnabled', 'interfaceId', 'interfaceName', 'tenantVersion', 'tenantType');
        $askedConfigs = explode(',', str_replace(' ', '', $this->input->get('fields')));
        foreach($askedConfigs as $key) {
            if(array_key_exists($key, $allowedConfigs)) {
                $values[$key] = (in_array($key, $computedConfigs)) ? $allowedConfigs[$key] : \RightNow\Utils\Config::getConfig($allowedConfigs[$key]);
            }
        }
        $sendConfigs['items'] = $values ? array($values) : array(); //wrapping inside another array to match with engagement cloud response format
        $response = $cache->set($cacheKey, json_encode($sendConfigs));
        Utils::sendCORSHeaders(array('GET'), Utils::getAllowedOrigin(), self::HTTP_RESPONSE_CACHE_TIME);
        header("Cache-Control: public, s-maxage=" . self::HTTP_RESPONSE_CACHE_TIME . ", max-age=" . self::HTTP_RESPONSE_CACHE_TIME, true);
        echo $response;
    }

    /**
     * Endpoint to authenticate chat request raised via embedded chat inlay. It also passes the required token to upload
     * files during the course of chat.
     * @return json Chat jwt and file upload token
     */
    public function authenticateChat() {
        AbuseDetection::check();
        if(!Utils::isValidRequestDomain()) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        Utils::sendCORSHeaders(array('POST'), Utils::getAllowedOrigin(), self::HTTP_RESPONSE_CACHE_TIME);

        $chatType = 1; // hardcoded to standard chat since that is the only mode supported at this time
        $chatResult = Utils::processAuthenticateChat($chatType);
        if (!empty($chatResult->errors) || !$chatResult->result) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        }
        else {
            echo json_encode(array(
                'jwt' => $chatResult->result['jwt'],
                'pool' => $chatResult->result['pool'],
            ));
        }
    }

    /**
     * Endpoint to upload files via embedded chat inlay.
     * @return json Uploaded file information
     */
    public function fileUpload() {
        AbuseDetection::check();
        if(!Utils::isValidRequestDomain()) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        Utils::sendCORSHeaders(array('POST'), Utils::getAllowedOrigin(), self::HTTP_RESPONSE_CACHE_TIME);
        $fileInfo = $_FILES['file'];
        if($fileInfo['error'] === UPLOAD_ERR_NO_FILE)
        {
            return Utils::uploadError(Config::getMessage(FILE_PATH_FOUND_MSG), UPLOAD_ERR_NO_FILE);
        }
        if($fileInfo['size'] === false || $fileInfo['size'] === null || ($fileInfo['error'] > 0))
        {
            return Utils::uploadError(Config::getMessage(FILE_SUCC_UPLOADED_FILE_PATH_FILE_MSG), self::GENERIC_ERROR);
        }
        if($fileInfo['size'] === 0)
        {
            return Utils::uploadError(null, self::EMPTY_FILE_ERROR);
        }
        if($fileInfo['size'] > Config::getConfig(FATTACH_MAX_SIZE))
        {
            return Utils::uploadError(str_replace("{ FILESIZE }", Config::getConfig(FATTACH_MAX_SIZE) / 1000, Config::getMessage(TRYING_A_TOO_L_MAX_A_SIZE_FILESIZE_KB_MSG)));
        }
        $attachmentData = array(
            'name' => $fileInfo['name'],
            'size' => $fileInfo['size'],
            'mimetype' => $fileInfo['type']
        );
        $temporaryName = $fileInfo['tmp_name'];
        $temporaryName = basename(strval($temporaryName));
        $temporaryName = substr($temporaryName, 3) . $_SERVER['SERVER_NAME'];
        $newName = CoreApi::fattach_full_path($temporaryName);

        @unlink($newName);
        if(@rename($_FILES['file']['tmp_name'], $newName))
        {
            chmod($newName, 0666);
            if(IS_HOSTED && !CoreApi::fas_put_tmp_file($newName))
            {
                return Utils::uploadError(Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG));
            }
        }
        else
        {
            return Utils::uploadError(Config::getMessage(FILE_SUCC_UPLOADED_FILE_PATH_FILE_MSG), self::GENERIC_ERROR);
        }

        $fileInfo['tmp_name'] = $temporaryName;
        $fileInfo['name'] = Utils::sanitizeFilename($fileInfo['name']);

        if(strlen($fileInfo['name']) > 100)
        {
            return Utils::uploadError(Config::getMessage(NAME_ATTACHED_FILE_100_CHARS_MSG));
        }
        if (!preg_match("@^[a-z0-9.-]+$@i", $fileInfo['tmp_name']))
        {
            return Utils::uploadError(Config::getMessage(FILE_SUCC_UPLOADED_FILE_PATH_FILE_MSG), self::GENERIC_ERROR);
        }

        return Utils::renderJSON($fileInfo);
    }

    /**
     * Endpoint to download a temporary file from FAS
     * @return mixed Temp file from FAS
     */
    public function getTempFile() {
        AbuseDetection::check();
        if(!Utils::isValidRequestDomain()) {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        $fileMetadata = array(
            'contentType' => $this->input->get('contentType'),
            'createdTime' => $this->input->get('createdTime'),
            'fileSize' => $this->input->get('fileSize'),
            'localFileName' => $this->input->get('localFileName'),
            'userFileName' => $this->input->get('userFileName')
        );
        if (!($fileMetadata['contentType']
            && $fileMetadata['createdTime']
            && $fileMetadata['fileSize']
            && $fileMetadata['localFileName']
            && $fileMetadata['userFileName']
            && in_array('application/json', explode(",", $_SERVER['HTTP_ACCEPT'])))) {
            header('HTTP/1.1 400 Bad Request');
            return;
        }
        Utils::sendCORSHeaders(array('GET'), Utils::getAllowedOrigin(), self::HTTP_RESPONSE_CACHE_TIME);
        Utils::downloadFromTempLocation((object) $fileMetadata);
    }

    /**
     * Page to provide temporary documentation for OIT
     */
    public function docs() {
        AbuseDetection::check();
        $cachedContentServer = \RightNow\Utils\Config::getConfig(CACHED_CONTENT_SERVER);
        echo '
            <!DOCTYPE html>
            <head>
                <meta charset="utf-8"/>
                <title>Oracle Inlay Toolkit (OIT) Documentation</title>
            </head>
            <body>
                <h2>Oracle Inlay Toolkit (OIT) Documentation</h2>
                <p><a href="'. \RightNow\Utils\Url::getOriginalUrl(false) .'/s/oit/latest/" target="_blank">OIT Registry</a></p>
                <p>Inlay Attributes
                    <ul>
                        <li>site-url: '. $cachedContentServer .' </li>
                    </ul>
                </p>
            </body>
            </html>
        ';
    }
}
