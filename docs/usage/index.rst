Getting Started
===============

.. _Nette Neon: https://ne - on.org/
.. _PHP Toml: https://php-collective.github.io/toml/
.. _Susina Xml To Array: https://github.com/susina/xml-to-array
.. _Symfony Yaml: https://symfony.com/doc/current/components/yaml.html


Install the library via `composer <http://getcomposer.org>`_:

.. code:: bash
    composer require susina/config-builder


and you're ready to go!


Additionally, you should install the library you could need to load your configuration file format. In example, if
you have to load some **yaml** files:

.. code:: bash
    composer require symfony/yaml


List of supported file formats and libraries to load
----------------------------------------------------

Susina Configuration Builder doesn't pre-install the libraries needed to load your configuration files, to avoid adding unnecessary dependencies to your project.
Choosing one or more of these libraries is your responsibility and it depends on which configuration format you prefer.

Here are the supported file formats and the libraries to load:

+--------+-----------------------+-----------------------------+------------------------------------------+
| Format |       Supported       |       Library to load       | Installation                             |
|        |     file extension    |                             |                                          |
+========+=======================+=============================+==========================================+
| *json* | .json, .json.dist     | Bundled with PHP            | Enabled by default                       |
+--------+-----------------------+-----------------------------+------------------------------------------+
| *neon* | .neon, .neon.dist     | `Nette Neon`_               | ``composer require nette/neon``          |
+--------+-----------------------+-----------------------------+------------------------------------------+
| *php*  | .php, .php.dist       | PHP itself                  | none                                     |
+--------+-----------------------+-----------------------------+------------------------------------------+
| *toml* | .toml, .toml.dist     | `PHP Toml`_                 | ``composer require php-collective/toml`` |
+--------+-----------------------+-----------------------------+------------------------------------------+
| *xml*  | .xml, .xml.dist       | `Susina Xml To Array`_      | Automatically installed                  |
|        |                       |                             | by composer                              |
+--------+-----------------------+-----------------------------+------------------------------------------+
| *yaml* | .yaml, .yaml.dist,    | `Symfony Yaml`_             | ``composer require symfony/yaml``        |
|        | .yml, .yml.dist       |                             |                                          |
+--------+-----------------------+-----------------------------+------------------------------------------+

.. toctree::
   :maxdepth: 2

   basic_usage
   parameters
   example