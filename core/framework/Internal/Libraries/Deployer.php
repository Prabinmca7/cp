<?php
namespace RightNow\Internal\Libraries;
use RightNow\Utils\FileSystem,
    RightNow\Utils\Widgets,
    RightNow\Internal\Utils\WidgetViews,
    RightNow\Utils\Tags,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Utils\Url,
    RightNow\Internal\Utils\Deployment,
    RightNow\Internal\Utils\Logs,
    RightNow\Internal\Utils\Deployment\CodeWriter,
    RightNow\Internal\Utils\Deployment\SharedViewPartialOptimization,
    RightNow\Internal\Utils\Deployment\WidgetHelperOptimization,
    RightNow\Internal\Libraries\Deployment\OptimizedWidgetWriter,
    RightNow\Internal\Libraries\Widget\PathInfo,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Libraries\Widget\Locator,
    RightNow\Internal\Libraries\ClientLoader,
    RightNow\Internal\Libraries\Deployment\Assets\AssetOptimizer,
    RightNow\Internal\Libraries\Deployment\Assets\AssetOrganizer,
    RightNow\Libraries\Cache\SizeLimitedCache,
    RightNow\Api;

require_once CPCORE . 'Internal/Libraries/ThemeParser.php';
require_once CPCORE . 'Internal/Libraries/Deployment/Assets/AssetOrganizer.php';
require_once CPCORE . 'Internal/Libraries/Deployment/Assets/AssetOptimizer.php';
require_once CPCORE . 'Internal/Libraries/Resolvers.php';
require_once CPCORE . 'Internal/Libraries/Staging.php';
require_once CPCORE . 'Internal/Libraries/Deployment/OptimizedWidgetWriter.php';
require_once CPCORE . 'Libraries/Cache/SizeLimitedCache.php';
require_once CPCORE . 'Internal/Utils/Deployment.php';
require_once CPCORE . 'Internal/Utils/Deployment/CodeWriter.php';
require_once CPCORE . 'Internal/Utils/Deployment/SharedViewPartialOptimization.php';
require_once CPCORE . 'Internal/Utils/Deployment/WidgetHelperOptimization.php';

/**
 * The Imperial Deployer: Performance Learnings of Development for Make Benefit Glorious Performance of Hosting.
 *
 * Mashes Together Files
 */
final class Deployer{
    private $hasWrittenLogHeader = false;
    private $compileErrors = array();
    private $compileWarnings = array();
    private $CI = false;
    private $delayedDeleteList = array();
    private $renamedCounter = 0;
    private $includeAutoloadJavascript = false;
    private $widgetHeaderFiles = array();
    private $compiledJavaScriptTemplates = array();
    private $widgetMetaCache = array();
    private $themesToCopyToOptimized = array();
    private $extraThemeFilesToCopyToOptimized = array();
    private $assetsToCopyToOptimized = array();
    private $deletedWidgets = array();
    private $shellSafeFilenameMap;
    private $logger = null;
    private $widgetArrayCache = null;
    private $widgetControllerCache = null;
    private $minifiedJsCache = array();
    private $minifiedJsCacheByteCount = 0;
    private $spaceRemovalOriginalNames = array();
    private $spaceRemovalNewNames = array();

    /**
     * A 30MB cache
     */
    private $maximumMinifiedJsCacheByteCount = 31457280;
    private $templateContentCache = array();
    private $templateContentCacheByteCount = 0;

    /**
     * A 5MB cache
     */
    private $maximumTemplateContentCacheByteCount = 5242880;

    /**
     * Max size per CSS file - 5KB
     */
    private $minimumCssFileSize = 5120;
    private $javaScriptInterfaceCalls = array('message' => array(), 'config' => array());
    private $sandboxedConfigs = array();
    private $shouldRemoveDeployLock = false;
    private $themePathResolver = null;
    private $widgetPresentationCssResolver = null;

    public $javaScriptFileManager = null;
    public $options = null;

    const SHELL_UNSAFE_FILENAME_REGEX = '@[^-a-zA-Z0-9./_]@'; // OK, so '/' isn't really shell safe, but it can't appear in filenames, either.
    const RN_WIDGET_RENDER_CALL_PATTERN = '@<\?=\\\\RightNow\\\\Utils\\\\Widgets::rnWidgetRenderCall\(\'(.*?)\', array\(.*\);@';

    /**
     * Files/pages required to be present in source directory
     */
    public static $requiredFilesInSourceDir = array(
        'config/mapping.php',
        'config/hooks.php',
        'errors/error_php.php',
        'errors/error_general.php',
        'views/admin/answer_full_preview.php',
        'views/admin/answer_quick_preview.php'
    );

    /**
     * Creates a Deployer class to promote CP files from specified source to specified target directories.
     * @param object $options A BaseDeployOptions derived object
     */
    function __construct($options) {
        assert_options(ASSERT_ACTIVE, !IS_HOSTED);
        assert_options(ASSERT_WARNING, 1);
        assert_options(ASSERT_BAIL, 1);
        assert(is_object($options));
        assert($options instanceof BaseDeployOptions);
        assert($options->getLogFileName() === false || Text::beginsWith($options->getLogFileName(), '/'));
        assert(Text::beginsWith($options->getScriptsBaseDir(), '/'));
        assert(Text::endsWith($options->getScriptsBaseDir(), '/'));
        assert(strlen($options->getScriptsBaseDir()) > 2);
        assert(Text::beginsWith($options->getSourceDir(), '/'));
        assert(Text::endsWith($options->getSourceDir(), '/'));
        assert(strlen($options->getSourceDir()) > 2);
        assert(Text::beginsWith($options->getOutputBaseDir(), '/'));
        assert(Text::endsWith($options->getOutputBaseDir(), '/'));
        assert(strlen($options->getOutputBaseDir()) > 2);
        assert(Text::beginsWith($options->getStaticContentBaseDir(), '/'));
        assert(Text::endsWith($options->getStaticContentBaseDir(), '/'));
        assert(strlen($options->getStaticContentBaseDir()) > 2);
        $this->logger = new DeployLogger($options->getLogFileName());
        $this->options = $options;
        $this->CI = get_instance();
        umask($this->getUmaskForEnvironment());
        $this->readSandboxedConfigValues();
        $this->widgetArrayCache = new SizeLimitedCache(5242880, array($this, 'populateWidgetArray'));
        $this->widgetControllerCache = new SizeLimitedCache(5242880, array($this, 'checkAndStripWidgetController'));
        $this->javaScriptFileManager = new JavaScriptFileManager();
    }

    function __destruct() {
        // suppressing logging as it interferes with json returned by ajax calls.
        $this->removeDeployLock(false);
    }

    /**
     * In hosting, we want the files we create to be writable by the group.  In development, we want the files to be world writeable because
     * PHP runs as a process which is not in that group that developers are;
     * otherwise, developers wouldn't be able to modify their files.
     */
    private function getUmaskForEnvironment() {
        if ($this->options->isRunningInHosting()) {
            return 0002;
        }
        return 0;
    }

    private function verifyDeployLock() {
        if ($this->options->shouldVerifyDeployLock()) {
            // Determine if the account_id and created_time of an existing lock was passed in.
            if ((!$accountID = $this->options->getAccountID()) || (!$createdTime = $this->options->getDeployLockCreatedTime())) {
                $accountID = null;
                $createdTime = null;
            }
            $lockData = Deployment::createDeployLock($this->options->getAccountID(), $this->options->getDeployType(), $accountID, $createdTime);
            if ($lockData['lock_obtained'] === true) {
                $this->shouldRemoveDeployLock = true;
                $this->writeMessage('', $lockData['message'], "<br />");
            }
            else {
                $this->compileError("{$lockData['message']}<br />{$lockData['lock_details']}");
            }
        }
    }

    private function removeDeployLock($writeToLog = true) {
        if ($this->shouldRemoveDeployLock === true) {
            list($lockWasRemoved) = Deployment::removeDeployLock($this->options->getAccountID());
            if ($lockWasRemoved) {
                $this->shouldRemoveDeployLock = false;
                if ($writeToLog === true) {
                    $this->writeMessage('', Config::getMessage(DEPLOY_LOCK_REMOVED_MSG), "<br />");
                }
            }
            else if ($writeToLog === true) {
                $this->writeMessage('', Config::getMessage(ERROR_ENC_REMOVING_DEPLOY_LOCK_MSG), "<br />", 'warn');
            }
        }
    }

    private function shortenPathName($path) {
        if (Text::beginsWith($path, CORE_FILES)) {
            return Text::getSubstringAfter($path, CORE_FILES);
        }
        if (Text::beginsWith($path, CUSTOMER_FILES)) {
            return Text::getSubstringAfter($path, CUSTOMER_FILES);
        }
        return Text::getSubstringAfter($path, HTMLROOT, $path);
    }

    /**
     * Return the path specified in one of the defined CP_*_URL configs.
     * The path will have a leading slash added to it, .php appended to the end, and common url parameters optionally stripped.
     * If the config value is blank, return the $page value.
     * @param string $page Page path e.g. '/answers/detail.php'
     * @param bool $obtainValueFromConfig If true, obtain page url value from corresponding config, else use $page (primarily used for unit testing).
     * @param bool $removeCommonUrlParameters If true, remove common url parameters (a_id, kw, etc) from url.
     * @return string
     * @throws \Exception If page isn't an expected config page
     */
    public static function getPageUrlFromConfig($page, $obtainValueFromConfig = true, $removeCommonUrlParameters = true) {
        static $pages;
        if (!isset($pages)) {
            $pages = array(
                '/answers/detail.php' => array('key' => CP_ANSWERS_DETAIL_URL, 'value' => false),
                '/home.php' => array('key' => CP_HOME_URL, 'value' => false),
            );
        }

        $standardize = function($url) use ($removeCommonUrlParameters) {
            $url = trim(Deployer::removeExtraSlashes($url), '/');
            if (!Text::beginsWith($url, '/')) {
                $url = "/$url";
            }
            if ($removeCommonUrlParameters) {
                foreach(array('a_id', 'kw', 'p', 'c') as $urlParameter) {
                    $url = Url::deleteParameter($url, $urlParameter);
                }
            }
            if (!Text::endsWith($url, '.php')) {
                $url .= '.php';
            }
            return $url;
        };

        if (!$obtainValueFromConfig) {
            return $standardize($page);
        }

        if (!array_key_exists($page, $pages)) {
            throw new \Exception("Not a recognized CP URL page: $page."); // doesn't need internationalized
        }

        if ($pages[$page]['value'] === false) {
            if ($configValue = Config::getConfig($pages[$page]['key'])) {
                $pages[$page]['value'] = $standardize($configValue);
            }
            else {
                $pages[$page]['value'] = null;
            }
        }

        return $pages[$page]['value'] ?: $page;
    }

    private function checkForLoginRequiredMismatchOnAnswersPages($page, $meta) {
        static $pages;
        if (!isset($pages)) {
            $pages = array('/answers/list.php' => null, self::getPageUrlFromConfig('/answers/detail.php') => null);
        }

        if (in_array($page, array_keys($pages))) {
            $pages[$page] = array_key_exists('login_required', $meta) ? $meta['login_required'] : false;
            $lastPage = $lastRequired = null;
            foreach ($pages as $thisPage => $thisRequired) {
                if ($thisRequired !== $lastRequired && $thisRequired !== null && $lastRequired !== null) {
                    list($requiredPage, $notRequiredPage) = ($thisRequired) ? array($thisPage, $lastPage) : array($lastPage, $thisPage);
                    $this->writeMessage('', sprintf(Config::getMessage(WARN_LOGIN_REQD_MISMATCH_PG_PCT_S_MSG), $requiredPage, $notRequiredPage), "<br />", 'warn');
                }
                $lastPage = $thisPage;
                $lastRequired = $thisRequired;
            }
        }
    }

    /**
     * Gets the current list of compile errors
     * @return array An array of error messages.
     */
    function getCompileErrors() {
        return $this->compileErrors;
    }

    /**
     * Gets the current list of compile warnings
     * @return array An array of warning messages.
     */
    function getCompileWarnings() {
        return $this->compileWarnings;
    }

    /**
     * Used to copy development files and configs to staging, then deploy staging_01/source -> staging_01/optimized
     * @return [boolean] - true upon success.
     */
    function stage() {
        $productionPaths = array();
        if (!$this->options->isRunningInHosting()) {
            // Obtain modification time of production directories and compare after deploy
            // as a sanity check to ensure staging never modifies production.
            // This can likely be removed once appropriate automatic tests are in place.
            foreach (array(OPTIMIZED_FILES . 'production/source', OPTIMIZED_FILES . 'production/optimized',
                           HTMLROOT . '/euf/assets', HTMLROOT . '/euf/generated/optimized') as $path) {
                if (FileSystem::isReadableDirectory($path)) {
                    $productionPaths[$path] = filemtime($path);
                }
            }
        }

        // Write staging log header now in case we fail prior to 'prepare_deploy()' below.
        // writeLogHeader has logic to prevent it from being called twice.
        $this->writeLogHeader();

        if ($this->options->shouldInitializeStaging()) {
            $this->writeMessage('<h3>', Config::getMessage(INITIALIZING_STAGING_ENVIRONMENT_LBL), '</h3>');
            Deployment::createStagingDirectories(
                $this->options->getStagingName(), true,
                $this->options->shouldModifyPageSetConfiguration()
            );
        }

        $this->options->setWidgetTargetPages();

        $exitStatus = ($this->prepare_deploy() && $this->commit_deploy());

        foreach ($productionPaths as $path => $mtime) {
            if (filemtime($path) !== $mtime) {
                $this->compileError("Production directory was modified! : $path"); // Doesn't need internationalization
            }
        }

        if ($exitStatus !== true && $this->options->shouldInitializeStaging()) {
            $stage = new Staging($this->options->getStagingName());
            $stage->restoreStagingDirectories();
        }

        return $exitStatus;
    }

    /**
     * Performs the first half of deploy; everything is compiled and so forth,
     * but is left in a temporary directory waiting for the deploy to be
     * committed.
     */
    function prepare_deploy() {
        set_time_limit(9000);

        $this->writeLogHeader();
        $this->writeMessage('<h3>', sprintf(Config::getMessage(PREPARING_PCT_S_OPERATION_LBL), $this->options->getDeployType()), '...</h3>');

        try {
            $this->prepare_deploy_core();
        }
        catch (\Exception $ex) {
            $message = htmlspecialchars($ex->getMessage());
            if ($this->options->shouldIncludeStackTrace()) {
                $message .= '<br />' . str_replace("\n", '<br />', htmlspecialchars($ex->getTraceAsString()));
            }
            $this->compileError($message);
        }
        $this->finalizeDelayedDeletes();
        $this->reportStatusForPrepareDeploy();
        return 0 === count($this->getCompileErrors());
    }

    /**
     * This does the real work of preparing for deploy, excluding stuff like error handling and logging.
     */
    private function prepare_deploy_core() {
        $this->validateBeforeDeploy();
        if (count($this->compileErrors) > 0) {
            return;
        }

        $this->copySourceFilesToTempSource();
        $this->copySelectiveFilesToSource();
        $this->removeSelectiveFilesFromSource();
        $this->validateHooksFileFormat();
        if (count($this->compileErrors) > 0) {
            return;
        }
        $this->combineCustomViewPartials();
        $this->combineCustomSharedViewHelpers();
        $this->modifyConfigurations();
        if ($this->options->shouldCopyStandardSyndicatedWidgetsToTempSource()) {
            $this->copyStandardSyndicatedWidgetsToTempSource();
        }
        $this->modifyVersions();

        // re-initialize widget registry in case custom widgets have changed.
        $this->options->setWidgetTargetPages('temp_source');

        if ($this->options->shouldUninstallDeletedCustomWidgets() && ($this->uninstallDeletedCustomWidgets() === false)) {
            $this->compileError(Config::getMessage(ERR_ENC_ATT_UPD_WIDGET_VERSIONS_MSG));
        }
        if (count($this->compileErrors) > 0) {
            return;
        }

        // re-initialize widget registry in case custom widgets have changed.
        $this->options->setWidgetTargetPages('temp_source');

        $this->copyTempSourceToTempOptimized();
        $this->copyPagesToHeaders();

        $this->compile();
        if (count($this->compileErrors) > 0) {
            return;
        }

        $this->copyThemesToOptimized();
        $this->copyExtraThemeFilesToOptimized();

        if (count($this->compileErrors) > 0) {
            return;
        }

        $this->removeUnnecessaryFiles();
        $sourceDirectory = $this->options->getTempOptimizedDir();
        if ($this->options->shouldScriptCompile()) {
            $this->runMakeFile($sourceDirectory, "$sourceDirectory/src");
            if ($this->options->shouldVerifyMinimizedOptimizedIncludes()) {
                $this->verifyMinimizedOptimizedIncludes(array(CPCORE . 'optimized_includes.php', CORE_FILES . 'compatibility/optimized_includes.php'));
            }
        }
    }

    /**
     * We create a backup copy of theme files for use during service pack and upgrade
     * deploy.  This handles files used by a theme which aren't in a theme directory.
     */
    private function copyExtraThemeFilesToOptimized() {
        $this->writeMessage('<hr size=1 />', sprintf(Config::getMessage(COPYING_EXTRA_THEME_FILES_PCT_S_LBL), basename($this->options->getOutputBaseDir())));
        foreach ($this->extraThemeFilesToCopyToOptimized as $sourcePath => $optimizedPath) {
            $this->writeMessage('', sprintf(Config::getMessage(COPYING_EXTRA_THEME_FILE_PCT_S_PCT_MSG), $sourcePath, $optimizedPath));
            FileSystem::mkdirOrThrowExceptionOnFailure(dirname($optimizedPath), true);
            FileSystem::copyFileOrThrowExceptionOnFailure($sourcePath, $optimizedPath, false);
        }
    }

    /**
     * Walk through the widgetVersions file and ensure all custom widgets actually exist on disk.
     * If not, print a warning and remove it from the widgetVersions file.
     *
     * @param array|null $widgetPaths An array of custom widget relative paths to check.
     *        If null this is obtained from Registry::getWidgetPathInfo(). This is generally only specified when testing.
     * @return boolean Returns true if no changes were made or changes were made successfully. Returns false if the widgetVersions file
     *        was unsuccessfully modified in development.
     */
    private function uninstallDeletedCustomWidgets($widgetPaths = null) {
        $widgets = $widgetPaths ?: array_keys(Widgets::getDeclaredWidgetVersions());
        $developmentWidgets = \RightNow\Internal\Utils\Version::getVersionFile(CUSTOMER_FILES . 'widgetVersions');
        $tempSourceLocation = $this->options->getTempSourceDir();
        $changesForStaging = $changesForDevelopment = array();
        foreach ($widgets as $widgetPath) {
            if (!Text::beginsWith($widgetPath, 'custom/')) {
                continue;
            }
            $widget = Registry::getWidgetPathInfo($widgetPath);
            if (!$widget || !FileSystem::isReadableFile($tempSourceLocation . "widgets/{$widget->relativePath}/{$widget->version}/info.yml")) {
                if (array_key_exists($widgetPath, $developmentWidgets) && !FileSystem::isReadableFile(CUSTOMER_FILES . "widgets/{$widget->relativePath}/{$widget->version}/info.yml")) {
                    $this->writeMessage('', sprintf(Config::getMessage(PCT_S_WIDGET_EX_DEVELOPMENT_STAGING_MSG), $widgetPath), '<br />', 'warn');
                    $changesForDevelopment[$widgetPath] = 'remove';
                }
                else {
                    $this->writeMessage('', sprintf(Config::getMessage(WIDGET_PCT_S_EX_STAGING_ENVIRONMENT_MSG), $widget->version ? ($widgetPath . ' (' . $widget->version . ')') : $widgetPath), '<br />', 'warn');
                }
                $this->deletedWidgets[$widgetPath] = $widget->version;
                $changesForStaging[] = $widgetPath;
            }
        }
        if ($changesForStaging) {
            $file = "{$tempSourceLocation}widgetVersions";
            $contents = \RightNow\Internal\Utils\Version::getVersionFile($file);
            foreach ($changesForStaging as $changeForStaging) {
                unset($contents[$changeForStaging]);
            }
            \RightNow\Internal\Utils\Version::writeVersionFile($file, $contents, 'php');
        }
        if ($changesForDevelopment) {
            $modifyResult = Widgets::modifyWidgetVersions($changesForDevelopment);
            return $modifyResult !== false;
        }
        return true;
    }

    /**
     * Copy <euf docroot>/assets/themes/standard -> <euf docroot>/generated/temp_optimized/<timestamp>/themes/standard
     *
     * We create a backup copy of theme files for use during service pack and upgrade
     * deploy.  This recursively copies theme directories.
     */
    private function copyThemesToOptimized() {
        $this->writeMessage('<hr size=1 />', sprintf(Config::getMessage(COPYING_THEMES_TO_PCT_S_AREA_LBL), basename($this->options->getOutputBaseDir())));
        foreach ($this->themesToCopyToOptimized as $sourcePath => $optimizedPath) {
            $this->writeMessage('', sprintf(Config::getMessage(COPYING_THEME_PCT_S_TO_PCT_S_LBL), $this->shortenPathName($sourcePath), $this->shortenPathName($optimizedPath)));
            FileSystem::mkdirOrThrowExceptionOnFailure($optimizedPath, true);
            FileSystem::copyDirectory($sourcePath, $optimizedPath, false, false);
        }
    }

    /**
     * Finishes the job that prepare_deploy() started: copies the temporary directories into place.
     */
    function commit_deploy() {
        $this->writeLogHeader(Config::getMessage(CPS_DEPLOYMENT_LOG_COMMIT_DEPLOY_LBL));

        $this->writeMessage('<h3>', Config::getMessage(COMMITTING_DEPLOY_OPERATION_LBL), '...</h3>');

        $this->lockPageSetMappings();
        $this->backupSourceDir();
        $this->moveTempSourceToOutputSource();
        $this->moveCompiledJsToHtmlDocroot();
        $this->moveTempOptimizedToOutputDir();
        $this->writeSandboxedConfigValues();
        $this->writeTimestampToFile();
        $this->writeDeployExitStatus();
        $this->finalizeDelayedDeletes();
        $this->recordVersionChanges();
        $this->removeDeployLock();

        return 0 === count($this->getCompileErrors());
    }

    private function writeDeployExitStatus() {
        $failText = sprintf('<span class="errorText">%s</span>', Config::getMessage(DEPLOY_OPERATION_FAILED_LBL));
        $successText = sprintf('<span class="successText">%s</span>', Config::getMessage(DEPLOY_OPERATION_SUCCESSFUL_LBL));
        $this->writeMessage('<h3>', ($this->compileErrors) ? $failText : $successText, '</h3>', 'info');
    }

    private function recordVersionChanges() {
        if (!$this->compileErrors && ($arguments = $this->options->versionChangeArgs())) {
            foreach ($arguments as $args) {
                call_user_func_array(array('\RightNow\Internal\Utils\Deployment', 'recordVersionChanges'), $args);
            }
        }
    }

    /**
     * The deploy process is a little sloppy and creates a some files which aren't strictly needed.
     * This cleans them up.
     */
    private function removeUnnecessaryFiles() {
        // In production, only the header, i.e. the combined page and template, is used;
        // the pages and templates aren't.
        $targets = array(
            $this->options->getTempOptimizedDir() . 'views/pages',
            $this->options->getTempOptimizedDir() . 'views/templates',
            $this->options->getTempSourceDir() . 'syndicated_widgets/standard',
        );
        // In non-hosted, we don't have script compiled versions of the standard widget files except
        // those in generated/production/optimized/widgets, so we have to leave those there.  In hosting
        // the standard widgets at core/widgets are script compiled so we can use those and delete these.
        if ($this->options->shouldRemoveStandardWidgetsFromTempOptimized()) {
            $targets[] = $this->options->getTempOptimizedDir() . 'syndicated_widgets/standard';
        }
        // Widget headers are unnecessary for runtime; they're only used at compile time.
        $targets = array_merge($this->widgetHeaderFiles, $targets);
        foreach ($targets as $target) {
            if (is_dir($target)) {
                FileSystem::removeDirectory($target, true);
            }
            else if (is_file($target)) {
                unlink($target);
            }
        }
    }

