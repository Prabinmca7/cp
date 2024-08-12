<?php

namespace RightNow\Helpers;

use RightNow\Utils\Framework;

/**
 * Commons functions for calling Okcs Rest API's
 */
class OkcsWhiteListHelper extends \RightNow\Models\Base
{
    private $apiUrlMappings = array();
    
    /**
     * Method to load end point urls.
     */
    public function loadEndPointUrl()
    {
        $this->apiUrlMappings["content"] = array('url' => '/content', 'methodType' => 'GET', 'apiType' => 'IM_API_URL');
        $this->apiUrlMappings["contentForDocumentId"] = array('url' => '/content/docId/{docId}', 'methodType' => 'GET', 'apiType' => 'IM_API_URL');
        $this->apiUrlMappings["contentForAnswerId"] = array('url' => '/content/answers/{answerId}', 'methodType' => 'GET', 'apiType' => 'IM_API_URL');
        $this->apiUrlMappings["contentDocumentLinksForAnswerId"] = array('url' => '/content/answers/{answerId}/documentLinks', 'methodType' => 'GET', 'apiType' => 'IM_API_URL');
        $this->apiUrlMappings["contentLearnedLinksForAnswerId"] = array('url' => '/content/answers/{answerId}/documentLinks/learned', 'methodType' => 'GET', 'apiType' => 'IM_API_URL');
        $this->apiUrlMappings["contentManualLinksForAnswerId"] = array('url' => '/content/answers/{answerId}/documentLinks/manual', 'methodType' => 'GET', 'apiType' => 'IM_API_URL');
        $this->apiUrlMappings["contentTypes"] = array('url' => '/contentTypes', 'methodType' => 'GET', 'apiType' => 'IM_API_URL');
        $this->apiUrlMappings["contentTypesBasedOnContentRecordId"] = array('url' => '/contentTypes/{recordId}', 'methodType' => 'GET', 'apiType' => 'IM_API_URL');
        $this->apiUrlMappings["categories"] = array('url' => '/categories', 'methodType' => 'GET', 'apiType' => 'IM_API_URL');
        $this->apiUrlMappings["channelCategories"] = array('url' => '/contentTypes/{channelReferenceKey}/categories', 'methodType' => 'GET', 'apiType' => 'IM_API_URL');
        $this->apiUrlMappings["subscriptionsBasedOnUserRecordId"] = array('url' => '/users/{userRecordId}/subscriptions', 'methodType' => 'GET', 'apiType' => 'IM_API_URL');
        $this->apiUrlMappings["subscriptionsBsdOnUsrRecordIdAndSubscriptionId"] = array('url' => '/users/{userRecordId}/subscriptions/{subscriptionId}', 'methodType' => 'GET', 'apiType' => 'IM_API_URL');
        $this->apiUrlMappings["rateContentBasedOnContentRecordId"] = array('url' => '/content/{recordId}/rate', 'methodType' => 'POST', 'apiType' => 'IM_API_URL');
        $this->apiUrlMappings["searchResult"] = array('url' => '/search/question', 'methodType' => 'POST', 'apiType' => 'SRCH_API_URL');
        $this->apiUrlMappings["highlightContent"] = array('url' => '/search/answer', 'methodType' => 'POST', 'apiType' => 'SRCH_API_URL');
        $this->apiUrlMappings["clickThroughAnswer"] = array('url' => '/search/click-thru', 'methodType' => 'POST', 'apiType' => 'SRCH_API_URL');
        $this->apiUrlMappings["facetNavigation"] = array('url' => '/search/navigation', 'methodType' => 'POST', 'apiType' => 'SRCH_API_URL');
        $this->apiUrlMappings["searchResultPagination"] = array('url' => '/search/pagination', 'methodType' => 'POST', 'apiType' => 'SRCH_API_URL');
    }
    
    /**
    * Gets a list of properties based on the method name passed.
    * @param string $methodName Name of method
    * @return array an array that contains mapping properties related to that method name.
    */
    public function getApiUrlData($methodName)
    {
        return $this->apiUrlMappings[$methodName];
    }
    
    /**
    * Method to get end point url after replacing with parameters passed
    * @param string $methodName Name of method to retrieve the end point url
    * @param array $dataArray Data array
    * @return array API endpoint data.
    */
    public function getUpdatedEndPointUrlData($methodName, $dataArray = array()) {
        $results = array();
        $apiArray = $this->apiUrlMappings[$methodName];
        $apiUrl = $apiArray['url'];
        if($dataArray) {
            foreach ($dataArray as $key => $value) {
                $pos = strpos($apiUrl, "{".$key."}");
                if ($pos !== false) {
                    $updatedApiUrl = str_replace("{".$key."}", $value, $apiUrl);
                    $apiUrl = $updatedApiUrl;
                }
            }
        }
        $updatedApiUrl = $updatedApiUrl ? $updatedApiUrl : $apiUrl;
        if (preg_match('/{(.*?)}/', $updatedApiUrl, $match)) {
            $results['pathParameter'] = $match[1];
        }
        $results['apiEndPointUrl'] = $updatedApiUrl;
        return $results;
    }
}