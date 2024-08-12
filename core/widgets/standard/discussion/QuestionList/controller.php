<?php
namespace RightNow\Widgets;

use RightNow\Utils\Url,
    RightNow\Utils\Config;

class QuestionList extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $questionIDs = array();
        $dataType = ucfirst($this->data['attrs']['type']);
        $prodCatID = $this->data['attrs']['show_sub_items_for'];

        if(!$prodCatID) {
            $prodCatID = ($dataType === "Product") ? Url::getParameter('p') : Url::getParameter('c');
        }

        if (!$prodCatID) {
            echo $this->reportError(Config::getMessage(SHOWSUBITEMS_P_PRODUCTCATEGORY_L_PAGE_MSG));
            return false;
        }

        $this->data['questionHeader'] = $this->data['attrs']['label_question'];
        foreach ($this->data['attrs']['show_columns'] as $metadata) {
            $this->data['tableHeaders'][$metadata] = $this->data['attrs']['label_'.$metadata];
        }

        if ($this->data['attrs']['specify_questions']) {
            $specifiedQuestionIDs = explode(',', $this->data['attrs']['specify_questions']);
            $questionIDs = array_slice($specifiedQuestionIDs, 0, $this->data['attrs']['max_question_count'], true);
        }

        $result = $this->CI->model('CommunityQuestion')->getCommentCountByQuestion($dataType, $prodCatID, $questionIDs, $this->data['attrs']['show_columns'], $this->data['attrs']['max_question_count'], $this->data['attrs']['sort_order'])->result;
        $validQuestions = array_intersect($questionIDs, array_keys($result));
        if ($this->data['attrs']['specify_questions']) {
            $result = array_replace(array_flip($validQuestions), $result);
        }

        $this->data['result'] = $result;
    }
}