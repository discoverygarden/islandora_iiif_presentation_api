<?php

namespace Drupal\islandora_iiif_presentation_api\Normalizer;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Trait for normalizing field items.
 */
trait FieldItemSpecificNormalizerTrait {
  use FieldSpecificNormalizerTrait;

  /**
   * {@inheritDoc}
   */
  protected function getSupportedType() : string {
    return FieldItemInterface::class;
  }

  /**
   * {@inheritDoc}
   */
  protected function getFieldName($data) : string {
    return $data->getParent()->getName();
  }

}
