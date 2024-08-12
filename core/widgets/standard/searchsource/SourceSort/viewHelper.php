<?

namespace RightNow\Helpers;

class SourceSortHelper extends \RightNow\Libraries\Widget\Helper {
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

    /**
     * Returns the HTML option element
     * @param string $value Value of the option element
     * @param string $label Label for the option element
     * @param bool $selected Whether the option element should be selected
     * @return string Fully-constructed option element
     */
    function outputOption ($value, $label, $selected) {
        return '<option ' . ($selected ? 'selected' : '') . ' value="' . $value . '">' . $label . "</option>";
    }
}
