<?php
/**
 * Give Form Template
 *
 * @package     Give
 * @subpackage  Forms
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Donation Form
 *
 * @since 1.0
 *
 * @param array $args Arguments for display
 *
 * @return string $purchase_form
 */
function give_get_donation_form( $args = array() ) {

	global $post;

	$form_id = is_object( $post ) ? $post->ID : 0;

	if ( isset( $args['id'] ) ) {
		$form_id = $args['id'];
	}

	$defaults = apply_filters( 'give_form_args_defaults', array(
		'form_id' => $form_id
	) );

	$args = wp_parse_args( $args, $defaults );

	$form = new Give_Donate_Form( $args['form_id'] );

	//bail if no form ID
	if ( empty( $form->ID ) ) {
		return false;
	}

	$payment_mode = give_get_chosen_gateway( $form->ID );

	$form_action = add_query_arg( apply_filters( 'give_form_action_args', array(
		'payment-mode' => $payment_mode,
	) ),
		give_get_current_page_url()
	);

	//Sanity Check: Donation form not published or user doesn't have permission to view drafts
	if ( 'publish' !== $form->post_status && ! current_user_can( 'edit_give_forms', $form->ID ) ) {
		return false;
	}

	//Get the form wrap CSS classes.
	$form_wrap_classes       = $form->get_form_wrap_classes($args);

	//Get the <form> tag wrap CSS classes.
	$form_classes       = $form->get_form_classes($args);

	ob_start();

	/**
	 * Fires before the post form outputs.
	 *
	 * @since 1.0
	 *
	 * @param int $form ->ID The current form ID
	 * @param array $args An array of form args
	 */
	do_action( 'give_pre_form_output', $form->ID, $args ); ?>

	<div id="give-form-<?php echo $form->ID; ?>-wrap" class="<?php echo $form_wrap_classes; ?>">

		<?php if ( $form->is_close_donation_form() ) {

			//Get Goal thank you message.
			$display_thankyou_message = get_post_meta( $form->ID, '_give_form_goal_achieved_message', true );
			$display_thankyou_message = ! empty( $display_thankyou_message ) ? $display_thankyou_message : esc_html__( 'Thank you to all our donors, we have met our fundraising goal.', 'give' );

			//Print thank you message.
			apply_filters( 'give_goal_closed_output', give_output_error( $display_thankyou_message, true, 'success' ) );

		} else {

			if ( isset( $args['show_title'] ) && $args['show_title'] == true ) {

				echo apply_filters( 'give_form_title', '<h2 class="give-form-title">' . get_the_title( $form_id ) . '</h2>' );

			}

			do_action( 'give_pre_form', $form->ID, $args ); ?>

			<form id="give-form-<?php echo $form_id; ?>" class="<?php echo $form_classes; ?>" action="<?php echo esc_url_raw( $form_action ); ?>" method="post">
				<input type="hidden" name="give-form-id" value="<?php echo $form->ID; ?>"/>
				<input type="hidden" name="give-form-title" value="<?php echo htmlentities( $form->post_title ); ?>"/>
				<input type="hidden" name="give-current-url" value="<?php echo htmlspecialchars( give_get_current_page_url() ); ?>"/>
				<input type="hidden" name="give-form-url" value="<?php echo htmlspecialchars( give_get_current_page_url() ); ?>"/>
				<input type="hidden" name="give-form-minimum" value="<?php echo give_format_amount( give_get_form_minimum_price( $form->ID ) ); ?>"/>

				<!-- The following field is for robots only, invisible to humans: -->
				<span class="give-hidden" style="display: none !important;">
					<label for="give-form-honeypot-<?php echo $form_id; ?>"></label>
					<input id="give-form-honeypot-<?php echo $form_id; ?>" type="text" name="give-honeypot" class="give-honeypot give-hidden"/>
				</span>

				<?php

				//Price ID hidden field for variable (mult-level) donation forms
				if ( give_has_variable_prices( $form_id ) ) {
					//get default selected price ID
					$prices   = apply_filters( 'give_form_variable_prices', give_get_variable_prices( $form_id ), $form_id );
					$price_id = 0;
					//loop through prices
					foreach ( $prices as $price ) {
						if ( isset( $price['_give_default'] ) && $price['_give_default'] === 'default' ) {
							$price_id = $price['_give_id']['level_id'];
						};
					}
					?>
					<input type="hidden" name="give-price-id" value="<?php echo $price_id; ?>"/>
				<?php }

				do_action( 'give_checkout_form_top', $form->ID, $args );

				do_action( 'give_payment_mode_select', $form->ID, $args );

				do_action( 'give_checkout_form_bottom', $form->ID, $args );

				?>
			</form>

			<?php do_action( 'give_post_form', $form->ID, $args ); ?>

		<?php } ?>

		<!--end #give-form-<?php echo absint( $form->ID ); ?>--></div>
	<?php

	/**
	 * Fires after the post form outputs.
	 *
	 * @since 1.0
	 *
	 * @param int $form ->ID The current form ID
	 * @param array $args An array of form args
	 */
	do_action( 'give_post_form_output', $form->ID, $args );

	$final_output = ob_get_clean();

	echo apply_filters( 'give_donate_form', $final_output, $args );
}


/**
 *
 * Give Show Purchase Form
 *
 * Renders the Donation Form, hooks are provided to add to the checkout form.
 * The default Donation Form rendered displays a list of the enabled payment
 * gateways, a user registration form (if enable) and a credit card info form
 * if credit cards are enabled
 *
 * @since 1.0
 *
 * @param  int $form_id ID of the Give Form
 *
 * @return string
 */
function give_show_purchase_form( $form_id ) {

	$payment_mode = give_get_chosen_gateway( $form_id );

	if ( ! isset( $form_id ) && isset( $_POST['give_form_id'] ) ) {
		$form_id = $_POST['give_form_id'];
	}

	do_action( 'give_purchase_form_top', $form_id );

	if ( give_can_checkout() && isset( $form_id ) ) {

		do_action( 'give_purchase_form_before_register_login', $form_id );

		do_action( 'give_purchase_form_register_login_fields', $form_id );

		do_action( 'give_purchase_form_before_cc_form', $form_id );

		// Load the credit card form and allow gateways to load their own if they wish
		if ( has_action( 'give_' . $payment_mode . '_cc_form' ) ) {
			do_action( 'give_' . $payment_mode . '_cc_form', $form_id );
		} else {
			do_action( 'give_cc_form', $form_id );
		}

		do_action( 'give_purchase_form_after_cc_form', $form_id );

	} else {
		// Can't checkout
		do_action( 'give_purchase_form_no_access', $form_id );

	}

	do_action( 'give_purchase_form_bottom', $form_id );
}

add_action( 'give_purchase_form', 'give_show_purchase_form' );

