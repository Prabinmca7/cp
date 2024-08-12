<?php

namespace RightNow\Controllers\Admin;

use RightNow\Utils\Tags,
    RightNow\Utils\Text,
    RightNow\Internal\Utils\Version,
    RightNow\Utils\Widgets,
    RightNow\Utils\Config,
    RightNow\Utils\Framework,
    RightNow\Internal\Libraries\Widget\PathInfo,
    RightNow\Internal\Libraries\Widget\Usage,
    RightNow\Internal\Libraries\Widget\DependencyInfo,
    RightNow\Internal\Utils\FileSystem,
    RightNow\Internal\Libraries\Widget\Registry;

require_once CPCORE . 'Internal/Utils/Admin.php';

/**
* Provides widget and framework version administration.
*/
class Versions extends Base {
    private $isTesting = false;

    function __construct() {
        parent::__construct(true, '_verifyLoginWithCPEditPermission');
        if (!IS_HOSTED) {
            require_once CPCORE . "Internal/Libraries/Widget/DependencyInfo.php";
            $this->isTesting = DependencyInfo::isTesting();
        }
    }

    function index() {
        $this->_render('versions/index', array(), Config::getMessage(VERSIONS_LBL));
    }

    function setTestMode($testName) {
        if(!IS_HOSTED) {
            if(DependencyInfo::setTest($testName)) {
                Framework::setLocationHeader("/ci/admin/versions");
                exit;
            }
            echo sprintf(Config::getMessage(INVALID_TEST_NAME_PCT_S_LBL), $testName);
        }
        echo sprintf(Config::getMessage(INVALID_FUNCTION_CALL_LBL));
    }

    function removeTests() {
        if(!IS_HOSTED) {
            DependencyInfo::removeTests();
            Framework::setLocationHeader("/ci/admin/versions");
            exit;
        }
        echo sprintf(Config::getMessage(INVALID_FUNCTION_CALL_LBL));
    }

    function removeMissingActiveWidgets() {
        $allWidgets = Registry::getAllWidgets();
        $declaredWidgets = Widgets::getDeclaredWidgetVersions();

        $deactivatedWidgets = array();
        foreach ($declaredWidgets as $widgetKey => $version) {
            if(!array_key_exists($widgetKey, $allWidgets)) {
                $deactivatedWidgets[$widgetKey] = 'remove';
            }
        }

        if($deactivatedWidgets)
            Widgets::modifyWidgetVersions($deactivatedWidgets);

        $this->_render('versions/removeMissingActiveWidgets', array(
            'deactivatedWidgets' => $deactivatedWidgets
        ), Config::getMessage(VERSIONS_LBL));
    }

