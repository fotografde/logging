<?php declare(strict_types=1);

namespace Gotphoto\Logging\ExceptionContext;

use Aws\Exception\AwsException;

class AwsExceptionContext implements ExceptionContext
{
    /**
     * @return array{aws_error_code: null|string, aws_error_message: null|string, aws_error_type: null|string}
     */
    public function __invoke(AwsException $exception): array
    {
        return [
            'aws_error_code'    => $exception->getAwsErrorCode(),
            'aws_error_message' => $exception->getAwsErrorMessage(),
            'aws_error_type'    => $exception->getAwsErrorType(),
        ];
    }

}
