<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin client for the Replicate prediction API. Runs an official model and
 * returns the URLs of the files it produced. The "Prefer: wait" header lets
 * fast models (FLUX schnell) return in a single request; a short poll loop is
 * kept as a fallback for the rare prediction that is still running.
 */
class ReplicateClient
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<int, string>
     */
    public function run(string $model, array $input): array
    {
        $prediction = Http::withToken((string) config('services.replicate.token'))
            ->baseUrl((string) config('services.replicate.base_url'))
            ->withHeaders(['Prefer' => 'wait'])
            ->timeout(120)
            ->post("/models/{$model}/predictions", ['input' => $input])
            ->throw()
            ->json();

        $prediction = $this->awaitCompletion($prediction);

        if (($prediction['status'] ?? null) !== 'succeeded') {
            throw new RuntimeException('Replicate prediction failed: '.($prediction['error'] ?? 'unknown error'));
        }

        $output = $prediction['output'] ?? [];
        $output = is_array($output) ? $output : [$output];

        return array_values(array_filter($output, 'is_string'));
    }

    /**
     * Poll the prediction until it reaches a terminal state, in case the
     * "Prefer: wait" window elapsed before the model finished.
     *
     * @param  array<string, mixed>  $prediction
     * @return array<string, mixed>
     */
    private function awaitCompletion(array $prediction): array
    {
        $terminal = ['succeeded', 'failed', 'canceled'];
        $getUrl = $prediction['urls']['get'] ?? null;

        for ($attempt = 0; ! in_array($prediction['status'] ?? '', $terminal, true) && is_string($getUrl) && $attempt < 30; $attempt++) {
            sleep(2);

            $prediction = Http::withToken((string) config('services.replicate.token'))
                ->timeout(30)
                ->get($getUrl)
                ->throw()
                ->json();
        }

        return $prediction;
    }
}
