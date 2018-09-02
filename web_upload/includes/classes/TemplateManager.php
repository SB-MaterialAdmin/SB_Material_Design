<?php
class TemplateManager {
  private static $Twig = NULL;

  public static function Initialize($ThemePath, $CachePath, $TwigPath) {
    if (self::IsInitialized())
      throw new \LogicException('Twig already ready for usage.');

    require($TwigPath . '/Autoloader.php');
    Twig_Autoloader::register();

    $Loader = new Twig_Loader_Filesystem($ThemePath); // for future. maybe i add namespaces. https://twig.symfony.com/doc/1.x/api.html#built-in-loaders
    self::$Twig = new Twig_Environment($Loader, [
      'cache'   => $CachePath,
      'charset' => 'utf8'
    ]);
  }

  public static function GetTwig() {
    if (!self::IsInitialized())
      throw new \LogicException('Twig is not ready for usage.');

    return self::$Twig;
  }

  public static IsInitialized() {
    return (self::$Twig !== NULL);
  }
}