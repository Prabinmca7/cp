UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    preloadFiles: [
        '/euf/core/admin/js/deploy/deploy.js',
        '/euf/core/admin/js/deploy/pageSetTable.js',
        '/euf/core/ejs/1.0/ejs-min.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: 'UI Behavior tests for the selectConfigs page' });

    suite.add(new Y.Test.Case({
        name: 'Initial page state',

        'Staging tab is selected': function() {
            Y.assert(Y.one('#selectConfigs').hasClass('selected'));
        }
    }));

    suite.add(new Y.Test.Case({
        name: 'Action menu tests',
        'Toggling the Select All option or a specific row option fires the onChange event and restores focus': function() {
            var onChangeFired = false,
                select = Y.one('#selectAll'),
                previousDropDownChangeEvent = dropDownChangeEvent;
            dropDownChangeEvent = function(menu) {
                previousDropDownChangeEvent(menu);
                onChangeFired = true;
            }
            Y.one('#selectAll').set('value', 1).simulate('change');
            Y.assert(onChangeFired);
            Y.Assert.areSame(document.activeElement, document.getElementById('selectAll'));

            onChangeFired = false;
            Y.one('#ps_1_staging').set('value', 1).simulate('change');
            Y.assert(onChangeFired);
            Y.Assert.areSame(document.activeElement, document.getElementById('ps_1_staging'));
        }
        // TODO: Mock up dataTable so we can test other action menu scenarios..
    }));

    return suite;
}).run();
