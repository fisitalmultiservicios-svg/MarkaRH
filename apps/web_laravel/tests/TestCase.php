<?php

namespace Tests;

use App\Models\User;
use App\Services\MfaSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get('database.connections.'.$connection.'.database');
        if (! $app->environment('testing') || ! (($connection === 'sqlite' && $database === ':memory:') || ($connection === 'mysql' && $database === 'markarh_test'))) {
            throw new RuntimeException('Destino de pruebas rechazado: '.$app->environment().' / '.$connection.' / '.$database);
        }

        return $app;
    }

    /** An MFA-authenticated fixture for domain tests; full flows live in MfaTest. */
    protected function asVerifiedMaster(User $master): static
    {
        $this->actingAs($master)->withSession([]);
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session.store']);
        MfaSession::complete($request, $master);

        return $this;
    }
}