    function manage() {
        $widgetRelationships = Widgets::getWidgetRelationships();
        $allWidgets = $widgetRelationships['widgets'];
        $encounteredErrors = $widgetRelationships['errors'];

        $allVersions = Version::getVersionHistory();
        $frameworkVersions = $allVersions['frameworkVersions'];

        $declaredVersions = Version::getVersionsInEnvironments();

        if($this->isTesting) {
            $declaredVersions['widgets'] = DependencyInfo::overrideAllDeclaredWidgetVersions($declaredVersions['widgets']);
            $allWidgets = DependencyInfo::overrideAllWidgetDependencyInfo($allWidgets);
            $data = DependencyInfo::loadTestDependencyInfo();
            if ($data['selectedFrameworkVersions']) {
                $declaredVersions['framework'] = $data['selectedFrameworkVersions'];
            }
        }

        uasort($frameworkVersions, "\RightNow\Internal\Utils\Version::compareVersionNumbers");

        $currentRelease = ($this->isTesting) ? DependencyInfo::getCXVersionNumber() : Version::getCXVersionNumber();
        $availableFrameworks = array();
        foreach ($frameworkVersions as $release => $frameworkVersion) {
            if (Version::compareVersionNumbers($release, $currentRelease) <= 0 ) {
                // Don't show framework updates that are only for newer CX releases.
                $majorMinorVersion = substr($frameworkVersion, 0, strrpos($frameworkVersion, '.'));
                if (!isset($availableFrameworks[$majorMinorVersion]) || !$availableFrameworks[$majorMinorVersion]) {
                    $availableFrameworks[$majorMinorVersion] = array(
                        'version' => $majorMinorVersion,
                        'release' => $release,
                        'displayableRelease' => Version::versionNumberToName($release),
                    );
                }
            }
        }
        $availableFrameworks = array_values($availableFrameworks);

        //If we are in non-hosted hunt down the real versions represented by 'current'
        //so that we don't have to deal with them in the version interface
        if(!IS_HOSTED) {
            foreach ($declaredVersions['widgets'] as $mode => $widgetArray) {
                foreach($declaredVersions['widgets'][$mode] as $widgetKey => &$version) {
                    if($version === 'current') {
                        $pathInfo = new PathInfo('standard', $widgetKey, CORE_WIDGET_FILES . $widgetKey, '');
                        $manifest = $pathInfo->meta;
                        if(is_array($manifest))
                            $version = substr($manifest['version'], 0, strrpos($manifest['version'], '.'));
                        else
                            exit('Error:' . $manifest);
                    }
                }
            }
        }

        $maxFrameworkVersion = $frameworkVersions[$currentRelease];

        return $this->_render('versions/manage', array(
            'allWidgets'          => $allWidgets, //List of all standard widgets and custom widgets including their versions and dependencies (extends/contains/framework)
            'widgetCategories'    => \RightNow\Internal\Utils\Admin::getUniqueCategories($allWidgets),
            'widgetNames'         => array_keys($allWidgets), //All the widgetKeys found in the cpHistory file
            'declaredVersions'    => $declaredVersions['widgets'], //The declared versions from the widget versions file, keyed by site mode
            'currentFramework'    => $declaredVersions['framework'],
            'availableFrameworks' => $availableFrameworks, //All the available framework versions for the given CX version, from cpHistory
            'maxFramework'        => substr($maxFrameworkVersion, 0, strrpos($maxFrameworkVersion, '.')),
            'errors'              => $encounteredErrors //Let JS know of any potential widget errors that require a refresh
        ), Config::getMessage(VERSIONS_LBL), array(
            'js'  => 'versions/manage',
            'css' => 'versions/manage',
        ));
    }

    /**
     * Modifies the version of a specified widget.
     * Only callable via an AJAX POST.
     * Accepts a `widget` post parameter whose value is expected to be a widget's relative path or all.
     * Accepts a `version` post parameter that should be a major.minor version number.
     * Renders a JSON-encoded object with a `success` member whose boolean value indicates success or failure.
     */
    function modifyWidgetVersions() {
        $this->_verifyAjaxPost();

        // Either a particular widget name, a list of widget names or all
        $subject = $this->input->post('widget');
        // A version number
        $version = $this->input->post('version');

        if ($subject === 'all') {
            $changes = Widgets::updateAllWidgetVersions();
        }
        else {
            $changes = Widgets::updateWidgetVersion($subject, $version);
        }

        $this->_renderJSONAndExit(array('success' => is_array($changes)));
    }

    /**
     * Deactivates the list of widgets. If any widget fails, reverts the process.
     * Only callable via an AJAX POST.
     * Accepts a `widgets` post parameter whose value is a comma separated list of widget keys
     * Renders a JSON-encoded object with a `success` member whose boolean value indicates success or failure.
     */
    function deactivateWidgets() {
        $this->_verifyAjaxPost();

        $transactions = array();
        $hasFailed = false;
        $subject = $this->input->post('widgets');
        foreach(explode(',', $subject) as $value) {
            $changes = Widgets::updateWidgetVersion($value, 'remove');
            if(is_array($changes)) {
                $transactions += $changes;
            }
            else {
                $hasFailed = true;
                break;
            }
        }

        if($hasFailed) {
            foreach($transactions as $widget => $versionChangeAction) {
                Widgets::updateWidgetVersion($widget, $versionChangeAction['previousVersion']);
            }
        }

        $this->_renderJSONAndExit(array('success' => !$hasFailed));
    }

