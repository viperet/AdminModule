AdminModule
===========

## AdminModule constructor options:
-	'title' - interface title, displayed in breadcrumbs
-	'table' - table interface will work with
-	'sort' - records sort order ('date DESC')
-	'form' - form definition,
-	'baseUrl' - interface base url ('/admin/staff/?foo'),
-	'helpersUrl' - url where library can be accessed ('/libs/AdminModule/'),
-	'db' - MySQL resource to access database
-	'role' - current user role for role-based access control, can be any string ('admin','user','guest')

## Form definition
```php
$form = array(
	'field_name_in_db' => array(
		'type' => 'text', // field type - text, date, checkbox etc
		'value' => 'Default value',
		'label' => 'Field label', // label in form can column label in list
		'label_hint' => 'Hint on field', // small text under label in form and popup hint in list
		'readonly' => false, // readonly field can't be modified, default - false
		'header' => true, // field should be displayed as column in list view
		'validation' => 'email', // validation mode on field, supported:
		                         // url, email, integer, money, float, regexp
		'validation_regexp' => '/^[a-z0-9]+$/', // regular expression for regexp validation mode, 
		                                        // btw regexp can be written directly in 'validation' field
		'validation_message' => 'Only letters and numbers can be used!', // custom regexp validation msg 
		'class' => 'custom-css-class', // class to be added to form element
		'truncate' => 80, // truncate field content in list view to given number of characters (80 default)
		'escape' => true, // escape html special chars in field value on output or not (default - true)
		'required' => true, // required field should have value to pass form validation (default - false)
		'filter' => true, // use field's contents for filtering/searching in list view (default - false)
		'filterByClick' => true, // filter list by field value on click on that field (default - false)
		'permissions' => ['admin'=>'rw', 'user'=>'r', 'guest'=>''], // role-based permissons on that field
				// 'rw' - can view and modify, 'r' - read only, '' or no entry - can't view or modify
	),
);
```	

Some field types have their own special fields.
