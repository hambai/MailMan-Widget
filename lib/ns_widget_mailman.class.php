<?php

class NS_Widget_MailMan extends WP_Widget {
	private $default_failure_message;
	private $default_loader_graphic = '/wp-content/plugins/mailman-widget/images/ajax-loader.gif';
	private $default_subscribe_text;
	private $default_success_message;
	private $default_title;
	private $successful_subscribe = false;
	private $subscribe_errors;
	private $ns_mm_plugin;

	public function NS_Widget_MailMan () {
		$this->default_failure_message = __('There was a problem processing your submission. Please try again.');
		$this->default_subscribe_text = __('Subscribe');
		$this->default_success_message = __('Thank you for subscribing to our mailing list!');
		$this->default_title = __('Subscribe to our mailing list');
		$widget_options = array('classname' => 'widget_ns_mailman', 'description' => __( "Displays a subscription form for a MailMan mailing list.", 'mailman-widget'));
		$this->WP_Widget('ns_widget_mailman', __('MailMan Subscription', 'mailman-widget'), $widget_options);
		$this->ns_mm_plugin = NS_MM_Plugin::get_instance();
		$this->default_loader_graphic = get_bloginfo('wpurl') . $this->default_loader_graphic;
		add_action('init', array(&$this, 'add_scripts'));
		add_action('parse_request', array(&$this, 'process_submission'));
	}

	public function add_scripts () {
		wp_enqueue_script('ns-mm-widget', get_bloginfo('wpurl') . '/wp-content/plugins/mailman-widget/js/mailman-widget-min.js', array('jquery'), false);
	}

	public function form ($instance) {
		$mailman = $this->ns_mm_plugin->get_mailman();
		if (false != $mailman) {
			$this->lists = $mailman->lists();
			$defaults = array(
				'failure_message' => $this->default_failure_message,
				'title' => $this->default_title,
				'subscribe_text' => $this->default_subscribe_text,
				'success_message' => $this->default_success_message
			);
			$vars = wp_parse_args($instance, $defaults);
			extract($vars);
			$form = '<h3>' . __('General Settings', 'mailman-widget') . '</h3><p><label>' . __('Title :', 'mailman-widget') . '<input class="widefat" id=""' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" /></label></p>';
			$form .= '<p><label>' . __('Select a Mailing List :', 'mailman-widget') . '';
			$form .= '<select class="widefat" id="' . $this->get_field_id('current_mailing_list') . '" name="' . $this->get_field_name('current_mailing_list') . '">';
			foreach ($this->lists as $key => $value) {
				$selected = (isset($current_mailing_list) && $current_mailing_list == $value['path']) ? ' selected="selected" ' : '';
				$form .= '<option ' . $selected . 'value="' . $value['path'] . '">' . __($value['name'], 'mailman-widget') . '</option>';
			}
			$form .= '</select></label></p>';
			$form .= '<p><label>' . __('Admin password :', 'mailman-widget') . '<input type="password" class="widefat" id="' . $this->get_field_id('adminpw') .'" name="' . $this->get_field_name('adminpw') . '" value="' . $adminpw . '" /></label></p>';
			$form .= '<p><label>' . __('Sign Up Button Text :', 'mailman-widget') . '<input type="text" class="widefat" id="' . $this->get_field_id('subscribe_text') .'" name="' . $this->get_field_name('subscribe_text') . '" value="' . $subscribe_text . '" /></label></p>';
			$form .= '<h3>' . __('Notifications', 'mailman-widget') . '</h3><p>' . __('Use these fields to customize what your visitors see after they submit the form', 'mailman-widget') . '</p><p><label>' . __('Success :', 'mailman-widget') . '<textarea class="widefat" id="' . $this->get_field_id('success_message') . '" name="' . $this->get_field_name('success_message') . '">' . $success_message . '</textarea></label></p><p><label>' . __('Failure :', 'mailman-widget') . '<textarea class="widefat" id="' . $this->get_field_id('failure_message') . '" name="' . $this->get_field_name('failure_message') . '">' . $failure_message . '</textarea></label></p>';
		} else {
			$form = $this->ns_mm_plugin->get_admin_notices();
		}
		echo $form;
	}

