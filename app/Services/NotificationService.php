<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Send order pending notification via WhatsApp and Email
     *
     * @param array $orderData
     * @return void
     */
    public function sendOrderPendingNotification(array $orderData): void
    {
        try {
            // Send WhatsApp
            $this->sendOrderPendingWhatsApp($orderData);

            // Send Email
            $this->sendOrderPendingEmail($orderData);
        } catch (\Throwable $e) {
            Log::error('Send order pending notification failed', [
                'order_number' => $orderData['order_number'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send order success notification via WhatsApp and Email
     *
     * @param array $orderData
     * @return void
     */
    public function sendOrderSuccessNotification(array $orderData): void
    {
        try {
            // Send WhatsApp
            $this->sendOrderSuccessWhatsApp($orderData);

            // Send Email
            $this->sendOrderSuccessEmail($orderData);
        } catch (\Throwable $e) {
            Log::error('Send order success notification failed', [
                'order_number' => $orderData['order_number'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send order pending WhatsApp via Fonnte
     *
     * @param array $orderData
     * @return void
     */
    private function sendOrderPendingWhatsApp(array $orderData): void
    {
        $phone = $orderData['phone'];
        $orderNumber = $orderData['order_number'];
        $grandTotal = number_format($orderData['grand_total'], 0, ',', '.');
        $paymentMethod = $orderData['payment_method'];
        $expiryTime = $orderData['expiry_time'] ?? null;

        $message = "🛍️ *Pesanan Berhasil Dibuat*\n\n"
            . "Halo {$orderData['customer_name']},\n\n"
            . "Pesanan kamu telah berhasil dibuat!\n\n"
            . "📦 *Detail Pesanan:*\n"
            . "No. Pesanan: *{$orderNumber}*\n"
            . "Total Pembayaran: *Rp {$grandTotal}*\n"
            . "Metode Pembayaran: *{$paymentMethod}*\n\n";

        if ($expiryTime) {
            $message .= "⏰ *Batas Waktu Pembayaran:*\n"
                . "{$expiryTime}\n\n";
        }

        $message .= "💳 *Cara Pembayaran:*\n"
            . $this->getPaymentInstructions($orderData)
            . "\n\n"
            . "Terima kasih telah berbelanja di XORIX! 🙏";

        $this->sendWhatsApp($phone, $message);
    }

    /**
     * Send order success WhatsApp via Fonnte
     *
     * @param array $orderData
     * @return void
     */
    private function sendOrderSuccessWhatsApp(array $orderData): void
    {
        $phone = $orderData['phone'];
        $orderNumber = $orderData['order_number'];
        $grandTotal = number_format($orderData['grand_total'], 0, ',', '.');

        $message = "✅ *Pembayaran Berhasil*\n\n"
            . "Halo {$orderData['customer_name']},\n\n"
            . "Pembayaran kamu telah berhasil dikonfirmasi!\n\n"
            . "📦 *Detail Pesanan:*\n"
            . "No. Pesanan: *{$orderNumber}*\n"
            . "Total Pembayaran: *Rp {$grandTotal}*\n"
            . "Status: *Lunas* ✓\n\n"
            . "Pesanan kamu akan segera diproses dan dikirim.\n"
            . "Kamu akan mendapat notifikasi saat pesanan dikirim.\n\n"
            . "Terima kasih telah berbelanja di XORIX! 🙏";

        $this->sendWhatsApp($phone, $message);
    }

    /**
     * Send order pending email
     *
     * @param array $orderData
     * @return void
     */
    private function sendOrderPendingEmail(array $orderData): void
    {
        $email = $orderData['email'];
        $orderNumber = $orderData['order_number'];
        $grandTotal = number_format($orderData['grand_total'], 0, ',', '.');
        $paymentMethod = $orderData['payment_method'];
        $expiryTime = $orderData['expiry_time'] ?? null;

        $subject = "Pesanan #{$orderNumber} - Menunggu Pembayaran";

        $message = "Halo {$orderData['customer_name']},\n\n"
            . "Pesanan kamu telah berhasil dibuat!\n\n"
            . "Detail Pesanan:\n"
            . "No. Pesanan: {$orderNumber}\n"
            . "Total Pembayaran: Rp {$grandTotal}\n"
            . "Metode Pembayaran: {$paymentMethod}\n\n";

        if ($expiryTime) {
            $message .= "Batas Waktu Pembayaran:\n"
                . "{$expiryTime}\n\n";
        }

        $message .= "Cara Pembayaran:\n"
            . $this->getPaymentInstructions($orderData)
            . "\n\n"
            . "Terima kasih telah berbelanja di XORIX!\n\n"
            . "Salam,\n"
            . "Tim XORIX";

        $this->sendEmail($email, $subject, $message);
    }

    /**
     * Send order success email
     *
     * @param array $orderData
     * @return void
     */
    private function sendOrderSuccessEmail(array $orderData): void
    {
        $email = $orderData['email'];
        $orderNumber = $orderData['order_number'];
        $grandTotal = number_format($orderData['grand_total'], 0, ',', '.');

        $subject = "Pembayaran Berhasil - Pesanan #{$orderNumber}";

        $message = "Halo {$orderData['customer_name']},\n\n"
            . "Pembayaran kamu telah berhasil dikonfirmasi!\n\n"
            . "Detail Pesanan:\n"
            . "No. Pesanan: {$orderNumber}\n"
            . "Total Pembayaran: Rp {$grandTotal}\n"
            . "Status: Lunas\n\n"
            . "Pesanan kamu akan segera diproses dan dikirim.\n"
            . "Kamu akan mendapat notifikasi saat pesanan dikirim.\n\n"
            . "Terima kasih telah berbelanja di XORIX!\n\n"
            . "Salam,\n"
            . "Tim XORIX";

        $this->sendEmail($email, $subject, $message);
    }

    /**
     * Get payment instructions based on payment method
     *
     * @param array $orderData
     * @return string
     */
    private function getPaymentInstructions(array $orderData): string
    {
        $paymentMethod = strtolower($orderData['payment_method']);
        $paymentDetails = $orderData['payment_details'] ?? [];

        // Virtual Account
        if (in_array($paymentMethod, ['bca', 'bni', 'bri', 'mandiri', 'permata', 'cimb'])) {
            $vaNumber = $paymentDetails['virtual_account_number'] ?? '-';
            $bank = strtoupper($paymentMethod);

            if ($paymentMethod === 'mandiri') {
                $billKey = $paymentDetails['bill_key'] ?? '-';
                $billerCode = $paymentDetails['biller_code'] ?? '-';
                return "Transfer ke Mandiri Bill Payment:\n"
                    . "Biller Code: {$billerCode}\n"
                    . "Bill Key: {$billKey}";
            }

            return "Transfer ke Virtual Account {$bank}:\n"
                . "No. VA: {$vaNumber}";
        }

        // QRIS
        if ($paymentMethod === 'qris') {
            return "Scan QRIS code yang telah dikirimkan\n"
                . "atau gunakan link pembayaran yang tersedia.";
        }

        return "Silakan lakukan pembayaran sesuai metode yang dipilih.";
    }

    /**
     * Send WhatsApp via Fonnte
     *
     * @param string $phone
     * @param string $message
     * @return void
     */
    private function sendWhatsApp(string $phone, string $message): void
    {
        try {
            $fonteUrl = config('services.fonnte.url');
            $fonteToken = config('services.fonnte.token');

            if (!$fonteUrl || !$fonteToken) {
                Log::warning('Fonnte configuration missing', [
                    'phone' => $phone,
                ]);
                return;
            }

            $response = Http::withHeaders([
                'Authorization' => $fonteToken,
            ])
                ->timeout(10)
                ->post($fonteUrl, [
                    'target'  => $phone,
                    'message' => $message,
                ]);

            if (!$response->successful()) {
                Log::error('Send WhatsApp failed', [
                    'phone' => $phone,
                    'response' => $response->body(),
                ]);
            } else {
                Log::info('WhatsApp sent successfully', [
                    'phone' => $phone,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Send WhatsApp exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send Email
     *
     * @param string $email
     * @param string $subject
     * @param string $message
     * @return void
     */
    private function sendEmail(string $email, string $subject, string $message): void
    {
        try {
            Mail::raw($message, function ($mail) use ($email, $subject) {
                $mail->to($email)
                    ->subject($subject);
            });

            Log::info('Email sent successfully', [
                'email' => $email,
                'subject' => $subject,
            ]);
        } catch (\Throwable $e) {
            Log::error('Send email failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
