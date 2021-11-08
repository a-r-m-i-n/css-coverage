<?php

/*  | This extension is made for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2021 Armin Vieweg <info@v.ieweg.de>
 */

// phpcs:disable
$EM_CONF[$_EXTKEY] = [
    'title' => 'CSS Coverage',
    'description' => '',
    'category' => 'misc',
    'shy' => 0,
    'version' => '0.1.0-dev',
    'state' => 'experimental',
    'author' => 'Armin Vieweg',
    'author_email' => 'info@v.ieweg.de',
    'author_company' => 'v.ieweg Webentwicklung',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.0.99',
            'typo3' => '10.4.0-11.99.99',
        ],
    ],
    'autoload' => [
        'psr-4' => ['T3\\CssCoverage\\' => 'Classes'],
    ],
];
// @codingStandardsIgnoreEnd
// phpcs:enable
