<?php

declare(strict_types=1);

namespace Jp7\Interadmin\Field;

/**
 * The formats a varchar field can be declared as, in the type editor's "Xtra" column.
 *
 * One vocabulary in one place: it used to be ten `isX()` predicates, an elseif chain deciding
 * the rule and a second one deciding the markup, all keyed on the same bare strings.
 */
enum VarcharXtra: string
{
    case Id = 'id';
    case IdEmail = 'id_email';
    case Email = 'email';
    case Num = 'num';
    case Cep = 'cep';
    case Cpf = 'cpf';
    case Cnpj = 'cnpj';
    case Telefone = 'telefone';
    case LatLong = 'll';
    case Url = 'url';
    case Color = 'cor';

    /**
     * The validation rule this format implies, run by RecordController on every save.
     *
     * The last four were added on 2026-08-02 and were each measured against ci's stored values
     * first, because a rule added here can make an EXISTING record unsaveable -- the form posts
     * every field, so an untouched one is validated too. `telefone` and `ll` reject nothing real;
     * `cor` rejects 52 rows of one repurposed column, which is the deliberate cost. Empty values
     * are skipped by Laravel for all of them, so `required` stays the only thing that makes a
     * field mandatory. Full sweep: docs/frontend.md, "the four xtras that had no server rule".
     */
    public function rule(): ?string
    {
        return match ($this) {
            self::Email, self::IdEmail => 'email',
            self::Num => 'pseudonumeric',
            self::Cep => 'cep',
            self::Cpf => 'cpf',
            self::Cnpj => 'cnpj',
            self::Telefone => 'telefone',
            self::LatLong => 'll',
            self::Color => 'cor',
            // Laravel's own rule. Former's LiveValidation knows this name, so it also renders
            // type="url" -- the only one here that changes the markup by itself.
            self::Url => 'url',
            self::Id => null,
        };
    }

    /** Whether two records of the type may not share a value. */
    public function isUnique(): bool
    {
        return match ($this) {
            self::Id, self::IdEmail, self::Cpf => true,
            default => false,
        };
    }

    /** The input type to render, where it is not the default text. */
    public function inputType(): ?string
    {
        return match ($this) {
            self::Email, self::IdEmail => 'email',
            self::Telefone => 'tel',
            default => null,
        };
    }

    /**
     * Numbers are accepted in Brazilian format (1,99 rather than 1.99), which Former's own
     * numeric handling would reject. The `-` is escaped because the pattern attribute compiles
     * with the `v` flag, which rejects a bare `-` inside a character class (see FloatField).
     */
    public function pattern(): ?string
    {
        return $this === self::Num ? '[+\-]?[0-9]+([0-9,.]*[0-9]+)?' : null;
    }
}