    /**
     * Creates the specified directory (if it doesn't exist.)  Reports an error on failure
     * @param string $dir String containing a path.
     * @return bool Indicating success.
     */
    private function mkdirOrRecordError($dir) {
        if (!@mkdir($dir, 0777, true)) {
            $this->compileError(sprintf(Config::getMessage(CREATE_DIRECTORY_PCT_S_SERVER_MSG), $dir));
            return false;
        }
        return true;
    }

    private function prepareTempOptimizedJavaScriptDir() {
        $this->delayedDelete($this->options->getTempOptimizedAssetsDir());
        $this->mkdirOrRecordError($this->options->getTempOptimizedAssetsDir());
    }

    /**
     * There are a number of places in the deploy process where we need to make
     * sure that a directory doesn't exist before creating a directory by that
     * name.  If that directory exists, we really don't want to take the
     * performance hit of deleting it then, so we just move it to a temporary
     * location and schedule it to be deleted later.
     *
     * @param string $toDelete String containing a path.
     * @return bool Indicating success.
     */
    private function delayedDelete($toDelete) {
        $toDelete = Text::removeTrailingSlash($toDelete);
        if (is_readable($toDelete)) {
            $delayedName = $toDelete . '.toDelete';
            if (is_readable($delayedName)) {
                $this->writeMessage('', sprintf(Config::getMessage(DELAYED_DEL_TARG_PCT_S_EX_DEL_MSG), $delayedName, $toDelete), '<br />');
                FileSystem::removeDirectory($delayedName, true);
            }
            if (is_readable($delayedName)) {
                $this->compileError(sprintf(Config::getMessage(FAIL_RNAME_PCT_S_PCT_S_PREP_MSG), $toDelete, $delayedName));
                return false;
            }
            if (!@rename($toDelete, $delayedName)) {
                $this->compileError(sprintf(Config::getMessage(FAIL_RENAME_PCT_S_PCT_S_PREP_MSG), $toDelete, $delayedName));
                return false;
            }
            array_push($this->delayedDeleteList, $delayedName);
        }
        return true;
    }

    /**
     * Finishes the job started by delayedDelete()
     */
    private function finalizeDelayedDeletes() {
        $this->writeMessage('', Config::getMessage(FINALIZING_DELAYED_DELETES_MSG), '<br />');
        while (null !== ($delayedDelete = array_shift($this->delayedDeleteList))) {
            FileSystem::removeDirectory($delayedDelete, true);
            if (is_readable($delayedDelete)) {
                $this->writeMessage('', sprintf(Config::getMessage(DELAYED_DELETE_FAILED_FOR_PCT_S_MSG), $delayedDelete), '<br />', 'warn');
            }
        }
        $this->writeMessage('', Config::getMessage(FINISHED_FINALIZING_DELAYED_MSG), '<br />');
    }

    /**
     * Renames a directory while ensuring that the target directory doesn't already exist.
     *
     * @param string $sourceDir String containing a path.
     * @param string $targetDir String containing a path.
     * @return bool Indicating success.
     */
    private function moveDirectory($sourceDir, $targetDir) {
        if (!$this->delayedDelete($targetDir)) {
            return false;
        }
        if (!@rename($sourceDir, $targetDir)) {
            $this->compileError(sprintf(Config::getMessage(FAILED_MOVING_PCT_S_TO_PCT_S_MSG), $sourceDir, $targetDir));
            return false;
        }
        return true;
    }

    /**
     * Sets all page set mappings to locked in the database
     */
    private function lockPageSetMappings() {
        if ($this->options->shouldLockPageSetConfiguration()) {
            $this->writeMessage('<hr size=1 />', Config::getMessage(LOCKING_PAGE_SET_MAPPINGS_LBL), '<br />');
            $this->CI->model('Pageset')->lockPageSetMappings();
        }
    }

    /**
     * Move application/<output>/source -> application/<output>/backup
     */
    private function backupSourceDir() {
        $sourceDir = $this->options->getOutputSourceDir();
        $targetDir = $this->options->getOutputBackupDir();
        $this->writeMessage('<hr size=1 />', sprintf(Config::getMessage(BACKING_UP_PCT_S_TO_PCT_S_LBL), $this->shortenPathName($sourceDir), $this->shortenPathName($targetDir)));
        if (is_dir($sourceDir)) {
            $this->moveDirectory($sourceDir, $targetDir);
        }
    }

    /**
     * Move /www/rnt/{site}/cgi-bin/{interface}.cfg/scripts/cp/generated/{output}/temp_source -> source
     *      /www/rnt/{site}/vhosts/{interface}/euf/generated/staging/staging_01/temp_source -> source (optionally)
     */
    private function moveTempSourceToOutputSource() {
        $directories = array(array($this->options->getTempSourceDir(), $this->options->getOutputSourceDir()));
        $this->appendAssetsSourceDirs($directories, false);

        foreach ($directories as $paths) {
            list($sourceDir, $targetDir) = $paths;
            $this->writeMessage('<hr size=1 />', sprintf(Config::getMessage(MOVING_CONTENTS_OF_PCT_S_TO_PCT_S_LBL), $this->shortenPathName($sourceDir), $this->shortenPathName($targetDir)));
            $this->moveDirectory($sourceDir, $targetDir);
        }
    }

    /**
     * When staging, or upgrading staging, append assets source dirs to $directories array.
     *
     * @param array &$directories Reference variable to append to.
     * @param bool $tempLast If true, append ({assets source dir}, {assets temp_source dir}), else append ({assets temp_source dir}, {assets source dir})
     */
    private function appendAssetsSourceDirs(array &$directories, $tempLast = true) {
        if (($source = $this->options->getAssetsSourceDir()) && ($tempSource = $this->options->getTempAssetsSourceDir())) {
            if ($tempLast) {
                $directories[] = array($source, $tempSource);
            }
            else {
                $directories[] = array($tempSource, $source);
            }
        }
    }

    /**
     * Move application/<output>/temp_optimized -> application/<output>/optimized
     */
    private function moveTempOptimizedToOutputDir() {
        $sourceDir = $this->options->getTempOptimizedDir();
        $targetDir = $this->options->getOutputOptimizedDir();
        $this->writeMessage('<hr size=1 />', sprintf(Config::getMessage(MOVING_CONTENTS_OF_PCT_S_TO_PCT_S_LBL), $this->shortenPathName($sourceDir), $this->shortenPathName($targetDir)));
        $this->moveDirectory($sourceDir, $targetDir);
    }

    /**
     * Copy /www/rnt/{site}/cgi-bin/{interface}.cfg/scripts/cp/generated/{output}/source -> temp_source
     *      /www/rnt/{site}/vhosts/{interface}/euf/generated/staging/staging_01/source -> temp_source (optionally)
     */
    private function copySourceFilesToTempSource() {
        $directories = array(array($this->options->getSourceDirForTempSource(), $this->options->getTempSourceDir()));
        $this->appendAssetsSourceDirs($directories);

        foreach ($directories as $paths) {
            list($sourceDir, $targetDir) = $paths;
            $this->writeMessage('<hr size=1 />', sprintf(Config::getMessage(COPYING_SRC_DIRECTORY_PCT_S_LBL), $this->shortenPathName($sourceDir), $this->shortenPathName($targetDir)));
            $this->delayedDelete($targetDir);
            FileSystem::copyDirectory($sourceDir, $targetDir);
        }
    }

    private function copySelectiveFilesToSource() {
        //TODO: preserve modification date.
        $files = $this->options->getSourceFilesToCopy();
        if (!is_array($files)) {
            throw new \Exception("sourceFilesToCopy is not an array.");
        }
        if (empty($files)) {
            return;
        }

        foreach ($files as $pairs) {
            list($source, $target) = $pairs;
            $target = $this->replaceSourceWithTempSource($target);
            $this->writeMessage('', sprintf(Config::getMessage(COPYING_PCT_S_TO_PCT_S_LBL), $this->shortenPathName($source), $this->shortenPathName($target)));
            if (!FileSystem::isWritableDirectory(dirname($target))) {
                FileSystem::mkdirOrThrowExceptionOnFailure(dirname($target), true);
            }

            if (!@copy($source, $target)) {
                $this->compileError(sprintf(Config::getMessage(FAILED_COPYING_FILE_PCT_S_PCT_S_MSG), $source, $target));
            }
        }
    }

    /**
     * Replace following source directories with temp_source:
     *     /www/rnt/{site}/cgi-bin/{interace}.cfg/scripts/cp/generated/staging/staging_01/source
     *     /www/rnt/{site}/vhosts/{interace}/euf/generated/staging/staging_01/source
     *
     * We copy user modified/specified files into temp_source so that if the staging deploy should fail,
     * the original source directory is left unchanged, and the files will remain different between
     * development and staging, allowing users to more easily identify the offending file(s).
     * @param string $path Path
     */
    private function replaceSourceWithTempSource($path) {
        $directories = array(array($this->options->getOutputSourceDir(), $this->options->getTempSourceDir()));
        $this->appendAssetsSourceDirs($directories);

        foreach ($directories as $paths) {
            list($search, $replace) = $paths;
            if (Text::beginsWith($path, $search)) {
                return str_replace($search, $replace, $path);
            }
        }

        return $path;
    }

    private function removeSelectiveFilesFromSource() {
        $files = $this->options->getSourceFilesToRemove();
        if (!is_array($files)) {
            throw new \Exception("sourceFilesToRemove is not an array.");
        }

        if (empty($files)) {
            return;
        }

        foreach ($files as $paths) {
            try {
                $matched = false;
                list($source, $target) = $paths;
                foreach($this->options->getStagingBaseDirectories() as $baseDirs) {
                    list($baseSource, $baseTarget) = $baseDirs;
                    if (Text::beginsWith($source, $baseSource) && Text::beginsWith($target, $baseTarget)) {
                        $matched = true;
                        $targetFile = Text::getSubstringAfter($target, $baseTarget);
                        foreach(Staging::removeFileAndPruneEmptyDirectories($baseSource, $this->replaceSourceWithTempSource($baseTarget), $targetFile) as $message) {
                            $this->writeMessage('', $message);
                        }
                    }
                }
                if (!$matched) {
                    throw new \Exception(Config::getMessage(NOT_A_VALID_TARGET_FOR_DELETION_LBL) . " " . $target);
                }
            }
            catch (\Exception $e) {
                $this->compileError($e->getMessage());
            }
        }
    }

    /**
     * Copy application/development/source -> application/<output>/temp_source
     * @param string $widgetType Type of widget
     * @param string $message Message to print to log
     */
    private function copyWidgetsToTempSource($widgetType, $message) {
        $sourceDir = CPCORE . $widgetType;
        $targetDir = $this->options->getTempSourceDir() . $widgetType;
        $this->writeMessage('<hr size=1 />', sprintf($message, $this->shortenPathName($sourceDir), $this->shortenPathName($targetDir)), '<br />');
        FileSystem::copyDirectory($sourceDir, $targetDir);
    }

    /**
     * Write user agent to page set mapping array from pageset model to /euf/application/<output>/temp_source/config/pageSetMapping.php
     */
    private function modifyConfigurations() {
        if ($this->options->shouldModifyPageSetConfiguration() && $mappings = $this->options->getPageSetMappings()) {
            $this->writeMessage('<hr size=1 />', Config::getMessage(WRITING_PAGE_SET_MAPPINGS_TO_FILE_MSG), '<br />');
            $content = $this->CI->model('Pageset')->getDeployedContent($mappings, $this->options->getShouldUseUnlockedPageSetValues());
            $path = $this->CI->model('Pageset')->getPageSetFilePath();
            $this->filePutContents($this->options->getTempSourceDir() . $path, $content);
        }
    }

    /**
     * Writes the combined custom view partials to
     * a deploy-timestamped file in the view partial
     * directory.
     */
    private function combineCustomViewPartials() {
        $this->filePutContents($this->options->getTempSourceDir() . 'views/Partials/' . $this->options->getTimestamp() . '.php',
            "<?\n" . SharedViewPartialOptimization::buildCustomSharedViewPartials());
    }

    /**
     * Writes the combined custom helpers to
     * a deploy-timestamped file in the helper
     * directory.
     */
    private function combineCustomSharedViewHelpers() {
        $this->filePutContents($this->options->getTempSourceDir() . 'helpers/' . $this->options->getTimestamp() . '.php',
            "<?\n" . WidgetHelperOptimization::buildCustomSharedHelpers());
    }

    /**
     * Copy application/rightnow/syndicated_widgets/standard -> application/<output>/temp_source/syndicated_widgets/standard
     */
    private function copyStandardSyndicatedWidgetsToTempSource() {
        $this->copyWidgetsToTempSource('syndicated_widgets/standard/', Config::getMessage(COPYING_STD_EXT_WIDGETS_PCT_S_TEMP_MSG));
    }

    /**
     * Modifies a subset of widget version changes declared within the staging environment
     */
    private function modifyVersions(){
        if(!$this->options->shouldPushVersionChanges())
            return;

        $tempSourceLocation = $this->options->getTempSourceDir();
        // Convert widgetVersions yaml into serialized php.
        $sourceDir = $this->options->getSourceDir();
        $files = array("widgetVersions" => $sourceDir === CUSTOMER_FILES ? "php" : null, "frameworkVersion" => null, "phpVersion" => null);
        foreach ($files as $file => $conversion){
            $source = $sourceDir . $file;
            $target = "$tempSourceLocation$file";

            if ($conversion) {
                $contents = \RightNow\Internal\Utils\Version::getVersionFile($source);
                \RightNow\Internal\Utils\Version::writeVersionFile($target, $contents, $conversion);
            }
            else if (FileSystem::isReadableFile($source)) {
                FileSystem::copyFileOrThrowExceptionOnFailure($source, $target, true);
            }
        }
    }

    /**
     * Copy application/<output>/temp_source -> application/<output>/temp_optimized
     */
    private function copyTempSourceToTempOptimized() {
        $sourceDir = $this->options->getTempSourceDir();
        $targetDir = $this->options->getTempOptimizedDir();
        $this->writeMessage('<hr size=1 />', sprintf(Config::getMessage(MOVING_CONTENTS_OF_PCT_S_TO_PCT_S_LBL), $this->shortenPathName($sourceDir), $this->shortenPathName($targetDir)));
        $this->delayedDelete($targetDir);
        FileSystem::copyDirectory($sourceDir, $targetDir);
    }

    /**
     * Copy application/<output>/temp_optimized/views/pages -> application/<output>/temp_optimized/views/headers
     *
     * At production, the pages are actually served out of the headers directory.
     * Devin gave them that name because the the "headers" aren't really pages
     * so much as combined pages, templates, and widgets.
     */
    private function copyPagesToHeaders() {
        $this->writeMessage('<hr size=1 />', Config::getMessage(COPYING_DIRECTORY_STRUCTURE_VIEWS_S_LBL));
        $sourceDir = $this->options->getTempOptimizedDir() . 'views/pages';
        $targetDir = $this->options->getTempOptimizedDir() . 'views/headers';
        FileSystem::copyDirectory($sourceDir, $targetDir);
    }

    private function reportStatusForPrepareDeploy() {
        $errorCount = count($this->compileErrors);

        // HMS depends on the <cps_error_count> tag being present to determine if a deployment succeeded.
        $this->writeMessage('<hr size=1 />', sprintf(Config::getMessage(NUMBER_OF_DEPLOY_ERRORS_PCT_S_LBL), $this->getCPErrorCountTags($errorCount)), '<br />');

        if ($errorCount > 0) {
            $this->writeMessage('<h3>', sprintf(Config::getMessage(COMP_FAIL_PLS_FIX_ERR_MARKED_PCT_S_MSG), "<font style='color: #FF0000;'>" . Config::getMessage(RD_LBL) . '</font>'), '...</h3>');
        }
        else {
            $this->writeMessage('', sprintf(Config::getMessage(LOG_FILE_PCT_S_LBL), $this->getCPLogFileNameTags()), '');
            $this->writeMessage('<h3>', Config::getMessage(COMPILE_SUCCESSFUL_LBL), '</h3>');
        }
    }

    private function getCPErrorCountTags($errorCount = null) {
        if ($errorCount === null) {
            $errorCount = count($this->compileErrors);
        }
        return Deployment::getCPErrorCountTags($errorCount);
    }

    private function getCPLogFileNameTags($logFileName = null) {
        if ($logFileName === null) {
            $logFileName = $this->options->getLogFileName();
        }
        return Deployment::getCPLogFileNameTags($logFileName);
    }

    private function writeLogHeader($message = null) {
        if ($this->hasWrittenLogHeader || !$this->options->getLogFileName()) {
            return;
        }
        if ($message === null) {
            $message = Config::getMessage(CPS_DEPLOYMENT_LOG_PREPARE_DEPLOY_LBL);
        }
        $this->logger->writeLogHeader($message,
            $this->options->getDeployType(),
            $this->options->getComment(),
            $this->options->getLogHeaderAccountInformation());
        $this->hasWrittenLogHeader = true;
    }

    private function writeMessage($leadingHtml, $message, $trailingHtml = '...<br />', $level = 'debug') {
        if(strtolower($level) === "warn") {
            array_push($this->compileWarnings, $message);
        }
        $this->logger->log($message, $level, $leadingHtml, $trailingHtml);
    }

    /**
     * Ensures that the specified file exists.  If it doesn't, a compile error is written
     * @param string $basePath Is prepended to $path when searching for the file.
     * @param string $path Is suffixed to $basePath and is the only bit included in the compile error message.
     * @return bool True if file exists, else false.
     */
    private function ensureFileExists($basePath, $path) {
        assert(strlen($path) > 0);
        if (!is_readable($basePath . $path)) {
            $this->compileError(sprintf(Config::getMessage(REQD_FILE_PCT_S_FND_PCT_S_FILE_EX_MSG), $path, $basePath));
            return false;
        }
        return true;
    }

    /**
     * Ensures that all required files are present before deploy.
     */
    private function validateBeforeDeploy() {
        $this->writeMessage('<hr size=1 />', Config::getMessage(VALIDATING_FILES_LBL));
        $this->verifyDeployLock();
        $this->validateRequiredFiles();
        $this->ensureProductionDirectoryIsWritable();
        $this->prepareTempOptimizedJavaScriptDir();
    }

    private function ensureProductionDirectoryIsWritable() {
        $eufApplicationDir = dirname($this->options->getOutputBaseDir());
        if (!FileSystem::isWritableDirectory($this->options->getOutputBaseDir()) && !FileSystem::isWritableDirectory($eufApplicationDir)) {
            $this->compileError(sprintf(Config::getMessage(NEITHER_PRODUCTION_DIRECTORY_PCT_S_MSG), $this->options->getOutputBaseDir()));
        }
    }

    private function validateRequiredFiles() {
        foreach (self::$requiredFilesInSourceDir as $file) {
            $this->ensureFileExists($this->options->getSourceDir(), $file);
        }

        //$otherFiles = array('/euf/config/splash.html');
        $otherFiles = array('/cp/customer/development/errors/splash.html', '/cp/customer/development/errors/error500.html');
        foreach ($otherFiles as $file) {
            $this->ensureFileExists($this->options->getScriptsBaseDir(), $file);
        }
    }

    private function validateHooksFileFormat() {
        $source = $this->options->getTempSourceDir();
        // Check the existence of hooks prior to 'require_once' below in case it does not exist in temp_source
        if (!$this->ensureFileExists($source, 'config/hooks.php')) {
            return;
        }

        // Some customers have copied the framework module Libraries/Hooks.php over their
        // development copy of config/hooks.php. Check for common code in Libraries/Hooks.php.
        $commonCodeToLookFor = array("namespace RightNow",
                                     "use RightNow",
                                     "class ",
                                     "public static ",
                                     "private static");

        $fHooks = fopen("{$source}config/hooks.php", "r");
        if (!$fHooks) {
            return;
        }

        $lineNum = 0;
        $hasPhpCode = false;
        while (!feof($fHooks)) {
            $line = fgets($fHooks);
            $lineNum++;
            foreach ($commonCodeToLookFor as $codeChunk) {
                if (Text::beginsWith(trim($line), $codeChunk)) {
                     $this->compileError(sprintf(Config::getMessage(NXPCT_CC_FN_LN_CNFGHKS_CNT_HK_DFS_RNHKS_MSG), $lineNum));
                     $hasPhpCode = true;
                     break;
                }
            }
        }

        fclose($fHooks);
        // Don't load config/hooks.php if it contains PHP code. Bad things can happen.
        if ($hasPhpCode === true) {
            return;
        }

        require_once "{$source}config/hooks.php";
        $modelsUsedInHookCalls = array();
        if (isset($rnHooks) && is_array($rnHooks)) {
            foreach ($rnHooks as $which => $val) {
                if (!is_array($val[0])) {
                    $val = array($val);
                }
                foreach ($val as $hookDef) {
                    $valid = \RightNow\Libraries\Hooks::validateHook($which, $hookDef);
                    if ($valid !== true) {
                        $this->compileError($valid);
                    }
                    else {
                        $modelLocation = $hookDef['filepath'] . '/' . $hookDef['class'] . '.php';
                        if (!$modelsUsedInHookCalls[$modelLocation]) {
                            $modelsUsedInHookCalls[$modelLocation] = array();
                        }
                        array_push($modelsUsedInHookCalls[$modelLocation], array('function' => $hookDef['function'], 'hookName' => $which));
                    }
                }
            }
            //Validate that functions exist in models that hooks are going to call
            foreach ($modelsUsedInHookCalls as $model => $functionArray) {
                $modelContent = @file_get_contents("{$source}models/custom/$model");
                foreach ($functionArray as $function) {
                    if (!preg_match('/function\s+' . $function['function'] . '\s*\\(/i', $modelContent))
                        $this->compileError(sprintf(Config::getMessage(HOOK_ERR_HOOK_PCT_S_FUNC_PCT_S_EX_MSG), $function['hookName'], $function['function'], $model));
                }
            }
        }
    }

    /**
     * Write out the optimizedWidget.php files for all standard and custom widgets.
     */
    private function writeOptimizedStandardWidgets() {
        // While constructing the optimizedWidgets file, store relative widget paths in rnWidgetRenderCall tags.
        // During deploy, we rewrite the optimized page code with the resolved widget path to ensure it references
        // the appropriate widget (EG. 'custom/' instead of 'standard/'.
        foreach (array_keys(Registry::getStandardWidgets(false)) as $widgetPath) {
            if(!$widget = Registry::getWidgetPathInfo($widgetPath))
                continue;
            $this->writeOptimizedWidgetCode($widget);
        }
        //Now we need to run the script compile command on the generated optimizedWidget.php files since they have the potential
        //to contain non-replaced message/config defines. This is because some of the values were scraped out of the info.yml file, which
        //is never script compiled
        if ($this->options->shouldScriptCompile())
            $this->runMakeFile(CORE_FILES . 'widgets/standard/', CORE_FILES . 'widgets/standard/src', array('regex' => '@optimizedWidget\.php$@'));
    }

    /**
     * Write out the optimizedWidget.php files for all custom widgets.
     * @param string $targetDir Target directory
     */
    private function writeOptimizedCustomWidgets($targetDir) {
        foreach (Widgets::getDeclaredWidgetVersions($this->options->getTempSourceDir()) as $widgetPath => $version) {
            // Below we check for the existence of the widget's absolute path as it may have been deleted by the customer
            // in their dev environment and is being removed from staging in this deploy.
            // If that widget is still being referenced, an error will be thrown when compiling the pages.
            if(!($widget = Registry::getWidgetPathInfo($widgetPath)) || $widget->type !== 'custom' || !FileSystem::isReadableDirectory($widget->absolutePath)) {
                continue;
            }
            $this->writeOptimizedWidgetCode($widget, $targetDir);
        }
    }

