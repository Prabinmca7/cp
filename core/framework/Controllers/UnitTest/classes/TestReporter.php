<?

class TestReporter {
    public static $reporters = array(
        'TAP',     // plaintext
        'Regular', // plaintext
        'CPHTML',  // html
    );

    /**
     * Loads and instantiates the specified test reporter.
     * @param  string $which,... Reporter name (from `$reporters`)
     *                           Additional params are passed to reporter's constructor
     * @return object        instantiated reporter
     * @throws Exception If the specified reporter doesn't exist
     */
    static function reporter ($which) {
        if (in_array($which, self::$reporters)) {
            $className = "{$which}Reporter";

            require_once __DIR__ . "/reporters/{$className}.php";

            $class = new ReflectionClass($className);
            return $class->newInstanceArgs(array_filter(array_slice(func_get_args(), 1)));
        }

        throw new \Exception("Unknown '$which' Reporter");
    }
}
