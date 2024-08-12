<?php
namespace RightNow\Utils;
use RightNow\Api,
    RightNow\Connect\v1_4 as ConnectPHP,
    RightNow\Libraries\Formatter as Formatter,
    RightNow\Utils\Date as DateUtil;

/**
 * Utility methods for handling server-side validation of form data
 */
final class Validation {
    /**
     * If given a Connect API constraint ID return the equivalent validation function in the `RightNow\Utils\Validation` namespace.
     * @param int|null $kind The Connect API constraint ID or null to retrieve the full list
     * @return string|array The string method name or a list of all constraints by ID
     */
    public static function getConstraintByID($kind = null) {
        static $constraints = array(
            0 => null,
            // @codingStandardsIgnoreStart
            ConnectPHP\Constraint::Min => 'minValue',
            ConnectPHP\Constraint::Max => 'maxValue',
            ConnectPHP\Constraint::MinLength => 'minLength',
            ConnectPHP\Constraint::MaxLength => 'maxLength',
            ConnectPHP\Constraint::MaxBytes => 'maxBytes',
            ConnectPHP\Constraint::In => 'in', // Validation not implemented
            ConnectPHP\Constraint::Not => 'not', // Validation not implemented
            ConnectPHP\Constraint::Pattern => 'regex',
            // @codingStandardsIgnoreEnd
            12 => 'maxOccurrences',
            14 => 'maxRepetitions',
            15 => 'minLowercaseChars',
            16 => 'minSpecialAndDigitChars',
            17 => 'minSpecialChars',
            18 => 'minUppercaseChars',
        );
        return $kind === null ? $constraints : (isset($constraints[$kind]) && $constraints[$kind] ? $constraints[$kind] : '');
    }

    /**
     * If given a constraint, return true if the constraint is supported, or false if it is not supported.
     * If not given a constraint, return the list of all valid constraints.
     * @param string|null $constraint The name of the constraint to check support
     * @return boolean|array True or false if the constraint is supported or the list of all constraints
     */
    public static function getSupportedConstraints($constraint = null) {
        static $validConstraints = array(
            'minValue',
            'maxValue',
            'minLength',
            'maxLength',
            'maxBytes',
            'regex',
            'maxOccurrences',
            'maxRepetitions',
            'minSpecialAndDigitChars',
            'minSpecialChars',
            'minLowercaseChars',
            'minUppercaseChars'
        );
        return $constraint === null ? $validConstraints : in_array($constraint, $validConstraints);
    }

    /**
     * Retrieves the various password configuration settings for contacts.
     * @param object $metaData Connect/_metadata instance for the Contact.NewPassword field
     * @param string|null $type The type of requirements to retrieve:
     *  'validations', 'expiration', or 'all'; defaults to 'validations'
     * @return array Contains either validation or expiration configuration keys and values
     *  or contains validation and expiration keys with sub-arrays as their values
     */
    public static function getPasswordRequirements($metaData, $type = 'validations') {
        static $keys = array(
            // @codingStandardsIgnoreStart
            ConnectPHP\Constraint::MinLength => array('key' => 'length', 'bounds' => 'min'),
            // @codingStandardsIgnoreEnd
            9  => array('key' => 'interval'),
            10 => array('key' => 'gracePeriod'),
            11 => array('key' => 'warningPeriod'),
            12 => array('key' => 'occurrences',      'bounds' => 'max'),
            13 => array('key' => 'old',              'bounds' => 'max'),
            14 => array('key' => 'repetitions',      'bounds' => 'max'),
            15 => array('key' => 'lowercase',        'bounds' => 'min'),
            16 => array('key' => 'specialAndDigits', 'bounds' => 'min'),
            17 => array('key' => 'special',          'bounds' => 'min'),
            18 => array('key' => 'uppercase',        'bounds' => 'min'),
        );
        static $expiration, $validations;

        if (!$validations) {
            $validations = $expiration = array();
            $constraints = $metaData->constraints;

            if (is_array($constraints)) {
                foreach ($constraints as $constraint) {
                    $requirement = isset($keys[$constraint->kind]) && $keys[$constraint->kind] ? $keys[$constraint->kind] : null;
                    if (isset($requirement['bounds']) && $requirement['bounds']) {
                        $validations[$requirement['key']] = array('bounds' => $requirement['bounds'], 'count' => $constraint->value);
                    }
                    else if(is_array($requirement)) {
                        $expiration[$requirement['key']] = $constraint->value;
                    }
                }
            }
        }
        if ($type === 'validations') {
            return $validations;
        }
        if ($type === 'expiration') {
            return $expiration;
        }
        return array('validations' => $validations, 'expiration' => $expiration);
    }

