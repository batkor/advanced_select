<?php

namespace Drupal\advanced_select\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\file\Entity\File;

/**
 * Plugin implementation of the 'advanced_select_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "advanced_select_field_formatter",
 *   label = @Translation("Advanced select"),
 *   field_types = {
 *     "list_string"
 *   }
 * )
 */
class AdvancedSelectFieldFormatter extends FormatterBase {

  private $widgetSettings;
  private $fieldOptions;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'image_style' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);

    return [
        'image_style' => [
          '#title' => t('Image style'),
          '#type' => 'select',
          '#default_value' => $this->getSetting('image_style'),
          '#empty_option' => t('None (original image)'),
          '#options' => $image_styles,
        ],
      ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if (empty($this->getSetting('image_style'))) {
      $summary[] = t('Original image');
    }
    else {
      $image_styles = image_style_options(FALSE);
      $summary[] = t('Image style: @style', ['@style' => $image_styles[$this->getSetting('image_style')]]);
    }

    return $summary;
  }

  public function setWidgetWettings($items) {
    $field_name = $items->getName();
    $field_entity_type_id = $items->getEntity()->getEntityTypeId();
    $field_entity_bundle = $items->getEntity()->bundle();
    $form_display = \Drupal::entityTypeManager()
                           ->getStorage('entity_form_display')
                           ->load($field_entity_type_id . '.' . $field_entity_bundle . '.default');
    $this->widgetSettings = $form_display->getComponent($field_name)['settings'];
  }

  public function setFieldOptions($items) {
    $provider = $items->getFieldDefinition()
                      ->getFieldStorageDefinition()
                      ->getOptionsProvider('value', $items->getEntity());
    $this->fieldOptions = OptGroup::flattenOptions($provider->getPossibleOptions());
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $this->setWidgetWettings($items);
    $this->setFieldOptions($items);

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $render = [];
    $value = $item->value;
    $options = $this->fieldOptions;
    $widgetSettings = $this->widgetSettings['values'];
    // If the stored value is in the current set of allowed values, display
    // the associated label, otherwise just display the raw value.
    $output = isset($options[$value]) ? $options[$value] : $value;

    $output = "<p class='value'>$output</p>";

    if (!empty($widgetSettings[$value]['img']['fids'])) {
      $file = File::load($widgetSettings[$value]['img']['fids']);
      if (empty($this->getSetting('image_style'))) {
        $render = [
          '#theme' => 'image',
          '#uri' => $file->getFileUri(),
          '#prefix' => '<div class="img">',
          '#suffix' => '</div>',
        ];
      }
      else {
        $render = [
          '#theme' => 'image_style',
          '#style_name' => $this->getSetting('image_style'),
          '#uri' => $file->getFileUri(),
          '#prefix' => '<div class="img">',
          '#suffix' => '</div>',
        ];
      }

      $render = \Drupal::service('renderer')
                       ->render($render);
    }

    return $render . $output;
  }

}