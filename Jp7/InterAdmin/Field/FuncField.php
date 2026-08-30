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
     * ⚠ It must not collide with a `campos` attribute name -- that array is otherwise entirely
     * the field's stored definition, and a handler reads both out of it.
     */
    public const FIELD_ID_TIPO = 'field_id_tipo';

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
        if (!is_callable($this->nome)) {
            return 'Function '.$this->nome.' not found.';
        }
        try {
            ob_start();
            // http://wiki.jp7.com.br:81/jp7/InterAdmin:Special
            // callable(array $campo, mixed $value, string $parte, stdClass $record)
            $campo = $this->campo + [self::FIELD_ID_TIPO => $this->type ? (int) $this->type->type_id : null];
            $response = call_user_func($this->nome, $campo, $value, $parte, $this->record);
            $response .= ob_get_clean();
            return $response;
        } catch (Throwable $e) {
            if (getenv('APP_DEBUG')) {
                throw $e;
            }
            Log::error($e);
            return '(erro: '.$this->nome.')';
        }
    }

    protected function getDefaultValue()
    {
        if ($this->default) {
            return $this->default;
        }
        if (isset($_POST[$this->tipo])) {
            return $_POST[$this->tipo][0];
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
        $relation = str_replace(['_ids', '_id'], '', $this->nome_id);
        $data = $this->type->getRelationshipData($relation);
        $field = new SelectAjaxField([
            'nome' => $data['tipo']
        ] + $this->campo);
        return $field->searchOptions($search);
    }
}
