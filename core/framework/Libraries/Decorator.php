<?php

namespace RightNow\Libraries;

use RightNow\Utils\Text,
    RightNow\Utils\Filesystem;

/**
 * Functions for adding decorators to Connect objects
 */
class Decorator {
    /**
     * Add the specified Decorator object(s) onto the
     * specified Connect object instance.
     *
     *      \RightNow\Libraries\Decorator::decorate($contact, 'Permission/UserPermissions', 'Validate/UserValidation');
     *      $contact->UserPermissions->hasPermission();
     *      $contact->UserValidation->validate();
     *
     *      \RightNow\Libraries\Decorator::decorate($contact, 'Permission/UserPermissions', array('class' => 'Validate/UserValidation', 'property' => 'Validation');
     *      $contact->UserPermissions->hasPermission();
     *      $contact->Validation->validate();
     *
     * Additionally, any number of string Decorator names can be passed in as extra parameters. If a string is
     * provided it will use the last segment of the class name as the property. If an array is provided it's expected to
     * have a 'class' and 'property' values where the class field is the decorator to load and the property field is the
     * name to use.
     *
     * @param object $connectObj Connect object instance
     * @return object The $connectObj with decorators loaded; the original $connectObj
     *                            reference is also modified
     * @throws \Exception If there's a problem loading a decorator onto $connectObj or the property name already exists on the $connectObj
     */
    static function add($connectObj) {
        if ($decorators = array_slice(func_get_args(), 1)) {
            foreach (array_unique($decorators) as $decorator) {
                if(is_array($decorator)){
                    self::decorate($connectObj, $decorator['class'], $decorator['property']);
                }
                else{
                    self::decorate($connectObj, $decorator);
                }
            }
        }

        return $connectObj;
    }

    /**
     * Removes the specified Decorator properties from
     * the specified Connect object instance.
     *
     * Additionally, any number of string Decorator names can be passed in as extra parameters. If a string is
     * provided it will use the last segment of the class name as the property. If an array is provided it's expected to
     * have a 'class' and 'property' values where the class field is the decorator to load and the property field is the
     * name to use.
     *
     * @param  object $connectObj Connect object instance
     * @return boolean True if all Decorators were removed, False otherwise
     */
    static function remove($connectObj) {
        if (!$decorators = array_slice(func_get_args(), 1)) return false;

        $decorators = array_unique($decorators);
        $removed = 0;

        foreach ($decorators as $decorator) {
            if (Text::stringContains($decorator, '/')) {
                $decorator = explode('/', $decorator);
                $decorator = end($decorator);
            }
            if (self::decoratorExistsOnObject($decorator, $connectObj)) {
                unset($connectObj->{$decorator});
                $removed++;
            }
        }

        return $removed === count($decorators);
    }

    /**
     * Decorates $connectObj with the specified decorator class.
     * @param  object      $connectObj   Connect object to decorate
     * @param  string      $name         Class name to decorate with
     * @param  string|null $propertyName Name to use instead of basename of class as decorator property
     * @return boolean            T if $connectObj was decorated, F otherwise
     * @throws \Exception If the name of the decorater being added already exists on the object and isn't an existing decorator
     */
    private static function decorate ($connectObj, $name, $propertyName = null) {
        $decoratorName = $propertyName ?: basename($name);

        // Don't override existing properties.
        if (property_exists($connectObj, $decoratorName)) {
            //Only throw an exception if an existing property exists and it's not a decorater instance
            if(!$connectObj->{$decoratorName} instanceof \RightNow\Decorators\Base){
                throw new \Exception("Failed to apply decorator $decoratorName because a property with the name $decoratorName already exists.");
            }
            return;
        }
        return (self::applyStandardDecoratorAndCustomOverride($connectObj, $name, $propertyName) || self::applyCustomDecorator($connectObj, $name, $propertyName));
    }

