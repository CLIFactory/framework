<?php

namespace CLIFactory {

    // imports
    use CLIFactory\Helpers\Cls;
    use Symfony\Component\Console;
    use CLIFactory\Contracts\BaseConfigInterface;
    use CLIFactory\Exceptions\ConfigurationException;

    readonly class Bootstrap
    {
        /** @var Console\Application $application Symfony application */
        private Console\Application $application;

        /** @var \CLIFactory\Container $container PSR- Container */
        private Container $container;


        /**
         * @param \CLIFactory\Contracts\BaseConfigInterface $config
         */
        public function __construct(private BaseConfigInterface $config)
        {
            // symfony application
            $this->application = new Console\Application();

            // psr container
            $this->container = new Container();
        }

        /**
         * The main entry point to the bootstrap process
         *
         * @return void
         *
         * @throws \Psr\Container\ContainerExceptionInterface
         * @throws \Psr\Container\NotFoundExceptionInterface
         * @throws \ReflectionException
         * @throws \Exception
         */
        public function run(): void
        {
            // let's make sure that the config class extends BaseConfig ...
            if (Cls::extends($this->config::class, BaseConfig::class)) {
// loop through the commands in the $commands array
                // of the config class
                foreach ($this->config->commands() as $name => $class) {
// add a new service to the psr container
                    $this->setContainerItem($name, $class);

                    // configure the symfony application
                    $this->buildApplication($name);
                }

                // run the symfony application
                $this->runApplication();
            }

            // ... if it doesn't, we need to throw a configuration error
            $this->throwConfigurationException();
        }

        /**
         * Sets an item in the PSR container
         *
         * @param $name
         * @param $class
         *
         * @return void
         */
        private function setContainerItem($name, $class): void
        {
            $this->container->set($name, new $class());
        }

        /**
         * Build the symfony application
         *
         * @param $name
         *
         * @return void
         *
         * @throws \Psr\Container\ContainerExceptionInterface
         * @throws \Psr\Container\NotFoundExceptionInterface
         * @throws \ReflectionException
         */
        private function buildApplication($name): void
        {
            $this->application->setName($this->config->name);
            $this->application->setVersion($this->config->version);
            $this->application->add($this->getContainerItem($name));
        }

        /**
         * Gets an item from the PSR container
         *
         * @param $name
         *
         * @return mixed
         *
         * @throws \Psr\Container\ContainerExceptionInterface
         * @throws \Psr\Container\NotFoundExceptionInterface
         * @throws \ReflectionException
         */
        private function getContainerItem($name): mixed
        {
            return $this->container->get($name);
        }

        /**
         * Runs the symfony application
         *
         * @return void
         *
         * @throws \Exception
         */
        private function runApplication(): void
        {
            $this->application->run();
        }

        /**
         * Throws a configuration exception
         *
         * @return void
         */
        private function throwConfigurationException(): void
        {
            throw new ConfigurationException('Configuration class must extend ' . BaseConfig::class);
        }
    }

}
