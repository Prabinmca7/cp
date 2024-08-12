/*global messages*/
YUI.add('step-five', function(Y) {
    'use strict';

    var Widget = {};

    /**
     * Step 5.
     * Provide additional details (optional).
     */
    Y.StepFive = Y.Step({
        name: 'five',
        events: {
            'click h3 a':          'toggleContent',
            'click .remove':       'removeUrlParam',
            'click #addUrlParam':  'addNewParam',
            'click input[name="jsModule"]': 'enforceJSModules',
            'blur .heading input': 'populateExample'
        },
        /**
         * @constructor
         */
        init: function(widget) {
            Widget = widget;

            this.on('step:five:reenable', this.reEnable);

            if (!this.init.finishedSubscribed) {
                // Only subscribe once
                Y.one('#finishIt').on('click', this.nextStep, this);
                this.init.finishedSubscribed = true;
            }

            // Hide the compatibility section (which currently only contains JS module)
            // if the widget doesn't have any JS.
            this.content.one('.compatibility')[Widget.components.js ? 'removeClass' : 'addClass']('hide');
        },

        /**
         * Called when the toggle heading is clicked.
         * @param {object} e DOM Event facade
         */
        toggleContent: function(e) {
            var target = e.currentTarget,
                toggleDiv = target.ancestor().next('div'),
                toggleIcon = target.one('.stepToggle');

            toggleDiv.toggleClass('hide');
            // Swap downward & rightward-facing arrows
            toggleIcon.setHTML((toggleIcon.getHTML() === '\u25BC') ? '\u25BA' : '\u25BC');

            if (!toggleDiv.hasClass('hide')) {
                this.content.one('textarea').focus();

                if (Widget.extendsFrom) {
                    this.content.one('.urlParams').addClass('wait');
                    Y.one('.' + this.name + ' .final').addClass('hide');
                    this.content.all('.urlParam.inherited').remove();
                    Y.io(Y.StepFive.endpoint + '/urlParameters/' + encodeURIComponent(Widget.extendsFrom), {
                        method: 'GET',
                        on: { success: this.receivedParentParams },
                        context: this
                    });

                    if (Widget.components.js) {
                        Y.io(Y.StepFive.endpoint + '/jsModule/' + encodeURIComponent(Widget.extendsFrom), {
                            method: 'GET',
                            on: { success: this.receivedParentJSModule },
                            context: this
                        });
                    }
                }
            }
        },

        /**
         * Called when a JS Module checkbox is clicked.
         * Ensures that at least one checkbox remains checked.
         * @param {object} e DOM Event facade
         */
        enforceJSModules: function(e) {
            if (e.target.getAttribute('data-for') === 'none') {
                // Can't have 'none' JS module and something else
                var disableOthers = (e.target.get('checked'))
                    ? true
                    : false;

                this.content.all('input[name="jsModule"]').set('checked', !disableOthers).set('disabled', disableOthers);
                e.target.set('checked', disableOthers).set('disabled', false);

                return;
            }

            var values = Y.Array.unique(this.content.all('input[name="jsModule"]').get('checked'));
            if (values.length === 1 && values[0] === false) {
                // Halt the event if all checkboxes were unchecked; halting it makes sure
                // the checked box remains checked
                e.halt();
            }
        },

        /**
         * Called when the create widget button is called.
         * @param {object} e DOM Event facade
         */
        nextStep: function(e) {
            e.halt();

            // Pull in the optional info
            if (this.gatherInfo()) {
                e.target.addClass('hide');
                this.fire({ widget: Widget });
            }
        },

        /**
         * Sets all of the info that the step is
         * responsible for on the Widget object.
         */
        gatherInfo: function() {
            Widget.info || (Widget.info = {});

            this.setDescription();
            this.setJSModules();
            return this.setParams();
        },

        /**
         * Sets the description of the widget to what's been entered.
         */
        setDescription: function() {
            var description = this.content.one('#description').get('value');
            if (description) {
                Widget.info.description = description;
            }
            else if (Widget.info.description) {
                // Unset any previously-set value if it's now cleared out
                Widget.info.description = null;
            }
        },

        /**
         * Sets the JS Module based on what's been checked.
         */
        setJSModules: function() {
            var modules = [];
            this.content.all('input[name="jsModule"]').each(function(input) {
                if (input.get('checked')) {
                    modules.push(input.getAttribute('data-for'));
                }
            });

            Widget.info.jsModule = modules;
        },

        /**
         * Sets the URL Params based on what's been entered.
         * @return {bool} True if validation passed
         */
        setParams: function() {
            var errors = 0,
                params = {};
            this.content.all('.urlParam').each(function(paramDiv, param, key) {
                param = {};
                paramDiv.all('[data-name]').some(function(input, name, value) {
                    name = input.getAttribute('data-name');
                    value = Y.Lang.trim(input.get('value'));
                    // @codingStandardsIgnoreStart
                    if (value && (name !== 'key' || !/[\s\/\\]/ .test(value))) {
                    // @codingStandardsIgnoreEnd
                        // Don't allow whitespace or slashes for param keys
                        input.removeClass('highlight');
                        if (name === 'key'){
                            var validationSection = Y.one('.validation');
                            if(validationSection){
                                validationSection.remove();
                            }
                        }
                        param[name] = value;
                    }
                    else {
                        input.addClass('highlight');
                        errors++;
                        if (errors === 1) {
                            // scroll to the first error
                            if(name === 'key' && !Y.one('.validation')) {
                                input.get('parentNode').append(this.getTemplate('validation').render());
                            }
                            Y.Step.scrollToNode(input);
                        }
                    }
                }, this);
                if (param.key) {
                    key = param.key;
                    delete param.key;
                    params[key] = param;
                }
            }, this);

            Widget.info.urlParams = params;

            return errors === 0;
        },

        /**
         * Inserts a new url param div.
         */
        addNewParam: function() {
            this.content.one('.urlParams').prepend(this.getTemplate('param', {
                name: '',
                readable: '',
                description: '',
                example: '',
                urlParamIndex: Y.StepFive.urlParams++
            })).one('input').focus();
        },

        /**
         * Callback for AJAX request to retrieve
         * extended widget's JS Module.
         * @param {number} id Transaction id
         * @param {object} resp ajax response data
         */
        receivedParentJSModule: function(id, resp) {
            var module = (resp.responseText)
                ? Y.JSON.parse(resp.responseText)
                : null;

            if (module) {
                if (module.error) {
                    this.content.insert(module.error, 0);
                }
                else {
                    this.updateParentJSModule(module);
                }
            }
        },

        /**
         * Checks / unchecks JS Module check boxes to
         * correspond to extended widget's JS Module.
         * @param {array} modules List of JS modules
         */
        updateParentJSModule: function(modules) {
            this.content.all('input[name="jsModule"]').set('checked', false);
            Y.Array.each(modules, function(value) {
                this.content.one('input[data-for="' + value + '"]').set('checked', true);
            }, this);
        },

        /**
         * Callback for AJAX request to retrieve
         * extended widget's URL params.
         * @param {number} id Transaction id
         * @param {object} resp ajax response data
         */
        receivedParentParams: function(id, resp) {
            var params = (resp.responseText)
                ? Y.JSON.parse(resp.responseText)
                : null;

            if (params) {
                if (params.error) {
                    this.content.insert(params.error, 0);
                }
                else {
                    this.addParentParams(params);
                }
            }

            this.content.one('.urlParams').removeClass('wait');
            Y.one('.' + this.name + ' .final').removeClass('hide');
        },

        /**
         * Inserts URL param divs for a group of params.
         * @param {object} params Extended widget's params
         */
        addParentParams: function(params) {
            var container = this.content.one('.urlParams'),
                template = this.getTemplate('param');

            Y.Object.each(params, function(val, key, node) {
                node = Y.Node.create(template.render({
                    name:           key,
                    readable:       val.name,
                    description:    val.description,
                    example:        val.example,
                    urlParamIndex: Y.StepFive.urlParams++
                }));
                node.addClass('inherited')
                    .one('.heading').insert('<div class="group"><div class="highlight inheritLabel">' + messages.inheritedUrlParam + '</div></div>', 'after');
                container.append(node);
            }, this);
        },

        /**
         * Called when the remove link is clicked on a URL param div.
         * Removes the param after animating it out.
         * @param {object} e DOM Event facade
         */
        removeUrlParam: function(e) {
            e.target.ancestor('.urlParam').transition({height: 0, duration: 0.5}, function() {
                this.remove();
            });
        },

        /**
         * Called when the url param key is blurred. Populates the example field if it's empty.
         * @param {object} e DOM Event facade
         */
        populateExample: function(e) {
            var exampleField = e.target.ancestor('.urlParam').one('input[data-name="example"]');
            if (e.target.get('value') && !exampleField.get('value')) {
                exampleField.set('value', e.target.get('value') + '/');
            }
        },

        /**
         * Shows the continue button.
         */
        reEnable: function() {
            Y.one('#finishIt').removeClass('hide');
        }
    }, {
        endpoint: '/ci/admin/tools/widgetBuilder/getWidgetInfo'
    });
    Y.StepFive.urlParams = 0;
}, null, {
    requires: ['step', 'io-base', 'json', 'node', 'transition']
});
