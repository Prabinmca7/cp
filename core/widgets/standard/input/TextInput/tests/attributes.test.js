UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'TextInput_0'
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: 'standard/input/TextInput',
        setUp: function(){
            var testExtender = {
                initValues: function() {
                    this.inputID = baseSelector + '_' + widget.data.js.name.replace(/\./g, "\\.");
                    this.input = Y.one(this.inputID);
                    this.errorDiv = 'hereBeErrors';
                    this.validationField = Y.one(this.inputID + '_Validate');
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: 'input validation',

        testInitialAttributes: function() {
            this.initValues();
            Y.Assert.isNotNull(this.input);
            Y.Assert.areSame(widget.data.attrs.default_value, this.input.get('value'));
        },

        testOnBlur: function() {
            this.initValues();

            // input and validation field values match
            this.validationField.focus();
            this.validationField.set('value', widget.data.attrs.default_value);
            this.validationField.once('blur', function() {
                Y.Assert.isFalse(this.validationField.hasClass('rn_ErrorField'));
            }, this);
            this.validationField.blur();

            // input and validation field values don't match
            this.validationField.focus();
            this.validationField.set('value', 'some value that does not match');
            this.validationField.once('blur', function() {
                Y.Assert.isTrue(this.validationField.hasClass('rn_ErrorField'));
            }, this);
            this.validationField.blur();

            //TODO: test 'checkExistingAccount' for login and email fields

        },

        testOnValidate: function() {
            this.initValues();

            var errorDiv = Y.one('#' + this.errorDiv),
                onValidateData = [{data: {error_location: 'hereBeErrors'}}];

            // input and validation field values match
            this.validationField.set('value', widget.data.attrs.default_value);
            widget.onValidate('validate', onValidateData);
            Y.Assert.areSame('', errorDiv.get('innerHTML'));

            this.validationField.set('value', 'no match');
            widget.onValidate('validate', onValidateData);
            Y.Assert.areNotSame(-1, errorDiv.get('innerHTML').indexOf('Validation for the field'));

            errorDiv.get('childNodes').remove();
        },

        testOnChange: function() {
            this.initValues();

            var eventReceived = false;
            RightNow.Event.on("evt_formInputDataChanged", function(args) {
                eventReceived = true;
            });

            this.input.simulate('change');
            Y.Assert.isTrue(eventReceived, "On Change event is not received");
        }

    }));

    suite.add(new Y.Test.Case({
        name: 'Test constraint change',
        setUp: function() {
            new RightNow.Form({attrs: {challenge_location: 'test', error_location: 'hereBeErrors'}, js: {f_tok: 'filler'}}, 'textInput_0_form', Y);
        },
        "Changing the requiredness should update the label and remove old error messages": function() {
            this.initValues();
            this.input.set('value', '');
            this.validationField.set('value', '');

            //Check the labels. They should NOT contain an asterisk or screen reader text.
            var errorDiv = Y.one('#' + this.errorDiv),
                labelContainer = Y.one(baseSelector + '_LabelContainer'),
                validateLabelContainer = Y.one(baseSelector + '_LabelValidateContainer'),
                validationData = [{data: {error_location: 'hereBeErrors'}}];

            Y.Assert.areSame('', errorDiv.get('innerHTML'));
            if (widget.data.attrs.label_input) {
                Y.Assert.isTrue(labelContainer.get('text').indexOf('text1') !== -1);
                Y.Assert.isTrue(labelContainer.get('text').indexOf('hint for text field') !== -1);
                Y.Assert.isTrue(labelContainer.get('text').indexOf('*') === -1);
                Y.Assert.isTrue(validateLabelContainer.get('text').indexOf('*') === -1);
                Y.Assert.areSame("Re-enter a value for the field 'text1'", validateLabelContainer.get('text').trim());
            }

            //Alter the requiredness. Labels should be added.
            widget.fire('constraintChange', {'required': true});

            if (widget.data.attrs.label_input) {
                Y.Assert.isTrue(labelContainer.one('.rn_Required').getAttribute('aria-label').indexOf('Required') !== -1);
                Y.Assert.isTrue(labelContainer.get('text').indexOf('*') !== -1);

                Y.Assert.isTrue(validateLabelContainer.get('text').indexOf('*') !== -1);
                Y.Assert.isTrue(validateLabelContainer.one('.rn_Required').getAttribute('aria-label').indexOf('Required') !== -1);
            }

            //Submitting the form should cause the fields to be highlighted and an error message added
            widget.onValidate('validate', validationData);

            Y.Assert.isTrue(errorDiv.get('childNodes').size() === 1);
            Y.Assert.isTrue(labelContainer.one('label').hasClass('rn_ErrorLabel'));
            Y.Assert.isTrue(this.input.hasClass('rn_ErrorField'));

            //Altering the requiredness again should remove labels and messages
            widget.fire('constraintChange', {'required': false});
            Y.Assert.isTrue(labelContainer.get('text').indexOf('*') === -1);
            Y.Assert.isTrue(validateLabelContainer.get('text').indexOf('*') === -1);
            Y.Assert.isNull(labelContainer.one('.rn_Required'));
            Y.Assert.isNull(validateLabelContainer.one('.rn_Required'));

            Y.Assert.isTrue(errorDiv.get('childNodes').size() === 0);
            Y.Assert.isFalse(labelContainer.one('label').hasClass('rn_ErrorLabel'));
            Y.Assert.isFalse(this.input.hasClass('rn_ErrorField'));
        }
    }));

    return suite;
}).run();