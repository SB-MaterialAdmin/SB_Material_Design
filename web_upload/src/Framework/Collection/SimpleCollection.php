<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Collection;

class SimpleCollection extends AbstractCollection
{
	use MultiOperationTrait;

	/**
	 * Constructor for \Framework\Collection\SimpleCollection
	 *
	 * @var	array	$data	Source data.
	 * @var	integer	$depth	If required, all included arrays can be transformed to collections too.
	 */
	public function __construct(array $data = [], $depth = 0)
	{
		if ($depth > 0)
		{
			$_data = [];
			foreach ($data as $key => $value)
			{
				if (!is_array($value))
				{
					$_data[$key] = $value;
					continue;
				}

				$_data[$key] = new self($value, max($depth-1, -1));
			}

			$data = $_data;
		}

		parent::__construct($data);
	}
}
