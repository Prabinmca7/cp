<?php
namespace RightNow\Libraries;

use \RightNow\Utils\Url,
    \RightNow\Utils\Text,
    \RightNow\Utils\Config,
    \RightNow\Libraries\AbuseDetection;

/**
 * Handles incoming POST requests by either validating the incoming data and performing
 * an action specific to the handler being executed or encountering a validation error
 * and saving the error code so widgets can process the error
 */
class PostRequest {
    public $clickstreamActionMapping = array();
    public static $constraints;

    function __construct() {
        $this->CI = get_instance();
        $this->clickstreamActionMapping = array(
            'sendForm' => 'incident_submit',
            'submitFeedback' => 'answer_feedback',
            'submitAnswerRating' => 'answer_rating',
            'doLogin' => 'account_login',
            'emailCredentials' => 'emailCredentials',
            'resetPassword' => 'resetPassword',
        );
    }

    /**
     * Handle form submissions that deal with creating or updating a Primary Object (Incident/Contact)
     * @return bool|string False if form is not valid, or a redirect location if it was submitted correctly
     */
    public function sendForm() {
        if(AbuseDetection::isAbuse() || (!$formatData = $this->CI->input->post('format')) || !$formatData['on_success_url']){
            $this->addError(Config::getMessage(REQUEST_COMPLETED_TIME_PLEASE_TRY_MSG));
            return false;
        }

        //Validation completed successfully, perform the second layer of validation
        $result = $this->CI->model('Field')->sendForm(array_values($this->getFormData()), array('i_id' => Url::getParameter('i_id')), $this->CI->input->post('smart_assistant') === 'true');

        if(count($result->errors)) {
            foreach($result->errors as $error) {
                $this->addError($error->externalMessage);
            }
            return false;
        }

        //Incident or Contact was created or updated, or Smart Assistant
        $result = $result->result;

        if($result['sa']) {
            self::addSmartAssistantResults($result['sa']);
            return false;
        }

        if($result['transaction'] && $result['transaction']['incident'] && $result['transaction']['incident']['key']) {
            $segment = "/{$result['transaction']['incident']['key']}/{$result['transaction']['incident']['value']}";
        }
        return $this->getRedirectUrl($result['redirectOverride'] ?: ($formatData['on_success_url'] . $segment), $formatData['add_params_to_url'] .
            '/messages/' . self::getMessageArray(Config::getMessage(SUCCESS_LBL), 'info', '', true));
            //TODO: When we do do a redirect, use flash data?
            //Conditionally tack on 'success' parameter in case the redirect url handles its own messaging?
    }

    /**
     * Handles data when a user submits an answer rating, but not the actual feedback
     * @return bool|string False on failure or redirect URL on success
     */
    public function submitAnswerRating() {
        if (AbuseDetection::isAbuse()) {
            $this->addError(Config::getMessage(REQUEST_COMPLETED_TIME_PLEASE_TRY_MSG));
            return false;
        }

        $formatData = $this->CI->input->post('format');
        $answerFeedback = $this->CI->input->post('answerFeedback');
        if (!is_array($answerFeedback) || !is_numeric($answerFeedback['OptionsCount']) || !is_numeric($answerFeedback['Threshold'])) {
            $this->addError(Config::getMessage(REQUEST_COMPLETED_TIME_PLEASE_TRY_MSG));
            return false;
        }

        $answerID = Url::getParameter('a_id');
        $answerRating = (int)$this->CI->input->post('answerRating');
        if($answerRating < 1){
            $this->addError(Config::getMessage(PLEASE_SELECT_RATING_ANSWER_MSG));
            return false;
        }
        if ($this->CI->model('Answer')->rate($answerID, $answerRating, $answerFeedback['OptionsCount'])) {
            //If no success URL is given or the rating is above threshold, just finish rendering the page with a submission successful message
            if(!is_array($formatData) || !$formatData['on_success_url'] || $answerRating > $answerFeedback['Threshold']) {
                $this->addMessage(Config::getMessage(FEEDBACK_SUBMITTED_SUCCESSFULLY_MSG), 'info');
                return false;
            }

            //Below threshold, ask for additional feedback
            $segment = "options_count/{$answerFeedback['OptionsCount']}/threshold/{$answerFeedback['Threshold']}/rating/{$answerRating}";
            return $this->getRedirectUrl(Url::addParameter($formatData['on_success_url'], 'a_id', $answerID), $segment);
        }

        $this->addError(Config::getMessage(REQUEST_COMPLETED_TIME_PLEASE_TRY_MSG));
        return false;
    }

