<?php
namespace RightNow\Libraries;
use RightNow\Utils\Text;

/**
 * Structure that holds all the details and information about a page set mapping.
 */
final class PageSetMapping {

    /**
     * Page set ID
     */
    private $id;

    /**
     * User agent regex
     */
    private $item;

    /**
     * Page set folder
     */
    private $value;

    /**
     * Bool denoting if page set is uneditable
     */
    private $locked;

    /**
     * Bool denoting if page set is enabled
     */
    private $enabled;

    /**
     * Description of page set
     */
    private $description;

    public function __construct(array $members) {
        $this->id = $members['id'];
        $this->item = $members['item'];
        $this->description = $members['description'];
        $this->value = $members['value'];
        $this->enabled = array_key_exists('enabled', $members) ? $members['enabled'] : true;
        $this->locked = array_key_exists('locked', $members) ? $members['locked'] : false;
    }

    /**
     * Converts the mapping into a string so it can be written to a file.
     *
     * @return string Contents of PageSetMapping in strong form
     * @internal
     */
    public function __toString() {
        $escape = function($string) {
            return str_replace("'", "\'", $string);
        };
        return "$this->id => new \RightNow\Libraries\PageSetMapping(array('id' => $this->id, " .
            "'item' => '" . $escape($this->item) . "', " .
            "'description' => '" . $escape($this->description) . "', " .
            "'value' => '" . $escape($this->value) . "', " .
            "'enabled' => " . (($this->enabled) ? "true" : "false") . ", " .
            "'locked' => " . (($this->locked) ? "true" : "false") . "))";
    }

    /**
     * Converts PageSetMapping into an associative array
     * @return array PageSetMapping in array form
     */
    public function toArray() {
        return array(
          'id' => $this->id,
          'item' => $this->item,
          'description' => $this->description,
          'value' => $this->value,
          'enabled' => $this->enabled,
          'locked' => $this->locked,
        );
    }

    /**
     * Getter for all private members.
     * @param string $name Property to retrieve
     * @return Mixed The value of the property specified.
     * @throws \Exception If property doesn't exist on class
     */
    public function __get($name) {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new \Exception("$name doesn't exist on this object");
    }

    /**
     * Provides backward-compatibility with old getter methods for mappings passed to pre_page_set_selection hook.
     * @param string $name Method name being called
     * @param mixed $args Arguments to pass to the method
     * @return mixed Result of property value
     */
    public function __call($name, $args) {
        if (Text::beginsWith($name, 'get') && ($property = Text::getSubstringAfter($name, 'get'))) {
            return $this->__get(strtolower($property));
        }
    }
}