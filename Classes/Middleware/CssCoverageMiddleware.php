<?php declare(strict_types = 1);
namespace T3\CssCoverage\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use T3\CssCoverage\Service\CssCoverageService;

class CssCoverageMiddleware implements MiddlewareInterface
{
    private CssCoverageService $coverageService;

    public function __construct(CssCoverageService $coverageService)
    {
        $this->coverageService = $coverageService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $configuration = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_csscoverage.'] ?? [];

        return $this->coverageService->run($handler->handle($request), $configuration);
    }
}
