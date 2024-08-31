<?php

namespace App\Listeners;

use App\Events\SaveTextFilterResponseEvent;
use App\Models\TextFilterAudit;
use Illuminate\Contracts\Queue\ShouldQueue;

class SaveTextFilterResponseListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        //Obtaining the users free request count
        $free_request_count = $event->user->free_request_count;

        $profanity_caught = [];
        $profanity_categories = [];

        foreach ($event->response['profanity']['words'] as $category_key => $category) {
            foreach ($category as $item) {
                $profanity_categories[] = $category_key;
                $profanity_caught[] = $item['flagged_word'];
            }
        }

        $profanity_caught = implode(',', $profanity_caught);
        $profanity_categories = implode(',', array_unique($profanity_categories));

        //Updating the text filter audit
        TextFilterAudit::query()->find($event->request['text_filter_audit_id'])->update([
            'response_body' => json_encode($event->response, JSON_PRETTY_PRINT),
            'is_successful' => true,
            'is_free_request' => ($free_request_count != 0),
            'profanity_caught' => empty($profanity_caught) ? null : $profanity_caught,
            'profanity_categories_caught' => empty($profanity_categories) ? null : $profanity_categories,
        ]);

        //Decrementing the free requests count if not 0
        if ($free_request_count != 0) {
            $event->user->decrement('free_request_count');
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(SaveTextFilterResponseEvent $event, \Throwable $exception): void
    {

    }
}
