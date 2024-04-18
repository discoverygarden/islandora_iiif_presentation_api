<?php

namespace Drupal\islandora_iiif_presentation_api\Normalizer\V3;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\iiif_presentation_api\Event\V3\ImageBodyEvent;
use Drupal\iiif_presentation_api\MappedFieldInterface;
use Drupal\iiif_presentation_api\Normalizer\EntityUriTrait;
use Drupal\iiif_presentation_api\Normalizer\V3\NormalizerBase;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\islandora_iiif_presentation_api\Normalizer\FieldItemSpecificNormalizerTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Exception\LogicException;

/**
 * Normalizer for image items.
 */
class ImageItemNormalizer extends NormalizerBase implements MappedFieldInterface {

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
    $this->targetFieldName = 'field_media_image';
    $this->targetEntityTypeId = 'media';
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

      try {
        $item_url = $file->toUrl('canonical');
      }
      catch (UndefinedLinkTemplateException $e) {
        $item_url = Url::fromRoute(
          "rest.entity.{$file->getEntityTypeId()}.GET",
          [
            $file->getEntityTypeId() => $file->id(),
          ]
        );
      }
      $generated_item_url = $item_url->setAbsolute()
        ->toString(TRUE);
      $this->addCacheableDependency($context, $generated_item_url);
      $item_id = $generated_item_url->getGeneratedUrl();
      $page_id = "{$item_id}/page/0";
      $normalized['items'][] = [
        'id' => $page_id,
        'type' => 'AnnotationPage',
        'items' => [
          [
            'id' => "{$item_id}/page/0/annotation/0",
            'type' => 'Annotation',
            'motivation' => 'painting',
            'body' => $this->generateBody($file, context: $context),
            'height' => (int) $normalized['height'],
            'width' => (int) $normalized['width'],
            'target' => $page_id,
          ],
        ],
      ];
      $normalized['thumbnail'] = [
        $this->generateBody($file, '!256,256', $context),
      ];
    }

    return $normalized;
  }

  /**
   * Generate the annotation body.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file for which to generate the body.
   * @param string $dimension_spec
   *   IIIF Image API dimension/size hint.
   * @param array $context
   *   The serializer context.
   *
   * @return array
   *   An associative array representing the body.
   */
  protected function generateBody(FileInterface $file, string $dimension_spec = 'full', array $context = []) : array {
    /** @var \Drupal\iiif_presentation_api\Event\V3\ImageBodyEvent $event */
    $event = $this->eventDispatcher->dispatch(new ImageBodyEvent($file, $dimension_spec));
    $this->addCacheableDependency($context, $event);
    $bodies = $event->getBodies();
    if (!$bodies) {
      return [];
    }
    $body = reset($bodies);
    $body['service'] = array_merge(...array_filter(array_column($bodies, 'service')));
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
