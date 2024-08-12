<?php
namespace RightNow\Internal\Libraries;
use RightNow\Internal\Utils\Version as VersionUtils,
    RightNow\Utils\FileSystem,
    RightNow\Utils\Text;

final class Changelog {
    private static $levels = array('major', 'minor', 'nano');
    private static $categories = array('NEW_FEATURE', 'DEPRECATION', 'API_CHANGE', 'BUG_FIX', 'OTHER');
    private static $sortPriorities = array('top' => 2, 'middle' => 1, 'bottom' => 0);
    private static $levelValues = array('major' => 2, 'minor' => 1, 'nano' => 0);
    private static $cache;
    private static $cacheKeys = array();

    /**
     * Return specified changelog entries in yaml format, filtered within $min and $max version.
     *
     * @param string $target Either 'framework' or the name of a widget (e.g. 'input/DateInput')
     * @param string|null $min The minimum version string ({major}.{minor}.{nano}, or {major}.{minor} or {major})
     * @param string|null $max The maximum version string ({major}.{minor}.{nano}, or {major}.{minor} or {major})
     * @return string|null
     * @throws ChangelogException If an invalid $target is specified.
     */
    public static function getChangelog($target, $min = null, $max = null) {
        if ($changelog = self::getChangelogAsArray($target, $min, $max)) {
            return self::arrayToYaml($changelog);
        }
    }

    /**
     * Return specified changelog entries as an array, filtered within $min and $max version.
     *
     * @param string $target Either 'framework' or a widget specifier (e.g. 'input/DateInput')
     * @param string|null $min The minimum version string ({major}.{minor}.{nano}, or {major}.{minor} or {major})
     * @param string|null $max The maximum version string ({major}.{minor}.{nano}, or {major}.{minor} or {major})
     * @return array|null
     * @throws ChangelogException If an invalid $target is specified.
     */
    public static function getChangelogAsArray($target, $min = null, $max = null) {
        list($target, $path) = self::getChangelogPath($target);
        return self::getChangelogFromCache($path, $target, $min, $max);
    }

    /**
     * Adds changelog entries from QA report via ci/admin/internalTools/addNewChangelogEntries
     *
     * @param array $entries Array of changelog entries from the QA site.
     * @param bool $writeToDisk If FALSE, do not write to changelog.yml on disk.
     * @param bool $returnExceptionsAsArrays Whether to return exceptions
     * @return array A response array having keys:
     *                   'commits' array A list of arrays having 'refno', 'files' and 'account' as keys.
     *                   'exceptions' array A list of ChangelogExceptions objects.
     */
    public static function addEntriesFromReport(array $entries, $writeToDisk = true, $returnExceptionsAsArrays = false) {
        $response = array('commits' => array(), 'exceptions' => array());

        $getException = function($key, $exception = null) use ($returnExceptionsAsArrays) {
            $exception = $exception ?: new ChangelogException($key);
            return ($returnExceptionsAsArrays) ? $exception->getProperties() : $exception;
        };

        if (!$entries) {
            $response['exceptions'][] = $getException('NO_ENTRIES_FOUND');
            return $response;
        }

        $commitsByAccountAndRefno = array();
        foreach($entries as $entry) {
            if (is_object($entry)) {
                $entry = (array)$entry;
            }
            if (!$entry || !is_array($entry)) {
                $response['exceptions'][] = $getException('INVALID_ENTRY');
                continue;
            }
            $entry = self::parseEntry($entry);
            $key = "{$entry['refno']}:{$entry['account']}:{$entry['email']}";
            unset($entry['email']);
            if (!array_key_exists($key, $commitsByAccountAndRefno)) {
                $commitsByAccountAndRefno[$key] = array();
            }
            $targets = $entry['targets'];
            if (!is_array($targets)) {
                $targets = array($targets);
            }
            unset($entry['targets']);
            foreach($targets as $target) {
                try {
                    $commitsByAccountAndRefno[$key][] = self::addEntry($target, $entry, $writeToDisk);
                }
                catch (ChangelogException $exception) {
                    $response['exceptions'][] = $getException(null, $exception);
                }
            }
        }

        foreach($commitsByAccountAndRefno as $key => $files) {
            list($refno, $account, $email) = explode(':', $key);
            $response['commits'][] = array('files' => array_values(array_unique($files)), 'account' => $account, 'refno' => $refno, 'email' => $email);
        }
        return $response;
    }

