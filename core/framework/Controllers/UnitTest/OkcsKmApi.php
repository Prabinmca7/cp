<?php

namespace RightNow\Controllers\UnitTest;

use \RightNow\Utils;
use \RightNow\Utils\Text;
use \RightNow\Controllers\UnitTest\okcs\mockservice\OkcsTestHelper;
use \RightNow\Controllers\UnitTest\okcs\mockservice;
if (IS_HOSTED) {
    exit("Did we ship the unit tests?  That would be sub-optimal.");
}

class OkcsKmApi extends \RightNow\Controllers\Base {
    const OKCS_MAX_HTTP_STATUS_CODE = 1000;

    function __construct(){
        parent::__construct(true, '_phonyLogin');
        umask(0);
        require_once CPCORE . 'Controllers/UnitTest/okcs/mockservice/OkcsTestHelper.php';
    }

    /**
     * Default function when one is not specified. Generates help text for documentation unit tests
     */
    public function index(){
        echo "Mock Api Url to help generate static response. Lets add Documentation later";
        
    }
    
    public function content(){
        $queryParameterArray = explode('/', OkcsTestHelper::getParameterString());
        $queryParameters = OkcsTestHelper::getQueryParameters();
        if($queryParameterArray[2] === 'content' && $queryParameterArray[4] === 'ratingsAggregate' && $queryParameterArray[3] === '04003706c11401e015ab1a43806007f9a'){
            $path = '/content/1000042.txt';
            echo $this->changeResourcePath(OkcsTestHelper::getFileContents($path));
            return;
        }
        if($queryParameterArray[2] === 'content' && $queryParameterArray[3] === 'versions' && $queryParameterArray[5] === 'previewThisVersion'){
            $path = '/content/answerPreview.txt';
            echo $this->changeResourcePath(OkcsTestHelper::getFileContents($path));
            return;
        }
        if($queryParameterArray[2] === 'content' && strpos($queryParameters['q'], 'nswerId IN')) {//giving answerId returns int(0) && of which returns bool(false)
            $path = '/content/favoritesList.txt';
            echo (OkcsTestHelper::getFileContents($path));
            return;
        }
        if($queryParameterArray[2] === 'content' && strpos($queryParameters['q'], 'documentId in')) {
            $path = '/content/twoDocIds.txt';
            echo (OkcsTestHelper::getFileContents($path));
            return;
        }
        if($queryParameterArray[2] === 'content' && $queryParameters['limit'] === '2'){echo('FOUR');
            echo OkcsTestHelper::getFileContents('/SiteMap.txt');
            return;
        }
        if(($queryParameterArray[2] === 'content') && ($queryParameterArray[3] !== 'answers') && (array_key_exists('q', $queryParameters))){
            if(($queryParameters['orderBy'] === 'mostRecent') && strpos($queryParameters['q'], 'PUBLISHED'))
            {
                echo OkcsTestHelper::getFileContents('/mostRecent.txt');
                return;
            }
            elseif(($queryParameters['orderBy'] === 'mostRecent') && strpos($queryParameters['q'], 'LATESTVALID'))
            {
                echo OkcsTestHelper::getFileContents('/mostRecentDraft.txt');
                return;
            }
            elseif(($queryParameters['orderBy'] === 'mostPopular') && strpos($queryParameters['q'], 'PUBLISHED'))
            {
                echo OkcsTestHelper::getFileContents('/mostPopular.txt');
                return;
            }
            elseif(($queryParameters['orderBy'] === 'mostPopular') && strpos($queryParameters['q'], 'LATESTVALID'))
            {
                echo OkcsTestHelper::getFileContents('/mostPopularDraft.txt');
                return;
            }
            elseif(($queryParameters['orderBy'] === 'publishDate:DESC') && strpos($queryParameters['q'], 'PUBLISHED')){
                if(strpos($queryParameters['q'], 'referenceKey')){
                    $query = explode(' ', $queryParameters['q']);
                    $contentType = str_replace('&quot;', '', $query[2]);
                    if (intval($contentType) < 600 && intval($contentType) !== 0){
                        OkcsTestHelper::html_response_code($contentType);
                        return;
                    }
                    else{
                        $path = '/content/filter/' . $contentType . '.txt';
                    }
                }
                else{
                    $path = '/content/filter/No_content_type.txt';
                }
                echo OkcsTestHelper::getFileContents($path);
                return;
            }
            elseif(($queryParameters['orderBy'] === 'publishDate:DESC') && strpos($queryParameters['q'], 'LATESTVALID')){
                if(strpos($queryParameters['q'], 'referenceKey')){
                    $query = explode(' ', $queryParameters['q']);
                    $contentType = str_replace('&quot;', '', $query[2]);
                    $path = '/content/filter/' . $contentType . '_draft.txt';
                }
                echo OkcsTestHelper::getFileContents($path);
                return;
            }
            OkcsTestHelper::html_response_code(404);
        }
        else if($queryParameterArray[2] === 'content' && $queryParameterArray[5] === 'allTranslations'){
            $path = '/content/allTranslations.txt';
            echo $this->changeResourcePath(OkcsTestHelper::getFileContents($path));
            return;
        }
        elseif($queryParameterArray[2] === 'content' && $queryParameterArray[3] === 'answers'){
            if(array_key_exists(4, $queryParameterArray) && ($queryParameterArray[4] > 1000)){
                if($queryParameterArray[4] === '1234'){
                    OkcsTestHelper::html_response_code(404);
                }
                $path = '/content/' . $queryParameterArray[4] . '.txt';
                echo $this->changeResourcePath(OkcsTestHelper::getFileContents($path));
                return;
            }
            else{
                OkcsTestHelper::html_response_code($queryParameterArray[4]);
                return;
            }
        }
        elseif($queryParameterArray[2] === 'content' && $queryParameterArray[4] === 'rate'){
            OkcsTestHelper::html_response_code(204);
        }
        else{
            echo OkcsTestHelper::getFileContents('/SiteMap.txt');
        }
    }

