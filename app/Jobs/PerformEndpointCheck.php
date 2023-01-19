<?php

namespace App\Jobs;

use App\Models\Endpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PerformEndpointCheck implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Endpoint $endpoint)
    {
        //
    }

    public function uniqueId()
    {
        return 'endpoint_' . $this->endpoint->id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // http request
        try {
            $response = Http::get($this->endpoint->url());
            Log::info($response->status());
        } catch (\Throwable $th) {
            //throw $th;
        }


        // create checks
        $this->endpoint->checks()->create([
            'response_code' => $response->status(),
            'response_body' => !$response->successful() ? $response->body() : null
        ]);

        // update endpoint with the next_check date
        $this->endpoint->update([
            'next_check' => now()->addSeconds($this->endpoint->frequency->value)
        ]);
    }
}