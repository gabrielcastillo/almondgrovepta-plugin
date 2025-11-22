<?php
	/*
	 * File: class-agpta-shopping-cart.php
	 *
	 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
	 * Copyright (c) 2025.
	 */


class AGPTA_ShoppingCart {


	/**
	 * Plugin Name
	 *
	 * @var string
	 */
	protected string $plugin_name;

	/**
	 * Plugin Version
	 *
	 * @var string
	 */
	protected string $plugin_version;

	/**
	 *  Database WPDB object
	 *
	 * @var object
	 */
	private object $db;

	/**
	 * WP Settings array
	 *
	 * @var array|false|mixed|void
	 */
	private array $options;

	/**
	 * Cart Total
	 *
	 * @var string|int
	 */
	private string $cart_total;

	/**
	 * Constructor
	 *
	 * @param string $plugin_name plugin name.
	 * @param string $plugin_version plugin version number.
	 * @param object $wpdb wp database object.
	 */
	public function __construct( string $plugin_name, string $plugin_version, object $wpdb ) {

		$this->plugin_name    = $plugin_name;
		$this->plugin_version = $plugin_version;
		$this->db             = $wpdb;
		$this->options        = get_option( 'agpta_settings', array() );
		$this->cart_total     = 0;
	}

	/**
	 * Add To Cart Shortcode
	 *
	 * @param array $atts shortcode params.
	 *
	 * @return bool|array|string
	 */
	public function agpta_add_to_cart_shortcode( array $atts ): bool|array|string {

		$atts = shortcode_atts(
			array(
				'product_id' => '',
			),
			$atts,
			'agpta_add_to_cart'
		);

		if ( empty( $atts['product_id'] ) ) {
			return array();
		}

		$event_id     = $atts['product_id'];
		$event_title  = get_the_title( $event_id );
		$ticket_price = get_post_meta(
			$event_id,
			'_agpta_event_price',
			true
		); // Assuming ticket price is stored in post meta.
		$ticket_price = $ticket_price ? $ticket_price : 0; // Fallback price if not set.

		$this->cart_total = $this->cart_total + $ticket_price / 100;

		// The form HTML with TailwindCSS classes.
		ob_start();
		?>
			<div class="event-ticket-form max-w-md mx-auto p-4 bg-white border border-gray-300 rounded-lg shadow-lg">
				<h2 class="text-xl font-semibold text-center mb-4"><?php echo esc_html( $event_title ); ?></h2>
				<p class="text-center text-lg mb-4">Price: $<?php echo number_format( $ticket_price, 2 ); ?></p>

				<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-4">
					<input type="hidden" name="action" value="agpta_add_ticket_to_cart">
					<input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>">

					<!-- Quantity Selector -->
					<div class="flex items-center justify-between">
						<label for="quantity" class="text-lg">Quantity:</label>
						<input type="number" name="qty" id="quantity" value="1" min="1"
								class="w-16 px-3 py-2 border border-gray-300 rounded-md" required>
					</div>

					<!-- Add to Cart Button -->
					<div class="text-center">
						<button type="submit"
								class="w-full bg-red-700 hover:bg-red-500 text-white py-2 rounded-md font-semibold">
							Add to Cart
						</button>
					</div>
				</form>
			</div>
			<?php
			return ob_get_clean();
	}

