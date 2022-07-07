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

# Exception context

Sometimes we want to log additional data from or when an exception appear. In many case you add additional dependency which is the Logger.
We can automize it with Exception context. You can create your custom error and rules how to extract the context from them.
See examples in folder ExceptionContext.

And here Guzzle example
```php
class GuzzleRequestExceptionContext implements ExceptionContext
{
    /**
     * @returnreturn array{message?: string}
     */
    public function __invoke(RequestException $exception): array
    {
        if ($exception->getResponse() !== null && $exception->getResponse()->getBody() !== null) {
            return ['message' => $exception->getResponse()->getBody()->getContents()];
        }
        return [];
    }
}
```
!WARNING: it is working automatically with Symfony integration only now. If you want you always can add integrations for another frameworks.


# Configuration
## Laravel
add in `config/logging.php` in `channels` section:

```php
        'gotphoto' => [
            'driver' => 'custom',
            'via' => new Gotphoto\Logging\Laravel\LaravelLoggerCreating,
            'app_name' => 'Service Name',
            'channel' => 'app'(security/reauest/order)
        ]
        'security' => [
            'driver' => 'custom',
            'via' => new Gotphoto\Logging\Laravel\LaravelLoggerCreating,
            'app_name' => 'Service Name',
            'channel' => 'security'
        ],
```

Do not forget to set one of them as default one in the same file : `'default' => env('LOG_CHANNEL', 'gotphoto'),`

## Symfony

Add bundle `new Gotphoto\Logging\Symfony\SymfonyLoggingBundle()`;

make monolog configuration looks like this
```yaml
monolog:
    handlers:
        handler1:
            type: stream
            path: "php://stderr"
            formatter: 'Gotphoto\Logging\Formatter'
        main:
            type: stream
            path: "php://stderr"
            formatter: 'Gotphoto\Logging\Formatter'
            level: info
            channels: [ "!something"]
        handler2:
            formatter: 'Gotphoto\Logging\Formatter'

```
Where the most important things are 
```
type: stream
path: "php://stderr"
formatter: 'Gotphoto\Logging\Formatter'
```
And `main` is a fully working example

### Exception context
Works in Symfony automatically. Just create implementation for the interface `Gotphoto\Logging\ExceptionContext\ExceptionContext` and add it as a
 service(usualy should be done automatically by `$services->load()`). And Symfony automatically will start to use it for you.
