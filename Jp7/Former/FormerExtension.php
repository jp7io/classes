<?php

namespace Jp7\Former;

use Illuminate\Support\Str;
use Former\Former as OriginalFormer;
use InterAdmin\Models\Record;
use Log;
use Jp7\InterAdmin\Field\FieldHeader;
use Lang;
use UnexpectedValueException;
use BadMethodCallException;
use DateTime;

/**
 * Add InterAdmin settings on former automatically.
 */
class FormerExtension
{
    use RowTrait, DecoratorTrait;

    private ?\InterAdmin\Models\Record $model = null;
    private ?array $rules = null;
    private \Former\Former $former;
    private bool $labelless = false; // Custom setting

    public function __construct(OriginalFormer $former)
    {
        // Send missing validations to Log
        if (getenv('APP_DEBUG')) {
            if ($errors = app()['session']->get('errors')) {
                Log::notice('Validation error', $errors->all());
            }
        }

        $this->former = $former;
    }

    /**
     * Forward calls to Former and decorate fields.
     */
    public function __call($method, $arguments)
    {
        $result = call_user_func_array([$this->former, $method], $arguments);

        if ($result instanceof \Former\Form\Form) {
            $this->decorateFormInterAdmin($result);
        } elseif ($result instanceof \Former\Traits\Field) {
            $this->decorateField($result); // DecoratorTrait
            $this->decorateFieldInterAdmin($result);
        }

        return $result;
    }

    public function &__get(string $property): mixed
    {
        return $this->former->$property;
    }

    public function __set(string $property, mixed $value)
    {
        $this->former->$property = $value;
    }

    public function labelless_open(...$arguments)
    {
        $this->labelless = true;
        return $this->open(...$arguments)->addClass('labelless');
    }

    public function populate($model)
    {
        if ($model instanceof Record) {
            $this->model = $model;
            $this->rules = $model->getRules();
        }

        return $this->former->populate($model);
    }

    public function close()
    {
        $this->model = null;

        return $this->former->close();
    }

    /**
     * Add "rules" and "action" from InterAdmin.
     */
    private function decorateFormInterAdmin(\Former\Form\Form $form): void
    {
        if ($this->model) {
            $form->rules($this->rules);
            if ($this->model->getRoute('store')) {
                $form->action($this->model->getUrl('store'));
            }
        }
    }

    /**
     * Set "label" and "options" from InterAdmin.
     */
    private function decorateFieldInterAdmin(\Former\Traits\Field $field): void
    {
        if (!$this->model || (!$alias = $field->getName())) {
            return;
        }
        if (str_contains($alias, '[')) {
            // Nested models: socios[0][nome]
            $aliasParts = explode('.', $this->toDots($alias));
            if (count($aliasParts) !== 3) {
                return;
            }
            list($childName, $i, $childAlias) = $aliasParts;
            try {
                // A child relation's related model is a template carrying the child's type_id.
                $related = $this->model->$childName()->getRelated();
                if ($related instanceof Record) {
                    $this->decorateFieldByTypeAndAlias($field, $related->getType(), $childAlias);
                }
            } catch (BadMethodCallException $e) {
                // no child type with this name
            }
            return;
        }
        $type = $this->model->getType();
        $this->decorateFieldByTypeAndAlias($field, $type, $alias);
    }

    private function decorateFieldByTypeAndAlias(\Former\Traits\Field $field, \InterAdmin\Models\Type $type, $alias): void
    {
        $fieldDefinitions = $type->getFields();
        $aliases = array_flip($type->getFieldAliases());

        if (empty($aliases[$alias])) {
            return;
        }

        $name = $aliases[$alias];
        $fieldDefinition = $fieldDefinitions[$name];

        // Set label
        if (!Lang::has('validation.attributes.'.$alias)) {
            // FIXME FieldHeader::text() roda funcoes special_
            $label = $fieldDefinition['label'] ?: FieldHeader::text($fieldDefinition);
            $field->label($label);
        }

        // Populate options
        if (Str::startsWith($name, 'select_')) {
            $this->populateOptions($field, $fieldDefinition['name']);
        }
        // Fix date format
        if ($field->getType() === 'date' && $field->getValue() instanceof DateTime) {
            $field->setValue($field->getValue()->format('Y-m-d'));
        }

        if ($rules = $this->former->getRules($alias)) {
            if (isset($rules['name_and_surname'])) {
                $field->pattern('\S+ +\S.*')
                    ->title('Preencha nome e sobrenome');
            }
        }
        if ($this->labelless) {
            $field->placeholder($field->getLabel()->getValue());
        }
    }

    protected function toDots($name): string
    {
        $name = str_replace( // same replace Laravel and Former do
            ['[', ']'],
            ['.', ''],
            $name
        );
        return trim($name, '.');
    }

    private function populateOptions(\Former\Traits\Field $field, $optionsType): void
    {
        if ($field->getType() === 'select') {
            $field->options(function () use ($optionsType): array {
                $options = [];
                foreach ($optionsType->records()->get() as $record) {
                    $options[$record->id] = $record->getName();
                }
                return $options;
            });
        } elseif ($field->getType() === 'radios') {
            $radios = [];
            foreach ($optionsType->records()->get() as $record) {
                if (!$name = $record->getName()) {
                    throw new UnexpectedValueException('getName() returned empty value for Record ID: '.$record->id);
                }
                $radios[$name] = ['value' => $record->id];
            }
            $field->radios($radios);
        }
    }
}
