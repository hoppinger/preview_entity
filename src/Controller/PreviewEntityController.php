<?php

namespace Drupal\preview_entity\Controller;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;

use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\serialization\Normalizer\CacheableNormalizerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class PreviewEntityController extends ControllerBase {

  /**
   * The serializer which serializes the views result.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

	/**
	 * @var RendererInterface
	 */
	protected $renderer;

  public function __construct(SerializerInterface $serializer, RendererInterface $renderer) {
    $this->serializer = $serializer;
    $this->renderer = $renderer;
  }

  public static function create(ContainerInterface $container) {
	  return new static($container->get('serializer'), $container->get('renderer'));
  }

	public function build($entity_type, $entity_id, $revision_id) {
	  $entity = $this->getEntity($entity_type, $entity_id, $revision_id);

	  if (!$entity) {
	  	$metadata = new CacheableMetadata();
	  	$metadata->addCacheTags([$entity_type . '_list']);
	  	throw new CacheableNotFoundHttpException($metadata);
	  }

		$serialization_context = [
			CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY => new CacheableMetadata(),
		];

		$context = new RenderContext();
		$serializer = $this->serializer;

		$output = $this->renderer
			->executeInRenderContext($context, function () use ($serializer, $entity, $serialization_context) {
				return $serializer->serialize($entity, 'json', $serialization_context);
			});

		$response = CacheableJsonResponse::fromJsonString($output);
		if (!$context->isEmpty()) {
			@trigger_error('Implicit cacheability metadata bubbling (onto the global render context) in normalizers is deprecated since Drupal 8.5.0 and will be removed in Drupal 9.0.0. Use the "cacheability" serialization context instead, for explicit cacheability metadata bubbling. See https://www.drupal.org/node/2918937', E_USER_DEPRECATED);
			$response->addCacheableDependency($context->pop());
		}
		$response->addCacheableDependency($serialization_context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY]);

	  return $response;
  }

  protected function getEntity($entity_type, $entity_id, $revision_id) {
  	if (!$this->entityTypeManager()->hasDefinition($entity_type)) {
  		return NULL;
	  }

		try {
      $storage = $this->entityTypeManager->getStorage($entity_type);
		} catch (PluginException $exception) {
  		return NULL;
		}

    $langcode_current = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  	$entity = $storage->loadRevision($revision_id);

  	if ($entity->hasTranslation($langcode_current)) {
      $entity = $entity->getTranslation($langcode_current);
    };

  	if ($entity && $entity->id() != $entity_id) {
  		return NULL;
	  }

  	return $entity;
  }

}
