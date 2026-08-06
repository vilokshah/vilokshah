<?php
/**
 * Footer and theme widgets.
 *
 * @package ManualDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contacts column: office address + social links.
 */
class Manual_Docs_Contacts_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'manual_docs_contacts',
			__( 'Manual Docs: Contacts', 'manual-docs' ),
			array(
				'description'                 => __( 'Footer contacts block with office address and social icons.', 'manual-docs' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Front-end output.
	 *
	 * @param array $args     Widget args.
	 * @param array $instance Instance.
	 */
	public function widget( $args, $instance ) {
		$title   = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Contacts', 'manual-docs' );
		$office  = ! empty( $instance['office'] ) ? $instance['office'] : __( 'Head Office', 'manual-docs' );
		$address = ! empty( $instance['address'] ) ? $instance['address'] : '';

		$networks = array(
			'x'         => array( 'label' => 'X', 'url' => ! empty( $instance['url_x'] ) ? $instance['url_x'] : '' ),
			'linkedin'  => array( 'label' => 'LinkedIn', 'url' => ! empty( $instance['url_linkedin'] ) ? $instance['url_linkedin'] : '' ),
			'youtube'   => array( 'label' => 'YouTube', 'url' => ! empty( $instance['url_youtube'] ) ? $instance['url_youtube'] : '' ),
			'facebook'  => array( 'label' => 'Facebook', 'url' => ! empty( $instance['url_facebook'] ) ? $instance['url_facebook'] : '' ),
			'instagram' => array( 'label' => 'Instagram', 'url' => ! empty( $instance['url_instagram'] ) ? $instance['url_instagram'] : '' ),
		);

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
		<div class="md-footer-contacts">
			<?php if ( $office ) : ?>
				<p class="md-footer-contacts__office"><?php echo esc_html( $office ); ?></p>
			<?php endif; ?>
			<?php if ( $address ) : ?>
				<div class="md-footer-contacts__address"><?php echo nl2br( esc_html( $address ) ); ?></div>
			<?php endif; ?>
			<ul class="md-footer-social" aria-label="<?php esc_attr_e( 'Social links', 'manual-docs' ); ?>">
				<?php foreach ( $networks as $key => $net ) : ?>
					<?php if ( empty( $net['url'] ) ) { continue; } ?>
					<li>
						<a class="md-footer-social__link md-footer-social__link--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $net['url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<span class="screen-reader-text"><?php echo esc_html( $net['label'] ); ?></span>
							<?php echo manual_docs_social_icon_svg( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Admin form.
	 *
	 * @param array $instance Instance.
	 */
	public function form( $instance ) {
		$fields = array(
			'title'         => array( 'label' => __( 'Title', 'manual-docs' ), 'default' => __( 'Contacts', 'manual-docs' ) ),
			'office'        => array( 'label' => __( 'Office label', 'manual-docs' ), 'default' => __( 'Head Office', 'manual-docs' ) ),
			'address'       => array( 'label' => __( 'Address', 'manual-docs' ), 'default' => '', 'type' => 'textarea' ),
			'url_x'         => array( 'label' => __( 'X / Twitter URL', 'manual-docs' ), 'default' => '' ),
			'url_linkedin'  => array( 'label' => __( 'LinkedIn URL', 'manual-docs' ), 'default' => '' ),
			'url_youtube'   => array( 'label' => __( 'YouTube URL', 'manual-docs' ), 'default' => '' ),
			'url_facebook'  => array( 'label' => __( 'Facebook URL', 'manual-docs' ), 'default' => '' ),
			'url_instagram' => array( 'label' => __( 'Instagram URL', 'manual-docs' ), 'default' => '' ),
		);
		foreach ( $fields as $key => $field ) {
			$val = isset( $instance[ $key ] ) ? $instance[ $key ] : $field['default'];
			$id  = $this->get_field_id( $key );
			$name = $this->get_field_name( $key );
			echo '<p><label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';
			if ( ! empty( $field['type'] ) && 'textarea' === $field['type'] ) {
				echo '<textarea class="widefat" rows="4" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $val ) . '</textarea>';
			} else {
				echo '<input class="widefat" type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '" />';
			}
			echo '</p>';
		}
	}

	/**
	 * Save.
	 *
	 * @param array $new_instance New.
	 * @param array $old_instance Old.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		foreach ( array( 'title', 'office', 'address', 'url_x', 'url_linkedin', 'url_youtube', 'url_facebook', 'url_instagram' ) as $key ) {
			if ( 'address' === $key ) {
				$instance[ $key ] = isset( $new_instance[ $key ] ) ? sanitize_textarea_field( $new_instance[ $key ] ) : '';
			} elseif ( 0 === strpos( $key, 'url_' ) ) {
				$instance[ $key ] = isset( $new_instance[ $key ] ) ? esc_url_raw( $new_instance[ $key ] ) : '';
			} else {
				$instance[ $key ] = isset( $new_instance[ $key ] ) ? sanitize_text_field( $new_instance[ $key ] ) : '';
			}
		}
		return $instance;
	}
}

/**
 * Newsletter / Stay Connected column.
 */
class Manual_Docs_Newsletter_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'manual_docs_newsletter',
			__( 'Manual Docs: Newsletter', 'manual-docs' ),
			array(
				'description'                 => __( 'Stay Connected email subscribe form for the footer.', 'manual-docs' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Front-end.
	 *
	 * @param array $args     Args.
	 * @param array $instance Instance.
	 */
	public function widget( $args, $instance ) {
		$title        = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Stay Connected', 'manual-docs' );
		$action       = ! empty( $instance['action_url'] ) ? $instance['action_url'] : '';
		$privacy_url  = ! empty( $instance['privacy_url'] ) ? $instance['privacy_url'] : '';
		$privacy_text = ! empty( $instance['privacy_text'] ) ? $instance['privacy_text'] : __( 'By continuing, I agree to the data privacy notice', 'manual-docs' );
		$button       = ! empty( $instance['button_text'] ) ? $instance['button_text'] : __( 'Subscribe', 'manual-docs' );
		$email_name   = ! empty( $instance['email_field'] ) ? $instance['email_field'] : 'EMAIL';

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$form_action = $action ? $action : '#';
		?>
		<form class="md-footer-newsletter" method="post" action="<?php echo esc_url( $form_action ); ?>" target="_blank" novalidate>
			<label class="md-footer-newsletter__label" for="<?php echo esc_attr( $this->get_field_id( 'email_front' ) ); ?>">
				<?php esc_html_e( 'Email', 'manual-docs' ); ?><span aria-hidden="true">*</span>
			</label>
			<input
				class="md-footer-newsletter__input"
				type="email"
				id="<?php echo esc_attr( $this->get_field_id( 'email_front' ) ); ?>"
				name="<?php echo esc_attr( $email_name ); ?>"
				required
				autocomplete="email"
				placeholder="<?php esc_attr_e( 'you@company.com', 'manual-docs' ); ?>"
			/>
			<label class="md-footer-newsletter__consent">
				<input type="checkbox" name="md_privacy_agree" value="1" required />
				<span>
					<?php
					if ( $privacy_url ) {
						echo wp_kses(
							sprintf(
								/* translators: %s: privacy policy link */
								__( 'By continuing, I agree to the %s', 'manual-docs' ),
								'<a href="' . esc_url( $privacy_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'data privacy notice', 'manual-docs' ) . '</a>'
							),
							array(
								'a' => array(
									'href'   => true,
									'target' => true,
									'rel'    => true,
								),
							)
						);
					} else {
						echo esc_html( $privacy_text );
					}
					?>
				</span>
			</label>
			<button type="submit" class="md-footer-newsletter__submit"><?php echo esc_html( $button ); ?></button>
			<?php if ( empty( $action ) ) : ?>
				<p class="md-footer-newsletter__hint"><?php esc_html_e( 'Set a form action URL in the widget (Mailchimp, HubSpot, etc.) to enable subscriptions.', 'manual-docs' ); ?></p>
			<?php endif; ?>
		</form>
		<?php
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Admin form.
	 *
	 * @param array $instance Instance.
	 */
	public function form( $instance ) {
		$fields = array(
			'title'         => __( 'Title', 'manual-docs' ),
			'action_url'    => __( 'Form action URL (Mailchimp / ESP endpoint)', 'manual-docs' ),
			'email_field'   => __( 'Email field name', 'manual-docs' ),
			'privacy_url'   => __( 'Privacy policy URL', 'manual-docs' ),
			'privacy_text'  => __( 'Consent text (if no privacy URL)', 'manual-docs' ),
			'button_text'   => __( 'Button text', 'manual-docs' ),
		);
		$defaults = array(
			'title'        => __( 'Stay Connected', 'manual-docs' ),
			'action_url'   => '',
			'email_field'  => 'EMAIL',
			'privacy_url'  => '',
			'privacy_text' => __( 'By continuing, I agree to the data privacy notice', 'manual-docs' ),
			'button_text'  => __( 'Subscribe', 'manual-docs' ),
		);
		foreach ( $fields as $key => $label ) {
			$val = isset( $instance[ $key ] ) ? $instance[ $key ] : $defaults[ $key ];
			printf(
				'<p><label for="%1$s">%2$s</label><input class="widefat" type="text" id="%1$s" name="%3$s" value="%4$s" /></p>',
				esc_attr( $this->get_field_id( $key ) ),
				esc_html( $label ),
				esc_attr( $this->get_field_name( $key ) ),
				esc_attr( $val )
			);
		}
	}

	/**
	 * Save.
	 *
	 * @param array $new_instance New.
	 * @param array $old_instance Old.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		return array(
			'title'        => isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
			'action_url'   => isset( $new_instance['action_url'] ) ? esc_url_raw( $new_instance['action_url'] ) : '',
			'email_field'  => isset( $new_instance['email_field'] ) ? sanitize_key( $new_instance['email_field'] ) : 'EMAIL',
			'privacy_url'  => isset( $new_instance['privacy_url'] ) ? esc_url_raw( $new_instance['privacy_url'] ) : '',
			'privacy_text' => isset( $new_instance['privacy_text'] ) ? sanitize_text_field( $new_instance['privacy_text'] ) : '',
			'button_text'  => isset( $new_instance['button_text'] ) ? sanitize_text_field( $new_instance['button_text'] ) : '',
		);
	}
}

/**
 * Social icon SVG markup.
 *
 * @param string $network Network key.
 * @return string
 */
function manual_docs_social_icon_svg( $network ) {
	$icons = array(
		'x'         => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 5l14 14M19 5L5 19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
		'linkedin'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="M7 10v7M7 7v.01M11 17v-4.5a2.5 2.5 0 015 0V17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
		'youtube'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="3" stroke="currentColor" stroke-width="2"/><path d="M10 9.5l6 2.5-6 2.5V9.5z" fill="currentColor"/></svg>',
		'facebook'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 8h3V5h-3a4 4 0 00-4 4v2H8v3h2v5h3v-5h3l1-3h-4V9a1 1 0 011-1z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
		'instagram' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>',
	);
	return isset( $icons[ $network ] ) ? $icons[ $network ] : '';
}

/**
 * Register theme widgets.
 */
function manual_docs_register_widgets() {
	register_widget( 'Manual_Docs_Contacts_Widget' );
	register_widget( 'Manual_Docs_Newsletter_Widget' );
}
add_action( 'widgets_init', 'manual_docs_register_widgets' );

/**
 * Whether any of the four footer columns has widgets.
 *
 * @return bool
 */
function manual_docs_has_footer_columns() {
	foreach ( array( 'footer-1', 'footer-2', 'footer-3', 'footer-4' ) as $id ) {
		if ( is_active_sidebar( $id ) ) {
			return true;
		}
	}
	return false;
}
