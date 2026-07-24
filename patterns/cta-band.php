<?php
/**
 * Pattern: CTA band.
 *
 * @package PrepGro\Theme
 */

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'prepgro/cta-band',
	array(
		'title'      => __( 'CTA band', 'prepgro-theme' ),
		'categories' => array( 'prepgro' ),
		'content'    => '<!-- wp:html -->
<section class="pgt-section"><div class="pgt-container">
  <div class="pgt-cta">
    <h2>Start practicing today.</h2>
    <p>Create a free account and take your first full-length practice test in minutes.</p>
    <a class="pgt-btn pgt-btn--on-dark pgt-btn--lg" href="/get-started/">Get started free</a>
  </div>
</div></section>
<!-- /wp:html -->',
	)
);