    /**
     * Validate a list of form fields (from Objects in \RightNow\Utils\Connect::getSupportedObjects) against both API constraints and the sent in constraints
     * @param array $formFields Array of fields to be validated, keyed by field name (e.g. Contact.Login). The field format is an object
     * with the following properties:
     *
     *      * name - The name of the field
     *      * value - The field's value
     *      * label - The field's label to be displayed in messages
     *      * required - Boolean true/false
     *      * constraints - An array of constraints. Note this list is merged with the API constraints and the most restrictive constraint is chosen.
     *          * key - A string from the list of supported constraints (getSupportedConstraints)
     *          * value - The value of the constraint to be enforced
     *      * requireCurrent - Boolean true/false, whether or not a Password field requires a current valid password
     *      * requireValidation - Boolean true/false, whether or not a Text field requires a validation field (common with passwords and emails)
     *      * labelValidation - The label to display in the requireValidation error message
     *
     * @param array &$errors A reference to an error array where errors will be appended
     * @param array &$warnings A reference to a warning array where warning will be appended
     * @return boolean True if validation succeeded, false otherwise. If the result of this function is submitted
     *                 to Connect both `Connect::checkAndStripMask` and `Connect::castValue` need to be performed first.
      */
    public static function validateFields(array $formFields, array &$errors = array(), array &$warnings = array()) {
        $fieldsValid = true;
        foreach($formFields as $name => $field) {
            $pieces = explode('.', $name);

            //If the API doesn't support writing on that object type, skip it.
            if(!$instance = Connect::getObjectInstance(ucfirst(strtolower($pieces[0])), true)) {
                continue;
            }

            try {
                list(, $fieldMetadata) = Connect::getObjectField($pieces, $instance);
            }
            catch(Connect\ConnectAPIErrorBase $e) {
                $warnings[] = $e->getMessage();
                continue;
            }

            if(!self::validate($field, $name, $fieldMetadata, $errors)) {
                $fieldsValid = false;
            }
        }
        return $fieldsValid;
    }