    /**
     * Adds a changelog entry to the framework or widget specified by $target.
     *
     * @param string $target Either 'framework' or a valid widget specifier.
     * @param array $entry Changelog array
     * @param bool $writeToDisk If FALSE, do not write to changelog.yml on disk.
     * @return string Returns the full path to the changelog specified by $target.
     * @throws ChangelogException If $target does not specify a writable changelog, or if $entry is invalid.
     */
    public static function addEntry($target, array $entry, $writeToDisk = true) {
        $entry = self::validateEntry($entry);
        $changelogID = $entry['changelogID'];
        $refno = $entry['refno'];
        try {
            list($target, $path) = self::getChangelogPath(str_replace(array("'", '"', ',', ' '), '', $target));
        }
        catch (ChangelogException $e) {
            throw new ChangelogException('INVALID_TARGET', $target, $changelogID, $refno);
        }
        if (!$changelog = self::getChangelogFromDisk($path)) {
            throw new ChangelogException('CHANGELOG_NOT_READABLE', $path, $changelogID, $refno);
        }
        $changelog = self::arrayToYaml(self::mergeEntry($entry, $changelog));
        if ($writeToDisk) {
            umask(0);
            if (!@file_put_contents($path, $changelog)) {
                throw new ChangelogException('CHANGELOG_NOT_WRITABLE', $path, $changelogID, $refno);
            }
            self::clearCache($target);
        }
        else if (!FileSystem::isReadableFile($path) || !is_writable($path)) {
            throw new ChangelogException('CHANGELOG_NOT_WRITABLE', $path, $changelogID, $refno);
        }
        return $path;
    }

    /**
     * Return specified changelog array, filtered within $min and $max version, grouped by version and category, and sorted by sortPosition, level and date.
     *
     * @param string $target Either 'framework' or a widget specifier (e.g. 'input/DateInput')
     * @param string|null $min The minimum version string ({major}.{minor}.{nano}, or {major}.{minor} or {major})
     * @param string|null $max The maximum version string ({major}.{minor}.{nano}, or {major}.{minor} or {major})
     * @return array|null
     * @throws ChangelogException If an invalid $target is specified.
     */
    public static function getFormattedChangelog($target, $min = null, $max = null) {
        $changelog = null;
        if ($changelogArray = self::getChangelogAsArray($target, $min, $max)) {
            list($changelog, $categories) = self::groupByMajorMinorAndStandardizeData($changelogArray);
            $changelog = self::sortEntries(self::groupByCategory($changelog, $categories));
        }
        return $changelog;
    }

    /**
     * Given an array of changelog entries grouped by version and category, sort by category, then by sortPosition, level and date
     * @param array $changelog The list of changelog entries
     * @return array
     */
    private static function sortEntries(array $changelog) {
        $sorted = array();
        foreach ($changelog as $version => $categorized) {
            foreach(self::$categories as $category) {
                if (isset($categorized[$category]) && $entries = $categorized[$category]) {
                    $sorted[$version][$category] = self::sortWithinCategory($entries);
                }
            }
        }
        return $sorted;
    }

    /**
     * Given an array of changelog entries, sort by:
     *   1. 'sortPosition' ('top', 'middle', 'bottom')
     *   2. 'level' ('major', 'minor', 'nano')
     *   3. 'date'
     * @param array $entries List of changelog entries
     * @return array
     */
    private static function sortWithinCategory(array $entries) {
        $priorities = self::$sortPriorities;
        $levelValues = self::$levelValues;
        $cmp = function($key, $a, $b) use ($priorities, $levelValues) {
            $a = $a[$key];
            $b = $b[$key];
            if ($key === 'sortPosition')
                $lookup = $priorities;
            else if ($key === 'level')
                $lookup = $levelValues;
            if (isset($lookup) && $lookup) {
                $a = array_key_exists($a, $lookup) ? $lookup[$a] : $lookup[strtolower($a)];
                $b = array_key_exists($b, $lookup) ? $lookup[$b] : $lookup[strtolower($b)];
            }
            else {
                $a = strtotime($a);
                $b = strtotime($b);
            }
            if ($a === $b)
                return 0;
            return ($a > $b) ? -1 : 1;
        };

        usort($entries, function($a, $b) use ($cmp) {
            if (($delta = $cmp('sortPosition', $a, $b)) !== 0)
                return $delta;
            if (($delta = $cmp('level', $a, $b)) !== 0)
                return $delta;
            return $cmp('date', $a, $b);
        });

        return $entries;
    }

