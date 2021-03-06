var gulp = require( 'gulp' ),
	fs = require( 'fs' ),
	$ = require( 'gulp-load-plugins' )(),
	pngquant = require( 'imagemin-pngquant' ),
	eventStream = require( 'event-stream' ),
	webpack = require( 'webpack-stream' ),
	webpackBundle = require( 'webpack' ),
	named = require( 'vinyl-named' );


// Sass tasks
gulp.task( 'sass', function () {
	return gulp.src( [ './src/scss/**/*.scss' ] )
		.pipe( $.plumber( {
			errorHandler: $.notify.onError( '<%= error.message %>' )
		} ) )
		.pipe( $.sourcemaps.init( { loadMaps: true } ) )
		.pipe( $.sassGlob() )
		.pipe( $.sass( {
			errLogToConsole: true,
			outputStyle: 'compressed',
			includePaths: [
				'./src/scss'
			]
		} ) )
		.pipe( $.autoprefixer() )
		.pipe( $.sourcemaps.write( './map' ) )
		.pipe( gulp.dest( './assets/css' ) );
} );


// Minify All
gulp.task( 'js', function () {
	return gulp.src( [ './src/js/**/*.js' ] )
		.pipe( $.sourcemaps.init( {
			loadMaps: true
		} ) )
		.pipe( $.uglify( {
			output: {
				comments: /^!/
			}
		} ) )
		.on( 'error', $.util.log )
		.pipe( $.sourcemaps.write( './map' ) )
		.pipe( gulp.dest( './assets/js/' ) );
} );

// Transpile JSX
gulp.task( 'jsx', function () {
	return gulp.src( [ './src/js/**/*.jsx', '!./src/js/**/_*.jsx' ] )
		.pipe( $.plumber( {
			errorHandler: $.notify.onError( '<%= error.message %>' )
		} ) )
		.pipe( named() )
		.pipe( webpack( {
			mode: 'production',
			devtool: 'source-map',
			module: {
				rules: [
					{
						test: /\.jsx$/,
						exclude: /(node_modules|bower_components)/,
						use: {
							loader: 'babel-loader',
							options: {
								presets: [ '@babel/preset-env' ],
								plugins: [ '@babel/plugin-transform-react-jsx' ]
							}
						}
					}
				]
			}
		}, webpackBundle ) )
		.pipe( gulp.dest( './assets/js' ) );
} );

// JS Hint
gulp.task( 'jshint', function () {
	return gulp.src( [ './src/js/**/*.js' ] )
		.pipe( $.jshint( './src/.jshintrc' ) )
		.pipe( $.jshint.reporter( 'jshint-stylish' ) );
} );

// Copy libraries
gulp.task( 'copylib', function ( done ) {
	// return eventStream.merge(
	// );
	done();
} );

// Image min
gulp.task( 'imagemin', function () {
	return gulp.src( './src/img/**/*' )
		.pipe( $.imagemin( {
			progressive: true,
			svgoPlugins: [ { removeViewBox: false } ],
			use: [ pngquant() ]
		} ) )
		.pipe( gulp.dest( './assets/img' ) );
} );


// watch
gulp.task( 'watch', function () {
	// Make SASS
	gulp.watch( 'src/scss/**/*.scss', gulp.task( 'sass' ) );
	// JS
	gulp.watch( [ 'src/js/**/*.js' ], gulp.parallel( 'js', 'jshint' ) );
	gulp.watch( [ 'src/js/**/*.jsx' ], gulp.parallel( 'jsx' ) );
	// Minify Image
	gulp.watch( 'src/img/**/*', gulp.parallel( 'imagemin' ) );
} );


// Build
gulp.task( 'build', gulp.parallel( 'copylib', 'jshint', 'js', 'jsx', 'sass', 'imagemin' ) );

// Default Tasks
gulp.task( 'default', gulp.task( 'watch' ) );