    /**
    * Validates the given field.
    * @param object $formField See `\RightNow\Utils\Validation::validateFields` for the complete structure
    * @param string $apiFieldName The field name that the API expects (e.g. Contact.Login)
    * @param object $fieldMetadata Connect/_metadata or KFAPI/_metadata object corresponding to the field
    * @param array &$errors Pass-by-reference array for population of error messages
    * @return bool True if validation succeeded, false if validation failed
    */
    public static function validate($formField, $apiFieldName, $fieldMetadata, array &$errors) {
        $fieldName = isset($formField->label) && $formField->label ? $formField->label : $fieldMetadata->name;

        //Trim any non-password fields
        $isPassword = self::isPassword($fieldMetadata->name);
        if(is_string($formField->value) && !$isPassword) {
            $formField->value = trim($formField->value);
        }
        if(Formatter::isFormatHTMLType($fieldMetadata->usageType)) {
            $stripFieldValue = strip_tags($formField->value, "<img>");
            $stripFieldValue = str_replace('&nbsp;', '', $stripFieldValue);
            // look for UTF-8 &nbsp;s (hex code A0)
            $stripFieldValue = preg_replace('/[\xA0\xC2]/', '', $stripFieldValue);
            if(trim($stripFieldValue) === '') {
                $formField->value = trim($stripFieldValue);
            }
        }
        //Check requiredness before any other validation
        $valueSpecified = ($formField->value !== '' && $formField->value !== null && $formField->value !== false);
        if((isset($formField->required) && $formField->required) && !$valueSpecified) {
            $errors[] = sprintf(Config::getMessage(PCT_S_IS_REQUIRED_MSG), $fieldName);
            return false;
        }

        if ($valueSpecified || ($isPassword && $formField->value !== null)) {
            if($typeError = self::checkDataType($formField->value, $fieldName, $apiFieldName, $fieldMetadata)) {
                $errors[] = $typeError;
                return false;
            }

            //At this point the only valid form value is a populated string (or an empty string if the field is a password field)
            $constraints = self::mergeConstraints(isset($formField->constraints) && $formField->constraints ? $formField->constraints : array(), $fieldMetadata->constraints ?: array());

            // Until Connect stops sending maxLength AND maxBytes, remove maxLength so users only see the one validation message.
            if (isset($constraints['maxBytes']) && $constraints['maxBytes'] && $constraints['maxLength']) {
                unset($constraints['maxLength']);
            }

            if($constraintErrors = self::checkConstraints($formField, $constraints, $fieldName)) {
                $errors = array_merge($errors, $constraintErrors);
                return false;
            }

            if($dataErrors = self::checkDataConsistency($formField, $fieldName, $apiFieldName, $fieldMetadata)) {
                $errors = array_merge($errors, $dataErrors);
                return false;
            }
        }
        return true;
    }

    /**
     * Given a set of field constraints merge it against the API constraints to get a complete
     * list of the constraints to be applied against the field. If there is any overlap
     * compare them (min/max) or combine them (regex) into an array.
     * @param array $constraints An array of given constraints for the field
     * @param array $apiConstraints An array of constraints from the Connect metadata
     * @return array List of merged constraints
     */
    private static function mergeConstraints(array $constraints, array $apiConstraints) {
        foreach($apiConstraints as $constraint) {
            if($key = self::getConstraintByID($constraint->kind)) {
                if(isset($constraints[$key] ) && $constraints[$key] && isset($constraint->value)) {
                    if(Text::beginsWith($key, 'min')) {
                        $constraints[$key] = (int)(($constraints[$key] > $constraint->value) ? $constraints[$key] : $constraint->value);
                    }
                    else if(Text::beginsWith($key, 'max')) {
                        $constraints[$key] = (int)(($constraints[$key] < $constraint->value) ? $constraints[$key] : $constraint->value);
                    }
                    else {
                        $constraints[$key] = array($constraints[$key], $constraint->value);
                    }
                }
                else if($constraint->value || ($key === 'minValue' && $constraint->value === 0)) {
                    $constraints[$key] = $constraint->value;
                }
            }
        }
        return $constraints;
    }

    /**
     * Check the data type of the form field and validate it if supported
     * @param string $value The field's value
     * @param string $label The field's label for messaging
     * @param string $apiFieldName The field name that the API expects (e.g. Contact.Login)
     * @param object $fieldMetadata The forms Connect PHP metadata
     * @return string|null If an error is encountered, the error string, otherwise null
     */
    private static function checkDataType($value, $label, $apiFieldName, $fieldMetadata) {
        $isCommonEmailType = in_array($apiFieldName, array('Contact.Emails.PRIMARY.Address', 'Contact.Emails.ALT1.Address', 'Contact.Emails.ALT2.Address'));
        $dataType = $fieldMetadata->COM_type;

        if($dataType === 'Integer') {
            $method = 'validInteger';
        }
        // @codingStandardsIgnoreStart
        else if($isCommonEmailType || ($fieldMetadata->usageType && $fieldMetadata->usageType === ConnectPHP\PropertyUsage::EmailAddress)) {
            $method = 'validEmail';
        }
        // @codingStandardsIgnoreEnd
        else if($apiFieldName === 'Incident.CustomFields.c.alternateemail') {
            $method = 'validEmailList';
        }
        else if($dataType === 'Date' || $dataType === 'DateTime') {
            $method = 'validDate';
        }
        else if($fieldMetadata->usageType && $fieldMetadata->usageType === ConnectPHP\PropertyUsage::URI) {
            $method = 'validUrl';
        }
        else if($apiFieldName === 'Contact.Login') {
            $method = 'validLogin';
        }

        return (isset($method) && $method) ? call_user_func(array(self::class, $method), $value, $label) : null;
    }

