<?php

declare(strict_types=1);

namespace Gotphoto\Logging;

use Monolog\Formatter\NormalizerFormatter;

/**
 * @internal
 */
class ExceptionNormalizerFormatter extends NormalizerFormatter
{
    /** @var array<string, array<callable(\Throwable):array<string, array<array-key, string>|int|string>>> */
    private readonly array $exceptionContextProviderMap;

    /**
     * @param array<string, array<callable(\Throwable):array<string, array<array-key, string>|int|string>>> $exceptionContextProviderMap
     */
    public function __construct(array $exceptionContextProviderMap = [], ?string $dateFormat = null)
    {
        $this->exceptionContextProviderMap = $exceptionContextProviderMap;
        parent::__construct($dateFormat);
    }

    protected function normalizeException(\Throwable $e, int $depth = 0): array
    {
        $data = parent::normalizeException($e, $depth);

        $exceptionProviders = $this->getExceptionContexts($e);

        foreach ($exceptionProviders as $exceptionProvider) {
            $additionalContext = $exceptionProvider($e);
            if (!empty($additionalContext) && isset($data['context']) && is_array($data['context'])) {
                $data['context'] = ($data['context'] ?? []) + $additionalContext;
            }
        }

        return $data;
    }

    /**
     * @return array<callable(\Throwable):array<string, array<array-key, string>|int|string>>
     */
    protected function getExceptionContexts(\Throwable $e): array
    {
        if (isset($this->exceptionContextProviderMap[\get_class($e)])) {
            return $this->exceptionContextProviderMap[\get_class($e)];
        }
        $exceptionContexts = [];
        foreach (array_keys($this->exceptionContextProviderMap) as $className) {
            if ($e instanceof $className) {
                $exceptionContexts = array_merge($exceptionContexts + $this->exceptionContextProviderMap[$className]);
            }
        }

        return $exceptionContexts;
    }
}
