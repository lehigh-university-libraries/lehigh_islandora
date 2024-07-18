<?php

declare(strict_types=1);

namespace Drupal\lehigh_islandora\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Add PDF Coverpage' condition.
 *
 * @Condition(
 *   id = "lehigh_islandora_add_pdf_coverpage",
 *   label = @Translation("Add PDF Coverpage"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", required = TRUE , label = @Translation("node"))
 *   }
 * )
 */
final class AddPdfCoverpage extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
  }


  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $node = $this->getContextValue('node');
    if (!$node) {
      return FALSE;
    }

    return $node->hasField('field_add_coverpage')
      && $node->field_add_coverpage->isEmpty()
      && $node->field_add_coverpage->value;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (empty($this->configuration['negate'])) {
      return $this->t('The node has the coverpage field set to true.');
    }
    else {
      return $this->t('The node does not have the coverpage field set to true');
    }
  }
}
