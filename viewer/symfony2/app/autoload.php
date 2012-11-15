<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = require __DIR__.'/../vendor/autoload.php';

// intl
if (!function_exists('intl_get_error_code')) {
    require_once __DIR__.'/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs/functions.php';

    $loader->add('', __DIR__.'/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs');
}

//$loader->registerPrefixes(array(
//    'Groupdocs_' => __DIR__.'/../vendor/groupdocs/lib',
//));
$loader->add('Groupdocs_', __DIR__.'/../vendor/groupdocs/lib');
set_include_path(__DIR__.'/../vendor/groupdocs/lib'.PATH_SEPARATOR.get_include_path());
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

return $loader;
