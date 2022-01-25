<?php

declare(strict_types = 1);

namespace Drupal\oe_whitelabel_user_profile\EventSubscriber;

use Drupal\cas\Event\CasPostLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Service\CasHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Copies the EU Login attributes to user fields.
 */
class EuLoginAttributesToUserFieldsSubscriber implements EventSubscriberInterface {

  /**
   * Array mapping of EU Login attributes with user account fields.
   */
  const USER_EU_LOGIN_ATTRIBUTE_MAPPING = [
    'mail' => 'email',
    'field_first_name' => 'firstName',
    'field_last_name' => 'lastName',
    'field_organization' => 'domain',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CasHelper::EVENT_POST_LOGIN => 'updateUserData',
      CasHelper::EVENT_PRE_REGISTER => 'processUserProperties',
    ];
  }

  /**
   * Updates the user data based on the information taken from EU Login.
   *
   * @param \Drupal\cas\Event\CasPostLoginEvent $event
   *   The triggered event.
   */
  public function updateUserData(CasPostLoginEvent $event): void {
    $properties = self::convertEuLoginAttributesToFieldValues($event->getCasPropertyBag()->getAttributes());
    $account = $event->getAccount();
    foreach ($properties as $name => $value) {
      $account->set($name, $value);
    }
    $account->save();
  }

  /**
   * Adds user properties based on the information taken from EU Login.
   *
   * @param \Drupal\cas\Event\CasPreRegisterEvent $event
   *   The triggered event.
   */
  public function processUserProperties(CasPreRegisterEvent $event): void {
    $attributes = $event->getCasPropertyBag()->getAttributes();
    $event->setPropertyValues(self::convertEuLoginAttributesToFieldValues($attributes));
  }

  /**
   * Converts the EU Login attributes into a Drupal field/values array.
   *
   * @param array $attributes
   *   An array containing a series of EU Login attributes.
   *
   * @return array
   *   An associative array of field values indexed by the field name.
   */
  protected static function convertEuLoginAttributesToFieldValues(array $attributes): array {
    $values = [];
    foreach (static::USER_EU_LOGIN_ATTRIBUTE_MAPPING as $field_name => $property_name) {
      if (!empty($attributes[$property_name])) {
        $values[$field_name] = $attributes[$property_name];
      }
    }
    return $values;
  }

}
