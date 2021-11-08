<?php declare(strict_types = 1);
namespace T3\CssCoverage\Service;


use Sabberworm\CSS\CSSList\AtRuleBlockList;
use Sabberworm\CSS\CSSList\Document;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser as CssParser;
use Sabberworm\CSS\RuleSet\AtRuleSet;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\RuleSet\RuleSet;
use T3\CssCoverage\Service\Result\CssFile;
use T3\CssCoverage\Service\Result\SelectorCollection;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CssCoverageService
{

    public function run(Response $response): Response
    {
        $body = $response->getBody();
        $body->rewind();
        $html = $body->getContents();


//        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump();


        // Extract used classes from HTML
        $usedSelectors = $this->extractUsedClassSelectorsFromHtml($html);
//        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($usedSelectors); die;

        // Get CSS files
        $cssFiles = $this->extractCssFilesFromHtml($html);

        // Process CSS files
        foreach ($cssFiles as $cssFile) {

            // TODO make this configurable
            if (StringUtility::endsWith($cssFile->getSanitizedFilePath(), 'fonts.css')) {
                continue;
            }
//            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($cssFile->getSanitizedFilePath());

            $parser = new CssParser(file_get_contents(Environment::getPublicPath() . $cssFile->getSanitizedFilePath()));
            $cssDocument = $parser->parse();

            $removedSelectors = [];
//            $blocksToRemove = [];
            $i = 0;

            $function = function (DeclarationBlock $block, $document) use (&$removedSelectors, $usedSelectors): void {
                $selectors = [];
                $items = [];
                foreach ($block->getSelectors() as $selector) {
                    // explode css selector
                    $s = $selector->getSelector();

                    $parts = GeneralUtility::trimExplode(' ', $s, true);
                    foreach ($parts as $part) {
                        $parts0 = explode('>', $part);
                        foreach ($parts0 as $part0) {

                            $parts1 = explode('+', $part0);
                            foreach ($parts1 as $part1) {
                                if (strpos($part1, '.') !== false) {
                                    $parts2 = explode('.', $part1);
                                    foreach ($parts2 as $p) {
                                        if (!empty($p)) {
                                            $items[] = '.' . $p;
                                        }
                                    }
                                } else {
                                    $items[] = $part;
                                }
                            }
                        }
                    }

                    $items = array_unique($items);
                    foreach ($items as $index => $item) {
                        $items[$index] = preg_replace('/:nth.*?\(.*?\)/i', '', $item);
                        $items[$index] = str_replace([
                            ':disabled',
                            ':hover',
                            ':active',
                            ':focus',
                            '::before',
                            '::after',
                            ':first-child',
                            ':last-child',
                            ':-moz-focusring',
                            ':-moz-placeholder',
                            '::-ms-expand',
                            '::-ms-input-placeholder',
                            '::-webkit-input-placeholder',
                            '::-placeholder',
                        ], '', $items[$index]);
                    }

                    $items = array_unique($items);
                    // end

//                    if (strlen($s) > 50) {
//                        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($items, $s); die;
//                    }
                }
                $selectorExisting = $usedSelectors->has($items);
                if (!$selectorExisting) {
                    $document->removeDeclarationBlockBySelector($selector, true);
                    $document->remove($block);
                    $removedSelectors[] = $selectors;
                }
            };


            /** @var DeclarationBlock|AtRuleBlockList $block */
            foreach ($cssDocument->getContents() as $block) {
                if ($block instanceof DeclarationBlock) {
                    $function($block, $cssDocument);
                }
                if ($block instanceof AtRuleBlockList) { // Media query
                    foreach ($block->getContents() as $block2) {
                        if ($block2 instanceof DeclarationBlock) {
                            $function($block2, $block);
                        }
                    }
                }
            }

//            $muh = $cssDocument->getAllDeclarationBlocks();
            /** @var DeclarationBlock $all */
//            foreach($muh as $all) {
//                $s = $all->getSelectors();
//                \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($s);
//                $cssDocument->remove($all);
//            }
//            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($muh); die;
//            $muh->

//            $a = $cssDocument->getContents();
//            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($a); die;


//            foreach ($removedSelectors as $removedSelector) {
//                $cssDocument->removeDeclarationBlockBySelector($removedSelector, true);
//            }

//            $rR = [];
//            foreach($removedSelectors as $r) {
//                $rR[] = implode(' ', $r);
//            }
//                        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($rR); die;


            $inlineCss = $cssDocument->render(OutputFormat::createCompact());
//            $inlineCss = $cssDocument->render();

            $info = '<!-- ' . $cssFile->getSanitizedFilePath() . ' -->';
            $html = str_replace($cssFile->getHtmlCode(), $info . PHP_EOL . '<style>' . $inlineCss . '</style>', $html);
        }




        $body->rewind();
        $body->write($html);

//        die;


        return $response;
    }

    /**
     * @return array|CssFile[]
     */
    private function extractCssFilesFromHtml(string $html): array
    {
        $dom = $this->parseHtml($html);
        $links = $dom->getElementsByTagName('link');

        $result = [];
        /** @var \DOMElement $link */
        foreach ($links as $link) {
            if ($link->hasAttribute('rel') && $link->getAttribute('rel') === 'stylesheet' &&
                $link->hasAttribute('type') && $link->getAttribute('type') === 'text/css' &&
                $link->hasAttribute('href')
            ) {
                $href = $link->getAttribute('href');
                $result[] = new CssFile($href, $dom->saveHTML($link));
            }
        }
        return $result;
    }

    private function extractUsedClassSelectorsFromHtml(string $html): SelectorCollection
    {
        $dom = $this->parseHtml($html);

        $tagNames = [];
        $classNames = [];
        $ids = [];
        $names = [];
        $selectors = [];

        $body = $dom->getElementsByTagName('body')[0];

        /** @var \DOMElement $element */
        foreach ($body->getElementsByTagName('*') as $element) {
            $tagNames[] = $element->nodeName;
            if ($element->hasAttribute('id') || $element->hasAttribute('class') || $element->hasAttribute('name')) {
                if ($element->hasAttribute('id')) {
                    $ids[] = $id = $element->getAttribute('id');
                }
                if ($element->hasAttribute('class')) {
                    $classNames[] = $class = $element->getAttribute('class');
                }
                if ($element->hasAttribute('name')) {
                    $names[] = $element->getAttribute('name');
                }
            }

            $selector = [$element->nodeName];
            $selectorString = $element->nodeName;
            if (isset($id)) {
                $selector[] = '#' . $id;
                $selectorString .= '#' . $id;
            }
            if (isset($class)) {
                $classes = GeneralUtility::trimExplode(' ', $class, true);
                $selector += $classes;
                $selectorString .= '.' . implode('.', $classes);
            }
            $selectors[$selectorString] = $selector;
        }

        $tagNames = array_unique($tagNames);
        $classNames = array_unique($classNames);
        $ids = array_unique($ids);
        $names = array_unique($names);

        return new SelectorCollection($tagNames, $classNames, $ids, $names, $selectors);
    }

    private function parseHtml(string $html)
    {
        $xml = new \DOMDocument();
        libxml_use_internal_errors(true);
        $success = $xml->loadHTML($html);

        if (!$success) {
            throw new \RuntimeException('Error parsing HTML!');
        }

        return $xml;

    }


}
