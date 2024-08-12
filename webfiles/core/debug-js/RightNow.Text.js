if(RightNow.Text) throw new Error("The RightNow.Text namespace variable has already been defined somewhere.");
/**
 * Contains functions which relate to string manipulation.
 * @namespace
 */
RightNow.Text = {
    /**
     * Returns the portion of haystack which follows the first occurence of needle
     * E.g. getSubstringAfter('a/b/c/d', 'b/c') == '/d'
     *
     * @param {string} haystack Text to look within
     * @param {string} needle Text to find
     * @return {boolean|string} False if haystack doesnt contain needle or the string if it does
     */
    getSubstringAfter: function(haystack, needle)
    {
        var index = haystack.indexOf(needle);
        if (index === -1)
            return false;
        return haystack.substr(index + needle.length);
    },

    /**
     * Returns the portion of haystack which is before the first occurence of needle
     * E.g. getSubstringBefore('a/b/c/d', 'b/c') == 'a/'
     *
     * @param {string} haystack Text to look within
     * @param {string} needle Text to find
     * @return {boolean|string} False if haystack doesnt contain needle or the string if it does
     */
    getSubstringBefore: function(haystack, needle)
    {
        var index = haystack.indexOf(needle);
        if (index === -1)
            return false;
        return haystack.substr(0, index);
    },

    /**
     * Returns the portion of subject that follows the first occurence of the startMarker substring
     * and precedes the next occurrence of the endMarker substring. If endMarker doesn't occur after
     * startMarker the rest of the substring after startMarker is returned.
     *
     * @param {string} subject Text to look within
     * @param {string} startMarker Text substring to begin searching
     * @param {string} endMarker Text substring to stop searching
     * @return {boolean|string} False if subject doesn't contain startMarker or the resulting substring if successful
     */
    getSubstringBetween: function(subject, startMarker, endMarker)
    {
        var startMarkerLength = startMarker.length,
            startIndex = subject.indexOf(startMarker) + startMarkerLength,
            endIndex = subject.indexOf(endMarker, startIndex);
        if(startIndex === startMarkerLength - 1)
            return false;
        return subject.substring(startIndex, ((endIndex > startIndex) ? endIndex : subject.length));
    },

    /**
     * Determines if a string starts with another string
     *
     * @param {string} haystack Text to look within
     * @param {string} needle Text substring to look for at the beginning of haystack
     * @return {boolean|string} False if haystack doesn't start with the needle, true if it does
     */
    beginsWith: function(haystack, needle)
    {
        return (haystack.substr(0, needle.length) === needle);
    },


    /**
     * Returns a formatting string. Works similar to how the equivalent
     * PHP function works.
     * @param {...string} string Variable length argument with the formatted string to print followed by the placeholder values
     * @return string The formatted string
     */
    sprintf: function(string)
    {
        var i = 1,
            args = arguments,
            /**@inner*/
            replacer = function(match, matchIndex, wholeString)
            {
                if (match === "%%")
                    return "%";
                if (i >= args.length)
                    throw new Error("RightNow.Text.sprintf: More placeholders (" + i + ") are present in the format string ('" + wholeString + "') than arguments (" + (args.length - 1) + ") were provided.");
                return args[i++];
            };
        return string.replace(/%[%sd]/g, replacer);
    },

    /**
     * Function to remove trailing commas
     * @param {string} val The string to be trimmed
     * @return {string} String with trailing commas removed
     */
    trimComma: function(val)
    {
        var len = val.length,
            notEnd = true;
        while (notEnd)
        {
            if (val.lastIndexOf(",") == len - 1)
            {
                len--;
                val = val.substr(0, len);
            }
            else
            {
                notEnd = false;
            }
        }
        return val;
    },

    /**
     * Determines if a string matches the definition of an email address. Does not indicate if the email address can receive mail.
     * @param {string} emailAddress The email address to test.
     * @return {boolean} True if emailAddress matches DE_VALID_EMAIL_PATTERN; false otherwise.
     */
    isValidEmailAddress: function(emailAddress)
    {
        var emailPattern = RightNow.Interface.getConfig("DE_VALID_EMAIL_PATTERN"),
            validLength = 255;
        if(this.Encoding.utf8Length(emailAddress) > validLength)
            return false;
        if(emailPattern)
        {
            this.isValidEmailAddress._emailRegex = this.isValidEmailAddress._emailRegex ||
                new RegExp("^" + decodeURIComponent(escape(emailPattern)) + "$", "i");
            return this.isValidEmailAddress._emailRegex.test(emailAddress);
        }
        else
        {
            this.isValidEmailAddress._emailRegexApi = this.isValidEmailAddress._emailRegexApi ||
                new RegExp("^" + RightNow.Interface.Constants.API_VALIDATION_REGEX_EMAIL + "$", "i");
            return this.isValidEmailAddress._emailRegexApi.test(emailAddress);
        }
    },

      /**
     * Determines if a string matches the rough definition of a url.
       * @param {string} url The url custom field to test.
       * @return {boolean} True if url matches the url acceptable regular expression.
       */
      isValidUrl: function(url)
      {
        //View the equivalent method in the \RightNow\Utils\Text::isValidUrl for more description on these regular expressions
        this.isValidUrl._protocol = this.isValidUrl._protocol ||
            '^[a-z][a-z0-9.+-]+://';
        this.isValidUrl._protocolRegex = this.isValidUrl._protocolRegex ||
            new RegExp(this.isValidUrl._protocol, 'i');
        this.isValidUrl._urlRegex = this.isValidUrl._urlRegex ||
            new RegExp(this.isValidUrl._protocol + '(?:(?:[^\x00-\x20\x7F<>\/]+\\.(?:[^\x00-\x20\x7F<>\/]+)|\\[[0-9a-f:]+\])(?::[0-9]+)?)[^\x00-\x20\x7F]*$', 'i');

        if(!this.isValidUrl._protocolRegex.test(url)){
            url = 'http://' + url;
        }
        return this.isValidUrl._urlRegex.test(url);
    },

    /**
     * Converts the given string into a "sluggable" string that's suitable for using in a URL.
     *
     * * Leading & trailing whitespace and apostrophes and quotes are removed.
     * * Non-alphanumeric characters are converted into hyphens.
     * * The string is lowercased.
     *
     * Hyphens are used, rather than underscores, based on [Google's webmaster recommendation for URLs](http://support.google.com/webmasters/bin/answer.py?hl=en&answer=76329).
     * @param {string} str String to slugify
     * @return String converted string
     */
    slugify: function(str) {
        return str
            .replace(/^\s+|\s+$|['`"]/g, '') // strip leading & trailing whitespace and remove apostrophes & quotes
            // TK - these result in nicer URLs, but worth including the additional localized labels on every page request?
            // .replace(/\s+@\s+/g, ' at ')  // @ -> at
            // .replace(/\s+&\s+/g, ' and ') // & -> and
            // .replace(/\s+\/\s+/g, ' or ') // / -> or
            .replace(/[\s~!@#$%^&*()_+={}\[\]:;<>,.?\/\\|]/g, '-')
            .replace(/\-+/g, '-')
            .toLowerCase();
    },

    /**
     * Strip all HTML tags from a string
     * @param {string} inputString string to remove HTML tags
     * @param {string} allowed string to allow HTML tags. i,e '<img><table>'
     * @return {string} String after removing tags
     */
    stripTags: function(inputString, allowed) {
        allowed = (((allowed || '') + '').toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join('')
        var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi
        return inputString.replace(tags, function ($0, $1) {
            return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : ''
        })
    }
};

/**
 * Contains functions to encode and decode strings.
 * @namespace
 */
RightNow.Text.Encoding = (function()
{
    var _base64characterMap = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_/.";

    return {
        /**
         * Decode a base64 encoded string
         * @param {string} inputString The encoded string to decode
         * @return {string} The decoded value
         */
        base64Decode: function(inputString)
        {
            if (!inputString || typeof(inputString) !== 'string')
                return "";

            //There are 3 special characters used in RightNow.Text encoding and decoding:
            // '=' goes to '.'. test string: q_id=1&
            // '/' stays the same. test string: <>? decoded string: PD4/
            // '+' goes to '_'. test string: <>>. decoded string: PD4_
            inputString = inputString.replace(/=/g, '.').replace(/\+/g, '_');

            var outputArray = [],
                i = 0;
            while (i < inputString.length)
            {
                var e1 = _base64characterMap.indexOf(inputString.charAt(i++)),
                    e2 = _base64characterMap.indexOf(inputString.charAt(i++));

                outputArray.push(String.fromCharCode((e1 << 2) | (e2 >> 4)));

                var e3 = _base64characterMap.indexOf(inputString.charAt(i++));
                if (0x40 === e3)
                    break;
                outputArray.push(String.fromCharCode(((e2 & 0x0F) << 4) | (e3 >> 2)));

                var e4 = _base64characterMap.indexOf(inputString.charAt(i++));
                if (e4 !== 0x40)
                    outputArray.push(String.fromCharCode(((e3 & 0x03) << 6) | e4));
            }
            return this.utf8Decode(outputArray.join(""));
        },

        /**
         * Take a string and base64 encode it
         * @param {string} inputString The plain text string to encode
         * @returns {string} The encoded value
         */
        base64Encode: function(inputString)
        {
            inputString = this.utf8Encode(inputString);

            var outputArray = [];
            for (var i = 0; i < inputString.length;)
            {
                var c1 = inputString.charCodeAt(i++);
                outputArray.push(_base64characterMap.charAt(c1 >> 2));

                var c2 = inputString.charCodeAt(i++);

                outputArray.push(_base64characterMap.charAt(((c1 & 0x03) << 4) | (c2 >> 4)));
                if (isNaN(c2))
                {
                    outputArray.push(_base64characterMap.charAt(64));
                    outputArray.push(_base64characterMap.charAt(64));
                }
                else
                {
                    var c3 = inputString.charCodeAt(i++);
                    outputArray.push(_base64characterMap.charAt(((c2 & 0x0F) << 2) | (c3 >> 6)));
                    if (isNaN(c3))
                        outputArray.push(_base64characterMap.charAt(64));
                    else
                        outputArray.push(_base64characterMap.charAt(c3 & 0x3F));
                }
            }
            return outputArray.join("");
        },

        /**
         * Returns the Ascii prefix of the string
         * @param {string} inputString The string to parse for the the Ascii prefix
         * @return {string} Ascii prefix
         */
        getAsciiPrefix: function(inputString)
        {
            for (var i = 0; i < inputString.length; ++i)
            {
                if (0x80 <= inputString.charCodeAt(i))
                    break;
            }
            if (i >= inputString.length)
                return inputString;
            return inputString.substring(0, i);
        },

        /**
         * Decodes a utf8 string
         * @param {string} inputString The string to decode
         * @return {string} The decoded value
         */
        utf8Decode: function(inputString)
        {
            var asciiPrefix = this.getAsciiPrefix(inputString);
            if (asciiPrefix.length === inputString.length)
                return asciiPrefix;
            var outputArray = [], c2, c3;
            for (var i = asciiPrefix.length; i < inputString.length; ++i)
            {
                var c = inputString.charCodeAt(i);
                if (c < 0x80)
                {
                    outputArray.push(String.fromCharCode(c));
                }
                else if (c < 0xC0)
                {
                    throw new Error("I can't decode the character at index " + i + " of " + inputString + " because it's not a valid UTF8 sequence.");
                }
                else if (c < 0xE0)
                {
                    if (inputString.length <= ++i)
                        throw new Error("I can't decode the character at index " + i + " of " + inputString + " because it's not a valid UTF8 sequence.");
                    c2 = inputString.charCodeAt(i);
                    outputArray.push(String.fromCharCode(((c & 31) << 6) | (c2 & 0x3F)));
                }
                else
                {
                    c2 = inputString.charCodeAt(++i);
                    c3 = inputString.charCodeAt(++i);
                    if (inputString.length <= i)
                        throw new Error("I can't decode the character at index " + i + " of " + inputString + " because it's not a valid UTF8 sequence.");
                    outputArray.push(String.fromCharCode(((c & 15) << 12) | ((c2 & 0x3F) << 6) | (c3 & 0x3F)));
                }
            }
            return asciiPrefix + outputArray.join("");
        },

        /**
         * Encode a string to utf8
         * @param {string} inputString The string to encode
         * @return {string} The encoded value
         */
        utf8Encode: function(inputString)
        {
            var asciiPrefix = this.getAsciiPrefix(inputString);
            if (asciiPrefix.length === inputString.length)
                return asciiPrefix;
            var outputArray = [];
            for (var i = asciiPrefix.length; i < inputString.length; ++i)
            {
                var c = inputString.charCodeAt(i);
                if (c < 0x80)
                {
                    outputArray.push(inputString.charAt(i));
                }
                else if (c < 0x800)
                {
                    outputArray.push(String.fromCharCode((c >> 6) | 0xC0));
                    outputArray.push(String.fromCharCode((c & 0x3F) | 0x80));
                }
                else
                {
                    outputArray.push(String.fromCharCode((c >> 12) | 0xE0));
                    outputArray.push(String.fromCharCode(((c >> 6) & 0x3F) | 0x80));
                    outputArray.push(String.fromCharCode((c & 0x3F) | 0x80));
                }
            }
            return asciiPrefix + outputArray.join("");
        },

        /**
         * Encode a string to utf8
         * @param {string} inputString The string to encode
         * @return {number} The length of the string
         */
        utf8Length: function(inputString)
        {
            var i, sz, len = 0;
            for (i = 0, sz = inputString.length; i < sz; i++) {
                if (inputString.charCodeAt(i) < 0x0080)
                    len += 1;
                else if (inputString.charCodeAt(i) < 0x0800)
                    len += 2;
                else
                    len += 3;
            }
            return(len);
        }
    };
}());
