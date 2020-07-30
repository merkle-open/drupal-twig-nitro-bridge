<?php

namespace Drupal\twig_nitro_bridge\Adapter;

use Namics\Terrific\Provider\ContextProviderInterface;
use Namics\Terrific\Twig\TerrificCompiler;
use Twig\Compiler;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;

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
  public const TERRIFIC_ARRAY_KEY = '#terrific';

  /**
   * The Terrific compiler.
   *
   * @var \Namics\Terrific\Twig\TerrificCompilerInterface
   */
  private $compiler;

  /**
   * Component.
   *
   * @var \Twig\Node\Node
   */
  private $component;

  /**
   * DataVariant.
   *
   * @var \Twig\Node\Node
   */
  private $dataVariant;

  /**
   * {@inheritdoc}
   */
  public function compile(
    Compiler $compiler,
    Node $component,
    Node $dataVariant = NULL,
    $only = FALSE
  ): void {
    $this->compiler = TerrificCompiler::create($compiler);
    $this->component = $component;
    $this->dataVariant = $dataVariant;

    if ($only) {
      $this->compiler->getTwigCompiler()
        ->raw("\t")
        ->raw(ContextProviderInterface::TERRIFIC_CONTEXT_VARIABLE . ' = [];');
    }

    $this->createContext();
  }

  /**
   * Creates a new context or merges the variant with the existing context.
   */
  private function createContext(): void {
    /* Is key value array used for data.
    E.g. {% component 'my-component' { title: "My title" } %} */
    if ($this->dataVariant instanceof ArrayExpression) {
      $this->compiler->getTwigCompiler()
        ->raw(ContextProviderInterface::TERRIFIC_CONTEXT_VARIABLE . ' = array_merge(' . ContextProviderInterface::TERRIFIC_CONTEXT_VARIABLE . ', ')
        ->subcompile($this->dataVariant)
        ->raw(');');
    }
    /* Is variable used for data.
    E.g. {% component 'my-component' myTwigVariable %} */
    elseif ($this->dataVariant instanceof NameExpression) {
      $this->compiler->compileAndMergeNameExpressionToContext(
        $this->dataVariant,
        $this->getNameExpressionDoesNotExistErrorMessage($this->dataVariant)
      );
    }
    /* Is object/array used for data.
    E.g. {% component 'my-component' myTwigObject.anObjectProperty.value %} */
    elseif ($this->dataVariant instanceof GetAttrExpression) {
      $this->compiler->compileAndMergeGetAttrExpressionToContext(
        $this->dataVariant,
        $this->getGetAttrExpressionDoesNotExistErrorMessage($this->dataVariant)
      );
    }
    else {
      $dataKey = ($this->dataVariant instanceof ConstantExpression)
        ? $this->dataVariant->getAttribute('value')
        : $this->component->getAttribute('value');

      $this->compiler->getTwigCompiler()
        ->raw("\n")->write('')
        ->raw('if (')
        ->raw('isset($context["' . self::TERRIFIC_ARRAY_KEY . '"]) && ')
        ->raw('isset($context["' . self::TERRIFIC_ARRAY_KEY . '"]["' . $dataKey . '"])')
        ->raw(') {')
        ->raw("\n")->write('')->write('')
        ->raw(ContextProviderInterface::TERRIFIC_CONTEXT_VARIABLE . ' = array_merge(' . ContextProviderInterface::TERRIFIC_CONTEXT_VARIABLE . ', ')
        ->raw('$context["' . self::TERRIFIC_ARRAY_KEY . '"]["' . $dataKey . '"]')
        ->raw(');')
        ->raw("\n")->write('')
        ->raw('} else {')
        ->raw("\n")->write('')->write('')
        ->raw('throw new \Twig\Error\Error("')
        ->raw(addslashes("No {$this->component->getNodeTag()} with name {$this->getComponentName()} exists."))
        ->raw('");')
        ->raw("\n")->write('')
        ->raw('}')
        ->raw("\n\n");
    }
  }

  /**
   * Returns the name of the current component.
   *
   * @return string
   *   The name of the component being called.
   */
  protected function getComponentName(): string {
    if ($this->component instanceof ConstantExpression) {
      $componentName = $this->component->getAttribute('value');
    }
    else {
      // TODO: Add implementations for more types.
      $componentName = addslashes('ContextProvider->getComponentName() not implemented for class "' . get_class($this->component) . '".');
    }

    return $componentName;
  }

  /**
   * Returns the error message to be used when the variable does not exist.
   *
   * @param \Twig\Node\Expression\NameExpression $expression
   *   The expression to get the message for.
   *
   * @return string
   *   The error message.
   *
   * @throws \Twig\Error\Error
   */
  protected function getNameExpressionDoesNotExistErrorMessage(NameExpression $expression): string {
    return addslashes('The variable "'
      . $this->compiler->getExpressionHandler()->getVariableNameFromNameExpression($expression)
      . '" passed to the '
      . $this->component->getNodeTag()
      . ' '
      . $this->getComponentName()
      . ' does not exist.');
  }

  /**
   * Returns the error message to be used when the variable does not exist.
   *
   * @param \Twig\Node\Expression\GetAttrExpression $expression
   *   The expression to get the message for.
   *
   * @return string
   *   The error message.
   */
  protected function getGetAttrExpressionDoesNotExistErrorMessage(GetAttrExpression $expression): string {
    $variableNameAndArrayKeys = $this->compiler->getExpressionHandler()->buildGetAttrExpressionArrayKeyPair($expression);

    return addslashes('The variable "'
      . $variableNameAndArrayKeys->toTwigVariableString()
      . '" passed to the '
      . $this->component->getNodeTag()
      . ' '
      . $this->getComponentName()
      . ' does not exist.');
  }

}
