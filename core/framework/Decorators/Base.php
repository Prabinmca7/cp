<?

namespace RightNow\Decorators;

/**
 * Base Decorator from which all other Decorators should extend from.
 */
abstract class Base {
    protected $connectObj;

    /**
     * Constructor.
     * @param object $connectObj A Connect object instance
     */
    function __construct($connectObj) {
        $this->verifyTypes($connectObj);

        $this->connectObj = $connectObj;
        $this->decoratorAdded();
    }

    /**
     * Hook method that sub-classes can optionally
     * implement.
     * `$this->connectObject` will have been assigned
     * to the Connect object instance.
     * This method exists as an alternative to brittle
     * constructor overriding.
     */
    protected function decoratorAdded() {}

    /**
     * Verifies that a type that the subclasses registers
     * in the `$connectTypes` property corresponds to the
     * supplied Connect object instance.
     * @param  object $connectObj A Connect object instance
     * @throws \Exception If The given object's type doesn't
     *         match a registered type
     */
    private function verifyTypes($connectObj) {
        if(method_exists($connectObj, 'getMetadata')){
            $type = $connectObj::getMetadata()->COM_type;
        }
        else{
            $type = get_class($connectObj);
        }

        if (array_search($type, $this->connectTypes) === false) {
            throw new \Exception(sprintf("The specified aspect, %s, does not support %s objects", $this->className(), $type));
        }
    }

    /**
     * Get the current class name, extracting the namespace.
     * @return string class name
     */
    private function className () {
        $namespacedClass = explode("\\", get_class($this));

        return end($namespacedClass);
    }
}