/**
 *
 * Give Show Login/Register Form Fields
 *
 * @since  1.4.1
 *
 * @param  int   $form_id ID of the Give Form
 *
 * @return void
 */
function give_show_register_login_fields( $form_id ) {

	$show_register_form = give_show_login_register_option( $form_id );

	if ( ( $show_register_form === 'registration' || ( $show_register_form === 'both' && ! isset( $_GET['login'] ) ) ) && ! is_user_logged_in() ) : ?>
		<div id="give-checkout-login-register-<?php echo $form_id; ?>">
			<?php do_action( 'give_purchase_form_register_fields', $form_id ); ?>
		</div>
	<?php elseif ( ( $show_register_form === 'login' || ( $show_register_form === 'both' && isset( $_GET['login'] ) ) ) && ! is_user_logged_in() ) : ?>
		<div id="give-checkout-login-register-<?php echo $form_id; ?>">
			<?php do_action( 'give_purchase_form_login_fields', $form_id ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ( ! isset( $_GET['login'] ) && is_user_logged_in() ) || ! isset( $show_register_form ) || 'none' === $show_register_form || 'login' === $show_register_form ) {
		do_action( 'give_purchase_form_after_user_info', $form_id );
	}
}

add_action( 'give_purchase_form_register_login_fields', 'give_show_register_login_fields' );

/**
 * Donation Amount Field
 *
 * Outputs the donation amount field that appears at the top of the donation forms. If the user has custom amount enabled the field will output as a customizable input
 *
 * @since  1.0
 *
 * @param  int   $form_id Give Form ID
 * @param  array $args
 *
 * @return void
 */
function give_output_donation_amount_top( $form_id = 0, $args = array() ) {

	global $give_options;

	$variable_pricing    = give_has_variable_prices( $form_id );
	$allow_custom_amount = get_post_meta( $form_id, '_give_custom_amount', true );
	$currency_position   = isset( $give_options['currency_position'] ) ? $give_options['currency_position'] : 'before';
	$symbol              = give_currency_symbol( give_get_currency() );
	$currency_output     = '<span class="give-currency-symbol give-currency-position-' . $currency_position . '">' . $symbol . '</span>';
	$default_amount      = give_format_amount( give_get_default_form_amount( $form_id ) );
	$custom_amount_text  = get_post_meta( $form_id, '_give_custom_amount_text', true );

	do_action( 'give_before_donation_levels', $form_id, $args );

	//Set Price, No Custom Amount Allowed means hidden price field
	if ( $allow_custom_amount == 'no' ) {
		?>

		<label class="give-hidden" for="give-amount-hidden"><?php echo esc_html__( 'Donation Amount:', 'give' ); ?></label>
		<input id="give-amount" class="give-amount-hidden" type="hidden" name="give-amount"
		       value="<?php echo $default_amount; ?>" required>
		<div class="set-price give-donation-amount form-row-wide">
			<?php if ( $currency_position == 'before' ) {
				echo $currency_output;
			} ?>
			<span id="give-amount-text" class="give-text-input give-amount-top"><?php echo $default_amount; ?></span>
			<?php if ( $currency_position == 'after' ) {
				echo $currency_output;
			} ?>
		</div>
		<?php
	} else {
		//Custom Amount Allowed
		?>
		<div class="give-total-wrap">
			<div class="give-donation-amount form-row-wide">
				<?php if ( $currency_position == 'before' ) {
					echo $currency_output;
				} ?>
				<label class="give-hidden" for="give-amount"><?php echo esc_html__( 'Donation Amount:', 'give' ); ?></label>
				<input class="give-text-input give-amount-top" id="give-amount" name="give-amount" type="tel" placeholder="" value="<?php echo $default_amount; ?>" autocomplete="off">
				<?php if ( $currency_position == 'after' ) {
					echo $currency_output;
				} ?>
			</div>
		</div>
	<?php }

	do_action( 'give_after_donation_amount', $form_id, $args );

	//Custom Amount Text
	if ( ! $variable_pricing && $allow_custom_amount == 'yes' && ! empty( $custom_amount_text ) ) { ?>
		<p class="give-custom-amount-text"><?php echo $custom_amount_text; ?></p>
	<?php }

	//Output Variable Pricing Levels
	if ( $variable_pricing ) {
		give_output_levels( $form_id );
	}

	do_action( 'give_after_donation_levels', $form_id, $args );
}

add_action( 'give_checkout_form_top', 'give_output_donation_amount_top', 10, 2 );


/**
 * Outputs the Donation Levels in various formats such as dropdown, radios, and buttons
 *
 * @since  1.0
 *
 * @param  int $form_id Give Form ID
 *
 * @return string
 */