    /**
     * Validates the field against its constraints.
     * @param object $formField Should have a 'value' property
     * @param array $constraints List of constraints
     * @param string $fieldName The field's name
     * @return array An array of error messages for the given formField and meta data
     */
    private static function checkConstraints($formField, array $constraints, $fieldName) {
        //Filter out any invalid constraints
        $constraints = array_intersect_key($constraints, array_flip(self::getSupportedConstraints()));

        foreach ($constraints as $constraint => $constraintValue) {
            if($constraintValue !== null) {
                if(!is_array($constraintValue)) {
                    $constraintValue = array($constraintValue);
                }

                //Look over all of the constraint values and look for errors
                foreach($constraintValue as $eachConstraint) {
                    if($error = call_user_func(array(self::class, $constraint), $formField->value, $eachConstraint, $fieldName)) {
                        $errors[] = $error;
                    }
                }
            }
        }
        return isset($errors) && $errors ? $errors : array();
    }

    /**
     * Check validation that needs to be performed at a higher level than the standard API constraints
     * @param object $field A reference to the field object. This object's value property can be altered if the field needs a mask removed
     * @param string $label The field's label displayed in messages
     * @param string $apiFieldName The API field name (e.g. Contact.Login)
     * @param object $fieldMetadata The Connect PHP metadata for the given field
     * @return array An array of error messages
     */
    private static function checkDataConsistency($field, $label, $apiFieldName, $fieldMetadata) {
        $errors = array();

        //Validate masks
        if($mask = Connect::getMask(Text::getSubstringBefore($apiFieldName, '.'), $apiFieldName, $fieldMetadata)) {
            if($maskErrors = self::validMask($field->value, $mask, $label)) {
                $errors += $maskErrors;
            }
        }

        //If a field has a validation value, ensure it and the default value match
        if(isset($field->requireValidation) && $field->requireValidation && $field->value !== $field->validation) {
            $errors[] = sprintf(Config::getMessage(DOES_NOT_MATCH_PCT_S_LBL), $label, $field->labelValidation ?: ($label . Config::getMessage(VALIDATION_UC_LBL)));
        }

        return $errors;
    }

    /**
     * Validate mask constraint.
     * @param mixed $value The value being validated.
     * @param string $mask The mask used to validate the value.
     * @param string $fieldName The field's name
     * @return array|null An array of error messages or null if all constraints were satisfied
     */
    protected static function validMask($value, $mask, $fieldName) {
        if ($errors = Text::validateInputMask($value, $mask)) {
            try {
                array_unshift($errors, Config::getMessage(EXPECTED_INPUT_COLON_LBL) . ' ' . Text::getSimpleMaskString($mask));
            }
            catch (\Exception $e) {
                // Invalid mask
            }
            foreach($errors as &$error) {
                $error = $fieldName . ' ' . $error;
            }

            // Decided in team meeting to restrict mask errors to the count of 2 to avoid a long list. Incident ref# 130307-000082.
            return array_slice($errors, 0, 2);
        }
    }

    /**
     * Validate email data type constraint.
     * @param mixed $value The value to validate.
     * @param string $fieldName Field's name
     * @return string|null Error message or null if all constraints were satisfied
     */
    protected static function validEmail($value, $fieldName) {
        if(!Text::isValidEmailAddress($value)) {
            return sprintf(Config::getMessage(PCT_S_IS_NOT_A_VALID_EMAIL_MSG), $fieldName);
        }
    }

