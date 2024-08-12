<?php
namespace RightNow\Internal;

use RightNow\Connect\v1_4 as Connect,
    RightNow\Internal\Utils\Version as Version;

class Api
{
    /**
     * Retrieves info about the logged-in account.
     * @return Object Info about the account
     */
    public static function account_data_get()
    {
        return account_data_get();
    }

    /**
     * Logs the user in as an account.
     * @param array|null $pairData Info needed to get
     * @return object Info about the account
     */
    public static function account_login($pairData)
    {
        return account_login($pairData);
    }

    /**
     * Retrieves info about the logged-in account.
     * @param string $sessionID Session ID
     * @return mixed Object account or Null if not valid
     */
    public static function acct_login_verify($sessionID)
    {
        return acct_login_verify($sessionID);
    }

    /**
     * Abuse Detection System - relay submit.
     * @param string $site Site name
     * @param string $resource Resource being checked against
     * @param string $ip IP address
     * @return void
     */
    public static function ads_relay_submit($site, $resource, $ip)
    {
        ads_relay_submit($site, $resource, $ip);
    }

    /**
     * Sends an email regarding a KB answer.
     * @param array|null $pairData Info needed to send the email
     * @return int Status code Zero indicates the email was not sent
     */
    public static function ans_eu_forward($pairData)
    {
        return ans_eu_forward($pairData);
    }

    /**
     * Builds a temp table index hierarchy on the search term.
     * @param string $searchTerm Search term
     * @return string Name of the keyword temp table
     */
    public static function build_temp_index_hier($searchTerm)
    {
        return build_temp_index_hier($searchTerm);
    }

    /**
     * Returns the path to the site's certificate storage area.
     * @return string Path
     */
    public static function cert_path()
    {
        return cert_path();
    }

    /**
     * Returns the path to the site's cfg directory.
     * @return string Path
     */
    public static function cfg_path()
    {
        return cfg_path();
    }

    /**
    * Returns the beginning URL for either admin or enduser with the correct protocol depending on config settings
    * @param int $urlType The type of URL to return, either CALLED_BY_END_USER or CALLED_BY_ADMIN
    * @return string
    */
    public static function cgi_url($urlType)
    {
        return cgi_url($urlType);
    }

    /**
     * Returns info for proper routing of a chat.
     * @param array|null $chatInfo Info to route the chat
     * @return array Contains result_code and optionally rule_acts keys
     */
    public static function chat_route($chatInfo)
    {
        return chat_route($chatInfo);
    }

    /**
     * Returns a JWT (token) for the logged-in user
     * @param int $chatType Type of chat (1 = cleartext, 2 = use JWT)
     * @return array Contains the JWT as 'session'
     */
    public static function chat_contact_auth_generate($chatType)
    {
        $chatInfo = array('type' => $chatType);
        return chat_contact_auth_generate($chatInfo);
    }

    /**
     * Determines if the current viewer is a search engine spider.
     * @param string $userAgent User agent
     * @param mixed $nothing Not used
     * @param string $ipAddress IP Address
     * @return int spider type; 0 if not a spider
     */
    public static function check_spider($userAgent, $nothing, $ipAddress)
    {
        $nothing = (empty($nothing)) ? '' : $nothing;
        return check_spider($userAgent, $nothing, $ipAddress);
    }

    /**
     * Returns a bit list representing the current contact's answer access levels
     * @return string bit list
     */
    public static function contact_answer_access()
    {
        return contact_answer_access();
    }

    /**
     * Logs the contact in.
     * @param array|null $pairData API pair data
     * @return array info about the contact
     */
    public static function contact_login($pairData)
    {
        return contact_login($pairData);
    }

    /**
     * Sends a password reset email to the contact.
     * @param array|null $pairData API pair data
     * @return void
     */
    public static function contact_login_recover($pairData)
    {
        contact_login_recover($pairData);
    }

    /**
     * Attempts to reauthenticate a logged in user given their login, encrypted password, and existing session ID
     * @param array|null $pairData API pair data needed to re-authenticate user
     * @return Object Profile object of user
     */
    public static function contact_login_reverify($pairData){
        return contact_login_reverify($pairData);
    }

    /**
     * Updates the expire time of a contacts login cookie in the contact_sessions table
     * @param array|null $pairData API pair data with login and expire time data
     * @return mixed Result of API call
     */
    public static function contact_login_update_cookie($pairData){
        return contact_login_update_cookie($pairData);
    }

    /**
     * Logs out the current given their session ID and auth token.
     * @param array|null $pairData API pair data with session id and auth 'cookie'
     * @return mixed Result of API call
     */
    public static function contact_logout($pairData){
        return contact_logout($pairData);
    }

    /**
     * Retrieves a matching contact based on the supplied data
     * (email, first, last, etc.)
     * @param array|null $pairData API pair data
     * @return array info about the matching contact containing c_id key
     */
    public static function contact_match($pairData)
    {
        return contact_match($pairData);
    }

    /**
     * Checks if the contacts existing password was entered correctly based on
     * the supplied data: c_id, password_text
     * Assert: only called when contact is changing password, not resetting.
     * @param array|null $pairData API pair data
     * @return int Status code (0 = passwords do not match, 1 = passwords matched)
     */
    public static function contact_password_verify($pairData)
    {
        if (!isset($pairData['source_upd']) || !$pairData['source_upd'])
            $pairData['source_upd'] = array(
                'lvl_id1' => SRC1_EU,
                'lvl_id2' => SRC2_EU_LOGIN_SECURITY,
            );
        return contact_password_verify($pairData);
    }

    /**
     * Creates a new page set mapping.
     * @param array|null $pairData API pair data
     * @return int new ID of the created mapping
     */
    public static function cp_ua_mapping_create($pairData)
    {
        return cp_ua_mapping_create($pairData);
    }

    /**
     * Deletes the specified page set mapping.
     * @param array|null $pairData API pair data
     * @return int Status code
     */
    public static function cp_ua_mapping_destroy($pairData)
    {
        return cp_ua_mapping_destroy($pairData);
    }

