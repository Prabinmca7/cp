<?php

namespace RightNow\Models;

use RightNow\Utils\FileSystem,
    RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Connect\Knowledge\v1 as KnowledgeFoundation;

/**
 * Base class for all models. Provides a number of boilerplate methods as well as a built in cache.
 */
abstract class Base{
    /**
     * Default cache length: 5 minutes
     */
    const CACHE_TIME = 300;

    protected $CI;

    private static $modelExtensionList = null;
    private static $loadedModels = array();
    private static $sessionToken = null;
    private $cache;

    function __construct()
    {
        $this->CI = (func_num_args() === 1) ? func_get_arg(0) : get_instance();
        if (IS_OPTIMIZED) {
            // Cache things for 5 minutes in Production & Staging.
            $this->cache = new \RightNow\Libraries\Cache\Memcache(self::CACHE_TIME);
        }
    }

    /**
     * Wrapper for ResponseObject class
     *
     * @param mixed $return The object|array|bool|string|whatever that is being returned from the method that was called.
     * @param \Closure|string $validationFunction A callable function that takes the return value as it's sole argument and returns
     * true upon success. If specified as null, no validation is performed.
     * @param array|string $errors An array of error messages, or a ResponseError objects or a string message
     * @param array|string $warnings An array of warning messages or a string message
     * @return \RightNow\Libraries\ResponseObject Instance of ResponseObject with populated properties
     * @see    \RightNow\Libraries\ResponseObject Used to construct one of these
     */
    public function getResponseObject($return, $validationFunction = 'is_object', $errors = array(), $warnings = array()) {
        $response = new \RightNow\Libraries\ResponseObject($validationFunction);
        $response->result = $return;

        if(!is_array($errors)){
            $errors = $errors ? array($errors) : array();
        }
        if(!is_array($warnings)){
            $warnings = $warnings ? array($warnings) : array();
        }
        foreach($errors as $error) {
            $response->error = $error;
        }
        foreach ($warnings as $warning) {
            $response->warning = $warning;
        }
        return $response;
    }

    /**
     * Returns a token to use for various KFAPI requests. This token is cached across a single CGI request.
     * @return string Token to pass to each KFAPI method
     */
    public static function getKnowledgeApiSessionToken(){
        if(self::$sessionToken === null){
            $CI = get_instance();
            $sessionID = ($CI->session) ? $CI->session->getSessionData('sessionID') : null;
            $serverHttpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
            self::$sessionToken = KnowledgeFoundation\Knowledge::StartInteraction(MOD_NAME, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $serverHttpReferer, $sessionID);
        }
        return self::$sessionToken;
    }

