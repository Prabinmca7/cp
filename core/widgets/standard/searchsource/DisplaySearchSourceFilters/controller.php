<?php
namespace RightNow\Widgets;

use RightNow\Libraries\Search,
    RightNow\Utils\Url;

class DisplaySearchSourceFilters extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $search = Search::getInstance($this->data['attrs']['source_id']);
        $this->data['js']['sourceCount'] = count($search->getSources());
        $searchFilters = $search->getFilters();

        // store filters in array to more easily add other filters in the future
        $this->data['js']['filters'] = array();

        if(($authorFilter = $searchFilters['author']) && ($authorFilter['value']) && ($socialUser = $this->CI->model('CommunityUser')->get($authorFilter['value'])->result)) {
            $this->data['js']['filters'][$authorFilter['type']] = $authorFilter;
            $this->data['js']['filters'][$authorFilter['type']]['label'] = $socialUser->DisplayName;
        }

        if(count($this->data['js']['filters']) === 0)
            $this->classList->add('rn_Hidden');
    }
}
