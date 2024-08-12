<?php

namespace RightNow\Controllers\Admin;

use RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Utils\FileSystem,
    RightNow\Utils\Widgets,
    RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Utils\Url,
    RightNow\Utils\Framework;

class Docs extends Base
{
    function __construct()
    {
        parent::__construct(true, '_verifyLoginWithCPEditPermission');
    }

    /**
     * Loads the main tag definition page
     * GET /ci/admin/docs
     */
    function index()
    {
        $this->_render('docs/index', array(), Config::getMessage(TAG_DOCUMENTATION_LBL));
    }

    /**
     * Loads the generic help page and migrate
     * help page.
     * GET /ci/admin/docs/help
     * GET /ci/admin/docs/help/migrate
     */
    function help()
    {
        $helpLabel = Config::getMessage(HELP_LBL);
        $data = array();
        if (in_array('migrate', $this->_getUrlSegments())) {
            $data['pageTitle'] = sprintf(\RightNow\Utils\Config::getMessage(FRAMEWORK_VERSION_PCT_S_ACT_CP_MSG), CP_FRAMEWORK_VERSION);
            $data['displayMigrationDetails'] = true;
            $data['frameworkVersion'] = CP_FRAMEWORK_VERSION;
            require_once CPCORE . 'Internal/Utils/VersionTracking.php';
            \RightNow\Internal\Utils\VersionTracking::recordVersionChanges(null, CUSTOMER_FILES, 'development');
        }
        else {
            $data['pageTitle'] = $helpLabel;
        }

        // Language code is only sent if the interface is Japanese.
        $language = strtolower(substr(LANG_DIR, 0, 2));
        $data['languageCode'] = ($language === 'ja') ? '-jp' : '';

        $this->_render('docs/help', $data, $helpLabel);
    }

    /**
     * Displays information about standard and custom widgets
     * GET /ci/admin/docs/widgets
     * @param string $widgetType Type of widget, standard, custom, browse, or empty string
     */
    function widgets($widgetType='')
    {
        Framework::installPathRestrictions();
        switch($widgetType)
        {
            case 'standard':
            case 'custom':
                $this->_displayWidgetDetails();
                break;
            case '':
                $this->_render('docs/widgets/index', null, Config::getMessage(WIDGETS_LBL));
                break;
            case 'browse':
                $this->_render('docs/widgets/list', array(
                    'isRoot' => true,
                ), Config::getMessage(WIDGETS_LBL), array(
                    'js' => 'docs/breadcrumb',
                ));
                break;
            default:
                $rendered = $this->_route(func_get_args());
                if ($rendered === false) {
                    $widgetPath = $this->_createPathFromSegments($this->_getUrlSegments());
                    $customWidgetPath = "custom/$widgetPath";
                    $redirect = (Registry::getWidgetPathInfo($customWidgetPath))
                        ? $customWidgetPath
                        : "standard/$widgetPath";
                    $this->_redirectAndExit("/ci/admin/docs/widgets/$redirect");
                }
                break;
        }
    }

    /**
     * Returns framework info.
     * GET /ci/admin/docs/framework
     */
    function framework() {
        $rendered = $this->_route(func_get_args());

        if ($rendered === false) {
            $rendered = $this->_render('docs/framework/index', null, Config::getMessage(FRAMEWORK_LBL));
        }

        return $rendered;
    }

    /**
     * Outputs an image file to the browser.
     * GET /ci/admin/docs/widgets/previewFile/:pathToFile
     */
    function _renderPreviewFile()
    {
        list($widgetPath, $imagePreviewPath) = explode('/preview/', Text::getSubstringAfter($this->uri->uri_string(), "previewFile/"));
        if($imagePreviewPath && ($widgetPathInfo = Registry::getWidgetPathInfo($widgetPath)))
        {
            $imagePreviewPath = $widgetPathInfo->absolutePath . '/preview/' . $imagePreviewPath;
            if(FileSystem::isReadableFile($imagePreviewPath))
            {
                $extension = strtolower(end(explode('.', $imagePreviewPath)));
                if($extension === 'jpg' || $extension === 'jpeg'){
                    $mimeType = 'image/jpg';
                }
                else if($extension === 'png'){
                    $mimeType = 'image/png';
                }
                else if($extension === 'gif'){
                    $mimeType = 'image/gif';
                }
                if($mimeType) {
                    Framework::writeContentWithLengthAndExit(file_get_contents($imagePreviewPath), $mimeType);
                }
            }
        }
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    }

    /**
     * Displays page that shows meta tag information.
     * GET /ci/admin/docs/framework/pageMeta
     */
    private function _renderPageMeta()
    {
        $tagDef = $this->_getTagDefinitions();
        $this->_render('docs/framework/pageMeta', array('meta' => $tagDef->tags['rn:meta']), Config::getMessage(PAGE_META_TAGS_LBL));
    }

