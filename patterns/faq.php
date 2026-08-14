<?php
/**
 * Title: FAQ
 * Slug: prepgro-theme/faq
 * Categories: prepgro
 *
 * @package PrepGro\Theme
 */

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'prepgro/faq',
	array(
		'title'      => __( 'FAQ', 'prepgro-theme' ),
		'categories' => array( 'prepgro' ),
		'content'    => '<!-- wp:html -->
<section class="pgt-section"><div class="pgt-container" style="max-width:760px;">
  <p class="pgt-eyebrow">FAQ</p>
  <h2 style="font-size:clamp(1.6rem,3.5vw,2.3rem);margin:.4rem 0 1.5rem;">Common questions</h2>
  <details class="pgt-card" style="margin-bottom:.75rem;"><summary style="font-weight:700;cursor:pointer;">Is there a free plan?</summary><p style="margin-top:.6rem;">Yes — create a free account and take full-length practice tests before you upgrade.</p></details>
  <details class="pgt-card" style="margin-bottom:.75rem;"><summary style="font-weight:700;cursor:pointer;">Which exams do you cover?</summary><p style="margin-top:.6rem;">School assessments, college-entrance tests, and certification exams — with more added over time.</p></details>
  <details class="pgt-card"><summary style="font-weight:700;cursor:pointer;">Can parents track progress?</summary><p style="margin-top:.6rem;">Absolutely. Parent dashboards show readiness and weak areas at a glance.</p></details>
</div></section>
<!-- /wp:html -->',
	)
);
