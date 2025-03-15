# Symfony Media

Add media manager to your app pages.

## How to install

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
composer require osw3/symfony-media
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php 
// config/bundles.php

return [
    // ...
    OSW3\Media\MediaBundle::class => ['all' => true],
];
```

## How to use

## How to configure


## dependencies

Install deps if you need to process :

Image:
```shell
composer require claviska/simpleimage
```

Audio:
```shell 
composer require james-heinrich/getid3
```

Video:
```shell 
composer require php-ffmpeg/php-ffmpeg
```

PDF:
```shell 
composer require setasign/fpdf
composer require setasign/fpdi
```










Create Entity

```shell 
bin/console make:entity Media
```

Valid the entity without properties and replace Entity class

```php 
use OSW3\Media\Trait\Entity\MediaTrait;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
class Media
{
    use MediaTrait;
}
```




```yaml 
#config/package/twig_component.yaml
twig_component:
    defaults:
        OSW3\Media\Components\: '@Media/'
```




PROCESSOR: 
Deinir la classe du processor dans la partie presets de config.yaml

Ajouter la classe au service

```yaml
service:    
    # App\Processor\TestProcessor:
    #     autowire: true
    #     autoconfigure: true
    #     public: true
    App\Processor\:
        resource: '../src/Processor/'
        autowire: true
        autoconfigure: true
        public: true
        # tags: ['app.processor']
```
