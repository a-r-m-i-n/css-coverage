<?php

use T3\CssCoverage\Middleware\CssCoverageMiddleware;

return [
    'frontend' => [
        'csscoverage/apply' => [
            'target' => CssCoverageMiddleware::class,
            'before' => [
                'typo3/cms-frontend/output-compression',
            ],
            'after' => [
                'typo3/cms-frontend/content-length-headers',
            ],
        ],
    ],
];
