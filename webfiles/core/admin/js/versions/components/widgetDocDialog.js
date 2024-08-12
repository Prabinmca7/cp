/* global messages*/

/**
 * Displays a dialog with the widget's documentation.
 *
 *   new Y.WidgetDocDialog(Y.one('#widgetName', widgetVersion).getHTML(), function () {
 *     e.currentTarget.focus();
 *   });
 */
YUI.add('WidgetDocDialog', function(Y) {
    "use strict";

    /**
     * Constructor.
     * @param {string} widgetName    path of widget to display (e.g. standard/utils/Blank)
     * @param {string} version       Major.Minor version number
     * @param {func=} closeCallback callback to execute when the dialog closes
     */
    function WidgetDocDialog (widgetName, version, closeCallback) {
        this.widgetName = widgetName;
        this.version = version;

        if (this.version) {
            this.version = Y.Escape.html(this.version);
        }

        this.show(closeCallback);
    }

    WidgetDocDialog.prototype.show = function (closeCallback) {
        this.dialog = Y.Helpers.panel({
            y: 100,
            width: '90%',
            buttons: this.getButtons(),
            bodyContent: WidgetDocDialog.templates.waiting,
            closeCallback: closeCallback,
            headerContent: this.getHeaderText(),
            destroyOnClose: true
        });

        this.dialog.show();

        var container = this.dialog.getStdModNode(Y.WidgetStdMod.BODY);

        container.delegate('click', this._onAccordionClick, 'h2, .bucketName, .attributeToggle');
        container.delegate('click', this._onLinkClick, 'a', this);

        this.requestDocContent();
    };

    WidgetDocDialog.prototype.requestDocContent = function () {
        Y.Helpers.ajax('/ci/admin/versions/getWidgetDocs/' + encodeURIComponent(this.widgetName) + '/' + encodeURIComponent(this.version), {
            raw: true,
            context: this,
            callback: this._onDocContentReceived
        });
    };

    WidgetDocDialog.prototype.getHeaderText = function () {
        return this.widgetName.split('/').pop() + ' - ' + this.version;
    };

    WidgetDocDialog.prototype.getButtons = function () {
        return [{
            template: '<button><i aria-hidden="true" role="presentation" class="fa fa-print"></i> ' + messages.print + '</button>',
            section: Y.WidgetStdMod.FOOTER,
            action: this._onPrintClick,
            context: this,
            classNames: 'printButton'
        }];
    };

    WidgetDocDialog.prototype._onPrintClick = function (e) {
        e.halt();
        var print = window.open("", "", "resizable=yes,scrollbars=yes,statusbar=no,location=no"),
            header = this.getHeaderText();
        print.document.write('<title>' + header + '</title><h1>' + header + '</h2>' + this.dialog.get('bodyContent').getHTML());
        print.document.close();
        print.focus();
        print.print();
        print.close();
    };

    WidgetDocDialog.prototype._onAccordionClick = function (e) {
        e.currentTarget.toggleClass('selected').next().toggleClass('hide');
    };

    WidgetDocDialog.prototype._onLinkClick = function (e) {
        if (e.currentTarget.get('href').indexOf('javascript:') === -1) {
            //Close the dialog since some links are same-page and won't reload the browser
            this.dialog.hide();
        }
    };

    WidgetDocDialog.prototype._onDocContentReceived = function (resp) {
        this.dialog.set('bodyContent', '<div class="widgetDetails">' + resp + '</div>').centered();
    };

    WidgetDocDialog.prototype._resizeToFit = function () {
        var contentHeight = parseInt(this.dialog.getStdModNode(Y.WidgetStdMod.BODY).getComputedStyle('height'), 10),
            windowHeight = parseInt(Y.one('body').get('winHeight'), 10);

        if (contentHeight > windowHeight) {
            Y.one('.widgetDetails').setStyle('height', (windowHeight - 140) + 'px');
        }
    };

    WidgetDocDialog.templates = {
        waiting: '<div class="bigwait"></div>'
    };

    Y.WidgetDocDialog = WidgetDocDialog;

}, null, {
    requires: ['Helpers']
});
