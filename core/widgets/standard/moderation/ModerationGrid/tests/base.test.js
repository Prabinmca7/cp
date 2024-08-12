/** start adding the test suite and tests */
UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'ModerationGrid_0'
}, function (Y, widget, baseSelector) {
    var moderationGridTests = new Y.Test.Suite({
        name: "standard/moderation/ModerationGrid",
        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.actionEventFired = false;
                    this.instanceID = 'ModerationGrid_0';
                    this.instance = widget;
                    RightNow.Event.on('evt_rowSelected', this.setRowSelectionEventFired, this);
                },
                setRowSelectionEventFired: function() {
                    this.rowSelectionEventFired = true;
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }

        }

    });

    function isAllSelected() {
        var allSelected = true;
        Y.all(baseSelector + '_Content table td input[type="checkbox"]').each(function(inputObj) {
            if(inputObj.get('checked') !== true ) {
                allSelected =  false;
            }
        });
        return allSelected;
    }

    function fireActionEvent(action_name)
    {
        var eventObject = new RightNow.Event.EventObject(this.instance, {data: {
            w_id: 'rn_'+this.instanceID,
            action: 1,
            action_name: action_name,
            report_id: widget.data.attrs.report_id
        }});

       RightNow.Event.fire("evt_moderationAction", eventObject);

    }

    moderationGridTests.add(new Y.Test.Case({
        name: "moderation grid test cases",

        "Test row-selection event is fired when a row gets selected": function() {
            this.initValues();
            Y.one(baseSelector + '_Content table  th input[type=checkbox]').set('checked', false);
            Y.one(baseSelector + '_Content table  th input[type=checkbox]').simulate('click');
            Y.Assert.areSame(true, this.rowSelectionEventFired,'Row selection event should have been fired');

            this.initValues();
            Y.one(baseSelector + '_Content table  th input[type=checkbox]').set('checked', false);
            var selectAll = Y.one(baseSelector + '_Content table  th input[type=checkbox]');
            Y.Assert.isObject(selectAll, 'Select-all checkbox not exist');
            selectAll.simulate('click');
            Y.Assert.areSame(true, this.rowSelectionEventFired,'Row selection event should have been fired when select all check box is checked');
        },

        "Check select-all checkbox present and Select all rows when select-all is clicked": function() {
            this.initValues();
            Y.one(baseSelector + '_Content table  th input[type=checkbox]').set('checked', false);
            var selectAll = Y.one(baseSelector + '_Content table  th input[type=checkbox]');

            Y.Assert.isObject(selectAll, 'Select-all checkbox not exist');
            selectAll.simulate('click');
            Y.Assert.areSame(true, isAllSelected(), 'Unable to check all rows using select-all checkbox');

            Y.one(baseSelector + '_Content table  th input[type=checkbox]').simulate('click');
            Y.Assert.areSame(false, isAllSelected(), 'Unable to uncheck all rows using select-all checkbox');

        },

        "Test sorting is disabled for given column": function() {
            var headNo = 0;
            Y.all(baseSelector + '_Content table th').each(function(columnHead) {
                if (typeof widget.data.attrs.exclude_from_sorting != 'undefined') {
                    if (Y.Array.indexOf(widget.data.attrs.exclude_from_sorting, ((headNo + 1).toString())) !== -1){
                         Y.Assert.areSame(false, columnHead.hasClass('yui3-datatable-sortable-column'), 'Disabling sorting on given column failed');
                    }
                }
                headNo++;
            });
        },

        "Test modActionPerformed flag and message is set correctly": function() {
            this.initValues();
            Y.Assert.areSame(false, widget._modActionPerformed, 'Flag should be false');
            fireActionEvent('suspend');
            Y.Assert.areSame(true, widget._modActionPerformed, 'Flag should be true');
            Y.Assert.areSame(true, widget._hasValidationMessage, 'There should be a validation message');
        },

        "Validation should fail when no question is selected but action event is triggered": function() {
            this.initValues();
            Y.one(baseSelector + '_Content table  th input[type=checkbox]').set('checked', false);
            widget._removeErrorMessage();
            fireActionEvent('suspend');
            Y.Assert.areSame(true, widget._hasValidationMessage, 'There should be a validation error');
        },

        "Test collect selected object ids": function() {
            this.initValues();
            Y.one(baseSelector + '_Content table  th input[type=checkbox]').set('checked', false);
            widget._removeErrorMessage();
            var objectIds = widget._getSelectedObjectIDs('suspend_user');
            if(Y.all(baseSelector + '_Content table td input[type="checkbox"]')) {
                Y.Assert.areSame(true, objectIds.rowDataExist, 'Table row not exist');
                Y.Assert.areSame(0, objectIds.object_ids.length, 'Objcet IDs are not empty');

                Y.one(baseSelector + '_Content table  th input[type=checkbox]').simulate('click');
                objectIds = widget._getSelectedObjectIDs('suspend_user');
                Y.Assert.areSame(true, objectIds.object_ids.length > 0, 'Objcet IDs are empty');
            }
        },

        "Test icon columns presence": function() {
            var headNo = 1;

            Y.all(baseSelector + '_Content table th').each(function(columnHead) {
                if (typeof widget.data.attrs.icon_cols != 'undefined') {
                    if (Y.Array.indexOf(widget.data.attrs.icon_cols, (headNo.toString())) !== -1){
                        Y.Assert.areSame(true, columnHead.one('span').hasClass('rn_ScreenReaderOnly'), 'Column header label is a text and not an icon');
                    }
                }
                headNo++;
            });
        },
        "No error should be displayed when just one row selected with max_allowed_selected_rows attribute value is  2": function() {
            this.initValues();
            Y.one(baseSelector + '_Content table  th input[type=checkbox]').set('checked', true);
            Y.one(baseSelector + '_Content table  tr input[type=checkbox]').simulate('click');
            Y.one(baseSelector + '_Content table  .yui3-datatable-data input[type=checkbox]').simulate('click'); //select just on row
            widget._removeErrorMessage();
            fireActionEvent('suspend');
            this.wait(function() {
                Y.Assert.areSame(false, widget._hasValidationMessage, 'There should be a validation error');
            }, 50);
        }
    }));

    return moderationGridTests;
}).run();

