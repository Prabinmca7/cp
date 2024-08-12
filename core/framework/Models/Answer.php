<?php

namespace RightNow\Models;

use RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Connect\Knowledge\v1 as KnowledgeFoundation,
    RightNow\Api;

/**
 * Methods for handling the retrieval and manipulation of Knowledgebase answers. Allows for retrieval of answer objects
 * as well as getting related/previous answers.
 */
class Answer extends Base
{
    /**
     * Returns an Answer object from the database based on the given id.
     * @param int $answerID The ID for the answer
     * @return Connect\Answer|null The Answer object with the specified id or null if the answer could not be
     * found, is private, or is not enduser visible.
     */
    public function get($answerID){
        static $answerCache = array();
        if(!Framework::isValidID($answerID)){
            return $this->getResponseObject(null, null, "Invalid Answer ID: $answerID");
        }
        if(isset($answerCache[$answerID]) && $cachedAnswer = $answerCache[$answerID]){
            return $cachedAnswer;
        }

        //Set current page_set_id(KFAPI)
        $CI = get_instance();
        $pageSetID = $CI->getPageSetID() ?: null;
        if($pageSetID)
            Connect\CustomerPortal::setPageSetMap($pageSetID);

        try{
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
        catch(\RightNow\Connect\v1_2\ConnectAPIError $e){
            // For now handling v1.2 explicitly
            if(!Framework::isLoggedIn() && $this->isEndUserVisible($answerID)){
                return $this->getResponseObject(null, null, null, $e->getMessage());
            }
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        catch(Connect\ConnectAPIErrorBase $e){
            //Answer couldn't be retrieved, but that doesn't mean it doesn't exist. If the user isn't logged in, it might
            //be a priviledged answer and therefore we'll check to see if the answer ID exists in the DB. If so, we'll return
            //a warning instead of an error
            if(!Framework::isLoggedIn() && $this->isEndUserVisible($answerID)){
                return $this->getResponseObject(null, null, null, $e->getMessage());
            }
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        $answerCache[$answerID] = $this->getResponseObject($answer);
        return $answerCache[$answerID];
    }

    /**
     * Checks if the provided answer ID exists in the database. This method does not return
     * a ResponseObject
     *
     * @param int $answerID The ID of the answer to check
     * @return boolean True if the answer exists, false otherwise
     */
    public function exists($answerID){
        if(!Framework::isValidID($answerID)){
            return false;
        }
        return Connect\Answer::first("ID = $answerID") !== null;
    }

    /**
     * Checks if answer has any end-user visiblity. This function does not check to see if the answer is visible
     * to whoever is signed on, but only if it has any enduser visibility at all. This method does not return a ResponseObject.
     *
     * @param int $answerID The ID of the answer to check
     * @return boolean True if the answer has end-user visibility, false otherwise
     */
    public function isEndUserVisible($answerID){
        if(!Framework::isValidID($answerID)){
            return false;
        }
        return Connect\Answer::first("ID = $answerID AND AccessLevels > '0' AND StatusWithType.StatusType = " . STATUS_TYPE_PUBLIC) !== null;
    }

    /**
     * Checks if answer is private. This method does not return a ResponseObject.
     *
     * @param int $answerID The ID of the answer to check
     * @return boolean True if the answer is private, false otherwise
     */
    public function isPrivate($answerID){
        if(!Framework::isValidID($answerID)){
            return false;
        }
        return Connect\Answer::first("ID = $answerID AND StatusWithType.StatusType = " . STATUS_TYPE_PRIVATE) !== null;
    }

    /**
     * Returns a list of the most popular answers optionally filtered by the specified product or category ID
     * @param int $limit Number of results to return. Only a max of 10 results are supported.
     * @param int $productID ID of the product to filter results
     * @param int $categoryID ID of the category to filter results
     * @return mixed Array of KFAPI SummaryContent objects or null if an error occured
     */
    public function getPopular($limit = 10, $productID = null, $categoryID = null){
        if($productID !== null && !Framework::isValidID($productID)){
            return $this->getResponseObject(null, null, "Invalid Product ID: $productID");
        }
        if($categoryID !== null && !Framework::isValidID($categoryID)){
            return $this->getResponseObject(null, null, "Invalid Category ID: $categoryID");
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
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        if($topAnswers){
            return $this->getResponseObject($topAnswers);
        }
        return $this->getResponseObject(null, null, null, 'No results found.');
    }

    /**
     * Retrieves a set number of related answers associated to a specific answer ID. It will first
     * grab all manually related answers and then any learned link answers if size permits.
     *
     * @param int $answerID Answer ID from which to get related answers
     * @param int $limit Amount of related and learned link answers to retrieve
     * @return array Results from query
     */
    public function getRelatedAnswers($answerID, $limit)
    {
        if(!Framework::isValidID($answerID)){
            return $this->getResponseObject(null, null, "Answer ID provided was not numeric.");
        }
        try{
            $answerContent = $this->get($answerID)->result;
            if(!$answerContent){
                return $this->getResponseObject(null, null, "Answer was not valid.");
            }

            //A max limit of 10 is enforced in KF API. If limit passed as 0 or >10, set it to 10 i.e. KF_API_ROWS_MAX.
            $limit = ($limit <= 0 || $limit > 10) ? 10 : $limit;
            $relatedAnswers = KnowledgeFoundation\Knowledge::GetRecommendedContent($this->getKnowledgeApiSessionToken(), $answerContent, $limit);
            $relatedAnswers = $relatedAnswers->SummaryContents;
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject($relatedAnswers, 'is_object');
    }

    /**
     * Retrieves answer titles for answers the user has previously viewed during their session. If the user has cookies
     * disabled this method will not return any results.
     *
     * @param int $answerID The answer ID to ignore in the result list
     * @param int $limit The limit on the number of answers to return. If set to 0, all will be returned
     * @param int $truncateSize The number of characters to truncate answer text to
     * @return array List of answer titles the user has previously viewed
     */
    public function getPreviousAnswers($answerID, $limit, $truncateSize) {
        if(!Framework::isValidID($answerID)){
            return $this->getResponseObject(null, null, "Answer ID provided was not numeric.");
        }

        $response = $this->getResponseObject(null);

        if (!$this->CI->session->canSetSessionCookies()) {
            //If cookies are disabled, we don't have any information so bail out
            return $response;
        }

        if(!($sessionUrlParams = $this->CI->session->getSessionData('urlParameters'))) {
            return $response;
        }

        foreach($sessionUrlParams as $param) {
            if(is_array($param) && key($param) === 'a_id') {
                $answerIDs[] = $param['a_id'];
            }
        }

        if (!$answerIDs) {
            return $response;
        }
        else {
            // Either string (single value) or array (multiple values)
            $answerIDs = is_array($answerIDs)
                ? array_filter($answerIDs, 'is_numeric')
                : array($answerIDs);
        }
        //we want to display latest view first, and don't need duplicates
        $answerIDs = array_unique(array_reverse($answerIDs));
        //Filter out this answer id
        $answerIDs = array_diff($answerIDs, array($answerID));
        if(count($answerIDs) === 0){
            return $response;
        }

        //restrict to the first n values as specified by limit (also happens to reindex array by default)
        $answers = ($limit === 0) ? $answerIDs : array_slice($answerIDs, 0, $limit);

        // Create the AccessLevels query for ROQL to pull the correct answers based on the contacts answer access levels.
        $visQuery = Api::contact_answer_access();
        if($visQuery)
            $whereClause .= " AND A.AccessLevels.NamedIDList.ID IN ($visQuery)";

        try{
            $queryResult = Connect\ROQL::query('SELECT A.ID, A.Summary FROM Answer A WHERE A.ID IN (' . implode(',', $answers) . ') ' . $whereClause . ' AND A.StatusWithType.StatusType.ID = ' . STATUS_TYPE_PUBLIC)->next();
        }
        catch(Connect\ConnectAPIErrorBase $e){
            $response->error = $e->getMessage();
            return $response;
        }

        $previousAnswers = array();
        while($row = $queryResult->next()){
            if($truncateSize){
                $row['Summary'] = Text::truncateText($row['Summary'], $truncateSize);
            }
            $row = $this->expandAnswerFields($row);
            //We need to keep the answers in reverse order they were seen in (i.e. most recent first), so key them here and sort once we're done
            $previousAnswers[array_search($row['ID'], $answers)] = array($row['ID'], $row['Summary']);
        }

        if (count($previousAnswers) === 0) {
            return $response;
        }

        ksort($previousAnswers);

        return $this->getResponseObject(array_values($previousAnswers), 'is_array');
    }

    /**
     * Returns an array containing answers' answerID, summary, languageID, statusType indexed according to answer IDs; doesn't
     * return answers that do not conform to access level restrictions according to the current logged-in user.
     * @param int|array $answer A single int answer id or an array containing multiple answer IDs
     * @param bool $escapeHtml Whether the summary should have HTML tags escaped
     * @param bool $validateAccessLevel Whether to enable answer access level validation
     * @return array List containing answer's answerID, summary, languageID, statusType indexed according to answer IDs
     */
    public function getAnswerSummary($answer, $escapeHtml = true, $validateAccessLevel = true)
    {
        if(Framework::isValidID($answer)){
            $whereClause = "= $answer";
        }
        else if(is_array($answer) && count($answer)){
            $answerList = implode(', ', $answer);
            if(!preg_match('/\s*\d+(\s*,\s*\d+)*\s*/', $answerList))
                return null;
            $whereClause = "IN ($answerList)";
        }
        else{
            return $this->getResponseObject(null, null, 'Not a valid type for the $answer parameter.');
        }

        // Create the AccessLevels query for ROQL to pull the correct answers based on the contacts answer access levels.
        $visQuery = Api::contact_answer_access();
        if($visQuery && $validateAccessLevel)
            $whereClause .= " AND A.AccessLevels.NamedIDList.ID IN ($visQuery)";

        try{
            $queryResult = Connect\ROQL::query("SELECT A.ID, A.Summary, A.Language.ID as LanguageID, A.StatusWithType.StatusType.ID as StatusType FROM Answer A WHERE A.ID $whereClause")->next();
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        $results = array();
        while($row = $queryResult->next()){
            $row = $this->expandAnswerFields($row, $escapeHtml);
            $row['StatusType'] = intval($row['StatusType']);
            $row['LanguageID'] = intval($row['LanguageID']);
            $results[$row['ID']] = $row;
        }
        return $this->getResponseObject($results, 'is_array');
    }

    /**
     * Sends an email regarding a knowledgebase answer to the specified email.
     * @param string $sendTo Email address to send to
     * @param string $name Name of sender
     * @param string $from Email address of sender
     * @param int $answerID Answer ID
     * @return bool Whether the email was successfully sent
     */
    public function emailToFriend($sendTo, $name, $from, $answerID) {
        // Get subject and validate name
        $answerID = intval($answerID);
        if(($answer = $this->getAnswerSummary($answerID, true, false)->result)) {
            $subject = $answer[$answerID]['Summary'];
        }
        $name = trim($name);
        if(!$answer || $name === '') {
            return $this->getResponseObject(false, 'is_bool', \RightNow\Utils\Config::getMessage(THERE_WAS_ERROR_EMAIL_WAS_NOT_SENT_LBL));
        }
        $name = Text::escapeHtml($name);

        $emailAddressError = function($email) {
            return (!Text::isValidEmailAddress($email) || Text::stringContains($email, ';') || Text::stringContains($email, ',') || Text::stringContains($email, ' '));
        };
        $sendTo = trim($sendTo);
        if ($emailAddressError($sendTo)) {
            return $this->getResponseObject(false, 'is_bool', \RightNow\Utils\Config::getMessage(RECIPIENT_EMAIL_ADDRESS_INCORRECT_LBL));
        }
        if ($emailAddressError($from)) {
            return $this->getResponseObject(false, 'is_bool', \RightNow\Utils\Config::getMessage(SENDER_EMAIL_ADDRESS_INCORRECT_LBL));
        }

        if ($abuseMessage = $this->isAbuse()) {
            return $this->getResponseObject(false, 'is_bool', $abuseMessage);
        }

        $emailSent = Api::ans_eu_forward(array(
            'a_id'              => $answerID,
            'name'              => $name,
            'sendto'            => "\t\t\t\t$sendTo",
            'subject'           => $subject,
            'comment'           => null,
            'from_addr'         => self::buildFromHeader($name, $from),
            'replyto_addr'      => $from,
            'suppress_output'   => 1,
        ));
        \RightNow\ActionCapture::record('answer', 'email', intval($answerID));
        $this->CI->session->setSessionData(array('previouslySeenEmail' => $from));

        if ($emailSent !== 0) {
            return $this->getResponseObject(true, 'is_bool');
        }
        return $this->getResponseObject(false, 'is_bool', \RightNow\Utils\Config::getMessage(SORRY_WERENT_ABLE_SEND_EMAIL_PLS_MSG));
    }

    /**
     * Rate the provided answer ID a $rating/$scale score.
     * @param int $answerID The answer ID to rate
     * @param int $rating The rating given
     * @param int $scale The scale that is being used
     * @return boolean Whether or not the rating action was successful
     */
    public function rate($answerID, $rating, $scale){
        try{
            if(Framework::isValidID($answerID) && ($answerContent = KnowledgeFoundation\AnswerContent::fetch($answerID))){
                //Make sure both the rating and the scale are betwen 1 and 5
                $rating = min(max(intval($rating), 1), 5);
                $scale = min(max(intval($scale), 1), 5);
                $answerContent->RateContent($this->getKnowledgeApiSessionToken(), $rating, $scale);
                return true;
            }
        }
        catch(\Exception $e){
            //Nothing special to do here as the return false below will indicate that the rating failed
        }
        return false;
    }

    /**
     * Find the first, deepest product for an answer.
     * @param int $answerID The answer ID to find the bottom-most value for
     * @return array Single item array containing the ID of the bottom-most product
     */
    public function getFirstBottomMostProduct($answerID){
        return $this->getFirstBottomMostProdCat($answerID, HM_PRODUCTS);
    }

    /**
     * Find the first, deepest category for an answer.
     * @param int $answerID The answer ID to find the bottom-most value for
     * @return array Single item array containing the ID of the bottom-most category
     */
    public function getFirstBottomMostCategory($answerID){
        return $this->getFirstBottomMostProdCat($answerID, HM_CATEGORIES);
    }

    /**
     * Use ROQL to find the first, deepest product or category match.
     * Used in "guessing" the most specific product or category for things like
     * ProactiveChat
     * @param int $answerID The answer ID to find the bottom-most value for
     * @param string $type Should be either HM_PRODUCTS or HM_CATEGORIES
     * @return array Single item array containing the ID of the bottom-most product or category
     */
    private function getFirstBottomMostProdCat($answerID, $type){
        static $bottomProdCatCache = array();

        if($cachedValue = isset($bottomProdCatCache[$answerID . $type]) ? $bottomProdCatCache[$answerID . $type] : null)
            return $bottomProdCatCache[$answerID . $type];

        list($singularType, $pluralType) = $type === HM_PRODUCTS ? array('Product', 'Products') : array('Category', 'Categories');

        try{
            // Ensure that the item has a prod/cat (check level 0)
            $queryResult = Connect\ROQL::query(sprintf("SELECT Answer.{$pluralType}.ID FROM Answer WHERE ID = %d AND Answer.Parent{$singularType}.EndUserVisibleInterfaces.ID = curInterface() LIMIT 1", $answerID))->next();
            $topItem = $queryResult->next();

            // If nothing here, just return blank array.
            if(!$topItem)
                return $bottomProdCatCache[$answerID . $type] = $this->getResponseObject(array(), 'is_array');

            // Check levels 5 to 1
            for($level = 4; $level >= 0; $level--)
            {
                $queryResult = Connect\ROQL::query(sprintf("SELECT Answer.{$pluralType}.ID FROM Answer WHERE ID = %d AND Answer.Parent{$singularType}.Parent%s IS NOT NULL AND Answer.Parent{$singularType}.EndUserVisibleInterfaces.ID = curInterface() LIMIT 1", $answerID, ($level === 0 ? '' : '.Level' . $level)))->next();

                if($value = $queryResult->next())
                    return $bottomProdCatCache[$answerID . $type] = $this->getResponseObject($value, 'is_array');
            }

            // If we've made it here, there's no depth; only level 0 is defined. Return it.
            return $bottomProdCatCache[$answerID . $type] = $this->getResponseObject($topItem, 'is_array');
        }
        catch(Connect\ConnectAPIErrorBase $e){
            $response = $this->getResponseObject(null);
            $response->error = $e->getMessage();
            return $response;
        }

        // Failsafe. We shouldn't make it here, but if we somehow did, return blank array for "no value"
        return $bottomProdCatCache[$answerID . $type] = $this->getResponseObject(array(), 'is_array');
    }

    /**
     * Expands answer tags in the question, solution, and summary fields of an answer. Also optionally
     * escapes HTML in the answer summary. Takes either a Connect object or an array.
     * @param Connect\Answer|array $answer Connect answer object or array with appropriate field names
     * @param bool $escapeSummaryHtml Whether to escape HTML in answer summary.
     * @return Connect\Answer|array Modified answer object or array
     */
    protected function expandAnswerFields($answer, $escapeSummaryHtml = true){
        $expand = function(&$content){
            if($content)
                $content = Text::expandAnswerTags($content);
        };
        if(is_object($answer)){
            $expand($answer->Question);
            $expand($answer->Solution);
            $expand($answer->Summary);
            if($answer->Summary && $escapeSummaryHtml)
                $answer->Summary = $this->escapeSummary($answer->Summary);
        }
        else if(is_array($answer)){
            $expand($answer['Question']);
            $expand($answer['Solution']);
            $expand($answer['Summary']);
            if($answer['Summary'] && $escapeSummaryHtml){
                $answer['Summary'] = $this->escapeSummary($answer['Summary']);
            }
        }
        return $answer;
    }

    /**
     * Returns $summary with select special characters converted to HTML entities.
     *
     * @param string $summary An answer summary
     * @return string Escaped $summary
     */
    private function escapeSummary($summary) {
        return str_replace(array('<', '>'), array('&lt;', '&gt;'), $summary);
    }

    /**
     * Builds a string used for the from field in the forwarded answer email.
     * Assuming the email address has already been validated; the header is
     * assured to be no more than 80 chars long.
     * @param string $name  Sender's name
     * @param string $email Sender's email
     * @return string       Reply-to header
     */
    private static function buildFromHeader($name, $email) {
        $truncationLimit = 80;
        $format = '"%s" <%s>';
        $header = sprintf($format, $name, $email);

        if (Text::getMultibyteStringLength($header) > $truncationLimit) {
            $chars = Text::getMultibyteCharacters($name);
            $nameTruncationLength = $truncationLimit - Text::getMultibyteStringLength(sprintf($format, '', $email));

            if ($nameTruncationLength <= 0) return $email;

            $name = implode('', array_slice($chars, 0, $nameTruncationLength));
            $header = sprintf($format, $name, $email);
        }

        return $header;
    }

}
