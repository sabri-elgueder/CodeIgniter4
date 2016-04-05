<?php namespace CodeIgniter\Database;

class Query implements QueryInterface
{
	/**
	 * The query string, as provided by the user.
	 *
	 * @var string
	 */
	protected $originalQueryString;

	/**
	 * The final query string after binding, etc.
	 *
	 * @var string
	 */
	protected $finalQueryString;

	/**
	 * The binds and their values used for binding.
	 *
	 * @var array
	 */
	protected $binds = [];

	/**
	 * Bind marker
	 *
	 * Character used to identify values in a prepared statement.
	 *
	 * @var    string
	 */
	protected $bindMarker = '?';

	/**
	 * The start time in seconds with microseconds
	 * for when this query was executed.
	 *
	 * @var float
	 */
	protected $startTime;

	/**
	 * The end time in seconds with microseconds
	 * for when this query was executed.
	 *
	 * @var float
	 */
	protected $endTime;

	/**
	 * The error code, if any.
	 *
	 * @var int
	 */
	protected $errorCode;

	/**
	 * The error message, if any.
	 *
	 * @var string
	 */
	protected $errorString;

	/**
	 * Pointer to database connection.
	 * Mainly for escaping features.
	 *
	 * @var ConnectionInterface
	 */
	public $db;

	//--------------------------------------------------------------------

	/**
	 * BaseQuery constructor.
	 *
	 * @param $db ConnectionInterface
	 */
	public function __construct(&$db)
	{
	    $this->db = $db;
	}
	
	//--------------------------------------------------------------------
	
	
	/**
	 * Sets the raw query string to use for this statement.
	 *
	 * @param string $sql
	 *
	 * @return mixed
	 */
	public function setQuery(string $sql, $binds=null)
	{
		$this->originalQueryString = $sql;

		if (! is_null($binds))
		{
			$this->binds = $binds;
		}

		return $this;
	}

	//--------------------------------------------------------------------

	/**
	 * Returns the final, processed query string after binding, etal
	 * has been performed.
	 *
	 * @return mixed
	 */
	public function getQuery()
	{
		if (empty($this->finalQueryString))
		{
			$this->finalQueryString = $this->originalQueryString;
		}

		$this->compileBinds();

		return $this->finalQueryString;
	}

	//--------------------------------------------------------------------

	/**
	 * Records the execution time of the statement using microtime(true)
	 * for it's start and end values. If no end value is present, will
	 * use the current time to determine total duration.
	 *
	 * @param int      $start
	 * @param int|null $end
	 *
	 * @return mixed
	 */
	public function setDuration(float $start, float $end = null)
	{
		$this->startTime = $start;

		if (is_null($end))
		{
			$end = microtime(true);
		}

		$this->endTime = $end;

		return $this;
	}

	//--------------------------------------------------------------------

	/**
	 * Returns the start time in seconds with microseconds.
	 *
	 * @param int $decimals
	 *
	 * @return mixed
	 */
	public function getStartTime($returnRaw = false, int $decimals = 6)
	{
		if ($returnRaw)
		{
			return $this->startTime;
		}

		return number_format($this->startTime, $decimals);
	}

	//--------------------------------------------------------------------
	/**
	 * Returns the duration of this query during execution, or null if
	 * the query has not been executed yet.
	 *
	 * @param int $decimals The accuracy of the returned time.
	 *
	 * @return mixed
	 */
	public function getDuration(int $decimals = 6)
	{
		return number_format(($this->endTime - $this->startTime), $decimals);
	}

	//--------------------------------------------------------------------

	/**
	 * Stores the error description that happened for this query.
	 */
	public function setError(int $code, string $error)
	{
		$this->errorCode   = $code;
		$this->errorString = $error;

		return $this;
	}

	//--------------------------------------------------------------------

	/**
	 * Reports whether this statement created an error not.
	 *
	 * @return bool
	 */
	public function hasError(): bool
	{
		return ! empty($this->errorString);
	}

	//--------------------------------------------------------------------

	/**
	 * Returns the error code created while executing this statement.
	 *
	 * @return string
	 */
	public function getErrorCode(): int
	{
		return $this->errorCode;
	}

	//--------------------------------------------------------------------

	/**
	 * Returns the error message created while executing this statement.
	 *
	 * @return string
	 */
	public function getErrorMessage(): string
	{
		return $this->errorString;
	}

