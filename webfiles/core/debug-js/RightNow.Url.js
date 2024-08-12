if(RightNow.Url) throw new Error("The RightNow.Url namespace variable has already been defined somewhere.");
/**
 * Contains functions which relate to URL manipulation, retrieval, etc.
 * @namespace
 */
RightNow.Url = (function(){
    var _Y = YUI(),
        _parameterSegment = 0,
        _sessionParameter = "",
        filterProdCats = function(items) {
            var filtered = [];
            _Y.Object.each(items, function(item) {
                if (typeof item === 'string') {
                    item = item.split(',');
                }
                if (_Y.Lang.isArray(item)) {
                    //Only add the most specific product to the URL
                    item = item[item.length - 1];
                }
                if (typeof item === 'string' || typeof item === 'number') {
                    filtered.push(item);
                }
            });
            return (filtered) ? filtered.join(';') : null;
        };

    return {
        /**
         * Checks the provided URL and adds a parameter key if it does not exist. If it
         * does exist, it will replace the existing parameter's value. If the parmVal is
         * an empty string, the parameter will be deleted if it already exists.
         *
         * @param {string} url The current URL to modify
         * @param {string} parmKey The parameter key to add
         * @param {?string} parmVal The parameter value to use
         * @param {boolean=} preserveExistingValue If true and 'url' already contains a parameter named 'parmKey', its value won't be overwritten in the URL.
         * @return {string} The URL with the parameter added
         */
        addParameter: function(url, parmKey, parmVal, preserveExistingValue)
        {
            if(parmVal == null) //if null or undefined ignore the value
            {
                return url;
            }
            if(parmVal === "") // if empty string remove from url
            {
                return RightNow.Url.deleteParameter(url, parmKey);
            }

            var fragmentIndex = url.indexOf("#"),
                appendFragment = false,
                fragment;
            if(fragmentIndex >= 0)
            {
                fragment = url.substr(fragmentIndex);
                url = url.substr(0, fragmentIndex);
                appendFragment = true;
            }

            if (parmVal.toString().length > 0)
            {
                var parameterRegex,
                    existingParameterScheme,
                    urlAppendCharacter;
                // Hack to get around browsers treating the .. incorrectly
                parmVal = (parmVal === '.' || parmVal === '..') ? ' ' + parmVal : parmVal;
                parmVal = encodeURIComponent(parmVal);
                if (parmKey === 'p' || parmKey === 'c') {
                    // Allow un-encoded semi-colon separator between products and categories
                    parmVal = parmVal.replace('%3B', ';');
                }

                // Swap out keyword 'session' with hex characters to prevent it from being stepped on later
                if (parmKey === 'kw' && parmVal === 'session')
                    parmVal = '%73%65%73%73%69%6f%6e';

                // Append parameter in query string format if it looks like that's the
                // format the URL uses.
                if(url.indexOf('?') >= 0)
                {
                    parameterRegex = new RegExp("&" + parmKey + "=[^&]+");
                    existingParameterScheme = "&" + parmKey + "=";
                    urlAppendCharacter = "&";
                }
                else
                {
                    parameterRegex = new RegExp("\/" + parmKey + "\/[^/]+");
                    existingParameterScheme = "/" + parmKey + "/";
                    urlAppendCharacter = "/";
                }

                if(url.indexOf(existingParameterScheme) >= 0)
                {
                    if (!preserveExistingValue)
                    {
                        url = url.replace(parameterRegex, existingParameterScheme + parmVal);
                    }
                }
                else
                {
                    if (url.charAt(url.length - 1) === urlAppendCharacter)
                        url = url.substr(0, url.length - 1);
                    url += existingParameterScheme + parmVal;
                }
            }
            if(appendFragment)
                return url + fragment;
            return url;
        },

        /**
         * Takes the search filter array and creates the url link string for report links
         * @param {Object} filters Search filters
         * @param {string=} parameters to add.  If left blank the ones from search filters (set by the report) will be used
         * @return {string} The URL with search filters added
         */
        buildUrlLinkString: function(filters, parameters)
        {
            var url = "",
                elements = (parameters) ? parameters.split(",") : ((filters.format && filters.format.parmList) ? filters.format.parmList.split(",") : []),
                subFilters = filters.filters || filters;
            for (var i = 0; i < elements.length; i++) {
                // first get the url value if it exists
                var key = elements[i],
                    node = elements[i],
                    value = this.getParameter(key);
                if (value !== null && typeof value !== "function") {
                    url = this.addParameter(url, key, value);
                }
                // next look for current search values
                node = (node === "kw") ? "keyword" : node;
                node = (node === "st") ? "searchType" : node;
                node = (node === "sort") ? "sort_args" : node;
                if (node === "page") {
                    if (subFilters[node])
                        url = this.addParameter(url, node, subFilters[node]);
                }
                else if (subFilters[node] && subFilters[node].filters && (subFilters[node].filters.data != null)) {
                    var subData = subFilters[node].filters.data;
                    value = (subData.val != null) ? subData.val : subData;
                    if (node === "sort_args") {
                        if(subData.col_id > 0){
                            value = subData.col_id + "," + (subData.sort_direction || 1);
                        }
                        else{
                            value = null;
                        }
                    }
                    else if (node === "org") {
                        value = subData.selected;
                    }
                    else if (node === "p" || node === "c") {
                        value = filterProdCats(value);
                    }

                    if (typeof value === 'boolean' || typeof value === 'string' || typeof value === 'number') {
                        url = this.addParameter(url, key, value);
                    }
                }
            }
            return url;
        },

        /**
         * This function will build the correct parameter string
         * based on the search filters
         *
         * @param {string} url The current URL to modify
         * @param {Array} filters An array of any search filters
         * @param {?number|string} searches The number of seaches performed - this is null if cookies are on
         * @return {string} Contains the the modified url and the number of parms added to the url
         */
        convertSearchFiltersToParms: function(url, filters, searches)
        {
            var keys = {
                keyword: "kw",
                locale: "loc",      // added locale parameter for OKCS search
                searchType: "st",
                page: "page",
                sort_args: "sort"
            };

            for (var node in filters) {
                if (typeof filters[node] === "function")
                    continue;

                var key = keys[node] ? keys[node] : node,
                    value = null,
                    subFilters = null;

                if (filters[node] && filters[node].filters)
                    subFilters = filters[node].filters;

                if (node === "keyword") {
                    value = subFilters.data;
                }
                else if (node === "page" && filters[node]) {
                    value = filters[node];
                }
                else if (node === "locale" && filters[node]) { // added locale parameter for OKCS search
                    value = filters[node];
                }
                else if (node === "sort_args") {
                    var column = (subFilters.data && subFilters.data.col_id) ? subFilters.data.col_id : subFilters.col_id,
                        direction = (subFilters.data && subFilters.data.sort_direction) ? subFilters.data.sort_direction : subFilters.sort_direction;
                    if (column !== undefined) {
                        value = (column == -1) ? null : column + "," + direction;
                    }
                }
                else if (node === "p" || node === "c" || node === "pc" || node === "asset_id") {
                    value = filterProdCats(subFilters.data);
                }
                else if (node === "org" && subFilters && subFilters.data && subFilters.data.selected != null) {
                    value = subFilters.data.selected;
                }
                else if (subFilters && subFilters.data != null) {
                    value = subFilters.data.val || subFilters.data;
                }

                if (typeof value === 'boolean' || typeof value === 'string' || typeof value === 'number') {
                    url = this.addParameter(url, key, value);
                }
            }
            //Only add search number if it is specified and cookies are not detected
            if (_sessionParameter !== "" && searches != null)
                url = this.addParameter(url, 'sno', searches);
            return url;
        },

        /**
         * Takes the URI of the page and returns the parameters as an array
         * @param {number} segment The segment of the URL to start with
         * @param {string=} [uri=string] URI to use instead of the page's current URI
         * @return {Array} Key/value pairs of parameters
         */
        convertToArray: function(segment, uri)
        {
            if(typeof segment === "undefined" || isNaN(parseInt(segment, 10))){
                segment = _parameterSegment;
            }
            //protect against catastrophic failure of this function to allow for urls with accidental multiple slashes
            var url = (uri || window.location.pathname).replace(/\/+/g, '/'),
                result = [],
                raw = url.split('/');
            if(uri && uri[0] === "/") {
                // began with a slash
                raw.shift();
            }
            if(raw.length > 1){
                //Subtract the first segment since it will be an empty string (i.e. the leading string before the pathname),
                //except when the pathname begins w/ '/ci/' which means segment already starts out w/ the correct index
                for(var i = (/^\/ci\//.test(url)) ? segment : segment - 1; i < raw.length; i += 2){
                    if(i + 1 < raw.length){
                        result[raw[i]] = decodeURIComponent(raw[i + 1]);
                    }
                }
            }
            return result;
        },

        /**
        * Takes an object and returns a parameter segment string.
        * Does not add any falsey values to the string.
        * @param {Object} obj Object with key-vals to convert to a segment string
        * @return {string} parameter segment string
        */
        convertToSegment: function(obj)
        {
            var str = "";
            for (var i in obj) {
                if (obj.hasOwnProperty(i) && obj[i]) {
                    str += "/" + i + "/" + encodeURIComponent(obj[i]);
                }
            }
            return str;
        },

        /**
         * This function will check the current URL and delete a parameter
         * key if it exists. If it doesn't exist, nothing will happen
         *
         * @param {string} url The current URL to modify
         * @param {string} parmKey The parameter key to remove
         * @return {string} The URl with key removed
         */
        deleteParameter: function(url, parmKey)
        {
            if(url.indexOf("/" + parmKey + "/") >= 0)
                return url.replace(new RegExp("/" + parmKey + "\/[^#/]+"), "");
            return url;
        },

        /**
         * Retrieves the session parameter for the page
         * @return {string} The session parameter
         */
        getSession: function()
        {
            return _sessionParameter;
        },

        /**
         * Retrieves the parameter segment for the page
         * @return {number} The parameter segment
         */
        getParameterSegment: function()
        {
            return _parameterSegment;
        },

        /**
         * Gets the value in the parm string for the key
         *
         * @param {string} parmKey The parameter key to retrieve
         * @return {?string} Parameter value or null if it doesn't exist
         *
         */
        getParameter: function(parmKey)
        {
            return this.convertToArray(this.getParameterSegment())[parmKey] || null;
        },

        /**
         * Takes a comma seperated list of URL parameter keys and builds up
         * a string of their values based on the current URL.
         * @param {string} parameterList Comma seperated list of parameter keys
         * @param {Array=} [exclusionList=Array] List of parameter keys that should be ignored
         * @return {string} The parameters in key1/value1/key2/value2 format
         */
        getUrlParametersFromList: function(parameterList, exclusionList)
        {
            if(exclusionList === undefined)
                exclusionList = [];
            var parameterString = "",
                parameters = parameterList.replace(/ /g, "").split(',');
            for(var i = 0; i < parameters.length; i++)
            {
                var excludedKey = false;
                for(var j = 0; j < exclusionList.length; j++)
                {
                    if(exclusionList[j] === parameters[i])
                    {
                        excludedKey = true;
                        break;
                    }
                }
                if(!excludedKey)
                {
                    var parameterValue = this.getParameter(parameters[i]);
                    if(parameterValue !== null)
                        parameterString += '/' + parameters[i] + '/' + parameterValue;
                }
            }
            return parameterString;
        },

        /**
         * Takes the given url and determines if it is the same page or a substring of the current URL
         * @param {string} url The url to check against
         * @return {boolean} true If the passed in url is equal to or a substring of the current URL
         */
        isSameUrl: function(url)
        {
            var location = window.location.pathname;
            if (location.charAt(location.length - 1) !== "/")
                location += "/";
            if (url.charAt(url.length - 1) !== "/")
                url += "/";
            return (location.substring(0, (url.length)) === url);
        },

        /**
         * Determines if the given url is external to CP based on window.location.host
         * @param {string} url - the url to check
         * @return {boolean} true if external url, false if url is in CP
         * @private
         */
        isExternalUrl: function(url)
        {
            return (url.indexOf(window.location.host) === -1);
        },

        /**
         * Navigates to a new URL. Adds the session if needed and
         * refreshes the page if navigating to the current location.
         * @param {string} url The url to navigate to
         * @param {boolean} external Denotes if the URL is out of the CP framework
         */
        navigate: function(url, external)
        {
            if(!external && this.getSession() !== "")
                url = this.addParameter(url, 'session', this.getSession());
            if (window.location.pathname === url || RightNow.Text.beginsWith(url.toLowerCase(), 'javascript:'))
                window.location.reload(true);
            else
                window.location = url;
        },

        /**
         * Sets the parameter segment for the page
         * @param {number} segment The parameter segment index
         */
        setParameterSegment: function(segment)
        {
            _parameterSegment = segment;
        },

        /**
         * Sets the session parameter for the page
         * @param {string} session The session parameter
         */
        setSession: function(session)
        {
            _sessionParameter = session;
        }
    };
}());

YUI().use('node-core', 'event-base', function(Y){
    var baseHrefFragment;
    /**
     * Rewrites node's href to fix relative urls for base tags
     * and appends session parameter as needed.
     * @param {Object} node an A tag node
     * @inner
     */
    function transform(node) {
        var href = node.get("href");
        if(!(
            href === "" ||
            (node.get("hostname") !== "" && node.get("hostname") !== window.location.hostname) ||
            href.indexOf('doc_view.php') > -1 ||
            href.indexOf('doc_submit.php') > -1 ||
            href.indexOf('doc_serve.php') > -1 ||
            href.indexOf('verify_acct_login.php') > -1 ||
            (href.indexOf("/ci/") > -1 && (href.indexOf("/ci/opensearch") === -1)) ||
            href.indexOf('qautils.php') > -1)) {
                var hashLocation = href.split("#"),
                    linkText = node.get("innerHTML"),
                    Text = RightNow.Text;
                //Some browsers automatically append a slash to the end of domain only URLs. We need to
                //strip them off so that the comparisons later can be accurate.
                if(hashLocation[0].substr(-1) === '/')
                    hashLocation[0] = hashLocation[0].slice(0, -1);
                if(baseHrefFragment.substr(-1) === '/')
                    baseHrefFragment = baseHrefFragment.slice(0, -1);

                if(hashLocation[1] !== undefined && hashLocation[0] === baseHrefFragment){
                    node.set("href", window.location.pathname + "#" + hashLocation[1]);
                    //yes, this is exactly what it looks like: IE bug replaces link text w/href if text contains @
                    if(linkText.indexOf("@") > -1)
                        node.set("innerHTML", linkText);
                }
                if(RightNow.Url.getSession() !== "" && hashLocation[0] !== baseHrefFragment){
                    //Check if the URL points to just the hostname or to just /app and fix it so that it
                    //fully points to the home page since we're appending the session parameter. We always
                    //pass the CP_HOME_URL config setting into JS so we can access it here using getConfig
                    //to take the user to the correct location.
                    href = node.get("href");
                    var pathname = Text.getSubstringAfter(node.get("href"), (node.get("hostname") || window.location.hostname)),
                        homePage = RightNow.Interface.getConfig("CP_HOME_URL");
                    if(pathname === "")
                        node.set("href", href + "/app/" + homePage);
                    else if(pathname === "/")
                        node.set("href", href + "app/" + homePage);
                    else if(pathname === "/app")
                        node.set("href", href + "/" + homePage);
                    else if(pathname === "/app/")
                        node.set("href", href + homePage);
                    //Need to recalculate pathname in case it was changed above
                    pathname = Text.getSubstringAfter(node.get('href'), (node.get('hostname') || window.location.hostname));
                    if(pathname && (Text.beginsWith(href, 'http://') || Text.beginsWith(href, 'https://') || Text.beginsWith(href, '/')) &&
                      (Text.beginsWith(pathname, '/app/') || Text.beginsWith(pathname, '/ci/') || Text.beginsWith(pathname, '/cc/'))){
                        node.set("href", RightNow.Url.addParameter(node.get("href"), 'session', RightNow.Url.getSession()));
                    }
                    if(linkText.indexOf('@') > -1)
                        node.set("innerHTML", linkText);
                }
        }
    }

    /**
     * Fixes any links within the specified rootNode to append the session parameter
     * if necessary, and to fix the base href on anchor links.
     * @param {Object=} rootNode The parent node to search within; if not specified,
     * all links on the page are examined
     * @inner
     */
    RightNow.Url.transformLinks = function(rootNode) {
        baseHrefFragment = Y.one('base');
        baseHrefFragment = (baseHrefFragment) ? baseHrefFragment.get("href") : "";
        ((rootNode && rootNode._yuid) ? rootNode : Y.one(rootNode || document.body)).all('a').each(transform);
    };

    /**
     *  Run transform links - this needs to be done on every page.
     */
    Y.on('domready', function(){
        RightNow.Url.transformLinks();
    });
});
