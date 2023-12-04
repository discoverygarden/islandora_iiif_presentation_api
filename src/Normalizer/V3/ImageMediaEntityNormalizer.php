<?php

namespace Drupal\islandora_iiif_presentation_api\Normalizer\V3;

use Drupal\iiif_presentation_api\Normalizer\EntityUriTrait;
use Drupal\iiif_presentation_api\Normalizer\V3\ContentEntityNormalizer;
use Drupal\media\MediaInterface;

/**
 * Normalizer for image media entities.
 */
class ImageMediaEntityNormalizer extends ContentEntityNormalizer {

  use EntityUriTrait;

  /**
   * {@inheritDoc}
   */
  protected $supportedInterfaceOrClass = MediaInterface::class;

  /**
   * {@inheritDoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    if (!isset($context['parent'])) {
      throw new \LogicException('Media must be normalized with a parent context.');
    }

    // XXX: If the parent is already a canvas just pass along the media's fields
    // to be normalized as opposed to creating a new level / item.
    return $context['parent']['type'] === 'Manifest' ?
      parent::normalize($object, $format, $context) :
      $this->normalizeEntityFields($object, $format, $context, []);
  }

  /**
   * {@inheritDoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      MediaInterface::class => TRUE,
    ];
  }

}