    /**
     * Updates the specified page set mapping.
     * @param array|null $pairData API pair data
     * @return int status code
     */
    public static function cp_ua_mapping_update($pairData)
    {
        return cp_ua_mapping_update($pairData);
    }

    /**
     * Formats a currency string.
     * @param string $id ID
     * @param string $value Value
     * @return string Currency string
     */
    public static function currency_str($id, $value)
    {
        return currency_str($id, $value);
    }

    /**
     * Formats a date string.
     * @param int $format Type of formatting to apply
     * @param string $string To format
     * @return string formatted date
     */
    public static function date_str($format, $string)
    {
        return $string !== null ? date_str($format, $string) : "";
    }

    /**
     * Retrieves a guided assistance tree.
     * @param array|null $pairData API pair data
     * @return array The GA tree
     */
    public static function decision_tree_get($pairData)
    {
        return decision_tree_get($pairData);
    }

    /**
     * Decodes a string that was encoded using encode_base64_urlsafe.
     * @param string $value To decode
     * @return string Decoded value
     */
    public static function decode_base64_urlsafe($value)
    {
        return !is_null($value) ? decode_base64_urlsafe($value) : null;
    }

    /**
     * Insert data into DQA.
     * @param int $type The type of data
     * @param array|null $data Data to insert
     * @return void
     */
    public static function dqa_insert($type, $data)
    {
        dqa_insert($type, json_encode($data));
    }

    /**
     * Base64 encodes a string, taking care not to use characters that
     * are illegal in a URL.
     * @param string $value To encode
     * @return string Encoded value
     */
    public static function encode_base64_urlsafe($value)
    {
        return encode_base64_urlsafe($value);
    }

    /**
     * Determines whether the File Attachment Server is available
     * @return bool Whether FAS is enabled
     */
    public static function fas_enabled()
    {
        return fas_enabled();
    }

    /**
     * Returns a file from the file attachment server
     * @param string $fileName Name of file to get
     * @return mixed File details
     */
    public static function fas_get($fileName)
    {
        return fas_get($fileName);
    }

    /**
     * Returns the size of the specified file in the FAS.
     * @param string $fileName File name
     * @return string File size
     */
    public static function fas_get_filesize($fileName)
    {
        return fas_get_filesize($fileName);
    }

    /**
     * Returns a temporary file from the file attachment server
     * @param string $fileName Name of file to get
     * @return mixed Temp file from FAS
     */
    public static function fas_get_tmp_file($fileName)
    {
        return fas_get_tmp_file($fileName);
    }

    /**
     * Returns the size of the temp file.
     * @param string $fileName File name
     * @return string File size
     */
    public static function fas_get_tmp_filesize($fileName)
    {
        return fas_get_tmp_filesize($fileName);
    }

    /**
     * Determines if the file is in the File Attachment Server
     * @param string $fileName File name
     * @return bool Whether the file is in the FAS
     */
    public static function fas_has_file($fileName)
    {
        return fas_has_file($fileName);
    }

    /**
    * Determines if the file is a temp file in the File Attachment Server
    * @param string $fileName File name
    * @return bool Whether the file is in the FAS
    */
    public static function fas_has_tmp_file($fileName)
    {
        return fas_has_tmp_file($fileName);
    }

    /**
     * Places the file with the specified name as a temp file on FAS
     * @param string $fileName File name
     * @return bool Whether the operation was a success
     */
    public static function fas_put_tmp_file($fileName)
    {
        return fas_put_tmp_file($fileName);
    }

    /**
     * Returns the full path to the specified file
     * @param string $fileName File name
     * @param bool $isProductFile Whether to use tmp or prod_tmp
     * @return string Full path
     */
    public static function fattach_full_path($fileName, $isProductFile = false)
    {
        // fattach_full_path will get the prod_tmp directory location
        $fullPath = fattach_full_path($fileName);
        if (!$isProductFile) {
            // call fattach_full_path with an empty string just to get the directory location to chop off
            $toTrim = fattach_full_path("");

            $trimmed = substr($fullPath, strlen($toTrim));

            // in hosted, upload_tmp_dir should be '/tmp'
            // in non-hosted, upload_tmp_dir should be '/bulk/httpd/cgi-bin/site.cfg/tmp'
            $fullPath = get_cfg_var('upload_tmp_dir') . '/' . $trimmed;
        }

        return $fullPath;
    }

    /**
     * Returns an inline image
     * @param array $pairData Pair data 'index_field_name' and 'index_field_value'
     * @return array Inline image
     */
    public static function fattach_thread_guid_get($pairData)
    {
        return fattach_thread_guid_get($pairData);
    }

    /**
     * Retrieves flow rules for the document.
     * @param int $contactID Contact ID
     * @param int $flowID Flow ID
     * @param int $stateID State ID
     * @param bool $something Seriously?
     * @param int $docID Doc ID
     * @param int $nonEmptyFieldCount Non empty filed count
     * @param array|null $pairData API pair data
     * @param array|null $surveyData Survey data
     * @param array|null $questionData Question data (optional)
     * @param array|null $questionOrder Order of questions (optional)
     * @param array|null $typeData Type data
     * @param int $fieldCount Field count
     * @return array Flow rules
     */
    public static function flow_rules($contactID, $flowID, $stateID, $something, $docID, $nonEmptyFieldCount, $pairData, $surveyData, $questionData = null, $questionOrder = null, $typeData = null, $fieldCount = 0)
    {
        // flow_rules behaves differently depending on the number of parameters sent in
        // if all of these are null it is a fool proof indication that we want to call the version with less parameters
        if ($questionData === null && $questionOrder === null && $typeData === null && $fieldCount === 0)
            return flow_rules($contactID, $flowID, $stateID, $something, $docID, $nonEmptyFieldCount, $pairData, $surveyData);

        return flow_rules($contactID, $flowID, $stateID, $something, $docID, $nonEmptyFieldCount, $pairData, $surveyData,
            $questionData, $questionOrder, $typeData, $fieldCount);
    }

