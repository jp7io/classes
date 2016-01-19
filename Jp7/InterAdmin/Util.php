<?php

class Jp7_InterAdmin_Util
{
    protected static $_default_vars = ['id_slug', 'parent_id', 'date_publish', 'date_insert', 'date_expire', 'date_modify', 'log', 'publish', 'deleted'];

    /**
     * Exports records and their children.
     *
     * @param InterAdminTipo $tipoObj InterAdminTipo where the records are.
     * @param array          $ids     Array de IDs.
     *
     * @return InterAdmin[]
     */
    public static function export(InterAdminTipo $tipoObj, array $ids, $use_id_string = false)
    {
        $options = [
            'fields' => array_merge(['*'], self::$_default_vars),
            'class' => 'InterAdmin',
            'fields_alias' => false,
        ];

        $optionsRegistros = $options;
        if ($use_id_string) {
            $optionsRegistros = self::_prepareOptionsForIdString($optionsRegistros, $tipoObj);
        }
        $exports = $tipoObj->find($optionsRegistros + [
            'where' => 'id IN('.implode(',', $ids).')',
        ]);

        $tiposChildren = $tipoObj->getInterAdminsChildren();
        foreach ($exports as $export) {
            self::_exportChildren($export, $tiposChildren, $use_id_string, $options);
        }

        return $exports;
    }

    protected static function _exportChildren($export, $tiposChildren, $use_id_string, $options)
    {
        $export->_children = [];
        foreach ($tiposChildren as $tipoChildrenArr) {
            $optionsChildren = $options;
            $optionsChildren['where'][] = "deleted = ''";

            $tipoChildren = $export->getChildrenTipo($tipoChildrenArr['id_tipo']);
            if ($use_id_string) {
                $optionsChildren = self::_prepareOptionsForIdString($optionsChildren, $tipoChildren);
            }

            //$optionsChildren['fields_alias'] = true;
            $children = $tipoChildren->find($optionsChildren);
            $tiposGrandChildren = $tipoChildren->getInterAdminsChildren();
            foreach ($children as $child) {
                self::_exportChildren($child, $tiposGrandChildren, $use_id_string, $options);
                $child->setParent(null);
            }
            $export->_children[$tipoChildren->id_tipo] = $children;
        }
        $export->setTipo(null);
    }

    protected static function _prepareOptionsForIdString($options, $tipo)
    {
        $campos = $tipo->getCampos();
        foreach ($campos as $campo) {
            $isSpecialRegistro = strpos($campo['tipo'], 'special_') === 0 && $campo['xtra'] == 'registros' && $tipo->getCampoTipo($campo) instanceof InterAdminTipo;
            $isSelectRegistro = strpos($campo['tipo'], 'select_') === 0 && strpos($campo['tipo'], 'select_multi_') !== 0 && !in_array($campo['xtra'], InterAdminField::getSelectTipoXtras());
            if ($isSpecialRegistro || $isSelectRegistro) {
                $options['fields'][$campo['tipo']] = ['id_string'];
            }
        }

        return $options;
    }

    protected static function _importAttributeFromIdString($record, $bind_children = false)
    {
        foreach ($record->attributes as $attributeName => $attribute) {
            if ($attribute instanceof InterAdmin && $attribute->id_string) {
                $attributeTipo = InterAdminTipo::getInstance($attribute->id_tipo);
                //$attribute->setTipo($attributeTipo);
                if ($attributeTipo) {
                    $options = [];
                    if ($bind_children) {
                        $options['order'] = 'parent_id = '.$record->parent_id.' DESC, ';
                    }
                    $options['order'] .= 'deleted = \'\' DESC';

                    $record->$attributeName = $attributeTipo->findByIdString($attribute->id_string, $options);
                }
            }
        }
    }

    /**
     * Imports records and their children with a new ID.
     *
     * @param array          $records
     * @param InterAdminTipo $tipoObj
     * @param InterAdmin     $parent
     * @param bool           $import_children defaults to TRUE
     * @param bool           $use_id_string   defaults to FALSE
     * @param bool           $bind_children   Children 1 has a relationship with Children 2, when copying, this relationship needs to be recreated
     */
    public static function import(array $records, InterAdminTipo $tipoObj, InterAdmin $parent = null, $import_children = true, $use_id_string = false, $bind_children = false)
    {
        $returnIds = [];
        foreach ($records as $record) {
            $returnId = ['id' => $record->id];
            unset($record->id);
            unset($record->id_slug);

            $record->setParent($parent);
            $record->setTipo($tipoObj);

            if ($use_id_string) {
                self::_importAttributeFromIdString($record);
            }

            $record->save();
            $returnId['new_id'] = $record->id;

            if ($import_children) {
                self::_importChildren($record, $use_id_string, $bind_children);
            }
            $returnIds[] = $returnId;
        }

        return $returnIds;
    }

