<?php
namespace RightNow\Widgets;

use \RightNow\Utils\Okcs;

class PerformanceTest extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
        require_once CPCORE . 'Utils/Okcs.php';
    }

    function getData() {
        $this->data['js'] = Okcs::getCachedTimings('timingCacheKey');
    }
}
