<?php
namespace RightNow\Widgets;

class ClickjackPrevention extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if(empty($this->data['attrs']['allow_from'])) {
            header("X-Content-Security-Policy: frame-ancestors " . $this->data['attrs']['frame_options']);
            header("Content-Security-Policy: frame-ancestors " . $this->data['attrs']['frame_options']);
            header("X-Frame-Options: " . $this->data['attrs']['frame_options']);
        }
        else {
            $this->data['fullUrlPath'] = parse_url(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
            $this->data['subDomain'] = explode(".", isset($this->data['fullUrlPath']['host']) ? $this->data['fullUrlPath']['host'] : '');
            $this->data['wildCardFullUrl'] = str_replace($this->data['subDomain'][0], "*", isset($this->data['fullUrlPath']['host']) ? $this->data['fullUrlPath']['host'] : '');
            $this->data['allowFromArray'] = explode(",", $this->data['attrs']['allow_from']);
            $domainUrls = array();
            foreach ($this->data['allowFromArray'] as $domain) {
                $domainUrls[] = "http://" . trim($domain);
                $domainUrls[] = "https://" . trim($domain);
            }
            $urls = trim(implode(" ", $domainUrls));
            if(!empty($urls)) {
                header("X-Content-Security-Policy: frame-ancestors " . $urls);
                header("Content-Security-Policy: frame-ancestors " . $urls);
            }
        }
    }
}