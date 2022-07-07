<?php

declare(strict_types=1);

namespace Gotphoto\Logging;

use Monolog\Formatter\NormalizerFormatter;

final class Formatter extends NormalizerFormatter
{
    /**
     * @var string the name of the system for the Logstash log message, used to fill the @source field
     */
    protected $systemName;
    /**
     * @var string an application name for the Logstash log message, used to fill the @type field
     */
    protected $applicationName;
    /** @var string */
    protected $environment;
    /** @var array<string, array<callable>> */
    protected $exceptionContextProviderMap;

    /**
     * @param string                         $applicationName the application that sends the data, used as the "type"
     *                                                        field of logstash
     * @param string                         $environment     current environment
     * @param string|null                    $systemName      the system/machine name, used as the "source" field of
     *                                                        logstash, defaults to the hostname of the machine
     * @param array<string, array<callable>> $exceptionContextProviderMap
     */
    public function __construct(
        string $applicationName,
        string $environment,
        array $exceptionContextProviderMap = [],
        ?string $systemName = null
    ) {
        // logstash requires a ISO 8601 format date with optional millisecond precision.
        parent::__construct('Y-m-d\TH:i:s.uP');
        $this->systemName                  = $systemName === null ? gethostname() : $systemName;
        $this->environment                 = $environment;
        $this->applicationName             = $applicationName;
        $this->exceptionContextProviderMap = $exceptionContextProviderMap;
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record): string
    {
        /** @var array{timestamp: int, datetime: string} $data */
        $data = parent::format($record);
        if (empty($data['datetime'])) {
            $data['datetime'] = gmdate('c');
        }
        $message = [
            '@timestamp'  => $data['datetime'],
            '@version'    => 1,
            'timestamp'   => $data['timestamp'],
            'host'        => $this->systemName,
            'environment' => $this->environment,
        ];
        unset($data['datetime'], $data['timestamp']);
        if ($this->applicationName) {
            $message['app'] = $this->applicationName;
        }
        $message += $data;
        if (isset($message['extra'])) {
            $message['extra'] = (object)$message['extra'];
        }
        if (isset($message['context'])) {
            $message['context'] = (object)$message['context'];
        }

        return $this->toJson($message)."\n";
    }

    public function formatBatch(array $records)
    {
        /** @var array $records */
        $records = parent::formatBatch($records);

        return $this->toJson($records, true);
    }

    /**
     * Moves New Relic context information from the
     * `$data['extra']['newrelic-context']` array to top level of record,
     * converts `datetime` object to `timestamp` top level element represented
     * as milliseconds since the UNIX epoch, and finally, normalizes the data
     */
    protected function normalize($data, int $depth = 0)
    {
        if ($depth === 0) {
            assert(is_array($data));
            /** @var array{extra?:array{newrelic-context?:array}, datetime: \Monolog\DateTimeImmutable} $data */
            if (isset($data['extra']['newrelic-context'])) {
                $data = array_merge($data, $data['extra']['newrelic-context']);
                /** @psalm-suppress MixedArrayAccess we checked that it is an array */
                unset($data['extra']['newrelic-context']);
                if (empty($data['extra'])) {
                    unset($data['extra']);
                }
            }
            /** @var array{datetime: \Monolog\DateTimeImmutable} $data */
            $data['timestamp'] = (int)$data['datetime']->format('U.u') * 1000;
        }

        return parent::normalize($data, $depth);
    }

    /**
     * @param \Throwable $e
     *
     * @return array
     */
    protected function normalizeException(\Throwable $e, int $depth = 0)
    {
        /** @var array{message: string, context?: array<string, mixed>} $data */
        $data = parent::normalizeException($e);

        $exceptionProviders = $this->exceptionContextProviderMap[\get_class($e)] ?? [];

        foreach ($exceptionProviders as $exceptionProvider) {
            /**
             * @var array<string, mixed> $additionalContext
             */
            $additionalContext = $exceptionProvider($e);
            if (!empty($additionalContext)) {
                $data['context'] = \array_merge($data['context'] ?? [], $additionalContext);
            }
        }

        return $data;
    }
}
