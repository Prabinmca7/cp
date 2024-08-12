<?php 

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 
 * Class load the PHP5 / PHP8 wrapper class to handle impatabilites between versions
 *
 */
class CI_Rntphpwrapper{
    
    public $cpPhpWrapper;
    
    public function __construct(){
        
        if(CP_PHP_VERSION == 80100 || CP_PHP_VERSION == 80300){
            $this->cpPhpWrapper = new Php8cpwrapper();
        }
        else {
            $this->cpPhpWrapper = new Php5cpwrapper();
        }
    }
}


class Php5cpwrapper{
    
    public function __construct(){
        //echo "PHP 5 initialized";
    }

    public function getScriptPath(){
        return 'scripts';
    }
    public function strftime($format, $seconds){
        return strftime($format, $seconds);
    }
    
    public function gmstrftime($format, $seconds){
        return gmstrftime($format, $seconds);
    }

    public function getDtfShortDate(){
        return \RightNow\Utils\Config::getConfig(DTF_SHORT_DATE);
    }

    public function getDtfTime(){
        return \RightNow\Utils\Config::getConfig(DTF_TIME); 
    }

    public function getTimeZone(){
        return strftime('%Z');
    }
    public function getDtfMonthDateDate(){
        return \RightNow\Utils\Config::getConfig(DTF_MONTH_DAY_DATE);
    }
}

class Php8cpwrapper{
    
    public function __construct(){
        //echo "PHP 8 initialized";
    }

    public function getScriptPath(){
        return 'scripts_' . CP_PHP_VERSION;
    }

    public function strftime($format, $seconds){
        return date($format, $seconds);
    }

    public function gmstrftime($format, $seconds){
        return date($format, $seconds);
    }

    public function getDtfShortDate(){
        return $this->strftimeToDate(\RightNow\Utils\Config::getConfig(DTF_SHORT_DATE));
    }

    public function getDtfTime(){
        return $this->strftimeToDate(\RightNow\Utils\Config::getConfig(DTF_TIME));
    }

    public function getTimeZone(){
       $getTimeZone = new DateTime();
       return $getTimeZone->format('T');
    }

    public function getDtfMonthDateDate(){
        return $this->strftimeToDate(\RightNow\Utils\Config::getConfig(DTF_MONTH_DAY_DATE));
    }
    /**
     * Convert strftime depricated in php version 8.3 format to php date format https://www.php.net/manual/en/function.strftime.php
     * @param $strftimeformat
     * @return string
     */
    private function strftimeToDate($strftimeFormat){
        $strftimeToDateArray = array('%a' => 'D', '%A' => 'l', '%d' => 'd', '%e' => 'j', '%j' => 'z', '%u' => 'N', '%w' => 'w', '%U' => 'W', '%V' => 'W', '%W' => 'W', '%b' => 'M', '%B' => 'F', '%h' => 'M', '%m' => 'm', '%g' => 'y', '%G' => 'Y', '%y' => 'y', '%Y' => 'Y', '%H' => 'H', '%k' => 'G', '%I' => 'h', '%l' => 'g',
  '%M' => 'i', '%p' => 'A', '%P' => 'a', '%r' => 'h:i:s A', '%R' => 'H:i', '%S' => 's', '%T' => 'H:i:s', '%X' => 'H:i:s', '%z' => 'O', '%Z' => 'T', '%c' => 'D M j H:i:s Y', '%D' => 'm/d/y', '%F' => 'Y-m-d', '%s' => 'U', '%x' => 'm/d/y', '%n' => '\n', '%t' => '\t', '%%' => '%');
        $phpDateFormat = strtr($strftimeFormat,$strftimeToDateArray);
        return $phpDateFormat;
    }
}

?>