    public function contentRecommendations(){
        $queryParameters = OkcsTestHelper::getQueryParameters();
        $queryParameterArray = explode('/', OkcsTestHelper::getParameterString());
        $postData = json_decode(file_get_contents("php://input"), true);
        if(!empty($queryParameters['q']) && Text::stringContains($queryParameters['limit'], '10')){
            echo OkcsTestHelper::getFileContents('/contentRecommendations.txt');
        }
        else if(!empty($queryParameters['q']) && Text::stringContains($queryParameters['limit'], '5')){
            echo OkcsTestHelper::getFileContents('/contentRecommendationsLimit.txt');
        }
        else if($queryParameterArray[3] !== null){
            echo OkcsTestHelper::getFileContents('/recommendations/' . $queryParameterArray[3] . '.txt');
        }
        else{
            if(!in_array('priority', $postData) || in_array($postData['priority'], array('LOW', 'MEDIUM', 'HIGH'))){
                echo OkcsTestHelper::getFileContents('/contentRecommendationsResponse.txt');
            }
            else{
                OkcsTestHelper::html_response_code(400);
            }
        }
    }

    public function repositories(){
        $queryParameters = OkcsTestHelper::getQueryParameters();
        $queryParameterArray = explode('/', OkcsTestHelper::getParameterString());
        if(($queryParameterArray[2] === 'repositories') && ($queryParameterArray[3] === 'default') && ($queryParameterArray[4] === 'availableLocales') && ($queryParameters['mode'])==='full'){
            echo OkcsTestHelper::getFileContents('/locales.txt');
            return;
        }
    }
    
    public function endpoint(){
        $queryParameters = OkcsTestHelper::getParameterString();
        $array = explode("/", $queryParameters);
        if(file_exists(CPCORE . 'Controllers/UnitTest/okcs/mockservice/km/api/' . $array[1] . '/')){
            $methodCall = str_replace("-", "_", $array[2]);
            self::$methodCall();
        }
        else {
            OkcsTestHelper::html_response_code(500);
        }
    }
    
