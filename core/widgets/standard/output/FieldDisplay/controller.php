<?php
namespace RightNow\Widgets;

use RightNow\Utils\Connect,
    RightNow\Libraries\Formatter;

class FieldDisplay extends \RightNow\Libraries\Widget\Output {
    protected $validObjectTypes = array('Menu', 'Country', 'NamedID', 'SlaInstance', 'Asset');

    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if(parent::getData() === false) {
            return false;
        }

        $value = $this->data['value'];
        $valueType = $this->getValueType($value, $this->fieldMetaData);
        if (!$this->fieldShouldBeDisplayed($value, $valueType)) {
            return false;
        }

        if (!$this->fieldIsValid($value, $valueType)) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(FLD_PCT_S_COMPLEX_DISPLAYED_MSG), $this->data['attrs']['name']));
            return false;
        }

        $this->data['value'] = Formatter::formatField($value, $this->fieldMetaData, $this->data['attrs']['highlight']);

        if (isset($this->data['mask']) && $this->data['mask']) {
            $this->data['value'] = Formatter::applyMask($this->data['value'], $this->data['mask']);
        }

        // Set up label-value justification
        $this->data['wrapClass'] = ($this->data['attrs']['left_justify']) ? ' rn_LeftJustify' : '';
    }

    /**
     * Returns the type of $value
     * @param mixed $value The value of $this->data['value']
     * @param object $fieldMetaData The value of $this->fieldMetaData
     * @return mixed The type indicated by $value and $fieldMetaData;
     */
    function getValueType($value, $fieldMetaData) {
        if ($fieldMetaData->is_menu) {
            return 'Menu';
        }
        if (Connect::isCountryType($value) && !Connect::isCustomAttribute($fieldMetaData) ) {
            return 'Country';
        }
        if (Connect::isNamedIDType($value)) {
            return 'NamedID';
        }
        if (Connect::isSlaInstanceType($value)) {
            return 'SlaInstance';
        }
        if (Connect::isAssetType($value)) {
            return 'Asset';
        }
        return $fieldMetaData->COM_type;
    }

    /**
     * Returns true if the field should be displayed
     * @param mixed $value The value of $this->data['value']
     * @param string $valueType The type indicated by $value
     * @return bool True if field should be displayed
     */
    function fieldShouldBeDisplayed($value, $valueType) {
        return (!is_null($value) && $value !== '' && !((!isset($value->ID) || !$value->ID) && in_array($valueType, $this->validObjectTypes)));
    }

    /**
     * Returns true if the field is valid to display with this widget.
     * @param mixed $value The value of $this->data['value']
     * @param string $valueType The type indicated by $value
     * @return bool True if field is valid.
     */
    function fieldIsValid($value, $valueType) {
        if (is_object($value) && !in_array($valueType, $this->validObjectTypes)) {
            return false;
        }
        return true;
    }
}