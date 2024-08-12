UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SelectionInput_0'
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: "standard/input/SelectionInput",        
        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.isCheckBox = widget.data.js.type === "Boolean" && widget.data.attrs.display_as_checkbox;
                    this.isRadio = widget.data.js.type === "Boolean" && !widget.data.attrs.display_as_checkbox;
                    this.isSelect = !this.isRadio && !this.isCheckBox;
                    this.errorID = 'hereBeErrors';
                    this.errorDiv = Y.one('#' + this.errorID);
                    this.label = Y.one('label');

                    if (this.isSelect) {
                        this.input = Y.one('select');
                        this.setAValue = function(val) {
                            this.input.set('selectedIndex', val || 0);
                        };
                    }
                    else if (this.isRadio) {
                        this.input = Y.all('input[type="radio"]');
                        this.label = Y.one('legend');
                        this.setAValue = function(val) {
                            this.input.removeAttribute('checked');
                            if (val !== null) {
                                this.input.item((val) ?  0 : 1).setAttribute('checked', 'checked');
                            }
                        };
                    }
                    else {
                        this.input = Y.one('input[type="checkbox"]');
                        this.setAValue = function(val) {
                            if (val) {
                                this.input.setAttribute('checked', 'checked');
                            }
                            else {
                                this.input.removeAttribute('checked');
                            }
                        };
                    }
                },
                errorIndicators: function() {
                    return this.input.hasClass('rn_ErrorField') && this.label.hasClass('rn_ErrorLabel');
                }
            };
            
            for(var item in this.items)
            {
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
            Y.all('input').removeAttribute('checked');
        },

        requiredError: function(numberOfErrors) {
            Y.Assert.areSame(numberOfErrors || 1, this.errorDiv.all('a').size());
            var message = this.errorDiv.one('a').getHTML();
            Y.assert(message.indexOf('required'));
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

        'No errors when field is set to a value': function() {
            this.setAValue(1);
            this.submit();
            Y.assert(!this.errorDiv.getHTML());
            Y.assert(!this.errorIndicators());
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'Blur validation',

        setUp: function() {
            this.initValues();
            Y.all('select, input').removeClass('rn_ErrorField');
            Y.all('label').removeClass('rn_ErrorLabel');
        },

        'Indicators appear on blur if field is blank': function() {
            this.setAValue(null);
            
            Y.assert(!this.errorIndicators());
            var element = (this.input instanceof Y.NodeList) ? this.input.item(1) : this.input;
            element.focus();
            element.once('blur', function() {
                Y.assert(this.errorIndicators());
            }, this);
            element.blur();

        },

        'Indicators are removed on blur when field has a value': function() {
            this.setAValue(1);

            var element = (this.input instanceof Y.NodeList) ? this.input.item(1) : this.input;
            element.focus();
            element.once('blur', function() {
                Y.assert(!this.errorDiv.getHTML());
                Y.assert(!this.errorIndicators());
            }, this);
            element.blur();

        }
    }));

    return tests;
});
UnitTest.run();