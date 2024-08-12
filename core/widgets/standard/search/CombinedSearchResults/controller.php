<?php
namespace RightNow\Widgets;

class CombinedSearchResults extends Multiline {
    function __construct($attrs) {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array('combined_search_ajax' => 'getAjaxResults'));
    }

    function getData() {
        $ordering = array();

        $displayAnswers = $this->data['attrs']['display_knowledgebase_results'];
        $displaySocial = $this->data['attrs']['social_results'];

        if (($page = \RightNow\Utils\Url::getParameter('page')) && intval($page) > 1) {
            $displaySocial = 0;
        }

        if (!$this->requestReport()) {
            return false;
        }

        $keyword = $this->data['js']['filters']['keyword']->filters->data;
        $knowledgebaseAnswersDisplayed = $displayAnswers && count($this->data['reportData']['data']);

        if ($displaySocial) {
            if(\RightNow\Utils\Config::getConfig(COMMUNITY_ENABLED)) {
                $ordering[$displaySocial] = 'socialView.php';
                $this->data['baseUrl'] = $this->data['attrs']['social_author_link_base_url'] ?: \RightNow\Utils\Config::getConfig(COMMUNITY_BASE_URL, 'RNW');
                $socialRequest = $this->requestSocialResults($keyword);
            }
            else {
                $this->data['attrs']['social_results'] = 0;
            }
        }

        if ($socialRequest && $socialRequest->connectionMade) {
            $this->data['socialData'] = $this->getSocialResults($socialRequest);
        }

        if (!$displayAnswers) {
            $this->data['reportData']['data'] = null;
        }

        ksort($ordering);
        $this->data['ordering'] = $ordering;
        $this->data['js']['displayedComponents'] = count($ordering);
        $this->data['js']['searchTerm'] = $keyword;
    }

    /**
     * Includes the specified partial view located in the widget's directory
     * @param string $name Either 'intentGuideView.php' or 'socialView.php'
     */
    public function displaySubBlock($name) {
        static $path;
        $path || ($path = CORE_WIDGET_FILES . \RightNow\Utils\Widgets::getFullWidgetVersionDirectory($this->getPath()));
        include $path . $name;
    }

    /**
     * Retrieves search results from AJAX request. Echos out JSON encoded results
     * @param array $params Post parameters
     */
    function getAjaxResults(array $params) {
        if ($params['social'] === 'true') {
            $socialRequest = $this->requestSocialResults($params['keyword']);
        }

        $results = array();
        if ($socialRequest) {
            $results['social'] = array(
                'data' => $this->getSocialResults($socialRequest),
                'ssoToken' => \RightNow\Utils\Url::communitySsoToken(''),
            );
        }

        $this->renderJSON($results);
    }

    /**
     * Gets the report. Sets all appropriate instance variables for rendering the report.
     * @return bool True if the report was retrieved correctly, false if there was an error
     */
    protected function requestReport() {
        if(!$this->CI->model('Report')->getAnswerAlias($this->data['attrs']['report_id'])->result) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_D_REPORT_JOINED_ANSWERS_MSG), $this->data['attrs']['report_id']));
            return false;
        }
        $parametersToAdd = \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url']);
        $format = array(
            'truncate_size'     => $this->data['attrs']['truncate_size'],
            'emphasisHighlight' => $this->data['attrs']['highlight'],
            'urlParms'          => $parametersToAdd,
            'hiddenColumns'     => true,
            'dateFormat'        => $this->data['attrs']['date_format'],
        );
        $filters = array('recordKeywordSearch' => true);
        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        $reportToken = \RightNow\Utils\Framework::createToken($this->data['attrs']['report_id']);
        $this->data['reportData'] = $this->CI->model('Report')->getDataHTML($this->data['attrs']['report_id'], $reportToken, $filters, $format)->result;
        $filters['page'] = $this->data['reportData']['page'];
        $this->data['js'] = array(
            'filters'   => $filters,
            'format'    => $format,
            'r_tok'     => $reportToken,
            'urlParams' => $parametersToAdd,
        );
        return true;
    }

    /**
     * Initializes a connection to retrieve community results.
     * @param string $keyword Search term
     * @return object An instance of AsyncDataModelRequest
     */
    protected function requestSocialResults($keyword) {
        return $this->CI->model('Social')->request('performSearch', $keyword, 20, null, $this->data['attrs']['social_resource_id'], null, null);
    }

    /**
     * Returns social results.
     * @param object $socialRequest An AsyncDataModelRequest instance
     * @return array Contains 'results' and 'totalResults' keys
     */
    protected function getSocialResults($socialRequest) {
        $socialResults = $socialRequest->getResponse()->result;
        return array(
            'results' => ($socialResults) ? $this->CI->model('Social')->formatSearchResults($socialResults->searchResults, $this->data['attrs']['truncate_size'], $this->data['attrs']['highlight'], $keyword)->result : array(),
            'totalResults' => $socialResults->totalCount,
        );
    }
}
