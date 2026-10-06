<?php

declare(strict_types=1);

use Miran\Mksine\Support\SvgSafety;

it('accepts an ordinary icon', function (string $svg) {
    expect(SvgSafety::isSafe($svg))->toBeTrue();
})->with([
    'plain shape' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 4h16v16H4z" fill="#f00"/></svg>'],
    'xml prolog' => ['<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="4"/></svg>'],
    'inline style block' => ['<svg xmlns="http://www.w3.org/2000/svg"><style>.a{fill:red}</style><rect class="a" width="10" height="10"/></svg>'],
    'style attribute' => ['<svg xmlns="http://www.w3.org/2000/svg"><rect style="fill:#00f;opacity:.5" width="10" height="10"/></svg>'],
    'same-document use' => ['<svg xmlns="http://www.w3.org/2000/svg"><defs><rect id="r" width="4" height="4"/></defs><use href="#r"/></svg>'],
    'comment' => ['<svg xmlns="http://www.w3.org/2000/svg"><!-- <script>alert(1)</script> --><rect width="1" height="1"/></svg>'],
    'anchor with relative href' => ['<svg xmlns="http://www.w3.org/2000/svg"><a href="/about"><rect width="1" height="1"/></a></svg>'],
]);

it('rejects a scriptable document', function (string $svg) {
    expect(SvgSafety::isSafe($svg))->toBeFalse();
})->with([
    'script element' => ['<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.domain)</script></svg>'],
    'script with cdata' => ['<svg xmlns="http://www.w3.org/2000/svg"><script><![CDATA[alert(1)]]></script></svg>'],
    'namespaced script' => ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:s="http://www.w3.org/2000/svg"><s:script>alert(1)</s:script></svg>'],
    'onload handler' => ['<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect width="1" height="1"/></svg>'],
    'onmouseover handler' => ['<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" onmouseover="alert(1)"/></svg>'],
    'uppercase handler' => ['<svg xmlns="http://www.w3.org/2000/svg" ONLOAD="alert(1)"><rect width="1" height="1"/></svg>'],
    'foreignObject html' => ['<svg xmlns="http://www.w3.org/2000/svg"><foreignObject width="10" height="10"><body xmlns="http://www.w3.org/1999/xhtml"><img src=x onerror="alert(1)"/></body></foreignObject></svg>'],
    'javascript anchor' => ['<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><rect width="1" height="1"/></a></svg>'],
    'javascript anchor via xlink' => ['<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><a xlink:href="javascript:alert(1)"><rect width="1" height="1"/></a></svg>'],
    'animate retargeting href' => ['<svg xmlns="http://www.w3.org/2000/svg"><a><animate attributeName="href" values="javascript:alert(1)"/><text>x</text></a></svg>'],
    'set element' => ['<svg xmlns="http://www.w3.org/2000/svg"><a><set attributeName="href" to="javascript:alert(1)"/><text>x</text></a></svg>'],
    'remote use' => ['<svg xmlns="http://www.w3.org/2000/svg"><use href="https://evil.test/p.svg#x"/></svg>'],
    'data uri use' => ['<svg xmlns="http://www.w3.org/2000/svg"><use href="data:image/svg+xml;base64,PHN2Zz48L3N2Zz4="/></svg>'],
    'iframe' => ['<svg xmlns="http://www.w3.org/2000/svg"><iframe src="https://evil.test"/></svg>'],
    'css import' => ['<svg xmlns="http://www.w3.org/2000/svg"><style>@import url("https://evil.test/x.css");</style></svg>'],
    'entity encoded handler' => ['<svg xmlns="http://www.w3.org/2000/svg"><a href="jav&#x61;script:alert(1)"><rect width="1" height="1"/></a></svg>'],
    'newline split scheme' => ['<svg xmlns="http://www.w3.org/2000/svg"><a href="java&#10;script:alert(1)"><rect width="1" height="1"/></a></svg>'],
]);

it('rejects a document that is not a well-formed svg', function (string $payload) {
    expect(SvgSafety::isSafe($payload))->toBeFalse();
})->with([
    'empty' => [''],
    'whitespace' => ["  \n\t "],
    'html polyglot' => ['<html><body><svg onload="alert(1)"></svg></body></html>'],
    'unclosed tag' => ['<svg xmlns="http://www.w3.org/2000/svg"><rect>'],
    'not xml at all' => ['GIF89a<script>alert(1)</script>'],
    'wrong root element' => ['<div xmlns="http://www.w3.org/1999/xhtml"><p>hi</p></div>'],
]);

it('rejects an internal dtd subset', function () {
    $billionLaughs = <<<'XML'
    <?xml version="1.0"?>
    <!DOCTYPE svg [
      <!ENTITY a "aaaaaaaaaa">
      <!ENTITY b "&a;&a;&a;&a;&a;&a;&a;&a;&a;&a;">
    ]>
    <svg xmlns="http://www.w3.org/2000/svg"><text>&b;</text></svg>
    XML;

    expect(SvgSafety::isSafe($billionLaughs))->toBeFalse();
});

it('rejects a document larger than the byte ceiling', function () {
    $padding = str_repeat('<rect width="1" height="1"/>', 100000);

    expect(strlen($padding))->toBeGreaterThan(SvgSafety::MAX_BYTES)
        ->and(SvgSafety::isSafe('<svg xmlns="http://www.w3.org/2000/svg">'.$padding.'</svg>'))->toBeFalse();
});

it('recognises the svg mime type', function () {
    expect(SvgSafety::isSvgMime('image/svg+xml'))->toBeTrue()
        ->and(SvgSafety::isSvgMime('image/svg+xml; charset=utf-8'))->toBeTrue()
        ->and(SvgSafety::isSvgMime('IMAGE/SVG+XML'))->toBeTrue()
        ->and(SvgSafety::isSvgMime('image/png'))->toBeFalse()
        ->and(SvgSafety::isSvgMime(null))->toBeFalse();
});

it('reports a missing or unreadable file as unsafe', function () {
    expect(SvgSafety::fileIsSafe(sys_get_temp_dir().'/mksine-not-here-'.uniqid().'.svg'))->toBeFalse()
        ->and(SvgSafety::fileIsSafe(sys_get_temp_dir()))->toBeFalse();
});

it('reads a file from disk', function () {
    $safe = tempnam(sys_get_temp_dir(), 'mks').'.svg';
    $unsafe = tempnam(sys_get_temp_dir(), 'mks').'.svg';

    file_put_contents($safe, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>');
    file_put_contents($unsafe, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

    expect(SvgSafety::fileIsSafe($safe))->toBeTrue()
        ->and(SvgSafety::fileIsSafe($unsafe))->toBeFalse();

    @unlink($safe);
    @unlink($unsafe);
});

it('keeps svg out of the default media allowlist', function () {
    $allowed = require __DIR__.'/../../../config/mksine.php';

    expect($allowed['media']['allowed_types'])->not->toContain('image/svg+xml');
});
