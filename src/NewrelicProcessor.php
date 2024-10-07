<?php
declare(strict_types=1);

namespace Gotphoto\Logging;

use Monolog\Processor\ProcessorInterface;

/**
 * Adds metadata to log that associates it with current New Relic application
 */
class NewrelicProcessor implements ProcessorInterface
{
    /**
     * Returns the given record with the New Relic linking metadata added
     * if a compatible New Relic extension is loaded, otherwise returns the
     * given record unmodified
     */
    public function __invoke(array $record)
    {
        if (function_exists('newrelic_get_linking_metadata')) {
            /** @var array $linking_data */
            $linking_data = newrelic_get_linking_metadata();
            $record['extra']['newrelic-context'] = $linking_data;
        }
        return $record;
    }
}
