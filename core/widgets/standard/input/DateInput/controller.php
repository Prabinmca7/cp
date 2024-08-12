<?php
namespace RightNow\Widgets;

use RightNow\Utils\Config,
    RightNow\Connect\v1_4 as Connect;

class DateInput extends \RightNow\Libraries\Widget\Input {

    /**
     * The date limits imposed by the API for legacy date fields
     */
    protected $minDateConstraint = 86400;
    protected $maxDateConstraint = 2147385599;

    function __construct($attrs) {
        if ($attrs['max_year']) {
            try {
                // getMaxYear can fail if EU_MAX_YEAR contains an invalid value.
                $maxYear = $attrs['max_year']->default = Config::getMaxYear();
            }
            catch (\Exception $e) {
                echo $this->reportError($e->getMessage());
                $maxYear = $attrs['max_year']->default = date('Y');
            }
            if (!$attrs['max_year']->value && !$attrs['default_value']->value) {
                $attrs['max_year']->value = $maxYear;
            }
        }
        parent::__construct($attrs);
    }

    function getData() {
        if (parent::getData() === false) {
            return false;
        }

        if (!in_array($this->dataType, array('Date', 'DateTime'))) {
            echo $this->reportError(sprintf(Config::getMessage(PCT_S_DATE_DATE_SLASH_TIME_FIELD_MSG), $this->fieldName));
            return false;
        }

        $this->data['readOnly'] = $this->data['js']['readOnly'] = $this->data['readOnly'] || $this->data['attrs']['read_only'];

        $this->data['displayType'] = $this->dataType;
        $minYear = $this->data['attrs']['min_year'];
        $maxYear = $this->data['attrs']['max_year'];
        $constraints = $this->getConstraints($this->getMetaConstraints());
        if (array_key_exists('min', $constraints) && ($parts = explode('-', $constraints['min'])) && ($minConstraintYear = intval($parts[0]))) {
            $minYear = $this->data['attrs']['min_year'];
            $this->reportError(sprintf(Config::getMessage(PCT_S_MIN_VAL_FLD_CONSTRAINT_SET_Y_UHK), $this->fieldName, $minYear), false);
        }

        $min = $this->data['js']['min'] = $this->getDateArray($minYear ?: Config::getMinYear(), $constraints['min'], 'min');
        $max = $this->getDateArray($maxYear ?: Config::getMaxYear(), $constraints['max'], 'max');
        if (!$this->data['readOnly']) {
            $this->data['constraints']['minValue'] = strtotime($min['date']);
            $this->data['constraints']['maxValue'] = strtotime($max['date']);
        }
        $this->data['dayLabel'] = Config::getMessage(DAY_LBL);
        $this->data['monthLabel'] = Config::getMessage(MONTH_LBL);
        $this->data['yearLabel'] = Config::getMessage(YEAR_LBL);
        $this->data['hourLabel'] = Config::getMessage(HOUR_LBL);
        $this->data['minuteLabel'] = Config::getMessage(MINUTE_LBL);
        list($this->data['monthOrder'],
            $this->data['dayOrder'],
            $this->data['yearOrder'],
            $this->data['js']['min_val']
        ) = $this->getOrderParameters($min, Config::getConfig(DTF_INPUT_DATE_ORDER), $this->dataType === 'DateTime');

        if($this->data['value']) {
            if(is_numeric($this->data['value'])) {
                $dt = new \DateTime();
                $dt->setTimestamp($this->data['value']);
            }
            else {
                $dt = new \DateTime($this->data['value']);
            }
            $this->data['value'] = explode(' ', $dt->format('m j Y G i'));
            $this->data['defaultValue'] = true;
        }
    }

    public function outputSelected($index, $itemIndex) {
        if (is_array($this->data['value']) && intval($this->data['value'][$index]) === $itemIndex) {
            return 'selected="selected"';
        }
    }

    /*
     * Returns an associative array of 'min' and 'max' whose values are the corresponding date in the format 'Y-m-d H:i:s'.
     * If the meta constraints suggest the more restrictive legacy values, the MIN|MAX_DATE defines are used.
     * Otherwise the date string is simply derived from the constraints.
     * @param array $constraints The min and max meta constraint values
     * @return array
     */
    protected function getConstraints(array $constraints) {
        $useDefines = $constraints['min'] === $this->minDateConstraint && $constraints['max'] === $this->maxDateConstraint;
        $toDate = function($type, $constraint) use ($useDefines) {
            if($useDefines || $constraint === null)
                return $type === 'min' ? MIN_DATE : MAX_DATE;
            else{
                if(is_numeric($constraint)) {
                     $dt = new \DateTime('@'.$constraint);
                }
                else {
                     $dt = new \DateTime($constraint);
                }
                return $dt->format('Y-m-d H:i:s');
            }
        };

        return array(
            'min' => $toDate('min', $constraints['min']),
            'max' => $toDate('max', $constraints['max']),
        );
    }

    /*
     * Returns an associative array of 'min' and 'max' whose values reflect the constraints defined in this->fieldMetaData.
     * @return array An associative array of 'min' and 'max' whose values reflect the constraints defined in this->fieldMetaData.
     */
    protected function getMetaConstraints() {
        $constraints = array();
        $meta = $this->fieldMetaData->constraints ?: array();
        foreach ($meta as $constraint) {
            // @codingStandardsIgnoreStart
            if ($constraint->kind === Connect\Constraint::Min) {
                $constraints['min'] = $constraint->value;
            }
            else if ($constraint->kind === Connect\Constraint::Max) {
                $constraints['max'] = $constraint->value;
            }
            // @codingStandardsIgnoreEnd
        }
        return $constraints;
    }

    /**
     * Returns an array of min or max 'year', 'month', 'day', 'time' and 'date'.
     * @param int $year The year
     * @param string $date The date string in 'Y-m-d H:i:s' format.
     * @param string $type Parameter to indicate if formatting min or max date
     * @return array
     */
    protected function getDateArray($year, $date, $type = 'max') {
        list($yearMonthDay, $time) = explode(' ', $date);
        list($constraintYear, $month, $day) = explode('-', $yearMonthDay);
        // Min year greater than constraints min year, reset min day and month to 1
        if ($type === 'min' && $constraintYear < $year) {
            $month = $day = 1;
        }
        else {
            $month = intval($month, 10);
            $day = intval($day, 10);
        }
        return array(
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'hour' => intval(\RightNow\Utils\Text::getSubstringBefore($time, ':')),
            'time' => $time,
            'date' => "{$year}-{$month}-{$day} {$time}",
        );
    }

    /**
     * Returns an array of widget parameters based on $dateOrder
     * @param Array $min An array of minimum 'year', 'month', 'day' & 'time'
     * @param Integer $dateOrder One of [0, 1, 2] based on dtf_input_date_order config
     * @param Boolean $addTime If true, append the time to the date string.
     * @return Array
     */
    private function getOrderParameters(array $min, $dateOrder, $addTime = true) {
        if ($dateOrder == 0) {
            $orderData = array(0, 1, 2, "{$min['month']}/{$min['day']}/{$min['year']}");
        }
        else if ($dateOrder == 1) {
            $orderData = array(1, 2, 0, "{$min['year']}/{$min['month']}/{$min['day']}");
        }
        else {
            $orderData = array(1, 0, 2, "{$min['day']}/{$min['month']}/{$min['year']}");
        }

        if ($addTime) {
            $orderData[3] .= " {$min['time']}";
        }

        return $orderData;
    }

}
