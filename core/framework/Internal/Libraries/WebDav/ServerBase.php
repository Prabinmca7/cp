<?php

namespace RightNow\Internal\Libraries\WebDav;

use UConverter;

/*
 +----------------------------------------------------------------------+
 | PHP Version 4                                                        |
 +----------------------------------------------------------------------+
 | Copyright (c) 1997-2003 The PHP Group                                |
 +----------------------------------------------------------------------+
 | This source file is subject to version 2.02 of the PHP license,      |
 | that is bundled with this package in the file LICENSE, and is        |
 | available at through the world-wide-web at                           |
 | http://www.php.net/license/2_02.txt.                                 |
 | If you did not receive a copy of the PHP license and are unable to   |
 | obtain it through the world-wide-web, please send a note to          |
 | license@php.net so we can mail you a copy immediately.               |
 +----------------------------------------------------------------------+
 | Authors: Hartmut Holzgraefe <hholzgra@php.net>                       |
 |          Christian Stocker <chregu@bitflux.ch>                       |
 +----------------------------------------------------------------------+
*/
require_once __DIR__ . "/ParsePropfind.php";
require_once __DIR__ . "/ParseProppatch.php";

/**
 * Virtual base class for implementing WebDAV servers
 *
 * WebDAV server base class, needs to be extended to do useful work
 *
 * @package HTTP_WebDAV_Server
 * @author  Hartmut Holzgraefe <hholzgra@php.net>
 * @version @package_version@
 */
class ServerBase
{
    /**
     * complete URI for this request
     *
     * @var string
     */
    protected $uri;

    /**
     * base URI for this request
     *
     * @var string
     */
    protected $base_uri;

    /**
     * URI path for this request
     *
     * @var string
     */
    protected $path;

    /**
     * Denotes a head request was made, which we
     * convert into a get request.
     */
    protected $modifiedHeadRequest = false;

    /**
     * Remember parsed If: (RFC2518/9.4) header conditions
     *
     * @var array
     */
    protected $_if_header_uris = array();

    /**
     * HTTP response status/message
     *
     * @var string
     */
    protected $_http_status = "200 OK";

    /**
     * encoding of property values passed in
     *
     * @var string
     */
    protected $_prop_encoding = "utf-8";

    /**
     * Copy of $_SERVER superglobal array
     *
     * Derived classes may extend the constructor to
     * modify its contents
     *
     * @var array
     */
    protected $_SERVER;

    /**
     * Multi-part header separator
     */
    protected $multipart_separator = false;

    /**
     * Constructor
     *
     * @param void
     */
    function __construct()
    {
        // PHP messages destroy XML output -> switch them off
        ini_set("display_errors", 0);

        // copy $_SERVER variables to local _SERVER array
        // so that derived classes can simply modify these
        $this->_SERVER = $_SERVER;
    }

    /**
     * Serve WebDAV HTTP request
     *
     * dispatch WebDAV HTTP request to the apropriate method handler
     *
     * @param  void
     * @return void
     */
    protected function serveRequest()
    {
        // adding "C" as locale to bypass issue with
        // international languages.
        try {
            setlocale(LC_COLLATE, "C");
            setlocale(LC_CTYPE, "C");
        }
        catch (\Exception $e) {
            //do nothing
        }

        // prevent warning in litmus check 'delete_fragment'
        if (strstr($this->_SERVER["REQUEST_URI"], '#'))
        {
            $this->http_status("400 Bad Request");
            return;
        }

        // default uri is the complete request uri
        $uri = (@$this->_SERVER["HTTPS"] === "on" ? "https://" : "http://");
        $uri .= $this->_SERVER['HTTP_HOST'].$this->_SERVER['SCRIPT_NAME'];

        $path_info = empty($this->_SERVER["PATH_INFO"]) ? "/" : $this->_SERVER["PATH_INFO"];

        $this->base_uri = $uri;
        $this->uri      = $uri . $path_info;
        $this->path = $this->_urldecode(substr($this->_SERVER["QUERY_STRING"], strlen('admin/webdav/index/')));

        if (!strlen($this->path)) {
            $this->path = '/';
        }

        if (ini_get("magic_quotes_gpc"))
            $this->path = stripslashes($this->path);

        // check
        if (! $this->_check_if_header_conditions())
            return;

        // detect requested method names
        $method  = strtolower($this->_SERVER["REQUEST_METHOD"]);
        $wrapper = "http_".$method;

        // activate HEAD emulation by GET if no HEAD method found
        if ($method == "head" && !method_exists($this, "head"))
        {
            $method = "get";
            $this->modifiedHeadRequest = true;
        }

        if (method_exists($this, $wrapper) && ($method == "options" || method_exists($this, $method)))
        {
            $this->$wrapper();  // call method by name
        }
        else
        {
            // method not found/implemented
            if ($this->_SERVER["REQUEST_METHOD"] == "LOCK") {
                $this->http_status("412 Precondition failed");
            }
            else
            {
                $this->http_status("405 Method not allowed");
                header("Allow: ".implode(", ", $this->_allow()));  // tell client what's allowed
            }
        }
    }

