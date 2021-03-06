<?php
/**
 * Analytics related functions
 *
 * @package capitalp
 */

add_action( 'admin_head', function() {
	?>
	<script>
	(function (i, s, o, g, r, a, m) {
		i['GoogleAnalyticsObject'] = r;
		i[r] = i[r] || function () {
			(i[r].q = i[r].q || []).push(arguments)
          }, i[r].l = 1 * new Date();
        a = s.createElement(o),
          m = s.getElementsByTagName(o)[0];
        a.async = 1;
        a.src = g;
        m.parentNode.insertBefore(a, m)
      })(window, document, 'script', 'https://www.google-analytics.com/analytics.js', 'ga');
	ga('create', 'UA-1766751-12', 'auto');
	ga('send', 'pageview');
	</script>
	<?php
} );

/**
 * Display analytics code
 */
add_action( 'wp_head', function () {
	?>
    <script>
      window.capitalP = {
        post         : 0,
        author       : 0,
        tags         : '',
        setDimension : function (slot, value) {
          try {
            ga('set', 'dimension' + slot, value);
          } catch (err) {
          }
        },
        setPostData  : function (postId, authorId, tags) {
          this.post = postId;
          this.author = authorId;
          this.tags = tags;
          this.setDimension(1, postId);
          this.setDimension(2, authorId);
          if (tags) {
            this.setDimension(3, tags);
          }
        },
        resetPostData: function () {
          if (this.post && this.author) {
            this.setDimension(1, this.post);
            this.setDimension(2, this.author);
            if (this.tags) {
              this.setDimension(3, this.tags);
            }
          }
        }
      };
	  <?php if ( ! is_preview() ) : ?>
      (function (i, s, o, g, r, a, m) {
        i['GoogleAnalyticsObject'] = r;
        i[r] = i[r] || function () {
            (i[r].q = i[r].q || []).push(arguments)
          }, i[r].l = 1 * new Date();
        a = s.createElement(o),
          m = s.getElementsByTagName(o)[0];
        a.async = 1;
        a.src = g;
        m.parentNode.insertBefore(a, m)
      })(window, document, 'script', 'https://www.google-analytics.com/analytics.js', 'ga');
      ga('create', 'UA-1766751-12', 'auto');
	  <?php
	  if ( is_singular( 'post' ) ) :
	  $tags = get_the_tags( get_queried_object_id() );
	  $tags = ( $tags && ! is_wp_error( $tags ) ) ? implode( ',', array_map( function ( $tag ) {
		  return $tag->term_id;
	  }, $tags ) ) : '';
	  ?>
      capitalP.setPostData( <?= get_queried_object()->ID ?>, <?= get_queried_object()->post_author ?>, '<?= $tags ?>');
	  <?php endif; ?>
      ga('send', 'pageview');
	  <?php endif; ?>
    </script>
	<?php
}, 99 );

/**
 * Add title parts
 */
add_filter( 'document_title_parts', function ( $title ) {
	if ( ! is_front_page() ) {
		$title['suffix'] = 'WordPressメディア';
	}
	return $title;
} );

/**
 * Display recent PV.
 */
add_shortcode( 'recent-hero', function ( $atts, $content = '' ) {
	$cache = get_transient( 'recent_hero' );
	if ( false === $cache ) {
		$fetcher = capitalp_analytics();
		if ( ! $fetcher ) {
			return '';
		}
		$list = [];
		ob_start();
		foreach ( $fetcher->fetch(
			date_i18n( 'Y-m-d', strtotime( '30 days ago' ) ),
			date_i18n( 'Y-m-d' ),
			'ga:pageviews',
			[
				'dimensions' => 'ga:dimension2',
				'sort' => '-ga:pageviews',
			]
		) as $row ) {
			list( $user_id, $pv ) = $row;
			if ( 1000 > $pv ) {
				continue;
			}
			$list[] = [
				'user' => new WP_User( $user_id ),
				'pv' => $pv,
			];
		}
		?>
		<table>
			<caption>最近30日の成績</caption>
			<thead>
			<tr>
				<th>投稿者</th>
				<th>PV数</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $list as $row ) : ?>
				<tr>
					<th>
						<a href="<?= get_author_posts_url( $row[ 'user' ]->ID, $row[ 'user' ]->user_nicename ) ?>">
							<?= esc_html( $row[ 'user' ]->display_name ) ?>
						</a>
					</th>
					<td><?= number_format_i18n( $row[ 'pv' ] ) ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		$content = ob_get_contents();
		ob_end_clean();
		$cache = $content;
		set_transient( 'recent_hero', $content, 60 * 30 );
	}
	return $cache;
} );

// Add post type for ranking.
add_action( 'init', function() {
	register_post_type( 'ranking', [
		'label'  => 'ランキング',
		'public' => true,
		'supports' => [ 'title', 'editor', 'author' ],
		'capability_type' => 'page',
		'map_meta_cap' => true,
		'capabilities' => [
			'create_posts' => 'create_ranking',
		],
	] );
} );

/**
 * ランキングが公開されたらつぶやく
 */
add_action( 'transition_post_status', function( $new_status, $old_status, $post ) {
	if ( ( 'ranking' === $post->post_type ) && ( 'publish' == $new_status ) && ( 'publish' != $old_status ) ) {
		if ( ! function_exists( 'gianism_update_twitter_status' ) ) {
			return;
		}
		gianism_update_twitter_status( sprintf(
			'%sのランキングが更新されたワン！ %s',
			date_i18n( get_option( 'date_format' ) ),
			get_permalink( $post )
		) );
	}
}, 10, 3 );