    /**
     * Executes the Content Manager retrieval function. Callers
     * should only continue execution if an error is returned.
     * @param array $pairData API pair data
     * @return array API pair with status code and message in case of error
     */
    public static function content_manager_content_get($pairData)
    {
        return content_manager_content_get($pairData);
    }

    /**
     * Determines if the previous request was valid.
     * @return int Status code (GEARMAN_CHECK_FAIL|GEARMAN_CHECK_WAITING|GEARMAN_CHECK_TRUE)
     */
    public static function gearman_validation_check_request()
    {
        return gearman_validation_check_request();
    }

    /**
     * Performs cleanup related to gearman validation.
     * @return void
     */
    public static function gearman_validation_cleanup()
    {
        gearman_validation_cleanup();
    }

    /**
     * Submits a validation request.
     * @param string $abuseChallenge Challenge
     * @param string $abuseChallengeResponse Response
     * @return void
     */
    public static function gearman_validation_send_request($abuseChallenge, $abuseChallengeResponse)
    {
        gearman_validation_send_request($abuseChallenge, $abuseChallengeResponse);
    }

    /**
     * Produces a new session ID.
     * @return string session id
     */
    public static function generate_session_id()
    {
        return generate_session_id();
    }

    /**
     * Returns the name of a generated keyword temp table.
     * @param string $searchTerm Search term
     * @param int $interfaceID ID of the interface
     * @return string Temp table name
     */
    public static function get_keyword_tmptbl($searchTerm, $interfaceID)
    {
        return get_keyword_tmptbl($searchTerm, $interfaceID);
    }

    /**
     * Gathers Smart Assistant response to incident data.
     * @param array|null $pairData API pair data
     * @return object Smart Assistant data
     */
    public static function incident_suggest($pairData)
    {
        return incident_suggest($pairData);
    }

    /**
     * Returns the interface display name
     * @return string Display name of current interface
     */
    public static function intf_dispname()
    {
        return intf_dispname();
    }

    /**
     * Returns the name of the interface.
     * @return string Name of current interface
     */
    public static function intf_name()
    {
        return intf_name();
    }

    /**
     * Determines if the specified IP address is whitelisted.
     * @param array $pairData Array of data to validate
     *              $pairData['ip_addr'] IP address
     *              $pairData['source'] where the call is made
     *              $pairData['user_agent'] user agent
     * @return Whether the IP is blessed:RET_ACCESS_VALIDATE_SUCCESS
     *               RET_CLIENT_ADDR_NOT_AUTH, RET_NO_CLIENT_ADDR_SPEC
     *               RET_USER_AGENT_NOT_AUTHORIZED
     */
    public static function access_validate($pairData)
    {
        return access_validate($pairData);
    }

    /**
     * Determines if the specified IP address is whitelisted.
     * @param string $validHosts Comma-separated list of valid hosts
     * @param string $ipAddress IP address
     * @return bool Whether the IP is blessed
     */
    public static function ipaddr_validate($validHosts, $ipAddress)
    {
        return ipaddr_validate($validHosts, $ipAddress);
    }

    /**
     * Detects the encoding of the input
     * @param string $text Text to examine
     * @return string Encoding of the text
     */
    public static function lang_detect_encoding($text)
    {
        return lang_detect_encoding($text);
    }

    /**
     * Returns an array of token objects.
     *
     * @param string $stringTokens String to parse
     * @param mixed $flags Flags to API
     * @param int $maxCount Max langs
     * @return array Tokenized string
     */
    public static function lang_tokenize($stringTokens, $flags, $maxCount)
    {
        return lang_tokenize($stringTokens, $flags, $maxCount);
    }

    /**
     * Transcodes a string between two encodings.
     * @param string $content Content to transcode
     * @param string $fromEncoding Encoding of `$content`
     * @param string $toEncoding Encoding to convert `$content` to
     * @return string Resulting transcoded string
     */
    public static function lang_transcode($content, $fromEncoding, $toEncoding)
    {
        return lang_transcode($content, $fromEncoding, $toEncoding);
    }

    /**
     * Loads up the cURL module to allow for server-server communication over SSL
     * @return bool Whether cURL was successfully loaded
     */
    public static function load_curl(){
        return load_curl();
    }

    /**
     * Forwards a document/campaign/survey to email addresses
     * @param string $track Tracking string
     * @param int $nodeID Campaign node ID (unused in Customer Portal)
     * @param int $webpageID Flow web page ID
     * @param int $emailCount Number of emails to forward to
     * @param array|null $emails Array of emails to forward to
     * @param string $greeting Greeting to use in forward
     * @param string $fromEmail Email of person initiating the forward
     * @param string $subject Subject of email forward
     * @return void
     */
    public static function ma_forward_friend($track, $nodeID, $webpageID, $emailCount, $emails, $greeting, $fromEmail, $subject)
    {
        ma_forward_friend($track, $nodeID, $webpageID, $emailCount, $emails, $greeting, $fromEmail, $subject);
    }

    /**
     * Returns marketing field mapping for the given table
     * @param int $table Table ID from which to get map
     * @return Object
     */
    public static function ma_get_field_column_map($table)
    {
        return ma_get_field_column_map($table);
    }

    /**
     * Get redirect URL for flow
     * @param int $flowId Flow ID
     * @param int $flowType Flow type
     * @param bool $fromWeb Whether process is from a web document
     * @return string Redirect URL for flow
     */
    public static function ma_get_flow_default_url($flowId, $flowType, $fromWeb)
    {
        return ma_get_flow_default_url($flowId, $flowType, $fromWeb);
    }

