<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$reviews = array(
	array(
		'name' => 'Aulia Askha',
		'text' => 'studionya comfortable, wangi dan nyaman untuk work out & treatment. Kak Dinda, best coach! super duper ramah banget. highly recommended'
	),
	array(
		'name' => 'Cut Alia Masturina',
		'text' => 'Warga cinere must try, lokasi nya ada di ruko blkg cibel. Oke dan affordable, ada meni pedi nya juga'
	),
	array(
		'name' => 'Rae Taylor',
		'text' => 'Studio baru di cinere yang estetik banget, lengkap ada yoga ada pilates, best banget ❤️'
	)
);
?>
<section class="reviews-section section-wrap section-space" id="reviews">
	<div class="section-heading centered-heading"><p class="eyebrow">REAL STORIES</p><h2>Yang sudah merasakan.</h2></div>
	<div class="review-grid">
		<?php foreach ($reviews as $review): ?>
		<figure class="review-card">
			<div class="stars" aria-label="5 dari 5 bintang">★★★★★</div>
			<blockquote>“<?php echo html_escape($review['text']); ?>”</blockquote>
			<figcaption><span class="review-avatar"><?php echo html_escape(strtoupper(substr($review['name'], 0, 1))); ?></span><?php echo html_escape($review['name']); ?><small class="review-source">Google Review</small></figcaption>
		</figure>
		<?php endforeach; ?>
	</div>
</section>