function give_output_levels( $form_id ) {

	//Get variable pricing
	$prices             = apply_filters( 'give_form_variable_prices', give_get_variable_prices( $form_id ), $form_id );
	$display_style      = get_post_meta( $form_id, '_give_display_style', true );
	$custom_amount      = get_post_meta( $form_id, '_give_custom_amount', true );
	$custom_amount_text = get_post_meta( $form_id, '_give_custom_amount_text', true );
	if ( empty( $custom_amount_text ) ) {
		$custom_amount_text = esc_html__( 'Give a Custom Amount', 'give' );
	}

	$output  = '';
	$counter = 0;

	switch ( $display_style ) {
		case 'buttons':

			$output .= '<ul id="give-donation-level-button-wrap" class="give-donation-levels-wrap give-list-inline">';

			foreach ( $prices as $price ) {
				$counter ++;
				$level_text    = apply_filters( 'give_form_level_text', ! empty( $price['_give_text'] ) ? $price['_give_text'] : give_currency_filter( give_format_amount( $price['_give_amount'] ) ), $form_id, $price );
				$level_classes = apply_filters( 'give_form_level_classes', 'give-donation-level-btn give-btn give-btn-level-' . $counter . ' ' . ( ( isset( $price['_give_default'] ) && $price['_give_default'] === 'default' ) ? 'give-default-level' : '' ), $form_id, $price );

				$output .= '<li>';
				$output .= '<button type="button" data-price-id="' . $price['_give_id']['level_id'] . '" class=" ' . $level_classes . '" value="' . give_format_amount( $price['_give_amount'] ) . '">';
				$output .= $level_text;
				$output .= '</button>';
				$output .= '</li>';

			}

			//Custom Amount
			if ( $custom_amount === 'yes' && ! empty( $custom_amount_text ) ) {
				$output .= '<li>';
				$output .= '<button type="button" data-price-id="custom" class="give-donation-level-btn give-btn give-btn-level-custom" value="custom">';
				$output .= $custom_amount_text;
				$output .= '</button>';
				$output .= '</li>';
			}

			$output .= '</ul>';

			break;

		case 'radios':

			$output .= '<ul id="give-donation-level-radio-list" class="give-donation-levels-wrap">';

			foreach ( $prices as $price ) {
				$counter ++;
				$level_text    = apply_filters( 'give_form_level_text', ! empty( $price['_give_text'] ) ? $price['_give_text'] : give_currency_filter( give_format_amount( $price['_give_amount'] ) ), $form_id, $price );
				$level_classes = apply_filters( 'give_form_level_classes', 'give-radio-input give-radio-input-level give-radio-level-' . $counter . ( ( isset( $price['_give_default'] ) && $price['_give_default'] === 'default' ) ? ' give-default-level' : '' ), $form_id, $price );

				$output .= '<li>';
				$output .= '<input type="radio" data-price-id="' . $price['_give_id']['level_id'] . '" class="' . $level_classes . '" name="give-radio-donation-level" id="give-radio-level-' . $counter . '" ' . ( ( isset( $price['_give_default'] ) && $price['_give_default'] === 'default' ) ? 'checked="checked"' : '' ) . ' value="' . give_format_amount( $price['_give_amount'] ) . '">';
				$output .= '<label for="give-radio-level-' . $counter . '">' . $level_text . '</label>';
				$output .= '</li>';

			}

			//Custom Amount
			if ( $custom_amount === 'yes' && ! empty( $custom_amount_text ) ) {
				$output .= '<li>';
				$output .= '<input type="radio" data-price-id="custom" class="give-radio-input give-radio-input-level give-radio-level-custom" name="give-radio-donation-level" id="give-radio-level-custom" value="custom">';
				$output .= '<label for="give-radio-level-custom">' . $custom_amount_text . '</label>';
				$output .= '</li>';
			}

			$output .= '</ul>';

			break;

		case 'dropdown':

			$output .= '<label for="give-donation-level" class="give-hidden">' . esc_html__( 'Choose Your Donation Amount', 'give' ) . ':</label>';
			$output .= '<select id="give-donation-level-' . $form_id . '" class="give-select give-select-level give-donation-levels-wrap">';

			//first loop through prices
			foreach ( $prices as $price ) {
				$level_text    = apply_filters( 'give_form_level_text', ! empty( $price['_give_text'] ) ? $price['_give_text'] : give_currency_filter( give_format_amount( $price['_give_amount'] ) ), $form_id, $price );
				$level_classes = apply_filters( 'give_form_level_classes', 'give-donation-level-' . $form_id . ( ( isset( $price['_give_default'] ) && $price['_give_default'] === 'default' ) ? ' give-default-level' : '' ), $form_id, $price );

				$output .= '<option data-price-id="' . $price['_give_id']['level_id'] . '" class="' . $level_classes . '" ' . ( ( isset( $price['_give_default'] ) && $price['_give_default'] === 'default' ) ? 'selected="selected"' : '' ) . ' value="' . give_format_amount( $price['_give_amount'] ) . '">';
				$output .= $level_text;
				$output .= '</option>';

			}

			//Custom Amount
			if ( $custom_amount === 'yes' && ! empty( $custom_amount_text ) ) {
				$output .= '<option data-price-id="custom" class="give-donation-level-custom" value="custom">' . $custom_amount_text . '</option>';
			}

			$output .= '</select>';

			break;
	}

	echo apply_filters( 'give_form_level_output', $output, $form_id );
}

/**
 * Display Reveal & Lightbox Button
 *
 * Outputs a button to reveal form fields
 *
 * @param int   $form_id
 * @param array $args
 *
 */
function give_display_checkout_button( $form_id, $args ) {

	$display_option = ( isset( $args['display_style'] ) && ! empty( $args['display_style'] ) )
		? $args['display_style']
		: get_post_meta( $form_id, '_give_payment_display', true );

	//no btn for onpage
	if ( $display_option === 'onpage' ) {
		return;
	}

	$display_label_field = get_post_meta( $form_id, '_give_reveal_label', true );
	$display_label       = ( ! empty( $display_label_field ) ? $display_label_field : esc_html__( 'Donate Now', 'give' ) );

	$output = '<button type="button" class="give-btn give-btn-' . $display_option . '">' . $display_label . '</button>';

	echo apply_filters( 'give_display_checkout_button', $output );
}

add_action( 'give_after_donation_levels', 'give_display_checkout_button', 10, 2 );

/**
 * Shows the User Info fields in the Personal Info box, more fields can be added via the hooks provided.
 *
 * @since  1.0
 *
 * @param  int $form_id
 *
 * @return void
 */
