<?php declare(strict_types=1);
/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use Susina\ConfigBuilder\Tests\VfsTrait;

uses(VfsTrait::class)->in('Unit', 'Functional');

function fixtures_dir(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures';
}