    public function contentTypes(){
        $queryParameters = OkcsTestHelper::getQueryParameters();
        $queryParameterArray = explode('/', OkcsTestHelper::getParameterString());
            // retrieves all the channels ex - http://mock-testing.reno.us.oracle.com/ci/unitTest/OkcsKmApi/endpoint/contentTypes?orderBy=referenceKey
        if(($queryParameterArray[2] === 'contentTypes') && ($queryParameters['q'] !== '') && ($queryParameters['orderBy']) === 'referenceKey'){
            $path = '/channels.txt';
            echo OkcsTestHelper::getFileContents($path);
            return;
        }
        elseif(($queryParameterArray[2] === 'contentTypes') && (array_key_exists('referenceKey', $queryParameters))){
            if((int)$queryParameters['referenceKey'] > 600 || (int)$queryParameters['referenceKey'] === 0){
                $path = '/content_types/refrencekeyChannel/'. $queryParameters['referenceKey'] . '.txt';
                echo OkcsTestHelper::getFileContents($path);
                return;
            }
            else{
                OkcsTestHelper::html_response_code(intval($queryParameterArray[3]));
                return;
            }
        }
        elseif($queryParameterArray[2]='contentTypes'){
            if((int)$queryParameterArray[3] > 600 || (int)$queryParameterArray[3] === 0){
                if($queryParameterArray[4] !== 'categories'){
                    $path = '/content_types/'.$queryParameterArray[3].'.txt';
                    echo OkcsTestHelper::getFileContents($path);
                    return;
                }
                // retrieves all the categories for each channel http://mock-testing.reno.us.oracle.com/ci/unitTest/OkcsKmApi/endpoint/contentTypes/CATEGORY_TEST/categories?mode=FULL
                else{
                    $path = '/content_types/categories/'.$queryParameterArray[3].'.txt';
                    echo OkcsTestHelper::getFileContents($path);
                    return;
                }
            }
            else{
                OkcsTestHelper::html_response_code($queryParameterArray[3]);
                return;
            }
        }
        OkcsTestHelper::html_response_code(404);
    }
    
