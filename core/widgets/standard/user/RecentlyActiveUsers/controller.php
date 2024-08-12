<?php
namespace RightNow\Widgets;
use RightNow\Utils\Date,
    RightNow\Utils\Url;

class RecentlyActiveUsers extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $interval = str_replace("past_", "", $this->data['attrs']['show_users_active_in']);
        $result = $this->CI->model('CommunityUser')->getRecentlyActiveUsers($this->data['attrs']['max_user_count'], $interval)->result;
        $this->data['users'] = !$result ? null : array_slice($result, 0, $this->data['attrs']['max_user_count'], true);
    }
}