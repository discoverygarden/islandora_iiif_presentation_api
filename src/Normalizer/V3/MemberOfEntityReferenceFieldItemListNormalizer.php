<?php

namespace Drupal\islandora_iiif_presentation_api\Normalizer\V3;

use Drupal\islandora\IslandoraUtils;

/**
 * Handles the oddity that is field_member_of for Islandora nodes.
 */
class MemberOfEntityReferenceFieldItemListNormalizer extends FieldSpecificEntityReferenceFieldItemListNormalizer {

  /**
   * {@inheritDoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $normalized = [];
    // XXX: Given that children are being resolved this is non-standard.
    // Leverage an entity query to find all children that are referencing the
    // entity in "field_member_of".
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $ids = $query->condition(IslandoraUtils::MEMBER_OF_FIELD, $object->getEntity()->id())
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

}