    public function batch(){
        $response = new \stdClass();
        $postData = json_decode(file_get_contents("php://input"), true);
        $result = array();
        $ContentTypes = array("CATEGORY_TEST", "CATEGORY_TEST.1", "DEFECT", "FACET_TESTING", "FILE_ATTACHMENT", "IM_FILE_ATTACHMENT", "MULTIPLE_NODES", "NODE_ATRR", "NODE_ATTR_2", "NULLCHANNEL", "SOLUTIONS", "TEST", "TEST_FILE_ATTACHMENT");
        $length = count($postData['requests']);
        $count = 0;
        foreach($postData['requests'] as $key => $request){
            $req = explode("/", $request['relativeUrl']);
            if(Text::stringContains($request['relativeUrl'], '/subscriptions/')) {
                echo OkcsTestHelper::getFileContents('/subscriptionDetailList.txt');
                return;
            }
            else if(Text::stringContains($request['relativeUrl'], "v1/content?q=filterMode.contentState eq 'PUBLISHED'&offset=0&limit=1&interfaceId=1&mode=KEY") && empty($postData['requests'][$key + 1])) {
                echo OkcsTestHelper::getFileContents('/SiteMapIndex.txt');
                return;
            }
            else if(Text::stringContains($request['relativeUrl'], "v1/content?q=filterMode.contentState eq 'PUBLISHED'&offset=0&limit=600&interfaceId=1&mode=KEY") && empty($postData['requests'][$key + 1])) {
                echo OkcsTestHelper::getFileContents('/SiteMapPage.txt');
                return;
            }
            else if(Text::stringContains($request['relativeUrl'], "v1/content?q=filterMode.contentState eq 'PUBLISHED'&offset=0&limit=600&interfaceId=1&orderBy=recordId&mode=KEY") && empty($postData['requests'][$key + 1])) {
                echo OkcsTestHelper::getFileContents('/SiteMapPage.txt');
                return;
            }
            else if(Text::stringContains($request['relativeUrl'], "v1/content?q=locale.recordId in ('en_US') and documentId in") && $length > 1) {
                echo OkcsTestHelper::getFileContents('/docIdBatch.txt');
                return;
            }
            else if(Text::stringContains($request['relativeUrl'], "learned") || Text::stringContains($request['relativeUrl'], "manual")) {
                if(Text::stringContains($request['relativeUrl'], "48")) {
                    echo OkcsTestHelper::getFileContents('/relatedAnswers48.txt');
                    return;
                }
                else if(Text::stringContains($request['relativeUrl'], "49")) {
                    echo OkcsTestHelper::getFileContents('/relatedAnswers49.txt');
                    return;
                }
            }
            else if(Text::stringContains($request['relativeUrl'], "/publicDocumentCount")) {
                if(in_array($req[2], $ContentTypes)){
                    if($key === 0){
                        $response = '{"items":[{"id":"0","body":"{\"publicDocumentCount\":' . 5 . '}","headers":[{"name":"Content-Language","value":"en-US"},{"name":"RNT-Time","value":"D=36669 t=1454450574560848"},{"name":"RNT-Machine","value":"12.111"},{"name":"X-ORACLE-DMS-ECID","value":"0000LA^0cJY6uH85RjH7id1Mg7Ea0000Fq"},{"name":"Date","value":"Tue, 02 Feb 2016 22:02:54 GMT"},{"name":"Access-Control-Allow-Origin","value":"*"},{"name":"Vary","value":"User-Agent"},{"name":"Transfer-Encoding","value":"chunked"},{"name":"Keep-Alive","value":"timeout=15, max=95"},{"name":"Connection","value":"Keep-Alive"},{"name":"Content-Type","value":"application\/json"},{"name":"Server","value":"Apache"}]}';
                        $count++;
                    }
                    else {
                        $response = $response . ', {"id":"' . $key . '","body":"{\"publicDocumentCount\":'. 5 . '}","headers":[{"name":"Content-Language","value":"en-US"},{"name":"RNT-Time","value":"D=27166 t=1454450574701139"},{"name":"RNT-Machine","value":"12.111"},{"name":"X-ORACLE-DMS-ECID","value":"0000LA^0cLi6uH85RjH7id1Mg7Ea0000Fr"},{"name":"Date","value":"Tue, 02 Feb 2016 22:02:54 GMT"},{"name":"Access-Control-Allow-Origin","value":"*"},{"name":"Vary","value":"User-Agent"},{"name":"Transfer-Encoding","value":"chunked"},{"name":"Keep-Alive","value":"timeout=15, max=94"},{"name":"Connection","value":"Keep-Alive"},{"name":"Content-Type","value":"application\/json"},{"name":"Server","value":"Apache"}]}';
                        $count++;
                    }
                }
                else {
                    $response = $response . ', {"id":"' . $key . '","body":"{\"error\":{\"title\":\"Expected object not found. Cause: Expected object not found for ID: FcAQ\",\"errorPath\":null,\"errorCode\":\"OKDOM-GEN0002\",\"type\":\"VALIDATION\",\"detail\":null},\"errorDetails\":[{\"title\":\"Expected object not found for ID: FcAQ\",\"errorPath\":null,\"errorCode\":\"OKDOM-GEN0001\",\"type\":\"VALIDATION\",\"detail\":null}]}","headers":[{"name":"Content-Language","value":"en-US"},{"name":"RNT-Time","value":"D=27166 t=1454450574701139"},{"name":"RNT-Machine","value":"12.111"},{"name":"X-ORACLE-DMS-ECID","value":"0000LA^0cLi6uH85RjH7id1Mg7Ea0000Fr"},{"name":"Date","value":"Tue, 02 Feb 2016 22:02:54 GMT"},{"name":"Access-Control-Allow-Origin","value":"*"},{"name":"Vary","value":"User-Agent"},{"name":"Transfer-Encoding","value":"chunked"},{"name":"Keep-Alive","value":"timeout=15, max=94"},{"name":"Connection","value":"Keep-Alive"},{"name":"Content-Type","value":"application\/json"},{"name":"Server","value":"Apache"}]}';
                    $count++;
                }
                if(empty($postData['requests'][$key + 1])) {
                    $response = $response . '], "hasMore":false,"count":' . $count . '}';
                    echo $response;
                    return;
                }
                continue 1;
            }
            parse_str(parse_url($request['relativeUrl'], PHP_URL_QUERY), $params);
            $categoryObject['id'] = (string)$request['id'];
            //Trying to parse data from strings in the form of "q=key1 eq value1 and key2 eq value2"
            $query = explode(" ", $params['q']);
            $queryHash = array();
            for($count = 0; $count < sizeof($query); $count++){
                if($query[$count] == 'eq'){
                    $queryHash[$query[$count-1]] = $query[$count+1];
                }
            }
            $resultPath = CPCORE . 'Controllers/UnitTest/okcs/mockservice/km/api/v1/guest/categories/body/' . $queryHash['externalId'] . '.txt';
            $fileContents = fopen($resultPath, "r");
            $categoryObject['body'] = fread($fileContents, filesize($resultPath));
            $categoryObject['headers'] = json_decode(OkcsTestHelper::getFileContents('/categories/headers/' . $queryHash['externalId'] . '.txt'));
            array_push($result, $categoryObject);
        }
        $response->items = $result;
        $response->hasMore = false;
        echo json_encode($response);
    }
    
