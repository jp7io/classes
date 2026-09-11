<?php

namespace Jp7\InterAdmin\Field;

/** A record a select field lists, the ORM's or the host app's Eloquent one. */
interface RecordInterface
{
    /** @return string|int The combo columns joined, the id where the type flags none. */
    public function getStringValue();

    /** @return bool */
    public function isPublished();
}
