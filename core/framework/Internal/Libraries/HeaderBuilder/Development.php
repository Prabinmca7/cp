<?php

namespace RightNow\Internal\Libraries\HeaderBuilder;

use RightNow\Utils\Config,
    RightNow\Utils\Url,
    RightNow\Internal\Utils\Version;

class Development
{
    protected $widgetPaths, $urlParameters, $pageUrlFragment, $pageContent, $pagePath;
    protected $errors = array();
    protected $warnings = array();
    protected $notifications = array();
    protected $viewPath = 'Admin/header/development';
    protected $pageDetails = array();

    public function __construct($widgetDetails, $widgetPaths, $pageUrlFragment, $pageDetails = array())
    {
        $this->widgetPaths = $widgetPaths;
        $this->urlParameters = (isset($widgetDetails['urlParameters'])) ? $widgetDetails['urlParameters'] : '';
        $this->pageUrlFragment = $pageUrlFragment;
        $this->pageDetails = $pageDetails;
        $this->pageDetails["pagePath"] = str_replace(ini_get("doc_root"), "", (isset($this->pageDetails["pagePath"]) ? $this->pageDetails["pagePath"] : ''));
        $this->pageDetails["templatePath"] = str_replace(ini_get("doc_root"), "", (isset($this->pageDetails["templatePath"]) ? $this->pageDetails["templatePath"] : ''));
        if(isset($widgetDetails['javaScriptModuleProblems']) && is_array($widgetDetails['javaScriptModuleProblems']) && count($widgetDetails['javaScriptModuleProblems']))
        {
            $moduleMessage = Config::getMessage(L_WIDGETS_JAVASCRIPT_MODULE_PAGE_LBL);
            $baseWidgetLink = Url::getShortEufBaseUrl(true, "/ci/admin/versions/manage/#widget=");
            $moduleMessage .= '<ul class="developmentHeader">' . implode("\n", array_map(function($widgetPath) use($baseWidgetLink){
                return "<li><a target='_blank' href='{$baseWidgetLink}{$widgetPath}'>{$widgetPath}</a>";
            }, array_keys($widgetDetails['javaScriptModuleProblems']))) . '</ul>';
            $this->warnings[] = $moduleMessage;
        }
    }

    /**
     * Returns the CSS includes needed to style the development header
     * @return string CSS content
     */
    public function getDevelopmentHeaderCss()
    {
        return '<link rel="stylesheet" type="text/css" href="' . Url::getCoreAssetPath('css/developmentHeader.css') . '"/>';
    }

    /**
     * Generate the header HTML content
     *
     * @return string HTML content for dev header
     */
    public function getDevelopmentHeaderHtml()
    {
        $errorCount = count($this->errors);
        $warningCount = count($this->warnings);
        $notificationCount = count($this->notifications);
        $pageUrlFragmentWithUrlParameters = $this->pageUrlFragment . Url::getParameterString();
        return get_instance()->load->view($this->viewPath, array(
            'title'                            => $this->getHeaderTitle(),
            'frameworkVersion'                 => \RightNow\Utils\Framework::getFrameworkVersion(),
            'phpVersion'                       => \RightNow\Utils\Framework::getPhpVersionLabel(),
            'assetBasePath'                    => Url::getCoreAssetPath(),
            'thisModeLabel'                    => $this->getThisModeLabel(),
            'thisModeUrl'                      => $this->getThisModeUrl($pageUrlFragmentWithUrlParameters),
            'otherModeUrl'                     => $this->getOtherModeUrl($pageUrlFragmentWithUrlParameters),
            'otherModeLabel'                   => $this->getOtherModeLabel(),
            'widgets'                          => $this->getWidgets(),
            'urlParams'                        => $this->urlParameters,
            'errors'                           => $this->errors,
            'notifications'                    => $this->notifications,
            'warnings'                         => $this->warnings,
            'pageDetails'                      => $this->pageDetails,
            'expandErrorWarningSection'        => ($errorCount + $warningCount + $notificationCount) > 0,
            'errorLabel'                       => ($errorCount === 1) ? Config::getMessage(YOU_HAVE_ONE_ERROR_PAGE_LBL) : sprintf(Config::getMessage(YOU_HAVE_PCT_D_ERRORS_PAGE_LBL), $errorCount),
            'warningLabel'                     => ($warningCount === 1) ? Config::getMessage(YOU_HAVE_ONE_WARNING_PAGE_LBL) : sprintf(Config::getMessage(YOU_HAVE_PCT_D_WARNINGS_PAGE_LBL), $warningCount),
            'notificationLabel'                => ($notificationCount === 1) ? Config::getMessage(YOU_HAVE_1_NOTIFICATION_PAGE_MSG) : sprintf(Config::getMessage(YOU_HAVE_PCT_D_NOTIFICATIONS_PAGE_MSG), $notificationCount),
            'pageUrlFragmentWithUrlParameters' => $pageUrlFragmentWithUrlParameters,
            'originalUrl'                      => Url::getShortEufAppUrl('sameAsCurrentPage', "/{$this->pageUrlFragment}"),
            'toggleAbuseDetectionLink'         => $this->getAbuseDetectionLink($pageUrlFragmentWithUrlParameters),
        ), true);
    }