	public function process_submission () {
		if (isset($_GET[$this->id_base . '_email'])) {
			header('Content-Type: application/json');
			$response = '';
			$merge_vars = array();
			$result = array('success' => false);
			if (is_email($_GET[$this->id_base . '_email'])) {
				$mailman = $this->ns_mm_plugin->get_mailman($_GET['ns_mm_number']);
				if (!$mailman) { return false; }
				$mailman->list=$this->get_current_mailing_list($_GET['ns_mm_number']);
				$mailman->adminpw=$this->get_adminpw($_GET['ns_mm_number']);
				$result['success'] = $mailman->subscribe($_GET[$this->id_base . '_email']);
				if ($result['success']) { $result['success_message'] =  $this->get_success_message($_GET['ns_mm_number']); }
				elseif ($mailman->error) { $result['error'] = $mailman->error; }
				else { $result['error'] = $this->get_failure_message($_GET['ns_mm_number']); }
			} else {
				$result['error'] = $this->get_failure_message($_GET['ns_mm_number']);
			}
			$response = json_encode($result);
			exit($response);
		} elseif (isset($_POST[$this->id_base . '_email'])) {
			$this->subscribe_errors = '<div class="error">'  . $this->get_failure_message($_POST['ns_mm_number']) .  '</div>';
			if (!is_email($_POST[$this->id_base . '_email'])) {
				return false;
			}
			$mailman = $this->ns_mm_plugin->get_mailman();
			if (!$mailman) { return false; }
			$mailman->list=$this->get_current_mailing_list($_POST['ns_mm_number']);
			$mailman->adminpw=$this->get_adminpw($_POST['ns_mm_number']);
			$subscribed=$mailman->subscribe($_POST[$this->id_base . '_email']);
			if (!$subscribed) { return false; }
			$this->subscribe_errors = '';
			setcookie($this->id_base . '-' . $this->number, $this->hash_mailing_list_id(), time() + 31556926);
			$this->successful_subscribe = true;
			$this->subscribe_success_message = '<p>' . $this->get_success_message($_POST['ns_mm_number']) . '</p>';
			return true;
		}
	}

	public function update ($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['current_mailing_list'] = esc_attr($new_instance['current_mailing_list']);
		$instance['adminpw'] = esc_attr($new_instance['adminpw']);
		$instance['failure_message'] = esc_attr($new_instance['failure_message']);
		$instance['subscribe_text'] = esc_attr($new_instance['subscribe_text']);
		$instance['success_message'] = esc_attr($new_instance['success_message']);
		$instance['title'] = esc_attr($new_instance['title']);
		return $instance;
	}

	public function widget ($args, $instance) {
		extract($args);
		if ((isset($_COOKIE[$this->id_base . '-' . $this->number]) && $this->hash_mailing_list_id($this->number) == $_COOKIE[$this->id_base . '-' . $this->number]) || false == $this->ns_mm_plugin->get_mailman()) {
			return 0;
		} else {
			$widget = $before_widget . $before_title . $instance['title'] . $after_title;
			if ($this->successful_subscribe) {
				$widget .= $this->subscribe_success_message;
			} else {
				$widget .= '<form action="' . $_SERVER['REQUEST_URI'] . '" id="' . $this->id_base . '_form-' . $this->number . '" method="post">' . $this->subscribe_errors . '<label>' . __('Email Address :', 'mailman-widget') . '</label><input type="hidden" name="ns_mm_number" value="' . $this->number . '" /><input type="text" name="' . $this->id_base . '_email" /><input class="button" type="submit" name="' . __($instance['subscribe_text'], 'mailman-widget') . '" value="' . __($instance['subscribe_text'], 'mailman-widget') . '" /></form><script type="text/javascript"> jQuery(\'#' . $this->id_base . '_form-' . $this->number . '\').ns_mm_widget({"url" : "' . $_SERVER['PHP_SELF'] . '", "cookie_id" : "'. $this->id_base . '-' . $this->number . '", "cookie_value" : "' . $this->hash_mailing_list_id() . '", "loader_graphic" : "' . $this->default_loader_graphic . '"}); </script>';
			}
			$widget .= $after_widget;
			echo $widget;
		}
	}

	private function hash_mailing_list_id () {
		$options = get_option($this->option_name);
		$hash = md5($options[$this->number]['current_mailing_list']);
		return $hash;
	}

	private function get_current_mailing_list ($number = null) {
		$options = get_option($this->option_name);
		return $options[$number]['current_mailing_list'];
	}

	private function get_adminpw ($number = null) {
		$options = get_option($this->option_name);
		return $options[$number]['adminpw'];
	}

	private function get_failure_message ($number = null) {
		$options = get_option($this->option_name);
		return $options[$number]['failure_message'];
	}

	private function get_success_message ($number = null) {
		$options = get_option($this->option_name);
		return $options[$number]['success_message'];
	}
}
//eof