    /**
     * Validate comma/semi colon separated email list. Return error message if any token in the list is not a valid email.
     * @param string $value The value to validate.
     * @param string $fieldName Field's name
     * @return string|null Error message or null if all constraints were satisfied
     */
    protected static function validEmailList($value, $fieldName) {
        $emailList = preg_split("/[,;]/", $value);
        foreach($emailList as $email){
            if(!Text::isValidEmailAddress(trim($email))) {
                return sprintf(Config::getMessage(PCT_S_IS_NOT_A_VALID_EMAIL_MSG), $fieldName);
            }
        }
    }

    /**
     * Validate the contact login
     * @param mixed $value The value to validate.
     * @param string $fieldName Field's name
     * @return string|null Error message or null if all constraints were satisfied
     */
    protected static function validLogin($value, $fieldName) {
        if($value && !preg_match("@^[^\"<>\s]*$@", html_entity_decode($value))) {
            return sprintf(Config::getMessage(PCT_S_CONT_SPACES_DOUBLE_QUOTES_LBL), $fieldName);
        }
    }

    /**
     * Validate URL data type constraint.
     * @param mixed $value The value to validate.
     * @param string $fieldName Field's name
     * @return string|null Error message or null if all constraints were satisfied
     */
    protected static function validUrl($value, $fieldName) {
        if(!Text::isValidUrl($value)) {
            return sprintf(Config::getMessage(PCT_S_IS_NOT_A_VALID_URL_MSG), $fieldName);
        }
    }

    /**
     * Validate integer data type constraint.
     * @param mixed $value The value to validate.
     * @param string $fieldName Field's name
     * @return string|null Error message or null if all constraints were satisfied
     */
    protected static function validInteger($value, $fieldName) {
        if(!is_numeric($value) || (!is_int(intval($value)) && $value !== (string) intval($value))) {
            return sprintf(Config::getMessage(PCT_S_MUST_BE_AN_INTEGER_MSG), $fieldName);
        }
    }

    /**
     * Validate date data type constraint.
     * @param mixed $value The value to validate.
     * @param string $fieldName Field's name
     * @return string|null Error message or null if all constraints were satisfied
     */
    protected static function validDate($value, $fieldName) {
        try {
            if(is_numeric($value)) {
                new \DateTime('@' . $value);
            }
            else if(($validDate = Text::isValidDate($value)) !== null && !$validDate) {
                return sprintf(Config::getMessage(PCT_S_IS_NOT_A_VALID_DATE_MSG), $fieldName);
            }
            else {
                new \DateTime($value);
            }
        }
        catch (\Exception $e) {
            return sprintf(Config::getMessage(PCT_S_IS_NOT_COMPLETELY_FILLED_IN_MSG), $fieldName);
        }
    }

    /**
    * Validates the field for a matching regular expression
    * @param string $value Field's value
    * @param string $regex Field's constraint
    * @param string $fieldName Field's name
    * @return string|null Error message or null if all constraints were satisfied
    */
    public static function regex($value, $regex, $fieldName) {
        /*
            Use forward-slash as start/end delimiter and escape any instances within the pattern.
            Translate the \w charater class, i.e. "word" characters, to match only ASCII "word" characters.
            The \w class is locale dependent, meaning when we're running in a German locale, umlauts would
            magically become valid in email addresses. PHP itself had a bug on exactly the same thing:
            http://bugs.php.net/47598
            http://cvs.php.net/viewvc.cgi/php-src/ext/filter/logical_filters.c?r1=1.1.2.29&r2=1.1.2.30&diff_format=u
        */
        $regex = '/' . str_replace('\w', '0-9A-Za-z_', str_replace('/', '\/', $regex)) . '/';
        if (!preg_match($regex, $value)) {
            return sprintf(Config::getMessage(PCT_S_CONTS_INVALID_CHAR_S_LBL), $fieldName);
        }
    }

