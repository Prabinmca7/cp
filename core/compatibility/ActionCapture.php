<?php

// Some of our products run on versions of PHP before 5.3, so we generally need to avoid PHP 5.3+ features.
// I stick this in a namespace to be nice to products which are using namespaces.
// If you don't have namespaces, feel free to comment out the line below.
namespace RightNow;

/**
 * This library records actions for the Action Capture Service and generates a snippet of markup to support browser based recording. For full documentation,
 * @see https://quartz.us.oracle.com/shelf/docs/Projects/ACS/ActionCaptureService_DesignDoc.html#actionphplibrary
 */
class ActionCapture {
    /**
     * Indicates if the library has received valid values for all of the required
     * configuration members that and is ready to record compliance actions.
     * For full documentation,
     * @see https://quartz.us.oracle.com/shelf/docs/Projects/ACS/ActionCaptureService_DesignDoc.html#actioncapture%3A%3Aisinitialized
     */
    public static function isInitialized() {
        return self::getInstance()->isInitialized();
    }

    /**
     * Indicates if the library has received valid values for all of the required
     * configuration members that and is ready to record instrumentation logs.
     * record actions.
     * For full documentation,
     * @see https://quartz.us.oracle.com/shelf/docs/Projects/ACS/ActionCaptureService_DesignDoc.html#actioncapture%3A%3Aisinstrumentationinitialized
     */
    public static function isInstrumentationInitialized() {
        return self::getInstance()->isInstrumentationInitialized();
    }

    /**
     * Provides the library with values that will be recorded with every action or instrumentation log. For full documentation,
     * @see https://quartz.us.oracle.com/shelf/docs/Projects/ACS/ActionCaptureService_DesignDoc.html#actioncapture%3A%3Ainitialize
     */
    public static function initialize(array $configArray) {
        self::getInstance()->initialize($configArray);
    }

    /**
     * Asynchronously logs an action. For full documentation,
     * @see https://quartz.us.oracle.com/shelf/docs/Projects/ACS/ActionCaptureService_DesignDoc.html#actioncapture%3A%3Arecord
     */
    public static function record($subject, $verb, $object = "") {
        self::getInstance()->record($subject, $verb, $object);
    }

    /**
     * Asynchronously records instrumentation data. For full documentation,
     * @see https://quartz.us.oracle.com/shelf/docs/Projects/ACS/ActionCaptureService_DesignDoc.html#actioncapture%3A%3Ainstrument
     */
    public static function instrument($subject, $verb, $level, array $customFields = array(), $duration = null) {
        self::getInstance()->instrument($subject, $verb, $level, $customFields, $duration);
    }

    /**
     * Generates an HTML block to enable client-side action capture. For full documentation,
     * @see https://quartz.us.oracle.com/shelf/docs/Projects/ACS/ActionCaptureService_DesignDoc.html#actioncapture%3A%3Acreatesnippet
     */
    public static function createSnippet($captureServer, $useMinifiedJS = true, $resourceBasePath = '/', $requestEngagementIdSecurely = true) {
        return self::getInstance()->createSnippet($captureServer, $useMinifiedJS, $resourceBasePath, $requestEngagementIdSecurely);
    }

    /**
     * Like createSnippet(), but only returns the script portion of the snippet.  For full documentation,
     * @see https://quartz.us.oracle.com/shelf/docs/Projects/ACS/ActionCaptureService_DesignDoc.html#actioncapture%3A%3Acreatescriptsnippet
     * @deprecated Prefer createSnippet().
     */
    public static function createScriptSnippet($captureServer, $useMinifiedJS = true, $resourceBasePath = '/', $requestEngagementIdSecurely = true) {
        return self::getInstance()->createScriptSnippet($captureServer, $useMinifiedJS, $resourceBasePath, $requestEngagementIdSecurely);
    }

    /**
     * Like createSnippet(), but only returns the noscript portion of the snippet.  For full documentation,
     * @see https://quartz.us.oracle.com/shelf/docs/Projects/ACS/ActionCaptureService_DesignDoc.html#actioncapture%3A%3Acreatenoscriptsnippet
     * @deprecated We realized this was useless. It remains in the API for compatibility, but returns an empty string.
     */
    public static function createNoScriptSnippet($captureServer, $resourceBasePath = '/', $requestEngagementIdSecurely = true) {
        return self::getInstance()->createNoScriptSnippet($captureServer, $resourceBasePath, $requestEngagementIdSecurely);
    }

    /**
     * Returns a string representing a serialized action based on the passed `$arguments`.
     * Can be thought of as a combination of initialize() and record().
     * @see https://quartz.us.oracle.com/shelf/docs/Projects/ACS/ActionCaptureService_DesignDoc.html#actioncapture%3A%3Aserializeaction
     */
    public static function serializeAction(array $arguments) {
        return self::getInstance()->serializeAction($arguments);
    }

    /**
     * Returns a string representing a serialized instrumentation log based on the passed `$arguments`.
     * Can be thought of as a combination of initialize() and instrument().
     * @see https://quartz.us.oracle.com/shelf/docs/Projects/ACS/ActionCaptureService_DesignDoc.html#actioncapture%3A%3Aserializeinstrumentation
     */
    public static function serializeInstrumentation(array $arguments) {
        return self::getInstance()->serializeInstrumentation($arguments);
    }

    private static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ActionCaptureImpl();
        }
        return self::$instance;
    }

    private static $instance = null;

    // Maximum lengths that will be accepted by initialize(), record() or instrument() for various fields.
    // In debug mode, the library will generally whine at you if the value exceeds this length.
    // If you find that the library is whining at you about a value you think is reasonable,
    // please let the ACS team know.
    // In non-debug mode, the library will generally truncate the value to this length.
    const BILLING_ID_MAX_LENGTH = 8;
    const URL_HASH_MAX_LENGTH = 8;
    const INSTANCE_ID_MAX_LENGTH = 97;
    const SESSION_ID_MAX_LENGTH = 127;
    const REFERRER_HOST_MAX_LENGTH = 150; // This should be applied internally after stripping referrer to just host.
    const CLEAN_URL_MAX_LENGTH = 2083;
    const USER_AGENT_MAX_LENGTH = 400;
    const PRODUCT_FAMILY_MAX_LENGTH = 4;
    const PRODUCT_MAX_LENGTH = 25;
    const PRODUCT_VERSION_MAX_LENGTH = 25;
    const REMOTE_IP_MAX_LENGTH = 39; // Accepts an IPv4 or IPv6 address
    const SUBJECT_MAX_LENGTH = 25;
    const VERB_MAX_LENGTH = 25;
    const OBJECT_MAX_LENGTH = 700;
    const OPERATING_SYSTEM_MAX_LENGTH = 100;
    const OPERATING_SYSTEM_VERSION_MAX_LENGTH = 100;
    const BROWSER_MAX_LENGTH = 100;
    const BROWSER_VERSION_MAX_LENGTH = 100;

    // Regex-style character classes for the characters which will be accepted by initialize() and record().
    // In debug mode, the library will generally whine at you if the value doesn't fit this constraint
    // If you find that the library is whining at you about a value you think is reasonable,
    // please let the ACS team know.
    // In non-debug mode, the library will generally filter out characters which don't match this constraint.
    const PRINTABLE_ASCII = '[ -~]';
    const PRINTABLE_ASCII_EXCEPT_TILDE = '[ -}]';
    const BILLING_ID_VALID_CHARACTERS = '[a-z0-9]';
    const INSTANCE_ID_VALID_CHARACTERS = '[-_:A-Za-z0-9]';
    const SESSION_ID_VALID_CHARACTERS = '[-~_.()*0-9a-zA-Z]';
    const REFERRER_VALID_CHARACTERS = self::PRINTABLE_ASCII; // This is much more liberal than the URI RFC would allow, but it turns out browsers are much more liberal than the RFC.
    const URL_VALID_CHARACTERS = self::PRINTABLE_ASCII; // Ditto. Chrome seems to always encode at least '<', '>', '%', and '"' but I feel like there's not all that much value to being restrictive. IE doesn't always encode space.
    //We are allowing all characters because in the transform we will replace non printables with spaces.
    const USER_AGENT_VALID_CHARACTERS = '[\x00-\xFF]';
    const PRODUCT_FAMILY_VALID_CHARACTERS = '[a-z]';
    const PRODUCT_VALID_CHARACTERS = self::PRINTABLE_ASCII_EXCEPT_TILDE;
    const PRODUCT_VERSION_VALID_CHARACTERS = self::PRINTABLE_ASCII_EXCEPT_TILDE;
    const REMOTE_IP_VALID_CHARACTERS = '[0-9a-f:.]'; // Accepts an IPv4 or IPv6 address
    const SUBJECT_VALID_CHARACTERS = '[-0-9A-Za-z]';
    const VERB_VALID_CHARACTERS = '[-0-9A-Za-z]';
    const COMMON_USER_ID_VALID_CHARACTERS = self::PRINTABLE_ASCII; // This will probably just contain digits, but I don't see a reason to be that restrictive.
    const PROVIDER_ID_VALID_CHARACTERS = self::PRINTABLE_ASCII;
    const OPERATING_SYSTEM_VALID_CHARACTERS = self::PRINTABLE_ASCII;
    const OPERATING_SYSTEM_VERSION_VALID_CHARACTERS = self::PRINTABLE_ASCII;
    const BROWSER_VALID_CHARACTERS = self::PRINTABLE_ASCII;
    const BROWSER_VERSION_VALID_CHARACTERS = self::PRINTABLE_ASCII;
    const HOST_REGEX = "@^(?:[[]((?:(?:[0-9a-fA-F]{1,4}[:]{1,2})|(?:[:]{2}))[0-9a-fA-F]{1,4}[0-9a-fA-F:.]*)[]])|([^#?:/.]+(?:[.][^#?:/.]+)*)@";
    /// The name for custom fields must conform to this pattern. Fields with non-conforming names will be discarded.
    const CUSTOM_FIELD_NAME_VALID_CHARACTERS = '@^[A-Za-z][_A-Za-z0-9]*$@';
    // There is no OBJECT_VALID_CHARACTERS because validation is not performed on the object.
    // Instead, the library silently replaces each "bad" character with a space.
    // The "bad" characters are [\x00-\x1F\x7F]
}

