<?php

declare(strict_types=1);

use Miran\Mksine\Core\Plugins\PluginManifestSource;

it('reads top-level metadata from a realistic manifest', function (): void {
    $source = <<<'PHP'
    <?php

    /**
     * Plugin Manifest for mks-backup
     */

    return [
        // Unique plugin identifier (must match folder name)
        'id' => 'mks-backup',
        'name' => 'MKSine Backup',
        'version' => '0.1.0',
        'description' => 'Database and selected-file backups.',
        'author' => [
            'name' => 'Someone Else',
            'version' => '9.9.9',
        ],
        'requires' => ['php' => '8.4'],
    ];
    PHP;

    expect(PluginManifestSource::scan($source))->toBe([
        'id' => 'mks-backup',
        'name' => 'MKSine Backup',
        'version' => '0.1.0',
    ]);
});

it('ignores keys nested inside sub-arrays', function (): void {
    $source = "<?php return ['author' => ['id' => 'nested-id'], 'id' => 'real-id'];";

    expect(PluginManifestSource::scan($source)['id'])->toBe('real-id');
});

it('ignores keys nested inside legacy array() syntax', function (): void {
    $source = "<?php return array('author' => array('id' => 'nested-id'), 'id' => 'real-id');";

    expect(PluginManifestSource::scan($source)['id'])->toBe('real-id');
});

it('reports a computed id as missing rather than guessing', function (): void {
    $source = "<?php \$prefix = 'mks'; return ['id' => \$prefix.'-backup'];";

    expect(PluginManifestSource::scan($source)['id'])->toBeNull();
});

it('returns nulls for sources that are not manifests', function (string $source): void {
    expect(PluginManifestSource::scan($source))->toBe(['id' => null, 'name' => null, 'version' => null]);
})->with([
    'empty' => '',
    'not php' => 'just some text',
    'no return' => '<?php class Foo {}',
    'unparsable' => '<?php return [ ',
]);

it('never executes the manifest it scans', function (): void {
    $canary = sys_get_temp_dir().'/mksine-manifest-canary-'.bin2hex(random_bytes(4));

    $source = sprintf(
        "<?php file_put_contents(%s, 'pwned'); return ['id' => 'evil-plugin'];",
        var_export($canary, true),
    );

    expect(PluginManifestSource::scan($source)['id'])->toBe('evil-plugin')
        ->and(file_exists($canary))->toBeFalse();
});

it('does not tokenise oversized sources', function (): void {
    $source = '<?php return [\'id\' => \'big\', \'pad\' => \''.str_repeat('x', 300000)."'];";

    expect(PluginManifestSource::scan($source)['id'])->toBeNull();
});