    /**
    * Validates the field for a minimum length
    * @param string $value Field's value
    * @param int $minLength Field's constraint
    * @param string $fieldName Field's name
    * @return string|null Error message or null if constraint was satisfied
    */
    public static function minLength($value, $minLength, $fieldName) {
        try
        {
            $fieldLength = Text::getMultibyteStringLength($value);
        }
        catch (\Exception $e)
        {
            $value = Api::utf8_cleanse($value);

            try
            {
                $fieldLength = Text::getMultibyteStringLength($value);
            }
            catch (\Exception $e)
            {
                // I give up
                $value = '';
            }
        }
        if ($fieldLength >= 0 && $fieldLength < $minLength) {
            $message = ($minLength - $fieldLength === 1) ?
                Config::getMessage(PCT_S_CONTAIN_1_CHARACTER_MSG) :
                sprintf(Config::getMessage(PCT_S_CONTAIN_PCT_D_CHARACTERS_MSG), '%s', $minLength - $fieldLength);
            return sprintf($message, $fieldName);
        }
    }

    /**
    * Validates the field for a maximum length
    * @param string $value Field's value
    * @param int $maxLength Field's constraint
    * @param string $fieldName Field's name
    * @return string|null Error message or null if all constraints were satisfied
    */
    public static function maxLength($value, $maxLength, $fieldName) {
        try
        {
            $fieldLength = Text::getMultibyteStringLength($value);
        }
        catch (\Exception $e)
        {
            $value = Api::utf8_cleanse($value);

            try
            {
                $fieldLength = Text::getMultibyteStringLength($value);
            }
            catch (\Exception $e)
            {
                // I give up
                $value = '';
            }
        }
        if ($fieldLength > 0 && $fieldLength > $maxLength) {
            if (self::isPassword($fieldName)) {
                return sprintf(Config::getMessage(PASSWD_ENTERED_EXCEEDS_MAX_CHARS_MSG), 20);
            }
            if (self::isDisplayName($fieldName)) {
                return sprintf(Config::getMessage(DISPLYNM_NT_MX_CHRS_CHRS_XP_SBM_SZ_XCDD_MSG), 80);
            }
            if ($fieldLength - $maxLength === 1) {
                return sprintf(Config::getMessage(PCT_S_EXCEEDS_SZ_LIMIT_PCT_D_CHARS_LBL), $fieldName, $maxLength);
            }
            return sprintf(Config::getMessage(PCT_S_EXS_SZ_LIMIT_PCT_D_CHARS_LBL), $fieldName, $maxLength, $fieldLength - $maxLength);
        }
    }

    /**
    * Validates the field for maximum byte length
    * @param string $value Field's value
    * @param int $maxBytes Field's constraint
    * @param string $fieldName Field's name
    * @return string|null Error message or null if max bytes constraint was satisfied
    */
    public static function maxBytes($value, $maxBytes, $fieldName) {
        if (($byteLength = strlen($value)) && $byteLength > $maxBytes) {
            return sprintf(Config::getMessage(PCT_S_EXCEEDS_SZ_LIMIT_PLS_SHORTEN_MSG), $fieldName);
        }
    }

    /**
    * Validates the field for a minimum value
    * @param string $value Field's value
    * @param int $minValue Field's constraint
    * @param string $fieldName Field's name
    * @return string|null Error message or null if all constraints were satisfied
    */
    public static function minValue($value, $minValue, $fieldName) {
        //The incoming value is either an integer (for an integer field), a date string (YYYY-MM-DD [HH:MM:SS]) or a timestamp.
        if (!is_numeric($value)) {
            // watch out for invalid date values
            if (DateUtil::canParse($value, $minValue)
                && DateUtil::getDateObject($value) < DateUtil::getDateObject($minValue)) {
                $addTime = (Text::getSubstringAfter($value, ' ') !== false);
                return sprintf(Config::getMessage(PCT_S_VALUE_MIN_VALUE_PCT_S_MSG), $fieldName, self::getDateString($minValue, $addTime));
            }
        }
        else if ($value < $minValue) {
            return sprintf(Config::getMessage(PCT_S_VAL_MIN_VAL_PCT_D_COLON_MSG), $fieldName, $minValue);
        }
    }

