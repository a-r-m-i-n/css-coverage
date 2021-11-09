<?php declare(strict_types = 1);
namespace T3\CssCoverage;

class Configuration
{
    protected bool $enabled;
    protected bool $debug;
    protected array $excludedFilePatterns = [];
    protected array $selectorWildcards = [];

    public function __construct(array $typoscriptSetup = [])
    {
        $this->enabled = (bool)$typoscriptSetup['enabled'];
        $this->debug = (bool)$typoscriptSetup['debug'];
        $this->excludedFilePatterns = $typoscriptSetup['excluded.'] ?? [];
        $this->selectorWildcards = $typoscriptSetup['selectorWildcards.'] ?? [];

    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isDebugEnabled(): bool
    {
        return $this->debug;
    }

    public function getExcludedFilePatterns(): array
    {
        return $this->excludedFilePatterns;
    }

    public function isExcludedFile(string $filePath): bool
    {
        foreach ($this->excludedFilePatterns as $pattern) {
            $match = fnmatch($pattern, $filePath);
            if ($match) {
                return true;
            }
        }

        return false;
    }

    public function getSelectorWildcards()
    {
        return $this->selectorWildcards;
    }

    public function isWildcarded(string $selector): bool
    {
        foreach ($this->selectorWildcards as $wildcard) {
            $match = fnmatch($wildcard, $selector);
            if ($match) {
                return true;
            }
        }

        return false;
    }

}
