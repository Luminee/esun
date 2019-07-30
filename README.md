# Esun
Make ElasticSearch operation like SQL, build ORM for ElasticSearch

## Installation

Install through Composer.

```
composer require luminee/esun
```

Add service provider in your project's config/app.php file:
```
  'providers' => [
    // Other Providers

    Luminee\Esun\EsunServiceProvider::class,
  ],
```
and facades:
```
  'aliases' => [
    // Other Aliases

    'ES' => Luminee\Esun\Facades\ES::class,
  ],
```

## Configuration

Publish the esun.php to config folder
```
php artisan vendor:publish --provider="Luminee\Esun\EsunServiceProvider"
```
