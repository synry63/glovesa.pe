<?php

/* * ************************************************

  Cred fields model

  (get custom fields for post types)
 * ************************************************ */

/**
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/crud/trunk_new/embedded/models/Fields.php $
 * $LastChangedDate: 2015-03-31 15:30:58 +0200 (mar, 31 mar 2015) $
 * $LastChangedRevision: 32734 $
 * $LastChangedBy: francesco $
 *
 */
final class CRED_User_Fields_Model extends CRED_Abstract_Model implements CRED_Singleton {

    private $_custom_posts_option = '__CRED_CUSTOM_POSTS';
    private $_custom_fields_option = '__CRED_CUSTOM_FIELDS';
    private $_custom_user_fields_option = '__CRED_CUSTOM_USER_FIELDS';

    /**
     * Class constructor
     */
    public function __construct() {
        parent::__construct();
    }

    public function getCustomFieldsList($exclude_fields = array()) {
        $exclude = array('_edit_last', '_edit_lock', '_wp_old_slug', '_thumbnail_id', '_wp_page_template',
            'first_name', 'last_name', 'nickname', 'description');
        if (!empty($exclude_fields))
            $exclude = array_merge($exclude, $exclude_fields);

        $exclude = "'" . implode("','", $exclude) . "'"; //wrap in quotes
        $sql = $this->wpdb->prepare("
            SELECT DISTINCT(pm.meta_key) FROM {$this->wpdb->usermeta} as pm, {$this->wpdb->users} as p
            WHERE
                pm.user_id=p.ID
            
            AND pm.meta_key NOT IN ({$exclude})

            AND pm.meta_key NOT LIKE '%s' 

            AND pm.meta_key NOT LIKE '%s'"
                , "wpcf-%", "\_%");

        $fields = $this->wpdb->get_col($sql);

        return $fields;
    }

