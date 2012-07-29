This is a custom autoloader for Contao 2
========================================

The module register itself as new autoloader by rewriting the functions.php
and replace the default __autoload function with an spl_autoload function.

The module is only usefull if you use Contao 2,
Contao 3 allready use spl_autoload and does not need this modification.

How to use
----------

Just install it, the module will update the functions.php by itself.
Even if you update your Contao installation, the functions.php get updated just in time.

Register namespaces
-------------------

The autoloader allow register of custom namespaces and paths.

```php
Autoloader::registerNamespace('My\\Cool\\Namespace', 'path/to/my/classes');
```

The autoloader follow the PSR-0 logic, means you need to create the corresponding directory structure.
For example, if you try to autoload the class `My\Cool\Namespace\MyClass`,
the class have to be defined in the file `path/to/my/classes/My/Cool/Namespace/MyClass.php`.

If this is to long for you, you can set the `subdir` flag to `true`.

```php
Autoloader::registerNamespace('My\\Cool\\Namespace', 'path/to/my/classes', true);
```

Setting the `subdir` flag to `true` means the given namespace starts at the given path.
In this case, the class `My\Cool\Namespace\MyClass` have to be defined in the file
`path/to/my/classes/MyClass.php`.
And the class In this case, the class `My\Cool\Namespace\SubNS\OtherClass` have to be defined in the file
`path/to/my/classes/SubNS/OtherClass.php`.