    /**
     * Return an array of optimized/compiled widget information either from cache or generated at time of request.
     * @param PathInfo $widget The PathInfo object for the current widget
     * @return array Contains info for generating the widget's production code
     */
    public function populateWidgetArray(PathInfo $widget) {
        if(!$widget)
            // error should already be displayed elsewhere
            return;
        $this->writeMessage('', Config::getMessage(COMPILING_WIDGET_LBL) . $widget->relativePath, '<br />');

        $absolutePath = $widget->absolutePath;
        $controllerCode = $this->widgetControllerCache->get($widget);
        $meta = Widgets::getWidgetInfo($widget);
        if(!is_array($meta)) {
            $this->compileError(sprintf(Config::getMessage(PROBLEM_PCT_S_PCT_S_COLON_LBL), $widget->relativePath, $meta));
            return;
        }
        $meta = Widgets::convertAttributeTagsToValues($meta, array(
            'eval' => false,
            'omit' => array('name', 'description'),
        ), $this->options->shouldScriptCompile());
        if(is_string($meta)){
            $this->compileError($meta);
            return;
        }
        $widgetView = Registry::getWidgetPathInfo($meta['view_path']);
        $widgetContent = Tags::transformTags(($widgetView && ($widgetViewContent = @file_get_contents($widgetView->view))) ? $widgetViewContent : '');
        $meta['widget_name'] = $widget->className;

        // Build extends info
        if (isset($meta['extends_info']) && $meta['extends_info']) {
            if ($meta['extends_info']['controller']) {
                $extendsPhp = array();
                foreach ($meta['extends_info']['controller'] as $controllerFile) {
                    $extendsPhp[] = $controllerFile;
                }
                $meta['extends_php'] = $extendsPhp;

                /*
                 if (!$extendsInfo['view']) {
                    // If the widget doesn't state that it wants to extend parent's view,
                    // then just use parent's view and ignore widget's own view
                    if($parentWidget = Registry::getWidgetPathInfo($extendsInfo['parent']))
                        $widgetContent = @file_get_contents($parentWidget->view);
                    unset($meta['view_path']);
                }
                */
            }
            if ($meta['extends_info']['logic']) {
                $meta['extends_js'] = $meta['extends_info']['logic'];
            }
            if ($meta['extends_info']['view']) {
                $meta['extends_view'] = $meta['extends_info']['view'];
            }
            $meta['parent'] = $meta['extends_info']['parent'];
        }

        if((isset($meta['js_templates']) && $meta['js_templates']) || (isset($meta['extends_info']['js_templates']) && $meta['extends_info']['js_templates'])) {
            // process JS templates
            try {
                $meta['js_templates'] = WidgetViews::getExtendedWidgetJsViews($meta, $widget);
            }
            catch (\Exception $e) {
                $this->compileError($widget->relativePath . ' - ' . $e->getMessage());
                return;
            }

            foreach ($meta['js_templates'] as $name => $template) {
                // Wrap config & message slots w/ m4-ignore tags so that they aren't converted to defines
                // (js_templates is written out in the widget's optimized php file).
                $meta['js_templates'][$name] = Config::convertJavaScriptCompileSafeDefines($template);
            }
        }

        try {
            // combine or collapse rn:blocks in view(s)
            $widgetContent = WidgetViews::getExtendedWidgetPhpView($widgetContent, $meta, $widget);
        }
        catch (\Exception $e) {
            $this->compileError("$widget->relativePath - " . $e->getMessage());
            return;
        }

        return array(
            'meta'             => $meta,
            'header'           => $this->createWidgetHeaderFunction($widget, $meta),
            'view_code'        => $this->createWidgetViewCode($meta, $widgetContent),
            'sub_widgets'      => $this->getSubWidgetDependencies($widget),
            'controller_code'  => $controllerCode,
            'controller_class' => $widget->className,
        );
    }

    /**
     * For a given widget PathInfo, retrieves all sub-widgets
     * (widgets embedded in the widget's views), including their
     * own required parent dependencies.
     * @param PathInfo $widget Widget PathInfo
     * @return array           Values are relative paths of all
     *                                sub-widgets and their parents
     */
    private function getSubWidgetDependencies(PathInfo $widget) {
        $locator = new Locator($widget->relativePath, '');
        $locator->processWidget($widget, $widget->relativePath);
        $dependencies = $locator->getWidgets();

        array_shift($dependencies); // Remove $widget's own info.

        $subWidgets = array();
        foreach ($dependencies as $info) {
            $widgetPath = $info['meta']['relativePath'];
            if ($widgetPath !== $widget->relativePath) {
                // Skip the widget itself.
                $subWidgets []= $widgetPath;
            }

            if (isset($info['meta']['extends_info']) && $info['meta']['extends_info']) {
                // Add sub-widgets' own parent dependencies.
                foreach ($info['meta']['extends_info']['controller'] as $parent) {
                    if (!in_array($parent, $dependencies)) {
                        array_unshift($subWidgets, $parent);
                    }
                }
            }
        }

        return array_unique($subWidgets);
    }

    private function getPhpFiles($path, $fileToListFirst = null) {
        $files = FileSystem::listDirectory($path, false, true, array('function', function($f) {
            return $f->isFile() && ($file = $f->getFilename()) && Text::endsWith($file, '.php') && !Text::beginsWith($file, '._');
        }));
        sort($files);
        if ($fileToListFirst !== null && (($offset = array_search($fileToListFirst, $files)) !== false)) {
            array_splice($files, $offset, 1);
            array_unshift($files, $fileToListFirst);
        }
        return $files;
    }

    /**
     * Loop through each page assembling the various required pieces (templates, themes, assets, etc.).
     * @param string $sourceDir Source directory
     * @param string $targetDir Target directory
     */
    private function compilePages($sourceDir, $targetDir) {
        $this->writeMessage('<hr size=1 />', Config::getMessage(COMPILING_PAGES_LBL));
        if ($this->options->shouldSuppressPageErrors()) {
            define('SUPPRESS_PAGE_ERRORS', true); // used by addErrorToPageAndHeader() to prevent errors from being written to optimized pages.
        }
        $pagesDirectory = $sourceDir . '/views/pages';
        RightNowLogoCompliance::setPagesDirectory($pagesDirectory);
        $templatesDirectory = $sourceDir . '/views/templates';
        $this->themePathResolver = $this->options->getThemePathResolver();
        foreach ($this->getPhpFiles($pagesDirectory, $this->options->pageToObtainDefaultTemplate()) as $path) {
            $this->compilePage("$pagesDirectory/$path", $targetDir, $templatesDirectory, null, true);
        }
        if ($this->options->shouldCheckRightNowLogoCompliance()) {
            RightNowLogoCompliance::setConfig($this->options->shouldVerifyAllRightNowLogoPagesSet());
            foreach (RightNowLogoCompliance::nonCompliantPages() as $page) {
                $this->writeMessage('', sprintf(Config::getMessage(WARN_PG_RN_LOGO_COMPLIANT_PCT_S_MSG), $page), "<br />", 'warn');
            }
        }
    }

    /**
     * Combines files. Intended to be called after all of the files are copied to the appropriate temporary directories.
     */
    private function compile() {
        $sourceDir = $this->options->getTempSourceDir();
        $targetDir = $this->options->getTempOptimizedDir();
        $assetOrganizer = new AssetOrganizer($this->options->getStaticContentBaseDir());

        if (!$this->options->isRunningInHosting()) {
            // Only on dev sites and when creating the installation tarball.
            $this->combineCorePhpIncludes();
            $this->writeOptimizedJS($assetOrganizer);
            $this->combineSyndicatedIncludes();
            $this->writeOptimizedStandardWidgets();
        }
        if ($this->options->shouldCompileStandardWidgets()) {
            $this->writeOptimizedStandardWidgets();
        }

        $this->writeCustomizedJS($assetOrganizer);
        $this->writeOptimizedCustomWidgets($targetDir);
        $this->compilePages($sourceDir, $targetDir);
    }

    /**
     * Combines all core PHP files into one php file and
     * combine all core javascript files into one file
     */
    private function combineCorePhpIncludes() {
        $addPhpIncludes = function($filenames) {
            $combinedPhp = "<?php\n";
            foreach ($filenames as $filename) {
                $combinedPhp .= CodeWriter::modifyPhpToAllowForCombination(file_get_contents($filename));
            }
            return $combinedPhp;
        };
        file_put_contents(CPCORE . 'optimized_includes.php', $addPhpIncludes(\Rnow::getCorePhpIncludes()) . SharedViewPartialOptimization::buildSharedViewPartials() .
            WidgetHelperOptimization::buildStandardSharedHelpers());
        file_put_contents(CORE_FILES . 'compatibility/optimized_includes.php', $addPhpIncludes(\Rnow::getCoreCompatibilityPhpIncludes()));
    }

    /**
     * Verifies that the optimized_includes files have been minimized
     * @param array $filePaths Paths to optimized_includes files
     */
    private function verifyMinimizedOptimizedIncludes($filePaths) {
        foreach ($filePaths as $filePath) {
            preg_match_all('/^\s+/m', file_get_contents($filePath), $matches);
            if (count($matches[0]) >= 1000) {
                $this->compileError(sprintf(Config::getMessage(OPTIMIZEDINCLUDE_FILE_AT_NOT_MINIMIZED_MSG), $filePath));
            }
        }
    }

    /**
     * Combines the standard JS and writes it all out.
     * @param object $assetOrganizer Collection of JS assets to optimize
     */
    private function writeOptimizedJS($assetOrganizer) {
        FileSystem::removeDirectory($this->options->getStaticContentBaseDir() . Url::getCoreAssetPath('js'), false);

        foreach ($assetOrganizer->getAllFrameworkJS() as $assetFile) {
            $this->filePutContents($assetFile->optimizedPath, $assetFile->minify());
        }
    }

    /**
     * Combines the custom JS and writes it all out.
     * @param object $assetOrganizer Collection of JS assets to optimize
     */
    private function writeCustomizedJS($assetOrganizer) {
        if (($customJS = $assetOrganizer->getCustomAndDeprecated($this->options->getTempSourceDir(), $this->options->getTempOptimizedAssetsTimestampDir()))
            && strlen($jsContent = $customJS->minify(false))) {
            $this->includeAutoloadJavascript = true;
            $this->filePutContents($customJS->optimizedPath, $jsContent);
        }
    }

    /**
     * Aggregate syndicated widget assets.
     */
    private function combineSyndicatedIncludes() {
        $assetOrganizer = new AssetOrganizer($this->options->getStaticContentBaseDir());

        list($yuiFiles, $coreFile) = $assetOrganizer->getSyndicated();

        //Sandbox Yahoo to RNOW object
        $compiledJS = "var RightNow = (function() {\nif(typeof RightNow != 'undefined')\nYAHOO=RightNow;\n";
        foreach ($yuiFiles as $file) {
            $compiledJS .= file_get_contents($this->options->getStaticContentBaseDir() . $file);
        }
        $compiledJS .= "\nreturn YAHOO;})();\n";

        $this->filePutContents($coreFile->optimizedPath, $compiledJS . $coreFile->minify(false));
    }

    /**
     * Builds up the command line which performs the venerable "script compile"
     * process, which replaces defines and minifies PHP files.
     * @param string $phpSourceDir Directory to run make command on
     */
    private function getDefineReplacementMakeCommand($phpSourceDir) {
        $makefile = $this->options->getScriptsBaseDir() . 'makefile_php';
        if (!FileSystem::isReadableFile($makefile)) {
            $this->compileError(Config::getMessage(PHP_DEF_REPLACEMENT_FILE_MAKEFILE_MSG));
            return false;
        }
        $make = 'make';
        if ($this->options->isRunningInHosting()) {
            $utilityDir = get_cfg_var('rnt.utility_dir');
            $make = "$utilityDir/$make";
            if (!is_file($make) || !is_executable($make)) {
                $this->compileError(Config::getMessage(MAKE_BINARY_WAS_NOT_FOUND_MAKE_MSG));
                return false;
            }
            $cmdbuf = "PATH=$utilityDir ";
        }
        else {
            $cmdbuf = 'PATH=/nfs/local/linux/bin/:$PATH ';
        }
        //Use half the number of cores on the current box (if that information is available)
        $numberOfCpuCores = isset($_ENV['RNT_CPU_CORES']) && $_ENV['RNT_CPU_CORES'] ? round((int)$_ENV['RNT_CPU_CORES'] / 2) : 12;
        $parallelizeArgument = " -j $numberOfCpuCores";
        $cmdbuf .= "$make $parallelizeArgument -f $makefile -C $phpSourceDir";
        if (!$this->options->isRunningInHosting()) {
            $scriptsBaseDir = $this->options->getScriptsBaseDir();
            $cmdbuf .= " ADDITIONAL_PHPH_VPATH={$scriptsBaseDir}include/ ";
            $cmdbuf .= " SCRIPTS={$scriptsBaseDir} ";
            $cmdbuf .= " MAKEPATH={$scriptsBaseDir}make/ ";
        }
        // Merge stderr onto standard out so that PHP will capture any errors from make.
        $cmdbuf .= ' 2>&1 ';
        return $cmdbuf;
    }

    /**
     * Run the makefile on the temp_optimized/src directory that will replace
     * defines and copy the files back up a directory into the temp_optimized/.
     *
     * Actually runs the commandline created by getDefineReplacementMakeCommand().
     * @param string $sourceDirectory Location of source directory in which to run makefile
     * @param string $targetDirectory Location to put output of running makefile
     * @param array|null $fileFilter Filter to run on files
     */
    private function runMakeFile($sourceDirectory, $targetDirectory, $fileFilter = array('php', 'phph')) {
        $this->cleanupMakeFiles($targetDirectory);

        //remove the views/default dir from temp_optimized
        FileSystem::removeDirectoryRecursivelyOrThrowExceptionOnFailure($this->options->getTempOptimizedDir() . 'views/default', true);

        $this->writeMessage('<hr size=1 />', sprintf(Config::getMessage(MOVING_CONTENTS_OF_PCT_S_TO_PCT_S_LBL), $this->shortenPathName($sourceDirectory), $this->shortenPathName($targetDirectory)));

        if (!$this->renameFilesAndFoldersToBeShellSafe($sourceDirectory)) {
            return;
        }

        FileSystem::moveDirectory($sourceDirectory, "$targetDirectory/", $fileFilter);

        $this->copyMakeFiles($targetDirectory);

        $cmdbuf = $this->getDefineReplacementMakeCommand($targetDirectory);
        if (!$cmdbuf) {
            return;
        }
        $this->writeMessage('', Config::getMessage(EXECUTING_MAKE_COMMAND_LBL) . ": $cmdbuf", '<br />');
        $error = $this->runScriptCompile($cmdbuf);
        if ($error) {
            $this->compileError(sprintf(Config::getMessage(SCRIPT_COMP_REPLACEMENT_DEFINES_ENC_LBL), $error));
            // TODO this is temporary.  I need to avoid cleaning up so I can figure out why it's failing.
            return;
        }

        $this->cleanupMakeFiles($targetDirectory);
        $this->renameFilesAndFoldersToRestoreSpaces($sourceDirectory);
    }

    /**
     * Copy the makefiles from application/makefiles to application/<output>/temp_optimized/src.
     * @param string|null $destinationDirectory Location of directory
     */
    private function copyMakeFiles($destinationDirectory = null) {
        $srcdir = $this->options->getScriptsBaseDir() . 'cp';
        if($destinationDirectory === null)
            $destinationDirectory = $this->options->getTempOptimizedDir() . 'src';
        foreach (array('make.moddefs', 'mod_info.phph') as $file) {
            if (is_readable("$srcdir/src/$file") && $file === 'mod_info.phph') {
                // Updated condition as a part of PHP8 as constants during optimise process need to be refrenced from src/mod_info.phph
                // I'm so sorry for this nasty hack.  We need to slurp in the non-script-compiled
                // mod_info.phph so that the defines are replaced correctly in custom widgets.
                $sourcePath = "$srcdir/src/$file";
            }
            else {
                $sourcePath = "$srcdir/$file";
            }
            if (!@copy($sourcePath, "$destinationDirectory/$file")) {
                $this->compileError(sprintf(Config::getMessage(FAILED_COPYING_FILE_PCT_S_PCT_S_MSG), "$srcdir/$file", "$destinationDirectory/$file"));
            }
        }
    }

    /**
     * Creates a very likely unique name for a file whose name contains spaces.
     * @param string $name Name of file
     */
    private function createShellSafeFilename($name) {
        $dirname = dirname($name);
        $filename = preg_replace(self::SHELL_UNSAFE_FILENAME_REGEX, '', basename($name));
        $randomNumber = rand(10000000, 99999999);
        $this->renamedCounter++;
        return "$dirname/" . $this->options->getTimestamp() . "-$randomNumber-{$this->renamedCounter}-$filename";
    }

    /**
     * The script compile can't handle spaces or other characters that bash
     * treats specially in the paths of files, so before running it, I rename
     * all of the files to unique, non-space containing paths, which I later
     * restore.
     *
     * @param string $dir String which contains a path which will be recursively searched for names containing spaces.
     * @return bool Indicating success.
     * @throws \Exception If $dir isn't valid
     */
    private function renameFilesAndFoldersToBeShellSafe($dir) {
        if (!is_dir($dir)) {
            throw new \Exception("The argument to renameFilesAndFoldersToBeShellSafe() must specify a directory.  \$dir=$dir");
        }
        if (!Text::beginsWith($dir, '/')) {
            throw new \Exception("The argument to renameFilesAndFoldersToBeShellSafe() must be an absolute path.  \$dir=$dir");
        }
        if (Text::stringContains(dirname($dir), ' ')) {
            throw new \Exception("The argument to renameFilesAndFoldersToBeShellSafe() must not contain a space in its base path.  \$dir=$dir");
        }
        $this->shellSafeFilenameMap = array();
        $stack = array($dir);
        while (($current = array_pop($stack)) !== null) {
            if (preg_match(self::SHELL_UNSAFE_FILENAME_REGEX, $current) > 0) {
                $newName = $this->createShellSafeFilename($current);
                if (!@rename($current, $newName)) {
                    $this->compileError(sprintf(Config::getMessage(RENAME_PCT_S_PCT_S_PREPARATION_CMD), $current, $newName));
                    return false;
                }
                $this->shellSafeFilenameMap[$newName] = $current;
                $current = $newName;
            }
            if (is_dir($current)) {
                foreach (FileSystem::getSortedListOfDirectoryEntries($current) as $child) {
                    array_push($stack, $current . '/' . $child);
                }
            }
        }
        $this->spaceRemovalOriginalNames = array();
        $this->spaceRemovalNewNames = array();
        foreach ($this->shellSafeFilenameMap as $newName => $originalName) {
            array_push($this->spaceRemovalOriginalNames, basename($originalName));
            array_push($this->spaceRemovalNewNames, basename($newName));
        }
        return true;
    }

    private function renameFilesAndFoldersToRestoreSpaces() {
        foreach (array_reverse($this->shellSafeFilenameMap, true) as $renamed => $original) {
            if (!@rename($renamed, $original)) {
                $this->compileError(sprintf(Config::getMessage(RENAME_PCT_S_PCT_S_RUNNING_SCRIPT_CMD), $renamed, $original));
            }
        }
    }

    private function beautifyScriptCompileOutput($output) {
        $output = rtrim($output);
        $output = $this->internationalizeScriptCompileOutput($output);
        $output = $this->restoreSpacesToScriptCompileOutput($output);
        return $output;
    }

    private function internationalizeScriptCompileOutput($output) {
        $output = preg_replace('@^Processing /@m', Config::getMessage(PROCESSING_LBL) . ' ', $output);
        $output = preg_replace('@^make.*(modification time in the future|Clock skew detected).*$@', '', $output);
        return $output;
    }

    private function restoreSpacesToScriptCompileOutput($output) {
            return str_replace($this->spaceRemovalNewNames, $this->spaceRemovalOriginalNames, $output);
    }

    /**
     * Runs the script compile. Returns its error code.  Trickles its output to stderr
     * by using fancy proc_open() instead of something simpler.
     * @param string $command The command to execute
     */
    private function runScriptCompile($command) {
        $descriptorSpec = array(
            0 => array('pipe', 'r'), // stdin
            1 => array('pipe', 'w'), // stdout
            2 => array('pipe', 'w') // stderr
        );
        $process = proc_open($command, $descriptorSpec, $pipes);
        if (false === $process) {
            $this->compileError(Config::getMessage(UNABLE_RUN_SCRIPT_COMPILE_COMMAND_MSG) . "  $command");
            return -1;
        }
        while (!feof($pipes[1])) {
            $line = fgets($pipes[1]);
            $this->writeMessage('', $this->beautifyScriptCompileOutput($line), '<br />');
        }
        return proc_close($process);
    }

    /**
     * Extracts the "class name" of a widget's logic.js from the path to the logic.js file.
     * @param array $meta Array containing a parsed widget meta tag's information.
     */
    private function getJavascriptNameFromMeta(array $meta) {
        if (array_key_exists('js_path', $meta)) {
            return Widgets::getWidgetJSClassName($meta['js_path']);
        }
        return '';
    }

    /**
     * Creates a bit of PHP containing a function which returns the essential runtime
     * details of a widget.  That function is used in the production page to
     * create a widget instance.
     * @param PathInfo $widget PathInfo for the widget
     * @param array $meta Widget's meta data array
     * @return string 'header' function containing the widget's vital info that's intended to be dumped
     * at the top of each optimized page's php source.
     * @see RightNow\Internal\Utils\Widgets#createWidgetInProduction
     */
    private function createWidgetHeaderFunction(PathInfo $widget, array $meta) {
        //We have to convert the code in widget attribute values from strings so it gets evaluated when included
        //within a production page. This is fairly hacky.
        if(isset($meta['attributes'])){
            $widgetAttributes = $meta['attributes'];
            unset($meta['attributes']);
        }
        $writtenAttributes = (isset($widgetAttributes) && is_array($widgetAttributes)) ? CodeWriter::createArray($widgetAttributes, function($name, $attribute) {
            return $attribute->toString();
        }) : "array()";

        // Remove absolutePath since it's specific to the site the optimizedWidget file is built on
        // so it shows up as a modified file when installing a Service Pack.
        unset($meta['absolutePath']);

        $metaArray = var_export($meta, true);

        $generatedWidgetFunctionName = Widgets::generateWidgetFunctionName($widget, '_');

        return CodeWriter::createWidgetHeaderFunction("{$generatedWidgetFunctionName}header", array(
            'jsName'            => $this->getJavascriptNameFromMeta($meta),
            'className'         => $widget->className,
            'viewFunctionName'  => "{$generatedWidgetFunctionName}view",
            'metaArray'         => $metaArray,
            'writtenAttributes' => $writtenAttributes,
        ));
    }

    /**
     * The widget's view code is converted into a method on the widget's class that simply embeds the view content.
     * If the widget has any view partials, then that view content is also converted into widget methods.
     * If the widget extends any other widgets that have view partials, then pass thru methods that delegate
     * back to the parent are created.
     * @param array  $widgetMetaData Widget's meta data info
     * @param string $widgetContent  Widget's view.php content
     * @return string                   1 or more widget methods wrapping the view code
     */
    private function createWidgetViewCode(array $widgetMetaData, $widgetContent) {
        $createdPartials = array();
        $viewFunctionBaseName = Widgets::generateWidgetFunctionName($widgetMetaData['relativePath'], '_');
        $viewCode = array( CodeWriter::createViewMethodWrapper($viewFunctionBaseName . 'view', $widgetContent) );
        $viewName = function($prefix, $fileName) {
            return $prefix . Text::getSubstringBefore($fileName, '.');
        };

        if (isset($widgetMetaData['view_partials']) && $widgetMetaData['view_partials']) {
            foreach ($widgetMetaData['view_partials'] as $fileName) {
                $partialName = $viewName($viewFunctionBaseName, $fileName);
                $createdPartials[$partialName] = true;
                $viewCode []= CodeWriter::createViewMethodWrapper($partialName, WidgetViews::combinePartials($fileName, $widgetMetaData['relativePath']));
            }
        }

        if (isset($widgetMetaData['extends_view']) && $widgetMetaData['extends_view']) {
            // Create methods for all view partials that parents have. These methods will simply call thru to the immediate
            // parent's view partial-containing (or method that delegates to its parent) method.
            foreach ($widgetMetaData['extends_view'] as $parent) {
                // The widgets that extend another widget but override the inherited view list their own view in both `extends_view` and `extends_info.view`.
                if ($parent === $widgetMetaData['relativePath']) continue;

                $widget = $this->widgetArrayCache->get(Registry::getWidgetPathInfo($parent));

                if (isset($widget['meta']['view_partials']) && $widget['meta']['view_partials']) {
                    $parentViewName = Widgets::generateWidgetFunctionName($widget['meta']['relativePath'], '_');

                    foreach ($widget['meta']['view_partials'] as $fileName) {
                        $partialName = $viewName($viewFunctionBaseName, $fileName);

                        // If the partial was already created from above, that means that the widget extends another widget
                        // and extended this particular partial--so block substitution was performed and there's no need
                        // to create a parent pass-thru function for the partial.

                        if (!array_key_exists($partialName, $createdPartials)) {
                            // If the partial *wasn't* created from above, that means that the widget extends another widget,
                            // the extended widget has partials, but this particular one wasn't extended by the child.
                            // In that case, create a parent pass-thru function for the partial.
                            $createdPartials[$partialName] = true;
                            $viewCode []= CodeWriter::createPassThruViewMethod($partialName, $viewName($parentViewName, $fileName));
                        }
                    }
                }
            }
        }

        return implode("\n", $viewCode);
    }

