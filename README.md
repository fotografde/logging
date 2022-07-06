# Gotphoto logging

Example log format:
```json
{
  "@timestamp": "2022-07-06T12:28:54.571699+00:00",
  "@version": 1,
  "timestamp": 1657110534571,
  "host": "maually/automatic defined host",
  "environment": "prod",
  "app": "ServiceName",
  "message": "Something happened",
  "context": {
    "hello": "people"
  },
  "extra": {
    "system-id": "48446546"
  },
  "level": 200,
  "level_name": "INFO",
  "channel": "security",
  "entity.name": "newrelic_defined",
  "entity.type": "newrelic_defined",
  "hostname": "newrelic_defined",
  "trace.id": "newrelic_defined",
  "span.id": "newrelic_defined"
}
```

## Laravel
add in `config/logging.php` in `channels` section:

```php
        'gotphoto' => [
            'driver' => 'custom',
            'via' => App\Framework\Logging\GotphotoLogger::class,
            'app_name' => 'Service Name',
            'channel' => 'app'(security/reauest/order)
        ]
        'security' => [
            'driver' => 'custom',
            'via' => App\Framework\Logging\GotphotoLogger::class,
            'app_name' => 'Service Name',
            'channel' => 'security'
        ],
```

Do not forget to set one of them as default one in the same file : `'default' => env('LOG_CHANNEL', 'gotphoto'),`
