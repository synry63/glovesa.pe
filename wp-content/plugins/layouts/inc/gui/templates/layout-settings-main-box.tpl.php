<div class="wrap">

    <h1><i class="icon-layouts-logo ont-icon-24 ont-color-orange css-layouts-logo"></i><?php _e( 'Layouts Settings', 'ddl-layouts' ) ?></h1>

    <?php
    /*
     * Tab code is done by hand because WordPress tab classes and functions are marked as private
     */
    ?>
    <!-- tabs -->
    <div class="wp-filter wpv-settings-filter">
        <ul class="filter-links wpv-settings-filter-links">
            <li class="wpv-settings-tab-features">
                <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'dd_layout_settings', 'tab' => 'features' ), admin_url( 'admin.php' ) ) ); ?>" class="     <?php echo $tab == 'features' ? 'current' : '' ?>">
                    <?php _e( 'Features', 'ddl-layouts' ); ?>
                </a>
            </li>
            <?php /*
                      <li class="wpv-settings-tab-compatibility">
                      <a href="<?php echo esc_url( add_query_arg( array( 'page'	=> 'dd_layout_settings', 'tab' => 'compatibility' ), admin_url( 'admin.php' ) ) ); ?>" class="<?php echo $tab == 'compatibility' ? 'current' : '' ?>">
                      <?php _e( 'Compatibility', 'ddl-layouts' ); ?>
                      </a>
                      </li>
                      <li class="wpv-settings-tab-development">
                      <a href="<?php echo esc_url( add_query_arg( array( 'page'	=> 'dd_layout_settings', 'tab' => 'development' ), admin_url( 'admin.php' ) ) ); ?>" class="  <?php echo $tab == 'development' ? 'current' : '' ?>">
                      <?php _e( 'Development', 'ddl-layouts' ); ?>
                      </a>
                      </li>
                     */ ?>
        </ul>
    </div>
    <!-- /tabs -->

    <div class="wpv-settings-tab-content">
        <?php do_action( "ddl_action_layouts_settings_{$tab}_section", $this ); ?>
    </div>

</div>