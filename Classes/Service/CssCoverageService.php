<?php declare(strict_types = 1);
namespace T3\CssCoverage\Service;


use Sabberworm\CSS\CSSList\AtRuleBlockList;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser as CssParser;
use Sabberworm\CSS\Property\Selector;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Symfony\Component\CssSelector\Exception\ExpressionErrorException;
use Symfony\Component\DomCrawler\Crawler;
use T3\CssCoverage\Service\Result\CssFile;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\StringUtility;

class CssCoverageService
{

    public function run(Response $response): Response
    {
        $body = $response->getBody();
        $body->rewind();
        $html = $body->getContents();

        // Get CSS files
        $cssFiles = $this->extractCssFilesFromHtml($html);

        $crawler = new Crawler($html);

        // Process CSS files
        foreach ($cssFiles as $cssFile) {

            // TODO make this configurable
            if (StringUtility::endsWith($cssFile->getSanitizedFilePath(), 'fonts.css')) {
                continue;
            }

            $parser = new CssParser(file_get_contents(Environment::getPublicPath() . $cssFile->getSanitizedFilePath()));
            $cssDocument = $parser->parse();


            /** @var DeclarationBlock $block */
            foreach ($cssDocument->getContents() as $block) {

                if ($block instanceof DeclarationBlock) {
                    $selectors = $block->getSelectors();
                    $removeIt = true;
                    /** @var Selector $selector */
                    foreach ($selectors as $selector) {
                        try {
                            $items = $crawler->filter($selector->getSelector());
                        } catch (ExpressionErrorException $e) {
                            $removeIt = false;
                            continue; // TODO
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

            foreach ($cssDocument->getContents() as $baum) {
                if ($baum instanceof AtRuleBlockList && empty($baum->getContents())) {
                    $cssDocument->remove($baum);
                }
            }

            $inlineCss = $cssDocument->render(OutputFormat::createCompact());

            $info = '<!-- ' . $cssFile->getSanitizedFilePath() . ' -->';
            $html = str_replace($cssFile->getHtmlCode(), $info . PHP_EOL . '<style>' . $inlineCss . '</style>', $html);
        }

        $body->rewind();
        $body->write($html);

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
