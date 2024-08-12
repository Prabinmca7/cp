RightNow.UI = RightNow.UI || {};

/**
 * Contains any functions which relate to the development panel that is used at the
 * top of the page in development mode. Note: This namespace does not exist in production mode
 * @namespace
 */
RightNow.UI.DevelopmentHeader = (function(){
    var Y,
        _deferredInit = null,
        _developmentHeaderPanel = null,
        _errorCount = 0,
        _originalUrl = null,
        _warningCount = 0,
        _adminBaseUrl = '',
        _ready = false,
        _widgetsFiltered = false,

        /**
         * @inner
         * Hides / shows the next table row of the thing clicked on.
         * @param {object} e Click event
         */
        _toggleWidgetRow = function(e) {
            e.halt();
            e.currentTarget.ancestor('tr').next('tr').toggleView();
        },
        /**
         * @inner
         * Hides / shows the element specified by the clicked thing's `data-toggle` attribute.
         * Swaps out a + / - on the element specified by the clicked thing's `data-toggle-icon`.
         * @param {object} e Click event
         */
        _togglePanel = function(e) {
            var icons = ['-', '+'],
                target = e.currentTarget,
                sectionToExpand = Y.one('#' + target.getAttribute('data-toggle')).toggleView();

            Y.one('#' + e.currentTarget.getAttribute('data-toggle-icon')).setHTML((sectionToExpand.getComputedStyle('display') === 'none')
                ? icons.pop() : icons.shift());
        },
        /**
         * @inner
         * Toggles the behaviour of 'inspectAllWidgetSource' function on press of 'alt'+'i' keys
         * @param {object} e keypress event
        **/
        _inspectWidgetShortcut = function(e){
            if (e.altKey && e.keyCode == 73) {
                // if alt key is pressed along with 'i'
                RightNow.UI.WidgetInspector.inspectAllWidgetSource();
            }
        },
        /**
         * @inner
         * Hides / shows the element specified by the clicked thing's `data-toggle` attribute.
         * Swaps out its text with what's in its `data-toggle-text` attribute.
         * @param {object} e Click event
         */
        _toggleDetails = function(e) {
            e.halt();
            var target = e.currentTarget,
                toggleText = target.getHTML();

            Y.one('#' + target.getAttribute('data-toggle')).toggleView();
            if(target.getAttribute('data-toggle') && !_widgetsFiltered) {
                RightNow.UI.WidgetInspector.filterWidgets();
                _widgetsFiltered = true;
            }
            target.setHTML(target.getAttribute('data-toggle-text')).setAttribute('data-toggle-text', toggleText);
        },
        /**
         * @inner
         * Sets up event listeners on the panel header.
         * @param {object} header Panel header
         */
        _initHeaderEvents = function(header) {
            var shiftClass = 'rn_ShiftMode',
                hoverClass = 'rn_Entered',
                body = Y.one(document.body),
                headerPanel = Y.one('#rn_DevelopmentHeaderPanel'),
                panelTitle = headerPanel.one('.rn_PanelTitle');

            header.on('click', function(e){
                if (e.shiftKey) {
                    // Cheat code: shift+click goes to admin page.
                    window.location = _adminBaseUrl + '/ci/admin/overview';
                }
                else {
                    headerPanel.toggleClass('rn_ExpandedHeader');
                    Y.one('#rn_ExpandedDevelopmentHeader').toggleView();
                }
            });

            function swapHeaderTitle(backToDefault) {
                panelTitle.setHTML(panelTitle.getAttribute((backToDefault) ? 'data-default' : 'data-alternate'));
            }
            body.on('keydown', function(e) {
                if (e.shiftKey) {
                    if (headerPanel.addClass(shiftClass).hasClass(hoverClass)) {
                        swapHeaderTitle();
                    }
                }
            });
            body.on('keyup', function(e) {
                if (e.keyCode === 16) {
                    headerPanel.removeClass(shiftClass);
                    swapHeaderTitle(true);
                }
            });
            header.on('mouseover', function(e) {
                if (headerPanel.addClass(hoverClass).hasClass(shiftClass)) {
                    swapHeaderTitle();
                }
            });
            header.on('mouseout', function(e) {
                headerPanel.removeClass(hoverClass);
                swapHeaderTitle(true);
            });
        },
        /**
         * @inner
         * The view's HTML needs to contain .yui3-widget-* selectors so that the Panel can be built correctly.
         * However, we don't want users' overly-generic CSS to override the dev header's default styling.
         * So once the Panel's been built, replace YUI's class names.
         * @param  {object} panelNode Panel's src node
         */
        _replaceYUIClasses = function(panelNode) {
            var prefix = 'rn_DevelopmentHeader',
                replacements = [
                    ['yui3-widget-hd',      prefix + 'AlwaysVisible'],
                    ['yui3-widget-bd',      prefix + 'ExpandingSection'],
                    ['yui3-widget-buttons', prefix + 'Buttons'],
                    ['yui3-button',         prefix + 'Close']
                ];

            panelNode
                .set('className', '')
                .get('parentNode').set('className', prefix + 'PanelContainer');

            Y.Array.each(replacements, function(replacement, node) {
                node = panelNode.one('.' + replacement[0]);
                if (node) {
                    // widget-bd isn't present for Staging header.
                    node.set('className', replacement[1]);
                }
            });
        },

        /**
         * @inner
         * Sets up the panel.
         * @param {string} originalUrl url to use when appending url parameters
         * @param {number} errors Number of errors
         * @param {number} warnings Number of warnings
         * @param {string} adminBaseUrl Base for admin pages
         */
        _initializePanel = function(originalUrl, errors, warnings, adminBaseUrl)
        {
            _originalUrl = originalUrl;
            _errorCount = errors;
            _warningCount = warnings;
            _adminBaseUrl = adminBaseUrl;

            var src = Y.one('#rn_DevelopmentHeaderPanel').setStyle("display", "block");
            _developmentHeaderPanel = new Y.Panel({
                srcNode: src,
                visible: true,
                render: true,
                zIndex: 200000,
                //Set the width to be 1/3 of the screen with min and max widths
                width: Math.min(Math.max(Y.one("body").get("winWidth") / 3, 455), 825),
                hideOn: [/* Override default behavior that hides the panel on ESC keypress */],
                buttons: [{label: '\u00D7', section: Y.WidgetStdMod.HEADER, action: function(){this.destroy(true);}}]
            });

            _replaceYUIClasses(src);
            _initHeaderEvents(_developmentHeaderPanel.getStdModNode(Y.WidgetStdMod.HEADER));

            var expandedHeader = Y.one('#rn_ExpandedDevelopmentHeader');
            expandedHeader.delegate('click', _togglePanel, 'h3[data-toggle]');
            expandedHeader.delegate('click', _toggleDetails, 'a[data-toggle]');
            expandedHeader.delegate('click', _toggleWidgetRow, 'a[data-expand-next-row]');
            _ready = true;
            if(Y.one('.rn_DevelopmentHeaderExpandingSection')) {
                RightNow.UI.WidgetInspector.init();
                expandedHeader.delegate('click', RightNow.UI.WidgetInspector.inspectWidgetSource, '.rn_widgetList');
                expandedHeader.delegate('click', RightNow.UI.WidgetInspector.inspectAllWidgetSource, '.rn_InspectAll');
                Y.one("#saveWidgetAttrs").delegate('click', RightNow.UI.WidgetInspector.checkWidgetErrors, '#saveWidgetAttrsBtn');
                Y.one("#saveWidgetAttrs").delegate('click', function(){
                    location.reload();
                }, '#cancelWidgetChangesBtn');
                document.addEventListener('keyup', _inspectWidgetShortcut, false);
            }
            _displayDeferredNotices();
        },
        /**
         * @inner
         * Appends HTML to make error or warning messages appear.
         * @param {string} message Error or warning message
         * @param {string} titleMessage Title for the icon and label
         * @param {string} imageID id for the icon to display for the message
         * @param {string} labelID label for the message
         * @param {string} containerID id for the warning or error container
         * @param {string} parentElementID parent of the container
         */
        _updateWarningOrError = function(message, titleMessage, imageID, labelID, containerID, parentElementID)
        {
            var titleMessageElement = Y.one("#" + labelID);
            if(titleMessageElement)
            {
                titleMessageElement.set('innerHTML', titleMessage);
            }
            Y.one("#" + imageID)
                .setStyle("display", "inline")
                .set("title", titleMessage)
                .set("alt", titleMessage);
            var ulContainer = Y.one("#" + containerID);
            if(!ulContainer)
            {
                Y.one("#" + parentElementID).appendChild(
                    Y.Node.create("<UL>").set("id", containerID)
                );
            }
            Y.one("#" + containerID).appendChild(
                Y.Node.create("<li></li>").set("innerHTML", message)
            );
            Y.one("#rn_ErrorsAndWarnings").setStyle("display", "block");
        },
        _deferredNotices = [],
        /**
         * @inner
         * Stores a notice to user later.
         * @param {string} message Error / Warning message
         * @param {string} type error or warning
         */
        _deferNotice = function(message, type) {
            _deferredNotices.push({message: message, type: type});
        },
        /**
         * @inner
         * Displays the deferred messages.
         */
        _displayDeferredNotices = function() {
            var header = RightNow.UI.DevelopmentHeader;
            Y.Array.each(_deferredNotices, function(notice) {
                header[((notice.type === 'error') ? 'addJavascriptError' : 'addJavascriptWarning')](notice.message);
            });
        };

    return {
        /**
         * Adds an error to the development header. This function should not be used directly
         * @see RightNow.UI.addDevelopmentHeaderError
         * @param {string} errorMessage The error message to display
         */
        addJavascriptError: function(errorMessage)
        {
            if (!_ready) return _deferNotice(errorMessage, 'error');

            _errorCount += 1;
            var errorTitle = (_errorCount === 1) ? RightNow.Interface.getMessage("YOU_HAVE_ONE_ERROR_PAGE_LBL") : RightNow.Text.sprintf(RightNow.Interface.getMessage("YOU_HAVE_PCT_D_ERRORS_PAGE_LBL"), _errorCount);
            _updateWarningOrError(errorMessage, errorTitle, "rn_PanelTitleErrorImage", "rn_ErrorCountLabel", "rn_ErrorInformationList", "rn_DevHeaderErrors");
            Y.one("#rn_DevHeaderErrors").addClass("rn_ErrorHighlight");
        },

        /**
         * Adds a warning to the development header. This function should not be used directly
         * @see RightNow.UI.addDevelopmentHeaderWarning
         * @param {string} warningMessage The warning message to display
         */
        addJavascriptWarning: function(warningMessage)
        {
            if (!_ready) return _deferNotice(warningMessage, 'warning');

            _warningCount += 1;
            var warningTitle = (_warningCount === 1) ? RightNow.Interface.getMessage("YOU_HAVE_ONE_WARNING_PAGE_LBL") : RightNow.Text.sprintf(RightNow.Interface.getMessage("YOU_HAVE_PCT_D_WARNINGS_PAGE_LBL"), _warningCount);
            _updateWarningOrError(warningMessage, warningTitle, "rn_PanelTitleWarningImage", "rn_WarningCountLabel", "rn_WarningInformationList", "rn_DevHeaderWarnings");
            Y.one("#rn_DevHeaderWarnings").addClass("rn_WarningHighlight");
        },

        /**
         * Display development header panel and position it correctly
         * @param {string} originalUrl Url of page when loaded
         * @param {number} errors Number of errors on the page
         * @param {number} warnings Number of warnings on the page
         * @param {string} adminBaseUrl Url for admin pages
         */
        initializePanel: function(originalUrl, errors, warnings, adminBaseUrl)
        {
            if (Y) {
                _initializePanel(originalUrl, errors, warnings, adminBaseUrl);
            }
            else {
                _deferredInit = arguments;
            }
        },

        /**
        * Set the YUI instance for this object.
        * @private
        */
        provideYUI: function(yui) {
            Y = yui;
            if (_deferredInit) {
                RightNow.UI.DevelopmentHeader.initializePanel.apply(this, _deferredInit);
                _deferredInit = null;
            }
        },

        /**
         * Updates the page URL to add any URL parameters specified
         */
        updateUrlParameters: function()
        {
            var url = _originalUrl;
            Y.one("#rn_DevelopmentHeaderUrlParameterList").all("input[type='text']")
                .each(function(i){url = RightNow.Url.addParameter(url, i.get("name"), i.get("value"));});
            window.location = url;
        }
    };
}());

YUI().use('node-base', 'node-core', 'node-style', 'panel', 'cookie', RightNow.UI.DevelopmentHeader.provideYUI);
