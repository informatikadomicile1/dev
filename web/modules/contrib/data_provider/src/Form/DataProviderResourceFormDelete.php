<?php

declare(strict_types=1);

namespace Drupal\data_provider\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Define the data provider resource delete form.
 */
class DataProviderResourceFormDelete extends EntityConfirmFormBase {

  /**
   * {@inheritDoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t(
      'Are you sure you want to delete %label?',
      ['%label' => $this->entity->label()]
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getCancelUrl(): Url {
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(
    array &$form,
    FormStateInterface $form_state
  ): void {
    $this->entity->delete();

    $this->messenger()->addMessage(
      $this->t('The data provider resource %label has been deleted!', [
        '%label' => $this->entity->label(),
      ])
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
