!function (doc, win) {
    var tabs = doc.createElement('div');
    tabs.id = 'mainTabs';

    tabs.innerHTML = ' \
    <ul class="yui3-tabview-list"> \
        <li class="yui3-tab yui3-widget"><a class="yui3-tab-label yui3-tab-content" href="#widgetVersions">tab 1</a></li> \
        <li class="yui3-tab yui3-widget"><a class="yui3-tab-label yui3-tab-content" href="#frameworkVersions">tab 2</a></li> \
        <li class="yui3-tab yui3-widget"><a class="yui3-tab-label yui3-tab-content" href="#history">tab 3</a></li> \
    </ul> \
    <div class="yui3-tabview-panel"> \
        <div id="widgetVersions"></div> \
        <div id="frameworkVersions"></div> \
        <div id="history"></div> \
    </div> \
    ';
    doc.body.appendChild(tabs);

    var div = doc.createElement('div');
    div.className = 'loading';
    doc.body.appendChild(div);

    var fixture = doc.createElement('button');
    fixture.id = 'viewOptions';
    doc.body.appendChild(fixture);

    widgetsFilterDropdown = doc.createElement('ul');
    widgetsFilterDropdown.id = 'widgetsFilterDropdown';
    widgetsFilterDropdown.innerHTML = ' \
            <li> \
                <ul> \
                    <li> \
                        <a data-filter-group="widgetType" data-filter-value="both" href="javascript:void(0);" class="selected">All</a> \
                    </li> \
                    <li> \
                        <a data-filter-group="widgetType" data-filter-value="custom" href="javascript:void(0);">Custom</a> \
                    </li> \
                    <li> \
                        <a data-filter-group="widgetType" data-filter-value="standard" href="javascript:void(0);">Standard</a> \
                    </li> \
                </ul> \
            </li> \
            <li> \
                <ul> \
                    <li> \
                        <a data-filter-group="widgetStatus" data-filter-value="all" href="javascript:void(0);" class="selected">All</a> \
                    </li> \
                    <li> \
                        <a data-filter-group="widgetStatus" data-filter-value="inuse" href="javascript:void(0);">Active</a> \
                    </li> \
                    <li> \
                        <a data-filter-group="widgetStatus" data-filter-value="notinuse" href="javascript:void(0);">Inactive</a> \
                    </li> \
                </ul> \
            </li> \
            <li> \
                <ul> \
                    <li> \
                        <a data-filter-group="widgetCategory" data-filter-value="any" href="javascript:void(0);" class="selected">Any</a> \
                    </li> \
                    <li> \
                        <a data-filter-group="widgetCategory" data-filter-value="category-6aabe1e3fd1e20a80438ddcadc565c44" href="javascript:void(0);" class="">Indian food</a> \
                    </li> \
                </ul> \
            </li> \
    ';
    doc.body.appendChild(widgetsFilterDropdown);

    var widgets = doc.createElement('div');
    widgets.id = 'widgets';
    widgets.innerHTML = ' \
<div data-type="standard" data-display-name="input/BasicTextInput" data-name="standard/input/BasicTextInput" class="listing-item widget inuse uptodate" role="option">stuff</div> \
<div data-type="standard" data-display-name="input/PotatoTextInput" data-name="standard/input/PotatoTextInput" class="listing-item widget disabled uptodate" role="option">stuff</div> \
<div data-type="custom" data-display-name="feedback/CustomAnswerFeedback" data-name="custom/feedback/CustomAnswerFeedback" class="listing-item widget inuse uptodate category-6aabe1e3fd1e20a80438ddcadc565c44" role="option">stuff</div> \
    ';
    doc.body.appendChild(widgets);

    win.hideShowPanel = function () {
        win.hideShowPanel.shown = !win.hideShowPanel.shown;
    };
}(document, window);

UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['Tabs', 'Filters'],
    preloadFiles: [
        '/euf/core/admin/js/versions/components/tabs.js',
        '/euf/core/admin/js/versions/components/component.js',
        '/euf/core/admin/js/versions/components/helpers.js',
        '/euf/core/admin/js/versions/components/filters.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Widget filters" });

    suite.add(new Y.Test.Case({
        name: "UI Behavior",

        "Dropdown panel shows": function () {
            Y.one('#viewOptions').simulate('click');
            Y.Assert.isTrue(window.hideShowPanel.shown);
        },

        "Clicked filter type is executed": function () {
            // all are visible by default
            Y.all('#widgets div').each(function(widgetDiv) {
                Y.Assert.isFalse(widgetDiv.hasClass('hide'));
            }, this);

            // test widget type
            Y.one('a[data-filter-value="custom"]').simulate('click');
            Y.all('#widgets div').each(function(widgetDiv) {
                if(widgetDiv.getAttribute('data-type') === 'standard')
                    Y.Assert.isTrue(widgetDiv.hasClass('hide'));
                if(widgetDiv.getAttribute('data-type') === 'custom')
                    Y.Assert.isFalse(widgetDiv.hasClass('hide'));
            }, this);
            Y.one('a[data-filter-value="both"]').simulate('click');

            // widgetStatus test
            Y.one('a[data-filter-value="notinuse"]').simulate('click');
            Y.all('#widgets div').each(function(widgetDiv) {
                if(widgetDiv.hasClass('disabled'))
                    Y.Assert.isFalse(widgetDiv.hasClass('hide'));
                else
                    Y.Assert.isTrue(widgetDiv.hasClass('hide'));
            }, this);
            Y.one('a[data-filter-value="all"]').simulate('click');

            // widgetCategory test
            Y.one('a[data-filter-value="category-6aabe1e3fd1e20a80438ddcadc565c44"]').simulate('click');
            Y.all('#widgets div').each(function(widgetDiv) {
                if(widgetDiv.hasClass('category-6aabe1e3fd1e20a80438ddcadc565c44'))
                    Y.Assert.isFalse(widgetDiv.hasClass('hide'));
                else
                    Y.Assert.isTrue(widgetDiv.hasClass('hide'));
            }, this);
        }
    }));

    return suite;
}).run();
