<?php declare( strict_types=1 );

namespace A8C\SpecialProjects\PluginTemplate\Tests\Integration;

use PHPUnit\Framework\Assert;

/**
 * Reads the `$a8csp_template_footprint` manifest out of the real `uninstall.php` for the
 * uninstall proofs, without requiring the file. Requiring it exits unless `WP_UNINSTALL_PLUGIN`
 * is already defined, and defining that just to read the array would run the delete loops before
 * a test has seeded anything for them to delete.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class InlineFootprint {
	/**
	 * Extracts the footprint array literal from `uninstall.php`'s source. Locates the literal by
	 * balancing parens from its own `array(` so the nested `options`/`user_meta` arrays don't
	 * confuse the match, then evaluates only that expression — never uninstall.php's guard or its
	 * delete loops.
	 * This eval is safe only because it parses this repository's own version-controlled
	 * `uninstall.php` and must never be generalized to evaluate user input, remote data,
	 * another file, or anything else from outside this repository; if the footprint's shape
	 * grows complex enough that this string-slicing extraction becomes fragile, use a
	 * `token_get_all()`-based reader as the eval-free alternative instead of trying to make
	 * the eval safer.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  array{options: list<string>, user_meta: list<string>}
	 */
	public static function read(): array {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local filesystem read of a tracked source file, not a remote resource.
		$source = (string) \file_get_contents( \dirname( __DIR__, 2 ) . '/uninstall.php' );

		$needle = '$a8csp_template_footprint';
		$assign = \strpos( $source, $needle );
		Assert::assertIsInt( $assign, "uninstall.php must declare {$needle} inline" );

		$array_start = \strpos( $source, 'array(', $assign );
		Assert::assertIsInt( $array_start, "could not find the {$needle} array literal" );

		$depth  = 0;
		$end    = null;
		$length = \strlen( $source );

		for ( $i = $array_start; $i < $length; $i++ ) {
			if ( '(' === $source[ $i ] ) {
				++$depth;
			} elseif ( ')' === $source[ $i ] ) {
				--$depth;

				if ( 0 === $depth ) {
					$end = $i;
					break;
				}
			}
		}

		Assert::assertIsInt( $end, "could not find the end of the {$needle} array literal" );

		$expression = \substr( $source, $array_start, $end - $array_start + 1 );
		$footprint  = eval( "return {$expression};" ); // phpcs:ignore Squiz.PHP.Eval -- evaluates a version-controlled array literal parsed out of this repo's own uninstall.php, never external input.

		Assert::assertIsArray( $footprint );
		Assert::assertArrayHasKey( 'options', $footprint );
		Assert::assertArrayHasKey( 'user_meta', $footprint );

		return $footprint;
	}
}
