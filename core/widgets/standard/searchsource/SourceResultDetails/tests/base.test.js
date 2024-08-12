UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'SourceResultDetails_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/searchsource/SourceResultDetails"
    });

    suite.add(new Y.Test.Case({
        name: "Behavior",

        setUp: function () {
            this.content = Y.one(baseSelector);
        },

        "Blanks out when no results come in 'response' event": function () {
            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, { data: {
                size: 0
            }}));

            Y.Assert.areSame('', this.content.getHTML());
        },

        "Displays total number of results if total is known": function () {
            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, { data: {
                size: 1,
                total: 2,
                offset: 0
            }}));

            Y.Assert.areSame(RightNow.Text.sprintf(widget.data.attrs.label_known_results, 1, 1, 2), this.content.getHTML());
        },

        "Doesn't display total number of results if total isn't known": function () {
            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, { data: {
                size: 1,
                offset: 0
            }}));

            Y.Assert.areSame(RightNow.Text.sprintf(widget.data.attrs.label_results, 1, 1), this.content.getHTML());
        },

        "Adds to given offset": function () {
            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, { data: {
                size: 4,
                total: 24,
                offset: 10
            }}));

            Y.Assert.areSame(RightNow.Text.sprintf(widget.data.attrs.label_known_results, 11, 14, 24), this.content.getHTML());
        }
    }));

    return suite;
}).run();
