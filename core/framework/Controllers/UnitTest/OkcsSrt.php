<?php

namespace RightNow\Controllers\UnitTest;

use RightNow\Utils;

class OkcsSrt extends \RightNow\Controllers\Base {
    private $cacheData;
    private $cache;
    private $apiVersion;
    private $helper;

    function __construct(){
        parent::__construct(true, '_phonyLogin');
        umask(0);
        require_once CPCORE . 'Controllers/UnitTest/okcs/mockservice/OkcsTestHelper.php';
        $this->helper = new \RightNow\Controllers\UnitTest\okcs\mockservice\OkcsTestHelper();
    }

    public function index(){
        echo "Mock Api Url to help generate static response. Lets add Documentation later";
    }

    public function api(){
        $queryUrl = $this->helper->getParameterString();
        $queryParameters = explode("/", $queryUrl);
        if ($queryParameters[2] === 'search' || $queryParameters[2] === 'keyValueCache' || $queryParameters[2] === 'contactDeflection'){
            $this->apiVersion = $queryParameters[1];
            $methodCall = $this->getMethod($queryParameters[2]);
        }
        else {
            $methodCall = $this->getMethod($queryParameters[1]);
        }
        if(!file_exists(CPCORE . 'Controllers/UnitTest/okcs/mockservice/srt/responses/' . $this->apiVersion . '/')){
            $this->helper->html_response_code(500);
        }
        else{
            self::$methodCall();
        }
    }
    
    public function keyValueCache() {
        $action = $_SERVER['REQUEST_METHOD'];
        $cacheData = array();
        $kmAuthToken = $_SERVER['HTTP_KMAUTHTOKEN'];
        if (!$this->validateToken($kmAuthToken)){
            $this->helper->html_response_code(400);
        }
        if ($action === 'POST') {
            $queryUrl = explode('/', $this->helper->getParameterString());
            $cacheKey = strtoupper(uniqid(rand(), false));
            $postData = json_decode(file_get_contents("php://input"));
            foreach ($postData as $key => $value){
                $cacheData[$key] = $value;
            }
            $this->getMemCache()->set($cacheKey, $cacheData);
            header(' ', true, 201);
            echo $cacheKey;
        }
        else if ($action === 'PUT') {
            $queryUrl = explode('/', $this->helper->getParameterString());
            $cacheKey = $queryUrl[1];
            if ($cacheKey === null || $cacheKey === ''){
                $this->helper->html_response_code(405);
            }
            else{
                $cacheData = $this->getMemCache()->get($cacheKey);
                if ($cacheData === false){
                    $this->helper->html_response_code(404); 
                }
                else {
                    $cacheData = json_decode(file_get_contents("php://input"), true);
                    $this->getMemCache()->set($cacheKey, $cacheData);
                    header(' ', true, 204);
                }
            }
        }
        else if ($action === 'DELETE'){
            $queryUrl = explode('/', $this->helper->getParameterString());
            $cacheKey = $queryUrl[1];
            if ($cacheKey === null || $cacheKey === ''){
                $this->helper->html_response_code(405);
            }
            else {
                $cacheData = $this->getMemCache()->get($cacheKey);
                if ($cacheData === false){
                    $this->helper->html_response_code(404); 
                }
                else {
                    $this->getMemCache()->expire($cacheKey);
                    header(' ', true, 204);
                }
            }
        }
        else if ($action === 'GET') {
            $queryUrl = explode('/', $this->helper->getParameterString());
            $cacheKey = $queryUrl[3];
            if ($cacheKey === null || $cacheKey === ''){
                $this->helper->html_response_code(405);
            }
            else {
                $cacheData = $this->getMemCache()->get($cacheKey);
                if ($cacheData === false){
                    $this->helper->html_response_code(404);
                }
                else {
                    header(' ', true, 200);
                    header("Content-Type:application/json");
                    echo json_encode($cacheData);
                }
            }
        }
    }
    
