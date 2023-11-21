<?php

namespace Drupal\islandora_iiif_presentation_api\Normalizer\V3;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileInterface;
use Drupal\iiif_presentation_api\Normalizer\EntityUriTrait;
use Drupal\iiif_presentation_api\Normalizer\V3\NormalizerBase;
use Drupal\iiif_presentation_api\Event\V3\ImageBodyEvent;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\islandora_iiif_presentation_api\Normalizer\FieldItemSpecificNormalizerTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Exception\LogicException;

/**
 * Normalizer for image items.
 */
class ImageItemNormalizer extends NormalizerBase {

  use EntityUriTrait;
  use FieldItemSpecificNormalizerTrait;

  /**
   * {@inheritDoc}
   */
  protected $supportedInterfaceOrClass = ImageItem::class;

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EventDispatcherInterface $eventDispatcher,
  ) {
    $this->supportedReferenceField = 'field_media_image';
    $this->supportedEntityType = 'media';
  }

  /**
   * {@inheritDoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    if (!isset($context['parent'])) {
      throw new LogicException('Normalization requires a parent context.');
    }
    $normalized = [];
    $values = $object->getValue();

    if (isset($values['height'])) {
      $normalized['height'] = (int) $values['height'];
    }
    if (isset($values['width'])) {
      $normalized['width'] = (int) $values['width'];
    }

    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')->load($values['target_id']);
    if ($file) {
      $this->addCacheableDependency($context, $file);
      $normalized['items'][] = [
        'id' => $context['parent']['id'],
        'type' => 'AnnotationPage',
        'items' => [
          [
            'id' => $this->getEntityUri($file, $context),
            'type' => 'Annotation',
            'motivation' => 'painting',
            'body' => $this->generateBody($file),
            'height' => (int) $normalized['height'],
            'width' => (int) $normalized['width'],
            'target' => $context['parent']['id'],
          ],
        ],
      ];
    }

    return $normalized;
  }

  /**
   * Generate the annotation body.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file for which to generate the body.
   *
   * @return array
   *   An associative array representing the body.
   */
  protected function generateBody(FileInterface $file) : array {
    /** @var \Drupal\iiif_presentation_api\Event\V3\ImageBodyEvent $event */
    $event = $this->eventDispatcher->dispatch(new ImageBodyEvent($file));
    $bodies = $event->getBodies();
    if (!$bodies) {
      return [];
    }
    $body = reset($bodies);
    $body['service'] = array_column($bodies, 'service');
    return $body;
  }

  /**
   * {@inheritDoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      ImageItem::class => TRUE,
    ];
  }

}
