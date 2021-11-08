<?php declare(strict_types = 1);
namespace T3\CssCoverage\Service\Result;


use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SelectorCollection
{
    private array $tagNames;
//    private array $classNameCombinations = [];
    private array $classNames = [];
    private array $ids;
    private array $names;
    private array $selectors;

    private array $special = [
        '*',
        ':root',
        '::after',
        '::before',
        'html',
        'body',
    ];

    // TODO Make configurable
    private array $wildcards = [
        '.nGY*',
        '.carousel*',
        '.dropdown*.show*',
        '.collapse*',
        '*.material-slider*',
        '.navbar-shrink',
        'data-ce-*',
    ];

    public function __construct(array $tagNames, array $classNames, array $ids, array $names, array $selectors)
    {
        $this->tagNames = $tagNames;
        foreach ($classNames as $className) {
            $classNameParts = GeneralUtility::trimExplode(' ', $className, true);
            sort($classNameParts);
//            $this->classNameCombinations[$className] = $classNameParts;
            $this->classNames = array_merge($this->classNames, $classNameParts);
        }
        $this->classNames = array_unique($this->classNames);
        $this->classNames = array_flip($this->classNames);
        unset($this->classNames['']);
        $this->classNames = array_flip($this->classNames);

        $this->ids = $ids;
        $this->names = $names;
        $this->selectors = $selectors;
    }

    public function has(array $selectorParts)
    {
//        foreach ($selectors as $selector) {
//            $selectorParts = explode(' ', $selector);
        foreach ($selectorParts as $selectorPart) {
//            $selectorPart = str_replace($this->ignoreInSelector, '', $selectorPart);

            // Wildcards and hardcoded specials
            if ($this->isWildcarded($selectorPart) || in_array($selectorPart, $this->special, true)) {
                return true;
            }

            // Classes
            if (StringUtility::beginsWith($selectorPart, '.')) {
//                if (strpos($selectorPart, 'nav-tabs') !== false) {
//                    \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(in_array(substr($selectorPart, 1), $this->classNames, true), $selectorPart); die;
//                }
                if (in_array(substr($selectorPart, 1), $this->classNames, true)) {
                    return true;
                }
            }

            // Ids
            if (StringUtility::beginsWith($selectorPart, '#')) {
                $idName = substr($selectorPart, 1);
                if (in_array($idName, $this->ids, true)) {
                    return true;
                }
            }

            // TODO check for attribute?

            // Tags
            if (in_array($selectorPart, $this->tagNames, true)) {
                return true;
            }
        }
//        }

        return false;
    }

    public function getTagNames(): array
    {
        return $this->tagNames;
    }

    public function getClassNames(): array
    {
        return $this->classNames;
    }

    public function getIds(): array
    {
        return $this->ids;
    }

    public function getNames(): array
    {
        return $this->names;
    }

    private function isWildcarded(string $selectorPart): bool
    {
        foreach ($this->wildcards as $wildcard) {
            if (fnmatch($wildcard, $selectorPart)) {
                return true;
            }
        }

        return false;
    }
}
