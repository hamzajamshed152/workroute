<?php

namespace App\Jobs;

use App\Models\JobWork;
use App\Models\Tradie;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use OpenAI;

class ProcessRecordingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $recordingUrl,
        public string $from
    ) {}

    public function handle()
    {
        $audio = file_get_contents(
            $this->recordingUrl . '.wav',
            false,
            stream_context_create([
                'http' => [
                    'header' => 'Authorization: Basic ' . base64_encode(
                        config('services.twilio.sid') . ':' . config('services.twilio.token')
                    )
                ]
            ])
        );

        $client = OpenAI::client(config('services.openai.key'));

        // 1️⃣ Whisper Transcription
        $transcript = $client->audio()->transcriptions()->create([
            'file' => $audio,
            'model' => 'whisper-1',
        ])->text;

        // 2️⃣ GPT Extraction
        $result = $client->chat()->completions()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Extract job info as JSON'
                ],
                [
                    'role' => 'user',
                    'content' => $transcript
                ],
            ],
        ]);

        $data = json_decode(
            $result->choices[0]->message->content,
            true
        );

        $tradie = Tradie::where('id',1)->first();

        // 3️⃣ Job Creation
        JobWork::create([
            'tradie_id' => $tradie->id,
            'customer_name' => $data['name'] ?? null,
            'customer_phone' => $this->from,
            'service_type' => $data['service'] ?? null,
            'location' => $data['location'] ?? null,
            'urgency' => $data['urgency'] ?? 'normal',
            'raw_transcript' => $transcript,
        ]);
    }
}
