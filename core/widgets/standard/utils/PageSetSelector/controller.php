<?php
namespace RightNow\Widgets;

class PageSetSelector extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if(!\RightNow\Utils\Config::getConfig(CP_COOKIES_ENABLED)) {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(CP_COOKIES_ENABLED_ENABLED_ORDER_MSG));
            return false;
        }

        $this->data['sets'] = array('/' => array());
        $sets = explode(',', trim($this->data['attrs']['page_sets']));
        if(!count(array_filter($sets))) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_ATTRIBUTE_MSG), 'page_sets'));
            return false;
        }

        $enabledPageSetMappings = $this->CI->model('Pageset')->getEnabledPageSetMappingArrays();
        foreach($sets as $set) {
            list($pageSet, $label) = array_map('trim', explode('>', $set));
            if ($pageSet === "default" || isset($enabledPageSetMappings[$pageSet])) {
                $this->data['sets'][$pageSet]['label'] = $label;
                $this->data['sets'][$pageSet]['mapping'] = $pageSet;
            }
        }

        $cookie = isset($_COOKIE['agent']) && $_COOKIE['agent'] ? $_COOKIE['agent'] : '';
        if(!$cookie) {
            //not set - default user agent mode
            $currentPageSet = $this->CI->getPageSetPath(); //mobile, custom page set, or null (default page set)
            $cookie = $currentPageSet ? $currentPageSet : '/';
        }
        if($cookie) {
            //re-map default to '/'
            if($this->data['sets']['default']) {
                $this->data['sets']['/'] = $this->data['sets']['default'];
                unset($this->data['sets']['default']);
                //default page set
                if($cookie === '/')
                    $this->data['sets']['/']['current'] = true;
            }
            if(isset($this->data['sets'][$cookie]) && $this->data['sets'][$cookie]) {
                //a mobile or custom page set
                $this->data['sets'][$cookie]['current'] = true;
            }
            if($this->data['attrs']['cookie_expiration']) {
                $this->data['expires'] = date(DATE_COOKIE, time() + ($this->data['attrs']['cookie_expiration'] * 86400));
            }
            $this->data['secure'] = \RightNow\Utils\Config::getConfig(SEC_END_USER_HTTPS, 'COMMON') ? 'secure' : '';
        }

        if (count($this->data['sets']) <= 1) {
            $this->classList->add('rn_Hidden');
            $this->classList->remove('rn_PageSetSelector');
        }

        $this->data['currentPage'] = \RightNow\Utils\Text::getSubstringAfter(ORIGINAL_REQUEST_URI, "/app/");
    }
}
