<?php

namespace Rais\MomoSuite;

use Illuminate\Support\Manager;
use Rais\MomoSuite\Providers\KorbaProvider;
use Rais\MomoSuite\Providers\ItcProvider;
use Rais\MomoSuite\Providers\HubtelProvider;
use Rais\MomoSuite\Providers\PaystackProvider;

class MomoSuite extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('momo-suite.default');
    }

    protected function createKorbaDriver()
    {
        return new KorbaProvider($this->config->get('momo-suite.providers.korba'));
    }

    protected function createItcDriver()
    {
        return new ItcProvider($this->config->get('momo-suite.providers.itc'));
    }

    protected function createHubtelDriver()
    {
        return new HubtelProvider($this->config->get('momo-suite.providers.hubtel'));
    }

    protected function createPaystackDriver()
    {
        return new PaystackProvider($this->config->get('momo-suite.providers.paystack'));
    }
}
