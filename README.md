Presenters/Unpresenters Framework
=====================

Presenters are often used to convert data in a storage format for display. Typical implementations use the decorator pattern.

A similar problem exists when receiving information back from a user, for example on a web form. This needs to be
converted from a human readable format into a format suitable for storage.

This is an **Unpresenter**.

This implementation provides four core capabilities

* Automatically handles date conversion from a defined format into a Carbon instance for storage
* Automatically handles checkboxes from forms (not nested)
* Handles validation simply
* Provides facility to mutate any attribute.

Requirements
------------
* Laravel
* As for Laravel >= v4.1

Installation
------------
You must install this library through Composer:

```javascript
{
    "require": {
        "SauveSolutions/presenters": "0.2.*"
    }
}
```

Usage
------

Using the library is very simple.

1. Derive an unpresenter class from SauveSolutions\presenters\Unpresenter and implement the functionality you need, typically
this is just listing the date variables, the checkbox variables and the validation rules.
2. In a controller instantiate an instance of the Unpresenter and pass the input array to the constructor.
3. Call validate and trap any ValidationExceptions thrown.
4. Access input variables directly from the unpresenter instance.

```php
class ExampleUnpresenter extends SauveSolutions\presenters\Unpresenter {

    //specify any date fields
    protected $dates = ['a_date'];

    //specify any checkboxes that may be expected in the data sent with the web request
    protected $checkboxes = ['important'];

    /**
     * Specify the validation rules to be applied.
     *
     * @param $bUpdate boolean Pass true if the object is being updated, false otherwise.
     * @return array The validation rules.
     */
    public function getValidationRules($update) {
        //if updating an existing record then $update would be true, for a new record it would be false. This allows you
        //to create different rules for updates to existing records or creation of new records, e.g. unique checks that exclude the current record.
        //for this example however simply use a single response.

        return array(
                    'a_date' => 'date_format:d/m/Y'
                );

    }


}
```

Then in your controller method, for example Store.

```php

public function store() {

    $input = Input::except('_token', '_method');

    $input = new ExampleUnpresenter($input);

    //simple try/catch block - the exception could be caught globally if preferred.
    try {
        //now try and validate
        $input->validate(false);
    } catch (SauveSolutions\exceptions\ValidationException $e) {
        //if it failed then redirect back.
        return Redirect::route('example.create')->withInput()->withErrors($e->getValidationErrors());
    }

    //now create a new example model.
    $model = new ExampleModel();
    $model->a_date = $input['a_date'];
    $model->save();

    //and finally let's show the index page.
    return Redirect::route('example.index');

}

```

Alternative
-----------
An alternative is to call $unpresenter->parseInput() which processes all of the input data and returns a plain php array
of the transformed inputs.



FAQ
---
*The DateConverter trait uses an English locale date format (d/m/Y) how can I change this?* The simplest approach would be
to derive your own Unpresenter class which overrides DateConverter::getDateFormat(). It is expected in any non trivial application
that the date format is an attribute of the User or a similar global setting. Then use your Unpresenter class as the base class
 of any other Unpresenters you create.

Laravel 4.3
------------
Laravel 4.3 has implemented a new FormRequest object that performs the validation functionality of the Unpresenter class. Together with the
ability to resolve a class from a hint on a controller method out of the IoC container will provide a further productivity boost.
It is likely that the Unpresenter functionality can be combined with the FormRequest functionality in a subclass to provide the
transformation capbility. This will be investigated in a future update.