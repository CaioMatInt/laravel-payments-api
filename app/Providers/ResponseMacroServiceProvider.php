<?php

namespace App\Providers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setSuccessResponse();
        $this->setFailureResponse();
    }

    public function setSuccessResponse()
    {
        Response::macro('success', function (
            int $code = 200,
            array $data = [],
            string $message = null
        ) {
            $response = [
                "success" => "true",
                "code" => $code,
                "message" => $message,
                "data" => $data
            ];

            return response($response, $code);
        });
    }

    public function setFailureResponse()
    {
        Response::macro('failure', function (
            int $code = 500,
            string $message = null
        ) {
            $response = [
                "success" => "false",
                "code" => $code,
                "message" => $message
            ];

            return response($response, $code);
        });
    }
}
