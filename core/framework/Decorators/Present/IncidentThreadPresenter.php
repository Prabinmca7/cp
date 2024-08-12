<?

namespace RightNow\Decorators;

/**
 * Decorator to help with presentation of the Incident Thread
 */
class IncidentThreadPresenter extends Base {
    protected $connectTypes = array( 'Thread' );

    /**
     * Returns the name of the author of the given thread.
     * A sama honorific (NAME_SUFFIX_LBL messagebase) is
     * added to the customer name; this value is populated
     * on Japanese interfaces.
     * @return String         String author name
     */
    function getAuthorName () {
        switch ($this->connectObj->EntryType->ID) {
            case ENTRY_CUSTOMER:
                $name = $this->connectObj->Contact->LookupName;
                $suffix = \RightNow\Utils\Config::getMessage(NAME_SUFFIX_LBL);
                $name .= ($suffix) ? " $suffix" : '';
                break;
            case ENTRY_RULE_RESP:
                $name = '';
                break;
            default:
                $name = $this->connectObj->Account->DisplayName;
                break;
        }

        return $name;
    }

    /**
     * Determines if the given thread is private and should
     * not be displayed.
     * @return Boolean         True if the thread is private
     *                              and shouldn't be displayed;
     *                              False if the thread should
     *                              be displayed
     */
    function isPrivate () {
        return !in_array($this->connectObj->EntryType->ID, array(
            ENTRY_STAFF,
            ENTRY_CUSTOMER,
            ENTRY_CUST_PROXY,
            ENTRY_RNL,
            ENTRY_RULE_RESP,
        ), true);
    }

    /**
     * Determines if the given thread is a customer or
     * customer proxy entry.
     * @return Boolean         True if the thread is a
     *                              customer or customer
     *                              proxy entry; False
     *                              otherwise
     */
    function isCustomerEntry () {
        return in_array($this->connectObj->EntryType->ID, array(
            ENTRY_CUSTOMER,
            ENTRY_CUST_PROXY,
        ), true);
    }

    /**
     * Formats the given thread's created time.
     * @param Boolean $highlight Whether or not to highlight the returned string
     * @return string The formatted string
     */
    function formattedCreationTime ($highlight) {
        $thread = $this->connectObj;
        $meta = $thread::getMetadata();
        return \RightNow\Libraries\Formatter::formatField($thread->CreatedTime, $meta->CreatedTime, $highlight);
    }

    /**
     * Formats the given thread.
     * @param Boolean $highlight Whether or not to highlight the returned string
     * @return string The formatted string
     */
    function formattedEntry ($highlight) {
        return \RightNow\Libraries\Formatter::formatThreadEntry($this->connectObj, $highlight);
    }
}
