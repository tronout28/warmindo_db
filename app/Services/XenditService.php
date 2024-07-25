<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Class XenditService.
 */
class XenditService
{
    protected $xenditApi;
    protected $xenditApiUrl = 'https://api.xendit.co/v2';

    public function __construct()
    {
        $this->xenditApi = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(env("XENDIT_SECRET_KEY") . ':'),
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Create a new payment.
     *
     * @param array $data
     * @return \Illuminate\Http\Client\Response
     */
    public function createInvoice(array $data)
    {
        return $this->xenditApi->post("{$this->xenditApiUrl}/invoices", $data);
    }

    /**
     * Get an invoice by ID.
     *
     * @param string $invoiceId
     * @return \Illuminate\Http\Client\Response
     */
    public function getInvoice(string $invoiceId)
    {
        return $this->xenditApi->get("{$this->xenditApiUrl}/invoices/{$invoiceId}");
    }

    /**
     * Get an invoice by ID.
     *
     * @param string $invoiceId
     * @return \Illuminate\Http\Client\Response
     */
    public function expireInvoice(string $invoiceId)
    {
        return $this->xenditApi->get("{$this->xenditApiUrl}/invoices/{$invoiceId}/expire!");
    }
}