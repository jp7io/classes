<?php

class Jp7_Model_SiteSettingsTipo extends Jp7_Model_TipoAbstract {
	public $isSubTipo = true;
	
	/**
	 * Usado pelo helper _getColorField
	 * @var array
	 */
	private static $_dados = array();
	private static $_theme_editor = false;
	
	public $attributes = array(
		'id_tipo' => 'SiteSettings',
		'nome' => 'Configura��es do Site',
		'campos' => 'tit_1{,}Cabe�alho{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}tit_1{;}varchar_key{,}T�tulo{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}header_title{;}varchar_1{,}Subt�tulo{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}header_subtitle{;}tit_3{,}Dados do Administrador{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}tit_3{;}varchar_2{,}Nome{,}Nome utilizado como remetente nos e-mails enviados pelo site.{,}{,}S{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}admin_name{;}varchar_3{,}E-mail{,}E-mail utilizado como remetente nos e-mails enviados pelo site.{,}{,}S{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}admin_email{;}tit_2{,}Template{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}tit_2{;}special_1{,}Jp7_Model_SiteSettingsTipo::getTemplateFields{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}Template{,}{,}{,}template_data{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
		'template' => '',
		'editpage' => '',
		'class' => '',
		'class_tipo' => '',
		'model_id_tipo' => 0,
		'tabela' => '',
		'unico' => 'S',
		'disparo' => 'Jp7_Model_SiteSettingsTipo::saveTemplateFields'
	);
		
	public static function getTemplateFields($campo, $value, $parte = 'edit') {
		global $c_cliente_url, $s_interadmin_cliente ;
		
		switch ($parte) {
			case 'header':
				return $campo['label'];
				break;
			case 'list':
				// Retorna alguma coisa
				return $value;
				break;
			case 'theme_editor':
				self::$_theme_editor = true;
				// sem break;
			case 'edit':
				// N�o sei porque ele coloca &quot;
				self::$_dados = unserialize(str_replace('&quot;', '"', $value));
				
				if (self::$_dados['css_template']) {
					// Convers�o tempor�ria, retirando prefixo css_, TODO retirar c�digo depois
					foreach (self::$_dados as $key => $value) {
						self::$_dados[substr($key, 4)] = $value;
						unset(self::$_dados[$key]);
					}
				}
				
				if (!self::$_theme_editor) {
					$campo['nome_id'] = 'template';
					$campo['tipo'] = 'css_' . $campo['nome_id'];
					$campo['tipo_de_campo'] = 'select';
					$campo['separador'] = '';
					$campo['value'] = self::$_dados[$campo['nome_id']];
					$campo['opcoes'] = array();
					
					foreach (glob(ROOT_PATH . '/_default/templates/*', GLOB_ONLYDIR) as $templateDir) {
						$relativeDir = str_replace(ROOT_PATH, '', $templateDir);
						$campo['opcoes'][$relativeDir] = basename($relativeDir);
					}
					$field = new InterAdminField($campo);
					echo $field->getHtml();
					
					?>
					<tr>
						<th title="css_template (template)"></th>
						<td colspan="3">
							<input type="button" value="Abrir Editor de Cores" 
							onclick="window.open('<?php echo $c_cliente_url; ?>interadmin/site/<?php echo $s_interadmin_cliente; ?>/theme_editor.php')" />
						</td>
					</tr>
					<tr><td height="10" style="padding:0px" colspan="4"></td></tr>
					<?php
					
				} else {
					self::_getTit('Cores de Fundo');
					self::_getColorField('body_background', 'Corpo da P�gina');
					self::_getColorField('master_background', 'Wrapper', true);
									
					self::_getTit('Cores do Cabe�alho');
					self::_getColorField('header_background', 'Cor de Fundo');
					self::_getColorField('header_title_color', 'T�tulo');
					self::_getColorField('header_subtitle_color', 'Subt�tulo', true);
					
					self::_breakTable();
					
					self::_getTit('Cores do Menu');
					self::_getColorField('menu_background', 'Fundo');
					self::_getColorField('menu_color', 'Texto');
					self::_getColorField('menu_active_background', 'Fundo Ativo');
					self::_getColorField('menu_active_color', 'Texto Ativo');
					self::_getColorField('menu_hover_background', 'Fundo Hover');
					self::_getColorField('menu_hover_color', 'Texto Hover', true);
					
					self::_breakTable();
					
					self::_getTit('Cores do Breadcrumb');
					self::_getColorField('breadcrumb_background', 'Cor de Fundo');
					self::_getColorField('breadcrumb_color', 'Texto', true );
					
					self::_getTit('Cores do Slideshow');
					self::_getColorField('slideshow_title_color', 'T�tulo');
					self::_getColorField('slideshow_text_color', 'Subt�tulo');
					self::_getColorField('slideshow_a_color', 'Link', true);				
					
					self::_breakTable();
					
					self::_getTit('Cores do Conte�do');
					self::_getColorField('content_background', 'Cor de Fundo');
					self::_getColorField('content_title_color', 'T�tulo');
					self::_getColorField('content_subtitle_color', 'Subt�tulo');
					self::_getColorField('content_color', 'Texto');
					self::_getColorField('content_a_color', 'Links');
					self::_getColorField('content_border_bottom', 'Separador', true);
					
					self::_breakTable();
					
					self::_getTit('Cores dos Boxes');
					self::_getColorField('box_background', 'Cor de Fundo');
					self::_getColorField('box_title_color', 'T�tulo');
					self::_getColorField('box_subtitle_color', 'Subt�tulo');
					self::_getColorField('box_color', 'Texto');
					self::_getColorField('box_header_background', 'Cabe�alho');
					self::_getColorField('box_header_color', 'Texto do Cabe�alho');
					self::_getColorField('box_footer_background', 'Rodap�');
					self::_getColorField('box_footer_color', 'Texto do Rodap�', true && !self::$_theme_editor);
					
					self::_breakTable();
					
					self::_getTit('Cores do Rodap�');
					self::_getColorField('footer_background', 'Cor de Fundo');
					self::_getColorField('footer_title_color', 'T�tulo');
					self::_getColorField('footer_color', 'Texto');
					self::_getColorField('disclaimer_color', 'Disclaimer', true);
					break;
				}
		}
	}
	
