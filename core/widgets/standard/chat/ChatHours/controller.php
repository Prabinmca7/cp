<?php  

namespace RightNow\Widgets;

class ChatHours extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $this->data['chatHours'] = $this->CI->model('Chat')->getChatHours()->result;
        $this->data['show_hours'] = !$this->data['chatHours']['inWorkHours'];

        $chatHoursData = $this->data['chatHours']['hours_data'];
        $timeZoneFirstLetter = substr($chatHoursData['time_zone'], 0, 1);
        // Following is the temporary solution to add context to displayed time-zones to overcome PHP time-zone abbrievations error
        if(($chatHoursData['time_zone']) && ($timeZoneFirstLetter === "+" || $timeZoneFirstLetter === "-")) {
            // if corrupt time-zone is found in chat-hours data, then append UTC before it to add context
            for($i = 0; $i < count($this->data['chatHours']['hours']); $i++) {
                $pos = strpos($this->data['chatHours']['hours'][$i][1], $chatHoursData['time_zone']);
                if ($pos) {
                    $this->data['chatHours']['hours'][$i][1] = substr_replace($this->data['chatHours']['hours'][$i][1], 'UTC', $pos, 0);
                }
            }
            $pos = strpos($this->data['chatHours']['current_time'], $chatHoursData['time_zone']);
            if ($pos) {
                $this->data['chatHours']['current_time'] = substr_replace($this->data['chatHours']['current_time'], 'UTC', $pos, 0);
            }
            $this->data['chatHours']['hours_data']['time_zone'] = 'UTC'.$chatHoursData['time_zone'];
        }
    }
}
