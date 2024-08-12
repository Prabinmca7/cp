<?php
namespace RightNow\Widgets;
use RightNow\Utils\Config,
    RightNow\Utils\Text;

class OkcsAnswerNotificationManager extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        $subscriptionList = $this->CI->model('Okcs')->getSubscriptionList();
        $titleLength = $this->data['attrs']['max_wordbreak_trunc'];
        $viewType = $this->data['attrs']['view_type'];
        $maxRecords = $this->data['attrs']['view_type'] === 'table' ? $this->data['attrs']['rows_to_display'] : (is_array($subscriptionList->items) ? count($subscriptionList->items) : null);
        $this->data['fields'] = $this->getTableFields();
        $list = array();
        if (!is_null($subscriptionList) && !isset($subscriptionList->error) && isset($subscriptionList->items) && is_array($subscriptionList->items) && count($subscriptionList->items) > 0) {
            foreach ($subscriptionList->items as $document) {
                if($maxRecords > 0) {
                    if($document->subscriptionType === 'SUBSCRIPTIONTYPE_CONTENT' && $document->content !== null) {
                        $subscriptionID = $document->recordId;
                        $dateAdded = $this->CI->model('Okcs')->processIMDate($document->dateAdded);
                        $document = $document->content;
                        foreach ($this->data['fields'] as $field) {
                            if (!property_exists($document, $field['name']) && !isset($field['name']) && $field['name'] !== 'expires') {
                                echo $this->reportError(sprintf(Config::getMessage(RES_OBJECT_PROPERTY_PCT_S_IS_NOT_MSG), $field['name']));
                                return false;
                            }
                        }
                        $document->title = Text::escapeHtml($document->title);
                        $item = array(
                            'documentId'        => $document->documentId,
                            'answerId'          => $document->answerId,
                            'title'             => is_null($titleLength) ? $document->title : Text::truncateText($document->title, $titleLength),
                            'expires'           => $dateAdded,
                            'subscriptionID'    => $subscriptionID
                        );
                        array_push($list, $item);
                        $maxRecords--;
                    }
                }
                else {
                    break;
                }
            }
            $this->data['subscriptionList'] = $list;
            $this->data['answerUrl'] = '/app/' . Config::getConfig(CP_ANSWERS_DETAIL_URL) . '/a_id/';
            $this->data['clickToSortMsg'] = $this->data['attrs']['label_sortable'];
        }
        else if (is_array($subscriptionList->items) && (count($subscriptionList->items) === 0) && $this->data['attrs']['hide_when_no_results']) {
            $this->classList->add('rn_Hidden');
        }
        $this->data['js']['fields'] = $this->data['fields'];
        $this->data['js']['answerUrl'] = $this->data['answerUrl'];
        $this->data['js']['failureMsgOnUnsub'] = Config::getMessage(SUBSCRIPTION_DOES_NOT_EXIST_LBL);
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
                case "documentId":
                    $labelAttribute = $this->data['attrs']['label_doc_id'];
                    break;
                case "answerId":
                    $labelAttribute = $this->data['attrs']['label_answer_id'];
                    break;
                case "title":
                    $labelAttribute = $this->data['attrs']['label_summary'];
                    break;
                case "expires":
                    $labelAttribute = $this->data['attrs']['label_subscription_date'];
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