    /**
     * Groups changelog entries by category
     * @param array $changelog List of changelog entries
     * @return array
     */
    private static function groupByCategory(array $changelog) {
        $grouped = array();
        foreach ($changelog as $version => $ungroupedEntries) {
            $entries = array();
            foreach($ungroupedEntries as $entry) {
                $category = $entry['category'];
                if (!isset($entries[$category]) || !$entries[$category]) {
                    $entries[$category] = array();
                }
                unset($entry['category']);
                $entries[$category][] = $entry;
            }
            $grouped[$version] = $entries;
        }
        return $grouped;
    }

    /**
     * Group changelogs by Major.Minor version, reformat some data (category, sortPosition),
     * and remove any entries that apply to future CX releases.
     * @param array $changelog Array of changelog entries
     * @return array First element in the returned array is the grouped changelogs. The second
     * element contains the unique categories of the entries.
     */
    private static function groupByMajorMinorAndStandardizeData(array $changelog) {
        require_once CPCORE . 'Internal/Libraries/Version.php';
        $currentRelease = new Version(VersionUtils::getCXVersionNumber());

        $consolidated = $categories = $processed = array();
        foreach ($changelog as $threeDigitVersion => $data) {
            if (!$data['release'] || $currentRelease->lessThan($data['release']))
                continue;
            $digits = explode('.', $threeDigitVersion);
            $version = "{$digits[0]}.{$digits[1]}";
            if (!array_key_exists($version, $consolidated)) {
                $consolidated[$version] = array();
            }
            foreach($data['entries'] as $entry) {
                if (!isset($entry['category']) || !isset($processed[$entry['category']]) || !$processed[$entry['category']]) {
                    $category = str_replace(' ', '_', strtoupper(isset($entry['category']) ? trim($entry['category']) : ''));
                    if (!in_array($category, self::$categories)) {
                        $category = 'OTHER';
                    }
                    $categories[] = $processed[isset($entry['category']) ? $entry['category'] : null] = $category;
                }
                $entry['category'] = $processed[isset($entry['category']) ? $entry['category'] : null];
                $entry['sortPosition'] = strtolower(isset($entry['sortPosition']) ? $entry['sortPosition'] : '');
                $consolidated[$version][] = $entry;
            }
        }
        return array($consolidated, array_unique($categories));
    }

    /**
     * Return $changelog updated where $entry['changelogID'] and $entry['refno'] matches an existing changelog entry.
     *
     * @param array $entry An array containing changelog data.
     * @param array $changelog The existing changelog as an array.
     * @return array|null Return $changelog with the entry specified by $entry['changelogID'] replaced.
     *                    Returns null if $entry['changelogID'] not specified, or if ID does not exist in $changelog.
     * @throws ChangelogException If $entry['changelogID'] is invalid.
     */
    private static function updateExistingEntry(array $entry, array $changelog) {
        if (($changelogID = $entry['changelogID']) && ($refno = $entry['refno'])) {
            if (!$changelogID = $entry['changelogID'] = intval($changelogID)) {
                throw new ChangelogException('INVALID_CHANGELOG_ID', $changelogID);
            }
            foreach(array_keys($changelog) as $version) {
                for($index = 0; $index < count($changelog[$version]['entries']); $index++) {
                    $changelogEntry = $changelog[$version]['entries'][$index];
                    if ($changelogID === (int) $changelogEntry['changelogID'] && $refno === $changelogEntry['refno']) {
                        unset($entry['release']);
                        $changelog[$version]['entries'][$index] = $entry;
                        return $changelog;
                    }
                }
            }
        }
    }

