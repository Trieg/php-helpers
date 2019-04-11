CHANGELOG
=========

Version 1.1.0
-------------
-   Add: A new init function for the Gettext library.
-   Add: The `unbind_domain` method / `unbind_textdomain` function.
-   Add: The `translate_locale` function.
-   Add: A second parameter for the `locale_converter` function.
-   Add: The new `translator` method, which...
-   Update: ... replaces all translation methods inside the `GettextReader` class.
-   Update: Renamed `locale_converter` into `convert_locale`.
-   Remove: The optional drop-in Gettext procedural function file.
-   Remove: Some unnessecary functions and methods.

Version 1.0.1
-------------
-   Add: An optional drop-in Gettext procedural function file.
-   Remove: The `init` function, which combines the initialization with `bind_domain`
-   Bugfix: The `set_default` return failure.

Version 1.0.0
-------------
-   Initial Release
