<?php
namespace RightNow\Api\Models;

use RightNow\Connect\Knowledge\v1 as KnowledgeFoundation,
    RightNow\Internal\Api\Response,
    RightNow\Internal\Utils\Version as Version,
    RightNow\Utils\Framework;

require_once CORE_FILES . 'compatibility/Internal/Api/Models/Base.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Response.php';

class Answer extends Base {

    /**
     * Checks if answer has end-user visiblity.
     * @param int $answerID The ID of the answer to check
     * @return boolean True if the answer has end-user visibility, false otherwise
     */
    private function isEndUserVisible($answerID){
        if(!Framework::isValidID($answerID)){
            return false;
        }
        try{
            if($this->connectVersion == 1.4)
                return \RightNow\Connect\v1_4\Answer::first("ID = $answerID AND AccessLevels > '0' AND StatusWithType.StatusType = " . STATUS_TYPE_PUBLIC) !== null;
            else
                return \RightNow\Connect\v1_3\Answer::first("ID = $answerID AND AccessLevels > '0' AND StatusWithType.StatusType = " . STATUS_TYPE_PUBLIC) !== null;
        }
        catch(Exception $err) {
            return Response::generateResponseObject(null, null, $err->getMessage());
        }
    }

    /**
     * Returns answer for the given id.
     * @param int $answerID The ID for the answer
     * @return Connect\Answer|null The Answer object with the specified id or null if the answer could not be
     * found, is private, or is not enduser visible.
     */
    public function getById($answerID){
        static $answerCache = array();
        if(!Framework::isValidID($answerID)){
            return Response::getErrorResponseObject("Invalid Answer ID: $answerID", Response::HTTP_NOT_FOUND_STATUS_CODE);
        }
        if($cachedAnswer = $answerCache[$answerID]){
            return $cachedAnswer;
        }
        //Set current page_set_id(KFAPI)
        $CI = get_instance();
        $pageSetID = $CI->getPageSetID() ?: null;

        try{
            if($this->connectVersion == 1.4)
                \RightNow\Connect\v1_4\CustomerPortal::setPageSetMap($pageSetID);
            else
                \RightNow\Connect\v1_3\CustomerPortal::setPageSetMap($pageSetID);

            $answerContent = KnowledgeFoundation\AnswerSummaryContent::fetch($answerID);
            $this->addKnowledgeApiSecurityFilter($answerContent);
            $viewOrigin = null;
            //If there is a 'related' URL parameter, tell the KFAPI so they record this correctly
            if(\RightNow\Utils\Url::getParameter('related') === '1'){
                $viewOrigin = new KnowledgeFoundation\ContentViewOrigin();
                $viewOrigin->ID = 2; //Not exposed to PHP - KF_API_VIEW_SOURCE_RELATED
            }
            $answer = $answerContent->GetContent($this->getKnowledgeApiSessionToken(), $viewOrigin);
        }
        catch(\RightNow\Connect\v1_3\ConnectAPIError $e){
            // For now handling v1.3 explicitly
            if(!Framework::isLoggedIn() && $this->isEndUserVisible($answerID)){
                return Response::getErrorResponseObject($e->getMessage(), Response::HTTP_FORBIDDEN_STATUS_CODE);
            }
            return Response::getErrorResponseObject($e->getMessage(), Response::HTTP_NOT_FOUND_STATUS_CODE);
        }
        catch(\RightNow\Connect\v1_2\ConnectAPIError $e){
            // For now handling v1.2 explicitly
            if(!Framework::isLoggedIn() && $this->isEndUserVisible($answerID)){
                return Response::getErrorResponseObject($e->getMessage(), Response::HTTP_FORBIDDEN_STATUS_CODE);
            }
            return Response::getErrorResponseObject($e->getMessage(), Response::HTTP_NOT_FOUND_STATUS_CODE);
        }
        catch(\RightNow\Connect\v1_4\ConnectAPIErrorBase $e){
            //Answer couldn't be retrieved, but that doesn't mean it doesn't exist. If the user isn't logged in, it might
            //be a priviledged answer and therefore we'll check to see if the answer ID exists in the DB. If so, we'll return
            //a warning instead of an error
            if(!Framework::isLoggedIn() && $this->isEndUserVisible($answerID)){
                return Response::getErrorResponseObject($e->getMessage(), Response::HTTP_FORBIDDEN_STATUS_CODE);
            }
            return Response::getErrorResponseObject($e->getMessage(), Response::HTTP_NOT_FOUND_STATUS_CODE);
        }
        $answerCache[$answerID] = Response::getResponseObject($answer);
        return $answerCache[$answerID];
    }

    /**
     * Returns a list of the most popular answers optionally filtered by the specified product or category ID
     * @param int $limit Number of results to return. Only a max of 10 results are supported.
     * @param int $productID ID of the product to filter results
     * @param int $categoryID ID of the category to filter results
     * @return mixed Array of KFAPI SummaryContent objects or error response object
     */
    public function getPopular($limit = 10, $productID = null, $categoryID = null){
        if($productID !== null && !Framework::isValidID($productID)){
            return Response::getErrorResponseObject("Invalid Product ID: $productID", Response::HTTP_BAD_REQUEST);
        }
        if($categoryID !== null && !Framework::isValidID($categoryID)){
            return Response::getErrorResponseObject("Invalid Category ID: $categoryID", Response::HTTP_BAD_REQUEST);
        }
        $contentSearch = null;
        $productID = intval($productID);
        $categoryID = intval($categoryID);
        $limit = max(min($limit, 10), 1);
        try{
            $contentSearch = new KnowledgeFoundation\ContentSearch();
            if($productID || $categoryID){
                $contentSearch->Filters = new KnowledgeFoundation\ContentFilterArray();
                if($productID){
                    $productFilter = new KnowledgeFoundation\ServiceProductContentFilter();
                    $productFilter->ServiceProduct = $productID;
                    $contentSearch->Filters[] = $productFilter;
                }
                if($categoryID){
                    $categoryFilter = new KnowledgeFoundation\ServiceCategoryContentFilter();
                    $categoryFilter->ServiceCategory = $categoryID;
                    $contentSearch->Filters[] = $categoryFilter;
                }
            }
            $this->addKnowledgeApiSecurityFilter($contentSearch);
            $topAnswers = KnowledgeFoundation\Knowledge::GetPopularContent($this->getKnowledgeApiSessionToken(), $contentSearch, null, $limit);
            $topAnswers = $topAnswers->SummaryContents;
        }
        catch(\Exception $e){
            return Response::getErrorResponseObject($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        if($topAnswers){
            return Response::getResponseObject($topAnswers);
        }
        return Response::getErrorResponseObject('No results found.', Response::HTTP_NOT_FOUND_STATUS_CODE);
    }
}