    /**
     * Combines all of the widget header functions and the widget controller
     * classes into one big string.
     * @param array $widgetPaths List of all widgets for the page + template to generate production code for
     *                           Result from #indicateWidgetsReferencedByThisView
     * @return string Huge chunk of code for all widgets
     */
    private function createWidgetPageCode(array $widgetPaths) {
        $pageWidgetCode = array();
        $includedWidgets = array();
        foreach (array_keys($widgetPaths) as $widgetPath) {
            if($widgetPath === 'referencedBy' || (isset($includedWidgets[$widgetPath]) && $includedWidgets[$widgetPath]) || !($widget = Registry::getWidgetPathInfo($widgetPath))) {
                continue;
            }
            $widgetArray = $this->widgetArrayCache->get($widget);
            $controllerClass = $widgetArray['controller_class'];

            // include the widgets if there are multiple view extensions as well
            if (isset($widgetArray['meta']['extends_view']) && $widgetArray['meta']['extends_view']) {
                foreach ($widgetArray['meta']['extends_view'] as $requiredWidgetPath) {
                    if((isset($includedWidgets[$requiredWidgetPath]) && $includedWidgets[$requiredWidgetPath]) || !($requiredWidget = Registry::getWidgetPathInfo($requiredWidgetPath))) {
                        continue;
                    }
                    $includedWidgets[$requiredWidgetPath] = $requiredWidget;
                }
            }

            // now handle controllers, since this will re-assign widgetArray
            if(isset($widgetArray['meta']['extends_php']) && $widgetArray['meta']['extends_php']) {
                foreach ($widgetArray['meta']['extends_php'] as $requiredWidgetPath) {
                    if((isset($includedWidgets[$requiredWidgetPath]) && $includedWidgets[$requiredWidgetPath]) || !($requiredWidget = Registry::getWidgetPathInfo($requiredWidgetPath))) {
                        continue;
                    }
                    $widgetArray = $this->widgetArrayCache->get($requiredWidget);
                    $controllerClass = $widgetArray['controller_class'];
                    $includedWidgets[$requiredWidgetPath] = $requiredWidget;
                }
            }

            //Check if we need to include this widgets parent, since it might not have been included yet. For example, say Widget A
            //only has JavaScript and no controller and widget B extends from it, but only extends its JavaScript. In that scenario, we
            //won't include Widget A's auto-generated controller in the above check since 'extends_php' is not set.
            if(isset($widgetArray['meta']['parent']) && ($parentWidgetPath = $widgetArray['meta']['parent']) && !$includedWidgets[$parentWidgetPath] && ($parentWidget = Registry::getWidgetPathInfo($parentWidgetPath))){
                $includedWidgets[$parentWidgetPath] = $parentWidget;
            }

            if (!isset($includedWidgets[$widgetPath]) || !$includedWidgets[$widgetPath]) {
                $includedWidgets[$widgetPath] = $widget;
            }
        }
        foreach ($includedWidgets as $widgetPath => $widgetPathInfoObject) {
            $widgetCode = $this->requireOptimizedWidgetCode($widgetPathInfoObject);
            if($widgetCode)
                $pageWidgetCode[] = $widgetCode;
        }
        return implode("\n", $pageWidgetCode);
    }

    /**
     * Creates string for widget header function and the widget controller
     * classes into one big string.
     * @param PathInfo $widget The PathInfo object for the current widget
     * @return string Chunk of code for page to render widget
     * @throws \Exception If we couldn't match the widget controller class declaration
     */
    private function generateWidgetViewCode(PathInfo $widget) {
        $widgetArray = $this->widgetArrayCache->get($widget);
        if(!$widgetArray)
            // error should already have been logged
            return;

        $replacement = "\\0\n    " . $widgetArray['view_code'] . "\n";
        $matches = $this->getRenderCallPathList($widgetArray['view_code']);

        // Inject view method(s) into the widget's class.
        $widgetControllerCode = preg_replace($this->getWidgetClassDeclarationPattern($widgetArray['controller_class']), $replacement, $widgetArray['controller_code'], 1, $matchCount);
        if ($matchCount !== 1) {
            throw new \Exception("Couldn't match the widget class declaration regular expression to the {$widgetArray['controller_class']} controller.");
        }

        if (isset($widgetArray['meta']['parent']) && ($requiredWidgetPath = $widgetArray['meta']['parent']) && ($requiredWidget = Registry::getWidgetPathInfo($requiredWidgetPath)) &&
            (isset($widgetArray['meta']['extends_view']) && $widgetArray['meta']['extends_view']) && $widgetArray['meta']['view_path'] && $matches && (!isset($widgetArray['meta']['extends']['overrideViewAndLogic']) || !$widgetArray['meta']['extends']['overrideViewAndLogic'])) {
            // For a widget that's extending a parent view via rn:blocks, perform block replacement on any widgets
            // contained within the parent's view.
            $this->addExtendedViewToWidgetCode($widget, $matches, $widgetControllerCode);
        }

        $code = "<?\n" . CodeWriter::modifyPhpToAllowForCombination($widgetControllerCode . "\n" . $widgetArray['header']);
        $code .= $this->checkAndStripWidgetViewHelper($widget);
        return $code;
    }

    /**
     * Writes out an optimized php file containing the widget header function
     * and the widget controller code. This is needed for AJAX endpoints
     * hosted in widget controllers.
     * @param PathInfo $widget The PathInfo object for the current widget
     * @param string $targetDir The directory to write the files out to
     */
    private function writeOptimizedWidgetCode(PathInfo $widget, $targetDir = null) {
        $writer = new OptimizedWidgetWriter($widget, $targetDir);

        if (FileSystem::isReadableFile($widget->logic)) {
            $writer->writeJavaScript($this->getMinifiedJavaScript($widget->logic));
        }
        if ($phpCode = $this->generateWidgetViewCode($widget)) {
            $widgetArray = $this->widgetArrayCache->get($widget);
            $writer->writePhp($phpCode, $widgetArray['sub_widgets']);
        }
    }

    /**
    * Adds code at the top of $widgetControllerCode; takes care to ensure that any
    * namespace declarations in $widgetControllerCode remain the first line in the file.
    * @param string $widgetControllerCode Widget's controller code
    * @param string $requires Code to insert at the top; presumably a require_once statement
    * @return string The code with $requires added to the top
    */
    private function addPHPRequire($widgetControllerCode, $requires) {
        $changed = preg_replace('/namespace\s+[\w\\\\]+\s*[;{]/', "\\0\n$requires", $widgetControllerCode, 1, $count);
        if ($count === 0) {
            return "$requires\n$widgetControllerCode";
        }
        return $changed;
    }

    /**
     * Returns the require_once call for the widget.
     * @param PathInfo $widget The PathInfo object for the current widget.
     * @return string The require_once call.
     */
    private function requireOptimizedWidgetCode(PathInfo $widget) {
        return CodeWriter::modifyPhpToAllowForCombination($this->generateWidgetViewCode($widget)) . "\n";
    }

    /**
     * A widget (let's call it X) is extending another widget's view that contains widget calls in it.
     * Apply the rn:block replacement on those sub-widgets' views, save the customized views
     * (if any of X's blocks were inserted into the views) as additional _view() methods
     * in X's optimized code, and modify the renderWidget calls in X's main view so that
     * those instances know to use the customized view for rendering.
     * @param PathInfo $widget The PathInfo object for the current widget
     * @param array|null $subWidgets Contains all sub-widgets; ea. item is a relative path
     * @param string &$widgetControllerCode String Controller code for the widget. Modified if there are
     *  changes to be made
     * @return boolean Whether any replacements were made or not
     */
    private function addExtendedViewToWidgetCode(PathInfo $widget, $subWidgets, &$widgetControllerCode) {
        $codeToAdd = '';
        $originalExtendingView = file_get_contents($widget->view);
        $seenSubWidgets = array();
        foreach ($subWidgets as $subWidgetPath) {
            if((!$subWidget = Registry::getWidgetPathInfo($subWidgetPath)) || (isset($seenSubWidgets[$subWidgetPath]) && $seenSubWidgets[$subWidgetPath] === true) || !FileSystem::isReadableFile($subWidget->view)) {
                continue;
            }
            $seenSubWidgets[$subWidgetPath] = true;
            $widgetViewToProcess = Tags::transformTags(file_get_contents($subWidget->view));
            $results = WidgetViews::combinePHPViews($subWidget->className, $widgetViewToProcess, $originalExtendingView, true);
            if ($results['replacementsMade'] > 0) {
                $customizedContent = $results['result'];
                $className = Widgets::generateWidgetFunctionName($widget) . Widgets::generateWidgetFunctionName($subWidget, '_customized_view');
                $viewFunctionName = Widgets::generateWidgetFunctionName($subWidget, '_view');
                $extendee = $subWidget->namespacedClassName;
                $codeToAdd .= "\nclass $className extends $extendee{\n"
                    . 'function constructor($attrs){parent::__construct($attrs);}'
                    . "\nfunction getData(){parent::getData();}\n"
                    . "function {$viewFunctionName}(\$data){extract(\$data); ?>$customizedContent<?}\n}";
                $widgetControllerCode = $this->addNewLibraryNameToResolvedWidgetRenderCalls($widgetControllerCode, $subWidget->relativePath, '\\' . $widget->namespace . '\\' . $className);
            }
        }
        if ($codeToAdd) {
            $widgetControllerCode .= $codeToAdd;
            return true;
        }
        return false;
    }

    /**
     * Adds a parameter to the rnWidgetRenderCall calls containing the specified widget path.
     * @param string $viewCode The code to perform the replacement on
     * @param string $widgetPath Resolved widget path used as the first argument to rnWidgetRenderCall
     * @param string $libraryName Library / class name to add as the last parameter to the call
     * @return string The $viewCode with the replacement made (or not)
     */
    private function addNewLibraryNameToResolvedWidgetRenderCalls($viewCode, $widgetPath, $libraryName) {
        return preg_replace_callback(str_replace('(.*?)', $widgetPath, self::RN_WIDGET_RENDER_CALL_PATTERN), function($match) use($libraryName) {
            return str_replace(');', ",'$libraryName');", $match[0]);
        }, $viewCode);
    }

    /**
     * Replaces all rnWidgetRenderCall calls containing relative paths to widgets with the resolved paths to widgets.
     * @param  string $viewCode The code to perform the replacements on
     * @return string $replaced View code containing resolved paths
     */
    private function replaceRelativeWidgetRenderCallsWithResolved($viewCode) {
        $replaced = preg_replace_callback(self::RN_WIDGET_RENDER_CALL_PATTERN, function($match) {
            list($rnWidgetRenderCall, $relativeWidgetPath) = $match;
            $resolvedWidget = Registry::getWidgetPathInfo($relativeWidgetPath);

            if ($resolvedWidget && ($resolvedWidgetPath = $resolvedWidget->relativePath)
                && ($resolvedWidgetPath !== $relativeWidgetPath)) {
                return str_replace("('$relativeWidgetPath',", "('$resolvedWidgetPath',", $rnWidgetRenderCall);
            }
            return $rnWidgetRenderCall;
        }, $viewCode);

        return $replaced;
    }

    /**
    * Searches widget view code for rnWidgetRenderCalls and adds call paths to $matches array
    * @param  string $viewCode The view code to perform matches on
    * @return array  extracted paths from rnWidgetRenderCalls calls
    */
    private function getRenderCallPathList($viewCode) {
        return preg_match_all(self::RN_WIDGET_RENDER_CALL_PATTERN, $viewCode, $matches) ? $matches[1] : array();
    }

    /**
     * Reads the widget controller and:
     * - Strips off opening and closing PHP tags
     * - Ensures that the class has the expected class name
     * - Ensures that the parent constructor is called
     * Note: cannot make 'private' or 'protected' as is sent to SizeLimitedCache.
     * @param PathInfo $widget The PathInfo object for the current widget
     */
    function checkAndStripWidgetController(PathInfo $widget) {
        if (!FileSystem::isReadableFile($widget->controller)) {
            return Widgets::getEmptyControllerCode($widget, false);
        }

        $controllerContent = CodeWriter::deleteClosingPHP(CodeWriter::deleteOpeningPHP(@file_get_contents($widget->controller)));

        //This is a simple check to see if the controller defines the class name we're expecting. It would
        //be nice to have a better check then a regex (since the search text could be in a comment), but this will
        //cover almost all cases.
        if (!preg_match($this->getWidgetClassDeclarationPattern($widget->className), $controllerContent)) {
            $this->compileError(sprintf(Config::getMessage(WIDGET_PCT_S_CONTROLLER_CLASS_NAME_MSG), $widget->relativePath));
            return '';
        }

        if (!$this->checkWidgetControllerConstructor($controllerContent, $widget->className)) {
            $this->compileError(sprintf(Config::getMessage(WIDGET_CONTROLLER_CONSTRUCTOR_ARG_MSG), $widget->relativePath));
            return '';
        }

        return $controllerContent;
    }

    /**
     * If the given widget has a view helper, it is retrieved,
     * modified for combination and checked for classname conformance.
     * @param  PathInfo $widget Widget pathinfo instance
     * @return string           View helper code
     */
    private function checkAndStripWidgetViewHelper (PathInfo $widget) {
        $widgetArray = $this->widgetArrayCache->get($widget);
        if (isset($widgetArray['meta']['view_helper']) && $widgetArray['meta']['view_helper']) {
            $content = CodeWriter::modifyPhpToAllowForCombination(@file_get_contents($widget->absolutePath . '/' . $widgetArray['meta']['view_helper']));
            if (!is_string($expectedClassName = WidgetHelperOptimization::validateWidgetHelperContents($widget, $content))) {
                return $content;
            }
            $this->compileError(sprintf(Config::getMessage(VIEW_HELPER_WIDGET_MUST_HAVE_CLAS_S_LBL), $widget->relativePath, $expectedClassName));
        }

        return '';
    }

    /**
     * If the controller has a constructor, then we want to make sure that it
     * takes at least one argument and passes that to the parent constructor.
     * Not having a construtor is also OK because the widget will inherit a
     * constructor which does the right thing.
     *
     * This has the weakness of using regular expressions to parse a programming language.
     * It's going to be tripped up by simple things like comments.
     *
     * @param string $controllerContent The code from the widget controller file.
     * @param string $widgetClassName The name of the widget class
     * @return boolean True if the widget controller is OK; false if it's not.
     */
    private function checkWidgetControllerConstructor($controllerContent, $widgetClassName) {
        if (!preg_match("@function\s+(?:$widgetClassName|__construct)\s*[(]@i", $controllerContent)) {
            return true;
        }

        if (preg_match("@function\s+(?:$widgetClassName|__construct)\s*[(]([^),]+)@i", $controllerContent, $matches)) {
            $argument = preg_quote(trim($matches[1]), '@');
            if (preg_match("@parent::__construct\s*[(]\s*$argument@i", $controllerContent)) {
                return true;
            }
        }
        return false;
    }

    private function getWidgetClassDeclarationPattern($widgetClassName) {
        return "/class\s+$widgetClassName\s+extends\s+([A-Za-z0-9\\\\_]+)[^{]*{/i";
    }

    /**
     * Records a compilation error message.
     *
     * @param string $str Error message.
     * @param bool $allowDuplicates Whether to allow duplicate error messages
     */
    private function compileError($str, $allowDuplicates = true) {
        // 090401-000008.  I considered a lot of options and decided to do a dirty
        // thing and include the <font> tag in the log written to disk.
        if ($allowDuplicates === true || !in_array($str, $this->compileErrors)) {
            $this->writeMessage('', Config::getMessage(ERROR_CAPS_LBL) . " - $str", "<br />", 'error');
            array_push($this->compileErrors, $str);
        }
    }

    /**
     * Delete the temp_optimized/src directory, if it exists.
     * @param string|null $toDelete Path to delete
     */
    private function cleanupMakeFiles($toDelete = null) {
        if($toDelete === null) {
            $toDelete = $this->options->getTempOptimizedDir() . 'src';
        }
        if (is_readable($toDelete)) {
            FileSystem::removeDirectoryRecursivelyOrThrowExceptionOnFailure($toDelete, true);
        }
    }

    /**
     * Takes combined page and meta content, and adds the various JavaScript components to the page. This includes the core framework JavaScript
     * as well as any of the page and template level JavaScript needed for the page.
     *
     * @param string $text Combined content of page and template
     * @param string $templateJavaScriptFile Name/path of template JavaScript file to include on page
     * @param array|null $templateJavaScriptFileInfo Array of JavaScript files included in template
     * @param string $pageJavaScriptFile Name/path of page JavaScript file to include on page
     * @param array|null $pageJavaScriptFileInfo Array of JavaScript files included in page
     * @param array|null $pageMessageBaseEntries List of messagebase values used within JavaScript on this page
     * @param array|null $pageConfigBaseEntries List of configbase values used within JavaScript on this page
     * @param array|null $meta Combined meta information from the page and template
     * @param string $optimizedAssetsDir Location to override default include location of assets
     * @param boolean $includeAdditionalJSFiles Denotes if autoloaded/deprecated JavaScript files should potentially be added to the page
     *
     * @return The content of the page with all JavaScript includes added.
     */
    private function addIncludes($text, $templateJavaScriptFile, $templateJavaScriptFileInfo, $pageJavaScriptFile, $pageJavaScriptFileInfo, $pageMessageBaseEntries, $pageConfigBaseEntries, $meta, $optimizedAssetsDir, $includeAdditionalJSFiles)
    {
        static $yuiConfiguration;
        $script = '';
        $javascriptModule = strtolower(isset($meta['javascript_module']) ? $meta['javascript_module'] : '');
        if($javascriptModule !== ClientLoader::MODULE_NONE) {
            $outputPath = "\RightNow\Utils\Url::getCoreAssetPath('js/" . MOD_BUILD_SP . '.' . MOD_BUILD_NUM . "/min/%s')";
            $echoOutputPath = "<?=$outputPath;?>";

            if (!$yuiConfiguration) {
                $staticYuiConfig = CodeWriter::createArray(ClientLoader::getCustomYUIModules(), function ($name, $value) use ($outputPath) {
                    return sprintf($outputPath, Text::getSubstringAfter($value, Url::getCoreAssetPath('debug-js/')));
                });
                $staticYuiConfig = CodeWriter::createArray(array('modules' => $staticYuiConfig));
                $yuiConfiguration = '<?=get_instance()->clientLoader->getYuiConfiguration(' . $staticYuiConfig . ');?>';
            }

            $script = $yuiConfiguration;

            if(!$javascriptModule || $javascriptModule === ClientLoader::MODULE_STANDARD)
                $script .= CodeWriter::scriptTag(sprintf($echoOutputPath, "RightNow.js"));
            else if($javascriptModule === ClientLoader::MODULE_MOBILE)
                $script .= CodeWriter::scriptTag(sprintf($echoOutputPath, "RightNow.Mobile.js"));

            if (isset($meta['include_chat']) && $meta['include_chat'])
                $script .= CodeWriter::scriptTag(sprintf($echoOutputPath, "RightNow.Chat.js"));
        }

        if ($templateJavaScriptFile) {
            $script .= CodeWriter::scriptTag(($optimizedAssetsDir ?: $this->getOptimizedAssetsFunctionPath())
                . 'templates'
                . "<?=\RightNow\Utils\Framework::calculateJavaScriptHash("
                . $this->calculateJavaScriptPaths($templateJavaScriptFileInfo)
                . ", '$templateJavaScriptFile', '" . $this->options->getTimestamp() . "');?>");
        }

        if ($pageJavaScriptFile) {
            $script .= CodeWriter::scriptTag(($optimizedAssetsDir ?: $this->getOptimizedAssetsFunctionPath())
                . 'pages'
                . "<?=\RightNow\Utils\Framework::calculateJavaScriptHash("
                . $this->calculateJavaScriptPaths($pageJavaScriptFileInfo)
                . ", '$pageJavaScriptFile', '" . $this->options->getTimestamp() . "');?>");
        }

        if ($javascriptModule !== ClientLoader::MODULE_NONE)
            $script .= '<?get_instance()->clientLoader->convertWidgetInterfaceCalls(' . var_export($pageMessageBaseEntries, true) . ', ' . var_export($pageConfigBaseEntries, true) . ");?>\n";

        if (!$javascriptModule || $javascriptModule === ClientLoader::MODULE_STANDARD)
        {
            if($includeAdditionalJSFiles && $this->includeAutoloadJavascript){
                $script .= CodeWriter::scriptTag($this->getOptimizedAssetsFunctionPath("custom/autoload.js"));
            }

            $script .= '<?=get_instance()->clientLoader->getClientInitializer();?>';
            if (isset($this->sandboxedConfigs['js']) && $this->sandboxedConfigs['js']) {
                $script .= ClientLoader::runtimeAdditionalJavaScriptPlaceholder;
                $text = Tags::insertAfterTag($text, $script, Tags::OPEN_BODY_TAG_REGEX);
                $text = Tags::insertBeforeTag($text, '<script type="text/javascript"><?=get_instance()->clientLoader->getWidgetInstantiationCode();?></script>', Tags::CLOSE_BODY_TAG_REGEX);
            }
            else {
                $script .= '<?=get_instance()->clientLoader->getAdditionalJavaScriptReferences();?>';
                $text = Tags::insertBeforeTag($text, $script, Tags::CLOSE_BODY_TAG_REGEX);
            }
        }
        else if ($javascriptModule === ClientLoader::MODULE_NONE) {
            $script .= '<?=get_instance()->clientLoader->getAdditionalJavaScriptReferences();?>';
            $text = Tags::insertBeforeTag($text, $script, Tags::CLOSE_BODY_TAG_REGEX);
        }
        else {
            $script .= '<?=get_instance()->clientLoader->getClientInitializer();?><?=get_instance()->clientLoader->getAdditionalJavaScriptReferences();?>';
            $text = Tags::insertBeforeTag($text, $script, Tags::CLOSE_BODY_TAG_REGEX);
        }

        // Random string will be added to a user page, everytime it's refreshed, if a user is logged in on https to mitigate BREACH attack
        $text = Tags::insertBeforeTag($text, '<?=\RightNow\Utils\Text::getRandomStringOnHttpsLogin();?>', Tags::CLOSE_BODY_TAG_REGEX);

        return $text;
    }

    /**
     * Creates the name for a page or template's javascript file.
     * @param string $pageName Name of page
     */
    private function getJavascriptFileName($pageName) {
        return str_replace('.php', '.js', $pageName);
    }

    /**
     * Creates the name for a page or template's CSS file.
     * @param string $pageName Name of page
     * @param string $themePath Path to page theme
     */
    private function getCssFileName($pageName, $themePath) {
        if ($themePath) {
            $themePath = '.' . str_replace('/', '.', $this->getThemePathRelativeToAssets($themePath));
        }
        return preg_replace('@[.]php$@', "{$themePath}.css", $pageName, 1);
    }

    /**
     * Attempts to write the data to the file.  Reports a compile error on failure.
     *
     * @param string $filename String containing a part to a file.
     * @param string $data String containing data to write to that file.
     */
    private function filePutContents($filename, $data) {
        $dir = dirname($filename);
        if (is_dir($dir) || $this->mkdirOrRecordError($dir)) {
            if (false === @file_put_contents($filename, $data)) {
                $this->compileError(sprintf(Config::getMessage(COULD_NOT_WRITE_TO_PCT_S_MSG), $filename));
            }
        }
    }

