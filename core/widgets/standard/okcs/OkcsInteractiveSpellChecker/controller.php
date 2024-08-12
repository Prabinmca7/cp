<?php
namespace RightNow\Widgets;

use RightNow\Utils\Config,
    RightNow\Libraries\Search,
    RightNow\Utils\Url,
    RightNow\Utils\Okcs;

class OkcsInteractiveSpellChecker extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }

        $search = Search::getInstance($this->data['attrs']['source_id']);
        $resultCount = Url::getParameter('resultCount');
        if($this->data['attrs']['results_per_page']) {
            $this->data['perPageList'] = array_map('trim', explode(",", $this->data['attrs']['results_per_page']));
            $this->data['defaultCount'] = $resultCount ? $resultCount : $this->data['perPageList'][0];
        }
        $filters = array('docIdRegEx' => array('value' => $this->data['attrs']['document_id_reg_ex']),
                        'resultCount' => array('value' => isset($this->data['defaultCount']) ? $this->data['defaultCount'] : 0),
                        'transactionID' => array('value' => null),
                        'docIdNavigation' => array('value' => $this->data['attrs']['doc_id_navigation']));
        $resultSet = $search->addFilters($filters)->executeSearch();
        $query = isset($resultSet->searchResults['query']) ? $resultSet->searchResults['query'] : null;
        $this->data['js'] = array('filter' => $search->getFilters(), 'sources' => $search->getSources());

        if ($interactiveFlag = !empty($query->spellchecked)) {
            $this->data['paraphrase'] = $this->constructBestQuestion($query);
        }
        else {
            $this->data['visibilityClass'] = 'rn_Hidden';
        }
    }

    /**
     * Checks for a source_id error. Emits an error message if a problem is found.
     * @return boolean True if an error was encountered False if all is good
    */
    private function sourceError() {
        if(\RightNow\Utils\Text::stringContains($this->data['attrs']['source_id'], ',')) {
            echo $this->reportError(Config::getMessage(THIS_WIDGET_ONLY_SUPPORTS_A_SNGL_I_UHK));
            return true;
        }
        return false;
    }

    /**
     * Constructs the best possible question out of the suggestions avaialable
     * in the response
     * @param object $query Query object from search result response
     * @return string $paraphrase Constructed question
    */
    private function constructBestQuestion($query) {
        //Iterate through the response to construct the best possible question
        $paraphrase = '';
        foreach($query->spellchecked->corrections as $item) {
            // For correctly spelt word
            if($item->correction) {
                $paraphrase .= trim($item->correction) . ' ';
                $fieldParaphrase .= trim($item->correction) . ' ';
            }
            // For misspelt word
            else {
                $confidenceLevel = 0;
                foreach($item->suggestions as $suggestionItem) {
                    if($suggestionItem->confidence > $confidenceLevel) {
                        $confidenceLevel = $suggestionItem->confidence;
                        $bestValue = trim($suggestionItem->value);
                    }
                }

                $fieldParaphrase .= $bestValue . ' ';
                $linkValue = '<span class=\'rn_CorrectedWord\'>' . $bestValue . '</span>';
                $paraphrase .= $linkValue . ' ';
            }
        }
        $this->data['js'] = array('fieldParaphrase' => $fieldParaphrase);
        return $paraphrase;
    }
}