    /**
     * Displays page that shows business object information.
     * GET /ci/admin/docs/framework/businessObjects
     */
    private function _renderBusinessObjects()
    {
        // generate the link to the Connect PHP documentation
        $softwareVersion = strtolower(str_replace(" ", "", \RightNow\Internal\Utils\Version::versionNumberToName(MOD_BUILD_VER)));
        $documentationVersion = sprintf("//documentation.custhelp.com/euf/assets/devdocs/%s/Connect_PHP/Default.htm", $softwareVersion);

        // get ConnectMetaData library
        $metaDataInstance = $this->_getConnectMetaData();

        $data = array(
            'constraints' => $metaDataInstance->getConstraintMapping(),
            'metaData' => $metaDataInstance->getMetaData(),
            'documentationVersion' => $documentationVersion,
        );

        $dependencies = array(
            'css'   => 'docs/businessObjects',
            'js'    => array(
                'docs/businessObjects',
                \RightNow\Utils\Url::getCoreAssetPath('ejs/1.0/ejs-min.js'),
            ),
        );

        return $this->_render('docs/framework/pageBusinessObjects', $data, Config::getMessage(BUSINESS_OBJECTS_LBL), $dependencies);
    }

    /**
     * Display information about the keys and values in widget info files.
     * GET /ci/admin/docs/widgets/info
     */
    private function _renderInfo()
    {
        $this->_render('docs/widgets/widgetInfo', null, Config::getMessage(WIDGET_INFO_FORMAT_LBL), array(
            'css' => 'docs/widgetInfo',
            'js' => 'docs/widgetInfo',
        ));
    }

    /**
     * Displays page tags information
     * GET /ci/admin/docs/framework/pageTags
     */
    private function _renderPageTags()
    {
        $tagDef = $this->_getTagDefinitions();
        $this->_render('docs/framework/pageTags', array(
            'exampleLabel' => Config::getMessage(EXAMPLE_UC_LBL),
            'pageTitle' => $tagDef->tags['rn:page_title'],
            'pageContent' => $tagDef->tags['rn:page_content'],
            'headContent' => $tagDef->tags['rn:head_content'],
            'conditionTag' => $tagDef->tags['rn:condition'],
            'containerTag' => $tagDef->tags['rn:container'],
            'fieldTag' => $tagDef->tags['rn:field'],
            'formTag' => $tagDef->tags['rn:form'],
            'themeTag' => $tagDef->tags['rn:theme'],
            'tagLabel' => Config::getMessage(TAG_LBL),
            'returnLabel' => Config::getMessage(RETURNED_VALUE_LBL),
            'inlineLabel' => Config::getMessage(INLINE_USAGE_LBL),
        ), Config::getMessage(PAGE_TAGS_LBL));
    }

    /**
     * Displays either the listing for the specified folder, or details
     * about a specific widget.
     */
    private function _displayWidgetDetails()
    {
        $segmentArray = $this->_getUrlSegments();
        $widgetPath = $this->_createPathFromSegments($segmentArray);

        //If we find a valid widget redirect to the documentation
        if(preg_match("#/([0-9]+\.[0-9]+(:?\.[0-9]+)?)$#", $widgetPath, $matches)) {
            array_pop($segmentArray);
            $widgetPath = $this->_createPathFromSegments($segmentArray);
            $version = $matches[1];
        }
        if(Registry::getWidgetPathInfo($widgetPath)) {
            $this->_redirectAndExit('/ci/admin/versions/manage#widget=' . urlencode($widgetPath) . '&docs=true' . ((isset($version) && $version) ? '&version=' . urlencode($version) : ''));
        }
        if(Registry::isWidgetOnDisk($widgetPath)) {
            if ($version) {
                // de-activated widget with specific version in URL - /ci/admin/docs/widgets/custom/sample/SampleWidget/1.0
                $this->_redirectAndExit('/ci/admin/versions/manage#widget=' . urlencode($widgetPath) . '&docs=true&version=' . urlencode($version));
            }
            else {
                // de-activated widget without a specific version in URL - /ci/admin/docs/widgets/custom/sample/SampleWidget
                $this->_redirectAndExit('/ci/admin/versions/manage#widget=' . urlencode($widgetPath));
            }
        }

        //Couldn't find a valid widget; dig deeper
        $page = array(
            'pathLinks' => $this->_buildDropdownArray($segmentArray),
            'topfolders' => $this->_getFolders($this->_createPathFromSegments($segmentArray)),
        );
        $this->_render("docs/widgets/list", $page, Config::getMessage(TAG_DOCUMENTATION_LBL), array(
            'js' => 'docs/breadcrumb',
        ));
    }

    private function _getTagDefinitions()
    {
        try
        {
            require_once CPCORE . 'Internal/Libraries/TagDefinitions.php';
            return \RightNow\Internal\Libraries\TagDefinitions::getInstance();
        }
        catch (\Exception $ex)
        {
            Framework::writeContentWithLengthAndExit(Config::getMessage(PG_SERVED_FOLLOWING_ERR_PLS_ERR_MSG) . '<p/>' . htmlspecialchars($ex->getMessage()));
        }
    }

