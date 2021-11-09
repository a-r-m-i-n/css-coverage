<?php declare(strict_types = 1);
namespace T3\CssCoverage\Service\Result;


use TYPO3\CMS\Core\Utility\StringUtility;

class CssFile
{

    private string $filePath;
    private string $sanitizedFilePath;
    private string $htmlCode;

    public function __construct(string$filePath, string $htmlCode)
    {

        $this->filePath = $filePath;
        // get rid of ?432131235 appendix
        $this->sanitizedFilePath = preg_replace('/(.*)\?.*/', '$1', $filePath);
        // get rid of EXT:min suffix
        if (StringUtility::endsWith($this->sanitizedFilePath, '-min.css')) {
            $this->sanitizedFilePath = substr($this->sanitizedFilePath, 0, -8) . '.css';
        } elseif (StringUtility::endsWith($this->sanitizedFilePath, '-min.css.gzip')) {
            $this->sanitizedFilePath = substr($this->sanitizedFilePath, 0, -13) . '.css';
        }
        $this->htmlCode = $htmlCode;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getSanitizedFilePath(): string
    {
        return '/' . ltrim($this->sanitizedFilePath, '/');
    }

    public function getHtmlCode(): string
    {
        return $this->htmlCode;
    }
}
