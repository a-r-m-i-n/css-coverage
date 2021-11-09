<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}


if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'] = [];
}
array_unshift(
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'],
    \T3\CssCoverage\Service\CssCoverageService::class . '->contentPostProc'
);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] =
    \T3\CssCoverage\Service\CssCoverageService::class . '->contentPostProc';
