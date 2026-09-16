<?php

declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Tests;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public private(set) vfsStreamDirectory $root {
        get => $this->root ??= $this->setUpVfs();
    }

    public string $configFile {
        get => "{$this->root->url()}/config_builder.neon";
    }

    public string $distFile {
        get => "{$this->root->url()}/config_builder.neon.dist";
    }


    public function runnungOnWindows(): bool
    {
        return str_contains(strtoupper(php_uname("s")), 'WIN');
    }

    private function setUpVfs(): vfsStreamDirectory
    {
        $root = vfsStream::setup();

        //Add default configuration file
        vfsStream::newFile('config_builder.neon')->at($root)->setContent(
            "
Marvel:
    - Iron Man
    - Hulk
    - Thor
    - Captain America
Disney:
    - Mickey Mouse
    - Donald Duck
Dc: Superman
",
        );

        //Add default configuration dist file
        vfsStream::newFile('config_builder.neon.dist')->at($root)->setContent(
            "
Marvel:
    - Iron Man
Disney:
    - Mickey Mouse
    - Donald Duck
",
        );

        //Add database_config.neon file
        vfsStream::newFile('database_config.neon')->at($root)->setContent(
            "
auto_connect: true
default_connection: mysql
connections:
  mysql:
    host:     localhost
    driver:   mysql
    username: user
    password: pass
  sqlite:
    host:     localhost
    driver:   sqlite
    username: user
    password: pass
  pgsql:
      host:     localhost
      driver:   postgresql
      username: user
      password: pass
",
        );

        //Add database_config.xml file
        vfsStream::newFile('database_config.xml')->at($root)->setContent(
            "
<database name=\"database_test\">
    <auto_connect>true</auto_connect>
    <default_connection>mysql</default_connection>
    <connections>
        <mysql>
            <host>localhost</host>
            <driver>mysql</driver>
            <username>user</username>
            <password>pass</password>
        </mysql>
        <sqlite>
            <host>localhost</host>
            <driver>sqlite</driver>
            <username>user</username>
            <password>pass</password>
        </sqlite>
    </connections>
</database>
",
        );

        //Add database_config.yml file
        vfsStream::newFile('database_config.yml')->at($root)->setContent(
            "
auto_connect: true
default_connection: mysql
connections:
  mysql:
    host: localhost
    driver: mysql
    username: user
    password: pass
  sqlite:
    host: localhost
    driver: sqlite
    username: user
    password: pass
",
        );

        //Add replaces_config.yml
        vfsStream::newFile('replaces_config.yml')->at($root)->setContent(
            "
auto_connect: true
default_connection: mysql
connections:
  mysql:
    host:     localhost
    driver:   mysql
    username: user
    password: pass
  sqlite:
    host:     '%kernel_dir%/database.sqlite'
    driver:   sqlite
    username: user
    password: pass
",
        );

        //Add cache directory
        vfsStream::newDirectory('cache_dir')->at($root);

        //Add test directory
        vfsStream::newDirectory('test_dir')->at($root);

        return $root;
    }
}