    /**
     * Parse a changelog entry from the QA report and return in the expected format.
     *
     * @param array $entry An array of incident and changelog data.
     * @return array|null An array upon success else null
     */
    private static function parseEntry(array $entry) {
        if (!$entry) return;

        $data = array(
            'refno' => $entry['refno'] ?: 0,
            'changelogID' => $entry['changelog_id'],
            'release' => Text::getSubstringBefore($entry['release'], ' (', $entry['release']),
            'account' => $entry['account'],
            'email' => $entry['email'],
            'date' => $entry['last_updated'] ?: $entry['created_time'],
            'description' => $entry['description'],
            'level' => strtolower($entry['level']),
            'category' => $entry['category'],
            'sortPosition' => $entry['sort_position'],
            'targets' => array_map('trim', explode("\n", $entry['targets'])),
        );
        if ($details = $entry['details']) {
            $data['details'] = self::parseDetails($details);
        }
        return $data;
    }

    /**
     * Parse the details textinput field which can contain 0 or many entries, one per line.
     *
     * @param string $text Changelog line to parse
     * @return array
     */
    private static function parseDetails($text) {
        $details = array();
        foreach(explode("\n", (string)$text) as $line) {
            if ($line = trim($line)) {
                $details[] = Text::beginsWith($line, '-') ? trim(substr($line, 1)) : $line;
            }
        }
        return $details;
    }

    /**
     * Verifies required attributes present in $entry array and adds 'date' if not specified.
     *
     * @param array $entry An array containing a changelog entry.
     * @return array
     * @throws ChangelogException If $entry is invalid.
     */
    private static function validateEntry(array $entry) {
        $required = array('description', 'level');
        foreach ($required as $key) {
            if (!$entry[$key]) {
                throw new ChangelogException('REQUIRED_KEY_MISSING', $key, $entry['changelogID'], $entry['refno']);
            }
        }
        if (!in_array($entry['level'], self::$levels)) {
            throw new ChangelogException('INVALID_LEVEL', $level, $entry['changelogID'], $entry['refno']);
        }
        if (!$entry['date']) {
            $entry['date'] = date('Y-m-d');
        }
        ksort($entry);
        return $entry;
    }

    /**
     * Merges in $entry to existing $changelog, incrementing the version key, and adding entry to appropriate version.
     * If the entry identified by the changelogID already exists, replace it with the new entry.
     *
     * @param array $entry A changelog entry array containing at least 'description' and 'level'.
     * @param array $changelog A changelog array having versions as keys.
     * @return array Changelog with new entry.
     */
    private static function mergeEntry(array $entry, array $changelog) {
        if ($updatedChangelog = self::updateExistingEntry($entry, $changelog)) {
            return $updatedChangelog;
        }

        $release = $entry['release'];
        unset($entry['release']);

        list($newVersion, $oldVersion) = self::calculateNextVersion(
            $entry['level'],
            array_map(function($a) {return $a['release'];}, $changelog),
            $release
        );
        $version = $oldVersion ?: $newVersion;
        if (!array_key_exists($version, $changelog)) {
            $changelog[$version] = array(
                'release' => (preg_match('@^\d{1,2}\.\d{1,2}$@', $release)) ? $release : '',
                'entries' => array()
            );
        }
        array_unshift($changelog[$version]['entries'], $entry);

        if ($oldVersion && $oldVersion !== $newVersion) {
            $changelog[$newVersion] = $changelog[$oldVersion];
            unset($changelog[$oldVersion]);
        }

        return self::sortVersionsByKey($changelog);
    }

    /**
     * Returns associative array ordered by versions in descending order.
     *
     * @param array $versions An associateve array having version as key.
     * @return array
     */
    private static function sortVersionsByKey(array $versions) {
        uksort($versions, "self::reverseCompareVersions");
        return $versions;
    }

    /**
     * Returns a flat array of versions in descending order.
     *
     * @param array $versions A list of versions.
     * @return array
     */
    private static function sortVersionList(array $versions) {
        usort($versions, array(self::class, "reverseCompareVersions"));
        return $versions;
    }

    /**
     * Sorting function used by usort and uksort to return versions in descending order.
     *
     * @param string $versionA First version
     * @param string $versionB Second version
     * @return mixed
     */
    private static function reverseCompareVersions($versionA, $versionB) {
        return VersionUtils::compareVersionNumbers($versionB, $versionA);
    }

