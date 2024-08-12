<?php
namespace RightNow\Utils;

/**
 * Methods for dealing with chat related functionality.
 */
final class Chat{
    /**
     * Determines if chat is currently available by checking if the current time is
     * within work hours and isn't a holiday.
     * @return bool Whether chat is currently available
     */
    public static function isChatAvailable()
    {
        static $chatAvailable = null;

        if($chatAvailable === null)
        {
            $chatHoursResponse = get_instance()->model('Chat')->getChatHours()->result;
            $chatAvailable = $chatHoursResponse['inWorkHours'] && !$chatHoursResponse['holiday'];
        }
        return $chatAvailable;
    }
}