Meteion
=======

Meteion is a straightforward PHP tool that loads FFXIV client data into a relational database.

# Compatibility

Meteion is compatible with Endwalker patch 6.1 [Newfound Adventure](https://na.finalfantasyxiv.com/endwalker/patch_6_1/).

# Getting Started

First, you need to extract the FFXIV client using [SaintCoinach](https://github.com/xivapi/SaintCoinach).

You can simply extract the client in the language you are interested in using the following command.

```shell
./SaintCoinach.Cmd.exe "C:\Program Files (x86)\SquareEnix\FINAL FANTASY XIV - A Realm Reborn" "lang English" rawexd
```

For more information, please refer to the [SaintCoinach documentation](https://github.com/xivapi/SaintCoinach#state-of-documentation).

# Requirement

Meteion works with PHP 7.1 and above.

# Installation

```shell
composer require ablaise/meteion
```

# Usage

You can use the following code to start loading the data.

```php
<?php

include './vendor/autoload.php';

use Meteion\Meteion;

$rawexd = '/path/to/rawexd';
$connection = [
	'dbname' => 'xiv',
	'user' => 'username',
	'password' => 'password',
	'host' => 'localhost',
	'port' => '5432',
	'driver' => 'pdo_pgsql',
];

$meteion = new Meteion($rawexd, $connection);
$meteion->run();
```

Please note that this may take some time depending on your server configuration. For now, only PostgreSQL is fully supported.

# Symfony Integration

See [MeteionBundle](https://github.com/ablaise/meteion).

# Known issues

At the moment, tables `chara_make_type` and `story` cannot be created.

# What's next?

* Multiple DBMS support
* Symfony integration (MeteionBundle)
* Speed improvement
* Better tests
* Error handling
* Bugfixes

This is still an alpha version under development, some serious changes may occur.
