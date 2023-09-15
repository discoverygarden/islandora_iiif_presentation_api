<?php

namespace Drupal\islandora_iiif_presentation_api\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Provides a trait for generating entity URIs.
 */
trait FieldSpecificNormalizerTrait {

  /**
   * The field name of the reference that this normalizer supports.
   *
   * @var string
   */
  protected string $supportedReferenceField;

  /**
   * The entity type that this normalizer supports.
   */
  protected string $supportedEntityType;

  /**
   * Ensures that the only field and entity type defined supports normalization.
   *
   * @param mixed $data
   *   Object to normalize.
   *
   * @return bool
   *   Whether it's one of the supported fields or not.
   */
  public function isSupportedTypeAndReference($data): bool {
    return is_a($data, $this->getSupportedType()) &&
      $data->getEntity()->getEntityTypeId() === $this->supportedEntityType &&
      $this->getFieldName($data) === $this->supportedReferenceField;
  }

  /**
   * Gets the supported type being used.
   *
   * @return string
   *   The class name of the supported type.
   */
  abstract protected function getSupportedType() : string;

  /**
   * Gets the field name of the reference that this normalizer supports.
   *
   * @param mixed $data
   *   Object to normalize.
   *
   * @return string
   *   The field name that is being supported.
   */
  abstract protected function getFieldName($data) : string;

}