    /**
     * Returns marketing document
     * @param int $docID Document ID
     * @param int $contactID Contact ID
     * @param int $flowID Flow ID
     * @param int $formatID Mailing format ID
     * @param string $formShortcut Url parameter shortcut
     * @param string $email Contact's email
     * @param int $flags Flags determining document mode
     * @param bool $doStats Whether to perform stats
     * @param string $mailingIDs Mailing IDs
     * @param string $prefillValue Pipe-separated list of fields
     * @param array|null $surveyData Survey data
     * @param array|null $questionData Question data
     * @param int $source Type of document source
     * @param int $sourceID ID of document source
     * @param int $newID ID of new document source
     * @param int $newSource Type of new document source
     * @return string Marketing document
     */
    public static function ma_serve_doc_get($docID, $contactID, $flowID, $formatID, $formShortcut, $email, $flags, $doStats, $mailingIDs, $prefillValue, $surveyData, $questionData, $source, $sourceID, $newID, $newSource){
        return ma_serve_doc_get($docID, $contactID, $flowID, $formatID,
            $formShortcut, $email, $flags, $doStats, $mailingIDs, $prefillValue,
            $surveyData, $questionData, $source, $sourceID, $newID, $newSource);
    }

    /**
     * Returns service mail document
     * @param int $docID Document ID
     * @param int $contactID Contact ID
     * @param int $emailType The type of the email
     * @param int $trackType The type of the tracking string
     * @param int $flowID Flow ID
     * @param int $formatID Mailing format ID
     * @param string $formShortcut Url parameter shortcut
     * @param string $email Contact's email
     * @param int $flags Flags determining document mode
     * @param bool $doStats Whether to perform stats
     * @param string $mailingIDs Mailing IDs
     * @param string $prefillValue Pipe-separated list of fields
     * @param array|null $surveyData Survey data
     * @param array|null $questionData Question data
     * @param int $source Type of document source
     * @param int $sourceID ID of document source
     * @param int $newID ID of new document source
     * @param int $newSource Type of new document source
     * @param bool $excludeNotes Whether to include private notes in the document
     * @return string Document HTML
     */
    public static function serve_doc_get($docID, $contactID, $emailType, $trackType, $flowID, $formatID, $formShortcut, $email, $flags, $doStats, $mailingIDs, $prefillValue, $surveyData, $questionData, $source, $sourceID, $newID, $newSource, $excludeNotes){
        return serve_doc_get($docID, $contactID, $emailType, $trackType, $flowID, $formatID,
            $formShortcut, $email, $flags, $doStats, $mailingIDs, $prefillValue,
            $surveyData, $questionData, $source, $sourceID, $newID, $newSource, $excludeNotes);
    }

    /**
     * Returns XML representation of poll
     * @param int $surveyID Survey ID
     * @param int $flowID Flow ID
     * @param int $questionID Question ID
     * @param bool $isAdmin Whether request is from an admin
     * @param bool $isSyndicated Whether request is from a syndicated widget
     * @return string XML representation of poll
     */
    public static function ma_serve_poll_get($surveyID, $flowID, $questionID, $isAdmin, $isSyndicated)
    {
        return ma_serve_poll_get($surveyID, $flowID, $questionID, $isAdmin, $isSyndicated);
    }

    /**
     * Returns tracking object based on string representation
     * @param string $track String representation of tracking object
     * @return Object Tracking object
     */
    public static function generic_track_decode($track)
    {
        return generic_track_decode($track);
    }

    /**
     * Takes in an array of keys (must be strings) and sends them off
     *   to the cache servers to be fetched asynchronously
     * @param int $type Specifying which cache to retrieve values from
     * @param array|null $siteKeys Cache keys (must be strings) that are specific
     *   to the current site
     * @param array|null $sharedKeys Cache keys (must be strings) that
     *   are shared between sites
     * @return Object A "get handle" object that may be passed to the
     *   memcache_value_fetch() function to retrieve the actual values
     */
    public static function memcache_value_deferred_get($type, $siteKeys, $sharedKeys = array())
    {
        self::verifyFunctionExists(__FUNCTION__);
        return memcache_value_deferred_get($type, $siteKeys, $sharedKeys);
    }

    /**
     * Deletes a value in the cache
     * @param int $type Specifying which cache to delete values in
     * @param string $key The key for the value to delete in the cache
     * @return int Success code of MEMCACHED_SUCCESS or appropriate error code on failure
     */
    public static function memcache_value_delete($type, $key)
    {
        self::verifyFunctionExists(__FUNCTION__);
        return memcache_value_delete($type, $key);
    }

    /**
     * Returns an array containing the fetched cached values
     * @param int $type Specifying which cache to retrieve values from
     * @param object $handle A "get handle" obtained from a call to memcache_value_get()
     * @return array An associative array containing all keys that were requested.
     *   If the value for a key was successfully fetched, the value is set in
     *   the array slot for said key. If the value was not successfully fetched
     *   (i.e. key/value pair was not present in the cache), the key slot
     *   contains a NULL in the returned array.
     */
    public static function memcache_value_fetch($type, $handle)
    {
        self::verifyFunctionExists(__FUNCTION__);
        return memcache_value_fetch($type, $handle);
    }

    /**
     * Sets a value in the cache
     * @param int $type Specifying which cache to set values in
     * @param string $key The key for the value to set in the cache
     * @param string $value The value to set in the cache
     * @param int $timeout The amount of time in seconds before the pair is removed
     *   from the cache if set to 0 then the config MEMCACHED_DEFAULT_EXPIRATION
     *   will be used
     * @return int|null Success code of MEMCACHED_SUCCESS, appropriate error code on failure, or null on exception
     */
    public static function memcache_value_set($type, $key, $value, $timeout)
    {
        self::verifyFunctionExists(__FUNCTION__);
        try {
            return memcache_value_set($type, $key, $value, $timeout);
        }
        catch (\Exception $e) {
            //Ignore memcache_value_set exceptions - likely because 'ITEM TOO BIG'
        }
    }

    /**
     * Swaps out the loaded messagebase files within the API
     * @param string $alternateInterfaceName Interface name of the messagebase files to use
     * @return mixed Result of API call
     */
    public static function msgbase_switch($alternateInterfaceName){
        return msgbase_switch($alternateInterfaceName);
    }

