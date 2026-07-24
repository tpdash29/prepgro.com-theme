<?php
/**
 * Pattern: Feature grid (3 cards).
 *
 * @package PrepGro\Theme
 */

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'prepgro/feature-grid',
	array(
		'title'      => __( 'Feature grid', 'prepgro-theme' ),
		'categories' => array( 'prepgro' ),
		'content'    => '<!-- wp:html -->
<section class="pgt-section"><div class="pgt-container">
  <p class="pgt-eyebrow">Why PrepGro</p>
  <h2 style="font-size:clamp(1.6rem,3.5vw,2.3rem);margin:.4rem 0 2rem;max-width:22ch;">Everything you need to walk in prepared.</h2>
  <div class="pgt-grid">
    <div class="pgt-card"><div class="pgt-card__icon">🧠</div><h3>AI-generated practice</h3><p>Fresh, exam-accurate questions on demand — dual-reviewed for quality.</p></div>
    <div class="pgt-card"><div class="pgt-card__icon">⏱️</div><h3>Real exam simulation</h3><p>Timed, full-length mock tests with a true computer-based-test experience.</p></div>
    <div class="pgt-card"><div class="pgt-card__icon">📈</div><h3>Progress you can see</h3><p>Detailed analytics pinpoint weak areas and track readiness.</p></div>
  </div>
</div></section>
<!-- /wp:html -->',
	)
);