    /**
     * Returns a two element array
     *     - element 0 is the 'next' version, or an existing version that $release is already part of.
     *     - element 1 (if specified) is the version the 'next' version should overwrite (e.g. 3.0.1)
     *
     * @param string $level Either 'major', 'minor' or 'nano'
     * @param array $versions An associative array having version as key, and 'release' as value.
     * @param null|string $release The specified release or null.
     * @return array
     */
    private static function calculateNextVersion($level, array $versions, $release = null) {
        $versionToBeReplaced = $existingVersion = null;
        $versions = self::sortVersionsByKey($versions);
        $keys = array_keys($versions);
        $maxVersion = $keys[0] ?: '1.0.1';
        $previousVersion = $keys[1];
        $lastReleasedVersion = self::getLastReleasedVersion($versions);

        if ($release && ($byRelease = array_flip($versions)) && array_key_exists($release, $byRelease)) {
            $existingVersion = $byRelease[$release];
        }

        if ($existingVersion && $existingVersion !== $maxVersion) {
            $newVersion = $existingVersion;
        }
        else if ($maxVersion === $lastReleasedVersion && $maxVersion === $existingVersion) {
            $alreadyIncremented = self::versionAlreadyIncremented($previousVersion, $maxVersion, $level);
            $newVersion = ($alreadyIncremented) ? $maxVersion : self::incrementVersion($maxVersion, $level);
            $versionToBeReplaced = ($versions[$previousVersion] === $release) ? $previousVersion : $maxVersion;
        }
        else {
            $alreadyIncremented = self::versionAlreadyIncremented($lastReleasedVersion, $maxVersion, $level);
            $newVersion = ($alreadyIncremented) ? $maxVersion : self::incrementVersion($maxVersion, $level);
            $currentRelease = $versions[$maxVersion];
            if (!($currentRelease && !$previousVersion)) {
                if (!$previousVersion || (!$release && $alreadyIncremented && !$currentRelease)) {
                    $versionToBeReplaced = $maxVersion;
                }
                else if (($release || $alreadyIncremented) && ($versions[$previousVersion] === $release)) {
                    $versionToBeReplaced = $previousVersion;
                }
            }
        }
        return array($newVersion, $versionToBeReplaced);
    }

    /**
     * Returns true if $newVersion has already been incremented from $oldVersion by specified $level.
     *
     * @param string|null $oldVersion Version in the form {major}.{minor}.{nano}
     * @param string $newVersion Version in the form {major}.{minor}.{nano}
     * @param string $level One of 'major', 'minor' or 'nano'
     * @return bool
     */
    private static function versionAlreadyIncremented($oldVersion, $newVersion, $level) {
        if (!$oldVersion || ($oldVersion === $newVersion)) {
            return false;
        }
        return (VersionUtils::compareVersionNumbers($newVersion, self::incrementVersion($oldVersion, $level)) !== -1);
    }

    /**
     * Returns the first version in $versions having a specified release.
     *
     * @param array $versions An associate array having version as key and release as value.
     * @return string|null First version having a release, or null.
     */
    private static function getLastReleasedVersion(array $versions) {
        foreach($versions as $version => $release) {
            if ($release) {
                return $version;
            }
        }
    }

    /**
     * Returns $version incremented by $level.
     *
     * @param string $version Version in the form {major}.{minor}.{nano}
     * @param string $level One of 'major', 'minor' or 'nano'
     * @return string Incremented version.
     */
    private static function incrementVersion($version, $level) {
        $digits = VersionUtils::versionToDigits($version, 3);
        $digits[array_search($level, self::$levels)]++;
        list($major, $minor, $nano) = $digits;
        if ($level === 'major') {
            return "$major.0.1";
        }
        if ($level === 'minor') {
            return "$major.$minor.1";
        }
        return "$major.$minor.$nano";
    }

    /**
     * Returns yaml from $array.
     *
     * @param array $array Array to convert
     * @return string|false Value YAML or false on failure
     */
    private static function arrayToYaml(array $array) {
        if ($yaml = @yaml_emit($array)) {
            return trim(substr($yaml, 3, -4));
        }
        return false;
    }

    /**
     * Returns an array from changelog.yml file specified by $path.
     *
     * @param string $path The absolute path to the changelog.yml file.
     * @return array|false
     */
    private static function getChangelogFromDisk($path) {
        return @yaml_parse_file($path);
    }

