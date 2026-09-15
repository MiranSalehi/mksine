<?php

return [
    'analysis_heading' => 'SEO analysis',
    'advisory_hint' => 'Advisory only — this score never blocks publish and does not rewrite your copy.',
    'snippet_heading' => 'Search preview',
    'snippet_untitled' => 'Untitled',
    'snippet_no_description' => 'Add a meta description to control this snippet.',
    'no_findings' => 'No checks in this panel.',
    'focus_keyphrase' => 'Focus keyphrase',
    'focus_keyphrase_helper' => 'The phrase you want this page to rank for. Used only for on-page scoring.',
    'lights' => [
        'good' => 'Good',
        'ok' => 'Needs improvement',
        'bad' => 'Poor',
    ],
    'panels' => [
        'seo' => 'SEO',
        'readability' => 'Readability',
    ],
    'checks' => [
        'keyphrase_missing' => 'Set a focus keyphrase to score keyphrase checks.',
        'keyphrase_in_title' => [
            'good' => 'The focus keyphrase appears in the title.',
            'bad' => 'The focus keyphrase does not appear in the title.',
        ],
        'keyphrase_in_meta_title' => [
            'good' => 'The focus keyphrase appears in the SEO title.',
            'bad' => 'The focus keyphrase does not appear in the SEO title.',
        ],
        'keyphrase_in_meta_description' => [
            'good' => 'The focus keyphrase appears in the meta description.',
            'bad' => 'The focus keyphrase does not appear in the meta description.',
        ],
        'keyphrase_in_slug' => [
            'good' => 'The focus keyphrase appears in the slug.',
            'bad' => 'The focus keyphrase does not appear in the slug.',
        ],
        'keyphrase_in_intro' => [
            'good' => 'The focus keyphrase appears in the first 300 characters.',
            'bad' => 'The focus keyphrase does not appear near the start of the content.',
        ],
        'meta_title_length' => [
            'good' => 'SEO title length is in the 50–60 character band (:length).',
            'ok' => 'SEO title length is close to 50–60 characters (:length).',
            'bad' => 'SEO title should be about 50–60 characters (now :length).',
        ],
        'meta_description_length' => [
            'good' => 'Meta description length is in the 120–160 character band (:length).',
            'ok' => 'Meta description length is close to 120–160 characters (:length).',
            'bad' => 'Meta description should be about 120–160 characters (now :length). Aim for 150–160.',
        ],
        'content_word_count' => [
            'good' => 'Content is long enough (:count words).',
            'ok' => 'Content is a bit short (:count words). Aim for 300+.',
            'bad' => 'Add more content (:count words).',
        ],
        'internal_outbound_links' => [
            'good' => 'Content has internal links (:internal internal, :outbound outbound).',
            'ok' => 'Add internal links where they help the reader (:internal internal, :outbound outbound).',
        ],
        'heading_presence' => [
            'good' => 'Content uses headings (H1–H3).',
            'bad' => 'Add at least one heading (H1–H3).',
        ],
        'paragraph_length' => [
            'good' => 'Paragraphs are a readable length.',
            'ok' => 'Some paragraphs are long (:count words).',
            'bad' => 'Split long paragraphs (:count words).',
        ],
        'consecutive_long_sentences' => [
            'good' => 'Sentence length looks readable.',
            'ok' => 'Some sentences are long.',
            'bad' => 'Several long sentences sit next to each other.',
        ],
        'flesch_reading_ease' => [
            'good' => 'English reading ease looks good (Flesch :score).',
            'ok' => 'English reading ease could be clearer (Flesch :score).',
            'bad' => 'English reading ease is difficult (Flesch :score). This metric is English-only.',
        ],
    ],
];
