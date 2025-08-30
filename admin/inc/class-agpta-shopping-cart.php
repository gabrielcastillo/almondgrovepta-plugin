<?php
/*
 * File: class-agpta-shopping-cart.php
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */


class AGPTA_ShoppingCart {

	private string $plugin_name;

	private string $plugin_version;

	private object $db;

    private array $options;

	public function __construct( $plugin_name, $plugin_version, $wpdb ) {

		$this->plugin_name    = $plugin_name;
		$this->plugin_version = $plugin_version;
		$this->db             = $wpdb;
        $this->options        = get_option( 'agpta_settings', array() );
	}

	public function agpta_add_to_cart_shortcode( $atts ) {

		$atts = shortcode_atts(array(
                'product_id' => '',
        ), $atts, 'agpta_add_to_cart');

        if ( empty($atts['product_id']) ) {
            return;
        }

        $event_id = $atts['product_id'];
		$event_title  = get_the_title( $event_id );
		$ticket_price = get_post_meta( $event_id, '_agpta_event_price', true ); // Assuming ticket price is stored in post meta
		$ticket_price = $ticket_price ? $ticket_price : 0; // Fallback price if not set

		// The form HTML with TailwindCSS classes
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
                    <input type="number" name="qty" id="quantity" value="1" min="1" class="w-16 px-3 py-2 border border-gray-300 rounded-md" required>
                </div>

                <!-- Add to Cart Button -->
                <div class="text-center">
                    <button type="submit" class="w-full bg-red-700 hover:bg-red-500 text-white py-2 rounded-md font-semibold">
                        Add to Cart
                    </button>
                </div>
            </form>
        </div>
		<?php
		return ob_get_clean();
	}

	public function agpta_display_cart_page() {

		// Check if the cart is empty
		if ( empty( $_SESSION['cart'] ) ) {
			return '<p>Your cart is empty.</p>';
		}

		ob_start();
		?>
		<div class="cart-items">
			<h2 class="text-2xl font-semibold mb-4">Your Cart</h2>
			<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-4">
                <?php wp_nonce_field( 'agpta_cart_nonce', 'agpta_nonce' ); ?>
				<input type="hidden" name="action" value="agpta_update_cart_items">
				<?php foreach ( $_SESSION['cart'] as $key => $item ) : ?>
					<?php
					$event       = get_post( $item['event_id'] );

					$event_title = esc_html( $event->post_title );
					$quantity    = $item['qty'];
					$price       = number_format( ( $item['price'] * $quantity ), 2 );
					?>
					<div class="flex justify-between items-center mb-4">
						<div>
							<a href="<?php echo esc_attr( esc_url( get_permalink ( $event ) ) ); ?>" title="Goto: <?php echo esc_attr( $event_title ); ?>"><span class="block"><?php echo $event_title; ?> (x<?php echo $quantity; ?>)</span></a>
							<span class="block text-sm text-gray-500">Price: $<?php echo $price; ?></span>
						</div>

						<!-- Quantity Selector -->
						<div class="flex items-center space-x-2">
							<input type="number" name="qty[<?php echo $key; ?>]" value="<?php echo $quantity; ?>" min="1" class="w-16 px-3 py-2 border border-gray-300 rounded-md">
							<button type="submit" name="update[<?php echo $key; ?>]" class="bg-green-500 text-white py-1 px-3 rounded-md hover:bg-green-600">
								Update
							</button>
						</div>

						<!-- Remove Item -->
						<div>
							<button type="submit" name="remove[<?php echo $key; ?>]" class="bg-red-500 text-white py-1 px-3 rounded-md hover:bg-red-600">
								Remove
							</button>
						</div>
					</div>
				<?php endforeach; ?>

				<!-- Checkout Button -->
				<div class="mt-8 py-8  alignright">
					<a href="<?php echo esc_url( home_url( '/checkout' ) ); ?>" class="bg-red-700 text-white py-2 px-4 rounded-md hover:bg-red-500">Proceed to Checkout</a>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public function agpta_display_checkout_page() {

		if ( empty( $_SESSION['cart'] ) ) {
			return '<p>Your cart is empty. Please add items before proceeding.</p>';
		}

		// Calculate the total amount of the cart
		$total_amount = 0;
		foreach ( $_SESSION['cart'] as $item ) {
			$total_amount += $item['price'] * $item['qty'];
		}

		ob_start();
		?>
        <div class="checkout-page max-w-lg mx-auto p-4">

            <div class="cart-summary mb-6">
                <h3 class="font-semibold text-lg">Cart Summary</h3>
                <ul>
					<?php foreach ( $_SESSION['cart'] as $item ) : ?>
						<?php
						$event       = get_post( $item['event_id'] );
						$event_title = esc_html( $event->post_title );
						$quantity    = $item['qty'];
						$price       = number_format( ( $item['price'] * $quantity ), 2 );
						?>
                        <li class="flex justify-between mb-4">
                            <span><?php echo $event_title; ?> (x<?php echo $quantity; ?>)</span>
                            <span>$<?php echo $price; ?></span>
                        </li>
					<?php endforeach; ?>
                </ul>

                <p class="font-semibold text-lg text-right">Total: $<?php echo number_format( $total_amount, 2 ); ?></p>
            </div>

            <!-- Stripe Checkout Button -->
            <div class="text-center">
                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
                    <?php wp_nonce_field( 'agpta_checkout_nonce', 'agpta_nonce' ); ?>
                    <input type="hidden" name="action" value="agpta_create_stripe_checkout_session">
                    <button type="submit" class="bg-red-700 text-white py-1.5 px-4 rounded-md hover:bg-red-500">
                        Proceed to Payment
                    </button>
                    <a class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-800" href="<?php echo esc_url( home_url( '/cart' ) ); ?>">Edit Cart</a>
                </form>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}

	public function agpta_handle_add_ticket_to_cart() {

		if ( isset( $_POST['event_id'] ) && isset( $_POST['qty'] ) ) {
			$event_id = absint( $_POST['event_id'] );
			$quantity = absint( $_POST['qty'] );

			// Fetch event data (e.g., price)
			$ticket_price = get_post_meta( $event_id, '_agpta_event_price', true );
			$ticket_price = $ticket_price ? $ticket_price : 0; // Default to $20 if not set

			// Save the cart item in the session
			$_SESSION['cart'][] = array(
				'event_id' => $event_id,
				'qty'      => $quantity,
				'price'    => $ticket_price,
			);

			// Redirect to cart page
			wp_redirect( home_url( '/cart' ) );
			exit;
		} else {
			// Handle missing form data (optional)
			wp_redirect( home_url( '/events' ) ); // Redirect back to event page or show error
			exit;
		}
	}

	public function agpta_update_cart_items() {
		if ( ! empty( $_SESSION['cart'] ) ) {
			if ( isset( $_POST['qty'] ) ) {
				foreach ( $_POST['qty'] as $key => $new_qty ) {

					$new_qty = max( 1, absint( $new_qty ) );

					$_SESSION['cart'][ $key ]['qty'] = $new_qty;
				}
			}

			if ( isset( $_POST['remove'] ) ) {
				foreach ( $_POST['remove'] as $key => $value ) {
					unset( $_SESSION['cart'][ $key ] );
				}

				$_SESSION['cart'] = array_values( $_SESSION['cart'] );
			}
		}

		wp_redirect( home_url( '/cart' ) );
		exit;
	}

}