<?php

namespace Bolt\Extension\Koolserve\Tracking;

use Bolt\Asset\Target;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Controller\Zone;
use Bolt\Extension\SimpleExtension;
use Bolt\Extension\Koolserve\Tracking\Provider;
use Silex\Application;
use Silex\ControllerCollection;

/**
 * Tracking extension class.
 *
 * @author Chris Hilsdon <chris@koolserve.uk>
 */
class TrackingExtension extends SimpleExtension
{
    /**
     * Available tracking providers
     * @var array
     */
    protected $providers = [
        'GoogleAnalytics'
    ];

    /**
     * Providers that have been enabled
     * @var array|null
     */
    private $enabledProviders;

    /**
     * Get the enabled tracking providers
     * @return array|null
     */
    public function getEnabledProviders()
    {
        if ($this->enabledProviders === null) {
            $this->setEnabledProviders();
        }

        return $this->enabledProviders;
    }

    /**
     * Fetch the config and then check for enabled and configured providers
     */
    protected function setEnabledProviders()
    {
        $app = $this->getContainer();
        $enabled = [];

        foreach ($this->providers as $providerName) {
            $className = "Bolt\\Extension\\Koolserve\\Tracking\Provider\\" . $providerName;
            $provider = new $className($app);

            if ($provider->isEnabled() === false) {
                continue;
            }

            if ($provider->isConfigured() === false) {
                continue;
            }

            $enabled[] = $provider;
        }

        $this->enabledProviders = $enabled;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        $app['tracking.config'] = $app->share(function () {
            return $this->getConfig();
        });

        $app['tracking.providers'] = $app->share(function () {
            return $this->getEnabledProviders();
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function registerFrontendRoutes(ControllerCollection $collection)
    {
        $app = $this->getContainer();
        foreach ($app['tracking.providers'] as $provider) {
            $provider->registerRoute($collection);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return [
            'templates' => ['namespace' => 'tracking'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerAssets()
    {
        $app = $this->getContainer();
        $assets = [];
        foreach ($app['tracking.providers'] as $provider) {
            $asset = new Snippet();
            $asset->setCallback([$provider, 'fetchSnippet'])
                ->setLocation(Target::END_OF_BODY)
                ->setPriority(50)
                ->setZone(Zone::FRONTEND);
            ;

            $assets[] = $asset;
        }

        return $assets;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultConfig()
    {
        return [
            'providers' => []
        ];
    }
}
