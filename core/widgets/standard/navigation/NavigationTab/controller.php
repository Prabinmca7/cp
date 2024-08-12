<?php
namespace RightNow\Widgets;

class NavigationTab extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        if(!$this->data['attrs']['external'])
            $this->data['attrs']['link'] .= \RightNow\Utils\Url::sessionParameter();

        //output the selected css class if we're on it's page
        if($this->data['attrs']['pages'] && $this->data['attrs']['css_class'])
        {
            $this->data['attrs']['pages'] = explode(',', str_replace(' ', '', $this->data['attrs']['pages']));
            $currentPage = $this->CI->page;
            foreach($this->data['attrs']['pages'] as $page)
            {
                if($currentPage === $page)
                {
                    $this->data['cssClass'] = $this->data['attrs']['css_class'];
                    $this->data['selectedAriaLabel'] = sprintf($this->data['attrs']['label_selected_tab'], $this->data['attrs']['label_tab']);
                }
            }
        }
        //get sub-pages, if any
        if($this->data['attrs']['subpages'])
        {
            $this->data['subpages'] = array();
            //get ea. comma-separated key > value pair
            $subPages = explode(',', $this->data['attrs']['subpages']);
            foreach($subPages as $value)
            {
                $splitPosition = strrpos($value, ' > ');
                if($splitPosition === false)
                {
                    echo $this->reportError("Invalid formatting of subpages attribute value '$value' : must be Name > URL separated.");
                    return false;
                }
                $pairValue['title'] = trim(substr($value, 0, $splitPosition));
                $pairValue['href'] = trim(substr($value, $splitPosition + 2));
                array_push($this->data['subpages'], $pairValue);
            }
        }
        if($this->data['attrs']['searches_done'] > 0)
        {
            $this->data['js']['searches'] = $this->CI->session->getSessionData('numberOfSearches');
            if($this->data['js']['searches'] < $this->data['attrs']['searches_done'])
                $this->classList->add('rn_Hidden');
        }
    }
}