    /**
     * Returns the optlist data given its ID
     * @param int $optlistId The ID of the optlist to return
     * @return array
     */
    public static function optl_get($optlistId)
    {
        return optl_get($optlistId);
    }

    /**
     * Returns formatted text
     * @param string $text Text to put in log file
     * @param int $options Option flags
     * @return string Formatted text
     */
    public static function print_text2str($text, $options)
    {
        $text = $text ? $text : '';
        $options = $options ? $options : 0;
        return print_text2str($text, $options);
    }

    /**
     * Set access levels for current session
     * @param string $accessLevels Access levels
     * @return void
     */
    public static function print_text_set_access($accessLevels)
    {
        print_text_set_access($accessLevels);
    }

    /**
     * Returns encrypted password
     * @param string $password Password
     * @param int $type Type of encryption
     * @return string Encrypted password
     */
    public static function pw_encrypt($password, $type)
    {
        return pw_encrypt($password, $type);
    }

    /**
     * Returns decrypted token
     * @param string $token Token to decrypt
     * @return string Decrypted token
     */
    public static function pw_rev_decrypt($token)
    {
        return pw_rev_decrypt($token);
    }

    /**
     * Returns encrypted token
     * @param string $token Token to encrypt
     * @return string Encrypted token
     */
    public static function pw_rev_encrypt($token)
    {
        return pw_rev_encrypt($token);
    }

    /**
     * Returns parsing results of search terms
     * @param int $searchType Search type
     * @param int $answerID Answer ID
     * @param string $searchTerms Search terms
     * @return array Associative array of parsing results
     */
    public static function rnkl_ext_doc_query_parse($searchType, $answerID, $searchTerms)
    {
        return rnkl_ext_doc_query_parse($searchType, $answerID, $searchTerms);
    }

    /**
     * Returns parsing results of query
     * @param string $query Query
     * @param int $incidentID Incident ID
     * @return array Associative array of parsing results
     */
    public static function rnkl_stem($query, $incidentID)
    {
        return rnkl_stem($query, $incidentID);
    }

    /**
     * Parses a search term into its various components
     * @param int $stopwordFlag Flag on how to handle stopwords
     * @param string $query The search term
     * @param int $dictionary Dictionary to use
     * @return array Parsed query
     */
    public static function rnkl_query_parse($stopwordFlag, $query, $dictionary)
    {
        return rnkl_query_parse($stopwordFlag, $query, $dictionary);
    }

    /**
     * Retrieves a cached site config variable
     * @param int $cacheVariable Variable to retrieve
     * @return int Value of cached variable
     */
    public static function sci_cache_int_get($cacheVariable){
        return sci_cache_int_get($cacheVariable);
    }

    /**
     * Sets terms to highlight
     * @param string $highlightTerms Terms to highlight
     * @return void
     */
    public static function set_highlight_terms($highlightTerms)
    {
        set_highlight_terms($highlightTerms);
    }

    /**
     * Sets the highlight wrapper
     * @param string $highlightWrapper Highlight wrapper
     * @return void
     */
    public static function set_highlight_wrapper($highlightWrapper)
    {
        set_highlight_wrapper($highlightWrapper);
    }

    /**
     * Sets the interface name for the current request
     * @param string $interfaceName The name of the interface
     * @return mixed Result of API call
     */
    public static function set_intf_name($interfaceName){
        return set_intf_name($interfaceName);
    }

    /**
     * Encrypts string
     * @param string $rawString Unencrypted string
     * @param string $secretKey Secret key
     * @param string $encryptionMethod Encryption method
     * @param string $keygenMethod Key generation method
     * @param string $paddingMethod Padding method
     * @param string $salt Salt
     * @param string $initializationVector Initialization vector
     * @param bool $base64Encode If True, also base64 encode the encrypted string.
     * @return string Encrypted string
     */
    public static function ske_buffer_encrypt($rawString, $secretKey, $encryptionMethod, $keygenMethod, $paddingMethod, $salt, $initializationVector, $base64Encode)
    {
        return ske_buffer_encrypt($rawString, $secretKey, $encryptionMethod, $keygenMethod, $paddingMethod, $salt, $initializationVector, $base64Encode);
    }

    /**
     * Decrypts string
     * @param string $encryptedString Encrypted string
     * @param string $secretKey Secret key
     * @param string $encryptionMethod Encryption method
     * @param string $keygenMethod Key generation method
     * @param string $paddingMethod Padding method
     * @param string $salt Salt
     * @param string $initializationVector Initialization vector
     * @param bool $base64Encode If True, also base64 encode the decrypted string.
     * @return string Decrypted string
     */
    public static function ske_buffer_decrypt($encryptedString, $secretKey, $encryptionMethod, $keygenMethod, $paddingMethod, $salt, $initializationVector, $base64Encode)
    {
        return ske_buffer_decrypt($encryptedString, $secretKey, $encryptionMethod, $keygenMethod, $paddingMethod, $salt, $initializationVector, $base64Encode);
    }


    /**
     * Retrieves a site-wide config setting
     * @param int $config The numerical config lookup key
     * @return mixed The value of the config setting
     */
    public static function site_config_int_get($config)
    {
        return site_config_int_get($config);
    }

    /**
     * Returns search words in sorted order
     * @param string $searchText Search text
     * @return string Search words in sorted order
     */
    public static function sort_word_string($searchText)
    {
        return sort_word_string($searchText);
    }

    /**
     * Runs direct SQL
     *
     * @param string $sqlCommand The SQL command to run
     * @return mixed Output of running SQL command
     */
    public static function test_sql_exec_direct($sqlCommand)
    {
        return test_sql_exec_direct($sqlCommand);
    }

