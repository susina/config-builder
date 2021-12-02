The Configuration Builder class can be set up via the following methods:

### addFile

[addFile]() method receive one or more strings or SplFilInfo objects as parameters. The strings should contain the name,
or the full path name, of the configuration files to load.Use this method to add one or more elements to the list of
configuration files to load. I.e.:

```php
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;

$builder = new ConfigurationBuilder();

$builder->addFile('my-project-config.yaml.dist', 'my-project-config-yml');
```

## setFiles

[setFiles]() method receive an array of strings or SplFileInfo objects and set the list of the configuration file to load.
The strings should contain the name, or the full path name, of the configuration files to load.
This method __removes__ all the file names previously added.

```php
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;

$builder = new ConfigurationBuilder();
$configFiles = ['my-project.dist.xml', 'my-project.xml'];

$builder->setFiles($configFiles);
```

This method can only accept an iterator, containing strings or SplFileInfo, so you can pass to it an instance of a finder
object, i.e. [Symfony Finder](https://symfony.com/doc/current/components/finder.html):

```php
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;
use Symfony\Component\Finder\Finder;

$builder = new ConfigurationBuilder();
$finder = new Finder();

$finder->in('app/config')->name('*.json')->files();

$builder->setFiles($finder);
```