    /**
     * OPTIONS method handler
     *
     * The OPTIONS method handler creates a valid OPTIONS reply
     * including Dav: and Allowed: heaers
     * based on the implemented methods found in the actual instance
     *
     * @param  void
     * @return void
     */
    protected function http_OPTIONS()
    {
        // Microsoft clients default to the Frontpage protocol
        // unless we tell them to use WebDAV
        header("MS-Author-Via: DAV");

        // get allowed methods
        $allow = $this->_allow();

        // Mac OS refuses to write to a WebDAV server that doesn't support locking.  We just pretend to lock and it's happy.
        if ($this->isClientMacOS())
        {
            $davClass = array(1,2);
        }
        else
        {
            $davClass = array(1);
            unset($allow["LOCK"]);
            unset($allow["UNLOCK"]);
        }

        // tell clients what we found
        $this->http_status("200 OK");
        header("DAV: "  .implode(", ", $davClass));
        header("Allow: ".implode(", ", $allow));

        header("Content-length: 0");
    }

    /**
     * Uses server variables to determine if the client is using the native Mac WebDAV client. This will
     * not be true if the client is using Dreamweaver on a Mac.
     *
     * @return boolean Result of Mac WebDAV check
     */
    protected function isClientMacOS()
    {
        return \RightNow\Utils\Text::beginsWith($_SERVER['HTTP_USER_AGENT'], 'WebDAVFS') && \RightNow\Utils\Text::stringContains($_SERVER['HTTP_USER_AGENT'], 'Darwin');
    }

    protected function isClientWindowsMiniRedirector() 
    {
        return \RightNow\Utils\Text::beginsWith($_SERVER['HTTP_USER_AGENT'], 'Microsoft-WebDAV-MiniRedir/');
    }


    // Mac OS refuses to write to a WebDAV server that doesn't support locking.  We just pretend to lock and it's happy.
    protected function LOCK()
    {
    }

    // Mac OS refuses to write to a WebDAV server that doesn't support locking.  We just pretend to lock and it's happy.
    protected function UNLOCK()
    {
    }

    // Mac OS refuses to write to a WebDAV server that doesn't support locking.  We just pretend to lock and it's happy.
    protected function http_LOCK()
    {
        $this->http_status("200 OK");
        if ($this->isClientWindowsMiniRedirector())
        {
            // I think I may have figured out how to make WebDAV work with Windows 7.  The
            // problem was that although we said that we don't support the LOCK feature of
            // WebDAV, it insisted upon sending a LOCK command.  I realized that I'd long ago
            // made LOCK send a 200 on purpose because clients that don't listen when you say
            // that you don't support the feature, e.g. Mac OS X, aren't going to be happy if
            // you tell them no when they try.   I did try giving Windows 7 the various WebDAV
            // RFC approved HTTP status codes which mean no, i.e. 415 and 412, and it just got
            // angry.  "No" means "no," Microsoft.  So, instead I decided to say yes and fake
            // it.  I setup a mod_dav server and figured out what Windows was expecting in
            // response to the LOCK command, commandeered that, stripped out anything that
            // looked non-essential, and sent that back.  It's happier now.
            echo <<<LOCK_RESPONSE
<?xml version="1.0" encoding="utf-8"?>
<!-- We told Windows we didn't support locking, but it doesn't believe us.  Now we lie. -->
<D:prop xmlns:D="DAV:">
<D:lockdiscovery>
<D:activelock>
<D:locktype><D:write/></D:locktype>
<D:lockscope><D:exclusive/></D:lockscope>
<D:depth>infinity</D:depth>
<D:timeout>Second-1</D:timeout> <!-- Because we're not going to actually create a lock, I tell Windows that the lock's time is really short.  It probably doens't matter, but I like mixing a bit of truth into my lies. -->
<D:locktoken>
<D:href>{e97b37d1-a441-4d31-aa01-e054f0b19dd4}</D:href> <!-- Just kidding!  I have no intention of honoring this lock token. -->
</D:locktoken>
</D:activelock>
</D:lockdiscovery>
</D:prop>
LOCK_RESPONSE;
        }
    }

    // Mac OS refuses to write to a WebDAV server that doesn't support locking.  We just pretend to lock and it's happy.
    protected function http_UNLOCK()
    {
        $this->http_status("204 No Content");
    }

