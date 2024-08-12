<?php

namespace RightNow\Internal\Libraries\Cache;

/**
 * Base class that establishes read-only class properties as well as
 * _get{property_name}() methods that are lazy loaded and cached for subsequent calls.
 *
 * If an undefined property is called that has a corresponding _get{property_name}
 * method, the method will be called the first time the property is invoked
 * with the value of the method being assigned as a property for subsequent calls.
 * This allows for lazy retrieval of class properties as well as simply calling
 * $version->{property_name} instead of something like $version->getAttribute({property_name}).
 */
class CachedMethods{
    private $readOnlyProperties = array();

    public function __construct(){}

    public function __get($attr) {
        $method = '_get' . ucfirst($attr);
        if (array_key_exists($attr, $this->readOnlyProperties)) {
            return $this->readOnlyProperties[$attr];
        }
        else if (\RightNow\Utils\Text::beginsWith($attr, '_get') || !method_exists($this, $method)) {
            throw new InvalidAttributeException(get_class($this) . "->$attr");
        }
        $this->readOnlyProperties[$attr] = $this->$method();
        return $this->readOnlyProperties[$attr];
    }

    public function __set($attr, $value) {
        if (array_key_exists($attr, $this->readOnlyProperties)) {
            throw new UnsettablePropertyException(get_class($this) . "->$attr");
        }
        else {
            $this->readOnlyProperties[$attr] = $value;
        }
    }
}

final class InvalidAttributeException extends \RightNow\Internal\Exception {
    public function __construct($message, $code=0) {
        parent::__construct("Property or method does not exist: $message", $code);
    }

    public function __toString() {
        return "<b style='color:red'>$this->message</b>";
    }
}

final class UnsettablePropertyException extends \RightNow\Internal\Exception {
    public function __construct($message, $code=0) {
        parent::__construct("Property cannot be set: $message", $code);
    }

    public function __toString() {
        return "<b style='color:red'>$this->message</b>";
    }
}