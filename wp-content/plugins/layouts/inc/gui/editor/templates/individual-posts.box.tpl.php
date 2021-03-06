<?php
global $wpddlayout;

if( $wpddlayout->post_types_manager->no_templates_at_all() === false ): ?>

<div class="js-change-wrap-box">
    <h2><span  class="change-layout-use-section-title js-collapse-group-individual"><?php _e('Individual pages:', 'ddl-layouts'); ?></span>
    <i class="fa fa-caret-up js-collapse-group-individual change-layout-use-section-title-icon-collapse"></i>
    </h2>

<div class="individual-pages-wrap">
    <div class="js-individual-pages-assigned individual-pages-assigned">
        <?php echo $this->print_single_posts_assigned_section( $current );?>
    </div>

    <?php $unique_id = uniqid(); ?>
    <div class="js-individual-popup-tabs">

        <ul>
            <li><a href="#js-ddl-individual-most-recent-<?php echo $unique_id; ?>"><?php _e('Most Recent', 'ddl-layouts'); ?></a></li>
            <li><a href="#js-ddl-individual-view-all-<?php echo $unique_id; ?>"><?php _e('View All', 'ddl-layouts'); ?></a></li>
            <li><a href="#js-ddl-individual-search-<?php echo $unique_id; ?>"><?php _e('Search', 'ddl-layouts'); ?></a></li>
        </ul>

        <div class="ddl-popup-tab-full ddl-individual-tab" id="js-ddl-individual-most-recent-<?php echo $unique_id; ?>">
            <?php
            global $wpddlayout;
            echo $wpddlayout->individual_assignment_manager->get_posts_checkboxes('page', 12);
            ?>
        </div>
        <!-- .ddl-popup-tab -->

        <div class="ddl-popup-tab-full ddl-individual-tab" id="js-ddl-individual-view-all-<?php echo $unique_id; ?>">
            <?php echo $wpddlayout->individual_assignment_manager->get_posts_checkboxes('page', -1, '', false); ?>
        </div>
        <!-- .ddl-popup-tab -->

        <div class="ddl-popup-tab-full ddl-individual-tab" id="js-ddl-individual-search-<?php echo $unique_id; ?>">
            <input class="js-individual-quick-search ddl-individual-quick-search" type="search"
                   id="ddl-individual-search" value="" title="Search" autocomplete="off"
                   placeholder="<?php _e('Search', 'ddl-layouts'); ?>" />

            <div id="ddl-individual-search-results-<?php echo $unique_id; ?>"></div>
        </div>
        <!-- .ddl-popup-tab -->

    </div>
    <!-- .js-individual-popup-tabs -->
    <div class="ddl-single-assignments-box-controls">

    <ul class="ddl-single-assignments-posts-who-controls">
        <li><input type="radio" id="ddl-individual-post-type-page" name="ddl-individual-post-type"
                   value="page" checked/><?php _e('Show only pages', 'ddl-layouts'); ?></li>
        <li><input type="radio" id="ddl-individual-post-type-any" name="ddl-individual-post-type"
                   value="any"/><?php _e('Show all content types', 'ddl-layouts'); ?></li>
    </ul>
        <ul class="ddl-single-assignments-box-lang-controls">
            <li>
                <?php do_action('ddl-add-wpml-custom-switcher');?>
            </li>
        </ul>
    </div>
    <div style="text-align: right;" class="js-individual-posts-update-wrap">
        <button data-group="<?php echo WPDD_Layouts_IndividualAssignmentManager::INDIVIDUAL_POST_ASSIGN_CHECKBOXES_NAME; ?>" class="button-secondary js-connect-to-layout js-buttons-change-update"><?php _e('Update', 'ddl-layouts'); ?></button>
    </div>

    <?php wp_nonce_field('wp_nonce_individual-pages-assigned', 'wp_nonce_individual-pages-assigned'); ?>
</div>

</div>
<?php endif;?>