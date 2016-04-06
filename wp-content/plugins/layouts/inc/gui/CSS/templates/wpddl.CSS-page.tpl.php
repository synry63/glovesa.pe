<div class="wrap">



    <div class="ddl-settings-wrap ddl-css-header-wrap">
        <div class="ddl-settings">
            <div class="ddl-settings-header">
                <h1>
                    <i class="icon-layouts-logo ont-icon-24 ont-color-orange css-layouts-logo"></i><?php _e('Layouts CSS', 'ddl-layouts'); ?>
                </h1>
            </div>

            <div class="ddl-settings-content">
                <p><?php _e('This is a CSS editor. You can add CSS rules here and they will be included on every page in the site\'s front-end. This is like editing the theme\'s "styles.css", just without having to edit physical files.', 'ddl-layouts'); ?></p>
            </div>
        </div>
    </div>


    <div class="ddl-settings-wrap ddl-css-edit-wrap">

        <div class="ddl-settings">

            <div class="ddl-settings-content ddl-css-edit-content">

                <div class="js-css-editor-message-container js-ddl-message-container dd-message-container"></div>

                <div class="js-code-editor code-editor layout-css-editor">
                    <div class="code-editor-toolbar js-code-editor-toolbar">
                        <ul>
                            <li></li>
                        </ul>
                    </div>
                    <!-- THERE SHOULDN'T BE ANY NEW LINE IN TEXT AREA TAG OTHERWISE CREATES A VISUAL BUG -->
                    <ul class="codemirror-bookmarks js-codemirror-bookmarks"></ul>
                    <textarea name="ddl-default-css-editor"
                              id="ddl-default-css-editor"
                              class="js-ddl-css-editor-area ddl-default-css-editor"><?php WPDDL_CSSEditor::print_layouts_css(); ?></textarea>
                    <p class="wp-caption-text alignleft"><?php _e('CTRL+Space: Display class names and IDs.', 'ddl-layouts');?></p>
                </div>

                <p class="js-need-css-help need-css-help">
                    <?php _e('Need help with CSS styling?', 'ddl-layouts'); ?>&nbsp;<a
                        href="<?php echo WPDDL_CSS_STYLING_LINK; ?>"
                        target="_blank"><?php _e('Using HTML and CSS to style layout cells', 'ddl-layouts'); ?> &raquo;</a>
                </p>

                <p class="update-button-wrap">
                    <span class="js-wpv-messages"></span>
                    <button class="js-layout-css-save button button-secondary" disabled="disabled">
                        <?php _e( 'Save', 'ddl-layouts' ); ?>
                    </button>
                </p>

            </div>

    <div class="clear"></div>
</div>