	/**
	 * Display Cart Shortcode
	 *
	 * @return false|string
	 */
	public function agpta_display_cart_page(): bool|string {

		// Check if the cart is empty.
		if ( empty( $_SESSION['cart'] ) ) {
			return '<p>Your cart is empty.</p>';
		}

		$cart = $_SESSION['cart'];

		ob_start();
		?>
			<div class="bg-white">
				<div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 sm:py-24 lg:px-0">
					<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-4">
					<?php wp_nonce_field( 'agpta_cart_nonce', 'agpta_nonce' ); ?>
						<input type="hidden" name="action" value="agpta_update_cart_items">
						<section aria-labelledby="cart-heading">
							<h2 id="cart-heading" class="sr-only">Items in your shopping cart</h2>

							<ul role="list" class="divide-y divide-gray-200 border-b border-t border-gray-200">
							<?php foreach ( $cart as $key => $item ) : ?>
									<?php
									$event = get_post( $item['event_id'] );

									$event_title       = esc_html( $event->post_title );
									$event_excerpt     = esc_html( $event->post_excerpt );
									$quantity          = $item['qty'];
									$price             = number_format( ( $item['price'] * $quantity ), 2 );
									$this->cart_total += (float) $price;
									?>
									<li class="flex py-6">
										<div class="shrink-0">
											<img src="https://tailwindcss.com/plus-assets/img/ecommerce-images/checkout-page-03-product-04.jpg"
												alt="Front side of mint cotton t-shirt with wavey lines pattern."
												class="size-24 rounded-md object-cover sm:size-32"/>
										</div>

										<div class="ml-4 flex flex-1 flex-col sm:ml-6">
											<div>
												<div class="flex justify-between">
													<h4 class="text-sm">
														<a href="<?php echo esc_attr( esc_url( get_permalink( $event ) ) ); ?>"
															title="Goto: <?php echo esc_attr( $event_title ); ?>"
															class="font-medium text-gray-700 hover:text-gray-800"><?php echo esc_html( $event_title ); ?>
															(x<?php echo esc_html( $quantity ); ?>)</a>
													</h4>
													<p class="ml-4 text-sm font-medium text-gray-900">Price:
														$<?php echo esc_html( $price ); ?></p>
												</div>
												<p class="mt-1 text-sm text-gray-500">
													<?php echo esc_html( $event_excerpt ); ?>
												</p>
											</div>

											<div class="mt-4 flex flex-1 items-end justify-between">
												<p class="flex items-center space-x-2 text-sm text-gray-700">
													<label for="qty[<?php echo esc_html( $key ); ?>]" class=""><span>Qty:</span></label>
													<input id="qty[<?php echo esc_html( $key ); ?>]" type="number" name="qty[<?php echo esc_html( $key ); ?>]"
															value="<?php echo esc_html( $quantity ); ?>" min="1"
															class="w-16 px-2.5 py-2 border border-gray-300 rounded-md">
													<button type="submit" name="update[<?php echo esc_html( $key ); ?>]"
															class="button-default-red">
														Update
													</button>
												</p>
												<div class="ml-4">
													<button type="submit" name="remove[<?php echo esc_html( $key ); ?>]"
															class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
														<span>Remove</span>
													</button>
												</div>
											</div>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>

						<!-- Order summary -->
						<section aria-labelledby="summary-heading" class="mt-10">
							<h2 id="summary-heading" class="sr-only">Order summary</h2>

							<div>
								<dl class="space-y-4">
									<div class="flex items-center justify-between">
										<dt class="text-base font-medium text-gray-900">Subtotal</dt>
										<dd class="ml-4 text-base font-medium text-gray-900">$<?php echo number_format( $this->cart_total, 2 ); ?></dd>
									</div>
								</dl>
								<p class="mt-1 text-sm text-gray-500">Shipping and taxes will be calculated at
									checkout.</p>
							</div>

							<div class="mt-10">
								<a href="<?php echo esc_url( home_url( '/checkout' ) ); ?>"
										class="button-default-red block w-full text-center">
									Checkout
								</a>
							</div>

							<div class="mt-6 text-center text-sm">
								<p>
									or
									<a href="<?php echo esc_url( home_url( 'pta-events' ) ); ?>" class="font-medium text-red-600 hover:text-red-500">
										Continue Shopping
										<span aria-hidden="true"> &rarr;</span>
									</a>
								</p>
							</div>
						</section>
					</form>
				</div>
			</div>
			<?php
			return ob_get_clean();
	}

