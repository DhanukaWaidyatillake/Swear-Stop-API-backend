<?php

namespace App\Listeners;

use App\Events\SaveTextFilterResponseEvent;
use App\Models\TextFilterAudit;

class SaveTextFilterResponseListener
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        TextFilterAudit::find($event->request->text_filter_audit_id)->update([
            'response_body' => json_encode($event->response, JSON_PRETTY_PRINT),
            'is_successful' => true
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(SaveTextFilterResponseEvent $event, \Throwable $exception): void
    {

    }
}
