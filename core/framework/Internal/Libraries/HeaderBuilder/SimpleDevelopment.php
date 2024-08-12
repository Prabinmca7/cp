<?php
namespace RightNow\Internal\Libraries\HeaderBuilder;

use RightNow\Utils\Url,
    RightNow\Utils\Config;

require_once CPCORE . 'Internal/Libraries/HeaderBuilder/Development.php';

class SimpleDevelopment extends Development
{
    protected $viewPath = 'Admin/header/simpleDevelopment';

    /**
     * Returns the CSS includes needed to style the development header
     * @return String CSS content
     */
    public function getDevelopmentHeaderCss()
    {
        return '<link rel="stylesheet" type="text/css" href="' . Url::getCoreAssetPath('css/simpleDevelopmentHeader.css') . '"/>';
    }

    protected function getHeaderTitle() {
        return \RightNow\Libraries\AbuseDetection::isForceAbuseCookieSet() ?
            Config::getMessage(DEVELOPMENT_AREA_ABUSE_MODE_LBL) :
            Config::getMessage(DEVELOPMENT_AREA_LBL);
    }
}
