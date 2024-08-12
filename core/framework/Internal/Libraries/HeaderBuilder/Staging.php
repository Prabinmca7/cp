<?php

namespace RightNow\Internal\Libraries\HeaderBuilder;

use RightNow\Utils\Url;

require_once CPCORE . 'Internal/Libraries/HeaderBuilder/Development.php';
require_once CPCORE . 'Internal/Utils/Admin.php';

final class Staging extends Development {
    private $shouldUseSimpleHeader;
    protected $viewPath = 'Admin/header/staging';

    public function __construct($widgetDetails, $widgetPaths, $pageUrlFragment, $shouldUseSimpleHeader) {
        parent::__construct($widgetDetails, $widgetPaths, $pageUrlFragment);

        if ($this->shouldUseSimpleHeader = $shouldUseSimpleHeader) {
            $this->viewPath = 'Admin/header/simpleStaging';
        }
    }

    public function getDevelopmentHeaderCss() {
        if ($this->shouldUseSimpleHeader) {
            return '<link rel="stylesheet" type="text/css" href="' . Url::getCoreAssetPath('css/simpleDevelopmentHeader.css') . '"/>';
        }
        return parent::getDevelopmentHeaderCss();
    }

    protected function getHeaderTitle() {
        $title = \RightNow\Utils\Config::getMessage(CUSTOMER_PORTAL_STAGING_AREA_LBL);
        list($location) = \RightNow\Environment\retrieveModeAndModeTokenFromCookie();
        $stagingEnvironments = \RightNow\Internal\Utils\Admin::getStagingEnvironments();
        if (count($stagingEnvironments) > 1 && $details = $stagingEnvironments[$location]) {
            $title .= sprintf(' %s %s', $details['stagingIndex'], $details['stagingName']);
        }
        return $title;
    }
}