class InstrumentationLevel {
    const DEBUG = "debug";
    const INFO = "info";
    const ERROR = "error";
    const FATAL = "fatal";
}

// ---------------------------------------------------------------------
// And that concludes our tour of the public API of ActionCapture, folks.
// Anything below this point is not approved by OSHA, FHA, FAA, or FDIC.
// ---------------------------------------------------------------------

/**
 * @private
 * The man behind the curtain of ActionCapture.
 * I took this approach to simplify unit testing (since nobody likes to unit test a singleton) while still keeping ActionCapture a singleton for easy use.
 */
class ActionCaptureImpl {
    public function __construct() {
        foreach ($this->fieldDefinitions as $meta) {
            list ( , , , $appliesTo, $outputFields) = $meta;
            foreach ($outputFields as $outputFieldName => $junk) {
                $this->outputFieldToAppliesTo[$outputFieldName] = $appliesTo;
            }
        }
    }

    // Map from output field name to an applies to constant (which can be passed to getMapFor()).
    private $outputFieldToAppliesTo = array();

    public function isInitialized($notActuallyRequiredFields = array()) {
        return $this->isInitializedImpl(self::APPLIES_TO_COMPLIANCE, $notActuallyRequiredFields);
    }

    public function isInstrumentationInitialized() {
        return $this->isInitializedImpl(self::APPLIES_TO_INSTRUMENTATION, array());
    }

