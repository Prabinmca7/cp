<?php

namespace RightNow\Internal\Libraries\Widget;

use RightNow\Utils\Framework,
    RightNow\Utils\FileSystem;

/**
 * Handles the loading / retrieval of extension content
 * and overridding behavior for extensions in customer and
 * core directories.
 */
class ExtensionLoader {
    /**
     * Cache of parsed extensions.yml.
     * Public for ease of testing and because
     * this class is internal.
     * @var array
     */
    public static $extensionRegistry;

    /**
     * Path to core directory to look for extensions.
     * @var string
     */
    private $coreDir;

    /**
     * Path to customer directory to look for extensions.
     * @var string
     */
    private $customerDir;

    /**
     * Name of extension's key name (in extensions.yml).
     * @var string
     */
    private $extensionName;

    /**
     * Constructor.
     * @param string $extensionName Type of extension (key from extensions.yml file)
     * @param string $directoryName Customer/core directory to look for extensions within
     */
    function __construct ($extensionName, $directoryName) {
        $this->extensionName = $extensionName;
        $this->coreDir = CPCORE . ucfirst(strtolower($directoryName));
        $this->customerDir = APPPATH . strtolower($directoryName);
    }

    /**
     * Loads the given extension's php file.
     * IF the core extension exists, it's loaded.
     *   - IF the custom extension is registered to extend, it's loaded
     * ELSE IF the custom extension exists, it's loaded.
     * @param string $registeredName Name of extension file in extensions.yml
     *                                (e.g. Foo)
     * @param string $filename Filename (or path) to the file
     *                             (e.g. Foo.php)
     * @return array       Keys and values indicating which content was loaded:
     *                          empty array: nothing loaded
     *                          -'core' key: (boolean)
     *                          -'custom' key: (boolean)
     */
    function loadExtension ($registeredName, $filename) {
        $loaded = array();

        if ($this->coreFileExists($filename)) {
            // First load core extension…
            $loaded['core'] = $this->loadContentFromCoreDirectory($filename);

            if ($this->extensionIsRegistered($registeredName) && $this->customerFileExists($filename)) {
                // …Then load custom one, if it's registered.
                $loaded['custom'] = $this->loadContentFromCustomerDirectory($filename);
            }
        }
        else if ($this->customerFileExists($filename)) {
            $loaded['custom'] = $this->loadContentFromCustomerDirectory($filename);
        }

        return $loaded;
    }

    /**
     * Retrieves the given extension's file.
     * IF the extension is registered, the custom content is used.
     * ELSE IF the core content exists, it's used.
     * ELSE IF the custom content exists, it's used.
     * @param string $registeredName Name of extension in extensions.yml
     *                                (e.g. Foo.Bar.Baz)
     * @param string $filename       Filename (or path) to the file
     *                                (e.g. Foo/Bar/Baz.php)
     * @return string|boolean                 string content or boolean false if
     *                                               it cannot be retrieved.
     */
    function getExtensionContent ($registeredName, $filename) {
        if ($this->extensionIsRegistered($registeredName) &&
            ($content = $this->getContentFromCustomerDirectory($filename)) !== false) {
            return $content;
        }

        if (($content = $this->getContentFromCoreDirectory($filename)) === false) {
            $content = $this->getContentFromCustomerDirectory($filename);
        }

        return $content;
    }

    /**
     * Determines whether the given extension is registered in extensions.yml.
     * @param string $name Key name
     * @return boolean       Whether the extension's present
     */
    function extensionIsRegistered ($name) {
        if (!self::$extensionRegistry) {
            self::$extensionRegistry = Framework::getCodeExtensions();
        }
        return is_array(self::$extensionRegistry)
            && isset(self::$extensionRegistry[$this->extensionName])
            && is_array(self::$extensionRegistry[$this->extensionName])
            && in_array($name, self::$extensionRegistry[$this->extensionName]);
    }

    /**
     * Gets the content from the specified custom dir.
     * @param string $name File name
     * @return string|boolean View content or false if the file path is invalid
     */
    function getContentFromCustomerDirectory ($name) {
        return @file_get_contents("{$this->customerDir}/{$name}");
    }

    /**
     * Gets the content from the specified core dir.
     * @param string $name File name
     * @return string|boolean View content or false if the file path is invalid
     */
    function getContentFromCoreDirectory ($name) {
        return @file_get_contents("{$this->coreDir}/{$name}");
    }

    /**
     * Gets the content from the specified custom dir.
     * @param string $name File name
     * @return boolean T if included, F if not included
     */
    function loadContentFromCustomerDirectory ($name) {
        return (bool) include_once "{$this->customerDir}/{$name}";
    }

    /**
     * Loads the content from the specified core dir.
     * @param string $name File name
     * @return boolean T if included, F if not included
     */
    function loadContentFromCoreDirectory ($name) {
        return IS_HOSTED ? true : (bool) include_once "{$this->coreDir}/{$name}";
    }

    /**
     * Determines if the file exists in the core dir.
     * @param string $name File name
     * @return boolean       If the file exists and is readable
     */
    function coreFileExists ($name) {
        return IS_HOSTED ? class_exists(Helpers\Loader::coreHelperClassName(basename($name, ".php"))) : FileSystem::isReadableFile("{$this->coreDir}/{$name}");
    }

    /**
     * Determines if the file exists in the customer dir.
     * @param string $name File name
     * @return boolean       If the file exists and is readable
     */
    function customerFileExists ($name) {
        return FileSystem::isReadableFile("{$this->customerDir}/{$name}");
    }
}
