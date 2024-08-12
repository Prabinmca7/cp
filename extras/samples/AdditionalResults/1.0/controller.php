<?php
/**
 * File: controller.php
 * Abstract: Extending controller for AdditionalResults widget
 * Version: 1.0
 */

namespace Custom\Widgets\search;

use RightNow\Libraries\Cache;

/**
* AdditionalResults
*
* @uses RightNow\Widgets\Multiline
*/
class AdditionalResults extends \RightNow\Widgets\Multiline {
    const CACHE_TIME = 1800; // Amount of time to cache results from the third-party API (in seconds)
    const API_URL = 'http://api.duckduckgo.com/?q=%s&format=json';

    function __construct($attrs) {
        parent::__construct($attrs);

        // Register the AJAX-handler method for this widget.
        // The key is the name of the widget's AJAX-type attribute.
        // The value can either be a String name of the method that
        // will handle the AJAX request OR an associative array with
        // two keys:
        // * method: String name of the method that will handle the AJAX request
        // * clickstream: String name of a clickstream entry to record for the AJAX request
        $this->setAjaxHandlers(array(
            'search_endpoint' => array(
                'method'      => 'getSearchResults',
                'clickstream' => 'custom_search',
            ),
        ));
    }

    function getData() {
        if ($kw = \RightNow\Utils\Url::getParameter('kw')) {
            $this->data['additionalResults'] = json_decode($this->getResults($kw));
        }

        return parent::getData();
    }

    /**
     * Produces search results as an AJAX endpoint. Echos out JSON encoded results
     * @param array $params POST params
     * @return void
     */
    function getSearchResults(array $params) {
        $response = $this->getResults($params['keyword']);

        header('Content-Length: ' . strlen($response));
        header('Content-type: application/json');
        echo $response;
    }

    /**
     * Makes the HTTP request thru the PersistentReadThroughCache
     * caching mechanism to the third-party API.
     * @param string $kw Search query
     * @return string Response from third-party; if no search query
     * is supplied, an object consisting of a "RelatedTopics" property
     * with an empty array is returned
     * @see http://api.duckduckgo.com/ Format of what's returned
     */
    protected function getResults($kw) {
        // If no query is supplied, the API doesn't return anything.
        // Our code expects that, at the very least,
        // this minimal object is returned.
        if ($kw === '' || $kw === null) return '{"RelatedTopics":[]}';

        $cache = new Cache\PersistentReadThroughCache(self::CACHE_TIME, function($kw, $url) {
            \RightNow\Utils\Framework::logMessage(sprintf($url, $kw));
            return @file_get_contents(sprintf($url, $kw));
        });

        return $cache->get($kw, self::API_URL);
    }
}