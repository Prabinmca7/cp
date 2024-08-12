<?
namespace RightNow\Libraries;

use RightNow\Utils\Text,
    RightNow\Connect\v1_4 as Connect;

/**
 * Provides a simple object wrapper around
 * a given ROQL query.
 */
class ConnectTabular {
    /**
     * Cache of query instances.
     * @var array
     */
    private static $cache = array();

    /**
     * The ROQL query to execute
     * @var string
     */
    public $query;

    /**
     * ROQL error message if an Exception
     * is encountered while executing the query.
     * @var string
     */
    public $error;

    /**
     * Error message if something not quite right
     * had to be performed to map the query.
     * @var array
     */
    public $warnings = array();

    /**
     * The cached result of #getFirst.
     * @var object|null
     */
    private $result;

    /**
     * The cached result of #getCollection.
     * @var array
     */
    private $results;

    /**
     * Returns a ConnectTabular instance for the query. Maintains a cache
     * of query instances with the supplied cache key.
     * @param  string $query    ROQL query
     * @param  string|bool $cacheKey Cache key for the query (e.g. "SocialUser1"
     *                          if querying for a CommunityUser with an ID of 1);
     *                          if not supplied, an md5 of $query is used;
     *                          if FALSE is supplied, no caching is performed
     * @return ConnectTabular           Instance
     */
    static function query ($query, $cacheKey = null) {
        if ($cacheKey === false) return new ConnectTabular($query);

        if (!$cacheKey || !is_string($cacheKey)) {
            $cacheKey = md5($query);
        }
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        return self::$cache[$cacheKey] = new ConnectTabular($query);
    }

    /**
     * Merges two tabular data objects into one. Properties
     * in the second object with the same name overwrite
     * properties in the first object.
     * @param  TabularDataObject $first Tabular object
     * @param  TabularDataObject $second Tabular object
     * @return TabularDataObject          Merged object
     */
    static function mergeQueryResults (TabularDataObject $first, TabularDataObject $second) {
        return new TabularDataObject(array_merge((array) $first, (array) $second));
    }

    /**
     * Private constructor.
     * @param string $query Query for the instance.
     */
    private function __construct ($query) {
        $this->query = $query;
    }

    /**
     * Gets a single row.
     * @param array|string|null $decorator Decorator to apply to result instance
     * @return object|null Result of the query
     */
    function getFirst ($decorator = null) {
        if ($this->result || $this->error || (!$fields = $this->extractSelect($this->query))) return $this->result;

        if (($result = $this->fetch()) && ($firstItem = $result->next())) {
            return $this->result = $this->populateResult($firstItem, $fields, $decorator);
        }
    }

    /**
     * Gets multiple rows.
     * @param array|string|null $decorator Decorator to apply to each result instance
     * @return array Contains an object for each row.
     */
    function getCollection ($decorator = null) {
        if ($this->results || $this->error || (!$fields = $this->extractSelect($this->query))) {
            return $this->results ?: array();
        }

        $results = array();
        $result = $this->fetch();

        while ($result && ($item = $result->next())) {
            $results []= $this->populateResult($item, $fields, $decorator);
        }

        return $this->results = $results;
    }

    /**
     * Fires off the query. Records any exception
     * message in the `error` property.
     * @return object ROQL result object
     */
    private function fetch () {
        try {
            $result = Connect\ROQL::query($this->query)->next();
        }
        catch (\Exception $e) {
            $this->error = $e->getMessage();
        }

        return isset($result) ? $result : null;
    }

    /**
     * Populates a standard object with property names either
     * supplied by $fields or falling back to simply converting
     * $result into a simple object.
     * @param array $result Result row of column name -> value.
     * @param array $fields Mapping from #extractSelect
     * @param array|string|null $decorator Optional decorator to apply to TabularDataObject instance
     * @return object         Converted $result
     */
    private function populateResult (array $result, array $fields, $decorator = null) {
        $obj = new TabularDataObject;

        if (count($fields) === count($result)) {
            // Clean mapping.
            foreach ($result as $key => $val) {
                $fromQuery = current($fields);
                $this->assignProperty($obj, $val, $fromQuery);
                next($fields);
            }
        }
        else {
            foreach ($result as $key => $val) {
                $obj->{$key} = $val;
            }
        }

        if($decorator){
            Decorator::add($obj, $decorator);
        }

        return $obj;
    }

