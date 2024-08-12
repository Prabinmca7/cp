<?
use RightNow\Utils,
    RightNow\Internal\Libraries\Widget\Registry;

/**********************
* 
* Filesystem Functions
*
**********************/
function isReadableFile($filePath){
    return Utils\FileSystem::isReadableFile($filePath);
}

function isReadableDirectory($dir){
    return Utils\FileSystem::isReadableDirectory($dir);
}
/**
 * @private
 */
function getOptimizedAssetsDir(){
    return Utils\FileSystem::getOptimizedAssetsDir();
}

/*********************
*
* Widget Functions
*
*********************/
function getBaseStandardWidgetPath(){
    return Registry::getBaseStandardWidgetPath();
}
function getBaseCustomWidgetPath(){
    return Registry::getBaseCustomWidgetPath();
}
function getAbsoluteWidgetPath($widgetPath){
    return Registry::getWidgetPathInfo($widgetPath)->absolutePath;
}
function requireStandardWidgetFile($widgetPath){
    require_once(Registry::getWidgetPathInfo($widgetPath)->absolutePath);
}
/**
 * This function is deprecated and has been replaced with Widget::widgetError()
 *
 * @param $widgetPath string The widget where the error occurred
 * @param $errorMessage string The error to display
 * @return string The html error
 */
function widgetError($widgetPath, $errorMessage){
    return \Widget::widgetError($widgetPath, $errorMessage);
}
function tabIndex($attribute, $offset){
    if(is_int($attribute))
        return "tabindex='$attribute$offset'";
}

/*********************
*
* Framework Functions
*
*********************/
function checkCache($key){
    return Utils\Framework::checkCache($key);
}
function inArrayCaseInsensitive($array, $search){
    return Utils\Framework::inArrayCaseInsensitive($array, $search);
}
function setCache($key, $value, $shouldSerialize=null){
    return Utils\Framework::setCache($key, $value, $shouldSerialize);
}
function isLoggedIn(){
    return Utils\Framework::isLoggedIn();
}
function isPta(){
    return Utils\Framework::isPta();
}
function isOpenLogin(){
    return Utils\Framework::isOpenLogin();
}
function isSpider(){
    return false;
}
function createToken($id){
    return Utils\Framework::createToken($id);
}
function isValidSecurityToken($token, $value){
    return Utils\Framework::isValidSecurityToken($token, $value);
}
function cpCreateTokenExp($id, $requireChallenge=false){
    return Utils\Framework::createTokenWithExpiration($id, $requireChallenge);
}
function optlistGet($optlistID){
    return Utils\Framework::getOptlist($optlistID);
}
function getNameFields($table){
    $names = array();
    if(getConfig(intl_nameorder)){
        $names[] = 'last_name';
        $names[] = 'first_name';
    }
    else{
        $names[] = 'first_name';
        $names[] = 'last_name';
    }
    if($table === 'incidents'){
        array_walk($names, function(&$element){$element = "contact_$element";});
    }
    return $names;
}
 /**
 * this function is used for when product category linking is enabled
 * it fills the data array with the correct options for the prod/cat UI
 * @param $selected array - selected categories from the database
 * @param $links array -an array of categories that are linked to a product
 * @param $data array - a reference to the data array where the callee wants this information stored
 */
function mapSelectedToLinks($selected, $links, &$data){
    $index = 0;
    for($i = 0; $i < count($selected) + 1; $i++)
    {
        if(count($links[$index]))
            $data['opt_data'][$i]['disp'] = 'show';
        for($j=0; $j<count($links[$index]); $j++)
        {
            if($selected[$i]['id'] == $links[$index][$j][0])
                $data['opt_data'][$i]['data'][$j]['selected'] = 'selected';

                $data['opt_data'][$i]['data'][$j]['value'] = $links[$index][$j][0];
                $data['opt_data'][$i]['data'][$j]['label'] = $links[$index][$j][1];
        }
        $index = $selected[$i]['id'];
    }
}
function logMessage($message){
    return Utils\Framework::logMessage($message);
}
function addDevelopmentHeaderError($errorMessage){
    return Utils\Framework::addDevelopmentHeaderError($errorMessage);
}
function addDevelopmentHeaderWarning($warningMessage){
    return Utils\Framework::addDevelopmentHeaderWarning($warningMessage);
}
function addErrorToPageAndHeader($error, $return = false){
    return Utils\Framework::addErrorToPageAndHeader($error, $return);
}
function within_sql($string){
    return Utils\Framework::escapeForSql($string);
}
/**
 * Wrapper for SEO::getDynamicTitle
 * @param $type string answers or incidents
 * @param $recordID int the a_id or i_id
 * @return string the value
 * @deprecated Use SEO::getDynamicTitle instead
 */
