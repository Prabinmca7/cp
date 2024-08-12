<?php
namespace RightNow\Widgets;
use RightNow\Utils\Config,
    RightNow\Utils\Url,
    RightNow\Utils\Text;
class OkcsFavoritesList extends \RightNow\Libraries\Widget\Base {
    private $answerViewApiVersion = 'v1';
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        $favoriteList = $this->CI->model('Okcs')->getFavoriteList();
        $titleLength = $this->data['attrs']['max_wordbreak_trunc'];
        $viewType = $this->data['attrs']['view_type'];
        $this->data['fields'] = $this->getTableFields();
        $list = array();
        $favoriteIds = isset($favoriteList->value) ? $favoriteList->value : null;
        if (!isset($favoriteList->error) && $favoriteIds !== '') {
            $userFavoritesList = !is_null($favoriteIds) ? array_unique(explode(",", $favoriteIds)) : null;
            $this->data['js']['userFavoritesList'] = $userFavoritesList;
            if($viewType === 'table' && $this->data['attrs']['enable_pagination_for_table']) {
                //enforce upper limit of 20 for attribute rows_to_display
                if($this->data['attrs']['rows_to_display'] > 20)
                    $this->data['attrs']['rows_to_display'] = 20;
                if( count($userFavoritesList) > 20)
                    $this->data['hasMore'] = true;
            }
            $favoriteIdArr = is_array($userFavoritesList) && count($userFavoritesList) > 20 ? array_splice($userFavoritesList, 0, 20) : $userFavoritesList;

            $answerDetailArr = $this->CI->model('Okcs')->getDetailsForAnswerId($favoriteIdArr);
            $maxRecords = $this->data['attrs']['view_type'] === 'table' ? $this->data['attrs']['rows_to_display'] : (is_array($favoriteIdArr) ? count($favoriteIdArr) : null);
            if(is_array($favoriteIdArr)){
                foreach ($favoriteIdArr as $favoriteId) {
                    if($maxRecords > 0) {
                        $answer = $answerDetailArr[$favoriteId];
                        if($answer) {
                            $title = $answer['title'];
                            $item = array(
                                'title'         => is_null($titleLength) ? $title : Text::truncateText($title, $titleLength),
                                'documentId'    => $answer['documentId'],
                                'answerId'      => $favoriteId
                            );
                            array_push($list, $item);
                            $maxRecords--;
                        }
                    }
                    else {
                        break;
                    }
                }
            }
            $this->data['js']['favoritesList'] = $this->data['favoritesList'] = $list;
            $this->data['answerUrl'] = '/app/' . Config::getConfig(CP_ANSWERS_DETAIL_URL) . '/a_id/';
        }
        else if (isset($favoriteList->items[0]->value) && $favoriteList->items[0]->value === '' && $this->data['attrs']['hide_when_no_favorites']) {
            $this->classList->add('rn_Hidden');
        }
        $this->data['js']['fields'] = $this->data['fields'];
        $this->data['js']['answerUrl'] = isset($this->data['answerUrl']) ? $this->data['answerUrl'] : null;
        $this->data['js']['docIdLabel'] = Config::getMessage(DOC_ID_LBL);
    }

    /**
    * Lists the fields of the table based on the value of the attribute 'display_fields' and extracts the label to use for each field.
    * @return array list of display fields
    */
    private function getTableFields() {
        $fields = array();
        $index = 0;
        $isTitle = false;
        foreach (explode("|", $this->data['attrs']['display_fields']) as $field) {
            switch ($field) {
                case "title":
                    $labelAttribute = $this->data['attrs']['label_title'];
                    break;
                case "documentId":
                    $labelAttribute = $this->data['attrs']['label_doc_id'];
                    break;
                default:
                    $labelAttribute = $this->data['attrs']['label_' . strtolower($field)];
            }

            $item = array(
                'name' => $field,
                'label' => $labelAttribute ?: $field,
                'columnID' => $index
            );
            $index++;
            array_push($fields, $item);
        }
        return $fields;
    }
}
