<?php
namespace RightNow\Internal\Libraries;
require_once CPCORE . 'Internal/Libraries/Cache/CachedMethods.php';

/**
 * Version class for working with product versions (E.g. 10.2  or "February `10").
 * Useful for validating version strings, providing common attributes (E.g. Version->monthName)
 * and for performing version comparisons (E.g. Version('10.11')->greaterThan('10.9')).
 * As this extends CachedMethods, any _get<property>() methods can be called
 * as regular properties/attributes (E.g. $v->monthName insted of $v->_getMonthName()).
 */
final class Version extends \RightNow\Internal\Libraries\Cache\CachedMethods {
    /**
     * Constructor
     * @param string $input Version number or name.
     * @throws \Exception If $input is not a recognized product version.
     */
    public function __construct($input) {
        parent::__construct();
        $this->input = $input;
        list($this->version, $this->versionName) = \RightNow\Internal\Utils\Version::getVersionNumberAndName($input);
        if ($this->version === null || $this->versionName === null)
            throw new \Exception("Not a recognized version: $input");
    }

    public function __toString() {
        return '{' . $this->version . ' : ' . $this->versionName . '}';
    }

    public function _getDigits() {
        return \RightNow\Internal\Utils\Version::versionToDigits($this->version);
    }

    public function _getVersionNumber() {
        return $this->version;
    }

    // Return 2 digit year
    public function _getYear() {
        $elements = explode('.', $this->version);
        return $elements[0];
    }

    // Return 4 digit year
    public function _getFullYear() {
        return '2' . str_pad($this->year, 3, '0', STR_PAD_LEFT);
    }

    // Return month number
    public function _getMonth() {
        $elements = explode('.', $this->version);
        return $elements[1];
    }

    public function _getMonthName() {
        $elements = explode(' ', $this->versionName);
        return $elements[0];
    }

    public function greaterThan($other) {
        return $this->compare($other) === 1;
    }

    public function lessThan($other) {
        return $this->compare($other) === -1;
    }

    public function equals($other) {
        return $this->compare($other) === 0;
    }

    /**
     * Compares two versions.
     * NOTE: Ideally you would do this by over-riding the comparison
     *       operator allowing you to perform direct class comparisons
     *       (E.G. <Version instance 1> > <Version instance 2>, but
     *       haven't found a way to do that yet (no magic __cmp() method).
     *
     * @param string|object $other Version string (E.G. 10.2) or Version object.
     * @return int The values 0 if this version === other version
     *                   1 if this version > other version
     *                  -1 if this version < other version
     */
    public function compare($other) {
        $other = $this->toVersion($other);
        if ($this->digits === $other->digits)
            return 0;
        else if ($this->digits > $other->digits)
            return 1;
        else
            return -1;
    }

    /**
     * Converts a version number into an instance of the Version class
     * @param mixed $version Version number, version name or existing Version instantiation.
     * @return object Version instantiation.
     */
    public static function toVersion($version) {
        if (is_object($version) && get_class($version) === 'RightNow\Internal\Libraries\Version')
            return $version;
        return new Version($version);

    }
}