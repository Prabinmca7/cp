<?php
namespace RightNow\Internal\Libraries;

use RightNow\Utils\Connect as ConnectUtil,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Config,
    RightNow\Utils\Text;

final class ConnectExplorer {
    const ADMIN_URL = '/ci/admin/explorer/inspect/';
    const MAX_QUERY_LIMIT = 10000;
    private static $queryTypes = array('select', 'describe', 'show');
    private static $newlineIndicator = '_CP_NEWLINE_';

    /**
     * Parses and runs the specified query and returns the results.
     * @param string $query The ROQL query
     * @param null|integer $defaultLimit The ROQL query 'LIMIT' parameter to be used if one is not explicitly specified in the $query itself.
     * @param integer $requestedPage The expected values are: -1 for previous page, 0 to not add pagination to query and 1 for next page. Default is 0.
     * @return array
     */
    public static function query($query, $defaultLimit = null, $requestedPage = 0) {
        try {
            return self::getResults(self::getQueryObject($query, $defaultLimit, intval($requestedPage)));
        }
        catch (\Exception $e) {
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Returns an array of Connect primary class names.
     * @param boolean $stripNamespace If true, remove the Connect namespace prefix from each class name.
     * @return array
     */
    public static function getPrimaryClasses($stripNamespace = false) {
        static $classes;
        static $stripped;
        if (!isset($classes)) {
            $classes = Connect\ConnectAPI::getPrimaryClassNames();
            sort($classes);
        }
        if ($stripNamespace) {
            if (!isset($stripped)) {
                $stripped = array_map(function($item) use ($classes) {
                    return str_replace("\\", ".", Text::getSubstringAfter($item, ConnectUtil::prependNamespace()));
                }, $classes);
            }
            return $stripped;
        }
        return $classes;
    }

    /**
     * Returns an array of Connect custom class namespaces.
     * @return array
     */
    public static function getCustomNamespaces() {
        static $namespaces = array();
        if (empty($namespaces)) {
            $classes = Connect\ConnectAPI::getPrimaryClassNames();
            foreach ($classes as $class) {
                list ($namespace, $class) = array_pad(explode('\\', Text::getSubstringAfter($class, ConnectUtil::prependNamespace())), 2, null);
                if ($class !== null) {
                    $namespaces[$namespace] = 1;
                }
            }
        }
        return array_keys($namespaces);
    }

    /**
     * Returns an array of formatted meta information as expected by the explorer/inspect page.
     * @param string $fieldName The Connect field name in dot notation (e.g. 'Contact' or 'Contact.Address.City')
     * @param integer|null $id The optional ID of a specific record of the primary object.
     * @return array An associative array having keys:
     *     {string} 'objectName' The primary object name
     *     {array}  'fields' The field names broken out into an array, minus the objectName
     *     {array}  'rows' The meta data fields ('field name', 'value', 'field type')
     */
    public static function getMeta($fieldName, $id = null) {
        list($objectName, $fieldName, $fields) = self::parseFieldName($fieldName);
        if (!$objectName) {
            return array();
        }
        $namespace = ConnectUtil::prependNamespace();
        $connectName = "{$namespace}{$objectName}";
        $object = ($id) ? $connectName::first('ID = ' . $id) : new $connectName();
        $meta = $object::getMetadata();
        $adminUrl = self::ADMIN_URL;
        $primaryName = $objectName;

        $isMeta = function($o) use ($namespace) {
            return (is_object($o) && Text::endsWith(get_class($o), '_metadata'));
        };

        $getData = function($field, $value, $type, $link = null, $parent = null) use ($id) {
            $data = array('field' => $field, 'value' => $value, 'type' => $type);
            if ($link) $data['link'] = str_replace('\\', '.', $link);
            if ($parent) $data['parent'] = $parent;
            return $data;
        };

        $objectData = null;
        if ($fields) {
            try {
                list($objectData, $meta) = ConnectUtil::find($fields, $meta, $object);
                if (is_object($objectData)) {
                    $object = $objectData;
                    $primaryName = $fieldName;
                }
                else if ($objectData == null) {
                    $primaryName = $fieldName;
                }
            }
            catch (\Exception $e) {
                $meta = (object) array();
            }
        }

        $rows = array();
        $trueLabel = Config::getMessage(TRUE_LC_LBL);
        $falseLabel = Config::getMessage(FLSE_LBL);
        $values = is_array($meta) ? $meta : get_object_vars($meta);
        foreach ($values as $key => $value) {
            if (!is_numeric($key)) {
                try {
                    $objectData = isset($object->{$key}) ? $object->{$key} : null;
                }
                catch (\Exception $e) {
                    $objectData = null;
                }
            }
            if ($isMeta($value)) {
                $rows[] = $getData($key, is_object($objectData) ? null : $objectData, self::getSubType($value), "$primaryName.$key");
                if (is_object($objectData)) {
                    foreach(get_object_vars($objectData) as $subKey => $subValue) {
                        try {
                            list($subValue, $subMeta) = ConnectUtil::find(array($key, $subKey), $meta, $object);
                        }
                        catch (\Exception $e) {
                            $subValue = null;
                        }
                        if (is_object($subValue)) {
                            $subValue = null;
                            $link = "{$primaryName}.{$key}.{$subKey}";
                        }
                        else {
                            $link = null;
                        }
                        $rows[] = $getData($subKey, $subValue, self::getSubType(isset($subMeta) ? $subMeta : null), $link, $key);
                    }
                }
            }
            else if (is_array($value)) {
                // Usually relationships or constraints.
                $rows[] = $getData($key, 'Array (' . count($value) . ')', "{$namespace}Array", "$fieldName.$key");
                foreach($value as $index => $subValue) {
                    $rows[] = $getData(self::getSubField($subValue, $index), self::getSubData($subValue), self::getSubType($subValue), "$fieldName.$key.$index", $key);
                }
            }
            else {
                if ($value === true) {
                    $value = $trueLabel;
                }
                else if ($value === false) {
                    $value = $falseLabel;
                }
                $rows[] = $getData($key, $value, $objectData);
            }
        }
        return array(
            'objectName' => str_replace('\\', '.', $objectName),
            'fields' => $fields,
            'rows' => $rows,
        );
    }

    /**
     * Return the value used for the 'field type' column
     * @param object|null $object Instance of Connect object
     */
    private static function getSubType($object) {
        if ($object) {
            return $object->type_name ?: $object->remoteObjectType ?: ConnectUtil::prependNamespace('Meta');
        }
    }

    /**
     * Return the value used for the 'data' column
     * @param object $object Instance of Connect object
     */
    private static function getSubData($object) {
        if (property_exists($object, 'kind') && property_exists($object, 'value')) {
            return "kind: {$object->kind} value: {$object->value}";
        }
    }

    /**
     * Return the value used for the 'field name' column
     * @param object|null $object Instance of Connect object
     * @param mixed $index Value to return if object doesn't have a relationName
     */
    private static function getSubField($object, $index = null) {
        if (property_exists($object, 'relationName')) {
            return $object->relationName;
        }
        return $index;
    }

    /**
     * Parse $fieldname into the primary object name and fields, replacing leading CO.{field} with CO\\{field}.
     * @param string $fieldName The Connect fields in dot notation.
     * @return null|array A 3 element array consisting of ({string} objectName, {string} fieldName {array} lookup)
     */
    private static function parseFieldName($fieldName) {
        if ($fieldName = trim("$fieldName")) {
            $lookup = explode('.', $fieldName);
            $objectName = trim(array_shift($lookup));
            $customObjectNamespaces = self::getCustomNamespaces();
            if (in_array($objectName, $customObjectNamespaces) && $lookup) {
                $objectName .= '\\' . trim(array_shift($lookup));
                $fieldName = $objectName . ($lookup ? ('.' . implode('.', $lookup)) : '');
            }
            return array($objectName, $fieldName, $lookup);
        }
    }

    /**
     * Parses the ROQL query and returns the results and additional meta data as an object.
     * @param string $query The ROQL query
     * @param null|integer $defaultLimit The ROQL query 'LIMIT' parameter to be used if one is not explicitly specified in the $query itself.
     * @param integer $requestedPage The expected values are: -1 for previous page, 0 to not add pagination to query and 1 for next page. Default is 0.
     * @return object
     * @throws \Exception If query provided is invalid
     */
    private static function getQueryObject($query, $defaultLimit = null, $requestedPage = 0) {
        $query = self::strip($query);
        $parts = self::getQueryParts($query);
        if (!is_array($parts['raw']) || count($parts['raw']) === 0) {
            throw new \Exception(sprintf(Config::getMessage(INVALID_QUERY_PCT_S_COLON_LBL), $query));
        }

        $objectName = null;     // ['Contact', 'Incident', 'Account', etc..]
        $queryType = strtolower($parts['raw'][0]);  // ['select', 'describe', 'show'], maps to ConnectExplorer methods
        switch ($queryType) {
            case 'show':
                if (($stripped = strtolower(self::strip($query, true))) && $stripped !== 'show objects' && $stripped !== 'show tables') {
                    throw new \Exception(sprintf(Config::getMessage(INV_QUERY_TB_OBJECTS_EXPECTED_PCT_S_LBL), $query));
                }
                $objectName = 'Objects';
                break;
            case 'describe':
            case 'desc':
                $objectName = self::getObjectName($parts['raw'][1], false);
                // throw exception if no objectName (or not valid primary object);
                $queryType = 'describe';
                $query = "DESC $objectName";
                break;
            case 'select':
                list($query, $objectName, $columns, $limit, $offset) = self::parseSelectQuery($query, $parts, $defaultLimit, $requestedPage);
                break;
            default:
                throw new \Exception(sprintf(Config::getMessage(INVALID_QUERY_PCT_S_COLON_LBL), $query));
        }

        return (object) array(
            'queryType' => $queryType,
            'objectName' => $objectName,
            'columns' => array_map(function($item) { return trim($item); }, explode(',', $columns)),
            'limit' => $limit,
            'offset' => $offset,
            'query' => $query,
        );
    }

    /**
     * Takes the results from getQueryObject and delivers the query results and additional meta info in an array.
     * @param object $queryObject The parsed query data as returned from getQueryObject.
     * @return array
     * @throws \Exception If query type isn't supported
     */
    private static function getResults($queryObject) {
        if (!is_object($queryObject)) {
            throw new \Exception(Config::getMessage(QUERY_OBJECT_IS_NOT_AN_OBJECT_MSG));
        }
        $startTime = microtime(true);
        $queryType = $queryObject->queryType;
        if (in_array($queryType, self::$queryTypes)) {
            list($columns, $results) = self::$queryType($queryObject);
        }
        else {
            throw new \Exception(Config::getMessage(INVALID_QUERY_TYPE_LBL) . ": $queryType");
        }

        return array(
            'duration' => round((microtime(true) - $startTime), 4),
            'total' => count($results),
            'limit' => $queryObject->limit,
            'offset' => $queryObject->offset,
            'queryType' => $queryObject->queryType,
            'objectName' => $queryObject->objectName,
            'query' => $queryObject->query,
            'columns' => $columns,
            'results' => $results,
        );
    }

    /**
     * Takes the query object as returned from getQueryObject for a SELECT statement and returns the results.
     * @param object $queryObject The parsed query data as returned from getQueryObject.
     * @return array An array of the results and columns from the requested SELECT.
     * @throws \Exception If query cannot be executed via Connect
     */
    private static function select($queryObject) {
        $getTitle = function($objectName) {
            return Config::getMessage(INSPECT_LBL) . " $objectName ";
        };
        $objectName = $queryObject->objectName;
        $title = $getTitle($objectName);

        try {
            // Add "hidden" columns _ID_, _LINK_ and _TITLE_ so we can build a link to the meta info.
            $row = Connect\ROQL::query("SELECT {$objectName}.ID AS _ID_,
                '{$objectName}' AS _LINK_, '{$title}' AS _TITLE_," .
                substr(html_entity_decode($queryObject->query), 6))->next(); // strlen('select') = 6
            $isObjectQuery = false;
        }
        catch (\Exception $tabularQueryException) {
            try {
                $row = Connect\ROQL::queryObject($queryObject->query)->next();
                $isObjectQuery = true;
                $objectName = null;
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                throw $tabularQueryException;
            }
        }

        $columns = $results = array();
        while ($result = $row->next()) {
            $columns = $columns ?: array_keys($isObjectQuery ? get_object_vars($result) : $result);
            if ($isObjectQuery) {
                if (!$objectName) {
                    $objectName = Text::getSubstringAfter(get_class($result), CONNECT_NAMESPACE_PREFIX . '\\');
                    $title = $getTitle($objectName);
                }
                $data = array('_ID_' => $result->ID, '_LINK_' => $objectName, '_TITLE_' => $title);
                foreach($columns as $fieldName) {
                    $value = $result->$fieldName;
                    $data[$fieldName] = self::getClassnameOrValue($value);
                }
                $result = $data;
            }
            $results[] = $result;
        }
        return array(self::getColumns($columns), $results);
    }

    /**
     * Returns classname of given field
     * @param mixed $field Field instance or name
     * @return mixed If $field is an object, return the classname, stripped of the leading 'RightNow\\' name.
     *     If not an object, return $field unchanged.
     */
    private static function getClassnameOrValue($field) {
        if (is_object($field)) {
            $classname = get_class($field);
            return Text::getSubstringAfter($classname, 'RightNow\\', $classname);
        }
        return $field;
    }

    /**
     * Takes the query object as returned from getQueryObject for a DESCRIBE statement and returns the results.
     * @param object $queryObject The parsed query data as returned from getQueryObject.
     * @return array An array of the results and columns from the requested DESCRIBE.
     */
    private static function describe($queryObject) {
        $columns = $results = array();
        $objectName = $queryObject->objectName;
        $object = self::getObjectName($objectName);
        $meta = $object::getMetadata();
        $title = Config::getMessage(INSPECT_LBL) . " {$objectName}.";
        $getData = function($field, $type, $nillable = null, $default = null) use ($title, $objectName) {
            return array(
                '_ID_' => $field,
                '_LINK_' => "{$objectName}.$field",
                '_TITLE_' => $title,
                'Field' => $field,
                'Type' => Text::getSubstringAfter($type, 'RightNow\\', $type),
                'Null' => $nillable,
                'Default' => $default,
            );
        };
        $yesLabel = Config::getMessage(YES_LBL);
        $noLabel = Config::getMessage(NO_LBL);
        foreach (array_keys(get_object_vars($meta)) as $property) {
            $value = $meta->{$property};
            if (is_object($value)) {
                $result = $getData($property, $value->type_name, $value->is_nillable ? $yesLabel : $noLabel, var_export($value->default, true));
                if (!$columns) {
                    $columns = self::getColumns(array_keys($result));
                }
                $results[] = $result;
            }
            else if ($property === 'relationships') {
                foreach($value as $sub) {
                    $results[] = $getData($sub->relationName, "relationship.{$sub->remoteObjectType}");
                }
            }
        }

        return array($columns, $results);
    }

    /**
     * Returns the result of a 'SHOW Objects' command.
     * @return array
     */
    private static function show() {
        $results = array();
        $inspectLabel = Config::getMessage(INSPECT_LBL);
        $objectLabel = Config::getMessage(OBJECT_NAME_LBL);
        foreach (self::getPrimaryClasses(true) as $className) {
            $results[] = array(
                '_ID_' => $className,
                '_LINK_' => $className,
                '_TITLE_' => "{$inspectLabel} ",
                $objectLabel => $className
            );
        }
        return array(array(array('key' => $objectLabel)), $results);
    }

    /**
     * Return an array of parsed ({query}, {object name}, {columns string})
     * @param string $query The entered query
     * @param array $parts An array of the query "parts" having keys 'raw' and 'upper'.
     * @param null|integer $defaultLimit Default limit of results
     * @param integer $requestedPage The expected values are: -1 for previous page, 0 to not add pagination to query and 1 for next page. Default is 0.
     * @return array
     * @throws \Exception If query provided is invalid and doesn't contain a 'FROM' segment
     */
    private static function parseSelectQuery($query, array $parts, $defaultLimit = null, $requestedPage = 0) {
        if (!$fromPosition = array_search('FROM', $parts['upper'], true)) {
            throw new \Exception(sprintf(Config::getMessage(INV_QUERY_PCT_S_CLAUSE_PCT_S_LBL), 'FROM', 'SELECT', $query));
        }

        $objectNamePosition = $fromPosition + 1;
        $objectName = trim($parts['raw'][$objectNamePosition]);
        while ($objectName === self::$newlineIndicator || $objectName === "") {
            $objectName = trim($parts['raw'][++$objectNamePosition]);
        }
        $objectName = self::getObjectName($objectName, false);

        $columns = implode(' ', array_slice($parts['raw'], 1, $fromPosition - 1));
        $conditions = trim(implode(' ', array_slice($parts['raw'], $objectNamePosition + 1)));
        $limit = (int)$defaultLimit;
        if ($limit && (!$conditions || !self::limitExists(self::restoreNewlines($conditions)))) {
            $conditions .= ' LIMIT ' . $limit;
        }
        else {
            $limit = self::getLimit(self::restoreNewlines($conditions));
            if($limit > self::MAX_QUERY_LIMIT){
                $limit = self::MAX_QUERY_LIMIT;
                $conditions = preg_replace('/limit \d+/mi', "LIMIT $limit", $conditions);
            }
        }

        $offset = self::getOffset(self::restoreNewlines($conditions));
        if ($requestedPage !== 0) {
            if ($requestedPage < 0) {
                // prev
                $offset = $offset - $limit;
                if ($offset < 0) {
                    $offset = 0;
                }
            }
            else {
                // next
                $offset = $limit + $offset;
            }

            if (!$conditions || !self::offsetExists(self::restoreNewLines($conditions))) {
                // add offset
                $conditions .= ' OFFSET ' . $offset;
            }
            else {
                // update offset
                $conditions = preg_replace('/ offset \d+/mi', ' OFFSET ' . $offset, $conditions);
            }
        }

        // if there are new lines around the object name, restore them
        $restoredObjectName = $objectName;
        if (($fromPosition + 1) < $objectNamePosition) {
            $restoredObjectName = implode("", array_slice($parts['raw'], $fromPosition + 1, ($objectNamePosition - $fromPosition)));
        }

        $query = self::restoreNewlines(self::strip(
            $parts['raw'][0] .  // SELECT | select
            " $columns " .
            $parts['raw'][$fromPosition] . // FROM | from
            " $restoredObjectName $conditions"
        ));
        return array($query, $objectName, trim(self::restoreNewlines($columns)), $limit, $offset);
    }

    /**
     * Replaces newline indicators with line breaks.
     * @param string $query The entered query
     * @return string
     */
    private static function restoreNewlines($query) {
        return str_replace(array("\n ", " \n"), "\n", str_replace(self::$newlineIndicator, "\n", $query));
    }

    /**
     * Separates $query by spaces and linebreaks and returns an array of parts.
     * @param string $query The entered query
     * @return array An array having keys 'raw' and 'upper'.
     */
    private static function getQueryParts($query) {
        $query = str_replace("\r", '', Text::removeSuffixIfExists($query, ';'));
        $query = str_replace("\n", ' ' . self::$newlineIndicator . ' ', $query);
        return array('raw' => explode(' ', $query), 'upper' => explode(' ', strtoupper($query)));
    }

    /**
     * Returns a formatted array of columns, omitting "hidden" columns.
     * @param array $columns A list of column names
     * @return array
     */
    private static function getColumns(array $columns) {
        $columnData = array();
        foreach ($columns as $value) {
            if ($value !== '_ID_' && $value !== '_LINK_' && $value !== '_TITLE_') {
                $columnData[] = array('key' => $value);
            }
        }
        return $columnData;
    }

    /**
     * Returns the $objectName, optinonally prepended with the connect namespace.
     * Object names are standardized for case and pluralization where possible to match Connect standards.
     * @param string $objectName Object name being queried
     * @param boolean $prependNamespace If true, return the parsed object name with the connect namespace prepended.
     * @return string
     * @throws \Exception If object name provided is not valid
     */
    private static function getObjectName($objectName, $prependNamespace = true) {
        static $cache = array();
        $cacheKey = "{$objectName}-{$prependNamespace}";
        if (!isset($cache[$cacheKey]) || !$cached = $cache[$cacheKey]) {
            $name = ConnectUtil::mapObjectName($objectName);
            if ($name === $objectName) {
                $foundName = false;
                foreach (self::getPrimaryClasses(true) as $className) {
                    if (strtolower($objectName) === strtolower($className)) {
                        $name = $className;
                        $foundName = true;
                        break;
                    }
                }

                if ($foundName === false)
                    throw new \Exception(sprintf(Config::getMessage(INVALID_OBJECT_NAME_PCT_S_COLON_LBL), $name));
            }
            if ($prependNamespace && !Text::beginsWith($name, CONNECT_NAMESPACE_PREFIX)) {
                if (Text::stringContains($name, '.')) {
                    $name = str_replace('.', '\\', $name);
                }
                $name = ConnectUtil::prependNamespace($name);
            }
            $cached = $cache[$cacheKey] = $name;
        }
        return $cached;
    }

    /**
     * Recursively replaces whitespace, and optionally newlines, with a single space.
     * @param string $string String to strip
     * @param boolean $removeNewlines Whether to strip newlines as well
     * @return string
     */
    private static function strip($string, $removeNewlines = false) {
        return trim(preg_replace($removeNewlines ? '/\s+/' : '/\h+/', ' ', $string));
    }

    /**
     * Return true if a LIMIT X clause exists in $query, and is not contained in a quoted sub-section.
     * @param string $query The query or part of a query to inspect for the LIMIT clause.
     * @return boolean
     */
    private static function limitExists($query) {
        return (self::getLimitOrOffsetValue('limit', $query) !== null);
    }

    /**
     * Return true if a OFFSET X clause exists in $query, and is not contained in a quoted sub-section.
     * @param string $query The query or part of a query to inspect for the OFFSET clause.
     * @return boolean
     */
    private static function offsetExists($query) {
        return (self::getLimitOrOffsetValue('offset', $query) !== null);
    }

    /**
     * Returns the value of the LIMIT or OFFSET keyword
     * @param string $keyword The in-casesensitive keyword to look for (LIMIT or OFFSET)
     * @param string $query The query or part of a query to return the $keyword value from
     * @return null|integer
     */
    private static function getLimitOrOffsetValue($keyword, $query) {
        $keyword = strtoupper($keyword);
        $query = ' ' . self::strip($query, true);
        if (preg_match_all("/ $keyword (\d+)/i", $query, $matches)) {
            if (!Text::stringContains($query, "'") && !Text::stringContains($query, '"')) {
                return (int)trim(Text::getSubstringAfter(strtoupper($matches[0][0]), $keyword));
            }
            $items = array();
            foreach($matches[0] as $match) {
                $match = trim($match);
                $items[$match] = $items[$match] ? $items[$match] + 1 : 1;
                if (!preg_match_all('/(["\'])[^\\\1]*?' . $match . '[^\\\1]*?\1/', $query, $matchesInQuotes) || count($matchesInQuotes[0]) < $items[$match]) {
                    return (int)trim(Text::getSubstringAfter(strtoupper($match), $keyword));
                }
            }
        }
        return null;
    }

    /**
     * Return the LIMIT from the query, or the provided default limit
     * @param string $query The query or part of a query to return the LIMIT from
     * @param int|null $defaultLimit The default limit to return if no limit was found in $query
     * @return integer
     */
    private static function getLimit($query, $defaultLimit = null) {
        $value = self::getLimitOrOffsetValue('LIMIT', $query);
        return $value ?: $defaultLimit;
    }

    /**
     * Return the OFFSET from the query
     * @param string $query The query or part of a query to return the OFFSET from
     * @return integer
     */
    private static function getOffset($query) {
        $value = self::getLimitOrOffsetValue('OFFSET', $query);
        return $value ?: 0;
    }
}
