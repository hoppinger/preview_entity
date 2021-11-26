<?php

namespace Drupal\preview_entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;

class LinkBuilder implements LinkBuilderInterface
{

  /**
   * @var Settings
  */
  protected $settings;

  /**
   * LinkBuilder constructor.
   * @param Settings $settings
   *
  */
  public function __construct(Settings $settings) {
    $this->settings = $settings;
  }

  public function determineValidUntil() {
    $preview_valid_days = $this->settings->get('preview_valid_days');
    $days_valid = intval($preview_valid_days && !empty($preview_valid_days) ? $preview_valid_days : 1);
    $seconds_valid = $days_valid * 86400;

    $now = \Drupal::time()->getRequestTime();

    return $now + $seconds_valid;
  }

  /**
   * @param EntityInterface $entity
   * @return array
  */
  public function buildPreviewLink(EntityInterface $entity, $valid_until) {
    $frontend_domain_url = $this->settings->get('frontend_domain_url');
    $shared_secret = $this->settings->get('shared_secret');

    $langcode_current = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    $entity_revision_key = $entity->getEntityTypeId() . '/' . $entity->id() . '/' . $entity->get('vid')->value;
    $token = $this->buildToken($valid_until, $entity_revision_key, $shared_secret);

    $node_tranlations = $entity->getTranslationLanguages();
    $default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();

    if(array_key_exists($langcode_current, $node_tranlations) && $langcode_current != $default_language) {
      $path = '/' . $langcode_current . '/preview/' . $entity_revision_key;
    }else {
      $path = '/preview/' . $entity_revision_key;
    }
    $uri =  $frontend_domain_url . $path . "?token=" . $token;
    
    return [
      '#type' => 'link',
      '#title' => $frontend_domain_url . $path,
      '#url' => Url::fromUri($uri),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
  }

  /**
   * @param EntityInterface $entity
   * @return array
   */
  public function buildPublishedLink(EntityInterface $entity) {
    $frontend_domain_url = $this->settings->get('frontend_domain_url');
    $path = $entity->toUrl()->toString();

    return [
      '#type' => 'link',
      '#title' => $frontend_domain_url . $path,
      '#url' => Url::fromUri($frontend_domain_url . $path),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
  }

  /**
   * @param integer $timestamp
   * @param string $entity_revision_key
   * @param string $shared_secret
   * @return string
  */
  public function buildToken($timestamp, $entity_revision_key, $shared_secret) {
    $entity_revision_key_timestamp = $entity_revision_key . '|' . $timestamp;
    $hmac = hash_hmac('sha256', $entity_revision_key_timestamp, $shared_secret, TRUE);
    $token = $timestamp . '|' . $hmac;
    $url_parameter = base64_encode($token);
    return str_replace(['+', '/', '='], ['-', '_', ''], $url_parameter);
  }
}
