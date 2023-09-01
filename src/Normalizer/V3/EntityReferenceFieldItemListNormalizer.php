<?php

namespace Drupal\islandora_iiif_presentation_api\Normalizer\V3;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\iiif_presentation_api\Normalizer\V3\NormalizerBase;
use Drupal\islandora\IslandoraUtils;

/**
 * Expands entity reference field values to their referenced entity.
 */
class EntityReferenceFieldItemListNormalizer extends NormalizerBase {

  public const SUPPORTED_FIELDS = [
    // Node fields.
    IslandoraUtils::MEMBER_OF_FIELD,
    IslandoraUtils::MODEL_FIELD,
    // Media fields, currently only image is supported.
    'field_media_image',
  ];

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
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    return $this->normalizeItems($object, $object->getEntity(), $format, $context);
  }

  /**
   * {@inheritDoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    // Parent will check both the format and the class defined in the normalizer
    // for us.
    return parent::supportsNormalization($data, $format) && $this->isSupportedField($data);
  }

  /**
   * Ensures that the only fields explicitly defined are normalized.
   *
   * @param mixed $data
   *   Object to normalize.
   *
   * @return bool
   *   Whether it's one of the supported fields or not.
   */
  public function isSupportedField($data) {
    return in_array($data->getName(), static::SUPPORTED_FIELDS);
  }

  /**
   * Normalizes an entity reference field item list.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemList $field_item_list
   *   The field item list to normalize.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being normalized.
   * @param string $format
   *   The format being normalized to.
   * @param array $context
   *   An array containing any context being passed to the normalizers.
   *
   * @return array
   *   An array of normalized values to be rendered.
   */
  public function normalizeItems(EntityReferenceFieldItemList $field_item_list, ContentEntityInterface $entity, string $format, array $context) {
    $normalized = [];
    if ($field_item_list->getName() === IslandoraUtils::MEMBER_OF_FIELD) {
      return $this->getChildren($entity, $format, $context);
    }

    foreach ($field_item_list as $item) {
      $normalized_property = $this->serializer->normalize($item, $format, $context);
      if (!empty($normalized_property)) {
        $normalized = NestedArray::mergeDeep($normalized, (array) $normalized_property);
      }
    }
    return $normalized;
  }

  /**
   * Retrieves children of a node given Islandora's child->parent relationships.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being normalized.
   * @param string $format
   *   The format being serialized.
   * @param array $context
   *   An array containing any context being passed to the normalizers.
   *
   * @return array
   *   An array of normalized children to be rendered.
   */
  public function getChildren(ContentEntityInterface $entity, $format, $context) {
    $normalized = [];
    // Leverage an entity query to find all children that are referencing the
    // entity in "field_member_of".
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $ids = $query->condition(IslandoraUtils::MEMBER_OF_FIELD, $entity->id())
      ->accessCheck()
      ->execute();

    // Load all the entities.
    $children = $this->entityTypeManager->getStorage('node')->loadMultiple($ids);
    if (!empty($children)) {
      $normalized['items'] = [];
      $context['base-depth'] = FALSE;
      foreach ($children as $child) {
        $normalized['items'][] = $this->serializer->normalize($child, $format, $context);
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
