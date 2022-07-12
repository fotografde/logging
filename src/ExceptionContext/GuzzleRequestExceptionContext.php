<?php declare(strict_types=1);

namespace Gotphoto\Logging\ExceptionContext;

use GuzzleHttp\Exception\RequestException;

class GuzzleRequestExceptionContext implements ExceptionContext
{
    /**
     * @return array{message?: string}
     */
    public function __invoke(RequestException $exception): array
    {
        /**
         * @psalm-suppress PossiblyNullReference
         * @psalm-suppress RedundantConditionGivenDocblockType
         */
        if ($exception->getResponse() !== null && $exception->getResponse()->getBody() !== null) {
            return ['message' => $exception->getResponse()->getBody()->getContents()];
        }

        return [];
    }

}
