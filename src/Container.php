<?php

namespace CLIFactory;

use ReflectionClass;
use ReflectionException;
use Psr\Container\ContainerInterface;
use CLIFactory\Exceptions\NotFoundException;

class Container implements ContainerInterface
{
	private array $services = [];
	
	/**
	 * {@inheritDoc}
	 *
	 * @throws \ReflectionException
	 */
	public function get(string $id)
	{
		$item = $this->resolve($id);
		if (!$item instanceof ReflectionClass) {
			return $item;
		}
		return $this->getInstance($item);
	}
	
	/**
	 * @throws \CLIFactory\Exceptions\NotFoundException
	 */
	private function resolve($id): mixed
	{
		try {
			$name = $id;
			if (isset($this->services[$id])) {
				$name = $this->services[$id];
				if (is_callable($name)) {
					return $name();
				}
			}
			return (new ReflectionClass($name));
		} catch (ReflectionException $e) {
			throw new NotFoundException($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	/**
	 * @param \ReflectionClass $item
	 *
	 * @return object|null
	 *
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 * @throws \ReflectionException
	 */
	private function getInstance(ReflectionClass $item): ?object
	{
		$constructor = $item->getConstructor();
		if (is_null($constructor) || $constructor->getNumberOfRequiredParameters() === 0) {
			return $item->newInstance();
		}
		$params = [];
		foreach ($constructor->getParameters() as $param) {
			if ($type = $param->getType()) {
				$params[] = $this->get($type->getName());
			}
		}
		return $item->newInstanceArgs($params);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function has($id): bool
	{
		try {
			$item = $this->resolve($id);
		} catch (NotFoundException) {
			return false;
		}
		return $item->isInstantiable();
	}
	
	public function set(string $key, $value): self
	{
		$this->services[$key] = $value;
		return $this;
	}
}
