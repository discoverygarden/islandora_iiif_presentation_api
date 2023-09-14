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
    // XXX: Being explicit as this trait should only deal with List and Items.
    if (!$data instanceof FieldItemInterface && !$data instanceof FieldItemListInterface) {
      return FALSE;
    }
    $field_name = $data instanceof FieldItemInterface ? $data->getParent()->getName() : $data->getName();
    return $data->getEntity()->getEntityTypeId() === $this->supportedEntityType && $field_name === $this->supportedReferenceField;
  }

}
