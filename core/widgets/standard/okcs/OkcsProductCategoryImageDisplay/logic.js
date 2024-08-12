RightNow.Widgets.OkcsProductCategoryImageDisplay = RightNow.Widgets.extend({
    constructor: function() {
        var img = this.Y.Node.create('<img />');
        this.Y.one(this.baseSelector + ' .rn_ProductImageContainer').setHTML(img);
        this._loadImage(img);
    },

    /**
     * Attempt to load product image. If there is an error, falls back to default image.
     * @param {Object} img YUI node
     */
    _loadImage: function(img) {
        img.on('error', function() {
            img.setAttrs(
                {
                    'src': this.data.attrs.image_path + '/default.png',
                    'alt': this.data.attrs.label_default_image_alt_text
                }
            );
        }, this);

        img.setAttrs(
            {
                'src': this.data.attrs.image_path + '/' + this.data.js.slug + '.png',
                'alt': (this.data.attrs.label_image_alt_text.indexOf('%s') > -1)
                       ? RightNow.Text.sprintf(this.data.attrs.label_image_alt_text, this.data.js.slug.replace(/[-_]/g, ' '))
                       : this.data.attrs.label_image_alt_text
            }
        );
    }
});
