<?php

namespace RightNow\Controllers;

/**
 * Endpoint to expose details about the CP search functionality to other websites and search engines. ({@link http://en.wikipedia.org/wiki/OpenSearch})
 */
final class BrowserSearch extends Base
{
    public function __construct()
    {
        parent::__construct();
        if (!\RightNow\Utils\Config::getConfig(EU_SYNDICATION_ENABLE))
            exit;
    }

    /**
     * Outputs opensearchdescription XML to expose CP search endpoint
     *
     * @param string $url Urlencoded URL to be used to search by the search plugin
     * @param string $title Urlencoded title to be used for the search engine name
     * @param string $desc Urlencoded description to be used for the search engine description
     * @param string $imgPath Urlencoded path to image to be used for the search engine
     */
    public function desc($url, $title, $desc, $imgPath)
    {
        if (!\RightNow\Utils\Url::isExternalUrl(urldecode($url))) {
            $desc = '<?xml version="1.0" encoding="UTF-8"?>
                <OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/" xmlns:moz="http://www.mozilla.org/2006/browser/search/">
                    <ShortName>' . urldecode($title) . '</ShortName>
                    <Description>' . urldecode($desc) . '</Description>
                    <Language>' . LANG_DIR . '</Language>
                    <SyndicationRight>limited</SyndicationRight>
                    <OutputEncoding>UTF-8</OutputEncoding>
                    <InputEncoding>UTF-8</InputEncoding>
                    <Url template="' . urldecode($url) . '" type="text/html" method="get"/>';
            if(strlen($imgPath))
            {
                $desc .= '<Image width="16" height="16" type="image/x-icon">' . \RightNow\Utils\Url::getShortEufBaseUrl() . urldecode($imgPath) . '</Image>';
            }
            $desc .= '</OpenSearchDescription>';
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 30 * 24 * 60 * 60));
            \RightNow\Utils\Framework::writeContentWithLengthAndExit($desc, 'application/opensearchdescription+xml; charset="utf-8"');
        }
        else {
            header('HTTP/1.1 404');
            \RightNow\Utils\Url::redirectToErrorPage(404);
        }
    }
}