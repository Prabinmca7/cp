var messages = { levels: {}, lastCheckTime: '%s', displayType: { view: 'view', standard: 'standard', custom: 'custom' } };

!function (doc, win) {
    var widgetName = doc.createElement('div');
    widgetName.id = 'widgetName';
    doc.body.appendChild(widgetName);

    var controlsArea = doc.createElement('div');
    controlsArea.id = 'widgetVersions';
    controlsArea.innerHTML = ' \
    <div class="controls"> \
        <select id="updateVersion" name=""><option value="1.0" data-inuse="true">1.0&nbsp;(currently in use)</option><option value=""></option></select> \
        <a class="button disabled" href="javascript:void(0);" id="updateWidgetButton">Activate this version</a> \
    </div>';
    doc.body.appendChild(controlsArea);

    var widgets = doc.createElement('div');
    widgets.id = 'widgetPanel';
    widgets.innerHTML = ' \
        <div id="details"> \
            <div class="thumbnail"></div> \
        </div>';
    doc.body.appendChild(widgets);

    var tabs = doc.createElement('div');
    tabs.id = 'tabs';
    tabs.innerHTML = '<div class="views"></div>';
    doc.body.appendChild(tabs);

    widgets = doc.createElement('div');
    widgets.id = 'widgets';
    widgets.innerHTML = '<div class="listing-item hide" data-name="standard/utils/Blank"></div>';
    doc.body.appendChild(widgets);

    window.allWidgets = {
        'custom/feedback/CustomAnswerFeedback': {
            'category': ['cupcakes'],
            'versions': []
        }
    };
    window.messages = {
        'modeLabels': 'arbitrary',
        'levels': 'arbitrary',
        'lastCheckTime': 'yesterday',
        'displayType': 'arbitrary'
    }

}(document, window);

UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['WidgetPanel'],
    preloadFiles: [
        '/euf/core/admin/js/versions/components/tests/bootstrap.js',
        '/euf/core/admin/js/versions/components/tabs.js',
        '/euf/core/admin/js/versions/components/component.js',
        '/euf/core/admin/js/versions/components/helpers.js',
        '/euf/core/admin/js/versions/components/versionUpdater.js',
        '/euf/core/admin/js/versions/components/widgetDocDialog.js',
        '/euf/core/admin/js/versions/components/tooltip.js',
        '/euf/core/admin/js/versions/components/markdown.js',
        '/euf/core/admin/js/versions/components/versionHelper.js',
        '/euf/core/admin/js/versions/components/versionPanel.js',
        '/euf/core/admin/js/versions/components/previewImageDialog.js',
        '/euf/core/admin/js/versions/components/listFocusManager.js',
        '/euf/core/admin/js/versions/components/widgetPanel.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Widget panel" });

    suite.add(new Y.Test.Case({
        name: "Widget image preview thumbnail",

        setUp: function() {
            this.container = Y.one('#widgetPanel .thumbnail');
        },

        testCase: function () {
            this.fail("TBI");
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Widget sub-tabs",

        "Widget usage renders as expected": function () {
            Y.WidgetPanel.buildViewsUsedOnContent({
                references: {
                    "foo/bar/baz": "view",
                    "some/other/thing.php": "custom",
                    "bad/law": "standard"
                },
                lastCheckTime: 'yesterday'
            }, 'some/widget/path');

            Y.Assert.areSame(4, Y.one('#tabs .views').get('childNodes').size());
            Y.Assert.areSame('yesterday', Y.one('#tabs .views .updateTime').getHTML());

            Y.Assert.areSame(1, Y.one('.views .view').all('li').size());
            Y.Assert.areSame(1, Y.all('.views .view a').size());
            Y.Assert.areSame(1, Y.one('.views .custom').all('li').size());
            Y.Assert.areSame(2, Y.all('.views .custom a').size());
            Y.Assert.areSame(1, Y.one('.views .standard').all('li').size());
            Y.Assert.areSame(2, Y.all('.views .standard a').size());
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Widget documentation",

        testCase: function () {
            this.fail("TBI");
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Widget dependencies",

        "WidgetPanel item correctly has selected class and category displayed on mouse click": function () {
            var testButton1 = Y.all('.listing-item').item(1);
            testButton1.simulate('click');
            Y.Assert.isTrue(testButton1.hasClass('selected'));

            var categoryDisplay = Y.one('.category');
            Y.Assert.isFalse(categoryDisplay.hasClass('hide'));
            Y.Assert.isTrue(categoryDisplay.get('text').indexOf(window.allWidgets[testButton1.getData('name')].category[0]) > -1);
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Widget usage",

        testCase: function () {
            this.fail("TBI");
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Widget deactivation",

        testCase: function () {
            this.fail("TBI");
        }
    }));

    return suite;
}).run();
