<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

use Behat\Mink\Element\DocumentElement;
use Drupal\Component\Utility\Html;

/**
 * Trait for handling of Paragraph related test actions.
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
trait ThunderParagraphsTestTrait {

  /**
   * Get number of paragraphs for defined field on current page.
   *
   * @param string $fieldName
   *   Paragraph field name.
   *
   * @return int
   *   Returns number of paragraphs.
   */
  protected function getNumberOfParagraphs($fieldName) {
    $fieldNamePart = HTML::cleanCssIdentifier($fieldName);

    $paragraphRows = $this->xpath("//*[@id=\"edit-{$fieldNamePart}-wrapper\"]//table[starts-with(@id, \"{$fieldNamePart}-values\")]/tbody/tr[contains(@class, \"draggable\")]");

    return count($paragraphRows);
  }

  /**
   * Get all paragraphs for the given field.
   *
   * @param string $fieldName
   *   Paragraph field name.
   * @param int $position
   *   The position of the paragraph item in the array.
   *
   * @return Behat\Mink\Element\NodeElement
   *   The paragraph node element.
   */
  protected function getParagraphItem($fieldName, $position) {
    $fieldNamePart = HTML::cleanCssIdentifier($fieldName);

    return $this->xpath("//*[@id=\"edit-{$fieldNamePart}-wrapper\"]//table[starts-with(@id, \"{$fieldNamePart}-values\")]/tbody/tr[contains(@class, \"draggable\")][{$position}]//div[contains(@class, \"paragraph-item\")]")[0];
  }

  /**
   * Add paragraph for field with defined paragraph type.
   *
   * This uses paragrpahs modal widget.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $type
   *   Type of the paragraph.
   * @param int $position
   *   Position of the paragraph.
   *
   * @return int
   *   Returns index for added paragraph.
   */
  public function addParagraph($fieldName, $type, $position = NULL) {
    $page = $this->getSession()->getPage();
    $numberOfParagraphs = $this->getNumberOfParagraphs($fieldName);

    $fieldSelector = HTML::cleanCssIdentifier($fieldName);
    if ($position === NULL || $position > $numberOfParagraphs) {
      $position = $numberOfParagraphs;
      $addButtonSelector = "input[id^='edit-$fieldSelector-add-more-first-button-area-add-more']";
    }
    else {
      $addButtonPosition = $position * 2 + 1;
      $addButtonSelector = "#edit-{$fieldSelector}-wrapper table > tbody > tr:nth-child({$addButtonPosition}) input";
    }

    $addButton = $page->find('css', $addButtonSelector);
    $this->scrollElementInView($addButtonSelector);

    $addButton->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->find('xpath', "//ul[@class='paragraphs-add-dialog-list']/li/button[@data-type='$type']")
      ->click();

    $this->assertSession()->assertWaitOnAjaxRequest();

    // Test if we have one more paragraph now.
    static::assertEquals($this->getNumberOfParagraphs($fieldName), ($numberOfParagraphs + 1));

    // Retrieve new paragraps delta from id attribute of the item.
    $paragraphItem = $this->getParagraphItem($fieldName, ($position + 1));
    $itemId = $paragraphItem->getAttribute('id');
    preg_match("/^edit-{$fieldSelector}-(\d+)--/", $itemId, $matches);

    return $matches[1];
  }

  /**
   * Add Image paragraph.
   *
   * @param string $fieldName
   *   Field name.
   * @param array $media
   *   List of media identifiers.
   * @param int $position
   *   Position of the paragraph.
   */
  public function addImageParagraph($fieldName, array $media, $position = NULL) {
    $paragraphIndex = $this->addParagraph($fieldName, 'image', $position);

    $this->selectMedia("{$fieldName}_{$paragraphIndex}_subform_field_image", 'image_browser', $media);

  }

  /**
   * Add Video paragraph.
   *
   * @param string $fieldName
   *   Field name.
   * @param array $media
   *   List of media identifiers.
   * @param int $position
   *   Position of the paragraph.
   */
  public function addVideoParagraph($fieldName, array $media, $position = NULL) {
    $paragraphIndex = $this->addParagraph($fieldName, 'video', $position);

    $this->selectMedia("{$fieldName}_{$paragraphIndex}_subform_field_video", 'video_browser', $media);

  }

  /**
   * Add Gallery paragraph.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $name
   *   Name of the gallery.
   * @param array $media
   *   List of media identifiers.
   * @param int $position
   *   Position of the paragraph.
   */
  public function addGalleryParagraph($fieldName, $name, array $media, $position = NULL) {
    $paragraphIndex = $this->addParagraph($fieldName, 'gallery', $position);

    $this->createGallery($name, "{$fieldName}_{$paragraphIndex}_subform_field_media", $media);
  }

  /**
   * Adding text type paragraphs.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $text
   *   Text for paragraph.
   * @param string $type
   *   Type of text paragraph.
   * @param int $position
   *   Position of the paragraph.
   */
  public function addTextParagraph($fieldName, $text, $type = 'text', $position = NULL) {
    $paragraphIndex = $this->addParagraph($fieldName, $type, $position);

    $this->fillCkEditor(
      $this->getSession()->getPage(),
      "textarea[name='{$fieldName}[{$paragraphIndex}][subform][field_text][0][value]']",
      $text
    );
  }

  /**
   * Create Twitter, Instagram or PinterestParagraph.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $socialUrl
   *   Url to tweet, instagram or pinterest.
   * @param string $type
   *   Type of paragraph (twitter|instagram|pinterest).
   * @param int $position
   *   Position of the paragraph.
   */
  public function addSocialParagraph($fieldName, $socialUrl, $type, $position = NULL) {
    $paragraphIndex = $this->addParagraph($fieldName, $type, $position);

    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    $page->fillField("{$fieldName}[{$paragraphIndex}][subform][field_media][0][inline_entity_form][field_url][0][uri]", $socialUrl);
  }

  /**
   * Add link paragraph.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $urlText
   *   Text that will be displayed for link.
   * @param string $url
   *   Link url.
   * @param int $position
   *   Position of the paragraph.
   */
  public function addLinkParagraph($fieldName, $urlText, $url, $position = NULL) {
    $paragraphIndex = $this->addParagraph($fieldName, 'link', $position);

    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    $page->fillField("{$fieldName}[{$paragraphIndex}][subform][field_link][0][title]", $urlText);
    $page->fillField("{$fieldName}[{$paragraphIndex}][subform][field_link][0][uri]", $url);
  }

  /**
   * Click button for editing of paragraph.
   *
   * @param \Behat\Mink\Element\DocumentElement $page
   *   Current active page.
   * @param string $paragraphsFieldName
   *   Field name in content type used to paragraphs.
   * @param int $index
   *   Index of paragraph to be edited, starts from 0.
   */
  public function editParagraph(DocumentElement $page, $paragraphsFieldName, $index) {
    $editButtonName = "{$paragraphsFieldName}_{$index}_edit";

    $this->scrollElementInView("[name=\"{$editButtonName}\"]");
    $page->pressButton($editButtonName);
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

}
