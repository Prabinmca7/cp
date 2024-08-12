RightNow.UI = RightNow.UI || {};
/**
 * Contains any functions which relate to the widget inspection of development panel that is used at the
 * top of the page in development mode. Note: This namespace does not exist in production mode
 * @namespace
 */
RightNow.UI.WidgetInspector = (function() {
    var _currentWidgetID = false,
        _toggleAllWidgetsInspected = false,
        _dialogDisplayed = false,
        _attributeData = null,
        _widgetProcessed = false,
        _widgetHidden = false,
        _numInspected = 0,
        _unInspectedWidgets = null,
        _totalInspectableWidgets = null,
        _attributeDialogs = {},
        _htmlReplacer = {"&":"&amp;", "<":"&lt;", ">":"&gt;", '"':"&quot;", "'":"&#x27;", "/":"&#x2F;", "`":"&#x60;"},
        _pageContents = {
            "body": {
                "textArea": "bodyContent"
            },
            "template": {
                "textArea": "templateContent"
            }
        },

        /**
         * Initializes the code editors
         *
         */
        _init = function() {
            var _codeTextArea,
                types = Object.keys(_pageContents),
                i;
            for (i = 0; i < types.length; i++) {
                _codeTextArea = document.getElementById(_pageContents[types[i]].textArea);
                if (!_codeTextArea) {
                    continue;
                }
                _pageContents[types[i]].path = types[i] === "body" ? pagePath : templatePath;
                _pageContents[types[i]].editor = CodeMirror(function(elt) {
                    _codeTextArea.parentNode.replaceChild(elt, _codeTextArea);
                }, {
                    lineSeparator: "\n",
                    value: document.getElementById("code_" + _pageContents[types[i]].textArea).value,
                    mode: "text/html",
                    showCursorWhenSelecting: true,
                    autoFocus: true,
                    overwrite: true,
                    lineNumbers: true
                });
            }
        },

        /**
         * Clears the _currentWidgetID variable, so that the attribute dialog box
         * can be opened more than once.
         */
        _exitAttributesDialog = function(widgetContainerID) {
            _attributeDialogs[widgetContainerID].destroy();
            _attributeDialogs[widgetContainerID] = null;
        },

        /**
         * @inner
         * When a widget's name is clicked,  the attributes of that
         * widget are displayed.
         * @param {object} e Click event
         */
        _showAttributesInDialog = function(e) {
            e.stopPropagation();
            e.preventDefault();
            var widgetContainerID;
            //This handles the hidden widgets attributes, the widget ID is passed through data-meta.
            if (!_attributeData) _attributeData = new EJS({
                text: Y.one('#widgetAttributeData').getHTML()
            });
            if (e.currentTarget.getAttribute('data-meta')) {
                widgetContainerID = e.currentTarget.getAttribute('data-meta');
            } else {
                widgetContainerID = e.currentTarget.ancestor("*").getAttribute('id');
            }
            if (_attributeDialogs[widgetContainerID]) {
                _attributeDialogs[widgetContainerID].show();
                return;
            }
            _attributeDialog = _createAttributeDialog(widgetContainerID, e.currentTarget.getAttribute("data-path"), e.currentTarget.hasClass("rn_AttributeLink"), isReference);
            _attributeDialog.show();
            _attributeDialogs[widgetContainerID] = _attributeDialog;
            Y.one('#' + _attributeDialog.get('id')).addClass('rn_attributesDialog');
        },

        /**
         * @inner
         * Highlights all the widgets on the page. When a widget's name is clicked,  the attributes of that
         * widget are displayed.
         * @param {object} e Click event
         */
        _inspectAllWidgetSource = function(disable) {
            if(disable === undefined) {
                disable = true;
            }
            if (document.cookie.indexOf('inspection') > -1 && disable) {
                document.cookie = 'developmentInspector=inspection' + '; Path = /; expires = Thu, 01 Jan 1970 00:00:01 UTC;';           }
            var node = Y.one('.rn_InspectAll');
            if(node.hasClass('fa-square-o')) {
                node.replaceClass('fa-square-o', 'fa-check-square-o');
            } else if (node.hasClass('fa-minus-square-o')) {
                node.replaceClass('fa-minus-square-o', 'fa-square-o');
            } else {
                node.replaceClass('fa-check-square-o', 'fa-square-o');
            }
            _toggleAllWidgetsInspected = !_toggleAllWidgetsInspected;
            for (var i = 0; i < allWidgets.length; i++) {
                _inspectWidgetSource(allWidgets[i]);
            }
            _toggleSaveWidgetPanel();
        },

        /**
         * @inner
         * Searches the page for the widget that has been clicked and scrolls to the widget if
         * it isn't hidden. When the widget name is clicked, the attributes of that widget are displayed.
         * @param {string|object} e This is a string when the function is called from _inspectAllWidgetSource and click event otherwise.
         */
        _inspectWidgetSource = function(e) {
            var inspectAllWidgets = false;
            _dialogDisplayed = false;
            var clickedWidgetName;
            _widgetProcessed = false;
            _widgetHidden = false;
            var node = Y.one('.rn_InspectAll');
            if (!e.currentTarget) {
                clickedWidgetName = e;
                inspectAllWidgets = true;
            } else {
                e.preventDefault();
                clickedWidgetName = e.currentTarget.getAttribute('data-widget-name');
            }
            Y.all("[data-widget-identifier=" + clickedWidgetName + "]").each(function(node) {
                var clickedWidgetID = node.getAttribute('id');
                //This condition is to process cases where a widget has 2 classes, so we ensure that it is processed only once by verifying the widget ID
                if ('rn_' + clickedWidgetName === clickedWidgetID.substring(0, clickedWidgetID.indexOf('_', 3))) {
                    _processNode(node, inspectAllWidgets, clickedWidgetName, e);
                }
            });
            Y.one('body').delegate('click', _showAttributesInDialog, '.rn_widgetNote', this);
            _toggleSaveWidgetPanel();
            if (!inspectAllWidgets) {
                _unInspectedWidgets = _totalInspectableWidgets - _numInspected;
                if (!_numInspected) {
                    if (node.hasClass('fa-minus-square-o')) {
                        _toggleAllWidgetsInspected = !_toggleAllWidgetsInspected;
                        node.replaceClass('fa-minus-square-o', 'fa-square-o');
                    }
                    else if (node.hasClass('fa-check-square-o')) {
                        _toggleAllWidgetsInspected = !_toggleAllWidgetsInspected;
                        node.replaceClass('fa-check-square-o', 'fa-square-o');
                    }
                }
                else if (!_unInspectedWidgets) {
                    if (node.hasClass('fa-minus-square-o')) {
                        node.replaceClass('fa-minus-square-o', 'fa-check-square-o');
                    }
                    else if (node.hasClass('fa-square-o')) {
                        _toggleAllWidgetsInspected = !_toggleAllWidgetsInspected;
                        node.replaceClass('fa-square-o', 'fa-check-square-o');
                    }
                }
                else {
                    if (node.hasClass('fa-square-o')) {
                        _toggleAllWidgetsInspected = !_toggleAllWidgetsInspected;
                        node.replaceClass('fa-square-o', 'fa-minus-square-o');
                    }                  
                    else if (node.hasClass('fa-check-square-o')) {
                        node.replaceClass('fa-check-square-o', 'fa-minus-square-o');
                    } 
                }                                     
            }
        },

        /**
         * @inner
         * Process every instance of a widget on a page, toggling the inspect, and handling cases of hidden widgets as well.
         * @param {YUI node} node The node/instance of the widget to be processed
         * @param {boolean} inspectAllWidgets Decides the flow in which the node is to be processed, i.e inspecting all widgets or a single widget
         * @param {string} clickedWidgetName Name of the widget being processed
         * @param {object} e Click event
         */
        _processNode = function(node, inspectAllWidgets, clickedWidgetName, e) {
            var clickedWidgetID = node.getAttribute('id');
            var widgetNoteId = clickedWidgetID + "_Note";
            var checkboxNode = e.currentTarget ? Y.one('#' + e.currentTarget.getAttribute('id')) : Y.one('#Inspect' + e);
            //handling hidden widgets. If the widget is in hidden state, we only show the dialog and return.
            if ((node.hasClass('rn_Hidden') || node.ancestor('.rn_Hidden')) && !_dialogDisplayed) {
                if (!inspectAllWidgets) {
                    _dialogDisplayed = true;
                    var dialogObj = RightNow.UI.Dialog.messageDialog(RightNow.Text.sprintf(RightNow.Interface.getMessage('SOME_INST_ID_BUT_SEE_ITS_SATTRIBUTESS_MSG'), '<a data-path="' + node.getAttribute('data-widget-path') + '" data-meta="' + clickedWidgetID + '" class="rn_AttributeLink">', '</a>') + '<div class="rn_InfoMessage">' + RightNow.Interface.getMessage('REVEALED_DISP_TB_DD_OP_ADDTL_T_EXPOSED_MSG') + '</div>', {
                        width: '35%'
                    });
                    var closeMsgDialogAndShowAttrDialog = function(e){
                        dialogObj.hide();
                        _showAttributesInDialog(e);
                    }
                    Y.one('body').delegate('click', closeMsgDialogAndShowAttrDialog, '.rn_AttributeLink', this);
                }
                _widgetHidden = true;
                return;
            }
            //if a hidden widget is now visible, we need to make it available in the list of widgets. Clicking on the exlamation mark now makes it available to inspect
            if (checkboxNode.hasClass('fa-info-circle') && !_widgetHidden) {
                checkboxNode.replaceClass('fa-info-circle', 'fa-square-o');
                _totalInspectableWidgets++;
            }
            //This entire if section deals with checking or unchecking the checkbox in the inspect column. We want to do this only once for all instance of a widget.
            if (!_widgetProcessed && !_widgetHidden) {
                _widgetProcessed = true;
                if (!inspectAllWidgets && e.currentTarget.getAttribute('data-inspected') === 'notInspected') {
                    checkboxNode.replaceClass("fa-square-o", "fa-check-square-o");
                    e.currentTarget.setAttribute('data-inspected', 'Inspected');
                    _numInspected++;
                } else if (!inspectAllWidgets && e.currentTarget.getAttribute('data-inspected') !== 'notInspected') {
                    checkboxNode.replaceClass("fa-check-square-o", "fa-square-o");
                    e.currentTarget.setAttribute('data-inspected', 'notInspected');
                    _numInspected--;
                } else if (inspectAllWidgets && _toggleAllWidgetsInspected) {
                    if (!checkboxNode.hasClass("fa-check-square-o")) {
                        checkboxNode.replaceClass("fa-square-o", "fa-check-square-o");
                        _numInspected++;
                    }
                    checkboxNode.setAttribute('data-inspected', 'Inspected');
                } else if (inspectAllWidgets && !_toggleAllWidgetsInspected) {
                    if (checkboxNode.hasClass("fa-check-square-o")) {
                        checkboxNode.replaceClass("fa-check-square-o", "fa-square-o");
                        _numInspected--;
                    }
                    checkboxNode.setAttribute('data-inspected', 'notInspected');
                }
            }
            //adding widget name link that will show the attributes on clicking
            if (!Y.one('#' + widgetNoteId)) {
                node.prepend('<div data-widget-name="' + clickedWidgetName + '" class="rn_widgetNote tooltip" id ="' + widgetNoteId + '" data-path="' + node.getAttribute('data-widget-path') + '" ><b>' + clickedWidgetName + '</b><span class="tooltiptext">' + RightNow.Interface.getMessage("VIEW_ATTRIBUTES_LBL") + '</span></div>');
            }
            //toggling highlight for the different flows
            if (node.hasClass('rn_HighlightWidget') && !_toggleAllWidgetsInspected) {
                node.removeClass('rn_HighlightWidget');
                Y.all('.rn_' + clickedWidgetName + ' ' + ".rn_widgetNote").setStyle('display', 'none');
            } else if (node.hasClass('rn_HighlightWidget') && !inspectAllWidgets && _toggleAllWidgetsInspected) {
                node.removeClass('rn_HighlightWidget');
                Y.all('.rn_' + clickedWidgetName + ' ' + ".rn_widgetNote").setStyle('display', 'none');
            } else if (!node.hasClass('rn_HighlightWidget') && inspectAllWidgets && _toggleAllWidgetsInspected) {
                node.addClass('rn_HighlightWidget');
                Y.all('.rn_' + clickedWidgetName + ' ' + ".rn_widgetNote").setStyle('display', 'inline-block');
            } else if (!node.hasClass('rn_HighlightWidget') && !inspectAllWidgets) {
                node.addClass('rn_HighlightWidget');
                Y.all('.rn_' + clickedWidgetName + ' ' + ".rn_widgetNote").setStyle('display', 'inline-block');
            }
        },

        /**
         * Classifies and filters out widgets that don't have views and widgets that are hidden. Runs only once
         */
        _filterWidgets = function() {
            _totalInspectableWidgets = allWidgets.length;
            if(Y.one('header')) {
                Y.one('header').addClass('rn_headerHeight');
            }
            for (var i = 0; i < allWidgets.length; i++) {
                var node = Y.one('.rn_' + allWidgets[i]);
                var widgetNode = Y.one('#Inspect' + allWidgets[i]);
                if (!node) {
                    widgetNode.ancestor().addClass('tooltip').append('<span class="tooltiptext">' + RightNow.Interface.getMessage("THIS_WIDGET_HAS_NO_VIEW_LBL") + '</span>');
                    widgetNode.replaceClass('rn_widgetList', 'rn_opaque');
                    _totalInspectableWidgets--;
                } else if ((node.hasClass('rn_Hidden') || node.ancestor('.rn_Hidden')) && widgetNode.hasClass('fa-square-o')) {
                    widgetNode.replaceClass('fa-square-o', 'fa-info-circle');
                    _totalInspectableWidgets--;
                }
            }
        },

        /**
         * Finds the widget line in the page php code and updates the modified attribute values.
         *
         * @param {string} widgetID Widget Identifier
         * @param {string} path Widget Path
         * @return boolean True If widget is changed else false
         */
        _findAndModifyTheWidgetLine = function(widgetID, path) {
            var o = Y.one("#" + widgetID),
                widgetPosition = o.getAttribute("data-widget-position"),
                subid = o.getAttribute("data-subid"),
                parent;
            if (!widgetPosition) {
                parent = o.ancestor("[data-widget-position]");
                widgetPosition = parent ? parent.getAttribute("data-widget-position") : "";
            }
            if (!widgetPosition) return {
                result: false,
                errors: true
            };
            var positions = widgetPosition.split("@"),
                start = parseInt(positions[0], 10),
                widgetLineLength = parseInt(positions[2], 10),
                _editor = _pageContents[positions[1]].editor,
                phpFile = _editor.getValue(),
                widgetLine = _substringUTF8(phpFile, start, widgetLineLength);
                attrs = _getModifiedAttributes(widgetID, subid);
                widgetLine = _escapeAttributeValues(widgetLine, widgetID);
            if (attrs.errors && attrs.errors.length > 0) {
                Y.each(attrs.errors, function(r) {
                    Y.each(r.errors, function(e) {
                        _addErrorMessage(e.message, r.elem, widgetID);
                    });
                });
                return {
                    result: false,
                    errors: true
                };
            }
            if (Object.keys(attrs.modifiedAttrs).length === 0 && attrs.anyAttrModified === false)
                return {
                    result: false
                };
            var modifiedWidgetLine = _replaceWidgetLineWithAtrributes(widgetLine, attrs),
                diff = _strLengthUTF8(modifiedWidgetLine) - widgetLineLength,
                modifedPhpFile = _substringUTF8(phpFile, 0, start) + modifiedWidgetLine + _substringUTF8(phpFile, start + widgetLineLength, _strLengthUTF8(phpFile) - (start + widgetLineLength));
            _pageContents[positions[1]].changed = true;
            _updateWidgetPositions(parent || o, positions, start, diff);
            _editor.setValue(modifedPhpFile);
            return {
                result: true
            };
        },

        /**
         * Finds the length of a string in UTF8 bytes
         * @param {string} str The string whose length is to be computed
         */
        _strLengthUTF8 = function(str) {
            var byteCounter = 0;
            var codePoint = 0;
            var i = 0;
            for (i = 0; i < str.length; i++) {

                // find the code point corressponding to the character and then the bytes required for it

                codePoint = str.charCodeAt(i);
                byteCounter += _getUTF8Length(codePoint);
            }
            return byteCounter;
        }

        /**
         * Returns a substring of the specified string with inputs in UTF8 bytes
         * @param {string} str The string for which the substring is to be obtained
         * @param {integer} start The starting character of the string in UTF8 bytes
         * @param {integer} length The length of the string in UTF8 bytes
         */
        _substringUTF8 = function(str, start, length) {
            var resultStr = '';
            var characterCounter = 0;
            var codePoint = 0;
            var byteCounter;

            // parse string till we reach the reach the required starting character

            for (byteCounter = 0; byteCounter < start; characterCounter++) {

                // find the code point corressponding to the character and then the bytes required for it
                codePoint = str.charCodeAt(characterCounter);
                byteCounter += _getUTF8Length(codePoint);
            }

            // The correct starting position is now available in characterCounter
            //The final character will be available when the bytecounter is further incremented by the length specified as input. This final length is stored in end.
            var end = characterCounter + length - 1;

            for (n = characterCounter; end >= characterCounter; n++) {
                //for each character, we now decrement the end variable by the appropriate byte size and append the characters to the final string.
                codePoint = str.charCodeAt(n);
                end -= _getUTF8Length(codePoint);
                resultStr += str[n];
            }

            return resultStr;
        },

        /**
         * Finds the number of bytes required by the UTF8 code point
         * @param {integer} code UTF8 code point
         */
        _getUTF8Length = function(code) {
            var len = 0;

            if (code <= 0x7f) {
              len = 1;
            } else if (code <= 0x7ff) {
              len = 2;
            } else if (code >= 0xd800 && code <= 0xdfff) {
              // Surrogate pair: These take 4 bytes in UTF-8 but they occur in pairs
              len = 2;
            } else if (code < 0xffff) {
              len = 3;
            } else {
              len = 4;
            }
            return len;
        },

        /**
         * Updates the character positions of a widget after attribute changes
         *
         * @param  {object} o Widget YUI Node Element
         * @param  {array} positions Array of widget char positions
         * @param  {int} start The character position of widget line
         * @param  {int} diff The difference to be updated in widget current position
         */
        _updateWidgetPositions = function(o, positions, start, diff) {
            var oldPosition = positions.join("@");
            positions[2] = parseInt(positions[2], 10) + diff;
            var newPosition = positions.join("@");
            o.setAttribute("data-widget-position", newPosition);
            Y.all("[data-widget-position='" + oldPosition + "']").each(function(elem) {
                elem.setAttribute("data-widget-position", newPosition);
            });
            Y.all("[data-widget-position*='" + positions[1] + "']").each(function(elem) {
                if (elem.getAttribute("id") !== o.getAttribute("id")) {
                    var pos = elem.getAttribute("data-widget-position").split("@");
                    pos[0] = parseInt(pos[0], 10);
                    if (pos[0] > start) {
                        pos[0] += diff;
                        elem.setAttribute("data-widget-position", pos.join("@"));
                    }
                }
            });
        },

        /**
         * Replaces the widget line with new modified attribute values
         *
         * @param  {string} widgetLine Widget line to be modified
         * @param  {object} attrs Widget attributes to be modified
         * @return {string} Modified Widget Line
         */
        _replaceWidgetLineWithAtrributes = function(widgetLine, attrs) {
            var modifiedAttrs = JSON.parse(JSON.stringify(attrs.modifiedAttrs));
            var doc = _parseWidgetLine(widgetLine);
            var rootElem = doc.getElementsByTagName("root")[0];
            var widgetElem = rootElem.childNodes[0];
            Y.Array.each(Object.keys(modifiedAttrs), function(attr) {
                widgetElem.setAttribute(attr.replace(/:/g, "__colon__"), modifiedAttrs[attr]);
            });
            Y.Array.each(Object.keys(attrs.ignoredAttrs), function(attr) {
                if (attrs.ignoredAttrs[attr] && attrs.ignoredAttrs[attr].isDefault) {
                    if(attrs.ignoredAttrs[attr].isModified && attr.substring(0, 3) === "sub") {
                        widgetElem.setAttribute(attr.replace(/:/g, "__colon__"), attrs.ignoredAttrs[attr].value);
                    }
                    else if (attr.substring(0, 3) !== "sub") {
                        widgetElem.removeAttribute(attr.replace(/:/g, "__colon__"));
                    }
                }
            });
            modifedWidgetLine = _deserializeParsedWidgetXML(doc);
            modifedWidgetLine = _escape(modifedWidgetLine, true);
            return modifedWidgetLine;
        },

        /**
         * Encloses the widget line in an XML and parses to manipulate the attributes
         *
         * @param  string widgetLine Widget line to be processed
         * @return object XML object of a widget line to be processed
         */
        _parseWidgetLine = function(widgetLine) {
            var widgetLineXML = widgetLine;

            var isSingleLineWidget = widgetLine.substring(widgetLine.length - 2) === "/>";

            if (!isSingleLineWidget) {
                widgetLineXML += "a</widget>";
            }
            widgetLineXML
            widgetLineXML = "<root>" + widgetLineXML.replace(/^<rn:widget/, "<widget") + "</root>";
            var parser = new DOMParser();
            widgetLineXML = _replace(widgetLineXML, ":", "__colon__");
            var doc;
            try {
                doc = parser.parseFromString(widgetLineXML, "text/xml");
            } catch (e) {}
            return doc;
        },

        /**
         * Replaces sub attribute colons
         * @param  string widgetLine Widget line
         * @param  string from Pattern to be matched
         * @param  string to Value to be replaced
         * @return string Widget line
         */
        _replace = function(widgetLine, from, to) {
            var re = new RegExp("\\ssub" + from + "(.+?)=", "g");
            Y.Array.each(widgetLine.match(re), function(colonAttr) {
                widgetLine = widgetLine.replace(colonAttr, colonAttr.replace(new RegExp(from, "g"), to));
            });
            return widgetLine;
        },

        /**
         * Deserailizes the widget line XML
         *
         * @param  object widgetXML Parsed XML object of the widget line
         * @return string Widget line with the modified attribute values
         */
        _deserializeParsedWidgetXML = function(widgetXML) {
            var rootElem = widgetXML.getElementsByTagName("root")[0];
            var widgetElem = rootElem.childNodes[0];
            var serializer = new XMLSerializer();
            var modifedWidgetLine = serializer.serializeToString(widgetElem);
            modifedWidgetLine = modifedWidgetLine.replace(/a<\/widget>$/, '');
            modifedWidgetLine = _replace(modifedWidgetLine, "__colon__", ":");
            modifedWidgetLine = modifedWidgetLine.replace(/^<widget/, '<rn:widget');
            return modifedWidgetLine;
        },

        /**
         * Collects the modified attributes from dialog and updates the widget line.
         *
         * @param  {string} widgetID Widget Identifier
         * @param  {string} path Widget path
         * @param  {object} dialog Widget attribute dialog instance
         */
        _updateWidgetAttributes = function(widgetID, path, dialog) {
            var result = _findAndModifyTheWidgetLine(widgetID, path),
                widgetNote = Y.one(".rn_widgetNote[id=" + widgetID + "_Note]");
            if (result.result) {
                widgetNote.removeClass("rn_ErrorWidgetNote");
                if (!widgetNote.hasClass("rn_ModifiedWidgetNote")) {
                    widgetNote.one("b").set("innerHTML", widgetNote.getAttribute("data-widget-name") + " (" + RightNow.Interface.getMessage("CHANGED_LBL") + ")");
                }
                widgetNote.addClass("rn_ModifiedWidgetNote");
                dialog.hide();
            } else if (result.errors) {
                widgetNote.removeClass("rn_ModifiedWidgetNote");
                if (!widgetNote.hasClass("rn_ErrorWidgetNote")) {
                    widgetNote.one("b").set("innerHTML", widgetNote.getAttribute("data-widget-name") + "(" + RightNow.Interface.getMessage("ERRORS_LBL") + ")");
                }
                widgetNote.addClass("rn_ErrorWidgetNote");
            } else if (!result.errors) {
                widgetNote.removeClass("rn_ErrorWidgetNote").removeClass("rn_ModifiedWidgetNote");
                widgetNote.one("b").set("innerHTML", widgetNote.getAttribute("data-widget-name"));
                dialog.hide();
            }
            _toggleSaveWidgetPanel();
        },

        /**
         * Toggles the save widget panel
         */
        _toggleSaveWidgetPanel = function() {
            var saveAttributesPanel = Y.one("#saveWidgetAttrs");
            if (Y.one(".rn_HighlightWidget") && Y.one(".rn_ModifiedWidgetNote")) {
                saveAttributesPanel.addClass("rn_SaveWidgetPanel");
                window.onbeforeunload = function() {
                    return "";
                };
            } else {
                saveAttributesPanel.removeClass("rn_SaveWidgetPanel");
                window.onbeforeunload = function () {
                    // blank function do nothing
                }
            }
        },

        _checkWidgetErrors = function() {
            if (Y.one(".rn_ErrorWidgetNote")) {
                var message = RightNow.Interface.getMessage("WIDGET_CHANGES_ERRORS_WILL_IGNORED_MSG"),
                    buttons = [{
                        text: RightNow.Interface.getMessage("OK_LBL"),
                        handler: function() {
                            _saveAttributes();
                        },
                        isDefault: true
                    }, {
                        text: RightNow.Interface.getMessage("CANCEL_LBL"),
                        handler: function() {
                            this.hide();
                        },
                        isDefault: false
                    }],
                    messageNode = Y.Node.create("<div>" + message + "</div>");
                RightNow.UI.Dialog.actionDialog(RightNow.Interface.getMessage("WARNING_LBL"), messageNode, {
                    "buttons": buttons,
                    "dialogDescription": ""
                }).show();
                return;
            }
            Y.one("#saveWidgetAttrsBtn").setAttribute("disabled", true);
            _saveAttributes();
        },

        _showWarningOnSave = function(failureOccured){
            if (failureOccured.length > 0) {
                var message;
                Y.one("#saveWidgetAttrsBtn").setAttribute("disabled", false);
                if (failureOccured.length > 1) {
                    message = RightNow.Interface.getMessage("COL_SAVE_ED_ERR_L_INV_E_PERMISSIONS_MSG");
                } else {
                    message = failureOccured[0] === "template" ? RightNow.Interface.getMessage("TEMPL_COL_SAVE_ED_ERR_INV_TEMPL_PRMSSNS_MSG") : RightNow.Interface.getMessage("PG_COL_SAVE_ED_ERR_L_INV_E_PERMISSIONS_MSG");
                }
                RightNow.UI.Dialog.messageDialog(message, {
                    exitCallback: function() {
                        location.reload();
                    }
                }).show();
                return true;
            }
            return false;
        },

        /**
         * Writes the updated page php code to the server
         */
        _saveAttributes = function() {
            var itemsToBeSaved = [],
                failureOccured = [];
            var handlers = function(type) {
                return {
                    success: function(transactionID, response) {
                        itemsToBeSaved.splice(itemsToBeSaved.indexOf(type), 1);
                        if (itemsToBeSaved.length === 0) {
                            Y.one("#saveWidgetAttrsBtn").setAttribute("disabled", false);
                            if(!_showWarningOnSave(failureOccured))
                                location.reload();
                        }
                    },
                    failure: function(transactionID, response) {
                        itemsToBeSaved.splice(itemsToBeSaved.indexOf(type), 1);
                        failureOccured.push(type);
                        if (itemsToBeSaved.length === 0) {
                            Y.one("#saveWidgetAttrsBtn").setAttribute("disabled", false);
                            _showWarningOnSave(failureOccured);
                        }
                    }
                }
            };
            var types = Object.keys(_pageContents),
                i;
            for (i = 0; i < types.length; i++) {
                if (!_pageContents[types[i]].changed) {
                    continue;
                }
                itemsToBeSaved.push(types[i]);
                Y.io("/dav" + _pageContents[types[i]].path, {
                    method: 'PUT',
                    data: _pageContents[types[i]].editor.getValue(),
                    on: handlers(types[i])
                });
            }
            window.onbeforeunload = function () {
                // blank function do nothing
            }
        },

        /**
         * Creates the widget attribute dialog
         *
         * @param  {string} widgetContainerID Widget Contianer Identifier
         * @param  {string} widgetPath Widget path
         * @param  {bool} specifies if the widget is hidden
         * @return {object} Widget Attribute Dialog
         */
        _createAttributeDialog = function(widgetContainerID, widgetPath, isHidden, isReference) {
            var clickedWidgetInstanceID = widgetContainerID.replace("rn_", "");
            var widgetInfo = RightNow.Widgets.getWidgetInformation(clickedWidgetInstanceID);
            var attrsData = _getCurrentWidgetAttributes(widgetContainerID);
            var flag = 0; // This var is initialized to check whether CCL widget has polling enabled
            var saveHandler = function() {
                return function() {
                    _updateWidgetAttributes(widgetContainerID, widgetPath, _attributeDialog);
                };
            }();
            if (clickedWidgetInstanceID.indexOf('ConditionalChatLink') !== -1) {
                if (attrsData.enable_polling) {
                    flag = 1; // Polling is enabled in CCL widget
                }
            }
            if (clickedWidgetInstanceID.indexOf('SocialUserAvatar') !== -1) {
                attrsData.label_gravatar_hint = _escape(attrsData.label_gravatar_hint);
                attrsData.label_facebook_success_message = _escape(attrsData.label_facebook_success_message);
            }
            if (clickedWidgetInstanceID.indexOf('ModerationGrid') !== -1) {
                attrsData.exclude_from_sorting = attrsData.exclude_from_sorting.join();
            }
            if (attrsData.length !== 0 && flag === 0) {
                var buttons = [{
                        text: RightNow.Interface.getMessage("OK_LBL"),
                        handler: saveHandler,
                        isDefault: true
                    }, {
                        text: RightNow.Interface.getMessage("CANCEL_LBL"),
                        handler:  {fn: function(){
                            _exitAttributesDialog(widgetContainerID);
                        }, scope: this},
                        isDefault: false
                    }],
                    attributeForm = Y.Node.create(_attributeData.render({
                        widgetPath: widgetPath,
                        widgetID: widgetContainerID,
                        widgetAttrs: attrsData,
                        attrInfo: widgetsInfo[widgetPath].attributes,
                        isHidden: isHidden,
                        yui: Y
                    }));
                    if(isHidden || isReference) {
                        buttons.shift();
                        buttons[0].text = RightNow.Interface.getMessage("OK_LBL");
                    }
                    var _attributeDialog = RightNow.UI.Dialog.actionDialog(clickedWidgetInstanceID.substring(0, clickedWidgetInstanceID.indexOf('_')), attributeForm, {
                        "buttons": buttons,
                        "dialogDescription": "",
                        "width": '70%'
                    });
                _attributeDialog.get('contentBox').addClass('rn_WidgetInspectorDialog');
                RightNow.UI.Dialog.addDialogEnterKeyListener(_attributeDialog, _checkWidgetErrors);
            } else if (attrsData.length !== 0 && flag === 1) {
                _attributeDialog = RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage("POLLING_TH_ATTRIBUTES_DISPLAYEDCHANGED_LBL"));
            } else {
                _attributeDialog = RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage("THIS_WIDGET_HAS_NO_ATTRIBUTES_MSG"));
            }
            return _attributeDialog;
        },

        /**
         * Gets the current attribute values of a widget.
         *
         * @param  {string} widgetContainerID Widget Contianer Identifier
         * @return {object} Widget attributes
         */
        _getCurrentWidgetAttributes = function(widgetContainerID) {
            var clickedWidgetInstanceID = widgetContainerID.replace("rn_", "");
            var widgetInfo = RightNow.Widgets.getWidgetInformation(clickedWidgetInstanceID);
            var attrsData = widgetInfo && widgetInfo.instance && widgetInfo.instance.data && widgetInfo.instance.data.attrs ? widgetInfo.instance.data.attrs : RightNow.JSON.parse(unescape(Y.one("#" + widgetContainerID).getAttribute("data-attrs")));
            return attrsData;
        },

        /**
         * Compares the attribute values in dialog with the previous values and returns the modified ones.
         *
         * @param  {string} widgetID WidgetIdentifier
         * @param  {string} subWidgetID WidgetIdentifier
         * @return {object} Attributes
         */
        _getModifiedAttributes = function(widgetID, subid) {
            _clearErrorMessage(widgetID);
            var widgetDialog = Y.one("#widgetAttrDialog_" + widgetID),
                currenAttrs = _getCurrentWidgetAttributes(widgetID),
                defaultAttrs = widgetsInfo[widgetDialog.getAttribute("data-dialog-widget-path")].attributes,
                attrs = {},
                ignoredAttrs = {},
                errorResults = [];
                anyAttrModified = false;
            widgetDialog.all("[data-attr]").each(function(o) {
                var attrName = o.getAttribute("name");
                var defaultAttrMeta = defaultAttrs[attrName];
                var currValue = typeof currenAttrs[attrName] !== "undefined" ? currenAttrs[attrName] : (defaultAttrMeta.default || null);
                if(!defaultAttrMeta && attrName.substring(0, 4) === "sub:") {
                    var widgetSubId = attrName.substring(attrName.indexOf(":") + 1, attrName.lastIndexOf(":"));
                    var subWidgetNode = document.querySelectorAll('[data-subid="' + widgetSubId + '"]');
                    if(subWidgetNode[0]){
                        var subWidgetPath = subWidgetNode[0].getAttribute("data-widget-path");
                        defaultAttrMeta = widgetsInfo[subWidgetPath].attributes[attrName.substring(attrName.lastIndexOf(":") + 1)];
                    }
                }
                var result = _getAttributeValue(defaultAttrMeta, o);
                if (result.errors.length > 0) {
                    result.oldValue = currValue;
                    errorResults.push(result);
                    return;
                }
                attrName = subid ? "sub:" + subid + ":" + attrName : attrName;
                if(currValue != result.value && result.value === defaultAttrMeta.default) {
                    anyAttrModified = true;
                }
                if (currValue != result.value && result.value !== defaultAttrMeta.default) {
                    attrs[attrName] = result.value;
                } else {
                    ignoredAttrs[attrName] = {
                        value: result.value,
                        isDefault: result.value == defaultAttrMeta.default,
                        isModified: currValue != result.value
                    };
                }
            });
            if (errorResults.length > 0) {
                return {
                    errors: errorResults
                };
            }
            return {
                modifiedAttrs: attrs,
                ignoredAttrs: ignoredAttrs,
                anyAttrModified: anyAttrModified
            };
        },

        /**
         * Validates whether a value is valid integer
         * @param  {object} result Error result
         */
        _validateInt = function(result) {
            _validateRequired(result);
            if (result.NaN) {
                var message = RightNow.Interface.getMessage("ATTRIBUTE_HAVENT_SPECIFIED_VALID_IT_LBL");
                message = RightNow.Text.sprintf(message, result.attrInfo.name);
                result.errors.push({
                    type: "invalid",
                    message: message
                });
            }
            if (result.errors.length > 0 || (result.value === null || result.value === "")) return;
            if (result.attrInfo.min && result.value < result.attrInfo.min) {
                var message = RightNow.Interface.getMessage("VAL_PCT_S_ATTRIB_MINIMUM_VAL_ACCD_MSG");
                message = RightNow.Text.sprintf(message, result.attrInfo.name, result.attrInfo.min, result.value);
                result.errors.push({
                    type: "min",
                    message: message
                });
            } else if (result.attrInfo.max && result.value > result.attrInfo.max) {
                var message = RightNow.Interface.getMessage("VAL_PCT_S_ATTRIB_MAX_VAL_ACCD_PCT_S_MSG");
                message = RightNow.Text.sprintf(message, result.attrInfo.name, result.attrInfo.max, result.value);
                result.errors.push({
                    type: "max",
                    message: message
                });
            }
        },

        /**
         * Validates whether a value is required or not
         * @param  {object} result Error result
         */
        _validateRequired = function(result) {
            if (result.attrInfo.required && (result.value === null || result.value === "") || (Array.isArray(result.value) && result.value.length === 0)) {
                var message = RightNow.Interface.getMessage("PCT_S_ATTRIB_REQD_HAVENT_VALUE_MSG");
                message = RightNow.Text.sprintf(message, result.attrInfo.name);
                result.errors.push({
                    type: "required",
                    message: message
                });
            }
        },

        /**
         * Adds an error message to the page and adds the correct CSS classes
         * @param message string The error message to display
         * @param focusElement HTMLElement The HTML element to focus on when the error message link is clicked
         * @param widgetID string Widget ID
         */
        _addErrorMessage = function(message, focusElement, widgetID) {
            var _errorDisplay = Y.one("#" + widgetID + "_widgetInspectorErrors");
            Y.one("#" + focusElement).addClass("rn_ErrorField");
            Y.one("[for='" + focusElement + "']").addClass("rn_ErrorLabel");
            if (_errorDisplay) {
                _errorDisplay.addClass('rn_MessageBox rn_ErrorMessage');
                //add link to message so that it can receive focus for accessibility reasons
                _errorDisplay.appendChild('<div><a href="javascript:void(0);" onclick="document.getElementById(\'' + focusElement + '\').focus(); return false;">' + message + '</a></div>');
                _errorDisplay.get("firstChild").one("a").focus();
                _errorDisplay.one("h2") ? _errorDisplay.one("h2").setHTML(RightNow.Interface.getMessage("ERRORS_LBL")) : _errorDisplay.prepend("<h2>" + RightNow.Interface.getMessage("ERROR_LBL") + "</h2>");
                _errorDisplay.one("h2").setAttribute('role', 'alert');
            }
        },

        /**
         * Clears out the error message divs and their classes.
         * @param widgetID string Widget ID
         */
        _clearErrorMessage = function(widgetID) {
            var _errorDisplay = Y.one("#" + widgetID + "_widgetInspectorErrors");
            if (_attributeDialogs[widgetID]) {
                Y.one("#" + _attributeDialogs[widgetID].id).all("[class~=rn_ErrorField]").each(function(elem) {
                    elem.removeClass("rn_ErrorField");
                    Y.one("[for=" + elem.get("id") + "]").removeClass("rn_ErrorLabel");
                });
            }
            if (_errorDisplay) _errorDisplay.setHTML('').removeClass('rn_MessageBox rn_ErrorMessage');
        },

        /**
         * Escapes the html. Unescapes if "unescape" argument is true
         * @param  string value Value to be escaped or unescaped
         * @param  boolean unescape if true unescapes the html else escapes
         * @return string Escaped or unescaped value
         */
        _escape = function(value, unescape){
            var keys = Object.keys(_htmlReplacer), i;
            for(i = 0; i < keys.length; i++){
                var key = keys[i];
                var val = _htmlReplacer[keys[i]];
                value = value.replace(new RegExp(!unescape ? key : val, "g"), !unescape ? val : key);
            }
            return value;
        },

        /**
         * Escapes the html characters of attribute values
         * @param  string widgetLine Widget line
         * @param  string widgetID Widget identifier
         * @return string Escaped Widget line
         */
        _escapeAttributeValues = function(widgetLine, widgetID){
            var r = new RegExp(/['"\s]\s*([a-z]|:|_|-)+=\s*['"]/ig);
            var start = 0, escaped = "", trimmedAttrName;
            while(a = r.exec(widgetLine)){
                 attrValue = widgetLine.substring(start, a.index);
                 attrName = widgetLine.substring(a.index, r.lastIndex);
                 if(start > 0){
                    attrValue = _escape(attrValue);
                 }
                 escaped += attrValue;
                 trimmedAttrName = attrName.replace(/^[\s='"]+|[\s='"]+$/gm, '');
                 if(!_isAttributeName(trimmedAttrName, widgetID)){
                    attrName.replace(trimmedAttrName, _escape(trimmedAttrName));
                 }
                 escaped += attrName;
                 start = r.lastIndex;
            }
            var m = widgetLine.match(/['"]\s*\/?>$/);
            escaped += _escape(widgetLine.substring(start, m ? m.index : widgetLine.length));
            escaped += m ? widgetLine.substring(m.index) : "";
            return escaped;
        },

        /**
         * True if passed attribute name is one of the valid attributes of a widget
         * @param  string  attrName Attribute name
         * @param  string  widgetID Widget identifier
         * @return boolean True or False
         */
        _isAttributeName = function(attrName, widgetID){
            var attrNames = Object.keys(_getCurrentWidgetAttributes(widgetID));
            attrNames.push("path");
            return attrNames.indexOf(attrName) !== -1;
        },

        /**
         * Converts the modified attribute values to their respective types
         *
         * @param  {object} attrInfo Attribute meta information
         * @param  {object} attributeElem Modified attribute YUI element
         * @return {int|string|boolean} Converted attribute value
         */
        _getAttributeValue = function(attrInfo, attributeElem) {
            var result = {
                attrInfo: attrInfo,
                errors: [],
                elem: attributeElem.getAttribute("id")
            };
            if (/INT/i.test(attrInfo.type)) {
                var val = attributeElem.get("value").trim(),
                    intval = parseInt(val, 10);
                result.NaN = (val !== "" || attrInfo.default !== null) ? isNaN(intval) : false;
                result.value = isNaN(intval) ? null : intval;
                _validateInt(result);
                return result;
            } else if (/BOOL/i.test(attrInfo.type)) {
                result.value = attributeElem.get("checked");
                _validateRequired(result);
                return result;
            } else if (/OPTION/i.test(attrInfo.type)) {
                var value = [];
                attributeElem.get("options").each(function() {
                    if (this.get("selected")) {
                        value.push(this.get("value"));
                    }
                });
                result.value = value.length > 0 ? value.join(",") : value;
                _validateRequired(result);
                return result;
            } else if (/STRING/i.test(attrInfo.type)) {
                result.value = attributeElem.get("value");
                _validateRequired(result);
                result.value = result.value ? result.value.replace(/"/g, "'") : result.value;
                return result;
            }
            result.value = attributeElem.get("value");
            _validateRequired(result);
            return result;
        };

    return {
        inspectWidgetSource: function(e) {
            _inspectWidgetSource(e);
        },
        inspectAllWidgetSource: function(disable) {
            _inspectAllWidgetSource(disable);
        },
        provideYUI: function(yui) {
            Y = yui;
            if (document.cookie.indexOf('inspection') > -1 ) {
                Y.on('domready',function(){RightNow.UI.WidgetInspector.inspectAllWidgetSource(false);});
            }
        },
        init: function() {
            _init();
        },
        filterWidgets: function() {
            _filterWidgets();
        },
        getAttributeValue: function(type, attributeElem) {
            return _getAttributeValue(type, attributeElem);
        },
        replaceWidgetLineWithAtrributes: function(widgetLine, attrs) {
            return _replaceWidgetLineWithAtrributes(widgetLine, attrs);
        },
        getModifiedAttributes: function(widgetID, subid) {
            return _getModifiedAttributes(widgetID, subid);
        },
        findAndModifyTheWidgetLine: function(widgetID, path) {
            _findAndModifyTheWidgetLine(widgetID, path);
        },
        saveAttributes: function() {
            _saveAttributes();
        },
        checkWidgetErrors: function(){
            _checkWidgetErrors();
        },
        getEditor: function(type) {
            return _pageContents[type].editor;
        },
        parseWidgetLine: function(widgetLine) {
            return _parseWidgetLine(widgetLine);
        },
        deserializeParsedWidgetXML: function(doc) {
            return _deserializeParsedWidgetXML(doc);
        },
        escapeAttributeValues: function(widgetLine, widgetID) {
            return _escapeAttributeValues(widgetLine, widgetID);
        }
    };
}());
YUI().use('node-base', 'node-core', 'node-style', 'panel', 'escape', 'io-base', 'datatype', 'datatype-xml', RightNow.UI.WidgetInspector.provideYUI);