    /**
     * Returns a 2-element array of:
     *   1 - $target ('framework' or standardized widget name/path)
     *   2 - the absolute path to the $target changelog.yml file.
     *
     * @param string $target Either 'framework' or the name of a widget (e.g. 'standard/input/DateInput')
     * @return array
     * @throws ChangelogException if an invalid $target is specified.
     */
    private static function getChangelogPath($target) {
        if ($target === 'framework') {
            $path = DOCROOT . "/cp/core/framework/changelog.yml";
        }
        else if (($allWidgets = Widget\Registry::getAllWidgets()) &&
            (($widget = $allWidgets[$target]) || $widget = $allWidgets["standard/$target"])) {
            $target = $widget['relativePath'];
            $path = preg_replace("#/[0-9]+\.[0-9]+(\.[0-9]+)?$#", "", $widget['absolutePath']) . "/changelog.yml";
        }
        if (!isset($path)) {
            throw new ChangelogException('INVALID_TARGET', $target);
        }
        return array($target, $path);
    }

    /**
     * Remove the cache entries associated with $target.
     *
     * @param string $target Either 'framework' or the name of a widget (e.g. 'input/DateInput')
     * @return bool True if cache entries existed in static $cacheKeys, else false.
     */
    private static function clearCache($target) {
        self::initializeCache();
        $cacheKey = "changelog.$target";
        $cacheEntriesExisted = false;
        foreach(array_keys(self::$cacheKeys) as $index) {
            $key = self::$cacheKeys[$index];
            if ($key === $cacheKey || Text::beginsWith($key, $cacheKey)) {
                self::$cache->set($key, null);
                unset(self::$cacheKeys[$index]);
                $cacheEntriesExisted = true;
            }
        }
        self::$cache->set($cacheKey, null); // Clear $target in case it was not in $cacheKeys

        return $cacheEntriesExisted;
    }

    /**
     * Initializes the cache
     */
    private static function initializeCache() {
        if (!self::$cache) {
            self::$cache = new \RightNow\Libraries\Cache\Memcache(1200 /* 20 min. */);
        }
    }

    /**
     * Return filtered and sorted changlog from cache, adding to the cache where necessary.
     *
     * @param string $path The absolute path to the changelog.yml file.
     * @param string $target Either 'framework' or the name of a widget (e.g. 'input/DateInput')
     * @param string|null $min The minimum version string ({major}.{minor}.{nano}, or {major}.{minor} or {major})
     * @param string|null $max The maximum version string ({major}.{minor}.{nano}, or {major}.{minor} or {major})
     * @return array|null
     */
    private static function getChangelogFromCache($path, $target, $min = null, $max = null) {
        self::initializeCache();
        $cacheKey = $filteredCacheKey = "changelog.$target";
        if ($min !== null || $max !== null) {
            $filteredCacheKey .= ".$min.$max";
        }

        if (!$changelog = self::$cache->get($filteredCacheKey)) {
            if (!($changelog = self::$cache->get($cacheKey)) && FileSystem::isReadableFile($path)) {
                $changelog = self::getChangelogFromDisk($path);
            }

            if ($changelog) {
                $changelog = self::filterChangelog($changelog, $min, $max);
                self::$cache->set($filteredCacheKey, $changelog);
                self::$cacheKeys[] = $filteredCacheKey;
            }
        }
        return $changelog ?: null;
    }

    /**
     * Return a changelog array, filtered within $min and $max versions, and sorted highest to lowest.
     *
     * @param array $changelog List of changelog entries
     * @param string|null $min The minimum version string ({major}.{minor}.{nano}, or {major}.{minor} or {major})
     * @param string|null $max The maximum version string ({major}.{minor}.{nano}, or {major}.{minor} or {major})
     * @return array
     */
    private static function filterChangelog(array $changelog, $min = null, $max = null) {
        $filtered = array();
        foreach (self::filterVersions(array_keys($changelog), $min, $max) as $version) {
            $filtered[$version] = $changelog[$version];
        }
        return $filtered;
    }

