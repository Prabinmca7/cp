YUI.add('step-two', function(Y) {
    'use strict';

    var Widget = {};

    /**
     * Step 2.
     * Widget to extend from (if applicable).
     * Widget name.
     * Widget folder.
     */
    Y.StepTwo = Y.Step({
        name: 'two',
        events: {
            'click button':             'nextStep',
            'valueChange #name':        'nameChange',
            'valueChange #folder':      'folderChange',
            'valueChange #toExtend':    'extendChange'
        },
        /**
         * @constructor
         * @param {string} type 'new' or 'extend'
         */
        init: function(type) {
            this.content.get('children').addClass('hide');
            this.content.one('.' + type).removeClass('hide');
            this.nameInput = this.content.one('#name');
            this.folderInput = this.content.one('#folder');
            this.namePlaceholder = this.content.one('#widgetPlaceholder');

            if (type === 'extend') {
                this.initAutocomplete();
                // Un-hide the name/folder/continue fields
                // if a valid value has been previously entered and
                // user chose 'new widget' but then 'extends'
                var extendee = this.content.one('#toExtend').get('value');
                if (extendee) {
                    this.extendChange(null, extendee);
                }
            }
            else {
                Widget.extendsFrom = null;
                var focus = this.content.one('.new').one('input');
                // Wait a bit: the step isn't shown until after this#init is called
                setTimeout(function() { focus.focus(); }, 100);

                // Values may have already been set if user
                // restarts step one w/ the 'extends' scenario
                // but then goes back to 'new'
                this.toggleButton();
            }
        },
        /**
         * Sets up autcomplete once and only once.
         */
        initAutocomplete: function() {
            if (this.init.acInit) return;

            var input = this.content.one('#toExtend');
            input.plug(Y.Plugin.AutoComplete, {
                // Remove the leading 'standard', 'custom' part of the widget path
                source: (function(context) {
                    context.mapping = {};
                    var shortened = [];
                    Y.Object.each(window.allWidgets, function(val, shorter) {
                        // Length of "standard/", length of "custom/"
                        shorter = val.relativePath.substr((val.type === 'standard') ? 9 : 7);
                        if (window.allWidgets['standard/' + shorter] && window.allWidgets['custom/' + shorter])
                            shorter += ' (' + val.type + ')';
                        context.mapping[shorter] = val.relativePath;
                        shortened.push(shorter);
                    });
                    return shortened;
                })(this),
                maxResults: 5,
                activateFirstItem: true,
                resultFilters: function(query, results){
                    query = query.toLowerCase();
                    var listOfMatches = Y.Array.filter(results, function(result) {
                        return result.text.toLowerCase().indexOf(query) !== -1;
                    });
                    // Sort first by 'best match' (e.g. if the user types 'productc', then
                    // input/*ProductCategory*Input is a better match than input/Basic*ProductCategory*Input)
                    // then sort alphabetically.
                    listOfMatches.sort(function (a, b) {
                        var aWidgetName = a.text.toLowerCase().split('/')[1],
                            bWidgetName = b.text.toLowerCase().split('/')[1];

                        if (aWidgetName.indexOf(query) === 0) return -1;
                        if (bWidgetName.indexOf(query) === 0) return 1;
                        if (aWidgetName < bWidgetName) return -1;
                        if (aWidgetName > bWidgetName) return 1;
                        return 0;
                    });

                    return listOfMatches;
                },
                resultHighlighter: 'phraseMatch'
            });

            input.ac.on('select', function(e) {
                e.halt();
                this.selected = e.result.text;
                input.set('value', this.mapping[this.selected]).focus().ac.hide();
                this.extendChange(null, input.get('value'));
            }, this);

            input.ac.on('query', function(e) {
                if (e.query === this.searchedOn) {
                    this.searchedOn = null;
                    input.ac.hide();
                    e.halt();
                }
            }, this);

            this.init.acInit = true;
        },
        /**
        * Shows a tooltip element (that's previously been hidden in the view)
        * and hides it after a 20 second delay.
        * @param {string} widget relative widget path
        */
        showTooltip: function(widget) {
            var tooltip = this.content.one('.tooltip'),
                widgetName = widget.substr(widget.lastIndexOf('/') + 1);

            tooltip = tooltip.set('innerHTML', '<span class="arrow"></span>' +
                tooltip.getAttribute('data-title')
                    .replace('%s', widgetName + '.css')
            ).replaceClass('hide', 'active');
            setTimeout(function() {
                tooltip.replaceClass('active', 'hide');
            }, 20000);
        },
        /**
         * Validates widget name.
         * @param {string} name widget name
         * @return {boolean} whether the name is valid
         */
        validName: function(name) {
            return (/^[a-zA-Z_][a-zA-Z0-9_]*$/g).test(name);
        },
        /**
         * Validates widget folder.
         * @param {string} name widget folder name
         * @return {boolean} whether the folder name is valid
         */
        validFolder: function(name) {
            return Y.Array.every(name.split('/'), function(v) {
                return !v || this.validName(v);
            }, this);
        },
        /**
         * Checks the value of the widget name.
         * @param {object} input field Node
         * @return {string|boolean} value of the field, after
         *   modifying and validating it; false if invalid
         */
        checkValue: function(input) {
            var oldValue = input.get('value'),
                newValue = Y.Lang.trim(oldValue)
                    .replace(/\/+/g, '/')
                    .replace(/\\+/g, ''),
                type = input.get('id');
            if (oldValue !== newValue) {
                input.set('value', newValue);
            }
            type = type.charAt(0).toUpperCase() + type.substr(1);
            if (!newValue || !this['valid' + type](newValue)) {
                input.addClass('highlight');
                return false;
            }
            input.removeClass('highlight');
            return newValue;
        },
        /**
         * When the value of the 'widget to extend' field changes.
         * @param {object|null} e DOM event facade or null if manually called
         * @param {string|null} widget name if manually called
         */
        extendChange: function(e, widget) {
            widget || (widget = e.target.get('value'));

            if (!(widget in window.allWidgets)) {
                this.content.one('.new').addClass('hide');
                return;
            }

            if (widget !== Widget.extendsFrom) {
                // Tooltip to remind modifying presentation CSS
                this.showTooltip(widget);
            }

            this.content.one('.new').removeClass('hide');
            Widget.extendsFrom = widget;
            this.toggleButton();
        },
        /**
         * When the value of the 'widget name' field changes.
         * @param {object} e DOM event facade
         */
        nameChange: function(e) {
            var value = this.checkValue(e.target);

            this.placeholder || (this.placeholder = Y.one('#widgetPlaceholder'));
            this.placeholder.set('innerHTML', '/ ' + (value || ''));

            Widget.name = value;
            this.toggleButton();
        },
        /**
         * When the value of the 'widget folder' field changes.
         * @param {object} e DOM event facade
         */
        folderChange: function(e) {
            var value = this.checkValue(e.target);
            Widget.folder = value;
            this.toggleButton();
        },
        /**
         * Makes sure the custom widget specified doesn't already exist.
         * @param {string} folder widget folder
         * @return {boolean} whether the widget name + folder is valid
         */
        validateFolder: function(folder) {
            return !(('custom/' + folder + '/' + Widget.name) in window.allWidgets);
        },
        /**
         * Shows or hides the next step button depending on the
         * validity of user input.
         */
        toggleButton: function() {
            this.button[(Widget.folder && this.validateFolder(Widget.folder) && Widget.name) ? 'removeClass' : 'addClass']('hide');
        },
        /**
         * Goes to the next step when the continue button is clicked.
         * @param {object} e DOM event facade
         */
        nextStep: function(e) {
            e.target.addClass('hide');
            this.fire({ widget: Widget });
        }
    });
}, null, {
    requires: ['step', 'autocomplete', 'autocomplete-highlighters']
});
