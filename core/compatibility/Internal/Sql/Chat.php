<?php

namespace RightNow\Internal\Sql;

final class Chat
{
    /**
     * Returns chat availability start and end time
     * @return array Details about chat availability
     */
    public static function getChatHoursAvailability()
    {
        $interfaceID = intf_id();

        //Get the hours for each weekday
        $si = sql_prepare(sprintf("SELECT rri.tm_start, rri.tm_end, rri.day
                                   FROM response_reqs rr, rr_intervals rri
                                   WHERE (rr.rr_type = 3) AND (rr.rr_id = rri.rr_id) AND (interface_id = %d)
                                   ORDER BY day, tm_start, tm_end", $interfaceID));

        sql_bind_col($si, 1, BIND_DTTM, 0);
        sql_bind_col($si, 2, BIND_DTTM, 0);
        sql_bind_col($si, 3, BIND_INT, 0);

        while(list($startTime, $endTime, $weekday) = sql_fetch($si))
            $result[] = array('startTime' => $startTime, 'endTime' => $endTime, 'weekday' => $weekday);
        sql_free($si);
        return $result;
    }

    /**
     * Determins if day provided is a chat holiday
     * @param int $month Numerical month
     * @param int $day Numerical day
     * @param int $year Numerical year
     * @return boolean True if day is a holiday, false otherwise
     */
    public static function isChatHoliday($month, $day, $year)
    {
        if(!is_numeric($month) && !is_numeric($day) && !is_numeric($year))
            return false;

        $interfaceID = intf_id();
        $isHoliday = false;
        if (\RightNow\Utils\Config::getConfig(CS_HOLIDAY_MSG_ENABLED, 'RNL'))
        {
            //Determine if today's date is a holiday
            $si = sql_prepare(sprintf("SELECT h.month, h.day, h.year
                                       FROM response_reqs rr, rr2holidays rh, holidays h, visibility v
                                       WHERE (rr.rr_type = 3) AND (rr.interface_id = %d) AND (rr.rr_id = rh.rr_id) AND (h.holiday_id = rh.holiday_id) AND (v.tbl = %d) AND
                                              (v.id = h.holiday_id) AND (v.interface_id = %d) AND (v.admin = 1) AND (h.month = %d) AND (h.day = %d) AND (h.year = %d)", $interfaceID, TBL_HOLIDAYS, $interfaceID, $month, $day, $year));

            sql_bind_col($si, 1, BIND_INT, 0);
            sql_bind_col($si, 2, BIND_INT, 0);
            sql_bind_col($si, 3, BIND_INT, 0);

            if($row = sql_fetch($si))
                $isHoliday = true;

            sql_free($si);
        }
        return $isHoliday;
    }

    /**
     * Return an array of menu items.
     *
     * @return array
     */
    public static function getAllMenuItems()
    {
        $si = sql_prepare(sprintf('SELECT m.cf_id, l.label_id, l.label
                                   FROM labels l, menu_items m
                                   WHERE l.label_id = m.id AND l.tbl = %d AND l.lang_id = %d
                                   ORDER BY m.cf_id, m.seq', TBL_MENU_ITEMS, lang_id(LANG_DIR)));

        $i = 0;
        sql_bind_col($si, ++$i, BIND_INT, 0);
        sql_bind_col($si, ++$i, BIND_INT, 0);
        sql_bind_col($si, ++$i, BIND_NTS, 41);

        $menuItems = array();
        $previousID = false;
        while($row = sql_fetch($si)) {
            list($customFieldID, $labelID, $label) = $row;
            if ($previousID !== $customFieldID) {
                $menuItems[$customFieldID] = array();
                $previousID = $customFieldID;
            }
            $menuItems[$customFieldID][$labelID] = $label;
        }

        sql_free($si);
        return $menuItems;
    }

    /**
     * Mapping function to convert a set of custom field data to pair data.
     * @param array $inputFields InputFields A set of custom fields in the CP format
     * @return array A set of pair data for the iapi
     */
    public static function customFieldToPairData(array $inputFields)
    {
        $customFields = array();
        $count = 0;
        foreach($inputFields as $cf)
        {
            switch($cf['data_type'])
            {
                case EUF_DT_DATE:
                    $val = $cf['value'];
                    //when we calculate prev_data in the model, this was already a timestamp, so, no point in doing it again
                    if(!is_numeric($val))
                    {
                        $val = strtotime($val);
                        //this allows us to clear out time cfs
                        if($val === false)
                            $val = TIME_NULL;
                    }
                    $customFields["cf_item$count"] = array('cf_id' => $cf['custom_field_id'],
                                                           'val_date' => $val);
                    break;
                case EUF_DT_DATETIME:
                    $val = $cf['value'];
                    if(!is_numeric($val))
                    {
                        $val = strtotime($val);
                        if($val === false)
                            $val = TIME_NULL;
                    }
                    $customFields["cf_item$count"] = array('cf_id' => $cf['custom_field_id'],
                                                           'val_dttm' => $val);
                    break;
                case EUF_DT_RADIO:
                case EUF_DT_INT:
                case EUF_DT_SELECT:
                    $customFields["cf_item$count"] = array('cf_id' => $cf['custom_field_id'],
                                                           'val_int' => (!is_null($cf['value']) && $cf['value'] !== '') ? intval($cf['value']) : INT_NULL);
                    break;
                default:
                    $customFields["cf_item$count"] = array('cf_id' => $cf['custom_field_id'],
                                                           'val_str' => strval($cf['value']));
                    break;
            }
            $count++;
        }
        return $customFields;
    }
}
