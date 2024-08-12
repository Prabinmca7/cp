<?php

namespace RightNow\Internal\Libraries\Widget\ViewPartials;

use RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Utils\Framework,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Utils\Framework as InternalFramework;

/**
 * Contains functionality for view partial rendering.
 * Usage:
 *
 *      $handler = new ViewPartialHandler( 'PartialForWidgetA', 'custom/relative/path/to/WidgetA' );
 *      echo $handler->render( array ( 'data' => 'that the view expects' ), $instanceOfWidgetA );
 *
 * If an error is encountered, the handler instance can be polled for validity and its errors can be
 * retrieved. When there's an error, #render returns FALSE.
 *
 *      if ( ! $handler->isValid() ) {
 *          echo implode( ', ', array_map( function ($e) { return $e['message']; }, $handler->getErrors() ) );
 *      }
 */
class Handler {
    private $view;
    private $viewName = '';

    /**
     * Takes in the view name and relative path to view being rendered.
     * @param string $viewName             Name of file (without any file extensions)
     * @param string $relativePathToWidget Relative path to widget (e.g. standard/feedback/AnswerFeedback/1.1/)
     * @throws \Exception If $viewName is invalid
     */
    function __construct ($viewName, $relativePathToWidget) {
        if ($error = $this->viewNameIsInvalid($viewName)) throw new \Exception($error);

        $this->viewName = $viewName;

        $typeOfPartial = $this->getTypeOfPartialContent();
        $this->view = new $typeOfPartial($this->viewName, $relativePathToWidget);
    }

    /**
     * Getter for private instance properties.
     * @param string $name Property name
     * @return Mixed       property value or null if it doesn't exist
     */
    function __get ($name) {
        if (property_exists($this, $name)) return $this->{$name};
    }

    /**
     * The view being requested is a shared view partial if
     * it begins with 'Partials.'.
     * @return boolean Whether the view is shared or not
     */
    private function viewIsSharedPartial () {
        return Text::beginsWith($this->viewName, 'Partials.');
    }

    /**
     * Validates that the view name is correct.
     * Makes sure there are no nefarious characters or file extensions being passed in.
     * @param string $name View name
     * @return boolean       T if correct, F if invalid
     */
    private function viewNameIsInvalid ($name) {
        if (!$name) {
            return Config::getMessage(A_VIEW_PARTIAL_NAME_IS_REQUIRED_MSG);
        }
        if (Text::stringContains($name, '..') || Text::stringContains($name, '/')) {
            return Config::getMessage(VIEW_PARTIAL_NAMES_CONTAIN_SLASH_CMD);
        }

        return false;
    }

    /**
     * The object to instantiate for the view being requested.
     * @return string Fully-namespaced class name
     */
    private function getTypeOfPartialContent () {
        return $this->viewIsSharedPartial()
            ? '\RightNow\Internal\Libraries\Widget\ViewPartials\SharedPartial'
            : '\RightNow\Internal\Libraries\Widget\ViewPartials\WidgetPartial';
    }
}
