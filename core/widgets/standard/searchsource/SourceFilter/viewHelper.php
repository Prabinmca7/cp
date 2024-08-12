<?

namespace RightNow\Helpers;

class SourceFilterHelper extends \RightNow\Libraries\Widget\Helper {
    /**
     * Returns the item at the specified index
     * within the split-apart $labels string.
     * @param int $index Index of the item to retrieve
     * @param string $labels String containing comma-separated labels
     * @return string Item at the specified index
     */
    function labelForOption ($index, $labels) {
        $labels = explode(',', $labels);
        return $labels[$index];
    }

    /**
     * Indicates whether the given option matches
     * the given id
     * @param object $option Option
     * @param int $selectedID Selected id
     * @return boolean True if the option's id matches
     */
    function isSelected ($option, $selectedID) {
        return $option->ID == $selectedID;
    }
}
