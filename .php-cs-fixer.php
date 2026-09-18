<?php 

declare(strict_types=1);

/*
 * Copyright (c) Cristiano Cinotti
 * 
 * This file is part of susina/config-builder package, released under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$finder = new PhpCsFixer\Finder()->in(__DIR__ . '/src')->in(__DIR__ . '/tests');

return new PhpCsFixer\Config()
    ->setRules([
        '@PER-CS3x0' => true,
        'header_comment' => [
            'header' => "Copyright (c) Cristiano Cinotti

This file is part of susina/config-builder package, released under the APACHE-2 license.
For the full copyright and license information, please view the LICENSE file distributed 
with this source code.",
        ]
    ])
    ->setFinder($finder)
;