    /**
     * Combines together the pieces needed for a single page.
     *
     * @param string $absolutePagePath File system path to the page to compile
     * @param string $targetDir Directory to write out optimized page
     * @param string $templateDir Directory to look in for templates
     * @param string $optimizedAssetsDir Optional location with which to override assets
     * @param boolean $includeAdditionalJSFiles Denotes if autoloaded/deprecated JavaScript files should potentially be added to the page
     */
    private function compilePage($absolutePagePath, $targetDir, $templateDir, $optimizedAssetsDir, $includeAdditionalJSFiles) {
        Widgets::resetContainerID();
        //find the page segment ie( /home.php, or /answers/list.php
        $relativePagePath = Text::getSubstringAfter($absolutePagePath, '/views/pages');
        $this->writeMessage('', Config::getMessage(COMPILING_PAGE_LBL) . $relativePagePath, '<br />');

        $rawPageContent = file_get_contents($absolutePagePath);
        list($meta, $rawPageContent) = Tags::parseMetaInfo($rawPageContent);
        static $defaultTemplatePath;
        if ((isset($meta['template']) && $relativeTemplatePath = $meta['template']) && ($defaultPage = $this->options->pageToObtainDefaultTemplate()) && !isset($defaultTemplatePath) && Text::endsWith($absolutePagePath, $defaultPage)) {
            // During upgrade, new pages may be introduced which will likely reference the standard template, or some other core template.
            // If one of these templates has been renamed/removed by the customer, then upgrade deploy will fail.
            // Use whatever template the CP_HOME_URL page uses in this scenario and print a warning.
            $defaultTemplatePath = $relativeTemplatePath;
        }

        $pageThemes = ThemeParser::parseAndValidate($rawPageContent, $relativePagePath, $this->themePathResolver);

        if (is_string($pageThemes)) {
            $this->compileError($pageThemes);
            return;
        }
        $pageContent = Tags::transformTags($rawPageContent);

        if (isset($relativeTemplatePath) && $relativeTemplatePath) {
            $absoluteTemplatePath = "$templateDir/$relativeTemplatePath";
            if (!is_file($absoluteTemplatePath)) {
                $errorMessage = sprintf(Config::getMessage(PCT_S_PG_REFERENCING_NONEXISTENT_MSG), $relativePagePath, $relativeTemplatePath);
                if (isset($defaultTemplatePath) && is_file("$templateDir/$defaultTemplatePath")) {
                    // Likely the standard template does not exists in euf/assets/themes
                    $this->writeMessage('', $errorMessage, '<br />', 'warn');
                    $this->writeMessage('', sprintf(Config::getMessage(WARN_REPLACING_TEMPL_PCT_S_PCT_S_PG_MSG), $relativeTemplatePath, $defaultTemplatePath), '<br />', 'warn');
                    $relativeTemplatePath = $defaultTemplatePath;
                    $absoluteTemplatePath = "$templateDir/$relativeTemplatePath";
                    $meta['template'] = $defaultTemplatePath;
                }
                else {
                    $this->compileError($errorMessage);
                    return;
                }
            }
            list($templateContent, $templateMeta, $templateWidgetCalls, $templateJavaScriptFile, $templateInterfaceCalls,
                 $templateThemes, $templateJavaScriptFileInfo, $templateHashFile, $revertedToDefaultTemplate)
                     = $this->getTemplateContent($absoluteTemplatePath, $relativeTemplatePath, $targetDir, $optimizedAssetsDir, $defaultTemplatePath);
            if (is_string($templateThemes)) {
                // the error message for template themes has already been added by getTemplateContent()
                return;
            }
            if ($revertedToDefaultTemplate) {
                $relativeTemplatePath = $defaultTemplatePath;
                $absoluteTemplatePath = "$templateDir/$relativeTemplatePath";
                $meta['template'] = $defaultTemplatePath;
            }
            $shouldOutputHtmlFiveTags = Tags::containsHtmlFiveDoctype($templateContent);
            $mainContentPath = "views/templates/$relativeTemplatePath";
            $meta = Tags::mergeMetaArrays($meta, $templateMeta);

            $combinedContent = Tags::mergePageAndTemplate($templateContent, $pageContent);
            //If the template didn't have the rn:page_content tag in it, the merge will fail and return false
            if($combinedContent === false)
            {
                $this->compileError(htmlspecialchars(sprintf(Config::getMessage(PCT_S_TEMPL_CONT_RN_PG_CONTENT_S_MSG), $mainContentPath)));
                return;
            }
        }
        else {
            $mainContentPath = "views/pages/$relativePagePath";
            $combinedContent = $pageContent;
            $shouldOutputHtmlFiveTags = Tags::containsHtmlFiveDoctype($pageContent);
            $templateWidgetCalls = array();
            $templateThemes = array();
            $relativeTemplatePath = $absoluteTemplatePath = false;
        }

        try {
            Tags::ensureContentHasHeadAndBodyTags($combinedContent, $mainContentPath);
        }
        catch (\Exception $ex) {
            $message = htmlspecialchars($ex->getMessage());
            if (!IS_HOSTED) {
                $message .= '<br />' . str_replace("\n", '<br />', htmlspecialchars($ex->getTraceAsString()));
            }
            $this->compileError($message);
            return;
        }

        if (isset($templateJavaScriptFileInfo) && $templateJavaScriptFileInfo) {
            // Filter the list of widget js and js helpers down to just the helpers.
            $templateWidgetHelpers = array_map(function($file) { return $file['path']; }, array_filter($templateJavaScriptFileInfo, function ($file) {
                return $file['type'] === 'helper';
            }));
        }

        $widgets = $this->getWidgetCallsInContent($relativePagePath, $rawPageContent);
        $pageWidgetCalls = $this->indicateWidgetsReferencedByThisView($widgets, $relativePagePath);
        $optimizedPageAssets = $this->getOptimizedJavaScriptForWidgets($widgets, array(
            'widgets' => $templateWidgetCalls,
            'helpers' => isset($templateWidgetHelpers) ? $templateWidgetHelpers : array(),
        ));

        $optimizedAssetsRelativePath = ($optimizedAssetsDir !== null) ? Text::getSubstringAfter($optimizedAssetsDir, HTMLROOT) : null;

        if ($optimizedPageAssets['js']) {
            $pageJavaScriptFile = $this->getJavascriptFileName($relativePagePath);

            $javaScriptHeaderLocation = (($optimizedAssetsDir === null) ? $this->options->getTempOptimizedAssetsTimestampDir() : $optimizedAssetsDir) . 'pages';
            $pageHashFile = $this->calculateJavaScriptHash($optimizedPageAssets['js_files'], $pageJavaScriptFile);
            $this->filePutContents($javaScriptHeaderLocation . $pageHashFile, $optimizedPageAssets['js']);
            $this->filePutContents($targetDir . 'javascript/pages' . $pageJavaScriptFile . 'on', json_encode($optimizedPageAssets['js_files']));
        }

        $runtimeThemeData = ThemeParser::convertListOfThemesToRuntimeInformation($pageThemes, $templateThemes);

        foreach($runtimeThemeData[2] as $themeSource => $themeDestination) {
            $runtimeThemeData[2][$themeSource] = ($optimizedAssetsDir) ? $optimizedAssetsRelativePath : $this->getOptimizedThemeFunctionPath($themeDestination);
        }

        $resolvedThemePaths = ThemeParser::convertListOfThemesToResolvedThemePaths($pageThemes, $templateThemes);

        // It's going to make life so much easier if we just pretend that pages which don't have a theme actually have the
        // standard theme.  So we're going to pretend.  It'll be fun.  We just have to remember not to sandbox the
        // fake theme at the end and not to include the standard theme's site CSS without being asked to.
        $wasAThemeDeclared = (count($pageThemes) > 0 || count($templateThemes) > 0);
        if ($wasAThemeDeclared) {
            $availableThemes = $runtimeThemeData[2];
        }
        else {
            $availableThemes = array(\Themes::standardThemePath => \Themes::standardThemePath);
            $resolvedThemePaths = array(\Themes::standardThemePath => HTMLROOT . \Themes::standardThemePath);
        }
        $resolver = $this->widgetPresentationCssResolver ?:  $this->options->getWidgetPresentationCssResolver($resolvedThemePaths);
        $resolver->setExtraThemeFileCallback(array($this, 'enqueueExtraThemeFileToCopyToTempOptimized'));
        $widgetCssByThemeAndFile = $this->getWidgetCssForThemes($availableThemes, array($relativePagePath => array_keys($pageWidgetCalls), $relativeTemplatePath => array_keys($templateWidgetCalls)), $resolver);

        $headContent = Tags::getMetaHeaders($shouldOutputHtmlFiveTags)
            . ClientLoader::getBaseSiteCss()
            . $this->getFinalCssForThemes($widgetCssByThemeAndFile, $relativePagePath, $relativeTemplatePath, $pageThemes, $templateThemes, $shouldOutputHtmlFiveTags, $optimizedAssetsRelativePath)
            . ClientLoader::runtimeHeadContentPlaceholder;

        $combinedContent = Tags::insertHeadContent($combinedContent, $headContent);
        $interfaceCalls = $this->mergeInterfaceCalls($optimizedPageAssets['interfaceCalls'], isset($templateInterfaceCalls) ? $templateInterfaceCalls : null);
        list($parsedMessageBaseEntries, $messageErrors) = Config::parseJavascriptMessages($interfaceCalls['message'], true);
        list($parsedConfigBaseEntries, $configErrors) = Config::parseJavascriptConfigs($interfaceCalls['config'], true, true, isset($meta['include_chat']) ? $meta['include_chat'] : false);
        $errors = array_merge($messageErrors, $configErrors);
        if (count($errors)) {
            foreach($errors as $error)
                $this->compileError($error);
            return;
        }
        $combinedContent = $this->addIncludes($combinedContent, isset($templateJavaScriptFile) ? $templateJavaScriptFile : '', isset($templateJavaScriptFileInfo) ? $templateJavaScriptFileInfo : '', isset($pageJavaScriptFile) ? $pageJavaScriptFile : '', $optimizedPageAssets['js_files'], $parsedMessageBaseEntries, $parsedConfigBaseEntries, $meta, $optimizedAssetsRelativePath, $includeAdditionalJSFiles);

        if ($this->options->shouldCheckRightNowLogoCompliance()) {
            // get the child widgets *only* for examining logo compliance
            $widgets = $absoluteTemplatePath ? $this->getWidgetCallsInContent($relativeTemplatePath, file_get_contents($absoluteTemplatePath), true, false) : array();
            $templateCallsChildrenOnly = $this->indicateWidgetsReferencedByThisView($widgets, $relativeTemplatePath);
            $widgets = $this->getWidgetCallsInContent($relativePagePath, $rawPageContent, false, false);
            $pageWidgetCallsChildrenOnly = $this->indicateWidgetsReferencedByThisView($widgets, $relativePagePath);
            RightNowLogoCompliance::checkPage($relativePagePath, $templateCallsChildrenOnly, $pageWidgetCallsChildrenOnly);
        }

        if ($this->options->shouldCheckForLoginRequiredMismatchOnAnswersPages()) {
            $this->checkForLoginRequiredMismatchOnAnswersPages($relativePagePath, $meta);
        }

        $compiledPageContent = CodeWriter::buildOptimizedPageContent(
            $combinedContent,
            SharedViewPartialOptimization::buildCustomSharedViewPartials() . WidgetHelperOptimization::buildCustomSharedHelpers() . $this->createWidgetPageCode(array_merge($pageWidgetCalls, $templateWidgetCalls)),
            CodeWriter::buildMetaDataArray($meta),
            var_export($runtimeThemeData, true)
        );
        $compiledPageContent = $this->replaceRelativeWidgetRenderCallsWithResolved($compiledPageContent);

        $this->filePutContents("$targetDir/views/headers$relativePagePath", $compiledPageContent);

        // Remember the part where we pretended that the standard theme was available for pages which
        // didn't actually have a theme?  Now we have to stop pretending so we don't try to copy the standard
        // theme to the sandbox.
        if ($wasAThemeDeclared) {
            foreach (array($pageThemes, $templateThemes) as $themeSet) {
                foreach ($themeSet as $themePath => $theme) {
                    if($optimizedAssetsDir) {
                        $this->enqueueThemeToCopyToTempOptimized($theme->getResolvedPath(), $optimizedAssetsDir);
                    }
                    else {
                        $this->enqueueThemeToCopyToTempOptimized($theme->getResolvedPath(), $this->getTempOptimizedThemePath($themePath));
                    }
                    if (isset($theme->extraThemePath)) {
                        // This is actually specific to upgrade deploy, but I'm always doing it because I'm lazy.
                        $this->enqueueThemeToCopyToTempOptimized($theme->extraThemePath, $this->getTempOptimizedThemePath($themePath));
                    }
                    foreach ($theme->getResolvedCssPaths() as $resolvedCssPath) {
                        $realCssPath = realpath($resolvedCssPath);
                        $realThemePath = realpath($theme->getResolvedPath());
                        if (Text::beginsWith($realCssPath, $realThemePath)) {
                            continue; // It's in the theme, so it will automatically get copied to the production sandbox.
                        }
                        if (isset($theme->extraThemePath) && Text::beginsWith($realCssPath, realpath($theme->extraThemePath) . '/')) {
                            // This is actually specific to upgrade deploy, but I'm always doing it because I'm lazy.
                            continue; // It's in the extra theme, so it will automatically get copied to the production sandbox.
                        }

                        $destination = $this->options->shouldCopyThemeCssFileSeparately($realCssPath);
                        if ($destination !== false) {
                            $this->enqueueExtraThemeFileToCopyToTempOptimized($realCssPath, $destination);
                        }
                    }
                }
            }
        }
    }

    /**
     * Gets the combined CSS for all widgets on a page (or the page's template or the page's widgets) for all themes used by that page.
     *
     * @param array $widgetCssByThemeAndFile Array keyed by theme path with values arrays keyed by the relative page and template path with values of the aggregated widget CSS for that combination.
     * @param string $relativePagePath Relative page path.
     * @param string $relativeTemplatePath Relative template page.
     * @param array|null $pageThemes Array keyed by theme path with values of Theme instances parsed from the page.
     * @param array|null $templateThemes Array keyed by theme path with values of Theme instances parsed from the template.
     * @param bool $shouldOutputHtmlFiveTags Boolean indicating whether tags which aren't HTML5 compatible should be suppressed.
     * @param string $optimizedAssetsDir Path to use instead of standard optimized directory
     *
     * @return string String containing PHP code which includes the right CSS based on the theme which is selected at runtime.
     */
    private function getFinalCssForThemes(array $widgetCssByThemeAndFile, $relativePagePath, $relativeTemplatePath, $pageThemes, $templateThemes, $shouldOutputHtmlFiveTags, $optimizedAssetsDir) {
        $cssByTheme = array();
        foreach ($widgetCssByThemeAndFile as $themePath => $files) {
            $pageWidgetCss = isset($files[$relativePagePath]) ? $files[$relativePagePath] : '';
            $templateWidgetCss = isset($files[$relativeTemplatePath]) ? $files[$relativeTemplatePath] : '';
            $cssByTheme[$themePath] = CodeWriter::getBaseHrefTag(Text::getSubstringAfter($themePath, '/euf/assets/') . '/', $shouldOutputHtmlFiveTags, $optimizedAssetsDir) .
                $this->makeTagsForSiteCss(isset($templateThemes[$themePath]) ? $templateThemes[$themePath] : null, $relativeTemplatePath, 'templates', $optimizedAssetsDir) .
                $this->makeTagsForSiteCss(isset($pageThemes[$themePath]) ? $pageThemes[$themePath] : null, $relativePagePath, 'pages', $optimizedAssetsDir) .
                $this->makeTagsForWidgetCss($relativeTemplatePath, $relativePagePath, $templateWidgetCss, $pageWidgetCss, $themePath, $optimizedAssetsDir);
        }

        assert(count($cssByTheme) > 0);

        return (count($cssByTheme) === 1)
            ? current($cssByTheme) // Gets the value of the only element in the array.
            : CodeWriter::createRuntimeThemeConditions($cssByTheme);
    }

    /**
     * Writes an optimized theme CSS file to disk (if needed) and makes the link tag pointing to that file.
     *
     * @param object $theme Theme instance
     * @param string $relativeContainerPath String containing the path to the page or template relative to the pages/ or templates/ directory.
     * @param string $containerType String containing either 'page' or 'template'.
     * @param string $optimizedAssetsDir  Path to override for optimized asset location
     *
     * @return string A string containing a link tag or an empty string if the content doesn't have any CSS for the theme.
     */
    private function makeTagsForSiteCss($theme, $relativeContainerPath, $containerType, $optimizedAssetsDir) {
        if (!$theme) {
            return '';
        }
        $siteCss = '';
        $siteCssPath = "$containerType/" . preg_replace('@[.]css$@', '.SITE.css', $this->getCssFileName($relativeContainerPath, $theme->getParsedPath()), 1);
        $replacePatterns = array();
        if(!$optimizedAssetsDir) {
            $replacePatterns[] = array($this->options->getOptimizedThemesPathPattern($this->options->getTimestamp()), self::getPathRelativeToFilePath($siteCssPath));
        }

        foreach ($theme->getResolvedCssPaths() as $cssFile) {
            $content = @file_get_contents($cssFile);
            if (!$content) {
                continue;
            }
            $rebaseToDirectory = dirname($cssFile);
            if(Text::beginsWith($rebaseToDirectory, $theme->getResolvedPath())) {
                if($optimizedAssetsDir){
                    $rebaseToDirectory = str_replace($theme->getResolvedPath(), HTMLROOT . $optimizedAssetsDir, $rebaseToDirectory);
                }
                else{
                    $rebaseToDirectory = str_replace($theme->getResolvedPath(), HTMLROOT . $this->getOptimizedThemePath($theme->getParsedPath()), $rebaseToDirectory);
                }
            }
            $siteCss .= Text::minifyCss(CssUrlRewriter::rewrite($content, $rebaseToDirectory, HTMLROOT, $replacePatterns)) . "\n";
        }
        $siteCss = trim($siteCss);
        if (strlen($siteCss) === 0) {
            return '';
        }

        if($optimizedAssetsDir){
            $siteCssRelativePath = "{$optimizedAssetsDir}$siteCssPath";
            $siteCssLocation = HTMLROOT . $siteCssRelativePath;
            if (!is_readable($siteCssLocation)) {
                $this->filePutContents($siteCssLocation, $siteCss);
            }
            return CodeWriter::linkTag(self::removeExtraSlashes($siteCssRelativePath));
        }
        $siteCssLocation = "{$this->options->getTempOptimizedAssetsTimestampDir()}$siteCssPath";
        if (!is_readable($siteCssLocation)) {
            $this->filePutContents($siteCssLocation, $siteCss);
        }
        return CodeWriter::linkTag(self::removeExtraSlashes($this->getOptimizedAssetsFunctionPath($siteCssPath)));
    }

    /**
     * Enqueues an extra-theme file which will later be copied by copyExtraThemeFilesToOptimized().
     *
     * @param string $sourcePath Absolute path to copy from.
     * @param string $targetPath Relative or absolute path to copy to.
     */
    public function enqueueExtraThemeFileToCopyToTempOptimized($sourcePath, $targetPath) {
        assert(is_string($sourcePath) && strlen($sourcePath) > 0);
        assert(FileSystem::isReadableFile($sourcePath));
        if($targetPath)
        assert(is_string($targetPath) && strlen($targetPath) > 0);

        // If the destination is relative, make it absolute.
        if (!Text::beginsWith($targetPath, '/')) {
            $targetPath = $this->options->getTempOptimizedAssetsTimestampDir() . $targetPath;
        }
        if (!array_key_exists($sourcePath, $this->extraThemeFilesToCopyToOptimized)) {
            $this->extraThemeFilesToCopyToOptimized[$sourcePath] = $targetPath;
        }
        else {
            assert($targetPath === $this->extraThemeFilesToCopyToOptimized[$sourcePath]);
        }
    }

    /**
     * Enqueues a theme directory which will later be copied by copyThemesToOptimized().
     *
     * @param string $sourcePath Absolute path to copy from.
     * @param string $targetPath Absolute path to copy to.
     */
    private function enqueueThemeToCopyToTempOptimized($sourcePath, $targetPath) {
        assert(is_string($sourcePath) && strlen($sourcePath) > 0);
        assert(is_string($targetPath) && strlen($targetPath) > 0);
        if (!array_key_exists($sourcePath, $this->themesToCopyToOptimized)) {
            $this->themesToCopyToOptimized[$sourcePath] = $targetPath;
        }
        else {
            assert($targetPath === $this->themesToCopyToOptimized[$sourcePath]);
        }
    }

    /**
     * Accumulates all of the optimized widget CSS needed for a set of themes for a page and (optionally) its template.
     *
     * @param array $themes Array keyed by theme path with values of optimized theme paths.
     * @param array $filesToWidgetCalls Array keyed by relative content (page/template) path with values of array of widgets used in that content.
     * @param object $resolver WidgetPresentationCssResolverBase instance
     *
     * @return array An array keyed by theme path with values of arrays keyed by relative content path with values of the combined and minified CSS for that combination.
     */
    private function getWidgetCssForThemes(array $themes, array $filesToWidgetCalls, $resolver) {
        $css = array();
        foreach ($themes as $themePath => $optimizedThemePath) {
            $css[$themePath] = array();
            foreach ($filesToWidgetCalls as $file => $widgetCalls) {
                if (!$file || !is_array($widgetCalls) || count($widgetCalls) === 0) {
                    continue;
                }
                $css[$themePath][$file] = $this->getWidgetCssForFileAndTheme($themePath, $optimizedThemePath, $widgetCalls, $resolver);
            }
        }
        return $css;
    }

    /**
     * Gets the optimized widget CSS for a single theme for a set of widgets.
     *
     * @param string $themePath Relative theme path.
     * @param string $optimizedThemePath Absolute path to the optimized version of $themePath
     * @param array $widgetPaths Array of widgets to get CSS for.
     * @param object $resolver WidgetPresentationCssResolverBase instance
     *
     * @return string The combined and minified CSS for the set of widgets in the specified theme.
     */
    private function getWidgetCssForFileAndTheme($themePath, $optimizedThemePath, array $widgetPaths, $resolver) {
        $css  = '';
        // TODO add a cache here
        foreach ($widgetPaths as $widgetPath) {
            // TODO Figure out why the page widget calls contains a key of 'referencedBy'.  That may not be good.
            if ((!Text::beginsWith($widgetPath, 'standard') && !Text::beginsWith($widgetPath, 'custom'))
                || !($widget = Registry::getWidgetPathInfo($widgetPath))) {
                continue;
            }
            $replacePatterns = array(
                array($this->options->getOptimizedThemesPathPattern($this->options->getOldTimestampForThemesPath()), $this->getOptimizedAssetsFunctionPath('themes/')),
            );
            $css .= Widgets::accumulateWidgetCss($widget, $themePath, false, $optimizedThemePath, $resolver, $replacePatterns);
        }
        return $css;
    }

    private function getThemePathRelativeToAssets($developmentThemePath) {
        $relative = Text::getSubstringAfter($developmentThemePath, '/euf/assets/');
        if (!$relative) {
            $relative = Text::getSubstringAfter($developmentThemePath, Url::getCoreAssetPath());
            if(!$relative){
                throw new \Exception("Theme paths are expected to be within /euf/assets.  '$developmentThemePath' was not.");
            }
        }
        return $relative;
    }

    private function getOptimizedThemePath($developmentThemePath) {
        return "{$this->options->getOptimizedAssetsBaseUrl()}/{$this->options->getTimestamp()}/{$this->getThemePathRelativeToAssets($developmentThemePath)}";
    }

    private function getOptimizedThemeFunctionPath($developmentThemePath) {
        return $this->getOptimizedAssetsFunctionPath($this->getThemePathRelativeToAssets($developmentThemePath));
    }

    private function getTempOptimizedThemePath($developmentThemePath) {
        assert(is_string($developmentThemePath) && strlen($developmentThemePath) > 0);
        return "{$this->options->getTempOptimizedAssetsDir()}/{$this->options->getTimestamp()}/{$this->getThemePathRelativeToAssets($developmentThemePath)}";
    }

