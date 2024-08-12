<?php

/*
 * This class exists to serve as a base class for exception classess which we
 * only want "internal" code to create.
 *
 * HOWTO use this class:
 * 1. Derive an exception class from it.
 * 2. Mark the class final.
 * 3. Be sure to call parent::__construct($message, $code).
 * 4. Call the methods normally.
 * 5. You probably want to mark the internal class as excluded in Doxyfile and
 *    hidden in FileSystemHandler.php.
 */

namespace RightNow\Internal;

class Exception extends \Exception {
    public function __construct($message, $code=0) {
        parent::__construct($message, $code);
    }
}