	protected static function _getColorField($nome_id, $nome, $separador = '') {
		$campo = array(
			'nome_id' => $nome_id,
			'tipo' => 'css_' . $nome_id,
			'tipo_de_campo' => 'varchar',
			'nome' => $nome,
			'xtra' => 'cor',
			'value' => self::$_dados[$nome_id],
			'default' => '',
			'separador' => $separador
		);
		
		$field = new InterAdminField($campo);
		echo $field->getHtml();
	}
	
	protected static function _getTit($nome) {
		$field = new InterAdminField(array(
			'tipo' => 'tit_' . toId($nome),
			'nome' => $nome
		));
		echo $field->getHtml();
	}
	
	protected static function _breakTable() {
		if (self::$_theme_editor) {
			?>
			</table>
			<table class="color-table">
			<?php
		}
	}
	
	public static function saveTemplateFields($from, $id, $id_tipo) {
		if ($from == 'edit' || $from == 'insert') {
			if ($id && $id_tipo) {
				$tipo = InterAdminTipo::getInstance($id_tipo);
				$registro = $tipo->findById($id, array(
					'fields' => array('special_1'),
					'fields_alias' => false
				));
				if ($registro) {
					$special_1 = array();
					if ($registro->special_1) {
						$special_1 = unserialize($registro->special_1);
					}
					foreach ($_POST as $key => $values) {
						if (startsWith('css_', $key) && !endsWith('_xtra', $key)) {
							$special_1[substr($key, 4)] = $values[0];
						}
					}
					$registro->updateAttributes(array(
						'special_1' => serialize($special_1)
					));
					
					self::$_dados = $special_1;
					self::_saveDynamicCss();
				}
			}
		}
	}
	
