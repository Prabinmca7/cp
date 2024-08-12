<?php
namespace RightNow\Widgets;

class IncidentThreadDisplay extends \RightNow\Libraries\Widget\Output
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        if(parent::getData() === false)
            return false;

        // Validate data type
        if (!\RightNow\Utils\Connect::isIncidentThreadType($this->data['value']))
        {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(INCIDENTTHREADDISPLAY_DISP_THREAD_MSG));
            return false;
        }

        //Cast Connect array to a normal array. Connect fails if we need to call the array_reverse function
        //and we want to be consistent in what datatype we provide to the view
        $threadArray = (array)$this->data['value'];
        //Connect apparently doesn't guarantee thread order, so order items to ensure consistency
        unset($this->data['value']);
        usort($threadArray, function($a, $b){
            if($a->CreatedTime === $b->CreatedTime){
                if($a->DisplayOrder === $b->DisplayOrder){
                    return 0;
                }
                return ($a->DisplayOrder > $b->DisplayOrder) ? -1 : 1;
            }
            return ($a->CreatedTime > $b->CreatedTime) ? -1 : 1;
        });
        $this->data['value'] = $threadArray;
        if($this->data['value'] && $this->data['attrs']['thread_order'] === 'ascending')
        {
            $this->data['value'] = array_reverse($this->data['value']);
        }
    }
}