    /**
     * Performs an eval of the provided code within the provided trust context. This allows
     * code to be evaled in both 'internal' and 'custom' modes.
     * @param string $trustContext Theoretical path to code being executed
     * @param string $scriptCode Code to execute
     * @param object $scope Context to use when executing $scriptCode. This object can be referenced using $this within the eval'd code.
     * @return mixed Result of API call
     */
    public static function trusted_eval($trustContext, $scriptCode, $scope = null){
        if($scope)
            return trusted_eval($trustContext, $scriptCode, $scope);
        return trusted_eval($trustContext, $scriptCode);
    }

    /**
     * Binds column to specified type.
     *
     * @param object $sqlInstance An instance as returned by sql_prepare
     * @param int $columnIndex Column index.
     * @param int $bindType One of: BIND_BIGINT, BIND_BIN, BIND_DATE, BIND_DTTM, BIND_INT, BIND_MEMO, BIND_NTS.
     * @param int $bindSize Size of field
     * @return mixed Result of bind call
     */
    public static function sql_bind_col($sqlInstance, $columnIndex, $bindType, $bindSize)
    {
        return sql_bind_col($sqlInstance, $columnIndex, $bindType, $bindSize);
    }

    /**
     * Fetch results from specified sql instance.
     *
     * @param object $sqlInstance An instance as returned by sql_prepare
     * @return array A row of queried data.
     */
    public static function sql_fetch($sqlInstance)
    {
        return sql_fetch($sqlInstance);
    }

    /**
     * Frees specified sql instance.
     *
     * @param object $sqlInstance An instance as returned by sql_prepare
     * @return mixed Result of API call
     */
    public static function sql_free($sqlInstance)
    {
        return sql_free($sqlInstance);
    }

    /**
     * Return query result as an integer.
     *
     * @param string $query Sql query string
     * @return int Result of query
     */
    public static function sql_get_int($query)
    {
        return sql_get_int($query);
    }

    /**
     * Return query result as a string.
     *
     * @param string $query Sql query string
     * @param int $maxSize The maximum string length to return
     * @return string Result of query
     */
    public static function sql_get_str($query, $maxSize)
    {
        return sql_get_str($query, $maxSize);
    }

    /**
     * Return query result as a datetime.
     *
     * @param string $query Sql query string
     * @return int Result of query
     */
    public static function sql_get_dttm($query)
    {
        return sql_get_dttm($query);
    }

    /**
     * Open a connection to SQL
     * @return bool Whether the connection was made successfully
     */
    public static function sql_open_db()
    {
        return sql_open_db();
    }

    /**
     * Close connection to SQL
     * @return bool Whether the connection was closed successfully
     */
    public static function sql_disconnect()
    {
        return sql_disconnect();
    }

    /**
     * Returns a sql instance that can passed to sql_bind_col, sql_fetch and sql_free
     *
     * @param string $query Sql query string
     * @return [instance]
     */
    public static function sql_prepare($query)
    {
        return sql_prepare($query);
    }

    /**
     * Returns stemmed version of search text
     * @param string $searchText Search text
     * @return string Stemmed version of search text
     */
    public static function strip_affixes($searchText)
    {
        return strip_affixes($searchText);
    }

    /**
     * Returns the shortcut for the next page of the survey
     * @param int $flowID Flow ID
     * @param int $questionSessionID Question session ID
     * @param int $wpID Web page ID if requesting previous page
     * @param bool $isBack True if we are requesting the previous page
     * @return string Shortcut for next page
     */
    public static function survey_resume_shortcut($flowID, $questionSessionID, $wpID, $isBack)
    {
        return survey_resume_shortcut($flowID, $questionSessionID, $wpID, $isBack);
    }

    /**
     * Returns SQL-compatible time representation
     * @param int $time UNIX timestamp
     * @return string SQL-compatible time representation
     */
    public static function time2db($time)
    {
        return time2db($time);
    }

    /**
     * Returns whether or not a given user agent is valid
     * @param string $validAgents Set of valid agents
     * @param string $userAgent User agent
     * @return bool Whether or not given $userAgent is valid
     */
    public static function ua_validate($validAgents, $userAgent)
    {
        return ua_validate($validAgents, $userAgent);
    }

    /**
     * Returns the length of the string
     * @param string $string The string
     * @return int Length of the string
     */
    public static function utf8_char_len($string)
    {
        return !is_null($string) ? utf8_char_len($string) : 0;
    }

    /**
     * Returns the cleansed string
     * @param string $string The string
     * @return string Cleansed string
     */
    public static function utf8_cleanse($string)
    {
        return utf8_cleanse($string);
    }

    /**
     * Returns the string truncated to the specified length
     * @param string $string The string to truncate
     * @param int $length The length to truncate the string to
     * @return string The truncated string
     */
    public static function utf8_trunc_nchars($string, $length)
    {
        return utf8_trunc_nchars($string, $length);
    }

    /**
     * Returns whether or not an old (encrypted) password and new password match
     * @param string $oldPassword Old (encrypted) password
     * @param string $newPassword New password
     * @return bool Whether or not passwords match
     */
    public static function ver_digest_compare_str($oldPassword, $newPassword)
    {
        return ver_digest_compare_str($oldPassword, $newPassword);
    }

    /**
     * Return an encrypted version of the given token
     * @param string $token Content to encrypt
     * @return string Encrypted version of token
     */
    public static function ver_ske_encrypt_urlsafe($token)
    {
        return ver_ske_encrypt_urlsafe($token);
    }

    /**
     * Return an encrypted version of the given token. More performant
     * than the non "fast" version but also slightly less secure.
     * @param string $token Content to encrypt
     * @return string Encrypted version of token
     */
    public static function ver_ske_encrypt_fast_urlsafe($token)
    {
        return !is_null($token) ? ver_ske_encrypt_fast_urlsafe($token) : '';
    }

    /**
     * Bind a view column with given parameters
     * @param object $viewHandle View handle
     * @param int $position Position
     * @param int $type Type
     * @param int $size Size
     * @return void
     */
    public static function view_bind_col($viewHandle, $position, $type, $size)
    {
        view_bind_col($viewHandle, $position, $type, $size);
    }

