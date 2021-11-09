<?php declare(strict_types = 1);
namespace T3\CssCoverage\Service;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Sabberworm\CSS\CSSList\AtRuleBlockList;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser as CssParser;
use Sabberworm\CSS\Property\Selector;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Symfony\Component\CssSelector\Exception\ExpressionErrorException;
use Symfony\Component\DomCrawler\Crawler;
use T3\CssCoverage\Configuration;
use T3\CssCoverage\Service\Result\CssFile;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class CssCoverageService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function contentPostProc(array $params, TypoScriptFrontendController $tsfe)
    {
        $content = $this->run($tsfe->content, $tsfe->tmpl->setup['plugin.']['tx_csscoverage.'] ?? []);

        $tsfe->content = $content;
    }

    public function run(string $html, array $configuration): string
    {
        if (empty($configuration)) {
            $this->logger->debug('Given TypoScript configuration is empty. Because of this, EXT:css_coverage remains disabled.');
            return $html;
        }

        /** @var Configuration $config */
        $config = GeneralUtility::makeInstance(Configuration::class, $configuration);
        if (!$config->isEnabled()) {
            return $html;
        }

        $this->logger->debug('CssCoverageService is running');

        // Get CSS files
        $cssFiles = $this->extractCssFilesFromHtml($html, $config);

        $crawler = new Crawler($html);

        // Process CSS files
        foreach ($cssFiles as $cssFile) {
            $parser = new CssParser($contents = file_get_contents(Environment::getPublicPath() . $cssFile->getSanitizedFilePath()));
            $originalSize = strlen(($contents));
            unset($contents);
            $cssDocument = $parser->parse();

            /** @var DeclarationBlock $block */
            foreach ($cssDocument->getContents() as $block) {

                if ($block instanceof DeclarationBlock) {
                    $selectors = $block->getSelectors();
                    $removeIt = true;
                    /** @var Selector $selector */
                    foreach ($selectors as $selector) {
                        if ($config->isWildcarded($selector->getSelector())) {
                            $removeIt = false;
                            continue;
                        }

                        try {
                            $items = $crawler->filter($selector->getSelector());
                        } catch (ExpressionErrorException $e) {
                            $removeIt = false;
                            continue; // Allow any weird pseudo css class
                        }
                        if ($items->count() > 0) {
                            $removeIt = false;
                        }
                    }
                    if ($removeIt) {
                        $cssDocument->remove($block);
                    }
                } elseif ($block instanceof AtRuleBlockList) {
                    foreach ($block->getContents() as $child) {
                        // TODO duplicate code
                        $selectors = $child->getSelectors();
                        $removeIt = true;
                        /** @var Selector $selector */
                        foreach ($selectors as $selector) {
                            try {
                                $items = $crawler->filter($selector->getSelector());
                            } catch (ExpressionErrorException $e) {
                                $removeIt = false;
                                continue;
                            }
                            if ($items->count() > 0) {
                                $removeIt = false;
                            }
                        }
                        if ($removeIt) {
                            $block->remove($child);
                        }
                    }
                }
            }

            // Remove empty media queries
            foreach ($cssDocument->getContents() as $content) {
                if ($content instanceof AtRuleBlockList) {
                    if (empty($content->getContents())) {
                        $cssDocument->remove($content);
                    }
                }
            }

            $inlineCss = $cssDocument->render(OutputFormat::createCompact());
            $newSize = strlen($inlineCss);
            $saved = round(($originalSize - $newSize) / 1024, 1); // KB
            $info = '';
            if ($config->isDebugEnabled()) {
                $info = '<!-- Saved ' . $saved . 'KB in ' . $cssFile->getSanitizedFilePath() . ' -->';
            }
            $html = str_replace($cssFile->getHtmlCode(), $info . PHP_EOL . '<style>' . $inlineCss . '</style>', $html);
        }

        return $html;
    }

    /**
     * @return array|CssFile[]
     */
    private function extractCssFilesFromHtml(string $html, Configuration $config): array
    {
        $crawler = new Crawler($html);

        $result = [];
        /** @var \DOMElement $link */
        foreach ($crawler->filter('link') as $link) {
            if ($link->hasAttribute('rel') && $link->getAttribute('rel') === 'stylesheet' &&
                $link->hasAttribute('type') && $link->getAttribute('type') === 'text/css' &&
                $link->hasAttribute('href') &&
                !$config->isExcludedFile($href = $link->getAttribute('href'))
            ) {
                $result[] = new CssFile($href, $link->ownerDocument->saveHTML($link));
            }
        }
        return $result;
    }
}
