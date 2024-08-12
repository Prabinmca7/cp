<?php
namespace RightNow\Widgets;

class TopicBrowse extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl(array('report_id' => $this->data['attrs']['report_id']), $urlFilters);
        if (!$filters = $this->CI->model('Report')->getFilterByName($this->data['attrs']['report_id'], $this->data['attrs']['filter_name'])->result) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(FILTER_PCT_S_EXIST_REPORT_PCT_S_LBL), $this->data['attrs']['filter_name'], $this->data['attrs']['report_id']));
            return false;
        }

        // if the topic browse filter exists in the URL use that as the default
        if ($urlFilter = $urlFilters[$this->data['attrs']['filter_name']]->filters->data[0]) {
            $filters['default_value'] = $urlFilter;
        }

        if(!$this->CI->model('Report')->getClusterToAnswersAlias($this->data['attrs']['report_id'])->result) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(RPT_JOINED_CLSTR_TREE2ANSWERS_ORDER_MSG), $this->data['attrs']['filter_name'], $this->data['attrs']['report_id']));
            return false;
        }
        if (!count($topics = $this->CI->model('Topicbrowse')->getTopicBrowseTree()->result)) {
            return false;
        }

        $this->data['js'] = array(
            'rnSearchType' => 'topicBrowse',
            'filters'      => $filters,
            'topics'       => $topics,
        );

        if($searchTerm = \RightNow\Utils\Url::getParameter('kw')) {
            //get matches & relevancy
            $searchResults = $this->CI->model('Topicbrowse')->getSearchBrowseTree($searchTerm)->result;
            if($searchResults && count($searchResults)) {
                $searchResults = array_values($searchResults);
                if(count($searchResults) === count($this->data['js']['topics']))
                    $this->data['js']['topics'] = array_map('array_merge', $this->data['js']['topics'], $searchResults);
            }
        }
    }
}
