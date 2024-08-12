/*
 * By default, it's difficult to get this widget to actually render (requires topics being defined and running agedatabase).
 * That doesn't mean the JS can't be tested. Manually load in the JS file, provide the minimum necessary DOM fixture, instantiate
 * the widget, and begin testing.
 */
UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    jsFiles: ['/cgi-bin/{cfg}/php/cp/core/widgets/standard/knowledgebase/TopicBrowse/logic.js'],
    yuiModules: ['gallery-treeview']
    /* omit `instanceID` since we don't want the UnitTest runner to instantiate the widget */
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: 'standard/knowledgebase/TopicBrowse',
        setUp: function() {
            // If I were testing something that dealt with the DOM, then this is where I'd jam in
            // a DOM fixture so that the widget could appropriately find the elements it needs.

            widget = new RightNow.Widgets.TopicBrowse({
                js: {
                    filters: {
                        attributes: 1,
                        data_type: 3,
                        default_value: null,
                        expression1: "cluster_tree2answers.parent_id",
                        fltr_id: 10,
                        name: "cluster ID",
                        oper_id: 1,
                        optlist_id: null,
                        prompt: "cluster ID",
                        required: 0,
                        type: 1
                    },
                    rnSearchType: 'topicBrowse',
                    topics: []
                },
                attrs: {
                    depth_limit: 3,
                    display_answer_relevancy: true,
                    filter_name: "parent",
                    label_description: "",
                    report_id: 178,
                    rn_container: "true",
                    rn_container_id: "rnc_1"
                }
            }, 'instance_id', Y);
        }
    });

    suite.add(new Y.Test.Case({
        name: 'Event Handling and Operation',

        "_nodeSelected should remain selected following _onSearch": function() {
            widget._nodeSelected = true;
            widget._onSearch();
            Y.Assert.isTrue(widget._nodeSelected);
        },

        "The cluster that was clicked on should be the selected node following a reportResponse and not be overridden by the 'bestMatch'": function() {
            var clusterID = 10001,
                nodeEvent = {};

            // override needed methods;
            widget._setLoading = function(){return;};
            widget._createRelevancyMeter = function(){return;};
            widget._selectNode = function(e) {
                nodeEvent = e;
            };
            widget._tree = {
                getNodeByProperty: function(key, value) {
                    return {key: key, selected: value};
                },
                render: function() {
                    return;
                }
            };

            widget._currentNode = clusterID;
            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, {
                data: {
                    topics: {
                        10001: {clusterID: 10001, display: "display",   matchedLeaves: 1, weight: 90},
                        10005: {clusterID: 10005, display: "bestMatch", matchedLeaves: 7, weight: 100}
                    }
                },
                filters: {
                    report_id: 178,
                    data: {
                        fltr_id: 10,
                        oper_id: 1,
                        val: clusterID
                    }
                }
            }));
            Y.Assert.areSame(clusterID, nodeEvent.node.selected);
            Y.Assert.areSame(clusterID, widget._currentNode);
            Y.Assert.isFalse(widget._nodeSelected);
        },
        "Topics as an empty list resets the tree": function() {
            var treeWasReset = false;

            widget._displayAllNodes = function(e) {
                treeWasReset = true;
            };
            widget._tree = {
                getRoot: function() {
                    return {children: []};
                },
                collapseAll: function() {return;},
                render: function() {return;}
            };

            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, {data: {topics: []}}));
            Y.Assert.isTrue(treeWasReset);
        }
    }));

    return suite;
}).run();