function give_user_info_fields( $form_id ) {

	if ( is_user_logged_in() ) :
		$user_data = get_userdata( get_current_user_id() );
	endif;

	do_action( 'give_purchase_form_before_personal_info', $form_id );
	?>
	<fieldset id="give_checkout_user_info">
		<legend><?php echo apply_filters( 'give_checkout_personal_info_text', esc_html__( 'Personal Info', 'give' ) ); ?></legend>
		<p id="give-first-name-wrap" class="form-row form-row-first">
			<label class="give-label" for="give-first">
				<?php esc_html_e( 'First Name', 'give' ); ?>
				<?php if ( give_field_is_required( 'give_first', $form_id ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
				<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'We will use this to personalize your account experience.', 'give' ); ?>"></span>
			</label>
			<input class="give-input required" type="text" name="give_first" placeholder="<?php esc_html_e( 'First Name', 'give' ); ?>" id="give-first" value="<?php echo is_user_logged_in() ? $user_data->first_name : ''; ?>"<?php if ( give_field_is_required( 'give_first', $form_id ) ) {
				echo ' required ';
			} ?>/>
		</p>

		<p id="give-last-name-wrap" class="form-row form-row-last">
			<label class="give-label" for="give-last">
				<?php esc_html_e( 'Last Name', 'give' ); ?>
				<?php if ( give_field_is_required( 'give_last', $form_id ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
				<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'We will use this as well to personalize your account experience.', 'give' ); ?>"></span>
			</label>

			<input class="give-input<?php if ( give_field_is_required( 'give_last', $form_id ) ) {
				echo ' required';
			} ?>" type="text" name="give_last" id="give-last" placeholder="<?php esc_html_e( 'Last Name', 'give' ); ?>" value="<?php echo is_user_logged_in() ? $user_data->last_name : ''; ?>"<?php if ( give_field_is_required( 'give_last', $form_id ) ) {
				echo ' required ';
			} ?> />
		</p>

		<?php do_action( 'give_purchase_form_before_email', $form_id ); ?>
		<p id="give-email-wrap" class="form-row form-row-wide">
			<label class="give-label" for="give-email">
				<?php esc_html_e( 'Email Address', 'give' ); ?>
				<?php if ( give_field_is_required( 'give_email', $form_id ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
				<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'We will send the purchase receipt to this address.', 'give' ); ?>"></span>
			</label>

			<input class="give-input required" type="email" name="give_email" placeholder="<?php esc_html_e( 'Email address', 'give' ); ?>" id="give-email" value="<?php echo is_user_logged_in() ? $user_data->user_email : ''; ?>"<?php if ( give_field_is_required( 'give_email', $form_id ) ) {
				echo ' required ';
			} ?>/>

		</p>
		<?php do_action( 'give_purchase_form_after_email', $form_id ); ?>

		<?php do_action( 'give_purchase_form_user_info', $form_id ); ?>
	</fieldset>
	<?php
	do_action( 'give_purchase_form_after_personal_info', $form_id );

}

add_action( 'give_purchase_form_after_user_info', 'give_user_info_fields' );
add_action( 'give_register_fields_before', 'give_user_info_fields' );

/**
 * Renders the credit card info form.
 *
 * @since  1.0
 *
 * @param  int $form_id
 *
 * @return void
 */
function give_get_cc_form( $form_id ) {

	ob_start();

	do_action( 'give_before_cc_fields', $form_id ); ?>

	<fieldset id="give_cc_fields-<?php echo $form_id ?>" class="give-do-validate">
		<legend><?php echo apply_filters( 'give_credit_card_fieldset_heading', esc_html__( 'Credit Card Info', 'give' ) ); ?></legend>
		<?php if ( is_ssl() ) : ?>
			<div id="give_secure_site_wrapper-<?php echo $form_id ?>">
				<span class="give-icon padlock"></span>
				<span><?php esc_html_e( 'This is a secure SSL encrypted payment.', 'give' ); ?></span>
			</div>
		<?php endif; ?>
		<p id="give-card-number-wrap-<?php echo $form_id ?>" class="form-row form-row-two-thirds">
			<label for="card_number-<?php echo $form_id ?>" class="give-label">
				<?php esc_html_e( 'Card Number', 'give' ); ?>
				<span class="give-required-indicator">*</span>
				<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'The (typically) 16 digits on the front of your credit card.', 'give' ); ?>"></span>
				<span class="card-type"></span>
			</label>

			<input type="tel" autocomplete="off" name="card_number" id="card_number-<?php echo $form_id ?>" class="card-number give-input required" placeholder="<?php esc_html_e( 'Card number', 'give' ); ?>" required/>
		</p>

		<p id="give-card-cvc-wrap-<?php echo $form_id ?>" class="form-row form-row-one-third">
			<label for="card_cvc-<?php echo $form_id ?>" class="give-label">
				<?php esc_html_e( 'CVC', 'give' ); ?>
				<span class="give-required-indicator">*</span>
				<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'The 3 digit (back) or 4 digit (front) value on your card.', 'give' ); ?>"></span>
			</label>

			<input type="tel" size="4" autocomplete="off" name="card_cvc" id="card_cvc-<?php echo $form_id ?>" class="card-cvc give-input required" placeholder="<?php esc_html_e( 'Security code', 'give' ); ?>" required/>
		</p>

		<p id="give-card-name-wrap-<?php echo $form_id ?>" class="form-row form-row-two-thirds">
			<label for="card_name-<?php echo $form_id ?>" class="give-label">
				<?php esc_html_e( 'Name on the Card', 'give' ); ?>
				<span class="give-required-indicator">*</span>
				<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'The name printed on the front of your credit card.', 'give' ); ?>"></span>
			</label>

			<input type="text" autocomplete="off" name="card_name" id="card_name-<?php echo $form_id ?>" class="card-name give-input required" placeholder="<?php esc_html_e( 'Card name', 'give' ); ?>" required/>
		</p>
		<?php do_action( 'give_before_cc_expiration' ); ?>
		<p class="card-expiration form-row form-row-one-third">
			<label for="card_expiry-<?php echo $form_id ?>" class="give-label">
				<?php esc_html_e( 'Expiration', 'give' ); ?>
				<span class="give-required-indicator">*</span>
				<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'The date your credit card expires, typically on the front of the card.', 'give' ); ?>"></span>
			</label>

			<input type="hidden" id="card_exp_month-<?php echo $form_id ?>" name="card_exp_month" class="card-expiry-month"/>
			<input type="hidden" id="card_exp_year-<?php echo $form_id ?>" name="card_exp_year" class="card-expiry-year"/>

			<input type="tel" autocomplete="off" name="card_expiry" id="card_expiry-<?php echo $form_id ?>" class="card-expiry give-input required" placeholder="<?php esc_html_e( 'MM / YY', 'give' ); ?>" required/>
		</p>
		<?php do_action( 'give_after_cc_expiration', $form_id ); ?>

	</fieldset>
	<?php
	do_action( 'give_after_cc_fields', $form_id );

	echo ob_get_clean();
}

add_action( 'give_cc_form', 'give_get_cc_form' );

/**
 * Outputs the default credit card address fields
 *
 * @since  1.0
 *
 * @param  int $form_id
 *
 * @return void
 */
