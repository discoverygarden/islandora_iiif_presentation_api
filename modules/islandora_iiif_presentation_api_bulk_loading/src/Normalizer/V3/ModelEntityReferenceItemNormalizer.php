<?php

namespace Drupal\islandora_iiif_presentation_api_bulk_loading\Normalizer\V3;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\islandora\IslandoraUtils;
use Drupal\islandora_iiif_presentation_api\Normalizer\V3\ModelEntityReferenceItemNormalizer as UpstreamNormalizer;

/**
 * Bulk-loading extension of upstream normalizer.
 */
class ModelEntityReferenceItemNormalizer extends UpstreamNormalizer {

  /**
   * Feature flag; use our logic (FALSE), or defer to the inner service (TRUE).
   *
   * @var bool
   */
  protected bool $useInner = FALSE;

  /**
   * Constructor.
   */
  public function __construct(
    protected UpstreamNormalizer $inner,
    IslandoraUtils $islandora_utils,
  ) {
    parent::__construct($islandora_utils);
    $this->useInner = getenv('ISLANDORA_IIIF_PRESENTATION_API_BULK_LOADING') === 'false';
  }

  /**
   * {@inheritDoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    if ($this->useInner) {
      return $this->inner->normalize($object, $format, $context);
    }

    $this->addCacheableDependency($context, (new CacheableMetadata())->addCacheTags(['media_list']));
    // XXX: In its current form this is only going to be applicable to things
    // that have image media as their service files.
    if ($media = ($context['referenced_media'] ?? $this->getDerivativeMedia($object))) {
      $normalized = $this->serializer->normalize($media, $format, $context);
      return $context['base-depth'] ? ['items' => [$normalized]] : $normalized;
    }
    return [];
  }

}
