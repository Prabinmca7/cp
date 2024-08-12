/*global messages*/
YUI().use('node', 'panel', 'json-parse', function(Y) {
    var fieldDetailsTemplate = Y.one('#fieldDetails').getHTML(),
        detailDialog = new Y.Panel({
            contentBox: Y.one('#businessObjectDetailsDialog'),
            width: '480px',
            centered: true,
            visible: false,
            modal: true,
            constraintoviewport: true,
            buttons: [{
                value: messages.close_lbl,
                section: Y.WidgetStdMod.FOOTER,
                action: function() { this.get("fixScroll")(); },
                classNames: 'cancelButton'
            }, {
                value: '\u00D7',
                section: Y.WidgetStdMod.HEADER,
                action: function() { this.get("fixScroll")(); }
            }]
        });

    /**
     * Gets the 'on create', 'on update', 'on create, on update', or 'false' label
     * depending on the combination of create / update flags.
     * @param  {bool} create Whether the item is True for the create operation
     * @param  {bool} update Whether the item is True for the update operation
     * @return {string} Label depending on the combination of create / update flags
     */
    function getCreateOrUpdateLabel (create, update) {
        getCreateOrUpdateLabel.labels || (getCreateOrUpdateLabel.labels = [
            messages.false_lbl,     // 0, 0
            messages.on_create_lbl, // 1, 0
            messages.on_update_lbl, // 0, 1
            [messages.on_create_lbl, messages.on_update_lbl].join(', ') // 1, 1
        ]);

        return getCreateOrUpdateLabel.labels[(create << 0) | (update << 1)];
    }

    function scrollToAnchor (e) {
        e.halt();

        var targetElement = Y.one('#' + e.currentTarget.getData('object-name'));
        if (targetElement) {
            window.scrollTo(targetElement.getX(), targetElement.getY());
        }
    }

    function displayDialog (e) {
        e.halt();
        // replace &apos; with ' for IE7/8
        var clickedElement = document.activeElement.getAttribute('id');
        var currentX = window.pageXOffset;
        var currentY = window.pageYOffset;
        var metaData = Y.JSON.parse(e.currentTarget.getData('meta-data').replace(/&apos;/g, "'"));

        detailDialog.set('headerContent', e.currentTarget.getHTML())
            .set('bodyContent', new EJS({text: fieldDetailsTemplate}).render({
                metaData:         metaData,
                readOnly:         getCreateOrUpdateLabel(metaData.is_read_only_for_create, metaData.is_read_only_for_update),
                required:         getCreateOrUpdateLabel(metaData.is_required_for_create, metaData.is_required_for_update),
                constraintLabels: Y.JSON.parse(Y.one('#fieldDetails').getData('constraint-labels')),
                namedValues:      Y.JSON.parse(e.currentTarget.getData('named-values')),
                objectType:       e.currentTarget.getData('object-name'),
                escapeHtml:       Y.Escape.html
            })).set("fixScroll", function() {
                detailDialog.hide();
                document.getElementById(clickedElement).focus();
            }).render().show();

        window.scrollTo(currentX, currentY);
        detailDialog.set('center', true);
        detailDialog.hide().show();
    }

    function fixScroll(clickedElement) {
        detailDialog.hide();
        document.getElementById(clickedElement).focus();
    }

    Y.one('#anchors').delegate('click', scrollToAnchor, 'a');
    Y.one(document.body).delegate('click', displayDialog, '.businessObject a');
});