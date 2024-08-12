<?

namespace RightNow\Internal\Libraries\Deployment\Assets;

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\ClientLoader;

/**
 * Performs grouping of JS files.
 */
class AssetOrganizer {
    private $docRoot;
    private $clientLoader;
    private $optimizedOutputDir;

    private static $dependencies = array(
        'Asset',
        'AssetGroup',
        'GlobAsset',
    );

    /**
     * Constructor.
     * @param string $docRoot Doc root to prepend onto paths
     */
    function __construct ($docRoot) {
        self::loadDependencies();

        $optimizedJavaScriptLocation = Url::getCoreAssetPath('js/' . MOD_BUILD_SP . '.' . MOD_BUILD_NUM);

        $this->docRoot = $docRoot;
        $this->optimizedOutputDir = $this->docRoot . $optimizedJavaScriptLocation . '/min';
        $this->clientLoader = new ClientLoader(new \RightNow\Internal\Libraries\DeployerClientLoaderOptions());
    }

    /**
     * Returns an Iterator object containing assets.
     * @return Object \Iterator
     */
    function getAllFrameworkJS () {
        return new AssetGroup(
            $this->getFramework(),
            $this->getWidgetHelperModules(),
            $this->getChat(),
            $this->getMAv2(),
            $this->getMACompatibility()
        );
    }

    /**
     * Assets representing the framework JS for
     * the two framework JS "modules" (mobile and standard)
     * @return array Assets for the two JS modules
     */
    function getFramework () {
        static $modules = array(
            'standard' => 'RightNow.js',
            'mobile'   => 'RightNow.Mobile.js',
        );

        $frameworkJS = array();

        foreach ($modules as $moduleName => $optimizedFileName) {
            $this->clientLoader->setJavaScriptModule($moduleName);
            $jsFiles = AssetOptimizer::prependFilePaths(array_merge(
                $this->clientLoader->getYuiJavaScriptFiles(),
                $this->clientLoader->getFrameworkJavaScriptFiles()
            ), $this->docRoot);

            $frameworkJS []= new Asset($jsFiles, "{$this->optimizedOutputDir}/{$optimizedFileName}");
        }
        return $frameworkJS;
    }

    /**
     * Assets representing all JS modules
     * - ui
     * - chat
     * - widgetHelpers
     * @return GlobAsset A group of assets
     */
    function getWidgetHelperModules () {
        return new GlobAsset($this->docRoot . Url::getCoreAssetPath('debug-js/modules/*/*.js'),
            $this->optimizedOutputDir . '/modules');
    }

    /**
     * Asset representing Chat JS.
     * @return Asset asset
     */
    function getChat () {
        $yuiPath = Url::getYUICodePath('');
        $jsFiles = array(
            'yui'  => array(),
            'core' => array(),
        );
        foreach ($this->clientLoader->getChatJavaScriptFiles() as $filePath) {
            $type = (Text::beginsWith($filePath, $yuiPath)) ? 'yui' : 'core';
            $jsFiles[$type][]= $filePath;
        }

        $jsFiles = AssetOptimizer::prependFilePaths(array_merge(
            $jsFiles['yui'], $jsFiles['core']
        ), $this->docRoot);

        return new Asset($jsFiles, "{$this->optimizedOutputDir}/RightNow.Chat.js");
    }

    /**
     * Asset representing MA JS for CPv2.
     * @return Asset asset
     */
    function getMAv2 () {
        $fileName = "RightNow.MarketingFeedback.js";
        return new Asset($this->docRoot . Url::getCoreAssetPath("debug-js/{$fileName}"),
            "{$this->optimizedOutputDir}/{$fileName}");
    }

    /**
     * Asset representing MA JS in compatibility.
     * @return Asset asset
     */
    function getMACompatibility () {
        $fileName = "RightNow.Compatibility.MarketingFeedback.js";
        return new Asset($this->docRoot . Url::getCoreAssetPath("debug-js/{$fileName}"),
            $this->docRoot . Url::getCoreAssetPath("static/{$fileName}"));
    }

    /**
     * Asset representing the old deprecated JS feature where customers can
     * place a file named "autoload.js" under customer/development/javascript/ and
     * expect it to get loaded on every page.
     * @param  string $pathPrefix          Path to customer directory to look for the file on disk
     * @param  string $optimizedPathPrefix Path to write the file out to
     * @return Asset|null                      Asset if one exists
     */
    function getCustomAndDeprecated ($pathPrefix, $optimizedPathPrefix) {
        $file = '/javascript/autoload.js';
        $path = "{$pathPrefix}{$file}";
        if (FileSystem::isReadableFile($path)) {
            return new Asset($path, "{$optimizedPathPrefix}/custom/autoload.js");
        }
    }

    /**
     * Returns syndicated JS files.
     * @return array The first element is an array containing all YUI files
     *                   The second element is an Asset representing the
     *                   core app JS files where the YUI contents are to
     *                   be prepended onto
     */
    function getSyndicated () {
        $jsFiles = array(
            'yui' => array(
                Url::getOldYuiCodePath('yahoo-dom-event/yahoo-dom-event.js'),
                Url::getOldYuiCodePath('get/get-min.js'),
                Url::getOldYuiCodePath('json/json-min.js'),
            ),
            'rn' => array(
                "{$this->docRoot}/euf/rightnow/debug-js/RightNow.Client.js",
                "{$this->docRoot}/euf/rightnow/debug-js/RightNow.Client.Util.js",
            ),
        );
        return array(
            $jsFiles['yui'],
            new Asset($jsFiles['rn'], "{$this->docRoot}/euf/rightnow/RightNow.Client.js")
        );
    }

    /**
     * Requires the dependencies this class relies upon.
     */
    private static function loadDependencies () {
        foreach (self::$dependencies as $fileName) {
            require_once __DIR__ . "/{$fileName}.php";
        }
    }
}
