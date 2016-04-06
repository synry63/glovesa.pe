<?php

// @todo comment
abstract class WPDDL_Cell_Abstract_Cell  extends WPDD_layout_cell {

	protected $id;
	protected $view_file;

	function __construct( $name, $width, $css_class_name = '', $content = null, $css_id, $tag ) {
		parent::__construct( $name, $width, $css_class_name, $this->id , $content, $css_id, $tag );

		$this->set_cell_type( $this->id );
	}

	protected function setViewFile() {
		return null;
	}

	function frontend_render_cell_content( $target ) {

		$this->view_file = $this->setViewFile();

		if( $this->view_file === null )
			return;

		if( file_exists( $this->view_file ) && is_readable( $this->view_file ) ) {

			ob_start();

			include( $this->view_file );

			$target->cell_content_callback(ob_get_clean(), $this);

		}
	}
}