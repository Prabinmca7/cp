<?

namespace RightNow\Internal\Utils;

use RightNow\Utils\Config;

class SearchSourceConfiguration {
    /**
     * Cached core mapping yaml contents.
     * @var array
     */
    private static $coreMapping;

    /**
     * Cached custom mapping yaml contents.
     * @var array
     */
    private static $customMapping;

    /**
     * Cached combined mapping structure.
     * @var array
     */
    private static $mergedMapping;

    /**
     * Errors encountered during processing.
     * @var array
     */
    private static $errors = array();

    /**
     * Gets the core + custom mapping entries configured in
     * the search_sources file.
     * @return array ['source key' => ['filters' => [], 'model' => 'path']]
     */
    static function getSearchMapping () {
        if (is_null(self::$mergedMapping)) {
            self::$mergedMapping = array_merge(self::validatedCustomMapping(), self::validatedCoreMappingWithCustomOverrides());
        }

        return self::$mergedMapping;
    }

    static function getMappingErrors () {
        return self::$errors;
    }

    /**
     * Returns an array containing trimmed items.
     * @param array|string $sources Source ids
     * @return array Normalized items
     */
    static function normalizeSourceIDs ($sources) {
        if (is_string($sources)) {
            $sources = explode(',', $sources);
        }
        $sources = array_map('trim', $sources);

        return $sources;
    }

    /**
     * Returns the custom mappings entries that _don't_ overlap
     * with core entries.
     * @return array combined entries
     */
    private static function validatedCustomMapping () {
        $mapping = array();
        $customMapping = self::getCustomMapping();

        foreach ($customMapping as $source => $sourceInfo) {
            if (!is_array($sourceInfo)) {
                self::$errors []= sprintf(Config::getMessage(THE_PCT_S_SRC_IS_IN_AN_INCOR_FMT_MSG), $source);
                continue;
            }

            $entry = self::customMappingEntry($sourceInfo);

            if ($entry['model']) {
                $mapping[$source] = $entry;
            }
            else {
                self::$errors []= sprintf(Config::getMessage(THE_PCT_S_SRC_DOES_NOT_SPEC_A_LBL), $source);
            }
        }

        return $mapping;
    }

    /**
     * Combines overlapping core / custom mapping entries.
     * @return array combined entries
     */
    private static function validatedCoreMappingWithCustomOverrides () {
        $coreMapping = self::getCoreMapping();
        $customMapping = self::getCustomMapping();

        $mapping = array();

        foreach ($coreMapping as $source => $sourceInfo) {
            $customEntry = null;
            $standardEntry = self::standardMappingEntry($sourceInfo);

            if (array_key_exists($source, $customMapping) && is_array($override = $customMapping[$source])) {
                $customEntry = self::customMappingEntry($override);
                unset($customMapping[$source]);
            }

            $mapping[$source] = self::mergeStandardAndCustomEntries($standardEntry, $customEntry ?: array());
        }

        return $mapping;
    }

    /**
     * Combines a custom and core mapping entry.
     * Uses the custom model, if specified, before defaulting to the core model.
     * Merges the filters, giving precedence to custom filters.
     * @param array $standardEntry Core entry
     * @param array $customEntry Custom entry
     * @return array Merged entry
     */
    private static function mergeStandardAndCustomEntries (array $standardEntry, array $customEntry) {
        $customEntryModel = isset($customEntry['model']) ? $customEntry['model'] : null;
        $standardEntryModel = isset($standardEntry['model']) ? $standardEntry['model'] : null;

        $standardEntryFilters = isset($standardEntry['filters']) ? $standardEntry['filters'] : array();
        $customEntryFilters = isset($customEntry['filters']) ? $customEntry['filters'] : array();

        $endpoint = isset($customEntry['endpoint']) ? $customEntry['endpoint'] : $standardEntry['endpoint'];

        return array(
            // Custom model can override standard model.
            'model'    => $customEntryModel ?: $standardEntryModel,
            // Merge filters. Custom filters can override standard filters.
            'filters'  => array_merge($standardEntryFilters, $customEntryFilters),
            'endpoint' => $endpoint,
        );
    }

    /**
     * Returns a normalized custom mapping entry.
     * @param  array $entry Custom mapping entry
     * @return array        Mapping entry with the model
     *                             prefixed with custom/ (or nulled out)
     *                             and filters set (or empty arrayed)
     */
    private static function customMappingEntry (array $entry) {
        return array(
            'model'    => ($entry['model']) ? 'custom/' . $entry['model'] : null,
            'filters'  => $entry['filters'] ?: array(),
            'endpoint' => $entry['endpoint'],
        );
    }

    /**
     * Returns a normalized standard mapping entry.
     * @param  array $entry Mapping entry
     * @return array        Mapping entry with the model
     *                          path prefixed with standard/
     */
    private static function standardMappingEntry (array $entry) {
        $entry['model'] = 'standard/' . $entry['model'];

        return $entry;
    }

    /**
     * Returns the core search_sources file.
     * @return array core file contents, YAML-parsed
     */
    private static function getCoreMapping () {
        if (is_null(self::$coreMapping)) {
            self::$coreMapping = @yaml_parse_file(CPCORE . 'Config/search_sources.yml');
        }

        return self::$coreMapping;
    }

    /**
     * Returns the custom search_sources file.
     * @return array custom file contents, YAML-parsed
     */
    private static function getCustomMapping () {
        if (is_null(self::$customMapping)) {
            self::$customMapping = (IS_REFERENCE) ? array() : (@yaml_parse_file(APPPATH . 'config/search_sources.yml') ?: array());
        }

        return self::$customMapping;
    }
}
