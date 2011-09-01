<?php
/**
 *
 */
class NS_MM_Plugin {
	private $options;
	private $donate_link = 'http://tinyurl.com/hm2kpaypal';
	private static $instance;
	private static $mailman;
	private static $name = 'NS_MM_Plugin';
	private static $prefix = 'ns_mm';
	private static $public_option = 'no';
	private static $textdomain = 'mailman-widget';
	private function __construct () {
		register_activation_hook(__FILE__, array(&$this, 'set_up_options'));
		/**
		 * Set up the settings.
		 */
		add_action('admin_init', array(&$this, 'register_settings'));
		/**
		 * Set up the administration page.
		 */
		add_action('admin_menu', array(&$this, 'set_up_admin_page'));
		/**
		 * Fetch the options, and, if they haven't been set up yet, display a notice to the user.
		 */
		$this->get_options();
		if ('' == $this->options) {
			add_action('admin_notices', array(&$this, 'admin_notices'));
		}
		/**
		 * Add our widget when widgets get intialized.
		 */
		add_action('widgets_init', create_function('', 'return register_widget("NS_Widget_MailMan");'));
		add_filter('plugin_row_meta', array(&$this, 'add_plugin_meta_links'), 10, 2);
		/**
		 *
		 */
		$this->load_text_domain();
	}
	public static function get_instance () {
		if (empty(self::$instance)) {
			self::$instance = new self::$name;
		}
		return self::$instance;
	}
	public function add_plugin_meta_links ($links, $file) {
		if (plugin_basename(realpath(dirname(__FILE__) . '/../mailman-widget.php')) == $file) {
			$links[] = '<a href="' . $this->donate_link . '">' . __('Donate', 'mailman-widget') . '</a>';
		}
		return $links;
	}
	/**
	 *
	 */
	public function admin_notices () {
		echo '<div class="error fade">' . $this->get_admin_notices() . '</div>';
	}
	public function admin_page () {
		global $blog_id;
		$adminurl = (is_array($this->options)) ? $this->options['adminurl'] : '';
		if (isset($_POST[self::$prefix . '_nonce'])) {
			$nonce = $_POST[self::$prefix . '_nonce'];
			$nonce_key = self::$prefix . '_update_options';
			if (! wp_verify_nonce($nonce, $nonce_key)) {
				echo '<div class="wrap">
					<div id="icon-options-general" class="icon32"><br /></div>
					<h2>MailMan Widget Settings</h2><p>' . __('What you\'re trying to do looks a little shady.', 'mailman-widget') . '</p></div>';
				return false;
			} else {
				$new_adminurl = $_POST[self::$prefix . '-adminurl'];
				$new_options['adminurl'] = $new_adminurl;
				$this->update_options($new_options);
				$adminurl = $this->options['adminurl'];
			}
		}
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32">
				<br />
			</div>
			<h2><?php echo __('MailMan Widget Settings', 'mailman-widget') ; ?></h2>
			<p><?php echo __('Enter a valid MailMan admin URL key here to get started. Once you\'ve done that, you can use the MailMan Widget from the Widgets menu. You will need to have at least MailMan list set up before the using the widget.', 'mailman-widget') ?>
			</p>
				<form action="options.php" method="post">
			<?php settings_fields(self::$prefix . '_options'); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="' . self::$prefix . '-adminurl">MailMan AdminURL</label>
					</th>
					<td>
						<input class="regular-text" id="<?php echo self::$prefix; ?>-adminurl" name="<?php echo self::$prefix; ?>_options[adminurl]" type="text" value="<?php echo $adminurl ?>" />
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php echo  __('Save Changes', 'mailman-widget'); ?>" />
			</p>
		</form>
	</div>
	<?php
	}
	public function get_admin_notices () {
		global $blog_id;
		$notice = '<p>';
		$notice .= __('You\'ll need to set up the MailMan subscription widget plugin options before using it. ', 'mailman-widget') . __('You can make your changes', 'mailman-widget') . ' <a href="' . get_admin_url($blog_id) . 'options-general.php?page=mailman-widget/lib/ns_mm_plugin.class.php">' . __('here', 'mailman-widget') . '.</a>';
		$notice .= '</p>';
		return $notice;
	}
	public function get_mailman () {
		$adminurl = $this->get_adminurl();
		if (false == $adminurl) {
			return false;
		} else {
			if (empty(self::$mailman)) {
				self::$mailman = new MailMan($adminurl);
			}
			return self::$mailman;
		}
	}
	public function get_options () {
		$this->options = get_option(self::$prefix . '_options');
		return $this->options;
	}
	public function load_text_domain () {
		load_plugin_textdomain(self::$textdomain, null, str_replace('lib', 'languages', dirname(plugin_basename(__FILE__))));
	}
	public function register_settings () {
		register_setting( self::$prefix . '_options', self::$prefix . '_options', array($this, 'validate_adminurl'));
	}
	public function remove_options () {
		delete_option(self::$prefix . '_options');
	}
	public function set_up_admin_page () {
		add_submenu_page('options-general.php', 'MailMan Widget Options', 'MailMan Widget', 'activate_plugins', __FILE__, array(&$this, 'admin_page'));
	}
	public function set_up_options () {
		add_option(self::$prefix . '_options', '', '', self::$public_option);
	}
	public function validate_adminurl ($array) {
		return $array;
		/*if (isset($array['adminurl']) && $array['adminurl']) {
			$array['adminurl']=filter_var($array, FILTER_VALIDATE_URL);
			if ($array['adminurl']) {
				return $array;
			}
		}*/
	}
	private function get_adminurl () {
		if (is_array($this->options) && ! empty($this->options['adminurl'])) {
			return $this->options['adminurl'];
		} else {
			return false;
		}
	}
	private function update_options ($options_values) {
		$old_options_values = get_option(self::$prefix . '_options');
		$new_options_values = wp_parse_args($options_values, $old_options_values);
		update_option(self::$prefix .'_options', $new_options_values);
		$this->get_options();
	}
}
//eof