<?php

namespace Jp7\Interadmin\Field;

use Illuminate\Support\Str;
use Jp7\Interadmin\Type;

/**
 * @property string $tipo
 * @property Type|string $nome
 * @property string $ajuda
 * @property string|int $tamanho
 * @property string|bool $obrigatorio    'S' or ''
 * @property string $separador
 * @property string $xtra
 * @property string|bool $lista     'S' or ''
 * @property numeric $orderby
 * @property string|bool $combo     'S' or ''
 * @property string|bool $readonly  'S' or ''
 * @property string|bool $form      'S' or ''
 * @property string $label
 * @property mixed $permissoes
 * @property string $default
 * @property string $nome_id
 */
class ColumnField extends BaseField
{
    /**
     * @var array
     */
    protected $campo;

    /**
     * @param array $campo
     */
    public function __construct(array $campo)
    {
        $this->campo = $campo;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (!isset($this->campo[$name])) {
            return;
        }
        return $this->campo[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->campo[$name]);
    }

    /**
     * @param string $name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->campo[$name]);
    }

    public function getHeaderTag()
    {
        return parent::getHeaderTag()->title($this->tipo);
    }

    public function getLabel()
    {
        return $this->nome;
    }

    public function getText()
    {
        return $this->getValue();
    }

    public function getEditTag()
    {
        $input = parent::getEditTag();
        if ($this->ajuda) {
            $input->help($this->ajuda);
        }
        // Title is just for information
        $input->getLabel()->setAttribute('title', $this->nome_id.' ('.$this->tipo.', xtra: '.$this->xtra.')');
        $input->onGroupAddClass($this->id);
        $input->onGroupAddClass($this->nome_id.'-group');
        if ($this->separador) {
            $input->onGroupAddClass('has-separator');
        }
        $this->handleReadonly($input);
        return $input;
    }

    protected function getFormerName()
    {
        return $this->tipo.(is_null($this->index) ? '' : '['.$this->index.']');
    }

    protected function getFormerId()
    {
        return $this->nome_id.(is_null($this->index) ? '' : '_'.$this->index);
    }

    protected function getValue()
    {
        $column = $this->tipo;
        $value = $this->record ? $this->record->$column : null;
        if (empty($this->record->id) && !$value) {
            $value = $this->getDefaultValue();
        }
        return $value;
    }

    protected function getDefaultValue()
    {
        return $this->default;
    }

    protected function handleReadonly($input)
    {
        if ($this->isReadonly()) {
            $input->disabled();
        }
    }

    public function isReadonly()
    {
        return $this->readonly || !$this->hasPermissions();
    }

    /**
     * Whether the acting user may edit this field.
     *
     * Reads the authenticated user instead of the `global $s_user` array the InterAdmin
     * admin used to publish. That array was only ever a copy of this same user, built by
     * Interadmin_Login::getUserData(), and it is being retired -- this method was the last
     * live reader of it anywhere, which is what the "Tempfix" note in the host app's
     * LegacySessionUser middleware refers to.
     *
     * The host app's authenticated user must expose isSa(), isAdmin() and permissionTipo().
     * This package deliberately does not name that class -- it has no dependency on any
     * host's namespace -- so the requirement is stated here rather than type-hinted. A host
     * whose user model predates those accessors gets a "call to undefined method" when a
     * form renders. That is the intended failure: a permission check that degraded quietly
     * would hand out edit rights, not withhold them.
     */
    protected function hasPermissions()
    {
        $user = auth()->user();

        if (!$this->permissoes || $user?->isSa()) {
            return true;
        }
        // Null-safe, matching what it replaces: $s_user was [] with nobody logged in, so
        // every subscript read as null. `permissoes` is non-empty by the guard above, so an
        // absent user still compares false here rather than matching.
        if ((string) $this->permissoes === (string) $user?->permissionTipo()) {
            // By select with the user type, used by CI Intercambio
            return true;
        }
        if ($this->permissoes === 'admin' && $user?->isAdmin()) {
            return true;
        }
        return false;
    }

    public function getRules()
    {
        $rules = [];
        if ($this->isReadonly()) {
            $rules[$this->getRuleName()][] = 'not_present';
        } elseif ($this->obrigatorio) {
            $rules[$this->getRuleName()][] = 'required';
        }
        return $rules;
    }

    public function getOrderSql($direction)
    {
        if (Str::startsWith($this->tipo, 'func_')) {
            return ''; // func is not a real column
        }
        return $this->tipo.' '.$direction;
    }
}