    private function _getConnectMetaData()
    {
        try
        {
            require_once CPCORE . 'Internal/Libraries/ConnectMetaData.php';
            return \RightNow\Internal\Libraries\ConnectMetaData::getInstance();
        }
        catch (\Exception $ex)
        {
            Framework::writeContentWithLengthAndExit(Config::getMessage(PG_SERVED_FOLLOWING_ERR_PLS_ERR_MSG) . '<p/>' . htmlspecialchars($ex->getMessage()));
        }
    }

    /**
     * Gets the segments specified in the URL.
     *
     * @return array Array of segments in the URL specified after admin/docs/function/.
     */
    private function _getUrlSegments()
    {
        return array_slice($this->uri->segment_array(), 3);
    }

    /**
     * Gets the path denoted by the segment array.
     *
     * @param array $segmentArray Array of path segments.
     * @return string The path specified by the segment array.
     */
    private function _createPathFromSegments(array $segmentArray)
    {
        return implode('/', $segmentArray);
    }

    /**
     * Gets a list of folders in the file system for the given path.
     * Dot files are omitted.
     * @param string $basePath The file system path to list directories under
     * @return array An array with the following keys:
     *   links: array containing all folders under the current path (ea. item contains path and name keys)
     *   widgetLevel: for a standard widget, whether the current $widgetPath is at the widget directory level
     */
    private function _getFolders($basePath)
    {
        $topLevelFolder = 'widgets/';
        $links = array();
        $widgets = (Text::beginsWith($basePath, 'custom')) ? Registry::getCustomWidgets() : Registry::getStandardWidgets();
        ksort($widgets);

        foreach (array_keys($widgets) as $widgetPath) {
            if (Text::beginsWith($widgetPath, $basePath)) {
                $pathParts = array_filter(explode('/', Text::getSubstringAfter($widgetPath, $basePath)));
                $currentPart = array_shift($pathParts);
                $links[] = array('path' => "{$topLevelFolder}{$basePath}/{$currentPart}", 'name' => $currentPart);
            }
        }

        // remove duplicates
        return array_map('unserialize', array_unique(array_map('serialize', $links)));
    }

    /**
     * Builds the array used for the dropdown links in the widget breadcrumb listing
     *
     * @param array $segments An array of the current page segments
     * @return array An array of all the widgets at each level
     */
    private static function _buildDropdownArray(array $segments)
    {
        $dropdownArray = array();
        $standardArray = array('label' => 'standard', 'value' => 'standard');
        $customArray = array('label' => 'custom', 'value' => 'custom');
        if($segments[0] === 'standard')
            $standardArray['selected'] = true;
        else
            $customArray['selected'] = true;

        $dropdownArray[] = array($standardArray, $customArray);
        $baseFolderPath = '';
        $segmentsVisited = array();
        $segmentsString = implode('/', $segments);
        foreach($segments as $segment) {
            array_push($segmentsVisited, $segment);
            if ($segmentsVisited === $segments)
                break;
            $index = count($segmentsVisited);
            $folderPath = ($index === 1) ? "$segment/" : "$folderPath$segment/";

            $dir = Registry::getAbsoluteWidgetPathInternal($folderPath);

            $selected = false;
            foreach(FileSystem::getSortedListOfDirectoryEntries($dir, null, array('method', 'isDir')) as $file) {
                $tempItem = array('label' => $file, 'value' => $folderPath . $file);
                if (Text::beginsWith($segmentsString, $folderPath . $file)) {
                    $tempItem['selected'] = true;
                    $selected = true;
                }
                $dropdownArray[$index + 1][] = $tempItem;
            }
            // If an invalid path is submitted via url parameters ('widgets/standard/foo'), or,
            // a folder whose widgets are all deprecated ('widgets/standard/composite'), there
            // will be no selected files, thus the label on the breadcrumb will be wrong.
            // Below we select the first file/dir to avoid this. Another option would be to determine if
            // the path exists or if all widget sub-folders were deprecated, but that would
            // be more expensive ...
            if ($selected === false && isset($dropdownArray[$index + 1][0]))
                $dropdownArray[$index + 1][0]['selected'] = true;
        }
        return $dropdownArray;
    }

    /**
     * Route a public endpoint to a private class method if allowed. The valid targets are any of the '_render' functions.
     * @param array|null $segments The rest of the segments that the top-level method received
     * @return bool|string|null String HTML if the method returned its rendered content,
     *                          Null if the method simply rendered,
     *                          False if no method was called
     */
    private function _route($segments) {
        if (count($segments)) {
            // Class methods that begin with `_render` + method are callable;
            // all other class methods are not.
            $method = '_render' . array_shift($segments);
            if (method_exists($this, $method)) {
                return call_user_func_array(array($this, $method), $segments);
            }
        }
        return false;
    }
}
