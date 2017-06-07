<?php

/**
 * This file is part of Dana\Interpolator.
 *
 * @author  dana <dana@dana.is>
 * @license MIT
 */

namespace Dana\Interpolator;

class Interpolator {
	/**
	 * @var array Current instance options.
	 */
	protected $options = [];
	/**
	 * @var array Current instance filters.
	 */
	protected $filters = [];
	/**
	 * @var array Current instance auto filters.
	 */
	protected $autoFilters = [];

	/**
	 * Constructor.
	 *
	 * @param null|array $options
	 *   (optional) Any instance options to set (see setOptions()).
	 *
	 * @param null|array $filters
	 *   (optional) Any instance filters to set (see setFilters()).
	 *
	 * @param null|string|array $autoFilters
	 *   (optional) Any specifiers for filters to apply automatically (see
	 *   setAutoFilters()).
	 *
	 * @return self
	 */
	public function __construct(
		$options     = null,
		$filters     = null,
		$autoFilters = null
	) {
		$this->setOptions($options);
		$this->setFilters($filters);
		$this->setAutoFilters($autoFilters);
	}

	/**
	 * Returns the default instance options as an associative array.
	 *
	 * @return array
	 */
	public function getDefaultOptions() {
		return ['strict' => true];
	}

	/**
	 * Returns the default instance filters as an associative array.
	 *
	 * @return array
	 */
	public function getDefaultFilters() {
		return [
			// Remove all non-alphabetical characters
			'a' => function ($str) {
				return preg_replace('/[^A-Za-z]/u', '', $str);
			},
			// Encode with base64
			'b' => 'base64_encode',
			// Encode with 'URL-safe' base64
			'B' => function ($str) {
				return rtrim(strtr(base64_encode($str), '/+', '_-'), '=');
			},
			// Hash with CRC32
			'c' => function ($str) {
				return hash('crc32', $str);
			},
			// Hash with CRC32b
			'C' => function ($str) {
				return hash('crc32b', $str);
			},
			// Remove all non-numeric characters
			'd' => function ($str) {
				return preg_replace('/[^0-9]/u', '', $str);
			},
			// Escape for shell
			'e' => 'escapeshellarg',
			// Escape for URL (RFC3986), but keep forward-slashes
			'f' => function ($str) {
				return implode('/', array_map('rawurlencode', explode('/', $str)));
			},
			// Escape for HTML
			'h' => 'htmlspecialchars',
			// Escape for HTML (all possible entities)
			'H' => 'htmlentities',
			// Escape for JSON
			'j' => 'json_encode',
			// Convert to lower-case (Unicode-safe)
			'l' => 'mb_strtolower',
			// Convert to lower-case
			'L' => 'strtolower',
			// Hash with MD5
			'm' => function ($str) {
				return hash('md5', $str);
			},
			// Escape for PCRE pattern
			'p' => 'preg_quote',
			// Escape for URL (RFC3986)
			'r' => 'rawurlencode',
			// Escape for URL (RFC1866)
			'R' => 'urlencode',
			// Hash with SHA1
			's' => function ($str) {
				return hash('sha1', $str);
			},
			// Hash with SHA256
			'S' => function ($str) {
				return hash('sha256', $str);
			},
			// Trim leading/trailing white space
			't' => 'trim',
			// Convert to upper-case (Unicode-safe)
			'u' => 'mb_strtoupper',
			// Convert to upper-case
			'U' => 'strtoupper',
			// Collapse consecutive white space
			'w' => function ($str) {
				return preg_replace('/\s+/u', ' ', $str);
			},
			// Remove all white space
			'W' => function ($str) {
				return preg_replace('/\s+/u', '', $str);
			},
		];
	}