    /**
     * Deletes the given widget, which involves deactivating the widget, deleting the widget from the file system,
     * and deleting the widget's presentation CSS file if it could not be used by another widget.
     * Only callable via an AJAX POST.
     * Accepts a `widgets` post parameter whose value is a single widget key
     * Renders a JSON-encoded object with a `success` member whose boolean value indicates success or failure. A `files` member will be
     * included with a successful deletion and will include an array of file paths that were removed.
     */
    function deleteWidget() {
        $this->_verifyAjaxPost();

        $widgetPath = $this->input->post('widgets');
        $deleteWidgetResults = Widgets::deleteWidget($widgetPath);
        if (isset($deleteWidgetResults['change'])) {
            unset($deleteWidgetResults['change']);
        }
        $this->_renderJSONAndExit($deleteWidgetResults);
    }

    /**
     * Modifies the version of the framework. Additionally updates widgets to their latest
     * version supporting the specified framework, if desired.
     * Accepts:
     *     - `version` post parameter whose value is expected to be a major.minor version number
     *     - `oldVersion` post parameter whose value is expected to be a major.minor version number
     * Optionally Accepts a `updateWidgets` post parameter; if specified, updates widgets to their latest version
     *  supporting the specified `version` framework
     * Renders a JSON-encoded object with a `success` member whose boolean value indicates success or failure.
     */
    function modifyFrameworkVersion() {
        $this->_verifyAjaxPost();
        $success = false;

        $version = $this->input->post('version');
        if (Version::isValidFrameworkVersion($version)) {
            $success = Version::updateDeclaredFrameworkVersion($version, $this->input->post('oldVersion'));

            if($success){
                if($this->input->post('updateWidgets')){
                    $changes = Widgets::updateAllWidgetVersions($version);
                    $success = is_array($changes) && $changes;
                }
                // from CP v3.11 when someone downgrades CP framework, we default PHP Version to 5.6
                if (Version::compareVersionNumbers(CP_FRAMEWORK_VERSION, "3.11") >= 0 && Version::compareVersionNumbers($version, CP_FRAMEWORK_VERSION) < 0) {
                    $phpVersionList = Framework::getSupportedPhpVersions();
                    Version::updateDeclaredPhpVersion(CP_LEGACY_PHP_VERSION, $phpVersionList[CP_LEGACY_PHP_VERSION]);
                }
            }
        }
        $this->_renderJSONAndExit(array('success' => $success));
    }

    /**
     * Display a list of widgets that appear to no longer be used and do not show up in
     * widget views, on pages or in templates.
     */
    function widgetsNoLongerInUse() {
        $matches = array();

        $customWidgets = array_keys(Registry::getCustomWidgets());

        // initialize the data structure to hold our results
        $matches = array_fill_keys($customWidgets, array());

        foreach ($customWidgets as $widgetPath) {
            $widgetUsage = $this->_instantiateWidgetUsage($widgetPath);
            $references = $widgetUsage->getReferences();
            $matches[$widgetPath] = $references ?: array();
        }

        $widgetUsage = array();

        // separate the matches into appropriate buckets
        $widgetUsage['noLongerUsed'] = array_keys(array_filter($matches, function($widget) {
            return empty($widget);
        }));

        $widgetUsage['possiblyUsed'] = array_filter($matches, function($widget) {
            return count($widget) > 0;
        });

        $widgetUsage = $this->checkForUseOfWidgetParent($widgetUsage);

        $this->_render('versions/noLongerInUse', array(
            'noLongerUsedWidgets' => $widgetUsage['noLongerUsed'],
            'title' => Config::getMessage(CUSTOM_WIDGETS_NOT_IN_USE_LBL),
            'allWidgetsUsed' => Config::getMessage(ALL_CUSTOM_WIDGETS_ARE_CURRENTLY_USE_MSG),
            'description' => Config::getMessage(WDGT_BG_WKD_RC_PP_DD_LV_RR_FND_DD_RR_LNG_MSG),
        ), Config::getMessage(CUSTOM_WIDGETS_NOT_IN_USE_LBL));
    }

