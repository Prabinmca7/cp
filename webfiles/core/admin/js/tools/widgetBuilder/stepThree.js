/*global messages, yuiPath*/

YUI.add('step-three', function(Y) {
    'use strict';

    var Widget = {};

    /**
     * Step 3.
     * Gets the various components of the widget.
     */
    Y.StepThree = Y.Step({
        name: 'three',
        events: {
            'click button':                     'nextStep',
            'click input[type="radio"]':        'toggleComponent',
            'click input[name="ajax"]':         'toggleAjax',
            'click input[name="view"]':         'toggleViewExtension',
            'click input[data-subchoice]':      'toggleSubChoice',
            'click input[name="js"]':           'toggleJS',
            'click #addYUIModule':              'addYUIModule',
            'click a.removeModule':             'removeYUIModule'
        },
        focusElement: '#withoutPhp',
        yuiModules: null,
        // Add our unofficial treeview
        additionalModules: ['gallery-treeview'],
        /**
         * @constructor
         */
        init: function(widget, validateStepThreeProgress) {
            Widget = widget;
            this.validateStepThreeProgress = validateStepThreeProgress;

            var toggle = ['new', 'extending'];

            if (!Widget.extendsFrom) {
                toggle.reverse();
                this.button.addClass('hide');
                // Reset any previously-set components if the widget WAS previously
                // extended but the user went back to new
                this.content.one('[data-when="extending-view"]').addClass('hide');
                this.content.one('fieldset[data-for="js"] input').removeAttribute('disabled');
                delete Widget.overrideView;
            }
            this.content.all('[data-when="' + toggle[0] + '"]').addClass('hide');
            this.content.all('[data-when="' + toggle[1] + '"]').removeClass('hide');

            if (!this.init.ttInit) {
                Y.TipIt('.' + this.name + ' a.tooltipLink');
                this.init.ttInit = true;
            }
            this.toggleNextStep();
        },
        /**
         * Called when the 'add yui module' link is clicked.
         * Inserts a new input field.
         * @param {object} e DOM Event facade
         */
        addYUIModule: function(e) {
            if (this.yuiModules) {
                this.insertAutocompletedInput();
            }
            else {
                // Drop a loading indicator in and request the info.
                // The first field gets added when the data comes back
                // from the server.
                var wait = Y.Node.create('<span class="wait"></span>');
                e.target.insert(wait, 'after');

                Y.io(yuiPath + 'data.json', {
                    on: {
                        success: function(id, resp) {
                            wait.remove();
                            this.gotYUIModules(Y.JSON.parse(resp.responseText));
                        }
                    },
                    context: this
                });
            }

            this.toggleNextStep();
        },
        /**
         * Callback when the YUI's data JSON is retrieved and parsed.
         * @param {object} info YUI data
         */
        gotYUIModules: function(info) {
            this.yuiModules = Y.Object.keys(info.modules).concat(this.additionalModules);
            this.insertAutocompletedInput();
        },
        /**
         * Creates and inserts an input field and plugs an autocomplete onto it.
         * Uses the yuiModules member for the data source, so, this assumes that
         * it's been populated.
         */
        insertAutocompletedInput: function() {
            var newInput = Y.Node.create(this.getTemplate('module').render()),
                newLabel = newInput.one('label'),
                selectedModules = this.getYUIModules(),
                remainingModules = Y.Array.filter(this.yuiModules, function(module) {
                    return Y.Array.indexOf(selectedModules, module) === -1;
                });

            this.content.one('#addYUIModule').insert(newInput, 'before');
            newInput = newInput.one('input').focus();
            newLabel.setAttribute('for', newInput.generateID());

            newInput.plug(Y.Plugin.AutoComplete, {
                source: remainingModules,
                maxResults: 4,
                activateFirstItem: true,
                resultHighlighter: 'phraseMatch',
                resultFilters: function(query, results) {
                    return Y.Array.filter(results, function(result) {
                        return result.text.toLowerCase().indexOf(query.toLowerCase()) !== -1;
                    });
                }
            });

            newInput.ac.on('select', function(e) {
                e.halt();
                this.selected = e.result.text;
                this.content.one('#addYUIModule').focus();
                // Disable the input so that module names can't be screwed up by (QA) users.
                newInput.set('value', e.result.text).set('disabled', true).ac.hide();
            }, this);

            newInput.ac.on('query', function(e) {
                if (e.query === this.selected) {
                    this.selected = null;
                    newInput.ac.hide();
                    e.halt();
                }
            }, this);
        },
        /**
         * Called when a module input's associated 'x' link is clicked. Removes the module.
         * @param {object} e DOM Event facade
         */
        removeYUIModule: function(e) {
            e.target.ancestor('.moduleContainer').remove();
            this.content.one('#addYUIModule').focus();
        },
        /**
         * Returns an array containing the values for all module inputs.
         * @return {array} module names
         */
        getYUIModules: function() {
            var modules = [];

            // Disabled inputs are the ones that are legit autocompleted module names.
            this.content.one('div[data-for="yui"]').all('input[disabled]').each(function(input, value) {
                value = input.get('value');
                if (value) {
                    modules.push(value);
                }
            });

            return modules;
        },
        /**
         * When a component's radio button is clicked.
         * @param {object} e DOM Event facade
         */
        toggleComponent: function(e) {
            var component = e.target.ancestor('fieldset').getAttribute('data-for'),
                include = e.target.get('value') === '1';
            Widget.components[component] = include;
            this.toggleNextStep();
        },
        /**
         * When a component with a depedent sub-component is clicked.
         * @param {object} e DOM Event facade
         */
        toggleSubChoice: function(e) {
            var target = e.target,
                enable = target.get('id').indexOf('has') === 0,
                disableFunc = (enable) ? 'removeAttribute' : 'setAttribute',
                components = target.ancestor('.row').next('.row.subchoice')
                                .all('input')[disableFunc]('disabled', 'disabled'),
                component = components.item(0).get('id').indexOf('has') === 0 ? components.item(0) : components.item(1);
            Widget.components[component.ancestor('fieldset').getAttribute('data-for')] = enable && component.get('checked');
            this.toggleNextStep();
        },
        /**
         * When the view toggle is clicked. Toggles the
         * display of the view extension choices
         * @param {object} e DOM Event facade
         */
        toggleViewExtension: function(e) {
            if (!Widget.extendsFrom) return;

            var target = e.target,
                enable = target.get('id').indexOf('has') === 0,
                className = enable ? 'removeClass' : 'addClass';

            this.content.one('[data-when="extending-view"]')[className]('hide');
            this.toggleSubChoice(e);
        },
        /**
         * When the ajax toggle is clicked. Forces the use
         * of JavaScript to 'yes' if AJAX is enabled
         * @param {object} e DOM Event facade
         */
        toggleAjax: function(e) {
            var target = e.target,
                enable = target.get('id').indexOf('has') === 0,
                hasJS = this.content.one('#hasJS');

            if(!enable) return;
            hasJS.set('checked', true);
            this.toggleJS({target: hasJS});
            this.toggleComponent({target: hasJS});
            this.toggleSubChoice({target: hasJS});
        },
        /**
         * When the JS toggle is clicked. Forces the use
         * of AJAX to 'no' if JS is disabled
         * @param {object} e DOM Event facade
         */
        toggleJS: function(e) {
            var target = e.target,
                enable = target.get('id').indexOf('has') === 0,
                withoutAjax = this.content.one('#withoutAjax'),
                YUIModule = this.content.one('#addYUIModule');

            if(enable && YUIModule.hasClass('disabled')){
                YUIModule.toggleClass('disabled');
            }
            if(!enable && !YUIModule.hasClass('disabled')){
                YUIModule.toggleClass('disabled');
            }

            if(enable) return;
            withoutAjax.set('checked', true);
            this.toggleComponent({target: withoutAjax});
        },
        /**
         * Enables/disables the next step button.
         */
        toggleNextStep: function() {
            this.button[(this.validateStepThreeProgress(Widget)) ? 'removeClass' : 'addClass']('hide');
        },
        /**
         * Goes to the next step when the continue button is clicked.
         * @param {object} e DOM Event facade
         */
        nextStep: function(e) {
            if (!this.validateStepThreeProgress(Widget)) return;

            if (Widget.components.js) {
                Widget.yuiModules = this.getYUIModules();
            }
            else {
                // Set back to null if user selected JS and YUI modules but later
                // deselected JS.
                Widget.yuiModules = null;
            }

            e.target.addClass('hide');
            this.fire({ widget: Widget });
        }
    });
}, null, {
    requires: ['step', 'io-base', 'node', 'tip-it']
});