    public static function _importChildren($record, $use_id_string, $bind_children)
    {
        foreach ($record->_children as $child_id_tipo => $tipo_children) {
            $childTipo = InterAdminTipo::getInstance($child_id_tipo);
            $childTipo->setParent($record);

            foreach ($tipo_children as $child) {
                unset($child->id);
                unset($child->id_slug);

                $child->setParent($record);
                $child->setTipo($childTipo);

                if ($use_id_string || $bind_children) {
                    self::_importAttributeFromIdString($child, $bind_children);
                }

                $child->save();
                self::_importChildren($child, $use_id_string, $bind_children);
            }
        }
    }

    public static function copy(InterAdminTipo $tipoObj, array $ids, InterAdminTipo $tipoDestino, InterAdmin $parent = null)
    {
        global $use_id_string, $bind_children; // FIXME usado no intermail
        global $s_user;

        $use_id_string = false;
        $bind_children = false;

        if ($tipoDestino->getInterAdminsTableName() != $tipoObj->getInterAdminsTableName()) {
            throw new Exception('Não é possível copiar para tipos com tabela customizada.');
        }

        $beforCopyEvent = InterAdmin_Event_BeforeCopy::getInstance();
        $beforCopyEvent->setIdTipo($tipoObj->id_tipo);
        $beforCopyEvent->notify();

        $registros = self::export($tipoObj, $ids, $use_id_string);

        foreach ($registros as $registro) {
            if ($tipoObj->id_tipo == $tipoDestino->id_tipo) {
                $registro->varchar_key = 'Cópia de '.$registro->varchar_key;
            }
            $registro->publish = '';
        }

        $oldLogUser = InterAdmin::setLogUser($s_user['login'].' - combo copy');
        $returnIds = self::import($registros, $tipoDestino, $parent, true, $use_id_string, $bind_children);
        InterAdmin::setLogUser($oldLogUser);

        if (InterAdmin_Event_AfterCopy::getInstance()->hasObservers()) {
            foreach ($returnIds as $returnId) {
                $afterCopyEvent = InterAdmin_Event_AfterCopy::getInstance();
                $afterCopyEvent->setIdTipo($tipoDestino->id_tipo);
                $afterCopyEvent->setId($returnId['id']);
                $afterCopyEvent->setCopyId($returnId['new_id']);
                $afterCopyEvent->notify();
            }
        }

        return $returnIds;
    }

    public static function syncTipos($model)
    {
        $inheritedTipos = InterAdminTipo::findTiposByModel($model->id_tipo, [
            'class' => 'InterAdminTipo',
        ]);
        ?>
		&bull; <?php echo $model->id_tipo;
        ?> - <?php echo $model->nome;
        ?> <br />
		<div class="indent">
			<?php foreach ($inheritedTipos as $key => $tipo) {
    ?>
				<?php
                $tipo->syncInheritance();
    $tipo->updateAttributes($tipo->attributes);
    ?>
				<?php self::syncTipos($tipo);
    ?>
			<?php 
}
        ?>
		</div>
		<?php

    }

    /**
     * Helper da função _getCampoType.
     *
     * @param InterAdminTipo $campoTipo
     * @param bool           $isTipo
     * @param bool           $isMulti
     *
     * @return string Type para o PHPDoc
     */
    protected function _getCampoTypeClass($campoTipo, $isTipo, $isMulti)
    {
        if ($isTipo) {
            $retorno = 'InterAdminTipo';
        } else {
            $retorno = $campoTipo->class ? $campoTipo->class : 'InterAdmin';
        }
        if ($isMulti && $retorno) {
            $retorno .= '[]';
        }

        return $retorno;
    }

