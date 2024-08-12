UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'ModerationAction_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/moderation/ModerationAction",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.actionEventFired = false;
                    //initially no rows are selected
                    fireRowSelectedEvent(false);
                    RightNow.Event.on('evt_moderationAction', this.setActionEventFired, this);
                },
                setActionEventFired: function() {
                    this.actionEventFired = true;
                }
            };
            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        },
        tearDown: function() {
            this.actionEventFired = false;
            RightNow.Event.unsubscribe('evt_moderationAction', this.setEventFired, this);
        }
    });

    function fireRowSelectedEvent(rowSelected, report_id) {
        RightNow.Event.fire("evt_rowSelected", new RightNow.Event.EventObject(this.instance, {data: {
            w_id: 'rn_' + this.instanceID,
            isRowSelected: rowSelected,
            report_id: report_id || widget.data.attrs.report_id
        }}));
    }

    suite.add(new Y.Test.Case({
        name: "Moderation actions test cases of this test suite",

        "Test atleast one action button present": function() {
            this.initValues();
            Y.Assert.isObject(Y.one(baseSelector).all('button'), 'No Button exist');
        },

        "Test action event is fired when button is clicked": function() {
            this.wait(function() {                
            }, 1000);
            this.initValues();
            Y.Assert.isObject(Y.one(baseSelector).all('button'), 'No Button exist');
            //fire row selection event
            fireRowSelectedEvent(true);
            Y.one(baseSelector).one('button').simulate('click');
            Y.Assert.areSame(true, this.actionEventFired,'Action event should have fired');
        },

        "Test action buttons are disabled for row selection in different report": function() {
            this.initValues();
            //fire row selection event from different report
            fireRowSelectedEvent(true, 123456);
            Y.Assert.areSame(Y.one(baseSelector).one('button').get("disabled"), true, "Action button should be disabled");
        },

        "Test action event fired for reset flags action if exist": function() {
            this.initValues();
            if (Y.one(baseSelector).one("button[value=reset_flags]")) {
                Y.one(baseSelector).one("button[value=reset_flags]").simulate('click');
                Y.Assert.areSame(false, this.actionEventFired,'Action event should not have fired as no rows are selected');
                //fire row selection event
                fireRowSelectedEvent(true);
                Y.one(baseSelector).one("button[value=reset_flags]").simulate('click');
                Y.Assert.areSame(true, this.actionEventFired,'Action event should have fired');
            }
        },

        "Test action event fired for suspend user action if exist": function() {
            this.initValues();
            if (Y.one(baseSelector).one("button[name=suspend_user]")) {
                Y.one(baseSelector).one("button[name=suspend_user]").simulate('click');
                 Y.Assert.areSame(false, this.actionEventFired,'Action event should not have fired as no rows are selected');
                //fire row selection event
                fireRowSelectedEvent(true);
                Y.one(baseSelector).one("button[name=suspend_user]").simulate('click');
                Y.Assert.areSame(true, this.actionEventFired,'Action event should have fired');
            }
        },

        "Test action event fired for restore user action if exist": function() {
            this.initValues();
            if (Y.one(baseSelector).one("button[name=restore_user]")) {
                Y.one(baseSelector).one("button[name=restore_user]").simulate('click');
                Y.Assert.areSame(false, this.actionEventFired,'Action event should not have fired as no rows are selected');
                //fire row selection event
                fireRowSelectedEvent(true);
                Y.one(baseSelector).one("button[name=restore_user]").simulate('click');
                Y.Assert.areSame(true, this.actionEventFired,'Action event should have fired');
            }
        },

        "Test to check dialog's Move, Cancel and Close buttons functionality": function() {
            this.initValues();
            var moveActionButton = Y.one(baseSelector).one("button[name=move]");
            if (moveActionButton) {
                var dialog, closeBtn, moveBtn, cancelBtn;
                //dialog should not be visible if no row is selected in grid
                moveActionButton.simulate('click');
                dialog = Y.one('#rnDialog1');
                Y.Assert.areSame(dialog, null, 'Dialog should not open if no row is selected in grid');

                //dialog should be visible if row is selected in grid
                fireRowSelectedEvent(true);
                moveActionButton.simulate('click');
                dialog = Y.one('#rnDialog1');
                closeBtn = dialog.all('button').item(0);
                moveBtn = dialog.all('button').item(2);
                cancelBtn = dialog.all('button').item(3);
                Y.assert(!dialog.ancestor('.yui3-panel-hidden'));
                Y.Assert.isTrue(Y.one(dialog).ancestor().hasClass('rn_MoveDialogContainer'), "Dialog doesn't have rn_MoveDialogContainer class");

                //check whether default selected product on opening the dialog box is 0 i.e "Select a Product"
                Y.Assert.areSame(widget.prodcatID, 0);
                Y.one(cancelBtn).simulate('click');
                moveActionButton.simulate('click');
                Y.assert(!dialog.ancestor('.yui3-panel-hidden'));
                Y.one(closeBtn).simulate('click');

                //check whether action event should not be fired when dialog move button is clicked and no product is selected
                widget.prodcatID = null;
                moveBtn.simulate('click');
                Y.Assert.areSame(false, this.actionEventFired, 'ModerationAction event should not be fired');

                //check whether action event should be fired when dialog move button is clicked and product is selected
                widget.prodcatID = 2;
                moveBtn.simulate('click');
                Y.Assert.areSame(true, this.actionEventFired, 'ModerationAction should be fired');
            }
        }
    }));

    return suite;
}).run();
