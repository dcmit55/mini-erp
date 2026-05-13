<?php

namespace App\Jobs;

use App\Services\Lark\LarkApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fire-and-forget job: pre-warm Lark media proxy cache for a set of URLs.
 *
 * Dispatched from the Costing Report page so the HTTP calls to Lark API
 * happen AFTER the page response is sent to the browser — not blocking it.
 */
class PrewarmLarkMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    /** @param string[] $larkUrls */
    public function __construct(public readonly array $larkUrls) {}

    public function handle(LarkApiClient $client): void
    {
        if (empty($this->larkUrls)) {
            return;
        }

        $client->prewarmBatch(array_unique($this->larkUrls));
    }
}