	/**
	 * Display Checkout Page
	 *
	 * @return bool|string
	 */
	public function agpta_display_checkout_page(): bool|string {

		if ( empty( $_SESSION['cart'] ) ) {
			return '<p>Your cart is empty. Please add items before proceeding.</p>';
		}

		$cart = $this->sanitize_session_array( $_SESSION['cart'] );

		// Calculate the total amount of the cart.
		$total_amount = 0;
		foreach ( $cart as $item ) {
			$total_amount += $item['price'] * $item['qty'];
		}

		ob_start();
		?>
			<div class="checkout-page max-w-lg mx-auto p-4">

				<div class="cart-summary mb-6">
					<h3 class="font-semibold text-lg">Cart Summary</h3>
					<ul>
					<?php foreach ( $cart as $item ) : ?>
							<?php
							$event       = get_post( $item['event_id'] );
							$event_title = esc_html( $event->post_title );
							$quantity    = $item['qty'];
							$price       = number_format( ( $item['price'] * $quantity ), 2 );
							?>
							<li class="flex justify-between mb-4">
								<span><?php echo esc_html( $event_title ); ?> (x<?php echo esc_html( $quantity ); ?>)</span>
								<span>$<?php echo esc_html( $price ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>

					<p class="font-semibold text-lg text-right">Total: $
					<?php
					echo number_format(
						$total_amount,
						2
					);
					?>
							</p>
				</div>

				<!-- Stripe Checkout Button -->
				<div class="text-center">
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
						<?php wp_nonce_field( 'agpta_checkout_nonce', 'agpta_nonce' ); ?>
						<input type="hidden" name="action" value="agpta_create_stripe_checkout_session">
						<button type="submit" class="bg-red-700 text-white py-1.5 px-4 rounded-md hover:bg-red-500">
							Proceed to Payment
						</button>
						<a class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-800"
							href="<?php echo esc_url( home_url( '/cart' ) ); ?>">Edit Cart</a>
					</form>
				</div>
			</div>
			<?php
			return ob_get_clean();
	}

	/**
	 * Add Ticket to Cart
	 *
	 * Uses ajax submission.
	 *
	 * @return false|void
	 */
	public function agpta_handle_add_ticket_to_cart() {

		if ( isset( $_POST['agpta_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['agpta_nonce'] ) ), 'agpta_checkout_nonce' ) ) {
			return false;
		}

		if ( isset( $_POST['event_id'], $_POST['qty'] ) ) {
			$event_id = absint( $_POST['event_id'] );
			$quantity = absint( $_POST['qty'] );

			// Fetch event data (e.g., price).
			$ticket_price = get_post_meta( $event_id, '_agpta_event_price', true );
			$ticket_price = $ticket_price ? $ticket_price : 0; // Default to $20 if not set.

			// Save the cart item in the session.
			$_SESSION['cart'][] = array(
				'event_id' => $event_id,
				'qty'      => $quantity,
				'price'    => $ticket_price,
			);
			// Redirect to cart page.
			wp_safe_redirect( home_url( '/cart' ) );
			exit;
		}

		wp_safe_redirect( home_url( '/events' ) ); // Redirect back to event page or show error.
		exit;
	}

	/**
	 * Update Cart Items
	 *
	 * Uses ajax form submission.
	 *
	 * @return void
	 */
	public function agpta_update_cart_items(): void {
		// @TODO: nonce verification needed.

		if ( ! empty( $_SESSION['cart'] ) ) {
			// ---- Update Quantities ----
			if ( isset( $_POST['qty'] ) && is_array( $_POST['qty'] ) ) {
				foreach ( $_POST['qty'] as $key => $new_qty ) {

					$new_qty = max( 1, absint( $new_qty ) );

					// Ensure the item exists AND is an array.
					if (
						isset( $_SESSION['cart'][ $key ] ) &&
						is_array( $_SESSION['cart'][ $key ] )
					) {

						// Ensure the item has a qty field.
						if ( ! isset( $_SESSION['cart'][ $key ]['qty'] ) ) {
							$_SESSION['cart'][ $key ]['qty'] = 1;
						}

						$_SESSION['cart'][ $key ]['qty'] = $new_qty;
					}
				}
			}

			if ( isset( $_POST['remove'] ) ) {
				foreach ( $_POST['remove'] as $key => $value ) {
					unset( $_SESSION['cart'][ $key ] );
				}

				$_SESSION['cart'] = array_values( $_SESSION['cart'] );
			}
		}

		wp_safe_redirect( home_url( '/cart' ) );
		exit;
	}



	/**
	 * Sanitize Session Data
	 *
	 * @param array $session_array user session array.
	 *
	 * @return array
	 */
	public function sanitize_session_array( $session_array ): array {
		$sanitized = array();

		foreach ( $session_array as $key => $value ) {
			// Sanitize the key (array keys must be safe too).
			$clean_key = sanitize_key( $key );

			if ( is_array( $value ) ) {
				// Recursively sanitize nested arrays.
				$sanitized[ $clean_key ] = $this->sanitize_session_array( $value );
			} elseif ( is_string( $value ) ) {
				// Sanitize strings.
				$sanitized[ $clean_key ] = sanitize_text_field( $value );
			} elseif ( is_numeric( $value ) ) {
				$sanitized[ $clean_key ] = (float) $value;
			} elseif ( is_bool( $value ) ) {
				$sanitized[ $clean_key ] = (bool) $value;
			} else {
				// Default: sanitize as plain text.
				$sanitized[ $clean_key ] = sanitize_text_field( (string) $value );
			}
		}

		return $sanitized;
	}
}