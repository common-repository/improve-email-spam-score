<?php
/**
 * Plugin Name:       Improve Email Spam Score
 * Description:       Adds a return-path and envelope-from address to the WordPress wp_mail() function. This reduces the spam score sent from your website form.
 * Version:           1.2
 * Author:            Teet Bergmann
 * Author URI:        https://smartdisain.eu/
 * Text Domain:       improve-email-spam-score
 * Domain Path:       /languages
 * Requires at least: 4.0
 * Requires PHP:      5.6
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit; 

function email_return_fix_load_plugin_textdomain() {
    load_plugin_textdomain('improve-email-spam-score', false, dirname(plugin_basename(__FILE__)) . '/languages'); 
}
add_action('plugins_loaded', 'email_return_fix_load_plugin_textdomain');

class Email_Fix_Plugin {
    public function __construct() {
        add_action('admin_menu', array($this, 'create_email_fix_plugin_settings_page'));
    }

    public function create_email_fix_plugin_settings_page() {
        $page_title = esc_html__('Spam Score Fix', 'improve-email-spam-score');
        $menu_title = esc_html__('Spam Score Fix', 'improve-email-spam-score');
        $capability = 'manage_options';
        $slug = 'improve_email_spam_score';
        $callback = array($this, 'plugin_settings_page_content');
        $icon = 'dashicons-email-alt';
        $position = 100;

        add_menu_page($page_title, $menu_title, $capability, $slug, $callback, $icon, $position);
    }

    public function plugin_settings_page_content() {
        if (isset($_POST['updated']) && $_POST['updated'] === 'true') {
            if (isset($_POST['emailfix_form']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['emailfix_form'])), 'emailfix_update')) {
                $this->handle_form();
            } else {
                $this->render_error_message(); // Render error if nonce is invalid
            }
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Spam Score Fix', 'improve-email-spam-score'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('emailfix_update', 'emailfix_form'); ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="from_name"><?php echo esc_html__('From Name', 'improve-email-spam-score'); ?></label>
                            </th>
                            <td>
                                <input name="from_name" type="text" id="from_name" value="<?php echo esc_attr(get_option('emailfix_from_name')); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="from_email"><?php echo esc_html__('From Email', 'improve-email-spam-score'); ?></label>
                            </th>
                            <td>
                                <input name="from_email" type="email" id="from_email" value="<?php echo esc_attr(get_option('emailfix_email')); ?>" class="regular-text">
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_html__('Save', 'improve-email-spam-score'); ?>">
                </p>
                <input type="hidden" name="updated" value="true">
            </form>
        </div>
        <?php
    }

    public function handle_form() {
    if (isset($_POST['emailfix_form']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['emailfix_form'])), 'emailfix_update')) {
        if (isset($_POST['from_name']) && isset($_POST['from_email'])) {
            $from_name = sanitize_text_field(wp_unslash($_POST['from_name']));
            $from_email = sanitize_email(wp_unslash($_POST['from_email']));

            update_option('emailfix_from_name', $from_name);
            update_option('emailfix_email', $from_email);

            $this->render_success_message();
        } else {
            $this->render_error_message();
        }
    } else {
        $this->render_error_message(); // Nonce verification failed
    }
	}

    private function render_error_message() {
        echo '<div class="error"><p>' . esc_html__('Something went wrong. Try again later.', 'improve-email-spam-score') . '</p></div>';
    }

    private function render_success_message() {
        echo '<div class="updated"><p>' . esc_html__('Data saved!', 'improve-email-spam-score') . '</p></div>';
    }
}

new Email_Fix_Plugin();

class Eposti_Tagasitee {
    public function __construct() {
        add_action('phpmailer_init', array($this, 'return_path'));
    }

    public function return_path($phpmailer) {
        $phpmailer->Sender = $phpmailer->From;
    }
}

new Eposti_Tagasitee();

add_filter('wp_mail_from', 'new_mail_from');
function new_mail_from($old) {
    return get_option('emailfix_email', $old);
}

add_filter('wp_mail_from_name', 'new_mail_from_name');
function new_mail_from_name($old) {
    return get_option('emailfix_from_name', $old);
}

add_action('phpmailer_init', 'prefix_add_phpmailer_setfrom');
function prefix_add_phpmailer_setfrom($phpmailer) {
    $phpmailer->setFrom(
        get_option('emailfix_email'),
        get_option('emailfix_from_name')
    );
}
