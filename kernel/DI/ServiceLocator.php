<?php

namespace App\Kernel\DI;

use App\Kernel\Exceptions\ClassNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

class ServiceLocator implements ContainerInterface
{
    private array $objects = [];
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function has(string $id): bool
    {
        return isset($this->objects[$id]) || class_exists($id);
    }

    /**
     * @throws ClassNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new ClassNotFoundException($id);
        }

        return $this->objects[$id] ?? $this->prepareObject($id);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface|ClassNotFoundException
     */
    private function prepareObject(string $class): object
    {
        $classReflector = new ReflectionClass($class);

        if (!$classReflector->isInstantiable()) {
            throw new ReflectionException("Class '$class' is not instantiable.");
        }

        $constructReflector = $classReflector->getConstructor();
        if (empty($constructReflector)) {
            $instance = new $class();
            $this->objects[$class] = $instance; // Сохраняем в контейнер
            return $instance;
        }

        $constructArguments = $constructReflector->getParameters();
        if (empty($constructArguments)) {
            $instance = new $class();
            $this->objects[$class] = $instance;
            return $instance;
        }

        $args = [];
        foreach ($constructArguments as $argument) {
            $type = $argument->getType();

            // Если тип не указан
            if ($type === null) {
                if ($argument->isDefaultValueAvailable()) {
                    $args[$argument->getName()] = $argument->getDefaultValue();
                } else {
                    throw new ReflectionException(
                        "Parameter '{$argument->getName()}' in '$class' has no type hint or default value."
                    );
                }
                continue;
            }

            // Проверяем, что тип — это ReflectionNamedType
            if (!$type instanceof ReflectionNamedType) {
                throw new ReflectionException(
                    "Parameter '{$argument->getName()}' in '$class' uses unsupported type (union/intersection)."
                );
            }

            $typeName = $type->getName();

            // Если это встроенный тип (string, int, array и т.д.)
            if ($type->isBuiltin()) {
                if ($argument->isDefaultValueAvailable()) {
                    $args[$argument->getName()] = $argument->getDefaultValue();
                } else {
                    throw new ReflectionException(
                        "Parameter '{$argument->getName()}' in '$class' is a built-in type '$typeName' without a default value."
                    );
                }
            } else {
                // Это класс или интерфейс, разрешаем зависимость
                $args[$argument->getName()] = $this->get($typeName);
            }
        }

        $instance = new $class(...$args);
        $this->objects[$class] = $instance; // Сохраняем как singleton
        return $instance;
    }
}