    /**
     * Clean up view resources
     * @param object $viewHandle View handle
     * @return void
     */
    public static function view_cleanup($viewHandle)
    {
        view_cleanup($viewHandle);
    }

    /**
     * Return a row of view data
     * @param object $viewHandle View handle
     * @return array Row of view data
     */
    public static function view_fetch($viewHandle)
    {
        return view_fetch($viewHandle);
    }

    /**
     * Returns the web index path
     * @return string Web index path
     */
    public static function webindex_path()
    {
        return webindex_path();
    }

    /**
     * Returns compressed version of given data
     * @param string $data Data to compress
     * @return string Compressed version of data
     */
    public static function zlib_compress($data)
    {
        return zlib_compress($data);
    }

    /**
     * Gets the list of custom fields for the specified table with the given visibility.
     * @param int $table One of the TBL_-style constants.
     * @param int $visibility One of the VIS_-style constants.
     * @return array of custom fields
     */
    public static function cf_get_list($table, $visibility)
    {
        return cf_get_list(array('tbl' => $table, 'vis' => $visibility));
    }

    /**
     * Updates interfaces.logo_compliant, indicating if the interface is in compliance
     * with regards to displaying the rightnow logo.
     *
     * @param bool $compliant True if interface in compliance, else false.
     * @return void
     */
    public static function logo_compliant_update($compliant)
    {
        logo_compliant_update($compliant);
    }

    /**
     * Returns an api profile object.
     *
     * @param array|null $parameters An associative array having keys: 'login', 'sessionid' and 'login_method'.
     * @return [object]
     */
    public static function contact_federated_login($parameters)
    {
        return contact_federated_login($parameters);
    }

    /**
     * Returns an array containing the contact's login, if successful.
     *
     * @param array|null $parameters An associative array having keys: 'token', 'type', 'subject', 'cf_name', 'url'
     * @return [array]
     */
    public static function sso_contact_token_validate($parameters)
    {
        return sso_contact_token_validate($parameters);
    }

    /**
     * Create a row in the cp_objects table.
     *
     * @param array|null $pairData An array having keys:
     *   'name' {string}  - 'framework' or relative widget path, minus the leading 'standard/' or 'custom/'
     *   'type' {integer} - One of optlist defines: CP_OBJECT_TYPE_(FRAMEWORK|WIDGET_STANDARD|WIDGET_CUSTOM)
     *
     * Example:
     *   array('name' => 'input/TextInput'  'type' => CP_OBJECT_TYPE_WIDGET_STANDARD)
     *
     * @return integer The cp_object_id that was created
     */
    public static function cp_object_create($pairData)
    {
        return cp_object_create($pairData);
    }

    /**
     * Remove rows from both of the cp_object tables for the specified cp_object_id.
     *
     * @param array|null $pairData An array having key 'cp_object_id'
     *
     * Example:
     *   array('cp_object_id' => 123)
     *
     * @return integer Returns 1 on success
     */
    public static function cp_object_destroy($pairData)
    {
        return cp_object_destroy($pairData);
    }

    /**
     * Returns a row from one of the cp_object tables.
     *
     * @param array|null $pairData An array having keys:
     *   'id' {integer} The cp_object_id
     *   'sub_tbl' {array} Specified only when targeting a row in the cp_object_versions table.
     *
     * Examples:
     *   cp_objects         - array('id' => 123)
     *   cp_object_versions - array('id' => 123, sub_tbl => array('tbl_id1' => TBL_CP_OBJECT_VERSIONS))
     *
     * @return array An array having keys:
     *   'cp_object_id' {integer}
     *   'name'         {string}  - 'framework' or relative widget path, minus the leading 'standard/' or 'custom/'
     *   'type'         {integer} - One of optlist defines: CP_OBJECT_TYPE_(FRAMEWORK|WIDGET_STANDARD|WIDGET_CUSTOM)
     *   'version'      {array}   - Contains version data when targeting cp_object_versions
     */
    public static function cp_object_get($pairData)
    {
        return cp_object_get($pairData);
    }

    /**
     * Add, update or delete rows from the cp_object_versions table.
     *
     * @param array|null $pairData An array having keys:
     *   'cp_object_id' {integer}
     *   'version'      {array}
     *
     * Example:
     *   array(
     *     'cp_object_id' => 123,
     *     'version' => array(
     *         'version_item' => array(
     *             'mode' => CP_OBJECT_MODE_(PRODUCTION|DEVELOPMENT|STAGING),
     *             'interface_id' => 1,
     *             'version' => '3.1.1',
     *             'action' => ACTION_ADD|ACTION_UPD|ACTION_DEL,
     *         ),
     *      ),
     *   );
     *
     * @return integer Returns 1 on success
     */
    public static function cp_object_update($pairData)
    {
        return cp_object_update($pairData);
    }

    /**
     * Returns whether the SIEBEL_EAI_HOST config is set, indicating that Siebel integration is enabled.
     *
     * @return bool True if siebel integration is enabled
     */
    public static function siebelEnabled()
    {
        return trim(\RightNow\Utils\Config::getConfig(SIEBEL_EAI_HOST)) !== '';
    }

