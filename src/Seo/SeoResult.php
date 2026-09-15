<?php

declare(strict_types=1);

namespace Miran\Mksine\Seo;

final readonly class SeoResult
{
    /**
     * @param  'good'|'ok'|'bad'  $trafficLight
     * @param  list<SeoFinding>  $seo
     * @param  list<SeoFinding>  $readability
     * @param  array{title: string, url: string, description: string}  $snippet
     */
    public function __construct(
        public int $overall,
        public string $trafficLight,
        public array $seo,
        public array $readability,
        public array $snippet,
    ) {}

    /**
     * @return list<SeoFinding>
     */
    public function allFindings(): array
    {
        return [...$this->seo, ...$this->readability];
    }
}
