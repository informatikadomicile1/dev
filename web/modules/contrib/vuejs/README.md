Vue.js Drupal module
====================

DESCRIPTION
-----------

The module provides a bridge between Drupal and Vue.js framework.

REQUIREMENTS
------------

- Drupal 9.x or 8.9.x
- Drush 10.x or - for Drupal 8.x only - Drush 9.x
- PHP 7.2 or higher
- The `vuejs:download` Drush command needs [`wget(1)`]

[`wget(1)`]: https://www.gnu.org/software/wget/


CONFIGURATION
-------------

Navigate to the `admin/config/development/vuejs` page and set up desired
versions of the libraries. Note if you prefer installing libraries locally you
need to install them after each version change. The following Drush command can
be used to quickly download required Vue.js libraries:

```
drush vuejs:download <LIBRARY_NAME>
```

Downloadable libraries are: `vue`, `vue-router`, and `vue-resource`.


HOW TO USE
----------

1. You can use inside Twig templates as usual, example:
```twig
{{ attach_library('vuejs/vue') }}
```
2. You can attach it programmatically:
```php
function MYMODULE_page_attachments(array &$attachments) {
  $attachments['#attached']['library'][] = 'vuejs/vue';
}
```
3. You can add it as dependency inside your `*.libraries.yml`:
```yaml
  dependencies:
    - vuejs/vue
    - vuejs/vue_router
    - vuejs/vue_resource
```


PROJECT PAGE
------------

https://www.drupal.org/project/vuejs