    /**
     * PROPFIND method handler
     *
     * @param  void
     * @return void
     */
    protected function http_PROPFIND()
    {
        $options = Array();
        $files   = Array();

        $options["path"] = $this->path;

        // search depth from header (default is "infinity)
        if (isset($this->_SERVER['HTTP_DEPTH']))
            $options["depth"] = $this->_SERVER["HTTP_DEPTH"];
        else
            $options["depth"] = "infinity";

        // analyze request payload
        $propinfo = new ParsePropfind("php://input");
        if (!$propinfo->success)
        {
            $this->http_status("400 Error");
            return;
        }
        $options['props'] = $propinfo->props;

        // call user handler
        if (!$this->PROPFIND($options, $files))
        {
            $files = array("files" => array());

            if (empty($files['files']))
            {
                $this->http_status("404 Not Found");
                return;
            }
        }

        // collect namespaces here
        $ns_hash = array();

        // Microsoft Clients need this special namespace for date and time values
        $ns_defs = "xmlns:ns0=\"urn:uuid:c2f41010-65b3-11d1-a29f-00aa00c14882/\"";

        // now we loop over all returned file entries
        foreach ($files["files"] as $filekey => $file)
        {
            // nothing to do if no properties were returend for a file
            if (!isset($file["props"]) || !is_array($file["props"]))
                continue;

            // now loop over all returned properties
            foreach ($file["props"] as $key => $prop)
            {
                // as a convenience feature we do not require that user handlers
                // restrict returned properties to the requested ones
                // here we strip all unrequested entries out of the response
                switch($options['props'])
                {
                    case "all":
                        // nothing to remove
                        break;
                    case "names":
                        // only the names of all existing properties were requested
                        // so we remove all values
                        unset($files["files"][$filekey]["props"][$key]["val"]);
                        break;
                    default:
                        $found = false;

                        // search property name in requested properties
                        foreach ((array)$options["props"] as $reqprop)
                        {
                            if ($reqprop["name"]  == $prop["name"]
                                && @$reqprop["xmlns"] == $prop["ns"])
                            {
                                $found = true;
                                break;
                            }
                        }

                    // unset property and continue with next one if not found/requested
                    if (!$found)
                    {
                        $files["files"][$filekey]["props"][$key]="";
                        continue(2);
                    }
                    break;
                }

                // namespace handling
                if (empty($prop["ns"])) continue; // no namespace
                $ns = $prop["ns"];
                if ($ns == "DAV:") continue; // default namespace
                if (isset($ns_hash[$ns])) continue; // already known

                // register namespace
                $ns_name = "ns".(count($ns_hash) + 1);
                $ns_hash[$ns] = $ns_name;
                $ns_defs .= " xmlns:$ns_name=\"$ns\"";
            }

            // we also need to add empty entries for properties that were requested
            // but for which no values where returned by the user handler
            if (is_array($options['props']))
            {
                foreach ($options["props"] as $reqprop)
                {
                    if ($reqprop['name']=="") continue; // skip empty entries

                    $found = false;

                    // check if property exists in result
                    foreach ($file["props"] as $prop)
                    {
                        if ($reqprop["name"]  == $prop["name"]
                            && @$reqprop["xmlns"] == $prop["ns"])
                        {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found)
                    {
                        if ($reqprop["xmlns"]==="DAV:" && $reqprop["name"]==="lockdiscovery")
                        {
                            // lockdiscovery is handled by the base class
                            $files["files"][$filekey]["props"][]
                                = $this->mkprop("DAV:",
                                                "lockdiscovery",
                                                $this->lockdiscovery($files["files"][$filekey]['path']));
                        }
                        else
                        {
                            // add empty value for this property
                            $files["files"][$filekey]["noprops"][] =
                                $this->mkprop($reqprop["xmlns"], $reqprop["name"], "");

                            // register property namespace if not known yet
                            if ($reqprop["xmlns"] != "DAV:" && !isset($ns_hash[$reqprop["xmlns"]]))
                            {
                                $ns_name = "ns".(count($ns_hash) + 1);
                                $ns_hash[$reqprop["xmlns"]] = $ns_name;
                                $ns_defs .= " xmlns:$ns_name=\"$reqprop[xmlns]\"";
                            }
                        }
                    }
                }
            }
        }

        // now we generate the reply header ...
        $this->http_status("207 Multi-Status");
        header('Content-Type: text/xml; charset="utf-8"');

        // ... and payload
        echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        echo "<D:multistatus xmlns:D=\"DAV:\">\n";

        foreach ($files["files"] as $file)
        {
            // ignore empty or incomplete entries
            if (!is_array($file) || empty($file) || !isset($file["path"]))
                continue;

            $path = $file['path'];
            if (!is_string($path) || $path==="") continue;

            echo " <D:response $ns_defs>\n";

            /* TODO right now the user implementation has to make sure
             collections end in a slash, this should be done in here
             by checking the resource attribute */
            if($file['self'])
                $href = $this->_SERVER['REQUEST_URI'];
            else
                $href = $this->_mergePathes($this->_SERVER['REQUEST_URI'], $path);
            $href = str_replace(".cfg/php", ".cfg/dav", $href);
            echo "  <D:href>$href</D:href>\n";

            // report all found properties and their values (if any)
            if (isset($file["props"]) && is_array($file["props"]))
            {
                echo "   <D:propstat>\n";
                echo "    <D:prop>\n";

                foreach ($file["props"] as $key => $prop)
                {
                    if (!is_array($prop))
                        continue;
                    if (!isset($prop["name"]))
                        continue;

                    if (!isset($prop["val"]) || $prop["val"] === "" || $prop["val"] === false)
                    {
                        // empty properties (cannot use empty() for check as "0" is a legal value here)
                        if ($prop["ns"]=="DAV:")
                            echo "     <D:$prop[name]/>\n";
                        else if (!empty($prop["ns"]))
                            echo "     <".$ns_hash[$prop["ns"]].":$prop[name]/>\n";
                        else
                            echo "     <$prop[name] xmlns=\"\"/>";

                    }
                    else if ($prop["ns"] == "DAV:")
                    {
                        // some WebDAV properties need special treatment
                        switch ($prop["name"]) {
                        case "creationdate":
                            echo "     <D:creationdate ns0:dt=\"dateTime.tz\">"
                                . gmdate("Y-m-d\\TH:i:s\\Z", $prop['val'])
                                . "</D:creationdate>\n";
                            break;
                        case "getlastmodified":
                            echo "     <D:getlastmodified ns0:dt=\"dateTime.rfc1123\">"
                                . gmdate("D, d M Y H:i:s ", $prop['val'])
                                . "GMT</D:getlastmodified>\n";
                            break;
                        case "resourcetype":
                            echo "     <D:resourcetype><D:$prop[val]/></D:resourcetype>\n";
                            break;
                        case "supportedlock":
                            echo "     <D:supportedlock>$prop[val]</D:supportedlock>\n";
                            break;
                        case "lockdiscovery":
                            echo "     <D:lockdiscovery>\n";
                            echo $prop["val"];
                            echo "     </D:lockdiscovery>\n";
                            break;
                        default:
                            echo "     <D:$prop[name]>"
                                . $this->_prop_encode(htmlspecialchars($prop['val']))
                                .     "</D:$prop[name]>\n";
                            break;
                        }
                    }
                    else
                    {
                        // properties from namespaces != "DAV:" or without any namespace
                        if ($prop["ns"])
                        {
                            echo "     <" . $ns_hash[$prop["ns"]] . ":$prop[name]>"
                                . $this->_prop_encode(htmlspecialchars($prop['val']))
                                . "</" . $ns_hash[$prop["ns"]] . ":$prop[name]>\n";
                        }
                        else
                        {
                            echo "     <$prop[name] xmlns=\"\">"
                                . $this->_prop_encode(htmlspecialchars($prop['val']))
                                . "</$prop[name]>\n";
                        }
                    }
                }

                echo "   </D:prop>\n";
                echo "   <D:status>HTTP/1.1 200 OK</D:status>\n";
                echo "  </D:propstat>\n";
            }

            // now report all properties requested but not found
            if (isset($file["noprops"]))
            {
                echo "   <D:propstat>\n";
                echo "    <D:prop>\n";

                foreach ($file["noprops"] as $key => $prop)
                {
                    if ($prop["ns"] == "DAV:")
                        echo "     <D:$prop[name]/>\n";
                    else if ($prop["ns"] == "")
                        echo "     <$prop[name] xmlns=\"\"/>\n";
                    else
                        echo "     <" . $ns_hash[$prop["ns"]] . ":$prop[name]/>\n";
                }

                echo "   </D:prop>\n";
                echo "   <D:status>HTTP/1.1 404 Not Found</D:status>\n";
                echo "  </D:propstat>\n";
            }
            echo " </D:response>\n";
        }
        echo "</D:multistatus>\n";
    }

    /**
     * PROPPATCH method handler
     *
     * @param  void
     * @return void
     */
    protected function http_PROPPATCH()
    {
        $options = Array();
        $options["path"] = $this->path;
        $propinfo = new ParseProppatch("php://input");

        if (!$propinfo->success)
        {
            $this->http_status("400 Error");
            return;
        }

        $options['props'] = $propinfo->props;
        $responsedescr = $this->PROPPATCH($options);

        $this->http_status("207 Multi-Status");
        header('Content-Type: text/xml; charset="utf-8"');

        echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";

        echo "<D:multistatus xmlns:D=\"DAV:\">\n";
        echo " <D:response>\n";
        echo "  <D:href>".$this->_urlencode($this->_mergePathes($this->_SERVER["SCRIPT_NAME"], $this->path))."</D:href>\n";

        foreach ($options["props"] as $prop)
        {
            echo "   <D:propstat>\n";
            echo "    <D:prop><$prop[name] xmlns=\"$prop[ns]\"/></D:prop>\n";
            echo "    <D:status>HTTP/1.1 $prop[status]</D:status>\n";
            echo "   </D:propstat>\n";
        }

        if ($responsedescr)
        {
            echo "  <D:responsedescription>".
            $this->_prop_encode(htmlspecialchars($responsedescr)).
            "</D:responsedescription>\n";
        }

        echo " </D:response>\n";
        echo "</D:multistatus>\n";
    }

    /**
     * MKCOL method handler
     *
     * @param  void
     * @return void
     */
    protected function http_MKCOL()
    {
        $options = Array();
        $options["path"] = $this->path;
        $stat = $this->MKCOL($options);
        $this->http_status($stat);
    }

    /**
     * GET method handler
     *
     * @param void
     * @returns void
     */
    protected function http_GET()
    {
        // TODO check for invalid stream
        $options         = Array();
        $options["path"] = $this->path;

        $this->_get_ranges($options);

        if (true === ($status = $this->GET($options)))
        {
            if (!headers_sent())
            {
                $status = "200 OK";

                if (!isset($options['mimetype']))
                    $options['mimetype'] = "application/octet-stream";

                header("Content-type: $options[mimetype]");

                if (isset($options['mtime']))
                    header("Last-modified:".gmdate("D, d M Y H:i:s ", $options['mtime'])."GMT");


                if (isset($options['stream']))
                {
                    // GET handler returned a stream
                    if (!empty($options['ranges']) && (0===fseek($options['stream'], 0, SEEK_SET)))
                    {
                        // partial request and stream is seekable

                        if (is_array($options['ranges']) && count($options['ranges']) === 1)
                        {
                            $range = $options['ranges'][0];

                            if (isset($range['start']))
                            {
                                fseek($options['stream'], $range['start'], SEEK_SET);
                                if (feof($options['stream']))
                                {
                                    $this->http_status("416 Requested range not satisfiable");
                                    return;
                                }

                                if (isset($range['end']))
                                {
                                    $size = $range['end']-$range['start']+1;
                                    $this->http_status("206 partial");
                                    header("Content-length: $size");
                                    header("Content-range: $range[start]-$range[end]/"
                                           . (isset($options['size']) ? $options['size'] : "*"));
                                    while ($size && !feof($options['stream']))
                                    {
                                        $buffer = fread($options['stream'], 4096);
                                        $size  -= strlen($buffer);
                                        echo $buffer;
                                    }
                                }
                                else
                                {
                                    $this->http_status("206 partial");
                                    if (isset($options['size']))
                                    {
                                        header("Content-length: ".($options['size'] - $range['start']));
                                        header("Content-range: ".$range['start']."-".$range['end']."/"
                                               . (isset($options['size']) ? $options['size'] : "*"));
                                    }
                                    fpassthru($options['stream']);
                                }
                            }
                            else
                            {
                                header("Content-length: ".$range['last']);
                                fseek($options['stream'], -$range['last'], SEEK_END);
                                fpassthru($options['stream']);
                            }
                        }
                        else
                        {
                            $this->_multipart_byterange_header(); // init multipart
                            foreach ($options['ranges'] as $range)
                            {
                                // TODO what if size unknown? 500?
                                if (isset($range['start']))
                                {
                                    $from = $range['start'];
                                    $to   = !empty($range['end']) ? $range['end'] : $options['size']-1;
                                }
                                else
                                {
                                    $from = $options['size'] - $range['last']-1;
                                    $to   = $options['size'] -1;
                                }
                                $total = isset($options['size']) ? $options['size'] : "*";
                                $size  = $to - $from + 1;
                                $this->_multipart_byterange_header($options['mimetype'], $from, $to, $total);


                                fseek($options['stream'], $from, SEEK_SET);
                                while ($size && !feof($options['stream']))
                                {
                                    $buffer = fread($options['stream'], 4096);
                                    $size  -= strlen($buffer);
                                    echo $buffer;
                                }
                            }
                            $this->_multipart_byterange_header(); // end multipart
                        }
                    }
                    else
                    {
                        // normal request or stream isn't seekable, return full content
                        if (isset($options['size']))
                            header("Content-length: ".$options['size']);

                        fpassthru($options['stream']);
                        return; // no more headers
                    }
                }
                elseif (isset($options['data']))
                {
                    if (is_array($options['data']))
                    {
                        // reply to partial request
                    }
                    else
                    {
                        header("Content-length: ".strlen($options['data']));
                        echo $options['data'];
                    }
                }
            }
        }
        if (!headers_sent())
        {
            if (false === $status)
                $this->http_status("404 not found");
            else
                $this->http_status("$status");
        }
    }


    /**
     * parse HTTP Range: header
     *
     * @param  array options array to store result in
     * @return void
     */
    protected function _get_ranges(&$options)
    {
        // process Range: header if present
        if (isset($this->_SERVER['HTTP_RANGE']))
        {

            // we only support standard "bytes" range specifications for now
            if (preg_match('/bytes\s*=\s*(.+)/', $this->_SERVER['HTTP_RANGE'], $matches))
            {
                $options["ranges"] = array();

                // ranges are comma separated
                foreach (explode(",", $matches[1]) as $range)
                {
                    // ranges are either from-to pairs or just end positions
                    list($start, $end) = explode("-", $range);
                    $options["ranges"][] = ($start==="")
                        ? array("last"=>$end)
                        : array("start"=>$start, "end"=>$end);
                }
            }
        }
    }

    /**
     * generate separator headers for multipart response
     *
     * first and last call happen without parameters to generate
     * the initial header and closing sequence, all calls inbetween
     * require content mimetype, start and end byte position and
     * optionaly the total byte length of the requested resource
     *
     * @param  string  mimetype
     * @param  int     start byte position
     * @param  int     end   byte position
     * @param  int     total resource byte size
     */
    protected function _multipart_byterange_header($mimetype = false, $from = false, $to=false, $total=false)
    {
        if ($mimetype === false)
        {
            if (!isset($this->multipart_separator))
            {
                // initial

                // a little naive, this sequence *might* be part of the content
                // but it's really not likely and rather expensive to check
                $this->multipart_separator = "SEPARATOR_".md5(microtime());

                // generate HTTP header
                header("Content-type: multipart/byteranges; boundary=".$this->multipart_separator);
            }
            else
            {
                // final
                // generate closing multipart sequence
                echo "\n--{$this->multipart_separator}--";
            }
        }
        else
        {
            // generate separator and header for next part
            echo "\n--{$this->multipart_separator}\n";
            echo "Content-type: $mimetype\n";
            echo "Content-range: $from-$to/". ($total === false ? "*" : $total);
            echo "\n\n";
        }
    }

    /**
     * HEAD method handler
     *
     * @param  void
     * @return void
     */
    protected function http_HEAD()
    {
        $status          = false;
        $options         = Array();
        $options["path"] = $this->path;

        if (method_exists($this, "HEAD"))
        {
            $status = $this->head($options);
        }
        else if (method_exists($this, "GET"))
        {
            ob_start();
            $status = $this->GET($options);
            if (!isset($options['size']))
                $options['size'] = ob_get_length();

            ob_end_clean();
        }

        if (!isset($options['mimetype']))
            $options['mimetype'] = "application/octet-stream";

        header("Content-type: $options[mimetype]");

        if (isset($options['mtime']))
            header("Last-modified:".gmdate("D, d M Y H:i:s ", $options['mtime'])."GMT");

        if (isset($options['size']))
            header("Content-length: ".$options['size']);

        if ($status === true)
            $status = "200 OK";
        if ($status === false)
            $status = "404 Not found";

        $this->http_status($status);
    }
    
    /**
     * PUT method handler
     *
     * @param  void
     * @return void
     */
    protected function http_PUT()
    {
        $options                   = Array();
        $options["path"]           = $this->path;
        $options["content_length"] = ($this->_SERVER["CONTENT_LENGTH"]) ? $this->_SERVER["CONTENT_LENGTH"] : $this->_SERVER["HTTP_X_EXPECTED_ENTITY_LENGTH"];

        // get the Content-type
        if (isset($this->_SERVER["CONTENT_TYPE"]))
        {
            // for now we do not support any sort of multipart requests
            if (!strncmp($this->_SERVER["CONTENT_TYPE"], "multipart/", 10))
            {
                $this->http_status("501 not implemented");
                echo "The service does not support mulipart PUT requests";
                return;
            }
            $options["content_type"] = $this->_SERVER["CONTENT_TYPE"];
        }
        else
        {
            // default content type if none given
            $options["content_type"] = "application/octet-stream";
        }

        /* RFC 2616 2.6 says: "The recipient of the entity MUST NOT
        ignore any Content-* (e.g. Content-Range) headers that it
        does not understand or implement and MUST return a 501
        (Not Implemented) response in such cases."
        */
        foreach ($this->_SERVER as $key => $val)
        {
            if (strncmp($key, "HTTP_CONTENT", 11)) continue;

            switch ($key)
            {
                case 'HTTP_CONTENT_ENCODING': // RFC 2616 14.11
                    // TODO support this if ext/zlib filters are available
                    $this->http_status("501 not implemented");
                    echo "The service does not support '$val' content encoding";
                    return;

                case 'HTTP_CONTENT_LANGUAGE': // RFC 2616 14.12
                    // we assume it is not critical if this one is ignored
                    // in the actual PUT implementation ...
                    $options["content_language"] = $val;
                    break;

                case 'HTTP_CONTENT_LOCATION': // RFC 2616 14.14
                    /* The meaning of the Content-Location header in PUT
                    or POST requests is undefined; servers are free
                    to ignore it in those cases. */
                    break;

                case 'HTTP_CONTENT_RANGE':    // RFC 2616 14.16
                    // single byte range requests are supported
                    // the header format is also specified in RFC 2616 14.16
                    // TODO we have to ensure that implementations support this or send 501 instead
                    if (!preg_match('@bytes\s+(\d+)-(\d+)/((\d+)|\*)@', $val, $matches))
                    {
                        $this->http_status("400 bad request");
                        echo "The service does only support single byte ranges";
                        return;
                    }

                    $range = array("start"=>$matches[1], "end"=>$matches[2]);
                    if (is_numeric($matches[3]))
                            $range["total_length"] = $matches[3];
                    $options["ranges"][] = $range;

                    // TODO make sure the implementation supports partial PUT
                    // this has to be done in advance to avoid data being overwritten
                    // on implementations that do not support this ...
                    break;

                case 'HTTP_CONTENT_MD5':      // RFC 2616 14.15
                    // TODO: maybe we can just pretend here?
                    $this->http_status("501 not implemented");
                    echo "The service does not support content MD5 checksum verification";
                    return;

                default:
                    // any other unknown Content-* headers
                    $this->http_status("501 not implemented");
                    echo "The service does not support '$key'";
                    return;
            }
        }
        $options["stream"] = fopen("php://stdin", "r");
        $stat = $this->PUT($options);

        if ($stat === false)
            $stat = "403 Forbidden";
        else if (is_resource($stat) && get_resource_type($stat) == "stream")
        {
            $stream = $stat;
            $stat = $options["new"] ? "201 Created" : "204 No Content";

            if (!empty($options["ranges"]))
            {
                // TODO multipart support is missing (see also above)
                if (0 == fseek($stream, $range[0]["start"], SEEK_SET))
                {
                    $length = $range[0]["end"]-$range[0]["start"]+1;
                    if (!fwrite($stream, fread($options["stream"], $length)))
                        $stat = "403 Forbidden";
                    else
                        $stat = "403 Forbidden";
                }
            }
            else
            {
                while (!feof($options["stream"]))
                {
                    if (false === fwrite($stream, fread($options["stream"], 4096)))
                    {
                        $stat = "403 Forbidden";
                        break;
                    }
                }
            }
            fclose($stream);
        }
        $this->http_status($stat);
    }

    /**
     * DELETE method handler
     *
     * @param  void
     * @return void
     */
    protected function http_DELETE()
    {
        // check RFC 2518 Section 9.2, last paragraph
        if (isset($this->_SERVER["HTTP_DEPTH"]))
        {
            if ($this->_SERVER["HTTP_DEPTH"] != "infinity")
            {
                $this->http_status("400 Bad Request");
                return;
            }
        }
        $options         = Array();
        $options["path"] = $this->path;

        $stat = $this->DELETE($options);
        $this->http_status($stat);
    }

    /**
     * COPY method handler
     *
     * @param  void
     * @return void
     */
    protected function http_COPY()
    {
        $this->_copymove("COPY");
    }

    /**
     * MOVE method handler
     *
     * @param  void
     * @return void
     */
    protected function http_MOVE()
    {
        $this->_copymove("MOVE");
    }

    protected function _copymove($what)
    {
        $options         = Array();
        $options["path"] = $this->path;
        $options["dest_path"] = $this->_urldecode(\RightNow\Utils\Text::getSubstringAfter($this->_SERVER['HTTP_DESTINATION'], '/dav/'));

        if (isset($this->_SERVER["HTTP_DEPTH"]))
            $options["depth"] = $this->_SERVER["HTTP_DEPTH"];
        else
            $options["depth"] = "infinity";

        // see RFC 2518 Sections 9.6, 8.8.4 and 8.9.3
        if (isset($this->_SERVER["HTTP_OVERWRITE"]))
            $options["overwrite"] = $this->_SERVER["HTTP_OVERWRITE"] == "T";
        else
            $options["overwrite"] = true;

        $stat = $this->$what($options);
        $this->http_status($stat);
    }

    /**
     * check for implemented HTTP methods
     *
     * @param  void
     * @return array something
     */
    protected function _allow()
    {
        // OPTIONS is always there
        $allow = array("OPTIONS" =>"OPTIONS");

        // all other METHODS need both a http_method() wrapper
        // and a method() implementation
        // the base class supplies wrappers only
        foreach (get_class_methods($this) as $method)
        {
            if (!strncmp("http_", $method, 5))
            {
                $method = strtoupper(substr($method, 5));
                if (method_exists($this, $method))
                {
                    $allow[$method] = $method;
                }
            }
        }

        // we can emulate a missing HEAD implemetation using GET
        if (isset($allow["GET"]))
            $allow["HEAD"] = "HEAD";

        return $allow;
    }

    /**
     * helper for property element creation
     *
     * @param  string  XML namespace (optional)
     * @param  string  property name
     * @param  string  property value
     * @return array   property array
     */
    protected function mkprop()
    {
        $args = func_get_args();
        if (count($args) == 3)
        {
            return array("ns"   => $args[0],
                         "name" => $args[1],
                         "val"  => $args[2]);
        }
        else
        {
            return array("ns"   => "DAV:",
                         "name" => $args[0],
                         "val"  => $args[1]);
        }
    }

    /**
     *
     *
     * @param  string  header string to parse
     * @param  int     current parsing position
     * @return array   next token (type and value)
     */
    protected function _if_header_lexer($string, &$pos)
    {
        // skip whitespace
        while (ctype_space($string[$pos]))
        {
            ++$pos;
        }

        // already at end of string?
        if (strlen($string) <= $pos)
            return false;

        // get next character
        $c = $string[$pos++];

        // now it depends on what we found
        switch ($c)
        {
            case "<":
                // URIs are enclosed in <...>
                $pos2 = strpos($string, ">", $pos);
                $uri  = substr($string, $pos, $pos2 - $pos);
                $pos  = $pos2 + 1;
                return array("URI", $uri);
            case "[":
                //Etags are enclosed in [...]
                if ($string[$pos] == "W")
                {
                    $type = "ETAG_WEAK";
                    $pos += 2;
                }
                else
                {
                    $type = "ETAG_STRONG";
                }
                $pos2 = strpos($string, "]", $pos);
                $etag = substr($string, $pos + 1, $pos2 - $pos - 2);
                $pos  = $pos2 + 1;
                return array($type, $etag);
            case "N":
                // "N" indicates negation
                $pos += 2;
                return array("NOT", "Not");
            default:
                // anything else is passed verbatim char by char
                return array("CHAR", $c);
        }
    }

    /**
     * parse If: header
     *
     * @param  string  header string
     * @return array   URIs and their conditions
     */
    protected function _if_header_parser($str)
    {
        $pos  = 0;
        $len  = strlen($str);
        $uris = array();

        // parser loop
        while ($pos < $len)
        {
            // get next token
            $token = $this->_if_header_lexer($str, $pos);

            // check for URI
            if ($token[0] == "URI")
            {
                $uri   = $token[1]; // remember URI
                $token = $this->_if_header_lexer($str, $pos); // get next token
            }
            else
            {
                $uri = "";
            }

            // sanity check
            if ($token[0] != "CHAR" || $token[1] != "(")
                return false;

            $list  = array();
            $level = 1;
            $not   = "";
            while ($level)
            {
                $token = $this->_if_header_lexer($str, $pos);
                if ($token[0] == "NOT")
                {
                    $not = "!";
                    continue;
                }
                switch ($token[0])
                {
                    case "CHAR":
                        switch ($token[1])
                        {
                            case "(":
                                $level++;
                                break;
                            case ")":
                                $level--;
                                break;
                            default:
                                return false;
                        }
                        break;
                    case "URI":
                        $list[] = $not."<$token[1]>";
                        break;
                    case "ETAG_WEAK":
                        $list[] = $not."[W/'$token[1]']>";
                        break;
                    case "ETAG_STRONG":
                        $list[] = $not."['$token[1]']>";
                        break;
                    default:
                        return false;
                }
                $not = "";
            }

            if (@is_array($uris[$uri]))
                $uris[$uri] = array_merge($uris[$uri], $list);
            else
                $uris[$uri] = $list;
        }

        return $uris;
    }

    /**
     * check if conditions from "If:" headers are meat
     *
     * the "If:" header is an extension to HTTP/1.1
     * defined in RFC 2518 section 9.4
     *
     * @param  void
     * @return void
     */
    protected function _check_if_header_conditions()
    {
        if (isset($this->_SERVER["HTTP_IF"]))
        {
            $this->_if_header_uris =  $this->_if_header_parser($this->_SERVER["HTTP_IF"]);

            foreach ($this->_if_header_uris as $uri => $conditions) {
                if ($uri == "")
                    $uri = $this->uri;

                // all must match
                $state = true;
                foreach ($conditions as $condition)
                {
                    // lock tokens may be free form (RFC2518 6.3)
                    // but if opaquelocktokens are used (RFC2518 6.4)
                    // we have to check the format (litmus tests this)
                    if (!strncmp($condition, "<opaquelocktoken:", strlen("<opaquelocktoken")))
                    {
                        if (!preg_match('/^<opaquelocktoken:[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}>$/', $condition))
                        {
                            $this->http_status("423 Locked");
                            return false;
                        }
                    }
                    if (!$this->_check_uri_condition($uri, $condition))
                    {
                        $this->http_status("412 Precondition failed");
                        $state = false;
                        break;
                    }
                }

                // any match is ok
                if ($state == true)
                    return true;
            }
            return false;
        }
        return true;
    }

    /**
     * Check a single URI condition parsed from an if-header
     *
     * @abstract
     * @param string $uri URI to check
     * @param string $condition Condition to check for this URI
     * @returns bool Condition check result
     */
    protected function _check_uri_condition($uri, $condition)
    {
        // not really implemented here,
        // implementations must override

        // a lock token can never be from the DAV: scheme
        // litmus uses DAV:no-lock in some tests
        if (!strncmp("<DAV:", $condition, 5))
            return false;

        return true;
    }

    /**
     * Generate lockdiscovery reply from checklock() result
     *
     * @param   string  resource path to check
     * @return  string  lockdiscovery response
     */
    protected function lockdiscovery($path)
    {
        return "";
    }


    /**
     * set HTTP return status and mirror it in a private header
     *
     * @param  string  status code and message
     * @return void
     */
    protected function http_status($status)
    {
        // simplified success case
        if ($status === true)
            $status = "200 OK";
        // remember status
        $this->_http_status = $status;

        // generate HTTP status response
        header("HTTP/1.1 $status");
        header("X-WebDAV-Status: $status", true);
    }

    /**
     * private minimalistic version of PHP urlencode()
     *
     * @param  string  URL to encode
     * @return string  encoded URL
     */
    protected function _urlencode($url)
    {
        return rawurlencode($url);
    }

    /**
     * private version of PHP urldecode
     *
     * not really needed but added for completenes
     *
     * @param  string  URL to decode
     * @return string  decoded URL
     */
    protected function _urldecode($path)
    {
        return urldecode($path);
    }

    /**
     * UTF-8 encode property values if not already done so
     *
     * @param  string  text to encode
     * @return string  utf-8 encoded text
     */
    protected function _prop_encode($text)
    {
        switch (strtolower($this->_prop_encoding))
        {
            case "utf-8":
                return $text;
            case "iso-8859-1":
            case "iso-8859-15":
            case "latin-1":
            default:
                return UConverter::transcode($text, 'UTF-8', strtoupper($this->_prop_encoding));
        }
    }

    /**
     * Slashify - make sure path ends in a slash
     *
     * @param   string directory path
     * @returns string directory path wiht trailing slash
     */
    protected function _slashify($path)
    {
        if ($path[strlen($path)-1] != '/')
            $path .= '/';

        return $path;
    }

    /**
     * Unslashify - make sure path doesn't in a slash
     *
     * @param   string directory path
     * @returns string directory path wihtout trailing slash
     */
    protected function _unslashify($path)
    {
        while ($path[strlen($path)-1] === '/')
            $path = substr($path, 0, -1);

        return $path;
    }

    /**
     * Merge two pathes, make sure there is exactly one slash between them
     *
     * @param  string  parent path
     * @param  string  child path
     * @return string  merged path
     */
    protected function _mergePathes($parent, $child)
    {
        if ($child[0] == '/')
            return $this->_unslashify($parent).$child;
        else
            return $this->_slashify($parent).$child;
    }
}
