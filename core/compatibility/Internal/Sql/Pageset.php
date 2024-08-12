<?
namespace RightNow\Internal\Sql;

// Starting in 13.5, this file is part of optimized_includes.php. The related models in 3.1 will no longer try
// to include this file directly, but models in 3.0 will. Since the compatibility files are not versioned, prevent
// this class from being re-declared.
// @codingStandardsIgnoreStart
if (!class_exists('\RightNow\Internal\Sql\Pageset')) {
// @codingStandardsIgnoreEnd


    /**
     * Provides functions for retrieving Page set mappings out of
     * the cp_ua_mapping database table.
     */
    final class Pageset{
        /**
         * Retrieves all page set mappings (excluding standard facebook mapping)
         * @return array List containing all mapping rows
         */
        public static function get() {
            $i = 1;
            $results = array();
            $si = sql_prepare(sprintf("SELECT page_set_id, ua_regex, description, page_set, attr FROM cp_ua_mapping
                WHERE interface_id = %d AND (page_set != 'facebook' OR page_set_id >= %d)", intf_id(), CP_FIRST_CUSTOM_PAGESET_ID));

            sql_bind_col($si, $i++, BIND_INT, 0);    //page_set_id
            sql_bind_col($si, $i++, BIND_NTS, 241);  //ua_regex
            sql_bind_col($si, $i++, BIND_NTS, 241);  //description
            sql_bind_col($si, $i++, BIND_NTS, 241);  //page_set
            sql_bind_col($si, $i++, BIND_INT, 0);    //attr

            while ($row = sql_fetch($si)) {
                list($pageSetID, $uaRegex, $description, $pageSet, $attr) = $row;
                $results []= array(
                    'page_set_id'   => $pageSetID,
                    'description'   => $description,
                    'ua_regex'      => $uaRegex,
                    'page_set'      => $pageSet,
                    'attr'          => $attr,
                );
            }
            sql_free($si);
            return $results;
        }

        /**
        * Finds the correct Facebook page set ID for the current interface.
        * @return mixed Int or null if not found
        */
        public static function getFacebookPageSetID() {
            $id = sql_get_int(sprintf("SELECT page_set_id FROM cp_ua_mapping WHERE interface_id = %d AND page_set = 'facebook' AND page_set_id < %d", intf_id(), CP_FIRST_CUSTOM_PAGESET_ID));
            return $id ?: null;
        }
    // @codingStandardsIgnoreStart
    }
    // @codingStandardsIgnoreEnd
}