    public function search(){
        $kmAuthToken = $_SERVER['HTTP_KMAUTHTOKEN'];
        if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
            $this->helper->html_response_code(405);
        }
        if (!$this->validateToken($kmAuthToken, 'search')){
            $this->helper->html_response_code(400);
        }
        $queryUrl = $this->helper->getParameterString();
        $queryParameters = explode("/", $queryUrl);
        if ($this->apiVersion === null){
            $methodCall = $this->getMethod($queryParameters[2]);
            $this->apiVersion = 'latest';
        }
        else{
            $methodCall = $this->getMethod($queryParameters[3]);
        }
        self::$methodCall();
    }

    public function contactDeflection(){
        $kmAuthToken = $_SERVER['HTTP_KMAUTHTOKEN'];
        if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
            $this->helper->html_response_code(405);
        }
        if (!$this->validateToken($kmAuthToken, 'search')){
            $this->helper->html_response_code(400);
        }
        $queryUrl = $this->helper->getParameterString();
        $queryParameters = explode("/", $queryUrl);
        if ($this->apiVersion === null){
            $methodCall = $this->getMethod($queryParameters[2] . 'Contact');
            $this->apiVersion = 'latest';
        }
        else{
            $methodCall = $this->getMethod($queryParameters[3] . 'Contact');
        }
        self::$methodCall();
    }
    
    public function question(){
        $question = urlencode(html_entity_decode($_GET['question']));
        $locale = $_GET['requestLocale'];
        $postData = json_decode(file_get_contents("php://input"), true);
        $requestedLocales = $postData['resultLocales'];
        if (preg_match('/^[\d]{3}$/', $question)){
            $this->helper->html_response_code(intval($question));
        }
        else{
            $resultData = $this->getFile(array('apiVersion' => $this->apiVersion, 'action' => 'Search', 'question' => $question, 'locale' => $requestedLocales, 'page' => 'P1'));
            header("Content-Type:application/json");
            $searchResult = json_decode(fread($resultData['content'], filesize($resultData['path'])), true);
            $searchResult['session'] = $this->getSession();
            $cacheData = array('keyword' => $question, 'locale' => explode(',', $requestedLocales), 'pageNumber' => 1, 'facet' => array());
            $this->getMemCache()->set($searchResult['session'], $cacheData);
            echo json_encode($searchResult);
        }
    }
    
    public function pagination(){
        $direction = $_GET['pageDirection'];
        $currentPage = intval($_GET['pageNumber']);
        $priorTransactionId = $_GET['priorTransactionId'];
        $postData = json_decode(file_get_contents("php://input"), true);
        $session = $postData['session'];
        $encryptedSession = $this->helper->decodeAndDecryptData($postData['session']);
        $cacheData = $this->getMemCache()->get($session);
        if($cacheData === false){
            $cacheData = $this->getMemCache()->get($encryptedSession);
            $session = $encryptedSession;
        }
        if ($direction === 'next'){
            $pageNumber = $cacheData['pageNumber'] + 1;
            $resultData = $this->getFile(array('apiVersion' => $this->apiVersion, 'action' => 'Page', 'question' => $cacheData['keyword'], 'locale' => implode('-', $cacheData['locale']), 'page' => 'P' . $pageNumber));
            header("Content-Type:application/json");
            $searchResult = json_decode(fread($resultData['content'], filesize($resultData['path'])), true);
            $searchResult['session'] = $session;
            $cacheData['pageNumber'] += 1;
            $this->getMemCache()->set($session, $cacheData);
            echo json_encode($searchResult);
        }
        else if ($direction === 'previous'){
            $pageNumber = $cacheData['pageNumber'] - 1;
            $resultData = $this->getFile(array('apiVersion' => $this->apiVersion, 'action' => 'Page', 'question' => $cacheData['keyword'], 'locale' => implode('-', $cacheData['locale']), 'page' => 'P' . $pageNumber));
            header("Content-Type:application/json");
            $searchResult = json_decode(fread($resultData['content'], filesize($resultData['path'])), true);
            $searchResult['session'] = $session;
            $cacheData['pageNumber'] = strval(pageNumber);
            $this->getMemCache()->set($session, $cacheData);
            echo json_encode($searchResult);
        }
        else {
            $this->helper->html_response_code(404);
        }
    }
    
    private function navigation(){
        $priorTransactionId = $_GET['priorTransactionid'];
        $facet = $_GET['facet'];
        $facetShowAll = $_GET['facetShowAll'];
        $postData = json_decode(file_get_contents("php://input"), true);
        $locale = explode(',', $postData['requestLocale']);
        $session = $postData['session'];
        $encryptedSession = $this->helper->decodeAndDecryptData($postData['session']);
        $cacheData = $this->getMemCache()->get($session);
        if($cacheData === false){
            $cacheData = $this->getMemCache()->get($encryptedSession);
            $session = $encryptedSession;
        }
        if (empty($cacheData['facet'])){
            $resultData = $this->getFile(array('apiVersion' => $this->apiVersion, 'action' => 'Facet', 'question' => $cacheData['keyword'], 'locale' => implode('-', $cacheData['locale']), 'facet' => $facet, 'page' => 'P1'));
            $this->getMemCache()->set($session, $cacheData);
            header("Content-Type:application/json");
            $searchResult = json_decode(fread($resultData['content'], filesize($resultData['path'])), true);
            $searchResult['session'] = $session;
            echo json_encode($searchResult);
        }
        //If a facet is preselected
        else {
            $isDeselected = false;
            $selectedFacets = array();
            foreach($cacheData['facet'] as $activeFacet){
                if ($activeFacet !== $facet){
                    array_push($selectedFacets, $activeFacet);
                }
                else if ($activeFacet === $facet){
                    $isDeselected = true;
                }
            }
            if ($isDeselected === true){
                $cacheData['facet'] = $selectedFacets;
            }
            else{
                array_push($cacheData['facet'], $facet);
            }
            sort($cacheData['facet']);
            $resultData = $this->getFile(array('apiVersion' => $this->apiVersion, 'action' => 'Facet', 'question' => $cacheData['keyword'], 'locale' => implode('-', $cacheData['locale']), 'facet' => implode('-', $cacheData['facet']), 'page' => 'P1'));
            $this->getMemCache()->set($session, $cacheData);
            header("Content-Type:application/json");
            $searchResult = json_decode(fread($resultData['content'], filesize($resultData['path'])), true);
            $searchResult['session'] = $session;
            echo json_encode($searchResult);
        }
    }
    
    public function questionContact(){
        $question = $_GET['question'];
        $locale = str_replace('_', '-', $_GET['requestLocale']);
        $postData = json_decode(file_get_contents("php://input"), true);
        $requestedLocales = $postData['resultLocales'];
        if (preg_match('/^[\d]{3}$/', $question)){
            $this->helper->html_response_code(intval($question));
        }
        else{
            if ($question) {
                $resultData = $this->getFile(array('apiVersion' => $this->apiVersion, 'action' => 'Deflection', 'question' => $question, 'locale' => $locale));
                header("Content-Type:application/json");
                echo fread($resultData['content'], filesize($resultData['path']));
            }
            else {
                $this->helper->html_response_code(404);
            }
        }
    }
    
    public function responseContact(){
        $priorTransactionId = $_GET['priorTransactionId'];
        $deflected = $_GET['deflectedFlag'];
        $postData = json_decode(file_get_contents("php://input"), true);
        if (isset($priorTransactionId) && isset($deflected)){
            header(' ', true, 204);
            $this->helper->html_response_code(200);
        }
        else {
            header(' ', true, 400);
            $this->helper->html_response_code(400);
        }
    }
    
    public function answer(){
        $keys = array_keys($_GET);
        $priorTransactionId = $_GET['priorTransactionId'];
        $answerID = $_GET['answerId'];
        $highlightInfo = $_GET['highlightInfo'];
        $trackedUrl = $_GET['trackedURL'];
        $isPDF = $_GET['isPDF'];
        $requestLocale = $_GET['requestLocale'];
        $postData = json_decode(file_get_contents("php://input"), true);
        if ($answerID != null){
            $resultData = $this->getFile(array('apiVersion' => $this->apiVersion, 'action' => 'Highlight', 'question' => "Windows", 'locale' => "en-US", 'docId' => $answerID));
            header("Content-Type:application/json");
            echo fread($resultData['content'], filesize($resultData['path']));
        }
        else {
            echo null;
        }
    }
    
    public function feedback(){
        $priorTransactionId = $_GET['priorTransactionId'];
        $userRating = $_GET['userRating'];
        $userFeedback = $_GET['userFeedback'];
        $postData = json_decode(file_get_contents("php://input"), true);
        if (array_key_exists('session', $postData)){
            if (($priorTransactionId !== null && $priorTransactionId !== '') && ($userRating !== null && $userRating !== '')){
                return;
            }
        }
        $this->helper->html_response_code(400);
    }
    
    private function validateToken($kmAuthToken, $request = null){
        if ($kmAuthToken === null || $kmAuthToken === ""){
            return false;
        }
        else {
            if (strpos($kmAuthToken, 'siteName') && (strpos($kmAuthToken, 'integrationUserToken'))){
                if ($request === null){
                    return true;
                }
                else if ($request === 'search'){
                    if (strpos($kmAuthToken, 'interfaceId')){
                        return true;
                    }
                    else {
                        header(' ', true, 500);
                        echo "Internal Server Error";
                    }
                }
            }
            return true;
        }
    }
    
    private function getFile($fileParams){
        if ($fileParams['action'] === 'Search'){
            $resultPath = CPCORE.'Controllers/UnitTest/okcs/mockservice/srt/responses/'.$fileParams['apiVersion'].'/'.$fileParams['action'].'-'.$fileParams['question'].'-'.$fileParams['locale'].'-P1.json';
            $fileContents = fopen($resultPath, "r");
            $resultData = array('content' => $fileContents, 'path' => $resultPath);
            return $resultData;
        }
        else if ($fileParams['action'] === 'Page'){
            $resultPath = CPCORE.'Controllers/UnitTest/okcs/mockservice/srt/responses/'.$fileParams['apiVersion'].'/'.'Search-'.$fileParams['question'].'-'.$fileParams['locale'].'-'.$fileParams['page'].'.json';
            $fileContents = fopen($resultPath, "r");
            $resultData = array('content' => $fileContents, 'path' => $resultPath);
            return $resultData;
        }
        else if ($fileParams['action'] === 'Facet'){
            if ($fileParams['facet'] === ''){
                $resultPath = CPCORE.'Controllers/UnitTest/okcs/mockservice/srt/responses/'.$fileParams['apiVersion'].'/'.'Facet-'.$fileParams['question'].'-'.$fileParams['locale'].'-P1.json';
            }
            else {
                $resultPath = CPCORE.'Controllers/UnitTest/okcs/mockservice/srt/responses/'.$fileParams['apiVersion'].'/'.'Facet-'.$fileParams['question'].'-'.$fileParams['locale'].'-'.$fileParams['facet'].'-P1.json';
            }
            $fileContents = fopen($resultPath, "r");
            $resultData = array('content' => $fileContents, 'path' => $resultPath);
            return $resultData;
        }
        else if ($fileParams['action'] === 'Deflection'){
            $resultPath = CPCORE.'Controllers/UnitTest/okcs/mockservice/srt/responses/'.$fileParams['apiVersion'].'/'.$fileParams['action'].'-'.$fileParams['question'].'-'.$fileParams['locale'].'.json';
            $fileContents = fopen($resultPath, "r");
            $resultData = array('content' => $fileContents, 'path' => $resultPath);
            return $resultData;
        }
        else if ($fileParams['action'] === 'Highlight'){
            $resultPath = CPCORE.'Controllers/UnitTest/okcs/mockservice/srt/responses/'.$fileParams['apiVersion'].'/'.$fileParams['action'].'-'.$fileParams['question'].'-'.$fileParams['locale'].'-'.$fileParams['docId'].'.json';
            $fileContents = fopen($resultPath, "r");
            $resultData = array('content' => $fileContents, 'path' => $resultPath);
            return $resultData;
        }
    }
    
    private function getSession(){
        return strtoupper(uniqid(rand(), false));
    }
    
    private function getDocID($trackedURL){
        $validDocIDs = array('1000001','1000004','1000007','1000021','1000036');
        foreach($validDocIDs as $docID){
            if (strpos($trackedURL, $docID))
            {
                if (!strpos($trackedURL, 'NODES') && !strpos($trackedURL, 'ATTACHMENT'))
                    return $docID;
            }
        }
    }
    
    private function getMethod($url){
        $url = str_replace('-', ' ', $url);
        $url = explode(' ', $url);
        for($i = 1; $i < count($url); $i++){
            $url[$i] = ucwords($url[$i]);
            $url[0] .= $url[$i];
        }
        return $url[0];
    }
    
    private function getMemCache() {
        return ($this->cache === null) ? new \RightNow\Libraries\Cache\Memcache(1000) : $this->cache;
    }
}