function give_default_cc_address_fields( $form_id ) {

	$logged_in = is_user_logged_in();

	if ( $logged_in ) {
		$user_address = get_user_meta( get_current_user_id(), '_give_user_address', true );
	}
	$line1 = $logged_in && ! empty( $user_address['line1'] ) ? $user_address['line1'] : '';
	$line2 = $logged_in && ! empty( $user_address['line2'] ) ? $user_address['line2'] : '';
	$city  = $logged_in && ! empty( $user_address['city'] ) ? $user_address['city'] : '';
	$zip   = $logged_in && ! empty( $user_address['zip'] ) ? $user_address['zip'] : '';
	ob_start(); ?>
	<fieldset id="give_cc_address" class="cc-address">
		<legend><?php echo apply_filters( 'give_billing_details_fieldset_heading', esc_html__( 'Billing Details', 'give' ) ); ?></legend>
		<?php do_action( 'give_cc_billing_top' ); ?>
		<p id="give-card-address-wrap" class="form-row form-row-two-thirds">
			<label for="card_address" class="give-label">
				<?php esc_html_e( 'Address', 'give' ); ?>
				<?php
				if ( give_field_is_required( 'card_address', $form_id ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
				<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'The primary billing address for your credit card.', 'give' ); ?>"></span>
			</label>

			<input type="text" id="card_address" name="card_address" class="card-address give-input<?php if ( give_field_is_required( 'card_address', $form_id ) ) {
				echo ' required';
			} ?>" placeholder="<?php esc_html_e( 'Address line 1', 'give' ); ?>" value="<?php echo $line1; ?>"<?php if ( give_field_is_required( 'card_address', $form_id ) ) {
				echo '  required ';
			} ?>/>
		</p>

		<p id="give-card-address-2-wrap" class="form-row form-row-one-third">
			<label for="card_address_2" class="give-label">
				<?php esc_html_e( 'Address Line 2', 'give' ); ?>
				<?php if ( give_field_is_required( 'card_address_2', $form_id ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
				<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( '(optional) The suite, apt no, PO box, etc, associated with your billing address.', 'give' ); ?>"></span>
			</label>

			<input type="text" id="card_address_2" name="card_address_2" class="card-address-2 give-input<?php if ( give_field_is_required( 'card_address_2', $form_id ) ) {
				echo ' required';
			} ?>" placeholder="<?php esc_html_e( 'Address line 2', 'give' ); ?>" value="<?php echo $line2; ?>"<?php if ( give_field_is_required( 'card_address_2', $form_id ) ) {
				echo ' required ';
			} ?>/>
		</p>

		<p id="give-card-city-wrap" class="form-row form-row-two-thirds">
			<label for="card_city" class="give-label">
				<?php esc_html_e( 'City', 'give' ); ?>
				<?php if ( give_field_is_required( 'card_city', $form_id ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
				<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'The city for your billing address.', 'give' ); ?>"></span>
			</label>
			<input type="text" id="card_city" name="card_city" class="card-city give-input<?php if ( give_field_is_required( 'card_city', $form_id ) ) {
				echo ' required';
			} ?>" placeholder="<?php esc_html_e( 'City', 'give' ); ?>" value="<?php echo $city; ?>"<?php if ( give_field_is_required( 'card_city', $form_id ) ) {
				echo ' required ';
			} ?>/>
		</p>

		<p id="give-card-zip-wrap" class="form-row form-row-one-third">
			<label for="card_zip" class="give-label">
				<?php esc_html_e( 'Zip / Postal Code', 'give' ); ?>
				<?php if ( give_field_is_required( 'card_zip', $form_id ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
				<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'The zip or postal code for your billing address.', 'give' ); ?>"></span>
			</label>

			<input type="text" size="4" id="card_zip" name="card_zip" class="card-zip give-input<?php if ( give_field_is_required( 'card_zip', $form_id ) ) {
				echo ' required';
			} ?>" placeholder="<?php esc_html_e( 'Zip / Postal Code', 'give' ); ?>" value="<?php echo $zip; ?>" <?php if ( give_field_is_required( 'card_zip', $form_id ) ) {
				echo ' required ';
			} ?>/>
		</p>

		<p id="give-card-country-wrap" class="form-row form-row-first">
			<label for="billing_country" class="give-label">
				<?php esc_html_e( 'Country', 'give' ); ?>
				<?php if ( give_field_is_required( 'billing_country', $form_id ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
				<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'The country for your billing address.', 'give' ); ?>"></span>
			</label>

			<select name="billing_country" id="billing_country" class="billing-country billing_country give-select<?php if ( give_field_is_required( 'billing_country', $form_id ) ) {
				echo ' required';
			} ?>"<?php if ( give_field_is_required( 'billing_country', $form_id ) ) {
				echo ' required ';
			} ?>>
				<?php

				$selected_country = give_get_country();

				if ( $logged_in && ! empty( $user_address['country'] ) && '*' !== $user_address['country'] ) {
					$selected_country = $user_address['country'];
				}

				$countries = give_get_country_list();
				foreach ( $countries as $country_code => $country ) {
					echo '<option value="' . esc_attr( $country_code ) . '"' . selected( $country_code, $selected_country, false ) . '>' . $country . '</option>';
				}
				?>
			</select>
		</p>

		<p id="give-card-state-wrap" class="form-row form-row-last">
			<label for="card_state" class="give-label">
				<?php esc_html_e( 'State / Province', 'give' ); ?>
				<?php if ( give_field_is_required( 'card_state', $form_id ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
				<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'The state or province for your billing address.', 'give' ); ?>"></span>
			</label>

			<?php
			$selected_state = give_get_state();
			$states         = give_get_states( $selected_country );

			if ( $logged_in && ! empty( $user_address['state'] ) ) {
				$selected_state = $user_address['state'];
			}

			if ( ! empty( $states ) ) : ?>
				<select name="card_state" id="card_state" class="card_state give-select<?php if ( give_field_is_required( 'card_state', $form_id ) ) {
					echo ' required';
				} ?>"<?php if ( give_field_is_required( 'card_state', $form_id ) ) {
					echo ' required ';
				} ?>>
					<?php
					foreach ( $states as $state_code => $state ) {
						echo '<option value="' . $state_code . '"' . selected( $state_code, $selected_state, false ) . '>' . $state . '</option>';
					}
					?>
				</select>
			<?php else : ?>
				<input type="text" size="6" name="card_state" id="card_state" class="card_state give-input" placeholder="<?php esc_html_e( 'State / Province', 'give' ); ?>"/>
			<?php endif; ?>
		</p>
		<?php do_action( 'give_cc_billing_bottom' ); ?>
	</fieldset>
	<?php
	echo ob_get_clean();
}

add_action( 'give_after_cc_fields', 'give_default_cc_address_fields' );


/**
 * Renders the user registration fields. If the user is logged in, a login form is displayed other a registration form is provided for the user to create an account.
 *
 * @since  1.0
 *
 * @param  int $form_id
 *
 * @return string
 */