	protected static function _saveDynamicCss() {
		global $c_interadminConfigPath, $c_remote;
		$filename = $c_interadminConfigPath . 'dynamic.css';
		
		$content = '/*' . "\r\n" . 
			'N�O EDITE ESTE ARQUIVO - Arquivo � gerado dinamicamente' . "\r\n" .
			'DO NOT EDIT THIS FILE - File is dynamically generated' . "\r\n" .
			'*/' .  "\r\n" .
			
			self::_getCssBase('body', array('body_background')) .
			self::_getCssBase('master', array('master_background')) .
			
			self::_getCssBase('header', array('header_background')) .
			self::_getCssBase('header-title', array('header_title_color')) .
			self::_getCssBase('header-subtitle', array('header_subtitle_color')) .
			
			self::_getCssBase('menu', array('menu_background')) .
			self::_getCssBase('menu-a', array('menu_color')) .
			self::_getCssBase('menu-on', array('menu_active_background')) .
			self::_getCssBase('menu-a-on', array('menu_active_color')) .
			self::_getCssBase('menu-hover', array('menu_hover_background')) .
			self::_getCssBase('menu-a-hover', array('menu_hover_color')) .
			
			self::_getCssBase('breadcrumb', array('breadcrumb_background')) .
			self::_getCssBase('breadcrumb-a', array('breadcrumb_color')) .
			
			self::_getCssBase('slideshow-title', array('slideshow_title_color')) .
			self::_getCssBase('slideshow-text', array('slideshow_text_color')) .
			self::_getCssBase('slideshow-a', array('slideshow_a_color')) .
			
			self::_getCssBase('content', array('content_background')) .
			self::_getCssBase('content-title', array('content_title_color')) .
			self::_getCssBase('content-subtitle', array('content_subtitle_color')) .
			self::_getCssBase('content-text', array('content_color')) .
			self::_getCssBase('content-a', array('content_a_color')) .
			self::_getCssBase('content-border-bottom', array('content_border_bottom')) .
			
			self::_getCssBase('box-header', array('box_header_background')) .
			self::_getCssBase('box-header-a', array('box_header_color')) .
			
			self::_getCssBase('box', array('box_background')) .
			self::_getCssBase('box-title', array('box_title_color')) .
			self::_getCssBase('box-subtitle', array('box_subtitle_color')) .
			self::_getCssBase('box-text', array('box_color')) .
			
			self::_getCssBase('box-footer', array('box_footer_background')) .
			self::_getCssBase('box-footer-a', array('box_footer_color')) .
			
			self::_getCssBase('footer', array('footer_background')) .
			self::_getCssBase('footer-title', array('footer_title_color')) .
			self::_getCssBase('footer-text', array('footer_color')) .
			
			self::_getCssBase('disclaimer', array('disclaimer_color')) .
			'';
		
		file_put_contents($filename, $content);
		
		if ($c_remote) {
			interadmin_update_remote_files(array('dynamic.css'));		
		}
	}
	
	protected static function _getCssBase($base_id, $properties) {
		$css = '@base(' . $base_id . ') {' . "\r\n";
		foreach ($properties as $property) {
			if (endsWith('_border_bottom', $property)) {
				$cssProperty = 'border-bottom-color';
			} elseif (endsWith('_background', $property)) {
				$cssProperty = 'background';
			} elseif (endsWith('_color', $property)) {
				$cssProperty = 'color';
			} else {
				continue;
			}
			if ($value = self::$_dados[$property]) {
				$css .= "\t" . $cssProperty . ': ' . $value . ';' . "\r\n";
			}
		}
		$css .= '}' . "\r\n";
		return $css;
	}
	
}