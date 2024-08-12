<?php

namespace RightNow\Internal\Libraries\Widget\ViewPartials;

use RightNow\Utils\Text,
    RightNow\Internal\Utils\Widgets,
    RightNow\Internal\Utils\WidgetViews;

/**
 * Represents a partial view contained within
 * - the widget's folder (non-optimized)
 * - a method on the widget class (optimized)
 */
class WidgetPartial extends Partial {
    public static $canSelfRender = false;
    /**
     * Returns the name of the widget method that's expected
     * to render the view partial.
     * @return string widget method name
     * @see  RightNow\Internal\Libraries\Deployer#createWidgetViewCode
     */
    protected function getOptimizedContents () {
        return Widgets::generateWidgetFunctionName(Widgets::getWidgetRelativePath($this->relativePathToParentView), "_{$this->name}");
    }

    /**
     * Gets the view file from a widget dir.
     * @return string|boolean view or false if the file can't be retrieved
     */
    protected function getNonOptimizedContents () {
        return $this->substituteBlocks();
    }

    /**
     * Prefix for standard and custom widgets.
     * @return string absolute prefix
     */
    private function getAbsolutePrefix () {
        return (Text::beginsWith($this->relativePathToParentView, 'custom/')) ? APPPATH . 'widgets/' : CORE_WIDGET_FILES;
    }

    /**
     * Substitute out rn:blocks
     * @return string view with rn:blocks substituted out
     */
    private function substituteBlocks () {
        return WidgetViews::combinePartials($this->name . self::FILE_NAME_SUFFIX, $this->relativePathToParentView);
    }
}
