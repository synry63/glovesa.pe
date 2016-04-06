<?php if (!defined('ABSPATH')) die('Security check'); ?>
<table class="access-form-texts">
    <tbody>
        <div class="cred-notification cred-error">
    <div class="cred-error">
        <?php
        if ($is_access_active) {
            ?>
            <p>
                <i class="icon-warning-sign"></i> <?php printf(__('To control who can see and use this form, go to %s.', 'wp-cred'), '<a href="' . admin_url() . '?page=types_access">Access settings</a>'); ?>
            </p>    
            <?php
        } else {
            ?>
            <p>
                <i class="icon-warning-sign"></i> <?php printf(__('This Form will be accessible to everyone, including guest (not logged in). They will be able to submit/edit content using this form.<br>To control who can use the form, please install %s.', 'wp-cred'), '<a target="_blank" href="https://wp-types.com/home/types-access/">Access plugin</a>'); ?>
            </p>
            <?php
        }
        ?>
    </div>
        </div>
</tbody>
</table>