<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramController extends Controller
{
    protected $botToken;
    protected $groqKey;

    public function __construct()
    {
        $this->botToken = config('services.telegram.token');
        $this->groqKey = config('services.groq.key');
    }

    public function webhook(Request $request)
    {
        $update = $request->all();
        \Log::info("Telegram Signal Received: " . json_encode($update));
        if (!isset($update['message'])) return response()->json(['status' => 'ok']);

        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $user = DB::table('users')->where('telegram_chat_id', $chatId)->first();

        // 1. Handle Unlinked Users (Link via Sync Code)
        if (!$user) {
            $code = null;
            if (str_starts_with($text, '/start ')) {
                $code = substr($text, 7);
            } elseif (strlen($text) === 6 && is_numeric($text)) {
                $code = $text;
            }

            if ($code) {
                $linkedUser = DB::table('users')->where('telegram_sync_code', $code)->first();
                if ($linkedUser) {
                    DB::table('users')->where('id', $linkedUser->id)->update([
                        'telegram_chat_id' => $chatId,
                        'telegram_sync_code' => null
                    ]);
                    $this->sendMessage($chatId, "✨ *Neural Link Successful!*\n\nWelcome, *{$linkedUser->name}*. Your brain is now synchronized with Personal Memory OS.\n\nYou can now send me:\n🎤 Voice Notes\n📄 Receipts/Photos\n💡 Thoughts & Tasks");
                    return response()->json(['status' => 'ok']);
                }
            }

            if (str_starts_with($text, '/start')) {
                $this->sendMessage($chatId, "👋 Welcome to *Personal Memory OS*.\n\nTo link your account, please send me the *6-digit Sync Code* from your web dashboard.");
            } else {
                $this->sendMessage($chatId, "⚠️ *System Unlinked.* Any data sent now will not be stored.\n\nPlease send your *Sync Code* to establish a connection.");
            }
            return response()->json(['status' => 'ok']);
        }

        // 2. Handle Commands
        if (str_starts_with($text, '/start')) {
            $this->sendMessage($chatId, "🧠 *Memory OS Online.* System stable.\n\nI am monitoring your inputs. You can speak to me or send data anytime.");
            return response()->json(['status' => 'ok']);
        }

        if (str_starts_with($text, '/status')) {
            $this->sendMessage($chatId, "📡 *Neural Status Report:*\n- Connectivity: OPTIMAL\n- User: {$user->name}\n- Mode: Active Sync");
            return response()->json(['status' => 'ok']);
        }

        // 3. Handle Voice Messages (Whisper)
        if (isset($message['voice'])) {
            $this->handleVoice($chatId, $message['voice'], $user);
            return response()->json(['status' => 'ok']);
        }

        // 4. Handle Photos (Vision)
        if (isset($message['photo'])) {
            $this->handlePhoto($chatId, $message['photo'], $user);
            return response()->json(['status' => 'ok']);
        }

        // 5. Handle Text Messages
        if ($text) {
            $this->processNaturalLanguage($chatId, $text, $user);
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleVoice($chatId, $voice, $user)
    {
        $this->sendMessage($chatId, "⏳ Processing Neural Frequency...");
        
        $fileId = $voice['file_id'];
        $fileResponse = Http::get("https://api.telegram.org/bot{$this->botToken}/getFile?file_id={$fileId}");
        $filePath = $fileResponse->json()['result']['file_path'];
        $fileUrl = "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}";

        $audioContent = Http::get($fileUrl)->body();
        $tempPath = 'temp_voice_' . time() . '.ogg';
        Storage::disk('local')->put($tempPath, $audioContent);
        $absolutePath = storage_path('app/' . $tempPath);

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $this->groqKey])
                ->attach('file', fopen($absolutePath, 'r'), 'voice.ogg')
                ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                    'model' => 'whisper-large-v3',
                    'language' => 'ar', // or auto
                    'response_format' => 'json'
                ]);

            if ($response->successful()) {
                $transcript = $response->json()['text'];
                $this->sendMessage($chatId, "🎧 Neural Transcript: \"$transcript\"");
                $this->processNaturalLanguage($chatId, $transcript, $user);
            } else {
                Log::error("Telegram Whisper Error: " . $response->body());
                $this->sendMessage($chatId, "❌ Failed to decode neural signal. (Check Groq API)");
            }
        } catch (\Exception $e) {
            Log::error("Telegram Voice processing error: " . $e->getMessage());
            $this->sendMessage($chatId, "❌ Neural frequency error.");
        } finally {
            if (file_exists($absolutePath)) unlink($absolutePath);
        }
    }

    private function handlePhoto($chatId, $photo, $user)
    {
        $this->sendMessage($chatId, "⏳ Analyzing Neural Image...");
        $fileId = end($photo)['file_id']; // Best quality
        $fileResponse = Http::get("https://api.telegram.org/bot{$this->botToken}/getFile?file_id={$fileId}");
        $filePath = $fileResponse->json()['result']['file_path'];
        $fileUrl = "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}";

        $imageData = base64_encode(Http::get($fileUrl)->body());

        $prompt = "Analyze this image for Personal Memory OS. 
        If it is a receipt or involve money, extract amount and category.
        If it's an idea or note, describe it.
        RESPOND ONLY WITH JSON:
        { \"type\": \"money|idea|unknown\", \"data\": { ... }, \"reply\": \"Simple response in Arabic\" }";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->groqKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.2-11b-vision-preview',
                'messages' => [
                    [
                        'role' => 'user', 
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$imageData}"]]
                        ]
                    ]
                ],
                'response_format' => ['type' => 'json_object']
            ]);

            if ($response->successful()) {
                $res = json_decode($response->json()['choices'][0]['message']['content'], true);
                $this->commitToDatabase($chatId, $res, $user);
            } else {
                $this->sendMessage($chatId, "❌ Optical sensors failed.");
            }
        } catch (\Exception $e) {
            $this->sendMessage($chatId, "❌ Image processing error.");
        }
    }

    private function processNaturalLanguage($chatId, $text, $user)
    {
        $prompt = "Analyze: \"$text\". Extract data for money/task/idea. RESPOND ONLY JSON:
        { \"type\": \"money|task|idea|unknown\", \"data\": { ... }, \"reply\": \"Cool reply in Arabic\" }";

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $this->groqKey])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are the Memory OS Neural Hub. Extract data from user input into JSON. If income/expense, use type "money". If mission/todo, use type "task". If concept/thought, use type "idea". RESPOND ONLY WITH JSON.'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'response_format' => ['type' => 'json_object']
                ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                // Safety check for raw content
                $cleanJson = preg_replace('/^[^{]*({.*})[^}]*$/s', '$1', $content);
                $res = json_decode($cleanJson, true);
                
                if ($res) {
                    $this->commitToDatabase($chatId, $res, $user);
                } else {
                    $this->sendMessage($chatId, "⚠️ Neural signal received but misunderstood.");
                }
            }
        } catch (\Exception $e) {
            Log::error("Telegram NL Processing Error: " . $e->getMessage());
            $this->sendMessage($chatId, "❌ AI processing failed.");
        }
    }

    private function commitToDatabase($chatId, $res, $user)
    {
        $type = $res['type'] ?? 'unknown';
        $data = $res['data'] ?? [];
        $reply = $res['reply'] ?? 'تم الاستيعاب.';

        if ($type === 'money') {
            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'amount' => $data['amount'] ?? 0,
                'type' => $data['type'] ?? 'expense',
                'category' => $data['category'] ?? 'عام',
                'description' => $data['description'] ?? 'عبر التلغرام',
                'created_at' => now(), 'updated_at' => now()
            ]);
        } elseif ($type === 'task') {
            DB::table('tasks')->insert([
                'user_id' => $user->id,
                'title' => $data['title'] ?? 'مهمة جديدة',
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now()
            ]);
        } elseif ($type === 'idea') {
            DB::table('ideas')->insert([
                'user_id' => $user->id,
                'content' => $data['content'] ?? 'فكرة من تليجرام',
                'status' => 'draft', 'category' => 'ذكية', 'created_at' => now(), 'updated_at' => now()
            ]);
        }

        $this->sendMessage($chatId, $reply);
    }

    private function sendMessage($chatId, $text)
    {
        Http::post("https://api.telegram.org/bot" . config('services.telegram.token') . "/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ]);
    }
}