function give_get_register_fields( $form_id ) {

	global $user_ID;

	if ( is_user_logged_in() ) {
		$user_data = get_userdata( $user_ID );
	}

	$show_register_form = give_show_login_register_option( $form_id );

	ob_start(); ?>
	<fieldset id="give-register-fields-<?php echo $form_id; ?>">

		<?php if ( $show_register_form == 'both' ) { ?>
			<div class="give-login-account-wrap">
				<p class="give-login-message"><?php esc_html_e( 'Already have an account?', 'give' ); ?>&nbsp;
					<a href="<?php echo esc_url( add_query_arg( 'login', 1 ) ); ?>" class="give-checkout-login" data-action="give_checkout_login"><?php esc_html_e( 'Login', 'give' ); ?></a>
				</p>
				<p class="give-loading-text">
					<span class="give-loading-animation"></span> <?php esc_html_e( 'Loading...', 'give' ); ?></p>
			</div>
		<?php } ?>

		<?php do_action( 'give_register_fields_before', $form_id ); ?>

		<fieldset id="give-register-account-fields-<?php echo $form_id; ?>">
			<legend><?php echo apply_filters( 'give_create_account_fieldset_heading', esc_html__( 'Create an account', 'give' ) );
				if ( ! give_logged_in_only( $form_id ) ) {
					echo ' <span class="sub-text">' . esc_html__( '(optional)', 'give' ) . '</span>';
				} ?></legend>
			<?php do_action( 'give_register_account_fields_before', $form_id ); ?>
			<div id="give-user-login-wrap-<?php echo $form_id; ?>" class="form-row form-row-one-third form-row-first">
				<label for="give-user-login-<?php echo $form_id; ?>">
					<?php esc_html_e( 'Username', 'give' ); ?>
					<?php if ( give_logged_in_only( $form_id ) ) { ?>
						<span class="give-required-indicator">*</span>
					<?php } ?>
					<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'The username you will use to log into your account.', 'give' ); ?>"></span>
				</label>

				<input name="give_user_login" id="give-user-login-<?php echo $form_id; ?>" class="<?php if ( give_logged_in_only( $form_id ) ) {
					echo 'required ';
				} ?>give-input" type="text" placeholder="<?php esc_html_e( 'Username', 'give' ); ?>" title="<?php esc_html_e( 'Username', 'give' ); ?>"/>
			</div>

			<div id="give-user-pass-wrap-<?php echo $form_id; ?>" class="form-row form-row-one-third">
				<label for="give-user-pass-<?php echo $form_id; ?>">
					<?php esc_html_e( 'Password', 'give' ); ?>
					<?php if ( give_logged_in_only( $form_id ) ) { ?>
						<span class="give-required-indicator">*</span>
					<?php } ?>
					<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'The password used to access your account.', 'give' ); ?>"></span>
				</label>

				<input name="give_user_pass" id="give-user-pass-<?php echo $form_id; ?>" class="<?php if ( give_logged_in_only( $form_id ) ) {
					echo 'required ';
				} ?>give-input" placeholder="<?php esc_html_e( 'Password', 'give' ); ?>" type="password"/>
			</div>

			<div id="give-user-pass-confirm-wrap-<?php echo $form_id; ?>" class="give-register-password form-row form-row-one-third">
				<label for="give-user-pass-confirm-<?php echo $form_id; ?>">
					<?php esc_html_e( 'Confirm PW', 'give' ); ?>
					<?php if ( give_logged_in_only( $form_id ) ) { ?>
						<span class="give-required-indicator">*</span>
					<?php } ?>
					<span class="give-tooltip give-icon give-icon-question" data-tooltip="<?php esc_html_e( 'Please retype your password to confirm.', 'give' ); ?>"></span>
				</label>

				<input name="give_user_pass_confirm" id="give-user-pass-confirm-<?php echo $form_id; ?>" class="<?php if ( give_logged_in_only( $form_id ) ) {
					echo 'required ';
				} ?>give-input" placeholder="<?php esc_html_e( 'Confirm password', 'give' ); ?>" type="password"/>
			</div>
			<?php do_action( 'give_register_account_fields_after', $form_id ); ?>
		</fieldset>

		<?php do_action( 'give_register_fields_after', $form_id ); ?>

		<input type="hidden" name="give-purchase-var" value="needs-to-register"/>

		<?php do_action( 'give_purchase_form_user_info', $form_id ); ?>

	</fieldset>
	<?php
	echo ob_get_clean();
}

add_action( 'give_purchase_form_register_fields', 'give_get_register_fields' );

/**
 * Gets the login fields for the login form on the checkout. This function hooks
 * on the give_purchase_form_login_fields to display the login form if a user already
 * had an account.
 *
 * @since  1.0
 *
 * @param  int $form_id
 *
 * @return string
 */
function give_get_login_fields( $form_id ) {

	$form_id            = isset( $_POST['form_id'] ) ? $_POST['form_id'] : $form_id;
	$show_register_form = give_show_login_register_option( $form_id );

	ob_start();
	?>
	<fieldset id="give-login-fields-<?php echo $form_id; ?>">
		<legend><?php echo apply_filters( 'give_account_login_fieldset_heading', esc_html__( 'Login to Your Account', 'give' ) );
			if ( ! give_logged_in_only( $form_id ) ) {
				echo ' <span class="sub-text">' . esc_html__( '(optional)', 'give' ) . '</span>';
			} ?>
		</legend>
		<?php if ( $show_register_form == 'both' ) { ?>
			<p class="give-new-account-link">
				<?php esc_html_e( 'Need to create an account?', 'give' ); ?>&nbsp;
				<a href="<?php echo remove_query_arg( 'login' ); ?>" class="give-checkout-register-cancel" data-action="give_checkout_register">
					<?php esc_html_e( 'Register', 'give' );
					if ( ! give_logged_in_only( $form_id ) ) {
						echo ' ' . esc_html__( 'or checkout as a guest &raquo;', 'give' );
					} ?>
				</a>
			</p>
			<p class="give-loading-text">
				<span class="give-loading-animation"></span> <?php esc_html_e( 'Loading...', 'give' ); ?> </p>
		<?php } ?>
		<?php do_action( 'give_checkout_login_fields_before', $form_id ); ?>
		<div id="give-user-login-wrap-<?php echo $form_id; ?>" class="form-row form-row-first">
			<label class="give-label" for="give-user-login-<?php echo $form_id; ?>">
				<?php esc_html_e( 'Username', 'give' ); ?>
				<?php if ( give_logged_in_only( $form_id ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
			</label>

			<input class="<?php if ( give_logged_in_only( $form_id ) ) {
				echo 'required ';
			} ?>give-input" type="text" name="give_user_login" id="give-user-login-<?php echo $form_id; ?>" value="" placeholder="<?php esc_html_e( 'Your username', 'give' ); ?>"/>
		</div>

		<div id="give-user-pass-wrap-<?php echo $form_id; ?>" class="give_login_password form-row form-row-last">
			<label class="give-label" for="give-user-pass-<?php echo $form_id; ?>">
				<?php esc_html_e( 'Password', 'give' ); ?>
				<?php if ( give_logged_in_only( $form_id ) ) { ?>
					<span class="give-required-indicator">*</span>
				<?php } ?>
			</label>
			<input class="<?php if ( give_logged_in_only( $form_id ) ) {
				echo 'required ';
			} ?>give-input" type="password" name="give_user_pass" id="give-user-pass-<?php echo $form_id; ?>" placeholder="<?php esc_html_e( 'Your password', 'give' ); ?>"/>
			<input type="hidden" name="give-purchase-var" value="needs-to-login"/>
		</div>

		<div id="give-forgot-password-wrap-<?php echo $form_id; ?>" class="give_login_forgot_password">
			 <span class="give-forgot-password ">
				 <a href="<?php echo wp_lostpassword_url() ?>" target="_blank"><?php esc_html_e( 'Reset password?' ) ?></a>
			 </span>
		</div>

		<div id="give-user-login-submit-<?php echo $form_id; ?>" class="give-clearfix">
			<input type="submit" class="give-submit give-btn button" name="give_login_submit" value="<?php esc_html_e( 'Login', 'give' ); ?>"/>
			<?php if ( $show_register_form !== 'login' ) { ?>
				<input type="button" data-action="give_cancel_login" class="give-cancel-login give-checkout-register-cancel give-btn button" name="give_login_cancel" value="<?php esc_html_e( 'Cancel', 'give' ); ?>"/>
			<?php } ?>
			<span class="give-loading-animation"></span>
		</div>
		<?php do_action( 'give_checkout_login_fields_after', $form_id ); ?>
	</fieldset><!--end #give-login-fields-->
	<?php
	echo ob_get_clean();
}

