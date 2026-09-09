<?php

namespace Jp7\InterAdmin\Field;

use Illuminate\Support\Str;
use Throwable;
use Log;

class FuncField extends ColumnField
{
    /**
     * The key composed into the $campo a handler receives: the type_id of the type this field
     * belongs to, which is the field's OWN type rather than the list's.
     *
     * ⚠ It must not collide with a field-definition attribute name -- that array is otherwise entirely
     * the field's stored definition, and a handler reads both out of it.
     */
    public const FIELD_TYPE_ID = 'field_type_id';

    protected $id = 'func';

    public function getText(): string
    {
        return strip_tags($this->getCellHtml());
    }

    public function getHeaderHtml(): string
    {
        return $this->getFuncHtml('', 'header');
    }

    public function getCellHtml(): string
    {
        return $this->getFuncHtml($this->getValue(), 'list');
    }

    protected function getFuncHtml($value, $parte): string
    {
        if (!is_callable($this->name)) {
            return 'Function '.$this->name.' not found.';
        }
        $buffers = ob_get_level();

        try {
            ob_start();
            // http://wiki.jp7.com.br:81/jp7/InterAdmin:Special
            // callable(array $campo, mixed $value, string $parte, stdClass $record)
            $campo = $this->campo + [self::FIELD_TYPE_ID => $this->ownerType ? (int) $this->ownerType->type_id : null];
            $response = call_user_func($this->name, $campo, $value, $parte, $this->record);
            $response .= ob_get_clean();
            return $response;
        } catch (Throwable $e) {
            // ⚠ The buffer above is only closed on the success path, so every throwing handler
            // leaked one -- and neither arm below reaches it. A leaked buffer captures whatever the
            // rest of the request echoes and PHP flushes it after the response, so a page with two
            // erroring fields is a 200 with stray bytes appended and nothing in any log saying so.
            while (ob_get_level() > $buffers) {
                ob_end_clean();
            }

            if (getenv('APP_DEBUG')) {
                throw $e;
            }
            Log::error($e);
            return '(erro: '.$this->name.')';
        }
    }

    protected function getDefaultValue()
    {
        if ($this->default) {
            return $this->default;
        }
        if (isset($_POST[$this->type])) {
            return $_POST[$this->type][0];
        }
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getEditTag(): string
    {
        $html = trim($this->getFuncHtml($this->getValue(), 'edit'));

        if (Str::startsWith($html, '<tr') || Str::endsWith($html, '</tr>')) {
             $html = '<table class="special-shim">'.$html.'</table>';
        }
        return $html;
    }

    public function searchOptions($search)
    {
        $relation = str_replace(['_ids', '_id'], '', $this->name_id);
        $data = $this->ownerType->getRelationshipData($relation);
        $field = new SelectAjaxField([
            // ⚠ $data is getRelationshipData()'s array, whose `tipo` is a Type object; the key
            // being built is the field-definition row's `name`, which for a select_ holds that same Type.
            'name' => $data['tipo']
        ] + $this->campo);
        return $field->searchOptions($search);
    }
}
