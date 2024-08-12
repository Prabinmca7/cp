<?php

namespace RightNow\Controllers\UnitTest;

use RightNow\Api;

class MemcacheTester extends \RightNow\Controllers\Base
{
    function __construct()
    {
        parent::__construct();

        $this->numSettableValues = 5;
        $this->memcacheType = MEMCACHE_TYPE_TEST0;
    }


    function index()
    {
        if (!IS_ADMIN && 'POST' === $_SERVER['REQUEST_METHOD'] && function_exists('\RightNow\Api::memcache_value_deferred_get'))
        {
            \RightNow\Libraries\AbuseDetection::isAbuse();
        }

        print "<html>\n";
        print "<head>\n";
        print "<title>Memcache Test Controller</title>\n";
        print "</head>\n";
        print '<body bgcolor="#01A2A6">' . "\n";

        print "<h1>Memcache Tester</h1>";
        print "<hr/>\n";

        self::printCurrentMemcacheInfo();

        if ($_POST['mc_action'])
        {
            print "\n";
            print "<h3>Action</h3>\n";

            if ($_POST['mc_action'] == 'Get')
            {
                self::memcacheGetValues();

                print "<pre>\n";
                print "Getting Memcache values...\n";
                print 'Done in <span id="gettime">??</span> milliseconds <span style="background-color:pink" id="getexception"></span>';
                print "</pre>\n";
            }
            else if ($_POST['mc_action'] == 'Set')
            {
                self::memcacheSetValues();
            }
            else if ($_POST['mc_action'] == 'Delete')
            {
                self::memcacheDeleteValues();
            }

            print "<hr/>\n";
        }

        print '<form action="" method="post">' . "\n";

        self::memcacheSetDialog();
        print '<input type="submit" name="mc_action" value="Set" />' . "\n";
        print "<hr/>\n";

        self::memcacheGetDialog();
        print '<input type="submit" name="mc_action" value="Get" />' . "\n";
        print '<input type="submit" name="mc_action" value="Delete" />' . "\n";
        print "<hr/>\n";

        print "</form>\n";
        print "\n";

        if ($_POST['mc_action'] && $_POST['mc_action'] == 'Get')
        {
            self::memcacheFetchValues();
            self::printFetchJS();
        }

        print "\n";
        print "<h3>Done</h3>\n";
        print "\n";

        print "</body>\n";
        print "</html>\n";
    }

    function printCurrentMemcacheInfo()
    {
        $servers = \RightNow\Utils\Config::getConfig(MEMCACHED_SERVERS, 'RNW');

        print "\n";
        print "<h3>Config Info</h3>\n";
        print "<pre>\n";

        print "MEMCACHED_SERVERS: [$servers]\n";

        print "Cache type: ";
        if ($this->memcacheType == MEMCACHE_TYPE_TEST0)
        {
            print "MEMCACHE_TYPE_TEST0";
        }
        else
        {
            print "??UNKNOWN??";
        }
        print "\n";

        print "</pre>\n";
        print "<hr/>\n";

    }

    function memcacheSetDialog()
    {
        print "\n";
        print "<h3>Key/Value/TTL Set</h3>\n";

        print "<p style='font-size:10px;'>TTL is in seconds. A value of 0 means that the config will be used.</p>";

        print "<pre>\n";
        print "<table>\n";
        for ($i = 0; $i < $this->numSettableValues; $i++)
        {
            print '  <tr>'."\n";

            // key
            print '    <td>Key: <input type="text" name="setkey'.$i.'"';
            if($_POST['setkey'.$i])
            {
                print ' value="' . $_POST['setkey'.$i] . '"';
            }
            print '/></td>'."\n";

            // value
            print '    <td>Value: <input type="text" name="setvalue'.$i.'"';
            if ($_POST['setvalue'.$i])
            {
                print ' value="' . $_POST['setvalue'.$i] . '"';
            }
            print '/></td>'."\n";

            // ttl
            print '    <td>TTL: <input type="text" name="setttl'.$i.'"';
            if ($_POST['setttl'.$i])
            {
                print ' value="' . $_POST['setttl'.$i] . '"';
            }
            else
            {
                print ' value="0"';
            }
            print '/></td>'."\n";

            // messages
            if ($this->setMessages)
            {
                if ($this->setMessages[$i]['error'])
                {
                    print '    <td>ERROR: <span style="background-color:pink">'.$this->setMessages[$i]['error'].'</span></td>'."\n";
                }
                else if ($this->setMessages[$i]['warning'])
                {
                    print '    <td>Warning: <span style="background-color:yellow">'.$this->setMessages[$i]['warning'].'</span></td>'."\n";
                }
                else if ($this->setMessages[$i]['success'])
                {
                    print '    <td><span style="background-color:lightgreen">OKAY</span></td>'."\n";
                }
            }

            print "  </tr>\n";
        }
        print "</table>\n";
        print "</pre>\n";
    }

