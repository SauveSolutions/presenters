<?php
/**
 * Copyright (C) 2014 - Sauve Solutions Limited
 * License : MIT
 */

namespace sauvesolutions\presenters;

use Carbon\Carbon;

/**
 * Class DateConverter
 *
 * This contains functionality to manage the conversion of dates from string to Carbon/
 *
 * @package Sauve\presenters
 */
trait DateConverter {

    protected function getDateFormat() {
        return 'd/m/Y';
    }

    protected function convertDateToString($date) {
        if ($date == null ) {
            return '';
        }

        return $date->format($this->getDateFormat());
    }

    /**
     * @param $date
     *
     * @return Carbon|null
     */
    protected function convertToCarbon($date) {
        if ($date == '') {
            return null;
        }

        return Carbon::createFromFormat($this->getDateFormat(), $date);
    }

}