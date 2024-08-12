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

    var widgets = doc.createElement('div');
    widgets.innerHTML = ' \
    <div id="widgets" class="listing" role="listbox"> \
        <div role="option" class="listing-item widget inuse uptodate" data-name="custom/feedback/CustomAnswerFeedback" data-display-name="feedback/CustomAnswerFeedback" data-type="custom"> \
            <div class="main"> \
                <div class="title">feedback/CustomAnswerFeedback</div> \
                <div class="meta">custom</div> \
            </div> \
            <div class="hide"> \
                <a class="thumbnail" href="javascript:void(0);" tabindex="0"></a> \
                <div class="timeline"> \
                    <div class="notice">This is the newest version</div> \
                    <div class="label hide" data-version="1.0" data-framework="" data-inuse="Staging, Production, Development">1.0</div> \
                </div> \
                <div class="category hide"><b></b></div> \
                <div class="versions"></div> \
                <div class="views"></div> \
                <div class="changes"></div> \
            </div> \
        </div> \
        <div role="option" class="listing-item widget inuse uptodate" data-name="custom/feedback/ExtendedCustomAnswerFeedback" data-display-name="feedback/ExtendedCustomAnswerFeedback" data-type="custom"> \
            <div class="main"> \
                <div class="title">feedback/ExtendedCustomAnswerFeedback</div> \
                <div class="meta">custom</div> \
            </div> \
            <div class="hide"> \
                <a class="thumbnail" href="javascript:void(0);" tabindex="0"></a> \
                <div class="timeline"> \
                    <div class="notice">This is the newest version</div> \
                    <div class="label hide" data-version="1.0" data-framework="" data-inuse="Staging, Production, Development">1.0</div> \
                </div> \
                <div class="category hide"><b></b></div> \
                <div class="versions"></div> \
                <div class="views"></div> \
                <div class="changes"></div> \
            </div> \
        </div> \
    </div>';
    doc.body.appendChild(widgets);


    var div = doc.createElement('div');
    div.className = 'loading';
    doc.body.appendChild(div);
}(document, window);