    /**
     * Decorates with a standard decorator and optionally a custom one, if
     * it's configured so.
     * @param  object      $connectObj   Connect object to decorate
     * @param  string      $name         Class name to decorate with
     * @param  string|null $propertyName Name to use instead of basename of class as decorator property
     * @return boolean            T if decorated, F if not
     */
    private static function applyStandardDecoratorAndCustomOverride ($connectObj, $name, $propertyName = null) {
        $decoratorName = basename($name);

        if (self::loadFile($name, CPCORE . "Decorators/{$name}.php")) {
            $standardClassName = "\\RightNow\\Decorators\\{$decoratorName}";
            (self::applyCustomOverride($connectObj, $name, $standardClassName, $propertyName) ||
                self::applyStandardDecorator($connectObj, $propertyName ?: $decoratorName, $standardClassName));
            return true;
        }

        return false;
    }

    /**
     * Decorates with a custom decorator that's configured to do so.
     * @param  object      $connectObj     Connect object to decorate
     * @param  string      $name           Class name to decorate with
     * @param  string      $mustExtendFrom Standard decorator that the custom decorator must extend from
     * @param  string|null $propertyName   Name to use instead of basename of class as decorator property
     * @return boolean                 T if decorated, F if not
     * @throws \Exception If custom decorator is not named or extended correctly
     */
    private static function applyCustomOverride ($connectObj, $name, $mustExtendFrom, $propertyName = null) {
        $overrides = \RightNow\Internal\Utils\Framework::getCodeExtensions('decoratorExtensions');
        if ($overrides && $overrides[$name]) {
            // There's a custom override.
            self::loadFile($name, APPPATH . "decorators/" . $overrides[$name] . ".php", true);

            $className = "\\Custom\\Decorators\\{$overrides[$name]}";

            if (!class_exists($className)) {
                throw new \Exception("Custom decorator must have $className as its class name.");
            }

            $decoratorInstance = new $className($connectObj);

            if (!$decoratorInstance instanceof $mustExtendFrom) {
                throw new \Exception("Custom decorator $className must extend from $mustExtendFrom");
            }

            $decoratorName = $propertyName ?: basename($name);
            $connectObj->{$decoratorName} = $decoratorInstance;

            return true;
        }
        return false;
    }

    /**
     * Sets the property on the connect object to decorate.
     * @param  object $connectObj        Connect object to decorate
     * @param  string $decoratorName     Name of class to decorate with
     * @param  string $standardClassName Class name of standard decorator class
     */
    private static function applyStandardDecorator ($connectObj, $decoratorName, $standardClassName) {
        $connectObj->{$decoratorName} = new $standardClassName($connectObj);
    }

    /**
     * Loads a custom decorator file and sets the property on
     * the connect object to decorate.
     * @param  object      $connectObj   Connect object to decorate
     * @param  string      $name         Name of class to decorate with
     * @param  string|null $propertyName Name to use instead of basename of class as decorator property
     */
    private static function applyCustomDecorator ($connectObj, $name, $propertyName = null) {
        self::loadFile($name, APPPATH . "decorators/{$name}.php", true);

        $decoratorName = basename($name);
        $className = "\\Custom\\Decorators\\{$decoratorName}";
        $connectObj->{$propertyName ?: $decoratorName} = new $className($connectObj);
    }

    /**
     * Determines whether the specified decorator name
     * exists on the given object and is of a decorator class.
     * @param  string $decorator  Name of decorator property
     * @param  object $connectObj Connect object being checked
     * @return boolean             T if the decorator exists, F otherwise
     */
    private static function decoratorExistsOnObject ($decorator, $connectObj) {
        if (property_exists($connectObj, $decorator)) {
            $className = get_class($connectObj->{$decorator});
            return Text::beginsWith($className, 'RightNow\Decorators') || Text::beginsWith($className, 'Custom\Decorators');
        }
        return false;
    }

    /**
     * Ensures that the given file is readable and `require_once`s it.
     * @param  string  $name       Name of decorator
     * @param  string  $path       Path to decorator file
     * @param  boolean $failLoudly If the file isn's readable, whether to throw an exception
     * @return boolean              T if the file was loaded successfully. F if not.
     * @throws \Exception If $failLoudly is T and the file isn't readable
     */
    private static function loadFile($name, $path, $failLoudly = false) {
        if (!Filesystem::isReadableFile($path)) {
            if ($failLoudly) {
                throw new \Exception("Could not load decorator $name");
            }
            return false;
        }
        require_once $path;

        return true;
    }
}
