UnitTest.addSuite({
   type:       UnitTest.Type.Widget,
   instanceID: 'SourceFilter_0'
}, function (Y, widget, baseSelector) {
   var suite = new Y.Test.Suite({
       name: "standard/searchsource/SourceFilter"
   });

   suite.add(new Y.Test.Case({
       name: "Event handling and operation",

       setUp: function () {
           this.input = Y.one(baseSelector + '_Dropdown').set('selectedIndex', -1);
           widget.searchSource().on('search', function () {
               // Prevent the search from happening.
               return false;
           });
       },

       "Nothing is returned in response to 'collect' if nothing is selected": function () {
           widget.searchSource().once('searchCancelled', function (e, args) {
               Y.Assert.isFalse(widget.data.attrs.filter_type in args[0]);
           });
           widget.searchSource().fire('collect').fire('search');
       },

       "Value is returned in response to 'collect' if something is selected": function () {
           this.input.set('selectedIndex', 1);
           widget.searchSource().once('searchCancelled', function (e, args) {
               Y.Assert.isTrue(widget.data.attrs.filter_type in args[0]);
               Y.Assert.areSame(widget.data.js.filter.key, args[0][widget.data.attrs.filter_type].key);
               Y.Assert.areSame(widget.data.js.filter.type, args[0][widget.data.attrs.filter_type].type);
           });
           widget.searchSource().fire('collect').fire('search');
       },

       "Dropdown's value is updated in response to 'updateFilters' event": function () {
           var eo = [new RightNow.Event.EventObject(null, { data: [
               {
                   key: widget.data.js.filter.key,
                   value: "yes"
               }
           ]})];
           widget.onFilterUpdate("numberOfBestAnswers", eo);

           Y.Assert.areEqual(1, this.input.get('selectedIndex'));
       },

       "Dropdown's value is updated to initial value in response to 'reset' event": function () {
           widget.initialValue = 'yes';
           widget.searchSource().fire('reset');
           Y.Assert.areEqual(widget.initialValue, this.input.get('value'));
       }
   }));

   return suite;
}).run();
