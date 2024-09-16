# Configuration Builder

![Tests](https://github.com/susina/config-builder/actions/workflows/test.yml/badge.svg)
[![Maintainability](https://api.codeclimate.com/v1/badges/df031168e25a1206df64/maintainability)](https://codeclimate.com/github/susina/config-builder/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/df031168e25a1206df64/test_coverage)](https://codeclimate.com/github/susina/config-builder/test_coverage)
![GitHub](https://img.shields.io/github/license/susina/config-builder)

Susina Configuration Builder is a library to load parameters, from configuration files, and build configuration objects
or arrays. It's based on [Symfony Config](https://symfony.com/doc/current/components/config.html) and 
[Propel configuration sub-system](https://github.com/propelorm/Propel2/tree/master/src/Propel/Common/Config).

Supported configuration file formats are:

- __.json__ via PHP json extension
- __.neon__ via [Nette Neon](https://github.com/nette/neon) library
- __.php__
- __.xml__ via PHP SimpleXml, Xml and Dom extensions
- __.yml__ via [Symfony Yaml](https://symfony.com/doc/current/components/yaml.html) component


## Installation

You can install the library via [composer](http://getcomposer.org):

```bash
composer require susina/config-builder
```
then you should install the library you need to load your preferred configuration file format:

```bash
# Suppose you want to use yaml format
composer require symfony/yaml
```

## Usage

See the [documentation site](https://susina.github.io/config-builder).


## Issues

Please, open an issue on [Github repository](https://github.com/susina/config-builder/issues).

## Contributing

Fork the repository and submit a pull request. For further information see the [documentation site](https://susina.github.io/config-builder)

## Licensing

This library is released under [Apache 2.0 license](LICENSE)