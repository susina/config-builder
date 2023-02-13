<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Tests;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;

trait VfsTrait
{
    private vfsStreamDirectory $root;

    public function getRoot(): vfsStreamDirectory
    {
        if (!isset($this->root)) {
            $this->root = vfsStream::setup();
        }

        return $this->root;
    }

    public function getConfigurationFile(): vfsStreamFile
    {
        $content = <<<NEON
Marvel:
    - Iron Man
    - Hulk
    - Thor
    - Captain America
Disney:
    - Mickey Mouse
    - Donald Duck
Dc: Superman
NEON;
        return vfsStream::newFile('config_builder.neon')->at($this->getRoot())->setContent($content);
    }

    public function getConfigurationDistFile(): vfsStreamFile
    {
        $content = <<<NEON
Marvel:
    - Iron Man
Disney:
    - Mickey Mouse
    - Donald Duck
NEON;
        return vfsStream::newFile('config_builder.neon.dist')->at($this->getRoot())->setContent($content);
    }

    public function populate(): void
    {
        $this->getConfigurationDistFile();
        $this->getConfigurationFile();
    }
}
