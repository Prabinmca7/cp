/**
 * File: logic.js
 * Abstract: Extending logic for PollingSlider widget
 * Version: 1.0
 */
RightNow.namespace('Custom.Widgets.surveys.PollingSlider');
Custom.Widgets.surveys.PollingSlider = RightNow.Widgets.Polling.extend({
    overrides: {
        /**
         * Overrides RightNow.Widgets.Polling#constructor
         */
        constructor: function() {
            // Call into parent's constructor
            this.parent();

            this._sliderNode = this.Y.one(this.baseSelector);
            this._pollTitleNode = this.Y.one(this.baseSelector + "_PollTitle");
            // Calculate the offset to use for the slider drawer in the closed position
            this._closedDrawerOffset = this._pollTitleNode.get('offsetHeight') - this._sliderNode.get('offsetHeight') - 1;

            // Start with the drawer closed
            this._sliderNode.setStyle('bottom', this._closedDrawerOffset + "px");
            this._drawerOpen = false;

            this._pollTitleNode.on('click', this._toggleDrawer, this);

            // If the user has already taken the survey, hide the slider entirely
            if(this.data.js.cookied_questionID > 0) {
                this._sliderNode.hide();
            }
        },

        /**
         * Overrides RightNow.Widgets.Polling#_showChart
         */
        _showChart: function(jsonString, totalVotes) {
            // Call parent's _showChart
            this.parent(jsonString, totalVotes);

            // If we have a poll title and we are attempting to show the chart inside the slider drawer
            if(this._pollTitleNode) {
                // Change the title click to permenantly close the drawer
                this._pollTitleNode.detach('click', this._toggleDrawer);
                this._pollTitleNode.on('click', this._closeDrawerPermenantly, this);
            }
        }
    },

    // Opens and closes the slider drawer
    _toggleDrawer: function() {
        this._sliderNode.transition({
            duration: 1,
            bottom: (this._drawerOpen) ? this._closedDrawerOffset + "px" : "0px"
        });
        // Toggle the drawer open flag
        this._drawerOpen = !this._drawerOpen;
    },

    // Closes the drawer permenantly (all the way off the screen)
    _closeDrawerPermenantly: function() {
        this._sliderNode.transition({
            duration: 1,
            bottom: this._sliderNode.get('offsetHeight') * -1 + "px"
        });
        this._drawerOpen = false;
    }

});