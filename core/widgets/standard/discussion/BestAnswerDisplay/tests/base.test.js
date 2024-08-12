UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'BestAnswerDisplay_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/BestAnswerDisplay",
    });

    suite.add(new Y.Test.Case({
        name: "Base interaction tests",

        "Long best answer should be initially collapsed": function() {
            var baseNode = Y.one(baseSelector);

            Y.assert(baseNode.one('.rn_BestAnswerBody').hasClass('rn_CommentCollapsed'));
            Y.assert(!baseNode.one('.rn_ShowAllCommentText').hasClass('rn_Hidden'));
            Y.assert(baseNode.one('.rn_CollapseCommentText').hasClass('rn_Hidden'));
        },

        "Clicking Expand should open up full answer text": function() {
            var baseNode = Y.one(baseSelector);

            baseNode.one('.rn_ShowAllCommentText').simulate('click');
            Y.assert(!baseNode.one('.rn_BestAnswerBody').hasClass('rn_CommentCollapsed'));
            Y.assert(baseNode.one('.rn_ShowAllCommentText ').hasClass('rn_Hidden'));
            Y.assert(!baseNode.one('.rn_CollapseCommentText').hasClass('rn_Hidden'));
        },

        "Clicking Collapse should collapse answer text": function() {
            var baseNode = Y.one(baseSelector);

            baseNode.one('.rn_CollapseCommentText').simulate('click');
            Y.assert(baseNode.one('.rn_BestAnswerBody').hasClass('rn_CommentCollapsed'));
            Y.assert(!baseNode.one('.rn_ShowAllCommentText').hasClass('rn_Hidden'));
            Y.assert(baseNode.one('.rn_CollapseCommentText').hasClass('rn_Hidden'));
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Server response tests",

        "All links in the response html from the server are transformed": function() {
            RightNow.Url.setSession("session");
            var location = window.location,
                links = [
                    // Test cases are array `[input, expected]` or string if input is expected to match the result.
                    'http://ducksarethebest.com/',
                    [ '#grace', location.pathname + '#grace' ],
                    'fall.html',
                    'app/lavender',
                    [ '/app', RightNow.Url.addParameter(location.protocol + '//' + location.host + '/app/home', 'session', RightNow.Url.getSession()) ],
                    ''
                ],
                html = '<ul>' + Y.Array.map(links, function (link) {
                    link = Y.Lang.isArray(link) ? link[0] : link;
                    return '<li><a href="' + link + '">' + link + '</a></li>';
                }).join('') + '</ul>';

            widget._replaceCurrentContent(html);

            var domLinks = Y.all('ul.rn_Refreshed a');

            Y.Assert.areSame(links.length, domLinks.size());

            domLinks.each(function (link, i) {
                var actual = link.getAttribute('href'),
                    expected = links[i];

                expected = Y.Lang.isArray(expected) ? expected[1] : expected;

                Y.Assert.areSame(expected, actual);
            });
        }
    }));

    return suite;
}).run();
