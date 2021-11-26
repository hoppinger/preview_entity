<?php

namespace Drupal\preview_entity;

use Drupal\Core\Entity\EntityInterface;

interface LinkBuilderInterface {

  public function determineValidUntil();

	/**
	 * @param EntityInterface $entity
	 * @return array
	 */
	public function buildPreviewLink(EntityInterface $entity, $valid_until);

  /**
   * @param EntityInterface $entity
   * @return array
   */
  public function buildPublishedLink(EntityInterface $entity);

	/**
	 * @param $timestamp int
	 * @param String $entity_revision_key
	 * @param String $shared_secret
	 * @return mixed
	 */
	public function buildToken($timestamp, $entity_revision_key, $shared_secret);

}