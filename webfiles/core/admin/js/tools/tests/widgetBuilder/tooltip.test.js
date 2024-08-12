UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    preloadFiles: [
        '/euf/core/admin/js/tools/widgetBuilder/tooltip.js',
    ],
    yuiModules: ['tip-it', 'node-event-simulate']
}, function(Y) {
    var suite = new Y.Test.Suite({name: 'Tip-It'});

    suite.add(new Y.Test.Case({
        name: 'Tip-It Tests',

        'Test container addition to DOM': function() {
            Y.Assert.isNotNull(Y.one('div.tooltip'));
            Y.Assert.areSame('', Y.one('div.tooltip').get('innerHTML'));
        },

        'Test adding nodes to DOM': function(){
            var node = Y.Node.create('<a href="http://www.google.com"><sup title="linky">tooltip</sup></a>'),
                tooltipDiv = Y.one('div.tooltip');
            Y.one(document.body).append(node);
            Y.TipIt(node);
            node.simulate("mouseover");

            //Check that the tooltip div has been updated
            Y.Assert.isTrue(tooltipDiv.hasClass('active'));
            Y.Assert.areSame('linky<div class="arrow"></div>', tooltipDiv.get('innerHTML'));
            Y.Assert.isTrue(parseInt(tooltipDiv.getStyle('top'), 10) >= 0);
            Y.Assert.isTrue(parseInt(tooltipDiv.getStyle('left'), 10) >= 0);

            //Check that the title has been moved to data attribute
            Y.Assert.areSame('', node.getAttribute('title'));
            Y.Assert.areSame('linky', node.one('sup').getAttribute('data-tooltip'));

            //Check changes after mouseout
            node.simulate('mouseout');
            Y.Assert.isFalse(tooltipDiv.hasClass('active'));
            Y.Assert.areSame("-10000px", tooltipDiv.getStyle('top'));
            Y.Assert.areSame("-10000px", tooltipDiv.getStyle('left'));

            node.remove();
        },

        'Test with node that has data attribute': function(){
            var childNode = Y.Node.create('<sup>tooltip element</sup>'),
                parentNode = Y.Node.create('<a href="http://www.google.com" data-tooltip="tooltip in data attr">linky</a>'),
                tooltipDiv = Y.one('div.tooltip');
            parentNode.append(childNode);
            Y.one(document.body).append(parentNode);
            Y.TipIt(parentNode);
            childNode.simulate("mouseover");

            //Check that the tooltip div has been updated
            Y.Assert.areSame('tooltip in data attr<div class="arrow"></div>', tooltipDiv.get('innerHTML'));

            //Check that links title has been moved to data attribute
            Y.Assert.areSame('', parentNode.getAttribute('title'));
            Y.Assert.areSame('tooltip in data attr', parentNode.getAttribute('data-tooltip'));
            Y.Assert.areSame('', childNode.getAttribute('title'));
            Y.Assert.areSame('', childNode.getAttribute('data-tooltip'));

            childNode.simulate('mouseout');
            parentNode.remove();
        },

        'Tooltip should be shown on focus event': function() {
            var node = Y.Node.create('<a href="http://www.google.com"><sup title="linky">tooltip</sup></a>'),
                tooltipDiv = Y.one('div.tooltip');
            Y.one(document.body).append(node);
            Y.TipIt(node);
            node.simulate('focus');

            //Check that the tooltip div has been updated
            Y.Assert.isTrue(tooltipDiv.hasClass('active'));
            Y.Assert.areSame('linky<div class="arrow"></div>', tooltipDiv.get('innerHTML'));
            Y.Assert.isTrue(parseInt(tooltipDiv.getStyle('top'), 10) >= 0);
            Y.Assert.isTrue(parseInt(tooltipDiv.getStyle('left'), 10) >= 0);

            //Check that the title has been moved to data attribute
            Y.Assert.areSame('', node.getAttribute('title'));
            Y.Assert.areSame('linky', node.one('sup').getAttribute('data-tooltip'));

            //Check changes after blur
            node.simulate('blur');
            Y.Assert.isFalse(tooltipDiv.hasClass('active'));
            Y.Assert.areSame("-10000px", tooltipDiv.getStyle('top'));
            Y.Assert.areSame("-10000px", tooltipDiv.getStyle('left'));

            node.remove();
        }
    }));
    return suite;
}).run();


