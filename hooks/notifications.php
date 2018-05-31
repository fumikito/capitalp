<?php
/**
 * Notification utilities
 *
 * @package capitalp
 */

// Send notification if post status is changed.
add_action( 'transition_post_status', function ( $new_status, $old_status, $post ) {
	if ( 'post' !== $post->post_type ) {
		return;
	}
	if ( ( 'publish' === $new_status ) && ( 'publish' !== $old_status ) ) {
		// Post is newly published.
		$content = '@channel 新しく記事が公開されたワン！ いっぱい宣伝して欲しいワン！ %s';
		do_action( 'hameslack', sprintf( $content, get_permalink( $post ) ) );
	}
}, 10, 3 );

// Cappy says...
add_filter( 'hameslack_api_default_text', function () {
	return 'ワンワン！　よくわからないワン！';
} );

// Cappy do...
add_filter( 'hameslack_rest_response', function ( $response, $request, $post ) {
	switch ( $post->post_name ) {
		case 'cappy-api':
			$text    = explode( "\n", trim( str_replace( 'cappy', '', $request['text'] ) ) );
			$command = array_shift( $text );
			$user_name        = $request['user_name'];
			$user_query       = new WP_User_Query( [
				'number'     => 1,
				'meta_query' => [
					[
						'key'   => 'slack',
						'value' => $user_name,
					],
				],
			] );
			$current_user = null;
			if ( ( $current_users = $user_query->get_results() ) ) {
				$current_user = $current_users[0];
			}
			foreach (
				[
					'#bow#'        => function ( $response, $request, $post ) {
						$response['text'] = 'Wow!';

						return $response;
					},
					'#who#' => function( $response, $request, $post ) use ( $current_user, $user_name ) {
						$response['text'] = $current_user ? sprintf( '@%s %sさんだワン！', $user_name, $current_user->display_name ) : 'わからないワン…';
						return $response;
					},
					'#list#' => function( $response, $request, $post ) use ( $current_user, $user_name ) {
						$response['attachments'] = [];
						foreach ( get_posts( [
							'post_type'      => 'post',
							'post_status'    => 'draft',
							'posts_per_page' => 20,
							'orderby'        => [ 'ID' => 'DESC' ],
						] ) as $post ) {
							$response['attachments'][] = [
								'title'       => $post->ID . ' : '. get_the_title( $post ),
								'title_link'  => admin_url( sprintf( 'post.php?post=%d&action=edit', $post->ID ) ),
								'author_name' => get_the_author_meta( 'display_name', $post->post_author ),
							];
						}
						$response['text'] = sprintf( '@%s 書きかけの記事はこちらだワン！', $user_name );
						return $response;
					},
					'#append#' => function( $response, $request, $post ) use ( $text, $command, $current_user, $user_name ) {
						try {
							list( $prefix, $id ) = explode( ' ', preg_replace( '#\s+#', ' ', trim( $command ) ) );
							if ( ! is_numeric( $id ) || ! ( $post = get_post( $id ) ) || 'post' !== $post->post_type ) {
								throw new \Exception( '投稿が見つからなかったワン！' );
							}
							if ( false !== array_search( $post->post_status, [ 'publish', 'future', 'pending' ] ) ) {
								throw new \Exception( 'すでに公開する投稿には追記できないワン！' );
							}
							array_unshift( $text, $post->post_content );
							wp_update_post( [
								'ID' => $post->ID,
								'post_content' => implode( "\n\n", $text ),
							] );
							$response['text'] = sprintf( '@%s 投稿に追加したワン！', $user_name );
						} catch ( \Exception $e ) {
							$response['text'] = "@{$user_name} ".$e->getMessage();
						} finally {
							return $response;
						}
					},
					'#log#'        => function ( $response, $request, $post ) use ( $text, $command, $current_user ) {
						try {
							$command = explode( ' ', preg_replace( '#\s+#', ' ', trim( $command ) ) );
							$id      = $request['channel_id'];
							if ( 3 !== count( $command ) ) {
								throw new Exception( 'うーん、失敗したワン……こうやってほしいワン  `cappy log 2 3`  こうすると、2時間前から3時間前のメッセージをメモするワン！' );
							}
							list( $log, $from, $to ) = $command;
							if ( ! is_numeric( $from ) ) {
								$from = 0;
							}
							if ( ! is_numeric( $to ) ) {
								$to = 1;
							}
							$from_gmt   = current_time( 'timestamp', true ) - $from * 3600;
							$from_local = current_time( 'timestamp' ) - $from * 3600;
							$to_gmt     = current_time( 'timestamp', true ) - $to * 3600;
							$to_local   = current_time( 'timestamp' ) - $to * 3600;
							$messages = hameslack_channel_history( $request['channel_name'], $to_gmt, $from_gmt, [
								'count' => 300,
							] );
							if ( is_wp_error( $messages ) ) {
								throw new Exception( $messages->get_error_message() );
							}
							if ( empty( $messages ) ) {
								throw new Exception( 'その期間にメッセージはなかったワン' );
							}
//							error_log( sprintf( 'Cappy logs %d messages.', count( $messages ) ) );
							$format  = get_option( 'time_format' );
							$title   = sprintf( '%s〜%sのログ', date_i18n( $format, $to_local ), date_i18n( $format, $from_local ) );
							// Grab users.
							$slack_users       = [];
							foreach ( hameslack_members() as $member ) {
								$slack_users[ $member->name ] = [
									'slack_id' => $member->id,
									'wp_id' => 0,
								];
							}
//							error_log( sprintf( 'Users: %s', var_export( $slack_users, true ) ) );
							global $wpdb;
							$query = <<<SQL
								SELECT user_id, meta_value FROM {$wpdb->usermeta}
								WHERE meta_key    = 'slack'
								  AND meta_value != ''
SQL;
							foreach ( $wpdb->get_results( $query ) as $user ) {
								if ( array_key_exists( $user->meta_value, $slack_users ) ) {
									$slack_users[ $user->meta_value ]['wp_id'] = $user->user_id;
								}
							}
							$get_user_id = function( $slack_id ) use ( $slack_users ) {
								foreach ( $slack_users as $name => $user ) {
									if ( $slack_id == $user['slack_id'] ) {
										return $user['wp_id'];
									}
								}
								return 0;
							};
							// Create content.
							$content = [];
							foreach ( $messages as $message ) {
//								error_log( sprintf( '[Cappy Message] %s', var_export( $message, true ) ) );
								if ( $user_id = $get_user_id( $message->user ) ) {
									$content[] = sprintf( '[capitalp_author user_id=%d]%s[/capitalp_author]', $user_id, $message->text );
								} else {
									$content[] = "\n{$message->text}\n";
								}
							}
							$new_content = [];
							foreach ( $content as $c ) {
								array_unshift( $new_content, $c );
							}
							$post_id = wp_insert_post( [
								'post_type' => 'post',
								'post_status' => 'draft',
								'post_title' => $title,
								'post_content' => implode( "\n", $new_content ),
								'post_author' => $current_user->ID,
							], true );
							if ( is_wp_error( $post_id ) ) {
								throw new Exception( $post_id->get_error_message() );
							}
							$response['text'] = sprintf( 'ログを作成したワン！' );
							$response['attachments'] = [
								[
									'title' => $title,
									'title_link'  => admin_url( sprintf( 'post.php?post=%d&action=edit', $post_id ) ),
								],
							];
						} catch ( Exception $e ) {
							$response['text'] = $e->getMessage();
						} finally {
							return $response;
						}
					},
					'#draft#'      => function ( $response, $request, $post ) use ( $text, $current_user ) {
						$response['text'] = 'ダメだったワン！';
						$user_name        = $request['user_name'];
						if ( ! ( $current_user ) ) {
							$response['attachment'] = [
								[
									'color' => 'danger',
									'title' => '404 NOT FOUND',
									'text'  => '該当するユーザーがいなかったワン',
								],
							];

							return $response;
						}
						// Try creating post.
						$title     = array_shift( $text );
						$content   = implode( "\n", $text );
						$posts_arr = [
							'post_title'   => $title,
							'post_content' => $content,
							'post_author'  => $current_user->ID,
							'post_type'    => 'post',
							'post_status'  => 'draft',
						];
						$post_id   = wp_insert_post( $posts_arr, true );
						if ( is_wp_error( $post_id ) ) {
							$response['attachment'] = [
								[
									'color' => 'danger',
									'title' => $post_id->get_error_code(),
									'text'  => $post_id->get_error_message(),
								],
							];
						} else {
							$response['text']        = sprintf( '@%s 下書きを作成したワン！', $user_name );
							$response['attachments'] = [
								[
									'color'       => 'success',
									'title'       => get_the_title( $post_id ),
									'title_link'  => admin_url( sprintf( 'post.php?post=%d&action=edit', $post_id ) ),
									'author_name' => $current_user->display_name,
									'text'        => $content,
								],
							];
						}

						return $response;
					},
					'#debug#'      => function ( $response, $request, $post ) {
						$response['text']        = 'こんな値を受け取ったワン！';
						$response['attachments'] = [
							[
								'title' => 'POSTリクエスト',
								'text'  => var_export( $request, true ),
							],
						];

						return $response;
					},
					'#help#'       => function ( $response, $request, $post ) {
						$response['text']        = '`cappy xxx` で利用できる命令はこれだワン！';
						$response['attachments'] = [
							[
								'title'  => 'Cappyが理解できるコマンド',
								'fields' => [
									[
										'title' => 'help',
										'value' => 'いま実行してるワン！',
										'short' => true,
									],
									[
										'title' => 'debug',
										'value' => '投稿された内容を返すワン！',
										'short' => true,
									],
									[
										'title' => 'list',
										'value' => '現在の下書きを返すワン！',
										'short' => true,
									],
									[
										'title' => 'append',
										'value' => '二行目以降を下書きの投稿本文に追記するワン！ `cappy append xx` で投稿IDを指定してほしいワン！',
									],
									[
										'title' => 'draft',
										'value' => '投稿された内容でWordPressに下書きを作成するワン！　1行目がタイトル、2行目以降が本文に設定されるワン！',
									],
									[
										'title' => 'log',
										'value' => '指定された期間のメッセージを一つの投稿にまとめるワン！　`cappy log 1 3` って書くと、1時間前から3時間前までをまとめることになるワン。',
									],
								],
							],
						];

						return $response;
					},
					'#(hey|wait)#' => function ( $response, $request, $post ) use ( $command ) {
						foreach (
							[
								[ 'hey', 'なに？', 'hey.gif' ],
								[ 'wait', '....', 'wait.jpg' ],
							] as list( $reg, $txt, $file )
						) {
							if ( false !== strpos( $command, $reg ) ) {
								$response['text']        = $txt;
								$url                     = sprintf(
									'%s/assets/img/cappy/%s',
									get_stylesheet_directory_uri(),
									$file
								);
								$response['attachments'] = [
									[
										'image_url' => $url,
									],
								];
								break;
							}
						}

						return $response;
					},
					'#.*#'         => function ( $response, $request, $post ) {
						$response['text'] = 'なんだワン？　cappy help って入力してほしいワン！';

						return $response;
					},
				] as $regex => $callback
			) {
				if ( preg_match( $regex, $command ) ) {
					$response = call_user_func_array( $callback, [ $response, $request, $post ] );
					break;
				}
			}
			break;
		default:
			// Do nothing.
			break;
	}

	return $response;
}, 10, 3 );

/**
 * Send slack if feedback sent.
 */
add_action( 'grunion_pre_message_sent', function( $post_id ) {
	if ( 'publish' == get_post_status( $post_id ) ) {
		$edit_link = admin_url( "post.php?post={$post_id}&action=edit" );
		$content = "@here ユーザーからタレコミまたはフィードバックがあるワン！ {$edit_link}";
		do_action( 'hameslack',  $content );
	}
}, 10 );
