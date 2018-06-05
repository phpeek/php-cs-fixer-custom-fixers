# PHP CS Fixer: custom fixers

[![Latest Stable Version](https://img.shields.io/packagist/v/kubawerlos/php-cs-fixer-custom-fixers.svg)](https://packagist.org/packages/kubawerlos/php-cs-fixer-custom-fixers)
[![PHP Version](https://img.shields.io/badge/php-%5E7.1-8892BF.svg)](https://php.net)
[![License](https://img.shields.io/github/license/kubawerlos/php-cs-fixer-custom-fixers.svg)](https://packagist.org/packages/kubawerlos/php-cs-fixer-custom-fixers)
[![Build Status](https://img.shields.io/travis/kubawerlos/php-cs-fixer-custom-fixers/master.svg)](https://travis-ci.org/kubawerlos/php-cs-fixer-custom-fixers)
[![Code coverage](https://img.shields.io/codecov/c/github/kubawerlos/php-cs-fixer-custom-fixers.svg?label=code%20coverage)](https://codecov.io/gh/kubawerlos/php-cs-fixer-custom-fixers)

A set of custom fixers for [PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer).

## Installation
PHP CS Fixer custom fixers can be installed by running:
```bash
composer require --dev kubawerlos/php-cs-fixer-custom-fixers
```


## Usage
In your PHP CS Fixer configuration register fixers and use them:
```diff
 <?php
 
 return PhpCsFixer\Config::create()
+    ->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers())
     ->setRules([
         '@PSR2' => true,
         'array_syntax' => ['syntax' => 'short'],
+        PhpCsFixerCustomFixers\Fixer\NoLeadingSlashInGlobalNamespaceFixer::name() => true,
+        PhpCsFixerCustomFixers\Fixer\NoTwoConsecutiveEmptyLinesFixer::name() => true,
     ]);

```


## Fixers
- **NoLeadingSlashInGlobalNamespaceFixer** - when in global namespace there should be no leading slash for class.
```diff
 <?php 
-$x = new \Foo();
+$x = new Foo();
 namespace Bar;
 $y = new \Baz();

```

- **NoPhpStormGeneratedCommentFixer** - there should be no comment generated by PhpStorm.
```diff
 <?php
-/**
- * Created by PhpStorm.
- * User: root
- * Date: 01.01.70
- * Time: 12:00
- */
 namespace Foo;

```

- **NoTwoConsecutiveEmptyLinesFixer** - there should be no two consecutive empty lines in code.
```diff
 <?php
 namespace Foo;
 
-
 class Bar {};

```

- **NoUselessClassCommentFixer** - there should be no comment like: "Class Foo\Bar".
```diff
 <?php
 /**
- * Class Foo\Bar
  * Class to do something
  */
 class Foo {}

```

- **NoUselessConstructorCommentFixer** - there should be no comment like: "Class Foo\Bar".
```diff
 class Foo
 {
     /**
-     * Foo constructor
      */
     public function __construct() {}
 }

```

- **PhpdocNoIncorrectVarAnnotationFixer** - `@var` should be correct in the code.
```diff
 <?php
-/** @var LoggerInterface $foo */
+
 $bar = new Logger();

```

- **PhpdocParamTypeFixer** - adds missing type for `@param` annotation.
```diff
 <?php
 /**
  * @param string $foo
- * @param        $bar
+ * @param mixed  $bar
  */

```


## Contributing
Request a feature or report a bug by creating [issue](https://github.com/kubawerlos/php-cs-fixer-custom-fixers/issues).

Alternatively, fork the repo, develop your changes, regenerate `README.md`:
```bash
src/Readme/run > README.md
```
make sure all checks pass:
```bash
vendor/bin/phpcs --exclude=Generic.Files.LineLength --report-full --standard=PSR2 src tests
vendor/bin/php-cs-fixer fix --config=tests/php-cs-fixer.config.php --diff --dry-run
vendor/bin/types-checker src tests
vendor/bin/psalm --config=tests/psalm.xml
phpdbg -qrr vendor/bin/phpunit --configuration=tests
```
and submit a pull request.
