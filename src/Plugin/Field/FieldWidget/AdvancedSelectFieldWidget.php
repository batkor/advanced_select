<?php

namespace Drupal\advanced_select\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Plugin implementation of the 'advanced_select_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "advanced_select_field_widget",
 *   label = @Translation("Advanced select field widget"),
 *   field_types = {
 *     "list_string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class AdvancedSelectFieldWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {

    return [
        'values' => [],
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    $folder = 'public://advanced_select/' . $this->fieldDefinition->getName() . '/';
    if (file_prepare_directory($folder, FILE_CREATE_DIRECTORY)) {
      $allowed_values = $this->getFieldSetting('allowed_values');
      $default_values = $this->getSetting('values');

      $elements['values'] = [
        '#prefix' => '<div class="advanced_select_items">',
        '#suffix' => '</div>',
      ];
      $elements['advanced_select_style'] = [
        '#type' => 'html_tag',
        '#tag' => 'style',
        '#value' => '.advanced_select_items{
            display: flex; 
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;}
          .advanced_select_items .form-wrapper{
            flex-basis: 33.33%; margin-bottom: 10px;}',
      ];

      foreach ($allowed_values as $key => $allowed_value) {
        $default_img = empty($default_values) ? NULL : $default_values[$key]['img'];
        $elements['values'][$key] = [
          '#type' => 'container',
          '#element_validate' => [
            [$this, 'settingsFormSaveValidate'],
          ],
        ];
        $elements['values'][$key]['label'] = [
          '#type' => 'textfield',
          '#value' => $allowed_value,
          '#disabled' => TRUE,
        ];
        $elements['values'][$key]['img'] = [
          '#type' => 'managed_file',
          '#title' => 'Изображение',
          '#upload_location' => $folder,
          '#upload_validators' => ['file_validate_extensions' => ['gif png jpg jpeg'],],
          '#default_value' => $default_img,
          '#element_validate' => [
            [$this, 'settingsFormImgValidate'],
          ],
        ];
      }
    }
    else {
      \Drupal\Core\Form\drupal_set_message($this->t('Could not create directory'));
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormImgValidate($element, FormStateInterface $form_state) {
    if (!empty($element['#value']['fids'])) {
      $file = File::load($element['#value']['fids']['0']);
      $file->status = FILE_STATUS_PERMANENT;
      $file->save();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $elems = [];
    foreach ($this->getSetting('values') as $item) {
      $elems[] = $item['label'];
    }

    $summary = [];
    $summary[] = $this->t('Elements: @elems', ['@elems' => implode(', ', $elems)]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $field_name = $this->fieldDefinition->getName();

    $element += [
      '#type' => 'select',
      '#title' => $this->fieldDefinition->getLabel(),
      '#description' => $this->fieldDefinition->getDescription(),
      '#default_value' => $this->getSelectedOptions($items),
      '#multiple' => $this->multiple && count($this->options) > 1,
      '#options' => $this->getOptions($items->getEntity()),
      '#attributes' => [
        'class' => ['hidden', 'advanced_select'],
        'data-advanced_select' => $field_name,
      ],
    ];

    $form['#attached']['drupalSettings']['advanced_select'][$field_name] = $this->getImgUrl();
    $form['#attached']['library'][] = 'advanced_select/main';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getImgUrl() {
    $output = [];
    foreach ($this->getSetting('values') as $key => $item) {
      $output[$key]['label'] = $item['label'];
      if (!empty($item['img']['fids'])) {
        $file = File::load($item['img']['fids']);
        $output[$key]['url'] = $file->url();
      }
      else {
        $output[$key]['url'] = '';
      }
    }

    return $output;
  }

}
