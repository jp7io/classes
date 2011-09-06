<?php

class Jp7_Model_SettingsTipo extends Jp7_Model_TipoAbstract {
	public $isSubTipo = true;
	
	public $attributes = array(
		'id_tipo' => 'Settings',
		'nome' => 'Configura��es',
		'campos' => 'tit_1{,}Metatags{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}varchar_key{,}Title{,}L�mite m�ximo de 50 caracteres{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}title{;}varchar_1{,}Description{,}L�mite m�ximo de 150 caracteres{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}description{;}varchar_2{,}Keywords{,}L�mite m�ximo de 80 caracteres{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}keywords{;}char_1{,}Sobrescrever Keywords{,}Se marcado, n�o manter� keywords padr�o.{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}overwrite_keywords{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
		'template' => '',
		'editpage' => '',
		'class' => '',
		'class_tipo' => '',
		'model_id_tipo' => 0,
		'tabela' => '',
		'unico' => 'S'
	);
	
}