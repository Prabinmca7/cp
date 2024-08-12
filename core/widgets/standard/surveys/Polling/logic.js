RightNow.Widgets.Polling = RightNow.Widgets.extend({
    constructor: function() {
        this._state = 0;
        this._submitInput = this.Y.one(this.baseSelector + "_Submit");
        this._chatID = 0;

        if (this.data.js.disabled_expired)
            return;

        if (this.data.attrs.modal && this.data.attrs.poll_logic)
            RightNow.Event.subscribe("evt_showPoll", this._onShowPoll, this);

        if (this.data.attrs.modal && this.data.attrs.frequency && Math.floor(Math.random() * 101) > this.data.attrs.frequency)
            return;

        if (!this.data.attrs.admin_console && this.data.js.cookied_questionID > 0) {
            if (this.data.attrs.modal) {
                // do not pop the poll because the user has already answered this poll
                return;
            }
            else {
                // go straight to results
                if (this.data.js.question_type === "choice" && this.data.js.show_chart && this.data.attrs.chart_type !== "none")
                    this._showChart(this.data.js.question_results, this.data.js.total);
                else
                    this._showThankYou();
                return;
            }
        }

        var viewResultsLink = this.Y.one(this.baseSelector + "_ViewResultsLink");
        if(!this.data.attrs.admin_console && !this.data.attrs.modal && viewResultsLink)
            viewResultsLink.on('click', this._viewResultsLinkClicked, this);

        if (!this.data.attrs.modal && this._submitInput)
            this._submitInput.set('disabled', false);

        var viewResultsDiv = this.Y.one(this.baseSelector + "_ViewResults");
        RightNow.UI.show(viewResultsDiv);

        if (this.data.attrs.modal && !this.data.attrs.poll_logic) {
            this._dialog = null;

            if (this.data.attrs.seconds > 0)
                this.Y.Lang.later(this.data.attrs.seconds * 1000, this, '_showDialog');
            else
                this._showDialog();
        }
        else if (!this.data.attrs.modal) {
            RightNow.UI.show(this.baseSelector);
            if(this._submitInput && !this.data.attrs.admin_console)
                this._submitInput.on("click", this._submitButtonClicked, this);
        }

        RightNow.Event.subscribe("evt_chatEngagementParticipantAddedResponse", this._chatEngagementParticipantAddedResponse, this);

        RightNow.ActionCapture.record('polling', 'showQuestion', this.data.js.question_id + '');
        RightNow.ActionCapture.record('polling', 'showFlow', this.data.js.flow_id + '');
    },

    /**
     * Shows an actionDialog for the modal case
     */
    _showDialog: function() {
        // set cookie on offer for modal mode, but don't set cookie for an admin preview
        if (!this.data.attrs.admin_console)
            this._setCookie();

        this._dialog = RightNow.UI.Dialog.actionDialog(this.data.js.title, this.Y.one(this.baseSelector),
            {"buttons": [{text: this.data.js.submit_button_label, handler: {fn: this._actionDialogButtonClicked, scope: this}, isDefault: true}],
            "width": this.data.attrs.dialog_width,
            "dialogDescription": this.baseDomID + "_PollQuestion"}
        );

        this._dialog.hideEvent.subscribe(function(){
            this.Y.one(this.baseSelector + "_FlipArea").addClass('rn_Hidden');
        }, null, this);

        RightNow.UI.show(this.baseSelector);
        this._dialog.show();
        this._dialog.enableButtons();

        var questionDiv = this.Y.one(this.baseSelector + '_PollQuestion');
        if (questionDiv) {
            questionDiv.set('tabIndex', -1).focus();
        }

        if(!this.data.attrs.admin_console){
            var resultsLink = this.Y.one(this.baseSelector + '_ViewResultsLink');
            if(resultsLink){
                resultsLink.on('click', this._viewResultsLinkClicked, this);
            }
        }
    },

    /**
     * Listener for when the submit button (modal) is clicked
     */
    _actionDialogButtonClicked: function() {
        if (this.data.attrs.admin_console)
            return false;

        this._dialog.disableButtons();
        if (this._state === 0) {
            if (!this._onSubmit()) {
                this._dialog.enableButtons();
                return false;
            }
        }
        else {
            this._dialog.hide();
        }

        this._state = 1;
    },

    /**
     * Listener for when the submit button (non modal) is clicked
     */
    _submitButtonClicked: function(e) {
        this._submitInput.set('disabled', true);
        if (this._state === 0) {
            if (!this._onSubmit()) {
                this._submitInput.set('disabled', false);
                return false;
            }
        }
        this._state = 1;
    },

    /**
     * Listener for when the view results link is clicked
     */
    _viewResultsLinkClicked: function() {
        if (this._dialog)
            this._dialog.disableButtons();
        else if (this._submitInput)
            this._submitInput.set('disabled', true);
        if (this._state === 0) {
            this._getPollResults(this.data.js.flow_id, this.data.js.question_id);
        }
    },

    /**
     * Fires a pollSubmitRequest to get the poll results without submitting any data
     */
    _getPollResults: function(flow_id, question_id) {
        var eventObject = new RightNow.Event.EventObject(this, {data: {
                   flow_id: flow_id,
                   question_id: question_id,
                   test: this.data.attrs.test,
                   results_only: true,
                   url: window.location.pathname,
                   w_id: this.data.info.w_id
        }});
    
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each ajax request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eventObject.data.f_tok = this.data.js.f_tok;
        }

        if (RightNow.Event.fire("evt_pollSubmitRequest", eventObject)){
             RightNow.Ajax.makeRequest(this.data.attrs.submit_poll_ajax,
                                       eventObject.data,
                                       {successHandler: this._onResponseReceived,
                                       scope: this, data: eventObject, json: true});
        }
        this._state = 1;
    },

    /**
     * Submits the poll
     * @return Bool true if the submission event was fired, false otherwise
     */
    _onSubmit: function() {
        var formElement = document.getElementById(this.baseDomID + "_QuestionForm");
        if (this._validate(formElement)) {
            var responses = [];
            for (var i = 0; i < formElement.length; i++) {
                var element = formElement[i];
                if (element.tagName === "SELECT") {
                    var elementNode = this.Y.one('#' + element.id);
                    responses.push({
                        id: element.id,
                        response: elementNode.get("options").get("value")[elementNode.get("selectedIndex")]
                    });
                }
                else if (element.tagName === "INPUT") {
                    if (element.type === "radio" || element.type === "checkbox") {
                        if (element.checked){
                            responses.push({id: element.name, response: element.value});
                        }
                    }
                    else if (element.type === "text") {
                        responses.push({id: element.name, response: element.value});
                    }
                }
                else if (element.tagName === "TEXTAREA") {
                    responses.push({id: element.name, response: element.value});
                }
            }

            var questionType = 0;
            switch (this.data.js.question_type) {
                case "text":
                    questionType = 1;
                    break;
                case "choice":
                    questionType = 2;
                    break;
                case "matrix":
                    questionType = 3;
                    break;
            }

            var eventObject = new RightNow.Event.EventObject(this, {data: {
                    survey_id: this.data.attrs.survey_id,
                    flow_id: this.data.js.flow_id,
                    question_id: this.data.js.question_id,
                    include_results: true,
                    url: window.location.pathname,
                    responses: RightNow.JSON.stringify(responses),
                    doc_id: this.data.js.doc_id,
                    test: this.data.attrs.test,
                    question_type: questionType,
                    location: window.location.pathname,
                    chart_type: this.data.attrs.chart_type,
                    w_id: this.data.info.w_id,
                    i_id: RightNow.Url.getParameter('i_id'),
                    chat_id: this._chatID
            }});
        
            if(this.data.js.f_tok){
                RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
                // Get a new f_tok value on each ajax request
                RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

                eventObject.data.f_tok = this.data.js.f_tok;
            }

            if (RightNow.Event.fire("evt_pollSubmitRequest", eventObject)){
                 RightNow.Ajax.makeRequest(this.data.attrs.submit_poll_ajax,
                                           eventObject.data,
                                           {successHandler: this._onResponseReceived,
                                           scope: this, data: eventObject, json: true});
           }
            // set cookie on submit for non modal mode
            if (!this.data.attrs.modal)
                this._setCookie();

            return true;
        }
        return false;
    },

    /**
     * Sets a cookie in the format instanceID=questionID to expire when cookie_duration is complete
     */
    _setCookie: function() {
        var date = new Date();
        date.setDate(date.getDate() + this.data.attrs.cookie_duration);
        document.cookie = this.instanceID + "=" + this.data.js.question_id + ";expires=" + date.toUTCString() + ";path=/";
    },

    /**
     * Validates the form's survey question
     * @param {HTMLFormElement} the form to validate
     * @return {bool} true if validation is succesfull, false otherwise
     */
    _validate: function(formElement) {
        var errorDisplay = document.getElementById(this.baseDomID + "_ErrorMessage");
        RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
        return RightNow.MarketingFeedback.validateSurveyFields(formElement, this._getFieldData(), this._getSurveyFieldObjectList(formElement), errorDisplay);
    },

    /**
     * Gets a fieldData object for validation
     * @return {Object} the fieldData object
     */
    _getFieldData: function() {
        return {
            reqd_msg: RightNow.Interface.getMessage("VALUE_REQD_MSG"),
            fld_too_mny_chars_msg: RightNow.Interface.getMessage("FLD_CONT_TOO_MANY_CHARS_MSG"),
            too_few_options_msg: RightNow.Interface.getMessage("NEED_TO_SELECT_MORE_OPTIONS_MSG"),
            too_many_options_msg: RightNow.Interface.getMessage("NEED_TO_SELECT_FEWER_OPTIONS_MSG")
        };
    },

    /**
     * Gets an array of survey field objects from hidden input elements
     * @param {HTMLFormElement} the form containing survey field objects
     * @return {Array} An array of objects containing validation data
     */
    _getSurveyFieldObjectList: function(formElement) {
        switch (this.data.js.question_type) {
            case "text":
            case "choice":
                if (document.getElementById("val_q_" + this.data.js.question_id)) {
                    var validationInfo = document.getElementById("val_q_" + this.data.js.question_id).value;
                    var validationArray = validationInfo.split(",");
                    var min = parseInt(validationArray[1], 10) > 0 ? parseInt(validationArray[1], 10) : 1;
                    return [{
                        id: parseInt(validationArray[0].replace(/'/g,''), 10),
                        min: min,
                        max: parseInt(validationArray[2], 10),
                        type: parseInt(validationArray[3], 10),
                        elements: validationArray[4],
                        question_text: ''
                    }];
                }
                break;
            case "matrix":
                var fieldObjects = [];
                for (var i in formElement) {
                    if (formElement[i] !== null && formElement[i].type === 'hidden') {
                        var validationInfo = formElement[i].value;
                        var validationArray = validationInfo.split(",");
                        var min = parseInt(validationArray[1], 10) > 0 ? parseInt(validationArray[1], 10) : 1;
                        fieldObjects.push({
                            id: validationArray[0].replace(/'/g,''),
                            min: min,
                            max: parseInt(validationArray[2], 10),
                            type: parseInt(validationArray[3], 10),
                            elements: validationArray[4],
                            question_text: '',
                            force_ranking: validationArray[6]
                        });
                    }
                }
                return fieldObjects;
                break;
        }
        return [{}];
    },

    /**
     * Event handler to set the chatID when the poll is administered from a chat page
     */
    _chatEngagementParticipantAddedResponse: function() {
        this._chatID = RightNow.Chat.Controller.ChatCommunicationsController.getEngagementID();
     },

    /**
     * Event handler for poll_logic
     */
    _onShowPoll: function() {
        this._dialog = null;
        if (this.data.attrs.seconds > 0)
            this.Y.Lang.later(this.data.attrs.seconds * 1000, this, '_showDialog');
        else
            this._showDialog();
    },

    /**
     * Event handler for server sends response.
     * @param {object} response Response object
     * @param {object} originalEventObj Original event object
     */
    _onResponseReceived: function(response, originalEventObj)
    {
        if(RightNow.Event.fire("evt_pollResponseReceived", {data: originalEventObj, response: response}))
        {
            if (this._dialog) {
                this._dialog.getButtons().item(0).set('innerHTML', this.data.js.ok_button_label);
                this._dialog.enableButtons();
            }

            if (this.data.js.question_type === "choice" && this.data.js.show_chart &&
               (this.data.attrs.chart_type === "vertical_bar" || this.data.attrs.chart_type === "horizontal_bar"
                || this.data.attrs.chart_type === "pie" || this.data.attrs.chart_type === "simple"))
                this._showChart(response.question_results, response.total);
            else
                this._showThankYou();
        }
    },

    /**
     * Shows the turn text rather than a chart
     */
    _showThankYou: function() {
        this.Y.one(this.baseSelector + "_PollQuestion").set('innerHTML', "");
        this.Y.one(this.baseSelector + "_FlipArea").set('innerHTML', this.data.js.turn_text);
        RightNow.UI.show(this.baseSelector);
    },

    /**
     * Shows the chart and calls showTotalVotes if checked in admin console
     * @param jsonString {String} JSON encoded string containing polling survey results data
     * @param totalVotes {String} The total number of submissions for this polling survey
     */
    _showChart: function(jsonString, totalVotes) {
        var flipAreaElement = this.Y.one(this.baseSelector + "_FlipArea");
        // We can't show a chart if we don't have a place to put it
        if (!flipAreaElement)
            return;

        this.Y.one(this.baseSelector + "_FlipArea").addClass("rn_ChartArea");
        var data = RightNow.JSON.parse(jsonString),
            total = 0,
            percentAsInt = 0, i;

        for (i in data) {
            if(data.hasOwnProperty(i)) {
                percentAsInt = parseInt(data[i].percent_total, 10);
                data[i].remainder = data[i].percent_total - percentAsInt;
                data[i].percent_total = percentAsInt;
                total += percentAsInt;
            }
        }

        if (total < 100) {
            var remainder = 100 - total;
            for (i = 0; i < remainder; i++) {
                // we don't want to add 1% to a choice that hasn't been picked
                // we also don't want to skip a choice without incrementing remainder because we won't add up to 100%
                // to prevent an infinite loop don't increment remainder more than 10 times
                if (data.hasOwnProperty(i) && data[i].percent_total > 0)
                    data[i].percent_total++;
                else if (i < 10)
                    remainder++;
            }
        }

        // build the simple chart as a back up regardless of chart_type
        this._buildSimpleChart(data, flipAreaElement);

        RightNow.UI.show(this.baseSelector);

        if (this.data.attrs.chart_type !== 'simple')
        {
            var chart = this._buildChart(data, flipAreaElement);
            if(RightNow.Event.fire("evt_renderChart", {chart: chart}))
                chart.render(flipAreaElement);
        }

        if (this.data.js.show_total_votes)
            this._showTotalVotes(totalVotes);
    },

    /**
     * Builds a simple chart that doesn't use YUI and styled with only CSS
     * @param chartData {Array} The results data for a polling survey
     * @param flipAreaElement {Node} The div to put the simple chart into
     */
    _buildSimpleChart: function(chartData, flipAreaElement) {
        var simpleChart = '<table class="rn_SimpleChartTable">';
        for (var i = 0; i < chartData.length; i++)
            simpleChart += '<tr class="rn_SimpleChartRow"><td class="rn_SimpleChartFirstCell">' + chartData[i].response + '</td><td class="rn_SimpleChartSecondCell" width="100%"><div class="rn_SimpleChartBar" style="width:' + chartData[i].percent_total + '%">&nbsp;</div></td><td class="rn_SimpleChartThirdCell">' + chartData[i].percent_total + "%" + '</td></tr>';
        simpleChart += '</table>';

        flipAreaElement.set('innerHTML', simpleChart);
    },

    /**
     * Builds the YUI chart
     * @param data {Array} Polling survey results data
     * @param flipAreaElement {Node} The div to put the chart into
     */
    _buildChart: function(data, flipAreaElement) {
        flipAreaElement.set('innerHTML', '');

        var dataValues = [];
        for (var i = 0; i < data.length; i++){
            dataValues.push({category:data[i].response, values:data[i].percent_total});
        }

        var chartTooltip = {
            markerLabelFunction: function(categoryItem, valueItem, itemIndex, series, seriesIndex){
                return categoryItem.value + "\n" + valueItem.value + "%";
            },
            styles: {
                backgroundColor: this.data.attrs.chart_tooltip_background_color,
                color: this.data.attrs.chart_tooltip_font_color,
                border: "none"
            }
        };

        var chartType = 'bar';

        switch (this.data.attrs.chart_type)
        {
            case "pie":
                chartType = 'pie';
                break;
            case "horizontal_bar":
                chartType = 'bar';
                var chartAxes = {
                    x:{
                        keys:["values"],
                        position:"bottom",
                        type:"numeric",
                        styles:{
                            majorTicks:{display: "none"},
                            minorTicks:{display: "none"},
                            label: {display: "none"}
                        }
                    },
                    y:{
                        keys:["category"],
                        position:"left",
                        type:"category",
                        styles:{
                            label: {color: "#000000"}
                        }
                    }
                };
                break;
            case "vertical_bar":
                chartType = 'column';
                var chartAxes = {
                    x:{
                        keys:["category"],
                        position:"bottom",
                        type:"category",
                        styles:{
                            label: {rotation:-45, color: "#000000"}
                        }
                    },
                    y:{
                        keys:["values"],
                        position:"left",
                        type:"numeric",
                        styles:{
                            majorTicks:{display: "none"},
                            minorTicks:{display: "none"},
                            label: {display: "none"}
                        }
                    }
                };
                break;
        }

        if (chartType != 'pie')
        {
            var seriesCollection = [
                        {
                            categoryKey: "category",
                            valueKey: "values",
                            styles: {
                                fill: {color: this.data.attrs.chart_bar_color}
                            }
                        }
                    ];
        }
        else
        {
            var seriesCollection = [
                        {
                            categoryKey: "category",
                            valueKey: "values",
                            styles:{
                                fill:{
                                    colors:
                                    //yellow, blue, green
                                    //aqua, red, teal
                                    //maroon, purple, navy, lime
                                    ["#FFFF00", "#0000FF", "#008000",
                                     "#00FFFF", "#FF0000", "#008080",
                                     "#800000", "#800080", "#000080",
                                     "#00FF00"]
                                },
                                border: {
                                    weight: 1,
                                    colors: ["#FFFFFF"]
                                }
                            }
                        }
                    ];
        }

        var ariaDiv = this.Y.Node.create("<div class='rn_ScreenReaderOnly' role='alert'>");
        ariaDiv.set('innerHTML', this._getChartAltText(this.data.attrs.chart_type, data));
        flipAreaElement.append(ariaDiv);

        var chart = new this.Y.Chart({
                       dataProvider: dataValues,
                       type: chartType,
                       seriesCollection: seriesCollection
                 });

        chart.set("tooltip", chartTooltip);
        if (chartType !== "pie")
            chart.set("axes", chartAxes);

        return chart;
    },

    /**
     * Gets a textual representation of the result data
     * @param chartType {String} The value of the chart_type attribute
     * @param chartData {Object} The result data
     */
    _getChartAltText: function (chartType, chartData) {
        var altText = '';
        switch (chartType) {
            case 'pie':
                altText = RightNow.Interface.getMessage("POLL_RESULTS_PIE_CHART_UC_LBL") + " ";
                break;
            case 'horizontal_bar':
                altText = RightNow.Interface.getMessage("POLL_RESULTS_HORIZ_BAR_CHART_LBL") + " ";
                break;
            case 'vertical_bar':
                altText = RightNow.Interface.getMessage("POLL_RESS_VERTICAL_BAR_CHART_LBL") + " ";
                break;
        }

        for (var i in chartData) {
            altText += chartData[i].response + ":";
            altText += chartData[i].percent_total + "%";
            if (i < chartData.length)
                altText += ", ";
        }

        return altText;
    },

    /**
     * Shows the total number of votes
     * @param totalVotes {String} The total number of submissions for this polling survey
     */
    _showTotalVotes: function(totalVotes) {
        this.Y.one(this.baseSelector + "_TotalVotesParagraph").append(" " + totalVotes);
        RightNow.UI.show(this.baseSelector + "_TotalVotes");
    }
});