add_action( 'give_purchase_form_login_fields', 'give_get_login_fields', 10, 1 );

/**
 * Payment Mode Select
 *
 * Renders the payment mode form by getting all the enabled payment gateways and
 * outputting them as radio buttons for the user to choose the payment gateway. If
 * a default payment gateway has been chosen from the Give Settings, it will be
 * automatically selected.
 *
 * @since  1.0
 *
 * @param  int $form_id
 *
 * @return void
 */
function give_payment_mode_select( $form_id ) {

	$gateways = give_get_enabled_payment_gateways();

	do_action( 'give_payment_mode_top', $form_id ); ?>

	<fieldset id="give-payment-mode-select">
		<?php do_action( 'give_payment_mode_before_gateways_wrap' ); ?>
		<div id="give-payment-mode-wrap">
			<legend class="give-payment-mode-label"><?php echo apply_filters( 'give_checkout_payment_method_text', esc_html__( 'Select Payment Method', 'give' ) ); ?>
				<span class="give-loading-text"><span class="give-loading-animation"></span> <?php esc_html_e( 'Loading...', 'give' ); ?></span>
			</legend>
			<?php

			do_action( 'give_payment_mode_before_gateways' ) ?>

			<ul id="give-gateway-radio-list">
				<?php foreach ( $gateways as $gateway_id => $gateway ) :
					$checked       = checked( $gateway_id, give_get_default_gateway( $form_id ), false );
					$checked_class = $checked ? ' give-gateway-option-selected' : '';
					echo '<li><label for="give-gateway-' . esc_attr( $gateway_id ) . '-' . $form_id . '" class="give-gateway-option' . $checked_class . '" id="give-gateway-option-' . esc_attr( $gateway_id ) . '">';
					echo '<input type="radio" name="payment-mode" class="give-gateway" id="give-gateway-' . esc_attr( $gateway_id ) . '-' . $form_id . '" value="' . esc_attr( $gateway_id ) . '"' . $checked . '>' . esc_html( $gateway['checkout_label'] );
					echo '</label></li>';
				endforeach; ?>
			</ul>
			<?php do_action( 'give_payment_mode_after_gateways' ); ?>
		</div>
		<?php do_action( 'give_payment_mode_after_gateways_wrap' ); ?>
	</fieldset>

	<?php do_action( 'give_payment_mode_bottom', $form_id ); ?>

	<div id="give_purchase_form_wrap">

		<?php do_action( 'give_purchase_form', $form_id ); ?>

	</div><!-- the checkout fields are loaded into this-->

	<?php do_action( 'give_purchase_form_wrap_bottom', $form_id );

}

add_action( 'give_payment_mode_select', 'give_payment_mode_select' );


/**
 * Renders the Checkout Agree to Terms, this displays a checkbox for users to
 * agree the T&Cs set in the Give Settings. This is only displayed if T&Cs are
 * set in the Give Settings.
 *
 * @since  1.0
 *
 * @param  int   $form_id
 *
 * @return void
 */
function give_terms_agreement( $form_id ) {

	$form_option = get_post_meta( $form_id, '_give_terms_option', true );
	$label       = get_post_meta( $form_id, '_give_agree_label', true );
	$terms       = get_post_meta( $form_id, '_give_agree_text', true );

	if ( $form_option === 'yes' && ! empty( $terms ) ) { ?>
		<fieldset id="give_terms_agreement">
			<div id="give_terms" class= "give_terms-<?php echo $form_id;?>" style="display:none;">
				<?php
				do_action( 'give_before_terms' );
				echo wpautop( stripslashes( $terms ) );
				do_action( 'give_after_terms' );
				?>
			</div>
			<div id="give_show_terms">
				<a href="#" class="give_terms_links give_terms_links-<?php echo $form_id;?>"><?php esc_html_e( 'Show Terms', 'give' ); ?></a>
				<a href="#" class="give_terms_links give_terms_links-<?php echo $form_id;?>" style="display:none;"><?php esc_html_e( 'Hide Terms', 'give' ); ?></a>
			</div>

			<input name="give_agree_to_terms" class="required" type="checkbox" id="give_agree_to_terms" value="1"/>
			<label
				for="give_agree_to_terms"><?php echo ! empty( $label ) ? stripslashes( $label ) : esc_html__( 'Agree to Terms?', 'give' ); ?></label>

		</fieldset>
		<?php
	}
}

add_action( 'give_purchase_form_before_submit', 'give_terms_agreement', 10, 1 );

/**
 * Checkout Final Total
 *
 * Shows the final purchase total at the bottom of the checkout page
 *
 * @since  1.0
 *
 * @param int   $form_id
 *
 * @return void
 */
function give_checkout_final_total( $form_id ) {

	if ( isset( $_POST['give_total'] ) ) {
		$total = apply_filters( 'give_donation_total', $_POST['give_total'] );
	} else {
		//default total
		$total = give_get_default_form_amount( $form_id );
	}
	//Only proceed if give_total available
	if ( empty( $total ) ) {
		return;
	}
	?>
	<p id="give-final-total-wrap" class="form-wrap ">
		<span class="give-donation-total-label"><?php echo apply_filters( 'give_donation_total_label', esc_html__( 'Donation Total:', 'give' ) ); ?></span>
		<span class="give-final-total-amount" data-total="<?php echo give_format_amount( $total ); ?>"><?php echo give_currency_filter( give_format_amount( $total ) ); ?></span>
	</p>
	<?php
}

add_action( 'give_purchase_form_before_submit', 'give_checkout_final_total', 999 );


/**
 * Renders the Checkout Submit section
 *
 * @since  1.0
 *
 * @param  int $form_id
 *
 * @return void
 */
function give_checkout_submit( $form_id ) {
	?>
	<fieldset id="give_purchase_submit">
		<?php do_action( 'give_purchase_form_before_submit', $form_id ); ?>

		<?php give_checkout_hidden_fields( $form_id ); ?>

		<?php echo give_checkout_button_purchase( $form_id ); ?>

		<?php do_action( 'give_purchase_form_after_submit', $form_id ); ?>

	</fieldset>
	<?php
}

add_action( 'give_purchase_form_after_cc_form', 'give_checkout_submit', 9999 );


/**
 * Give Checkout Button Purchase
 *
 * Renders the Purchase button on the Checkout
 * @since  1.0
 *
 * @param  int $form_id
 *
 * @return string
 */
