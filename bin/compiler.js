/**
 * Build JS.
 *
 * @see https://github.com/tarosky/workflows/tree/main/boilerplate/bin
 */

const fs = require( 'fs' );
const { glob } = require( 'glob' )
const { exec, execSync } = require( 'child_process' )
const { dumpSetting } = require( '@kunoichi/grab-deps' );

const command = process.argv[ 2 ];

/**
 * @typedef Path
 * @type {object}
 * @property {string} dir    - Parent directory.
 * @property {number} order  - Order. Lower processed first.
 * @property {string[]} path - Array of file path.
 */

/**
 * Add path by order.
 *
 * @param {Path[]} paths
 * @param {string}   newPath
 * @return {void}
 */
const addPath = ( paths, newPath ) => {
	const pathParts = newPath.split( '/' ).filter( path => path.length >= 1 );
	const length = pathParts.length;
	const lastName = pathParts.pop();
	const parentDir = pathParts.join( '/' );
	let index = -1;
	for ( let i = 0; i < paths.length; i++ ) {
		if ( paths[ i ].dir === parentDir ) {
			index = i;
			break;
		}
	}
	if ( 0 <= index ) {
		// Already exists.
		paths[ index ].path.push( [ parentDir, lastName ].join( '/' ) );
	} else {
		// Add new path.
		paths.push( {
			dir: parentDir,
			order: length,
			path: [ [ parentDir, lastName ].join( '/' ) ],
		} );
	}
	paths.sort( ( a, b ) => {
		if ( a.order === b.order ) {
			return 0;
		} else {
			return a.order < b.order ? -1 : 1;
		}
	} );
}

/**
 * Extract license header from JS file.
 *
 * @param {string} path
 * @param {string} src
 * @param {string} dest
 * @return {boolean}
 */
const extractHeader = ( path, src, dest ) => {
	const target = path.replace( src, dest ) + '.LICENSE.txt';
	const content = fs.readFileSync( path, 'utf8' );
	if ( !content ) {
		return false;
	}
	const match = content.match( /^(\/\*{1,2}!.*?\*\/)/ms );
	if ( !match ) {
		console.log( match );
		return false;
	}
	fs.writeFileSync( target, match[ 1 ] );
}

switch ( command ) {
	case 'dump':
		dumpSetting( 'dist' );
		console.log( 'wp-dependencies.json updated.' );
		break;
	case 'js':
	default:
		console.log( 'Building js files...' );
		glob( [ './assets/js/**/*.js' ] ).then( res => {
			/** @type {Path[]} paths */
			const paths = [];
			res.map( ( path ) => {
				addPath( paths, path );
			} );
			paths.forEach( ( p ) => {
				const output = p.dir.replace( 'assets/js', 'dist/js' );
				if ( ! fs.existsSync( output ) ) {
					fs.mkdirSync( output, { recursive: true } );
				}
				execSync( `npx wp-scripts build ${ p.path.join( ' ' ) } --output-path=${ output }` );
			} );
			// Put license.txt.
			glob( [ 'assets/js/**/*.js' ] ).then( res => {
				res.map( ( path ) => {
					extractHeader( path, 'assets/js', 'dist/js' );
				} );
			} );
			// Remove php files.
			glob( './dist/js/**/*.php' ).then( res => {
				res.forEach( function( path ) {
					fs.unlinkSync( path );
				} );
				console.log( 'Done.' );
			} );
		} ).catch( res => {
			console.error( res.stdout.toString() );
		} );
		break;
}

