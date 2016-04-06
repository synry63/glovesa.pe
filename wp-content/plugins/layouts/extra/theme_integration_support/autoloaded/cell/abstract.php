<?php

// @todo comment
abstract class WPDDL_Cell_Abstract implements WPDDL_Cell_Interface {

	protected $id;
	protected $factory;

	public function setup() {
		add_filter( 'dd_layouts_register_cell_factory', array( $this, 'cell' ) );
	}

	public function cell( $factories ) {
		$factories[$this->id] = new $this->factory();
		return $factories;
	}
}