	//--------------------------------------------------------------------

	/**
	 * Determines if the statement is a write-type query or not.
	 *
	 * @return bool
	 */
	public function isWriteType(): bool
	{
		return (bool)preg_match(
			'/^\s*"?(SET|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|TRUNCATE|LOAD|COPY|ALTER|RENAME|GRANT|REVOKE|LOCK|UNLOCK|REINDEX)\s/i',
			$this->originalQueryString);
	}

	//--------------------------------------------------------------------

	/**
	 * Swaps out one table prefix for a new one.
	 *
	 * @param string $orig
	 * @param string $swap
	 *
	 * @return mixed
	 */
	public function swapPrefix(string $orig, string $swap)
	{
		$sql = empty($this->finalQueryString) ? $this->originalQueryString : $this->finalQueryString;

		$this->finalQueryString = preg_replace('/(\W)'.$orig.'(\S+?)/', '\\1'.$swap.'\\2', $sql);

		return $this;
	}

	//--------------------------------------------------------------------

	/**
	 * Returns the original SQL that was passed into the system.
	 *
	 * @return string
	 */
	public function getOriginalQuery()
	{
	    return $this->originalQueryString;
	}

	//--------------------------------------------------------------------

	/**
	 * Escapes and inserts any binds into the finalQueryString object.
	 */
	protected function compileBinds()
	{
		$sql = $this->finalQueryString;

		$hasNamedBinds  = strpos($sql, ':') !== false;

		if (empty($this->binds) || empty($this->bindMarker) ||
		    (strpos($sql, $this->bindMarker) === false &&
		     $hasNamedBinds === false)
		)
		{
			return;
		}

		if ( ! is_array($this->binds))
		{
			$binds     = [$this->binds];
			$bindCount = 1;
		}
		else
		{
			$binds     = $this->binds;
			$bindCount = count($binds);
		}

		// Reverse the binds so that duplicate named binds
		// will be processed prior to the original binds.
		if (! is_numeric(key(array_slice($binds, 0, 1))))
		{
			$binds = array_reverse($binds);
		}

		// We'll need marker length later
		$ml = strlen($this->bindMarker);

		if ($hasNamedBinds)
		{
			$sql = $this->matchNamedBinds($sql, $binds);
		}
		else
		{
			$sql = $this->matchSimpleBinds($sql, $binds, $bindCount, $ml);
		}

		$this->finalQueryString = $sql;
	}

	//--------------------------------------------------------------------

	protected function matchNamedBinds(string $sql, array $binds)
	{
		foreach ($binds as $placeholder => $value)
		{
			$escapedValue = $this->db->escape($value);
			if (is_array($escapedValue))
			{
				$escapedValue = '('.implode(',', $escapedValue).')';
			}
			$sql = str_replace(':'.$placeholder, $escapedValue, $sql);
		}

		return $sql;
	}

	//--------------------------------------------------------------------

	protected function matchSimpleBinds(string $sql, array $binds, int $bindCount, int $ml)
	{
		// Make sure not to replace a chunk inside a string that happens to match the bind marker
		if ($c = preg_match_all("/'[^']*'/i", $sql, $matches))
		{
			$c = preg_match_all('/'.preg_quote($this->bindMarker, '/').'/i',
				str_replace($matches[0],
					str_replace($this->bindMarker, str_repeat(' ', $ml), $matches[0]),
					$sql, $c),
				$matches, PREG_OFFSET_CAPTURE);

			// Bind values' count must match the count of markers in the query
			if ($bindCount !== $c)
			{
				return $sql;
			}
		}
		// Number of binds must match bindMarkers in the string.
		else if (($c = preg_match_all('/'.preg_quote($this->bindMarker, '/').'/i', $sql, $matches,
				PREG_OFFSET_CAPTURE)) !== $bindCount)
		{
			return $sql;
		}

		do
		{
			$c--;
			$escapedValue = $this->db->escape($binds[$c]);
			if (is_array($escapedValue))
			{
				$escapedValue = '('.implode(',', $escapedValue).')';
			}
			$sql = substr_replace($sql, $escapedValue, $matches[0][$c][1], $ml);
		}
		while ($c !== 0);

		return $sql;
	}

	//--------------------------------------------------------------------

}