UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'DateInput_0'
}, function(Y, widget, baseSelector){
    var dateInputTests = new Y.Test.Suite({
        name: 'standard/input/DateInput',
        setUp: function(){
            var testExtender = {
                initValues: function() {
                    this.fullColumnName = 'Incident.CustomFields.CO.FieldDate';
                    this.columnName = 'FieldDate';
                    this.labelInput = 'aDate';
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
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    dateInputTests.add(new Y.Test.Case({
        name: 'date validation',

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

        testMinYearAttribute: function() {
            this.initValues();
            Y.Assert.areSame(1902, widget.data.attrs.min_year);
        },

        testDateValidation: function() {
            this.initValues();
            var year, month, day,
                tooSmall = ' value is too small',
                notValid = ' is not a valid date',
                incomplete = ' is not completely filled in',
                valid = '',
                dates = [
                    [1901, 12,  31, tooSmall],
                    [1902,  1,   1, valid],
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
                if (!year && list[0] === 1901) {
                    // Tack on an unsupported date to verify it's validated as being too small.
                    Y.one(this.yearID).appendChild('<option value="1901">1901</option>');
                    year = Y.one(this.yearID + ' option[value="1901"]');
                }
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
        }
    }));
    return dateInputTests;
});
UnitTest.run();
