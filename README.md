This library contains helpers to be used by sage 9 based theme.
See https://github.com/strarsis/sage9-onepager-themefiles for the theme related files.

In `setup.php`:
```php
add_action('after_setup_theme', function () {
    \Onepager_Extension_Controls::init();
});
````

## Credits
Code has been adopted from [WordPress Twenty Seventeen theme](https://github.com/WordPress/WordPress/tree/master/wp-content/themes/twentyseventeen).
