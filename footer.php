<?php
/**
 * Footer template — for the few classic templates that call get_footer().
 *
 * This is a block (FSE) theme; the footer normally comes from the block
 * template parts. The engine's Elevate templates (course single, lesson
 * orphan branch, quiz fallback) call get_footer() directly, and without
 * this file WordPress prints nothing there — no footer chrome at all.
 * render_footer() picks the variant (app footer + country floor on course
 * singles, nothing on focus surfaces) exactly as the block part does.
 *
 * @package PrepGro\Theme
 */

echo \PrepGro\Theme\Chrome::instance()->render_footer(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- chrome markup, escaped at build.
wp_footer();
echo "</body></html>\n";
