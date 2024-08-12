<?php
namespace RightNow\Widgets;

use RightNow\Utils\Connect;

class PasswordInput extends \RightNow\Libraries\Widget\Input {
    const MAX_SIZE = 20;

    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (parent::getData() === false) return false;
        if ($this->fieldName !== 'NewPassword') {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(DATA_TYPE_PCT_S_APPR_PASSWD_INPUT_MSG), $this->fieldName));
            return false;
        }

        //Don't display password fields if they aren't enabled or the field is marked as read only (logged in via PTA, on chat launch page, etc)
        if (!\RightNow\Utils\Config::getConfig(EU_CUST_PASSWD_ENABLED) || $this->data['readOnly'])
            return false;

        \RightNow\Utils\Url::redirectIfPageNeedsToBeSecure();

        unset($this->constraints);

        if ($validations = $this->getValidations()) {
            $this->data['attrs']['required'] || ($this->data['attrs']['required'] = !is_null($validations['length']));
            $this->data['js']['requirements'] = $validations;
        }
        if($this->data['attrs']['require_current_password']) {
            $this->data['constraints']['requireCurrent'] = true;
        }
        if($this->data['attrs']['require_validation']) {
            $this->data['constraints']['requireValidation'] = true;
        }
    }

    /**
     * Retrieves all of the validation requirements set via
     * the Contact Password Configuration tool.
     * @return Array Empty if no validations, associative array if validations:
     *  - validationName:
     *    - bounds: string (min or max)
     *    - count:  int (number of characters required)
     *    - label:  string (validation label)
     */
    function getValidations() {
        $validations = \RightNow\Utils\Validation::getPasswordRequirements($this->fieldMetaData);
        $validationsToPerform = array();

        foreach ($validations as $name => $validation) {
            if (!$validation['count'] || $name === 'old' ||
                (($name === 'repetitions' || $name === 'occurrences') && $validations['length'] && $validations['length']['count'] && $validation['count'] > $validations['length']['count'])) continue;
            // Only include validations with actual counts.
            // Don't validate against old passwords on the client.
            // Don't display confusing max-occurrences & max-repetitions if
            // their requirement is greater than the required length
            $validationsToPerform[$name] = $validation;
            $validationsToPerform[$name]['label'] = self::getValidationLabel($name, $validation['count']);
        }

        return $validationsToPerform;
    }

    /**
     * Retrieves the label attribute for the given validation.
     * NOTE: If any of the `label_*_char` or `label_*_chars` attribute
     * names are modified in info.yml then this method must be modified.
     * @param string $name Validation name
     * @param int $number The number of characters the validation requires
     * @return string|null The label string or null if not found
     */
    function getValidationLabel($name, $number) {
        switch ($name) {
            case 'length':
                $attributeName = 'min_length';
                break;
            case 'occurrences':
                $attributeName = 'occurring';
                break;
            case 'repetitions':
                $attributeName = 'repetition';
                break;
            case 'specialAndDigits':
                $attributeName = 'special_digit';
                break;
            default:
                $attributeName = $name;
                break;
        }

        if ($label = $this->data['attrs']["label_{$attributeName}_char" . (($number > 1) ? 's' : '')]) {
            return sprintf($label, $number);
        }
    }

    /**
     * View helper that outputs the input field's constraints.
     * @return String HTML attributes
     */
    function outputConstraints() {
        $attributes = '';
        $max = self::MAX_SIZE; // String interpolation doesn't work w/ `self` :-{(
        $attributes .= "maxlength='{$max}'";
        if ($this->data['attrs']['required']) {
            $attributes .= ' required';
        }
        if($this->data['attrs']['disable_password_autocomplete']){
            $attributes .= ' autocomplete="off"';
        }
        return $attributes;
    }
}