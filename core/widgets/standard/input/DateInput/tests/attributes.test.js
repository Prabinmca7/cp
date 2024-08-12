UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'DateInput_0'
}, function(Y, widget, baseSelector){
    var dateInputTests = new Y.Test.Suite({
        name: 'standard/input/DateInput',
        setUp: function(){
            Y.one(document.body).append('<div id="test">');
            new RightNow.Form({attrs: {challenge_location: 'test', error_location: 'test'}, js: {f_tok: 'filler'}}, 'DateInput_0_form', Y);

            var testExtender = {
                initValues: function() {
                    this.fullColumnName = 'Incident.CustomFields.c.date1';
                    this.columnName = 'date1';
                    this.labelInput = widget.data.attrs.label_input;
                    var prefix = baseSelector + '_' + this.fullColumnName.replace(/\./g, '\\.') + '_';
                    this.yearID = prefix + 'Year';
                    this.monthID = prefix + 'Month';
                    this.dayID = prefix + 'Day';
                    this.yearElement = Y.one(this.yearID);
                    this.monthElement = Y.one(this.monthID);
                    this.dayElement = Y.one(this.dayID);
                    this.fields = [this.yearElement, this.monthElement, this.dayElement];
                    this.formID = baseSelector + '_form';
                    this.formElement = Y.one(this.formID);
                    this.errorDiv = 'hereBeErrors';

                    var d = new Date();
                    this.yearNow = d.getFullYear();
                    this.monthNow = d.getMonth() + 1;
                    this.dayNow = d.getDate();
                    if (widget.data.attrs.default_value === 'now') {
                        this.maxYear = this.yearNow;
                        this.selectedYear = this.yearNow;
                        this.selectedMonth = this.monthNow;
                        this.selectedDay = this.dayNow;
                    } else {
                        this.maxYear = 2100;
                        this.selectedYear = 2000;
                        this.selectedMonth = 1;
                        this.selectedDay = 2;
                    }
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    dateInputTests.add(new Y.Test.Case({
        name: 'date validation',

        testInitialAttributes: function() {
            this.initValues();
            Y.Assert.areSame(this.fullColumnName, widget.data.attrs.name);
            Y.Assert.areSame(1970, widget.data.attrs.min_year);
            Y.Assert.areSame(this.maxYear, widget.data.attrs.max_year);
            Y.Assert.areSame(this.labelInput, widget.data.attrs.label_input);
            Y.Assert.isFalse(widget.data.attrs.required);
        },

        getDateOption: function(type, optionValue) {
            var types = {year: {id: this.yearID, 'default': this.selectedYear},
                         month: {id: this.monthID, 'default': this.selectedMonth},
                         day: {id: this.dayID, 'default': this.selectedDay}},
                value = (optionValue == null) ? types[type]['default'] : optionValue;

            return Y.one(types[type].id + ' option[value="' + value + '"]');
        },

        resetDateFields: function() {
            var numberOfFields = this.fields.length;
            for (var i = 0; i < numberOfFields; i++) {
                this.fields[i].all('option').set('selected', false);
            }
        },

        testDefaultValue: function() {
            Y.Assert.isNotNull(this.yearElement);
            Y.Assert.isNotNull(this.monthElement);
            Y.Assert.isNotNull(this.dayElement);

            Y.Assert.isTrue(this.getDateOption('year').get('selected'));
            Y.Assert.isTrue(this.getDateOption('month').get('selected'));
            Y.Assert.isTrue(this.getDateOption('day').get('selected'));
        },

        testDateValidation: function() {
            this.initValues();
            var year, month, day,
                tooSmall = ' value is too small',
                notValid = ' is not a valid date',
                incomplete = ' is not completely filled in',
                valid = '',
                dates = [
                    [1970,  1,   1, tooSmall],
                    [  '', '',  '', valid],
                    [2000,  1,  '', incomplete],
                    [2000, '',   1, incomplete],
                    [  '',  1,   1, incomplete],
                    [1999,  4,  31, notValid],
                    [1999,  6,  31, notValid],
                    [1999,  9,  31, notValid],
                    [1999, 11,  31, notValid],
                    [2000,  2,  31, notValid],
                    [1999,  2,  30, notValid],
                    [1999,  2,  29, notValid],
                    [1999,  2,  31, notValid],
                    [2000,  2,  29, valid], // leap year
                    [this.yearNow,  this.monthNow,  this.dayNow, valid]
                ];

            for (var i=0, list, dateString, errorDiv, expected, actual; i < dates.length; i++) {
                list = dates[i];

                this.resetDateFields();

                year = this.getDateOption('year', list[0]);
                month = this.getDateOption('month', list[1]);
                day = this.getDateOption('day', list[2]);
                dateString = month.get('value') + '/' + day.get('value') + '/' + year.get('value');

                year.set('selected', true);
                month.set('selected', true);
                day.set('selected', true);

                widget.onValidate('submit', [{data: {error_location: this.errorDiv}}]);
                errorDiv = Y.one('#' + this.errorDiv);
                expected = list[3];
                actual = errorDiv.get('innerHTML');
                errorDiv.set('innerHTML', '');
                if (expected === valid) {
                    Y.Assert.areSame(expected, actual, dateString);
                } else {
                    Y.Assert.isTrue(actual.indexOf(this.labelInput + expected) !== -1, dateString);
                }
            }
        },

        testOnChange: function() {
            this.initValues();
            var eventReceived = false;
            RightNow.Event.on("evt_formInputDataChanged", function(args) {
                eventReceived = true;
            });

            year = this.getDateOption('year', 2000);
            month = this.getDateOption('month', 1);
            day = this.getDateOption('day', 1);

            year.set('selected', true);
            month.set('selected', true);
            day.set('selected', true);
            this.yearElement.simulate("change");
            Y.Assert.isTrue(eventReceived, "On Change event is not received");
        }
    }));

    dateInputTests.add(new Y.Test.Case({
        name: 'Test constraint change',

        "Changing the requiredness should update the label and remove old error messages": function() {
            widget.input.set('value', '');

            //Check the labels. They should NOT contain an asterisk or screen reader text.
            var errorDiv = Y.one('#hereBeErrors'),
                labelContainer = Y.one(baseSelector + '_Legend'),
                validationData = [{data: {error_location: 'hereBeErrors'}}];

            Y.Assert.areSame('', errorDiv.get('innerHTML'));
            Y.Assert.isTrue(labelContainer.get('text').indexOf(widget.data.attrs.label_input) !== -1);
            Y.Assert.isTrue(labelContainer.get('text').indexOf('*') === -1);

            //Alter the requiredness. Labels should be added.
            widget.fire('constraintChange', {'required': true});

            if (widget.data.attrs.label_input) {
                Y.Assert.isTrue(labelContainer.one('.rn_Required').getAttribute('aria-label').indexOf('Required') !== -1);
                Y.Assert.isTrue(labelContainer.get('text').indexOf('*') !== -1);
            }

            //Submitting the form should cause the fields to be highlighted and an error message added
            widget.onValidate('validate', validationData);

            Y.Assert.isTrue(errorDiv.get('childNodes').size() === 1);
            Y.Assert.isTrue(labelContainer.hasClass('rn_ErrorLabel'));
            Y.Assert.isTrue(widget.input.hasClass('rn_ErrorField')[0]);
            Y.Assert.isTrue(widget.input.hasClass('rn_ErrorField')[1]);

            //Altering the requiredness again should remove labels and messages
            widget.fire('constraintChange', {'required': false});
            Y.Assert.isTrue(labelContainer.get('text').indexOf('*') === -1);
            Y.Assert.isTrue(labelContainer.get('text').indexOf('Required') === -1);

            Y.Assert.isTrue(errorDiv.get('childNodes').size() === 0);
            Y.Assert.isFalse(labelContainer.hasClass('rn_ErrorLabel'));
            Y.Assert.isFalse(widget.input.hasClass('rn_ErrorField')[0]);
            Y.Assert.isFalse(widget.input.hasClass('rn_ErrorField')[1]);
        }
    }));

    return dateInputTests;

}).run();
