<?php

// @todo comment
abstract class WPDDL_Cell_Abstract_Cell_Factory  extends WPDD_layout_cell_factory {

	protected $cell_class;

	protected $name = '';
	protected $description = '';
	protected $btn_text = '';
	protected $dialog_title_create;
	protected $dialog_title_edit;
	protected $allow_multiple = true;
	protected $category;
	protected $has_settings = false;

	protected $cell_image_url;
	protected $preview_image_url;

	public function build( $name, $width, $css_class_name = '', $content = null, $css_id, $tag ) {
		return new $this->cell_class( $name, $width, $css_class_name, $content, $css_id, $tag );
	}

	public function get_cell_info( $template ) {
		$this->setDialogTitleCreate();
		$this->setDialogTitleEdit();
		$this->setCategory();
		$this->setCellImageUrl();

		$template['cell-image-url']      = $this->cell_image_url;
		$template['preview-image-url']   = $this->preview_image_url;
		$template['name']                = $this->name;
		$template['description']         = $this->description;
		$template['button-text']         = $this->btn_text;
		$template['dialog-title-create'] = $this->dialog_title_create;
		$template['dialog-title-edit']   = $this->dialog_title_edit;
		$template['dialog-template']     = $this->_dialog_template();
		$template['allow-multiple']      = $this->allow_multiple;
		$template['category']            = $this->category;
		$template['has_settings']        = $this->has_settings;

		return $template;
	}

	public function get_editor_cell_template() {
		$this->setCategory();

		ob_start();
		?>
			<div class="cell-content">
			<p class="cell-name from-bot-10"><?php echo $this->category . ' - ' . $this->name; ?></p>
				<div class="cell-preview">
	                <div class="ddl-genesis-widget-header-right-preview">

					</div>
				</div>
			</div>
			</div>
		<?php
		return ob_get_clean();
	}

	private function _dialog_template() {
		/* Cell Dialog Output */
	}

	public function enqueue_editor_scripts() {
		//wp_register_script( 'wp-genesis-widget-header-right-editor', ( WPDDL_GUI_RELPATH . "editor/js/child-cell.js" ), array('jquery'), null, true );
		//wp_enqueue_script( 'wp-genesis-widget-header-right-editor' );
	}

	private function setCategory() {
		if( $this->category === null ) {
			$this->category = ( defined( 'LAYOUTS_INTEGRATION_THEME_NAME' ) )
				? LAYOUTS_INTEGRATION_THEME_NAME
				: 'Theme Integration';
		}
	}

	private function setDialogTitleEdit() {
		if( $this->dialog_title_edit === null )
			$this->dialog_title_edit = 'Edit ' . $this->name;
	}

	private function setDialogTitleCreate() {
		if( $this->dialog_title_create === null )
			$this->dialog_title_create = 'Place ' . $this->name;
	}

	protected function setCellImageUrl() {
		if( $this->cell_image_url === null )
			$this->cell_image_url = DDL_ICONS_SVG_REL_PATH . 'generic-cell.svg';
	}
}