    protected static function _getTipoPhpDocCampo($tipo, $campo)
    {
        if (strpos($campo['tipo'], 'special_') === 0 && $campo['xtra']) {
            $isMulti = in_array($campo['xtra'], InterAdminField::getSpecialMultiXtras());
            $isTipo = in_array($campo['xtra'], InterAdminField::getSpecialTipoXtras());

            $retorno = self::_getCampoTypeClass($tipo->getCampoTipo($campo), $isTipo, $isMulti);
        } elseif (strpos($campo['tipo'], 'select_') === 0) {
            $isMulti = (strpos($campo['tipo'], 'select_multi') === 0);
            $isTipo = in_array($campo['xtra'], InterAdminField::getSelectTipoXtras());

            $retorno = self::_getCampoTypeClass($campo['nome'], $isTipo, $isMulti);
        } elseif (strpos($campo['tipo'], 'int') === 0 || strpos($campo['tipo'], 'id') === 0) {
            $retorno = 'int';
        } elseif (strpos($campo['tipo'], 'char') === 0) {
            $retorno = 'string';
        } elseif (strpos($campo['tipo'], 'date') === 0) {
            return 'Jp7_Date';
        } else {
            $retorno = 'string';
        }

        return $retorno;
    }

    public static function gerarClasseInterAdmin(InterAdminTipo $tipo, $gerarArquivo = true, $nomeClasse = '')
    {
        global $config;
        $prefixoClasse = ucfirst($config->name_id);

        if (!$nomeClasse) {
            $nomeClasse = $tipo->class;
        }

        $phpdoc = '/**'."\r\n";
        foreach ($tipo->getCampos() as $campo) {
            $phpdoc .= ' * @property '.self::_getTipoPhpDocCampo($tipo, $campo).' $'.$campo['nome_id']."\r\n";
        }
        $phpdoc .= ' * @property Jp7_Date date_publish'."\r\n";
        $phpdoc .= ' */';

        $conteudo = <<<STR
<?php

$phpdoc
class {$nomeClasse} extends {$prefixoClasse}_InterAdmin {

}
STR;
        if ($gerarArquivo) {
            return self::salvarClasse($nomeClasse, $conteudo);
        } else {
            return $conteudo;
        }
    }

    public static function gerarClasseInterAdminTipo(InterAdminTipo $tipo, $gerarArquivo = true, $nomeClasse = '', $nomeClasseInterAdmin = '')
    {
        global $config;
        $prefixoClasse = ucfirst($config->name_id);

        if (!$nomeClasse) {
            $nomeClasse = $tipo->class_tipo;
        }
        if (!$nomeClasseInterAdmin) {
            $nomeClasseInterAdmin = $tipo->class;
        }
        if (!$nomeClasseInterAdmin) {
            $constname = InterAdminTipo::getDefaultClass().'::DEFAULT_NAMESPACE';
            if (defined($constname)) {
                $nomeClasseInterAdmin = constant($constname).'InterAdmin';
            } else {
                $nomeClasseInterAdmin = 'InterAdmin';
            }
        }
        $phpdoc = '/**'."\r\n";
        $phpdoc .= ' * @method '.$nomeClasseInterAdmin.'[] find'."\r\n";
        $phpdoc .= ' * @method '.$nomeClasseInterAdmin.' findFirst'."\r\n";
        $phpdoc .= ' * @method '.$nomeClasseInterAdmin.' findById'."\r\n";
        $phpdoc .= ' */';

        $conteudo = <<<STR
<?php

$phpdoc
class {$nomeClasse} extends {$prefixoClasse}_InterAdminTipo {
	const ID_TIPO = {$tipo->id_tipo};
}
STR;
        if ($gerarArquivo) {
            return self::salvarClasse($nomeClasse, $conteudo);
        } else {
            return $conteudo;
        }
    }
    /**
     * Salva o conteudo da classe em arquivo
     * return array.
     */
    public static function salvarClasse($nomeClasse, $conteudo)
    {
        global $c_interadminConfigPath;

        $arquivo = dirname($c_interadminConfigPath).'/classes/'.str_replace('_', '/', $nomeClasse).'.php';
        if (!is_file($arquivo)) {
            @mkdir(dirname($arquivo), 0777, true);

            $retorno = file_put_contents($arquivo, $conteudo);
            @chmod($arquivo, 0777);
            if ($retorno === false) {
                $avisos['erro'][] = 'Não foi possível gravar arquivo: "'.$arquivo.'". Verifique permissões no diretório.';
            } else {
                $avisos['sucesso'][] = 'Arquivo "'.$arquivo.'" gerado.';
            }
        } else {
            $avisos['erro'][] = 'Arquivo "'.$arquivo.'" já existe.';
        }

        return $avisos;
    }
}