function give_checkout_button_purchase( $form_id ) {

	$display_label_field = get_post_meta( $form_id, '_give_checkout_label', true );
	$display_label       = ( ! empty( $display_label_field ) ? $display_label_field : esc_html__( 'Donate Now', 'give' ) );
	ob_start(); ?>
	<div class="give-submit-button-wrap give-clearfix">
		<input type="submit" class="give-submit give-btn" id="give-purchase-button" name="give-purchase" value="<?php echo $display_label; ?>"/>
		<span class="give-loading-animation"></span>
	</div>
	<?php
	return apply_filters( 'give_checkout_button_purchase', ob_get_clean(), $form_id );
}

/**
 * Give Agree to Terms
 *
 * Outputs the JavaScript code for the Agree to Terms section to toggle the T&Cs text
 * @since  1.0
 *
 * @param  int $form_id
 *
 * @return void
 */
function give_agree_to_terms_js( $form_id ) {

	$form_option = get_post_meta( $form_id, '_give_terms_option', true );

	if ( $form_option === 'yes' ) {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$('body').on('click', '.give_terms_links-<?php echo $form_id;?>', function (e) {
					e.preventDefault();
					$('.give_terms-<?php echo $form_id;?>').slideToggle();
					$('.give_terms_links-<?php echo $form_id;?>').toggle();
					return false;
				});
			});
		</script>
		<?php
	}
}

add_action( 'give_checkout_form_top', 'give_agree_to_terms_js', 10, 2 );

/**
 * Show Give Goals
 *
 * @since  1.0
 * @since  1.6   Add template for Give Goals Shortcode.
 *               More info is on https://github.com/WordImpress/Give/issues/411
 *
 * @param  int   $form_id
 * @param  array $args
 *
 * @return mixed
 */

function give_show_goal_progress( $form_id, $args ) {

    ob_start();
    give_get_template( 'shortcode-goal' , array( 'form_id' => $form_id, 'args' => $args ) );

    echo apply_filters( 'give_goal_output', ob_get_clean() );

	return true;
}

add_action( 'give_pre_form', 'give_show_goal_progress', 10, 2 );

/**
 * Adds Actions to Render Form Content
 *
 * @since  1.0
 *
 * @param  int   $form_id
 * @param  array $args
 *
 * @return void
 */
function give_form_content( $form_id, $args ) {

	$show_content = ( isset( $args['show_content'] ) && ! empty( $args['show_content'] ) )
		? $args['show_content']
		: get_post_meta( $form_id, '_give_content_option', true );

	if ( $show_content !== 'none' ) {
		//add action according to value
		add_action( $show_content, 'give_form_display_content', 10, 2 );
	}
}

add_action( 'give_pre_form_output', 'give_form_content', 10, 2 );

/**
 * Renders Post Form Content
 *
 * Displays content for Give forms; fired by action from give_form_content
 *
 * @param int $form_id
 *
 * @return void
 * @since      1.0
 */
function give_form_display_content( $form_id, $args ) {

	$content      = wpautop( get_post_meta( $form_id, '_give_form_content', true ) );
	$show_content = ( isset( $args['show_content'] ) && ! empty( $args['show_content'] ) )
		? $args['show_content']
		: get_post_meta( $form_id, '_give_content_option', true );

	if ( give_get_option( 'disable_the_content_filter' ) !== 'on' ) {
		$content = apply_filters( 'the_content', $content );
	}

	$output = '<div id="give-form-content-' . $form_id . '" class="give-form-content-wrap" >' . $content . '</div>';

	echo apply_filters( 'give_form_content_output', $output );

	//remove action to prevent content output on addition forms on page
	//@see: https://github.com/WordImpress/Give/issues/634
	remove_action( $show_content, 'give_form_display_content' );
}


/**
 * Renders the hidden Checkout fields
 *
 * @since 1.0
 *
 * @param int $form_id
 *
 * @return void
 */
function give_checkout_hidden_fields( $form_id ) {

	do_action( 'give_hidden_fields_before', $form_id );
	if ( is_user_logged_in() ) { ?>
		<input type="hidden" name="give-user-id" value="<?php echo get_current_user_id(); ?>"/>
	<?php } ?>
	<input type="hidden" name="give_action" value="purchase"/>
	<input type="hidden" name="give-gateway" value="<?php echo give_get_chosen_gateway( $form_id ); ?>"/>
	<?php
	do_action( 'give_hidden_fields_after', $form_id );

}

/**
 * Filter Success Page Content
 *
 * Applies filters to the success page content.
 *
 * @since 1.0
 *
 * @param string $content Content before filters
 *
 * @return string $content Filtered content
 */
function give_filter_success_page_content( $content ) {

	global $give_options;

	if ( isset( $give_options['success_page'] ) && isset( $_GET['payment-confirmation'] ) && is_page( $give_options['success_page'] ) ) {
		if ( has_filter( 'give_payment_confirm_' . $_GET['payment-confirmation'] ) ) {
			$content = apply_filters( 'give_payment_confirm_' . $_GET['payment-confirmation'], $content );
		}
	}

	return $content;
}

add_filter( 'the_content', 'give_filter_success_page_content' );


/**
 * Test Mode Frontend Warning
 *
 * Displays a notice on the frontend for donation forms
 * @since 1.1
 */

function give_test_mode_frontend_warning() {

	$test_mode = give_get_option( 'test_mode' );

	if ( $test_mode == 'on' ) {
		echo '<div class="give_error give_warning" id="give_error_test_mode"><p><strong>' . esc_html__( 'Notice', 'give' ) . '</strong>: ' . esc_html__( 'Test mode is enabled. While in test mode no live transactions are processed.', 'give' ) . '</p></div>';
	}
}

add_action( 'give_pre_form', 'give_test_mode_frontend_warning', 10 );


/**
 * Members-only Form
 *
 * If "Disable Guest Donations" and "Display Register / Login" is set to none
 *
 * @since  1.4.1
 *
 * @param  string $final_output
 * @param  array  $args
 *
 * @return string
 */

function give_members_only_form( $final_output, $args ) {

	$form_id = isset( $args['form_id'] ) ? $args['form_id'] : 0;

	//Sanity Check: Must have form_id & not be logged in
	if ( empty( $form_id ) || is_user_logged_in() ) {
		return $final_output;
	}

	//Logged in only and Register / Login set to none
	if ( give_logged_in_only( $form_id ) && give_show_login_register_option( $form_id ) == 'none' ) {

		$final_output = give_output_error( esc_html__( 'Please log in in order to complete your donation.', 'give' ), false );

		return apply_filters( 'give_members_only_output', $final_output, $form_id );

	}

	return $final_output;

}

add_filter( 'give_donate_form', 'give_members_only_form', 10, 2 );