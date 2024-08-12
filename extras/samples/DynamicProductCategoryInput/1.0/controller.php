<?php
/**
 * File: controller.php
 * Abstract: Basic controller for the DynamicProductCategoryInput widget
 * Version: 1.0
 */

namespace Custom\Widgets\input;

class DynamicProductCategoryInput extends \RightNow\Widgets\ProductCategoryInput {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        // Call into parent's 'getData' method. If something went wrong, return false so the widget is not rendered.
        if (parent::getData() === false) return false;

        // Parse the value in the attribute
        $this->data['js']['fieldMapping'] = $this->parseFieldMapping($this->data['attrs']['show_fields_for_ids']);

        // Parse the list of fields that make Incidents.FileAttachments required
        $this->data['js']['requiredFieldMapping'] = $this->parseFieldMapping($this->data['attrs']['fields_required_for_ids']);
    }

    /**
     * Parses the 'show_fields_for_ids' and 'fields_required_for_ids' attributes into a data structure that can be used in JavaScript
     * @param string $attributeValue The value from the attribute that will be parsed
     * @return array Parsed attributes
     */
    protected function parseFieldMapping($attributeValue) {
        $keyValuePairs = array();
        if (trim($attributeValue) === "")
            return $keyValuePairs;

        $items = explode("|", $attributeValue);
        foreach ($items as $item) {
            list ($key, $valueString) = explode(":", $item);
            $keyValuePairs[trim($key)] = array_map(function($value) {
                return trim($value);
            }, explode(',', $valueString));
        }
        return $keyValuePairs;
    }
}