    /**
     * Validates that the country and province IDs specified by the PTA data are valid values within the DB
     * @param array|null $addressFields List of address fields specified in the PTA string
     * @return array Potentially modified address fields to send to the IAPI
     */
    protected static function validateCountryAndProvince($addressFields){
        $countryID = isset($addressFields['country_id']) ? $addressFields['country_id'] : 0;
        $provinceID = isset($addressFields['prov_id']) ? $addressFields['prov_id'] : 0;
        if($countryID > 0){
            try{
                if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.8") >= 0)
                    $country = Connect\Country::fetch($countryID);
                else
                    $country = \RightNow\Connect\v1_3\Country::fetch($countryID);

                if($country){
                    //Country is valid, iterate over provinces to see if provided province ID is valid
                    if($provinceID > 0 && self::checkForValidState($country->Provinces, $provinceID)){
                        //Both country and province are valid, return fields unmodified
                        return $addressFields;
                    }
                    //Country is valid, but province is not a valid option
                    unset($addressFields['prov_id']);
                    return $addressFields;
                }
            }
            catch(\Exception $e){
                //Not a valid country, unset it below
            }
            unset($addressFields['country_id']);
        }
        if($provinceID > 0){
            try{
                if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.8") >= 0)
                    $stateCount = Connect\ROQL::query(sprintf("SELECT count() as total from Country C where C.Provinces.ID = %d", $provinceID))->next()->next();
                else
                    $stateCount = \RightNow\Connect\v1_3\ROQL::query(sprintf("SELECT count() as total from Country C where C.Provinces.ID = %d", $provinceID))->next()->next();
                if(intval($stateCount['total']) > 0){
                    return $addressFields;
                }
            }
            catch(\Exception $e){
                //If we get an exception, we'll just unset the province field below and return
            }
            unset($addressFields['prov_id']);
        }
        return $addressFields;
    }

    /**
     * Generates the list of channel fields which are being modified for the given username
     * @param array|null $setChannelFields List of fields set via PTA request
     * @param string $username Login for the contact to check
     * @return array List of channel fields to update
     */
    protected static function getListOfModifiedChannelFields(array $setChannelFields, $username){
        $modifiedChannelFields = array();
        $existingChannelFields = self::getExistingChannelFields($username);
        foreach($setChannelFields as $channelField){
            //Modification of an existing field
            if($existingChannelFields[$channelField['chan_type_id']]){
                if($channelField['username'] !== $existingChannelFields[$channelField['chan_type_id']]['Username']){
                    $modifiedChannelFields[] = $channelField;
                }
                //Delete from array so we can check later if we're deleting any fields
                unset($existingChannelFields[$channelField['chan_type_id']]);
            }
            //Brand new field being sent in
            else{
                $modifiedChannelFields[] = $channelField;
            }
        }
        //If we have any items left in this array, that means they exist in the DB now, but weren't sent in from PTA. This means
        //that the user is explicitly deleting their data from the DB, so add a entry with an empty username so we call sm_user_update
        //to null it out
        if(count($existingChannelFields)){
            foreach($existingChannelFields as $channelID => $unsetField){
                $modifiedChannelFields[] = array('chan_type_id' => $channelID, 'username' => '');
            }
        }
        return $modifiedChannelFields;
    }

    /**
     * Takes the contact ID and the list of channel fields being modified and deletes the existing row and creates a new row.
     * @param int $contactID ID of the contact
     * @param array|null $modifiedChannelFields List of modified channel fields
     * @return void
     */
    protected static function createOrUpdateChannelFields($contactID, array $modifiedChannelFields){
        $smUsers = contact_get(array(
            'id'      => $contactID,
            'sub_tbl' => array('tbl_id' => TBL_SM_USERS),
            'source_in' => array(
                'lvl_id1' => SRC1_EU,
                'lvl_id2' => SRC2_EU_PASSTHRU,
            ))
        );
        if(count($smUsers["sm_user"])){
            foreach($smUsers["sm_user"] as $smUser){
                sm_user_update(array('c_id' => null, 'sm_user_id' => $smUser['sm_user_id']));
            }
        }
        foreach($modifiedChannelFields as $modifiedChannelField){
            if($modifiedChannelField['username'] !== '' && $modifiedChannelField['username'] !== null){
                sm_user_create(array('c_id' => $contactID, 'chan_type_id' => $modifiedChannelField['chan_type_id'], 'username' => $modifiedChannelField['username']));
            }
        }
    }

    /**
     * Checks if the provided state ID is present in the list of states
     * @param array|null $stateList List of states
     * @param int $stateID ID to look for
     * @return bool Whether the given ID exists in the list
     */
    private static function checkForValidState($stateList, $stateID){
        foreach($stateList as $state) {
            if($state->ID === intval($stateID)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks to see if a function exists and throws an exception
     * if it does not
     * @param string $functionName Name of function to check
     * @return void
     * @throws \Exception if function does not exist
     */
    private static function verifyFunctionExists($functionName)
    {
        if(!function_exists($functionName))
            throw new \Exception("$functionName does not exist");
    }

    /**
     * Retrieves all of the currently set channel username fields for the given user login
     * @param string $username Contact username
     * @return array List of set channel usernames
     */
    private static function getExistingChannelFields($username){
        try {
            if(Version::compareVersionNumbers(Version::getVersionNumber(CP_FRAMEWORK_VERSION), "3.8") >= 0)
                $query = Connect\ROQL::query(sprintf("SELECT ChannelUsernames.ChannelType.ID, ChannelUsernames.UserNumber,
                                                      ChannelUsernames.Username FROM Contact WHERE Login = '%s'", Connect\ROQL::escapeString($username)))->next();
            else
                $query = \RightNow\Connect\v1_3\ROQL::query(sprintf("SELECT ChannelUsernames.ChannelType.ID, ChannelUsernames.UserNumber,
                                                      ChannelUsernames.Username FROM Contact WHERE Login = '%s'", \RightNow\Connect\v1_3\ROQL::escapeString($username)))->next();
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return array();
        }
        catch(\RightNow\Connect\v1_3\ConnectAPIErrorBase $e){
            return array();
        }

        $channelUsernames = array();
        while($row = $query->next()) {
            if($row['ID']){
                $channelUsernames[$row['ID']] = array(
                    'UserNumber' => $row['UserNumber'],
                    'Username' => $row['Username']
                );
            }
        }
        return $channelUsernames;
    }

    /**
     * Return the sourceUpd array for required field in sss_question, sss_discussion
     * and sss_comment.
     *
     * @return array Array of sourceUpd
     */
    private static function getSourceUpd()
    {
        return array(
            'lvl_id1' => SRC1_EU,
            'lvl_id2' => SRC2_EU_CONNECT,
        );
    }

}