    /**
     * Properly creates the property (and sub-properties) for
     * the given mapping.
     * @param  object $obj     Result row object
     * @param  string|int|null $value   Property value
     * @param  object $mapping Mapping from #extractAlias
     */
    private function assignProperty($obj, $value, $mapping) {
        foreach (array_filter(array(isset($mapping->name) ? $mapping->name : null, isset($mapping->alias) ? $mapping->alias : null)) as $name) {
            $this->assign($obj, self::subFields($name), $value);
        }
    }
    
    /**
     * Splits the string into an array representing
     * each sub-property. Adds an additional ID
     * sub-field for Status and StatusType fields
     * in order to conform with Connect objects.
     * @param  string $name Field name
     * @return array       Sub-fields
     */
    private static function subFields ($name) {
        return explode('.', $name);
    }

    /**
     * Assigns properties and sub-properties onto $obj.
     * @param  object $obj        Result row object and its
     *                            sub-objects
     * @param  array $propertyName Property names
     * @param  string|int|null $value      Property value
     */
    private function assign ($obj, array $propertyName, $value) {
        $name = array_shift($propertyName);

        if (count($propertyName) > 0) {
            if (!property_exists($obj, $name) || !is_object($obj->{$name})) {
                $this->warnings []= "Attempting to set a sub-property on a property that has already been assigned out as a primitive value: $name";
                $obj->{$name} = new TabularDataObject;
            }

            $this->assign($obj->{$name}, $propertyName, $value);
        }
        else {
            $obj->{$name} = $value;
        }
    }

    /**
     * Extracts the columns being selected in the query.
     * @param  string $query ROQL query
     * @return array        Filled with objects representing
     *                             each field
     */
    private function extractSelect ($query) {
        $matches = array();

        if (preg_match('/select (.*)[^from]from (.*)/misx', $query, $matches)) {
            if (Text::stringContains($matches[0], '*')) {
                $this->error = "* not allowed. Each field must be specified.";
            }
            else {
                $columns = array_filter(explode(',', $matches[1]), 'trim');
                return self::extractAlias($matches[2], $columns);
            }
        }
        else {
            $this->error = "Invalid query: $query";
        }

        return array();
    }

    /**
     * Extracts the table alias from the post select portion of a query and produces
     * an array of objects representing each field. The table alias is removed from
     * each field; each field also has an optional alias property if an alias was
     * defined in the query.
     * @param  string $postSelect The portion of a query after the SELECT bit
     * @param  array $fields     Fields extracted from the SELECT bit
     * @return array            $fields if unable to process $postSelect
     */
    private static function extractAlias ($postSelect, array $fields) {
        if (preg_match('/\s*([A-Za-z.0-9-_]+) ([a-zA-Z0-9-_]+)/mis', $postSelect, $matches)) {
            $aliases = array_slice($matches, 1);
            $mapped = array();
            foreach ($fields as $val) {
                $mapped []= self::fieldNameToObject(trim($val), $aliases);
            }
            return $mapped;
        }

        return $fields;
    }

    /**
     * Produces an object representing the field.
     * @param  string $fieldName    Field name
     * @param  array $tableAliases Table aliases to extract from $fieldName
     * @return object               Has a name property and optionally an
     *                                  alias property
     */
    private static function fieldNameToObject ($fieldName, $tableAliases) {
        foreach ($tableAliases as $alias) {
            if (Text::beginsWith($fieldName, "{$alias}.")) {
                $fieldName = Text::getSubstringAfter($fieldName, "{$alias}.");
            }
        }

        if (Text::stringContainsCaseInsensitive($fieldName, ' as ')) {
            list($fieldName, $alias) = explode(' as ', $fieldName) + [null, null];
            if (!$alias) {
                list($fieldName, $alias) = array_pad(explode(' AS ', $fieldName), 2, '');
            }
            return (object) array(
                'alias' => trim(str_replace(array('"', "'"), '', $alias)),
                'name'  => trim($fieldName),
            );
        }

        return (object) array('name' => $fieldName);
    }
}

/**
 * Represents a row of ROQL tabular results.
 */
#[\AllowDynamicProperties]
class TabularDataObject {  
    /**
     * Constructor.
     * @param array $properties Properties to populate
     */
    function __construct(array $properties = array()) {
        foreach ($properties as $name => $val) {
            $this->{$name} = $val;
        }
    }

    /**
     * String view of the object.
     * @return string Properties of the object
     */
    function __toString () {
        return (string) (array) $this;
    }
}
