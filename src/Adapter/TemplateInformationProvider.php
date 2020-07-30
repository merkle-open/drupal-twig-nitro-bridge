<?php

namespace Drupal\twig_nitro_bridge\Adapter;

use Namics\Terrific\Config\ConfigReader;
use Namics\Terrific\Provider\TemplateInformationProviderInterface;
use Drupal\Core\Config\ConfigFactory as DrupalConfigFactory;
use Drupal\Core\File\FileSystemInterface;

/**
 * Class TemplateInformationProvider.
 *
 * @package Drupal\twig_nitro_bridge\Adapter
 */
class TemplateInformationProvider implements TemplateInformationProviderInterface {
  /**
   * List of paths where templates are stored.
   *
   * @var array
   */
  private $paths = [];

  /**
   * Path to Frontend Directory.
   *
   * @var string
   */
  private $basePath = '';

  /**
   * Terrific's config.json Content.
   *
   * @var array
   */
  private $terrificConfig = [];

  /**
   * TemplateLocator constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   * @param \Drupal\Core\File\FileSystemInterface $filesystem
   *   The file system service.
   */
  public function __construct(
    DrupalConfigFactory $config_factory,
    FileSystemInterface $filesystem
  ) {
    $this->basePath = $filesystem
      ->realpath(
        DRUPAL_ROOT . '/' . $config_factory->get('twig_nitro_bridge.settings')->get('frontend_dir')
      );

    $this->terrificConfig = (new ConfigReader($this->basePath))->read();
  }

  /**
   * Returns a list of paths where templates can be found.
   *
   * @return array
   *   Return array of paths.
   */
  public function getPaths(): array {
    if (empty($this->paths)) {
      $this->generatePaths();
    }

    return $this->paths;
  }

  /**
   * Generate paths array from Terrific configuration.
   */
  private function generatePaths(): void {
    $components = $this->terrificConfig['nitro']['components'];
    foreach ($components as $name => $component) {
      $this->paths[$name] = $this->basePath . '/' . $component['path'];
    }
  }

}
