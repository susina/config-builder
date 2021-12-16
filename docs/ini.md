The Configuration Builder supports an `ini` file format _with steroids_, which permits to define more complex structures, inspired on [Zend\Config\IniReader](http://framework.zend.com/manual/2.3/en/modules/zend.config.reader.html) class. In this _nested_ way, you can define an ini hierarchy via a string, where each level is separated by a dot. For instance:

```ini
; Classical way
[first_section]
foo = bar
bar = baz

;Nested way
first_section.foo = bar
first_section.bar = baz
```

You can define also a deeper hierarchy, with more than two levels:

```ini
section_1.sub_section_1.sub_sub_section.foo = bar
section_1.sub_section_2.sub_sub_section.bar = baz
```

and this will get converted into the following array:

```php
<?php

[
    'section_1' => [
        'sub_section_1' => [
            'sub_sub_section' => [
                'foo' => 'bar'
            ]
        ],
        'sub_section_2' => [
            'sub_sub_section' => [
                'bar' => 'baz'
            ]
        ]
    ]
]
```

Of course, you can mix both syntaxes, as you can see in the following sample:

```ini
[database]

connections.mysource.adapter    = mysql
connections.mysource.classname  = Propel\Runtime\Connection\DebugPDO
connections.mysource.dsn        = mysql:host=localhost;dbname=mydb
connections.mysource.user       = root

connections.yoursource.adapter    = mysql
connections.yoursource.classname  = Propel\Runtime\Connection\DebugPDO
connections.yoursource.dsn        = mysql:host=localhost;dbname=yourdb
connections.yoursource.user       = root
```