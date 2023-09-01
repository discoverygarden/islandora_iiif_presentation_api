<?php

namespace Drupal\islandora_iiif_presentation_api\Normalizer\V3;

use Drupal\iiif_presentation_api\Normalizer\V3\ContentEntityNormalizer;
use Drupal\media\MediaInterface;
use Symfony\Component\Serializer\Exception\LogicException;

/**
 * Normalizer for media entities.
 */
class MediaEntityNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritDoc}
   */
  protected $supportedInterfaceOrClass = MediaInterface::class;

  /**
   * {@inheritDoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    if (!isset($context['parent'])) {
      throw new LogicException('Media must be normalized with a parent context.');
    }

    // Adjust the context to denote that the media is a canvas.
    $context['parent']['type'] = 'Canvas';

    return parent::normalize($object, $format, $context);
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