    /**
     * Given a relative path to a file (EG. pages/answers/detail.themes.standard.css) return the relative path with the correct number of ../ 's plus the specified prefix (EG. ../../themes)
     * @param string $relativeFilePath Relative file path
     * @param string $prefix Prefix to prepend
     * @return string
     */
    public static function getPathRelativeToFilePath($relativeFilePath, $prefix = 'themes/') {
        $elements = explode('/', self::removeExtraSlashes($relativeFilePath));
        $returnPath = '';
        while(array_shift($elements) !== null) {
            $returnPath .= '../';
        }
        return substr_replace($returnPath, $prefix, -3);
    }

    /**
     * Return $css string replacing calls to '?=getOptimizedAssetsDir();?>themes/' with '../{../}themes'
     * @param string $css CSS content
     * @param string $pathRelativeToTimestamp Path to file relative to timestamp dir
     * @return string
     */
    private function replaceOptimizedAssetsFunctionWithRelativeThemesPath($css, $pathRelativeToTimestamp) {
        return preg_replace('/' . preg_quote($this->getOptimizedAssetsFunctionPath('themes/'), '/') . '/', self::getPathRelativeToFilePath($pathRelativeToTimestamp), $css);
    }

    /**
     * Remove extra slashes from specified path.
     * @param string $path Path to modify
     * @return string
     */
    public static function removeExtraSlashes($path) {
        return preg_replace('@/{2,}@', '/', $path);
    }

    /**
     * Takes a chunk of CSS, writes it out to a optimized location and returns a CSS include tag to the newly created file
     *
     * @param string $css The CSS to write to disk
     * @param string $pathRelativeToTimestamp Path of the CSS file relative to the optimized timestamp directory
     * @param boolean $clobberExistingFile Boolean denoting if existing file should be overidden
     * @param string $optimizedAssetsDir Optional secondary directory prefix to write file contents to
     */
    private function writeCssToDiskAndReturnLink($css, $pathRelativeToTimestamp, $clobberExistingFile = false, $optimizedAssetsDir = null) {
        $pathRelativeToTimestamp = self::removeExtraSlashes($pathRelativeToTimestamp);
        $cssFileLocation = (($optimizedAssetsDir) ? HTMLROOT . $optimizedAssetsDir : $this->options->getTempOptimizedAssetsTimestampDir()) . $pathRelativeToTimestamp;

        if (!FileSystem::isReadableFile($cssFileLocation) || $clobberExistingFile === true) {
            $this->filePutContents($cssFileLocation, $this->replaceOptimizedAssetsFunctionWithRelativeThemesPath($css, $pathRelativeToTimestamp));
        }

        $assetsPath = ($optimizedAssetsDir) ? $optimizedAssetsDir . $pathRelativeToTimestamp : $this->getOptimizedAssetsFunctionPath($pathRelativeToTimestamp);
        return CodeWriter::linkTag(self::removeExtraSlashes($assetsPath));
    }

    /**
     * Writes to disk (if necessary) the optimized widget CSS file and returns a link tag pointing to it or an inline style block.
     * @param string $templateName Name of template
     * @param string $pageName Name of page
     * @param string $templateWidgetCss CSS for the template
     * @param string $pageWidgetCss CSS for the page
     * @param string $themePath Path to theme
     * @param string $optimizedAssetsDir Optimized assets directory
     * @return string CSS content to include on page
     */
    private function makeTagsForWidgetCss($templateName, $pageName, $templateWidgetCss, $pageWidgetCss, $themePath, $optimizedAssetsDir) {
        if(strlen($templateWidgetCss) === 0 && strlen($pageWidgetCss) === 0){
            return "";
        }
        $writeTemplateCssToDisk = ($templateName !== false && strlen($templateWidgetCss) > $this->minimumCssFileSize);
        $writePageCssToDisk = strlen(isset($pageWidgetCss) ? $pageWidgetCss : '') > $this->minimumCssFileSize;
        if ($writeTemplateCssToDisk) {
            $cssIncludeTag = $this->writeCssToDiskAndReturnLink($templateWidgetCss, "templates/{$this->getCssFileName($templateName, $themePath)}", false, $optimizedAssetsDir);
        }
        else {
            $cssIncludeTag = "<style type=\"text/css\">\n<!--\n$templateWidgetCss";
            if ($writePageCssToDisk) {
                $cssIncludeTag .= "\n-->\n</style>\n";
            }
        }

        if ($writePageCssToDisk) {
            $cssIncludeTag .= $this->writeCssToDiskAndReturnLink($pageWidgetCss, "pages/{$this->getCssFileName($pageName, $themePath)}", true, $optimizedAssetsDir);
        }
        else {
            if ($writeTemplateCssToDisk) {
                $cssIncludeTag .= "<style type=\"text/css\">\n<!--\n$pageWidgetCss\n--></style>\n";
            }
            else {
                $cssIncludeTag .= "$pageWidgetCss\n-->\n</style>\n";
            }
        }
        return $cssIncludeTag;
    }

    private function getOptimizedAssetsFunctionPath($path = null) {
        return "<?=FileSystem::getOptimizedAssetsDir();?>$path";
    }

    /**
     * Writes to the cache of template details.  Helps avoid reprocessing templates which are used by many pages.
     * @param string $relativeTemplatePath Relative path to template
     * @param string $content Content to add to cache
     */
    private function addToTemplateContentCache($relativeTemplatePath, $content) {
        $this->templateContentCacheByteCount += strlen($content[0]);
        if ($this->templateContentCacheByteCount > $this->maximumTemplateContentCacheByteCount) {
            unset($this->templateContentCache);
            $this->templateContentCache = array();
            $this->templateContentCacheByteCount = strlen($content[0]);
        }
        $this->templateContentCache[$relativeTemplatePath] = $content;
    }

    /**
     * Reads from the cache of template details.  Helps avoid reprocessing templates which are used by many pages.
     * @param string $templatePath Path to template
     * @param string $relativeTemplatePath Relative template path
     * @param string $targetDir Target directory to write content
     * @param string $optimizedAssetsDir Optimized asset directory
     * @param string $defaultTemplatePath Default template path
     * @return string Content of the template
     */
    private function getTemplateContent($templatePath, $relativeTemplatePath, $targetDir, $optimizedAssetsDir, $defaultTemplatePath = null) {
        if (array_key_exists($relativeTemplatePath, $this->templateContentCache)) {
            return $this->templateContentCache[$relativeTemplatePath];
        }
        // The following needs to be done in three separate steps because parseMetaInfo needs the content before transformTags touches it.
        $templateContent = file_get_contents($templatePath);
        list($templateMeta, $templateContent) = Tags::parseMetaInfo($templateContent);
        $templateThemes = ThemeParser::parseAndValidate($templateContent, Text::getSubstringAfter($templatePath, 'source/', $relativeTemplatePath), $this->themePathResolver);
        if (is_string($templateThemes)) {
            $errorMessage = htmlspecialchars($templateThemes);
            if ($defaultTemplatePath && basename($templatePath) !== $defaultTemplatePath && array_key_exists($defaultTemplatePath, $this->templateContentCache)) {
                // Likely, during the last regular deploy, no pages referenced the template in question, so the theme it references does not exist in
                // euf_backup/generated/optimized/XXXXXXXXXX/themes which is where the upgrade deploy resolver looks.
                $this->writeMessage('', $errorMessage, '<br />', 'warn');
                $this->writeMessage('', sprintf(Config::getMessage(WARN_REPLACING_TEMPL_PCT_S_PCT_S_PG_MSG), basename($templatePath), $defaultTemplatePath), '<br />', 'warn');
                $content = $this->templateContentCache[$defaultTemplatePath];
                array_splice($content, count($content) - 1, 1, true); // sets $revertedToDefaultTemplate = true;
                return $content;
            }
            $this->compileError($errorMessage);
        }

        $widgets = $this->getWidgetCallsInContent($relativeTemplatePath, $templateContent, true);
        $templateWidgetCalls = $this->indicateWidgetsReferencedByThisView($widgets, $relativeTemplatePath);
        $optimizedTemplateAssets = $this->getOptimizedJavaScriptForWidgets($widgets);

        $templateContent = Tags::transformTags($templateContent);

        $templatePathSegment = "/$relativeTemplatePath";
        if ((strlen($optimizedTemplateAssets['js']) > 0) && !array_key_exists($templatePathSegment, $this->compiledJavaScriptTemplates)) {
            $javascriptTemplatePathSegment = $this->getJavascriptFileName($templatePathSegment);
            $fullTemplateJavascriptPath = (($optimizedAssetsDir === null) ? $this->options->getTempOptimizedAssetsTimestampDir() : $optimizedAssetsDir) . 'templates';
            $templateHashFile = $this->calculateJavaScriptHash($optimizedTemplateAssets['js_files'], $javascriptTemplatePathSegment);
            $this->filePutContents($fullTemplateJavascriptPath . $templateHashFile, $optimizedTemplateAssets['js']);
            $this->filePutContents($targetDir . 'javascript/templates' . $javascriptTemplatePathSegment . 'on', json_encode($optimizedTemplateAssets['js_files']));
            $this->compiledJavaScriptTemplates[$templatePathSegment] = $javascriptTemplatePathSegment;
        }
        if (array_key_exists($templatePathSegment, $this->compiledJavaScriptTemplates)) {
            $optimizedTemplateJavascriptPath = $this->compiledJavaScriptTemplates[$templatePathSegment];
        }

        $content = array($templateContent, $templateMeta, $templateWidgetCalls, isset($optimizedTemplateJavascriptPath) ? $optimizedTemplateJavascriptPath : '',
                         $optimizedTemplateAssets['interfaceCalls'], $templateThemes, $optimizedTemplateAssets['js_files'], isset($templateHashFile) ? $templateHashFile : '', false);
        $this->addToTemplateContentCache($relativeTemplatePath, $content);
        return $content;
    }

    private function addToMinifiedJsCache($filePath, $minifiedContent) {
        $this->minifiedJsCacheByteCount += strlen($minifiedContent);
        if ($this->minifiedJsCacheByteCount > $this->maximumMinifiedJsCacheByteCount) {
            unset($this->minifiedJsCache);
            $this->minifiedJsCache = array();
            $this->minifiedJsCacheByteCount = strlen($minifiedContent);
        }
        return $this->minifiedJsCache[$filePath] = $minifiedContent;
    }


    /**
     * Finds widget calls in the given content and returns
     * info about them. Emits compile errors if invalid
     * widgets were found.
     * @param string $viewPath Path of the view
     * @param string $content  View content
     * @param boolean $isTemplate Whether the content is a template
     *                            (used in constructing an error message
     *                             if an error occurs)
     * @param boolean $includeParentViews Whether to include parent views
     * @return array See Locator#getWidgets
     */
    private function getWidgetCallsInContent($viewPath, $content, $isTemplate = false, $includeParentViews = true) {
        $locator = new Locator($viewPath, $content, $includeParentViews);
        $widgets = $locator->getWidgets();
        $widgets = $locator::removeNonReferencedParentWidgets($widgets);

        foreach ($locator->errors as $erroneousWidget) {
            $this->compileError($this->verifyWidget($erroneousWidget, $viewPath, $isTemplate), false);
        }

        return $widgets;
    }

    /**
     * Adds the given widgets into a data structure and
     * returns it.
     * @param array  $widgets  Output of #getWidgetCallsInContent
     * @param string $viewPath Path of the view
     * @return array
     *         <relative widget path> => [ 'referencedBy' => [<view path> => true] ]
     */
    private function indicateWidgetsReferencedByThisView(array $widgets, $viewPath) {
        // There isn't a super great reason this data structure
        // needs to exist like this, except that it's referenced
        // all over the place in this file, so I'm persisting it here.
        $structure = array();

        foreach ($widgets as $relativePath => $info) {
            $structure[$relativePath] = array(
                'referencedBy' => array($viewPath => true),
            );
        }

        return $structure;
    }

    /**
     * Builds a data structure containing the optimized
     * JS info for the given widgets.
     * @param  array $widgetJSInfo Output of #getWidgetCallsInContent
     * @param  array $omit         Widgets/helpers to omit. e.g. template items
     *                              'widgets' => array of widgets to omit (keys are relative widget paths)
     *                              'helpers' => array of helpers to omit (keys are absolute file paths)
     * @return array               #buildMinifiedAssets output
     */
    private function getOptimizedJavaScriptForWidgets(array $widgetJSInfo, array $omit = array()) {
        $widgetJavaScriptInfo = $this->getJavaScriptInfoForWidgets($widgetJSInfo, isset($omit['widgets']) && $omit['widgets'] ? $omit['widgets'] : array());
        return $this->buildMinifiedAssets($widgetJavaScriptInfo, isset($omit['helpers']) && $omit['helpers'] ? $omit['helpers'] : array());
    }

    /**
     * Concatenates javascript and also minifies it in addition to
     * retrieving all config and message base calls within the javascript.
     * @param array $widgetFiles Output of #getJavaScriptInfoForWidgets
     * @param array $helpersToOmit Optional list containing paths to js helper modules that shouldn't be included
     * @return array with the following keys:
     *          js: The minified + concatenated javascript for the page/template
     *          interfaceCalls: All config + message base calls made in the javascript
     *          js_files: Basically #getJavaScriptInfoForWidgets(...)['js'] without the 'minified' item
     */
    private function buildMinifiedAssets(array $widgetFiles, array $helpersToOmit = array()) {
        // Combines all minified JS code.
        $minifiedJavaScript = function ($javaScriptContents) {
            return array_reduce($javaScriptContents, function ($reduced, $item) { return $reduced . "\n" . $item['minified']; }, '');
        };

        // Order $widgetFiles['js'] by dependency based on the result from $this->javaScriptFileManager->getDependencySortedWidgetJavaScriptFiles
        $orderedWidgetJSFiles = array();
        foreach($this->javaScriptFileManager->getDependencySortedWidgetJavaScriptFiles() as $orderedFile) {
            foreach($widgetFiles['js'] as $widgetFile) {
                if($widgetFile['fullPath'] === $orderedFile || $widgetFile['path'] === $orderedFile) {
                    $orderedWidgetJSFiles []= $widgetFile;
                }
            }
        }

        if ($helpers = $this->getWidgetHelpers($minifiedJavaScript($orderedWidgetJSFiles), $helpersToOmit)) {
            // Add required widget helpers to the list of JS for the page.
            $orderedWidgetJSFiles = array_merge($helpers, $orderedWidgetJSFiles);
            $widgetFiles['interface'] = array_merge(array_map(function ($i) { return $i['fileSystemPath']; }, $helpers), $widgetFiles['interface']);
        }

        return array(
            // Single string of the concated, minified JS for the page.
            'js'             => $minifiedJavaScript($orderedWidgetJSFiles),
            // Array of message and config base entries to inject into the page `[config: [], message: []]`
            'interfaceCalls' => array_reduce($widgetFiles['interface'], array($this, 'parseInterfaceCalls')),

            /*
             * Array containing info about the JS being included on the page:
             * [{
             *      type: string - custom|standard,
             *      path: string - absolute / relative path to content (relative: widget),
             *      version: string
             * }]
             */
            'js_files'       => array_reduce($orderedWidgetJSFiles, function($reduced, $item) {unset($item['minified']); $reduced[] = $item; return $reduced;}, array()),
        );
    }

    /**
     * Gets the necessary widget helper modules
     * for the given chunk of JS.
     * @param string $content JavaScript code
     * @param array|null $exclude List of paths to exclude
     * (basically a list of the `path` items that this
     *  returns)
     * @return array          Empty if none found; when found,
     *                              includes items:
     *                              - type: 'helper',
     *                              - path: asset path to the file
     *                              - fileSystemPath: path to the file on the FS
     *                              - minified: file's minified code
     */
    private function getWidgetHelpers($content, $exclude) {
        $resultList = array();
        $helpers = Config::findAllJavaScriptHelperObjectCalls($content);

        if ($helpers) {
            array_unshift($helpers, 'EventProvider');
            $helpers = array_unique($helpers);
            // Framework folder insertion to get real path is done by dynamicJavaScript util.
            $filePath = Url::getCoreAssetPath('js/' . MOD_BUILD_SP . '.' . MOD_BUILD_NUM . '/min/modules/widgetHelpers/');
            $absolutePath = $this->options->getStaticContentBaseDir() . $filePath;

            foreach ($helpers as $helper) {
                if ($helper === 'SearchProducer' || $helper === 'SearchConsumer') {
                    $helper = 'SourceSearchFilter';
                }
                $helper .= '.js';
                $assetPath = "{$filePath}{$helper}";

                if ($exclude && array_search($assetPath, $exclude) !== false) continue;

                $fileSystemPath = "{$absolutePath}{$helper}";

                $resultList []= array(
                    'type'              => 'helper',
                    'path'              => $assetPath,
                    'fileSystemPath'    => $fileSystemPath,
                    'minified'          => $this->getMinifiedJavaScript($fileSystemPath),
                );
            }
        }

        return $resultList;
    }

    /**
     * Finds the message base and config calls in the specified JS file and adds them to the existing set.
     * @param array|null $interfaceCalls Array of already-found interface calls
     * @param string|array $jsFilePath Full path to the widget's javascript file or an array of JS template content
     *  template content
     * @return array Config and message base calls for the widget plus $interfaceCalls
     */
    private function parseInterfaceCalls($interfaceCalls, $jsFilePath) {
        if (!is_array($interfaceCalls)) {
            $interfaceCalls = $this->javaScriptInterfaceCalls;
        }
        if (is_array($jsFilePath)) {
            // Array of JS templates, not a filepath.
            foreach ($jsFilePath as $name => $content) {
                $interfaceCalls = $this->mergeInterfaceCalls($interfaceCalls, Config::findAllJavaScriptMessageAndConfigCalls($content, $name));
            }
            return $interfaceCalls;
        }

        $logicFilePath = Text::getSubstringAfter($jsFilePath, 'widgets/', $jsFilePath);
        return $this->mergeInterfaceCalls($interfaceCalls, Config::findAllJavaScriptMessageAndConfigCalls($this->getMinifiedJavaScript($jsFilePath), $logicFilePath));
    }

    private function mergeInterfaceCalls($a, $b) {
        if (!is_array($a)) {
            if (!is_array($b))
                return array('message' => array(), 'config' => array());
            return $b;
        }
        if (!is_array($b)) {
            return $a;
        }
        return array(
            'config' => array_merge($a['config'], $b['config']),
            'message' => array_merge($a['message'], $b['message']),
        );
    }

    /**
     * Returns minified JS content for either a full widget path, or the specified PathInfo object. If the value provided is a
     * PathInfo object, we'll return the already optimized widget JS from disk. Otherwise, we'll run JSMin on the content.
     * @param string|PathInfo $javaScriptPathOrPathInfo Path to widget logic file or widget PathInfo object
     * @return string Minified Javascript
     */
    private function getMinifiedJavaScript($javaScriptPathOrPathInfo) {
        $javascriptLocation = is_string($javaScriptPathOrPathInfo) ? $javaScriptPathOrPathInfo : $javaScriptPathOrPathInfo->logic;
        if(!array_key_exists($javascriptLocation, $this->minifiedJsCache)) {
            if(is_string($javaScriptPathOrPathInfo) || $javaScriptPathOrPathInfo->type === 'custom'){
                return $this->addToMinifiedJsCache($javascriptLocation, AssetOptimizer::minify($javascriptLocation));
            }
            //As this is a standard widget, don't minify it, since a minified version already exists on disk. This also allows us to pass null as the second param to getWidgetPath()
            //since we know it won't ever be used
            $optimizedJS = new OptimizedWidgetWriter($javaScriptPathOrPathInfo);
            return $this->addToMinifiedJsCache($javascriptLocation, file_get_contents($optimizedJS->jsPath));
        }
        return $this->minifiedJsCache[$javascriptLocation];
    }

    /**
    * Appends minified JavaScript code from the specified file path onto the specified string
    * @param string $minifiedJs The existing JS code to append the file's minified code onto
    * @param string $jsFilePath File path to a JS file to minify the code and append onto $minifiedJs
    * @param string $where Where to add the code to: 'append' (default) or 'prepend'
    * @return string The combined minified code
    */
    private function addMinifiedJsFrom($minifiedJs, $jsFilePath, $where = 'append') {
        $js = $this->getMinifiedJavaScript($jsFilePath);
        if ($where === 'prepend') {
            return "$js\n$minifiedJs";
        }
        return "$minifiedJs\n$js";
    }

    private function addMinifiedCssFrom($minifiedCss, $cssFilePath) {
        if (is_readable($cssFilePath))
            return ((strlen($minifiedCss)) ? ($minifiedCss . "\n") : '') . Text::minifyCss(file_get_contents($cssFilePath));
        return $minifiedCss;
    }

    /**
     * Verifies that the widget exists on disk and is valid
     * @param string|object $widget Usually a relative widget path, or, can be a PathInfo object for unit testing.
     * @param string $containerPath The file system path of the containing page or template
     * @param boolean $isTemplate Indicates whether the content is a template/page
     * @return object|string Returns a PathInfo object upon success, else an error message.
     */
    private function verifyWidget($widget, $containerPath, $isTemplate) {
        $errorMessage = null;
        if (is_object($widget)) {
            $widgetPath = $widget->relativePath;
        }
        else {
            $widgetPath = $widget;
            $widget = Registry::getWidgetPathInfo($widgetPath);
        }

        if (!$widget || ($widget->type === 'custom' && !FileSystem::isReadableDirectory($widget->absolutePath)) || ($errorMessage = Widgets::verifyWidgetReferences($widget))) {
            $label = $isTemplate ? Config::getMessage(TEMPL_LBL) : Config::getMessage(PG_LBL);
            if ($errorMessage) {
                return sprintf(Config::getMessage(PCT_S_PCT_S_PCT_S_COLON_LBL), $label, $containerPath, $errorMessage);
            }
            if (Registry::isWidgetOnDisk($widgetPath)) {
                $widgets = Registry::getAllWidgets();
                $widgetInfo = $widgets[$widgetPath];
                if (!$widgetInfo && array_key_exists("custom/$widgetPath", $widgets)) {
                    $widgetPath = "custom/$widgetPath";
                    $widgetInfo = $widgets[$widgetPath];
                }
                if (($widgetInfo['type'] === 'custom')
                    && ($newVersion = $this->deletedWidgets[$widgetPath])
                    && ($absolutePath = $widgetInfo['absolutePath'])
                    && ($version = Registry::getVersionInPath($absolutePath))
                    && ($version !== $newVersion)) {
                      // Widget version being updated does not match widget version on disk
                      return sprintf(Config::getMessage(PCT_S_PCT_S_RFERENCING_WIDGET_MSG), $label, $containerPath, $newVersion, $version, $widgetPath);
                }
                // Widget likely has not been activated
                return sprintf(Config::getMessage(PCT_S_PCT_S_REFERENCING_WIDGET_MSG), $label, $containerPath, $widgetPath);
            }
            return sprintf(Config::getMessage(PCT_S_PCT_S_REFERENCING_WIDGET_EX_MSG), $label, $containerPath, $widgetPath);
        }
        return $widget;
    }