    /**
     * Handles data when a user submits answer feedback content, which will be turned into an incident
     * @return bool|string False on failure or redirect URL on success
     */
    public function submitFeedback() {
        if (AbuseDetection::isAbuse()) {
            $this->addError(Config::getMessage(REQUEST_COMPLETED_TIME_PLEASE_TRY_MSG));
            return false;
        }

        $formData = $this->getFormData();
        $errors = array();
        if(!\RightNow\Utils\Validation::validateFields($formData, $errors)) {
            foreach($errors as $error) {
                $this->addError($error);
            }
            return false;
        }

        $answerFeedback = $this->CI->input->post('answerFeedback');
        $formatData = $this->CI->input->post('format');
        if (!is_array($answerFeedback) ||
            !is_numeric($answerFeedback['OptionsCount']) || !is_numeric($answerFeedback['Threshold']) ||
            !is_numeric($answerFeedback['Rating']) || !is_numeric($answerFeedback['AnswerId'])) {
            $this->addError(Config::getMessage(REQUEST_COMPLETED_TIME_PLEASE_TRY_MSG));
            return false;
        }

        $email = $this->CI->session->getProfileData('email') ?: $formData['Contact.Emails.PRIMARY.Address']->value ?: null;
        $incidentResult = $this->CI->model('Incident')->submitFeedback($answerFeedback['AnswerId'],
            $answerFeedback['Rating'],
            $answerFeedback['Threshold'],
            null,
            $formData['Incident.Threads']->value,
            $email,
            $answerFeedback['OptionsCount']
        );

        //Submission successful
        if ($incidentResult->result && $incidentResult->result->ID > 0) {
            $redirectUrl = '/app/' . Config::getConfig(CP_ANSWERS_DETAIL_URL);
            $segment = "/a_id/{$answerFeedback['AnswerId']}/messages/" . $this->getMessageArray(Config::getMessage(FEEDBACK_SUBMITTED_SUCCESSFULLY_MSG), 'info', '', true);
            if(is_array($formatData) && $formatData['on_success_url']) {
                $redirectUrl = $formatData['on_success_url'];
            }
            return $this->getRedirectUrl($redirectUrl, $segment);
        }
        else if($incidentResult->error){
            $this->addError($incidentResult->error->externalMessage);
        }
        else{
            $this->addError(Config::getMessage(REQUEST_COMPLETED_TIME_PLEASE_TRY_MSG));
        }
        return false;
    }

    /**
     * Handles login form submission
     * @return bool|string False on failure or redirect URL on success
     */
    public function doLogin() {
        if(AbuseDetection::isAbuse()){
            $this->addError(Config::getMessage(REQUEST_COMPLETED_TIME_PLEASE_TRY_MSG));
            return false;
        }
        $formatData = $this->CI->input->post('format');
        $response = $this->CI->model('Contact')->doLogin(
            $this->CI->input->post('Contact_Login'),
            $this->CI->input->post('Contact_Password'),
            $this->CI->session->getSessionData('sessionID'),
            null, $this->getRedirectUrl($formatData['on_success_url'], $formatData['add_params_to_url'])
        );

        //The user has been logged in. Either redirect to the `forceRedirect` URL (if the password is expired), or the `on_success_url` we passed in.
        if ($response->result['success'] === 1) {
            return $response->result['forceRedirect'] ?: $response->result['url'];
        }

        $this->addError($response->error ?: $response->result['message'] ?: Config::getMessage(USERNAME_PASSWD_ENTERED_INCOR_ACCT_MSG));
        return false;
    }

