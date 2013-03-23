openlss/lib-tpl
=======

Wrapper library for managing PHPTAL environment. Also supports HTML Tidy formatting.

Usage
----
```php
ld('/func/mda_glob','tpl','config');

//init templating system
$theme = (Config::get('theme','name') ? Config::get('theme','name') : 'default');
Tpl::_get()->setPath(ROOT_GROUP.'/theme/'.$theme);
Tpl::_get()->setUri('/theme/'.$theme.'/');
Tpl::_get()->set(array(
	 'lss_version'		=>	LSS_VERSION
	,'version'			=>	VERSION
	,'site_name'		=>	Config::get('site_name')
	,'site_title'		=>	Config::get('site_name')
	,'uri'				=>	Config::get('url','uri')
	,'url'				=>	Config::get('url','url')
	,'theme_path'		=>	Tpl::_get()->uri
	,'copyright'		=>	'© '.date('Y').' '.Config::get('site_name')
));
unset($theme);

$params = array();

//parse template and return
$params['html'] = Tpl::_get()->output('client_file_manage',$params,false);

//parse template and output
Tpl::_get()->output('client_file_list',$params);
```

Reference
----

### (object) Tpl::_get()
Returns the singleton (creates it if it doesnt exist)

### (object) Tpl::setPath($value)
Sets the path to the template files

### (object) Tpl::setUri($value)
Sets the URI that should be used when parsing templates

### (string) Tpl::addCss($file,$media='screen')
Adds a CSS file entry that gets output to the HEAD section of the body

### (bool) Tpl::resetCss()
Clears the CSS buffer

### (string) Tpl::addJs($file)
Adds a Javascript file load that will be output to the HEAD section of the body

### (bool) Tpl::resetJs()
Clears the JS buffer

### (bool) Tpl::set($name,$value=null,$overwrite=true)
Sets a constant that can be used globally in the templates
  * $name		The name of the constant
  * $value		The constant value
  * $overwrite	When set to FALSE will not overwrite existing constant
Returns FALSE if unable to write constant

### (bool) Tpl::get($name)
Get a constant by name
If $name is NULL returns entire constant tree
Returns NULL if no constant exists

### (string) Tpl::add($html)
Add raw data to the template body

### (object) Tpl::reset()
Resets the body buffer

### (object) Tpl::setStub($name,$value=true)
Enables stubs that are loaded conditionally

### (object) Tpl::addDebug($value)
Adds debug output to but printed with the template
(allows debug data to be formatted more readible)

### (string) Tpl::debug()
Returns buffered debug data ready for templating

### (string) Tpl::stats()
Collects various stats from script execution
The return value is ready for templating

### (mixed) Tpl::output($tpl,$params=array(),$echo=true)
Output Function
  * This is the main output handler that calls into PHPTAL
  * It will render the specified template file and add
    the passed tags to the environment
  * It also sets up the global environment for page parsing
  * NOTE: there should only be one call to this per
     page unless in extreme circumstances
  * ARGUMENTS
   * file	the template file to be parsed
   * tags	array of variables to be added to env
   * echo	when true the tpl system will output
              directly to the browser and exit

