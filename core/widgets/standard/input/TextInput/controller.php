<?php
namespace RightNow\Widgets;

use RightNow\Utils\Connect,
    RightNow\Utils\Framework,
    RightNow\Libraries\AbuseDetection;

class TextInput extends \RightNow\Libraries\Widget\Input {
    function __construct($attrs) {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'existing_contact_check_ajax' => array(
                'method' => 'existingContactCheck',
                'exempt_from_login_requirement' => true
            )
        ));
    }

    function getData() {
        if (($clientLoader = $this->CI->clientLoader) && !$clientLoader->isJavaScriptModuleNone()) {
            $clientLoader->addJavaScriptInclude(\RightNow\Utils\Url::getCoreAssetPath('thirdParty/js/Markdown.Converter.min.js'));
        }

        if (parent::getData() === false) return false;
        if (!in_array($this->dataType, array('String', 'Integer', 'Thread', 'Comment'))) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(DATA_TYPE_PCT_S_APPR_TEXT_INPUT_MSG), $this->fieldName));
            return false;
        }

        if ($this->fieldName === 'DisplayName' && $this->table === 'Communityuser' && get_class($this) === 'RightNow\\Widgets\\TextInput') {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(WIDGET_CANNOT_USED_S_FIELD_USE_INSTEAD_MSG), $this->table, $this->fieldName, 'input/DisplayNameInput'));
            return false;
        }

        //Certain Connect objects (e.g. Incident.Threads) are objects even when they have no value. Since we're displaying
        //the data within a text input box, it needs to be a string, so convert it to one. As we can't generically convert objects
        //into strings since we have no idea which fields to display instead, just convert things into an empty string.
        if(is_object($this->data['value'])){
            $this->data['value'] = '';
        }

        $displayType = $this->data['displayType'] = $this->determineDisplayType($this->data['inputName'], $this->dataType, $this->constraints);
        if (isset($this->data['attrs']['textarea']) && $this->data['attrs']['textarea']) {
            if ($displayType === 'Number' || $displayType === 'Email'
                || ($displayType === 'Text' && Connect::isCustomField($this->fieldMetaData))
                || (($regex = $this->constraints['regex']) && \RightNow\Utils\Validation::regex("a\nb", $regex, $this->fieldName))) {
                   echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(PCT_S_SUPPORT_PCT_S_ATTRIB_MSG), $this->fieldName, 'textarea'));
                   return false;
            }
            $displayType = $this->data['displayType'] = 'Textarea';
        }
        $this->data['inputType'] = strtolower($displayType);

        if($displayType === "Number" && ($this->data['attrs']['maximum_length'] > 0 || $this->data['attrs']['minimum_length'] > 0)){
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(FLD_PCT_S_INT_FLD_MAX_VAL_MIMIMUM_MSG), $this->fieldName));
            return false;
        }
        if($displayType !== "Number" && (isset($this->data['attrs']['maximum_value']) || isset($this->data['attrs']['minimum_value']))){
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(FLD_PCT_S_INT_FLD_MAX_LNG_MIMIMUM_MSG), $this->fieldName));
            return false;
        }

        $this->data['readOnly'] = $this->data['js']['readOnly'] = $this->data['readOnly'] || (isset($this->data['attrs']['read_only']) && $this->data['attrs']['read_only'] ? $this->data['attrs']['read_only'] : '');

        if (!$this->data['readOnly']) {
            // Since Connect gives us 'maxBytes' and 'maxLength', treat them equally here so we don't unecessarily limit a blob field to its smaller character length.
            if ($maxLength = intval(isset($this->constraints['maxBytes']) && $this->constraints['maxBytes'] ? $this->constraints['maxBytes'] : (isset($this->constraints['maxLength']) ? $this->constraints['maxLength'] : 0 ))) {
                $this->constraints['maxLength'] = $this->data['js']['constraints']['maxLength'] = $maxLength;
            }

            //Only set the max length of the attribute if it's less than the DB required max length
            if(isset($this->data['attrs']['maximum_length']) && $this->data['attrs']['maximum_length'] > 0){
                $maxLength = $this->constraints['maxLength'] = $this->data['js']['constraints']['maxLength'] = $this->data['constraints']['maxLength'] = ($maxLength > 0 ? min($maxLength, $this->data['attrs']['maximum_length']) : $this->data['attrs']['maximum_length']);
            }
            //If a minimum length is set, that also means the user has to input some content, thereby making it required
            if(isset($this->data['attrs']['minimum_length']) && $this->data['attrs']['minimum_length'] > 0){
                if($maxLength > 0 && ($this->data['attrs']['minimum_length'] > $maxLength)){
                    echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(FLD_PCT_S_MNIMUM_LNG_PCT_D_MAX_LNG_MSG), $this->fieldName, $this->data['attrs']['minimum_length'], $maxLength));
                    return false;
                }
                $this->constraints['minLength'] = $this->data['constraints']['minLength'] = $this->data['js']['constraints']['minLength'] = $this->data['attrs']['minimum_length'];
                $this->data['attrs']['required'] = $this->data['constraints']['required'] = true;
            }

            if(isset($this->data['attrs']['maximum_value'])){
                $this->constraints['maxValue'] = (isset($this->constraints['maxValue'])) ? min($this->constraints['maxValue'], $this->data['attrs']['maximum_value']) : $this->data['attrs']['maximum_value'];
                $this->data['js']['constraints']['maxValue'] = $this->data['constraints']['maxValue'] = $this->constraints['maxValue'];
            }

            if(isset($this->data['attrs']['minimum_value'])){
                $this->constraints['minValue'] = (isset($this->constraints['minValue'])) ? max($this->constraints['minValue'], $this->data['attrs']['minimum_value']) : $this->data['attrs']['minimum_value'];
                if(isset($this->constraints['maxValue']) && ($this->data['attrs']['minimum_value'] > $this->constraints['maxValue'])){
                    echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(FLD_PCT_S_MINIMUM_VAL_PCT_D_MAX_VAL_MSG), $this->fieldName, $this->data['attrs']['minimum_value'], $this->constraints['maxValue']));
                    return false;
                }
                $this->data['js']['constraints']['minValue'] = $this->data['constraints']['minValue'] = $this->constraints['minValue'];
            }
        }

        if(isset($this->data['attrs']['require_validation']) && $this->data['attrs']['require_validation']) {
            if($this->data['attrs']['textarea'] || $displayType === 'Textarea') {
                $this->data['constraints']['requireValidation'] = false;
                $this->data['attrs']['require_validation'] = false;
            }
            else {
                $this->data['constraints']['requireValidation'] = true;
            }
        }

        //Standard Field
        if(!Connect::isCustomField($this->fieldMetaData)) {
            if ($this->fieldName === 'NewPassword') {
                echo $this->reportError(\RightNow\Utils\Config::getMessage(PASSWD_FLDS_REQUIRE_PASSWORDINPUT_MSG));
                return false;
            }
            //Error if using alt first/last name fields when not on Japanese site
            if ($this->fieldName === 'NameFurigana' && LANG_DIR !== 'ja_JP') {
                echo $this->reportError(\RightNow\Utils\Config::getMessage(ALT_FIRST_NAME_ALT_LAST_NAME_FLDS_MSG));
                return false;
            }
            //Prepopulate email address field if it does not already have a value and was set on a previous email input
            if(\RightNow\Utils\Text::stringContainsCaseInsensitive($this->data['inputName'], 'Emails.Primary') && !$this->data['value'] && ($previouslySeen = $this->CI->session->getSessionData('previouslySeenEmail'))) {
                $this->data['value'] = $previouslySeen;
            }

            if (isset($this->data['attrs']['validate_on_blur']) && $this->data['attrs']['validate_on_blur'] === true)
                $this->data['js']['previousValue'] = $this->data['value'];
        }

        if (isset($this->data['js']['mask'])) {
            $this->data['maskedValue'] = $this->data['value'];
            $this->data['value'] = \RightNow\Libraries\Formatter::applyMask($this->data['value'], $this->data['js']['mask']);
        }

        $this->data['js']['contactToken'] = \RightNow\Utils\Framework::createTokenWithExpiration(1, false, false);
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }

    /**
     * Returns HTML attributes for an input element based on the field's
     * constraints.
     * @return string HTML attributes
     */
    public function outputConstraints() {
        $attributes = '';
        if (isset($this->constraints['maxLength']) && $maxLength = $this->constraints['maxLength']) {
            $attributes .= "maxlength='{$maxLength}' ";
        }
        if (array_key_exists('maxValue', $this->constraints)) {
            $attributes .= "max='{$this->constraints['maxValue']}' ";
        }
        if (array_key_exists('minValue', $this->constraints)) {
            $attributes .= "min='{$this->constraints['minValue']}' ";
        }
        if ($this->data['attrs']['required']) {
            $attributes .= "required ";
        }
        if ($this->data['inputName'] === 'Contact.Login') {
            $attributes .= "autocorrect='off' autocapitalize='off' ";
        }
        return trim($attributes);
    }

    /**
     * Determines whether a contact with the given email or login already exists.
     * Similar to /ci/ajaxRequest/checkForExistingContact except it can validate usernames as well.
     * Always returns false unless the widget attribute validate_on_blur is set.
     *
     * Uses the following POST data values
     * @param array|null $constraints Field's contraints
     *   checkForChallenge boolean Used to trigger defenses when abuse is detected
     *   contactToken string CSRF form token
     *   email string Email address to check
     *   login string username to check - only used if $POST['email'] is absent
     * @return string HTML attributes
     */
    public function existingContactCheck($constraints) {
        if(isset($constraints['checkForChallenge']) && $constraints['checkForChallenge']){
            AbuseDetection::check();
        }

        // Since this is called in a blur handler, return false instead of throwing a CAPTCHA if
        // we see abuse or if the validate_on_blur attribute is not set (since that attribute
        // is false by default, this endpoint is secured by default)
        else if (AbuseDetection::isAbuse() || $this->data['attrs']['validate_on_blur'] !== true) {
            Framework::writeContentWithLengthAndExit(json_encode(false), 'application/json');
        }

        $token = $constraints['contactToken'];
        if(Framework::isValidSecurityToken($token, 1) === false){
            $this->renderJSON(false);
            return;
        }
        if(isset($constraints['email']) && $email = $constraints['email'])
        {
            $paramType = 'email';
            $param = $email;
        }
        else if($login = $constraints['login'])
        {
            $paramType = 'login';
            $param = $login;
        }
        $results = $this->CI->model('Contact')->contactAlreadyExists($paramType, $param, false)->result;
        $this->renderJSON($results);
    }

    /**
     * Determines the type of input for the field.
     * @param string $fieldName Field name
     * @param string $dataType Data type of the field
     * @param array|null $constraints Field's contraints
     * @return string One of Email|Url|Number|Textarea|Text
     */
    protected function determineDisplayType($fieldName, $dataType, $constraints) {
        if (\RightNow\Utils\Text::beginsWith($fieldName, 'Contact.Emails.') || (isset($this->data['js']['email']) && $this->data['js']['email'])) {
            return 'Email';
        }
        if (isset($this->data['js']['url']) && $this->data['js']['url']) {
            return 'Url';
        }
        if ($dataType === 'Integer') {
            return 'Number';
        }
        if ($dataType === 'Thread' || $dataType === 'Comment') {
            return 'Textarea';
        }
        if ($constraints) {
            foreach ($constraints as $name => $constraint) {
                if ($name === 'maxLength' && $constraint <= 300) {
                    return 'Text';
                }
            }
        }

        return 'Textarea';
    }
}
