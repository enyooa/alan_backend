<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class WhatsAppCheckController extends Controller
{
    public function checkWhatsApp(Request $request)
    {
        // Validate incoming data: ensure 'phoneNumber' is provided
        $request->validate([
            'phoneNumber' => 'required'
        ]);

        $rawNumber = $request->input('phoneNumber');

        // Step 1: Remove all non-digit characters
        $phoneNumber = preg_replace('/\D/', '', $rawNumber);

        // Step 2: Convert from +7 or 8 to standard 10-digit
        //   - If it’s 11 digits starting with '8', remove the first digit
        //   - If it’s 11 digits starting with '7', remove the first digit
        //   (e.g. +77076069831 -> 77076069831 -> remove leading '7' -> 7076069831
        //         87076069831  -> remove leading '8' -> 7076069831)
        if (strlen($phoneNumber) === 11) {
            // If it starts with '7' or '8', strip the first digit
            if ($phoneNumber[0] === '7' || $phoneNumber[0] === '8') {
                $phoneNumber = substr($phoneNumber, 1);
            }
        }

        // Now $phoneNumber should be the 10-digit format (e.g. 7076069831)

        // External API URL
        $url = "https://7105.api.greenapi.com/waInstance7105215666/checkWhatsapp/96df68496897444f89ec3dc7b044d4f45b1a0365634f4ab2ba";

        // Create Guzzle client
        $client = new Client();

        try {
            // Send POST request
            $response = $client->post($url, [
                'json' => [
                    'phoneNumber' => $phoneNumber, // final, normalized phone
                ]
            ]);

            // Parse and return JSON response
            $jsonResponse = json_decode($response->getBody(), true);

            return response()->json($jsonResponse, $response->getStatusCode());

        } catch (\Exception $e) {
            // Return error info if the request fails
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
