<?php

namespace Jp7\Interadmin\Field;

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
 * Injected by the xtra_disabledfields parser rather than stored in campos, and read only
 * by SelectFieldTrait::query(), which is a trait and cannot declare it.
 * @property string $where
 */
class ColumnField extends BaseField
{
    protected array $campo;

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
    public function __get(string $name): mixed
    {
        if (!isset($this->campo[$name])) {
            return null;
        }
        return $this->campo[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name)
    {
        return isset($this->campo[$name]);
    }

    /**
     * @param string $name
     * @return void
     */
    public function __unset(string $name)
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
        return $this->applyGroupSettings(parent::getEditTag());
    }

    /**
     * Everything the field's GROUP carries beyond the control. Split out of getEditTag() so a
     * field whose variants render different rows can still put each one through this contract
     * without asking for the Former field a second time -- a second getFormerField() means a
     * second options query, and a second run of Former's per-name id counter.
     */
    protected function applyGroupSettings($input)
    {
        if ($this->ajuda) {
            $input->help($this->ajuda);
        }
        $input->getLabel()->setAttribute('title', $this->getLabelTitle());
        foreach ($this->getGroupClasses() as $class) {
            $input->onGroupAddClass($class);
        }
        $this->handleReadonly($input);
        return $input;
    }

    /**
     * The classes Former's field group carries, so a row built by hand can be given the same ones
     * and be styled and scripted as the field it is. `required` is not among them: Former derives
     * that from the rules the form was opened with (see getRules()).
     *
     * @return string[]
     */
    protected function getGroupClasses(): array
    {
        $classes = [$this->id, $this->nome_id.'-group'];
        if ($this->separador) {
            $classes[] = 'has-separator';
        }
        return $classes;
    }

    /**
     * The physical column behind the label, so an editor can find the field in the type.
     */
    protected function getLabelTitle(): string
    {
        return $this->nome_id.' ('.$this->tipo.', xtra: '.$this->xtra.')';
    }

    protected function getColumn(): string
    {
        return $this->tipo;
    }

    protected function getIdentifier(): string
    {
        // A field built in code rather than parsed out of `campos` carries no alias: ci's
        // Ci\Field\Passeios\Roteiros hands SelectField only `nome` and `tipo`. The column is
        // the identifier those have, and __get() returns null rather than '' for a missing key.
        return $this->nome_id ?: $this->getColumn();
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

    public function isReadonly(): bool
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
    protected function hasPermissions(): bool
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

    public function getRules(): array
    {
        $rules = [];
        if ($this->isReadonly()) {
            $rules[$this->getRuleName()][] = 'not_present';
        } elseif ($this->obrigatorio) {
            $rules[$this->getRuleName()][] = 'required';
        }
        return $rules;
    }

    public function getOrderSql($direction): string
    {
        if (str_starts_with($this->tipo, 'func_')) {
            return ''; // func is not a real column
        }
        return $this->tipo.' '.$direction;
    }
}
