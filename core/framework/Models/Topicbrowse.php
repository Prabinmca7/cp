<?php

namespace RightNow\Models;

use RightNow\Utils\Framework,
    RightNow\Api,
    RightNow\Internal\Sql\Topicbrowse as Sql;

require_once CORE_FILES . 'compatibility/Internal/Sql/Topicbrowse.php';

/**
 * Retrieval of topic browser trees.
 */
class Topicbrowse extends Base {
    const CACHEKEY = 'TopicBrowse';

    /**
     * Returns an array of Topicbrowse tree results.
     *
     * @return array Topicbrowse results
     */
    public function getTopicBrowseTree() {
        if (!$response = Framework::checkCache(self::CACHEKEY)) {
            $response = $this->getResponseObject(Sql::getClusterResults(), 'is_array');
            Framework::setCache(self::CACHEKEY, $response);
        }
        return $response;
    }

    /**
     * Returns the cluster ID of the best matching cluster tree item matching the search terms.
     *
     * @param string $searchQuery Search terms
     * @return int The best matching cluster ID or 0 if none.
     */
    public function getBestMatchClusterID($searchQuery) {
        $clusterID = 0;
        if (is_array(($clusterResults = $this->getSearchBrowseTree($searchQuery)->result))) {
            foreach ($clusterResults as $clusterNode) {
                if ($clusterNode['display'] === 'bestMatch' && $clusterNode['clusterID']) {
                    $clusterID = $clusterNode['clusterID'];
                    break;
                }
            }
        }
        return $this->getResponseObject($clusterID, 'is_int');
    }

    /**
     * Returns an array of all tree items and sub-items
     *
     * @param string $searchQuery Search query to be used for filling weights (optional)
     * @return array An array of browse tree items : (clusterID, weight, matchedLeaves, display)
     */
    public function getSearchBrowseTree($searchQuery){
        $cacheKey = self::CACHEKEY . "_{$searchQuery}";
        if(($response = Framework::checkCache($cacheKey)) === null) {
            $response = $this->getResponseObject(Sql::getSearchBrowseTreeResults($searchQuery), 'is_array');
            Framework::setCache($cacheKey, $response);
        }
        return $response;
    }
}