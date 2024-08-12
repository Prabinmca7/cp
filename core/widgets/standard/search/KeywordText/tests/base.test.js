UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'KeywordText_0'
}, function(Y, widget, baseSelector) {
    var testSuite = new Y.Test.Suite({
        name: "standard/search/KeywordText",
    });
        
    testSuite.add(new Y.Test.Case({
        name: "Event Handling and Operation",
        'Ensure the widget has an initial value set if one is given': function() {
            var textBox = Y.one(baseSelector + '_Text'),
                hasTestedCallback = false;

            //Request the initial value, if there is a url parameter it should be set
            widget.searchSource().once('send', function(evtName, args) {
                hasTestedCallback = true;
                Y.Assert.areSame('send', evtName);
                args = args[0].allFilters.keyword.filters;
                if(!RightNow.Url.getParameter('kw')) {
                    Y.Assert.areSame('', args.data);       
                }
                else {
                    Y.Assert.areSame('iphone', args.data);
                }
                return false;
            }).fire('search', new RightNow.Event.EventObject(widget, {data: 'filter test'}));
            Y.assert(hasTestedCallback, 'The callback was never executed');
        },

        'Ensure that the keywordText widget changes its value when the keywordChanged event is fired': function() {
            var textBox = Y.one(baseSelector + '_Text'),
                hasTestedCallback = false,
                eo = new RightNow.Event.EventObject(widget, {
                    data: "Filter Test", 
                    filters: {
                        "searchName": widget.data.searchName,
                        "data": "Change Test &#039; &gt; &lt; &quot;",
                        "rnSearchType": widget.data.rnSearchType,
                        "report_id": widget.data.attrs.report_id
                    }
                });

            textBox.set('value', '');
            widget.searchSource().once("keywordChanged", function(event, args) {
                hasTestedCallback = true;
                args = args[0];
                Y.Assert.areSame('keywordChanged', event);
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.isObject(args.filters);
                Y.Assert.areSame(widget.instanceID, args.w_id);
                Y.Assert.areSame(widget._decoder(args.filters.data), textBox.get('value'), "Search Filters Request Event");
            }).fire("keywordChanged", eo);
            textBox.set('value', '');
            Y.assert(hasTestedCallback, 'The callback was never executed');
        },

        'Ensure that the widget responds with the correct filter when it is requested for a search': function() {
            var textBox = Y.one(baseSelector + '_Text'),
                testValue = "Change Test &#039; &gt; &lt; &quot;",
                hasTestedCallback = false;

            //Change the value then request it and make sure they match
            textBox.set('value', testValue);
            widget.searchSource().once('send', function(evtName, args) {
                hasTestedCallback = true;
                Y.Assert.areSame('send', evtName);
                args = args[0].allFilters.keyword.filters;
                Y.Assert.areSame(testValue, args.data);       
                return false;
            }).fire('search', new RightNow.Event.EventObject(widget, {data: 'filter test'}));
            Y.assert(hasTestedCallback, 'The callback was never executed');
        },

        'The empty reset event from the history manager should reset KeywordText to its initial value': function() {
            var textBox = Y.one(baseSelector + '_Text'),
                hasTestedCallback = false;

            //Change the value to banana and reset to initial, it should match the initial value
            textBox.set('value', 'Banana');
            widget.searchSource().once('reset', function(event, args) {
                hasTestedCallback = true;
                Y.Assert.areSame('reset', event);
                Y.assert(!args[0]); //Empty event object
                Y.Assert.areSame(widget.data.js.initialValue, textBox.get('value'));
            }).fire('reset');
            Y.assert(hasTestedCallback, 'The callback was never executed');
        },

        'The all reset event should reset KeywordText to its last searched value': function() {
            var textBox = Y.one(baseSelector + '_Text'),
                testValue = "Gibber gibber gibber",
                hasChangedValue = false,
                hasTestedCallback = false;

            //Set a searched value (i.e. someone has performed a search)
            textBox.set('value', testValue);
            widget.searchSource().once('send', function(evtName, args) {
                hasChangedValue = true;
                Y.Assert.areSame('send', evtName);
                args = args[0].allFilters.keyword.filters;
                Y.Assert.areSame(testValue, args.data);       
                return false;
            }).fire('search', new RightNow.Event.EventObject(widget, {data: 'filter test'}));
            Y.assert(hasChangedValue, 'The value was never modified');

            //Pretend someone changed the value
            textBox.set('value', 'My new value! derp');

            //Now reset and make sure the searched value is returned
            widget.searchSource().once('reset', function(evtName, args) {
                hasTestedCallback = true;
                Y.Assert.areSame('reset', evtName);
                Y.Assert.areSame('all', args[0].data.name);
                Y.Assert.areSame(testValue, textBox.get('value'));
            }).fire('reset', new RightNow.Event.EventObject(this, {data: {name: 'all'}}));
            Y.assert(hasTestedCallback, 'The callback was never executed');
        }
    }));
    return testSuite;
});
UnitTest.run();
