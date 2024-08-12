UnitTest.addSuite({ type: UnitTest.Type.Widget, instanceID: 'DateInput_0' },
function(Y, widget, baseSelector) {
    var tests = new Y.Test.Suite({
        name: 'standard/input/DateInput',
        setUp: function(){
            var testExtender = {
                initValues: function() {
                    this.fields = Y.all('select');
                    this.label = Y.one('legend');
                    this.errorID = 'hereBeErrors';
                    this.errorDiv = Y.one('#' + this.errorID);
                    this.lastSelect = this.fields.item(this.fields.size() - 1);
                },

                errorIndicators: function() {
                    return this.fields.hasClass('rn_ErrorField') && this.label.hasClass('rn_ErrorLabel');
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    tests.add(new Y.Test.Case({
        name: 'Required validation',
    
        setUp: function() {
            this.initValues();
        },

        tearDown: function() {
            this.errorDiv.setHTML('');
            Y.all('select').set('value', 0);
        },

        requiredError: function(numberOfErrors) {
            Y.Assert.areSame(numberOfErrors || 1, this.errorDiv.all('a').size());
            var message = this.errorDiv.one('a').getHTML();
            Y.assert(message.indexOf('required') >= 0);
            Y.assert(message.indexOf(widget.data.attrs.label_input) >= 0);
        },

        partiallyFilledOutError: function(numberOfErrors) {
            Y.Assert.areSame(numberOfErrors || 1, this.errorDiv.all('a').size());
            var message = this.errorDiv.one('a').getHTML();
            Y.assert(message.indexOf('not completely filled in') >= 0);
            Y.assert(message.indexOf(widget.data.attrs.label_input) >= 0);
        },

        submit: function() {
            widget.onValidate('submit', [new RightNow.Event.EventObject(widget, { data: { error_location: this.errorID }})]);
        },

        'Error occurs if field is blank': function() {
            this.submit();
            this.requiredError();
            Y.assert(this.errorIndicators());
        },

        'Error occurs if field is partially filled-out': function() {
            Y.one('select').set('value', 1);
            this.submit();
            this.partiallyFilledOutError();
            Y.assert(this.errorIndicators());

            Y.all('select').item(1).set('value', 1);
            this.submit();
            this.partiallyFilledOutError(2);
            Y.assert(this.errorIndicators());

            Y.all('select').item(2).set('value', 2012);
            this.submit();
            this.partiallyFilledOutError(2); // No error, so didn't change.
            Y.assert(!this.errorIndicators());
        },

        'No errors when field is completely filled-out': function() {
            Y.all('select').item(0).set('value', 1);
            Y.all('select').item(1).set('value', 1);
            Y.all('select').item(2).set('value', 2012);
            this.submit();
            Y.assert(!this.errorDiv.getHTML());
            Y.assert(!this.errorIndicators());
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'Blur validation',

        setUp: function() {
            this.initValues();
            Y.all('select').removeClass('rn_ErrorField');
            Y.all('label').removeClass('rn_ErrorLabel');
        },
        doBlur: function() {
            this.lastSelect.focus();
            this.lastSelect.once('blur', function() {
                Y.assert(this.errorIndicators());
            }, this);
            this.lastSelect.blur();
        },
        'Indicators appear on blur if field is blank': function() {
            Y.all('select').set('selectedIndex', 0);
            Y.assert(!this.errorIndicators());
            this.doBlur();
        },

        'Indicators appear on blur if field is partially filled-in': function() {
            Y.one('select').set('value', 1);
            this.doBlur();

            Y.all('select').item(1).set('value', 1);
            this.doBlur();
        },

        'Indicators are removed on blur when field is completely filled-in': function() {
            Y.all('select').item(0).set('value', 1);
            Y.all('select').item(1).set('value', 1);
            Y.all('select').item(2).set('value', 2012);

            this.lastSelect.focus();
            this.lastSelect.once('blur', function() {
                Y.assert(!this.errorDiv.getHTML());
                Y.assert(!this.errorIndicators());
            }, this);
            this.lastSelect.blur();
        }
    }));

    return tests;
}).run();