    public function testing(){
        $okcsImApiUrl ='http://slc01fjo.us.oracle.com:8227/km/api/';
        OkcsTestHelper::updateMockAnswerResults($okcsImApiUrl, range(1000000, 1000053));
    }

    public function users(){
        $queryParameters = OkcsTestHelper::getQueryParameters();
        $queryParameterArray = explode('/', OkcsTestHelper::getParameterString($queryParameterArray));
        if($queryParameterArray[3] === 'slatest'){
            echo OkcsTestHelper::getFileContents('/TEST.txt');
            return;
        }
        elseif($queryParameterArray[3] === 'test'){
            echo OkcsTestHelper::getFileContents('/userstest.txt');
            return;
        }
        elseif($queryParameterArray[4] === 'subscriptions'){
            echo OkcsTestHelper::getFileContents('/subscriptions.txt');
            return;
        }
        if(strpos($queryParameters['q'], 'search_language')){
            echo OkcsTestHelper::getFileContents('/RecordID.txt');
            return;
        }
        if(strpos($queryParameters['q'], 'favorite_document')){// fetches List of Favorites
            echo OkcsTestHelper::getFileContents('/favorites.txt');
            return;
        }
        echo OkcsTestHelper::getFileContents('/TEST.txt');
    }
    public function categories(){
        $queryParameters = OkcsTestHelper::getQueryParameters();
        $queryParameterArray = explode('/', OkcsTestHelper::getParameterString());
        if(($queryParameterArray[2] === 'categories') && Text::stringContains($queryParameters['q'], "externalType eq &#039;PRODUCT&#039;")){
            echo OkcsTestHelper::getFileContents('/categories/onlyproducts.txt');
            return;
        }
        elseif(($queryParameterArray[2] === 'categories') && Text::stringContains($queryParameters['q'], "externalType eq &#039;CATEGORY&#039;")){//echo('TWO');
            echo OkcsTestHelper::getFileContents('/categories/onlycategories.txt');
            return;
        }
        //FacetFilter mock response
        elseif(($queryParameterArray[2] === 'categories') && Text::stringContains($queryParameters['q'], "&#039;RN_PRODUCT_7&#039;,&#039;RN_CATEGORY_4&#039;,&#039;CMS-XML&#039;,&#039;OKKB-TEST&#039;")){
            echo OkcsTestHelper::getFileContents('/categories/categoryListDetails.txt');
            return;
        }
        elseif(($queryParameterArray[2] === 'categories') && ($queryParameters['mode']) === 'FULL' && $queryParameterArray[4] && ($queryParameterArray[4] !== 'children')){
            echo OkcsTestHelper::getFileContents('/categories/filter.txt');
            return;
        }
        elseif(($queryParameterArray[2] === 'categories') && empty($queryParameters) && $queryParameterArray[3] === 'RN_PRODUCT_1'){
            echo OkcsTestHelper::getFileContents('/categories/SingleProduct.txt');
            return;
        }
        elseif(($queryParameterArray[2] === 'categories') && empty($queryParameters) && $queryParameterArray[3] === 'RN_CATEGORY_1'){
            echo OkcsTestHelper::getFileContents('/categories/SingleCategory.txt');
            return;
        }
        elseif(($queryParameterArray[2] === 'categories') && ($queryParameterArray[3] === 'WINDOWS') && ($queryParameterArray[4] === 'children') && ($queryParameters['mode']) === 'FULL'){
            echo OkcsTestHelper::getFileContents('/categories/windows.txt');
            return;
        }
        elseif(($queryParameterArray[2] === 'categories') && ($queryParameterArray[3] === 'OPERATING_SYSTEMS') && ($queryParameterArray[4] === 'children') && ($queryParameters['mode']) === 'FULL'){
            echo OkcsTestHelper::getFileContents('/categories/OPERATING_SYSTEMS.txt');
            return;
        }
        elseif(($queryParameterArray[2] === 'categories') && ($queryParameterArray[3] === 'COMPANIES') && ($queryParameterArray[4] === 'children') && ($queryParameters['mode']) === 'FULL'){
            echo OkcsTestHelper::getFileContents('/categories/COMPANIES.txt');
            return;
        }
        elseif(($queryParameterArray[2] === 'categories') && (substr($queryParameterArray[3],0,3) === 'RN_') && ($queryParameterArray[4] === 'children') && ($queryParameters['mode']) === 'FULL'){
            echo OkcsTestHelper::getFileContents('/categories/' . $queryParameterArray[3] . '_children.txt');
            return;
        }
        elseif(($queryParameterArray[2] === 'categories') && ($queryParameterArray[4] === 'children')){
            echo OkcsTestHelper::getFileContents('/nochildren.txt');
            return;
        }
        elseif(($queryParameterArray[2] === 'categories') && $queryParameterArray[3] === 'RN_PRODUCT_1'){
            echo OkcsTestHelper::getFileContents('/categories/SingleProduct.txt');
            return;
        }
        elseif(($queryParameterArray[2] === 'categories') && $queryParameterArray[3] === 'RN_CATEGORY_1'){
            echo OkcsTestHelper::getFileContents('/categories/SingleCategory.txt');
            return;
        }
        elseif(($queryParameterArray[2] === 'categories') && ($queryParameters['mode']) === 'FULL'){
            echo OkcsTestHelper::getFileContents('/categories/filter.txt');
            return;
        }
    }