    private function isInitializedImpl($appliesToMask, $notActuallyRequiredFields) {
        foreach (array_diff_key($this->fieldDefinitions, $notActuallyRequiredFields) as $field => $meta) {
            list( , , $isRequired, $appliesTo, $outputFields) = $meta;
            if (($isRequired === self::REQUIRED) && $this->isApplicable($appliesTo, $appliesToMask)) {
                //This checks to see if a transform of a required field failed or if it wasn't passed in
                foreach ($outputFields as $jsonKey => $transform) {
                    if (!array_key_exists($jsonKey, $this->getMapFor($appliesTo))) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    private function isApplicable($appliesTo, $appliesToMask) {
        return ($appliesToMask & $appliesTo) !== 0;
    }

    private function &getMapFor($appliesTo) {
        if ($appliesTo === self::APPLIES_TO_BOTH) return $this->configMap;
        else if ($appliesTo === self::APPLIES_TO_COMPLIANCE) return $this->complianceOnlyConfigMap;
        else if ($appliesTo === self::APPLIES_TO_INSTRUMENTATION) return $this->instrumentationOnlyConfigMap;
        else throw new \Exception("Unknown \$appliesTo=$appliesTo.");
    }

    /**
     * Do not be tricked by this function name... it isn't required to use the library (though it usually is used).
     * In some products serializeAction is directly called without using this.
     *
     * This class should be used by either using initialize() + record() or by using serializeAction().
     */
    public function initialize($configArray) {
        if (!is_array($configArray)) {
            $this->reportError("The argument passed to ActionCapture::initialize must be an array.");
            return;
        }

        // We check debug before we do anything else so it doesn't have to appear first to be effective.
        if (array_key_exists('debugMode', $configArray)) {
            $this->debugMode = ($configArray['debugMode'] === true);
            unset($configArray['debugMode']);
        }

        if (array_key_exists('logDirectory', $configArray)) {
            $this->handleLogDirectory('logDirectory', $configArray['logDirectory']);
            unset($configArray['logDirectory']);
        }

        //We've decided that if a remoteIP wasn't provided or if it was provided as an empty string we'll
        //set it to loopback. We did this because compliance and instrumentation had differing opinions on
        //what was legal and this was the most straightforward way to keep with our "error on initialization".
        if(!array_key_exists('remoteIP', $configArray) || $configArray['remoteIP'] === NULL || $configArray['remoteIP'] === ''){
            $configArray['remoteIP'] = "127.0.0.1";
        }

        foreach ($this->fieldDefinitions as $fieldName => &$meta) {
            if (array_key_exists($fieldName, $configArray)) {
                $this->handleField($fieldName, $configArray[$fieldName]);
                unset($configArray[$fieldName]);
            }
        }

        if (!empty($configArray)) {
            $unrecognizedKeys = implode(', ', array_keys($configArray));
            $this->reportError("ActionCapture did not recognize the configuration keys: {$unrecognizedKeys}.");
        }
    }

    /**
     * @param $timestamp is expected to always be null when called through the facade, but to be set when called by serializeAction.
     */
    public function record($subject, $verb, $object, $timestamp = null, $hostname = null) {
        if ($this->isInitialized()) {
            $subject = (string)$subject;
            $verb = (string)$verb;
            $object = (string)$object;
            if ($this->validateSubjectOrVerb('subject', $subject) && $this->validateSubjectOrVerb('verb', $verb)) {
                $object = $this->filterObject($object);
                $this->getLogger()->record($this->serializeActionImpl(
                    $subject, $verb, $object, $timestamp, $hostname));
            }
        }
        else {
            $this->reportError("ActionCapture::record may not be called until all of the required fields are correctly initialized.  " .
                "The required fields and whether they are correctly set: " . $this->getRequiredFieldsState(self::APPLIES_TO_COMPLIANCE));
        }
    }

    public function instrument($subject, $verb, $level, $customFields = array(), $duration = null, $timestamp = null, $hostname = null) {
        if ($this->isInstrumentationInitialized()) {
            $subject = (string)$subject;
            $verb = (string)$verb;
            if ($this->validateSubjectOrVerb('subject', $subject) && $this->validateSubjectOrVerb('verb', $verb) && $this->isValidInstrumentationLevel($level)) {
                $this->getLogger()->record($this->serializeInstrumentationImpl($subject, $verb, $level, $customFields, $duration, $timestamp, $hostname));
            }
        }
        else {
            $this->reportError("ActionCapture::instrument may not be called until all of the required fields are correctly initialized.  " .
                "The required fields and whether they are correctly set: " . $this->getRequiredFieldsState(self::APPLIES_TO_INSTRUMENTATION));
        }
    }

    private function validateTimestamp($timestamp) {
        $earliestValidTimeMillis = 1262304000000; // 2010-01-01T00:00:00Z in milliseconds
        if ($timestamp !== null && ($timestamp < $earliestValidTimeMillis)) {
            $this->reportError("ActionCapture::serializeAction was called with a 'timestamp' value ($timestamp) less than the earliest valid value ($earliestValidTimeMillis).  The time should be expressed in milliseconds since the unix epoch.");
            $timestamp = null;
        }
        return ($timestamp === null) ? (round(microtime(true) * 1000)) : $timestamp;
    }

    // Caches the value of php_uname();
    private $hostname = null;

    public function validateHostname($hostname) {
        if ($hostname !== null && $hostname !== "") {
            return $hostname;
        }
        if ($this->hostname === null) {
            $this->hostname = php_uname('n'); // I'm calling php_uname() instead of gethostname() for compatibility with php 5.2.
        }
        return $this->hostname;
    }

    public function createNoScriptSnippet($captureServer, $resourceBasePath, $requestEngagementIdSecurely) {
        return "";
    }

    public function createScriptSnippet($captureServer, $useMinifiedJS, $resourceBasePath, $requestEngagementIdSecurely) {
        if (!$this->ensureCreateSnippetRequiredFieldsAreSet() ||
            !($captureServer = $this->ensureCaptureServerIsSet($captureServer))) {
            return "";
        }
        $resourceBasePath = $this->ensurePathBeginsAndEndsWithSlash($resourceBasePath);
        $engagementIdProtocol = $this->getEngagementIdProtcol($requestEngagementIdSecurely);

        // I didn't json_encode either of these variables because I know they don't contain any bad
        // characters and because PHP 5.3's json_encode always needlessly escapes "/".
        $jsLibraryUrl = $resourceBasePath . ($useMinifiedJS ? 'api/1/javascript/acs.js' : 'api/1/javascript/acs.debug.js');
        $jsEngagementIdUrl = "{$resourceBasePath}api/e/{$this->getConfigurationValue('billinggroupid')}/e.js";

        $sessionID = $this->getConfigurationValue('sessionid');
        $altSessionID = $this->getConfigurationValue('altsessionid');
        $urlHash = $this->getConfigurationValue('urlhash');
        $cleanUrl = $this->getConfigurationValue('url');
        // The slightly odd construction here is to ensure that debug comes first in the settings, but
        // also to ensure that debug, sessionID, etc. don't appear if false-y.
        $config = json_encode(array_merge(
            $this->debugMode ? array("debug" => true) : array(),
            $sessionID != null ? array("s" => $sessionID) : array(),
            $altSessionID != null ? array("as" => $altSessionID) : array(),
            $urlHash != null ? array("uh" => $urlHash) : array(),
            $cleanUrl != null ? array("uc" => $cleanUrl) : array(),
            array(
                "b" => $this->getConfigurationValue('billinggroupid'),
                "i" => $this->getConfigurationValue('instanceid'),
                "f" => $this->getConfigurationValue('productfamily'),
                "p" => $this->getConfigurationValue('product'),
                "v" => $this->getConfigurationValue('productversion'),
                "th" => $captureServer,
            )
        ));

        // The evolving scene of asynchronous script loading is described at:
        // http://www.lognormal.com/blog/2012/12/12/the-script-loader-pattern/
        // This code is based on that work and its predecessors.
        // minified using Google's closure compiler: https://developers.google.com/closure/compiler/

        /************Unminified version*****************
        var _rnq=_rnq||[];
        _rnq.push($config);
        (function(urlArray){
            var frameDocument,documentDomain,frame=document.createElement('iframe'),script=document.getElementsByTagName('script');
            frame.src="javascript:false";
            frame.title="Action Capture";
            frame.role="presentation";
            (frame.frameElement||frame).style.cssText="position:absolute;width:0;height:0;border:0";
            script=script[script.length-1];
            script.parentNode.insertBefore(frame,script);
            try { frameDocument=frame.contentWindow.document; }
            catch(e) {
                documentDomain=document.domain;
                frame.src="javascript:var d=document.open();d.domain='"+documentDomain+"';void(0);";
                frameDocument=frame.contentWindow.document;
            }
            frameDocument.open()._l = function() {
                var scriptElement;
                while(urlArray.length) {
                    scriptElement=this.createElement('script');
                    if(documentDomain) this.domain=documentDomain;
                    scriptElement.src=urlArray.pop();
                    this.body.appendChild(scriptElement);
                }
            };
            frameDocument.write('<body onload="document._l();">');
            frameDocument.close();
        })(['$engagementIdProtocol$captureServer$jsEngagementIdUrl','//$captureServer$jsLibraryUrl']);
        ************************************************/
        return <<<SNIPPET
<script type="text/javascript">
var _rnq=_rnq||[];_rnq.push($config);
(function(e){var b,d,a=document.createElement("iframe"),c=document.getElementsByTagName("script");a.src="javascript:false";a.title="Action Capture";a.role="presentation";(a.frameElement||a).style.cssText="position:absolute;width:0;height:0;border:0";c=c[c.length-1];c.parentNode.insertBefore(a,c);try{b=a.contentWindow.document}catch(f){d=document.domain,a.src="javascript:var d=document.open();d.domain='"+d+"';void(0);",b=a.contentWindow.document}b.open()._l=function(){for(var a;e.length;)a=this.createElement("script"),
d&&(this.domain=d),a.src=e.pop(),this.body.appendChild(a)};b.write('<head><title>Action Capture</title></head><body onload="document._l();">');b.close()})(["$engagementIdProtocol$captureServer$jsEngagementIdUrl","//$captureServer$jsLibraryUrl"]);
</script>
SNIPPET;
    }

    public function createSnippet($captureServer, $useMinifiedJS, $resourceBasePath, $requestEngagementIdSecurely) {
        return $this->createScriptSnippet($captureServer, $useMinifiedJS, $resourceBasePath, $requestEngagementIdSecurely);
    }

    private function getEngagementIdProtcol($requestEngagementIdSecurely) {
        // Normally we want to request the engagement ID over SSL in order to have the
        // same value regardless of whether the page is SSL.  Another reason is that sending
        // the engagement ID over SSL should prevent all but the most egregious
        // HTTP proxies from caching it.  However, not all development contexts
        // support SSL, so I provide an option to request it on the same
        // protocol as the page.
        return $requestEngagementIdSecurely ? "https://" : "//";
    }

    private function ensureCaptureServerIsSet($captureServer) {
        $captureServer = trim($captureServer);
        if ($captureServer === "") {
            $this->reportError("ActionCapture::createSnippet needs a valid captureServer.");
            return false;
        }
        return $captureServer;
    }

    private function ensureCreateSnippetRequiredFieldsAreSet() {
        if (!$this->isInitialized(array('sessionID' => false))) {
            $this->reportError("ActionCapture::createSnippet may not be called until all of the required fields are correctly initialized.  " .
                "The required fields and whether they are correctly set: " . $this->getRequiredFieldsState(self::APPLIES_TO_COMPLIANCE));
            return false;
        }
        return true;
    }

    private function ensurePathBeginsAndEndsWithSlash($path) {
        $path = trim($path);
        if (substr($path, 0, 1) !== '/') {
            $path = "/$path";
        }
        if (substr($path, -1) !== '/') {
            $path = "$path/";
        }
        return $path;
    }

    private function serialize($arguments, $origin, $method, $keys) {
        if (!is_array($arguments)) {
            $this->reportError("The argument passed to ActionCapture::" . $origin . " must be an array.");
            return;
        }

        //We clear this so that we don't use values from previous calls
        $this->configMap = array();
        $this->complianceOnlyConfigMap = array();
        $this->instrumentationOnlyConfigMap = array();

        foreach ($this->fieldDefinitions as $fieldName => &$meta) {
            if (array_key_exists($fieldName, $arguments)) {
                $this->handleField($fieldName, $arguments[$fieldName]);
            }
        }
        $oldLogger = $this->getLogger();

        try {
            $logger = new SingleActionLogger();
            $this->setLogger($logger);
            $safe_array_ref = function ($key) use ($arguments) { return array_key_exists($key, $arguments) ? $arguments[$key] : null; };
            $method_arguments = array_map($safe_array_ref, $keys);
            call_user_func_array(array($this, $method), $method_arguments);
            $action = $logger->getAction();
            $this->setLogger($oldLogger);
        } catch (\Exception $ex) {
            // Wouldn't it be cool if PHP had a try..catch..finally?
            // Fake it to ensure logger gets put back.
            $this->setLogger($oldLogger);
            throw $ex;
        }
        return $action;
    }

    public function serializeInstrumentation($arguments) {
        return $this->serialize($arguments, "serializeInstrumentation", "instrument", array('subject', 'verb', 'level', 'customFields', 'duration', 'timestamp', 'hostname'));
    }

    public function serializeAction($arguments) {
        return $this->serialize($arguments, "serializeAction", "record", array('subject', 'verb', 'object', 'timestamp', 'hostname'));
    }

    /**
     * Public only so I can unit test it.
     */
    function getConfigurationValue($key) {
        $map = $this->getMapFor($this->outputFieldToAppliesTo[$key]);
        return array_key_exists($key, $map) ? $map[$key] : null;
    }

    public function getRequiredFieldsState($appliesToMask) {
        $result = array();
        foreach ($this->fieldDefinitions as $fieldName => &$meta) {
            list( , , $isRequired, $appliesTo, $outputFields) = $meta;
            if ($isRequired === self::REQUIRED && $this->isApplicable($appliesToMask, $appliesTo)) {
                array_push($result, $this->getConfigurationState($fieldName, $outputFields));
            }
        }
        return implode(", ", $result);
    }

    //This simply checks that the first value mapped from "fieldName" is in the outputFields.
    //Basically this is just checking to make sure some value was mapped as "output" for a required input.  Since
    //I removed the member variables for the configuration this is the best I can do.
    private function getConfigurationState($fieldName, $outputFields) {
        if(count($outputFields) > 0){
            $outputFieldName = current(array_keys($outputFields));
            return "$fieldName is " . (!array_key_exists($outputFieldName, $this->getMapFor($this->outputFieldToAppliesTo[$outputFieldName])) ? "unset" : "OK");
        }

        return "$fieldName is not a field";
    }

    // This exists primarily for unit testing.
    public function setLogger(ActionLoggerBase $logger) {
        $this->logger = $logger;
    }

    // This is public only for unit testing.
    public function getLogger() {
        if ($this->logger === null) {
            $this->logger = $this->createLogger();
        }
        return $this->logger;
    }

    private function createLogger() {
        if ($this->debugMode) {
            return new NoOpActionLogger();
        }
        if ($this->logDirectory !== null) {
            return new FileSystemActionLogger($this->logDirectory);
        }

        return new DqaActionLogger();
    }

    private function isValidInstrumentationLevel($level) {
        $validIntrumentationLevels = array(InstrumentationLevel::DEBUG, InstrumentationLevel::INFO, InstrumentationLevel::ERROR, InstrumentationLevel::FATAL);
        if (!in_array($level, $validIntrumentationLevels, true)) {
            $this->reportError("'$level' is not a valid instrumentation level.  The valid choices are: " . implode(", ", $validIntrumentationLevels));
            return false;
        }
        return true;
    }

    /**
    * This function checks to see if a value is an integral value of 32-bit or 64-bit proportions. PHP
    * treats integral values that exceed the system bit-length as floats, hence the checking that is
    * more complicated than it should have to be.
    */
    private static function isIntOrLong($value) {
        // Check is_int first. If that passes great. (On a 32-bit build of PHP, is_int maxes out at 2^32-1.
        // On a 64-bit build, is_int() maxes out at 2^64-1.)  If not, then we need to check float and see if the
        // float value is actually an integral value. The floor and ceil calls are used in case PHP's internal
        // representation of floats causes wackiness in the value (i.e. 100 is 99.99999999999998). See:
        // http://www.php.net/manual/en/language.types.float.php
        return is_int($value) || (is_float($value) && ($value === floor($value) || $value === ceil($value))
                    && $value >= self::IntegralMin && $value <= self::IntegralMax);
    }

    // The min/max are based on 14 digit precision. This behaves differently between PHP versions and
    // potentially platforms on which it's being run, but 14 seems to be the lowest max precision.
    const IntegralMax = 100000000000000;
    const IntegralMin = -100000000000000;

    /**
     * Produces a string containing an instrumentation log line in JSON form.
     * Assumes that that $subject, $verb, and $level have been rationalized/validated.  All must be strings
     * Only logs $duration if it's a positive value.
     * Provides a value for $timestamp or $hostname if it is null or otherwise nonsensical.
     * Prefixes the keys of customFields with $ or # depending on data type.
     */
    private function serializeInstrumentationImpl($subject, $verb, $level, $customFields, $duration, $timestamp, $hostname) {
        $log = array(
            'datetime' => $this->validateTimestamp($timestamp),
            'level' => $level,
            'subject' => strtolower($subject),
            'verb' => strtolower($verb),
            'hostname' => $this->validateHostname($hostname)
        );
        if (($duration = (integer)$duration) > 0) {
            $log['duration'] = $duration;
        }
        $standardFields = array_merge($log, $this->configMap, $this->instrumentationOnlyConfigMap);
        $customFields = $this->convertInstrumentationCustomFields($customFields);
        return $this->encodeJson($standardFields, $customFields);
    }

    /**
     * Removes the bad custom field values and writes the good ones to a new array with the appropriate prefix for the type.
     */
    private function convertInstrumentationCustomFields(&$customFields) {
        $log = array();
        if (is_array($customFields)) {
            foreach ($customFields as $key => $value) {
                if (!preg_match(ActionCapture::CUSTOM_FIELD_NAME_VALID_CHARACTERS, $key)) {
                    $this->reportError(
                        "Invalid custom field name. " .
                        "The name '$key' did not conform to ActionCapture::CUSTOM_FIELD_NAME_VALID_CHARACTERS regex: " .  ActionCapture::CUSTOM_FIELD_NAME_VALID_CHARACTERS
                    );
                }
                else if (is_null($value)) { /* No op. Ignore nulls. */ }
                else if (is_string($value)) $log[self::StringCustomFieldPrefix . $key] = $value;
                else if (self::isIntOrLong($value)) $log[self::IntegralCustomFieldPrefix . $key] = $value;
                else if ($this->debugMode) {
                    if(is_numeric($value)) {
                        $this->reportError("Custom field '$key' is an unaccepted number. Only integrals between "
                            . self::IntegralMin . " and " . self::IntegralMax . " allowed.");
                    }
                    else {
                        $this->reportError("Invalid custom field type. The value for '$key' was '$value', which was a "
                            . gettype($value));
                    }
                }
            }
        }
        return $log;
    }

    const StringCustomFieldPrefix = "$";
    const IntegralCustomFieldPrefix = "#";

    private function serializeActionImpl($subject, $verb, $object, $timestamp, $hostname) {
        $standardFields = array_merge(array(
            'datetime' => $this->validateTimestamp($timestamp),
            'recordedby' => 'server',
            'subject' => strtolower($subject),
            'verb' => strtolower($verb),
            'hostname' => $this->validateHostname($hostname)
        ), $this->configMap, $this->complianceOnlyConfigMap);
        $truncatableFields = array(
            'object' => $object
        );
        return $this->encodeJson($standardFields, $truncatableFields);
    }

    private function encodeJson($standardFields, $truncatableFields) {
        $json = $this->getLogger()->encodeJson($standardFields, $truncatableFields);
        if ($this->getLogger() instanceof LimitedSizeLoggerBase && strlen($json) > $this->getLogger()->maxJsonLength()) {
            return $this->makeErrorForExcessivelyLongLog($standardFields, $truncatableFields);
        }
        return $json;
    }

    /**
     * If we can't truncate the log sufficiently to fit within the logger's
     * constraints, then we'll try to return a warning instead so that we can
     * determine bad things are happening.
     */
    private function makeErrorForExcessivelyLongLog($standardFields, $truncatableFields){
        $errorTruncatableFields = array(
            self::StringCustomFieldPrefix . 'oldLevel' => array_key_exists('level', $standardFields) ? "{$standardFields['level']}" : "(unset)",
            self::StringCustomFieldPrefix . 'oldSubject' => "{$standardFields['subject']}",
            self::StringCustomFieldPrefix . 'oldVerb' => "{$standardFields['verb']}",
            self::StringCustomFieldPrefix . 'oldCustomKeys' => implode(",", array_keys($truncatableFields)),
        );
        $standardFields['level'] = InstrumentationLevel::INFO;
        $standardFields['subject'] = 'excess-size';
        $standardFields['verb'] = 'prevented-encoding';
        $errorJson = $this->getLogger()->encodeJson($standardFields, $errorTruncatableFields);
        if (strlen($errorJson) > $this->getLogger()->maxJsonLength()) {
            return json_encode(array('totally failed' => "to truncate to {$this->getLogger()->maxJsonLength()}"));
        }
        else {
            return $errorJson;
        }
    }

    private function runTransform($value, $transform) {
        if ($transform === self::NO_TRANSFORM) {
            return $value;
        }
        if (is_string($transform)) {
            return $this->$transform($value);
        }
        throw new \Exception("Unknown transform type: " . (string)$transform);
    }

    private function validateSubjectOrVerb($key, $value) {
        $regex = '/^' . ActionCapture::SUBJECT_VALID_CHARACTERS . '{1,' . ActionCapture::SUBJECT_MAX_LENGTH .'}$/';
        if (!preg_match($regex, $value)) {
            $this->reportError("The $key must match $regex.  The value, '$value', didn't match.");
            return false;
        }
        return true;
    }

    private function getCommonUserIDHash($commonUserID) {
        $billingID = $this->getConfigurationValue('billinggroupid');
        $providerID = $this->getConfigurationValue('providerid');
        if ($billingID !== NULL  &&  $commonUserID !== NULL  &&  $providerID !== NULL) {
            // if we have all three pieces we need
            $md5 = md5("{$commonUserID}****{$providerID}{$billingID}SuperStrongSmellingSalts");
            // we've used the info.  now make sure we don't serialize it.
            $map = &$this->getMapFor(self::APPLIES_TO_BOTH);
            unset($map['providerid']);
            return $md5;
        } else {
            return NULL;
        }
    }

    function filterUserAgent($value) {
        return trim($this->filterToPrintableValue($value, ActionCapture::USER_AGENT_MAX_LENGTH));
    }

    private function filterObject($value) {
        return $this->filterToPrintableValue($value, ActionCapture::OBJECT_MAX_LENGTH);
    }

    private function filterToPrintableValue($value, $maxLength) {
        return preg_replace('@[\x00-\x1F\x7F]+@', ' ', $this->truncateUnicodeWithEllipsis($value, $maxLength));
    }

    /**
     * A truncated string will have this appended.
     */
    const Ellipsis = "...";

    /**
     * Public only so I can unit test it.
     */
    function truncateWithEllipsis($string, $maxLength) {
        if (strlen($string) <= $maxLength) {
            return $string;
        }
        return substr($string, 0, $maxLength - strlen(self::Ellipsis)) . self::Ellipsis;
    }

    /**
     * Truncates $string to at most $maxLength unicode characters, not a fixed number of bytes.
     *
     *  If I use the naive truncation with a string that can contain Unicode, it's possible I'll split a character.
     *  That would be suboptimal.  The various PHP environments this code will be running in don't have a consistent
     *  function to do UTF-8 string length and/or substr, so I invented the kooky thing below.
     *
     *  Warning: $maxLength is measured in unicode characters, not in bytes.
     *  Warning: This function won't work well for small values of $maxLength, say less than 4.
     */
    private function truncateUnicodeWithEllipsis($string, $maxLength) {
        if (strlen($string) <= $maxLength) {
            // Cheater case to save the cost of a regex when it's obviously short enough.
            return $string;
        }
        $ellipsisLength = strlen(self::Ellipsis);
        $lengthMinusEllipsis = $maxLength - $ellipsisLength;
        // The trailing 'u' on the regular expression means that the bytes should be treated as UTF-8 characters
        // The general theory on the regex is that if the last group matches anything, it means $string is too long.
        // The middle group exists only to remove $ellipsisLength many characters if we do need to truncate.
        // The first group is what we want to keep if we have to truncate.
        preg_match("@^(.{0,$lengthMinusEllipsis})(?:.{0,$ellipsisLength})(.?)@u", $string, $matches);
        if (0 === strlen($matches[2])) {
            return $string;
        }
        return $matches[1] . self::Ellipsis;
    }

    // Values for the isRequired field.
    const REQUIRED = true;
    const OPTIONAL = false;

    const APPLIES_TO_COMPLIANCE = 1;
    const APPLIES_TO_INSTRUMENTATION = 2;
    const APPLIES_TO_BOTH = 3; // i.e. (APPLIES_TO_COMPLIANCE | APPLIES_TO_INSTRUMENTATION);

    const NO_TRANSFORM = null;

    // This array is of the form:
    // 'fieldName' => array(
    //    validCharactersInRegexForm,
    //    maxLength,
    //    isRequired,
    //    appliesTo,
    //    array('jsonOutputField' => 'transformFunction'))
    public $fieldDefinitions = array(
        'billingID' => array(
            ActionCapture::BILLING_ID_VALID_CHARACTERS,
            ActionCapture::BILLING_ID_MAX_LENGTH,
            self::REQUIRED,
            self::APPLIES_TO_BOTH,
            array('billinggroupid' => self::NO_TRANSFORM)),
        'instanceID' => array(
            ActionCapture::INSTANCE_ID_VALID_CHARACTERS,
            ActionCapture::INSTANCE_ID_MAX_LENGTH,
            self::REQUIRED,
            self::APPLIES_TO_BOTH,
            array('instanceid' => self::NO_TRANSFORM)),
        'sessionID' => array(
            ActionCapture::SESSION_ID_VALID_CHARACTERS,
            ActionCapture::SESSION_ID_MAX_LENGTH,
            self::REQUIRED, // Technically sessionID is optional when creating a snippet, but required for recording an action.
            self::APPLIES_TO_BOTH,
            array('sessionid' => self::NO_TRANSFORM)),
        'altSessionID' => array(
            ActionCapture::SESSION_ID_VALID_CHARACTERS,
            ActionCapture::SESSION_ID_MAX_LENGTH,
            self::OPTIONAL,
            self::APPLIES_TO_COMPLIANCE,
            array('altsessionid' => self::NO_TRANSFORM)),
        'referrer' => array(
            ActionCapture::REFERRER_VALID_CHARACTERS,
            PHP_INT_MAX, //The truncation should be done as part of the transforms
            self::OPTIONAL,
            self::APPLIES_TO_COMPLIANCE,
            array('referrer' => 'getHostFromUrl','referrerhash' => 'getHashedUrl')),
        'cleanUrl' => array(
            ActionCapture::URL_VALID_CHARACTERS,
            ActionCapture::CLEAN_URL_MAX_LENGTH,
            self::OPTIONAL,
            self::APPLIES_TO_BOTH,
            array('url' => 'stripProtocol')),
        'url' => array(
            ActionCapture::URL_VALID_CHARACTERS,
            PHP_INT_MAX, //This is because the input shouldn't be truncated at all. It's up to the transforms to do the right thing.
            self::OPTIONAL,
            self::APPLIES_TO_BOTH,
            array('url' => 'getHostnameIfUrlUnset', 'urlhash' => 'getHashedUrl', 'altsessionid' => 'getAltSessionIDFromUrl')),
        'userAgent' => array(
            ActionCapture::USER_AGENT_VALID_CHARACTERS,
            ActionCapture::USER_AGENT_MAX_LENGTH,
            self::OPTIONAL,
            self::APPLIES_TO_BOTH,
            //Filter to replace unprintable chars with spaces
            array('useragent' => 'filterUserAgent')),
        'productFamily' => array(
            ActionCapture::PRODUCT_FAMILY_VALID_CHARACTERS,
            ActionCapture::PRODUCT_FAMILY_MAX_LENGTH,
            self::REQUIRED,
            self::APPLIES_TO_BOTH,
            array('productfamily' => self::NO_TRANSFORM)),
        'product' => array(
            ActionCapture::PRODUCT_VALID_CHARACTERS,
            ActionCapture::PRODUCT_MAX_LENGTH,
            self::REQUIRED,
            self::APPLIES_TO_BOTH,
            array('product' => self::NO_TRANSFORM)),
        'productVersion' => array(
            ActionCapture::PRODUCT_VERSION_VALID_CHARACTERS,
            ActionCapture::PRODUCT_VERSION_MAX_LENGTH,
            self::REQUIRED,
            self::APPLIES_TO_BOTH,
            array('productversion' => self::NO_TRANSFORM)),
        'remoteIP' => array(
            ActionCapture::REMOTE_IP_VALID_CHARACTERS,
            ActionCapture::REMOTE_IP_MAX_LENGTH,
            self::REQUIRED,
            self::APPLIES_TO_BOTH,
            array('anonymizedip' => 'getAnonymizedIP', 'hashedip' => 'getHashedIP')),
        'providerID' => array(
            ActionCapture::PROVIDER_ID_VALID_CHARACTERS,
            PHP_INT_MAX, //This is because the input shouldn't be truncated at all. It's up to the transforms to do the right thing.
            self::OPTIONAL,
            self::APPLIES_TO_BOTH,
            array('providerid' => self::NO_TRANSFORM)), // providerID will be used in getCommonUserIDHash, and then discarded
        'commonUserID' => array(
            ActionCapture::COMMON_USER_ID_VALID_CHARACTERS,
            PHP_INT_MAX, //This is because the input shouldn't be truncated at all. It's up to the transforms to do the right thing.
            self::OPTIONAL,
            self::APPLIES_TO_BOTH,
            array('commonuserid' => 'getCommonUserIDHash')),
        'operatingSystem' => array(
            ActionCapture::OPERATING_SYSTEM_VALID_CHARACTERS,
            ActionCapture::OPERATING_SYSTEM_MAX_LENGTH,
            self::OPTIONAL,
            self::APPLIES_TO_INSTRUMENTATION,
            array('operatingsystem' => self::NO_TRANSFORM)),
        'operatingSystemVersion' => array(
            ActionCapture::OPERATING_SYSTEM_VERSION_VALID_CHARACTERS,
            ActionCapture::OPERATING_SYSTEM_VERSION_MAX_LENGTH,
            self::OPTIONAL,
            self::APPLIES_TO_INSTRUMENTATION,
            array('operatingsystemversion' => self::NO_TRANSFORM)),
        'browser' => array(
            ActionCapture::BROWSER_VALID_CHARACTERS,
            ActionCapture::BROWSER_MAX_LENGTH,
            self::OPTIONAL,
            self::APPLIES_TO_INSTRUMENTATION,
            array('browser' => self::NO_TRANSFORM)),
        'browserVersion' => array(
            ActionCapture::BROWSER_VERSION_VALID_CHARACTERS,
            ActionCapture::BROWSER_VERSION_MAX_LENGTH,
            self::OPTIONAL,
            self::APPLIES_TO_INSTRUMENTATION,
            array('browserversion' => self::NO_TRANSFORM)),
    );

    //All initialization of things in the fieldDefinitions array must be done here. It's used by both
    //initialize and serializeAction.
    private function handleField($key, $value) {
        list($validCharacters, $maxLength, $isRequired, $appliesTo, $outputFields) = $this->fieldDefinitions[$key];
        $map = &$this->getMapFor($appliesTo);
        if ($this->debugMode) {
            if ($key === 'sessionID') {
                // Session ID is a weird case where we merrily accept and truncate values that are too long by design.
                // Really, we don't want to report errors if it's too long because that's become an expected situation.
                $value = substr($value, 0, ActionCapture::SESSION_ID_MAX_LENGTH);
            }
            if (preg_match($this->makeMatchingRegex($validCharacters, $isRequired, $maxLength), $value ? $value : '')) {
                if($value !== null){
                    foreach ($outputFields as $jsonKey => $transform) {
                        $transformedValue = $this->runTransform($value, $transform);
                        if($transformedValue !== null){
                            $map[$jsonKey] = $transformedValue;
                        }
                    }
                }
            }
            else {
                $this->reportError("The value passed for ActionCapture configuration $key ('$value') does not meet the requirement of containing only characters in /$validCharacters/ with a maximum length of $maxLength.");
            }
        }
        else {
            $filteredValue = $this->truncateWithEllipsis(preg_replace($this->makeFilteringRegex($validCharacters), '', $value), $maxLength);
            if ($filteredValue !== "" || $key === 'remoteIP') { // an empty remoteIP should be accepted, and it will get turned into the loop back address later on
                foreach ($outputFields as $jsonKey => $transform) {
                    $transformedValue = $this->runTransform($filteredValue, $transform);
                    if ($transformedValue !== null) {
                        $map[$jsonKey] = $transformedValue;
                    }
                }
            }
        }
    }

    private function handleLogDirectory($key, $value) {
        if (is_dir($value) && is_writable($value)) {
            $this->logDirectory = $value;
        }
        else {
            $this->reportError("The value given for ActionCapture configuration $key ('$value') does not specify a location that is writable by the current process.");
        }
    }

    private function getAltSessionIDFromUrl($url) {
        if (preg_match("/[?&]__altsessionid=([^&]*)/", $url, $matches) > 0) {
            return $this->truncateWithEllipsis(preg_replace($this->makeFilteringRegex(ActionCapture::SESSION_ID_VALID_CHARACTERS), '', $matches[1]), ActionCapture::SESSION_ID_MAX_LENGTH);
        }
        return null;
    }

    /**
     * Public only so I can unit test it.
     */
    function stripProtocol($url) {
        foreach (array("http://", "https://") as $proto) {
            if (0 === strncmp($proto, $url, strlen($proto))) {
                $stripped = substr($url, strpos($url, "/") + 2);
                return $stripped === false ? "" : $stripped;
            }
        }
        return $url;
    }

    private function reportError($message) {
        if ($this->debugMode) {
            throw new \Exception($message);
        }
    }

    private function makeMatchingRegex($validCharacters, $isRequired, $maxLength) {
        if ($this->debugMode) {
            assert(strlen($validCharacters) > 2);
            assert(substr($validCharacters, 0, 1) === '[');
            assert(substr($validCharacters, -1) === ']');
        }

        $minRepeat = $isRequired ? 1 : 0;
        $maxRepeat = $maxLength === PHP_INT_MAX ? "" : $maxLength;
        return sprintf('@^%s{%s,%s}$@D', $validCharacters, $minRepeat, $maxRepeat);
    }

    private function makeFilteringRegex($validCharacters) {
        if ($this->debugMode) {
            assert(strlen($validCharacters) > 2);
            assert(substr($validCharacters, 0, 1) === '[');
            assert(substr($validCharacters, -1) === ']');
        }
        return '@[^' . substr($validCharacters, 1) . '+@D';
    }

    /**
     * public only so I can use it in unit tests.
     */
    public function wasPhpBuiltWithIPv6() {
        return
            // If it were done correctly, this should be sufficient.
            function_exists('inet_pton') && function_exists('inet_ntop') &&
            // ...but I wouldn't be writing this if it were done correctly.
            // Some bright spark built RightNow's 5.3.2 PHP with the IPv6
            // functions but without letting them include calls to the IPv6
            // kernel functions, so they fail when given an IPv6 address.
            @inet_pton('::F00:1') !== false;
    }

    /**
     * public only so I can unit test it.
     */
    public function getAnonymizedIP($ip) {
        list($isIPv6, $canonicalAddress) = $this->getCanonicalIP($ip);
        if ($isIPv6) return $this->getAnonymizedIPv6($canonicalAddress);
        else return $this->getAnonymizedIPWithoutIPv6($canonicalAddress);
    }

    /**
     * This really means "without IPv6 functions".
     */
    private function getAnonymizedIPWithoutIPv6($ip) {
        if (false !== ($index = strrpos($ip, "."))) {
            // IPv4 style
            // Tear off the last dot and octet. "1.2.3.4" will look like "1.2.3" afterward
            return substr($ip, 0, $index);
        }
        if (false !== ($index = strrpos($ip, ":"))) {
            // IPv6 style
            return $this->getAnonymizedIPv6($ip);
        }
        // OK, so the IP address we already accepted doesn't match our expectations. Erm.
        // We'll punt and default to local loopback address.
        return "127.0.0";
    }

    private function getAnonymizedIPv6($canonical) {
        // If the address ends like ":1" or ":12", then we want to terminate it at (and including) the colon.
        // If the address ends like ":123" or ":1234", then we want to cut off the last 2 characters.
        $colonIndex = strrpos($canonical, ':');
        return substr($canonical, 0, max(strlen($canonical) - 2, $colonIndex + 1));
    }

    /**
     * @returns array($isIPv6, $canonicalAddress)
     */
    private function getCanonicalIP($ip) {
        if (!$this->wasPhpBuiltWithIPv6()) {
            return array(false, $ip);
        }
        $bytes = @inet_pton($ip);
        if ($bytes === false || strlen($bytes) !== 16) {
            // If the address failed to parse or if it wasn't an IPv6 address, use the standard handling.
            return array(false, $ip);
        }
        $canonical = @inet_ntop($bytes);
        if (false === $canonical) {
            return array(false, $ip);
        }
        if (substr($bytes, 0, 12) === "\0\0\0\0\0\0\0\0\0\0\xFF\xFF") {
            // It's hybrid dual stack IPv4/IPv6.  Convert to IPv4 and anonymize.
            // $canonical will look like '::ffff:127.0.0.1', so just grab the IPv4 looking thing at the end.
            $colonIndex = strrpos($canonical, ':');
            return array(false, substr($canonical, $colonIndex + 1));
        }
        return array(true, $canonical);
    }

    /**
     * public only so I can unit test it.
     */
    public function getHashedIP($ip) {
        list(, $canonical) = $this->getCanonicalIP($ip);
        return md5($canonical . $this->getConfigurationValue('billinggroupid') . 'SalishSmokedSeaSalt');
    }

    /**
     * This function exists to handle the weird case of the input possibly containing a 'url', but not a 'cleanUrl'.
     * In that case we'd like to fallback to setting the output URL based on the input URL rather than the clean URL, which we'd prefer.
     */
    private function getHostnameIfUrlUnset($url) {
        if (array_key_exists('url', $this->getMapFor($this->outputFieldToAppliesTo['url']))) {
            return null; // i.e., do not overwrite the existing value which was set by the 'cleanUrl' handler.
        }
        return $this->getHostFromUrl($url);
    }

    private function getHashedUrl($url) {
        return substr(md5($this->stripProtocol($url) . $this->getConfigurationValue('billinggroupid') . 'SweatySailorSeaSalt'), 0, ActionCapture::URL_HASH_MAX_LENGTH);
    }

    /**
     * Public only so I can unit test it.
     */
    function getHostFromUrl($url) {
        if(preg_match(ActionCapture::HOST_REGEX, $this->stripProtocol($url), $matches) > 0){
            //If we've chopped to just a hostname, we still make sure it's 150 characters
            $host = $this->truncateWithEllipsis(count($matches) == 3 ? $matches[2] : $matches[1], ActionCapture::REFERRER_HOST_MAX_LENGTH);
            return $host == false ? "" : $host;
        }
        return "";
    }

    private $configMap = array();
    private $complianceOnlyConfigMap = array();
    private $instrumentationOnlyConfigMap = array();
    private $logger = null;
    private $debugMode = false;
    private $logDirectory = null;
}

/**
 * @private
 * Base class for actually writing out actions.
 */
abstract class ActionLoggerBase {
    public function encodeJson($standardFields, $truncatableFields) {
        return json_encode(array_merge($standardFields, $truncatableFields));
    }
    public abstract function record($serializedAction);
}

/**
 * @private
 * A logger which caps the size of the logged message by truncating fields.
 */
abstract class LimitedSizeLoggerBase extends ActionLoggerBase {
    public abstract function maxJsonLength();

    public function encodeJson($standardFields, $truncatableFields) {
        return $this->convertToJson($standardFields, $truncatableFields);
    }

    // I'm only going to have convertToJson() try this many times before giving up because I don't want logging to turn into a huge time sink for the calling process.
    const MaxJsonConversionAttempts = 10;

    /**
     * Converts the standard and custom fields to a JSON representation.
     * Ruthlessly truncates and removes custom fields in order to shorten the output to fit within the constraints of the transport.
     *
     * Algorithm:
     * 1. JSON encode all the fields. Figure out how much that exceeds the maximum by. If it fits, we're done.
     * 2. For all string fields which are longer than 50 bytes, proportionally truncate the portion above 50 bytes.
     * 3. If the total sum of the bytes available for truncation couldn't solve the problem, totally drop a field.
     * 4. If we've been this way fewer than 10 times, go to 1. Otherwise, give up and return the current state of things, which will probably result in the data being corrupted/dropped.
     *
     * - Fields containing numeric data will not be truncated because truncating a number totally destroys its meaning. They will only be dropped.
     * - Hopefully any fields which contain an enum-like value will have fewer than 50 bytes.
     *
     * For example, suppose you have custom fields like:
     *   array(
     *       "a" => 8 * "a",
     *       "b" => 98 * "b",
     *       "c" => 198 * "c"
     *   )
     *   And you need to get rid of 80 bytes.
     *   "a" can't be truncated because it's shorter than 50 bytes.
     *   "b" can give up 50.
     *   "c" can give up 150.
     *   So "b" loses 20 and "c" loses 60.
     *   (Technically "b" and "c" lose 3 more each to allow for the addition of an ellipsis.)
     */
    protected function convertToJson($standardFields, &$customFields, $remainingAttempts = self::MaxJsonConversionAttempts) {
        $json = json_encode(array_merge($standardFields, $customFields));
        $excess = strlen($json) - $this->maxJsonLength();
        if ($remainingAttempts <= 0 || $excess <= 0 ) {
            // I'd like to report an error if we've run out of attempts, but the logger doesn't have a mechanism to log errors, ironically.
            return $json;
        }
        $customFields = self::proportionallyTruncateStringFields($customFields, $excess);
        return $this->convertToJson($standardFields, $customFields, $remainingAttempts - 1);
    }

    /**
     * Strings of this length or shorter in json encoded length won't be truncated to make all the custom fields fit.
     */
    const MinStringFieldLength = 50;

    /**
     * This is public only to enable unit testing. It's intended to be used only by convertToJson.
     */
    static function proportionallyTruncateStringFields(&$fields, $excess) {
        $fieldsEligibleForTruncation = array();
        $bytesEligibleForTruncation = 0;
        foreach ($fields as $key => $value) {
            if (is_string($value)) {
                $json = json_encode($value);
                $jsonEncodedStrlen = strlen($json);
                if ($jsonEncodedStrlen > self::MinStringFieldLength) {
                    $fieldsEligibleForTruncation[$key] = $json;
                    $bytesEligibleForTruncation = $bytesEligibleForTruncation + ($jsonEncodedStrlen - self::MinStringFieldLength);
                }
            }
        }
        $totalBytesTruncated = 0;
        if ($bytesEligibleForTruncation > 0) {
            $totalBytesToRemove = min($bytesEligibleForTruncation, $excess);
            foreach ($fieldsEligibleForTruncation as $key => $json) {
                $jsonEncodedStrlen = strlen($json);
                $value = $fields[$key];
                $eligibleBytesInThisValue = $jsonEncodedStrlen - self::MinStringFieldLength;
                $toRemove = ceil($totalBytesToRemove * ($eligibleBytesInThisValue / $bytesEligibleForTruncation));
                $targetLength = max(self::MinStringFieldLength, $jsonEncodedStrlen - $toRemove);
                if ($targetLength < $jsonEncodedStrlen) {
                    // Because truncateJsonEncodedWithEllipsis() sometimes removes more than we ask, $totalBytesTruncated represents the minimum removed.
                    // I could JSON encode the string afterward to figure out its new length in order to determine how much was actually cut, but I don't
                    // think it's worth the performance hit.
                    $totalBytesTruncated = $totalBytesTruncated + max(0, $jsonEncodedStrlen - $targetLength);
                    $fields[$key] = self::truncateJsonEncodedWithEllipsis($value, $targetLength, $json);
                }
            }
        }
        if ($totalBytesTruncated < $excess) {
            // If we were unable to truncate enough to fix the problem, drop a field.
            return self::dropField($fields);
        }
        return $fields;
    }

    private static function dropField(&$fields) {
        if (!is_array($fields) || count($fields) <= 1) {
            return array();
        }
        // This provides an approximation of the longest field because it's based on the non-JSON-encoded length.
        // It's good enough without the hit of re-JSON encoding.
        // My expectation in general is that by the time we get here, all string fields will be truncated to MinStringFieldLength
        // or shorter, so there won't be much difference in length amongst the fields.
        $longestKey = null;
        $longest = -1;
        foreach ($fields as $key => $value) {
            $keyValueLength = strlen($key) + strlen($value);
            if ($keyValueLength > $longest) {
                $longestKey = $key;
                $longest = $keyValueLength;
            }
        }
        unset($fields[$longestKey]);
        return $fields;
    }

    /**
     * A control character (0x01-0x1F) becomes a 6 bytes (\uXXXX) in PHP's JSON. That's the worst case.
     */
    const MaxJsonEncodingExpansionFactor = 6;

    /**
     * The math for truncating a string based on its JSON encoded length needs to take into account the wrapping double quotes.
     */
    const WrappingDoubleQuotesLength = 2;

    /**
     * Truncates $string so that its JSON encoded length (including enclosing double quotes) is no more than $maxLength bytes.
     * If truncation divides a JSON escape sequence, the return value will be shorter than $maxLength.
     * @param $string Should not be JSON encoded.
     * @param $json You can optionally specify an already JSON encoded copy of $string.
     * @return non-JSON-encoded string short enough to fit in $maxLength when encoded.
     */
    static function truncateJsonEncodedWithEllipsis($string, $maxLength, $json = null) {
        if ($json === null) {
            if (strlen($string) * self::MaxJsonEncodingExpansionFactor + self::WrappingDoubleQuotesLength <= $maxLength) {
                return $string;
            }
            // Fill in the optional parameter if it was unset.
            $json = json_encode($string);
        }
        if ($json === null) {
            // json_encode() returns null if $string contains an invalid UTF-8 sequence. I hate null.
            return "";
        }
        if (strlen($json) <= $maxLength) {
            return $string;
        }
        $withRoomForEllipsis = max(0, $maxLength - strlen(ActionCaptureImpl::Ellipsis));
        // -1 for the closing double quote we have to add after we slice off the excess.
        for ($chop = $withRoomForEllipsis - 1; $chop > self::WrappingDoubleQuotesLength; --$chop) {
            $choppedString = json_decode(substr($json, 0, $chop) . '"');
            // json_decode() returns null if $string contains an invalid JSON escape sequence.
            // I'm counting on json_decode() returning null if I chopped it in the middle of a JSON escape sequence.
            // (Of course json_decode() also returns null when decoding the string "null", but I'm not going to worry about that.
            // There are some busted corners of PHP's API you just can't account for. Also I'm pretty sure all of the code above
            // should prevent a null from getting here.)
            if ($choppedString !== null) {
                return $choppedString . ActionCaptureImpl::Ellipsis;
            }
        }
        return "";
    }
}

/**
 * @private
 * I wanted to push more of the shared bits of the derived classes into here except I want one to report errors and the
 * other to suppress them.  I don't know of a good way to do that in PHP without sticking '@' on the front of method
 * calls.
 */
abstract class BaseFileSystemActionLogger extends ActionLoggerBase {
    protected $logDirectory;

    public function __construct($logDirectory) {
        $this->logDirectory = $logDirectory;
    }

    // We create the log file with a temporary name and then rename it to the real name once we're done with it to avoid
    // having log shipper pick up the file while we're in the middle of writing it.
    protected function getTempLogPath() {
        return $this->logDirectory . '/' . gmdate('Y-m-d\TH:i') . '.' . microtime(true) . '.' . getmypid() . '.log' . $this->tempExtension;
    }

    protected function getLogPath($tempPath) {
        assert(substr($tempPath, -strlen($this->tempExtension)) === $this->tempExtension);
        return substr($tempPath, 0, -strlen($this->tempExtension));
    }

    private $tempExtension = ".tmp";
}
/**
 * @private
 * Writes actions to a file.
 *
 * This class is expected to be used in an environment which is user interactive.
 * Consequently, it does its best to suppress errors, unlike BatchFileSystemActionLogger.
 */
class FileSystemActionLogger extends BaseFileSystemActionLogger {
    private $buffer = "";

    public function __construct($logDirectory) {
        parent::__construct($logDirectory);
    }

    public function __destruct() {
        $this->flush();
    }

    public function record($serializedAction) {
        if (strlen($serializedAction) > 0) {
            $this->buffer .= $serializedAction;
            $this->buffer .= "\n";
            if (strlen($this->buffer) > 4 * 1024 * 1024) {
                // To avoid consuming too much memory, flush if we have buffered up a lot.
                $this->flush();
            }
        }
    }

    // I made this public only so I could unit test more easily.
    public function flush() {
        if (strlen($this->buffer) > 0) {
            // Make sure that the logs can be deleted once they've been shipped by setting the umask.
            $oldUmask = umask(0000);
            if (@is_dir($this->logDirectory) || @mkdir($this->logDirectory)) {
                $tempPath = $this->getTempLogPath();
                @file_put_contents($tempPath, $this->buffer, FILE_APPEND);
                @rename($tempPath, $this->getLogPath($tempPath));
                $this->buffer = "";
            }
            umask($oldUmask);
        }
    }
}

/**
 * This class exists because IntentGuide does a batch conversion from their logs to ACS logs.  They're manually calling ActionCapture::serializeAction()
 * and record() for each action.  Originally they were using FileSystemActionLogger; this class is superior for their usage because it keeps the file
 * handle open between calls to record and because it renames the file to have a log extension only when it's done so that ops' log shipping infrastructure
 * doesn't pick it up in mid-write.
 *
 * This class is expected to be used in an environment which is not user interactive.
 * Consequently, it reports errors, unlike FileSystemActionLogger.
 */
class BatchFileSystemActionLogger extends BaseFileSystemActionLogger {
    private $tempFileName;
    private $fileHandle = null;

    public function __construct($logDirectory) {
        parent::__construct($logDirectory);
        $this->openFile();
    }

    private function openFile() {
        assert($this->fileHandle === null);
        // Make sure that the logs can be deleted once they've been shipped by setting the umask.
        $oldUmask = umask(0000);
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory);
        }
        $this->tempFileName = $this->getTempLogPath();
        $this->fileHandle = fopen($this->tempFileName, "a");
        umask($oldUmask);
    }

    public function __destruct() {
        $this->close();
    }

    public function record($serializedAction) {
        if (strlen($serializedAction) > 0) {
            fwrite($this->fileHandle, $serializedAction);
            fwrite($this->fileHandle, "\n");
        }
    }

    // I made this public only so I could unit test more easily.
    public function flush() {
        fflush($this->filehandle);
    }

    public function close() {
        if ($this->fileHandle !== null) {
            fclose($this->fileHandle);
            rename($this->tempFileName, $this->getLogPath($this->tempFileName));
            $this->fileHandle = null;
        }
    }
}

/**
 * @private
 * Writes actions to DQA, which is CX's proprietary queueing infrastructure.
 */
class DqaActionLogger extends LimitedSizeLoggerBase  {
    // Because our typical transport includes UDP to minirsyslogd, we have to limit our transmitted JSON to about 8131 bytes.
    // However there seems to be some spooky variability around that, so I'll pick a slightly smaller number.
    // @See https://quartz.us.oracle.com/shelf/docs/Projects/ACS/Instrumentation/Product%20Instrumentation%20Overview.html#maximumdatasize
    public function maxJsonLength() {
        return 8100;
    }

    public function record($serializedAction) {
        // I defined DQA_ACTION_CAPTURE in common/include/libutil/util.h
        // I'm not going to use that define here because 1) DQA defines
        // with values >= 1000 don't automatically generate PHP defines
        // and 2) this code wouldn't necessarily have access to it anyway.
        // Suffice it to say, the magic number below is reserved for
        // action capture messages passed to DQA.
        $dqaType = 1008;
        if(function_exists('dqa_insert')){
            dqa_insert($dqaType, $serializedAction);
        }
        else {
            $sock = @fsockopen("udp://127.0.0.1", 50514);
            // This magic 14 is the syslog type. See http://www.monitorware.com/common/en/articles/syslog-described.php
            @fwrite($sock, sprintf("<14>dqa[%d]: +ACS+ +ACS+ %d %s", getmypid(), $dqaType, $serializedAction));
            @fclose($sock);
        }
    }
}

/**
 * @private
 * Doesn't write actions.
 */
class NoOpActionLogger extends ActionLoggerBase {
    public function record($serializedAction) {
        // As the name suggests...
    }
}

/**
 * @private
 * Holds one action at a time.
 * Using this class as the logger allows me to keep the guts of ActionCaptureImpl the same while implementing serializeAction().
 */
class SingleActionLogger extends ActionLoggerBase {
    private $action = null;
    public function record($serializedAction) {
        $this->action = $serializedAction;
    }
    public function getAction() {
        return $this->action;
    }
}
