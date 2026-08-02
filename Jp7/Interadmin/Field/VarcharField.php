<?php

namespace Jp7\Interadmin\Field;

use HtmlObject\Element;
use DB;

class VarcharField extends ColumnField
{
    protected $id = 'varchar';
    /*
    [0] = "Normal";
    ['id'] = "ID";
    ['id_email'] = "ID E-Mail";
    ['email'] = "E-Mail";
    ['num'] = "Número";
    ['cep'] = "CEP";
    ['cpf'] = "CPF";
    ['cnpj'] = "CNPJ";
    ['telefone'] = "Telefone";
    ['ll']="Latitude e Longitude";
    ['url'] = "URL";
    ['cor']="Cor Hexadecimal";
    */
    public function getRules()
    {
        $rules = parent::getRules();
        $name = $this->getRuleName();

        if ($this->isUnique()) {
            $rules[$name][] = $this->record->getUniqueRule($this->tipo);
        }
        if ($this->isEmail()) {
            $rules[$name][] = 'email';
        } elseif ($this->isNumeric()) {
            $rules[$name][] = 'pseudonumeric';
        } elseif ($this->isCep()) {
            $rules[$name][] = 'cep';
        } elseif ($this->isCpf()) {
            $rules[$name][] = 'cpf';
        } elseif ($this->isCnpj()) {
            $rules[$name][] = 'cnpj';
        // The four below had no server rule until 2026-08-02: masked in the browser, accepted
        // unconditionally here. Each was measured against ci's stored values first, because a rule
        // added to this method can make an EXISTING record unsaveable -- the form posts every
        // field, so an untouched one is validated too. `telefone` and `ll` reject nothing real
        // (`telefone`'s 48 rejects are all SQL-injection probes from a public form); `cor` rejects
        // 52 rows of one repurposed column, which is the deliberate cost. Empty values are skipped
        // by Laravel for all of them, so `required` stays the only thing that makes a field
        // mandatory. Full sweep: docs/frontend.md, "the four xtras that had no server rule".
        } elseif ($this->isTel()) {
            $rules[$name][] = 'telefone';
        } elseif ($this->isLatLong()) {
            $rules[$name][] = 'll';
        } elseif ($this->isColor()) {
            $rules[$name][] = 'cor';
        } elseif ($this->isUrl()) {
            // Laravel's own rule. Former's LiveValidation knows this name, so it also renders
            // type="url" -- the only one of the four that changes the markup.
            $rules[$name][] = 'url';
        }
        if ($this->tamanho) {
            $rules[$name][] = 'max:'.$this->tamanho;
        }
        return $rules;
    }

    protected function isUnique()
    {
        return $this->xtra === 'id' || $this->xtra === 'id_email' || $this->xtra === 'cpf';
    }

    protected function isEmail()
    {
        return $this->xtra === 'email' || $this->xtra === 'id_email';
    }

    protected function isNumeric()
    {
        return $this->xtra === 'num';
    }

    protected function isTel()
    {
        return $this->xtra === 'telefone';
    }

    protected function isCpf()
    {
        return $this->xtra === 'cpf';
    }

    protected function isCnpj()
    {
        return $this->xtra === 'cnpj';
    }

    protected function isCep()
    {
        return $this->xtra === 'cep';
    }

    protected function isColor()
    {
        return $this->xtra === 'cor';
    }

    protected function isLatLong()
    {
        return $this->xtra === 'll';
    }

    protected function isUrl()
    {
        return $this->xtra === 'url';
    }

    protected function getFormerField()
    {
        $input = parent::getFormerField();
        if ($this->isEmail()) {
            $input->type('email');
        } elseif ($this->isNumeric()) {
            // Remove Former HTML5 Validation
            // Because we accept numbers in Brazilian format: 1,99 instead of 1.99
            // `-` is escaped because the pattern attribute is compiled with the `v` flag,
            // which rejects a bare `-` inside a character class (see FloatField).
            $input->pattern('[+\-]?[0-9]+([0-9,.]*[0-9]+)?');
        } elseif ($this->isTel()) {
            $input->type('tel');
        } elseif ($this->isColor()) {
            $input->prepend($this->getColorpickerHtml());
        }
        return $input->data_type($this->xtra ?: false);
    }

    public function hasMassEdit()
    {
        return true;
    }

    protected function getColorpickerHtml()
    {
        return '<div class="colorpicker-button"></div>';
    }
}