    /**
     * Handles username retrieval or password reset email submission
     * @return bool|string False on failure or redirect URL on success
     */
    public function emailCredentials() {
        if (AbuseDetection::isAbuse()) {
            $this->addError(Config::getMessage(REQUEST_COMPLETED_TIME_PLEASE_TRY_MSG));
            return false;
        }

        $credentialData = $this->CI->input->post('emailCredentials');
        if (!isset($credentialData['type']) || !isset($credentialData['value'])) {
            $this->addError(Config::getMessage(REQUEST_COMPLETED_TIME_PLEASE_TRY_MSG));
            return false;
        }

        $method = 'sendLoginEmail';
        $value = html_entity_decode($credentialData['value']);

        //Submitted field is required
        if($value === null || $value === '') {
            $this->addError($credentialData['type'] === 'password' ? Config::getMessage(A_USERNAME_IS_REQUIRED_MSG) : Config::getMessage(AN_EMAIL_ADDRESS_IS_REQUIRED_MSG));
            return false;
        }

        if($credentialData['type'] === 'password') {
            $method = 'sendResetPasswordEmail';
            if (Text::stringContains($value, ' ')) {
                $this->addError(sprintf(Config::getMessage(PCT_S_MUST_NOT_CONTAIN_SPACES_MSG), Config::getMessage(USERNAME_LBL)));
                return false;
            }
            if (Text::stringContains($value, '"')) {
                $this->addError(sprintf(Config::getMessage(PCT_S_CONTAIN_DOUBLE_QUOTES_MSG), Config::getMessage(USERNAME_LBL)));
                return false;
            }
            if (Text::stringContains($value, '<') || Text::stringContains($value, '>')) {
                $this->addError(sprintf(Config::getMessage(PCT_S_CNT_THAN_MSG), Config::getMessage(USERNAME_LBL)));
                return false;
            }
        }
        else {
            if(!Text::isValidEmailAddress($value)) {
                $this->addError(Config::getMessage(EMAIL_IS_NOT_VALID_MSG));
                return false;
            }
        }

        if ($return = $this->CI->model('Contact')->$method($value)->result) {
            self::addMessage($return['message'], 'info');
        }
        // Return false regardless of there being an error or not, since the response
        // from sendResetPasswordEmail and sendLoginEmail contains HTML. As a result
        // that data cannot be passed using the /messages/ URL parameter, since the
        // HTML will not be rendered.
        return false;
    }

    /**
     * Handles password reset
     * @return bool|string False on failure or redirect URL on success
     */
    public function resetPassword() {
        if(AbuseDetection::isAbuse() || (!$formatData = $this->CI->input->post('format')) || !$formatData['on_success_url']) {
            $this->addError(Config::getMessage(REQUEST_COMPLETED_TIME_PLEASE_TRY_MSG));
            return false;
        }

        $result = $this->CI->model('Field')->resetPassword($this->getFormData(), $this->CI->input->post('pw_reset'));
        if(count($result->errors)) {
            foreach($result->errors as $error) {
                $this->addError($error->externalMessage);
            }
            return false;
        }

        $result = $result->result;
        if ($result['transaction'] && $result['transaction']['contact'] && $result['transaction']['contact']['value']) {
            return $this->getRedirectUrl($formatData['on_success_url']);
        }
        $this->addError(Config::getMessage(REQUEST_COMPLETED_TIME_PLEASE_TRY_MSG));
        return false;
    }

    /**
     * Return a string comprised of $url and $parameters, stripped of any extra slashes and containing a leading slash.
     * @param string $url URL to redirect to
     * @param string $parameters Additional parameters to append
     * @return string Normalized URL path
     */
    protected function getRedirectUrl($url = '', $parameters = '') {
        return '/' . \RightNow\Internal\Utils\Widgets::normalizeSlashesInWidgetPath("$url/$parameters", true);
    }

    /**
     * Call abuse detection and pulls out form data and constraints from the post data.
     * @return array Array of form data
     */
    protected function getFormData() {
        $formData = $this->CI->input->post('formData') ?: array();
        $formConstraints = json_decode(base64_decode($this->CI->input->post('constraints')) ?: '{}', true);
        $formData = $this->transformBasicFields($formData, $formConstraints);

        //Populate form data with traditional naming so values can be prefilled if the page re-renders
        foreach($formData as $details){
            $_POST[str_replace(".", "_", $details->name)] = $details->value;
        }

        return $formData;
    }