function getDynamicTitle($type, $recordID){
    return \RightNow\Libraries\SEO::getDynamicTitle($type, $recordID);
}
/**
 * No longer returns metadata (keyword, products, categories) about the specified answer ID
 * @return String Empty string
 * @deprecated
 * @see #isSpider()
 */
function getAnswerMetaData(){
    return '';
}
function getIcon($url, $external=false){
    return Utils\Framework::getIcon($url, $external);
}
function destroyCookie($cookieName){
    return Utils\Framework::destroyCookie($cookieName);
}
function setCPCookie($name, $value, $expire, $path = '/', $domain = '', $httpOnly = true, $secure = -1){
    return Utils\Framework::setCPCookie($name, $value, $expire, $path, $domain, $httpOnly, $secure);
}
function writeContentWithLengthAndExit($content, $mimeType = null){
    return Utils\Framework::writeContentWithLengthAndExit($content, $mimeType);
}
function getErrorMessageFromCode($code){
    return Utils\Framework::getErrorMessageFromCode($code);
}
function getCustomFieldList($table, $visibility){
    return Utils\Framework::getCustomFieldList($table, $visibility);
}
function evalCodeAndCaptureOutput($code){
    return Utils\Framework::evalCodeAndCaptureOutput($code);
}

/***********************************
 * Deprecated Custlogin functions
 **********************************/
/**
 * @deprecated November 2011. Use contact_login instead.
 * @param $username String contacts.login value
 * @param $password String User-entered password value
 * @param $sessionID String Current session id
 * @param $hasCookies Int 0 if cookies are disabled, 1 otherwise
 * @return Object Contact profile
 */
function custlogin($username, $password, $sessionID, $hasCookies) {
    $pairData = array(
        'login' => $username,
        'sessionid' => $sessionID,
        'cookie_set' => $hasCookies,
        'login_method' => CP_LOGIN_METHOD_LOCAL,        
    );
    if (is_string($password) && $password !== '') {
        $pairData['password_text'] = $password;
    }
    return (object) RightNowApi::contact_login($pairData);
}
/**
 * @deprecated November 2011. Use contact_answer_access instead.
 * @return String Access level for the current user to use as a SQL query limiter
 */
function custlogin_access() {
    return RightNowApi::contact_answer_access();
}
/**
 * @deprecated November 2011. Use contact_login_verify instead.
 * @param $sessionID String Current session id
 * @param $cookieData String Cookie data
 * @param $pairData Array Contact data for the user
 * @return Object Contact profile
 */
function custlogin_verify(&$sessionID, $cookieData, $pairData = array()) {
    return RightNowApi::contact_login_verify($sessionID, $cookieData, $pairData);
}
/**
 * @deprecated November 2011. Use contact_logout instead.
 * @param $sessionID String Current session id
 * @param $cookieData String Cookie data
 * @return Int Whether the operation was successful
 */
function custlogout_session($sessionID, $cookieData) {
    return contact_logout(array(
        'sessionid' => $sessionID,
        'cookie' => $cookieData,
    ));
}
/**
 * @deprecated November 2011. Use contact_login_reverify instead.
 * @param $username String contacts.login value
 * @param $password String User-entered password value
 * @param $sessionID String Current session id
 * @return Object Contact profile
 */
function update_verified_contact($username, $password, $sessionID) {
    return (object) contact_login_reverify(array(
        'login' => $username,
        'password_hash' => $password,
        'sessionid' => $sessionID,
    ));
}
/**
 * @deprecated November 2011. Use contact_update_cookie instead.
 * @param $username String contacts.login value
 * @param $cookieExpireTime Mixed String timestamp or Int -1 to expire the cookie
 * @return Int Whether the operation was successful
 */
function update_cookie_expire($username, $cookieExpireTime) {
    return contact_login_update_cookie(array(
        'login' => $username,
        'expire_time' => $cookieExpireTime,
    ));
}

