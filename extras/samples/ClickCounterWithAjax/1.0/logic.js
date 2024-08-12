/**
 * File: logic.js
 * Abstract: Logic for ClickCounterWithAjax widget
 * Version: 1.0
 */
RightNow.namespace('Custom.Widgets.sample.ClickCounterWithAjax');
Custom.Widgets.sample.ClickCounterWithAjax = RightNow.Widgets.extend({ 
    /**
     * Widget constructor.
     */
    constructor: function() {
        this._message = this.Y.one(this.baseSelector + '_Message');
        this._button = this.Y.one(this.baseSelector + '_Button');
        this._numTimes = 0;
        // don't bother processing anything if the message or button
        // elements cannot be found
        if (!this._message || !this._button)
            return;

        this._button.on('click', this.getUpdate_message_endpoint, this);
    },

    /**
     * Makes an AJAX request for `update_message_endpoint`.
     */
    getUpdate_message_endpoint: function() {
        // Make AJAX request and include the number of times that the button has been clicked
        this._numTimes++;
        var eventObj = new RightNow.Event.EventObject(this, {data:{
            w_id: this.data.info.w_id,
            numTimes: this._numTimes
        }});
        RightNow.Ajax.makeRequest(this.data.attrs.update_message_endpoint, eventObj.data, {
            successHandler: this.update_message_endpointCallback,
            scope:          this,
            data:           eventObj,
            json:           true
        });
    },

    /**
     * Handles the AJAX response for `update_message_endpoint`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from #getUpdate_message_endpoint
     */
    update_message_endpointCallback: function(response, originalEventObj) {
        if (response)
            this.renderView(response);
    },

    /**
     * Renders the `view.ejs` JavaScript template.
     * @param {object} response JSON-parsed response from the server
     */
    renderView: function(response) {
        var content = new EJS({text: this.getStatic().templates.view}).render({
            message: response.message,
            spanClass: response.spanClass
        });
        this._message.set('innerHTML', content);
    }
});