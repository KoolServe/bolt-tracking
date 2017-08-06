<?php

namespace Bolt\Extension\Koolserve\Tracking\Provider;

use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Google Analytics Provider
 *
 * @author Chris Hilsdon <chris@koolserve.uk>
 */
class GoogleAnalytics extends Base
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'GoogleAnalytics';

    /**
     * {@inheritdoc}
     */
    protected $requiredConfigKeys = [
        'account'
    ];

    /**
     * {@inheritdoc}
     */
    protected $remoteScript = 'https://ssl.google-analytics.com/ga.js';

    /**
     * {@inheritdoc}
     */
    public function fetchSnippet()
    {
        $config = $this->fetchConfig();
        $data = [
            'account' => $config['account'],
        ];

        return $this->renderSnippet($data);
    }
}
