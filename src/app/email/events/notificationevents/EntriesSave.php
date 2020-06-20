<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   https://craftcms.github.io/license
 */

namespace barrelstrength\sproutbase\app\email\events\notificationevents;

use barrelstrength\sproutbase\app\email\base\NotificationEvent;
use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\events\ElementEvent;
use craft\events\ModelEvent;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;

/**
 *
 * @property string $eventHandlerClassName
 * @property array $allSections
 * @property Entry|null|array|ElementInterface $mockEventObject
 * @property null $eventObject
 * @property string $name
 * @property mixed $eventName
 * @property string $description
 * @property string $eventClassName
 */
class EntriesSave extends NotificationEvent
{
    public $whenNew = false;

    public $whenUpdated = false;

    public $sectionIds;

    public $availableSections;

    public function getEventClassName()
    {
        return Entry::class;
    }

    public function getEventName()
    {
        return Entry::EVENT_AFTER_SAVE;
    }

    public function getEventHandlerClassName()
    {
        return ModelEvent::class;
    }

    public function getName(): string
    {
        return Craft::t('sprout', 'When an entry is saved');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout', 'Triggered when an entry is saved.');
    }

    /**
     * @param array $settings
     *
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function getSettingsHtml($settings = []): string
    {
        if (!$this->availableSections) {
            $this->availableSections = $this->getAllSections();
        }

        return Craft::$app->getView()->renderTemplate('sprout/notifications/_components/events/save-entry', [
            'event' => $this,
        ]);
    }

    public function getEventObject()
    {
        $event = $this->event ?? null;

        return $event->sender ?? null;
    }

    /**
     * @return array|ElementInterface|Entry|null
     */
    public function getMockEventObject()
    {
        $criteria = Entry::find();

        $ids = $this->sectionIds;

        if (is_array($ids) && count($ids)) {

            $id = array_shift($ids);

            $criteria->where([
                'sectionId' => $id,
            ]);
        }

        return $criteria->one();
    }

    /**
     * @return array
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            'whenNew', 'required', 'when' => function() {
                return $this->whenUpdated == false;
            },
        ];
        $rules[] = [
            'whenUpdated', 'required', 'when' => function() {
                return $this->whenNew == false;
            },
        ];
        $rules[] = [['whenNew', 'whenUpdated'], 'validateWhenTriggers'];
        $rules[] = [['event'], 'validateEvent'];
        $rules[] = ['sectionIds', 'default', 'value' => false];
        $rules[] = [['sectionIds'], 'validateSectionIds'];

        return $rules;
    }

    public function validateWhenTriggers()
    {
        /**
         * @var ElementEvent $event
         */
        $event = $this->event ?? null;

        $isNewEntry = $event->isNew ?? false;

        $matchesWhenNew = $this->whenNew && $isNewEntry ?? false;
        $matchesWhenUpdated = $this->whenUpdated && !$isNewEntry ?? false;

        if (!$matchesWhenNew && !$matchesWhenUpdated) {
            $this->addError('event', Craft::t('sprout', 'When an Entry is saved Event does not match any scenarios.'));
        }

        // Make sure new entries are new.
        if (($this->whenNew && !$isNewEntry) && !$this->whenUpdated) {
            $this->addError('event', Craft::t('sprout', '"When an entry is created" is selected but the entry is being updated.'));
        }

        // Make sure updated entries are not new
        if (($this->whenUpdated && $isNewEntry) && !$this->whenNew) {
            $this->addError('event', Craft::t('sprout', '"When an entry is updated" is selected but the entry is new.'));
        }
    }

    public function validateEvent()
    {
        $event = $this->event ?? null;

        if (!$event) {
            $this->addError('event', Craft::t('sprout', 'ElementEvent does not exist.'));
        }

        // Only trigger this event when an Entry is Live.
        // When an Entry Type is updated, SCENARIO_ESSENTIALS
        // When status is disabled, SCENARIO_DEFAULT
        if ($event->sender->getScenario() !== Element::SCENARIO_LIVE) {
            $this->addError('event', Craft::t('sprout', 'The `EntriesSave` Notification Event only triggers when an Entry is saved in a live scenario.'));
        }

        if ($event->sender->getStatus() !== Entry::STATUS_LIVE) {
            $this->addError('event', Craft::t('sprout', 'The `EntriesSave` Notification Event only triggers for enabled element.'));
        }

        if (get_class($event->sender) !== Entry::class) {
            $this->addError('event', Craft::t('sprout', 'The `EntriesSave` Notification Event does not match the craft\elements\Entry class.'));
        }
    }

    public function validateSectionIds()
    {
        /**
         * @var ElementEvent $event
         */
        $element = $this->event->sender ?? null;
        $elementId = null;

        if (get_class($element) === Entry::class && $element !== null) {
            $elementId = $element->getSection()->id;
        }

        // If entry sections settings are unchecked
        if ($this->sectionIds == false) {
            $this->addError('event', Craft::t('sprout', 'No Section has been selected.'));
        }

        // If any section ids were checked, make sure the entry belongs in one of them
        if (is_array($this->sectionIds) and !in_array($elementId, $this->sectionIds, false)) {
            $this->addError('event', Craft::t('sprout', 'Saved Entry Element does not match any selected Sections.'));
        }
    }

    /**
     * Returns an array of sections suitable for use in checkbox field
     *
     * @return array
     */
    protected function getAllSections(): array
    {
        $result = Craft::$app->sections->getAllSections();
        $options = [];

        foreach ($result as $key => $section) {
            $options[] = [
                'label' => $section->name,
                'value' => $section->id,
            ];
        }

        return $options;
    }
}
