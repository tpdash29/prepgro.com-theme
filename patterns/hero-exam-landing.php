<?php
/**
 * Pattern: Hero — exam landing.
 *
 * @package PrepGro\Theme
 */

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'prepgro/hero-exam-landing',
	array(
		'title'      => __( 'Hero — exam landing', 'prepgro-theme' ),
		'categories' => array( 'prepgro' ),
		'content'    => '<!-- wp:html -->
<section class="pgt-hero"><div class="pgt-container">
  <p class="pgt-eyebrow">Practice smarter</p>
  <h1 class="pgt-hero__title">Ace your next exam with<br><span class="accent">practice that adapts to you.</span></h1>
  <p class="pgt-lead" style="max-width:52ch;">Realistic practice tests, AI-generated questions, and clear progress tracking.</p>
  <div class="pgt-hero__cta">
    <a class="pgt-btn pgt-btn--primary pgt-btn--lg" href="/get-started/">Get started free</a>
    <a class="pgt-btn pgt-btn--ghost pgt-btn--lg" href="/all-exams/">Explore practice tests</a>
  </div>
  <p class="pgt-hero__trust">No credit card required · Full-length mock tests · Instant results</p>
</div></section>
<!-- /wp:html -->',
	)
);
