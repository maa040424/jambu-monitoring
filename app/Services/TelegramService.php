<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $botToken;
    protected string $chatId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', '');
        $this->chatId   = config('services.telegram.chat_id', '');
    }

    /**
     * Kirim pesan teks ke Telegram.
     */
    public function sendMessage(string $message): bool
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            Log::warning('Telegram: bot_token atau chat_id belum diatur di .env');
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        try {
            $response = Http::post($url, [
                'chat_id'    => $this->chatId,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                Log::info('Telegram: Notifikasi berhasil dikirim.');
                return true;
            }

            Log::error('Telegram: Gagal mengirim notifikasi.', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Telegram: Exception saat mengirim notifikasi.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Kirim file/dokumen ke Telegram.
     */
    public function sendDocument(string $fileContent, string $filename, string $caption = ''): bool
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            Log::warning('Telegram: bot_token atau chat_id belum diatur di .env');
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendDocument";

        try {
            $response = Http::attach(
                'document', $fileContent, $filename
            )->post($url, [
                'chat_id'    => $this->chatId,
                'caption'    => $caption,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                Log::info('Telegram: File backup berhasil dikirim.');
                return true;
            }

            Log::error('Telegram: Gagal mengirim file backup.', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Telegram: Exception saat mengirim file backup.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
