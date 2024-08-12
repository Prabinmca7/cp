<?
//Utility script to convert the old /euf/config/url_mappings.txt file to the new /euf/application/development/source/config/mapping.php file

define('BASEPATH', str_replace('\\', '/', realpath(dirname(__FILE__))));
define('APPPATH', BASEPATH . '/application/development/source/');
define('CPCORE', BASEPATH . '/application/rightnow/');

$mappingPath = BASEPATH . '/config/url_mappings.txt';
if(!is_readable($mappingPath))
    exit("The file '" . BASEPATH . "/config/url_mappings.txt' does not exist.\n");

$oldMappingFile = file($mappingPath);
$oldMappingEntries = array();
//scan each line, parse out the mapping
if($oldMappingFile !== false)
{
    foreach($oldMappingFile as $line)
    {
        $arr = explode('=>', $line);
        if(count($arr) == 2)
        {
            //remove the quotes and whitespace
            $from = trim(str_replace('"', '', $arr[0]));
            if(strpos($from, 'enduser/') === 0)
                $from = substr($from, strlen('enduser/'));
            $to = trim(str_replace('"', '', $arr[1]));
            $oldMappingEntries[$from] = $to;
        }
    }
}

if(!is_readable(APPPATH . 'config/mapping.php'))
    exit("The file '" . APPPATH . "config/mapping.php' does not exist.\n");

@chmod(APPPATH . 'config/mapping.php', 0777);
require_once APPPATH . 'config/mapping.php';
$oldMappingFile = @fopen(APPPATH . 'config/mapping.php', 'a');

if($oldMappingFile !== false)
{
    foreach($oldMappingEntries as $old => $new)
    {
        if(isset($pageMapping[$old]))
        {
            if($pageMapping[$old]['new_page'] != $new)
                fwrite($oldMappingFile, "\$pageMapping['$old']  = array('new_page' => '$new');\n");
        }
        else
        {
            fwrite($oldMappingFile, "\$pageMapping['$old']  = array('new_page' => '$new');\n");
        }
    }
    fclose($oldMappingFile);
}

//Delete out the old file
@unlink($mappingPath);