    /**
     * Function used to add errors to the development header bar
     *
     * @param string $errorMessage The error message to display
     */
    public function addError($errorMessage)
    {
        $this->errors[] = $errorMessage;
    }

    /**
     * Function used to add warnings to the development header bar
     *
     * @param string $warningMessage The warning message to display
     */
    public function addWarning($warningMessage)
    {
        $this->warnings[] = $warningMessage;
    }

    protected function getHeaderTitle() {
        return \RightNow\Libraries\AbuseDetection::isForceAbuseCookieSet() ?
            Config::getMessage(CUST_PORTAL_DEVELOPMENT_ABUSE_LBL) :
            Config::getMessage(CUSTOMER_PORTAL_DEVELOPMENT_AREA_LBL);
    }

    protected function getWidgets() {
        $cpHistory = Version::getVersionHistory(false, false);

        $widgets = array();
        if (isset($this->widgetPaths) && count($this->widgetPaths)) {
            foreach ($this->widgetPaths as $path => $info) {
                $isLatestVersion = true;
                $currentVersion = $info['meta']['version'];

                //If it is a standard widget, check the CP History
                if(reset(explode('/', $path)) === 'standard') {
                    foreach($cpHistory['widgetVersions'][$path] as $version => $dependencies) {
                        $version = substr($version, 0, strrpos($version, '.'));
                        if(in_array(CP_FRAMEWORK_VERSION, $dependencies['requires']['framework']) && Version::compareVersionNumbers($version, substr($currentVersion, 0, strrpos($currentVersion, '.'))) === 1) {
                            $isLatestVersion = false;
                            break;
                        }
                    }
                }
                //If it's custom, check on disk
                else {
                    $widgetVersions = \RightNow\Utils\FileSystem::listDirectory(CUSTOMER_FILES . 'widgets/' . $path, false, false, array('match', '#^[0-9]+\.[0-9]+$#'));
                    usort($widgetVersions, function($a, $b) { return -Version::compareVersionNumbers($a, $b); });
                    foreach($widgetVersions as $version) {
                        $hasNewVersion = (Version::compareVersionNumbers($version, $currentVersion) === 1);
                        if(!$hasNewVersion) {
                            break;
                        }

                        $meta = \RightNow\Utils\Widgets::getWidgetInfoFromManifest(CUSTOMER_FILES . "widgets/{$path}/{$version}", $path);
                        if(is_array($meta) && (!$meta['requires']['framework'] || in_array(CP_FRAMEWORK_VERSION, $meta['requires']['framework']))) {
                            $isLatestVersion = false;
                            break;
                        }
                    }
                }
                $widgets[$path] = array(
                    'url' => Url::getShortEufBaseUrl(true, "/ci/admin/versions/manage/#widget=$path"),
                    'isLatestVersion' => $isLatestVersion,
                    'currentVersion' => $currentVersion
                );

                if(IS_DEVELOPMENT || IS_REFERENCE) {
                    $meta = \RightNow\Utils\Widgets::convertAttributeTagsToValues($info['meta'] ?: $meta, array('validate' => true, 'eval' => true));
                    $widgets[$path]['attributes'] = isset($meta['attributes']) ? $meta['attributes'] : null;
                }
            }
        }
        return $widgets;
    }

    protected function getOtherModeLabel() {
        return Config::getMessage(GO_TO_REFERENCE_IMPLEMENTATION_CMD);
    }

    protected function getOtherModeUrl($pageUrlFragmentWithUrlParameters) {
        return Url::getShortEufBaseUrl(true, "/ci/admin/overview/referenceRedirect/$pageUrlFragmentWithUrlParameters");
    }

    protected function getThisModeUrl($pageUrlFragmentWithUrlParameters) {
        return Url::getShortEufBaseUrl(true, "/ci/admin/overview/developmentRedirect/$pageUrlFragmentWithUrlParameters");
    }

    protected function getThisModeLabel() {
        return Config::getMessage(DIRECT_URL_PAGE_DEVELOPMENT_MODE_LBL);
    }

    protected function getAbuseDetectionLink($pageUrlFragmentWithUrlParameters) {
        return sprintf('<a href="%s">%s</a><br>',
            Url::getShortEufBaseUrl(true, "/ci/admin/overview/toggleAbuseRedirect/{$pageUrlFragmentWithUrlParameters}"),
            Config::getMessage(TOGGLE_ABUSE_DETECTION_LBL));
    }
}
