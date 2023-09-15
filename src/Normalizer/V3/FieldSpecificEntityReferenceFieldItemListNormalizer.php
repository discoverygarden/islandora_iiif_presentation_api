<?php

namespace Drupal\islandora_iiif_presentation_api\Normalizer\V3;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\iiif_presentation_api\Normalizer\V3\NormalizerBase;
use Drupal\islandora_iiif_presentation_api\Normalizer\FieldItemListSpecificNormalizerTrait;

/**
 * Expands entity reference fields to their referenced entity given constraints.
 */
class FieldSpecificEntityReferenceFieldItemListNormalizer extends NormalizerBase {

  use FieldItemListSpecificNormalizerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  protected $supportedInterfaceOrClass = EntityReferenceFieldItemList::class;

  /**
   * Constructor for the EntityReferenceFieldItemListNormalizer.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, $entity_type, $reference_field) {
    $this->entityTypeManager = $entity_type_manager;
    $this->supportedReferenceField = $reference_field;
    $this->supportedEntityType = $entity_type;
  }

  /**
   * {@inheritDoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    // Parent will check both the format and the class defined in the normalizer
    // for us.
    return parent::supportsNormalization($data, $format) && $this->isSupportedTypeAndReference($data);
  }

  /**
   * {@inheritDoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $normalized = [];
    foreach ($object as $item) {
      $normalized_property = $this->serializer->normalize($item, $format, $context);
      if (!empty($normalized_property)) {
        $normalized = NestedArray::mergeDeep($normalized, (array) $normalized_property);
      }
    }
    return $normalized;
  }

  /**
   * {@inheritDoc}
   */
  public function hasCacheableSupportsMethod(): bool {
    // XXX: Given fields are being checked in supportsNormalization, this cannot
    // be cached.
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      EntityReferenceFieldItemList::class => FALSE,
    ];
  }

}
