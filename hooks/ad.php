<?php
/**
 * Advertisement hooks
 *
 * @package capitalp
 */

/**
 * Show ad
 */
add_filter( 'the_content', function ( $content ) {
	if ( is_singular( 'post' ) && in_the_loop() ) {
		ob_start();
		?>
        <div class="ad-single-top">
            <span class="ad-title">SPONSORED LINK</span>
			<?php capitalp_ad( 'after_title' ) ?>
        </div>
		<?php
		
		if ( author_can( get_post(), 'contributor' ) ) {
			get_template_part( 'template-parts/block/contributor' );
		}
		$ad = ob_get_contents();
		ob_end_clean();
		$content = $ad . $content;
	}

	return $content;
} );

/**
 * Show advertisement after content
 */
add_filter( 'the_content', function ( $content ) {
	
	if ( is_singular( 'post' ) && in_the_loop() ) {
		ob_start();
		get_template_part( 'template-parts/block/ad', 'content' );
		$ad = ob_get_contents();
		ob_end_clean();
		$content .= $ad;
	}

	return $content;
}, 11 );

/**
 * Register sidebar for widget
 */
add_action( 'init', function () {
	register_sidebar( [
		'id'            => 'after-content',
		'name'          => 'コンテンツ直下',
		'description'   => 'コンテンツ直下に表示されます。一個ぐらいにしておいてください。',
		'before_widget' => '<aside class="ad-widget-content">',
		'before_title'  => '<h3 class="ad-widget-title">',
		'after_title'   => '</h3>',
		'after_widget'  => '</aside>',
	] );
}, 11 );



/**
 * Register widgetss
 */
add_action( 'widgets_init', function () {
	register_widget( CapitalP_WidgetAdsence::class );
} );

add_action( 'get_template_part_template-parts/entry-summary', function($slug, $name) {
	if ( ( is_singular() || is_page() ) && ! is_front_page() ) {
		return;
	}
	static $counter = 0;
	$counter++;
	if ( ( 0 === $counter % 4 ) && ( 1 < $counter ) ) {
		?>
		<?php capitalp_ad( 'infeed' ) ?>
		</li>
		<li class="c-entries__item">
		<?php
	}
}, 10, 2 );