    public function suggestedSearch(){
        $queryParameters = OkcsTestHelper::getQueryParameters();
        $queryParameterArray = explode('/', OkcsTestHelper::getParameterString());
        if(($queryParameterArray[2] === 'suggestedSearch') && Text::stringContains($queryParameters['categories'], "RN_PRODUCT_8,RN_CATEGORY_18")){
            echo OkcsTestHelper::getFileContents('/suggestedSearchCombo.txt');
        }
        else if(($queryParameterArray[2] === 'suggestedSearch') && Text::stringContains($queryParameters['categories'], "RN_PRODUCT_8")){
            echo OkcsTestHelper::getFileContents('/suggestedSearchProduct.txt');
        }
        else if(($queryParameterArray[2] === 'suggestedSearch') && Text::stringContains($queryParameters['categories'], "RN_CATEGORY_12")){
            echo OkcsTestHelper::getFileContents('/suggestedSearchCategory.txt');
        }
        else if($queryParameters['matchAllCategories'] === 'true')
            echo OkcsTestHelper::getFileContents('/suggestedSearchMatchAllCategsTrue.txt');
        else if($queryParameters['matchAllCategories'] === 'false')
            echo OkcsTestHelper::getFileContents('/suggestedSearchMatchAllCategsFalse.txt');
        else if($queryParameters['applyCTIndexStatus'] === 'true')
            echo OkcsTestHelper::getFileContents('/suggestedSearchApplyCtIndexTrue.txt');
        else if($queryParameters['applyCTIndexStatus'] === 'false')
            echo OkcsTestHelper::getFileContents('/suggestedSearchApplyCtIndexFalse.txt');
        else{
            echo OkcsTestHelper::getFileContents('/suggestedSearch.txt');
        }
    }

    public function auth(){
        echo OkcsTestHelper::getFileContents('/integration-authorize.txt');
    }
    
    public function dataForms(){
        $queryParameterArray = explode('/', OkcsTestHelper::getParameterString());
        $path = '/data_forms/'.$queryParameterArray[3].'.txt';
        echo OkcsTestHelper::getFileContents($path);
    }

    private function changeResourcePath($response){
        $responseObject = json_decode($response);
        $responseObject->resourcePath = 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsMockFAS/resources/';
        return json_encode($responseObject);
    }

    protected function _phonyLogin() {
        // Yes, this should do nothing.
    }
   
}
