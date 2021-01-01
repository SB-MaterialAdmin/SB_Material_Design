<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework;

// TODO: add loading all meta extensions from addons.
class ClassExtension
{
	/** @var array $classExtension */
	protected $classExtension = [];

	/** @var array $extensionMap */
	protected $extensionMap = [];

	/**
	 * @param $class
	 * @return string
	 * @throws \Exception
	 */
	public function extendClass($class)
	{
		$class = ltrim($class, '\\');
		if (!isset($this->extensionMap[$class]))
		{
			/** Maybe passed empty class name? */
			if (!$class)
			{
				return $class;
			}

			/** Check existing available class extensions. */
			$extensions = !empty($this->classExtension[$class]) ? $this->classExtension[$class] : [];
			$extension = $class;
			if (count($extensions) > 0)
			{
				if (class_exists($class))
				{
					try
					{
						foreach ($extensions as $extensionClass)
						{
							if (preg_match('/[;,$\/#"\'\.()]/', $extensionClass))
							{
								continue;
							}

							// FWCP - framework class proxy.
							$nsSplit = strrpos($extensionClass, '\\');
							if ($nsSplit !== false && $ns = substr($extensionClass, 0, $nsSplit))
							{
								$proxyClass = $ns . '\\FWCP_' . substr($extensionClass, $nsSplit + 1);
							}
							else
							{
								$proxyClass = 'FWCP_' . $extensionClass;
							}

							class_alias($extension, $extensionClass);
							$finalClass = $extensionClass;

							if (!class_exists($finalClass))
							{
								throw new \Exception("Could not find class $extensionClass when attempting to extend $class");
							}
						}
					}
					catch (\Exception $e)
					{
						$this->extensionMap[$class] = $class;
						throw $e;
					}
				}
			}

			$this->extensionMap[$class] = $extension;
		}

		return $this->extensionMap[$class];
	}
}