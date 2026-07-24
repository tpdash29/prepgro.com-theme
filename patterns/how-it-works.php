<?php
/**
 * Pattern: How it works (3 steps).
 *
 * @package PrepGro\Theme
 */

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'prepgro/how-it-works',
	array(
		'title'      => __( 'How it works', 'prepgro-theme' ),
		'categories' => array( 'prepgro' ),
		'content'    => '<!-- wp:html -->
<section class="pgt-section pgt-section--tint"><div class="pgt-container">
  <p class="pgt-eyebrow">How it works</p>
  <h2 style="font-size:clamp(1.6rem,3.5vw,2.3rem);margin:.4rem 0 2rem;">Three steps to test-ready.</h2>
  <div class="pgt-grid">
    <div class="pgt-card"><div class="pgt-card__icon">1</div><h3>Pick your exam</h3><p>Choose your grade, subject, or target test and jump into a tailored prep path.</p></div>
    <div class="pgt-card"><div class="pgt-card__icon">2</div><h3>Practice &amp; review</h3><p>Take mock tests and targeted drills, then review every answer with clear explanations.</p></div>
    <div class="pgt-card"><div class="pgt-card__icon">3</div><h3>Track readiness</h3><p>Watch your scores climb and know exactly when you\'re ready.</p></div>
  </div>
</div></section>
<!-- /wp:html -->',
	)
);
