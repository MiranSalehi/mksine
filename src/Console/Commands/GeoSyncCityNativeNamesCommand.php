<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Models\GeoCountry;
use Miran\Mksine\Services\Geo\GeoCityNativeNameSyncService;

class GeoSyncCityNativeNamesCommand extends Command
{
    protected $signature = 'mks:geo:sync-city-native-names
                            {--country=IR : ISO2 country code to sync}
                            {--dry-run : Preview changes without writing to the database}
                            {--source=cod24,wikidata : Comma-separated sources: cod24, wikidata}';

    protected $description = 'Normalize geo city native names from Wikidata (fa) and COD24 postal data';

    public function handle(GeoCityNativeNameSyncService $wikidataSync): int
    {
        if (! Schema::hasTable('geo_cities')) {
            $this->error('Geo tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $countryIso2 = strtoupper((string) $this->option('country'));
        $dryRun = (bool) $this->option('dry-run');
        $sources = array_filter(array_map(
            static fn (string $part): string => strtolower(trim($part)),
            explode(',', (string) $this->option('source')),
        ));

        $useWikidata = in_array('wikidata', $sources, true);
        $useCod24 = in_array('cod24', $sources, true);

        if (! $useWikidata && ! $useCod24) {
            $this->error('Choose at least one source: wikidata and/or cod24.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('Dry run — no database writes.');
        }

        $country = GeoCountry::query()->where('iso2', $countryIso2)->first();
        if ($country === null) {
            $this->error("Country {$countryIso2} was not found in geo_countries.");

            return self::FAILURE;
        }

        $this->info("Syncing native names for {$countryIso2}…");

        /** @var list<array<string, mixed>> $allChanges */
        $allChanges = [];
        $examined = 0;
        $updated = 0;
        $skipped = 0;
        $unmatchedCod24 = 0;

        if ($useWikidata) {
            $this->line('Source: Wikidata (fa labels)');
            $bar = $this->output->createProgressBar();
            $bar->setFormat(' %current% cities examined');

            $result = $wikidataSync->syncFromWikidata(
                countryIso2: $countryIso2,
                dryRun: $dryRun,
                progress: static function (int $count) use ($bar): void {
                    $bar->setProgress($count);
                },
            );

            $bar->finish();
            $this->newLine();
            $examined += $result['examined'];
            $updated += $result['updated'];
            $skipped += $result['skipped'];
            $allChanges = array_merge($allChanges, $result['changes']);
        }

        if ($useCod24) {
            $cod24SyncClass = 'Mksine\\EcomShippingCod24\\Services\\Cod24GeoCityNativeNameSync';
            if (! class_exists($cod24SyncClass)) {
                $this->warn('COD24 plugin is not available — skipping cod24 source.');
            } else {
                $this->line('Source: COD24 postal city list');
                $bar = $this->output->createProgressBar();
                $bar->setFormat(' %current% cities processed');

                $result = app($cod24SyncClass)->syncForCountry(
                    geoCountryId: (int) $country->id,
                    dryRun: $dryRun,
                    progress: static function () use ($bar): void {
                        $bar->advance();
                    },
                );

                $bar->finish();
                $this->newLine();
                $updated += $result['updated'];
                $skipped += $result['skipped'];
                $unmatchedCod24 = $result['unmatched'];
                $allChanges = array_merge($allChanges, $result['changes']);
            }
        }

        $this->newLine();
        $this->line("Examined (wikidata): {$examined}");
        $this->line("Updated: {$updated}");
        $this->line("Skipped: {$skipped}");
        if ($useCod24) {
            $this->line("Unmatched COD24: {$unmatchedCod24}");
        }

        if ($allChanges !== []) {
            $this->newLine();
            $this->table(
                ['ID', 'State', 'From', 'To', 'Source'],
                array_map(static fn (array $change): array => [
                    $change['id'],
                    $change['state'] ?? '—',
                    $change['from'],
                    $change['to'],
                    $change['source'],
                ], array_slice($allChanges, 0, 50)),
            );

            if (count($allChanges) > 50) {
                $remaining = count($allChanges) - 50;
                $this->line("… and {$remaining} more change(s).");
            }
        }

        return self::SUCCESS;
    }
}
