<?php
/**
 * Copyright (C) 2014 - Sauve Solutions Limited
 * License : MIT
 */

namespace sauvesolutions\exceptions;

/**
 * Class ValidationException
 *
 * This is a simple class that extends the base exception class to handle passing the validation errors
 * returned by the Laravel validation component.
 *
 */
class ValidationException extends \Exception {

    protected $validationErrors;

    /**
     * Simple constructor, must pass in the array of validation errors
     *
     * @param array  $validationErrors A simple array of the validation errors returned from the validation component.
     * @param string $message What message do you want to display, not normally used since the errors are per field.
     * @param int    $code
     * @param null   $previous
     */
    public function __construct(array $validationErrors, $message = 'Validation Failed', $code = 0, $previous = null) {

        //need to call the base class.
        parent::__construct ( $message, $code, $previous );

        //and store the validation errors passed in.
        $this->validationErrors = $validationErrors;
    }

    /**
     * Returns the array of the validation errors.
     *
     * @return array
     */
    public function getValidationErrors() {
        return $this->validationErrors;
    }

}