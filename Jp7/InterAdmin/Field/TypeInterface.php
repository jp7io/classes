<?php

namespace Jp7\InterAdmin\Field;

/** An InterAdmin type as the field layer reads it, the ORM's or the host app's Eloquent one. */
interface TypeInterface
{
    /** @return int|string Its type_id, the primary key of both object models' Type. */
    public function getKey();

    /** @return string */
    public function getName();

    /** @return array<string, array<string, mixed>> Keyed by column, a select_'s `name` resolved. */
    public function getFields();

    /** @return array<int, string> */
    public function getComboFieldNames();

    /** @return array<string, mixed> */
    public function getRelationshipData(string $relationship);

    /** Its records as the ORM's query hands them out: id, type_id and id_slug kept on any SELECT,
     * and this type's ORDER BY after the caller's.
     * @return mixed A builder of either object model. */
    public function selectableRecords();

    /** @param array<string, mixed> $attributes A selectableRecords() row, as getAttributes() had it.
     * @return RecordInterface */
    public function recordFromAttributes(array $attributes);
}
