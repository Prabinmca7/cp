<?php
namespace RightNow\Libraries;

use RightNow\Utils\Tags,
    RightNow\Utils\Url,
    RightNow\Utils\FileSystem,
    RightNow\Utils\Framework;

/**
 * Handles passing data from the server to the client to initialize CP on the client side.
 */
final class ClientLoader extends \RightNow\Internal\Libraries\ClientLoader {
    //Content that the public ClientLoader interface needs to modify.
    /**
     * List of CSS to dynamically include
     * @internal
     */
    protected $includedCSS = array();

    /**
     * String of content to dynamically add within the head tag
     * @internal
     */
    protected $includedHeadContent = '';

    /**
     * List of CSS files to include
     * @internal
     */
    protected $cssFiles = array();

    /**
     * List of Javascript files to include
     * @internal
     */
    protected $javaScriptFiles;

    /**
     * Instance of ClientLoader options
     * @internal
     */
    protected $options;

    public function __construct($arg) {
        parent::__construct();
        $this->options = $arg;
    }

    /**
     * Sets the pages JavaScript module (i.e. standard or mobile).
     * @param string $module The module to use for the current page.
     * @return void
     */
    public function setJavaScriptModule($module)
    {
        parent::setJavaScriptModule($module);
    }

    /**
     * Adds a reference to a JavaScript file to add to the current page.
     * @param string $path Path to the JavaScript file
     * @param string $type Type of JS file being added
     * @param string $attributes Additional script tag attributes
     * @return string Script tag which points to the file
     * @internal
     */
    public function addJavaScriptInclude($path, $type='additional', $attributes='')
    {
        return parent::addJavaScriptInclude($path, $type, $attributes);
    }

    /**
     * Loads the JavaScript resource(s) specified by $urls. Resource will be loaded asynchronously by default.
     * @param string|array $urls The path to the JS being loaded, or a list of paths.
     * @param array $options Options that control whether the resource is fetched asynchronously
     *                       as well as other options that can be passed along to `YUI.Get.js` such as a callback.
     *                       If options is empty, or only contains the 'async' attribute, the JS resource will be loaded via the `script` tag.
     *                       Specifying any other options, such as `callback` will result in loading the resource via `YUI.Get.js`.
     * @return string The code used to load the resource, either via the `script` tag or `YUI.Get.js`
     */
    public function loadJavaScriptResource($urls, array $options = array('async' => true)) {
        return parent::loadJavaScriptResource($urls, $options);
    }

    /**
     * Returns the YUI configuration object
     * @param array $configuration YUI_config options to set
     * @return string Script tag which outputs the YUI config object
     * @internal
     */
    public function getYuiConfiguration(array $configuration = array()) {
        return parent::getYuiConfigurationSnippet($configuration);
    }

    /**
     * Converts message and config calls found within a widget logic file.
     * @param array $messageBaseInformation List of messagebase entries in the widget
     * @param array $configBaseInformation List of configbase entries in the widget
     * @return void
     * @internal
     */
    public function convertWidgetInterfaceCalls(array $messageBaseInformation, array $configBaseInformation) {
        parent::convertWidgetInterfaceCalls($messageBaseInformation, $configBaseInformation);
    }

    /**
     * Retrieves the script needed to initialize CP on the client side.
     * @return string
     * @internal
     */
    public function getClientInitializer() {
        return parent::getClientInitializer();
    }

    /**
     * Returns a script tag with all additional JS file references for the current page
     * @return string
     * @internal
     */
    public function getAdditionalJavaScriptReferences() {
        return parent::getAdditionalJavaScriptReferences();
    }

    /**
     * Adds the contents of a file or literal code to the end of the page.
     * @param string $pathOrCode Either the path to a file or literal JavaScript code
     * @param boolean|null $isCode Denotes if content is literal code or the path to a file
     * @return void
     */
    public function addJavaScriptInline($pathOrCode, $isCode = false)
    {
        //If we're adding a file inline, make sure the path to the file exists.
        if(!$isCode && !FileSystem::isReadableFile($pathOrCode))
        {
            //Support the ability for users to add URI paths as well
            if(FileSystem::isReadableFile(HTMLROOT . $pathOrCode))
            {
                $pathOrCode = HTMLROOT . $pathOrCode;
            }
            else
            {
                Framework::addDevelopmentHeaderError(sprintf(\RightNow\Utils\Config::getMessage(ADDJAVASCRIPTINLINE_ERR_FILE_PCT_S_MSG), $pathOrCode));
                return;
            }
        }
        $this->javaScriptFiles->addCode($pathOrCode, $isCode);
    }

    /**
     * Creates CSS link tag, but only if file hasn't already been included
     *
     * @param string $cssPath The file system path
     * @param boolean $fullPath Indicates if this will be full path
     * @return string The formed CSS link tag if file hasn't been loaded already
     */
    public function createCSSTag($cssPath, $fullPath=false)
    {
        if(!in_array($cssPath, $this->includedCSS))
        {
            array_push($this->includedCSS, $cssPath);
            if ($fullPath)
                $cssPath = Url::getShortEufBaseUrl('sameAsCurrentPage', $cssPath);
            return Tags::createCssTag($cssPath);
        }

    }

    /**
     * Adds content to the bottom of the head tag
     * @param string $content The content to put at the top of the head
     * @return void
     */
    public function addHeadContent($content)
    {
        $this->includedHeadContent .= $content;
    }

    /**
     * Adds a CSS include given the path to a CSS file
     * @param string $path The path to the CSS file to add
     * @return void
     */
    public function addStylesheet($path)
    {
        if (!array_key_exists($path, $this->cssFiles))
        {
            $this->cssFiles[$path] = true;
            $this->addHeadContent("<link type=\"text/css\" rel=\"stylesheet\" href=\"$path\" />\n");
        }
    }

    /**
     * Checks for a 'none' moduleType in a page set.
     * @return boolean True if moduleType is 'none', false otherwise
     */
    public function isJavaScriptModuleNone() {
        return parent::isJavaScriptModuleNone();
    }

    /**
     * Checks for a 'standard' moduleType in a page set.
     * @return boolean True if moduleType is '' or 'standard', false otherwise
     */
    public function isJavaScriptModuleStandard() {
        return parent::isJavaScriptModuleStandard();
    }

    /**
     * Checks for a 'mobile' moduleType in a page set.
     * @return boolean True if moduleType is 'mobile', false otherwise
     */
    public function isJavaScriptModuleMobile() {
        return parent::isJavaScriptModuleMobile();
    }
}