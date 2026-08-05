<?php
/**
 * Customizer control: the image-generation prompt for one slot.
 *
 * Read-only. It renders no setting of its own — it exists so the owner can see
 * what this photo position is for, what size it wants, and can copy a
 * ready-to-paste prompt into whichever image model they use.
 *
 * The prompt is built per country of operation (Image_Slots::prompt), so the
 * same control shows a US brief on the US site and a Canadian one on a
 * Canadian deployment without any per-site editing.
 *
 * Loaded lazily from Theme_Options::register(), because WP_Customize_Control
 * only exists once the Customizer has booted.
 *
 * @package PrepGro\Theme
 */

namespace PrepGro\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WP_Customize_Control' ) ) {
	return;
}

/**
 * A read-only line of guidance inside a section. Saves nothing; exists so a
 * control can point at a setting that lives elsewhere in the Customizer.
 */
class Customize_Note_Control extends \WP_Customize_Control {

	/**
	 * Control type.
	 *
	 * @var string
	 */
	public $type = 'pgt_note';

	/**
	 * The text to show.
	 *
	 * @var string
	 */
	public $note = '';

	/**
	 * Render.
	 *
	 * @return void
	 */
	public function render_content() {
		if ( '' === $this->note ) {
			return;
		}
		echo '<p class="description pgt-note">' . esc_html( $this->note ) . '</p>';
	}
}

/**
 * Renders the art direction, recommended size and copyable prompt.
 */
class Customize_Image_Prompt_Control extends \WP_Customize_Control {

	/**
	 * Control type.
	 *
	 * @var string
	 */
	public $type = 'pgt_image_prompt';

	/**
	 * Slot key this control describes.
	 *
	 * @var string
	 */
	public $slot_key = '';

	/**
	 * Render.
	 *
	 * @return void
	 */
	public function render_content() {
		$slot = Image_Slots::get( $this->slot_key );
		if ( ! $slot ) {
			return;
		}

		$prompt = Image_Slots::prompt( $this->slot_key );
		$field  = 'pgt-prompt-' . sanitize_html_class( $this->slot_key );
		$filled = count( Image_Slots::attachments( $this->slot_key ) );
		?>
		<div class="pgt-promptbox">
			<?php if ( ! empty( $slot['where'] ) ) : ?>
				<p class="pgt-promptbox__where"><?php echo esc_html( $slot['where'] ); ?></p>
			<?php endif; ?>

			<?php
			// Without this the owner has to open five collapsed media controls
			// to learn whether anything is in rotation at all.
			?>
			<p class="pgt-imgslot-count<?php echo $filled ? '' : ' is-empty'; ?>">
				<?php
				if ( $filled > 1 ) {
					printf(
						/* translators: %d: number of images currently uploaded */
						esc_html__( '%d images in rotation — one is picked at random on each page load.', 'prepgro-theme' ),
						(int) $filled
					);
				} elseif ( 1 === $filled ) {
					esc_html_e( '1 image in use. Add more to rotate between them.', 'prepgro-theme' );
				} else {
					esc_html_e( 'Nothing uploaded yet — this position shows a brand tint.', 'prepgro-theme' );
				}
				?>
			</p>

			<p class="pgt-promptbox__size">
				<strong><?php esc_html_e( 'Recommended size', 'prepgro-theme' ); ?>:</strong>
				<?php echo esc_html( Image_Slots::size_label( $this->slot_key ) ); ?>
			</p>

			<label class="customize-control-title" for="<?php echo esc_attr( $field ); ?>">
				<?php esc_html_e( 'Prompt for generating an image', 'prepgro-theme' ); ?>
			</label>
			<textarea
				id="<?php echo esc_attr( $field ); ?>"
				class="pgt-promptbox__text"
				rows="9"
				readonly
				onclick="this.select();"
			><?php echo esc_textarea( $prompt ); ?></textarea>

			<button type="button" class="button pgt-promptbox__copy" data-target="<?php echo esc_attr( $field ); ?>">
				<?php esc_html_e( 'Copy prompt', 'prepgro-theme' ); ?>
			</button>

			<p class="description">
				<?php
				printf(
					/* translators: %d: maximum number of images per slot */
					esc_html__( 'Generate the image, then upload it below. Add up to %d and the site shows a different one on each page load.', 'prepgro-theme' ),
					(int) Image_Slots::MAX
				);
				?>
			</p>
		</div>
		<?php
	}
}