    /**
     * Load models. If the model is already loaded, it will return a reference to the current
     * version. If the model is not loaded, it will include the required file, create an instance and return it.
     * @param string $modelPath Model name in the form (standard/custom)/modelName i.e.(standard/Answer or custom/test/Answer). If standard or custom
     * are omitted, it will default to standard but look for custom model extensions first
     * @return object Reference to the model instance to use.
     * @throws \Exception If the provided relative model path could not be loaded.
     * @internal
     */
    public static function loadModel($modelPath){
        if (!is_string($modelPath)) {
            throw new \Exception(Config::getMessage(INVALID_MODEL_PATH_SPECIFIED_LBL));
        }
        //Store the path specified so that we can cache it along with the resolved path as well
        $originalModelPath = strtolower($modelPath);
        //Retrieve the model instance from the cache. We lowercase the path since it shouldn't affect which model is being loaded as class names in
        //PHP are case insensitive
        $cacheKey = $originalModelPath;
        if(isset(self::$loadedModels[$cacheKey]) && $existingModelInstance = self::$loadedModels[$cacheKey]){
            return self::$loadedModels[$cacheKey];
        }

        $standardClassExtensionPath = null;
        //Check to see if the path begins with standard or custom. If not, check to see if a custom model is extending this model and swap out
        //the path if necessary. If there is no extension occurring, then we're loading a standard model so prefix the path appropriately
        if(!Text::beginsWith($originalModelPath, 'standard/') && !Text::beginsWith($originalModelPath, 'custom/')){
            if($extendedModelPath = self::getExtendedModel($modelPath)){
                $standardClassExtensionPath = $modelPath;
                $modelPath = $extendedModelPath;
            }
            else{
                $modelPath = "standard/$modelPath";
            }
            $cacheKey = strtolower($modelPath);
        }

        list($basePath, $subDirectoryPath, $fileName, $className) = self::getAbsoluteModelPathAndClassname($modelPath);
        if (!class_exists($className)) {
            //Attempt to find different variations of the input filename
            foreach(array($fileName, strtolower($fileName), ucfirst(strtolower($fileName))) as $modelName) {
                $modelName = "{$basePath}{$subDirectoryPath}{$modelName}.php";
                if(FileSystem::isReadableFile($modelName)) {
                    $fullPath = $modelName;
                    break;
                }
            }
            if(!isset($fullPath)) {
                //We couldn't find the requested model. If this is a standard model, attempt to look in the compat layer.
                $fullPath = CORE_FILES . "compatibility/Models/{$subDirectoryPath}{$fileName}.php";
                if(!Text::beginsWith($modelPath, 'standard/') || !FileSystem::isReadableFile($fullPath)){
                    throw new \Exception(sprintf(Config::getMessage(LOCATE_MODEL_PCT_S_PATH_PCT_S_MSG), $fileName, "{$basePath}{$subDirectoryPath}{$fileName}.php"));
                }
            }

            //If a custom model is extending a standard model, include the parent file before we include the child
            if($standardClassExtensionPath){
                $fullParentFilePath = CPCORE . "Models/$standardClassExtensionPath.php";
                if(!FileSystem::isReadableFile($fullParentFilePath)){
                    throw new \Exception(sprintf(Config::getMessage(PCT_S_CST_MODEL_DECLARES_EXTENDS_MSG), $subDirectoryPath . $fileName, $standardClassExtensionPath));
                }
                require_once $fullParentFilePath;
            }

            require_once $fullPath;
            if(!class_exists($className)){
                //Show a more descriptive message if the class exists, but in the global scope
                if(class_exists($fileName)){
                    throw new \Exception(sprintf(Config::getMessage(CUST_MODEL_CLASSES_NAMESPACED_CUST_MSG), $subDirectoryPath . $fileName));
                }
                throw new \Exception(sprintf(Config::getMessage(FND_MDEL_FILE_EXPECTED_CLASS_NAME_MSG), $className));
            }
        }

        $instance = new $className();
        if($standardClassExtensionPath){
            //Now that we have an instance, make sure it properly extends the standard model
            $standardModelClassName = "RightNow\\Models\\" . str_replace("/", "\\", $standardClassExtensionPath);
            if(!$instance instanceof $standardModelClassName){
                throw new \Exception(sprintf(Config::getMessage(PCT_S_CUST_MODEL_DECLARES_EXTENDS_MSG), $subDirectoryPath . $fileName, $standardClassExtensionPath, $standardModelClassName));
            }
        }

        get_instance()->load->setModelLoaded($instance);
        //Store the instance in the cache under both the resolved path and the original path if necessary
        self::$loadedModels[$cacheKey] = $instance;
        if($cacheKey !== $originalModelPath){
            self::$loadedModels[$originalModelPath] = $instance;
        }
        return $instance;
    }

    /**
     * Returns the cached value for the given key.
     * First checks the in-process cache before defaulting to memcache (when in optimized mode)
     * @param string $key Cache key
     * @return object|boolean Deserialized object or false if not found
     */
    protected function getCached($key) {
        if ($result = \RightNow\Utils\Framework::checkCache($key)) {
            return $result;
        }
        if ($this->cache) {
            return $this->cache->get($key);
        }

        return false;
    }

    /**
     * Caches the given value with a key.
     * @param string $key Cache key
     * @param mixed $value The object, array, string, etc. to be cached
     * @return mixed The value stored in the cache
     */
    protected function cache($key, $value) {
        \RightNow\Utils\Framework::setCache($key, $value);
        if ($this->cache) {
            return $this->cache->set($key, $value);
        }

        return $value;
    }