    /**
     * Produces a list of JavaScript info for the given list of widgets.
     * @param array $widgets Widgets (see Widgets/Locator#getWidgets for keys)
     * @param array $omit    Widgets to omit
     * @return array              keys:
     *         -js: array
     *             -type: custom|standard
     *             -path: relative path
     *             -version: version
     *             -minified: minified js contents
     *         -js_paths: array containing absolute paths to js files
     *         -interface: array containing absolute paths to js files to parse
     *                     for interface calls
     */
    private function getJavaScriptInfoForWidgets(array $widgets, array $omit = array()) {
        $widgetFiles = array(
            'js'           => array(),
            'js_paths'     => array(),
            'interface'    => array(),
        );

        foreach ($widgets as $widgetRelativePath => $widgetContext) {
            $pathInfo = $this->verifyWidget($widgetRelativePath, $widgetContext['referencedBy'][0], false);

            // If $pathInfo is a string (i.e. an error message), assume that some other part of the deployer
            // has already taken care of logging the issue
            if (is_string($pathInfo) || array_key_exists($pathInfo->relativePath, $omit)) continue;

            $widgetInfo = $this->widgetArrayCache->get($pathInfo);

            if(isset($widgetInfo['meta']['extends']) && $widgetInfo['meta']['extends'] && $widgetInfo['meta']['extends_info']['logic']) {
                // Add any JS that the widget extends to the list.
                foreach($widgetInfo['meta']['extends_info']['logic'] as $file) {
                    if(!$extendsJSPathInfo = Registry::getWidgetPathInfo($file)) continue;

                    if (!in_array($extendsJSPathInfo->logic, $widgetFiles['js_paths'])) {
                        $widgetFiles = $this->addJSEntry($widgetFiles, $extendsJSPathInfo);
                    }
                }
            }

            if($widgetInfo['meta']['controller_path'] && (isset( $widgetInfo['meta']['extends']) && $widgetInfo['meta']['extends']) && $widgetInfo['meta']['extends_info']['logic']) {
                $this->javaScriptFileManager->addDependentWidgetRelationships($pathInfo->logic, $widgetInfo['meta']['extends']['widget'], true);
            }

            if ((isset($widgetInfo['meta']['js_path']) && $widgetPathInfo = Registry::getWidgetPathInfo($widgetInfo['meta']['js_path']))
                && FileSystem::isReadableFile($widgetPathInfo->logic)
                && !in_array($widgetPathInfo->logic, $widgetFiles['js_paths'])) {
                // Add the widget's own JS to the list.
                $widgetFiles = $this->addJSEntry($widgetFiles, $widgetPathInfo);
            }

            if (isset($widgetInfo['meta']['js_templates']) && $jsTemplates = $widgetInfo['meta']['js_templates']) {
                // Add JS templates to the list of files to parse for interface (message|config) calls.
                $toParse = array();
                foreach ($jsTemplates as $name => $template) {
                    // The relative path is used as a key so that if there's any errors with
                    // any interface calls then the error messages will reference the correct files.
                    $toParse["{$pathInfo->relativePath}/$name.ejs"] = $template;
                }
                $widgetFiles['interface'][] = $toParse;
            }
        }

        return $widgetFiles;
    }

    /**
     * Adds JS info about the given PathInfo object onto
     * sub-arrays on the given array and returns the result.
     * @param array   $widgetFiles    Must have `js_paths`, `interface`,
     *                                `js` keys pointing to arrays
     * @param PathInfo $widgetPathInfo Widget path info
     */
    private function addJSEntry (array $widgetFiles, PathInfo $widgetPathInfo) {
        $version = IS_TARBALL_DEPLOY
            ? Widgets::getFullVersionFromManifest($widgetPathInfo->absolutePath)
            : $widgetPathInfo->version;

        $this->javaScriptFileManager->addFile($widgetPathInfo->logic, 'widget');
        $widgetFiles['js_paths'] []= $widgetPathInfo->logic;
        $widgetFiles['interface'] []= $widgetPathInfo->logic;
        $widgetFiles['js'] []= array(
            'type'     => $widgetPathInfo->type,
            'path'     => $widgetPathInfo->relativePath,
            'fullPath' => $widgetPathInfo->logic,
            'version'  => $version,
            'minified' => $this->getMinifiedJavaScript($widgetPathInfo),
        );

        return $widgetFiles;
    }

    /**
     * Return unique hash value of JavaScript information.
     *
     * @param array $javaScriptInfo The JavaScript included in a particular asset (page or template)
     * @param string $assetPath The path to a particular asset (page or template)
     * @return string Unique hash of paths corresponding to $javaScriptInfo
     */
    private function calculateJavaScriptHash(array $javaScriptInfo, $assetPath) {
        return \RightNow\Utils\Framework::calculateJavaScriptHash(array_map(function($item) { return $item['path']; }, $javaScriptInfo), $assetPath, $this->options->getTimestamp());
    }

    /**
     * Return array of JavaScript paths.
     *
     * @param array $javaScriptInfo The JavaScript included in a particular asset (page or template)
     * @return string The var_export equivalent of JavaScript paths corresponding to $javaScriptInfo
     */
    private function calculateJavaScriptPaths(array $javaScriptInfo) {
        $paths = array_map(function($item) { return $item['path']; }, $javaScriptInfo);
        return var_export($paths, true);
    }

    /**
     * Move <euf docroot>/generated/temp_optimized -> <euf docroot>/generated/optimized
     */
    private function moveCompiledJsToHtmlDocroot() {
        $this->writeMessage('<hr size=1 />', Config::getMessage(MOVING_JAVASCRIPT_FILES_HTML_LBL) . ' (' . $this->options->getOptimizedAssetsDir() . ')');
        $this->moveDirectory($this->options->getTempOptimizedAssetsDir(), $this->options->getOptimizedAssetsDir());
    }

    /**
     * The configs for sandboxes configs are different than most configs in
     * that the values don't take effect in production immediately.  They only
     * go into effect once the user has changed the value and initiated a
     * deploy.  Service pack and upgrade deploys need to use the value from the
     * last deploy, not the current config value.
     */
    private function readSandboxedConfigValues() {
        $sandboxedConfigs = SandboxedConfigs::configValues();
        if ($this->options->shouldPreserveSandboxedConfigs() &&
            $this->options->isRunningInHosting() &&
            ($configFileLocation = $this->options->getExistingSandboxedConfigFileLocation()) &&
            (FileSystem::isReadableFile($configFileLocation . SandboxedConfigs::FILE_NAME) || FileSystem::isReadableFile($configFileLocation . SandboxedConfigs::OLD_FILE_NAME))) {
            // Merge arrays so we pick up any new configs not in the file.
            $sandboxedConfigs = array_merge($sandboxedConfigs, SandboxedConfigs::configArrayFromFile($configFileLocation));
        }
        $this->sandboxedConfigs = $sandboxedConfigs;
    }

    /**
     * Write backward compatible configs to /euf/application/<output>/optimized/config/sandboxedConfigs
     */
    private function writeSandboxedConfigValues() {
        SandboxedConfigs::writeConfigArrayToFile($this->sandboxedConfigs, $this->options->getNewSandboxedConfigFileLocation());
    }

    private function writeTimestampToFile() {
        FileSystem::writeDeployTimestampToFile($this->options->getDeployTimestampMode());
    }
}

/**
 * Rather than have the deployer know about the context it's running in, we pass
 * it a BaseDeployOptions instance which knows all that stuff.
 */
abstract class BaseDeployOptions{
    protected $fromVersionObject = null;
    function __construct($account = false, $lockCreatedTime = null, $timestamp = null) {
        $this->now = $timestamp ?: time();
        $this->CI = get_instance();
        $this->account = $account;
        $this->lockCreatedTime = $lockCreatedTime;
        $this->setWidgetTargetPages();

        if ($fromVersion = isset($_SERVER['FROM_VER']) ? $_SERVER['FROM_VER'] : '') {
            require_once CPCORE . 'Internal/Libraries/Version.php';
            $this->fromVersionObject = new Version($fromVersion);
        }
    }

    protected $CI;
    protected $now;
    protected $logFileName = false;
    protected $oldTimestamp = false;
    protected $account;
    public $lockCreatedTime;

    /**
     * Returns log file path
     * @return string The path to write logs to.
     */
    function getLogFileName() {
        if (!$this->logFileName) {
            $this->logFileName = $this->calculateLogFileName();
        }
        return $this->logFileName;
    }

    // Account_id to send to createDeployLock.
    // When HMS calls this, there is no session_id, so for that case we use account_id of 1 (administrator).
    function getAccountID() {
        return ($this->account) ? $this->account->acct_id : 1;
    }

    function getWidgetTargetPages() {
        return 'development';
    }

    function setWidgetTargetPages($sourceDirectory = 'source') {
        if ($targetPages = $this->getWidgetTargetPages()) {
            Registry::setTargetPages($targetPages, $sourceDirectory);
        }
    }

    function calculateLogFileName() {
        return Api::cfg_path() . '/log/deploy' . $this->getTimestamp() . '.log';
    }

    function getScriptsBaseDir() {
        return DOCROOT . '/';
    }

    function getDefaultTimestamp() {
        return '0000000000';
    }

    function getOldTimestamp() {
        if ($this->oldTimestamp === false) {
            if (FileSystem::isReadableDirectory(OPTIMIZED_ASSETS_PATH) && ($oldTimestamp = FileSystem::getLastDeployTimestampFromDir())) {
                $this->oldTimestamp = $oldTimestamp;
            }
            else {
                $this->oldTimestamp = $this->getDefaultTimestamp();
            }
        }
        return $this->oldTimestamp;
    }

    function getOldTimestampForThemesPath() {
        return $this->getOldTimestamp();
    }

    function getInputBaseDir() {
        return CUSTOMER_FILES;
    }

    /**
     * Returns base directory for optimized files
     * @return string The directory when the optimized and source folders will be written.
     */
    function getOutputBaseDir() {
        return OPTIMIZED_FILES . 'production/';
    }

    /**
     * Returns base directory of source files
     * @return string The directory to deploy from.
     */
    function getSourceDir() {
        return $this->getInputBaseDir();
    }

    /**
     * Returns directory for temp source files
     * @return string The directory to copy to temp_source
     */
    function getSourceDirForTempSource() {
        return $this->getSourceDir();
    }

    function getTempSourceDir() {
        return $this->getOutputBaseDir() . 'temp_source/';
    }

    function getTempOptimizedDir() {
        return $this->getOutputBaseDir() . 'temp_optimized/';
    }

    function getOutputSourceDir() {
        return $this->getOutputBaseDir() . 'source/';
    }

    function getOutputBackupDir() {
        return $this->getOutputBaseDir() . 'backup/';
    }

    function getOutputOptimizedDir() {
        return $this->getOutputBaseDir() . 'optimized/';
    }

    function getAssetsDir() {
        return HTMLROOT . '/euf/assets/';
    }

    function getOptimizedAssetsDir() {
        return HTMLROOT . $this->getOptimizedAssetsBaseUrl() . '/';
    }

    function getCurrentOptimizedAssetsDir() {
        return $this->getOptimizedAssetsDir();
    }

    function getTempOptimizedAssetsDir() {
        return HTMLROOT . $this->getTempOptimizedAssetsBaseUrl() . '/';
    }

    /**
     * Returns base path to static asset directory
     * @return string The directory that static files are served from, which is
     *   usually called the doc root and sometimes called the HTML root.
     */
    function getStaticContentBaseDir() {
        return HTMLROOT . '/';
    }

    /**
     * Only staging has a source dir for assets
     */
    function getAssetsSourceDir() {
        return null;
    }

    /**
     * Only staging has a source dir for assets
     */
    function getTempAssetsSourceDir() {
        return null;
    }

    function getOptimizedAssetsBaseUrl() {
        return '/euf/generated/optimized';
    }

    function getTempOptimizedAssetsBaseUrl() {
        return '/euf/generated/temp_optimized';
    }

    function getTimestamp() {
        return $this->now;
    }

    function getTimestampFromLog($logName) {
        if (preg_match('/^.+([0-9]{10})\.log$/', $logName, $matches)) {
            return $matches[1];
        }
    }

    function shouldIncludeStackTrace() {
        return !IS_HOSTED;
    }

    function shouldPreserveSandboxedConfigs() {
        return false;
    }

    function getExistingSandboxedConfigFileLocation() {
        return "{$this->getOutputOptimizedDir()}config/";
    }

    function getNewSandboxedConfigFileLocation() {
        return "{$this->getOutputOptimizedDir()}config/";
    }

    function shouldRemoveStandardWidgetsFromTempOptimized() {
        return $this->isRunningInHosting();
    }

    function getLogHeaderAccountInformation() {
        return Deployment::getAccountInformation($this->account);
    }

    function isRunningInHosting() {
        return IS_HOSTED;
    }

    function getThemePathResolver() {
        return new NormalThemeResolver();
    }

    function shouldVerifyMinimizedOptimizedIncludes() {
        return false;
    }

    // @codingStandardsIgnoreStart
    function getWidgetPresentationCssResolver($resolvedThemePaths) {
        return new NormalWidgetPresentationCssResolver();
    }
    // @codingStandardsIgnoreEnd

    abstract function getDeployType();

    /**
     * Location of last optimized timestamp directory
     */
    private $currentOptimizedAssetsTimestampDir;

    /**
     * TODO: this will either reflect the old timestamp dir or the new one, depending on when it is called. Consider adding a get[New|Old]OptimizedAssetsTimestampDir() function.
     */
    function getOptimizedAssetsTimestampDir() {
        if (!isset($this->currentOptimizedAssetsTimestampDir)) {
            $parentDirectory = $this->getCurrentOptimizedAssetsDir();
            $max = FileSystem::getLastDeployTimestampFromDir($parentDirectory);
            if ($max === null) {
                // Occasionally, a service pack deploy will fail with a missing assets timestamp directory.
                $max = $this->getDefaultTimestamp();
                echo "Creating optimized assets timestamp directory: $parentDirectory$max.<br />"; // We don't have access to Config::getMessage() from here.
                FileSystem::mkdirOrThrowExceptionOnFailure("$parentDirectory$max");
            }
            $this->currentOptimizedAssetsTimestampDir = $parentDirectory . $max;
        }
        return $this->currentOptimizedAssetsTimestampDir;
    }

    function getTempOptimizedAssetsTimestampDir() {
        return "{$this->getTempOptimizedAssetsDir()}{$this->getTimestamp()}/";
    }

    /**
     * Indicates if the CSS file is inside of /euf/assets but outsite of the theme.
     *
     * @param string $realCssPath The realpath() equivalent of the path to the CSS file
     *
     * @return bool False or a string containing a relative path to the CSS file.
     */
    function shouldCopyThemeCssFileSeparately($realCssPath) {
        $realEufAssets = realpath(HTMLROOT . '/euf/assets') . '/';
        if (Text::beginsWith($realCssPath, $realEufAssets)) {
            return Text::getSubstringAfter($realCssPath, $realEufAssets);
        }
        return false;
    }

    function shouldCheckRightNowLogoCompliance() {
        return true;
    }

    function shouldCheckForLoginRequiredMismatchOnAnswersPages() {
        return !Config::getConfig(CP_CONTACT_LOGIN_REQUIRED, 'RNW_UI');
    }

    function shouldVerifyAllRightNowLogoPagesSet() {
        return true;
    }

    function shouldLockPageSetConfiguration() {
        return true;
    }

    function shouldModifyPageSetConfiguration() {
        return true;
    }

    function getPageSetMappings() {
        return null;
    }

    function shouldPushVersionChanges(){
        return true;
    }

    /**
     * Second argument to pageset_model::getDeployedContent()
     */
    function getShouldUseUnlockedPageSetValues() {
        return true;
    }

    function shouldCompileStandardWidgets() {
        return !IS_HOSTED;
    }

    function shouldScriptCompile() {
        return true;
    }

    private $sourceFilesToCopy;
    function getSourceFilesToCopy() {
        return array();
    }

    private $sourceFilesToRemove;
    function getSourceFilesToRemove() {
        return array();
    }

    function shouldCopyStandardSyndicatedWidgetsToTempSource() {
        return true;
    }

    function getComment() {
        return null;
    }

    function getDeployLockCreatedTime() {
        return $this->lockCreatedTime;
    }

    function shouldVerifyDeployLock() {
        return true;
    }

    function getDeployTimestampFilePath() {
        return DEPLOY_TIMESTAMP_FILE;
    }

    function getOptimizedThemesPathPattern($timestamp) {
        return "@^/euf/generated/optimized/$timestamp/themes/@";
    }

    function getDeployTimestampMode() {
        return 'production';
    }

    function pageToObtainDefaultTemplate() {
        return null;
    }

    function shouldSuppressPageErrors() {
        return true;
    }

    function shouldCheckDuplicateAssetExistence(){
        return true;
    }

    function shouldUninstallDeletedCustomWidgets(){
        return false;
    }

    function versionChangeArgs() {
        return array();
    }
}

final class BasicDeployOptions extends BaseDeployOptions {
    function __construct($account, $lockCreatedTime = null) {
        if (!is_object($account) || !isset($account->acct_id)) {
            throw new \Exception(Config::getMessage(A_VALID_ACCOUNT_MUST_BE_SPECIFIED_MSG));
        }
        parent::__construct($account, $lockCreatedTime);
    }

    function getDeployType() {
        return Config::getMessage(BASIC_DEPLOYMENT_LBL);
    }
}

class PrepareDeployOptions extends BaseDeployOptions {
    // @codingStandardsIgnoreStart
    function __construct($account, $lockCreatedTime = null) {
        parent::__construct($account, $lockCreatedTime);
    }
    // @codingStandardsIgnoreEnd

    function getDeployType() {
        return Config::getMessage(USER_INITIATED_LBL);
    }
}

final class CommitDeployOptions extends PrepareDeployOptions {
    function __construct($account, $lockCreatedTime = null) {
        parent::__construct($account, $lockCreatedTime, $this->getTimestampFromLog($this->calculateLogFileName()));
    }

    function calculateLogFileName() {
        $CI = $this->CI ?: get_instance();
        $postedName = $CI->input->post('log_name');
        return ($postedName ? $postedName : parent::calculateLogFileName());
    }

    function getDeployType() {
        return Config::getMessage(USER_INITIATED_LBL);
    }
}

/**
 * Optimize staging_xx/source -> <output>/optimized
 */
class StagingDeployOptions extends BaseDeployOptions {
    protected $stagingPath = null;
    protected $runInTarballMode = false;
    protected $filesToRemove;
    protected $stagingHtmlRootRelativeDir;
    protected $stagingHtmlRootDir;
    protected $stagingBaseDirectories;
    private $stagingName = null;
    private $filesToCopy = null;
    private $pageSetChanges = null;
    private $pushVersionChanges = true;
    private $comment = null;
    private $stageObject = null;
    private $logPath = null;
    private $initialize = null;

    function __construct($stageObject, $comment = '', $runInTarballMode = false) {
        if (!$stageObject instanceof Stage) {
            throw new \Exception("Invalid stage object");
        }

        $this->runInTarballMode = $runInTarballMode;
        $this->stagingPath = $stageObject->getStagingPath();
        $this->stagingName = $stageObject->getStagingName();
        $this->logPath = ($runInTarballMode === true) ? false : $stageObject->getLogPath();
        $this->comment = $comment;
        $this->filesToCopy = $stageObject->getFilesToCopy();
        $this->filesToRemove = $stageObject->getFilesToRemove();
        $this->pageSetChanges = $stageObject->getPageSetChanges();
        $this->pushVersionChanges = $stageObject->shouldPushVersionChanges();
        $this->initialize = $stageObject->shouldInitialize();
        $this->stagingHtmlRootRelativeDir = "/euf/generated/staging/{$this->stagingName}/";
        $this->stagingHtmlRootDir = HTMLROOT . $this->stagingHtmlRootRelativeDir;
        $this->stagingBaseDirectories = $stageObject->getStagingDirectories();
        parent::__construct($stageObject->getAccount(), $stageObject->lockCreatedTime, $this->getTimestampFromLog($this->logPath));
    }

    function getStagingBaseDirectories() {
        return $this->stagingBaseDirectories;
    }

    function getWidgetTargetPages() {
        return $this->stagingName;
    }

    function shouldInitializeStaging() {
        return ($this->initialize === true);
    }

    function getStagingName() {
        return $this->stagingName;
    }

    function getComment() {
        return $this->comment;
    }

    function getDeployType() {
        return Config::getMessage(STAGING_DEPLOYMENT_LBL);
    }

    function getOutputBaseDir() {
        return $this->stagingPath;
    }

    function getTempOptimizedAssetsDir() {
        return HTMLROOT . $this->getTempOptimizedAssetsBaseUrl() . '/';
    }

    function getOptimizedAssetsBaseUrl() {
        return "{$this->stagingHtmlRootRelativeDir}optimized";
    }

    function getTempOptimizedAssetsBaseUrl() {
        return "{$this->stagingHtmlRootRelativeDir}temp_optimized";
    }

    function getAssetsSourceDir() {
        return "{$this->stagingHtmlRootDir}source/";
    }

    function getTempAssetsSourceDir() {
        return "{$this->stagingHtmlRootDir}temp_source/";
    }

    function getAssetsDir() {
        return "{$this->stagingHtmlRootDir}source/assets/";
    }

    function getSourceFilesToCopy() {
        return $this->filesToCopy;
    }

    function getSourceFilesToRemove() {
        return $this->filesToRemove;
    }

    function getSourceDirForTempSource() {
        return $this->stagingPath . 'source';
    }

    function getLogFileName() {
        return $this->logPath;
    }

    function calculateLogFileName() {
        return $this->logPath;
    }

    function shouldVerifyAllRightNowLogoPagesSet() {
        return false;
    }

    function shouldCopyStandardSyndicatedWidgetsToTempSource() {
        return false;
    }

    /**
     * Returns the new mappings or false if nothing should be changed
     * @return mixed
     */
    function getPageSetMappings() {
        if (is_array($this->pageSetChanges) && !empty($this->pageSetChanges)) {
            return $this->CI->model('Pageset')->getPageSetMappingFromComparedArray($this->pageSetChanges, $this->stagingPath . 'source');
        }

        if ($this->fromVersionObject && $this->fromVersionObject->equals('10.5')) {
            // page set min custom id changed from 100,000 -> 10,000 so re-write file from database.
            $pageSetMappings = $this->CI->model('Pageset')->getPageSetMappingArrays();
            return $pageSetMappings['custom'] ? $pageSetMappings : false;
        }

        return false;
    }

    function shouldPushVersionChanges(){
        return $this->pushVersionChanges;
    }

    function shouldLockPageSetConfiguration() {
        return false;
    }

    function shouldModifyPageSetConfiguration() {
        return ($this->runInTarballMode === false);
    }

    function shouldCheckRightNowLogoCompliance() {
        return ($this->runInTarballMode === false);
    }

    function shouldUninstallDeletedCustomWidgets() {
        return ($this->runInTarballMode === false);
    }

    function shouldCheckForLoginRequiredMismatchOnAnswersPages() {
        return ($this->runInTarballMode === true) ? false : parent::shouldCheckForLoginRequiredMismatchOnAnswersPages();
    }

    function shouldVerifyDeployLock() {
        return ($this->runInTarballMode === false);
    }

    function isRunningInHosting() {
        return ($this->runInTarballMode === true) ? false : IS_HOSTED;
    }

    function getDeployTimestampFilePath() {
        return "{$this->stagingPath}deployTimestamp";
    }

    function getOptimizedThemesPathPattern($timestamp) {
        return "@^/euf/generated/staging/{$this->stagingName}/(source/assets|temp_source/assets|optimized/$timestamp)/themes/@";
    }

    function getResolverPath() {
        return "{$this->getTempAssetsSourceDir()}assets";
    }

    function getThemePathResolver() {
        return new SpecifiedHtmlRootThemeResolver($this->getResolverPath());
    }

    function getWidgetPresentationCssResolver($resolvedThemePaths) {
        return new SpecifiedHtmlRootWidgetPresentationCssResolver($resolvedThemePaths, $this->getResolverPath());
    }

    function getDeployTimestampMode() {
        return $this->stagingName;
    }

    function versionChangeArgs() {
        require_once CPCORE . 'Internal/Utils/VersionTracking.php';
        $args = array();
        if (!\RightNow\Internal\Utils\VersionTracking::versionsPopulatedForMode('development')) {
            $args[] = array(null, CUSTOMER_FILES, 'development');
        }
        if (!\RightNow\Internal\Utils\VersionTracking::versionsPopulatedForMode('staging')) {
            $args[] = array(null, "{$this->stagingPath}optimized/", 'staging');
        }
        else if ($this->pushVersionChanges) {
            // if staging is being initialized, generated/staging/staging_01 is moved to generated/production/backup/staging_01
            $args[] = ($this->shouldInitializeStaging()) ?
                array(OPTIMIZED_FILES . 'production/backup/staging_01/optimized/', "{$this->stagingPath}optimized/", 'staging') :
                array("{$this->stagingPath}backup/", "{$this->stagingPath}optimized/", 'staging');
        }
        return $args;
    }
}

/**
 * Deploy/optimize production/source -> production/optimized
 */
class UpgradeProductionDeployOptions extends BaseDeployOptions {
    function getWidgetTargetPages() {
        return 'production';
    }

    function getDeployType() {
        return Config::getMessage(UPGRADE_PRODUCTION_DEPLOYMENT_LBL);
    }

    function shouldPreserveSandboxedConfigs() {
        return true;
    }

