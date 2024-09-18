<?php

declare(strict_types=1);

namespace Gotphoto\Logging;

use Monolog\Formatter\NormalizerFormatter;

/**
 * @internal
 */
final class OtelFormatter extends NormalizerFormatter
{
    /**
     * @param array<string, array<callable>> $exceptionContextProviderMap
     */
    public function __construct(private readonly array $exceptionContextProviderMap = [])
    {
        parent::__construct();
    }

    /**
     * @return array
     */
    protected function normalizeException(\Throwable $e, int $depth = 0)
    {
        /** @var array{message: string, context?: array<string, mixed>} $data */
        $data = parent::normalizeException($e, $depth);

        $exceptionProviders = $this->getExceptionContexts($e);

        foreach ($exceptionProviders as $exceptionProvider) {
            /**
             * @var array<string, mixed> $additionalContext
             */
            $additionalContext = $exceptionProvider($e);
            if (!empty($additionalContext)) {
                $data['context'] = ($data['context'] ?? []) + $additionalContext;
            }
        }

        return $data;
    }

    /**
     * @return array<callable>
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
