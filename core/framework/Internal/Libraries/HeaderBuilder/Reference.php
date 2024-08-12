<?php

namespace RightNow\Internal\Libraries\HeaderBuilder;

use RightNow\Utils\Config;

require_once CPCORE . 'Internal/Libraries/HeaderBuilder/Development.php';
require_once CPCORE . 'Internal/Libraries/HeaderBuilder/SimpleDevelopment.php';

final class Reference extends Development {
    protected function getHeaderTitle() {
        return IS_OKCS_REFERENCE ? Config::getMessage(CUSTOMER_PORTAL_KA_REF_IMPLEMENTATION_LBL) : Config::getMessage(CUST_PORTAL_REF_IMPLEMENTATION_LBL);
    }

    protected function getOtherModeUrl($pageUrlFragmentWithUrlParameters) {
        return \RightNow\Utils\Url::getShortEufBaseUrl() . "/ci/admin/overview/developmentRedirect/$pageUrlFragmentWithUrlParameters";
    }

    protected function getOtherModeLabel() {
        return Config::getMessage(GO_TO_DEVELOPMENT_AREA_CMD);
    }

    protected function getThisModeUrl($pageUrlFragmentWithUrlParameters) {
        return \RightNow\Utils\Url::getShortEufBaseUrl() . "/ci/admin/overview/referenceRedirect/$pageUrlFragmentWithUrlParameters";
    }

    protected function getThisModeLabel() {
        return Config::getMessage(DIRECT_URL_PG_REF_IMPLEMENTATION_LBL);
    }

    protected function getAbuseDetectionLink($pageUrlFragmentWithUrlParameters) {
        return "";
    }
}

final class SimpleReference extends SimpleDevelopment {
    protected function getHeaderTitle() {
        return Config::getMessage(REFERENCE_IMPLEMENTATION_LBL);
    }
}