    /**
     * Creates an array of properties from incoming constraint and form field data
     * @param string $name The name of the field
     * @param string $value The value of the field
     * @param array $constraints Constraints being applied to the specific field
     * @return array A list of properties for the given field
     */
    private function getFieldProperties($name, $value, array $constraints) {
        $properties = array(
            'name' => $name,
            'value' => ($constraints['isCheckbox'] && $value === null) ? false : $value,
            'required' => $constraints['required'] ? true : false,
            'constraints' => array_intersect_key($constraints, array_flip(\RightNow\Utils\Validation::getSupportedConstraints()))
        );

        if($constraints['requireCurrent']) {
            $properties['requireCurrent'] = true;
        }
        if($constraints['requireValidation']) {
            $properties['requireValidation'] = true;
        }

        //Add in the labels if given
        if($constraints['labels']) {
            $properties['label'] = $constraints['labels']['label_error'] ?: $constraints['labels']['label_input'] ?: '';
            if($constraints['requireValidation'] && ($labelValidation = $constraints['labels']['label_validation'])) {
                $properties['labelValidation'] = $labelValidation;
            }
        }
        return $properties;
    }

    /**
     * Processes form fields submitted without JavaScript manipulation.
     * @param array $fields Raw submitted form fields
     * @param array $fieldConstraints List of all field constraints
     * @return array Array Transformed form fields with date and password fields reduced
     */
    public function transformBasicFields(array $fields, array $fieldConstraints) {
        $processedFields = $groupedFields = array();
        $validGroupings = array('year', 'month', 'day', 'hour', 'minute', 'validation', 'currentpassword');

        //Add any constraints without an associated field to the fields array with a null value
        $removedFields = array_fill_keys(array_keys(array_diff_key($fieldConstraints, $fields)), null);
        foreach($fields + $removedFields as $name => $value) {
            if(($grouping = strtolower(substr(strrchr($name, '#'), 1))) && in_array($grouping, $validGroupings)) {
                $groupedFields[substr($name, 0, strrpos($name, '#'))][$grouping] = $value;
            }
            else {
                $processedFields[$name] = $this->getFieldProperties($name, $value, $fieldConstraints[$name] ?: array());
            }
        }

        //Look through the grouped fields and add them to the form data
        foreach($groupedFields as $name => $keys) {
            $field = $processedFields[$name] ?: $this->getFieldProperties($name, null, $fieldConstraints[$name] ?: array());
            if(isset($keys['validation'])) {
                $field['validation'] = $keys['validation'];
            }
            if(isset($keys['currentpassword'])) {
                $field['currentValue'] = $keys['currentpassword'];
            }

            if(isset($keys['year'])) {
                $dttmExists = array_key_exists('hour', $keys) && array_key_exists('minute', $keys);

                if($keys['year'] !== '' || $keys['month'] !== '' || $keys['day'] !== '') {
                    $field['value'] = sprintf('%s-%s-%s',
                        $keys['year'] ?: -1,
                        $keys['month'] ?: 13,
                        $keys['day'] ?: 32);
                    //If the fields don't exist, set them to 00 and pass through
                    if(!$dttmExists) {
                        $field['value'] .= ' 00:00:00';
                    }
                    else {
                        $concat = sprintf(' %s:%s:00',
                            $keys['hour'] ?: 25,
                            $keys['minute'] ?: 61);
                        $field['value'] = $field['value'] . $concat;
                    }
                }
                else {
                    $field['value'] = '';
                }
            }
            $processedFields[$name] = $field;
        }

        //Cast all of the fields to objects and return them
        return array_map(function($field) { return (object) $field; }, $processedFields);
    }

    /**
     * List of SmartAssistant results
     */
    protected static $smartAssistantResults = null;

    /**
     * Convenience function to add smart assistant results to a static field.
     * @param array|null $smartAssistantResults The array of smart assistant results.
     * @return void
     */
    public static function addSmartAssistantResults($smartAssistantResults) {
        self::$smartAssistantResults = $smartAssistantResults;
    }

