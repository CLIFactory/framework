<?php

namespace CLIFactory {

    use CLIFactory\Helpers\Cls;
    use Symfony\Component\Console;
    use CLIFactory\Contracts;
    use CLIFactory\Exceptions;

    readonly class Bootstrap
    {
        /** @var Console\Application $application Symfony application */
        private Console\Application $application;

        /** @var \CLIFactory\Container $container PSR- Container */
        private Container $container;

        /**
         * @param \CLIFactory\Contracts\BaseConfigInterface $config
         */
        public function __construct(private Contracts\BaseConfigInterface $config)
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
         */
        public function run(): void
        {
            // let's make sure that the config class extends BaseConfig ...
            if (Cls::extends($this->config::class, BaseConfig::class)) {
                // loop through the commands in the $commands array
                // within the config class
                foreach ($this->config->commands() as $name => $class) {
                    // add a new service to the psr container
                    $this->setContainerItem($name, $class);

                    // configure the symfony command
                    $this->application->add($this->getContainerItem($name));
                }

                // build and run the symfony application
                $this->build();
            }

            // ... if it doesn't, we need to throw a configuration error
            throw new Exceptions\ConfigurationException(
                'Configuration class must extend ' . BaseConfig::class
            );
        }

        /**
         * Build the symfony application
         *
         * @return void
         */
        private function build(): void
        {
            $this->application->setName($this->config->name());
            $this->application->setVersion($this->config->version());
            $this->application->run();
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
    }
}
