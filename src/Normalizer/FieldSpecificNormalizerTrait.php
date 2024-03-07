<?php

namespace Drupal\islandora_iiif_presentation_api\Normalizer;

/**
 * Provides a trait for generating entity URIs.
 */
trait FieldSpecificNormalizerTrait {

  /**
   * The field name of the reference that this normalizer supports.
   *
   * @var string
   */
  protected string $targetFieldName;

  /**
   * The entity type that this normalizer supports.
   *
   * @var string
   */
  protected string $targetEntityTypeId;

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
      $data->getEntity()->getEntityTypeId() === $this->getTargetEntityTypeId() &&
      $this->getFieldName($data) === $this->getTargetFieldName();
  }

  /**
   * Get the target entity type.
   *
   * @return string
   *   The target entity type.
   */
  public function getTargetEntityTypeId() : string {
    return $this->targetEntityTypeId;
  }

  /**
   * Get the target field name.
   *
   * @return string
   *   The target field name.
   */
  public function getTargetFieldName() : string {
    return $this->targetFieldName;
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
