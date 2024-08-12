RightNow.Widgets.ActivityCharts = RightNow.Widgets.extend({
    constructor: function() {
        this.chartData = {};
        this.chartStyleDefinition = {};
        this.legendDefinition = false;
        this.axesDefinition = {};
        this.tooltipDefinition = {};
        this.seriesCollectionDefinition = [];

        //Set the required data to generate the chart
        this._setChartData();
        this._setChartStyleDefinition();
        this._setLegendDefinition();
        this._setAxesDefinition();
        this._setTooltipDefinition();
        this._setSeriesCollectionDefinition();

        //Create a new chart instance and render it
        new this.Y.Chart({
            dataProvider: this.chartData,
            ariaLabel: this.data.attrs.label_chart_title,
            styles:this.chartStyleDefinition,
            legend: this.legendDefinition,
            axes: this.axesDefinition,
            tooltip: this.tooltipDefinition,
            seriesCollection: this.seriesCollectionDefinition,
            render: this.baseSelector + "_CountChart",
            type: "column",
            categoryKey: "date",
            horizontalGridlines: true
        });
    },

    /**
    * Set the data to generate the chart, override this method to pass different data
    */
    _setChartData: function() {
        this.chartData = this.data.js.chart.data;
    },

    /**
     * Set the custom style for chart's background and border
     */
    _setChartStyleDefinition: function() {
        this.chartStyleDefinition = {
            graph: {
                background: {
                    fill: {
                        color: this.data.attrs.chart_background_color
                    },
                    border: {
                        color: this.data.attrs.chart_border_color
                    }
                }
            }
        };
    },

    /**
     * Set the custom style for chart legend if show_legend attribute set to true
     */
    _setLegendDefinition: function() {
        if (this.data.attrs.show_legend) {
            this.legendDefinition = {
                position: "right",
                width: 150,
                height: 150,
                styles: {
                    hAlign: "center",
                    hSpacing: 4,
                    item: {
                        label: {
                            color: this.data.attrs.chart_font_color
                        }
                    },
                    background: {
                        fill: {
                            color: this.data.attrs.chart_legend_background_color
                        },
                        border: {
                            color: this.data.attrs.chart_legend_border_color
                        }
                    }
                }
            };
        }
    },

    /**
     * Customize the X and Y axis like not displaying floating point; rotate the X axis labels to avoid overlapping
     */
    _setAxesDefinition: function() {
        var legendLabels = this.Y.Object.values(this.data.js.chart.keys);
        this.axesDefinition = {
            totals: {
                keys: legendLabels,
                position: "left",
                labelFunction: function (val) {
                    //display only integers in Y-axis
                    return (val % 1 === 0)  ? parseFloat(val) : '';
                },
                type: "numeric",
                minimum: 0,
                maximum: this.data.js.chart.axes.maximum,
                styles: {
                    label: {
                        color: this.data.attrs.chart_font_color
                    }
                }
            },
            dateRange: {
                keys: ["date"],
                position: "bottom",
                type: "category",
                styles: {
                    label: {
                        rotation: -45,
                        margin: {top: 5},
                        color: this.data.attrs.chart_font_color
                    }
                }
            }
        };
    },

    /**
     * Set the custom style for tooltip
     */
    _setTooltipDefinition: function() {
        this.tooltipDefinition = {
            styles: {
                backgroundColor: this.data.attrs.chart_tooltip_background_color,
                color: this.data.attrs.chart_font_color,
                borderColor: this.data.attrs.chart_tooltip_border_color
            }
        };
    },

    /**
     * Set the customized style for different bars in the chart, bar colors can be controlled from widget attributes
     */
    _setSeriesCollectionDefinition: function() {
        var fillColors = {
            CommunityQuestion: this.data.attrs.chart_question_bar_color,
            CommunityComment: this.data.attrs.chart_comment_bar_color,
            CommunityUser: this.data.attrs.chart_user_bar_color
        };
        this.Y.Object.each(this.data.js.chart.keys, function(socialObjectLabel, socialObject) {
            var customStyle = {
                xKey: "date",
                xDisplayName: this.data.attrs.label_date,
                yKey: socialObjectLabel,
                yDisplayName: socialObjectLabel,
                styles: {
                   marker: {
                        width: 15,
                        height: 15,
                        fill: {
                            color: fillColors[socialObject]
                         },
                         over: {
                             fill: {
                                 color: this.data.attrs.chart_hover_bar_color
                             }
                         }
                    }
                }
            };
            this.seriesCollectionDefinition.push(customStyle);
        }, this);
    }
});