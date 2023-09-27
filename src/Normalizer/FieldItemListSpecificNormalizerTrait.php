<?php

namespace Drupal\islandora_iiif_presentation_api\Normalizer;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Trait for normalizing field items.
 */
trait FieldItemListSpecificNormalizerTrait {
  use FieldSpecificNormalizerTrait;

  /**
   * {@inheritDoc}
   */
  protected function getSupportedType() : string {
    return FieldItemListInterface::class;
  }

  /**
   * {@inheritDoc}
   */
  protected function getFieldName($data) : string {
    return $data->getName();
  }

}
