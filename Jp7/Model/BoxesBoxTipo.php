<?php

class Jp7_Model_BoxesBoxTipo extends Jp7_Model_TipoAbstract
{
    public $isSubTipo = true;

    public $attributes = array(
        'id_tipo' => 'BoxesBox',
        'nome' => 'Boxes - Box',
        'campos' => 'varchar_key{,}ID do Box{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}id_box{;}text_1{,}Par�metros{,}{,}10{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}params{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}',
        'children' => '',
        'arquivos_ajuda' => '',
        'arquivos' => '',
        'template' => '',
        'editpage' => '',
        'class' => '',
        'class_tipo' => '',
        'model_id_tipo' => 0,
        'tabela' => 'boxes',
    );
}
