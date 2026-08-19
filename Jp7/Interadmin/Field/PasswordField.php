<?php

namespace Jp7\Interadmin\Field;

use Former;

class PasswordField extends ColumnField
{
    protected $id = 'password';

    public function getText(): string
    {
        return $this->getValue() ? '******' : '';
    }

    protected function getFormerField()
    {
        $input = Former::password($this->getFormerName())
            ->id($this->getFormerId());

        if ($this->getValue()) {
            // Disabled so it won't force the user to change the password
            $input->disabled()
                ->data_filled();
        }
        return $input;
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        // An `obrigatorio` password is required when CREATING a record only. On an existing
        // one the box means "change the password", and leaving it empty is the only way the
        // form can say "keep the current one" -- so requiring it makes every other field on
        // the record unreachable.
        //
        // Keyed on the record, not on getValue(): a record saved WITHOUT a password (ci has
        // 691 such users) used to fall through to `required` and could not be saved at all.
        // The save path drops an empty value rather than writing it.
        if ($this->record && $this->record->id) {
            // Remove required
            if (isset($rules[$this->getRuleName()])) {
                $rules[$this->getRuleName()] = array_diff($rules[$this->getRuleName()], ['required']);
            }
        }
        return $rules;
    }
}