    /**
     * Renders a view containing the specified widget's details.
     * @param string $widgetPath Relative widget path
     * @param string $version Version to retrieve
     */
    function getWidgetDocs($widgetPath, $version) {
        $widgetPath = urldecode($widgetPath);
        $version = urldecode($version);
        $currentVersion = Widgets::getCurrentWidgetVersion($widgetPath);
        if ($currentVersion === 'current') {
            $version = null;
        }

        //Try to find an activated widget. If we don't find one, try to get it directly from disk.
        $widget = Registry::getWidgetPathInfo($widgetPath, $version);
        if(!$widget && Registry::isWidgetOnDisk($widgetPath)) {
            $widgetSegments = explode('/', $widgetPath);
            $widgetType = array_shift($widgetSegments);
            if ($version) {
                if (IS_HOSTED) {
                    try {
                        // Tack on the nano version
                        $version = Widgets::getWidgetNanoVersion($widgetPath, $version);
                    }
                    catch (\Exception $e) {
                        // Invalid $widgetPath
                    }
                }
                else if ($currentVersion === null) {
                    // Non-hosted site so the version directory does not exist.
                    $version = null;
                }
            }
            $widget = new PathInfo($widgetType, $widgetPath, Registry::getBasePath($widgetType) . $widgetPath, $version);
        }
        if (!is_string($widget)) {
            require_once CPCORE . 'Internal/Libraries/Widget/Documenter.php';
            $details = \RightNow\Internal\Libraries\Widget\Documenter::getWidgetDetails($widget, array(
                'events' => true,
                'previewFiles' => false,
            ));

            if (is_array($details)) {
                echo $this->load->view('Admin/docs/widgets/widgetDetails', $details + array('widgetLocation' => $widgetPath), true);

                return;
            }
            echo $details;
        }
        else {
            echo Config::getMessage(ERR_OCC_WIDGETS_DOCUMENTATION_VIEW_MSG);
        }
    }

    /**
    * Returns a list of views (either pages, templates, or widgets) that a given widget is used on.
    * Keyed by partial file paths with values of the file path type (custom [widget],
    * standard [widget], or view [page or template]).
    * @param string $widgetPath Full relative widget path (e.g standard/search/AdvancedSearchDialog)
    */
    function getViewsUsedOn($widgetPath) {
        $widgetUsage = $this->_instantiateWidgetUsage($widgetPath);
        $this->_echoJSONAndExit(json_encode(array(
            'references'    => $widgetUsage->getReferences(),
            'lastCheckTime' => \RightNow\Api::date_str(DATEFMT_DTTM, $widgetUsage->lastCheckTime),
        )));
    }

    /**
    * Returns code snippets from a file that uses the widget.
    * @param string $widgetPath Full relative widget path
    * @param string $fileType File type ('custom', 'standard', 'view')
    * @param string $file Partial file path to look for use of the widget
    */
    function getWidgetFileUsage($widgetPath, $fileType, $file) {
        $widgetUsage = $this->_instantiateWidgetUsage($widgetPath);

        try {
            $results = $widgetUsage->getReferenceInFile(urldecode($file), $fileType, true);
        }
        catch (\Exception $e) {
            $this->_renderJSONAndExit($e->getMessage());
        }

        if ($results) {
            $results = Text::escapeHtml($results);
            $results = str_replace(Usage::$snippetBreak, "</pre><hr/><pre>\n", $results);
            $results = str_replace(Usage::$startWidgetPathMatch, '<span class="pathEmphasis">', $results);
            $results = str_replace(Usage::$endWidgetPathMatch, '</span>', $results);
        }

        $this->_echoJSONAndExit(json_encode(array(
            'snippet'       => "<pre>{$results}</pre>",
            'lastCheckTime' => \RightNow\Api::date_str(DATEFMT_DTTM, $widgetUsage->lastCheckTime),
        )));
    }

