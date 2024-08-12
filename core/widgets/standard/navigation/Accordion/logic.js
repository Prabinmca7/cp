RightNow.Widgets.Accordion = RightNow.Widgets.extend({
    constructor: function() {
        var toggle = this.Y.one('#' + this.data.attrs.toggle);
        this._itemToToggle = this.Y.one('#' + this.data.attrs.item_to_toggle);
        if(toggle) {
            if(!this._itemToToggle) {
                var current = toggle.next();
                if(current)
                    this._itemToToggle = current;
                else
                    return;
            }
            this._currentlyShowing = toggle.hasClass(this.data.attrs.expanded_css_class) ||
                this._itemToToggle.getComputedStyle("display") !== "none";

            //trick to get voiceover to announce state to screen readers.
            this._screenReaderMessageCarrier = toggle.appendChild(this.Y.Node.create(
                "<img style='opacity: 0;' src='/euf/core/static/whitePixel.png' alt='" +
                    (this._currentlyShowing ? this.data.attrs.label_expanded : this.data.attrs.label_collapsed) + "'/>"));
            toggle.on("click", this._onClick, this);
        }
    },

    /**
    * Toggles the display of the element.
    * @param clickEvent Event Click event
    */
    _onClick: function(clickEvent) {
        var focusItem = (this.data.attrs.focus_item_on_open_selector) ? this._itemToToggle.all(this.data.attrs.focus_item_on_open_selector).item(0) : null,
            target = clickEvent.target, cssClassToAdd, cssClassToRemove;
        if(this._currentlyShowing) {
            cssClassToAdd = this.data.attrs.collapsed_css_class;
            cssClassToRemove = this.data.attrs.expanded_css_class;
            this._itemToToggle.setStyle("display", "none");
            this._screenReaderMessageCarrier.set("alt", this.data.attrs.label_collapsed);
        }
        else {
            cssClassToAdd = this.data.attrs.expanded_css_class;
            cssClassToRemove = this.data.attrs.collapsed_css_class;
            this._itemToToggle.setStyle("display", "block");
            this._screenReaderMessageCarrier.set("alt", this.data.attrs.label_expanded);
        }
        if(target) {
            target.addClass(cssClassToAdd)
                    .removeClass(cssClassToRemove);
        }
        this._currentlyShowing = !this._currentlyShowing;
        if(this._currentlyShowing && focusItem) {
            focusItem.focus();
        }
    }
});