    public function getPostTypeCustomFields($post_type, $exclude_fields = array(), $show_private = true, $paged, $perpage = 10, $orderby = 'meta_key', $order = 'asc') {
        /*
          TODO:
          make search incremental to avoid large data issues
         */

        //Fixed https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196947000/comments
        //get custom fields not managed by Types
        //added not like wpcf-%

        $exclude = array('_edit_last', '_edit_lock', '_wp_old_slug', '_thumbnail_id', '_wp_page_template', 'first_name', 'last_name', 'nickname', 'description');
        if (!empty($exclude_fields))
            $exclude = array_merge($exclude, $exclude_fields);

        $exclude = "'" . implode("','", $exclude) . "'"; //wrap in quotes

        if ($paged < 0) {
            if ($show_private)
                $sql = $this->wpdb->prepare("
                SELECT COUNT(DISTINCT(pm.meta_key)) FROM {$this->wpdb->usermeta} as pm, {$this->wpdb->users} as p
                WHERE
                    pm.user_id=p.ID
                
                AND pm.meta_key NOT IN ({$exclude})
                AND pm.meta_key NOT LIKE '%s' 
                ", "wpcf-%");
            else
                $sql = $this->wpdb->prepare("
                SELECT COUNT(DISTINCT(pm.meta_key)) FROM {$this->wpdb->usermeta} as pm, {$this->wpdb->users} as p
                WHERE
                    pm.user_id=p.ID
                
                AND pm.meta_key NOT IN ({$exclude})

                AND pm.meta_key NOT LIKE '%s' 

                AND pm.meta_key NOT LIKE '%s'
                ", "wpcf-%", "\_%");

            return $this->wpdb->get_var($sql);
        }

        $paged = intval($paged);
        $perpage = intval($perpage);
        $paged--;
        $order = strtoupper($order);
        if (!in_array($order, array('ASC', 'DESC')))
            $order = 'ASC';
        if (!in_array($orderby, array('meta_key')))
            $orderby = 'meta_key';

        if ($show_private)
            $sql = $this->wpdb->prepare("
            SELECT DISTINCT(pm.meta_key) FROM {$this->wpdb->usermeta} as pm, {$this->wpdb->users} as p
            WHERE
                pm.user_id=p.ID
            
            AND pm.meta_key NOT IN ({$exclude})
            AND pm.meta_key NOT LIKE '%s' 
            ORDER BY pm.{$orderby} {$order}
            LIMIT " . ($paged * $perpage) . ", " . $perpage
                    , "wpcf-%");
        else
            $sql = $this->wpdb->prepare("
            SELECT DISTINCT(pm.meta_key) FROM {$this->wpdb->usermeta} as pm, {$this->wpdb->users} as p
            WHERE
                pm.user_id=p.ID
            
            AND pm.meta_key NOT IN ({$exclude})

            AND pm.meta_key NOT LIKE '%s' 

            AND pm.meta_key NOT LIKE '%s'
            ORDER BY pm.{$orderby} {$order}
            LIMIT " . ($paged * $perpage) . ", " . $perpage
                    , "wpcf-%", "\_%");

        $fields = $this->wpdb->get_col($sql);

        return $fields;
    }

    public function getCustomFields($role = "") {
        $fields = array();
        //return get_option('wpcf-usermeta', false);
        $isTypesActive = defined('WPCF_VERSION');
        if ($isTypesActive) {

            if (defined('WPCF_EMBEDDED_ABSPATH')) {
                require_once WPCF_EMBEDDED_ABSPATH . '/includes/usermeta-post.php';
                $_type_fields_groups = wpcf_admin_usermeta_get_groups_fields();
            }

            //Getting all types user meta fields
            if (isset($_type_fields_groups) && !empty($_type_fields_groups))
                foreach ($_type_fields_groups as $n => $f) {
                    if ($f['is_active'] == 1) {
                        if (
                                (
                                isset($f['_wp_types_group_showfor']) &&
                                (
                                (is_array($f['_wp_types_group_showfor']) && in_array($role, $f['_wp_types_group_showfor'])) ||
                                (is_string($f['_wp_types_group_showfor']) && 'all' == $f['_wp_types_group_showfor'])
                                )
                                ) || empty($role)) {
                            $fields = array_merge($fields, $f['fields']);
                        }
                    }
                }

            $fields = (isset($fields) && !empty($fields)) ? $fields : array();

            $_all_option_wpcf_fields = get_option('wpcf-usermeta', false);
            if (!empty($_all_option_wpcf_fields))
                foreach ($_all_option_wpcf_fields as $key => $f) {
                    if (isset($f['data']['controlled']) && $f['data']['controlled'] == 1) {
                        $newf = array();
                        //use slug because of key sensisitive
                        $newf[$f['slug']] = $f;
                        $fields = array_merge($fields, $newf);
                    }
                }

            $plugin = 'types';
            if (!empty($fields))
                foreach ($fields as $key => $field) {

                    $fields[$key]['post_labels'] = "$key";
                    $fields[$key]['post_type'] = 'user';
                    $fields[$key]['plugin_type'] = $plugin;
                    $fields[$key]['plugin_type_prefix'] = 'wpcf-';
                }
        }

        $fields = (isset($fields) && !empty($fields)) ? $fields : array();

        $custom_items = $this->getCustomFieldsList();

        $_all_option_custom_cred_fields = get_option($this->_custom_user_fields_option, false);

        if (isset($_all_option_custom_cred_fields) && isset($_all_option_custom_cred_fields['cred-user-form'])) {
            foreach ($_all_option_custom_cred_fields['cred-user-form'] as $key => $f) {
                if (!in_array($key, $custom_items))
                    continue;

                if (isset($f['cred_custom']) && $f['cred_custom'] == 1) {
                    $newf = array();
                    //use slug because of key sensisitive
                    $newf[$f['slug']] = $f;
                    $fields = array_merge($fields, $newf);
                }
            }
        }

        $plugin = 'cred';
        if (!empty($fields))
            foreach ($fields as $key => $field) {
                if (isset($fields[$key]['plugin_type']) &&
                        $fields[$key]['plugin_type'] == 'types') {
                    continue;
                }
                $fields[$key]['post_labels'] = "$key";
                $fields[$key]['post_type'] = 'user';
                $fields[$key]['plugin_type'] = $plugin;
                $fields[$key]['plugin_type_prefix'] = 'cred-';
                $fields[$key]['meta_key'] = $fields[$key]['plugin_type_prefix'] . $key;
            }

        //#########################################################################################
        $sql = 'SELECT distinct(meta_key) as meta_key FROM ' . $this->wpdb->usermeta . '
                    WHERE meta_key not like "%s" AND meta_key not like "%s"';
        $sql = $this->wpdb->prepare($sql, "wpcf-%", "\_%");
        $usermetas = $this->wpdb->get_results($sql);

        if (!empty($usermetas)) {
            $usermetas = json_decode(json_encode($usermetas), true);
            $to_include = array('first_name', 'last_name', 'description');

            foreach ($usermetas as $n => $metas) {
                if (!in_array($metas['meta_key'], $to_include))
                    continue;

                if (!isset($fields[$metas['meta_key']]))
                    $fields[$metas['meta_key']] = array();

                $fields[$metas['meta_key']]['id'] = $metas['meta_key'];
                $fields[$metas['meta_key']]['slug'] = $metas['meta_key'];
                $fields[$metas['meta_key']]['type'] = ($metas['meta_key'] == 'description') ? 'wysiwyg' : 'textfield';
                $fields[$metas['meta_key']]['name'] = ($metas['meta_key'] == 'description') ? 'Biographical Info' : ucwords(str_replace("_", " ", $metas['meta_key']));
                $fields[$metas['meta_key']]['data'] = '';
                $fields[$metas['meta_key']]['meta_key'] = $metas['meta_key'];
                $fields[$metas['meta_key']]['post_type'] = 'user';
                $fields[$metas['meta_key']]['meta_type'] = 'usermeta';
                $fields[$metas['meta_key']]['post_labels'] = $fields[$metas['meta_key']]['name'];
                $fields[$metas['meta_key']]['plugin_type'] = "";
                $fields[$metas['meta_key']]['plugin_type_prefix'] = "";
            }
        }
        return $fields;
    }

    public function getFields($autogenerate = array('username' => true, 'nickname' => true, 'password' => true), $role = "", $add_default = true, $localized_message_callback = null) {
        // ALL FIELDS
        $fields_all = array();

        // fetch custom fields for post type even if not created by types or default
        $groups = array();
        $groups_conditions = array();

        $fields = $this->getCustomFields($role);

        //Check this because is working fine in post without it
        foreach ($fields as &$field) {            
            if ($field['plugin_type'] == 'types') {
                if (isset($field['data']['validate']['required']['message'])) {
                    $mym = $field['data']['validate']['required']['message'];
                    if ($localized_message_callback) {
                        $mym = call_user_func($localized_message_callback, 'field_required');                        
                    }
                    $field['data']['validate']['required']['message'] = $mym;
                }
                if (isset($field['data']['validate']['number']['message'])) {
                    $mym = $field['data']['validate']['number']['message'];
                    if ($localized_message_callback) {
                        $mym = call_user_func($localized_message_callback, 'enter_valid_number');
                    }
                    $field['data']['validate']['number']['message'] = $mym;
                }
                if (isset($field['data']['validate']['url']['message'])) {
                    $mym = $field['data']['validate']['url']['message'];
                    if ($localized_message_callback) {
                        $mym = call_user_func($localized_message_callback, 'enter_valid_url');
                    }
                    $field['data']['validate']['url']['message'] = $mym;
                }
            }
        }

        //#########################################################################################

        $user_fields = array();

        if ($add_default) {

            if ($localized_message_callback) {
                $message = call_user_func($localized_message_callback, 'field_required');
            } else {
                $message = __('This field is requireds', 'wp-cred');
            }

            $expression_user = isset($autogenerate['username']) && ( (bool) $autogenerate['username'] !== true || $autogenerate['username'] === 'false');
            $expression_nick = isset($autogenerate['nickname']) && ( (bool) $autogenerate['nickname'] !== true || $autogenerate['nickname'] === 'false');
            $expression_pawwsd = isset($autogenerate['password']) && ( (bool) $autogenerate['password'] !== true || $autogenerate['password'] === 'false');

            if ($expression_user === true) {
                $user_fields['user_login'] = array('post_type' => 'user', 'post_labels' => __('Username', 'wp-cred'), 'id' => 'user_login', 'wp_default' => true, 'slug' => 'user_login', 'type' => 'textfield', 'name' => __('Username', 'wp-cred'), 'description' => 'Username', 'data' => array('repetitive' => 0, 'validate' => array('required' => array('active' => 1, 'value' => true, 'message' => $message)), 'conditional_display' => array(), 'disabled_by_type' => 0));
            }

            if ($expression_nick === true) {
                $user_fields['nickname'] = array('post_type' => 'user', 'post_labels' => __('Nickname', 'wp-cred'), 'id' => 'nickname', 'wp_default' => true, 'slug' => 'nickname', 'type' => 'textfield', 'name' => __('Nickname', 'wp-cred'), 'description' => 'Nickname', 'data' => array('repetitive' => 0, 'validate' => array('required' => array('active' => 1, 'value' => true, 'message' => $message)), 'conditional_display' => array(), 'disabled_by_type' => 0));
            }

            if ($expression_pawwsd === true) {
                $user_fields['user_pass'] = array('post_type' => 'user', 'post_labels' => __('Password', 'wp-cred'), 'id' => 'user_pass', 'wp_default' => true, 'slug' => 'user_pass', 'type' => 'password', 'name' => __('Password', 'wp-cred'), 'description' => 'Password', 'data' => array('repetitive' => 0, 'validate' => array('required' => array('active' => 1, 'value' => true, 'message' => $message)), 'conditional_display' => array(), 'disabled_by_type' => 0));
                $user_fields['user_pass2'] = array('post_type' => 'user', 'post_labels' => __('Repeat Password', 'wp-cred'), 'id' => 'user_pass2', 'wp_default' => true, 'slug' => 'user_pass2', 'type' => 'password', 'name' => __('Repeat Password', 'wp-cred'), 'description' => 'Repeat Password', 'data' => array('repetitive' => 0, 'validate' => array('required' => array('active' => 1, 'value' => true, 'message' => $message)), 'conditional_display' => array(), 'disabled_by_type' => 0));
            }

            $user_fields['user_email'] = array('post_type' => 'user', 'post_labels' => __('Email', 'wp-cred'), 'id' => 'user_email', 'wp_default' => true, 'slug' => 'user_email', 'type' => 'email', 'name' => __('Email', 'wp-cred'), 'description' => 'Email', 'data' => array('repetitive' => 0, 'validate' => array('email' => array('active' => 1, 'message' => __('Please enter a valid email address', 'wp-cred')), 'required' => array('active' => 1, 'value' => true, 'message' => $message)), 'conditional_display' => array(), 'disabled_by_type' => 0));
            $user_fields['user_url'] = array('post_type' => 'user', 'post_labels' => __('Website', 'wp-cred'), 'id' => 'user_url', 'wp_default' => true, 'slug' => 'user_url', 'type' => 'textfield', 'name' => __('Website', 'wp-cred'), 'description' => 'Url', 'data' => array(/* 'repetitive' => 0, 'validate' => array ( 'required' => array ( 'active' => 1, 'value' => true, 'message' => __('This field is required','wp-cred') ) ), 'conditional_display' => array ( ), 'disabled_by_type' => 0 */));
        }

        $parents = array();

        // EXTRA FIELDS
        $extra_fields = array();
        $extra_fields['recaptcha'] = array('id' => 're_captcha', 'slug' => 'recaptcha', 'name' => esc_js(__('reCaptcha', 'wp-cred')), 'type' => 'recaptcha', 'cred_builtin' => true, 'description' => esc_js(__('Adds Image Captcha to your forms to prevent automatic submision by bots', 'wp-cred')));
        $setts = CRED_Loader::get('MODEL/Settings')->getSettings();
        if (
                !isset($setts['recaptcha']['public_key']) ||
                !isset($setts['recaptcha']['private_key']) ||
                empty($setts['recaptcha']['public_key']) ||
                empty($setts['recaptcha']['private_key'])
        ) {
            // no keys set for API
            $extra_fields['recaptcha']['disabled'] = true;
            $extra_fields['recaptcha']['disabled_reason'] = sprintf('<a href="%s" target="_blank">%s</a> %s', CRED_CRED::$settingsPage, __('Get and Enter your API keys', 'wp-cred'), esc_js(__('to use the Captcha field.', 'wp-cred')));
        }
        /* else
          $extra_fields['recaptcha']['disabled']=false; */

        // featured image field
        $extra_fields['_featured_image'] = array('id' => '_featured_image', 'slug' => '_featured_image', 'name' => esc_js(__('Featured Image', 'wp-cred')), 'type' => 'image', 'cred_builtin' => true, 'description' => 'Featured Image');
        $extra_fields['_featured_image']['supports'] = false;

        // BASIC FORM FIELDS
        $form_fields = array();
        $form_fields['form'] = array('id' => 'creduserform', 'name' => esc_js(__('User Form Container', 'wp-cred')), 'slug' => 'creduserform', 'type' => 'creduserform', 'cred_builtin' => true, 'description' => esc_js(__('User Form (required)', 'wp-cred', 'wp-cred')));
        //$form_fields['form_end']=array('id'=>'form_end','name'=>'Form End','slug'=>'form_end','type'=>'form_end','cred_builtin'=>true,'description'=>__('End of Form'));
        $form_fields['form_submit'] = array('value' => __('Submit', 'wp-cred'), 'id' => 'form_submit', 'name' => esc_js(__('Form Submit', 'wp-cred')), 'slug' => 'form_submit', 'type' => 'form_submit', 'cred_builtin' => true, 'description' => esc_js(__('Form Submit Button', 'wp-cred')));
        $form_fields['form_messages'] = array('value' => '', 'id' => 'form_messages', 'name' => esc_js(__('Form Messages', 'wp-cred')), 'slug' => 'form_messages', 'type' => 'form_messages', 'cred_builtin' => true, 'description' => esc_js(__('Form Messages Container', 'wp-cred')));
        $form_fields['user_login'] = array('post_type' => 'user', 'post_labels' => __('Username', 'wp-cred'), 'id' => 'user_login', 'wp_default' => true, 'slug' => 'user_login', 'type' => 'textfield', 'name' => __('Username', 'wp-cred'), 'description' => 'Username', 'data' => array('repetitive' => 0, 'validate' => array('required' => array('active' => 1, 'value' => true, 'message' => $message)), 'conditional_display' => array(), 'disabled_by_type' => 0));
        //nickname is required
        $form_fields['nickname'] = array('post_type' => 'user', 'post_labels' => __('Nickname', 'wp-cred'), 'id' => 'nickname', 'wp_default' => true, 'slug' => 'nickname', 'type' => 'textfield', 'name' => __('Nickname', 'wp-cred'), 'description' => 'Nickname', 'data' => array(/* 'repetitive' => 0, 'validate' => array ( 'required' => array ( 'active' => 1, 'value' => true, 'message' => __('This field is required','wp-cred') ) ), 'conditional_display' => array ( ), 'disabled_by_type' => 0 */));
        $form_fields['user_pass'] = array('post_type' => 'user', 'post_labels' => __('Password', 'wp-cred'), 'id' => 'user_pass', 'wp_default' => true, 'slug' => 'user_pass', 'type' => 'password', 'name' => __('Password', 'wp-cred'), 'description' => 'Password', 'data' => array('repetitive' => 0, 'validate' => array('required' => array('active' => 1, 'value' => true, 'message' => $message)), 'conditional_display' => array(), 'disabled_by_type' => 0));
        $form_fields['user_pass2'] = array('post_type' => 'user', 'post_labels' => __('Repeat Password', 'wp-cred'), 'id' => 'user_pass2', 'wp_default' => true, 'slug' => 'user_pass2', 'type' => 'password', 'name' => __('Repeat Password', 'wp-cred'), 'description' => 'Repeat Password', 'data' => array('repetitive' => 0, 'validate' => array('required' => array('active' => 1, 'value' => true, 'message' => $message)), 'conditional_display' => array(), 'disabled_by_type' => 0));

        // TAXONOMIES FIELDS
        $taxonomies = array();

        $form_fields = array_merge($user_fields, $form_fields);

        $fields_all['groups'] = $groups;
        $fields_all['groups_conditions'] = $groups_conditions;
        $fields_all['form_fields'] = $form_fields;
        $fields_all['user_fields'] = $user_fields;
        $fields_all['custom_fields'] = $fields;
        $fields_all['taxonomies'] = $taxonomies;
        $fields_all['parents'] = $parents;
        $fields_all['extra_fields'] = $extra_fields;
        $fields_all['form_fields_count'] = count($form_fields);
        $fields_all['user_fields_count'] = count($user_fields);
        $fields_all['custom_fields_count'] = count($fields);
        $fields_all['taxonomies_count'] = count($taxonomies);
        $fields_all['parents_count'] = count($parents);
        $fields_all['extra_fields_count'] = count($extra_fields);

        return $fields_all;
    }

    public function getAllFields() {
        return get_option('wpcf-fields');
    }

}
