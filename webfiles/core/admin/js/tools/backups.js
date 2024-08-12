YUI().use('node', 'event-base', function(Y) {
    var toggleExpanded = function(node, expand) {
        if(!node) return;

        var methods = ['addClass', 'removeClass'];
        if(expand) methods.reverse();

        node.one('.header')[methods[1]]('corner');
        node.one('.description').toggleClass('expanded');
        node.one('.icon')[methods[0]]('fa fa-minus')[methods[1]]('fa fa-plus');
    };

    Y.one('.collapsible-list').delegate('click', function(e) {
        var item = e.currentTarget.ancestor('.item');
        toggleExpanded(item, item.one('.description').hasClass('expanded'));
    }, '.header');
});
