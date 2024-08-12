<?php
namespace RightNow\Widgets;

use RightNow\Utils\Config;

class ResultInfo extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);

    }

    function getData()
    {
        if($this->data['attrs']['add_params_to_url'])
        {
            $appendedParameters = explode(',', trim($this->data['attrs']['add_params_to_url']));
            foreach($appendedParameters as $key => $parameter)
            {
                if(trim($parameter) === 'kw')
                {
                    unset($appendedParameters[$key]);
                    break;
                }
            }
            $this->data['attrs']['add_params_to_url'] = (count($appendedParameters)) ? implode(',', $appendedParameters) : '';
            $this->data['appendedParameters'] = \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url']);
        }

        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        if (!$this->helper('Social')->validateModerationMaxDateRangeInterval($this->data['attrs']['max_date_range_interval'])) {
            echo $this->reportError(Config::getMessage(MAX_FMT_YEAR_T_S_EX_90_S_5_YEAR_ETC_MSG));
            return false;
        }        
        $filters = $this->CI->model('Report')->cleanFilterValues($filters, $this->helper('Social')->getModerationDateRangeValidationFunctions($this->data['attrs']['max_date_range_interval']));
        $reportToken = \RightNow\Utils\Framework::createToken($this->data['attrs']['report_id']);
        if($this->data['attrs']['combined_results'])
        {
            $format['hiddenColumns'] = true;
        }
        $results = $this->CI->model('Report')->getDataHTML($this->data['attrs']['report_id'], $reportToken, $filters, isset($format) ? $format : null)->result;
        //hide elements with no info
        $this->data['suggestionClass'] = $this->data['spellClass'] = $this->data['noResultsClass'] = $this->data['resultClass'] = 'rn_Hidden';
        if(is_array($results['ss_data']) && count($results['ss_data']))
        {
            $this->data['suggestionClass'] = '';
            $this->data['suggestionData'] = $results['ss_data'];
        }
        if($results['spelling'])
        {
            $this->data['spellClass'] = '';
            $this->data['spellData'] = $results['spelling'];
        }
        if($results['total_num'] === 0 && (!isset($results['topics']) || !$results['topics']) && ($results['search_term'] !== '' || $this->data['attrs']['show_no_results_msg_without_search_term']))
        {
            //display 'no results' message only if there was a search query or show_no_result_msg_without_search_term attribute is set to true and no results were found; don't display if there's topic tree results
            $this->data['noResultsClass'] = '';
        }
        else if(!$results['truncated'])
        {
            $this->data['resultClass'] = '';
            $this->data['firstResult'] = $results['start_num'];
            $this->data['lastResult'] = $results['end_num'];
            $this->data['totalResults'] = $results['total_num'];
        }

        if($this->data['attrs']['show_no_results_msg_without_search_term'] === true)
        {
            $this->data['attrs']['label_no_results_suggestions'] = '';
        }

        if(!$this->data['attrs']['combined_results'] && $results['search_term'] !== null && $results['search_term'] !== '' && $results['search_term'] !== false)
        {
            $stopWords = $results['stopword'];
            $noDictWords = $results['not_dict'];
            $searchTerms = explode(' ', $results['search_term']);
            $this->data['searchQuery'] = array();

            //construct search results message for the searched-on terms
            foreach($searchTerms as $word)
            {
                //get rid of punctuation, whitespace
                $strippedWord = preg_replace('/\W/', '', $word);
                //a word in the search query was a stopword
                if($stopWords && $strippedWord && strstr($stopWords, $strippedWord) !== false)
                    $type = 'stop';
                //a word in the search query was a no_dict word
                else if($noDictWords && $strippedWord && strstr($noDictWords, $strippedWord) !== false)
                    $type = 'notFound';
                //probably a valid search term
                else
                    $type = 'normal';
                $word = htmlspecialchars($word, ENT_QUOTES, 'UTF-8', false);
                array_push($this->data['searchQuery'], array('word' => $word, 'url' => urlencode(str_replace('&amp;', '&', $word)) . '/search/1', $type => true));
            }
        }
        //validate sprintf strings so that a horrid php error isn't output
        if(substr_count($this->data['attrs']['label_results'], '%d') > 3)
        {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_ATTRIBUTE_3_PCT_D_ARGUMENTS_MSG), 'label_results'));
            return false;
        }
        else if(substr_count($this->data['attrs']['label_results_search_query'], '%d') > 3)
        {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_ATTRIBUTE_3_PCT_D_ARGUMENTS_MSG), 'label_results_search_query'));
            return false;
        }

        if($this->data['attrs']['combined_results'])
        {
            if(!$this->data['attrs']['display_knowledgebase_results'])
            {
                // do not report on kb results
                $this->data['resultClass'] = 'rn_Hidden';
                $this->data['noResultsClass'] = '';
                $results['total_num'] =
                    $this->data['firstResult'] =
                        $this->data['lastResult'] =
                            $this->data['totalResults'] = 0;
            }
            $searchSocial = (\RightNow\Utils\Framework::inArrayCaseInsensitive(array('all', 'social'), $this->data['attrs']['combined_results']) && \RightNow\Utils\Config::getConfig(COMMUNITY_ENABLED, 'RNW'));

            $combinedResults = ($searchSocial ? $this->addCommunityResults($filters['keyword']->filters->data) : 0);
        }

        $this->data['js'] = array(
            'linkUrl' => "/app/{$this->CI->page}/search/1/kw/",
            'totalResults' => isset($this->data['totalResults']) ? $this->data['totalResults']  : 0,
            'lastResult' => isset($this->data['lastResult']) ? $this->data['lastResult'] : 0,
            'firstResult' => isset($this->data['firstResult']) ? $this->data['firstResult'] : 0,
            'searchTerm' => $filters['keyword']->filters->data,
            'combinedResults' => (isset($combinedResults) && $combinedResults) ? $combinedResults : 0,
            'prunedAnswers' => isset($prunedAnswers) ? $prunedAnswers : null,
            'social' => isset($searchSocial) ? $searchSocial : null,
            'error' => $results['error'],
        );
    }

    /**
    * Retrieves community results (if COMMUNITY_ENABLED) and adds the number of results to the widget's total.
    * @param string $keyword Search term
    * @return int Number of community results or zero if no results
    */
    protected function addCommunityResults($keyword) {
        $socialResults = $this->CI->model('Social')->request('performSearch', $keyword, 20, null, $this->data['attrs']['social_resource_id'], null, null)->getResponse()->result;
        return $this->addCombinedResults($this->data, $filters['page'], $socialResults->totalCount);
    }

    /**
    * Adds results to the widget's total.
    * @param array &$data The widget's search data; pass-by-reference
    * @param int $page The current page of search results
    * @param int $numberOfAdditionalResults The number of results to add
    * @return int Number of results added
    */
    protected function addCombinedResults(array &$data, $page, $numberOfAdditionalResults) {
        if($numberOfAdditionalResults) {
            $numberOfAdditionalResults = ($numberOfAdditionalResults > 20 ? 20 : $numberOfAdditionalResults);
            $data['totalResults'] += $numberOfAdditionalResults;
            if(!$data['noResultsClass'] && $numberOfAdditionalResults) {
                $data['noResultsClass'] = 'rn_Hidden';
                $data['resultClass'] = '';
                $data['firstResult'] = 1;
            }
            $indexToIncrement = (!$page || $page < 2) ? 'lastResult' : 'firstResult';
            $data[$indexToIncrement] += $numberOfAdditionalResults;
        }
        return $numberOfAdditionalResults ?: 0;
    }
}