	/**
	 * Sets an array of instance options.
	 *
	 * @param null|array $options
	 *   (optional) An array of instance options to set. If null or empty, all
	 *   options will be over-written by their defaults.
	 *
	 * @return self
	 */
	public function setOptions($options = null) {
		if ( empty($options) ) {
			return $this->setOptions($this->getDefaultOptions());
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
		if ( ! array_key_exists($name, $this->getDefaultOptions()) ) {
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
	 * Registers an array of filters.
	 *
	 * @param null|array $options
	 *   (optional) An associative array of filters to set, where each key is a
	 *   single ASCII letter and each value is a callable. If null, the default
	 *   filters will be registered. If empty, all filters will be unregistered.
	 *
	 * @return self
	 */
	public function setFilters($filters = null) {
		if ( $filters === null ) {
			return $this->setFilters($this->getDefaultFilters());
		} elseif ( empty($filters) ) {
			$this->filters = [];
			return $this;
		}

		foreach ( $filters as $specifier => $callback ) {
			$this->setFilter($specifier, $callback);
		}

		return $this;
	}

	/**
	 * Registers a filter.
	 *
	 * @param string $specifier
	 *   The single-letter specifier for the filter.
	 *
	 * @param callable $callable
	 *   A callable which accepts as its first or only argument the string to be
	 *   filtered.
	 *
	 * @return self
	 *
	 * @throws \InvalidArgumentException if $specifier is invalid.
	 * @throws \InvalidArgumentException if $callable is invalid.
	 */
	public function setFilter($specifier, $callable) {
		if ( strlen($specifier) !== 1 || ! preg_match('/^[A-Za-z]$/', $specifier) ) {
			throw new \InvalidArgumentException("Illegal specifier: ${specifier}");
		}

		if ( ! is_callable($callable) ) {
			throw new \InvalidArgumentException(sprintf(
				'Expected callable (got %s)',
				gettype($callable)
			));
		}

		$this->filters[$specifier] = $callable;
		return $this;
	}

	/**
	 * Returns the current instance filters.
	 *
	 * @return array
	 *   An associative array of filter specifiers and their callables.
	 */
	public function getFilters() {
		return $this->filters;
	}

	/**
	 * Returns the current value of an instance option.
	 *
	 * @param string $name The name of the option.
	 *
	 * @return mixed The value of the option.
	 *
	 * @throws \InvalidArgumentException if $specifier is unrecognised.
	 */
	public function getFilter($specifier) {
		if ( ! isset($this->filters[$specifier]) ) {
			throw new \InvalidArgumentException("Unrecognised specifier: ${specifier}");
		}

		return $this->filters[$specifier];
	}

	/**
	 * Sets filters to be applied automatically.
	 *
	 * @param null|string|array $specifiers
	 *   (optional) Zero or more valid specifiers corresponding to filters which
	 *   should be applied automatically after all other filters have finished.
	 *   For example, if 'u' is set as the auto filter, each replacement will be
	 *   upper-cased after other filters have been applied. Auto filters can be
	 *   suppressed on a per-place-holder basis using the special `-` specifier.
	 *   Providing an empty $specifiers value disables auto filters.
	 *
	 * @return self
	 *
	 * @throws \InvalidArgumentException if one of $specifiers is unrecognised.
	 */
	public function setAutoFilters($specifiers = null) {
		if ( $specifiers === null || $specifiers === '' || $specifiers === [] ) {
			$this->autoFilters = [];
			return $this;
		}

		if ( is_string($specifiers) ) {
			$specifiers = str_split($specifiers);
		}

		foreach ( $specifiers as $specifier ) {
			if ( ! isset($this->filters[$specifier]) ) {
				throw new \InvalidArgumentException("Unrecognised specifier: ${specifier}");
			}
		}

		$this->autoFilters = $specifiers;
		return $this;
	}

	/**
	 * Returns auto-filter specifiers as an array.
	 *
	 * @return array
	 */
	public function getAutoFilters() {
		return $this->autoFilters;
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

	/**
	 * Interpolates a string of place-holders.
	 *
	 * @param string $string   The string to interpolate.
	 * @param array  $fixtures The fixtures to use for interpolation.
	 *
	 * @return string The interpolated string.
	 *
	 * @throws \RuntimeException if strict and a fixture name is not found.
	 * @throws \RuntimeException if strict and a fixture's type is unsupported.
	 */
	protected function interpolate($string, array $fixtures) {
		return preg_replace_callback(
			'/(\\\\*)(\%\{([\w.-]+)(?:\|([A-Za-z0-9-]*))?\})/',
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
							"Unsupported fixture value type: %s (%s)",
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
	 * Applies filters to a string.
	 *
	 * @param string $string  The string being filtered/substituted.
	 * @param array  $filters Zero or more filter specifiers.
	 *
	 * @return string The (possibly) filtered string.
	 *
	 * @throws \RuntimeException if a supplied filter specifier is unrecognised.
	 */
	protected function applyFilters($string, array $specifiers) {
		$applyAutoFilters = true;

		foreach ( $specifiers as $specifier ) {
			if ( $specifier === '-' ) {
				$applyAutoFilters = false;
				continue;
			}

			if ( ! isset($this->filters[$specifier]) ) {
				throw new \RuntimeException(
					"Unrecognised or invalid filter specifier: ${specifier}"
				);
			}

			$string = $this->filters[$specifier]($string);
		}

		if ( $applyAutoFilters && ! empty($this->autoFilters) ) {
			foreach ( $this->autoFilters as $specifier ) {
				if ( ! isset($this->filters[$specifier]) ) {
					throw new \RuntimeException(
						"Unrecognised or invalid filter specifier: ${specifier}"
					);
				}

				$string = $this->filters[$specifier]($string);
			}
		}

		return (string) $string;
	}
}

