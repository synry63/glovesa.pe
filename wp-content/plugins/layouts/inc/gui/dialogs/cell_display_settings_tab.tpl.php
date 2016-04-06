<div class="ddl-form js-css-styling-controls css-styling-controls">
	<p>
		<label for="ddl_tag_name"><?php _e('HTML Tag:', 'ddl-layouts'); ?></label>
		<select class="js-select2 js-ddl-tag-name" id="ddl_tag_name" name="ddl_tag_name">
            <option value="article">&lt;article&gt;</option>
			<option value="aside">&lt;aside&gt;</option>
			<option value="blockquote">&lt;blockquote&gt;</option>
			<option value="button">&lt;button&gt;</option>
			<option value="div" selected>&lt;div&gt;</option>
			<option value="figure">&lt;figure&gt;</option>
			<option value="footer">&lt;footer&gt;</option>
			<option value="h1">&lt;h1&gt;</option>
			<option value="h2">&lt;h2&gt;</option>
			<option value="h3">&lt;h3&gt;</option>
			<option value="h4">&lt;h4&gt;</option>
			<option value="h5">&lt;h5&gt;</option>
			<option value="h6">&lt;h6&gt;</option>
			<option value="header">&lt;header&gt;</option>
			<option value="section">&lt;section&gt;</option>
		</select>
		<span class="desc"><?php _e('Choose the HTML tag to use when rendering this cell.','ddl-layouts') ?></span>
	</p>
	<p>
		<label for="ddl-<?php echo $dialog_type; ?>-edit-css-id"><?php _e('Tag ID:', 'ddl-layouts'); ?> <span class="opt">(<?php _e('optional', 'ddl-layouts'); ?>)</span></label>
		<input type="text" name="ddl-<?php echo $dialog_type; ?>-edit-css-id" id="ddl-<?php echo $dialog_type; ?>-edit-css-id" class="js-edit-css-id">
		<span class="desc"><?php _e('Set an ID for the cell if you want to specify a unique style for it.','ddl-layouts') ?></span>
	</p>
	<p>
		<label for="ddl-<?php echo $dialog_type; ?>-edit-class-name"><?php _e('Tag classes:', 'ddl-layouts'); ?> <span class="opt">(<?php _e('optional', 'ddl-layouts'); ?>)</span></label>
		<input type="text" name="ddl-<?php echo $dialog_type; ?>-edit-class-name" id="ddl-<?php echo $dialog_type; ?>-edit-class-name" class="js-select2-tokenizer js-edit-css-class">
		<span class="desc" style="display:none"><?php _e('Separated class names by a single space.','ddl-layouts') ?></span>
	</p>

	<div class="js-css-editor-message-container"></div>

	<div class="js-preset-layouts-rows row-not-render-message from-top-20" id="js-child-not-render-message">
		<p class="toolset-alert toolset-alert-info">
			<?php _e('You cannot style this element because it will not appear in the site\'s front-end. To style, please edit the child layout and add class, ID and styling to it.', 'ddl-layouts');?>
		</p>
	</div>
</div>

<script type="text/html" id="ddl-styles-extra-controls">
	<div class="ddl-form js-css-styling-controls css-styling-controls">
		<p>
			<label for="ddl_tag_name"><?php _e('HTML Tag:', 'ddl-layouts'); ?></label>
			<select class="js-select2 js-ddl-tag-name" id="ddl_tag_name" name="ddl_tag_name">
				<option value="article">&lt;article&gt;</option>
				<option value="aside">&lt;aside&gt;</option>
				<option value="blockquote">&lt;blockquote&gt;</option>
				<option value="button">&lt;button&gt;</option>
				<option value="div" selected>&lt;div&gt;</option>
				<option value="figure">&lt;figure&gt;</option>
				<option value="footer">&lt;footer&gt;</option>
				<option value="h1">&lt;h1&gt;</option>
				<option value="h2">&lt;h2&gt;</option>
				<option value="h3">&lt;h3&gt;</option>
				<option value="h4">&lt;h4&gt;</option>
				<option value="h5">&lt;h5&gt;</option>
				<option value="h6">&lt;h6&gt;</option>
				<option value="header">&lt;header&gt;</option>
				<option value="section">&lt;section&gt;</option>
			</select>
			<span class="desc"><?php _e('Choose the HTML tag to use when rendering this cell.','ddl-layouts') ?></span>
		</p>
		<p>
			<label for="ddl-default-edit-css-id"><?php _e('Tag ID:', 'ddl-layouts'); ?> <span class="opt">(<?php _e('optional', 'ddl-layouts'); ?>)</span></label>
			<input type="text" name="ddl-default-edit-css-id" id="ddl-default-edit-css-id" class="js-edit-css-id css-id-control" value="{{{id}}}">
			<span class="desc"><?php _e('Set an ID for the cell if you want to specify a unique style for it.','ddl-layouts') ?></span>
		</p>
		<p>
			<label for="ddl-default-edit-class-name"><?php _e('Tag classes:', 'ddl-layouts'); ?> <span class="opt">(<?php _e('optional', 'ddl-layouts'); ?>)</span></label>
			<input type="text" name="ddl-default-edit-class-name" id="ddl-default-edit-class-name" class="js-select2-tokenizer js-edit-css-class" value="{{{css}}}">
			<span class="desc" style="display:none"><?php _e('Separated class names by a single space.','ddl-layouts') ?></span>
		</p>

		<div class="js-css-editor-message-container"></div>

	</div>
</script>