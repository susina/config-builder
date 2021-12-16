The Configuration Builder supports the definition of some _parameters_ in your configuration file (any format). This functionality is inspired on [Symfony ParameterBag](https://github.com/symfony/symfony/blob/5.4/src/Symfony/Component/DependencyInjection/ParameterBag/ParameterBag.php).

A parameter is a previously defined property, put between `%` special character. When the builder found a parameter, it simply replaces its placeholder with the previously defined value. In example:

```yaml
general:
    project: MyProject

paths:
    projectDir: /home/%project%
```

It becomes:

```yaml
general:
    project: MyProject

paths:
    projectDir: /home/MyProject
```

You can escape the special character `%` by doubling it:

```yaml
general:
    project: 100%%
```

`project` property now contains the string `'100%'`.

### Special parameters: environment variables ###

The parameter `env` is used to specify an environment variable. Many hosts give services or credentials via environment variables and you can use them in your configuration file via `env.variable` syntax.
In example, let's suppose to have the following environment variables:

```php
<?php

$_ENV['host']   = '192.168.0.54'; //Database host name
$_ENV['dbName'] = 'myDB'; //Database name
```

In your configuration file you can write:

```yaml
project:
  database:
      default:
          adapter: mysql
          dsn: mysql:host=%env.host%;dbname=%env.dbName%
```

and it becomes:

```yaml
project:
  database:
      default:
          adapter: mysql
          dsn: mysql:host=192.168.0.54;dbname=myDB
```