/*********************
*
* Tag Functions
*
*********************/
function makeCssTag($url){
	return Utils\Tags::createCssTag($url);
}

/*********************
*
* Text Functions
*
*********************/
function beginsWith($haystack, $needle){
	return Utils\Text::beginsWith($haystack, $needle);
}
function beginsWithCaseInsensitive($haystack, $needle){
	return Utils\Text::beginsWithCaseInsensitive($haystack, $needle);
}

function endsWith($haystack, $needle){
	return Utils\Text::endsWith($haystack, $needle);
}
function stringContains($haystack, $needle){
	return Utils\Text::stringContains($haystack, $needle);
}
function stringContainsCaseInsensitive($haystack, $needle){
	return Utils\Text::stringContainsCaseInsensitive($haystack, $needle);
}
function getSubstringAfter($haystack, $needle, $default = false){
	return Utils\Text::getSubstringAfter($haystack, $needle, $default);
}
function getSubstringBefore($haystack, $needle, $default = false){
	return Utils\Text::getSubstringBefore($haystack, $needle);
}
function getSubstringStartingWith($haystack, $needle, $default = false){
	return Utils\Text::getSubstringStartingWith($haystack, $needle, $default = false);
}
function strlenCompare($s1, $s2){
	return Utils\Text::strlenCompare($s1, $s2);
}
function rnt_mb_strlen($buffer){
	return Utils\Text::getMultibyteStringLength($buffer);
}
function removeSuffix($haystack, $needle){
    return Utils\Text::removeSuffixIfExists($haystack, $needle);
}
function removeSuffixIfExists($haystack, $needle){
	return Utils\Text::removeSuffixIfExists($haystack, $needle);
}
function removeTrailingSlash($path){
	return Utils\Text::removeTrailingSlash($path);
}
function escapeStringForJavaScript($string){
	return Utils\Text::escapeStringForJavaScript($string);
}
function unescapeQuotes($content){
	return Utils\Text::unescapeQuotes($content);
}
function joinOmittingBlanks($glue, $array){
	return Utils\Text::joinOmittingBlanks($glue, $array);
}
function truncateText($data, $length, $addEllipsis = true){
	return Utils\Text::truncateText($data, $length, $addEllipsis);
}
function expandAnswerTags($buffer, $isAdmin = false, $showConditionalSections = false){
	return Utils\Text::expandAnswerTags($buffer, $isAdmin, $showConditionalSections);
}
function getReadableFileSize($size){
	return Utils\Text::getReadableFileSize($size);
}
function getLanguageCode(){
	return Utils\Text::getLanguageCode;
}
function emphasizeText($text, $options = array()){
	return Utils\Text:: emphasizeText($text, $options);
}
function isValidEmailAddress($emailAddress){
	return Utils\Text::isValidEmailAddress($emailAddress);
}
function isValidUrl($urlString){
	return Utils\Text::isValidUrl($urlString);
}
function minifyCss($css){
	return Utils\Text::minifyCss($css);
}
function highlightTextHelper($text, $searchTermPhrase, $minimumSearchTermLength){
	return Utils\Text::highlightTextHelper($text, $searchTermPhrase, $minimumSearchTermLength);
}

function insertBeforeTag($haystack, $needle, $tagRegex){
	return Utils\Tags::insertBeforeTag($haystack, $needle, $tagRegex);
}

/*********************
*
* URL Functions
*
*********************/
function urlParmAdd($url, $key, $value){
    return Utils\Url::addParameter($url, $key, $value);
}
function urlParmDelete($url, $key){
    return Utils\Url::deleteParameter($url, $key);
}
function getParmIndex(){
    return Utils\Url::getParameterIndex();
}
function getUrlParm($key){
    return Utils\Url::getParameter($key);
}
function getUrlParmsString(){
    return Utils\Url::getParameterString();
}
function getUrlParmString($key){
    return Utils\Url::getParameterWithKey($key);
}
function getUrlParametersFromList($parameterList, $excludedParameters=array()){
    return Utils\Url::getParametersFromList($parameterList, $excludedParameters);
}
function sessionParameter(){
	return Utils\Url::sessionParameter();
}
function getYUICodePath($module){
    return "/rnt/rnw/yui_2.7/$module";
}
function communitySsoToken($openingCharacter = '?', $includeKey = true, $redirectUrl = ''){
    return Utils\Url::communitySsoToken($openingCharacter, $includeKey, $redirectUrl);
}
/**
 * Redirects to the given URL. This must be called before any page content has been sent.
 * @param $url URL string to redirect to.
 * @private
 */
