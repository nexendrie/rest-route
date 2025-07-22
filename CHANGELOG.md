Version 5.0.0
- package renamed to nexendrie/rest-route, changed namespace to Nexendrie\RestRoute
- allowed PHP 8
- raised minimal version of PHP to 7.4
- possible BC break: added/updated property types, type hints and return type hints
- added support for HEAD method
- fixed compatibility with nette/application 3.2, allowed nette/utils 4
- BC break: removed methods RestRoute::getDefaultFormat and getPath from public api, added virtual readonly properties $defaultFormat and $path instead
- BC break: made RestRoute::$module virtual readonly property
