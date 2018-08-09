This library contains helpers to be used by sage 9 based theme.
See https://github.com/strarsis/sage9-onepager-themefiles for the theme related files.

## Installation

1. Install this helper library
````
$ composer require strarsis/sage9-onepager-lib
````

2. Require this helper library
In `setup.php`:
```php
add_action('after_setup_theme', function () {
    \strarsis\Sage9Onepager\Controls::init();
});
````

3. Go to the theme files and README in https://github.com/strarsis/sage9-onepager-themefiles and follow step 3 and below.

## Credits
Code has been adopted from [WordPress Twenty Seventeen theme](https://github.com/WordPress/WordPress/tree/master/wp-content/themes/twentyseventeen).
