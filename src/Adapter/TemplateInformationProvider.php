<?php

namespace Drupal\twig_nitro_bridge\Adapter;

use Namics\Terrific\Config\ConfigReader;
use Namics\Terrific\Provider\TemplateInformationProviderInterface;
use Drupal\Core\Config\ConfigFactory as DrupalConfigFactory;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\twig_nitro_bridge\Error\TerrificFileExtensionNotDefinedError;

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
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * TemplateLocator constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory param.
   * @param \Drupal\Core\File\FileSystemInterface $filesystem
   *   FileSystem.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   */
  public function __construct(
    DrupalConfigFactory $config_factory,
    FileSystemInterface $filesystem,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->basePath = $filesystem
      ->realpath(
        DRUPAL_ROOT . '/' . $config_factory->get('twig_nitro_bridge.settings')->get('frontend_dir')
      );

    $this->terrificConfig = (new ConfigReader($this->basePath))->read();
    $this->logger = $logger_factory->get('twig_nitro_bridge');
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
      $elements = $this->checkForComponentElements($this->paths[$name]);
      $this->paths = array_merge($this->paths, $elements);
    }
  }

  /**
   * Add elements (sub-components) folder if it exists.
   *
   * TODO: Workaround for new frontend standard.
   */
  private function checkForComponentElements($componentPath): array {
    $elements = [];
    $dir = new \DirectoryIterator($componentPath);
    foreach ($dir as $fileinfo) {
      if ($fileinfo->isDir() && !$fileinfo->isDot()) {
        $elementsDir = $componentPath . '/' . $fileinfo->getFilename() . '/' . 'elements';
        if (file_exists($elementsDir)) {
          $key = $fileinfo->getFilename() . '_elements';
          $elements[$key] = $elementsDir;
        }
      }
    }

    return $elements;
  }

  /**
   * Returns the template file extension.
   *
   * @return string
   *   Template File Extension.
   *
   * @throws \Drupal\twig_nitro_bridge\Error\TerrificFileExtensionNotDefinedError
   */
  public function getFileExtension(): string {
    if (!isset($this->terrificConfig['nitro']['view_file_extension'])) {
      $this->logger->notice('Frontend Template File Extension not defined in Terrific\'s Configuration File.');

      throw new TerrificFileExtensionNotDefinedError();
    }

    /** @var string $fileExtension */
    $fileExtension = $this->terrificConfig['nitro']['view_file_extension'];

    return $fileExtension;
  }

}
