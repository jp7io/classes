<?php

namespace Jp7\Interadmin\Field;

use HtmlObject\Element;
use Jp7\Interadmin\Type;

///////// Hierarchy ////////////////
// Tag          <td>{!! $Html !!}</td>
// -> Html      <span>{{ $Text }}</span>
//    -> Text   Hi
// --------------------------------
// <td><span>Hi</span></td>
//
interface FieldInterface
{

    public function setRecord($record);

    public function setType(Type $type);

    /**
     * Which repeat of a multi-value field this is. Beside setRecord()/setType() because the
     * Factory's callers set all three, and the return type they get is this interface.
     */
    public function setIndex($index);

    /**
     * The WHERE fragment this field's filter contributes, or '' -- the SQL counterpart of
     * getFilterTag(), and read straight off the Factory's return value by Export.
     *
     * @return string
     */
    public function getFilterSql();

    /**
     * Returns object for <th> tag
     *
     * @return Element
     */
    public function getHeaderTag();

    /**
     * Returns object for <td> tag
     *
     * @return Element
     */
    public function getCellTag();

    /**
     * Return inner HTML for <th> tag
     *
     * @return Element|string
     */
    public function getHeaderHtml();

    /**
     * Return inner HTML for <td> tag
     *
     * @return Element|string
     */
    public function getCellHtml();

    /**
     * Return inner text for header
     *
     * @return string
     */
    public function getLabel();

    /**
     * Return inner text for cell
     *
     * @return string
     */
    public function getText();

    /**
     * Return object for <div class="form-group">...</div>
     *
     * @return Element|string
     */
    public function getEditTag();

    /**
     * Return object for filter
     *
     * @return Element|string
     */
    public function getFilterTag();

    /**
     * @return bool
     */
    public function hasMassEdit();

    /**
     * Return input for mass edit
     *
     * @return Element|string
     */
    public function getMassEditTag();

    /**
     * @return array
     */
    public function getRules();
}
