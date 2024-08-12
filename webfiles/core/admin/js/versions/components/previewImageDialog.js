/* global messages*/

/**
 * Displays a dialog with an image preview carousel.
 *
 *   new Y.PreviewImageDialog(Y.one('#widgetName').getHTML(), function () {
 *     e.currentTarget.focus();
 *   });
 */
YUI.add('PreviewImageDialog', function(Y) {
    "use strict";

    /**
     * Constructor.
     * @param {string} widgetPath    path of widget to display
     * @param {func=} closeCallback callback to execute when the dialog closes
     */
    function PreviewImageDialog (widgetPath, closeCallback) {
        this.widgetPath = widgetPath;
        this.fileNames = [];
        this.filesLoaded = 0;

        this.createDialog(closeCallback);
        this.requestImages();
    }

    PreviewImageDialog.sortBySize = function (a, b) {
        if (a < b) return 1;
        if (a > b) return -1;
        return 0;
    };

    PreviewImageDialog.prototype.createDialog = function (closeCallback) {
        this.dialog = new Y.Helpers.panel({
            headerContent: this.widgetPath,
            width: '', // empty-string = auto
            bodyContent: Y.Node.create('<div class="textcenter imageCarousel"/>'),
            closeCallback: closeCallback
        }).show();

        var node = this.dialog.get('srcNode');
        node.delegate('click', this.changeThumbnail, '.previous', this, 'previous');
        node.delegate('click', this.changeThumbnail, '.next', this, 'next');
    };

    /**
     * Creates the images and inserts them into the dialog.
     */
    PreviewImageDialog.prototype.insertImages = function () {
        var body = this.dialog.get('bodyContent').item(0),
            classNames = ['current', 'hide'];

        Y.Array.each(this.fileNames, function (fileName, index) {
            var node = Y.Node.create('<img alt="" class="imageSize" src="/ci/admin/docs/widgets/previewFile/' +  this.widgetPath + '/preview/' + fileName + '">');
            
            // First image is shown, subsequent images are hidden.
            node.addClass(classNames[index] || classNames[1]);

            body.append(node);
        }, this);
        
        this.dialog.centered();
    };
    
    /**
     * Inserts pagination links.
     */
    PreviewImageDialog.prototype.insertPagination = function () {
        if (this.fileNames.length <= 1) return;

        var previousImage = Y.Node.create('<a href="javascript:void(0);" role="button" class="imagePager previous" title="' + messages.previous + '"><i aria-hidden="true" role="presentation" class="fa fa-chevron-left"></i><span class="screenreader">' + messages.previous + '</span></a>');
        this.dialog.setStdModContent(Y.WidgetStdMod.BODY, previousImage, Y.WidgetStdMod.AFTER);

        var nextImage = Y.Node.create('<a href="javascript:void(0);" role="button" class="imagePager next" title="' + messages.next + '"><span class="screenreader">' + messages.next + '</span><i aria-hidden="true" role="presentation" class="fa fa-chevron-right"></i></a>');
        this.dialog.setStdModContent(Y.WidgetStdMod.BODY, nextImage, Y.WidgetStdMod.AFTER);
    };

    PreviewImageDialog.prototype.loadImages = function (fileNames) {
        this.fileNames = fileNames;
        this.insertImages();
        this.insertPagination();
    };

    /**
     * Get the manifest of images files for the widget.
     */
    PreviewImageDialog.prototype.requestImages = function () {
        Y.Helpers.ajax('/ci/admin/versions/getWidgetPics/' + encodeURIComponent(this.widgetPath), {
            callback: this.loadImages,
            context: this
        });
    };

    /**
     * Callback when a pagination link is clicked.
     * @param  {Object} e            click event
     * @param  {string} relationship previous|next
     */
    PreviewImageDialog.prototype.changeThumbnail = function (e, relationship) {
        var imageCarousel = e.currentTarget.get('parentNode').one('.imageCarousel'),
            img = imageCarousel.one('.current').removeClass('current').addClass('hide'),
            // What happens when the 'next' button is clicked but there are no more images?
            // Loop back around to the first one.
            loopAround = {
                next: 'firstChild',
                previous: 'lastChild'
            };

        (img[relationship]() || imageCarousel.get(loopAround[relationship])).addClass('current').removeClass('hide');
    };

    Y.PreviewImageDialog = PreviewImageDialog;

}, null, {
    requires: ['Helpers']
});
