<?php
namespace RightNow\Widgets;

use RightNow\Libraries\Search;

class SourceSearchField extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $search = Search::getInstance($this->data['attrs']['source_id']);
        $filter = $search->getFilter($this->data['attrs']['filter_type']);

        $this->displayNotices($search->getWarnings(), true);

        if ($filter) {
            $prefill = $filter['value'] && $filter['value'] !== '*' ? $filter['value'] : '';

            $this->data['js'] = array(
                'prefill' => $prefill,
                'filter'  => $filter,
            );
        }
        else {
            $this->displayNotices($search->getErrors());
            return false;
        }
    }

    /**
     * Displays any notices that are found
     * @param array $notices Array of notice messages
     * @param bool $warning Whether or not these notices are warnings or errors
     */
    protected function displayNotices (array $notices, $warning = false) {
        foreach ($notices as $notice) {
            echo $this->reportError($notice, !$warning);
        }
    }
}