    /**
     * Returns a json encoded changelog array filtered by specified min and max versions.
     *
     * @param string $type The value 'framework' or a widget specifier (e.g. 'input/DateInput')
     * @param string|null $min The minimum version string ({major}.{minor}.{nano}, or {major}.{minor} or {major})
     * @param string|null $max The maximum version string ({major}.{minor}.{nano}, or {major}.{minor} or {major})
     */
    function getChangelog($type, $min = null, $max = null)
    {
        if ($this->isTesting) {
            $this->_renderJSONAndExit(DependencyInfo::getChangelogMockData($min ?: '1.0'));
        }

        require_once CPCORE . 'Internal/Libraries/Changelog.php';
        try {
            // if type is not valid, an exception will be thrown
            $formattedChangelog = \RightNow\Internal\Libraries\Changelog::getFormattedChangelog(urldecode($type), $min, $max);
        }
        catch (\Exception $e) {
            $formattedChangelog = array();
        }
        $this->_renderJSONAndExit($formattedChangelog);
    }

    /**
     * Return details on change history, expanding log file details for presentation.
     * @param string $widgetPathFilter Only change history related to this widget will be returned
     */
    function getChangeHistory($widgetPathFilter = null){
        $widgetPathFilter = ($widgetPathFilter) ? urldecode($widgetPathFilter) : $widgetPathFilter;
        $logFile = file_get_contents(CUSTOMER_FILES . "versionAuditLog");
        $lines = explode("\n", $logFile);
        $result = array();
        foreach($lines as $line){
            if(!$line) continue;
            list($accountID, $widgetPath, $newVersion, $oldVersion, $time) = explode(",", $line);
            if($widgetPathFilter && $widgetPathFilter !== $widgetPath){
                continue;
            }
            $entryDetails = array(
                'user' => Config::getMessageJS(UNKNOWN_LBL),
                'previous' => $oldVersion,
                'newVersion' => $newVersion,
                'time' => \RightNow\Api::date_str(DATEFMT_DTTM, $time)
                );

            if($widgetPath){
                $entryDetails['type'] = $widgetPath;
                $entryDetails['previous'] = $oldVersion ?: Config::getMessage(NOT_ACTIVATED_LBL);
                $entryDetails['newVersion'] = $newVersion ?: Config::getMessage(DEACTIVATED_LBL);
            }
            else if($newVersion && $oldVersion){
                $entryDetails['type'] = Config::getMessageJS(FRAMEWORK_CHANGE_LBL);
            }
            if($accountID && $account = $this->model("Account")->get($accountID)->result){
                $entryDetails['user'] = $account->DisplayName;
            }
            $result []= $entryDetails;
        }

        $this->_renderJSONAndExit($result);
    }

    /**
     * Read the widget's 'preview' directory and return all the image file names.
     * @param string $widgetRelativePath Relative widget path
     */
    function getWidgetPics($widgetRelativePath) {
        $widgetRelativePath = urldecode($widgetRelativePath);
        $typeOfWidget = array_shift(explode('/', $widgetRelativePath));
        $widgetPathInfo = Registry::getWidgetPathInfo($widgetRelativePath);

        require_once CPCORE . 'Internal/Libraries/Widget/Documenter.php';
        $images = array();
        $images = \RightNow\Internal\Libraries\Widget\Documenter::getWidgetPreviewImages($widgetPathInfo);
        $images = $this->getImageNames($images);
        $this->_renderJSONAndExit($images);
    }

