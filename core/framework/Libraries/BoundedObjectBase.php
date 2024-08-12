<?php
namespace RightNow\Libraries;

/**
* Protects extended classes against arbitrary addition of properties by using PHP's magic __get and __set methods. Extended classes must use protected properties.
*/
class BoundedObjectBase {
    /**
    * Returns the specified property if it exists. Subclasses must use protected properties.
    * @param string $name Name of property to get
    * @return mixed The value of the property specified
    * @throws \Exception If the specified property doesn't exist for the object
    */
    public function __get($name) {
        if(property_exists($this, $name))
            return $this->$name;
        throw new \Exception("Member variable $name does not exist in this object");
    }

    /**
    * Sets the specified property if it exists. Subclasses must use protected properties.
    * @param string $name Name of property to set
    * @param mixed $value Value to set
    * @return mixed The value being set
    * @throws \Exception If the specified property doesn't exist for the object
    */
    public function __set($name, $value) {
        if(property_exists($this, $name))
            return $this->$name = $value;
        throw new \Exception("Member variable $name does not exist in this object");
    }
}