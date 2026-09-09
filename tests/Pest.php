<?php

declare(strict_types=1);

use Miran\Mksine\Tests\TestCase as PackageTestCase;

if (class_exists(\Tests\TestCase::class)) {
    uses(\Tests\TestCase::class)->in(__DIR__.'/Unit', __DIR__.'/Feature');
} else {
    uses(PackageTestCase::class)->in(__DIR__.'/Unit', __DIR__.'/Feature');
}
