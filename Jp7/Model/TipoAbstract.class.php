<?php

class Jp7_Model_TipoAbstract extends InterAdminTipo {
	public $isSubTipo = false;
	public $hasOwnPage = true;
	
	/**
	 * $id_tipo n�o � inteiro
	 * @return 
	 */
	public function __construct() {
		
	}
	
	public function getFieldsValues($fields, $forceAsString = false, $fieldsAlias = false) {
		if (is_string($fields)) {
			return $this->attributes[$fields]; 
		} elseif (is_array($fields)) {
			return (object) array_intersect_key($this->attributes, array_flip($fields));
		}
	}
	
	protected function _findChildByModel($model_id_tipo) {
		$child = InterAdminTipo::findFirstTipoByModel($model_id_tipo, array(
			'where' => array("admin <> ''")
		));
		if (!$child) {
			// Tenta criar o tipo filho caso ele n�o exista
			$sistemaTipo = InterAdminTipo::findFirstTipo(array(
				'where' => array(
					"nome = 'Sistema'",
					"admin <> ''"
				)
			));
			if ($sistemaTipo) {
				$columns = $sistemaTipo->getDb()->MetaColumns($sistemaTipo->getTableName());
				if ($columns['MODEL_ID_TIPO']->type == 'varchar') {
					$classesTipo = $sistemaTipo->getFirstChildByNome('Classes');
					if ($classesTipo) {
						$child = new InterAdminTipo();
						$child->parent_id_tipo = $classesTipo->id_tipo;
						$child->model_id_tipo = $model_id_tipo;
						$child->nome = 'Modelo - ' . $model_id_tipo;
						$child->mostrar = 'S';
						$child->admin = 'S';
						$child->save();
						return $child;
					}
				}
			}
			//throw new Exception('Could not find a Tipo using the model "' . $model_id_tipo . '". You need to create one in Sistema/Classes.');
		} else {
			return $child;
		}
	}
	
	/**
	 * Trigger executado ap�s inserir um tipo com esse modelo.
	 * 
	 * @param InterAdminTipo $tipo
	 * @return void
	 */
	public function createChildren(InterAdminTipo $tipo) {
		
	}
	/**
	 * Helper for creating children Tipos for Boxes, Settings and Introduction.
	 * 
	 * @param InterAdminTipo $tipo
	 * @return void
	 */
	public function createBoxesSettingsAndIntroduction(InterAdminTipo $tipo) {
		if (!$tipo->getFirstChildByModel('Introduction')) {
			$introduction = $tipo->createChild('Introduction');
			$introduction->nome = 'Introdu��o';
			$introduction->ordem = -30;
	        $introduction->save();
		}
		if (!$tipo->getFirstChildByModel('Images')) {
			$images = $tipo->createChild('Images');
			$images->nome = 'Images';
			$images->ordem = -25;
	        $images->save();
		}
		if (!$tipo->getFirstChildByModel('Boxes')) {
			$boxes = $tipo->createChild('Boxes');
			$boxes->nome = 'Boxes';
			$boxes->ordem = -20;
	        $boxes->save();
		}
		if (!$tipo->getFirstChildByModel('Settings')) {
			$settings = $tipo->createChild('Settings');
			$settings->nome = 'Configura��es';
			$settings->ordem = -10;
	        $settings->save();
		}
	}
	/**
	 * Returns the fields when editting the boxes.
	 * 
	 * @param Jp7_Box_BoxAbstract $box
	 * @return string	HTML
	 */
	public function getEditorFields(Jp7_Box_BoxAbstract $box) {
		// do nothing
	}
	/**
	 * Receives the params from the boxes and prepare the necessary data.
	 * 
	 * @param Jp7_Box_BoxAbstract $box
	 * @return void
	 */
	public function prepareData(Jp7_Box_BoxAbstract $box) {
		// do nothing
	}
	
	protected function _getEditorImageFields($box) {
		ob_start();
		?>
		<div class="group">
			<div class="group-label">Imagens</div>
			<div class="group-fields">
				<div class="field">
					<label>Dimens�es:</label>
					<?php echo $box->numericField('imgWidth', 'Largura', '80'); ?> x
					<?php echo $box->numericField('imgHeight', 'Altura', '60'); ?> px
				</div>
				<div class="field">
					<label title="Se estiver marcado ir� recortar a imagem nas dimens�es exatas que foram informadas.">Recortar:</label>
					<?php echo $box->checkbox('imgCrop', true); ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
	
	protected function _prepareImageData($box) {
		$imgHeight = $box->params->imgHeight ? $box->params->imgHeight : 60;
		$imgWidth = $box->params->imgWidth ? $box->params->imgWidth : 80;
		
		$box->view->imgSize = $imgWidth . 'x' . $imgHeight;
		$box->view->imgCrop = isset($box->params->imgCrop) ? $box->params->imgCrop : true;
		
		$box->view->headStyle()->appendStyle('
.content-' . toId($this->id_tipo) . ' .img-wrapper {
	height: ' . $imgHeight . 'px;
	width: ' . $imgWidth . 'px;
	line-height: ' . $imgHeight . 'px;
}
.content-' . toId($this->id_tipo) . ' .img-wrapper img {
	max-height: ' . $imgHeight . 'px;
	max-width: ' . $imgWidth . 'px;
}
');
	}
}