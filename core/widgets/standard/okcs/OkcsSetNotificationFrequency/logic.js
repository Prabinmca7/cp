RightNow.Widgets.OkcsSetNotificationFrequency = RightNow.Widgets.extend({
    constructor: function() {
        this.data.js.subscriptionValue = '';
        var submitButton = this.Y.one(this.baseSelector + '_SubmitButton');
        this._selectedScheduleValue = this.Y.one(this.baseSelector + '_selectedScheduleValue');
            
        if(submitButton) {
            submitButton.on('click', this._onSubmitClick, this);
        }
        
        if(this._selectedScheduleValue) {
            this._selectedScheduleValue.on('change', this._onValueChange, this);
        }
    },
    
    /**
     * Method to be called on schedule selection
     */
    _onValueChange: function() {
        var selectedValue = this.Y.one(this.baseSelector + '_selectedScheduleValue'); 
        if(Number(this.data.js.selectedValue) === Number(selectedValue.get('value'))){
            this.Y.one(this.baseSelector + '_SubmitButton').set("disabled", true); 
        }else{
            this.Y.one(this.baseSelector + '_SubmitButton').set("disabled", false); 
        }            
    },
    
    /**
    * Event handler executed when the submit button is clicked
    * @param {Object} e Event
    */
    _onSubmitClick: function(e) {
        e.halt();
        var eventObject;
        var selectedValue = this.Y.one(this.baseSelector + '_selectedScheduleValue');        
        var subscriptionValue = selectedValue.get('value');
        this.data.js.subscriptionValue = subscriptionValue;
        if(subscriptionValue != null){
            eventObject = new RightNow.Event.EventObject(this, {data: {
                scheduleValue: subscriptionValue,
                action: 'SubscriptionSchedule'
            }});
            this.Y.one(this.baseSelector + '_SubmitButton').set("disabled", true);
            RightNow.Event.fire("evt_pageLoading");
            RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
                successHandler: this._subscriptionScheduleSuccessHandler,
                json: true, scope: this
            });
        }
    },
    /**
    * Displays Unsubscription message from the ajax response success.
    * @param response Object response.
    */
    _subscriptionScheduleSuccessHandler: function(response) {
        RightNow.Event.fire("evt_pageLoaded");
        if(response === "Success"){
            this.data.js.selectedValue = this.data.js.subscriptionValue;
            this.data.js.subscriptionValue = '';
            RightNow.UI.displayBanner(this.data.attrs.label_subscription_schedule_msg);
        }else{
            RightNow.UI.displayBanner(this.data.attrs.label_subscription_schedule_failure_msg, { type: 'ERROR', focusElement: this.__SubmitButton});
            this.Y.one(this.baseSelector + '_SubmitButton').set("disabled", false);
        }
    }
});