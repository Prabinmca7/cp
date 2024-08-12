<?php
namespace RightNow\Widgets;

use RightNow\Utils\Text,
    RightNow\Utils\Url;

class CapabilityDetector extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if($this->data['attrs']['fail_page_set'] === $this->data['attrs']['pass_page_set']) {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(PG_SET_TESTS_FAIL_PG_SET_TESTS_MSG));
            return false;
        }

        $currentPageSetPath = $this->CI->getPageSetPath() ?: 'default';
        $automaticallyRedirect = $this->data['attrs']['automatically_redirect_on_failure'];
        $pageSetInvalid = false;
        $this->data['runJSTests'] = true;
        $this->data['showSuccessMessage'] = $this->data['attrs']['display_tests_pass'];

        $this->data['link_tests_fail'] = '/ci/redirect/pageSet/' . urlencode($this->data['attrs']['fail_page_set']) . '/'
            . Url::deleteParameter(Text::getSubstringAfter($_SERVER['REQUEST_URI'], "/app/"), 'session') . Url::sessionParameter();
        $this->data['label_tests_fail'] = sprintf($this->data['attrs']['label_tests_fail'], $this->data['link_tests_fail']);

        $this->data['label_tests_pass'] = sprintf($this->data['attrs']['label_tests_pass'],
            '/ci/redirect/pageSet/' . urlencode($this->data['attrs']['pass_page_set']) . '/'
            . Url::deleteParameter(Text::getSubstringAfter($_SERVER['REQUEST_URI'], "/app/"), 'session') . Url::sessionParameter());

        $enabledPageSetMappings = $this->CI->model('Pageset')->getEnabledPageSetMappingArrays();

        if($currentPageSetPath === $this->data['attrs']['fail_page_set']) {
            $automaticallyRedirect = false;
            $this->data['link_tests_fail'] = '';
            $this->data['label_tests_fail'] = '';
        }
        else if($currentPageSetPath === $this->data['attrs']['pass_page_set']) {
            $this->data['showSuccessMessage'] = false;
        }

        if($this->data['attrs']['fail_page_set'] !== 'default' && !isset($enabledPageSetMappings[$this->data['attrs']['fail_page_set']])) {
            $automaticallyRedirect = false;
            $this->data['link_tests_fail'] = '';
            $this->data['label_tests_fail'] = $this->data['attrs']['label_no_link'];
            $this->data['runJSTests'] = $this->data['attrs']['perform_javascript_checks_with_no_link'];
        }
        if($this->data['attrs']['pass_page_set'] !== 'default' && !isset($enabledPageSetMappings[$this->data['attrs']['pass_page_set']])) {
            $this->data['showSuccessMessage'] = false;
            $this->data['label_tests_pass'] = '';
        }

        if(!$this->data['link_tests_fail'] && !$this->data['label_tests_pass']) {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(PG_SET_TESTS_PASS_INV_PG_SET_TESTS_MSG));
            return false;
        }

        $this->data['automatically_redirect'] = $automaticallyRedirect;
        if($automaticallyRedirect) {
            $this->CI->clientLoader->addHeadContent('<noscript><META http-equiv="refresh" content="0;URL=' . $this->data['link_tests_fail'] . '"></noscript>');
        }
    }
}