    /**
     * Extracts the image name from the absolute image path.
     * @param array|null $images Array containing absolute image paths
     * @return array Sorted array of image names
     */
    function getImageNames($images) {
        $reformattedImages = array();
        if (!empty($images)) {
            sort($images);
            // since 'preview.png' is shown in the thumbnail, force it to be first in the list
            $defaultPreviewImage = null;
            foreach ($images as $imageName) {
                $imageName = substr(strrchr($imageName, '/'), 1);
                if ($imageName === 'preview.png') {
                    $defaultPreviewImage = $imageName;
                    continue;
                }
                $reformattedImages[] = $imageName;
            }
            if ($defaultPreviewImage) {
                array_unshift($reformattedImages, $defaultPreviewImage);
            }
        }
        return $reformattedImages;
    }

    /**
     * Takes a set of widget usage data and traverses any parent widgets to further determine
     * if it could also be moved to the no longer used set of widgets.
     *
     * @param array $widgetUsage Set of widget usage data from initial scan of views.
     * @return array|null Final set of no longer used and possibly used widgets
     * @access private
     */
    function checkForUseOfWidgetParent($widgetUsage) {
        if ($widgetUsage['possiblyUsed'] === null || $widgetUsage['noLongerUsed'] === null) {
            return null;
        }

        $mustRescanPossibles = true;

        while ($mustRescanPossibles) {
            $mustRescanPossibles = false;

            // use a separate variable so we can modify original list without getting into an infinite loop
            $possibles = $widgetUsage['possiblyUsed'];

            foreach ($possibles as $widgetPath => $locations) {
                // if this widget is on any page, we don't need to process it any further
                if (!in_array('view', $locations)) {
                    $widgetViews = array();

                    foreach ($locations as $path => $viewType) {
                        // see if the path is currently in the noLongerUsed list
                        if (!in_array($path, $widgetUsage['noLongerUsed'])) {
                            $widgetViews []= $path;
                        }
                    }

                    if (empty($widgetViews)) {
                        $widgetUsage['noLongerUsed'] []= $widgetPath;
                        unset($widgetUsage['possiblyUsed'][$widgetPath]);
                        $mustRescanPossibles = true;
                    }
                }
            }
        }

        return $widgetUsage;
    }

    /**
     * Method to validate and update PHP version in CP
     */
    public function updatePhpVersion(){
        $this->_verifyAjaxPost();
        $newVersion = $this->input->post('newVersion');
        $phpVersionList = Framework::getSupportedPhpVersions();

        if(!empty($newVersion) && preg_match('/^[0-9]{5}/', $newVersion) && array_key_exists($newVersion, $phpVersionList)) {
            $this->_renderJSONAndExit(array('success' => Version::updateDeclaredPhpVersion($newVersion, $phpVersionList[$newVersion])));
        }
        $this->_renderJSONAndExit(array('success' => false));
    }

    /**
     * Always add a no-cache header for IE8 so that when the framework version is changing
     * AJAX requests aren't inadvertently cached.
     * @param string $toRender The content to render
     */
    protected function _renderJSONAndExit($toRender) {
        header('Cache-Control: no-cache, no-store');
        parent::_renderJSONAndExit($toRender);
    }

    /**
     * Always add a no-cache header for IE8 so that when the framework version is changing
     * AJAX requests aren't inadvertently cached.
     * @param string $toEcho The content to echo
     */
    protected function _echoJSONAndExit($toEcho) {
        header('Cache-Control: no-cache, no-store');
        parent::_echoJSONAndExit($toEcho);
    }

    /**
     * Instantiates a Usage object for the given widget path.
     * @param  string $widgetPath URL-encoded relative widget path
     * @return object             Usage object
     */
    private function _instantiateWidgetUsage ($widgetPath) {
        require_once CPCORE . 'Internal/Libraries/Widget/Usage.php';

        return new Usage(urldecode($widgetPath), '\RightNow\Libraries\Cache\Memcache');
    }
}
