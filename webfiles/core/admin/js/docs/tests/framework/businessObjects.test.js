UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['json-parse'],
    preloadFiles: [
        '/euf/core/ejs/1.0/ejs.js',
        '/euf/core/admin/js/docs/businessObjects.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Business objects JS functionality" });

    suite.add(new Y.Test.Case({
        name: "Behavior",

        setUp: function () {
            this.firstField = Y.one('.businessObject').one('a');
        },

        getFirstFieldMetaData: function () {
            return Y.JSON.parse(this.firstField.getData('meta-data'));
        },

        setFirstFieldMetaData: function (metaData) {
            this.firstField.setAttribute('data-meta-data', JSON.stringify(metaData));
        },

        setFirstFieldObjectName: function (name) {
            this.firstField.setAttribute('data-object-name', name);
        },

        getDialogText: function () {
            return Y.one('#businessObjectDetails').get('text').replace(/\s{2,}/g, ' ');
        },

        closeDialog: function() {
            var button = Y.one('#businessObjectDetailsDialog button');
            if (button) {
                button.simulate('click');
            }
        },

        getScrollTop: function() {
            return document.documentElement.scrollTop||document.body.scrollTop;
        },

        "Field's description is escaped": function () {
            var metaData = this.getFirstFieldMetaData();

            metaData.description += '<flame>';
            var expected = metaData.description;

            this.setFirstFieldMetaData(metaData);

            this.firstField.simulate('click');

            Y.Assert.isTrue(this.getDialogText().indexOf(expected) > -1);
        },

        "Created label appears for readOnly property": function () {
            this.setFirstFieldObjectName('Open');
            var metaData = this.getFirstFieldMetaData();

            metaData.is_read_only_for_create = true;
            metaData.is_read_only_for_update = false;

            this.setFirstFieldMetaData(metaData);

            this.firstField.simulate('click');

            Y.Assert.isTrue(this.getDialogText().indexOf('Read Only ' + window.messages.on_create_lbl) > -1);
        },

        "Updated label appears for readOnly property": function () {
            this.setFirstFieldObjectName('Open');
            var metaData = this.getFirstFieldMetaData();

            metaData.is_read_only_for_create = false;
            metaData.is_read_only_for_update = true;

            this.setFirstFieldMetaData(metaData);

            this.firstField.simulate('click');

            Y.Assert.isTrue(this.getDialogText().indexOf('Read Only ' + window.messages.on_update_lbl) > -1);
        },

        "Created and Updated labels appear for readOnly property": function () {
            this.setFirstFieldObjectName('Open');
            var metaData = this.getFirstFieldMetaData();

            metaData.is_read_only_for_create = true;
            metaData.is_read_only_for_update = true;

            this.setFirstFieldMetaData(metaData);

            this.firstField.simulate('click');

            Y.Assert.isTrue(this.getDialogText().indexOf('Read Only ' + window.messages.on_create_lbl + ', ' + window.messages.on_update_lbl) > -1);
        },

        "False label appears for readOnly property": function () {
            this.setFirstFieldObjectName('Open');
            var metaData = this.getFirstFieldMetaData();

            metaData.is_read_only_for_create = false;
            metaData.is_read_only_for_update = false;

            this.setFirstFieldMetaData(metaData);

            this.firstField.simulate('click');

            Y.Assert.isTrue(this.getDialogText().indexOf('Read Only ' + window.messages.false_lbl) > -1);
        },

        "Clicking an object scrolls to the corresponding section": function () {
            var incidentLink = Y.one('#anchors a[data-object-name="incident"]'),
                incidentHeader = Y.one('h3#incident'),
                incidentHeaderPosition = Math.round(incidentHeader.getY());


            Y.Assert.areSame(0, this.getScrollTop());
            incidentLink.simulate('click');
            Y.Assert.areSame(incidentHeaderPosition - 1, this.getScrollTop());
            window.scrollTo(0, 0);
        },

        "Displaying a dialog does not scroll the underlying page": function () {
            var incidentLink = Y.one('#anchors a[data-object-name="incident"]'),
                incidentHeader = Y.one('h3#incident'),
                incidentHeaderPosition = Math.round(incidentHeader.getY()),
                IncidentFieldLink = Y.one('a[data-object-name="Incident"]');

            incidentLink.simulate('click');
            var currentPosition = this.getScrollTop();
            Y.Assert.areSame(incidentHeaderPosition - 1, currentPosition, 'Scroll to section did not happen');
            IncidentFieldLink.simulate('click');
            Y.Assert.areSame(currentPosition, this.getScrollTop(), 'Opening dialog caused page to scroll');
            this.closeDialog();
            Y.Assert.areSame(currentPosition, this.getScrollTop(), 'Closing dialog caused page to scroll');
            window.scrollTo(0, 0);
        }
    }));

    return suite;
}).run();
