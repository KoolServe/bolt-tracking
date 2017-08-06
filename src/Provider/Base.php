<?php

namespace Bolt\Extension\Koolserve\Tracking\Provider;

use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base Provider for tracking scripts
 *
 * @author Chris Hilsdon <chris@koolserve.uk>
 */
abstract class Base
{
    /**
     * @var \Silex\Application
     */
    protected $app;

    /**
     * Provider name
     * @var string
     */
    protected $name;

    /**
     * Required config variables for the provider to run
     * @var array
     */
    protected $requiredConfigKeys = [];

    /**
     * URL of the script to fetch
     * @var string
     */
    protected $remoteScript;

    /**
     * Prefix for routes
     * @var string
     */
    private $trackingBaseUrl = '/tracking/';

    /**
     * Get provider name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get required config keys
     * @return array
     */
    public function getRequiredConfigKeys()
    {
        return $this->requiredConfigKeys;
    }

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register route with a name
     * @param  ControllerCollection $collection
     * @return ControllerCollection
     */
    public function registerRoute(ControllerCollection $collection)
    {
        $path = $this->fetchScriptRoute();
        $name = $this->fetchScriptRouteName();
        $collection->get($path, [$this, 'fetchProxyScript'])
            ->bind($name);

        return $collection;
    }

    /**
     * Get the config for this provider
     * @return array
     */
    public function fetchConfig()
    {
        return $this->app['tracking.config']['providers'][$this->getName()];
    }

    /**
     * Check if this provider is enabled (in the list of configured providers)
     * @return boolean
     */
    public function isEnabled()
    {
        if ($this->app['tracking.config']['providers'] === null) {
            return false;
        }

        $name = $this->getName();
        if (array_key_exists($name, $this->app['tracking.config']['providers'])) {
            return true;
        }

        return false;
    }

    /**
     * Checks that the configuration for this provider is correct and that each required key has a value
     * @return boolean
     */
    public function isConfigured()
    {
        $config = $this->fetchConfig();
        foreach ($this->getRequiredConfigKeys() as $value) {
            if (array_key_exists($value, $config) && $config[$value] !== null) {
                continue;
            }

            $message = 'Config value "'. $value .'" is not set or is null. Skipping provider "' . $this->getName() . '"';
            $this->app['logger.system']->warning($message, ['event' => 'extension']);

            return false;
        }

        return true;
    }

    /**
     * Fetch the snippet to add the the page
     * @return string
     */
    public function fetchSnippet()
    {
        $config = $this->fetchConfig();
        $data = [];

        return $this->renderSnippet($data);
    }

    /**
     * Render the script snippet via twig
     * @param  array $data [description]
     * @return string
     */
    protected function renderSnippet($data)
    {
        $template = '@tracking/providers/' . $this->getName() . '.twig';
        $data = $data + [
            'script' => $this->app['url_generator']->generate(
                $this->fetchScriptRouteName(),
                [],
                $this->app["url_generator"]::ABSOLUTE_URL
            )
        ];

        return $this->app['twig']->render($template, $data);
    }

    /**
     * Return the route for the proxy
     * @return string
     */
    public function fetchScriptRoute()
    {
        return $this->trackingBaseUrl . strtolower($this->getName());
    }

    /**
     * Return the name given for the proxy route
     * @return string
     */
    public function fetchScriptRouteName()
    {
        return 'tracking_' . strtolower($this->getName());
    }

    /**
     * Fetch the tracking script and then create a response with *correct* headers
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fetchProxyScript()
    {
        $client = $this->app['guzzle.client'];
        $response = $client->get($this->remoteScript);
        $script = $response->getBody();

        // Return a new response and set the headers correctly
        $response = new Response($script . '');
        $response->headers->set('Content-Type', 'application/javascript');
        $response->headers->set('Cache-Control', 'max-age=86400');

        return $response;
    }
}