    /**
    * Validates the field for a maximum value
    * @param string $value Field's value
    * @param int $maxValue Field's constraint
    * @param string $fieldName Field's name
    * @return string|null Error message or null if all constraints were satisfied
    */
    public static function maxValue($value, $maxValue, $fieldName) {
        //The incoming value is either an integer (for an integer field), a date string (YYYY-MM-DD [HH:MM:SS]) or a timestamp.
        if (!is_numeric($value)) {
            // watch out for invalid date values
            if (DateUtil::canParse($value, $maxValue)
                && DateUtil::getDateObject($value) > DateUtil::getDateObject($maxValue)) {
                $addTime = (Text::getSubstringAfter($value, ' ') !== false);
                return sprintf(Config::getMessage(PCT_S_VAL_MAX_VAL_PCT_S_COLON_MSG), $fieldName, self::getDateString($maxValue, $addTime));
            }
        }
        else if ($value > $maxValue) {
            return sprintf(Config::getMessage(PCT_S_VAL_MAX_VAL_PCT_D_COLON_MSG), $fieldName, $maxValue);
        }
    }

    /**
    * Validates that a field contains under `$maxValue` occurrences of any character
    * @param string $value Field's value
    * @param int $maxValue Field's constraint
    * @param string $fieldName Field's name
    * @return string|null Error message or null if constraint was satisfied
    */
    public static function maxOccurrences($value, $maxValue, $fieldName) {
        $counts = array();
        foreach(Text::getMultibyteCharacters($value) as $char) {
            if(!$counts[$char]) $counts[$char] = 0;

            if(++$counts[$char] > $maxValue) {
                return ($maxValue === 1) ? sprintf(Config::getMessage(PCT_S_REPEATED_CHARS_LBL), $fieldName) : sprintf(Config::getMessage(PCT_S_CONTAIN_PCT_D_REPEATED_CHARS_LBL), $fieldName, $maxValue);
            }
        }
    }

    /**
    * Validates the field constraint under `$maxValue` repetitions of any character
    * @param string $value Field's value
    * @param int $maxValue Field's constraint
    * @param string $fieldName Field's name
    * @return string|null Error message or null if constraint was satisfied
    */
    public static function maxRepetitions($value, $maxValue, $fieldName) {
        foreach(Text::getMultibyteCharacters($value) as $char) {
            if($lastChar === $char) {
                if(++$reps > $maxValue) {
                    return ($maxValue === 1) ? sprintf(Config::getMessage(PCT_S_CHAR_REPEATED_ROW_LBL), $fieldName) : sprintf(Config::getMessage(PCT_S_CNT_PCT_D_REPEATED_CHARS_LBL), $fieldName, $maxValue);
                }
            }
            else {
                $lastChar = $char;
                $reps = 1;
            }
        }
    }

    /**
    * Validates the field for a minimum number of lowercase characters
    * @param string $value Field's value
    * @param int $minChars Field's constraint
    * @param string $fieldName Field's name
    * @return string|null Error message or null if constraint was satisfied
    */
    public static function minLowercaseChars($value, $minChars, $fieldName) {
        return self::getErrorByType($value, $minChars, $fieldName, 'lowercase');
    }

    /**
    * Validates the field for a minimum number of uppercase characters
    * @param string $value Field's value
    * @param int $minChars Field's constraint
    * @param string $fieldName Field's name
    * @return string|null Error message or null if constraint was satisfied
    */
    public static function minUppercaseChars($value, $minChars, $fieldName) {
        return self::getErrorByType($value, $minChars, $fieldName, 'uppercase');
    }

    /**
    * Validates the field for a minimum number of special characters
    * @param string $value Field's value
    * @param int $minChars Field's constraint
    * @param string $fieldName Field's name
    * @return string|null Error message or null if constraint was satisfied
    */
    public static function minSpecialChars($value, $minChars, $fieldName) {
        return self::getErrorByType($value, $minChars, $fieldName, 'special');
    }

