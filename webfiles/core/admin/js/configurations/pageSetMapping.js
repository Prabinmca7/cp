/*global toggle, messages*/
YUI().use('io', 'json', 'dom', 'node', 'anim', 'template-micro', 'FormToken', function(Y) {
    /**
     * Displaying flash messages.
     * @return {object} Flash object
     */
    var Flash = (function () {
        var element = Y.one('#flashMessage'),
            classes = {
                error: 'error',
                message: 'message'
            };

        function show () {
            element.removeClass('invisible');
            new Y.Anim({ node: element, to: { opacity: 1 }, duration: 0.5 }).run();
        }

        function hide () {
            new Y.Anim({ node: element, to: { opacity: 0 }, duration: 0.5 }).run();
            element.addClass('invisible');
        }

        function displayMessage (addClass, removeClass, message) {
            element.setHTML(message).addClass(addClass).removeClass(removeClass);
            show();
        }

        function displayServerMessage (addClass, removeClass, message) {
            if(Y.Lang.isArray(message.message)) {
                message.message = message.message.join('<br />');
            }
            element.setHTML(message.message + ((message.id) ? '<br />' + messages.testLink : '')).addClass(addClass).removeClass(removeClass);
            show();
        }

        return {
            // Accepts string error message as its first arg.
            showError: Y.bind(displayMessage, null, classes.error, classes.message),
            // Accepts object with message property as its first arg.
            showServerError: Y.bind(displayServerMessage, null, classes.error, classes.message),
            // Accepts object with message property as its first arg.
            showServerMessage: Y.bind(displayServerMessage, null, classes.message, classes.error),
            // Accepts string error message as its first arg.
            showMessage: Y.bind(displayMessage, null, classes.message, classes.error),
            hide: hide
        };
    })();

    /**
     * Provides a locking mechanism for ajax requests.
     * @return {object} Ajax object
     */
    var Ajax = this.Ajax || (function() {
        // Set a lock so that multiple requests can't be fired at once.
        // It's the caller's responsibility to check before updating any
        // UI elements.
        var requesting = false;

        return {
            waiting: function () {
                return requesting;
            },
            /**
             * Make a POST.
             * @param  {string}   destination Endpoint
             * @param  {Function} callback    Success handler
             * @param  {string}   postVars    POST form data
             */
            post: function (destination, callback, postVars) {
                if (requesting) return;

                requesting = true;
                Y.FormToken.makeAjaxWithToken("/ci/admin/configurations/" + destination, {
                    method: "POST",
                    on: {
                        success: function () {
                            requesting = false;
                            callback.apply(null, arguments);
                        }
                    },
                    data: postVars
                }, this);
            }
        };
    })();

    /**
     * Provides a loading icon to place on UI elements.
     * @return {object} LockIcon object
     */
    var LockIcon = (function () {
        var icon = Y.Node.create("<span class='wait' aria-hidden='true' role='presentation'></span>");

        return {
            /**
             * Shows a loading icon in place of the given node.
             * @param  {object} nodeToReplace Y.Node
             */
            show: function (nodeToReplace) {
                nodeToReplace.insert(icon.removeClass('hide'), 'after');
                nodeToReplace.addClass('hide');
            },

            /**
             * Hides the loading icon added with #show and
             * reshows the original node.
             * @param  {object} nodeToReplace Y.Node
             */
            hide: function (nodeToReplace) {
                icon.addClass('hide');
                nodeToReplace.removeClass('hide');
            }
        };
    })();

    /**
     * Represents a page set mapping row
     * in the table.
     * @param {object} el Y.Node row
     * @param {string} id Row / pageset id
     * @constructor
     */
    function MappingRow (el, id) {
        this.el = el;
        this.id = id;
    }
    /**
     * Mapping of element selector → click handler method.
     * @type {Object}
     */
    MappingRow.events = {
        'a.save': 'save',
        'a.delete': 'remove',
        'a.edit': 'enterEditMode',
        'a[data-enable="1"]': 'enable',
        'a[data-enable="0"]': 'disable'
    };

    /**
     * Y.Template.Micro templates.
     * Each item is a template function that accepts an object
     * literal of replacement variables to swap in.
     * @type {Object}
     */
    MappingRow.templates = {
        newRow: Y.Template.Micro.compile(Y.one('#newMappingRow').getHTML()),
        savedRow: Y.Template.Micro.compile(Y.one('#savedMappingRow').getHTML())
    };

    /**
     * Click handler for save link.
     * @param  {object} e click event
     */
    MappingRow.prototype.save = function (e) {
        var values = this.getValues(),
            operation = e.target.getAttribute("data-operation"),
            errors = this.validate(values.description, values.value);

        if (errors.length) {
            Flash.showError(errors.join("<br />"));
            LockIcon.hide(e.target);
            return;
        }

        Flash.hide();

        Ajax.post((operation === "add") ? "addPageSet/" : "savePageSet/",
            Y.bind(this.onSaveResponse, this, values),
            this.toURI(values, (operation === 'add') ? '' : this.id));
    };
    /**
     * Handler for save server response.
     * @param  {object} values   Saved values
     * @param  {number} id       AJAX transaction id
     * @param  {object} response XHR object
     */
    MappingRow.prototype.onSaveResponse = function (values, id, response) {
        response = Y.JSON.parse(response.responseText);

        if (!response.id) return Flash.showServerError(response);

        this.id = response.id;

        this.transformAfterSave(values);
        this.el.one('.edit').focus();
    };
    /**
     * Converts the newly-saved row.
     * @param  {object} values Saved values
     */
    MappingRow.prototype.transformAfterSave = function (values) {
        var savedRow = Y.Node.create(MappingRow.templates.savedRow(Y.merge(messages, { id: this.id }, values)));

        if (this.el.hasClass('disabled')) {
            savedRow.addClass('disabled')
                .one('[data-enable]').setAttribute('data-enable', '1').setHTML(messages.enable);
        }

        this.el.insert(savedRow, 'after').remove();
        this.el = savedRow;
    };
    /**
     * Whether the row is new and unsaved.
     * @return {Boolean} Whether the row is new
     */
    MappingRow.prototype.isNew = function () {
        return !!this.el.getAttribute("data-new-row");
    };
    /**
     * Converts the given values into a stringified version.
     * @param  {object} values Values
     * @param  {number} id     row id
     * @return {string}        stringified values, ready for URI
     */
    MappingRow.prototype.toURI = function (values, id) {
        var str = [];
        Y.Object.each(values, function (val, key) {
            str.push(id + '_' + key + '=' + encodeURIComponent(val));
        });

        return str.join('&');
    };
    /**
     * Gets the values for the row.
     * @return {object} Has item, description, value properties
     */
    MappingRow.prototype.getValues = function () {
        return {
            item: Y.Lang.trim(Y.one('[id="' + this.id + '_item"]').get("value")),
            description: Y.Lang.trim(Y.one('[id="' + this.id + '_description"]').get("value")),
            value: Y.Lang.trim(Y.one('[id="' + this.id + '_value"]').get("value"))
        };
    };
    /**
     * Does some value validation.
     * @param  {string} description Row's description
     * @param  {string} value       Row's value
     * @return {array}             Filled with error messages
     */
    MappingRow.prototype.validate = function (description, value) {
        var errors = [];

        if (description === '') {
            errors.push(messages.emptyDesc);
        }
        if (value === '') {
            errors.push(messages.emptyPageSet);
        }
        if (value.indexOf(' ') > -1) {
            errors.push(messages.invalidPageSet);
        }

        return errors;
    };
    /**
     * Removes the row from the table, animating it out.
     */
    MappingRow.prototype.removeFromDOM = function () {
        var anim = new Y.Anim({
            node: this.el,
            to: { opacity: 0 },
            duration: 0.5
        });
        anim.on("end", function() {
            this.el.remove();
            this.el = null;
        }, this);
        anim.run();
    };
    /**
     * Callback for server delete response.
     * @param  {number} id       AJAX transaction id
     * @param  {object} response XHR object
     */
    MappingRow.prototype.onRemoveResponse = function (id, response) {
        if(response.responseText){
            Flash.showServerMessage(Y.JSON.parse(response.responseText));
            this.removeFromDOM();
        }
    };
    /**
     * Click handler for remove button.
     * @param  {object} e Click event
     */
    MappingRow.prototype.remove = function (e) {
        if(Ajax.waiting()) return;

        if(this.isNew()){
            Flash.hide();
            this.removeFromDOM();
            return;
        }
        LockIcon.show(e.target);

        Ajax.post("deletePageSet/" + this.id, Y.bind(this.onRemoveResponse, this));
    };
    /**
     * Click handler for enable button.
     */
    MappingRow.prototype.enable = function () {
        this.toggleState(true);
    };
    /**
     * Click handler for disable button.
     */
    MappingRow.prototype.disable = function () {
        this.toggleState(false);
    };
    /**
     * Enables / disables the row.
     * @param  {boolean} toEnable Whether to enable (True)
     *                            or disable (False)
     */
    MappingRow.prototype.toggleState = function (toEnable) {
        if(Ajax.waiting()) return;

        LockIcon.show(this.el.one('[data-enable]'));

        Ajax.post("enablePageSet/" + this.id + "/" + ((toEnable) ? 1 : 0), Y.bind(this.onToggleStateResponse, this, toEnable));
    };
    /**
     * Callback for server enable / disable.
     * @param  {boolean} toEnable Whether to enable
     * @param  {number} id       AJAX transaction id
     * @param  {object} response XHR object
     */
    MappingRow.prototype.onToggleStateResponse = function (toEnable, id, response) {
        if(response.responseText) {
            var enableLink = this.el.one('[data-enable]');

            LockIcon.hide(enableLink);

            var rowFnc = (toEnable) ? "removeClass" : "addClass",
                iconFnc = (toEnable) ? "addClass" : "removeClass",
                responseData = Y.JSON.parse(response.responseText);

            if (responseData.id !== null) {
                this.el.all("i.fa fa-ban")[iconFnc]("hide");
                this.el[rowFnc]("disabled");
                Flash.showServerMessage(responseData);
                enableLink.set("innerHTML", (toEnable) ? messages.disable : messages.enable)
                    .setAttribute("data-enable", (toEnable) ? 0 : 1);
            }
            else {
                Flash.showServerError(responseData);
            }

            this.el.one('[data-enable]').focus();
        }
    };
    /**
     * Goes into edit mode: showing the value fields
     * and providing save / delete buttons.
     */
    MappingRow.prototype.enterEditMode = function () {
        var focusFirstInput, editCell;
        this.el.all("input, div").each(function(node) {
            if (node.get("className") === "editMode" && node.get("tagName").toLowerCase() === "div") return;

            toggle(node);
            if(!focusFirstInput && node.get("tagName").toLowerCase() === "input"){
                node.focus();
                focusFirstInput = true;
            }
        });
        editCell = this.el.get("children").slice(-1).item(0);
        toggle(editCell);
        if (editCell.previous()) {
            toggle(editCell.previous());
            toggle(editCell.previous().previous());
        }
    };
    /**
     * Locates and returns, creating if needed, a MappingRow
     * instance for the given row id.
     * @param  {string} rowID  Row's id
     * @param  {boolean=} custom Whether the row is custom or not
     * @return {object}        MappingRow instance
     */
    MappingRow.find = function (rowID, custom) {
        MappingRow.rows || (MappingRow.rows = {});

        var key = (custom ? 'custom' : 'standard') + '_' + rowID;
        if (!(key in MappingRow.rows)) {
            MappingRow.rows[key] = new MappingRow(Y.one('#' + key), rowID);
        }

        return MappingRow.rows[key];
    };

    /**
     * Represents the mapping table.
     * @type {Object}
     */
    var Table = {
        localStorageKey: 'adminShowMappings',
        /**
         * Adds a new row element to the table.
         */
        addNewRow: function () {
            this.addNewRow.id || (this.addNewRow.id = 0);
            this.addNewRow.id++;

            var newRow = MappingRow.templates.newRow(Y.merge(messages, { id: this.addNewRow.id }));
            // Insert the new row and focus on its first input element.
            Y.one("#mappingTable").one('tbody').append(newRow)
                .all('tr').slice(-1).item(0).one('input').focus();
        },
        /**
         * Attaches delegate listeners for all the events.
         */
        attachDOMEvents: function () {
            var table = Y.one('table');

            Y.Object.each(MappingRow.events, function (methodName, selector) {
                table.delegate('click', Table._delegateEvent, selector, null, methodName);
            });

            Y.one("#addPageSetButton").on("click", Table.addNewRow, Table);
            Y.one("#showEnabled").on("click", Table.showRows, null, 'enabled');
            Y.one("#showAll").on("click", Table.showRows, null, 'all');
        },
        /**
         * Shows different rows: all or enabled, saving which one
         * in local storage so it can be restored on page load.
         * @param  {object} e          Click event
         * @param  {string} whatToShow Either 'enabled' or 'all'
         */
        showRows: function (e, whatToShow){
            if (e && e.target.hasClass('selected')) return;

            Y.all('#showAll, #showEnabled').toggleClass('selected');
            Y.one('#mappingTable')[(whatToShow === 'all' ? 'removeClass' : 'addClass')]('hideDisabled');

            if (typeof localStorage !== "undefined" && localStorage) {
                localStorage.setItem(Table.localStorageKey, whatToShow);
            }
        },
        /**
         * Fetches a saved show-row-state out of local storage and
         * shows the desired rows.
         */
        restoreState: function () {
            if(typeof localStorage !== "undefined" && localStorage){
                var savedState = localStorage.getItem(Table.localStorageKey);
                if (savedState && savedState !== 'all') {
                    Table.showRows(null, savedState);
                }
            }
        },
        /**
         * Event handler for all row events. The mapping row
         * is located and the specified event name method is
         * called on it.
         * @param  {object} e         DOM event
         * @param  {string} eventName Method to call on the MappingRow
         */
        _delegateEvent: function (e, eventName) {
            var rowID = e.target.getAttribute('data-row'),
                isCustom = !!e.target.ancestor('tr').get('id').match(/custom/),
                row = MappingRow.find(rowID, isCustom);

            if (row) {
                row[eventName](e);
            }
        }
    };

    Table.attachDOMEvents();
    Table.restoreState();
});