    function memcacheGetDialog()
    {
        print "\n";
        print "<h3>Key Get or Delete</h3>\n";

        print "<pre>\n";
        print "<table>\n";
        for ($i = 0; $i < $this->numSettableValues; $i++)
        {
            print "  <tr>\n";

            // key
            print '    <td>Key: <input type="text" name="getkey'.$i.'"';
            if ($_POST['getkey'.$i])
            {
                print 'value="'.$_POST['getkey'.$i].'"';
            }
            print "/></td>\n";

            // value
            if ($this->getSubmitted && $this->getSubmitted[$i])
            {
                print '    <td>Value: <span id="getvalue'.$i.'" style="background-color:lightyellow">Pending...</span></td>'."\n";
            }
            else if ($this->deleteSubmitted)
            {
                if ($this->deleteMessages[$i])
                {
                    print '    <td>Delete: <span id="getvalue'.$i.'" style="background-color:lightgreen">' .
                        $this->deleteMessages[$i] . '</span></td>'."\n";
                }
                else if ($this->deleteErrors[$i])
                {
                    if ($this->deleteErrors[$i] = 'NOT FOUND')
                    {
                        print '    <td>Delete: <span id="getvalue'.$i.'" style="background-color:lightyellow">*Not Found*</span></td>'."\n";
                    }
                    else
                    {
                        print '    <td>Delete: <span id="getvalue'.$i.'" style="background-color:pink">' .
                            $this->deleteErrors[$i] . '</span></td>'."\n";
                    }
                }
            }

            print "  </tr>\n";
        }
        print "</table>\n";
        print "</pre>\n";
    }

    function memcacheSetValues()
    {
        $numExceptions = 0;
        $startTime = microtime(true);

        print "<pre>\n";
        print "Setting Memcache values...\n";

        for ($i = 0; $i < $this->numSettableValues; $i++)
        {
            if ($_POST['setkey'.$i])
            {
                $key = $_POST['setkey'.$i];

                if ($_POST['setvalue'.$i])
                {
                    $value = $_POST['setvalue'.$i];
                    $ttl = $_POST['setttl'.$i];

                    try
                    {
                        $setResult = Api::memcache_value_set($this->memcacheType, $key, $value, $ttl);
                        $this->setMessages[$i]['success'] = true;
                    }
                    catch (\Exception $e)
                    {
                        $numExceptions++;
                        $this->setMessages[$i]['error'] = $e->getMessage();
                    }
                }
                else
                {
                    $this->setMessages[$i]['warning'] = 'No value specified';
                }
            }
            else if ($_POST['setvalue'.$i])
            {
                $this->setMessages[$i]['warning'] = "No key specified";
            }
        }

        $endTime = microtime(true);
        $totalTime = 1000 * ($endTime - $startTime);
        printf("Done in %1.3f milliseconds with %d exceptions.\n", $totalTime, $numExceptions);
        print "</pre>\n";

    }

    function memcacheGetValues()
    {
        $siteKeys = array();
        $podKeys = array();

        for ($i = 0; $i < $this->numSettableValues; $i++)
        {
            if ($_POST['getkey'.$i])
            {
                $this->getLookup[$_POST['getkey'.$i]] = $i;
                $this->getSubmitted[$i] = true;

                $siteKeys[] = $_POST['getkey'.$i];
            }
        }

        try
        {
            $this->memcacheHandle = Api::memcache_value_deferred_get($this->memcacheType, $siteKeys, $podKeys);
        }
        catch (\Exception $e)
        {
            $this->getException[] .= "\RightNow\Api::memcache_value_deferred_get: [" . $e->getMessage() . "] ";
        }
    }

    function memcacheFetchValues()
    {
        $getStartTime = microtime(true);

        try
        {
            $this->getValues = Api::memcache_value_fetch($this->memcacheType, $this->memcacheHandle);
        }
        catch (\Exception $e)
        {
            $this->getException[] .= "\RightNow\Api::memcache_value_fetch: [" . $e->getMessage() . "] ";
        }

        $getEndTime = microtime(true);
        $this->getTotalTime = 1000 * ($getEndTime - $getStartTime);
    }


    function printFetchJS()
    {
        print '<script type="text/javascript">' . "\n";

        print "document.getElementById('gettime').innerHTML='";
        printf("%1.3f", $this->getTotalTime);
        print "';\n";

        if ($this->getException)
        {
            print "document.getElementById('getexception').innerHTML='<br/>With exception(s): ";
            print '<ul style="background-color:pink">';
            foreach ($this->getException as $e)
            {
                print "<li>$e";
            }
            print "</ul>';\n";
        }

        if ($this->getValues)
        {
            foreach ($this->getValues as $key => $value)
            {
                if ($value)
                {
                    print "document.getElementById('getvalue".$this->getLookup[$key]."').innerHTML='$value';\n";
                }
                else
                {
                    print "document.getElementById('getvalue".$this->getLookup[$key]."').innerHTML='*Not Found*';\n";
                }
            }
        }

        print "</script>\n";
    }

    function memcacheDeleteValues()
    {
        $startTime = microtime(true);

        print "<pre>\n";
        print "Deleting Memcache values...\n";

        for ($i = 0; $i < $this->numSettableValues; $i++)
        {
            if ($_POST['getkey'.$i])
            {
                $key = $_POST['getkey'.$i];

                try
                {
                    Api::memcache_value_delete($this->memcacheType, $key);
                    $this->deleteMessages[$i] = 'Success';
                }
                catch (\Exception $e)
                {
                    $this->deleteErrors[$i] = $e->getMessage();
                }
            }
        }
        $this->deleteSubmitted = true;

        $endTime = microtime(true);
        $totalTime = 1000 * ($endTime - $startTime);
        printf("Done in %1.3f milliseconds.\n", $totalTime);
        print "</pre>\n";
    }
}