function http_redirect($url){
    header("Location: $url");
}
/**
 * 'setFiltersFromUrl' was replaced with 'setFiltersFromAttributesAndUrl' in 11.5.
 * This function is necessary to support customer modified code that calls the old function name.
 *
 * @deprecated url.php:setFiltersFromAttributesAndUrl
 */
function setFiltersFromUrl($reportID, &$filters){
    return Utils\Url::setFiltersFromAttributesAndUrl(array('report_id' => $reportID), $filters);
}
function setFiltersFromAttributesAndUrl($attributes, &$filters){
    return Utils\Url::setFiltersFromAttributesAndUrl($attributes, $filters);
}
function replaceExternalLoginVariables($errorCode, $redirectPage){
    return Utils\Url::replaceExternalLoginVariables($errorCode, $redirectPage);
}
function getShortEufAppUrl($matchProtocol = 'sameAsCurrentPage', $path = ''){
    return Utils\Url::getShortEufAppUrl($matchProtocol, $path);
}
function getShortEufBaseUrl($matchProtocol = 'sameAsCurrentPage', $path = ''){
    return Utils\Url::getShortEufBaseUrl($matchProtocol, $path);
}
function getCachedContentServer($requireCachedServer = false, $path = '', $getIP = false){
    return Utils\Url::getCachedContentServer($requireCachedServer, $path, $getIP);
}
function convertInsecureUrlToNetworkPathReference($url){
    return Utils\Url::convertInsecureUrlToNetworkPathReference($url);
}
function isRequestHttps(){
    return Utils\Url::isRequestHttps();
}
function getHomePage($makeAbsolute = true){
    return Utils\Url::getHomePage($makeAbsolute);
}
function isExternalUrl($url){
    return Utils\Url::isExternalUrl($url);
}
function redirectToHttpsIfNecessary(){
    Utils\Url::redirectToHttpsIfNecessary();
}

/*********************
*
* MiddleLayer Functions
*
*********************/
/**
* This function adds format characters to the values of text fields with masks.
* @return
* @param $stringToFormat String The default value of the field without format characters
* @param $mask String The field's mask i.e. F(M#M#M#F)
*/
function put_format_chars($stringToFormat, $mask){
   if (strlen($stringToFormat) === 0)
       return $stringToFormat;
   $returnString = ''; //the default value of the text field with format characters
   for ($i = 0, $j = 0; $i < strlen($mask); $i += 2)
   {
       if ($mask[$i] === 'F') // format character found
       {
           $returnString .= $mask[$i+1];
       }
       else
       {
           $returnString .= $stringToFormat[$j];
           $j++;
       }
   }
   return $returnString;
}
function vis_sql($visPrefix, $labelField, $visField){
   return("case $visPrefix.$visField when 1 then $labelField else NULL end");
}
function parseFieldName($name, $input = false){
    return Utils\Connect::parseFieldName($name, $input);
}

/*********************
*
* Config/Message Functions
*
*********************/
function getMessage($id, $messageBase = 'RNW'){
    return Utils\Config::getMessage($id, $messageBase);
}
function getMessageJS($id, $messageBase = 'RNW'){
    return Utils\Config::getMessageJS($id, $messageBase);
}
function ASTRgetMessage($string){
    return Utils\Config::ASTRgetMessage($string);
}
function ASTRgetMessageJS($string){
    return Utils\Config::ASTRgetMessageJS($string);
}
function getConfig($id, $configBase = 'RNW_UI'){
    return Utils\Config::getConfig($id, $configBase);
}
function getConfigJS($id, $configBase = 'RNW_UI'){
    return Utils\Config::getConfigJS($id, $configBase);
}
function deprecatedCoreEnabled(){
    return 0;
}
function deprecatedEventsEnabled(){
    return 0;
}
function deprecatedJSLoadingEnabled(){
    return 0;
}
function contactLoginRequiredEnabled(){
    return Utils\Config::contactLoginRequiredEnabled();
}
function getMinYear(){
    return Utils\Config::getMinYear();
}
function getMaxYear($year = null){
    return Utils\Config::getMaxYear($year);
}
