<?

namespace RightNow\Internal\Api\Libraries;

/**
 * Represents a single search results in a set of
 * \RightNow\Internal\Api\Libraries\SearchResults.
 * Provides a common interface so that, regardless of
 * a result's source, clients can be assured that a basic
 * set of properties will exist.
 * Specific search sources can set their own unique properties
 * on a sub-object property.
 *
 *      $searchResult = new SearchResult();
 *      $searchResult->type = 'MyType';
 *      $searchResult->MyType->author = 'Cuffy';
 *      $searchResult->text = 'Gather';
 */
class SearchResult {
    /**
     * URL representing where the result can be found.
     * @var string
     */
    public $url;

    /**
     * Title or text representing the result. Commonly used
     * as a link's text.
     * @var string
     */
    public $text;

    /**
     * Snippet of the result's content.
     * @var string
     */
    public $summary;

    /**
     * Timestamp representing when the content was created.
     * @var string
     */
    public $created;

    /**
     * Timestamp representing when the content was updated.
     * @var string
     */
    public $updated;

    /**
     * A specific type of result. When this property is set,
     * a sub-object property is set on the SearchResult instance, allowing
     * type-specific properties to be added onto it.
     * @var string
     */
    protected $type;

    /**
     * Constructor.
     * @param string $type Optional type to set.
     */
    function __construct($type = '') {
        if ($type) {
            $this->__set('type', $type);
        }
    }

    /**
     * Setter. If the property being set is
     * `type` then a sub-object with the type's
     * name is set on the object.
     * @param string $name Property name
     * @param mixed $value Property value
     * @throws \Exception When property value is not a string
     * @return mixed Property value
     */
    function __set ($name, $value) {
        if ($name === 'type') {
            if (!is_string($value)) {
                throw new \Exception("type property must be a string");
            }
            $this->setSubPropertyObject($value);
        }

        return $this->{$name} = $value;
    }

    /**
     * Getter.
     * @param  string $name Property name
     * @return mixed       Property value
     */
    function __get ($name) {
        return $this->$name;
    }

    /**
     * Returns an array representation of the object,
     * suitable for json encoding.
     * @return array This object, as an array
     */
    function toArray () {
        $obj = (array) $this;

        foreach ($obj as $key => $val) {
            if (!preg_match("/^[a-zA-Z]/", $key)) {
                // Restricted access property names are prefixed with
                // '\u0000*\u0000'.
                $obj[substr($key, 3)] = $val;
                unset($obj[$key]);
            }
        }

        return $obj;
    }

    /**
     * Sets an empty object on the given propery
     * name.
     * @param string $name Property name
     */
    private function setSubPropertyObject ($name) {
        $this->{$name} = (object) array();
    }
}
