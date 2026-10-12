<?php
defined('BASEPATH') OR exit('No direct script access allowed');

?>
<section class="reviews-section section-wrap section-space" id="reviews">
	<div class="section-heading centered-heading"><p class="eyebrow">REAL STORIES</p><h2>Yang sudah merasakan.</h2></div>
	<div class="review-grid">
		<?php foreach ($reviews as $review): ?>
		<figure class="review-card">
			<div class="stars" aria-label="<?php echo (int) $review->rating; ?> dari 5 bintang"><?php echo str_repeat('★', (int) $review->rating); ?></div>
			<blockquote>“<?php echo html_escape($review->review_text); ?>”</blockquote>
			<figcaption><span class="review-avatar"><?php echo html_escape(strtoupper(substr($review->name, 0, 1))); ?></span><?php echo html_escape($review->name); ?><small class="review-source"><?php echo html_escape($review->source); ?></small></figcaption>
		</figure>
		<?php endforeach; ?>
	</div>
</section>
