<?php
namespace RightNow\Widgets;

use RightNow\Utils\Url,
    RightNow\Utils\Okcs,
    RightNow\Libraries\Search;

class SearchRating extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $search = Search::getInstance($this->data['attrs']['source_id']);
        $searchFlag = count($search->executeSearch()->results) > 0;
        $this->data['js'] = array(
            'priorTransactionID' => $this->CI->model('Okcs')->getPriorTransactionID(),
            'okcsSearchSession' => $this->CI->model('Okcs')->getSession(),
            'searchFlag' => $searchFlag,
            'docIdRegEx' => array('value' => $this->data['attrs']['document_id_reg_ex']),
            'docIdNavigation' => array('value' => $this->data['attrs']['doc_id_navigation']),
        );
        if($this->data['attrs']['source_id']) {
            $search = \RightNow\Libraries\Search::getInstance($this->data['attrs']['source_id']);
            $this->data['js']['filter'] = $search->getFilters();
            $this->data['js']['sources'] = $search->getSources();
        }
    }
}
