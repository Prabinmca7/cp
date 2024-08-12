UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'DateInput_0'
}, function(Y, widget, baseSelector){
    var dateInputTests = new Y.Test.Suite({
        name: 'standard/input/DateInput',
        setUp: function(){
            var testExtender = {
                initValues: function() {
                    var prefix = baseSelector + '_Incident\\.CustomFields\\.CO\\.FieldDttm_',
                        d = new Date();
                    this.labelInput = 'aDate';
                    this.errorDiv = 'hereBeErrors';
                    this.yearNow = d.getFullYear();
                    this.monthNow = d.getMonth() + 1;
                    this.dayNow = d.getDate();
                    this.types = {
                        year:   {id: prefix + 'Year'},
                        month:  {id: prefix + 'Month'},
                        day:    {id: prefix + 'Day'},
                        hour:   {id: prefix + 'Hour'},
                        minute: {id: prefix + 'Minute'}
                    };
                    this.fields = [];
                    var element;
                    Y.Array.each(['year','month','day','hour','minute'], function(item) {
                        element = Y.one(this.types[item].id);
                        this.types[item].element = element;
                        this.fields.push(element);
                    }, this);
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    dateInputTests.add(new Y.Test.Case({
        name: 'date validation',

        getDateOption: function(type, value) {
            return Y.one(this.types[type].id + ' option[value="' + value + '"]');
        },

        resetDateFields: function() {
            var numberOfFields = this.fields.length;
            for (var i = 0; i < numberOfFields; i++) {
                this.fields[i].all('option').set('selected', false);
            }
        },

        testDateValidation: function() {
            this.initValues();

            // Tack on a year less than min, just for fun.
            this.types.year.element.appendChild('<option value="1969">1969</option>');

            var year, month, day, hour, minute,
                tooSmall = ' value is too small',
                notValid = ' is not a valid date',
                incomplete = ' is not completely filled in',
                valid = '',
                dates = [
                    [1970,  1,   3, 0, 0, valid],
                    [1970,  1,   2, 23, 59, tooSmall],
                    [1969,  12,  31, 23, 59, tooSmall],
                    [  '', '',  '', '', '', valid],
                    [2000,  1,  '', 12, 0, incomplete],
                    [2000, '',   1, 12, 0, incomplete],
                    [  '',  1,   1, 12, 0, incomplete],
                    [2000,  1,   1, 12, '', incomplete],
                    [1999,  4,  31, 12, 0, notValid],
                    [1999,  6,  31, 12, 0, notValid],
                    [1999,  9,  31, 12, 0, notValid],
                    [1999, 11,  31, 12, 0, notValid],
                    [2000,  2,  31, 12, 0, notValid],
                    [1999,  2,  30, 12, 0, notValid],
                    [1999,  2,  29, 12, 0, notValid],
                    [1999,  2,  31, 12, 0, notValid],
                    [2000,  2,  29, 12, 0, valid], // leap year
                    [this.yearNow,  this.monthNow,  this.dayNow, 12, 0, valid]
                ];

            for (var i=0, list, dateString, errorDiv, expected, actual; i < dates.length; i++) {
                list = dates[i];

                this.resetDateFields();

                year = this.getDateOption('year', list[0]);
                month = this.getDateOption('month', list[1]);
                day = this.getDateOption('day', list[2]);
                hour = this.getDateOption('hour', list[3]);
                minute = this.getDateOption('minute', list[4]);
                dateString = month.get('value') + '/' + day.get('value') + '/' + year.get('value') + ' ' + hour.get('value') + ':' + minute.get('value');

                year.set('selected', true);
                month.set('selected', true);
                day.set('selected', true);
                hour.set('selected', true);
                minute.set('selected', true);

                widget.onValidate('submit', [{data: {error_location: this.errorDiv}}]);
                errorDiv = Y.one('#' + this.errorDiv);
                expected = list[5];
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