    // application/production/
    function getInputBaseDir() {
        return $this->getOutputBaseDir();
    }

    function getSourceDir() {
        return parent::getSourceDir() . 'source/';
    }

    function calculateLogFileName() {
        return Api::cfg_path() . '/log/upgradeProduction' . $this->getTimestamp() . '.log';
    }

    function getExistingSandboxedConfigFileLocation() {
        if ($this->isRunningInHosting()) {
            //Before we try and read the previous file, we need to check what version the site is being upgraded from. If the site is
            //being upgraded from something before 9.11 then we dont need to read any file because we know it won't exist so we just want
            //to read the actual config values. If the site is being upgraded from 9.11+, then we need to check both the scripts_backup and normal
            //scripts folders.
            if ($this->fromVersionObject && $this->fromVersionObject->lessThan('9.11')) {
                return false;
            }
            //Check if the old file exists, if not, that means it is the same as the file in the current production folder
            $preUpgradeFileLocation = str_ireplace('.cfg/scripts/cp', '.cfg/scripts_backup/cp', "{$this->getOutputOptimizedDir()}config/");
            if (FileSystem::isReadableDirectory($preUpgradeFileLocation)) {
                return $preUpgradeFileLocation;
            }
        }
        return parent::getExistingSandboxedConfigFileLocation();
    }

    function getThemePathResolver() {
        if ($this->doesOptimizedAssetsTimestampDirExist()) {
            return new UpgradeThemeResolver(Text::removeTrailingSlash($this->getOptimizedAssetsTimestampDir()), $this->getNewDefaultAssets());
        }
        return parent::getThemePathResolver();
    }

    function getWidgetPresentationCssResolver($resolvedThemePaths) {
        if ($this->doesOptimizedAssetsTimestampDirExist()) {
            return new UpgradeWidgetPresentationCssResolver($resolvedThemePaths, Text::removeTrailingSlash($this->getOptimizedAssetsTimestampDir()), $this->getNewDefaultAssets());
        }
        return parent::getWidgetPresentationCssResolver($resolvedThemePaths);
    }

    function getCurrentOptimizedAssetsDir() {
        if ($this->optimizedAssetsTimestampDirExists === false) {
            return parent::getCurrentOptimizedAssetsDir();
        }
        return $this->getOptimizedProductionEufBaseDir();
    }

    function getEufBaseDir() {
        // HMS sends a 'CP_ACTIVE' environment variable to specify if interface has modified CP files and/or has ever done a deploy.
        if (array_key_exists('CP_ACTIVE', $_SERVER) && $_SERVER['CP_ACTIVE'] === '0') {
            // use euf instead of euf_backup so new css is brought into deployed pages.
            return HTMLROOT . '/euf/';
        }
        // Note: euf_backup will not generally exist in our dev sites, so this will behave differently...
        return HTMLROOT . '/euf_backup/';
    }

    function getOptimizedProductionEufBaseDir(){
        return $this->getEufBaseDir() . 'generated/optimized/';
    }

    function getOptimizedStagingEufBaseDir(){
        return "{$this->getEufBaseDir()}generated/staging/{$this->stagingName}/optimized/";
    }

    function getNewDefaultAssets() {
        if (!isset($this->newDefaultAsssets)) {
            $this->newDefaultAsssets = self::getNewDefaultAssetsImpl();
        }
        return $this->newDefaultAsssets;
    }

    /**
     * List of new default assets
     */
    protected $newDefaultAsssets;

    private static function getNewDefaultAssetsImpl() {
        $oldDefaultFiles = array_keys(FileSystem::getDirectoryTree(HTMLROOT . '/euf_backup/assets/default/'));
        $newDefaultFiles = array_keys(FileSystem::getDirectoryTree(HTMLROOT . '/euf/assets/default/'));
        $newAssets = array();
        foreach (array_diff($newDefaultFiles, $oldDefaultFiles) as $newAsset) {
            $newAssets[$newAsset] = HTMLROOT . '/euf/assets/default/' . $newAsset;
        }
        var_export($newAssets);
        return $newAssets;
    }

    function shouldCopyThemeCssFileSeparately($realCssPath) {
        if (!$this->doesOptimizedAssetsTimestampDirExist()) {
            return parent::shouldCopyThemeCssFileSeparately($realCssPath);
        }

        $destination = parent::shouldCopyThemeCssFileSeparately($realCssPath);
        if ($destination === false) {
            $realProductionEufAssets = realpath($this->getOptimizedAssetsTimestampDir()) . '/';
            if (Text::beginsWith($realCssPath, $realProductionEufAssets)) {
                $destination = Text::getSubstringAfter($realCssPath, $realProductionEufAssets);
            }
            else {
                $realNewDefaultEufAssets = realpath(HTMLROOT . '/euf/assets/default') . '/';
                if (Text::beginsWith($realCssPath, $realProductionEufAssets)) {
                    $destination = Text::getSubstringAfter($realCssPath, $realProductionEufAssets);
                }
            }
        }
        return $destination;
    }

    /**
     * Flag to tell whether optimized timpstamp directory exists
     */
    private $optimizedAssetsTimestampDirExists;

    /**
     * If upgrading from pre-9.11 to 9.11, the theme sandbox directory won't exist.
     */
    function doesOptimizedAssetsTimestampDirExist() {
        if (!isset($this->optimizedAssetsTimestampDirExists)) {
            $this->getOptimizedAssetsTimestampDir();
        }
        return $this->optimizedAssetsTimestampDirExists;
    }

    function getOptimizedAssetsTimestampDir() {
        if (isset($this->optimizedAssetsTimestampDirExists)) {
            return parent::getOptimizedAssetsTimestampDir();
        }

        try {
            $dir = parent::getOptimizedAssetsTimestampDir();
            $this->optimizedAssetsTimestampDirExists = true;
            return $dir;
        }
        catch (\Exception $ex) {
            $this->optimizedAssetsTimestampDirExists = false;
            return parent::getOptimizedAssetsTimestampDir();
        }
    }

    function getOldTimestampForThemesPath() {
        static $timestamp;
        if (!isset($timestamp)) {
            $timestamp = parent::getOldTimestampForThemesPath();
            if (Text::endsWith($this->getEufBaseDir(), '/euf_backup/')) {
                $path = $this->getOptimizedProductionEufBaseDir();
                if (FileSystem::isReadableDirectory($path)) {
                    $timestamp = FileSystem::getLastDeployTimestampFromDir($path);
                }
            }
        }
        return $timestamp;
    }

    function shouldLockPageSetConfiguration() {
        return false;
    }

    function shouldModifyPageSetConfiguration() {
        return $this->fromVersionObject && $this->fromVersionObject->equals('10.5');
    }

    /**
     * For upgrades from 10.5, regenerate the pageSetMapping.php file as the
     * minimum custom page set id changed from 100,000 to 10,000
     *
     * @return mixed
     */
    function getPageSetMappings() {
        $pageSetMappings = $this->CI->model('Pageset')->getPageSetMappingArrays();
        return $pageSetMappings['custom'] ? $pageSetMappings : false;
    }

    /**
     * Second argument to pageset_model::getDeployedContent()
     * @return [bool]
     */
    function getShouldUseUnlockedPageSetValues() {
        return false;
    }

    function shouldVerifyAllRightNowLogoPagesSet() {
        return false;
    }

    function getOptimizedThemesPathPattern($timestamp) {
        return "@^/euf(_backup)?/generated/optimized/$timestamp/themes/@";
    }

    function pageToObtainDefaultTemplate() {
        return substr(Deployer::getPageUrlFromConfig('/home.php'), 1);
    }

    function versionChangeArgs() {
        // Record both production AND development versions to ensure the cp_object tables are initially populated.
        return array(
            array(null, CUSTOMER_FILES, 'development', array('db')),
            array(null, OPTIMIZED_FILES . 'production/optimized/', 'production', array('db')),
        );
    }

    function shouldCheckDuplicateAssetExistence(){
        return false;
    }
}

/**
 * Deploy/optimize staging_XX/source -> staging_XX/optimized
 */
class UpgradeStagingDeployOptions extends UpgradeProductionDeployOptions {
    protected $stagingPath = null;
    protected $stagingName = null;
    protected $stagingHtmlRootRelativeDir = null;
    protected $stagingHtmlRootDir = null;
    function __construct($stagingObject) {
        if (!$stagingObject instanceof Staging) {
            throw new \Exception("Invalid staging object");
        }
        $this->stagingPath = $stagingObject->getStagingPath();
        $this->stagingName = $stagingObject->getStagingName();
        $this->stagingHtmlRootRelativeDir = "/euf/generated/staging/{$this->stagingName}/";
        $this->stagingHtmlRootDir = HTMLROOT . $this->stagingHtmlRootRelativeDir;
        parent::__construct();
    }

    function getWidgetTargetPages() {
        return $this->stagingName;
    }

    function getDeployType() {
        return Config::getMessage(UPGRADE_STAGING_DEPLOYMENT_LBL);
    }

    function calculateLogFileName() {
        return Api::cfg_path() . '/log/upgradeStaging' . $this->getTimestamp() . '.log';
    }

    // application/staging/staging_XX
    function getInputBaseDir() {
        return $this->stagingPath;
    }

    // application/staging/staging_XX
    function getOutputBaseDir() {
        return $this->getInputBaseDir();
    }

    // Next two functions necessary because UpgradeStagingDeployOptions extends
    // UpgradeProductionDeployOptions, not StagingDeployOptions
    function getAssetsSourceDir() {
        return "{$this->stagingHtmlRootDir}source/";
    }

    function getTempAssetsSourceDir() {
        return "{$this->stagingHtmlRootDir}temp_source/";
    }

    function getDeployTimestampMode() {
        return $this->stagingName;
    }

    function getOptimizedAssetsBaseUrl() {
        return "/euf/generated/staging/{$this->stagingName}/optimized";
    }

    function getTempOptimizedAssetsBaseUrl() {
        return "/euf/generated/staging/{$this->stagingName}/temp_optimized";
    }

    function getCurrentOptimizedAssetsDir() {
        $dir = $this->getOptimizedStagingEufBaseDir();
        if (FileSystem::isReadableDirectory($dir)) {
            return $dir;
        }
        return parent::getCurrentOptimizedAssetsDir();
    }

    function getOldTimestampForThemesPath() {
        static $timestamp;
        if (!isset($timestamp)) {
            $timestamp = parent::getOldTimestampForThemesPath();
            if (Text::endsWith($this->getEufBaseDir(), '/euf_backup/')) {
                $path = $this->getOptimizedStagingEufBaseDir();
                if (FileSystem::isReadableDirectory($path)) {
                    $timestamp = FileSystem::getLastDeployTimestampFromDir($path);
                }
            }
        }
        return $timestamp;
    }

    function getOptimizedThemesPathPattern($timestamp) {
        return "@^/euf(_backup)?/generated/staging/{$this->stagingName}/optimized/$timestamp/themes/@";
    }

    function shouldModifyPageSetConfiguration() {
        return false;
    }

    function versionChangeArgs() {
        return array(array(null, "{$this->stagingPath}optimized/", 'staging', array('db')));
    }

    function shouldCheckDuplicateAssetExistence(){
        return false;
    }
}

final class ServicePackStagingDeployOptions extends UpgradeStagingDeployOptions {
    function getDeployType() {
        return Config::getMessage(SERVICE_PACK_STAGING_DEPLOYMENT_LBL);
    }

    function calculateLogFileName() {
        return Api::cfg_path() . '/log/servicePackStaging' . $this->getTimestamp() . '.log';
    }

    function getCurrentOptimizedAssetsDir() {
        $dir = $this->getOptimizedStagingEufBaseDir();
        if (FileSystem::isReadableDirectory($dir)) {
            return $dir;
        }
        return parent::getCurrentOptimizedAssetsDir();
    }

    function getOldTimestampForThemesPath() {
        static $path;
        if (!isset($path)) {
            $path = FileSystem::getLastDeployTimestampFromDir($this->getCurrentOptimizedAssetsDir());
        }
        return $path;
    }

    function getThemePathResolver() {
        return new SpecifiedHtmlRootThemeResolver(Text::removeTrailingSlash($this->getOptimizedAssetsTimestampDir()));
    }

    function shouldCheckRightNowLogoCompliance() {
        return false;
    }

    function getWidgetPresentationCssResolver($resolvedThemePaths) {
        return new SpecifiedHtmlRootWidgetPresentationCssResolver($resolvedThemePaths, Text::removeTrailingSlash($this->getOptimizedAssetsTimestampDir()));
    }

    function shouldCheckDuplicateAssetExistence(){
        return false;
    }

    function getEufBaseDir() {
        return HTMLROOT . '/euf/';
    }

    // Service packs should not change versions
    function versionChangeArgs() {
        return array();
    }
}

final class UnitTestDeployOptions extends BaseDeployOptions {
    function __construct() {
        if (IS_HOSTED) {
            throw new \Exception('This is only allowed in development.');
        }
        parent::__construct();
    }

    function getTimestamp() {
        return $this->getDefaultTimestamp();
    }

    function getDeployType() {
        return 'Unit test deploy.';
    }

    function shouldLockPageSetConfiguration() {
        return false;
    }

    function shouldModifyPageSetConfiguration() {
        return false;
    }

    function shouldVerifyDeployLock() {
        return false;
    }

    function shouldSuppressPageErrors() {
        return false;
    }

    function pageToObtainDefaultTemplate() {
        return substr(Deployer::getPageUrlFromConfig('/home.php'), 1);
    }
}

class TarballDeployOptions extends BaseDeployOptions {
    function getWidgetTargetPages() {
        return null;
    }

    function getLogFileName() {
        return false;
    }

    function getTimestamp() {
        return $this->getDefaultTimestamp();
    }

    function isRunningInHosting() {
        return false;
    }

    function shouldIncludeStackTrace() {
        return true;
    }

    function shouldRemoveStandardWidgetsFromTempOptimized() {
        return true;
    }

    function getDeployType() {
        return 'Tarball deploy';
    }

    function getOptimizedAssetsTimestampDir() {
        throw new \Exception("When the tarball deployer runs, there isn't an optimized assets timestamp dir.  You've got to make sure getOptimizedAssetsTimestampDir() doesn't get called in tarball deploy.");  // This message does not need to be internationalized.
    }

    function shouldCheckRightNowLogoCompliance() {
        return false;
    }

    function shouldCheckForLoginRequiredMismatchOnAnswersPages() {
        return false;
    }

    function shouldLockPageSetConfiguration() {
        return false;
    }

    function shouldModifyPageSetConfiguration() {
        return false;
    }

    function shouldCompileStandardWidgets() {
        return true;
    }

    function shouldVerifyDeployLock() {
        return false;
    }

    function shouldCheckDuplicateAssetExistence(){
        return false;
    }

    function shouldVerifyMinimizedOptimizedIncludes() {
        return false;
    }
}

final class VersionCronDeployOptions extends TarballDeployOptions {
    function getDeployType() {
        return 'Version CRON deploy';
    }

    function getOptimizedAssetsTimestampDir() {
        throw new \Exception("When the Version CRON runs, there isn't an optimized assets timestamp dir.  You've got to make sure getOptimizedAssetsTimestampDir() doesn't get called in tarball deploy.");  // This message does not need to be internationalized.
    }

    function shouldScriptCompile() {
        return false;
    }
}

final class TarballStagingDeployOptions extends StagingDeployOptions {
    function shouldCheckDuplicateAssetExistence(){
        return false;
    }

    function versionChangeArgs() {
        return array();
    }
}

final class ServicePackDeployOptions extends BaseDeployOptions {

    function calculateLogFileName() {
        return Api::cfg_path() . '/log/servicePackProduction' . $this->getTimestamp() . '.log';
    }

    function getWidgetTargetPages() {
        return 'production';
    }

    function getSourceDir() {
        return parent::getOutputSourceDir();
    }

    function getDeployType() {
        return Config::getMessage(SERVICE_PACK_DEPLOYMENT_LBL);
    }

    function shouldPreserveSandboxedConfigs() {
        return true;
    }

    function getThemePathResolver() {
        return new SpecifiedHtmlRootThemeResolver(Text::removeTrailingSlash($this->getOptimizedAssetsTimestampDir()));
    }

    function getWidgetPresentationCssResolver($resolvedThemePaths) {
        return new SpecifiedHtmlRootWidgetPresentationCssResolver($resolvedThemePaths, Text::removeTrailingSlash($this->getOptimizedAssetsTimestampDir()));
    }

    function shouldCopyThemeCssFileSeparately($realCssPath) {
        $destination = parent::shouldCopyThemeCssFileSeparately($realCssPath);
        if ($destination === false) {
            $realProductionEufAssets = realpath($this->getOptimizedAssetsTimestampDir()) . '/';
            if (Text::beginsWith($realCssPath, $realProductionEufAssets)) {
                $destination = Text::getSubstringAfter($realCssPath, $realProductionEufAssets);
            }
        }
        return $destination;
    }

    function shouldCheckRightNowLogoCompliance() {
        return false;
    }

    function shouldLockPageSetConfiguration() {
        return false;
    }

    function shouldModifyPageSetConfiguration() {
        return false;
    }

    function pageToObtainDefaultTemplate() {
        return substr(Deployer::getPageUrlFromConfig('/home.php'), 1);
    }

    // Put the service pack backup in backup/servicePack to preserve the backup from
    // a normal promote. Use backup if it doesn't exist so the rename won't fail.
    function getOutputBackupDir() {
        if (!is_dir($this->getOutputBaseDir() . 'backup')) {
            return $this->getOutputBaseDir() . 'backup';
        }
        return $this->getOutputBaseDir() . 'backup/servicePack';
    }

    function shouldCheckDuplicateAssetExistence(){
        return false;
    }

    // Service packs should not change versions
    function versionChangeArgs() {
        return array();
    }
}

/**
 * RightNowLogoCompliance class for determining if specified pages display the RightNowLogo widget.
 * $pages maintains state between checkPage() calls.
 */
final class RightNowLogoCompliance{
    private static $pages = null;
    private static $pagesDirectory = null;
    private static $widgets = array('standard/utils/OracleLogo', 'standard/utils/RightNowLogo', 'standard/utils/RNTLogo');

    /**
     * Set the directory to check for RightNow Logo Compliance in
     * @param string $pagesDirectory The source directory for the pages being deployed
     */
    public static function setPagesDirectory($pagesDirectory) {
        if ($pagesDirectory !== self::$pagesDirectory) {
            self::$pagesDirectory = $pagesDirectory;
            self::resetPages();
        }
    }

    public static function checkPage($page, $templateWidgetCalls, $pageWidgetCalls) {
        if (!self::isACompliancePage($page) || self::getPageStatus($page) === true) {
            return;
        }
        self::setPageStatus($page, self::logoReferencedByTemplate($templateWidgetCalls) ?: self::logoReferencedByPage($page, $pageWidgetCalls));
    }

    public static function setConfig($verifyAllPagesSet = true) {
        if ($verifyAllPagesSet === true && !self::allPagesSet()) {
            throw new \Exception('All RightNow Logo Compliance pages were not evaluated: ' . var_export(self::$pages, true));
        }
        Api::logo_compliant_update(self::allPagesCompliant());
    }

    public static function nonCompliantPages() {
        $nonCompliant = array();
        foreach (self::getPages() as $page => $status) {
            if ($status === false) {
                array_push($nonCompliant, $page);
            }
        }
        return $nonCompliant;
    }

    private static function isACompliancePage($page) {
        return array_key_exists($page, self::getPages());
    }

    private static function logoReferencedByTemplate($templateWidgetCalls) {
        foreach ($templateWidgetCalls as $widgetCall => $values) {
            if (in_array($widgetCall, self::$widgets) && isset($values['referencedBy'])) {
                return true;
            }
        }
        return false;
    }

    private static function logoReferencedByPage($page, $pageWidgetCalls) {
        foreach ($pageWidgetCalls as $widgetCall => $values) {
            if (in_array($widgetCall, self::$widgets) && array_key_exists($page, $values['referencedBy'])) {
                return true;
            }
        }
        return false;
    }

    private static function allPagesCompliant() {
        return !(in_array(false, self::getPages(), true));
    }

    private static function allPagesSet() {
        return !(in_array(null, self::getPages(), true));
    }

    private static function getPages() {
        if (self::$pages === null) {
            self::$pages = array();
            $pageOptions = array(
                '/answers/detail.php' => Deployer::getPageUrlFromConfig('/answers/detail.php'),
                '/home.php' => Deployer::getPageUrlFromConfig('/home.php'),
            );
            $pagesBasePath = (self::$pagesDirectory === null) ? APPPATH . 'views/pages' : self::$pagesDirectory;
            foreach ($pageOptions as $defaultValue => $configValue) {
                if (FileSystem::isReadableFile("$pagesBasePath$configValue")) {
                    self::$pages[$configValue] = null;
                }
                else if ($configValue !== $defaultValue && FileSystem::isReadableFile("$pagesBasePath$defaultValue")) {
                    self::$pages[$defaultValue] = null;
                }
            }
        }
        return self::$pages;
    }

    private static function setPageStatus($page, $status) {
        if (self::$pages === null) {
            self::$pages = self::getPages();
        }
        self::$pages[$page] = $status;
    }

    private static function getPageStatus($page) {
        if (self::$pages === null) {
            self::$pages = self::getPages();
        }
        return self::$pages[$page];
    }

    /**
     * Reset the compliance pages to check array to null to force rebuilding
     */
    private static function resetPages() {
        self::$pages = null;
    }
}

final class DeployLogger{
    private $logger = null;
    private $dateTimeFormat = null;
    private $CI;
    public function __construct($logPath = false) {
        if ($logPath !== false) {
            require_once CPCORE . 'Internal/Libraries/Logging.php';
            $this->CI = get_instance();
            $this->dateTimeFormat = $this->CI->cpwrapper->cpPhpWrapper->getDtfShortDate() . ' ' . $this->CI->cpwrapper->cpPhpWrapper->getDtfTime();
            $this->logger = new \RightNow\Internal\Libraries\SimpleLogger($logPath, 'DEBUG', $this->dateTimeFormat);
        }
    }

    public function log($message, $level = 'debug', $leadingHtml = '', $trailingHtml = '...<br />') {
        $level = strtolower($level);
        if (in_array($level, array('warn', 'error', 'fatal'))) {
            $leadingHtml .= '<font style="color: #FF0000;">';
            $trailingHtml = "</font>$trailingHtml";
        }
        echo $leadingHtml , $message , $trailingHtml , "\n";
        flush();
        $this->insertLog($message, $level);
    }

    public function insertLog($message, $level = 'debug') {
        if ($this->logger !== null) {
            $this->logger->$level($message);
        }
    }

    public function writeLogHeader($message, $deployType, $comment = null, $accountInformation = null) {
        $logger = $this->logger;
        $str = "\n" . $logger::LOGGING_HEADER_DELIMITER . "\n" .
                $message ."\n" .
                $this->logInfo($deployType, $comment, $accountInformation) .
                $logger::LOGGING_HEADER_DELIMITER . "\n\n";
        $this->insertLog($str, 'info');
    }

    private function logInfo($deployType, $comment = null, $accountInformation = null) {
        require_once CPCORE . 'Internal/Utils/Logs.php';
        $keys = Logs::getDeployLogDataKeys();
        $getTaggedLogData = function($logDataKey, $value) use ($keys) {
            assert(array_key_exists($logDataKey, $keys));
            return "{$keys[$logDataKey]}<rn_log_$logDataKey>$value</rn_log_$logDataKey>";
        };
        $items = array();
        $date = \RightNow\Utils\Framework::formatDate();
        $items[] = $getTaggedLogData('date', $date);
        if ($accountInformation) {
            $items[] = $getTaggedLogData('byAccount', rtrim($accountInformation));
        }
        if ($this->CI) {
            $items[] = $getTaggedLogData('ipAddress', $this->CI->input->ip_address());
            $items[] = $getTaggedLogData('userAgent', Text::escapeHtml($this->CI->agent->agent_string()));
            $items[] = $getTaggedLogData('interfaceName', Api::intf_name());
        }
        $items[] = $getTaggedLogData('deployType', $deployType);
        if ($comment) {
            $items[] = $getTaggedLogData('comment', $comment);
        }
        return implode("\n", $items) . "\n";
    }
}