    /**
     * Sets the SecurityOptions object on the KFAPI Content object. Uses the Contact object parameter if provided or the currently logged
     * in user.
     * @param object $knowledgeApiContent Instance of KFAPI Content/ContentSearch object or one of its multiple extended classes
     * @param Connect\Contact|null $contact Instance of Connect contact object used to populate filters. No need to specify if the user is logged in
     * @return void
     */
    protected function addKnowledgeApiSecurityFilter($knowledgeApiContent, $contact = null){
        // if the contact is not logged in, do not add the contact to the security options
        // for SmartAssistant to prevent privileged content from being returned
        if (!\RightNow\Utils\Framework::isLoggedIn())
            return;
        if(!is_object($contact) && $this->CI->session && \RightNow\Utils\Framework::isLoggedIn()){
            $contact = $this->CI->model('Contact')->get($this->CI->session->getProfileData('contactID'))->result;
        }
        if(is_object($contact)){
            $knowledgeApiContent->SecurityOptions = new KnowledgeFoundation\ContentSecurityOptions();
            $knowledgeApiContent->SecurityOptions->Contact = $contact;
        }
    }

    /**
     * Returns an error message if site is under abuse.
     * @return string|false False if site not in an abusive state, else an error message.
     */
    protected function isAbuse(){
        return \RightNow\Libraries\AbuseDetection::isAbuse() ? Config::getMessage(REQUEST_PERFORMED_SITE_ABUSIVE_MSG) : false;
    }

    /**
     * Looks up the requested model in the extensions file to see if we should load a custom model instead.
     * @param string $model The standard model file being requested
     * @return string|null The path to the custom model to load instead or null if no extension was found
     */
    private static function getExtendedModel($model){
        //Load in the extension file if it hasn't been loaded yet
        if(self::$modelExtensionList === null){
            $extensionsList = \RightNow\Utils\Framework::getCodeExtensions();
            if(is_array($extensionsList) && array_key_exists('modelExtensions', $extensionsList)){
                self::$modelExtensionList = (is_array($extensionsList['modelExtensions']))
                    ? $extensionsList['modelExtensions']
                    : array();
            }
            else{
                $CI = get_instance();
                if(method_exists($CI, '_addErrorPriorToHeaderInitialization')){
                    $CI->_addErrorPriorToHeaderInitialization(Config::getMessage(ATT_PARSE_CUST_S_DEVELOPMENT_S_CFG_MSG), false);
                }
                self::$modelExtensionList = array();
            }
        }
        if(isset(self::$modelExtensionList[$model]) && $extendedModelPath = self::$modelExtensionList[$model]){
            return "custom/$extendedModelPath";
        }
        return null;
    }

    /**
     * Parse out the components of the model given its relative path
     * @param string $modelPath Relative model path provided to model() method
     * @return array Components of the model. List includes
     *      -basePath: Path to model root directory, either CPCORE/Models or APPPATH/models/custom
     *      -subDirectoryPath: Path after basePath, e.g. Answer or subDirectory/MyModel
     *      -fileName: File name on disk, without any path prefix, e.g. Answer or MyModel
     *      -className: Fully namespaced class to load, e.g. RightNow\Models\Answer or Custom\Models\MyModel
     */
    private static function getAbsoluteModelPathAndClassname($modelPath){
        $explodedPath = array_filter(explode('/', $modelPath));
        $modelName = array_pop($explodedPath);
        $modelType = strtolower(array_shift($explodedPath));
        $subDirectoryPath = count($explodedPath) ? implode('/', $explodedPath) . '/' : '';

        // If the model is a standard model, look in CPCORE; otherwise in APPPATH
        if($modelType === 'standard')
        {
            $basePath = CPCORE . 'Models/';
            //By default, use exactly what's specified for the model's filename and classname
            //and assume that the filename and classname are identical, as is the standard convention.
            $className = "RightNow\\Models\\" . ($subDirectoryPath !== "" ? str_replace("/", "\\", $subDirectoryPath) : "" ) . $modelName;
        }
        else
        {
            $basePath = APPPATH . 'models/custom/';
            $className = "Custom\\Models\\" . ($subDirectoryPath !== "" ? str_replace("/", "\\", $subDirectoryPath) : "" ) . $modelName;
        }
        return array($basePath, $subDirectoryPath, $modelName, $className);
    }
}