    /**
    * Validates the field for a minimum number of special characters and digits
    * @param string $value Field's value
    * @param int $minChars Field's constraint
    * @param string $fieldName Field's name
    * @return string|null Error message or null if constraint was satisfied
    */
    public static function minSpecialAndDigitChars($value, $minChars, $fieldName) {
        return self::getErrorByType($value, $minChars, $fieldName, 'specialAndDigit');
    }

    /**
    * Generalization of the minimum constraint functions
    * @param string $value Field's value
    * @param int $minChars Field's constraint
    * @param string $fieldName Field's name
    * @param string $checkType The type of constraint to check 'uppercase', 'lowercase', 'special', 'specialAndDigit'
    * @return string|null Error message or null if constraint was satisfied
    */
    private static function getErrorByType($value, $minChars, $fieldName, $checkType) {
        $checkTypes = array(
            'uppercase' => array(
                'method' => function($char) { return ctype_upper($char); },
                'singular' => Config::getMessage(PCT_S_CONT_PCT_D_UPPER_CHAR_MSG),
                'plural' => Config::getMessage(PCT_S_CONT_PCT_D_UPPER_CHARS_MSG)
            ),
            'lowercase' => array(
                'method' => function($char) { return ctype_lower($char); },
                'singular' => Config::getMessage(PCT_S_CONT_PCT_D_LOWER_CHAR_MSG),
                'plural' => Config::getMessage(PCT_S_CONT_PCT_D_LOWER_CHARS_MSG)
            ),
            'special' => array(
                'method' => function($char) { return ctype_punct($char) || ctype_space($char); },
                'singular' => Config::getMessage(PCT_S_CONT_PCT_D_SPECIAL_CHAR_MSG),
                'plural' => Config::getMessage(PCT_S_CONT_PCT_D_SPECIAL_CHARS_MSG)
            ),
            'specialAndDigit' => array(
                'method' => function($char) { return ctype_digit($char) || ctype_punct($char) || ctype_space($char); },
                'singular' => Config::getMessage(PCT_S_CNT_PCT_D_MSG),
                'plural' => Config::getMessage(PCT_S_CONT_PCT_D_NUMS_MSG)
            )
        );

        if(!$check = $checkTypes[$checkType]) return;

        $count = 0;
        foreach(Text::getMultibyteCharacters($value) as $char) {
            $count += $check['method']($char);
        }

        if($count < $minChars) {
            return sprintf(($minChars === 1) ? $check['singular'] : $check['plural'], $fieldName, $minChars);
        }
    }

    /**
     * Return an internationally adjusted date string
     * @param string $date The date to be transformed
     * @param boolean $addTime If true, append the time to the date string.
     * @return string Adjusted string
     */
    private static function getDateString($date, $addTime = false) {
        if(is_string($date))
            $dt = new \DateTime("$date");
        else
            $dt = new \DateTime("@$date");
        $dt->setTimeZone(new \DateTimeZone(Config::getConfig(TZ_INTERFACE)));
        list($month, $day, $year, $time) = explode(' ', $dt->format('m d Y H:i:s'));
        $month = intval($month);
        $day = intval($day);
        $dateOrder = Config::getConfig(DTF_INPUT_DATE_ORDER);
        $dateString = ($dateOrder === 0) ? "$month/$day/$year" : (($dateOrder === 1) ? "$year/$month/$day" : "$day/$month/$year");
        if ($addTime) {
            $dateString .= " $time";
        }
        return $dateString;
    }

    /**
     * Returns true if $fieldName is a password field.
     * @param string $fieldName The name of the field.
     * @return boolean True if $fieldName is a password field.
     */
    private static function isPassword($fieldName) {
        return Text::stringContains($fieldName, 'NewPassword');
    }

    /**
     * Returns true if $fieldName is the DisplayName field.
     * @param string $fieldName The name of the field.
     * @return boolean True if $fieldName is DisplayName field.
     */
    private static function isDisplayName($fieldName) {
        return Text::stringContains($fieldName, 'DisplayName');
    }
}
