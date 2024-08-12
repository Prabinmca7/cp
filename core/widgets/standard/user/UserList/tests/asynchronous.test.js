UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'UserList_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/user/UserList",

    });

    suite.add(new Y.Test.Case({
        name: "Content Load Asynchronously",
        setUp: function() {
            this.expectedValuesOnAjax = {
                w_id: widget.data.info.w_id
            };
        },
        "Returns HTML": function () {
            this.wait(function() {
                Y.Assert.isNotNull(Y.one(baseSelector + ' .rn_UsersTableView'));  
            }, 3000); 
        },
        "Error on loading data": function() {
            Y.one(baseSelector + ' .rn_UsersView').setHTML('');
            var responseInfo = {"result": widget.data.attrs.label_content_load_error};
            UnitTest.overrideMakeRequest(widget.data.attrs.content_load_ajax, this.expectedValuesOnAjax,'_error', widget, responseInfo);
            widget.contentLoad();
            Y.one(baseSelector + " .contentLoadError").simulate('click');
            this.wait(function() {
                Y.Assert.isNotNull(Y.one(baseSelector + ' .rn_UsersTableView'));  
            }, 3000);
        }
    }));

    return suite;
}).run();
