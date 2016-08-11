<?php

/**
 * This file is part of Dana\Interpolator.
 *
 * @author  dana geier <dana@dana.is>
 * @license MIT
 */

namespace Dana\Interpolator\Test;

use Dana\Interpolator\Interpolator;

class InterpolatorTest extends \PHPUnit_Framework_TestCase {
	/**
	 * Data provider for testRender().
	 *
	 * @return array[]
	 */
	public function provideForTestRender() {
		$test = ' <foo  \"  bar>  ';

		return [
			// Empty-string tests
			[[], '',       [''], ''],
			[[], '%{0}',   [''], ''],
			[[], '%{0|e}', [''], "''"],
			[[], '%{0|t}', [''], ''],

			// Non-string scalar fixture tests
			[[], '%{0}', [null],  (string) null],
			[[], '%{0}', [true],  (string) true],
			[[], '%{0}', [false], (string) false],
			[[], '%{0}', [999],   (string) 999],

			// Basic strict-mode tests
			[[], '%{0}',   [$test], $test],
			[[], 'x%{0}',  [$test], 'x' . $test],
			[[], '%{0}x',  [$test], $test . 'x'],
			[[], 'x%{0}x', [$test], 'x' . $test . 'x'],
			[[], '%{0|e}', [$test], escapeshellarg($test)],
			[[], '%{0|h}', [$test], htmlspecialchars($test)],
			[[], '%{0|H}', [$test], htmlentities($test)],
			[[], '%{0|j}', [$test], json_encode($test)],
			[[], '%{0|L}', [$test], mb_strtolower($test)],
			[[], '%{0|p}', [$test], preg_quote($test)],
			[[], '%{0|r}', [$test], urlencode($test)],
			[[], '%{0|R}', [$test], rawurlencode($test)],
			[[], '%{0|t}', [$test], trim($test)],
			[[], '%{0|U}', [$test], mb_strtoupper($test)],
			[[], '%{0|w}', [$test], preg_replace('/\s+/', ' ', $test)],
			[[], '%{0|W}', [$test], preg_replace('/\s+/', '',  $test)],

			// Multiple-filter tests
			[[], '%{0|et}', [$test], trim(escapeshellarg($test))],
			[[], '%{0|te}', [$test], escapeshellarg(trim($test))],

			// Associative fixture tests
			[[], '%{a}',   ['a'   => $test],             $test],
			[[], '%{aaa}', ['aaa' => $test],             $test],
			[[], '%{1a}',  ['1a'  => $test],             $test],
			[[], '%{a1}',  ['a1'  => $test],             $test],
			[[], '%{_}',   ['_'   => $test],             $test],
			[[], '%{a|e}', ['a'   => $test],             escapeshellarg($test)],
			[[], '%{a}',   ['a'   => $test, 'A' => 'x'], $test],
			[[], '%{A}',   ['A'   => $test, 'a' => 'x'], $test],

			// Non-strict-mode missing-fixture tests
			[['strict' => false], '%{1}',    [$test], ''],
			[['strict' => false], '%{1|e}',  [$test], escapeshellarg('')],
			[['strict' => false], '%{1|h}',  [$test], htmlspecialchars('')],
			[['strict' => false], '%{1|H}',  [$test], htmlentities('')],
			[['strict' => false], '%{1|j}',  [$test], json_encode('')],
			[['strict' => false], '%{1|L}',  [$test], mb_strtolower('')],
			[['strict' => false], '%{1|p}',  [$test], preg_quote('')],
			[['strict' => false], '%{1|r}',  [$test], urlencode('')],
			[['strict' => false], '%{1|R}',  [$test], rawurlencode('')],
			[['strict' => false], '%{1|t}',  [$test], trim('')],
			[['strict' => false], '%{1|U}',  [$test], mb_strtoupper('')],
			[['strict' => false], '%{1|w}',  [$test], preg_replace('/\s+/', ' ', '')],
			[['strict' => false], '%{1|W}',  [$test], preg_replace('/\s+/', '',  '')],

			// Non-strict-mode illegal-type tests
			[['strict' => false], '%{0}', [[]],          'array'],
			[['strict' => false], '%{0}', [(object) []], 'object'],

			// Escaping tests
			[[], '\\%{0}',         [$test], '%{0}'],
			[[], '\\\\%{0}',       [$test], '\\' . $test],
			[[], '\\\\\\%{0}',     [$test], '\\%{0}'],
			[[], '\\\\\\\\%{0}',   [$test], '\\\\' . $test],
			[[], '\\\\\\\\\\%{0}', [$test], '\\\\%{0}'],
			[[], '\\ %{0}',        [$test], '\\ ' . $test],
			[[], '\\\\ %{0}',      [$test], '\\\\ ' . $test],
			[[], '\\\\\\ %{0}',    [$test], '\\\\\\ ' . $test],

			// Non-conforming syntax tests
			[[], '%{0!}',  [$test], '%{0!}'],
			[[], '%{0|!}', [$test], '%{0|!}'],
			[[], '%{0 }',  [$test], '%{0 }'],
			[[], '%{ 0}',  [$test], '%{ 0}'],
			[[], '%{0',    [$test], '%{0'],

			// Nested place-holder tests
			[[], '%{%{0}}',       [$test], '%{' . $test . '}'],
			[[], '%{%{%{0}}}',    [$test], '%{%{' . $test . '}}'],
			[[], '%{%{%{%{0}}}}', [$test], '%{%{%{' . $test . '}}}'],
			[[], '%{%{0}%{0}}',   [$test], '%{' . $test . $test . '}'],
		];
	}

	/**
	 * Data provider for testRenderException().
	 *
	 * @return array[]
	 */
	public function provideForTestRenderException() {
		return [
			// Missing-fixture exceptions
			[[], '%{0}', []],
			[[], '%{1}', ['']],
			[[], '%{a}', []],
			[[], '%{A}', ['a' => 'a']],

			// Illegal-fixture-type exceptions
			[[], '%{0}', [[]]],
			[[], '%{0}', [(object) []]],

			// Illegal-filter-specifier exceptions
			[[],                  '%{0|}',   ['']],
			[[],                  '%{0|1}',  ['']],
			[[],                  '%{0|Z}',  ['']],
			[[],                  '%{0|eZ}', ['']],
			[['strict' => false], '%{0|}',   ['']],
			[['strict' => false], '%{0|1}',  ['']],
			[['strict' => false], '%{0|Z}',  ['']],
			[[],                  '%{0|eZ}', ['']],
		];
	}

	/**
	 * Tests the render() method.
	 *
	 * @param array  $options  Interpolator instance options.
	 * @param string $string   The string to interpolate.
	 * @param array  $fixtures The fixtures to use for interpolation.
	 * @param string $expected The expected result.
	 *
	 * @dataProvider provideForTestRender
	 *
	 * @return void
	 */
	public function testRender($options, $string, $fixtures, $expected) {
		$interpolator = new Interpolator($options);
		$this->assertSame($expected, $interpolator->render($string, $fixtures));
	}

	/**
	 * Tests that render() throws exceptions where necessary.
	 *
	 * @return void
	 *
	 * @dataProvider      provideForTestRenderException
	 * @expectedException \RuntimeException
	 */
	public function testRenderException($options, $string, $fixtures) {
		(new Interpolator($options))->render($string, $fixtures);
	}
}

