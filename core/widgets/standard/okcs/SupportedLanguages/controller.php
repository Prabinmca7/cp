<?php
namespace RightNow\Widgets;

use RightNow\Libraries\Search,
    RightNow\Utils\Config,
    RightNow\Utils\Url,
    RightNow\Utils\Okcs,
    RightNow\Utils\Text;

class SupportedLanguages extends \RightNow\Libraries\Widget\Base {
    private $supportedLanguageApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        $userLocale = $this->CI->model('Okcs')->getUserLanguagePreferences();
        if ($userLocale->error === null) {
            $userLocale = $interfaceLocale = \RightNow\Utils\Okcs::getInterfaceLocale();
            $this->data['js']['defaultLocale'] = str_replace("_", "-", $userLocale);
            $selectedLocale = urldecode(Url::getParameter('loc'));
            $searchText = strlen(Url::getParameter('kw')) === 0 ? Url::getParameter('keyword') : Url::getParameter('kw');
            
            $supportedLanguages = $this->CI->model('Okcs')->getSupportedLanguages($this->supportedLanguageApiVersion);
            if ($supportedLanguages !== null) {
                if ($supportedLanguages->errors !== null) {
                    echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($supportedLanguages->errors[0]));
                    return false;
                }
                else {
                    $availableLanguages = array();
                    $selected = !empty($selectedLocale) ? explode(",", $selectedLocale) : (count($userLocale) === 0 ? array($this->data['js']['defaultLocale']) : explode(",", $this->data['js']['defaultLocale']));
                    
                    // If url has keyword and no locale, no language should be checked.
                    if (!is_null($searchText) && strlen($selectedLocale) === 0)
                        $selected = null;

                    // Replace underscore (_) in locale code from IM API as Search API expects hyphen (-) in locale code
                    // then adds a key "selected" in the array to track if a locale code is to be marked as checked in View.
                    foreach ($supportedLanguages->items as $lang) {
                        $item = array('code' => str_replace("_", "-", $lang->localeCode), 'description' => $lang->localeDesc);
                        if (!is_null($selected)) {
                            foreach ($selected as $loc) {
                                if (strcmp($item['code'], $loc) === 0)
                                {
                                    $item['selected'] = true;
                                    break;
                                }
                                else {
                                    $item['selected'] = false;
                                }
                            }
                        }
                        if(strcmp($item['code'], $interfaceLocale) === 0) {
                            array_unshift($availableLanguages, $item);
                        }
                        else {
                            array_push($availableLanguages, $item);
                        }
                    }
                    $this->data['availableLanguages'] = $availableLanguages;
                    $search = Search::getInstance($this->data['attrs']['source_id']);
                    $filter = $search->getFilter($this->data['attrs']['filter_type']);
                    if ($filter) {
                        $this->data['js'] = array(
                            'filter'  => $filter
                        );
                    }
                }
            }
        }
        else {
            echo $this->reportError($this->CI->model('Okcs')->formatErrorMessage($userLocale->error));
            return false;
        }
    }
}

