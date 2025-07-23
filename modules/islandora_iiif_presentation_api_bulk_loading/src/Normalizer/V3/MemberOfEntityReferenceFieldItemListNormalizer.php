<?php

namespace Drupal\islandora_iiif_presentation_api_bulk_loading\Normalizer\V3;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\islandora\IslandoraUtils;
use Drupal\islandora_iiif_presentation_api\Normalizer\V3\MemberOfEntityReferenceFieldItemListNormalizer as UpstreamNormalizer;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;

/**
 * Bulk-loading extension of upstream normalizer.
 */
class MemberOfEntityReferenceFieldItemListNormalizer extends UpstreamNormalizer {

  /**
   * Feature flag; use our logic (FALSE), or defer to the inner service (TRUE).
   *
   * @var bool
   */
  protected bool $useInner = FALSE;

  /**
   * Configuration; the size of query result chunks to load.
   *
   * @var int
   */
  protected int $loadSize;

  /**
   * Constructor.
   */
  public function __construct(
    protected UpstreamNormalizer $inner,
    EntityTypeManagerInterface $entity_type_manager,
    string $entity_type,
    string $reference_field,
    protected Connection $database,
  ) {
    parent::__construct($entity_type_manager, $entity_type, $reference_field);
    $this->useInner = getenv('ISLANDORA_IIIF_PRESENTATION_API_BULK_LOADING') === 'false';
    $this->loadSize = (int) getenv('ISLANDORA_IIIF_PRESENTATION_API_BULK_LOADING_SIZE') ?: 64;
  }

  /**
   * {@inheritDoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    if ($this->useInner) {
      return $this->inner->normalize($object, $format, $context);
    }

    if (!$context['base-depth']) {
      return [];
    }

    $normalized = [
      'items' => [],
    ];

    $this->addCacheableDependency($context, (new CacheableMetadata())->addCacheTags(['node_list']));
    $child_context = $context + ['base-depth' => FALSE];
    foreach ($this->getChildren($object->getEntity()) as $item) {
      [$child, $media] = $item;
      $normalized['items'][] = $this->serializer->normalize($child, $format, $child_context + [
        'referenced_media' => $media,
      ]);
    }

    return $normalized;
  }

  /**
   * Helper; enumerate child nodes with related service files.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node of which to enumerate the children.
   *
   * @return \Generator
   *   Generates two-tuples, each containing:
   *   - the loaded child node; and,
   *   - an (optional) loaded service file which is related to the child node.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getChildren(NodeInterface $node) : \Generator {
    $query = $this->database->select('node__field_member_of', 'nfmo')
      ->condition('nfmo.field_member_of_target_id', $node->id());
    assert($query instanceof SelectInterface);
    $mfmo = $query->leftJoin('media__field_media_of', 'mfmo', '%alias.field_media_of_target_id = nfmo.entity_id');
    $mfsu = $query->leftJoin('media__field_media_use', 'mfmu', "%alias.entity_id = $mfmo.entity_id");
    $ttfeu = $query->join('taxonomy_term__field_external_uri', 'ttfeu', "%alias.entity_id = $mfsu.field_media_use_target_id");
    $query->condition("$ttfeu.field_external_uri_uri", 'http://pcdm.org/use#ServiceFile');
    $nfw = $query->leftJoin('node__field_weight', 'nfw', '%alias.entity_id = nfmo.entity_id');
    $nid_alias = $query->addField('nfmo', 'entity_id', 'nid');
    $mid_alias = $query->addExpression("MIN($mfmo.entity_id)", 'mid');
    $weight_alias = $query->addExpression("COALESCE($nfw.field_weight_value, 0)", 'w');
    $query->groupBy($nid_alias);
    $query->groupBy($weight_alias);
    $query->orderBy($weight_alias);

    $node_storage = $this->entityTypeManager->getStorage('node');
    $media_storage = $this->entityTypeManager->getStorage('media');

    $results = $query->execute()->fetchAll();
    foreach (array_chunk($results, $this->loadSize) as $chunk) {
      $nodes = $node_storage->loadMultiple(array_column($chunk, $nid_alias));
      $media = $media_storage->loadMultiple(array_column($chunk, $mid_alias));
      $mapped_media = array_combine(
        array_map(static function (MediaInterface $media) {
          return $media->{IslandoraUtils::MEDIA_OF_FIELD}?->entity?->id();
        }, $media),
        $media,
      );

      foreach ($nodes as $_node) {
        yield [$_node, $mapped_media[$_node->id()]];
      }
    }

  }

}
