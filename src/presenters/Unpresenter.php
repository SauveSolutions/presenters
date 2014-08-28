<?php
/**
 * Copyright (C) 2014 - Sauve Solutions Limited
 * License : MIT
 */

namespace Sauve\presenters;

use Sauve\exceptions\ValidationException;

/**
 * Class Unpresenter
 *
 * This is the base class that is used to "unpresent" information received from a UI, for example it is designed to
 * convert dates from a string format to Carbon or deal with check boxes etc. Fundamentally it does the same thing as the presenter
 * so it a lot of ways it is an empty class for now.
 *
 * @package Sauve\presenters
 */
class Unpresenter implements \ArrayAccess {

    //we are going to be converting Dates, so we need the date converter trait
    use DateConverter;

    /**
     * @var array Store all of the data passed to the unpresenter
     */
    protected $input;

    /**
     * @var array Are there any checkboxes we are expecting? If these are not set in the input stream then these will simply
     * not be present so therefore hard to set.
     */
    protected $checkBoxes = [];

    /**
     * What are the values we want to use for the different states of a checkbox.
     * @var array
     */
    protected $checkBoxStates = ['checked'=>true, 'unchecked'=>false];

    /**
     * @var array Like with Eloquent ORM we want to see if any of the fields supplied are dates. If they are then we will
     * transform them at the point of access.
     */
    protected $dates = [];


    /**
     * @param array $input
     */
    public function __construct(array $input) {
        $this->setInput($input);
    }


    /**
     * @param array $input
     */
    public function setInput($input) {
        $this->input = $input;
    }


    /**
     * Return the list of the check boxes defined for this unpresenter.
     * @return array
     */
    protected function getCheckboxes() {
        return $this->checkBoxes;
    }


    /**
     * Return the array of fields that are to be treated as a date.
     * @return array
     */
    public function getDates() {
        return $this->dates;
    }


    /**
     * Return the validation rules to be applied.
     *
     * @param $bUpdate boolean Indicates whether the validation rules should be applied for an update or a first entry.
     * @return array The validation rules.
     */
    public function getValidationRules($bUpdate) {
        return [];
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    public function __get($value) {
        return $this->getTransformedValue($value);
    }


    /**
     * Perform the validation of the input array
     *
     * @param $bUpdate boolean pass true to apply validation for an update.
     *
     * @throws \Sauve\exceptions\ValidationException
     */
    public function validate($bUpdate) {
        $validator = \Validator::make($this->input, $this->getValidationRules($bUpdate));

        if ($validator->fails()) {
            throw new ValidationException($validator->getMessageBag()->toArray());
        }

    }


    /**
     * This function is used to get a value through the system. It checks for a function of the right name
     * and if found it calls that function and returns the result. If the function cannot be found
     * then it simply returns the value in the input array.
     *
     * @param $key
     *
     * @return mixed
     */
    protected function getTransformedValue($key) {

        //firstly lets see if an accessor function has been created. Accessor functions have a prototype getAttributeName()
        $methodName = 'get'.studly_case($key);

        if (method_exists($this, $methodName)) {
            //so an accessor function exists - we are going to delegate to that function.
            return $this->$methodName();
        }

        //so there is no method defined so we will simply get a key - might fail!
        return $this->getAttributeValue($key);
    }


    /**
     * Obtain a value from the input array. This will check if the item being requested is a date or a checkbox
     * and call the correct handling function accordingly.
     *
     * @param $key
     *
     * @throws \Exception
     * @return mixed
     */
    protected function getAttributeValue($key) {

        //firstly is it a checkbox?
        if (in_array($key, $this->checkBoxes)) {
            //it is a checkbox, so let's simply return the checkbox value.
            return $this->checkboxValue($key, $this->checkBoxStates['checked'], $this->checkBoxStates['unchecked']);
        }

        //now is it a date?
        if (in_array($key, $this->getDates())) {
            //it is specified in the date array so we need to return the date transformed to an instance of Carbon, using the date converter trait.
            return $this->convertToCarbon($this->input[$key]);
        }

        //if the attribute exists on the input then go get it.
        if (isset($this->input[$key])) {
            return $this->input[$key];
        }

        //haven't found anything so let's throw an exception.
        throw new \Exception("Unknown attribute key");
    }


    /**
     * Process a checkbox in the input array - if the check box is checked then the field will be in the array, if not
     * then the field won't exist.
     *
     * @param $key string Name of the checkbox
     * @param $true boolean The value to return if checked
     * @param $false boolean The value to return if not checked
     *
     * @return mixed
     */
    protected function checkboxValue($key, $true = true, $false = false) {
        //the semantics of a checkbox are such that if the checkbox field exists then it must be checked
        if (isset($this->input[$key])) {
            //return the value for $true
            return $true;
        }

        //ok so the check box was not checked (the checkbox name is not in the return array) so we return the value $false
        return $false;
    }


    /**
     * This function iterates over the input items and returns a new array that has all of the attributes in it that
     * have been converted to the storage format.
     *
     * @return array
     */
    public function parseInput() {
        $output = [];

        foreach($this->input as $key => $value) {
            //we will handle checkboxes separately...
            if (!in_array($key, $this->getCheckboxes())) {
                $output[$key] = $this->getTransformedValue($key);
            }
        }

        //now going to go through the checkboxes.
        foreach ($this->getCheckboxes() as $checkbox) {
            $output[$key] = $this->getTransformedValue($checkbox);
        }

        //and finally return the result.
        return $output;
    }


    /*
     * We now need to implement the array accessor functions.
     */


    /**
     * Return true if the key is in the original input array, or if it is in the checkbox
     * array, since if in the checkbox array it might not be present in the input
     * array if the checkbox was not checked.
     *
     * @param string $key
     *
     * @return bool
     */
    public function offsetExists($key) {
        return isset($this->input[$key]) || in_array($key, $this->getCheckboxes());
    }

    /**
     * Get the value for a particular key.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function offsetGet($key) {
        return $this->getTransformedValue($key);
    }

    /**
     * This should really be read only, but in some case it might be simpler to set extra values on the input array
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value) {
        $this->input[$key] = $value;
    }

    /**
     * As above, should really be readonly, but sometimes might need to clear out an array key.
     * @param mixed $key
     */
    public function offsetUnset($key) {
        unset($this->input[$key]);
    }


}