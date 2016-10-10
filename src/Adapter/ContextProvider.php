<?php

namespace Drupal\twig_nitro_bridge\Adapter;

use Deniaz\Terrific\Provider\ContextProviderInterface;
use Twig_Compiler;
use Twig_Node;
use Twig_Node_Expression_Array;
use Twig_Node_Expression_Constant;

/**
 * Class ContextProvider.
 *
 * @package Drupal\twig_nitro_bridge\Adapter
 */
class ContextProvider implements ContextProviderInterface {
  /**
   * Array Key where data is stored.
   *
   * @const string TERRIFIC_ARRAY_KEY
   */
  const TERRIFIC_ARRAY_KEY = '#terrific';
  /**
   * Compiler.
   *
   * @var Twig_Compiler
   */
  private $compiler;

  /**
   * Component.
   *
   * @var Twig_Node
   */
  private $component;

  /**
   * DataVariant.
   *
   * @var Twig_Node
   */
  private $dataVariant;

  /**
   * Only.
   *
   * @var bool
   */
  private $only;

  /**
   * {@inheritdoc}
   */
  public function compile(
    Twig_Compiler $compiler,
    Twig_Node $component,
    Twig_Node $dataVariant = NULL,
    $only = FALSE
  ) {
    $this->compiler = $compiler;
    $this->component = $component;
    $this->dataVariant = $dataVariant;
    $this->only = (bool) $only;

    if ($this->only) {
      $this->compiler
        ->raw("\t")
        ->raw('$tContext = [];');
    }

    $this->createContext();
  }

  /**
   * Creates a new context or merges the variant with the existing context.
   */
  private function createContext() {
    if ($this->dataVariant instanceof Twig_Node_Expression_Array) {
      $this->compiler
        ->raw('$tContext = array_merge($tContext, ')
        ->subcompile($this->dataVariant)
        ->raw(');');
    }
    else {
      $dataKey = ($this->dataVariant instanceof Twig_Node_Expression_Constant)
        ? $this->dataVariant->getAttribute('value')
        : $this->component->getAttribute('value');

      $this->compiler
        ->raw("\n")->addIndentation()
        ->raw('if (')
        ->raw('isset($context["' . self::TERRIFIC_ARRAY_KEY . '"]) && ')
        ->raw('isset($context["' . self::TERRIFIC_ARRAY_KEY . '"]["' . $dataKey . '"])')
        ->raw(') {')
        ->raw("\n")->addIndentation()->addIndentation()
        ->raw('$tContext = array_merge($tContext, ')
        ->raw('$context["' . self::TERRIFIC_ARRAY_KEY . '"]["' . $dataKey . '"]')
        ->raw(');')
        ->raw("\n")->addIndentation()
        ->raw('} else {')
        ->raw("\n")->addIndentation()->addIndentation()
        ->raw('throw new \Twig_Error("')
        ->raw("Data Variant {$dataKey} not mapped. Check your preprocess hooks.")
        ->raw('");')
        ->raw("\n")->addIndentation()
        ->raw('}')
        ->raw("\n\n");
    }
  }

}