    /**
     * Given a list of versions, return them filtered within $min and $max, and sorted highest to lowest.
     *
     * @param array $versions An array of version strings. E.g. array('3.2.1', '3.1.2', '3.1.1')
     * @param string|null $min The minimum version string ({major}.{minor}.{nano}, or {major}.{minor} or {major})
     * @param string|null $max The maximum version string ({major}.{minor}.{nano}, or {major}.{minor} or {major})
     * @return array
     * @throws ChangelogException If value passed in for $min is greater than $max
     */
    private static function filterVersions(array $versions, $min = null, $max = null) {
        $getVersionArray = function($version, $padWith = 0) {
            $digits = array_pad(explode('.', ($version) ?: ''), 3, 0);
            $versionString = '';
            foreach (range(0, 2) as $i) {
                $digit = $digits[$i];
                if (!$digit && $digit !== '0') {
                    $digit = $padWith;
                }
                $versionString .= "$digit.";
            }
            return VersionUtils::versionToDigits(substr($versionString, 0, -1), 3);
        };
        $minArray = $getVersionArray($min);
        $maxArray = $getVersionArray($max, 999);
        if ($maxArray < $minArray) {
            throw new ChangelogException('MIN_GREATER_THAN_MAX', "min: '$min' max: '$max'");
        }
        $versions = array_filter($versions, function($version) use ($minArray, $maxArray) {
            $versionArray = VersionUtils::versionToDigits($version, 3);
            return $versionArray >= $minArray && $versionArray <= $maxArray;
        });

        return self::sortVersionList($versions);
    }
}

/**
 * Custom Exception class used to simplify the calling and verification of exceptions thrown by the Changelog class.
 * ChangelogExceptions have convenience methods to retrieve error level, error key, changelogID and refno.
 */
final class ChangelogException extends \Exception {
    private $key, $level, $changelogID, $refno;
    private $errorCodes = array(
        // warnings
        'NO_ENTRIES_FOUND'       => array('level' => 'warning', 'message' => 'No entries to add'),
        // errors
        'CHANGELOG_NOT_READABLE' => array('level' => 'error',   'message' => 'Changelog missing or not readable'),
        'CHANGELOG_NOT_WRITABLE' => array('level' => 'error',   'message' => 'Changelog is not writable'),
        'REQUIRED_KEY_MISSING'   => array('level' => 'error',   'message' => 'Required key missing'),
        'MIN_GREATER_THAN_MAX'   => array('level' => 'error',   'message' => 'The minimum version cannot exceed the maximum version'),
        'INVALID_CHANGELOG_ID'   => array('level' => 'error',   'message' => 'Invalid changelog ID'),
        'INVALID_TARGET'         => array('level' => 'error',   'message' => "Target should be 'framework' or a valid widget specifier"),
        'INVALID_ENTRY'          => array('level' => 'error',   'message' => 'Entries must be a non-empty array'),
        'INVALID_LEVEL'          => array('level' => 'error',   'message' => "Level must specified, and be one of 'major', 'minor' or 'nano'"),
    );

    /**
     * ChangelogException constructor
     * @param string $key A key from $errorCodes above, or a generic error message.
     * @param mixed $value An optional value that will be appended to the error message.
     * @param int|null $changelogID The changelogID as stored in the QA site.
     * @param string|null $refno The incident reference # as stored in the QA site.
     */
    function __construct($key, $value = null, $changelogID = null, $refno = null) {
        $this->changelogID = $changelogID;
        $this->refno = $refno;
        if ($entry = $this->errorCodes[$key]) {
            list($this->key, $this->level, $msg) = array($key, $entry['level'], $entry['message']);
        }
        else {
            list($this->key, $this->level, $msg) = array('GENERIC', 'error', $key);
        }
        parent::__construct($msg . (($value !== null) ? " : $value" : ''));
    }

    function __toString() {
        return "[{$this->refno} : {$this->changelogID}] {$this->message}";
    }

    function getProperties() {
        return array(
            'refno' => $this->refno,
            'changelogID' => $this->changelogID,
            'level' => $this->level,
            'key' => $this->key,
            'message' => $this->message,
        );
    }

    function getKey() {
        return $this->key;
    }

    function getLevel() {
        return $this->level;
    }

    function getChangelogID() {
        return $this->changelogID;
    }

    function getRefno() {
        return $this->refno;
    }
}
