<?php

declare(strict_types=1);

namespace Gotphoto\Logging;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

final class LogstashFormatter extends ExceptionNormalizerFormatter
{
    /**
     * @var string the name of the system for the Logstash log message, used to fill the @source field
     */
    protected string $systemName;

    /**
     * @param string $applicationName the application that sends the data, used as the "type"
     *                                                        field of logstash
     * @param string $environment current environment
     * @param string|null $systemName the system/machine name, used as the "source" field of
     *                                                        logstash, defaults to the hostname of the machine
     * @param array<string, array<callable(\Throwable):array<string, array<array-key, string>|int|string>>> $exceptionContextProviderMap
     */
    public function __construct(
        protected string $applicationName,
        protected string $environment,
        protected array $exceptionContextProviderMap = [],
        ?string $systemName = null,
    ) {
        // logstash requires a ISO 8601 format date with optional millisecond precision.
        parent::__construct($exceptionContextProviderMap, 'Y-m-d\TH:i:s.uP');
        $this->systemName = $systemName === null ? gethostname() : $systemName;
    }

    /**
     * {@inheritdoc}
     */
    public function format(LogRecord $record): string
    {
        /** @var array{timestamp: int, datetime: string, extra?:array, context?:array} $data */
        $data = parent::format($record);
        /** @psalm-suppress RiskyTruthyFalsyComparison this is okay null or empty string */
        if (empty($data['datetime'])) {
            $data['datetime'] = gmdate('c');
        }
        /** @psalm-suppress RiskyTruthyFalsyComparison this is okay null or empty string */
        if (empty($data['timestamp'])) {
            $data['timestamp'] = time();
        }
        $message = [
            '@timestamp' => $data['datetime'],
            '@version' => 1,
            'timestamp' => $data['timestamp'],
            'host' => $this->systemName,
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

        return $this->toJson($message) . "\n";
    }

    public function formatBatch(array $records)
    {
        /** @var array $records */
        $records = parent::formatBatch($records);

        return $this->toJson($records, true);
    }
}
