<?php

namespace Drupal\islandora_iiif_presentation_api\Normalizer\V3;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\iiif_presentation_api\Normalizer\V3\NormalizerBase;
use Drupal\islandora\IslandoraUtils;
use Drupal\taxonomy\TermInterface;

/**
 * Normalizer for entity reference items.
 */
class EntityReferenceItemNormalizer extends NormalizerBase {

  /**
   * {@inheritDoc}
   */
  protected $supportedInterfaceOrClass = EntityReferenceItem::class;

  /**
   * The IslandoraUtils service.
   *
   * @var \Drupal\islandora\IslandoraUtils
   */
  protected IslandoraUtils $islandoraUtils;

  /**
   * The service file taxonomy term.
   *
   * @var \Drupal\taxonomy\TermInterface|null
   */
  protected ?TermInterface $serviceFileTerm = NULL;

  /**
   * Constructor for the EntityReferenceItemNormalizer.
   *
   * @param \Drupal\islandora\IslandoraUtils $islandora_utils
   *   The IslandoraUtils service.
   */
  public function __construct(IslandoraUtils $islandora_utils) {
    $this->islandoraUtils = $islandora_utils;
  }

  /**
   * {@inheritDoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    // XXX: In its current form this is only going to be applicable to things
    // that have image media as their service files.
    if ($media = $this->getDerivativeMedia($object)) {
      $normalized['items'] = [];
      $normalized['items'][] = $this->serializer->normalize($media, $format, $context);
      return $normalized;
    }
    return [];
  }

  /**
   * Finds any service file derivative media for the given node.
   *
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $object
   *   The entity reference being normalized.
   *
   * @return \Drupal\media\MediaInterface|false|null
   *   The derivative media or FALSE/null if none found.
   */
  public function getDerivativeMedia(EntityReferenceItem $object) {
    // XXX: Given Islandora relationships "field_model" is being leveraged to
    // discover the derivative media and serialize them. This is not a sound
    // approach but until the relationship direction is changed here we are.
    $service_file_term = $this->getServiceFileTerm();
    if (!$service_file_term) {
      return FALSE;
    }
    return $this->islandoraUtils->getMediaWithTerm($object->getEntity(), $service_file_term);
  }

  /**
   * Helper to retrieve the taxonomy term for the service file media use.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The service file term or NULL if none found.
   */
  protected function getServiceFileTerm() {
    if ($this->serviceFileTerm === NULL) {
      $this->serviceFileTerm = $this->islandoraUtils->getTermForUri('http://pcdm.org/use#ServiceFile');
    }
    return $this->serviceFileTerm;
  }

  /**
   * {@inheritDoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      EntityReferenceItem::class => TRUE,
    ];
  }

}
