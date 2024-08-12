<?php
namespace RightNow\Widgets;
use RightNow\Utils\Url;

class RelatedKnowledgeAdvanceAnswers extends \RightNow\Libraries\Widget\Base {
    private $answerListApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $question = $this->CI->model('CommunityQuestion')->get(Url::getParameter('qid'))->result;
        if (!$prdCatExtId = $question->{$this->data['attrs']['related_by']}->ID) return false;

        $filter = array(
            'pageSize' => $this->data['attrs']['limit'],
            'pageNumber' => 0,
            'type' => 'popular',
            'categoryExtId' => $prdCatExtId,
            'answerListApiVersion' => $this->answerListApiVersion
        );
        $articles = $this->CI->model('Okcs')->getArticlesSortedBy($filter);
        $this->data['articles'] = $articles->items;
    }
}
