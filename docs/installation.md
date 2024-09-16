Install the library via composer:

```bash
composer require susina/config-builder
```

Additionally, you should install the library you could need to load your configuration file format. In example, if
you have to load some __yaml__ files:

```
composer require symfony/yaml
```

## List of supported file formats and libraries to load

Susina Configuration Builder doesn't pre-install the libraries needed to load your configuration files, to avoid adding
unnecessary dependencies to your project.
Choosing one or more of these libraries is your responsibility and it depends
on which format you prefer.

Here are the list of supported file formats and the libraries to load:

|Format|Supported file extension|Library to load|Installation|
|------|------------------------|---------------|-------|
|_json_|.json, .json.dist|Bundled with PHP by default|enabled by default|
|_neon_|.neon, .neon.dist|[Nette Neon](https://ne-on.org/)|`composer require nette/neon`|
|_php_|.php, .php.dist|PHP itself|none|
|_xml_|.xml, .xml.dist|[Susina Xml To Array](https://github.com/susina/xml-to-array)|Automatically installed by composer|
|_yaml_|.yaml, .yml, .yaml.dist, .yml.dist|[Symfony Yaml](https://symfony.com/doc/current/components/yaml.html)|`composer require symfony/yaml`|