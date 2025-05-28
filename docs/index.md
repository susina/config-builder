# Susina Configuration Builder

Susina Configuration Builder is a library to load and build configuration objects or arrays.
It's based on [Symfony Config](https://symfony.com/doc/current/components/config.html) and it's heavily inspired on
[Propel configuration sub-system](https://github.com/propelorm/Propel2/tree/master/src/Propel/Common/Config).

---

Building a configuration is a three-step process:

1. load the parameters from some configuration files
2. process the loaded parameters to normalize and validate them
3. return an array of cleaned parameters or a configuration object

We ship loaders for the following file formats:

- **.json** via PHP json extension
- **.neon** via [Nette Neon](https://github.com/nette/neon) library
- **.php**
- **.xml** via [Susina xml to array](https://github.com/susina/xml-to-array) library
- **.yml** via [Symfony Yaml](https://symfony.com/doc/current/components/yaml.html) component

The Configuration Builder can populate any object whose constructor, or any other initialize method, takes an array as a parameter.
