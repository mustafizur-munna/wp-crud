<?php

/**
 * Plugin Name: WDA CRUD
 * Plugin URI:        https://wordpress.org/plugins/wda-crud
 * Description:       Wordpress database CRUD operations
 * Version:           1.0.0
 * Requires at least: 6.6.2
 * Requires PHP:      7.2
 * Author:            Mustafizur Munna
 * Author URI:        https://mustafizur-munna.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wda-crud
 * Domain Path:       /languages
*/

if( ! defined( 'ABSPATH' ) ){
    exit();
}


class WDA_CRUD{
    private static $instance;
    private $table_name;
    public static function get_instance(){
        if( ! isset( self::$instance ) ){
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct(){
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wda_users';
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        add_action( 'plugins_loaded', array( $this, 'load_textdomain') );
        add_action( 'admin_menu', array( $this, 'wda_crud_admin_menu' ) );
    }

    public function activate(){
        global $wpdb;
        $table_charset = $wpdb->get_charset_collate();

        $create_table = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            person_name VARCHAR(255) NOT NULL,
            person_email VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $table_charset";

        if( ! function_exists( 'dbDelta' ) ){
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta( $create_table );
    }

    public function deactivate() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS $this->table_name");
    }

    public function load_textdomain(){
        load_plugin_textdomain( 'wda-crud', false, dirname( plugin_basename( __FILE__ ) ) . "/languages" );
    }

    public function wda_crud_admin_menu(){
        $title = __( 'WDA CRUD Users', 'wda-crud' );
        add_menu_page( $title, $title, 'manage_options', 'wda-crud-users', array( $this, 'wda_crud_menu_callback' ) );
        add_submenu_page( 'wda-crud-users', 'Add New User', 'Add New User', 'manage_options', 'add-new-user', array( $this, 'add_new_user_callback' ) );
        add_submenu_page( '-', 'Edit User', "Edit User", 'manage_options', 'edit-user', array( $this, 'edit_user_callback' ));
    }

    public function wda_crud_menu_callback(){
            global $wpdb;
            $users = $wpdb->get_results("SELECT * FROM $this->table_name");
        ?>
        <link rel="stylesheet" href="//cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">

        <div class="wrap">
            <table id="myTable" class="display">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach( $users as $user ): ?>
                        <tr>
                            <td><?php _e( $user->person_name, 'wda-crud' ); ?></td>
                            <td><?php _e( $user->person_email, 'wda-crud' ); ?></td>
                            <td><a href="<?php echo admin_url( "admin.php?page=edit-user&uid=$user->id" ); ?>">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="add-new-btn">
                <a href="<?php  echo admin_url( 'admin.php?page=add-new-user' ); ?>" class="button button-primary">Add New Data</a>
            </div>
        </div>

            <link rel="stylesheet" href="//cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">
            <script src="//cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
            <script>
                let table = new DataTable('#myTable');
            </script>
        <?php
    }

    public function add_new_user_callback(){
            if( isset( $_POST['usubmit'] ) ){
                $uname = isset( $_POST['uname'] ) ? $_POST['uname'] : "";
                $uname = sanitize_text_field( $uname );
                $uemail = isset( $_POST['uemail'] ) ? $_POST['uemail'] : "";
                $uemail = sanitize_email( $uemail );

                global $wpdb;
                $wpdb->insert($this->table_name,
                array(
                    'person_name' => $uname,
                    'person_email' => $uemail,
                    ),
                    array(
                        '%s',
                        '%s'
                    )
                );
            }
        ?>
            <div class="wrap">
                <form action="" method="post">
                    <input class="regular-text" style="margin-bottom:10px" placeholder="Name" type="text" name="uname" required> <br>
                    <input class="regular-text" style="margin-bottom:10px" placeholder="Email" type="email" name="uemail" required> <br>
                    <input class="button button-primary" type="submit" name="usubmit" value="Save">
                </form>
            </div>
        <?php
    }

    public function edit_user_callback(){
        if( isset( $_GET['uid'] ) ){
            $uid = isset( $_GET['uid'] ) ? $_GET['uid'] : "";
            $uid = sanitize_text_field( $uid );

            global $wpdb;
            $user_data = $wpdb->get_row( "SELECT * FROM $this->table_name WHERE id = $uid", ARRAY_A );
            if( isset( $_POST['usubmit'] ) ){
                $uname = isset( $_POST['uname'] ) ? $_POST['uname'] : "";
                $uname = sanitize_text_field( $uname );
                $uemail = isset( $_POST['uemail'] ) ? $_POST['uemail'] : "";
                $uemail = sanitize_email( $uemail );
                
                $if_updated = $wpdb->update($this->table_name,
                array(
                    'person_name' => $uname,
                    'person_email' => $uemail,
                    ),
                    array(
                        'id' => $uid
                    ),
                    array(
                        '%s',
                        '%s'
                    ),
                    array(
                        '%d'
                    )
                );
                if( $if_updated ){
                    ?>
                        <script>
                            location.href='<?php echo admin_url( 'admin.php?page=wda-crud-users' ) ?>';
                        </script>
                    <?php
                }
            }
            ?>
                <div class="wrap">
                    <form action="" method="post">
                        <input class="regular-text" style="margin-bottom:10px" placeholder="Name" type="text" name="uname" value="<?php echo esc_attr( $user_data['person_name'] ); ?>" required> <br>
                        <input class="regular-text" style="margin-bottom:10px" placeholder="Email" type="email" name="uemail" value="<?php echo esc_attr( $user_data['person_email'] ); ?>" required> <br>
                        <input class="button button-primary" type="submit" name="usubmit" value="Update">
                    </form>
                </div>
            <?php
        }
    }
}

WDA_CRUD::get_instance();