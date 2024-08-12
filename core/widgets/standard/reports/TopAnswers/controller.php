<?php
namespace RightNow\Widgets;

use RightNow\Utils\Url;

class TopAnswers extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs){
        parent::__construct($attrs);
    }

    function getData(){
        $answerContent = $this->CI->model('Answer')->getPopular(
            isset($this->data['attrs']['limit']) ? $this->data['attrs']['limit'] : null,
            isset($this->data['attrs']['product_filter_id']) ? $this->data['attrs']['product_filter_id'] : null,
            isset($this->data['attrs']['category_filter_id']) ? $this->data['attrs']['category_filter_id'] : null
        );

        if($answerContent->result === null){
            if($answerContent->error){
                echo $this->reportError($answerContent->error, true);
            }
            return false;
        }

        if ($procatID = Url::getParameter('p')) {
            $prodCatObject = $this->CI->model('Prodcat')->get($procatID)->result;
            $this->data['js']['prodcatName'] = $prodCatObject->Name;
        }

        $this->data['results'] = $this->truncateExcerptIfNeeded($answerContent->result);
    }

    /**
     * Truncates all excerpts contained in the $results object to the length specified by 'excerpt_max_length'
     * @param object $results Answer content results
     * @return object The answer content results with excerpts truncated if specified
     */
    protected function truncateExcerptIfNeeded($results) {
        // The limit of 256 below is a hard limit currently enforced by KnowledgeFoundation\Knowledge::GetPopularContent()
        if ($results && (($maxLength = $this->data['attrs']['excerpt_max_length']) < 256)) {
            foreach($results as $result) {
                $result->Excerpt = \RightNow\Utils\Text::truncateText($result->Excerpt, $maxLength, true);
            }
        }

        return $results;
    }
}