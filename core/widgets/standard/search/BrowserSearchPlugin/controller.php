<?php

namespace RightNow\Widgets;

class BrowserSearchPlugin extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs){
        parent::__construct($attrs);
    }

    function getData(){
        //If attribute is set, split out pages and remove whitespace
        if(!strlen($this->data['attrs']['pages']))
            return false;

        $this->data['attrs']['pages'] = explode(',', str_replace(' ', '', $this->data['attrs']['pages']));

        $segmentUrl = '';
        $pageSegments = $this->CI->config->item('parm_segment');
        //Grab all the page URL segments after page/render and before parms
        for($i = 3; $i < $pageSegments; $i++)
            $segmentUrl .= $this->CI->uri->segment($i) . '/';

        //Strip off last appended slash
        $segmentUrl = substr($segmentUrl, 0, strlen($segmentUrl) - 1);

        //if we're on the page specified then construct link
        if(in_array($segmentUrl, $this->data['attrs']['pages']))
            $this->constructOutput($segmentUrl);
        else
            return false;
    }

    /**
     * Updates widget values if current page is configured for use by this widget
     * @param string $page The current page
     */
    protected function constructOutput($page){
        if(strlen($this->data['attrs']['search_page']))
            $page = $this->data['attrs']['search_page'];

        if(substr($page, -1) !== '/')
            $page .= '/';
        if(substr($page, 0) !== '/')
            $page = '/' . $page;
        //add search parm
        $page .= 'kw/{searchTerms}';

        $this->data['url'] = urlencode(\RightNow\Utils\Url::getShortEufAppUrl('sameAsCurrentPage', $page));
        $this->data['title'] = $this->data['attrs']['title'];
        $this->data['attrs']['title'] = urlencode($this->data['attrs']['title']);
        $this->data['attrs']['description'] = urlencode($this->data['attrs']['description']);
        $this->data['attrs']['icon_path'] = urlencode($this->data['attrs']['icon_path']);
    }
}
