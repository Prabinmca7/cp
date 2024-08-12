/*global messages*/
YUI.add('step-four', function(Y) {
    'use strict';

    var Widget = {};

    /**
     * Step 4.
     * Widget's attributes.
     */
    Y.StepFour = Y.Step({
        name: 'four',
        events: {
            'click button.continue':         'nextStep',
            'click a.remove':                'removeAttribute',
            'change select':                 'dataTypeChange',
            'click .addOption':              'addOption',
            'click #addAttribute':           'addAttribute',
            'click .options .removeOption':  'removeOption',
            'click input[type="checkbox"][data-name="default"]': 'changeBooleanLabel',
            'focus textarea': 'enableContinue',
            'focus input': 'enableContinue',
            'blur textarea': 'enforceFieldRequirements',
            'blur input': 'enforceFieldRequirements'
        },
        /**
         * @constructor
         */
        init: function(widget, validatePreviousStep) {
            Widget = widget;
            this.validatePreviousStep = validatePreviousStep;
            this.content.all('.attribute.inherited').remove();

            if (Widget.extendsFrom) {
                this.content.addClass('bigwait').all('button').get('parentNode').addClass('hide');
                Y.io('/ci/admin/tools/widgetBuilder/getWidgetInfo/attributes/' + encodeURIComponent(Widget.extendsFrom), {
                    method: 'GET',
                    on: { success: this.addParentAttributes },
                    context: this
                });
            }
            else if (Widget.components.ajax && !this.content.one('.attribute input[data-name=name][value=default_ajax_endpoint]')) {
                var defaultAttr = Y.Node.create(this.getTemplate('attribute', {
                    name:           'default_ajax_endpoint',
                    description:    messages.ajaxDescription,
                    defaultValue:   '/ci/ajax/widget',
                    attributeIndex: Y.StepFour.attributes++
                }));
                defaultAttr.one('[data-name="type"]').set('value', 'ajax');
                this.content.one('.attributes').append(defaultAttr);
            }
            this.button.removeClass('hide');
        },
        /**
         * Ajax callback for retrieving inherited attributes.
         * @param {string} id IO transaction id
         * @param {object} resp Response object
         */
        addParentAttributes: function(id, resp) {
            var attrs = (resp.responseText)
                ? Y.JSON.parse(resp.responseText)
                : null;

            if (attrs) {
                if (attrs.error) {
                    this.content.insert(attrs.error, 0);
                }
                else {
                    this.content.one('.attributes').append(this.buildInheritedAttributes(attrs));
                }
            }
            this.content.removeClass('bigwait').all('button').get('parentNode').removeClass('hide');
        },
        /**
         * Adds a new attribute element to the step.
         * @param {object} e DOM Event facade
         */
        addAttribute: function(e) {
            e.halt();

            var node = this.getTemplate('attribute', {
                name: '',
                description: '',
                defaultValue: '',
                attributeIndex: Y.StepFour.attributes++
            });
            if (Y.UA.ie < 9) {
                // Change events don't properly bubble in old IE
                node = Y.Node.create(node);
                node.one('select').on('change', this.dataTypeChange, this);
            }
            this.content.one('.attributes').append(
                node
            ).get('lastChild').one('input').focus();
            this.button.removeClass('hide');
        },
        /**
         * Builds attribute elements for inherited attributes.
         * @param {object} attrs Attributes
         */
        buildInheritedAttributes: function(attrs) {
            var fragment = Y.one(document.createDocumentFragment()),
                template = this.getTemplate('attribute');

            Y.Object.each(attrs, function(attr, attrName, node, dataType) {
                dataType = attr.type.toLowerCase();
                dataType = (dataType.indexOf('bool') === 0) ? 'boolean' : dataType;
                node = Y.Node.create(
                    template.render({
                        name:           attrName,
                        description:    attr.description,
                        // @codingStandardsIgnoreStart
                        defaultValue:   attr['default'] || (attr['default'] === 0 ? 0 : ''),
                        // @codingStandardsIgnoreEnd
                        attributeIndex: Y.StepFour.attributes++
                    })
                );

                this.transformAttributeNode(node, attr, dataType);

                node.addClass('inherited')
                    .setAttribute('data-orig-name', attrName)
                    .one('.heading').insert('<div class="group"><div class="highlight inheritLabel">' + messages.inheritedAttribute + '</div></div>', 'after');

                if (Y.UA.ie < 9) {
                    // Change events don't properly bubble in old IE
                    node.one('select').on('change', this.dataTypeChange, this);
                }

                fragment.append(node);
            }, this);

            return fragment;
        },

        /**
         * Modifies the node's contents according to the attribute value
         * and data type.
         * @param  {Object} node     Y.Node for the attribute
         * @param  {Object} attribute    Attribute
         * @param  {String} dataType Data type
         */
        transformAttributeNode: function(node, attribute, dataType) {
            node.one('option[value="' + dataType + '"]').setAttribute('selected', 'selected');

            if (attribute.required) {
                node.one('input[data-name="required"]').setAttribute('checked', 'checked');
            }

            this.switchDataType(node, dataType);
            // @codingStandardsIgnoreStart
            if (dataType === 'boolean' && attribute['default']) {
            // @codingStandardsIgnoreEnd
                this.changeBooleanLabel({target: node.one('[data-name="default"]').set('checked', true)});
            }
            else if (dataType === 'option' || dataType === 'multioption') {
                if(attribute.options && attribute.options.length){
                    var options = '',
                        optionView = this.getTemplate('option');
                    Y.Array.each(attribute.options, function(val) {
                        options += optionView.render({
                            value: val,
                            attributeIndex: node.getAttribute('data-index'),
                            optionIndex: Y.StepFour.options++,
                            defaultType: dataType === 'option' ? 'radio' : 'checkbox',
                            // @codingStandardsIgnoreStart
                            checked: this.shouldOptionBeSelectedAsDefault(val, attribute['default']) ? 'checked' : ''
                            // @codingStandardsIgnoreEnd
                        });
                    }, this);
                    node.one('.options').append(options);
                }
            }
        },

        /**
         * When rendering an extended widgets (multi)option attribute, this determines
         * whether the current radio/checkbox should be selected
         * @param  {mixed} value        The value of the current option
         * @param  {mixed} defaultValue The widgets default value
         * @return {bool}               Whether the current option should be checked on page load
         */
        shouldOptionBeSelectedAsDefault: function(value, defaultValue){
            if(Y.Lang.isArray(defaultValue)){
                for(var i in defaultValue){
                    if(defaultValue[i] === value){
                        return true;
                    }
                }
            }
            return value === defaultValue;
        },

        /**
         * Removes an attribute object when its 'x' button is clicked
         * @param {object} e DOM Event facade
         */
        removeAttribute: function(e) {
            e.halt();
            var attr = e.target.ancestor('.attribute');
            if (attr.hasClass('inherited')) {
                Widget.attributes[attr.getAttribute('data-orig-name')] = 'unset';
            }
            this.enableContinue();
            attr.transition({height: 0, duration: 0.5}, function() {
                var elementToFocusOn = this.next() || this.previous() || this.ancestor().next();
                if (elementToFocusOn && (elementToFocusOn = elementToFocusOn.one('button, input'))) {
                    elementToFocusOn.focus();
                }
                this.remove();
            });
        },
        /**
         * Switches the type of the input of the attribute default.
         * @param {object} ancestorAttribute Attribute Node
         * @param {string} dataType data-type that's selected
         */
        switchDataType: function(ancestorAttribute, dataType) {
            dataType = dataType.toLowerCase();

            //If we're switching to an option/multioption type, hide the default input as it's part of the options selector
            if(dataType === 'option' || dataType === 'multioption'){
                ancestorAttribute.one('.row.default').addClass('hide');
                ancestorAttribute.one('.options').removeClass('hide').setAttribute('data-attribute-type', dataType);
                //If we're switching from one option type to the other, we need to swap out the input types
                var existingOptions = ancestorAttribute.all('.row.options input[type=' + (dataType === 'option' ? 'checkbox' : 'radio') + ']');
                existingOptions.each(function(node){
                    this.replaceInput(node, (dataType === 'option' ? 'radio' : 'checkbox'), {name: node.get('name'), id: node.get('id')});
                }, this);
                return;
            }
            ancestorAttribute.one('.row.default').removeClass('hide');
            ancestorAttribute.one('.options').addClass('hide');

            var switchTo = dataType === 'int' ? 'number' : 'text',
                toggleBooleanLabel = 'addClass',
                field = ancestorAttribute.one('[data-name="default"]'),
                descriptionForField = '';

            if (dataType === 'boolean') {
                switchTo = 'checkbox';
                toggleBooleanLabel = 'removeClass';
                descriptionForField = 'booleanDefaultDescription-' + ancestorAttribute.getAttribute('data-index');
            }

            field.get('parentNode')[(toggleBooleanLabel === 'addClass') ? 'removeClass' : 'addClass']('inline')
                .next()[toggleBooleanLabel]('hide');

            if (Y.UA.ie && Y.UA.ie < 9 && (switchTo === 'checkbox' || field.get('type') === 'checkbox')) {
                // Donkey browsers don't allow us to change input types
                this.replaceInput(field, switchTo, {
                    'data-name': 'default',
                    'data-type': switchTo,
                    'aria-describedby': descriptionForField
                });
            }
            else {
                // Set (and validate against) data-type attr, since IE doesn't feel like supporting type='number'
                field.set('type', switchTo).setAttribute('data-type', switchTo);

                if (descriptionForField) {
                    field.setAttribute('aria-describedby', descriptionForField);
                }
                else {
                    field.removeAttribute('aria-describedby');
                }
            }
            this.enableContinue();
        },

        /**
         * Replaces the given node with a new input.
         * @param  {Object} field      Existing input node
         * @param  {String} type       New input type
         * @param  {Object=} attributes Attributes to set on the node
         * @return {Object}            replacement node
         */
        replaceInput: function (field, type, attributes) {
            var replacement = Y.Node.create('<input type="' + type + '"/>');
            Y.Object.each(attributes, function (val, name) {
                replacement.setAttribute(name, val);
            });
            return field.replace(replacement);
        },

        /**
         * Adds a new option input for the attribute.
         * @param {object} e DOM Event facade
         */
        addOption: function(e) {
            var error = Y.one('.validation.error');
            if (error) {
                error.hide();
            }
            e.halt();
            var rowParent = e.target.ancestor('.options.row'),
                attributeType = rowParent.getAttribute('data-attribute-type');
            rowParent.appendChild(
                Y.Node.create(this.getTemplate('option', {
                    value: '',
                    attributeIndex: rowParent.ancestor('.attribute').getAttribute('data-index'),
                    optionIndex: Y.StepFour.options++,
                    defaultType: attributeType === 'option' ? 'radio' : 'checkbox',
                    checked: '' }))
            ).one('input').focus();
        },
        /**
         * Removes an option input for the attribute.
         * @param {object} e DOM Event facade
         */
        removeOption: function(e) {
            e.halt();
            e.target.ancestor('div.row').remove();
        },
        /**
         * When the data-type dropdown changes.
         * @param {object} e DOM Event facade
         */
        dataTypeChange: function(e) {
            this.switchDataType(e.target.ancestor('.attribute'), e.target.get('value'));
            this.enforceFieldRequirements(e);
        },
        /**
         * When the checkbox default for boolean-type attributes changes.
         * @param {object} e DOM Event facade
         */
        changeBooleanLabel: function(e) {
            var target = e.target;
            target.get('parentNode').next().set('innerHTML', (target.get('checked')) ? messages.trueCap : messages.falseCap);
        },
        /**
         * Sets `Widget.attributes` to the state of
         * the attributes on the page.
         */
        setAttributes: function() {
            var attrs = {},
                attr,
                error = false;
            this.content.all('.attribute').each(function(node) {
                attr = {};
                if (node.all('[data-name]').some(function(input, value, name, type, fieldError) {
                    name = input.getAttribute('data-name');
                    value = Y.Lang.trim(input.get('value'));
                    type = input.getAttribute('data-type') || input.getAttribute('type');

                    if (type === 'checkbox') {
                        value = input.get('checked');
                    }
                    else if (type === 'number') {
                        if (value === '') {
                            // Null is a valid default for int-type attributes
                            value = null;
                        }
                        else {
                            value = parseInt(value, 10);
                            fieldError = isNaN(value);
                        }
                    }
                    else if (name === 'option') {
                        attr.options || (attr.options = []);
                        if (this.validateAttrName(value, true)) {
                            if(input.next('input').get('checked')){
                                // @codingStandardsIgnoreStart
                                if(attr.type === 'option'){
                                    attr['default'] = value;
                                }
                                else{
                                    attr['default'] || (attr['default'] = []);
                                    attr['default'].push(value);
                                }
                                // @codingStandardsIgnoreEnd
                            }
                            attr.options.push(value);
                            input.removeClass('highlight');
                            return;
                        }
                        else {
                            fieldError = true;
                        }
                    }
                    else if(name === 'default' && (attr.type === 'option' || attr.type === 'multioption')){
                        //Default values are calculated differently for option types
                        return false;
                    }
                    else {
                        fieldError = (!value && name !== 'default') || (name === 'name' && !this.validateAttrName(value));
                    }

                    if(name === 'name' && !fieldError) {
                        node.setAttribute('data-attr-name', value);
                        // Check for duplicate attribute names
                        fieldError = attrs[value] !== undefined;
                    }

                    if (fieldError) {
                        input.addClass('highlight').focus();
                        var errorMessage = messages.validationErrors[name];
                        if (errorMessage) {
                            input.get('parentNode').append(this.getTemplate('validation', {message: errorMessage}));
                        }
                        return true;
                    }
                    else {
                        input.removeClass('highlight');
                        attr[name] = value;
                    }
                }, this)) {
                    error = true;
                }
                attrs[attr.name] = attr;
            }, this);

            if (!error) {
                error = this.validateOptions(attrs);
            }
            Widget.attributes = attrs;
            return !error;
        },

        /**
         * Verifies that all option-type attributes
         * actually have options present.
         * @param  {Object} attrs List of attributes
         * @return {boolean}       T if there's an error, F otherwise
         */
        validateOptions: function(attrs) {
            return Y.Object.some(attrs, function(attr) {
                if ((attr.type === 'option' || attr.type === 'multioption') && !attr.options) {
                    var errorMessage = Y.Node.create(this.getTemplate('validation', { message: messages.optionError }));
                    errorMessage.setAttribute('tabindex', 0).addClass('error');
                    this.content.one('.attribute[data-attr-name="' + attr.name + '"] .options')
                        .append(errorMessage);
                    errorMessage.focus();

                    return true;
                }
            }, this);
        },

        /**
         * Validate the attributes name.
         * @param {string} value Attribute name
         * @param {boolean} allowSpaces Whether or not the validation should allow spaces
         * @return boolean true if the attribute is valid, false otherwise
         */
        validateAttrName: function(value, allowSpaces) {
            return ((allowSpaces) ? value.match(/^[\sA-Za-z0-9._-]+$/g) : value.match(/^[a-z0-9_-]+$/g)) !== null;
        },

        /**
         * (Re)enable the continue button.
         */
        enableContinue: function() {
            this.button.removeClass('hide');
        },

        /**
         * Validates required fields on each attribute
         * @param {object} e DOM Event facade
         */
        enforceFieldRequirements: function(e) {
            var type, name, value, errorMessages,
                error = false,
                target = e.target;

            if(e.target.get('tagName').toLowerCase() === 'select') {
                type = target.get('value');
                target = target.ancestor('.attribute').one('input[data-name="default"]');
            }
            else {
                type = target.ancestor('.attribute').one('select[data-name="type"]').get('value');
            }

            name = target.getAttribute('data-name');
            value = Y.Lang.trim(target.get('value'));

            if(name === 'description') {
                error = !value;
            }
            else if(name === 'name') {
                errorMessages = target.get('parentNode').all('.validation');
                if(errorMessages.size() === 0) {
                    target.get('parentNode').append(this.getTemplate('validation', {message: messages.validationErrors[name]}));
                }
                error = !value;
            }
            else if (name === 'default' && type === 'ajax') {
                error = !value;
            }

            if(error) {
                target.addClass('highlight');
            }
            else {
                target.get('parentNode').all('.validation').remove(true);
                target.removeClass('highlight');
            }
        },

        /**
         * Takes existing attribute default value property and returns new property taking into
         * account data types for option and multioption types
         */
        setDefaultAttributeValue: function(existingDefaultProperty, attributeType, valueToAdd){
            var defaultValue;
            if(attributeType === 'multioption'){
                defaultValue = existingDefaultProperty || [value];
            }
            else{
                defaultValue = value;
            }
            return defaultValue;
        },

        /**
         * Goes to the next step when the continue button is clicked.
         * @param {object} e DOM Event facade
         */
        nextStep: function(e) {
            e.halt();

            if (!this.validatePreviousStep(Widget)) return;

            this.content.all('.attribute .validation').remove();
            if (this.setAttributes()) {
                e.target.addClass('hide');

                this.fire({ widget: Widget });
            }
        }
    });
    Y.StepFour.attributes = 0;
    Y.StepFour.options = 0;
}, null, {
    requires: ['step', 'node', 'transition', 'io-base', 'json']
});