    /**
     * Retrieve smart assistant results, if available.
     * @return array|null Array containing smart assistant results or null.
     */
    public static function getSmartAssistantResults() {
        return self::$smartAssistantResults;
    }

    protected static $messages = array();
    protected static $requestMessagesRetrieved = false;

    /**
     * Convenience function to add an 'error' type message.
     * @param string $message The message to display.
     * @param string $field The field name, if applicable, under which messages will be grouped.
     * @return void
     */
    public static function addError($message, $field = '') {
        self::addMessage($message, 'error', $field);
    }

    /**
     * Add a status message to self::$messages. Currently displayed by the BasicFormStatusDisplay widget.
     * @param string $message The message to display.
     * @param string $type The type of message, generally 'error' or 'info'.
     * @param string $field The field name, if applicable, under which messages will be grouped.
     * @return void
     * @throws \Exception if $message, $type or $field is not a string.
     */
    public static function addMessage($message, $type = 'error', $field = '') {
        foreach(array('message', 'type', 'field') as $input) {
            $value = $$input;
            if (!is_string($value)) {
                throw new \Exception(sprintf(Config::getMessage(PCT_S_IS_NOT_A_STRING_MSG), $input));
            }
        }

        if (!self::$messages[$type]) {
            self::$messages[$type] = array();
        }
        if (!self::$messages[$type][$field]) {
            self::$messages[$type][$field] = array();
        }
        self::$messages[$type][$field][] = $message;
    }

    /**
     * Retrieve all messages.
     * @return array List of messages
     */
    public static function getMessages() {
        if (!self::$requestMessagesRetrieved) {
            self::addMessagesFromRequest();
        }
        return self::$messages;
    }

    /**
     * Remove all messages from self::$messages.
     * @return void
     */
    public static function clearMessages() {
        self::$messages = array();
    }

    /**
     * Obtain any messages from url parameters and add to self::$messages.
     * @return void
     */
    protected static function addMessagesFromRequest() {
        self::$requestMessagesRetrieved = true;
        $messages = (func_num_args() === 1) ? func_get_arg(0) : Url::getParameter('messages'); // Allow unit testing
        if (!$messages) {
            return;
        }

        try {
            $messages = self::decodeMessages($messages);
        }
        catch (\Exception $e) {
            self::addError($e->getMessage(), 'REQUEST ERROR');
            return;
        }
        if (is_object($messages)) {
            $messages = array($messages);
        }
        if (is_array($messages)) {
            foreach($messages as $message) {
                try {
                    self::addMessage(htmlspecialchars($message->message, ENT_QUOTES, 'UTF-8'), $message->type, (property_exists($message, 'field') ? htmlspecialchars($message->field, ENT_QUOTES, 'UTF-8') : ''));
                }
                catch (\Exception $e) {
                    self::addError($e->getMessage(), 'REQUEST ERROR');
                }
            }
        }
    }

    /**
     * Return the specified $message and $type in an array.
     * @param string $message The message to display.
     * @param string $type The type of message, generally 'error' or 'info'.
     * @param string $field The field name, if applicable, under which messages will be grouped.
     * @param bool $encode Whether to encode the returned message.
     * @return mixed Array or string representation of the message, depending on `$encode` parameter.
     */
    protected static function getMessageArray($message, $type = 'error', $field = '', $encode = false) {
        $message = array('message' => $message, 'type' => $type, 'field' => $field);
        return ($encode) ? self::encodeMessages($message) : $message;
    }

    /**
     * Encodes $messages to be send in url parameters.
     * @param array $messages An array of messages.
     * @return string Encoded messages.
     */
    protected static function encodeMessages(array $messages) {
        return base64_encode(json_encode($messages));
    }

    /**
     * Decodes messages $hash
     * @param string $hash Encoded messages array.
     * @return array Decoded messages.
     * @throws \Exception If $hash is not a string
     */
    protected static function decodeMessages($hash) {
        if (!is_string($hash)) {
            throw new \Exception(Config::getMessage(INVALID_MESSAGES_HASH_LBL));
        }
        return json_decode(base64_decode($hash));
    }
}
