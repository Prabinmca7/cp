/**
 * File: logic.js
 * Abstract: Logic file for the DisplayChartReport widget
 * Version: 1.0
 */

RightNow.namespace('Custom.Widgets.sample.DisplayChartReport');
Custom.Widgets.sample.DisplayChartReport = RightNow.Widgets.extend({
    /**
     * Widget constructor.
     */
    constructor: function() {
        //Insert the initial data set into the chart. Notice how this.data.js.reportData
        //corresponds to the equivalent field in the controller getData function. Additionally, notice
        //that the this.data.attrs.chart_type field corresponds to the same attribute in
        //the info.yml file.
        this._chart = new this.Y.Chart({
            dataProvider: this.transformReportResults(this.data.js.reportData),
            render: this.baseSelector + '_Container',
            type: this.data.attrs.chart_type,
            axes: {
                category: {
                    title: this.data.js.categoryLabel,
                    styles: {
                        title: {
                            fontSize: '120%',
                            color: '#000'
                        },
                        label: {
                            color: '#000'
                        }
                    }
                },
                value: {
                    title: this.data.js.valueLabel,
                    styles: {
                        title: {
                            fontSize: '120%',
                            color: '#000'
                        },
                        label: {
                            color: '#000'
                        }
                    }
                }
            },
            valueAxisName: 'value',
            horizontalGridlines: true,
            verticalGridlines: true
        });

        //Set up some instance variables and add a click handler for the latest results link
        this._container = this.Y.one(this.baseSelector + '_Container');
        this._resultsLink = this.Y.one(this.baseSelector + '_ResultsLink');
        this._resultsLink.on('click', this.getUpdatedResults, this);
    },

    /**
     * Make an AJAX request to the server to retrieve and modify the charting data. Notice that
     * this function makes a request to the this.data.attrs.get_updates_ajax endpoint. The same
     * endpoint can be seen in the controller.php file in the setAjaxHandlers call and in the info.yml
     * file.
     */
    getUpdatedResults: function() {
        //Add a loading indicator to the button
        this.setLoading(true);

        var eventObject = new RightNow.Event.EventObject(this, {data: {
            report_id: this.data.attrs.report_id,
            r_tok: this.data.js.r_tok,
            w_id: this.data.info.w_id
        }});

        //Make a request for the new data. When the data is retrieved add it to the current chart.
        //If the response contains invalid data, display a warning dialog.
        RightNow.Ajax.makeRequest(this.data.attrs.get_chart_data_ajax, eventObject.data,
        {
            successHandler: this.onSuccessfulUpdate,
            failureHandler: this.onFailedUpdate,
            json: true,
            scope: this
        });
    },

    /**
     * Executed when a successful update has occurred. Take the results from the AJAX response and
     * render them in the chart.
     * @param {Object} response The JSON response from the server
     */
    onSuccessfulUpdate: function(response) {
        //Disable the loading indicator
        this.setLoading(false);
        if(typeof response !== 'string') {
            this._chart.set('dataProvider', this.transformReportResults(response));
        }
        else {
            RightNow.UI.Dialog.messageDialog(response, {'icon': 'ALARM'});
        }
    },

    /**
     * Executed when an update has failed. Display a warning dialog and clear the load indicator.
     */
    onFailedUpdate: function() {
        this.setLoading(false);
        RightNow.UI.Dialog.messageDialog('There was an error with the request. Please try again.', {'icon': 'ALARM'});
    },

    /**
     * Add a little bit of flair to the loading indicator while also preventing simultaneous requests
     * to the server. This function acts like a locking mechanism, toggling on and off with each
     * successful request to the server.
     * @param {Boolean} isLoading true or false to enable or disable loading
     */
    setLoading: function(isLoading) {
        var resultLink = this._resultsLink,
            scope = this;

        if(isLoading) {
            resultLink.detach('click', this.getUpdatedResults);
            resultLink.set('innerHTML', this.data.attrs.label_loading);
            this._ticker = setInterval(function() {
                var currentContent = resultLink.get('innerHTML');
                if(currentContent.indexOf('...') !== -1) {
                    resultLink.set('innerHTML', scope.data.attrs.label_loading);
                }
                else {
                    resultLink.set('innerHTML', currentContent + '.');
                }
            }, 200);
        }
        else {
            clearInterval(this._ticker);
            resultLink.set('innerHTML', this.data.attrs.label_result_link);
            resultLink.on('click', this.getUpdatedResults, this);
        }
    },

    /**
     * Take the report results returned from the server and transform them into a
     * data structure that can be fed directly into the YUI charting library. Charts
     * use two keys 'category' and 'value' to represent the two different dimensions of
     * data in a bar, line, column or pie chart.
     */
    transformReportResults: function(results) {
        var chartData = [];
        this.Y.Object.each(results, function(element, key) {
            chartData.push({
                category: element[0], /* Grab the column label from the data */
                value: element[1] /* And the associated value */
            });
        });
        return chartData;
    }
});
