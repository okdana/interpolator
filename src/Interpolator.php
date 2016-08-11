<?php

/**
 * This file is part of Dana\Interpolator.
 *
 * @author  dana geier <dana@dana.is>
 * @license MIT
 */

namespace Dana\Interpolator;

class Interpolator {
	/**
	 * @var array Default instance options.
	 */
	protected $defaultOptions = [
		// If true, throw an exception when the referenced fixture key does not
		// exist or the fixture value could not be coerced to a string;
		// otherwise, silently replace the value by an empty string
		'strict' => true,
	];

	/**
	 * @var array Current instance options.
	 */
	protected $options = [];

	/**
	 * Constructor.
	 *
	 * @param array $options (optional) Any instance options to set.
	 *
	 * @return self
	 */
	public function __construct(array $options = []) {
		$this->setOptions();
		$this->setOptions($options);
	}

	/**
	 * Sets an array of instance options.
	 *
	 * @param array $options
	 *   (optional) An array of instance options to set. If empty, all options
	 *   will be over-written by their defaults.
	 *
	 * @return self
	 */
	public function setOptions(array $options = []) {
		if ( empty($options) ) {
			$this->setOptions($this->defaultOptions);
			return $this;
		}

		foreach ( $options as $name => $value ) {
			$this->setOption($name, $value);
		}
		return $this;
	}

	/**
	 * Sets an instance option.
	 *
	 * @param string $name  The name of the option.
	 * @param mixed  $value The value of the option.
	 *
	 * @return self
	 *
	 * @throws \InvalidArgumentException if $name is unrecognised.
	 */
	public function setOption($name, $value) {
		if ( ! array_key_exists($name, $this->defaultOptions) ) {
			throw new \InvalidArgumentException("Unrecognised option: ${name}");
		}

		$this->options[$name] = $value;
		return $this;
	}

	/**
	 * Returns the current instance options.
	 *
	 * @return array An associative array of instance options.
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * Returns the current value of an instance option.
	 *
	 * @param string $name The name of the option.
	 *
	 * @return mixed The value of the option.
	 *
	 * @throws \InvalidArgumentException if $name is unrecognised.
	 */
	public function getOption($name) {
		if ( ! array_key_exists($name, $this->options) ) {
			throw new \InvalidArgumentException("Unrecognised option: ${name}");
		}
		return $this->options[$name];
	}

	/**
	 * Applies filters to a string.
	 *
	 * @param string $string  The string being substituted.
	 * @param array  $filters Zero or more filter specifiers.
	 *
	 * @return string The (possibly) filtered string.
	 *
	 * @throws \RuntimeException if a supplied filter specifier is unrecognised.
	 */
	protected function applyFilters($string, array $filters) {
		foreach ( $filters as $filter ) {
			switch ( $filter ) {
				case 'e':
					$string = escapeshellarg($string);
					break;
				case 'h':
					$string = htmlspecialchars($string);
					break;
				case 'H':
					$string = htmlentities($string);
					break;
				case 'j':
					$string = json_encode($string);
					break;
				case 'L':
					$string = mb_strtolower($string);
					break;
				case 'p':
					$string = preg_quote($string);
					break;
				case 'r':
					$string = urlencode($string);
					break;
				case 'R':
					$string = rawurlencode($string);
					break;
				case 't':
					$string = trim($string);
					break;
				case 'U':
					$string = mb_strtoupper($string);
					break;
				case 'w':
					$string = preg_replace('/\s+/', ' ', $string);
					break;
				case 'W':
					$string = preg_replace('/\s+/', '', $string);
					break;
				default:
					throw new \RuntimeException(
						"Unrecognised filter specifier: ${filter}"
					);
			}
		}
		return (string) $string;
	}

	/**
	 * Interpolates a string of place-holders.
	 *
	 * @param string $string   The string to interpolate.
	 * @param array  $fixtures The fixtures to use for interpolation.
	 *
	 * @return string The interpolated string.
	 *
	 * @throws \RuntimeException if strict and a fixture name is not found.
	 * @throws \RuntimeException if strict and a fixture's type is illegal.
	 */
	protected function interpolate($string, array $fixtures) {
		return preg_replace_callback(
			'/(\\\\*)(%\{(\w+)(?:\|([A-Za-z0-9]*))?\})/',
			function ($m) use ($fixtures) {
				$backslashes = $m[1];
				$placeholder = $m[2];
				$fixture     = $m[3];
				$filters     = isset($m[4]) ? str_split($m[4]) : [];

				// If we've got an odd number of back-slashes preceding the
				// place-holder, it must be escaped — strip off the escaping on
				// all of the previous back-slashes, plus the one escaping our
				// place-holder, and return the rest as-is
				if ( ($len = strlen($backslashes)) && $len % 2 !== 0 ) {
					return substr($backslashes, ($len / 2) + 1) . $placeholder;
				// Otherwise, just strip off the escaping on the previous
				// back-slashes
				} else {
					$backslashes = substr($backslashes, $len / 2);
				}

				if ( ! array_key_exists($fixture, $fixtures) ) {
					if ( $this->options['strict'] ) {
						throw new \RuntimeException(
							"Fixture not found: ${fixture}"
						);
					}
					$fixtures[$fixture] = '';
				}

				$replace = $fixtures[$fixture];

				if ( ! is_scalar($replace) && $replace !== null ) {
					if ( is_object($replace) && method_exists($replace, '__toString') ) {
						$replace = (string) $replace;
					} elseif ( $this->options['strict'] ) {
						throw new \RuntimeException(sprintf(
							"Illegal fixture value type: %s (%s)",
							$fixture,
							gettype($replace)
						));
					} else {
						$replace = gettype($replace);
					}
				}

				$replace = $this->applyFilters($replace, $filters);

				return $backslashes . $replace;
			},
			$string
		);
	}

	/**
	 * Renders a string via interpolation.
	 *
	 * @param string $string   The string to interpolate.
	 * @param array  $fixtures The fixtures to use for interpolation.
	 *
	 * @return string The interpolated string.
	 */
	public function render($string, array $fixtures) {
		// Hopefully a small optimisation (@todo: test this assumption)
		if ( strpos($string, '%{') === false ) {
			return $string;
		}
		return $this->interpolate($string, $fixtures);
	}
}

