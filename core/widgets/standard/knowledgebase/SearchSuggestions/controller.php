<?php
namespace RightNow\Widgets;

class SearchSuggestions extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $searchSuggestionsConfig = \RightNow\Utils\Config::getConfig(SEARCH_SUGGESTIONS_DISPLAY);
        if(!($searchSuggestionsConfig & SRCH_SUGGESTIONS_DSPLY_PRODS) && !($searchSuggestionsConfig & SRCH_SUGGESTIONS_DSPLY_CATS)) {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(WARN_SRCH_SUGG_DISP_CFG_SET_VAL_1_2_MSG));
            return false;
        }

        $filters = array();
        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        $reportToken = \RightNow\Utils\Framework::createToken($this->data['attrs']['report_id']);

        $results = $this->CI->model('Report')->getDataHTML($this->data['attrs']['report_id'], $reportToken, $filters, null)->result;
        $this->data['js'] = $this->data['parameters'] = array();
        $this->data['relatedProducts'] = (is_array($results) && count($results['related_prods'])) ? $results['related_prods'] : array();
        $this->data['relatedCategories'] = (is_array($results) && count($results['related_cats'])) ? $results['related_cats'] : array();

        foreach(array(
            'prod' => array('key' => 'productFilter', 'value' => 'p'),
            'cat' => array('key' => 'categoryFilter', 'value' => 'c'),
        ) as $filterName => $info) {
            if($this->CI->model('Report')->getFilterByName($this->data['attrs']['report_id'], $filterName)->result) {
                $this->data['js'][$info['key']] = $info['value'];
                $this->data['parameters'][$info['value']] = \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url'], array($info['value']));
            }
            else {
                echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(FILTER_PCT_S_EXIST_REPORT_ID_PCT_S_LBL), $filterName, $this->data['attrs']['report_id']));
                return false;
            }
        }

        if(!count($this->data['relatedProducts']) && !count($this->data['relatedCategories']))
            $this->classList->add('rn_Hidden');

        $this->data['attrs']['report_page_url'] = (($this->data['attrs']['report_page_url'] === '') ? '/app/' . $this->CI->page : $this->data['attrs']['report_page_url']) . \RightNow\Utils\Url::sessionParameter();

        //Remove any p or c parameters in attribute since they'll be ignored
        if($this->data['attrs']['add_params_to_url'] !== '') {
            $appendedParameters = explode(',', trim($this->data['attrs']['add_params_to_url']));
            foreach($appendedParameters as $key => $parameter) {
                if(($trimmedParameter = trim($parameter)) && ($trimmedParameter === 'p' || $trimmedParameter === 'c'))
                    unset($appendedParameters[$key]);
            }
            $this->data['attrs']['add_params_to_url'] = (count($appendedParameters)) ? implode(',', $appendedParameters) : '';
        }
    }
}
