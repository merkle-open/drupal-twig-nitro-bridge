<?php

namespace Drupal\twig_nitro_bridge\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TwigNitroBridgeSettingsForm.
 *
 * @package Drupal\twig_nitro_bridge\Form
 */
class TwigNitroBridgeSettingsForm extends ConfigFormBase {

  /**
   * Settings id.
   *
   * @var string
   */
  const SETTINGS_ID = 'twig_nitro_bridge.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twig_nitro_bridge_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS_ID,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS_ID);

    $form['frontend_dir'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Frontend directory'),
      '#description' => $this->t('Path to the frontend directory.'),
      '#default_value' => $config->get('frontend_dir'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('frontend_dir')) {
      $form_state->setErrorByName('frontend_dir', $this->t('The field "Frontend directory" is empty.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS_ID)
      ->set('frontend_dir', $form_state->getValue('frontend_dir'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
