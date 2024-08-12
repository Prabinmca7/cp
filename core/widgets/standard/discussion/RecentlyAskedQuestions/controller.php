<?php
namespace RightNow\Widgets;

use RightNow\Utils\Framework,
    RightNow\Libraries\Search;

class RecentlyAskedQuestions extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if($this->data['attrs']['questions_with_comments'] === 'no_comments') {
            $questionIds = array_unique($this->getQuestionsWithNoComments());
            $questionList = $this->CI->model('CommunityQuestion')->getQuestionsByIDs($questionIds)->result;
        }
        else {
            $filters = $this->getFilters();
            $questionList = $this->CI->model('CommunityQuestion')->getRecentlyAskedQuestions($filters)->result;
        }
        $this->data['js']['questions'] = array();
        foreach ($questionList as $question) {
            if (!array_key_exists($question->ID, $this->data['js']['questions'])) {
                $this->data['js']['questions'][$question->ID] = $question;
            }
        }
    }

    /**
     * Fetches the questions which have no comments
     * @return array Array of question IDs which have no comments
     */
    protected function getQuestionsWithNoComments() {
        $i = 1;
        $results = $filteredResults = array();
        $totalPages = null; $page = 0;
        $instance = Search::getInstance('SocialSearch');
        $interval = str_replace('past_', '', $this->data['attrs']['no_comments_time_period']);
        $filters = array('limit' => array('value' => 100), 'createdTime' => array('value' => $interval), 'query' => array('value' => '*'), 'sort' => array('value' => 2), 'direction' => array('value' => 1), 'numberOfBestAnswers' => array('value' => 0));

        while(($totalPages === null || ++$page <= $totalPages) && (count($filteredResults) < $this->data['attrs']['maximum_questions'])){
            if ($this->data['attrs']['category_filter']) {
                $filters['category'] = array('value' => $this->data['attrs']['category_filter']);
            }
            if ($this->data['attrs']['product_filter']) {
                $filters['product'] = array('value' => $this->data['attrs']['product_filter']);
            }
            $filters['page'] = array('value' => $i++);
            $searchResults = $this->executeSearch($instance, $filters);
            if($totalPages === null){
                 $totalPages = intval(ceil($searchResults->total / 100));
            }
            $results = $searchResults->results;
            $filteredResults = array_merge($filteredResults, array_filter($results, function ($result) {
                if ($result->SocialSearch->commentCount === 0) {
                    return $result;
                }
            }));
        }
        $questionIds = array_slice(array_map(function($item) {
            return $item->SocialSearch->id;
        }, $filteredResults), 0, $this->data['attrs']['maximum_questions']);
        return $questionIds;
    }

    /**
     * Performs the search, applying the given filters.
     * @param RightNow\Libraries\Search $search Search instance
     * @param array $filters Search filters
     * @return \RightNow\Libraries\SearchResults Results from the search
     */
    protected function executeSearch($search, array $filters) {
        Search::clearCache();
        $results = $search->addFilters($filters)->executeSearch();
        return $results;
    }

    /**
     * Sets the filter values based on widget attribute values
     * @return array Array filter parameters
     */
    protected function getFilters() {
        switch($this->data['attrs']['questions_with_comments'])
        {
            case 'best_answers_only':
                $filterValue = 'with';
                break;
            case 'no_best_answers':
                $filterValue = 'without';
                break;
            default:
                $filterValue = 'all';
        }

        $filters = array(
            'maxQuestions'    => $this->data['attrs']['maximum_questions'],
            'includeChildren' => $this->data['attrs']['include_children'],
            'questionsFilter' => $filterValue,
            'answerType'      => ($filterValue === 'with' || $filterValue === 'without') ? null : array()
        );
        if ($this->data['attrs']['category_filter']) {
            $filters['category'] = $this->data['attrs']['category_filter'];
        }
        if ($this->data['attrs']['product_filter']) {
            $filters['product'] = $this->data['attrs']['product_filter'];
        }
        return $filters;
    }
}
